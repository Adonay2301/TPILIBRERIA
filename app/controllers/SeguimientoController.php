<?php
/**
 * Seguimiento de pedidos del cliente (solo ve los suyos).
 */

class SeguimientoController extends Controller
{
    /** Pasos visibles de la línea de tiempo (estado del ENUM => texto). */
    private const PASOS = [
        'pendiente'      => 'Pedido recibido',
        'en_preparacion' => 'En preparación',
        'enviado'        => 'En camino',
        'entregado'      => 'Entregado',
    ];

    /** GET /mis-pedidos */
    public function index(): void
    {
        Auth::requerirRol(['cliente']);
        $pedidos = $this->modelo('Pedido');
        $lista = $pedidos->delCliente(Auth::idPerfil());

        $this->vista('seguimiento/index', [
            'titulo'  => 'Mis pedidos',
            'pedidos' => $lista,
            'libros'  => $pedidos->detallesDe(array_column($lista, 'id_pedido')),
        ]);
    }

    /** GET /seguimiento/{id} */
    public function ver(int $id): void
    {
        Auth::requerirRol(['cliente']);
        $pedidos = $this->modelo('Pedido');

        // Un cliente solo puede abrir sus propios pedidos
        $pedido = $pedidos->obtener($id, Auth::idPerfil());
        if (!$pedido) {
            throw new HttpError(404);
        }

        $historial = $pedidos->historial($id);
        $fechas = [];
        foreach ($historial as $h) {
            $fechas[$h['estado_nuevo']] = $h['fecha_cambio'];
        }

        // Cada paso queda "hecho" si el pedido ya pasó por ese estado
        $orden = array_keys(self::PASOS);
        $posicionActual = array_search($pedido['estado'], $orden, true);
        $pasos = [];
        foreach (self::PASOS as $estado => $texto) {
            $pasos[] = [
                'texto' => $texto,
                'fecha' => $fechas[$estado] ?? null,
                'hecho' => $posicionActual !== false && array_search($estado, $orden, true) <= $posicionActual,
            ];
        }

        $cancelacion = null;
        if ($pedido['estado'] === 'cancelado') {
            $ultimo = end($historial);
            $cancelacion = ['fecha' => $ultimo['fecha_cambio'] ?? null, 'motivo' => $ultimo['comentario'] ?? null];
        }

        $this->vista('seguimiento/ver', [
            'titulo'      => 'Seguimiento ' . codigoPedido($id),
            'pedido'      => $pedido,
            'detalles'    => $pedidos->detalles($id),
            'pasos'       => $pasos,
            'cancelacion' => $cancelacion,
        ]);
    }
}
