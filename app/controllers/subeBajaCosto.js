const link = '../models/subeBajaCosto.php';
let productosAjuste = [];
let modalBusquedaRef = null;
let terceroEmpresa = {
  id_tercero: 0,
  id_tercero_dir: 0,
  id_condicion: 0
};

const toNumber = (value) => {
  if (value === null || value === undefined) return 0;
  const clean = String(value).replace(/\$/g, '').replace(/,/g, '').trim();
  const n = parseFloat(clean);
  return isNaN(n) ? 0 : n;
};

const money = (n) => formatNum(toNumber(n), '$');

const getPeriodoActual = () => {
  const now = new Date();
  return {
    mes: now.getMonth() + 1,
    ano: now.getFullYear()
  };
};

const cargarBodegaActual = async () => {
  try {
    const idBodega = $('#bodega').val().trim();
    if (!idBodega) return;

    const resp = await bodegasUsuarios();
    const item = (resp || []).find((b) => String(b.id) === String(idBodega));

    if (item) {
      $('#bodega_actual').val(`${item.bodega} - ${item.descripcion}`);
    } else {
      $('#bodega_actual').val(`Bodega ${idBodega}`);
    }
  } catch (e) {
    console.error('No se pudo cargar la bodega actual', e);
  }
};

const cargarDirecciones = async (idTercero, idPredeterminado = 0) => {
  try {
    const resp = await enviarPeticion({
      link: '../models/Terceros.php',
      op: 's_terceros_direcciones',
      id_tercero: idTercero
    });

    const sel = $('#id_tercero_dir');
    sel.empty();

    (resp.data || []).forEach((d) => {
      sel.append(`<option value="${d.id}">${d.id} - ${d.direccion}</option>`);
    });

    if (idPredeterminado && sel.find(`option[value="${idPredeterminado}"]`).length) {
      sel.val(String(idPredeterminado));
    }

    if (!sel.val() && (resp.data || []).length > 0) {
      sel.val(String(resp.data[0].id));
    }
  } catch (e) {
    console.error('No se pudieron cargar direcciones del tercero', e);
  }
};

const cargarTercero = async (item) => {
  terceroEmpresa = {
    id_tercero: item.id || 0,
    id_tercero_dir: item.id_direccion || 0,
    id_condicion: item.id_condicion_pago_prov || 0
  };

  $('#id_tercero').val(terceroEmpresa.id_tercero);
  $('#id_tercero_dir').val(terceroEmpresa.id_tercero_dir);
  $('#nit').val(item.nit || '');
  $('#tratamiento').val(item.tratamiento || '');
  $('#razon_comercial').val(item.razon_comercial || '');

  await cargarCondicionesPago();

  if (terceroEmpresa.id_condicion) {
    $('#condicion').val(terceroEmpresa.id_condicion);
  }
};

const cargarCondicionesPago = async () => {
  await condicionesPago('P', 'condicion');
};

const recalcRow = (index) => {
  const item = productosAjuste[index];
  if (!item) return;

  const nuevoPromedio = Math.max(0, toNumber(item.nuevo_promedio));
  const delta = nuevoPromedio - toNumber(item.promedio_actual);

  item.nuevo_promedio = nuevoPromedio;
  item.delta_unitario = delta;
  item.ajuste_total = delta * toNumber(item.stock);
};

const recalcularTodo = () => {
  productosAjuste.forEach((_, idx) => recalcRow(idx));
};

const actualizarImpactoTotal = () => {
  const total = productosAjuste.reduce((acc, x) => acc + toNumber(x.ajuste_total), 0);
  $('#lbl-impacto-total').text(money(total));
};

const actualizarFilaVisual = (index) => {
  const row = $('#tb-productos tbody tr').eq(index);
  if (!row.length || !productosAjuste[index]) return;

  row.find('.td-delta').text(money(productosAjuste[index].delta_unitario));
  row.find('.td-ajuste').text(money(productosAjuste[index].ajuste_total));
  actualizarImpactoTotal();
};

