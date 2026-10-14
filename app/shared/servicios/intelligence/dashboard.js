/* ==========================================================
   dashboard.js
   ----------------------------------------------------------
   Consume api_dashboard.php y pinta el dashboard con jQuery +
   Bootstrap + Chart.js.

   Los nombres de campo usados aquí (venta_total, impacto,
   costo_promedio, valor_inventario, nivel, codigo, etc.) vienen
   directo del volcado real de la API (datos_api.txt), así que
   ya NO se adivinan — se referencian de forma explícita.

   Contrato esperado de cada sección de "data" (ver api_dashboard.php):
     ventas.serie          -> [{fecha, venta, venta_iva}]
     ventas.resumen        -> {venta_total, venta_total_iva}
     ventas.comparativo    -> {periodo_actual{...}, periodo_anterior{...}, variacion, tendencia}
     ventas.analisis       -> {estado, nivel, mensaje}
     ventas.alertas        -> {total, por_nivel, por_codigo, items:[...]}

     productos.ventas_por_producto -> {total, items:[...]}
     productos.impacto             -> {venta_actual, venta_anterior, variacion,
                                        impacto_total, impacto_caidas, impacto_crecimientos,
                                        productos_caida, productos_crecimiento, productos_sin_ventas_actuales}
     productos.caida / crecimiento -> {total, items:[...]}
     productos.alertas             -> {total, por_nivel, por_codigo, items:[...]}

     inventario.resumen        -> {total_productos, valor_inventario, productos_sin_stock, ...}
     inventario.actual          -> {total, items:[...]}   (top 50 por valor_inventario)
     inventario.rotacion        -> {total, items:[...]}   (top 30 por rotación)
     inventario.sin_movimiento  -> {total, items:[...]}
     inventario.alertas         -> {total, por_nivel, por_codigo, items:[...]}  (excluye PRODUCTO_SIN_STOCK del listado)
   ========================================================== */

const API_URL = 'intelligenceAPI.php';
let charts = {};

$(function () {
  bindNav();
  bindFilters();
  cargarDashboard();
});

/* ---------------------------------------------------------
 * Navegación / filtros
 * ------------------------------------------------------- */
function bindNav() {
  $('.nav-sections .nav-link').on('click', function (e) {
    e.preventDefault();
    $('.nav-sections .nav-link').removeClass('active');
    $(this).addClass('active');
    const target = document.getElementById($(this).data('section'));
    if (target) target.scrollIntoView({ behavior: 'smooth' });
  });
}

function bindFilters() {
  const hoy = new Date();
  const primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
  $('#f-fecha-inicial').val(toISO(primerDia));
  $('#f-fecha-final').val(toISO(hoy));
  $('#f-bodega').val(0);

  $('#form-filtros').on('submit', function (e) {
    e.preventDefault();
    cargarDashboard();
  });
}
function toISO(d) { return d.toISOString().slice(0, 10); }

/* ---------------------------------------------------------
 * Carga principal
 * ------------------------------------------------------- */
function cargarDashboard() {
  const params = {
    fecha_inicial: $('#f-fecha-inicial').val(),
    fecha_final: $('#f-fecha-final').val(),
    bodega: $('#f-bodega').val() || 0,
  };

  $('#loading-bar').removeClass('d-none');
  $('#error-banner').addClass('d-none').text('');

  $.getJSON(API_URL, params)
    .done(function (resp) {
      if (!resp.ok) {
        mostrarError(resp.error || 'Error desconocido en el servidor.');
        setEstadoConexion(false);
        return;
      }
      setEstadoConexion(true);
      $('#empresa-actual').text(resp.meta?.empresa ?? 'Empresa');
      $('#ventas-rango').text(`${resp.meta.fecha_inicial} → ${resp.meta.fecha_final}`);
      pintarTodo(resp.data || {});
    })
    .fail(function (xhr) {
      setEstadoConexion(false);
      mostrarError('No se pudo conectar con ' + API_URL + ' (HTTP ' + xhr.status + '). Revisa que el endpoint esté en la misma carpeta y la sesión esté activa.');
    })
    .always(function () {
      $('#loading-bar').addClass('d-none');
    });
}

function setEstadoConexion(ok) {
  const el = $('#estado-conexion');
  el.removeClass('ok err').addClass(ok ? 'ok' : 'err');
  el.text(ok ? '● conectado' : '● sin datos');
}
function mostrarError(msg) { $('#error-banner').removeClass('d-none').text(msg); }

/* ---------------------------------------------------------
 * Orquesta el pintado de todas las secciones
 * ------------------------------------------------------- */
