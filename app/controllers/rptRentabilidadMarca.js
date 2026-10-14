const listarAlmacenes = async () => {
    try {
        const resp = await bodegasUsuarios();
        let options = `<option value="0">Todas las bodegas</option>`;
        // ordenar el array por bodega 
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
const consultarRentabilidad = async (fechaInicio, fechaFin) => {
    showLoadingWithTetrisLogo('Cargando información', 'ISA Software')
    try {
        const data = {
            link: '../models/rptRentabilidadMarca.php',
            op: 'consultar',
            fecha_inicio: fechaInicio,
            fecha_fin: fechaFin,
            id_bodega: $("#bodegas").val()
        };

        console.log({ data })

        const resp = await enviarPeticion(data);
        console.log({ resp })
        dissminSwal();

        if (resp.data.length == 0) {
            $("#resultado").html(`<div class="alert alert-info"><i class="fas fa-info-circle"></i> No se encontraron datos para el rango de fechas seleccionado.</div>`);
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
        'marca',
        'venta',
        'costo',
        'utilidad',
        'rentabilidad'
    ];

    const columnNames = [
        'ID',
        'Marca',
        'Venta',
        'Costo',
        'Utilidad',
        'Markup (%)'
    ];

    const currencyColumns = ['venta', 'costo', 'utilidad'];
    const sumColumns = ['venta', 'costo'];

    const totales = calcularTotales(data);

    const totalVenta = totales.totalVenta;
    const totalCosto = totales.totalCosto;
    const totalUtilidad = totales.totalUtilidad || (totales.totalVenta - totales.totalCosto);

    const margenGlobal = totalVenta !== 0 ? (totalUtilidad / totalVenta) * 100 : 0;
    const markupGlobal = totalCosto !== 0 ? (totalUtilidad / totalCosto) * 100 : 0;

    let htmlResultado = `
        <div class="row g-3 mb-4">

            <div class="col-md-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex flex-column">
                        <small class="text-muted">Venta</small>
                        <h4 class="mb-1">${formatNum(totalVenta, '$')}</h4>
                        <small class="text-muted mt-auto">
                            Total facturado al cliente.
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
                            Costo total de ventas.
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
                            Venta - Costo.
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
                            Utilidad / Venta.
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
                            Utilidad / Costo.
                        </small>
                    </div>
                </div>
            </div>

        </div>

      

            <div class="table-responsive">
                <table id="tblRentabilidad" class="table table-hover mb-0">
                    <thead class="table-primary"></thead>
                    <tbody></tbody>
                    <tfoot class="table-light fw-bold bg-light"></tfoot>
                </table>
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

        const venta = parseFloat(celdas.eq(2).text().replace(/[^\d.-]/g, '')) || 0;
        const costo = parseFloat(celdas.eq(3).text().replace(/[^\d.-]/g, '')) || 0;
        const utilidad = parseFloat(celdas.eq(4).text().replace(/[^\d.-]/g, '')) || 0;

        const margen = venta !== 0 ? (utilidad / venta) * 100 : 0;
        const markup = costo !== 0 ? (utilidad / costo) * 100 : 0;

        const format = (val) => {
            if (val < 0) {
                return `<span class="text-danger fw-bold">
                    <i class="fas fa-arrow-down"></i> ${val.toFixed(2)}%
                </span>`;
            }
            if (val > 0) {
                return `<span class="text-success fw-bold">
                    <i class="fas fa-arrow-up"></i> ${val.toFixed(2)}%
                </span>`;
            }
            return `${val.toFixed(2)}%`;
        };

        celdas.eq(5).html(format(markup)); // rentabilidad = markup
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
    $("#marcas").val("").trigger("change");
}

$(function () {
    listarAlmacenes();
    listarMarcas();
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
});
