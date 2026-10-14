const link = '../models/notasCreditoDebito.php';


/**
 * Busca documentos con los filtros especificados
 */
const buscarDocumentos = async () => {

  const idTercero = $("#id_tercero").val().trim();

  if (!idTercero) {
    Swal.fire("Atención", "Debe seleccionar un tercero para buscar documentos.", "warning");
    return;
  }

  const fecha_ini = $("#filtro_fecha_ini").val().trim();
  const fecha_fin = $("#filtro_fecha_fin").val().trim();
  const tipo_doc = $("#filtro_tipo_doc").val().trim();
  const numero = $("#filtro_numero").val().trim();

  try {

    showLoadingWithTetrisLogo('Buscando documentos', 'ISA Software');

    const resp = await enviarPeticion({
      link: link,
      op: 'buscar-documentos',
      id_tercero: idTercero,
      fecha_ini: fecha_ini,
      fecha_fin: fecha_fin,
      tipo_doc: tipo_doc,
      numero: numero
    });

    dissminSwal();

    if (!resp.data || resp.data.length === 0) {
      $("#contenedor-tabla-documentos").html(
        ShowAlert('No se encontraron documentos', 'info')
      );
      return;
    }

    const campos = [
      "id_transaccion",
      "numero",
      "tipo_documento",
      "fecha_hora",
      "valor_total",
      "valor_aplicado",
      "valor_pendiente"
    ];

    const encabezados = [
      "Id",
      "Número",
      "Tipo",
      "Fecha",
      "Valor Total",
      "Valor Aplicado",
      "Pendiente"
    ];

    $("#contenedor-tabla-documentos").html(`
      <table id="tabla-documentos-busqueda" class="table table-sm table-bordered table-hover w-100">
        <thead class="table-primary"></thead> 
        <tbody></tbody>
      </table>
    `);

    createDataTable(
      'tabla-documentos-busqueda',
      resp.data,
      campos,
      encabezados,
      '',
      '',
      true, //activar otherButton
      '<button class="other-btn btn btn-sm btn-outline-primary" title="Cargar" ><i class="fa-regular fa-circle-down"></i></button>', // icono del botÃ³n extra
      obtenerDetalles,
      true,
      ['valor_total', 'valor_aplicado', 'valor_pendiente']
    );

  } catch (e) {

    console.error("Error en búsqueda de documentos:", e);
    dissminSwal();

    Swal.fire(
      'Error!',
      'Un error ha ocurrido al buscar los documentos.',
      'error'
    );
  }
};

const obtenerDetalles = async (item) => {
  try {
    showLoadingWithTetrisLogo('Cargando detalles', 'ISA Software');
    const resp = await enviarPeticion({
      link: link,
      op: 'obtener-detalles',
      id_transaccion: item.id_transaccion
    });

    console.log("Respuesta detalles transacción:", resp);

    dissminSwal();

    if (!resp.data || resp.data.length === 0) {
      Swal.fire('Información', 'No se encontraron detalles para esta transacción.', 'info');
      return;
    }
    window.detallesTransaccion = resp.data;
    let filas = '';
    resp.data.forEach((row, index) => {
      const costoUnit = formatNum(row.costo_unitario, '$');
      const costoTotal = formatNum(row.costo_total, '$');
      const iva = parseFloat(row.pcj_iva || 0).toFixed(2) + '%';
      const desc = parseFloat(row.pcj_descuento || 0).toFixed(2) + '%';
      const costoNeto = (row.costo_total || 0) - (row.descuento_aplicado || 0);
      filas += `
      <tr>
        <td class="text-center">
          <input type="checkbox"
            class="chk-detalle"
            data-index="${index}">
        </td>
        <td>${row.codigo}</td>
        <td>${row.descripcion}</td>
        <td class="text-end">${costoUnit}</td>
        <td class="text-center">${iva}</td>
        <td class="text-center">${desc}</td>
        <td class="text-center">${row.cantidad}</td>
        <td>${row.und}</td>
        <td class="text-end">${costoTotal}</td>
        <td class="text-end">${formatNum(costoNeto, '$')}</td>
        <td>
          <input type="text"
            class="form-control form-control-sm text-end nuevo-costo"
            data-index="${index}"
            value="${row.costo_unitario}" readonly>
        </td>
      </tr>
      `;
    });

    $("#contenedor-tabla-detalles").html(`
      <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover">
          <thead class="table-primary">
            <tr>
              <th style="width:40px;">
                <input type="checkbox" id="chk-select-all-table">
              </th>
              <th>Código</th>
              <th>Descripción</th>
              <th class="text-end">Costo Unit.</th>
              <th class="text-center">IVA</th>
              <th class="text-center">Desc.</th>
              <th class="text-center">Cantidad</th>
              <th>Unidad</th>
              <th class="text-end">Costo Total</th>
              <th class="text-end">Costo Neto</th>
              <th style="width:120px;">Nuevo Costo</th>
            </tr>
          </thead>
          <tbody>
            ${filas}
          </tbody>
        </table>
      </div>
    `);

    // seleccionar todos
    $("#chk-select-all-table").on("change", function () {
      const checked = $(this).prop("checked");
      $(".chk-detalle").prop("checked", checked);
    });

    actualizarTotalDescuentoAplicado();

    const modalDetalles = new bootstrap.Modal(
      document.getElementById('modalDetalleTransaccion')
    );
    modalDetalles.show();
  } catch (e) {
    console.error("Error al obtener detalles:", e);
    dissminSwal();
    Swal.fire(
      'Error!',
      'Un error ha ocurrido al obtener los detalles de la transacción.',
      'error'
    );
  }
};

