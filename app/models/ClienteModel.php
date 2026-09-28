<?php
/**
 * Clientes (tablas clientes + usuarios) y sus datos de compra.
 */

class ClienteModel extends Model
{
    /**
     * Registra una cuenta de cliente con su primera dirección (todo o nada).
     * Devuelve los datos listos para Auth::iniciarSesion().
     */
    public function registrar(array $d): array
    {
        return $this->transaccion(function () use ($d) {
            $this->ejecutar(
                "INSERT INTO usuarios (correo, contrasena, rol) VALUES (?, ?, 'cliente')",
                [$d['correo'], password_hash($d['contrasena'], PASSWORD_DEFAULT)]
            );
            $idUsuario = $this->ultimoId();

            $this->ejecutar(
                'INSERT INTO clientes (id_usuario, nombres, apellidos, telefono) VALUES (?, ?, ?, ?)',
                [$idUsuario, $d['nombres'], $d['apellidos'], $d['telefono'] ?: null]
            );
            $idCliente = $this->ultimoId();

            $this->ejecutar(
                "INSERT INTO direcciones (id_cliente, alias, direccion, referencia, id_distrito, es_principal)
                 VALUES (?, 'Casa', ?, ?, ?, 1)",
                [$idCliente, $d['direccion'], $d['referencia'] ?: null, $d['id_distrito']]
            );

            return [
                'id_usuario' => $idUsuario,
                'id_perfil'  => $idCliente,
                'correo'     => $d['correo'],
                'rol'        => 'cliente',
                'nombre'     => $d['nombres'] . ' ' . $d['apellidos'],
            ];
        });
    }

    public function obtener(int $idCliente): ?array
    {
        return $this->uno(
            'SELECT c.id_cliente, c.id_usuario, c.nombres, c.apellidos, c.telefono, c.fecha_registro,
                    u.correo, u.activo, u.ultimo_acceso
               FROM clientes c
               JOIN usuarios u ON u.id_usuario = c.id_usuario
              WHERE c.id_cliente = ?',
            [$idCliente]
        );
    }

    /**
     * Consulta base del listado del panel: cliente + dirección principal + resumen de pedidos.
     * Los pedidos se agregan en una subconsulta para no duplicar filas.
     */
    private function consultaListado(array $f, array &$params): string
    {
        $sql = "SELECT c.id_cliente, c.nombres, c.apellidos, c.telefono, c.fecha_registro, u.correo, u.activo,
                       m.nombre AS municipio, dp.nombre AS departamento,
                       COALESCE(ps.pedidos, 0) AS pedidos, COALESCE(ps.total_gastado, 0) AS total_gastado,
                       COALESCE(ps.activos, 0) AS activos, COALESCE(ps.este_mes, 0) AS este_mes, ps.ultimo_pedido
                  FROM clientes c
                  JOIN usuarios u ON u.id_usuario = c.id_usuario
                  LEFT JOIN (SELECT id_cliente, MIN(id_direccion) AS id_direccion
                               FROM direcciones WHERE es_principal = 1 GROUP BY id_cliente) pd ON pd.id_cliente = c.id_cliente
                  LEFT JOIN direcciones dir     ON dir.id_direccion = pd.id_direccion
                  LEFT JOIN distritos d         ON d.id_distrito = dir.id_distrito
                  LEFT JOIN municipios m        ON m.id_municipio = d.id_municipio
                  LEFT JOIN departamentos dp    ON dp.id_departamento = m.id_departamento
                  LEFT JOIN (SELECT id_cliente,
                                    COUNT(*) AS pedidos,
                                    SUM(CASE WHEN estado <> 'cancelado' THEN total ELSE 0 END) AS total_gastado,
                                    SUM(estado IN ('pendiente', 'en_preparacion', 'enviado')) AS activos,
                                    SUM(fecha_pedido >= DATE_FORMAT(CURDATE(), '%Y-%m-01')) AS este_mes,
                                    MAX(fecha_pedido) AS ultimo_pedido
                               FROM pedidos GROUP BY id_cliente) ps ON ps.id_cliente = c.id_cliente
                 WHERE 1 = 1";

        if (!empty($f['q'])) {
            $sql .= " AND (CONCAT(c.nombres, ' ', c.apellidos) LIKE ? OR u.correo LIKE ? OR c.telefono LIKE ?)";
            array_push($params, $this->like($f['q']), $this->like($f['q']), $this->like($f['q']));
        }
        if (!empty($f['departamento'])) {
            $sql .= ' AND dp.id_departamento = ?';
            $params[] = (int) $f['departamento'];
        }
        $sql .= match ($f['actividad'] ?? '') {
            'activos'   => ' AND ps.activos > 0',
            'mes'       => ' AND ps.este_mes > 0',
            'sin'       => ' AND ps.pedidos IS NULL',
            'inactivos' => ' AND ps.ultimo_pedido < NOW() - INTERVAL 90 DAY',
            default     => '',
        };

        return $sql;
    }

