const id_modulo = '39';
let confMod;
let carrito_enc = [];
let carrito_det = [];
let permisos = [];
let data_tipos = '';
let und_default = [];
let tmpPrecioEdicion = 0;
let conceptosRetencion = [];
let modalBusquedaRef = null;
let buscando = false;
//let soloStock = false;
let perfilTributarioRetenciones = {
  ret_fte: 'N',
  ret_cree: 'N',
  ret_ica: 'N',
  ret_iva: 'N'
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
    $("#bodegas").val($("#id_bodega_session").val()).attr("disabled", true);
  } catch (e) {
    console.error(e)
    $("#result").html(ShowAlert('Error interno de la aplicación', 'error'))
  }
};

const InitRenderTableCart = () => {
  if (ConfGeneral) {

    let btnEditPrecio = '';
    if (permisos?.CambiarPrecio) {
      btnEditPrecio = `<span id="basic-default-password2" class="input-group-text cursor-pointer btn btn-sm btn-outline-primary btn-editar-precio"
                            ><i class="bx bx-pencil"></i
                          ></span>`;
    }

    if (ConfGeneral.ManejaFechaLote == 'S' && ConfGeneral.ManejaFIFO == 'N') {
      tb_ref = `
            <table  width="100%" class=" table-data-referencia" cellpadding="2" cellspacing="0" >
              <thead>
                <tr>
                  <th><b>Código</b></th>
                  <th><b>Precio b</b></th>
                  <th><b>Dto</b></th>
                  <th><b>Iva</b></th>
                  <th><b>Precio n.</b></th>
                  <th><b>Lote</b></th> 
                  <th><b>Vcmto</b></th>
                  <th rowspan="2"> <select class="form-control p-0 data-grid" id="und_grid" style="height: 60px;width: 120px;"  multiple ></select></th>
                  <th width="15%"><b>Cant</b></th>
                  <th><b>Total</b></th>
                </tr>
                <tr>
                  <td><input type="text" size="5"  class="form-control form-control-sm data-grid " id="codigo_grid" readonly></td>
                  <td><input type="text" class="form-control form-control-sm data-grid" id="preciob_grid" readonly></td>
                  <td><input type="text" class="form-control form-control-sm data-grid" id="dto_grid" size="4" readonly></td>
                  <td><input type="text" class="form-control form-control-sm data-grid" id="iva_grid" size="4" readonly></td>
                  <td>
                    <div class="input-group">
                      <input type="text" class="form-control form-control-sm data-grid" id="precion_grid" readonly>
                      ${btnEditPrecio}
                    </div>
                  </td>
                  <td><input type="text" class="form-control form-control-sm data-grid" id="lote_grid" readonly></td>
                  <td><input type="date" size="6" class="form-control form-control-sm data-grid" id="vcmto_grid" readonly></td>
                  <td width="15%"><input type="number" min="1"  class="form-control form-control-sm table-primary"data-grid id="cantidad_grid" ></td>
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
                        <th>Precio b</th>
                        <th  class="text-center">Dcto</th>
                        <th  class="text-center">Iva</th>
                        <th>Precio n</th>
                        <th  class="text-center">Cant</th>
                        <th  class="text-center">Cant desp</th>
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
                  <th><b>Precio n.</b></th>
                  <th rowspan="2"> <select class="form-control p-0 data-grid" id="und_grid" style="height: 60px;width: 120px;"  multiple ></select></th>
                  <th width="10%"><b>Cant</b></th>
                  <th><b>Total</b></th>
                </tr>
                <tr>
                  <td><input type="text" size="5"  class="form-control form-control-sm data-grid " id="codigo_grid" readonly></td>
                  <td><input type="text" class="form-control form-control-sm data-grid" id="preciob_grid" readonly></td>
                  <td><input type="text" class="form-control form-control-sm data-grid" id="dto_grid" size="4" readonly></td>
                  <td><input type="text" class="form-control form-control-sm data-grid" id="iva_grid" size="4" readonly></td>
                  <td>
                    <div class="input-group">
                      <input type="text" class="form-control form-control-sm data-grid" id="precion_grid" readonly>
                      ${btnEditPrecio}
                    </div>
                  </td>
                  <td width="10%"><input type="number" min="1"  class="form-control form-control-sm table-primary"data-grid id="cantidad_grid"></td>
                  <td><input type="text" class="form-control form-control-sm data-grid" id="total_grid" readonly></td>
                </tr>
              </thead>
           </table>
      `
      let tableHead = `
              <table class="table table-sm display nowrap" style="width: 100%" width="100%" id="carrito">
                  <thead class="table-primary" >
                      <tr>
                        <th>Código</th>
                        <th>Descripción</th>
                        <th>Precio b</th>
                        <th  class="text-center">Dcto</th>
                        <th  class="text-center">Iva</th>
                        <th>Precio n</th>
                        <th  class="text-center">Cant</th>
                        <th  class="text-center">Cant desp</th>
                        <th  class="text-center">Und</th>
                        <th  class="text-center">Conv</th>
                        <th>Total</th>
                        <th></th>
                        <th></th>`;


      tableHead += `</tr>
                    </thead>
                  <tbody>
                  </tbody>
            </table>  
          `
      $("#dvReferencias").html()
    }
    $(".div-table-data-referencia").html(tb_ref);
  }
};

const calcularValorPedido = () => {

  let total_iva = 0;
  let total_dto = 0;
  let total = 0;
  let total_bruto = 0;

  $("#carrito tbody tr").each(function () {
    tr = $(this);
    const cantidad = $.trim(tr.find("td.carrito-cantidad2").text());
    const cantidad_pedida = $.trim(tr.find("td.carrito-cantidad").text());
    const valor_unitario = $.trim(tr.find("td.carrito-valor-unitario").text());
    const dto = $.trim(tr.find("td.carrito-dto").text());
    const iva = $.trim(tr.find("td.carrito-iva").text());
    const can_und = $.trim(tr.find("td.carrito-cantidad-und").text());

    total_iva += ((valor_unitario * (1 - (dto / 100))) * ((iva / 100))) * (cantidad * can_und);
    total_dto += (valor_unitario * ((dto / 100))) * (cantidad * can_und);
    total += ((valor_unitario * (1 - (dto / 100))) * ((iva / 100) + 1)) * (cantidad * can_und);
    total_bruto += total - total_iva + total_dto;

    if (cantidad == cantidad_pedida) {
      tr.find("td").addClass("table-success")
    } else {
      tr.find("td").removeClass("table-success")
    }
  });
  $("#t_bruto").val(formatNum(total_bruto, '$', ConfGeneral))
  $("#t_dcto").val(formatNum(total_dto, '$', ConfGeneral))
  $("#t_iva").val(formatNum(total_iva, '$', ConfGeneral))
  $("#t_total").val(formatNum(total, '$', ConfGeneral))

};

