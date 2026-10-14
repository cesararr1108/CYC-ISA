// configuraciones
const id_modulo = 23;
let permisos = [];


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
const cargarDirecciones = (id_tercero) => {

  try {
    return enviarPeticion({
      link: '../models/Pedidos.php',
      op: 'listarDirecciones',
      id_tercero
    });

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
  $("#id_tranasaccion").val("0")
  $("#notas").val('')
  $("#vendedor").val(0);

}

const limpiarTercero = () => {
  $("#id_tercero").val("");
  $("#nit").val("");
  $("#tratamiento").val("");
  $("#razon_comercial").val("");
  $("#id_tercero_dir").html("");
  $('#id_condicion').prop('selectedIndex', 1);
  $("#lista_tercero").val(0);
  $("#info_condicion").text('')
  $("#info_lista").text('')
  $("#info_credito").text('')
  $("#info_zona_venta").text('')
  $("#info_email").text('')
  $("#txtBusquedaTercero").val('')
  $("#info_zona_descripcion").text('')
}

const limpiarReferencia = () => {
  $("#cantidad_grid").val('')
  $("#codigo_grid").val('')
  $("#laboratorio_grid").val('')
  $("#descripcion_grid").val('')
  $("#stock_grid").val('')
  $("#preciob_grid").val('');
  $("#dto_grid").val('');
  $("#iva_grid").val('');
  $("#precion_grid").val('');
  $("#lote_grid").val('');
  $("#vcmto_grid").val('')
  $("#cantidad_grid").val('')
  $("#und_grid").html('')
  $("#total_grid").val('')
  $("#stock_real").val(0)
  $("#precio_unitario").val(0)
  $("#id_referencia").val(0)
  $("#costo_promedio").val(0)
  $("#id_detalle").val(0)
  $("#filtro").val('');
  $("#stock_disponible").val(0)
  $("#id_pedidos_lotes").val(0)
}

const disabledEnabled = (sw) => {
  $("#txtBusquedaTercero").attr("disabled", sw)
  $("#id_tercero_dir").attr("disabled", sw)
  $("#tipos").attr("disabled", sw)
  $("#id_condicion").attr("disabled", sw)
  $("#bodegas").attr("disabled", sw)
}

const limpiarTemporales = () => {
  $("#temporales").html('');
  fechaActual([
    ['fecha_inicial', 'fecha_final']
  ]);
}

const nuevoPedido = (noEliminar = [], redirigir = true) => {

  if (!noEliminar.includes('limpiarPedido')) {
    limpiarPedido()
  }

  if (!noEliminar.includes('limpiarReferencia')) {
    limpiarReferencia()
  }

  if (!noEliminar.includes('limpiarTercero')) {
    limpiarTercero()
  }

  if (!noEliminar.includes('limpiarTemporales')) {
    limpiarTemporales()
  }

  if (redirigir) {
    $('#tabs button[data-bs-target="#navs-tercero"]').tab('show');
  }
  $("#p_temporales").text(0);
  $("#p_confirmados").text(0);
  $("#p_separados").text(0);
  $("#p_facturados").text(0);
  $("#p_despachados").text(0);
  $("#carrito tbody tr").remove();
  activeDisabled();

}

const cargarItemGrid = (data) => {
  $("#id_referencia").val(data.id)
  $("#descripcion_grid").val(data.descripcion);
  $("#laboratorio_grid").val(data.laboratorio);
  const stock_und = data.total_stock > 0 ? parseInt(data.total_stock / data.can_und) + ` ${data.des_und_comp} y ` + (data.total_stock % data.can_und) * data.can_und : 0;
  $("#stock_grid").val(stock_und);

  $("#codigo_grid").val(data.codigo);
  $("#precio_unitario").val(data.precio);
  $("#dto_grid").val(data.dcto);
  $("#iva_grid").val(data.porcentaje_iva);
  $("#stock_real").val(data.total_stock);

  let unds = [];

  if (data.cant_1 > 0) {
    unds.push([data.und_1, data.cant_1, data.precio, data.precion])
  }
  if (data.cant_2 > 0) {
    unds.push([data.und_2, data.cant_2, data.precio2, data.precion2])
  }
  if (data.cant_3 > 0) {
    unds.push([data.und_3, data.cant_3, data.precio3, data.precion3])
  }
  if (data.cant_4 > 0) {
    unds.push([data.und_4, data.cant_4, data.precio4, data.precion4])
  }
  let op = ''
  unds.forEach((und, idx) => {
    let selected = ''
    if (idx == 0) {
      selected = 'selected'
    }
    op += `<option ${selected} data-precio ='${und[2]}' data-precion ='${und[3]}' value=${und[1]}>${$.trim(und[0])}-${und[1]}</option>`
  })
  $("#und_grid").html(op);

  const datosPrecio = calcularPrecioUnidad(data.precio, $("#und_grid option:selected").val(), data.dcto, data.porcentaje_iva)
  $("#preciob_grid").val(formatNum(datosPrecio.preciob, '$'))
  $("#precion_grid").val(formatNum(datosPrecio.precion, '$'))

  const cant_select = $("#und_grid option:selected").val();
  const disponible = parseInt($("#stock_real").val() / cant_select);

  $("#stock_disponible").val(disponible)
  $("#cantidad_grid").prop("max", disponible);
  $("#costo_promedio").val(data.promedio);
  $("#result-lotes").html('')
  setTimeout(function () {
    $("#cantidad_grid").focus()
  }, 300)

}

const cargarItemGridLote = (ob) => {
  const element = JSON.parse($(ob).attr("data-element"));
  cargarItemGrid(element)
  $("#modalFechaLotes").modal("hide");
}

const actualizarTotales = (totales) => {
  $("#t_bruto").val(formatNum(parseFloat(totales.valor_total) - parseFloat(totales.iva) + parseFloat(totales.descuento), '$'));
  $("#t_dcto").val(formatNum(totales.descuento, '$'));
  $("#t_iva").val(formatNum(totales.iva, '$'));
  $("#t_total").val(formatNum(totales.valor_total, '$'));
}

const eliminarItem = async (id) => {
  const id_pedido = $("#id_pedido").val();

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
        showLoadingWithTetrisLogo('Eliminando Item', 'ISA Software')
        resp = await enviarPeticion({
          link: '../shared/servicios/servicios.php',
          op: 'delete_item_pedido',
          id,
          id_pedido
        });

        if (resp.error) {
          dissminSwal()
          Swal.fire("Error", resp.message, "error")
          return;
        }

        if (resp.total_reg == 0) {
          addPedidoOcupado('eliminar_ocupado', $("#numero").val());
          nuevoPedido('limpiarTercero');
          $("#carrito tbody tr").remove();
          //activeDisabled()
          dissminSwal()
          return;
        }
        actualizarTotales(resp.totales[0])
        $("#id_detalle" + id).remove();
        dissminSwal()


      } catch (e) {
        console.error(e)
        dissminSwal()
        Swal.fire("Error", "No se pudo eliminar el registro", "error")
      }

    }
  });

}

