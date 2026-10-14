<?php
session_start();

$titulo = 'Balance general';
include '../dependencies/header.php';
?>
<div class="card">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Reportes / Reportes de Contables / </span> Balance general</h5>
    <div class="nav-align-top ">
      <ul class="nav nav-tabs" role="tablist" id="tabs">
        <li class="nav-item">
          <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-busqueda" aria-controls="navs-busqueda" aria-selected="true"> <i class="fa-solid fa-file-circle-plus"></i> &nbsp;Filtros de búsqueda </button>
        </li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane fade show active" id="navs-busqueda" role="tabpanel">
          <div class="row g-2">
            <div class="col-md-2">
              <div class="mb-3">
                <label class="form-label" for="fecha_inicial">Mes y año inicial</label>
                <input type="month" id="fecha_inicial" name="fecha_inicial" class="form-control form-control-sm">
              </div>
            </div>
            <div class="col-md-2">
              <div class="mb-3">
                <label class="form-label" for="fecha_final">Mes y año final</label>
                <input type="month" id="fecha_final" name="fecha_final" class="form-control form-control-sm">
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label" for="cu_inicial">Cuenta inicial</label>
                <select class="form-select form-select-sm" id="cu_inicial" name="cu_inicial"></select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label" for="cu_final">Cuenta final</label>
                <select class="form-select form-select-sm" id="cu_final" name="cu_final"></select>
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
          <div id="contenedor-balance" class="table-responsive mt-3"></div>
        </div>
      </div>
    </div>
  </div>
</div>


<?php

$scriptsPagina = [
  '../controllers/rptBalanceGeneral.js?' . rand()
];

include '../dependencies/footer.php';

?>