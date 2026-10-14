const link_ = '../models/rptRetenciones.php';

const toNumber = (value) => Number(value) || 0;

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(toNumber(value));
};

const formatPercentage = (value) => {
    return `${Number(value || 0).toFixed(2)} %`;
};

const renderCardsTransacciones = (rows) => {
    const totalDocumentos = rows.length;
    const totalBaseGravable = rows.reduce((acc, row) => acc + toNumber(row.base_gravable), 0);
    const totalRetencionFuente = rows.reduce((acc, row) => acc + toNumber(row.retencion_fuente), 0);
    const promedioRetencion = rows.length > 0
        ? rows.reduce((acc, row) => acc + toNumber(row.porcentaje_retencion), 0) / rows.length
        : 0;

    return `
        <div class="row g-2 mb-3">
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small text-muted">Documentos</div>
                                <div class="h6 mb-0">${totalDocumentos.toLocaleString('es-CO')}</div>
                            </div>
                            <i class="fa-solid fa-file-invoice text-muted"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small text-muted">Base Gravable</div>
                                <div class="h6 mb-0">${formatCurrency(totalBaseGravable)}</div>
                            </div>
                            <i class="fa-solid fa-coins text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small text-muted">Retención Fuente</div>
                                <div class="h6 mb-0 text-primary">${formatCurrency(totalRetencionFuente)}</div>
                            </div>
                            <i class="fa-solid fa-percent text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small text-muted">Promedio Retención</div>
                                <div class="h6 mb-0">${formatPercentage(promedioRetencion)}</div>
                            </div>
                            <i class="fa-solid fa-chart-line text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
};

const renderCardsContable = (rows) => {
    const totalRegistros = rows.length;
    const totalReteFuente = rows.reduce((acc, row) => acc + toNumber(row.retefuente_compra) + toNumber(row.retefuente_venta), 0);
    const totalReteIva = rows.reduce((acc, row) => acc + toNumber(row.reteiva_compra) + toNumber(row.reteiva_venta), 0);
    const totalReteIca = rows.reduce((acc, row) => acc + toNumber(row.reteica_compra) + toNumber(row.reteica_venta), 0);

    return `
        <div class="row g-2 mb-3">
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small text-muted">Registros</div>
                                <div class="h6 mb-0">${totalRegistros.toLocaleString('es-CO')}</div>
                            </div>
                            <i class="fa-solid fa-list-check text-muted"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small text-muted">Total Retefuente</div>
                                <div class="h6 mb-0 text-primary">${formatCurrency(totalReteFuente)}</div>
                            </div>
                            <i class="fa-solid fa-hand-holding-dollar text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small text-muted">Total Rete IVA</div>
                                <div class="h6 mb-0 text-info">${formatCurrency(totalReteIva)}</div>
                            </div>
                            <i class="fa-solid fa-percent text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small text-muted">Total Rete ICA</div>
                                <div class="h6 mb-0 text-success">${formatCurrency(totalReteIca)}</div>
                            </div>
                            <i class="fa-solid fa-building text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
};

const renderTablaTransacciones = (rows) => {
    const cardsHTML = renderCardsTransacciones(rows);

    const tableHTML = `
        ${cardsHTML}
        <table class="table table-sm table-striped" id="tbl-retenciones-transacciones">
            <thead class="table-primary"></thead>
            <tbody></tbody>
        </table>
    `;

    $('#reporte-estado-resultados').html(tableHTML);

    createDataTable(
        'tbl-retenciones-transacciones',
        rows,
        [
            'fecha',
            'tipo',
            'numero',
            'nit',
            'tercero',
            'tratamiento',
            'base_gravable',
            'retencion_fuente',
            'porcentaje_retencion'
        ],
        [
            'Fecha',
            'Tipo',
            'Número',
            'NIT',
            'Tercero',
            'Tratamiento',
            'Base Gravable',
            'Retención Fuente',
            '% Retención'
        ],
        null,
        null,
        false,
        false,
        '',
        true,
        ['base_gravable', 'retencion_fuente'],
        'COP'
    );
};

const renderTablaContable = (rows) => {
    const cardsHTML = renderCardsContable(rows);

    const tableHTML = `
        ${cardsHTML}
        <table class="table table-sm table-striped" id="tbl-retenciones-contable">
            <thead class="table-primary"></thead>
            <tbody></tbody>
        </table>
    `;

    $('#reporte-estado-resultados').html(tableHTML);

    createDataTable(
        'tbl-retenciones-contable',
        rows,
        [
            'id_transaccion',
            'nit',
            'tipo',
            'numero',
            'retefuente_compra',
            'retefuente_venta',
            'reteiva_compra',
            'reteiva_venta',
            'reteica_compra',
            'reteica_venta'
        ],
        [
            'Id Transacción',
            'NIT',
            'Tipo',
            'Número',
            'Retefuente Compra',
            'Retefuente Venta',
            'Rete IVA Compra',
            'Rete IVA Venta',
            'Rete ICA Compra',
            'Rete ICA Venta'
        ],
        null,
        null,
        false,
        false,
        '',
        true,
        ['retefuente_compra', 'retefuente_venta', 'reteiva_compra', 'reteiva_venta', 'reteica_compra', 'reteica_venta'],
        'COP'
    );
};

const consultarReporte = async () => {
    const fechaInicial = $('#fe_fecha_inicial').val();
    const fechaFinal = $('#fe_fecha_final').val();
    const tipoConsulta = $('#tipo_consulta').val();

    if (!fechaInicial || !fechaFinal) {
        Swal.fire('Ups', 'La fecha inicial y final son obligatorias', 'warning');
        return;
    }

    showLoadingWithTetrisLogo('Consultando reporte', 'ISA Software');
    try {
        const resp = await enviarPeticion({
            link: link_,
            op: 'consultar',
            fecha_inicial: fechaInicial,
            fecha_final: fechaFinal,
            tipo_consulta: tipoConsulta
        });

        if (resp.error) {
            Swal.fire('Error', resp.mensaje || 'No fue posible consultar la información', 'error');
            return;
        }

        const rows = Array.isArray(resp.data) ? resp.data : [];
        if (rows.length === 0) {
            $('#reporte-estado-resultados').html('');
            Swal.fire('Ups', 'No se encontraron resultados para el rango seleccionado', 'info');
            return;
        }

        if (tipoConsulta === 'contable') {
            renderTablaContable(rows);
        } else {
            renderTablaTransacciones(rows);
        }
    } catch (error) {
        console.error(error);
        Swal.fire('Error', 'Error al consultar el reporte', 'error');
    } finally {
        dissminSwal();
    }
};

$(function () {
    fechaActual([
        ['fe_fecha_inicial', 'fe_fecha_final']
    ]);

    $('#btn-busqueda').on('click', consultarReporte);

    $('#btn-limpiar').on('click', function () {
        fechaActual([
            ['fe_fecha_inicial', 'fe_fecha_final']
        ]);
        $('#tipo_consulta').val('transacciones');
        $('#reporte-estado-resultados').html('');
    });
    dissminSwal();
});
