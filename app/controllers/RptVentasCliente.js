const consultar = async () => {
  showLoadingWithTetrisLogo('Consultando información', 'ISA Software')
  try {
    const resp = await enviarPeticion({
      link: '../models/RptVentasCliente.php',
      id_tercero: $("#id_tercero").val(),
      fecha_inicial: $("#fecha_inicial").val(),
      fecha_final: $("#fecha_final").val(),
      op: 'consultar'
    });

    if (resp.length == 0) {
      dissminSwal()
      Swal.fire("Ups", "No se encontraron resultados", "info")
      return;
    }
    const tb = ` <table id="ventas" class="table table-sm table-borderless table-hover table-striped w-100">
                    <thead class="table-primary"></thead> 
                    <tbody></tbody>
                  </table>`
    $("#result").html(tb);

    createDataTable('ventas', resp,
      ['fecha', 'nit', 'razon_comercial', 'cliente', 'condicion', 'tipo', 'numero', 'valor_bruto', 'descuento', 'iva', 'total'],
      ['Fecha', 'Nit', 'Razón comercial', 'Cliente', 'Condicion de pago', 'Tipo', 'Numero', 'Vlr bruto', 'Vlr Dcto', 'Vlr Iva', 'Vlr Total'],
      null, null, false, false, '', true,
      ['total', 'iva', 'descuento', 'valor_bruto'], 'COP',
      [],
      ['total'])

    dissminSwal();

  } catch (error) {
    dissminSwal();
    console.error(error)
    Swal.fire("Error", "Error al obtener la información", "error")
  }
}
$(function () {
  const hoy = new Date();
  const primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
  const ultimoDia = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
  const formatoFecha = (fecha) => fecha.toISOString().split("T")[0];

  $("#fecha_inicial").val(formatoFecha(primerDia));
  $("#fecha_final").val(formatoFecha(ultimoDia));

  $('#cliente').autocomplete({
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

  $('#cliente').keyup(function () {
    const dato = $.trim($("#cliente").val());

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
