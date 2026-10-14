var creditoChart = null;
var ventasChart = null;
$(async function () {
  await tiposTerceros();
  await zonasVentas();
  await tipoIdentificacion();
  await cargaAnios('SAno');
  $('#txtBusquedaTercero').autocomplete({
    source: async function (request, response) {
      const data = {
        link: '../models/Terceros.php',
        filtro: $.trim(request.term),
        tipo_tercero: $("#id_tipo_tercero").val(),
        estado: '',
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
      item = ui.item;
      llenarDatos(item);
    }
  });
  $('#Stipo').on('change', async () => {
    const id = $('#tId').val();

    if (!id) return;

    try {
      await carteraTercero(id);
    } catch (error) {
      console.error('Error al actualizar cartera del tercero:', error);
    }
  });

  $("#btn-nuevo").click(async () => {
    await limpiarDatos();
  });


  dissminSwal();
})
/*
 @ Tipos de terceros
*/
const tiposTerceros = async () => {
  try {
    const data = {
      link: '../models/tiposTerceros.php',
      filtro: '',
      op: 'listar'
    };
    var resp = await enviarPeticion(data);

    let option = ``;
    resp.data.forEach(function (d) {
      option += `<option value="${d.id}">${d.descripcion.toUpperCase()}</option>`;
    });
    $("#id_tipo_tercero").html(option);
  } catch (e) {
    console.log(e);
  }
}
/*
 @ Tipo de identificacion del tercero
*/
const tipoIdentificacion = async () => {
  try {
    const data = {
      link: '../models/tipoIdentificaciones.php',
      filtro: '',
      op: 'listar'
    };

    var resp = await enviarPeticion(data);
    let option = `<option value="">Seleccionar</option>`;
    resp.data.forEach(function (d) {
      option += `<option value="${d.id}">${d.tipo_identificacion.toUpperCase()} - ${d.descripcion.toUpperCase()}</option>`;
    });
    $("#id_tipo_identificacion").html(option);
  } catch (e) {
    console.log(e);
  }
}
/*
 @ LimpiarDatos
*/
const limpiarDatos = async () => {

  const inputs = [
    '#tNit', '#tRazon', '#tNombres', '#tDireccion', '#tTelefono',
    '#id_zona_ventas', '#Dígito', '#id_tipo_identificacion',
    '#tId', '#txtBusquedaTercero'
  ];

  inputs.forEach(id => $(id).val(''));

  const textos = ['#tCondicion', '#tCupo', '#tLista'];
  textos.forEach(id => $(id).text(''));

  ['#CarteraChart', '#VentasChart'].forEach(id => $(id).empty());

  if (creditoChart) {
    creditoChart.destroy();
    creditoChart = null;
  }

  if (ventasChart) {
    ventasChart.destroy();
    ventasChart = null;
  }

  $('#tabs button[data-bs-target="#dvTercero"]').tab('show');
  $('#txtBusquedaTercero').focus();
};

/*
 @ llenarDatos
*/
const llenarDatos = async (data) => {
  $("#tNit").val(data.nit);
  $("#tRazon").val(data.razon_comercial);
  $("#tNombres").val(data.tratamiento);
  $("#tDireccion").val(data.direccion);
  $("#tTelefono").val(data.telefono);
  $("#id_zona_ventas").val(data.id_zona_ventas);
  $("#Dígito").val(data.Dígito);
  $("#id_tipo_identificacion").val(data.id_tipo_identificacion);
  $("#tCondicion").text(data.des_condicion);
  $("#tCupo").text(formatNum(data.cupo_credito));
  $("#tLista").text(data.lista_precios);
  $("#tId").val(data.id);
  const disponible = parseFloat(data.cupo_credito) - parseFloat(data.cxc);
  const comprometido = parseFloat(data.cxc);
  renderCreditoChart('CarteraChart', comprometido, disponible);

  await obtenerVentas(data.id);
  await carteraTercero(data.id);
}

/*
 @ Cartera
*/
const carteraTercero = async (id_tercero) => {
  try {
    showLoadingWithTetrisLogo('Cargando información', 'ISA Software')
    const data = {
      link: '../models/erm.php',
      op: 's_documentos',
      id_tercero,
      estado: $.trim($("#Stipo").val()),
      anio: $.trim($("#sAnio").val())
    };

    var resp = await enviarPeticion(data);
    if (resp.length == 0) {
      dissminSwal()
      $("#Result").html(`<div class="alert alert-danger" role="alert">
                            <i class="fa-solid fa-bell fa-shake"></i> Sin resultados para las condiciones seleccionadas.
                          </div>`);
      return;
    }
    const tb = ` <table id="cartera-detalle" class="table table-sm table-borderless table-hover table-striped w-100">
                    <thead class="table-primary"></thead> 
                    <tbody></tbody>
                  </table>`
    $("#Result").html(tb);

    createDataTable(
      'cartera-detalle',
      resp,
      ['tipo', 'numero','documento', 'fecha', 'vencimiento', 'dias', 'total', 'total_aplicado', 'total_vencida', 'total_sin_vencer', 'c_1_30', 'c_31_60', 'c_61_90', 'c_91_120'],
      ['Tipo', 'Numero','Doc.Referencia', 'Fecha', 'Vencimiento', 'Dias', 'Total', 'Aplicado', 'Vencido', 'Sin Vencer', '1_30', '31_60', '61_90', '90_120'],
      null,
      null,
      true,
      `
        <button class="btn btn-sm btn-outline-primary other-btn" title="Imprimir">
          <i class="fa-solid fa-print"></i>
        </button>
      `,
      imprimir,
      true,
      ['total_vencida', 'total_sin_vencer', 'total', 'total_aplicado', 'c_1_30', 'c_31_60', 'c_61_90', 'c_91_120'],
      'COP',
      [],
      []
    );

    dissminSwal();
  } catch (e) {
    console.log(e);
    dissminSwal();
  }
}

const imprimir = (data) => {
  ImprimirDocumento(data.sw, data.id);
}
/*
 @ obtenerVentas
*/

const obtenerVentas = async (id_tercero) => {
  try {
    const data = {
      link: '../models/erm.php',
      id_tercero,
      op: 's_ventas'
    };

    var resp = await enviarPeticion(data);
    console.log(resp);
    await cargarVentasChart(resp);
  } catch (e) {
    console.log(e);
  }

};


/*
 @ cargarVentasChart
*/

const cargarVentasChart = async (data) => {
  try {
    const meses = [];
    const ventas = [];

    data.forEach(item => {
      meses.push(`${nombreMes(item.mes)} ${item.ano}`);
      ventas.push(Number(item.valor));
    });

    renderVentasChart('VentasChart', meses, ventas);

  } catch (error) {
    console.error('Error cargando ventasChart:', error);
  }
};


/*
 @ nombreMes
*/

const nombreMes = (mes) => {
  const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
  return meses[mes - 1] || '';
};

/*
 @ Credito Char
*/


function renderCreditoChart(containerId, comprometido, disponible) {

  // Evitar valores negativos en el donut
  comprometido = Number(comprometido) || 0;
  disponible = Number(disponible) || 0;

  // Si el disponible es negativo, significa saldo pendiente.
  // Para el gráfico de crédito lo dejamos en 0.
  if (disponible < 0) {
    disponible = 0;
  }

  var series = [comprometido, disponible];

  console.log(
    'Renderizando gráfico de crédito con valores:',
    comprometido,
    disponible
  );

  // Si ya existe el chart → solo se actualiza
  if (creditoChart) {
    creditoChart.updateSeries(series);
    return;
  }

  var options = {
    chart: {
      type: 'donut',
      height: 300
    },
    series: series,
    labels: ['Comprometido', 'Disponible'],
    colors: ['#e74c3c', '#2ecc71'],
    legend: {
      position: 'bottom'
    },
    tooltip: {
      y: {
        formatter: function (val) {
          return val.toLocaleString();
        }
      }
    },
    responsive: [{
      breakpoint: 480,
      options: {
        chart: {
          height: 250
        }
      }
    }]
  };

  creditoChart = new ApexCharts(
    document.querySelector('#' + containerId),
    options
  );

  creditoChart.render();
}
/*
  @ Ventas Char
 */
function renderVentasChart(containerId, meses, ventas) {

  // Si ya existe el chart → solo se actualiza
  if (ventasChart) {
    ventasChart.updateOptions({
      xaxis: {
        categories: meses
      }
    });
    ventasChart.updateSeries([{
      name: 'Ventas',
      data: ventas
    }]);
    return;
  }

  var options = {
    chart: {
      type: 'line',
      height: 300,
      toolbar: {
        show: false
      },
      zoom: {
        enabled: false
      }
    },
    series: [{
      name: 'Ventas',
      data: ventas
    }],
    stroke: {
      curve: 'smooth', // 🔥 la “onda”
      width: 3
    },
    colors: ['#4f46e5'],
    dataLabels: {
      enabled: false
    },
    markers: {
      size: 5,
      colors: ['#4f46e5'],
      strokeWidth: 2,
      hover: {
        size: 7
      }
    },
    xaxis: {
      categories: meses,
      labels: {
        style: {
          fontSize: '12px'
        }
      }
    },
    yaxis: {
      labels: {
        formatter: function (val) {
          return '$' + val.toLocaleString();
        }
      }
    },
    tooltip: {
      y: {
        formatter: function (val) {
          return '$' + val.toLocaleString();
        }
      }
    },
    grid: {
      borderColor: '#e5e7eb',
      strokeDashArray: 4
    }
  };

  ventasChart = new ApexCharts(
    document.querySelector('#' + containerId),
    options
  );

  ventasChart.render();
}

/*
 @ zonasVentas
*/
const zonasVentas = async () => {
  try {
    const data = {
      link: '../models/zonasVentas.php',
      filtro: '',
      op: 'listar'
    };
    var resp = await enviarPeticion(data);
    let option = ``;
    resp.data.forEach(function (d) {
      option += `<option value="${d.id}">${d.codigo_zona} - ${d.descripcion.toUpperCase()}</option>`;
    });
    $("#id_zona_ventas").html(option);
  } catch (e) {
    console.log(e);
  }
}
/*
 @ cargaAnios
*/
const cargaAnios = async (ob) => {
  var anio_actual = new Date().getFullYear();
  var option = '';
  for (var i = anio_actual; i >= 2008; i--) {
    if (anio_actual == i) {
      option += "<option value='" + i + "' selected='selected'>" + i + "</option>";
    } else {
      option += '<option value="' + i + '">' + i + '</option>';
    }
  }
  $("#" + ob).html(option);
}
/*
 @ cargaMeses
*/
const cargaMeses = async (ob) => {
  var meses = new Array('', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
  var mes_actual = new Date().getMonth() + 1;
  var option = '';
  for (var i = 1; i <= 12; i++) {
    var dia = 0;
    if (i.toString().length == 1) {
      dia = '0' + i;
    } else {
      dia = i;
    }
    if (mes_actual == i) {
      option += "<option value='" + dia + "' selected='selected'>" + dia + ' - ' + meses[i] + " (Actual)</option>";
    } else {

      option += '<option value="' + dia + '">' + dia + ' - ' + meses[i] + '</option>';
    }
  }
  $("#" + ob).html(option);
}
