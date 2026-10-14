const LINK_NOMINA = '../models/nomina.php';
const LINK_NOMINA_CONTABILIZACION = '../models/nominaContabilizacion.php';
const LINK_NOMINA_COLILLA = '../models/nominaColilla.php';
const LINK_NOMINA_EGRESO = '../models/nominaEgreso.php';
const LINK_DIAN_NOMINA = '../models/Fe/Controllers/ProcesarNomina.php';
const URL_API_FE = 'https://www.thecyctechnology.com/api-fe/public/index.php/dian-service/getDocumentFile';

// Estado de la vista
const estadoNomina = {
    periodo: null,      // objeto período devuelto por el servidor
    empleados: []       // filas de la tabla
};

const ETIQUETAS_SISTEMA = {
    salario_basico: 'Salario básico',
    aux_transporte: 'Auxilio de transporte',
    aporte_salud: 'Aporte salud',
    aporte_pension: 'Aporte pensión',
    aporte_fsp_solidaridad: 'Fondo de solidaridad pensional',
    aporte_fsp_subsistencia: 'Fondo de subsistencia',
    retencion_fuente: 'Retención en la fuente'
};

const ESTADOS = {
    CALCULAR: { clase: 'bg-label-secondary', texto: 'Por calcular' },
    BORRADOR: { clase: 'bg-label-warning', texto: 'Borrador' },
    CONTABILIZADO: { clase: 'bg-label-info', texto: 'Contabilizado' },
    EMITIDO: { clase: 'bg-label-success', texto: 'Emitido DIAN' }
};

// ---------------------------------------------------------------------------
// Utilidades
// ---------------------------------------------------------------------------
const escapeHtml = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

const notificarError = (mensaje) => Swal.fire('Error', mensaje || 'Se generó un error interno de la aplicación', 'error');
const notificarExito = (mensaje) => Swal.fire({ icon: 'success', title: 'Listo', text: mensaje, timer: 1800, showConfirmButton: false });

const formatearMoneda = (valor) =>
    new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(valor || 0);

const listaHtml = (titulo, items, clase = 'text-danger') => (items && items.length)
    ? `<div class="text-start mt-2"><small class="fw-semibold">${titulo}</small>
         <ul class="small ${clase} mb-0 ps-3" style="max-height:220px;overflow:auto">${items.map((e) => `<li>${escapeHtml(e)}</li>`).join('')}</ul></div>`
    : '';

const cargando = (texto) => showLoadingWithTetrisLogo(texto, 'ISA Software');

function cerrarDropdown(el) {
    const toggle = $(el).closest('.dropdown').find('[data-bs-toggle="dropdown"]')[0];
    if (toggle) {
        (bootstrap.Dropdown.getInstance(toggle) || new bootstrap.Dropdown(toggle)).hide();
    }
}

const periodoSeleccionado = () => $('#slcPeriodoLiquidacion').val();

const seleccionados = (filtro = () => true) => $('.chkEmpleado:checked').map(function () {
    return estadoNomina.empleados[Number($(this).data('idx'))];
}).get().filter(filtro);

// Abre las colillas en una pestaña nueva (POST para no exponer ids en la URL)
const abrirColillas = ({ ids = [], periodo = '' } = {}) => {
    const form = $('<form>', { method: 'POST', action: LINK_NOMINA_COLILLA, target: '_blank' });
    if (ids.length) form.append($('<input>', { type: 'hidden', name: 'ids', value: ids.join(',') }));
    if (periodo) form.append($('<input>', { type: 'hidden', name: 'periodo', value: periodo }));
    $('body').append(form);
    form.trigger('submit').remove();
};

// ---------------------------------------------------------------------------
// Años y períodos
// ---------------------------------------------------------------------------
const generarOpcionesAnios = () => {
    const actual = new Date().getFullYear();
    let html = '<option value="">Seleccione año...</option>';
    for (let i = actual - 2; i <= actual + 1; i++) {
        html += `<option value="${i}" ${i === actual ? 'selected' : ''}>${i}</option>`;
    }
    $('#slcAnioLiquidacion').html(html);
    cargarPeriodosLiquidacion(actual);
};

const cargarPeriodosLiquidacion = async (anio) => {
    try {
        const resp = await enviarPeticion({ link: LINK_NOMINA, op: 'combos_periodos', anio });
        let html = '<option value="">Seleccione período...</option>';
        const hoy = new Date().toISOString().slice(0, 10);
        let sugerido = '';
        (resp.data || []).forEach((p) => {
            if (p.fecha_inicio <= hoy && p.fecha_fin >= hoy) sugerido = p.id;
            html += `<option value="${p.id}" data-inicio="${p.fecha_inicio}" data-fin="${p.fecha_fin}">${p.label}</option>`;
        });
        $('#slcPeriodoLiquidacion').html(html);
        if (sugerido) {
            $('#slcPeriodoLiquidacion').val(sugerido).trigger('change');
        }
    } catch (e) {
        console.error(e);
        notificarError('No se pudieron cargar los períodos');
    }
};

