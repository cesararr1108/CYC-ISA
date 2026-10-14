let arrReferenciasOC = [];
const id_modulo = 22;
let permisos;
let conceptosRetencion = [];

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
};
const getDirecciones = async (id) => {
  try {
    const dataSend = {
      link: '../models/Terceros.php',
      op: 's_terceros_direcciones',
      id_tercero: id
    };
    const resp = await enviarPeticion(dataSend);
    let options;
    if (resp.data.length > 0) {
      resp.data.forEach((d) => {
        options += `<option value="${d.id}">${d.direccion}</option>`
      });
    }
    $("#direcciones").html(options);
  } catch (e) {
    console.error(e); // Agrega manejo de errores 
    Swal.fire('Error!', 'Un error ha ocurrido, por favor notifique a su administrador.', 'error');
  }
};

const buscarPedidos = async () => {
  try {
    const fecha_inicial = $.trim($("#fhIni").val());
    const fecha_final = $.trim($("#fhFin").val());

    showLoadingWithTetrisLogo('Buscando información', 'ISA Software')
    const resp = await enviarPeticion({
      op: 'busqueda_pedido',
      link: '../shared/servicios/servicios.php',
      fecha_inicial,
      fecha_final,
      estado: 'P',
      sw: 16
    });
    console.log({
      resp
    })
    dissminSwal();

    if (resp.data.length == 0) {
      $("#result-ordenes").html(`
        <div class="alert alert-warning alert-dismissible mt-3" role="alert">
            <span><i class="fa-regular fa-bell"></i></span>
            No se encontraron resultados!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`);
      return;
    }

    let tabla = `<table class="table table-sm table-hover table-striped mt-2" width="100%">
                  <thead class="table-primary">
                    <tr>
                      <th>Bodega</th>
                      <th>Usuario</th>
                      <th>Fecha</th>
                      <th>Numero</th>
                      <th>Nit</th>
                      <th>Tercero</th>
                      <th>Valor</th>
                      <th>Pcj ingreso</th>
                      <th>Cargar</th>
                     <!-- <th>Flujo</th> -->
                    </tr>
                  </thead>
                  <tbody>`;
    resp.data.forEach(element => {

      clase_pcj_ingreso = '';
      if (element.porcentaje_ingresado == 100) {
        clase_pcj_ingreso = 'bg-success';
      } else if (element.porcentaje_ingresado > 0 && element.porcentaje_ingresado < 100) {
        clase_pcj_ingreso = 'bg-warning';
      } else if (element.porcentaje_ingresado == 0) {
        clase_pcj_ingreso = 'bg-danger';
      }


      tabla += `<tr>
                    <td>${element.bodega}</td>
                    <td>${element.usuario}</td>
                    <td>${element.fecha_hora}</td>
                    <td>${element.id_pedido}</td>
                    <td>${element.nit}</td>
                    <td>${element.nombres}</td>
                    <td>${formatNum(element.valor_total, '$')}</td>
                    <td> <span class="badge rounded-pill  ${clase_pcj_ingreso}"> ${element.porcentaje_ingresado}%</span></td>
                    <td align="center"><button class="btn btn-outline-primary btn-sm btn-recuperar" data-element='${JSON.stringify(element)}'><i class="fa-regular fa-circle-down"></i></button></td>
                    <!-- <td align="center"><button class="btn btn-outline-success btn-sm btn-flujo" data-element='${JSON.stringify(element)}'><i class="fa-solid fa-bars-staggered"></i></button></td> -->
                </tr>`;
    });

    tabla += `</tbody></table>`;

    $("#result-ordenes").html(tabla);
    $(".btn-recuperar").click(function () {

      const element = JSON.parse($(this).attr("data-element"));
      console.error({
        element
      })
      if (element.id_transaccion_tmp > 0) {
        Swal.fire({
          title: "Hemos detectado un ingreso temporal asociado a esta OC",
          text: "Desea recuperarlo o desecharlo e iniciar uno nuevo!",
          icon: "question",
          showCancelButton: true,
          confirmButtonColor: "#3085d6",
          cancelButtonColor: "#d33",
          confirmButtonText: "Recuperar",
          cancelButtonText: "Nuevo"
        }).then(async (result) => {
          if (result.isConfirmed) {
            cargarOrden(element);
            recalcularEncabezado();
          } else {
            await deleteTransaccion(element.id_transaccion_tmp);
            element.id_transaccion_tmp = 0;
            cargarOrden(element);
          }
        });
      } else {
        cargarOrden(element);
      }

    });
    $(".btn-flujo").click(function () {
      const element = JSON.parse($(this).attr("data-element"));
      flujo(element);
    });

  } catch (e) {
    console.error(e);
    dissminSwal();
    Swal.fire("Error", "Se generó un error interno", "error");
  }
};

const flujo = async (data) => {
  const resp = await enviarPeticion({
    op: 'flujo_pedido',
    link: '../shared/servicios/servicios.php',
    id: data.id_pedido
  });
  $("#fl-cabecera").text(`ORDEN DE COMPRA : ${data.id_pedido} - ${data.nombres}`);
  let options = ``;

  if (resp.data.length > 0) {
    resp.data.forEach((d) => {
      if (d.id_doc != null) {
        options += `
            <li onclick="ImprimirDocumento(1, ${d.id_doc})" class="list-group-item node-treeview1">
<span class="indent"></span>
                <i class="fa-regular fa-circle"></i>
                <span class="icon node-icon"></span>
                ${d.tipo_doc} - ${d.numero_doc} 
                <i class="fa-solid fa-arrow-right-long"></i> 
                <b>USUARIO</b> (${d.usuario}) 
                <b>FECHA/HORA</b> ${d.fecha_hora}
            </li>`;
      }

    });
  }

  $("#fl-cuerpo").html(options);
  $("#flujo").modal('show');
}

const deleteTransaccion = async (id) => {
  const resp = await enviarPeticion({
    op: 'delete_transaccion',
    link: '../shared/servicios/servicios.php',
    id_transaccion: id
  });

  if (resp.error) {
    Swal.fire('Error', 'ha ocurrido un error', 'error');
    return;
  }
}
/*
 @ Cargar orden de compra seleccionada
*/
const cargarOrden = async (data) => {
  console.log({ data })
  $("#numero").val(data.num_transaccion_tmp);
  if (data.id_concepto_retencion > 0) {
    $("#concepto_retencion").val(data.id_concepto_retencion).attr('disabled', true);
  }

  $("#id_tercero").val(data.id_tercero);
  $("#id_perfil_tributario").val(data.id_perfil_tributario || 0);
  cargarPerfilTributarioPorId(data.id_perfil_tributario || 0);
  $('#tabs button[data-bs-target="#navs-creacion"]').tab('show');
  $("#id_transaccion").val(data.id_transaccion_tmp);
  $("#condicion").val(data.condicion);
  $("#nit").val(data.nit);
  $("#tratamiento").val(data.nombres);
  $("#razon_comercial").val(data.razon_comercial);
  $("#bodegas").val(data.id_bodega);
  $("#notas").val(data.notas);
  $("#id_pedido").val(data.id_pedido);
  $("#n_total").val(formatNum(data.valor_total, '$'));
  getDirecciones(data.id_tercero);
  $("#direcciones").val(data.id_tercero_dir);
  $("#buscar_ref").attr('readonly', false).focus();
  $("#buscar_ter").attr('readonly', true);
  $("#buscar_ter").val(data.nit + ' - ' + data.nombres);
  $("#email_fe").val(data.email_fe);

  if (data.documento != null && data.documento != undefined && data.documento != '') {
    $("#referencia").val(data.documento);
  }

  try {

    let item = await enviarPeticion({
      op: 'buscar_detalle_pedido',
      link: '../shared/servicios/servicios.php',
      id_pedido: data.id_pedido
    });

    if (data.id_transaccion_tmp > 0) {

      try {
        //busca los items que estan en t_transaciones_detalle_tmp que no estan en t_pedidos_detalle con el id_transaccion_tmp
        let item_tmp = await enviarPeticion({
          op: 'buscar_detalle_transaccion_tmp',
          link: '../shared/servicios/servicios.php',
          id_transaccion: data.id_transaccion_tmp,
          id_pedido: data.id_pedido
        });

        if (item_tmp.length > 0) {
          // uno item y item_tmp  como si vinieran de la misma peticion 
          item = item.concat(item_tmp);
        }

      } catch (error) {
        console.error(error)
      }
    }
    /*
    let item = await enviarPeticion({
      op: 'buscar_detalle_pedido',
      link: '../shared/servicios/servicios.php',
      id_pedido: data.id_pedido
    });*/

    if (item.length > 0) {
      arrReferenciasOC = item;
      recalcularEncabezado();
      viewTableProductos();
      $('#tabs button[data-bs-target="#navs-productos"]').removeClass('disabled').removeAttr('disabled');
    }
    $('#tabs button[data-bs-target="#navs-creacion"]').tab('show');

  } catch (e) {
    console.error(e)
    Swal.fire("Ups", "Se produjo un error al cargar los items", "error")
  }

  //$("#modalOrden").modal('hide');

}

