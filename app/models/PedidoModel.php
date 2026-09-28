<?php
/**
 * Pedidos, su detalle y su historial de estados.
 *
 * subtotal, costo_envio y total los calculan los triggers de la base de datos:
 * este modelo nunca los escribe. El historial también lo llenan los triggers,
 * usando las variables de sesión @id_usuario_actual y @comentario_estado.
 */

class PedidoModel extends Model
{
    /** Transiciones de estado permitidas. */
    public const TRANSICIONES = [
        'pendiente'      => ['en_preparacion', 'cancelado'],
        'en_preparacion' => ['enviado', 'cancelado'],
        'enviado'        => ['entregado'],
        'entregado'      => [],
        'cancelado'      => [],
    ];

    private const SELECT_LISTADO = "SELECT p.id_pedido, p.fecha_pedido, p.estado, p.subtotal, p.costo_envio, p.total,
                                           p.id_empleado, p.id_cliente,
                                           CONCAT(c.nombres, ' ', c.apellidos) AS cliente, u.correo,
                                           CONCAT(e.nombres, ' ', e.apellidos) AS empleado,
                                           (SELECT SUM(cantidad) FROM pedido_detalles WHERE id_pedido = p.id_pedido) AS articulos,
                                           m.nombre AS municipio
                                      FROM pedidos p
                                      JOIN clientes c       ON c.id_cliente = p.id_cliente
                                      JOIN usuarios u       ON u.id_usuario = c.id_usuario
                                      JOIN distritos d      ON d.id_distrito = p.id_distrito
                                      JOIN municipios m     ON m.id_municipio = d.id_municipio
                                      LEFT JOIN empleados e ON e.id_empleado = p.id_empleado";

    // -----------------------------------------------------------------
    // Crear pedido (cliente)
    // -----------------------------------------------------------------

