/* =====================================================================
 * POS - Ventas (sw = 5)
 * Versión optimizada: estado centralizado, delegación de eventos,
 * peticiones en paralelo, render en lote y vista responsiva.
 * ===================================================================== */
const id_modulo = '35';

/* ============================ ESTADO ============================ */
let permisos = {};
let data_tipos = {};
let modalBusquedaRef = null;
let listaModalBusqueda = null;
let buscando = false;
let guardandoItem = false;
let guardandoDocumento = false;
let manejaLote = false;
let itemActual = null;

let conceptosRetencion = [];
let conceptosTipoCargado = null;          // id de tipo de documento cuyos conceptos están en memoria
let firmaRetencionesGuardadas = '';      // evita reenviar retenciones idénticas
let perfilTributarioRetenciones = { ret_fte: 'N', ret_cree: 'N', ret_ica: 'N', ret_iva: 'N' };

let pagosRegistrados = [];               // pagos del modal (antes se leían del DOM)
let totalFacturaPago = 0;
let lotesActuales = [];
// Caché para editar sin ir al servidor: id_detalle -> foto de la grilla al momento de agregarlo
const cacheCarrito = new Map();
let temporalesData = [];
let facturasData = [];

/* ============================ HELPERS ============================ */
const esS = (v) => v === true || v === 1 || v === '1' || String(v ?? '').toUpperCase() === 'S';
const num = (v) => { const n = parseFloat(v); return Number.isFinite(n) ? n : 0; };
const money = (v) => formatNum(num(v), '$', ConfGeneral);
const unmoney = (v) => num(unformatNum(v ?? 0));
const esc = (v) => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const cargando = (msg) => showLoadingWithTetrisLogo(msg, 'ISA Software');
const cerrarCarga = () => dissminSwal();
const alerta = (titulo, texto, icono = 'warning') => Swal.fire(titulo, texto, icono);
const enfocar = (sel, ms = 300) => setTimeout(() => { const $e = $(sel); $e.trigger('focus'); if ($e.is('input')) $e.trigger('select'); }, ms);
const undSel = () => $("#und_grid option:selected");
const fechaISO = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
const mostrarTab = (target) => $(`#tabs button[data-bs-target="${target}"]`).prop('disabled', false).tab('show');
const listaPreciosActual = () => {
  const listaTercero = parseInt($("#lista_tercero").val(), 10) || 0;
  return listaTercero || (parseInt($("#lista_almacen").val(), 10) || 0);
};
/**
 * Resalta la lista de precios que realmente se aplica (la del tercero si tiene,
 * si no la del almacén) y habilita el botón de información cuando hay cliente.
 */
const actualizarPanelCliente = () => {
  const listaTercero = parseInt($("#lista_tercero").val(), 10) || 0;
  const listaAlmacen = parseInt($("#lista_almacen").val(), 10) || 0;
  const usaTercero = listaTercero > 0;
  const usaAlmacen = !usaTercero && listaAlmacen > 0;

  $("#tile_lista_tercero").toggleClass('activa', usaTercero).toggleClass('inactiva', !usaTercero)
    .find('.lista-precio-uso').toggleClass('d-none', !usaTercero);
  $("#tile_lista_almacen").toggleClass('activa', usaAlmacen).toggleClass('inactiva', !usaAlmacen)
    .find('.lista-precio-uso').toggleClass('d-none', !usaAlmacen);
  $("#nota_lista_tercero").text(usaTercero ? 'Asignada al cliente' : 'El cliente no tiene lista');

  $("#btn_info_cliente").prop('disabled', !(parseInt($("#id_tercero").val(), 10) > 0));
};

const esDomicilio = () => $("#domicilio").hasClass('activo');
const setDomicilio = (activo) => $("#domicilio")
  .toggleClass('activo btn-primary', !!activo)
  .toggleClass('btn-outline-secondary', !activo);

/** Inicializa dropdowns con strategy fixed para que no se recorten dentro de .table-responsive */
const initDropdownsFixed = ($scope) => {
  $scope.find("[data-bs-toggle='dropdown']").each(function () {
    new bootstrap.Dropdown(this, { popperConfig: (cfg) => ({ ...cfg, strategy: 'fixed' }) });
  });
};

const sinResultados = (texto = 'No se encontraron resultados!') => `
  <div class="alert alert-warning mb-0"><i class="fa-solid fa-triangle-exclamation"></i> ${texto}</div>`;

/** Modal genérico de solo lectura (comprometidas, stock otras bodegas, etc.) */
const mostrarModalTabla = ({ id, titulo, cuerpo, tamano = '', estatico = false }) => {
  document.getElementById(id)?.remove();
  document.body.insertAdjacentHTML('beforeend', `
    <div class="modal fade" id="${id}" tabindex="-1" ${estatico ? 'data-bs-backdrop="static" data-bs-keyboard="false"' : ''}>
      <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down ${tamano}">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">${titulo}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">${cuerpo}</div>
        </div>
      </div>
    </div>`);
  const el = document.getElementById(id);
  el.addEventListener('hidden.bs.modal', () => el.remove(), { once: true });
  new bootstrap.Modal(el).show();
};

/* ============================ TERCERO ============================ */
const disabledEnabledTercero = (sw) => {
  $("#nit, #id_tercero_dir, #tipos, #id_condicion, #bodegas").prop("disabled", sw);
};

const cargarTercerosDefault = async () => {
  if (!ConfModulos?.TerceroDefault) return false;
  try {
    const resp = await enviarPeticion({
      link: '../models/Terceros.php',
      filtro: ConfModulos.TerceroDefault,
      tipo_tercero: '',
      estado: 'A',
      op: 's_tercero',
      ligero: 1
    });
    if (!resp?.data?.length) return false;
    return !!(await cargarTercero(resp.data[0]));
  } catch (e) {
    console.error(e);
    return false;
  }
};

const validarTercero = async (item) => {
  if (!permisos.IgnorarBloqueoTercero && item.estado !== 'A') {
    return { sw: false, msj: 'El cliente se encuentra bloqueado' };
  }
  if (!validarEmail(item.email_fe)) {
    return { sw: false, msj: 'El email del cliente no es válido' };
  }
  if (item.dias === 0) {
    return { sw: false, msj: 'El cliente no tiene condición de pago crédito' };
  }

  if (permisos.ControlCarteraVencida || permisos.ControlCupoCredito) {
    const consulta = await controlCreditoTercero(item.id);
    if (!consulta?.ok || !consulta.data?.length) {
      return { sw: false, msj: 'No fue posible validar el cupo del tercero, por favor verifique e intente nuevamente.' };
    }
    const credito = consulta.data[0];
    if (permisos.ControlCarteraVencida && credito.en_mora === 'S') {
      return { sw: false, msj: 'El tercero presenta cartera en mora, imposible continuar con la venta.' };
    }
    if (permisos.ControlCupoCredito && Number(credito.cupo_disponible) < 0) {
      return { sw: false, msj: 'El tercero no tiene cupo de crédito disponible, imposible continuar con la venta.' };
    }
  }
  return { sw: true, msj: '' };
};

const pintarInfoTercero = (d) => {
  $("#info_lista").text(d.lista_precios ?? '');
  $("#info_credito").text(formatNum(d.cupo_credito, '$'));
  $("#info_zona_descripcion").text(d.zona_descripcion ?? '');
  $("#info_email").text(d.email_fe ?? '');
};

const cargarTercero = async (item) => {
  const valido = await validarTercero(item);
  if (!valido.sw) {
    alerta("Oops", valido.msj);
    return false;
  }

  $("#id_tercero").val(item.id);
  $("#nit").val(item.nit);
  $("#tratamiento").val(item.tratamiento);
  $("#razon_comercial").val(item.razon_comercial);
  $("#textDireccion").val(item.direccion);
  $("#lista_tercero").val(parseInt(item.lista_precios, 10) || 0);
  actualizarPanelCliente();
  $("#info_condicion").text('CONTADO');
  $("#info_zona_venta").text(item.codigo_zona ?? '');
  $("#info_controlados").text($.trim(item.controlados ?? ''));
  $("#info_institucional").text($.trim(item.institucionales ?? ''));
  pintarInfoTercero(item);

  // Antes: 3 peticiones secuenciales + una consulta extra del tercero que ya teníamos
  const [okDir] = await Promise.all([
    cargarDirecciones(item.id),
    cargarPerfilTributarioDesdeTercero(item.id, item.id_perfil_tributario),
    listarConceptosRetencion()
  ]);

  if (!okDir) {
    sinDirecciones();
    return false;
  }

  mostrarTab('#navs-creacion');
  enfocar('#filtro', 500);
  return true;
};

const limpiarTercero = () => {
  $("#id_tercero, #nit, #tratamiento, #razon_comercial, #txtBusquedaTercero, #notas").val("");
  $("#info_condicion, #info_lista, #info_credito, #info_zona_venta, #info_email, #info_zona_descripcion, #info_institucional, #info_controlados").text("");
  $("#id_tercero_dir").html("");
  $("#lista_tercero").val(0);
  actualizarPanelCliente();
};

const listarAlmacenes = async () => {
  try {
    const resp = await bodegasUsuarios();
    if (!resp?.length) {
      $("#result").html(ShowAlert('No se encontraron resultados', 'warning'));
      return;
    }
    $("#bodegas")
      .html(resp.map(d => `<option value="${esc(d.id)}">${esc(d.bodega)} - ${esc(d.descripcion)}</option>`).join(''))
      .val($("#id_bodega_session").val())
      .prop("disabled", true);
  } catch (e) {
    console.error(e);
    $("#result").html(ShowAlert('Error interno de la aplicación', 'error'));
  }
};

/** Devuelve true si cargó al menos una dirección */
const cargarDirecciones = async (id_tercero) => {
  try {
    const resp = await enviarPeticion({ link: '../models/Pedidos.php', op: 'listarDirecciones', id_tercero });
    if (!Array.isArray(resp) || !resp.length) return false;
    $("#id_tercero_dir").html(resp.map(d =>
      `<option value="${esc(d.id)}" ${d.principal == 1 ? 'selected' : ''}>${esc(d.direccion)}</option>`
    ).join(''));
    return true;
  } catch (e) {
    console.error(e);
    return false;
  }
};

const sinDirecciones = () => {
  nuevoPedido();
  alerta("Error", "No se encontraron direcciones para este tercero, por favor ingrese una dirección para continuar", "error");
};

/* ============================ RETENCIONES ============================ */
const listarConceptosRetencion = async (forzar = false) => {
  const idTipo = parseInt($("#tipos").val() || '0', 10);
  const $sel = $("#concepto_retencion");

  if (!idTipo) {
    conceptosRetencion = [];
    conceptosTipoCargado = null;
    $sel.html('<option value="">NO APLICA</option>');
    aplicarRetencionesPorPerfil();
    return;
  }

  // Cache por tipo de documento: antes se consultaba en cada cambio de cliente
  if (!forzar && idTipo === conceptosTipoCargado) {
    $sel.val('');
    aplicarRetencionesPorPerfil();
    return;
  }

  try {
    const resp = await enviarPeticion({ link: '../models/tiposDocumentos.php', op: 'list_retencion', id_tipo_documento: idTipo });
    conceptosRetencion = resp?.ok && Array.isArray(resp.data) ? resp.data : [];
    conceptosTipoCargado = idTipo;
  } catch (e) {
    console.error(e);
    conceptosRetencion = [];
    conceptosTipoCargado = null;
  }

  $sel.html(conceptosRetencion.length
    ? '<option value="">Seleccione un concepto</option>' +
      conceptosRetencion.map(d => `<option value="${esc(d.id)}">${esc(d.concepto_descripcion)}</option>`).join('')
    : '<option value="">NO APLICA</option>');
  aplicarRetencionesPorPerfil();
};

const limpiarPerfilTributarioCompra = () => {
  perfilTributarioRetenciones = { ret_fte: 'N', ret_cree: 'N', ret_ica: 'N', ret_iva: 'N' };
  $("#id_perfil_tributario").val('0');
  $("#perfil_tributario_compra").html('<option value="">NO APLICA</option>');
  aplicarRetencionesPorPerfil();
};

/** Resumen compacto del botón que despliega los valores en móvil */
const actualizarResumenMovil = () => {
  const numero = $.trim($("#numero").val());
  $("#resumen_numero").text(numero !== '' && numero !== '0' ? `#${numero}` : 'Nuevo');
  $("#resumen_neto").text($("#t_neto_pagar").val() || money(0));
};

const pintarRetencionesTotales = (retFte = 0, retIca = 0, retIva = 0) => {
  const totalRet = retFte + retIca + retIva;
  const neto = Math.max(0, unmoney($("#t_total").val()) - totalRet);
  $("#t_ret_fte").val(money(retFte));
  $("#t_ret_ica").val(money(retIca));
  $("#t_ret_iva").val(money(retIva));
  $("#t_ret_total").val(money(totalRet));
  $("#t_neto_pagar").val(money(neto));
  $("#barra_neto").text(money(neto));
  actualizarResumenMovil();
};

const aplicarRetencionesPorPerfil = () => {
  const idConcepto = parseInt($("#concepto_retencion").val() || '0', 10);
  const concepto = conceptosRetencion.find(c => parseInt(c.id, 10) === idConcepto);

  if (!concepto) {
    pintarRetencionesTotales(0, 0, 0);
    return;
  }

  const baseDocumento = Math.max(0, unmoney($("#t_bruto").val()) - unmoney($("#t_dcto").val()));
  const ivaTotal = unmoney($("#t_iva").val());
  const p = perfilTributarioRetenciones;

  const aplicarFte = p.ret_fte === 'S' && baseDocumento >= num(concepto.base_retefuente);
  const aplicarIca = p.ret_ica === 'S' && baseDocumento >= num(concepto.base_reteica);
  const aplicarIva = p.ret_iva === 'S' && ivaTotal >= num(concepto.base_reteiva);

  pintarRetencionesTotales(
    aplicarFte ? baseDocumento * num(concepto.porcentaje_retefuente) / 100 : 0,
    aplicarIca ? baseDocumento * num(concepto.porcentaje_reteica) / 100 : 0,
    aplicarIva ? ivaTotal * num(concepto.porcentaje_reteiva) / 100 : 0
  );
};

