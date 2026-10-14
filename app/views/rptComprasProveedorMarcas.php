<?php
session_start();

$titulo = 'Compras por proveedor y marcas';
include '../dependencies/header.php';
?>
<div class="card">
  <div class="card-body">

    <!-- Título -->
    <h5 class="m-0 p-0 mb-3">
      <span class="text-muted fw-light">
        Reportes / Reportes de Compras /
      </span>
      Compras por proveedor y marcas
    </h5>

    <!-- Filtros -->
    <div class="row g-2 align-items-end">

      <!-- Proveedor -->
      <div class="col-md-3">
        <input
          type="hidden"
          readonly
          id="id_tercero"
          value="0" />

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
        </div>
      </div>


      <!-- Marca -->
      <div class="col-md-3">

        <label class="form-label mb-1" for="marcas">
          Marca
        </label>

        <select
          id="marcas"
          class="form-select form-select-sm"
          data-placeholder="Todas las marcas">
          <option value="">
            Todas las marcas
          </option>
        </select>

      </div>


      <!-- Fecha inicial -->
      <div class="col-md-2">

        <label
          class="form-label mb-1"
          for="fecha_inicial">
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
          class="form-label mb-1"
          for="fecha_final">
          Fecha final
        </label>

        <input
          type="date"
          id="fecha_final"
          name="fecha_final"
          class="form-control form-control-sm" />

      </div>


      <!-- Botón consultar -->
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
    <div
      id="result"
      class="table-responsive mt-3"></div>

  </div>
</div>

<?php

$scriptsPagina = [
  '../controllers/rptComprasProveedorMarcas.js?' . rand()
];

include '../dependencies/footer.php';

?>