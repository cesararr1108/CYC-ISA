const id_modulo = '74';
// configuraciones  
let carrito_enc = [];
let carrito_det = [];
let permisos = [];
let trasladosCargados = []; // Variable global para almacenar traslados sin filtrar


const disabledEnabledTercero = (sw) => {
  $("#nit").attr("disabled", sw);
  $("#id_tercero_dir").attr("disabled", sw);
  $("#tipos").attr("disabled", sw);
  $("#id_condicion").attr("disabled", sw)
  $("#bodegas").attr("disabled", sw)
  $("#id_bodega_destino").attr("disabled", sw)
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

  $("#id_tercero").val(item.id);
  $("#nit").val(item.nit);
  $("#tratamiento").val(item.tratamiento);
  $("#razon_comercial").val(item.razon_comercial);
  $("#textDireccion").val(item.direccion)
  //$("#id_condicion").val()
  //
  $("#info_condicion").text('CONTADO')
  $("#info_lista").text(item.lista_precios)
  $("#info_credito").text(formatNum(item.cupo_credito, '$', ConfGeneral))
  $("#info_zona_venta").text(item.codigo_zona)
  $("#info_zona_descripcion").text(item.zona_descripcion)
  $("#info_email").text(item.email_fe)
  $("#info_controlados").text($.trim(item.controlados))
  $("#info_controlados").text($.trim(item.controlados))
  $("#info_institucional").text($.trim(item.institucionales))
  ///

  await cargarDirecciones(item.id);
  // $("#id_tercero_dir").val(item.id_direccion);
  //$('#tabs button[data-bs-target="#navs-creacion"]').attr('disabled', false)
  //$('#tabs button[data-bs-target="#navs-creacion"]').tab("show")
  setTimeout(() => {
    $("#filtro").focus();
  }, 500);

}

const listarAlmacenes = async (bodegaDefaul = $("#bodega_session").val(), id_bodega_destino = null) => {
  try {
    //const resp = await bodegasUsuarios();
    const resp = await enviarPeticion({
      link: '../models/almacenes.php',
      filtro: '',
      op: 'listar'
    });;
    if (resp.data.length == 0) {
      $("#result").html(ShowAlert('No se encontraron resultados', 'warning'))
      return;
    }
    let options = ``;
    resp.data.forEach((d) => {
      options += `<option value="${d.id}">${d.almacen} - ${d.descripcion}</option>`;
    });
    $("#bodegas").html(options);
    const BodegaActual = bodegaDefaul
    $("#bodegas").val(BodegaActual);//.attr("disabled", true);


    const todasBog = await enviarPeticion({
      link: '../models/almacenes.php',
      filtro: '',
      op: 'listar'
    });

    const bodegasDestino = todasBog.data.filter(item => item.id != BodegaActual);

    let optionsDes = ``;
    bodegasDestino.forEach((d) => {
      optionsDes += `<option value="${d.id}">${d.almacen} - ${d.descripcion}</option>`;
    });
    $("#id_bodega_destino").html(optionsDes);
    if (id_bodega_destino !== null) {
      $("#id_bodega_destino").val(id_bodega_destino);
    }

  } catch (e) {
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

    if (resp.lenght == 0) {
      return;
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
    // Error silencioso
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
  // $('#id_condicion').prop('selectedIndex', 1); 

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
  $("#stock_sin_comp").val(0);
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
  disabledEnabledTercero(false)
  fechaActual([
    ['fecha_inicial', 'fecha_final'],
    ['fecha_ini', 'fecha_fin']
  ])

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
}

const cargarItemGrid = (data) => {

  $("#id_referencia").val(data.id)
  $("#descripcion_grid").val(data.descripcion);
  $("#laboratorio_grid").val(data.laboratorio);

  const stock_und = data.total_stock > 0 ? parseInt(data.total_stock / data.can_und) + ` ${data.des_und_comp} y ` + (data.total_stock % data.can_und) * data.can_und : 0;

  $("#stock_grid").val(stock_und);
  $("#stock_sin_comp").val(data.stock_sin_comp);
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
  $("#preciob_grid").val(formatNum(costo, '$', ConfGeneral))
  coston = Math.round((costo * (data.porcentaje_iva) / 100 + 1))
  $("#precion_grid").val(formatNum(coston, '$', ConfGeneral))

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

  try {


    let resp = await enviarPeticion({
      link: '../shared/servicios/servicios.php',
      op: 'cargar-lotes',
      id_referencia: data.id,
      bodega: $("#bodegas").val(),
      id_pedido: $("#id_transaccion").val()
    });

    resp = resp.filter(item => (item.stock - item.comprometidas) > 0);

    if (resp.length == 0) {
      Swal.fire("Ups", "No se encontraron lotes disponibles", "error")
      return;
    }

    if (resp.length == 1) {
      const item_lotes = resp[0];
      data.id_lote = item_lotes.id_lote;
      data.lote = item_lotes.lote;
      data.vencimiento = item_lotes.vencimiento;
      data.total_stock = item_lotes.stock - item_lotes.comprometidas;
      data.stock_sin_comp = item_lotes.stock;

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
      data.vencimiento = item.vencimiento;
      data.total_stock = item.stock - item.comprometidas;
      data.stock_sin_comp = item.stock;



      tb += `<tr tabindex="${tab}" 
                  onclick="cargarItemGridLote(this)" 
                  data-element='${JSON.stringify(data)}'
                   >
                <td>${item.lote}</td>
                <td>${item.vencimiento}</td>
                <td>${item.stock - item.comprometidas}</td>
              </tr>`
      tab++;


    });

    tb += `</tbody>
       </table>`

    $("#result-lotes").html(tb)
    // Seleccionar la primera fila automáticamente
    $('#modalFechaLotes').on('shown.bs.modal', function () {
      const firstRow = $(".data-lotes tbody tr").first();
      firstRow.addClass("focus");
      firstRow.focus();
    });

  } catch (e) {
    Swal.fire("Error", "Error al cargar los lotes", "error")
  }

}

const actualizarTotales = (totales) => {

  $("#t_bruto").val(formatNum(parseFloat(totales.valor_total) - parseFloat(totales.iva) + parseFloat(totales.descuento), '$', ConfGeneral));
  $("#t_dcto").val(formatNum(totales.descuento, '$', ConfGeneral));
  $("#t_iva").val(formatNum(totales.iva, '$', ConfGeneral));
  $("#t_total").val(formatNum(totales.valor_total, '$', ConfGeneral));
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

}

const editarPedido = async (id) => {

  try {
    showLoadingWithTetrisLogo('Cargando información', 'ISA Software');

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
    $("#stock_real").val(parseInt(item.total_stock) - parseInt(item.comprometidas) + parseInt(item.cantidad))
    $("#id_detalle").val(item.id_detalle)
    if (ConfGeneral.ManejaFechaLote == 'S') {
      $("#id_lote").val(item.id_lote)

    }

    const datosPrecio = calcularPrecioUnidad(item.valor_unitario, item.can_und, item.pcj_descuento, item.iva)

    $("#preciob_grid").val(formatNum(datosPrecio.preciob, '$', ConfGeneral));
    $("#dto_grid").val(item.pcj_descuento)
    $("#iva_grid").val(item.iva)
    $("#precion_grid").val(formatNum(datosPrecio.precion, '$', ConfGeneral));
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
    $("#total_grid").val(formatNum(total, '$', ConfGeneral));

    const stock = mostrarStockDisponible($("#stock_real").val(),
      $("#und_grid option:selected").val(),
      $("#und_grid option:selected").text().split("-")[0])
    $("#stock_grid").val(stock);

    dissminSwal()

  } catch (e) {
    dissminSwal()
    Swal.fire("Error", "Ha ocurrido un error al intentar obtener los datos!", "error")
  }

}


const addCart = (item) => {
  let cl = '';

  if (item.disabled) {
    cl = 'table-danger'
  }
  let tr = `<tr id="id_detalle${item.id}" class="${cl}">
             <td>${item.codigo}</td>
             <td width="30%">${item.descripcion}-${item.laboratorio}</td>
             <td>${item.preciob}</td>
             <td  class="text-center">${item.dto}</td>
             <td  class="text-center">${item.iva}</td>
             <td>${item.precion}</td>
             <td  class="text-center ">${item.cantidad}</td>
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
             <td><button class="btn btn-sm btn-light border border-1 border-danger"  onclick="eliminarItem(${item.id})"><i class="fa-regular fa-trash-can text-danger"></i></button></td>
             <td><button class="btn btn-sm btn-light border border-1 border-primary" ${item.disabled ? 'disabled' : ''} onclick="editarPedido(${item.id})"><i class="fa fa-pencil text-primary "></i></button></td>  
             <td class="d-none">${item.id}</td>
         </tr>`
  $("#carrito tbody").prepend(tr);
  disabledEnabledTercero(true);


}

const recuperarPedido = async (data) => {
  showLoadingWithTetrisLogo('Cargando Pedido', 'ISA Software');
  try {
    nuevoPedido()
    //carga los datos del tercero
    item = data;
    listarAlmacenes(item.id_bodega, item.id_bodega_destino);
    $('#tabs button[data-bs-target="#navs-creacion"]').tab('show')

    $("#id_tercero").val(item.id_tercero);
    $("#nit").val(item.nit);
    $("#tratamiento").val(item.nombres);
    $("#razon_comercial").val(item.razon_comercial);
    $("#textDireccion").val(item.direccion)

    $("#info_condicion").text(item.des_condicion)
    $("#info_lista").text(item.lista_precios)
    $("#info_credito").text(formatNum(item.cupo_credito, '$', ConfGeneral))
    $("#info_zona_venta").text(item.zona_ventas)
    $("#info_zona_descripcion").text(item.zona_descripcion)
    $("#info_email").text(item.email_fe)
    ///

    await cargarDirecciones(item.id_tercero);
    //asigno la direccion selecciona
    $("#id_tercero_dir").val(item.id_tercero_dir)
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
      itemCart.preciob = formatNum(dataPrecio.preciob, '$', ConfGeneral)
      itemCart.dto = item.pcj_descuento
      itemCart.iva = item.pcj_iva
      itemCart.precion = formatNum(dataPrecio.precion, '$', ConfGeneral)
      itemCart.cantidad = item.cantidad / item.can_und
      itemCart.und = item.und
      itemCart.can_und = item.can_und

      if (ConfGeneral.ManejaFechaLote == 'S') {
        itemCart.vcmto = item.vmcto
        itemCart.lote = item.lote
      }

      itemCart.total = formatNum(Math.round(dataPrecio.precion * (item.cantidad / item.can_und)), '$', ConfGeneral)
      disabled = false;
      if (parseInt(item.stock_lote) < parseInt(item.cantidad)) {
        disabled = true;
      }
      itemCart.disabled = disabled;
      await addCart(itemCart)
      limpiarReferencia();
    })
    $("#filtro").focus()
    dissminSwal()

  } catch (e) {
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
        // Error silencioso
      }

      buscarTemporales();
      nuevoPedido();
    }
  });

}

