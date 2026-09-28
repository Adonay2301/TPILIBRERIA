<?php
/**
 * Gráfico de líneas de ventas por día, en SVG puro (sin librerías).
 * Variables: $serie (lista de ['etiqueta' => 'dd/mm', 'valor' => float])
 */
$ancho = 700;
$alto = 200;
$izq = 58;   // espacio para las etiquetas del eje Y
$abajo = 24; // espacio para las etiquetas del eje X
$max = max(1, max(array_column($serie, 'valor')) * 1.1);
$n = count($serie);

$x = fn (int $i) => $izq + ($n > 1 ? $i / ($n - 1) : 0) * ($ancho - $izq - 10);
$y = fn (float $v) => ($alto - $abajo) - ($v / $max) * ($alto - $abajo - 10);

$puntos = [];
foreach ($serie as $i => $p) {
    $puntos[] = round($x($i), 1) . ',' . round($y($p['valor']), 1);
}
$area = 'M' . $x(0) . ',' . ($alto - $abajo) . ' L' . implode(' L', $puntos) . ' L' . $x($n - 1) . ',' . ($alto - $abajo) . ' Z';
?>
<svg viewBox="0 0 <?= $ancho ?> <?= $alto ?>" class="w-100 grafico-ventas" role="img" aria-label="Ventas por día">
    <defs>
        <linearGradient id="gradVentas" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#1a3a4a" stop-opacity=".18"/>
            <stop offset="100%" stop-color="#1a3a4a" stop-opacity="0"/>
        </linearGradient>
    </defs>

    <?php for ($i = 0; $i <= 4; $i++): $valor = $max / 4 * $i; ?>
        <line x1="<?= $izq ?>" x2="<?= $ancho - 10 ?>" y1="<?= $y($valor) ?>" y2="<?= $y($valor) ?>" stroke="#ede6d6" stroke-dasharray="4 3"/>
        <text x="<?= $izq - 6 ?>" y="<?= $y($valor) + 4 ?>" text-anchor="end" font-size="10" fill="#8a7f72"><?= e(moneda($valor)) ?></text>
    <?php endfor; ?>

    <?php foreach ($serie as $i => $p): ?>
        <?php if ($i === 0 || $i === $n - 1 || $i % 5 === 4): ?>
            <text x="<?= $x($i) ?>" y="<?= $alto - 6 ?>" text-anchor="middle" font-size="10" fill="#8a7f72"><?= e($p['etiqueta']) ?></text>
        <?php endif; ?>
    <?php endforeach; ?>

    <path d="<?= $area ?>" fill="url(#gradVentas)"/>
    <polyline points="<?= implode(' ', $puntos) ?>" fill="none" stroke="#1a3a4a" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>
    <?php foreach ($serie as $i => $p): ?>
        <circle cx="<?= $x($i) ?>" cy="<?= $y($p['valor']) ?>" r="<?= $i === $n - 1 ? 4 : 2.5 ?>" fill="#fff" stroke="#1a3a4a" stroke-width="1.5">
            <title><?= e($p['etiqueta'] . ': ' . moneda($p['valor'])) ?></title>
        </circle>
    <?php endforeach; ?>
</svg>