const listarLotesPedidos = async (id_det, editable = false) => {
  try {
    const resp = await enviarPeticion({
      link: '../models/Facturacion.php',
      op: 'listarLotesPedidos',
      id_det
    })
    if (resp.length == 0) {
      dissminSwal()
      Swal.fire("Ups", "No se encontraron resultados", "error")
      return;
    }

    let tb = `
    <table class="table table-sm table-items-pedidos-lotes">
      <thead>
        <tr>
           <th>Lote</th>
           <th>Vencimiento</th>
          `;

    if (editable) {
      tb += ` <th>Cantidad</th>
              <th>Stock</th>`
    } else {
      tb += `<th>Cantidad</th>`
    }
    tb += `</tr>
      </thead>
      <tbody>
   `;

    resp.forEach(item => {

      tb += `
        <tr id="tr_item_lotes_pedidos_${item.id}">
          <td class="table-success">${item.lote}</td>
          <td>${item.vencimiento}</td>
        `

      if (editable) {
        tb += `
         <td class="lotes-pedidos-cantidad"><input type="number" data-id ="${item.id}" value="${item.cantidad}" class="form-control form-control-sm input-item-pedidos-lotes"  /></td>
         <td class="lotes-pedidos-stock">${item.stock}</td>
         <td class=""><button class="btn btn-sm btn-outline-danger btn-item-lotes-pedidos" data-element=${JSON.stringify(item)}><i class="fa fa-trash"></i></button></td>
         `
      } else {
        tb += `<td>${item.cantidad}</td>`
      }

      tb += `</tr>`
    });

    tb += `</tbody>
   </table>`
    $("#resultLotesPedidos").html(tb)
    $("#modalLotesPedidos").modal("show")
    dissminSwal();

    $(".btn-item-lotes-pedidos").click(function () {

      const element = JSON.parse($(this).attr("data-element"));

      Swal.fire({
        title: "Esta seguro de eliminar la cantidad del lote?",
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
              link: '../models/Facturacion.php',
              id: element.id,/** este es el id de t_pedidos_lotes */
              id_detalle_pedido: element.id_detalle_pedido,
              op: 'delete-item-lotes-pedidos',
              id_referencia: element.id_referencia
            });
            console.log({
              resp
            })
            if (!resp.ok) {
              Swal.fire("Ups", "Error al eliminar el pedido", "error")
              return;
            }

            display = resp.n_lotes > 0 ? 'block' : 'none'

            $("#id_detalle" + element.id_detalle_pedido)
              .find("td.carrito-pedidos-lotes")
              .find("div")
              .find("button.btn-pedidos-lotes")
              .find("span")
              .text(resp.n_lotes)
              .css("display", display)

            const can_und = parseInt($("#id_detalle" + element.id_detalle_pedido)
              .find("td.carrito-cantidad-und").text());

            cantidad = resp.cantidad / can_und;

            const precio_neto = unformatNum($("#id_detalle" + element.id_detalle_pedido).find("td.carrito-precio-neto").text())
            total = precio_neto * cantidad;
            $("#id_detalle" + element.id_detalle_pedido).find("td.carrito-total").text(formatNum(total, '$', ConfGeneral))
            $("#id_detalle" + element.id_detalle_pedido).find("td.carrito-cantidad2").text(cantidad);

            $("#tr_item_lotes_pedidos_" + element.id).remove();

            if (display == 'none') {
              $("#modalLotesPedidos").modal("hide")
            }
          } catch (e) {
            console.error(e)
            Swal.fire("Error", "Error interno en el sistema", "error")
          }
        }// if (result.isConfirmed)  {
      })

    });

    $(".input-item-pedidos-lotes").focus(function () {
      $("#tmp").val($(this).val())
    })

    $(".input-item-pedidos-lotes").change(async function () {
      const cant = $(this).val();

      if (isNaN(parseInt(cant)) || cant == 0) {
        $(this).val($("#tmp").val());
        return;
      }
      const id = $(this).attr("data-id");

      if (id == undefined || isNaN(parseInt(id))) {
        Swal.fire("Ups", "No se detecto la data", "warning")
        return;
      }

      const cantidad_pedida = parseInt($("#id_detalle" + id_det).find("td:eq(6)").html());

      let suma_cant = 0;

      $(".table-items-pedidos-lotes tbody tr").each(function () {
        const tr = $(this)
        cantidad = parseInt(tr.find("td:eq(2)").find("input").val())

        if (!isNaN(cantidad)) {
          suma_cant += parseInt(cantidad)
        }
      })


      if (suma_cant > cantidad_pedida) {
        $(this).val($("#tmp").val());
        Swal.fire("Ups", "La cantidad a facturar no puede ser mayor a la cantidad pedida", "warning")
        return;
      }
      try {
        const resp = await enviarPeticion({
          op: 'update-item-pedidos-lotes',
          link: '../models/Facturacion.php',
          id,
          cantidad: cant,
          id_pedido: $("#id_pedido").val()
        })

        if (!resp.ok) {
          Swal.fire("Ups", resp.message, "error")
          return;
        }

        Swal.fire("Ok", resp.message, "success")



        $("#id_detalle" + id_det).find("td:eq(7)").html(suma_cant)
        const precion = unformatNum($("#id_detalle" + id_det).find("td.carrito-precio-neto").html());
        total = precion * suma_cant;
        $("#id_detalle" + id_det).find("td.carrito-total").html(formatNum(total, '$', ConfGeneral))
        // $("#id_detalle"+id_det).find("td:eq(6)").html(cant)
        $("#tmp").val("")
      } catch (e) {
        Swal.fire("Error", "Ups ha ocurrido un error interno", "error")
        console.error(e)
      }


    })

  } catch (error) {
    dissminSwal()
    console.error(error)
  }

};

const addCart = (item) => {

  let disabledBtn = '';
  let cantidad2 = 0;

  const numero_pedido = isNaN(parseInt($("#numero_pedido").val())) ? 0 : $("#numero_pedido").val();
  if (numero_pedido > 0) {
    disabledBtn = 'disabled'
    cantidad2 = item.cantidad2
  } else {
    cantidad2 = item.cantidad;
  }
  tabindex = $("#carrito tbody tr").length + 1;

  // si existe pedido no muestro el boton de eliminar
  let btnDelete = `<button class="btn btn-sm btn-light border border-1 border-danger" ${disabledBtn}  onclick="eliminarItem(${item.id})"><i class="fa-regular fa-trash-can text-danger"></i></button>`

  if ($("#id_pedido").val() > 0) {
    btnDelete = '';
  }

  let tr = `
 <tr id="id_detalle${item.id}" tabindex="${tabindex}">
    <td class="carrito-codigo">${item.codigo}</td>
    <td class="carrito-descripcion">${item.descripcion}-${item.laboratorio}</td>
    <td class="carrito-precio_bruto">${item.preciob}</td>
    <td  class="text-center carrito-dto">${item.dto}</td>
    <td  class="text-center carrito-iva">${item.iva}</td>
    <td class="carrito-precio-neto">${item.precion}</td>
    <td  class="text-center carrito-cantidad">${item.cantidad}</td>
    <td  class="text-center carrito-cantidad2">${cantidad2}</td>
    <td  class="text-center carrito-und">${item.und}</td>
    <td class="text-center carrito-cantidad-und">${item.can_und}</td>
    <td class="carrito-vcmto">${item.vmcto}</td>
    <td class="carrito-lote">${item.lote}</td>
    <td class="carrito-total">${item.total}</td>
    <td class="carrito-pedidos-lotes">
     <div class="btn-group btn-group-sm btn-light">
          ${btnDelete}
          <button class="btn btn-sm btn-light border border-1 border-primary" onclick="editarPedido(${item.id})"><i class="fa fa-pencil text-primary "></i></button> 
   
   `;

  if (numero_pedido > 0) {
    if (ConfGeneral.ManejaFechaLote == 'S') {
      if (ConfGeneral.ManejaFIFO == 'N') {
        const n_lotes_pedidos = item?.n_lotes_pedidos != undefined ? item.n_lotes_pedidos : 0

        if (n_lotes_pedidos > 0) {
          clase_d_none = ''
        } else {
          clase_d_none = 'd-none'
        }

        tr += `
           <button type="button" data-id="${item.id}" class="btn btn-sm btn-outline-success position-relative btn-pedidos-lotes">
              <i class="fa-solid fa-boxes-stacked"></i>
              <span class="position-absolute ${clase_d_none} top-0 start-100 translate-middle badge rounded-pill bg-danger">
                <span class="visually-hidden"></span>${n_lotes_pedidos}
              </span>
          </button>  
         </div> 

          `
      } else {
        tr += ` </div> </td>`
      }
    } else {
      tr += ` </div> </td>`
    }
  } else {
    tr += ` </div> </td>`
  }

  tr += `
    <td class="d-none carrito-id-detalle">${item.id}</td>
    <td class="carrito-valor-unitario d-none">${item.valor_unitario}</td>
  </tr>`
  $("#carrito tbody").append(tr);

  $(".btn-pedidos-lotes").click(function () {
    id_det = $(this).attr("data-id");
    listarLotesPedidos(id_det, false);
  })
  disabledEnabledTercero(true);
};

const recuperarPedido = async (data) => {
  showLoadingWithTetrisLogo('Cargando pedido', 'ISA Software')

  try {
    nuevoPedido()
    //carga los datos del tercero
    item = data;
    let direccionesCargadas = await cargarDirecciones(item.id_tercero);
    if (!direccionesCargadas) {
      return;
    }

    $("#numero_pedido").val('')
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
    $("#id_tercero_dir").val(item.id_tercero_dir)
    $('#tabs button[data-bs-target="#navs-creacion"]').tab('show')
    $('#tabs button[data-bs-target="#navs-creacion"]').attr('disabled', false);
    $('#id_condicion').val(item.id_condicion_pago);
    $('#tipos').val(item.id_tipo_documento);

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
      itemCart.preciob = formatNum(dataPrecio.preciob, '$', ConfGeneral)
      itemCart.dto = item.pcj_descuento
      itemCart.iva = item.pcj_iva
      itemCart.precion = formatNum(dataPrecio.precion, '$', ConfGeneral)
      itemCart.cantidad = item.cantidad / item.can_und
      itemCart.und = item.und
      itemCart.can_und = item.can_und
      itemCart.valor_unitario = item.valor_unitario
      itemCart.vmcto = item.vmcto
      itemCart.lote = item.lote

      itemCart.total = formatNum(Math.round(dataPrecio.precion * (item.cantidad / item.can_und)), '$', ConfGeneral)
      await addCart(itemCart)
      limpiarReferencia();
    })
    dissminSwal()
    $("#filtro").focus()


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
      //nuevoPedido();
    }
  });

};

const insertarEncabezado = async () => {

  const listaAlm = parseInt($("#lista_almacen").val()) || 0;
  const listaTer = parseInt($("#lista_tercero").val()) || 0;
  const lista_precios = listaTer === 0 ? listaAlm : listaTer;

  return await enviarPeticion({
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
    id_tercero_dir: $("#id_tercero_dir").val(),
    id_condicion: $("#id_condicion").val(),
    zona_ventas: $.trim($("#info_zona_venta").text()),
    zona_descripcion: $.trim($("#info_zona_descripcion").text()),
    valor_aplicado: 0,
    es_credito: 1,
    lista: lista_precios
  });
};

