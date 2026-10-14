const LINK_TIPOS_DOC = '../models/tiposDocumentos.php';

// sw que admiten las banderas especiales
const SW_NOMINA = ['9', '14'];
const SW_SOPORTE = ['14'];

let editIdConceptoRet = 0;
let cuentasCargadas = false;
let cacheTipos = [];

const escapeHtml = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const cargando = (texto) => showLoadingWithTetrisLogo(texto, 'ISA Software');
const toast = (icon, title) => Swal.fire({ icon, title, toast: true, position: 'top-end', timer: 2200, showConfirmButton: false });
const numero = (v) => (v === null || v === undefined || v === '') ? '' : Number(v).toLocaleString('es-CO', { maximumFractionDigits: 3 });
const hoy = () => new Date().toISOString().slice(0, 10);

// ---------------------------------------------------------------------------
// Combos
// ---------------------------------------------------------------------------
const cargarSw = async () => {
  try {
    const resp = await enviarPeticion({ link: '../shared/servicios/servicios.php', op: 'cargar_sw' });
    let html = '<option value="">Seleccione...</option>';
    (resp.data || []).forEach((e) => { html += `<option value="${escapeHtml(e.sw)}">${escapeHtml(e.sw)} - ${escapeHtml(e.descripcion)}</option>`; });
    $('#slcSw').html(html);
  } catch (e) {
    console.error(e);
  }
};

const cargarAlmacenes = async () => {
  try {
    const resp = await enviarPeticion({ link: '../models/almacenes.php', op: 'listar', filtro: '' });
    let html = '<option value="0">Todas las bodegas</option>';
    (resp.data || []).forEach((a) => { html += `<option value="${a.id}">${escapeHtml(a.almacen)} - ${escapeHtml(a.descripcion)}</option>`; });
    $('#slcAlmacen').html(html);
  } catch (e) {
    console.error(e);
  }
};

// El plan de cuentas solo se carga al abrir los conceptos de retención (antes se cargaba siempre al iniciar)
const cargarCuentas = async () => {
  if (cuentasCargadas) return true;
  const resp = await enviarPeticion({ link: '../models/cuentasContables.php', op: 'listar-todos' });
  if (!resp.data || !resp.data.length) {
    Swal.fire('Plan de cuentas', 'No hay un plan de cuentas cargado; no se pueden configurar retenciones.', 'info');
    return false;
  }
  const html = '<option value=""></option>' + resp.data
    .map((c) => `<option value="${escapeHtml(c.cuenta)}">${escapeHtml(c.cuenta)} - ${escapeHtml(c.descripcion)}</option>`).join('');
  $('#retencionModal .form-select2').html(html).select2({
    theme: 'bootstrap-5',
    dropdownParent: $('#retencionModal'),
    allowClear: true,
    placeholder: 'Cuenta',
    width: '100%',
    language: { noResults: () => 'No se encontraron resultados' }
  });
  cuentasCargadas = true;
  return true;
};

// ---------------------------------------------------------------------------
// Formulario
// ---------------------------------------------------------------------------
const actualizarOpcionesSw = () => {
  const sw = String($('#slcSw').val() || '');
  const admiteNomina = SW_NOMINA.includes(sw);
  const admiteSoporte = SW_SOPORTE.includes(sw);

  $('#contenedor_nomina').toggleClass('d-none', !admiteNomina);
  $('#contenedor_documento_soporte').toggleClass('d-none', !admiteSoporte);
  if (!admiteNomina) $('#nomina').prop('checked', false);
  if (!admiteSoporte) $('#documento_soporte').prop('checked', false);

  $('#ayudaNomina').text(sw === '9'
    ? 'Egreso de pago de nómina: se usa en el botón "Egreso de pago" del módulo de nómina.'
    : 'Causación de nómina: se usa al contabilizar la nómina.');
};

// Nómina y documento soporte son excluyentes
const exclusivas = function () {
  if (this.checked) {
    $(this.id === 'nomina' ? '#documento_soporte' : '#nomina').prop('checked', false);
  }
};

