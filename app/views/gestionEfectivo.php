<?php
session_start();

$titulo = 'Gestión de efectivo';
include '../dependencies/header.php';
?>
<div class="card">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">POS / </span> Gestión de efectivo</h5>

    <div class="nav-align-top">
      <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item">
          <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-creacion" aria-controls="navs-creacion" aria-selected="true">
            <i class="fa-solid fa-money-bill-transfer"></i>&nbsp;Proceso
          </button>
        </li>
        <li class="nav-item">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-listado" aria-controls="navs-listado" aria-selected="false" id="tab-historial">
            <i class="fa-solid fa-clock-rotate-left"></i>&nbsp;Historial
          </button>
        </li>
      </ul>

      <div class="tab-content">

        <!-- =========================================================
             PROCESO
        ========================================================== -->
        <div class="tab-pane fade show active" id="navs-creacion" role="tabpanel">

          <!-- Indicadores de la caja -->
          <div class="row g-2 mb-3">
            <div class="col-12 col-md-4">
              <div class="card shadow-none border h-100">
                <div class="card-body p-2 px-3 d-flex align-items-center gap-2">
                  <div class="avatar avatar-sm flex-shrink-0">
                    <span class="avatar-initial rounded bg-label-primary"><i class="fa-solid fa-cash-register"></i></span>
                  </div>
                  <div class="flex-grow-1">
                    <small class="text-muted d-block lh-1">Saldo en caja</small>
                    <span class="fw-bold text-primary" id="saldo-caja-txt">$0</span>
                  </div>
                  <button type="button" class="btn btn-icon btn-xs btn-outline-primary rounded-pill" id="btn-refrescar-saldo" title="Actualizar saldo">
                    <i class="fa-solid fa-rotate"></i>
                  </button>
                </div>
              </div>
            </div>
            <div class="col-6 col-md-4">
              <div class="card shadow-none border h-100">
                <div class="card-body p-2 px-3 d-flex align-items-center gap-2">
                  <div class="avatar avatar-sm flex-shrink-0 d-none d-sm-block">
                    <span class="avatar-initial rounded bg-label-secondary"><i class="fa-solid fa-user"></i></span>
                  </div>
                  <div class="text-truncate">
                    <small class="text-muted d-block lh-1">Usuario</small>
                    <small class="fw-semibold text-uppercase" id="usuario">-</small>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-6 col-md-4">
              <div class="card shadow-none border h-100">
                <div class="card-body p-2 px-3 d-flex align-items-center gap-2">
                  <div class="avatar avatar-sm flex-shrink-0 d-none d-sm-block">
                    <span class="avatar-initial rounded bg-label-secondary"><i class="fa-solid fa-store"></i></span>
                  </div>
                  <div class="text-truncate">
                    <small class="text-muted d-block lh-1">Almacén</small>
                    <small class="fw-semibold" id="bodega">-</small>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <form id="FormCrear" class="needs-validation" novalidate autocomplete="off">
            <input type="hidden" id="id_usuario" name="id_usuario" required>
            <input type="hidden" id="id_bodega" name="id_bodega" required>

            <div class="row g-3">
              <!-- ===================== FORMULARIO ===================== -->
              <div class="col-lg-8">
                <div class="card shadow-none border">
                  <div class="card-body p-3">

                    <!-- 1. Tipo de movimiento -->
                    <small class="text-muted text-uppercase fw-semibold d-block mb-2">1. Tipo de movimiento</small>
                    <div class="row g-2 mb-3">
                      <div class="col-6">
                        <input type="radio" class="btn-check" name="btnradio" id="btnradio1" value="INGRESO" checked>
                        <label class="btn btn-sm btn-outline-success w-100 h-100 py-2 d-flex align-items-center justify-content-center justify-content-sm-start gap-2 text-start" for="btnradio1">
                          <i class="fa-solid fa-circle-arrow-down fs-5"></i>
                          <span>
                            <span class="d-block fw-semibold">Ingreso</span>
                            <small class="d-none d-sm-block fw-normal">Entrada de dinero</small>
                          </span>
                        </label>
                      </div>
                      <div class="col-6">
                        <input type="radio" class="btn-check" name="btnradio" id="btnradio2" value="EGRESO">
                        <label class="btn btn-sm btn-outline-danger w-100 h-100 py-2 d-flex align-items-center justify-content-center justify-content-sm-start gap-2 text-start" for="btnradio2">
                          <i class="fa-solid fa-circle-arrow-up fs-5"></i>
                          <span>
                            <span class="d-block fw-semibold">Egreso</span>
                            <small class="d-none d-sm-block fw-normal">Salida de dinero</small>
                            <small class="fw-normal d-none" id="egreso-sin-saldo">Sin saldo disponible</small>
                          </span>
                        </label>
                      </div>
                    </div>

                    <!-- 2. Datos del documento -->
                    <small class="text-muted text-uppercase fw-semibold d-block mb-2">2. Datos del documento</small>
                    <div class="row g-2 mb-3">
                      <div class="col-md-6">
                        <label class="form-label mb-1" for="id_tipo_documento">Tipo de documento</label>
                        <select class="form-select form-select-sm" id="id_tipo_documento" name="id_tipo_documento" required></select>
                        <div class="invalid-feedback">Seleccione el tipo de documento.</div>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label mb-1" for="id_concepto_caja">Concepto de caja</label>
                        <select class="form-select form-select-sm" id="id_concepto_caja" name="id_concepto_caja" required>
                          <option value="">Seleccione un concepto</option>
                        </select>
                        <div class="invalid-feedback">Seleccione el concepto.</div>
                      </div>
                      <div class="col-12">
                        <label class="form-label mb-1" for="txtBusquedaTercero">Tercero</label>
                        <div class="input-group input-group-sm input-group-merge" id="grp-busqueda-tercero">
                          <span class="input-group-text"><i class="bx bx-search"></i></span>
                          <input type="search" class="form-control" id="txtBusquedaTercero"
                            placeholder="Buscar por nombre, razón social o NIT" enterkeyhint="search">
                        </div>
                        <div class="alert alert-primary d-none align-items-center justify-content-between gap-2 mb-0 py-1 px-2" id="tercero-seleccionado">
                          <div class="d-flex align-items-center gap-2 text-truncate small">
                            <i class="fa-solid fa-user-check"></i>
                            <div class="text-truncate">
                              <span class="fw-semibold d-block text-truncate lh-sm" id="tercero-nombre"></span>
                              <small id="tercero-nit"></small>
                            </div>
                          </div>
                          <button type="button" class="btn btn-xs btn-outline-primary flex-shrink-0" id="btn-quitar-tercero">
                            Cambiar
                          </button>
                        </div>
                        <input type="hidden" id="id_tercero" name="id_tercero">
                        <input type="hidden" id="id_direccion" name="id_direccion">
                        <input type="hidden" id="id_condicion" name="id_condicion">
                        <input type="hidden" id="zona_ventas" name="zona_ventas">
                        <input type="hidden" id="zona_descripcion" name="zona_descripcion">
                      </div>
                    </div>

                    <!-- Pago de documentos pendientes (solo egresos, opcional) -->
                    <div class="d-none mb-3" id="seccion-documentos">
                      <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                        <small class="text-muted text-uppercase fw-semibold">
                          Pagar documentos pendientes <span class="text-lowercase fw-normal">(opcional)</span>
                        </small>
                        <div class="form-check form-switch mb-0">
                          <input class="form-check-input" type="checkbox" id="chk-pagar-documentos">
                          <label class="form-check-label small" for="chk-pagar-documentos">Aplicar</label>
                        </div>
                      </div>

                      <div class="d-none" id="panel-documentos">
                        <div class="d-flex align-items-center gap-2 mb-2">
                          <div class="input-group input-group-sm input-group-merge flex-grow-1">
                            <span class="input-group-text"><i class="bx bx-search"></i></span>
                            <input type="search" class="form-control" id="filtro-documentos" placeholder="Filtrar por tipo o número">
                          </div>
                          <span class="badge bg-label-primary flex-shrink-0" id="contador-documentos">0 pendientes</span>
                        </div>
                        <!-- Alto máximo con scroll interno y encabezado fijo -->
                        <div class="table-responsive border rounded mb-2" style="max-height: 260px; overflow-y: auto;">
                          <table class="table table-sm table-hover align-middle mb-0 small" id="tabla-documentos">
                            <thead class="table-primary sticky-top" style="z-index: 2;">
                              <tr>
                                <th style="width: 32px;">
                                  <input class="form-check-input" type="checkbox" id="chk-todos-docs" title="Seleccionar todos">
                                </th>
                                <th>Documento</th>
                                <th class="d-none d-md-table-cell">Fecha</th>
                                <th class="text-end d-none d-sm-table-cell">Valor</th>
                                <th class="text-end">Saldo</th>
                                <th class="text-end" style="width: 130px;">A pagar</th>
                              </tr>
                            </thead>
                            <tbody></tbody>
                          </table>
                        </div>
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 small">
                          <span>Total a aplicar: <b class="text-primary" id="total-aplicado">$0</b></span>
                          <button type="button" class="btn btn-xs btn-outline-primary" id="btn-usar-aplicado">
                            <i class="fa-solid fa-arrow-down me-1"></i> Usar como valor del egreso
                          </button>
                        </div>
                        <div class="alert alert-warning small py-1 px-2 mt-2 mb-0 d-none" id="msg-diferencia-docs"></div>
                      </div>
                    </div>

                    <!-- 3. Valores -->
                    <small class="text-muted text-uppercase fw-semibold d-block mb-2">3. Valores</small>
                    <div class="row g-2 mb-2">
                      <div class="col-12 col-md-5">
                        <select class="form-select form-select-sm" id="cuenta_caja" aria-label="Caja"></select>
                      </div>
                      <div class="col-9 col-md-5">
                        <div class="input-group input-group-sm input-group-merge">
                          <span class="input-group-text">$</span>
                          <input type="number" class="form-control" id="cant_efectivo" min="0" step="any"
                            inputmode="decimal" enterkeyhint="done" placeholder="0">
                        </div>
                      </div>
                      <div class="col-3 col-md-2 d-grid">
                        <button type="button" class="btn btn-sm btn-primary" id="btn-add-caja" title="Agregar (Enter)">
                          <i class="fa-solid fa-plus"></i><span class="d-none d-md-inline ms-1">Agregar</span>
                        </button>
                      </div>
                    </div>
                    <div class="d-flex flex-wrap gap-1 mb-2" id="montos-rapidos">
                      <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill" data-monto="5000">+ 5.000</button>
                      <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill" data-monto="10000">+ 10.000</button>
                      <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill" data-monto="20000">+ 20.000</button>
                      <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill" data-monto="50000">+ 50.000</button>
                      <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill" data-monto="100000">+ 100.000</button>
                    </div>

                    <div class="table-responsive">
                      <table class="table table-sm table-hover align-middle mb-0" id="tabla-cajas">
                        <thead class="table-primary">
                          <tr>
                            <th>Caja</th>
                            <th class="text-end" style="width: 140px;">Valor</th>
                            <th class="text-center" style="width: 50px;"></th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr id="cajas-vacio">
                            <td colspan="3" class="text-center text-muted small py-3">
                              <i class="fa-solid fa-coins fs-5 d-block mb-1 opacity-50"></i>
                              Seleccione la caja, digite el valor y presione <b>Agregar</b>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ===================== RESUMEN ===================== -->
              <div class="col-lg-4">
                <div class="card shadow-none border sticky-lg-top" style="top: 6rem; z-index: 1;">
                  <div class="card-header p-3 pb-2 d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 fw-semibold">Resumen</h6>
                    <span class="badge bg-label-success" id="res-movimiento">Ingreso</span>
                  </div>
                  <div class="card-body p-3 pt-1">
                    <ul class="list-unstyled small mb-2">
                      <li class="d-flex justify-content-between gap-3 mb-1">
                        <span class="text-muted">Documento</span>
                        <span class="fw-semibold text-end text-truncate" id="res-documento">—</span>
                      </li>
                      <li class="d-flex justify-content-between gap-3 mb-1">
                        <span class="text-muted">Concepto</span>
                        <span class="fw-semibold text-end text-truncate" id="res-concepto">—</span>
                      </li>
                      <li class="d-flex justify-content-between gap-3 mb-1">
                        <span class="text-muted">Tercero</span>
                        <span class="fw-semibold text-end text-truncate" id="res-tercero">—</span>
                      </li>
                      <li class="d-flex justify-content-between gap-3 mb-1 d-none" id="li-res-documentos">
                        <span class="text-muted">Documentos</span>
                        <span class="fw-semibold text-end" id="res-documentos">—</span>
                      </li>
                      <li class="d-flex justify-content-between gap-3">
                        <span class="text-muted">Cajas</span>
                        <span class="fw-semibold" id="res-cajas">0</span>
                      </li>
                    </ul>

                    <label class="form-label mb-1" for="observaciones">Observaciones</label>
                    <textarea class="form-control form-control-sm text-uppercase" id="observaciones" name="observaciones"
                      rows="2" maxlength="250" placeholder="Describa el motivo del movimiento" required></textarea>
                    <div class="invalid-feedback">Ingrese una observación.</div>
                    <small class="text-muted d-block text-end mb-2"><span id="obs-contador">0</span>/250</small>

                    <div class="bg-lighter rounded p-2 px-3 mb-2 small">
                      <span class="text-muted d-block">Total del movimiento</span>
                      <span class="fs-5 fw-bold text-primary d-block mb-1 total-movimiento">$0</span>
                      <div class="d-flex justify-content-between">
                        <span class="text-muted">Saldo actual</span>
                        <span id="saldo-actual">$0</span>
                      </div>
                      <div class="d-flex justify-content-between">
                        <span class="text-muted">Saldo después</span>
                        <span class="fw-semibold" id="saldo-despues">$0</span>
                      </div>
                    </div>

                    <div class="alert alert-danger small py-1 px-2 mb-2 d-none" id="msg-saldo-insuf">
                      <i class="fa-solid fa-circle-exclamation me-1"></i>El egreso supera el saldo en caja.
                    </div>

                    <div class="d-none d-lg-grid gap-2">
                      <button type="submit" class="btn btn-sm btn-primary btn-guardar">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Guardar movimiento
                      </button>
                      <button type="button" class="btn btn-sm btn-outline-secondary btn-limpiar">
                        <i class="fa-solid fa-eraser me-1"></i> Limpiar
                      </button>
                      <small class="text-muted text-center">Enter agrega el valor · F9 guarda</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Barra de acciones fija (solo móvil / tablet) -->
            <div class="d-lg-none" style="height: 64px;"></div>
            <div class="d-lg-none position-fixed bottom-0 start-0 end-0 bg-body border-top shadow-lg px-3 py-2" style="z-index: 1030;">
              <div class="d-flex align-items-center gap-2">
                <div class="flex-grow-1">
                  <small class="text-muted d-block lh-1">Total</small>
                  <span class="fw-bold text-primary total-movimiento">$0</span>
                </div>
                <button type="button" class="btn btn-sm btn-icon btn-outline-secondary btn-limpiar" title="Limpiar">
                  <i class="fa-solid fa-eraser"></i>
                </button>
                <button type="submit" class="btn btn-sm btn-primary btn-guardar px-3">
                  <i class="fa-solid fa-floppy-disk me-1"></i> Guardar
                </button>
              </div>
            </div>
          </form>
        </div>

        <!-- =========================================================
             HISTORIAL
        ========================================================== -->
        <div class="tab-pane fade" id="navs-listado" role="tabpanel">

          <!-- Filtros -->
          <div class="row g-2 align-items-end mb-3">
            <div class="col-6 col-md-3">
              <label class="form-label mb-1" for="fecha_inicial">Desde</label>
              <input id="fecha_inicial" type="date" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-3">
              <label class="form-label mb-1" for="fecha_final">Hasta</label>
              <input id="fecha_final" type="date" class="form-control form-control-sm">
            </div>
            <div class="col-7 col-md-3">
              <label class="form-label mb-1" for="filtro_tipo">Movimiento</label>
              <select id="filtro_tipo" class="form-select form-select-sm">
                <option value="">Todos</option>
                <option value="10">Ingresos</option>
                <option value="9">Egresos</option>
              </select>
            </div>
            <div class="col-5 col-md-3 d-grid">
              <button type="button" class="btn btn-sm btn-primary" id="btn-buscar">
                <i class="fa-solid fa-magnifying-glass me-1"></i> Buscar
              </button>
            </div>
          </div>

          <!-- Resumen del historial -->
          <div class="row g-2 mb-3 d-none" id="kpis-historial">
            <div class="col-6 col-xl-3">
              <div class="card shadow-none border h-100">
                <div class="card-body p-2 px-3 d-flex align-items-center gap-2">
                  <div class="avatar avatar-sm flex-shrink-0">
                    <span class="avatar-initial rounded bg-label-success"><i class="fa-solid fa-arrow-down"></i></span>
                  </div>
                  <div class="text-truncate">
                    <small class="text-muted d-block lh-1">Ingresos · <span id="kpi-cant-ingresos">0 mov.</span></small>
                    <span class="fw-bold text-success" id="kpi-ingresos">$0</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-6 col-xl-3">
              <div class="card shadow-none border h-100">
                <div class="card-body p-2 px-3 d-flex align-items-center gap-2">
                  <div class="avatar avatar-sm flex-shrink-0">
                    <span class="avatar-initial rounded bg-label-danger"><i class="fa-solid fa-arrow-up"></i></span>
                  </div>
                  <div class="text-truncate">
                    <small class="text-muted d-block lh-1">Egresos · <span id="kpi-cant-egresos">0 mov.</span></small>
                    <span class="fw-bold text-danger" id="kpi-egresos">$0</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-6 col-xl-3">
              <div class="card shadow-none border h-100">
                <div class="card-body p-2 px-3 d-flex align-items-center gap-2">
                  <div class="avatar avatar-sm flex-shrink-0">
                    <span class="avatar-initial rounded bg-label-primary"><i class="fa-solid fa-scale-balanced"></i></span>
                  </div>
                  <div class="text-truncate">
                    <small class="text-muted d-block lh-1">Neto</small>
                    <span class="fw-bold text-primary" id="kpi-neto">$0</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-6 col-xl-3">
              <div class="card shadow-none border h-100">
                <div class="card-body p-2 px-3 d-flex align-items-center gap-2">
                  <div class="avatar avatar-sm flex-shrink-0">
                    <span class="avatar-initial rounded bg-label-secondary"><i class="fa-solid fa-receipt"></i></span>
                  </div>
                  <div class="text-truncate">
                    <small class="text-muted d-block lh-1">Movimientos</small>
                    <span class="fw-bold" id="kpi-cantidad">0</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Resultados -->
          <div class="d-flex align-items-center justify-content-between mb-2">
            <small class="text-muted text-uppercase fw-semibold">Movimientos</small>
            <span class="badge bg-label-primary d-none" id="badge-resultados">0</span>
          </div>
          <div id="result">
            <div class="text-center text-muted small py-4 border rounded">
              <i class="fa-solid fa-magnifying-glass fs-5 d-block mb-1 opacity-50"></i>
              Seleccione un rango de fechas y presione <b>Buscar</b>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$scriptsPagina = [
  '../controllers/gestionEfectivo.js?' . rand()
];

include '../dependencies/footer.php';
?>