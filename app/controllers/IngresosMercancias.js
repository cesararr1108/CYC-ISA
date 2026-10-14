const link_ = '../models/Entradas.php';
let arrayReferencias = [];
let listasReferenciaModal = [];
let seleccionPrecioRecepcionModal = { listaId: null, campo: 'precio' };
let modalBusquedaRef = null;
let allowDecimals = 0;

const id_modulo = '40';
// configuraciones  
let carrito_enc = [];
let carrito_det = [];
let permisos = [];
let conceptosRetencion = [];
let perfilTributarioRetenciones = {
  ret_fte: 'N',
  ret_cree: 'N',
  ret_ica: 'N',
  ret_iva: 'N'
};
//CARGUE MASIVO  
const linkHomologacionCodigosModel = '../models/HomologacionCodigos.php';
let filasArchivoMasivo = [];
/* INICIO : CARGA DE INGRESOS DE MERCANCIAS POR CSV */
const parsearLineaCargaMasiva = (linea, separador) => {
  // Explica cada línea de esto:
  // resultado: array donde se almacenan los valores parseados de la línea
  // actual: valor actual que se está construyendo mientras se recorre la línea
  // entreComillas: indica si el caracter actual está dentro de comillas
  const resultado = [];
  let actual = '';
  let entreComillas = false;

  for (let i = 0; i < linea.length; i++) {
    const caracter = linea[i];
    if (caracter === '"') {
      if (entreComillas && linea[i + 1] === '"') {
        actual += '"';
        i++;
      } else {
        entreComillas = !entreComillas;
      }
      continue;
    }

    if (caracter === separador && !entreComillas) {
      resultado.push(actual.trim());
      actual = '';
      continue;
    }

    actual += caracter;
  }

  resultado.push(actual.trim());
  return resultado;
};

const detectarSeparadorCargaMasiva = (linea) => {
  const separadores = [';', ',', '\t', '|'];
  let mejor = ',';
  let max = -1;
  separadores.forEach((sep) => {
    const count = (linea.split(sep).length - 1);
    if (count > max) {
      max = count;
      mejor = sep;
    }
  });
  return mejor;
};

const parsearNumeroCargaMasiva = (valor) => {
  const limpio = String(valor ?? '').trim().replace(/[$\s]/g, '');
  if (!limpio) {
    return null;
  }
  if (limpio.includes(',') && !limpio.includes('.')) {
    return parseFloat(limpio.replace(',', '.'));
  }
  return parseFloat(limpio.replace(/,/g, ''));
};

const normalizarTextoCargaMasiva = (valor) => {
  return String(valor ?? '').trim();
};
//  mapeo las columas de el archivo csv 
const mapearFilaCargaMasiva = (fila) => {
  const c = fila.map((v) => String(v ?? '').trim());
  return {
    numero_factura: c[0] || '',
    codigo_proveedor: c[1] || '',
    producto: c[2] || '',
    nit_proveedor: c[3] || '',
    cantidad: parsearNumeroCargaMasiva(c[4]),
    porcentaje_descuento: parsearNumeroCargaMasiva(c[5]) || 0,
    costo: parsearNumeroCargaMasiva(c[6]),
    fecha_vencimiento: c[7] || '01-01-1900',
    lote: c[8] || '0'
  };
};

const leerArchivoMasivoIngresos = async (archivo) => {
  if (!archivo) {
    throw new Error('Debe seleccionar un archivo');
  }

  const ext = (archivo.name.split('.').pop() || '').toLowerCase();
  let filasRaw = [];

  if (ext === 'xlsx' || ext === 'xls' || ext === 'xlx') {
    const buffer = await archivo.arrayBuffer();
    const wb = XLSX.read(buffer, { type: 'array' });
    const sheet = wb.Sheets[wb.SheetNames[0]];
    //omito el encabezado primera fila 
    // omito el encabezado primera fila
    filasRaw = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: '' }).slice(1);
  } else {
    const txt = await archivo.text();
    const lineas = txt.split(/\r?\n/).map((l) => l.trim()).filter(Boolean);
    if (!lineas.length) {
      return [];
    }
    // omito los titulos 
    const sep = detectarSeparadorCargaMasiva(lineas[0]);
    //limpio los datos de las filas y los mapeo a objetos
    filasRaw = lineas.slice(1).map((linea) => parsearLineaCargaMasiva(linea, sep));
  }

  if (!filasRaw.length) {
    return [];
  }
  return filasRaw.map(mapearFilaCargaMasiva);

};
let activo = false;

/*
 * INICIO Modificacion 2026-09-24 - Christian Bula
 * Se cambio el renderPreviewCargaMasiva y se distribuyo entre nuevas funciones y helpers para que todo no quedara consolidado en esa sola funcion
 */
// ---------- Helpers ----------
const escapeAttr = (str) => String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
const num = (v) => parseFloat(v) || 0;
const calcCostoNeto = (costo, descuento, iva) => Math.round(costo * (1 - descuento / 100) * (1 + iva / 100));
const ivaDiferente = (ivaXml, ivaProducto) => ivaXml !== null && ivaXml !== undefined && Math.abs(num(ivaProducto) - num(ivaXml)) > 0.001;
const ivaHtml = (iva, ivaXml) => ivaDiferente(ivaXml, iva)
  ? `<span class="badge bg-label-warning" title="El IVA del XML era ${ivaXml}%"><i class="fa-solid fa-triangle-exclamation me-1"></i>${iva}%</span>`
  : `${iva}%`;

// Leer/escribir data-element siempre desde el atributo (evita la caché de jQuery)
const getElement = (tr) => {
  try { return JSON.parse(tr.attr('data-element') || '{}'); } catch { return {}; }
};
const setElement = (tr, obj) => {
  tr.removeData('element');
  tr.attr('data-element', JSON.stringify(obj));
};

const badgeTotal = (valor) => `<span class="badge bg-label-primary p-2" style="font-size:11px">${formatNum(valor, '$')}</span>`;

const ICONO_OK = '<span class="badge bg-label-success"><i class="fa-regular fa-circle-check fa-2x"></i></span>';
const ICONO_ERR = '<span class="badge bg-label-danger"><i class="fa-solid fa-triangle-exclamation fa-2x"></i></span>';

const btnHomologar = (idx, homologado) => `
  <button type="button" class="btn btn-sm btn-outline-${homologado ? 'primary' : 'warning'} btn-homologar-ref" data-homologar-fila="${idx}">
    <i class="fa-solid fa-link me-1"></i>${homologado ? 'Modificar' : 'Homologar'}
  </button>`;

// Recalcula una fila completa a partir de sus inputs y su data-element
const recalcularFila = (tr) => {
  const el = getElement(tr);
  const cantidad = num(tr.find('.input-csv-cantidad').val());
  const costo = num(unformatNum(tr.find('.input-csv-costo').val() || 0));
  const conversion = num(tr.find('td.td-conversion-sistema').text()) || 1;
  const costoNeto = calcCostoNeto(costo, num(el.porcentaje_descuento), num(el.porcentaje_iva));

  tr.find('td.td-cantidad-real-sistema').text(Math.round(cantidad * conversion));
  tr.find('td.td-costo-real-sistema').text(formatNum(Math.round(costo / conversion), '$'));
  tr.find('td.td-costo-neto').text(formatNum(costoNeto, '$'));
  tr.find('td.td-total-costo').html(badgeTotal(cantidad * costoNeto));

  const invalido = isNaN(parseFloat(tr.find('.input-csv-cantidad').val())) ||
    isNaN(parseFloat(unformatNum(tr.find('.input-csv-costo').val() || 0)));
  tr.toggleClass('table-danger', invalido);
};

// Total general y contadores de válidas/inválidas
const recalcularTotales = () => {
  let total = 0, validas = 0, invalidas = 0;

  $('#tbodyPreviewMasivo tr[data-element]').each(function () {
    const tr = $(this);
    const el = getElement(tr);
    const cantidad = num(tr.find('.input-csv-cantidad').val());
    const costo = num(unformatNum(tr.find('.input-csv-costo').val() || 0));
    total += cantidad * calcCostoNeto(costo, num(el.porcentaje_descuento), num(el.porcentaje_iva));
    el.homologado ? validas++ : invalidas++;
  });

  $('#lblTotalMasivo').text(`Total: ${validas + invalidas}`);
  $('#lblValidasMasivo').text(`Válidas: ${validas}`);
  $('#lblInvalidasMasivo').text(`Inválidas: ${invalidas}`);
  $('#lblTotal').text(`Total TMP: ${formatNum(total, '$')}`);
};

// Construye el <select> de unidades de compra
const buildSelectUnidades = (p, conversionActual) => {
  let opts = '';
  for (let i = 1; i <= 4; i++) {
    const cant = p[`cant_${i}`];
    if (i > 1 && !(cant > 0)) continue;
    const sel = conversionActual == cant ? 'selected' : '';
    opts += `<option value="${cant || 1}" ${sel}>${p[`und_${i}`] || ''} - ${cant || 1}</option>`;
  }
  return `<select class="form-select form-select-sm select-unidades-compra" style="width: 100px !important;">${opts}</select>`;
};

// Conversión según la unidad de compra del producto
const getConversion = (p) => {
  for (let i = 1; i <= 4; i++) {
    if (p.und_compra == p[`cant_${i}`]) return p[`cant_${i}`];
  }
  return 1;
};

// ---------- Homologación de una fila ----------
const abrirModalHomologacion = (tr, indice) => {

  if (modalBusquedaRef) {
    modalBusquedaRef.destroy();
    modalBusquedaRef = null;
  }

  modalBusquedaRef = new ModalBusquedaProducto(async (dataProduct) => {
    const el = getElement(tr);
    const ivaProducto = num(dataProduct.porcentaje_iva);
    const hayDiferenciaIva = ivaDiferente(el.iva_xml, ivaProducto);

    const advertenciaIva = hayDiferenciaIva ? `
    <div class="alert alert-warning text-start mt-3 mb-0">
      <i class="fa-solid fa-triangle-exclamation me-1"></i>
      <strong>El IVA no coincide.</strong><br>
      IVA en el XML: <strong>${el.iva_xml}%</strong><br>
      IVA del producto en el sistema: <strong>${ivaProducto}%</strong>
    </div>` : '';

    const { isConfirmed } = await Swal.fire({
      title: 'Confirmar homologación',
      html: `¿Desea homologar la referencia <strong>${dataProduct.codigo || ''} - ${dataProduct.descripcion || ''}</strong> para la fila <strong>${indice + 1}</strong>?${advertenciaIva}`,
      icon: hayDiferenciaIva ? 'warning' : 'question',
      showCancelButton: true,
      confirmButtonText: hayDiferenciaIva ? 'Homologar de todas formas' : 'Sí, homologar',
      cancelButtonText: 'Cancelar'
    });
    if (!isConfirmed) return;

    try {
      const resp = await enviarPeticion({
        link: linkHomologacionCodigosModel,
        op: 'guardar_homologacion',
        codigo_tercero: el.codigo_proveedor,
        nit_tercero: el.nit_proveedor,
        id_referencia: dataProduct.id,
      });

      if (!(resp.id > 0)) return;

      Swal.fire('Éxito', 'La homologación se guardó correctamente', 'success');
      modalBusquedaRef.hide();

      const conversion = getConversion(dataProduct);
      const iva = num(dataProduct.porcentaje_iva);

      // Actualizo el data-element conservando lo que ya tenía (cantidad, descuento, etc.)
      setElement(tr, {
        ...el,
        homologado: true,
        codigo: dataProduct.codigo || '',
        id: dataProduct.id || 0,
        descripcion: dataProduct.descripcion || '',
        conversion,
        des_und_compra: dataProduct.des_und_comp || '',
        porcentaje_iva: iva
      });

      // Actualizo la UI de la fila
      tr.find('td.td-estado-homologacion-sistema').html(ICONO_OK);
      tr.find('td.td-accion-homologar-sistema').html(btnHomologar(indice, true));
      tr.find('td.td-codigo-sistema').text(dataProduct.codigo || '');
      tr.find('td.td-descripcion-sistema').text(dataProduct.descripcion || '');
      //tr.find('td.td-iva').text(`${iva}%`);
      tr.find('td.td-conversion-sistema').text(conversion);
      tr.find('td.td-des-und-compra-sistema').html(buildSelectUnidades(dataProduct, conversion));

      recalcularFila(tr);
      recalcularTotales();

    } catch (error) {
      console.error('Error al guardar la homologación:', error);
      Swal.fire('Error', 'Ocurrió un error al guardar la homologación: ' + (error.message || 'Error desconocido'), 'error');
    }
  }, { check_stock: false });

  modalBusquedaRef.show();
};

// ---------- Render principal ----------
const renderPreviewCargaMasiva = (data = [], resumen = {}) => {
  let html = '';

  data.forEach((item, idx) => {
    const homologado = !!(item.homologado || item.referencia_match);
    const descuento = num(item.porcentaje_descuento);
    const iva = num(item.porcentaje_iva);
    const costo = num(item.costo);
    const cantidad = num(item.cantidad);
    const costoNeto = calcCostoNeto(costo, descuento, iva);
    const tieneIvaXml = item.porcentaje_iva !== undefined && item.porcentaje_iva !== null && item.porcentaje_iva !== '';
    const ivaTexto = tieneIvaXml ? `${iva}%` : '-';
    const textDcto = descuento > 0 ? `<span style="font-size:11px" class="badge bg-label-danger p-2">${descuento}%</span>` : '0%';

    const elemento = {
      homologado,
      codigo: item.codigo || '',
      id: item.id_referencia || 0,
      descripcion: item.descripcion_sistema || '',
      nit_proveedor: item.nit_proveedor || '',
      codigo_proveedor: item.codigo_proveedor || '',
      numero_factura: item.numero_factura || '',
      porcentaje_descuento: descuento,
      porcentaje_iva: iva,
      iva_xml: tieneIvaXml ? iva : null,
      conversion: item.conversion || 1,
      des_und_compra: item.des_und_compra || '',
      cantidad
    };

    html += `
      <tr data-element="${escapeAttr(JSON.stringify(elemento))}">
        <td>${idx + 1}</td>
        <td class="td-estado-homologacion-sistema">${homologado ? ICONO_OK : ICONO_ERR}</td>
        <td class="td-accion-homologar-sistema">${btnHomologar(idx, homologado)}</td>
        <td>${item.nit_proveedor || ''}</td>
        <td>${item.numero_factura || ''}</td>
        <td>${item.codigo_proveedor || ''}</td>
        <td>${item.producto || ''}</td>
        <td class="td-codigo-sistema">${item.codigo || ''}</td>
        <td class="td-descripcion-sistema">${item.descripcion || ''}</td>
        <td class="td-laboratorio-sistema d-none">${item.laboratorio || ''}</td>
        <td><input type="number" class="form-control form-control-sm input-csv-cantidad" value="${cantidad}"></td>
        <td><input type="text" class="form-control form-control-sm input-csv-costo" style="width: 100px !important;" value="${formatNum(costo, '$')}"></td>
        <td class="td-porcentaje-descuento">${textDcto}</td>
        <td class="td-iva">${ivaTexto}</td>
        <td class="td-costo-neto">${formatNum(costoNeto, '$')}</td>
        <td class="td-des-und-compra-sistema">${item.unidadesCompras || ''}</td>
        <td class="td-conversion-sistema">${item.conversion || ''}</td>
        <td class="td-cantidad-real-sistema">${item.cantidad_real ?? ''}</td>
        <td class="td-costo-real-sistema">${formatNum(item.costo_real || 0, '$')}</td>
        <td class="td-total-costo">${badgeTotal(cantidad * costoNeto)}</td>
        <td class="td-fecha-vencimiento-sistema">
          <input type="date" class="form-control form-control-sm input-csv-vencimiento" style="width: 110px !important;" value="${toInputDateFormat(item.fecha_vencimiento)}">
        </td>
        <td class="td-lote-sistema">
          <input type="text" class="form-control form-control-sm input-csv-lote" style="width: 100px !important;" value="${escapeAttr(item.lote || '')}">
        </td>
      </tr>`;
  });

  const $tbody = $('#tbodyPreviewMasivo');
  $tbody.html(html || `<tr>
                          <td colspan="22" class="text-center text-muted py-4">
                              <div class="d-flex flex-column align-items-center justify-content-center">
                                  <i class="fa-solid fa-inbox fa-lg mb-2"></i>
                                  <span>Sin datos para previsualizar</span>
                              </div>
                          </td>
                      </tr>`
  );
  recalcularTotales();

  // ---------- Eventos delegados (una sola vez por render, sin duplicarse) ----------
  $tbody.off('.masivo');

  // Foco / blur del costo: quita y pone el formato
  $tbody
    .on('focus.masivo', '.input-csv-costo', function () {
      $(this).val(unformatNum($(this).val() || 0)).select();
    })
    .on('blur.masivo', '.input-csv-costo', function () {
      $(this).val(formatNum($(this).val() || 0, '$'));
    });

  // Cambios en cantidad / costo
  $tbody.on('input.masivo', '.input-csv-cantidad, .input-csv-costo', function () {
    recalcularFila($(this).closest('tr'));
    recalcularTotales();
  });

  // Cambio de unidad de compra (funciona también con selects creados después)
  $tbody.on('change.masivo', '.select-unidades-compra', function () {
    const tr = $(this).closest('tr');
    const conversion = num($(this).val()) || 1;
    const desUnd = $.trim(($(this).find('option:selected').text().split(' - ')[0]) || '');

    tr.find('td.td-conversion-sistema').text(conversion);
    setElement(tr, { ...getElement(tr), conversion, des_und_compra: desUnd });

    recalcularFila(tr);
  });

  // Botón Homologar / Modificar (funciona aunque el botón se reemplace)
  $tbody.on('click.masivo', '.btn-homologar-ref', function () {
    const indice = parseInt($(this).data('homologar-fila'), 10) || 0;
    abrirModalHomologacion($(this).closest('tr'), indice);
  });
};

