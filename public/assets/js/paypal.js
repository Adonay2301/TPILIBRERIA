/**
 * Pago con PayPal en la confirmación del pedido.
 *
 * 1. crearOrden: envía el formulario a /pago/paypal/orden; el servidor valida
 *    y crea la orden por el total del carrito. Aquí solo se recibe el id.
 * 2. El cliente aprueba el pago:
 *    - modo real: en la ventana oficial (JS SDK de PayPal);
 *    - modo simulado: en #modalPaypalSim, que imita esa ventana.
 * 3. capturar: /pago/paypal/capturar cobra la orden y crea el pedido.
 */
(function () {
    'use strict';

    const form = document.getElementById('formCompra');
    const contenedor = document.getElementById('paypalBotones');
    const procesando = document.getElementById('paypalProcesando');
    if (!form || !contenedor) return;

    // Muestra el botón del método elegido
    const metodoElegido = () => form.querySelector('[data-metodo-pago]:checked')?.value;
    const alternar = () => {
        document.querySelectorAll('[data-pago]').forEach((bloque) => {
            bloque.classList.toggle('d-none', bloque.dataset.pago !== metodoElegido());
        });
    };
    form.addEventListener('change', (evento) => {
        if (evento.target.matches('[data-metodo-pago]')) alternar();
    });
    alternar();

    // Con PayPal elegido, Enter no debe enviar el formulario de contra entrega
    form.addEventListener('submit', (evento) => {
        if (metodoElegido() === 'paypal') evento.preventDefault();
    });

    // -----------------------------------------------------------------
    // Pasos comunes a los dos modos
    // -----------------------------------------------------------------

    /** Devuelve el id de la orden, o lanza un error (ya mostrado al cliente). */
    async function crearOrden() {
        const r = await PYA.post('/pago/paypal/orden', new FormData(form));
        if (!r.ok) {
            PYA.aviso(r.mensaje, 'warning');
            throw new Error(r.mensaje);
        }
        return r.id;
    }

    /** Cobra la orden. Devuelve true si hay que dejar elegir otro medio de pago. */
    async function capturar(ordenId) {
        procesando.classList.remove('d-none');
        const r = await PYA.post('/pago/paypal/capturar', { orden_id: ordenId });

        if (r.ok) {
            window.location.href = r.redirigir;
            return false;
        }
        procesando.classList.add('d-none');
        PYA.aviso(r.mensaje, r.reintentar ? 'warning' : 'danger');
        return Boolean(r.reintentar);
    }

    const avisarCancelado = () => PYA.aviso('Cancelaste el pago. Tu carrito sigue intacto.', 'info');

    if (contenedor.dataset.modo === 'simulado') {
        iniciarSimulado();
    } else {
        iniciarReal();
    }

    // -----------------------------------------------------------------
    // Modo real: botones del JS SDK de PayPal
    // -----------------------------------------------------------------
    function iniciarReal() {
        if (!window.paypal) {
            contenedor.innerHTML = '<p class="small text-danger mb-0">No se pudo cargar PayPal. Revisa tu conexión y recarga la página.</p>';
            return;
        }

        paypal.Buttons({
            style: { layout: 'vertical', color: 'gold', shape: 'rect', label: 'pay' },
            createOrder: crearOrden,
            async onApprove(data, actions) {
                // Tarjeta o fondos rechazados: PayPal deja elegir otro medio en la misma orden
                if (await capturar(data.orderID)) return actions.restart();
            },
            onCancel: avisarCancelado,
            onError(error) {
                console.error(error); // los errores de validación ya se mostraron en crearOrden
            },
        }).render(contenedor);
    }

    // -----------------------------------------------------------------
    // Modo simulado: ventana propia que imita PayPal
    // -----------------------------------------------------------------
    function iniciarSimulado() {
        const boton = document.getElementById('btnPaypalSim');
        const ventana = document.getElementById('modalPaypalSim');
        const formSim = document.getElementById('formPaypalSim');
        const modal = bootstrap.Modal.getOrCreateInstance(ventana);
        let ordenId = null;
        let aprobada = false;

        boton.addEventListener('click', async () => {
            boton.disabled = true;
            try {
                ordenId = await crearOrden();
            } catch (e) {
                return; // el aviso ya se mostró
            } finally {
                boton.disabled = false;
            }
            abrirVentana();
        });

        function abrirVentana() {
            aprobada = false;
            ventana.querySelector('[data-sim-orden]').textContent = ordenId;
            formSim.querySelector('#simRechazar').checked = false;
            modal.show();
        }

        // Cerrar la ventana sin pagar = cancelar (como en PayPal)
        ventana.addEventListener('hidden.bs.modal', () => {
            if (!aprobada) avisarCancelado();
        });

        formSim.addEventListener('submit', async (evento) => {
            evento.preventDefault();
            const pagar = formSim.querySelector('[type="submit"]');
            pagar.disabled = true;

            const datos = new FormData(formSim);
            datos.append('orden_id', ordenId);
            const r = await PYA.post('/pago/paypal/simulador/aprobar', datos);
            pagar.disabled = false;

            if (!r.ok) {
                PYA.aviso(r.mensaje, 'warning');
                return;
            }

            aprobada = true;
            const oculta = new Promise((listo) => ventana.addEventListener('hidden.bs.modal', listo, { once: true }));
            modal.hide();
            const reintentar = await capturar(ordenId);
            await oculta; // Bootstrap ignora show() mientras la ventana se está cerrando

            // Igual que actions.restart(): se vuelve a abrir la ventana con la misma orden
            if (reintentar) abrirVentana();
        });
    }
})();
