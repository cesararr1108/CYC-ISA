const id_modulo = '35';
// configuraciones 
let carrito_enc = [];
let carrito_det = [];
let permisos = [];
let data_tipos = '';
let modalBusquedaRef = null;
let buscando = false;
//let soloStock = false;
//let limite_registros = 20;
let conceptosRetencion = [];
let perfilTributarioRetenciones = {
  ret_fte: 'N',
  ret_cree: 'N',
  ret_ica: 'N',
  ret_iva: 'N'
};


const disabledEnabledTercero = (sw) => {
  $("#nit").attr("disabled", sw);
  $("#id_tercero_dir").attr("disabled", sw);
  $("#tipos").attr("disabled", sw);
  $("#id_condicion").attr("disabled", sw)
  $("#bodegas").attr("disabled", sw)
};

const cargarTercerosDefault = async () => {
  if (ConfModulos.TerceroDefault) {
    if (ConfModulos.TerceroDefault != '') {
      const data = {
        link: '../models/Terceros.php',
        filtro: ConfModulos.TerceroDefault,
        tipo_tercero: '',
        estado: 'A',
        op: 's_tercero'
      };

      try {
        const resp = await enviarPeticion(data);
        if (resp.data.length) {
          let datos = resp.data;
          let ok = await cargarTercero(datos[0]);
          if (!ok) {
            return false;
          } else {
            return true;
          }
        }
      } catch (e) {
        console.error(e)
      }
    }
  }

};

const validarTercero = async (item) => {
  if (!permisos.IgnorarBloqueoTercero && item.estado !== 'A') {
    return {
      sw: false,
      msj: 'El cliente se encuentra bloqueado'
    };
  }

  if (!validarEmail(item.email_fe)) {
    return {
      sw: false,
      msj: 'El email del cliente no es válido'
    };
  }

  if (item.dias === 0) {
    return {
      sw: false,
      msj: 'El cliente no tiene condición de pago crédito'
    };
  }

  let credito = null;

  if (permisos.ControlCarteraVencida || permisos.ControlCupoCredito) {
    const consultaCredito = await controlCreditoTercero(item.id);

    console.log(consultaCredito)

    if (!consultaCredito?.ok || !consultaCredito.data?.length) {
      return {
        sw: false,
        msj: 'No fue posible validar el cupo del tercero, por favor verifique e intente nuevamente.'
      };
    }

    credito = consultaCredito.data[0];
  }

  if (permisos.ControlCarteraVencida && credito?.en_mora === 'S') {
    return {
      sw: false,
      msj: 'El tercero presenta cartera en mora, imposible continuar con la venta.'
    };
  }

  if (permisos.ControlCupoCredito && Number(credito?.cupo_disponible) < 0) {
    return {
      sw: false,
      msj: 'El tercero no tiene cupo de crédito disponible, imposible continuar con la venta.'
    };
  }

  return {
    sw: true,
    msj: ''
  };
};

const cargarTercero = async (item) => {

  const valido = await validarTercero(item);

  if (!valido.sw) {
    Swal.fire("Oops", valido.msj, "warning")
    return false;
  }

  $("#id_tercero").val(item.id);
  $("#nit").val(item.nit);
  $("#tratamiento").val(item.tratamiento);
  $("#razon_comercial").val(item.razon_comercial);
  $("#textDireccion").val(item.direccion)
  $("#lista_tercero").val(parseInt(item.lista_precios));
  $("#info_condicion").text('CONTADO')
  $("#info_lista").text(item.lista_precios);
  $("#info_credito").text(formatNum(item.cupo_credito, '$'))
  $("#info_zona_venta").text(item.codigo_zona)
  $("#info_zona_descripcion").text(item.zona_descripcion)
  $("#info_email").text(item.email_fe)
  $("#info_controlados").text($.trim(item.controlados))
  $("#info_controlados").text($.trim(item.controlados))
  $("#info_institucional").text($.trim(item.institucionales))

  await cargarPerfilTributarioDesdeTercero(item.id);
  await listarConceptosRetencion();

  let direccionesCargadas = await cargarDirecciones(item.id);
  if (!direccionesCargadas) {
    return;
  }

  $('#tabs button[data-bs-target="#navs-creacion"]').attr('disabled', false)
  $('#tabs button[data-bs-target="#navs-creacion"]').tab("show")
  setTimeout(() => {
    $("#filtro").focus();
  }, 500);

  return true;
};

const listarAlmacenes = async () => {
  try {

    const resp = await bodegasUsuarios();
    if (resp.length == 0) {
      $("#result").html(ShowAlert('No se encontraron resultados', 'warning'))
      return;
    }
    let options = ``;
    resp.forEach((d) => {
      options += `<option value="${d.id}">${d.bodega} - ${d.descripcion}</option>`;
    });
    $("#bodegas").html(options);
    $("#bodegas").val($("#id_bodega_session").val()).attr("disabled", true)
  } catch (e) {
    console.error(e)
    $("#result").html(ShowAlert('Error interno de la aplicación', 'error'))
  }
};

const cargarDirecciones = async (id_tercero) => {

  try {
    const resp = await enviarPeticion({
      link: '../models/Pedidos.php',
      op: 'listarDirecciones',
      id_tercero
    });

    if (resp.length == 0) {
      nuevoPedido();
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

};

const listarConceptosRetencion = async () => {
  const idTipoDocumento = parseInt($("#tipos").val() || '0', 10);

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
};

const limpiarPerfilTributarioCompra = () => {
  perfilTributarioRetenciones = { ret_fte: 'N', ret_cree: 'N', ret_ica: 'N', ret_iva: 'N' };
  $("#id_perfil_tributario").val('0');
  $("#perfil_tributario_compra").html('<option value="">NO APLICA</option>');
  aplicarRetencionesPorPerfil();
};

const pintarRetencionesTotales = (retFte = 0, retIca = 0, retIva = 0) => {
  const totalBruto = unformatNum($("#t_total").val() || 0);
  const totalRetenciones = retFte + retIca + retIva;
  const netoPagar = Math.max(0, totalBruto - totalRetenciones);

  $("#t_ret_fte").val(formatearMonedaLocal(retFte));
  $("#t_ret_ica").val(formatearMonedaLocal(retIca));
  $("#t_ret_iva").val(formatearMonedaLocal(retIva));
  $("#t_ret_total").val(formatearMonedaLocal(totalRetenciones));
  $("#t_neto_pagar").val(formatearMonedaLocal(netoPagar));
};

const aplicarRetencionesPorPerfil = () => {
  const idConcepto = parseInt($("#concepto_retencion").val() || '0', 10);
  const concepto = conceptosRetencion.find((c) => parseInt(c.id, 10) === idConcepto);

  if (!concepto) {
    pintarRetencionesTotales(0, 0, 0);
    return;
  }

  const subtotal = unformatNum($("#t_bruto").val() || 0);
  const descuento = unformatNum($("#t_dcto").val() || 0);
  const ivaTotal = unformatNum($("#t_iva").val() || 0);
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

    const perfiles = Array.isArray(resp.data) ? resp.data : [];

    if (!resp.ok || perfiles.length === 0) {
      limpiarPerfilTributarioCompra();
      return;
    }

    const perfilSeleccionado = perfiles.find((p) => parseInt(p.bodega, 10) === bodega) || perfiles[0];

    perfilTributarioRetenciones = {
      ret_fte: perfilSeleccionado.ret_fte || 'N',
      ret_cree: perfilSeleccionado.ret_cree || 'N',
      ret_ica: perfilSeleccionado.ret_ica || 'N',
      ret_iva: perfilSeleccionado.ret_iva || 'N'
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
};

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
};

const guardarRetencionesTMP = async (idTransaccion, retFte, retIca, retIva) => {
  try {
    let resp = await enviarPeticion({
      link: '../models/Pos.php',
      op: 'guardar_retenciones',
      id_transaccion: idTransaccion,
      ret_fte: retFte,
      ret_ica: retIca,
      ret_iva: retIva,
      id_concepto_retencion: parseInt($("#concepto_retencion").val() || '0', 10)
    });
    // console.log(resp);
  } catch (e) {
    console.error('Error al guardar retenciones:', e);
  }
};

const formatearMonedaLocal = (valor) => {
  const n = isNaN(parseFloat(valor)) ? 0 : parseFloat(valor);
  if (typeof ConfGeneral !== 'undefined' && ConfGeneral) {
    return formatNum(n, '$', ConfGeneral);
  }
  return n.toFixed(2);
};

const restaurarRetencionesEncabezado = (data) => {
  const idConcepto = parseInt(data.id_concepto_retencion || '0', 10);
  const retFte = parseFloat(data.retencion_fuente || 0);
  const retIca = parseFloat(data.retencion_ica || 0);
  const retIva = parseFloat(data.retencion_iva || 0);

  if (idConcepto > 0) {
    $("#concepto_retencion").val(String(idConcepto));
  }
  pintarRetencionesTotales(retFte, retIca, retIva);
};
const limpiarPedido = () => {
  $("#numero").val("");
  $("#t_bruto, #t_dcto, #t_iva, #t_total, #id_transaccion").val(0);
  limpiarPerfilTributarioCompra();
};

const limpiarTercero = () => {
  $("#id_tercero, #nit, #tratamiento, #razon_comercial, #txtBusquedaTercero, #notas").val("");
  $("#info_condicion, #info_lista, #info_credito, #info_zona_venta, #info_email, #info_zona_descripcion, #info_institucional, #info_controlados").text("");
  $("#id_tercero_dir").html("");
};
const limpiarReferencia = () => {
  $("#unidades_comprometidas").text("");
  $("#und_grid").html("");
  $(
    "#codigo_grid, #laboratorio_grid, #descripcion_grid, #stock_grid, " +
    "#preciob_grid, #dto_grid, #iva_grid, #precion_grid, #cantidad_grid, " +
    "#total_grid, #filtro, #tipo_referencia"
  ).val("");
  $("#stock_real, #id_lote, #precio_unitario, #id_referencia, #costo_promedio, #id_detalle, #comision_grid").val(0);
  if (ConfGeneral.ManejaFechaLote == 'S') {
    $("#lote_grid, #vcmto_grid").val("");
  }
  $("#precion_grid").attr("readonly", "readonly");
  $("#btn_comision_producto").addClass("d-none");
};

const limpiarTemporales = () => {
  $("#temporales").html('')
  fechaActual([
    ['fecha_inicial', 'fecha_final']
  ]);
};

const nuevoPedido = () => {
  limpiarPedido()
  limpiarReferencia()
  limpiarTercero()
  limpiarTemporales()
  $("#carrito tbody tr").remove()
  $("#modalMediosPagos").modal("hide");
  $("#table-pagos tbody tr").remove();
  $("#valor_factura").text('$0');
  $("#valor_pago").text('$0');
  $("#cambio").text('$0');
  $("#valor_recibido").val('$0');
  $("#forma_pago").val(1);
  $("#valor_pago").html('$0')
  disabledEnabledTercero(false)
  $("#domicilio").removeClass("btn-success").addClass("btn-outline-secondary");
  $('#tabs button[data-bs-target="#navs-tercero"]').tab("show")
};

let itemActual = null;

const cargarItemGrid = (data) => {
  //console.log("Cargando item en grid:", data);
  itemActual = data;

  const $und = $("#und_grid");
  const $stockReal = $("#stock_real");

  $("#id_referencia").val(data.id);
  $("#tipo_referencia").val(data.tipo);
  $("#descripcion_grid").val(data.descripcion);
  $("#laboratorio_grid").val(data.laboratorio);
  $("#codigo_grid").val(data.codigo);
  $("#precio_unitario").val(data.precio);
  $("#dto_grid").val(data.dcto);
  $("#iva_grid").val(data.porcentaje_iva);
  $stockReal.val(data.total_stock);
  $("#unidades_comprometidas").text(data.comprometidas);
  $("#costo_promedio").val(data.promedio);

  const stockUnd = data.total_stock > 0
    ? `${parseInt(data.total_stock / data.can_und)} ${data.des_und_comp} y ${(data.total_stock % data.can_und) * data.can_und}`
    : 0;

  $("#stock_grid").val(stockUnd);

  // Construcción de unidades
  const unds = [1, 2, 3, 4]
    .map(i => ({
      nombre: data[`und_${i}`],
      cantidad: data[`cant_${i}`],
      precio: data[`precio${i === 1 ? "" : i}`],
      precion: data[`precion${i === 1 ? "" : i}`],
      comision: data[`comision${i}`]
    }))
    .filter(u => u.cantidad > 0);

  const opciones = unds.map(u => `
    <option
      value="${u.cantidad}"
      data-precio="${u.precio}"
      data-precion="${u.precion}"
      data-comision="${u.comision}"
      ${Number(u.cantidad) === Number(data.can_und) ? "selected" : ""}
    >
      ${$.trim(u.nombre)}-${u.cantidad}
    </option>
  `).join("");


  //Comisiones 
  $und
    .data("maneja-comision", data.comision)
    .data("calculo-comision", data.calculo_comision)
    .html(opciones);
  //Comisiones 
  // Si no existe la unidad correspondiente, seleccionar la primera
  if (!$und.val() && unds.length) {
    $und.val(unds[0].cantidad);
  }

  const cantSelect = Number($und.val());

  const datosPrecio = calcularPrecioUnidad(
    data.precio,
    cantSelect,
    data.dcto,
    data.porcentaje_iva
  );

  $("#preciob_grid").val(formatNum(datosPrecio.preciob, "$"));
  $("#precion_grid").val(formatNum(datosPrecio.precion, "$"));

  $("#cantidad_grid").prop(
    "max",
    Math.floor(Number($stockReal.val()) / cantSelect)
  );

  if (ConfGeneral.ManejaFechaLote === "S") {
    $("#vcmto_grid").val(data.vencimiento);
    $("#id_lote").val(data.id_lote);
    $("#lote_grid").val(data.lote);
  }

  $("#result-lotes").empty();

  $und.trigger("change");

  setTimeout(() => {
    $("#cantidad_grid").focus().select();
  }, 400);
};

const cargarItemGridLote = (ob) => {
  const element = JSON.parse($(ob).attr("data-element"));
  cargarItemGrid(element)
  $("#modalFechaLotes").modal("hide");

};

const cargarLote = async (data) => {
  try {
    const resp = await enviarPeticion({
      link: '../shared/servicios/servicios.php',
      op: 'cargar-lotes',
      id_referencia: data.id,
      bodega: $("#bodegas").val()
    });

    if (resp.length == 0) {
      data.id_lote = 0;
      data.lote = 0;
      data.vencimiento = '';
      data.total_stock = 0;
      cargarItemGrid(data);
      return;
    }

    if (resp.length == 1) {
      const item_lotes = resp[0];
      data.id_lote = item_lotes.id_lote;
      data.lote = item_lotes.lote;
      data.vencimiento = item_lotes.vencimiento,
        data.total_stock = item_lotes.stock - item_lotes.comprometidas
      cargarItemGrid(data);
      return;
    }
    $("#modalFechaLotes").modal("show");

    tb = `
          <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered align-middle data-lotes mb-0">
              <thead class="table-primary">
                <tr class="text-center">
                  <th>Lote</th>
                  <th>Vencimiento</th>
                  <th>Stock</th>
                  <th>Disponible</th>
                </tr>
              </thead>
              <tbody>
          `;

    let tab = 300;

    resp.forEach(item => {
      data.id_lote = item.id_lote;
      data.lote = item.lote;
      data.vencimiento = item.vencimiento;
      data.total_stock = item.stock - item.comprometidas - item.comp_scan;

      const disponible = item.stock - item.comprometidas;

      tb += `
              <tr
                tabindex="${tab}"
                class="cursor-pointer"
                onclick="cargarItemGridLote(this)"
                data-element='${JSON.stringify(data)}'>
                <td><strong>${item.lote}</strong></td>
                <td class="text-center">${item.vencimiento}</td>
                <td class="text-center">${item.stock}</td>
                <td class="text-center">
                  <span class="badge ${disponible > 0 ? 'bg-success' : 'bg-danger'}">
                    ${disponible}
                  </span>
                </td>
              </tr>
            `;
      tab++;
    });

    tb += `
              </tbody>
            </table>
          </div>`;
    $("#result-lotes").html(tb);

    setTimeout(function () {
      $(".data-lotes tbody tr:first").addClass("focus").focus()
    }, 600);

  } catch (e) {
    console.error(e)
    Swal.fire("Error", "Error al cargar los lotes", "error")
  }

};

const actualizarTotales = async (totales) => {
  $("#t_bruto").val(formatNum(parseFloat(totales.valor_total) - parseFloat(totales.iva) + parseFloat(totales.descuento), '$'));
  $("#t_dcto").val(formatNum(totales.descuento, '$'));
  $("#t_iva").val(formatNum(totales.iva, '$'));
  $("#t_total").val(formatNum(totales.valor_total, '$'));
  aplicarRetencionesPorPerfil();
  const retFte = unformatNum($("#t_ret_fte").val() || '0');
  const retIca = unformatNum($("#t_ret_ica").val() || '0');
  const retIva = unformatNum($("#t_ret_iva").val() || '0');
  const idTransaccion = $("#id_transaccion").val();
  if (idTransaccion > 0) {
    await guardarRetencionesTMP(idTransaccion, retFte, retIca, retIva);
  }
};

const eliminarItem = async (id) => {

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
        // nuevoPedido();
        limpiarPedido()
        limpiarReferencia()
        limpiarTemporales()
        $("#carrito tbody tr").remove()
        $("#modalMediosPagos").hide();
        $("#table-pagos tbody tr").remove();
        $("#valor_factura").val('$0');
        $("#valor_pago").val('$0');
        $("#cambio").val('$0');
        $("#valor_recibido").val('$0');
        $("#forma_pago").val(1);
        $("#valor_pago").html('$0')
        disabledEnabledTercero(false)
        return;
      }
      actualizarTotales(resp.totales[0])

      $("#id_detalle" + id).remove();
    }
  });

};

