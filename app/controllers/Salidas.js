const id_modulo = '19';
let carrito_enc = [];
let carrito_det = [];
let permisos = [];


const disabledEnabledTercero = (sw) => {
  $("#nit").attr("disabled", sw);
  $("#id_tercero_dir").attr("disabled", sw);
  $("#tipos").attr("disabled", sw);
  $("#id_condicion").attr("disabled", sw)
  $("#bodegas").attr("disabled", sw)
  $("#notas").attr("disabled", sw)
}

const validarTercero = (item) => {
  //
  let sw = true;
  let msj;
  //bloqueo


  if (!permisos.IgnorarBloqueoTercero) {
    if (item.estado != 'A') {
      msj = 'El cliente se encuentra bloqueao'
      sw = false;
      return;
    }
  }

  return {
    sw,
    msj
  };
}

const cargarTercero = async (item) => {
  const valido = validarTercero(item);

  if (!valido.sw) {
    Swal.fire("Ups", valido.msj, "warning")
    return;
  }
  await cargarDirecciones(item.id);
  $("#id_tercero").val(item.id);
  $("#nit").val(item.nit);
  $("#tratamiento").val(item.tratamiento);
  $("#razon_comercial").val(item.razon_comercial);
  $("#textDireccion").val(item.direccion)
  $("#info_condicion").text('CONTADO')
  $("#info_lista").text(item.lista_precios)
  $("#info_credito").text(formatNum(item.cupo_credito, '$'))
  $("#info_zona_venta").text(item.codigo_zona)
  $("#info_zona_descripcion").text(item.zona_descripcion)
  $("#info_email").text(item.email_fe)
  $("#info_controlados").text($.trim(item.controlados))
  $("#info_controlados").text($.trim(item.controlados))
  $("#info_institucional").text($.trim(item.institucionales))
  $('#tabs button[data-bs-target="#navs-creacion"]').attr('disabled', false)
  $('#tabs button[data-bs-target="#navs-creacion"]').tab("show")
  setTimeout(() => {
    $("#filtro").focus();
  }, 500);

}

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

const limpiarPedido = () => {
  $("#numero").val('')
  $("#t_bruto").val(0)
  $("#t_dcto").val(0)
  $("#t_iva").val(0)
  $("#t_total").val(0)
  $("#id_transaccion").val("0")
}

const limpiarTercero = () => {
  $("#id_tercero").val("");
  $("#nit").val("");
  $("#tratamiento").val("");
  $("#razon_comercial").val("");
  $("#id_tercero_dir").html("");
  $("#info_condicion").text('')
  $("#info_lista").text('')
  $("#info_credito").text('')
  $("#info_zona_venta").text('')
  $("#info_email").text('')
  $("#txtBusquedaTercero").val('')
  $("#info_zona_descripcion").text('')
  $("#info_institucional").text('')
  $("#info_controlados").text('')
}

const limpiarReferencia = () => {
  $("#codigo_grid").val('')
  $("#laboratorio_grid").val('')
  $("#descripcion_grid").val('')
  $("#stock_grid").val('')
  $("#preciob_grid").val('');
  $("#dto_grid").val('');
  $("#iva_grid").val('');
  $("#precion_grid").val('');
  if (ConfGeneral.ManejaFechaLote == 'S') {
    $("#lote_grid").val('');
    $("#vcmto_grid").val('')
  }
  $("#cantidad_grid").val('')
  $("#und_grid").html('')
  $("#total_grid").val('')
  $("#stock_real").val(0)
  $("#id_lote").val(0)
  $("#precio_unitario").val(0)
  $("#id_referencia").val(0)
  $("#costo_promedio").val(0)
  $("#id_detalle").val(0)
  $("#filtro").val('');
  $("#id_detalle").val(0)

}

const limpiarTemporales = () => {
  $("#temporales").html('')
  $("#fecha_inicial").val('')
  $("#fecha_final").val('')
}

const nuevoPedido = async () => {
  limpiarPedido()
  limpiarReferencia()
  limpiarTercero()
  limpiarTemporales()
  $("#carrito tbody tr").remove()
  $("#modalMediosPagos").modal("hide");
  $("#table-pagos tbody tr").remove();
  $("#valor_factura").val('$0');
  $("#valor_pago").val('$0');
  $("#cambio").val('$0');
  $("#valor_recibido").val('$0');
  $("#forma_pago").val(1);
  $("#valor_pago").html('$0')
  listarAlmacenes();
  disabledEnabledTercero(false);
  fechaActual([['fecha_inicial', 'fecha_final']])
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
    cargarTercero(datos[0])
  }
  $('#tabs button[data-bs-target="#navs-tercero"]').tab("show")
}

