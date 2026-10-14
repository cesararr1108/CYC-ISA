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
            link: '../models/rptVentasMarcas.php',
            op: 'consultar',
            fecha_inicio: fechaInicio,
            fecha_fin: fechaFin,
            id_bodega: $("#bodegas").val(),
            id_marca: $("#marcas").val()
        };


        const resp = await enviarPeticion(data);

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
    // Nombres de los datos de la API - según el modelo rptVentasMarcas.php
    const dataNames = [
        'codigo',
        'marca',
        'unidades',
        'venta_bruta',
        'devoluciones',
        'venta_neta',
        'costo',
        'utilidad',
        'margen',
        'markup',
        'participacion'
    ];

    // Nombres para mostrar en la tabla
    const columnNames = [
        'ID',
        'Marca',
        'Unidades',
        'Venta Bruta',
        'Devoluciones',
        'Venta Neta',
        'Costo',
        'Utilidad',
        'Margen %',
        'Markup %',
        'Participación %'
    ];

    // Columnas que deben formatearse como moneda
    const currencyColumns = ['venta_bruta', 'devoluciones', 'venta_neta', 'costo', 'utilidad'];

    // Columnas que se deben sumar en el footer
    const sumColumns = ['unidades', 'venta_bruta', 'devoluciones', 'venta_neta', 'costo', 'utilidad'];

    // Calcular totales
    const totales = calcularTotales(data);

    // Crear el HTML de la tabla con cards de resumen
    let htmlResultado = `
        
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-pie-chart"></i> Participación por Marca (TOP 20)
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div id="graficoParticipacion" style="height: 460px;"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
            <div class="card shadow-sm border-0">
               <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-gauge-high"></i> Indicadores clave
                        </h5>
                    </div>
                      <div class="card-body">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h6 class="card-title mb-2 text-muted">Total Unidades</h6>
                        <h4 class="mb-0">${totales.totalUnidades.toLocaleString('es-CO')}</h4>
                    </div>
                </div>
      
                <div class="card shadow-sm border-0 mt-3">
                    <div class="card-body">
                        <h6 class="card-title mb-2 text-muted">Total Venta Neta</h6>
                        <h4 class="mb-0">${formatNum(totales.totalVentaNeta, '$')}</h4>
                    </div>
                </div>
     
                <div class="card shadow-sm border-0 mt-3">
                    <div class="card-body">
                        <h6 class="card-title mb-2 text-muted">Total Costo</h6>
                        <h4 class="mb-0">${formatNum(totales.totalCosto, '$')}</h4>
                    </div>
                </div>
    
                <div class="card shadow-sm border-0 mt-3">
                    <div class="card-body">
                        <h6 class="card-title mb-2 text-muted">Total Utilidad</h6>
                        <h4 class="mb-0 ${totales.totalUtilidad < 0 ? 'text-danger' : 'text-success'}">
                            <i class="fas ${totales.totalUtilidad < 0 ? 'fa-arrow-down' : 'fa-arrow-up'}"></i>
                            ${formatNum(totales.totalUtilidad, '$')}
                        </h4>
                    </div>
                </div>
                     </div>
                 </div>
             </div>
        </div>

                <div class="table-responsive">
                    <table id="tblRentabilidad" class="table table-hover mb-0">
                        <thead class="table-primary">
                        </thead>
                        <tbody>
                        </tbody>
                        <tfoot class="table-light fw-bold bg-light">
                        </tfoot>
                    </table>
                </div>


        
    `;

    $("#resultado").html(htmlResultado);

    // Crear DataTable con opciones de exportación
    createDataTable(
        'tblRentabilidad',
        data,
        dataNames,
        columnNames,
        false, // editCallback
        false, // deleteCallback
        false, // otherButton
        '', // boton
        '', // otherCallback
        true, // btnExport (habilitar botones de exportación)
        currencyColumns,
        'COP',
        [],
        sumColumns,
        20000 // pageLength
    );

    // Generar gráfico de participación por marca
    generarGraficoParticipacion(data);

    // Aplicar estilos a filas según utilidad (índice 7)
    $("#tblRentabilidad tbody tr").each(function () {
        const celdas = $(this).find('td');
        const utilidad = parseFloat(celdas.eq(7).text().replace(/[^\d.-]/g, '')) || 0;

        if (utilidad < 0) {
            // Utilidad negativa - flecha roja hacia abajo
            celdas.eq(7).html(`<span class="text-danger fw-bold"><i class="fas fa-arrow-down"></i> ${celdas.eq(7).html()}</span>`);
        } else if (utilidad > 0) {
            // Utilidad positiva - flecha verde hacia arriba
            celdas.eq(7).html(`<span class="text-success fw-bold"><i class="fas fa-arrow-up"></i> ${celdas.eq(7).html()}</span>`);
        }
    });

}