const cargarPerfilTributarioPorId = async (idPerfil) => {
  const idPerfilTributario = parseInt(idPerfil || '0', 10);
  const bodega = parseInt($("#bodegas").val() || '0', 10);

  if (!idPerfilTributario) {
    limpiarPerfilTributarioCompra();
    return;
  }

  try {
    const resp = await enviarPeticion({
      link: '../models/IngresosMercancias.php',
      op: 'perfil_tributario_detalle',
      id_perfil_tributario: idPerfilTributario,
      bodega
    });
    const perfiles = resp?.ok && Array.isArray(resp.data) ? resp.data : [];
    if (!perfiles.length) {
      limpiarPerfilTributarioCompra();
      return;
    }
    const perfil = perfiles.find(p => parseInt(p.bodega, 10) === bodega) || perfiles[0];
    perfilTributarioRetenciones = {
      ret_fte: perfil.ret_fte || 'N',
      ret_cree: perfil.ret_cree || 'N',
      ret_ica: perfil.ret_ica || 'N',
      ret_iva: perfil.ret_iva || 'N'
    };
    $("#id_perfil_tributario").val(idPerfilTributario);
    $("#perfil_tributario_compra").html(`<option value="${esc(perfil.id)}" selected>${esc(perfil.tipo)} - ${esc(perfil.descripcion)}</option>`);
    aplicarRetencionesPorPerfil();
  } catch (e) {
    console.error(e);
    limpiarPerfilTributarioCompra();
  }
};

/**
 * Si el registro del tercero ya trae id_perfil_tributario se usa directo
 * (antes siempre se volvía a consultar el tercero completo).
 */
const cargarPerfilTributarioDesdeTercero = async (idTercero, idPerfilConocido) => {
  if (idPerfilConocido !== undefined && idPerfilConocido !== null && idPerfilConocido !== '') {
    return cargarPerfilTributarioPorId(idPerfilConocido);
  }
  const id = parseInt(idTercero || '0', 10);
  if (!id) {
    limpiarPerfilTributarioCompra();
    return;
  }
  try {
    const resp = await enviarPeticion({ link: '../models/Terceros.php', op: 's_tercero', id_tercero: id, filtro: '', tipo_tercero: '', ligero: 1 });
    const tercero = resp?.data?.[0];
    await cargarPerfilTributarioPorId(tercero ? tercero.id_perfil_tributario : 0);
  } catch (e) {
    console.error(e);
    limpiarPerfilTributarioCompra();
  }
};

const guardarRetencionesTMP = async (idTransaccion, retFte, retIca, retIva) => {
  const idConcepto = parseInt($("#concepto_retencion").val() || '0', 10);
  const firma = [idTransaccion, retFte.toFixed(2), retIca.toFixed(2), retIva.toFixed(2), idConcepto].join('|');
  if (firma === firmaRetencionesGuardadas) return;   // sin cambios: no se envía
  try {
    await enviarPeticion({
      link: '../models/Pos.php',
      op: 'guardar_retenciones',
      id_transaccion: idTransaccion,
      ret_fte: retFte,
      ret_ica: retIca,
      ret_iva: retIva,
      id_concepto_retencion: idConcepto
    });
    firmaRetencionesGuardadas = firma;
  } catch (e) {
    console.error('Error al guardar retenciones:', e);
  }
};

const guardarRetencionesActuales = async () => {
  const idTransaccion = parseInt($("#id_transaccion").val(), 10) || 0;
  if (idTransaccion <= 0) return;
  await guardarRetencionesTMP(idTransaccion, unmoney($("#t_ret_fte").val()), unmoney($("#t_ret_ica").val()), unmoney($("#t_ret_iva").val()));
};

const formatearMonedaLocal = (valor) => money(valor);

const restaurarRetencionesEncabezado = (data) => {
  const idConcepto = parseInt(data.id_concepto_retencion || '0', 10);
  if (idConcepto > 0) $("#concepto_retencion").val(String(idConcepto));
  pintarRetencionesTotales(num(data.retencion_fuente), num(data.retencion_ica), num(data.retencion_iva));
};

/* ============================ TOTALES ============================ */
const pintarTotales = (t) => {
  const total = num(t.valor_total), iva = num(t.iva), dcto = num(t.descuento);
  $("#t_bruto").val(money(total - iva + dcto));
  $("#t_dcto").val(money(dcto));
  $("#t_iva").val(money(iva));
  $("#t_total").val(money(total));
};

const actualizarTotales = async (totales) => {
  pintarTotales(totales);
  aplicarRetencionesPorPerfil();
  await guardarRetencionesActuales();
};

/* ============================ LIMPIEZA ============================ */
const limpiarPedido = () => {
  $("#numero").val("");
  $("#t_bruto, #t_dcto, #t_iva, #t_total, #id_transaccion").val(0);
  firmaRetencionesGuardadas = '';
  limpiarPerfilTributarioCompra();
};

const limpiarReferencia = () => {
  itemActual = null;
  $("#unidades_comprometidas").text("");
  $("#und_grid").html("");
  $("#codigo_grid, #laboratorio_grid, #descripcion_grid, #stock_grid, #preciob_grid, #dto_grid, #iva_grid, #precion_grid, #cantidad_grid, #total_grid, #filtro, #tipo_referencia").val("");
  $("#stock_real, #id_lote, #precio_unitario, #id_referencia, #costo_promedio, #id_detalle, #comision_grid").val(0);
  if (manejaLote) $("#lote_grid, #vcmto_grid").val("");
  $("#precion_grid").prop("readonly", true);
  $("#btn_comision_producto").addClass("d-none");
};

const limpiarTemporales = () => {
  $("#temporales").html('');
  fechaActual([['fecha_inicial', 'fecha_final']]);
};

const aplicarEstadoFormaPago = () => {
  const forma = formaPagoSeleccionada();
  const esBanco = forma?.naturaleza === 'B';
  $("#tipo_tarjeta").val('0').prop("disabled", !esBanco);
  $("#voucher").val('').prop("disabled", !esBanco);
};

const reiniciarPagos = () => {
  pagosRegistrados = [];
  totalFacturaPago = 0;
  $("#valor_factura").text(money(0));
  $("#valor_recibido").val('');
  $("#forma_pago").val(1);
  if (!$("#forma_pago").val()) $("#forma_pago").prop('selectedIndex', 0);
  aplicarEstadoFormaPago();
  renderPagos();
};

/** Punto único de reinicio (antes estaba duplicado en nuevoPedido y eliminarItem) */
const reiniciarDocumento = ({ conservarTercero = false } = {}) => {
  limpiarPedido();
  limpiarReferencia();
  if (!conservarTercero) limpiarTercero();
  limpiarTemporales();
  $("#carrito tbody").empty();
  cacheCarrito.clear();
  $("#modalMediosPagos").modal("hide");
  reiniciarPagos();
  disabledEnabledTercero(false);
  if (!conservarTercero) {
    setDomicilio(false);
    $('#tabs button[data-bs-target="#navs-tercero"]').tab("show");
  }
};

const nuevoPedido = () => reiniciarDocumento();

/* ============================ PRODUCTO / GRILLA ============================ */
/** Construye las opciones de unidad (compartido por cargarItemGrid y editarPedido) */
const construirUnidades = (data) => {
  const unds = [1, 2, 3, 4]
    .map(i => ({
      idx: i,
      nombre: data[`und_${i}`],
      cantidad: data[`cant_${i}`],
      precio: data[`precio${i === 1 ? '' : i}`],
      precion: data[`precion${i === 1 ? '' : i}`],
      comision: data[`comision${i}`]
    }))
    .filter(u => Number(u.cantidad) > 0);

  const $und = $("#und_grid");
  $und
    .data("maneja-comision", data.comision)
    .data("calculo-comision", data.calculo_comision)
    .html(unds.map(u => `
      <option value="${esc(u.cantidad)}" data-idx="${u.idx}" data-precio="${num(u.precio)}"
              data-precion="${num(u.precion)}" data-comision="${num(u.comision)}"
              ${Number(u.cantidad) === Number(data.can_und) ? 'selected' : ''}>
        ${esc($.trim(u.nombre || ''))}-${esc(u.cantidad)}
      </option>`).join(''));

  // En un select multiple .val() devuelve [] (truthy): se valida por opción seleccionada
  if (!$und.find('option:selected').length) $und.find('option').first().prop('selected', true);
};

const cargarItemGrid = (data) => {
  itemActual = data;

  $("#id_referencia").val(data.id);
  $("#tipo_referencia").val(data.tipo);
  $("#descripcion_grid").val(data.descripcion);
  $("#laboratorio_grid").val(data.laboratorio);
  $("#codigo_grid").val(data.codigo);
  $("#precio_unitario").val(data.precio);
  $("#dto_grid").val(data.dcto);
  $("#iva_grid").val(data.porcentaje_iva);
  $("#stock_real").val(data.total_stock);
  $("#unidades_comprometidas").text(data.comprometidas || '');
  $("#costo_promedio").val(data.promedio);

  if (manejaLote) {
    $("#vcmto_grid").val(data.vencimiento);
    $("#id_lote").val(data.id_lote);
    $("#lote_grid").val(data.lote);
  }

  construirUnidades(data);
  const cantSelect = Number(undSel().val()) || 1;
  $("#cantidad_grid").prop("max", Math.floor(num($("#stock_real").val()) / cantSelect));
  $("#result-lotes").empty();

  // El change recalcula precio, stock, total y comisión (antes se calculaba 2 veces)
  $("#und_grid").trigger("change");
  enfocar('#cantidad_grid', 400);
};

const cargarLote = async (data) => {
  try {
    const resp = await enviarPeticion({
      link: '../shared/servicios/servicios.php',
      op: 'cargar-lotes',
      id_referencia: data.id,
      bodega: $("#bodegas").val()
    });
    const lotes = Array.isArray(resp) ? resp : [];

    if (!lotes.length) {
      cargarItemGrid({ ...data, id_lote: 0, lote: 0, vencimiento: '', total_stock: 0 });
      return;
    }
    if (lotes.length === 1) {
      const l = lotes[0];
      cargarItemGrid({ ...data, id_lote: l.id_lote, lote: l.lote, vencimiento: l.vencimiento, total_stock: num(l.stock) - num(l.comprometidas) });
      return;
    }

    // Se guarda en memoria y la fila solo lleva el índice (antes JSON en atributo, se rompía con comillas)
    lotesActuales = lotes.map(l => ({
      ...data,
      id_lote: l.id_lote,
      lote: l.lote,
      vencimiento: l.vencimiento,
      total_stock: num(l.stock) - num(l.comprometidas) - num(l.comp_scan)
    }));

    const filas = lotes.map((l, i) => {
      const disponible = num(l.stock) - num(l.comprometidas);
      return `
        <tr tabindex="${300 + i}" class="cursor-pointer" data-idx="${i}">
          <td><strong>${esc(l.lote)}</strong></td>
          <td class="text-center">${esc(l.vencimiento)}</td>
          <td class="text-center">${esc(l.stock)}</td>
          <td class="text-center"><span class="badge ${disponible > 0 ? 'bg-primary' : 'bg-danger'}">${disponible}</span></td>
        </tr>`;
    }).join('');

    $("#modalFechaLotes").modal("show");
    $("#result-lotes").html(`
      <div class="table-responsive">
        <table class="table table-sm table-hover table-bordered align-middle data-lotes mb-0">
          <thead class="table-primary"><tr class="text-center"><th>Lote</th><th>Vencimiento</th><th>Stock</th><th>Disponible</th></tr></thead>
          <tbody>${filas}</tbody>
        </table>
      </div>`);
    setTimeout(() => $(".data-lotes tbody tr:first").addClass("focus").trigger('focus'), 600);
  } catch (e) {
    console.error(e);
    alerta("Error", "Error al cargar los lotes", "error");
  }
};

/** Compatibilidad: se mantiene el nombre por si algún script global lo invoca */
const cargarItemGridLote = (ob) => {
  const lote = lotesActuales[parseInt($(ob).attr('data-idx'), 10)];
  if (!lote) return;
  cargarItemGrid(lote);
  $("#modalFechaLotes").modal("hide");
};

const recalcularLinea = () => {
  const cantidad = parseInt($("#cantidad_grid").val(), 10);
  if (!cantidad) {
    $("#total_grid").val(money(0));
    return;
  }
  const dto = $("#dto_grid").val();
  const iva = $("#iva_grid").val();
  const datosPrecio = calcularPrecioUnidad($("#precio_unitario").val(), undSel().val(), dto, iva);
  $("#preciob_grid").val(money(datosPrecio.preciob));
  $("#precion_grid").val(money(datosPrecio.precion));
  const totales = calcularTotal(datosPrecio.preciob, dto, iva, cantidad);
  $("#total_grid").val(money(totales.ttotal));
};

