<?php
session_start();

$titulo = 'Cuentas por cobrar';
include '../dependencies/header.php';
?>
<div class="card">
  <div class="card-body">
    <h5 class="m-0 p-0 mb-3"><span class="text-muted fw-light">Reportes / Reportes de Cartera / </span>Cuentas por cobrar</h5>


    <div class="row g-2">
      <!-- Descripción -->
      <div class="col-md-4">
        <label>Tercero</label>
        <div class="input-group "> <span class="input-group-text"><i class="bx bx-search"></i></span>
          <input type="text" class="form-control form-control-sm" id="proveedor" placeholder="Nit/Nombres" />
          <input type="hidden" readonly id="id_tercero" value="0" />
        </div>
      </div>

      <!-- Marca -->
      <div class="col-md-4">
        <label for="fecha_inicial">Fecha inicial</label>
        <input type="date" id="fecha_inicial" name="fecha_inicial" class="form-control form-control-sm">
      </div>
      <div class="col-md-4">
        <label for="fecha_final">Fecha inicial</label>
        <input type="date" id="fecha_final" name="fecha_final" class="form-control form-control-sm">
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
  '../controllers/RptCxC.js?' . rand()
];

include '../dependencies/footer.php';

?>