const insertarDetallePedido = async (cantidad2) => {
  return await enviarPeticion({
    link: '../shared/servicios/servicios.php',
    op: 'add_pedido_detalle',
    id_pedido: $("#id_pedido").val(),
    id_referencia: $("#id_referencia").val(),
    cantidad: $("#cantidad_grid").val() * parseInt($("#und_grid option:selected").val()),
    valor_unitario: $("#precio_unitario").val(),
    costo_unitario: $("#costo_promedio").val(),
    pcj_iva: $("#iva_grid").val(),
    pcj_descuento: $("#dto_grid").val(),
    und: $.trim($("#und_grid option:selected").text().split("-")[0]),
    can_und: parseInt($("#und_grid option:selected").text().split("-")[1]),
    sw: 8,
    id_bodega: $("#bodegas").val(),
    cantidad2: cantidad2
  });


};

const agregarCantidad2 = async () => {

  const cantidad = isNaN(parseInt($("#cantidad_grid").val())) ? 0 : $("#cantidad_grid").val();

  if (cantidad > 0) {

    if ($("#id_detalle").val() > 0) {

      const itemtr = $("#id_detalle" + $("#id_detalle").val());
      const cantidad_pedido = itemtr.find("td.carrito-cantidad").text();
      const precio_neto = unformatNum(itemtr.find("td.carrito-precio-neto").text());
      const total = precio_neto * cantidad;
      if (!permisos.AgregarMayorCantidadPedida) {
        if (cantidad > cantidad_pedido) {
          dissminSwal();
          limpiarReferencia()
          Swal.fire("Ups", "La cantidad despachada no puede ser mayor a la pedida", "error")
          $("#filtro").focus()
          return;
        }
      }

      try {

        const resp = await enviarPeticion({
          link: '../models/Facturacion.php',
          op: 'update-cantidad2',
          id: $("#id_detalle").val(),
          cantidad2: cantidad * parseInt($("#und_can option:selected").text().split("-")[1]),
          cantidad_lote: cantidad * parseInt($("#und_can option:selected").text().split("-")[1]),
          id_pedidos_lotes: $("#id_pedidos_lotes").val(),
          ManejaFechaLote: ConfGeneral.ManejaFechaLote,
          ManejaFIFO: ConfGeneral.ManejaFIFO,
          id_referencia: $("#id_referencia").val()
        })

        if (!resp.ok) {
          dissminSwal();
          Swal.fire("Ups", "Error al actualizar la cantidad", "error")
          return;
        }
        itemtr.find("td.carrito-cantidad2").text(cantidad);
        itemtr.find("td.carrito-total").text(formatNum(total, '$'));

        itemtr.find("td.carrito-pedidos-lotes").find("button").find("span").text(resp.pedidos_lotes.length);
        if (resp.pedidos_lotes.length == 0) {
          itemtr.find("td.carrito-pedidos-lotes").find("button").find("span").addClass("d-none")
        } else {
          itemtr.find("td.carrito-pedidos-lotes").find("button").find("span").removeClass("d-none")
        }
        calcularValorPedido();
        limpiarReferencia();
        return;

      } catch (error) {
        console.error(error)
        dissminSwal()
        Swal.fire("Error", "Ocurrio un error al actualizar la cantidad", "error")
      }

    }//id_detalle>0

    let existe = false;
    $("#carrito tbody tr").each(async function () {
      const tr = $(this);
      const codigo = $.trim(tr.find("td.carrito-codigo").text())

      if (codigo == $.trim($("#codigo_grid").val())) {
        tr.focus();
        existe = true;
        const cantidad2 = $.trim(tr.find("td.carrito-cantidad2").text())
        const cantidad_pedido = parseInt($.trim(tr.find("td.carrito-cantidad").text()));
        const new_can = parseInt(cantidad2) + parseInt(cantidad);
        const id_det = $.trim(tr.find("td.carrito-id-detalle").html());
        const can_und = $.trim(tr.find("td.carrito-cantidad-und").text())

        if (!permisos.AgregarMayorCantidadPedida) {
          if (new_can > cantidad_pedido) {
            dissminSwal();
            limpiarReferencia()
            Swal.fire("Ups", "La cantidad despachada no puede ser mayor a la pedida", "error")
            $("#filtro").focus()
            return;
          }
        }

        const precio_neto = unformatNum(tr.find("td.carrito-precio-neto").text());
        const total = precio_neto * new_can;
        const data = {
          id_det,
          cantidad,
          precio_neto,
          total,
          tr,
          new_can,
          can_und,
          id_referencia: $("#id_referencia").val()
        }

        if (ConfGeneral.ManejaFechaLote == 'S') {
          if (ConfGeneral.ManejaFIFO == 'N') {
            data.id_lote = $("#id_lote").val()
            data.vencimiento = $("#vcmto_grid").val()
            data.lote = $("#lote_grid").val()
          } else {
            data.id_lote = 0
            data.vencimiento = '1900-01-01'
            data.lote = '0'
          }

        } else {
          data.id_lote = 0
          data.vencimiento = '1900-01-01'
          data.lote = '0'
        }
        console.error({ data })
        await actualizacionCantidad2Pedido(data);
        await limpiarReferencia();
        await dissminSwal();
        setTimeout(() => {
          $("#filtro").focus()
        }, 500);

        return;
      }
    });

    if (!existe) {

      if (!permisos.FacturarPoductosDiferentes) {
        //no tiene permisos para agregar productos diferentes a lo pedido
        Swal.fire("Error", "No tienes permiso para agregar productos diferentes al pedido", "error")
        refValida = false;
        return;
      }
      //agrego el item a el pedido y actualizo la cantidad despachada y loa grego al carrito
      //inserto el detalle 
      let cantidad2 = 0;
      cantidad2 = $("#cantidad_grid").val() * parseInt($("#und_grid option:selected").val());

      const datosDetalle = await insertarDetallePedido(cantidad2);

      if (datosDetalle.error) {
        dissminSwal()
        Swal.fire("Error", "No se pudo insertar el producto, por favor intente nuevamente!", "error")
        return;
      }

      const itemCart = {
        codigo: $("#codigo_grid").val(),
        descripcion: $.trim($("#descripcion_grid").val()),
        laboratorio: $.trim($("#laboratorio_grid").val()),
        preciob: $("#preciob_grid").val(),
        dto: $("#dto_grid").val(),
        iva: $("#iva_grid").val(),
        precion: $("#precion_grid").val(),
        cantidad: $("#cantidad_grid").val(),
        cantidad2,
        und: $.trim($("#und_grid option:selected").text().split("-")[0]),
        can_und: parseInt($("#und_grid option:selected").text().split("-")[1]),
        id: datosDetalle.data.id,
        valor_unitario: $("#precio_unitario").val(),
        total: $("#total_grid").val()
      }
      addCart(itemCart);
    }

    /// actualizarTotales() 
  }
};

const actualizacionCantidad2Pedido = (data) => {

  enviarPeticion({
    link: '../models/Facturacion.php',
    op: 'update-cantidad2',
    id_det: data.id_det,
    cantidad: data.new_can,
    can_und: data.can_und,
    ManejaFechaLote: ConfGeneral.ManejaFechaLote,
    ManejaFIFO: ConfGeneral.ManejaFIFO,
    id_lote: data.id_lote,
    vencimiento: data.vencimiento,
    lote: data.lote,
    cantidad_lote: data.cantidad * data.can_und,
    id_pedido: $("#id_pedido").val(),
    id_referencia: data.id_referencia
  })
    .then(resp => {

      if (resp.ok) {
        data.tr.find("td.carrito-cantidad2").text(data.new_can);
        data.tr.find("td.carrito-total").text(formatNum(data.total, '$'))

        if (ConfGeneral.ManejaFechaLote == 'S') {
          if (ConfGeneral.ManejaFIFO == 'N') {
            data.tr.find("td.carrito-pedidos-lotes").find("button.btn-pedidos-lotes").find("span").text(resp.pedidos_lotes);
            if (data.cantidad_lote == 0) {
              data.tr.find("td.carrito-pedidos-lotes").find("button.btn-pedidos-lotes").find("span").addClass("d-none")
            } else {
              data.tr.find("td.carrito-pedidos-lotes").find("button.btn-pedidos-lotes").find("span").removeClass("d-none")
            }
          }
        }

        calcularValorPedido()
        return;
      }
    })
    .catch(e => {
      console.error(e)
    })
};

