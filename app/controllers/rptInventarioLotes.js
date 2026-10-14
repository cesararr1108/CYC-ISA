(() => {
'use strict';

const link_ = '../models/rptInventarioLotes.php';

// Control de peticiones: si llega una respuesta vieja se descarta
let consultaId = 0;
let debounceTimer = null;

// Configuración de columnas por modo de visualización
const CONFIG_VISTAS = {
  detalle: {
    op: 's_inventario_lote',
    campos: ['bodega_desc', 'proveedor', 'codigo', 'descripcion', 'descripcion_larga', 'codigo_cum',
      'costo_unitario', 'stock', 'costo_total', 'lote', 'vencimiento', 'ean', 'codigo_invima'],
    encabezados: ['Bodega', 'Proveedor', 'Código', 'Descripción', 'Descripción larga', 'CUM',
      'Costo unitario', 'Stock', 'Costo total', 'Lote', 'Vencimiento', 'EAN', 'INVIMA']
  },
  resumen: {
    op: 's_inventario_total',
    campos: ['bodega_desc', 'proveedor', 'codigo', 'descripcion', 'descripcion_larga', 'codigo_cum',
      'costo_unitario', 'stock', 'costo_total', 'ean', 'codigo_invima'],
    encabezados: ['Bodega', 'Proveedor', 'Código', 'Descripción', 'Descripción larga', 'CUM',
      'Costo unitario', 'Stock', 'Costo total', 'EAN', 'INVIMA']
  }
};

const DIAS_ALERTA_VENCIMIENTO = 90;

$(function () {
  // Cargar combos en paralelo
  Promise.all([listarAlmacenes(), listarMarcas()]).catch(console.error);

  $('#btn-busqueda').on('click', () => consultarInventario());

  // Enter ejecuta de inmediato
  $('#busqueda-descripcion').on('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      clearTimeout(debounceTimer);
      consultarInventario();
    }
  });

  // Cambios en filtros: consulta con pequeño retardo para agrupar clics seguidos
  $('#con_stock, #busqueda-institucional, #busqueda-regulado, #busqueda-controlado, #bodegas, #marcas, input[name="busqueda-lote"]')
    .on('change', () => consultarConRetardo());

  $('#btn-limpiar').on('click', limpiarFiltrosInventario);
  $('#btn-generar-tirilla').on('click', generarTirillaInventario);

  dissminSwal();
});

/* ============================================================
 * Utilidades
 * ============================================================ */
const consultarConRetardo = (ms = 350) => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => consultarInventario(), ms);
};

const obtenerFiltros = () => ({
  descripcion: $('#busqueda-descripcion').val().trim(),
  marca: $('#marcas').val() || '0',
  bodega: $('#bodegas').val() || '',
  optLote: $('input[name="busqueda-lote"]:checked').val() || 'detalle',
  stock: $('#con_stock').is(':checked'),
  institucional: $('#busqueda-institucional').is(':checked'),
  regulado: $('#busqueda-regulado').is(':checked'),
  controlado: $('#busqueda-controlado').is(':checked')
});

const formatoMoneda = (v) => '$' + (Number(v) || 0).toLocaleString('es-CO', { maximumFractionDigits: 0 });
const formatoNumero = (v) => (Number(v) || 0).toLocaleString('es-CO');

const estadoInicial = (mensaje, icono = 'bx-search-alt') => `
  <div class="text-center text-muted py-5">
    <i class="bx ${icono} bx-lg d-block mb-2 text-primary"></i>${mensaje}
  </div>`;

const limpiarResultadosInventario = (html = estadoInicial('Defina los filtros y presione <strong>Consultar</strong>')) => {
  $('#resumen-inventario').addClass('d-none').html('');
  $('#info-consulta').text('');
  $('#resultado-inventario').html(html);
};

/* ============================================================
 * Combos
 * ============================================================ */
