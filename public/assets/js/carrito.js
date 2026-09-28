/**
 * Carrito: cambia cantidades y quita libros con fetch() sin recargar la página.
 * Los totales que se muestran vienen del servidor (CarritoController::estadoCarrito).
 */
(function () {
    'use strict';

    // Refresca subtotales, resumen y el contador del navbar con la respuesta del servidor
    function pintar(datos) {
        if (datos.vacio) {
            window.location.reload(); // muestra el estado "carrito vacío"
            return;
        }
        (datos.lineas || []).forEach((linea) => {
            const fila = document.querySelector(`[data-linea="${linea.id_libro}"] [data-linea-subtotal]`);
            if (fila) fila.textContent = linea.subtotal;
        });
        ['subtotal', 'envio', 'total'].forEach((clave) => {
            const celda = document.querySelector(`[data-resumen="${clave}"]`);
            if (celda) celda.textContent = datos[clave];
        });
        const envio = document.querySelector('[data-resumen="envio"]');
        envio?.classList.toggle('texto-verde', datos.envio === 'Gratis');
        PYA.actualizarContador(datos.carrito);
    }

    // Espera un momento antes de enviar, por si el cliente pulsa varias veces "+"
    const pendientes = new Map();
    function guardarCantidad(idLibro, input) {
        clearTimeout(pendientes.get(idLibro));
        pendientes.set(idLibro, setTimeout(async () => {
            const r = await PYA.post('/carrito/actualizar', { id_libro: idLibro, cantidad: input.value });
            if (!r.ok) return PYA.aviso(r.mensaje, 'danger');
            input.value = r.cantidad;
            pintar(r);
        }, 350));
    }

    document.addEventListener('click', (evento) => {
        const boton = evento.target.closest('[data-carrito-cambiar]');
        if (!boton) return;
        const linea = boton.closest('[data-linea]');
        const input = linea.querySelector('[data-carrito-cantidad]');
        const max = Number(input.max) || 99;
        input.value = Math.min(max, Math.max(1, Number(input.value) + Number(boton.dataset.carritoCambiar)));
        guardarCantidad(linea.dataset.linea, input);
    });

    document.addEventListener('change', (evento) => {
        const input = evento.target.closest('[data-carrito-cantidad]');
        if (!input) return;
        guardarCantidad(input.closest('[data-linea]').dataset.linea, input);
    });

    document.addEventListener('submit', async (evento) => {
        const form = evento.target.closest('[data-carrito-eliminar]');
        if (!form) return;
        evento.preventDefault();
        const r = await PYA.post('/carrito/eliminar', new FormData(form));
        if (!r.ok) return PYA.aviso(r.mensaje, 'danger');
        form.closest('[data-linea]').remove();
        PYA.aviso(r.mensaje);
        pintar(r);
    });
})();