const actualizarConsumo = () => {
  const ini = parseInt($('#txtRangoInicial').val(), 10) || 0;
  const fin = parseInt($('#txtRangoFinal').val(), 10) || 0;
  const num = parseInt($('#txtNumero').val(), 10) || 0;
  const mostrar = fin > 0 && fin > ini;
  $('#barraConsumo, #lblConsumo').toggleClass('d-none', !mostrar);
  if (!mostrar) return;
  const pct = Math.min(100, Math.max(0, ((num - ini) / (fin - ini)) * 100));
  $('#barraConsumo .progress-bar').css('width', `${pct}%`);
  const disponibles = Math.max(0, fin - num);
  $('#lblConsumo').html(`Consumido ${pct.toFixed(1)} % del rango · quedan <strong>${disponibles.toLocaleString('es-CO')}</strong> números`
    + (pct >= 90 ? ' <span class="text-danger">· solicite una nueva resolución</span>' : ''));
};

const modoEdicion = (editando) => {
  $('#slcSw').prop('disabled', editando);
  $('#ayudaSw').toggleClass('d-none', !editando);
  $('#badgeModoEdicion').toggleClass('d-none', !editando);
  $('#lblTabFormulario').text(editando ? 'Editar tipo' : 'Crear tipo');
  $('#btnConceptosRet').prop('disabled', !editando);
};

const nuevoTipo = () => {
  limpiarFormulario('FormCrear');
  $('#FormCrear').removeClass('was-validated');
  $('#txtId').val('');
  $('#slcSw').val('');
  $('#slcAlmacen').val('0');
  $('#slcInactivo').val('0');
  $('#txtRangoInicial, #txtRangoFinal, #txtNumero, #txtResolucion').val('0');
  $('#txtFechaInicio, #txtFechaFinal').val(hoy());
  $('#documento_electronico, #documento_soporte, #nomina').prop('checked', false);
  modoEdicion(false);
  actualizarOpcionesSw();
  actualizarConsumo();
};

const cargarEnFormulario = (data) => {
  if (String(data.editable || 'S').toUpperCase() === 'N') {
    Swal.fire('Documento del sistema', 'Este tipo de documento no se puede editar ni eliminar.', 'info');
    return;
  }
  nuevoTipo();
  ShowDataFrom(data, 'FormCrear');
  $('#txtId').val(data.id);
  $('#slcSw').val(String(data.sw));
  $('#txtFechaInicio').val(String(data.fecha_inicio || '').slice(0, 10));
  $('#txtFechaFinal').val(String(data.fecha_fin || '').slice(0, 10));
  $('#documento_electronico').prop('checked', String(data.documento_electronico || 'N') === 'S');
  $('#documento_soporte').prop('checked', String(data.documento_soporte || 'N') === 'S');
  $('#nomina').prop('checked', String(data.nomina || 'N') === 'S');
  modoEdicion(true);
  actualizarOpcionesSw();
  actualizarConsumo();
  selectedTab('tabs', 'navs-creacion');
};

const guardarTipo = async () => {
  const form = document.getElementById('FormCrear');
  if (!form.checkValidity()) {
    form.classList.add('was-validated');
    return;
  }
  const data = formatearArrayRequest($('#FormCrear').serializeArray());
  const editando = !!$('#txtId').val();
  data.link = LINK_TIPOS_DOC;
  data.op = editando ? 'update' : 'agregar';
  data.sw = $('#slcSw').val();                          // el select deshabilitado no se serializa
  data.tipo = String(data.tipo || '').toUpperCase().trim();
  data.documento_electronico = $('#documento_electronico').is(':checked') ? 'S' : 'N';
  data.documento_soporte = $('#documento_soporte').is(':checked') ? 'S' : 'N';
  data.nomina = $('#nomina').is(':checked') ? 'S' : 'N';

  cargando('Guardando registro');
  try {
    const resp = await enviarPeticion(data);
    dissminSwal();
    if (resp.error) {
      Swal.fire('No se guardó', resp.message, 'warning');
      return;
    }
    toast('success', resp.message);
    if (!editando && resp.data?.id) {
      // Queda en edición para poder configurar retenciones de inmediato
      $('#txtId').val(resp.data.id);
      modoEdicion(true);
    }
    cacheTipos = [];
  } catch (e) {
    dissminSwal();
    console.error(e);
    Swal.fire('Error', 'Error interno de la aplicación', 'error');
  }
};