const editarPedido = async (id) => {
  try {
    showLoadingWithTetrisLogo('Cargando información', 'ISA Software');

    const resp = await enviarPeticion({
      link: '../shared/servicios/servicios.php',
      op: 'detalle_pedido',
      id
    });
    console.log({
      resp
    })
    if (resp.length == 0) {
      Swal.fire("Error", "No se encontrron item", "error")
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

    const datosPrecio = calcularPrecioUnidad(item.valor_unitario, item.can_und, item.pcj_descuento, item.iva)

    $("#preciob_grid").val(formatNum(datosPrecio.preciob, '$'));
    $("#dto_grid").val(item.pcj_descuento)
    $("#iva_grid").val(item.iva)
    $("#precion_grid").val(formatNum(datosPrecio.precion, '$'));
    $("#id_pedido").val(item.id_pedido)
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
      unds.push([item.und_4, item.cant_4, item.precio4, item.precion4])
    }
    let op = ''
    unds.forEach((und, idx) => {
      let selected = ''
      if (idx == 0) {
        selected = 'selected'
      }
      op += `<option ${selected} data-precio='${und[2]}' data-precion='${und[3]}' value=${und[1]}>${$.trim(und[0])}-${und[1]}</option>`
    })
    $("#und_grid").html(op);
    $("#und_grid").val(item.can_und);
    des_und_comp = $("#und_grid option:selected").text().split("-")[0]
    const stock_und = item.total_stock > 0 ? parseInt(item.total_stock / item.can_und) + ` ${des_und_comp} y ` + (item.total_stock % item.can_und) * item.can_und : 0;
    $("#stock_grid").val(stock_und);


    $("#cantidad_grid").val(item.cantidad / item.can_und)
    const total = datosPrecio.precion * (item.cantidad / item.can_und)
    $("#total_grid").val(formatNum(total, '$'))

    dissminSwal()
    $("#und_grid").trigger("change");
    setTimeout(function () {
      $("#cantidad_grid").focus().select();
    }, 500)
  } catch (e) {
    console.error(e)
    dissminSwal()
    Swal.fire("Error", "Ha ocurrido un error al intentar obtener los datos!", "error")
  }

}

const activeDisabled = () => {
  const fila = $("#carrito tbody tr").length;
  if (fila > 0) {
    disabledEnabled(true)
  } else {
    disabledEnabled(false)
  }
}

const addCart = (item, idx) => {

  const tr = `
         <tr id="id_detalle${item.id}" tabindex="${idx + 101}">
            <td>${item.codigo}</td>
            <td>${item.descripcion}-${item.marca}</td>
            <td>${item.preciob}</td>
            <td  class="text-center">${item.dto}</td>
            <td  class="text-center">${item.iva}</td>
            <td>${item.precion}</td>
            <td  class="text-center">${item.cantidad}</td>
            <td  class="text-center">${item.und}</td>
            <td>${item.can_und}</td>
            <td>${item.total}</td>
            <td><button class="btn btn-sm btn-light border border-1 border-danger" onclick="eliminarItem(${item.id})"><i class="fa-regular fa-trash-can text-danger"></i></button></td>
            <td><button class="btn btn-sm btn-light border border-1 border-primary"  onclick="editarPedido(${item.id})"><i class="fa fa-pencil text-primary "></i></button></td>  
            <td class="d-none">${item.id}</td>
        </tr>`
  $("#carrito tbody").append(tr);
  limpiarReferencia()

}

const recuperarPedido = async (data) => {
  showLoadingWithTetrisLogo('Cargando Pedido', 'ISA Software');

  if (data.numero > 0) {
    const ocupado = await enviarPeticion({
      link: '../models/Pedidos.php',
      op: 'pedido_ocupado',
      numero: data.numero,
      tipo: 'ped'
    })
    if (ocupado.ocupado) {
      Swal.fire("", "Este pedido esta siendo  tratado por el usuario .<br> Usuario :" + ocupado.usuario + "<br> Fecha : " + ocupado.fecha, "error")
      return;
    }

  }

  try {

    nuevoPedido()
    activeDisabled()
    //carga los datos del tercero
    const item = data;
    const dir = await cargarDirecciones(item.id_tercero);
    console.log(item)
    if (dir.length == 0) {
      Swal.fire("Error", "El tercero no tiene direcciones asignadas", "error")
      return;
    }

    let p = '';

    dir.forEach(item => {
      selected = ''
      if (item.principal == 1) {
        selected = 'selected'
      }
      p += `<option  ${selected} value="${item.id}">${item.direccion}</option>`
    })

    $("#id_tercero_dir").html(p);
    $("#id_tercero").val(item.id_tercero);
    $("#nit").val(item.nit);
    $("#tratamiento").val(item.nombres);
    $("#razon_comercial").val(item.razon_comercial);
    $("#textDireccion").val(item.direccion)
    $("#id_condicion").val(item.condicion)
    $("#lista_tercero").val(item.lista_precios);
    $("#info_condicion").text(item.des_condicion);
    $("#info_lista").text(item.lista_precios);
    $("#info_credito").text(formatNum(item.cupo_credito, '$'));
    $("#info_zona_venta").text(item.zona_ventas);
    $("#info_zona_descripcion").text(item.zona_descripcion);
    $("#info_email").text(item.email_fe);
    //asigno la direccion selecciona
    $("#id_tercero_dir").val(item.id_tercero_dir)
    // $("#id_tercero_dir").val(item.id_direccion);
    $('#tabs button[data-bs-target="#navs-creacion"]').tab('show')
    $('#tabs button[data-bs-target="#navs-creacion"]').attr('disabled', false)
    //carga los datos del encabezado 
    $("#id_pedido").val(item.id_pedido);
    $("#numero").val(item.numero)
    actualizarTotales({
      valor_total: item.valor_total,
      iva: item.iva,
      descuento: item.descuento
    });
    $("#notas").val(item.notas)

    //carga los item
    const items = await enviarPeticion({
      link: '../shared/servicios/servicios.php',
      op: 'buscar_detalle_pedido',
      id_pedido: $("#id_pedido").val()
    })

    if (items.length == 0) {
      dissminSwal()
      Swal.fire("Error", "No se encontraron item", "error");
      nuevoPedido()
      return;
    }

    items.forEach(async (producto) => {
      const item = producto.datos
      let itemCart = {}
      const dataPrecio = calcularPrecioUnidad(item.valor_unitario, item.can_und, item.pcj_descuento, item.pcj_iva)

      itemCart.id = item.id
      itemCart.codigo = item.codigo
      itemCart.descripcion = item.descripcion
      itemCart.marca = item.marca
      itemCart.preciob = formatNum(dataPrecio.preciob, '$')
      itemCart.dto = item.pcj_descuento
      itemCart.iva = item.pcj_iva
      itemCart.precion = formatNum(dataPrecio.precion, '$')
      itemCart.cantidad = item.cantidad / item.can_und
      itemCart.und = item.und
      itemCart.can_und = item.can_und
      itemCart.total = formatNum(Math.round((dataPrecio.precion * (item.cantidad / item.can_und)) * 100) / 100, '$')
      await addCart(itemCart)
      activeDisabled()
    })
    $("#filtro").focus()
    dissminSwal()
    addPedidoOcupado('marcar_ocupado', item.numero)

  } catch (e) {
    console.error(e)
    nuevoPedido()
  }

};

