<?php
/**
 * Novedades: libro destacado (vista vw_novedades) y últimas incorporaciones.
 */

class NovedadesController extends Controller
{
    private const ORDENES = [
        'recientes'   => 'Más recientes',
        'precio-asc'  => 'Precio: menor a mayor',
        'precio-desc' => 'Precio: mayor a menor',
        'titulo-az'   => 'Título A–Z',
    ];

    /** GET /novedades */
    public function index(): void
    {
        $libros = $this->modelo('Libro');
        $idCategoria = (int) $this->parametro('categoria');
        $orden = array_key_exists($this->parametro('orden'), self::ORDENES) ? $this->parametro('orden') : 'recientes';

        $filtros = ['solo_activos' => true, 'categorias' => $idCategoria > 0 ? [$idCategoria] : []];
        $paginacion = paginar($libros->contar($filtros), 8);

        $this->vista('novedades/index', [
            'titulo'      => 'Novedades',
            'destacado'   => $libros->destacado(),
            'libros'      => $libros->buscar($filtros, $orden, $paginacion['por_pagina'], $paginacion['offset']),
            'paginacion'  => $paginacion,
            'totalNuevos' => $libros->contar(['solo_activos' => true, 'novedades' => true]),
            'categorias'  => $this->modelo('Categoria')->opciones(true),
            'idCategoria' => $idCategoria,
            'orden'       => $orden,
            'ordenes'     => self::ORDENES,
        ]);
    }
}