const imprimirTrasladoTmp = (data) => {
  if (!data.id_transaccion) {
    Swal.fire("Error", "No se encontró el ID del traslado", "error");
    return;
  }
  const base = 'https://thecyctechnology.com/development';
  const esDev = window.location.href.startsWith(base);

  if (esDev) {
    url = 'https://thecyctechnology.com/development/app/pdf/';
  } else {
    url = 'https://thecyctechnology.com/cyc/app/pdf/';
  }
  const urlImprimir = `${url}trasladosBodegas-tmp.php?id=${data.id_transaccion}`;
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
      nit: $("#nit_tmp").val(),
      sw: 3
    });
    console.log(resp);

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

    const botonImpresion = `<button class="other-btn btn btn-sm btn-outline-primary" title="Imprimir">
                               <i class="fa-solid fa-print"></i>
                            </button>`;

    createDataTable(
      'pedidos-temp',
      resp.data,
      ['fecha_hora', 'usuario', 'id_bodega', 'id_bodega_destino', 'numero', 'nombres', 'razon_comercial', 'descuento', 'iva', 'valor_total'],
      ['Fecha', 'usuario', 'origen', 'destino', 'Numero', 'Cliente', 'Razon comercial', 'Dto', 'Iva', 'Total'],
      recuperarPedido,
      eliminarPedido,
      true,
      botonImpresion,
      imprimirTrasladoTmp,
      false
    )

    dissminSwal()
  } catch (e) {
    dissminSwal()
    Swal.fire("Error", "Ha ocurrido un error al obtener los pedidos", "error")
  }

}
const soloContado = (data) => {
  return data.filter(item => item.dias == 0)
}

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

}

const trasladosPendientes = async () => {
  try {
    showLoadingWithTetrisLogo('Cargando información', 'ISA Software');
    const resp = await enviarPeticion({
      link: '../models/TrasladosBodegas.php',
      op: 'traslados_pendientes',
      bodega: $("#bodegas").val()
    });

    $("#traslados-pendientes").html('');
    if (resp.length == 0) {
      dissminSwal()
      //Swal.fire("Información", "No se encontraron traslados pendientes!", "info")
      $("#n_traslados_pendientes").hide();
      $("#traslados-pendientes").html(`
        <div class="alert alert-warning"><i class="fa-solid fa-triangle-exclamation"></i> No se encontraron traslados pendientes!</div>   
      `);

      return;
    }
    $("#n_traslados_pendientes").text(resp.length)
    $("#n_traslados_pendientes").show();

    let table = `
      <table class="table table-sm table-striped table-hover" id="traslados-pendientes">
        <thead> 
          <tr>
            <th>Fecha</th>
            <th>Hora</th>
            <th>Numero</th> 
            <th>Bodega origen</th>
            <th>Bodega destino</th>
            <th>Usuario</th>  
            <th>Detalle</th>
          </tr>
        </thead> 
        <tbody>
    `
    resp.forEach(item => {
      table += `  
        <tr data-element='${JSON.stringify(item)}'>
          <td>${item.fecha}</td>
          <td>${item.hora}</td>
          <td>${item.numero}</td>
          <td>${item.bodega_origen}</td>
          <td>${item.bodega_destino}</td>
          <td>${item.usuario}</td>  
          <td><button class="btn btn-sm btn-light border border-1 border-primary"  onclick="trasladosPendientesDetalle(${item.numero})"><i class="fa-solid fa-eye text-primary "></i></button></td>
        </tr>
      `
    });

    table += `  
         </tbody>
      </table>`
    $("#traslados-pendientes").html(table);
    dissminSwal()
    $("#modalTrasladosPendientes").modal("show")

  } catch (e) {
    dissminSwal()
    $("#traslados-pendientes").html(` 
      <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> Error al cargar los traslados pendientes!</div>
    `);
  }
}

