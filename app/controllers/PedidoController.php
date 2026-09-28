<?php
/**
 * Pedidos.
 * - Cliente: confirmar y crear la compra desde el carrito.
 * - Panel: listado, detalle, cambio de estado y asignación.
 *   El administrador puede todo; el empleado se asigna pedidos pendientes y avanza solo los suyos, sin cancelar.
 */

class PedidoController extends Controller
{
    public const MOTIVOS_CANCELACION = ['Sin existencias', 'Solicitud del cliente', 'Dirección incorrecta', 'Otro'];

    // =================================================================
    // Cliente
    // =================================================================

    /** GET /pedido/confirmar */
    public function confirmar(): void
    {
        Auth::requerirRol(['cliente']);
        $idCliente = Auth::idPerfil();
        $carrito = $this->modelo('Carrito');
        $items = $carrito->items($idCliente);

        if (!$items) {
            flash('info', 'Tu carrito está vacío.');
            $this->redirigir('/carrito');
        }

        $this->vista('pedidos/confirmar', [
            'titulo'        => 'Confirmar pedido',
            'items'         => $items,
            'resumen'       => $carrito->resumen($items),
            'direcciones'   => $this->modelo('Direccion')->delCliente($idCliente),
            'departamentos' => $this->modelo('Ubicacion')->departamentos(),
            'cliente'       => $this->modelo('Cliente')->obtener($idCliente),
        ]);
    }

    /** POST /pedido/crear */
    public function crear(): void
    {
        Auth::requerirRol(['cliente']);
        $this->validarCsrf();
        $idCliente = Auth::idPerfil();

        $telefono = telefonoSv($this->entrada('telefono'));
        $notas = mb_substr($this->entrada('notas'), 0, 255);
        $errores = [];

        // Dirección guardada o nueva
        if ($this->entrada('id_direccion') !== 'nueva') {
            $dir = $this->modelo('Direccion')->obtenerDeCliente((int) $this->entrada('id_direccion'), $idCliente);
            if (!$dir) {
                $errores[] = 'Selecciona una dirección de entrega.';
            } else {
                $entrega = ['direccion' => $dir['direccion'], 'referencia' => $dir['referencia'], 'id_distrito' => (int) $dir['id_distrito']];
            }
        } else {
            $entrega = [
                'direccion'   => mb_substr($this->entrada('direccion'), 0, 255),
                'referencia'  => mb_substr($this->entrada('referencia'), 0, 255),
                'id_distrito' => (int) $this->entrada('id_distrito'),
            ];
            if ($entrega['direccion'] === '') $errores[] = 'Escribe la dirección de entrega.';
            if (!$this->modelo('Ubicacion')->distrito($entrega['id_distrito'])) $errores[] = 'Selecciona departamento, municipio y distrito.';
        }
        if (!telefonoValido($telefono)) {
            $errores[] = 'El teléfono de contacto debe tener el formato 7123-4567.';
        }

        if ($errores) {
            $this->recordarEntrada();
            foreach ($errores as $error) flash('danger', $error);
            $this->redirigir('/pedido/confirmar');
        }

        try {
            $idPedido = $this->modelo('Pedido')->crearDesdeCarrito(
                $idCliente,
                $entrega + ['telefono' => $telefono, 'notas' => $notas]
            );
        } catch (DomainException $e) {
            flash('danger', $e->getMessage());
            $this->redirigir('/carrito');
        }

        // Guarda la dirección nueva en la libreta si el cliente lo pidió
        if ($this->entrada('id_direccion') === 'nueva' && $this->entrada('guardar_direccion') === '1') {
            $this->modelo('Direccion')->crear($idCliente, $entrega + ['alias' => mb_substr($this->entrada('alias'), 0, 40)]);
        }

        flash('success', '¡Gracias por tu compra! Tu pedido ' . codigoPedido($idPedido) . ' fue registrado.');
        $this->redirigir('/seguimiento/' . $idPedido);
    }

    // =================================================================
    // Panel
    // =================================================================

    /** GET /admin/pedidos */
    public function index(): void
    {
        Auth::requerirRol(Auth::ROLES_PANEL);

        $filtros = [
            'q'      => mb_substr($this->parametro('q'), 0, 100),
            'estado' => $this->parametro('estado'),
            'desde'  => $this->parametroFecha('desde', ''),
            'hasta'  => $this->parametroFecha('hasta', ''),
        ];
        $pedidos = $this->modelo('Pedido');
        $paginacion = paginar($pedidos->contar($filtros), POR_PAGINA_ADMIN);

        $this->vista('admin/pedidos/index', [
            'titulo'     => 'Gestión de pedidos',
            'filtros'    => $filtros,
            'pedidos'    => $pedidos->listar($filtros, $paginacion['por_pagina'], $paginacion['offset']),
            'paginacion' => $paginacion,
            'contadores' => $pedidos->contadores(),
            'esAdmin'    => Auth::esAdmin(),
            'miIdEmpleado' => Auth::esRol('empleado') ? Auth::idPerfil() : null,
        ], 'admin');
    }

