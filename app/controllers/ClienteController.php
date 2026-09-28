<?php
/**
 * Gestión de clientes (solo administrador).
 */

class ClienteController extends Controller
{
    public const ACTIVIDADES = [
        'activos'   => 'Con pedidos activos',
        'mes'       => 'Compró este mes',
        'sin'       => 'Sin pedidos',
        'inactivos' => 'Inactivo más de 90 días',
    ];

    /** GET /admin/clientes */
    public function index(): void
    {
        Auth::requerirRol(['administrador']);

        $filtros = [
            'q'            => mb_substr($this->parametro('q'), 0, 100),
            'departamento' => (int) $this->parametro('departamento'),
            'actividad'    => array_key_exists($this->parametro('actividad'), self::ACTIVIDADES) ? $this->parametro('actividad') : '',
        ];
        $clientes = $this->modelo('Cliente');
        $paginacion = paginar($clientes->contarListado($filtros), POR_PAGINA_ADMIN);

        $this->vista('admin/clientes/index', [
            'titulo'        => 'Gestión de clientes',
            'clientes'      => $clientes->listar($filtros, $paginacion['por_pagina'], $paginacion['offset']),
            'filtros'       => $filtros,
            'paginacion'    => $paginacion,
            'stats'         => $clientes->estadisticas(),
            'top'           => $clientes->topCompradores(),
            'departamentos' => $this->modelo('Ubicacion')->departamentos(),
            'actividades'   => self::ACTIVIDADES,
        ], 'admin');
    }

    /** GET /admin/clientes/{id} */
    public function ver(int $id): void
    {
        Auth::requerirRol(['administrador']);
        $clientes = $this->modelo('Cliente');
        $cliente = $clientes->obtener($id) ?? throw new HttpError(404);

        $this->vista('admin/clientes/ver', [
            'titulo'      => $cliente['nombres'] . ' ' . $cliente['apellidos'],
            'cliente'     => $cliente,
            'resumen'     => $clientes->resumenCompras($id),
            'direcciones' => $this->modelo('Direccion')->delCliente($id),
            'pedidos'     => $this->modelo('Pedido')->delCliente($id),
            'librosTop'   => $clientes->librosMasComprados($id),
        ], 'admin');
    }

    /** POST /admin/clientes/{id}/estado — desactiva o reactiva la cuenta */
    public function cambiarEstado(int $id): void
    {
        Auth::requerirRol(['administrador']);
        $this->validarCsrf();

        $cliente = $this->modelo('Cliente')->obtener($id) ?? throw new HttpError(404);
        $activar = $this->entrada('activo') === '1';
        $this->modelo('Usuario')->cambiarActivo((int) $cliente['id_usuario'], $activar);

        flash('success', $activar ? 'La cuenta fue reactivada.' : 'La cuenta fue desactivada. El cliente no podrá iniciar sesión.');
        $this->redirigir('/admin/clientes/' . $id);
    }
}
