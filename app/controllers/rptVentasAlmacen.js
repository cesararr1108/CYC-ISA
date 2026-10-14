const link_ = '../models/rptVentasAlmacen.php';

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

    showLoadingWithTetrisLogo('Consultando ventas', 'ISA Software');

    try {

        // Destruir gráfica anterior si existe
        if (window.chartVentas) {
            window.chartVentas.destroy();
            window.chartVentas = null;
        }

        $("#grafico-ventas").empty();

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

        const campos = [
            "id_bodega",
            "descripcion",
            "venta",
            "venta_iva",
            "vlr_iva",
            "costo",
            "utilidad"
        ];

        const encabezados = [
            "Bodega",
            "Descripción",
            "Venta",
            "Venta IVA",
            "Valor IVA",
            "Costo",
            "Utilidad"
        ];

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
            ['venta', 'venta_iva', 'vlr_iva', 'costo', 'utilidad']
        );

        const totalVentas = resp.reduce((a, r) => a + (+r.venta || 0), 0);
        const totalVentaIVA = resp.reduce((a, r) => a + (+r.venta_iva || 0), 0);
        const totalValorIVA = resp.reduce((a, r) => a + (+r.vlr_iva || 0), 0);
        const totalCosto = resp.reduce((a, r) => a + (+r.costo || 0), 0);
        const totalUtilidad = resp.reduce((a, r) => a + (+r.utilidad || 0), 0);

        const margen = totalVentas > 0
            ? ((totalUtilidad / totalVentas) * 100).toFixed(2)
            : 0;

        $("#resumen-ventas").html(`
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm border-primary text-center p-3">
                    <h6>Total Ventas</h6>
                    <h4>$${totalVentas.toLocaleString('es-CO')}</h4>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="card shadow-sm border-info text-center p-3">
                    <h6>Total Venta IVA</h6>
                    <h4>$${totalVentaIVA.toLocaleString('es-CO')}</h4>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="card shadow-sm border-info text-center p-3">
                    <h6>Total Valor IVA</h6>
                    <h4>$${totalValorIVA.toLocaleString('es-CO')}</h4>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="card shadow-sm border-danger text-center p-3">
                    <h6>Total Costo</h6>
                    <h4>$${totalCosto.toLocaleString('es-CO')}</h4>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="card shadow-sm border-success text-center p-3">
                    <h6>Total Utilidad</h6>
                    <h4>$${totalUtilidad.toLocaleString('es-CO')}</h4>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="card shadow-sm border-info text-center p-3">
                    <h6>Margen %</h6>
                    <h4>${margen}%</h4>
                </div>
            </div>
        `);

        const bodegas = resp.map(r => r.descripcion);
        const ventas = resp.map(r => +r.venta || 0);
        const utilidades = resp.map(r => +r.utilidad || 0);

        const options = {
            chart: {
                type: 'bar',
                height: 420
            },
            plotOptions: {
                bar: {
                    horizontal: true
                }
            },
            series: [
                {
                    name: "Ventas",
                    data: ventas
                },
                {
                    name: "Utilidad",
                    data: utilidades
                }
            ],
            xaxis: {
                categories: bodegas,
                labels: {
                    style: {
                        fontSize: '10px',
                        fontFamily: 'Arial, sans-serif'
                    }
                }
            },
            yaxis: {
                labels: {
                    formatter: val => val.toLocaleString('es-CO')
                }
            },
            tooltip: {
                y: {
                    formatter: val => "$ " + val.toLocaleString('es-CO')
                }
            },
            colors: ['#0d6efd', '#28a745']
        };

        window.chartVentas = new ApexCharts(
            document.querySelector("#grafico-ventas"),
            options
        );

        await window.chartVentas.render();

        dissminSwal();

    } catch (e) {

        console.error(e);

        if (window.chartVentas) {
            window.chartVentas.destroy();
            window.chartVentas = null;
        }

        $("#grafico-ventas").empty();

        $("#resultado-ventas").html(
            ShowAlert("Error interno de la aplicación", "error")
        );

        $("#resumen-ventas").empty();

        dissminSwal();
    }
};