const viewTableProductos = () => {
  $("#items_ tbody").html('');
  arrReferenciasOC.forEach(row => {

    const dataorg = row;
    row = row.datos;
    let pcj_iva = row.pcj_iva;
    let pcj_dto = row.pcj_descuento;
    let costo = (row.costo_unitario * row.can_und).toFixed(2);
    let cantidad = row.cantidad / row.can_und;
    let costo_con_descuento = (row.costo_unitario * row.can_und) * (1 - (pcj_dto / 100));
    let costo_neto = (costo_con_descuento * ((pcj_iva / 100) + 1)).toFixed(2);
    let costo_total = (costo_neto * cantidad).toFixed(2);
    let pendientes = parseInt(cantidad) - (parseInt(row.ingresadas) + parseInt(row.temporales));

    classPedido = '';

    if (row.id == 0) {
      classPedido = 'table-danger';
    }
    /*
    tr = `  <tr data-element='${JSON.stringify(dataorg)}'>
                <td colspan="10"><b>${row.codigo} - ${row.descripcion} - </b><span class="text-primary">${row.marca}</span></td>
            </tr>
            <tr class="det_${row.id} ${classPedido}">
                <td>${formatNum(costo, '$')}</td>
                <td>${pcj_iva}</td>
                <td>${pcj_dto}</td>
                <td>${formatNum(costo_neto, '$')}</td>
                <td>${cantidad} - ${row.und}</td>
                <td>${formatNum(costo_total, '$')}</td>
                <td align="center"><span class="badge badge-center rounded-pill bg-label-primary">${parseInt(row.ingresadas)}</span></td>
                <td align="center"><span class="badge badge-center rounded-pill bg-label-danger">${pendientes}</span></td>
                <td align="center"><span class="badge badge-center rounded-pill bg-label-success">${parseInt(row.temporales)}</span></td>
                <td align="center"><button class="btn btn-outline-primary btn-sm" onclick="verLotes(${row.id_referencia})"><i class="fa-regular fa-square-plus"></i></button></td>
              </tr> `;*/
    if (pendientes <= 0) {
      cl_tr = 'table-success';
    } else {
      cl_tr = '';
    }
    tr = `  
            <tr class="det_${row.id} ${classPedido} ${cl_tr}" data-element='${JSON.stringify(dataorg)}'>
                <td>${row.codigo} - ${row.descripcion} - <span class="text-primary">${row.marca}</span></td>
                <td>${formatNum(costo, '$')}</td>
                <td>${pcj_iva}</td>
                <td>${pcj_dto}</td>
                <td>${formatNum(costo_neto, '$')}</td>
                <td>${cantidad} - ${row.und}</td>
                <td>${formatNum(costo_total, '$')}</td>
                <td align="center"><span class="badge badge-center rounded-pill bg-label-primary">${parseInt(row.ingresadas)}</span></td>
                <td align="center"><span class="badge badge-center rounded-pill bg-label-danger">${pendientes}</span></td>
                <td align="center"><span class="badge badge-center rounded-pill bg-label-success">${parseInt(row.temporales)}</span></td>
                <td align="center"><button class="btn btn-outline-primary btn-sm" onclick="verLotes(${row.id_referencia})"><i class="fa-regular fa-square-plus"></i></button></td>
              </tr> `;
    $("#items_ tbody").append(tr);
  });
}
const verLotes = (id_ref) => {

  const ref = arrReferenciasOC.find(item =>
    item.posiciones.some(pos => parseInt(pos.id_referencia) === parseInt(id_ref))
  );

  let html = ``;
  if (ref) {
    html = `<table class="table table-sm table-hover table-striped" id="">
                <thead class="table-primary" >
                  <tr class="text-primary">
                    <th>ID Ref</th>
                    <th>ID Pos</th>
                    <th>Lote</th>
                    <th>Vencimiento</th>
                    <th>Cantidad</th>
                    <th>Eliminar</th>
                  </tr>
                </thead>
                <tbody>`;
    ref.posiciones.forEach(pos => {
      html += `<tr>
                  <td>${id_ref}</td>    
                  <td>${pos.id}</td>
                  <td>${pos.lote}</td>
                  <td>${pos.vmcto}</td>
                  <td align="center">${parseInt(pos.cantidad)}</td>
                  <td align="center"><button class="btn btn-outline-danger btn-sm" onclick="deleteItem(${pos.id},${parseInt(id_ref)})"><i class="fa-regular fa-trash-can"></i></button></td>
                </tr>`;
    });

    html += `</tbody></table>`;
  } else {
    html = `<div class="alert alert-warning alert-dismissible mt-3" role="alert">
                <span><i class="fa-regular fa-bell"></i></span> No se encontraron resultados!
            </div>`;
  }

  $("#modal-posiciones-body").html(html);
  $("#modal-posiciones").modal('show');
}


const filtrarProductos = (query) => {
  query = query.toLowerCase();
  return arrReferenciasOC.filter(item => {
    if (item.ean.some(ean => ean.includes(query))) return true;
    if (item.datos.codigo.toLowerCase().includes(query)) return true;
    if (item.datos.descripcion.toLowerCase().includes(query)) return true;
    return false;
  });
}

const obtenerOpcionesAutocomplete = (query) => {
  const productosFiltrados = filtrarProductos(query);
  return productosFiltrados.map(producto => {
    return {
      label: `${producto.datos.descripcion} (Código: ${producto.datos.codigo}, EAN: ${producto.ean.join(', ')})`,
      value: producto.datos.descripcion,
      producto: producto
    };
  });
}

const addReferencia = async (id_ref, cantidad, lote, vmcto, id_tran) => {
  vmcto = (vmcto === undefined) ? 'N/A' : vmcto;
  lote = (lote === undefined) ? 'N/A' : lote;
  const ref = arrReferenciasOC.find(item => parseInt(item.datos.id_referencia) === parseInt(id_ref));


  if (ref) {

    ref.datos.temporales = parseInt(ref.datos.temporales) + cantidad;

    if (!ref.posiciones) {
      ref.posiciones = [];
    }
    const posicionExistente = ref.posiciones.find(pos => parseInt(pos.id_referencia) === parseInt(id_ref) && parseInt(pos.id) === parseInt(id_tran));

    if (posicionExistente) {
      posicionExistente.cantidad = parseInt(posicionExistente.cantidad) + cantidad;
    } else {

      ref.posiciones.push({
        id_referencia: id_ref,
        id: id_tran,
        lote: lote,
        cantidad: cantidad,
        vmcto: vmcto
      });
    }
  }
  viewTableProductos();
  recalcularEncabezado();
  limpiarReferencia();
}
const deleteReferencia = async (id_ref, id_tran) => {
  const ref = arrReferenciasOC.find(item => parseInt(item.datos.id_referencia) === parseInt(id_ref));

  if (ref) {
    const posicionExistente = ref.posiciones.find(pos => parseInt(pos.id_referencia) === parseInt(id_ref) && parseInt(pos.id) === parseInt(id_tran));
    if (posicionExistente) {
      const index = ref.posiciones.indexOf(posicionExistente);
      if (index > -1) {

        ref.datos.temporales = parseInt(ref.datos.temporales) - parseInt(posicionExistente.cantidad);

        ref.posiciones.splice(index, 1);
        await recalcularEncabezado();
        viewTableProductos();
      }
    } else {
      console.log("No se encontró la posición con el id_tran especificado.");
    }
  } else {
    console.log("No se encontró la referencia con el id_referencia especificado.");
  }
}


