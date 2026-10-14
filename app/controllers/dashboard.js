const id_modulo = '77';
let permisos = [];
let promocionesVigentesData = [];
let stockMinimoData = [];
let facturasVencidasData = [];
let proximosVencerData = [];

// Un permiso nulo significa "visible para todos"; si no, debe estar activo en permisos.
const tienePermiso = (permiso) => !permiso || !!(permisos && permisos[permiso]);

'use strict';
const validarSuscripcionUI = (empresa) => {

  if (!empresa || empresa.length === 0) return;

  const data = empresa[0];

  const hoyStr = new Date().toISOString().split('T')[0];

  const hoy = new Date(hoyStr);
  const vencimiento = new Date(data.fecha_vencimiento);

  const diasGracia = parseInt(data.dias_gracia);

  const vencimientoConGracia = new Date(data.fecha_vencimiento);
  vencimientoConGracia.setDate(vencimientoConGracia.getDate() + diasGracia);

  const diffDias = Math.floor(
    (vencimiento - hoy) / (1000 * 60 * 60 * 24)
  );

  let tipo = null;
  let mensaje = '';
  let color = '';
  let icono = '';

  if (hoy >= vencimientoConGracia) {
    tipo = 'SUSPENDIDO';
    color = 'danger';
    icono = '⛔';
    mensaje = `Tu suscripción venció el ${data.fecha_vencimiento}. El servicio será suspendido el dia de hoy, realiza tu pago lo mas pronto posible para que puedas seguir disfrutando de nuestros servicios.`;
  }
  else if (hoy > vencimiento) {
    tipo = 'GRACIA';
    color = 'warning';
    icono = '⚠';
    mensaje = `Tu suscripción venció el ${data.fecha_vencimiento}. Estás en período de gracia (${diasGracia} días).`;
  }
  else if (diffDias <= 5) {
    tipo = 'PROXIMO';
    color = 'warning';
    icono = '⚠';
    if (diffDias === 0) {
      mensaje = `Tu suscripción vence hoy.`;
    } else {
      mensaje = `Tu suscripción vence en ${diffDias} día(s).`;
    }
  }

  if (!tipo) return;

  mostrarCardSuscripcion(color, icono, mensaje);
};
const mostrarCardSuscripcion = (color, icono, mensaje) => {

  const cardHTML = `
    <div class="card border-${color} mb-3">
      <div class="card-body">
        <h5 class="card-title text-${color}">
          ${icono} Estado de Suscripción
        </h5>
        <p class="mb-2">${mensaje}</p>
      </div>
    </div>
  `;

  $("#contenedor-suscripcion").html(cardHTML);
};
/* ---------------------------------------------------------------
 * Promociones vigentes.
 * dias_restantes viene calculado en SQL (0 = termina hoy).
 * --------------------------------------------------------------- */
const DIAS_PROMO_POR_TERMINAR = 3;

const textoDiasPromo = d => d <= 0 ? 'Termina hoy' : d === 1 ? 'Termina mañana' : `${d} días`;
const colorPromo = d => d <= DIAS_PROMO_POR_TERMINAR ? 'warning' : 'primary';

const aplicaPromo = p => {
  const partes = [];
  const refs = parseInt(p.cantidad_referencias, 10) || 0, marcas = parseInt(p.cantidad_marcas, 10) || 0;
  if (refs) partes.push(`${refs} referencia${refs === 1 ? '' : 's'}`);
  if (marcas) partes.push(`${marcas} marca${marcas === 1 ? '' : 's'}`);
  return partes.length ? partes.join(' · ') : (p.tipo_aplicacion ? escHtml(p.tipo_aplicacion) : 'General');
};

const mostrarDetallePromociones = () => {
  if (!promocionesVigentesData || promocionesVigentesData.length === 0) {
    Swal.fire('Sin promociones', 'No hay promociones vigentes en este momento', 'info');
    return;
  }

  const datos = [...promocionesVigentesData].sort((a, b) => a.diasRestantes - b.diasRestantes);
  const porTerminar = datos.filter(p => p.diasRestantes <= DIAS_PROMO_POR_TERMINAR).length;
  const descMax = Math.max(...datos.map(p => parseFloat(p.porcentaje_descuento) || 0));

  const modalId = 'modalPromociones';
  const modalHtml = `
    <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header border-bottom">
            <h5 class="modal-title" id="${modalId}Label">
              <i class="bx bxs-gift"></i> Promociones vigentes
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3 mb-3">
              <div class="col-6 col-md-4"><small class="text-muted d-block">Activas</small><h6 class="mb-0 text-primary">${datos.length}</h6></div>
              <div class="col-6 col-md-4"><small class="text-muted d-block">Terminan en ${DIAS_PROMO_POR_TERMINAR} días o menos</small><h6 class="mb-0 ${porTerminar ? 'text-warning' : ''}">${porTerminar}</h6></div>
              <div class="col-12 col-md-4"><small class="text-muted d-block">Mayor descuento</small><h6 class="mb-0">${descMax}%</h6></div>
            </div>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
              <div class="btn-group btn-group-sm" role="group">
                <input type="radio" class="btn-check" name="promoFiltro" id="promoTodas" value="todas" checked>
                <label class="btn btn-outline-primary" for="promoTodas">Todas</label>
                <input type="radio" class="btn-check" name="promoFiltro" id="promoTerminan" value="terminan">
                <label class="btn btn-outline-primary" for="promoTerminan">Por terminar</label>
              </div>
              <div class="input-group input-group-sm" style="max-width: 260px;">
                <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="search" class="form-control" id="promoBuscar" placeholder="Nombre, lista o bodega">
              </div>
            </div>
            <div class="table-responsive border rounded" style="max-height: 50vh;">
              <table class="table table-sm table-hover align-middle mb-0 tabla-primary" id="tblPromociones">
                <thead>
                  <tr>
                    <th>Promoción</th>
                    <th class="text-end">Descuento</th>
                    <th>Aplica a</th>
                    <th>Lista / Bodega</th>
                    <th>Vigencia</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
          <div class="modal-footer border-top">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
  `;

  const oldModal = document.getElementById(modalId);
  if (oldModal) oldModal.remove();
  document.body.insertAdjacentHTML('beforeend', modalHtml);

  const render = () => {
    const filtro = $('input[name="promoFiltro"]:checked').val();
    const q = String($('#promoBuscar').val() || '').toLowerCase().trim();
    const lista = datos.filter(p => (filtro === 'todas' || p.diasRestantes <= DIAS_PROMO_POR_TERMINAR) &&
      (!q || `${p.nombre} ${p.desc_lista || ''} ${p.bodega_desc || ''} ${p.observaciones || ''}`.toLowerCase().includes(q)));

    $('#tblPromociones tbody').html(lista.map(p => `
      <tr>
        <td>
          <span class="fw-semibold">${escHtml(p.nombre)}</span>
          ${p.observaciones ? `<small class="d-block text-muted">${escHtml(p.observaciones)}</small>` : ''}
        </td>
        <td class="text-end fw-semibold text-primary">${parseFloat(p.porcentaje_descuento) || 0}%</td>
        <td>${aplicaPromo(p)}</td>
        <td>
          ${escHtml(p.desc_lista || 'Todas')}
          <small class="d-block text-muted">${escHtml(p.bodega_desc || '')}</small>
        </td>
        <td class="text-nowrap">
          <small class="d-block text-muted">${fechaCorta(p.fecha_inicio)} – ${fechaCorta(p.fecha_final)}</small>
          <span class="badge bg-label-${colorPromo(p.diasRestantes)}">${textoDiasPromo(p.diasRestantes)}</span>
        </td>
      </tr>`).join('') || '<tr><td colspan="5" class="text-center text-muted py-4">Sin promociones para este filtro</td></tr>');
  };

  $(`#${modalId}`).on('change', 'input[name="promoFiltro"]', render).on('input', '#promoBuscar', render);
  render();
  new bootstrap.Modal(document.getElementById(modalId)).show();
};

const mostrarPromocionesVigentes = async () => {
  try {
    const promociones = await promocionesVigentes();
    if (promociones && Array.isArray(promociones) && promociones.length > 0) {
      promocionesVigentesData = promociones.map(p => ({ ...p, diasRestantes: Math.max(0, parseInt(p.dias_restantes, 10) || 0) }));

      const total = promocionesVigentesData.length;
      const porTerminar = promocionesVigentesData.filter(p => p.diasRestantes <= DIAS_PROMO_POR_TERMINAR).length;
      const proxima = Math.min(...promocionesVigentesData.map(p => p.diasRestantes));

      const resumen = porTerminar
        ? `<small class="text-warning fw-medium"><i class="icon-base bx bx-time-five"></i> ${porTerminar} por terminar · ${textoDiasPromo(proxima).toLowerCase()}</small>`
        : `<small class="text-primary fw-medium"><i class="icon-base bx bx-check-circle"></i> Próxima termina en ${proxima} días</small>`;

      $("#dashboard-promociones-vigentes").html(`
        <div class="card h-100">
          <div class="card-body">
            <div class="card-title d-flex align-items-start justify-content-between mb-4">
              <div class="avatar flex-shrink-0">
                <span class="avatar-initial rounded bg-primary text-white"><i class="bx bxs-gift fs-4"></i></span>
              </div>
              <div class="dropdown">
                <button class="btn p-0" type="button" id="cardPromo" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardPromo">
                  <a class="dropdown-item" href="javascript:void(0);" onclick="mostrarDetallePromociones()"><i class="fa-solid fa-list"></i>&nbsp;Ver detalle</a>
                </div>
              </div>
            </div>
            <p class="mb-1">Promociones Activas</p>
            <h4 class="card-title mb-3">${total}</h4>
            ${resumen}
          </div>
        </div>
      `);
    } else {
      promocionesVigentesData = [];
      $("#dashboard-promociones-vigentes").html(`
        <div class="card h-100">
          <div class="card-body">
            <div class="card-title d-flex align-items-start justify-content-between mb-4">
              <div class="avatar flex-shrink-0">
                <span class="avatar-initial rounded bg-label-secondary"><i class="bx bx-gift fs-4"></i></span>
              </div>
            </div>
            <p class="mb-1">Promociones Activas</p>
            <h4 class="card-title mb-3">0</h4>
            <small class="text-muted fw-medium"><i class="icon-base bx bx-info-circle"></i> Sin promociones vigentes</small>
          </div>
        </div>
      `);
    }
  } catch (error) {
    console.error('Error al obtener promociones vigentes:', error);
    $("#dashboard-promociones-vigentes").html(`
      <div class="card h-100">
        <div class="card-body">
          <div class="card-title d-flex align-items-start justify-content-between mb-4">
            <div class="avatar flex-shrink-0">
              <span class="avatar-initial rounded" style="background-color: #C62828; color: white;"><i class="bx bx-error-circle fs-4"></i></span>
            </div>
          </div>
          <p class="mb-1">Promociones Activas</p>
          <h4 class="card-title mb-3 text-danger">Error</h4>
          <small class="text-muted fw-medium">No se pudieron cargar las promociones</small>
        </div>
      </div>
    `);
  }
}
const mostrarEstadoServicio = async () => {
  try {
    const estado = await estadoServicio();
    if (estado && Array.isArray(estado) && estado.length > 0) {
      validarSuscripcionUI(estado);
    }
  } catch (error) {
    console.error('Error al obtener estado del servicio:', error);
  }
}
/* ---------------------------------------------------------------
 * Stock por debajo del mínimo.
 * Nivel = stock / mínimo:  agotada (stock <= 0), crítica (< 50 % del mínimo),
 * baja (entre 50 % y el mínimo).
 * --------------------------------------------------------------- */