const listarMarcas = async () => {
  try {
    const resp = await enviarPeticion({ link: '../models/Marcas.php', op: 'listar' });
    const marcas = (resp?.data || []).slice().sort((a, b) => a.descripcion.localeCompare(b.descripcion));
    let opciones = '<option value="0">Todas</option>';
    marcas.forEach(m => { opciones += `<option value="${m.id}">${m.descripcion}</option>`; });
    $('#marcas').html(opciones);
  } catch (e) {
    console.error(e);
    Swal.fire('Error', 'Error al cargar las marcas', 'error');
  }
};

const listarAlmacenes = async () => {
  try {
    const [data, resp] = await Promise.all([getRol(), bodegasUsuarios()]);
    let opciones = '';
    if (parseInt(data.rol) === 4) {
      opciones = '<option value="0">Todas las bodegas</option>';
    }
    resp.sort((a, b) => a.bodega.localeCompare(b.bodega));
    resp.forEach(d => { opciones += `<option value="${d.id}">${d.bodega} - ${d.descripcion}</option>`; });
    $('#bodegas').html(opciones);
  } catch (e) {
    console.error(e);
    Swal.fire('Error', 'Error al cargar las bodegas', 'error');
  }
};

/* ============================================================
 * Resumen (KPIs)
 * ============================================================ */
const kpi = (icono, etiqueta, valor) => `
  <div class="col-6 col-lg">
    <div class="h-100 inv-kpi">
      <div class="d-flex align-items-center gap-2 p-2">
        <span class="avatar-initial bg-label-primary"><i class="bx ${icono}"></i></span>
        <div>
          <div class="kpi-label">${etiqueta}</div>
          <p class="kpi-value">${valor}</p>
        </div>
      </div>
    </div>
  </div>`;

const pintarResumenInventario = (resp, modo) => {
  const costoTotal = resp.reduce((acc, r) => acc + (parseFloat(r.costo_total) || 0), 0);
  const unidades = resp.reduce((acc, r) => acc + (parseFloat(r.stock) || 0), 0);
  const referencias = new Set(resp.map(r => r.codigo)).size;

  let html = kpi('bx-dollar', 'Costo total', formatoMoneda(costoTotal))
    + kpi('bx-cube', 'Unidades', formatoNumero(unidades))
    + kpi('bx-barcode', 'Referencias', formatoNumero(referencias));

  // En modo lotes se agregan indicadores de vencimiento
  if (modo === 'detalle') {
    const hoy = new Date(); hoy.setHours(0, 0, 0, 0);
    const limite = new Date(hoy); limite.setDate(limite.getDate() + DIAS_ALERTA_VENCIMIENTO);
    let vencidos = 0, porVencer = 0;

    resp.forEach(r => {
      if (!r.vencimiento || !(parseFloat(r.stock) > 0)) return;
      const f = new Date(r.vencimiento + 'T00:00:00');
      if (isNaN(f)) return;
      if (f < hoy) vencidos++;
      else if (f <= limite) porVencer++;
    });

    html += kpi('bx-error-circle', 'Lotes vencidos', formatoNumero(vencidos))
      + kpi('bx-time-five', `Vencen ≤ ${DIAS_ALERTA_VENCIMIENTO} días`, formatoNumero(porVencer));
  }

  $('#resumen-inventario').html(html).removeClass('d-none');
};

/* ============================================================
 * Consulta principal
 * ============================================================ */