/*
 *FIN Modificacion 2026-09-24 - Christian Bula
 */
const previsualizarCargaMasiva = async () => {

  try {
    if (!filasArchivoMasivo.length) {
      Swal.fire('Alerta', 'No hay filas para previsualizar', 'warning');
      return;
    }

    const resp = await enviarPeticion({
      link: linkHomologacionCodigosModel,
      op: 'previsualizar_homologacion',
      rows: JSON.stringify(filasArchivoMasivo)
    });
    // console.log('Respuesta previsualización carga masiva:', resp);
    if (!resp || resp.error === true) {
      Swal.fire('Error', resp.message || 'No se pudo previsualizar', 'error');
      return;
    }

    previewCargaMasiva = Array.isArray(resp.data) ? resp.data : [];
    previewCargaMasiva.__resumen = resp.resumen || {};
    renderPreviewCargaMasiva(previewCargaMasiva, resp.resumen || {});
    //renderErroresCargaMasiva(previewCargaMasiva, resp.resumen || {});
  } catch (e) {
    console.error('Error previsualizando carga masiva:', e);
    Swal.fire('Error', 'Ocurrió un error al previsualizar la carga masiva', 'error');
  }

};

// convierte fechas en formatos comunes (DD/MM/YYYY, DD-MM-YYYY, etc.) a YYYY-MM-DD para el input type="date"
const toInputDateFormat = (fecha) => {
  if (!fecha) return '';
  // si ya viene en formato YYYY-MM-DD, la deja igual
  if (/^\d{4}-\d{2}-\d{2}$/.test(fecha)) return fecha;
  // intenta parsear DD/MM/YYYY o DD-MM-YYYY
  const match = fecha.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/);
  if (match) {
    const [, dia, mes, anio] = match;
    return `${anio}-${mes.padStart(2, '0')}-${dia.padStart(2, '0')}`;
  }
  return '';
};

const insertarCargaMasivaTMP = async () => {

  const filas = $('#tbodyPreviewMasivo tr[data-element]').toArray();
  const dataSend = [];
  let filasIvaDiferente = 0;

  // ---------- Validaciones ----------
  for (const fila of filas) {
    const tr = $(fila);
    const el = getElement(tr);
    const numFila = tr.index() + 1;

    if (!(el.id > 0)) {
      Swal.fire(
        'Falta homologar un producto',
        `La fila <strong>${numFila}</strong> aún no tiene una referencia del sistema asociada.<br>Pulsa <strong>Homologar</strong> en esa fila para continuar.`,
        'info');
      return;
    }

    const cantidad = parseFloat(tr.find('.input-csv-cantidad').val());
    if (isNaN(cantidad) || cantidad <= 0) {
      Swal.fire(
        'Falta completar la cantidad',
        `En la fila <strong>${numFila}</strong> la cantidad debe ser mayor a cero.<br>Ingresa la cantidad recibida para continuar.`,
        'info');
      return;
    }

    if (ivaDiferente(el.iva_xml, el.porcentaje_iva)) filasIvaDiferente++;

    const conversion = el.conversion || 1;

    dataSend.push({
      id_referencia: el.id,
      cantidad: cantidad * conversion,
      costo_unitario: num(unformatNum(tr.find('.input-csv-costo').val() || 0)) / conversion,
      fecha_vencimiento: tr.find('.input-csv-vencimiento').val() || '01-01-1900',
      lote: tr.find('.input-csv-lote').val() || 'DEFAULT',
      id_bodega: parseInt($("#bodegas").val() || '0', 10),
      pcj_descuento: el.porcentaje_descuento || 0,
      can_und: conversion,
      und: el.des_und_compra || '',
      porcentaje_iva: el.porcentaje_iva || 0
    });
  }

  if (!dataSend.length) {
    Swal.fire(
      'No hay productos para guardar',
      'Carga un archivo con productos antes de guardar.',
      'info');
    return;
  }

  // El número de factura se toma de la primera fila (antes se sobrescribía en cada vuelta)
  $("#documento").val(getElement($(filas[0])).numero_factura || '');

  // ---------- Confirmación ----------
  const advertenciaIva = filasIvaDiferente > 0 ? `
    <div class="alert alert-warning text-start mt-3 mb-0">
      <i class="fa-solid fa-triangle-exclamation me-1"></i>
      <strong>${filasIvaDiferente}</strong> fila(s) tienen un IVA distinto al del XML.
    </div>` : '';

  const { isConfirmed } = await Swal.fire({
    title: 'Confirmar carga masiva',
    html: `
      <div class="text-start">
        Se guardarán <strong>${dataSend.length}</strong> referencia(s) con un
        <strong>${$('#lblTotal').text()}</strong>.<br>
        Factura: <strong>${$('#documento').val() || '-'}</strong>
      </div>
      ${advertenciaIva}`,
    icon: filasIvaDiferente > 0 ? 'warning' : 'question',
    showCancelButton: true,
    confirmButtonText: 'Sí, guardar',
    cancelButtonText: 'Cancelar'
  });
  if (!isConfirmed) return;

  // ---------- Envío ----------
  showLoadingWithTetrisLogo('Guardando homologaciones...', 'ISA Software');

  try {
    const resp = await enviarPeticion({
      link: '../models/IngresosMercancias.php',
      op: 'insertar_carga_masiva_tmp',
      rows: JSON.stringify(dataSend),
      encabezado: JSON.stringify({
        numero: 0,
        id_tipo_documento: parseInt($("#tipo").val() || '0', 10),
        id_tercero: parseInt($("#id_tercero").val() || '0', 10),
        id_bodega: parseInt($("#bodegas").val() || '0', 10),
        notas: $("#notas").val() || '',
        fecha: $("#fecha").val() || '',
        documento: $("#documento").val() || '',
        valor_total: parseFloat(unformatNum($("#valor_total").val() || 0)),
        id_concepto_retencion: $("#concepto_retencion").val() || 0,
        id_condicion: parseInt($("#condicion").val() || '0', 10),
        id_tercero_dir: $("#id_tercero_dir").val() || '',
        sw: 1,
        id_pedido: 0,
        lista: 0,
        zona_ventas: '',
        zona_descripcion: '',
        vendedor: 0,
        ibua: 0,
        icui: 0,
        impoconsumo: 0,
        valor_aplicado: 0,
        dias: 0,
        id_bodega_destino: parseInt($("#id_bodega_destino").val() || '0', 10),
        cuenta_caja: $("#cuenta_caja").val() || '',
        id_concepto_caja: parseInt($("#id_concepto_caja").val() || '0', 10),
        id_doc_link: parseInt($("#id_doc_link").val() || '0', 10),
        es_domicilio: 0,
        retencion_fuente: 0,
        retencion_ica: 0,
        retencion_iva: 0,
        retencion_cree: 0,
        es_credito: 1
      })
    });

    if (!resp || resp.ok === false) {
      dissminSwal();
      Swal.fire('Error', resp?.message || 'No se pudo guardar la carga masiva', 'error');
      return;
    }

    $('#modalCargaMasiva').modal('hide');
    $('#fileCargaMasiva').val('');
    $("#tbodyPreviewMasivo").html('');
    $('#lblTotalMasivo').text('Total: 0');
    $('#lblValidasMasivo').text('Válidas: 0');
    $('#lblInvalidasMasivo').text('Inválidas: 0');

    $("#result").html(ShowAlert('Carga masiva guardada correctamente', 'success'));
    $("#btnLimpiar").trigger('click');
    dissminSwal();
    await MostrarPedido(resp.data);

  } catch (error) {
    dissminSwal();
    Swal.fire('Error', 'Ocurrió un error al guardar la carga masiva: ' + (error.message || 'Error desconocido'), 'error');
    console.error('Error al guardar la carga masiva:', error);
  }
};


const listarAlmacenes = async () => {
  try {

    const resp = await bodegasUsuarios();
    //console.warn('Respuesta listarAlmacenes:', resp);
    if (resp.length == 0) {
      $("#result").html(ShowAlert('No se encontraron resultados', 'warning'))
      return;
    }
    let options = ``;
    resp.forEach((d) => {
      options += `<option value="${d.id}">${d.bodega} - ${d.descripcion}</option>`;
    });
    $("#bodegas").html(options);
    $("#bodegas").val($("#id_bodega_session").val()).attr("disabled", true);
  } catch (e) {
    $("#result").html(ShowAlert('Error interno de la aplicación', 'error'))
  }
}

const listarTipoDocumentos = async (sw) => {
  let bodega = $("#bodegas").val();
  try {
    const data = {
      link: '../models/tiposDocumentos.php',
      sw: sw,
      op: 'listar_sw',
      bodega: bodega
    }
    const resp = await enviarPeticion(data);
    // console.log('listarTipoDocumentos resp:', resp);
    if (resp.data.length == 0) {
      $("#result").html(ShowAlert('No se encontraron resultados', 'warning'))
      return;
    }
    let options = ``;
    resp.data.forEach((d) => {
      options += `<option value="${d.id}">${d.tipo} - ${d.descripcion}</option>`;
    });
    $("#tipo").html(options);
  } catch (e) {
    console.error(e)
    $("#result").html(ShowAlert('Error interno de la aplicación', 'error'))
  }
}

const listarConceptosRetencion = async () => {
  const idTipoDocumento = parseInt($("#tipo").val() || '0', 10);
  if (!idTipoDocumento) {
    conceptosRetencion = [];
    $("#concepto_retencion").html('<option value="">NO APLICA</option>');
    aplicarRetencionesPorPerfil();
    return;
  }
  try {
    const resp = await enviarPeticion({
      link: '../models/tiposDocumentos.php',
      op: 'list_retencion',
      id_tipo_documento: idTipoDocumento
    });

    conceptosRetencion = Array.isArray(resp.data) ? resp.data : [];

    if (!resp.ok || conceptosRetencion.length === 0) {
      $("#concepto_retencion").html('<option value="">NO APLICA</option>');
      aplicarRetencionesPorPerfil();
      return;
    }

    let options = '<option value="">Seleccione un concepto</option>';
    conceptosRetencion.forEach((d) => {
      options += `<option value="${d.id}">${d.concepto_descripcion}</option>`;
    });
    $("#concepto_retencion").html(options);
    aplicarRetencionesPorPerfil();
  } catch (e) {
    console.error(e);
    conceptosRetencion = [];
    $("#concepto_retencion").html('<option value="">NO APLICA</option>');
    aplicarRetencionesPorPerfil();
  }
}

const formatearMonedaLocal = (valor) => {
  const n = isNaN(parseFloat(valor)) ? 0 : parseFloat(valor);
  if (typeof ConfGeneral !== 'undefined' && ConfGeneral) {
    return formatNum(n, '$', ConfGeneral);
  }
  return n.toFixed(2);
}

const limpiarPerfilTributarioCompra = () => {
  perfilTributarioRetenciones = {
    ret_fte: 'N',
    ret_cree: 'N',
    ret_ica: 'N',
    ret_iva: 'N'
  };
  $("#id_perfil_tributario").val('0');
  $("#perfil_tributario_compra").html('<option value="">NO APLICA</option>');
  aplicarRetencionesPorPerfil();
}

const normalizarNumeroListaModal = (valor) => {
  const numero = parseFloat(String(valor ?? 0).replace(/[^0-9.-]/g, ''));
  return isNaN(numero) ? 0 : numero;
}

const obtenerIvaActualModal = () => {
  const ivaText = $("#iva").val() || '0';
  return parseFloat(String(ivaText).replace(/[$,%]/g, '')) || 0;
};

const obtenerCostoBaseListaModal = (tipoLista) => {
  const costoPromedio = unformatNum($("#promedio").val() || 0);
  const costoUltimo = unformatNum($("#ultimo_costo").val() || 0);
  return tipoLista === 'CU' ? costoUltimo : costoPromedio;
}

const calcularRentabilidadListaModal = (precioFijo, costoBase, ivaPercent = 0) => {
  const precio = normalizarNumeroListaModal(precioFijo);
  if (costoBase <= 0) return 0;
  // Descontar IVA del precio: precio_sin_iva = precio_con_iva / (1 + (iva/100))
  const precioSinIva = precio / (1 + (ivaPercent / 100));
  return ((precioSinIva - costoBase) / costoBase) * 100;
}

const calcularPrecioVentaListaModal = (porcentaje, costoBase, ivaPercent = 0) => {
  const margen = normalizarNumeroListaModal(porcentaje);
  if (costoBase <= 0) return 0;
  // Calcular precio sin IVA y luego agregar IVA
  const precioSinIva = costoBase * (1 + margen / 100);
  const precioConIva = precioSinIva * (1 + (ivaPercent / 100));
  return precioConIva;
}

const formatearEditableListaModal = (valor, tipoLista) => {
  const numero = normalizarNumeroListaModal(valor);
  return tipoLista === 'PF'
    ? formatNum(numero.toFixed(0), '$', ConfGeneral)
    : numero.toFixed(2);
}

const formatearCalculadoListaModal = (valor, tipoLista) => {
  const numero = normalizarNumeroListaModal(valor);
  return tipoLista === 'PF'
    ? `${numero.toFixed(2)}%`
    : formatNum(numero.toFixed(0), '$', ConfGeneral);
}

