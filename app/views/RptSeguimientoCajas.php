<?php
session_start();

$titulo = 'Seguimiento de cajas';
include '../dependencies/header.php';
?>
  <div class="card">
    <div class="card-body">
      <h5 class="m-0 p-0 mb-3"><span class="text-muted fw-light">Reportes / Reportes de Varios / </span> Seguimiento de cajas</h5>


      <div class="row g-2">
        <!-- Descripción -->
        <div class="col-md-4">
          <label>Usuarios</label>
          <div class="input-group "> <span class="input-group-text"><i class="bx bx-search"></i></span>
            <select class="form-control" id="usuarios"></select>
          </div>
        </div>

        <!-- Marca -->
        <div class="col-md-4">
          <label for="fecha_inicial">Fecha inicial</label>
          <input type="datetime-local" id="fecha_inicial" name="fecha_inicial" class="form-control">
        </div>
        <div class="col-md-4">
          <label for="fecha_final">Fecha inicial</label>
          <input type="datetime-local" id="fecha_final" name="fecha_final" class="form-control">
        </div>
        <!-- Lotes -->

      </div>

      <!-- Botones -->
      <div class="row mt-3">
        <div class="col-md-12 d-flex justify-content-end gap-2">
          <button type="button" class="btn btn-sm btn-primary" id="btn-busqueda"> <i class="fa fa-search"></i> Consultar </button>
        </div>
      </div>

      <!-- Resultado -->
      <div id="result" class="table-responsive mt-3"></div>
    </div>
  </div>

<?php

$scriptsPagina = [
  '../controllers/RptSeguimientoCajas.js?' . rand()
];

include '../dependencies/footer.php';

?>