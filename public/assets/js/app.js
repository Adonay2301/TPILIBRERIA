/**
 * JavaScript común del sistema (sin frameworks).
 * - PYA.post(): peticiones fetch con token CSRF y respuesta JSON
 * - Cierre automático de alertas
 * - Confirmación de formularios peligrosos (data-confirmar)
 * - Mostrar/ocultar contraseña (data-toggle-password)
 * - Selects dependientes departamento → municipio → distrito (data-ubicacion-hijo)
 */
(function () {
    'use strict';

    const meta = (nombre) => document.querySelector(`meta[name="${nombre}"]`)?.content ?? '';

    const PYA = {
        baseUrl: meta('base-url'),
        csrf: meta('csrf-token'),

        /** Construye una URL del sitio: PYA.url('/carrito') */
        url(ruta) {
            return this.baseUrl + '/' + String(ruta).replace(/^\/+/, '');
        },

        /** Envía datos por POST y devuelve el JSON de la respuesta. */
        async post(ruta, datos = {}) {
            const cuerpo = datos instanceof FormData ? datos : new URLSearchParams(datos);
            const respuesta = await fetch(this.url(ruta), {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': this.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: cuerpo,
            });

            const json = await respuesta.json().catch(() => ({ ok: false, mensaje: 'Respuesta inválida del servidor.' }));

            // Sesión expirada: se manda al login
            if (respuesta.status === 401 && json.login) {
                window.location.href = json.login;
            }
            return { status: respuesta.status, ...json };
        },

        /** Muestra un aviso flotante igual a los mensajes flash del servidor. */
        aviso(mensaje, tipo = 'success') {
            let contenedor = document.querySelector('.alertas-flotantes');
            if (!contenedor) {
                contenedor = document.createElement('div');
                contenedor.className = 'alertas-flotantes';
                document.body.appendChild(contenedor);
            }
            const alerta = document.createElement('div');
            alerta.className = `alert alert-${tipo} alert-dismissible fade show shadow`;
            alerta.setAttribute('role', 'alert');
            alerta.textContent = mensaje;
            const cerrar = document.createElement('button');
            cerrar.type = 'button';
            cerrar.className = 'btn-close';
            cerrar.setAttribute('data-bs-dismiss', 'alert');
            cerrar.setAttribute('aria-label', 'Cerrar');
            alerta.appendChild(cerrar);
            contenedor.appendChild(alerta);
            programarCierre(alerta);
        },

        /** Actualiza el número del ícono del carrito en el navbar. */
        actualizarContador(cantidad) {
            const icono = document.querySelector('.icono-carrito');
            if (!icono) return;
            let contador = icono.querySelector('.contador');
            if (!contador) {
                contador = document.createElement('span');
                contador.className = 'contador';
                icono.appendChild(contador);
            }
            contador.textContent = cantidad;
            contador.hidden = !cantidad;
        },

        /** Formato de moneda de El Salvador: 1234.5 -> $1,234.50 */
        moneda(valor) {
            return '$' + Number(valor).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
    };

    window.PYA = PYA;

    // Las alertas se cierran solas a los 5 segundos
    function programarCierre(alerta) {
        setTimeout(() => {
            if (window.bootstrap && document.body.contains(alerta)) {
                bootstrap.Alert.getOrCreateInstance(alerta).close();
            }
        }, 5000);
    }
    document.querySelectorAll('[data-autocerrar]').forEach(programarCierre);

    // Formularios que piden confirmación: <form data-confirmar="¿Seguro?">
    document.addEventListener('submit', (evento) => {
        const mensaje = evento.target.dataset?.confirmar;
        if (mensaje && !window.confirm(mensaje)) {
            evento.preventDefault();
        }
    });

    // Mostrar/ocultar contraseña: <button data-toggle-password="#idCampo">
    document.addEventListener('click', (evento) => {
        const boton = evento.target.closest('[data-toggle-password]');
        if (!boton) return;
        const campo = document.querySelector(boton.dataset.togglePassword);
        if (!campo) return;
        const visible = campo.type === 'text';
        campo.type = visible ? 'password' : 'text';
        boton.querySelector('i')?.classList.toggle('bi-eye', visible);
        boton.querySelector('i')?.classList.toggle('bi-eye-slash', !visible);
    });

    // Agregar al carrito: <button data-agregar-carrito="15" [data-cantidad-desde="#input"]>
    // Visitante -> modal de login; personal -> aviso; cliente -> fetch al servidor
    document.addEventListener('click', async (evento) => {
        const boton = evento.target.closest('[data-agregar-carrito]');
        if (!boton) return;
        evento.preventDefault();

        const rol = document.body.dataset.rol || '';
        if (!rol) {
            const modal = document.getElementById('modalLogin');
            if (modal) bootstrap.Modal.getOrCreateInstance(modal).show();
            return;
        }
        if (rol !== 'cliente') {
            PYA.aviso('Las compras se hacen con una cuenta de cliente.', 'warning');
            return;
        }

        const campoCantidad = boton.dataset.cantidadDesde && document.querySelector(boton.dataset.cantidadDesde);
        boton.disabled = true;
        const r = await PYA.post('/carrito/agregar', {
            id_libro: boton.dataset.agregarCarrito,
            cantidad: campoCantidad ? campoCantidad.value : 1,
        });
        boton.disabled = false;

        PYA.aviso(r.mensaje, r.ok ? 'success' : 'warning');
        if (r.ok) PYA.actualizarContador(r.carrito);
    });

    // Botones − / + de un campo de cantidad
    document.addEventListener('click', (evento) => {
        const boton = evento.target.closest('[data-cantidad-mas], [data-cantidad-menos]');
        if (!boton) return;
        const campo = document.querySelector(boton.dataset.cantidadMas || boton.dataset.cantidadMenos);
        const paso = boton.dataset.cantidadMas ? 1 : -1;
        campo.value = Math.min(Number(campo.max) || 99, Math.max(Number(campo.min) || 1, Number(campo.value) + paso));
    });

    // Select que navega a la URL de la opción elegida (ordenar)
    document.addEventListener('change', (evento) => {
        const select = evento.target.closest('select[data-navegar]');
        if (select) window.location.href = select.value;
    });

    // Formularios de filtros que se envían solos al marcar una casilla
    document.addEventListener('change', (evento) => {
        const form = evento.target.closest('form[data-autoenviar]');
        if (form && ['checkbox', 'radio'].includes(evento.target.type)) form.submit();
    });

    // Confirmar pedido: mostrar los campos de "otra dirección"
    document.addEventListener('change', (evento) => {
        if (!evento.target.matches('[data-alternar-nueva]')) return;
        document.getElementById('direccionNueva')?.classList.toggle('d-none', evento.target.value !== 'nueva');
    });

    // Modales que reciben datos del botón que los abre: data-modal-datos='{"campo":"valor"}' -> [data-campo="campo"]
    document.addEventListener('show.bs.modal', (evento) => {
        const origen = evento.relatedTarget;
        if (!origen || !origen.dataset.modalDatos) return;
        const datos = JSON.parse(origen.dataset.modalDatos);
        Object.entries(datos).forEach(([clave, valor]) => {
            evento.target.querySelectorAll(`[data-campo="${clave}"]`).forEach((el) => {
                if (clave === 'accion') el.setAttribute('action', valor);
                else if ('value' in el && el.tagName !== 'BUTTON') el.value = valor;
                else el.textContent = valor;
            });
        });
    });

    // Selects dependientes: <select data-ubicacion-hijo="#municipio" data-ubicacion-url="/api/municipios/">
    document.addEventListener('change', async (evento) => {
        const padre = evento.target.closest('[data-ubicacion-hijo]');
        if (!padre) return;

        const hijo = document.querySelector(padre.dataset.ubicacionHijo);
        if (!hijo) return;

        // Limpia el hijo y, en cadena, a sus propios hijos (distrito)
        const limpiar = (select) => {
            select.innerHTML = '<option value="">Seleccionar…</option>';
            select.disabled = true;
            const nieto = select.dataset.ubicacionHijo && document.querySelector(select.dataset.ubicacionHijo);
            if (nieto) limpiar(nieto);
        };
        limpiar(hijo);
        if (!padre.value) return;

        try {
            const respuesta = await fetch(PYA.url(padre.dataset.ubicacionUrl + encodeURIComponent(padre.value)), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const datos = await respuesta.json();
            (datos.items || []).forEach((item) => {
                const opcion = document.createElement('option');
                opcion.value = item.id;
                opcion.textContent = item.nombre;
                hijo.appendChild(opcion);
            });
            hijo.disabled = false;
        } catch (e) {
            PYA.aviso('No se pudieron cargar las ubicaciones.', 'danger');
        }
    });
})();