const actualizarTotalDescuentoAplicado = () => {
  let totalAplicado = 0;

  if (!Array.isArray(window.detallesTransaccion) || window.detallesTransaccion.length === 0) {
    $("#total-descuento-aplicado").text(formatNum(0, '$'));
    return;
  }

  window.detallesTransaccion.forEach((item, index) => {
    const costoOriginal = parseFloat(item.costo_unitario) || 0;
    const cantidad = parseFloat(item.cantidad) || 0;
    const nuevoCosto = parseNumero($(`.nuevo-costo[data-index="${index}"]`).val());
    const nuevoCostoSeguro = isNaN(nuevoCosto) ? costoOriginal : nuevoCosto;
    const descuentoItem = Math.max((costoOriginal - nuevoCostoSeguro) * cantidad, 0);
    totalAplicado += descuentoItem;
  });

  $("#total-descuento-aplicado").text(formatNum(totalAplicado, '$'));
};

const actualizarSombreadoFilasAplicadas = () => {
  if (!Array.isArray(window.detallesTransaccion) || window.detallesTransaccion.length === 0) {
    return;
  }

  window.detallesTransaccion.forEach((item, index) => {
    const costoOriginal = parseFloat(item.costo_unitario) || 0;
    const nuevoCosto = parseNumero($(`.nuevo-costo[data-index="${index}"]`).val());
    const nuevoCostoSeguro = isNaN(nuevoCosto) ? costoOriginal : nuevoCosto;
    const tieneDescuento = nuevoCostoSeguro < costoOriginal;

    $(`.nuevo-costo[data-index="${index}"]`)
      .closest("tr")
      .toggleClass("fila-descuento-aplicado", tieneDescuento);
  });
};

const limpiarDescuentosAplicados = () => {
  if (!Array.isArray(window.detallesTransaccion) || window.detallesTransaccion.length === 0) {
    $("#total-descuento-aplicado").text(formatNum(0, '$'));
    return;
  }

  window.detallesTransaccion.forEach((item, index) => {
    const costoOriginal = parseFloat(item.costo_unitario) || 0;
    $(`.nuevo-costo[data-index="${index}"]`).val(costoOriginal.toFixed(2));
  });

  $("#descuento_porcentaje").val('');
  $("#descuento_valor").val('');
  $("#descuento_porcentaje").prop("disabled", false);
  $("#descuento_valor").prop("disabled", false);

  actualizarTotalDescuentoAplicado();
  actualizarSombreadoFilasAplicadas();
};

const obtenerDescuentosAplicados = () => {
  const productosSeleccionados = [];
  let subtotal = 0;

  if (!Array.isArray(window.detallesTransaccion) || window.detallesTransaccion.length === 0) {
    return { productosSeleccionados, subtotal };
    actualizarSombreadoFilasAplicadas();
  }

  window.detallesTransaccion.forEach((item, index) => {
    const costoOriginal = parseFloat(item.costo_unitario) || 0;
    const cantidad = parseFloat(item.cantidad) || 0;
    const nuevoCosto = parseNumero($(`.nuevo-costo[data-index="${index}"]`).val());
    const nuevoCostoSeguro = isNaN(nuevoCosto) ? costoOriginal : nuevoCosto;
    const totalDescuento = (costoOriginal - nuevoCostoSeguro) * cantidad;

    if (totalDescuento > 0) {
      subtotal += totalDescuento;
      productosSeleccionados.push({
        id_referencia: item.id_referencia,
        codigo: item.codigo,
        descripcion: item.descripcion,
        pcj_iva: item.pcj_iva,
        pcj_descuento: item.pcj_descuento,
        unidad: item.und,
        cantidad: cantidad,
        costo_original: costoOriginal,
        nuevo_costo: nuevoCostoSeguro,
        total_descuento: totalDescuento
      });
    }
  });

  return { productosSeleccionados, subtotal };
};

/**
 * Aplica descuento a los productos seleccionados
 */
