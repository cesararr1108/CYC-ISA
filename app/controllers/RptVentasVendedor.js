const consultar = async () => {

  showLoadingWithTetrisLogo('Generando reporte', 'ISA Software')

  try {

    const tipo_vista = $('input[name="tipo_vista"]:checked').val();

    const resp = await enviarPeticion({
      link: '../models/RptVentasVendedor.php',
      op: 'consultar',
      id_vendedor: $("#slcVendedores").val(),
      id_zona: $("#slcZonas").val(),
      fecha_inicial: $("#fecha_inicial").val(),
      fecha_final: $("#fecha_final").val(),
    })

    const filas = normalizarFilas(resp);

    if (filas.length == 0) {
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

    renderTotales(filas)

    // =========================
    // MOSTRAR VISTA CONSOLIDADA O DETALLADA
    // =========================
    // MOSTRAR VISTA CONSOLIDADA O DETALLADA
    // =========================

    if (tipo_vista === "consolidada") {
      renderVistaConsolidada(filas);
    } else {
      renderVistaDetallada(filas);
    }

    dissminSwal()

  } catch (error) {

    dissminSwal()
    console.error(error)
    Swal.fire("Error", "Error al obtener la información", "error")

  }

}

const toNumber = (value) => Number(value) || 0;

const formatCurrency = (valor) => {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(valor || 0);
};

const normalizarFilas = (resp) => {
  return (resp || []).map((item) => ({
    ...item,
    vendedor_principal: `${item.nit_vendedor || 0} - ${item.vendedor || 'S/A'}`,
    vendedor_secundario: `${item.nit_vendedor2 || 0} - ${item.vendedor2 || 'S/A'}`,
  }));
};

// =========================
// RENDER TOTALES
// =========================

const renderTotales = (resp) => {

  let totalImpactos = 0;
  let totalVentasNetas = 0;
  let totalDevolucionesNetas = 0;
  let totalVentaNetaReal = 0;
  let totalIvaNeto = 0;

  resp.forEach(item => {
    totalImpactos += toNumber(item.impactos);
    totalVentasNetas += toNumber(item.ventas_netas);
    totalDevolucionesNetas += toNumber(item.devoluciones_netas);
    totalVentaNetaReal += toNumber(item.venta_neta_real);
    totalIvaNeto += toNumber(item.iva_neto);
  });

  const cardsHTML = `
    <div class="row mb-4">
      <div class="col-lg col-md-4 col-sm-6 mb-2">
        <div class="card ">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="flex-grow-1">
                <h6 class="text-muted fw-normal mb-1">Impactos</h6>
                <h5 class="mb-0 text-primary fw-bold">${totalImpactos.toLocaleString('es-CO')}</h5>
              </div>
              <div class="text-primary" style="font-size: 2rem;">
                <i class="fa fa-bullseye"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg col-md-4 col-sm-6 mb-2">
        <div class="card">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="flex-grow-1">
                <h6 class="text-muted fw-normal mb-1">Ventas Netas</h6>
                <h5 class="mb-0 text-success fw-bold">${formatCurrency(totalVentasNetas)}</h5>
              </div>
              <div class="text-success" style="font-size: 2rem;">
                <i class="fa fa-arrow-trend-up"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg col-md-4 col-sm-6 mb-2">
        <div class="card">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="flex-grow-1">
                <h6 class="text-muted fw-normal mb-1">Devoluciones Netas</h6>
                <h5 class="mb-0 text-danger fw-bold">${formatCurrency(totalDevolucionesNetas)}</h5>
              </div>
              <div class="text-danger" style="font-size: 2rem;">
                <i class="fa fa-rotate-left"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg col-md-4 col-sm-6 mb-2">
        <div class="card">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="flex-grow-1">
                <h6 class="text-muted fw-normal mb-1">Venta Neta Real</h6>
                <h5 class="mb-0 text-info fw-bold">${formatCurrency(totalVentaNetaReal)}</h5>
              </div>
              <div class="text-info" style="font-size: 2rem;">
                <i class="fa fa-chart-line"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg col-md-4 col-sm-6 mb-2">
        <div class="card">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="flex-grow-1">
                <h6 class="text-muted fw-normal mb-1">IVA Neto</h6>
                <h5 class="mb-0 text-warning fw-bold">${formatCurrency(totalIvaNeto)}</h5>
              </div>
              <div class="text-warning" style="font-size: 2rem;">
                <i class="fa fa-receipt"></i>
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
    ['codigo_zona', 'zona', 'vendedor_principal', 'vendedor_secundario', 'impactos', 'venta_neta_real'],
    ['Cod Zona', 'Zona', 'Vendedor principal', 'Vendedor secundario', 'Impactos', 'Venta neta real'],
    null,
    null,
    false,
    false,
    '',
    true,
    ['venta_neta_real'],
    'COP'
  )

  if (window.chartVentas) {
    window.chartVentas.destroy()
    window.chartVentas = null
  }

  $("#grafica-ventas").remove()

}

// =========================
// VISTA DETALLADA
// =========================

const renderVistaDetallada = (resp) => {

  createDataTable(
    'ventas',
    resp,
    [
      'codigo_zona',
      'zona',
      'vendedor_principal',
      'vendedor_secundario',
      'impactos',
      'ventas_netas',
      'devoluciones_netas',
      'ventas_iva',
      'devoluciones_iva',
      'venta_neta_real',
      'iva_neto'
    ],
    [
      'Cod Zona',
      'Zona',
      'Vendedor principal',
      'Vendedor secundario',
      'Impactos',
      'Ventas netas',
      'Devoluciones netas',
      'Ventas + IVA',
      'Devoluciones + IVA',
      'Venta neta real',
      'IVA neto'
    ],
    null,
    null,
    false,
    false,
    '',
    true,
    ['ventas_netas', 'devoluciones_netas', 'ventas_iva', 'devoluciones_iva', 'venta_neta_real', 'iva_neto'],
    'COP'
  )

  if (window.chartVentas) {
    window.chartVentas.destroy()
    window.chartVentas = null
  }

  $("#grafica-ventas").remove()

}


const listarVendedores = async () => {
  try {
  const resp = await enviarPeticion({
      link: '../models/RptVentasVendedor.php',
      op: 'consultar_vendedores'
    });
    $("#slcVendedores").append(`<option value="0">Todos</option>`);
    resp.data.forEach(usuario => {
      $("#slcVendedores").append(`<option value="${usuario.id}">${usuario.nit} - ${usuario.razon_comercial} </option>`)
    });


  } catch (error) {
    console.error(error)
    Swal.fire("Error", "Error al obtener la información", "error")
  }
}

const listarZonas = async () => {
  try {
    const resp = await enviarPeticion({
      link: '../models/RptVentasVendedor.php',
      op: 'consultar_zonas'
    });
    console.log(resp)
    $("#slcZonas").append(`<option value="0">Todos</option>`);
    resp.forEach(zona => {
      $("#slcZonas").append(`<option value="${zona.id}">${zona.codigo_zona} - ${zona.descripcion}</option>`)
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
  listarVendedores();
  listarZonas();

  $("#btn-busqueda").click(function () {
    if ($("#fecha_inicial").val() == '' || $("#fecha_final").val() == '') {
      Swal.fire("Ups", "La fecha inicial y final es obligatoria", "error")
      return;
    }
    consultar();
  });
  dissminSwal();
})