const consultarInventario = async () => {
  const filtros = obtenerFiltros();

  if (filtros.bodega === '') {
    Swal.fire('Atención', 'Seleccione una bodega', 'warning');
    return;
  }

  const vista = CONFIG_VISTAS[filtros.optLote] || CONFIG_VISTAS.detalle;
  const miConsulta = ++consultaId;

  showLoadingWithTetrisLogo('Consultando inventario', 'ISA Software');
  const inicio = performance.now();

  try {
    const resp = await enviarPeticion({ link: link_, op: vista.op, ...filtros });

    // Si mientras tanto se lanzó otra consulta, se ignora esta respuesta
    if (miConsulta !== consultaId) return;

    if (resp?.error) {
      limpiarResultadosInventario(ShowAlert(resp.message || 'Error al consultar', 'error'));
      return;
    }

    if (!Array.isArray(resp) || resp.length === 0) {
      limpiarResultadosInventario(estadoInicial('No se encontraron resultados con los filtros aplicados', 'bx-package'));
      return;
    }

    pintarResumenInventario(resp, filtros.optLote);

    const segundos = ((performance.now() - inicio) / 1000).toFixed(2);
    $('#info-consulta').text(`${formatoNumero(resp.length)} registros · ${segundos} s`);

    $('#resultado-inventario').html(`
      <table id="tb_referencias" class="table table-sm table-hover w-100">
        <thead class="table-primary"></thead>
        <tbody></tbody>
      </table>
    `);

    createDataTable(
      'tb_referencias',
      resp,
      vista.campos,
      vista.encabezados,
      '', '', false, '', '',
      true,                          // exportar
      ['costo_unitario', 'costo_total']
    );
  } catch (e) {
    console.error('❌ Error en consultarInventario:', e);
    if (miConsulta === consultaId) {
      limpiarResultadosInventario(ShowAlert('Error interno de la aplicación', 'error'));
    }
  } finally {
    if (miConsulta === consultaId) dissminSwal();
  }
};

/* ============================================================
 * Limpiar
 * ============================================================ */
const limpiarFiltrosInventario = () => {
  clearTimeout(debounceTimer);
  consultaId++; // invalida cualquier consulta en curso
  $('#busqueda-descripcion').val('');
  $('#marcas').val('0');
  $('#vis-detalle').prop('checked', true);
  $('#con_stock').prop('checked', true);
  $('#busqueda-institucional, #busqueda-regulado, #busqueda-controlado').prop('checked', false);
  limpiarResultadosInventario();
  dissminSwal();
  $('#busqueda-descripcion').trigger('focus');
};

/* ============================================================
 * Tirilla PDF
 * ============================================================ */
const generarTirillaInventario = () => {
  const f = obtenerFiltros();
  const params = new URLSearchParams({
    descripcion: f.descripcion,
    marca: f.marca,
    stock: f.stock ? 'S' : 'N',
    institucional: f.institucional ? 'S' : 'N',
    regulado: f.regulado ? 'S' : 'N',
    controlado: f.controlado ? 'S' : 'N',
    bodega: f.bodega,
    optLote: f.optLote
  });

  $('#modalDoc').remove();
  $('body').append(`
    <div class="modal fade" id="modalDoc" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="bx bx-receipt text-primary me-2"></i>Tirilla Kardex general</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body p-0">
            <div style="position:relative;width:100%;height:75vh;">
              <iframe id="iframeDocumento" width="100%" height="100%" frameborder="0"
                style="border:none;display:block;opacity:0;transition:opacity .2s ease;"></iframe>
              <div id="pdf-loading" class="bg-body"
                style="position:absolute;inset:0;z-index:10;display:flex;flex-direction:column;justify-content:center;align-items:center;">
                <div class="spinner-border text-primary mb-3" role="status" style="width:3rem;height:3rem;"></div>
                <div class="fw-semibold text-primary">Generando documento...</div>
                <small class="text-muted mt-1">Por favor espere</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  `);

  $('#modalDoc').modal('show').on('hidden.bs.modal', function () { $(this).remove(); });

  const iframe = document.getElementById('iframeDocumento');
  const loading = document.getElementById('pdf-loading');

  iframe.onload = () => {
    loading.style.display = 'none';
    iframe.style.opacity = '1';
  };
  iframe.onerror = () => {
    loading.innerHTML = `
      <div class="text-center">
        <i class="bx bx-error-circle bx-lg text-primary mb-2"></i>
        <div class="fw-semibold">No se pudo cargar el documento</div>
        <small class="text-muted">Intente nuevamente.</small>
      </div>`;
  };

  iframe.src = `../pdf/inventarioLotes.php?${params.toString()}`;
};

})();