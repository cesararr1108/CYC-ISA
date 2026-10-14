<?php
session_start();

$titulo = 'Reasignar Lotes';
include '../dependencies/header.php';
?>

<input type="hidden" value="<?php echo $_SESSION["Ses_id"] ?? 0; ?>" id="usuario_id" readonly />
<input type="hidden" value="<?php echo $_SESSION["Ses_nombres"] . ' ' . $_SESSION["Ses_apellidos"] ?? ''; ?>" id="usuario_nombre" readonly />
<div class="card">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Inventario /</span> Reasignar Lotes</h5>
    <div class="nav-align-top ">
      <ul class="nav nav-tabs" role="tablist" id="tabs">
        <li class="nav-item">
          <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-busqueda" aria-controls="navs-busqueda" aria-selected="true">
            <i class="fa-solid fa-search"></i> &nbsp;Búsqueda
          </button>
        </li>
        <li class="nav-item">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-historial" aria-controls="navs-historial" aria-selected="false">
            <i class="fa-solid fa-history"></i> &nbsp;Historial
          </button>
        </li>
      </ul>
      <div class="tab-content">

        <!-- PESTAÑA BÚSQUEDA -->
        <div class="tab-pane fade show active" id="navs-busqueda" role="tabpanel">
          <div class="row mt-3">
            <div class="col-md-4">
              <div class="mb-3">
                <input type="hidden" id="txtIdReferenciaActual" readonly>
                <label for="txtBusquedaReferencia" class="form-label"><strong>Referencia</strong></label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text"><i class="bx bx-search"></i></span>
                  <input type="text" class="form-control" id="txtBusquedaReferencia" placeholder="Referencia" autocomplete="off">
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label for="txtBusquedaLote" class="form-label"><strong>Lote</strong></label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text"><i class="bx bx-search"></i></span>
                  <input type="text" class="form-control" id="txtBusquedaLote" placeholder="Lote" autocomplete="off">
                </div>
              </div>
            </div>
            <div class="col-md-4" style="display: flex; align-items: flex-end;">
              <div class="mb-3" style="width: 100%;">
                <button class="btn btn-sm btn-outline-primary w-100" type="button" id="btnBuscarLote">
                  <i class="fa-solid fa-search"></i> Buscar
                </button>
              </div>
            </div>
          </div>

          <!-- Tabla de resultados búsqueda -->
          <div class="row mt-3">
            <div class="col-12">
              <div id="divResultados" style="display:none;">
                <h6 class="mb-2">Referencias encontradas:</h6>
                <div class="table-responsive">
                  <table class="table table-sm table-bordered" id="tblResultados">
                    <thead class="table-primary">
                      <tr>
                        <th>ID</th>
                        <th>ID Referencia</th>
                        <th>Lote</th>
                        <th>Vencimiento</th>
                        <th>Acción</th>
                      </tr>
                    </thead>
                    <tbody></tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <!-- Formulario de reasignación -->
          <form id="formReasignar" class="needs-validation" novalidate style="display:none;">
            <div class="row mt-4">
              <div class="col-12">
                <h6 class="mb-3 text-primary"><strong>Información del Lote Seleccionado</strong></h6>
              </div>
            </div>

            <div class="row">
              <div class="col-md-3">
                <div class="mb-3">
                  <input type="hidden" id="txtIdLote" readonly>

                  <label for="txtIdReferencia" class="form-label">Referencia</label>
                  <input type="text" class="form-control form-control-sm" id="txtIdReferencia" placeholder="Referencia" readonly>
                  <span id="descripcion" class="text-muted"></span>
                </div>
              </div>
              <div class="col-md-3">
                <div class="mb-3">
                  <label for="txtLoteActual" class="form-label">Lote Actual</label>
                  <input type="text" class="form-control form-control-sm" id="txtLoteActual" readonly>
                </div>
              </div>
              <div class="col-md-3">
                <div class="mb-3">
                  <label for="txtVencimientoActual" class="form-label">Vencimiento Actual</label>
                  <input type="text" class="form-control form-control-sm" id="txtVencimientoActual" readonly>
                </div>
              </div>
            </div>

            <div class="row mt-4">
              <div class="col-12">
                <h6 class="mb-3 text-success"><strong>Datos Nuevos</strong></h6>
              </div>
            </div>

            <div class="row">
              <div class="col-md-3">
                <div class="mb-3">
                  <label for="txtNuevoLote" class="form-label"><strong>Nuevo Lote</strong></label>
                  <input type="text" class="form-control form-control-sm" id="txtNuevoLote" required>
                </div>
              </div>
              <div class="col-md-3">
                <div class="mb-3">
                  <label for="txtNuevoVencimiento" class="form-label"><strong>Nuevo Vencimiento</strong></label>
                  <input type="date" class="form-control form-control-sm" id="txtNuevoVencimiento" required>
                </div>
              </div>
            </div>

            <div class="row mt-4">
              <div class="col-12">
                <button type="button" class="btn btn-sm btn-light border-1" id="btnLimpiar">Limpiar</button>
                <button type="submit" class="btn btn-sm btn-primary" id="btnReasignar">
                  <i class="fa-solid fa-save"></i> Guardar Cambios
                </button>
              </div>
            </div>
          </form>
        </div>

        <!-- PESTAÑA HISTORIAL -->
        <div class="tab-pane fade" id="navs-historial" role="tabpanel">
          <div class="row mt-3">
            <div class="col-12">
              <div class="mb-3">
                <label for="txtFiltroHistorial" class="form-label">Filtrar por usuario ,acción o referencia:</label>
                <div class="input-group input-group-sm">
                  <input type="hidden" id="textIdReferenciaHistorial" readonly>
                  <input type="text" class="form-control" id="txtFiltroHistorial" placeholder="Búscar..." autocomplete="off">
                  <button class="btn btn-outline-primary" type="button" id="btnActualizarHistorial">
                    <i class="fa-solid fa-arrows-rotate"></i> Actualizar
                  </button>
                </div>
              </div>
              <div class="table-responsive text-nowrap">
                <table class="table table-sm table-bordered" id="tblHistorial">
                  <thead class="table-primary">
                    <tr>
                      <th>Fecha</th>
                      <th>Usuario</th>
                      <th>Acción</th>
                      <th>Tabla Afectada</th>
                      <th>Detalle</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MODAL TRANSACCIONES -->