// ---------------------------------------------------------------------------
// Tabla de empleados
// ---------------------------------------------------------------------------
const filaMensaje = (texto, clase = 'text-muted') =>
    `<tr><td colspan="9" class="text-center ${clase} py-4"><small>${texto}</small></td></tr>`;

const cargarEmpleadosPeriodo = async (periodo = periodoSeleccionado()) => {
    $('#chkSeleccionarTodos').prop('checked', false);
    if (!periodo) {
        estadoNomina.empleados = [];
        $('#cuerpoTablaEmpleados').html(filaMensaje('Seleccione un período para ver los empleados'));
        limpiarResumen();
        return;
    }
    $('#cuerpoTablaEmpleados').html(filaMensaje('Cargando empleados...'));
    try {
        const resp = await enviarPeticion({ link: LINK_NOMINA, op: 'listar_periodo', periodo });
        if (resp.error) {
            throw new Error(resp.message);
        }
        estadoNomina.periodo = resp.data.periodo;
        estadoNomina.empleados = resp.data.empleados || [];
        if (!estadoNomina.empleados.length) {
            $('#cuerpoTablaEmpleados').html(filaMensaje('No hay empleados con contrato vigente en este período'));
            limpiarResumen();
            return;
        }
        renderizarTablaEmpleados();
        calcularTotalesPeriodo();
    } catch (e) {
        console.error(e);
        $('#cuerpoTablaEmpleados').html(filaMensaje('Error al cargar empleados', 'text-danger'));
        notificarError(e.message || 'No se pudieron cargar los empleados del período');
    }
};

const itemMenu = (accion, icono, texto, habilitado, titulo = '') =>
    `<li><a class="dropdown-item ${habilitado ? '' : 'disabled'}" href="javascript:void(0);" data-accion="${accion}" ${titulo ? `title="${escapeHtml(titulo)}"` : ''}>
        <i class="fa ${icono} me-2"></i>${texto}</a></li>`;

const renderizarTablaEmpleados = () => {
    const html = estadoNomina.empleados.map((emp, idx) => {
        const estado = String(emp.estado_emision || 'CALCULAR').toUpperCase();
        const cfg = ESTADOS[estado] || { clase: 'bg-label-dark', texto: estado };
        const ingresos = parseFloat(emp.devengados_total || 0);
        const deducciones = parseFloat(emp.deducciones_total || 0);
        const tieneNomina = !!emp.id_nomina;
        const contabilizado = estado === 'CONTABILIZADO' || estado === 'EMITIDO';

        return `<tr data-idx="${idx}">
            <td class="ps-3"><input type="checkbox" class="form-check-input chkEmpleado" data-idx="${idx}"></td>
            <td>${escapeHtml(emp.prefijo)}</td>
            <td>${escapeHtml(emp.consecutivo)}</td>
            <td>
                <strong>${escapeHtml(emp.empleado)}</strong>
                ${Number(emp.tiene_perfil) ? '' : '<i class="fa fa-exclamation-triangle text-warning ms-1" title="Contrato sin perfil contable"></i>'}
                <br><small class="text-muted">${escapeHtml(emp.empleado_documento)}${emp.cargo ? ' · ' + escapeHtml(emp.cargo) : ''}</small>
            </td>
            <td class="text-end">${formatearMoneda(ingresos)}<br><small class="text-muted">${emp.cantidad_novedades_devengados || 0} conceptos</small></td>
            <td class="text-end">${formatearMoneda(deducciones)}<br><small class="text-muted">${emp.cantidad_novedades_deducciones || 0} conceptos</small></td>
            <td class="text-end fw-bold">${formatearMoneda(ingresos - deducciones)}</td>
            <td class="text-center"><span class="badge ${cfg.clase}">${cfg.texto}</span>
                ${emp.egreso_numero ? `<br><small class="text-primary" title="Pagada con egreso"><i class="fa fa-check-circle me-1"></i>${escapeHtml(emp.egreso_tipo)} ${escapeHtml(emp.egreso_numero)}</small>` : ''}</td>
            <td class="text-center pe-3">
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport">
                        <i class="fa fa-cogs me-1"></i> Acciones
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        ${itemMenu('detalle', 'fa-eye', 'Ver detalle', tieneNomina, tieneNomina ? '' : 'Calcule la nómina primero')}
                        ${itemMenu('colilla', 'fa-receipt text-primary', 'Colilla de pago', contabilizado, contabilizado ? '' : 'Disponible al contabilizar')}
                        ${itemMenu('dian', 'fa-paper-plane', 'Emitir DIAN', estado === 'CONTABILIZADO', 'La nómina debe estar contabilizada')}
                        ${itemMenu('eliminar', 'fa-trash text-danger', 'Eliminar', estado === 'BORRADOR')}
                        <li><hr class="dropdown-divider"></li>
                        ${itemMenu('pdf', 'fa-file-pdf text-danger', 'PDF DIAN', estado === 'EMITIDO')}
                        ${itemMenu('xml', 'fa-file-code text-success', 'XML DIAN', estado === 'EMITIDO')}
                        ${itemMenu('info', 'fa-info-circle text-primary', 'Información DIAN', estado === 'EMITIDO')}
                    </ul>
                </div>
            </td>
        </tr>`;
    }).join('');
    $('#cuerpoTablaEmpleados').html(html);
    aplicarFiltroEmpleados();
};