const renderInputPrecioListaModal = (lista, campo, etiqueta) => {
  const tipoLista = String(lista.tipo_lista || 'PF').trim();
  const valorBase = normalizarNumeroListaModal(lista[campo]);
  const costoBase = obtenerCostoBaseListaModal(tipoLista);
  const ivaActual = obtenerIvaActualModal();
  const calculado = tipoLista === 'PF'
    ? calcularRentabilidadListaModal(valorBase, costoBase, ivaActual)
    : calcularPrecioVentaListaModal(valorBase, costoBase, ivaActual);

  return `
    <div class="lista-precio-celda d-grid gap-1">
      <div class="fw-semibold text-primary small">${etiqueta}</div>
      <input type="text" class="form-control form-control-sm lista-precio-edit"
        data-campo="${campo}"
        data-lista-id="${lista.id}"
        value="${formatearEditableListaModal(valorBase, tipoLista)}">
      <input type="text" class="form-control form-control-sm bg-light lista-precio-calc"
        data-calc-campo="${campo}"
        value="${formatearCalculadoListaModal(calculado, tipoLista)}" readonly>
    </div>
  `;
}

const actualizarFilaListaModal = (fila) => {
  const tipoLista = String(fila.data('tipo') || 'PF').trim();
  const costoBase = obtenerCostoBaseListaModal(tipoLista);
  const ivaActual = obtenerIvaActualModal();

  ['precio', 'precio_2', 'precio_3', 'precio_4'].forEach((campo) => {
    const inputEditable = fila.find(`[data-campo="${campo}"]`);
    const inputCalculado = fila.find(`[data-calc-campo="${campo}"]`);
    const valorActual = normalizarNumeroListaModal(inputEditable.val());
    const calculado = tipoLista === 'PF'
      ? calcularRentabilidadListaModal(valorActual, costoBase, ivaActual)
      : calcularPrecioVentaListaModal(valorActual, costoBase, ivaActual);

    inputCalculado.val(formatearCalculadoListaModal(calculado, tipoLista));
    inputEditable.removeClass('rentabilidad-negativa');
    inputCalculado.removeClass('rentabilidad-negativa');

    if (tipoLista === 'PF') {
      if (calculado < 0 && valorActual !== 0) {
        inputCalculado.addClass('rentabilidad-negativa');
      }
    } else if (valorActual < 0 && valorActual !== 0) {
      inputEditable.addClass('rentabilidad-negativa');
    }
  });
}

const actualizarTablaListasModal = () => {
  $('#tbodyListasModal tr[data-lista-id]').each(function () {
    actualizarFilaListaModal($(this));
  });
}

const marcarPrecioRecepcionModal = (input) => {
  const fila = input.closest('tr');
  seleccionPrecioRecepcionModal = {
    listaId: parseInt(fila.data('lista-id') || '0', 10),
    campo: String(input.data('campo') || 'precio')
  };
}

const obtenerPrecioTemporalDesdeListasModal = () => {
  const filas = $('#tbodyListasModal tr[data-lista-id]');

  if (!filas.length) {
    return null;
  }

  let fila = $();

  if (seleccionPrecioRecepcionModal.listaId) {
    fila = filas.filter(`[data-lista-id="${seleccionPrecioRecepcionModal.listaId}"]`).first();
  }

  if (!fila.length) {
    fila = filas.first();
  }

  const campo = ['precio', 'precio_2', 'precio_3', 'precio_4'].includes(seleccionPrecioRecepcionModal.campo)
    ? seleccionPrecioRecepcionModal.campo
    : 'precio';

  const tipoLista = String(fila.data('tipo') || 'PF').trim();
  const precio = normalizarNumeroListaModal(fila.find(`[data-campo="${campo}"]`).val());

  if (!tipoLista || isNaN(precio) || precio <= 0) {
    return null;
  }

  return {
    tipo_lista: tipoLista,
    precio
  };
}

const obtenerListasEditadasModal = () => {
  return $('#tbodyListasModal tr[data-lista-id]').map(function () {
    const fila = $(this);
    return {
      id: parseInt(fila.data('lista-id'), 10),
      lista: parseInt(fila.data('lista') || '0', 10),
      tipo_lista: String(fila.data('tipo') || 'PF').trim(),
      precio: normalizarNumeroListaModal(fila.find('[data-campo="precio"]').val()),
      precio_2: normalizarNumeroListaModal(fila.find('[data-campo="precio_2"]').val()),
      precio_3: normalizarNumeroListaModal(fila.find('[data-campo="precio_3"]').val()),
      precio_4: normalizarNumeroListaModal(fila.find('[data-campo="precio_4"]').val())
    };
  }).get();
}

const renderTablaListasModal = (listas) => {
  if (!Array.isArray(listas) || listas.length === 0) {
    $("#tbodyListasModal").html(`
      <tr>
        <td colspan="6" class="text-center text-muted">La referencia no tiene listas de precios configuradas</td>
      </tr>
    `);
    return;
  }

  if (!seleccionPrecioRecepcionModal.listaId && listas.length > 0) {
    seleccionPrecioRecepcionModal = {
      listaId: parseInt(listas[0].id || '0', 10),
      campo: 'precio'
    };
  }

  let filas = '';
  listas.forEach((lista) => {
    filas += `
      <tr data-lista-id="${lista.id}" data-lista="${lista.lista}" data-tipo="${lista.tipo_lista}">
        <td class="fw-semibold">${lista.lista || 'SIN NOMBRE'}</td>
        <td><span class="badge bg-label-primary">${lista.tipo_lista}</span></td>
        <td>${renderInputPrecioListaModal(lista, 'precio', 'Conv. 1')}</td>
        <td>${renderInputPrecioListaModal(lista, 'precio_2', 'Conv. 2')}</td>
        <td>${renderInputPrecioListaModal(lista, 'precio_3', 'Conv. 3')}</td>
        <td>${renderInputPrecioListaModal(lista, 'precio_4', 'Conv. 4')}</td>
      </tr>
    `;
  });

  $("#tbodyListasModal").html(filas);
  actualizarTablaListasModal();
}

const cargarListasReferenciaModal = async (idReferencia, idTransaccion = 0) => {
  const id = parseInt(idReferencia || 0, 10);
  const idTmp = parseInt(idTransaccion || 0, 10);

  if (!id) {
    listasReferenciaModal = [];
    renderTablaListasModal([]);
    return;
  }

  try {
    const resp = await enviarPeticion({
      link: '../models/Referencias.php',
      op: 's_referencia_listas',
      id_referencia: id
    });

    const listasBase = Array.isArray(resp.data) ? resp.data : [];
    listasReferenciaModal = listasBase;

    if (idTmp > 0) {
      const respTmp = await enviarPeticion({
        link: '../models/IngresosMercancias.php',
        op: 'cargar-precios',
        id_transaccion: idTmp,
        id_referencia: id
      });

      const listasTmp = Array.isArray(respTmp) ? respTmp : [];

      if (listasTmp.length > 0) {
        const mapaTmp = new Map(listasTmp.map(item => [String(item.lista), item]));
        listasReferenciaModal = listasBase.map((lista) => {
          const temporal = mapaTmp.get(String(lista.lista));
          return temporal ? {
            ...lista,
            tipo_lista: temporal.tipo_lista || lista.tipo_lista,
            precio: temporal.precio,
            precio_2: temporal.precio_2,
            precio_3: temporal.precio_3,
            precio_4: temporal.precio_4
          } : lista;
        });
      }
    }

    renderTablaListasModal(listasReferenciaModal);
  } catch (e) {
    console.error(e);
    listasReferenciaModal = [];
    renderTablaListasModal([]);
  }
}

const guardarPrecioTemporalRecepcion = async (mostrarMensaje = false) => {
  const idReferencia = parseInt($("#id_referencia").val() || '0', 10);
  const idTransaccion = parseInt($("#id_transaccion").val() || '0', 10);
  const listasEditadas = obtenerListasEditadasModal().filter((item) => parseInt(item.lista || '0', 10) > 0);

  if (!idReferencia || !idTransaccion || listasEditadas.length === 0) {
    if (mostrarMensaje) {
      Swal.fire('Info', 'No hay listas de precios válidas para guardar en recepción', 'info');
    }
    return { ok: false };
  }

  return await enviarPeticion({
    link: '../shared/servicios/servicios.php',
    op: 'actualizacionPrecios',
    id_referencia: idReferencia,
    id_transaccion: idTransaccion,
    listas: JSON.stringify(listasEditadas)
  });
}

const pintarRetencionesTotales = (retFte = 0, retIca = 0, retIva = 0) => {
  const totalBruto = unformatNum($("#total-total").val() || 0);
  const totalRetenciones = retFte + retIca + retIva;
  const netoPagar = Math.max(0, totalBruto - totalRetenciones);

  $("#total-ret-fte").val(formatearMonedaLocal(retFte));
  $("#total-ret-ica").val(formatearMonedaLocal(retIca));
  $("#total-ret-iva").val(formatearMonedaLocal(retIva));
  $("#total-ret-total").val(formatearMonedaLocal(totalRetenciones));
  $("#total-neto-pagar").val(formatearMonedaLocal(netoPagar));
}

const aplicarRetencionesPorPerfil = () => {
  const idConcepto = parseInt($("#concepto_retencion").val() || '0', 10);
  const concepto = conceptosRetencion.find((c) => parseInt(c.id, 10) === idConcepto);

  if (!concepto) {
    pintarRetencionesTotales(0, 0, 0);
    return;
  }

  const subtotal = unformatNum($("#total-sub-total").val() || 0);
  const descuento = unformatNum($("#total-descuento").val() || 0);
  const ivaTotal = unformatNum($("#total-iva").val() || 0);
  const baseDocumento = Math.max(0, subtotal - descuento);

  const baseRetFte = parseFloat(concepto.base_retefuente || 0);
  const baseRetIca = parseFloat(concepto.base_reteica || 0);
  const baseRetIva = parseFloat(concepto.base_reteiva || 0);

  const porRetFte = parseFloat(concepto.porcentaje_retefuente || 0);
  const porRetIca = parseFloat(concepto.porcentaje_reteica || 0);
  const porRetIva = parseFloat(concepto.porcentaje_reteiva || 0);

  const aplicarFte = perfilTributarioRetenciones.ret_fte === 'S' && baseDocumento >= baseRetFte;
  const aplicarIca = perfilTributarioRetenciones.ret_ica === 'S' && baseDocumento >= baseRetIca;
  const aplicarIva = perfilTributarioRetenciones.ret_iva === 'S' && ivaTotal >= baseRetIva;

  const retFte = aplicarFte ? (baseDocumento * (porRetFte / 100)) : 0;
  const retIca = aplicarIca ? (baseDocumento * (porRetIca / 100)) : 0;
  const retIva = aplicarIva ? (ivaTotal * (porRetIva / 100)) : 0;

  pintarRetencionesTotales(retFte, retIca, retIva);
}

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

    const perfiles = Array.isArray(resp.data) ? resp.data : [];

    if (!resp.ok || perfiles.length === 0) {
      limpiarPerfilTributarioCompra();
      return;
    }

    const perfilSeleccionado = perfiles.find((p) => parseInt(p.bodega, 10) === bodega) || perfiles[0];

    perfilTributarioRetenciones = {
      ret_fte: (perfilSeleccionado.ret_fte || 'N'),
      ret_cree: (perfilSeleccionado.ret_cree || 'N'),
      ret_ica: (perfilSeleccionado.ret_ica || 'N'),
      ret_iva: (perfilSeleccionado.ret_iva || 'N')
    };

    $("#id_perfil_tributario").val(idPerfilTributario);
    $("#perfil_tributario_compra").html(
      `<option value="${perfilSeleccionado.id}" selected>${perfilSeleccionado.tipo} - ${perfilSeleccionado.descripcion}</option>`
    );

    aplicarRetencionesPorPerfil();
  } catch (e) {
    console.error(e);
    limpiarPerfilTributarioCompra();
  }
}

const cargarPerfilTributarioDesdeTercero = async (idTercero) => {
  const id = parseInt(idTercero || '0', 10);

  if (!id) {
    limpiarPerfilTributarioCompra();
    return;
  }

  try {
    const resp = await enviarPeticion({
      link: '../models/Terceros.php',
      op: 's_tercero',
      id_tercero: id,
      filtro: '',
      tipo_tercero: ''
    });

    const tercero = Array.isArray(resp.data) && resp.data.length > 0 ? resp.data[0] : null;
    const idPerfil = tercero ? parseInt(tercero.id_perfil_tributario || '0', 10) : 0;
    await cargarPerfilTributarioPorId(idPerfil);
  } catch (e) {
    console.error(e);
    limpiarPerfilTributarioCompra();
  }
}

const actualizarCostoNeto = () => {
  let costo_unitario = parseFloat($.trim($("#costo").val()).replace(/,/g, ''));
  const pcj_descuento = parseFloat(unformatNum($("#pcj_descuento").val() || 0));
  const iva = isNaN(parseFloat($("#iva").val())) ? 0 : parseFloat($("#iva").val());
  const impoconsumo = parseFloat($("#impoconsumo").val() || 0);
  const ibua = parseFloat(unformatNum($("#ibua").val()) || 0);
  const icui = parseFloat($("#icui").val() || 0);
  const cantidad = parseFloat($("#cantidad").val() || 0);

  // Factor de conversión de la unidad seleccionada (ej: 1 caja = 12 unidades -> factor = 12)
  const unidadFactor = parseFloat($("#unidad option:selected").val() || 1);
  const factorSeguro = (isNaN(unidadFactor) || unidadFactor <= 0) ? 1 : unidadFactor;

  const promedioActual = unformatNum($("#promedio").val() || 0);
  const stockActual = parseFloat(String($("#stock").val() || 0).replace(/[^0-9.-]/g, '')) || 0;

  // 1. Obtenemos el último costo registrado (que está en unidad mínima)
  const ultimoCostoMinimo = unformatNum($("#ultimo_costo").val() || 0);
  const $badgeCosto = $("#badge_variacion_costo"); // Selector de tu badge o label

  if (isNaN(costo_unitario) || costo_unitario <= 0) {
    $("#costo").val(0);
    $("#costo_neto").val(formatNum(0, '$', ConfGeneral));
    $("#costo_total").val(formatNum(0, '$', ConfGeneral));
    $("#promedio_calculado").val(formatNum(promedioActual, '$', ConfGeneral));

    // Resetear badge si el costo es inválido/cero
    $badgeCosto.text("Sin cambios").removeClass("bg-success bg-danger bg-warning bg-info").addClass("bg-secondary");
    return;
  }

  costo_unitario = (costo_unitario * (1 - (pcj_descuento / 100)));
  const valor_iva = costo_unitario * (iva / 100);
  const impuesto_saludable = ibua > 0 ? ibua : costo_unitario * icui / 100;
  costo_unitario = (costo_unitario + impuesto_saludable + impoconsumo);

  const costo_neto = (costo_unitario + valor_iva);
  const costoTotal = costo_neto * cantidad;

  $("#costo_neto").val(formatNum(costo_neto, '$', ConfGeneral));
  $("#costo_total").val(formatNum(costoTotal, '$', ConfGeneral));

  // 2. Lógica de comparación teniendo en cuenta la conversión
  $badgeCosto.removeClass("bg-success bg-danger bg-secondary bg-info");

  if (ultimoCostoMinimo > 0) {
    // Convertimos nuestro costo neto unitario (de la presentación de compra) a costo por unidad mínima
    const costoNetoPorUnidadMinima = costo_neto / factorSeguro;

    // Comparamos peras con peras (ambos en unidad mínima)
    if (costoNetoPorUnidadMinima > ultimoCostoMinimo) {
      // Subió de precio
      $badgeCosto.text("Subió 📈").addClass("bg-danger text-white");
    } else if (costoNetoPorUnidadMinima < ultimoCostoMinimo) {
      // Bajó de precio
      $badgeCosto.text("Bajó 📉").addClass("bg-success text-white");
    } else {
      // Se mantuvo igual
      $badgeCosto.text("Se mantuvo ➖").addClass("bg-secondary text-white");
    }
  } else {
    // Si no hay un costo previo de referencia
    $badgeCosto.text("Primer registro").addClass("bg-info text-white");
  }

  const cantidadBase = cantidad * factorSeguro;
  let promedioCalculado = promedioActual;

  if ((stockActual + cantidadBase) > 0 && cantidad > 0) {
    promedioCalculado = ((promedioActual * stockActual) + costoTotal) / (stockActual + cantidadBase);
  }

  $("#promedio_calculado").val(formatNum(promedioCalculado, '$', ConfGeneral));
};

