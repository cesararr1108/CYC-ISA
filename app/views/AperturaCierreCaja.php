<?php
session_start();

$titulo = 'Apertura y cierre de cajas';
include '../dependencies/header.php';
?>
<input type="hidden" id="redirect" value="<?= isset($_GET['redirect']) ? htmlspecialchars($_GET['redirect']) : '0' ?>" />

<style>
  /* ====== Tokens basados en la plantilla Sneat (claro / oscuro) ====== */
  :root {
    --hc-soft: #f5f5f9;      /* fondo de página Sneat */
    --hc-border: #d9dee3;    /* bordes Sneat */
    --hc-text: #697a8d;      /* texto Sneat */
    --hc-muted: #a1acb8;     /* texto atenuado Sneat */
    --hc-strong: #566a7f;    /* encabezados Sneat */
    --hc-primary: #696cff;
    --hc-success: #71dd37;
    --hc-danger: #ff3e1d;
    --hc-warning: #ffab00;
    --hc-info: #03c3ec;
    --hc-secondary: #8592a3;
    --hc-ok-bg: rgba(105, 108, 255, .10);
    --hc-ok-fg: #696cff;
    --hc-neg-bg: rgba(255, 62, 29, .12);
    --hc-neg-fg: #e0300f;
    --hc-pos-bg: rgba(255, 171, 0, .16);
    --hc-pos-fg: #b67b00;
  }

  html[data-color-scheme="dark"] {
    --hc-soft: #232333;
    --hc-border: #444564;
    --hc-text: #a3a4cc;
    --hc-muted: #7c7d9c;
    --hc-strong: #cbcbe2;
    --hc-ok-fg: #a5a6ff;
    --hc-neg-fg: #ff6a50;
    --hc-pos-fg: #ffc04d;
  }

  .hc-strong { color: var(--hc-strong); }
  .min-w-0 { min-width: 0; }

  /* ====== Filtros ====== */
  .hc-presets, .hc-chips { display: flex; gap: .4rem; overflow-x: auto; scrollbar-width: none; }
  .hc-presets::-webkit-scrollbar, .hc-chips::-webkit-scrollbar { display: none; }
  .hc-preset, .hc-chip { white-space: nowrap; border-radius: 50rem; }
  .hc-preset.active, .hc-chip.active { background: var(--hc-primary); border-color: var(--hc-primary); color: #fff; }

  .hc-chevron { transition: transform .2s; font-size: .7rem; }
  .hc-btn-filtros:not(.collapsed) .hc-chevron { transform: rotate(180deg); }

  /* ====== KPIs ====== */
  .hc-kpi { transition: transform .15s, box-shadow .15s; }
  .hc-kpi-destacado { border-width: 2px; }
  .hc-kpi.hc-clickable { cursor: pointer; }
  .hc-kpi.hc-clickable:hover { transform: translateY(-2px); box-shadow: 0 4px 14px rgba(0, 0, 0, .08); }
  .hc-kpi-icon { width: 36px; height: 36px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
  .hc-kpi-title { font-size: .7rem; text-transform: uppercase; letter-spacing: .03em; color: var(--hc-text); }
  .hc-kpi-value { font-size: 1.05rem; font-weight: 700; color: var(--hc-strong); line-height: 1.2; word-break: break-word; }
  .hc-kpi-sub { font-size: .72rem; color: var(--hc-text); }

  /* ====== Diferencias (colores) ====== */
  .hc-dif-ok { background: var(--hc-ok-bg); color: var(--hc-ok-fg); }
  .hc-dif-neg { background: var(--hc-neg-bg); color: var(--hc-neg-fg); }
  .hc-dif-pos { background: var(--hc-pos-bg); color: var(--hc-pos-fg); }
  .hc-pill { display: inline-block; padding: .15rem .55rem; border-radius: 50rem; font-size: .78rem; font-weight: 600; white-space: nowrap; }

  /* ====== Panel por grupo ====== */
  .hc-grupo + .hc-grupo { margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed var(--hc-border); }
  .hc-bar-row { display: grid; grid-template-columns: 78px 1fr auto; gap: .5rem; align-items: center; font-size: .75rem; margin-top: .3rem; }
  .hc-bar-lbl { color: var(--hc-text); }
  .hc-bar-val { color: var(--hc-strong); font-weight: 600; white-space: nowrap; }
  .hc-bar { height: 8px; border-radius: 4px; background: var(--hc-soft); overflow: hidden; }
  .hc-bar span { display: block; height: 100%; border-radius: 4px; }
  .hc-bar-reg { background: var(--hc-info); }
  .hc-bar-sis { background: var(--hc-primary); }

  /* ====== Panel por cajero ====== */
  .hc-cajero { display: flex; align-items: center; gap: .75rem; padding: .55rem .5rem; border-radius: 8px; cursor: pointer; }
  .hc-cajero:hover, .hc-cajero:focus-visible { background: var(--hc-soft); outline: none; }
  .hc-cajero + .hc-cajero { border-top: 1px dashed var(--hc-border); }
  .hc-cajero.active { background: rgba(105, 108, 255, .12); box-shadow: inset 3px 0 0 var(--hc-primary); }

  /* ====== Filtros activos ====== */
  .hc-activo { display: inline-flex; align-items: center; gap: .35rem; font-size: .75rem; padding: .15rem .25rem .15rem .6rem; border-radius: 50rem; background: rgba(105, 108, 255, .12); color: var(--hc-primary); font-weight: 600; max-width: 100%; }
  .hc-activo-x { border: 0; background: transparent; color: inherit; font-size: 1rem; line-height: 1; width: 22px; height: 22px; border-radius: 50%; padding: 0; }
  .hc-activo-x:hover { background: rgba(105, 108, 255, .2); }
  .hc-avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--hc-soft); color: var(--hc-primary); font-weight: 700; font-size: .75rem; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }

  /* ====== Barra de herramientas ====== */
  .hc-toolbar { position: sticky; top: 0; z-index: 5; background: var(--hc-soft); padding: .6rem 0; border-bottom: 1px solid var(--hc-border); }
  .hc-toolbar-top { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; }
  .hc-search { flex: 1 1 260px; max-width: 420px; }
  .hc-toolbar-actions { display: flex; gap: .5rem; align-items: center; margin-left: auto; }
  .hc-orden-wrap { width: 240px; flex-wrap: nowrap; }
  /* Escritorio: los filtros bajan de línea en vez de montarse sobre otros controles */
  @media (min-width: 768px) {
    .hc-chips { flex-wrap: wrap; overflow: visible; }
  }
  /* Móvil: todo a ancho completo; los filtros se desplazan de lado */
  @media (max-width: 767.98px) {
    .hc-search { flex-basis: 100%; max-width: none; }
    .hc-toolbar-actions { width: 100%; margin-left: 0; }
    .hc-orden-wrap { width: 100%; }
  }

  /* ====== Detalle: pestañas y movimientos ====== */
  .hc-tabs { flex-wrap: nowrap; overflow-x: auto; gap: .25rem; scrollbar-width: none; }
  .hc-tabs::-webkit-scrollbar { display: none; }
  .hc-tabs .nav-link { white-space: nowrap; padding: .35rem .7rem; font-size: .82rem; }
  .hc-mov-resumen { background: var(--hc-soft); border-radius: 8px; padding: .5rem .75rem; margin-bottom: .75rem; }
  .hc-mov-resumen > div:first-child { display: flex; justify-content: space-between; font-size: .85rem; }
  .hc-mov { padding: .6rem .25rem; }
  .hc-mov + .hc-mov { border-top: 1px dashed var(--hc-border); }
  .hc-mov-main { min-width: 0; }
  .hc-mov-det { color: var(--hc-text); margin-top: .15rem; white-space: pre-wrap; word-break: break-word; }
  .hc-mov-medios { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .3rem; }
  .hc-medio-chip { font-size: .7rem; padding: .1rem .5rem; border-radius: 50rem; background: var(--hc-soft); color: var(--hc-strong); border: 1px solid var(--hc-border); white-space: nowrap; }
  .hc-medio-chip.hc-g-efectivo, .hc-medio-chip.hc-g-bancos { border-color: rgba(105, 108, 255, .45); }
  .hc-link-tab, .hc-ir-tab { text-decoration: none; }
  .hc-link-tab:hover, .hc-ir-tab:hover { text-decoration: underline; }

  /* ====== Tarjeta de cierre ====== */
  .hc-card { border-left: 4px solid var(--hc-secondary) !important; cursor: pointer; transition: transform .15s, box-shadow .15s; }
  .hc-card:hover, .hc-card:focus-visible { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0, 0, 0, .1); outline: none; }
  .hc-card[data-estado="ok"] { border-left-color: var(--hc-success) !important; }
  .hc-card[data-estado="faltante"] { border-left-color: var(--hc-danger) !important; }
  .hc-card[data-estado="sobrante"] { border-left-color: var(--hc-warning) !important; }
  .hc-card[data-estado="cruzado"] { border-left-color: var(--hc-info) !important; }
  .hc-badge { white-space: nowrap; }

  .hc-label { font-size: .68rem; text-transform: uppercase; letter-spacing: .03em; opacity: .85; }
  .hc-big { font-size: 1.35rem; font-weight: 700; line-height: 1.15; }

  .hc-total { display: flex; justify-content: space-between; align-items: center; gap: .75rem; padding: .6rem .75rem; border-radius: 8px; }
  .hc-total strong { font-weight: 600; }

  .hc-cmp-row { display: grid; grid-template-columns: minmax(0, 1fr) auto auto; gap: .5rem; align-items: center; padding: .4rem 0; font-size: .8rem; }
  .hc-cmp-row + .hc-cmp-row { border-top: 1px dashed var(--hc-border); }
  .hc-cmp-name { color: var(--hc-strong); font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .hc-cmp-name i { width: 18px; color: var(--hc-primary); }
  .hc-cmp-vals { display: flex; flex-direction: column; align-items: flex-end; font-size: .72rem; color: var(--hc-text); line-height: 1.35; }
  .hc-cmp-vals i { margin-right: .3rem; opacity: .7; }

  .hc-base-ref { color: var(--hc-info); cursor: help; white-space: nowrap; }
  .hc-obs { background: var(--hc-soft); border-radius: 6px; padding: .35rem .5rem; color: var(--hc-text); }
  .hc-obs-box { background: var(--hc-soft); color: var(--hc-strong); white-space: pre-wrap; }

  /* ====== Detalle ====== */
  .hc-info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: .6rem; }
  .hc-info-grid > div { background: var(--hc-soft); border-radius: 8px; padding: .5rem .7rem; min-width: 0; }
  .hc-info-grid span { display: block; font-size: .68rem; text-transform: uppercase; color: var(--hc-text); }
  .hc-info-grid strong { color: var(--hc-strong); font-size: .88rem; word-break: break-word; }
  .hc-calc { background: var(--hc-soft); border-radius: 8px; padding: .5rem .75rem; }
  .hc-calc > div { display: flex; justify-content: space-between; gap: .5rem; padding: .25rem 0; }
  .hc-tr-grupo td { font-size: .7rem; text-transform: uppercase; color: var(--hc-text); background: var(--hc-soft); }

  /* Estado vacío: ícono centrado y texto debajo */
  .hc-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .5rem; text-align: center; padding: 3rem 1rem; color: var(--hc-text); width: 100%; }
  .hc-empty-icon { display: inline-flex; align-items: center; justify-content: center; width: 64px; height: 64px; border-radius: 50%; background: rgba(105, 108, 255, .1); color: var(--hc-primary); font-size: 1.75rem; margin-bottom: .25rem; }
  .hc-empty-icon i { display: block; line-height: 1; margin: 0; }
  .hc-empty-titulo { font-weight: 600; color: var(--hc-strong); }

  @media (max-width: 575.98px) {
    .hc-big { font-size: 1.2rem; }
    .hc-kpi-value { font-size: .95rem; }
    .hc-bar-row { grid-template-columns: 64px 1fr auto; }
  }

  .hc-bodega { font-size: .8rem; }
  .min-w-0 { min-width: 0; }


  .hc-chip.active .badge { background: #fff !important; color: var(--hc-primary) !important; }

  /* ====== Historial: lista horizontal ====== */
  .hc-list { overflow: hidden; }
  .hc-list-head, .hc-item {
    display: grid;
    grid-template-columns: minmax(190px, 1.5fr) repeat(3, minmax(120px, 1fr)) minmax(140px, .9fr) auto;
    gap: .75rem; align-items: center; padding: .7rem 1rem;
  }
  /* Igual que el thead de las tablas Sneat */
  .hc-list-head { font-size: .75rem; font-weight: 500; text-transform: uppercase; letter-spacing: 1px; color: var(--hc-strong); border-top: 1px solid var(--hc-border); padding-top: .75rem; padding-bottom: .75rem; }
  .hc-item { border-top: 1px solid var(--hc-border); border-left: 3px solid transparent; cursor: pointer; transition: background .15s; }
  .hc-item:hover, .hc-item:focus-visible { background: var(--hc-soft); outline: none; }
  .hc-item[data-estado="ok"] { border-left-color: transparent; }
  .hc-item[data-estado="faltante"] { border-left-color: var(--hc-danger); }
  .hc-item[data-estado="sobrante"] { border-left-color: var(--hc-warning); }
  .hc-item[data-estado="cruzado"] { border-left-color: var(--hc-primary); }
  .hc-item-grupos { display: contents; }
  .hc-g { min-width: 0; font-size: .75rem; line-height: 1.35; }
  .hc-g-lbl { display: none; font-weight: 600; color: var(--hc-strong); margin-bottom: .15rem; }
  .hc-g-vals { color: var(--hc-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .hc-g-vals i { width: 14px; opacity: .7; }
  .hc-g .hc-pill { margin-top: .2rem; }
  .hc-g.hc-g-vacio { color: var(--hc-text); opacity: .5; }
  .hc-item-total { text-align: right; }
  .hc-item-total .hc-big { font-size: 1.05rem; }
  .hc-item-acciones { display: flex; gap: .35rem; justify-content: flex-end; }
  .hc-item-acciones .btn { padding: .25rem .45rem; }

  /* Tablet y celular: la fila se reorganiza en bloques */
  @media (max-width: 991.98px) {
    .hc-list-head { display: none; }
    .hc-item { grid-template-columns: minmax(0, 1fr) auto; gap: .6rem; padding: .8rem; }
    .hc-item-grupos { display: grid; grid-column: 1 / -1; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .4rem; }
    .hc-g { background: var(--hc-soft); border-radius: 8px; padding: .4rem .5rem; }
    .hc-g-lbl { display: block; }
    .hc-item-acciones { grid-column: 1 / -1; justify-content: stretch; }
    .hc-item-acciones .btn { flex: 1; }
  }

  /* ====== Cards anidadas dentro de la card del módulo ====== */
  .ac-modulo .tab-content .card { box-shadow: none; border: 1px solid var(--hc-border); }
  .ac-modulo .tab-content .card.hc-kpi-destacado { border-width: 2px; }
  .ac-modulo .tab-content .card .card-header { background: transparent; }
  .ac-modulo .hc-toolbar { background: #fff; }
  html[data-color-scheme="dark"] .ac-modulo .hc-toolbar { background: #2b2c40; }
  @media (max-width: 575.98px) {
    .ac-modulo > .card-body { padding: 1rem .75rem; }
    .ac-modulo .tab-content { padding: 1rem .5rem; }
  }

  /* ====== Proceso de apertura / cierre ====== */
  .ac-tabs .nav-link { white-space: nowrap; }

  .ac-estado { border-radius: 12px; padding: 1.1rem; display: flex; gap: 1rem; align-items: center; }
  .ac-estado-icon { width: 48px; height: 48px; border-radius: .375rem; display: inline-flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; }
  /* Abierta = verde (success Sneat) · Cerrada = rojo (danger Sneat) */
  .ac-estado.ac-abierta { background: rgba(113, 221, 55, .12); }
  .ac-estado.ac-abierta .ac-estado-icon { background: #71dd37; color: #fff; }
  .ac-estado.ac-abierta h5 { color: #56ca00; }
  .ac-estado.ac-cerrada { background: rgba(255, 62, 29, .10); }
  .ac-estado.ac-cerrada .ac-estado-icon { background: #ff3e1d; color: #fff; }
  .ac-estado.ac-cerrada h5 { color: #ff3e1d; }
  .ac-estado h5 { margin: 0; font-weight: 700; }
  .ac-timer { font-variant-numeric: tabular-nums; }

  .ac-datos { list-style: none; padding: 0; margin: 0; }
  .ac-datos li { display: flex; justify-content: space-between; gap: .75rem; padding: .55rem 0; font-size: .85rem; }
  .ac-datos li + li { border-top: 1px dashed var(--hc-border, #e4e6ef); }
  .ac-datos span { color: var(--hc-text, #697a8d); }
  .ac-datos strong { text-align: right; word-break: break-word; }

  .ac-paso-abrir { /*background: rgba(113, 221, 55, .16) !important; */color: #696cff !important; }
  .ac-paso-cerrar {/* background: rgba(255, 62, 29, .14) !important;*/ color: #696cff !important; }
  .ac-paso { display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: .375rem; background: rgba(105, 108, 255, .16); color: #696cff; font-size: .8rem; font-weight: 600; margin-right: .5rem; }

  .ac-monto { font-size: 1.15rem; font-weight: 600; text-align: right; font-variant-numeric: tabular-nums; }
  .ac-monto-lg { font-size: 1.6rem; }
  .ac-campo-ayuda { font-size: .75rem; color: var(--hc-text, #697a8d); }

  .ac-total { border-radius: 12px; padding: .9rem 1rem; background: rgba(105, 108, 255, .1); display: flex; justify-content: space-between; align-items: center; gap: 1rem; }
  .ac-total .ac-total-valor { font-size: 1.5rem; font-weight: 700; color: #696cff; font-variant-numeric: tabular-nums; }

  .ac-base-ref { border-radius: 10px; padding: .6rem .8rem; background: rgba(105, 108, 255, .08); font-size: .82rem; }

  .ac-acciones { position: sticky; bottom: 0; background: var(--bs-body-bg, #fff); padding: .75rem 0; z-index: 4; }
  html[data-color-scheme="dark"] .ac-acciones { background: #2b2c40; }

  /* ====== Contador de billetes ====== */
  .ac-den { display: grid; grid-template-columns: 1fr auto 1fr; gap: .5rem; align-items: center; padding: .35rem 0; }
  .ac-den + .ac-den { border-top: 1px dashed var(--hc-border, #e4e6ef); }
  .ac-den-lbl { font-weight: 600; font-variant-numeric: tabular-nums; }
  .ac-den-lbl small { font-weight: 400; color: var(--hc-text, #697a8d); }
  .ac-den-qty { display: flex; align-items: center; gap: .25rem; }
  .ac-den-qty input { width: 64px; text-align: center; }
  .ac-den-sub { text-align: right; font-variant-numeric: tabular-nums; color: var(--hc-text, #697a8d); }
  .ac-den-sub.ac-tiene { color: inherit; font-weight: 600; }
  .ac-den-titulo { font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; color: var(--hc-text, #697a8d); margin-top: .75rem; }
</style>

<div class="card ac-modulo">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">POS /</span> Apertura y cierre de cajas</h5>
    <div class="nav-align-top">
      <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item">
          <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#ac-tab-proceso" aria-controls="ac-tab-proceso" aria-selected="true">
            <i class="fa-solid fa-cash-register"></i>&nbsp;Mi caja
          </button>
        </li>
        <li class="nav-item">
          <button type="button" class="nav-link" id="ac-tab-historial-btn" role="tab" data-bs-toggle="tab" data-bs-target="#ac-tab-historial" aria-controls="ac-tab-historial" aria-selected="false">
            <i class="fa-solid fa-clock-rotate-left"></i>&nbsp;Historial de cierres
          </button>
        </li>
      </ul>
      <div class="tab-content">


  <!-- ============================ PROCESO ============================ -->
  <div class="tab-pane fade show active" id="ac-tab-proceso" role="tabpanel">
    <input type="hidden" id="id_apertura" value="0" />
    <input type="hidden" id="id_usuario" value="" />
    <input type="hidden" id="id_bodega" value="" />
    <input type="hidden" id="bodega" value="" />

    <div class="row g-3">

      <!-- Estado de la caja -->
      <div class="col-12 col-lg-4">
        <div class="card h-100">
          <div class="card-body p-3">
            <div id="ac-estado" class="ac-estado ac-cerrada mb-3">
              <span class="ac-estado-icon"><i class="fa-solid fa-spinner fa-spin"></i></span>
              <div class="min-w-0">
                <h5 id="ac-estado-titulo">Consultando…</h5>
                <div class="small text-muted" id="ac-estado-sub"></div>
              </div>
            </div>

            <ul class="ac-datos">
              <li><span><i class="fa-regular fa-user me-1"></i>Usuario</span><strong id="ac-usuario">—</strong></li>
              <li><span><i class="fa-solid fa-store me-1"></i>Bodega</span><strong id="ac-bodega">—</strong></li>
              <li id="ac-li-apertura" class="d-none"><span><i class="fa-solid fa-lock-open me-1"></i>Abierta desde</span><strong id="ac-fecha-apertura">—</strong></li>
              <li id="ac-li-tiempo" class="d-none"><span><i class="fa-regular fa-clock me-1"></i>Tiempo abierta</span><strong class="ac-timer" id="ac-tiempo">—</strong></li>
              <li>
                <span><i class="fa-solid fa-lock me-1"></i>Último cierre</span>
                <strong>
                  <span id="ac-ultimo-cierre">—</span>
                  <button type="button" class="btn btn-link btn-sm p-0 ms-1 d-none" id="ac-imprimir-ultimo" title="Imprimir último cierre"><i class="fa fa-print"></i></button>
                </strong>
              </li>
            </ul>

            <div id="ac-avisos" class="mt-3 d-flex flex-column gap-2"></div>

            <button type="button" class="btn btn-sm btn-outline-secondary w-100 mt-3" id="ac-refrescar">
              <i class="fa-solid fa-rotate me-1"></i>Actualizar estado
            </button>
          </div>
        </div>
      </div>

      <!-- Panel de acción -->
      <div class="col-12 col-lg-8">

        <!-- Apertura -->
        <div class="card h-100 d-none" id="ac-panel-apertura">
          <div class="card-body p-3 d-flex flex-column">
            <h6 class="mb-1"><span class="ac-paso ac-paso-abrir"><i class="fa-solid fa-lock-open"></i></span>Abrir caja</h6>
            <p class="small text-muted mb-3">Indica el dinero con el que inicias el turno (base). Este valor queda fijo durante todo el turno.</p>

            <label class="form-label" for="ac-base">Base de caja</label>
            <div class="input-group">
              <span class="input-group-text">$</span>
              <input type="text" inputmode="numeric" autocomplete="off" class="form-control ac-monto ac-monto-lg ac-money" id="ac-base" value="0">
            </div>
            <div class="ac-campo-ayuda mt-1" id="ac-base-ayuda">Puedes usar el contador de billetes si lo prefieres.</div>
            <button type="button" class="btn btn-sm btn-link px-0 align-self-start" data-contar="ac-base">
              <i class="fa-solid fa-calculator me-1"></i>Contar billetes y monedas
            </button>

            <div class="ac-acciones mt-auto">
              <button type="button" class="btn btn-primary w-100" id="ac-btn-abrir">
                <i class="fa-solid fa-lock-open me-1"></i>Abrir caja
              </button>
            </div>
          </div>
        </div>

        <!-- Cierre -->
        <div class="card h-100 d-none" id="ac-panel-cierre">
          <div class="card-body p-3 d-flex flex-column">
            <h6 class="mb-1"><span class="ac-paso ac-paso-cerrar"><i class="fa-solid fa-lock"></i></span>Cerrar caja</h6>
            <p class="small text-muted mb-3">Cuenta lo que tienes y regístralo por medio de pago. El sistema comparará estos valores con lo vendido.</p>

            <div class="ac-base-ref mb-3 d-flex justify-content-between align-items-center gap-2">
              <span><i class="fa-solid fa-circle-info me-1 text-primary"></i>Base del turno <small class="text-muted">(no la incluyas en el efectivo)</small></span>
              <strong id="ac-base-ref">$0</strong>
            </div>

            <div class="row g-3">
              <div class="col-12">
                <label class="form-label mb-1" for="ac-efectivo"><i class="fa-solid fa-money-bill-wave me-1 text-primary"></i>Efectivo</label>
                <div class="input-group">
                  <span class="input-group-text">$</span>
                  <input type="text" inputmode="numeric" autocomplete="off" class="form-control ac-monto ac-money ac-cierre-valor" id="ac-efectivo" value="0">
                  <button type="button" class="btn btn-outline-primary" data-contar="ac-efectivo" title="Contar billetes y monedas">
                    <i class="fa-solid fa-calculator"></i><span class="d-none d-sm-inline ms-1">Contar</span>
                  </button>
                </div>
                <div class="ac-campo-ayuda mt-1">Billetes y monedas de las ventas, sin la base.</div>
              </div>
              <div class="col-12 col-sm-6">
                <label class="form-label mb-1" for="ac-tarjetas"><i class="fa-solid fa-building-columns me-1 text-primary"></i>Tarjetas / Bancos</label>
                <div class="input-group">
                  <span class="input-group-text">$</span>
                  <input type="text" inputmode="numeric" autocomplete="off" class="form-control ac-monto ac-money ac-cierre-valor" id="ac-tarjetas" value="0">
                </div>
                <div class="ac-campo-ayuda mt-1">Datáfono, transferencias, Nequi, etc.</div>
              </div>
              <div class="col-12 col-sm-6">
                <label class="form-label mb-1" for="ac-otros"><i class="fa-solid fa-wallet me-1 text-primary"></i>Otros medios</label>
                <div class="input-group">
                  <span class="input-group-text">$</span>
                  <input type="text" inputmode="numeric" autocomplete="off" class="form-control ac-monto ac-money ac-cierre-valor" id="ac-otros" value="0">
                </div>
                <div class="ac-campo-ayuda mt-1">Bonos, vales u otros.</div>
              </div>
              <div class="col-12">
                <label class="form-label mb-1" for="ac-observaciones">Observaciones <small class="text-muted">(opcional)</small></label>
                <textarea class="form-control form-control-sm" id="ac-observaciones" rows="2" maxlength="1000"
                          placeholder="Ej.: faltante por cambio mal entregado, pago registrado en otro medio…"></textarea>
              </div>
            </div>

            <div class="ac-total mt-3">
              <div>
                <div class="small text-muted">Total registrado</div>
                <div class="small text-muted" id="ac-total-desglose">Efectivo + bancos + otros</div>
              </div>
              <div class="ac-total-valor" id="ac-total">$0</div>
            </div>

            <div class="ac-acciones mt-3">
              <button type="button" class="btn btn-primary w-100" id="ac-btn-cerrar">
                <i class="fa-solid fa-lock me-1"></i>Cerrar caja
              </button>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- ============================ HISTORIAL ============================ -->
  <div class="tab-pane fade" id="ac-tab-historial" role="tabpanel">

    <!-- Filtros -->
    <div class="card mb-3">
      <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 pb-2">
        <h5 class="card-title m-0">Historial de cierres</h5>
        <span class="badge bg-label-primary hc-bodega" title="El historial muestra la bodega seleccionada en tu sesión">
          <i class="fa-solid fa-store me-1"></i><span id="hc-bodega-nombre">Bodega de la sesión</span>
        </span>
      </div>
      <div class="card-body p-3 pt-2">
        <div class="mb-3">
          <div class="hc-presets" role="group" aria-label="Rangos rápidos">
            <button type="button" class="btn btn-sm btn-outline-primary hc-preset" data-rango="hoy">Hoy</button>
            <button type="button" class="btn btn-sm btn-outline-primary hc-preset" data-rango="ayer">Ayer</button>
            <button type="button" class="btn btn-sm btn-outline-primary hc-preset" data-rango="7d">Últimos 7 días</button>
            <button type="button" class="btn btn-sm btn-outline-primary hc-preset" data-rango="mes">Este mes</button>
          </div>
        </div>
        <div class="row g-2 align-items-end">
          <div class="col-6 col-md-4">
            <label class="form-label mb-1" for="hc-fecha-inicial">Desde</label>
            <input type="date" class="form-control form-control-sm" id="hc-fecha-inicial">
          </div>
          <div class="col-6 col-md-4">
            <label class="form-label mb-1" for="hc-fecha-final">Hasta</label>
            <input type="date" class="form-control form-control-sm" id="hc-fecha-final">
          </div>
          <div class="col-12 col-md-4">
            <button type="button" class="btn btn-sm btn-primary w-100" id="hc-consultar">
              <i class="fa fa-search me-1"></i>Consultar
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Aviso de la base -->
    <div id="hc-aviso-base" class="d-none"></div>

    <!-- Indicadores -->
    <div id="hc-kpis" class="row g-2 mb-3"></div>

    <!-- Barra de herramientas -->
    <div id="hc-toolbar" class="hc-toolbar d-none">
      <div class="hc-toolbar-top">
        <div class="input-group input-group-sm input-group-merge hc-search">
          <span class="input-group-text"><i class="fa fa-search"></i></span>
          <input type="search" class="form-control" id="hc-buscar" placeholder="Buscar cajero o #cierre">
        </div>
        <div class="hc-toolbar-actions">
          <div class="input-group input-group-sm hc-orden-wrap">
            <span class="input-group-text"><i class="fa-solid fa-arrow-down-wide-short"></i></span>
            <select class="form-select form-select-sm" id="hc-orden" aria-label="Ordenar">
              <option value="alerta">Mayor diferencia primero</option>
              <option value="dif_asc">Mayor faltante primero</option>
              <option value="dif_desc">Mayor sobrante primero</option>
              <option value="fecha_desc">Más recientes</option>
              <option value="fecha_asc">Más antiguos</option>
            </select>
          </div>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2 mt-2">
        <div class="hc-chips flex-grow-1" id="hc-chips">
          <button type="button" class="btn btn-sm btn-outline-secondary hc-chip active" data-filtro="todos">Todos <span class="badge bg-label-primary ms-1" data-count="todos">0</span></button>
          <button type="button" class="btn btn-sm btn-outline-secondary hc-chip" data-filtro="revisar">Con diferencia <span class="badge bg-label-primary ms-1" data-count="revisar">0</span></button>
          <button type="button" class="btn btn-sm btn-outline-secondary hc-chip" data-filtro="faltante">Faltantes <span class="badge bg-label-primary ms-1" data-count="faltante">0</span></button>
          <button type="button" class="btn btn-sm btn-outline-secondary hc-chip" data-filtro="sobrante">Sobrantes <span class="badge bg-label-primary ms-1" data-count="sobrante">0</span></button>
          <button type="button" class="btn btn-sm btn-outline-secondary hc-chip" data-filtro="cruzado">Cruce de medios <span class="badge bg-label-primary ms-1" data-count="cruzado">0</span></button>
          <button type="button" class="btn btn-sm btn-outline-secondary hc-chip" data-filtro="ok">Cuadrados <span class="badge bg-label-primary ms-1" data-count="ok">0</span></button>
        </div>
        <span class="small text-muted text-nowrap d-none d-md-inline" id="hc-resultado-txt"></span>
      </div>
    </div>

    <!-- Resultados -->
    <div id="hc-lista-wrap" class="card hc-list mt-2 d-none">
      <div class="card-header d-flex justify-content-between align-items-center py-3">
        <h5 class="card-title m-0">Cierres del periodo</h5>
        <small class="text-muted" id="hc-lista-total"></small>
      </div>
      <div class="hc-list-head">
        <span>Cajero / turno</span>
        <span><i class="fa-solid fa-money-bill-wave me-1"></i>Efectivo</span>
        <span><i class="fa-solid fa-building-columns me-1"></i>Bancos</span>
        <span><i class="fa-solid fa-wallet me-1"></i>Otros</span>
        <span class="text-end">Diferencia</span>
        <span></span>
      </div>
      <div id="hc-lista"></div>
    </div>
    <div id="hc-vacio" class="d-none"></div>

    <!-- Gráficos -->
    <div id="hc-graficos" class="row g-3 mt-3 d-none">
      <div class="col-12 col-lg-6">
        <div class="card h-100">
          <div class="card-header pb-0">
            <h5 class="card-title m-0">Medios de pago (sistema)</h5>
            <small class="text-muted">Recaudo del periodo según el sistema</small>
          </div>
          <div class="card-body p-3">
            <div id="hc-grafico-medios"></div>
          </div>
        </div>
      </div>
      <div class="col-12 col-lg-6">
        <div class="card h-100">
          <div class="card-header pb-0">
            <h5 class="card-title m-0">Comparativa cajero vs sistema</h5>
            <small class="text-muted">Lo registrado por los cajeros frente a lo calculado</small>
          </div>
          <div class="card-body p-3">
            <div id="hc-grafico-comparativa"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal: detalle del cierre (historial) -->
<div class="modal fade" id="hc-modal-detalle" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="hc-detalle-titulo">Detalle del cierre</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body" id="hc-detalle-body"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-sm btn-outline-primary d-none" id="hc-detalle-editar"><i class="fa-solid fa-pen-to-square me-1"></i>Editar valores</button>
        <button type="button" class="btn btn-sm btn-primary" id="hc-detalle-print"><i class="fa fa-print me-1"></i>Imprimir</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: contador de billetes y monedas -->
<div class="modal fade" id="ac-modal-contar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa-solid fa-calculator me-2 text-primary"></i>Contar billetes y monedas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body" id="ac-contar-body"></div>
      <div class="modal-footer flex-column align-items-stretch gap-2">
        <div class="ac-total m-0">
          <span class="small text-muted">Total contado</span>
          <span class="ac-total-valor" id="ac-contar-total">$0</span>
        </div>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-sm btn-outline-secondary" id="ac-contar-limpiar"><i class="fa-solid fa-eraser me-1"></i>Limpiar</button>
          <button type="button" class="btn btn-sm btn-primary flex-fill" id="ac-contar-usar"><i class="fa-solid fa-check me-1"></i>Usar este valor</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal: editar cierre (requiere permiso EditarCierreCaja) -->
<div class="modal fade" id="ac-modal-editar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa-solid fa-pen-to-square me-2 text-primary"></i>Editar cierre <span id="ac-editar-num"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="ac-editar-id" value="0" />
        <div class="small text-muted mb-3" id="ac-editar-fechas"></div>
        <div class="row g-2">
          <div class="col-6">
            <label class="form-label mb-1" for="ac-editar-base">Base</label>
            <input type="text" inputmode="numeric" class="form-control form-control-sm ac-monto ac-money" id="ac-editar-base">
          </div>
          <div class="col-6">
            <label class="form-label mb-1" for="ac-editar-efectivo">Efectivo</label>
            <input type="text" inputmode="numeric" class="form-control form-control-sm ac-monto ac-money" id="ac-editar-efectivo">
          </div>
          <div class="col-6">
            <label class="form-label mb-1" for="ac-editar-tarjetas">Tarjetas / Bancos</label>
            <input type="text" inputmode="numeric" class="form-control form-control-sm ac-monto ac-money" id="ac-editar-tarjetas">
          </div>
          <div class="col-6">
            <label class="form-label mb-1" for="ac-editar-otros">Otros medios</label>
            <input type="text" inputmode="numeric" class="form-control form-control-sm ac-monto ac-money" id="ac-editar-otros">
          </div>
          <div class="col-12">
            <label class="form-label mb-1" for="ac-editar-obs">Motivo de la modificación <span class="text-danger">*</span></label>
            <textarea id="ac-editar-obs" class="form-control form-control-sm" rows="3" maxlength="1000" placeholder="Explica por qué se corrigen los valores"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-sm btn-primary" id="ac-editar-guardar"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar cambios</button>
      </div>
    </div>
  </div>
</div>


<?php
$scriptsPagina = [
  '../controllers/AperturaCierreCaja.js?' . rand()
];

include '../dependencies/footer.php';
?>