const aplicarDescuento = () => {

  const porcentaje = parseFloat($("#descuento_porcentaje").val()) || 0;
  const valor = unformatNum($("#descuento_valor").val()) || 0;

  // Validar que no ingresen ambos
  if (porcentaje > 0 && valor > 0) {
    Swal.fire('Atención', 'Solo puede ingresar porcentaje o valor, no ambos.', 'warning');
    return;
  }

  if (porcentaje === 0 && valor === 0) {
    Swal.fire('Atención', 'Ingrese un porcentaje o un valor de descuento.', 'warning');
    return;
  }

  const seleccionados = $("#contenedor-tabla-detalles")
    .find(".chk-detalle:checked");

  if (seleccionados.length === 0) {
    Swal.fire('Atención', 'Seleccione al menos un producto.', 'warning');
    return;
  }

  seleccionados.each(function () {
    const index = $(this).data("index");
    const item = window.detallesTransaccion[index];
    const cantidad = parseFloat(item.cantidad) || 1;

    const costoUnitarioActual = parseNumero($(`.nuevo-costo[data-index="${index}"]`).val());
    const costoUnitarioBase = costoUnitarioActual > 0
      ? costoUnitarioActual
      : (parseFloat(item.costo_unitario) || 0);

    let costoTotalBase = costoUnitarioBase * cantidad;
    let nuevoTotal = costoTotalBase;

    if (porcentaje > 0) {
      const descuento = costoTotalBase * (porcentaje / 100);
      nuevoTotal = costoTotalBase - descuento;
    } else {
      nuevoTotal = costoTotalBase - valor;
    }

    if (nuevoTotal < 0) nuevoTotal = 0;
    const nuevoCostoUnitario = cantidad > 0 ? nuevoTotal / cantidad : 0;

    $(`.nuevo-costo[data-index="${index}"]`)
      .val(nuevoCostoUnitario.toFixed(2));
  });

  actualizarTotalDescuentoAplicado();
  actualizarSombreadoFilasAplicadas();

  // limpiar campos
  $("#descuento_porcentaje").val('');
  $("#descuento_valor").val('');

  // desmarcar selección al finalizar el cálculo
  $(".chk-detalle").prop("checked", false);
  $("#chk-select-all-table").prop("checked", false);
};

/**
 * Finaliza el proceso y precarga la informacion en pantalla para guardar
 */
const finalizarDescuento = async () => {
  const { productosSeleccionados, subtotal } = obtenerDescuentosAplicados();

  if (productosSeleccionados.length === 0) {
    Swal.fire("Atención", "No hay descuentos aplicados para guardar.", "warning");
    return;
  }

  // actualizar totales de la nota (subtotal y valor neto = total del descuento)
  $("#subtotal").val(formatNum(subtotal, "$"));
  $("#valor_neto").val(formatNum(subtotal, "$"));

  // colocar documento referencia
  if (window.documentoSeleccionado) {
    $("#doc_ref").val(window.documentoSeleccionado.numero);
  }

  // construir tabla de productos aplicados
  let filas = "";

  productosSeleccionados.forEach(p => {
    filas += `
      <tr>
        <td>${p.codigo}</td>
        <td>${p.descripcion}</td>
        <td class="text-end">${formatNum(p.total_descuento, "$")}</td>
      </tr>
    `;
  });

  $("#contenedor-productos-descuento").html(`
    <div class="table-responsive mt-3">
      <table class="table table-sm table-bordered table-hover">
        <thead class="table-primary">
          <tr>
            <th>Código</th>
            <th>Descripción</th>
            <th class="text-end">Descuento Aplicado</th>
          </tr>
        </thead>
        <tbody>
          ${filas}
        </tbody>
      </table>
    </div>
  `);

  // guardar productos para enviar al backend
  window.productosNotaDescuento = productosSeleccionados;

  // cerrar modales
  $(".modal").modal("hide");

};

/**
 * Carga la lista de tipos de nota (SW)
 */
const swNotas = async () => {
  try {
    const data = {
      link,
      op: 'listar-sw-notas'
    };
    const resp = await enviarPeticion(data);

    const $tipoNota = $("#tipo_nota");
    $tipoNota.empty().append('<option value="">Seleccione un tipo de nota</option>');

    if (Array.isArray(resp.data)) {
      $.each(resp.data, (_, item) => {
        $tipoNota.append(
          $("<option>", {
            value: item.sw,
            text: `${item.sw} - ${item.descripcion}`
          })
        );
      });
    }
  } catch (e) {
    console.error("Error al listar tipos de nota:", e.message);
    Swal.fire({
      icon: 'error',
      title: 'Error al cargar notas',
      text: 'No se pudieron obtener los tipos de nota. Intente nuevamente.'
    });
  }
};

/**
 * Carga los tipos de documento segun el tipo de nota y la bodega
 */
