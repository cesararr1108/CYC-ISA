const link_ = '../models/Entradas.php';
let arrayReferencias = [];
let arrayRef = [];

const id_modulo = '40';
// configuraciones  
let carrito_enc = [];
let carrito_det = [];
let permisos = [];
let datos = [];

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
    $("#bodegas").val($("#id_bodega_session").val()).attr("disabled", true);
  } catch (e) {
    console.error(e)
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
    if (resp.data.lenght == 0) {
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


const actualizarCostoNeto = () => {
  // Obtener el costo unitario, quitando comas si las hay
  let costo_unitario = parseFloat($.trim($("#costo").val()).replace(/,/g, ''));
  const pcj_descuento = parseFloat(unformatNum($("#pcj_descuento").val()));
  const iva = isNaN(parseFloat($("#iva").val())) ? 0 : parseFloat($("#iva").val());
  const impoconsumo = parseFloat($("#impoconsumo").val())
  const ibua = parseFloat($("#ibua").val())
  const icui = parseFloat($("#icui").val())
  const cantidad = $("#cantidad").val()

  // Validar si el costo unitario es un número válido
  if (isNaN(costo_unitario) || costo_unitario <= 0) {
    // Si el costo no es válido, poner el valor en 0 y actualizar costo_neto
    $("#costo").val(0);
    $("#costo_neto").val(0);
    return;
  }
  // aplico los descuentos e impuestos 
  costo_unitario = (costo_unitario * (1 - (pcj_descuento / 100)));
  //calculo el iva 
  const valor_iva = costo_unitario * (iva / 100);
  const impuesto_saludable = ibua > 0 ? ibua : costo_unitario * icui / 100;
  costo_unitario = (costo_unitario + impuesto_saludable + impoconsumo);
  // Calcular el costo neto si ambos valores son válidos
  const costo_neto = costo_unitario + valor_iva;
  // Actualizar el campo de costo neto con 2 decimales
  $("#costo_neto").val(formatNum(costo_neto, '$', ConfGeneral));
  $("#costo_total").val(formatNum((costo_neto * cantidad), '$', ConfGeneral))
};


const agregarDetalle = () => {

  const descripcion = $("#descripcion_corta").val();
  const codigo = $("#codigo").val();
  const costo = $("#costo").val();
  const iva = $("#iva").val();
  const vmcto = $("#vmcto").val();
  const cantidad = $("#cantidad").val();
  const unidad = $("#unidad").val();
  const costo_neto = parseFloat($("#costo_neto").val());
  const costo_total = costo_neto * parseInt(cantidad);
  const lote = $.trim($("#lote").val());
}

const addReferencia = async (id_transaccion_det_tmp, detalle) => {

  arrayReferencias = [];

  const descripcion = $("#descripcion_corta").val();
  const codigo = $("#codigo").val();
  const costo = $.trim($("#costo").val()).replace(/,/g, '');
  //const costo_unitario = $("#costo_unitario").val();
  const iva = $("#iva").val();
  const vmcto = $("#vmcto").length ? $("#vmcto").val() : '';

  const can_und = parseInt($.trim($.trim($("#unidad option:selected").text()).split("-")[1]))
  const unidad = $.trim($.trim($("#unidad option:selected").text()).split("-")[0])
  const cantidad = detalle.cantidad / can_und;
  const costo_neto = unformatNum($("#costo_neto").val().replace(/,/g, ''));
  const costo_total = (costo_neto * parseInt(cantidad));
  const lote = $("#lote").length ? $("#lote").val().toUpperCase() : '';

  if (codigo.lenght == 0) {
    return;
  }

  if (isNaN((costo))) {
    Swal.fire("Error", "El costo no es valido.", "error");
    return;
  }

  if (unidad.lenght == 0) {
    Swal.fire("Error", "Debe seleccionar una unidad.", "error");
    return;
  }
  if (ConfGeneral.ManejaFechaLote == 'S') {
    if (lote.lenght == 0) {
      Swal.fire("Error", "Debe digitar el lote de la referencia.", "error");
      return;
    }
  }
  if (ConfGeneral.ManejaFechaLote == 'S') {
    if (vmcto == '') {
      Swal.fire("Error", "Fecha no valida.", "error");
      return;
    }
  }

  if (isNaN(parseFloat(cantidad)) || cantidad == 0) {
    Swal.fire("Error", "La cantidad no es valida.", "error");
    return;
  }

  arrayReferencias.push({
    codigo,
    descripcion,
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

  fila.find("td").eq(2).html(formatNum(item.costo, '$', ConfGeneral))  //costo 
  fila.find("td").eq(3).html(formatNum(item.costo_neto, '$', ConfGeneral))  //costo  neto 

  if (ConfGeneral.ManejaFechaLote == 'S') {
    fila.find("td").eq(5).html(item.vmcto) //fecha vencimiento
    fila.find("td").eq(6).html(item.lote) //lote
    fila.find("td").eq(7).html(item.cantidad) //cantidad 
    fila.find("td").eq(8).html(item.unidad) //und
    fila.find("td").eq(9).html(item.can_und) //can und
    fila.find("td").eq(10).html(formatNum(item.costo_total, '$', ConfGeneral)) //costo total 
  } else {
    // fila.find("td").eq(5).html(item.vmcto) //fecha vencimiento
    fila.find("td").eq(5).html(item.cantidad) //cantidad 
    fila.find("td").eq(6).html(item.unidad) //und
    fila.find("td").eq(7).html(item.can_und) //can und
    fila.find("td").eq(8).html(formatNum(item.costo_total, '$', ConfGeneral)) //costo total 
  }
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
  $("#tipo_precio").val(0)
  $("#precio_venta").val(0)
  $("#cantidad").val(0);
  arrayReferencias = [];
}

const limpiarTercero = () => {
  $("#id_tercero").val("");
  $("#nit").val("");
  $("#tratamiento").val("");
  $("#razon_comercial").val("");
  $("#textDireccion").val("")
  $("#id_tercero_dir").val("0")
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
  $('#bodegas').prop('selectedIndex', 0);
}

const limpiarFiltrosTemp = () => {
  $("#temporales").html("");
  $("#fecha_inicial").val("");
  $("#fecha_final").val("");
}

const nuevoPedido = async () => {
  limpiarReferencia();
  limpiarTercero();
  limpiarDatosPedido();
  $("#items_ tbody tr").remove();
  $("#txtBusquedaTercero").val("")
  limpiarFiltrosTemp();
  bloquearCampos(false)
  listarAlmacenes()
  $('#tabs button[data-bs-target="#navs-tercero"]').tab('show') // Select tab by name
  const tercero = await serviceTerceroEmpresa();

  if (tercero.length > 0) {
    const data = {
      link: '../models/Terceros.php',
      filtro: $.trim(tercero[0].nit),
      tipo_tercero: '',
      estado: 'A',
      op: 's_tercero'
    };

    const resp = await enviarPeticion(data);
    let datos = resp.data;

    await cargarTercero(datos[0])
  }

  fechaActual([
    ['fecha_inicial', 'fecha_final']
  ])
  $("#txtBusquedaTercero").focus()
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
          nuevoPedido()
          bloquearCampos(false)
        }).catch(error => {
          console.error({ error })
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
  // Recorrer los datos y agregar las filas
  arrayReferencias.forEach(item => {

    if (ConfGeneral.ManejaFechaLote == 'S') {
      tabla += `
        <tr class="det_${item.id_transaccion_det_tmp}">
          <td>${item.codigo}</td>
          <td>${item.descripcion}</td>
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
        <td>${item.descripcion}</td>
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
      case "1": costo_unitario = item.costo_unitario * item.cant_1; break;
      case "2": costo_unitario = item.costo_unitario * item.cant_2; break;
      case "3": costo_unitario = item.costo_unitario * item.cant_3; break;
      case "4": costo_unitario = item.costo_unitario * item.cant_4; break;
    }
    $("#costo").val(costo_unitario);
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


    setTimeout(function () {
      $("#costo").focus().select();
    }, 500);

    new Cleave(".format-num", {
      numeral: true,
      numeralThousandsGroupStyle: 'thousand',
      numeralDecimalMark: '.',
      numeralDecimalScale: 2,
    });
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

    /* 
     let fecha = new Date();
     fecha.setFullYear(fecha.getFullYear() + 1);
     const fechaFormateada = fecha.toISOString().split("T")[0];
     */
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

  switch (ob.und_compra) {
    case "1":
      costo_unitario = ob.costo_unitario * ob.cant_1;
      break;
    case "2":
      costo_unitario = ob.costo_unitario * ob.cant_2;
      break;
    case "3":
      costo_unitario = ob.costo_unitario * ob.cant_3;
      break;
    case "4":
      costo_unitario = ob.costo_unitario * ob.cant_4;
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

  new Cleave(".format-num", {
    numeral: true,
    numeralThousandsGroupStyle: 'thousand',
    numeralDecimalMark: '.',
    numeralDecimalScale: 2,
  });

  //await cargarListasReferenciaModal(ob.id, $("#id_transaccion").val());
}

const MostrarPedido = async (data) => {

  $("#items_ tbody tr").empty();
  $("#id_transaccion").val(data.id_transaccion);
  recalcularEncabezado();
  $("#id_tercero").val(data.id_tercero);
  $("#id_tercero_dir").val(data.id_tercero_dir)
  $("#textDireccion").val(data.direccion)
  $("#nit").val(data.nit);
  $("#tratamiento").val(data.nombres);
  $("#razon_comercial").val(data.razon_comercial);
  $("#bodegas").val(data.id_bodega);
  $("#id_tipo_documento").val(data.id_tipo_documento);
  $("#notas").val(data.notas);

  $("#total").val(formatNum(data.valor_total, '$', ConfGeneral));
  $("#numero").val(data.numero);
  $("#documento").val(data.documento)

  item = await enviarPeticion({
    op: 'busqueda_temporal_det',
    link: '../shared/servicios/servicios.php',
    id_transaccion: data.id_transaccion
  });

  if (item.length > 0) {
    let tr = ''
    item.forEach(row => {
      can_und = row.can_und;
      costo = row.costo_unitario;
      costo_ = costo * (1 - (row.pcj_descuento / 100));
      iva = costo_ * (row.pcj_iva / 100)
      impoconsumo = row.impoconsumo;
      ibua = row.ibua;
      icui = costo_ * (row.icui / 100);
      costo_neto = Math.round((parseFloat(((costo_ + parseFloat(iva)) * row.can_und)) + parseFloat(impoconsumo) + parseFloat(ibua) + parseFloat(icui)))
      cantidad = row.cantidad / row.can_und
      costo_total = Math.round(costo_neto * cantidad)

      if (ConfGeneral.ManejaFechaLote == 'S') {

        tr += ` <tr class="det_${row.id_transaccion_det_tmp}">
                  <td>${row.codigo}</td>
                  <td>${row.descripcion}</td>
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
                  <td>${row.descripcion}</td>
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
  }

}


const buscarTemporales = async () => {

  try {
    const fecha_inicial = $.trim($("#fecha_inicial").val());
    const fecha_final = $.trim($("#fecha_final").val());
    showLoadingWithTetrisLogo('Buscando información', 'ISA Software');
    const resp = await enviarPeticion({
      op: 'busqueda_temporal',
      link: '../shared/servicios/servicios.php',
      fecha_inicial,
      fecha_final,
      sw: 2
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

  } catch (e) {
    dissminSwal();
    Swal.fire("Error", "Se generó un error interno", "error");
  }
};

const consultarLote = async (id_referencia, lote) => {
  try {
    // $("input,button").attr("disabled",true)
    const resp = await enviarPeticion({
      op: 'consultar_lote',
      link: '../shared/servicios/servicios.php',
      id_referencia,
      lote
    });
    // $("input,button").attr("disabled",false)

    if (resp.length > 0) {
      lotes = resp[0];
      $("#vmcto").val(lotes.vencimiento)
      $("#lote").val(lotes.lote)
    }
    //  $("#vcmto").focus();
  } catch (error) {
    console.error({ error: 'error al consultar el lote' })
    // $("input,button").attr("disabled",false)
  }
}
const bloquearCampos = (sw) => {
  $(".bloqueado").attr("disabled", sw)
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



async function procesarCSV(contenido) {

  const lineas = contenido.split("\n");
  let msj = '';
  console.log({
    lineas
  })

  // Leer y validar cada línea
  for (let i = 0; i < lineas.length; i++) {
    const linea = lineas[i].trim();

    const columnas = linea.split(";");

    if (columnas[0] == '') {
      error = true;
      msj += '<li>Codigo invalido</li>';
    }
    if (!isNaN(parseInt(columnas[1]))) {
      error = true;
      msj += '<li>Error en la cantidad</li>';
    }
    if (!isNaN(parseFloat(columnas[2]))) {
      error = true;
      msj += '<li>Error en el costo</li>';
    }

    datos.push({
      codigo: columnas[0],
      linea: i + 1,
      cantidad: columnas[1],
      costo: columnas[2],
      errores: {
        error,
        msj
      }
    });

  } //for


  $("#modalPreviwCsv").modal("show");

}

const descargarExcel = (url, nombre = `Archivo.xlsx`) => {
  var a = document.createElement('a');
  a.href = url;
  a.download = nombre;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
}

const cargarExcel = async (file) => {

  showLoadingWithTetrisLogo('Cargando información', 'ISA Software');
  var reader = new FileReader();
  reader.onload = function (event) {
    var data = new Uint8Array(event.target.result);

    var workbook = XLSX.read(data, {
      type: 'array'
    });
    var sheetName = workbook.SheetNames[0];
    var worksheet = workbook.Sheets[sheetName];
    var jsonData = XLSX.utils.sheet_to_json(worksheet, {
      header: 1,
      raw: false // <-- fuerza conversión a texto
    });

    $('#tableContainer').empty();
    var table = $(`<table class="table table-sm table-hover table-striped" id="ref-importadas_">
                       <thead class="table-primary">
                           <tr class="text-primary">
                                <th>Código</th>
                                <th>Costo</th>
                                <th>Cantidad</th>
                                <th>Lote</th>
                                <th>Vmcto</th>                                
                            </tr>
                        </thead>
                    </table>`);

    var tbody = $('<tbody></tbody>');
    jsonData.slice(1).forEach(function (row) {

      if (row[3] == undefined) {
        row[3] = 'INICIAL';
      } else {
        row[3] = String(row[3]);
        if (row[3].length > 30) {
          row[3] = row[3].substring(0, 30);
        }
      }
      if (row[4] == undefined) {
        row[4] = '1900-01-01';
      } else {
        var fecha = new Date(row[4]);
        if (!isNaN(fecha.getTime())) {
          row[4] = fecha.toISOString().split('T')[0];
        } else {
          row[4] = '1900-01-01';
        }
      }

      arrayRef.push({
        codigo: String(row[0] || ''), // Primera columna: Código
        costo: parseFloat(row[1] || 0), // Segunda columna: Costo
        cantidad: parseInt(row[2] || 0), // Tercera columna: Cantidad
        lote: String(row[3] || 'INICIAL'), // Cuarta columna: Lote
        vmcto: String(row[4] || '1900-01-01'), // Quinta columna: Vmcto

      });
      var dataRow = $('<tr></tr>');
      row.forEach(function (cell) {
        dataRow.append('<td>' + cell + '</td>');
      });
      tbody.append(dataRow);
    });
    table.append(tbody);
    $('#tableContainer').append(table);
    dissminSwal();
  };
  reader.readAsArrayBuffer(file);
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


  } catch (e) {
    console.error(e)
  }

}


const cargarImportados = async () => {
  showLoadingWithTetrisLogo('Cargando información', 'ISA Software');
  try {
    const resp = await enviarPeticion({
      link: '../models/Entradas.php',
      op: 'cargar_importados',
      id_tercero: $("#id_tercero").val(),
      id_bodega: $("#bodegas").val(),
      id_tercero_dir: $("#id_tercero_dir").val(),
      id_condicion: $("#condicion").val(),
      id_tipo_documento: $("#tipo").val(),
      notas: $("#notas").val(),
      datos: JSON.stringify(arrayRef)
    });
    console.log({ resp });
    // Validar respuesta del backend
    const errores = resp.errores;
    const datosValidos = resp.datos_validos;
    const detInsert = resp.det_insert;

    const erroresCount = errores.length;
    const nuevosCount = detInsert.length;
    const existentesCount = datosValidos.length - nuevosCount; // si quieres separar

    // Construir tabla resumen
    const tablaHTML = `
      <table class="table">
        <thead>
          <tr>
            <th>Tipo de Registro</th>
            <th>Cantidad</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><strong>Registros Nuevos</strong></td>
            <td>${nuevosCount}</td>
          </tr>
          <tr>
            <td><strong>Registros Existentes</strong></td>
            <td>${existentesCount}</td>
          </tr>
          <tr>
            <td><strong>Registros con Error</strong></td>
            <td>${erroresCount}</td>
          </tr>
        </tbody>
      </table>
    `;

    // Si hay errores, listarlos debajo de la tabla
    let erroresHTML = "";
    if (erroresCount > 0) {
      erroresHTML = `
        <div class="alert alert-danger mt-3 text-start">
          <strong>Errores detectados:</strong>
          <ul style="max-height: 200px; overflow-y: auto; font-size: 0.9rem;">
            ${errores.map(err => `<li>${err}</li>`).join('')}
          </ul>
        </div>
      `;
    }

    Swal.fire({
      title: resp.message ?? "Resultado de la importación",
      html: tablaHTML + erroresHTML,
      icon: erroresCount > 0 ? 'warning' : 'success',
      confirmButtonText: 'Aceptar'
    });
    limpiarImport();
    nuevoPedido();
  } catch (e) {
    console.error(e);
    Swal.fire('Error interno', 'Se ha presentado un error, valide e intente nuevamente', 'error');
  }
};
const limpiarImport = () => {
  $('#tableContainer').empty();
  arrayRef = [];
  $('#Upload').val('');
}

const cargarTercero = async (item) => {
  await cargarDirecciones(item.id);

  $("#id_tercero").val(item.id);
  $("#nit").val(item.nit);
  $("#tratamiento").val(item.tratamiento);
  $("#razon_comercial").val(item.razon_comercial);
  $("#textDireccion").val(item.direccion)
  $("#id_tercero_dir").val(item.id_direccion);
  $("#condicion").val(item.id_condicion_pago_prov)
  $('#tabs button[data-bs-target="#navs-creacion"]').attr('disabled', false)
}

$(async function () {
  const tercero = await serviceTerceroEmpresa();

  if (tercero.length > 0) {
    const data = {
      link: '../models/Terceros.php',
      filtro: $.trim(tercero[0].nit),
      tipo_tercero: '',
      estado: 'A',
      op: 's_tercero'
    };

    const resp = await enviarPeticion(data);
    let datos = resp.data;

    await cargarTercero(datos[0])
  }

  fechaActual([
    ['fecha_inicial', 'fecha_final']
  ])

  await listarAlmacenes();
  await listarTipoDocumentos(2);
  await condicionesPago('P', 'condicion');
  permisos = await permisosUsuario(id_modulo)
  const { general, modulos } = await cargarConfiguraciones();
  ConfGeneral = general;
  ConfModulos = modulos[id_modulo];


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


  $("#notas").focusout(function () {
    const nota = $.trim($(this).val());
    if ($("#id_transaccion").val() > 0) {
      actualizarNotas($("#id_transaccion").val())
    }
  })




  $("#btnGuardar").click(async function () {

    try {
      showLoadingWithTetrisLogo('Guardando documento', 'ISA Software');

      if (isNaN(parseInt($("#id_transaccion").val())) || $("#id_transaccion").val() == 0) {
        Swal.fire("Error", "No se detecto un documento en proceso", "error")
        return;
      }

      if (ConfGeneral.ManejaFechaLote == 'S') {
        //asigno los lotes  <i class="fa-regular fa-floppy-disk"></i>
        const lotes = await enviarPeticion({
          link: '../shared/servicios/servicios.php',
          op: 'asignar_lotes',
          id_transaccion: $("#id_transaccion").val()
        });

        if (!lotes.ok) {
          Swal.fire("Error", "No se pudieron asignar los lotes", "errpr");
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
      } console.log(resp)

      nuevoPedido();
      let formato = 'C';
      if (ConfModulos.FormatoImpresionCompras) {
        if (ConfModulos.FormatoImpresionCompras === 'T') {
          formato = 'T';
        }
      }
      ImprimirDocumento(
        1,
        resp.id,
        'Ingresos de mercancias',
        false,
        '',
        formato
      );
      dissminSwal()
    } catch (e) {
      console.error(e)
      Swal.fire("Error", "Ha ocurrido un error interno en la aplicación", "error")
    }

  })

  $("#lote").focusout(function () {
    const lote = $.trim($(this).val());
    if (lote.length > 0) {
      consultarLote($("#id_referencia").val(), $("#lote").val());
    }
  });

  $("#vmcto").focusout(function () {
    vmcto = $.trim($(this).val());
    anio = vmcto.split("-")[0]

    if (parseInt(anio) < 2024) {
      $(this).val('')
      return;
    }

  })

  $("#btnLimpiar").click(function () {
    nuevoPedido();
  });

  $("#buscar-temporal").click(function () {
    buscarTemporales();
  })

  $(".calculo-costo").on('input', function () {
    actualizarCostoNeto();
  })



  const inputs = document.querySelectorAll('button[tabindex],input[tabindex],select[tabindex]');

  inputs.forEach(input => {
    input.addEventListener('keydown', function (event) {

      const currentIndex = Array.from(inputs).indexOf(input);
      const currentInputId = input.id;

      if (event.key === 'Enter') {
        // Prevenir el comportamiento predeterminado (enviar formulario)
        // event.preventDefault();
        // Verificar si no es el último campo con tabindex

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

  var today = new Date().toISOString().split('T')[0];
  // $('#vmcto').val(today);
  $('#costo').val(0);
  const allowDecimals = ConfGeneral.NumeroDecimales > 0;

  new Cleave('.format-num', {
    numeral: true,
    numeralThousandsGroupStyle: 'thousand',
    numeralDecimalMark: allowDecimals ? '.' : '',
    numeralDecimalScale: allowDecimals ? ConfGeneral.NumeroDecimales : 0,
  });

  //$('#tabs button[data-bs-target="#navs-creacion"]').attr('disabled',false)
  $('#txtBusquedaTercero').keyup(function () {
    const tercero = $.trim($(this).val())

    if (tercero.length == 0) {

      //$('#tabs button[data-bs-target="#navs-creacion"]').attr('disabled',true);
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
      await cargarDirecciones(item.id);


      $("#id_tercero").val(item.id);
      $("#nit").val(item.nit);
      $("#tratamiento").val(item.tratamiento);
      $("#razon_comercial").val(item.razon_comercial);
      $("#textDireccion").val(item.direccion)
      $("#id_tercero_dir").val(item.id_direccion);
      $("#condicion").val(item.id_condicion_prov)

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

  $('#li_item').mousedown(function (e) {

    if ($("#id_tercero").val() == '' || $("#id_tercero").val() == '0') {
      //    #tabs button[data-bs-target="#navs-creacion"]
      var trigger = document.querySelector('#tabs button[data-bs-target="#navs-tercero"]');
      var tab = new bootstrap.Tab(trigger);
      tab.show();
      $('#tabs button[data-bs-target="#navs-tercero"]').trigger("click");
      Swal.fire("Ups", "Debe cargar un tercero", "info");;
      return;
    }
  })

  //actualiza el costo neto 

  //Evento boton agregar referencia

  const modalBusquedaRef = new ModalBusquedaProducto((rowData) => {
    cargarReferencia(rowData);
  }, { check_stock: false });


  $("#filtro").on('keyup', async function (e) {
    const filtro = $.trim($(this).val())
    if (filtro.length == 0) { return; }
    if (e.key == 'Enter') {
      const resp = await modalBusquedaRef.getRecords({ data: filtro, cod_barras: $("#cod_barras").is(":checked") });
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
      const lote_ = $.trim($(this).val())

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
        sw: 2,
        id_tercero_dir: $("#id_tercero_dir").val(),
        id_condicion: $("#condicion").val()
      }

      let dataDetalle = formatearArrayRequest($(this).serializeArray());

      showLoadingWithTetrisLogo('Guardando ', 'ISA Software');

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
            if (codigo == $.trim($("#codigo").val()).toUpperCase()
            ) {
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

              if (opcion == 1) {//sumar
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

      }//

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
      dataDetalle.sw = 2;
      // dataDetalle.id               = $("#id_detalle").val()

      respDet = await enviarPeticion(dataDetalle);

      if (respDet.error) {
        Swal.fire("Error", respDet.message, "error");
        return;
      }

      const id_ = $("#id_detalle").val() > 0 ? $("#id_detalle").val() : respDet.data.id



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

  $("#btn-csv").click(function () {
    const id_tercero = $.trim($("#id_tercero").val())
    const id_tercero_dir = $("#id_tercero_dir").val();

    if (id_tercero == '' || id_tercero == '0') {
      Swal.fire("Oops", "Debe seleccionar un tercero", "error")
      return;
    }
    console.log({
      id_tercero
    })
    $("#modalCsv").modal("show");
  });

  $("#subir").click(function () {
    let input = document.getElementById("csv");

    if (!input || input.files.length === 0) {
      Swal.fire("Advertencia!", "Seleccione un archivo primero", "warning");
      return;
    }

    let file = input.files[0];
    cargarExcel(file);
  });

  $('#btnUp').click(function () {
    if (arrayRef.length > 0) {
      cargarImportados();
    } else {
      Swal.fire('No hay datos que importar', 'Por favor carque un archivo válido.', 'warning');
    }
  });
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

    ///  $("#id_lote").val($(this).data('id'));
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

  $("#txtBusquedaTercero").focus()
  dissminSwal();

})