const eliminarPedido = (idPedido) => {
  Swal.fire({
    title: "¿Eliminar pedido?",
    text: "Esta acción no se puede deshacer.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
    confirmButtonColor: "#d33",
    cancelButtonColor: "#6c757d"
  }).then(async (result) => {
    if (!result.isConfirmed) return;
    try {
      showLoadingWithTetrisLogo("Eliminando pedido...", "ISA Software");
      const resp = await enviarPeticion({
        link: "../shared/servicios/servicios.php",
        op: "delete_pedido",
        id_pedido: idPedido
      });
      dissminSwal();
      if (resp.error) {
        Swal.fire("Error", resp.message || "No fue posible eliminar el pedido.", "error");
        return;
      }
      Swal.fire(
        "Correcto",
        "El pedido fue eliminado correctamente.",
        "success"
      );
      await buscarTemporales();
      nuevoPedido();
    } catch (e) {
      console.error(e);
      dissminSwal();
      Swal.fire(
        "Error",
        "Ocurrió un error al eliminar el pedido.",
        "error"
      );
    }
  });

};

function actualizarContadoresEstados(data) {
  const conteo = {
    T: 0,
    C: 0,
    S: 0,
    F: 0,
    D: 0
  };

  data.forEach(({ estado }) => {
    if (conteo[estado] !== undefined) {
      conteo[estado]++;
    }
  });

  $("#p_temporales").text(conteo.T);
  $("#p_confirmados").text(conteo.C);
  $("#p_separados").text(conteo.S);
  $("#p_facturados").text(conteo.F);
  $("#p_despachados").text(conteo.D);
};

const buscarTemporales = async () => {

  try {

    const fecha_inicial = $.trim($("#fecha_inicial").val());
    const fecha_final = $.trim($("#fecha_final").val());

    showLoadingWithTetrisLogo("Buscando información", "ISA Software");

    const resp = await enviarPeticion({
      op: "listar_pedidos",
      link: "../models/Pedidos.php",
      fecha_inicial,
      fecha_final,
      nit: $("#nit_tmp").val(),
      sw: 8,
      estado: "",
      logistico : "N"
    });
    console.log(resp)
    if (!resp.data || resp.data.length === 0) {

      dissminSwal();

      $("#temporales").html(`
        <div class="alert alert-warning text-center">
          No se encontraron pedidos.
        </div>
      `);

      return;
    }

    // Mapa para recuperar el pedido completo
    const pedidos = {};

    resp.data.forEach(item => {
      pedidos[item.id_pedido] = item;
    });

    actualizarContadoresEstados(resp.data);

    let html = `
      <table id="pedidos-temp" class="table table-sm table-hover table-bordered align-middle w-100">
        <thead class="table-primary">
          <tr>
            <th>Número</th>
            <th>Fecha</th>
            <th class="text-center">Estado</th>
            <th>Cliente</th>
            <th class="text-end">Dto</th>
            <th class="text-end">IVA</th>
            <th class="text-end">Total</th>
            <th class="text-center" style="width:170px;">Acciones</th>
          </tr>
        </thead>
        <tbody>
    `;

    resp.data.forEach(item => {
      let estado = "";
      switch (item.estado) {

        case "T":
          estado = `<span class="btn btn-secondary btn-sm" title="Temporal">
                        <i class="fa-regular fa-file"></i>
                      </span>`;
          break;
        case "C":
          estado = `
              <span class="btn btn-warning btn-sm" title="Confirmado">
                <i class="fa-solid fa-circle-check"></i>
              </span>`;
          break;
        case "S":
          estado = `
              <span class="btn btn-info btn-sm text-white" title="Separado">
                <i class="fa-solid fa-boxes-stacked"></i>
              </span>`;
          break;
        case "F":
          estado = `
              <span class="btn btn-success btn-sm" title="Facturado">
                <i class="fa-solid fa-file-invoice-dollar"></i>
              </span>`;
          break;
        case "D":
          estado = `
              <span class="btn btn-primary btn-sm" title="Despachado">
                <i class="fa-solid fa-truck-fast"></i>
              </span>`;
          break;
        default:
          estado = `
              <span class="btn btn-dark btn-sm">
                <i class="fa-solid fa-question"></i>
              </span>`;
          break;
      }

      let estado_auth = '';

      switch (item.estado_autorizacion) {
        case "P": // Pendiente
          estado_auth = ` <small class="text-warning d-block mt-1">
                            <i class="fa-regular fa-clock"></i>
                            ${item.desc_autorizacion}
                          </small>`;
          break;
        case "A": // Aprobado
          estado_auth = ` <small class="text-success d-block mt-1">
                            <i class="fa-solid fa-circle-check me-1"></i>
                            ${item.desc_autorizacion}
                          </small>`;
          break;
        case "R": // Rechazado
          estado_auth = ` <small class="text-danger d-block mt-1">
                            <i class="fa-solid fa-circle-xmark me-1"></i>
                            ${item.desc_autorizacion}
                          </small>`;
          break;
        default:
          estado_auth = '';
      }



      const acciones = `
        <button
            class="btn btn-outline-primary btn-sm btnOpciones"
            title="Opciones">
            <i class="fa-solid fa-bars"></i>
            Opciones
        </button>
      `;

      html += `
        <tr data-id="${item.id_pedido}">
          <td class="fw-semibold">${item.numero}</td>
          <td>
            ${item.fecha_hora.substring(0, 16)}
          </td>
          <td class="text-center">
            ${estado}
          </td>
          <td>
            <div class="fw-semibold">
              ${item.nombres}
            </div>
            ${item.razon_comercial ? `<small class="text-primary d-block">${item.razon_comercial}</small>` : ""}
            ${estado_auth}
          </td>
          <td class="text-end">
            ${formatNum(item.descuento, '$')}
          </td>
          <td class="text-end">
            ${formatNum(item.iva, '$')}
          </td>
          <td class="text-end fw-bold">
            ${formatNum(item.valor_total, '$')}
          </td>
          <td class="text-center">
            ${acciones}
          </td>
        </tr>
      `;
    });

    html += `
        </tbody>
      </table>
    `;

    $("#temporales").html(html);
    $("#pedidos-temp tbody")
      .off("click", ".btnOpciones")
      .on("click", ".btnOpciones", function () {
        const id = $(this).closest("tr").data("id");
        mostrarOpcionesPedido(pedidos[id]);
      });
    dissminSwal();
  } catch (e) {
    console.error(e);
    dissminSwal();
    Swal.fire(
      "Error",
      "Ha ocurrido un error al obtener los pedidos.",
      "error"
    );
  }

};