const nivelStock = r => {
  const stock = parseFloat(r.stock) || 0, minimo = parseFloat(r.stock_minimo) || 0;
  const pct = minimo > 0 ? Math.max(stock, 0) / minimo * 100 : 0;
  const estado = stock <= 0 ? 'agotada' : pct < 50 ? 'critica' : 'baja';
  return { stock, minimo, pct, faltante: Math.max(minimo - stock, 0), estado };
};
const ESTADO_STOCK = {
  agotada: { texto: 'Agotada', color: 'danger' },
  critica: { texto: 'Crítica', color: 'warning' },
  baja:    { texto: 'Baja', color: 'secondary' }
};
const fmtCant = n => Number(n).toLocaleString('es-CO', { maximumFractionDigits: 2 });

const mostrarDetalleStockMinimo = () => {
  if (!stockMinimoData || stockMinimoData.length === 0) {
    Swal.fire('Sin alertas', 'No hay referencias por debajo del stock mínimo', 'info');
    return;
  }

  const modalId = 'modalStockMinimo';
  const modalHtml = `
    <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header border-bottom">
            <h5 class="modal-title" id="${modalId}Label">
              <i class="fa-solid fa-triangle-exclamation"></i> Stock por debajo del mínimo
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3 mb-3" id="stkResumen"></div>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
              <div class="btn-group btn-group-sm" role="group">
                <input type="radio" class="btn-check" name="stkFiltro" id="stkTodas" value="todas" checked>
                <label class="btn btn-outline-primary" for="stkTodas">Todas</label>
                <input type="radio" class="btn-check" name="stkFiltro" id="stkAgotadas" value="agotada">
                <label class="btn btn-outline-primary" for="stkAgotadas">Agotadas</label>
                <input type="radio" class="btn-check" name="stkFiltro" id="stkCriticas" value="critica">
                <label class="btn btn-outline-primary" for="stkCriticas">Críticas</label>
                <input type="radio" class="btn-check" name="stkFiltro" id="stkBajas" value="baja">
                <label class="btn btn-outline-primary" for="stkBajas">Bajas</label>
              </div>
              <div class="input-group input-group-sm" style="max-width: 260px;">
                <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="search" class="form-control" id="stkBuscar" placeholder="Código, descripción o marca">
              </div>
            </div>
            <div class="table-responsive border rounded" style="max-height: 50vh;">
              <table class="table table-sm table-hover align-middle mb-0 tabla-primary" id="tblStockMinimo">
                <thead>
                  <tr>
                    <th>Referencia</th>
                    <th class="text-end">Stock</th>
                    <th class="text-end">Mínimo</th>
                    <th class="text-end">Faltante</th>
                    <th style="min-width: 150px;">Nivel</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
            <div class="form-text">Faltante = unidades necesarias para llegar al stock mínimo. Nivel = stock actual frente al mínimo.</div>
          </div>
          <div class="modal-footer border-top">
            <button type="button" class="btn btn-sm btn-outline-primary" id="stkExportar"><i class="fa-solid fa-download"></i> Descargar CSV</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
  `;

  const oldModal = document.getElementById(modalId);
  if (oldModal) oldModal.remove();
  document.body.insertAdjacentHTML('beforeend', modalHtml);

  // Más urgente primero: menor nivel de stock frente al mínimo
  const datos = stockMinimoData.map(r => ({ ...r, ...nivelStock(r) })).sort((a, b) => a.pct - b.pct || b.faltante - a.faltante);
  const cuenta = e => datos.filter(r => r.estado === e).length;

  $('#stkResumen').html(`
    <div class="col-6 col-md-3"><small class="text-muted d-block">Referencias</small><h6 class="mb-0">${datos.length}</h6></div>
    <div class="col-6 col-md-3"><small class="text-muted d-block">Agotadas</small><h6 class="mb-0 text-danger">${cuenta('agotada')}</h6></div>
    <div class="col-6 col-md-3"><small class="text-muted d-block">Críticas (&lt; 50 %)</small><h6 class="mb-0 text-warning">${cuenta('critica')}</h6></div>
    <div class="col-6 col-md-3"><small class="text-muted d-block">Bajas</small><h6 class="mb-0">${cuenta('baja')}</h6></div>`);

  const filtrados = () => {
    const filtro = $('input[name="stkFiltro"]:checked').val();
    const q = String($('#stkBuscar').val() || '').toLowerCase().trim();
    return datos.filter(r => (filtro === 'todas' || r.estado === filtro) &&
      (!q || `${r.codigo} ${r.descripcion} ${r.marca || ''}`.toLowerCase().includes(q)));
  };

  const render = () => {
    const lista = filtrados();
    $('#tblStockMinimo tbody').html(lista.map(r => {
      const est = ESTADO_STOCK[r.estado];
      return `
        <tr>
          <td>
            <span class="fw-semibold">${escHtml(r.codigo)}</span>
            <small class="d-block text-muted">${escHtml(r.descripcion)}${r.marca ? ' · ' + escHtml(r.marca) : ''}</small>
          </td>
          <td class="text-end ${r.stock <= 0 ? 'text-danger fw-semibold' : ''}">${fmtCant(r.stock)}</td>
          <td class="text-end">${fmtCant(r.minimo)}</td>
          <td class="text-end fw-semibold">${fmtCant(r.faltante)}</td>
          <td>
            <div class="d-flex align-items-center gap-2">
              <div class="progress flex-grow-1" style="height:6px">
                <div class="progress-bar bg-${est.color}" style="width:${Math.min(r.pct, 100)}%"></div>
              </div>
              <span class="badge bg-label-${est.color}" style="min-width:4.2rem">${est.texto}</span>
            </div>
          </td>
        </tr>`;
    }).join('') || '<tr><td colspan="5" class="text-center text-muted py-4">Sin referencias para este filtro</td></tr>');
  };

  // Descarga lo que se está viendo (filtro + búsqueda) para armar el pedido
  const exportar = () => {
    const lineas = ['codigo;descripcion;marca;stock;minimo;faltante;estado'].concat(
      filtrados().map(r => [r.codigo, r.descripcion, r.marca || '', r.stock, r.minimo, r.faltante, ESTADO_STOCK[r.estado].texto]
        .map(v => String(v ?? '').replace(/[;\r\n]/g, ' ')).join(';')));
    const blob = new Blob(['﻿' + lineas.join('\r\n')], { type: 'text/csv;charset=utf-8' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'stock_por_debajo_minimo.csv';
    a.click();
    setTimeout(() => URL.revokeObjectURL(a.href), 1000);
  };

  $(`#${modalId}`).on('change', 'input[name="stkFiltro"]', render)
    .on('input', '#stkBuscar', render)
    .on('click', '#stkExportar', exportar);
  render();
  new bootstrap.Modal(document.getElementById(modalId)).show();
};

const mostrarReferenciasStock = async () => {
  try {
    const referencias = await referenciasStockMinimo();
    if (referencias && Array.isArray(referencias) && referencias.length > 0) {
      stockMinimoData = referencias;
      const niveles = referencias.map(nivelStock);
      const agotadas = niveles.filter(n => n.estado === 'agotada').length;
      const criticas = niveles.filter(n => n.estado === 'critica').length;

      // Rojo si hay agotadas, naranja si solo hay referencias bajas
      const colorAvatar = agotadas ? '#D32F2F' : '#F57C00';
      const resumen = agotadas
        ? `<small class="text-danger fw-medium"><i class="icon-base bx bx-alert-circle"></i> ${agotadas} agotadas · ${criticas} críticas</small>`
        : `<small class="text-warning fw-medium"><i class="icon-base bx bx-info-circle"></i> ${criticas} críticas · ${referencias.length - criticas} bajas</small>`;

      $("#dashboard-stock-minimo").html(`
        <div class="card h-100">
          <div class="card-body">
            <div class="card-title d-flex align-items-start justify-content-between mb-4">
              <div class="avatar flex-shrink-0">
                <span class="avatar-initial rounded" style="background-color: ${colorAvatar}; color: white;"><i class="fa-solid fa-triangle-exclamation fs-4"></i></span>
              </div>
              <div class="dropdown">
                <button class="btn p-0" type="button" id="cardStock" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardStock">
                  <a class="dropdown-item" href="javascript:void(0);" onclick="mostrarDetalleStockMinimo()"><i class="fa-solid fa-list"></i>&nbsp;Ver detalle</a>
                </div>
              </div>
            </div>
            <p class="mb-1">Stock por Debajo del Mínimo</p>
            <h4 class="card-title mb-3">${referencias.length}</h4>
            ${resumen}
          </div>
        </div>
      `);
    } else {
      stockMinimoData = [];
      $("#dashboard-stock-minimo").html(`
        <div class="card h-100">
          <div class="card-body">
            <div class="card-title d-flex align-items-start justify-content-between mb-4">
              <div class="avatar flex-shrink-0">
                <span class="avatar-initial rounded" style="background-color: #43A047; color: white;"><i class="fa-solid fa-check-circle fs-4"></i></span>
              </div>
            </div>
            <p class="mb-1">Stock por Debajo del Mínimo</p>
            <h4 class="card-title mb-3">0</h4>
            <small class="text-success fw-medium"><i class="icon-base bx bx-check"></i> Todo en orden</small>
          </div>
        </div>
      `);
    }
  } catch (error) {
    console.error('Error al obtener referencias con stock mínimo:', error);
    $("#dashboard-stock-minimo").html(`
      <div class="card h-100">
        <div class="card-body">
          <div class="card-title d-flex align-items-start justify-content-between mb-4">
            <div class="avatar flex-shrink-0">
              <span class="avatar-initial rounded" style="background-color: #C62828; color: white;"><i class="bx bx-error-circle fs-4"></i></span>
            </div>
          </div>
          <p class="mb-1">Stock por Debajo del Mínimo</p>
          <h4 class="card-title mb-3 text-danger">Error</h4>
          <small class="text-muted fw-medium">No se pudieron cargar los datos</small>
        </div>
      </div>
    `);
  }
}
/* ---------------------------------------------------------------
 * Período (año / mes) seleccionado en el filtro de la vista.
 * Si por alguna razón el filtro no existe, se usa el mes actual.
 * --------------------------------------------------------------- */
const periodoActual = () => {
  if (window.DashboardPeriodo) return window.DashboardPeriodo.get();
  const hoy = new Date();
  return { anio: hoy.getFullYear(), mes: hoy.getMonth() + 1 };
};
// Evita pintar la respuesta de un período anterior si el usuario ya cambió de filtro
const periodoVigente = (anio, mes) => {
  const p = periodoActual();
  return p.anio === anio && p.mes === mes;
};

const mostrarVentas = async (p = periodoActual()) => {
  try {
    const ventas = await ventasTotales(p.anio, p.mes);
    if (!periodoVigente(p.anio, p.mes)) return;
    const hayDatos = ventas && Array.isArray(ventas) && ventas.length > 0;
    $("#dashboard-ventas").text(formatNum(hayDatos ? (ventas[0].venta_iva || 0) : 0, '$'));
  } catch (error) {
    console.error('Error al obtener ventas totales:', error);
    if (periodoVigente(p.anio, p.mes)) $("#dashboard-ventas").text('—');
  }
}
const limpiarGrafico = (instancia, selector, mensaje) => {
  if (window[instancia]) {
    window[instancia].destroy();
    window[instancia] = null;
  }
  $(selector).html(`<div class="d-flex align-items-center justify-content-center text-muted py-5">${mensaje}</div>`);
};
const limpiarGraficoVentas = (mensaje) => limpiarGrafico('chartVentas', '#totalRevenueChart', mensaje);
// El gráfico es anual: solo depende del año
const mostrarVentasMensuales = async (p = periodoActual()) => {
  try {
    const ventas = await ventasMensuales(p.anio);
    if (p.anio !== periodoActual().anio) return;
    $("#ventasMensualesAnio").text(etiquetaPeriodo(p.anio, periodoActual().mes));
    if (ventas && Array.isArray(ventas) && ventas.length > 0) {
      crearGraficoVentas(ventas);
    } else {
      limpiarGraficoVentas(`Sin ventas registradas en ${p.anio}`);
    }
  } catch (error) {
    console.error('Error al obtener ventas mensuales:', error);
  }
}
const etiquetaPeriodo = (anio, mes) => {
  const nombre = new Date(anio, mes - 1, 1).toLocaleString('es-CO', { month: 'long' });
  return nombre.charAt(0).toUpperCase() + nombre.slice(1) + ' ' + anio;
};
// Gráfico del mes seleccionado: una barra de tiempo por día
const mostrarVentasDiarias = async (p = periodoActual()) => {
  try {
    const ventas = await ventasDiarias(p.anio, p.mes);
    if (!periodoVigente(p.anio, p.mes)) return;
    $("#ventasDiariasPeriodo").text(etiquetaPeriodo(p.anio, p.mes));
    if (ventas && Array.isArray(ventas) && ventas.length > 0) {
      crearGraficoVentasDiarias(ventas, p);
    } else {
      limpiarGrafico('chartVentasDiarias', '#ventasDiariasChart', `Sin ventas registradas en ${etiquetaPeriodo(p.anio, p.mes)}`);
    }
  } catch (error) {
    console.error('Error al obtener ventas diarias:', error);
  }
}
const mostrarIngresos = async (p = periodoActual()) => {
  try {
    const ingresos = await Ingresos(p.anio, p.mes);
    if (!periodoVigente(p.anio, p.mes)) return;
    const hayDatos = ingresos && Array.isArray(ingresos) && ingresos.length > 0;
    $("#dashboard-ingresos").text(formatNum(hayDatos ? (ingresos[0].ingresos || 0) : 0, '$'));
  } catch (error) {
    console.error('Error al obtener ingresos:', error);
    if (periodoVigente(p.anio, p.mes)) $("#dashboard-ingresos").text('—');
  }
}
const mostrarEgresos = async (p = periodoActual()) => {
  try {
    const egresos = await Egresos(p.anio, p.mes);
    if (!periodoVigente(p.anio, p.mes)) return;
    const hayDatos = egresos && Array.isArray(egresos) && egresos.length > 0;
    $("#dashboard-egresos").text(formatNum(hayDatos ? (egresos[0].egresos || 0) : 0, '$'));
  } catch (error) {
    console.error('Error al obtener egresos:', error);
    if (periodoVigente(p.anio, p.mes)) $("#dashboard-egresos").text('—');
  }
}
const mostrarDevoluciones = async (p = periodoActual()) => {
  try {
    const devoluciones = await Devoluciones(p.anio, p.mes);
    if (!periodoVigente(p.anio, p.mes)) return;
    const hayDatos = devoluciones && Array.isArray(devoluciones) && devoluciones.length > 0;
    $("#dashboard-devoluciones").text(formatNum(hayDatos ? ((devoluciones[0].devoluciones_iva || 0) * -1) : 0, '$'));
  } catch (error) {
    console.error('Error al obtener devoluciones:', error);
    if (periodoVigente(p.anio, p.mes)) $("#dashboard-devoluciones").text('—');
  }
}
const UtilidadBruta = async (p = periodoActual()) => {
  try {
    const utilidad = await Utilidad(p.anio, p.mes);
    if (!periodoVigente(p.anio, p.mes)) return;

    const hayDatos = utilidad && Array.isArray(utilidad) && utilidad.length > 0;
    const venta = hayDatos ? parseFloat(utilidad[0].venta || 0) : 0;
    const costo = hayDatos ? parseFloat(utilidad[0].costo || 0) : 0;
    const utilidadBruta = venta - costo;
    const porcentaje = venta > 0 ? (utilidadBruta / venta) * 100 : 0;
    const esPositiva = utilidadBruta >= 0;

    const colorClase = esPositiva ? 'text-success' : 'text-danger';
    const icono = esPositiva ? '<i class="bx bx-up-arrow-alt"></i>' : '<i class="bx bx-down-arrow-alt"></i>';

    // Actualizar el h3 con el valor
    $("#dashboard-rentabilidad").text(formatNum(utilidadBruta, '$'));

    // Actualizar el small con color, icono y porcentaje
    $("#dashboard-rentabilidad").parent().find('small').attr('class', `${colorClase} fw-semibold`).html(`${icono} ${Math.abs(porcentaje).toFixed(2)}%`);
  } catch (error) {
    console.error('Error al obtener utilidad bruta:', error);
    if (periodoVigente(p.anio, p.mes)) $("#dashboard-rentabilidad").text('—');
  }
}

/* ---------------------------------------------------------------
 * Cumplimiento del presupuesto de ventas (bodega de la sesión)
 * La tarjeta muestra el tipo principal con presupuesto en el mes
 * (prioridad: zona, vendedor, usuario). El detalle permite cambiar de tipo.
 * --------------------------------------------------------------- */
const TIPOS_PRESUPUESTO = [
  { id: 'zona', etiqueta: 'Zona', plural: 'Por zona' },
  { id: 'vendedor', etiqueta: 'Vendedor', plural: 'Por vendedor' },
  { id: 'usuario', etiqueta: 'Usuario', plural: 'Por usuario' }
];
let presupuestoData = null; // { p, presupuestos: { zona: 123, ... }, venta, tipoPrincipal }

// Verde desde 100 %, amarillo desde 80 %, rojo por debajo
const colorCumplimiento = pct => pct >= 100 ? 'success' : pct >= 80 ? 'warning' : 'danger';

const escHtml = s => String(s ?? '').replace(/[&<>"']/g, c =>
  ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

// Avance esperado del mes: solo aplica al mes en curso
const ritmoDelMes = (p, venta, presupuesto) => {
  const hoy = new Date();
  if (hoy.getFullYear() !== p.anio || (hoy.getMonth() + 1) !== p.mes) return null;
  const diasMes = new Date(p.anio, p.mes, 0).getDate();
  const dia = hoy.getDate();
  const proyeccion = venta / dia * diasMes;
  return {
    dia, diasMes,
    esperado: dia / diasMes * 100,
    proyeccion,
    pctProyeccion: presupuesto > 0 ? proyeccion / presupuesto * 100 : null
  };
};

const pintarTarjetaPresupuesto = () => {
  const $valor = $("#dashboard-presupuesto");
  const $barra = $("#dashboard-presupuesto-barra");
  const $resumen = $("#dashboard-presupuesto-resumen");
  const d = presupuestoData;

  $barra.removeClass('bg-primary bg-success bg-warning bg-danger');
  $valor.removeClass('text-success text-warning text-danger');

  if (!d || !d.tipoPrincipal) {
    $valor.text('—');
    $barra.addClass('bg-primary').css('width', '0%');
    $resumen.attr('class', 'text-muted d-block text-truncate').text('Sin presupuesto para este mes');
    return;
  }

  const presupuesto = d.presupuestos[d.tipoPrincipal];
  const pct = presupuesto > 0 ? d.venta / presupuesto * 100 : 0;
  const color = colorCumplimiento(pct);
  const ritmo = ritmoDelMes(d.p, d.venta, presupuesto);

  $valor.text(pct.toFixed(1) + '%').addClass('text-' + color);
  $barra.addClass('bg-' + color).css('width', Math.min(pct, 100) + '%');

  // En el mes en curso se compara contra lo esperado a la fecha; en meses cerrados, contra la meta
  let texto = `${formatNum(d.venta, '$')} de ${formatNum(presupuesto, '$')}`;
  if (ritmo) texto += ` · esperado ${ritmo.esperado.toFixed(0)}%`;
  $resumen.attr('class', 'text-muted d-block text-truncate').attr('title', texto).text(texto);
};

const mostrarPresupuesto = async (p = periodoActual()) => {
  try {
    const r = await presupuestoCumplimiento(p.anio, p.mes);
    if (!periodoVigente(p.anio, p.mes)) return;

    const presupuestos = {};
    ((r && r.presupuestos) || []).forEach(x => { presupuestos[x.tipo] = parseFloat(x.presupuesto) || 0; });
    const tipoPrincipal = (TIPOS_PRESUPUESTO.find(t => presupuestos[t.id] > 0) || {}).id || null;

    presupuestoData = { p: { anio: p.anio, mes: p.mes }, presupuestos, venta: parseFloat((r && r.venta) || 0), tipoPrincipal };
    pintarTarjetaPresupuesto();
  } catch (error) {
    console.error('Error al obtener el cumplimiento del presupuesto:', error);
    if (periodoVigente(p.anio, p.mes)) {
      presupuestoData = null;
      $("#dashboard-presupuesto").text('—');
      $("#dashboard-presupuesto-resumen").text('No se pudo cargar');
    }
  }
};

const mostrarDetallePresupuesto = () => {
  const d = presupuestoData;
  if (!d || !d.tipoPrincipal) {
    Swal.fire('Sin presupuesto', `No hay presupuesto registrado para ${etiquetaPeriodo(periodoActual().anio, periodoActual().mes)} en esta bodega`, 'info');
    return;
  }

  $("#presDetallePeriodo").text(etiquetaPeriodo(d.p.anio, d.p.mes));

  // Solo se muestran los tipos que tienen presupuesto en el mes
  const tipos = TIPOS_PRESUPUESTO.filter(t => d.presupuestos[t.id] > 0);
  $("#presDetTipos").html(tipos.map(t => `
    <li class="nav-item" role="presentation">
      <button type="button" class="nav-link btn-sm ${t.id === d.tipoPrincipal ? 'active' : ''}" data-tipo="${t.id}">${t.plural}</button>
    </li>`).join('')).toggleClass('d-none', tipos.length < 2);

  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPresupuesto')).show();
  cargarDetallePresupuesto(d.tipoPrincipal);
};

const cargarDetallePresupuesto = async (tipo) => {
  const d = presupuestoData;
  const infoTipo = TIPOS_PRESUPUESTO.find(t => t.id === tipo);
  const presupuesto = d.presupuestos[tipo] || 0;
  const pct = presupuesto > 0 ? d.venta / presupuesto * 100 : 0;
  const ritmo = ritmoDelMes(d.p, d.venta, presupuesto);

  $("#presDetTipos .nav-link").removeClass('active').filter(`[data-tipo="${tipo}"]`).addClass('active');
  $("#presDetThRef").text(infoTipo.etiqueta);

  // Resumen del tipo seleccionado
  $("#presDetPresupuesto").text(formatNum(presupuesto, '$'));
  $("#presDetVenta").text(formatNum(d.venta, '$'));
  $("#presDetCumplimiento").attr('class', 'mb-0 text-' + colorCumplimiento(pct)).text(pct.toFixed(1) + '%');
  const falta = presupuesto - d.venta;
  $("#presDetFaltaTitulo").text(falta > 0 ? 'Falta por vender' : 'Supera la meta en');
  $("#presDetFalta").attr('class', 'mb-0 ' + (falta > 0 ? '' : 'text-success')).text(formatNum(Math.abs(falta), '$'));

  if (ritmo) {
    $("#presDetRitmo").removeClass('d-none').html(
      `<i class="fa-regular fa-clock me-1"></i> Van ${ritmo.dia} de ${ritmo.diasMes} días del mes ` +
      `(se esperaría un <strong>${ritmo.esperado.toFixed(0)}%</strong>). ` +
      `A este ritmo el mes cerraría en <strong>${formatNum(ritmo.proyeccion, '$')}</strong>` +
      (ritmo.pctProyeccion !== null ? ` (${ritmo.pctProyeccion.toFixed(0)}% del presupuesto).` : '.'));
  } else {
    $("#presDetRitmo").addClass('d-none');
  }

  const $tb = $("#tblPresupuestoDetalle tbody").html(
    '<tr><td colspan="5" class="text-center text-muted py-4">Cargando...</td></tr>');
  $("#presDetNota").text('');

  if (typeof showLoadingWithTetrisLogo === 'function') showLoadingWithTetrisLogo();
  try {
    const filas = await presupuestoDetalle(d.p.anio, d.p.mes, tipo) || [];
    let ventaAsignada = 0;

    const html = filas.map(f => {
      const pres = parseFloat(f.presupuesto) || 0, venta = parseFloat(f.venta) || 0;
      const dif = venta - pres;
      const pctF = f.cumplimiento === null ? null : parseFloat(f.cumplimiento);
      ventaAsignada += venta;
      const celda = pctF === null ? '<span class="text-muted">—</span>' : `
        <div class="d-flex align-items-center gap-2">
          <div class="progress flex-grow-1" style="height:6px">
            <div class="progress-bar bg-${colorCumplimiento(pctF)}" style="width:${Math.min(pctF, 100)}%"></div>
          </div>
          <span class="fw-semibold text-${colorCumplimiento(pctF)}" style="min-width:3.2rem;text-align:right">${pctF.toFixed(1)}%</span>
        </div>`;
      return `
        <tr>
          <td>${escHtml(f.referencia)}</td>
          <td class="text-end">${formatNum(pres, '$')}</td>
          <td class="text-end">${formatNum(venta, '$')}</td>
          <td class="text-end text-${dif >= 0 ? 'success' : 'danger'}">${formatNum(dif, '$')}</td>
          <td>${celda}</td>
        </tr>`;
    }).join('');

    // Ventas de la bodega que no quedaron en ninguna zona / vendedor / usuario presupuestado
    const sinAsignar = d.venta - ventaAsignada;
    const filaSinAsignar = Math.abs(sinAsignar) >= 1 ? `
      <tr class="text-muted">
        <td><em>Ventas sin presupuesto asignado</em></td>
        <td class="text-end">—</td>
        <td class="text-end">${formatNum(sinAsignar, '$')}</td>
        <td class="text-end">—</td>
        <td></td>
      </tr>` : '';

    $tb.html(html + filaSinAsignar ||
      '<tr><td colspan="5" class="text-center text-muted py-4">Sin registros</td></tr>');

    if (filaSinAsignar) {
      $("#presDetNota").text(`La fila "Ventas sin presupuesto asignado" son ventas de la bodega cuyo ${infoTipo.etiqueta.toLowerCase()} no tiene presupuesto en el mes; sí cuentan en el cumplimiento total.`);
    }
  } catch (error) {
    console.error('Error al obtener el detalle del presupuesto:', error);
    $tb.html('<tr><td colspan="5" class="text-center text-danger py-4">No se pudo cargar el detalle</td></tr>');
  } finally {
    if (typeof dissminSwal === 'function') dissminSwal();
  }
};

$(document).on('click', '#presDetTipos .nav-link', e => cargarDetallePresupuesto($(e.currentTarget).data('tipo')));

/* ---------------------------------------------------------------
 * Detalle de Ventas, Devoluciones, Ingresos y Egresos del período.
 * Se consulta por documento; las vistas por día, usuario y tercero
 * se agrupan en el navegador sin volver a consultar.
 * --------------------------------------------------------------- */
const CONCEPTOS_MOV = {
  ventas:       { titulo: 'Ventas mes + IVA', icono: 'fa-solid fa-wallet', conIva: true },
  devoluciones: { titulo: 'Devoluciones mes + IVA', icono: 'fa-solid fa-person-walking-arrow-loop-left', conIva: true },
  ingresos:     { titulo: 'Ingresos', icono: 'fa-solid fa-inbox', conIva: false },
  egresos:      { titulo: 'Egresos', icono: 'fa-solid fa-right-from-bracket', conIva: false }
};
const MAX_FILAS_MOV = 300; // filas que se pintan en la vista de documentos
let movData = { concepto: null, filas: [] };

const mostrarDetalleMovimientos = async (concepto) => {
  const cfg = CONCEPTOS_MOV[concepto];
  if (!cfg) return;
  const p = periodoActual();

  if (typeof showLoadingWithTetrisLogo === 'function') showLoadingWithTetrisLogo();
  let filas = [];
  try {
    filas = await detalleMovimientos(p.anio, p.mes, concepto) || [];
  } catch (error) {
    console.error('Error al obtener el detalle:', error);
    if (typeof dissminSwal === 'function') dissminSwal();
    Swal.fire('Error', 'No se pudo cargar el detalle', 'error');
    return;
  }
  if (typeof dissminSwal === 'function') dissminSwal();

  movData = {
    concepto,
    filas: filas.map(f => ({
      ...f,
      valor: parseFloat(f.valor) || 0,
      valor_neto: f.valor_neto === undefined ? null : (parseFloat(f.valor_neto) || 0),
      documento: `${f.tipo || ''} ${f.numero || ''}`.trim(),
      busqueda: [f.tipo, f.numero, f.tercero, f.nit, f.usuario, fechaCorta(f.fecha)].join(' ').toLowerCase()
    }))
  };

  // Resumen general del período
  const total = movData.filas.reduce((s, f) => s + f.valor, 0);
  const neto = movData.filas.reduce((s, f) => s + (f.valor_neto || 0), 0);
  const n = movData.filas.length;
  $("#movTitulo").html(`<i class="${cfg.icono}"></i> ${cfg.titulo}`);
  $("#movPeriodo").text(etiquetaPeriodo(p.anio, p.mes));
  $("#movTotal").text(formatNum(total, '$'));
  $("#movCantidad").text(n.toLocaleString('es-CO'));
  $("#movPromedio").text(formatNum(n ? total / n : 0, '$'));
  $("#movNeto").text(formatNum(neto, '$'));
  $("#movNetoCol").toggleClass('d-none', !cfg.conIva);

  $("#movVistaDoc").prop('checked', true);
  $("#movBuscar").val('');
  renderMovimientos();
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalMovimientos')).show();
};

// Agrupa las filas por una llave y devuelve [{ etiqueta, sub, docs, valor, neto }] ordenado
const agruparMovimientos = (filas, vista) => {
  const grupos = new Map();
  filas.forEach(f => {
    let llave, etiqueta, sub = '';
    if (vista === 'dia') { llave = f.fecha; etiqueta = fechaCorta(f.fecha); }
    else if (vista === 'usuario') { llave = f.usuario || '—'; etiqueta = f.usuario || '(sin usuario)'; }
    else { llave = f.nit || f.tercero || '—'; etiqueta = f.tercero || '(sin tercero)'; sub = f.nit || ''; }

    if (!grupos.has(llave)) grupos.set(llave, { llave, etiqueta, sub, docs: 0, valor: 0, neto: 0 });
    const g = grupos.get(llave);
    g.docs++;
    g.valor += f.valor;
    g.neto += f.valor_neto || 0;
  });
  const lista = [...grupos.values()];
  // Por día: cronológico; los demás: de mayor a menor valor
  return vista === 'dia'
    ? lista.sort((a, b) => String(a.llave).localeCompare(String(b.llave)))
    : lista.sort((a, b) => b.valor - a.valor);
};

const renderMovimientos = () => {
  const cfg = CONCEPTOS_MOV[movData.concepto];
  const vista = $('input[name="movVista"]:checked').val();
  const q = String($("#movBuscar").val() || '').toLowerCase().trim();
  const filas = q ? movData.filas.filter(f => f.busqueda.includes(q)) : movData.filas;
  const totalFiltrado = filas.reduce((s, f) => s + f.valor, 0);
  const netoFiltrado = filas.reduce((s, f) => s + (f.valor_neto || 0), 0);
  const colNeto = cfg.conIva;
  const $tabla = $("#tblMovimientos");
  let thead, tbody, nota = '';

  if (vista === 'documento') {
    thead = `<tr>
        <th>Fecha</th><th>Documento</th><th>Tercero</th><th>Usuario</th>
        ${colNeto ? '<th class="text-end">Sin IVA</th>' : ''}
        <th class="text-end">${colNeto ? 'Con IVA' : 'Valor'}</th>
      </tr>`;
    tbody = filas.slice(0, MAX_FILAS_MOV).map(f => `
      <tr>
        <td class="text-nowrap">${fechaCorta(f.fecha)}</td>
        <td class="text-nowrap">${escHtml(f.documento)}</td>
        <td>${escHtml(f.tercero) || '<span class="text-muted">—</span>'}${f.nit ? `<small class="text-muted d-block">${escHtml(f.nit)}</small>` : ''}</td>
        <td>${escHtml(f.usuario)}</td>
        ${colNeto ? `<td class="text-end text-nowrap">${formatNum(f.valor_neto, '$')}</td>` : ''}
        <td class="text-end text-nowrap">${formatNum(f.valor, '$')}</td>
      </tr>`).join('');
    if (filas.length > MAX_FILAS_MOV) {
      nota = `Se muestran ${MAX_FILAS_MOV} de ${filas.length.toLocaleString('es-CO')} documentos. Use el buscador o las vistas agrupadas para ver el resto; los totales sí incluyen todos.`;
    }
  } else {
    const titulo = { dia: 'Día', usuario: 'Usuario', tercero: 'Tercero' }[vista];
    const grupos = agruparMovimientos(filas, vista);
    thead = `<tr>
        <th>${titulo}</th><th class="text-end">Documentos</th>
        ${colNeto ? '<th class="text-end">Sin IVA</th>' : ''}
        <th class="text-end">${colNeto ? 'Con IVA' : 'Valor'}</th>
        <th style="min-width: 140px;">Participación</th>
      </tr>`;
    tbody = grupos.map(g => {
      const pct = totalFiltrado ? g.valor / totalFiltrado * 100 : 0;
      return `
        <tr>
          <td>${escHtml(g.etiqueta)}${g.sub ? `<small class="text-muted d-block">${escHtml(g.sub)}</small>` : ''}</td>
          <td class="text-end">${g.docs.toLocaleString('es-CO')}</td>
          ${colNeto ? `<td class="text-end text-nowrap">${formatNum(g.neto, '$')}</td>` : ''}
          <td class="text-end text-nowrap">${formatNum(g.valor, '$')}</td>
          <td>
            <div class="d-flex align-items-center gap-2">
              <div class="progress flex-grow-1" style="height:6px"><div class="progress-bar bg-primary" style="width:${pct}%"></div></div>
              <small class="text-muted" style="min-width:2.8rem;text-align:right">${pct.toFixed(1)}%</small>
            </div>
          </td>
        </tr>`;
    }).join('');
  }

  const colspan = (vista === 'documento' ? 4 : 2);
  const tfoot = filas.length ? `
    <tfoot><tr class="fw-semibold">
      <td colspan="${colspan}">Total${q ? ' filtrado' : ''} (${filas.length.toLocaleString('es-CO')} doc.)</td>
      ${colNeto ? `<td class="text-end text-nowrap">${formatNum(netoFiltrado, '$')}</td>` : ''}
      <td class="text-end text-nowrap">${formatNum(totalFiltrado, '$')}</td>
      ${vista === 'documento' ? '' : '<td></td>'}
    </tr></tfoot>` : '';

  $tabla.find('tfoot').remove();
  $tabla.find('thead').html(thead);
  $tabla.find('tbody').html(tbody ||
    `<tr><td colspan="7" class="text-center text-muted py-4">${q ? 'Sin resultados para la búsqueda' : 'No hay movimientos en el período'}</td></tr>`);
  if (tfoot) $tabla.append(tfoot);
  $("#movNota").text(nota);
};

$(document).on('change', 'input[name="movVista"]', renderMovimientos);
$(document).on('input', '#movBuscar', renderMovimientos);

/* ---------------------------------------------------------------
 * Recarga al cambiar el filtro de período.
 * Solo se consultan las tarjetas que el usuario puede ver y el gráfico
 * únicamente cuando cambia el año. Las alertas operativas (promociones,
 * vencimientos, stock mínimo, facturas vencidas) son estado actual y no se tocan.
 * --------------------------------------------------------------- */
const cargarPeriodo = async ({ anio, mes, token, cambioAnio }) => {
  const p = { anio, mes };
  // El título y el mes marcado del gráfico mensual se actualizan al instante
  $("#ventasMensualesAnio").text(etiquetaPeriodo(anio, mes));
  marcarMesEnGrafico(mes);
  try {
    const tareas = [];
    if (tienePermiso('dashboard-ventas')) tareas.push(mostrarVentas(p));
    if (tienePermiso('dashboard-devoluciones')) tareas.push(mostrarDevoluciones(p));
    if (tienePermiso('dashboard-ingresos')) tareas.push(mostrarIngresos(p));
    if (tienePermiso('dashboard-egresos')) tareas.push(mostrarEgresos(p));
    if (tienePermiso('dashboard-rentabilidad')) tareas.push(UtilidadBruta(p));
    if (tienePermiso('dashboard-presupuesto')) tareas.push(mostrarPresupuesto(p));
    if (cambioAnio && tienePermiso('dashboard-ventaMes')) tareas.push(mostrarVentasMensuales(p));
    if (tienePermiso('dashboard-ventaDia')) tareas.push(mostrarVentasDiarias(p));
    await Promise.all(tareas);
  } finally {
    window.DashboardPeriodo && window.DashboardPeriodo.finalizarCarga(token);
  }
};
document.addEventListener('dashboard:periodo', (e) => cargarPeriodo(e.detail));
/* ---------------------------------------------------------------
 * Facturas vencidas y próximas a vencer.
 * El servidor envía "estado" (vencida | proxima) y "dias_transcurridos"
 * (> 0 vencida hace N días, <= 0 vence en N días).
 * --------------------------------------------------------------- */
const DIAS_PROXIMAS_VENCER = 7; // debe coincidir con $diasProximas en services-dashboard.php

const textoDiasFactura = f => {
  const d = parseInt(f.dias_transcurridos, 10) || 0;
  if (d > 0) return `Vencida hace ${d} día${d === 1 ? '' : 's'}`;
  if (d === 0) return 'Vence hoy';
  return `Vence en ${-d} día${d === -1 ? '' : 's'}`;
};

// Rojo: vencida hace más de 30 días o vence hoy/vencida; amarillo: por vencer
const colorFactura = f => {
  const d = parseInt(f.dias_transcurridos, 10) || 0;
  return d >= 0 ? 'danger' : 'warning';
};

const fechaCorta = f => {
  if (!f) return '';
  const [a, m, d] = String(f).substring(0, 10).split('-');
  return d ? `${d}/${m}/${a}` : f;
};

const tablaFacturas = (lista, vacio) => {
  if (!lista.length) return `<div class="text-center text-muted py-4">${vacio}</div>`;
  const total = lista.reduce((s, f) => s + (parseFloat(f.saldo) || 0), 0);
  return `
    <div class="table-responsive border rounded" style="max-height: 50vh;">
      <table class="table table-sm table-hover align-middle mb-0 tabla-primary">
        <thead>
          <tr>
            <th>Tercero</th>
            <th>Documento</th>
            <th>Vencimiento</th>
            <th>Estado</th>
            <th class="text-end">Saldo</th>
          </tr>
        </thead>
        <tbody>
          ${lista.map(f => `
            <tr>
              <td>${escHtml(f.razon_comercial)}<small class="text-muted d-block">${escHtml(f.nit || '')}</small></td>
              <td class="text-nowrap">${escHtml(f.tipo)} ${escHtml(f.numero)}</td>
              <td class="text-nowrap">${fechaCorta(f.vencimiento)}</td>
              <td class="text-nowrap"><span class="badge bg-label-${colorFactura(f)}">${textoDiasFactura(f)}</span></td>
              <td class="text-end text-nowrap">${formatNum(f.saldo, '$')}</td>
            </tr>`).join('')}
        </tbody>
        <tfoot>
          <tr class="fw-semibold">
            <td colspan="4">Total (${lista.length})</td>
            <td class="text-end text-nowrap">${formatNum(total, '$')}</td>
          </tr>
        </tfoot>
      </table>
    </div>`;
};

const mostrarDetalleFacturas = (pestana) => {
  if (!facturasVencidasData || facturasVencidasData.length === 0) {
    Swal.fire('Sin alertas', `No hay facturas vencidas ni por vencer en los próximos ${DIAS_PROXIMAS_VENCER} días`, 'info');
    return;
  }

  // Vencidas: las más antiguas primero. Próximas: las que vencen antes primero.
  const vencidas = facturasVencidasData.filter(f => f.estado === 'vencida')
    .sort((a, b) => b.dias_transcurridos - a.dias_transcurridos);
  const proximas = facturasVencidasData.filter(f => f.estado !== 'vencida')
    .sort((a, b) => b.dias_transcurridos - a.dias_transcurridos);
  const activa = pestana || (vencidas.length ? 'vencidas' : 'proximas');

  const modalId = 'modalFacturasVencidas';
  const modalHtml = `
    <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header border-bottom">
            <h5 class="modal-title" id="${modalId}Label">
              <i class="fa-solid fa-file-invoice"></i> Facturas vencidas y por vencer
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <ul class="nav nav-pills mb-3" role="tablist">
              <li class="nav-item" role="presentation">
                <button type="button" class="nav-link ${activa === 'vencidas' ? 'active' : ''}" data-bs-toggle="tab" data-bs-target="#facTabVencidas" role="tab">
                  Vencidas <span class="badge rounded-pill bg-white text-primary ms-1">${vencidas.length}</span>
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button type="button" class="nav-link ${activa === 'proximas' ? 'active' : ''}" data-bs-toggle="tab" data-bs-target="#facTabProximas" role="tab">
                  Por vencer (${DIAS_PROXIMAS_VENCER} días) <span class="badge rounded-pill bg-white text-primary ms-1">${proximas.length}</span>
                </button>
              </li>
            </ul>
            <div class="tab-content p-0 shadow-none">
              <div class="tab-pane fade ${activa === 'vencidas' ? 'show active' : ''}" id="facTabVencidas" role="tabpanel">
                ${tablaFacturas(vencidas, 'No hay facturas vencidas')}
              </div>
              <div class="tab-pane fade ${activa === 'proximas' ? 'show active' : ''}" id="facTabProximas" role="tabpanel">
                ${tablaFacturas(proximas, `No hay facturas que venzan en los próximos ${DIAS_PROXIMAS_VENCER} días`)}
              </div>
            </div>
          </div>
          <div class="modal-footer border-top">
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="bootstrap.Modal.getInstance(document.getElementById('${modalId}')).hide(); mostrarCalendarioFacturas();">
              <i class="fa-regular fa-calendar-days"></i> Ver calendario
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
  `;

  const oldModal = document.getElementById(modalId);
  if (oldModal) oldModal.remove();
  document.body.insertAdjacentHTML('beforeend', modalHtml);
  new bootstrap.Modal(document.getElementById(modalId)).show();
};

const mostrarFacturas = async () => {
  try {
    const facturas = await facturasVencidas();
    if (!facturas || !Array.isArray(facturas) || facturas.length === 0) {
      facturasVencidasData = [];
      $("#dashboard-facturas-vencer").html(`
        <div class="card h-100">
          <div class="card-body">
            <div class="card-title d-flex align-items-start justify-content-between mb-4">
              <div class="avatar flex-shrink-0">
                <span class="avatar-initial rounded" style="background-color: #43A047; color: white;"><i class="fa-solid fa-file-invoice fs-4"></i></span>
              </div>
              <div class="dropdown">
                <button class="btn p-0" type="button" id="cardFacturas" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardFacturas">
                  <a class="dropdown-item" href="javascript:void(0);" onclick="mostrarCalendarioFacturas()"><i class="fa-regular fa-calendar-days"></i>&nbsp;Ver calendario</a>
                </div>
              </div>
            </div>
            <p class="mb-1">Facturas Vencidas / Por Vencer</p>
            <h4 class="card-title mb-3">0</h4>
            <small class="text-success fw-medium"><i class="icon-base bx bx-check"></i> Todo en orden</small>
          </div>
        </div>
      `);
      return;
    }

    facturasVencidasData = facturas;
    const vencidas = facturas.filter(f => f.estado === 'vencida');
    const proximas = facturas.length - vencidas.length;
    const saldoVencido = vencidas.reduce((sum, f) => sum + parseFloat(f.saldo || 0), 0);

    // Rojo si hay vencidas; amarillo si solo hay próximas a vencer
    const colorAvatar = vencidas.length ? '#D32F2F' : '#F57C00';

    $("#dashboard-facturas-vencer").html(`
      <div class="card h-100">
        <div class="card-body">
          <div class="card-title d-flex align-items-start justify-content-between mb-4">
            <div class="avatar flex-shrink-0">
              <span class="avatar-initial rounded" style="background-color: ${colorAvatar}; color: white;"><i class="fa-solid fa-file-invoice fs-4"></i></span>
            </div>
            <div class="dropdown">
              <button class="btn p-0" type="button" id="cardFacturas" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
              </button>
              <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardFacturas">
                <a class="dropdown-item" href="javascript:void(0);" onclick="mostrarDetalleFacturas('vencidas')"><i class="fa-solid fa-list"></i>&nbsp;Ver vencidas</a>
                <a class="dropdown-item" href="javascript:void(0);" onclick="mostrarDetalleFacturas('proximas')"><i class="fa-regular fa-clock"></i>&nbsp;Ver próximas a vencer</a>
                <a class="dropdown-item" href="javascript:void(0);" onclick="mostrarCalendarioFacturas()"><i class="fa-regular fa-calendar-days"></i>&nbsp;Ver calendario</a>
              </div>
            </div>
          </div>
          <p class="mb-1">Facturas Vencidas / Por Vencer</p>
          <h4 class="card-title mb-3">${facturas.length}</h4>
          <small class="${vencidas.length ? 'text-danger' : 'text-warning'} fw-medium"
                 title="${vencidas.length} vencida(s) por ${formatNum(saldoVencido, '$')} · ${proximas} por vencer en ${DIAS_PROXIMAS_VENCER} días">
            <i class="icon-base bx ${vencidas.length ? 'bx-alert-circle' : 'bx-time-five'}"></i> ${vencidas.length} vencidas · ${proximas} por vencer
          </small>
        </div>
      </div>
    `);
  } catch (error) {
    console.error('Error al obtener facturas vencidas:', error);
    $("#dashboard-facturas-vencer").html(`
      <div class="card h-100">
        <div class="card-body">
          <div class="card-title d-flex align-items-start justify-content-between mb-4">
            <div class="avatar flex-shrink-0">
              <span class="avatar-initial rounded" style="background-color: #C62828; color: white;"><i class="bx bx-error-circle fs-4"></i></span>
            </div>
          </div>
          <p class="mb-1">Facturas Vencidas / Por Vencer</p>
          <h4 class="card-title mb-3 text-danger">Error</h4>
          <small class="text-muted fw-medium">No se pudieron cargar los datos</small>
        </div>
      </div>
    `);
  }
};

window.addEventListener('message', function (event) {
    if (event.data && event.data.tipo === 'calendarioPagosAltura') {
        const iframe = document.getElementById('iframeCalendarioFacturas');
        console.log('Mensaje recibido del iframe:', event.data);
        if (iframe) {
            // Nunca dejamos que el iframe crezca más del 75% del alto de la ventana,
            // así el modal completo nunca se desborda del viewport.
            const maxAltura = window.innerHeight * 0.75;
            const alturaFinal = Math.min(event.data.altura, maxAltura);
            iframe.style.height = alturaFinal + 'px';
        }
    }
});

const mostrarCalendarioFacturas = () => {

    const url = '../views/calendarioPagos.php';

    $('#iframeCalendarioFacturas').attr('src', url);

    const modalElement = document.getElementById('modalCalendarioFacturas');
    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);

    modal.show();
};

/* ---------------------------------------------------------------
 * Productos (lotes) vencidos y próximos a vencer.
 * Rangos: vencido (< 0 días), 30 días, 90 días y hasta 6 meses.
 * --------------------------------------------------------------- */
const RANGOS_VENCIMIENTO = [
  { id: 'vencido', texto: 'Vencido', color: 'danger', hasta: -1 },
  { id: 'd30', texto: '≤ 30 días', color: 'warning', hasta: 30 },
  { id: 'd90', texto: '31 – 90 días', color: 'info', hasta: 90 },
  { id: 'd180', texto: '> 90 días', color: 'secondary', hasta: Infinity }
];
const rangoVencimiento = dias => RANGOS_VENCIMIENTO.find(r => dias <= r.hasta);
const textoDiasVence = d => d < 0 ? `Vencido hace ${-d} d` : d === 0 ? 'Vence hoy' : `Vence en ${d} d`;

const mostrarDetalleProximosVencer = () => {
  if (!proximosVencerData || proximosVencerData.length === 0) {
    Swal.fire('Sin alertas', 'No hay productos vencidos ni próximos a vencer', 'info');
    return;
  }

  const datos = proximosVencerData.map(p => {
    const dias = parseInt(p.dias_restantes, 10) || 0;
    return { ...p, dias, rango: rangoVencimiento(dias), stock: parseFloat(p.stock) || 0, costo_total: parseFloat(p.costo_total) || 0 };
  }).sort((a, b) => a.dias - b.dias || b.costo_total - a.costo_total);

  const resumenRango = id => {
    const lista = datos.filter(d => d.rango.id === id);
    return { n: lista.length, costo: lista.reduce((s, d) => s + d.costo_total, 0) };
  };
  const venc = resumenRango('vencido'), d30 = resumenRango('d30');
  const costoTotal = datos.reduce((s, d) => s + d.costo_total, 0);

  const modalId = 'modalProximosVencer';
  const modalHtml = `
    <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header border-bottom">
            <h5 class="modal-title" id="${modalId}Label">
              <i class="fa-solid fa-hourglass-end"></i> Productos vencidos y próximos a vencer
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3 mb-3">
              <div class="col-6 col-md-3"><small class="text-muted d-block">Lotes</small><h6 class="mb-0">${datos.length}</h6></div>
              <div class="col-6 col-md-3"><small class="text-muted d-block">Costo en riesgo</small><h6 class="mb-0 text-primary">${formatNum(costoTotal, '$')}</h6></div>
              <div class="col-6 col-md-3"><small class="text-muted d-block">Vencidos</small><h6 class="mb-0 text-danger">${venc.n} · ${formatNum(venc.costo, '$')}</h6></div>
              <div class="col-6 col-md-3"><small class="text-muted d-block">Vencen en 30 días</small><h6 class="mb-0 text-warning">${d30.n} · ${formatNum(d30.costo, '$')}</h6></div>
            </div>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
              <div class="btn-group btn-group-sm" role="group">
                <input type="radio" class="btn-check" name="venFiltro" id="venTodos" value="todos" checked>
                <label class="btn btn-outline-primary" for="venTodos">Todos</label>
                ${RANGOS_VENCIMIENTO.map(r => `
                  <input type="radio" class="btn-check" name="venFiltro" id="ven_${r.id}" value="${r.id}">
                  <label class="btn btn-outline-primary" for="ven_${r.id}">${r.texto}</label>`).join('')}
              </div>
              <div class="input-group input-group-sm" style="max-width: 260px;">
                <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="search" class="form-control" id="venBuscar" placeholder="Producto, código, lote o laboratorio">
              </div>
            </div>
            <div class="table-responsive border rounded" style="max-height: 50vh;">
              <table class="table table-sm table-hover align-middle mb-0 tabla-primary" id="tblProximosVencer">
                <thead>
                  <tr>
                    <th>Producto</th>
                    <th>Lote</th>
                    <th>Vencimiento</th>
                    <th class="text-end">Stock</th>
                    <th class="text-end">Costo total</th>
                  </tr>
                </thead>
                <tbody></tbody>
                <tfoot></tfoot>
              </table>
            </div>
          </div>
          <div class="modal-footer border-top">
            <button type="button" class="btn btn-sm btn-outline-primary" id="venExportar"><i class="fa-solid fa-download"></i> Descargar CSV</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
  `;

  const oldModal = document.getElementById(modalId);
  if (oldModal) oldModal.remove();
  document.body.insertAdjacentHTML('beforeend', modalHtml);

  const filtrados = () => {
    const filtro = $('input[name="venFiltro"]:checked').val();
    const q = String($('#venBuscar').val() || '').toLowerCase().trim();
    return datos.filter(d => (filtro === 'todos' || d.rango.id === filtro) &&
      (!q || `${d.codigo} ${d.descripcion} ${d.lote} ${d.laboratorio}`.toLowerCase().includes(q)));
  };

  const render = () => {
    const lista = filtrados();
    $('#tblProximosVencer tbody').html(lista.map(d => `
      <tr>
        <td>
          <span class="fw-semibold">${escHtml(d.descripcion)}</span>
          <small class="d-block text-muted">${escHtml(d.codigo)}${d.laboratorio ? ' · ' + escHtml(d.laboratorio) : ''}</small>
        </td>
        <td class="text-nowrap">${escHtml(d.lote)}</td>
        <td class="text-nowrap">
          ${fechaCorta(d.vencimiento)}
          <span class="badge bg-label-${d.rango.color} ms-1">${textoDiasVence(d.dias)}</span>
        </td>
        <td class="text-end">${fmtCant(d.stock)}</td>
        <td class="text-end text-nowrap">${formatNum(d.costo_total, '$')}</td>
      </tr>`).join('') || '<tr><td colspan="5" class="text-center text-muted py-4">Sin productos para este filtro</td></tr>');

    const total = lista.reduce((s, d) => s + d.costo_total, 0);
    $('#tblProximosVencer tfoot').html(lista.length ? `
      <tr class="fw-semibold">
        <td colspan="4">Total (${lista.length} lote${lista.length === 1 ? '' : 's'})</td>
        <td class="text-end text-nowrap">${formatNum(total, '$')}</td>
      </tr>` : '');
  };

  const exportar = () => {
    const lineas = ['codigo;descripcion;laboratorio;lote;vencimiento;dias;stock;costo_unitario;costo_total'].concat(
      filtrados().map(d => [d.codigo, d.descripcion, d.laboratorio, d.lote, d.vencimiento, d.dias, d.stock, d.costo, d.costo_total]
        .map(v => String(v ?? '').replace(/[;\r\n]/g, ' ')).join(';')));
    const blob = new Blob(['﻿' + lineas.join('\r\n')], { type: 'text/csv;charset=utf-8' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'productos_por_vencer.csv';
    a.click();
    setTimeout(() => URL.revokeObjectURL(a.href), 1000);
  };

  $(`#${modalId}`).on('change', 'input[name="venFiltro"]', render)
    .on('input', '#venBuscar', render)
    .on('click', '#venExportar', exportar);
  render();
  new bootstrap.Modal(document.getElementById(modalId)).show();
};

const mostrarProximosVencer = async () => {
  try {
    const proximos_ven = await productosVencidos();
    if (!proximos_ven || !Array.isArray(proximos_ven) || proximos_ven.length === 0) {
      proximosVencerData = [];
      $("#dashboard-proximos-vencer").html(`
        <div class="card h-100">
          <div class="card-body">
            <div class="card-title d-flex align-items-start justify-content-between mb-4">
              <div class="avatar flex-shrink-0">
                <span class="avatar-initial rounded" style="background-color: #43A047; color: white;"><i class="fa-solid fa-hourglass-end fs-4"></i></span>
              </div>
            </div>
            <p class="mb-1">Productos Próximos a Vencer</p>
            <h4 class="card-title mb-3">0</h4>
            <small class="text-success fw-medium"><i class="icon-base bx bx-check"></i> Todo en orden</small>
          </div>
        </div>
      `);
      return;
    }

    proximosVencerData = proximos_ven;
    const dias = proximos_ven.map(p => parseInt(p.dias_restantes, 10) || 0);
    const vencidos = dias.filter(d => d < 0).length;
    const en30 = dias.filter(d => d >= 0 && d <= 30).length;
    const costo = proximos_ven.reduce((s, p) => s + (parseFloat(p.costo_total) || 0), 0);

    // Rojo si hay vencidos, naranja si hay lotes que vencen en 30 días, azul verdoso en otro caso
    const colorAvatar = vencidos ? '#D32F2F' : en30 ? '#F57C00' : '#0097A7';
    const resumen = vencidos || en30
      ? `<small class="${vencidos ? 'text-danger' : 'text-warning'} fw-medium" title="Costo en riesgo: ${formatNum(costo, '$')}"><i class="icon-base bx bx-alert-circle"></i> ${vencidos} vencidos · ${en30} en 30 días</small>`
      : `<small style="color: #0097A7;" class="fw-medium" title="Costo en riesgo: ${formatNum(costo, '$')}"><i class="icon-base bx bx-info-circle"></i> ${formatNum(costo, '$')} en riesgo</small>`;

    $("#dashboard-proximos-vencer").html(`
      <div class="card h-100">
        <div class="card-body">
          <div class="card-title d-flex align-items-start justify-content-between mb-4">
            <div class="avatar flex-shrink-0">
              <span class="avatar-initial rounded" style="background-color: ${colorAvatar}; color: white;"><i class="fa-solid fa-hourglass-end fs-4"></i></span>
            </div>
            <div class="dropdown">
              <button class="btn p-0" type="button" id="cardVencer" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
              </button>
              <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardVencer">
                <a class="dropdown-item" href="javascript:void(0);" onclick="mostrarDetalleProximosVencer()"><i class="fa-solid fa-list"></i>&nbsp;Ver detalle</a>
              </div>
            </div>
          </div>
          <p class="mb-1">Productos Próximos a Vencer</p>
          <h4 class="card-title mb-3">${proximos_ven.length}</h4>
          ${resumen}
        </div>
      </div>
    `);
  } catch (error) {
    console.error('Error al obtener productos próximos a vencer:', error);
    $("#dashboard-proximos-vencer").html(`
      <div class="card h-100">
        <div class="card-body">
          <div class="card-title d-flex align-items-start justify-content-between mb-4">
            <div class="avatar flex-shrink-0">
              <span class="avatar-initial rounded" style="background-color: #C62828; color: white;"><i class="bx bx-error-circle fs-4"></i></span>
            </div>
          </div>
          <p class="mb-1">Productos Próximos a Vencer</p>
          <h4 class="card-title mb-3 text-danger">Error</h4>
          <small class="text-muted fw-medium">No se pudieron cargar los datos</small>
        </div>
      </div>
    `);
  }
}
// Opciones compartidas por los gráficos de área de ventas (mensual y diario)
const anotacionMes = (categoria) => ({
  x: categoria,
  borderColor: '#ff3e1d',
  strokeDashArray: 4,
  label: {
    text: categoria,
    orientation: 'horizontal',
    style: { color: '#fff', background: '#ff3e1d', fontSize: '11px' }
  }
});

// Mueve la marca del mes seleccionado sin volver a consultar ni redibujar el gráfico
const marcarMesEnGrafico = (mes) => {
  const grafico = window.chartVentas;
  if (!grafico) return;
  try {
    grafico.clearAnnotations();
    const categoria = (window.chartVentasMeses || {})[mes];
    if (categoria) grafico.addXaxisAnnotation(anotacionMes(categoria));
  } catch (e) {
    console.warn('No se pudo marcar el mes en el gráfico:', e);
  }
};

const COLORES_GRAFICO_MENSUAL = ['#696CFF', '#03C3EC']; // Índigo / Celeste
const COLORES_GRAFICO_DIARIO  = ['#71DD37', '#20C997']; // Verde / Turquesa

const opcionesGraficoVentas = ({ categorias, ventas, ventasIVA, tituloEjeX, colores, mesMarcado }) => {
  const axisColor = config.colors.axisColor;
  const borderColor = config.colors.borderColor;

  const opciones = {
    series: [
      { name: 'Venta', data: ventas },
      { name: 'Venta + IVA', data: ventasIVA }
    ],
    chart: {
      height: 300,
      type: 'area',
      toolbar: { show: false },
      sparkline: { enabled: false }
    },
    fill: {
      type: 'gradient',
      gradient: {
        shadeIntensity: 1,
        opacityFrom: 0.7,
        opacityTo: 0.1,
        stops: [0, 100]
      }
    },
    colors: colores || COLORES_GRAFICO_MENSUAL,
    stroke: { curve: 'smooth', width: 3 },
    dataLabels: { enabled: false },
    grid: {
      borderColor: borderColor,
      padding: { top: 0, bottom: -8, left: 20, right: 20 }
    },
    xaxis: {
      categories: categorias,
      labels: { style: { colors: axisColor, fontSize: '13px' } },
      axisTicks: { show: false },
      axisBorder: { show: false }
    },
    yaxis: {
      labels: {
        style: { colors: axisColor, fontSize: '13px' },
        formatter: val => '$' + Number(val).toLocaleString('es-CO')
      }
    },
    tooltip: {
      shared: true,
      intersect: false,
      y: { formatter: val => '$' + Number(val).toLocaleString('es-CO') }
    },
    legend: {
      show: true,
      position: 'top',
      horizontalAlign: 'left',
      labels: { colors: axisColor }
    }
  };

  // Marca el mes seleccionado en el filtro (solo gráfico mensual)
  if (mesMarcado) {
    opciones.annotations = { xaxis: [anotacionMes(mesMarcado)] };
  }

  // OJO: no se debe pasar `title: undefined` a ApexCharts; rompe su merge de opciones.
  // Solo se agrega el título del eje X cuando existe.
  if (tituloEjeX) {
    opciones.xaxis.title = {
      text: tituloEjeX,
      style: { color: axisColor, fontSize: '12px', fontWeight: 400 }
    };
  }

  return opciones;
};

// Destruye el gráfico anterior, limpia mensajes previos y dibuja el nuevo
const renderizarGraficoVentas = (selector, instancia, opciones) => {
  const elemento = document.querySelector(selector);
  if (!elemento) return;

  if (window[instancia]) {
    window[instancia].destroy();
  }

  // Quita mensajes como "Sin ventas registradas" de un render anterior
  elemento.innerHTML = '';

  window[instancia] = new ApexCharts(elemento, opciones);
  window[instancia].render();
};

const crearGraficoVentas = (data) => {
  const ventasArray = Array.isArray(data) ? data : data.ventas;

  if (!Array.isArray(ventasArray)) {
    console.error("Datos de ventas inválidos:", data);
    return;
  }

  const meses = ventasArray.map(v => {
    const numeroMes = parseInt(v.mes);
    const nombreMes = new Date(0, numeroMes - 1).toLocaleString('es-CO', { month: 'short' });
    return nombreMes.charAt(0).toUpperCase() + nombreMes.slice(1);
  });

  // mes (1-12) -> nombre mostrado en el eje, para poder marcar el mes seleccionado
  window.chartVentasMeses = {};
  ventasArray.forEach((v, i) => { window.chartVentasMeses[parseInt(v.mes)] = meses[i]; });

  renderizarGraficoVentas('#totalRevenueChart', 'chartVentas', opcionesGraficoVentas({
    categorias: meses,
    ventas: ventasArray.map(v => Number(v.venta) || 0),
    ventasIVA: ventasArray.map(v => Number(v.venta_iva) || 0),
    colores: COLORES_GRAFICO_MENSUAL,
    mesMarcado: window.chartVentasMeses[periodoActual().mes]
  }));
};

// Ventas del mes seleccionado, día a día. Los días sin ventas se muestran en 0;
// si es el mes en curso, el gráfico llega hasta hoy.
const crearGraficoVentasDiarias = (data, p = periodoActual()) => {
  if (!Array.isArray(data)) {
    console.error("Datos de ventas diarias inválidos:", data);
    return;
  }

  const hoy = new Date();
  const esMesActual = hoy.getFullYear() === p.anio && (hoy.getMonth() + 1) === p.mes;
  const ultimoDia = esMesActual ? hoy.getDate() : new Date(p.anio, p.mes, 0).getDate();

  const porDia = {};
  data.forEach(v => { porDia[parseInt(v.dia)] = v; });

  const categorias = [];
  const ventas = [];
  const ventasIVA = [];
  for (let d = 1; d <= ultimoDia; d++) {
    categorias.push(String(d));
    ventas.push(Number(porDia[d] && porDia[d].venta) || 0);
    ventasIVA.push(Number(porDia[d] && porDia[d].venta_iva) || 0);
  }

  renderizarGraficoVentas('#ventasDiariasChart', 'chartVentasDiarias', opcionesGraficoVentas({
    categorias,
    ventas,
    ventasIVA,
    tituloEjeX: 'Día del mes',
    colores: COLORES_GRAFICO_DIARIO
  }));
};
function aplicarPermisos(permisos) {
  document.querySelectorAll('[data-permiso]').forEach(columna => {
    const permiso = columna.dataset.permiso;
    // Si no existe la propiedad o no es 1, se elimina
    if (!permisos[permiso]) {
      columna.remove();
    } else {
      columna.classList.remove('d-none');
    }

  });

};
function reorganizarColumnas() {

  const filas = document.querySelectorAll('.row');

  filas.forEach(row => {

    // Buscar el elemento bienvenido (el que tiene data-no-reorganizar)
    const bienvenido = row.querySelector('[data-no-reorganizar]');

    if (bienvenido) {
      // Revisar si hay elementos con data-permiso visibles en la misma fila
      const columnasConPermiso = row.querySelectorAll('[data-permiso]');
      const visiblesConPermiso = Array.from(columnasConPermiso).filter(col => col.offsetParent !== null);

      // Limpiar clases col-lg-* y col-md-*
      Array.from(bienvenido.classList).forEach(clase => {
        if ((clase.startsWith('col-lg-') || clase.startsWith('col-md-')) && /\d+$/.test(clase)) {
          bienvenido.classList.remove(clase);
        }
      });

      // Determinar el tamaño según cantidad de elementos visibles con permiso
      let tamano = 12; // Por defecto, ancho completo si no hay elementos con permiso

      if (visiblesConPermiso.length === 1) {
        tamano = 9; // 9 + 3 = 12
      } else if (visiblesConPermiso.length === 2) {
        tamano = 6; // 6 + 3 + 3 = 12
      } else if (visiblesConPermiso.length >= 3) {
        tamano = 3; // 3 + 3 + 3 + 3 = 12
      }

      bienvenido.classList.add(`col-lg-${tamano}`);
      bienvenido.classList.add(`col-md-${tamano}`);

      // Reorganizar elementos con permiso si es necesario
      visiblesConPermiso.forEach(col => {
        Array.from(col.classList).forEach(clase => {
          if ((clase.startsWith('col-lg-') || clase.startsWith('col-md-')) && /\d+$/.test(clase)) {
            col.classList.remove(clase);
          }
        });

        // Cada elemento con permiso en esta fila debe ocupar 3 columnas (25%)
        col.classList.add('col-lg-3');
        col.classList.add('col-md-3');
      });

      return;
    }

    // Seleccionar solo columnas con data-permiso
    const columnas = row.querySelectorAll('[data-permiso]');

    // Solo las visibles
    const visibles = Array.from(columnas).filter(col => col.offsetParent !== null);

    const cantidad = visibles.length;

    if (cantidad === 0) return;

    // 12 dividido por cantidad visible
    const nuevoTamano = Math.floor(12 / cantidad);

    // Validar que el nuevo tamaño sea válido (mínimo 3 columnas de ancho)
    if (nuevoTamano < 3) return;

    visibles.forEach(col => {
      // limpiamos solo clases col-lg-* y col-md-* que sean numéricas
      Array.from(col.classList).forEach(clase => {
        if ((clase.startsWith('col-lg-') || clase.startsWith('col-md-')) && /\d+$/.test(clase)) {
          col.classList.remove(clase);
        }
      });

      // Aplicar nuevo tamaño
      col.classList.add(`col-lg-${nuevoTamano}`);
      col.classList.add(`col-md-${nuevoTamano}`);
    });

  });

};
const inicializarDashboard = async () => {
  try {
    // Paso 1: Obtener y aplicar permisos (DEBE ser primero)
    try {
      permisos = await permisosUsuario(id_modulo);
      if (!permisos || typeof permisos !== 'object') {
        console.warn('Permisos no disponibles');
        permisos = {};
      }
      aplicarPermisos(permisos);
      reorganizarColumnas();

      // Sin tarjetas que dependan del período, el filtro no tiene sentido
      if (!document.querySelector('[data-periodo]')) {
        const filtro = document.getElementById('filtroPeriodoDashboard');
        if (filtro) filtro.remove();
      }
    } catch (error) {
      console.error('Error al cargar permisos:', error);
    }

    // Paso 2: Cargar datos que pueden ejecutarse en paralelo
    // Estado del servicio (necesario para validación)
    await mostrarEstadoServicio();

    // Paso 3: Cargar en paralelo SOLO lo que el usuario puede ver.
    // Cada consulta depende del mismo permiso (data-permiso) que muestra su tarjeta.
    const tareas = [];
    if (tienePermiso('dashboard-ventas')) tareas.push(mostrarVentas());
    if (tienePermiso('dashboard-ingresos')) tareas.push(mostrarIngresos());
    if (tienePermiso('dashboard-egresos')) tareas.push(mostrarEgresos());
    if (tienePermiso('dashboard-devoluciones')) tareas.push(mostrarDevoluciones());
    if (tienePermiso('dashboard-rentabilidad')) tareas.push(UtilidadBruta());
    if (tienePermiso('dashboard-presupuesto')) tareas.push(mostrarPresupuesto());
    if (tienePermiso('dashboard-ventaMes')) tareas.push(mostrarVentasMensuales());
    if (tienePermiso('dashboard-ventaDia')) tareas.push(mostrarVentasDiarias());
    if (tienePermiso('dashboard-FacturasVmcto')) tareas.push(mostrarFacturas());
    if (tienePermiso('dashboard-vencimientos')) tareas.push(mostrarProximosVencer());
    if (tienePermiso('dashboard-stock-minimo')) tareas.push(mostrarReferenciasStock());
    if (tienePermiso('dashboard-promociones')) tareas.push(mostrarPromocionesVigentes());

    await Promise.all(tareas);

    // Paso 4: Cerrar el loader
    dissminSwal();

  } catch (error) {
    console.error('Error crítico al inicializar dashboard:', error);
    dissminSwal();
    Swal.fire("Error", "Error al cargar el dashboard. Por favor recargue la página.", "error");
  }
};
(async function () {
  await inicializarDashboard();
})();