const link_ = '../models/rptVentasItems.php';

// Inicializar Select2 para bodegas
const inicializarBodegas = async () => {
  try {
    const resp = await bodegasUsuarios();
    if (resp.length == 0) {
      console.warn("No se encontraron bodegas");
      return;
    }
    let options = ``;
    resp.forEach((d) => {
      options += `<option value="${d.id}">${d.bodega} - ${d.descripcion}</option>`;
    });
    $("#id_bodegas").html(options);
    
    // Inicializar Select2
    $("#id_bodegas").select2({
      placeholder: "Seleccione bodegas...",
      allowClear: true,
      width: '100%'
    });
  } catch (e) {
    console.error(e);
  }
};

const listarMarcas = async () => {
  try {
    const resp = await enviarPeticion({
      link: '../models/Marcas.php',
      op: 'listar',
      filtro: ''
    });

    const marcas = Array.isArray(resp.data) ? resp.data : [];
    let options = `<option value="">Todas las marcas</option>`;

    marcas.forEach((item) => {
      options += `<option value="${item.id}">${item.descripcion}</option>`;
    });

    $("#marcas").html(options).select2({
      placeholder: 'Todas las marcas',
      allowClear: true,
      width: '100%'
    });
  } catch (e) {
    console.error(e);
  }
};

// Autocompletable para búsqueda de referencias
const inicializarAutocompletablesReferencias = () => {
  $("#busqueda_referencia").autocomplete({
    source: async (request, response) => {
      try {
        const filtro = $.trim(request.term);
        if (filtro.length < 2) {
          response([]);
          return;
        }

        const resp = await enviarPeticion({
          link: link_,
          op: 'buscar-referencias',
          filtro: filtro
        });
          console.log({resp})
        if (resp && Array.isArray(resp)) {
          response(resp.map(item => ({
            label: `${item.codigo} - ${item.descripcion}`,
            value: item.codigo,
            id: item.id_referencia
          })));
        } else {
          response([]);
        }
      } catch (e) {
        console.error(e);
        response([]);
      }
    },
    minLength: 2,
    select: (event, ui) => {
      $("#busqueda_referencia").val(ui.item.label);
      $("#id_referencia_filtro").val(ui.item.id);
      return false;
    }
  });
};
 
$(async function () {
  await limpiar();
  await inicializarBodegas();
  await listarMarcas();
  inicializarAutocompletablesReferencias();

  $("#btn-busqueda").click(function () {
    let fhIni = $("#fe_fecha_inicial").val();
    let fhFin = $("#fe_fecha_final").val();

    if (!fhIni || !fhFin) {
      Swal.fire("Advertencia", "Debe seleccionar ambas fechas", "warning");
      return;
    }

    // Convertir a objeto Date para comparar
    let dIni = new Date(fhIni);
    let dFin = new Date(fhFin);

    if (dFin < dIni) {
      Swal.fire("Error", "La fecha final no puede ser anterior a la fecha inicial", "error");
      return;
    }

    consultarVentas(fhIni, fhFin)
  });
  $("#btn-limpiar").click(function () {
    limpiar();
  });
  dissminSwal();
});

const limpiarGraficaVentas = () => {
  if (window.chartVentas) {
    window.chartVentas.destroy();
    window.chartVentas = null;
  }
  $("#grafico-ventas").empty();
};

