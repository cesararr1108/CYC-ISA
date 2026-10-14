<?php
session_start();

$titulo = 'Estado de resultados';
include '../dependencies/header.php';
?>
<style>
  /* Opcional: compactar tablas del informe */
  .estados-table th,
  .estados-table td {
    padding: 0.25rem 0.5rem !important;
    font-size: 0.85rem;
  }

  .summary-card {
    border-radius: 8px;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
  }

  .small-muted {
    font-size: .85rem;
    color: #6c757d;
  }

  /* Asignar anchos fijos a todas las tablas de estado de resultados */
  .estados-table th.col-cuenta,
  .estados-table td.col-cuenta {
    width: 110px;
    white-space: nowrap;
  }

  .estados-table th.col-desc,
  .estados-table td.col-desc {
    width: 300px;
    /* puedes ajustar */
    max-width: 300px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .estados-table th.col-debito,
  .estados-table td.col-debito,
  .estados-table th.col-credito,
  .estados-table td.col-credito,
  .estados-table th.col-resultado,
  .estados-table td.col-resultado {
    width: 120px;
    white-space: nowrap;
  }
</style>
<div class="card">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Reportes / Reportes de Contables / </span> Estado de resultados</h5>
    <div class="nav-align-top ">
      <ul class="nav nav-tabs" role="tablist" id="tabs">
        <li class="nav-item">
          <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-busqueda" aria-controls="navs-busqueda" aria-selected="true"> <i class="fa-solid fa-file-circle-plus"></i> &nbsp;Filtros de búsqueda </button>
        </li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane fade show active" id="navs-busqueda" role="tabpanel">
          <div class="row">
            <div class="col-6">
              <div class="mb-3">
                <label class="form-label" for="basic-icon-default-fullname">Fecha inicial</label>
                <div class="input-group input-group-merge"> <span class="input-group-text"><i class="fa-regular fa-calendar-check"></i></span>
                  <input type="date" class="form-control form-control-sm" id="fe_fecha_inicial" aria-describedby="fe_fecha_inicial">
                </div>
              </div>
            </div>
            <div class="col-6">
              <div class="mb-3">
                <label class="form-label" for="basic-icon-default-fullname">Fecha final</label>
                <div class="input-group input-group-merge"> <span class="input-group-text"><i class="fa-regular fa-calendar-check"></i></span>
                  <input type="date" class="form-control form-control-sm" id="fe_fecha_final" aria-describedby="fe_fecha_final">
                </div>
              </div>
            </div>
          </div>
          <!-- Botones -->
          <div class="row mt-3">
            <div class="col-md-12 d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-limpiar"> <i class="fa-solid fa-eraser"></i> Limpiar </button>
              <button type="button" class="btn btn-sm btn-primary" id="btn-busqueda"> <i class="fa fa-search"></i> Consultar </button>
            </div>
          </div>
          <!-- Resultado -->
          <div id="reporte-estado-resultados" class="table-responsive mt-3"></div>
        </div>
      </div>
    </div>
  </div>
</div>


<?php

$scriptsPagina = [
  '../controllers/rptEstadoResultados.js?' . rand()
];

include '../dependencies/footer.php';

?>