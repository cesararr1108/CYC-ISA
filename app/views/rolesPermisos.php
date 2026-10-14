<?php
session_start();

$titulo = 'Roles permisos';
include '../dependencies/header.php';
?>
<style>
  .select-form-create {
    height: 55vh;
  }

  .text-opacity-50 {
    opacity: 0.5;
  }
</style>
<div class="card " style="height: 95vh;">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Administración /</span> Roles permisos</h5>
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
                      <label for="txtRol" class="form-label w-100">Rol
                        <div class="input-group">
                          <span class="input-group-text" id="basic-addon11">
                            <i class="fa-solid fa-magnifying-glass"></i>
                          </span>
                          <input
                            id="txtRol"
                            type="text"
                            class="form-control"
                            aria-describedby="basic-addon11" />
                        </div>
                      </label>
                      <select
                        multiple
                        class="form-select  select-form-create"
                        id="slcRoles"
                        name="id_rol"
                        required></select>
                    </div>
                    <div class="col-4">
                      <label for="txtModulos" class="form-label w-100">Modulos
                        <div class="input-group">
                          <span class="input-group-text" id="basic-addon11">
                            <i class="fa-solid fa-magnifying-glass"></i>
                          </span>
                          <input
                            id="txtModulos"
                            type="text"
                            class="form-control"
                            aria-describedby="basic-addon11" />
                        </div>
                      </label>
                      <select
                        required
                        multiple
                        class="form-select select-form-create"
                        id="slcModulos"></select>
                    </div>
                    <div class="col-4">
                      <label for="txtPermisos" class="form-label w-100">Permisos
                        <div class="input-group">
                          <span class="input-group-text" id="basic-addon11">
                            <i class="fa-solid fa-magnifying-glass"></i>
                          </span>
                          <input
                            id="txtPermisos"
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
                        name="id_modulo_permiso"
                        style="height: auto;"></select>
                      <h5 class="mt-2">Seleccionado <small class="text-opacity-50">(Clic para eliminar)</small></h5>
                      <select

                        multiple
                        class="form-select  select-form-create"
                        id="slcPermisosSelected"
                        name=""
                        style="height: 35vh"></select>
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
                  <button class="btn btn-primary btn-sm" id="buscar">Buscar </button>
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
  '../controllers/rolesPermisos.js?' . rand()
];

include '../dependencies/footer.php';

?>