const cargarItemGrid = (data) => {

  $("#id_referencia").val(data.id)
  $("#descripcion_grid").val(data.descripcion);
  $("#laboratorio_grid").val(data.laboratorio);
  const stock_und = data.total_stock > 0 ? parseInt(data.total_stock / data.can_und) + ` ${data.des_und_comp} y ` + (data.total_stock % data.can_und) * data.can_und : 0;
  $("#stock_grid").val(stock_und);

  $("#codigo_grid").val(data.codigo);
  $("#precio_unitario").val(data.promedio);
  $("#dto_grid").val(data.dcto);
  $("#iva_grid").val(data.porcentaje_iva);
  $("#stock_real").val(data.total_stock);
  const costo = data.promedio;
  let unds = [];

  if (data.cant_1 > 0) {
    unds.push([data.und_1, data.cant_1])
  }
  if (data.cant_2 > 0) {
    unds.push([data.und_2, data.cant_2])
  }
  if (data.cant_3 > 0) {
    unds.push([data.und_3, data.cant_3])
  }
  if (data.cant_4 > 0) {
    unds.push([data.und_4, data.cant_4])
  }
  let op = ''
  unds.forEach((und, idx) => {
    let selected = ''
    if (idx == 0) {
      selected = 'selected'
    }
    op += `<option ${selected} value=${und[1]}>${$.trim(und[0])}-${und[1]}</option>`
  })
  $("#und_grid").html(op);

  const datosPrecio = calcularPrecioUnidad(costo, $("#und_grid option:selected").val(), data.dcto, data.porcentaje_iva)
  $("#preciob_grid").val(formatNum(costo, '$'))
  coston = Math.round((costo * (data.porcentaje_iva) / 100 + 1))
  $("#precion_grid").val(formatNum(coston, '$'))

  const cant_select = $("#und_grid option:selected").val();
  const disponible = parseInt($("#stock_real").val() / cant_select);

  $("#cantidad_grid").prop("max", disponible);


  if (ConfGeneral.ManejaFechaLote == 'S') {
    $("#vcmto_grid").val(data.vencimiento)
    $("#id_lote").val(data.id_lote)
    $("#lote_grid").val(data.lote);
  }

  $("#costo_promedio").val(data.promedio);

  $("#result-lotes").html('');
  $("#und_grid").trigger("click")
  setTimeout(() => {
    $("#cantidad_grid").focus().select();
  }, 400);

}

const cargarItemGridLote = (ob) => {
  const element = JSON.parse($(ob).attr("data-element"));
  cargarItemGrid(element)
  $("#modalFechaLotes").modal("hide");

}