const cargarTipos = async () => {
  const bodega = $("#bodega").val().trim();
  const tipoNota = $("#tipo_nota").val().trim();

  const $tipoDocumento = $("#tipo_documento");
  $tipoDocumento.empty().append('<option value="">Seleccione un tipo de documento</option>');

  if (!tipoNota) return;

  if (!bodega) {
    Swal.fire({
      icon: 'warning',
      title: 'Bodega no encontrada',
      text: 'No se ha identificado la bodega. La página se recargará.',
      confirmButtonText: 'Aceptar',
      allowOutsideClick: false
    }).then(() => location.reload());
    return;
  }

  try {
    const resp = await enviarPeticion({
      link: '../models/tiposDocumentos.php',
      op: 'listar_sw',
      sw: tipoNota,
      bodega: bodega
    });

    if (Array.isArray(resp.data)) {
      $.each(resp.data, (_, item) => {
        $tipoDocumento.append(
          $("<option>", {
            value: item.id,
            text: `${item.tipo} - ${item.descripcion}`,
            'data-documento-electronico': item.documento_electronico || 'N',
            'data-documento-soporte': item.documento_soporte || 'N'
          })
        );
      });
    }
  } catch (e) {
    console.error("Error al cargar tipos de documento:", e);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'No se pudo cargar la lista de tipos de documento.'
    });
  }
};

const obtenerFlagsDocumentoTipo = () => {
  const opt = $("#tipo_documento option:selected");
  return {
    documentoElectronico: String(opt.data('documento-electronico') || 'N').toUpperCase(),
    documentoSoporte: String(opt.data('documento-soporte') || 'N').toUpperCase()
  };
}

const debeEnviarDocumentoElectronico = () => {
  const sw = String($("#tipo_nota").val() || '');
  const flags = obtenerFlagsDocumentoTipo();

  // SW 14 (ND proveedores): permite envio por doc. electronico o doc. soporte
  if (sw === '14') {
    return flags.documentoElectronico === 'S' || flags.documentoSoporte === 'S';
  }

  // Resto de notas: solo si esta marcado como electronico
  return flags.documentoElectronico === 'S';
}

const enviarDocumentoElectronicoNota = async (idTransaccion, sw) => {
  try {
    const respDian = await enviarDocumentoElctronico(idTransaccion, sw);

    if (!respDian || (typeof respDian === 'object' && respDian.ok === false)) {
      const msgBackend = respDian?.message || 'No fue posible enviar el documento electrónico.';
      return { ok: false, data: null, message: msgBackend, isHtml: false };
    }

    const dataDian = (respDian && typeof respDian === 'object')
      ? (respDian.data || respDian?.respuesta?.data || null)
      : null;

    if (!dataDian) {
      const msgSinData = 'No se recibió una respuesta válida del servicio electrónico.';
      return { ok: false, data: null, message: msgSinData, isHtml: false };
    }

    // Guarda respuesta DIAN si viene con estructura esperada
    if (dataDian?.UUID) {
      await guardarRespuestasDian(idTransaccion, dataDian);
    }

    if (String(dataDian?.IsValid || '').toLowerCase() === 'false') {
      let msg = 'No fue posible enviar el documento electrónico.';
      let isHtml = false;
      if (dataDian?.ErrorMessage) {
        if (Array.isArray(dataDian.ErrorMessage)) {
          msg = generarListaHTML(dataDian.ErrorMessage);
          isHtml = true;
        } else {
          msg = String(dataDian.ErrorMessage);
        }
      }
      return { ok: false, data: dataDian, message: msg, isHtml };
    }

    return { ok: true, data: dataDian, message: 'Documento enviado correctamente a la DIAN.', isHtml: false };
  } catch (e) {
    console.error('Error envío electrónico:', e);
    return {
      ok: false,
      data: null,
      message: 'El documento se guardó pero falló el envío electrónico.',
      isHtml: false
    };
  }
}

const obtenerEstadoDocumentoGenerado = (item) => {
  const esElectronico = String(item.documento_electronico || 'N').toUpperCase() === 'S';
  const fueEnviado = String(item.cufe || '').trim() !== '';

  if (!esElectronico) {
    return '<span class="badge bg-secondary-subtle text-secondary border">No electrónico</span>';
  }

  if (fueEnviado) {
    return '<button class="btn btn-sm btn-outline-success" title="Enviado a la DIAN" disabled><i class="fa-solid fa-circle-check text-success"></i></button>';
  }

  return `<button class="btn btn-sm btn-outline-danger btn-enviar-nota-electronica" title="Enviar a la DIAN" data-id="${item.id_transaccion}" data-sw="${item.sw}"><i class="fa-solid fa-paper-plane"></i></button>`;
}

