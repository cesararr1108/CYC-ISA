const listarAlmacenes = async () => {
    try {
        const resp = await bodegasUsuarios();
        let options = `<option value="0">Todas las bodegas</option>`;
        // ordenar el arrya por bodega 
        resp.sort((a, b) => a.bodega.localeCompare(b.bodega));
        resp.forEach((d) => {
            options += `<option value="${d.id}">${d.bodega} - ${d.descripcion}</option>`;
        });
        $("#bodegas").html(options);
    } catch (e) {
        console.error(e)
        Swal.fire("Error", "Error al cargar las bodegas", "error");
    }
}
const consultarRentabilidad = async (fechaInicio, fechaFin) => {
    showLoadingWithTetrisLogo('Cargando información', 'ISA Software')
    try {
        const data = {
            link: '../models/RptRentabilidadItem.php',
            op: 'consultar',
            fecha_inicio: fechaInicio,
            fecha_fin: fechaFin,
            id_bodega: $("#bodegas").val()
        };

        const resp = await enviarPeticion(data);
        console.log({ resp })
        dissminSwal();

        if (resp.error) {
            Swal.fire("Error", resp.mensaje, "error");
            return;
        }
        if (resp.data.length == 0) {
            $("#resultado").html(ShowAlert("No se encontraron resultados", "warning"));
            return;
        }

        // Preparar datos para DataTable
        mostrarTablaRentabilidad(resp.data, fechaInicio, fechaFin);

    } catch (e) {
        console.error(e);
        dissminSwal();
        Swal.fire("Error", "Error interno de la aplicación", "error");
    }
}
const mostrarTablaRentabilidad = (data, fechaInicio, fechaFin) => {

    const dataNames = [
        'codigo',
        'producto',
        'id_bodega',
        'venta',
        'costo',
        'utilidad',
        'margen',
        'markup',
        'venta_pp',
        'utilidad_pp',
        'margen_pp',
        'markup_pp'
    ];

    const columnNames = [
        'Código',
        'Descripción',
        'Bodega',
        'Venta',
        'Costo',
        'Utilidad',
        'Margen%',
        'Markup%',
        'Venta_PP',
        'Utilidad_PP',
        'Margen_PP%',
        'Markup_PP%'
    ];

    const currencyColumns = [
        'venta',
        'costo',
        'utilidad',
        'venta_pp',
        'utilidad_pp'
    ];

    const sumColumns = [
        'venta',
        'costo',
        'utilidad',
        'venta_pp',
        'utilidad_pp'
    ];

    const totalVenta = data.reduce(
        (a, b) => a + parseFloat(b.venta || 0),
        0
    );

    const totalCosto = data.reduce(
        (a, b) => a + parseFloat(b.costo || 0),
        0
    );

    const totalUtilidad = data.reduce(
        (a, b) => a + parseFloat(b.utilidad || 0),
        0
    );

    const totalVentaPP = data.reduce(
        (a, b) => a + parseFloat(b.venta_pp || 0),
        0
    );

    const totalUtilidadPP = data.reduce(
        (a, b) => a + parseFloat(b.utilidad_pp || 0),
        0
    );

    const margenGlobal =
        totalVenta > 0
            ? (totalUtilidad / totalVenta) * 100
            : 0;

    const markupGlobal =
        totalCosto > 0
            ? (totalUtilidad / totalCosto) * 100
            : 0;

    const margenPPGlobal =
        totalVentaPP > 0
            ? (totalUtilidadPP / totalVentaPP) * 100
            : 0;

    const markupPPGlobal =
        totalCosto > 0
            ? (totalUtilidadPP / totalCosto) * 100
            : 0;

    let htmlResultado = `
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body d-flex flex-column">
                            <small class="text-muted">Venta</small>
                            <h4 class="mb-1">${formatNum(totalVenta, '$')}</h4>
                            <small class="text-muted mt-auto">
                                Total facturado al cliente después de descuentos.
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body d-flex flex-column">
                            <small class="text-muted">Costo</small>
                            <h4 class="mb-1">${formatNum(totalCosto, '$')}</h4>
                            <small class="text-muted mt-auto">
                                Costo total de los productos vendidos.
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body d-flex flex-column">
                            <small class="text-muted">Utilidad</small>
                            <h4 class="mb-1">${formatNum(totalUtilidad, '$')}</h4>
                            <small class="text-muted mt-auto">
                                Ganancia neta (Venta - Costo).
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body d-flex flex-column">
                            <small class="text-muted">Margen</small>
                            <h4 class="mb-1 text-primary">
                                ${margenGlobal.toFixed(2)}%
                            </h4>
                            <small class="text-muted mt-auto">
                                Utilidad sobre la venta (Utilidad ÷ Venta)
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body d-flex flex-column">
                            <small class="text-muted">Markup</small>
                            <h4 class="mb-1 text-success">
                                ${markupGlobal.toFixed(2)}%
                            </h4>
                            <small class="text-muted mt-auto">
                                Utilidad sobre el costo (Utilidad ÷ Costo)
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body d-flex flex-column">
                            <small class="text-muted">Venta PP</small>
                            <h4 class="mb-1">${formatNum(totalVentaPP, '$')}</h4>
                            <small class="text-muted mt-auto">
                                Venta con descuento por pronto pago.
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body d-flex flex-column">
                            <small class="text-muted">Margen PP</small>
                            <h4 class="mb-1 text-warning">
                                ${margenPPGlobal.toFixed(2)}%
                            </h4>
                            <small class="text-muted mt-auto">
                                Margen después de pronto pago.
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body d-flex flex-column">
                            <small class="text-muted">Markup PP</small>
                            <h4 class="mb-1 text-danger">
                                ${markupPPGlobal.toFixed(2)}%
                            </h4>
                            <small class="text-muted mt-auto">
                                Markup después de pronto pago.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
  
        <div class="card shadow-sm border-0">

            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="mb-0 text-white">
                            <i class="fas fa-chart-line"></i>
                            Rentabilidad por Producto
                        </h5>

                        <small class="text-white-50">
                            Del ${fechaInicio} al ${fechaFin}
                        </small>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="tblRentabilidad" class="table table-hover mb-0">
                     <thead class="table-primary"> </thead>
                     <tbody></tbody>
                     <tfoot class="table-light fw-bold"> </tfoot>
                    </table>
                </div>
            </div>
        </div>
    `;

    $("#resultado").html(htmlResultado);

    createDataTable(
        'tblRentabilidad',
        data,
        dataNames,
        columnNames,
        false,
        false,
        false,
        '',
        '',
        true,
        currencyColumns,
        'COP',
        [],
        sumColumns,
        20000
    );
    $("#tblRentabilidad tbody tr").each(function () {

        const celdas = $(this).find('td');

        // =========================
        // VALORES BASE
        // =========================
        const venta = parseFloat(celdas.eq(3).text().replace(/[^\d.-]/g, '')) || 0;
        const costo = parseFloat(celdas.eq(4).text().replace(/[^\d.-]/g, '')) || 0;
        const utilidad = parseFloat(celdas.eq(5).text().replace(/[^\d.-]/g, '')) || 0;

        const ventaPP = parseFloat(celdas.eq(8).text().replace(/[^\d.-]/g, '')) || 0;
        const utilidadPP = parseFloat(celdas.eq(9).text().replace(/[^\d.-]/g, '')) || 0;

        // =========================
        // CALCULOS
        // =========================

        const margen = venta !== 0 ? (utilidad / venta) * 100 : 0;
        const markup = costo !== 0 ? (utilidad / costo) * 100 : 0;

        const margenPP = ventaPP !== 0 ? (utilidadPP / ventaPP) * 100 : 0;
        const markupPP = costo !== 0 ? (utilidadPP / costo) * 100 : 0;

        // =========================
        // FUNCION DE ESTILO
        // =========================
        const formatIndicador = (valor) => {

            if (valor < 0) {
                return `<span class="text-danger fw-bold">
                        <i class="fas fa-arrow-down"></i> ${valor.toFixed(2)}%
                    </span>`;
            }

            if (valor > 0) {
                return `<span class="text-success fw-bold">
                        <i class="fas fa-arrow-up"></i> ${valor.toFixed(2)}%
                    </span>`;
            }

            return `<span class="text-muted">${valor.toFixed(2)}%</span>`;
        };

        // =========================
        // ASIGNAR VALORES
        // =========================

        celdas.eq(6).html(formatIndicador(margen));      // Margen
        celdas.eq(7).html(formatIndicador(markup));      // Markup
        celdas.eq(10).html(formatIndicador(margenPP));   // Margen PP
        celdas.eq(11).html(formatIndicador(markupPP));   // Markup PP
    });
};
const calcularTotales = (data) => {
    let totalVenta = 0;
    let totalCosto = 0;
    let rentabilidadTotal = 0;

    data.forEach(row => {
        totalVenta += parseFloat(row.venta) || 0;
        totalCosto += parseFloat(row.costo) || 0;
        rentabilidadTotal += (parseFloat(row.venta) - parseFloat(row.costo)) || 0;
    });

    // Markup = (Utilidad / Costo) * 100
    const porcentajeRentabilidad = totalCosto !== 0
        ? ((rentabilidadTotal / totalCosto) * 100)
        : 0;

    return {
        totalVenta,
        totalCosto,
        rentabilidadTotal,
        porcentajeRentabilidad
    };
}
const agregarFlechasRentabilidad = () => {
    // Obtener índice de la columna rentabilidad_valor (posición 7 en dataNames)
    const indiceRentabilidad = 7;
    const indiceCosto = 5;
    const indiceUtilidad = 6;

    // Recorrer todas las filas del tbody
    $('#tblRentabilidad tbody tr').each(function () {
        const celdas = $(this).find('td');

        if (celdas.length > indiceRentabilidad) {
            const costo = parseFloat(celdas.eq(indiceCosto).text().replace(/[^\d.-]/g, '')) || 0;
            const utilidad = parseFloat(celdas.eq(indiceUtilidad).text().replace(/[^\d.-]/g, '')) || 0;

            // Calcular markup = (Utilidad / Costo) * 100
            const markup = costo !== 0 ? ((utilidad / costo) * 100) : 0;

            if (!isNaN(markup)) {
                const celdaRent = celdas.eq(indiceRentabilidad);

                if (utilidad < 0) {
                    // Utilidad negativa - flecha roja hacia abajo
                    celdaRent.html(`<span class="text-danger fw-bold"><i class="fas fa-arrow-down"></i> ${markup.toFixed(2)}%</span>`);
                } else if (utilidad > 0) {
                    // Utilidad positiva - flecha verde hacia arriba
                    celdaRent.html(`<span class="text-success fw-bold"><i class="fas fa-arrow-up"></i> ${markup.toFixed(2)}%</span>`);
                } else {
                    // Utilidad igual a 0
                    celdaRent.html(`<span class="text-muted fw-bold"><i class="fas fa-minus"></i> 0%</span>`);
                }
            }
        }
    });
}
const limpiar = () => {
    $("#resultado").html("");
    $("#fe_fecha_inicial").val("");
    $("#fe_fecha_final").val("");
    $("#bodegas").val(0);
}
$(function () {
    listarAlmacenes();
    fechaActual([[
        'fe_fecha_inicial',
        'fe_fecha_final'
    ]]);

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
        if (dIni > dFin) {
            Swal.fire("Advertencia", "La fecha inicial no puede ser mayor a la fecha final", "warning");
            return;
        }
        consultarRentabilidad(fhIni, fhFin)
    });
    $("#btn-limpiar").click(function () {
        limpiar();
    });
    dissminSwal();
})