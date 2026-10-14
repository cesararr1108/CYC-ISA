const id_modulo = '45';
const validarAperturaCaja = async () => {
  try {

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
    return true;

  } catch (e) {
    console.error("Error validando apertura de caja:", e);
    return false;
  }
};
// configuraciones 
let carrito_enc = [];
let carrito_det = [];
let permisos = []

const bloquearCampos = (sw) => {
  $("#numero-factura").attr("disabled", sw)
  $("#tipo-factura").attr("disabled", sw)
  $("#tipos").attr("disabled", sw)
  $("#bodegas").attr("disabled", sw)
  $("#id_condicion").attr("disabled", sw)
  $("#notas").attr("disabled", sw)
}
const buscarFacturas = async () => {
  showLoadingWithTetrisLogo('Buscando facturas asociadas', 'ISA Software')

  try {
    const resp = await enviarPeticion({
      link: '../models/devolucion-pos.php',
      op: 'buscar-factura',
      id_tercero: $("#id_tercero").val(),
      tipo: $("#tipo-factura").val(),
      numero: $("#numero-factura").val(),
      fecha_inicial: $("#fecha-inicial").val(),
      fecha_final: $("#fecha-final").val(),
      bodega: $("#bodegas").val()
    });

    if (!resp.ok) {
      Swal.fire("Error", resp.message, "error")
      return;
    }

    console.log({ resp });
    if (resp.data.length == 0) {
      dissminSwal()
      Swal.fire("Ups", "No se encontraron resultados", "warning")
      return;
    }

    let otro_medio = 0;

    let tb = `<table class="table table-sm table-striped table-hover">
                  <thead class="table-primary">
                    <tr>
                      <th>#</th>
                      <th>Fecha</th>
                      <th>Tipo</th>
                      <th>Numero</th>
                      <th>Nit</th>
                      <th>Cliente</th>
                      <th>Valor</th>
                      <th>Vendedor</th>
                    </tr>
                  </thead>
            <tbody>`

    resp.data.forEach(item => {

      otro_medio = item.otro_medio;

      tb += `<tr class="tr-facturas-clientes" data-element = '${JSON.stringify(item)}'>
                    <td>${item.id}</td>
                    <td>${item.fecha_hora}</td>
                    <td>${item.tipo}</td>
                    <td>${item.numero}</td>
                    <td>${item.nit}</td>
                    <td>${item.tratamiento}</td>
                    <td>${formatNum(item.valor_total, '$')}</td>
                    <td>${item.vendedor}</td>
              </tr>`

    });

    tb += `</tbody>
          </table>`
    $("#result-facturas").html(tb)
    saldoCaja('saldo-caja-busqueda-factura');
    dissminSwal()

    // agrega el evento a todas las filas
    document.querySelectorAll('.tr-facturas-clientes').forEach(tr => {

      tr.addEventListener('click', async () => {

        saldoCaja('saldo-caja-detalle-factura')

        const id_transaccion = tr.children[0].textContent;
        const num_fact = tr.children[3].textContent;
        const tip_fact = tr.children[2].textContent;
        const element = JSON.parse(tr.getAttribute("data-element"));

        $("#documento-ref").text(tip_fact + '-' + num_fact)
        $("#valor-devolucion-detalle-factura").val("$0")

        try {

          showLoadingWithTetrisLogo('Cargando items', 'ISA Software');

          const detalle = await enviarPeticion({
            link: '../models/devolucion-pos.php',
            op: 'detalle-fact',
            id_transaccion,
          });

          if (detalle.length == 0) {
            Swal.fire("Ups", "No se encontraron items en la factura", "error")
            return;
          }

          $("#cod_zona").val(element.codigo_zona)
          $("#zona").val(element.des_zona_ventas)
          $("#fact-cliente").val(element.tratamiento + '/' + element.razon_comercial)
          $("#id_direccion").val(element.id_direccion)
          $("#id_tercero").val(element.id_tercero)
          $("#nit").val(element.nit)
          $("#fact-direccion").val(element.direccion)
          $("#cliente").val(element.tratamiento + '/' + element.razon_comercial)
          $("#vencimiento").val(element.vencimiento)
          $("#dias-vencimiento").val(element.dias)
          $("#condicion").val(element.condicion)
          $("#tipo_factura").val(element.tipo_factura)
          $("#id_transaccion_ref").val(element.id)


          let tbd = `<table class="table table-sm table-striped table-hover data-detalle-factura">
                                <thead class="table-primary">
                                  <tr>
                                    <th>#</th>
                                    <th>Codigo</th>
                                    <th>Producto</th>
                                    <th>Lab</th>
                                    <th>Cantidad</th>
                                    <th>Und</th>
                                    <th>Iva</th>
                                    <th>Dcto</th>
                                    <th>Precio</th>
                                    <th>Total</th>
                                    <th>Dev</th>
                                    <th>Lote</th>
                                    <th>Vmcto</th>
                                    <th>Cant dev.</th>
                                  </tr>
                                </thead>
                                <tbody>`

          detalle.forEach(item => {

            let total = item.precio * item.cantidad;
            let cant_maxima = (item.cantidad - item.cantidad_devuelta) < 0 ? 0 : (item.cantidad - item.cantidad_devuelta);
            let disabled_input = '';
            let cantidad_defaul = 0;
            let disbled_edit_cant = ''

            if (cant_maxima == 0) {
              disabled_input = 'disabled';
            }

            if (item.cantidad == 1 && item.cantidad_devuelta == 0) {
              cantidad_defaul = item.cantidad;
            }

            item['otro_medio'] = otro_medio

            tbd += `<tr class="tr-facturas-clientes-detalle" data-element = '${JSON.stringify(item)}'>
                              <td>${item.id_transaccion}</td>
                              <td>${item.codigo}</td>
                              <td>${item.descripcion}</td>
                              <td>${item.lab}</td>
                              <td >${item.cantidad}</td>
                              <td>${item.und} x ${item.can_und}</td>
                              <td>${item.pcj_iva}</td>
                              <td>${item.pcj_descuento}</td>
                              <td class="data-detalle-factura-precio">${formatNum(item.precio, '$')}</td>
                              <td>${formatNum(total, '$')}</td>
                              <th>${item.cantidad_devuelta}</th>
                              <th>${item.lote}</th>
                              <th>${item.vmcto}</th>
                              <td class="data-detalle-factura-cantidad"><input type="number" ${disabled_input} value="0" class="cantidades form-control form-control-sm cantidad-devuelta" max="${cant_maxima}" min ="1"  /> </td>
                        </tr>`
          })

          tbd += `</tbody>
                    </table>`


          $("#modalFacturasDetalle").modal("show")
          $("#modalFacturas").modal("hide")
          $("#result-facturas").html('')
          $("#result-facturas-detalle").html(tbd)

          document.querySelectorAll('.cantidad-devuelta').forEach(inp => {
            inp.addEventListener('input', () => {
              const min = parseInt(inp.min);
              const max = parseInt(inp.max);
              let val = parseInt(inp.value);

              if (val > max) inp.value = max;
              if (val < min) inp.value = min;
            });
            inp.addEventListener('focus', () => {
              $(inp).select()
            });


            inp.addEventListener('focusout', () => {
              let total_dev = 0;
              $(".data-detalle-factura tbody tr").each(function () {
                tr = $(this);
                cantidad = isNaN(parseInt(tr.find("td.data-detalle-factura-cantidad").find("input").val())) ? 0 : parseInt(tr.find("td.data-detalle-factura-cantidad").find("input").val());
                precio = unformatNum(tr.find("td.data-detalle-factura-precio").html());
                total = precio * cantidad;
                total_dev += parseFloat(total)
              });

              $("#valor-devolucion-detalle-factura").val(formatNum(total_dev, '$'))
            })
          });
          dissminSwal();

        } catch (error) {
          console.error(error)
          dissminSwal()
          Swal.fire("Error", "Error al cargar los items", "error")
        }

      });
    }); //click 

    $("#modalFacturas").modal("show")


  } catch (error) {
    dissminSwal();
    console.error(error)
    Swal.fire("Error", "Error al consultar las facturas", "error")
  }

}

