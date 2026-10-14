    <?php
    session_start();

    $titulo = 'Cuentas contables';
    include '../dependencies/header.php';
    ?>
    <div class="card" style="height: auto;">
      <div class="card-body">
        <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Financiero /</span> Cuentas contables</h5>
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
              <form id="" class="needs-validation  form-basico" novalidate>
                <!-- Clase de cuenta -->
                <div class="row mb-3">
                  <label for="clase" class="col-sm-2 col-form-label">Clase</label>
                  <div class="col-sm-10">
                    <select class="form-select form-select-sm" id="clase" name="clase" required>
                    </select>
                  </div>
                </div>
                <!-- Grupo -->
                <div class="row mb-3">
                  <label for="grupo" class="col-sm-2 col-form-label">Grupo</label>
                  <div class="col-sm-10">
                    <select class="form-select form-select-sm" id="grupo" name="grupo" required>
                    </select>
                  </div>
                </div>
                <!-- Cuenta -->
                <div class="row mb-3">
                  <label for="cuenta" class="col-sm-2 col-form-label">Cuenta</label>
                  <div class="col-sm-10">
                    <select class="form-select form-select-sm" id="cuenta" name="cuenta" required>
                    </select>
                  </div>
                </div>
                <!-- Subcuenta -->
                <div class="row mb-3">
                  <label for="subcuenta" class="col-sm-2 col-form-label">Subcuenta</label>
                  <div class="col-sm-10">
                    <div class="input-group mb-3">
                      <select class="form-select form-select-sm" id="subcuenta" name="subcuenta" required>
                      </select>
                      <button class="btn btn-sm btn-outline-primary" id="btn-subcuenta" type="button"><i class="fa-regular fa-square-plus"></i> </button>
                    </div>
                  </div>
                </div>
                <!-- Auxiliar -->
                <div class="row mb-3">
                  <label for="auxiliar" class="col-sm-2 col-form-label">Auxiliar</label>
                  <div class="col-sm-10">
                    <div class="input-group mb-3">
                      <select class="form-select form-select-sm" id="auxiliar" name="auxiliar">
                      </select>
                      <button class="btn btn-sm btn-outline-primary" id="btn-auxiliar" type="button"><i class="fa-regular fa-square-plus"></i> </button>
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

        <!-- Modal Subcuentas  -->
        <div class="modal fade" id="mdl-subcuentas" aria-hidden="true" data-bs-backdrop="static">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="">Creación de subcuenta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body" id="">
                <div class="card">
                  <div class="card-body">
                    <div class="row">
                      <div class="col-md-4">
                        <label for="txt-id-sub" class="form-label">Id</label>
                        <input type="text" class="form-control form-control-sm" id="txt-id-sub" readonly disabled>
                      </div>
                      <div class="col-md-4">
                        <label for="txt-cuenta-sub" class="form-label">Cuenta</label>
                        <input type="text" class="form-control form-control-sm" id="txt-cuenta-sub" readonly disabled>
                      </div>
                      <div class="col-md-4">
                        <label for="txt-subcuenta-sub" class="form-label">Subcuenta</label>
                        <input type="text" class="form-control form-control-sm" id="txt-subcuenta-sub" placeholder="Subcuenta" maxlength="2" onKeyPress="return vnumeros(event)" autocomplete="off">
                      </div>
                    </div>
                    <label for="txt-descripcion-sub" class="form-label">Descripción</label>
                    <input type="text" class="form-control form-control-sm text-uppercase" autocomplete="off" id="txt-descripcion-sub" placeholder="Descripción">
                    <div class="form-check mt-3">
                      <input class="form-check-input" type="checkbox" id="chk-estado-sub" value="1">
                      <label class="form-check-label" for="chk-estado">Activa</label>
                    </div>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-danger" id="btn-eliminar-sub"><i class="fa-solid fa-trash-can"></i> Eliminar </button>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-guardar-sub"><i class="fa-regular fa-floppy-disk"></i> Guardar </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal"><i class="fa-regular fa-rectangle-xmark"></i> Cerrar </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Modal Auxiliares  -->
        <div class="modal fade" id="mdl-auxiliares" aria-hidden="true" data-bs-backdrop="static">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="">Creación de auxiliares</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body" id="">
                <div class="card">
                  <div class="card-body">
                    <div class="row">
                      <div class="col-md-4">
                        <label for="txt-id-sub" class="form-label">Id</label>
                        <input type="text" class="form-control form-control-sm" id="txt-id-aux" readonly disabled>
                      </div>
                      <div class="col-md-4">
                        <label for="txt-subcuenta-aux" class="form-label">Subcuenta</label>
                        <input type="text" class="form-control form-control-sm" id="txt-subcuenta-aux" readonly disabled>
                      </div>
                      <div class="col-md-4">
                        <label for="txt-subcuenta-sub" class="form-label">Auxiliar</label>
                        <input type="text" class="form-control form-control-sm" id="txt-auxiliar-aux" placeholder="Auxiliar" maxlength="2" onKeyPress="return vnumeros(event)" autocomplete="off">
                      </div>
                    </div>
                    <label for="txt-descripcion-sub" class="form-label">Descripción</label>
                    <input type="text" class="form-control form-control-sm text-uppercase" autocomplete="off" id="txt-descripcion-aux" placeholder="Descripción">
                    <div class="form-check mt-3">
                      <input class="form-check-input" type="checkbox" id="chk-estado-aux" value="1">
                      <label class="form-check-label" for="chk-estado">Activa</label>
                    </div>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-danger" id="btn-eliminar-aux"><i class="fa-solid fa-trash-can"></i> Eliminar </button>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-guardar-aux"><i class="fa-regular fa-floppy-disk"></i> Guardar </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal"><i class="fa-regular fa-rectangle-xmark"></i> Cerrar </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <?php

    $scriptsPagina = [
      '../controllers/cuentasContables.js?' . rand()
    ];

    include '../dependencies/footer.php';

    ?>