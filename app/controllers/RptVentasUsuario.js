const consultar = async () => {

  showLoadingWithTetrisLogo('Generando reporte', 'ISA Software')

  try {

    const tipo_vista = $('input[name="tipo_vista"]:checked').val();

    const resp = await enviarPeticion({
      link: '../models/RptVentasUsuario.php',
      id_usuario: $("#slcUsuarios").val(),
      fecha_inicial: $("#fecha_inicial").val(),
      fecha_final: $("#fecha_final").val(),
      tipo_vista: tipo_vista
    })

    if (resp.length == 0) {
      dissminSwal()
      Swal.fire("Ups", "No se encontraron resultados", "info")
      return
    }

    const tb = `
      <table class="table table-sm table-striped" id="ventas">
        <thead class="table-primary"></thead>
        <tbody></tbody>
      </table>
    `

    $("#result").html(tb)

    // =========================
    // MOSTRAR TOTALES
    // =========================

    renderTotales(resp, tipo_vista)

    // =========================
    // MOSTRAR VISTA CONSOLIDADA O DETALLADA
    // =========================

    if (tipo_vista === "consolidada") {
      renderVistaConsolidada(resp);
    } else {
      renderVistaDetallada(resp);
    }

    dissminSwal()

  } catch (error) {

    dissminSwal()
    console.error(error)
    Swal.fire("Error", "Error al obtener la información", "error")

  }

}
// =========================
// RENDER TOTALES
// =========================
const renderTotales = (resp, tipo_vista) => {

  let totalVenta = 0;
  let totalCosto = 0;
  let totalUtilidad = 0;

  if (tipo_vista === "consolidada") {
    resp.forEach(item => {
      totalVenta += Number(item.valor_total) || 0;
      totalCosto += Number(item.costo_total) || 0;
      totalUtilidad += Number(item.utilidad) || 0;
    });
  } else {
    resp.forEach(item => {
      totalVenta += Number(item.valor_total) || 0;
      totalCosto += Number(item.costo_unidad * item.cantidad) || 0;
      totalUtilidad += Number(item.utilidad) || 0;
    });
  }

  const margenPromedio = totalVenta > 0 ? ((totalUtilidad / totalVenta) * 100).toFixed(2) : 0;

  const formatoMoneda = (valor) => {
    return new Intl.NumberFormat('es-CO', {
      style: 'currency',
      currency: 'COP',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(valor);
  };

  const cardsHTML = `
    <div class="row mb-4">
      <div class="col-md-3">
        <div class="card ">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="flex-grow-1">
                <h6 class="text-muted fw-normal mb-1">Ventas</h6>
                <h5 class="mb-0 text-primary fw-bold">${formatoMoneda(totalVenta)}</h5>
              </div>
              <div class="text-primary" style="font-size: 2rem;">
                <i class="fa fa-shopping-cart"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="flex-grow-1">
                <h6 class="text-muted fw-normal mb-1">Costo</h6>
                <h5 class="mb-0 text-danger fw-bold">${formatoMoneda(totalCosto)}</h5>
              </div>
              <div class="text-danger" style="font-size: 2rem;">
                <i class="fa fa-credit-card"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="flex-grow-1">
                <h6 class="text-muted fw-normal mb-1">Utilidad</h6>
                <h5 class="mb-0 text-success fw-bold">${formatoMoneda(totalUtilidad)}</h5>
              </div>
              <div class="text-success" style="font-size: 2rem;">
                <i class="fa fa-arrow-trend-up"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="flex-grow-1">
                <h6 class="text-muted fw-normal mb-1">Margen</h6>
                <h5 class="mb-0 text-info fw-bold">${margenPromedio}%</h5>
              </div>
              <div class="text-info" style="font-size: 2rem;">
                <i class="fa fa-chart-pie"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  `;

  $("#result").prepend(cardsHTML);

}
// =========================
// VISTA CONSOLIDADA
// =========================
const renderVistaConsolidada = (resp) => {

  createDataTable(
    'ventas',
    resp,
    ['usuario', 'nombres', 'apellidos', 'desc_bodega', 'valor_total', 'costo_total', 'utilidad', 'margen'],
    ['Usuario', 'Nombres', 'Apellidos', 'Bodega', 'Ventas', 'Costo', 'Utilidad', 'Margen'],
    null,
    null,
    false,
    false,
    '',
    true,
    ['valor_total', 'costo_total', 'utilidad'],
    'COP'
  )

  // =========================
  // DATOS PARA GRAFICA
  // =========================

  const vendedores = resp.map(v =>
    `${v.nombres} ${v.apellidos}`
  )

  const ventas = resp.map(v =>
    Number(v.valor_total)
  )

  // =========================
  // ALTURA DINAMICA CON MINIMA
  // =========================

  let altura = vendedores.length * 45
  if (altura < 150) altura = 150 // altura mínima para 1 registro

  // =========================
  // DESTRUIR GRÁFICO EXISTENTE
  // =========================

  if (window.chartVentas) {
    window.chartVentas.destroy()
  }

  // =========================
  // GRAFICA HORIZONTAL (solo Ventas)
  // =========================

  const options = {

    chart: {
      type: 'bar',
      height: altura
    },

    series: [
      {
        name: 'Ventas',
        data: ventas
      }
    ],

    plotOptions: {
      bar: {
        horizontal: true,
        borderRadius: 4,
        barHeight: vendedores.length === 1 ? "40%" : "60%" // barra más grande si solo hay 1
      }
    },

    xaxis: {
      categories: vendedores
    },

    colors: ['#1f77b4'], // solo un color para ventas

    dataLabels: {
      enabled: false
    },

    tooltip: {
      y: {
        formatter: function (val) {
          return new Intl.NumberFormat('es-CO', {
            style: 'currency',
            currency: 'COP'
          }).format(val)
        }
      }
    }

  }

  $("#grafica-ventas").html("")

  window.chartVentas = new ApexCharts(
    document.querySelector("#grafica-ventas"),
    options
  )

  window.chartVentas.render()

}
// =========================
// VISTA DETALLADA
// =========================
const renderVistaDetallada = (resp) => {

  createDataTable(
    'ventas',
    resp,
    ['numero', 'tipo', 'fecha_documento', 'cliente', 'usuario', 'producto', 'marca', 'cantidad', 'und', 'precio_neto_unidad', 'valor_total', 'costo_unidad', 'utilidad', 'margen'],
    ['# Doc', 'Tipo', 'Fecha', 'Cliente', 'Vendedor', 'Producto', 'Marca', 'Cant', 'Und', 'Precio Unit', 'Total', 'Costo Unit', 'Utilidad', 'Margen %'],
    null,
    null,
    false,
    false,
    '',
    true,
    ['precio_neto_unidad', 'valor_total', 'costo_unidad', 'utilidad'],
    'COP'
  )

  // =========================
  // DESTRUIR GRÁFICO EXISTENTE Y OCULTARLO
  // =========================

  if (window.chartVentas) {
    window.chartVentas.destroy()
  }

  $("#grafica-ventas").html("<p class='text-muted text-center mt-3'>Gráfico no disponible en vista detallada</p>")

}
const listarUsuarios = async () => {
  try {
    const resp = await serviceListarUsuarios();
    $("#slcUsuarios").append(`<option value="0">Todos</option>`);
    resp.data.forEach(usuario => {
      $("#slcUsuarios").append(`<option value="${usuario.id}">${usuario.usuario} - ${usuario.nombre_completo} </option>`)
    });


  } catch (error) {
    console.error(error)
    Swal.fire("Error", "Error al obtener la información", "error")
  }
}
$(async function () {

  fechaActual([
    ['fecha_inicial', 'fecha_final ']
  ]);
  listarUsuarios();
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
  $('#vendedor').autocomplete({
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
      $("#id_vendedor").val(item.id)
    }
  });
  $('#vendedor').keyup(function () {
    const dato = $.trim($("#vendedor").val());

    if (dato.length == 0) {
      $("#id_vendedor").val(0)
      return;
    }

  })

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
  });
  dissminSwal();
})