const mostrarOpcionesPedido = (data) => {

  console.log(data);

  let estado = '';
  let editable = false;

  switch (data.estado) {

    case 'T':
      estado = `
        <span class="badge bg-secondary px-3 py-2">
          <i class="fa-regular fa-file me-1"></i> Temporal
        </span>`;
      editable = true;
      break;

    case 'C':
      estado = `
        <span class="badge bg-warning px-3 py-2">
          <i class="fa-solid fa-circle-check me-1"></i> Confirmado
        </span>`;
      break;

    case 'S':
      estado = `
        <span class="badge bg-info text-dark px-3 py-2">
          <i class="fa-solid fa-boxes-stacked me-1"></i> Separado
        </span>`;
      break;

    case 'F':
      estado = `
        <span class="badge bg-success px-3 py-2">
          <i class="fa-solid fa-file-invoice-dollar me-1"></i> Facturado
        </span>`;
      break;
    case "D":
      estado = `
        <span class="btn btn-primary btn-sm" title="Despachado">
          <i class="fa-solid fa-truck-fast"></i> Despachado
        </span>`;
      break;

    default:
      estado = `<span class="badge bg-dark">Sin estado</span>`;
  }

  // Acciones disponibles
  $("#btn-desbloquear,#btn-guardar,#btn-editar,#btn-eliminar")
    .prop("disabled", !editable);

  $("#mdl-cliente").text(
    data.razon_comercial && data.razon_comercial.trim() !== ''
      ? data.razon_comercial
      : data.nombres
  );

  $("#mdl-estado").html(estado);
  $("#mdl-fecha").text(data.fecha_hora);
  $("#mdl-pedido").text(data.numero);
  $("#mdl-usuario").text(data.usuario);
  $("#mdl-notas").text(data.notas);
  $("#mdl-cliente").text(data.nombres);
  $("#mdl-id-pedido").text(data.id_pedido);
  $("#mdl-autorizacion").text(data.desc_autorizacion || 'N/A');
  $("#mdl-factura").text(
    (data.tipo_factura ?? '') + ' - ' + (data.numero_factura ?? '')
  );


  const pasos = [
    {
      codigo: "T",
      texto: "Temporal",
      icono: "fa-file",
      color: "secondary",
      fecha: data.fecha_hora,
      usuario: data.usuario
    },

    {
      codigo: "A",
      texto: "Autorización",
      icono: "fa-user-check",
      color: "primary",
      fecha: data.fecha_autorizacion,
      usuario: data.usuario_autoriza,
      estado: data.desc_autorizacion
    },
    {
      codigo: "C",
      texto: "Confirmado",
      icono: "fa-circle-check",
      color: "warning",
      fecha: data.estado === "C" || data.estado === "S" || data.estado === "F"
        ? data.fecha_hora
        : null,
      usuario: data.estado === "C" || data.estado === "S" || data.estado === "F"
        ? data.usuario
        : null
    },
    {
      codigo: "S",
      texto: "Separación",
      icono: "fa-boxes-stacked",
      color: "info",
      fecha: data.fin_separacion,
      usuario: data.usuario_separacion
    },
    {
      codigo: "F",
      texto: "Facturación",
      icono: "fa-file-invoice-dollar",
      color: "success",
      fecha: data.numero_factura ? data.fecha_factura : null,
      usuario: data.usuario_factura
    },
    {
      codigo: "D",
      texto: "Despacho",
      icono: "fa-truck-fast",
      color: "primary",
      fecha: data.fin_despacho,
      usuario: data.usuario_despacho
    }
  ];

  let timeline = `
<div class="d-flex justify-content-between align-items-start">
`;

  pasos.forEach((p, i) => {

    const realizado = p.fecha != null;

    timeline += `

    <div class="text-center flex-fill">

        <div class="rounded-circle mx-auto mb-2
                    ${realizado ? `bg-${p.color} text-white` : "bg-light text-muted border"}"
             style="
                width:55px;
                height:55px;
                line-height:55px;
                font-size:20px;
                ${realizado ? "box-shadow:0 0 0 4px rgba(13,110,253,.08);" : ""}
             ">

            <i class="fa-solid ${p.icono}"></i>

        </div>

        <div class="${realizado ? "fw-semibold" : "text-muted"}">
            ${p.texto}
        </div>

        ${p.estado
        ? `<small class="text-primary d-block">${p.estado}</small>`
        : ""
      }

        ${realizado
        ? `
                <small class="text-muted d-block">
                    ${(p.fecha)}
                </small>

                <small class="fw-semibold text-secondary d-block">
                    ${p.usuario ?? ""}
                </small>
            `
        : `
                <small class="text-muted d-block">
                    Pendiente
                </small>
            `
      }

    </div>

    ${i < pasos.length - 1
        ? `<div class="flex-fill border-top mt-4"></div>`
        : ""
      }

    `;
  });

  timeline += "</div>";

  // Solicitar autorización
  $("#btn-desbloquear").off("click").on("click", async function () {

    if (!validarAutorizacion(data.estado_autorizacion, "solicitar")) return;

    await solicitarAutorizacion(data.id_pedido);
  });

  // Guardar
  $("#btn-guardar").off("click").on("click", function () {

    if (!validarAutorizacion(data.estado_autorizacion, "guardar")) return;

    guardarPedido(
      data.id_pedido,
      data.tipo,
      data.numero,
      data.id_tercero
    );

    $("#modalOptions").modal("hide");
  });

  // Editar
  $("#btn-editar").off("click").on("click", function () {

    if (!validarAutorizacion(data.estado_autorizacion, "editar")) return;

    recuperarPedido(data);
    $("#modalOptions").modal("hide");
  });

  // Eliminar
  $("#btn-eliminar").off("click").on("click", function () {

    if (!validarAutorizacion(data.estado_autorizacion, "eliminar")) return;

    eliminarPedido(data.id_pedido);
    $("#modalOptions").modal("hide");
  });


  // Imprimir Pedido
  $("#btn-pdf-pedido").off("click").on("click", function () {
    ImprimirDocumento(
      8,
      data.id_pedido,
      'Impresion de pedido de venta',
      false,
      ''
    );
  });
  //Imprimir Factura
  $("#btn-pdf-factura").off("click").on("click", function () {
    if (data.id_factura > 0) {
      ImprimirDocumento(
        5,
        data.id_factura,
        'Impresion de factura de venta',
        true,
        ''
      );
    } else {
      Swal.fire('Oops', 'No hay un número de factura que imprimir.', 'warning')
    }
  });

  //Linea de tiempo 
  $("#pedido-timeline").html(timeline);

  $("#modalOptions").modal("show");

};

