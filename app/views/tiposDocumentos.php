<?php
session_start();
$titulo = 'Tipos de documentos';
include '../dependencies/header.php';
?>
<div class="card shadow-sm border-0">
  <div class="card-body p-4">

    <!-- Encabezado -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
      <div>
        <h5 class="fw-bold mb-1"><span class="text-muted fw-light">Maestra /</span> Tipos de documentos</h5>
        <p class="text-muted small mb-0">Prefijos, numeración y comportamiento de cada tipo de documento del sistema.</p>
      </div>
    </div>

    <ul class="nav nav-tabs" role="tablist" id="tabs">
      <li class="nav-item">
        <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-creacion" aria-selected="true">
          <i class="fa-solid fa-file-circle-plus me-1"></i> <span id="lblTabFormulario">Crear tipo</span>
        </button>
      </li>
      <li class="nav-item">
        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-listado" aria-selected="false">
          <i class="fa-solid fa-list-ol me-1"></i> Tipos registrados
        </button>
      </li>
    </ul>

    <div class="tab-content pt-4 px-0">

      <!-- ===================================================== -->
      <!-- FORMULARIO -->
      <!-- ===================================================== -->
      <div class="tab-pane fade show active" id="navs-creacion" role="tabpanel">
        <form id="FormCrear" class="needs-validation" novalidate>
          <input type="hidden" id="txtId" name="id" value="">

          <!-- Identificación -->
          <div class="border rounded-3 p-3 p-md-4 mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
              <div>
                <h6 class="text-primary fw-bold mb-0">Identificación</h6>
                <small class="text-muted">Código, nombre y comportamiento (sw) del documento.</small>
              </div>
              <span class="badge bg-label-primary d-none" id="badgeModoEdicion"><i class="fa fa-pen me-1"></i>Editando</span>
            </div>
            <div class="row g-3">
              <div class="col-lg-2 col-md-4">
                <label for="txtTipo" class="form-label fw-semibold">Tipo <span class="text-danger">*</span></label>
                <input type="text" class="form-control form-control-sm text-uppercase" id="txtTipo" name="tipo"
                       maxlength="10" pattern="[A-Za-z0-9_\-]{1,10}" autocomplete="off" required placeholder="Ej. FV">
                <div class="invalid-feedback">Hasta 10 letras o números.</div>
              </div>
              <div class="col-lg-5 col-md-8">
                <label for="txtDescripcion" class="form-label fw-semibold">Descripción <span class="text-danger">*</span></label>
                <input type="text" class="form-control form-control-sm" id="txtDescripcion" name="descripcion"
                       maxlength="100" autocomplete="off" required placeholder="Ej. Factura de venta">
                <div class="invalid-feedback">Ingrese la descripción.</div>
              </div>
              <div class="col-lg-3 col-md-6">
                <label for="slcSw" class="form-label fw-semibold">Sw <span class="text-danger">*</span></label>
                <select class="form-select form-select-sm" id="slcSw" name="sw" required>
                  <option value="">Cargando...</option>
                </select>
                <small class="text-muted" id="ayudaSw">El sw no se puede cambiar después de crear el tipo.</small>
              </div>
              <div class="col-lg-2 col-md-6">
                <label for="slcInactivo" class="form-label fw-semibold">Estado</label>
                <select class="form-select form-select-sm" id="slcInactivo" name="inactivo">
                  <option value="0">Activo</option>
                  <option value="1">Inactivo</option>
                </select>
              </div>
              <div class="col-lg-4 col-md-6">
                <label for="slcAlmacen" class="form-label fw-semibold">Almacén</label>
                <select class="form-select form-select-sm" id="slcAlmacen" name="almacen" required>
                  <option value="0">Todas las bodegas</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Numeración y vigencia -->
          <div class="border rounded-3 p-3 p-md-4 mb-4">
            <div class="mb-3">
              <h6 class="text-primary fw-bold mb-0">Numeración y vigencia</h6>
              <small class="text-muted">Rango autorizado, consecutivo actual y resolución.</small>
            </div>
            <div class="row g-3">
              <div class="col-lg-2 col-md-4">
                <label for="txtRangoInicial" class="form-label fw-semibold">Rango inicial</label>
                <input type="number" min="0" class="form-control form-control-sm text-end" id="txtRangoInicial" name="rango_inicial" value="0" required>
              </div>
              <div class="col-lg-2 col-md-4">
                <label for="txtRangoFinal" class="form-label fw-semibold">Rango final</label>
                <input type="number" min="0" class="form-control form-control-sm text-end" id="txtRangoFinal" name="rango_final" value="0" required>
                <small class="text-muted">0 = sin límite</small>
              </div>
              <div class="col-lg-2 col-md-4">
                <label for="txtNumero" class="form-label fw-semibold">Número actual</label>
                <input type="number" min="0" class="form-control form-control-sm text-end" id="txtNumero" name="numero" value="0" required>
              </div>
              <div class="col-lg-2 col-md-4">
                <label for="txtResolucion" class="form-label fw-semibold">Resolución</label>
                <input type="text" class="form-control form-control-sm" id="txtResolucion" name="resolucion" value="0" maxlength="20" autocomplete="off">
              </div>
              <div class="col-lg-2 col-md-4">
                <label for="txtFechaInicio" class="form-label fw-semibold">Fecha inicio</label>
                <input type="date" class="form-control form-control-sm" id="txtFechaInicio" name="fecha_inicio">
              </div>
              <div class="col-lg-2 col-md-4">
                <label for="txtFechaFinal" class="form-label fw-semibold">Fecha final</label>
                <input type="date" class="form-control form-control-sm" id="txtFechaFinal" name="fecha_fin">
              </div>
              <div class="col-12">
                <div class="progress d-none" id="barraConsumo" style="height:6px" title="Consumo del rango autorizado">
                  <div class="progress-bar bg-primary" role="progressbar" style="width:0%"></div>
                </div>
                <small class="text-muted d-none" id="lblConsumo"></small>
              </div>
            </div>
          </div>

          <!-- Configuración -->
          <div class="border rounded-3 p-3 p-md-4 mb-4">
            <div class="mb-3">
              <h6 class="text-primary fw-bold mb-0">Configuración</h6>
              <small class="text-muted">Opciones que cambian el tratamiento del documento.</small>
            </div>
            <div class="row g-3">
              <div class="col-lg-4">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="documento_electronico">
                  <label class="form-check-label fw-semibold" for="documento_electronico">Documento electrónico</label>
                  <div class="small text-muted">Se transmite a la DIAN (factura, nota o documento electrónico).</div>
                </div>
              </div>
              <div class="col-lg-4 opcion-sw d-none" id="contenedor_documento_soporte">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="documento_soporte">
                  <label class="form-check-label fw-semibold" for="documento_soporte">Documento soporte</label>
                  <div class="small text-muted">Compras a no obligados a facturar (solo sw 14).</div>
                </div>
              </div>
              <div class="col-lg-4 opcion-sw d-none" id="contenedor_nomina">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="nomina">
                  <label class="form-check-label fw-semibold" for="nomina">Documento de nómina</label>
                  <div class="small text-muted" id="ayudaNomina"></div>
                </div>
              </div>
            </div>
            <div class="row g-3 mt-1">
              <div class="col-md-6">
                <label for="txtLeyenda1" class="form-label fw-semibold">Leyenda 1</label>
                <textarea class="form-control form-control-sm" id="txtLeyenda1" name="leyenda1" maxlength="255" rows="2"
                          placeholder="Texto que se imprime en el documento"></textarea>
              </div>
              <div class="col-md-6">
                <label for="txtLeyenda2" class="form-label fw-semibold">Leyenda 2</label>
                <textarea class="form-control form-control-sm" id="txtLeyenda2" name="leyenda2" maxlength="255" rows="2"></textarea>
              </div>
            </div>
          </div>

          <div class="d-flex flex-column flex-sm-row justify-content-end gap-2">
            <button class="btn btn-sm btn-outline-primary" type="button" id="btnConceptosRet" disabled
                    title="Guarde o seleccione un tipo de documento para configurar sus retenciones">
              <i class="fa fa-percent me-1"></i> Conceptos de retención
            </button>
            <button class="btn btn-sm btn-outline-secondary" type="button" id="btnNuevo">
              <i class="fa fa-file me-1"></i> Nuevo
            </button>
            <button class="btn btn-sm btn-primary" type="submit" id="btnGuardar">
              <i class="fa fa-save me-1"></i> Guardar
            </button>
          </div>
        </form>
      </div>

      <!-- ===================================================== -->
      <!-- LISTADO -->
      <!-- ===================================================== -->
      <div class="tab-pane fade" id="navs-listado" role="tabpanel">
        <div class="border rounded-3">
          <div class="p-3 border-bottom">
            <div class="row g-2 align-items-center">
              <div class="col-lg-5">
                <h6 class="fw-bold mb-0">Tipos registrados</h6>
                <small class="text-muted">Use los botones de cada fila para editar, eliminar o ver el detalle <i class="fa fa-info-circle"></i>.</small>
              </div>
              <div class="col-lg-7">
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-white"><i class="bx bx-search text-muted"></i></span>
                  <input id="filtro" type="text" class="form-control" placeholder="Tipo, descripción o número de sw..." autocomplete="off">
                  <button class="btn btn-outline-secondary" type="button" id="btnLimpiarFiltro" title="Limpiar"><i class="bx bx-x"></i></button>
                  <button class="btn btn-primary" type="button" id="buscar"><i class="bx bx-search me-1"></i>Buscar</button>
                </div>
              </div>
            </div>
          </div>
          <div class="table-responsive text-nowrap p-2">
            <div id="result"></div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Modal información -->