const generarGraficoParticipacion = (data) => {
    // Limitar a top 20 marcas
    const top20 = data.slice(0, 20);
    
    // Extraer marcas y participación
    const marcas = top20.map(item => item.marca);
    const participacion = top20.map(item => parseFloat(item.participacion) || 0);

    // Paleta de colores para el gráfico
    const colores = [
        '#696cff', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981',
        '#06b6d4', '#0ea5e9', '#f97316', '#14b8a6', '#6366f1'
    ];

    const options = {
        chart: {
            type: 'donut',
            height: 400,
            toolbar: { show: true }
        },
        series: participacion,
        labels: marcas,
        colors: colores.slice(0, marcas.length),
        plotOptions: {
            pie: {
                donut: {
                    size: '75%',
                    labels: {
                        show: true,
                        name: {
                            show: true,
                            fontSize: '12px',
                            offsetY: 20
                        },
                        value: {
                            show: true,
                            fontSize: '14px',
                            fontWeight: 600,
                            offsetY: -20,
                            formatter: (val) => val.toFixed(2) + '%'
                        },
                        total: {
                            show: true,
                            label: 'Participación',
                            fontSize: '12px',
                            formatter: () => '100%'
                        }
                    }
                }
            }
        },
        tooltip: {
            y: {
                formatter: (val) => val.toFixed(2) + '%'
            }
        },
        legend: {
            position: 'bottom',
            fontSize: '11px',
            offsetY: 10
        },
        dataLabels: {
            enabled: true,
            formatter: (val) => val.toFixed(2) + '%',
            style: {
                fontSize: '11px',
                fontWeight: 'bold'
            }
        }
    };

    // Destruir gráfico anterior si existe
    if (window.chartParticipacion) {
        window.chartParticipacion.destroy();
    }

    window.chartParticipacion = new ApexCharts(document.querySelector('#graficoParticipacion'), options);
    window.chartParticipacion.render();
}

const calcularTotales = (data) => {
    let totalUnidades = 0;
    let totalVentaNeta = 0;
    let totalCosto = 0;
    let totalUtilidad = 0;

    data.forEach(row => {
        totalUnidades += parseFloat(row.unidades) || 0;
        totalVentaNeta += parseFloat(row.venta_neta) || 0;
        totalCosto += parseFloat(row.costo) || 0;
        totalUtilidad += parseFloat(row.utilidad) || 0;
    });

    return {
        totalUnidades,
        totalVentaNeta,
        totalCosto,
        totalUtilidad
    };
}

const agregarFlechasRentabilidad = () => {
    // Estilos para columnas de porcentaje (Margen %, Markup %, Participación %)
    const indiceMargen = 8;
    const indiceMarkup = 9;
    const indiceParticipacion = 10;

    // Recorrer todas las filas del tbody
    $('#tblRentabilidad tbody tr').each(function () {
        const celdas = $(this).find('td');

        if (celdas.length > indiceParticipacion) {
            const margen = parseFloat(celdas.eq(indiceMargen).text()) || 0;
            const markup = parseFloat(celdas.eq(indiceMarkup).text()) || 0;
            const participacion = parseFloat(celdas.eq(indiceParticipacion).text()) || 0;

            // Estilos para Margen
            if (margen > 0) {
                celdas.eq(indiceMargen).html(`<span class="text-success fw-bold">${margen.toFixed(2)}%</span>`);
            }

            // Estilos para Markup
            if (markup > 0) {
                celdas.eq(indiceMarkup).html(`<span class="text-info fw-bold">${markup.toFixed(2)}%</span>`);
            }

            // Estilos para Participación
            if (participacion > 0) {
                celdas.eq(indiceParticipacion).html(`<span class="text-primary fw-bold">${participacion.toFixed(2)}%</span>`);
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
    // Limpiar gráfico
    if (window.chartParticipacion) {
        window.chartParticipacion.destroy();
        window.chartParticipacion = null;
    }
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