const recalcularEncabezado = async () => {

  let resp = await enviarPeticion({
    op: 'update_encabezado',
    link: '../shared/servicios/servicios.php',
    id_transaccion: $("#id_transaccion").val()
  });
  console.log({ resp })
  if (!resp.error) {
    $("#in-total-bruto").val(formatNum(parseFloat(resp.valor_total) + parseFloat(resp.descuento) - parseFloat(resp.iva), '$', ConfGeneral));
    $("#in-total-dcto").val(formatNum(parseFloat(resp.descuento), '$', ConfGeneral));
    $("#in-total-iva").val(formatNum(parseFloat(resp.iva), '$', ConfGeneral));
    $("#in-total").val(formatNum(parseFloat(resp.valor_total), '$', ConfGeneral));
    //retenciones 

    $("#total-ret-fte").val(formatNum(parseFloat(resp.retencion_fuente), '$', ConfGeneral));
    $("#total-ret-ica").val(formatNum(parseFloat(resp.retencion_ica), '$', ConfGeneral));
    $("#total-ret-iva").val(formatNum(parseFloat(resp.retencion_iva), '$', ConfGeneral));
    $("#total-ret-total").val(formatNum(parseFloat(resp.retencion_fuente) + parseFloat(resp.retencion_ica) + parseFloat(resp.retencion_iva) + parseFloat(resp.retencion_cree), '$', ConfGeneral));
    $("#total-neto-pagar").val(formatNum(parseFloat(resp.total_pagar), '$', ConfGeneral));

  }
}

const deleteItem = (id_pos, id_ref) => {
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
      let resp = await enviarPeticion({
        link: '../shared/servicios/servicios.php',
        op: 'delete_item',
        id: id_pos,
        id_transaccion
      });


      if (resp.error) {
        Swal.fire("Error", resp.message, "error")
        return;
      }
      recalcularEncabezado();

      if (resp.total_reg == 0) {

        $("#id_transaccion").val(0);
        //elimino el encbezado de t_transacciones_tmp 
        await enviarPeticion({
          link: '../shared/servicios/servicios.php',
          op: 'delete_transaccion',
          id_transaccion: id_transaccion
        });
      }
      deleteReferencia(id_ref, id_pos);
      verLotes(id_ref);


    }
  });

}

// Función para obtener productos con pendientes
const obtenerProductosPendientes = () => {
  const productosPendientes = [];

  arrReferenciasOC.forEach(item => {
    const row = item.datos;
    const cantidad = row.cantidad / row.can_und;
    const pendientes = parseInt(cantidad) - (parseInt(row.ingresadas) + parseInt(row.temporales));

    if (pendientes > 0) {
      productosPendientes.push({
        codigo: row.codigo,
        descripcion: row.descripcion,
        marca: row.marca,
        cantidad_ordenada: cantidad,
        ingresadas: parseInt(row.ingresadas),
        temporales: parseInt(row.temporales),
        pendientes: pendientes,
        und: row.und
      });
    }
  });

  return productosPendientes;
};

// Función para mostrar el modal de productos pendientes
const mostrarModalProductosPendientes = () => {
  const productosPendientes = obtenerProductosPendientes();

  if (productosPendientes.length === 0) {
    // Si no hay pendientes, proceder directamente a guardar
    confirmarGuardado();
    return;
  }

  // Construir tabla HTML
  let tablaHTML = `
    <table class="table table-hover table-sm mb-0">
      <thead class="table-light">
        <tr class="text-muted" style="font-size: 0.85rem;">
          <th><i class="fa-solid fa-box"></i> Código</th>
          <th>Descripción / Marca</th>
          <th class="text-center">Ordenado</th>
          <th class="text-center">Ingresado</th>
          <th class="text-center">Temporal</th>
          <th class="text-center text-danger"><i class="fa-solid fa-exclamation-circle"></i> Pendiente</th>
        </tr>
      </thead>
      <tbody>
  `;

  productosPendientes.forEach(producto => {
    tablaHTML += `
      <tr class="align-middle">
        <td>
          <span class="badge bg-light text-dark">${producto.codigo}</span>
        </td>
        <td>
          <small><strong>${producto.descripcion}</strong></small><br>
          <span class="text-muted" style="font-size: 0.8rem;"><i class="fa-solid fa-tag"></i> ${producto.marca}</span>
        </td>
        <td class="text-center">
          <span class="badge bg-info">${producto.cantidad_ordenada} ${producto.und}</span>
        </td>
        <td class="text-center">
          <span class="badge bg-success">${producto.ingresadas}</span>
        </td>
        <td class="text-center">
          <span class="badge bg-primary">${producto.temporales}</span>
        </td>
        <td class="text-center">
          <span class="badge bg-danger" style="font-size: 1rem; padding: 0.5rem;">
            <strong>${producto.pendientes}</strong>
          </span>
        </td>
      </tr>
    `;
  });

  tablaHTML += `
      </tbody>
    </table>
    <div class="alert alert-warning mt-3 mb-0">
      <small>
        <i class="fa-solid fa-info-circle"></i> 
        <strong>Nota:</strong> Tiene <strong>${productosPendientes.length}</strong> producto(s) con cantidad(es) pendiente(s). 
        ¿Desea continuar con el guardado?
      </small>
    </div>
  `;

  // Llenar el contenido del modal
  $("#modal-pendientes-content").html(tablaHTML);

  // Mostrar el modal
  $("#modal-productos-pendientes").modal('show');
};

