// Función principal: consulta y renderiza el informe
async function cargarEstadoResultados(fhIni, fhFin) {
  try {
    // Mostrar loader (usa tu función o Swal)
    showLoadingWithTetrisLogo('Generando Estado de Resultados', 'ISA Software')

    const payload = {
      link: '../models/rptEstadoResultados.php', // ajusta si tu endpoint es otro
      op: 'consultar-estado-resultados', // ajusta según tu backend
      fhIni,
      fhFin
    };

    const resp = await enviarPeticion(payload);
    const rows = resp.data || [];

    dissminSwal();

    renderEstadoResultados(rows, '#reporte-estado-resultados', fhIni, fhFin);
  } catch (err) {
    dissminSwal();
    console.error(err);
    $('#reporte-estado-resultados').html(`<div class="alert alert-danger">Error al generar el informe.</div>`);
  }
}


function renderEstadoResultados(rows, selector = '#reporte-estado-resultados', fhIni, fhFin) {

  const $target = $(selector);
  $target.empty();

  if (!rows || rows.length === 0) {
    $target.html(`<div class="alert alert-warning">No se encontraron registros para el período seleccionado.</div>`);
    return;
  }

  // Formateador moneda COP
  const fmt = (v) => {
    if (v === null || v === undefined || v === '') return '';
    return new Intl.NumberFormat('es-CO', {
      style: 'currency',
      currency: 'COP',
      minimumFractionDigits: 0
    }).format(Number(v));
  };

  // Orden categorías
  const order = ['INGRESOS', 'COSTOS', 'GASTOS', 'OTROS', 'NO CLASIFICADO'];

  const grouped = {};
  rows.forEach(r => {
    const cat = r.categoria || 'NO CLASIFICADO';
    if (!grouped[cat]) grouped[cat] = [];
    grouped[cat].push(r);
  });

  const header = $(`
    <div class="d-flex justify-content-between align-items-start mb-3">
      <div>
        <h5 class="mb-0">Estado de Resultados</h5>
        <div class="small-muted">
          Periodo: <strong>${formatDateForDisplay(fhIni)}</strong> —
          <strong>${formatDateForDisplay(fhFin)}</strong>
        </div>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" id="btn-export-csv">
          Descargar CSV
        </button>
        <button class="btn btn-outline-primary btn-sm" id="btn-print-report">
          Imprimir
        </button>
      </div>
    </div>
  `);

  $target.append(header);

  let totals = {
    INGRESOS: 0,
    COSTOS: 0,
    GASTOS: 0,
    OTROS: 0
  };

  order.forEach(cat => {

    const dataCat = grouped[cat];
    if (!dataCat || dataCat.length === 0) return;

    let $card = $(`
      <div class="card mb-3 summary-card">
        <div class="card-body p-2">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">${cat}</h6>
            <small class="text-muted">${dataCat.length} cuentas</small>
          </div>

          <div class="table-responsive">
            <table class="table table-sm table-hover estados-table mb-0">
              <thead class="table-light">
                <tr>
                  <th>Cuenta</th>
                  <th>Descripción</th>
                  <th class="text-end">Débito</th>
                  <th class="text-end">Crédito</th>
                  <th class="text-end">Resultado</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>

        </div>
      </div>
    `);

    const $tbody = $card.find('tbody');

    let subtotal = 0;

    dataCat
      .sort((a, b) => a.cuenta.localeCompare(b.cuenta))
      .forEach(r => {

        const deb = Number(r.total_debito || 0);
        const cre = Number(r.total_credito || 0);

        let res = 0;

        // Normalización contable
        if (cat === 'INGRESOS' || cat === 'OTROS') {
          res = cre - deb;
        } else if (cat === 'COSTOS' || cat === 'GASTOS') {
          res = deb - cre;
        } else {
          res = cre - deb;
        }

        subtotal += res;

        $tbody.append(`
          <tr>
            <td class="text-muted">${escapeHtml(r.cuenta)}</td>
            <td title="${escapeHtml(r.descripcion || '')}">
              ${truncateHtml(r.descripcion || '', 80)}
            </td>
            <td class="text-end">${fmt(deb)}</td>
            <td class="text-end">${fmt(cre)}</td>
            <td class="text-end fw-semibold">${fmt(res)}</td>
          </tr>
        `);

      });

    $tbody.append(`
      <tr class="table-active">
        <td colspan="4" class="text-end">
          <strong>Subtotal ${cat}</strong>
        </td>
        <td class="text-end">
          <strong>${fmt(subtotal)}</strong>
        </td>
      </tr>
    `);

    if (totals.hasOwnProperty(cat)) {
      totals[cat] = subtotal;
    }

    $target.append($card);

  });

  // =============================
  // Cálculos contables correctos
  // =============================

  const ingresos = totals.INGRESOS || 0;
  const costos = totals.COSTOS || 0;
  const gastos = totals.GASTOS || 0;
  const otros = totals.OTROS || 0;

  const utilidadBruta = ingresos - costos;
  const utilidadOperacional = utilidadBruta - gastos;
  const utilidadAntesImpuestos = utilidadOperacional + otros;

  const resumenHtml = $(`
    <div class="card mt-3">
      <div class="card-body">
        <h6 class="mb-3">Resumen</h6>

        <div class="row g-2">

          <div class="col-md-3">
            <div class="small-muted">Total Ingresos</div>
            <div class="fw-semibold">${fmt(ingresos)}</div>
          </div>

          <div class="col-md-3">
            <div class="small-muted">Total Costos</div>
            <div class="fw-semibold">${fmt(costos)}</div>
          </div>

          <div class="col-md-3">
            <div class="small-muted">Utilidad Bruta</div>
            <div class="fw-semibold">${fmt(utilidadBruta)}</div>
          </div>

          <div class="col-md-3">
            <div class="small-muted">Total Gastos</div>
            <div class="fw-semibold">${fmt(gastos)}</div>
          </div>

          <div class="col-md-3 mt-2">
            <div class="small-muted">Utilidad Operacional</div>
            <div class="fw-semibold">${fmt(utilidadOperacional)}</div>
          </div>

          <div class="col-md-3 mt-2">
            <div class="small-muted">Otros</div>
            <div class="fw-semibold">${fmt(otros)}</div>
          </div>

          <div class="col-md-3 mt-2">
            <div class="small-muted">Utilidad Antes Impuestos</div>
            <div class="fw-bold text-primary">${fmt(utilidadAntesImpuestos)}</div>
          </div>

        </div>
      </div>
    </div>
  `);

  $target.append(resumenHtml);

  $('#btn-print-report').off('click').on('click', () => window.print());

  $('#btn-export-csv').off('click').on('click', () => {
    const csv = buildCsvFromRows(rows);
    downloadFile(csv, `estado_resultados_${fhIni}_${fhFin}.csv`);
  });

  // =========================
  // Helpers
  // =========================

  function formatDateForDisplay(d) {
    if (!d) return '';
    const s = String(d);
    if (s.length === 8) {
      return `${s.slice(6, 8)}/${s.slice(4, 6)}/${s.slice(0, 4)}`;
    }
    return s;
  }

  function escapeHtml(text) {
    if (!text) return '';
    return $('<div>').text(text).html();
  }

  function truncateHtml(text, max = 60) {
    if (!text) return '';
    if (text.length <= max) return escapeHtml(text);
    return escapeHtml(text.substr(0, max)) + '...';
  }

  function buildCsvFromRows(rowsArray) {
    const header = ['fecha', 'categoria', 'cuenta', 'descripcion', 'debito', 'credito', 'resultado'];
    const lines = [header.join(',')];

    rowsArray.forEach(r => {
      const vals = [
        `"${(r.fecha || '')}"`,
        `"${(r.categoria || '')}"`,
        `"${(r.cuenta || '')}"`,
        `"${(r.descripcion || '').replace(/"/g, '""')}"`,
        `${Number(r.total_debito || 0)}`,
        `${Number(r.total_credito || 0)}`,
        `${Number(r.resultado || 0)}`
      ];
      lines.push(vals.join(','));
    });

    return lines.join('\r\n');
  }

  function downloadFile(content, filename) {
    const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });

    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);

    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }

}

const limpiar = () => {
  const hoy = new Date();
  const primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
  const ultimoDia = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
  const formatoFecha = (fecha) => fecha.toISOString().split("T")[0];

  $("#fe_fecha_inicial").val(formatoFecha(primerDia));
  $("#fe_fecha_final").val(formatoFecha(ultimoDia));

  $("#reporte-estado-resultados").html("");

  if ($.fn.DataTable.isDataTable("#tb_libro")) {
    $("#tb_libro").DataTable().clear().destroy();
  }
};


$(function () {
  limpiar();

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

    cargarEstadoResultados(fhIni, fhFin)
  });
  $("#btn-limpiar").click(function () {
    limpiar();
  });
  dissminSwal();
});
