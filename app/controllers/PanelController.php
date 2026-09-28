<?php
/**
 * Panel de inicio. El administrador ve indicadores del negocio;
 * el empleado ve sus pedidos, los disponibles para tomar y las alertas de stock.
 */

class PanelController extends Controller
{
    /** GET /admin */
    public function index(): void
    {
        Auth::requerirRol(Auth::ROLES_PANEL);
        Auth::esAdmin() ? $this->panelAdministrador() : $this->panelEmpleado();
    }

    private function panelAdministrador(): void
    {
        $hoy = date('Y-m-d');
        $desde = $this->parametroFecha('desde', date('Y-m-01'));
        $hasta = $this->parametroFecha('hasta', $hoy);
        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        // Período anterior de la misma duración, para comparar (▲ / ▼)
        $dias = (int) (new DateTime($desde))->diff(new DateTime($hasta))->days + 1;
        $antHasta = date('Y-m-d', strtotime("$desde -1 day"));
        $antDesde = date('Y-m-d', strtotime("$antHasta -" . ($dias - 1) . ' days'));

        $pedidos = $this->modelo('Pedido');
        $libros = $this->modelo('Libro');

        $this->vista('admin/panel/administrador', [
            'titulo'       => 'Panel administrativo',
            'desde'        => $desde,
            'hasta'        => $hasta,
            'actual'       => $pedidos->resumenVentas($desde, $hasta),
            'anterior'     => $pedidos->resumenVentas($antDesde, $antHasta),
            'nuevos'       => $pedidos->clientesNuevos($desde, $hasta),
            'nuevosAnt'    => $pedidos->clientesNuevos($antDesde, $antHasta),
            'serie'        => $pedidos->ventasPorDia(date('Y-m-d', strtotime('-29 days')), $hoy),
            'recientes'    => $pedidos->recientes(5),
            'masVendidos'  => $libros->masVendidos($desde, $hasta, 5),
            'porEmpleado'  => $pedidos->seguimientoPorEmpleado(),
            'stockBajo'    => $libros->stockBajo(8),
        ], 'admin');
    }

    private function panelEmpleado(): void
    {
        $pedidos = $this->modelo('Pedido');
        $mios = $pedidos->delEmpleado(Auth::idPerfil());

        $conteo = ['en_preparacion' => 0, 'enviado' => 0, 'entregado' => 0];
        foreach ($mios as $p) {
            if (isset($conteo[$p['estado']])) {
                $conteo[$p['estado']]++;
            }
        }

        $this->vista('admin/panel/empleado', [
            'titulo'      => 'Panel',
            'conteo'      => $conteo,
            'disponibles' => $pedidos->disponibles(),
            'enCurso'     => array_filter($mios, fn ($p) => in_array($p['estado'], ['en_preparacion', 'enviado'], true)),
            'stockBajo'   => $this->modelo('Libro')->stockBajo(6),
        ], 'admin');
    }
}
