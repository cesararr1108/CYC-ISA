const link_ = '../models/rptVentasGenerales.php';

$(async function () {
  await limpiar();
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

    consultarVentas(fhIni, fhFin)
  });
  $("#btn-limpiar").click(function () {
    limpiar();
  });
  dissminSwal();
});

const limpiar = async () => {
  const hoy = new Date();
  const primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
  const ultimoDia = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
  const formatoFecha = (fecha) => fecha.toISOString().split("T")[0];

  $("#fe_fecha_inicial").val(formatoFecha(primerDia));
  $("#fe_fecha_final").val(formatoFecha(ultimoDia));

  $("#resultado-ventas").html("");
  $("#resumen-ventas").html("");
  $("#grafico-ventas").html("");
};
const consultarVentas = async (fechaInicio, fechaFin) => {
  showLoadingWithTetrisLogo('Consultando ventas', 'ISA Software')
  try {
    const campos = [
      "id_bodega",
      "desc_bodega",
      "tipo",
      "numero",
      "usuario",
      "nit",
      "tratamiento",
      "razon_comercial",
      "fecha",
      "vencimiento",
      "documento_electronico",
      "valor_total"
    ];

    const encabezados = [
      "Bodega",
      "Desc.Bodega",
      "Tipo",
      "Numero",
      "Usuario",
      "Nit",
      "Tratamiento",
      "Razón Comercial",
      "Fecha",
      "Vencimiento",
      "Electronico",
      "Valor Total"
    ];

    const filtros = {
      op: "s_ventas",
      fechaInicio,
      fechaFin,
      tipo: $("#sl_tipo").val()
    };

    const data = {
      link: link_,
      ...filtros
    };

    const resp = await enviarPeticion(data);
    console.log(resp);

    if (!resp || resp.length === 0) {
      $("#resultado-ventas").html(ShowAlert("No se encontraron ventas", "warning"));
      $("#resumen-ventas").html("");
      $("#grafico-ventas").html("");
      dissminSwal();
      return;
    }

    // 🧾 Renderizar tabla
    $("#resultado-ventas").html(`
      <table id="tb_ventas" class="table table-sm table-borderless table-hover table-striped w-100">
        <thead class="table-primary"></thead> 
        <tbody></tbody>
      </table>
    `);

    // 🛑 Advertencia si hay muchos registros
    if (resp.length > 5000) {
      $("#resultado-ventas").before(`
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
          <strong>⚠️ Aviso:</strong> Se encontraron <strong>${resp.length.toLocaleString('es-CO')}</strong> registros. 
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      `);
    }

    // 🧾 Renderizar tabla SIN botones (por incompatibilidad de versiones)
    createDataTable(
      'tb_ventas',
      resp,
      campos,
      encabezados,
      '',
      '',
      true,
      '<button class="other-btn btn btn-sm btn-outline-primary" title="Imprimir documento"><i class="fa fa-print"></i></button>',
      imprimirVenta,
      false,  // ✅ DESACTIVAR botones por incompatibilidad
      ['valor_total']  // ✅ Solo valor_total existe en los datos
    );

    // ✅ Renderizar badges de documento electrónico (en cada cambio de página)
    const table = $('#tb_ventas').DataTable();
    
    const renderizarBadges = () => {
      table.column(10).nodes().to$().each(function() {
        const valor = $(this).text().trim();
        if (valor === 'S') {
          $(this).html('<span class="badge bg-success">Electronico</span>');
        } else if (valor === 'N') {
          $(this).html('<span class="badge bg-danger">No electronico</span>');
        }
      });
    };
    
    // Renderizar badges inicialmente
    renderizarBadges();
    
    // Renderizar badges cada vez que se dibuja la tabla (cambio de página, ordenamiento, etc.)
    $('#tb_ventas').on('draw.dt', renderizarBadges);

    // ✅ Agregar botones de exportación manuales
    $("#resultado-ventas").before(`
      <div class="mb-3">
        <button type="button" class="btn btn-sm btn-outline-success export-excel" title="Exportar a Excel">
          <i class="fa-regular fa-file-excel"></i> Exportar Excel
        </button>
        <button type="button" class="btn btn-sm btn-outline-info export-csv" title="Exportar a CSV">
          <i class="fa-regular fa-file"></i> Exportar CSV
        </button>
        <button type="button" class="btn btn-sm btn-outline-danger export-pdf" title="Exportar a PDF">
          <i class="fa-regular fa-file-pdf"></i> Exportar PDF
        </button>
      </div>
    `);

    // 📥 Event listeners para exportación
    $(".export-excel").off('click').on('click', function () {
      exportarExcelCliente(resp, campos, encabezados);
    });

    $(".export-csv").off('click').on('click', function () {
      let fhIni = $("#fe_fecha_inicial").val();
      let fhFin = $("#fe_fecha_final").val();
      exportarDesdeServidor('csv', fhIni, fhFin);
    });

    $(".export-pdf").off('click').on('click', function () {
      let fhIni = $("#fe_fecha_inicial").val();
      let fhFin = $("#fe_fecha_final").val();
      exportarDesdeServidor('pdf', fhIni, fhFin);
    });

    //  === RESUMEN ===
    const totalVentas = resp.reduce((acc, r) => acc + (parseFloat(r.valor_total) || 0), 0);
    const totalAplicado = resp.reduce((acc, r) => acc + (parseFloat(r.valor_aplicado) || 0), 0);
    const totalTransacciones = resp.length;
    const totalUsuarios = new Set(resp.map(r => r.usuario)).size;

    $("#resumen-ventas").html(`
      <div class="col-md-3 mb-3">
        <div class="card shadow-sm border-primary text-center p-3 rounded-3">
          <h6>Total Ventas</h6>
          <h4 class="fw-bold">$${totalVentas.toLocaleString('es-CO')}</h4>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card shadow-sm border-success text-center p-3 rounded-3">
          <h6>Total Aplicado</h6>
          <h4 class="fw-bold">$${totalAplicado.toLocaleString('es-CO')}</h4>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card shadow-sm border-warning text-center p-3 rounded-3">
          <h6>Transacciones</h6>
          <h4 class="fw-bold">${totalTransacciones}</h4>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card shadow-sm border-info text-center p-3 rounded-3">
          <h6>Usuarios</h6>
          <h4 class="fw-bold">${totalUsuarios}</h4>
        </div>
      </div>
    `);

    // 📈 === GRAFICAR CON APEXCHARTS ===
    const ventasPorFecha = {};
    resp.forEach(r => {
      const fecha = r.fecha;
      const valor = parseFloat(r.valor_total) || 0;
      if (!ventasPorFecha[fecha]) ventasPorFecha[fecha] = 0;
      ventasPorFecha[fecha] += valor;
    });

    const fechas = Object.keys(ventasPorFecha).sort();
    const valores = fechas.map(f => ventasPorFecha[f]);

    if (window.chartVentas) window.chartVentas.destroy();

    const options = {
      chart: {
        type: 'line',
        height: 400,
        toolbar: {
          show: true
        }
      },
      series: [{
        name: 'Ventas',
        data: valores
      }],
      xaxis: {
        categories: fechas,
        title: {
          text: 'Fecha'
        },
        labels: {
          rotate: -45
        }
      },
      yaxis: {
        title: {
          text: 'Valor Total'
        },
        labels: {
          formatter: val => val.toLocaleString('es-CO')
        }
      },
      tooltip: {
        y: {
          formatter: val => "$ " + val.toLocaleString('es-CO')
        }
      },
      colors: ['#007bff']
    };

    window.chartVentas = new ApexCharts(document.querySelector("#grafico-ventas"), options);
    window.chartVentas.render();

    dissminSwal();

  } catch (e) {
    console.error("Error en consultar Ventas:", e);
    $("#resultado-ventas").html(ShowAlert("Error interno de la aplicación", "error"));
    $("#resumen-ventas").html("");
    $("#grafico-ventas").html("");
    dissminSwal();
  }
};

