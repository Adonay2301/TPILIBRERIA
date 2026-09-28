<?php
/**
 * Catálogo público con filtros, orden, vista de cuadrícula/lista y paginación.
 */

class CatalogoController extends Controller
{
    public const ORDENES = [
        'recomendados' => 'Recomendados',
        'recientes'    => 'Más recientes',
        'precio-asc'   => 'Precio: menor a mayor',
        'precio-desc'  => 'Precio: mayor a menor',
        'mas-vendidos' => 'Más vendidos',
        'titulo-az'    => 'Título A–Z',
    ];

    /** GET /catalogo */
    public function index(): void
    {
        $filtros = [
            'solo_activos'   => true,
            'q'              => mb_substr($this->parametro('q'), 0, 100),
            'categorias'     => $this->parametroEnteros('categoria'),
            'autores'        => $this->parametroEnteros('autor'),
            'editoriales'    => $this->parametroEnteros('editorial'),
            'disponibilidad' => in_array($this->parametro('disponibilidad'), ['disponibles', 'agotados'], true) ? $this->parametro('disponibilidad') : '',
            'precio_min'     => $this->parametro('precio_min'),
            'precio_max'     => $this->parametro('precio_max'),
            'anio_desde'     => $this->parametro('anio_desde'),
            'anio_hasta'     => $this->parametro('anio_hasta'),
        ];
        $orden = array_key_exists($this->parametro('orden'), self::ORDENES) ? $this->parametro('orden') : 'recomendados';
        $vistaLista = $this->parametro('vista') === 'lista';

        $libros = $this->modelo('Libro');
        $paginacion = paginar($libros->contar($filtros), POR_PAGINA);

        $this->vista('catalogo/index', [
            'titulo'      => 'Catálogo',
            'filtros'     => $filtros,
            'orden'       => $orden,
            'ordenes'     => self::ORDENES,
            'vistaLista'  => $vistaLista,
            'libros'      => $libros->buscar($filtros, $orden, $paginacion['por_pagina'], $paginacion['offset']),
            'paginacion'  => $paginacion,
            'totalCatalogo' => $libros->contar(['solo_activos' => true]),
            'categorias'  => $this->modelo('Categoria')->opciones(true),
            'autores'     => $this->modelo('Autor')->opciones(true),
            'editoriales' => $this->modelo('Editorial')->opciones(true),
        ]);
    }
}