const renderTablaProductos = () => {
  let rows = '';

  productosAjuste.forEach((p, idx) => {
    rows += `
      <tr>
        <td>${p.codigo}</td>
        <td>${p.descripcion}</td>
        <td class="text-end">${formatNum(p.stock)}</td>
        <td class="text-end">${money(p.promedio_actual)}</td>
        <td>
          <input type="number" min="0" step="0.01" class="form-control form-control-sm text-end inp-nuevo-promedio" data-index="${idx}" value="${toNumber(p.nuevo_promedio).toFixed(2)}">
        </td>
        <td class="text-end td-delta">${money(p.delta_unitario)}</td>
        <td class="text-end td-ajuste">${money(p.ajuste_total)}</td>
        <td class="text-center">
          <button type="button" class="btn btn-sm btn-outline-danger btn-quitar" data-index="${idx}">
            <i class="fa-solid fa-trash"></i>
          </button>
        </td>
      </tr>
    `;
  });

  if (rows === '') {
    rows = '<tr><td colspan="8" class="text-center text-muted">No hay productos agregados.</td></tr>';
  }

  $('#tb-productos tbody').html(rows);

  actualizarImpactoTotal();
};

const agregarProductoDesdeCodigo = async (codigoRef) => {
  const codigo = $.trim(codigoRef || '').toUpperCase();
  const id_bodega = ($('#bodega').val() || '').trim();
  const { mes, ano } = getPeriodoActual();
  const sw = ($('#sw').val() || '').trim();
  const id_tipo_documento = ($('#id_tipo_documento').val() || '').trim();

  if (!codigo) {
    Swal.fire('Atencion', 'Digite un codigo de referencia.', 'warning');
    return;
  }

  if (!sw) {
    Swal.fire('Atencion', 'Debe seleccionar un SW (17 o 18).', 'warning');
    return;
  }

  if (!id_tipo_documento) {
    Swal.fire('Atencion', 'Debe seleccionar un tipo de documento.', 'warning');
    return;
  }

  if (!id_bodega || !mes || !ano) {
    Swal.fire('Atencion', 'Debe indicar bodega y periodo.', 'warning');
    return;
  }

  if (productosAjuste.some((x) => x.codigo === codigo)) {
    Swal.fire('Atencion', 'La referencia ya esta agregada.', 'warning');
    return;
  }

  try {
    showLoadingWithTetrisLogo('Buscando referencia', 'ISA Software')
    const resp = await enviarPeticion({
      link,
      op: 'buscar-referencia',
      codigo,
      id_bodega,
      mes,
      ano
    });

    dissminSwal();

    const row = (resp.data || [])[0];
    if (!row) {
      Swal.fire('Informacion', 'No se encontro la referencia.', 'info');
      return;
    }

    if (toNumber(row.stock) <= 0) {
      Swal.fire('Atencion', 'La referencia no tiene stock para ajustar costo promedio.', 'warning');
      return;
    }

    const item = {
      id_referencia: row.id,
      codigo: row.codigo,
      descripcion: row.descripcion,
      stock: toNumber(row.stock),
      promedio_actual: toNumber(row.promedio),
      nuevo_promedio: toNumber(row.promedio),
      delta_unitario: 0,
      ajuste_total: 0
    };

    productosAjuste.push(item);
    renderTablaProductos();
    $('#filtro').val('').focus();
  } catch (e) {
    dissminSwal();
    console.error(e);
    Swal.fire('Error', 'Error consultando referencia.', 'error');
  }
};

const validarSegunSW = () => {
  const sw = parseInt($('#sw').val() || '0', 10);

  const invalidos = productosAjuste.filter((p) => {
    const delta = toNumber(p.delta_unitario);
    if (sw === 17) return delta <= 0;
    if (sw === 18) return delta >= 0;
    return true;
  });

  return {
    ok: invalidos.length === 0,
    invalidos
  };
};

const cargarSW = async () => {
  try {
    await enviarPeticion({ link, op: 'crear-sw' });

    const resp = await enviarPeticion({ link, op: 'listar-sw' });
    const swSel = $('#sw');
    swSel.empty().append('<option value="">SELECCIONE SW</option>');

    (resp.data || []).forEach((it) => {
      swSel.append(`<option value="${it.sw}">${it.sw} - ${it.descripcion}</option>`);
    });
  } catch (e) {
    console.error(e);
    Swal.fire('Error', 'No fue posible cargar SW 17/18.', 'error');
  }
};

const cargarTerceroEmpresa = async () => {
  try {
    const tercero = await serviceTerceroEmpresa();
    if (!tercero || tercero.length === 0) return;

    const resp = await enviarPeticion({
      link: '../models/Terceros.php',
      filtro: $.trim(tercero[0].nit),
      tipo_tercero: '',
      estado: 'A',
      op: 's_tercero'
    });

    const data = (resp.data || [])[0];
    if (!data) return;

    await cargarTercero(data);
    await cargarDirecciones(data.id, data.id_direccion);
  } catch (e) {
    console.error('No se pudo cargar tercero empresa', e);
  }
};