<div class="modal fade" id="modalTransacciones" tabindex="-1" aria-labelledby="modalTransaccionesLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header text-white">
        <h5 class="modal-title" id="modalTransaccionesLabel">
          <i class="fa-solid fa-list"></i> Transacciones que serán afectadas
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" style="max-height: 400px; overflow-y: auto;">
        <div class="row mb-3">
          <div class="col-md-6">
            <strong>Lote:</strong> <span id="modLote"></span>
          </div>
          <div class="col-md-6">
            <strong>Vencimiento:</strong> <span id="modVencimiento"></span>
          </div>
          <div class="col-md-6 mt-2">
            <strong>ID Referencia:</strong> <span id="modIdReferencia"></span>
          </div>
          <div class="col-md-6 mt-2">
            <strong>Total de movimientos:</strong> <span class="badge bg-info" id="modTotalMovimientos">0</span>
          </div>
        </div>
        <div class="table-responsive text-nowrap">
          <table class="table table-sm table-bordered table-hover" id="tblTransaccionesDetalle">
            <thead class="table-light">
              <tr>
                <th>Bodega</th>
                <th>Tipo Doc.</th>
                <th>Descripción</th>
                <th>Fecha</th>
                <th>Cantidad</th>
                <th>Und</th>
                <th>Lote</th>
                <th>Vencimiento</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-outline-primary" id="btnConfirmarTransacciones">
          <i class="fa-solid fa-check"></i> Entendido, Proceder
        </button>
      </div>
    </div>
  </div>
</div>

<div id="modal-container"></div>


<?php

$scriptsPagina = [
  '../controllers/reasignarlotes.js?' . rand()
];
include '../dependencies/footer.php';

?>