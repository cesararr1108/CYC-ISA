<?php
session_start();

$titulo = 'Reservas de inventario';
include '../dependencies/header.php';
?>
<input type="hidden" value="<?php echo $_SESSION["Ses_id"] ?? 0; ?>" id="usuario_id" readonly />
<input type="hidden" value="<?php echo $_SESSION["Ses_nombres"] . ' ' . $_SESSION["Ses_apellidos"] ?? ''; ?>" id="usuario_nombre" readonly />
<input type="hidden" id="id_bodega_session" value="<?php if (!empty($_SESSION["Ses_id_bodega"])) {
                                                      echo $_SESSION["Ses_id_bodega"];
                                                    } else {
                                                      echo  '0';
                                                    } ?>" />
<div class="card">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-4"><span class="text-muted fw-light">Inventario /</span> Reservas de Inventario</h5>
    <!-- PESTAÑAS: CREAR Y CONSULTAR RESERVAS -->
    <div class="nav-align-top ">
      <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item">
          <button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-crear-pane" role="tab" aria-controls="tab-crear-pane" aria-selected="true">
            <i class="bx bx-plus me-2"></i> Nueva Reserva
          </button>
        </li>
        <li class="nav-item">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#tab-consultar-pane" aria-controls="tab-consultar-pane" aria-selected="false">
            <i class="bx bx-list-ul me-2"></i> Consultar Reservas
          </button>
        </li>
      </ul>


      <div class="tab-content">
        <!-- PESTAÑA 1: CREAR RESERVA -->
        <div class="tab-pane fade show active" id="tab-crear-pane" role="tabpanel" aria-labelledby="tab-crear">
          <div class="row mb-4">
            <div class="col-md-4">
              <div class="card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                  <h5 class="mb-0 text-primary form-label">Datos del tercero</h5>
                </div>
                <div class="card-body">
                  <label for="txtBusquedaTercero" class="form-label">Búsqueda de tercero</label>
                  <div class="input-group input-group-sm input-group-merge"> <span class="input-group-text"><i class="bx bx-search"></i></span>
                    <input type="text" class="form-control" placeholder="Búsqueda de tercero..." aria-label="Búsqueda de tercero..." aria-describedby="basic-addon-search" id="txtBusquedaTercero">
                  </div>
                  <label for="id_tercero" class="form-label"></label>
                  <input type="hidden" id="id_tercero" name="id_tercero" class="form-control form-control-sm" readonly value="0">
                  <label for="nit" class="form-label">NIT</label>
                  <input type="text" class="form-control form-control-sm" name="nit" id="nit" readonly>
                  <label for="nombres" class="form-label">Tratamiento</label>
                  <input type="text" class="form-control form-control-sm text-uppercase" name="tratamiento" id="tratamiento" readonly>
                  <label for="txtRazon" class="form-label">Razón Comercial</label>
                  <input type="text" class="form-control form-control-sm text-uppercase" name="razon_comercial" id="razon_comercial" readonly>
                  <label for="bodegas" class="form-label fw-semibold">Bodega</label>
                  <select class="form-select form-select-sm" id="bodegas" name="id_bodega"></select>
                  <label for="bodegas" class="form-label fw-semibold">Notas</label>
                  <textarea class="form-control form-control-sm" id="notas" rows="3" placeholder="Notas adicionales..."></textarea>
                </div>
              </div>
            </div>
            <div class="col-md-8">
              <div class="card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                  <h5 class="mb-0 text-primary form-label">Selección de Referencias</h5>
                  <div class="d-flex align-items-end gap-2">
                    <button class="btn btn-outline-primary btn-sm" id="btnCrearReserva">
                      <i class="bx bx-save me-1"></i> Crear Reserva
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" id="btnNuevo">
                      <i class="bx bx-refresh me-1"></i> Limpiar
                    </button>
                    <button class="btn btn-outline-danger btn-sm"
                      id="btn-video-tutorial"
                      title="Ver video tutorial">
                      <i class="fa-brands fa-youtube"></i>
                      <span class="d-none d-lg-inline ms-1">Tutorial</span>
                    </button>
                  </div>
                </div>
                <div class="card-body">
                  <input type="hidden" class="form-control form-control-sm" id="txtIdReferencia" readonly>
                  <label for="txtBusquedaReferencia" class="form-label fw-semibold">Referencia</label>
                  <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                    <input type="text" class="form-control form-control-sm" id="txtBusquedaReferencia" placeholder="Buscar referencia (Enter)" autocomplete="off">
                  </div>
                  <div class="row mt-3">
                    <div class="col-md-3">
                      <input type="hidden" class="form-control form-control-sm" id="txtIdLote" placeholder="Lote" autocomplete="off" readonly>
                      <div class="mb-3">
                        <label for="txtLote" class="form-label fw-semibold">Lote / Vencimiento</label>
                        <input type="text" class="form-control form-control-sm" id="txtLote" placeholder="Lote / Vencimiento" autocomplete="off" readonly>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="mb-3">
                        <label for="txtStock" class="form-label fw-semibold">Stock</label>
                        <input type="text" class="form-control form-control-sm" id="txtStock" placeholder="Stock" autocomplete="off" readonly>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="mb-3">
                        <label for="txtReservado" class="form-label fw-semibold">Cantidad</label>
                        <input type="text" class="form-control form-control-sm" id="txtReservado" placeholder="Cantidad" autocomplete="off" inputmode="numeric" pattern="[0-9]*">
                      </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                      <div class="mb-3 w-100">
                        <button class="btn btn-outline-primary btn-sm w-100" id="btnAgregarDetalle">
                          <i class="bx bx-plus me-1"></i> Agregar
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="mt-3">
                <table class="table table-sm table-bordered">
                  <thead class="table-light">
                    <tr>
                      <th>Referencia</th>
                      <th>Lote / Vencimiento</th>
                      <th>Cantidad Reservada</th>
                      <th>Eliminar</th>
                    </tr>
                  </thead>
                  <tbody id="detalleReserva">
                    <tr>
                      <td colspan="4" class="text-center text-muted py-4">
                        <i class="bx bx-info-circle"></i> No hay referencias agregadas
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <!-- PESTAÑA 2: CONSULTAR RESERVAS -->
        <div class="tab-pane fade" id="tab-consultar-pane" role="tabpanel" aria-labelledby="tab-consultar">
          <!-- FILTROS -->
          <div class="row mb-2 ">
            <div class="col-md-3">
              <label for="filtroFechaDesde" class="form-label fw-semibold">Fecha Desde</label>
              <input type="date" class="form-control form-control-sm" id="filtroFechaDesde">
            </div>
            <div class="col-md-3">
              <label for="filtroFechaHasta" class="form-label fw-semibold">Fecha Hasta</label>
              <input type="date" class="form-control form-control-sm" id="filtroFechaHasta">
            </div>
            <div class="col-md-3">
              <label for="filtroEstado" class="form-label fw-semibold">Estado</label>
              <select class="form-select form-select-sm" id="filtroEstado">
                <option value="">Todos</option>
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">&nbsp;</label>
              <div class="d-flex gap-2">
                <button class="btn btn-outline-primary btn-sm flex-grow-1" id="btnAplicarFiltros">
                  <i class="bx bx-filter me-1"></i> Filtrar
                </button>
              </div>
            </div>
          </div>


          <!-- TABLA DE RESERVAS -->
          <div class="table-responsive">
            <table id="tablaReservas" class="table table-sm table-striped table-hover align-middle">

              <thead class="table-primary">
                <tr>
                  <th>Bodega</th>
                  <th>Tercero</th>
                  <th>Usuario</th>
                  <th>Fecha</th>
                  <th class="text-center">Estado</th>
                  <th class="text-center">Acciones</th>
                </tr>
              </thead>

              <tbody>
                <tr>
                  <td colspan="8" class="text-center text-muted py-4">
                    <i class="bx bx-inbox fs-4"></i>
                    <div>Haga clic en "Filtrar" para cargar el listado</div>
                  </td>
                </tr>
              </tbody>

            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Contenedor para modales dinámicos -->
<div id="modal-container"></div>
<div class="modal fade" id="modalDetalleReserva" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detalle de Reserva</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex flex-column p-2 border border-info rounded mb-3">
          <div class="d-flex align-items-center text-info mb-1">
            <i class="bx bx-note me-2"></i>
            <strong>Notas:</strong>
          </div>
          <span id="notasDetalleReserva"></span>
        </div>
        <table class="table table-sm table-striped">
          <thead class="table-primary">
            <tr>
              <th>Código</th>
              <th>Producto</th>
              <th>Lote</th>
              <th>Vencimiento</th>
              <th>Cantidad</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody id="tablaDetalleReserva">
          </tbody>
        </table>

      </div>

    </div>
  </div>
</div>

<div class="modal fade" id="modal-pdf" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detalle de Reserva - PDF</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
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
  '../controllers/reservasInventario.js?' . rand()
];

include '../dependencies/footer.php';

?>