const cargarTiposDocumento = async () => {
  const sw = $('#sw').val();
  const bodega = $('#bodega').val().trim();
  const tipoSel = $('#id_tipo_documento');

  tipoSel.empty().append('<option value="">SELECCIONE DOCUMENTO</option>');

  if (!sw || !bodega) return;

  try {
    const respTipos = await enviarPeticion({
      link: '../models/tiposDocumentos.php',
      op: 'listar_sw',
      sw,
      bodega
    });

    (respTipos.data || []).forEach((d) => {
      tipoSel.append(`<option value="${d.id}">${d.tipo} - ${d.descripcion}</option>`);
    });
  } catch (e) {
    console.error(e);
    Swal.fire('Error', 'No se pudieron cargar documentos para el SW seleccionado.', 'error');
  }
};

const agregarProducto = async () => {
  await agregarProductoDesdeCodigo($('#filtro').val());
};

const renderMisAjustes = (rows) => {
  const data = (rows || []).map((r) => ({
    ...r,
    producto: `${r.codigo || ''} - ${r.descripcion || ''}`,
    stock: toNumber(r.stock),
    promedio_anterior: toNumber(r.promedio_anterior),
    nuevo_promedio: toNumber(r.nuevo_promedio),
    delta: toNumber(r.delta),
    ajuste_total: toNumber(r.ajuste_total)
  }));

  createDataTable(
    'tb-misajustes',
    data,
    [
      'fecha_hora',
      'numero',
      'sw',
      'razon_comercial',
      'producto',
      'stock',
      'promedio_anterior',
      'nuevo_promedio',
      'delta',
      'ajuste_total'
    ],
    [
      'Fecha/Hora',
      'Documento',
      'SW',
      'Tercero',
      'Producto',
      'Stock',
      'Promedio Anterior',
      'Nuevo Promedio',
      'Delta',
      'Ajuste Total'
    ],
    null,
    null,
    true,
    '<button class="other-btn btn btn-sm btn-outline-primary" title="Imprimir"><i class="fa fa-print"></i></button>',
    (item) => imprimirAjuste(item.id_transaccion, item.sw),
    false,
    ['promedio_anterior', 'nuevo_promedio', 'delta', 'ajuste_total'],
    'COP',
    [
      { data: 'stock', className: 'text-end' },
      { data: 'sw', className: 'text-center' }
    ]
  );
};

const imprimirAjuste = async (idTransaccion, sw) => {
  const id = parseInt(idTransaccion || '0', 10);
  const swDoc = parseInt(sw || '0', 10);
  if (!id) {
    Swal.fire('Atencion', 'No se encontro el documento a imprimir.', 'warning');
    return;
  }

  if (![17, 18].includes(swDoc)) {
    Swal.fire('Atencion', 'El documento no corresponde a SW 17/18.', 'warning');
    return;
  }

  const tituloDinamico = swDoc === 17 ? 'Impresion de sube costo promedio' : 'Impresion de baja costo promedio';
  await ImprimirDocumento(swDoc, id, tituloDinamico, false);
};

const cargarMisAjustes = async () => {
  const fechaInicial = $('#fechaInicial').val();
  const fechaFinal = $('#fechaFinal').val();

  if (!fechaInicial || !fechaFinal) {
    Swal.fire('Atencion', 'Debe seleccionar ambas fechas.', 'warning');
    return;
  }

  if (fechaInicial > fechaFinal) {
    Swal.fire('Atencion', 'La fecha inicial no puede ser mayor que la fecha final.', 'warning');
    return;
  }

  try {
    showLoadingWithTetrisLogo('Buscando ajustes', 'ISA Software')

    const resp = await enviarPeticion({
      link,
      op: 'listar-mis-ajustes',
      fecha_inicial: fechaInicial,
      fecha_final: fechaFinal
    });

    dissminSwal();

    if (resp.error) {
      Swal.fire('Error', resp.message || 'Error al consultar ajustes.', 'error');
      return;
    }

    renderMisAjustes(resp.data || []);
  } catch (e) {
    dissminSwal();
    console.error(e);
    Swal.fire('Error', 'Error consultando ajustes.', 'error');
  }
};

