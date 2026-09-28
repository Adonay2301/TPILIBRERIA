<?php
/**
 * Libros del catálogo (libros, libros_autores) con su inventario y ventas.
 */

class LibroModel extends Model
{
    /** Autores de un libro en una sola cadena, en el orden de la portada. */
    private const SUB_AUTORES = "(SELECT GROUP_CONCAT(CONCAT(a.nombres, ' ', a.apellidos) ORDER BY la.orden SEPARATOR ', ')
                                    FROM libros_autores la JOIN autores a ON a.id_autor = la.id_autor
                                   WHERE la.id_libro = l.id_libro)";

    /** Unidades vendidas por libro (pedidos no cancelados). */
    private const SUB_VENDIDOS = "(SELECT pd.id_libro, SUM(pd.cantidad) AS vendidos
                                     FROM pedido_detalles pd
                                     JOIN pedidos p ON p.id_pedido = pd.id_pedido AND p.estado <> 'cancelado'
                                    GROUP BY pd.id_libro)";

    private function columnas(): string
    {
        return "l.id_libro, l.isbn, l.titulo, l.precio, l.stock_actual, l.stock_minimo, l.portada,
                l.es_novedad, l.activo, l.fecha_ingreso, l.anio_publicacion, l.id_categoria, l.id_editorial,
                c.nombre AS categoria, e.nombre AS editorial, " . self::SUB_AUTORES . " AS autores,
                COALESCE(v.vendidos, 0) AS vendidos,
                (l.es_novedad = 1 OR l.fecha_ingreso >= NOW() - INTERVAL 30 DAY) AS es_nuevo";
    }

    private function desde(): string
    {
        return ' FROM libros l
                 JOIN categorias c  ON c.id_categoria = l.id_categoria
                 JOIN editoriales e ON e.id_editorial = l.id_editorial
                 LEFT JOIN ' . self::SUB_VENDIDOS . ' v ON v.id_libro = l.id_libro';
    }

    /**
     * Condiciones de búsqueda comunes al catálogo público y al inventario.
     * $f: q, categorias[], autores[], editoriales[], disponibilidad, precio_min, precio_max,
     *     anio_desde, anio_hasta, solo_activos
     */
    private function filtros(array $f, array &$params): string
    {
        $sql = ' WHERE 1 = 1';

        if (!empty($f['solo_activos'])) {
            $sql .= ' AND l.activo = 1';
        }
        if (!empty($f['q'])) {
            $sql .= " AND (l.titulo LIKE ? OR l.isbn LIKE ? OR EXISTS (
                          SELECT 1 FROM libros_autores la JOIN autores a ON a.id_autor = la.id_autor
                           WHERE la.id_libro = l.id_libro AND CONCAT(a.nombres, ' ', a.apellidos) LIKE ?))";
            array_push($params, $this->like($f['q']), $this->like($f['q']), $this->like($f['q']));
        }
        foreach (['categorias' => 'l.id_categoria', 'editoriales' => 'l.id_editorial'] as $clave => $columna) {
            if (!empty($f[$clave])) {
                $sql .= " AND $columna IN (" . $this->marcadores($f[$clave]) . ')';
                array_push($params, ...$f[$clave]);
            }
        }
        if (!empty($f['autores'])) {
            $sql .= ' AND EXISTS (SELECT 1 FROM libros_autores la WHERE la.id_libro = l.id_libro
                                    AND la.id_autor IN (' . $this->marcadores($f['autores']) . '))';
            array_push($params, ...$f['autores']);
        }
        $sql .= match ($f['disponibilidad'] ?? '') {
            'disponibles' => ' AND l.stock_actual > 0',
            'agotados'    => ' AND l.stock_actual = 0',
            'bajas'       => ' AND l.stock_actual > 0 AND l.stock_actual <= l.stock_minimo',
            default       => '',
        };
        foreach (['precio_min' => 'l.precio >= ?', 'precio_max' => 'l.precio <= ?',
                  'anio_desde' => 'l.anio_publicacion >= ?', 'anio_hasta' => 'l.anio_publicacion <= ?'] as $clave => $cond) {
            if (isset($f[$clave]) && $f[$clave] !== '' && is_numeric($f[$clave])) {
                $sql .= " AND $cond";
                $params[] = $f[$clave];
            }
        }
        if (!empty($f['novedades'])) {
            $sql .= ' AND (l.es_novedad = 1 OR l.fecha_ingreso >= NOW() - INTERVAL 30 DAY)';
        }
        return $sql;
    }

    private function orden(string $orden): string
    {
        return match ($orden) {
            'recientes'    => ' ORDER BY l.fecha_ingreso DESC, l.id_libro DESC',
            'precio-asc'   => ' ORDER BY l.precio ASC, l.titulo',
            'precio-desc'  => ' ORDER BY l.precio DESC, l.titulo',
            'mas-vendidos' => ' ORDER BY vendidos DESC, l.titulo',
            'titulo-az'    => ' ORDER BY l.titulo ASC',
            'stock'        => ' ORDER BY l.stock_actual ASC, l.titulo',
            default        => ' ORDER BY (l.stock_actual > 0) DESC, l.es_novedad DESC, vendidos DESC, l.titulo',
        };
    }

    public function contar(array $filtros): int
    {
        $params = [];
        return (int) $this->valor('SELECT COUNT(*) FROM libros l' . $this->filtros($filtros, $params), $params);
    }

    public function buscar(array $filtros, string $orden, int $limite, int $offset): array
    {
        $params = [];
        $sql = 'SELECT ' . $this->columnas() . $this->desde() . $this->filtros($filtros, $params)
             . $this->orden($orden) . ' LIMIT ' . (int) $limite . ' OFFSET ' . (int) $offset;
        return $this->todos($sql, $params);
    }

    /** Detalle completo de un libro. */
    public function obtener(int $idLibro, bool $soloActivo = true): ?array
    {
        $sql = 'SELECT ' . $this->columnas() . ', l.sinopsis, l.numero_paginas, l.idioma' . $this->desde()
             . ' WHERE l.id_libro = ?' . ($soloActivo ? ' AND l.activo = 1' : '');
        return $this->uno($sql, [$idLibro]);
    }

    /** Ids de los autores del libro (para el formulario). */
    public function idsAutores(int $idLibro): array
    {
        return array_map('intval', array_column(
            $this->todos('SELECT id_autor FROM libros_autores WHERE id_libro = ? ORDER BY orden', [$idLibro]),
            'id_autor'
        ));
    }

    /** Otros libros de la misma categoría. */
    public function relacionados(int $idLibro, int $idCategoria, int $limite = 4): array
    {
        return $this->todos(
            'SELECT ' . $this->columnas() . $this->desde() .
            ' WHERE l.activo = 1 AND l.id_categoria = ? AND l.id_libro <> ?
              ORDER BY (l.stock_actual > 0) DESC, vendidos DESC LIMIT ' . (int) $limite,
            [$idCategoria, $idLibro]
        );
    }

    /** Sugerencias para el carrito vacío: los más vendidos con stock. */
    public function sugerencias(int $limite = 4): array
    {
        return $this->todos(
            'SELECT ' . $this->columnas() . $this->desde() .
            ' WHERE l.activo = 1 AND l.stock_actual > 0 ORDER BY vendidos DESC, l.fecha_ingreso DESC LIMIT ' . (int) $limite
        );
    }

    /** Libro destacado de Novedades: la novedad marcada más reciente (vista vw_novedades). */
    public function destacado(): ?array
    {
        return $this->uno(
            'SELECT ' . $this->columnas() . ', l.sinopsis, l.numero_paginas' . $this->desde() .
            ' JOIN vw_novedades n ON n.id_libro = l.id_libro
              ORDER BY n.es_novedad DESC, n.fecha_ingreso DESC LIMIT 1'
        );
    }

    /** Libros más vendidos en un período (panel y reportes). */
    public function masVendidos(string $desde, string $hasta, int $limite = 5): array
    {
        return $this->todos(
            "SELECT l.id_libro, l.titulo, l.portada, " . self::SUB_AUTORES . " AS autores,
                    SUM(pd.cantidad) AS unidades, SUM(pd.subtotal) AS ingresos
               FROM pedido_detalles pd
               JOIN pedidos p ON p.id_pedido = pd.id_pedido
               JOIN libros l  ON l.id_libro = pd.id_libro
              WHERE p.estado <> 'cancelado' AND p.fecha_pedido >= ? AND p.fecha_pedido < ? + INTERVAL 1 DAY
              GROUP BY l.id_libro, l.titulo, l.portada
              ORDER BY unidades DESC, ingresos DESC
              LIMIT " . (int) $limite,
            [$desde, $hasta]
        );
    }

    /** Libros en el mínimo o por debajo (vista vw_stock_bajo). */
    public function stockBajo(int $limite = 10): array
    {
        return $this->todos(
            'SELECT s.*, l.portada, ' . self::SUB_AUTORES . ' AS autores
               FROM vw_stock_bajo s JOIN libros l ON l.id_libro = s.id_libro
              ORDER BY s.stock_actual ASC, s.faltante DESC LIMIT ' . (int) $limite
        );
    }

    /** Indicadores del inventario. */
    public function estadisticasInventario(): array
    {
        return $this->uno(
            'SELECT COUNT(*) AS titulos,
                    COALESCE(SUM(stock_actual), 0) AS unidades,
                    COALESCE(SUM(stock_actual > 0 AND stock_actual <= stock_minimo), 0) AS bajas,
                    COALESCE(SUM(stock_actual = 0), 0) AS agotados
               FROM libros WHERE activo = 1'
        );
    }

    public function isbnExiste(string $isbn, int $excluirId = 0): bool
    {
        return (bool) $this->valor('SELECT COUNT(*) FROM libros WHERE isbn = ? AND id_libro <> ?', [$isbn, $excluirId]);
    }

    /**
     * Crea un libro con sus autores y su inventario inicial.
     * El stock no se escribe directo: entra como movimiento 'inventario_inicial' (lo aplica el trigger).
     */
    public function crear(array $d, array $autores, int $stockInicial, int $idUsuario): int
    {
        return $this->transaccion(function () use ($d, $autores, $stockInicial, $idUsuario) {
            $this->ejecutar(
                'INSERT INTO libros (isbn, titulo, sinopsis, id_categoria, id_editorial, anio_publicacion, numero_paginas,
                                     idioma, precio, stock_minimo, portada, es_novedad, activo)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$d['isbn'], $d['titulo'], $d['sinopsis'], $d['id_categoria'], $d['id_editorial'], $d['anio_publicacion'],
                 $d['numero_paginas'], $d['idioma'], $d['precio'], $d['stock_minimo'], $d['portada'], $d['es_novedad'], $d['activo']]
            );
            $idLibro = $this->ultimoId();
            $this->guardarAutores($idLibro, $autores);

            if ($stockInicial > 0) {
                $this->ejecutar(
                    "INSERT INTO movimientos_inventario (id_libro, tipo, motivo, cantidad, id_usuario, observacion)
                     VALUES (?, 'entrada', 'inventario_inicial', ?, ?, 'Carga inicial de inventario')",
                    [$idLibro, $stockInicial, $idUsuario]
                );
            }
            return $idLibro;
        });
    }

    public function actualizar(int $idLibro, array $d, array $autores): void
    {
        $this->transaccion(function () use ($idLibro, $d, $autores) {
            $this->ejecutar(
                'UPDATE libros SET isbn = ?, titulo = ?, sinopsis = ?, id_categoria = ?, id_editorial = ?, anio_publicacion = ?,
                                   numero_paginas = ?, idioma = ?, precio = ?, stock_minimo = ?, portada = COALESCE(?, portada),
                                   es_novedad = ?, activo = ?
                 WHERE id_libro = ?',
                [$d['isbn'], $d['titulo'], $d['sinopsis'], $d['id_categoria'], $d['id_editorial'], $d['anio_publicacion'],
                 $d['numero_paginas'], $d['idioma'], $d['precio'], $d['stock_minimo'], $d['portada'], $d['es_novedad'],
                 $d['activo'], $idLibro]
            );
            $this->guardarAutores($idLibro, $autores);
        });
    }

    /** Reemplaza la relación libro-autor respetando el orden elegido. */
    private function guardarAutores(int $idLibro, array $autores): void
    {
        $this->ejecutar('DELETE FROM libros_autores WHERE id_libro = ?', [$idLibro]);
        foreach (array_values($autores) as $i => $idAutor) {
            $this->ejecutar('INSERT INTO libros_autores (id_libro, id_autor, orden) VALUES (?, ?, ?)', [$idLibro, $idAutor, $i + 1]);
        }
    }

    /** "Eliminar" del prototipo = ocultar del catálogo (el historial impide borrar). */
    public function cambiarActivo(int $idLibro, bool $activo): void
    {
        $this->ejecutar('UPDATE libros SET activo = ? WHERE id_libro = ?', [$activo ? 1 : 0, $idLibro]);
    }
}