function pintarTodo(data) {
  const ventas = data.ventas || {};
  const productos = data.productos || {};
  const inventario = data.inventario || {};

  /* ================= VENTAS ================= */
  renderBannerAnalisis('#banner-analisis-ventas', ventas.analisis);
  renderKPIsConfig('#kpi-resumen-ventas', ventas.resumen, KPI_RESUMEN_VENTAS);
  renderSerieVentas('chart-ventas-periodo', ventas.serie);
  renderComparativoChart('chart-comparativo', ventas.comparativo);
  renderAlertGroup('#panel-alertas-ventas', '#count-alertas-ventas', ventas.alertas);

  /* ================= PRODUCTOS ================= */
  renderKPIsConfig('#kpi-impacto-productos', productos.impacto, KPI_IMPACTO_PRODUCTOS);
  renderImpactoChart('chart-impacto-productos', productos.impacto);
  renderTableConfig('#tabla-productos-caida', productos.caida, COLS_PRODUCTO_CAMBIO);
  setCount('#count-productos-caida', productos.caida);
  renderTableConfig('#tabla-productos-crecimiento', productos.crecimiento, COLS_PRODUCTO_CAMBIO);
  setCount('#count-productos-crecimiento', productos.crecimiento);
  renderTableConfig('#tabla-ventas-producto', productos.ventas_por_producto, COLS_VENTAS_PRODUCTO);
  setCount('#count-ventas-producto', productos.ventas_por_producto);
  renderAlertGroup('#panel-alertas-productos', '#count-alertas-productos', productos.alertas);

  /* ================= INVENTARIO ================= */
  renderKPIsConfig('#kpi-inventario-resumen', inventario.resumen, KPI_INVENTARIO_RESUMEN);
  renderRotacionChart('chart-rotacion', inventario.rotacion);
  renderDistribucionRotacionChart('chart-rotacion-distribucion', inventario.resumen);
  renderTableConfig('#tabla-inventario-actual', inventario.actual, COLS_INVENTARIO_ACTUAL);
  setCount('#count-inventario-actual', inventario.actual);
  renderTableConfig('#tabla-inventario-sin-movimiento', inventario.sin_movimiento, COLS_SIN_MOVIMIENTO);
  setCount('#count-inventario-sin-movimiento', inventario.sin_movimiento);
  renderAlertGroup('#panel-alertas-inventario', '#count-alertas-inventario', inventario.alertas);

  /* ================= ALERTAS CONSOLIDADAS (badge del sidebar) ================= */
  const totalAlertas = (ventas.alertas?.total || 0) + (productos.alertas?.total || 0) + (inventario.alertas?.total || 0);
  $('#badge-alertas-total').text(fmtNumber(totalAlertas));
}

/* ==========================================================
 * FORMATO
 * ========================================================== */
function fmtNumber(n) {
  if (n === null || n === undefined || n === '' || isNaN(n)) return '—';
  return new Intl.NumberFormat('es-CO', { maximumFractionDigits: 2 }).format(n);
}
function fmtMoney(n) {
  if (n === null || n === undefined || n === '' || isNaN(n)) return '—';
  return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(n);
}
function fmtPercent(n) {
  if (n === null || n === undefined || n === '' || isNaN(n)) return '—';
  const num = Number(n);
  const signo = num > 0 ? '+' : '';
  return `${signo}${fmtNumber(num)}%`;
}
function fmtValor(val, fmt) {
  if (fmt === 'money') return fmtMoney(val);
  if (fmt === 'percent') return fmtPercent(val);
  if (fmt === 'number') return fmtNumber(val);
  return val ?? '—';
}
function labelize(key) {
  return key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}
function isPlainObject(v) { return v !== null && typeof v === 'object' && !Array.isArray(v); }
function emptyState(msg) { return `<div class="empty-state">${msg}</div>`; }
function setCount(sel, wrapper) {
  if (!wrapper) { $(sel).text(''); return; }
  const mostrados = (wrapper.items || []).length;
  const total = wrapper.total ?? mostrados;
  $(sel).text(total > mostrados ? `mostrando ${fmtNumber(mostrados)} de ${fmtNumber(total)}` : `${fmtNumber(total)}`);
}

/* ==========================================================
 * CONFIGURACIÓN DE COLUMNAS / KPIs (a partir del esquema real)
 * ========================================================== */
const KPI_RESUMEN_VENTAS = [
  { key: 'venta_total', label: 'Venta total', fmt: 'money' },
  { key: 'venta_total_iva', label: 'Venta total (con IVA)', fmt: 'money' },
];

