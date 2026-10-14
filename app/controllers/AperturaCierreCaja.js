/* =====================================================================
 * Apertura y cierre de caja
 * Módulo independiente: proceso de apertura/cierre e historial de cierres
 * de la bodega de la sesión. No depende del reporte de cierres.
 * ===================================================================== */
// Globales que usan las funciones generales del sistema (cargarConfiguraciones, permisos, etc.)
const id_modulo = 42;
let permisos = [];

(() => {
    const LINK = '../models/AperturaCierreCaja.php';

    // Denominaciones colombianas para el contador
    const BILLETES = [100000, 50000, 20000, 10000, 5000, 2000, 1000];
    const MONEDAS = [1000, 500, 200, 100, 50];

    let estado = null;             // última respuesta de 'info'
    let desfaseReloj = 0;          // hora servidor − hora navegador (ms)
    let timer = null;
    let enviando = false;

    const conteos = {};            // conteo de billetes por campo destino
    let contarDestino = null;

    /* ------------------------------------------------------------------
     * Utilidades
     * ------------------------------------------------------------------ */
    const num = (v) => {
        if (v === null || v === undefined || v === '') return 0;
        if (typeof v === 'number') return Number.isFinite(v) ? v : 0;
        let s = String(v).trim().replace(/[$\s]/g, '');
        if (/^-?\d{1,3}(\.\d{3})+$/.test(s)) return parseFloat(s.replace(/\./g, '')); // miles es-CO: 150.000
        if (/^-?\d+(\.\d{1,2})?$/.test(s)) return parseFloat(s);     // formato BD: 150000.00
        s = s.replace(/\./g, '').replace(',', '.');                     // formato es-CO
        const n = parseFloat(s);
        return Number.isFinite(n) ? n : 0;
    };

    const miles = (n) => new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 }).format(Math.round(n || 0));
    const pesos = (n) => '$' + miles(n);

    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));

    const parseFecha = (v) => {
        if (!v || String(v).startsWith('0000')) return null;
        const d = new Date(String(v).replace(' ', 'T'));
        return isNaN(d.getTime()) ? null : d;
    };

    const fmtFecha = (v) => {
        const d = parseFecha(v);
        return d ? d.toLocaleString('es-CO', { weekday: 'short', day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : '—';
    };

    const fmtDuracion = (min) => {
        min = Math.max(0, Math.floor(min));
        const h = Math.floor(min / 60);
        const m = min % 60;
        if (h >= 24) return `${Math.floor(h / 24)} d ${h % 24} h`;
        return h ? `${h} h ${String(m).padStart(2, '0')} min` : `${m} min`;
    };

    // Los campos de dinero son pesos enteros: solo cuentan los dígitos ("150.000" → 150000)
    const numCampo = (v) => parseInt(String(v ?? '').replace(/[^\d]/g, ''), 10) || 0;

    const valorCampo = (id) => numCampo($(`#${id}`).val());

    const setValorCampo = (id, n) => {
        $(`#${id}`).val(miles(n));
    };

    // Carga con el logo del sistema. Se cierra ANTES de mostrar cualquier otro mensaje.
    const cargando = (mensaje = 'Cargando información') => showLoadingWithTetrisLogo(mensaje, 'ISA Software');
    const listo = () => dissminSwal();

    const toast = (icon, title) => Swal.fire({
        toast: true, position: 'top-end', icon, title, showConfirmButton: false, timer: 2500, timerProgressBar: true
    });

    /* ------------------------------------------------------------------
     * Campos de dinero: formato al salir, número limpio al entrar
     * ------------------------------------------------------------------ */
    const bindCamposDinero = () => {
        $(document).on('focus', '.ac-money', function () {
            if (this.readOnly) return;
            const n = numCampo($(this).val());
            $(this).val(n ? String(n) : '');
            setTimeout(() => this.select(), 0);
        });

        $(document).on('input', '.ac-money', function () {
            const limpio = $(this).val().replace(/[^\d]/g, '');
            if (limpio !== $(this).val()) $(this).val(limpio);
            if ($(this).hasClass('ac-cierre-valor')) actualizarTotal();
        });

        $(document).on('blur', '.ac-money', function () {
            $(this).val(miles(numCampo($(this).val())));
            if ($(this).hasClass('ac-cierre-valor')) actualizarTotal();
        });
    };

    const actualizarTotal = () => {
        const ef = valorCampo('ac-efectivo');
        const tj = valorCampo('ac-tarjetas');
        const ot = valorCampo('ac-otros');
        $('#ac-total').text(pesos(ef + tj + ot));
        $('#ac-total-desglose').text(`${pesos(ef)} + ${pesos(tj)} + ${pesos(ot)}`);
    };

    /* ------------------------------------------------------------------
     * Estado de la caja
     * ------------------------------------------------------------------ */
    const pintarTiempo = () => {
        if (!estado?.abierta) return;
        const inicio = parseFecha(estado.fecha_apertura);
        if (!inicio) return;
        const ahora = Date.now() + desfaseReloj;
        const min = (ahora - inicio.getTime()) / 60000;
        $('#ac-tiempo').text(fmtDuracion(min));
        $('#ac-estado-sub').text(`Abierta hace ${fmtDuracion(min)}`);
    };

    const cargarEstado = async () => {
        $('#ac-refrescar').prop('disabled', true);
        cargando('Consultando tu caja');
        try {
            const resp = await enviarPeticion({ link: LINK, op: 'info' });
            listo();

            if (!resp || resp.ok === false) {
                $('#ac-estado').attr('class', 'ac-estado ac-cerrada mb-3');
                $('#ac-estado .ac-estado-icon').html('<i class="fa-solid fa-triangle-exclamation"></i>');
                $('#ac-estado-titulo').text('No se pudo consultar la caja');
                $('#ac-estado-sub').text(resp?.message || resp?.mensaje || 'Intenta actualizar de nuevo.');
                $('#ac-panel-apertura, #ac-panel-cierre').addClass('d-none');
                return;
            }

            estado = resp;
            const servidor = parseFecha(resp.hora_servidor);
            desfaseReloj = servidor ? servidor.getTime() - Date.now() : 0;

            $('#id_apertura').val(resp.id_apertura);
            $('#id_usuario').val(resp.id_usuario);
            $('#id_bodega').val(resp.id_bodega);
            $('#bodega').val(resp.bodega_des);

            $('#ac-usuario').text(String(resp.usuario || '').toUpperCase());
            $('#ac-bodega').text(resp.bodega_des || '—');
            $('#ac-ultimo-cierre').text(resp.ultimo_cierre ? fmtFecha(resp.ultimo_cierre) : 'Sin cierres');
            $('#ac-imprimir-ultimo').toggleClass('d-none', !(resp.id_ultimo_cierre > 0));

            clearInterval(timer);

            if (resp.abierta) {
                $('#ac-estado').attr('class', 'ac-estado ac-abierta mb-3');
                $('#ac-estado .ac-estado-icon').html('<i class="fa-solid fa-lock-open"></i>');
                $('#ac-estado-titulo').text('Caja abierta');
                $('#ac-li-apertura, #ac-li-tiempo').removeClass('d-none');
                $('#ac-fecha-apertura').text(fmtFecha(resp.fecha_apertura));

                $('#ac-panel-apertura').addClass('d-none');
                $('#ac-panel-cierre').removeClass('d-none');
                $('#ac-base-ref').text(pesos(num(resp.base)));

                // Si el cierre se dejó a medias, conserva lo que hay
                setValorCampo('ac-efectivo', num(resp.efectivo));
                setValorCampo('ac-tarjetas', num(resp.tarjetas));
                setValorCampo('ac-otros', num(resp.otros));
                $('#ac-observaciones').val(resp.observaciones || '');
                actualizarTotal();

                pintarTiempo();
                timer = setInterval(pintarTiempo, 30000);
            } else {
                $('#ac-estado').attr('class', 'ac-estado ac-cerrada mb-3');
                $('#ac-estado .ac-estado-icon').html('<i class="fa-solid fa-lock"></i>');
                $('#ac-estado-titulo').text('Caja cerrada');
                $('#ac-estado-sub').text('Abre la caja para empezar a vender.');
                $('#ac-li-apertura, #ac-li-tiempo').addClass('d-none');

                $('#ac-panel-cierre').addClass('d-none');
                $('#ac-panel-apertura').removeClass('d-none');
                aplicarControlBase();
            }

            pintarAvisos();
        } catch (e) {
            console.error('Error consultando la caja:', e);
            listo();
            Swal.fire('Error', 'No fue posible consultar el estado de la caja', 'error');
        } finally {
            $('#ac-refrescar').prop('disabled', false);
        }
    };

    const baseMinima = () => {
        const conf = typeof ConfModulos !== 'undefined' ? ConfModulos : window.ConfModulos;
        const v = num(conf?.ControlBaseMinima);
        return v > 0 ? v : 0;
    };

    const aplicarControlBase = () => {
        const fija = baseMinima();
        if (fija > 0) {
            setValorCampo('ac-base', fija);
            $('#ac-base').prop('readonly', true);
            $('[data-contar="ac-base"]').addClass('d-none');
            $('#ac-base-ayuda').text('La base está definida por la configuración del sistema.');
        } else {
            $('#ac-base').prop('readonly', false);
            $('[data-contar="ac-base"]').removeClass('d-none');
            if (!valorCampo('ac-base')) setValorCampo('ac-base', 0);
        }
    };

    const pintarAvisos = () => {
        const avisos = [];
        if (baseMinima() > 0) {
            avisos.push(`<div class="alert alert-primary py-2 px-3 small mb-0">
                <i class="fa-solid fa-circle-info me-1"></i>
                <strong>Base fija:</strong> la base de caja debe ser ${pesos(baseMinima())}, según la configuración del sistema.
            </div>`);
        }
        if (permisos?.ControlDiarioAperturaCaja) {
            avisos.push(`<div class="alert alert-primary py-2 px-3 small mb-0">
                <i class="fa-solid fa-calendar-day me-1"></i>
                <strong>Una apertura por día:</strong> solo puedes abrir y cerrar caja una vez al día.
            </div>`);
        }
        $('#ac-avisos').html(avisos.join(''));
    };

    /* ------------------------------------------------------------------
     * Abrir caja
     * ------------------------------------------------------------------ */
    const abrirCaja = async () => {
        if (enviando) return;
        const base = valorCampo('ac-base');

        const confirm = await Swal.fire({
            title: '¿Abrir la caja?',
            html: `
                <div class="text-start small">
                    <div class="d-flex justify-content-between py-1"><span>Bodega</span><strong>${esc(estado?.bodega_des)}</strong></div>
                    <div class="d-flex justify-content-between py-1 border-top"><span>Base de caja</span><strong>${pesos(base)}</strong></div>
                    ${base === 0 ? '<div class="alert alert-primary py-1 px-2 mt-2 mb-0">Vas a abrir la caja sin base.</div>' : ''}
                </div>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fa-solid fa-lock-open me-1"></i>Sí, abrir',
            cancelButtonText: 'Revisar',
            confirmButtonColor: '#71dd37',
            reverseButtons: true,
            focusConfirm: true
        });
        if (!confirm.isConfirmed) return;

        enviando = true;
        const $btn = $('#ac-btn-abrir').prop('disabled', true);
        cargando('Abriendo caja');

        try {
            const resp = await enviarPeticion({
                link: LINK,
                op: 'abrir_caja',
                base,
                id_bodega: $('#id_bodega').val(),
                ControlDiarioAperturaCaja: permisos?.ControlDiarioAperturaCaja ? 1 : 0
            });
            listo();

            if (!resp?.ok) {
                Swal.fire('No se pudo abrir la caja', resp?.message || 'Intenta de nuevo', 'error');
                return;
            }

            conteos['ac-base'] = {};
            await cargarEstado();
            toast('success', resp.message || 'Caja abierta');

            if ($('#redirect').val() === '1') {
                setTimeout(() => { window.location.href = 'Pos.php'; }, 1200);
            } else {
                setTimeout(() => $('#ac-efectivo').trigger('focus'), 300);
            }
        } catch (e) {
            console.error('Error abriendo la caja:', e);
            listo();
            Swal.fire('Error', 'Se generó un error en la aplicación', 'error');
        } finally {
            enviando = false;
            $btn.prop('disabled', false);
        }
    };

    /* ------------------------------------------------------------------
     * Cerrar caja
     * ------------------------------------------------------------------ */
    const cerrarCaja = async () => {
        if (enviando) return;
        const efectivo = valorCampo('ac-efectivo');
        const tarjetas = valorCampo('ac-tarjetas');
        const otros = valorCampo('ac-otros');
        const total = efectivo + tarjetas + otros;
        const observaciones = $('#ac-observaciones').val().trim();

        const fila = (lbl, val, extra = '') =>
            `<div class="d-flex justify-content-between py-1 ${extra}"><span>${lbl}</span><strong>${val}</strong></div>`;

        const confirm = await Swal.fire({
            title: '¿Cerrar la caja?',
            html: `
                <div class="text-start small">
                    ${fila('Efectivo', pesos(efectivo))}
                    ${fila('Tarjetas / Bancos', pesos(tarjetas), 'border-top')}
                    ${fila('Otros medios', pesos(otros), 'border-top')}
                    ${fila('Total registrado', `<span class="text-primary">${pesos(total)}</span>`, 'border-top fs-6')}
                    <div class="text-muted mt-2"><i class="fa-solid fa-circle-info me-1"></i>Base del turno: ${pesos(num(estado?.base))} (no se incluye).</div>
                    ${total === 0 ? '<div class="alert alert-primary py-1 px-2 mt-2 mb-0">Todos los valores están en cero. ¿Seguro que no hubo movimientos?</div>' : ''}
                    <div class="text-muted mt-2">Después de cerrar no podrás modificar estos valores.</div>
                </div>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '<i class="fa-solid fa-lock me-1"></i>Sí, cerrar caja',
            cancelButtonText: 'Revisar',
            confirmButtonColor: '#ff3e1d',
            reverseButtons: true
        });
        if (!confirm.isConfirmed) return;

        enviando = true;
        const $btn = $('#ac-btn-cerrar').prop('disabled', true);
        cargando('Cerrando caja');
        const idApertura = $('#id_apertura').val();

        try {
            const resp = await enviarPeticion({
                link: LINK,
                op: 'cerrar_caja',
                id_apertura: idApertura,
                efectivo,
                tarjetas,
                otros,
                observaciones
            });
            listo();

            if (!resp?.ok) {
                Swal.fire('No se pudo cerrar la caja', resp?.message || 'Intenta de nuevo', 'error');
                return;
            }

            conteos['ac-efectivo'] = {};
            hc.desactualizado = true;
            await cargarEstado();

            const fin = await Swal.fire({
                icon: 'success',
                title: 'Caja cerrada',
                text: resp.message || 'La caja se cerró correctamente.',
                showCancelButton: true,
                confirmButtonText: '<i class="fa fa-print me-1"></i>Imprimir cierre',
                cancelButtonText: 'Listo',
                reverseButtons: true
            });

            if (fin.isConfirmed) {
                imprimirCierre(resp.id_apertura || idApertura);
            }

            if ($('#redirect').val() === '1') {
                setTimeout(() => { window.location.href = 'Pos.php'; }, fin.isConfirmed ? 4000 : 500);
            }
        } catch (e) {
            console.error('Error cerrando la caja:', e);
            listo();
            Swal.fire('Error', 'Se generó un error en la aplicación', 'error');
        } finally {
            enviando = false;
            $btn.prop('disabled', false);
        }
    };

    /* ------------------------------------------------------------------
     * Contador de billetes y monedas
     * ------------------------------------------------------------------ */
    const filaDenominacion = (valor, tipo) => {
        const key = `${tipo}-${valor}`;
        return `
        <div class="ac-den" data-key="${key}" data-valor="${valor}">
            <div class="ac-den-lbl">${pesos(valor)} <small>${tipo === 'b' ? 'billete' : 'moneda'}</small></div>
            <div class="ac-den-qty">
                <button type="button" class="btn btn-sm btn-outline-secondary px-2" data-paso="-1" aria-label="Menos">−</button>
                <input type="text" inputmode="numeric" class="form-control form-control-sm" value="" placeholder="0" aria-label="Cantidad de ${pesos(valor)}">
                <button type="button" class="btn btn-sm btn-outline-secondary px-2" data-paso="1" aria-label="Más">+</button>
            </div>
            <div class="ac-den-sub">$0</div>
        </div>`;
    };

    const renderContador = () => {
        $('#ac-contar-body').html(`
            <div class="ac-den-titulo">Billetes</div>
            ${BILLETES.map((v) => filaDenominacion(v, 'b')).join('')}
            <div class="ac-den-titulo">Monedas</div>
            ${MONEDAS.map((v) => filaDenominacion(v, 'm')).join('')}
        `);
    };

    const recalcularContador = () => {
        let total = 0;
        const actual = {};
        $('#ac-contar-body .ac-den').each(function () {
            const valor = Number($(this).data('valor'));
            const qty = parseInt($(this).find('input').val(), 10) || 0;
            const sub = valor * qty;
            total += sub;
            actual[$(this).data('key')] = qty;
            $(this).find('.ac-den-sub').text(pesos(sub)).toggleClass('ac-tiene', qty > 0);
        });
        if (contarDestino) conteos[contarDestino] = actual;
        $('#ac-contar-total').text(pesos(total)).data('total', total);
    };

    const abrirContador = (destino) => {
        contarDestino = destino;
        renderContador();
        const previo = conteos[destino] || {};
        $('#ac-contar-body .ac-den').each(function () {
            const q = previo[$(this).data('key')];
            if (q) $(this).find('input').val(q);
        });
        recalcularContador();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('ac-modal-contar')).show();
    };

    const bindContador = () => {
        $(document).on('click', '[data-contar]', function () {
            abrirContador($(this).data('contar'));
        });

        $('#ac-contar-body').on('click', '[data-paso]', function () {
            const $in = $(this).closest('.ac-den').find('input');
            const q = Math.max(0, (parseInt($in.val(), 10) || 0) + Number($(this).data('paso')));
            $in.val(q || '');
            recalcularContador();
        });

        $('#ac-contar-body').on('input', 'input', function () {
            $(this).val($(this).val().replace(/[^\d]/g, ''));
            recalcularContador();
        });

        // Enter pasa a la siguiente denominación
        $('#ac-contar-body').on('keydown', 'input', function (e) {
            if (e.key !== 'Enter') return;
            e.preventDefault();
            const inputs = $('#ac-contar-body input');
            const i = inputs.index(this);
            if (i < inputs.length - 1) inputs.eq(i + 1).trigger('focus').trigger('select');
            else $('#ac-contar-usar').trigger('focus');
        });

        $('#ac-contar-limpiar').on('click', () => {
            $('#ac-contar-body input').val('');
            recalcularContador();
        });

        $('#ac-contar-usar').on('click', () => {
            if (!contarDestino) return;
            setValorCampo(contarDestino, Number($('#ac-contar-total').data('total')) || 0);
            if ($(`#${contarDestino}`).hasClass('ac-cierre-valor')) actualizarTotal();
            bootstrap.Modal.getOrCreateInstance(document.getElementById('ac-modal-contar')).hide();
        });

        $('#ac-modal-contar').on('shown.bs.modal', () => {
            $('#ac-contar-body input').first().trigger('focus');
        });
    };

    /* ------------------------------------------------------------------
     * Editar cierre (permiso EditarCierreCaja)
     * ------------------------------------------------------------------ */
    const abrirEditar = async (idApertura) => {
        try {
            cargando('Cargando cierre');
            const resp = await enviarPeticion({ link: LINK, op: 's_cierre', id_apertura: idApertura });
            listo();
            if (!resp?.ok) {
                Swal.fire('Error', resp?.message || 'No se pudo cargar el cierre', 'error');
                return;
            }
            $('#ac-editar-id').val(idApertura);
            $('#ac-editar-num').text(`#${idApertura}`);
            $('#ac-editar-fechas').html(`<i class="fa-regular fa-clock me-1"></i>${fmtFecha(resp.fecha_apertura)} → ${fmtFecha(resp.fecha_cierre)}`);
            setValorCampo('ac-editar-base', num(resp.base));
            setValorCampo('ac-editar-efectivo', num(resp.efectivo));
            setValorCampo('ac-editar-tarjetas', num(resp.tarjetas));
            setValorCampo('ac-editar-otros', num(resp.otros));
            $('#ac-editar-obs').val(resp.observaciones || '').removeClass('is-invalid');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('ac-modal-editar')).show();
        } catch (e) {
            console.error('Error cargando el cierre:', e);
            listo();
            Swal.fire('Error', 'Se generó un error en la aplicación', 'error');
        }
    };

    const guardarEdicion = async () => {
        const obs = $('#ac-editar-obs').val().trim();
        if (!obs) {
            $('#ac-editar-obs').addClass('is-invalid').trigger('focus');
            return;
        }
        const $btn = $('#ac-editar-guardar').prop('disabled', true);
        cargando('Guardando cambios');
        try {
            const resp = await enviarPeticion({
                link: LINK,
                op: 'u_cierre',
                id_apertura: $('#ac-editar-id').val(),
                base: valorCampo('ac-editar-base'),
                efectivo: valorCampo('ac-editar-efectivo'),
                tarjetas: valorCampo('ac-editar-tarjetas'),
                otros: valorCampo('ac-editar-otros'),
                observaciones: obs
            });
            listo();
            if (!resp?.ok) {
                Swal.fire('Error', resp?.message || 'No se pudo actualizar', 'error');
                return;
            }
            bootstrap.Modal.getOrCreateInstance(document.getElementById('ac-modal-editar')).hide();
            if (hc.cargado) await hcConsultar();
            toast('success', resp.message || 'Cierre actualizado');
        } catch (e) {
            console.error('Error actualizando el cierre:', e);
            listo();
            Swal.fire('Error', 'Se generó un error en la aplicación', 'error');
        } finally {
            $btn.prop('disabled', false);
        }
    };

    /* ==================================================================
     * HISTORIAL DE CIERRES (bodega de la sesión)
     * Diferencia = lo registrado por el cajero − lo calculado por el sistema
     *   negativa → faltante | positiva → sobrante
     * La base es un valor fijo de apertura y NO entra en las diferencias.
     * ================================================================== */
    const HC_CFG = {
        tolerancia: 1000,   // diferencias menores o iguales se consideran cuadradas
        camposFijos: [
            'id_apertura', 'id_bodega', 'usuario', 'nombres', 'fecha_apertura', 'fecha_cierre',
            'base', 'efectivo_usuario', 'bancos_usuario', 'otro_usuario', 'observaciones',
            'egresos', 'ingresos', 'total'
        ],
        registrado: { efectivo: 'efectivo_usuario', bancos: 'bancos_usuario', otros: 'otro_usuario' },
        // Asignación manual de un medio a un grupo (nombre en minúscula tal como sale del SP)
        mapaMedios: {},
        patrones: {
            bancos: /tarjeta|d[eé]bito|banc|transfer|consign|nequi|daviplata|pse|\bqr\b|dat[aá]fono|bold|wompi|redeban|credibanco/i,
            efectivo: /efectivo|contado|cash/i
        }
    };

    const HC_GRUPOS = ['efectivo', 'bancos', 'otros'];
    const HC_GRUPO_INFO = {
        efectivo: { txt: 'Efectivo', icon: 'fa-money-bill-wave' },
        bancos: { txt: 'Bancos / tarjetas', icon: 'fa-building-columns' },
        otros: { txt: 'Otros', icon: 'fa-wallet' }
    };
    const HC_ESTADOS = {
        faltante: { txt: 'Faltante', cls: 'danger', icon: 'fa-circle-exclamation', prio: 0 },
        sobrante: { txt: 'Sobrante', cls: 'warning', icon: 'fa-triangle-exclamation', prio: 1 },
        cruzado: { txt: 'Cruce de medios', cls: 'primary', icon: 'fa-right-left', prio: 2 },
        ok: { txt: 'Cuadrado', cls: 'primary', icon: 'fa-circle-check', prio: 3 }
    };
    const HC_TABS = [
        { key: 'ingresos', txt: 'Ingresos', icon: 'fa-arrow-trend-up', cls: 'primary', vacio: 'No hubo ingresos de caja en este turno.' },
        { key: 'egresos', txt: 'Egresos', icon: 'fa-arrow-trend-down', cls: 'primary', vacio: 'No hubo egresos de caja en este turno.' },
        { key: 'ventas', txt: 'Ventas', icon: 'fa-cart-shopping', cls: 'primary', vacio: 'No hubo ventas en este turno.' },
        { key: 'devoluciones', txt: 'Devoluciones', icon: 'fa-rotate-left', cls: 'primary', vacio: 'No hubo devoluciones en este turno.' }
    ];

    const hc = {
        cargado: false,
        desactualizado: false,
        data: [],
        bodega: '',
        filtro: 'todos',
        orden: 'alerta',
        busqueda: '',
        movCache: new Map(),
        actual: null
    };

    const ymd = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    const fmtCorta = (d) => d ? d.toLocaleString('es-CO', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : '—';
    const labelMedio = (k) => String(k || '').replace(/[_-]+/g, ' ').replace(/\s+/g, ' ').trim().toLowerCase().replace(/(^|\s)\S/g, (c) => c.toUpperCase());

    const pesosDif = (n) => {
        const v = Math.round(n || 0);
        if (v === 0) return pesos(0);
        return (v > 0 ? '+' : '−') + pesos(Math.abs(v));
    };
    const difClase = (dif) => (Math.abs(dif) <= HC_CFG.tolerancia ? 'ok' : (dif < 0 ? 'neg' : 'pos'));
    const difTexto = (dif) => ({ ok: 'Cuadrado', neg: 'Faltante', pos: 'Sobrante' }[difClase(dif)]);

    const grupoMedio = (k) => {
        const key = String(k).toLowerCase();
        if (HC_CFG.mapaMedios[key]) return HC_CFG.mapaMedios[key];
        if (HC_CFG.patrones.bancos.test(key)) return 'bancos';
        if (HC_CFG.patrones.efectivo.test(key)) return 'efectivo';
        return 'otros';
    };

    const duracion = (a, b) => {
        if (!a || !b) return '';
        return fmtDuracion((b - a) / 60000);
    };

    /* ---------- Datos ---------- */
    const hcEnriquecer = (rows) => {
        if (!rows.length) return [];
        const medios = Object.keys(rows[0])
            .filter((k) => !HC_CFG.camposFijos.includes(k))
            .map((k) => ({ key: k, label: labelMedio(k), grupo: grupoMedio(k) }));

        return rows.map((r) => {
            const base = num(r.base);
            const ingresos = num(r.ingresos);
            const egresos = num(r.egresos);
            const lista = medios.map((m) => ({ ...m, valor: num(r[m.key]) }));

            const ventas = { efectivo: 0, bancos: 0, otros: 0 };
            lista.forEach((m) => { ventas[m.grupo] += m.valor; });

            // Efectivo esperado = ventas en efectivo + ingresos − egresos (sin base)
            const sistema = { ...ventas, efectivo: ventas.efectivo + ingresos - egresos };

            const grupos = HC_GRUPOS.map((g) => {
                const registrado = num(r[HC_CFG.registrado[g]]);
                return { key: g, ...HC_GRUPO_INFO[g], registrado, sistema: sistema[g], dif: registrado - sistema[g] };
            });

            const totalRegistrado = grupos.reduce((s, g) => s + g.registrado, 0);
            const totalSistema = grupos.reduce((s, g) => s + g.sistema, 0);
            const dif = totalRegistrado - totalSistema;

            let est = 'ok';
            if (dif < -HC_CFG.tolerancia) est = 'faltante';
            else if (dif > HC_CFG.tolerancia) est = 'sobrante';
            else if (grupos.some((g) => Math.abs(g.dif) > HC_CFG.tolerancia)) est = 'cruzado';

            return {
                raw: r,
                id: r.id_apertura,
                usuario: r.usuario,
                nombres: r.nombres,
                observaciones: String(r.observaciones ?? '').trim(),
                apertura: parseFecha(r.fecha_apertura),
                cierre: parseFecha(r.fecha_cierre),
                base, ingresos, egresos, ventas,
                medios: lista,
                grupos, totalRegistrado, totalSistema, dif,
                estado: est
            };
        });
    };

    const HC_ORDENES = {
        alerta: (a, b) => HC_ESTADOS[a.estado].prio - HC_ESTADOS[b.estado].prio || Math.abs(b.dif) - Math.abs(a.dif),
        dif_asc: (a, b) => a.dif - b.dif,
        dif_desc: (a, b) => b.dif - a.dif,
        fecha_desc: (a, b) => (b.apertura?.getTime() || 0) - (a.apertura?.getTime() || 0),
        fecha_asc: (a, b) => (a.apertura?.getTime() || 0) - (b.apertura?.getTime() || 0)
    };

    const hcFiltrados = () => {
        let d = [...hc.data];
        const q = hc.busqueda.toLowerCase().trim();
        if (q) d = d.filter((x) => [x.id, x.usuario, x.nombres].join(' ').toLowerCase().includes(q));
        if (hc.filtro === 'revisar') d = d.filter((x) => x.estado !== 'ok');
        else if (hc.filtro !== 'todos') d = d.filter((x) => x.estado === hc.filtro);
        return d.sort(HC_ORDENES[hc.orden] || HC_ORDENES.alerta);
    };

    /* ---------- Render ---------- */
    const hcRenderKpis = () => {
        const d = hc.data;
        const sum = (arr, k) => arr.reduce((s, x) => s + x[k], 0);
        const falt = d.filter((x) => x.estado === 'faltante');
        const sobr = d.filter((x) => x.estado === 'sobrante');
        const neta = sum(d, 'dif');
        const c = difClase(neta);
        const clsNeta = c === 'ok' ? 'primary' : (c === 'neg' ? 'danger' : 'warning');

        const kpis = [
            { t: 'Cierres', v: d.length, icon: 'fa-cash-register', cls: 'primary', sub: `${d.filter((x) => x.estado === 'ok').length} cuadrado(s)`, accion: 'todos' },
            { t: 'Diferencia neta', v: pesosDif(neta), icon: 'fa-scale-balanced', cls: 'primary', valorCls: clsNeta, sub: difTexto(neta), destacar: true },
            { t: 'Faltantes', v: falt.length, icon: 'fa-circle-exclamation', cls: 'primary', sub: falt.length ? pesosDif(sum(falt, 'dif')) : 'Ninguno', accion: falt.length ? 'faltante' : null },
            { t: 'Sobrantes', v: sobr.length, icon: 'fa-triangle-exclamation', cls: 'primary', sub: sobr.length ? pesosDif(sum(sobr, 'dif')) : 'Ninguno', accion: sobr.length ? 'sobrante' : null }
        ];

        $('#hc-kpis').html(kpis.map((k) => `
            <div class="col-6 col-md-3">
                <div class="card hc-kpi h-100 ${k.accion ? 'hc-clickable' : ''} ${k.destacar ? 'hc-kpi-destacado border-primary' : ''}"
                     ${k.accion ? `data-accion="${k.accion}" role="button" tabindex="0"` : ''}>
                    <div class="card-body p-2 p-md-3 d-flex gap-2 align-items-start">
                        <span class="hc-kpi-icon bg-label-primary"><i class="fa-solid ${k.icon}"></i></span>
                        <div class="min-w-0">
                            <div class="hc-kpi-title">${k.t}</div>
                            <div class="hc-kpi-value ${k.valorCls && k.valorCls !== 'primary' ? `text-${k.valorCls}` : ''}">${k.v}</div>
                            <div class="hc-kpi-sub">${k.sub}</div>
                        </div>
                    </div>
                </div>
            </div>`).join(''));

        const totalBase = sum(d, 'base');
        $('#hc-aviso-base').removeClass('d-none').html(`
            <div class="alert alert-primary alert-dismissible d-flex align-items-start gap-2 py-2 small mb-3" role="alert">
                <i class="fa-solid fa-circle-info mt-1"></i>
                <div><strong>La base de caja no hace parte de las diferencias.</strong>
                Es un valor fijo de apertura (${pesos(totalBase)} en total para este periodo) y se muestra solo como referencia.</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>`);
    };

    const hcCeldaGrupo = (g) => {
        if (g.registrado === 0 && g.sistema === 0) {
            return `<div class="hc-g hc-g-vacio"><div class="hc-g-lbl"><i class="fa-solid ${g.icon} me-1"></i>${g.txt}</div>Sin movimientos</div>`;
        }
        return `
            <div class="hc-g">
                <div class="hc-g-lbl"><i class="fa-solid ${g.icon} me-1"></i>${g.txt}</div>
                <div class="hc-g-vals" title="Registrado por el cajero"><i class="fa-solid fa-user-pen"></i>${pesos(g.registrado)}</div>
                <div class="hc-g-vals" title="Calculado por el sistema"><i class="fa-solid fa-server"></i>${pesos(g.sistema)}</div>
                <span class="hc-pill hc-dif-${difClase(g.dif)}">${pesosDif(g.dif)}</span>
            </div>`;
    };

    const hcItem = (d) => {
        const e = HC_ESTADOS[d.estado];
        const puedeEditar = !!permisos?.EditarCierreCaja;
        return `
        <div class="hc-item" data-id="${esc(d.id)}" data-estado="${d.estado}" tabindex="0" role="button" aria-label="Ver detalle del cierre ${esc(d.id)}">
            <div class="min-w-0">
                <div class="fw-semibold hc-strong text-truncate"><i class="fa-regular fa-user me-1"></i>${esc(d.nombres || d.usuario)}</div>
                <div class="small text-muted text-truncate">
                    #${esc(d.id)} · ${fmtCorta(d.apertura)} → ${fmtCorta(d.cierre)}
                    <span class="d-none d-xl-inline">(${duracion(d.apertura, d.cierre)})</span>
                </div>
                <div class="small text-muted text-truncate">
                    <span class="hc-base-ref" title="Valor fijo de apertura. No hace parte de la diferencia."><i class="fa-solid fa-circle-info me-1"></i>Base ${pesos(d.base)}</span>
                    · <a href="#" class="hc-link-tab" data-tab="ingresos">Ing. ${pesos(d.ingresos)}</a>
                    · <a href="#" class="hc-link-tab" data-tab="egresos">Egr. ${pesos(d.egresos)}</a>
                </div>
                ${d.observaciones ? `<div class="small text-muted text-truncate" title="${esc(d.observaciones)}"><i class="fa-regular fa-comment-dots me-1"></i>${esc(d.observaciones)}</div>` : ''}
            </div>

            <div class="hc-item-grupos">${d.grupos.map(hcCeldaGrupo).join('')}</div>

            <div class="hc-item-total">
                <div class="hc-big text-${d.estado === 'faltante' ? 'danger' : (d.estado === 'sobrante' ? 'warning' : 'primary')}">${pesosDif(d.dif)}</div>
                <span class="badge bg-label-${e.cls} hc-badge"><i class="fa-solid ${e.icon} me-1"></i>${e.txt}</span>
            </div>

            <div class="hc-item-acciones">
                <button type="button" class="btn btn-sm btn-outline-primary" title="Ver detalle"><i class="fa-solid fa-magnifying-glass-dollar"></i><span class="d-lg-none ms-1">Detalle</span></button>
                ${puedeEditar ? `<button type="button" class="btn btn-sm btn-outline-primary hc-btn-editar" title="Editar cierre"><i class="fa-solid fa-pen-to-square"></i><span class="d-lg-none ms-1">Editar</span></button>` : ''}
                <button type="button" class="btn btn-sm btn-outline-secondary hc-btn-print" title="Imprimir cierre"><i class="fa fa-print"></i><span class="d-lg-none ms-1">Imprimir</span></button>
            </div>
        </div>`;
    };

    /* ---------- Gráficos (ApexCharts) ---------- */
    const hcCharts = { medios: null, comparativa: null };

    const hcRenderGraficos = () => {
        if (typeof ApexCharts === 'undefined') {
            $('#hc-graficos').addClass('d-none');
            return;
        }
        $('#hc-graficos').removeClass('d-none');
        const oscuro = document.documentElement.getAttribute('data-color-scheme') === 'dark';
        // Colores de texto y rejilla de Sneat (claro / oscuro)
        const textoEje = oscuro ? '#a3a4cc' : '#a1acb8';
        const rejilla = oscuro ? '#444564' : '#eceef1';
        const base = {
            chart: { background: 'transparent', fontFamily: '"Public Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif', toolbar: { show: false } },
            theme: { mode: oscuro ? 'dark' : 'light' },
            grid: { borderColor: rejilla, strokeDashArray: 4 },
            legend: { labels: { colors: textoEje } },
            tooltip: { y: { formatter: (v) => pesos(v) } }
        };

        // Medios de pago del sistema (suma del periodo)
        const totales = {};
        hc.data.forEach((d) => d.medios.forEach((m) => { totales[m.label] = (totales[m.label] || 0) + m.valor; }));
        const medios = Object.entries(totales).filter(([, v]) => v > 0).sort((a, b) => b[1] - a[1]);

        if (hcCharts.medios) { hcCharts.medios.destroy(); hcCharts.medios = null; }
        if (medios.length) {
            hcCharts.medios = new ApexCharts(document.querySelector('#hc-grafico-medios'), {
                ...base,
                chart: { ...base.chart, type: 'donut', height: 300 },
                series: medios.map(([, v]) => Math.round(v)),
                labels: medios.map(([k]) => k),
                // Escala del color primary del sistema
                colors: ['#696cff', '#8a8cff', '#a5a6ff', '#c3c4ff', '#4f52d9', '#3a3db3', '#dcdcff', '#2b2d8a'],
                legend: { position: 'bottom', labels: { colors: textoEje } },
                stroke: { width: 4, colors: [oscuro ? '#2b2c40' : '#fff'] },
                dataLabels: { formatter: (v) => `${v.toFixed(1)}%` },
                plotOptions: {
                    pie: {
                        donut: {
                            labels: {
                                show: true,
                                total: { show: true, label: 'Total', formatter: (w) => pesos(w.globals.seriesTotals.reduce((a, b) => a + b, 0)) },
                                value: { formatter: (v) => pesos(Number(v)) }
                            }
                        }
                    }
                }
            });
            hcCharts.medios.render();
        } else {
            $('#hc-grafico-medios').html(hcVacio('fa-chart-pie', '', 'Sin recaudo en el periodo'));
        }

        // Cajero vs sistema por grupo
        const suma = (g, k) => hc.data.reduce((s, d) => s + d.grupos.find((x) => x.key === g)[k], 0);
        if (hcCharts.comparativa) { hcCharts.comparativa.destroy(); hcCharts.comparativa = null; }
        hcCharts.comparativa = new ApexCharts(document.querySelector('#hc-grafico-comparativa'), {
            ...base,
            chart: { ...base.chart, type: 'bar', height: 300 },
            series: [
                { name: 'Cajero', data: HC_GRUPOS.map((g) => Math.round(suma(g, 'registrado'))) },
                { name: 'Sistema', data: HC_GRUPOS.map((g) => Math.round(suma(g, 'sistema'))) }
            ],
            xaxis: {
                categories: HC_GRUPOS.map((g) => (g === 'bancos' ? 'Bancos' : HC_GRUPO_INFO[g].txt)),
                labels: { style: { colors: textoEje, fontSize: '13px' } },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: { labels: { style: { colors: textoEje, fontSize: '13px' }, formatter: (v) => (Math.abs(v) >= 1e6 ? `$${(v / 1e6).toFixed(1)} M` : `$${miles(v)}`) } },
            colors: ['#c3c4ff', '#696cff'],
            plotOptions: { bar: { columnWidth: '45%', borderRadius: 8 } },
            dataLabels: { enabled: false },
            legend: { position: 'top', horizontalAlign: 'left', labels: { colors: textoEje } }
        });
        hcCharts.comparativa.render();
    };

    const hcConteos = () => {
        const c = { todos: hc.data.length, revisar: 0, faltante: 0, sobrante: 0, cruzado: 0, ok: 0 };
        hc.data.forEach((d) => { c[d.estado] += 1; if (d.estado !== 'ok') c.revisar += 1; });
        Object.entries(c).forEach(([k, v]) => {
            $(`#hc-chips [data-count="${k}"]`).text(v);
            if (k !== 'todos') $(`#hc-chips [data-filtro="${k}"]`).toggleClass('d-none', v === 0 && hc.filtro !== k);
        });
        $('#hc-chips .hc-chip').removeClass('active').filter(`[data-filtro="${hc.filtro}"]`).addClass('active');
    };

    const hcRenderLista = () => {
        const data = hcFiltrados();
        $('#hc-resultado-txt').text(`${data.length} de ${hc.data.length} cierre(s)`);
        $('#hc-lista-total').text(`${data.length} de ${hc.data.length} cierre(s)`);
        if (!data.length) {
            $('#hc-lista').html('');
            $('#hc-lista-wrap').addClass('d-none');
            $('#hc-vacio').removeClass('d-none').html(hcVacio(
                'fa-filter-circle-xmark',
                'No hay cierres con este filtro',
                '',
                '<button type="button" class="btn btn-sm btn-link" id="hc-ver-todo">Ver todos</button>'
            ));
            return;
        }
        $('#hc-vacio').addClass('d-none').html('');
        $('#hc-lista-wrap').removeClass('d-none');
        $('#hc-lista').html(data.map(hcItem).join(''));
    };

    const hcVerTodo = () => {
        hc.filtro = 'todos';
        hc.busqueda = '';
        $('#hc-buscar').val('');
        hcConteos();
        hcRenderLista();
    };

    const hcVacio = (icono, titulo, texto = '', extra = '') => `
        <div class="hc-empty">
            <span class="hc-empty-icon"><i class="fa-solid ${icono}"></i></span>
            ${titulo ? `<div class="hc-empty-titulo">${titulo}</div>` : ''}
            ${texto ? `<div class="small">${texto}</div>` : ''}
            ${extra}
        </div>`;

    const hcSinDatos = (html) => {
        hc.data = [];
        $('#hc-kpis, #hc-lista').html('');
        $('#hc-toolbar, #hc-aviso-base, #hc-graficos, #hc-lista-wrap').addClass('d-none');
        $('#hc-vacio').removeClass('d-none').html(html);
    };

    /* ---------- Consulta ---------- */
    const hcSetRango = (rango) => {
        const hoy = new Date();
        let ini = new Date(hoy);
        let fin = new Date(hoy);
        if (rango === 'ayer') { ini.setDate(hoy.getDate() - 1); fin = new Date(ini); }
        else if (rango === '7d') ini.setDate(hoy.getDate() - 6);
        else if (rango === 'mes') ini = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
        $('#hc-fecha-inicial').val(ymd(ini));
        $('#hc-fecha-final').val(ymd(fin));
        $('.hc-preset').removeClass('active').filter(`[data-rango="${rango}"]`).addClass('active');
    };

    const hcConsultar = async () => {
        const fecha_inicial = $('#hc-fecha-inicial').val();
        const fecha_final = $('#hc-fecha-final').val();
        if (!fecha_inicial || !fecha_final) {
            Swal.fire('Advertencia', 'Selecciona ambas fechas', 'warning');
            return;
        }
        if (fecha_final < fecha_inicial) {
            Swal.fire('Error', 'La fecha final no puede ser anterior a la inicial', 'error');
            return;
        }

        const $btn = $('#hc-consultar').prop('disabled', true);
        cargando('Consultando cierres');
        try {
            const resp = await enviarPeticion({ link: LINK, op: 'listar-cierres', fecha_inicial, fecha_final });
            listo();
            hc.cargado = true;
            hc.desactualizado = false;
            hc.movCache.clear();

            if (!resp?.ok) {
                hcSinDatos(`<div class="alert alert-danger small">${esc(resp?.message || 'No fue posible consultar el historial')}</div>`);
                return;
            }

            hc.bodega = resp.bodega?.nombre || '';
            $('#hc-bodega-nombre').text(hc.bodega || 'Bodega de la sesión');

            if (!resp.cierres?.length) {
                hcSinDatos(hcVacio(
                    'fa-cash-register',
                    'No hay cierres en este periodo',
                    `Bodega ${esc(hc.bodega)}. Prueba con otro rango de fechas.`
                ));
                return;
            }

            hc.data = hcEnriquecer(resp.cierres);
            hcRenderKpis();
            hcRenderGraficos();
            $('#hc-toolbar').removeClass('d-none');
            hcConteos();
            hcRenderLista();
        } catch (e) {
            console.error('Error consultando el historial:', e);
            listo();
            hcSinDatos('<div class="alert alert-danger small">Error interno de la aplicación</div>');
        } finally {
            $btn.prop('disabled', false);
        }
    };

    /* ---------- Impresión ---------- */
    const imprimirCierre = (idApertura) => {
        $('#modalDoc').remove();
        $('body').append(`
            <div class="modal fade" id="modalDoc" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-fullscreen-sm-down" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Cierre de caja #${esc(idApertura)}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body p-0">
                            <iframe width="100%" style="height:75vh; border:none;" src="../pdf/cierre_caja.php?id=${encodeURIComponent(idApertura)}"></iframe>
                        </div>
                    </div>
                </div>
            </div>`);
        $('#modalDoc').modal('show');
    };

    /* ---------- Detalle ---------- */
    const hcValorMov = (r, tab) => ((tab === 'ventas' || tab === 'devoluciones') && r.valor_pagado !== undefined
        ? num(r.valor_pagado) : num(r.valor_total));

    const hcHtmlMov = (lista, tab) => {
        const def = HC_TABS.find((t) => t.key === tab);
        if (!lista.length) return hcVacio(def.icon, '', def.vacio);

        const total = lista.reduce((s, r) => s + hcValorMov(r, tab), 0);
        const porMedio = {};
        lista.forEach((r) => (r.medios || []).forEach((m) => {
            const k = labelMedio(m.medio);
            porMedio[k] = (porMedio[k] || 0) + num(m.valor);
        }));
        const chip = (nombre, valor) => `<span class="hc-medio-chip hc-g-${grupoMedio(nombre)}">${esc(nombre)} ${pesos(valor)}</span>`;

        return `
            <div class="hc-mov-resumen">
                <div><span>${lista.length} documento(s)</span><strong class="text-${def.cls}">${pesos(total)}</strong></div>
                <div class="hc-mov-medios mt-1">${Object.entries(porMedio).sort((a, b) => b[1] - a[1]).map(([k, v]) => chip(k, v)).join('')}</div>
            </div>
            <div>${lista.map((r) => {
                const f = parseFecha(r.fecha_hora);
                const nit = r.nit && String(r.nit) !== '0' ? `NIT ${esc(r.nit)}` : '';
                const ref = r.documento ? `Ref. ${esc(r.documento)}` : '';
                return `
                <div class="hc-mov">
                    <div class="d-flex justify-content-between gap-2">
                        <span class="fw-semibold hc-strong text-truncate">${esc(r.tipo || '')} ${esc(r.numero ?? r.id)}</span>
                        <span class="fw-bold text-${def.cls} text-nowrap">${pesos(hcValorMov(r, tab))}</span>
                    </div>
                    <div class="small text-muted text-truncate">
                        <i class="fa-regular fa-clock me-1"></i>${f ? f.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' }) : ''}${[nit, ref].filter(Boolean).map((x) => ` · ${x}`).join('')}
                    </div>
                    ${String(r.notas ?? '').trim() ? `<div class="small hc-mov-det">${esc(r.notas)}</div>` : ''}
                    ${(r.medios || []).length ? `<div class="hc-mov-medios">${r.medios.map((m) => chip(labelMedio(m.medio), m.valor)).join('')}</div>` : ''}
                </div>`;
            }).join('')}</div>`;
    };

    const hcCargarMovimientos = async (d) => {
        const pintar = (mov) => HC_TABS.forEach((t) => {
            const lista = mov[t.key] || [];
            $(`#hc-tabcount-${t.key}`).text(lista.length);
            $(`#hc-pane-${t.key}`).html(hcHtmlMov(lista, t.key));
        });

        if (hc.movCache.has(String(d.id))) {
            pintar(hc.movCache.get(String(d.id)));
            return;
        }
        cargando('Cargando movimientos');
        try {
            const resp = await enviarPeticion({ link: LINK, op: 'detalle-cierre', id_apertura: d.id });
            listo();
            if (!hc.actual || String(hc.actual.id) !== String(d.id)) return;
            if (!resp?.ok) {
                HC_TABS.forEach((t) => $(`#hc-pane-${t.key}`).html(`<div class="alert alert-danger small py-2 m-0">${esc(resp?.message || 'No fue posible cargar los movimientos')}</div>`));
                return;
            }
            hc.movCache.set(String(d.id), resp);
            pintar(resp);
        } catch (e) {
            console.error('Error cargando movimientos:', e);
            listo();
            HC_TABS.forEach((t) => $(`#hc-pane-${t.key}`).html('<div class="alert alert-danger small py-2 m-0">Error interno de la aplicación</div>'));
        }
    };

    const hcMostrarTab = (tab) => {
        const btn = document.querySelector(`#hc-detalle-tabs [data-bs-target="#hc-pane-${tab}"]`);
        if (btn) bootstrap.Tab.getOrCreateInstance(btn).show();
    };

    const hcExplicacion = (d) => {
        if (d.estado === 'faltante') return `El cajero registró <strong>${pesos(Math.abs(d.dif))}</strong> menos de lo que calcula el sistema.`;
        if (d.estado === 'sobrante') return `El cajero registró <strong>${pesos(d.dif)}</strong> más de lo que calcula el sistema.`;
        if (d.estado === 'cruzado') {
            const g = d.grupos.filter((x) => Math.abs(x.dif) > HC_CFG.tolerancia).map((x) => x.txt.toLowerCase());
            return `El total cuadra, pero hay diferencias entre ${g.join(' y ')}. Posiblemente un pago se registró en otro medio.`;
        }
        return '';
    };

    const hcAbrirDetalle = (id, tab = 'resumen') => {
        const d = hc.data.find((x) => String(x.id) === String(id));
        if (!d) return;
        hc.actual = d;
        const e = HC_ESTADOS[d.estado];
        const gEf = d.grupos.find((g) => g.key === 'efectivo');

        const filasMedios = HC_GRUPOS.map((g) => {
            const lista = d.medios.filter((m) => m.grupo === g && m.valor !== 0);
            if (!lista.length) return '';
            return `<tr class="hc-tr-grupo"><td colspan="2">${HC_GRUPO_INFO[g].txt}</td></tr>` +
                lista.map((m) => `<tr><td class="ps-3">${esc(m.label)}</td><td class="text-end">${pesos(m.valor)}</td></tr>`).join('');
        }).join('');

        const resumen = `
            ${d.estado !== 'ok' ? `<div class="alert alert-${e.cls} py-2 small mb-3"><i class="fa-solid ${e.icon} me-1"></i>${hcExplicacion(d)}</div>` : ''}
            <div class="hc-total hc-dif-${difClase(d.dif)} mb-3">
                <div><div class="hc-label">Diferencia total</div><div class="hc-big">${pesosDif(d.dif)}</div></div>
                <div class="text-end small"><div>Registró <strong>${pesos(d.totalRegistrado)}</strong></div><div>Sistema <strong>${pesos(d.totalSistema)}</strong></div></div>
            </div>

            <h6 class="mb-2">Registrado vs sistema</h6>
            <div class="table-responsive mb-3">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th></th><th class="text-end">Cajero</th><th class="text-end">Sistema</th><th class="text-end">Diferencia</th></tr></thead>
                    <tbody>${d.grupos.map((g) => `
                        <tr>
                            <td><i class="fa-solid ${g.icon} me-2 text-primary"></i>${g.txt}</td>
                            <td class="text-end">${pesos(g.registrado)}</td>
                            <td class="text-end">${pesos(g.sistema)}</td>
                            <td class="text-end"><span class="hc-pill hc-dif-${difClase(g.dif)}">${pesosDif(g.dif)}</span></td>
                        </tr>`).join('')}</tbody>
                </table>
            </div>

            <h6 class="mb-2">¿Cómo se calcula el efectivo esperado?</h6>
            <div class="hc-calc small mb-3">
                <div><a href="#" class="hc-ir-tab" data-tab="ventas">Ventas en efectivo <i class="fa-solid fa-chevron-right small"></i></a><span>${pesos(d.ventas.efectivo)}</span></div>
                <div><a href="#" class="hc-ir-tab" data-tab="ingresos">+ Ingresos de caja <i class="fa-solid fa-chevron-right small"></i></a><span>${pesos(d.ingresos)}</span></div>
                <div><a href="#" class="hc-ir-tab" data-tab="egresos">− Egresos de caja <i class="fa-solid fa-chevron-right small"></i></a><span>${pesos(d.egresos)}</span></div>
                <div class="border-top pt-2"><strong>= Efectivo esperado</strong><strong class="hc-strong">${pesos(gEf.sistema)}</strong></div>
                <div><span>Efectivo registrado por el cajero</span><strong class="hc-strong">${pesos(gEf.registrado)}</strong></div>
            </div>
            <div class="alert alert-primary py-2 small mb-3">
                <i class="fa-solid fa-circle-info me-1"></i><strong>Base de caja: ${pesos(d.base)}.</strong>
                Es un valor fijo asignado al abrir la caja; se muestra como referencia y no hace parte del cálculo de diferencias.
            </div>

            <h6 class="mb-2">Medios de pago según el sistema</h6>
            <div class="table-responsive mb-3"><table class="table table-sm mb-0"><tbody>${filasMedios || '<tr><td class="text-muted">Sin ventas registradas</td></tr>'}</tbody></table></div>

            <h6 class="mb-1">Observaciones del cajero</h6>
            <div class="small p-2 rounded hc-obs-box">${d.observaciones ? esc(d.observaciones) : '<span class="text-muted">Sin observaciones</span>'}</div>`;

        const tabs = [{ key: 'resumen', txt: 'Resumen', icon: 'fa-scale-balanced' }, ...HC_TABS];

        $('#hc-detalle-titulo').html(`
            <span class="d-block">${esc(d.nombres || d.usuario)} <span class="badge bg-label-${e.cls} ms-1 align-middle">${e.txt}</span></span>
            <small class="text-muted fw-normal">Cierre #${esc(d.id)} · ${esc(hc.bodega)} · ${fmtCorta(d.apertura)} → ${fmtCorta(d.cierre)}</small>`);

        $('#hc-detalle-body').html(`
            <ul class="nav nav-pills hc-tabs mb-3" id="hc-detalle-tabs" role="tablist">
                ${tabs.map((t) => `
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link ${t.key === 'resumen' ? 'active' : ''}" data-bs-toggle="pill" data-bs-target="#hc-pane-${t.key}" role="tab">
                            <i class="fa-solid ${t.icon} me-1"></i>${t.txt}
                            ${t.key !== 'resumen' ? `<span class="badge rounded-pill bg-label-${t.cls} ms-1" id="hc-tabcount-${t.key}">…</span>` : ''}
                        </button>
                    </li>`).join('')}
            </ul>
            <div class="tab-content p-0">
                <div class="tab-pane fade show active" id="hc-pane-resumen" role="tabpanel">${resumen}</div>
                ${HC_TABS.map((t) => `<div class="tab-pane fade" id="hc-pane-${t.key}" role="tabpanel"></div>`).join('')}
            </div>`);

        $('#hc-detalle-editar').toggleClass('d-none', !permisos?.EditarCierreCaja);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('hc-modal-detalle')).show();
        if (tab !== 'resumen') hcMostrarTab(tab);
        hcCargarMovimientos(d);
    };

    const bindHistorial = () => {
        $('.hc-preset').on('click', function () {
            hcSetRango($(this).data('rango'));
            hcConsultar();
        });
        $('#hc-fecha-inicial, #hc-fecha-final').on('change', () => $('.hc-preset').removeClass('active'));
        $('#hc-consultar').on('click', hcConsultar);

        $('#hc-buscar').on('input', function () {
            hc.busqueda = $(this).val();
            hcRenderLista();
        });
        $('#hc-orden').on('change', function () {
            hc.orden = $(this).val();
            hcRenderLista();
        });
        $('#hc-chips').on('click', '.hc-chip', function () {
            const f = $(this).data('filtro');
            if (f === 'todos') { hcVerTodo(); return; }
            hc.filtro = f;
            hcConteos();
            hcRenderLista();
        });
        $('#hc-kpis').on('click keydown', '[data-accion]', function (e) {
            if (e.type === 'keydown' && e.key !== 'Enter') return;
            const a = $(this).data('accion');
            if (a === 'todos') hcVerTodo();
            else { hc.filtro = a; hcConteos(); hcRenderLista(); }
        });
        $('#hc-vacio').on('click', '#hc-ver-todo', hcVerTodo);

        $('#hc-lista').on('click keydown', '.hc-item', function (e) {
            if (e.type === 'keydown' && e.key !== 'Enter') return;
            const id = $(this).data('id');
            if ($(e.target).closest('.hc-btn-print').length) {
                e.stopPropagation();
                imprimirCierre(id);
                return;
            }
            if ($(e.target).closest('.hc-btn-editar').length) {
                e.stopPropagation();
                abrirEditar(id);
                return;
            }
            const link = $(e.target).closest('.hc-link-tab');
            if (link.length) e.preventDefault();
            hcAbrirDetalle(id, link.length ? link.data('tab') : 'resumen');
        });

        $('#hc-detalle-body').on('click', '.hc-ir-tab', function (e) {
            e.preventDefault();
            hcMostrarTab($(this).data('tab'));
        });
        $('#hc-detalle-print').on('click', () => {
            if (!hc.actual) return;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('hc-modal-detalle')).hide();
            imprimirCierre(hc.actual.id);
        });
        $('#hc-detalle-editar').on('click', () => {
            if (!hc.actual) return;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('hc-modal-detalle')).hide();
            abrirEditar(hc.actual.id);
        });

        // Se consulta la primera vez que se abre la pestaña, o si hubo un cierre/edición desde entonces
        $('#ac-tab-historial-btn').on('shown.bs.tab', () => {
            if (!hc.cargado || hc.desactualizado) hcConsultar();
        });

        hcSetRango('hoy');
    };

    /* ------------------------------------------------------------------
     * Inicio
     * ------------------------------------------------------------------ */
    $(async function () {
        permisos = await permisosUsuario(id_modulo);
        const {
            general,
            modulos
        } = await cargarConfiguraciones();
        ConfGeneral = general;
        ConfModulos = modulos[id_modulo];

        bindCamposDinero();
        bindContador();

        $('#ac-btn-abrir').on('click', abrirCaja);
        $('#ac-btn-cerrar').on('click', cerrarCaja);
        $('#ac-refrescar').on('click', cargarEstado);
        $('#ac-base').on('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); $('#ac-base').trigger('blur'); abrirCaja(); } });

        // Enter en los valores del cierre pasa al siguiente campo
        $('.ac-cierre-valor').on('keydown', function (e) {
            if (e.key !== 'Enter') return;
            e.preventDefault();
            const orden = ['ac-efectivo', 'ac-tarjetas', 'ac-otros', 'ac-observaciones'];
            const i = orden.indexOf(this.id);
            $(`#${orden[i + 1]}`).trigger('focus');
        });

        $('#ac-imprimir-ultimo').on('click', () => {
            if (estado?.id_ultimo_cierre) imprimirCierre(estado.id_ultimo_cierre);
        });

        $('#ac-editar-guardar').on('click', guardarEdicion);
        $('#ac-editar-obs').on('input', function () { $(this).removeClass('is-invalid'); });

        bindHistorial();

        await cargarEstado();
        dissminSwal();
    });
})();