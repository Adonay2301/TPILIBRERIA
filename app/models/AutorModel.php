<?php
/**
 * Autores (tabla autores). Solo se eliminan si no tienen libros.
 */

class AutorModel extends Model
{
    /** Listado con cantidad de libros, filtrable por texto y nacionalidad. */
    public function listar(string $q = '', string $nacionalidad = ''): array
    {
        $sql = "SELECT a.id_autor, a.nombres, a.apellidos, a.nacionalidad, a.biografia,
                       CONCAT(a.nombres, ' ', a.apellidos) AS nombre_completo,
                       COUNT(la.id_libro) AS libros
                  FROM autores a
                  LEFT JOIN libros_autores la ON la.id_autor = a.id_autor
                 WHERE 1 = 1";
        $params = [];
        if ($q !== '') {
            $sql .= " AND (CONCAT(a.nombres, ' ', a.apellidos) LIKE ? OR a.nacionalidad LIKE ?)";
            array_push($params, $this->like($q), $this->like($q));
        }
        if ($nacionalidad !== '') {
            $sql .= ' AND a.nacionalidad = ?';
            $params[] = $nacionalidad;
        }
        $sql .= ' GROUP BY a.id_autor, a.nombres, a.apellidos, a.nacionalidad, a.biografia ORDER BY a.apellidos, a.nombres';
        return $this->todos($sql, $params);
    }

    /** Para selects y filtros del catálogo (con libros visibles). */
    public function opciones(bool $soloConLibros = false): array
    {
        $sql = "SELECT a.id_autor AS id, CONCAT(a.nombres, ' ', a.apellidos) AS nombre,
                       COUNT(l.id_libro) AS libros
                  FROM autores a
                  LEFT JOIN libros_autores la ON la.id_autor = a.id_autor
                  LEFT JOIN libros l ON l.id_libro = la.id_libro AND l.activo = 1
                 GROUP BY a.id_autor, a.nombres, a.apellidos"
             . ($soloConLibros ? ' HAVING libros > 0' : '')
             . ' ORDER BY nombre';
        return $this->todos($sql);
    }

    public function nacionalidades(): array
    {
        return array_column($this->todos(
            'SELECT DISTINCT nacionalidad FROM autores WHERE nacionalidad IS NOT NULL ORDER BY nacionalidad'
        ), 'nacionalidad');
    }

    public function obtener(int $id): ?array
    {
        return $this->uno(
            'SELECT a.*, (SELECT COUNT(*) FROM libros_autores WHERE id_autor = a.id_autor) AS libros
               FROM autores a WHERE a.id_autor = ?',
            [$id]
        );
    }

    public function existe(string $nombres, string $apellidos, int $excluirId = 0): bool
    {
        return (bool) $this->valor(
            'SELECT COUNT(*) FROM autores WHERE nombres = ? AND apellidos = ? AND id_autor <> ?',
            [$nombres, $apellidos, $excluirId]
        );
    }

    public function crear(array $d): int
    {
        $this->ejecutar(
            'INSERT INTO autores (nombres, apellidos, nacionalidad, biografia) VALUES (?, ?, ?, ?)',
            [$d['nombres'], $d['apellidos'], $d['nacionalidad'] ?: null, $d['biografia'] ?: null]
        );
        return $this->ultimoId();
    }

    public function actualizar(int $id, array $d): void
    {
        $this->ejecutar(
            'UPDATE autores SET nombres = ?, apellidos = ?, nacionalidad = ?, biografia = ? WHERE id_autor = ?',
            [$d['nombres'], $d['apellidos'], $d['nacionalidad'] ?: null, $d['biografia'] ?: null, $id]
        );
    }

    /** Devuelve false si el autor tiene libros (la llave foránea lo impide). */
    public function eliminar(int $id): bool
    {
        if ((int) $this->valor('SELECT COUNT(*) FROM libros_autores WHERE id_autor = ?', [$id]) > 0) {
            return false;
        }
        return $this->ejecutar('DELETE FROM autores WHERE id_autor = ?', [$id]) > 0;
    }
}