const trasladosPendientesDetalle = async (numero) => {
  try {
    showLoadingWithTetrisLogo('Cargando información', 'ISA Software');
    const resp = await enviarPeticion({
      link: '../models/TrasladosBodegas.php',
      op: 'traslados_pendientes_detalle',
      bodega: $("#bodegas").val(),
      numero
    });

    if (resp.length == 0) {
      dissminSwal()
      return;
    }

    let table = `
      <table class="table table-sm table-striped table-hover table-detalle-items-pendientes " id="traslados-pendientes">
        <thead class=" table-nowrap table-primary">
          <tr>
            <th>Fecha</th>
            <th>Numero</th> 
            <th>Bodega origen</th>
            <th>Bodega destino</th>
            <th>Usuario</th> 
            <th>Código</th>
            <th>Descripción</th>
            <th>Laboratorio</th>
            <th>Cantidad enviada</th>
            <th>Recibidas</th>
            <th>Pendientes</th>
            <th>Cantidad a recibir</th>
            <th>Und</th>
            <th>Lote</th>
            <th>Vcmto</th>
          </tr>
        </thead>
        <tbody>
    `
    resp.forEach(item => {
      table += `
        <tr data-element='${JSON.stringify(item)}'>
          <td>${item.fecha}</td>
          <td>${item.numero}</td>
          <td>${item.bodega_origen}</td>
          <td>${item.bodega_destino}</td>
          <td>${item.usuario}</td>
          <td>${item.codigo}</td>
          <td>${item.descripcion}</td>
          <td>${item.laboratorio}</td>
          <td class="table-primary">${item.cantidad_enviada}</td>
          <td class="table-info">${item.cantidad_recibida}</td>
          <td class="table-warning">${item.cantidad_enviada - item.cantidad_recibida}</td>
          <td class="table-primary"><input type="number" class="form-control form-control-sm cantidad-recibir" value="0" id="cantidad_recibida_${item.id_det}"></td>
          <td>${item.und}</td>
          <td>${item.lote}</td>
          <td>${item.vmcto}</td>
        </tr>
      `;
    });

    table += `  
         </tbody>
      </table>`
    $("#traslados-pendientes-detalle").html(table);

    $(".cantidad-recibir").on("focusout", function () {

      const data = JSON.parse($(this).closest("tr").attr("data-element"))

      const id_det = data.id_det;
      const cantidad_enviada = parseInt(data.cantidad_enviada);
      const cantidad_recibida = parseInt(data.cantidad_recibida);
      const cantidad_pendiente = cantidad_enviada - cantidad_recibida;
      const cantidad_a_recibir = parseInt($(this).val());
      if (cantidad_a_recibir > cantidad_pendiente) {
        Swal.fire("Error", "La cantidad a recibir no puede ser mayor a la cantidad pendiente", "error")
        $(this).val(cantidad_pendiente)
      }
    })


    dissminSwal()
    $("#modalTrasladosPendientesDetalle").modal("show")

  } catch (e) {
    dissminSwal()
  }
}

const cambiarAlmacenes = async () => {
  try {
    const BodegaActual = $("#bodegas").val();

    const todasBog = await enviarPeticion({
      link: '../models/almacenes.php',
      filtro: '',
      op: 'listar'
    });;


    const bodegasDestino = todasBog.data.filter(item => item.id != BodegaActual);

    let optionsDes = ``;
    bodegasDestino.forEach((d) => {
      optionsDes += `<option value="${d.id}">${d.almacen} - ${d.descripcion}</option>`;
    });
    $("#id_bodega_destino").html(optionsDes);

  } catch (e) {
    $("#result").html(ShowAlert('Error interno de la aplicación', 'error'))
  }
}

const consultarTraslados = async () => {
  try {
    showLoadingWithTetrisLogo('Cargando información', 'ISA Software');
    const resp = await enviarPeticion({
      link: '../models/TrasladosBodegas.php',
      op: 'consultar-traslados',
      fh_ini: $("#fecha_ini").val(),
      fh_fin: $("#fecha_fin").val()
    });

    if (resp.length == 0) {
      dissminSwal();
      $("#dvMisTraslados").html('<div class="alert alert-info">No hay traslados en el rango de fechas seleccionado</div>');
      return;
    }

    // Crear tabla HTML
    if ($("#tblMisTraslados").length === 0) {
      $("#dvMisTraslados").html(`<div class="table-responsive text-nowrap">
        <table id="tblMisTraslados" class="table table-bordered table-hover">
          <thead class="table-primary">
          </thead>
        </table>
        </div>`);
    }

    // Almacenar traslados para filtrar posteriormente
    trasladosCargados = resp;

    // Obtener valores únicos para los filtros
    const bodegasOrigen = [...new Set(resp.map(item => item.b_origen))].sort();
    const bodegasDestino = [...new Set(resp.map(item => item.destino))].sort();

    // Llenar selects de filtros
    let optOrigen = '<option value="">Todas</option>';
    bodegasOrigen.forEach(bodega => {
      optOrigen += `<option value="${bodega}">${bodega}</option>`;
    });
    $("#filtro_bodega_origen").html(optOrigen);

    let optDestino = '<option value="">Todas</option>';
    bodegasDestino.forEach(bodega => {
      optDestino += `<option value="${bodega}">${bodega}</option>`;
    });
    $("#filtro_bodega_destino").html(optDestino);

    // Transformar datos para que la fecha sea string formateado
    const trasladosFormateados = resp.map(item => ({
      ...item,
      fecha: new Date(item.fecha).toLocaleDateString('es-CO')
    }));

    // Renderizar tabla inicial
    renderizarTablaTraslados(trasladosFormateados);

    dissminSwal();

  } catch (e) {
    dissminSwal();
    Swal.fire('Error', 'Error al cargar los traslados', 'error');
  }
}