const renderListadoNotas = (data) => {
  const filas = data.map((item) => `
    <tr>
      <td>${item.id_transaccion ?? ''}</td>
      <td>${item.fecha_hora ?? ''}</td>
      <td>${item.tipo ?? ''}</td>
      <td>${item.numero ?? ''}</td>
      <td>${item.razon_comercial ?? ''}</td>
      <td class="text-end">${formatNum(item.valor_total || 0, '$')}</td>
      <td class="text-center">${obtenerEstadoDocumentoGenerado(item)}</td>
      <td class="text-center"><button class="btn btn-sm btn-outline-primary btn-imprimir-nota" title="Imprimir" data-id="${item.id_transaccion}" data-sw="${item.sw}"><i class="fa fa-print"></i></button></td>
    </tr>
  `).join('');

  $("#result").html(`
    <div class="table-responsive">
      <table id="tabla-notas" class="table table-sm table-borderless table-hover w-100">
        <thead class="table-primary">
          <tr>
            <th>Id</th>
            <th>Fecha</th>
            <th>Tipo</th>
            <th>Número</th>
            <th>Tercero</th>
            <th>Valor</th>
            <th>Estado DIAN</th>
            <th>Opc</th>
          </tr>
        </thead>
        <tbody>${filas}</tbody>
      </table>
    </div>
  `);

  if ($.fn.DataTable.isDataTable('#tabla-notas')) {
    $('#tabla-notas').DataTable().clear().destroy();
  }

  $('#tabla-notas').DataTable({
    order: [[0, 'desc']],
    pageLength: 10,
    columnDefs: [
      { targets: [6, 7], orderable: false },
      { targets: [6, 7], searchable: false }
    ]
  });
}

/**
 * Habilita o deshabilita el campo de busqueda del cliente segun las selecciones
 */
const toggleBusquedaTercero = () => {
  const tipoNota = $("#tipo_nota").val().trim();
  const tipoDocumento = $("#tipo_documento").val().trim();
  const $busqueda = $("#txtBusquedaTercero");

  if (tipoNota && tipoDocumento) {
    $busqueda.prop("disabled", false);
  } else {
    $busqueda.val("").prop("disabled", true);
  }
};

const getDirecciones = async (id) => {
  try {
    const dataSend = {
      link: '../models/Terceros.php',
      op: 's_terceros_direcciones',
      id_tercero: id
    };
    const resp = await enviarPeticion(dataSend);
    console.log(resp);
    const direccion = $("#direccion");
    direccion.empty();

    if (Array.isArray(resp.data)) {
      $.each(resp.data, (_, item) => {
        direccion.append(
          $("<option>", {
            value: item.id,
            text: `${item.id} - ${item.direccion}`
          })
        );
      });
    }
  } catch (e) {
    console.error(e); // Agrega manejo de errores
    Swal.fire('Error!', 'Un error ha ocurrido, por favor notifique a su administrador.', 'error');
  }
}

/**
 * Inicializa el autocomplete del campo de búsqueda de terceros
 */
const initAutocompleteTercero = () => {
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
        response(Array.isArray(resp.data) ? resp.data : []);
      } catch (error) {
        console.error("Error en la búsqueda de terceros:", error);
        response([]);
      }
    },
    minLength: 3,
    select: function (event, ui) {
      const tipo = $("#tipo_nota").val();
      const item = ui.item;
      getDirecciones(item.id);
      $("#razon_comercial").val(item.razon_comercial);
      $("#id_tercero").val(item.id);
      $("#nit").val(item.nit);

      if (tipo == 11 || tipo == 12) {
        $("#cond_pago").val(item.id_condicion);
      } else {
        $("#cond_pago").val(item.id_condicion_pago_prov);
      }

      const hoy = new Date();
      let diasCredito = 0;

      if (tipo == 11 || tipo == 12) {
        diasCredito = parseInt(item.dias_c) || 0;
      } else {
        diasCredito = parseInt(item.dias_p) || 0;
      }

      const fechaVenc = new Date(hoy);
      fechaVenc.setDate(hoy.getDate() + diasCredito);

      const vencimientoFormateado = fechaVenc.toISOString().split('T')[0];
      $("#fecha_vencimiento").val(vencimientoFormateado);
      const diffMs = fechaVenc - hoy;
      const diffDias = Math.ceil(diffMs / (1000 * 60 * 60 * 24));

      $("#dias").val(diffDias);
    }
  });
};
const convertirAMayusculas = (form) => {
  var inputs = form.querySelectorAll('input[type="text"], input[type="email"], textarea');
  inputs.forEach(function (input) {
    input.value = input.value.toUpperCase();
  });
}

const limpiarFormularioNota = () => {
  const form = $("#FormNota")[0];
  if (form) {
    form.reset();
    form.classList.remove('was-validated');
  }

  // Limpia resultados visuales de descuentos/documentos asociados
  $("#contenedor-productos-descuento").html('');
  $("#contenedor-tabla-detalles").html('');
  $("#contenedor-tabla-documentos").html('');
  const direccion = $("#direccion");
  direccion.empty();

  window.productosNotaDescuento = [];
  window.detallesTransaccion = [];
  $("#total-descuento-aplicado").text(formatNum(0, '$'));
}


