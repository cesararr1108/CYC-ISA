<?php
session_start();

$titulo = 'Módulos permisos';
include '../dependencies/header.php';
?>
<div class="card" style="height: auto;">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Administración /</span> Módulos permisos</h5>
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

                  <div class="row">
                    <div class="col-4">
                      <label for="txtModulo" class="form-label w-100">Modulo
                        <div class="input-group">
                          <span class="input-group-text" id="basic-addon11">
                            <i class="fa-solid fa-magnifying-glass"></i>
                          </span>
                          <input
                            id="txtModulo"
                            type="text"
                            class="form-control"
                            aria-describedby="basic-addon11" />
                        </div>
                      </label>
                      <select
                        multiple
                        class="form-select"
                        id="slcModulos"
                        name="id_modulo"
                        required
                        style="height: 40vh;"></select>
                    </div>
                    <div class="col-4">
                      <label for="txtPermisos" class="form-label w-100">Permisos
                        <div class="input-group">
                          <span class="input-group-text" id="basic-addon11">
                            <i class="fa-solid fa-magnifying-glass"></i>
                          </span>
                          <input
                            id="txtPermiso"
                            type="text"
                            class="form-control"
                            aria-describedby="basic-addon11" />
                        </div>
                      </label>
                      <select
                        required
                        multiple
                        class="form-select "
                        id="slcPermisos"
                        name="id_permiso"
                        style="height: 40vh;"></select>
                    </div>
                    <div class="col-4">
                      <label>Asignados</label>
                      <select

                        multiple
                        class="form-select "
                        id="slcAsignados"
                        name="asignados"
                        style="height: 40vh;"
                        disabled></select>
                    </div>

                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>
        <div class="tab-pane fade" id="navs-listado" role="tabpanel">
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
                      placeholder="permiso,modulo..."
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
  '../controllers/modulosPermisos.js?' . rand()
];

include '../dependencies/footer.php';

?>