const limpiarTercero = () => {
  $("#id_tercero").val('0')
  $("#nit").val('');
  $("#cliente").val('');
  $("#id_direccion").val("");

}
const limpiarFiltros = () => {
  $("#tipo-factura").val('');
  $("#numero-factura").val('');
  obtenerFechas()
}

const obtenerFechas = () => {

}

const imprimir = (id_transaccion) => {
  ImprimirDocumento(4, id_transaccion, 'Impresion de devolución', false);
}

const nuevo = () => {
  $('#tabs button[data-bs-target="#navs-items"]').attr('disabled', false)
  $('#tabs button[data-bs-target="#navs-busqueda"]').tab("show")
  $("#modalFacturasDetalle").modal("hide")
  $("#modalFacturas").modal("hide");
  $("#result-facturas-detalle").html('');
  $("#documento-ref").text('')
  $("#items-devolver").html('');
  $("#result").html('');
  $("#t_bruto").val('$0');
  $("#t_dcto").val('$0');
  $("#t_iva").val('$0');
  $("#t_total").val('$0');
  $("#notas").val('');
  $("#fact-cliente").val('');
  $("#fact-direccion").val('');
  $("#cod_zona").val("");
  $("#zona").val("");
  $("#saldo-caja-busqueda-factura").val("$0")
  $("#saldo-caja-detalle-factura").val("$0")
  $("#saldo-caja-items").val("$0")
  $("#id_transaccion_ref").val('0')
  $("#valor-devolucion-detalle-factura").val("$0")
  bloquearCampos(false)
  limpiarTercero();
  limpiarFiltros();
  listarAlmacenes()
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
    $("#bodegas").val($("#id_bodega_session").val()).attr("disabled", true)
  } catch (e) {
    console.error(e)
    $("#result").html(ShowAlert('Error interno de la aplicación', 'error'))
  }
}
const cargarCliente = (item) => {

  $("#busqueda-tercero").val('');
  $("#nit").val(item.nit);
  $("#id_tercero").val(item.id);
  $("#cliente").val(item.tratamiento + '/' + item.razon_comercial)
}