const addReferencia = async (id_transaccion_det_tmp, detalle) => {

  arrayReferencias = [];

  const descripcionCorta = $("#descripcion_corta").val();
  const $badge = $("#badge_variacion_costo");
  const badgeTexto = $badge.text().trim();
  const badgeClases = $badge.attr("class"); // Captura las clases (ej: badge bg-danger text-white)

  // Creamos el HTML del badge solo si hubo un cambio real
  let badgeHtml = "";
  if (badgeTexto && badgeTexto !== "Sin cambios") {
    badgeHtml = `<span class="${badgeClases}">${badgeTexto}</span>`;
  }

  const codigo = $("#codigo").val();
  const costo = $.trim($("#costo").val()).replace(/,/g, '');
  const iva = $("#iva").val();
  const vmcto = $("#vmcto").length ? $("#vmcto").val() : '';
  const can_und = parseInt($.trim($.trim($("#unidad option:selected").text()).split("-")[1]))
  const unidad = $.trim($.trim($("#unidad option:selected").text()).split("-")[0])
  const cantidad = detalle.cantidad / can_und;
  const costo_neto = unformatNum($("#costo_neto").val().replace(/,/g, ''));
  const costo_total = (costo_neto * parseInt(cantidad));
  const lote = $("#lote").length ? $("#lote").val().toUpperCase() : '';

  if (codigo.length == 0) return;
  if (isNaN((costo))) { Swal.fire("Error", "El costo no es valido.", "error"); return; }
  if (unidad.length == 0) { Swal.fire("Error", "Debe seleccionar una unidad.", "error"); return; }

  if (ConfGeneral.ManejaFechaLote == 'S') {
    if (lote.length == 0) { Swal.fire("Error", "Debe digitar el lote de la referencia.", "error"); return; }
    if (vmcto == '') { Swal.fire("Error", "Fecha no valida.", "error"); return; }
  }

  if (isNaN(parseFloat(cantidad)) || cantidad == 0) {
    Swal.fire("Error", "La cantidad no es valida.", "error");
    return;
  }

  arrayReferencias.push({
    codigo,
    descripcion: descripcionCorta,
    badgeHtml, // <--- Guardamos el HTML listo para renderizar en la tabla
    costo,
    costo_neto,
    iva,
    vmcto,
    lote,
    cantidad,
    unidad,
    can_und,
    costo_total,
    id_transaccion_det_tmp
  });

  if ($("#id_detalle").val() > 0) {
    editReferencia();
  } else {
    generarTabla();
  }

  limpiarReferencia();
  $("#modalAdd").modal('hide');
};

const editReferencia = (id) => {

  item = arrayReferencias[0];

  fila = $(".det_" + item.id_transaccion_det_tmp);

  // 1. Actualizamos la celda de la descripción (columna index 1) incluyendo el badge con su color
  fila.find("td").eq(1).html(`
    ${item.descripcion}
    <div class="mt-1">${item.badgeHtml || ''}</div>
  `);

  // 2. Actualizamos costos
  fila.find("td").eq(2).html(formatNum(item.costo, '$', ConfGeneral)); // costo 
  fila.find("td").eq(3).html(formatNum(item.costo_neto, '$', ConfGeneral)); // costo neto 

  if (ConfGeneral.ManejaFechaLote == 'S') {
    fila.find("td").eq(5).html(item.vmcto); // fecha vencimiento
    fila.find("td").eq(6).html(item.lote); // lote
    fila.find("td").eq(7).html(item.cantidad); // cantidad 
    fila.find("td").eq(8).html(item.unidad); // und
    fila.find("td").eq(9).html(item.can_und); // can und
    fila.find("td").eq(10).html(formatNum(item.costo_total, '$', ConfGeneral)); // costo total 
  } else {
    fila.find("td").eq(5).html(item.cantidad); // cantidad 
    fila.find("td").eq(6).html(item.unidad); // und
    fila.find("td").eq(7).html(item.can_und); // can und
    fila.find("td").eq(8).html(formatNum(item.costo_total, '$', ConfGeneral)); // costo total 
  }
};

const guardarRetencionesTMP = async (idTransaccion, retFte, retIca, retIva) => {
  try {
    let resp = await enviarPeticion({
      link: '../models/IngresosMercancias.php',
      op: 'guardar_retenciones',
      id_transaccion: idTransaccion,
      ret_fte: retFte,
      ret_ica: retIca,
      ret_iva: retIva,
      id_concepto_retencion: parseInt($("#concepto_retencion").val() || '0', 10)
    });
  } catch (e) {
    console.error('Error al guardar retenciones:', e);
  }
}

const restaurarRetencionesEncabezado = (data) => {
  const idConcepto = parseInt(data.id_concepto_retencion || '0', 10);
  const retFte = parseFloat(data.retencion_fuente || 0);
  const retIca = parseFloat(data.retencion_ica || 0);
  const retIva = parseFloat(data.retencion_iva || 0);

  if (idConcepto > 0) {
    $("#concepto_retencion").val(String(idConcepto));
  }

  pintarRetencionesTotales(retFte, retIca, retIva);
}

const recalcularEncabezado = async () => {

  let resp = await enviarPeticion({
    op: 'update_encabezado_compra',
    link: '../models/IngresosMercancias.php',
    id_transaccion: $("#id_transaccion").val()
  });

  if (resp.length > 0) {
    $("#total-sub-total").val(formatNum(resp[0].bruto, '$', ConfGeneral));
    $("#total-descuento").val(formatNum(resp[0].descuento, '$', ConfGeneral));
    $("#total-iva").val(formatNum(resp[0].iva, '$', ConfGeneral))
    $("#total-ibua").val(formatNum(resp[0].ibua, '$', ConfGeneral));
    $("#total-icui").val(formatNum(resp[0].icui, '$', ConfGeneral));
    $("#total-impoconsumo").val(formatNum(resp[0].impoconsumo, '$', ConfGeneral))
    total = parseFloat(resp[0].bruto) - parseFloat(resp[0].descuento)
      + parseFloat(resp[0].impoconsumo)
      + parseFloat(resp[0].ibua)
      + parseFloat(resp[0].icui)
      + parseFloat(resp[0].iva);

    $("#total-total").val(formatNum(total, '$', ConfGeneral))

    // Calcular y guardar retenciones
    aplicarRetencionesPorPerfil();

    // Obtener valores de retenciones desde la UI
    const retFte = unformatNum($("#total-ret-fte").val() || '0');
    const retIca = unformatNum($("#total-ret-ica").val() || '0');
    const retIva = unformatNum($("#total-ret-iva").val() || '0');

    await guardarRetencionesTMP($("#id_transaccion").val(), retFte, retIca, retIva);
  } else {
    limpiarDatosPedido()
    limpiarFiltrosTemp()
    limpiarReferencia();
  }
}

const eliminarPedido = (id_transaccion) => {
  return enviarPeticion({
    op: 'delete_transaccion',
    id_transaccion,
    link: '../shared/servicios/servicios.php'
  })
}


const limpiarReferencia = () => {
  $("#costo_unitario").val("0");
  $("#descripcion_corta").val('');
  $("#descripcion_larga").val('');
  $("#codigo").val('');
  $("#unidad").html('');
  $("#iva").val(0);
  $("#costo_neto").val(0);
  $("#promedio").val(0);
  $("#ultimo_costo").val(0);
  $("#stock").val(0);
  $("#promedio_calculado").val(0);
  $("#lote").val('');
  $("#vmcto").val('');
  $("#cantidad").val(0);
  $("#filtro").val('');
  $("#id_transaccion_det").val(0)
  $("#id_referencia").val(0)
  $("#costo").val(0)
  $("#ibua").val(0)
  $("#icui").val(0)
  $("#impoconsumo").val(0)
  $("#costo_total").val(0)
  $("#pcj_descuento").val(0)
  $("#FormDetalle").removeClass("was-validated");
  seleccionPrecioRecepcionModal = { listaId: null, campo: 'precio' };
  $("#id_detalle").val(0);
  listasReferenciaModal = [];
  $("#tbodyListasModal").html(`
    <tr>
      <td colspan="6" class="text-center text-muted">Cargue una referencia para visualizar sus listas de precios</td>
    </tr>
  `);
  const $badgeCosto = $("#badge_variacion_costo");
  $badgeCosto.text("Sin cambios").removeClass("bg-success bg-danger bg-warning bg-info").addClass("bg-secondary");
  arrayReferencias = [];
}

const limpiarTercero = () => {
  $("#id_tercero").val("");
  $("#nit").val("");
  $("#tratamiento").val("");
  $("#razon_comercial").val("");
  $("#textDireccion").val("")
  $("#id_tercero_dir").val("0")
  limpiarPerfilTributarioCompra();
}

const limpiarDatosPedido = () => {
  $("#numero").val("");
  $("#notas").val("");
  $("#total-sub-total").val("0");
  $("#total-total").val("0");
  $("#total-descuento").val("0");
  $("#total-iva").val("$0");
  $("#total-ibua").val("$0");
  $("#total-icui").val("$0")
  $("#documento").val("")
  $("#total-impoconsumo").val("0");
  $("#id_transaccion").val("0");
  $("#concepto_retencion").html('<option value="">NO APLICA</option>');
  conceptosRetencion = [];
  limpiarPerfilTributarioCompra();
  pintarRetencionesTotales(0, 0, 0);
  $('#bodegas').prop('selectedIndex', 0);
}

const limpiarFiltrosTemp = () => {
  $("#temporales").html("");
  fechaActual([['fecha_inicial', 'fecha_final']]);
}

const nuevoPedido = async () => {
  limpiarReferencia();
  limpiarTercero();
  limpiarDatosPedido();
  $("#items_ tbody tr").remove();
  $("#txtBusquedaTercero").val("")
  limpiarFiltrosTemp();
  bloquearCampos(false)
  await listarConceptosRetencion();
  $('#tabs button[data-bs-target="#navs-tercero"]').tab('show');
  $("#txtBusquedaTercero").focus()
  listarAlmacenes();
  fechaActual([
    ['fecha_inicial', 'fecha_final'],
    'fecha'
  ])
}

const confirmarEliminarPedido = (id_transaccion) => {

  Swal.fire({
    title: "Esta seguro de eliminar el pedido?",
    text: "Esta acción no puede reversarse!",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Si"
  }).then(async (result) => {
    if (result.isConfirmed) {
      eliminarPedido(id_transaccion)
        .then(item => {

          if (item.error) {
            Swal.fire("Info", "Se elimino correctamente el pedido", "error");
          }

          Swal.fire("Error", "Se elimino correctamente el pedido", "success");
          buscarTemporales();
          //nuevoPedido()
          bloquearCampos(false)
        }).catch(error => {
          console.error({
            error
          })
        })
    }
  })
}

const confirmarEliminar = (ob, id) => {

  const id_transaccion = $("#id_transaccion").val();

  Swal.fire({
    title: "Esta seguro de eliminar el producto?",
    text: "Esta acción no puede reversarse!",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Si"
  }).then(async (result) => {
    if (result.isConfirmed) {

      try {
        resp = await enviarPeticion({
          link: '../shared/servicios/servicios.php',
          op: 'delete_item',
          id,
          id_transaccion
        });

        if (resp.error) {
          Swal.fire("Error", resp.message, "error")
          return;
        }

        if (resp.total_reg == 0) {

          await eliminarPedido($("#id_transaccion").val())
          recalcularEncabezado()
          limpiarReferencia();
          limpiarDatosPedido();
          $("#items_ tbody tr").remove();
          $("#txtBusquedaTercero").val("")
          limpiarFiltrosTemp();

          return;
        } else {
          fila = $(ob).parent().parent();
          fila.remove();
          recalcularEncabezado();
        }

      } catch (e) {
        console.error(e)
      }

    }
  });

}

const generarTabla = () => {
  let tabla = ``;
  arrayReferencias.forEach(item => {
    if (ConfGeneral.ManejaFechaLote == 'S') {
      tabla += `
        <tr class="det_${item.id_transaccion_det_tmp}">
          <td>${item.codigo}</td>
          <td>${item.descripcion} <div class="mt-1">${item.badgeHtml}</div></td>
          <td>${formatNum(item.costo, '$', ConfGeneral)}</td>
          <td>${formatNum(item.costo_neto, '$', ConfGeneral)}</td>
          <td>${item.iva}</td>
          <td>${item.vmcto}</td>
          <td>${item.lote}</td>
          <td class="text-center">${item.cantidad}</td>
          <td class="text-center">${item.unidad}</td>
          <td>${item.can_und}</td>
          <td>${formatNum(item.costo_total, '$', ConfGeneral)}</td>
          <td align="center"><button class="btn btn-sm btn-outline-danger btnEliminar" onclick="confirmarEliminar(this,${item.id_transaccion_det_tmp})"><i class="fa-regular fa-trash-can"></i></button></td>
          <td align="center"><button class="btn btn-sm btn-outline-primary btnEditar" onclick="abrirModalReferencia(${item.id_transaccion_det_tmp})"><i class="fa-regular fa-pen-to-square "></i></td>
          <td style="display:none">${item.id_transaccion_det_tmp}</td>
        </tr>
      `;
    } else {
      tabla += `
      <tr class="det_${item.id_transaccion_det_tmp}">
        <td>${item.codigo}</td>
        <td>${item.descripcion} <div class="mt-1">${item.badgeHtml}</div></td>
        <td>${formatNum(item.costo, '$', ConfGeneral)}</td>
        <td>${formatNum(item.costo_neto, '$', ConfGeneral)}</td>
        <td>${item.iva}</td>
        <td class="text-center">${item.cantidad}</td>
        <td class="text-center">${item.unidad}</td>
        <td>${item.can_und}</td>
        <td>${formatNum(item.costo_total, '$', ConfGeneral)}</td>
        <td align="center"><button class="btn btn-sm btn-outline-danger btnEliminar" onclick="confirmarEliminar(this,${item.id_transaccion_det_tmp})"><i class="fa-regular fa-trash-can"></i></button></td>
        <td align="center"><button class="btn btn-sm btn-outline-primary btnEditar" onclick="abrirModalReferencia(${item.id_transaccion_det_tmp})"><i class="fa-regular fa-pen-to-square "></i></td>
        <td style="display:none">${item.id_transaccion_det_tmp}</td>
      </tr>
    `;
    }

  });

  // Insertar la tabla en el contenedor
  $("#items_ tbody").prepend(tabla);
  validarCamposBlock();
  // recalcularEncabezado();
};

