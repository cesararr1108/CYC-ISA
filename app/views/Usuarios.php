<?php
session_start();

$titulo = 'Roles';
include '../dependencies/header.php';
?>

<!-- Modal Scroll long content -->
<div class="col-lg-4 col-md-3">
  <!-- Modal -->
  <div class="modal fade" id="modalInfo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalLongTitle">Información general </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <table class="table table-sm table-striped table-borderleess">
            <tr>
              <th width="30%">Tipos</th>
              <td id="tdTipo"></td>
            </tr>
            <tr>
              <th>Descripcion</th>
              <td id="tdDescripcion"></td>
            </tr>
            <tr>
              <th>Sw</th>
              <td id="tdSw"></td>
            </tr>
            <tr>
              <th>Leyenda 1</th>
              <td id="tdLeyenda1"></td>
            </tr>
            <tr>
              <th>Leyenda 2</th>
              <td id="tdLeyenda2"></td>
            </tr>
            <tr>
              <th>Inactivo</th>
              <td id="tdInactivo"></td>
            </tr>
            <tr>
              <th>Fecha inicio</th>
              <td id="TdFechaInicio"></td>
            </tr>
            <tr>
              <th>Fecha final</th>
              <td id="TdFechaFinal"></td>
            </tr>
            <tr>
              <th>Almacen</th>
              <td id="tdAlmacen"></td>
            </tr>
            <tr>
              <th>Fecha creación</th>
              <td id="TdFechaCreacion"></td>
            </tr>
            <tr>
              <th>Usuario</th>
              <td id="tdUsuario"></td>
            </tr>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="card">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Administración /</span> Usuarios</h5>
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
              <div class="col-12">
                <div class="demo-inline-spacing d-flex justify-content-end">
                  <button class="btn btn-sm btn-light border-1 border border-light" type="button" id="btnNuevo">Nuevo</button>
                  <button class="btn btn-sm btn-primary  border-1 border border-primary" type="submit" id="btnGuardar">Guardar</button>
                </div>
              </div>
            </div>
            <!-- <div class="row">  -->
            <div class="row">
              <div class="col-8">
                <div class="row g-3">

                  <!-- ID -->

                  <input type="text" class="form-control form-control-sm" id="id" name="id" readonly>

                  <!-- Usuario -->
                  <div class="col-md-6">
                    <label for="usuario" class="form-label">Usuario</label>
                    <div class="input-group input-group-sm"> <span class="input-group-text text-primary"> <i class="fa-solid fa-user"></i> </span>
                      <input type="text" class="form-control" id="usuario" name="usuario" maxlength="20" autocomplete="off" required>
                    </div>
                    <small class="text-muted">Máximo 20 caracteres</small>
                  </div>
                  <div class="col-md-6">
                    <label for="estado" class="form-label">Estado</label>
                    <select class="form-select form-select-sm" id="estado" name="estado" required>
                      <option value="A">Activo</option>
                      <option value="B">Bloqueado</option>
                      <option value="I">Inactivo</option>
                    </select>
                  </div>

                  <!-- Contraseña -->
                  <div class="col-md-6">
                    <label class="form-label" for="clave">Contraseña</label>
                    <div class="input-group input-group-sm">
                      <input type="password" class="form-control" id="clave" name="clave" autocomplete="off" required>
                      <span id="bloqueo-clave" class="input-group-text cursor-pointer d-none"> <i class="fa-solid fa-lock text-primary"></i> </span>
                    </div>
                  </div>

                  <!-- Rol -->
                  <div class="col-md-6">
                    <label for="roles_id" class="form-label">Rol</label>
                    <select class="form-select form-select-sm" name="roles_id" id="roles_id" required>
                    </select>
                  </div>

                  <!-- Nombres -->
                  <div class="col-md-6">
                    <label for="nombres" class="form-label">Nombres</label>
                    <input type="text" class="form-control form-control-sm" name="nombres" id="nombres" maxlength="15" required>
                  </div>

                  <!-- Apellidos -->
                  <div class="col-md-6">
                    <label for="apellidos" class="form-label">Apellidos</label>
                    <input type="text" class="form-control form-control-sm" name="apellidos" id="apellidos" maxlength="15" required>
                  </div>

                  <!-- Teléfono -->
                  <div class="col-md-6">
                    <label for="telefono" class="form-label">Teléfono</label>
                    <div class="input-group input-group-sm"> <span class="input-group-text text-primary"> <i class="fa-solid fa-mobile-screen-button"></i> </span>
                      <input type="text" class="form-control" name="telefono" id="telefono" maxlength="15" required>
                    </div>
                  </div>

                  <!-- Email -->
                  <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <div class="input-group input-group-sm"> <span class="input-group-text text-primary"> <i class="fa-solid fa-at"></i> </span>
                      <input type="email" class="form-control" name="email" id="email" maxlength="120" required>
                    </div>
                  </div>

                  <!-- Nit -->
                  <div class="col-md-4">
                    <label for="nit" class="form-label">Nit</label>
                    <input type="number" class="form-control form-control-sm" name="nit" id="nit">
                    <small class="text-muted">Nit del tercero</small>
                  </div>

                  <!-- Tercero -->
                  <div class="col-md-8">
                    <label for="tercero" class="form-label">Tercero</label>
                    <input type="text" class="form-control form-control-sm" id="tercero" readonly>
                  </div>
                </div>
              </div>
              <div class="col-4">
                <div class=" d-flex align-items-center flex-column mt-3">
                  <h5 class="">Foto de perfil</h5>
                  <img
                    id="preview"
                    name="imagen"
                    src="#"
                    class="border text-primary border-1 rounded-circle"
                    style="width: 150px; height: 150px; object-fit: cover;" />

                  <div class="card-body demo-vertical-spacing demo-only-element">
                    <div class="input-group">
                      <label class="input-group-text" for="imagen"><i class="fa-solid fa-cloud-arrow-up"></i></label>
                      <input type="file" class="form-control" id="imagen" name="imagen" accept="image/*" />
                    </div>
                  </div>
                </div>
                <div class="card mt-1">
                  <div class="card-body">
                    <label>Bodegas</label>
                    <select class="form-select form-select-sm mt-2" name="bodegas" id="bodegas" multiple style="height:150px" required>
                    </select>
                    <small class="text-muted">Seleccione una o varias bodegas</small>
                  </div>
                </div>
              </div>
              <!-- <div class="col 6">  -->
            </div>
            <!-- <div class="row">  -->
          </form>
        </div>
        <div class="tab-pane fade " id="navs-listado" role="tabpanel">
          <div class="row">
            <div class="col-12">
              <div class="row mb-3">
                <div class="input-group input-group-sm">
                  <input id="filtro" type="text" class="form-control form-control-sm" placeholder="Usuario/Nombre" aria-label="Recipient's username" aria-describedby="button-addon2" />
                  <button class="btn btn-outline-primary" type="button" id="busqueda-usuarios"><i class="fa-solid fa-magnifying-glass"></i></button>
                </div>
              </div>
              <div class="row mt-3">
                <div class="col">
                  <label>Roles</label>
                  <select class="form-control form-control-sm mt-1" id="rol_filter">
                  </select>
                </div>
                <div class="col">
                  <label>Estado</label>
                  <select class="form-control form-control-sm mt-1" id="estado_filter">
                    <option value="">Todos</option>
                    <option value="A">activos</option>
                    <option value="I">Inactivas</option>
                  </select>
                </div>
              </div>
              <div class="row mt-3">
                <div class="col-12">
                  <div class="table-responsive text-nowrap">
                    <div id="result"></div>
                  </div>
                </div>
              </div>
            </div>
            <!--  <div class="col-12"> -->
          </div>
          <!-- row -->
        </div>
      </div>
      <!--  <div class="tab-content">  -->
    </div>
  </div>
</div>


<?php

$scriptsPagina = [
  '../controllers/Usuarios.js?' . rand()
];

include '../dependencies/footer.php';

?>