const editarPedido = async (id) => {

  try {

    showLoadingWithTetrisLogo('Cargando información...', 'ISA Software');

    const resp = await enviarPeticion({
      link: '../shared/servicios/servicios.php',
      op: 'detalle_transacciones_tmp',
      id
    });

    //console.log({ resp });

    if (!resp || resp.length === 0) {
      Swal.fire("Error", "No se encontraron items", "error");
      return;
    }

    const item = resp[0];

    // Guardamos item actual
    itemActual = item;


    // =========================
    // DATOS GENERALES
    // =========================

    const totalStock = Number(item.total_stock) || 0;
    const comprometidas = Number(item.comprometidas) || 0;
    const cantidadItem = Number(item.cantidad) || 0;

    const stock_total =
      totalStock - (comprometidas - cantidadItem);

    $("#id_referencia").val(item.id);
    $("#tipo_referencia").val(item.tipo);
    $("#descripcion_grid").val(item.descripcion);
    $("#laboratorio_grid").val(item.laboratorio);
    $("#codigo_grid").val(item.codigo);

    $("#precio_unitario").val(
      Number(item.valor_unitario) || 0
    );

    $("#costo_promedio").val(
      Number(item.costo_unitario) || 0
    );

    $("#stock_real").val(
      Number.isFinite(stock_total) ? stock_total : 0
    );

    $("#id_detalle").val(item.id_detalle);
    $("#unidades_comprometidas").text(
      Number(item.comprometidas) || 0
    );

    $("#dto_grid").val(
      Number(item.pcj_descuento) || 0
    );

    $("#iva_grid").val(
      Number(item.iva) || 0
    );

    $("#id_transaccion").val(item.id_transaccion);


    // =========================
    // LOTE
    // =========================

    if (ConfGeneral.ManejaFechaLote === 'S') {

      $("#id_lote").val(item.id_lote);
      $("#lote_grid").val(item.lote);
      $("#vcmto_grid").val(item.vencimiento);

    }


    // =========================
    // CONSTRUIR UNIDADES
    // =========================

    const unds = [1, 2, 3, 4]
      .map(i => ({
        nombre: item[`und_${i}`],
        cantidad: item[`cant_${i}`],
        precio: item[`precio${i === 1 ? "" : i}`],
        precion: item[`precion${i === 1 ? "" : i}`],
        comision: item[`comision${i}`]
      }))
      .filter(u => Number(u.cantidad) > 0);


    // =========================
    // OPCIONES DEL SELECT
    // =========================

    const opciones = unds.map(u => `
      <option
        value="${u.cantidad}"
        data-precio="${u.precio || 0}"
        data-precion="${u.precion || 0}"
        data-comision="${u.comision || 0}"
        ${Number(u.cantidad) === Number(item.can_und)
        ? "selected"
        : ""}
      >
        ${$.trim(u.nombre || '')}-${u.cantidad}
      </option>
    `).join("");


    // =========================
    // CONFIGURACIÓN DE COMISIÓN
    // =========================

    $("#und_grid")
      .data("maneja-comision", item.comision)
      .data("calculo-comision", item.calculo_comision)
      .html(opciones);


    // =========================
    // SELECCIONAR UNIDAD
    // =========================

    $("#und_grid").val(item.can_und);


    // =========================
    // CANTIDAD DEL DETALLE
    // =========================

    const canUnd =
      Number(item.can_und) || 1;

    const cantidad =
      cantidadItem / canUnd;

    $("#cantidad_grid").val(
      Number.isFinite(cantidad)
        ? cantidad
        : 0
    );


    // =========================
    // RECALCULAR TODO
    // =========================
    //
    // El evento change se encarga de:
    // - Precio
    // - Precio con IVA/descuento
    // - Stock
    // - Total
    // - Comisión
    // - Indicador de comisión
    //

    $("#und_grid").trigger("change");


    dissminSwal();

  } catch (e) {

    console.error(e);

    dissminSwal();

    Swal.fire(
      "Error",
      "Ha ocurrido un error al intentar obtener los datos!",
      "error"
    );
  }
};

const addCart = (item) => {

  // Misma estructura de celdas (mismo orden) que Pos.js para no alterar la lógica; en móvil se muestra como tarjeta por CSS
  let tr = `<tr id="id_detalle${item.id}">
             <td data-label="Código">${item.codigo}</td>
             <td class="c-desc">${item.descripcion}-${item.laboratorio}</td>
             <td data-label="Precio b">${item.preciob}</td>
             <td data-label="Dcto">${item.dto}</td>
             <td data-label="Iva">${item.iva}</td>
             <td data-label="Precio n">${item.precion}</td>
             <td data-label="Cant">${item.cantidad}</td>
             <td data-label="Und">${item.und}</td>
             <td data-label="Conv">${item.can_und}</td>`;

  if (ConfGeneral.ManejaFechaLote == 'S') {
    tr += `
              <td data-label="F.Vto">${item.vcmto}</td>
              <td data-label="Lote">${item.lote}</td>
          `
  }

  tr += `
             <td class="c-total" data-label="Total">${item.total}</td>
             <td class="c-acc"><button class="btn btn-sm btn-outline-danger" onclick="eliminarItem(${item.id})"><i class="fa-regular fa-trash-can"></i> Quitar</button></td>
             <td class="c-acc"><button class="btn btn-sm btn-outline-primary"  onclick="editarPedido(${item.id})"><i class="fa fa-pencil"></i> Editar</button></td>
             <td class="d-none">${item.id}</td>
         </tr>`
  $("#carrito tbody").append(tr);
  disabledEnabledTercero(true);


};

const recuperarPedido = async (data) => {

  showLoadingWithTetrisLogo('Cargando Pedido...', 'ISA Software')

  try {

    nuevoPedido()

    //carga los datos del tercero
    item = data;
    $("#tipos").val(item.id_tipo_documento)
    $("#id_tercero").val(item.id_tercero);
    $("#nit").val(item.nit);
    $("#tratamiento").val(item.nombres);
    $("#razon_comercial").val(item.razon_comercial);
    $("#textDireccion").val(item.direccion)
    if (item.es_domicilio == 1) {
      $("#domicilio").removeClass("btn-outline-secondary").addClass("btn-success");
    }
    //$("#id_condicion").val(item.id_condicion_pago)
    //
    $("#info_condicion").text(item.des_condicion)
    $("#info_lista").text(item.lista_precios)
    $("#info_credito").text(formatNum(item.cupo_credito, '$'))
    $("#info_zona_venta").text(item.zona_ventas)
    $("#info_zona_descripcion").text(item.zona_descripcion)
    $("#info_email").text(item.email_fe)
    ///

    let direccionesCargadas = await cargarDirecciones(item.id_tercero);
    if (!direccionesCargadas) {
      return;
    }

    //asigno la direccion selecciona
    $("#id_tercero_dir").val(item.id_tercero_dir)
    // $("#id_tercero_dir").val(item.id_direccion);
    $('#tabs button[data-bs-target="#navs-creacion"]').tab('show')
    $('#tabs button[data-bs-target="#navs-creacion"]').attr('disabled', false)
    //carga los datos del encabezado 
    $("#id_transaccion").val(item.id_transaccion);
    $("#numero").val(item.numero)
    await actualizarTotales({
      valor_total: item.valor_total,
      iva: item.iva,
      descuento: item.descuento
    });
    await cargarPerfilTributarioDesdeTercero(item.id_tercero);
    await listarConceptosRetencion();
    restaurarRetencionesEncabezado(item);
    $("#notas").val(item.notas)
    $("#carrito tbody tr").remove();
    //carga los item
    const items = await enviarPeticion({
      link: '../shared/servicios/servicios.php',
      op: 'busqueda_temporal_det',
      id_transaccion: $("#id_transaccion").val()
    })

    if (items.length == 0) {
      dissminSwal()
      Swal.fire("Error", "No se encontraron item", "error");
      nuevoPedido()
      return;
    }

    items.forEach(async (item) => {

      let itemCart = {}
      const dataPrecio = calcularPrecioUnidad(item.valor_unitario, item.can_und, item.pcj_descuento, item.pcj_iva)

      itemCart.id = item.id_transaccion_det_tmp
      itemCart.codigo = item.codigo
      itemCart.descripcion = item.descripcion
      itemCart.laboratorio = item.laboratorio
      itemCart.preciob = formatNum(dataPrecio.preciob, '$')
      itemCart.dto = item.pcj_descuento
      itemCart.iva = item.pcj_iva
      itemCart.precion = formatNum(dataPrecio.precion, '$')
      itemCart.cantidad = item.cantidad / item.can_und
      itemCart.und = item.und
      itemCart.can_und = item.can_und

      if (ConfGeneral.ManejaFechaLote == 'S') {
        itemCart.vcmto = item.vmcto
        itemCart.lote = item.lote
      }

      itemCart.total = formatNum(Math.round(dataPrecio.precion * (item.cantidad / item.can_und)), '$')
      await addCart(itemCart)
      limpiarReferencia();
    })
    $("#filtro").focus()
    dissminSwal()

  } catch (e) {
    console.error(e)
    nuevoPedido()
  }

};