// Valida si el pedido tiene alguna solicitud de autorización
function validarAutorizacion(estado, accion) {

  switch (accion) {

    // Solo permite solicitar autorización cuando no existe ninguna
    case "solicitar":
      if (estado === "") return true;

      let mensaje = "";
      switch (estado) {
        case "P":
          mensaje = "Este pedido ya tiene una solicitud de autorización pendiente.";
          break;
        case "A":
          mensaje = "Este pedido ya fue autorizado.";
          break;
        case "R":
          mensaje = "Este pedido ya tiene una autorización rechazada.";
          break;
        default:
          mensaje = "Este pedido ya cuenta con un proceso de autorización.";
      }

      Swal.fire({
        icon: "warning",
        title: "No es posible continuar",
        text: mensaje
      });
      return false;

    // Solo permite guardar si está autorizado o no tiene solicitud
    case "guardar":
      if (estado === "" || estado === "A") return true;

      Swal.fire({
        icon: estado === "P" ? "warning" : "error",
        title: estado === "P"
          ? "Autorización pendiente"
          : "Autorización rechazada",
        text: estado === "P"
          ? "Este pedido tiene una solicitud de autorización pendiente y no puede guardarse."
          : "Este pedido fue rechazado y no puede guardarse."
      });
      return false;

    // Editar y eliminar solo si no existe ninguna solicitud
    case "editar":
    case "eliminar":
      if (estado === "") return true;

      let accionTexto = accion === "editar" ? "editar" : "eliminar";

      let mensajeAccion = "";
      switch (estado) {
        case "P":
          mensajeAccion = `No es posible ${accionTexto} el pedido porque tiene una solicitud de autorización pendiente.`;
          break;
        case "A":
          mensajeAccion = `No es posible ${accionTexto} el pedido porque ya fue autorizado.`;
          break;
        case "R":
          mensajeAccion = `No es posible ${accionTexto} el pedido porque tiene una autorización rechazada.`;
          break;
        default:
          mensajeAccion = `No es posible ${accionTexto} el pedido porque tiene un proceso de autorización.`;
      }

      Swal.fire({
        icon: "warning",
        title: "Operación no permitida",
        text: mensajeAccion
      });
      return false;
  }

  return true;
}


const envioCorreo = async (id, NombreArchivo) => {
  try {
    const resp = await enviarPeticion({
      link: '../models/Pedidos.php',
      op: 'email',
      id,
      NombreArchivo
    })

    if (!resp.ok) {
      initToast = ('bg-danger', 'Error', 'Error al enviar el pedido por correo', 'danger')
      return;
    }

  } catch (error) {
    console.error(error)
  }

}

const addPedidoOcupado = (op = null, numero) => {
  try {
    enviarPeticion({
      link: '../models/Pedidos.php',
      op: op ? op : 'marcar_ocupado',
      numero,
      tipo: 'PED'
    });
  } catch (error) {
    console.error(error)
  }

}

let conStock;


const ValidarOcupado = (tipo, numero) => {
  console.log({
    tipo, numero
  })
}

const guardarPedido = async (id_pedido, tipo, numero, id_tercero) => {

  const resp = await enviarPeticion({
    link: '../models/Terceros.php',
    filtro: '',
    tipo_tercero: '',
    id_tercero: id_tercero,
    estado: 'A',
    op: 's_tercero'
  });

  if (!resp.data || resp.data.length === 0) {
    Swal.fire("Error", "No se pudo obtener la información del tercero.", "error");
    return;
  }
  const datos = resp.data[0];
  const valido = await validarTercero(datos, id_pedido);

  if (!valido.sw) {

    if (valido.autorizacion == 'N') {
      Swal.fire({
        title: "No es posible continuar",
        html: ` <p class="text-danger fw-bold">${valido.msj}</p>`,
        icon: "warning",
      });
    } else {

          const resultado = await Swal.fire({
            title: "No es posible continuar",
            icon: "warning",
            html: `
              <div class="credito-alert-box">
                ${valido.msj}
              </div>

              <p class="credito-desc">
                El cliente presenta novedades de bloqueo, mora, cupo o vencimiento.
              </p>

              <div class="credito-info-grid">
                <div class="credito-info-card">
                  <span>Cupo crédito</span>
                  <strong>${datos.cupo_credito}</strong>
                </div>
                <div class="credito-info-card">
                  <span>Condición de pago</span>
                  <strong>${datos.des_condicion}</strong>
                </div>
              </div>

              <div class="credito-footer-note">
                <i class="fa-solid fa-circle-info"></i>
                Puede solicitar la autorización directamente ahora o hacerlo posteriormente
                desde el menú de opciones mediante la opción <strong>Desbloquear</strong>.
              </div>
            `,
            showCancelButton: true,
            confirmButtonText: "Solicitar ahora",
            cancelButtonText: "Hacerlo después",
            reverseButtons: true,
            buttonsStyling: false,
            customClass: {
              popup: "swal-credito-popup",
              icon: "swal-credito-icon",
              title: "swal-credito-title",
              htmlContainer: "swal-credito-html",
              actions: "swal-credito-actions",
              confirmButton: "btn btn-outline-primary",
              cancelButton: "btn btn-outline-secondary"
            }
          });

          if (resultado.isConfirmed) {
            solicitarAutorizacion(id_pedido);
          }
    }

    return false;
  }

  const result = await Swal.fire({
    title: "¿Confirmar pedido?",
    text: "El pedido será confirmado y ya no podrá editarse ni eliminarse.",
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Sí, confirmar",
    cancelButtonText: "Cancelar",
    confirmButtonColor: "#198754",
    cancelButtonColor: "#6c757d",
    reverseButtons: true
  });

  if (!result.isConfirmed) {
    return;
  }

  showLoadingWithTetrisLogo('Guardando documento', 'ISA Software');

  try {

    const resp = await enviarPeticion({
      link: '../shared/servicios/servicios.php',
      op: 'upd_estado_pedido',
      id: id_pedido,
      estado: 'P'
    });

    if (resp.error) {
      dissminSwal();
      Swal.fire("Error", "No se pudo guardar el pedido.", "error");
      return;
    }

    // Generar PDF
    await enviarPeticion({
      link: '../pdf/pedidos-ventas.php',
      NombreArchivo: resp.nombre_pdf,
      id: id_pedido,
      metodo: 'GET',
      accion: 'F'
    });

    addPedidoOcupado('eliminar_ocupado', numero);

    ImprimirDocumento(
      8,
      id_pedido,
      'Impresión de pedido de venta',
      false,
      resp.nombre_pdf
    );

    envioCorreo(id_pedido, resp.nombre_pdf);
    nuevoPedido();
    disabledEnabled(false);
    dissminSwal();
    Swal.fire({
      icon: "success",
      title: "Pedido confirmado",
      text: "El pedido fue confirmado correctamente.",
      timer: 1800,
      showConfirmButton: false
    });

  } catch (e) {
    console.error(e);
    dissminSwal();
    Swal.fire(
      "Error",
      "Se generó un error al guardar el pedido.",
      "error"
    );

  }

};