const guardarDocumentoAjuste = async () => {
  const sw = parseInt($('#sw').val() || '0', 10);
  const id_tipo_documento = $('#id_tipo_documento').val().trim();
  const notas = $('#notas').val().trim();
  const id_bodega = $('#bodega').val().trim();
  const { mes, ano } = getPeriodoActual();

  if (![17, 18].includes(sw)) {
    Swal.fire('Atencion', 'Seleccione SW 17 o 18.', 'warning');
    return;
  }

  if (!id_tipo_documento) {
    Swal.fire('Atencion', 'Seleccione tipo de documento.', 'warning');
    return;
  }

  const id_tercero = parseInt($('#id_tercero').val() || '0', 10);
  const id_tercero_dir = parseInt($('#id_tercero_dir').val() || '0', 10);
  const id_condicion = parseInt($('#condicion').val() || '0', 10);

  if (!id_condicion || id_condicion == '') {
    Swal.fire('Atencion', 'No se pudo identificar la condición de pago, verifique sus datos e intente nuevamente.', 'warning');
    return;
  }

  if (!id_tercero || !id_tercero_dir) {
    Swal.fire('Atencion', 'No se pudo identificar el tercero de la empresa para crear el documento, verifique sus datos e intente nuevamente.', 'warning');
    return;
  }

  if (productosAjuste.length === 0) {
    Swal.fire('Atencion', 'No hay productos para guardar.', 'warning');
    return;
  }

  recalcularTodo();

  const valid = validarSegunSW();
  if (!valid.ok) {
    const msg = sw === 17
      ? 'Para SW 17 todos los nuevos promedios deben ser mayores al promedio actual.'
      : 'Para SW 18 todos los nuevos promedios deben ser menores al promedio actual.';
    Swal.fire('Atencion', msg, 'warning');
    return;
  }

  const itemsAjuste = productosAjuste.filter((x) => Math.abs(toNumber(x.ajuste_total)) > 0);
  if (itemsAjuste.length === 0) {
    Swal.fire('Atencion', 'No hay ajustes con impacto para guardar.', 'warning');
    return;
  }

  const totalDocumento = itemsAjuste.reduce((acc, x) => acc + Math.abs(toNumber(x.ajuste_total)), 0);

  const confirm = await Swal.fire({
    title: 'Confirmar documento',
    html: `<p>Se generara un documento con SW <b>${sw}</b></p><p>Total: <b>${money(totalDocumento)}</b></p>`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Si, guardar',
    cancelButtonText: 'Cancelar'
  });

  if (!confirm.isConfirmed) return;

  try {
    showLoadingWithTetrisLogo('Guardando documento', 'ISA Software')

    const respTx = await enviarPeticion({
      link: '../shared/servicios/servicios.php',
      op: 'add_transacciones_tmp',
      id_tipo_documento,
      id_tercero,
      id_bodega,
      notas,
      valor_total: totalDocumento,
      iva: 0,
      descuento: 0,
      sw,
      id_tercero_dir,
      id_condicion,
      vencimiento: '1900-01-01',
      retencion_fuente: 0,
      retencion_iva: 0,
      retencion_ica: 0,
      dias: 0,
      es_credito: 0
    });
    console.log('Respuesta creación transacción:', respTx);

    if (respTx.error || !respTx.data || !respTx.data.id) {
      dissminSwal();
      Swal.fire('Error', 'No se pudo crear la transaccion.', 'error');
      return;
    }


    const id_transaccion = respTx.data.id;

    for (const p of itemsAjuste) {
      const valorUnitarioDoc = Math.abs(toNumber(p.delta_unitario));

      await enviarPeticion({
        link: '../shared/servicios/servicios.php',
        op: 'add_transacciones_detalle_tmp',
        id_transaccion,
        id_referencia: p.id_referencia,
        cantidad: toNumber(p.stock),
        valor_unitario: valorUnitarioDoc,
        costo_unitario: valorUnitarioDoc,
        pcj_iva: 0,
        pcj_descuento: 0,
        und: 'UND',
        can_und: toNumber(p.stock),
        sw,
        id_bodega,
        id_pedido: 0,
        id: 0
      });
    }


    const respDoc = await enviarPeticion({
      link: '../shared/servicios/servicios.php',
      op: 'volcar_documento',
      id_transaccion,
      updateVendedor: 'N',
      vendedor: ''
    });

    if (!respDoc.ok) {
      await enviarPeticion({
        link: '../shared/servicios/servicios.php',
        op: 'delete_transaccion',
        id_transaccion
      });

      dissminSwal();
      Swal.fire('Error', respDoc.message || 'No se pudo volcar el documento.', 'error');
      return;
    }

    const respAjuste = await enviarPeticion({
      link,
      op: 'ajustar-costo-promedio',
      sw,
      id_bodega,
      mes,
      ano,
      items: JSON.stringify(itemsAjuste)
    });

    dissminSwal();

    if (respAjuste.error) {
      Swal.fire('Error', respAjuste.message || 'Documento creado, pero fallo el ajuste de costo.', 'error');
      return;
    }

    Swal.fire('Correcto', `Documento #${respDoc.id} generado y costos ajustados.`, 'success');

    productosAjuste = [];
    renderTablaProductos();
    $('#notas').val('');
    await cargarTiposDocumento();
  } catch (e) {
    dissminSwal();
    console.error(e);
    Swal.fire('Error', 'Error interno al guardar el documento de ajuste.', 'error');
  }
};

