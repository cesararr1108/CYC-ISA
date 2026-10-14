function ImprimirIngreso(data) {
  console.log(data)
  let url = 'https://thecyctechnology.com/cyc/app/pdf/';
  url += 'IngresoMercanciaTirilla.php?id=' + data.id;
  $("#modalDoc").remove();

  // Crear el modal y el iframe
  $("body").append(`
      <div class="modal fade" id="modalDoc" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Ingreso de mercancias</h5>
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
}

const consultar = async () => {
  showLoadingWithTetrisLogo('Buscando información', 'ISA Software')
  try {
    const resp = await enviarPeticion({
      link: '../models/RptComprasProveedor.php',
      id_tercero: $("#id_tercero").val(),
      fecha_inicial: $("#fecha_inicial").val(),
      fecha_final: $("#fecha_final").val(),
      op: $("#tipo").val()
    });

    if (resp.length === 0) {
      dissminSwal();
      Swal.fire("Ups", "No se encontraron resultados", "info");
      return;
    }

    // 👉 Contenedor fluido + scroll horizontal
    const tb = `
        <table class="table table-sm table-borderless table-hover table-striped w-100" id="compras">
          <thead class="table-primary"></thead>
          <tbody></tbody>
          <tfoot></tfoot>
        </table>
    `;
    $("#result").html(tb);

    if ($("#tipo").val() === 'consolidado') {
      createDataTable(
        'compras',
        resp,
        ['doc_ref', 'fecha', 'nit', 'razon_comercial', 'condicion', 'vencimiento', 'tipo', 'numero', 'subtotal', 'descuento', 'iva', 'impoconsumo', 'ibua', 'icui', 'ReteFte', 'neto', 'valor_total'],
        ['Documento', 'Fecha/Doc', 'Nit', 'Proveedor', 'Condición de pago', 'Vencimiento', 'Tipo', 'Número', 'Subtotal', 'Descuento', 'IVA', 'Impoconsumo', 'IBUA', 'ICUI', 'ReteFte', 'Neto', 'Total'],
        null,
        null,
        true,
        `<button class="btn btn-outline-primary other-btn"> <i class="fa fa-print"></i></button>`,
        ImprimirIngreso,
        true,
        ['subtotal', 'descuento', 'iva', 'impoconsumo', 'ibua', 'icui', 'retencion', 'neto', 'valor_total'],
        'COP',
        [],
        ['subtotal', 'descuento', 'iva', 'impoconsumo', 'ibua', 'icui', 'retencion', 'neto', 'valor_total']
      );
    } else {
      createDataTable(
        'compras',
        resp,
        [
          'codigo',
          'producto',
          'marca',
          'cantidad',
          'und',
          'costo',
          'costo_promedio',
          'precio_lista',
          'utilidad',
          'rentabilidad',
          'margen',
          'porcentaje_descuento',
          'porcentaje_iva',
          'lote',
          'vencimiento',
          'total',
          'doc_ref',
          'fecha',
          'nit',
          'razon_comercial',
          'condicion',
          'tipo',
          'numero'
        ],
        [
          'Código',
          'Producto',
          'Marca',
          'Cant',
          'Und',
          'Costo',
          'CostoProm.',
          'PrecioLista_1',
          'Utilidad',
          'Rent.%',
          'Margen%',
          'Dcto%',
          'IVA%',
          'Lote',
          'Vencimiento',
          'Total',
          'DocRef',
          'Fecha',
          'NIT',
          'Proveedor',
          'Condición',
          'Tipo',
          'Número'
        ],
        null,
        null,
        false,
        false,
        '',
        true,
        [
          'costo',
          'costo_promedio',
          'precio_lista',
          'utilidad',
          'total'
        ],
        'COP',
        [],
        [
          'total',
          'utilidad'
        ]
      );
      // Aplicar estilos a filas según costo total (índice 7)
      const table = $('#compras').DataTable();

      table.on('draw.dt', function () {
        $('#compras tbody tr').each(function () {
          const celdas = $(this).find('td');
          const rentabilidad = parseFloat(celdas.eq(8).text().replace(/[^\d.-]/g, '')) || 0;

          celdas.eq(8).html(
            `<span class="${rentabilidad < 0 ? 'text-danger' : 'text-success'} fw-bold">
                 ${celdas.eq(8).text()}
            </span>`
          );
        });
      });

      // Para aplicar el formato a la primera página
      table.draw(false);
    }

    // 🔥 Forzar recalculo de tamaño (clave para que "crezca")
    setTimeout(() => {
      $('#compras').DataTable().columns.adjust().draw(false);
    }, 100);



    dissminSwal();

  } catch (error) {
    dissminSwal();
    console.error(error);
    Swal.fire("Error", "Error al obtener la información", "error");
  }
};

$(function () {
  const hoy = new Date();
  const primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
  const ultimoDia = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
  const formatoFecha = (fecha) => fecha.toISOString().split("T")[0];

  $("#fecha_inicial").val(formatoFecha(primerDia));
  $("#fecha_final").val(formatoFecha(ultimoDia));

  $('#proveedor').autocomplete({
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
      $("#id_tercero").val(item.id)
    }
  });

  $('#proveedor').keyup(function () {
    const dato = $.trim($("#proveedor").val());

    if (dato.length == 0) {
      $("#id_tercero").val(0)
      return;
    }

  })

  $("#btn-busqueda").click(function () {
    if ($("#fecha_inicial").val() == '' || $("#fecha_final").val() == '') {
      Swal.fire("Ups", "La fecha inicial y final es obligatoria", "error")
      return;
    }
    consultar();
  })
  dissminSwal();
})