const cargarLote = async (data) => {
  console.log({ data })
  try {
    const resp = await enviarPeticion({
      link: '../shared/servicios/servicios.php',
      op: 'cargar-lotes',
      id_referencia: data.id,
      bodega: $("#bodegas").val()
    });
    console.log({ resp })
    if (resp.length == 0) {
      Swal.fire("Ups", "No se encontraron lotes disponibles", "error")
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

    tb = `<table class="table table-sm data-lotes">
           <thead>
             <tr>
               <th>Lote</th>
               <th>Vencimiento</th>
               <th>Stock</th>
             </tr>
           </thead>
           <tbody>
           `
    let tab = 200;
    resp.forEach(item => {

      data.id_lote = item.id_lote;
      data.lote = item.lote;
      data.vencimiento = item.vencimiento,
        data.total_stock = item.stock

      tb += `<tr tabindex="${tab}" onclick="cargarItemGridLote(this)" data-element='${JSON.stringify(data)}' >
               <td>${item.lote}</td>
               <td>${item.vencimiento}</td>
               <td>${item.stock}</td>
             </tr>`
      tab++;
    });

    tb += `</tbody>
       </table>`

    $("#result-lotes").html(tb)

    $(".data-lotes table tbody tr:first").focus();

  } catch (e) {
    console.error(e)
    Swal.fire("Error", "Error al cargar los lotes", "error")
  }

}

const actualizarTotales = (totales) => {
  console.error({
    totales
  })
  $("#t_bruto").val(formatNum(parseFloat(totales.valor_total) - parseFloat(totales.iva) + parseFloat(totales.descuento), '$'));
  $("#t_dcto").val(formatNum(totales.descuento, '$'));
  $("#t_iva").val(formatNum(totales.iva, '$'));
  $("#t_total").val(formatNum(totales.valor_total, '$'));
}

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
    showLoadingWithTetrisLogo('Cargando información', 'ISA Software')
    const resp = await enviarPeticion({
      link: '../shared/servicios/servicios.php',
      op: 'detalle_transacciones_tmp',
      id
    });

    if (resp.length == 0) {
      Swal.fire("Error", "No se encontraron items", "error")
      return;
    }

    const item = resp[0];

    $("#id_referencia").val(item.id)
    $("#descripcion_grid").val(item.descripcion)
    $("#laboratorio_grid").val(item.laboratorio)
    $("#codigo_grid").val(item.codigo)
    $("#precio_unitario").val(item.valor_unitario)
    $("#costo_promedio").val(item.costo_unitario)
    $("#stock_real").val(item.total_stock)
    $("#id_detalle").val(item.id_detalle)
    if (ConfGeneral.ManejaFechaLote == 'S') {
      $("#id_lote").val(item.id_lote)

    }

    const datosPrecio = calcularPrecioUnidad(item.valor_unitario, item.can_und, item.pcj_descuento, item.iva)

    $("#preciob_grid").val(formatNum(datosPrecio.preciob, '$'));
    $("#dto_grid").val(item.pcj_descuento)
    $("#iva_grid").val(item.iva)
    $("#precion_grid").val(formatNum(datosPrecio.precion, '$'));
    if (ConfGeneral.ManejaFechaLote == 'S') {
      $("#lote_grid").val(item.lote)
      $("#vcmto_grid").val(item.vencimiento);
    }

    $("#id_transaccion").val(item.id_transaccion)
    let unds = [];

    if (item.cant_1 > 0) {
      unds.push([item.und_1, item.cant_1])
    }
    if (item.cant_2 > 0) {
      unds.push([item.und_2, item.cant_2])
    }
    if (item.cant_3 > 0) {
      unds.push([item.und_3, item.cant_3])
    }
    if (item.cant_4 > 0) {
      unds.push([item.und_4, item.cant_4])
    }
    let op = ''
    unds.forEach((und, idx) => {
      let selected = ''
      if (idx == 0) {
        selected = 'selected'
      }
      op += `<option ${selected} value=${und[1]}>${$.trim(und[0])}-${und[1]}</option>`
    })
    $("#und_grid").html(op);
    $("#und_grid").val(item.can_und);
    $("#cantidad_grid").val(item.cantidad / item.can_und)
    const total = datosPrecio.precion * (item.cantidad / item.can_und)
    $("#total_grid").val(formatNum(total, '$'));

    const stock = mostrarStockDisponible($("#stock_real").val(),
      $("#und_grid option:selected").val(),
      $("#und_grid option:selected").text().split("-")[0])
    $("#stock_grid").val(stock);

    dissminSwal()

  } catch (e) {
    console.error(e)
    dissminSwal()
    Swal.fire("Error", "Ha ocurrido un error al intentar obtener los datos!", "error")
  }

};

const addCart = (item) => {

  let tr = `<tr id="id_detalle${item.id}">
             <td>${item.codigo}</td>
             <td width="30%">${item.descripcion}-${item.laboratorio}</td>
             <td>${item.preciob}</td>
             <td  class="text-center">${item.dto}</td>
             <td  class="text-center">${item.iva}</td>
             <td>${item.precion}</td>
             <td  class="text-center">${item.cantidad}</td>
             <td  class="text-center">${item.und}</td>
             <td>${item.can_und}</td>`;

  if (ConfGeneral.ManejaFechaLote == 'S') {
    tr += `
              <td>${item.vcmto}</td>
              <td>${item.lote}</td>
          `
  }

  tr += `
             <td>${item.total}</td>
             <td><button class="btn btn-sm btn-light border border-1 border-danger" onclick="eliminarItem(${item.id})"><i class="fa-regular fa-trash-can text-danger"></i></button></td>
             <td><button class="btn btn-sm btn-light border border-1 border-primary"  onclick="editarPedido(${item.id})"><i class="fa fa-pencil text-primary "></i></button></td>  
             <td class="d-none">${item.id}</td>
         </tr>`
  $("#carrito tbody").append(tr);
  disabledEnabledTercero(true);


}

