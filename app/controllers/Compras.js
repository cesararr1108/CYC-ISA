let arrayReferencias = [];
const id_modulo = 20
let soloStock = false;
let permisos;
let busquedaEnProgreso = false;
let estadoFiltroActual = 'T';
let arrayRef = [];

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

const limpiar = () => {
  $("#buscar_ter").val('').focus();
  $("#id_tercero").val('');
  $("#email_fe").val('');
  $("#notas").val('');
  $("#nit").val('');
  $("#tratamiento").val('');
  $("#razon_comercial").val('');
  $("#direcciones").html('');
  $("#buscar_ter").focus();
  $("#id_pedido").val(0);
  $("#n_total").val(formatNum(0, '$'));
  $("#items_ tbody").html('');
  limpiarReferencia();
  condicionesPago('P', 'id_condicion');

  // Mostrar pestaña de Terceros
  $('#tabs button[data-bs-target="#navs-tercero"]').tab("show");

  // Bloquear pestaña de Creación
  $('#tabs button[data-bs-target="#navs-productos"]').addClass('disabled').attr('disabled', true);

  // Habilitar pestaña de Terceros
  $('#tabs button[data-bs-target="#navs-tercero"]').removeClass('disabled').removeAttr('disabled');
};

const cargarReferencia = async (ob) => {
  limpiarReferencia()
  $("#id_referencia").val(ob.id);
  $("#descripcion_corta").val(ob.descripcion);
  $("#descripcion_larga").val(ob.descripcion_larga);
  $("#codigo").val(ob.codigo);
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
  $("#pcj_descuento").val(0.00);
  $("#modalAdd").modal('show');
  let iva = $("#iva").val();
  let costo_neto = parseFloat(costo_unitario) * (1 + (parseFloat(iva) / 100))
  $("#costo_neto").val(costo_neto);
  $("#ultimo_costo").val(formatNum(ob.ultimo_costo_compra, '$'))

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

  setTimeout(function () {
    $("#costo").focus().select();
  }, 500);
  $("#stock").val(ob.total_stock + " unidades");

  const elements = document.querySelectorAll('.format-num');
  elements.forEach((element) => {
    new Cleave(element, {
      numeral: true,
      numeralThousandsGroupStyle: 'thousand',
      numeralDecimalMark: '.',
      numeralDecimalScale: 2,
    });
  });


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

const addReferencia = async (id_det) => {

  arrayReferencias = [];

  const descripcion = $("#descripcion_corta").val();
  const codigo = $("#codigo").val();
  const costo = $.trim($("#costo").val()).replace(/,/g, '');
  const costo_unitario = $("#costo_unitario").val();
  const iva = $("#iva").val();
  const dto = $("#pcj_descuento").val();
  const cantidad = $("#cantidad").val();
  const can_und = $.trim($.trim($("#unidad option:selected").text()).split("-")[1])
  const unidad = $.trim($.trim($("#unidad option:selected").text()).split("-")[0])
  const costo_neto = unformatNum($("#costo_neto").val().replace(/,/g, ''));
  const costo_total = (costo_neto * parseInt(cantidad)).toFixed(2);


  if (codigo.length == 0) {
    return;
  }
  arrayReferencias.push({
    codigo,
    descripcion,
    costo,
    costo_neto,
    iva,
    dto,
    cantidad,
    unidad,
    can_und,
    costo_total,
    id_det
  });

  if ($("#id_detalle").val() > 0) {
    editReferencia();
  } else {
    generarTabla();
  }
  limpiarReferencia();
  $("#modalAdd").modal('hide');
};

const limpiarReferencia = () => {
  $("#costo_unitario").val("0");
  $("#descripcion_corta").val('');
  $("#descripcion_larga").val('');
  $("#codigo").val('');
  $("#unidad").html('');
  $("#iva").val(0);
  $("#costo_neto").val(0);
  $("#cantidad").val(0);
  $("#buscar_ref").val('');
  $("#id_pedido_det").val(0)
  $("#id_referencia").val(0)
  $("#costo").val(0);
  $("#pcj_descuento").val(0);
  $("#FormDetalle").removeClass("was-validated");
  $("#id_detalle").val(0);
  arrayReferencias = [];
};

const generarTabla = () => {
  let tabla = ``;
  arrayReferencias.forEach(item => {
    tabla += `
      <tr class="det_${item.id_det}">
        <td>${item.codigo}</td>
        <td>${item.descripcion}</td>
        <td>${formatNum(item.costo, '$')}</td>
        <td>${formatNum(item.costo_neto, '$')}</td>
        <td>${item.iva}</td>
        <td>${item.dto}</td>
        <td>${item.cantidad}</td>
        <td>${item.unidad}</td>
        <td>${item.can_und}</td>
        <td>${formatNum(item.costo_total, '$')}</td>
        <td align="center"><button class="btn btn-sm btn-outline-danger btnEliminar" onclick="eliminarReferencia(this,${item.id_det})"><i class="fa-regular fa-trash-can"></i></button></td>
        <td align="center"><button class="btn btn-sm btn-outline-primary btnEditar" onclick="editarReferencia(${item.id_det})"><i class="fa-regular fa-pen-to-square "></i></td>
      </tr>
    `;
  });
  $("#items_ tbody").append(tabla);
};

const editarReferencia = async (id) => {

  try {
    limpiarReferencia();
    $("#modalAdd").modal('show');

    resp = await enviarPeticion({
      link: '../shared/servicios/servicios.php',
      op: 'detalle_pedido',
      id
    });

    item = resp[0];
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
    $("#id_referencia").val(item.id);
    $("#descripcion_corta").val(item.descripcion);
    $("#descripcion_larga").val(item.descripcion_larga);
    $("#codigo").val(item.codigo);
    $("#iva").val(item.iva);
    $("#pcj_descuento").val(item.pcj_descuento);
    let iva = $("#iva").val();
    let descuento = $("#pcj_descuento").val();
    let costo_con_descuento = costo_unitario * (1 - (descuento / 100));
    let costo_neto = costo_con_descuento * (1 + iva / 100);

    $("#costo_neto").val(costo_neto);
    $("#ultimo_costo").val(formatNum(item.ultimo_costo_compra, '$', ConfGeneral))
    $("#cantidad").val(item.cantidad / item.can_und);
    $("#id_detalle").val(item.id_detalle);
    $("#stock").val(item.total_stock + " unidades");
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

    const elements = document.querySelectorAll('.format-num');
    elements.forEach((element) => {
      new Cleave(element, {
        numeral: true,
        numeralThousandsGroupStyle: 'thousand',
        numeralDecimalMark: '.',
        numeralDecimalScale: 2,
      });
    });


  } catch (e) {
    console.error(e)
  }

};

const eliminarReferencia = (ob, id) => {
  const id_pedido = $("#id_pedido").val();
  Swal.fire({
    title: "Esta seguro de eliminar el producto?",
    text: "Esta acción no puede reversarse!",
    icon: "question",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Si",
    cancelButtonText: "No"
  }).then(async (result) => {
    if (result.isConfirmed) {
      resp = await enviarPeticion({
        link: '../shared/servicios/servicios.php',
        op: 'delete_item_pedido',
        id,
        id_pedido
      });
      if (resp.total_reg == 0) {
        $("#id_pedido").val(0);
        $("#n_total").val(formatNum(0, '$'))
      } else {
        recalcularEncabezado();
      }
      fila = $(ob).parent().parent();
      fila.remove();
    }
  });

};

const editReferencia = (id) => {
  item = arrayReferencias[0];
  fila = $(".det_" + item.id_det);
  fila.find("td").eq(2).html(formatNum(item.costo, '$')) //costo 
  fila.find("td").eq(3).html(formatNum(item.costo_neto, '$')) //costo  neto 
  fila.find("td").eq(5).html(item.dto) //fecha vencimiento 
  fila.find("td").eq(6).html(item.cantidad) //cantidad 
  fila.find("td").eq(7).html(item.unidad) //und
  fila.find("td").eq(8).html(item.can_und) //can und
  fila.find("td").eq(9).html(formatNum(item.costo_total, '$')) //costo total
};

const recalcularEncabezado = async () => {
  try {
    let resp = await enviarPeticion({
      op: 'update_encabezado_pedido',
      link: '../shared/servicios/servicios.php',
      id_pedido: $("#id_pedido").val()
    });
    console.log({
      resp
    })
    if (!resp.error) {
      $("#n_total").val(formatNum(resp.valor_total, '$'));
    }
  } catch (error) {
    console.log({ error })
  }

};

const buscarTemporales = async () => {
  try {
    const fecha_inicial = $.trim($("#fecha_inicial").val());
    const fecha_final = $.trim($("#fecha_final").val());
    showLoadingWithTetrisLogo('Buscando información', 'ISA Software')
    const resp = await enviarPeticion({
      op: 'busqueda_pedido',
      link: '../shared/servicios/servicios.php',
      fecha_inicial,
      fecha_final,
      sw: 16,
      estado: estadoFiltroActual
    });
    console.log({ resp })
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
                      <th>Estado</th>
                      <th>EDI</th>
                      <th>PDF</th>
                      <th>Editar</th>
                      <th>Eliminar</th>
                    </tr>
                  </thead>
                  <tbody>`;
    resp.data.forEach(element => {
      let estado = '';
      let disabled = ''
      let ediStatus = '';

      switch (element.estado) {
        case 'T':
          estado = `<button class="btn btn-outline-warning btn-sm"><i class="fa-regular fa-clock"></i></button>`;
          break;
        case 'P':
          estado = `<button class="btn btn-outline-success btn-sm"><i class="fa-solid fa-check-double"></i></button>`;
          break;
        case 'I':
          estado = `<button class="btn btn-outline-info btn-sm"><i class="fa-solid fa-bolt-lightning"></i></button>`;
          disabled = 'disabled'
          break;
      }

      if (element.edi === 'S') {
        if (element.estado_edi === 'ENVIADO') {
          ediStatus = `<button class="btn btn-outline-success btn-sm" title="EDI Enviado"><i class="fa-solid fa-satellite-dish"></i></button>`;
        } else if (element.estado_edi === 'ERROR') {
          ediStatus = `<button class="btn btn-outline-danger btn-sm" title="Error al enviar EDI - Clic para reenviar" onclick="reenviarEDI(${element.id_pedido},'${element.ean_edi}')"><i class="fa-solid fa-triangle-exclamation"></i></button>`;
        } else {
          ediStatus = `<button class="btn btn-outline-secondary btn-sm" title="Pendiente de envío EDI - Clic para enviar" onclick="reenviarEDI(${element.id_pedido},'${element.ean_edi}')"><i class="fa-regular fa-paper-plane"></i></button>`;
        }
      }

      tabla += `<tr>
                    <td>${element.bodega}</td>
                    <td>${element.usuario}</td>
                    <td>${element.fecha_hora}</td>
                    <td>${element.id_pedido}</td>
                    <td>${element.nit}</td>
                    <td>${element.nombres}</td>
                    <td>${formatNum(element.valor_total, '$')}</td>
                    <td align="center">${estado}</td>
                    <td align="center">${ediStatus}</td>
                    <td align="center"><button class="btn btn-outline-danger btn-sm" onclick="ImprimirDocumento(200,${element.id_pedido},'Impresión de orden de compra')"><i class="fa-regular fa-file-pdf"></i></button></td>
                    <td align="center"><button class="btn btn-outline-primary btn-sm btn-recuperar" data-element='${JSON.stringify(element)}' ${disabled}><i class="fa fa-pencil"></i></button></td>
                    <td align="center"><button class="btn btn-outline-danger btn-sm" onclick="eliminarPedido(${element.id_pedido})" ${disabled}><i class="fa-regular fa-trash-can"></i></button></td>
                </tr>`;
    });

    tabla += `</tbody></table>`;

    $("#temporales").html(tabla);
    $(".btn-recuperar").click(function () {
      const element = JSON.parse($(this).attr("data-element"));
      recuperarPedido(element);
      $('#tabs button[data-bs-target="#navs-productos"]').tab('show')
      $('[data-bs-target="#navs-productos"]').removeClass('disabled').removeAttr('disabled');
    });

  } catch (e) {
    console.error(e);
    dissminSwal();
    Swal.fire("Error", "Se generó un error interno", "error");
  }
};

const recuperarPedido = async (data) => {

  $("#id_tercero").val(data.id_tercero);
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
  $("#buscar_ter").val(data.nit + ' - ' + data.nombres);
  $("#email_fe").val(data.email_fe);
  $("#edi").val(data.edi);
  $("#ean_edi").val(data.ean_edi);
  $("#items_ tbody").html('');

  let item = await enviarPeticion({
    op: 'buscar_detalle_pedido',
    link: '../shared/servicios/servicios.php',
    id_pedido: data.id_pedido
  });

  if (item.length > 0) {
    item.forEach(row => {
      row = row.datos;
      let pcj_iva = row.pcj_iva;
      let pcj_dto = row.pcj_descuento;
      let costo = (row.costo_unitario * row.can_und).toFixed(2);
      let cantidad = row.cantidad / row.can_und;
      let costo_con_descuento = (row.costo_unitario * row.can_und) * (1 - (pcj_dto / 100));
      let costo_neto = (costo_con_descuento * ((pcj_iva / 100) + 1)).toFixed(2);
      let costo_total = (costo_neto * cantidad).toFixed(2);

      tr = ` <tr class="det_${row.id}">
                <td>${row.codigo}</td>
                <td>${row.descripcion}</td>
                <td>${formatNum(costo, '$')}</td>
                <td>${formatNum(costo_neto, '$')}</td>
                <td>${pcj_iva}</td>
                <td>${pcj_dto}</td>
                <td>${cantidad}</td>
                <td>${row.und}</td>
                <td>${row.can_und}</td>
                <td>${formatNum(costo_total, '$')}</td>
                <td align="center"><button class="btn btn-sm btn-outline-danger btnEliminar" onclick="eliminarReferencia(this,${row.id})"><i class="fa-regular fa-trash-can"></i></button></td>
                <td align="center"><button class="btn btn-sm btn-outline-primary btnEditar" onclick="editarReferencia(${row.id})"><i class="fa-regular fa-pen-to-square "></i></td>
              </tr> `;
      $("#items_ tbody").append(tr);
    });
  }

};

const eliminarPedido = (id_pedido) => {
  Swal.fire({
    title: "Esta seguro de eliminar el pedido?",
    text: "Esta acción no puede reversarse!",
    icon: "question",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Si",
    cancelButtonText: "No"
  }).then(async (result) => {
    if (result.isConfirmed) {
      enviarPeticion({
        op: 'delete_pedido',
        id_pedido,
        link: '../shared/servicios/servicios.php'
      }).then(item => {
        if (item.error) {
          Swal.fire("Info", "Se elimino correctamente el pedido", "error");
        }
        Swal.fire("Error", "Se elimino correctamente el pedido", "success");
        buscarTemporales();
      }).catch(error => {
        console.error({
          error
        })
      })
    }
  })
};

const actualizarCostoNeto = () => {
  let costo_unitario = parseFloat($.trim($("#costo").val()).replace(/,/g, ''));
  if (isNaN(costo_unitario) || costo_unitario <= 0) {
    $("#costo").val(0);
    $("#costo_neto").val(0);
    return;
  }
  $("#costo_unitario").val((costo_unitario / $("#unidad option:selected").val()).toFixed(2));
  let iva = $("#iva").val();
  iva = parseFloat(iva);
  if (isNaN(iva)) {
    $("#costo_neto").val(0);
    return;
  }
  let descuento = $("#pcj_descuento").val();
  descuento = parseFloat(descuento);
  if (isNaN(descuento) || descuento <= 0) {
    $("#pcj_descuento").val(0);
  }
  let costo_con_descuento = costo_unitario;
  if (descuento > 0) {
    costo_con_descuento = costo_unitario * (1 - descuento / 100);
  }
  let costo_neto = costo_con_descuento * (1 + iva / 100);
  $("#costo_neto").val(costo_neto.toFixed(2));
};

const updateNota = async (id) => {
  let notas = $("#notas").val();
  const resp = await enviarPeticion({
    link: '../shared/servicios/servicios.php',
    op: 'add_nota_pedido',
    notas: notas,
    id: id
  });

  if (resp.error) {
    Swal.fire("Error", "ha ocurrido un error al actualizar la nota, verifique e intente nuevamente.", "error");
  }
};

const guardar = async (id) => {
  Swal.fire({
    title: "Esta seguro de guardar el pedido?",
    text: "Esta acción no puede reversarse!",
    icon: "question",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Si",
    cancelButtonText: "No"
  }).then(async (result) => {
    if (result.isConfirmed) {
      const resp = await enviarPeticion({
        link: '../shared/servicios/servicios.php',
        op: 'upd_estado_pedido',
        id: id,
        estado: 'P'
      });
      await enviarEDI(id);
      ImprimirDocumento(200, id, 'Impresión de orden de compra');
      limpiar();
    }
  });


};

const enviarEDI = async (id) => {
  const edi = $("#edi").val();
  const ean = $("#ean_edi").val();

  if (edi === 'S') {
    try {
      const resp = await enviarPeticion({
        link: '../shared/servicios/servicios-edi.php',
        ean,
        id
      });

      if (resp?.error) {
        Swal.fire("Error", resp?.msj || resp?.api?.message || "No fue posible enviar EDI", "error");
        return false;
      }

      Swal.fire("EDI", resp?.api?.message || "Documento EDI enviado correctamente", "success");
      return true;

    } catch (e) {
      console.log(e);
      Swal.fire("Error", "Error interno al enviar EDI", "error");
      return false;

    }
  }

  return true;
};

const reenviarEDI = async (id, ean) => {
  try {
    showLoadingWithTetrisLogo('Enviando EDI...', 'ISA Software')
    const resp = await enviarPeticion({
      link: '../shared/servicios/servicios-edi.php',
      ean,
      id
    });
    await buscarTemporales();
    if (resp?.error) {
      Swal.fire("Error", resp?.msj || resp?.api?.message || "No fue posible enviar EDI", "error");
    } else {
      Swal.fire("EDI", resp?.api?.message || "Documento EDI enviado correctamente", "success");
    }

  } catch (e) {
    dissminSwal();
    console.log(e);
    Swal.fire("Error", "Error interno al enviar EDI", "error");
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

const descargarExcel = (url, nombre = `Archivo.xlsx`) => {
  var a = document.createElement('a');
  a.href = url;
  a.download = nombre;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
};

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
                                <th> bodega</th>
                                <th>Código</th>
                                <th>Descripción</th>
                                <th>Laboratorio</th>
                                <th>Und comp</th>
                                <th>Conv</th>
                                <th>Costo</th>
                                <th>Cantidad</th>
                                <th>nit proveedor</th>                                
                            </tr>
                        </thead>
                    </table>`);

    var tbody = $('<tbody></tbody>');
    jsonData.slice(1).forEach(async function (row) {

      infoRef = await enviarPeticion({
        link: '../models/SugeridoCompras.php',
        op: 'getInfRef',
        codigo: row[1]
      })

      console.warn({ infoRef })
      row[0] = row[0];
      row[1] = row[1];
      row[6] = row[2];
      row[2] = infoRef[0].producto;
      row[7] = row[3];
      row[3] = infoRef[0].laboratorio;
      row[8] = row[4];
      row[4] = infoRef[0].unidad;
      row[5] = infoRef[0].conversion;


      arrayRef.push({
        bodega: String(row[0] || ''), // Primera columna: bodega
        codigo: String(row[1] || ''), // Segunda columna: Código
        producto: String(row[2] || ''), // Tercera columna: Producto
        laboratorio: String(row[3] || ''), // Cuarta columna: Laboratorio
        unidad: String(row[4] || ''), // Quinta columna: Und comp
        conversion: String(row[5] || ''), // Sexta columna: Conv
        costo: parseFloat(row[6] || 0), // Séptima columna: Costo
        cantidad: parseInt(row[7] || 0), // Octava columna: Cantidad
        nit: String(row[8] || ''), // Quinta columna: nit
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
};

$(async function () {
  listarAlmacenes();
  limpiar();
  tiposDocumentos(16, $("#bodegas").val(), 'id_tipo_documento');


  permisos = await permisosUsuario(id_modulo)
  const {
    general,
    modulos
  } = await cargarConfiguraciones();
  ConfGeneral = general;
  ConfModulos = modulos[id_modulo];

  $("#btn-csv").click(function () {
    const id_tercero = $.trim($("#id_tercero").val())

    if (id_tercero == '' || id_tercero == '0') {
      Swal.fire("Oops", "Debe seleccionar un tercero", "error")
      return;
    }
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

  $("#unidad").on('change', function () {
    let costo_unitario = parseFloat($.trim($("#costo").val()).replace(/,/g, ''));
    if (isNaN(costo_unitario) || costo_unitario <= 0) {
      $("#costo").val(0);
      $("#costo_neto").val(0);
      return;
    }

    const costo = calcularPrecioUnidad(
      costo_unitario,
      1,
      parseFloat($("#pcj_descuento").val()),
      parseFloat($("#iva").val())
    );
    $("#costo_neto").val(formatNum(costo.precion, '$', ConfGeneral));
    const cantidad = parseFloat($("#cantidad").val());
    if (!isNaN(cantidad) && cantidad > 0) {
      const costoTotal = costo.precion * cantidad;
      $("#costo_total").val(formatNum(costoTotal, '$', ConfGeneral));
    } else {
      $("#costo_total").val(formatNum(costo.precion, '$', ConfGeneral));
    }
  })
  $("#costo,#pcj_descuento,#cantidad").keyup(function () {
    //actualizarCostoNeto();
    let costo_unitario = parseFloat($.trim($("#costo").val()).replace(/,/g, ''));
    const costoneto = calcularPrecioUnidad(
      costo_unitario,
      1,
      parseFloat($("#pcj_descuento").val()),
      parseFloat($("#iva").val())
    );
    $("#costo_neto").val(formatNum(costoneto.precion, '$', ConfGeneral));
    const cantidad = parseFloat($("#cantidad").val());
    if (!isNaN(cantidad) && cantidad > 0) {
      const costoTotal = costoneto.precion * cantidad;
      $("#costo_total").val(formatNum(costoTotal, '$', ConfGeneral));
    } else {
      $("#costo_total").val(formatNum(costoneto.precion, '$', ConfGeneral));
    }
  })
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
    select: async function (event, ui) {
      item = ui.item;


      if (ConfModulos?.SoloTercerosProveedores == 'S') {
        if (item.es_proveedor != 'S') {
          Swal.fire("Error", "El tercero no es un proveedor", "error");
          return;
        }
      }

      if (item.id_condicion_prov == 0) {
        Swal.fire("Ups", "El proveedor no tiene un condicion de pago asignada.", "error")
        return;
      }
      // Habilitar la pestaña Creación
      $('[data-bs-target="#navs-productos"]').removeClass('disabled').removeAttr('disabled');

      $("#id_tercero").val(item.id);

      let direccionesCargadas = await cargarDirecciones(item.id);
      if (!direccionesCargadas) {
        Swal.fire("Error", "No se pudieron cargar las direcciones del tercero", "error");
        return;
      }


      $("#id_condicion").val(item.id_condicion_pago_prov);
      $("#email_fe").val(item.email_fe);
      $("#nit").val(item.nit);
      $("#tratamiento").val(item.tratamiento);
      $("#razon_comercial").val(item.razon_comercial);
      getDirecciones(item.id);
      $("#buscar_ref").attr('readonly', false).focus();
      $("#edi").val(item.edi);
      $("#ean_edi").val(item.ean_edi);

    }
  });

  $("#btnLimpiar").click(() => {
    limpiar();
  });
  $("#btnGroupEstado button").click(function () {
    $("#btnGroupEstado button").removeClass('active');
    $(this).addClass('active');
    estadoFiltroActual = $(this).data('estado');
  });

  $("#btnBuscarTemporal").click(() => {
    buscarTemporales();
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
  const preferencias = await preferenciasUsuario(id_modulo);

  if (preferencias.SoloProductoSock == 'S') {
    soloStock = true;
  }

  const modalBusquedaRef = new ModalBusquedaProducto((rowData) => {
    cargarReferencia(rowData);
  }, { check_stock: soloStock, cod_barras: false });



  $("#buscar_ref").on('keyup', async function (e) {
    const filtro = $.trim($(this).val())

    if (filtro.length == 0) { return; }

    if (e.key == 'Enter') {
      // Evitar múltiples ejecuciones al presionar Enter varias veces
      if (busquedaEnProgreso) {
        return;
      }

      try {
        busquedaEnProgreso = true;
        $(this).attr('disabled', true);

        const resp = await modalBusquedaRef.getRecords({ data: filtro, cod_barras: false });
        const nregistros = modalBusquedaRef.getNrecords()

        if (nregistros > 0) {
          if (nregistros == 1) {
            cargarReferencia(resp[0]);
          } else {
            modalBusquedaRef.show();
          }
        }
      } catch (error) {
        console.error(error);
      } finally {
        busquedaEnProgreso = false;
        $(this).attr('disabled', false);
      }
    }
  });

  let formDetalle = document.getElementById('FormDetalle');
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

      let id_pedido = $("#id_pedido").val();
      let dataDetalle = formatearArrayRequest($(this).serializeArray());

      let dataEncabezado = {
        link: '../shared/servicios/servicios.php',
        op: 'add_pedidos',
        id_tercero: $("#id_tercero").val(),
        id_bodega: $("#bodegas").val(),
        notas: $("#notas").val(),
        valor_total: unformatNum($("#n_total").val()),
        id_tercero_dir: $("#direcciones").val(),
        estado: 'T',
        sw: 16,
        id_condicion: $("#id_condicion").val(),
        id_tipo_documento: $("#id_tipo_documento").val()
      };

      let costo_unitario = parseFloat(($("#costo").val()).replace(/,/g, ''));
      let unidad = $("#unidad option:selected").text().split("-")[0];
      let cantidad = dataDetalle.cantidad;
      if (isNaN((costo_unitario))) {
        // Swal.fire("Error", "El costo no es valido.", "error");
        return;
      }

      if (unidad.lenght == 0) {
        // Swal.fire("Error", "Debe seleccionar una unidad.", "error");
        return;
      }


      if (isNaN(parseFloat(cantidad)) || cantidad == 0) {
        // Swal.fire("Error", "La cantidad no es valida.", "error");
        return;
      }


      showLoadingWithTetrisLogo('Guardando pedido', 'ISA Software')
      if (isNaN(parseInt(id_pedido)) || parseInt(id_pedido) == 0) {
        let resp = await enviarPeticion(dataEncabezado);
        console.log({
          encabezado: resp
        })
        if (resp.error) {
          dissminSwal();
          Swal.fire("Error", resp.message, "error");
          return;
        }
        $("#id_pedido").val(resp.data.id);
      }

      dataDetalle.link = '../shared/servicios/servicios.php';
      if ($("#id_detalle").val() > 0) {
        dataDetalle.op = 'update_pedido_detalle';
        dataDetalle.id = $("#id_detalle").val()
      } else {
        dataDetalle.op = 'add_pedido_detalle';
      }
      dataDetalle.id_pedido = $("#id_pedido").val();
      dataDetalle.und = unidad;
      dataDetalle.can_und = $("#unidad option:selected").text().split("-")[1];
      dataDetalle.valor_unitario = parseFloat(($("#costo").val().replace(/,/g, ''))) / dataDetalle.can_und;
      dataDetalle.pcj_descuento = parseFloat($("#pcj_descuento").val().replace(/,/g, ''));
      dataDetalle.costo_unitario = costo_unitario / dataDetalle.can_und;
      dataDetalle.cantidad = cantidad * dataDetalle.can_und;
      dataDetalle.sw = 16;
      dataDetalle.id_bodega = $("#bodegas").val();

      let respDet = await enviarPeticion(dataDetalle);
      console.log({ respDet })

      if (respDet.error) {
        Swal.fire("Error", respDet.message, "error");
        return;
      }
      let idRef = $("#id_detalle").val() > 0 ? $("#id_detalle").val() : respDet.data.id
      await addReferencia(idRef);
      await recalcularEncabezado();
      dissminSwal();
      $("#buscar_ref").focus();
    } catch (e) {
      console.error(e)
      dissminSwal();
    }
  });

  //Actualizar nota de pedido
  $("#notas").focusout(() => {
    let idped = $("#id_pedido").val();
    if (!isNaN(parseFloat(idped)) && idped > 0) {
      updateNota(idped);
    }
  });
  $("#btnGuardar").click(() => {
    let idped = $("#id_pedido").val();
    if (!isNaN(parseFloat(idped)) && idped > 0) {
      guardar(idped);
    } else {
      Swal.fire('Oops!', 'No hay nada que guardar.', 'error');
    }
  });


  const hoy = new Date();
  const fechaFormateada = hoy.toISOString().split('T')[0];
  $("#fecha_inicial,#fecha_final").val(fechaFormateada);
  dissminSwal();
})
