<?php
/**
 * Preparación de pedidos (compartida).
 * Administrador: ve todos los pedidos en preparación agrupados por empleado.
 * Empleado: ve solo los suyos. Ambos marcan "Pedido preparado" (pasa a 'enviado').
 */

class PreparacionController extends Controller
{
    /** GET /admin/preparacion */
    public function index(): void
    {
        Auth::requerirRol(Auth::ROLES_PANEL);
        $pedidos = $this->modelo('Pedido');

        $lista = $pedidos->enPreparacion(Auth::esAdmin() ? null : Auth::idPerfil());

        // Agrupa por empleado (el administrador ve un bloque por persona)
        $grupos = [];
        foreach ($lista as $p) {
            $grupos[$p['empleado'] ?? 'Sin asignar'][] = $p;
        }

        $this->vista('admin/preparacion/index', [
            'titulo'  => 'Preparación de pedidos',
            'grupos'  => $grupos,
            'libros'  => $pedidos->detallesDe(array_column($lista, 'id_pedido')),
            'esAdmin' => Auth::esAdmin(),
        ], 'admin');
    }

    /** POST /admin/preparacion/{id}/listo */
    public function listo(int $id): void
    {
        Auth::requerirRol(Auth::ROLES_PANEL);
        $this->validarCsrf();

        $pedidos = $this->modelo('Pedido');
        $pedido = $pedidos->obtener($id);
        if (!$pedido || $pedido['estado'] !== 'en_preparacion') {
            flash('warning', 'El pedido ya no está en preparación.');
            $this->redirigir('/admin/preparacion');
        }
        // Un empleado solo puede marcar sus propios pedidos
        if (!Auth::esAdmin() && (int) $pedido['id_empleado'] !== Auth::idPerfil()) {
            throw new HttpError(403);
        }

        try {
            $pedidos->cambiarEstado($id, 'enviado', 'Pedido preparado y entregado al repartidor', Auth::id());
            flash('success', 'Pedido ' . codigoPedido($id) . ' marcado como preparado y enviado.');
        } catch (DomainException $e) {
            flash('danger', $e->getMessage());
        }
        $this->redirigir('/admin/preparacion');
    }
}
