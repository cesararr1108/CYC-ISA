  <?php
  session_start();

  $titulo = 'Homologación de códigos';
  include '../dependencies/header.php';
  ?>
  <style>
    .swal2-container {
      z-index: 20000 !important;
    }

    .table-preview thead th,
    .table-listado thead th {
      white-space: nowrap;
      vertical-align: middle;
    }

    .preview-badge {
      min-width: 105px;
    }

    .preview-error {
      background: #fff5f5 !important;
    }

    .preview-ok {
      background: #f0fff4 !important;
    }

    .resultados-busqueda {
      max-height: 320px;
      overflow: auto;
    }
  </style>
  <input type="hidden" id="id_usuario" value="<?php echo isset($_SESSION['Ses_id']) ? (int) $_SESSION['Ses_id'] : 0; ?>">

  <div class="card" style="height: auto;">
    <div class="card-body">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
          <h5 class="fw-bold py-0 mb-1"><span class="text-muted fw-light">Maestra /</span> Homologación de códigos</h5>
          <div class="text-muted small">Carga CSV, previsualiza y asocia referencias manualmente cuando no exista coincidencia.</div>
        </div>
        <!--<div class="d-flex flex-wrap gap-2">
          <button type="button" class="btn btn-outline-success btn-sm" id="btnSeleccionarArchivo"><i class="fa-solid fa-file-csv"></i> Cargar CSV</button>
          <button type="button" class="btn btn-outline-primary btn-sm" id="btnGuardarHomologacion"><i class="fa-regular fa-floppy-disk"></i> Guardar</button>
          <button type="button" class="btn btn-outline-secondary btn-sm" id="btnLimpiarHomologacion"><i class="fa-regular fa-file"></i> Limpiar</button>
        </div>-->
      </div>

      <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-carga" type="button" role="tab">Carga y previsualización</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-listado" type="button" role="tab">Listado homologado</button>
        </li>
      </ul>

      <div class="tab-content">
        <div class="tab-pane fade show active" id="tab-carga" role="tabpanel">
          <div class="row g-3 mb-3">
            <div class="col-lg-4">
              <div class="card h-100 border">
                <div class="card-body">
                  <div class="mb-3">
                    <label class="form-label">Archivo CSV</label>
                    <div class="input-group input-group-sm mb-2">
                      <input
                        type="file"
                        class="d-none"
                        id="txtArchivoHomologacion"
                        accept=".csv,.txt">

                      <button
                        type="button"
                        class="btn btn-outline-success"
                        id="btnSeleccionarArchivo">
                        <i class="fa-solid fa-file-csv me-1"></i>
                        Cargar CSV
                      </button>

                      <input
                        type="text"
                        class="form-control"
                        id="txtNombreArchivo"
                        placeholder="Seleccione un archivo..."
                        readonly>
                    </div>
                    <!-- <input type="file" class="form-control form-control-sm d-none" id="txtArchivoHomologacion" accept=".csv,.txt">
                    <button type="button" class="btn btn-outline-success btn-sm" id="btnSeleccionarArchivo"><i class="fa-solid fa-file-csv"></i> Cargar CSV</button>
                    <input type="text" class="form-control form-control-sm mb-2" id="txtNombreArchivo" placeholder="Seleccione un archivo" readonly>-->
                    <div class="d-flex gap-2">
                      <button type="button" class="btn btn-outline-primary btn-sm" id="btnProcesarArchivo"><i class="fa-solid fa-magnifying-glass"></i> Previsualizar</button>
                      <button type="button" class="btn btn-outline-primary btn-sm" id="btnGuardarHomologacion"><i class="fa-regular fa-floppy-disk"></i> Guardar</button>
                      <button type="button" class="btn btn-outline-secondary btn-sm" id="btnLimpiarHomologacion"><i class="fa-regular fa-file"></i> Limpiar</button>
                      <!--<button type="button" class="btn btn-outline-secondary btn-sm" id="btnLimpiarArchivo"><i class="fa-regular fa-trash-can"></i></button>-->
                    </div>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Tipo de listado</label>
                    <select class="form-select form-select-sm" id="slcTipoListado">
                      <option value="PROVEEDOR">Proveedor - Precio/Costo</option>
                      <option value="CLIENTE">Cliente - Precio</option>
                    </select>
                  </div>
                  <div class="alert alert-info mb-0">
                    <strong>Estructura esperada:</strong>
                    <ol class="mb-0 mt-2 ps-3">
                      <li>Código de barras</li>
                      <li>Código de proveedor</li>
                      <li>NIT del proveedor</li>
                      <li>Stock, opcional</li>
                      <li>Precio o costo</li>
                    </ol>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-lg-8">
              <div class="row g-3 mb-3">
                <div class="col-md-3">
                  <div class="card border h-100">
                    <div class="card-body py-3">
                      <div class="text-muted small">Total filas</div>
                      <div class="fs-4 fw-bold" id="lblTotalFilas">0</div>
                    </div>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="card border h-100">
                    <div class="card-body py-3">
                      <div class="text-muted small">Asociadas</div>
                      <div class="fs-4 fw-bold text-success" id="lblTotalAsociadas">0</div>
                    </div>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="card border h-100">
                    <div class="card-body py-3">
                      <div class="text-muted small">Pendientes</div>
                      <div class="fs-4 fw-bold text-danger" id="lblTotalPendientes">0</div>
                    </div>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="card border h-100">
                    <div class="card-body py-3">
                      <div class="text-muted small">NIT detectados</div>
                      <div class="fs-4 fw-bold text-primary" id="lblTotalNits">0</div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="card border">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                  <div>
                    <strong>Vista previa del archivo</strong>
                    <div class="text-muted small">Puede editar stock y precio/costo directamente antes de guardar.</div>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <label for="slcFiltroPreview" class="small text-muted mb-0">Ver:</label>
                    <select id="slcFiltroPreview" class="form-select form-select-sm" style="min-width: 150px;">
                      <option value="todos" selected>Todos</option>
                      <option value="pendientes">Solo pendientes</option>
                      <option value="asociados">Solo asociados</option>
                    </select>
                    <div class="text-muted small" id="lblEstadoArchivo">Sin archivo cargado</div>
                  </div>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover align-middle table-preview" id="tblPreviewHomologacion">
                      <thead class="table-primary">
                        <tr>
                          <th>#</th>
                          <th>Código barras</th>
                          <th>Código proveedor</th>
                          <th>NIT</th>
                          <th>Stock</th>
                          <th class="col-2">Precio/Costo</th>
                          <th>Referencia sistema</th>
                          <th>Estado</th>
                          <th>Acción</th>
                        </tr>
                      </thead>
                      <tbody id="tbodyPreviewHomologacion"></tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="tab-pane fade" id="tab-listado" role="tabpanel">
          <div class="card border">
            <div class="card-body">
              <div class="row g-2 mb-3">
                <div class="col-md-4">
                  <input type="text" class="form-control form-control-sm" id="txtFiltroListado" placeholder="Buscar por código, descripción o NIT">
                </div>
                <div class="col-md-3">
                  <button type="button" class="btn btn-outline-primary btn-sm" id="btnBuscarListado"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
                </div>
              </div>
              <div class="table-responsive" id="DivListadoHomologacion">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="modalBuscarReferencia" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Buscar y asociar referencia</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="input-group input-group-sm mb-3">
            <input type="text" class="form-control" id="txtBuscarReferencia" placeholder="Código, EAN o descripción">
            <button type="button" class="btn btn-primary" id="btnBuscarReferencia"><i class="fa-solid fa-magnifying-glass"></i></button>
          </div>
          <div class="alert alert-light border small mb-3">
            Seleccione una referencia para asociarla a la fila que quedó pendiente.
          </div>
          <div class="resultados-busqueda table-responsive">
            <table class="table table-sm table-hover align-middle">
              <thead class="table-secondary">
                <tr>
                  <th>Código</th>
                  <th>EAN</th>
                  <th>Descripción</th>
                  <th></th>
                </tr>
              </thead>
              <tbody id="tbodyBusquedaReferencia"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>


  <?php

  $scriptsPagina = [
    '../controllers/HomologacionCodigos.js?' . rand()
  ];

  include '../dependencies/footer.php';

  ?>