<?php
session_start();

$titulo = 'Inventario de referencias';
include '../dependencies/header.php';
?>

<style>
  .inv-kpi { border: 1px solid rgba(67, 89, 113, .1); border-radius: .5rem; }
  .inv-kpi .avatar-initial { width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: .375rem; font-size: 1.1rem; flex-shrink: 0; }
  .inv-kpi .kpi-label { font-size: .7rem; color: #8592a3; text-transform: uppercase; letter-spacing: .3px; }
  .inv-kpi .kpi-value { font-size: 1.05rem; font-weight: 600; margin: 0; }
  .inv-filtros .form-label { font-size: .7rem; text-transform: uppercase; letter-spacing: .3px; color: #8592a3; margin-bottom: .25rem; }
  .inv-switches .form-check { margin-bottom: 0; }
  .inv-seccion { font-size: .8rem; font-weight: 600; color: #566a7f; }
  #tb_referencias td { white-space: nowrap; vertical-align: middle; }
</style>

<div class="card">
  <div class="card-body">

    <!-- Título -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
      <h5 class="fw-bold mb-0">
        <span class="text-muted fw-light">Reportes / Reportes de Inventarios /</span> Inventario de referencias
      </h5>
      <small class="text-muted d-none d-md-inline">Busca por descripción, código, CUM, INVIMA o EAN</small>
    </div>

    <!-- ===================== FILTROS ===================== -->
    <div class="inv-filtros border rounded p-3 mb-3">
      <div class="inv-seccion mb-2"><i class="bx bx-filter-alt text-primary me-1"></i>Filtros de búsqueda</div>

      <div class="row g-2">
        <div class="col-12 col-lg-5">
          <label class="form-label" for="busqueda-descripcion">Producto</label>
          <div class="input-group input-group-sm input-group-merge">
            <span class="input-group-text"><i class="bx bx-search"></i></span>
            <input type="text" class="form-control" id="busqueda-descripcion" autocomplete="off"
              placeholder="Ej: acetaminofen 500, código, CUM o EAN" />
          </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
          <label class="form-label" for="bodegas">Bodega</label>
          <select id="bodegas" class="form-select form-select-sm">
            <option value="">Cargando...</option>
          </select>
        </div>

        <div class="col-12 col-md-6 col-lg-2">
          <label class="form-label" for="marcas">Marca</label>
          <select id="marcas" class="form-select form-select-sm">
            <option value="0">Todas</option>
          </select>
        </div>

        <div class="col-12 col-lg-2">
          <label class="form-label">Visualización</label>
          <div class="btn-group btn-group-sm w-100" role="group" aria-label="Visualización de lotes">
            <input type="radio" class="btn-check" name="busqueda-lote" id="vis-detalle" value="detalle" checked>
            <label class="btn btn-outline-primary" for="vis-detalle" title="Ver por lote y vencimiento">Lotes</label>
            <input type="radio" class="btn-check" name="busqueda-lote" id="vis-resumen" value="resumen">
            <label class="btn btn-outline-primary" for="vis-resumen" title="Ver stock total">Total</label>
          </div>
        </div>

        <div class="col-12">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-2">
            <div class="d-flex flex-wrap gap-3 inv-switches small">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="con_stock" checked />
                <label class="form-check-label" for="con_stock">Con stock</label>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="busqueda-institucional" />
                <label class="form-check-label" for="busqueda-institucional">Institucional</label>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="busqueda-regulado" />
                <label class="form-check-label" for="busqueda-regulado">Regulado</label>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="busqueda-controlado" />
                <label class="form-check-label" for="busqueda-controlado">Controlado</label>
              </div>
            </div>

            <div class="d-flex gap-2">
              <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-limpiar">
                <i class="bx bx-eraser me-1"></i>Limpiar
              </button>
              <button type="button" class="btn btn-sm btn-outline-primary" id="btn-generar-tirilla">
                <i class="bx bx-receipt me-1"></i>Tirilla
              </button>
              <button type="button" class="btn btn-sm btn-primary" id="btn-busqueda">
                <i class="bx bx-search me-1"></i>Consultar
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ===================== RESUMEN ===================== -->
    <div id="resumen-inventario" class="row g-2 mb-3 d-none"></div>

    <!-- ===================== RESULTADOS ===================== -->
    <div class="d-flex justify-content-between align-items-center mb-2">
      <div class="inv-seccion"><i class="bx bx-package text-primary me-1"></i>Resultados</div>
      <small class="text-muted" id="info-consulta"></small>
    </div>
    <div id="resultado-inventario" class="table-responsive">
      <div class="text-center text-muted py-5">
        <i class="bx bx-search-alt bx-lg d-block mb-2 text-primary"></i>
        Defina los filtros y presione <strong>Consultar</strong>
      </div>
    </div>

  </div>
</div>

<?php
$scriptsPagina = [
  '../controllers/rptInventarioLotes.js?' . rand()
];

include '../dependencies/footer.php';
?>