const validarItems = () => {
  const nfilas = $(".table-items-dev tbody tr").length;

  if (nfilas == 0) {
    nuevo();
  }
}

const calcularTotales = () => {

  let vrlbruto = 0;
  let vlrdcto = 0;
  let vlriva = 0;
  let vlrtotal = 0;

  $(".table-items-dev tbody tr").each(function () {
    const element = JSON.parse($(this).attr("data-element"));
    cantidad = isNaN(parseInt($(this).find("td.td-cantidad").find("input").val())) ? 0 : parseInt($(this).find("td.td-cantidad").find("input").val())

    const valor_unitario = element.valor_unitario;
    const dcto = element.pcj_descuento;
    const iva = element.pcj_iva;

    const bruto = (valor_unitario * (1 - (dcto / 100))) * cantidad;
    const valor_iva = ((valor_unitario * (1 - (dcto / 100))) * (iva / 100)) * cantidad;
    const valor_dcto = (valor_unitario * (dcto / 100)) * cantidad;
    const total = bruto - valor_dcto + valor_iva;

    vrlbruto = vrlbruto + parseFloat(bruto)
    vlrdcto = vlrdcto + parseFloat(valor_dcto)
    vlriva = vlriva + parseFloat(valor_iva)
    vlrtotal = vlrtotal + parseFloat(total)

  });

  $("#t_bruto").val(formatNum(vrlbruto, '$'))
  $("#t_dcto").val(formatNum(vlrdcto, '$'))
  $("#t_iva").val(formatNum(vlriva, '$'))
  $("#t_total").val(formatNum(vlrtotal, '$'))

}