const onCambioUnidad = () => {
  const $option = undSel();
  if (!$option.length || $("#codigo_grid").val() === '') return;

  const data_precio = $option.attr("data-precio");
  const data_comision = num($option.attr("data-comision"));
  const cantidadUnidad = Number($option.val());
  const dto = $("#dto_grid").val();
  const iva = $("#iva_grid").val();

  const datosPrecio = calcularPrecioUnidad(data_precio, cantidadUnidad, dto, iva);
  $("#preciob_grid").val(money(datosPrecio.preciob));
  $("#precion_grid").val(money(datosPrecio.precion));

  $("#stock_grid").val(mostrarStockDisponible($("#stock_real").val(), cantidadUnidad, $option.text().split("-")[0]));

  const totales = calcularTotal(data_precio, dto, iva, num($("#cantidad_grid").val()) * cantidadUnidad);
  $("#total_grid").val(money(totales.ttotal));
  $("#precio_unitario").val(data_precio);

  // Comisión
  const $btn = $("#btn_comision_producto");
  let valorComision = 0;
  if ($("#und_grid").data("maneja-comision") === "S") {
    const porcentaje = $("#und_grid").data("calculo-comision") === "porcentaje";
    valorComision = porcentaje ? num(data_precio) * (data_comision / 100) : data_comision;
    $btn.removeClass("d-none")
      .attr("title", porcentaje ? `Producto con comisión del ${data_comision}%` : `Producto con comisión de ${money(data_comision)}`)
      .html(`<i class="fa ${porcentaje ? 'fa-percent' : 'fa-dollar-sign'}"></i>`);
  } else {
    $btn.addClass("d-none");
  }
  $("#comision_grid").val(valorComision);
  $("#cantidad_grid").trigger('focus').trigger('select');
};

/* ============================ CARRITO ============================ */
const filaCarrito = (it) => `
  <tr id="id_detalle${it.id}" data-id="${it.id}" data-codigo="${esc(it.codigo)}" data-lote="${esc(it.lote ?? '')}"
      data-und="${esc(it.und)}" data-cantidad="${it.cantidad}" data-can-und="${it.can_und}">
    <td data-label="Código">${esc(it.codigo)}</td>
    <td data-label="Descripción" class="td-full">${esc(it.descripcion)}-${esc(it.laboratorio)}</td>
    <td data-label="Precio b" class="text-end c-preciob">${it.preciob}</td>
    <td data-label="Dcto" class="text-center">${esc(it.dto)}</td>
    <td data-label="Iva" class="text-center">${esc(it.iva)}</td>
    <td data-label="Precio n" class="text-end c-precion">${it.precion}</td>
    <td data-label="Cant" class="text-center c-cant">${it.cantidad}</td>
    <td data-label="Und" class="text-center c-und">${esc(it.und)}</td>
    <td data-label="Conv" class="text-center c-conv">${it.can_und}</td>
    ${manejaLote ? `<td data-label="F.Vto">${esc(it.vcmto)}</td><td data-label="Lote">${esc(it.lote)}</td>` : ''}
    <td data-label="Total" class="text-end fw-semibold c-total">${it.total}</td>
    <td class="acciones text-nowrap text-end">
      <button class="btn btn-sm btn-outline-primary btn-editar-item" title="Editar"><i class="fa fa-pencil"></i></button>
      <button class="btn btn-sm btn-outline-danger btn-eliminar-item" title="Eliminar"><i class="fa-regular fa-trash-can"></i></button>
    </td>
  </tr>`;

const addCart = (item) => {
  $("#carrito tbody").append(filaCarrito(item));
  disabledEnabledTercero(true);
};

const actualizarFilaCarrito = (id, d) => {
  const $tr = $(`#id_detalle${id}`);
  if (!$tr.length) return;
  $tr.attr({ 'data-cantidad': d.cantidad, 'data-und': d.und, 'data-can-und': d.can_und });
  $tr.find('.c-preciob').text(d.preciob);
  $tr.find('.c-precion').text(d.precion);
  $tr.find('.c-cant').text(d.cantidad);
  $tr.find('.c-und').text(d.und);
  $tr.find('.c-conv').text(d.can_und);
  $tr.find('.c-total').text(d.total);
};

/** Busca una fila con el mismo código + unidad (+ lote si aplica). Antes usaba índices de columna fijos. */
const buscarFilaDuplicada = (codigo, lote, und) => {
  let $res = null;
  $("#carrito tbody tr").each(function () {
    const $tr = $(this);
    if (String($tr.attr('data-codigo')).toUpperCase() === codigo &&
        String($tr.attr('data-und')).toUpperCase() === und &&
        (lote === null || String($tr.attr('data-lote')).toUpperCase() === lote)) {
      $res = $tr;
      return false;
    }
  });
  return $res;
};

/** Reemplaza el prompt() "1. Sumar / 2. Reemplazar" (no usable en móvil). Devuelve 1, 2 o 0. */
const preguntarDuplicado = async (cantActual, und) => {
  const r = await Swal.fire({
    title: 'Producto ya agregado',
    html: `Ya se encuentran <b>${cantActual} ${esc(und)}</b> en el carrito.<br>¿Qué desea hacer?`,
    icon: 'question',
    showDenyButton: true,
    showCancelButton: true,
    confirmButtonText: 'Sumar',
    denyButtonText: 'Reemplazar',
    cancelButtonText: 'Cancelar'
  });
  if (r.isConfirmed) return 1;
  if (r.isDenied) return 2;
  return 0;
};

const crearEncabezado = async () => {
  const id_tercero_dir = $("#id_tercero_dir").val();
  if (!id_tercero_dir) {
    alerta("Error", "No se ha seleccionado una dirección para el tercero, asígnela e intente de nuevo", "error");
    return false;
  }
  const resp = await enviarPeticion({
    link: '../shared/servicios/servicios.php',
    op: 'add_transacciones_tmp',
    id_tipo_documento: $("#tipos").val(),
    id_tercero: $("#id_tercero").val(),
    id_bodega: $("#bodegas").val(),
    notas: $("#notas").val(),
    valor_total: 0,
    iva: 0,
    descuento: 0,
    sw: 5,
    id_tercero_dir,
    id_condicion: $("#id_condicion").val(),
    zona_ventas: $.trim($("#info_zona_venta").text()),
    zona_descripcion: $.trim($("#info_zona_descripcion").text()),
    es_credito: 0,
    lista: listaPreciosActual(),
    es_domicilio: esDomicilio() ? 1 : 0
  });

  // Antes se asignaban id/numero ANTES de validar el error
  if (resp?.error || !resp?.data?.id) {
    alerta("Error", "No se pudo insertar el documento", "error");
    return false;
  }
  $("#id_transaccion").val(resp.data.id);
  $("#numero").val(resp.data.numero);
  actualizarResumenMovil();
  firmaRetencionesGuardadas = '';
  return true;
};

const agregarItem = async () => {
  if (guardandoItem) return;   // evita doble Enter / doble toque
  if (!(parseInt($("#id_referencia").val(), 10) > 0)) return;

  const cantGrid = parseInt($("#cantidad_grid").val(), 10);
  if (!(cantGrid > 0)) {
    $("#total_grid").val(money(0));
    return;
  }

  guardandoItem = true;
  try {
    if (!(await validacionVendedor())) return;
    if (!validacionReferencia()) return;

    cargando('Guardando item...');

    if (!(parseInt($("#numero").val(), 10) > 0)) {
      if (!(await crearEncabezado())) return;
    }

    const $und = undSel();
    const preciob = unmoney($("#preciob_grid").val());
    const precion = unmoney($("#precion_grid").val());
    const und = $.trim($und.text().split("-")[0]).toUpperCase();
    const canUnd = parseInt($und.val(), 10) || 0;
    const codigo = $.trim($("#codigo_grid").val());
    const lote = $.trim($("#lote_grid").val() ?? '');
    const vencimiento = $("#vcmto_grid").val();

    let cantidad = cantGrid;
    let total = unmoney($("#total_grid").val());
    let op = 'add_transacciones_detalle_tmp';
    let idDet = parseInt($("#id_detalle").val(), 10) || 0;

    if (idDet > 0) {
      op = 'update_transacciones_detalle_tmp';
    } else {
      const $fila = buscarFilaDuplicada(codigo.toUpperCase(), manejaLote ? lote.toUpperCase() : null, und);
      if ($fila) {
        const cantActual = parseInt($fila.attr('data-cantidad'), 10) || 0;
        const opcion = await preguntarDuplicado(cantActual, und);
        if (!opcion) return;
        cargando('Guardando item...');
        op = 'update_transacciones_detalle_tmp';
        idDet = parseInt($fila.attr('data-id'), 10);
        if (opcion === 1) cantidad = cantActual + cantGrid;
        total = cantidad * precion;
      }
    }

    const esProductoConLote = manejaLote && $("#tipo_referencia").val() === 'P';
    const resDet = await enviarPeticion({
      link: '../shared/servicios/servicios.php',
      op,
      id_transaccion: $("#id_transaccion").val(),
      id_referencia: $("#id_referencia").val(),
      cantidad: cantidad * canUnd,
      valor_unitario: num($("#precio_unitario").val()),
      costo_unitario: num($("#costo_promedio").val()),
      pcj_iva: num($("#iva_grid").val()),
      pcj_descuento: num($("#dto_grid").val()),
      und,
      can_und: canUnd,
      sw: 5,
      id_bodega: $("#bodegas").val(),
      id_pedido: 0,
      comision: num($("#comision_grid").val()) * cantidad,
      id: idDet,
      id_lote: esProductoConLote ? $("#id_lote").val() : 0,
      vmcto: esProductoConLote ? vencimiento : '1900-01-01',
      lote: esProductoConLote ? lote : '0'
    });

    if (!resDet || resDet.error) {
      alerta("Error", "No se pudo insertar el producto, por favor intente nuevamente!", "error");
      return;
    }

    // Foto de la grilla para que "Editar" sea instantáneo
    const idFila = op === 'add_transacciones_detalle_tmp' ? resDet.data.id : idDet;
    cacheCarrito.set(String(idFila), tomarSnapshotGrid());

    // El DOM se actualiza solo después de que el servidor confirma (antes era al revés)
    const datosFila = { preciob: money(preciob), precion: money(precion), cantidad, und, can_und: canUnd, total: money(total) };
    if (op === 'add_transacciones_detalle_tmp') {
      addCart({
        id: resDet.data.id,
        codigo,
        descripcion: $("#descripcion_grid").val(),
        laboratorio: $("#laboratorio_grid").val(),
        dto: $("#dto_grid").val(),
        iva: $("#iva_grid").val(),
        vcmto: vencimiento,
        lote,
        ...datosFila
      });
    } else {
      actualizarFilaCarrito(idDet, datosFila);
    }

    limpiarReferencia();
    await actualizarTotales(resDet.data.totales[0]);
    cerrarCarga();
    enfocar('#filtro', 300);
  } catch (e) {
    console.error(e);
    alerta("Error", "Se generó un error interno en la aplicación", "error");
  } finally {
    guardandoItem = false;
  }
};

const eliminarItem = async (id) => {
  const r = await Swal.fire({
    title: "¿Está seguro de eliminar el producto?",
    text: "Esta acción no puede reversarse!",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí",
    cancelButtonText: "Cancelar"
  });
  if (!r.isConfirmed) return;

  cargando('Eliminando producto...');
  try {
    const resp = await enviarPeticion({
      link: '../shared/servicios/servicios.php',
      op: 'delete_item',
      id,
      id_transaccion: $("#id_transaccion").val()
    });
    if (resp.error) {
      alerta("Error", resp.message, "error");
      return;
    }
    if (resp.total_reg == 0) {
      reiniciarDocumento({ conservarTercero: true });
      cerrarCarga();
      return;
    }
    $(`#id_detalle${id}`).remove();
    cacheCarrito.delete(String(id));
    await actualizarTotales(resp.totales[0]);
    cerrarCarga();
  } catch (e) {
    console.error(e);
    alerta("Error", "No fue posible eliminar el producto", "error");
  }
};

/* ---------- Edición rápida ----------
 * Antes cada "Editar" consultaba detalle_transacciones_tmp (vistas de precios,
 * stock y comprometidas) y tardaba varios segundos. Ahora se guarda una foto de
 * la grilla al agregar el producto y la edición se carga sin ir al servidor.
 * Solo se consulta el servidor la primera vez para ítems de un pedido recuperado.
 */
const CAMPOS_SNAPSHOT = ['#id_referencia', '#tipo_referencia', '#descripcion_grid', '#laboratorio_grid', '#codigo_grid',
  '#dto_grid', '#iva_grid', '#stock_real', '#costo_promedio', '#id_lote', '#lote_grid', '#vcmto_grid'];

const tomarSnapshotGrid = () => ({
  data: itemActual ? { ...itemActual } : null,
  valores: Object.fromEntries(CAMPOS_SNAPSHOT.map(sel => [sel, $(sel).val()])),
  comprometidas: $("#unidades_comprometidas").text()
});

const restaurarSnapshotGrid = (id, snap) => {
  const $tr = $(`#id_detalle${id}`);
  if (!$tr.length || !snap?.data) return false;

  limpiarReferencia();
  itemActual = { ...snap.data };
  Object.entries(snap.valores).forEach(([sel, v]) => $(sel).val(v));
  $("#unidades_comprometidas").text(snap.comprometidas || '');

  construirUnidades(itemActual);
  $("#und_grid").val(String($tr.attr('data-can-und')));
  $("#cantidad_grid").val($tr.attr('data-cantidad'));
  $("#id_detalle").val(id);
  $("#und_grid").trigger("change");   // recalcula precio, total y comisión
  return true;
};

