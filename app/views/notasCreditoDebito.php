<?php
session_start();
$titulo = 'Notas crédito y débito';
include '../dependencies/header.php';
?>
<style>
  .fila-descuento-aplicado {
    background-color: #fff8e1 !important;
  }
</style>
<input type="hidden" id="bodega" value="<?php if (!empty($_SESSION["Ses_bodega"])) {
                                          echo $_SESSION["Ses_bodega"];
                                        } ?>" readonly>
<div class="card">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Financiero /</span> Notas Crédito y Débito</h5>
    <div class="nav-align-top ">
      <ul class="nav nav-tabs" role="tablist" id="tabsNotas">
        <li class="nav-item">
          <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-crear" type="button"> <i class="fa-solid fa-file-circle-plus"></i> Crear Nota </button>
        </li>
        <li class="nav-item">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-historial" type="button"> <i class="fa-solid fa-list-ol"></i> Historial </button>
        </li>
        <li class="nav-item" style="flex:1;"></li>
        <li class="nav-item border-danger">
        </li>
      </ul>
      <div class="tab-content">

        <div class="tab-pane fade show active" id="tab-crear">
          <form id="FormNota" class="needs-validation form-basico" novalidate>
            <div class="row">
              <!-- COLUMNA IZQUIERDA - TERCERO -->
              <div class="col-md-4">
                <div class="card h-100 mb-3">
                  <h5 class="card-header form-label text-primary">Información del Tercero</h5>
                  <div class="card-body">
                    <input type="hidden" id="id_tercero" name="id_tercero">
                    <div class="mb-2">
                      <label class="form-label">Tipo de Nota</label>
                      <select id="tipo_nota" name="tipo_nota" class="form-select form-select-sm" required></select>
                    </div>
                    <div class="mb-2">
                      <label class="form-label">Tipo de Documento</label>
                      <select id="tipo_documento" name="tipo_documento" class="form-select form-select-sm" required></select>
                    </div>
                    <div class="mb-2">
                      <label class="form-label">Búsqueda</label>
                      <div class="input-group input-group-sm">
                        <span class="input-group-text">
                          <i class="bx bx-search"></i>
                        </span>
                        <input type="text" class="form-control" placeholder="Buscar cliente o proveedor..." id="txtBusquedaTercero" disabled>
                      </div>
                    </div>

                    <div class="mb-2">
                      <label class="form-label">NIT</label>
                      <input type="text" id="nit" name="nit" class="form-control form-control-sm" readonly>
                    </div>
                    <div class="mb-2">
                      <label class="form-label">Razón Comercial</label>
                      <input type="text" id="razon_comercial" name="razon_comercial" class="form-control form-control-sm" readonly>
                    </div>
                    <div class="mb-2">
                      <label class="form-label">Dirección</label>
                      <select id="direccion" name="id_tercero_dir" class="form-select form-select-sm"></select>
                    </div>
                    <div class="mb-2">
                      <label class="form-label">Condición de Pago</label>
                      <select id="cond_pago" name="id_condicion"
                        class="form-select form-select-sm" disabled></select>
                    </div>
                    <div class="mb-2">
                      <label class="form-label">Documento Referencia</label>
                      <input id="doc_ref" name="doc_ref" class="form-control form-control-sm" placeholder="Factura o pedido">
                    </div>
                  </div>
                </div>
              </div>

              <!-- COLUMNA DERECHA -->
              <div class="col-md-8">
                <!-- INFORMACIÓN FINANCIERA -->
                <div class="card h-100 mb-3">
                  <h5 class="card-header form-label text-primary">Información Financiera</h5>
                  <div class="card-body">
                    <div class="row mb-2">
                      <div class="col-md-4">
                        <label class="form-label">Concepto</label>
                        <select id="concepto" name="concepto"
                          class="form-select form-select-sm">
                          <option value="NO APLICA">NO APLICA</option>
                          <option value="DEVOLUCIÓN">DEVOLUCIÓN</option>
                          <option value="DESCUENTO">DESCUENTO</option>
                        </select>
                      </div>

                      <div class="col-md-4">
                        <label class="form-label">Fecha Vencimiento</label>
                        <input type="date" id="fecha_vencimiento" name="vencimiento" class="form-control form-control-sm" readonly>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">Días</label>
                        <input id="dias" name="dias" class="form-control form-control-sm text-center" readonly>
                      </div>
                    </div>
                    <div class="row mb-2">
                      <div class="col-md-4">
                        <label class="form-label">Subtotal</label>
                        <input id="subtotal" name="subtotal" class="form-control form-control-sm text-end" placeholder="0.00" required>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">IVA</label>
                        <input id="iva" name="iva" class="form-control form-control-sm text-end" placeholder="0.00">
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">Descuento</label>
                        <input id="descuento" name="descuento" class="form-control form-control-sm text-end" placeholder="0.00">
                      </div>
                    </div>

                    <div class="row mb-2">
                      <div class="col-md-4">
                        <label class="form-label">Retefuente</label>
                        <input id="retefuente" name="retencion_fuente" class="form-control form-control-sm text-end" placeholder="0.00">
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">ReteIVA</label>
                        <input id="reteiva" name="retencion_iva" class="form-control form-control-sm text-end" placeholder="0.00">
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">ReteICA</label>
                        <input id="reteica" name="retencion_ica" class="form-control form-control-sm text-end" placeholder="0.00">
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-md-12">
                        <label class="form-label">Valor Neto</label>
                        <input id="valor_neto" name="valor_neto" class="form-control form-control-sm text-end fw-bold text-primary" readonly>
                      </div>
                    </div>
                    <div class="mb-2">
                      <label class="form-label">Observaciones</label>
                      <textarea id="nota" name="notas" class="form-control form-control-sm" rows="2" placeholder="Ingrese comentarios o motivo de la nota..." required></textarea>
                    </div>
                    <div class="mb-2">
                      <button class="btn btn-sm btn-success d-none" type="button" id="btn-asociar-doc" data-bs-toggle="modal" data-bs-target="#modalBuscarDocumento">
                        <i class="fa-solid fa-paperclip"></i> Asociar documento
                      </button>
                      <button class="btn btn-sm btn-outline-primary" type="submit">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar Nota
                      </button>
                      <button class="btn btn-sm btn-outline-secondary" type="button" id="btn-nuevo">
                        <i class="fa-solid fa-file"></i> Nuevo
                      </button>
                    </div>

                    <div class="row">
                      <div class="col-12">
                        <div id="contenedor-productos-descuento" class="table-responsive text-nowrap"></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>

        <!-- 📋 HISTORIAL -->
        <div class="tab-pane fade" id="tab-historial">
          <div class="row mb-3">
            <div class="col-6">
              <div class="input-group"> <span class="input-group-text"><i class="fa-solid fa-calendar-days"></i></span>
                <input id="fecha_ini" type="date" class="form-control form-control-sm" />
              </div>
            </div>
            <div class="col-6">
              <div class="input-group"> <span class="input-group-text"><i class="fa-solid fa-calendar-days"></i></span>
                <input id="fecha_fin" type="date" class="form-control form-control-sm" />
              </div>
            </div>
          </div>
          <div class="row mt-2">
            <div class="col">
              <button class="btn btn-outline-primary btn-sm" id="btn-buscar"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
            </div>
          </div>
          <div class="row mt-3">
            <div class="col-12">
              <div id="result"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>