const calcularTotalesPeriodo = () => {
    let ingresos = 0;
    let deducciones = 0;
    estadoNomina.empleados.forEach((e) => {
        ingresos += parseFloat(e.devengados_total || 0);
        deducciones += parseFloat(e.deducciones_total || 0);
    });
    $('#lblEmpleados').text(estadoNomina.empleados.length);
    $('#lblIngresosAdicionales').text(formatearMoneda(ingresos));
    $('#lblDeducciones').text(formatearMoneda(deducciones));
    $('#lblNetoPagar').text(formatearMoneda(ingresos - deducciones));
    $('#lblPeriodoActual').text(estadoNomina.periodo?.label || $('#slcPeriodoLiquidacion option:selected').text());
};

const limpiarResumen = () => {
    $('#lblEmpleados').text('0');
    $('#lblIngresosAdicionales, #lblDeducciones, #lblNetoPagar').text(formatearMoneda(0));
    $('#lblPeriodoActual').text('Seleccione un período');
};

const aplicarFiltroEmpleados = () => {
    const filtro = ($('#filtroEmpleados').val() || '').toLowerCase();
    $('#cuerpoTablaEmpleados tr').each(function () {
        $(this).toggle($(this).text().toLowerCase().includes(filtro));
    });
};

// ---------------------------------------------------------------------------
// Estado DIAN de la empresa
// ---------------------------------------------------------------------------
const statusDian = async () => {
    const $lbl = $('#lblStatusDian');
    try {
        const resp = await enviarPeticion({ link: LINK_NOMINA, op: 'obtener_estado_dian' });
        const status = parseInt(resp.data, 10);
        if (!resp.error && status === 1) {
            $lbl.attr('class', 'badge bg-label-success').html('<i class="fa fa-check-circle me-1"></i> PRODUCCIÓN DIAN');
        } else if (!resp.error && status === 2) {
            $lbl.attr('class', 'badge bg-label-warning').html('<i class="fa fa-flask me-1"></i> PRUEBAS DIAN');
        } else {
            $lbl.attr('class', 'badge bg-label-secondary').text(resp.error ? 'SIN CONFIGURACIÓN DIAN' : 'DESCONOCIDO');
        }
    } catch (e) {
        console.error(e);
        $lbl.attr('class', 'badge bg-label-danger').text('ERROR DIAN');
    }
};

