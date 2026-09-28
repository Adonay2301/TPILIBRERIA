<?php
/**
 * Empleados (tablas empleados + usuarios). Nunca se eliminan: baja lógica.
 */

class EmpleadoModel extends Model
{
    private const SELECT = "SELECT e.id_empleado, e.id_usuario, e.nombres, e.apellidos, e.dui, e.telefono,
                                   e.fecha_contratacion, e.estado, e.fecha_baja, e.motivo_baja,
                                   u.correo, u.ultimo_acceso, u.activo,
                                   (SELECT COUNT(*) FROM pedidos WHERE id_empleado = e.id_empleado
                                       AND estado = 'en_preparacion') AS pedidos_pendientes
                              FROM empleados e
                              JOIN usuarios u ON u.id_usuario = e.id_usuario";

    public function listar(string $q = '', string $estado = ''): array
    {
        $sql = self::SELECT . ' WHERE 1 = 1';
        $params = [];
        if ($q !== '') {
            $sql .= " AND (CONCAT(e.nombres, ' ', e.apellidos) LIKE ? OR u.correo LIKE ? OR e.dui LIKE ?)";
            array_push($params, $this->like($q), $this->like($q), $this->like($q));
        }
        if (in_array($estado, ['activo', 'baja'], true)) {
            $sql .= ' AND e.estado = ?';
            $params[] = $estado;
        }
        return $this->todos($sql . " ORDER BY e.estado = 'baja', e.apellidos, e.nombres", $params);
    }

    public function estadisticas(): array
    {
        return $this->uno(
            "SELECT COUNT(*) AS total, COALESCE(SUM(estado = 'activo'), 0) AS activos, COALESCE(SUM(estado = 'baja'), 0) AS inactivos
               FROM empleados"
        );
    }

    public function obtener(int $id): ?array
    {
        return $this->uno(self::SELECT . ' WHERE e.id_empleado = ?', [$id]);
    }

    /** Empleados activos (para asignar pedidos). */
    public function activos(): array
    {
        return $this->todos(
            "SELECT id_empleado, CONCAT(nombres, ' ', apellidos) AS nombre FROM empleados WHERE estado = 'activo' ORDER BY nombres"
        );
    }

    public function duiExiste(string $dui, int $excluirId = 0): bool
    {
        return (bool) $this->valor('SELECT COUNT(*) FROM empleados WHERE dui = ? AND id_empleado <> ?', [$dui, $excluirId]);
    }

    /** Crea la cuenta de acceso y el perfil del empleado. */
    public function crear(array $d, string $contrasena): int
    {
        return $this->transaccion(function () use ($d, $contrasena) {
            $this->ejecutar(
                "INSERT INTO usuarios (correo, contrasena, rol) VALUES (?, ?, 'empleado')",
                [$d['correo'], password_hash($contrasena, PASSWORD_DEFAULT)]
            );
            $idUsuario = $this->ultimoId();

            $this->ejecutar(
                'INSERT INTO empleados (id_usuario, nombres, apellidos, dui, telefono, fecha_contratacion) VALUES (?, ?, ?, ?, ?, ?)',
                [$idUsuario, $d['nombres'], $d['apellidos'], $d['dui'], $d['telefono'] ?: null, $d['fecha_contratacion']]
            );
            return $this->ultimoId();
        });
    }

    public function actualizar(int $id, array $d): void
    {
        $this->transaccion(function () use ($id, $d) {
            $this->ejecutar(
                'UPDATE usuarios u JOIN empleados e ON e.id_usuario = u.id_usuario SET u.correo = ? WHERE e.id_empleado = ?',
                [$d['correo'], $id]
            );
            $this->ejecutar(
                'UPDATE empleados SET nombres = ?, apellidos = ?, dui = ?, telefono = ?, fecha_contratacion = ? WHERE id_empleado = ?',
                [$d['nombres'], $d['apellidos'], $d['dui'], $d['telefono'] ?: null, $d['fecha_contratacion'], $id]
            );
        });
    }

    /**
     * Baja lógica: libera sus pedidos en preparación para reasignarlos y lo marca como 'baja'.
     * El trigger llena fecha_baja y desactiva su usuario. Devuelve los pedidos liberados.
     */
    public function darDeBaja(int $id, string $motivo): int
    {
        return $this->transaccion(function () use ($id, $motivo) {
            $liberados = $this->ejecutar(
                "UPDATE pedidos SET id_empleado = NULL WHERE id_empleado = ? AND estado = 'en_preparacion'",
                [$id]
            );
            $this->ejecutar(
                "UPDATE empleados SET estado = 'baja', motivo_baja = ? WHERE id_empleado = ? AND estado = 'activo'",
                [$motivo !== '' ? $motivo : null, $id]
            );
            return $liberados;
        });
    }

    /** El trigger limpia fecha_baja y reactiva el usuario. */
    public function reactivar(int $id): void
    {
        $this->ejecutar("UPDATE empleados SET estado = 'activo' WHERE id_empleado = ?", [$id]);
    }

    /** Indicadores de la ficha del empleado. */
    public function metricas(int $idEmpleado, int $idUsuario): array
    {
        return $this->uno(
            "SELECT (SELECT COUNT(DISTINCT h.id_pedido) FROM historial_estados_pedido h
                      WHERE h.id_usuario = ? AND h.estado_nuevo = 'enviado'
                        AND h.fecha_cambio >= DATE_FORMAT(CURDATE(), '%Y-%m-01')) AS preparados_mes,
                    (SELECT COUNT(*) FROM movimientos_inventario
                      WHERE id_usuario = ? AND tipo = 'entrada') AS entradas_inventario,
                    (SELECT COUNT(*) FROM pedidos WHERE id_empleado = ?) AS pedidos_total",
            [$idUsuario, $idUsuario, $idEmpleado]
        );
    }

    /** Actividad reciente: cambios de estado de pedidos y movimientos de inventario. */
    public function actividad(int $idUsuario, int $limite = 10): array
    {
        return $this->todos(
            "(SELECT 'pedido' AS tipo, h.fecha_cambio AS fecha, h.id_pedido, h.estado_nuevo AS detalle, NULL AS titulo, NULL AS cantidad
                FROM historial_estados_pedido h WHERE h.id_usuario = ?)
             UNION ALL
             (SELECT 'inventario', m.fecha, m.id_pedido, m.motivo, l.titulo, m.cantidad
                FROM movimientos_inventario m JOIN libros l ON l.id_libro = m.id_libro
               WHERE m.id_usuario = ? AND m.tipo = 'entrada')
             ORDER BY fecha DESC LIMIT " . (int) $limite,
            [$idUsuario, $idUsuario]
        );
    }
}