const eliminarPedido = (data) => {

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

      try {
        const resp = await enviarPeticion({
          link: '../shared/servicios/servicios.php',
          id_transaccion: data.id_transaccion,
          op: 'delete_transaccion'
        });

        if (resp.error) {
          Swal.fire("Ups", "Error al eliminar el pedido", "error")
          return;
        }

      } catch (e) {
        console.error(e)
      }

      buscarTemporales();
      nuevoPedido();
      cargarTercerosDefault()
    }
  });

};

const buscarTemporales = async () => {
  try {
    const fecha_inicial = $.trim($("#fecha_inicial").val());
    const fecha_final = $.trim($("#fecha_final").val());

    showLoadingWithTetrisLogo('Buscando información...', 'ISA Software');

    const resp = await enviarPeticion({
      op: 'busqueda_temporal',
      link: '../shared/servicios/servicios.php',
      fecha_inicial,
      fecha_final,
      nit: $("#nit_tmp").val(),
      sw: 5,
      es_credito: 0
    });

    if (resp.data.length === 0) {
      dissminSwal();

      $("#temporales").html(`
        <div class="alert alert-warning">
          <i class="fa-solid fa-triangle-exclamation"></i>
          No se encontraron resultados!
        </div>
      `);

      return;
    }

    let filas = '';

    resp.data.forEach((item, index) => {
      if (parseInt(item.es_domicilio) === 1) {
        item.numero += ` <span class="ms-2 
                                      d-inline-flex 
                                      align-items-center 
                                      justify-content-center 
                                      rounded-circle 
                                      text-success 
                                      border 
                                      border-success-subtle 
                                      bg-success-subtle"
                                style="width: 28px; height: 28px;"
                                title="Servicio a domicilio">
                            <i class="fa-solid fa-motorcycle" style="font-size: 13px;"></i>
                          </span>
                        `;
      }



      filas += `
        <tr data-index="${index}">
          <td>${item.fecha_hora ?? ''}</td>
          <td>${item.numero ?? ''}</td>
          <td>${item.nombres ?? ''}</td>
          <td>${item.razon_comercial ?? ''}</td>
          <td>${formatNum(item.descuento ?? '', '$')}</td>
          <td>${formatNum(item.iva ?? '', '$')}</td>
          <td>${formatNum(item.valor_total ?? '', '$')}</td>
          <td class="text-center">
            <div class="dropdown">
              <button
                type="button"
                class="btn btn-sm btn-outline-secondary dropdown-toggle"
                data-bs-toggle="dropdown"
                aria-expanded="false"
                title="Opciones">
                <i class="fa-solid fa-ellipsis-vertical"></i> Opciones
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li>
                  <a class="dropdown-item edit-btn" href="javascript:void(0)">
                    <i class="fa-regular fa-pen-to-square me-2 text-warning"></i>
                    Editar
                  </a>
                </li>
                <li>
                  <a class="dropdown-item other-btn" href="javascript:void(0)">
                    <i class="fa fa-print me-2 text-primary"></i>
                    Imprimir
                  </a>
                </li>
                <li>
                  <a class="dropdown-item email-btn" href="javascript:void(0)">
                    <i class="fa-solid fa-envelope me-2 text-info"></i>
                    Enviar correo
                  </a>
                </li>
                <li>
                  <a class="dropdown-item whatsapp-btn" href="javascript:void(0)">
                    <i class="fab fa-whatsapp me-2 text-success"></i>
                    Enviar por WhatsApp
                  </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <a class="dropdown-item delete-btn text-danger" href="javascript:void(0)">
                    <i class="fa-regular fa-trash-can me-2"></i>
                    Eliminar
                  </a>
                </li>
              </ul>
            </div>
          </td>
        </tr>
      `;
    });

    $("#temporales").html(`
      <div class="table-responsive">
        <table id="pedidos-temp" class="table table-sm table-striped table-hover">
          <thead class="table-primary">
            <tr>
              <th>Fecha</th>
              <th>Numero</th>
              <th>Cliente</th>
              <th>Razon comercial</th>
              <th>Dto</th>
              <th>Iva</th>
              <th>Total</th>
              <th class="text-center">opciones</th>
            </tr>
          </thead>

          <tbody>
            ${filas}
          </tbody>
        </table>
      </div>
    `);

    // Inicializar dropdowns con strategy "fixed" para que no se
    // recorten ni generen scroll dentro de .table-responsive
    $("#pedidos-temp [data-bs-toggle='dropdown']").each(function () {
      new bootstrap.Dropdown(this, {
        popperConfig: (defaultBsPopperConfig) => ({
          ...defaultBsPopperConfig,
          strategy: 'fixed'
        })
      });
    });

    // EDITAR / RECUPERAR
    $("#pedidos-temp tbody").on("click", ".edit-btn", function () {
      const index = $(this).closest("tr").data("index");
      const rowData = resp.data[index];

      recuperarPedido(rowData);
    });

    // ELIMINAR
    $("#pedidos-temp tbody").on("click", ".delete-btn", function () {
      const index = $(this).closest("tr").data("index");
      const rowData = resp.data[index];

      eliminarPedido(rowData);
    });

    // IMPRIMIR
    $("#pedidos-temp tbody").on("click", ".other-btn", function () {
      const index = $(this).closest("tr").data("index");
      const rowData = resp.data[index];

      imprimirFila(rowData);
    });

    //WHATSAPP
    $("#pedidos-temp tbody").on("click", ".whatsapp-btn", function () {
      const index = $(this).closest("tr").data("index");
      const rowData = resp.data[index];
      enviarWhatsapp(rowData.id_transaccion, 1001, rowData.celular, rowData.nombres);
    });

    //ENVIAR CORREO
    $("#pedidos-temp tbody").on("click", ".email-btn", function () {
      const index = $(this).closest("tr").data("index");
      const rowData = resp.data[index];
      enviarEmail(rowData.id_transaccion, 1001, rowData.email_fe, rowData.nombres);
    });

    dissminSwal();

  } catch (e) {
    console.error(e);

    dissminSwal();

    Swal.fire(
      "Error",
      "Ha ocurrido un error al obtener los pedidos",
      "error"
    );
  }
};

const imprimirFila = (item) => {

  let titulo = `Cotización- ${item.id_transaccion}`;
  let url = `../pdf/factura-tmp-pos.php?id=${item.id_transaccion}`;

  // Eliminar el modal si ya existe
  $("#modalDoc").remove();

  // Crear el modal y el iframe
  $("body").append(`
    <div class="modal fade" id="modalDoc" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">${titulo}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div>
              <iframe width="100%" style="height:400px; border:none;" src="${url}" frameborder="0"></iframe>
            </div>
          </div>
        </div>
      </div>
    </div>
  `);

  // Mostrar el modal 
  $("#modalDoc").modal("show");
};

const soloContado = (data) => {
  return data.filter(item => item.dias == 0)
};

const consultarMediosPgos = async () => {

  try {
    const resp = await enviarPeticion({
      link: '../models/MediosPagos.php',
      op: 'listar',
      filtro: ''
    });

    if (resp.data.length == 0) {
      return;
    }
    let p = ''
    resp.data.forEach(item => {
      p += `<option value="${item.id}" data-element='${JSON.stringify(item)}'>${item.descripcion}</option>`
    });
    $("#forma_pago").html(p)

  } catch (error) {
    console.error(error)
  }
};

const validarPago = (data) => {
  let sw = true;

  const valorRecibido = unformatNum($("#valor_recibido").val() || '0');

  if (isNaN(valorRecibido) || valorRecibido <= 0) {
    Swal.fire("Error", "Debe digitar el valor ", "warning");
    sw = false;
    return;
  }

  //valido si ya esta completo el pago 
  const totalFactura = unformatNum($("#valor_factura").text());
  const totalPago = unformatNum($("#valor_pago").text());
  const valor = valorRecibido
  const forma_pago = $("#forma_pago").val()

  if (forma_pago != 1) {

    if ((totalPago + valor) > totalFactura) {
      Swal.fire("Oops", "El valor recibido no puede ser mayor al de la factura!", "warning");
      sw = false;
      return;
    }

  }

  return sw;
};

const existePago = (tipo) => {
  let existe = false;
  $("#table-pagos tbody tr").each(function () {
    tipox = $(this).find("td").eq(0).html();

    if (tipox == tipo) {
      existe = true;
      return;
    }
  });
  return existe;
};

const addPay = () => {

  const forma = JSON.parse($("#forma_pago option:selected").attr("data-element"));

  const existe = existePago(forma.id);
  if (existe) {
    eliminarPago(forma.id);
  }

  const valido = validarPago(forma)
  const valorRecibido = unformatNum($("#valor_recibido").val() || '0');
  let valorPago = valorRecibido;
  const totalFactura = unformatNum($("#valor_factura").text());
  const forma_pago = $("#forma_pago").val()
  const totalPago = unformatNum($("#valor_pago").text());
  const faltante = totalFactura - totalPago;


  if (faltante == 0) {
    return;
  }

  if (forma_pago == 1) {
    if (valorPago > faltante) {
      valorPago = faltante
    }
  }

  if (!valido) {
    return;
  }

  const tr = `<tr data-element='${JSON.stringify(forma)}' id="id_tipo${forma.id}">
                <td>${forma.id}</td>
                <td>${forma.descripcion}</td>
                <td>${formatearMonedaLocal(valorPago)}</td>
                <td>${$("#tipo_tarjeta option:selected").text()}</td>
                <td>${$("#voucher").val()}</td>
                <td><button class="btn btn-sm btn-outline-danger" onclick="eliminarPago(${forma.id})"><i class="fa fa-trash"></i></button></td>
                <td style="display:none">${$("#tipo_tarjeta option:selected").val()}</td>
                <td style="display:none">${valorRecibido}</td>
              </tr>`;
  $("#table-pagos tbody").append(tr);
  calcularValoresPagos();
  $("#tipo_tarjeta").val('0');
  $("#voucher").val("")
  $("#valor_recibido").val("")
  $('#forma_pago').prop('selectedIndex', 0);
  $("#valor_recibido").focus()
};

const eliminarPago = (id) => {
  $("#id_tipo" + id).remove()
  calcularValoresPagos();
};

const calcularValoresPagos = () => {

  let totalPagado = 0;
  let totalE = 0;
  let totalB = 0;

  let totalERecibido = 0;
  const totalFactura = unformatNum($("#valor_factura").text());

  $("#table-pagos tbody tr").each(function () {
    const tr = $(this);
    const data = JSON.parse(tr.attr("data-element"));
    const valor = unformatNum($.trim(tr.find("td").eq(2).html()));

    if (data.naturaleza == 'E') {
      totalE += parseFloat(valor);
      totalERecibido += $.trim(tr.find("td:last").html())
    } else {
      totalB += parseFloat(valor);
    }

    totalPagado += parseFloat(valor);
  });

  const restante = totalERecibido - (totalFactura - totalB);
  const cambio = restante < 0 ? 0 : restante;
  const faltante = Math.max(0, totalFactura - totalPagado);

  $("#cambio").text(formatearMonedaLocal(cambio));
  $("#valor_pago").text(formatearMonedaLocal(totalPagado));
  $("#faltante").text(formatearMonedaLocal(faltante));

};

const enviarFacturaElectronica = async (id, sw) => {

  try {
    showLoadingWithTetrisLogo('Enviando factura...', 'ISA Software')
    const resp = await enviarDocumentoElctronico(id, 5);
    //console.log({ resp })

    if (resp.data.IsValid == 'false') {
      if (resp.data?.ErrorMessage) {
        if (Array.isArray(resp.data.ErrorMessage)) {
          const mensajes = generarListaHTML(resp.data.ErrorMessage);
          Swal.fire("Error", mensajes, "error");
        } else {
          Swal.fire("Error", resp.data.ErrorMessage, "error");
        }
      } else {
        console.error(resp.data.ErrorMessage)
      }
      return;
    }
    Swal.fire("Ok", "Documento enviado correctamente", "success")
    await listarFacturas();
  } catch (error) {
    Swal.fire("Ups", "Ha ocurrido un error al enviar el documento", "error")
    console.error(error)
  }
};

