<?php
session_start();

$titulo = 'Condiciones de pago';
include '../dependencies/header.php';
?>
<div class="card">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Maestra /</span> Condiciones de pago</h5>
    <div class="nav-align-top ">
      <ul class="nav nav-tabs" role="tablist" id="tabs">
        <li class="nav-item">
          <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-creacion" aria-controls="navs-creacion" aria-selected="true"> <i class="fa-solid fa-file-circle-plus"></i> &nbsp;Creación </button>
        </li>
        <li class="nav-item">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-listado" aria-controls="navs-listado" aria-selected="false"> <i class="fa-solid fa-list-ol"></i> &nbsp;Listado </button>
        </li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane fade show active" id="navs-creacion" role="tabpanel">
          <form id="FormCrear" class="needs-validation  form-basico" novalidate>
            <div class="row">
              <div class="col-md-12">
                <div class="mb-1">
                  <div class="row">
                    <div class="col-12">
                      <div class="demo-inline-spacing d-flex justify-content-end">
                        <button class="btn btn-outline-secondary btn-sm" id="btnNuevo"><i class="fa-regular fa-file"></i>&nbsp;Nuevo</button>
                        <button class="btn btn-outline-primary btn-sm" id="btnGuardar"><i class="fa-regular fa-floppy-disk"></i>&nbsp;Guardar</button>
                      </div>
                    </div>
                  </div>
                  <div class="row">
                    <!-- Campo id -->
                    <input type="hidden" class="form-control form-control-sm" id="id" name="id">
                    <!-- Campo tipo_condicion -->
                    <div class="mt-2">
                      <label for="tipo_condicion" class="form-label">Tipo condición</label>
                      <select class="form-select form-select-sm" id="tipo_condicion" name="tipo_condicion" required>
                        <option value="C">Cliente</option>
                        <option value="P">Proveedor</option>
                      </select>
                    </div>
                    <!-- Campo descripcion -->
                    <div class="mt-2">
                      <label for="descripcion" class="form-label">Descripción</label>
                      <input type="text" class="form-control form-control-sm" id="descripcion" name="descripcion" autocomplete="off" maxlength="50" required>
                    </div>
                    <div class="mt-2">
                      <!-- Campo dias1 -->
                      <label for="dias" class="form-label">Días 1</label>
                      <input type="number" class="form-control form-control-sm" id="dias" name="dias" autocomplete="off" required>
                    </div>
                    <!-- Campo dto1 -->
                    <div class="mt-2">
                      <label for="dto" class="form-label">Descuento (%)</label>
                      <input type="number" step="0.01" class="form-control form-control-sm" id="dto" autocomplete="off" name="dto" required>
                    </div>
                    <!-- Campo dias_gracia -->
                    <div class="mt-2">
                      <label for="dias_gracia" class="form-label">Días de Gracia</label>
                      <input type="number" class="form-control form-control-sm" id="dias_gracia" autocomplete="off" name="dias_gracia">
                    </div>
                    <div class="mt-2">
                      <label for="dias_gracia" class="form-label">Editable</label>
                      <input type="checkbox" class="checkbox-inline" id="editable" name="editable" checked disabled>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>
        <div class="tab-pane fade " id="navs-listado" role="tabpanel">
          <div class="row">
            <div class="col-12">
              <div class="row mb-3">
                <div class="col-12">
                  <div class="input-group input-group-merge"> <span class="input-group-text"><i class="bx bx-search"></i></span>
                    <input name="" id="filtro" type="text" class="form-control form-control-sm" placeholder="Descripcion..." aria-label="Search..." />
                    <button class="btn btn-primary btn-sm" id="buscar">Buscar </button>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-12">
                  <div class="table-responsive text-nowrap">
                    <div id="result"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>


<?php

$scriptsPagina = [
  '../controllers/condicionesPago.js?' . rand()
];

include '../dependencies/footer.php';

?>