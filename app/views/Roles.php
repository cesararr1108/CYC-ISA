<?php
session_start();

$titulo = 'Roles';
include '../dependencies/header.php';
?>
<div class="card" style="height: auto;">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Administración /</span> Roles</h5>
    <div class="nav-align-top ">
      <ul class="nav nav-tabs" role="tablist" id="tabs">
        <li class="nav-item">
          <button
            type="button"
            class="nav-link active"
            role="tab"
            data-bs-toggle="tab"
            data-bs-target="#navs-creacion"
            aria-controls="navs-creacion"
            aria-selected="true">
            <i class="fa-solid fa-file-circle-plus"></i> &nbsp;Creación
          </button>
        </li>
        <li class="nav-item">
          <button
            type="button"
            class="nav-link"
            role="tab"
            data-bs-toggle="tab"
            data-bs-target="#navs-listado"
            aria-controls="navs-listado"
            aria-selected="false">
            <i class="fa-solid fa-list-ol"></i> &nbsp;Listado
          </button>
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
                        <button class="btn btn-sm btn-light border-1 border border-light" type="button" id="btnNuevo">Nuevo</button>
                        <button class="btn btn-sm btn-primary  border-1 border border-primary" type="submit" id="btnGuardar">Guardar</button>
                      </div>
                    </div>
                  </div>
                  <div class="d-none">
                    <label for="txtId" class="form-label">Id</label>
                    <input type="text" class="form-control form-control-sm" value="" id="txtId" name="id" autocomplete="off" readonly>
                  </div>
                  <div>
                    <label for="txtRol" class="form-label">Rol</label>
                    <input type="text" class="form-control form-control-sm" id="txtRol" name="descripcion" maxlength="50" autocomplete="off" required>
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
                <div class="col-9">
                  <div class="input-group input-group-merge">
                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                    <input
                      name=""
                      id="filtro"
                      type="text"
                      class="form-control form-control-sm"
                      placeholder="Titulo,descripcion..."
                      aria-label="Search..." />
                  </div>

                </div>
                <div class="col-3">
                  <button class="btn btn-primary btn-sm w-100" id="buscar">Buscar </button>
                </div>
              </div>
              <div class="row">
                <div class="col-12">
                  <div id="result"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div><!--    <div class="tab-content">  -->
    </div>
  </div>
</div>
<?php

$scriptsPagina = [
  '../controllers/Roles.js?' . rand()
];

include '../dependencies/footer.php';

?>