const aplicarFiltros = () => {
  const bodegaOrigen = $("#filtro_bodega_origen").val();
  const bodegaDestino = $("#filtro_bodega_destino").val(

  );
  const tipoMovimiento = $("#filtro_tipo_movimiento").val();

  // Filtrar datos
  let trasladosFiltrados = trasladosCargados.filter(item => {
    let cumple = true;

    if (bodegaOrigen && item.b_origen !== bodegaOrigen) {
      cumple = false;
    }
    if (bodegaDestino && item.destino !== bodegaDestino) {
      cumple = false;
    }
    if (tipoMovimiento && item.tipo_movimiento !== tipoMovimiento) {
      cumple = false;
    }

    return cumple;
  });

  // Transformar y renderizar
  const trasladosFormateados = trasladosFiltrados.map(item => ({
    ...item,
    fecha: new Date(item.fecha).toLocaleDateString('es-CO')
  }));

  renderizarTablaTraslados(trasladosFormateados);
};

// Función para validar si un traslado puede ser editado
const validarTrasladoEditable = (rowData) => {
  // 1. Validar que sea SALIDA (flecha roja hacia arriba)
  if (rowData.tipo_movimiento !== 'SALIDA') {
    Swal.fire('Aviso', 'Solo se pueden editar traslados de SALIDA', 'warning');
    return false;
  }

  // 2. Validar que no tenga más de 48 horas de ser creado
  const fechaCreacion = new Date(rowData.fecha_hora);
  const ahora = new Date();
  const horasTranscurridas = (ahora - fechaCreacion) / (1000 * 60 * 60);

  if (horasTranscurridas > 48) {
    Swal.fire('Aviso', 'No se puede editar traslados con más de 48 horas de creado', 'warning');
    return false;
  }

  return true;
};

// Función para validar stock de un item
const validarStockItem = async (id_bodega, id_referencia, cantidad_solicitada, id_lote = null, cantidad_und = 1) => {
  try {
    const resp = await enviarPeticion({
      link: '../models/TrasladosBodegas.php',
      op: 'validar-stock-item-traslado',
      id_bodega: id_bodega,
      id_referencia: id_referencia,
      cantidad: cantidad_solicitada,
      id_lote: id_lote,
      cantidad_und: cantidad_und
    });

    return resp;
  } catch (e) {
    console.error('Error validando stock:', e);
    return { ok: false, mensaje: 'Error al validar stock' };
  }
};

// Función para editar items del traslado
const editarTraslado = async (rowData) => {
  try {
    // Validar si el traslado puede ser editado
    if (!validarTrasladoEditable(rowData)) {
      return;
    }

    showLoadingWithTetrisLogo('Cargando detalles', 'ISA Software');

    // Obtener detalles del traslado
    const resp = await enviarPeticion({
      link: '../models/TrasladosBodegas.php',
      op: 'detalle-traslado',
      id_traslado: rowData.id
    });

    if (!resp || resp.length === 0) {
      dissminSwal();
      Swal.fire('Aviso', 'No hay detalles para este traslado', 'info');
      return;
    }

    //filtraar sw = 6 para mostrar solo los items de salida
    const itemsSalida = resp.filter(item => parseInt(item.sw) === 6);

    // Crear el modal con items editables
    let htmlModalEditar = `
      <div class="row mb-3">
        <div class="col-md-3">
          <h6><strong>Número:</strong> ${rowData.numero}</h6>
        </div>
        <div class="col-md-3">
          <h6><strong>Fecha:</strong> ${rowData.fecha}</h6>
        </div>
        <div class="col-md-3">
          <h6><strong>Bodega Origen:</strong> ${rowData.b_origen}</h6>
        </div>
        <div class="col-md-3">
          <h6><strong>Bodega Destino:</strong> ${rowData.destino}</h6>
        </div>
      </div>
      <div class="table-responsive">
        <table id="tblEditarDetalleTraslado" class="table table-bordered table-striped table-sm">
          <thead class="table-primary">
            <tr>
              <th>Código</th>
              <th>Descripción</th>
              <th>Laboratorio</th>
              <th class="text-center" style="width: 100px;">Cantidad</th>
              <th class="text-center" style="width: 80px;">Unidad</th>
              <th>Lote</th>
              <th>Vencimiento</th>
              <th class="text-center" style="width: 80px;">Estado</th>
            </tr>
          </thead>
          <tbody>`;

    itemsSalida.forEach((item, index) => {
      const iconoTipo = parseInt(item.sw) == 6 ?
        '<i class="fa-solid fa-arrow-up text-danger" style="font-size: 1.2rem;" title="Salida"></i>' :
        '<i class="fa-solid fa-arrow-down text-success" style="font-size: 1.2rem;" title="Entrada"></i>';

      htmlModalEditar += `
        <tr 
        data-id-detalle="${item.id}"
        data-id-referencia="${item.id_referencia}" 
        data-lote="${item.id_lote || 0}"
        data-id-bodega="${item.id_bodega}"
        data-id-bodega-destino="${item.id_bodega_destino}"
        data-can-und="${item.can_und || 0}"
        data-mes="${item.mes || ''}"
        data-ano="${item.ano || ''}"
        >
          <td><strong>${item.codigo}</strong></td>
          <td>${item.descripcion}</td>
          <td>${item.laboratorio || '-'}</td>
          <td class="text-center">
            <input type="number" class="form-control form-control-sm cantidad-item" 
                   value="${item.cantidad}" min="1" style="width: 100px;" data-initial="${item.cantidad}">
            <small class="text-muted stock-info"></small>
          </td>
          <td class="text-center">${item.unidad}</td>
          <td>${item.lote || '-'}</td>
          <td>${item.vmcto || '-'}</td>
          <td class="text-center">
            <span class="badge bg-success estado-item">Válido</span>
          </td>
        </tr>`;
    });

    htmlModalEditar += `
      </tbody>
      </table>
      </div>
      <div class="alert alert-info mt-3">
        <strong>Nota:</strong> Al cambiar cantidades, se validará automáticamente el stock disponible en la bodega de salida.
      </div>`;

    // Crear o actualizar el modal
    let modal = document.getElementById('modalEditarTraslado');
    if (!modal) {
      crearModalEditarTraslado();
      modal = document.getElementById('modalEditarTraslado');
    }

    document.getElementById('detalleEditarTraslado').innerHTML = htmlModalEditar;

    // Guardar referencias globales para usar en validaciones
    window.trasladoActualAEditar = {
      id: rowData.id,
      id_bodega: rowData.id_bodega,
      items_editados: {}
    };

    // Agregar event listeners para validar stock al cambiar cantidades
    $('#tblEditarDetalleTraslado tbody').off('change', 'input.cantidad-item').on('change', 'input.cantidad-item', async function () {
      const $row = $(this).closest('tr');
      const $input = $(this);
      const id_detalle = $row.data('id-detalle');
      const id_referencia = $row.data('id-referencia');
      const cantidad_nueva = parseFloat($input.val());
      const $estadoBadge = $row.find('.estado-item');
      const $stockInfo = $row.find('.stock-info');
      const cantidad_und = $row.data('can-und') || 1;

      if (cantidad_nueva <= 0) {
        $estadoBadge.removeClass('bg-success').addClass('bg-danger').text('Inválido');
        $stockInfo.text('Cantidad debe ser mayor a 0');
        return;
      }

      // Deshabilitar input y mostrar cargando
      $input.prop('disabled', true);
      $estadoBadge.removeClass('bg-success bg-danger').addClass('bg-secondary').html('<i class="fa-solid fa-spinner fa-spin-pulse fa-sm"></i> Validando...');
      $stockInfo.html('<span class="text-muted"><i class="fa-solid fa-spinner fa-spin-pulse"></i> Validando stock...</span>');

      try {
        // Validar stock
        const validacion = await validarStockItem(
          window.trasladoActualAEditar.id_bodega,
          id_referencia,
          cantidad_nueva,
          $row.data('lote') !== 0 ? $row.data('lote') : null,
          cantidad_und
        );

        if (validacion.ok) {
          $estadoBadge.removeClass('bg-danger').addClass('bg-success').text('Válido');
          $stockInfo.html(`<span class="text-success">Stock: ${validacion.stock_disponible}</span>`);
          window.trasladoActualAEditar.items_editados[id_detalle] = {
            cantidad: cantidad_nueva,
            id_referencia: id_referencia
          };
        } else {
          $estadoBadge.removeClass('bg-success').addClass('bg-danger').text('Sin stock');
          $stockInfo.html(`<span class="text-danger">${validacion.mensaje}</span>`);
          delete window.trasladoActualAEditar.items_editados[id_detalle];
        }
      } catch (e) {
        console.error('Error validando stock:', e);
        $estadoBadge.removeClass('bg-secondary').addClass('bg-danger').text('Error');
        $stockInfo.html(`<span class="text-danger">Error al validar: ${e.message}</span>`);
      } finally {
        // Reactivar input
        $input.prop('disabled', false);
        $input.focus();
      }
    });

    // Mostrar el modal
    const bsModal = new bootstrap.Modal(modal);

    dissminSwal();
    bsModal.show();

  } catch (e) {
    dissminSwal();
    console.error('Error al editar traslado:', e);
    Swal.fire('Error', 'Error al editar el traslado: ' + e.message, 'error');
  }
};