// Al escribir un tipo que ya existe, ofrece cargarlo para edición
const verificarTipoExistente = async () => {
  if ($('#txtId').val()) return;
  const tipo = $.trim($('#txtTipo').val()).toUpperCase();
  if (!tipo) return;
  try {
    const resp = await enviarPeticion({ link: LINK_TIPOS_DOC, op: 'buscar-tipo', tipo });
    if (!Array.isArray(resp) || !resp.length) return;
    const r = await Swal.fire({
      icon: 'info',
      title: `El tipo ${escapeHtml(tipo)} ya existe`,
      text: `${resp[0].descripcion} (sw ${resp[0].sw}). ¿Desea editarlo?`,
      showCancelButton: true,
      confirmButtonText: 'Sí, editar',
      cancelButtonText: 'Cambiar código'
    });
    if (r.isConfirmed) {
      cargarEnFormulario(resp[0]);
    } else {
      $('#txtTipo').val('').trigger('focus');
    }
  } catch (e) {
    console.error(e);
  }
};

// ---------------------------------------------------------------------------
// Listado (se carga al abrir la pestaña)
// ---------------------------------------------------------------------------
const badgeSN = (v, texto) => String(v || 'N') === 'S' ? `<span class="badge bg-label-primary">${texto}</span>` : '';

const listarTiposDocumentos = async () => {
  if (!$('#navs-listado').hasClass('active')) return;
  cargando('Buscando información');
  try {
    const resp = await enviarPeticion({ link: LINK_TIPOS_DOC, op: 'listar', filtro: $('#filtro').val() });
    dissminSwal();
    cacheTipos = resp.data || [];
    if (!cacheTipos.length) {
      $('#result').html(ShowAlert('No se encontraron resultados', 'warning'));
      return;
    }
    const datos = cacheTipos.map((item) => ({
      ...item,
      sw_des: `<span class="fw-semibold">${escapeHtml(item.sw)}</span>`,
      numeracion: Number(item.rango_final) > 0
        ? `${numero(item.numero)} <small class="text-muted">/ ${numero(item.rango_final)}</small>`
        : numero(item.numero),
      marcas: [badgeSN(item.documento_electronico, 'Electrónico'), badgeSN(item.documento_soporte, 'Soporte'),
               badgeSN(item.nomina, String(item.sw) === '9' ? 'Egreso nómina' : 'Nómina')].join(' '),
      inactivo_des: String(item.inactivo_des).toUpperCase() === 'INACTIVO'
        ? '<span class="badge bg-label-secondary">Inactivo</span>'
        : '<span class="badge bg-label-success">Activo</span>'
    }));

    $('#result').html('<table id="tiposdocumentos" class="table table-sm table-hover"><thead class="table-primary"></thead></table>');
    createDataTable(
      'tiposdocumentos',
      datos,
      ['tipo', 'descripcion', 'sw_des', 'almacen_des', 'numeracion', 'marcas', 'fecha_fin', 'inactivo_des'],
      ['Tipo', 'Descripción', 'Sw', 'Almacén', 'Número', 'Configuración', 'Vigencia hasta', 'Estado'],
      cargarEnFormulario,
      eliminarTipo,
      true,
      '<button class="btn btn-sm btn-icon btn-outline-primary other-btn" title="Información"><i class="fa fa-info-circle"></i></button>',
      mostrarInfo
    );
  } catch (e) {
    dissminSwal();
    console.error(e);
    $('#result').html(ShowAlert('Error interno de la aplicación', 'error'));
  }
};

const eliminarTipo = async (data) => {
  if (String(data.editable || 'S').toUpperCase() === 'N') {
    Swal.fire('Documento del sistema', 'Este tipo de documento no se puede editar ni eliminar.', 'info');
    return;
  }
  if (!(await showConfirmation())) return;
  cargando('Eliminando...');
  const resp = await enviarPeticion({ link: LINK_TIPOS_DOC, op: 'delete', id: data.id });
  dissminSwal();
  if (resp.error) {
    Swal.fire('No se eliminó', resp.message, 'warning');
    return;
  }
  toast('success', resp.message);
  if (String($('#txtId').val()) === String(data.id)) nuevoTipo();
  listarTiposDocumentos();
};