const abrirModalReferencia = async (id) => {
  try {
    limpiarReferencia();
    $("#modalAdd").modal('show');

    const resp = await enviarPeticion({
      link: '../shared/servicios/servicios.php',
      op: 'detalle_transacciones_tmp',
      id
    });

    const item = resp[0];
    $("#costo_unitario").val(item.costo_unitario);
    costo_unitario = 0;

    switch (item.und_compra) {
      case "1":
        costo_unitario = item.costo_unitario * item.cant_1;
        break;
      case "2":
        costo_unitario = item.costo_unitario * item.cant_2;
        break;
      case "3":
        costo_unitario = item.costo_unitario * item.cant_3;
        break;
      case "4":
        costo_unitario = item.costo_unitario * item.cant_4;
        break;
    }
    $("#costo").val(costo_unitario);
    $("#promedio").val(formatNum(item.promedio || 0, '$', ConfGeneral));
    $("#ultimo_costo").val(formatNum(((item.ultimo_costo_compra || item.costo_unitario || 0) * (item.can_und || 1)), '$', ConfGeneral));
    $("#promedio_calculado").val(formatNum(item.promedio || 0, '$', ConfGeneral));
    $("#id_referencia").val(item.id);
    $("#descripcion_corta").val(item.descripcion);
    $("#descripcion_larga").val(item.descripcion_larga);
    $("#codigo").val(item.codigo);
    $("#iva").val(item.iva);
    $("#pcj_descuento").val(item.pcj_descuento)
    $("#ibua").val(item.ibua)
    $("#icui").val(item.icui)
    $("#impoconsumo").val(item.impoconsumo)

    let iva = $("#iva").val();
    let costo_neto = parseFloat(costo_unitario) * (1 + (parseFloat(iva) / 100));
    $("#costo_neto").val(costo_neto);
    $("#cantidad").val(item.cantidad / item.can_und)

    if (ConfGeneral.ManejaFechaLote == 'S') {
      $("#id_lote").val(item.id_lote);
      $("#lote").val(item.lote);
      $("#vmcto").val(item.vencimiento);
    }

    $("#id_detalle").val(item.id_detalle)
    $("#stock").val(item.total_stock + " unidades")
    let undOpt = ``;

    unidades = [
      [item.und_1, item.cant_1, 1],
      [item.und_2, item.cant_2, 2],
      [item.und_3, item.cant_3, 3],
      [item.und_4, item.cant_4, 4]
    ]
    unidades.sort(function (a, b) {
      return b[2] - a[2];
    });
    unidades.forEach(element => {
      selected = ''
      if (element[2] == item.und_compra) {
        selected = 'selected'
      }
      if (element[1] > 0) {
        undOpt += `<option ${selected} value="${element[1]}">${element[0]}-${element[1]}</option>`;
      }

    });
    $("#unidad").html(undOpt).trigger("change");

    seleccionPrecioRecepcionModal = { listaId: null, campo: 'precio' };

    setTimeout(function () {
      $("#cantidad").focus().select();
    }, 500);


    $('.format-num').each(function () {
      new Cleave(this, {
        numeral: true,
        numeralThousandsGroupStyle: 'thousand',
        numeralDecimalMark: allowDecimals ? '.' : '',
        numeralDecimalScale: allowDecimals ? ConfGeneral.NumeroDecimales : 0,
      });
    });

    /* new Cleave(".format-num", {
       numeral: true,
       numeralThousandsGroupStyle: 'thousand',
       numeralDecimalMark: '.',
       numeralDecimalScale: 2,
     });*/
    await cargarListasReferenciaModal(item.id, $("#id_transaccion").val());
    $("#cantidad").trigger("input")

  } catch (e) {
    console.error(e)
  }
}

const cargarReferencia = async (ob) => {
  limpiarReferencia();
  $("#id_referencia").val(ob.id);
  $("#descripcion_corta").val(ob.descripcion);
  $("#descripcion_larga").val(ob.descripcion_larga);
  $("#codigo").val(ob.codigo);

  if ($.trim(ob.maneja_lote) === 'N') {

    const fechaFormateada = '9999-01-01';

    $("#lote")
      .val("DEFAULT")
      .prop("readonly", true)
      .prop("tabIndex", -1);

    $("#vmcto")
      .val(fechaFormateada)
      .prop("readonly", true)
      .prop("tabIndex", -1);

    $("#id_lote").val("0");

    $("#btnBuscarLotes").hide();

    $("#unidad").prop("tabIndex", 4);
    $("#btnAddReferencia").prop("tabIndex", 5);

    // Opcional: llevar el foco directamente a unidad
    setTimeout(() => {
      $("#unidad").focus();
    }, 100);

  } else {

    $("#lote")
      .val("")
      .prop("readonly", false)
      .prop("tabIndex", 4);

    $("#vmcto")
      .val("")
      .prop("readonly", false)
      .prop("tabIndex", 5);

    $("#id_lote").val("0");

    $("#btnBuscarLotes").show();

    $("#unidad").prop("tabIndex", 6);
    $("#btnAddReferencia").prop("tabIndex", 7);
  }

  $("#promedio").val(formatNum(ob.promedio, '$', ConfGeneral));
  $("#promedio_calculado").val(formatNum(ob.promedio, '$', ConfGeneral));
  $("#costo_promedio").val(formatNum(ob.promedio, '$', ConfGeneral));
  $("#ultimo_costo").val(formatNum(ob.ultimo_costo_compra, '$', ConfGeneral));
  costo_unitario = 0;
  //se cambia el ob.costo_unitario por ob.ultimo_costo_compra
  switch (ob.und_compra) {
    case "1":
      costo_unitario = ob.ultimo_costo_compra * ob.cant_1;
      break;
    case "2":
      costo_unitario = ob.ultimo_costo_compra * ob.cant_2;
      break;
    case "3":
      costo_unitario = ob.ultimo_costo_compra * ob.cant_3;
      break;
    case "4":
      costo_unitario = ob.ultimo_costo_compra * ob.cant_4;
      break;
  }


  $("#costo").val(costo_unitario);
  $("#costo_unitario").val(ob.costo_unitario);
  $("#iva").val(ob.iva);
  $("#impoconsumo").val(ob.impoconsumo)

  if (ob.porcentaje_icui > 0) {
    $("#icui").attr("readonly", "readonly")
    $("#icui").val(ob.porcentaje_icui)
    $("#ibua").attr("readonly", "readonly")
    $("#ibua").val(0)
  } else if (ob.ibua > 0) {
    $("#icui").attr("readonly", "readonly")
    $("#icui").val(0)
    $("#ibua").attr("readonly", "")
    $("#ibua").val(ob.ibua)
  } else {
    $("#icui").attr("readonly", "")
    $("#ibua").attr("readonly", "")
    $("#icui").val(0)
    $("#ibua").val(0)
  }

  $("#modalAdd").modal('show');
  let iva = $("#iva").val();
  let costo_neto = parseFloat(costo_unitario) * (1 + (parseFloat(iva) / 100))
  $("#costo_neto").val(formatNum(costo_neto, '$', ConfGeneral));
  $("#stock").val(ob.total_stock + " unidades");

  let undOpt = ``;

  unidades = [
    [ob.und_1, ob.cant_1, 1],
    [ob.und_2, ob.cant_2, 2],
    [ob.und_3, ob.cant_3, 3],
    [ob.und_4, ob.cant_4, 4]
  ]
  unidades.sort(function (a, b) {
    return b[2] - a[2];
  });
  unidades.forEach(element => {
    selected = ''
    if (element[2] == ob.und_compra) {
      selected = 'selected'
    }
    if (element[1] > 0) {
      undOpt += `<option ${selected} value="${element[1]}">${element[0]}-${element[1]}</option>`;
    }

  });

  $("#unidad").html(undOpt).trigger("change");

  seleccionPrecioRecepcionModal = { listaId: null, campo: 'precio' };


  setTimeout(function () {
    $("#cantidad").focus().select();
  }, 500);

  /*
  new Cleave(".format-num", {
    numeral: true,
    numeralThousandsGroupStyle: 'thousand',
    numeralDecimalMark: '.',
    numeralDecimalScale: 2,
  });*/
  $('.format-num').each(function () {
    new Cleave(this, {
      numeral: true,
      numeralThousandsGroupStyle: 'thousand',
      numeralDecimalMark: allowDecimals ? '.' : '',
      numeralDecimalScale: allowDecimals ? ConfGeneral.NumeroDecimales : 0,
    });
  });

  await cargarListasReferenciaModal(ob.id, $("#id_transaccion").val());
}

const MostrarPedido = async (data) => {
  showLoadingWithTetrisLogo('Cargando items, por favor espere...', 'ISA Software');
  $("#items_ tbody tr").empty();
  $("#id_transaccion").val(data.id_transaccion);
  $("#id_tercero").val(data.id_tercero);
  $("#id_tercero_dir").val(data.id_tercero_dir)
  $("#textDireccion").val(data.direccion)
  $("#nit").val(data.nit);
  $("#tratamiento").val(data.nombres);
  $("#razon_comercial").val(data.razon_comercial);
  $("#bodegas").val(data.id_bodega);
  $("#tipo").val(data.id_tipo_documento);
  $("#condicion").val(data.id_condicion_pago);
  await listarConceptosRetencion();
  await cargarPerfilTributarioDesdeTercero(data.id_tercero);
  restaurarRetencionesEncabezado(data);
  $("#notas").val(data.notas);

  $("#total").val(formatNum(data.valor_total, '$', ConfGeneral)); $("#numero").val(data.numero);
  $("#documento").val(data.documento)

  item = await enviarPeticion({
    op: 'busqueda_temporal_det',
    link: '../shared/servicios/servicios.php',
    id_transaccion: data.id_transaccion
  });

  if (item.length > 0) {
    let tr = ''
    item.forEach(row => {
      can_und = row.can_und || 1;
      costo = row.costo_unitario;
      costo_ = costo * (1 - (row.pcj_descuento / 100));
      iva = costo_ * (row.pcj_iva / 100)
      impoconsumo = row.impoconsumo || 0;
      ibua = row.ibua || 0;
      icui = costo_ * (row.icui / 100);
      costo_neto = Math.round((parseFloat(((costo_ + parseFloat(iva)) * can_und)) + parseFloat(impoconsumo) + parseFloat(ibua) + parseFloat(icui)))
      cantidad = row.cantidad / can_und;
      costo_total = Math.round(costo_neto * cantidad);
      costo_ultimo = row.costo_ultimo || 0;
      // --- LÓGICA DE BADGE DINÁMICO AL CARGAR EL PEDIDO ---
      // Limpiamos la descripción por si ya venía con un badge concatenado de antes
      let descripcionLimpia = row.descripcion ? row.descripcion.split(' - ')[0] : '';
      let badgeHtml = "";

      if (costo_ultimo > 0 && can_und > 0) {
        const costoNetoMinimo = costo_neto / can_und; // Normalizado a unidad mínima
        if (costoNetoMinimo > costo_ultimo) {
          badgeHtml = `<div class="mt-1"><span class="badge bg-danger text-white">Subió 📈</span></div>`;
        } else if (costoNetoMinimo < costo_ultimo) {
          badgeHtml = `<div class="mt-1"><span class="badge bg-success text-white">Bajó 📉</span></div>`;
        } else {
          badgeHtml = `<div class="mt-1"><span class="badge bg-secondary text-white">Se mantuvo ➖</span></div>`;
        }
      } else {
        // Si no hay un costo previo de referencia en la base de datos
        badgeHtml = `<div class="mt-1"><span class="badge bg-info text-white">Primer registro</span></div>`;
      }
      // ---------------------------------------------------

      if (ConfGeneral.ManejaFechaLote == 'S') {

        tr += ` <tr class="det_${row.id_transaccion_det_tmp}">
                  <td>${row.codigo}</td>
                  <td>
                    ${descripcionLimpia}
                    ${badgeHtml}
                  </td>
                  <td>${formatNum(costo * can_und, '$', ConfGeneral)}</td>
                  <td>${formatNum(costo_neto, '$', ConfGeneral)}</td>
                  <td>${row.pcj_iva}</td>
                  <td>${row.vmcto}</td>
                  <td>${row.lote}</td>
                  <td class="text-center">${cantidad}</td>
                  <td class="text-center">${row.und}</td>
                  <td>${row.can_und}</td>
                  <td>${formatNum(costo_total, '$', ConfGeneral)}</td>
                  <td align="center"><button class="btn btn-sm btn-outline-danger btnEliminar" onclick="confirmarEliminar(this,${row.id_transaccion_det_tmp})"><i class="fa-regular fa-trash-can"></i></button></td>
                  <td align="center"><button class="btn btn-sm btn-outline-primary btnEditar" onclick="abrirModalReferencia(${row.id_transaccion_det_tmp})"><i class="fa-regular fa-pen-to-square "></i></td>
                  <td style="display:none">${row.id_transaccion_det_tmp}</td>
                </tr>`

      } else {
        tr += ` <tr class="det_${row.id_transaccion_det_tmp}">
                  <td>${row.codigo}</td>
                  <td>
                    ${descripcionLimpia}
                    ${badgeHtml}
                  </td>
                  <td>${formatNum(costo * can_und, '$', ConfGeneral)}</td>
                  <td>${formatNum(costo_neto, '$', ConfGeneral)}</td>
                  <td>${row.pcj_iva}</td>
                  <td class="text-center">${cantidad}</td>
                  <td class="text-center">${row.und}</td>
                  <td>${row.can_und}</td>
                  <td>${formatNum(costo_total, '$', ConfGeneral)}</td>
                  <td align="center"><button class="btn btn-sm btn-outline-danger btnEliminar" onclick="confirmarEliminar(this,${row.id_transaccion_det_tmp})"><i class="fa-regular fa-trash-can"></i></button></td>
                  <td align="center"><button class="btn btn-sm btn-outline-primary btnEditar" onclick="abrirModalReferencia(${row.id_transaccion_det_tmp})"><i class="fa-regular fa-pen-to-square "></i></td>
                  <td style="display:none">${row.id_transaccion_det_tmp}</td>            
                 </tr>`
      }


    });
    $("#items_ tbody").append(tr);
    $('#tabs button[data-bs-target="#navs-creacion"]').attr('disabled', false)
    recalcularEncabezado(data.id_transaccion)
    bloquearCampos(true);
    limpiarFiltrosTemp()
    dissminSwal();
  }

}

const buscarTemporales = async () => {

  try {
    const fecha_inicial = $.trim($("#fecha_inicial").val());
    const fecha_final = $.trim($("#fecha_final").val());
    showLoadingWithTetrisLogo('Buscando información', 'ISA Software')
    const resp = await enviarPeticion({
      op: 'busqueda_temporal',
      link: '../shared/servicios/servicios.php',
      fecha_inicial,
      fecha_final,
      sw: 1,
      bodega: $("#bodegas").val(),
    });

    dissminSwal();

    if (resp.data.length == 0) {
      $("#temporales").html(`
        <div class="alert alert-warning alert-dismissible mt-3" role="alert">
            <span><i class="fa-regular fa-bell"></i></span>
            No se encontraron resultados!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`);
      return;
    }

    let tabla = `<table class="table table-sm table-hover table-striped mt-2">
                  <thead class="table-primary">
                    <tr>
                      <th>Fecha</th>
                      <th>Tipo</th>
                      <th>Numero</th>
                      <th>Nit</th>
                      <th>Tercero</th>
                      <th>Valor</th>
                      <th></th>
                      <th></th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>`;

    // Generar filas
    resp.data.forEach(element => {
      tabla += `<tr>
                  <td>${element.fecha_hora}</td>
                  <td>${element.tipo}</td>
                  <td>${element.numero}</td>
                  <td>${element.nit}</td>
                  <td>${element.nombres}</td>
                  <td>${formatNum(element.valor_total, '$', ConfGeneral)}</td>
                  <td><button class="btn btn-outline-primary btn-sm btn-imprimir" data-element='${JSON.stringify(element)}'><i class="fa fa-print"></i></button></td>
                  <td><button class="btn btn-outline-primary btn-sm btn-recuperar" data-element='${JSON.stringify(element)}'><i class="fa fa-pencil"></i></button></td>
                  <td><button class="btn btn-outline-danger btn-sm" onclick="confirmarEliminarPedido(${element.id_transaccion})"><i class="fa-regular fa-trash-can"></i></button></td>
                </tr>`;
    });

    tabla += `</tbody></table>`;

    // Insertar tabla completa en el DOM
    $("#temporales").html(tabla);

    // Agregar eventos de clic a los botones de editar
    $(".btn-recuperar").click(function () {
      const element = JSON.parse($(this).attr("data-element"));
      MostrarPedido(element); // Pasar el objeto 'element' a la función
      $('#tabs button[data-bs-target="#navs-creacion"]').tab('show') // Select tab by name
    });
    $(".btn-imprimir").click(function () {
      const element = JSON.parse($(this).attr("data-element"));
      imprimirTemporal(element); // Pasar el objeto 'element' a la función
    });

  } catch (e) {
    dissminSwal();
    Swal.fire("Error", "Se generó un error interno", "error");
  }
};