/**
 * 📥 Exportar datos desde el servidor
 * @param {string} formato - csv, excel o pdf
 * @param {string} fechaInicio - fecha en formato YYYY-MM-DD
 * @param {string} fechaFin - fecha en formato YYYY-MM-DD
 */
const exportarDesdeServidor = async (formato, fechaInicio, fechaFin) => {
  try {
    showLoadingSwalAlert2(
      `<i class="fa-solid fa-spinner text-primary fa-spin"></i> Generando ${formato.toUpperCase()}...`,
      '<span class="text-primary">Por favor espere mientras se prepara el archivo</span>',
      false
    );

    const data = {
      link: link_,
      op: `export_${formato}`,  // op: export_csv, export_excel, export_pdf
      fechaInicio,
      fechaFin
    };

    // 🌐 Realizar solicitud con timeout extendido
    const resp = await fetch(link_, {
      method: 'POST',
      body: new URLSearchParams(data),
      timeout: 300000  // 5 minutos para exportación
    });

    if (!resp.ok) {
      throw new Error(`HTTP Error: ${resp.status}`);
    }

    // 💾 Obtener blob (archivo binario)
    const blob = await resp.blob();

    // 📝 Generar nombre de archivo con fecha actual
    const ahora = new Date().toISOString().split('T')[0];
    const nombreArchivo = `ventas_${ahora}_${Math.random().toString(36).substring(7)}.${formato === 'excel' ? 'xlsx' : formato}`;

    // 🔗 Crear URL de descarga
    const urlDescarga = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = urlDescarga;
    link.download = nombreArchivo;
    document.body.appendChild(link);
    link.click();

    // 🧹 Limpiar
    window.URL.revokeObjectURL(urlDescarga);
    document.body.removeChild(link);

    dissminSwal();
    Swal.fire("Éxito", `Archivo ${formato.toUpperCase()} descargado correctamente`, "success");

  } catch (e) {
    console.error(`Error en exportar ${formato}:`, e);
    dissminSwal();
    Swal.fire(
      "Error",
      `No se pudo generar el archivo ${formato.toUpperCase()}. ${e.message}`,
      "error"
    );
  }
};

