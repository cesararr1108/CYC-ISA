<?php
session_start();

$titulo = 'POS';
include '../dependencies/header.php';
?>
<style>
  /* ---------- Variables de color (claro / oscuro) ---------- */
  :root {
    --pos-superficie: #fff;
    --pos-borde: #d9dee3;
    --pos-primary: #696cff;
    --pos-primary-suave: rgba(105, 108, 255, .05);
    --pos-primary-fondo: rgba(105, 108, 255, .12);
    --pos-texto-fuerte: #566a7f;
    --pos-texto-tenue: #8592a3;
    --pos-texto-suave: #a1acb8;
    --pos-foco: #f5f2ff;
    --pos-superficie-2: #fff;
  }
  /* Tema oscuro del sistema: dark-theme.css lo activa con data-color-scheme="dark" o data-bs-theme="dark" en <html> */
  html[data-color-scheme="dark"],
  html[data-bs-theme="dark"] {
    --pos-superficie: #2b2c40;
    --pos-superficie-2: #34354d;
    --pos-borde: #45465b;
    --pos-primary: #696cff;
    --pos-primary-suave: rgba(105, 108, 255, .12);
    --pos-primary-fondo: rgba(105, 108, 255, .20);
    --pos-texto-fuerte: #f5f5f9;
    --pos-texto-tenue: #a7acb2;
    --pos-texto-suave: #8a8fa0;
    --pos-foco: #34354d;
  }

  .form-control:focus { background-color: var(--pos-foco) !important; }
  .form-control-plaintext { background-color: var(--pos-superficie) !important; }

  /* ---------- Tablas compactas (escritorio) ---------- */
  #carrito thead th, .datos thead th { font-size: .7rem; padding: 4px; white-space: nowrap; }
  #carrito tbody td, .datos tbody td { font-size: .72rem; padding: 3px 4px; vertical-align: middle; }

  #descripcion_grid, #stock_grid, #laboratorio_grid:disabled {
    background-color: var(--pos-superficie); border-left: none; border-right: none; border-top: none;
  }

  .data-lotes tbody tr:focus { background-color: rgb(95, 50, 255); color: var(--pos-superficie); outline: none; cursor: pointer; }

  .label-totales { font-size: .72rem; font-weight: 600; min-width: 78px; }
  .input-group.input-group-sm .input-group-text { font-size: .72rem; font-weight: 600; }

  input[type=number]::-webkit-inner-spin-button,
  input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; appearance: none; margin: 0; }
  input[type=number] { -moz-appearance: textfield; appearance: textfield; }

  .pagos-scroll { max-height: 220px; overflow-y: auto; }
  #table-pagos thead th { position: sticky; top: 0; }

  .nota-amarilla {
    background: #fff8d6; border: 1px solid #f3d97a; border-radius: 6px; color: #5c4b00;
    padding: .75rem; resize: vertical; box-shadow: inset 0 1px 2px rgba(0,0,0,.04); transition: all .2s ease;
  }
  .nota-amarilla:focus { background: #fffbe8; border-color: #e8bf3d; box-shadow: 0 0 0 .15rem rgba(255,193,7,.15); outline: none; }
  .nota-amarilla::placeholder { color: #9a8740; font-style: italic; }

  /* ---------- Encabezado y pestañas ---------- */
  #tabs { flex-wrap: nowrap; overflow-x: auto; overflow-y: hidden; scrollbar-width: none; }
  #tabs::-webkit-scrollbar { display: none; }
  #tabs .nav-link { white-space: nowrap; }

  .grid-referencia .form-label { font-size: .7rem; font-weight: 600; margin-bottom: 2px; }
  /* Código + descripción en una sola línea */
  .producto-cabecera #codigo_grid { flex: 0 0 120px; max-width: 120px; background-color: var(--pos-superficie); border-top: none; border-left: none; }
  /* Valores más amplios y legibles; Dto e Iva angostos */
  .grid-referencia .campo-valor input { font-weight: 600; }
  .grid-referencia .campo-pct input { padding-left: .25rem; padding-right: .25rem; }
  @media (min-width: 992px) {
    .grid-referencia .campo-pct { flex: 0 0 64px; max-width: 64px; }
  }
  #und_grid { height: 60px; }

  /* Indicador mientras busca terceros (jQuery UI agrega esta clase durante la petición) */
  .ui-autocomplete-loading {
    background-image: linear-gradient(90deg, transparent 0%, rgba(105,108,255,.25) 50%, transparent 100%);
    background-size: 200% 100%; animation: buscando-tercero 1s linear infinite;
  }
  @keyframes buscando-tercero { from { background-position: 200% 0; } to { background-position: -200% 0; } }
  .ui-autocomplete { max-height: 320px; overflow-y: auto; overflow-x: hidden; z-index: 1090 !important; }

  /* ---------- Listas de precios ---------- */
  .lista-precio-tile {
    border: 1px solid var(--pos-borde); border-radius: .375rem;
    padding: .3rem .55rem; height: 100%; background: var(--pos-superficie); transition: border-color .2s, background-color .2s;
  }
  .lista-precio-titulo { font-size: .68rem; font-weight: 600; color: var(--pos-texto-tenue); }
  .lista-precio-valor {
    display: block; width: 100%; border: 0; padding: 0; background: transparent; outline: none;
    font-size: 1rem; font-weight: 700; color: var(--pos-texto-fuerte); line-height: 1.25; cursor: default;
  }
  .lista-precio-uso { font-size: .58rem; padding: .2em .45em; }
  .lista-precio-nota { display: block; color: var(--pos-texto-suave); font-size: .64rem; line-height: 1.2; }
  .lista-precio-tile.activa { border-color: var(--pos-primary); background: var(--pos-primary-suave); }
  .lista-precio-tile.activa .lista-precio-valor { color: var(--pos-primary); }
  .lista-precio-tile.inactiva { opacity: .65; }

  /* ---------- Botón información del cliente ---------- */
  .btn-info-cliente {
    display: flex; align-items: center; gap: .55rem; text-align: left;
    border: 1px dashed var(--pos-borde); border-radius: .375rem;
    padding: .3rem .55rem; background: var(--pos-superficie); color: inherit; transition: all .2s ease;
  }
  .btn-info-cliente-titulo { font-size: .78rem; line-height: 1.2; }
  .btn-info-cliente-sub { font-size: .66rem; line-height: 1.2; }
  .btn-info-cliente:hover:not(:disabled), .btn-info-cliente:focus-visible {
    border-style: solid; border-color: var(--pos-primary); background: var(--pos-primary-suave);
  }
  .btn-info-cliente .fa-chevron-right { transition: transform .2s ease; }
  .btn-info-cliente:hover:not(:disabled) .fa-chevron-right { color: var(--pos-primary) !important; transform: translateX(2px); }
  .btn-info-cliente:disabled { opacity: .55; cursor: not-allowed; }
  .btn-info-cliente-icono {
    flex: 0 0 26px; width: 26px; height: 26px; border-radius: .375rem; font-size: .8rem;
    display: flex; align-items: center; justify-content: center;
    background: var(--pos-primary-fondo); color: var(--pos-primary);
  }

  /* ---------- Información del cliente ---------- */
  .info-cliente-avatar {
    flex: 0 0 52px; width: 52px; height: 52px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    background: var(--pos-primary-fondo); color: var(--pos-primary);
    font-weight: 700; font-size: 1.1rem; text-transform: uppercase;
  }
  .info-cliente-seccion {
    font-size: .7rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase;
    color: var(--pos-texto-tenue); margin-bottom: .5rem;
  }
  .info-cliente-dato {
    border: 1px solid var(--pos-borde); border-radius: .5rem;
    padding: .5rem .75rem; height: 100%; display: flex; flex-direction: column; gap: .15rem;
  }
  .info-cliente-dato small { color: var(--pos-texto-tenue); font-size: .72rem; }
  .info-cliente-dato > span { font-weight: 600; min-height: 1.25rem; }
  .info-cliente-destacado { background: var(--pos-primary-suave); border-color: rgba(105, 108, 255, .3); }
  .info-cliente-chip {
    border: 1px solid var(--pos-borde); border-radius: 2rem;
    padding: .3rem .4rem .3rem .85rem; font-size: .8rem; font-weight: 600;
    display: inline-flex; align-items: center; gap: .5rem;
  }
  .min-w-0 { min-width: 0; }

  .resumen-pago { border: 1px solid var(--pos-borde); border-radius: .5rem; padding: .5rem; text-align: center; }
  .resumen-pago small { color: var(--pos-texto-tenue); }
  .resumen-pago h5 { margin: 0; }

  /* ---------- Barra fija inferior (solo móvil, pestaña carrito) ---------- */
  .pos-barra-movil {
    position: fixed; left: 0; right: 0; bottom: 0; z-index: 1030;
    background: var(--pos-superficie); border-top: 1px solid var(--pos-borde);
    box-shadow: 0 -2px 8px rgba(0,0,0,.06);
    padding: .5rem 1rem calc(.5rem + env(safe-area-inset-bottom));
    display: flex; align-items: center; justify-content: space-between; gap: .75rem;
  }

  #btnToggleTotales .icono-toggle { transition: transform .2s ease; }
  #btnToggleTotales[aria-expanded="true"] .icono-toggle { transform: rotate(180deg); }

  /* ---------- Móvil ---------- */
  @media (max-width: 767.98px) {
    .pos-card-body { padding: .75rem; padding-bottom: 5rem; }
    #guardar-documento { width: 100%; }

    /* Evita el zoom automático de iOS al enfocar campos de captura */
    .producto-cabecera #codigo_grid { flex-basis: 90px; max-width: 90px; }
    #filtro, #cantidad_grid, #valor_recibido, #voucher, #txtBusquedaTercero, #txtBusquedaTerceroTmp, #precion_grid { font-size: 16px; }

    /* Tablas como tarjetas: cada <td> usa su data-label */
    .tabla-cards, .tabla-cards tbody { display: block; width: 100%; }
    .tabla-cards thead { display: none; }
    .tabla-cards tbody tr {
      display: grid; grid-template-columns: 1fr 1fr; gap: .2rem .75rem;
      border: 1px solid var(--pos-borde); border-radius: .5rem;
      padding: .5rem .75rem; margin-bottom: .5rem; background: var(--pos-superficie);
    }
    .tabla-cards tbody td {
      display: flex; justify-content: space-between; gap: .5rem;
      border: 0 !important; padding: 0 !important; font-size: .8rem !important; text-align: right;
    }
    .tabla-cards tbody td::before { content: attr(data-label); color: var(--pos-texto-tenue); font-weight: 600; text-align: left; }
    .tabla-cards tbody td.td-full { grid-column: 1 / -1; font-weight: 600; text-align: left; }
    .tabla-cards tbody td.td-full::before, .tabla-cards tbody td.acciones::before { content: none; }
    .tabla-cards tbody td.acciones { grid-column: 1 / -1; justify-content: flex-end; gap: .35rem; margin-top: .25rem; }
  }
  /* ---------- Ajustes específicos del tema oscuro ---------- */
  html[data-color-scheme="dark"] .form-control:focus,
  html[data-bs-theme="dark"] .form-control:focus { color: #cbcbe2; }
  html[data-color-scheme="dark"] .nota-amarilla,
  html[data-bs-theme="dark"] .nota-amarilla {
    background: #3a3628; border-color: #6b5d2e; color: #e9d99a;
  }
  html[data-color-scheme="dark"] .nota-amarilla:focus,
  html[data-bs-theme="dark"] .nota-amarilla:focus { background: #403b2b; border-color: #8a7735; }
  html[data-color-scheme="dark"] .nota-amarilla::placeholder,
  html[data-bs-theme="dark"] .nota-amarilla::placeholder { color: #a8995e; }
  html[data-color-scheme="dark"] .pos-barra-movil,
  html[data-bs-theme="dark"] .pos-barra-movil { box-shadow: 0 -2px 10px rgba(0,0,0,.45); }
  html[data-color-scheme="dark"] .lista-precio-tile.inactiva,
  html[data-bs-theme="dark"] .lista-precio-tile.inactiva { opacity: 1; border-style: dashed; background: transparent; }
  html[data-color-scheme="dark"] .lista-precio-tile.inactiva .lista-precio-valor,
  html[data-bs-theme="dark"] .lista-precio-tile.inactiva .lista-precio-valor { color: var(--pos-texto-tenue); }
  html[data-color-scheme="dark"] .lista-precio-valor,
  html[data-bs-theme="dark"] .lista-precio-valor { color: var(--pos-texto-fuerte); -webkit-text-fill-color: currentColor; }
  html[data-color-scheme="dark"] .pos-barra-movil,
  html[data-bs-theme="dark"] .pos-barra-movil { background: var(--pos-superficie-2); border-top-color: var(--pos-borde); }
  html[data-color-scheme="dark"] .pos-barra-movil .text-muted,
  html[data-bs-theme="dark"] .pos-barra-movil .text-muted { color: var(--pos-texto-tenue) !important; }
  html[data-color-scheme="dark"] #modalInfo .modal-header,
  html[data-bs-theme="dark"] #modalInfo .modal-header { border-bottom-color: var(--pos-borde); }
  html[data-color-scheme="dark"] .info-cliente-dato,
  html[data-bs-theme="dark"] .info-cliente-dato { background: var(--pos-superficie-2); }
  html[data-color-scheme="dark"] .info-cliente-dato > span,
  html[data-bs-theme="dark"] .info-cliente-dato > span,
  html[data-color-scheme="dark"] #info_nombre,
  html[data-bs-theme="dark"] #info_nombre,
  html[data-color-scheme="dark"] #info_nit,
  html[data-bs-theme="dark"] #info_nit { color: var(--pos-texto-fuerte) !important; }
  html[data-color-scheme="dark"] .info-cliente-seccion,
  html[data-bs-theme="dark"] .info-cliente-seccion { color: var(--pos-texto-tenue); }
  html[data-color-scheme="dark"] .info-cliente-chip,
  html[data-bs-theme="dark"] .info-cliente-chip { background: var(--pos-superficie-2); color: var(--pos-texto-fuerte); }
  html[data-color-scheme="dark"] .info-cliente-destacado,
  html[data-bs-theme="dark"] .info-cliente-destacado { border-color: rgba(105, 108, 255, .45); }
  html[data-color-scheme="dark"] .btn-info-cliente,
  html[data-bs-theme="dark"] .btn-info-cliente { color: #cbcbe2; }
  html[data-color-scheme="dark"] .ui-autocomplete-loading,
  html[data-bs-theme="dark"] .ui-autocomplete-loading {
    background-image: linear-gradient(90deg, transparent 0%, rgba(105,108,255,.35) 50%, transparent 100%);
  }

</style>

<input type="hidden" value="<?php echo htmlspecialchars(($_SESSION["Ses_nombres"] ?? '') . ' ' . ($_SESSION["Ses_apellidos"] ?? '')); ?>" readonly id="nombres_usuario" />
<input type="hidden" value="" id="tmp" readonly />
<input type="hidden" value="" id="vendedor" readonly />
<input type="hidden" id="id_bodega_session" value="<?php echo !empty($_SESSION["Ses_id_bodega"]) ? (int)$_SESSION["Ses_id_bodega"] : 0; ?>" />
<input type="hidden" id="tipo_referencia" value="" />

<div class="card">
  <div class="card-body pos-card-body">

    <!-- Encabezado + acciones (fuera de las pestañas para que no se rompan en móvil) -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
      <h5 class="fw-bold mb-0"><span class="text-muted fw-light">POS / </span>Ventas</h5>
      <div class="d-flex flex-wrap gap-1">
        <button type="button" class="btn btn-sm btn-outline-secondary" id="domicilio" title="Es domicilio">
          <i class="fa-solid fa-motorcycle"></i><span class="d-none d-sm-inline">&nbsp;Es domicilio</span>
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="nuevo" title="Nuevo">
          <i class="fa-regular fa-file"></i><span class="d-none d-sm-inline">&nbsp;Nuevo</span>
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-info-permisos" title="Permisos del módulo">
          <i class="fa-solid fa-circle-info"></i><span class="d-none d-sm-inline">&nbsp;Permisos</span>
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-crear-cliente" title="Crear cliente">
          <i class="fa-regular fa-address-book"></i><span class="d-none d-sm-inline">&nbsp;Crear Cliente</span>
        </button>
        <button type="button" class="btn btn-sm btn-primary js-abrir-pagos" id="guardar" title="Guardar (F4)">
          <i class="fa-regular fa-floppy-disk"></i><span class="d-none d-sm-inline">&nbsp;Guardar F4</span>
        </button>
      </div>
    </div>

    <div class="nav-align-top">
      <ul class="nav nav-tabs" role="tablist" id="tabs">
        <li class="nav-item">
          <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-tercero" aria-controls="navs-tercero" aria-selected="true"><i class="fa-solid fa-user-tie"></i>&nbsp;Cliente</button>
        </li>
        <li class="nav-item">
          <button id="btn-navs-creacion" disabled type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-creacion" aria-controls="navs-creacion" aria-selected="false"><i class="fa-solid fa-cart-plus"></i>&nbsp;Carrito</button>
        </li>
        <li class="nav-item">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-temporal" aria-controls="navs-temporal" aria-selected="false"><i class="fa-regular fa-clock"></i>&nbsp;Temporal</button>
        </li>
        <li class="nav-item">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-fe" aria-controls="navs-fe" aria-selected="false"><i class="fa-solid fa-file-invoice"></i>&nbsp;FE</button>
        </li>
      </ul>

      <div class="tab-content px-2 px-md-4">

        <!-- ===================== CLIENTE ===================== -->
        <div class="tab-pane fade show active" id="navs-tercero" role="tabpanel">
          <form id="FormEncabezado" class="needs-validation form-basico" novalidate onsubmit="return false;">
            <div class="row g-3">
              <div class="col-12 col-lg-8">
                <div class="card p-0 h-100">
                  <h5 class="card-header text-primary form-label fw-bold">Tercero</h5>
                  <div class="card-body">
                    <label for="txtBusquedaTercero" class="form-label">Búsqueda de tercero</label>
                    <div class="input-group input-group-sm input-group-merge">
                      <span class="input-group-text"><i class="bx bx-search"></i></span>
                      <input type="text" class="form-control" placeholder="Búsqueda de tercero..." id="txtBusquedaTercero" autocomplete="off">
                    </div>
                    <input type="hidden" id="id_tercero" name="id_tercero" value="0">
                    <div class="row g-2 mt-1">
                      <div class="col-12 col-md-4">
                        <label for="nit" class="form-label">NIT</label>
                        <input type="text" class="form-control form-control-sm" name="nit" id="nit" readonly>
                      </div>
                      <div class="col-12 col-md-8">
                        <label for="tratamiento" class="form-label">Tratamiento</label>
                        <input type="text" class="form-control form-control-sm text-uppercase" name="tratamiento" id="tratamiento" readonly>
                      </div>
                      <div class="col-12">
                        <label for="razon_comercial" class="form-label">Razón Comercial</label>
                        <input type="text" class="form-control form-control-sm text-uppercase" name="razon_comercial" id="razon_comercial" readonly>
                      </div>
                      <div class="col-12">
                        <label for="id_tercero_dir" class="form-label">Dirección</label>
                        <select id="id_tercero_dir" name="id_tercero_dir" class="form-select form-select-sm"></select>
                      </div>
                      <!-- Listas de precios: se resalta la que se aplica en la venta -->
                      <div class="col-6">
                        <div class="lista-precio-tile" id="tile_lista_tercero">
                          <div class="d-flex justify-content-between align-items-center">
                            <label for="lista_tercero" class="lista-precio-titulo mb-0"><i class="fa-solid fa-user-tag me-1"></i>Lista tercero</label>
                            <span class="badge bg-primary lista-precio-uso d-none">En uso</span>
                          </div>
                          <input type="text" class="lista-precio-valor" value="0" id="lista_tercero" readonly tabindex="-1" />
                          <small class="lista-precio-nota" id="nota_lista_tercero">Asignada al cliente</small>
                        </div>
                      </div>
                      <div class="col-6">
                        <div class="lista-precio-tile" id="tile_lista_almacen">
                          <div class="d-flex justify-content-between align-items-center">
                            <label for="lista_almacen" class="lista-precio-titulo mb-0"><i class="fa-solid fa-warehouse me-1"></i>Lista almacén</label>
                            <span class="badge bg-primary lista-precio-uso d-none">En uso</span>
                          </div>
                          <input type="text" class="lista-precio-valor" value="0" id="lista_almacen" readonly tabindex="-1" />
                          <small class="lista-precio-nota" id="nota_lista_almacen">Se usa si el cliente no tiene lista</small>
                        </div>
                      </div>
                    </div>
                    <button type="button" class="btn btn-info-cliente w-100 mt-2" id="btn_info_cliente" data-bs-toggle="modal" data-bs-target="#modalInfo" disabled>
                      <span class="btn-info-cliente-icono"><i class="fa-solid fa-address-card"></i></span>
                      <span class="text-start flex-grow-1">
                        <span class="d-block fw-semibold btn-info-cliente-titulo">Información del cliente</span>
                        <small class="text-muted d-block btn-info-cliente-sub">Condición, cupo, zona y clasificación</small>
                      </span>
                      <i class="fa-solid fa-chevron-right small text-muted"></i>
                    </button>
                  </div>
                </div>
              </div>

              <div class="col-12 col-lg-4">
                <div class="card h-100">
                  <h5 class="card-header text-primary form-label fw-bold">Condiciones</h5>
                  <div class="card-body">
                    <label for="tipos" class="form-label">Tipo</label>
                    <select class="form-select form-select-sm" id="tipos" name="id_tipo_documentos"></select>
                    <label for="id_condicion" class="form-label mt-2">Condición de pago</label>
                    <select class="form-select form-select-sm" id="id_condicion" name="id_condicion" disabled></select>
                    <label for="bodegas" class="form-label mt-2">Bodega</label>
                    <select class="form-select form-select-sm" id="bodegas" name="id_bodega"></select>
                    <input type="hidden" id="id_perfil_tributario" value="0" readonly>
                    <label class="form-label mt-2">Perfil tributario</label>
                    <select class="form-select form-select-sm" id="perfil_tributario_compra" disabled>
                      <option value="">NO APLICA</option>
                    </select>
                    <label class="form-label mt-2">Concepto de retención</label>
                    <select class="form-select form-select-sm" id="concepto_retencion" name="id_concepto_retencion">
                      <option value="">NO APLICA</option>
                    </select>
                    <label for="notas" class="form-label mt-2">Notas</label>
                    <textarea class="form-control nota-amarilla" id="notas" name="notas" maxlength="500" rows="2"></textarea>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>

        <!-- ===================== CARRITO ===================== -->
        <div class="tab-pane fade" id="navs-creacion" role="tabpanel">
          <!-- En móvil los valores quedan ocultos; este botón los despliega -->
          <button type="button" class="btn btn-sm btn-outline-primary w-100 d-flex d-md-none justify-content-between align-items-center mb-2"
                  id="btnToggleTotales" data-bs-toggle="collapse" data-bs-target="#panelTotales" aria-expanded="false" aria-controls="panelTotales">
            <span><i class="fa-solid fa-calculator me-1"></i>Doc <span id="resumen_numero">Nuevo</span></span>
            <span>Neto <strong id="resumen_neto">$0</strong><i class="fa-solid fa-chevron-down ms-2 icono-toggle"></i></span>
          </button>
          <div class="collapse d-md-block" id="panelTotales">
          <div class="row g-2 mb-2">
            <div class="col-12 col-md-3">
              <div class="input-group input-group-sm">
                <span class="input-group-text label-totales">Número</span>
                <input name="numero" id="numero" type="number" class="form-control form-control-sm" readonly />
              </div>
            </div>
            <div class="col-12 col-md-9">
              <div class="row g-1">
                <div class="col-6 col-md-3"><div class="input-group input-group-sm input-group-merge"><span class="input-group-text label-totales">Valor Bruto</span><input type="text" class="form-control form-control-sm text-end" id="t_bruto" value="0" readonly></div></div>
                <div class="col-6 col-md-3"><div class="input-group input-group-sm input-group-merge"><span class="input-group-text label-totales">Dcto</span><input type="text" class="form-control form-control-sm text-end" id="t_dcto" value="0" readonly></div></div>
                <div class="col-6 col-md-3"><div class="input-group input-group-sm input-group-merge"><span class="input-group-text label-totales">Iva</span><input type="text" class="form-control form-control-sm text-end" id="t_iva" value="0" readonly></div></div>
                <div class="col-6 col-md-3"><div class="input-group input-group-sm input-group-merge"><span class="input-group-text label-totales">Total</span><input type="text" class="form-control form-control-sm text-end fw-bold" id="t_total" value="0" readonly></div></div>

                <div class="col-6 col-md-3"><div class="input-group input-group-sm input-group-merge"><span class="input-group-text label-totales">RteFte</span><input type="text" class="form-control form-control-sm text-end" id="t_ret_fte" value="$0" readonly></div></div>
                <div class="col-6 col-md-3"><div class="input-group input-group-sm input-group-merge"><span class="input-group-text label-totales">ReteICA</span><input type="text" class="form-control form-control-sm text-end" id="t_ret_ica" value="$0" readonly></div></div>
                <div class="col-6 col-md-3"><div class="input-group input-group-sm input-group-merge"><span class="input-group-text label-totales">ReteIVA</span><input type="text" class="form-control form-control-sm text-end" id="t_ret_iva" value="$0" readonly></div></div>
                <div class="col-6 col-md-3"><div class="input-group input-group-sm input-group-merge"><span class="input-group-text label-totales">Tot. Ret.</span><input type="text" class="form-control form-control-sm text-end" id="t_ret_total" value="$0" readonly></div></div>

                <div class="col-12 col-md-3 offset-md-9">
                  <div class="input-group input-group-sm input-group-merge">
                    <span class="input-group-text text-primary label-totales">Neto Pagar</span>
                    <input type="text" class="form-control form-control-sm text-end text-primary fw-bold" id="t_neto_pagar" value="$0" readonly>
                  </div>
                </div>
              </div>
            </div>
          </div>
          </div><!-- /#panelTotales -->
          <input type="number" class="d-none" id="id_transaccion" readonly value="0">

          <div class="row g-2 mt-1">
            <div class="col-12 col-md-8">
              <label class="form-label text-primary fw-bold mb-1" for="filtro"><i class="fa fa-search"></i>&nbsp;Búsqueda de productos</label>
              <div class="input-group input-group-merge">
                <span class="input-group-text"><i class="bx bx-search"></i></span>
                <input id="filtro" type="search" enterkeyhint="search" class="form-control form-control-sm filtros-busqueda-productos" placeholder="Código, descripción, descripción larga" autocomplete="off" />
                <button type="button" class="btn btn-sm btn-primary" id="btnBusquedaAvanzada" title="Búsqueda avanzada"><i class="fa-solid fa-sliders"></i></button>
              </div>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label text-primary fw-bold mb-1" for="stock_grid"><i class="fa-solid fa-boxes-stacked"></i>&nbsp;Stock</label>
              <div class="input-group input-group-sm input-group-merge">
                <input id="stock_grid" type="text" class="form-control form-control-sm" readonly />
                <span class="input-group-text gap-1">
                  <button type="button" class="btn btn-sm btn-outline-primary position-relative" id="btn_unidades_comprometidas" title="Unidades comprometidas">
                    <i class="fa fa-clock"></i>
                    <span id="unidades_comprometidas" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"></span>
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-primary" id="btn_stock_otras_bodegas" title="Stock en otras bodegas"><i class="fa fa-warehouse"></i></button>
                  <button type="button" class="btn btn-sm btn-outline-primary d-none" id="btn_comision_producto" title="Producto con comisión"><i class="fa fa-percent"></i></button>
                </span>
              </div>
            </div>
          </div>

          <div class="row g-2 mt-1">
            <div class="col-12 col-md-8">
              <!-- Código al lado de la descripción: libera espacio en la grilla para los valores -->
              <div class="input-group input-group-sm producto-cabecera">
                <input type="text" class="form-control form-control-sm data-grid fw-semibold text-primary" id="codigo_grid" placeholder="Código" readonly title="Código" />
                <input type="text" class="form-control form-control-sm data-grid" id="descripcion_grid" placeholder="Descripción" disabled />
              </div>
            </div>
            <div class="col-12 col-md-4"><input type="text" class="form-control form-control-sm data-grid" id="laboratorio_grid" disabled /></div>
          </div>

          <!-- Grilla de captura: la construye Pos.js (con o sin lote) -->
          <div class="mt-2 div-table-data-referencia"></div>

          <input type="number" id="stock_real" value="0" readonly class="d-none" />
          <input type="number" id="id_lote" value="0" readonly class="d-none" />
          <input type="text" id="precio_unitario" value="0" readonly class="d-none" />
          <input type="number" id="id_referencia" value="0" readonly class="d-none" />
          <input type="hidden" id="costo_promedio" value="0" readonly />
          <input type="number" id="id_detalle" value="0" readonly class="d-none">
          <input type="hidden" id="tmp_precio" readonly />
          <input type="hidden" id="comision_grid" readonly />

          <div id="dvReferencias" class="mt-3 table-responsive"></div>
        </div>

        <!-- ===================== TEMPORALES ===================== -->
        <div class="tab-pane fade" id="navs-temporal" role="tabpanel">
          <div class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
              <label class="form-label fw-bold mb-1" for="fecha_inicial">Fecha inicial</label>
              <input type="date" class="form-control form-control-sm" id="fecha_inicial">
            </div>
            <div class="col-6 col-md-2">
              <label class="form-label fw-bold mb-1" for="fecha_final">Fecha final</label>
              <input type="date" class="form-control form-control-sm" id="fecha_final">
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label fw-bold mb-1" for="txtBusquedaTerceroTmp">Cliente</label>
              <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input id="txtBusquedaTerceroTmp" type="text" class="form-control" placeholder="Buscar por nombre, identificación o NIT" autocomplete="off">
              </div>
              <input type="hidden" id="nit_tmp" readonly>
            </div>
            <div class="col-12 col-md-2 d-grid">
              <button class="btn btn-primary btn-sm" id="buscar-temporal"><i class="fa-solid fa-magnifying-glass me-1"></i>Consultar</button>
            </div>
          </div>
          <div id="temporales" class="mt-3"></div>
        </div>

        <!-- ===================== FACTURAS ELECTRÓNICAS ===================== -->
        <div class="tab-pane fade" id="navs-fe" role="tabpanel">
          <div class="row g-2 align-items-end">
            <div class="col-6 col-md-4">
              <label class="form-label mb-1 fw-bold" for="fe_fecha_inicial">Fecha inicial</label>
              <input type="date" class="form-control form-control-sm" id="fe_fecha_inicial">
            </div>
            <div class="col-6 col-md-4">
              <label class="form-label mb-1 fw-bold" for="fe_fecha_final">Fecha final</label>
              <input type="date" class="form-control form-control-sm" id="fe_fecha_final">
            </div>
            <div class="col-12 col-md-4 d-grid">
              <button class="btn btn-sm btn-primary" id="consultar-fe"><i class="fa-solid fa-magnifying-glass me-1"></i>Consultar</button>
            </div>
          </div>
          <div id="listado-facturas" class="mt-3"></div>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- Barra inferior en móvil: total y cobro siempre a la mano -->
<div class="pos-barra-movil d-md-none d-none" id="barraMovil">
  <div>
    <small class="text-muted d-block">Neto a pagar</small>
    <strong class="text-primary fs-5" id="barra_neto">$0</strong>
  </div>
  <button type="button" class="btn btn-primary js-abrir-pagos"><i class="fa-solid fa-cash-register me-1"></i>Cobrar</button>
</div>

<!-- Información del cliente -->
<div class="modal fade" id="modalInfo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down" role="document">
    <div class="modal-content">
      <div class="modal-header border-bottom">
        <h5 class="modal-title text-uppercase fw-bold text-primary"><i class="fa-solid fa-address-card me-1"></i> Información del cliente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <!-- Identificación -->
        <div class="d-flex align-items-center gap-3 mb-3">
          <div class="info-cliente-avatar" id="info_iniciales">--</div>
          <div class="min-w-0">
            <div class="fw-bold text-uppercase text-truncate" id="info_nombre">Sin cliente</div>
            <small class="text-muted d-block text-truncate" id="info_razon"></small>
            <small class="text-muted">NIT <span class="fw-semibold text-body" id="info_nit">-</span></small>
          </div>
        </div>

        <!-- Comercial -->
        <h6 class="info-cliente-seccion">Condiciones comerciales</h6>
        <div class="row g-2 mb-3">
          <div class="col-6">
            <div class="info-cliente-dato">
              <small><i class="fa-solid fa-handshake me-1"></i>Condición</small>
              <span id="info_condicion"></span>
            </div>
          </div>
          <div class="col-6">
            <div class="info-cliente-dato">
              <small><i class="fa-solid fa-tags me-1"></i>Lista de precios</small>
              <span id="info_lista"></span>
            </div>
          </div>
          <div class="col-12">
            <div class="info-cliente-dato info-cliente-destacado">
              <small><i class="fa-solid fa-credit-card me-1"></i>Cupo de crédito</small>
              <span id="info_credito" class="fs-5 text-primary"></span>
            </div>
          </div>
        </div>

        <!-- Zona y contacto -->
        <h6 class="info-cliente-seccion">Zona y contacto</h6>
        <div class="row g-2 mb-3">
          <div class="col-12">
            <div class="info-cliente-dato">
              <small><i class="fa-solid fa-map-location-dot me-1"></i>Zona de ventas</small>
              <span><span id="info_zona_venta" class="badge bg-label-primary me-1"></span><span id="info_zona_descripcion"></span></span>
            </div>
          </div>
          <div class="col-12">
            <div class="info-cliente-dato">
              <small><i class="fa-solid fa-envelope me-1"></i>Email facturación electrónica</small>
              <span id="info_email" class="text-break"></span>
            </div>
          </div>
        </div>

        <!-- Clasificación -->
        <h6 class="info-cliente-seccion">Clasificación</h6>
        <div class="d-flex flex-wrap gap-2">
          <div class="info-cliente-chip">Controlados <span id="info_controlados" class="d-none"></span><span class="badge" id="info_controlados_badge"></span></div>
          <div class="info-cliente-chip">Institucional <span id="info_institucional" class="d-none"></span><span class="badge" id="info_institucional_badge"></span></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- Nuevo cliente -->
<div class="modal fade" id="modalNewCliente" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-fullscreen-md-down" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Nuevo cliente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body p-0"></div>
    </div>
  </div>
</div>

<!-- Medios de pago -->
<div class="modal fade" id="modalMediosPagos" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-fullscreen-md-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa-solid fa-money-check-dollar text-primary"></i> Formas de pago</h5>
        <button class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">

        <div class="row g-2 mb-3">
          <div class="col-6 col-md-3"><div class="resumen-pago"><small>Factura</small><h5 id="valor_factura" class="text-primary">$0</h5></div></div>
          <div class="col-6 col-md-3"><div class="resumen-pago"><small>Pagado</small><h5 id="valor_pago">$0</h5></div></div>
          <div class="col-6 col-md-3"><div class="resumen-pago"><small>Faltante</small><h5 id="faltante">$0</h5></div></div>
          <div class="col-6 col-md-3"><div class="resumen-pago border-primary"><small>Cambio</small><h5 id="cambio" class="text-primary">$0</h5></div></div>
        </div>

        <div class="card shadow-none border mb-3">
          <div class="card-body p-3">
            <small class="text-muted d-block mb-2">
              Seleccione el medio de pago, digite el valor recibido y presione <b>ENTER</b> o <b>Agregar pago</b>.
            </small>
            <div class="row g-2 align-items-end">
              <div class="col-12 col-lg-3">
                <label class="form-label fw-bold text-primary small mb-1" for="forma_pago">Medio de pago</label>
                <select class="form-select form-select-sm" id="forma_pago"></select>
              </div>
              <div class="col-12 col-lg-3">
                <label class="form-label fw-bold text-primary small mb-1" for="valor_recibido">Valor recibido</label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text"><i class="fa-solid fa-dollar-sign"></i></span>
                  <input id="valor_recibido" class="form-control text-end fw-bold" inputmode="decimal" enterkeyhint="done" autocomplete="off" placeholder="0">
                </div>
              </div>
              <div class="col-6 col-lg-2">
                <label class="form-label fw-bold text-primary small mb-1" for="tipo_tarjeta">Tipo tarjeta</label>
                <select class="form-select form-select-sm" id="tipo_tarjeta" disabled>
                  <option value="0">No aplica</option>
                  <option value="1">Débito</option>
                  <option value="2">Crédito</option>
                </select>
              </div>
              <div class="col-6 col-lg-2">
                <label class="form-label fw-bold text-primary small mb-1" for="voucher">Voucher</label>
                <input class="form-control form-control-sm" id="voucher" maxlength="20" autocomplete="off" placeholder="Número" disabled>
              </div>
              <div class="col-12 col-lg-2 d-grid">
                <button class="btn btn-primary btn-sm" id="addPay"><i class="fa-solid fa-plus me-1"></i>Agregar pago</button>
              </div>
            </div>
          </div>
        </div>

        <div class="table-responsive pagos-scroll">
          <table class="table table-sm table-hover mb-0 tabla-cards" id="table-pagos">
            <thead class="table-primary">
              <tr><th>Id</th><th>Forma</th><th class="text-end">Valor</th><th>Tarjeta</th><th>Voucher</th><th></th></tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer flex-column flex-md-row justify-content-md-between">
        <small class="text-muted d-none d-md-block">Agregue uno o varios medios de pago hasta completar el valor de la factura.</small>
        <button class="btn btn-primary" id="guardar-documento"><i class="fa-solid fa-floppy-disk"></i> Guardar documento</button>
      </div>
    </div>
  </div>
</div>

<!-- Confirmación de guardado -->
<div class="modal fade" id="modalConfirmarGuardar" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-body text-center">
        <i class="fa-solid fa-circle-question fa-3x text-primary mb-3"></i>
        <h5 class="mb-0">¿Está seguro de guardar este documento?</h5>
      </div>
      <div class="modal-footer justify-content-center">
        <button id="btnCancelarConfirm" class="btn btn-outline-secondary">Cancelar</button>
        <button id="btnAceptarConfirm" class="btn btn-primary">Sí, guardar</button>
      </div>
    </div>
  </div>
</div>

<!-- Permisos y configuración del módulo (solo lectura) -->
<div class="modal fade" id="modalPermisos" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa-solid fa-shield-halved text-primary me-1"></i> Permisos y configuración</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <small class="text-muted d-block mb-2">Usuario: <strong id="permisos_usuario"></strong></small>
        <input type="search" class="form-control form-control-sm mb-3" id="permisos_filtro" placeholder="Filtrar en las tres pestañas..." autocomplete="off">
        <ul class="nav nav-tabs flex-nowrap overflow-auto" role="tablist" id="tabsInfoModulo">
          <li class="nav-item">
            <button type="button" class="nav-link active text-nowrap" data-bs-toggle="tab" data-bs-target="#info_tab_permisos" role="tab">
              Permisos <span class="badge rounded-pill bg-label-primary ms-1" id="info_cont_permisos"></span>
            </button>
          </li>
          <li class="nav-item">
            <button type="button" class="nav-link text-nowrap" data-bs-toggle="tab" data-bs-target="#info_tab_modulo" role="tab">
              Config. módulo <span class="badge rounded-pill bg-label-primary ms-1" id="info_cont_modulo"></span>
            </button>
          </li>
          <li class="nav-item">
            <button type="button" class="nav-link text-nowrap" data-bs-toggle="tab" data-bs-target="#info_tab_general" role="tab">
              Config. general <span class="badge rounded-pill bg-label-primary ms-1" id="info_cont_general"></span>
            </button>
          </li>
        </ul>
        <div class="tab-content px-0 pt-3">
          <div class="tab-pane fade show active" id="info_tab_permisos" role="tabpanel"><div id="info_lista_permisos"></div></div>
          <div class="tab-pane fade" id="info_tab_modulo" role="tabpanel"><div id="info_lista_modulo"></div></div>
          <div class="tab-pane fade" id="info_tab_general" role="tabpanel"><div id="info_lista_general"></div></div>
        </div>
      </div>
      <div class="modal-footer justify-content-between">
        <small class="text-muted">Los permisos se administran en roles y los parámetros en la configuración del sistema.</small>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<div id="modal-container"></div>

<?php
$scriptsPagina = [
  // filemtime en vez de rand(): el navegador cachea el JS y solo lo descarga de nuevo cuando cambia
  '../controllers/Pos.js?v=' . (@filemtime(__DIR__ . '/../controllers/Pos.js') ?: date('Ymd'))
];
include '../dependencies/footer.php';
?>