<!-- MODAL: Búsqueda de Documentos -->
<div class="modal fade" id="modalBuscarDocumento" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fa-solid fa-magnifying-glass"></i> Buscar Documentos a Aplicar
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row mb-3">
          <div class="col-md-3">
            <label>Fecha Inicial</label>
            <input type="date" id="filtro_fecha_ini" class="form-control form-control-sm">
          </div>
          <div class="col-md-3">
            <label>Fecha Final</label>
            <input type="date" id="filtro_fecha_fin" class="form-control form-control-sm">
          </div>
          <div class="col-md-3">
            <label>Tipo de Documento</label>
            <input type="text" id="filtro_tipo_doc" class="form-control form-control-sm" placeholder="Ej: FACTURA">
          </div>
          <div class="col-md-3">
            <label>Número de Documento</label>
            <input type="text" id="filtro_numero" class="form-control form-control-sm" placeholder="Ej: 12345">
          </div>
        </div>
        <div class="row mb-3">

          <div class="col-md-12 d-flex align-items-end">
            <button type="button" class="btn btn-outline-primary btn-sm" id="btn-filtrar-documentos">
              <i class="fa-solid fa-magnifying-glass"></i> Buscar
            </button>
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <div id="contenedor-tabla-documentos" class="table-responsive text-nowrap"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>


<!-- Modal para mostrar detalles de transacciones -->
<div class="modal fade" id="modalDetalleTransaccion" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fa-solid fa-list"></i> Detalle de Transacción
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <!-- Panel de descuentos -->
        <div class="card mb-3">
          <div class="card-body">
            <div class="row">
              <div class="col-md-3">
                <label>Porcentaje (%)</label>
                <input type="number" id="descuento_porcentaje" class="form-control form-control-sm" placeholder="0" min="0" max="100" step="0.01">
              </div>
              <div class="col-md-3">
                <label>Valor ($)</label>
                <input type="text" id="descuento_valor" class="form-control form-control-sm" placeholder="$0.00">
              </div>
              <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-primary btn-sm w-100" id="btn-aplicar-descuento">
                  <i class="fa-solid fa-calculator"></i> Calcular
                </button>
              </div>
              <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="btn-limpiar-descuentos">
                  <i class="fa-solid fa-eraser"></i> Limpiar
                </button>
              </div>
              <div class="col-md-2 d-flex align-items-end">
                <div class="w-100 border rounded px-2 py-1 bg-light">
                  <small class="text-muted d-block">Valor aplicado</small>
                  <strong id="total-descuento-aplicado" class="text-primary">$0</strong>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tabla de detalles -->
        <div class="table-responsive w-100">
          <div id="contenedor-tabla-detalles" class="table-responsive text-nowrap"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-sm btn-outline-success" id="btn-aplicar-descuentos-finales">
          <i class="fa-solid fa-check"></i> Aplicar Cambios
        </button>
      </div>
    </div>
  </div>
</div>

<?php
$scriptsPagina = [
  '../controllers/notasCreditoDebito.js?' . rand()
];
include '../dependencies/footer.php';
?>