// Función para guardar cambios del traslado editado
const guardarCambiosTraslado = async () => {
  try {
    if (!window.trasladoActualAEditar || Object.keys(window.trasladoActualAEditar.items_editados).length === 0) {
      Swal.fire('Aviso', 'No hay cambios para guardar', 'info');
      return;
    }

    showLoadingWithTetrisLogo('Guardando cambios', 'ISA Software');

    // Preparar datos para enviar al servidor
    const items = [];
    $('#tblEditarDetalleTraslado tbody tr').each(function () {
      const $row = $(this);

      const id_detalle = $row.data('id-detalle');
      const id_referencia = $row.data('id-referencia');
      const id_bodega = $row.data('id-bodega');
      const id_bodega_destino = $row.data('id-bodega-destino');
      const can_und = $row.data('can-und');
      const cantidad_actual = parseFloat($row.find('input.cantidad-item').val());
      const $estadoBadge = $row.find('.estado-item');
      const ano = $row.data("ano");
      const mes = $row.data("mes")

      // Solo enviar items con estado válido
      if ($estadoBadge.hasClass('bg-success')) {
        items.push({
          id_detalle: id_detalle,
          cantidad: cantidad_actual,
          can_und: can_und,
          id_referencia: id_referencia,
          id_bodega: id_bodega,
          id_bodega_destino,
          mes,
          ano
        });
      }
    });

    if (items.length === 0) {
      dissminSwal();
      Swal.fire('Aviso', 'No hay items válidos para guardar', 'warning');
      return;
    }

    const resp = await enviarPeticion({
      link: '../models/TrasladosBodegas.php',
      op: 'actualizar-items-traslado',
      id_traslado: window.trasladoActualAEditar.id,
      items: JSON.stringify(items)
    });

    dissminSwal();

    if (resp.ok) {
      bootstrap.Modal.getInstance(document.getElementById('modalEditarTraslado')).hide();
      Swal.fire('Éxito', 'Cambios guardados correctamente', 'success');
      // Recargar traslados
      await consultarTraslados();
    } else {
      Swal.fire('Error', resp.mensaje || 'Error al guardar cambios', 'error');
    }
  } catch (e) {
    dissminSwal();
    console.error('Error al guardar cambios:', e);
    Swal.fire('Error', 'Error al guardar los cambios: ' + e.message, 'error');
  }
};

// Función para crear el modal de edición
const crearModalEditarTraslado = () => {
  const modalHTML = `
    <div class="modal fade" id="modalEditarTraslado" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-fullscreen" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Editar Traslado</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div id="detalleEditarTraslado"></div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="guardarCambiosTraslado()">Guardar Cambios</button>
          </div>
        </div>
      </div>
    </div>`;

  $('body').append(modalHTML);
};

const renderizarTablaTraslados = (datos) => {

  let btns = `<button class="other-btn btn btn-sm btn-outline-primary" title="Ver Detalle" aria-label="Ver Detalle"><i class="fa-solid fa-eye"></i></button>
              <button class="print-btn btn btn-sm btn-outline-danger" title="Imprimir" aria-label="Imprimir"><i class="fa-solid fa-print"></i></button>`;

  if (permisos.ModificarTraladosGuardados) {
    btns += `<button class="edit-btn btn btn-sm btn-outline-warning" title="Editar" aria-label="Editar"><i class="fa-solid fa-pen-to-square"></i></button>`;
  }
  // Usar createDataTable
  createDataTable(
    'tblMisTraslados',                                      // ID tabla
    datos,                                                  // datos
    ['numero', 'fecha', 'b_origen', 'destino', 'usuario_generador', 'cantidad_items', 'notas', 'tipo_movimiento'], // dataNames
    ['Número', 'Fecha', 'Bodega Origen', 'Bodega Destino', 'Usuario', 'Items', 'Notas', 'Movimiento'],  // columnNames
    false,                                                  // editCallback (false para manejarlo manualmente)
    false,                                                  // deleteCallback
    true,                                                   // otherButton
    btns,                                                   // boton
    verDetalleTraslado,                                     // otherCallback
    false,                                                  // btnExport
    [],                                                     // currencyColumns
    'COP',                                                  // currencyCode
    [{                                                      // customColumnClasses
      data: 'usuario_generador',
      className: 'text-center'
    },
    {
      data: 'cantidad_items',
      className: 'text-center'
    },
    {
      data: 'notas',
      className: 'text-center'
    },
    {
      data: 'tipo_movimiento',
      className: 'text-center'
    }],
    [],                                                     // sumColumns
    10                                                      // pageLength
  );

  // Función para renderizar flechas y notas cada vez que se redibuja la tabla
  const renderizarFlechasYNotasEnTabla = () => {
    const table = $(`#tblMisTraslados`).DataTable();
    table.rows({ page: 'current' }).every(function (index) {
      const data = this.data();

      // Renderizar flechas de movimiento (índice 7 - tipo_movimiento)
      const cellMovimiento = $('td:eq(7)', this.node());
      if (data.tipo_movimiento === 'ENTRADA') {
        cellMovimiento.html('<i class="fa-solid fa-arrow-down text-success" style="font-size: 1.2rem;" title="Entrada"></i>');
      } else if (data.tipo_movimiento === 'SALIDA') {
        cellMovimiento.html('<i class="fa-solid fa-arrow-up text-danger" style="font-size: 1.2rem;" title="Salida"></i>');
      }

      // Renderizar icono de notas (índice 6)
      const cellNotas = $('td:eq(6)', this.node());
      if (data.notas && data.notas.trim() !== '') {
        cellNotas.html(`<button class="btn-notas btn btn-sm btn-outline-warning" data-notas="${data.notas.replace(/"/g, '&quot;')}" title="Ver notas"><i class="fa-solid fa-comment"></i></button>`);
      } else {
        cellNotas.html('-');
      }
    });
  };

  // Post-procesamiento para renderizar flechas de movimiento e icono de notas
  const table = $(`#tblMisTraslados`).DataTable();

  // Ejecutar la primera vez
  renderizarFlechasYNotasEnTabla();

  // Ejecutar cada vez que se redibuja la tabla (cambio de página)
  table.on('draw.dt', function () {
    renderizarFlechasYNotasEnTabla();
  });

  // Manejador para el botón de notas
  $('#tblMisTraslados tbody').off('click', 'button.btn-notas').on('click', 'button.btn-notas', function (e) {
    e.stopPropagation();
    const notas = $(this).data('notas');
    Swal.fire({
      title: 'Notas del Traslado',
      text: notas,
      icon: 'info',
      confirmButtonText: 'Cerrar'
    });
  });

  // Manejador para botón de imprimir
  $('#tblMisTraslados tbody').off('click', 'button.print-btn').on('click', 'button.print-btn', function () {
    const rowData = table.row($(this).closest('tr')).data();
    imprimirTraslado(rowData.id);
  });

  // Manejador para botón de editar
  $('#tblMisTraslados tbody').off('click', 'button.edit-btn').on('click', 'button.edit-btn', function () {
    const rowData = table.row($(this).closest('tr')).data();
    editarTraslado(rowData);
  });
};

