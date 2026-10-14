<?php
session_start();

$titulo = 'Ventas por usuario';
include '../dependencies/header.php';
?>
<div class="card">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Reportes / Reportes de ventas / </span> Ventas por usuario</h5>
    <div class="row  g-2 mt-3">
      <div class="col-md-4">
        <div class="mb-3">
          <label class="form-label" for="fecha_inicial">Fecha inicial</label>
          <div class="input-group input-group-merge"> <span class="input-group-text"><i class="fa-regular fa-calendar-check"></i></span>
            <input type="date" class="form-control form-control-sm" id="fecha_inicial" aria-describedby="fecha_inicial" name="fecha_inicial">
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="mb-3">
          <label class="form-label" for="fecha_final">Fecha final</label>
          <div class="input-group input-group-merge"> <span class="input-group-text"><i class="fa-regular fa-calendar-check"></i></span>
            <input type="date" class="form-control form-control-sm" id="fecha_final" aria-describedby="fecha_final" name="fecha_final">
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="slcUsuarios">Usuario</label>
        <select class="form-select form-select-sm" id="slcUsuarios"></select>
      </div>
    </div>
    <!-- Botones -->
    <div class="row mt-3">
      <div class="col-md-12 d-flex justify-content-between align-items-center gap-2">
        <div class="btn-group" role="group">
          <input type="radio" class="btn-check" name="tipo_vista" id="vista_consolidada" value="consolidada" checked>
          <label class="btn btn-sm btn-outline-primary" for="vista_consolidada">
            <i class="fa fa-chart-bar"></i> Vista Consolidada
          </label>
          <input type="radio" class="btn-check" name="tipo_vista" id="vista_detallada" value="detallada">
          <label class="btn btn-sm btn-outline-primary" for="vista_detallada">
            <i class="fa fa-list"></i> Vista Detallada
          </label>
        </div>
        <button type="button" class="btn btn-sm btn-primary" id="btn-busqueda"> <i class="fa fa-search"></i> Consultar </button>
      </div>
    </div>
    <!-- Grafico -->
    <div class="row">
      <div class="col-12">
        <div id="grafica-ventas"></div>
      </div>
    </div>

    <div id="result"></div>
    <!-- Resultado -->
    <div id="result" class="table-responsive mt-3"></div>
  </div>
</div>

<?php

$scriptsPagina = [
  '../controllers/RptVentasUsuario.js?' . rand()
];

include '../dependencies/footer.php';

?>