    <?php
    session_start();

    $titulo = 'Tipos de terceros';
    include '../dependencies/header.php';
    ?>
    <div class="card" style="height: 95vh;">
      <div class="card-body">
        <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Maestra /</span> Tipos de terceros</h5>
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
                aria-selected="true"> <i class="fa-solid fa-file-circle-plus"></i> &nbsp;Creación </button>
            </li>
            <li class="nav-item">
              <button
                type="button"
                class="nav-link"
                role="tab"
                data-bs-toggle="tab"
                data-bs-target="#navs-listado"
                aria-controls="navs-listado"
                aria-selected="false"> <i class="fa-solid fa-list-ol"></i> &nbsp;Listado </button>
            </li>
          </ul>
          <div class="tab-content">
            <div class="tab-pane fade show active" id="navs-creacion" role="tabpanel">
              <form id="FormCrear" class="needs-validation  form-basico" novalidate>
                <div class="row">
                  <div class="col-md-12">
                    <div class="mb-1" bis_skin_checked="1">
                      <div class="row">
                        <div class="col-12">
                          <div class="demo-inline-spacing d-flex justify-content-end">
                            <button class="btn btn-sm btn-outline-secondary" type="button" id="btnNuevo"> <i class="fa-solid fa-plus"></i> Nuevo</button>
                            <button class="btn btn-sm btn-outline-primary" type="submit" id="btnGuardar"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
                            <button class="btn btn-sm btn-outline-danger" type="button" id="btn-video-tutorial" title="Ver video tutorial"> <i class="fab fa-youtube"></i> Tutorial</button>
                          </div>
                        </div>
                      </div>
                      <div bis_skin_checked="1" class="d-none">
                        <label for="txtId" class="form-label">Id</label>
                        <input type="text" class="form-control form-control-sm" value="" id="txtId" name="id" autocomplete="off" readonly>
                      </div>
                      <div class="card shadow-sm mb-3 mt-3">
                        <div class="card-header">
                          <i class="fa-solid fa-info-circle"></i> Información General
                        </div>

                        <div class="card-body">
                          <div class="row g-3">

                            <div class="col-12 col-md-3">
                              <label for="txtTipo" class="form-label">
                                Tipo
                              </label>
                              <input type="text"
                                class="form-control form-control-sm"
                                id="txtTipo"
                                name="tipo_tercero"
                                maxlength="30"
                                autocomplete="off"
                                placeholder="Ej: Proveedor"
                                required>
                              <div class="form-text">
                                Código corto del tipo
                              </div>
                            </div>

                            <div class="col-12 col-md-6">
                              <label for="txtDescripcion" class="form-label">
                                Descripción
                              </label>
                              <input type="text"
                                class="form-control form-control-sm"
                                name="descripcion"
                                id="txtDescripcion"
                                maxlength="100"
                                autocomplete="off"
                                placeholder="Descripción detallada del tipo"
                                required>
                              <div class="form-text">
                                Máximo 100 caracteres
                              </div>
                            </div>

                            <div class="col-12 col-md-3">
                              <label for="slcInactivo" class="form-label">
                                Estado
                              </label>
                              <select class="form-select form-select-sm"
                                id="slcInactivo"
                                name="inactivo">
                                <option value="0">✓ Activo</option>
                                <option value="1">✕ Inactivo</option>
                              </select>
                            </div>

                          </div>
                        </div>
                      </div>
                      <div class="card shadow-sm mb-3">
                        <div class="card-header">
                          <i class="fa-solid fa-users-gear"></i> Asignaciones de Rol
                        </div>

                        <div class="card-body">
                          <div class="row g-3">

                            <div class="col-md-3">
                              <div class="form-check">
                                <input type="checkbox"
                                  class="form-check-input"
                                  id="es_proveedor"
                                  name="es_proveedor">
                                <label for="es_proveedor" class="form-check-label">
                                  <i class="fa-solid fa-industry text-warning"></i>
                                  Es Proveedor
                                </label>
                              </div>
                            </div>

                            <div class="col-md-3">
                              <div class="form-check">
                                <input type="checkbox"
                                  class="form-check-input"
                                  id="es_cliente"
                                  name="es_cliente">
                                <label for="es_cliente" class="form-check-label">
                                  <i class="fa-solid fa-handshake text-success"></i>
                                  Es Cliente
                                </label>
                              </div>
                            </div>

                            <div class="col-md-3">
                              <div class="form-check">
                                <input type="checkbox"
                                  class="form-check-input"
                                  id="es_vendedor"
                                  name="es_vendedor">
                                <label for="es_vendedor" class="form-check-label">
                                  <i class="fa-solid fa-person-hiking text-danger"></i>
                                  Es Vendedor
                                </label>
                              </div>
                            </div>

                            <div class="col-md-3">
                              <div class="form-check">
                                <input type="checkbox"
                                  class="form-check-input"
                                  id="es_empleado"
                                  name="es_empleado">
                                <label for="es_empleado" class="form-check-label">
                                  <i class="fa-solid fa-briefcase text-primary"></i>
                                  Es Empleado
                                </label>
                              </div>
                            </div>

                          </div>
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
                    <div class="input-group input-group-merge"> <span class="input-group-text"><i class="bx bx-search"></i></span>
                      <input name="" id="filtro" type="text" class="form-control form-control-sm" placeholder="Codigo, descripcion..." aria-label="Buscar..." autocomplete="off">
                      <button class="btn btn-outline-primary btn-sm" id="buscar">Buscar</button>
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
          </div>
        </div>
      </div>
    </div>
    <!-- Modal Video YouTube -->
    <div class="modal fade" id="modalVideoTutorial" tabindex="-1" aria-labelledby="modalVideoTutorialLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalVideoTutorialLabel">
              <i class="fab fa-youtube"></i> Tutorial Video
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-0">
            <div class="ratio ratio-16x9">
              <iframe id="videoFrameTutorial" src="" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
          </div>
        </div>
      </div>
    </div>

    <?php

    $scriptsPagina = [
      '../controllers/tiposTerceros.js?' . rand()
    ];

    include '../dependencies/footer.php';

    ?>