const reenviarMail = async (tipo, numero, email) => {
  Swal.fire({
    title: 'Reenviar correo',
    input: 'email',
    inputLabel: 'Dirección de correo',
    inputValue: email,
    showCancelButton: true,
    confirmButtonText: 'Enviar',
    cancelButtonText: 'Cancelar',
    inputValidator: (value) => {
      if (!value) {
        return 'Debes ingresar un correo válido';
      }
    }
  }).then(async (result) => {
    if (result.isConfirmed) {
      const nuevoEmail = result.value;
      if (!validarEmail(nuevoEmail)) {
        Swal.fire('Error', 'El email es inválido!', 'error');
        return;
      }

      // Mostrar loading
      Swal.fire({
        title: 'Enviando correo...',
        html: 'Por favor espera un momento',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      try {
        const resp = await enviarPeticion({
          link: '../models/Fe/Controllers/ReenviarCorreo.php',
          numero,
          tipo,
          nuevoEmail
        });
        Swal.close();

        if (resp.data.error) {
          Swal.fire('Error', resp.data.mensaje, 'error');
        } else {
          Swal.fire('Excelente!', resp.data.mensaje, 'success');
        }
      } catch (error) {
        // Cerrar loading en caso de error
        Swal.close();
        Swal.fire('Error', 'Ocurrió un error al enviar el correo', 'error');
        console.error('Error al reenviar correo:', error);
      }
    }
  });
};

const listarFacturas = async () => {

  try {
    const fecha_inicial = $("#fe_fecha_inicial").val();
    const fecha_final = $("#fe_fecha_final").val();

    showLoadingWithTetrisLogo('Consultando información...', 'ISA Software');

    const resp = await enviarPeticion({
      link: '../models/Pos.php',
      op: 'listar-facturas',
      fecha_inicial,
      fecha_final
    });

    dissminSwal();

    if (!resp || resp.length === 0) {
      $("#listado-facturas").html(`
        <div class="mt-3 alert alert-warning">
          <i class="fa-solid fa-triangle-exclamation"></i>
          No se encontraron resultados!
        </div>
      `);
      return;
    }

    let op_mail = ``;
    let op_whatsapp = ``;

    const filas = resp.map(item => {
      op_mail = `enviarEmail('${item.id}', 5, '${item.email || ''}', '${item.cliente || ''}')`;
      op_whatsapp = `enviarWhatsapp('${item.id}', 5, '${item.celular || ''}', '${item.cliente || ''}')`;

      let estado = '';
      let opc = '';

      const electronico = item.documento_electronico === 'S'
        ? `
          <span class="badge bg-success">
            <i class="fa-solid fa-file-circle-check"></i>
            Sí
          </span>
        `
        :
        `
          <span class="badge bg-secondary">
            <i class="fa-solid fa-file"></i>
            No
          </span>
        `;

      let opcion_electronica = '';

      if (item.documento_electronico === 'S') {
        if (item.cufe && item.cufe !== '') {
          estado = `
            <span class="badge bg-success">
              <i class="fa-solid fa-circle-check"></i>
              Enviado
            </span>
          `;
          op_mail = `reenviarMail('${item.tipo}', ${item.numero}, '${item.email || ''}')`;
        } else {
          estado = `
            <span class="badge bg-danger">
              <i class="fa-solid fa-circle-exclamation"></i>
              Pendiente
            </span>
          `;
          opcion_electronica = `
            <li>
              <a class="dropdown-item" href="javascript:void(0)" onclick="enviarFacturaElectronica(${item.id},5)">
                <i class="fa-solid fa-paper-plane me-2"></i>
                Enviar factura electrónica
              </a>
            </li>
          `;
        }

      } else {
        estado = `
          <span class="badge bg-secondary">
            <i class="fa-regular fa-circle-xmark"></i>
            No aplica
          </span>
        `;
      }

      // Menú desplegable de acciones
      opc = `
        <div class="dropdown">
          <button
            class="btn btn-sm btn-outline-secondary dropdown-toggle"
            type="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            title="Opciones">
            <i class="fa-solid fa-ellipsis-vertical"></i> Opciones
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li>
              <a class="dropdown-item" href="javascript:void(0)" onclick="opcionImpresion(${item.id})">
                <i class="fa-solid fa-print me-2 text-primary"></i>
                Imprimir
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="javascript:void(0)" onclick="${op_whatsapp}">
                <i class="fab fa-whatsapp me-2 text-success"></i>
                Enviar por WhatsApp
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="javascript:void(0)" onclick="${op_mail}">
                <i class="fa-solid fa-envelope me-2 text-info"></i>
                Enviar correo
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="javascript:void(0)" onclick="exportarXLS(${item.id})">
                <i class="fa-solid fa-file-excel me-2 text-success"></i>
                Exportar Excel - CSV
              </a>
            </li>
            ${opcion_electronica}
          </ul>
        </div>
      `;

      return `
        <tr>
          <td>${item.numero}</td>
          <td>${item.tipo}</td>
          <td>${item.fecha}</td>
          <td>${item.nit}</td>
          <td>${item.cliente} <br> <small class="text-primary">${item.razon_comercial}</small></td>
          <td class="text-end">${formatNum(item.valor_total, '$')}</td>
          <td class="text-center">
            ${electronico}
          </td>
          ${data_tipos.documento_electronico === 'S'
          ? `
              <td>
                ${item.email || ''}
              </td>
              <td class="text-center">
                ${estado}
              </td>
            `
          : ''
        }
          <td class="text-center">
            ${opc}
          </td>
        </tr>
      `;
    }).join('');

    const tabla = `
      <div class="table-responsive">
        <table class="table table-sm table-hover datos">
          <thead class="table-primary">
            <tr>
              <th>Numero</th>
              <th>Tipo</th>
              <th>Fecha</th>
              <th>Nit</th>
              <th>Cliente</th>
              <th class="text-end">Valor total</th>
              <th>Electrónica</th>
              ${data_tipos.documento_electronico === 'S' ? `
                  <th>Email</th>
                  <th>Estado</th>
                `
        : ''
      }
              <th>Opciones</th>
            </tr>
          </thead>
          <tbody>
            ${filas}
          </tbody>
        </table>
      </div>
    `;

    $("#listado-facturas").html(tabla);

    // Clave: inicializar cada dropdown con strategy "fixed"
    // para que Popper lo posicione respecto al viewport y no
    // se recorte por el overflow del .table-responsive
    $("#listado-facturas [data-bs-toggle='dropdown']").each(function () {
      new bootstrap.Dropdown(this, {
        popperConfig: (defaultBsPopperConfig) => ({
          ...defaultBsPopperConfig,
          strategy: 'fixed'
        })
      });
    });

  } catch (error) {
    console.error(error);
    dissminSwal();
    Swal.fire(
      "Error",
      "Ocurrió un error consultando las facturas",
      "error"
    );
  }
};

const formatearFecha = (fecha) => {
  if (!fecha) return '';

  const fechaObj = new Date(fecha);

  if (isNaN(fechaObj.getTime())) {
    return fecha;
  }

  const dia = String(fechaObj.getDate()).padStart(2, '0');
  const mes = String(fechaObj.getMonth() + 1).padStart(2, '0');
  const anio = fechaObj.getFullYear();

  return `${dia}-${mes}-${anio}`;
};


const obtenerDatosFactura = async (id) => {
  try {
    const respuesta = await enviarPeticion({
      link: '../shared/servicios/servicios.php',
      op: 'exportar-factura',
      id
    });

    return respuesta;
  } catch (error) {
    console.error('Error obteniendo factura:', error);
    throw error;
  }
};

const exportarXLS = (id) => {
  Swal.fire({
    title: '¿Cómo deseas exportar?',
    text: 'Selecciona el formato de exportación',
    icon: 'question',
    showCancelButton: true,
    showDenyButton: true,
    confirmButtonText: 'Excel',
    denyButtonText: 'CSV',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#198754',
    denyButtonColor: '#0d6efd',
    cancelButtonColor: '#6c757d'
  }).then((result) => {

    if (result.isConfirmed) {
      exportarExcel(id);
    }

    if (result.isDenied) {
      exportarCSV(id);
    }

  });
};

const exportarExcel = async (id) => {
  try {

    Swal.fire({
      title: 'Generando Excel...',
      text: 'Por favor espera',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    const respuesta = await obtenerDatosFactura(id);

    if (!respuesta.ok || !respuesta.data || respuesta.data.length === 0) {
      Swal.fire({
        icon: 'warning',
        title: 'Sin datos',
        text: 'No hay información para exportar.'
      });
      return;
    }

    // Formatear fecha de vencimiento
    const datos = respuesta.data.map(fila => ({
      ...fila,
      vencimiento: formatearFecha(fila.vencimiento)
    }));
    // Crear hoja
    const worksheet = XLSX.utils.json_to_sheet(datos);

    // Crear libro
    const workbook = XLSX.utils.book_new();

    // Agregar hoja
    XLSX.utils.book_append_sheet(
      workbook,
      worksheet,
      'Factura'
    );

    // Descargar
    XLSX.writeFile(
      workbook,
      `factura_${id}.xlsx`
    );

    Swal.close();

  } catch (error) {

    console.error(error);

    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'No fue posible exportar la factura a Excel.'
    });

  }
};

const exportarCSV = async (id) => {
  try {

    Swal.fire({
      title: 'Generando CSV...',
      text: 'Por favor espera',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    const respuesta = await obtenerDatosFactura(id);

    if (!respuesta.ok || !respuesta.data || respuesta.data.length === 0) {
      Swal.fire({
        icon: 'warning',
        title: 'Sin datos',
        text: 'No hay información para exportar.'
      });
      return;
    }

    // Formatear fecha de vencimiento
    const datos = respuesta.data.map(fila => ({
      ...fila,
      vencimiento: formatearFecha(fila.vencimiento)
    }));

    // Encabezados
    const encabezados = Object.keys(datos[0]);

    // Filas
    const filas = datos.map(fila => {

      return encabezados.map(campo => {

        let valor = fila[campo] ?? '';

        // Convertir a texto
        valor = String(valor);

        // Escapar comillas
        valor = valor.replace(/"/g, '""');

        // Encerrar en comillas
        return `"${valor}"`;

      }).join(';');

    });

    // Construir CSV
    const csv = [
      encabezados.join(';'),
      ...filas
    ].join('\r\n');

    // BOM para caracteres especiales
    const blob = new Blob(
      ['\uFEFF' + csv],
      {
        type: 'text/csv;charset=utf-8;'
      }
    );

    // Descargar
    const url = URL.createObjectURL(blob);

    const link = document.createElement('a');

    link.href = url;
    link.download = `factura_${id}.csv`;

    document.body.appendChild(link);
    link.click();

    document.body.removeChild(link);

    URL.revokeObjectURL(url);

    Swal.close();

  } catch (error) {

    console.error(error);

    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'No fue posible exportar la factura a CSV.'
    });

  }
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
      actions: 'd-flex gap-2', // 🔥 aquí está la solución
      confirmButton: 'btn btn-sm btn-primary',
      denyButton: 'btn btn-sm btn-success',
      cancelButton: 'btn btn-sm btn-secondary'
    }
  });

  if (!isConfirmed && !isDenied) return;

  const tipo = isConfirmed
    ? { titulo: 'Factura de venta', esCarta: false }
    : { titulo: 'Factura carta', esCarta: true };

  ImprimirDocumento(5, idItem, tipo.titulo, tipo.esCarta);
};

const validarAperturaCaja = async () => {
  try {

    //ConfModulos.ControlHoraCajaPOS
    const resp = await enviarPeticion({
      link: '../models/AperturaCierreCaja.php',
      op: 'info'
    });


    if (resp.id_apertura == 0) {

      let texto = "";

      if (permisos.abrirModuloApertura) {
        texto = `
          <i class="fas fa-cash-register fa-2x text-danger mb-3"></i>
          <h5 class="mb-3">No hay apertura de caja</h5>
          <p>
            Es necesario realizar la <strong>apertura de caja</strong>.
            Haz click 
            <a href="AperturaCierreCaja.php?redirect=1" class="fw-bold text-decoration-underline">
              aquí
            </a>
            para ir al módulo.
          </p>
        `;
      } else {
        texto = `
          <i class="fas fa-lock fa-2x text-danger mb-3"></i>
          <h5 class="mb-3">Caja no abierta</h5>
          <p>
            Es necesario realizar la <strong>apertura de caja</strong>.
            Solicita permisos para acceder al módulo de
            <strong>Apertura y Cierre de Caja</strong>.
          </p>
        `;
      }

      $("body").html(`
        <div class="container d-flex justify-content-center align-items-center vh-100">
          <div class="alert alert-danger text-center shadow-lg p-4" style="max-width:500px;">
            <h4 class="alert-heading">
              <i class="fas fa-exclamation-triangle me-2"></i>
              Atención
            </h4>
            <hr>
            ${texto}
          </div>
        </div>
      `);

      return false;
    }

    $(".container-fluid").removeClass("d-none");

    // Validar control de horas máximas de apertura
    if (ConfModulos.ControlHoraCajaPOS && ConfModulos.ControlHoraCajaPOS > 0) {
      if (resp.horas_apertura > ConfModulos.ControlHoraCajaPOS) {
        $("body").html(`
          <div class="container d-flex justify-content-center align-items-center vh-100">
            <div class="alert alert-warning text-center shadow-lg p-4" style="max-width:500px;">
              <h4 class="alert-heading">
                <i class="fas fa-hourglass-end me-2"></i>
                Tiempo Máximo Excedido
              </h4>
              <hr>
              <i class="fas fa-cash-register fa-2x text-warning mb-3"></i>
              <h5 class="mb-3">Se ha pasado su hora máxima de cierre de caja</h5>
              <p>
                La caja ha estado abierta <strong>${resp.horas_apertura.toFixed(2)} horas</strong>.<br>
                Tiempo máximo permitido: <strong>${ConfModulos.ControlHoraCajaPOS} horas</strong>.<br><br>
                Es necesario que <strong>cierres y vuelvas a abrir tu caja</strong>
                para continuar operando.
              </p>
              <a href="AperturaCierreCaja.php?redirect=1" class="btn btn-sm btn-warning mt-3">
                <i class="fas fa-door-open me-1"></i>
                Ir a Apertura y Cierre de Caja
              </a>
            </div>
          </div>
        `);
        return false;
      }
    }

    return true;

  } catch (e) {
    console.error("Error validando apertura de caja:", e);
    return false;
  }
};