const recuperarPedido = async (data) => {
  showLoadingWithTetrisLogo('Cargando información', 'ISA Software')
  try {
    nuevoPedido()
    //carga los datos del tercero
    item = data;

    $("#id_tercero").val(item.id_tercero);
    $("#nit").val(item.nit);
    $("#tratamiento").val(item.nombres);
    $("#razon_comercial").val(item.razon_comercial);
    $("#textDireccion").val(item.direccion)
    $("#info_condicion").text(item.des_condicion)
    $("#info_lista").text(item.lista_precios)
    $("#info_credito").text(formatNum(item.cupo_credito, '$'))
    $("#info_zona_venta").text(item.zona_ventas)
    $("#info_zona_descripcion").text(item.zona_descripcion)
    $("#info_email").text(item.email_fe)
    await cargarDirecciones(item.id_tercero);
    //asigno la direccion selecciona
    $("#id_tercero_dir").val(item.id_tercero_dir)
    $('#tabs button[data-bs-target="#navs-creacion"]').tab('show')
    $('#tabs button[data-bs-target="#navs-creacion"]').attr('disabled', false)
    //carga los datos del encabezado 
    $("#id_transaccion").val(item.id_transaccion);
    $("#numero").val(item.numero)
    actualizarTotales({
      valor_total: item.valor_total,
      iva: item.iva,
      descuento: item.descuento
    });
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

}

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
    }
  });
};

const buscarTemporales = async () => {
  try {
    const fecha_inicial = $.trim($("#fecha_inicial").val());
    const fecha_final = $.trim($("#fecha_final").val());

    showLoadingWithTetrisLogo('Consultando temporales', 'ISA Software')
    const resp = await enviarPeticion({
      op: 'busqueda_temporal',
      link: '../shared/servicios/servicios.php',
      fecha_inicial,
      fecha_final,
      nit: $("#nit_tmp").val(),
      sw: 6
    });

    if (resp.data.length == 0) {
      dissminSwal()
      $("#temporales").html(`
           <div class="alert alert-warning"><i class="fa-solid fa-triangle-exclamation"></i> No se encontraron resultados!</div>
         `);
      return;
    }

    $("#temporales").html(`
         <table id="pedidos-temp" class="table table-sm table-striped table-hover">
            <thead class="table-primary"></thead>
            <tbody></tbody>
         </table>
     `)

    createDataTable(
      'pedidos-temp',
      resp.data,
      ['fecha_hora', 'numero', 'nombres', 'razon_comercial', 'descuento', 'iva', 'valor_total'],
      ['Fecha', 'Numero', 'Cliente', 'Razon comercial', 'Dto', 'Iva', 'Total'],
      recuperarPedido,
      eliminarPedido,
      false,
      '',
      '',
      false
    )
    dissminSwal()
  } catch (e) {
    console.error(e)
    dissminSwal()
    Swal.fire("Error", "Ha ocurrido un error al obtener los pedidos", "error")
  }

};

const soloContado = (data) => {
  return data.filter(item => item.dias == 0)
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
  if (!ConfGeneral.perderPrecio) {
    const costo_und = ($("#costo_promedio").val() * $("#und_grid option:selected").val()).toFixed(0);
    const precio = (unformatNum($("#precion_grid").val()) / (($("#iva_gird").val()) / 100 + 1)).toFixed(0)
    if (costo_und > precio) {
      Swal.fire("Ups", "El precio de venta esta por debajo del costo del producto", "error")
      refValida = false;
      return;
    }
  }
  //validar stock
  stock = $("#stock_real").val()
  cant_um = $("#cantidad_grid").val() * $("#und_grid option:selected").val()

  if (cant_um > stock) {
    Swal.fire("Error", "No hay stock suficiente", "error")
    refValida = false;
    return;
  }

  return refValida;

};


