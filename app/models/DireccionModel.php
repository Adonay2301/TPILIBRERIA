<?php
/**
 * Libreta de direcciones del cliente (tabla direcciones).
 */

class DireccionModel extends Model
{
    private const SELECT = "SELECT dir.id_direccion, dir.alias, dir.direccion, dir.referencia, dir.es_principal,
                                   d.id_distrito, d.nombre AS distrito, m.nombre AS municipio, dp.nombre AS departamento
                              FROM direcciones dir
                              JOIN distritos d      ON d.id_distrito = dir.id_distrito
                              JOIN municipios m     ON m.id_municipio = d.id_municipio
                              JOIN departamentos dp ON dp.id_departamento = m.id_departamento";

    public function delCliente(int $idCliente): array
    {
        return $this->todos(self::SELECT . ' WHERE dir.id_cliente = ? ORDER BY dir.es_principal DESC, dir.id_direccion', [$idCliente]);
    }

    /** Una dirección, solo si pertenece al cliente. */
    public function obtenerDeCliente(int $idDireccion, int $idCliente): ?array
    {
        return $this->uno(self::SELECT . ' WHERE dir.id_direccion = ? AND dir.id_cliente = ?', [$idDireccion, $idCliente]);
    }

    public function crear(int $idCliente, array $datos): int
    {
        $tienePrincipal = (bool) $this->valor(
            'SELECT COUNT(*) FROM direcciones WHERE id_cliente = ? AND es_principal = 1',
            [$idCliente]
        );

        $this->ejecutar(
            'INSERT INTO direcciones (id_cliente, alias, direccion, referencia, id_distrito, es_principal)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $idCliente,
                $datos['alias'] ?: 'Casa',
                $datos['direccion'],
                $datos['referencia'] ?: null,
                $datos['id_distrito'],
                $tienePrincipal ? 0 : 1, // la primera dirección queda como principal
            ]
        );
        return $this->ultimoId();
    }
}