// ---------------------------------------------------------------------------
// Detalle de una nómina
// ---------------------------------------------------------------------------
const mostrarDetallesNomina = async (emp) => {
    if (!emp?.id_nomina) {
        notificarError('La nómina de este empleado aún no se ha calculado');
        return;
    }
    cargando('Consultando detalle...');
    let resp;
    try {
        resp = await enviarPeticion({ link: LINK_NOMINA, op: 'obtener_detalles', id_nomina: emp.id_nomina });
    } finally {
        dissminSwal();
    }
    if (resp.error || !resp.data) {
        notificarError(resp.message || 'No se encontraron detalles');
        return;
    }

    const estado = String(resp.data.estado_emision || '').toUpperCase();
    const esBorrador = estado === 'BORRADOR';
    const cfg = ESTADOS[estado] || { clase: 'bg-label-dark', texto: estado };
    const detalles = resp.data.detalles || [];
    const lock = esBorrador ? '' : 'disabled';

    const filas = detalles.map((d) => {
        const nombre = ETIQUETAS_SISTEMA[d.concepto] || d.concepto_nombre || d.concepto;
        const cantidad = parseFloat(d.cantidad || 0);
        return `<tr data-id="${d.id}" data-tipo="${d.tipo}">
            <td>${escapeHtml(nombre)}<br><small class="text-muted">${escapeHtml(d.concepto)}</small></td>
            <td><span class="badge ${d.tipo === 'DEVENGADO' ? 'bg-label-success' : 'bg-label-danger'}">${d.tipo}</span></td>
            <td class="text-end">${cantidad ? cantidad.toLocaleString('es-CO') : ''}</td>
            <td><input type="number" step="0.01" min="0" class="form-control form-control-sm valor-detalle text-end" value="${parseFloat(d.total || 0)}" ${lock}></td>
            <td class="text-nowrap">
                <button class="btn btn-sm btn-icon btn-outline-primary btn-editar-detalle" ${lock} title="Guardar"><i class="fas fa-save"></i></button>
                <button class="btn btn-sm btn-icon btn-outline-danger btn-eliminar-detalle" ${lock} title="Eliminar"><i class="fas fa-trash"></i></button>
            </td>
        </tr>`;
    }).join('');

    const body = `
        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="fw-semibold mb-0 small">Notas</label>
            <span class="badge ${cfg.clase}">${cfg.texto}</span>
        </div>
        <textarea id="txtNotaNomina" class="form-control form-control-sm" rows="2" maxlength="500"
            placeholder="Observaciones de la nómina (se imprimen en la colilla)..." ${lock}>${escapeHtml(resp.data.nomina?.observaciones || '')}</textarea>
        <div class="text-end mt-2">
            <button class="btn btn-sm btn-primary" id="btnGuardarNotaNomina" ${lock}><i class="fas fa-save me-1"></i>Guardar nota</button>
        </div>
        ${esBorrador ? '' : `<div class="alert alert-warning small py-2 mt-3 mb-0"><i class="fas fa-lock me-1"></i>
            La nómina está <strong>${cfg.texto}</strong>; el detalle no puede modificarse.</div>`}
        <div class="table-responsive mt-3">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-primary"><tr><th>Concepto</th><th>Tipo</th><th class="text-end">Cant.</th><th width="170">Valor</th><th width="90"></th></tr></thead>
                <tbody>${filas}</tbody>
            </table>
        </div>
        <div class="row g-2 mt-2">
            <div class="col-md-4"><div class="border rounded p-2"><small class="text-muted">Devengados</small><div class="fw-bold" id="totalDevengos"></div></div></div>
            <div class="col-md-4"><div class="border rounded p-2"><small class="text-muted">Deducciones</small><div class="fw-bold" id="totalDeducciones"></div></div></div>
            <div class="col-md-4"><div class="border border-primary rounded p-2"><small class="text-primary">Neto a pagar</small><div class="fw-bold text-primary" id="totalNetoDetalle"></div></div></div>
        </div>`;

    $('#tituloModalNomina').text(`Detalle · ${emp.empleado}`);
    $('#bodyModalNomina').html(body).data('id-nomina', emp.id_nomina);
    $('#btnColillaModal').toggleClass('d-none', !(estado === 'CONTABILIZADO' || estado === 'EMITIDO')).data('id-nomina', emp.id_nomina);
    recalcularTotalesModal();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetallesNomina')).show();
};

const recalcularTotalesModal = () => {
    let dev = 0;
    let ded = 0;
    $('#bodyModalNomina tbody tr').each(function () {
        const v = parseFloat($(this).find('.valor-detalle').val()) || 0;
        if ($(this).data('tipo') === 'DEVENGADO') dev += v; else ded += v;
    });
    $('#totalDevengos').text(formatearMoneda(dev));
    $('#totalDeducciones').text(formatearMoneda(ded));
    $('#totalNetoDetalle').text(formatearMoneda(dev - ded));
};