const validarTercero = async (item, id_pedido) => {

  if (!permisos.IgnorarBloqueoTercero && item.estado !== 'A') {
    return {
      sw: false,
      autorizacion: 'N',
      msj: 'El cliente se encuentra bloqueado, solicitar al area administrativa su desbloqueo.'
    };
  }

  if (!validarEmail(item.email_fe)) {
    return {
      sw: false,
      autorizacion: 'N',
      msj: 'El email del cliente no es válido, solicitar al area administrativa su corrección.'
    };
  }

  if (item.dias === 0) {
    return {
      sw: false,
      autorizacion: 'N',
      msj: 'El cliente no tiene condición de pago crédito, solicitar al area administrativa su corrección.'
    };
  }

  const pedido = await enviarPeticion({
    link: '../models/Pedidos.php',
    op: 'validar_autorizacion_pedido',
    id_pedido
  });

  if (pedido?.ok) {
    if (pedido?.data?.autorizacion === 'P') {
      return {
        sw: false,
        autorizacion: 'N',
        msj: 'El pedido aún se encuentra pendiente de autorización.'
      };
    }

    if (pedido?.data?.autorizacion === 'R') {
      return {
        sw: false,
        autorizacion: 'N',
        msj: 'El pedido fue rechazado por el autorizador, imposible continuar con la venta.'
      };
    }

    if (pedido?.data?.autorizacion === 'A') {
      return {
        sw: true,
        msj: ''
      };
    }
  }
  //Validacion de credito

  let credito = null;

  if (permisos.ControlCarteraVencida || permisos.ControlCupoCredito) {
    const consultaCredito = await controlCreditoTercero(item.id);

    if (!consultaCredito?.ok || !consultaCredito.data?.length) {
      return {
        sw: false,
        autorizacion: 'S',
        msj: 'No fue posible validar el cupo del tercero, por favor verifique e intente nuevamente.'
      };
    }

    credito = consultaCredito.data[0];
  }

  if (permisos.ControlCarteraVencida && credito?.en_mora === 'S') {
    return {
      sw: false,
      autorizacion: 'S',
      msj: 'El tercero presenta cartera en mora, imposible continuar con la venta.'
    };
  }

  if (permisos.ControlCupoCredito && Number(credito?.cupo_disponible) < 0) {
    return {
      sw: false,
      autorizacion: 'S',
      msj: 'El tercero no tiene cupo de crédito disponible, imposible continuar con la venta.'
    };
  }

  return {
    sw: true,
    msj: ''
  };
};

const solicitarAutorizacion = async (id_pedido) => {
  if (!id_pedido) {
    Swal.fire("Error", "No se encontró el pedido.", "error");
    return;
  }
  $("#modalOptions").modal("hide");

  const resultado = await Swal.fire({
    html: `<p>Solicitud de desbloqueo</p><p>Pedido numero <strong>${id_pedido}</strong></p><p>¿Desea solicitar autorización?</p>
               <textarea 
                  id="mdl-texto-autorizacion" 
                  rows="4" 
                  cols="40" 
                  placeholder="Ingrese un comentario (opcional), máximo 100 caracteres" 
                  style="width: 100%; margin-top: 10px; padding: 10px;  border-radius: 5px;" 
                  maxlength="100"></textarea>`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Solicitar autorización',
    cancelButtonText: 'Cancelar'
  });


  if (!resultado.value) return;

  const comentario = $("#mdl-texto-autorizacion").val().trim();
  try {
    const resp = await enviarPeticion({
      link: "../models/Pedidos.php",
      op: "solicita_autorizacion",
      id_pedido,
      comentario: comentario ?? ""
    });
    if (resp.ok) {
      nuevoPedido([], false);
      $('#tabs button[data-bs-target="#navs-temporal"]').tab('show');
      await buscarTemporales();
      Swal.fire({
        icon: "success",
        title: "Solicitud enviada",
        text: resp.mensaje,
        timer: 2000,
        showConfirmButton: false
      });
    } else {
      console.error("Error al solicitar autorización:", resp);
      Swal.fire({
        icon: "error",
        title: "No fue posible solicitar la autorización",
        text: resp.mensaje
      });
    }
  } catch (error) {
    console.error(error);
    Swal.fire({
      icon: "error",
      title: "Error",
      text: "Ocurrió un problema al solicitar la autorización."
    });
  }
}