const validacionVendedor = async () => {

  let mostrarPromt = false;
  let valido = true;

  if (ConfGeneral.solicitarVendedor) {

    // si es por item vededor queda en 0 y siempre pide el vendedor
    if (ConfModulos.solicitarPorItem == 'S') {
      mostrarPromt = true;
    } else if (ConfModulos.solicitarPrimerItem == 'S') {
      if ($("#vendedor").val() == '0' || $("#vendedor").val() == '') {
        mostrarPromt = true;
      } else {
        mostrarPromt = false;
      }
    }
  }

  if (mostrarPromt) {

    try {
      const codigo = prompt("Por favor digite el código de vendedor");

      if (!codigo || codigo === undefined) {
        Swal.fire("Error", "Código de vendedor no valido ", "error");
        valido = false;
        return;
      }
      const vendedor = await enviarPeticion({
        link: '../models/Pos.php',
        op: 'validar-vendedor',
        codigo
      })

      if (vendedor.length == 0) {
        Swal.fire("Error", "No se encontro el vendedor ", "error");
        valido = false;
        return;
      }
      $("#vendedor").val(codigo);

    } catch (error) {
      Swal.fire("Error", "Ocurrio un error al validar el codigo de vendedor ", "error");
      valido = false;
      return;
    }
  }

  return valido;
};

const validacionReferencia = () => {
  let refValida = true;
  //validar precio 
  if (unformatNum($("#preciob_grid").val()) == 0) {
    Swal.fire("Ups", "No se puede facturar productos con precio en 0", "warning")
    refValida = false;
    return;
  }

  //validar si el precio esta por debajo del costo
  if (!permisos.PerderPrecio) {

    const costo_und = ($("#costo_promedio").val() * $("#und_grid option:selected").val()).toFixed(0);
    const precio = (unformatNum($("#precion_grid").val()) / (($("#iva_grid").val()) / 100 + 1)).toFixed(0);

    const costo = parseFloat(costo_und);
    const precioVenta = parseFloat(precio);
    const diferencia = costo - precioVenta;

    if (costo > precioVenta) {

      Swal.fire({
        icon: "error",
        title: "Imposible continuar",
        html: `
          El precio de venta está por debajo del costo del producto.<br><br>
          <b>Costo:</b> $${formatNum(costo)}<br>
          <b>Precio de venta:</b> $${formatNum(precioVenta)}<br>
          <b>Diferencia:</b> $${formatNum(diferencia)}<br><br>
          <span class="text-danger">No tiene permisos para esta operación.</span>
        `
      });

      refValida = false;
      return;
    }
  }
  //validar stock
  const stock = Number($("#stock_real").val()) || 0;
  const cant_um = (Number($("#cantidad_grid").val()) || 0) * (Number($("#und_grid option:selected").val()) || 0);
  const tipo_producto = $("#tipo_referencia").val();

  if (tipo_producto === 'P') {
    if (cant_um > stock) {
      Swal.fire("No hay stock suficiente", "Verifique unidades comprometidas", "error");
      refValida = false;
      return;
    }
  }
  return refValida;

};

const eliminarPagoTemp = async (id_transaccion) => {
  const resp = await enviarPeticion({
    link: '../models/Pos.php',
    op: 'eliminarPago',
    id_transaccion
  })
};

const cargarUnidadesComprometidas = async (id_producto, id_bodega) => {

  try {

    const resp = await enviarPeticion({
      link: '../models/Pos.php',
      op: 'listar-comprometidos',
      id_producto,
      id_bodega
    });

    const data = resp || [];

    if (data.length === 0) {
      Swal.fire({
        icon: 'info',
        title: 'Sin unidades comprometidas',
        text: 'No existen registros para este producto en esta bodega.'
      });
      return;
    }

    let filas = data.map(item => `
      <tr>
        <td> ${item.tipo} </td>
        <td> ${item.numero} </td>
        <td> ${item.usuario} </td>
        <td>
          ${item.razon_comercial}
          <p class="text-primary mb-0">${item.tratamiento}</p>
        </td>
        <td>${item.fecha_hora}</td>
        <td class="text-center">${item.cantidad}</td>
        <td>${item.lote}</td>
        <td>${item.vmcto}</td>
      </tr>
    `).join('');

    const modalHTML = `
      <div class="modal fade" id="modalComprometidos" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
         <div class="modal-dialog modal-xl modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">
                ⚠️ Unidades comprometidas
              </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
              <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover">
                  <thead class="table-primary">
                    <tr>  
                      <th>Tipo</th>
                      <th>Numero</th>
                      <th>Usuario</th>
                      <th>Cliente</th>
                      <th>Fecha</th>
                      <th class="text-center">Cantidad</th>
                      <th>Lote</th>
                      <th>Vencimiento</th>
                    </tr>
                  </thead>
                  <tbody>
                    ${filas}
                  </tbody>
                </table>
              </div>

            </div>

          </div>
        </div>
      </div>
    `;

    // eliminar modal previo si existe
    const modalAnterior = document.getElementById('modalComprometidos');
    if (modalAnterior) modalAnterior.remove();

    // insertar modal
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    const modal = new bootstrap.Modal(document.getElementById('modalComprometidos'));
    modal.show();

    // eliminar del DOM al cerrar
    document.getElementById('modalComprometidos').addEventListener('hidden.bs.modal', function () {
      this.remove();
    });

  } catch (error) {

    console.error("Error cargando unidades comprometidas:", error);

    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'No se pudieron cargar las unidades comprometidas'
    });

  }

};

const confirmarGuardado = () => {
  return new Promise((resolve) => {
    const modalElement = document.getElementById("modalConfirmarGuardar");
    const modal = new bootstrap.Modal(modalElement, {
      backdrop: "static",
      keyboard: false
    });
    const aceptar = $("#btnAceptarConfirm");
    const cancelar = $("#btnCancelarConfirm");
    let seleccionado = "aceptar";

    const ponerEncima = () => {
      $(modalElement).css({ "z-index": 12000 });
      $(".modal-backdrop").last().css({ "z-index": 11990 });
    };

    const actualizarFoco = () => {
      if (seleccionado === "aceptar") {
        aceptar.trigger("focus");
      } else {
        cancelar.trigger("focus");
      }
    };

    const cerrar = (resultado) => {
      $(document).off("keydown.confirmar");
      modal.hide();
      resolve(resultado);
    };

    aceptar.off("click.confirmar").on("click.confirmar", () => {
      cerrar(true);
    });

    cancelar.off("click.confirmar").on("click.confirmar", () => {
      cerrar(false);
    });

    $(modalElement).off("shown.bs.modal.confirmar").on("shown.bs.modal.confirmar", () => {
      ponerEncima();
      seleccionado = "aceptar";
      setTimeout(() => {
        actualizarFoco();
      }, 100);
    });

    const teclado = (e) => {
      if (!$(modalElement).hasClass("show")) {
        return;
      }
      switch (e.key) {
        case "ArrowLeft":
        case "ArrowRight":
          seleccionado = seleccionado === "aceptar" ? "cancelar" : "aceptar";
          actualizarFoco();
          e.preventDefault();
          break;
        case "Enter":
          if (seleccionado === "aceptar") {
            aceptar.trigger("click");
          } else {
            cancelar.trigger("click");
          }
          e.preventDefault();
          break;
        case "Escape":
          cancelar.trigger("click");
          break;
      }
    };

    $(document).off("keydown.confirmar").on("keydown.confirmar", teclado);
    modal.show();
  });
};

function crearModalBusqueda() {

  // Si ya existe, destruirla primero
  if (modalBusquedaRef) {
    modalBusquedaRef.destroy();
    modalBusquedaRef = null;
  }
  const listaAlmacen = parseInt($("#lista_almacen").val()) || 0;
  const listaTercero = parseInt($("#lista_tercero").val()) || 0;
  const lista = listaTercero === 0 ? listaAlmacen : listaTercero;


  modalBusquedaRef = new ModalBusquedaProducto(
    async (rowData) => {
      if (ConfGeneral.ManejaFechaLote === "S") {
        await cargarLote(rowData);
      } else {
        await cargarItemGrid(rowData);
      }
    },
    {
      cod_barras: false,
      lista: lista,
      id_modulo: id_modulo
    }
  );

  return modalBusquedaRef;
}

