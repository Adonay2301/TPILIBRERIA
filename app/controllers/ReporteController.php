<?php
/**
 * Reportes de ventas y libros más vendidos (solo administrador), con exportación a CSV.
 */

class ReporteController extends Controller
{
    /** GET /admin/reportes[?exportar=ventas|libros] */
    public function index(): void
    {
        Auth::requerirRol(['administrador']);

        $desde = $this->parametroFecha('desde', date('Y-m-01'));
        $hasta = $this->parametroFecha('hasta', date('Y-m-d'));
        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        $pedidos = $this->modelo('Pedido');
        $serie = $pedidos->ventasPorDia($desde, $hasta);
        $libros = $this->modelo('Libro')->masVendidos($desde, $hasta, 10);

        // Exportación: el CSV sale de los mismos datos que se muestran en pantalla
        if ($this->parametro('exportar') === 'ventas') {
            $this->descargarCsv("ventas_{$desde}_{$hasta}.csv", ['Fecha', 'Ventas (USD)'],
                array_map(fn ($d) => [date('d/m/Y', strtotime($d['dia'])), number_format($d['valor'], 2, '.', '')], $serie));
        }
        if ($this->parametro('exportar') === 'libros') {
            $this->descargarCsv("mas_vendidos_{$desde}_{$hasta}.csv", ['#', 'Título', 'Autores', 'Unidades', 'Ingresos (USD)'],
                array_map(fn ($l, $i) => [$i + 1, $l['titulo'], $l['autores'], $l['unidades'], $l['ingresos']], $libros, array_keys($libros)));
        }

        $this->vista('admin/reportes/index', [
            'titulo'  => 'Reportes',
            'desde'   => $desde,
            'hasta'   => $hasta,
            'resumen' => $pedidos->resumenVentas($desde, $hasta),
            'serie'   => $serie,
            'libros'  => $libros,
        ], 'admin');
    }
}
