<?php
session_start();

$titulo = 'Cierres de Caja';
include '../dependencies/header.php';
?>
<style>
  /* ====== Tokens (claro / oscuro) ====== */
  :root {
    --cc-soft: #f5f5f9;
    --cc-border: #e4e6ef;
    --cc-text: #697a8d;
    --cc-strong: #384551;
    --cc-primary: #696cff;
    --cc-success: #71dd37;
    --cc-danger: #ff3e1d;
    --cc-warning: #ffab00;
    --cc-info: #03c3ec;
    --cc-secondary: #8592a3;
    --cc-ok-bg: rgba(113, 221, 55, .14);
    --cc-ok-fg: #4c9a22;
    --cc-neg-bg: rgba(255, 62, 29, .12);
    --cc-neg-fg: #e0300f;
    --cc-pos-bg: rgba(255, 171, 0, .16);
    --cc-pos-fg: #b67b00;
  }

  html[data-color-scheme="dark"] {
    --cc-soft: #232332;
    --cc-border: #3a3a54;
    --cc-text: #b3b6c4;
    --cc-strong: #e4e4f0;
    --cc-ok-fg: #8ee35f;
    --cc-neg-fg: #ff6a50;
    --cc-pos-fg: #ffc04d;
  }

  .cc-strong { color: var(--cc-strong); }
  .min-w-0 { min-width: 0; }

  /* ====== Filtros ====== */
  .cc-presets, .cc-chips { display: flex; gap: .4rem; overflow-x: auto; scrollbar-width: none; }
  .cc-presets::-webkit-scrollbar, .cc-chips::-webkit-scrollbar { display: none; }
  .cc-preset, .cc-chip { white-space: nowrap; border-radius: 50rem; }
  .cc-preset.active, .cc-chip.active { background: var(--cc-primary); border-color: var(--cc-primary); color: #fff; }

  .cc-chevron { transition: transform .2s; font-size: .7rem; }
  .cc-btn-filtros:not(.collapsed) .cc-chevron { transform: rotate(180deg); }

  /* ====== KPIs ====== */
  .cc-kpi { border: 1px solid var(--cc-border); transition: transform .15s, box-shadow .15s; }
  .cc-kpi-destacado { border-width: 2px; }
  .cc-kpi.cc-clickable { cursor: pointer; }
  .cc-kpi.cc-clickable:hover { transform: translateY(-2px); box-shadow: 0 4px 14px rgba(0, 0, 0, .08); }
  .cc-kpi-icon { width: 36px; height: 36px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
  .cc-kpi-title { font-size: .7rem; text-transform: uppercase; letter-spacing: .03em; color: var(--cc-text); }
  .cc-kpi-value { font-size: 1.05rem; font-weight: 700; color: var(--cc-strong); line-height: 1.2; word-break: break-word; }
  .cc-kpi-sub { font-size: .72rem; color: var(--cc-text); }

  /* ====== Diferencias (colores) ====== */
  .cc-dif-ok { background: var(--cc-ok-bg); color: var(--cc-ok-fg); }
  .cc-dif-neg { background: var(--cc-neg-bg); color: var(--cc-neg-fg); }
  .cc-dif-pos { background: var(--cc-pos-bg); color: var(--cc-pos-fg); }
  .cc-pill { display: inline-block; padding: .15rem .55rem; border-radius: 50rem; font-size: .78rem; font-weight: 600; white-space: nowrap; }

  /* ====== Panel por grupo ====== */
  .cc-grupo + .cc-grupo { margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed var(--cc-border); }
  .cc-bar-row { display: grid; grid-template-columns: 78px 1fr auto; gap: .5rem; align-items: center; font-size: .75rem; margin-top: .3rem; }
  .cc-bar-lbl { color: var(--cc-text); }
  .cc-bar-val { color: var(--cc-strong); font-weight: 600; white-space: nowrap; }
  .cc-bar { height: 8px; border-radius: 4px; background: var(--cc-soft); overflow: hidden; }
  .cc-bar span { display: block; height: 100%; border-radius: 4px; }
  .cc-bar-reg { background: var(--cc-info); }
  .cc-bar-sis { background: var(--cc-primary); }

  /* ====== Panel por cajero ====== */
  .cc-cajero { display: flex; align-items: center; gap: .75rem; padding: .55rem .5rem; border-radius: 8px; cursor: pointer; }
  .cc-cajero:hover, .cc-cajero:focus-visible { background: var(--cc-soft); outline: none; }
  .cc-cajero + .cc-cajero { border-top: 1px dashed var(--cc-border); }
  .cc-cajero.active { background: rgba(105, 108, 255, .12); box-shadow: inset 3px 0 0 var(--cc-primary); }

  /* ====== Filtros activos ====== */
  .cc-activo { display: inline-flex; align-items: center; gap: .35rem; font-size: .75rem; padding: .15rem .25rem .15rem .6rem; border-radius: 50rem; background: rgba(105, 108, 255, .12); color: var(--cc-primary); font-weight: 600; max-width: 100%; }
  .cc-activo-x { border: 0; background: transparent; color: inherit; font-size: 1rem; line-height: 1; width: 22px; height: 22px; border-radius: 50%; padding: 0; }
  .cc-activo-x:hover { background: rgba(105, 108, 255, .2); }
  .cc-avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--cc-soft); color: var(--cc-primary); font-weight: 700; font-size: .75rem; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }

  /* ====== Barra de herramientas ====== */
  .cc-toolbar { position: sticky; top: 0; z-index: 5; background: var(--cc-soft); padding: .6rem 0; border-bottom: 1px solid var(--cc-border); }
  .cc-toolbar-top { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; }
  .cc-search { flex: 1 1 260px; max-width: 420px; }
  .cc-toolbar-actions { display: flex; gap: .5rem; align-items: center; margin-left: auto; }
  .cc-orden-wrap { width: 240px; flex-wrap: nowrap; }
  /* Escritorio: los filtros bajan de línea en vez de montarse sobre otros controles */
  @media (min-width: 768px) {
    .cc-chips { flex-wrap: wrap; overflow: visible; }
  }
  /* Móvil: todo a ancho completo; los filtros se desplazan de lado */
  @media (max-width: 767.98px) {
    .cc-search { flex-basis: 100%; max-width: none; }
    .cc-toolbar-actions { width: 100%; margin-left: 0; }
    .cc-orden-wrap { width: 100%; }
  }

  /* ====== Detalle: pestañas y movimientos ====== */
  .cc-tabs { flex-wrap: nowrap; overflow-x: auto; gap: .25rem; scrollbar-width: none; }
  .cc-tabs::-webkit-scrollbar { display: none; }
  .cc-tabs .nav-link { white-space: nowrap; padding: .35rem .7rem; font-size: .82rem; }
  .cc-mov-resumen { background: var(--cc-soft); border-radius: 8px; padding: .5rem .75rem; margin-bottom: .75rem; }
  .cc-mov-resumen > div:first-child { display: flex; justify-content: space-between; font-size: .85rem; }
  .cc-mov { padding: .6rem .25rem; }
  .cc-mov + .cc-mov { border-top: 1px dashed var(--cc-border); }
  .cc-mov-main { min-width: 0; }
  .cc-mov-det { color: var(--cc-text); margin-top: .15rem; white-space: pre-wrap; word-break: break-word; }
  .cc-mov-medios { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .3rem; }
  .cc-medio-chip { font-size: .7rem; padding: .1rem .5rem; border-radius: 50rem; background: var(--cc-soft); color: var(--cc-strong); border: 1px solid var(--cc-border); white-space: nowrap; }
  .cc-medio-chip.cc-g-efectivo { border-color: var(--cc-success); }
  .cc-medio-chip.cc-g-bancos { border-color: var(--cc-primary); }
  .cc-link-tab, .cc-ir-tab { text-decoration: none; }
  .cc-link-tab:hover, .cc-ir-tab:hover { text-decoration: underline; }

  /* ====== Tarjeta de cierre ====== */
  .cc-card { border-left: 4px solid var(--cc-secondary) !important; cursor: pointer; transition: transform .15s, box-shadow .15s; }
  .cc-card:hover, .cc-card:focus-visible { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0, 0, 0, .1); outline: none; }
  .cc-card[data-estado="ok"] { border-left-color: var(--cc-success) !important; }
  .cc-card[data-estado="faltante"] { border-left-color: var(--cc-danger) !important; }
  .cc-card[data-estado="sobrante"] { border-left-color: var(--cc-warning) !important; }
  .cc-card[data-estado="cruzado"] { border-left-color: var(--cc-info) !important; }
  .cc-badge { white-space: nowrap; }

  .cc-label { font-size: .68rem; text-transform: uppercase; letter-spacing: .03em; opacity: .85; }
  .cc-big { font-size: 1.35rem; font-weight: 700; line-height: 1.15; }

  .cc-total { display: flex; justify-content: space-between; align-items: center; gap: .75rem; padding: .6rem .75rem; border-radius: 8px; }
  .cc-total strong { font-weight: 600; }

  .cc-cmp-row { display: grid; grid-template-columns: minmax(0, 1fr) auto auto; gap: .5rem; align-items: center; padding: .4rem 0; font-size: .8rem; }
  .cc-cmp-row + .cc-cmp-row { border-top: 1px dashed var(--cc-border); }
  .cc-cmp-name { color: var(--cc-strong); font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .cc-cmp-name i { width: 18px; color: var(--cc-primary); }
  .cc-cmp-vals { display: flex; flex-direction: column; align-items: flex-end; font-size: .72rem; color: var(--cc-text); line-height: 1.35; }
  .cc-cmp-vals i { margin-right: .3rem; opacity: .7; }

  .cc-base-ref { color: var(--cc-info); cursor: help; white-space: nowrap; }
  .cc-obs { background: var(--cc-soft); border-radius: 6px; padding: .35rem .5rem; color: var(--cc-text); }
  .cc-obs-box { background: var(--cc-soft); color: var(--cc-strong); white-space: pre-wrap; }

  /* ====== Detalle ====== */
  .cc-info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: .6rem; }
  .cc-info-grid > div { background: var(--cc-soft); border-radius: 8px; padding: .5rem .7rem; min-width: 0; }
  .cc-info-grid span { display: block; font-size: .68rem; text-transform: uppercase; color: var(--cc-text); }
  .cc-info-grid strong { color: var(--cc-strong); font-size: .88rem; word-break: break-word; }
  .cc-calc { background: var(--cc-soft); border-radius: 8px; padding: .5rem .75rem; }
  .cc-calc > div { display: flex; justify-content: space-between; gap: .5rem; padding: .25rem 0; }
  .cc-tr-grupo td { font-size: .7rem; text-transform: uppercase; color: var(--cc-text); background: var(--cc-soft); }

  .cc-empty { text-align: center; padding: 3rem 1rem; color: var(--cc-text); }
  .cc-empty i { font-size: 2.5rem; opacity: .5; margin-bottom: .75rem; }

  @media (max-width: 575.98px) {
    .cc-big { font-size: 1.2rem; }
    .cc-kpi-value { font-size: .95rem; }
    .cc-bar-row { grid-template-columns: 64px 1fr auto; }
  }