const editarPedido = async (id) => {
  if (cacheCarrito.has(String(id)) && restaurarSnapshotGrid(id, cacheCarrito.get(String(id)))) {
    if (window.matchMedia('(max-width: 767.98px)').matches) {
      document.querySelector('.div-table-data-referencia')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    return;
  }

  try {
    cargando('Cargando información...');
    const resp = await enviarPeticion({ link: '../shared/servicios/servicios.php', op: 'detalle_transacciones_tmp', id });
    if (!resp?.length) {
      alerta("Error", "No se encontraron items", "error");
      return;
    }

    const item = resp[0];
    itemActual = item;
    const cantidadItem = num(item.cantidad);
    const stockTotal = num(item.total_stock) - (num(item.comprometidas) - cantidadItem);

    $("#id_referencia").val(item.id);
    $("#tipo_referencia").val(item.tipo);
    $("#descripcion_grid").val(item.descripcion);
    $("#laboratorio_grid").val(item.laboratorio);
    $("#codigo_grid").val(item.codigo);
    $("#precio_unitario").val(num(item.valor_unitario));
    $("#costo_promedio").val(num(item.costo_unitario));
    $("#stock_real").val(stockTotal);
    $("#id_detalle").val(item.id_detalle);
    $("#unidades_comprometidas").text(num(item.comprometidas) || '');
    $("#dto_grid").val(num(item.pcj_descuento));
    $("#iva_grid").val(num(item.iva));
    $("#id_transaccion").val(item.id_transaccion);

    if (manejaLote) {
      $("#id_lote").val(item.id_lote);
      $("#lote_grid").val(item.lote);
      $("#vcmto_grid").val(item.vencimiento);
    }

    construirUnidades(item);
    $("#und_grid").val(item.can_und);
    $("#cantidad_grid").val(cantidadItem / (num(item.can_und) || 1));
    $("#und_grid").trigger("change");
    cacheCarrito.set(String(id), tomarSnapshotGrid());   // la siguiente edición ya no consulta el servidor
    cerrarCarga();
  } catch (e) {
    console.error(e);
    alerta("Error", "Ha ocurrido un error al intentar obtener los datos!", "error");
  }
};

/* ============================ TEMPORALES ============================ */
const recuperarPedido = async (item) => {
  cargando('Cargando pedido...');
  try {
    nuevoPedido();

    $("#tipos").val(item.id_tipo_documento);
    $("#id_tercero").val(item.id_tercero);
    $("#nit").val(item.nit);
    $("#tratamiento").val(item.nombres);
    $("#razon_comercial").val(item.razon_comercial);
    $("#textDireccion").val(item.direccion);
    $("#lista_tercero").val(parseInt(item.lista_precios, 10) || 0);
    actualizarPanelCliente();
    setDomicilio(item.es_domicilio == 1);
    $("#info_condicion").text(item.des_condicion ?? '');
    $("#info_zona_venta").text(item.zona_ventas ?? '');
    pintarInfoTercero(item);

    // 4 peticiones en paralelo (antes secuenciales)
    const [okDir, items] = await Promise.all([
      cargarDirecciones(item.id_tercero),
      enviarPeticion({ link: '../shared/servicios/servicios.php', op: 'busqueda_temporal_det', id_transaccion: item.id_transaccion, ligero: 1 }),
      cargarPerfilTributarioDesdeTercero(item.id_tercero, item.id_perfil_tributario),
      listarConceptosRetencion()
    ]);

    if (!okDir) {
      sinDirecciones();
      return;
    }
    if (!items?.length) {
      alerta("Error", "No se encontraron items", "error");
      nuevoPedido();
      return;
    }

    $("#id_tercero_dir").val(item.id_tercero_dir);
    $("#id_transaccion").val(item.id_transaccion);
    $("#numero").val(item.numero);
    actualizarResumenMovil();
    $("#notas").val(item.notas);

    // Se pintan los totales y las retenciones GUARDADAS sin reescribirlas en BD.
    // Antes se guardaban en 0 (perfil aún no cargado) y luego el pago no cuadraba con el servidor.
    pintarTotales(item);
    restaurarRetencionesEncabezado(item);
    firmaRetencionesGuardadas = [item.id_transaccion, num(item.retencion_fuente).toFixed(2), num(item.retencion_ica).toFixed(2), num(item.retencion_iva).toFixed(2), parseInt(item.id_concepto_retencion || '0', 10)].join('|');

    // Render en un solo append (antes forEach async + append por fila)
    $("#carrito tbody").html(items.map(d => {
      const dp = calcularPrecioUnidad(d.valor_unitario, d.can_und, d.pcj_descuento, d.pcj_iva);
      const cantidad = num(d.cantidad) / (num(d.can_und) || 1);
      return filaCarrito({
        id: d.id_transaccion_det_tmp,
        codigo: d.codigo,
        descripcion: d.descripcion,
        laboratorio: d.laboratorio,
        preciob: money(dp.preciob),
        dto: d.pcj_descuento,
        iva: d.pcj_iva,
        precion: money(dp.precion),
        cantidad,
        und: d.und,
        can_und: d.can_und,
        vcmto: d.vmcto,
        lote: d.lote,
        total: money(Math.round(dp.precion * cantidad))
      });
    }).join(''));

    disabledEnabledTercero(true);
    limpiarReferencia();
    mostrarTab('#navs-creacion');
    cerrarCarga();
    enfocar('#filtro', 300);
  } catch (e) {
    console.error(e);
    nuevoPedido();
    alerta("Error", "No fue posible recuperar el pedido", "error");
  }
};

const eliminarPedido = async (data) => {
  const r = await Swal.fire({
    title: "¿Está seguro de eliminar el pedido?",
    text: "Esta acción no puede reversarse!",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí",
    cancelButtonText: "Cancelar"
  });
  if (!r.isConfirmed) return;

  try {
    const resp = await enviarPeticion({ link: '../shared/servicios/servicios.php', id_transaccion: data.id_transaccion, op: 'delete_transaccion' });
    if (resp.error) {
      alerta("Ups", "Error al eliminar el pedido", "error");
      return;
    }
  } catch (e) {
    console.error(e);
  }
  nuevoPedido();
  await buscarTemporales();
  cargarTercerosDefault();
};

const buscarTemporales = async () => {
  try {
    cargando('Buscando información...');
    const resp = await enviarPeticion({
      op: 'busqueda_temporal',
      link: '../shared/servicios/servicios.php',
      fecha_inicial: $.trim($("#fecha_inicial").val()),
      fecha_final: $.trim($("#fecha_final").val()),
      nit: $("#nit_tmp").val(),
      sw: 5,
      es_credito: 0
    });

    temporalesData = Array.isArray(resp?.data) ? resp.data : [];
    if (!temporalesData.length) {
      cerrarCarga();
      $("#temporales").html(sinResultados());
      return;
    }

    // El badge de domicilio se pinta aparte: antes se concatenaba HTML dentro de item.numero
    // y al "Editar" el número quedaba vacío en el input numérico (se creaba un documento nuevo).
    const badgeDomicilio = `<span class="ms-1 badge rounded-pill bg-label-primary" title="Servicio a domicilio"><i class="fa-solid fa-motorcycle"></i></span>`;

    const filas = temporalesData.map((item, i) => `
      <tr data-index="${i}">
        <td data-label="Fecha">${esc(item.fecha_hora)}</td>
        <td data-label="Número">${esc(item.numero)}${parseInt(item.es_domicilio, 10) === 1 ? badgeDomicilio : ''}</td>
        <td data-label="Cliente" class="td-full">${esc(item.nombres)}</td>
        <td data-label="Razón comercial">${esc(item.razon_comercial)}</td>
        <td data-label="Dto" class="text-end">${money(item.descuento)}</td>
        <td data-label="Iva" class="text-end">${money(item.iva)}</td>
        <td data-label="Total" class="text-end fw-semibold">${money(item.valor_total)}</td>
        <td class="acciones text-center">
          <div class="dropdown">
            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="fa-solid fa-ellipsis-vertical"></i> Opciones
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="javascript:void(0)" data-accion="editar"><i class="fa-regular fa-pen-to-square me-2 text-primary"></i>Editar</a></li>
              <li><a class="dropdown-item" href="javascript:void(0)" data-accion="imprimir"><i class="fa fa-print me-2 text-primary"></i>Imprimir</a></li>
              <li><a class="dropdown-item" href="javascript:void(0)" data-accion="email"><i class="fa-solid fa-envelope me-2 text-primary"></i>Enviar correo</a></li>
              <li><a class="dropdown-item" href="javascript:void(0)" data-accion="whatsapp"><i class="fab fa-whatsapp me-2 text-primary"></i>Enviar por WhatsApp</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="javascript:void(0)" data-accion="eliminar"><i class="fa-regular fa-trash-can me-2"></i>Eliminar</a></li>
            </ul>
          </div>
        </td>
      </tr>`).join('');

    $("#temporales").html(`
      <div class="table-responsive">
        <table id="pedidos-temp" class="table table-sm table-hover datos tabla-cards">
          <thead class="table-primary">
            <tr><th>Fecha</th><th>Número</th><th>Cliente</th><th>Razón comercial</th><th class="text-end">Dto</th><th class="text-end">Iva</th><th class="text-end">Total</th><th class="text-center">Opciones</th></tr>
          </thead>
          <tbody>${filas}</tbody>
        </table>
      </div>`);
    initDropdownsFixed($("#temporales"));
    cerrarCarga();
  } catch (e) {
    console.error(e);
    alerta("Error", "Ha ocurrido un error al obtener los pedidos", "error");
  }
};

const imprimirFila = (item) => {
  $("#modalDoc").remove();
  $("body").append(`
    <div class="modal fade" id="modalDoc" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-fullscreen-md-down" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Cotización- ${esc(item.id_transaccion)}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body p-0">
            <iframe width="100%" style="height:75vh; border:none;" src="../pdf/factura-tmp-pos.php?id=${encodeURIComponent(item.id_transaccion)}"></iframe>
          </div>
        </div>
      </div>
    </div>`);
  $("#modalDoc").modal("show");
};

/* ============================ PAGOS ============================ */
const soloContado = (data) => data.filter(item => item.dias == 0);

const consultarMediosPgos = async () => {
  try {
    const resp = await enviarPeticion({ link: '../models/MediosPagos.php', op: 'listar', filtro: '' });
    if (!resp?.data?.length) return;
    $("#forma_pago").html(resp.data.map(item =>
      `<option value="${esc(item.id)}" data-element="${esc(JSON.stringify(item))}">${esc(item.descripcion)}</option>`
    ).join(''));
  } catch (e) {
    console.error(e);
  }
};

const formaPagoSeleccionada = () => {
  const attr = $("#forma_pago option:selected").attr("data-element");
  if (!attr) return null;
  try { return JSON.parse(attr); } catch { return null; }
};

const renderPagos = () => {
  $("#table-pagos tbody").html(pagosRegistrados.map(p => `
    <tr>
      <td data-label="Id">${esc(p.id_medio_pago)}</td>
      <td data-label="Forma" class="td-full">${esc(p.descripcion)}</td>
      <td data-label="Valor" class="text-end">${money(p.valor)}</td>
      <td data-label="Tarjeta">${esc(p.tipo_tarjeta_txt)}</td>
      <td data-label="Voucher">${esc(p.voucher)}</td>
      <td class="acciones text-end"><button class="btn btn-sm btn-outline-danger btn-eliminar-pago" data-id="${esc(p.id_medio_pago)}" title="Eliminar"><i class="fa fa-trash"></i></button></td>
    </tr>`).join(''));
  calcularValoresPagos();
};

const calcularValoresPagos = () => {
  let totalPagado = 0, totalNoEfectivo = 0, recibidoEfectivo = 0;
  pagosRegistrados.forEach(p => {
    totalPagado += p.valor;
    if (p.naturaleza === 'E') recibidoEfectivo += p.recibido;   // antes se concatenaban strings aquí
    else totalNoEfectivo += p.valor;
  });
  const cambio = Math.max(0, recibidoEfectivo - (totalFacturaPago - totalNoEfectivo));
  const faltante = Math.max(0, totalFacturaPago - totalPagado);

  $("#cambio").text(money(cambio));
  $("#valor_pago").text(money(totalPagado));
  $("#faltante").text(money(faltante));
  return { totalPagado, cambio, faltante };
};

const addPay = () => {
  const forma = formaPagoSeleccionada();
  if (!forma) {
    alerta('Oops', 'Por favor selecciona un método de pago válido.');
    return;
  }

  const valorRecibido = unmoney($("#valor_recibido").val());
  if (!(valorRecibido > 0)) {
    alerta("Error", "Debe digitar el valor");
    return;
  }

  // Si el medio ya existe se reemplaza: el faltante se calcula sin él
  const otros = pagosRegistrados.filter(p => p.id_medio_pago != forma.id);
  const pagadoOtros = otros.reduce((s, p) => s + p.valor, 0);
  const faltante = totalFacturaPago - pagadoOtros;

  if (faltante <= 0) {
    alerta('Atención', 'La factura ya se encuentra totalmente pagada.', 'info');
    return;
  }
  // Solo el efectivo puede superar el faltante (genera cambio)
  if (forma.naturaleza !== 'E' && valorRecibido > faltante) {
    alerta("Oops", "El valor recibido no puede ser mayor al de la factura!");
    return;
  }

  const voucher = $.trim($("#voucher").val() || '');
  if (forma.naturaleza === 'B' && esS(ConfModulos?.solicitarVoucher) && voucher === '') {
    initToast('bg-danger', 'Imposible continuar', 'Es obligatorio diligenciar el voucher para el método de pago.', 'danger');
    enfocar('#voucher', 100);
    return;
  }

  pagosRegistrados = [...otros, {
    id_medio_pago: forma.id,
    descripcion: forma.descripcion,
    naturaleza: forma.naturaleza,
    valor: Math.min(valorRecibido, faltante),
    recibido: valorRecibido,
    tipo_tarjeta: $("#tipo_tarjeta").val() || '0',
    tipo_tarjeta_txt: $("#tipo_tarjeta option:selected").text() || '',
    voucher
  }];
  renderPagos();

  $("#valor_recibido").val("");
  $('#forma_pago').prop('selectedIndex', 0);
  aplicarEstadoFormaPago();
  $("#valor_recibido").trigger('focus');
};

const eliminarPago = (id) => {
  pagosRegistrados = pagosRegistrados.filter(p => p.id_medio_pago != id);
  renderPagos();
};

const eliminarPagoTemp = async (id_transaccion) => {
  try {
    await enviarPeticion({ link: '../models/Pos.php', op: 'eliminarPago', id_transaccion });
  } catch (e) {
    console.error(e);
  }
};

const abrirPagos = () => {
  // Si ya está abierto (p. ej. F4 de nuevo) no se reinician los pagos digitados
  if ($("#modalMediosPagos").hasClass('show')) return;
  if (!(parseInt($("#numero").val(), 10) > 0) || !$("#carrito tbody tr").length) {
    alerta("Error", "No se encontró un número de documento o el carrito está vacío");
    return;
  }
  reiniciarPagos();
  totalFacturaPago = unmoney($("#t_neto_pagar").val());
  $("#valor_factura").text(money(totalFacturaPago));
  calcularValoresPagos();
  $("#modalMediosPagos").modal("show");
};

const confirmarGuardado = () => new Promise((resolve) => {
  const modalElement = document.getElementById("modalConfirmarGuardar");
  const modal = bootstrap.Modal.getOrCreateInstance(modalElement, { backdrop: "static", keyboard: false });
  const $aceptar = $("#btnAceptarConfirm");
  const $cancelar = $("#btnCancelarConfirm");
  let seleccionado = "aceptar";

  const actualizarFoco = () => (seleccionado === "aceptar" ? $aceptar : $cancelar).trigger("focus");
  const cerrar = (resultado) => {
    $(document).off("keydown.confirmar");
    modal.hide();
    resolve(resultado);
  };

  $aceptar.off("click.confirmar").on("click.confirmar", () => cerrar(true));
  $cancelar.off("click.confirmar").on("click.confirmar", () => cerrar(false));

  $(modalElement).off("shown.bs.modal.confirmar").on("shown.bs.modal.confirmar", () => {
    $(modalElement).css("z-index", 12000);
    $(".modal-backdrop").last().css("z-index", 11990);
    seleccionado = "aceptar";
    setTimeout(actualizarFoco, 100);
  });

  $(document).off("keydown.confirmar").on("keydown.confirmar", (e) => {
    if (!$(modalElement).hasClass("show")) return;
    switch (e.key) {
      case "ArrowLeft":
      case "ArrowRight":
        seleccionado = seleccionado === "aceptar" ? "cancelar" : "aceptar";
        actualizarFoco();
        e.preventDefault();
        break;
      case "Enter":
        (seleccionado === "aceptar" ? $aceptar : $cancelar).trigger("click");
        e.preventDefault();
        break;
      case "Escape":
        $cancelar.trigger("click");
        break;
    }
  });
  modal.show();
});

const mostrarStockInsuficiente = (productos) => {
  const filas = productos.map(p => `
    <tr>
      <td>${esc(p.codigo)} - ${esc(p.producto)}</td>
      <td class="text-end">${esc(p.stock_actual)}</td>
      <td class="text-end">${esc(p.cant_solicitada)}</td>
      <td class="text-end text-danger fw-bold">${num(p.cant_solicitada) - num(p.stock_actual)}</td>
    </tr>`).join('');
  Swal.fire({
    icon: "error",
    title: "Stock insuficiente",
    html: `<div class="table-responsive"><table class="table table-sm table-bordered">
             <thead class="table-primary"><tr><th>Producto</th><th>Stock</th><th>Pedido</th><th>Faltante</th></tr></thead>
             <tbody>${filas}</tbody></table></div>`,
    width: 800
  });
};

const guardarDocumento = async () => {
  if (guardandoDocumento) return;   // evita doble guardado (doble clic / doble Enter)
  guardandoDocumento = true;
  const id_transaccion = $("#id_transaccion").val();

  try {
    cargando('Verificando documento...');
    // Se valida de nuevo la caja por si se cerró con la pantalla abierta
    if (!(await validarAperturaCaja())) return;

    if (!pagosRegistrados.length) {
      alerta("Oops", "Debe agregar un pago!", "error");
      return;
    }
    const { totalPagado, cambio } = calcularValoresPagos();
    if (totalFacturaPago - totalPagado > 0.009) {
      alerta("Oops", "No se ha completado el pago!", "error");
      return;
    }

    cerrarCarga();
    if (!(await confirmarGuardado())) return;

    if (esS(ConfGeneral.solicitarVendedor) && esS(ConfModulos.solicitarAlGuardar)) {
      if (!(await validarCodigoVendedor({ numero: $("#numero").val(), tipo: $("#tipos").val() }))) return;
    }

    cargando('Guardando documento...');
    const numero = $("#numero").val();
    const tipo = $.trim($("#tipos option:selected").text());

    const repPago = await enviarPeticion({
      link: '../models/Pos.php',
      op: 'insertarPago',
      pagos: JSON.stringify(pagosRegistrados.map(p => ({
        id_transaccion,
        id_medio_pago: parseInt(p.id_medio_pago, 10),
        valor: p.valor,
        tipo_tarjeta: p.tipo_tarjeta,
        voucher: p.voucher,
        numero,
        tipo,
        cambio
      }))),
      numero,
      tipo,
      valor_total: totalFacturaPago,
      id_transaccion
    });

    if (!repPago?.ok) {
      alerta("Error", repPago?.message || "No fue posible registrar los pagos", "error");
      return;
    }

    const respDoc = await enviarPeticion({
      link: '../shared/servicios/servicios.php',
      op: 'volcar_documento',
      id_transaccion,
      updateVendedor: ConfModulos.solicitarAlGuardar,
      vendedor: $("#vendedor").val()
    });

    if (!respDoc?.ok) {
      if (respDoc?.productos?.length) mostrarStockInsuficiente(respDoc.productos);
      else alerta("Error", respDoc?.message || "No fue posible guardar el documento", "error");
      eliminarPagoTemp(id_transaccion);
      return;
    }

    const idTransaccion = respDoc.id;

    // El documento ya quedó guardado: un fallo aquí no debe impedir limpiar la pantalla
    try {
      await generarContabilizacion(idTransaccion);
    } catch (e) {
      console.error('Error en contabilización:', e);
    }

    cerrarCarga();
    nuevoPedido();
    cargarTercerosDefault();
    ImprimirDocumento(5, idTransaccion, 'Impresion de pedido de venta', false);

    if (permisos.FacturaElectronicaAutomatica) {
      enviarDocumentoElctronico(idTransaccion, 5).catch(e => console.error(e));
    }
  } catch (e) {
    console.error(e);
    eliminarPagoTemp(id_transaccion);
    alerta("Ups", "Se generó un error al guardar el documento", "error");
  } finally {
    guardandoDocumento = false;
  }
};

/* ============================ FACTURAS ELECTRÓNICAS ============================ */
const enviarFacturaElectronica = async (id) => {
  try {
    cargando('Enviando factura...');
    const resp = await enviarDocumentoElctronico(id, 5);
    if (resp.data.IsValid == 'false') {
      const err = resp.data?.ErrorMessage;
      if (Array.isArray(err)) alerta("Error", generarListaHTML(err), "error");
      else if (err) alerta("Error", err, "error");
      else cerrarCarga();
      return;
    }
    await Swal.fire("Ok", "Documento enviado correctamente", "success");
    await listarFacturas();
  } catch (e) {
    console.error(e);
    alerta("Ups", "Ha ocurrido un error al enviar el documento", "error");
  }
};

const reenviarMail = async (tipo, numero, email) => {
  const result = await Swal.fire({
    title: 'Reenviar correo',
    input: 'email',
    inputLabel: 'Dirección de correo',
    inputValue: email,
    showCancelButton: true,
    confirmButtonText: 'Enviar',
    cancelButtonText: 'Cancelar',
    inputValidator: (value) => (!value ? 'Debes ingresar un correo válido' : undefined)
  });
  if (!result.isConfirmed) return;

  const nuevoEmail = result.value;
  if (!validarEmail(nuevoEmail)) {
    alerta('Error', 'El email es inválido!', 'error');
    return;
  }

  cargando('Enviando correo...');
  try {
    const resp = await enviarPeticion({ link: '../models/Fe/Controllers/ReenviarCorreo.php', numero, tipo, nuevoEmail });
    if (resp.data.error) alerta('Error', resp.data.mensaje, 'error');
    else alerta('Excelente!', resp.data.mensaje, 'success');
  } catch (e) {
    console.error('Error al reenviar correo:', e);
    alerta('Error', 'Ocurrió un error al enviar el correo', 'error');
  }
};

const listarFacturas = async () => {
  try {
    cargando('Consultando información...');
    const resp = await enviarPeticion({
      link: '../models/Pos.php',
      op: 'listar-facturas',
      fecha_inicial: $("#fe_fecha_inicial").val(),
      fecha_final: $("#fe_fecha_final").val()
    });
    cerrarCarga();

    facturasData = Array.isArray(resp) ? resp : [];
    if (!facturasData.length) {
      $("#listado-facturas").html(sinResultados());
      return;
    }

    const conFE = data_tipos.documento_electronico === 'S';
    const filas = facturasData.map((item, i) => {
      const esElectronica = item.documento_electronico === 'S';
      const enviada = esElectronica && item.cufe !== '';
      const estado = !esElectronica
        ? `<span class="badge bg-label-secondary">No aplica</span>`
        : enviada
          ? `<span class="badge bg-label-primary"><i class="fa-solid fa-circle-check"></i> Enviado</span>`
          : `<span class="badge bg-label-danger"><i class="fa-solid fa-circle-exclamation"></i> Pendiente</span>`;

      return `
        <tr data-index="${i}">
          <td data-label="Número">${esc(item.numero)}</td>
          <td data-label="Tipo">${esc(item.tipo)}</td>
          <td data-label="Fecha">${esc(item.fecha)}</td>
          <td data-label="Nit">${esc(item.nit)}</td>
          <td data-label="Cliente" class="td-full">${esc(item.cliente)}<br><small class="text-primary">${esc(item.razon_comercial)}</small></td>
          <td data-label="Valor total" class="text-end fw-semibold">${money(item.valor_total)}</td>
          <td data-label="Electrónica" class="text-center">${esElectronica ? '<span class="badge bg-label-primary">Sí</span>' : '<span class="badge bg-label-secondary">No</span>'}</td>
          ${conFE ? `<td data-label="Email" class="text-break">${esc(item.email)}</td><td data-label="Estado" class="text-center">${estado}</td>` : ''}
          <td class="acciones text-center">
            <div class="dropdown">
              <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa-solid fa-ellipsis-vertical"></i> Opciones
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="javascript:void(0)" data-accion="imprimir"><i class="fa-solid fa-print me-2 text-primary"></i>Imprimir</a></li>
                <li><a class="dropdown-item" href="javascript:void(0)" data-accion="whatsapp"><i class="fab fa-whatsapp me-2 text-primary"></i>Enviar por WhatsApp</a></li>
                <li><a class="dropdown-item" href="javascript:void(0)" data-accion="email"><i class="fa-solid fa-envelope me-2 text-primary"></i>Enviar correo</a></li>
                <li><a class="dropdown-item" href="javascript:void(0)" data-accion="exportar"><i class="fa-solid fa-file-excel me-2 text-primary"></i>Exportar Excel - CSV</a></li>
                ${esElectronica && !enviada ? `<li><a class="dropdown-item" href="javascript:void(0)" data-accion="fe"><i class="fa-solid fa-paper-plane me-2 text-primary"></i>Enviar factura electrónica</a></li>` : ''}
              </ul>
            </div>
          </td>
        </tr>`;
    }).join('');

    $("#listado-facturas").html(`
      <div class="table-responsive">
        <table class="table table-sm table-hover datos tabla-cards">
          <thead class="table-primary">
            <tr>
              <th>Número</th><th>Tipo</th><th>Fecha</th><th>Nit</th><th>Cliente</th><th class="text-end">Valor total</th><th>Electrónica</th>
              ${conFE ? '<th>Email</th><th>Estado</th>' : ''}
              <th class="text-center">Opciones</th>
            </tr>
          </thead>
          <tbody>${filas}</tbody>
        </table>
      </div>`);
    initDropdownsFixed($("#listado-facturas"));
  } catch (e) {
    console.error(e);
    alerta("Error", "Ocurrió un error consultando las facturas", "error");
  }
};

/** Corrige el desfase de un día: new Date('2026-10-12') se interpreta en UTC y en Bogotá mostraba el 11 */
const formatearFecha = (fecha) => {
  if (!fecha) return '';
  const m = String(fecha).match(/^(\d{4})-(\d{2})-(\d{2})/);
  if (m) return `${m[3]}-${m[2]}-${m[1]}`;
  const d = new Date(fecha);
  return isNaN(d.getTime()) ? fecha : `${String(d.getDate()).padStart(2, '0')}-${String(d.getMonth() + 1).padStart(2, '0')}-${d.getFullYear()}`;
};

const obtenerDatosFactura = (id) => enviarPeticion({ link: '../shared/servicios/servicios.php', op: 'exportar-factura', id });

const descargarBlob = (blob, nombre) => {
  const url = URL.createObjectURL(blob);
  const a = Object.assign(document.createElement('a'), { href: url, download: nombre });
  document.body.appendChild(a);
  a.click();
  a.remove();
  URL.revokeObjectURL(url);
};

/** Exportación unificada (antes exportarExcel y exportarCSV duplicaban consulta y validación) */
const exportarFactura = async (id, formato) => {
  try {
    cargando(`Generando ${formato === 'xlsx' ? 'Excel' : 'CSV'}...`);
    const respuesta = await obtenerDatosFactura(id);
    if (!respuesta?.ok || !respuesta.data?.length) {
      Swal.fire({ icon: 'warning', title: 'Sin datos', text: 'No hay información para exportar.' });
      return;
    }
    const datos = respuesta.data.map(f => ({ ...f, vencimiento: formatearFecha(f.vencimiento) }));

    if (formato === 'xlsx') {
      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(datos), 'Factura');
      XLSX.writeFile(wb, `factura_${id}.xlsx`);
    } else {
      const encabezados = Object.keys(datos[0]);
      const csv = [
        encabezados.join(';'),
        ...datos.map(f => encabezados.map(c => `"${String(f[c] ?? '').replace(/"/g, '""')}"`).join(';'))
      ].join('\r\n');
      descargarBlob(new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' }), `factura_${id}.csv`);
    }
    cerrarCarga();
  } catch (e) {
    console.error(e);
    Swal.fire({ icon: 'error', title: 'Error', text: `No fue posible exportar la factura a ${formato === 'xlsx' ? 'Excel' : 'CSV'}.` });
  }
};
const exportarExcel = (id) => exportarFactura(id, 'xlsx');
const exportarCSV = (id) => exportarFactura(id, 'csv');

const exportarXLS = async (id) => {
  const r = await Swal.fire({
    title: '¿Cómo deseas exportar?',
    text: 'Selecciona el formato de exportación',
    icon: 'question',
    showCancelButton: true,
    showDenyButton: true,
    confirmButtonText: 'Excel',
    denyButtonText: 'CSV',
    cancelButtonText: 'Cancelar'
  });
  if (r.isConfirmed) exportarExcel(id);
  if (r.isDenied) exportarCSV(id);
};

const opcionImpresion = async (idItem) => {
  const { isConfirmed, isDenied } = await Swal.fire({
    title: 'Selecciona tipo de impresión',
    text: '¿Cómo deseas imprimir el documento?',
    icon: 'question',
    showCancelButton: true,
    showDenyButton: true,
    confirmButtonText: 'Tirilla',
    denyButtonText: 'Carta',
    cancelButtonText: 'Cancelar',
    buttonsStyling: false,
    reverseButtons: true,
    customClass: {
      actions: 'd-flex gap-2',
      confirmButton: 'btn btn-sm btn-primary',
      denyButton: 'btn btn-sm btn-outline-primary',
      cancelButton: 'btn btn-sm btn-outline-secondary'
    }
  });
  if (!isConfirmed && !isDenied) return;
  ImprimirDocumento(5, idItem, isConfirmed ? 'Factura de venta' : 'Factura carta', !isConfirmed);
};

/* ============================ CAJA / VENDEDOR / VALIDACIONES ============================ */
const pantallaBloqueo = ({ tipo = 'danger', icono, titulo, contenido }) => {
  $("body").html(`
    <div class="container d-flex justify-content-center align-items-center vh-100 px-3">
      <div class="alert alert-${tipo} text-center shadow-lg p-4" style="max-width:500px;">
        <h4 class="alert-heading"><i class="fas ${icono} me-2"></i>${titulo}</h4>
        <hr>
        ${contenido}
        <a href="AperturaCierreCaja.php?redirect=1" class="btn btn-sm btn-${tipo} mt-3">
          <i class="fas fa-door-open me-1"></i> Ir a Apertura y Cierre de Caja
        </a>
      </div>
    </div>`);
};

const validarAperturaCaja = async () => {
  try {
    const resp = await enviarPeticion({ link: '../models/AperturaCierreCaja.php', op: 'info' });

    if (resp.id_apertura == 0) {
      cerrarCarga();
      pantallaBloqueo({
        icono: 'fa-exclamation-triangle',
        titulo: 'Atención',
        contenido: `<i class="fas ${permisos.abrirModuloApertura ? 'fa-cash-register' : 'fa-lock'} fa-2x text-danger mb-3"></i>
                    <h5 class="mb-3">${permisos.abrirModuloApertura ? 'No hay apertura de caja' : 'Caja no abierta'}</h5>
                    <p>Es necesario realizar la <strong>apertura de caja</strong>.</p>`
      });
      return false;
    }

    $(".container-fluid").removeClass("d-none");

    const maxHoras = num(ConfModulos.ControlHoraCajaPOS);
    const horas = num(resp.horas_apertura);
    if (maxHoras > 0 && horas > maxHoras) {
      cerrarCarga();
      pantallaBloqueo({
        tipo: 'warning',
        icono: 'fa-hourglass-end',
        titulo: 'Tiempo Máximo Excedido',
        contenido: `<i class="fas fa-cash-register fa-2x text-warning mb-3"></i>
                    <h5 class="mb-3">Se ha pasado su hora máxima de cierre de caja</h5>
                    <p>La caja ha estado abierta <strong>${horas.toFixed(2)} horas</strong>.<br>
                    Tiempo máximo permitido: <strong>${maxHoras} horas</strong>.<br><br>
                    Es necesario que <strong>cierres y vuelvas a abrir tu caja</strong> para continuar operando.</p>`
      });
      return false;
    }
    return true;
  } catch (e) {
    console.error("Error validando apertura de caja:", e);
    cerrarCarga();
    return false;
  }
};

/** Reemplaza prompt(): funciona en móvil y no bloquea el navegador */
const validarCodigoVendedor = async (extra = {}) => {
  const { value, isConfirmed } = await Swal.fire({
    title: 'Código de vendedor',
    input: 'text',
    inputAttributes: { autocomplete: 'off', inputmode: 'numeric' },
    showCancelButton: true,
    confirmButtonText: 'Validar',
    cancelButtonText: 'Cancelar',
    inputValidator: (v) => (!$.trim(v) ? 'Digite el código de vendedor' : undefined)
  });
  if (!isConfirmed) return false;

  const codigo = $.trim(value);
  try {
    const vendedor = await enviarPeticion({ link: '../models/Pos.php', op: 'validar-vendedor', codigo, ...extra });
    if (!Array.isArray(vendedor) || !vendedor.length) {
      alerta("Error", "No se encontró el vendedor", "error");
      return false;
    }
    $("#vendedor").val(codigo);
    return true;
  } catch (e) {
    console.error(e);
    alerta("Error", "Ocurrió un error al validar el código de vendedor", "error");
    return false;
  }
};

const validacionVendedor = async () => {
  if (!esS(ConfGeneral.solicitarVendedor)) return true;
  const vendedorActual = $.trim($("#vendedor").val());
  const pedir = esS(ConfModulos.solicitarPorItem) ||
    (esS(ConfModulos.solicitarPrimerItem) && (vendedorActual === '' || vendedorActual === '0'));
  return pedir ? validarCodigoVendedor() : true;
};

const validacionReferencia = () => {
  if (unmoney($("#preciob_grid").val()) == 0) {
    alerta("Ups", "No se puede facturar productos con precio en 0");
    return false;
  }

  // Antes esta validación se ejecutaba en CADA tecla de la cantidad
  if (ConfModulos.perderPrecio == 'N' && Math.round(num($("#costo_promedio").val())) > num($("#precio_unitario").val())) {
    alerta("Error", "El precio no puede ser menor que el costo del producto", "error");
    return false;
  }

  if (!permisos.PerderPrecio) {
    const costo = Math.round(num($("#costo_promedio").val()) * num(undSel().val()));
    const precioVenta = Math.round(unmoney($("#precion_grid").val()) / (num($("#iva_grid").val()) / 100 + 1));
    if (costo > precioVenta) {
      Swal.fire({
        icon: "error",
        title: "Imposible continuar",
        html: `El precio de venta está por debajo del costo del producto.<br><br>
               <b>Costo:</b> ${money(costo)}<br>
               <b>Precio de venta:</b> ${money(precioVenta)}<br>
               <b>Diferencia:</b> ${money(costo - precioVenta)}<br><br>
               <span class="text-danger">No tiene permisos para esta operación.</span>`
      });
      return false;
    }
  }

  const stock = num($("#stock_real").val());
  const cantUm = num($("#cantidad_grid").val()) * num(undSel().val());
  if ($("#tipo_referencia").val() === 'P' && cantUm > stock) {
    alerta("No hay stock suficiente", "Verifique unidades comprometidas", "error");
    return false;
  }
  return true;
};

const cargarUnidadesComprometidas = async (id_producto, id_bodega) => {
  try {
    const data = await enviarPeticion({ link: '../models/Pos.php', op: 'listar-comprometidos', id_producto, id_bodega });
    if (!data?.length) {
      Swal.fire({ icon: 'info', title: 'Sin unidades comprometidas', text: 'No existen registros para este producto en esta bodega.' });
      return;
    }
    const filas = data.map(item => `
      <tr>
        <td data-label="Tipo">${esc(item.tipo)}</td>
        <td data-label="Número">${esc(item.numero)}</td>
        <td data-label="Usuario">${esc(item.usuario)}</td>
        <td data-label="Cliente" class="td-full">${esc(item.razon_comercial)}<p class="text-primary mb-0">${esc(item.tratamiento)}</p></td>
        <td data-label="Fecha">${esc(item.fecha_hora)}</td>
        <td data-label="Cantidad" class="text-center">${esc(item.cantidad)}</td>
        <td data-label="Lote">${esc(item.lote)}</td>
        <td data-label="Vencimiento">${esc(item.vmcto)}</td>
      </tr>`).join('');

    mostrarModalTabla({
      id: 'modalComprometidos',
      titulo: '<i class="fa fa-clock text-primary"></i> Unidades comprometidas',
      tamano: 'modal-xl',
      estatico: true,
      cuerpo: `<div class="table-responsive"><table class="table table-sm table-bordered table-hover datos tabla-cards">
                 <thead class="table-primary"><tr><th>Tipo</th><th>Número</th><th>Usuario</th><th>Cliente</th><th>Fecha</th><th class="text-center">Cantidad</th><th>Lote</th><th>Vencimiento</th></tr></thead>
                 <tbody>${filas}</tbody></table></div>`
    });
  } catch (e) {
    console.error("Error cargando unidades comprometidas:", e);
    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron cargar las unidades comprometidas' });
  }
};

const cargarStockOtrasBodegas = async (id_producto, id_bodega) => {
  try {
    const data = await enviarPeticion({ link: '../models/Pos.php', op: 'stock-otras-bodegas', id_producto, id_bodega });
    if (!data?.length) {
      Swal.fire({ icon: 'info', title: 'Sin stock en otras bodegas', text: 'No hay existencias de este producto en otras bodegas.' });
      return;
    }
    mostrarModalTabla({
      id: 'modalStockOtrasBodegas',
      titulo: 'Stock en otras bodegas',
      cuerpo: `<p class="text-muted mb-2">${esc($("#descripcion_grid").val())}</p>
               <div class="table-responsive"><table class="table table-sm table-bordered table-hover mb-0">
                 <thead class="table-primary"><tr><th>Bodega</th><th class="text-end">Stock</th></tr></thead>
                 <tbody>${data.map(i => `<tr><td>${esc(i.bodega)}</td><td class="text-end fw-bold">${esc(i.stock_total)}</td></tr>`).join('')}</tbody>
               </table></div>`
    });
  } catch (e) {
    console.error(e);
    alerta("Error", "No se pudo consultar el stock en otras bodegas", "error");
  }
};

/* ============================ PERMISOS Y CONFIGURACIÓN ============================ */
/**
 * La ventana de información muestra tres grupos:
 *  - Permisos del usuario en el módulo  -> permisosUsuario(id_modulo)
 *  - Configuración del módulo           -> cargarConfiguraciones().modulos[id_modulo]
 *  - Configuración general              -> cargarConfiguraciones().general
 * Los parámetros conocidos llevan nombre y descripción; cualquier otro que llegue
 * del servidor también se lista con un nombre generado a partir de su clave.
 */
const CATALOGO_INFO = {
  permisos: {
    CambiarPrecio: { nombre: 'Cambiar precio', desc: 'Permite editar el precio neto del producto en la grilla.' },
    PerderPrecio: { nombre: 'Vender por debajo del costo', desc: 'Permite facturar con precio de venta menor al costo del producto.' },
    IgnorarBloqueoTercero: { nombre: 'Ignorar bloqueo de cliente', desc: 'Permite vender a clientes en estado bloqueado.' },
    ControlCarteraVencida: { nombre: 'Control de cartera vencida', desc: 'Impide vender a clientes con cartera en mora.' },
    ControlCupoCredito: { nombre: 'Control de cupo de crédito', desc: 'Impide vender a clientes sin cupo de crédito disponible.' },
    FacturaElectronicaAutomatica: { nombre: 'Factura electrónica automática', desc: 'Envía la factura a la DIAN al guardar el documento.' },
    abrirModuloApertura: { nombre: 'Abrir apertura de caja', desc: 'Permite ingresar al módulo de apertura y cierre de caja.' }
  },
  modulo: {
    TerceroDefault: { nombre: 'Cliente por defecto', desc: 'Cliente que se carga automáticamente al iniciar o al crear un documento nuevo.' },
    tipoDocumentoDefault: { nombre: 'Tipo de documento por defecto', desc: 'Tipo de documento seleccionado al abrir el POS.' },
    solicitarAlGuardar: { nombre: 'Solicitar vendedor al guardar', desc: 'Pide el código de vendedor antes de guardar la factura.' },
    solicitarPorItem: { nombre: 'Solicitar vendedor por ítem', desc: 'Pide el código de vendedor cada vez que se agrega un producto.' },
    solicitarPrimerItem: { nombre: 'Solicitar vendedor en el primer ítem', desc: 'Pide el código de vendedor solo al agregar el primer producto.' },
    solicitarVoucher: { nombre: 'Voucher obligatorio', desc: 'Exige el número de voucher en los pagos con medios bancarios.' },
    perderPrecio: { nombre: 'Permitir precio menor al costo', desc: 'Si está inactivo, el precio unitario no puede ser menor al costo promedio.' },
    ControlHoraCajaPOS: { nombre: 'Horas máximas de caja abierta', desc: 'Tiempo máximo que puede permanecer abierta la caja (0 = sin control).', unidad: 'horas' }
  },
  general: {
    ManejaFechaLote: { nombre: 'Maneja fecha y lote', desc: 'Los productos se venden por lote y fecha de vencimiento.' },
    ManejaFIFO: { nombre: 'Maneja FIFO', desc: 'Los lotes se asignan por fecha de vencimiento al guardar.' },
    solicitarVendedor: { nombre: 'Solicitar vendedor', desc: 'Habilita la captura del código de vendedor en los documentos.' }
  }
};

const VALORES_ACTIVO = ['1', 'S', 'SI', 'TRUE'];
const VALORES_INACTIVO = ['0', 'N', 'NO', 'FALSE'];

/** Devuelve true / false si el valor es un indicador, o null si es un valor de otro tipo (texto, número) */
const interpretarIndicador = (v) => {
  if (v === true) return true;
  if (v === false || v === null || v === undefined) return false;
  const t = String(v).trim().toUpperCase();
  if (VALORES_ACTIVO.includes(t)) return true;
  if (VALORES_INACTIVO.includes(t)) return false;
  return null;
};

/** Convierte "ControlCupoCredito" o "control_cupo" en "Control cupo credito" */
const nombreLegible = (clave) => {
  const t = String(clave).replace(/_/g, ' ').replace(/([a-z0-9])([A-Z])/g, '$1 $2').toLowerCase().trim();
  return t.charAt(0).toUpperCase() + t.slice(1);
};

/** Acepta un objeto plano o un arreglo de objetos (como llega la configuración en sesión) */
const aplanarConfig = (fuente) => {
  if (Array.isArray(fuente)) return Object.assign({}, ...fuente.filter(x => x && typeof x === 'object'));
  return (fuente && typeof fuente === 'object') ? fuente : {};
};

const construirItemsInfo = (grupo, fuente) => {
  const datos = aplanarConfig(fuente);
  const catalogo = CATALOGO_INFO[grupo] || {};
  const claves = new Set([...Object.keys(catalogo), ...Object.keys(datos)]);

  return [...claves]
    .filter(k => datos[k] === undefined || datos[k] === null || typeof datos[k] !== 'object')
    .map(k => {
      const valor = datos[k];
      const estado = interpretarIndicador(valor);
      return {
        clave: k,
        nombre: catalogo[k]?.nombre || nombreLegible(k),
        desc: catalogo[k]?.desc || '',
        unidad: catalogo[k]?.unidad || '',
        definido: valor !== undefined && valor !== null && String(valor).trim() !== '',
        // Un parámetro de configuración que no llegó del servidor se muestra "Sin definir";
        // un permiso que no llegó equivale a no tenerlo (Inactivo)
        sinDefinir: grupo !== 'permisos' && !(k in datos),
        estado,          // true / false / null (valor de texto o número)
        valor
      };
    })
    // Activos primero, luego valores, luego inactivos; dentro de cada grupo por nombre
    .sort((a, b) => {
      const peso = (x) => (x.estado === true ? 0 : x.estado === null && x.definido ? 1 : 2);
      return (peso(a) - peso(b)) || a.nombre.localeCompare(b.nombre, 'es');
    });
};

const badgeInfo = (item) => {
  if (item.estado === true) {
    return '<span class="badge bg-primary text-nowrap"><i class="fa-solid fa-check me-1"></i>Activo</span>';
  }
  if (item.estado === null && item.definido) {
    const texto = `${item.valor}${item.unidad ? ' ' + item.unidad : ''}`;
    return `<span class="badge bg-label-primary text-wrap text-end" style="max-width:160px">${esc(texto)}</span>`;
  }
  if (item.sinDefinir) {
    return '<span class="badge bg-label-secondary text-nowrap">Sin definir</span>';
  }
  return '<span class="badge bg-label-secondary text-nowrap"><i class="fa-solid fa-xmark me-1"></i>Inactivo</span>';
};

const GRUPOS_INFO = [
  { id: 'permisos', fuente: () => permisos, contenedor: '#info_lista_permisos', contador: '#info_cont_permisos' },
  { id: 'modulo', fuente: () => ConfModulos, contenedor: '#info_lista_modulo', contador: '#info_cont_modulo' },
  { id: 'general', fuente: () => ConfGeneral, contenedor: '#info_lista_general', contador: '#info_cont_general' }
];

const pintarInfoModulo = (filtro = '') => {
  const f = filtro.trim().toLowerCase();

  GRUPOS_INFO.forEach(g => {
    const todos = construirItemsInfo(g.id, g.fuente());
    const visibles = todos.filter(p => !f || `${p.nombre} ${p.clave} ${p.desc} ${p.valor ?? ''}`.toLowerCase().includes(f));
    const activos = todos.filter(p => p.estado === true).length;
    const indicadores = todos.filter(p => p.estado !== null).length;

    $(g.contador).text(indicadores ? `${activos}/${indicadores}` : todos.length);
    $(g.contenedor).html(visibles.length
      ? `<ul class="list-group">${visibles.map(p => `
          <li class="list-group-item d-flex justify-content-between align-items-start gap-3">
            <div class="me-auto">
              <div class="fw-semibold">${esc(p.nombre)}</div>
              ${p.desc ? `<small class="text-muted d-block">${esc(p.desc)}</small>` : ''}
              <small class="text-muted"><code>${esc(p.clave)}</code></small>
            </div>
            ${badgeInfo(p)}
          </li>`).join('')}</ul>`
      : sinResultados(f ? 'Ningún parámetro coincide con el filtro.' : 'No hay parámetros configurados.'));
  });
};

/* ============================ INFORMACIÓN DEL CLIENTE ============================ */
const vacioGuion = (sel) => { const $e = $(sel); if ($.trim($e.text()) === '') $e.text('-'); };

const badgeSiNo = (valor) => {
  const v = interpretarIndicador(valor);
  if (v === true) return { cls: 'badge bg-primary', txt: 'Sí' };
  if (v === false && String(valor ?? '').trim() !== '') return { cls: 'badge bg-label-secondary', txt: 'No' };
  return { cls: 'badge bg-label-secondary', txt: String(valor ?? '').trim() || 'No' };
};

/** Completa el encabezado y los indicadores de la ventana con lo que ya está cargado en pantalla */
const prepararInfoCliente = () => {
  const tratamiento = $.trim($("#tratamiento").val());
  const razon = $.trim($("#razon_comercial").val());
  const nombre = tratamiento || razon || 'Sin cliente';
  const iniciales = nombre === 'Sin cliente' ? '--'
    : nombre.split(/\s+/).filter(Boolean).slice(0, 2).map(p => p.charAt(0)).join('');

  $("#info_nombre").text(nombre);
  $("#info_razon").text(razon && razon !== tratamiento ? razon : '');
  $("#info_nit").text($.trim($("#nit").val()) || '-');
  $("#info_iniciales").text(iniciales);

  ['#info_condicion', '#info_lista', '#info_credito', '#info_zona_descripcion', '#info_email'].forEach(vacioGuion);
  $("#info_zona_venta").toggleClass('d-none', $.trim($("#info_zona_venta").text()) === '');

  [['#info_controlados', '#info_controlados_badge'], ['#info_institucional', '#info_institucional_badge']].forEach(([origen, destino]) => {
    const b = badgeSiNo($(origen).text());
    $(destino).attr('class', b.cls).text(b.txt);
  });
};

const mostrarPermisos = () => {
  $("#permisos_usuario").text($.trim($("#nombres_usuario").val()));
  $("#permisos_filtro").val('');
  pintarInfoModulo();
  $('#tabsInfoModulo button[data-bs-target="#info_tab_permisos"]').tab('show');
  $("#modalPermisos").modal('show');
};

/* ============================ BÚSQUEDA DE PRODUCTOS ============================ */
/** Recrea el modal si cambió la lista de precios (antes quedaba con la lista del primer cliente) */
const obtenerModalBusqueda = () => {
  const lista = listaPreciosActual();
  if (modalBusquedaRef && listaModalBusqueda === lista) return modalBusquedaRef;

  if (modalBusquedaRef) modalBusquedaRef.destroy();
  listaModalBusqueda = lista;
  modalBusquedaRef = new ModalBusquedaProducto(
    async (rowData) => {
      if (manejaLote) await cargarLote(rowData);
      else cargarItemGrid(rowData);
    },
    { cod_barras: false, lista, id_modulo }
  );
  return modalBusquedaRef;
};
const crearModalBusqueda = obtenerModalBusqueda;

const buscarProducto = async () => {
  if (buscando) return;
  const filtro = $.trim($("#filtro").val());
  if (!filtro) return;

  const lista = listaPreciosActual();
  if (!lista) {
    alerta("Error", "No se ha asignado una lista de precios para el cliente o el almacén", "error");
    return;
  }

  const modal = obtenerModalBusqueda();
  buscando = true;
  try {
    const resp = await modal.getRecords({ data: filtro, cod_barras: false, lista });
    const nregistros = modal.getNrecords();
    if (!nregistros) return;

    if (nregistros === 1) {
      if (manejaLote) await cargarLote(resp[0]);
      else cargarItemGrid(resp[0]);
      $("#filtro").val("");
    } else {
      modal.show();
    }
  } catch (e) {
    console.error("Error en búsqueda:", e);
  } finally {
    buscando = false;
  }
};

/* ============================ CONSTRUCCIÓN DE GRILLAS ============================ */
const construirGridReferencia = () => {
  const btnEditPrecio = permisos.CambiarPrecio
    ? `<button type="button" class="btn btn-outline-primary btn-editar-precio" title="Editar precio"><i class="bx bx-pencil"></i></button>`
    : '';
  const campo = (cols, label, input) => `<div class="${cols}"><label class="form-label">${label}</label>${input}</div>`;

  $(".div-table-data-referencia").html(`
    <div class="row g-2 align-items-end grid-referencia">
      ${campo('col-6 col-sm-4 col-lg campo-valor', 'Precio b', '<input type="text" class="form-control form-control-sm data-grid text-end" id="preciob_grid" readonly>')}
      ${campo('col-3 col-sm-2 col-lg-auto campo-pct', 'Dto %', '<input type="text" class="form-control form-control-sm data-grid text-center" id="dto_grid" readonly>')}
      ${campo('col-3 col-sm-2 col-lg-auto campo-pct', 'Iva %', '<input type="text" class="form-control form-control-sm data-grid text-center" id="iva_grid" readonly>')}
      ${campo('col-6 col-sm-4 col-lg campo-valor', 'Precio n.', `<div class="input-group input-group-sm">
          <input type="text" class="form-control form-control-sm data-grid text-end" id="precion_grid" inputmode="decimal" readonly>${btnEditPrecio}</div>`)}
      ${manejaLote ? campo('col-6 col-sm-4 col-lg', 'Lote', '<input type="text" class="form-control form-control-sm data-grid" id="lote_grid" readonly>') : ''}
      ${manejaLote ? campo('col-6 col-sm-4 col-lg', 'Vcmto', '<input type="date" class="form-control form-control-sm data-grid" id="vcmto_grid" readonly>') : ''}
      ${campo('col-6 col-sm-4 col-lg-2', 'Unidad', '<select class="form-select form-select-sm p-0 data-grid" id="und_grid" multiple></select>')}
      ${campo('col-6 col-sm-4 col-lg-1', 'Cant', '<input type="number" min="1" inputmode="numeric" enterkeyhint="done" class="form-control form-control-sm table-primary data-grid" id="cantidad_grid">')}
      ${campo('col-6 col-sm-4 col-lg campo-valor', 'Total', '<input type="text" class="form-control form-control-sm data-grid text-end text-primary" id="total_grid" readonly>')}
      <div class="col-12 col-lg-auto d-grid">
        <button type="button" class="btn btn-sm btn-primary" id="btn_agregar_item" title="Agregar (Enter)"><i class="fa-solid fa-cart-plus"></i><span class="d-lg-none">&nbsp;Agregar al carrito</span></button>
      </div>
    </div>`);

  $("#dvReferencias").html(`
    <table class="table table-sm table-hover tabla-cards mb-0" id="carrito">
      <thead class="table-primary">
        <tr>
          <th>Código</th><th>Descripción</th><th class="text-end">Precio b</th><th class="text-center">Dcto</th><th class="text-center">Iva</th>
          <th class="text-end">Precio n</th><th class="text-center">Cant</th><th class="text-center">Und</th><th class="text-center">Conv</th>
          ${manejaLote ? '<th>F.Vto</th><th>Lote</th>' : ''}
          <th class="text-end">Total</th><th></th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>`);
};

/* ============================ EVENTOS ============================ */
const bindEventos = () => {
  // --- Acciones generales ---
  $(document).on('click', '.js-abrir-pagos', abrirPagos);
  // F4 ya lo maneja el componente de búsqueda (dispara click en #guardar)
  // Evita que las flechas cambien los campos numéricos (delegado: cubre los creados dinámicamente)
  $(document).on('keydown', 'input[type="number"]', (e) => {
    if (e.key === 'ArrowUp' || e.key === 'ArrowDown') e.preventDefault();
  });

  $('#tabs button[data-bs-toggle="tab"]').on('shown.bs.tab', (e) => {
    $("#barraMovil").toggleClass('d-none', $(e.target).attr('data-bs-target') !== '#navs-creacion');
  });

  $("#btn-info-permisos").on('click', mostrarPermisos);
  $("#modalInfo").on('show.bs.modal', prepararInfoCliente);
  $("#permisos_filtro").on('input', function () { pintarInfoModulo($(this).val()); });

  $("#nuevo").on('click', async () => {
    nuevoPedido();
    await cargarTercerosDefault();
  });

  // --- Domicilio (antes había 2 manejadores de click separados) ---
  $("#domicilio").on('click', async function () {
    const nuevoEstado = !esDomicilio();
    setDomicilio(nuevoEstado);
    const id = parseInt($("#id_transaccion").val(), 10) || 0;
    if (id <= 0) return;
    try {
      const resp = await enviarPeticion({ link: '../shared/servicios/servicios.php', op: 'update_encabezado_transaccion', id, es_domicilio: nuevoEstado ? 1 : 0 });
      if (!resp.ok) throw new Error('update');
    } catch (e) {
      console.error(e);
      setDomicilio(!nuevoEstado);
      alerta("Error", "Error al actualizar el registro", "error");
    }
  });

  // --- Notas (antes había 2 pares focus/focusout que enviaban 2 peticiones) ---
  $("#notas")
    .on('focus', function () { $("#tmp").val($(this).val()); })
    .on('blur', async function () {
      const id = parseInt($("#id_transaccion").val(), 10) || 0;
      if (id <= 0 || $(this).val() === $("#tmp").val()) return;
      try {
        const resp = await enviarPeticion({ link: '../shared/servicios/servicios.php', op: 'update_encabezado_transaccion', id, notas: $(this).val() });
        if (!resp.ok) throw new Error('update');
        $("#tmp").val("");
      } catch (e) {
        console.error(e);
        $(this).val($("#tmp").val());
        alerta("Error", "Error al actualizar el registro", "error");
      }
    });

  // --- Tipo de documento / retenciones ---
  $("#tipos").on("change", async function () {
    const attr = $("#tipos option:selected").attr("data-element");
    if (attr) { try { data_tipos = JSON.parse(attr); } catch { /* sin cambios */ } }
    await listarConceptosRetencion();
  });

  $("#concepto_retencion").on('change', async () => {
    aplicarRetencionesPorPerfil();
    if ((parseInt($("#id_transaccion").val(), 10) || 0) > 0) {
      cargando('Aplicando retenciones');
      await guardarRetencionesActuales();
      cerrarCarga();
    }
  });

  // --- Búsqueda de productos ---
  $("#filtro").on("keydown", (e) => {
    if (e.key !== "Enter") return;
    e.preventDefault();
    buscarProducto();
  });

  $("#btnBusquedaAvanzada").on("click", () => {
    obtenerModalBusqueda().show();
    $("#busqueda-descripcion").val($("#filtro").val());
    enfocar('#busqueda-descripcion', 200);
  });

  // Delegado en document: el modal de lotes vive fuera de esta vista
  $(document).on('click', '#result-lotes tr[data-idx]', function () { cargarItemGridLote(this); });

  // --- Grilla de captura ---
  $("#und_grid")
    .on('change', (e) => { e.preventDefault(); onCambioUnidad(); })
    .on('keyup', (e) => {
      if (e.key === 'Enter' && $("#codigo_grid").val() !== '') $("#cantidad_grid").trigger('focus').trigger('select');
    });

  $("#cantidad_grid")
    .on('input', recalcularLinea)                       // cálculo liviano mientras digita
    .on('keyup', (e) => {                               // Enter agrega (se mantiene keyup como el original)
      if (e.key !== 'Enter') return;
      e.preventDefault();
      recalcularLinea();
      agregarItem();
    });
  $("#btn_agregar_item").on('click', () => { recalcularLinea(); agregarItem(); });

  $("#precion_grid").on('keyup', (e) => { if (e.key === 'Enter') $("#cantidad_grid").trigger('focus'); });

  if (permisos.CambiarPrecio) {
    $(".btn-editar-precio").on('click', () => {
      if (!(parseInt($("#id_referencia").val(), 10) > 0)) return;
      const $p = $("#precion_grid");
      $p.prop("readonly", !$p.prop("readonly")).trigger('focus').trigger('select');
    });

    $("#precion_grid")
      .on('focus', function () {
        $(this).val(unformatNum($(this).val())).trigger('select');
        $("#tmp_precio").val($(this).val());
      })
      .on('blur', function () {
        if (!(parseInt($("#id_referencia").val(), 10) > 0)) return;
        const precio = num($(this).val());
        if (!precio) {
          $(this).val(money($("#tmp_precio").val()));
          return;
        }
        const dto = num($("#dto_grid").val());
        const iva = num($("#iva_grid").val());
        const pbruto = precio / ((1 - dto / 100) * (iva / 100 + 1));

        $("#precio_unitario").val(pbruto);
        $("#preciob_grid").val(money(Math.round(pbruto)));
        $(this).val(money(precio));

        // Antes se comparaba el PRECIO de la unidad con 1..4 (nunca coincidía); ahora se usa el índice real
        const idx = parseInt(undSel().attr("data-idx"), 10);
        if (itemActual && idx >= 1 && idx <= 4) {
          const sufijo = idx === 1 ? '' : idx;
          itemActual[`precion${sufijo}`] = precio;
          itemActual[`precio${sufijo}`] = pbruto;
        }
        recalcularLinea();
      });
  }

  // --- Carrito (delegado: un solo manejador para todas las filas) ---
  $("#dvReferencias")
    .on('click', '.btn-editar-item', function () { editarPedido($(this).closest('tr').attr('data-id')); })
    .on('click', '.btn-eliminar-item', function () { eliminarItem($(this).closest('tr').attr('data-id')); });

  // --- Stock / comprometidas ---
  $("#btn_unidades_comprometidas").on('click', () => {
    const id_producto = parseInt($("#id_referencia").val(), 10) || 0;
    const id_bodega = parseInt($("#bodegas").val(), 10) || 0;
    if (!id_producto) return alerta("Error", "No se ha seleccionado un producto", "error");
    if (!id_bodega) return alerta("Error", "No se ha seleccionado una bodega", "error");
    cargarUnidadesComprometidas(id_producto, id_bodega);
  });

  $("#btn_stock_otras_bodegas").on('click', () => {
    const id_producto = parseInt($("#id_referencia").val(), 10) || 0;
    if (!id_producto) return alerta("Error", "No se ha seleccionado un producto", "error");
    cargarStockOtrasBodegas(id_producto, $("#bodegas").val());
  });

  // --- Pagos ---
  $("#addPay").on('click', addPay);
  $("#forma_pago").on('change', () => { aplicarEstadoFormaPago(); $("#valor_recibido").trigger('focus'); });
  $("#valor_recibido").on('keyup', function (e) {
    if (e.key !== 'Enter') return;
    addPay();
    const { faltante } = calcularValoresPagos();
    if (faltante <= 0 && pagosRegistrados.length) $("#guardar-documento").trigger('focus');
  });
  $("#table-pagos").on('click', '.btn-eliminar-pago', function () { eliminarPago($(this).attr('data-id')); });
  $("#guardar-documento").on('click', guardarDocumento);
  $("#modalMediosPagos").on('shown.bs.modal', () => $("#valor_recibido").trigger('focus').trigger('select'));

  // --- Temporales (delegado una sola vez) ---
  $("#buscar-temporal").on('click', buscarTemporales);
  $("#temporales").on('click', '[data-accion]', function () {
    const item = temporalesData[$(this).closest('tr').data('index')];
    if (!item) return;
    switch ($(this).data('accion')) {
      case 'editar': recuperarPedido(item); break;
      case 'eliminar': eliminarPedido(item); break;
      case 'imprimir': imprimirFila(item); break;
      case 'whatsapp': enviarWhatsapp(item.id_transaccion, 1001, item.celular, item.nombres); break;
      case 'email': enviarEmail(item.id_transaccion, 1001, item.email_fe, item.nombres); break;
    }
  });

  // --- Facturas (delegado; antes onclick inline que se rompía con comillas en el nombre) ---
  $("#consultar-fe").on('click', listarFacturas);
  $("#listado-facturas").on('click', '[data-accion]', function () {
    const item = facturasData[$(this).closest('tr').data('index')];
    if (!item) return;
    const enviada = item.documento_electronico === 'S' && item.cufe !== '';
    switch ($(this).data('accion')) {
      case 'imprimir': opcionImpresion(item.id); break;
      case 'whatsapp': enviarWhatsapp(item.id, 5, item.celular || '', item.cliente || ''); break;
      case 'email':
        if (enviada) reenviarMail(item.tipo, item.numero, item.email || '');
        else enviarEmail(item.id, 5, item.email || '', item.cliente || '');
        break;
      case 'exportar': exportarXLS(item.id); break;
      case 'fe': enviarFacturaElectronica(item.id); break;
    }
  });

  // --- Tercero ---
  $('#txtBusquedaTercero').on('input', function () {
    if ($.trim($(this).val()).length === 0) {
      $('#btn-navs-creacion').prop('disabled', true);
      limpiarTercero();
    }
  });

  /*
   * Búsqueda de terceros con caché: si el usuario borra y vuelve a escribir, o repite
   * una búsqueda, se responde al instante sin ir al servidor. jQuery UI ya descarta
   * respuestas viejas; aquí además se evita enviar la misma consulta dos veces a la vez.
   */
  const cacheTerceros = new Map();
  const pendientesTerceros = new Map();
  const MAX_CACHE_TERCEROS = 50;

  const consultarTerceros = (termino) => {
    const clave = termino.toUpperCase();
    if (cacheTerceros.has(clave)) return Promise.resolve(cacheTerceros.get(clave));
    if (pendientesTerceros.has(clave)) return pendientesTerceros.get(clave);

    const peticion = enviarPeticion({ link: '../models/Terceros.php', filtro: termino, tipo_tercero: '', estado: 'A', op: 's_tercero', ligero: 1 })
      .then(resp => {
        const datos = Array.isArray(resp?.data) ? resp.data : [];
        if (cacheTerceros.size >= MAX_CACHE_TERCEROS) cacheTerceros.delete(cacheTerceros.keys().next().value);
        cacheTerceros.set(clave, datos);
        return datos;
      })
      .finally(() => pendientesTerceros.delete(clave));

    pendientesTerceros.set(clave, peticion);
    return peticion;
  };

  const fuenteTerceros = async (request, response) => {
    try {
      response(await consultarTerceros($.trim(request.term)));
    } catch (e) {
      console.error("Error en la solicitud:", e);
      response([]);
    }
  };

  $('#txtBusquedaTercero').autocomplete({
    source: fuenteTerceros,
    minLength: 3,
    delay: 450,
    select: async (event, ui) => {
      if (parseInt($("#numero").val(), 10) > 0) {
        alerta("Ups", "Ya hay una factura en proceso, no se puede cambiar el cliente", "info");
        return false;
      }
      limpiarPedido();
      limpiarReferencia();
      limpiarTemporales();
      await cargarTercero(ui.item);
    }
  });

  $("#txtBusquedaTerceroTmp")
    .on("input", function () { if ($.trim($(this).val()) === "") $("#nit_tmp").val(""); })
    .autocomplete({
      source: fuenteTerceros,
      minLength: 3,
      delay: 450,
      select: (event, ui) => { $("#nit_tmp").val(ui.item.nit); }
    });

  // Al cerrar la creación de cliente se limpia la caché para que el nuevo aparezca en la búsqueda
  $("#modalNewCliente").on('hidden.bs.modal', () => cacheTerceros.clear());

  $("#btn-crear-cliente").on('click', () => {
    $("#modalNewCliente .modal-body").html(`<iframe src="terceroBasico.php" width="100%" style="height:75vh; border:none;"></iframe>`);
    $("#modalNewCliente").modal('show');
  });
};

/* ============================ INICIO ============================ */
$(async function () {
  cargando('Cargando POS...');

  // Permisos y configuración en paralelo
  const [perm, conf] = await Promise.all([permisosUsuario(id_modulo), cargarConfiguraciones()]);
  permisos = perm || {};
  ConfGeneral = conf.general;
  ConfModulos = conf.modulos?.[id_modulo] || {};
  manejaLote = ConfGeneral?.ManejaFechaLote === 'S';

  if (!(await validarAperturaCaja())) return;

  construirGridReferencia();
  bindEventos();
  fechaActual([['fecha_inicial', 'fecha_final']]);

  const hoy = new Date();
  $("#fe_fecha_inicial").val(fechaISO(new Date(hoy.getFullYear(), hoy.getMonth(), 1)));
  $("#fe_fecha_final").val(fechaISO(new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0)));

  // Catálogos independientes en paralelo (antes 4 esperas en serie)
  const [listaAlmacen] = await Promise.all([
    cargarListaAlmacen(),
    listarAlmacenes(),
    condicionesPago('C', 'id_condicion', soloContado),
    consultarMediosPgos()
  ]);
  const listaPrecio = listaAlmacen?.[0]?.lista_precios;
  if (listaPrecio) $("#lista_almacen").val(listaPrecio);
  actualizarPanelCliente();

  await tiposDocumentos(5, $("#bodegas").val(), 'tipos');

  // El tipo por defecto se selecciona ANTES de cargar conceptos y data_tipos (antes era después)
  if (ConfModulos.tipoDocumentoDefault) {
    const tipoDefault = $.trim(ConfModulos.tipoDocumentoDefault);
    $('#tipos option').filter(function () { return $.trim($(this).text()) === tipoDefault; }).prop('selected', true);
  }
  const attrTipo = $("#tipos option:selected").attr("data-element");
  if (attrTipo) { try { data_tipos = JSON.parse(attrTipo); } catch { data_tipos = {}; } }

  await listarConceptosRetencion(true);
  reiniciarPagos();
  cerrarCarga();

  const okTer = await cargarTercerosDefault();
  if (!okTer) $("#txtBusquedaTercero").trigger('focus');
});