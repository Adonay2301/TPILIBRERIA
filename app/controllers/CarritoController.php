<?php
/**
 * Carrito del cliente. Tres estados: con productos, vacío sin pedidos y vacío con un pedido reciente.
 * agregar/actualizar/eliminar responden JSON cuando se llaman con fetch().
 */

class CarritoController extends Controller
{
    /** GET /carrito */
    public function index(): void
    {
        Auth::requerirRol(['cliente']);
        $idCliente = Auth::idPerfil();
        $carrito = $this->modelo('Carrito');
        $items = $carrito->items($idCliente);

        $datos = ['titulo' => 'Mi carrito', 'items' => $items, 'resumen' => $carrito->resumen($items)];

        if (!$items) {
            $pedidos = $this->modelo('Pedido');
            $ultimo = $pedidos->ultimoDelCliente($idCliente);
            $datos['ultimoPedido'] = $ultimo;
            $datos['librosUltimo'] = $ultimo ? ($pedidos->detallesDe([$ultimo['id_pedido']])[$ultimo['id_pedido']] ?? []) : [];
            $datos['sugerencias'] = $this->modelo('Libro')->sugerencias();
        }

        $datos['scripts'] = ['js/carrito.js'];
        $this->vista('carrito/index', $datos);
    }

    /** POST /carrito/agregar */
    public function agregar(): void
    {
        Auth::requerirRol(['cliente']);
        $this->validarCsrf();

        $idLibro = (int) $this->entrada('id_libro');
        $cantidad = max(1, min(99, (int) $this->entrada('cantidad', '1')));
        $carrito = $this->modelo('Carrito');

        try {
            $carrito->agregar(Auth::idPerfil(), $idLibro, $cantidad);
        } catch (DomainException $e) {
            $this->responder(false, $e->getMessage(), 422);
        }

        $this->responder(true, 'Libro agregado al carrito.', 200, [
            'carrito' => $carrito->contarArticulos(Auth::idPerfil()),
        ]);
    }

    /** POST /carrito/actualizar */
    public function actualizar(): void
    {
        Auth::requerirRol(['cliente']);
        $this->validarCsrf();

        $idCliente = Auth::idPerfil();
        $carrito = $this->modelo('Carrito');
        $cantidad = $carrito->actualizar($idCliente, (int) $this->entrada('id_libro'), (int) $this->entrada('cantidad', '1'));

        $this->responder(true, 'Cantidad actualizada.', 200, $this->estadoCarrito($idCliente) + ['cantidad' => $cantidad]);
    }

    /** POST /carrito/eliminar */
    public function eliminar(): void
    {
        Auth::requerirRol(['cliente']);
        $this->validarCsrf();

        $idCliente = Auth::idPerfil();
        $this->modelo('Carrito')->eliminar($idCliente, (int) $this->entrada('id_libro'));

        $this->responder(true, 'Libro quitado del carrito.', 200, $this->estadoCarrito($idCliente));
    }

    /** Totales actualizados para refrescar la página sin recargar. */
    private function estadoCarrito(int $idCliente): array
    {
        $carrito = $this->modelo('Carrito');
        $items = $carrito->items($idCliente);
        $resumen = $carrito->resumen($items);

        return [
            'carrito'  => (int) $resumen['articulos'],
            'vacio'    => !$items,
            'lineas'   => array_map(fn ($i) => ['id_libro' => (int) $i['id_libro'], 'subtotal' => moneda($i['subtotal'])], $items),
            'subtotal' => moneda($resumen['subtotal']),
            'envio'    => $resumen['envio'] > 0 ? moneda($resumen['envio']) : 'Gratis',
            'total'    => moneda($resumen['total']),
        ];
    }

    /** JSON para fetch(); mensaje + redirección para formularios normales. */
    private function responder(bool $ok, string $mensaje, int $codigo, array $extra = []): never
    {
        if (esPeticionJson()) {
            $this->json(['ok' => $ok, 'mensaje' => $mensaje] + $extra, $codigo);
        }
        flash($ok ? 'success' : 'danger', $mensaje);
        $this->volver('/carrito');
    }
}