const KPI_IMPACTO_PRODUCTOS = [
  { key: 'venta_actual', label: 'Venta actual', fmt: 'money' },
  { key: 'venta_anterior', label: 'Venta anterior', fmt: 'money' },
  { key: 'variacion', label: 'Variación', fmt: 'percent' },
  { key: 'impacto_total', label: 'Impacto neto', fmt: 'money' },
  { key: 'productos_caida', label: 'Productos en caída', fmt: 'number' },
  { key: 'productos_crecimiento', label: 'Productos en crecimiento', fmt: 'number' },
  { key: 'productos_sin_ventas_actuales', label: 'Sin ventas actuales', fmt: 'number' },
];

const KPI_INVENTARIO_RESUMEN = [
  { key: 'total_productos', label: 'Total productos', fmt: 'number' },
  { key: 'valor_inventario', label: 'Valor de inventario', fmt: 'money' },
  { key: 'productos_sin_stock', label: 'Sin stock', fmt: 'number' },
  { key: 'productos_sin_rotacion', label: 'Sin rotación', fmt: 'number' },
  { key: 'productos_rotacion_muy_alta', label: 'Rotación muy alta', fmt: 'number' },
  { key: 'productos_rotacion_alta', label: 'Rotación alta', fmt: 'number' },
  { key: 'productos_rotacion_normal', label: 'Rotación normal', fmt: 'number' },
  { key: 'productos_rotacion_baja', label: 'Rotación baja', fmt: 'number' },
  { key: 'productos_sobreinventario', label: 'Sobreinventario', fmt: 'number' },
];

const COLS_VENTAS_PRODUCTO = [
  { key: 'producto', label: 'Producto' },
  { key: 'venta', label: 'Venta', fmt: 'money' },
  { key: 'costo', label: 'Costo', fmt: 'money' },
  { key: 'utilidad', label: 'Utilidad', fmt: 'money' },
  { key: 'margen', label: 'Margen', fmt: 'percent' },
  { key: 'cantidad', label: 'Cant.', fmt: 'number' },
];

const COLS_PRODUCTO_CAMBIO = [
  { key: 'producto', label: 'Producto' },
  { key: 'venta_actual', label: 'Venta actual', fmt: 'money' },
  { key: 'venta_anterior', label: 'Venta anterior', fmt: 'money' },
  { key: 'variacion', label: 'Variación', fmt: 'percent' },
  { key: 'impacto', label: 'Impacto', fmt: 'money' },
  { key: 'estado', label: 'Estado', fmt: 'estado' },
];

const COLS_INVENTARIO_ACTUAL = [
  { key: 'producto', label: 'Producto' },
  { key: 'costo_unitario', label: 'Costo unitario', fmt: 'money' },
  { key: 'costo_promedio', label: 'Costo promedio', fmt: 'money' },
  { key: 'stock_total', label: 'Stock', fmt: 'number' },
  { key: 'valor_inventario', label: 'Valor inventario', fmt: 'money' },
];

const COLS_SIN_MOVIMIENTO = [
  { key: 'producto', label: 'Producto' },
  { key: 'stock', label: 'Stock', fmt: 'number' },
  { key: 'costo_promedio', label: 'Costo promedio', fmt: 'money' },
  { key: 'valor_inventario', label: 'Valor inventario', fmt: 'money' },
];

// Colores de badge para valores de 'estado'
function tonoEstado(estado) {
  const e = String(estado || '').toUpperCase();
  if (e.includes('CAIDA') || e.includes('CRITIC') || e === 'SIN_STOCK') return 'tono-rojo';
  if (e.includes('CRECIMIENTO')) return 'tono-verde';
  if (e.includes('SIN_MOVIMIENTO') || e.includes('SIN_VENTAS')) return 'tono-ambar';
  return 'tono-gris';
}

/* ==========================================================
 * KPI CARDS (config-driven)
 * ========================================================== */
function renderKPIsConfig(container, obj, config) {
  const $c = $(container).empty();
  if (!obj) { $c.append(emptyState('Sin datos disponibles todavía.')); return; }
  config.forEach(({ key, label, fmt }) => {
    if (obj[key] === undefined) return;
    $c.append(`
      <div class="kpi-card">
        <div class="kpi-label">${label}</div>
        <div class="kpi-value">${fmtValor(obj[key], fmt)}</div>
      </div>
    `);
  });
}

/* ==========================================================
 * BANNER de análisis (estado / nivel / mensaje)
 * ========================================================== */