const verDetalleTraslado = async (rowData) => {
  try {
    showLoadingWithTetrisLogo('Cargando detalles', 'ISA Software');

    const resp = await enviarPeticion({
      link: '../models/TrasladosBodegas.php',
      op: 'detalle-traslado',
      id_traslado: rowData.id
    });

    if (!resp || resp.length === 0) {
      dissminSwal();
      Swal.fire('Aviso', 'No hay detalles para este traslado', 'info');
      return;
    }

    // Crear la tabla de detalles
    let htmlTabla = `
      <div class="row mb-3">
        <div class="col-md-4">
          <h6><strong>Número:</strong> ${rowData.numero}</h6>
        </div>
        <div class="col-md-4">
          <h6><strong>Bodega Origen:</strong> ${rowData.b_origen}</h6>
        </div>
        <div class="col-md-4">
          <h6><strong>Bodega Destino:</strong> ${rowData.destino}</h6>
        </div>
      </div>
      <div class="row mb-3">
        <div class="col-md-12">
          <h6><strong>Fecha:</strong> ${rowData.fecha}</h6>
        </div>
      </div>
      <div class="table-responsive">
        <table id="tblDetalleTraslado" class="table table-bordered table-striped table-sm">
          <thead class="table-primary">
            <tr>
              <th class="text-center" style="width: 50px;">Tipo</th>
              <th>Código</th>
              <th>Descripción</th>
              <th>Laboratorio</th>
              <th class="text-end">Cantidad</th>
              <th class="text-center">Unidad</th>
              <th>Lote</th>
              <th>Vencimiento</th>
            </tr>
          </thead>
        <tbody>`;

    resp.forEach((item) => {

      let iconoTipo = '';
      if (parseInt(item.sw) == 2) {
        iconoTipo = '<i class="fa-solid fa-arrow-down text-success" style="font-size: 1.2rem;" title="Entrada"></i>';
      } else if (parseInt(item.sw) == 6) {
        iconoTipo = '<i class="fa-solid fa-arrow-up text-danger" style="font-size: 1.2rem;" title="Salida"></i>';
      }

      htmlTabla += `
        <tr>
          <td class="text-center">${iconoTipo}</td>
          <td><strong>${item.codigo}</strong></td>
          <td>${item.descripcion}</td>
          <td>${item.laboratorio || '-'}</td>
          <td class="text-end">${formatNum(item.cantidad, '', ConfGeneral)}</td>
          <td class="text-center">${item.unidad}</td>
          <td>${item.lote || '-'}</td>
          <td>${item.vmcto || '-'}</td>
        </tr>`;
    });

    htmlTabla += `
      </tbody>
      </table>
      </div>`;

    // Mostrar el modal
    let modal = document.getElementById('modalDetalleTraslado');
    if (!modal) {
      crearModalDetalleTraslado();
      modal = document.getElementById('modalDetalleTraslado');
    }

    document.getElementById('detalleTraslado').innerHTML = htmlTabla;
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();

    dissminSwal();

  } catch (e) {
    dissminSwal();
    Swal.fire('Error', 'Error al cargar los detalles del traslado', 'error');
  }
};

const imprimirTraslado = async (idTraslado) => {
  try {
    await ImprimirDocumento(3, idTraslado, 'Impresión de Traslado entre Bodegas', false);
  } catch (e) {
    Swal.fire('Error', 'Error al imprimir el traslado', 'error');
  }
};

const crearModalDetalleTraslado = () => {
  const modalHTML = `
    <div class="modal fade" id="modalDetalleTraslado" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-fullscreen" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Detalle del Traslado</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div id="detalleTraslado"></div>
          </div>
          <div class="modal-footer">
          
          </div>
        </div>
      </div>
    </div>`;

  $('body').append(modalHTML);
};

const imprimirDetalleTraslado = () => {
  const contenido = document.getElementById('detalleTraslado').innerHTML;
  const ventana = window.open('', 'Impresión', 'height=400,width=600');
  ventana.document.write('<html><head><title>Detalle del Traslado</title>');
  ventana.document.write('<link rel="stylesheet" href="../../assets/vendor/css/core.css">');
  ventana.document.write('<style>body { font-size: 0.85rem; } .table { margin-top: 20px; } @media print { .modal-footer { display: none; } }</style>');
  ventana.document.write('</head><body onload="window.print()">');
  ventana.document.write(contenido);
  ventana.document.write('</body></html>');
  ventana.document.close();
};

// Guardar traslado en JSON (para notificaciones)
const guardarTrasladoJSON = async (idTraslado) => {
  try {
    const resp = await enviarPeticion({
      link: '../models/TrasladosBodegas.php',
      op: 'guardar-traslado-json',
      id_traslado: idTraslado
    });
  } catch (e) {
    // Error silencioso
  }
}