<div class="modal fade" id="modalInfo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fs-6 fw-bold">Información del tipo de documento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <table class="table table-sm mb-0 small" id="tablaInfo"><tbody></tbody></table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal conceptos de retención -->
<div class="modal fade" id="retencionModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title fs-6 fw-bold mb-0">Conceptos de retención</h5>
          <small class="text-muted"><span id="retTipoDoc"></span> · <span id="retDescDoc"></span></small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="border rounded-3 p-3 mb-3">
          <div class="row g-3">
            <div class="col-lg-4">
              <label class="form-label fw-semibold" for="retTxtConcepto">Concepto <span class="text-danger">*</span></label>
              <input type="text" class="form-control form-control-sm" id="retTxtConcepto" maxlength="100" autocomplete="off" placeholder="Ej. Compras generales declarantes">
            </div>
            <div class="col-lg-8">
              <div class="row g-2">
                <div class="col-md-4">
                  <label class="form-label fw-semibold" for="retCuentaReteFuente">Cuenta retefuente</label>
                  <select class="form-select form-select-sm form-select2" id="retCuentaReteFuente" data-placeholder="Cuenta"></select>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-semibold" for="retCuentaReteICA">Cuenta reteICA</label>
                  <select class="form-select form-select-sm form-select2" id="retCuentaReteICA" data-placeholder="Cuenta"></select>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-semibold" for="retCuentaReteIVA">Cuenta reteIVA</label>
                  <select class="form-select form-select-sm form-select2" id="retCuentaReteIVA" data-placeholder="Cuenta"></select>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="row g-2">
                <div class="col-6"><label class="form-label small mb-1" for="retBaseReteFuente">Base retefuente ($)</label>
                  <input type="number" min="0" step="0.01" class="form-control form-control-sm text-end" id="retBaseReteFuente"></div>
                <div class="col-6"><label class="form-label small mb-1" for="retPcjReteFuente">% retefuente</label>
                  <input type="number" min="0" max="100" step="0.001" class="form-control form-control-sm text-end" id="retPcjReteFuente"></div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="row g-2">
                <div class="col-6"><label class="form-label small mb-1" for="retBaseReteICA">Base reteICA ($)</label>
                  <input type="number" min="0" step="0.01" class="form-control form-control-sm text-end" id="retBaseReteICA"></div>
                <div class="col-6"><label class="form-label small mb-1" for="retPcjReteICA">% reteICA</label>
                  <input type="number" min="0" max="100" step="0.001" class="form-control form-control-sm text-end" id="retPcjReteICA"></div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="row g-2">
                <div class="col-6"><label class="form-label small mb-1" for="retBaseReteIVA">Base reteIVA ($)</label>
                  <input type="number" min="0" step="0.01" class="form-control form-control-sm text-end" id="retBaseReteIVA"></div>
                <div class="col-6"><label class="form-label small mb-1" for="retPcjReteIVA">% reteIVA</label>
                  <input type="number" min="0" max="100" step="0.001" class="form-control form-control-sm text-end" id="retPcjReteIVA"></div>
              </div>
            </div>
          </div>
          <div class="d-flex justify-content-end gap-2 mt-3">
            <button type="button" id="btnBorrar" class="btn btn-sm btn-outline-danger" disabled><i class="fa-solid fa-trash-can me-1"></i>Eliminar</button>
            <button type="button" id="btnLimpiarRet" class="btn btn-sm btn-outline-secondary"><i class="fa-regular fa-file me-1"></i>Nuevo</button>
            <button type="button" id="btnAdicionar" class="btn btn-sm btn-primary"><i class="fa-regular fa-floppy-disk me-1"></i><span id="lblBtnRet">Agregar</span></button>
          </div>
        </div>

        <div class="table-responsive text-nowrap">
          <table id="tablaRetenciones" class="table table-sm table-hover align-middle mb-0">
            <thead class="table-primary">
              <tr>
                <th>Concepto</th>
                <th>Retefuente</th>
                <th class="text-end">Base</th>
                <th class="text-end">%</th>
                <th>ReteICA</th>
                <th class="text-end">Base</th>
                <th class="text-end">%</th>
                <th>ReteIVA</th>
                <th class="text-end">Base</th>
                <th class="text-end">%</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
        <small class="text-muted">Clic en una fila para editarla.</small>
      </div>
      <div class="modal-footer">
        <button class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<?php
$scriptsPagina = [
  '../controllers/tiposDocumentos.js?' . rand()
];
include '../dependencies/footer.php';
?>
