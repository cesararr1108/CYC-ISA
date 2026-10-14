<?php
session_start();

$titulo = 'Listado de terceros';
include '../dependencies/header.php';
?>
<div class="card">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Reportes / Reportes de Varios / </span> Listado de terceros</h5>
    <div class="nav-align-top ">
      <ul class="nav nav-tabs" role="tablist" id="tabs">
        <li class="nav-item">
          <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-busqueda" aria-controls="navs-busqueda" aria-selected="true"> <i class="fa-solid fa-file-circle-plus"></i> &nbsp;Filtros de búsqueda </button>
        </li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane fade show active" id="navs-busqueda" role="tabpanel">
          <div class="row g-2">
            <!-- Tipo de tercero -->
            <div class="col-md-6">
              <label for="txtBusquedaTercero" class="form-label">Tipo de tercero</label>
              <div class="input-group input-group-sm"> <span class="input-group-text"><i class="fa-solid fa-filter"></i></span>
                <select id="id_tipo_tercero" name="id_tipo_tercero" class="form-select form-select-sm">
                </select>
              </div>
            </div>
            <!-- Nombres o razon social -->
            <div class="col-md-6">
              <label for="txtBusquedaTercero" class="form-label">Razón social / Nombres</label>
              <div class="input-group input-group-sm"> <span class="input-group-text"><i class="fa-regular fa-address-card"></i></span>
                <input type="text" class="form-control" placeholder="Nombre / Razón" id="busqueda-tercero" />
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
          <div id="resultado-terceros" class="table-responsive mt-3"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php

$scriptsPagina = [
  '../controllers/rptListadoTerceros.js?' . rand()
];

include '../dependencies/footer.php';

?>