// ---------------------------------------------------------------------------
// Procesos
// ---------------------------------------------------------------------------
const liquidarNomina = async () => {
    const periodo = periodoSeleccionado();
    if (!periodo) {
        notificarError('Seleccione un período primero');
        return;
    }
    const marcados = seleccionados((e) => ['CALCULAR', 'BORRADOR'].includes(String(e.estado_emision).toUpperCase()));
    const alcance = marcados.length ? `${marcados.length} empleado(s) seleccionado(s)` : 'todos los empleados pendientes';

    const ok = await Swal.fire({
        title: '¿Calcular nómina?',
        html: `<div class="text-start small">
                <p class="mb-2">Período: <strong>${escapeHtml($('#slcPeriodoLiquidacion option:selected').text())}</strong></p>
                <p class="mb-2">Se calculará la nómina de <strong>${alcance}</strong>. Las nóminas en borrador se recalculan
                y se pierden los ajustes manuales del detalle.</p>
                <p class="mb-0 text-muted">Se omiten las nóminas contabilizadas o emitidas.</p></div>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, calcular',
        cancelButtonText: 'Cancelar'
    });
    if (!ok.isConfirmed) return;

    cargando('Liquidando, por favor espere...');
    try {
        const resp = await enviarPeticion({
            link: LINK_NOMINA, op: 'liquidar_masivo', periodo,
            ids: marcados.map((e) => e.id).join(',')
        });
        dissminSwal();
        await cargarEmpleadosPeriodo(periodo);
        if (resp.error) {
            notificarError(resp.message);
            return;
        }
        const d = resp.data || {};
        Swal.fire({
            icon: d.errores?.length ? 'warning' : 'success',
            title: 'Liquidación terminada',
            html: `<p class="mb-0">${escapeHtml(resp.message)}</p>${listaHtml('Errores', d.errores)}${listaHtml('Avisos', d.avisos, 'text-warning')}`
        });
    } catch (e) {
        dissminSwal();
        console.error(e);
        notificarError('Error al procesar la liquidación');
    }
};

const contabilizarNomina = async () => {
    const periodo = periodoSeleccionado();
    const idTipoDocumento = $('#slcTipoDocumentoContable').val();
    if (!periodo) {
        notificarError('Seleccione un período para contabilizar');
        return;
    }
    if (!idTipoDocumento) {
        notificarError('Seleccione el tipo de documento para realizar la causación');
        return;
    }
    const marcados = seleccionados((e) => String(e.estado_emision).toUpperCase() === 'BORRADOR');
    const borradores = estadoNomina.empleados.filter((e) => String(e.estado_emision).toUpperCase() === 'BORRADOR').length;
    if (!borradores) {
        notificarError('No hay nóminas en borrador para contabilizar en este período');
        return;
    }

    const ok = await Swal.fire({
        title: '¿Contabilizar nómina?',
        html: `<div class="text-start small">Se creará un documento de causación por empleado
               (${marcados.length || borradores} nómina(s)) con devengados, deducciones, aportes del empleador y provisiones.
               <br><br>Después podrá generar las colillas de pago.</div>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="fa fa-book me-1"></i> Sí, contabilizar',
        cancelButtonText: 'Cancelar'
    });
    if (!ok.isConfirmed) return;

    cargando('Generando documentos y asientos contables...');
    try {
        const resp = await enviarPeticion({
            link: LINK_NOMINA_CONTABILIZACION, op: 'contabilizar', periodo,
            id_tipo_documento: idTipoDocumento,
            ids: marcados.map((e) => e.id_nomina).join(',')
        });
        dissminSwal();
        if (resp.error) {
            Swal.fire({
                icon: 'error',
                title: 'No se contabilizó',
                html: `<p class="mb-0">${escapeHtml(resp.message)}</p>${listaHtml('Detalle', resp.data?.errores)}${listaHtml('Avisos', resp.data?.avisos, 'text-warning')}`
            });
            return;
        }
        await cargarEmpleadosPeriodo(periodo);
        const r = await Swal.fire({
            icon: 'success',
            title: 'Nómina contabilizada',
            html: `<p class="mb-0">${escapeHtml(resp.message)}</p>${listaHtml('Avisos', resp.data?.avisos, 'text-warning')}`,
            showCancelButton: true,
            confirmButtonText: '<i class="fa fa-receipt me-1"></i> Generar colillas',
            cancelButtonText: 'Cerrar'
        });
        if (r.isConfirmed) {
            abrirColillas({ periodo });
        }
    } catch (e) {
        dissminSwal();
        console.error(e);
        notificarError('Ocurrió un error al procesar la contabilización');
    }
};

const generarColillasPeriodo = () => {
    const periodo = periodoSeleccionado();
    if (!periodo) {
        notificarError('Seleccione un período');
        return;
    }
    const validas = (e) => ['CONTABILIZADO', 'EMITIDO'].includes(String(e.estado_emision).toUpperCase());
    const marcados = seleccionados(validas);
    if (marcados.length) {
        abrirColillas({ ids: marcados.map((e) => e.id_nomina) });
        return;
    }
    if (!estadoNomina.empleados.some(validas)) {
        notificarError('Las colillas se habilitan cuando la nómina está contabilizada');
        return;
    }
    abrirColillas({ periodo });
};

/** Envía una nómina a la DIAN. Devuelve {ok, mensaje} sin mostrar alertas. */
const enviarDian = async (idNomina) => {
    const resp = await enviarPeticion({ link: LINK_DIAN_NOMINA, id_nomina: idNomina });
    const data = resp?.data ?? {};
    if (resp && resp.ok && data.IsValid === true) {
        await enviarPeticion({ link: LINK_NOMINA, op: 'actualizar_estado', id_nomina: idNomina, nuevo_estado: 'EMITIDO' });
        return { ok: true, mensaje: data.UUID || '' };
    }
    if (resp && resp.ok && data.IsValid === false) {
        const errores = Array.isArray(data.ErrorMessage) ? data.ErrorMessage.join('; ') : '';
        return { ok: false, mensaje: `${data.StatusDescription || 'Rechazado por la DIAN'} ${errores}`.trim() };
    }
    return { ok: false, mensaje: resp?.message || 'No fue posible enviar el documento' };
};

