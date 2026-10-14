const link_ = '../models/rptConciliacionTributariaContable.php';

const formatMoney = (valor) => new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
}).format(Number(valor) || 0);

const aplicarBadgeEstado = () => {
        $('#tbl-reporte-conciliacion tbody tr').each(function () {
                const $tdEstado = $(this).find('td').eq(19);
                const estado = $.trim($tdEstado.text());
                if (estado === 'OK') {
                        $tdEstado.html('<span class="badge bg-label-success">OK</span>');
                } else if (estado === 'REVISAR') {
                        $tdEstado.html('<span class="badge bg-label-warning">REVISAR</span>');
                }
        });
};

const renderResumen = (rows) => {
        const totalRegistros = rows.length;
        const totalOk = rows.filter((x) => x.estado === 'OK').length;
        const totalRevisar = rows.filter((x) => x.estado === 'REVISAR').length;

        const totalDiferencias = rows.reduce((acc, x) => {
                return acc
                        + Math.abs(Number(x.diferencia_iva) || 0)
                        + Math.abs(Number(x.diferencia_retefuente) || 0)
                        + Math.abs(Number(x.diferencia_reteiva) || 0)
                        + Math.abs(Number(x.diferencia_reteica) || 0);
        }, 0);

        return `
            <div class="row g-2 mb-3">
                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body py-2">
                            <div class="small text-muted">Registros</div>
                            <div class="h5 mb-0">${totalRegistros.toLocaleString('es-CO')}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body py-2">
                            <div class="small text-muted">Estado OK</div>
                            <div class="h5 mb-0 text-success">${totalOk.toLocaleString('es-CO')}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body py-2">
                            <div class="small text-muted">Estado Revisar</div>
                            <div class="h5 mb-0 text-warning">${totalRevisar.toLocaleString('es-CO')}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body py-2">
                            <div class="small text-muted">Suma abs diferencias</div>
                            <div class="h6 mb-0 text-danger">${formatMoney(totalDiferencias)}</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
};

const renderReporte = (rows) => {
        const data = (rows || []).map((item) => ({ ...item }));

        const resumenHTML = renderResumen(rows || []);
        const tableHTML = `
            ${resumenHTML}
      <table class="table table-sm table-striped text-nowrap" id="tbl-reporte-conciliacion">
        <thead class="table-primary"></thead>
        <tbody></tbody>
      </table>
    `;

    $('#reporte-estado-resultados').html(tableHTML);

    createDataTable(
        'tbl-reporte-conciliacion',
        data,
        [
            'fecha', 'tipo', 'numero', 'operacion', 'nit',
            'valor_total', 'base_documento', 'iva_documento', 'retefuente_documento', 'reteiva_documento', 'reteica_documento',
            'iva_contabilidad', 'retefuente_contabilidad', 'reteiva_contabilidad', 'reteica_contabilidad',
            'diferencia_iva', 'diferencia_retefuente', 'diferencia_reteiva', 'diferencia_reteica',
            'estado'
        ],
        [
            'Fecha', 'Tipo', 'Numero', 'Operacion', 'NIT',
            'Valor total', 'Base documento', 'IVA documento', 'Retefuente doc', 'ReteIVA doc', 'ReteICA doc',
            'IVA contabilidad', 'Retefuente cont', 'ReteIVA cont', 'ReteICA cont',
            'Dif IVA', 'Dif Retefuente', 'Dif ReteIVA', 'Dif ReteICA',
            'Estado'
        ],
        null,
        null,
        false,
        false,
        '',
        true,
        [
            'valor_total', 'base_documento', 'iva_documento', 'retefuente_documento', 'reteiva_documento', 'reteica_documento',
            'iva_contabilidad', 'retefuente_contabilidad', 'reteiva_contabilidad', 'reteica_contabilidad',
            'diferencia_iva', 'diferencia_retefuente', 'diferencia_reteiva', 'diferencia_reteica'
        ],
        'COP'
    );

    const table = $('#tbl-reporte-conciliacion').DataTable();
    aplicarBadgeEstado();

    $('#tbl-reporte-conciliacion').off('draw.dt.estado').on('draw.dt.estado', function () {
        aplicarBadgeEstado();
    });
};

const consultarReporte = async () => {
    const fechaInicial = $('#fe_fecha_inicial').val();
    const fechaFinal = $('#fe_fecha_final').val();

    if (!fechaInicial || !fechaFinal) {
        Swal.fire('Ups', 'La fecha inicial y final son obligatorias', 'warning');
        return;
    }

    showLoadingWithTetrisLogo('Consultando conciliacion', 'ISA Software');
    try {
        const resp = await enviarPeticion({
            link: link_,
            op: 'consultar',
            fecha_inicial: fechaInicial,
            fecha_final: fechaFinal
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

        renderReporte(rows);
    } catch (error) {
        console.error(error);
        Swal.fire('Error', 'Error al consultar el reporte', 'error');
    } finally {
        dissminSwal();
    }
};

$(function () {
    fechaActual([
        ['fe_fecha_inicial', 'fe_fecha_final ']
    ]);

    $('#btn-busqueda').on('click', consultarReporte);

    $('#btn-limpiar').on('click', function () {
        fechaActual([
            ['fe_fecha_inicial', 'fe_fecha_final ']
        ]);
        $('#reporte-estado-resultados').html('');
    });
    dissminSwal();
});