    /**
     * Convierte el carrito en un pedido. Todo ocurre en una transacción:
     * valida stock, inserta el pedido y sus detalles (los triggers calculan montos) y vacía el carrito.
     */
    public function crearDesdeCarrito(int $idCliente, array $entrega): int
    {
        return $this->transaccion(function () use ($idCliente, $entrega) {
            $items = $this->todos(
                'SELECT cd.id_carrito, cd.id_libro, cd.cantidad, l.titulo, l.precio, l.stock_actual, l.activo
                   FROM carritos c
                   JOIN carrito_detalles cd ON cd.id_carrito = c.id_carrito
                   JOIN libros l ON l.id_libro = cd.id_libro
                  WHERE c.id_cliente = ?
                  FOR UPDATE',
                [$idCliente]
            );

            if (!$items) {
                throw new DomainException('Tu carrito está vacío.');
            }
            foreach ($items as $item) {
                if (!$item['activo'] || (int) $item['stock_actual'] < (int) $item['cantidad']) {
                    throw new DomainException("No hay suficientes unidades de «{$item['titulo']}». Ajusta la cantidad en tu carrito.");
                }
            }

            $this->ejecutar(
                'INSERT INTO pedidos (id_cliente, direccion_entrega, referencia_entrega, id_distrito, telefono_contacto, notas)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [$idCliente, $entrega['direccion'], $entrega['referencia'] ?: null, $entrega['id_distrito'],
                 $entrega['telefono'], $entrega['notas'] ?: null]
            );
            $idPedido = $this->ultimoId();

            foreach ($items as $item) {
                // El precio se congela al momento de la compra
                $this->ejecutar(
                    'INSERT INTO pedido_detalles (id_pedido, id_libro, cantidad, precio_unitario) VALUES (?, ?, ?, ?)',
                    [$idPedido, $item['id_libro'], $item['cantidad'], $item['precio']]
                );
            }

            $this->ejecutar('DELETE FROM carrito_detalles WHERE id_carrito = ?', [$items[0]['id_carrito']]);

            return $idPedido;
        });
    }

    // -----------------------------------------------------------------
    // Consultas de un pedido
    // -----------------------------------------------------------------

    /** Pedido con cliente, dirección completa y empleado. $idCliente limita a los pedidos de ese cliente. */
    public function obtener(int $idPedido, ?int $idCliente = null): ?array
    {
        $sql = "SELECT p.*, CONCAT(c.nombres, ' ', c.apellidos) AS cliente, c.telefono AS telefono_cliente, u.correo,
                       d.nombre AS distrito, m.nombre AS municipio, dp.nombre AS departamento,
                       CONCAT(e.nombres, ' ', e.apellidos) AS empleado
                  FROM pedidos p
                  JOIN clientes c       ON c.id_cliente = p.id_cliente
                  JOIN usuarios u       ON u.id_usuario = c.id_usuario
                  JOIN distritos d      ON d.id_distrito = p.id_distrito
                  JOIN municipios m     ON m.id_municipio = d.id_municipio
                  JOIN departamentos dp ON dp.id_departamento = m.id_departamento
                  LEFT JOIN empleados e ON e.id_empleado = p.id_empleado
                 WHERE p.id_pedido = ?";
        $params = [$idPedido];
        if ($idCliente !== null) {
            $sql .= ' AND p.id_cliente = ?';
            $params[] = $idCliente;
        }
        return $this->uno($sql, $params);
    }

    public function detalles(int $idPedido): array
    {
        return $this->todos(
            "SELECT pd.id_libro, pd.cantidad, pd.precio_unitario, pd.subtotal, l.titulo, l.portada,
                    (SELECT GROUP_CONCAT(CONCAT(a.nombres, ' ', a.apellidos) ORDER BY la.orden SEPARATOR ', ')
                       FROM libros_autores la JOIN autores a ON a.id_autor = la.id_autor
                      WHERE la.id_libro = l.id_libro) AS autores
               FROM pedido_detalles pd
               JOIN libros l ON l.id_libro = pd.id_libro
              WHERE pd.id_pedido = ?
              ORDER BY pd.id_pedido_detalle",
            [$idPedido]
        );
    }

    /** Detalles de varios pedidos agrupados por id (tarjetas con portadas). */
    public function detallesDe(array $idsPedidos): array
    {
        if (!$idsPedidos) {
            return [];
        }
        $filas = $this->todos(
            'SELECT pd.id_pedido, pd.id_libro, pd.cantidad, l.titulo, l.portada
               FROM pedido_detalles pd JOIN libros l ON l.id_libro = pd.id_libro
              WHERE pd.id_pedido IN (' . $this->marcadores($idsPedidos) . ')
              ORDER BY pd.id_pedido_detalle',
            array_values($idsPedidos)
        );
        $agrupado = [];
        foreach ($filas as $fila) {
            $agrupado[$fila['id_pedido']][] = $fila;
        }
        return $agrupado;
    }

    /** Historial de estados con el nombre y rol de quien hizo el cambio. */
    public function historial(int $idPedido): array
    {
        return $this->todos(
            "SELECT h.estado_anterior, h.estado_nuevo, h.comentario, h.fecha_cambio, u.rol,
                    CONCAT_WS(' ', COALESCE(c.nombres, e.nombres, a.nombres), COALESCE(c.apellidos, e.apellidos, a.apellidos)) AS usuario
               FROM historial_estados_pedido h
               LEFT JOIN usuarios u        ON u.id_usuario = h.id_usuario
               LEFT JOIN clientes c        ON c.id_usuario = u.id_usuario
               LEFT JOIN empleados e       ON e.id_usuario = u.id_usuario
               LEFT JOIN administradores a ON a.id_usuario = u.id_usuario
              WHERE h.id_pedido = ?
              ORDER BY h.fecha_cambio, h.id_historial",
            [$idPedido]
        );
    }

    // -----------------------------------------------------------------
    // Pedidos del cliente
    // -----------------------------------------------------------------

    public function delCliente(int $idCliente): array
    {
        return $this->todos(self::SELECT_LISTADO . ' WHERE p.id_cliente = ? ORDER BY p.fecha_pedido DESC', [$idCliente]);
    }

    public function ultimoDelCliente(int $idCliente): ?array
    {
        return $this->uno(self::SELECT_LISTADO . ' WHERE p.id_cliente = ? ORDER BY p.fecha_pedido DESC LIMIT 1', [$idCliente]);
    }

    // -----------------------------------------------------------------
    // Listado del panel
    // -----------------------------------------------------------------

    /** $f: q, estado, desde, hasta, id_empleado, sin_asignar */
    private function filtros(array $f, array &$params): string
    {
        $sql = ' WHERE 1 = 1';
        if (!empty($f['q'])) {
            $q = preg_replace('/^PYA-0*/i', '', $f['q']); // permite buscar por código PYA-000012
            $sql .= " AND (p.id_pedido = ? OR CONCAT(c.nombres, ' ', c.apellidos) LIKE ? OR u.correo LIKE ?)";
            array_push($params, ctype_digit($q) ? (int) $q : 0, $this->like($f['q']), $this->like($f['q']));
        }
        if (!empty($f['estado']) && isset(self::TRANSICIONES[$f['estado']])) {
            $sql .= ' AND p.estado = ?';
            $params[] = $f['estado'];
        }
        if (!empty($f['desde'])) {
            $sql .= ' AND p.fecha_pedido >= ?';
            $params[] = $f['desde'];
        }
        if (!empty($f['hasta'])) {
            $sql .= ' AND p.fecha_pedido < ? + INTERVAL 1 DAY';
            $params[] = $f['hasta'];
        }
        if (!empty($f['id_empleado'])) {
            $sql .= ' AND p.id_empleado = ?';
            $params[] = (int) $f['id_empleado'];
        }
        return $sql;
    }

    public function contar(array $filtros): int
    {
        $params = [];
        return (int) $this->valor(
            'SELECT COUNT(*) FROM pedidos p JOIN clientes c ON c.id_cliente = p.id_cliente
               JOIN usuarios u ON u.id_usuario = c.id_usuario' . $this->filtros($filtros, $params),
            $params
        );
    }

    public function listar(array $filtros, int $limite, int $offset): array
    {
        $params = [];
        return $this->todos(
            self::SELECT_LISTADO . $this->filtros($filtros, $params)
            . ' ORDER BY p.fecha_pedido DESC LIMIT ' . (int) $limite . ' OFFSET ' . (int) $offset,
            $params
        );
    }

    /** Contadores de la pantalla de pedidos. */
    public function contadores(): array
    {
        return $this->uno(
            "SELECT COALESCE(SUM(DATE(fecha_pedido) = CURDATE()), 0) AS hoy,
                    COALESCE(SUM(estado = 'pendiente'), 0) AS pendientes,
                    COALESCE(SUM(estado = 'en_preparacion'), 0) AS preparando,
                    COALESCE(SUM(estado = 'enviado'), 0) AS enviados,
                    COALESCE(SUM(estado = 'entregado' AND fecha_actualizacion >= DATE_FORMAT(CURDATE(), '%Y-%m-01')), 0) AS entregados_mes
               FROM pedidos"
        );
    }

    /** Pedidos pendientes sin empleado (los que un empleado puede tomar). */
    public function disponibles(): array
    {
        return $this->todos(self::SELECT_LISTADO . " WHERE p.estado = 'pendiente' AND p.id_empleado IS NULL ORDER BY p.fecha_pedido");
    }

    /** Pedidos de un empleado (opcionalmente solo en ciertos estados). */
    public function delEmpleado(int $idEmpleado, array $estados = []): array
    {
        $sql = self::SELECT_LISTADO . ' WHERE p.id_empleado = ?';
        $params = [$idEmpleado];
        if ($estados) {
            $sql .= ' AND p.estado IN (' . $this->marcadores($estados) . ')';
            array_push($params, ...$estados);
        }
        return $this->todos($sql . ' ORDER BY p.fecha_pedido DESC', $params);
    }

    /** Pedidos en preparación (todos, o solo los de un empleado). */
    public function enPreparacion(?int $idEmpleado = null): array
    {
        $sql = self::SELECT_LISTADO . " WHERE p.estado = 'en_preparacion'";
        $params = [];
        if ($idEmpleado !== null) {
            $sql .= ' AND p.id_empleado = ?';
            $params[] = $idEmpleado;
        }
        return $this->todos($sql . ' ORDER BY empleado IS NULL, empleado, p.fecha_pedido', $params);
    }

    public function recientes(int $limite = 5): array
    {
        return $this->todos(self::SELECT_LISTADO . ' ORDER BY p.fecha_pedido DESC LIMIT ' . (int) $limite);
    }

    // -----------------------------------------------------------------
    // Cambios de estado
    // -----------------------------------------------------------------

    /**
     * Cambia el estado del pedido (y opcionalmente asigna empleado) en una transacción.
     * - pendiente -> en_preparacion: registra la salida de inventario (venta).
     * - en_preparacion -> cancelado: devuelve el stock (devolución).
     * El trigger escribe el historial con @comentario_estado.
     */
    public function cambiarEstado(int $idPedido, string $nuevo, ?string $comentario, int $idUsuario, ?int $idEmpleado = null): void
    {
        $this->transaccion(function () use ($idPedido, $nuevo, $comentario, $idUsuario, $idEmpleado) {
            $pedido = $this->uno('SELECT estado FROM pedidos WHERE id_pedido = ? FOR UPDATE', [$idPedido]);
            if (!$pedido) {
                throw new DomainException('El pedido no existe.');
            }
            if (!in_array($nuevo, self::TRANSICIONES[$pedido['estado']], true)) {
                throw new DomainException('Ese cambio de estado no está permitido.');
            }

            if ($pedido['estado'] === 'pendiente' && $nuevo === 'en_preparacion') {
                $this->moverInventario($idPedido, 'salida', 'venta', $idUsuario);
            }
            if ($pedido['estado'] === 'en_preparacion' && $nuevo === 'cancelado') {
                $this->moverInventario($idPedido, 'entrada', 'devolucion', $idUsuario);
            }

            $this->ejecutar('SET @comentario_estado = ?', [$comentario !== '' ? $comentario : null]);
            try {
                if ($idEmpleado !== null) {
                    $this->ejecutar('UPDATE pedidos SET estado = ?, id_empleado = ? WHERE id_pedido = ?', [$nuevo, $idEmpleado, $idPedido]);
                } else {
                    $this->ejecutar('UPDATE pedidos SET estado = ? WHERE id_pedido = ?', [$nuevo, $idPedido]);
                }
            } finally {
                $this->ejecutar('SET @comentario_estado = NULL');
            }
        });
    }

    /** Salidas (venta) o entradas (devolución) de todos los libros del pedido. */
    private function moverInventario(int $idPedido, string $tipo, string $motivo, int $idUsuario): void
    {
        foreach ($this->todos('SELECT id_libro, cantidad FROM pedido_detalles WHERE id_pedido = ?', [$idPedido]) as $d) {
            try {
                $this->ejecutar(
                    'INSERT INTO movimientos_inventario (id_libro, tipo, motivo, cantidad, id_usuario, id_pedido, observacion)
                     VALUES (?, ?, ?, ?, ?, ?, ?)',
                    [$d['id_libro'], $tipo, $motivo, $d['cantidad'], $idUsuario, $idPedido,
                     ($motivo === 'venta' ? 'Venta pedido ' : 'Devolución pedido ') . codigoPedido($idPedido)]
                );
            } catch (PDOException $e) {
                // SQLSTATE 45000 = mensaje del trigger (stock insuficiente)
                if ($e->getCode() === '45000') {
                    throw new DomainException('No hay stock suficiente para preparar este pedido.');
                }
                throw $e;
            }
        }
    }

    /** Asigna (o quita con null) el empleado. El trigger rechaza empleados dados de baja. */
    public function asignar(int $idPedido, ?int $idEmpleado): void
    {
        try {
            $this->ejecutar('UPDATE pedidos SET id_empleado = ? WHERE id_pedido = ?', [$idEmpleado, $idPedido]);
        } catch (PDOException $e) {
            if ($e->getCode() === '45000') {
                throw new DomainException('El empleado está dado de baja.');
            }
            throw $e;
        }
    }

    // -----------------------------------------------------------------
    // Estadísticas (panel y reportes). "Ventas" = pedidos no cancelados.
    // -----------------------------------------------------------------

    public function resumenVentas(string $desde, string $hasta): array
    {
        return $this->uno(
            "SELECT COALESCE(SUM(p.total), 0) AS ventas, COUNT(*) AS pedidos,
                    COALESCE(AVG(p.total), 0) AS ticket_promedio,
                    COALESCE(SUM((SELECT SUM(cantidad) FROM pedido_detalles WHERE id_pedido = p.id_pedido)), 0) AS libros
               FROM pedidos p
              WHERE p.estado <> 'cancelado' AND p.fecha_pedido >= ? AND p.fecha_pedido < ? + INTERVAL 1 DAY",
            [$desde, $hasta]
        );
    }

    /** Ventas por día, con los días sin ventas en cero. */
    public function ventasPorDia(string $desde, string $hasta): array
    {
        $filas = $this->todos(
            "SELECT DATE(fecha_pedido) AS dia, SUM(total) AS ventas
               FROM pedidos
              WHERE estado <> 'cancelado' AND fecha_pedido >= ? AND fecha_pedido < ? + INTERVAL 1 DAY
              GROUP BY DATE(fecha_pedido)",
            [$desde, $hasta]
        );
        $porDia = array_column($filas, 'ventas', 'dia');

        $serie = [];
        for ($d = new DateTime($desde), $fin = new DateTime($hasta); $d <= $fin; $d->modify('+1 day')) {
            $clave = $d->format('Y-m-d');
            $serie[] = ['dia' => $clave, 'etiqueta' => $d->format('d/m'), 'valor' => (float) ($porDia[$clave] ?? 0)];
        }
        return $serie;
    }

    /** Clientes nuevos en un período. */
    public function clientesNuevos(string $desde, string $hasta): int
    {
        return (int) $this->valor(
            'SELECT COUNT(*) FROM clientes WHERE fecha_registro >= ? AND fecha_registro < ? + INTERVAL 1 DAY',
            [$desde, $hasta]
        );
    }

    /** Pedidos por empleado y estado (tabla "Seguimiento por empleado"). */
    public function seguimientoPorEmpleado(): array
    {
        return $this->todos(
            "SELECT e.id_empleado, CONCAT(e.nombres, ' ', e.apellidos) AS empleado, e.estado AS estado_empleado,
                    COUNT(p.id_pedido) AS total,
                    COALESCE(SUM(p.estado = 'en_preparacion'), 0) AS preparando,
                    COALESCE(SUM(p.estado = 'enviado'), 0) AS enviados,
                    COALESCE(SUM(p.estado = 'entregado'), 0) AS entregados
               FROM empleados e
               LEFT JOIN pedidos p ON p.id_empleado = e.id_empleado AND p.estado <> 'cancelado'
              GROUP BY e.id_empleado, e.nombres, e.apellidos, e.estado
             HAVING e.estado = 'activo' OR total > 0
              ORDER BY empleado"
        );
    }
}