const listarMovimientos = async () => {
  showLoadingWithTetrisLogo('Cargando información', 'ISA Software');
  try {
    const data = {
      link: link,
      op: 'listar-notas'
    }
    let resp = await enviarPeticion(data); console.log(resp);
    if (!Array.isArray(resp) || resp.length === 0) {
      $("#result").html(ShowAlert('No se encontraron resultados', 'warning'))
      dissminSwal();
      return;
    }
    renderListadoNotas(resp);
    dissminSwal();
  } catch (e) {
    console.error(e.message)
    $("#result").html(ShowAlert('Error interno de la aplicación', 'error'))
    dissminSwal();
  }
}
const imprimirFila = async (data) => {
  await ImprimirDocumento(data.sw, data.id_transaccion, 'Impresión de documento', false)
}

const imprimirNotaDesdeListado = async (idTransaccion, sw) => {
  await ImprimirDocumento(sw, idTransaccion, 'Impresión de documento', false)
}

const enviarNotaElectronicaDesdeListado = async (idTransaccion, sw) => {
  let res = null;

  showLoadingWithTetrisLogo('Enviando documento', 'ISA Software');


  try {
    res = await enviarDocumentoElectronicoNota(idTransaccion, sw);
  } finally {
    dissminSwal();
  }

  if (res?.ok) {
    const uuid = String(res?.data?.UUID || '').trim();
    const html = uuid
      ? `<p>Documento enviado correctamente a la DIAN.</p><p><b>UUID:</b> ${uuid}</p>`
      : 'Documento enviado correctamente a la DIAN.';
    await Swal.fire('Éxito', html, 'success');
  } else {
    if (res?.isHtml) {
      await Swal.fire({
        title: 'Atención',
        html: res.message || 'No fue posible enviar el documento electrónico.',
        icon: 'warning'
      });
    } else {
      await Swal.fire(
        'Atención',
        res?.message || 'No fue posible enviar el documento electrónico.',
        'warning'
      );
    }
  }

  await listarMovimientos();
}


const controlCamposNumericos = (selector, callback = null) => {
  // Focus: limpia si es 0
  $(document).on("focus", selector, function () {
    const val = $(this).val().trim();
    if (val === "0" || val === "$0" || val === "$0.00") {
      $(this).val("");
    }
  });

  // Blur: formatea
  $(document).on("blur", selector, function () {
    const n = parseNumero($(this).val());
    $(this).val(formatNum(n, '$'));
    if (typeof callback === "function") {
      callback();
    }
  });
  // Input: recalculo en tiempo real
  $(document).on("input", selector, function () {
    if (typeof callback === "function") {
      callback();
    }
  });

  // Solo números
  $(document).on("keypress", selector, function (e) {
    const char = String.fromCharCode(e.which);
    if (!/[0-9.,]/.test(char)) {
      e.preventDefault();
    }
  });
  // Inicializar formato
  $(selector).each(function () {
    const n = parseNumero($(this).val());
    if (n > 0) {
      $(this).val(formatNum(n, '$'));
    }
  });

};

// Recalcula el valor neto
const recalcularValorNeto = () => {
  const subtotal = parseNumero(unformatNum($("#subtotal").val()));
  const descuento = parseNumero(unformatNum($("#descuento").val()));
  const iva = parseNumero(unformatNum($("#iva").val()));
  const retefuente = parseNumero(unformatNum($("#retefuente").val()));
  const reteiva = parseNumero(unformatNum($("#reteiva").val()));
  const reteica = parseNumero(unformatNum($("#reteica").val()));

  //F rmula: (Subtotal - Descuento + IVA) - (ReteFuente + ReteIVA + ReteICA)
  const neto = (subtotal - descuento + iva) - (retefuente + reteiva + reteica);

  $("#valor_neto").val(formatNum(neto, '$'));
};
// Convierte texto a n mero limpio
const parseNumero = (valor) => {
  if (valor === null || valor === undefined) return 0;

  // convertir a string para validar caracteres
  const str = String(valor).trim();

  // si contiene algo que no sea dígito, punto, coma o signo negativo
  if (/[^0-9.,-]/.test(str)) {
    // usar unformatNum para limpiar
    return unformatNum(str);
  }

  // ya es número "limpio" o sólo dígitos
  const n = parseFloat(str.replace(',', '.'));
  return isNaN(n) ? 0 : n;
};
/**
 * Inicializacion
 **/