const saldoCaja = async (id) => {
  try {
    const resp = await enviarPeticion({
      link: '../shared/servicios/servicios.php',
      op: 'valor-caja-turno'
    });
    $("#" + id).val(formatNum(resp[0].saldo, '$'))
  } catch (error) {
    console.error(error)
  }
}


$(async function () {
  permisos = await permisosUsuario(id_modulo)
  const validCaja = await validarAperturaCaja();
  if (!validCaja) {
    return;
  }
  fechaActual([
    ['fecha-inicial', 'fecha-final'],
    ['dian_fecha_inicial', 'dian_fecha_final'],

  ]);
  const {
    general,
    modulos
  } = await cargarConfiguraciones();
  ConfGeneral = general;
  ConfModulos = modulos[id_modulo];
  await listarAlmacenes();
  await condicionesPago('C', 'id_condicion', false);
  await tiposDocumentos(4, $("#bodegas").val(), 'tipos');

  $("#tipo-factura").focus();

  $(".cantidades").on('focus', () => {
    $(this).select();
  })

  $("#bodegas").on('change', async function () {
    $('#tipos').empty();
    await tiposDocumentos(4, $(this).val(), 'tipos');

    if ($('#tipos option').length === 0) {
      Swal.fire("Ups", "No se encontro tipo documento asociado a esta bodega", "warning")
      $('#bodegas')[0].selectedIndex = 0;
      await tiposDocumentos(4, $("#bodegas").val(), 'tipos');
      return;
    }
  });

  const modalBusquedaTer = new ModalBusquedaTerceros((rowData) => {
    cargarCliente(rowData)
  }, {});

  $("#busqueda-tercero").keyup(async function (e) {
    const filtro = $.trim($(this).val())

    if (filtro.length == 0) {
      return;
    }

    if (e.key == 'Enter') {
      await modalBusquedaTer.getRecords(filtro);
      if (modalBusquedaTer.getNrecords() > 0) {
        modalBusquedaTer.show();
      }
    }
  });

  // Controlar visibilidad de alertas de caja basado en checkbox
  const toggleCajaInfo = () => {
    const isChecked = $("#pago-igual-pago-venta").is(":checked");
    const salInfo = $(".saldo-caja-info");

    if (isChecked) {
      // Si está marcado: ocultar alertas y saldos (usar métodos de pago de la factura)
      salInfo.css("display", "none");
    } else {
      // Si está desmarcado: mostrar alertas y saldos (validar saldo en caja)
      salInfo.css("display", "");
    }
  };

  // Event listener para el checkbox - toggle en tiempo real
  $(document).on("change", "#pago-igual-pago-venta", function () {
    toggleCajaInfo();
  });

  // Inicializar el estado de visibilidad al cargar la página
  $(function () {
    toggleCajaInfo();
  });

  // Re-inicializar toggle cuando se abre modalFacturasDetalle
  $("#modalFacturasDetalle").on("show.bs.modal", function () {
    setTimeout(toggleCajaInfo, 100);
  });

  $("#buscar-factura").click(function () {

    if ($("#tipo-factura").val() == '' && $("#numero-factura").val() == '') {

      if ($("#id_tercero").val() == '0') {
        Swal.fire("Validación", "Debe seleccionar un tercero", "warning");
        return;
      }
      if (($("#fecha-inicial").val()) == '' || !validarFecha($("#fecha-final").val()) == '') {
        Swal.fire("Validación", "Debe selecionar una fecha inicial y final", "warning");
        return;
      }
    }

    buscarFacturas()
  })


  $("#filtro-items").keyup(function () {
    const filtro = $(this).val().toLowerCase();
    $(".data-detalle-factura tbody tr").filter(function () {
      $(this).toggle($(this).text().toLowerCase().indexOf(filtro) > -1)
    });
  });

  $("#nuevo").click(function () {
    nuevo()
  })

  $("#cargar-item").click(function () {
    nfilas = $(".data-detalle-factura tbody tr").length;

    if (nfilas == 0) {
      return;
    }

    valido = false;
    items = [];

    $(".data-detalle-factura tbody tr").each(function () {
      cantidad = $(this).find("td.data-detalle-factura-cantidad").find("input").val()
      let element = JSON.parse($(this).attr("data-element"));

      if (!isNaN(parseInt(cantidad)) && cantidad > 0) {
        let nuevo = {
          ...element
        };
        nuevo.cantidad_dev = parseInt(cantidad);
        items.push(nuevo)
      }
    });

    if (items.length == 0) {
      Swal.fire("Validación", "Debe indicar un producto a devolver ", "warning")
      return;
    }

    const saldo_caja = unformatNum($("#saldo-caja-detalle-factura").val());
    const total_dev = unformatNum($("#valor-devolucion-detalle-factura").val());

    if (!$("#pago-igual-pago-venta").is(":checked")) {
      if (total_dev > saldo_caja) {
        Swal.fire("Ups", "No se puede devolver mas dinero del disponible en caja", "error")
        return;
      }
    }


    tbDetalleDev = `
          <table class="table table-sm table-striped table-hover table-items-dev">
            <thead class="table-primary">
              <tr>
                <th>Código</th>
                <th>Descripción</th>
                <th>Lab</th>
                <th>Dcto</th>
                <th>Iva</th>
                <th>Precio</th>
                <th>Cant. Fact</th>
                <th>Und</th>
                <th>Cant dev.</th>
                <th>Cant a dev</th>
                <th>Total</th>
                <th></th>
              </tr>
            </thead>
        `
    let vrlbruto = 0;
    let vlrdcto = 0;
    let vlriva = 0;
    let vlrtotal = 0;

    items.forEach(item => {
      // total = item.cantidad_dev *item.precio;
      cant_maxima = (item.cantidad - item.cantidad_devuelta) < 0 ? 0 : (item.cantidad - item.cantidad_devuelta);

      const valor_unitario = item.valor_unitario;
      const dcto = item.pcj_descuento;
      const iva = item.pcj_iva;

      const bruto = parseFloat((valor_unitario * (1 - (dcto / 100))) * item.cantidad_dev);
      const valor_iva = parseFloat(((valor_unitario * (1 - (dcto / 100))) * (iva / 100)) * item.cantidad_dev);
      const valor_dcto = parseFloat((valor_unitario * (dcto / 100)) * item.cantidad_dev);
      const total = Math.round(bruto - valor_dcto + valor_iva);

      vrlbruto = vrlbruto + parseFloat(bruto)
      vlrdcto = vlrdcto + parseFloat(valor_dcto)
      vlriva = vlriva + parseFloat(valor_iva)
      vlrtotal = vlrtotal + parseFloat(total)

      let disabled_edit_cant = ''
      let disabled_delete = '';

      tbDetalleDev += `
            <tr class="" data-element='${JSON.stringify(item)}'>
              <td>${item.codigo}</td>
              <td>${item.descripcion}</td>
              <td>${item.lab}</td>
              <td>${item.pcj_descuento}</td>
              <td>${item.pcj_iva}</td>
              <td>${formatNum(item.precio, '$')}</td>
              <td>${item.cantidad}</td>
              <td>${item.cantidad_devuelta}</td>
              <td>${item.und} x ${item.can_und}</td>
              <td class="td-cantidad"><input  type="number" ${disabled_edit_cant} value="${item.cantidad_dev}" max="${cant_maxima}" min="1" data-element='${JSON.stringify(item)}' class="form-control form-control-sm cantidad-dev-cart cantidades" /></td>
              <td class="tr-total-item-dev">${formatNum(total, '$')}</td>
              <td><button class="btn btn-sm btn-outline-danger btn-delete" ${disabled_delete} ><i class="fa fa-trash"></i></button></td>
            </tr>
          `
    }) //
    tbDetalleDev += `</tbody>
        </table>`;
    $("#items-devolver").html(tbDetalleDev);

    $("#t_bruto").val(formatNum(vrlbruto, '$'))
    $("#t_dcto").val(formatNum(vlrdcto, '$'))
    $("#t_iva").val(formatNum(vlriva, '$'))
    $("#t_total").val(formatNum(vlrtotal, '$'))
    saldoCaja('saldo-caja-items')

    document.querySelectorAll('.cantidad-dev-cart').forEach(inp => {
      inp.addEventListener('input', () => {
        const min = parseInt(inp.min);
        const max = parseInt(inp.max);
        let val = parseInt(inp.value);

        if (val > max) inp.value = max;
        if (val < min) inp.value = min;
      });
    });

    document.querySelectorAll('.cantidad-dev-cart').forEach(inp => {
      inp.addEventListener('focus', () => {
        $("#tmp").val(inp.value)
      })
      inp.addEventListener('focusout', () => {
        if (isNaN(parseInt(inp.value)) || inp.value == 0) {
          inp.value = $("#tmp").val();
          return;
        }

        element = JSON.parse($(inp).attr("data-element"))
        total = element.precio * inp.value;

        inp.closest("tr").querySelectorAll("td.tr-total-item-dev").forEach(td => {
          td.innerHTML = formatNum(total, '$')
        })
        calcularTotales();
      })
    })
    //ELIMINA LA FILA  
    document.querySelectorAll('.btn-delete').forEach(btn => {
      btn.addEventListener('click', () => {
        btn.closest('tr').remove();
        calcularTotales();
        validarItems()
      })
    });

    $('#tabs button[data-bs-target="#navs-items"]').attr('disabled', false)
    $('#tabs button[data-bs-target="#navs-items"]').tab("show")
    $("#modalFacturasDetalle").modal("hide")
    $("#result-facturas-detalle").html('')
    $("#documento-ref").text('')
    bloquearCampos(true)
  });

  $("#guardar").click(async function () {
    //Se valida nuevamente el cierre de caja para evitar que el usuario haya dejado abierta la pantalla y se haya cerrado la caja posteriormente
    const validaCierre = await validarAperturaCaja();
    if (!validaCierre) {
      return;
    }
    try {
      const nfila = $(".table-items-dev tbody tr").length;
      const saldo_caja = unformatNum($("#saldo-caja-items").val())
      const valor_dev = unformatNum($("#t_total").val())

      if (nfila == 0) {
        Swal.fire("", "No se ha agregado items para devolución", "warning")
        return;
      }
      const pago_igual = $("#pago-igual-pago-venta").is(":checked")
      let devolver_pago_completo = 0;
      if (pago_igual == false) {
        if (valor_dev > saldo_caja) {
          Swal.fire("Ups", "No se puede hacer una devolución por un valor mayor al saldo en caja", "error")
          return;
        }
        devolver_pago_completo = 1;
      }


      const items = [];
      let vendedor = 0;
      let otro_medio = 0;

      $(".table-items-dev tbody tr").each(function () {
        const element = JSON.parse($(this).attr("data-element"));

        const cant_ = isNaN(parseInt($(this).find("td.td-cantidad").find("input").val())) ? 0 : parseInt($(this).find("td.td-cantidad").find("input").val());
        let id_lote = null;
        let lote = null;
        let vmcto = null;

        if (ConfGeneral.ManejaFechaLote == 'S') {
          id_lote = element.id_lote;
          lote = element.lote;
          vmcto = element.vencimiento;
        }

        if (cant_ > 0) {
          vendedor = element.vendedor
          otro_medio = element.otro_medio;
          items.push({
            id_transaccion: 0,
            id_referencia: element.id_referencia,
            cantidad: cant_ * element.can_und,
            valor_unitario: element.valor_unitario,
            costo_unitario: element.costo_unitario,
            id_lote,
            lote,
            vmcto,
            pcj_iva: element.pcj_iva,
            pcj_descuento: element.pcj_descuento,
            und: element.und,
            can_und: element.can_und,
            sw: 4,
            id_bodega: element.id_bodega,
            id_pedido: 0,
            impoconsumo: element.impoconsumo,
            ibua: element.ibua,
            icui: element.icui,
            id_transaccion_ref: element.id_transaccion,
            comision: element.comision
          });
        }

      })

      showLoadingWithTetrisLogo('Guardando documento...', 'ISA Software');
      //agregar el encabezado 
      const datosEncabezado = await enviarPeticion({
        link: '../models/devolucion-pos.php',
        op: 'guardar-devolucion',
        id_tipo_documento: $("#tipos").val(),
        id_tercero: $("#id_tercero").val(),
        id_bodega: $("#bodegas").val(),
        notas: $.trim($("#notas").val()),
        valor_total: unformatNum($("#t_total").val()),
        valor_aplicado: unformatNum($("#t_total").val()),
        iva: unformatNum($("#t_iva").val()),
        descuento: unformatNum($("#t_dcto").val()),
        sw: 4,
        id_tercero_dir: $.trim($("#id_direccion").val()),
        id_condicion: $("#id_condicion").val(),
        zona_ventas: $.trim($("#cod_zona").val()),
        zona_descripcion: $.trim($("#zona").val()),
        detalle: JSON.stringify(items),
        vendedor,
        documento: $("#tipo-factura").val() + '-' + $("#numero-factura").val(),
        tipo_factura: $("#tipo-factura").val(),
        numero_factura: $("#numero-factura").val(),
        otro_medio,
        tipo: $.trim($("#tipos option:selected").text()),
        vencimiento: $("#vencimiento").val(),
        id_transaccion_ref: $("#id_transaccion_ref").val(),
        es_credito: 0,
        devolver_pago_completo
      });

      if (!datosEncabezado.ok) {
        dissminSwal();
        Swal.fire("Error", "Error al insertar el registro", "error")
        return;
      }

      nuevo();
      bloquearCampos(false)
      limpiarFiltros();
      limpiarTercero();
      dissminSwal();
      ImprimirDocumento(4, datosEncabezado.id_transaccion, 'Devolucion venta pos')

    } catch (error) {
      dissminSwal();
      Swal.fire("Error", "Error al insertar el registro", "error")
      console.error(error)
    }
  });

  $("#numero-factura").keyup(async function (e) {

    if (e.key === 'Enter') {
      const tipo = $.trim($("#tipo-factura").val());
      const numero = $(this).val()

      if (tipo != '' && numero > 0) {
        await buscarFacturas();
      }

    }

  });

  // ======================== ENVÍO A DIAN ========================
  const enviarDevolucionElectronica = async (id, sw) => {
    try {

      showLoadingWithTetrisLogo('Enviando devolución', 'ISA Software')
      const resp = await enviarDocumentoElctronico(id, sw); // sw 4 para devoluciones
      console.log({ resp })
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
      await listarDevoluciones();

    } catch (error) {
      dissminSwal();
      Swal.fire("Ups", "Ha ocurrido un error al enviar el documento", "error")
      console.error(error)
    }
  }

  const reenviarMailDevolucion = async (tipo, numero, email) => {
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
          Swal.close();
          Swal.fire('Error', 'Ocurrió un error al enviar el correo', 'error');
          console.error('Error al reenviar correo:', error);
        }
      }
    });
  }

  const listarDevoluciones = async () => {
    try {
      const fecha_inicial = $("#dian_fecha_inicial").val()
      const fecha_final = $("#dian_fecha_final").val()
      const data_tipos = JSON.parse($("#tipos option:selected").attr("data-element"));

      showLoadingWithTetrisLogo('Consultando información', 'ISA Software')

      const resp = await enviarPeticion({
        link: '../models/devolucion-pos.php',
        op: 'listar-devoluciones',
        fecha_inicial,
        fecha_final,
        id_tipo_documento: $("#tipos").val()
      });
      dissminSwal();
      console.log({ resp })

      if (resp.length == 0) {
        $("#listado-devoluciones").html(`
             <div class="mt-3 alert alert-warning"><i class="fa-solid fa-triangle-exclamation"></i> No se encontraron resultados!</div>
          `);
        return;
      }

      if (data_tipos.documento_electronico == 'S') {
        tb = `<table class="table table-sm" >
                  <thead class="table-primary">
                    <tr>
                      <th>Numero</th>
                      <th>Tipo</th>
                      <th>Fecha</th>
                      <th>Nit</th>
                      <th>Cliente</th>
                      <th>Valor total</th>
                      <th>Email</th>
                      <th>Estado</th>
                      <th>Opc</th>
                    </tr>
                  </thead>
                  <tbody>`
      } else {
        tb = `<table class="table table-sm" >
                <thead class="table-primary">
                  <tr>
                    <th>Numero</th>
                    <th>Tipo</th>
                    <th>Fecha</th>
                    <th>Nit</th>
                    <th>Cliente</th>
                    <th>Valor total</th>
                    <th>Opc</th>
                  </tr>
                </thead>
                <tbody>`
      }

      resp.forEach(item => {
        let opc = '';
        let estado = `<button class="btn btn-sm btn-outline-danger" onclick="enviarDevolucionElectronica(${item.id},4)"><i class="fa-solid fa-paper-plane"></i></button>`

        if (data_tipos.documento_electronico == 'S') {
          if (item.cufe != '') {
            estado = `<button class="btn btn-sm btn-outline-success" ><i class="fa-solid fa-circle-check text-success"></i></button>`;
            opc = `<div class="btn-group ">
                    <button class="btn btn-sm btn-outline-primary"  onclick="ImprimirDocumento(4,${item.id},'Devolución electrónica de venta',false)"><i class="fa-solid fa-print"></i></button>
                    <button class="btn btn-sm btn-outline-secondary" data-element='${JSON.stringify(item)}'
                    onclick="reenviarMailDevolucion('${item.tipo}',${item.numero},'${item.email}')"><i class="fa-solid fa-envelope"></i></button>
                  </div>`
          }
        } else {
          estado = '<i class="fa-regular fa-circle-check text-success"></i>';
          opc = `<button class="btn btn-sm btn-outline-primary"  onclick="ImprimirDocumento(4,${item.id},'Devolución de venta',false)"><i class="fa-solid fa-print"></i></button>`
        }

        tb += `<tr>
                    <td>${item.numero}</td>
                    <td>${item.tipo}</td>
                    <td>${item.fecha}</td>
                    <td>${item.nit}</td>
                    <td>${item.cliente}</td>
                    <td>${item.valor_total}</td>`;
        if (data_tipos.documento_electronico == 'S') {
          tb += `
                    <td>${item.email}</td>
                    <td align="center">${estado}</td> `;
        }
        tb += `<td>${opc}</td>`;

        tb += `</tr>`
      })
      tb += `</tbody>
          </table>`
      $("#listado-devoluciones").html(tb);

    } catch (error) {
      console.error(error)
      dissminSwal();
    }
  }

  $("#consultar-dian").click(async function () {
    await listarDevoluciones();
  });
  // ======================== FIN ENVÍO A DIAN ========================

  dissminSwal();

});
