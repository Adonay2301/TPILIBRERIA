<?php
/**
 * División territorial de El Salvador: departamentos -> municipios -> distritos.
 */

class UbicacionModel extends Model
{
    public function departamentos(): array
    {
        return $this->todos('SELECT id_departamento AS id, nombre FROM departamentos ORDER BY nombre');
    }

    public function municipios(int $idDepartamento): array
    {
        return $this->todos(
            'SELECT id_municipio AS id, nombre FROM municipios WHERE id_departamento = ? ORDER BY nombre',
            [$idDepartamento]
        );
    }

    public function distritos(int $idMunicipio): array
    {
        return $this->todos(
            'SELECT id_distrito AS id, nombre FROM distritos WHERE id_municipio = ? ORDER BY nombre',
            [$idMunicipio]
        );
    }

    /** Distrito con su municipio y departamento, o null si no existe. */
    public function distrito(int $idDistrito): ?array
    {
        return $this->uno(
            'SELECT d.id_distrito, d.nombre AS distrito, m.id_municipio, m.nombre AS municipio,
                    dp.id_departamento, dp.nombre AS departamento
               FROM distritos d
               JOIN municipios m     ON m.id_municipio = d.id_municipio
               JOIN departamentos dp ON dp.id_departamento = m.id_departamento
              WHERE d.id_distrito = ?',
            [$idDistrito]
        );
    }
}