const limpiar = async () => {

  fechaActual([[
    'fe_fecha_inicial',
    'fe_fecha_final'
  ]]);

  $("#id_bodegas").val(null).trigger('change');
  $("#busqueda_referencia").val("");
  $("#id_referencia_filtro").val("0");
  $("#marcas").val("").trigger('change');
  $("#resultado-ventas").html("");
  $("#resumen-ventas").html("");
  limpiarGraficaVentas();
};
const consultarVentas = async (fechaInicio, fechaFin) => {
     showLoadingWithTetrisLogo('Cargando por favor espere...', 'ISA Software')

  try {
    // Obtener filtros de bodegas y referencias
    const bodegas = $("#id_bodegas").val() || [];
    const id_referencia = $("#id_referencia_filtro").val() || "0";
    const id_marca = $("#marcas").val() || "";

    // 🧩 Campos que vienen de la vista v_ventas_general
    const campos = [
      "desc_bodega",
      "tipo",
      "numero",
      "fecha",
      "nit",
      "nombres",
      "razon_comercial",
      "codigo",
      "producto",
      "marca",
      "lote",
      "vencimiento",
      "ean",
      "cantidad",
      "und",
      "can_und",
      "precio_neto_unidad",
      "precio_unitario_unidad",
      "precio_neto_total",
      "porcentaje_iva",
      "valor_iva",
      "porcentaje_descuento",
      "valor_descuento",
      "valor_total",
      "valor_total_con_iva"
    ];

    const encabezados = [
      "Bodega",
      "Tipo",
      "Número",
      "Fecha",
      "NIT",
      "Nombres",
      "Razón Comercial",
      "Código",
      "Producto",
      "Marca",
      "Lote",
      "Vmcto",
      "EAN",
      "Cantidad",
      "Und",
      "Can/Und",
      "PNeto/Und",
      "PUnitario/Und",
      "Total Neto",
      "Pcj.IVA",
      "Vlr.IVA",
      "Pcj.Descuento",
      "Vlr.Descuento",
      "Total",
      "Total c/IVA"
    ];

    // 🧾 Filtros de consulta
    const filtros = {
      op: "s_ventas_detalle",
      fechaInicio,
      fechaFin,
      bodegas: JSON.stringify(bodegas),
      id_referencia: id_referencia,
      id_marca: id_marca
    };

    const data = {
      link: link_,
      ...filtros
    };

    // 🚀 Petición al servidor
    const resp = await enviarPeticion(data);

    if (!resp || resp.length === 0) {
      $("#resultado-ventas").html(ShowAlert("No se encontraron ventas", "warning"));
      $("#resumen-ventas").html("");
      limpiarGraficaVentas();
      dissminSwal();
      return;
    }

    // 🧮 Renderizar tabla
    $("#resultado-ventas").html(`
      <table id="tb_ventas" class="table table-sm table-borderless table-hover text-nowrap table-striped w-100">
        <thead class="table-primary"></thead> 
        <tbody></tbody>
      </table>
    `);

    createDataTable(
      "tb_ventas",
      resp,
      campos,
      encabezados,
      "",
      "",
      false,
      "",
      "",
      true,
      ["precio_neto_unidad", "precio_unitario_unidad", "precio_neto_total", "valor_iva", "valor_descuento", "valor_total", "valor_total_con_iva"]
    );

    // 📊 === RESUMEN GENERAL ===
    const totalVentas = resp.reduce(
      (acc, r) => acc + (parseFloat(r.valor_total_con_iva) || 0),
      0
    );
    const totalProductos = resp.reduce((acc, r) => acc + (parseFloat(r.cantidad) || 0), 0);
    const totalIVA = resp.reduce((acc, r) => acc + (parseFloat(r.valor_iva) || 0), 0);
    const totalDescuentos = resp.reduce((acc, r) => acc + (parseFloat(r.valor_descuento) || 0), 0);
    const totalTransacciones = new Set(resp.map(r => `${r.tipo}-${r.numero}`)).size;
    const totalClientes = new Set(resp.map(r => r.nit)).size;

    $("#resumen-ventas").html(`
      <div class="col-md-2 mb-3">
        <div class="card shadow-sm border-primary text-center p-3 rounded-3">
          <h6>Total Ventas c/IVA</h6>
          <h4 class="fw-bold">$${totalVentas.toLocaleString('es-CO', {minimumFractionDigits: 2})}</h4>
        </div>
      </div>
      <div class="col-md-2 mb-3">
        <div class="card shadow-sm border-success text-center p-3 rounded-3">
          <h6>Total Productos</h6>
          <h4 class="fw-bold">${totalProductos.toLocaleString('es-CO')}</h4>
        </div>
      </div>
      <div class="col-md-2 mb-3">
        <div class="card shadow-sm border-info text-center p-3 rounded-3">
          <h6>Total IVA</h6>
          <h4 class="fw-bold">$${totalIVA.toLocaleString('es-CO', {minimumFractionDigits: 2})}</h4>
        </div>
      </div>
      <div class="col-md-2 mb-3">
        <div class="card shadow-sm border-warning text-center p-3 rounded-3">
          <h6>Descuentos</h6>
          <h4 class="fw-bold">$${totalDescuentos.toLocaleString('es-CO', {minimumFractionDigits: 2})}</h4>
        </div>
      </div>
      <div class="col-md-2 mb-3">
        <div class="card shadow-sm border-danger text-center p-3 rounded-3">
          <h6>Transacciones</h6>
          <h4 class="fw-bold">${totalTransacciones}</h4>
        </div>
      </div>
      <div class="col-md-2 mb-3">
        <div class="card shadow-sm border-secondary text-center p-3 rounded-3">
          <h6>Clientes</h6>
          <h4 class="fw-bold">${totalClientes}</h4>
        </div>
      </div>
    `);

    // 📈 === TOP PRODUCTOS MÁS VENDIDOS ===
    const ventasPorProducto = {};

    resp.forEach(r => {
      const producto = r.producto || "Sin nombre";
      const cantidad = parseFloat(r.cantidad) || 0;

      if (!ventasPorProducto[producto]) ventasPorProducto[producto] = 0;
      ventasPorProducto[producto] += cantidad;
    });

    // Convertir a array y ordenar por cantidad descendente
    const topProductos = Object.entries(ventasPorProducto)
      .sort((a, b) => b[1] - a[1])
      .slice(0, 10); // top 10

    const nombres = topProductos.map(p => p[0]);
    const cantidades = topProductos.map(p => p[1]);

    limpiarGraficaVentas();

    const options = {
      chart: {
        type: "bar",
        height: 400,
        toolbar: { show: true }
      },
      series: [
        {
          name: "Cantidad vendida",
          data: cantidades
        }
      ],
      xaxis: {
        categories: nombres,
        title: { text: "Producto" },
        labels: {
          style: { fontSize: "11px" },
          rotate: -15
        }
      },
      yaxis: {
        title: { text: "Unidades vendidas" },
        labels: {
          formatter: val => val.toLocaleString("es-CO")
        }
      },
      tooltip: {
        y: {
          formatter: val => val.toLocaleString("es-CO") + " unidades"
        }
      },
      colors: ["#198754"],
      plotOptions: {
        bar: {
          horizontal: true,
          borderRadius: 5
        }
      },
      dataLabels: {
        enabled: true,
        formatter: val => val.toLocaleString("es-CO")
      }
    };

    window.chartVentas = new ApexCharts(document.querySelector("#grafico-ventas"), options);
    window.chartVentas.render();

    dissminSwal();

  } catch (e) {
    console.error("Error en consultar Ventas:", e);
    $("#resultado-ventas").html(ShowAlert("Error interno de la aplicación", "error"));
    $("#resumen-ventas").html("");
    limpiarGraficaVentas();
    dissminSwal();
  }
};
