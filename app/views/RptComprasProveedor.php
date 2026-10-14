<?php
session_start();

$titulo = 'Compras por proveedor';
include '../dependencies/header.php';
?>
<div class="card">
  <div class="card-body">

    <!-- Título -->
    <h5 class="m-0 p-0 mb-3">
      <span class="text-muted fw-light">
        Reportes / Reportes de Compras /
      </span>
      Compras por proveedor
    </h5>

    <!-- Filtros -->
    <div class="row g-2 align-items-end">

      <!-- Proveedor -->
      <div class="col-md-4">
        <label class="form-label mb-1" for="proveedor">
          Proveedor
        </label>

        <div class="input-group input-group-sm">
          <span class="input-group-text">
            <i class="bx bx-search"></i>
          </span>

          <input
            type="text"
            class="form-control"
            id="proveedor"
            placeholder="Nit/Nombres" />

          <input
            type="hidden"
            readonly
            id="id_tercero"
            value="0" />
        </div>
      </div>


      <!-- Tipo -->
      <div class="col-md-2">
        <label class="form-label mb-1" for="tipo">
          Tipo
        </label>

        <select
          class="form-select form-select-sm"
          id="tipo">
          <option value="consolidado">Consolidado</option>
          <option value="detallado">Detalle</option>
        </select>
      </div>


      <!-- Fecha inicial -->
      <div class="col-md-2">
        <label
          for="fecha_inicial"
          class="form-label mb-1">
          Fecha inicial
        </label>

        <input
          type="date"
          id="fecha_inicial"
          name="fecha_inicial"
          class="form-control form-control-sm" />
      </div>


      <!-- Fecha final -->
      <div class="col-md-2">
        <label
          for="fecha_final"
          class="form-label mb-1">
          Fecha final
        </label>

        <input
          type="date"
          id="fecha_final"
          name="fecha_final"
          class="form-control form-control-sm" />
      </div>


      <!-- Consultar -->
      <div class="col-md-2">
        <button
          type="button"
          class="btn btn-sm btn-primary w-100"
          id="btn-busqueda">
          <i class="fa fa-search me-1"></i>
          Consultar
        </button>
      </div>

    </div>


    <!-- Resultado -->
    <div class="table-responsive mt-3">
      <div id="result"></div>
    </div>

  </div>
</div>

<?php

$scriptsPagina = [
  '../controllers/RptComprasProveedor.js?' . rand()
];

include '../dependencies/footer.php';

?>