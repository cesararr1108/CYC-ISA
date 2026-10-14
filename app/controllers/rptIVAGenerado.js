const link_ = '../models/rptIVAGenerado.php';

const toNumber = (value) => Number(value) || 0;

const formatCurrency = (value) => {
        return new Intl.NumberFormat('es-CO', {
                style: 'currency',
                currency: 'COP',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
        }).format(toNumber(value));
};

const renderCardsTransacciones = (rows) => {
        const totalBaseVentas = rows.reduce((acc, row) => acc + toNumber(row.base_ventas), 0);
        const totalIvaGenerado = rows.reduce((acc, row) => acc + toNumber(row.iva_generado), 0);
        const totalBaseCompras = rows.reduce((acc, row) => acc + toNumber(row.base_compras), 0);
        const totalIvaDescontable = rows.reduce((acc, row) => acc + toNumber(row.iva_descontable), 0);
        const saldoIva = totalIvaGenerado - totalIvaDescontable;

        return `
            <div class="row g-2 mb-3">
                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="small text-muted">Base Ventas</div>
                                    <div class="h6 mb-0">${formatCurrency(totalBaseVentas)}</div>
                                </div>
                                <i class="fa-solid fa-store text-muted"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="small text-muted">IVA Generado</div>
                                    <div class="h6 mb-0 text-primary">${formatCurrency(totalIvaGenerado)}</div>
                                </div>
                                <i class="fa-solid fa-file-invoice-dollar text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="small text-muted">Base Compras</div>
                                    <div class="h6 mb-0">${formatCurrency(totalBaseCompras)}</div>
                                </div>
                                <i class="fa-solid fa-cart-shopping text-muted"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="small text-muted">IVA Neto</div>
                                    <div class="h6 mb-0 ${saldoIva >= 0 ? 'text-success' : 'text-danger'}">${formatCurrency(saldoIva)}</div>
                                </div>
                                <i class="fa-solid fa-scale-balanced ${saldoIva >= 0 ? 'text-success' : 'text-danger'}"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
};

const renderCardsContable = (rows) => {
        const totalIvaGenerado = rows.reduce((acc, row) => acc + toNumber(row.iva_generado), 0);
        const totalIvaDescontable = rows.reduce((acc, row) => acc + toNumber(row.iva_descontable), 0);
        const totalSaldo = rows.reduce((acc, row) => acc + toNumber(row.saldo_iva), 0);

        return `
            <div class="row g-2 mb-3">
                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="small text-muted">Registros</div>
                                    <div class="h6 mb-0">${rows.length.toLocaleString('es-CO')}</div>
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
                                    <div class="small text-muted">IVA Generado</div>
                                    <div class="h6 mb-0 text-primary">${formatCurrency(totalIvaGenerado)}</div>
                                </div>
                                <i class="fa-solid fa-arrow-trend-up text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="small text-muted">IVA Descontable</div>
                                    <div class="h6 mb-0 text-info">${formatCurrency(totalIvaDescontable)}</div>
                                </div>
                                <i class="fa-solid fa-arrow-trend-down text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="small text-muted">Saldo IVA</div>
                                    <div class="h6 mb-0 ${totalSaldo >= 0 ? 'text-success' : 'text-danger'}">${formatCurrency(totalSaldo)}</div>
                                </div>
                                <i class="fa-solid fa-wallet ${totalSaldo >= 0 ? 'text-success' : 'text-danger'}"></i>
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
      <table class="table table-sm table-striped" id="tbl-iva-generado">
        <thead class="table-primary"></thead>
        <tbody></tbody>
      </table>
    `;

    $('#reporte-estado-resultados').html(tableHTML);

    createDataTable(
        'tbl-iva-generado',
        rows,
        [
            'porcentaje_iva', 'nit', 'id_tercero', 'tratamiento', 'razon_comercial',
            'base_ventas', 'iva_generado', 'base_compras', 'iva_descontable'
        ],
        [
            '% IVA', 'NIT', 'Id tercero', 'Tratamiento', 'Razon comercial',
            'Base ventas', 'IVA generado', 'Base compras', 'IVA descontable'
        ],
        null,
        null,
        false,
        false,
        '',
        true,
        ['base_ventas', 'iva_generado', 'base_compras', 'iva_descontable'],
        'COP'
    );
};

const renderTablaContable = (rows) => {
        const cardsHTML = renderCardsContable(rows);

        const tableHTML = `
            ${cardsHTML}
      <table class="table table-sm table-striped" id="tbl-iva-generado">
        <thead class="table-primary"></thead>
        <tbody></tbody>
      </table>
    `;

    $('#reporte-estado-resultados').html(tableHTML);

    createDataTable(
        'tbl-iva-generado',
        rows,
        ['id_transaccion', 'nit', 'tipo', 'numero', 'iva_generado', 'iva_descontable', 'saldo_iva'],
        ['Id transaccion', 'NIT', 'Tipo', 'Numero', 'IVA generado', 'IVA descontable', 'Saldo IVA'],
        null,
        null,
        false,
        false,
        '',
        true,
        ['iva_generado', 'iva_descontable', 'saldo_iva'],
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
            Swal.fire('Error', resp.mensaje || 'No fue posible consultar la informacion', 'error');
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