function renderBannerAnalisis(container, analisis) {
  const $c = $(container).empty();
  if (!analisis || !analisis.mensaje) { $c.hide(); return; }
  $c.show();
  const nivel = String(analisis.nivel || 'normal').toLowerCase();
  $c.removeClass().addClass('status-banner nivel-' + nivel);
  const icono = nivel === 'critica' ? 'fa-circle-exclamation' : (nivel === 'advertencia' ? 'fa-triangle-exclamation' : 'fa-circle-check');
  $c.html(`<i class="fa-solid ${icono}"></i> <span><strong>${labelize(analisis.estado || '')}</strong> — ${analisis.mensaje}</span>`);
}

/* ==========================================================
 * TABLA CONFIG-DRIVEN
 * ========================================================== */
function renderTableConfig(container, wrapper, columns) {
  const $c = $(container).empty();
  const items = wrapper?.items || [];
  if (!items.length) { $c.append(emptyState('Sin registros.')); return; }

  const thead = '<thead><tr>' + columns.map((c) => {
    const numeric = c.fmt && c.fmt !== 'estado';
    return `<th class="${numeric ? 'numeric' : ''}">${c.label}</th>`;
  }).join('') + '</tr></thead>';

  const tbody = '<tbody>' + items.map((row) => {
    return '<tr>' + columns.map((c) => {
      const val = row[c.key];
      if (c.fmt === 'estado') {
        return `<td><span class="badge-estado ${tonoEstado(val)}">${labelize(String(val ?? ''))}</span></td>`;
      }
      const numeric = c.fmt && c.fmt !== 'estado';
      return `<td class="${numeric ? 'numeric' : ''}">${fmtValor(val, c.fmt)}</td>`;
    }).join('') + '</tr>';
  }).join('') + '</tbody>';

  $c.append(`<table class="table table-sm table-hover align-middle mb-0">${thead}${tbody}</table>`);
}

/* ==========================================================
 * ALERTAS (tipo, codigo, nivel, titulo, mensaje, ...)
 * ========================================================== */
function renderAlertGroup(containerSel, countSel, wrapper) {
  const $c = $(containerSel).empty();
  $(countSel).text(wrapper ? fmtNumber(wrapper.total) : '');

  if (!wrapper || !wrapper.total) {
    $c.append(`<div class="empty-state"><i class="fa-solid fa-circle-check text-success"></i> Sin alertas activas.</div>`);
    return;
  }

  // Chips de conteo por código (incluye códigos excluidos del listado, p.ej. PRODUCTO_SIN_STOCK)
  if (wrapper.por_codigo && Object.keys(wrapper.por_codigo).length) {
    const mostrados = new Set((wrapper.items || []).map((a) => a.codigo));
    const $chips = $('<div class="chip-row"></div>');
    Object.entries(wrapper.por_codigo).forEach(([codigo, count]) => {
      const oculto = !mostrados.has(codigo);
      $chips.append(`<span class="chip${oculto ? ' muted' : ''}">${labelize(codigo)}: <strong>${fmtNumber(count)}</strong>${oculto ? ' (no listadas)' : ''}</span>`);
    });
    $c.append($chips);
  }

  (wrapper.items || []).forEach((a) => {
    const nivel = String(a.nivel || 'info').toLowerCase();
    $c.append(`
      <div class="alerta-item level-${nivel}">
        <div class="a-title">${a.titulo || a.codigo || 'Alerta'}</div>
        ${a.mensaje ? `<div class="a-meta">${a.mensaje}</div>` : ''}
      </div>
    `);
  });
}

/* ==========================================================
 * CHARTS
 * ========================================================== */
function destroyChart(id) {
  if (charts[id]) { charts[id].destroy(); delete charts[id]; }
}

// Ventas: serie diaria/semanal -> línea con 2 datasets (venta, venta_iva)
function renderSerieVentas(canvasId, serie) {
  const el = document.getElementById(canvasId);
  if (!el) return;
  destroyChart(canvasId);
  if (!serie || !serie.length) {
    el.parentElement.innerHTML = emptyState('Sin datos de ventas en el rango seleccionado.');
    return;
  }
  charts[canvasId] = new Chart(el, {
    type: 'line',
    data: {
      labels: serie.map((r) => r.fecha),
      datasets: [
        {
          label: 'Venta',
          data: serie.map((r) => r.venta),
          borderColor: '#1F6F8B',
          backgroundColor: 'rgba(31,111,139,0.12)',
          fill: true, tension: 0.35, pointRadius: 2,
        },
        {
          label: 'Venta con IVA',
          data: serie.map((r) => r.venta_iva),
          borderColor: '#E0982B',
          backgroundColor: 'transparent',
          borderDash: [4, 3],
          fill: false, tension: 0.35, pointRadius: 2,
        },
      ],
    },
    options: {
      responsive: true,
      plugins: { legend: { display: true, labels: { boxWidth: 10, font: { size: 11 } } } },
      scales: { y: { ticks: { callback: (v) => fmtNumber(v) } } },
    },
  });
}

