const consultar = async () => {
  showLoadingWithTetrisLogo('Cargando por favor espere...', 'ISA Software')
  try {
    const resp = await enviarPeticion({
      link: '../models/RptCxC.php',
      id_tercero: $("#id_tercero").val(),
      fecha_inicial: $("#fecha_inicial").val(),
      fecha_final: $("#fecha_final").val(),
      op: 'consultar'
    });

    console.log({ resp });

    if (!resp || resp.length === 0) {
      dissminSwal();
      $("#result").html(`<div class="alert alert-warning"><i class="fa-solid fa-triangle-exclamation"></i> No se encontraron resultados!</div>`);
      return;
    }

    const tb = `
      <table id="cxc" class="table table-sm table-borderless table-hover table-striped w-100">
        <thead class="table-primary"></thead>
        <tbody></tbody>
      </table>`;
    $("#result").html(tb);



    // Crear la DataTable con los campos de la vista
    createDataTable(
      'cxc',
      resp,
      ['fecha', 'nit', 'razon_comercial', 'tratamiento', 'tipo', 'numero', 'documento', 'notas', 'valor_total', 'valor_aplicado', 'saldo'],
      ['Fecha', 'NIT', 'Razón Comercial', 'Cliente', 'Tipo', 'Número', 'Documento', 'Notas', 'Valor Total', 'Valor Aplicado', 'Saldo'],
      null, // sin filtros personalizados
      null, // sin orden personalizado
      false, // paginación
      false, // exportación
      '', // título
      true, // sumar columnas
      ['valor_total', 'valor_aplicado', 'saldo'], // columnas a sumar
      'COP', // formato moneda
      [], // sin columnas ocultas
      ['saldo'] // columna resaltada (saldo pendiente)
    );

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
});
