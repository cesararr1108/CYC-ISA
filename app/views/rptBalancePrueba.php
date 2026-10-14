<?php
session_start();

$titulo = 'Balance de prueba';
include '../dependencies/header.php';
?>
<style>
  .estados-table th.col-cuenta,
  .estados-table td.col-cuenta {
    width: 120px;
    white-space: nowrap;
  }

  .estados-table th.col-desc,
  .estados-table td.col-desc {
    width: 280px;
    max-width: 280px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .estados-table th.col-saldo,
  .estados-table td.col-saldo,
  .estados-table th.col-debito,
  .estados-table td.col-debito,
  .estados-table th.col-credito,
  .estados-table td.col-credito {
    width: 140px;
    white-space: nowrap;
  }
</style>
<div class="card">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Reportes / Reportes de Contables / </span> Balance de prueba</h5>
    <div class="nav-align-top ">
      <ul class="nav nav-tabs" role="tablist" id="tabs">
        <li class="nav-item">
          <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-busqueda" aria-controls="navs-busqueda" aria-selected="true"> <i class="fa-solid fa-file-circle-plus"></i> &nbsp;Filtros de búsqueda </button>
        </li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane fade show active" id="navs-busqueda" role="tabpanel">
          <div class="row">
            <div class="col-3">
              <div class="mb-3">
                <div class="form-group">
                  <label for="fecha" class="form-label">mes y año inicial</label>
                  <input type="month" id="fecha_inicial" name="fecha_inicial" class="form-control form-control-sm">
                </div>
              </div>
            </div>
            <div class="col-3">
              <div class="mb-3">
                <div class="form-group">
                  <label for="fecha" class="form-label">mes y año final</label>
                  <input type="month" id="fecha_final" name="fecha_final" class="form-control form-control-sm">
                </div>
              </div>
            </div>
            <div class="col-3">
              <label for="cu_inicial" class="form-label">Cuenta inicial</label>
              <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cu_inicial" name="cu_inicial">
              </select>
            </div>
            <div class="col-3">
              <label for="cu_final" class="form-label">Cuenta final</label>
              <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cu_final" name="cu_final">
              </select>
            </div>
          </div>
          <div class="row mt-3">
            <div class="col-12">
              <label for="ver_terceros" class="form-label">terceros</label>
              <input type="checkbox" id="ver_terceros" class="form-check-input" name="ver_terceros">
              <!-- <label for="ver_niveles" class="form-label">niveles</label>
                <input type="checkbox" id="ver_niveles" class="form-check-input" name="ver_niveles">-->
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
          <div id="contenedor-balance" class="table-responsive text-nowrap mt-3"></div>
        </div>
      </div>
    </div>
  </div>
</div>


<?php

$scriptsPagina = [
  '../controllers/rptBalancePrueba.js?' . rand()
];

include '../dependencies/footer.php';

?>