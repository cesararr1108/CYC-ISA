  <?php
  session_start();

  $titulo = 'Marcas';
  include '../dependencies/header.php';
  ?>
  <div class="card">
    <div class="card-body">
      <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Maestra /</span> Marcas</h5>
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
                          <button class="btn btn-sm btn-light border-1 border border-light" type="button" id="btnNuevo">Nuevo</button>
                          <button class="btn btn-sm btn-primary  border-1 border border-primary" type="submit" id="btnGuardar">Guardar</button>
                        </div>
                      </div>
                    </div>
                    <div bis_skin_checked="1" style="display:none">
                      <label for="txtId" class="form-label">Id</label>
                      <input type="text" class="form-control form-control-sm" value="" id="txtId" name="id" autocomplete="off" readonly>
                    </div>
                    <div bis_skin_checked="1">
                      <label for="txtDescripcion" class="form-label">Descripción</label>
                      <input type="text" class="form-control form-control-sm" name="descripcion" id="txtDescripcion" maxlength="50" autocomplete="off" required>
                    </div>
                    <div bis_skin_checked="1">
                      <label for="txtUrl" class="form-label">Estado</label>
                      <select class="form-select form-select-sm" id="slcInactivo" name="inactivo">
                        <option value="0">Activa</option>
                        <option value="1">Inactiva</option>
                      </select>
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

  <?php

  $scriptsPagina = [
    '../controllers/marcas.js?' . rand()
  ];

  include '../dependencies/footer.php';

  ?>