const imprimirTemporal = (data) => {
  const base = 'https://thecyctechnology.com/development';
  const esDev = window.location.href.startsWith(base);

  if (esDev) {
    url = 'https://thecyctechnology.com/development/app/pdf/';
  } else {
    url = 'https://thecyctechnology.com/cyc/app/pdf/';
  }
  const urlImprimir = `${url}compras-tmp.php?id=${data.id_transaccion}`;
  // Eliminar el modal si ya existe
  $("#modalDoc").remove();

  // Crear el modal y el iframe
  $("body").append(`
    <div class="modal fade" id="modalDoc" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Documento Temporal de ingreso</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div>
              <iframe width="100%" style="height:400px; border:none;" src="${urlImprimir}" frameborder="0"></iframe>
            </div>
          </div>
        </div>
      </div>
    </div>
  `);

  // Mostrar el modal 
  $("#modalDoc").modal("show");
}

const consultarLote = async (id_referencia, lote) => {
  try {
    const resp = await enviarPeticion({
      op: 'consultar_lote',
      link: '../shared/servicios/servicios.php',
      id_referencia,
      lote
    });

    if (resp.length > 0) {
      lotes = resp[0];
      $("#vmcto").val(lotes.vencimiento)
      $("#lote").val(lotes.lote)
    }
  } catch (error) {
    console.error({
      error: 'error al consultar el lote'
    })
  }
}

const bloquearCampos = (sw) => {
  $(".bloqueado").attr("disabled", sw)
}

const abrirTabTercero = () => {
  const selector = '#tabs button[data-bs-target="#navs-tercero"]';
  const trigger = document.querySelector(selector);

  if (!trigger) {
    return;
  }

  const tab = bootstrap.Tab.getInstance(trigger) || new bootstrap.Tab(trigger);
  tab.show();
  $(trigger).trigger("click");
}

const validarAccesoCreacion = () => {
  const idTercero = String($("#id_tercero").val() || '').trim();
  const concepto = String($("#concepto_retencion").val() || '').trim();
  const documento = $.trim($("#documento").val());

  if (!idTercero || idTercero === '0') {
    abrirTabTercero();
    Swal.fire("Oops", "Debe cargar un tercero", "warning");
    return false;
  }

  if ($("#concepto_retencion option").length > 1 && !concepto) {
    abrirTabTercero();
    Swal.fire("Oops", "Debe seleccionar un concepto de retención", "warning");
    return false;
  }

  if (permisos.DocumentoRefRequerido && documento === '') {
    abrirTabTercero();
    Swal.fire("Oops", "El documento de referencia es requerido", "warning");
    return false;
  }

  return true;
}

const validarCamposBlock = () => {

  n_fila = $("#items_ tbody tr").length;

  if (n_fila == 0) {
    limpiarDatosPedido()
    limpiarFiltrosTemp()
    limpiarReferencia()
    bloquearCampos(false)
  } else {
    bloquearCampos(true)
  }
}

const actualizarNotas = async (id_transaccion) => {
  try {
    $("#notas").attr("disabled", true)
    const resp = await enviarPeticion({
      link: '../models/IngresosMercancias.php',
      op: 'actualizarNota',
      id_transaccion,
      nota: $("#notas").val()
    });

    $("#notas").attr("disabled", false)
  } catch (error) {
    $("#notas").attr("disabled", false)
  }
}

const cargarDirecciones = async (id_tercero) => {

  try {
    const resp = await enviarPeticion({
      link: '../models/Pedidos.php',
      op: 'listarDirecciones',
      id_tercero
    });

    if (resp.length == 0) {
      Swal.fire("Error", "No se encontraron direcciones para este tercero, por favor ingrese una dirección para continuar", "error");
      return false;
    }

    p = '';

    resp.forEach(item => {
      selected = ''
      if (item.principal == 1) {
        selected = 'selected'
      }
      p += `<option  ${selected} value="${item.id}">${item.direccion}</option>`
    })

    $("#id_tercero_dir").html(p);
    return true;

  } catch (e) {
    console.error(e)
  }

}

const listarPerfiles = async () => {
  try {
    const data = {
      link: '../models/perfilesContables.php',
      op: 'listar-todos'
    }
    const resp = await enviarPeticion(data);
    let option = ``;
    resp.data.forEach((d) => {
      option += `<option value="${d.id}">${d.descripcion}</option>`;
    });
    $("#slcPerfilContable").html(option);
  } catch (e) {
    console.log(e);
  }
}

const marcas = async () => {
  const resp = await enviarPeticion({
    link: '../models/Marcas.php',
    op: 'listar',
    filtro: ''
  });

  let option = ``
  resp.data.forEach(item => {
    option += `<option value="${item.id}">${item.descripcion}</option>`
  });

  $("#slcMarcas").html(option);
}

const ultimoCodigo = async () => {
  const resp = await enviarPeticion({
    link: '../models/Referencias.php',
    op: 'ultimo_codigo'
  })

  $("#ultimo_codigo").val(resp.codigo)
}

const getIva = async () => {
  try {
    const dataSend = {
      link: '../models/Referencias.php',
      op: 's_iva_perfil',
      id_perfil: $("#slcPerfilContable").val()
    };
    const resp = await enviarPeticion(dataSend);
    return resp[0];
  } catch (e) {
    console.log(e);
    Swal.fire('Error!', 'Un error ha ocurrido, por favor notifique a su administrador.', 'error');
  }
};

/**
 * =============================================================
 * PROCESAR FACTURA ELECTRÓNICA XML
 * =============================================================
 */
async function procesarFacturaElectronicaXML(archivo) {
  /*
   * Validar extensión
   */
  const nombre = archivo.name.toLowerCase();

  if (!nombre.endsWith('.xml')) {
    Swal.fire(
      'Archivo inválido',
      'Para importar una factura electrónica debes seleccionar un archivo XML.',
      'error'
    );

    return;
  }


  showLoadingWithTetrisLogo(
    'Procesando factura electrónica...',
    'ISA Software'
  );

  try {

    const formData = new FormData();

    formData.append(
      'archivo_xml',
      archivo
    );

    formData.append(
      'op',
      'procesar_factura_electronica'
    );

    const response =
      await fetch(
        '../models/HomologacionCodigos.php',
        {
          method: 'POST',
          body: formData
        }
      );

    if (!response.ok) {
      throw new Error(
        'El servidor respondió con código ' +
        response.status
      );
    }


    const data = await response.json();
    /*
     * ---------------------------------------------------------
     * ERROR DEL SERVIDOR
     * ---------------------------------------------------------
     */

    if (
      data.error ||
      data.exito === false
    ) {

      dissminSwal();

      Swal.fire(
        'Error',
        data.mensaje ||
        data.message ||
        'No fue posible procesar la factura electrónica.',
        'error'
      );

      return;
    }


    /*
     * ---------------------------------------------------------
     * VALIDAR DATOS
     * ---------------------------------------------------------
     */

    if (
      !data.data ||
      !Array.isArray(data.data)
    ) {

      dissminSwal();

      Swal.fire(
        'Error',
        'La factura fue procesada pero no se encontraron productos.',
        'error'
      );

      return;
    }


    /*
     * ---------------------------------------------------------
     * GUARDAR EN LA MISMA VARIABLE DEL PLANO
     * ---------------------------------------------------------
     */

    filasArchivoMasivo = data.data;

    /*
     * ---------------------------------------------------------
     * VALIDAR QUE EXISTAN PRODUCTOS
     * ---------------------------------------------------------
     */

    if (
      filasArchivoMasivo.length === 0
    ) {

      dissminSwal();

      Swal.fire(
        'Alerta',
        'La factura electrónica no contiene productos.',
        'warning'
      );

      return;
    }


    /*
     * ---------------------------------------------------------
     * NIT DEL PROVEEDOR
     * ---------------------------------------------------------
     */

    const nitProveedor = filasArchivoMasivo[0].nit_proveedor || '';
    if (!nitProveedor) {
      dissminSwal();
      Swal.fire(
        'Error',
        'No fue posible determinar el NIT del proveedor.',
        'error'
      );
      return;
    }


    /*
     * ---------------------------------------------------------
     * VALIDAR NIT CONTRA EL TERCERO
     * ---------------------------------------------------------
     */

    const resp =
      await enviarPeticion({
        link: '../models/HomologacionCodigos.php',
        op: 'validar_tercero',
        nit: nitProveedor
      });

    if (resp.error) {
      dissminSwal();
      Swal.fire(
        'Error',
        'El NIT del proveedor no es válido o no existe.',
        'error'
      );
      return;
    }


    if (
      Array.isArray(resp.errores) &&
      resp.errores.length > 0
    ) {

      const erroresHtml = resp.errores.map(err => `<li>${err}</li>`).join('');
      dissminSwal();
      Swal.fire(
        'Error',
        `<ul>${erroresHtml}</ul>`,
        'error'
      );

      return;
    }


    /*
     * ---------------------------------------------------------
     * VALIDAR NIT DE LA EMPRESA
     * ---------------------------------------------------------
     */

    if (
      nitProveedor.trim() != $("#nit").val().trim()
    ) {
      dissminSwal();
      Swal.fire(
        'Error',
        'El NIT del proveedor no coincide con el NIT registrado.',
        'error'
      );
      return;
    }


    /*
     * ---------------------------------------------------------
     * HOMOLOGAR PRODUCTOS
     * ---------------------------------------------------------
     */

    const dataRef =
      await enviarPeticion({
        link: linkHomologacionCodigosModel,
        op: 'previsualizar_homologacion',
        rows: JSON.stringify(filasArchivoMasivo)
      });
    if (
      dataRef.errores &&
      dataRef.errores.length > 0
    ) {

      $("#errores").html('');
      let erroresHtml = '<ul class="alert alert-danger">';
      dataRef.errores.forEach(error => {
        erroresHtml +=
          `<li>${error}</li>`;

      });
      erroresHtml += '</ul>';
      $("#errores").html(erroresHtml).show();

      dissminSwal();

      return;
    }

    /*
     * ---------------------------------------------------------
     * MOSTRAR PREVISUALIZACIÓN
     * ---------------------------------------------------------
     */

    renderPreviewCargaMasiva(
      dataRef.data || []
    );

    dissminSwal();

    /*
     * ---------------------------------------------------------
     * MENSAJE
     * ---------------------------------------------------------
     */

    Swal.fire({
      icon: 'success',
      title: 'Factura electrónica procesada',
      html: `Se encontraron <strong>${filasArchivoMasivo.length}</strong> líneas de productos.`,
      timer: 1800,
      showConfirmButton: false
    });

  } catch (error) {
    console.error(
      'Error procesando factura electrónica:',
      error
    );
    dissminSwal();
    Swal.fire(
      'Error',
      error.message ||
      'No fue posible procesar la factura electrónica.',
      'error'
    );
  }
}