const procesarDian = async (emp) => {
    const ok = await Swal.fire({
        title: '¿Emitir nómina electrónica?',
        text: `Se enviará la nómina de ${emp.empleado} a la DIAN.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, emitir',
        cancelButtonText: 'Cancelar'
    });
    if (!ok.isConfirmed) return;

    cargando('Procesando en DIAN, por favor espere...');
    try {
        const r = await enviarDian(emp.id_nomina);
        dissminSwal();
        if (r.ok) {
            Swal.fire({ icon: 'success', title: 'Aceptada por la DIAN', html: `<small><strong>CUNE:</strong> ${escapeHtml(r.mensaje || 'No disponible')}</small>` });
        } else {
            notificarError(r.mensaje);
        }
        await cargarEmpleadosPeriodo();
    } catch (e) {
        dissminSwal();
        notificarError(e.message || 'Error al procesar la nómina');
    }
};

// Envío masivo secuencial (el servicio DIAN procesa un documento por solicitud)
const procesarDianMasivo = async () => {
    const periodo = periodoSeleccionado();
    if (!periodo) {
        notificarError('Seleccione un período');
        return;
    }
    let pendientes = seleccionados((e) => String(e.estado_emision).toUpperCase() === 'CONTABILIZADO');
    if (!pendientes.length) {
        pendientes = estadoNomina.empleados.filter((e) => String(e.estado_emision).toUpperCase() === 'CONTABILIZADO');
    }
    if (!pendientes.length) {
        notificarError('No hay nóminas contabilizadas pendientes de emitir');
        return;
    }
    const ok = await Swal.fire({
        title: '¿Emitir a la DIAN?',
        text: `Se enviarán ${pendientes.length} nómina(s) contabilizadas.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, emitir',
        cancelButtonText: 'Cancelar'
    });
    if (!ok.isConfirmed) return;

    const errores = [];
    let aceptadas = 0;
    for (let i = 0; i < pendientes.length; i++) {
        cargando(`Enviando a DIAN ${i + 1} de ${pendientes.length}...`);
        try {
            const r = await enviarDian(pendientes[i].id_nomina);
            if (r.ok) aceptadas++; else errores.push(`${pendientes[i].empleado}: ${r.mensaje}`);
        } catch (e) {
            errores.push(`${pendientes[i].empleado}: ${e.message || 'error de comunicación'}`);
        }
    }
    dissminSwal();
    await cargarEmpleadosPeriodo(periodo);
    Swal.fire({
        icon: errores.length ? 'warning' : 'success',
        title: 'Envío DIAN terminado',
        html: `<p class="mb-0">Aceptadas: <strong>${aceptadas}</strong> · Con error: <strong>${errores.length}</strong></p>${listaHtml('Errores', errores)}`
    });
};