//---Nuevo Christian 
$(async function () {
  permisos = await permisosUsuario(id_modulo);
  if (permisos.UtilizarTrasladoDirecto) {
    $(".activate-mod-direct").show()
    $(".mod-transito").hide()
  } else {
    $(".activate-mod-direct").hide()
    $(".mod-transito").show()
  }

  fechaActual([
    ['fecha_inicial', 'fecha_final'],
    ['fecha_ini', 'fecha_fin']
  ])
  //await validarAperturaCaja();
  await listarAlmacenes();
  await condicionesPago('', 'id_condicion');
  await tiposDocumentos(3, $("#bodegas").val(), 'tipos');
  const tercero = await serviceTerceroEmpresa();
  $("#bodegas").on('change', async function () {
    await cambiarAlmacenes();
  });
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
  await trasladosPendientes();
  /**fecha y lotes  */

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

  $("#procesar-traslados-pendientes-detalle").on('click', async function () {
    let items = new Array();

    $(".table-detalle-items-pendientes tbody tr").each(function () {

      const data = JSON.parse($(this).attr("data-element"))
      const cantidad_recibir = parseInt($(this).find(".cantidad-recibir").val());
      let id_det = data.id_det;

      if (cantidad_recibir > 0) {
        items.push({
          id_det: id_det,
          cantidad: cantidad_recibir
        });
      }

    });
    if (items.length > 0) {
      try {
        showLoadingWithTetrisLogo('Procesando traslado', 'ISA Software');
        const resp = await enviarPeticion({
          link: '../models/TrasladosBodegas.php',
          op: 'recibir_traslado',
          items: JSON.stringify(items),
          id_bodega_destino: $("#bodegas").val()
        });
        if (!resp.ok) {
          Swal.fire("Error", resp.message, "error")
          return;
        }
        $("#modalTrasladosPendientesDetalle").modal("hide")
        $("#modalTrasladosPendientes").modal("hide")
        $("#traslados-pendientes-detalle").html('');
        $("#traslados-pendientes").html('');
        dissminSwal()
        Swal.fire("Éxito", resp.message, "success")
      } catch (e) {
        Swal.fire("Error", "Error al procesar el traslado", "error")
        return;
      }
    } else {
      Swal.fire("Información", "No se han ingresado cantidades a recibir", "info")
    }

  });

  $("#recibir-todo-traslados-pendientes-detalle").on('click', function () {
    $(".table-detalle-items-pendientes tbody tr").each(function () {
      const data = JSON.parse($(this).attr("data-element"))
      const cantidad_enviada = parseInt(data.cantidad_enviada);
      const cantidad_recibida = parseInt(data.cantidad_recibida);
      const cantidad_pendiente = cantidad_enviada - cantidad_recibida;
      $(this).find(".cantidad-recibir").val(cantidad_pendiente)
    })
  })


  $("#traslados_pendientes").click(function () {
    trasladosPendientes();
  })

  $("#nuevo").click(async function () {
    nuevoPedido();
    $("#carrito tbody tr").remove()
  })

  $("#notas").focus(function () {
    $("#tmp").val($(this).val())
  })


  $("#guardar").click(async function (e) {

    showLoadingWithTetrisLogo('Guardando documento', 'ISA Software');

    //guardamos los pagos  
    let idTransaccion = true;
    const id_transaccion = $("#id_transaccion").val()

    try {
      const trasladoDirecto =permisos.UtilizarTrasladoDirecto ? 'S' : 'N';

      const respDoc = await enviarPeticion({
        link: '../shared/servicios/servicios.php',
        op: 'volcar_documento_traslado',
        id_transaccion,
        id_bodega_destino: $("#id_bodega_destino").val(),
        traslado_directo:trasladoDirecto
      });
      console.log({
        respDoc
      })
      if (!respDoc.ok) {

        if (respDoc.productos && respDoc.productos.length > 0) {
          let tabla = `
                      <table class="table table-sm table-borderless table-striped">
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
                      <td style="font-weight:bold" class="text-danger">${faltante}</td>
                    </tr>
                  `;
          });

          tabla += `</tbody></table>`;

          Swal.fire({
            icon: "error",
            title: respDoc.message,
            html: tabla,
            width: 800
          });


        } else {
          Swal.fire("Error", respDoc.message, "error");
        }
        return;
      }

      idTransaccion = respDoc.id;

      const respNoti =await  enviarPeticion({
          link: '../models/TrasladosBodegas.php',
          op:'enviar-notificacion',
          transaccion :'traslados',
          id_transaccion :idTransaccion
        });
        console.log({
          respNoti
        })

    } catch (error) { console.error({error})
      dissminSwal()
      Swal.fire("Ups", "Se genero un error al guardar el documento", "error")
      return;
    }

    dissminSwal()
    nuevoPedido();
    ImprimirDocumento(3, idTransaccion, 'Impresion de traslado', false);

  });

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

      $("#preciob_grid").val(formatNum(datosPrecio.preciob, '$', ConfGeneral))
      $("#precion_grid").val(formatNum(datosPrecio.precion, '$', ConfGeneral));

      const stock = mostrarStockDisponible($("#stock_real").val(),
        $("#und_grid option:selected").val(),
        $("#und_grid option:selected").text().split("-")[0])
      $("#stock_grid").val(stock);

      const totales = calcularTotal(unformatNum($("#preciob_grid").val()),
        $("#dto_grid").val(),
        $("#iva_grid").val(),
        $("#cantidad_grid").val());
      $("#total_grid").val(formatNum(totales.ttotal, '$', ConfGeneral))

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

        $("#preciob_grid").val(formatNum(datosPrecio.preciob, '$', ConfGeneral))
        $("#precion_grid").val(formatNum(datosPrecio.precion, '$', ConfGeneral));

        const stock = mostrarStockDisponible($("#stock_real").val(),
          $("#und_grid option:selected").val(),
          $("#und_grid option:selected").text().split("-")[0])
        $("#stock_grid").val(stock);

        const totales = calcularTotal(unformatNum($("#preciob_grid").val()),
          $("#dto_grid").val(),
          $("#iva_grid").val(),
          $("#cantidad_grid").val());
        $("#total_grid").val(formatNum(totales.ttotal, '$', ConfGeneral))

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

    $("#total_grid").val(formatNum(totales.ttotal, '$', ConfGeneral))


    if (e.key == 'Enter') {
      const refValida = validacionReferencia()

      if (!refValida) {
        return;
      }

      showLoadingWithTetrisLogo('Guardando item', 'ISA Software');
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
            id_bodega_destino: $("#id_bodega_destino").val(),
            notas: $("#notas").val(),
            valor_total: 0,
            iva: 0,
            descuento: 0,
            sw: 3,
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
        let error_stock = false;

        if ($("#id_detalle").val() == 0) {

          $("#carrito tbody tr").each(function () {

            const codigo = $.trim($(this).find("td").eq(0).html()).toUpperCase();

            if (ConfGeneral.ManejaFechaLote == 'S') {

              const lote = $.trim($(this).find("td").eq(10).html()).toUpperCase();
              // und_cargada = $.trim($(this).find("td").eq(7).html()).toUpperCase();

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

                  if (cantidad_agregada > $("#stock_sin_comp").val()) {
                    Swal.fire("Ups", "Stock insuficiente", "warning");
                    error_stock = true;
                    return;
                  }


                  // modifico los datos en las tablas 
                  $(this).find("td").eq(6).html(cantidad_agregada)
                  $(this).find("td").eq(11).html(formatNum(total_agregado, '$', ConfGeneral));

                } else if (opcion == 2) { // reemplazar
                  opc = 'update_transacciones_detalle_tmp'
                  sw_add = true
                  cantidad_agregada = $("#cantidad_grid").val();
                  und_cargada = $.trim($("#und_grid option:selected").text().split("-")[0]);
                  can_und_cargada = parseInt($("#und_grid option:selected").val());
                  total_agregado = (cantidad_agregada) * unformatNum($(this).find("td").eq(5).html());
                  if (cantidad_agregada > $("#stock_sin_comp").val()) {
                    Swal.fire("Ups", "Stock insuficiente", "warning");
                    error_stock = true;
                    return;
                  }
                  // // modifico los datos en las tablas 
                  $(this).find("td").eq(2).html(formatNum(preciob, '$', ConfGeneral));
                  $(this).find("td").eq(5).html(formatNum(precion, '$', ConfGeneral));
                  $(this).find("td").eq(6).html(cantidad_agregada);
                  $(this).find("td").eq(7).html(und_cargada);
                  $(this).find("td").eq(8).html(can_und_cargada);
                  $(this).find("td").eq(11).html(formatNum(total_agregado, '$', ConfGeneral));
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
                  if (cantidad_agregada > $("#stock_sin_comp").val()) {
                    Swal.fire("Ups", "Stock insuficiente", "warning");
                    error_stock = true;
                    return;
                  }
                  // modifico los datos en las tablas 
                  $(this).find("td").eq(6).html(cantidad_agregada)
                  $(this).find("td").eq(9).html(formatNum(total_agregado, '$', ConfGeneral));

                } else if (opcion == 2) { //Reemplazar
                  opc = 'update_transacciones_detalle_tmp'
                  sw_add = true
                  cantidad_agregada = $("#cantidad_grid").val();
                  und_cargada = $.trim($("#und_grid option:selected").text().split("-")[0]);
                  can_und_cargada = parseInt($("#und_grid option:selected").val());
                  total_agregado = (cantidad_agregada) * unformatNum($(this).find("td").eq(5).html());
                  if (cantidad_agregada > $("#stock_sin_comp").val()) {
                    Swal.fire("Ups", "Stock insuficiente", "warning");
                    error_stock = true;
                    return;
                  }
                  // // modifico los datos en las tablas 
                  $(this).find("td").eq(2).html(formatNum(preciob, '$', ConfGeneral));
                  $(this).find("td").eq(5).html(formatNum(precion, '$', ConfGeneral));
                  $(this).find("td").eq(6).html(cantidad_agregada);
                  $(this).find("td").eq(7).html(und_cargada);
                  $(this).find("td").eq(8).html(can_und_cargada);
                  $(this).find("td").eq(9).html(formatNum(total_agregado, '$', ConfGeneral));
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
            $("#id_detalle" + $("#id_detalle").val()).find("td").eq(11).html(formatNum(total, '$', ConfGeneral));
            $("#id_detalle" + $("#id_detalle").val()).find("td").eq(2).html(formatNum(preciob, '$', ConfGeneral));
            $("#id_detalle" + $("#id_detalle").val()).find("td").eq(5).html(formatNum(precion, '$', ConfGeneral));

          } else {
            $("#id_detalle" + $("#id_detalle").val()).find("td").eq(6).html(cantidad_agregada);
            $("#id_detalle" + $("#id_detalle").val()).find("td").eq(7).html(und_cargada);
            $("#id_detalle" + $("#id_detalle").val()).find("td").eq(8).html(can_und_cargada);
            $("#id_detalle" + $("#id_detalle").val()).find("td").eq(9).html(formatNum(total, '$', ConfGeneral));
            $("#id_detalle" + $("#id_detalle").val()).find("td").eq(2).html(formatNum(preciob, '$', ConfGeneral));
            $("#id_detalle" + $("#id_detalle").val()).find("td").eq(5).html(formatNum(precion, '$', ConfGeneral));

          }

        }

        if (!sw_add) {
          dissminSwal()
          return;
        }

        if (error_stock) {
          Swal.fire("Ups", "Stock insuficiente!", "error");
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

        itemCart.total = formatNum(total, '$', ConfGeneral)

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
        Swal.fire("Error", e.message ? e?.responseText : 'Se genero un error en la aplicacion', "error")
      }

    }
  })

  $("#filtro").on('keyup', async function (e) {

    const filtro = $.trim($(this).val())
    if (filtro.length == 0) { return; }
    if (e.key == 'Enter') {
      const resp = await modalBusquedaRef.getRecords({
        data: filtro,
        cod_barras: $("#cod_barras").is(":checked"),
        bodega: $("#bodegas").val()
      });
      const nregistros = modalBusquedaRef.getNrecords()

      if (nregistros > 0) {

        if (nregistros == 1) {

          if (ConfGeneral.ManejaFechaLote == 'N') {
            await cargarItemGrid(resp[0]);
          } else {
            if (ConfGeneral.ManejaFIFO == 'N') {
              await cargarLote(resp[0]);
              return;
            } else {
              await cargarItemGrid(resp[0]);
              $(this).val('')
            }
          }

        } else {
          modalBusquedaRef.show();
        }
      }
    }
  });


  $('#txtBusquedaTercero').keyup(function () {
    terceroSel = $.trim($(this).val())

    if (terceroSel.length == 0) {

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
        response([]);
      }
    },
    minLength: 3,
    select: async function (event, ui) {
      $("#nit_tmp").val(ui.item.nit)
    }
  });


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

    if ($("#id_bodega_destino").val() == '' || $("#id_bodega_destino").val() == '0') {
      //    #tabs button[data-bs-target="#navs-creacion"]
      var trigger = document.querySelector('#tabs button[data-bs-target="#navs-tercero"]');
      var tab = new bootstrap.Tab(trigger);
      tab.show();
      $('#tabs button[data-bs-target="#navs-tercero"]').trigger("click");
      Swal.fire("Ups", "Debe seleccionar una bodega destino", "info");;
      return;
    }

  })

  $("#txtBusquedaTercero").focus();

  $("#buscar-traslados").click(async function () {
    await consultarTraslados();
  });

  // Event listeners para los filtros
  $("#filtro_bodega_origen, #filtro_bodega_destino, #filtro_tipo_movimiento").on('change', function () {
    aplicarFiltros();
  });

  $("#btnAutoTraslados").click(function () {
    $("#modalTrasladosAuto").modal('show');
  });

  $("#btn-busqueda").click(function () {
    consultarSugerido();
  })
  $("#btn-limpiar").click(function () {
    limpiarSugerido();
  });
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });
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
    let currentRow = $(".data-lotes tbody tr.focus");

    if (e.key === 'ArrowDown') {

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