$(async function () {

  fechaActual([
    ['fecha_inicial', 'fecha_final'],
    'fecha'
  ])

  await listarAlmacenes();
  await listarTipoDocumentos(1);
  await listarConceptosRetencion();
  await condicionesPago('P', 'condicion');
  permisos = await permisosUsuario(id_modulo);

  const {
    general,
    modulos
  } = await cargarConfiguraciones();
  ConfGeneral = general;
  ConfModulos = modulos[id_modulo];

  if (permisos.CrearReferenciaCompras) {
    $("#btnCrearReferencia").removeClass("d-none");
  } else {
    $("#btnCrearReferencia").addClass("d-none");
  }


  if (ConfGeneral.ManejaFechaLote == 'S') {

    tr_items = `    
              <th>Código</th>
              <th>Descripción</th>
              <th>Costo</th>
              <th>Costo. N.</th>
              <th>IVA (%)</th>
              <th>F. Vto</th>
              <th>Lote</th>
              <th>Cantidad</th>
              <th>Unidad</th>
              <th>Conv</th>
              <th>Costo T</th>
              <th>Eliminar</th>
              <th>Editar</th>
      `
  } else {
    tr_items = `             
              <th>Código</th>
              <th>Descripción</th>
              <th>Costo</th>
              <th>Costo. N.</th>
              <th>IVA (%)</th>
              <th>Cantidad</th>
              <th>Unidad</th>
              <th>Conv</th>
              <th>Costo T</th>
              <th>Eliminar</th>
              <th>Editar</th>`;
    $(".clase-col-md-3-costo-add-item").removeClass("col-md-3").addClass("col-md-6")
    $(".maneja-fecha-lotes").remove()
  }

  $(".tr-head-items").html(tr_items)

  $("#btnLimpiarMasivo").on('click', function () {
    filasArchivoMasivo = [];
    previewCargaMasiva = [];
    $('#inputArchivoMasivo').val('');
    renderPreviewCargaMasiva([]);
  });

  /*  ********************************************* */

  $("#lblInvalidasMasivo").click(() => {
    activo = !activo;
    if (!activo) {
      $("#tblPreviewMasivo tbody tr").show();
      return;
    }


    $("#tblPreviewMasivo tbody tr").each(function () {

      const invalidas = $(this).hasClass("table-danger");
      if (!invalidas) {
        $(this).hide();
      }
    });

  });


  $('#btnCargaMasiva').on('click', function () {
    //debo cargar el tercero primero  
    if ($("#id_tercero").val() == 0 || $("#id_tercero").val() == '') {
      Swal.fire('Error', 'Debe seleccionar un tercero antes de realizar la carga masiva', 'error');
      return;
    }


    let concepto = $("#concepto_retencion").val();

    if ($("#concepto_retencion option").length > 1 && concepto === "") {
      Swal.fire("Error", "Debe seleccionar un concepto de retención", "error");
      return;
    }

    filasArchivoMasivo = [];
    previewCargaMasiva = [];
    $('#inputArchivoMasivo').val('');
    renderPreviewCargaMasiva([]);
    $('#modalCargaMasiva').modal('show');
  });

  //nuevo christian inicio

  /**
   * CAMBIO DE TIPO DE IMPORTACIÓN
   */
  $('input[name="tipoCargaMasiva"]').on('change', function () {
    const tipo = $('input[name="tipoCargaMasiva"]:checked').val();
    const input = $('#inputArchivoMasivo');
    if (tipo === 'XML') {
      input.attr('accept', '.xml').val('');
      $('#ayudaTipoArchivo').text(
        'Selecciona la factura electrónica XML enviada por tu proveedor.'
      );
      $('#infoCargaPlano').hide();
      $('#infoCargaXML').show();
    } else {
      input.attr('accept', '.txt,.csv,.xlsx,.xls').val('');
      $('#ayudaTipoArchivo').text(
        'Selecciona un archivo CSV, TXT, XLS o XLSX.'
      );
      $('#infoCargaXML').hide();
      $('#infoCargaPlano').show();
    }
    /*
     * Limpiar información anterior
     */
    filasArchivoMasivo = [];

    $('#tbodyPreviewMasivo').html(`
        <tr>
            <td colspan="22" class="text-center text-muted py-4">
                <div class="d-flex flex-column align-items-center justify-content-center">
                    <i class="fa-solid fa-inbox fa-lg mb-2"></i>
                    <span>Sin datos para previsualizar</span>
                </div>
            </td>
        </tr>
    `);


    $('#errores').hide().html('');
    $('#lblTotalMasivo').text('0');
    $('#lblValidasMasivo').text('0');
    $('#lblInvalidasMasivo').text('0');
  });

  $('#btnPrevisualizarMasivo').on('click', async function () {
    $("#errores").html('').hide();
    const tipoCarga = $('input[name="tipoCargaMasiva"]:checked').val();
    const archivo = $('#inputArchivoMasivo')[0].files[0];
    /*
     * ---------------------------------------------------------
     * VALIDAR ARCHIVO
     * ---------------------------------------------------------
     */
    if (!archivo) {
      Swal.fire(
        'Alerta',
        'Debes seleccionar un archivo.',
        'warning'
      );
      return;
    }
    /*
     * =========================================================
     * FACTURA ELECTRÓNICA XML
     * =========================================================
     */

    if (tipoCarga === 'XML') {
      await procesarFacturaElectronicaXML(archivo);
      return;
    }


    /*
     * =========================================================
     * PLANO TRADICIONAL
     * =========================================================
     */

    showLoadingWithTetrisLogo(
      'Cargando por favor espere...',
      'ISA Software'
    );

    try {

      filasArchivoMasivo = await leerArchivoMasivoIngresos(archivo);

      if (!filasArchivoMasivo.length) {

        dissminSwal();

        Swal.fire(
          'Alerta',
          'El archivo no contiene filas válidas',
          'warning'
        );

        return;
      }

      const primeraFila = filasArchivoMasivo[0];
      const nitProveedor = primeraFila.nit_proveedor || '';

      if (nitProveedor.trim() === '') {

        dissminSwal();
        Swal.fire(
          'Error',
          'El NIT del proveedor no puede estar vacío en la primera fila',
          'error'
        );
        return;
      }
      if (
        nitProveedor.trim() !=
        $("#nit").val().trim()
      ) {
        dissminSwal();
        Swal.fire(
          'Error',
          'El NIT del proveedor no coincide con el NIT registrado',
          'error'
        );

        return;
      }
      try {
        const resp =
          await enviarPeticion({
            link: '../models/HomologacionCodigos.php',
            op: 'validar_tercero',
            nit: nitProveedor
          });

        if (resp.error) {
          dissminSwal();
          Swal.fire(
            'Error',
            'El NIT del proveedor no es válido o no existe.',
            'error'
          );
          return;
        }
        if (
          Array.isArray(resp.errores) &&
          resp.errores.length > 0
        ) {

          const erroresHtml =
            resp.errores
              .map(err => `<li>${err}</li>`)
              .join('');


          dissminSwal();

          Swal.fire(
            'Error',
            `<ul>${erroresHtml}</ul>`,
            'error'
          );

          return;
        }


        /*
         * -------------------------------------------------
         * VALIDAR FILAS
         * -------------------------------------------------
         */

        const erroresFila = [];

        for (let i = 0; i < filasArchivoMasivo.length; i++) {

          const fila = filasArchivoMasivo[i];

          /*
           * Número factura
           */
          if (
            !fila.numero_factura ||
            fila.numero_factura.length > 30
          ) {

            erroresFila.push(
              `Fila ${i + 1}: El número de factura no puede estar vacío y debe tener máximo 30 caracteres.`
            );

          }


          /*
           * Código proveedor
           */
          if (
            !fila.codigo_proveedor ||
            fila.codigo_proveedor.trim() === ''
          ) {

            erroresFila.push(
              `Fila ${i + 1}: El código de proveedor no puede estar vacío.`
            );

          }


          /*
           * NIT
           */
          if (
            !fila.nit_proveedor ||
            fila.nit_proveedor.trim() === ''
          ) {

            erroresFila.push(
              `Fila ${i + 1}: El NIT del proveedor no puede estar vacío.`
            );

          }


          /*
           * Cantidad
           */
          if (
            isNaN(parseInt(fila.cantidad)) ||
            parseInt(fila.cantidad) <= 0
          ) {

            erroresFila.push(
              `Fila ${i + 1}: La cantidad debe ser un número mayor a 0.`
            );

          }


          /*
           * Costo
           */
          if (
            isNaN(parseFloat(fila.costo))
          ) {

            erroresFila.push(
              `Fila ${i + 1}: El costo debe ser un número válido.`
            );

          }


          /*
           * Descuento
           */
          if (
            isNaN(
              parseFloat(
                fila.porcentaje_descuento
              )
            )
          ) {

            erroresFila.push(
              `Fila ${i + 1}: El porcentaje de descuento debe ser un número válido.`
            );

          }


          /*
           * FECHA
           */
          if (fila.fecha_vencimiento) {

            fila.fecha_vencimiento =
              fila.fecha_vencimiento.replace(
                /\//g,
                '-'
              );


            const partesFecha =
              fila.fecha_vencimiento.split('-');


            if (partesFecha.length === 3) {

              const dia = partesFecha[0].padStart(2, '0');
              const mes = partesFecha[1].padStart(2, '0');
              const anio = partesFecha[2];

              fila.fecha_vencimiento = `${dia}-${mes}-${anio}`;
            }

          }


          if (
            !fila.fecha_vencimiento ||
            !/^\d{2}-\d{2}-\d{4}$/.test(
              fila.fecha_vencimiento
            )
          ) {

            erroresFila.push(
              `Fila ${i + 1}: La fecha debe tener el formato dd-mm-yyyy.`
            );

          }


          /*
           * LOTE
           */
          if (
            !fila.lote ||
            fila.lote.trim() === ''
          ) {

            erroresFila.push(
              `Fila ${i + 1}: El campo 'lote' no puede estar vacío.`
            );

          }

        }


        /*
         * -------------------------------------------------
         * MOSTRAR ERRORES
         * -------------------------------------------------
         */

        if (erroresFila.length > 0) {
          $("#errores").html('');
          let erroresHtml = '<ul class="alert alert-danger">';
          erroresFila.forEach(error => {
            erroresHtml +=
              `<li>${error}</li>`;
          });
          erroresHtml += '</ul>';
          $("#errores").html(erroresHtml).show();
          dissminSwal();
          return;
        }


        /*
         * -------------------------------------------------
         * HOMOLOGACIÓN
         * -------------------------------------------------
         */

        const dataRef =
          await enviarPeticion({
            link: linkHomologacionCodigosModel,
            op: 'previsualizar_homologacion',
            rows: JSON.stringify(filasArchivoMasivo)

          });
        if (
          dataRef.errores &&
          dataRef.errores.length > 0
        ) {

          $("#errores").html('');

          let erroresHtml = '<ul class="alert alert-danger">';

          dataRef.errores.forEach(error => {
            erroresHtml += `<li>${error}</li>`;
          });

          erroresHtml += '</ul>';
          $("#errores").html(erroresHtml).show();
          dissminSwal();
          return;
        }


        /*
         * -------------------------------------------------
         * RENDER
         * -------------------------------------------------
         */

        renderPreviewCargaMasiva(dataRef.data || []);
        dissminSwal();
      } catch (e) {
        console.error('Error validando el NIT del proveedor:', e);
        dissminSwal();
        Swal.fire(
          'Error',
          'Ocurrió un error al validar el NIT del proveedor.',
          'error'
        );
        return;
      }


    } catch (error) {

      console.error({
        error
      });

      dissminSwal();

      Swal.fire(
        'Error',
        error.message ||
        'No se pudo leer el archivo',
        'error'
      );
    }

  });

  //nuevo christian fin


  $('#btnInsertarMasivo').on('click', async function () {
    try {
      await insertarCargaMasivaTMP();

    } catch (error) {
      console.error(error);
      dissminSwal();
      Swal.fire('Error', 'Error guardando la homologación', 'error');
    }
  });

  $("#notas").focusout(function () {
    const nota = $.trim($(this).val());
    if ($("#id_transaccion").val() > 0) {
      actualizarNotas($("#id_transaccion").val())
    }
  })



  $("#btnGuardar").on('click', async function () {

    try {
      showLoadingWithTetrisLogo('Guardando documento', 'ISA Software');

      if (isNaN(parseInt($("#id_transaccion").val())) || $("#id_transaccion").val() == 0) {
        Swal.fire("Error", "No se detecto un documento en proceso", "error")
        return;
      }

      if (ConfGeneral.ManejaFechaLote == 'S') {
        const lotes = await enviarPeticion({
          link: '../shared/servicios/servicios.php',
          op: 'asignar_lotes',
          id_transaccion: $("#id_transaccion").val()
        });

        if (!lotes.ok) {
          Swal.fire("Error", "No se pudieron asignar los lotes", "error");
          dissminSwal();
          return;
        }

      }

      const resp = await enviarPeticion({
        link: '../shared/servicios/servicios.php',
        op: 'volcar_documento',
        id_transaccion: $("#id_transaccion").val()
      });
      if (!resp.ok) {
        Swal.fire("error", resp.message, "error");
        return;
      }

      const respCont = await generarContabilizacion(resp.id);
      let txtCont = respCont.message ?? "";
      nuevoPedido();

      Swal.fire({
        title: 'Éxito',
        html: `<p>Documento <b>#${resp.id}</b> generado correctamente.</p>
               <p><b>Contabilización</b></p>
               <p>${txtCont}</p>`,
        icon: 'success',
        showCancelButton: true,
        confirmButtonText: 'OK',
        cancelButtonText: 'Imprimir',
        reverseButtons: true
      }).then(async (result) => {
        if (result.dismiss === Swal.DismissReason.cancel) {
          let formato = 'C';
          if (ConfModulos.FormatoImpresionCompras) {
            if (ConfModulos.FormatoImpresionCompras === 'T') {
              formato = 'T';
            }
          }
          await ImprimirDocumento(1, resp.id, 'Impresión de documento', false, '', formato);
        }
      });
    } catch (e) {
      console.error({ e })
      Swal.fire("Error", "Ha ocurrido un error interno en la aplicación", "error")
    }

  })

  $("#vmcto").focusout(function () {
    vmcto = $.trim($(this).val());
    anio = vmcto.split("-")[0]

    if (parseInt(anio) < 2024) {
      $(this).val('')
      return;
    }

  })

  $("#btnLimpiar").click(async function () {
    await nuevoPedido();
  });

  $("#buscar-temporal").click(function () {
    buscarTemporales();
  })

  $(".calculo-costo").on('input', function () {
    actualizarCostoNeto();
  })

  // Recalcular tabla de listas cuando cambie el IVA
  $("#iva").on('change', function () {
    actualizarTablaListasModal();
  });

  // Recalcular tabla de listas cuando cambien los costos
  $("#promedio, #ultimo_costo").on('input', function () {
    actualizarTablaListasModal();
  });

  $(document).on('input', '.lista-precio-edit', function () {
    marcarPrecioRecepcionModal($(this));
    actualizarFilaListaModal($(this).closest('tr'));
  });

  $(document).on('focus', '.lista-precio-edit', function () {
    marcarPrecioRecepcionModal($(this));
    const fila = $(this).closest('tr');
    const tipo = String(fila.data('tipo') || 'PF').trim();
    if (tipo === 'PF') {
      $(this).val(normalizarNumeroListaModal($(this).val()));
    }
  });

  $(document).on('blur', '.lista-precio-edit', function () {
    const fila = $(this).closest('tr');
    const tipo = String(fila.data('tipo') || 'PF').trim();
    const valor = normalizarNumeroListaModal($(this).val());
    $(this).val(formatearEditableListaModal(valor, tipo));
    actualizarFilaListaModal(fila);
  });


  $("#tipo").change(async function () {
    await listarConceptosRetencion();
  });

  $("#concepto_retencion").change(function () {
    aplicarRetencionesPorPerfil();
  });

  const inputs = document.querySelectorAll('button[tabindex],input[tabindex],select[tabindex]');

  inputs.forEach(input => {
    input.addEventListener('keydown', function (event) {

      const currentIndex = Array.from(inputs).indexOf(input);
      const currentInputId = input.id;

      if (event.key === 'Enter') {

        if (currentInputId != 'btnAddReferencia') {
          if (currentIndex < inputs.length - 1) {
            inputs[currentIndex + 1].focus();
            if (inputs[currentIndex + 1].tagName != 'SELECT') {
              inputs[currentIndex + 1].select();
            }
          }
        }

      } else if (event.key === 'ArrowLeft') {
        if (currentIndex) {
          inputs[currentIndex - 1].focus()
          if (inputs[currentIndex + 1].tagName != 'SELECT') {
            inputs[currentIndex - 1].select();
          }

        }
      }

    });
  });
  $('#costo').val(0);
  allowDecimals = ConfGeneral.NumeroDecimales > 0;
  /*
    new Cleave('.format-num', {
      numeral: true,
      numeralThousandsGroupStyle: 'thousand',
      numeralDecimalMark: allowDecimals ? '.' : '',
      numeralDecimalScale: allowDecimals ? ConfGeneral.NumeroDecimales : 0,
    });*/
  $('.format-num').each(function () {
    new Cleave(this, {
      numeral: true,
      numeralThousandsGroupStyle: 'thousand',
      numeralDecimalMark: allowDecimals ? '.' : '',
      numeralDecimalScale: allowDecimals ? ConfGeneral.NumeroDecimales : 0,
    });
  });
  $('#txtBusquedaTercero').keyup(function () {
    const tercero = $.trim($(this).val())

    if (tercero.length == 0) {
      limpiarTercero();
      return;
    }
  });

  $('#txtBusquedaTercero').autocomplete({
    source: async function (request, response) {
      const data = {
        link: '../models/Terceros.php',
        filtro: $.trim(request.term),
        tipo_tercero: '',
        estado: 'A',
        op: 's_tercero'
      };
      try {
        const resp = await enviarPeticion(data);
        let datos = resp.data;

        if (Array.isArray(datos)) {
          response(datos);
        } else {
          response([]);
        }
      } catch (error) {
        console.error("Error en la solicitud:", error);
        response([]);
      }
    },
    minLength: 3,
    select: async function (event, ui) {
      const item = ui.item;
      if (ConfModulos.SoloTercerosProveedores == 'S') {
        if (item.es_proveedor != 'S') {
          Swal.fire("Error", "El tercero no es un proveedor", "error");
          return;
        }
      }

      if (item.id_condicion_prov == 0) {
        Swal.fire("Error", "El tercero no tiene condicion de pago para proveedor", "error");
        return;
      }

      let direccionesCargadas = await cargarDirecciones(item.id);
      if (!direccionesCargadas) {
        Swal.fire("Error", "No se pudieron cargar las direcciones del tercero", "error");
        return;
      }

      $("#id_tercero").val(item.id);
      $("#nit").val(item.nit);
      $("#tratamiento").val(item.tratamiento);
      $("#razon_comercial").val(item.razon_comercial);
      $("#textDireccion").val(item.direccion)
      $("#id_tercero_dir").val(item.id_direccion);
      $("#condicion").val(item.id_condicion_prov)
      $("#id_perfil_tributario").val(item.id_perfil_tributario || 0);
      cargarPerfilTributarioPorId(item.id_perfil_tributario || 0);

      if (permisos.DocumentoRefRequerido) {
        if ($.trim($("#documento").val()).length > 0) {
          // $('#tabs button[data-bs-target="#navs-creacion"]').attr('disabled',false);
        }
      }
    }
  });

  $("#documento").focusout(function () {
    if (permisos.DocumentoRefRequerido) {
      if ($(this).val().length > 0) {
        if ($("#id_tercero").val() > 0) {
          //  $('#tabs button[data-bs-target="#navs-creacion"]').attr('disabled',false);
        }
      }
    }
  })

  $('#li_item').on('mousedown', function (e) {
    if (!validarAccesoCreacion()) {
      e.preventDefault();
      e.stopPropagation();
      return false;
    }
  })


  $("#filtro").on('keyup', async function (e) {
    const filtro = $.trim($(this).val())
    if (filtro.length == 0) {
      return;
    }
    if (e.key == 'Enter') {
      if (modalBusquedaRef) {
        modalBusquedaRef.destroy();
        modalBusquedaRef = null;
      }
      modalBusquedaRef = new ModalBusquedaProducto((rowData) => {
        cargarReferencia(rowData);
      }, {
        check_stock: false
      });

      const resp = await modalBusquedaRef.getRecords({
        data: filtro,
        cod_barras: $("#cod_barras").is(":checked")
      });
      const nregistros = modalBusquedaRef.getNrecords()

      if (nregistros > 0) {

        if (nregistros == 1) {
          await cargarReferencia(resp[0]);
        } else {
          modalBusquedaRef.show();
        }
      }
    }
  });

  $("#lote").focus(function () {
    $("#tmp_").val($(this).val())
  });

  $("#lote").focusout(function () {

    if (ConfGeneral.ManejaFechaLote == 'N') {
      return;
    }
    if ($("#id_detalle").val() > 0) {
      const lote_ = $.trim($(this).val()).toUpperCase()

      if (lote_.length == 0) {
        $(this).val(tmp_)
      }
      const tmp_ = $("#tmp_").val()
      const codigo_ = $("#codigo").val()

      if (lote_ != tmp_) {
        $("#items_ tbody tr").each(function () {
          const lote = $.trim($(this).find("td").eq(6).html()).toUpperCase()
          const codigo = $.trim($(this).find("td").eq(0).html()).toUpperCase()

          if (lote == lote_ && codigo_ == codigo) {
            Swal.fire("Ups", "El lote " + lote_ + " ya fue agregado ", "warning");
            $("#lote").val(tmp_)
            return;
          }
        });
      }
    }
  })


  var formDetalle = document.getElementById('FormDetalle');

  formDetalle.addEventListener('submit', async function (event) {

    try {

      event.preventDefault();

      //validar si concepto de retencion esta activo y se ha seleccionado un concepto, si no se cumple mostrar mensaje de error y retornar

      let concepto = $("#concepto_retencion").val();

      if ($("#concepto_retencion option").length > 1 && concepto === "") {
        Swal.fire("Error", "Debe seleccionar un concepto de retención", "error");
        return;
      }

      if (concepto !== "") {
        let valor = $("#valor_retencion").val();

        if (valor === '' || parseFloat(valor) === 0) {
          Swal.fire("Error", "Debe ingresar un valor de retención válido", "error");
          return;
        }
      }

      //validar que la fecha de vencimiento no sea menor a la fecha actual, si se cumple mostrar mensaje de error y retornar

      let fecha_vencimiento = $("#vmcto").val();
      let fecha_actual = new Date().toISOString().split('T')[0];
      if (fecha_vencimiento < fecha_actual) {
        Swal.fire("Error", "La fecha de vencimiento no puede ser menor a la fecha actual", "error");
        return;
      }

      if (!formDetalle.checkValidity()) {
        event.stopPropagation();
        formDetalle.classList.add('was-validated');
        dissminSwal();
        return;
      }

      formDetalle.classList.add('was-validated');

      let id_transaccion = $("#id_transaccion").val();
      let dataEncabezado = {
        link: '../shared/servicios/servicios.php',
        op: 'add_transacciones_tmp',
        id_tipo_documento: $("#tipo").val(),
        id_tercero: $("#id_tercero").val(),
        id_bodega: $("#bodegas").val(),
        notas: $("#notas").val(),
        documento: $("#documento").val().length > 0 ? $("#documento").val().toUpperCase() : $("#documento").val(),
        valor_total: unformatNum($("#total-total").val()),
        sw: 1,
        id_tercero_dir: $("#id_tercero_dir").val(),
        id_condicion: $("#condicion").val(),
        fecha: $("#fecha").val(),
        es_credito: 1
      }

      let dataDetalle = formatearArrayRequest($(this).serializeArray());

      // --- AJUSTE: Incluir el texto del badge en la descripción que va al backend ---
      const badgeTexto = $("#badge_variacion_costo").text().trim();
      if (badgeTexto && badgeTexto !== "Sin cambios") {
        // Si tu campo se llama 'descripcion' o 'descripcion_corta' en el formulario:
        if (dataDetalle.descripcion) {
          dataDetalle.descripcion += ` - ${badgeTexto}`;
        } else if (dataDetalle.descripcion_corta) {
          dataDetalle.descripcion_corta += ` - ${badgeTexto}`;
        }
      }
      // -----------------------------------------------------------------------------

      showLoadingWithTetrisLogo('Guardando información', 'ISA Software');

      if (isNaN(parseInt(id_transaccion)) || parseInt(id_transaccion) == 0) {
        //inserto  encabezado  
        resp = await enviarPeticion(dataEncabezado);

        if (resp.error) {
          dissminSwal();
          Swal.fire("Error", resp.message, "error");
          return;
        }
        $("#id_transaccion").val(resp.data.id)
        $("#numero").val(resp.data.numero)
      }

      dataDetalle.link = '../shared/servicios/servicios.php';

      //inserto detalle 
      if ($("#id_detalle").val() > 0) {
        dataDetalle.op = 'update_transacciones_detalle_tmp';
        dataDetalle.id = $("#id_detalle").val()
      } else {
        dataDetalle.op = 'add_transacciones_detalle_tmp';
      }

      let sw = true;
      let cantidad_agregada = dataDetalle.cantidad;
      let und_cargada = dataDetalle.und;
      let can_und_cargada = dataDetalle.can_und;
      let vencimiento = dataDetalle.vencimiento;
      dataDetalle.lote = dataDetalle.lote.length > 0 ? dataDetalle.lote.toUpperCase() : '';
      let lote = dataDetalle.lote;
      let opcion = 0;

      if ($("#id_detalle").val() == 0) {

        $("#items_ tbody tr").each(function () {

          const codigo = $.trim($(this).find("td").eq(0).html()).toUpperCase();


          if (ConfGeneral.ManejaFechaLote == 'S') {
            const lote = $.trim($(this).find("td").eq(6).html()).toUpperCase();

            if (codigo == $.trim($("#codigo").val().toUpperCase())
              && lote == $.trim($("#lote").val()).toUpperCase()
            ) {
              dataDetalle.id = $(this).find("td:last").html();
              cantidad_agregada = parseInt($(this).find("td").eq(7).html());
              und_cargada = $.trim($(this).find("td").eq(8).html()).toUpperCase();
              sw = false;

              if (und_cargada == $.trim($("#unidad option:selected").text().split("-")[0])) {
                TextPromt = "Ya se encuentra agregada " + (cantidad_agregada) + " " + und_cargada + " , que desea hacer? \n 1. Sumar al actual.  \n 2. Reemplazar.";
              } else {
                TextPromt = "Ya se encuentra agregada " + (cantidad_agregada) + " " + und_cargada + " , que desea hacer?  \n 2. Reemplazar.";
              }

              opcion = prompt(TextPromt);

              if (opcion == 1) {

                dataDetalle.op = 'update_transacciones_detalle_tmp'
                cantidad_agregada = cantidad_agregada + parseInt(dataDetalle.cantidad);
                und_cargada = $.trim($(this).find("td").eq(8).html()).toUpperCase();
                can_und_cargada = $(this).find("td").eq(9).html();
                dataDetalle.vmcto = $(this).find("td").eq(5).html();
                dataDetalle.lote = $(this).find("td").eq(6).html();
                sw = true;

              } else if (opcion == 2) {
                dataDetalle.op = 'update_transacciones_detalle_tmp'
                sw = true
                cantidad_agregada = dataDetalle.cantidad;
                und_cargada = dataDetalle.und;
                can_und_cargada = dataDetalle.can_und;
              }
            }
          } else {
            // no maneja fecha lote 
            if (codigo == $.trim($("#codigo").val()).toUpperCase()) {
              cantidad_agregada = parseInt($(this).find("td").eq(5).html());
              dataDetalle.id = $(this).find("td:last").html();
              und_cargada = $.trim($(this).find("td").eq(6).html()).toUpperCase();
              sw = false;

              if (und_cargada == $.trim($("#unidad option:selected").text().split("-")[0])) {
                TextPromt = "Ya se encuentra agregada " + (cantidad_agregada) + " " + und_cargada + " , que desea hacer? \n 1. Sumar al actual.  \n 2. Reemplazar.";
              } else {
                TextPromt = "Ya se encuentra agregada " + (cantidad_agregada) + " " + und_cargada + " , que desea hacer?  \n 2. Reemplazar.";
              }

              opcion = prompt(TextPromt);

              if (opcion == 1) { //sumar
                dataDetalle.op = 'update_transacciones_detalle_tmp'
                cantidad_agregada = cantidad_agregada + parseInt(dataDetalle.cantidad);
                und_cargada = $.trim($(this).find("td").eq(8).html()).toUpperCase();
                can_und_cargada = $(this).find("td").eq(7).html();
                sw = true
              } else if (opcion == 2) { //Reemplazar
                dataDetalle.op = 'update_transacciones_detalle_tmp'
                sw = true
                cantidad_agregada = dataDetalle.cantidad;
                und_cargada = dataDetalle.und;
                can_und_cargada = dataDetalle.can_und;
              }
            }
          }
        });

      } //

      if (!sw) {
        dissminSwal();
        return;
      }


      dataDetalle.id_referencia = $("#id_referencia").val()
      dataDetalle.id_transaccion = $("#id_transaccion").val();
      dataDetalle.und = und_cargada === undefined ? $.trim($("#unidad option:selected").text().split("-")[0]) : und_cargada;
      dataDetalle.can_und = can_und_cargada === undefined ? $("#unidad option:selected").text().split("-")[1] : can_und_cargada;
      dataDetalle.cantidad = cantidad_agregada;
      dataDetalle.valor_unitario = parseFloat(($("#costo").val().replace(/,/g, ''))) / dataDetalle.can_und;
      dataDetalle.pcj_descuento = $("#pcj_descuento").val();
      dataDetalle.costo_unitario = parseFloat(($("#costo").val()).replace(/,/g, '')) / dataDetalle.can_und;
      dataDetalle.cantidad = dataDetalle.cantidad * dataDetalle.can_und;
      dataDetalle.id_bodega = $("#bodegas").val()
      dataDetalle.ibua = unformatNum(dataDetalle.ibua);
      dataDetalle.sw = 1;

      respDet = await enviarPeticion(dataDetalle);

      if (respDet.error) {
        Swal.fire("Error", respDet.message, "error");
        return;
      }

      const id_ = $("#id_detalle").val() > 0 ? $("#id_detalle").val() : respDet.data.id

      if ($("#id_referencia").val() > 0 && $("#id_transaccion").val() > 0) {
        try {
          const respPrecio = await guardarPrecioTemporalRecepcion(false);
        } catch (error) {
          console.error("Error en la actualización temporal de precios:", error);
        }
      }


      if (opcion != 0) {
        $("#id_detalle").val(respDet.data.id)
      }
      await addReferencia(id_, dataDetalle);

      await recalcularEncabezado();
      await limpiarReferencia();
      $("#id_detalle").val(0)
      $("#filtro").focus();
      dissminSwal();

    } catch (e) {
      console.error(e)
      dissminSwal();
    }
  });

  $("#txtBusquedaTercero").focus()

  $("#btnBuscarLotes").click(async function () {

    const id_referencia = $("#id_referencia").val();

    if (!id_referencia) {
      Swal.fire(
        "Atención",
        "Debe seleccionar una referencia.",
        "warning"
      );
      return;
    }

    let resp = await enviarPeticion({
      link: '../models/IngresosMercancias.php',
      op: 'cargar-lotes',
      id_referencia: id_referencia
    });

    let html = '';
    if (resp.length == 0) {
      html = `<tr><td colspan="3" class="text-center"><i class="fa fa-exclamation-triangle text-warning"></i> No se encontraron lotes para este producto</td></tr>`;
    } else {

      resp.forEach(item => {

        html += `
              <tr class="fila-lote"
                  style="cursor:pointer"
                  data-id="${item.id}"
                  data-lote="${item.lote}"
                  data-vencimiento="${item.vencimiento}">
                  <td>${item.lote}</td>
                  <td>${item.vencimiento}</td>
                  <td>
                      <i class="fa fa-check text-success"></i>
                  </td>
              </tr>
              `;
      });
    }

    $("#tblLotesBody").html(html);

    const modal = new bootstrap.Modal(
      document.getElementById('modalLotes')
    );

    modal.show();
  });
  $(document).on('click', '.fila-lote', function () {
    $("#lote").val($(this).data('lote'));
    $("#vmcto").val($(this).data('vencimiento'));

    bootstrap.Modal
      .getInstance(document.getElementById('modalLotes'))
      .hide();
  });
  $("#btnMostrarOcultar").on("click", function () {

    const boton = $(this);
    const icono = boton.find("i");
    const texto = boton.find(".texto-boton");

    $("#fila-encabezado").slideToggle(200, function () {

      if ($(this).is(":visible")) {

        icono.removeClass("fa-eye")
          .addClass("fa-eye-slash");

        texto.text("Ocultar totales");

      } else {

        icono.removeClass("fa-eye-slash")
          .addClass("fa-eye");

        texto.text("Mostrar totales");

      }

    });

  });

  //Modal para crear referencia
  $("#btnCrearReferencia").click(async function () {
    showLoadingWithTetrisLogo('Cargando información', 'ISA Software');
    await listarPerfiles();
    await marcas();
    await ultimoCodigo();
    const modal = new bootstrap.Modal(document.getElementById('modalCrearReferencia'));
    modal.show();
    dissminSwal();
  });

  $("#btnGuardarReferencia").on("click", async function () {
    try {

      const formulario = document.querySelector("#frmReferencia"); // id del formulario

      // Valida todos los campos required
      if (!formulario.checkValidity()) {
        formulario.reportValidity();
        return;
      }
      showLoadingWithTetrisLogo('Guardando referencia', 'ISA Software')
      const dataIva = await getIva();

      let costoUnitario = parseFloat($("#txtCostoUnd").val().replace(/,/g, ''));
      let precioUnitario = parseFloat($("#txtCostoUnd").val().replace(/,/g, ''));

      const data = {
        link: '../models/Referencias.php',
        op: 'g_referencia',
        activo: 1,
        tipo: $("#slcTipoReferencia").val(),
        codigo: $("#txtCodigo").val().trim(),
        descripcion: $("#txtDescripcion").val().trim(),
        descripcion_larga: $("#txtDescripcion").val().trim(),
        costo_unitario: costoUnitario,
        precio_unitario: precioUnitario,
        id_marca: $("#slcMarcas").val(),
        perfil_contable: $("#slcPerfilContable").val(),
        maneja_lote: $("#slcManejaLote").val(),
        id_iva: dataIva.id,
        und_1: 'UND',
        cant_1: 1,
        und_compra: 1,
        listas: [],
        ean: []
      };

      const resp = await enviarPeticion(data);

      if (!resp.error) {
        initToast('bg-success', 'Correcto!', resp.message, 'success');
        limpiarFormulario('frmReferencia');
        await ultimoCodigo();

      } else {
        initToast('bg-danger', 'Error!', resp.message, 'danger');
      }
      dissminSwal();

    } catch (e) {
      console.error(e);
      dissminSwal();
    }
  });

  const elements = document.querySelectorAll('.moneda');
  elements.forEach((element) => {
    new Cleave(element, {
      numeral: true,
      numeralThousandsGroupStyle: 'thousand',
      numeralDecimalMark: '.',
      numeralDecimalScale: 2,
    });
  });

  // =========================
  // EVENTO BOTÓN VIDEO YOUTUBE
  // =========================

  $("#btn-video-tutorial").click(function () {
    const videoURL = "https://youtu.be/3pBSaD1X-Ok";
    const videoID = videoURL.split('/').pop().split('?')[0];
    const embedURL = `https://www.youtube.com/embed/${videoID}`;

    $("#videoFrameTutorial").attr("src", embedURL);

    const modal = new bootstrap.Modal(document.getElementById('modalVideoTutorial'));
    modal.show();
  });

  // Detener video al cerrar el modal
  $("#modalVideoTutorial").on('hidden.bs.modal', function () {
    $("#videoFrameTutorial").attr("src", "");
  });
  dissminSwal();
})