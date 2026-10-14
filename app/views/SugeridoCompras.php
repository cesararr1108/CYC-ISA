<?php
session_start();

$titulo = 'Sugerido de compras';
include '../dependencies/header.php';
?>
  <input type="hidden" id="nombres_usuario" value="<?php echo isset($_SESSION["Ses_nombres"]) ? $_SESSION["Ses_nombres"] . ' ' . $_SESSION["Ses_apellidos"] : ''; ?>" />
  <input type="hidden" id="bodega_session" value="<?php echo isset($_SESSION['Ses_id_bodega']) ? $_SESSION['Ses_id_bodega'] : ''; ?>">
  <input type="hidden" id="bodega_descripcion" value="<?php echo isset($_SESSION['Ses_bodega_des']) ? $_SESSION['Ses_bodega_des'] : ''; ?>">
  <input type="hidden" id="id_usuario" value="<?php echo isset($_SESSION["Ses_id"]) ? $_SESSION["Ses_id"] : ''; ?>" />
  <div class="card">
    <div class="card-body">
      <h5 class="fw-bold py-0 mb-3">
        <span class="text-muted fw-light">Inventarios / </span>Sugerido de Compras
      </h5>

      <!-- Tabs -->
      <div class="nav-align-top">
        <ul class="nav nav-tabs" role="tablist" id="tabs">
          <li class="nav-item">
            <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-filtros"
              aria-controls="navs-filtros" aria-selected="true">
              <i class="fa-solid fa-filter"></i> &nbsp;Filtros
            </button>
          </li>
          <li class="nav-item">
            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-resultados"
              aria-controls="navs-resultados" aria-selected="false">
              <i class="fa-solid fa-list"></i> &nbsp;Resultados
            </button>
          </li>
          <!--<li class="nav-item">
            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-listados-precios"
              aria-controls="navs-listados-precios" aria-selected="false">
              <i class="fa-solid fa-list"></i> &nbsp;Listados de Precios
            </button>
          </li>-->
        </ul>

        <div class="tab-content">
          <!-- Tab Filtros  -->
          <div class="tab-pane fade show active" id="navs-filtros" role="tabpanel">
            <form>

              <div class="row">
                <div class="col-12">
                  <div class="card border shadow-sm h-100">
                    <div class="card-header py-2 px-3 d-flex justify-content-between align-items-center">
                      <span class="fw-semibold"><i class="fa-solid fa-warehouse me-2"></i>Bodegas <span
                          class="text-warning">*</span></span>
                      <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="chk_seleccionar_todas_bodegas">
                        <label class="form-check-label" for="chk_seleccionar_todas_bodegas">Seleccionar todas</label>
                      </div>
                    </div>
                    <div class="card-body py-3 px-3">
                      <select id="filtro_bodegas" class="form-select form-select-sm" multiple="multiple"
                        style="width:100%;">
                        <option value="">Seleccionar bodegas...</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>
              <div class="row g-2 mt-1">

                <!-- Columna izquierda: Bodegas  -->
                <div class="col-12 col-lg-4">
                  <!-- Card: Bodegas -->
                  <div class="card border shadow-sm h-100">

                    <div class="card-body py-3 px-3">
                      <div class="row mt-2">
                        <div class="col">
                          <span class="fw-semibold"><i class="fa-solid fa-user-tie me-2"></i>Proveedor <span
                              class="text-warning">*</span></span>
                          <input id="filtro_proveedores" class="form-control form-control-sm">
                          <input id="id_tercero" type="hidden" />
                          <input id="id_condicion_pago" type="hidden" />
                          <input id="id_tercero_dir" type="hidden" />
                          <label>Nit</label>
                          <input type="text" id="nit" class="form-control form-control-sm" readonly>
                          <label>Tratamiento</label>
                          <input type="text" id="tratamiento" class="form-control form-control-sm" readonly>
                          <label>Razón Comercial</label>
                          <input type="text" id="razon_comercial" class="form-control form-control-sm" readonly>

                          <label>Condición de pago </label>
                          <input type="text" id="condicion_pago" class="form-control form-control-sm" readonly>
                          <!--  select de tipos de documentos -->
                          <label for="tipo_documento" class="form-label fw-semibold mb-1">Tipo de Documento</label>
                          <select id="tipo_documento" class="form-select form-select-sm">
                            <option value="">Seleccionar...</option>
                          </select>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- Columna derecha: Categorías, Parámetros, Opciones y Botones -->
                <div class="col-12 col-lg-8">
                  <div class="row g-2">

                    <!-- Card: Categorías -->
                    <div class="col-12 col-md-6">
                      <div class="card border shadow-sm h-100">
                        <div class="card-header py-2 px-3">
                          <span class="fw-semibold"><i class="fa-solid fa-tags me-2"></i>Categorías</span>
                        </div>
                        <div class="card-body py-3 px-3">
                          <div class="row g-2">
                            <div class="col-md-6">
                              <label for="filtro_categoria1" class="form-label fw-semibold mb-1">Categoría 1</label>
                              <select id="filtro_categoria1" class="form-select form-select-sm">
                                <option value="">Todas</option>
                              </select>
                            </div>
                            <div class="col-md-6">
                              <label for="filtro_categoria2" class="form-label fw-semibold mb-1">Categoría 2</label>
                              <select id="filtro_categoria2" class="form-select form-select-sm">
                                <option value="">Todas</option>
                              </select>
                            </div>
                            <div class="col-md-6">
                              <label for="filtro_categoria3" class="form-label fw-semibold mb-1">Categoría 3</label>
                              <select id="filtro_categoria3" class="form-select form-select-sm">
                                <option value="">Todas</option>
                              </select>
                            </div>
                            <div class="col-md-6">
                              <label for="filtro_categoria4" class="form-label fw-semibold mb-1">Categoría 4</label>
                              <select id="filtro_categoria4" class="form-select form-select-sm">
                                <option value="">Todas</option>
                              </select>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Card: Parámetros -->
                    <div class="col-12 col-md-6">
                      <div class="card border shadow-sm h-100">
                        <div class="card-header py-2 px-3">
                          <span class="fw-semibold"><i class="fa-solid fa-sliders me-2"></i>Parámetros</span>
                        </div>
                        <div class="card-body py-3 px-3">
                          <div class="row g-2">
                            <div class="col-6">
                              <label for="filtro_marca" class="form-label fw-semibold mb-1">Marca</label>
                              <select id="filtro_marca" class="form-select form-select-sm">
                                <option value="">Todas</option>
                              </select>
                            </div>
                            <div class="col-6">
                              <label for="filtro_meses" class="form-label fw-semibold mb-1">Período (Meses)</label>
                              <select id="filtro_meses" class="form-select form-select-sm">
                                <option value="1">1 mes</option>
                                <option value="2">2 meses</option>
                                <option value="3" selected>3 meses</option>
                                <option value="4">4 meses</option>
                                <option value="6">6 meses</option>
                                <option value="12">12 meses</option>
                              </select>
                            </div>
                            <div class="col-6">
                              <label for="filtro_mes_actual" class="form-label fw-semibold mb-1">Incluir mes actual</label>
                              <select id="filtro_mes_actual" class="form-select form-select-sm">
                                <option value="1" selected>Sí</option>
                                <option value="0">No</option>
                              </select>
                            </div>
                            <div class="col-6">
                              <label for="filtro_dias" class="form-label fw-semibold mb-1">Días de Inventario</label>
                              <select id="filtro_dias" class="form-select form-select-sm">
                                <option value="7">7 días</option>
                                <option value="15">15 días</option>
                                <option value="30" selected>30 días</option>
                                <option value="45">45 días</option>
                                <option value="60">60 días</option>
                                <option value="90">90 días</option>
                                <option value="120">120 días</option>
                              </select>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Card: Opciones adicionales  -->
                    <div class="col-12">
                      <div class="card border shadow-sm">
                        <div class="card-header py-2 px-3">
                          <span class="fw-semibold"><i class="fa-solid fa-filter me-2"></i>Opciones adicionales</span>
                        </div>
                        <div class="card-body py-3 px-3">
                          <div class="d-flex flex-wrap gap-4">
                            <div class="form-check">
                              <input class="form-check-input" type="checkbox" id="filtro_solo_rotacion" value="1">
                              <label class="form-check-label" for="filtro_solo_rotacion">Solo productos con
                                rotación</label>
                            </div>
                            <div class="form-check">
                              <input class="form-check-input" type="checkbox" id="filtro_solo_sugerir" value="1">
                              <label class="form-check-label" for="filtro_solo_sugerir">Solo productos a sugerir
                                compra</label>
                            </div>
                            <div class="form-check">
                              <input class="form-check-input" type="checkbox" id="filtro_agrurar_productos" value="1">
                              <label class="form-check-label" for="filtro_agrurar_productos">Agrupar por referencia <br><span class="text-primary">No tiene encuenta las bodegas</span></label>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Botones -->
                    <div class="col-12 d-flex justify-content-end gap-2">
                      <button type="reset" class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-undo"></i> Limpiar
                      </button>
                      <button type="button" class="btn btn-sm btn-outline-primary" id="btnGenerarSugeridoBottom">
                        <i class="fa-solid fa-play"></i> Generar Sugerido
                      </button>
                    </div>

                  </div>
                </div>

              </div>
            </form>
          </div>
          <!-- Tab Resultados  -->
          <div class="tab-pane fade" id="navs-resultados" role="tabpanel">

            <!-- Card de Totales y Acciones - ESTÁTICO EN LA PARTE SUPERIOR -->
            <div class="card mb-4" id="cardTotalesAcciones2"
              style="position: sticky; top: 0; z-index: 100; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
              <div class="card-body">
                <div class="row g-3 mb-0">
                  <div class="col-md-3">
                    <label class="form-label fw-semibold">Cantidad de Items</label>
                    <div class="input-group input-group-sm">
                      <input type="text" class="form-control form-control-sm fw-bold" id="resumen_items" readonly
                        style="background-color: #f5f5f5;">
                      <span class="input-group-text">items</span>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label fw-semibold">Cantidad de Productos</label>
                    <div class="input-group input-group-sm">
                      <input type="text" class="form-control form-control-sm fw-bold" id="resumen_productos" readonly
                        style="background-color: #f5f5f5;">
                      <span class="input-group-text">unidades</span>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label fw-semibold">Costo Total</label>
                    <div class="input-group input-group-sm">
                      <span class="input-group-text">$</span>
                      <input type="text" class="form-control form-control-sm fw-bold" id="resumen_costo" readonly
                        style="background-color: #f5f5f5; color: #0d6efd;">
                    </div>
                  </div>
                  <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-sm btn-primary w-100" id="btnGuardarOrdenCompras"
                      title="Guardar orden de compras">
                      <i class="fa-solid fa-floppy-disk"></i> Guardar Orden de Compras
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div id="dvSugerido" class="mt-3" style="height: calc(100vh );">
              <div class="alert alert-info">
                <i class="fa-solid fa-info-circle"></i> Haga clic en "Generar" para ver el análisis
              </div>
            </div>

          </div>
          <!-- Tab Listados de Precios -->
          <div class="tab-pane fade" id="navs-listados-precios" role="tabpanel">
            <div class="card border shadow-sm">
              <div class="card-header py-2 px-3">
                <span class="fw-semibold"><i class="fa-solid fa-list-ul me-2"></i>Carga de Listados de Precios por
                  Proveedor</span>
              </div>
              <div class="card-body py-3 px-3">
                <!-- Inputs ocultos para almacenar datos del proveedor -->
                <input type="hidden" id="idProveedorSugerido">
                <input type="hidden" id="idTerceroSugerido">

                <!-- Seleccionar Proveedor -->
                <div class="row mb-3">
                  <div class="col-md-6">
                    <label for="selectProveedorSugerido" class="form-label">Proveedor <span
                        class="text-danger">*</span></label>
                    <input id="selectProveedorSugerido" class="form-control form-control-sm"
                      placeholder="Escribe el nombre del proveedor..." required>
                    <small class="text-muted">Escribe al menos 3 caracteres para buscar</small>
                  </div>

                  <div class="col-md-3">
                    <label for="inputNitSugerido" class="form-label">NIT</label>
                    <input type="text" class="form-control form-control-sm" id="inputNitSugerido" readonly>
                    <small class="text-muted">Se llena automáticamente</small>
                  </div>

                  <div class="col-md-3">
                    <label for="inputNombreProveedorSugerido" class="form-label">Nombre</label>
                    <input type="text" class="form-control form-control-sm" id="inputNombreProveedorSugerido" readonly>
                  </div>
                </div>

                <hr>

                <!-- Carga de Archivo -->
                <div class="row mb-3">
                  <div class="col-md-12">
                    <label for="inputArchivoSugerido" class="form-label">Archivo de Precios <span
                        class="text-danger">*</span></label>
                    <div class="input-group">
                      <input type="file" class="form-control form-control-sm" id="inputArchivoSugerido"
                        accept=".csv,.xlsx,.xls" style="display:none;">
                      <input type="text" class="form-control form-control-sm" id="labelArchivoSugerido"
                        placeholder="Seleccionar archivo..." readonly>
                      <button class="btn btn-outline-secondary btn-sm" type="button" id="btnCargarArchivoSugerido">
                        <i class="bx bx-folder-open"></i> Seleccionar
                      </button>
                    </div>
                    <small class="text-muted">Formato: CSV, XLSX o XLS (máximo 10 MB)</small>
                  </div>
                </div>

                <!-- Formato esperado -->
                <div class="alert alert-info alert-sm mb-3">
                  <strong><i class="bx bx-info-circle"></i> Formato esperado:</strong>
                  <ul class="mb-0 mt-2">
                    <li><strong>Columna 1:</strong> EAN (código del producto)</li>
                    <li><strong>Columna 2:</strong> Precio (sin IVA)</li>
                    <li><strong>Columna 3:</strong> Precio con IVA</li>
                  </ul>
                </div>

                <!-- Botones de acción -->
                <div class="row">
                  <div class="col-md-12">
                    <button class="btn btn-primary btn-sm" id="btnProcesarArchivoSugerido" disabled>
                      <i class="bx bx-play"></i> Procesar Archivo
                    </button>
                    <button class="btn btn-success btn-sm" id="btnDescargarPlantillaSugerido">
                      <i class="bx bx-download"></i> Descargar Plantilla
                    </button>
                    <button class="btn btn-warning btn-sm" id="btnLimpiarFormSugerido">
                      <i class="bx bx-trash"></i> Limpiar
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Tab Configuración -->

        </div>

      </div>
    </div>
  </div>
  </div>

  <!-- Modal de Preview -->
  <div class="modal fade" id="modalPreviewSugerido" tabindex="-1" aria-labelledby="modalPreviewSugeridoLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalPreviewSugeridoLabel">
            <i class="bx bx-search"></i> Vista Previa del Listado
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="previewContenidoSugerido">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
          <button type="button" class="btn btn-success btn-sm" id="btnGuardarListadoSugerido" disabled>
            <i class="bx bx-save"></i> Guardar Listado
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal de Actualización -->
  <div class="modal fade" id="modalActualizandoSugerido" tabindex="-1" aria-labelledby="modalActualizandoSugeridoLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center">
          <div class="spinner-border text-primary mb-3" role="status">
            <span class="visually-hidden">Cargando...</span>
          </div>
          <p><strong>Actualizando listado de precios...</strong></p>
          <p class="text-muted">Por favor espere, no cierre esta ventana</p>
        </div>
      </div>
    </div>
  </div>

  
<?php

$scriptsPagina = [
  '../controllers/sugeridocompras.js?' . rand(),
  '../controllers/ListadosPreciosProveedor.js?' . rand(),
  'https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js',
  'https://cdn.jsdelivr.net/npm/handsontable@14.0.0/dist/handsontable.full.min.js',
];
include '../dependencies/footer.php';

?>