<?php
session_start();

$titulo = 'Egresos y pagos';
include '../dependencies/header.php';
?>
<div class="card">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Tesorería /</span> Egresos y pagos</h5>
    <!-- Totales -->
    <style>
      .rc-resumen .form-label {
        font-size: .7rem;
        font-weight: 600;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: #6c757d;
        margin-bottom: .25rem
      }

      .rc-resumen .input-group-text {
        background: #f5f6f8;
        color: #6c757d
      }

      .rc-resumen .rc-total {
        text-align: right;
        font-weight: 600;
        font-variant-numeric: tabular-nums
      }

      .rc-resumen .rc-acciones .btn {
        white-space: nowrap
      }

      .rc-resumen #hintCruce {
        display: block;
        min-height: 1.25rem
      }

      @media (prefers-color-scheme: dark) {
        .rc-resumen .input-group-text {
          background: transparent
        }
      }

      [data-bs-theme="dark"] .rc-resumen .input-group-text,
      .dark-style .rc-resumen .input-group-text {
        background: transparent
      }
    </style>
    <div class="card mb-3 border border-danger rc-resumen">
      <div class="card-header d-flex align-items-center justify-content-between py-2">
        <span class="badge bg-danger px-3 py-2"><i class="fa-solid fa-arrow-up"></i> Egresos</span>
        <small id="hintCruce" class="small text-muted text-end" aria-live="polite"></small>
      </div>
      <div class="card-body pt-2 pb-3">
        <div class="row g-2 align-items-end">
          <div class="col-12 col-lg-3">
            <label class="form-label" for="txt_tipo_doc">Tipo de documento</label>
            <div class="input-group input-group-sm"> <span class="input-group-text"><i class="fa-solid fa-file-archive"></i></span>
              <select class="form-select bg-light" id="txt_tipo_doc">
              </select>
            </div>
          </div>
          <div class="col-6 col-lg-2">
            <label class="form-label" for="txt_Abonos">Abonos</label>
            <div class="input-group input-group-sm"> <span class="input-group-text"><i class="fa-solid fa-dollar-sign"></i></span>
              <input type="text" id="txt_Abonos" class="form-control rc-total disabled-field" readonly>
            </div>
          </div>
          <div class="col-6 col-lg-2">
            <label class="form-label" for="txt_Documentos">Documentos</label>
            <div class="input-group input-group-sm"> <span class="input-group-text"><i class="fa-solid fa-file-invoice"></i></span>
              <input type="text" id="txt_Documentos" class="form-control rc-total disabled-field" readonly>
            </div>
          </div>
          <div class="col-12 col-lg-2">
            <label class="form-label" for="txt_SinAsignar">Sin asignar</label>
            <div class="input-group input-group-sm"> <span class="input-group-text"><i class="fa-solid fa-hand-holding-dollar"></i></span>
              <input type="text" id="txt_SinAsignar" class="form-control rc-total disabled-field" readonly>
            </div>
          </div>
          <div class="col-12 col-lg-3 d-flex justify-content-lg-end gap-2 rc-acciones">
            <button type="button" class="btn btn-sm btn-primary flex-fill flex-lg-grow-0" id="btnGuardar"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
            <button type="button" class="btn btn-sm btn-outline-secondary flex-fill flex-lg-grow-0" id="btnLimpiar"><i class="fa-solid fa-eraser"></i> Nuevo</button>
          </div>
        </div>
      </div>
    </div>
    <div class="card mb-3">
      <div class="card-body pt-2 pb-3">
        <div class="nav-align-top">
          <ul class="nav nav-tabs" role="tablist" id="tabs">
            <li class="nav-item" role="presentation">
              <button type="button" class="nav-link active" id="tab-proveedor" role="tab" data-bs-toggle="tab" data-bs-target="#dvCliente" aria-controls="dvCliente" aria-selected="true"> <i class="fa-solid fa-user-plus"></i> &nbsp;Proveedor </button>
            </li>
            <li class="nav-item" role="presentation">
              <button type="button" class="nav-link" id="tab-documentos" role="tab" data-bs-toggle="tab" data-bs-target="#dvDocumentos" aria-controls="dvDocumentos" aria-selected="false"> <i class="fa-solid fa-file-invoice"></i> &nbsp;Documentos <span class="badge bg-label-primary ms-1" id="badgeDocs">0</span> </button>
            </li>
            <li class="nav-item" role="presentation">
              <button type="button" class="nav-link" id="tab-pagos" role="tab" data-bs-toggle="tab" data-bs-target="#dvPagos" aria-controls="dvPagos" aria-selected="false"> <i class="fa-solid fa-cash-register"></i> Pagos <span class="badge bg-label-primary ms-1" id="badgePagos">0</span> </button>
            </li>
            <li class="nav-item" role="presentation">
              <button type="button" class="nav-link" id="tab-preliminares" role="tab" data-bs-toggle="tab" data-bs-target="#dvPreliminares" aria-controls="dvPreliminares" aria-selected="false"> <i class="fa-solid fa-list-check"></i> Preliminar </button>
            </li>
          </ul>
          <div class="tab-content">
            <div class="tab-pane fade show active" id="dvCliente" role="tabpanel">
              <div class="row">
                <div class="col-md-4">
                  <label class="form-label">Proveedor</label>
                  <div class="input-group mb-3"> <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                    <input type="text" id="txt_cliente" name="txt_cliente" autocomplete="off" class="form-control form-control-sm" placeholder="Búsqueda del proveedor" />
                    <input type="hidden" id="txt_cliente_id" name="txt_cliente_id" class="form-control form-control-sm bg-light" placeholder="id" readonly disabled />
                  </div>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Nit</label>
                  <div class="input-group mb-3"> <span class="input-group-text"><i class="fa-regular fa-address-card"></i></span>
                    <input type="text" id="txt_cliente_nit" name="txt_cliente_nit" class="form-control form-control-sm bg-light" placeholder="NIT" readonly disabled />
                  </div>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Razón social</label>
                  <div class="input-group mb-3"> <span class="input-group-text"><i class="fa-regular fa-address-card"></i></span>
                    <input type="text" id="txt_razon_social" name="txt_razon_social" class="form-control form-control-sm bg-light" placeholder="Razón social" readonly disabled />
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-4">
                  <label for="id_tercero_dir" class="form-label">Dirección</label>
                  <div class="input-group mb-3"> <span class="input-group-text"><i class="fa-regular fa-address-book"></i></span>
                    <select id="id_tercero_dir" name="id_tercero_dir" class="form-select form-select-sm">
                    </select>
                  </div>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Fecha</label>
                  <div class="input-group mb-3"> <span class="input-group-text"><i class="fa-solid fa-calendar-days"></i></span>
                    <input type="date" id="txt_fecha_rc" name="txt_fecha_rc" class="form-control form-control-sm " />
                  </div>
                </div>
                <div class="col-md-4">
                  <label for="id_condicion" class="form-label">Condición de pago</label>
                  <div class="input-group mb-3"> <span class="input-group-text"><i class="fa-solid fa-file-invoice-dollar"></i></span>
                    <select class="form-select form-select-sm" id="id_condicion" name="id_condicion">
                    </select>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-4">
                  <label class="form-label">Documento | Referencia</label>
                  <div class="input-group mb-3"> <span class="input-group-text"><i class="fa-solid fa-flag"></i></span>
                    <input type="text" id="txt_Documento" name="txt_Documento" autocomplete="off" class="form-control form-control-sm " placeholder="Doc" />
                    <input type="text" id="txt_Referencia" name="txt_Referencia" autocomplete="off" class="form-control form-control-sm " placeholder="Ref" />
                  </div>
                </div>
                <div class="col-md-8">
                  <label class="form-label">Notas</label>
                  <div class="input-group mb-3"> <span class="input-group-text"><i class="fa-regular fa-comment"></i></span>
                    <textarea class="form-control form-control-sm bg-warning-light" maxlength="200" id="txt_notas_rc" name="txt_notas_rc" placeholder="Notas, máximo 200 caracteres"></textarea>
                  </div>
                </div>
              </div>
            </div>
            <!-- Documentos -->
            <div class="tab-pane table-responsive fade" id="dvDocumentos" role="tabpanel" style="height:400px; overflow:auto;">
              <div class="p-3 text-center text-muted">Seleccione un proveedor para ver sus documentos pendientes...</div>
            </div>
            <!-- Pagos -->
            <div class="tab-pane fade overflow-auto" style="height: 400px;" id="dvPagos" role="tabpanel">
              <div class="row">
                <div class="col-md-6">
                  <label class="form-label">Tipo de pago</label>
                  <div class="input-group mb-3"> <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                    <select class="form-select form-select-sm bg-light" id="txt_tipo_pago">
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Valor</label>
                  <div class="input-group mb-3"> <span class="input-group-text"><i class="fa-regular fa-address-card"></i></span>
                    <input type="text" id="txt_valor_pago" name="txt_valor_pago" class="form-control form-control-sm bg-light js-money" inputmode="decimal" autocomplete="off" />
                    <button type="button" class="btn btn-outline-success btn-sm" id="btnPagarTotal" title="Cargar el valor pendiente por asignar"> <i class="fa-solid fa-check-double"></i> </button>
                    <button class="btn btn-outline-primary btn-sm" type="button" id="btnAgregarPago"> <i class="fa-solid fa-circle-plus"></i> Agregar </button>
                  </div>
                </div>
              </div>
              <table class="table data-pagos table-sm table-striped table-hover table-bordered table-responsive-md">
                <thead class="cabecera table-primary">
                  <tr>
                    <th data-breakpoints="xs">Modelo</th>
                    <th data-breakpoints="xs">Forma</th>
                    <th>Valor</th>
                    <th>Cuenta</th>
                    <th>Eliminar</th>
                  </tr>
                </thead>
                <tbody id="tdAddPagos">
                </tbody>
              </table>
            </div>

            <!-- Preliminares -->
            <div class="tab-pane fade" id="dvPreliminares" role="tabpanel" style="height:400px; overflow:auto;">
              <div class="row g-2">
                <div class="col-md-6">
                  <label class="form-label">Filtro</label>
                  <input type="text" id="txt_filtro_rc" class="form-control form-control-sm bg-light" placeholder="Buscar...">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Fecha Inicial</label>
                  <input type="date" id="txt_fecha_ini" class="form-control form-control-sm bg-light">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Fecha Final</label>
                  <input type="date" id="txt_fecha_fin" class="form-control form-control-sm bg-light">
                </div>
              </div>
              <div class="row mt-3">
                <div class="col-md-12 d-flex  gap-2">
                  <button type="button" class="btn btn-outline-primary btn-sm js-consultar" data-estado="T"><i class="fa-solid fa-magnifying-glass"></i> Todos</button>
                  <button type="button" class="btn btn-outline-danger btn-sm js-consultar" data-estado="P"><i class="fa-solid fa-file-circle-exclamation"></i> Pendientes</button>
                  <button type="button" class="btn btn-outline-success btn-sm js-consultar" data-estado="G"><i class="fa-solid fa-file-circle-check"></i> Aplicados</button>
                </div>
              </div>
              <div id="resultRecibos" class="mt-3"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="ModalAddDocumento" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Agregar Documento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">

          <!-- ==========================================
               COLUMNA IZQUIERDA
          ========================================== -->
          <div class="col-md-6">
            <div class="card shadow-sm border-0">
              <div class="card-header form-label text-primary fw-semibold">Valores</div>
              <div class="card-body">

                <!-- Sw / Tipo / Número -->
                <div class="mb-3">
                  <label class="form-label">Sw | Tipo | Número</label>
                  <div class="input-group"> <span class="input-group-text"><i class="fa-solid fa-flag"></i></span>
                    <input type="text" id="txtAddSw" class="form-control form-control-sm bg-light" readonly>
                    <input type="text" id="txtAddTipo" class="form-control form-control-sm bg-light" readonly>
                    <input type="text" id="txtAddNumero" class="form-control form-control-sm bg-light" readonly>
                  </div>
                </div>
                <div class="row">
                  <!-- Valor total -->
                  <div class="col-6">
                    <label class="form-label">Valor Total</label>
                    <div class="input-group"> <span class="input-group-text"><i class="fa-solid fa-dollar-sign"></i></span>
                      <input type="text" id="txtAddValorTotal" class="form-control form-control-sm bg-light" readonly>
                    </div>
                  </div>

                  <!-- IVA -->
                  <div class="col-6">
                    <label class="form-label">IVA</label>
                    <div class="input-group"> <span class="input-group-text"><i class="fa-solid fa-dollar-sign"></i></span>
                      <input type="text" id="txtAddIva" class="form-control form-control-sm bg-light" readonly>
                    </div>
                  </div>
                </div>

                <!-- Saldo -->
                <div class="mb-3">
                  <label class="form-label">Saldo</label>
                  <div class="input-group"> <span class="input-group-text"><i class="fa-solid fa-dollar-sign"></i></span>
                    <input type="text" id="txtAddValor" class="form-control form-control-sm bg-light" readonly>
                  </div>
                </div>

                <!-- Abono -->
                <div class="mb-3">
                  <label class="form-label">Abono</label>
                  <div class="input-group"> <span class="input-group-text"><i class="fa-solid fa-dollar-sign"></i></span>
                    <input type="text" id="txtAddAbono" class="form-control form-control-sm js-money" inputmode="decimal" autocomplete="off">
                    <button type="button" class="btn btn-outline-primary btn-sm" id="btnSaldoTotal"> <i class="fa-solid fa-check-double"></i> Saldo total </button>
                  </div>
                </div>

                <!-- Retenciones -->
                <div class="row g-2 mb-3">
                  <div class="col-md-4">
                    <label class="form-label">Rete IVA</label>
                    <div class="input-group"> <span class="input-group-text"><i class="fa-solid fa-dollar-sign"></i></span>
                      <input type="text" id="txtAddReteIVA" class="form-control form-control-sm bg-light" readonly>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Rete Fuente</label>
                    <div class="input-group"> <span class="input-group-text"><i class="fa-solid fa-dollar-sign"></i></span>
                      <input type="text" id="txtAddReteFuente" class="form-control form-control-sm bg-light" readonly>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Rete ICA</label>
                    <div class="input-group"> <span class="input-group-text"><i class="fa-solid fa-dollar-sign"></i></span>
                      <input type="text" id="txtAddReteICA" class="form-control form-control-sm bg-light" readonly>
                    </div>
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label">Valor Descuento</label>
                  <div class="input-group"> <span class="input-group-text"><i class="fa-solid fa-dollar-sign"></i></span>
                    <input type="text" id="txtAddDcto" class="form-control form-control-sm bg-light" readonly>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- ==========================================
               COLUMNA DERECHA
          ========================================== -->
          <div class="col-md-6">

            <!-- POSIBLES -->
            <div class="card shadow-sm border-0 mb-3">
              <div class="card-header form-label text-primary fw-semibold">Posibles</div>
              <div class="card-body">

                <!-- Rete IVA -->
                <div class="row g-2 mb-3">
                  <div class="col-md-4">
                    <label class="form-label">Rete IVA</label>
                    <select id="txtAddRIVA" class="form-select form-select-sm bg-light">
                      <option value="0">0</option>
                      <option value="15">15</option>
                      <option value="100">100</option>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Base</label>
                    <input type="text" id="txtAddIvaBase" class="form-control form-control-sm bg-light" readonly>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Valor</label>
                    <div class="input-group">
                      <input type="text" id="txtAddIvaValor" class="form-control form-control-sm bg-light" readonly>
                      <button type="button" class="btn btn-outline-primary btn-sm js-aplicar-ret" data-ret="I" title="Aplicar retención"> <i class="fa-solid fa-check-double"></i> </button>
                    </div>
                  </div>
                </div>

                <!-- Rete Fuente -->
                <div class="row g-2 mb-3">
                  <div class="col-md-4">
                    <label class="form-label">Rete Fuente</label>
                    <select id="txtAddFuente" class="form-select form-select-sm bg-light">
                      <option value="0">0</option>
                      <option value="1">1</option>
                      <option value="1.5">1.5</option>
                      <option value="2">2</option>
                      <option value="2.5">2.5</option>
                      <option value="3">3</option>
                      <option value="3.5">3.5</option>
                      <option value="4">4</option>
                      <option value="4.5">4.5</option>
                      <option value="5">5</option>
                      <option value="6">6</option>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Base</label>
                    <input type="text" id="txtAddFuenteBase" class="form-control form-control-sm bg-light" readonly>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Valor</label>
                    <div class="input-group">
                      <input type="text" id="txtAddFuenteValor" class="form-control form-control-sm bg-light" readonly>
                      <button type="button" class="btn btn-outline-primary btn-sm js-aplicar-ret" data-ret="F" title="Aplicar retención"> <i class="fa-solid fa-check-double"></i> </button>
                    </div>
                  </div>
                </div>

                <!-- Rete ICA -->
                <div class="row g-2">
                  <div class="col-md-4">
                    <label class="form-label">Rete ICA</label>
                    <input type="text" id="txtAddRICA" class="form-control form-control-sm js-decimal" inputmode="decimal" autocomplete="off">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Base</label>
                    <input type="text" id="txtAddIcaBase" class="form-control form-control-sm bg-light" readonly>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Valor</label>
                    <div class="input-group">
                      <input type="text" id="txtAddIcaValor" class="form-control form-control-sm bg-light" readonly>
                      <button type="button" class="btn btn-outline-primary btn-sm js-aplicar-ret" data-ret="C" title="Aplicar retención"> <i class="fa-solid fa-check-double"></i> </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- FINANCIERAS -->
            <div class="card shadow-sm border-0">
              <div class="card-header form-label text-primary fw-semibold">Financieras</div>
              <div class="card-body">
                <div class="row g-2 mb-3">
                  <div class="col-md-4">
                    <label class="form-label">Cond. Pago</label>
                    <input type="text" id="txtAddCondicion" class="form-control form-control-sm bg-light" readonly>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Cond. Desc</label>
                    <input type="text" id="txtAddCondicionDesc" class="form-control form-control-sm bg-light" readonly>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Días Doc</label>
                    <input type="text" id="txtAddDiasDoc" class="form-control form-control-sm bg-light" readonly>
                  </div>
                </div>
                <div class="row g-2">
                  <div class="col-md-4">
                    <label class="form-label">Días Gracia</label>
                    <input type="text" id="txtAddDiasGracia" class="form-control form-control-sm bg-light" readonly>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">% Descuento</label>
                    <input type="text" id="txtAddDescuento" class="form-control form-control-sm bg-light" readonly>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Valor Descuento</label>
                    <div class="input-group">
                      <input type="text" id="txtAddDescuentoValor" class="form-control form-control-sm js-money" inputmode="decimal">
                      <button type="button" class="btn btn-outline-primary btn-sm" id="btnAplicarDcto" title="Aplicar descuento"> <i class="fa-solid fa-check-double"></i> </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary btn-sm" id="btnAddDoc">Agregar</button>
        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal tramitar -->