$(async function () {
  permisos = await permisosUsuario(id_modulo);
  const { general, modulos } = await cargarConfiguraciones();
  ConfGeneral = general;
  ConfModulos = modulos[id_modulo];

  const validCaja = await validarAperturaCaja();
  if (!validCaja) {
    return;
  }

  fechaActual([
    ['fecha_inicial', 'fecha_final']
  ])
  const listaPrecio = (await cargarListaAlmacen())?.[0]?.lista_precios;

  if (listaPrecio) {
    $("#lista_almacen").val(listaPrecio);
  }

  await listarAlmacenes();
  await condicionesPago('C', 'id_condicion', soloContado);
  await consultarMediosPgos();
  await tiposDocumentos(5, $("#bodegas").val(), 'tipos');
  await listarConceptosRetencion();
  const okTer = await cargarTercerosDefault();
  /*
    const preferencias = await preferenciasUsuario(id_modulo);
  
    if (preferencias.SoloProductoSock == 'S') {
      soloStock = true;
    }

    if (preferencias.LimiteRegistros) {
      limite_registros = preferencias.LimiteRegistros
    }
  */


  if ($("#tipos option:selected").attr("data-element")) {
    data_tipos = JSON.parse($("#tipos option:selected").attr("data-element"));
  }
  const hoy = new Date();
  const primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
  const ultimoDia = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
  const formatoFecha = (fecha) => fecha.toISOString().split("T")[0];

  $("#fe_fecha_inicial").val(formatoFecha(primerDia));
  $("#fe_fecha_final").val(formatoFecha(ultimoDia));


  $("#domicilio").on('click', function () {
    //valido si tiene la clase btn-success que indica que el cliente tiene domicilio registrado
    if ($(this).hasClass('btn-success')) {
      $(this).removeClass('btn-success').addClass('btn-outline-secondary');

    } else {
      $(this).removeClass('btn-outline-secondary').addClass('btn-success');
    }

  })

  /**fecha y lotes */

  if (ConfGeneral) {

    btnEditPrecio = '';
    if (permisos.CambiarPrecio) {
      btnEditPrecio = `<span id="basic-default-password2" class="input-group-text cursor-pointer btn btn-sm btn-outline-primary btn-editar-precio"
                                ><i class="bx bx-pencil"></i
                              ></span>`
    }

    if (ConfGeneral.ManejaFechaLote == 'S') {


      tb_ref = `<div class="pos-producto"><div class="campo "><label>Código</label><input type="text" class="form-control form-control-sm data-grid" id="codigo_grid" readonly></div><div class="campo "><label>Precio b</label><input type="text" class="form-control form-control-sm data-grid" id="preciob_grid" readonly></div><div class="campo "><label>Dto</label><input type="text" class="form-control form-control-sm data-grid" id="dto_grid" readonly></div><div class="campo "><label>Iva</label><input type="text" class="form-control form-control-sm data-grid" id="iva_grid" readonly></div><div class="campo span2"><label>Precio n.</label><div class="input-group"><input type="text" class="form-control form-control-sm data-grid" id="precion_grid" readonly />${btnEditPrecio}</div></div><div class="campo "><label>Lote</label><input type="text" class="form-control form-control-sm data-grid" id="lote_grid" readonly></div><div class="campo span2"><label>Vcmto</label><input type="date" class="form-control form-control-sm data-grid" id="vcmto_grid" readonly></div><div class="campo span3"><label>Unidad</label><select class="form-control p-0 data-grid" id="und_grid" multiple></select></div><div class="campo "><label>Cant</label><input type="number" min="1" class="form-control form-control-sm table-primary data-grid" id="cantidad_grid"></div><div class="campo span2"><label>Total</label><input type="text" class="form-control form-control-sm data-grid" id="total_grid" readonly></div></div>`
      $("#dvReferencias").html(`
                  <table class="table table-sm" id="carrito">
                      <thead class="table-primary" >
                          <tr>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th>Precio b</th>
                            <th  class="text-center">Dcto</th>
                            <th  class="text-center">Iva</th>
                            <th>Precio n</th>
                            <th  class="text-center">Cant</th>
                            <th  class="text-center">Und</th>
                            <th  class="text-center">Conv</th>
                            <th>F.Vto</th>
                            <th>Lote</th>
                            <th>Total</th>
                            <th></th>
                            <th></th>
                          </tr>
                        </thead>
                      <tbody>
                      </tbody>
                </table>  
              `)
    } else {
      tb_ref = `<div class="pos-producto"><div class="campo "><label>Código</label><input type="text" class="form-control form-control-sm data-grid" id="codigo_grid" readonly></div><div class="campo "><label>Precio b</label><input type="text" class="form-control form-control-sm data-grid" id="preciob_grid" readonly></div><div class="campo "><label>Dto</label><input type="text" class="form-control form-control-sm data-grid" id="dto_grid" readonly></div><div class="campo "><label>Iva</label><input type="text" class="form-control form-control-sm data-grid" id="iva_grid" readonly></div><div class="campo span2"><label>Precio n.</label><div class="input-group"><input type="text" class="form-control form-control-sm data-grid" id="precion_grid" readonly />${btnEditPrecio}</div></div><div class="campo span3"><label>Unidad</label><select class="form-control p-0 data-grid" id="und_grid" multiple></select></div><div class="campo "><label>Cant</label><input type="number" min="1" class="form-control form-control-sm table-primary data-grid" id="cantidad_grid"></div><div class="campo span2"><label>Total</label><input type="text" class="form-control form-control-sm data-grid" id="total_grid" readonly></div></div>`
      $("#dvReferencias").html(`
                  <table class="table table-sm" id="carrito">
                      <thead class="table-primary" >
                          <tr>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th>Precio b</th>
                            <th  class="text-center">Dcto</th>
                            <th  class="text-center">Iva</th>
                            <th>Precio n</th>
                            <th  class="text-center">Cant</th>
                            <th  class="text-center">Und</th>
                            <th  class="text-center">Conv</th>
                            <th>Total</th>
                            <th></th>
                            <th></th>
                          </tr>
                        </thead>
                      <tbody>
                      </tbody>
                </table>  
              `)
    }
    $(".div-table-data-referencia").html(tb_ref);
  }

  $(".btn-editar-precio").click(function () {
    if ($("#id_referencia").val() > 0) {
      $("#precion_grid").prop("readonly", function (_, value) {
        return !value;  // alterna true/false
      });
      $("#precion_grid").focus().select()
    }
  });
  // valido los permisos
  if (permisos?.CambiarPrecio) {

    $("#precion_grid").focus(function () {
      $(this).val(unformatNum($(this).val())).select()
      $("#tmp_precio").val($(this).val())
    });

    $("#precion_grid").focusout(function () {

      if ($("#id_referencia").val() > 0) {

        //const precio = Number(unformatNum($(this).val())) || 0
        const precio = Number($(this).val()) || 0
        const dto = Number($("#dto_grid").val()) || 0
        const iva = Number($("#iva_grid").val()) || 0

        if (!precio) {
          $(this).val(formatNum($("#tmp_precio").val(), '$', ConfGeneral))
          return;
        }

        const pbruto = precio / ((1 - (dto / 100)) * ((iva / 100) + 1))

        $("#precio_unitario").val(pbruto)
        $("#preciob_grid").val(formatNum(Math.round(pbruto), '$', ConfGeneral))
        $(this).val(formatNum(precio, '$', ConfGeneral))

        //actualizar precio según unidad seleccionada
        const unidadPrecio = $("#und_grid option:selected").data("precio")

        if (unidadPrecio == 1) {
          itemActual.precion = precio
          itemActual.precio = pbruto
        }

        if (unidadPrecio == 2) {
          itemActual.precion2 = precio
          itemActual.precio2 = pbruto
        }

        if (unidadPrecio == 3) {
          itemActual.precion3 = precio
          itemActual.precio3 = pbruto
        }

        if (unidadPrecio == 4) {
          itemActual.precion4 = precio
          itemActual.precio4 = pbruto
        }

        $("#cantidad_grid").trigger("keyup");

      }

    });

  }

  if (ConfModulos.tipoDocumentoDefault != '') {
    $('#tipos option').filter(function () {
      return $(this).text().trim() === $.trim(ConfModulos.tipoDocumentoDefault);
    }).prop('selected', true);
  }

  $("#consultar-fe").click(function () {
    listarFacturas()
  })

  $("#nuevo").click(async function () {
    nuevoPedido();
    let ok = await cargarTercerosDefault()
    $("#carrito tbody tr").remove();
    if (ok) {
      $('#tabs button[data-bs-target="#navs-creacion"]').attr('disabled', false)
      $('#tabs button[data-bs-target="#navs-creacion"]').tab("show")
    }
  })

  $("#notas").focus(function () {
    $("#tmp").val($(this).val())
  })

  // PAGOS 

  $("#addPay").click(function () {
    addPay();
  });

  $("#forma_pago").change(function () {
    element = JSON.parse($("#forma_pago option:selected").attr("data-element"));

    $("#tipo_tarjeta").val('0').attr("disabled", true)
    $("#voucher").val('').attr("disabled", true)

    if (element.naturaleza == 'B') {
      $("#tipo_tarjeta").val('0').attr("disabled", false)
      $("#voucher").val('').attr("disabled", false)
    }
    $("#valor_recibido").focus()
  });

  $("#tipos").on("change", async function () {
    await listarConceptosRetencion();
  });

  $("#concepto_retencion").on('change', async function () {
    showLoadingWithTetrisLogo('Aplicando retenciones', 'ISA Software');
    aplicarRetencionesPorPerfil();
    //Aqui se debe actualizar el encabezado de la factura con el valor de la retencion
    const retFte = unformatNum($("#t_ret_fte").val() || '0');
    const retIca = unformatNum($("#t_ret_ica").val() || '0');
    const retIva = unformatNum($("#t_ret_iva").val() || '0');
    const idTransaccion = $("#id_transaccion").val();
    if (idTransaccion > 0) {
      await guardarRetencionesTMP(idTransaccion, retFte, retIca, retIva);
    }
    dissminSwal();
  });

  $("#valor_recibido").keyup(function (e) {
    if (e.key == 'Enter') {
      addPay();
      if (unformatNum($("#valor_pago").text()) >= unformatNum($("#valor_factura").text())) {
        $("#guardar-documento").focus();
      } else {
        $(this).focus();
      }
    }

  })

  $("#guardar-documento").click(async function (e) {
    //Se valida nuevamente el cierre de caja para evitar que el usuario haya dejado abierta la pantalla y se haya cerrado la caja posteriormente
    const validaCierre = await validarAperturaCaja();

    if (!validaCierre) {
      return;
    }

    showLoadingWithTetrisLogo('Verificando documento...', 'ISA Software');

    //validar si hay pago 
    const npagos = $("#table-pagos tbody tr").length;

    if (npagos == 0) {
      Swal.fire("Oops", "Debe agregar un pago!", "error");
      return;
    }

    if (unformatNum($("#valor_factura").text()) > unformatNum($("#valor_pago").text())) {
      Swal.fire("Oops", "No se ha completado el pago!", "error")
      return;
    }

    dissminSwal();
    //Nueva confirmacion de guardado
    const confirma = await confirmarGuardado();
    if (!confirma) {
      return;
    }
    //
    showLoadingWithTetrisLogo('Guardando documento...', 'ISA Software');
    if (ConfGeneral.solicitarVendedor == 'S') {

      if (ConfModulos.solicitarAlGuardar) {
        try {
          const codigo = prompt("Por favor digite el código de vendedor");

          if (!codigo || codigo === undefined) {
            Swal.fire("Error", "Código de vendedor no valido ", "error");
            return;
          }
          const vendedor = await enviarPeticion({
            link: '../models/Pos.php',
            op: 'validar-vendedor',
            numero: $("#numero").val(),
            tipo: $("#tipos").val(),
            codigo
          })

          if (vendedor.length == 0) {
            Swal.fire("Error", "No se encontro el vendedor ", "error");
            return;
          }
          $("#vendedor").val(codigo);

        } catch (error) {
          console.error(error)
          Swal.fire("Error", "Ocurrio un error al validar el codigo de vendedor ", "error");
          return;
        }
      }
    }
    //guardamos los pagos 
    let pagos = [];
    const numero = $("#numero").val();
    const tipo = $.trim($("#tipos option:selected").text());
    let idTransaccion = true;
    const id_transaccion = $("#id_transaccion").val()

    $("#table-pagos tbody tr").each(function () {
      tr = $(this);

      const id_medio_pago = parseInt($.trim(tr.find("td:eq(0)").html()));
      const valor = parseFloat(unformatNum($.trim(tr.find("td:eq(2)").html())));
      const tipo_tarjeta = ($.trim(tr.find("td:eq(6)").html()))
      const voucher = $.trim(tr.find("td:eq(4)").html())
      const cambio = $.trim(unformatNum($("#cambio").text(), '$'))


      pagos.push({
        id_transaccion,
        id_medio_pago,
        valor,
        tipo_tarjeta,
        voucher,
        numero,
        tipo,
        cambio
      });

    });

    try {
      const repPago = await enviarPeticion({
        link: '../models/Pos.php',
        op: 'insertarPago',
        pagos: JSON.stringify(pagos),
        numero,
        tipo,
        valor_total: unformatNum($("#t_neto_pagar").val()),
        id_transaccion
      });

      //console.log("repPago", repPago)

      if (!repPago.ok) {
        Swal.fire("Error", repPago.message, "error");
        return;
      }


      const respDoc = await enviarPeticion({
        link: '../shared/servicios/servicios.php',
        op: 'volcar_documento',
        id_transaccion,
        updateVendedor: ConfModulos.solicitarAlGuardar,
        vendedor: $("#vendedor").val()
      });
      //console.log("respDoc", respDoc)

      if (!respDoc.ok) {
        if (respDoc.productos && respDoc.productos.length > 0) {
          let tabla = `
                      <table class="table table-sm table-bordered table-striped">
                        <thead class="table-primary">
                          <tr>
                            <th>Producto</th>
                            <th>Stock</th>
                            <th>Pedido</th>
                            <th>Faltante</th>
                          </tr>
                        </thead>
                        <tbody>
                    `;
          respDoc.productos.forEach(p => {
            const faltante = p.cant_solicitada - p.stock_actual;
            tabla += `
                    <tr>
                      <td>${p.codigo} - ${p.producto}</td>
                      <td>${p.stock_actual}</td>
                      <td>${p.cant_solicitada}</td>
                      <td style="color:red;font-weight:bold">${faltante}</td>
                    </tr>
                  `;
          });

          tabla += `</tbody></table>`;

          Swal.fire({
            icon: "error",
            title: "Stock insuficiente",
            html: tabla,
            width: 800
          });

        } else {
          Swal.fire("Error", respDoc.message, "error");
        }

        eliminarPagoTemp(id_transaccion);
        return;
      }
      idTransaccion = respDoc.id;



    } catch (error) {
      console.error(error);
      dissminSwal()
      eliminarPagoTemp(id_transaccion);
      Swal.fire("Ups", "Se genero un error al guardar el documento", "error")
      return;
    }
    const respCont = await generarContabilizacion(idTransaccion);
    let txtCont = respCont.message ?? "";

    dissminSwal();
    nuevoPedido();
    cargarTercerosDefault();
    ImprimirDocumento(5, idTransaccion, 'Impresion de pedido de venta', false);

    if (permisos.FacturaElectronicaAutomatica) {
      const respDian = await enviarDocumentoElctronico(idTransaccion, 5);
    }

  });

  $("#guardar").on('click', async function () {

    if (isNaN(parseInt($("#numero").val())) || $("#numero").val() == 0) {
      Swal.fire("Error", "No se encontro un numero de documento", "warning");
      return;
    }
    $("#table-pagos tbody tr").remove();
    $("#forma_pago").val(1);
    $("#tipo_tarjeta").val('0').attr("disabled", true)
    $("#voucher").val('').attr("disabled", true);

    $("#valor_pago").text('$0');
    $("#cambio").text('$0');
    $("#faltante").val('$0');
    $("#valor_factura").text($("#t_neto_pagar").val());
    $("#modalMediosPagos").modal("show");
    setTimeout(() => {
      $("#valor_recibido").focus().select();
    }, 500);
    return;

  })

  $("#notas").on('focusout', async function () {

    try {

      const nota = $.trim($(this).val());
      let sw = true
      if ($("#id_transaccion").val() > 0) {
        sw = false
      }

      if (sw) {
        if (nota.length == 0) {
          return;
        }
      }

      const resp = await enviarPeticion({
        link: '../shared/servicios/servicios.php',
        op: 'update_encabezado_transaccion',
        id: $("#id_transaccion").val(),
        notas: $("#notas").val()
      })

      if (!resp.ok) {
        Swal.fire("Error", "Error al actualizar el registro", "error");
        return;
      }
      $("#tmp").val("");

    } catch (e) {
      console.error(e)
      $(this).val($("#tmp").val())
    }

  });

  $("#buscar-temporal").on('click', function () {
    buscarTemporales();
  })

  $('input[type="number"]').keydown(function (e) {
    // Evitar que las flechas de dirección cambien el valor
    if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
      e.preventDefault();
    }
  });

  /*$("#und_grid").on('change', function (e) {
    e.preventDefault();
    let data_precio = $("#und_grid option:selected").attr("data-precio");
    let data_comision = $("#und_grid option:selected").attr("data-comision");
    console.log("precio unidad", data_precio)
    if ($("#codigo_grid").val() != '') {
      const datosPrecio = calcularPrecioUnidad(data_precio,
        $("#und_grid option:selected").val(),
        $("#dto_grid").val(),
        $("#iva_grid").val())

      $("#preciob_grid").val(formatNum(datosPrecio.preciob, '$', ConfGeneral))
      $("#precion_grid").val(formatNum(datosPrecio.precion, '$', ConfGeneral));

      const stock = mostrarStockDisponible($("#stock_real").val(),
        $("#und_grid option:selected").val(),
        $("#und_grid option:selected").text().split("-")[0])
      $("#stock_grid").val(stock);

      const totales = calcularTotal(data_precio,
        $("#dto_grid").val(),
        $("#iva_grid").val(),
        $("#cantidad_grid").val() * $("#und_grid option:selected").val());
      $("#total_grid").val(formatNum(totales.ttotal, '$', ConfGeneral))

      $("#cantidad_grid").focus().select();
      $("#precio_unitario").val(data_precio);
      $("#comision_grid").val(data_comision);
    }

  });*/

  $("#und_grid").on('change', function (e) {
    e.preventDefault();

    const $option = $("#und_grid option:selected");

    const data_precio = $option.attr("data-precio");
    const data_comision = Number($option.attr("data-comision")) || 0;

    // Configuración de comisión del producto
    const manejaComision = $("#und_grid").data("maneja-comision");
    const calculoComision = $("#und_grid").data("calculo-comision");

    if ($("#codigo_grid").val() != '') {

      const cantidadUnidad = Number($option.val());

      // =========================
      // PRECIO
      // =========================
      const datosPrecio = calcularPrecioUnidad(
        data_precio,
        cantidadUnidad,
        $("#dto_grid").val(),
        $("#iva_grid").val()
      );

      $("#preciob_grid").val(
        formatNum(datosPrecio.preciob, '$', ConfGeneral)
      );

      $("#precion_grid").val(
        formatNum(datosPrecio.precion, '$', ConfGeneral)
      );

      // =========================
      // STOCK
      // =========================
      const stock = mostrarStockDisponible(
        $("#stock_real").val(),
        cantidadUnidad,
        $option.text().split("-")[0]
      );

      $("#stock_grid").val(stock);

      // =========================
      // TOTAL
      // =========================
      const totales = calcularTotal(
        data_precio,
        $("#dto_grid").val(),
        $("#iva_grid").val(),
        $("#cantidad_grid").val() * cantidadUnidad
      );

      $("#total_grid").val(
        formatNum(totales.ttotal, '$', ConfGeneral)
      );

      // =========================
      // PRECIO UNITARIO
      // =========================
      $("#precio_unitario").val(data_precio);

      // =========================
      // COMISIÓN
      // =========================

      let valorComision = 0;
      const $btnComision = $("#btn_comision_producto");

      // Si el producto maneja comisión
      if (manejaComision === "S") {
        $btnComision.removeClass("d-none");
        // Comisión calculada como porcentaje
        if (calculoComision === "porcentaje") {
          $btnComision
            .attr(
              "title",
              `Producto con comisión del ${data_comision}%`
            )
            .html('<i class="fa fa-percent"></i>');
          valorComision = Number(data_precio) * (data_comision / 100);
        }
        // Comisión como valor fijo
        else {
          $btnComision
            .attr(
              "title",
              `Producto con comisión de ${formatNum(data_comision, '$', ConfGeneral)}`
            )
            .html('<i class="fa fa-dollar-sign"></i>');
          valorComision = data_comision;
        }
      } else {
        $btnComision.addClass("d-none");
      }


      $("#comision_grid").val(valorComision);

      // =========================
      // ENFOQUE
      // =========================
      $("#cantidad_grid").focus().select();
    }
  });


  $("#und_grid").on('keyup', function (e) {
    if (e.key == 'Enter') {
      if ($("#codigo_grid").val() != '') {
        $("#cantidad_grid").focus().select();
      }
    }
  });

  $("#precion_grid").on('keyup', function (e) {
    if (e?.key == 'Enter') {
      $("#cantidad_grid").focus();
    }
  });
  $("#cantidad_grid").on('keyup', async function (e) {
    e.preventDefault();
    if (isNaN(parseInt($("#cantidad_grid").val())) || $("#cantidad_grid").val() == 0) {
      $("#total_grid").val(formatNum(0, '$'))
      return;
    }

    const costo = Math.round($("#costo_promedio").val())
    const precio = ($("#precio_unitario").val());
    if (ConfModulos.perderPrecio == 'N') {
      if (costo > precio) {
        Swal.fire("Error", "El precio no puede ser menor que el costo del producto", "error")
        return;
      }
    }
    const datosPrecio = calcularPrecioUnidad(precio,
      $("#und_grid option:selected").val(),
      $("#dto_grid").val(), $("#iva_grid").val());
    $("#preciob_grid").val(formatNum(datosPrecio.preciob, '$'))
    $("#precion_grid").val(formatNum(datosPrecio.precion, '$'));

    const totales = calcularTotal(datosPrecio.preciob,
      $("#dto_grid").val(),
      $("#iva_grid").val(),
      $("#cantidad_grid").val());

    $("#total_grid").val(formatNum(totales.ttotal, '$'))



    if (e.key == 'Enter') {

      //validacion del vendedor 
      const validacionVen = await validacionVendedor();

      if (!validacionVen) {
        return;
      }

      const refValida = validacionReferencia()

      if (!refValida) {
        return;
      }
      showLoadingWithTetrisLogo('Guardando item...', 'ISA Software')

      try {

        //
        const numero = $("#numero").val()
        let sw_add = true;

        if (isNaN(parseInt($("#numero").val())) || $("#numero").val() == 0) {
          const listaAlm = parseInt($("#lista_almacen").val()) || 0;
          const listaTer = parseInt($("#lista_tercero").val()) || 0;
          const lista_precios = listaTer === 0 ? listaAlm : listaTer;
          const id_tercero_dir = $("#id_tercero_dir").val();
          if (!id_tercero_dir) {
            Swal.fire("Error", "No se ha seleccionado una dirección para el tercero, asignela e intente de nuevo", "error")
            return;
          }
          //insertar el encabezado
          datosEncabezado = await enviarPeticion({
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
            lista: lista_precios,
            es_domicilio: $("#domicilio").hasClass('btn-success') ? 1 : 0
          });

          $("#id_transaccion").val(datosEncabezado.data.id);
          $("#numero").val(datosEncabezado.data.numero)
          if (datosEncabezado.error) {
            dissminSwal()
            Swal.fire("Error", "No se puedo insertar el documento", "error")
            return;
          }

          // guardar retenciones en el nuevo encabezado
          const retFteNew = unformatNum($("#t_ret_fte").val() || '0');
          const retIcaNew = unformatNum($("#t_ret_ica").val() || '0');
          const retIvaNew = unformatNum($("#t_ret_iva").val() || '0');
          if (retFteNew > 0 || retIcaNew > 0 || retIvaNew > 0) {
            await guardarRetencionesTMP(datosEncabezado.data.id, retFteNew, retIcaNew, retIvaNew);
          }

        }

        let cantidad_agregada = $("#cantidad_grid").val();
        let preciob = unformatNum($("#preciob_grid").val());
        let precion = unformatNum($("#precion_grid").val());
        let und_cargada = $.trim($("#und_grid option:selected").text().split("-")[0]).toUpperCase();
        let can_und_cargada = parseInt($("#und_grid option:selected").val());
        let vencimiento = $("#vcmto_grid").val();
        let lote = $.trim($("#lote_grid").val());
        let total = parseFloat(unformatNum($("#total_grid").val()));
        let opcion = 0;
        let idDet = 0;
        let opc = 'add_transacciones_detalle_tmp'
        //VERIFICO SI EL PRODUCTO YA FUE AGREGADO   

        if ($("#id_detalle").val() == 0) {

          $("#carrito tbody tr").each(async function () {

            const codigo = $.trim($(this).find("td").eq(0).html()).toUpperCase();

            if (ConfGeneral.ManejaFechaLote == 'S') {

              const lote = $.trim($(this).find("td").eq(10).html()).toUpperCase();
              const und_cargada_fila = $.trim($(this).find("td").eq(7).html()).toUpperCase();


              if (und_cargada == und_cargada_fila) {
                TextPromt = "Ya se encuentra agregada " + (parseInt($(this).find("td").eq(6).html())) + " " + und_cargada + " , que desea hacer? \n 1. Sumar al actual.  \n 2. Reemplazar.";
              } else {
                TextPromt = "Ya se encuentra agregada " + (parseInt($(this).find("td").eq(6).html())) + " " + und_cargada + " , que desea hacer?  \n 2. Reemplazar.";
              }

              if (codigo == $.trim($("#codigo_grid").val().toUpperCase()) &&
                lote == $.trim($("#lote_grid").val()).toUpperCase() &&
                und_cargada == und_cargada_fila) {

                sw_add = false;
                opcion = prompt(TextPromt);
                cantidad_agregada = parseInt($(this).find("td").eq(6).html());
                idDet = $(this).find("td:last").html();
                total_agregado = unformatNum($(this).find("td").eq(11).html());

                if (opcion == 1) {//sumar 

                  opc = 'update_transacciones_detalle_tmp'
                  cantidad_agregada = cantidad_agregada + parseInt($("#cantidad_grid").val());
                  und_cargada = $.trim($(this).find("td").eq(7).html()).toUpperCase();
                  can_und_cargada = $(this).find("td").eq(8).html();
                  total_agregado = cantidad_agregada * precion;
                  sw_add = true;

                  // modifico los datos en las tablas 
                  $(this).find("td").eq(6).html(cantidad_agregada)
                  $(this).find("td").eq(11).html(formatNum(total_agregado, '$'));

                } else if (opcion == 2) { // reemplazar
                  opc = 'update_transacciones_detalle_tmp'
                  sw_add = true
                  cantidad_agregada = $("#cantidad_grid").val();
                  und_cargada = $.trim($("#und_grid option:selected").text().split("-")[0]);
                  can_und_cargada = parseInt($("#und_grid option:selected").val());
                  total_agregado = cantidad_agregada * precion;
                  // // modifico los datos en las tablas 
                  $(this).find("td").eq(2).html(formatNum(preciob, '$'));
                  $(this).find("td").eq(5).html(formatNum(precion, '$'));
                  $(this).find("td").eq(6).html(cantidad_agregada);
                  $(this).find("td").eq(7).html(und_cargada);
                  $(this).find("td").eq(8).html(can_und_cargada);
                  $(this).find("td").eq(11).html(formatNum(total_agregado, '$'));
                }
              }
            } else {
              // no maneja fecha lote 
              if (codigo == $.trim($("#codigo_grid").val()).toUpperCase() &&
                und_cargada == und_cargada_fila) {
                cantidad_agregada = parseInt($(this).find("td").eq(6).html());
                idDet = $(this).find("td:last").html();
                und_cargada = $.trim($(this).find("td").eq(7).html()).toUpperCase();
                sw_add = false;
                total_agregado = unformatNum($(this).find("td").eq(9).html());

                if (und_cargada == $.trim($("#und_grid option:selected").text().split("-")[0]).toUpperCase()) {
                  TextPromt = "Ya se encuentra agregada " + (cantidad_agregada) + " " + und_cargada + " , que desea hacer? \n 1. Sumar al actual.  \n 2. Reemplazar.";
                } else {
                  TextPromt = "Ya se encuentra agregada " + (cantidad_agregada) + " " + und_cargada + " , que desea hacer?  \n 2. Reemplazar.";
                }

                opcion = prompt(TextPromt);

                if (opcion == 1) {//sumar
                  opc = 'update_transacciones_detalle_tmp'
                  cantidad_agregada = cantidad_agregada + parseInt($("#cantidad_grid").val());
                  und_cargada = $.trim($(this).find("td").eq(7).html()).toUpperCase();
                  can_und_cargada = $(this).find("td").eq(8).html();
                  sw_add = true
                  total_agregado = cantidad_agregada * precion;

                  // modifico los datos en las tablas 
                  $(this).find("td").eq(6).html(cantidad_agregada)
                  $(this).find("td").eq(9).html(formatNum(total_agregado, '$'));

                } else if (opcion == 2) { //Reemplazar
                  opc = 'update_transacciones_detalle_tmp'
                  sw_add = true
                  cantidad_agregada = $("#cantidad_grid").val();
                  und_cargada = $.trim($("#und_grid option:selected").text().split("-")[0]);
                  can_und_cargada = parseInt($("#und_grid option:selected").val());
                  total_agregado = cantidad_agregada * precion;
                  // modifico los datos en las tablas 
                  $(this).find("td").eq(2).html(formatNum(preciob, '$'));
                  $(this).find("td").eq(5).html(formatNum(precion, '$'));
                  $(this).find("td").eq(6).html(cantidad_agregada);
                  $(this).find("td").eq(7).html(und_cargada);
                  $(this).find("td").eq(8).html(can_und_cargada);
                  $(this).find("td").eq(9).html(formatNum(total_agregado, '$'));
                }
              }
            }

          });
        } else {
          opc = 'update_transacciones_detalle_tmp'
          idDet = $("#id_detalle").val();

          if (ConfGeneral.ManejaFechaLote == 'S') {
            $("#id_detalle" + $("#id_detalle").val()).find("td").eq(6).html(cantidad_agregada);
            $("#id_detalle" + $("#id_detalle").val()).find("td").eq(7).html(und_cargada);
            $("#id_detalle" + $("#id_detalle").val()).find("td").eq(8).html(can_und_cargada);
            $("#id_detalle" + $("#id_detalle").val()).find("td").eq(11).html(formatNum(total, '$'));
            $("#id_detalle" + $("#id_detalle").val()).find("td").eq(2).html(formatNum(preciob, '$'));
            $("#id_detalle" + $("#id_detalle").val()).find("td").eq(5).html(formatNum(precion, '$'));

          } else {
            $("#id_detalle" + $("#id_detalle").val()).find("td").eq(6).html(cantidad_agregada);
            $("#id_detalle" + $("#id_detalle").val()).find("td").eq(7).html(und_cargada);
            $("#id_detalle" + $("#id_detalle").val()).find("td").eq(8).html(can_und_cargada);
            $("#id_detalle" + $("#id_detalle").val()).find("td").eq(9).html(formatNum(total, '$'));
            $("#id_detalle" + $("#id_detalle").val()).find("td").eq(2).html(formatNum(preciob, '$'));
            $("#id_detalle" + $("#id_detalle").val()).find("td").eq(5).html(formatNum(precion, '$'));

          }

        }

        if (!sw_add) {
          dissminSwal()
          return;
        }

        let datosDetalle;

        /*datosDetalle = {
          link: '../shared/servicios/servicios.php',
          op: opc,
          id_transaccion: $("#id_transaccion").val(),
          id_referencia: $("#id_referencia").val(),
          cantidad: cantidad_agregada * parseInt($("#und_grid option:selected").val()),
          valor_unitario: $("#precio_unitario").val(),
          costo_unitario: $("#costo_promedio").val(),
          pcj_iva: $("#iva_grid").val(),
          pcj_descuento: $("#dto_grid").val(),
          und: und_cargada,
          can_und: parseInt(can_und_cargada),
          sw: 5,
          id_bodega: $("#bodegas").val(),
          id_pedido: 0,
          comision: $("#comision_grid").val() || 0,
          id: idDet
        }*/
        const cantidadAgregada = Number(cantidad_agregada) || 0;
        const unidadesSeleccionadas = Number($("#und_grid option:selected").val()) || 0;

        // Cantidad total de unidades
        const cantidadTotal = cantidadAgregada * unidadesSeleccionadas;

        // Comisión por unidad
        const comisionUnidad = Number($("#comision_grid").val()) || 0;

        // Comisión total
        const comisionTotal = comisionUnidad * cantidadAgregada;

        datosDetalle = {
          link: '../shared/servicios/servicios.php',
          op: opc,
          id_transaccion: $("#id_transaccion").val(),
          id_referencia: $("#id_referencia").val(),
          cantidad: cantidadTotal,
          valor_unitario: Number($("#precio_unitario").val()) || 0,
          costo_unitario: Number($("#costo_promedio").val()) || 0,
          pcj_iva: Number($("#iva_grid").val()) || 0,
          pcj_descuento: Number($("#dto_grid").val()) || 0,
          und: und_cargada,
          can_und: Number(can_und_cargada) || 0,
          sw: 5,
          id_bodega: $("#bodegas").val(),
          id_pedido: 0,
          // Comisión total
          comision: comisionTotal,
          id: idDet
        };


        if (ConfGeneral.ManejaFechaLote == 'S') {

          if ($("#tipo_referencia").val() == 'P') {
            datosDetalle.id_lote = $("#id_lote").val();
            datosDetalle.vmcto = vencimiento;
            datosDetalle.lote = lote;
          } else {
            datosDetalle.id_lote = 0
            datosDetalle.vmcto = '1900-01-01';
            datosDetalle.lote = '0';
          }
          //inserto el detalle 


        } else {
          datosDetalle.id_lote = 0
          datosDetalle.vmcto = '1900-01-01';
          datosDetalle.lote = '0';
        }

        //aqui se debe controlar la comision
        //console.log("datosDetalle", datosDetalle)
        const resDet = await enviarPeticion(datosDetalle);

        if (resDet.error) {
          dissminSwal()
          Swal.fire("Error", "No se pudo insertar el producto, por favor intente nuevamente!", "error")
          return;
        }
        let itemCart = {};

        itemCart.id = resDet.data.id
        itemCart.codigo = $("#codigo_grid").val();
        itemCart.descripcion = $("#descripcion_grid").val();
        itemCart.laboratorio = $("#laboratorio_grid").val();
        itemCart.preciob = $("#preciob_grid").val();
        itemCart.dto = $("#dto_grid").val();
        itemCart.iva = $("#iva_grid").val();
        itemCart.precion = $("#precion_grid").val();
        itemCart.cantidad = cantidad_agregada;
        itemCart.und = und_cargada;
        itemCart.can_und = can_und_cargada;

        if (ConfGeneral.ManejaFechaLote == 'S') {
          itemCart.vcmto = vencimiento
          itemCart.lote = lote
        }

        itemCart.total = formatNum(total, '$')

        if (opc == 'add_transacciones_detalle_tmp') {
          await addCart(itemCart)
        }
        limpiarReferencia()
        await actualizarTotales(resDet.data.totales[0])
        await dissminSwal();
        itemActualizar = null; // reseteo la variable global que se usa para actualizar el item del carrito cuando se edita un item ya agregado
        setTimeout(function () {
          $("#filtro").focus()
        }, 500)
      } catch (e) {
        dissminSwal()
        Swal.fire("Error", "Se genero un error interno en la aplicación", "error")
        console.error(e)
      }

    }
  });




  $("#filtro").on("keydown", async function (e) {

    if (e.key !== "Enter") {
      return;
    }
    e.preventDefault();
    if (buscando) {
      return;
    }
    const filtro = $.trim(this.value);

    if (!filtro) {
      return;
    }
    const listaAlmacen = parseInt($("#lista_almacen").val()) || 0;
    const listaTercero = parseInt($("#lista_tercero").val()) || 0;
    const lista = listaTercero === 0 ? listaAlmacen : listaTercero;

    if (!lista) {
      Swal.fire(
        "Error",
        "No se ha asignado una lista de precios para el cliente o el almacén",
        "error"
      );
      return;
    }
    // ---------------------------------------------
    // ASEGURAR QUE EXISTA EL COMPONENTE
    // ---------------------------------------------
    if (!modalBusquedaRef) {
      crearModalBusqueda();
    }
    try {
      buscando = true;
      const resp = await modalBusquedaRef.getRecords({
        data: filtro,
        cod_barras: false,
        lista: lista
      });

      const nregistros = modalBusquedaRef.getNrecords();

      if (!nregistros) {
        return;
      }
      // ---------------------------------------------
      // UN SOLO RESULTADO
      // ---------------------------------------------
      if (nregistros === 1) {
        if (ConfGeneral.ManejaFechaLote === "N") {
          await cargarItemGrid(resp[0]);
        } else {
          await cargarLote(resp[0]);
        }
        // Limpiar después de agregar
        $("#filtro").val("");

      }
      // ---------------------------------------------
      // VARIOS RESULTADOS
      // ---------------------------------------------
      else {
        modalBusquedaRef.show();
      }
    } catch (error) {
      console.error(
        "Error en búsqueda:",
        error
      );
    } finally {
      buscando = false;
    }

  });

  $("#btnBusquedaAvanzada").on("click", function () {
    // Si por alguna razón no existe la instancia,
    // la creamos
    if (!modalBusquedaRef) {
      crearModalBusqueda();
    }
    // Mostrar modal
    modalBusquedaRef.show();

    // Pasar el filtro principal al campo de descripción
    $("#busqueda-descripcion").val($("#filtro").val());

    // Enfocar el campo
    setTimeout(() => {
      $("#busqueda-descripcion").focus();
    }, 200);

  });

  $('#txtBusquedaTercero').keyup(function () {
    tercero = $.trim($(this).val())

    if (tercero.length == 0) {

      $('#tabs button[data-bs-target="#navs-creacion"]').attr('disabled', true);
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

      item = ui.item;
      if ($("#numero").val() > 0) {
        Swal.fire("Ups", "Ya hay una factura en proceso, no se puede cambiar el cliente", "info")
        return
      }
      cargarTercero(item)
      limpiarPedido()
      limpiarReferencia()
      limpiarTemporales()
    }
  });
  $("#txtBusquedaTerceroTmp").on("input", function () {
    if ($.trim($(this).val()) === "") {
      $("#nit_tmp").val("");
    }
  });
  $('#txtBusquedaTerceroTmp').autocomplete({
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
      //console.log({ ui });
      $("#nit_tmp").val(ui.item.nit)
    }
  });

  $("#txtBusquedaTercero").focus();

  $("#btn-crear-cliente").click(function () {
    // URL del PHP que quieres mostrar
    const url = "terceroBasico.php";

    // Construimos el iframe con estilos responsivos
    const iframe = `
        <iframe 
          src="${url}" 
          width="100%" 
          height="500px" 
          frameborder="0" 
          style="border:none; overflow:auto;">
        </iframe>
      `;

    // Insertamos el iframe dentro del body del modal
    $("#modalNewCliente .modal-body").html(iframe);

    // Mostramos el modal
    $("#modalNewCliente").modal('show');
  });

  $("#btn_unidades_comprometidas").click(function () {
    const id_bodega = $("#bodegas").val();
    const id_producto = $("#id_referencia").val();
    if (!id_producto || id_producto == 0) {
      Swal.fire("Error", "No se ha seleccionado un producto", "error");
      return;
    }
    if (!id_bodega && id_bodega == 0) {
      Swal.fire("Error", "No se ha seleccionado una bodega", "error");
      return;
    }
    cargarUnidadesComprometidas(id_producto, id_bodega);
  });

  $("#btn_stock_otras_bodegas").click(async function () {
    const id_bodega = $("#bodegas").val();
    const id_producto = $("#id_referencia").val();
    if (!id_producto || id_producto == 0) {
      Swal.fire("Error", "No se ha seleccionado un producto", "error");
      return;
    }
    try {
      const data = await enviarPeticion({
        link: '../models/Pos.php',
        op: 'stock-otras-bodegas',
        id_producto,
        id_bodega
      });

      const descripcion = $("#descripcion_grid").val() || '';

      if (!data || data.length === 0) {
        Swal.fire({
          icon: 'info',
          title: 'Sin stock en otras bodegas',
          text: 'No hay existencias de este producto en otras bodegas.'
        });
        return;
      }

      const filas = data.map(item => `
        <tr>
          <td>${item.bodega}</td>
          <td class="text-end fw-bold">${item.stock_total}</td>
        </tr>
      `).join('');

      const modalHTML = `
        <div class="modal fade" id="modalStockOtrasBodegas" tabindex="-1">
          <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Stock en otras bodegas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <p class="text-muted mb-2">${descripcion}</p>
                <div class="table-responsive">
                  <table class="table table-sm table-bordered table-hover">
                    <thead class="table-primary">
                      <tr>
                        <th>Bodega</th>
                        <th class="text-end">Stock</th>
                      </tr>
                    </thead>
                    <tbody>${filas}</tbody>
                  </table>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
              </div>
            </div>
          </div>
        </div>
      `;

      const anterior = document.getElementById('modalStockOtrasBodegas');
      if (anterior) anterior.remove();

      document.body.insertAdjacentHTML('beforeend', modalHTML);

      const modal = new bootstrap.Modal(document.getElementById('modalStockOtrasBodegas'));
      modal.show();

      document.getElementById('modalStockOtrasBodegas').addEventListener('hidden.bs.modal', function () {
        this.remove();
      });

    } catch (e) {
      Swal.fire("Error", "No se pudo consultar el stock en otras bodegas", "error");
    }
  });

  //domicilios 
  $("#domicilio").on('click', async function () {

    try {

      const es_domicilio = $("#domicilio").hasClass('btn-success') ? 1 : 0;

      if ($("#id_transaccion").val() > 0) {
        const resp = await enviarPeticion({
          link: '../shared/servicios/servicios.php',
          op: 'update_encabezado_transaccion',
          id: $("#id_transaccion").val(),
          es_domicilio: es_domicilio

        })

        if (!resp.ok) {
          Swal.fire("Error", "Error al actualizar el registro", "error");
          return;
        }
        $("#tmp").val("");
      }
    } catch (e) {
      console.error(e)
      $(this).val($("#tmp").val())
    }

  });

  $("#notas").focus(function () {
    $("#tmp").val($(this).val())
  })
  $("#notas").on('focusout', async function () {

    try {

      if ($("#id_transaccion").val() > 0) {
        const resp = await enviarPeticion({
          link: '../shared/servicios/servicios.php',
          op: 'update_encabezado_transaccion',
          id: $("#id_transaccion").val(),
          notas: $("#notas").val()
        })

        if (!resp.ok) {
          $(this).val($("#tmp").val())
          Swal.fire("Error", "Error al actualizar el registro", "error");
          return;
        }
        $("#tmp").val("");
      }
    } catch (e) {
      console.error(e)
      $(this).val($("#tmp").val())
    }

  });

  if (!okTer) {
    return;
  }
  dissminSwal();

});