    /** GET /admin/pedidos/{id} */
    public function ver(int $id): void
    {
        Auth::requerirRol(Auth::ROLES_PANEL);
        $pedidos = $this->modelo('Pedido');
        $pedido = $pedidos->obtener($id);
        if (!$pedido) {
            throw new HttpError(404);
        }

        $this->vista('admin/pedidos/ver', [
            'titulo'     => 'Pedido ' . codigoPedido($id),
            'pedido'     => $pedido,
            'detalles'   => $pedidos->detalles($id),
            'historial'  => $pedidos->historial($id),
            'estados'    => $this->estadosPermitidos($pedido),
            'puedeTomar' => Auth::esRol('empleado') && $pedido['estado'] === 'pendiente' && $pedido['id_empleado'] === null,
            'empleados'  => Auth::esAdmin() ? $this->modelo('Empleado')->activos() : [],
            'motivos'    => self::MOTIVOS_CANCELACION,
            'esAdmin'    => Auth::esAdmin(),
        ], 'admin');
    }

    /** POST /admin/pedidos/{id}/estado */
    public function cambiarEstado(int $id): void
    {
        Auth::requerirRol(Auth::ROLES_PANEL);
        $this->validarCsrf();

        $pedidos = $this->modelo('Pedido');
        $pedido = $pedidos->obtener($id);
        if (!$pedido) {
            throw new HttpError(404);
        }

        $nuevo = $this->entrada('estado');
        if (!in_array($nuevo, $this->estadosPermitidos($pedido), true)) {
            throw new HttpError(403);
        }

        // Comentario del historial: motivo de cancelación + nota interna
        $comentario = trim(implode(' · ', array_filter([
            $nuevo === 'cancelado' ? $this->entrada('motivo') : '',
            mb_substr($this->entrada('comentario'), 0, 200),
        ])));
        if ($nuevo === 'cancelado' && !in_array($this->entrada('motivo'), self::MOTIVOS_CANCELACION, true)) {
            $this->fallar('Selecciona el motivo de la cancelación.', $id);
        }

        // Quién queda a cargo al pasar a preparación
        $idEmpleado = null;
        if ($nuevo === 'en_preparacion') {
            $idEmpleado = Auth::esRol('empleado') ? Auth::idPerfil() : ((int) $this->entrada('id_empleado') ?: null);
        }

        try {
            $pedidos->cambiarEstado($id, $nuevo, $comentario, Auth::id(), $idEmpleado);
        } catch (DomainException $e) {
            $this->fallar($e->getMessage(), $id);
        }

        $this->exito('Estado actualizado a «' . estadoPedido($nuevo)['texto'] . '».', $id);
    }

    /** POST /admin/pedidos/{id}/asignar */
    public function asignar(int $id): void
    {
        Auth::requerirRol(Auth::ROLES_PANEL);
        $this->validarCsrf();

        $pedidos = $this->modelo('Pedido');
        $pedido = $pedidos->obtener($id);
        if (!$pedido) {
            throw new HttpError(404);
        }

        try {
            if (Auth::esRol('empleado')) {
                // "Asignarme": solo pedidos pendientes sin empleado; pasa a preparación a su nombre
                if ($pedido['estado'] !== 'pendiente' || $pedido['id_empleado'] !== null) {
                    $this->fallar('Este pedido ya fue tomado por otra persona.', $id);
                }
                $pedidos->cambiarEstado($id, 'en_preparacion', 'Pedido asignado para preparación', Auth::id(), Auth::idPerfil());
                $this->exito('Pedido ' . codigoPedido($id) . ' asignado a ti.', $id);
            }

            // Administrador: asigna o quita el empleado responsable
            if (in_array($pedido['estado'], ['entregado', 'cancelado'], true)) {
                $this->fallar('No se puede reasignar un pedido cerrado.', $id);
            }
            $idEmpleado = (int) $this->entrada('id_empleado') ?: null;
            $pedidos->asignar($id, $idEmpleado);
        } catch (DomainException $e) {
            $this->fallar($e->getMessage(), $id);
        }

        $this->exito($idEmpleado ? 'Empleado asignado.' : 'Se quitó la asignación.', $id);
    }

    /**
     * Estados a los que el usuario actual puede llevar el pedido.
     * Empleado: nunca cancela; solo avanza pedidos que son suyos (o toma pendientes sin asignar).
     */
    private function estadosPermitidos(array $pedido): array
    {
        $siguientes = PedidoModel::TRANSICIONES[$pedido['estado']];
        if (Auth::esAdmin()) {
            return $siguientes;
        }

        $esSuyo = (int) $pedido['id_empleado'] === Auth::idPerfil();
        $libre = $pedido['estado'] === 'pendiente' && $pedido['id_empleado'] === null;
        if (!$esSuyo && !$libre) {
            return [];
        }
        return array_values(array_diff($siguientes, ['cancelado']));
    }

    private function exito(string $mensaje, int $id): never
    {
        if (esPeticionJson()) {
            $this->json(['ok' => true, 'mensaje' => $mensaje]);
        }
        flash('success', $mensaje);
        $this->volver('/admin/pedidos/' . $id);
    }

    private function fallar(string $mensaje, int $id): never
    {
        if (esPeticionJson()) {
            $this->json(['ok' => false, 'mensaje' => $mensaje], 422);
        }
        flash('danger', $mensaje);
        $this->volver('/admin/pedidos/' . $id);
    }
}
