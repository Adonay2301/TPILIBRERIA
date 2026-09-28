<?php
/**
 * Pie de página (tienda y panel).
 */
?>
<footer class="footer-pya">
    <div class="container-xl px-3 px-md-4">
        <div class="row g-5 pb-4">
            <div class="col-md-6">
                <?php $marcaOscura = false; require RUTA_VISTAS . '/partials/marca.php'; ?>
                <p class="footer-texto mt-3">
                    Un espacio cuidado para lectores apasionados. Selección curada de literatura latinoamericana,
                    títulos de autor y novedades editoriales de todo el mundo.
                </p>
            </div>
            <div class="col-md-6">
                <h4 class="footer-titulo">Contacto</h4>
                <ul class="footer-lista">
                    <li>Calle Arce #418, Centro Histórico</li>
                    <li>San Salvador, El Salvador</li>
                    <li class="pt-1">+503 2222-3344</li>
                    <li>hola@puntoyaparte.sv</li>
                </ul>
            </div>
        </div>
        <div class="footer-legal">
            © <?= date('Y') ?> Punto y Aparte · San Salvador, El Salvador · Todos los derechos reservados
        </div>
    </div>
</footer>
