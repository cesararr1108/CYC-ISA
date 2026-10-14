const link_ = '../models/rptVentasHoras.php';

$(async function () {
    await limpiar();
    await cargarBodegas();
    $("#btn-busqueda").click(async function () {

        const fhIni = $("#fe_fecha_inicial").val();
        const fhFin = $("#fe_fecha_final").val();
        const bodegaSeleccionada = $("#slcBodegas").val();


        if (!fhIni || !fhFin) {
            Swal.fire("Advertencia", "Debe seleccionar ambas fechas", "warning");
            return;
        }

        if (new Date(fhFin) < new Date(fhIni)) {
            Swal.fire("Error", "La fecha final no puede ser anterior a la fecha inicial", "error");
            return;
        }

        await consultarVentas(fhIni, fhFin, bodegaSeleccionada);

    });

    $("#btn-limpiar").click(limpiar);

    dissminSwal();

});


const limpiar = async () => {
    fechaActual([['fe_fecha_inicial', 'fe_fecha_final']]);

    $("#resultado-ventas").empty();
    $("#resumen-ventas").empty();
    $("#grafico-ventas").empty();

};

const cargarBodegas = async () => {
    const resp = await enviarPeticion({
        link: '../shared/servicios/servicios.php',
        op: 'bodegas'
    })
    if (resp.length > 0) {
        let p = '<option value="0">Todas las bodegas</option>';
        resp.forEach(element => {
            p += `<option value="${element.almacen}" data-element='${JSON.stringify(element)}'>${element.almacen} - ${element.descripcion}</option>`
        });
        $("#slcBodegas").html(p).select2();
    }
}


const consultarVentas = async (fechaInicio, fechaFin, bodegaSeleccionada = 0) => {

    showLoadingWithTetrisLogo('Consultando ventas por horas', 'ISA Software')

    try {
        let resp = await enviarPeticion({
            link: link_,
            op: "s_ventas_bodega",
            fechaInicio,
            fechaFin,
            bodegaSeleccionada
        });

        console.log("Respuesta del servidor:", resp);

        if (!resp || resp.length === 0) {
            $("#resultado-ventas").html(
                ShowAlert("No se encontraron registros", "warning")
            );
            $("#resumen-ventas").empty();
            $("#grafico-ventas").empty();
            dissminSwal();
            return;
        }

        // Procesar datos de horas
        const campos = ["hora", "total_venta"];
        const encabezados = ["Hora", "Venta Total"];

        $("#resultado-ventas").html(`
      <table id="tb_ventas" class="table table-sm table-borderless table-hover table-striped w-100">
        <thead class="table-primary"></thead>
        <tbody></tbody>
      </table>
    `);

        createDataTable(
            'tb_ventas',
            resp,
            campos,
            encabezados,
            '',
            '',
            false,
            '',
            '',
            true,
            ['total_venta']
        );

        // Calcular estadísticas
        const totalVentas = resp.reduce((a, r) => a + (+r.total_venta || 0), 0);
        const ventasPromedio = (totalVentas / resp.length).toFixed(2);
        const ventasMaxima = Math.max(...resp.map(r => +r.total_venta || 0));
        const horaMaxVenta = resp.find(r => +r.total_venta === ventasMaxima);

        // Mostrar resumen
        $("#resumen-ventas").html(`
      <div class="col-md-3 mb-3">
        <div class="card shadow-sm border-primary text-center p-3">
          <h6>Total Ventas</h6>
          <h4>$${totalVentas.toLocaleString('es-CO')}</h4>
        </div>
      </div>

      <div class="col-md-3 mb-3">
        <div class="card shadow-sm border-info text-center p-3">
          <h6>Venta Promedio/Hora</h6>
          <h4>$${(+ventasPromedio).toLocaleString('es-CO')}</h4>
        </div>
      </div>

      <div class="col-md-3 mb-3">
        <div class="card shadow-sm border-success text-center p-3">
          <h6>Venta Máxima</h6>
          <h4>$${ventasMaxima.toLocaleString('es-CO')}</h4>
        </div>
      </div>

      <div class="col-md-3 mb-3">
        <div class="card shadow-sm border-warning text-center p-3">
          <h6>Hora Pico</h6>
          <h4>${horaMaxVenta ? horaMaxVenta.hora : 'N/A'}</h4>
        </div>
      </div>
    `);

        // Preparar datos para el gráfico
        const horas = resp.map(r => r.hora);
        const ventas = resp.map(r => +r.total_venta || 0);

        if (window.chartVentas) window.chartVentas.destroy();

        // Gráfico de línea con área para mostrar picos de ventas
        const options = {
            chart: {
                type: 'area',
                height: 420,
                zoom: {
                    enabled: true
                },
                toolbar: {
                    show: true
                }
            },

            stroke: {
                curve: 'smooth',
                width: 2.5
            },

            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.7,
                    opacityTo: 0.1,
                    stops: [0, 100]
                }
            },

            series: [
                {
                    name: "Ventas por Hora",
                    data: ventas
                }
            ],

            xaxis: {
                categories: horas,
                labels: {
                    style: {
                        fontSize: '11px',
                        fontFamily: 'Arial, sans-serif'
                    }
                }
            },

            yaxis: {
                labels: {
                    formatter: val => "$ " + val.toLocaleString('es-CO')
                }
            },

            tooltip: {
                y: {
                    formatter: val => "$ " + val.toLocaleString('es-CO')
                }
            },

            colors: ['#667eea'],

            dataLabels: {
                enabled: false
            },

            grid: {
                borderColor: '#f1f1f1'
            }
        };

        window.chartVentas = new ApexCharts(
            document.querySelector("#grafico-ventas"),
            options
        );

        window.chartVentas.render();

        dissminSwal();

    } catch (e) {
        console.error(e);

        $("#resultado-ventas").html(
            ShowAlert("Error interno de la aplicación", "error")
        );

        $("#resumen-ventas").empty();
        $("#grafico-ventas").empty();

        dissminSwal();
    }
};
