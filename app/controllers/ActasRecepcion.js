
const tiposDoc = async () => {

  const entradas = await enviarPeticion({
    link: '../models/tiposDocumentos.php',
    sw: 2,
    op: 'listar_sw',
    bodega: $("#id_bodega_session").val()
  })

  let p = `<option value="">Todos</option>`;

  entradas.data.forEach(item => {
    p += `<option value="${item.id}" data-element='${JSON.stringify(item)}'>${item.tipo}</option>`
  })

  const compras = await enviarPeticion({
    link: '../models/tiposDocumentos.php',
    sw: 1,
    op: 'listar_sw',
    bodega: $("#id_bodega_session").val()
  })

  compras.data.forEach(item => {
    p += `<option value="${item.id}" data-element='${JSON.stringify(item)}'>${item.tipo}</option>`
  })
  $("#tipos").html(p)
}

const nuevo = () => {
  $("#id_tranasaccion").val('')
  $("#id_tercero").val('')
  $("#proveedor").val('')
  $("#busqueda-proveedor").val('')
  $("#tipos").val('')
  $("#numero").val('')
  $("#items-recepcion").text("");
  $("#result-documentos").html('')
  // Limpiar formulario historial
  $("#id_tercero_his").val('')
  $("#proveedor_his").val('')
  $("#busqueda_proveedor_his").val('')
  $("#result_his").html('')
  // Desactivar pestaña de aceptación
  $("#btn-navs-creacion").addClass('disabled').attr('tabindex', '-1');
  fechaActual([
    ['fecha-inicial', 'fecha-final'],
    ['fecha-inicial_his', 'fecha-final_his']
  ]);
  $('#tabs button[data-bs-target="#navs-tercero"]').tab('show')
}
$(async function () {
  fechaActual([
    ['fecha-inicial', 'fecha-final'],
    ['fecha-inicial_his', 'fecha-final_his']
  ]);

  await tiposDoc();

  $("#nuevo").on('click', function () {
    nuevo();
  })

  $("#consultar_his").click(async function () {
    const fecha_inicial = $("#fecha-inicial_his").val()
    const fecha_final = $("#fecha-final_his").val()
    const id_tercero = $("#id_tercero_his").val()
    try {
      const resp = await enviarPeticion({
        link: '../models/ActaRecepcion.php',
        fecha_inicial,
        fecha_final,
        id_tercero,
        op: 'historial'
      });

      if (resp.length == 0) {
        Swal.fire("Ups", "No se encontraron resultados", "error");
        return;
      }

      $("#result_his").html(`
          <table id="hiatoria" class="table table-sm table-borderless table-hover table-striped">
                    <thead class="table-primary"></thead> 
          </table>`)
      createDataTable(
        'hiatoria',
        resp,
        ['tipo', 'numero', 'fecha_doc', 'fecha_creacion', 'nit', 'proveedor', 'items'],
        ['Tipo', 'Numero', 'Fecha doc', 'Fecha acta', 'Nit', 'Proveedor', 'Items'],
        null,
        null,
        true,
        `<button class="btn btn-sm btn-outline-primary btn-imprimir-acta" title="Imprimir Acta"><i class="fa fa-print"></i></button>`
      );

      // Delegated event para botones de imprimir en historial
      $(document).on('click', '.btn-imprimir-acta', function () {
        const row = $(this).closest('tr');
        const tipo = row.find('td').eq(0).text().trim();
        const numero = row.find('td').eq(1).text().trim();
        if (tipo) {
          $('#iframe-pdf-acta').attr('src', `../pdf/pdf_acta_recepcion.php?tipo=${tipo}&numero=${numero}`);
          $('#modalPdfActa').modal('show');
        } else {
          Swal.fire("Error", "No se pudo obtener el ID del acta", "error");
        }
      });
    } catch (error) {
      console.error(error)
    }
  })
  $("#guardar").on('click', async function () {

    showLoadingWithTetrisLogo('Guardando acta de recepción', 'ISA Software');
    try {
      let errores = false;
      let items_ = new Array();
      // $(".detalle-recepcion tbody tr").each()
      $(".detalle-recepcion tbody tr").each(function () {
        const tr = $(this);

        tr.removeClass("table-danger")

        const cantidad = tr.find("td.tr-cantdad").find("input").val()
        const data = JSON.parse(tr.attr("data-element"))
        const nota = $.trim(tr.find("td.tr-nota").find("textarea").val())
        const invima = $.trim(tr.find("td.tr-invima").find("input").val())
        const id_referencia = data.id;

        if (isNaN(parseInt(cantidad)) || cantidad == 0) {
          tr.addClass("table-danger");
          errores = true;
        }

        const lote = $.trim(tr.find("td.tr-lote").find("input").val());
        const vmcto = $.trim(tr.find("td.tr-vmcto").find("input").val());

        const aprobado = tr.find("td").eq(9).find("input").is(":checked");

        if (lote.length == 0) {
          errores = true;
          tr.addClass("table-danger");
        }

        if (vmcto == '') {
          errores = true;
          tr.addClass("table-danger");
        }

        if (invima == '') {
          errores = true;
          tr.addClass("table-danger");
        }

        items_.push({
          cantidad,
          lote,
          vmcto,
          id_referencia,
          nota,
          invima,
          aprobado,
          id_tercero: $("#id_tercero").val(),
          id_bodega: $("#id_bodega_session").val(),
          id_usuario: $("#id_usuario").val(),
          id_transaccion: $("#id_transaccion").val()
        })

      })

      if (errores) {
        dissminSwal();
        Swal.fire("Error", "Hay productos con datos no validos", "error");
        return;
      }

      const resp = await enviarPeticion({
        link: '../models/ActaRecepcion.php',
        op: 'guardar',
        items_: JSON.stringify(items_)
      });

      if (!resp.ok) {
        Swal.fire("Error", resp.message, "error");
        return;
      }

      dissminSwal();

      // Mostrar PDF del acta guardada
      if (resp.tipo && resp.numero) {
        Swal.fire({
          title: "Acta guardada correctamente",
          text: "¿Deseas ver el PDF del acta de recepción?",
          icon: "success",
          showCancelButton: true,
          confirmButtonText: "Ver PDF",
          cancelButtonText: "Cerrar"
        }).then((result) => {
          if (result.isConfirmed) {
            $('#iframe-pdf-acta').attr('src', `../pdf/pdf_acta_recepcion.php?tipo=${resp.tipo}&numero=${resp.numero}`);
            $('#modalPdfActa').modal('show');
          }
          nuevo();
        });
      } else {
        Swal.fire("Ok", "Acta guardada correctamente!", "success");
        nuevo();
      }


    } catch (error) {
      console.error(error)
      Swal.fire("Error", error.message ? error?.message : error?.responseText, "error");
    }

  })
  $("#busqueda-proveedor").keyup(function () {
    if ($(this).val().length == 0) {
      $("#id_tercero").val('')
      $("#proveedor").val("")
      return;
    }
  });

  $("#busqueda_proveedor_his").keyup(function () {
    if ($(this).val().length == 0) {
      $("#id_tercero_his").val('')
      $("#proveedor_his").val("")
      return;
    }
  })

  $("#consultar").click(async function () {
    showLoadingWithTetrisLogo('Consultando documentos', 'ISA Software')

    const fecha_inicial = $("#fecha-inicial").val();
    const fecha_final = $("#fecha-final").val();
    const id_tercero = $("#id_tercero").val();
    const tipo = $("#tipos").val();
    const numero = $("#numero").val();

    if (isNaN(parseInt($("#id_tercero").val())) || $("#id_tercero").val() == 0) {
      Swal.fire("Oops", "Es necesario seleccionar el proveedor", "warning")
      return;
    }
    $("#modalDocumentos").modal("show");
    try {

      const documentos = await enviarPeticion({
        link: '../models/ActaRecepcion.php',
        op: 'acta-recepcion',
        fecha_inicial,
        fecha_final,
        id_tercero,
        tipo,
        numero
      })
      console.log(documentos)

      if (documentos.length == 0) {
        dissminSwal()
        Swal.fire("Ups", "No se encotraron resultados", "info");
        $("#result-documentos").html(``);
        return;
      }

      let tb = `
            <table class="table table-sm table-hover ">
                <thead class="table-primary">
                    <tr>
                        <th>Tipo</th>
                        <th>Numero</th>
                        <th>Proveedor</th>
                        <th>Fecha</th>
                        <th>Valor</th>
                        <th>Items</th>
                    </tr>
                </thead>
            <tbody>
        `
      documentos.forEach(item => {
        tb += `<tr class="tr_documentos" data-id = ${item.id} data-element = ${JSON.stringify(item)}>
                <td>${item.tipo}</td>
                <td>${item.numero}</td>
                <td>${item.proveedor}</td>
                <td>${item.fecha_hora}</td>
                <td>${formatNum(item.valor_total,'$')}</td>
                <td>${item.items}</td>
            </tr>`
      })

      tb += `</tbody>
        </table>`

      $("#result-documentos").html(tb);

      $(".tr_documentos").on('click', async function () {

        try {

          const id = $(this).attr("data-id");
          const data_e = $(this).attr("data-element");

          showLoadingWithTetrisLogo('Cargando items', 'ISA Software');
          const resp = await enviarPeticion({
            link: '../models/ActaRecepcion.php',
            op: 'carga-detalle',
            id_transaccion: id
          });

          if (resp.length == 0) {
            dissminSwal()
            Swal.fire("Sin productos pendientes", "Todos los productos de este documento ya tienen acta de recepción registrada.", "info");
            return;
          }

          let tbd = `
            <table class="table table-sm table-hover detalle-recepcion">
              <thead class="table-primary">
                <tr>
                  <th>Codigo</th>
                  <th>Descripción</th>
                  <th>Marca</th>
                  <th>Cantidad</th>
                  <th>Und</th>
                  <th>Lote</th>
                  <th>Vencimiento</th>
                  <th>Invima</th>
                  <th>Nota</th>
                  <th>Aprobado</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
          `

          resp.forEach(item => {
            tbd += `<tr data-element ='${JSON.stringify(item)}'>
                      <td>${item.codigo}</td>
                      <td>${item.descripcion}</td>
                      <td>${item.marca}</td>
                      <td class="tr-cantdad"><input type="number" value="${item.cantidad}" class="form-control form-control-sm" /></td>
                      <td>${item.und}</td>
                      <td class="tr-lote"><input type="text" value="${item.lote}" class="form-control form-control-sm" /></td>
                      <td class="tr-vmcto"><input type="date" value="${item.vmcto}" class="form-control form-control-sm" /></td>
                      <td class="tr-invima"><input type="text" value="${item.invima}" maxlength="70" class="form-control form-control-sm" /></td>
                      <td class="tr-nota"><textarea class="form-control" maxlength="150" rows="1"></textarea></td>
                      <td><input type="checkbox" class="form-check-input" checked /></td>
                      <td><button class="btn btn-sm btn-outline-danger btn-eleiminar-item"><i class="fa fa-trash"></i></button></td>
             </tr>`
          })
          tbd += `<tbody>
          </table>`
          $("#items-recepcion").html(tbd);

          // Habilitar y mostrar pestaña de aceptación
          $("#btn-navs-creacion").removeClass('disabled').removeAttr('tabindex');
          $('#tabs button[data-bs-target="#navs-creacion"]').tab('show');
          dissminSwal()
          $("#modalDocumentos").modal("hide");

          $("#id_transaccion").val(id);

        } catch (error) {
          console.error(error)
          dissminSwal();
          Swal.fire("Ups", "Se genero un error en el sistema", "info")
        }

      })
      $("#modalDocumentos").modal("show");
      dissminSwal()
    } catch (error) {
      console.error(error)
      Swal.fire("Ups", "Ha ocurrido un error al consultar los datos", "error")
    }

  })

  $('#busqueda_proveedor_his').autocomplete({
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

      const item = ui.item;

      $("#id_tercero_his").val(item.id);
      $("#proveedor_his").val(item.tratamiento + '/' + item.razon_comercial);

    }
  });


  $('#busqueda-proveedor').autocomplete({
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

      const item = ui.item;

      $("#id_tercero").val(item.id);
      $("#proveedor").val(item.tratamiento + '/' + item.razon_comercial);

    }
  });

  $('#modalPdfActa').on('hidden.bs.modal', function () {
    $('#iframe-pdf-acta').attr('src', '');
  });

  dissminSwal()

})