const eliminarNomina = async (emp) => {
    const ok = await Swal.fire({
        title: '¿Eliminar nómina?',
        text: `Se eliminará la liquidación de ${emp.empleado}.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ff3e1d',
        reverseButtons: true
    });
    if (!ok.isConfirmed) return;
    cargando('Eliminando...');
    const resp = await enviarPeticion({ link: LINK_NOMINA, op: 'eliminar_nomina', id_nomina: emp.id_nomina });
    dissminSwal();
    if (resp.error) {
        notificarError(resp.message);
        return;
    }
    notificarExito(resp.message);
    cargarEmpleadosPeriodo();
};

const mostrarInfoDian = async (emp) => {
    const resp = await enviarPeticion({ link: LINK_NOMINA, op: 'informacion_dian', tipo: emp.prefijo, numero: emp.consecutivo });
    const info = resp.data?.[0];
    if (resp.error || !info) {
        Swal.fire({ icon: 'warning', title: 'Sin información', text: 'No se encontró información DIAN para este documento.' });
        return;
    }
    const urlDian = info.QRCode ? (String(info.QRCode).match(/https?:\/\/[^\s]+/) || [])[0] : '';
    const fila = (k, v) => `<tr><th class="text-nowrap">${k}</th><td style="word-break:break-all">${escapeHtml(v || '')}</td></tr>`;
    Swal.fire({
        title: 'Información DIAN',
        width: 760,
        html: `<table class="table table-sm table-bordered text-start small"><tbody>
            ${fila('Documento', `${info.tipo} ${info.numero}`)}${fila('CUNE', info.cufe)}${fila('Archivo XML', info.XmlFileName)}
            ${fila('Fecha envío', info.fechaEnvio)}${fila('Fecha validación', info.fechaValidacion)}</tbody></table>
            ${urlDian ? `<a href="${escapeHtml(urlDian)}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fa fa-search me-1"></i>Ver en DIAN</a>` : ''}`,
        confirmButtonText: 'Cerrar'
    });
};

// ---------------------------------------------------------------------------
// Egreso de pago de nómina
// ---------------------------------------------------------------------------
let cuentasBancoCargadas = false;

const cargarCuentasBanco = async () => {
    if (cuentasBancoCargadas) return;
    const resp = await enviarPeticion({ link: '../models/cuentasContables.php', op: 'listar-todos' });
    let html = '<option value=""></option>';
    (resp.data || [])
        .filter((c) => /^11/.test(String(c.cuenta)))          // Disponible: caja y bancos
        .forEach((c) => { html += `<option value="${escapeHtml(c.cuenta)}">${escapeHtml(c.cuenta)} - ${escapeHtml(c.descripcion)}</option>`; });
    $('#slcCuentaBancoEgreso').html(html);
    if ($.fn.select2) {
        $('#slcCuentaBancoEgreso').select2({
            theme: 'bootstrap-5', dropdownParent: $('#modalEgresoNomina'), placeholder: 'Caja o banco (11xx)', allowClear: true, width: '100%'
        });
    }
    cuentasBancoCargadas = true;
};

const abrirModalEgreso = async () => {
    const periodo = periodoSeleccionado();
    if (!periodo) {
        notificarError('Seleccione un período');
        return;
    }
    const marcados = seleccionados((e) => ['CONTABILIZADO', 'EMITIDO'].includes(String(e.estado_emision).toUpperCase()) && !e.egreso_numero);
    cargando('Consultando nóminas pendientes de pago...');
    let resp;
    try {
        resp = await enviarPeticion({ link: LINK_NOMINA_EGRESO, op: 'datos_egreso', periodo, ids: marcados.map((e) => e.id_nomina).join(',') });
        await cargarCuentasBanco();
    } finally {
        dissminSwal();
    }
    if (resp.error) {
        notificarError(resp.message);
        return;
    }
    const d = resp.data;
    if (!d.cantidad) {
        notificarError('No hay nóminas contabilizadas pendientes de pago en este período');
        return;
    }
    if (!d.tipos.length) {
        notificarError('No existe un tipo de documento de egreso (sw 9) marcado como nómina. Créelo en Tipos de documento.');
        return;
    }
    $('#slcTipoDocEgreso').html(d.tipos.map((t) => `<option value="${t.id}">${escapeHtml(t.tipo)} - ${escapeHtml(t.descripcion)}</option>`).join(''));
    $('#txtFechaEgreso').val(d.fecha_sugerida).attr('max', new Date().toISOString().slice(0, 10));
    $('#lblEgresoCantidad').text(d.cantidad);
    $('#lblEgresoTotal').text(formatearMoneda(d.total));
    $('#lblEgresoAlcance').text(marcados.length ? 'empleados seleccionados' : 'todo el período');
    $('#tbodyEgreso').html(d.empleados.map((e) => `<tr>
        <td>${escapeHtml(e.empleado)}<br><small class="text-muted">${escapeHtml(e.documento)}</small></td>
        <td><small>${escapeHtml(e.pago || '—')}</small></td>
        <td class="text-end">${formatearMoneda(e.neto)}</td></tr>`).join(''));
    $('#modalEgresoNomina').data('ids', marcados.map((e) => e.id_nomina).join(','));
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEgresoNomina')).show();
};

const generarEgreso = async () => {
    const cuenta = $('#slcCuentaBancoEgreso').val();
    if (!cuenta) {
        notificarError('Seleccione la cuenta de caja o banco');
        return;
    }
    const modo = $('input[name="rdModoEgreso"]:checked').val();
    const ok = await Swal.fire({
        title: '¿Generar egreso?',
        html: `<div class="small">Se ${modo === 'TOTAL' ? 'generará <strong>un egreso</strong>' : 'generará <strong>un egreso por empleado</strong>'}
               por ${$('#lblEgresoTotal').text()} desde la cuenta <strong>${escapeHtml(cuenta)}</strong>.</div>`,
        icon: 'question', showCancelButton: true, confirmButtonText: 'Sí, generar', cancelButtonText: 'Cancelar'
    });
    if (!ok.isConfirmed) return;

    cargando('Generando egreso...');
    try {
        const resp = await enviarPeticion({
            link: LINK_NOMINA_EGRESO, op: 'generar_egreso',
            periodo: periodoSeleccionado(),
            ids: $('#modalEgresoNomina').data('ids') || '',
            id_tipo_documento: $('#slcTipoDocEgreso').val(),
            cuenta_banco: cuenta,
            fecha: $('#txtFechaEgreso').val(),
            modo
        });
        dissminSwal();
        if (resp.error) {
            Swal.fire({ icon: 'error', title: 'No se generó el egreso', html: `<p class="mb-0">${escapeHtml(resp.message)}</p>${listaHtml('Detalle', resp.data?.errores)}` });
            return;
        }
        bootstrap.Modal.getInstance(document.getElementById('modalEgresoNomina'))?.hide();
        await cargarEmpleadosPeriodo();
        Swal.fire({ icon: 'success', title: 'Egreso generado', text: resp.message });
    } catch (e) {
        dissminSwal();
        console.error(e);
        notificarError('Ocurrió un error al generar el egreso');
    }
};

const tiposDocumentosCausacion = async () => {
    const resp = await enviarPeticion({ link: LINK_NOMINA, op: 's_documentos' });
    const $sel = $('#slcTipoDocumentoContable').empty();
    const items = Array.isArray(resp.data) ? resp.data : [];
    if (items.length !== 1) {
        $sel.append('<option value="">Seleccione...</option>');
    }
    items.forEach((item) => $sel.append($('<option>', { value: item.id, text: `${item.tipo} - ${item.descripcion}`, 'data-tipo': item.tipo })));
};

// ---------------------------------------------------------------------------
// Eventos
// ---------------------------------------------------------------------------
$(async function () {
    generarOpcionesAnios();
    statusDian();
    tiposDocumentosCausacion();

    $(document).on('show.bs.dropdown', '#tablaPeriodoEmpleados .dropdown', function () {
        $(this).closest('.table-responsive').css('overflow', 'visible');
    });
    $(document).on('hide.bs.dropdown', '#tablaPeriodoEmpleados .dropdown', function () {
        $(this).closest('.table-responsive').css('overflow', 'auto');
    });

    $('#slcAnioLiquidacion').on('change', function () {
        if ($(this).val()) {
            cargarPeriodosLiquidacion($(this).val());
            cargarEmpleadosPeriodo('');
        }
    });
    $('#slcPeriodoLiquidacion').on('change', () => cargarEmpleadosPeriodo());

    $('#btnCalcularNomina').on('click', liquidarNomina);
    $('#btnContabilizar').on('click', contabilizarNomina);
    $('#btnColillasPeriodo').on('click', generarColillasPeriodo);
    $('#btnProcesarDianMasivo').on('click', procesarDianMasivo);
    $('#btnEgresoNomina').on('click', abrirModalEgreso);
    $('#btnConfirmarEgreso').on('click', generarEgreso);

    // Acciones por fila (delegadas: un solo manejador para toda la tabla)
    $('#cuerpoTablaEmpleados').on('click', '[data-accion]', function (e) {
        e.preventDefault();
        if ($(this).hasClass('disabled')) return;
        cerrarDropdown(this);
        const emp = estadoNomina.empleados[Number($(this).closest('tr').data('idx'))];
        if (!emp) return;
        switch ($(this).data('accion')) {
            case 'detalle': mostrarDetallesNomina(emp); break;
            case 'colilla': abrirColillas({ ids: [emp.id_nomina] }); break;
            case 'dian': procesarDian(emp); break;
            case 'eliminar': eliminarNomina(emp); break;
            case 'pdf': window.open(`${URL_API_FE}?ext=pdf-nomina&tipo=${encodeURIComponent(emp.prefijo)}&numero=${encodeURIComponent(emp.consecutivo)}`, '_blank'); break;
            case 'xml': window.open(`${URL_API_FE}?ext=xml&tipo=${encodeURIComponent(emp.prefijo)}&numero=${encodeURIComponent(emp.consecutivo)}`, '_blank'); break;
            case 'info': mostrarInfoDian(emp); break;
        }
    });

    // Modal de detalle (delegado una sola vez)
    $('#bodyModalNomina')
        .on('input', '.valor-detalle', recalcularTotalesModal)
        .on('click', '.btn-editar-detalle', async function () {
            const fila = $(this).closest('tr');
            const r = await enviarPeticion({
                link: LINK_NOMINA, op: 'actualizar_detalle',
                id_detalle: fila.data('id'), total: parseFloat(fila.find('.valor-detalle').val()) || 0
            });
            if (r.error) {
                notificarError(r.message || 'Error al actualizar');
                return;
            }
            notificarExito('Valor actualizado');
            cargarEmpleadosPeriodo();
        })
        .on('click', '.btn-eliminar-detalle', async function () {
            const fila = $(this).closest('tr');
            const c = await Swal.fire({
                title: '¿Eliminar concepto?', icon: 'warning', showCancelButton: true,
                confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar', reverseButtons: true
            });
            if (!c.isConfirmed) return;
            const r = await enviarPeticion({ link: LINK_NOMINA, op: 'eliminar_detalle', id_detalle: fila.data('id') });
            if (r.error) {
                notificarError(r.message || 'No se pudo eliminar');
                return;
            }
            fila.remove();
            recalcularTotalesModal();
            cargarEmpleadosPeriodo();
        })
        .on('click', '#btnGuardarNotaNomina', async function () {
            const r = await enviarPeticion({
                link: LINK_NOMINA, op: 'guardar_nota_nomina',
                id_nomina: $('#bodyModalNomina').data('id-nomina'), nota: $('#txtNotaNomina').val()
            });
            if (r.error) notificarError(r.message); else notificarExito('Nota guardada');
        });

    $('#btnColillaModal').on('click', function () {
        abrirColillas({ ids: [$(this).data('id-nomina')] });
    });

    $('#chkSeleccionarTodos').on('change', function () {
        $('#cuerpoTablaEmpleados tr:visible .chkEmpleado').prop('checked', this.checked);
    });
    $('#filtroEmpleados').on('input', aplicarFiltroEmpleados);

    dissminSwal();
});