$(async function () {
  renderTablaProductos();
  await cargarBodegaActual();
  await cargarCondicionesPago();
  await cargarTerceroEmpresa();
  await cargarSW();

  modalBusquedaRef = new ModalBusquedaProducto((rowData) => {
    if (rowData && rowData.codigo) {
      agregarProductoDesdeCodigo(rowData.codigo);
    }
  }, { check_stock: true, cod_barras: false });

  $('#filtro').focus();

  // Autocomplete para busqueda de terceros
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
        console.error("Error en la solicitud de terceros:", error);
        response([]);
      }
    },
    minLength: 3,
    select: async function (event, ui) {
      const item = ui.item;
      $('#id_tercero').val(item.id);
      await cargarTercero(item);
      await cargarDirecciones(item.id, item.id_direccion);
    }
  });

  $('#sw').on('change', async function () {
    productosAjuste = [];
    renderTablaProductos();
    await cargarTiposDocumento();
  });

  $('#btnBusquedaAvanzada').on('click', function () {
    if (!modalBusquedaRef) return;
    modalBusquedaRef.show();
    $('#busqueda-descripcion').val($('#filtro').val());
    setTimeout(() => {
      $('#busqueda-descripcion').focus();
    }, 200);
  });

  let buscando = false;
  $('#filtro').on('keydown', async function (e) {
    if (e.key !== 'Enter') return;
    e.preventDefault();
    if (!modalBusquedaRef || buscando) return;

    const filtro = $.trim(this.value);
    if (!filtro) return;

    try {
      buscando = true;
      const resp = await modalBusquedaRef.getRecords({
        data: filtro,
        cod_barras: false,
        bodega: $('#bodega').val().trim()
      });

      const nregistros = modalBusquedaRef.getNrecords();
      if (!nregistros) return;

      if (nregistros === 1 && resp && resp[0] && resp[0].codigo) {
        await agregarProductoDesdeCodigo(resp[0].codigo);
      } else {
        modalBusquedaRef.show();
        $('#busqueda-descripcion').val(filtro);
      }
    } catch (error) {
      console.error('Error en busqueda de referencias:', error);
    } finally {
      buscando = false;
    }
  });

  $('#tb-productos').on('input', '.inp-nuevo-promedio', function () {
    const index = $(this).data('index');
    productosAjuste[index].nuevo_promedio = toNumber($(this).val());
    recalcRow(index);
    actualizarFilaVisual(index);
  });

  $('#tb-productos').on('blur', '.inp-nuevo-promedio', function () {
    const index = $(this).data('index');
    const valor = toNumber($(this).val());
    $(this).val(valor.toFixed(2));
    productosAjuste[index].nuevo_promedio = valor;
    recalcRow(index);
    actualizarFilaVisual(index);
  });

  $('#tb-productos').on('click', '.btn-quitar', function () {
    const index = $(this).data('index');
    productosAjuste.splice(index, 1);
    renderTablaProductos();
  });

  $('#btn-guardar').on('click', guardarDocumentoAjuste);

  // Buscar ajustes por rango de fechas
  $('#btnBuscarAjustes').on('click', cargarMisAjustes);

  // Establecer fechas por defecto (primer día del mes hasta hoy)
  const today = new Date();
  const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
  $('#fechaInicial').val(firstDay.toISOString().split('T')[0]);
  $('#fechaFinal').val(today.toISOString().split('T')[0]);

  dissminSwal();
});