</style>

<div class="cc-wrap">
  <!-- Card de filtros: encabezado siempre visible; en móvil el contenido se colapsa -->
  <div class="card mb-3">
    <div class="card-body p-3 pb-md-0">
      <div class="d-flex align-items-center justify-content-between gap-2">
        <h5 class="fw-bold mb-0 min-w-0 text-truncate">
          <span class="text-muted fw-light d-none d-sm-inline">Reportes / Reportes de ventas / </span>Cierres de Caja
        </h5>
        <button type="button" class="btn btn-sm btn-outline-primary d-md-none cc-btn-filtros collapsed flex-shrink-0"
                data-bs-toggle="collapse" data-bs-target="#cc-filtros" aria-expanded="false" aria-controls="cc-filtros">
          <i class="fa-solid fa-sliders me-1"></i><span id="cc-rango-txt">Filtros</span>
          <i class="fa-solid fa-chevron-down ms-1 cc-chevron"></i>
        </button>
      </div>
    </div>

    <div class="collapse d-md-block" id="cc-filtros">
    <div class="card-body p-3 pt-3">
      <div class="cc-presets mb-3" role="group" aria-label="Rangos rápidos">
        <button type="button" class="btn btn-sm btn-outline-primary cc-preset" data-rango="hoy">Hoy</button>
        <button type="button" class="btn btn-sm btn-outline-primary cc-preset" data-rango="ayer">Ayer</button>
        <button type="button" class="btn btn-sm btn-outline-primary cc-preset" data-rango="7d">Últimos 7 días</button>
        <button type="button" class="btn btn-sm btn-outline-primary cc-preset" data-rango="mes">Este mes</button>
      </div>
      <div class="row g-2 align-items-end">
        <div class="col-6 col-md-3">
          <label class="form-label mb-1" for="fe_fecha_inicial">Desde</label>
          <input type="date" class="form-control form-control-sm" id="fe_fecha_inicial">
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label mb-1" for="fe_fecha_final">Hasta</label>
          <input type="date" class="form-control form-control-sm" id="fe_fecha_final">
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label mb-1" for="slcBodegas">Bodega</label>
          <select class="form-select form-select-sm" id="slcBodegas" name="slcBodegas"></select>
        </div>
        <div class="col-12 col-md-3 d-flex gap-2">
          <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-limpiar" title="Limpiar">
            <i class="fa-solid fa-eraser"></i><span class="d-md-none d-xl-inline ms-1">Limpiar</span>
          </button>
          <button type="button" class="btn btn-sm btn-primary flex-fill" id="btn-busqueda">
            <i class="fa fa-search me-1"></i>Consultar
          </button>
        </div>
      </div>
    </div>
    </div>
  </div>

  <!-- Aviso: la base no hace parte de las diferencias -->
  <div id="cc-aviso-base" class="d-none"></div>

  <!-- KPIs -->
  <div id="cc-kpis" class="row g-2 mb-3"></div>

  <!-- Paneles: diferencias por medio y por cajero -->
  <div id="cc-paneles" class="row g-3 mb-2 d-none">
    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-body p-3">
          <h6 class="mb-1"><i class="fa-solid fa-scale-balanced me-2 text-primary"></i>Registrado vs sistema por medio</h6>
          <div class="small text-muted mb-3">
            <span class="d-inline-flex align-items-center gap-1 me-3"><span class="cc-bar-reg d-inline-block rounded" style="width:12px;height:8px"></span>Cajero</span>
            <span class="d-inline-flex align-items-center gap-1"><span class="cc-bar-sis d-inline-block rounded" style="width:12px;height:8px"></span>Sistema</span>
          </div>
          <div id="cc-grupos"></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-body p-3">
          <h6 class="mb-1"><i class="fa-solid fa-users me-2 text-primary"></i>Diferencias por cajero</h6>
          <div class="small text-muted mb-2">Toca un cajero para ver solo sus cierres</div>
          <div id="cc-cajeros"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Barra de herramientas -->
  <div id="cc-toolbar" class="cc-toolbar d-none">
    <!-- Fila 1: búsqueda + orden + vista -->
    <div class="cc-toolbar-top">
      <div class="input-group input-group-sm input-group-merge cc-search">
        <span class="input-group-text"><i class="fa fa-search"></i></span>
        <input type="search" class="form-control" id="cc-buscar" placeholder="Buscar cajero, bodega o #cierre">
      </div>
      <div class="cc-toolbar-actions">
        <div class="input-group input-group-sm cc-orden-wrap">
          <span class="input-group-text"><i class="fa-solid fa-arrow-down-wide-short"></i></span>
          <select class="form-select form-select-sm" id="cc-orden" aria-label="Ordenar">
            <option value="alerta">Mayor diferencia primero</option>
            <option value="dif_asc">Mayor faltante primero</option>
            <option value="dif_desc">Mayor sobrante primero</option>
            <option value="fecha_desc">Más recientes</option>
            <option value="fecha_asc">Más antiguos</option>
          </select>
        </div>
        <div class="btn-group btn-group-sm d-none d-md-inline-flex" role="group" aria-label="Vista">
          <button type="button" class="btn btn-outline-primary active" data-vista="cards" title="Tarjetas"><i class="fa-solid fa-grip"></i></button>
          <button type="button" class="btn btn-outline-primary" data-vista="tabla" title="Tabla"><i class="fa-solid fa-table-list"></i></button>
        </div>
      </div>
    </div>

    <!-- Fila 2: filtros por estado -->
    <div class="d-flex align-items-center gap-2 mt-2">
        <div class="cc-chips flex-grow-1" id="cc-chips">
          <button type="button" class="btn btn-sm btn-outline-secondary cc-chip active" data-filtro="todos">Todos <span class="badge bg-white text-dark ms-1" data-count="todos">0</span></button>
          <button type="button" class="btn btn-sm btn-outline-secondary cc-chip" data-filtro="revisar">Con diferencia <span class="badge bg-dark ms-1" data-count="revisar">0</span></button>
          <button type="button" class="btn btn-sm btn-outline-secondary cc-chip" data-filtro="faltante">Faltantes <span class="badge bg-danger ms-1" data-count="faltante">0</span></button>
          <button type="button" class="btn btn-sm btn-outline-secondary cc-chip" data-filtro="sobrante">Sobrantes <span class="badge bg-warning ms-1" data-count="sobrante">0</span></button>
          <button type="button" class="btn btn-sm btn-outline-secondary cc-chip" data-filtro="cruzado">Cruce de medios <span class="badge bg-info ms-1" data-count="cruzado">0</span></button>
          <button type="button" class="btn btn-sm btn-outline-secondary cc-chip" data-filtro="ok">Cuadrados <span class="badge bg-success ms-1" data-count="ok">0</span></button>
        </div>
        <span class="small text-muted text-nowrap d-none d-md-inline" id="cc-resultado-txt"></span>
    </div>

    <!-- Fila 3: filtros activos con opción de quitarlos -->
    <div id="cc-filtros-activos" class="d-none d-flex flex-wrap align-items-center gap-2 mt-2"></div>
  </div>

  <!-- Resultados -->
  <div id="cc-lista" class="row g-3 mt-1"></div>
  <div id="cc-tabla" class="card mt-2 d-none">
    <div class="card-body p-2 table-responsive text-nowrap" id="resultado-cierres"></div>
  </div>
  <div id="cc-vacio" class="d-none"></div>
</div>

<!-- Modal detalle -->
<div class="modal fade" id="cc-modal-detalle" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="cc-detalle-titulo">Detalle del cierre</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body" id="cc-detalle-body"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-sm btn-primary" id="cc-detalle-print"><i class="fa fa-print me-1"></i>Imprimir</button>
      </div>
    </div>
  </div>
</div>

<?php
$scriptsPagina = [
  '../controllers/rptCierresCajas.js?' . rand()
];

include '../dependencies/footer.php';
?>