/**
 * 📊 Exportar datos a Excel usando XLSX.js (cliente)
 * @param {Array} data - Array de objetos con los datos
 * @param {Array} campos - Array con nombres de campos
 * @param {Array} encabezados - Array con títulos de columnas
 */
const exportarExcelCliente = (data, campos, encabezados) => {
  try {
    showLoadingSwalAlert2(
      `<i class="fa-solid fa-download text-primary fa-spin"></i> Generando Excel...`,
      '<span class="text-primary">Por favor espere mientras se prepara el archivo</span>',
      false
    );

    // Transformar datos al formato que necesita XLSX
    const exportData = data.map(row => {
      const newRow = {};
      encabezados.forEach((header, idx) => {
        let valor = row[campos[idx]] || '';
        // Transformar documento_electronico
        if (campos[idx] === 'documento_electronico') {
          valor = valor === 'S' ? 'Electronico' : valor === 'N' ? 'No electronico' : valor;
        }
        newRow[header] = valor;
      });
      return newRow;
    });

    // Crear workbook
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.json_to_sheet(exportData);

    // Ajustar ancho de columnas
    const colWidths = encabezados.map(header => ({
      wch: header.length + 10
    }));
    ws['!cols'] = colWidths;

    // Agregar hoja al workbook
    XLSX.utils.book_append_sheet(wb, ws, "Ventas");

    // Descargar
    const ahora = new Date().toISOString().split('T')[0];
    XLSX.writeFile(wb, `ventas_${ahora}.xlsx`);

    dissminSwal();
    Swal.fire("Éxito", "Archivo Excel descargado correctamente", "success");

  } catch (e) {
    console.error("Error en exportarExcelCliente:", e);
    dissminSwal();
    Swal.fire(
      "Error",
      `No se pudo generar el archivo Excel. ${e.message}`,
      "error"
    );
  }
};

const imprimirVenta = (data) => {
  const sw = data.sw ?? 5;
  const id = data.id_transaccion;
  const titulo = `${data.tipo ?? 'Documento'} - ${data.numero ?? ''}`;
  console.log("Imprimiendo venta:", { data, sw, id, titulo });
  ImprimirDocumento(sw, id, titulo, true);
};