$(async function () {

  await listarAlmacenes();
  await condicionesPago('C', 'id_condicion');
  await tiposDocumentos(8, $("#bodegas").val(), 'tipos')
  permisos = await permisosUsuario(id_modulo)
  const { general, modulos } = await cargarConfiguraciones();
  ConfGeneral = general;
  ConfModulos = modulos;
  fechaActual([
    ['fecha_inicial', 'fecha_final']
  ]);
  const listaPrecio = (await cargarListaAlmacen())?.[0]?.lista_precios;

  if (listaPrecio) {
    $("#lista_almacen").val(listaPrecio);
  }

  $("#nuevo").click(function () {
    if ($("#numero").val() > 0) {
      addPedidoOcupado('eliminar_ocupado', $("#numero").val());
    }

    nuevoPedido();
    disabledEnabled(false)
    $('#tabs button[data-bs-target="#navs-creacion"]').attr('disabled', true)
    $("#carrito tbody tr").remove()
  });
  $("#limpiar-filtros").on("click", function () {
    fechaActual([['fecha_inicial', 'fecha_final']]);
    $("#txtBusquedaTerceroTmp").val("");
    $("#nit_tmp").val("");
    $("#temporales").html("");
    $("#p_temporales").text(0);
    $("#p_confirmados").text(0);
    $("#p_separados").text(0);
    $("#p_facturados").text(0);
    $("#p_despachados").text(0);
  });

  $("#notas").focus(function () {
    $("#tmp").val($(this).val())
  });

  $("#guardar").on("click", async function () {

    const numero = parseInt($("#numero").val()) || 0;
    const id = parseInt($("#id_pedido").val()) || 0;
    const tipo = $("#tipos").val();
    const id_tercero = parseInt($("#id_tercero").val()) || 0;

    if (id_tercero === 0) {
      Swal.fire("Ups", "Debe seleccionar un cliente.", "warning");
      return;
    }

    if (numero === 0) {
      Swal.fire("Ups", "Debe iniciar o cargar un pedido.", "warning");
      return;
    }

    $(this).prop("disabled", true);

    try {
      await guardarPedido(id, tipo, numero, id_tercero);
    } finally {
      $(this).prop("disabled", false);
    }

  });

  $("#notas").on('focusout', async function () {

    try {

      const nota = $.trim($(this).val());
      let sw = true
      if ($("#id_pedido").val() > 0) {
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
        id: $("#id_pedido").val(),
        notas: $("#notas").val()
      })

      if (!resp.ok) {
        Swal.fire("Error", "Error al actualizar el registro", "error");
        return;
      }
      $("#tmp").val("")

    } catch (e) {
      console.error(e)
      $(this).val($("#tmp").val())
    }

  });

  $("#buscar-temporal").on('click', function () {
    buscarTemporales();
  });

  $("#und_grid").on('change', function (e) {
    e.preventDefault();
    data_precio = $("#und_grid option:selected").attr("data-precio");

    if ($("#codigo_grid").val() != '') {
      const datosPrecio = calcularPrecioUnidad(data_precio,
        $("#und_grid option:selected").val(),
        $("#dto_grid").val(), $("#iva_grid").val())
      $("#preciob_grid").val(formatNum(datosPrecio.preciob, '$', ConfGeneral))
      $("#precion_grid").val(formatNum(datosPrecio.precion, '$', ConfGeneral));

      const stock = mostrarStockDisponible($("#stock_real").val(),
        $("#und_grid option:selected").val(),
        $("#und_grid option:selected").text().split("-")[0])
      $("#stock_grid").val(stock);

      const totales = calcularTotal(unformatNum($("#precio_unitario").val()),
        $("#dto_grid").val(),
        $("#iva_grid").val(),
        $("#cantidad_grid").val() * $("#und_grid option:selected").val());
      $("#total_grid").val(formatNum(totales.ttotal, '$', ConfGeneral))

      $("#cantidad_grid").focus().select();
      $("#precio_unitario").val(data_precio)
    }

  });

  $("#und_grid").on('keyup', function (e) {
    if ($("#codigo_grid").val() == '') {
      return;
    }

    if (e.key == 'Enter') {
      $("#cantidad_grid").focus().select();
    }
  });

  $("#cantidad_grid").on('keyup change', async function (e) {
    e.preventDefault()
    if (isNaN(parseInt($("#cantidad_grid").val())) || $("#cantidad_grid").val() == 0) {
      return;
    }

    const totales = calcularTotal(unformatNum($("#precio_unitario").val()),
      $("#dto_grid").val(),
      $("#iva_grid").val(),
      $("#cantidad_grid").val() * $("#und_grid option:selected").val());

    $("#total_grid").val(formatNum(totales.ttotal, '$'))

    if (e.key == 'Enter') {

      if (isNaN(parseInt($("#cantidad_grid").val())) || $("#cantidad_grid").val() == 0) {
        return;
      }

      // rentabilidad
      precio = $("#precion_grid").val() / (($("#iva_grid").val() / 100) + 1)
      if (!validarRentabilidad($("#costo_promedio").val(), precio)) {
        Swal.fire("Error", "El precio de venta esta por debajo del costo del producto", "error")
        return;
      }

      showLoadingWithTetrisLogo('Guardando item', 'ISA Software');
      try {

        //
        const numero = $("#numero").val()
        const listaAlm = parseInt($("#lista_almacen").val()) || 0;
        const listaTer = parseInt($("#lista_tercero").val()) || 0;
        const lista_precios = listaTer === 0 ? listaAlm : listaTer;

        let sw_add = true;

        if (isNaN(parseInt($("#numero").val())) || $("#numero").val() == 0) {
          //insertar el encabezado
          datosEncabezado = await enviarPeticion({
            link: '../shared/servicios/servicios.php',
            op: 'add_pedidos',
            id_tipo_documento: $("#tipos").val(),
            id_tercero: $("#id_tercero").val(),
            id_bodega: $("#bodegas").val(),
            notas: $("#notas").val(),
            valor_total: 0,
            iva: 0,
            descuento: 0,
            sw: 8,
            id_tercero_dir: $("#id_tercero_dir").val(),
            id_condicion: $("#id_condicion").val(),
            zona_ventas: $.trim($("#info_zona_venta").text()),
            zona_descripcion: $.trim($("#info_zona_descripcion").text()),
            lista_precios: lista_precios,
            estado: 'T',
            vendedor: 0,
            //vendedor:$("#vendedor").val()
          });

          $("#id_pedido").val(datosEncabezado.data.id);
          $("#numero").val(datosEncabezado.data.numero)

          if (datosEncabezado.error) {
            dissminSwal()
            Swal.fire("Error", "No se puedo insertar el documento", "error")
            return;
          }

        }


        //VERIFICO SI EL PRODUCTO YA FUE AGREGADO 
        $("#carrito tbody tr").each(async function () {

          const codigo = $.trim($(this).find("td").eq(0).html()).toUpperCase();
          const id = $.trim($(this).find("td:last").html())

          if (codigo == $.trim($("#codigo_grid").val()).toUpperCase()) {
            sw_add = false;
            let new_cantidad = 0;
            let total = 0;
            let cantidad_agregada = 0;

            const precion = unformatNum($("#precion_grid").val())

            if ($("#id_detalle").val() == 0 || $("#id_detalle").val() == '') {
              cantidad_agregada = parseInt($("#id_detalle" + id).find("td").eq(6).html());
              new_cantidad = cantidad_agregada + parseInt($("#cantidad_grid").val());
              total = new_cantidad * precion
            } else {
              cantidad_agregada = $("#cantidad_grid").val()
              new_cantidad = cantidad_agregada;
              total = new_cantidad * precion
            }

            updateDetalle = await enviarPeticion({
              link: '../shared/servicios/servicios.php',
              op: 'update_pedido_detalle',
              id: id,
              id_referencia: $("#id_referencia").val(),
              cantidad: new_cantidad * parseInt($("#und_grid option:selected").val()),
              can_und: $("#und_grid option:selected").val(),
              und: $.trim($("#und_grid option:selected").text().split("-")[0]),
              id_pedido: $("#id_pedido").val()
            });

            if (updateDetalle.error) {
              Swal.fire("Error", "Error al insertar el producto", "error")
              return;
            }
            $("#id_detalle" + id).find("td").eq(2).html($("#preciob_grid").val())
            $("#id_detalle" + id).find("td").eq(5).html($("#precion_grid").val())
            $("#id_detalle" + id).find("td").eq(6).html(new_cantidad)
            $("#id_detalle" + id).find("td").eq(8).html($.trim($("#und_grid option:selected").val()))
            $("#id_detalle" + id).find("td").eq(7).html($.trim($("#und_grid option:selected").text()))
            $("#id_detalle" + id).find("td").eq(9).html(formatNum(total, '$'))
            actualizarTotales(updateDetalle.totales[0])
            limpiarReferencia()
            dissminSwal();
            $(this).addClass("table-warning").focus()
            setTimeout(() => {
              $(this).removeClass("table-warning")
            }, 1000);
            return;
          }
        });

        if (!sw_add) {
          dissminSwal()
          return;
        }

        //inserto el detalle 
        const datosDetalle = await enviarPeticion({
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
          can_und: parseInt($("#und_grid option:selected").val()),
          sw: 8,
          id_bodega: $("#bodegas").val(),
          cantidad_desp: 0
        })

        if (datosDetalle.error) {
          dissminSwal()
          Swal.fire("Error", "No se pudo insertar el producto, por favor intente nuevamente!", "error")
          return;
        }

        //agrego el pedido a ocupado 
        addPedidoOcupado('marcar_ocupado', $("#numero").val());
        let itemCart = {}

        itemCart.id = datosDetalle.data.id
        itemCart.codigo = $("#codigo_grid").val(),
          itemCart.descripcion = $("#descripcion_grid").val()
        itemCart.marca = $("#laboratorio_grid").val()
        itemCart.preciob = $("#preciob_grid").val()
        itemCart.dto = $("#dto_grid").val()
        itemCart.iva = $("#iva_grid").val()
        itemCart.precion = $("#precion_grid").val()
        itemCart.cantidad = $("#cantidad_grid").val()
        itemCart.und = $.trim($("#und_grid option:selected").text().split("-")[0])
        itemCart.can_und = $("#und_grid option:selected").val()
        itemCart.vcmto = $("#vcmto_grid").val()
        itemCart.lote = $("#lote_grid").val()
        itemCart.total = $("#total_grid").val()
        await addCart(itemCart)
        await dissminSwal();

        actualizarTotales(datosDetalle.data.totales[0])
        activeDisabled();
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


  const modalBusquedaRef = new ModalBusquedaProducto((rowData) => {
    cargarItemGrid(rowData)
  }, { check_stock: false, cod_barras: false });
  let buscando = false;

  $("#filtro").on("keydown", async function (e) {
    if (e.key !== "Enter") return;
    e.preventDefault();
    if (buscando) return; // evita doble ejecución rápida
    const filtro = $.trim(this.value);
    if (!filtro) return;
    const listaAlmacen = parseInt($("#lista_almacen").val()) || 0;
    const listaTercero = parseInt($("#lista_tercero").val()) || 0;
    const lista = listaTercero === 0 ? listaAlmacen : listaTercero;
    if (!lista) {
      Swal.fire("Error", "No se ha asignado una lista de precios para el cliente o el almacén", "error");
      console.warn("Lista inválida");
      return;
    }
    try {
      buscando = true;
      const resp = await modalBusquedaRef.getRecords({
        data: filtro,
        cod_barras: false,
        lista: lista
      });
      const nregistros = modalBusquedaRef.getNrecords();
      if (!nregistros) return;
      if (nregistros === 1) {
        await cargarItemGrid(resp[0]);
        $("#filtro").val(""); // limpia después de agregar
      } else {
        modalBusquedaRef.show();
      }
    } catch (error) {
      console.error("Error en búsqueda:", error);
    } finally {
      buscando = false;
    }

  });


  $("#btnBusquedaAvanzada").click(function () {
    //
    if ($("#id_detalle").val() > 0) {
      return;
    }
    modalBusquedaRef.show();
    $("#busqueda-descripcion").val($("#filtro").vsl());
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
      showLoadingWithTetrisLogo('Cargando tercero', 'ISA Software');
      item = ui.item;
      const dir = await cargarDirecciones(item.id);
      console.log(dir);
      if (dir.length == 0) {
        Swal.fire("Error", "El tercero no tiene direcciones asignadas", "error");
        return;
      }

      let p = '';

      dir.forEach(item => {
        selected = ''
        if (item.principal == 1) {
          selected = 'selected'
        }
        p += `<option  ${selected} value="${item.id}">${item.direccion}</option>`
      })

      $("#id_tercero_dir").html(p);
      const validarBasica = ValidarDataBasicaTerceroCliente(item, permisos)
      const validarCartera = await CreditManagement(item.nit, permisos);

      $("#id_tercero").val(item.id);
      $("#nit").val(item.nit);
      $("#tratamiento").val(item.tratamiento);
      $("#razon_comercial").val(item.razon_comercial);
      $("#textDireccion").val(item.direccion)
      $("#id_condicion").val(item.id_condicion);
      $("#lista_tercero").val(parseInt(item.lista_precios));
      $("#info_condicion").text(item.des_condicion)
      $("#info_lista").text(item.lista_precios)
      $("#info_credito").text(formatNum(item.cupo_credito, '$'))
      $("#info_zona_venta").text(item.codigo_zona)
      $("#info_zona_descripcion").text(item.zona_descripcion);
      $("#info_email").text(item.email_fe);
      $('#tabs button[data-bs-target="#navs-creacion"]').attr('disabled', false)
      dissminSwal()
    }
  });

  $("#txtBusquedaTercero").focus();
  dissminSwal();

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
})

$(document).keydown(function (e) {

  if (!$("#modalBusquedaRef").is(":visible")) {

    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
      $("#und_grid").focus()
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
});