const agregarProducto = async (cantidad, e) => {

  if (isNaN(parseInt(cantidad)) || cantidad == 0) {
    return;
  }

  const totales = calcularTotal(unformatNum($("#precio_unitario").val()),
    $("#dto_grid").val(),
    $("#iva_grid").val(),
    cantidad * $("#und_grid option:selected").val());

  $("#total_grid").val(formatNum(totales.ttotal, '$', ConfGeneral))

  //validacion del vendedor 
  const validacionVen = await validacionVendedor();

  if (!validacionVen) {
    return;
  }

  if (e?.key == 'Enter') {

    const refValida = validacionReferencia()

    if (!refValida) {
      return;
    }
    showLoadingWithTetrisLogo('Guardando item', 'ISA Software');
    try {
      const numero_pedido = $("#numero_pedido").val()
      let sw_add = true;

      if (!isNaN(numero_pedido) && numero_pedido > 0) {
        // valido si manejo fecha y no manejo fifo 
        agregarCantidad2()
        return;
      }

      if (isNaN(parseInt($("#numero").val())) || $("#numero").val() == 0) {
        const datosEncabezado = await insertarEncabezado()

        if (datosEncabezado.error) {
          dissminSwal()
          Swal.fire("Error", "No se puedo insertar el documento", "error")
          return;
        }
        $("#id_transaccion").val(datosEncabezado.data.id);
        $("#numero").val(datosEncabezado.data.numero)
      }

      if ($("#id_transaccion").val() == 0 || isNaN(parseInt($("#id_transaccion").val()))) {
        Swal.fire("Ups", "No se encontro id de trnaasaccion", "error")
        return;
      }
      //insertar
      if ($("#id_detalle").val() == 0) {

        let existe = false;
        const filas = $("#carrito tbody tr").toArray(); // Convertir a array para usar for...of

        for (const fila of filas) {
          const $fila = $(fila);
          const codigo = $.trim($fila.find("td.carrito-codigo").eq(0).text()).toUpperCase();
          const lote = $.trim($fila.find("td.carrito-lote").text()).toUpperCase();
          const stock_real = $("#stock_real").val()
          const und_agregada_fila = $.trim($fila.find("td.carrito-und").text()).toUpperCase()
          if (
            codigo === $.trim($("#codigo_grid").val()).toUpperCase() &&
            lote === $.trim($("#lote_grid").val()).toUpperCase() &&
            und_agregada_fila == $.trim($("#und_grid option:selected").text().split("-")[0]).toUpperCase()
          ) {
            const cantidad_grid = parseInt($("#cantidad_grid").val());
            const cantidad_agregada = parseInt($fila.find("td.carrito-cantidad").text());

            const new_cantidad = cantidad_agregada + cantidad_grid;
            const total = new_cantidad * parseFloat(unformatNum($fila.find("td.carrito-precio-neto").text()));

            if (stock_real < cantidad_grid) {
              limpiarReferencia()
              Swal.fire("Ups", "No hay stock suficiente", "error");

              existe = true;
              break; // ⛔ se detiene el for
            }

            const can_und = $fila.find("td.carrito-cantidad-und").text();

            const update = await enviarPeticion({
              op: 'update_transacciones_detalle_tmp',
              link: '../shared/servicios/servicios.php',
              cantidad: new_cantidad * can_und,
              id: parseInt($fila.find("td.carrito-id-detalle").text()),
              id_transaccion: $("#id_transaccion").val()
            });

            if (update.error) {
              dissminSwal();
              Swal.fire("Error", "No se pudo insertar el producto, por favor intente nuevamente!", "error");
              return;
            }

            await actualizarTotales(update.data.totales[0]);
            $fila.find("td.carrito-cantidad").text(new_cantidad);
            $fila.find("td.carrito-total").text(formatNum(total, '$', ConfGeneral));
            $fila.find("td").addClass("table-info")
            $fila.focus()
            setTimeout(function () {
              $fila.find("td").removeClass("table-info")
            }, 1500)
            if (isNaN(parseInt($("#numero_pedido").val())) || $("#numero_pedido").val() == '0') {
              $fila.find("td.carrito-cantidad2").text(new_cantidad);
            }

            dissminSwal();
            limpiarReferencia();
            setTimeout(function () {
              $("#filtro").focus();
            }, 400);

            existe = true;
            break; // Ya lo encontró, no sigue buscando
          }
        }//for

        if (existe) {
          return;
        }

        if (!existe) {
          //inserto la referencia 

          const datosDetalle = {
            link: '../shared/servicios/servicios.php',
            op: 'add_transacciones_detalle_tmp',
            id_transaccion: $("#id_transaccion").val(),
            id_referencia: $("#id_referencia").val(),
            cantidad: $("#cantidad_grid").val() * parseInt($("#und_grid option:selected").text().split("-")[1]),
            valor_unitario: $("#precio_unitario").val(),
            costo_unitario: $("#costo_promedio").val(),
            id_lote: isNaN(parseInt($("#id_lote").val())) ? 0 : $("#id_lote").val(),
            pcj_iva: $("#iva_grid").val(),
            pcj_descuento: $("#dto_grid").val(),
            und: $.trim($("#und_grid option:selected").text().split("-")[0]),
            can_und: parseInt($("#und_grid option:selected").text().split("-")[1]),
            vmcto: $.trim($("#vcmto_grid").val()) == '' ? '1900-01-01' : $("#vcmto_grid").val(),
            lote: $.trim($("#lote_grid").val()),
            sw: 5,
            id_bodega: $("#bodegas").val(),
            id_pedido: $("#id_pedido").val(),
            id: 0
          }

          const insertarDetalle = await enviarPeticion(datosDetalle)

          if (insertarDetalle.error) {
            dissminSwal()
            Swal.fire("Error", "No se pudo insertar el producto, por favor intente nuevamente!", "error")
            return;
          }

          await actualizarTotales(insertarDetalle.data.totales[0]);

          await addCart({
            id: insertarDetalle.data.id,
            codigo: $("#codigo_grid").val(),
            descripcion: $("#descripcion_grid").val(),
            laboratorio: $("#laboratorio_grid").val(),
            preciob: $("#preciob_grid").val(),
            dto: $("#dto_grid").val(),
            iva: $("#iva_grid").val(),
            precion: $("#precion_grid").val(),
            cantidad: $("#cantidad_grid").val(),
            cantidad_desp: $("#cantidad_grid").val() * parseInt($("#und_grid option:selected").text().split("-")[1]),
            und: $.trim($("#und_grid option:selected").text().split("-")[0]),
            can_und: parseInt($("#und_grid option:selected").text().split("-")[1]),
            vmcto: $.trim($("#vcmto_grid").val()) == '' ? '1900-01-01' : $("#vcmto_grid").val(),
            lote: $.trim($("#lote_grid").val()),
            total: $("#total_grid").val(),
            valor_unitario: $("#precio_unitario").val(),
          })
          dissminSwal()
          limpiarReferencia()
          setTimeout(function () {
            $("#filtro").focus();
          }, 400)
        }// existe !
      } else {

        //actualizar la referencias 
        const datosDetalle = {
          link: '../shared/servicios/servicios.php',
          op: 'update_transacciones_detalle_tmp',
          id_transaccion: $("#id_transaccion").val(),
          id_referencia: $("#id_referencia").val(),
          cantidad: $("#cantidad_grid").val() * parseInt($("#und_grid option:selected").text().split("-")[1]),
          valor_unitario: $("#precio_unitario").val(),
          costo_unitario: $("#costo_promedio").val(),
          id_lote: isNaN(parseInt($("#id_lote").val())) ? 0 : $("#id_lote").val(),
          pcj_iva: $("#iva_grid").val(),
          pcj_descuento: $("#dto_grid").val(),
          und: $.trim($("#und_grid option:selected").text().split("-")[0]),
          can_und: parseInt($("#und_grid option:selected").text().split("-")[1]),
          vmcto: $.trim($("#vcmto_grid").val()) == '' ? '1900-01-01' : $("#vcmto_grid").val(),
          lote: $.trim($("#lote_grid").val()),
          sw: 5,
          id_bodega: $("#bodegas").val(),
          id_pedido: $("#id_pedido").val(),
          id: $("#id_detalle").val()
        }
        const update = await enviarPeticion(datosDetalle)

        if (update.data.error) {
          dissminSwal()
          Swal.fire("Error", "No se pudo actualizar el producto, por favor intente nuevamente!", "error")
          return;
        }
        await actualizarTotales(update.data.totales[0]);
        $("#id_detalle" + $("#id_detalle").val()).find("td.carrito-cantidad").text($("#cantidad_grid").val());
        $("#id_detalle" + $("#id_detalle").val()).find("td.carrito-cantidad2").text($("#cantidad_grid").val())
        $("#id_detalle" + $("#id_detalle").val()).find("td.carrito-precio-bruto").text($("#preciob_grid").val())
        $("#id_detalle" + $("#id_detalle").val()).find("td.carrito-precio-neto").text($("#precion_grid").val())
        $("#id_detalle" + $("#id_detalle").val()).find("td.carrito-und").text($.trim($("#und_grid option:selected").text().split("-")[0]))
        $("#id_detalle" + $("#id_detalle").val()).find("td.carrito-cantidad-und").text(parseInt($("#und_grid option:selected").text().split("-")[1]))
        $("#id_detalle" + $("#id_detalle").val()).find("td.carrito-total").text($("#total_grid").val());
        $("#id_detalle" + $("#id_detalle").val()).find("td.carrito-valor-unitario").text($("#precio_unitario").val());

        dissminSwal()
        limpiarReferencia()
        setTimeout(function () {
          $("#filtro").focus();
        }, 400)
      }

    } catch (e) {
      console.error(e)
      dissminSwal()
      Swal.fire("Ups", "Se produjo un error al actualizar la referencia", "error")
    }
  }
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
      es_credito: 1
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

const disabledEnabledTercero = (sw) => {
  $("#nit").attr("disabled", sw);
  $("#id_tercero_dir").attr("disabled", sw);
  $("#tipos").attr("disabled", sw);
  $("#id_condicion").attr("disabled", sw)
  $("#bodegas").attr("disabled", sw)
  /// $("#notas").attr("disabled",sw)
};

const validacionVendedor = async () => {

  let mostrarPromt = false;
  let valido = true;

  if (ConfModulos.solicitarVendedor) {
    // si es por item vededor queda en 0 y siempre pide el vendedor
    if (ConfModulos.solicitarPorItem) {
      mostrarPromt = true;
    } else if (ConfModulos.solicitarPrimerItem) {

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
        link: '../models/Facturacion.php',
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
  stock = $("#stock_real").val()

  cant_um = $("#cantidad_grid").val() * $("#und_grid option:selected").val()

  tipo_producto = $("#tipo_referencia").val()

  if (tipo_producto == 'P') {
    if (cant_um > stock) {
      Swal.fire("Error", "No hay stock suficiente", "error")
      refValida = false;
      return;
    }
  }

  return refValida;
};

const eliminarPagoTemp = async (id_transaccion) => {
  const resp = await enviarPeticion({
    link: '../models/Facturacion.php',
    op: 'eliminarPago',
    id_transaccion
  })
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

  let direccionesCargadas = await cargarDirecciones(item.id);
  if (!direccionesCargadas) {
    return false;
  }

  $("#id_tercero").val(item.id);
  $("#nit").val(item.nit);
  $("#tratamiento").val(item.tratamiento);
  $("#razon_comercial").val(item.razon_comercial);
  $("#textDireccion").val(item.direccion)
  $("#lista_tercero").val(parseInt(item.lista_precios));
  await cargarPerfilTributarioDesdeTercero(item.id);
  $("#info_condicion").text('CONTADO')
  $("#info_lista").text(item.lista_precios)
  $("#info_credito").text(formatNum(item.cupo_credito, '$', ConfGeneral))
  $("#info_zona_venta").text(item.codigo_zona)
  $("#info_zona_descripcion").text(item.zona_descripcion)
  $("#info_email").text(item.email_fe)
  $("#info_controlados").text($.trim(item.controlados))
  $("#info_controlados").text($.trim(item.controlados))
  $("#info_institucional").text($.trim(item.institucionales))
  $('#tabs button[data-bs-target="#navs-creacion"]').attr('disabled', false)
  $('#tabs button[data-bs-target="#navs-creacion"]').tab("show");

  $("#id_condicion").val(item.id_condicion);
  setTimeout(() => {
    $("#filtro").focus();
  }, 500);
  return valido;
};

const limpiarPedido = () => {
  $("#numero, #numero_pedido").val("");
  $("#id_pedido, #t_bruto, #t_dcto, #t_iva, #t_total, #id_transaccion").val(0);
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
    "#preciob_grid, #dto_grid, #iva_grid, #precion_grid, #tipo_referencia, " +
    "#cantidad_grid, #total_grid, #filtro"
  ).val("");

  $("#stock_real, #id_lote, #precio_unitario, #id_referencia, #costo_promedio, #id_detalle").val(0);

  if (ConfGeneral.ManejaFechaLote == 'S' && ConfGeneral.ManejaFIFO == 'N') {
    $("#lote_grid, #vcmto_grid").val("");
  }
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
  disabledEnabledTercero(false)
  $('#tabs button[data-bs-target="#navs-tercero"]').attr('disabled', false)
  $('#tabs button[data-bs-target="#navs-tercero"]').tab("show")
  listarAlmacenes()
};

const cargarItemGrid = async (data) => {
  itemActual = data;

  const $undGrid = $("#und_grid");
  const $cantidad = $("#cantidad_grid");
  const $stockReal = $("#stock_real");

  // Datos básicos
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
    ? `${Math.floor(data.total_stock / data.can_und)} ${data.des_und_comp} y ${(data.total_stock % data.can_und) * data.can_und}`
    : 0;

  $("#stock_grid").val(stockUnd);

  // Construir unidades
  const unds = [];

  for (let i = 1; i <= 4; i++) {
    const cantidad = Number(data[`cant_${i}`]);

    if (cantidad > 0) {
      unds.push({
        nombre: $.trim(data[`und_${i}`]),
        cantidad,
        precio: data[`precio${i === 1 ? "" : i}`],
        precion: data[`precion${i === 1 ? "" : i}`]
      });
    }
  }

  $undGrid.html(
    unds
      .map(
        (u, idx) => `
          <option
            value="${u.cantidad}"
            data-precio="${u.precio}"
            data-precion="${u.precion}"
            ${idx === 0 ? "selected" : ""}>
            ${u.nombre}-${u.cantidad}
          </option>`
      )
      .join("")
  );

  // Si deseas seleccionar data.can_und en vez de la primera:
  // $undGrid.val(data.can_und);

  const cantSelect = Number($undGrid.val());

  const datosPrecio = await calcularPrecioUnidad(
    data.precio,
    cantSelect,
    data.dcto,
    data.porcentaje_iva
  );

  $("#preciob_grid").val(formatNum(datosPrecio.preciob, "$", ConfGeneral));
  $("#precion_grid").val(formatNum(datosPrecio.precion, "$", ConfGeneral));

  $cantidad.prop(
    "max",
    Math.floor(Number($stockReal.val()) / cantSelect)
  );

  if (
    ConfGeneral.ManejaFechaLote === "S" &&
    ConfGeneral.ManejaFIFO === "N"
  ) {
    $("#vcmto_grid").val(data.vencimiento);
    $("#id_lote").val(data.id_lote);
    $("#lote_grid").val(data.lote);
  }

  $("#result-lotes").empty();

  $undGrid.trigger("change");

  if ($("#cod_barras").is(":checked")) {
    $cantidad
      .val(1)
      .trigger(
        $.Event("keyup", {
          key: "Enter",
          keyCode: 13,
          which: 13
        })
      );
    return;
  }

  setTimeout(() => {
    $cantidad.focus().select();
  }, 300);
};

const cargarItemGridLote = async (ob) => {
  const element = await JSON.parse($(ob).attr("data-element"));
  cargarItemGrid(element)
  $("#modalFechaLotes").modal("hide");
};

const cargarLote = async (data) => {

  try {
    const resp = await enviarPeticion({
      link: '../shared/servicios/servicios.php',
      op: 'cargar-lotes',
      id_referencia: data.id,
      bodega: $("#bodegas").val(),
      id_pedido: $("#id_pedido").val()
    });

    /*const disponibles = resp.filter(p =>
      (p.stock - p.comprometidas - p.comp_scan) > 0
    );*/

    //console.log(disponibles)

    // if (disponibles.length == 0) {
    if (resp.length == 0) {
      data.id_lote = 0;
      data.lote = 0;
      data.vencimiento = '';
      data.total_stock = 0;
      cargarItemGrid(data);
      return;
    }

    // if (disponibles.length == 1) {
    if (resp.length == 1) {
      const item_lotes = resp[0];
      data.id_lote = item_lotes.id_lote;
      data.lote = item_lotes.lote;
      data.vencimiento = item_lotes.vencimiento;
      data.total_stock = item_lotes.stock - item_lotes.comprometidas - item_lotes.comp_scan;
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

    $("#result-lotes").html(tb)
    setTimeout(function () {
      $(".data-lotes tbody tr:first").addClass("focus").focus()
    }, 600)

  } catch (e) {
    console.error(e)
    Swal.fire("Error", "Error al cargar los lotes", "error")
  }

};

const formatearMonedaLocal = (valor) => {
  const n = isNaN(parseFloat(valor)) ? 0 : parseFloat(valor);
  if (typeof ConfGeneral !== 'undefined' && ConfGeneral) {
    return formatNum(n, '$', ConfGeneral);
  }
  return n.toFixed(2);
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
      link: '../models/Facturacion.php',
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

const actualizarTotales = async (totales) => {
  $("#t_bruto").val(formatNum(parseFloat(totales.valor_total) - parseFloat(totales.iva) + parseFloat(totales.descuento), '$'));
  $("#t_dcto").val(formatNum(totales.descuento, '$', ConfGeneral));
  $("#t_iva").val(formatNum(totales.iva, '$', ConfGeneral));
  $("#t_total").val(formatNum(totales.valor_total, '$', ConfGeneral));
  aplicarRetencionesPorPerfil();
  const retFte = unformatNum($("#t_ret_fte").val() || '0');
  const retIca = unformatNum($("#t_ret_ica").val() || '0');
  const retIva = unformatNum($("#t_ret_iva").val() || '0');
  const idTransaccion = $("#id_transaccion").val();
  if (idTransaccion > 0) {
    await guardarRetencionesTMP(idTransaccion, retFte, retIca, retIva);
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
    showLoadingWithTetrisLogo('Cargando información', 'ISA Software');

    const id_pedido = isNaN(parseInt($("#id_pedido").val())) ? 0 : $("#id_pedido").val();
    let resp;

    if (id_pedido > 0) {
      listarLotesPedidos(id, true)
      return;

    } else {
      resp = await enviarPeticion({
        link: '../shared/servicios/servicios.php',
        op: 'detalle_transacciones_tmp',
        id
      });
    }

    if (resp.length == 0) {
      Swal.fire("Error", "No se encontraron items", "error")
      return;
    }

    const item = resp[0];

    // const stock_total = item.stock - item.comprometidas - item.comp_scan;
    const stock_total = item.total_stock - (item.comprometidas - item.cantidad);
    $("#id_referencia").val(item.id)
    $("#descripcion_grid").val(item.descripcion)
    $("#laboratorio_grid").val(item.laboratorio)
    $("#codigo_grid").val(item.codigo)
    $("#precio_unitario").val(item.valor_unitario)
    $("#costo_promedio").val(item.costo_unitario)
    //$("#stock_real").val(item.total_stock)
    $("#stock_real").val(stock_total);
    $("#unidades_comprometidas").text(item.comprometidas);
    $("#id_detalle").val(item.id_detalle)
    $("#tipo_referencia").val(item.tipo)
    if (ConfGeneral.ManejaFechaLote == 'S' && ConfGeneral.ManejaFIFO == 'N') {
      $("#id_lote").val(item.id_lote)
    }

    const datosPrecio = calcularPrecioUnidad(item.valor_unitario, item.can_und, item.pcj_descuento, item.iva)

    $("#preciob_grid").val(formatNum(datosPrecio.preciob, '$', ConfGeneral));
    $("#dto_grid").val(item.pcj_descuento)
    $("#iva_grid").val(item.iva)
    $("#precion_grid").val(formatNum(datosPrecio.precion, '$', ConfGeneral));
    if (ConfGeneral.ManejaFechaLote == 'S' && ConfGeneral.ManejaFIFO == 'N') {
      $("#lote_grid").val(item.lote)
      $("#vcmto_grid").val(item.vencimiento);
    } else {
      $("#lote_grid").val('')
      $("#vcmto_grid").val(item.vencimiento);
    }

    $("#id_transaccion").val(item.id_transaccion)
    let unds = [];

    if (item.cant_1 > 0) {
      unds.push([item.und_1, item.cant_1, item.precio, item.precion])
    }
    if (item.cant_2 > 0) {
      unds.push([item.und_2, item.cant_2, item.precio2, item.precion2])
    }
    if (item.cant_3 > 0) {
      unds.push([item.und_3, item.cant_3, item.precio3, item.precion3])
    }
    if (item.cant_4 > 0) {
      unds.push([item.und_4, item.cant_4, item.precio3, item.precion3])
    }
    let op = ''
    unds.forEach((und, idx) => {
      let selected = ''
      if (idx == 0) {
        selected = 'selected'
      }
      op += `<option ${selected} 
      data-precio='${und[2]}' 
      data-precion='${und[3]}'  
      value="${und[1]}">${$.trim(und[0])}-${und[1]}</option>`
    })
    $("#und_grid").html(op);
    $("#und_grid").val(item.can_und);

    if ($("#id_pedido").val() > 0) {
      $("#cantidad_grid").val(item.cantidad2 / item.can_und)
    } else {
      $("#cantidad_grid").val(item.cantidad / item.can_und)
    }

    const total = datosPrecio.precion * (item.cantidad / item.can_und)
    $("#total_grid").val(formatNum(total, '$', ConfGeneral));

    const stock = mostrarStockDisponible(stock_total,
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

const noContado = (data) => {
  return data.filter(item => item.dias > 0)
};

const setDateNow = (Idfechas) => {
  const hoy = new Date();

  // Formatear a YYYY-MM-DD
  const yyyy = hoy.getFullYear();
  const mm = String(hoy.getMonth() + 1).padStart(2, '0'); // Mes (0-11)
  const dd = String(hoy.getDate()).padStart(2, '0');      // Día
  const fechaFormateada = `${yyyy}-${mm}-${dd}`;
  // Asignar al input
  Idfechas.forEach(id => {
    document.getElementById(id).value = fechaFormateada;
  })
};

const enviarFacturaElectronica = async (id, sw) => {

  try {
    showLoadingWithTetrisLogo('Enviando factura', 'ISA Software');
    const resp = await enviarDocumentoElctronico(id, 5);
   // console.log({ resp })
    dissminSwal();

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
    dissminSwal();
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

function validarDiferenciasDespacho() {

  let diferencias = [];

  $("#carrito tbody tr").each(function () {
    let tr = $(this);

    let codigo = tr.find(".carrito-codigo").text();
    let descripcion = tr.find(".carrito-descripcion").text();
    let cantidad = parseFloat(tr.find(".carrito-cantidad").text()) || 0;
    let despachado = parseFloat(tr.find(".carrito-cantidad2").text()) || 0;

    if (cantidad !== despachado) {
      diferencias.push({
        codigo,
        descripcion,
        cantidad,
        despachado,
        diferencia: cantidad - despachado
      });
    }
  });

  return diferencias;
}

function construirTablaDiferencias(lista) {

  let html = `
    <div class="table-responsive text-nowrap">
        <table class="table table-sm table-bordered table-dif-cantidad-cantidad2">
            <thead class="table-primary">
                <tr>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Pedida</th>
                    <th>Despachada</th>
                    <th>Diferencia</th>
                </tr>
            </thead>
            <tbody>
    `;

  lista.forEach(p => {
    html += `
            <tr>
                <td>${p.codigo}</td>
                <td style="text-align:left">${p.descripcion}</td>
                <td class="text-center">${p.cantidad}</td>
                <td class="text-center">${p.despachado}</td>
                <td class="text-danger text-center"><b>${p.diferencia}</b></td>
            </tr>
        `;
  });

  html += "</tbody></table></div>";

  return html;
};

function confirmarPedidoConDiferencias(callbackOk) {

  let diferencias = validarDiferenciasDespacho();

  // 👉 Si NO hay diferencias → continuar directo
  if (diferencias.length === 0) {
    callbackOk();
    return;
  }

  let tablaHTML = construirTablaDiferencias(diferencias);

  Swal.fire({
    title: "⚠️ Diferencias en despacho",
    html: `
            <p>Los siguientes productos NO coinciden con lo pedido:</p>
            ${tablaHTML}
            <br>
            <b>¿Desea continuar de todas formas?</b>
        `,
    icon: "warning",
    width: 800,
    showCancelButton: true,
    confirmButtonText: "Sí, continuar",
    cancelButtonText: "Cancelar",
    confirmButtonColor: "#28a745",
    cancelButtonColor: "#d33",
    customClass: {
      confirmButton: "btn-sm",
      cancelButton: "btn-sm"
    },
  }).then((result) => {
    if (result.isConfirmed) {
      callbackOk();
    }
  });

};

const guardarPedido = async () => {
  showLoadingWithTetrisLogo('Guardando factura', 'ISA Software');

  let idFactura = 0;
  const id_transaccion = $("#id_transaccion").val()
  try {

    const id_pedido = isNaN(parseInt($("#id_pedido").val())) ? 0 : $("#id_pedido").val();
    const numero_temp = isNaN(parseInt($("#numero").val())) ? 0 : $("#numero").val();
    let respDoc;

    if (numero_temp == 0 && id_pedido == 0) {
      Swal.fire("Error", "No se encontro un numero de documento", "warning");
      return;
    }


    if (id_pedido > 0) {

      respDoc = await enviarPeticion({
        link: '../shared/servicios/servicios.php',
        op: 'guardar-factura-pedido',
        id_pedido,
        id_tipo_transaccion: $("#tipos").val()
      });
    } else {

      if (ConfGeneral.ManejaFIFO == 'S') {
        op_ = 'FIFO';
      } else {
        op_ = 'volcar_documento'
      }

      respDoc = await enviarPeticion({
        link: '../shared/servicios/servicios.php',
        op: op_,
        id_transaccion,
        updateVendedor: ConfModulos.solicitarAlGuardar,
        vendedor: $("#vendedor").val()
      });
    }
    ///console.log({ respDoc })
    if (!respDoc.ok) {
      dissminSwal()
      Swal.fire("error", respDoc.message, "error");
      return;
    }
    idFactura = respDoc.id;

  } catch (error) {
    console.error(error);
    dissminSwal()
    Swal.fire("Ups", "Se genero un error al guardar el documento", "error")
    return;
  }
  //envio a la DIAN 
  dissminSwal()
  nuevoPedido();
  const textCont = await generarContabilizacion(idFactura);
  if (permisos.FacturaElectronicaAutomatica) {
    const respDian = await enviarDocumentoElctronico(idFactura, 5);
  }
  msj = `
  <p>Factura guardada corectamente</p>
  <p>${textCont.message}</p>
  `
  Swal.fire("Mensaje", msj, "success")

  ImprimirDocumento(5, idFactura, 'Impresion de factura', true);

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

function crearModalBusqueda() {

  // Si ya existe, destruirla primero
  if (modalBusquedaRef) {
    modalBusquedaRef.destroy();
    modalBusquedaRef = null;
  }
  const listaAlmacen = parseInt($("#lista_almacen").val()) || 0;
  const listaTercero = parseInt($("#lista_tercero").val()) || 0;
  const lista = listaTercero === 0 ? listaAlmacen : listaTercero;


  modalBusquedaRef = new ModalBusquedaProducto(async (rowData) => {
    if (ConfGeneral.ManejaFechaLote === "S") {
      await cargarLote(rowData);
    } else {
      await cargarItemGrid(rowData);
    }
  }, {
    cod_barras: false,
    lista: lista,
    id_modulo: id_modulo
  }
  );

  return modalBusquedaRef;
}

$(async function () {
  fechaActual([
    ['fecha_inicial', 'fecha_final'],
    ['fe_fecha_inicial', 'fe_fecha_final'],
    ['fecha_inicial_pedido', 'fecha_final_pedido']

  ]);
  const listaPrecio = (await cargarListaAlmacen())?.[0]?.lista_precios;

  if (listaPrecio) {
    $("#lista_almacen").val(listaPrecio);
  }
  await listarAlmacenes();
  await condicionesPago('C', 'id_condicion', null);
  if ($("#id_condicion  option").length == 0) {
    $("body").html(`
      <div class="alert alert-danger">No se detecto un condicion de pago a crédito , por validar en el modulo ' Condiciones de pagos ' en el menu</div>
    `)
    return;
  }
  await tiposDocumentos(5, $("#bodegas").val(), 'tipos');
  data_tipos = JSON.parse($("#tipos option:selected").attr("data-element"));
  await listarConceptosRetencion();

  const preferencias = await preferenciasUsuario(id_modulo);

  if (preferencias.CheckearBarras == 'S') {
    $("#cod_barras").attr("checked", true)
  } else {
    $("#cod_barras").attr("checked", false)
  }


  /*if (preferencias.SoloProductoSock == 'S') {
    soloStock = true;
  }*/


  permisos = await permisosUsuario(id_modulo)
  const { general, modulos } = await cargarConfiguraciones();
  ConfGeneral = general;
  ConfModulos = modulos;

  if (permisos?.CambiarCondicionPago) {
    $("#id_condicion").removeAttr("disabled");
  } else {
    $("#id_condicion").attr("disabled", true);
  }

  if (permisos?.CambiarPrecio) {
    $(".btn-editar-precio").removeClass("d-none");
  }

  if (permisos.CargarPedido) {
    $("#div_Btnpedidos").html(`
        <button type="button" class="btn btn-sm btn-outline-danger m-1" id="Btnpedidos"><i class="fa-solid fa-circle-down"></i> Cargar pedido</button>  
      `)
  }

  $("#consultar-fe").click(function () {
    listarFacturas()
  })

  $(document).on("click", ".btn-editar-precio", function () {
    if (!permisos?.CambiarPrecio) {
      return;
    }

    if ($("#id_referencia").val() > 0) {
      $("#precion_grid").prop("readonly", function (_, value) {
        return !value;
      });
      $("#precion_grid").focus().select();
    }
  });

  if (permisos?.CambiarPrecio) {
    $("#precion_grid").focus(function () {
      const valorActual = unformatNum($(this).val());
      tmpPrecioEdicion = Number(valorActual) || 0;
      $(this).val(valorActual).select();
    });

    // Nuevo para que formee el precio mientras se escribe, permitiendo solo números y un punto decimal
    /* $("#precion_grid").on("input", function () {
       let valor = $(this).val();
       // Eliminar todo excepto números y un punto decimal
       valor = valor.replace(/[^0-9.]/g, "");
       // Permitir un solo punto decimal
       const partes = valor.split(".");
       if (partes.length > 2) {
         valor = partes[0] + "." + partes.slice(1).join("");
       }
       if (valor === "") {
         $(this).val("");
         return;
       }
       const numero = parseFloat(valor);
       if (isNaN(numero)) return;
       $(this).val(
         numero.toLocaleString("en-US", {
           minimumFractionDigits: 0,
           maximumFractionDigits: 2
         })
       );
     });*/
    //fin 

    $("#precion_grid").focusout(function () {
      if ($("#id_referencia").val() <= 0) {
        return;
      }

      //const precio = Number(unformatNum($(this).val())) || 0;
      const precio = Number($(this).val()) || 0;
      const dto = Number($("#dto_grid").val()) || 0;
      const iva = Number($("#iva_grid").val()) || 0;

      if (!precio) {
        $(this).val(formatNum(tmpPrecioEdicion, '$', ConfGeneral));
        return;
      }

      const pbruto = precio / ((1 - (dto / 100)) * ((iva / 100) + 1));

      $("#precio_unitario").val(pbruto);
      $("#preciob_grid").val(formatNum(Math.round(pbruto), '$', ConfGeneral));
      $(this).val(formatNum(precio, '$', ConfGeneral));

      $("#und_grid option:selected").attr("data-precio", pbruto);
      $("#und_grid option:selected").attr("data-precion", precio);

      $("#cantidad_grid").trigger("keyup");
    });
  }

  $("#tipos").on('change', async function () {
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

  $("#nuevo").click(async function () {
    nuevoPedido();
    $("#carrito tbody tr").remove()
  })

  $("#guardar").on('click', function () {

    Swal.fire({
      title: "Esta seguro que desea guardar la factura?",
      text: "Esta acción no puede reversarse!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Si"
    }).then(async (result) => {

      if (result.isConfirmed) {
        //VALIDO SI HAY REFERENCIAS DEL PEDIDO SIN COMPLETAR
        confirmarPedidoConDiferencias(async function () {
          // 👉 AQUÍ va tu código real de guardar pedido
          await guardarPedido();
        });
      }
    })

  });
  $("#und_grid").on('change', function (e) {
    e.preventDefault();
    data_precio = $("#und_grid option:selected").attr("data-precio");

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
      $("#precio_unitario").val(data_precio)
    }

  });
  $("#Btnpedidos").click(function () {
    $("#modalPedidos").modal("show");
  })
  $("#listar-pedidos").click(async function () {
    const nit = $("#nit_tercero_pedido").val()
    const fecha_inicial = $("#fecha_inicial_pedido").val()
    const fecha_final = $("#fecha_final_pedido").val()
    nuevoPedido();

    showLoadingWithTetrisLogo('Buscando pedidos', 'ISA Software');

    try {

      const pedidos = await enviarPeticion({
        link: '../shared/servicios/servicios.php',
        op: 'busqueda_pedido',
        fecha_inicial,
        fecha_final,
        nit,
        sw: 8,
        estado: 'P'
      });


      if (pedidos.data.length == 0) {
        dissminSwal()
        $("#result-pedidos").html(`
              <div class="alert alert-danger">No se encontrarón resultados!</div>  
            `);
        return;
      }


      let tb = `<table class="table table-sm table-striped " id="table-pedidos">
                      <thead class="table-primary">
                        <tr>
                          <th>Fecha</th>
                          <th>Numero</th>
                          <th>Cliente</th>
                          <th>Total</th>
                          <th>Items</th>
                          <th>Ingresados</th>
                          <th>Progreso</th>
                          <th>Acciones</th>
                        </tr>
                      </thead>
                      <tbody>`
      pedidos.data.forEach(element => {

        const pcj = ((element.items_ingresados / element.items) * 100).toFixed(2)
        let progreso = '';
        if (pcj > 0 && pcj <= 33) {
          progreso = 'danger'
        } else if (pcj > 33 && pcj <= 66) {
          progreso = 'primary'
        } else if (pcj > 66) {
          progreso = 'success'
        }

        const fechaHora = element.fecha_hora || '';
        const [fecha, hora] = fechaHora.split(' ');

        tb += `
                <tr>
                  <td>
                    <div class="fw-semibold">
                        ${fecha || ''}
                    </div>
                    ${hora ? `
                                      <small class="text-muted d-block">
                                          <i class="fa-regular fa-clock me-1"></i>
                                          ${hora}
                                      </small>
                                  `
            : ''}
                  </td>
                  <td>${element.numero}</td>
                  <td>
                    <div class="fw-semibold">
                        ${element.razon_comercial}
                    </div>
                    <small class="text-muted d-block">
                       ${element.nombres || ''}
                    </small>
                    <small class="text-muted d-block">
                        NIT: ${element.nit || ''}
                    </small>     
                  </td>
                  <td>${formatNum(element.valor_total, '$', ConfGeneral)}</td>
                  <td>${element.items}</td>
                  <td>${element.items_ingresados}</td>
                  <td class="table-${progreso}">${pcj}%</td>
                  <td>
                      <button class="btn btn-sm btn-outline-primary cargar-pedido m-1" data-element='${JSON.stringify(element)}' ><i class="fa fa-arrow-down"></i></button>
                      <button class="btn btn-sm btn-outline-info btn-detalle-pedido m-1" data-id="${element.id_pedido}" ><i class="fa fa-eye"></i></button>
                    </td>
                </tr>
              `
      });

      tb += `</tbody></table>`
      $("#result-pedidos").html(tb)
      dissminSwal();

      $(".cargar-pedido").on('click', async function () {
        $("#result-pedidos").html('')
        try {
          const encabezado = JSON.parse($.trim($(this).attr("data-element")));

          //cargo el cliente 
          if (isNaN(parseInt(encabezado.id_tercero)) || encabezado.id_tercero == 0) {
            Swal.fire("Error", "No se detecto el id del tercero", "error")
            return;
          }
          showLoadingWithTetrisLogo('Cargando pedido', 'ISA Software');

          const dataTercero = await enviarPeticion({
            link: '../models/Terceros.php',
            id_tercero: encabezado.id_tercero,
            filtro: '',
            op: 's_tercero',
            tipo_tercero: ''
          });

          const tercero = dataTercero.data[0];
          const cargaTercero = await cargarTercero(tercero);

          if (!cargaTercero.sw) {
            return;
          }
        //  console.log({  tercero, encabezado  })
          $("#id_condicion").val(encabezado.condicion)
          $("#bodegas").val(encabezado.id_bodega)
          $("#id_tercero_dir").val(encabezado.id_tercero_dir)
          $("#numero_pedido").val(encabezado.numero)
          $("#id_pedido").val(encabezado.id_pedido)
          const id = encabezado.id_pedido;

          const detalle = await enviarPeticion({
            link: '../shared/servicios/servicios.php',
            id_pedido: id,
            op: "buscar_detalle_pedido"
          });

          new_detalle = detalle.filter(item => item.datos.cantidad > item.datos.cantidad_desp)

          if (new_detalle.length == 0) {
            Swal.fire("Ups", "Este pedido no tiene items", "error")
            return;
          }
          $("#carrito tbody tr").remove()



          new_detalle.forEach(productos => {
            item = productos.datos

            carrito_det = {
              id: item.id,
              codigo: item.codigo,
              descripcion: item.descripcion,
              laboratorio: item.marca,
              dto: item.pcj_descuento,
              iva: item.pcj_iva,
              preciob: formatNum(item.valor_unitario * item.can_und, '$', ConfGeneral),
              precion: formatNum(((item.valor_unitario * (1 - (item.pcj_descuento / 100))) * ((item.pcj_iva / 100) + 1) * item.can_und), '$', ConfGeneral),
              cantidad: item.cantidad - item.cantidad_desp,
              und: item.und,
              can_und: item.can_und,
              cantidad2: item.cantidad2,
              total: formatNum((((item.valor_unitario * (1 - (item.pcj_descuento / 100))) * ((item.pcj_iva / 100) + 1) * item.can_und) * item.cantidad2), '$', ConfGeneral),
              valor_unitario: item.valor_unitario,
              vmcto: '01-01-1900',
              lote: '0',
              n_lotes_pedidos: item.n_lotes_pedidos
            };

            addCart(carrito_det)
          })

          $("#modalPedidos").modal("hide")
          calcularValorPedido();
          dissminSwal();

        } catch (error) {
          dissminSwal()
          console.error({ error: error.message })
          Swal.fire("Error", "Error al cargar el detalle del pedido", "error")
        }
      })

      $(".btn-detalle-pedido").click(async function () {
        const id_pedido = $(this).attr("data-id");
        $("#result-detalle-pedido").html('');

        if (id_pedido > 0) {
          showLoadingWithTetrisLogo('Cargando items del pedido', 'ISA Software');
          const detalle_pedido = await enviarPeticion({
            link: '../shared/servicios/servicios.php',
            op: 'buscar_detalle_pedido',
            id_pedido
          })

          if (detalle_pedido.length == 0) {
            dissminSwal();
            Swal.fire("Ups", "No se encontraron resultados", "error")
            return;
          }
          tb = `<table class="table table-sm">
                        <thead>
                          <tr>
                            <th>Codigo</th>
                            <th>Producto</th>
                            <th>Precio</th>
                            <th>Dto</th>
                            <th>Iva</th>
                            <th>Precio neto</th>
                            <th>Cantidad</th>
                            <th>Total</th>
                          </tr>
                        </thead>
                    <tbody>`
          detalle_pedido.forEach(item => {
            preciob = item.datos.valor_unitario * item.datos.can_und
            precion = ((item.datos.valor_unitario * (1 - (item.datos.pcj_descuento / 100))) * ((item.datos.pcj_iva / 100) + 1)) * item.datos.can_und
            cantidad = item.datos.cantidad / item.datos.can_und
            total = cantidad * precion
            tb += `<tr>
                            <td>${item.datos.codigo}</td>
                            <td>${item.datos.descripcion + '-' + item.datos.marca}</td>
                            <td>${formatNum(preciob, '$', ConfGeneral)}</td>
                            <td>${item.datos.pcj_descuento}</td>
                            <td>${item.datos.pcj_iva}</td>
                            <td>${formatNum(precion, '$', ConfGeneral)}</td>
                            <td>${cantidad}</td>
                            <td>${formatNum(total, '$', ConfGeneral)}</td>
                        </tr>`
          });
          tb += `</tbody>
                  </table>`

          $("#result-detalle-pedido").html(tb);
          dissminSwal()
          $("#modalDetallePedido").modal("show")
        }
      });


    } catch (error) {
      dissminSwal()
      Swal.fire("Errpr", "Error al consultar los pedidos", "error")
      console.error(error)
    }
  })

  $("#txtBusquedaTercero").focus();
  dissminSwal();
  $(".container-fluid").removeClass("d-none")

  /************************************************* */
  //CHANGE
  $("#cod_barras").change(function () {
    const opcion = $(this).is(":checked") ? 'S' : 'N';

    enviarPeticion({
      link: '../shared/servicios/servicios.php',
      op: 'cambiar-preferencias',
      CheckearBarras: opcion,
      id_modulo
    })
      .then(resp => {
        console.log({ resp })
      })
      .catch(err => {
        console.error({ err })
      })
  });

  //KEYUP
  $('#cliente_pedido').autocomplete({
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

      $("#nit_tercero_pedido").val(item.nit);
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

      if (item.id_condicion == 0) {
        Swal.fire("Error", "El tercero no tiene condicion de pago para proveedor", "error");
        return;
      }
      cargarTercero(item);


      limpiarPedido()
      limpiarReferencia()
      limpiarTemporales()

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
      $("#nit_tmp").val(ui.item.nit)
    }
  });
  $("#txtBusquedaTerceroTmp").on("input", function () {
    if ($.trim($(this).val()) === "") {
      $("#nit_tmp").val("");
    }
  });
  $("#dto_grid").on('keyup', function (e) {
    const precio = $("#precio_unitario").val()
    const datosPrecio = calcularPrecioUnidad(
      precio,
      $("#und_grid option:selected").val(),
      $(this).val(),
      $("#iva_grid").val()
    );
    $("#precion_grid").val(formatNum(datosPrecio.precion, '$', ConfGeneral))

    const cantidad = $("#cantidad_grid").val();

    if (isNaN(parseInt(cantidad)) || cantidad == 0) {
      return;
    }

    const totales = calcularTotal(unformatNum($("#precio_unitario").val()),
      $("#dto_grid").val(),
      $("#iva_grid").val(),
      cantidad * $("#und_grid option:selected").val());

    $("#total_grid").val(formatNum(totales.ttotal, '$', ConfGeneral))

  });
  $("#cantidad_grid").on('keyup', function (e) {
    agregarProducto($(this).val(), e)
  });

  $("#precion_grid").on('keyup', function (e) {
    if (e?.key == 'Enter') {
      $("#cantidad_grid").focus();
    }
  });

  $("#cliente_pedido").keyup(function () {
    if ($(this).val().length == 0) {
      $("#nit_tercero_pedido").val(0)
    }
  })
  $('#txtBusquedaTercero').keyup(function () {
    tercero = $.trim($(this).val())

    if (tercero.length == 0) {

      $('#tabs button[data-bs-target="#navs-creacion"]').attr('disabled', true);
      limpiarTercero();
      return;
    }
  });
  $("#und_grid").on('keyup', function (e) {
    if (e.key == 'Enter') {
      if ($("#codigo_grid").val() != '') {
        $("#cantidad_grid").focus().select();
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
      const resp =
        await modalBusquedaRef.getRecords({
          data: filtro,
          cod_barras: false,
          lista: lista
        });

      const nregistros =
        modalBusquedaRef.getNrecords();


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

  $("#buscar-temporal").on('click', function () {
    buscarTemporales();
  });

  $('input[type="number"]').keydown(function (e) {
    // Evitar que las flechas de dirección cambien el valor
    if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
      e.preventDefault();
    }
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
  //FOCUS FOCUSOUT

  $("#notas").focus(function () {
    $("#tmp").val($(this).val())
  })
  $("#notas").on('focusout', async function () {

    try {

      const nota = $.trim($(this).val());

      if ($("#id_transaccion").val() > 0) {
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
      }
    } catch (e) {
      console.error(e)
      $(this).val($("#tmp").val())
    }

  });


  $(".container-fluid").removeClass("d-none")
  dissminSwal();
});

