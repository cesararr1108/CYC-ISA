<?php
session_start();
$titulo = 'Promociones';
include '../dependencies/header.php';
?>
<style>
    #tablaSeleccion tbody td:last-child {
        cursor: pointer;
        user-select: none;
    }
</style>
<input type="hidden" value="<?php echo $_SESSION["Ses_id"] ?? 0; ?>" id="usuario_id" readonly />
<input type="hidden" value="<?php echo $_SESSION["Ses_nombres"] . ' ' . $_SESSION["Ses_apellidos"] ?? ''; ?>" id="usuario_nombre" readonly />
<input type="hidden" id="id_bodega_session" value="<?php if (!empty($_SESSION["Ses_id_bodega"])) {
                                                        echo $_SESSION["Ses_id_bodega"];
                                                    } else {
                                                        echo  '0';
                                                    } ?>" />
<div class="card">
    <div class="card-body">
        <h5 class="fw-bold py-0 mb-4"><span class="text-muted fw-light">Marketing /</span> Promociones</h5>
        <!-- PESTAÑAS: CREAR Y CONSULTAR RESERVAS -->
        <div class="card ">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-crear" data-bs-toggle="tab" data-bs-target="#tab-crear-pane" type="button" role="tab" aria-controls="tab-crear-pane" aria-selected="true">
                        <i class="bx bx-plus me-2"></i> Crear
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-consulta" data-bs-toggle="tab" data-bs-target="#tab-consultar-pane" type="button" role="tab" aria-controls="tab-consultar-pane" aria-selected="false">
                        <i class="bx bx-list-ul me-2"></i> Consultar
                    </button>
                </li>
            </ul>

            <div class="card-body">
                <div class="tab-content">
                    <!-- PESTAÑA 1: CREAR -->
                    <div class="tab-pane fade show active" id="tab-crear-pane" role="tabpanel" aria-labelledby="tab-crear">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="bodegas" class="form-label fw-semibold">Bodega <small class="text-muted">(o todas)</small></label>
                                <select class="form-select form-select-sm" id="bodegas" name="id_bodega"></select>
                            </div>
                            <div class="col-md-3">
                                <label for="lista_precios" class="form-label fw-semibold">Lista de precios</label>
                                <select class="form-select form-select-sm" id="lista_precios" name="id_lista_precio"></select>
                            </div>
                            <div class="col-md-3">
                                <label for="fecha_inicial" class="form-label fw-semibold">Fecha Inicial</label>
                                <input type="date" class="form-control form-control-sm" id="fecha_inicial" placeholder="Fecha inicial" autocomplete="off">
                            </div>
                            <div class="col-md-3">
                                <label for="fecha_final" class="form-label fw-semibold">Fecha Final</label>
                                <input type="date" class="form-control form-control-sm" id="fecha_final" placeholder="Fecha final" autocomplete="off">
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label for="nombre_promocion" class="form-label fw-semibold">Nombre de promoción</label>
                                <input type="text" class="form-control form-control-sm" id="nombre_promocion" placeholder="Nombre de promoción" autocomplete="off">
                            </div>
                            <div class="col-md-3">
                                <label for="porcentaje_desc" class="form-label fw-semibold">Porcentaje de descuento</label>
                                <input type="text" class="form-control form-control-sm" id="porcentaje_desc" placeholder="Porcentaje de descuento" inputmode="numeric" pattern="[0-9]*" autocomplete="off">
                            </div>
                            <div class="col-md-3">
                                <label for="aplicar_a" class="form-label fw-semibold">Aplicar a</label>
                                <select class="form-select form-select-sm" id="aplicar_a" name="aplicar_a">
                                    <option value="1">Todas las referencias</option>
                                    <option value="2">Referencias específicas</option>
                                    <option value="3">Marcas específicas</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="d-flex gap-2">
                                    <button class="btn btn-outline-primary btn-sm" id="btnCrearReserva">
                                        <i class="bx bx-plus me-1"></i> Crear Promoción
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm" id="btnNuevo">
                                        <i class="bx bx-refresh me-1"></i> Limpiar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- TABLA DE SELECCIÓN DE REFERENCIAS/MARCAS (Oculta por defecto) -->
                        <div id="contenedorTablaSeleccion" class="mt-4" style="display: none;">
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <h6 class="fw-semibold mb-2" id="labelTablaSeleccion">Referencias a aplicar</h6>
                                    <div class="input-group input-group-sm mb-3">
                                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                                        <input type="text" class="form-control form-control-sm" id="filtroSeleccion" placeholder="Buscar..." autocomplete="off">
                                        <button class="btn btn-outline-secondary btn-sm" type="button" id="btnLimpiarFiltroSeleccion" title="Limpiar búsqueda">
                                            <i class="bx bx-x"></i>
                                        </button>
                                        <button class="btn btn-outline-info btn-sm" type="button" id="btnVerSeleccionados" title="Ver seleccionados">
                                            <i class="bx bx-list-check me-1"></i> Seleccionadas <span class="badge bg-info ms-1" id="badgeSeleccionados">0</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                        <table id="tablaSeleccion" class="table table-sm table-striped table-hover">
                                            <thead class="table-primary">
                                                <tr>
                                                    <th style="width: 50px;">
                                                        <input type="checkbox" id="chkSeleccionarTodos" title="Seleccionar todos">
                                                    </th>
                                                    <th id="headerSeleccion">Nombre</th>
                                                </tr>
                                            </thead>
                                            <tbody id="bodyTablaSeleccion">
                                                <tr>
                                                    <td colspan="2" class="text-center text-muted py-3">
                                                        <i class="bx bx-inbox"></i> Selecciona una opción arriba
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PESTAÑA 2: CONSULTAR PROMOCIONES -->
                    <div class="tab-pane fade" id="tab-consultar-pane" role="tabpanel" aria-labelledby="tab-consulta">
                        <div class="alert border border-primary mb-4" role="alert">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bx bx-info-circle me-2 text-primary"></i>
                                <span class="fw-semibold text-primary">Información:</span>
                            </div>
                            <small>
                                <div>Los filtros de fecha inicial y final buscan dentro de la fecha de inicio de las promociones.</div>
                            </small>
                        </div>
                        <!-- FILTROS -->
                        <div class="row mb-2 ">
                            <div class="col-md-2">
                                <label for="filtro_bodega" class="form-label fw-semibold">Bodega</label>
                                <select class="form-select form-select-sm" id="filtro_bodega"></select>
                            </div>
                            <div class="col-md-2">
                                <label for="filtroFechaDesde" class="form-label fw-semibold">Fecha Desde</label>
                                <input type="date" class="form-control form-control-sm" id="filtroFechaDesde">
                            </div>
                            <div class="col-md-2">
                                <label for="filtroFechaHasta" class="form-label fw-semibold">Fecha Hasta</label>
                                <input type="date" class="form-control form-control-sm" id="filtroFechaHasta">
                            </div>
                            <div class="col-md-2">
                                <label for="filtro_estado" class="form-label fw-semibold">Estado</label>
                                <select class="form-select form-select-sm" id="filtro_estado">
                                    <option value="">Todos</option>
                                    <option value="1">Activo</option>
                                    <option value="0">Inactivo</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-outline-primary btn-sm flex-grow-1" id="btnAplicarFiltros">
                                        <i class="bx bx-filter me-1"></i> Filtrar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- BÚSQUEDA EN TABLA -->
                        <div class="row mb-3 ">
                            <div class="col-md-12">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                                    <input type="text" class="form-control form-control-sm" id="filtroTablaPromociones" placeholder="Buscar en la tabla..." autocomplete="off">
                                    <button class="btn btn-outline-secondary btn-sm" type="button" id="btnLimpiarBusquedaPromo" title="Limpiar búsqueda">
                                        <i class="bx bx-x"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- TABLA DE PROMOCIONES -->
                        <div class="table-responsive">
                            <table id="tablaPromociones" class="table table-sm table-striped table-hover">
                                <thead class="table-primary">
                                    <tr>
                                        <th>Bodega</th>
                                        <th>Nombre</th>
                                        <th>Lista</th>
                                        <th class="text-center">Descuento</th>
                                        <th class="text-center">Aplica a</th>
                                        <th>Período</th>
                                        <th>Usuario</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="bodyTablaPromociones">
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="bx bx-inbox"></i> Haga clic en "Filtrar" para cargar el listado
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
</div>

<!-- Contenedor para modales dinámicos -->
<div id="modal-container"></div>

<!-- MODAL: Ver Seleccionados -->
<div class="modal fade" id="modalVerSeleccionados" tabindex="-1" aria-labelledby="modalVerSeleccionadosLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVerSeleccionadosLabel">Elementos Seleccionados</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="contenidoSeleccionados">
                    <!-- Se llena dinámicamente -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php
$scriptsPagina = [
    '../controllers/promociones.js?' . rand()
];
include '../dependencies/footer.php';
?>