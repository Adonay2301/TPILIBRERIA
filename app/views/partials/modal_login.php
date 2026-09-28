<?php
/**
 * Modal "Inicia sesión para comprar" (visitantes que pulsan Agregar al carrito).
 */
?>
<div class="modal fade" id="modalLogin" tabindex="-1" aria-labelledby="modalLoginTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
        <div class="modal-content overflow-hidden">
            <div style="height:4px;background:linear-gradient(90deg,var(--color-teal) 0%,var(--color-orange) 100%)"></div>
            <div class="modal-body text-center px-4 py-5 position-relative">
                <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                <div class="mx-auto mb-4 rounded-circle d-flex align-items-center justify-content-center"
                     style="width:64px;height:64px;background:rgba(212,98,26,.1);color:var(--color-orange);font-size:1.8rem">
                    <i class="bi bi-lock"></i>
                </div>
                <h2 class="titulo-pagina mb-2" id="modalLoginTitulo" style="font-size:1.5rem">Inicia sesión para comprar</h2>
                <p class="texto-suave small mb-4">Necesitas una cuenta para agregar libros a tu carrito y realizar pedidos.</p>
                <a href="<?= url('/login') ?>" class="btn btn-pya w-100 py-2 mb-2">Iniciar sesión</a>
                <a href="<?= url('/registro') ?>" class="btn btn-pya-contorno w-100 py-2">Crear cuenta</a>
                <button type="button" class="btn btn-link btn-sm texto-suave text-decoration-none mt-3" data-bs-dismiss="modal">Seguir explorando</button>
            </div>
        </div>
    </div>
</div>
