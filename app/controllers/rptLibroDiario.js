const cargarLibroDiario = async (fhIni, fhFin) => {
  showLoadingWithTetrisLogo('Generando Libro Diario', 'ISA Software');

  try {
    const data = {
      link: '../models/rptLibroDiario.php',
      op: 'consultar-libro',
      fhIni,
      fhFin
    };

    let resp = await enviarPeticion(data);
    resp = resp.data || [];

    if (!resp.length) {
      $('#contenedor-libro-diario').html(
        ShowAlert('No se encontraron registros en el Libro Diario', 'warning')
      );
      dissminSwal();
      return;
    }

    const rows = resp.map((r) => ({
      fecha: formatDateForDisplay(r.fecha),
      tipo: r.tipo || '-',
      numero: r.numero || '-',
      cuenta: r.cuenta || '-',
      cuenta_desc: r.cuenta_desc || '-',
      nit: r.nit || '-',
      razon_comercial: r.razon_comercial || '-',
      tratamiento: r.tratamiento || '-',
      debito: parseFloat(r.debito) || 0,
      credito: parseFloat(r.credito) || 0
    }));

    let totalDeb = 0;
    let totalCre = 0;

    rows.forEach((row) => {
      totalDeb += row.debito;
      totalCre += row.credito;
    });

    const balance = totalDeb - totalCre;

    const header = $(
      `<div class="d-flex justify-content-between align-items-start mb-3">
        <div>
          <h5 class="mb-0">Libro Diario Contable</h5>
          <div class="small-muted">Periodo:
            <strong>${formatDateForDisplay(fhIni)}</strong> —
            <strong>${formatDateForDisplay(fhFin)}</strong>
          </div>
        </div>
      </div>
    `);

    const summaryCards = `
      <div class="row g-3 mb-3">
        <div class="col-md-4">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="text-muted small">Total Débito</div>
              <h5 class="mb-0 text-success">${fmt(Math.round(totalDeb))}</h5>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="text-muted small">Total Crédito</div>
              <h5 class="mb-0 text-danger">${fmt(Math.round(totalCre))}</h5>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="text-muted small">Registros</div>
              <h5 class="mb-0">${rows.length}</h5>
            </div>
          </div>
        </div>
      </div>
    `; 

    const tableMarkup = `
      <div class="table-responsive" id="contenedor-tabla-libro">
        <table class="table table-hover estados-table mb-0" id="tabla-libro">
          <thead class="table-primary">
            <tr>
              <th>Fecha</th>
              <th>Tipo</th>
              <th>Número</th>
              <th>Cuenta</th>
              <th>Descripción</th>
              <th>NIT</th>
              <th>Razón Social</th>
              <th>Tratamiento</th>
              <th class="text-end">Débito</th>
              <th class="text-end">Crédito</th>
            </tr>
          </thead>
        </table>
      </div>
    `;

    $('#contenedor-libro-diario').empty().append(header).append(summaryCards).append(tableMarkup);

    createDataTable(
      'tabla-libro',
      rows,
      ['fecha', 'tipo', 'numero', 'cuenta', 'cuenta_desc', 'nit', 'razon_comercial', 'tratamiento', 'debito', 'credito'],
      ['Fecha', 'Tipo', 'Número', 'Cuenta', 'Descripción', 'NIT', 'Razón Social', 'Tratamiento', 'Débito', 'Crédito'],
      null,
      null,
      false,
      '',
      '',
      true,
      ['debito', 'credito'],
      'COP',
      [],
      [],
      25,
      true
    );

    dissminSwal();
  } catch (e) {
    console.error('Error Libro Diario:', e);
    $('#contenedor-libro-diario').html(
      ShowAlert('Error al generar el Libro Diario', 'danger')
    );
    dissminSwal();
  }
};

function formatDateForDisplay(d) {
  if (!d) return '';
  const s = String(d);
  if (s.length === 8) {
    return `${s.slice(6, 8)}/${s.slice(4, 6)}/${s.slice(0, 4)}`;
  }
  return s;
}

function fmt(num) {
  const value = Number(num || 0);
  return value.toLocaleString('es-CO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
}

function escapeCsv(value) {
  const str = value == null ? '' : String(value);
  return `"${str.replace(/"/g, '""')}"`;
}

function exportarLibroCSV(fhIni, fhFin) {
  const table = $('#tabla-libro').DataTable();
  const rows = table.rows().data().toArray();

  if (!rows.length) return;

  const headers = ['Fecha', 'Tipo', 'Número', 'Cuenta', 'Descripción', 'NIT', 'Razón Social', 'Tratamiento', 'Débito', 'Crédito'];
  const csvRows = rows.map((row) => [
    row.fecha,
    row.tipo,
    row.numero,
    row.cuenta,
    row.cuenta_desc,
    row.nit,
    row.razon_comercial,
    row.tratamiento,
    row.debito,
    row.credito
  ]);

  const content = [headers, ...csvRows]
    .map((row) => row.map(escapeCsv).join(','))
    .join('\n');

  const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = `LibroDiario_${fhIni}_a_${fhFin}.csv`;
  link.click();
  URL.revokeObjectURL(link.href);
}

const limpiar = () => {
  const hoy = new Date();
  const primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
  const ultimoDia = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
  const formatoFecha = (fecha) => fecha.toISOString().split('T')[0];

  $('#fe_fecha_inicial').val(formatoFecha(primerDia));
  $('#fe_fecha_final').val(formatoFecha(ultimoDia));

  $('#contenedor-libro-diario').html('');

  if ($.fn.DataTable.isDataTable('#tabla-libro')) {
    $('#tabla-libro').DataTable().clear().destroy();
  }
};

$(function () {
  limpiar();

  $('#btn-busqueda').click(function () {
    let fhIni = $('#fe_fecha_inicial').val();
    let fhFin = $('#fe_fecha_final').val();

    if (!fhIni || !fhFin) {
      Swal.fire('Advertencia', 'Debe seleccionar ambas fechas', 'warning');
      return;
    }

    let dIni = new Date(fhIni);
    let dFin = new Date(fhFin);

    if (dFin < dIni) {
      Swal.fire('Error', 'La fecha final no puede ser anterior a la fecha inicial', 'error');
      return;
    }

    cargarLibroDiario(fhIni, fhFin);
  });

  $('#btn-limpiar').click(function () {
    limpiar();
  });

  dissminSwal();
});