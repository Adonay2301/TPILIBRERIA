<?php
/**
 * Categorías del catálogo (tabla categorias).
 */

class CategoriaModel extends Model
{
    /** Listado con cantidad de libros visibles en el catálogo. */
    public function listar(string $q = ''): array
    {
        $sql = 'SELECT c.id_categoria, c.nombre, c.descripcion, COUNT(l.id_libro) AS libros
                  FROM categorias c
                  LEFT JOIN libros l ON l.id_categoria = c.id_categoria
                 WHERE c.nombre LIKE ?
                 GROUP BY c.id_categoria, c.nombre, c.descripcion
                 ORDER BY c.nombre';
        return $this->todos($sql, [$this->like($q)]);
    }

    /** Para selects y filtros: id, nombre y libros activos. */
    public function opciones(bool $soloConLibros = false): array
    {
        $sql = 'SELECT c.id_categoria AS id, c.nombre, COUNT(l.id_libro) AS libros
                  FROM categorias c
                  LEFT JOIN libros l ON l.id_categoria = c.id_categoria AND l.activo = 1
                 GROUP BY c.id_categoria, c.nombre'
             . ($soloConLibros ? ' HAVING libros > 0' : '')
             . ' ORDER BY c.nombre';
        return $this->todos($sql);
    }

    public function obtener(int $id): ?array
    {
        return $this->uno(
            'SELECT c.*, (SELECT COUNT(*) FROM libros WHERE id_categoria = c.id_categoria) AS libros
               FROM categorias c WHERE c.id_categoria = ?',
            [$id]
        );
    }

    public function nombreExiste(string $nombre, int $excluirId = 0): bool
    {
        return (bool) $this->valor('SELECT COUNT(*) FROM categorias WHERE nombre = ? AND id_categoria <> ?', [$nombre, $excluirId]);
    }

    public function crear(array $d): int
    {
        $this->ejecutar('INSERT INTO categorias (nombre, descripcion) VALUES (?, ?)', [$d['nombre'], $d['descripcion'] ?: null]);
        return $this->ultimoId();
    }

    public function actualizar(int $id, array $d): void
    {
        $this->ejecutar('UPDATE categorias SET nombre = ?, descripcion = ? WHERE id_categoria = ?', [$d['nombre'], $d['descripcion'] ?: null, $id]);
    }

    /** Devuelve false si hay libros en la categoría. */
    public function eliminar(int $id): bool
    {
        if ((int) $this->valor('SELECT COUNT(*) FROM libros WHERE id_categoria = ?', [$id]) > 0) {
            return false;
        }
        return $this->ejecutar('DELETE FROM categorias WHERE id_categoria = ?', [$id]) > 0;
    }
}