<div class="modal fade" id="ModalTramitar" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Tramitar Documento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <!-- ========== DATOS CLIENTE Y DOCUMENTO ========== -->
        <div class="row g-2">
          <!-- ================= CLIENTE ================= -->
          <div class="col-12 col-lg-8">
            <div class="card shadow-sm border-0 h-100">
              <div class="card-header form-label text-primary"> <i class="fa-solid fa-user"></i> Datos del tercero </div>
              <div class="card-body">
                <input type="hidden" id="txtTramiteId" disabled />
                <!-- NIT -->
                <div class="mb-3">
                  <label class="form-label fw-bold"><i class="fa-regular fa-address-card"></i> NIT</label>
                  <input type="text" id="txtTramiteNit" class="form-control form-control-sm bg-light" readonly />
                </div>
                <div class="row ">
                  <!-- Nombres -->
                  <div class="col-6 mb-3">
                    <label class="form-label fw-bold"><i class="fa-solid fa-user"></i> Nombres</label>
                    <input type="text" id="txtTramiteNombres" class="form-control form-control-sm bg-light" readonly />
                  </div>

                  <!-- Razón Social -->
                  <div class="col-6 mb-3">
                    <label class="form-label fw-bold"><i class="fa-regular fa-building"></i> Razón Social</label>
                    <input type="text" id="txtTramiteRazon" class="form-control form-control-sm bg-light" readonly />
                  </div>
                </div>
                <!-- Notas -->
                <div class="mb-3">
                  <label class="form-label fw-bold"><i class="fa-regular fa-comment"></i> Notas</label>
                  <textarea id="txt_tramiteNotas_rc" class="form-control form-control-sm bg-light" rows="2" readonly></textarea>
                </div>
              </div>
            </div>
          </div>
          <!-- ================= DOCUMENTO ================= -->
          <div class="col-12 col-lg-4">
            <div class="card shadow-sm border-0 h-100">
              <div class="card-header form-label text-primary"> <i class="fa-solid fa-file-lines"></i> Datos del documento </div>
              <div class="card-body">

                <!-- Tipo Numero -->
                <div class="mb-3">
                  <label class="form-label fw-bold"><i class="fa-solid fa-flag"></i> Tipo / Número</label>
                  <div class="input-group">
                    <input type="hidden" id="txtTramiteSw">
                    <input type="text" id="txtTramiteTipo" class="form-control form-control-sm bg-light" readonly>
                    <input type="text" id="txtTramiteNumero" class="form-control form-control-sm bg-light" readonly>
                  </div>
                </div>

                <!-- Doc y Ref -->
                <div class="mb-3">
                  <label class="form-label fw-bold"><i class="fa-solid fa-folder-tree"></i> Doc / Ref</label>
                  <div class="input-group">
                    <input type="text" id="txtTramiteRef" class="form-control form-control-sm bg-light" readonly />
                    <input type="text" id="txtTramiteDoc" class="form-control form-control-sm bg-light" readonly />
                  </div>
                </div>
                <div class="row">
                  <!-- Usuario Crea -->
                  <div class="col-6 mb-3">
                    <label class="form-label fw-bold"><i class="fa-solid fa-user-pen"></i> Creado por</label>
                    <input type="text" id="txtTramiteUsu" class="form-control form-control-sm bg-light" readonly />
                  </div>

                  <!-- Usuario Autoriza -->
                  <div class="col-6 mb-3">
                    <label class="form-label fw-bold"><i class="fa-solid fa-user-check"></i> Autorizado por</label>
                    <input type="text" id="txtTramiteUsuAuto" class="form-control form-control-sm bg-light" readonly />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ========== DETALLES DE PAGO ========== -->
        <div class="row mt-3">
          <div class="col-md-12">
            <div class="card shadow-sm border-0">
              <div class="card-header form-label text-primary"> <i class="fa-solid fa-money-check-dollar"></i> Detalle del pago </div>
              <div class="card-body">
                <div id="resultDetalle" class="table-container"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-success m-1 btn-sm" id="btnContabilizar"> <i class="fa-regular fa-paper-plane"></i> Contabilizar </button>
        <button class="btn btn-outline-danger m-1 btn-sm" id="btnVerRecibo"> <i class="fa-regular fa-file-pdf"></i> PDF </button>
        <button class="btn btn-outline-danger m-1 btn-sm" id="btnEliminar"> <i class="fa-solid fa-trash-can"></i> Eliminar </button>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>


<?php

$scriptsPagina = [
  '../controllers/egresosPago.js?v=' . @filemtime(__DIR__ . '/../controllers/egresosPago.js')
];

include '../dependencies/footer.php';

?>