$(async function () {
  fechaActual([['fecha_inicial', 'fecha_final']])
  await listarAlmacenes();
  await condicionesPago('', 'id_condicion');
  await tiposDocumentos(6, $("#bodegas").val(), 'tipos');
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
    cargarTercero(datos[0])
  }
  const {
    general,
    modulos
  } = await cargarConfiguraciones();
  ConfGeneral = general;
  ConfModulos = modulos[id_modulo];
  const preferencias = await preferenciasUsuario(id_modulo);

  /**fecha y lotes */

  if (ConfGeneral) {

    if (ConfGeneral.ManejaFechaLote == 'S') {
      tb_ref = `
                <table  width="100%" class=" table-data-referencia"  cellpadding="2" cellspacing="0" >
                  <thead>
                    <tr>
                      <th><b>Código</b></th>
                      <th><b>Costo b</b></th>
                      <th><b>Dto</b></th>
                      <th><b>Iva</b></th>
                      <th><b>Costo n.</b></th>
                      <th><b>Lote</b></th> 
                      <th><b>Vcmto</b></th>
                      <th rowspan="2"> <select class="form-control p-0 data-grid" id="und_grid" style="height: 60px;width: 120px;"  multiple ></select></th>
                      <th><b>Cant</b></th>
                      <th><b>Total</b></th>
                    </tr>
                    <tr>
                      <td><input type="text" size="5"  class="form-control form-control-sm data-grid " id="codigo_grid" readonly></td>
                      <td><input type="text" class="form-control form-control-sm data-grid" id="preciob_grid" readonly></td>
                      <td><input type="text" class="form-control form-control-sm data-grid" id="dto_grid" size="4" readonly></td>
                      <td><input type="text" class="form-control form-control-sm data-grid" id="iva_grid" size="4" readonly></td>
                      <td><input type="text" class="form-control form-control-sm data-grid" id="precion_grid" readonly></td>
                      <td><input type="text" class="form-control form-control-sm data-grid" id="lote_grid" readonly></td>
                      <td><input type="date" size="6" class="form-control form-control-sm data-grid" id="vcmto_grid" readonly></td>
                      <td><input type="number" min="1"  class="form-control form-control-sm table-primary"data-grid id="cantidad_grid" ></td>
                      <td><input type="text" class="form-control form-control-sm data-grid" id="total_grid" readonly></td>
                    </tr>
                  </thead>
                </table>     
          `
      $("#dvReferencias").html(`
                  <table class="table table-sm display nowrap" border="1" style="width: 100%" width="100%" id="carrito">
                      <thead class="table-primary" >
                          <tr>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th>Costo b</th>
                            <th  class="text-center">Dcto</th>
                            <th  class="text-center">Iva</th>
                            <th>Costo n</th>
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
      tb_ref = `
          
              <table  width="100%" class=" table-data-referencia" cellpadding="2" cellspacing="0" >
                  <thead>
                    <tr>
                      <th><b>Código</b></th>
                      <th><b>Precio b</b></th>
                      <th><b>Dto</b></th>
                      <th><b>Iva</b></th>
                      <th><b>Costo n.</b></th>
                      <th rowspan="2"> <select class="form-control p-0 data-grid" id="und_grid" style="height: 60px;width: 120px;"  multiple ></select></th>
                      <th><b>Cant</b></th>
                      <th><b>Total</b></th>
                    </tr>
                    <tr>
                      <td><input type="text" size="5"  class="form-control form-control-sm data-grid " id="codigo_grid" readonly></td>
                      <td><input type="text" class="form-control form-control-sm data-grid" id="preciob_grid" readonly></td>
                      <td><input type="text" class="form-control form-control-sm data-grid" id="dto_grid" size="4" readonly></td>
                      <td><input type="text" class="form-control form-control-sm data-grid" id="iva_grid" size="4" readonly></td>
                      <td><input type="text" class="form-control form-control-sm data-grid" id="precion_grid" readonly></td>
                      <td><input type="number" min="1"  class="form-control form-control-sm table-primary"data-grid id="cantidad_grid" ></td>
                      <td><input type="text" class="form-control form-control-sm data-grid" id="total_grid" readonly></td>
                    </tr>
                  </thead>
              </table>
          `
      $("#dvReferencias").html(`
                  <table class="table table-sm display nowrap" style="width: 100%" width="100%" id="carrito">
                      <thead class="table-primary" >
                          <tr>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th>Costo b</th>
                            <th  class="text-center">Dcto</th>
                            <th  class="text-center">Iva</th>
                            <th>Costo n</th>
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

  let soloStock = false;
  if (preferencias.SoloProductoSock == 'S') {
    soloStock = true;
  }

  const modalBusquedaRef = new ModalBusquedaProducto((rowData) => {

    if (ConfGeneral.ManejaFechaLote == 'S') {
      cargarLote(rowData);
    } else {
      cargarItemGrid(rowData);
    }

  }, {
    check_stock: soloStock
  });


  $("#nuevo").click(async function () {
    nuevoPedido();
    $("#carrito tbody tr").remove()
  })

  $("#notas").focus(function () {
    $("#tmp").val($(this).val())
  })


  $("#guardar").click(async function (e) {

    showLoadingWithTetrisLogo('Guardando documento', 'ISA Software')

    //guardamos los pagos 
    let idTransaccion = true;
    const id_transaccion = $("#id_transaccion").val()

    try {

      const respDoc = await enviarPeticion({
        link: '../shared/servicios/servicios.php',
        op: 'volcar_documento',
        id_transaccion
      });
      console.log(respDoc);

      if (!respDoc.ok) {
        dissminSwal(); // Ocultamos el loading primero

        let mensajeError = respDoc.message || "No se pudo guardar el documento";

        // Verificamos si viene el arreglo de productos con problemas de stock
        if (respDoc.productos && respDoc.productos.length > 0) {
          mensajeError += "<br><br><ul style='text-align: left; max-height: 200px; overflow-y: auto;'>";
          respDoc.productos.forEach(p => {
            mensajeError += `<li><b>[${p.codigo}]</b> ${p.producto}<br>Stock actual: <b>${p.stock_actual}</b> | Solicitado: <b>${p.cant_solicitada}</b></li><br>`;
          });
          mensajeError += "</ul>";
        }

        Swal.fire({
          icon: "error",
          title: "¡Atención!",
          html: mensajeError,
          confirmButtonText: "Entendido"
        });

        return;
      }

      idTransaccion = respDoc.id;

    } catch (error) {
      console.error(error);
      dissminSwal()
      Swal.fire("Ups", "Se genero un error al guardar el documento", "error")
      return;
    }

    dissminSwal()
    nuevoPedido();
    ImprimirDocumento(6, idTransaccion, 'Impresion de pedido de venta', false);

  });

  //**** */

  $("#notas").on('focusout', async function () {

    try {
      const nota = $.trim($(this).val());
      let sw = true
      if ($("#id_transaccion").val() > 0) {
        sw = false;
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
      });

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

  $("#und_grid").on('click', function (e) {
    e.preventDefault();

    if ($("#codigo_grid").val() != '') {
      const datosPrecio = calcularPrecioUnidad($("#precio_unitario").val(),
        $("#und_grid option:selected").val(),
        $("#dto_grid").val(), $("#iva_grid").val())

      $("#preciob_grid").val(formatNum(datosPrecio.preciob, '$'))
      $("#precion_grid").val(formatNum(datosPrecio.precion, '$'));

      const stock = mostrarStockDisponible($("#stock_real").val(),
        $("#und_grid option:selected").val(),
        $("#und_grid option:selected").text().split("-")[0])
      $("#stock_grid").val(stock);

      const totales = calcularTotal(unformatNum($("#preciob_grid").val()),
        $("#dto_grid").val(),
        $("#iva_grid").val(),
        $("#cantidad_grid").val());
      $("#total_grid").val(formatNum(totales.ttotal, '$'))

      $("#cantidad_grid").focus().select();
    }

  });

  $("#und_grid").on('keyup', function (e) {
    e.preventDefault();

    if (e.key == 'ArrowUp' || e.key == 'ArrowDown') {

      if ($("#codigo_grid").val() != '') {

        const datosPrecio = calcularPrecioUnidad($("#precio_unitario").val(),
          $("#und_grid option:selected").val(),
          $("#dto_grid").val(), $("#iva_grid").val())

        $("#preciob_grid").val(formatNum(datosPrecio.preciob, '$'))
        $("#precion_grid").val(formatNum(datosPrecio.precion, '$'));

        const stock = mostrarStockDisponible($("#stock_real").val(),
          $("#und_grid option:selected").val(),
          $("#und_grid option:selected").text().split("-")[0])
        $("#stock_grid").val(stock);

        const totales = calcularTotal(unformatNum($("#preciob_grid").val()),
          $("#dto_grid").val(),
          $("#iva_grid").val(),
          $("#cantidad_grid").val());
        $("#total_grid").val(formatNum(totales.ttotal, '$'))

      }
    }

  });

  $("#und_grid").on('keyup', function (e) {
    if (e.key == 'Enter') {
      if ($("#codigo_grid").val() != '') {
        $("#cantidad_grid").focus().select();
      }
    }
  });


  $("#cantidad_grid").on('keyup', async function (e) {
    e.preventDefault()
    if (isNaN(parseInt($("#cantidad_grid").val())) || $("#cantidad_grid").val() == 0) {
      return;
    }

    const totales = calcularTotal(unformatNum($("#preciob_grid").val()),
      $("#dto_grid").val(),
      $("#iva_grid").val(),
      $("#cantidad_grid").val());

    $("#total_grid").val(formatNum(totales.ttotal, '$'))


    if (e.key == 'Enter') {
      const refValida = validacionReferencia()

      if (!refValida) {
        return;
      }

      showLoadingWithTetrisLogo('Guardando item', 'ISA Software')
      try {

        //
        const numero = $("#numero").val()
        let sw_add = true;

        if (isNaN(parseInt($("#numero").val())) || $("#numero").val() == 0) {
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
            sw: 6,
            id_tercero_dir: $("#id_tercero_dir").val(),
            id_condicion: $("#id_condicion").val(),
            zona_ventas: $.trim($("#info_zona_venta").text()),
            zona_descripcion: $.trim($("#info_zona_descripcion").text())
          });

          $("#id_transaccion").val(datosEncabezado.data.id);
          $("#numero").val(datosEncabezado.data.numero)
          if (datosEncabezado.error) {
            dissminSwal()
            Swal.fire("Error", "No se puedo insertar el documento", "error")
            return;
          }

        }

        let cantidad_agregada = $("#cantidad_grid").val();
        let preciob = unformatNum($("#preciob_grid").val());
        let precion = unformatNum($("#precion_grid").val());
        let und_cargada = $.trim($("#und_grid option:selected").text().split("-")[0]);
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
              und_cargada = $.trim($(this).find("td").eq(7).html()).toUpperCase();


              if (und_cargada == $.trim($("#und_grid option:selected").text().split("-")[0])) {
                TextPromt = "Ya se encuentra agregada " + (cantidad_agregada) + " " + und_cargada + " , que desea hacer? \n 1. Sumar al actual.  \n 2. Reemplazar.";
              } else {
                TextPromt = "Ya se encuentra agregada " + (cantidad_agregada) + " " + und_cargada + " , que desea hacer?  \n 2. Reemplazar.";
              }


              if (codigo == $.trim($("#codigo_grid").val().toUpperCase())
                && lote == $.trim($("#lote_grid").val()).toUpperCase()) {

                sw_add = false;
                opcion = prompt(TextPromt);
                cantidad_agregada = parseInt($(this).find("td").eq(6).html());
                idDet = $(this).find("td:last").html();
                total_agregado = unformatNum($(this).find("td").eq(11).html());

                if (opcion == 1) { //sumar 

                  opc = 'update_transacciones_detalle_tmp'
                  cantidad_agregada = cantidad_agregada + parseInt($("#cantidad_grid").val());
                  und_cargada = $.trim($(this).find("td").eq(7).html()).toUpperCase();
                  can_und_cargada = $(this).find("td").eq(8).html();
                  total_agregado = (cantidad_agregada) * unformatNum($(this).find("td").eq(5).html());
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
                  total_agregado = (cantidad_agregada) * unformatNum($(this).find("td").eq(5).html());
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
              if (codigo == $.trim($("#codigo_grid").val()).toUpperCase()) {
                cantidad_agregada = parseInt($(this).find("td").eq(6).html());
                idDet = $(this).find("td:last").html();
                und_cargada = $.trim($(this).find("td").eq(7).html()).toUpperCase();
                sw_add = false;
                total_agregado = unformatNum($(this).find("td").eq(9).html());

                if (und_cargada == $.trim($("#und_grid option:selected").text().split("-")[0])) {
                  TextPromt = "Ya se encuentra agregada " + (cantidad_agregada) + " " + und_cargada + " , que desea hacer? \n 1. Sumar al actual.  \n 2. Reemplazar.";
                } else {
                  TextPromt = "Ya se encuentra agregada " + (cantidad_agregada) + " " + und_cargada + " , que desea hacer?  \n 2. Reemplazar.";
                }

                opcion = prompt(TextPromt);

                if (opcion == 1) { //sumar
                  opc = 'update_transacciones_detalle_tmp'
                  cantidad_agregada = cantidad_agregada + parseInt($("#cantidad_grid").val());
                  und_cargada = $.trim($(this).find("td").eq(7).html()).toUpperCase();
                  can_und_cargada = $(this).find("td").eq(8).html();
                  sw_add = true
                  total_agregado = (cantidad_agregada) * unformatNum($(this).find("td").eq(5).html());
                  //

                  // modifico los datos en las tablas 
                  $(this).find("td").eq(6).html(cantidad_agregada)
                  $(this).find("td").eq(9).html(formatNum(total_agregado, '$'));

                } else if (opcion == 2) { //Reemplazar
                  opc = 'update_transacciones_detalle_tmp'
                  sw_add = true
                  cantidad_agregada = $("#cantidad_grid").val();
                  und_cargada = $.trim($("#und_grid option:selected").text().split("-")[0]);
                  can_und_cargada = parseInt($("#und_grid option:selected").val());
                  total_agregado = (cantidad_agregada) * unformatNum($(this).find("td").eq(5).html());
                  // // modifico los datos en las tablas 
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
        datosDetalle = {
          link: '../shared/servicios/servicios.php',
          op: opc,
          id_transaccion: $("#id_transaccion").val(),
          id_referencia: $("#id_referencia").val(),
          cantidad: cantidad_agregada * parseInt($("#und_grid option:selected").val()),
          valor_unitario: $("#precio_unitario").val(),
          costo_unitario: $("#costo_promedio").val(),
          // id_lote           : $("#id_lote").val(),
          pcj_iva: $("#iva_grid").val(),
          pcj_descuento: $("#dto_grid").val(),
          und: und_cargada,
          can_und: parseInt(can_und_cargada),
          // vmcto             : vencimiento,
          // lote              : lote,
          sw: 6,
          id_bodega: $("#bodegas").val(),
          id_pedido: 0,
          id: idDet
        }

        if (ConfGeneral.ManejaFechaLote == 'S') {
          //inserto el detalle 
          datosDetalle.id_lote = $("#id_lote").val();
          datosDetalle.vmcto = vencimiento;
          datosDetalle.lote = lote;

        }

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
        setTimeout(function () {
          $("#filtro").focus()
        }, 500)
      } catch (e) {
        dissminSwal()
        Swal.fire("Error", "Se genero un error interno en la aplicación", "error")
        console.error(e)
      }

    }
  })

  $("#filtro").on('keyup', async function (e) {

    const filtro = $.trim($(this).val())
    if (filtro.length == 0) { return; }
    if (e.key == 'Enter') {
      const resp = await modalBusquedaRef.getRecords({ data: filtro, cod_barras: $("#cod_barras").is(":checked") });
      const nregistros = modalBusquedaRef.getNrecords()

      if (nregistros > 0) {

        if (nregistros == 1) {

          if (ConfGeneral.ManejaFechaLote == 'N') {
            await cargarItemGrid(resp[0]);
          } else {
            /*if( ConfGeneral.ManejaFIFO=='N' ){ 
              console.log("cargar lote")
                 await cargarLote(resp[0]);
                 return;
            }else{
               console.log("cargar item maneja fifo ")
                await cargarItemGrid(resp[0]);
                $(this).val('')
            }*/
            await cargarLote(resp[0]);
            return;
          }

        } else {
          modalBusquedaRef.show();
        }
      }
    }
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
      cargarTercero(item)
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
      console.log({
        ui
      })
      $("#nit_tmp").val(ui.item.nit)
    }
  })

  $("#txtBusquedaTercero").focus();
  dissminSwal();

});

$(document).keydown(function (e) {

  if (!$("#modalBusquedaRef").is(":visible")) {
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
      if ($("#codigo_grid").val() != '') {
        $("#und_grid").focus()
      }
    }
  }

  if ($("#modalFechaLotes").is(":visible")) {

    if (e.key === 'ArrowDown') {
      const currentRow = $(".data-lotes tbody tr.focus");
      let nextRow = currentRow.next("tr");

      // Si no hay siguiente fila, ir a la primera
      if (nextRow.length === 0) {
        nextRow = $(".data-lotes tbody tr").first();
      }
      currentRow.removeClass("focus");
      nextRow.addClass("focus");
      nextRow.focus();
    }

    // Moverse hacia arriba en la tabla (tecla "ArrowUp")
    if (e.key === 'ArrowUp') {
      const currentRow = $(".data-lotes tbody tr.focus");
      let prevRow = currentRow.prev("tr");

      // Si no hay fila anterior, ir a la última
      if (prevRow.length === 0) {
        prevRow = $(".data-lotes tbody tr").last();
      }

      currentRow.removeClass("focus");
      prevRow.addClass("focus");
      prevRow.focus();
    }

    // Al presionar Enter, simular el clic en la fila seleccionada
    if (e.key === 'Enter') {
      const focusedRow = $(".data-lotes tbody tr.focus");
      if (focusedRow.length > 0) {
        cargarItemGridLote(focusedRow[0]);
      }
    }
  }

  if (e.key == 'F4') {
    $("#guardar").trigger("click");
    return;
  }

  if (e.key == 'Escape') {
    $("#filtro").focus();
    limpiarReferencia();
  }
}); //