// Comparativo: período actual vs. anterior (2 barras)
function renderComparativoChart(canvasId, comparativo) {
  const el = document.getElementById(canvasId);
  if (!el) return;
  destroyChart(canvasId);
  const actual = comparativo?.periodo_actual;
  const anterior = comparativo?.periodo_anterior;
  if (!actual || !anterior) {
    el.parentElement.innerHTML = emptyState('Sin datos comparativos.');
    return;
  }
  charts[canvasId] = new Chart(el, {
    type: 'bar',
    data: {
      labels: [
        `Anterior\n${anterior.fecha_inicio} a ${anterior.fecha_fin}`,
        `Actual\n${actual.fecha_inicio} a ${actual.fecha_fin}`,
      ],
      datasets: [{
        label: 'Venta',
        data: [Number(anterior.venta) || 0, Number(actual.venta) || 0],
        backgroundColor: ['#B9C3CB', comparativo.tendencia === 'DISMINUCION' ? '#C0392B' : '#2E8B57'],
        borderRadius: 6,
      }],
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: { y: { ticks: { callback: (v) => fmtNumber(v) } } },
    },
  });
}

// Impacto de productos: caídas vs. crecimientos (2 barras horizontales)
function renderImpactoChart(canvasId, impacto) {
  const el = document.getElementById(canvasId);
  if (!el) return;
  destroyChart(canvasId);
  if (!impacto || impacto.impacto_caidas === undefined) {
    el.parentElement.innerHTML = emptyState('Sin datos de impacto.');
    return;
  }
  charts[canvasId] = new Chart(el, {
    type: 'bar',
    data: {
      labels: ['Impacto por caídas', 'Impacto por crecimientos'],
      datasets: [{
        data: [Number(impacto.impacto_caidas) || 0, Number(impacto.impacto_crecimientos) || 0],
        backgroundColor: ['#C0392B', '#2E8B57'],
        borderRadius: 6,
      }],
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      plugins: { legend: { display: false } },
      scales: { x: { ticks: { callback: (v) => fmtNumber(v) } } },
    },
  });
}

// Rotación: top 30 productos por índice de rotación
function renderRotacionChart(canvasId, wrapper) {
  const el = document.getElementById(canvasId);
  if (!el) return;
  destroyChart(canvasId);
  const items = wrapper?.items || [];
  if (!items.length) {
    el.parentElement.innerHTML = emptyState('Sin datos de rotación.');
    return;
  }
  const top = items.slice(0, 15); // 15 en el gráfico para que no se amontone
  charts[canvasId] = new Chart(el, {
    type: 'bar',
    data: {
      labels: top.map((r) => r.producto),
      datasets: [{
        label: 'Rotación',
        data: top.map((r) => Number(r.rotacion) || 0),
        backgroundColor: '#1F6F8B',
        borderRadius: 6,
      }],
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      plugins: { legend: { display: false } },
    },
  });
}

// Distribución de productos por nivel de rotación (dona) a partir del resumen
function renderDistribucionRotacionChart(canvasId, resumen) {
  const el = document.getElementById(canvasId);
  if (!el) return;
  destroyChart(canvasId);
  if (!resumen) { el.parentElement.innerHTML = emptyState('Sin datos.'); return; }

  const data = [
    ['Sin stock', resumen.productos_sin_stock, '#C0392B'],
    ['Sin rotación', resumen.productos_sin_rotacion, '#B9C3CB'],
    ['Rotación baja', resumen.productos_rotacion_baja, '#E0982B'],
    ['Rotación normal', resumen.productos_rotacion_normal, '#1F6F8B'],
    ['Rotación alta', resumen.productos_rotacion_alta, '#2E8B57'],
    ['Rotación muy alta', resumen.productos_rotacion_muy_alta, '#1D6B41'],
  ].filter(([, v]) => typeof v === 'number' && v > 0);

  if (!data.length) { el.parentElement.innerHTML = emptyState('Sin datos.'); return; }

  charts[canvasId] = new Chart(el, {
    type: 'doughnut',
    data: {
      labels: data.map((d) => d[0]),
      datasets: [{ data: data.map((d) => d[1]), backgroundColor: data.map((d) => d[2]) }],
    },
    options: {
      responsive: true,
      plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
    },
  });
}