const mostrarInfo = (data) => {
  const fila = (k, v) => `<tr><th class="text-muted fw-normal" width="38%">${k}</th><td class="fw-semibold">${v}</td></tr>`;
  const sn = (v) => String(v || 'N') === 'S' ? 'Sí' : 'No';
  $('#tablaInfo tbody').html([
    fila('Tipo', escapeHtml(data.tipo)),
    fila('Descripción', escapeHtml(data.descripcion)),
    fila('Sw', escapeHtml(data.sw)),
    fila('Almacén', escapeHtml(data.almacen_des)),
    fila('Rango', `${numero(data.rango_inicial)} a ${numero(data.rango_final) || 'sin límite'}`),
    fila('Número actual', numero(data.numero)),
    fila('Resolución', escapeHtml(data.resolucion)),
    fila('Vigencia', `${escapeHtml(String(data.fecha_inicio || '').slice(0, 10))} a ${escapeHtml(String(data.fecha_fin || '').slice(0, 10))}`),
    fila('Electrónico / Soporte / Nómina', `${sn(data.documento_electronico)} / ${sn(data.documento_soporte)} / ${sn(data.nomina)}`),
    fila('Estado', escapeHtml(String(data.inactivo) === '1' ? 'Inactivo' : 'Activo')),
    fila('Leyenda 1', escapeHtml(data.leyenda1)),
    fila('Leyenda 2', escapeHtml(data.leyenda2)),
    fila('Creado', `${escapeHtml(data.fecha_creacion)} · ${escapeHtml(data.usuario)}`)
  ].join(''));
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalInfo')).show();
};

// ---------------------------------------------------------------------------
// Conceptos de retención
// ---------------------------------------------------------------------------
const limpiarConceptosRetencion = () => {
  editIdConceptoRet = 0;
  $('#retTxtConcepto, #retBaseReteFuente, #retPcjReteFuente, #retBaseReteICA, #retPcjReteICA, #retBaseReteIVA, #retPcjReteIVA').val('');
  $('#retencionModal .form-select2').val(null).trigger('change');
  $('#tablaRetenciones tbody tr').removeClass('table-active');
  $('#btnBorrar').prop('disabled', true);
  $('#lblBtnRet').text('Agregar');
};

const conceptosRetencion = async () => {
  const resp = await enviarPeticion({ link: LINK_TIPOS_DOC, op: 'list_retencion', id_tipo_documento: $('#txtId').val() });
  if (!resp.ok) {
    Swal.fire('Error', resp.message, 'error');
    return;
  }
  const filas = (resp.data || []).map((i) => `
    <tr data-id="${i.id}" style="cursor:pointer">
      <td>${escapeHtml(i.concepto_descripcion)}</td>
      <td>${escapeHtml(i.cuenta_retefuente)}</td><td class="text-end">${numero(i.base_retefuente)}</td><td class="text-end">${numero(i.porcentaje_retefuente)}</td>
      <td>${escapeHtml(i.cuenta_reteica)}</td><td class="text-end">${numero(i.base_reteica)}</td><td class="text-end">${numero(i.porcentaje_reteica)}</td>
      <td>${escapeHtml(i.cuenta_reteiva)}</td><td class="text-end">${numero(i.base_reteiva)}</td><td class="text-end">${numero(i.porcentaje_reteiva)}</td>
    </tr>`).join('');
  $('#tablaRetenciones tbody').html(filas || '<tr><td colspan="10" class="text-center text-muted py-3"><small>Sin conceptos de retención</small></td></tr>');
};

const abrirConceptosRetencion = async () => {
  if (!$('#txtId').val()) {
    Swal.fire('Atención', 'Guarde o seleccione un tipo de documento primero.', 'warning');
    return;
  }
  cargando('Cargando conceptos de retención');
  try {
    if (!(await cargarCuentas())) return;
    await conceptosRetencion();
    limpiarConceptosRetencion();
    $('#retTipoDoc').text($('#txtTipo').val());
    $('#retDescDoc').text($('#txtDescripcion').val());
    bootstrap.Modal.getOrCreateInstance(document.getElementById('retencionModal')).show();
  } finally {
    dissminSwal();
  }
};