// Función para confirmar el guardado
const confirmarGuardado = () => {
  Swal.fire({
    title: "¿Está seguro de confirmar el ingreso?",
    text: "Esta acción no puede reversarse!",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Sí, confirmar",
    cancelButtonText: "Cancelar"
  }).then(async (result) => {
    if (result.isConfirmed) {

      if (ConfGeneral.ManejaFechaLote == 'S') {
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

      let resp = await enviarPeticion({
        link: '../shared/servicios/servicios.php',
        op: 'volcar_documento',
        id_transaccion: $("#id_transaccion").val()
      });

      if (resp.error) {
        Swal.fire("Error", resp.message, "error")
        return;
      }

      nuevoPedido();
      ImprimirDocumento(1, resp.id);
      dissminSwal();
    }
  });
};

const ingresar = async () => {
  const id_transaccion = $("#id_transaccion").val();

  if (id_transaccion > 0) {
    Swal.fire({
      title: "Esta seguro de confirmar el ingreso?",
      text: "Esta acción no puede reversarse!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Si"
    }).then(async (result) => {
      if (result.isConfirmed) {
        let resp = await enviarPeticion({
          link: '../shared/servicios/servicios.php',
          op: 'volcar_documento',
          id_transaccion
        });
        console.log({ resp })

        if (resp.error) {
          Swal.fire("Error", resp.message, "error")
          return;
        }
        nuevoPedido();
        ImprimirDocumento(1, resp.id);
        dissminSwal();
      }
    });
  } else {
    Swal.fire('Error!', 'No hay nada que ingresar.', 'error');
  }

}

const nuevoPedido = async () => {

  showLoadingWithTetrisLogo('Cargando información', 'ISA Software')
  $("#numero").val('');
  $("#id_tercero").val('');
  $("#id_transaccion").val('');
  $("#nit").val('');
  $("#tratamiento").val('');
  $("#razon_comercial").val('');
  $("#notas").val('');
  $("#id_pedido").val(0);
  $("#n_total").val(formatNum(0, '$'));
  $("#in-total").val(formatNum(0, '$'));
  $("#direcciones").html('');
  $("#buscar_ter").attr('readonly', false);
  $("#buscar_ref").attr('readonly', true);
  $("#buscar_ter").val('');
  $("#email_fe").val('');
  $("#items_ tbody").html('');
  $("#in-total-bruto").val(formatNum(0, '$'));
  $("#in-total-dcto").val(formatNum(0, '$'));
  $("#in-total-iva").val(formatNum(0, '$'));
  $("#in-total-neto").val(formatNum(0, '$'));
  $("#total-ret-fte").val(formatNum(0, '$'));
  $("#total-ret-ica").val(formatNum(0, '$'));
  $("#total-ret-iva").val(formatNum(0, '$'));
  $("#total-ret-total").val(formatNum(0, '$'));
  $("#total-neto-pagar").val(formatNum(0, '$'));
  $("#fecha").val(new Date().toISOString().split('T')[0]);

  $("#concepto_retencion").val('').attr('disabled', false);
  limpiarPerfilTributarioCompra();
  limpiarReferencia();

  conceptosRetencion = [];
  arrReferenciasOC = [];
  await listarAlmacenes();
  await listarTipoDocumentos(1);
  await condicionesPago('P', 'condicion');
  $('[data-bs-target="#navs-productos"]').addClass('disabled').attr('disabled', 'disabled');
  $('#tabs button[data-bs-target="#navs-creacion"]').tab('show');
  dissminSwal();
}


const cargarReferencia_ = (item) => {

  let cantidad = item.datos.cantidad / item.datos.can_und;
  let pendientes = cantidad - (parseInt(item.datos.ingresadas) + parseInt(item.datos.temporales));

  if (pendientes <= 0) {
    Swal.fire('Información', 'No hay pendientes por ingresar para este producto', 'info');
    $("#buscar_ref_oc").val('').focus();
    return;
  }


  let pcj_iva = item.datos.pcj_iva;
  let pcj_dto = item.datos.pcj_descuento;
  let costo = (item.datos.costo_unitario * item.datos.can_und).toFixed(2);
  let costo_con_descuento = (item.datos.costo_unitario * item.datos.can_und) * (1 - (pcj_dto / 100));
  let costo_neto = (costo_con_descuento * ((pcj_iva / 100) + 1)).toFixed(2);
  let costo_total = (costo_neto * cantidad).toFixed(2);
  let undOpt = ``;

  unidades = [
    [item.datos.und_1, item.datos.cant_1, 1],
    [item.datos.und_2, item.datos.cant_2, 2],
    [item.datos.und_3, item.datos.cant_3, 3],
    [item.datos.und_4, item.datos.cant_4, 4]
  ]
  unidades.sort(function (a, b) {
    return b[2] - a[2];
  });

  let sw_seleccionada = false;

  unidades.forEach((element) => {
    let selected = ''

    if (element[1] > 0) {

      if (item.datos.und != null && item.datos.und.trim() != '') {
        if (element[0].trim().toUpperCase() == item.datos.und.trim().toUpperCase()) {
          selected = 'selected'
          sw_seleccionada = true;
        }
      }
      undOpt += `<option ${selected} value="${element[1]}">${element[0]}-${element[1]}</option>`;
    }

  });

  $("#in-unidad").html(undOpt)

  if (!sw_seleccionada) {
    //selecciono el primer option del select in-unidad
    $("#in-unidad option:first").attr('selected', 'selected');
  }

  if (item.datos.maneja_lote.trim() == 'S') {
    $("#row_lotes").show();
  } else {
    $("#row_lotes").hide();
  }

  $("#id_pedido_ref").val(item.datos.id_pedido);
  $("#id_referencia").val(item.datos.id_referencia);
  $("#in-pendientes").val(pendientes);
  $("#in-id-referencia").val(item.datos.id_referencia);
  $("#in-costo-neto").val(costo_neto);
  $("#in-costo").val(costo);
  $("#in-iva").val(pcj_iva);
  $("#in-dto").val(pcj_dto);
  $("#in-stock").val(item.datos.total_stock);
  $("#in-codigo").val(item.datos.codigo);
  $("#in-cantidad-orden").val(cantidad);
  $("#in-descripcion_corta").val(item.datos.descripcion);
  $("#in-descripcion_larga").val(item.datos.descripcion_larga);
  $("#in-maneja-lote").val(item.datos.maneja_lote);
  $("#promedio").val(formatNum(item.datos.promedio * item.datos.can_und, '$', ConfGeneral));
  $("#ultimo_costo").val(formatNum(item.datos.ultimo_costo, '$', ConfGeneral));
  $("#in-impoconsumo").val(item.datos.impoconsumo)

  if (item.datos.porcentaje_icui > 0) {
    $("#in-icui").attr("readonly", "readonly")
    $("#in-icui").val(item.datos.porcentaje_icui)
    $("#in-ibua").attr("readonly", "readonly")
    $("#in-ibua").val(0)
  } else if (item.datos.ibua > 0) {
    $("#in-icui").attr("readonly", "readonly")
    $("#in-icui").val(0)
    $("#in-ibua").attr("readonly", "")
    $("#in-ibua").val(item.datos.ibua)
  } else {
    $("#in-icui").attr("readonly", "")
    $("#in-ibua").attr("readonly", "")
    $("#in-icui").val(0)
    $("#in-ibua").val(0)
  }


  if (item.datos.maneja_lote == 'N') {
    $("#lote").removeAttr('required').prop('disabled', true).val('0');
    $("#vmcto").removeAttr('required').prop('disabled', true).val('1900-01-01');
  } else {
    $("#lote").attr('required', 'required').prop('disabled', false).val('');
    $("#vmcto").attr('required', 'required').prop('disabled', false).val('');
  }
  $("#stock").val(item.datos.total_stock + " unidades");
  $("#cantidad").val('');
  $("#modalAdd").modal('show');

  /* const elements = document.querySelectorAll('.format-num');
   elements.forEach((element) => {
     new Cleave(element, {
       numeral: true,
       numeralThousandsGroupStyle: 'thousand',
       numeralDecimalMark: '.',
       numeralDecimalScale: 2,
     });
   });*/
  setTimeout(() => {
    if ($("#in-maneja-lote").val() == 'S') {
      $("#in-lote").focus();
    } else {
      $("#in-cantidad").focus();
    }

  }, 500);

  actualizarCostoNeto();

}


const limpiarReferencia = () => {
  $("#in-costo_unitario").val("0");
  $("#in-descripcion_corta").val('');
  $("#in-descripcion_larga").val('');
  $("#in-codigo").val('');
  $("#in-unidad").html('');
  $("#in-iva").val(0);
  $("#in-costo_neto").val(0);
  $("#in-lote").val('');
  $("#in-vmcto").val('');
  $("#in-cantidad").val(0);
  $("#filtro").val('');
  $("#id_transaccion_det").val(0)
  $("#id_referencia").val(0)
  $("#in-costo").val(formatNum(0, '$', ConfGeneral));
  $("#in-ibua").val(0)
  $("#in-icui").val(0)
  $("#promedio").val(0)
  $("#ultimo_costo").val(0)
  $("#in-impoconsumo").val(0)
  $("#costo_total").val(0)
  $("#in-pcj_descuento").val(0)
  $("#FormDetalle").removeClass("was-validated");
  $("#tipo_precio").val(0)
  $("#precio_venta").val(0)
  $("#valor_tipo_precio").val(0)
  $("#in-cantidad").val(0);
  $("#id_detalle").val(0);
  $("#buscar_ref_oc").val('').focus();
  arrayReferencias = [];
}

const cargarReferencia = async (ob) => {
  console.warn({ ob })
  limpiarReferencia();
  $("#id_referencia").val(ob.id);
  $("#in-descripcion_corta").val(ob.descripcion);
  $("#in-descripcion_larga").val(ob.descripcion_larga);
  $("#in-codigo").val(ob.codigo);

  if ($.trim(ob.maneja_lote) == 'N') {
    $("#in-lote,#in-vmcto").prop("readonly", true);
    // Valores por defecto cuando no maneja lote
    $("#in-vmcto").val("1900-01-01");
    $("#in-lote").val("0");
    $("#id_lote").val("0");
    $("#in-cantidad").attr("tabindex", "8");
  } else {
    $("#in-lote,#in-vmcto").prop("readonly", false);
    $("#in-vmcto").val("");
    $("#in-lote").val("");
    $("#id_lote").val("0")
  }

  $("#promedio").val(formatNum(ob.promedio, '$', ConfGeneral));

  //cargarUltimoCosto(ob.id)


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


  $("#in-costo").val(costo_unitario);
  $("#in-costo_unitario").val(ob.costo_unitario);
  $("#in-iva").val(ob.iva);
  $("#in-impoconsumo").val(ob.impoconsumo)

  if (ob.porcentaje_icui > 0) {
    $("#in-icui").attr("readonly", "readonly")
    $("#in-icui").val(ob.porcentaje_icui)
    $("#in-ibua").attr("readonly", "readonly")
    $("#in-ibua").val(0)
  } else if (ob.ibua > 0) {
    $("#in-icui").attr("readonly", "readonly")
    $("#in-icui").val(0)
    $("#in-ibua").attr("readonly", "")
    $("#in-ibua").val(ob.ibua)
  } else {
    $("#in-icui").attr("readonly", "")
    $("#in-ibua").attr("readonly", "")
    $("#in-icui").val(0)
    $("#in-ibua").val(0)
  }

  $("#modalAdd").modal('show');
  let iva = $("#iva").val();
  let costo_neto = parseFloat(costo_unitario) * (1 + (parseFloat(iva) / 100))
  $("#in-costo_neto").val(formatNum(costo_neto, '$', ConfGeneral));
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
  console.log({
    undOpt
  })
  $("#in-unidad").html(undOpt).trigger("change");
  $("#in-ultimo_costo").val(formatNum(ob.ultimo_costo_compra * $("#in-unidad option:selected").val(), '$', ConfGeneral));

  enviarPeticion({
    link: '../models/IngresosMercancias.php',
    id_transaccion: $("#id_transaccion").val(),
    id_referencia: ob.id,
    op: 'cargar-precios'
  })
    .then(resp => {

      if (resp.length > 0) {
        $("#tipo_precio").val(resp[0].tipo_lista)
        if (ob.tipo_lista != 'PF') {
          $("#valor_tipo_precio").val(resp[0].precio)
        } else {
          $("#valor_tipo_precio").val(formatNum(resp[0].precio, '$', ConfGeneral))
        }
      } else {
        $("#tipo_precio").val('PF');

        if (ob.tipo_lista != 'PF') {
          $("#valor_tipo_precio").val(0)
        } else {
          $("#valor_tipo_precio").val(formatNum(0, '$', ConfGeneral))
        }
      }
      // calcularPrecio();
      precio = calcularPrecio($("#tipo_precio").val(),
        $("#valor_tipo_precio").val(),
        $("#unidad option:selected").val(),
        unformatNum($("#in-promedio").val()),
        parseFloat($("#in-iva").val()),
        $("#in-ultimo_costo").val()
      )
      $("#precio_venta").val(formatNum(precio, '$', ConfGeneral))
    })
    .catch(e => {
      console.error(e)
    })

  setTimeout(function () {
    $("#in-costo").focus().select();
  }, 500);

  new Cleave(".format-num", {
    numeral: true,
    numeralThousandsGroupStyle: 'thousand',
    numeralDecimalMark: '.',
    numeralDecimalScale: 2,
  });

}

const actualizarCostoNeto = () => {
  // Obtener el costo unitario, quitando comas si las hay
  let costo_unitario = parseFloat($.trim($("#in-costo").val()).replace(/,/g, ''));
  const pcj_descuento = parseFloat(unformatNum($("#in-pcj_descuento").val()));
  const iva = isNaN(parseFloat($("#in-iva").val())) ? 0 : parseFloat($("#in-iva").val());
  const impoconsumo = isNaN(parseFloat($("#in-impoconsumo").val())) ? 0 : parseFloat($("#in-impoconsumo").val());
  const ibua = isNaN(parseFloat($("#in-ibua").val())) ? 0 : parseFloat($("#in-ibua").val());
  const icui = isNaN(parseFloat($("#in-icui").val())) ? 0 : parseFloat($("#in-icui").val());
  const cantidad = isNaN(parseFloat($("#in-cantidad").val())) ? 0 : parseFloat($("#in-cantidad").val());
  // Validar si el costo unitario es un número válido
  if (isNaN(costo_unitario) || costo_unitario <= 0) {
    // Si el costo no es válido, poner el valor en 0 y actualizar costo_neto
    $("#in-costo").val(0);
    $("#in-costo_neto").val(0);
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
  $("#in-costo_neto").val(formatNum(costo_neto, '$', ConfGeneral));
  $("#costo_total").val(formatNum((costo_neto * cantidad), '$', ConfGeneral))
};

const getRetencionesFuente = async (id_tipo_documento) => {
  try {
    const resp = await serviceRetencionesFuente(id_tipo_documento);
    console.log({ resp })
    if (resp.data.length > 0) {
      let option = '<option value="">Seleccione</option>';
      resp.data.forEach(item => {
        option += `<option value="${item.id}">${item.concepto_descripcion}</option>`;
      });
      $("#concepto_retencion").html(option);
    }

  } catch (error) {
    console.error("Error al obtener retenciones en la fuente:", error);
  }
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
    console.log({ perfiles })

    const perfilSeleccionado = perfiles.find((p) => parseInt(p.bodega, 10) === bodega) || perfiles[0];

    perfilTributarioRetenciones = {
      ret_fte: (perfilSeleccionado.ret_fte || 'N'),
      ret_cree: (perfilSeleccionado.ret_cree || 'N'),
      ret_ica: (perfilSeleccionado.ret_ica || 'N'),
      ret_iva: (perfilSeleccionado.ret_iva || 'N')
    };
    console.log({
      perfilTributarioRetenciones,
      perfilSeleccionado,
      idPerfilTributario,
      html: `<option value="${perfilSeleccionado.id}" selected>${perfilSeleccionado.tipo} - ${perfilSeleccionado.descripcion}</option>`
    })
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


const aplicarRetencionesPorPerfil = () => {
  const idConcepto = parseInt($("#concepto_retencion").val() || '0', 10);
  const concepto = conceptosRetencion.find((c) => parseInt(c.id, 10) === idConcepto);

  if (!concepto) {
    // pintarRetencionesTotales(0, 0, 0);
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


const formatearMonedaLocal = (valor) => {
  const n = isNaN(parseFloat(valor)) ? 0 : parseFloat(valor);
  if (typeof ConfGeneral !== 'undefined' && ConfGeneral) {
    return formatNum(n, '$', ConfGeneral);
  }
  return n.toFixed(2);
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

$(async function () {


  permisos = await permisosUsuario(id_modulo)
  const { general, modulos } = await cargarConfiguraciones();
  ConfGeneral = general;
  ConfModulos = modulos[id_modulo];
  console.log({ ConfGeneral, ConfModulos })


  const allowDecimals = ConfGeneral.NumeroDecimales > 0;

  new Cleave('.format-num', {
    numeral: true,
    numeralThousandsGroupStyle: 'thousand',
    numeralDecimalMark: allowDecimals ? '.' : '',
    numeralDecimalScale: allowDecimals ? ConfGeneral.NumeroDecimales : 0,
  });

  await nuevoPedido();
  getRetencionesFuente($("#tipo").val());
  $('#buscar_ter').autocomplete({
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
    select: function (event, ui) {
      console.log(ui);

      item = ui.item;

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
      // Habilitar la pestaña Creación 
      $('[data-bs-target="#navs-productos"]').removeClass('disabled').removeAttr('disabled');
      $("#id_tercero").val(item.id);
      $("#email_fe").val(item.email_fe);
      $("#nit").val(item.nit);
      $("#tratamiento").val(item.tratamiento);
      $("#razon_comercial").val(item.razon_comercial);
      $("#condicion").val(item.id_condicion)
      getDirecciones(item.id);
      $("#buscar_ref").attr('readonly', false).focus();

    }
  });
  const hoy = new Date();
  const fechaFormateada = hoy.toISOString().split('T')[0];
  $("#fhIni,#fhFin").val(fechaFormateada);

  $("#btnBuscarOrden").on('click', () => {
    buscarPedidos();
  });
  /*
   $('#buscar_ref').autocomplete({
     source: function (request, response) {
       const opciones = obtenerOpcionesAutocomplete(request.term);
       response(opciones);
     },
     minLength: 3,
     select: function (event, ui) {
       const item = ui.item.producto;
       let cantidad = item.datos.cantidad / item.datos.can_und;
       let pcj_iva = item.datos.pcj_iva;
       let pcj_dto = item.datos.pcj_descuento;
       let costo = (item.datos.costo_unitario * item.datos.can_und).toFixed(2);
       let costo_con_descuento = (item.datos.costo_unitario * item.datos.can_und) * (1 - (pcj_dto / 100));
       let costo_neto = (costo_con_descuento * ((pcj_iva / 100) + 1)).toFixed(2);
       let costo_total = (costo_neto * cantidad).toFixed(2);
       let undOpt = ``;
 
       unidades = [
         [item.datos.und_1, item.datos.cant_1, 1],
         [item.datos.und_2, item.datos.cant_2, 2],
         [item.datos.und_3, item.datos.cant_3, 3],
         [item.datos.und_4, item.datos.cant_4, 4]
       ]
       unidades.sort(function (a, b) {
         return b[2] - a[2];
       });
       unidades.forEach(element => {
         let selected = ''
         if (element[0] == item.datos.und) {
           selected = 'selected'
         }
         if (element[1] > 0) {
           undOpt += `<option ${selected} value="${element[1]}">${element[0]}-${element[1]}</option>`;
         }
 
       });
       $("#in-unidad").html(undOpt)
       if (item.datos.maneja_lote.trim() == 'S') {
         $("#row_lotes").show();
       } else {
         $("#row_lotes").hide();
       }
       let pendientes = cantidad - (parseInt(item.datos.ingresadas) + parseInt(item.datos.temporales));
 
       $("#in-pendientes").val(pendientes);
       $("#in-id-referencia").val(item.datos.id_referencia);
       $("#in-costo-neto").val(costo_neto);
       $("#in-costo").val(costo);
       $("#in-iva").val(pcj_iva);
       $("#in-dto").val(pcj_dto);
       $("#in-stock").val(item.datos.total_stock);
       $("#in-codigo").val(item.datos.codigo);
       $("#in-cantidad-orden").val(cantidad);
       $("#in-descripcion").val(item.datos.descripcion);
       $("#in-descripcion-larga").val(item.datos.descripcion_larga);
       $("#in-maneja-lote").val(item.datos.maneja_lote);
       if (item.datos.maneja_lote == 'N') {
         $("#lote").removeAttr('required').prop('disabled', true).val('0');
         $("#vmcto").removeAttr('required').prop('disabled', true).val('1900-01-01');
       } else {
         $("#lote").attr('required', 'required').prop('disabled', false).val('');
         $("#vmcto").attr('required', 'required').prop('disabled', false).val('');
       }
       $("#cantidad").val('');
       $("#modal-ingreso").modal('show');
 
       const elements = document.querySelectorAll('.format-num');
       elements.forEach((element) => {
         new Cleave(element, {
           numeral: true,
           numeralThousandsGroupStyle: 'thousand',
           numeralDecimalMark: '.',
           numeralDecimalScale: 2,
         });
       });
       $("#in-cantidad").focus();
     }
   });
 */
  const modalBusquedaRef = new ModalBusquedaProducto((rowData) => {
    console.log({ rowData })
    const ref = {
      datos: {
        can_und: rowData.can_und,
        cant_1: rowData.cant_1,
        cant_2: rowData.cant_2,
        cant_3: rowData.cant_3,
        cant_4: rowData.cant_4,
        cantidad: 0,
        cantidad2: 0,
        cantidad_desp: 0,
        codigo: rowData.codigo,
        costo_unitario: rowData.costo_unitario,
        descripcion: rowData.descripcion,
        descripcion_larga: rowData.descripcion_larga,
        ibua: rowData.ibua,
        icui: rowData.icui,
        id: rowData.id,
        id_bodega: rowData.id_bodega,
        id_pedido: 0,
        id_referencia: rowData.id,
        impoconsumo: rowData.impoconsumo,
        ingresadas: 0,
        maneja_lote: rowData.maneja_lote,
        marca: rowData.marca,
        n_lotes_pedidos: 0,
        pcj_descuento: 0,
        pcj_iva: 0,
        promedio: rowData.promedio,
        sw: 0,
        temporales: 0,
        total_stock: rowData.total_stock,
        ultimo_costo: rowData.ultimo_costo,
        und: '',
        und_1: rowData.und_1,
        und_2: rowData.und_2,
        und_3: rowData.und_3,
        und_4: rowData.und_4,
        valor_unitario: rowData.costo_unitario,
        und_compra: rowData.und_compra
      },
      ean: [],
      posiciones: []
    }
    existe = arrReferenciasOC.some(item => item.datos.id_referencia == ref.datos.id_referencia);
    if (existe) {
      Swal.fire("Error", "La referencia ya fue agregada", "error");
      return;
    }
    cargarReferencia_(ref);

  }, {
    check_stock: false
  });


  $("#buscar_ref").on('keyup', async function (e) {
    const filtro = $.trim($(this).val())
    if (filtro.length == 0) {
      return;
    }
    if (e.key == 'Enter') {
      const resp = await modalBusquedaRef.getRecords({
        data: filtro,
        cod_barras: 0
      });
      const nregistros = modalBusquedaRef.getNrecords()

      if (nregistros > 0) {

        if (nregistros == 1) {

          const opciones = obtenerOpcionesAutocomplete(resp[0].codigo);


          const item = opciones[0].producto;
          console.error({
            item
          })
          return;

          let cantidad = item.datos.cantidad / item.datos.can_und;
          let pcj_iva = item.datos.pcj_iva;
          let pcj_dto = item.datos.pcj_descuento;
          let costo = (item.datos.costo_unitario * item.datos.can_und).toFixed(2);
          let costo_con_descuento = (item.datos.costo_unitario * item.datos.can_und) * (1 - (pcj_dto / 100));
          let costo_neto = (costo_con_descuento * ((pcj_iva / 100) + 1)).toFixed(2);
          let costo_total = (costo_neto * cantidad).toFixed(2);
          let undOpt = ``;

          unidades = [
            [item.datos.und_1, item.datos.cant_1, 1],
            [item.datos.und_2, item.datos.cant_2, 2],
            [item.datos.und_3, item.datos.cant_3, 3],
            [item.datos.und_4, item.datos.cant_4, 4]
          ]
          unidades.sort(function (a, b) {
            return b[2] - a[2];
          });
          unidades.forEach(element => {
            let selected = ''
            if (element[0] == item.datos.und) {
              selected = 'selected'
            }
            if (element[1] > 0) {
              undOpt += `<option ${selected} value="${element[1]}">${element[0]}-${element[1]}</option>`;
            }

          });
          $("#in-unidad").html(undOpt)
          if (item.datos.maneja_lote.trim() == 'S') {
            $("#row_lotes").show();
          } else {
            $("#row_lotes").hide();
          }
          let pendientes = cantidad - (parseInt(item.datos.ingresadas) + parseInt(item.datos.temporales));

          $("#in-pendientes").val(pendientes);
          $("#id_referencia").val(item.datos.id_referencia);
          $("#in-costo_neto").val(costo_neto);
          $("#costo_unitario").val(item.datos.costo_unitario);
          $("#in-costo").val(costo);
          $("#in-iva").val(pcj_iva);
          $("#in-dto").val(pcj_dto);
          $("#in-stock").val(item.datos.total_stock);
          $("#in-codigo").val(item.datos.codigo);
          $("#in-cantidad-orden").val(cantidad);
          $("#in-descripcion").val(item.datos.descripcion);
          $("#in-descripcion-larga").val(item.datos.descripcion_larga);
          $("#in-maneja-lote").val(item.datos.maneja_lote);

          $("#in-impoconsumo").val(item.datos.impoconsumo)
          $("#in-icui").val(item.datos.icui)
          $("#in-ibua").val(item.datos.ibua)
          $("#promedio").val(formatNum(item.datos.promedio, '$'));

          $("#ultimo_costo").val(formatNum(item.datos.ultimo_costo, '$'))
          if (item.datos.maneja_lote == 'N') {
            $("#lote").removeAttr('required').prop('disabled', true).val('0');
            $("#vmcto").removeAttr('required').prop('disabled', true).val('1900-01-01');
          } else {
            $("#lote").attr('required', 'required').prop('disabled', false).val('');
            $("#vmcto").attr('required', 'required').prop('disabled', false).val('');
          }
          $("#cantidad").val('');
          $("#modalAdd").modal('show');

          /*const elements = document.querySelectorAll('.format-num');
          elements.forEach((element) => {
            new Cleave(element, {
              numeral: true,
              numeralThousandsGroupStyle: 'thousand',
              numeralDecimalMark: '.',
              numeralDecimalScale: 2,
            });
          });*/
          $("#in-cantidad").focus();
          $("#modalAdd").modal('show');
        } else {
          modalBusquedaRef.show();
        }
      }
    }
  });

  $("#btnBusquedaAvanzada").click(function () {
    modalBusquedaRef.show();
    $("#busqueda-descripcion").val($("#filtro").val());
    setTimeout(() => {
      $("#busqueda-descripcion").focus();
    }, 200);
  });

  $("#referencia").on('focusout', function () {
    const ref = $.trim($(this).val());

    if ($("#id_transaccion").val().length > 0) {
      enviarPeticion({
        link: '../models/IngresosMercancias.php',
        op: 'update-ref',
        id_transaccion: $.trim($("#id_transaccion").val()),
        referencia: ref
      }).then(resp => {
        console.log({ resp })
        if (!resp.ok) {
          Swal.fire('Error', resp.message, 'error');
        }
      }).catch(e => {
        console.error(e);
        Swal.fire('Error', 'Error al actualizar referencia: ' + e.message, 'error');
      });
    }

  });

  $("#in-lote").on('focusout', function () {
    const lote = $.trim($(this).val());
    const id_referencia = $("#id_referencia").val();
    // valido si este lote existe pra esta referencia en array arrayReferenciasOC
    const referencia = arrReferenciasOC.find(ref => ref.datos.id_referencia == id_referencia);
    console.log({ referencia, id_referencia, lote })
    /*
    if (referencia) {
      const loteExiste = referencia.posiciones.some(pos => pos.lote == lote); 
      if (!loteExiste) {
        Swal.fire('Error', 'El lote ingresado no existe para esta referencia', 'error');
        $(this).val('');
      } 
    }*/
  });

  // Búsqueda en tabla items_ por código, descripción y EAN (se dispara con Enter)
  // Mostrar resultados en modal
  $("#buscar_ref_oc").on('keydown', function (e) {
    if (e.key !== 'Enter') return;

    e.preventDefault();
    const query = $.trim($(this).val()).toLowerCase();

    if (query.length === 0) {
      Swal.fire('Advertencia', 'Ingresa un término para buscar', 'warning');
      return;
    }

    // Array para guardar resultados
    const resultados = [];

    $("#items_ tbody tr[data-element]").each(function () {
      try {
        const elementStr = $(this).attr('data-element');
        const elementData = JSON.parse(elementStr);

        if (!elementData) return;

        // Buscar en la estructura completa del item
        let item;
        if (elementData.datos) {
          item = elementData;
        } else {
          item = { datos: elementData, ean: elementData.ean || [] };
        }

        const codigo = (item.datos?.codigo || '').toLowerCase();
        const descripcion = (item.datos?.descripcion || '').toLowerCase();
        const eanArray = item.ean || [];

        // Buscar en código, descripción o EAN
        const coincide =
          codigo.includes(query) ||
          descripcion.includes(query) ||
          eanArray.some(e => e.toLowerCase().includes(query));

        if (coincide) {
          resultados.push(item);
        }
      } catch (e) {
        console.error('Error buscando:', e);
      }
    });

    // Mostrar resultados en modal
    if (resultados.length === 0) {
      Swal.fire('Sin resultados', 'No se encontraron productos que coincidan con tu búsqueda', 'info');
      return;
    }

    mostrarModalResultados(resultados);
  });

  // Función para mostrar modal con resultados
  const mostrarModalResultados = (resultados) => {
    let html = `
      <div class="modal fade" id="modalResultadosBusqueda" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Resultados de búsqueda</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Código</th>
                      <th>Descripción</th>
                      <th>EAN</th>
                    </tr>
                  </thead>
                  <tbody>
    `;
    if (resultados.length == 1) {
      const opciones = obtenerOpcionesAutocomplete(resultados[0].datos.codigo);
      cargarReferencia_(opciones[0].producto);
      return;
    }
    resultados.forEach((item, index) => {
      const codigo = item.datos?.codigo || 'N/A';
      const descripcion = item.datos?.descripcion || 'N/A';
      const ean = (item.ean && item.ean.length > 0) ? item.ean.join(', ') : 'N/A';

      html += `
        <tr class="fila-resultado" data-index="${index}" style="cursor: pointer;">
          <td><strong>${codigo}</strong></td>
          <td>${descripcion}</td>
          <td><small>${ean}</small></td>
        </tr>
      `;
    });

    html += `
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;

    // Remover modal anterior si existe
    $("#modalResultadosBusqueda").remove();

    // Agregar modal al body
    $("body").append(html);

    // Mostrar el modal
    const modal = new bootstrap.Modal(document.getElementById('modalResultadosBusqueda'));
    modal.show();

    // Handler para seleccionar producto al hacer click en la fila
    $(".fila-resultado").off('click').on('click', function () {
      const index = $(this).data('index');
      const elemento = resultados[index];

      if (elemento && elemento.datos?.codigo) {
        try {
          // Cerrar modal
          modal.hide();

          // Limpiar búsqueda
          $("#buscar_ref_oc").val('');

          // Llamar a obtenerOpcionesAutocomplete con el código
          const opciones = obtenerOpcionesAutocomplete(elemento.datos.codigo);

          if (opciones) {
            // Cargar la referencia
            cargarReferencia_(opciones[0].producto);
          } else {
            Swal.fire('Error', 'No se pudieron cargar las opciones del producto', 'error');
          }
        } catch (e) {
          console.error('Error al cargar referencia:', e);
          Swal.fire('Error', 'Error al cargar el producto: ' + e.message, 'error');
        }
      }
    });
  };

  // Click en fila de tabla para cargar el producto (mantener para compatibilidad)
  $(document).on('click', '#items_ tbody tr[data-element]', function () {
    try {
      const elementStr = $(this).attr('data-element');
      const elemento = JSON.parse(elementStr);

      if (elemento && elemento.codigo) {
        // Limpiar búsqueda
        $("#buscar_ref_oc").val('');

        // Llamar a obtenerOpcionesAutocomplete con el código
        const opciones = obtenerOpcionesAutocomplete(elemento.codigo);
        console.log({ opciones, elementoSeleccionado: elemento });

        if (opciones && opciones.length > 0) {
          // Cargar la referencia
          cargarReferencia_(opciones[0].producto);
        }
      }
    } catch (e) {
      console.error('Error al cargar referencia:', e);
    }
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

  $(".calculo-costo").on('input', function () {
    actualizarCostoNeto();
  })

  // Validar fecha de vencimiento
  const validarFechaVencimiento = () => {
    const fechaVencimiento = $("#in-vmcto").val();
    const manejaLote = $("#in-maneja-lote").val();

    // Si no maneja lote, no validar fecha (debe tener valores por defecto)
    if (manejaLote !== 'S') {
      $("#error-vmcto").remove();
      return true;
    }

    if (!fechaVencimiento) {
      return true; // La validación HTML required lo valida
    }

    // Obtener meses mínimos de la configuración
    const mesesMinimos = ConfModulos.MesesMinimoIngreso || 0;

    // Calcular fecha mínima (hoy + meses mínimos)
    const hoy = new Date();
    const fechaMinima = new Date(hoy);
    fechaMinima.setMonth(fechaMinima.getMonth() + parseInt(mesesMinimos));

    // Convertir a YYYY-MM-DD para comparar
    const fechaMinimaFormato = fechaMinima.toISOString().split('T')[0];

    // Comparar fechas
    if (fechaVencimiento < fechaMinimaFormato) {
      // Mostrar error
      const errorHtml = `
        <div id="error-vmcto" class="text-danger small mt-1">
          <i class="fa-solid fa-circle-exclamation"></i>
          La fecha de vencimiento debe ser mínimo ${mesesMinimos} mes(es) a partir de hoy 
          (mínimo: <strong>${fechaMinimaFormato}</strong>)
        </div>
      `;

      // Remover error anterior si existe
      $("#error-vmcto").remove();

      // Agregar error debajo del input
      $("#in-vmcto").after(errorHtml);
      $("#in-vmcto").addClass("is-invalid");

      return false;
    } else {
      // Limpiar error
      $("#error-vmcto").remove();
      $("#in-vmcto").removeClass("is-invalid");
      return true;
    }
  };

  // Validar cuando cambia la fecha
  $("#in-vmcto").on('change', function () {
    validarFechaVencimiento();
  });

  var formDetalle = document.getElementById('FormDetalle');

  formDetalle.addEventListener('submit', async function (event) {
    try {
      event.preventDefault();
      // 
      if ($("#referencia").val().length == 0) {
        Swal.fire('Atención', 'Debe digitar el documento de referencia para cargar la oc', 'warning');
        event.stopPropagation();
        return;
      }

      //valido conepto de retencion si aplica

      let concepto = $("#concepto_retencion").val();

      if ($("#concepto_retencion option").length > 1 && concepto === "") {
        Swal.fire("Error", "Debe seleccionar un concepto de retención", "error");
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
        valor_total: unformatNum($("#in-total").val()),
        id_condicion: $("#condicion").val(),
        id_pedido: $("#id_pedido").val(),
        sw: 1, //compras
        id_tercero_dir: $("#direcciones").val(),
        documento: $("#referencia").val(),
        id_concepto_retencion: concepto,
      };

      let cant_orden = parseInt($("#in-cantidad-orden").val());
      let cant_ingreso = parseInt($("#in-cantidad").val());
      let pendientes = parseInt($("#in-pendientes").val());

      // Validar cantidad según configuración
      if (cant_ingreso > pendientes) {
        // Verificar si está permitido ingresar mayor cantidad que la pedida ConfGeneral
        if ($.trim(ConfModulos.CantidadMayorPedida) !== 'S') {
          Swal.fire("Error", "La cantidad a ingresar no puede ser mayor a la de la orden de compra", "error");
          return;
        }
      }

      // Validar fecha de vencimiento
      if (!validarFechaVencimiento()) {
        Swal.fire("Error", "La fecha de vencimiento no cumple con los requisitos mínimos", "error");
        return;
      }


      showLoadingWithTetrisLogo('Guardando', 'ISA Software')
      if (isNaN(parseInt(id_transaccion)) || parseInt(id_transaccion) == 0) {
        let resp = await enviarPeticion(dataEncabezado);

        if (resp.error) {
          dissminSwal();
          Swal.fire("Error", resp.message, "error");
          return;
        }
        $("#id_transaccion").val(resp.data.id)
        $("#numero").val(resp.data.numero)
      }

      let dataDetalle = formatearArrayRequest($(this).serializeArray());

      dataDetalle.link = '../shared/servicios/servicios.php';
      if ($("#id_detalle").val() > 0) {
        dataDetalle.op = 'update_transacciones_detalle_tmp';
        dataDetalle.id = $("#id_detalle").val()
      } else {
        dataDetalle.op = 'add_transacciones_detalle_tmp';
      }
      dataDetalle.id_transaccion = $("#id_transaccion").val();
      dataDetalle.und = $("#in-unidad option:selected").text().split("-")[0];
      dataDetalle.can_und = $("#in-unidad option:selected").text().split("-")[1];
      dataDetalle.valor_unitario = parseFloat(($("#in-costo").val().replace(/,/g, ''))) / dataDetalle.can_und;
      dataDetalle.costo_unitario = parseFloat(($("#in-costo").val()).replace(/,/g, '')) / dataDetalle.can_und;
      dataDetalle.cantidad = cant_ingreso * dataDetalle.can_und;
      dataDetalle.id_bodega = $("#bodegas").val();
      dataDetalle.id_pedido = $("#id_pedido").val();
      dataDetalle.impoconsumo = $("#in-impoconsumo").val();
      dataDetalle.icui = $("#in-icui").val();
      dataDetalle.ibua = $("#in-ibua").val();
      dataDetalle.sw = 1; //compras
      if ($("#in-maneja-lote").val() == 'N') {
        delete dataDetalle.lote;
        delete dataDetalle.vmcto;
      }

      let respDet = await enviarPeticion(dataDetalle);

      if (respDet.error) {
        Swal.fire("Error", respDet.message, "error");
        return;
      }
      id_ = $("#id_detalle").val() > 0 ? $("#id_detalle").val() : respDet.data.id
      let id_ref = dataDetalle.id_referencia;
      let cantidad = dataDetalle.cantidad;
      let lote = dataDetalle.lote;
      let vmcto = dataDetalle.vmcto;
      await addReferencia(id_ref, cant_ingreso, lote, vmcto, id_);
      dissminSwal();
      $("#modalAdd").modal('hide');
      $("#buscar_ref").val('');
      $("#buscar_ref").focus();


    } catch (e) {
      dissminSwal();
      Swal.fire('Ocurrio un error', e, 'error');
      console.log(e);
    }
  });

  $("#btnGuardar").click(function () {
    if ($("#id_transaccion").val() == 0) {
      Swal.fire('Atención', 'Debe agregar al menos un producto para guardar el ingreso', 'warning');
      return;
    }
    if ($("#referencia").val().length == 0) {
      Swal.fire('Atención', 'Debe ingresar una referencia para guardar el ingreso', 'warning');
      return;
    }
    // Mostrar el modal de productos pendientes antes de confirmar guardado
    mostrarModalProductosPendientes();
  });

  // Evento para el botón de confirmar en el modal de pendientes
  $("#btn-confirmar-pendientes").off('click').on('click', function () {
    $("#modal-productos-pendientes").modal('hide');
    confirmarGuardado();
  });

  $("#btnLimpiar,#btnLimpiar2").click(function () {
    nuevoPedido();
  })

  // Limpiar formulario cuando se cierre modalAdd
  $("#modalAdd").on('hidden.bs.modal', function () {
    limpiarReferencia();
    $("#FormDetalle").removeClass("was-validated");
  });

});