$(async function () {
  fechaActual([
    ['filtro_fecha_ini', 'filtro_fecha_fin'],
    ['fecha_ini', 'fecha_fin']
  ]);

  $("#txtBusquedaTercero").prop("disabled", true); // Deshabilitar al inicio

  await swNotas();
  await cargarTipos();
  initAutocompleteTercero();

  dissminSwal();

  // Cuando cambia tipo_nota
  $("#tipo_nota").change(async function () {
    const tipo = $(this).val();
    if (tipo == 11 || tipo == 12) {
      condicionesPago('C', 'cond_pago');
    } else {
      condicionesPago('P', 'cond_pago');
    }

    // Mostrar botón Asociar Documento solo para tipo 13
    $("#btn-asociar-doc").toggleClass('d-none', tipo != 13);

    await cargarTipos();
    toggleBusquedaTercero();
  });

  // Cuando cambia tipo_documento
  $("#tipo_documento").change(() => {
    toggleBusquedaTercero();
  });


  const camposNumericos = "#subtotal, #descuento, #iva, #retefuente, #reteiva, #reteica";

  controlCamposNumericos(camposNumericos, recalcularValorNeto);

  // Evento submit del formulario con validaci n Bootstrap
  const forms = document.querySelectorAll('.needs-validation');

  Array.prototype.slice.call(forms).forEach((form) => {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();

      // Convierte todos los textos a mayúsculas
      convertirAMayusculas(form);

      // Validación HTML5 / Bootstrap
      if (!form.checkValidity()) {
        event.stopPropagation();
        form.classList.add('was-validated');
        return;
      }

      // Convierte el formulario a objeto jQuery y formatea datos
      const data = formatearArrayRequest($(form).serializeArray());
      console.log("Datos formateados:", data);

      // Variables de control
      const id_bodega = $("#bodega").val().trim();
      const sw = $("#tipo_nota").val().trim();
      const id_tipo_documento = $("#tipo_documento").val().trim();
      const id_tercero = $("#id_tercero").val().trim();
      const id_condicion = $("#cond_pago").val();

      if (!id_condicion) {
        return Swal.fire(
          "Atención",
          "El tercero seleccionado no posee una condición de pago, por favor asígnela en el módulo de terceros.",
          "warning"
        );
      }
      // Validaciones manuales
      if (!id_tercero) return Swal.fire("Atención", "Debe seleccionar un tercero.", "warning");
      if (!id_tipo_documento) return Swal.fire("Atención", "Debe seleccionar un tipo de documento.", "warning");
      if (!id_bodega) return Swal.fire("Atención", "Debe seleccionar una bodega.", "warning");
      if (!data.valor_neto || parseNumero(data.valor_neto) <= 0)
        return Swal.fire("Atención", "El valor neto debe ser mayor a cero.", "warning");

      // Evita envíos múltiples
      const btnSubmit = $(form).find('button[type="submit"]');
      btnSubmit.prop('disabled', true);

      try {
        const confirm = await Swal.fire({
          title: '¿Confirmar transacción?',
          text: `Se va a generar un nuevo documento, esto no se puede reversar`,
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Sí, continuar',
          cancelButtonText: 'Cancelar'
        });

        if (!confirm.isConfirmed) return;

        showLoadingWithTetrisLogo('Guardando información', 'ISA Software');

        let id_doc_link = 0;
        if (window.productosNotaDescuento && window.productosNotaDescuento.length > 0) {
          if (window.detallesTransaccion && window.detallesTransaccion.length > 0) {
            id_doc_link = window.detallesTransaccion[0].id_transaccion || 0;
          }
        }

        // 1️⃣ Crear la transacción principal
        const resp = await enviarPeticion({
          link: '../shared/servicios/servicios.php',
          op: 'add_transacciones_tmp',
          id_tipo_documento,
          id_tercero,
          id_bodega,
          notas: data.notas,
          valor_total: (parseNumero(data.valor_neto) + parseNumero(data.retencion_fuente) + parseNumero(data.retencion_iva) + parseNumero(data.retencion_ica)),
          iva: parseNumero(data.iva),
          descuento: parseNumero(data.descuento),
          sw,
          id_tercero_dir: data.id_tercero_dir,
          id_condicion,
          vencimiento: data.vencimiento,
          retencion_fuente: parseNumero(data.retencion_fuente),
          retencion_iva: parseNumero(data.retencion_iva),
          retencion_ica: parseNumero(data.retencion_ica),
          dias: data.dias,
          es_credito: 1,
          id_doc_link: id_doc_link
        });

        if (resp.error) {
          Swal.fire("Error", "No se pudo insertar el documento.", "error");
          return;
        }

        const id_transaccion = resp.data.id;

        // 2️⃣ Insertar los detalles desde productos seleccionados
        if (window.productosNotaDescuento && window.productosNotaDescuento.length > 0) {
          for (const p of window.productosNotaDescuento) {
            console.log(p);
            det = await enviarPeticion({
              link: '../shared/servicios/servicios.php',
              op: 'add_transacciones_detalle_tmp',
              id_transaccion,
              id_referencia: p.id_referencia,
              cantidad: p.cantidad,
              valor_unitario: (p.total_descuento / p.cantidad),
              costo_unitario: (p.total_descuento / p.cantidad),
              pcj_iva: 0,
              pcj_descuento: 0,
              und: p.unidad,
              can_und: p.cantidad,
              sw,
              id_bodega: id_bodega,
              id_pedido: 0,
              id: 0
            });
            console.log(det);
          }
        }

        // 3️⃣ Volcar documento
        const respDoc = await enviarPeticion({
          link: '../shared/servicios/servicios.php',
          op: 'volcar_documento',
          id_transaccion,
          updateVendedor: 'N',
          vendedor: ''
        });

        if (!respDoc.ok) {
          Swal.fire("Error", respDoc.message, "error");
          await enviarPeticion({
            link: '../shared/servicios/servicios.php',
            op: 'delete_transaccion',
            id_transaccion
          });
          return;
        }

        // 4️⃣ Generar contabilización
        // 3.5 Ajustar costo promedio en t_referencias_mov (solo notas de proveedor sw=13 y sw=14)
        if ((sw == 13 || sw == 14) && window.productosNotaDescuento && window.productosNotaDescuento.length > 0) {
          const hoy = new Date();
          const mesActual = hoy.getMonth() + 1; // JS getMonth() es 0-based
          const anoActual = hoy.getFullYear();

          for (const p of window.productosNotaDescuento) {
            // Positivo: aumenta costo_ent (ND proveedor sw=14)
            // Negativo: reduce  costo_ent (NC proveedor sw=13)
            const ajuste_costo =
              (parseFloat(p.nuevo_costo) - parseFloat(p.costo_original)) * parseFloat(p.cantidad);

            if (Math.abs(ajuste_costo) > 0) {
              await enviarPeticion({
                link: link,
                op: 'ajustar-costo-promedio',
                id_referencia: p.id_referencia,
                id_bodega: id_bodega,
                mes: mesActual,
                ano: anoActual,
                ajuste_costo: ajuste_costo,
                id_doc_link: id_doc_link,
                nuevo_costo: p.nuevo_costo
              });
            }
          }
        }

        const respCont = await generarContabilizacion(respDoc.id);
        let txtCont = respCont.message ?? "";
        let respEnvioElectronico = null;

        // 4.1 Envio electronico condicionado por tipo de documento
        if (debeEnviarDocumentoElectronico()) {
          respEnvioElectronico = await enviarDocumentoElectronicoNota(respDoc.id, sw);

          if (!respEnvioElectronico?.ok) {
            if (respEnvioElectronico?.isHtml) {
              await Swal.fire({
                title: 'Atención',
                html: respEnvioElectronico.message || 'No fue posible enviar el documento electrónico.',
                icon: 'warning'
              });
            } else {
              await Swal.fire(
                'Atención',
                respEnvioElectronico?.message || 'No fue posible enviar el documento electrónico.',
                'warning'
              );
            }
          }
        }

        // 5️⃣ Mensaje éxito
        Swal.fire({
          title: 'Éxito',
          html: `<p>Documento <b>#${respDoc.id}</b> generado correctamente.</p>
               <p><b>Contabilización</b></p>
               <p>${txtCont}</p>`,
          icon: 'success',
          showCancelButton: true,
          confirmButtonText: 'OK',
          cancelButtonText: 'Imprimir',
          reverseButtons: true
        }).then(async (result) => {
          if (result.dismiss === Swal.DismissReason.cancel) {
            await ImprimirDocumento(sw, respDoc.id, 'Impresión de documento', false);
          }
        });

        // 6️⃣ Reset form
        limpiarFormularioNota();

      } catch (error) {
        console.error("Error al enviar la petición:", error);
        Swal.fire("Error", "Ocurrió un problema en el envío de la información.", "error");
      } finally {
        btnSubmit.prop('disabled', false);
      }

    }, false);
  });
  $("#btn-buscar").on("click", function () {
    listarMovimientos();
  });
  $(document).off('click', '.btn-imprimir-nota').on('click', '.btn-imprimir-nota', async function () {
    const idTransaccion = $(this).data('id');
    const sw = $(this).data('sw');
    await imprimirNotaDesdeListado(idTransaccion, sw);
  });
  $(document).off('click', '.btn-enviar-nota-electronica').on('click', '.btn-enviar-nota-electronica', async function () {
    const idTransaccion = $(this).data('id');
    const sw = $(this).data('sw');
    await enviarNotaElectronicaDesdeListado(idTransaccion, sw);
  });
  $("#btn-nuevo").on("click", function () {
    limpiarFormularioNota();
  });
  $("#btn-filtrar-documentos").on("click", function () {
    buscarDocumentos();
  });

  /**
   * Eventos para el modal de detalles de transacción
   */
  $("#btn-aplicar-descuento").on("click", function () {
    aplicarDescuento();
  });

  $("#btn-limpiar-descuentos").on("click", function () {
    limpiarDescuentosAplicados();
  });

  /**
   * Cuando el usuario escriba porcentaje, deshabilitar valor y viceversa, esto evita errores
   */

  $("#descuento_porcentaje").on("input", function () {
    $("#descuento_valor").prop("disabled", $(this).val() !== "");
  });

  $("#descuento_valor").on("input", function () {
    $("#descuento_porcentaje").prop("disabled", $(this).val() !== "");
  });

  /**
   * Finaliza el proceso
   */
  $("#btn-aplicar-descuentos-finales").on("click", function () {
    finalizarDescuento();
  });
  dissminSwal();

});