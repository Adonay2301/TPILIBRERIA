<?php
/**
 * Editoriales (tabla editoriales).
 */

class EditorialModel extends Model
{
    public function listar(string $q = ''): array
    {
        return $this->todos(
            'SELECT e.id_editorial, e.nombre, e.pais, COUNT(l.id_libro) AS libros
               FROM editoriales e
               LEFT JOIN libros l ON l.id_editorial = e.id_editorial
              WHERE e.nombre LIKE ? OR e.pais LIKE ?
              GROUP BY e.id_editorial, e.nombre, e.pais
              ORDER BY e.nombre',
            [$this->like($q), $this->like($q)]
        );
    }

    public function opciones(bool $soloConLibros = false): array
    {
        $sql = 'SELECT e.id_editorial AS id, e.nombre, COUNT(l.id_libro) AS libros
                  FROM editoriales e
                  LEFT JOIN libros l ON l.id_editorial = e.id_editorial AND l.activo = 1
                 GROUP BY e.id_editorial, e.nombre'
             . ($soloConLibros ? ' HAVING libros > 0' : '')
             . ' ORDER BY e.nombre';
        return $this->todos($sql);
    }

    public function obtener(int $id): ?array
    {
        return $this->uno(
            'SELECT e.*, (SELECT COUNT(*) FROM libros WHERE id_editorial = e.id_editorial) AS libros
               FROM editoriales e WHERE e.id_editorial = ?',
            [$id]
        );
    }

    public function nombreExiste(string $nombre, int $excluirId = 0): bool
    {
        return (bool) $this->valor('SELECT COUNT(*) FROM editoriales WHERE nombre = ? AND id_editorial <> ?', [$nombre, $excluirId]);
    }

    public function crear(array $d): int
    {
        $this->ejecutar('INSERT INTO editoriales (nombre, pais) VALUES (?, ?)', [$d['nombre'], $d['pais'] ?: null]);
        return $this->ultimoId();
    }

    public function actualizar(int $id, array $d): void
    {
        $this->ejecutar('UPDATE editoriales SET nombre = ?, pais = ? WHERE id_editorial = ?', [$d['nombre'], $d['pais'] ?: null, $id]);
    }

    public function eliminar(int $id): bool
    {
        if ((int) $this->valor('SELECT COUNT(*) FROM libros WHERE id_editorial = ?', [$id]) > 0) {
            return false;
        }
        return $this->ejecutar('DELETE FROM editoriales WHERE id_editorial = ?', [$id]) > 0;
    }
}