    public function contarListado(array $filtros): int
    {
        $params = [];
        return (int) $this->valor('SELECT COUNT(*) FROM (' . $this->consultaListado($filtros, $params) . ') t', $params);
    }

    public function listar(array $filtros, int $limite, int $offset): array
    {
        $params = [];
        $sql = $this->consultaListado($filtros, $params)
             . ' ORDER BY c.fecha_registro DESC LIMIT ' . (int) $limite . ' OFFSET ' . (int) $offset;
        return $this->todos($sql, $params);
    }

    /** Indicadores de la pantalla de clientes. */
    public function estadisticas(): array
    {
        return $this->uno(
            "SELECT (SELECT COUNT(*) FROM clientes) AS registrados,
                    (SELECT COUNT(*) FROM clientes WHERE fecha_registro >= DATE_FORMAT(CURDATE(), '%Y-%m-01')) AS nuevos_mes,
                    (SELECT COUNT(*) FROM clientes
                      WHERE fecha_registro >= DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-01')
                        AND fecha_registro <  DATE_FORMAT(CURDATE(), '%Y-%m-01')) AS nuevos_mes_anterior,
                    (SELECT COUNT(DISTINCT id_cliente) FROM pedidos
                      WHERE estado IN ('pendiente', 'en_preparacion', 'enviado')) AS con_activos,
                    (SELECT COALESCE(AVG(total), 0) FROM pedidos WHERE estado <> 'cancelado') AS ticket_promedio"
        );
    }

    /** Resumen de compras de un cliente para su ficha. */
    public function resumenCompras(int $idCliente): array
    {
        return $this->uno(
            "SELECT COUNT(*) AS pedidos,
                    COALESCE(SUM(CASE WHEN estado <> 'cancelado' THEN total END), 0) AS total_gastado,
                    COALESCE(AVG(CASE WHEN estado <> 'cancelado' THEN total END), 0) AS ticket_promedio
               FROM pedidos WHERE id_cliente = ?",
            [$idCliente]
        );
    }

    /** Libros que más ha comprado el cliente. */
    public function librosMasComprados(int $idCliente, int $limite = 3): array
    {
        return $this->todos(
            "SELECT l.id_libro, l.titulo, l.portada, SUM(pd.cantidad) AS veces
               FROM pedido_detalles pd
               JOIN pedidos p ON p.id_pedido = pd.id_pedido AND p.estado <> 'cancelado'
               JOIN libros l  ON l.id_libro = pd.id_libro
              WHERE p.id_cliente = ?
              GROUP BY l.id_libro, l.titulo, l.portada
              ORDER BY veces DESC, l.titulo
              LIMIT " . (int) $limite,
            [$idCliente]
        );
    }

    /** Los tres clientes que más han gastado (estrella en el listado). */
    public function topCompradores(int $limite = 3): array
    {
        return array_column($this->todos(
            "SELECT id_cliente FROM pedidos WHERE estado <> 'cancelado'
              GROUP BY id_cliente ORDER BY SUM(total) DESC LIMIT " . (int) $limite
        ), 'id_cliente');
    }
}
