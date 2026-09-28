<?php
/**
 * Clase base de los modelos: da acceso a la conexión PDO y atajos para
 * ejecutar sentencias preparadas. Todo el SQL del sistema vive en los modelos.
 */

abstract class Model
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::conexion();
    }

    /** Ejecuta una sentencia preparada y devuelve el statement. */
    protected function consulta(string $sql, array $parametros = []): PDOStatement
    {
        $st = $this->db->prepare($sql);
        $st->execute($parametros);
        return $st;
    }

    /** Una fila o null. */
    protected function uno(string $sql, array $parametros = []): ?array
    {
        $fila = $this->consulta($sql, $parametros)->fetch();
        return $fila === false ? null : $fila;
    }

    /** Todas las filas. */
    protected function todos(string $sql, array $parametros = []): array
    {
        return $this->consulta($sql, $parametros)->fetchAll();
    }

    /** Primer valor de la primera fila (COUNT, SUM...). */
    protected function valor(string $sql, array $parametros = []): mixed
    {
        $valor = $this->consulta($sql, $parametros)->fetchColumn();
        return $valor === false ? null : $valor;
    }

    /** INSERT/UPDATE/DELETE: devuelve las filas afectadas. */
    protected function ejecutar(string $sql, array $parametros = []): int
    {
        return $this->consulta($sql, $parametros)->rowCount();
    }

    protected function ultimoId(): int
    {
        return (int) $this->db->lastInsertId();
    }

    /** Texto para LIKE con los comodines escapados: 'garcía' -> '%garcía%' */
    protected function like(string $texto): string
    {
        return '%' . addcslashes($texto, '%_\\') . '%';
    }

    /** Marcadores para IN (...): [3, 5, 8] -> '?,?,?' */
    protected function marcadores(array $valores): string
    {
        return implode(',', array_fill(0, count($valores), '?'));
    }

    /** Ejecuta varias operaciones en una transacción; si algo falla, deshace todo. */
    protected function transaccion(callable $operaciones): mixed
    {
        $this->db->beginTransaction();
        try {
            $resultado = $operaciones();
            $this->db->commit();
            return $resultado;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