const guardarRetencion = async () => {
  const op = editIdConceptoRet === 0 ? 'create_retencion' : 'update_retencion';
  const resp = await enviarPeticion({
    link: LINK_TIPOS_DOC, op, id: editIdConceptoRet,
    id_tipo_documento: $('#txtId').val(),
    concepto_descripcion: $('#retTxtConcepto').val(),
    cuenta_retefuente: $('#retCuentaReteFuente').val() || '',
    base_retefuente: $('#retBaseReteFuente').val() || 0,
    porcentaje_retefuente: $('#retPcjReteFuente').val() || 0,
    cuenta_reteica: $('#retCuentaReteICA').val() || '',
    base_reteica: $('#retBaseReteICA').val() || 0,
    porcentaje_reteica: $('#retPcjReteICA').val() || 0,
    cuenta_reteiva: $('#retCuentaReteIVA').val() || '',
    base_reteiva: $('#retBaseReteIVA').val() || 0,
    porcentaje_reteiva: $('#retPcjReteIVA').val() || 0
  });
  if (!resp.ok) {
    Swal.fire('No se guardó', resp.message, 'warning');
    return;
  }
  toast('success', resp.message);
  await conceptosRetencion();
  limpiarConceptosRetencion();
};

const editarRetencion = async (id) => {
  const resp = await enviarPeticion({ link: LINK_TIPOS_DOC, op: 'get_retencion', id });
  if (!resp.ok || !resp.data) {
    Swal.fire('Error', resp.message || 'Concepto no encontrado', 'error');
    return;
  }
  const i = resp.data;
  editIdConceptoRet = Number(i.id);
  $('#retTxtConcepto').val(i.concepto_descripcion);
  $('#retCuentaReteFuente').val(i.cuenta_retefuente).trigger('change');
  $('#retBaseReteFuente').val(i.base_retefuente);
  $('#retPcjReteFuente').val(i.porcentaje_retefuente);
  $('#retCuentaReteICA').val(i.cuenta_reteica).trigger('change');
  $('#retBaseReteICA').val(i.base_reteica);
  $('#retPcjReteICA').val(i.porcentaje_reteica);
  $('#retCuentaReteIVA').val(i.cuenta_reteiva).trigger('change');
  $('#retBaseReteIVA').val(i.base_reteiva);
  $('#retPcjReteIVA').val(i.porcentaje_reteiva);
  $('#btnBorrar').prop('disabled', false);
  $('#lblBtnRet').text('Actualizar');
};

const eliminarRetencion = async () => {
  if (!editIdConceptoRet) return;
  if (!(await showConfirmation())) return;
  const resp = await enviarPeticion({ link: LINK_TIPOS_DOC, op: 'delete_retencion', id: editIdConceptoRet });
  if (!resp.ok) {
    Swal.fire('Error', resp.message, 'error');
    return;
  }
  toast('success', resp.message);
  await conceptosRetencion();
  limpiarConceptosRetencion();
};

// ---------------------------------------------------------------------------
// Eventos
// ---------------------------------------------------------------------------
$(async function () {
  'use strict';
  cargando('Cargando información');
  await Promise.all([cargarSw(), cargarAlmacenes()]);
  nuevoTipo();
  dissminSwal();

  $('#FormCrear').on('submit', (e) => {
    e.preventDefault();
    guardarTipo();
  });
  $('#btnNuevo').on('click', nuevoTipo);
  $('#slcSw').on('change', actualizarOpcionesSw);
  $('#nomina, #documento_soporte').on('change', exclusivas);
  $('#txtRangoInicial, #txtRangoFinal, #txtNumero').on('input', actualizarConsumo);
  $('#txtTipo').on('input', function () {
    this.value = this.value.toUpperCase().replace(/\s/g, '');
  }).on('blur', verificarTipoExistente);

  // Listado: se consulta al entrar a la pestaña
  $('button[data-bs-target="#navs-listado"]').on('shown.bs.tab', listarTiposDocumentos);
  $('#buscar').on('click', listarTiposDocumentos);
  $('#filtro').on('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      listarTiposDocumentos();
    }
  });
  $('#btnLimpiarFiltro').on('click', () => {
    $('#filtro').val('');
    listarTiposDocumentos();
  });

  // Retenciones
  $('#btnConceptosRet').on('click', abrirConceptosRetencion);
  $('#btnAdicionar').on('click', guardarRetencion);
  $('#btnLimpiarRet').on('click', limpiarConceptosRetencion);
  $('#btnBorrar').on('click', eliminarRetencion);
  $('#tablaRetenciones').on('click', 'tbody tr[data-id]', function () {
    $('#tablaRetenciones tbody tr').removeClass('table-active');
    $(this).addClass('table-active');
    editarRetencion($(this).data('id'));
  });
});
