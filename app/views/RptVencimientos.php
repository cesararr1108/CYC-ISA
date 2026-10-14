<?php
session_start();

$titulo = 'Vencimientos de productos';
include '../dependencies/header.php';
?>

<div class="card">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Reportes / Reportes de Inventarios / </span> Vencimientos de productos</h5>
    <div class="nav-align-top ">
      <ul class="nav nav-tabs" role="tablist" id="tabs">
        <li class="nav-item">
          <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-busqueda" aria-controls="navs-busqueda" aria-selected="true"> <i class="fa-solid fa-file-circle-plus"></i> &nbsp;Filtros de búsqueda </button>
        </li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane fade show active" id="navs-busqueda" role="tabpanel">
          <div class="row g-2">
            <!-- Descripción -->
            <div class="col-md-6">
              <div class="input-group input-group-sm"> <span class="input-group-text"><i class="bx bx-search"></i></span>
                <input type="text" class="form-control busqueda-referencias"
                  placeholder="Descripción del producto"
                  id="busqueda-descripcion" />
              </div>
            </div>

            <!-- Marca -->
            <div class="col-md-6">
              <div class="input-group input-group-sm"> <span class="input-group-text"><i class="fa-solid fa-flask"></i></span>
                <select class="form-control" id="marcas"></select>
              </div>
            </div>
            <!-- Lotes -->

          </div>

          <!-- Filtros -->
          <div class="row mt-3">
            <div class="col-12">
              <div class="form-check form-check-inline">
                <input class="form-check-input busqueda-referencias" type="checkbox" id="busqueda-institucional" name="institucional" />
                <label class="form-check-label" for="busqueda-institucional">Institucional</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input busqueda-referencias" type="checkbox" id="busqueda-regulado" name="regulado" />
                <label class="form-check-label" for="busqueda-regulado">Regulado</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input busqueda-referencias" type="checkbox" id="busqueda-controlado" name="controlado" />
                <label class="form-check-label" for="busqueda-controlado">Controlado</label>
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
          <div id="result" class="table-responsive mt-3"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php

$scriptsPagina = [
  '../controllers/RptVencimientos.js?' . rand()
];

include '../dependencies/footer.php';

?>