<?php
session_start();

$titulo = 'Presupuesto de ventas';
include '../dependencies/header.php';
?>

<style>
    /* Grilla editable de presupuesto */
    #tblGrilla td, #tblGrilla th { vertical-align: middle; }
    #tblGrilla .inp-valor { text-align: right; max-width: 190px; margin-left: auto; }
    #tblGrilla tr.fila-modificada { background-color: rgba(var(--bs-primary-rgb), .06); }
    #tblGrilla tr.fila-modificada .inp-valor { border-color: var(--bs-primary); }
    #tblGrilla .col-anterior { color: var(--bs-secondary-color, #a1acb8); }
    .grilla-scroll { max-height: 60vh; overflow-y: auto; }
    /* Encabezados de tabla en color primary */
    .tabla-primary thead th {
        background-color: var(--bs-primary) !important;
        color: #fff !important;
        border-color: var(--bs-primary) !important;
        white-space: nowrap;
    }
    .grilla-scroll thead th, .tabla-previa thead th { position: sticky; top: 0; z-index: 2; }
    .grilla-scroll tfoot td { position: sticky; bottom: 0; z-index: 2; background: var(--bs-body-bg, #fff); }
    .zona-archivo {
        border: 2px dashed var(--bs-border-color, #d9dee3); border-radius: .5rem;
        padding: 2rem 1rem; text-align: center; cursor: pointer; transition: .15s;
    }
    .zona-archivo:hover, .zona-archivo.arrastrando { border-color: var(--bs-primary); background: rgba(var(--bs-primary-rgb), .04); }
    .barra-cambios { position: sticky; bottom: 0; z-index: 5; }
</style>

<div class="card">
    <div class="card-body">
        <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Comercial /</span> Presupuesto de ventas</h5>
        <div class="nav-align-top">
            <ul class="nav nav-tabs" role="tablist" id="tabs">
                <li class="nav-item">
                    <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-creacion" aria-controls="navs-creacion" aria-selected="true">
                        <i class="fa-solid fa-file-circle-plus"></i> &nbsp;Ingreso de presupuesto</button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-cumplimiento" aria-controls="navs-cumplimiento" aria-selected="false">
                        <i class="fa-solid fa-chart-line"></i> &nbsp;Cumplimiento</button>
                </li>
            </ul>
            <div class="tab-content">

                <!-- ================= PESTAÑA: INGRESO ================= -->
                <div class="tab-pane fade show active" id="navs-creacion" role="tabpanel">

                    <!-- Período: todo habilitado y con valores por defecto (año y mes actual, bodega de la sesión) -->
                    <div class="row g-3 align-items-end mb-3">
                        <div class="col-6 col-md-2">
                            <label for="selAnio" class="form-label">Año</label>
                            <select id="selAnio" class="form-select form-select-sm"></select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label for="selMes" class="form-label">Mes</label>
                            <select id="selMes" class="form-select form-select-sm"></select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="selBodega" class="form-label">Bodega</label>
                            <select id="selBodega" class="form-select form-select-sm"></select>
                        </div>
                        <div class="col-12 col-md-5">
                            <label class="form-label d-block">Presupuestar por</label>
                            <div class="btn-group btn-group-sm w-100" role="group" id="grpTipo">
                                <input type="radio" class="btn-check" name="tipo" id="tipoZona" value="zona" checked>
                                <label class="btn btn-outline-primary" for="tipoZona"><i class="fa-solid fa-map-location-dot"></i> Zona</label>
                                <input type="radio" class="btn-check" name="tipo" id="tipoVendedor" value="vendedor">
                                <label class="btn btn-outline-primary" for="tipoVendedor"><i class="fa-solid fa-user-tie"></i> Vendedor</label>
                                <input type="radio" class="btn-check" name="tipo" id="tipoUsuario" value="usuario">
                                <label class="btn btn-outline-primary" for="tipoUsuario"><i class="fa-solid fa-user"></i> Usuario</label>
                            </div>
                        </div>
                    </div>

                    <!-- Resumen + acciones -->
                    <div class="card border shadow-none mb-3">
                        <div class="card-body py-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                <div class="d-flex flex-wrap gap-4">
                                    <div>
                                        <small class="text-muted d-block">Total presupuesto</small>
                                        <h5 class="mb-0 text-primary" id="lblTotal">$ 0</h5>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block" id="lblConValorTitulo">Con presupuesto</small>
                                        <h5 class="mb-0" id="lblConValor">0 de 0</h5>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block">Mes anterior</small>
                                        <h5 class="mb-0 text-muted" id="lblTotalAnterior">$ 0</h5>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnImportar">
                                        <i class="fa-solid fa-file-arrow-up"></i> Cargar archivo
                                    </button>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fa-solid fa-wand-magic-sparkles"></i> Más acciones
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="#" id="accTraerAnterior"><i class="fa-regular fa-copy me-2"></i>Traer valores del mes anterior</a></li>
                                            <li><a class="dropdown-item" href="#" id="accTraerOtro"><i class="fa-regular fa-calendar me-2"></i>Traer valores de otro mes...</a></li>
                                            <li><a class="dropdown-item" href="#" id="accDistribuir"><i class="fa-solid fa-divide me-2"></i>Repartir un total en partes iguales...</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item" href="#" id="accPlantilla"><i class="fa-solid fa-download me-2"></i>Descargar plantilla del archivo</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="#" id="accLimpiar"><i class="fa-solid fa-eraser me-2"></i>Vaciar todos los valores</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Grilla editable: una fila por zona / vendedor / usuario -->
                    <div class="mb-2">
                        <div class="input-group input-group-sm" style="max-width:340px">
                            <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="search" id="txtBuscar" class="form-control" placeholder="Buscar zona...">
                        </div>
                    </div>
                    <div class="table-responsive grilla-scroll border rounded">
                        <table class="table table-sm table-hover mb-0 tabla-primary" id="tblGrilla">
                            <thead>
                                <tr>
                                    <th style="width:90px">Código</th>
                                    <th id="thReferencia">Zona</th>
                                    <th class="text-end d-none d-md-table-cell" style="width:170px">Mes anterior</th>
                                    <th class="text-end" style="width:220px">Presupuesto</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                            <tfoot>
                                <tr class="fw-semibold">
                                    <td colspan="2">Total</td>
                                    <td class="text-end d-none d-md-table-cell text-muted" id="tfAnterior">$ 0</td>
                                    <td class="text-end text-primary" id="tfTotal">$ 0</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="form-text mb-3">
                        Escriba el valor directamente en cada fila (Enter pasa a la siguiente). Dejar el valor vacío quita el presupuesto al guardar.
                    </div>

                    <!-- Barra de cambios pendientes -->
                    <div class="barra-cambios d-none" id="barraCambios">
                        <div class="alert alert-primary d-flex flex-wrap justify-content-between align-items-center gap-2 mb-0 py-2">
                            <span><i class="fa-solid fa-pen-to-square me-1"></i> <strong id="lblCambios">0</strong> cambio(s) sin guardar</span>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnDescartar">Descartar</button>
                                <button type="button" class="btn btn-sm btn-primary" id="btnGuardar">
                                    <i class="fa-regular fa-floppy-disk"></i> Guardar cambios
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= PESTAÑA: CUMPLIMIENTO ================= -->
                <div class="tab-pane fade" id="navs-cumplimiento" role="tabpanel">

                    <div class="row g-3 align-items-end mb-3">
                        <div class="col-6 col-md-2">
                            <label for="selAnioC" class="form-label">Año</label>
                            <select id="selAnioC" class="form-select form-select-sm"></select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label for="selMesC" class="form-label">Mes</label>
                            <select id="selMesC" class="form-select form-select-sm"></select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="selBodegaC" class="form-label">Bodega</label>
                            <select id="selBodegaC" class="form-select form-select-sm"></select>
                        </div>
                        <div class="col-12 col-md-5">
                            <label class="form-label d-block">Presupuesto por</label>
                            <div class="btn-group btn-group-sm w-100" role="group" id="grpTipoC">
                                <input type="radio" class="btn-check" name="tipoC" id="tipoCZona" value="zona" checked>
                                <label class="btn btn-outline-primary" for="tipoCZona"><i class="fa-solid fa-map-location-dot"></i> Zona</label>
                                <input type="radio" class="btn-check" name="tipoC" id="tipoCVendedor" value="vendedor">
                                <label class="btn btn-outline-primary" for="tipoCVendedor"><i class="fa-solid fa-user-tie"></i> Vendedor</label>
                                <input type="radio" class="btn-check" name="tipoC" id="tipoCUsuario" value="usuario">
                                <label class="btn btn-outline-primary" for="tipoCUsuario"><i class="fa-solid fa-user"></i> Usuario</label>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-4">
                            <div class="card border shadow-none"><div class="card-body py-2">
                                <small class="text-muted">Presupuesto</small>
                                <h5 class="mb-0" id="cPresupuesto">—</h5>
                            </div></div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="card border shadow-none"><div class="card-body py-2">
                                <small class="text-muted">Ventas netas</small>
                                <h5 class="mb-0" id="cVentas">—</h5>
                            </div></div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="card border shadow-none"><div class="card-body py-2">
                                <small class="text-muted">Cumplimiento</small>
                                <h5 class="mb-0" id="cCumplimiento">—</h5>
                            </div></div>
                        </div>
                    </div>

                    <div class="table-responsive border rounded">
                        <table class="table table-sm table-hover align-middle mb-0 tabla-primary" id="tblCumplimiento">
                            <thead>
                                <tr>
                                    <th id="thReferenciaC">Zona</th>
                                    <th class="text-end">Presupuesto</th>
                                    <th class="text-end">Ventas</th>
                                    <th class="text-end">Diferencia</th>
                                    <th style="min-width:160px">Cumplimiento</th>
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

<!-- Modal: traer valores de otro mes (llena la grilla, no guarda hasta confirmar) -->
<div class="modal fade" id="modalTraer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Traer valores de otro mes</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-6">
                        <label for="selAnioOrigen" class="form-label">Año</label>
                        <select id="selAnioOrigen" class="form-select form-select-sm"></select>
                    </div>
                    <div class="col-6">
                        <label for="selMesOrigen" class="form-label">Mes</label>
                        <select id="selMesOrigen" class="form-select form-select-sm"></select>
                    </div>
                    <div class="col-12">
                        <label for="txtAjuste" class="form-label">Ajuste porcentual (opcional)</label>
                        <div class="input-group input-group-sm">
                            <input type="number" id="txtAjuste" class="form-control" value="0" step="0.1">
                            <span class="input-group-text">%</span>
                        </div>
                        <div class="form-text">Ej.: 5 aumenta los valores un 5 %, -10 los reduce un 10 %.</div>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="chkSoloVacios" checked>
                            <label class="form-check-label" for="chkSoloVacios">Llenar solo las filas que estén vacías</label>
                        </div>
                    </div>
                </div>
                <div class="form-text mt-3">Los valores se pasan a la tabla para que los revise; se guardan al dar clic en <strong>Guardar cambios</strong>.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm btn-primary" id="btnConfirmarTraer"><i class="fa-regular fa-copy"></i> Traer valores</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: carga desde archivo plano -->
<div class="modal fade" id="modalImportar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Cargar presupuesto desde archivo</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3" id="lblDestinoImport"></p>

                <!-- Paso 1: elegir archivo -->
                <div id="pasoArchivo">
                    <label class="zona-archivo d-block" id="zonaArchivo" for="fileImport">
                        <i class="fa-solid fa-file-csv fa-2x text-primary mb-2"></i>
                        <div class="fw-semibold">Arrastre aquí el archivo o haga clic para seleccionarlo</div>
                        <small class="text-muted">Formatos .csv o .txt — separado por punto y coma, coma, tabulador o |</small>
                    </label>
                    <input type="file" id="fileImport" accept=".csv,.txt,text/csv,text/plain" class="d-none">

                    <div class="mt-3 small">
                        <div class="fw-semibold mb-1">Estructura esperada (una fila por <span class="lbl-tipo-min">zona</span>):</div>
                        <pre class="bg-lighter border rounded p-2 mb-1">codigo;valor
1;15000000
2;8.500.000</pre>
                        <div class="text-muted">
                            La primera columna puede ser el <strong>código</strong> o el <strong>nombre</strong> exacto.
                            Puede incluir una columna intermedia con el nombre (como la plantilla); se toma la última columna como valor.
                            El encabezado es opcional.
                            <a href="#" id="lnkPlantilla">Descargar plantilla con los datos actuales</a>.
                        </div>
                    </div>
                </div>

                <!-- Paso 2: vista previa -->
                <div id="pasoPrevia" class="d-none">
                    <div class="d-flex flex-wrap gap-2 mb-2" id="resumenImport"></div>
                    <div class="btn-group btn-group-sm mb-2" role="group">
                        <input type="radio" class="btn-check" name="filtroImport" id="fiTodos" value="todos" checked>
                        <label class="btn btn-outline-primary" for="fiTodos">Todos</label>
                        <input type="radio" class="btn-check" name="filtroImport" id="fiErrores" value="errores">
                        <label class="btn btn-outline-primary" for="fiErrores">Solo con error</label>
                    </div>
                    <div class="table-responsive border rounded tabla-previa" style="max-height:45vh">
                        <table class="table table-sm mb-0 tabla-primary" id="tblPrevia">
                            <thead>
                                <tr>
                                    <th style="width:60px">Línea</th>
                                    <th>Dato del archivo</th>
                                    <th>Coincide con</th>
                                    <th class="text-end">Valor</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" id="chkVaciarNoIncluidos">
                        <label class="form-check-label" for="chkVaciarNoIncluidos">
                            Vaciar las filas que no vienen en el archivo (el archivo reemplaza todo el presupuesto del mes)
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="btnOtroArchivo">Elegir otro archivo</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm btn-primary" id="btnAplicarImport" disabled>
                    <i class="fa-solid fa-check"></i> Pasar a la tabla
                </button>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="hdBodegaSesion" value="<?= htmlspecialchars($_SESSION['Ses_id_bodega'] ?? '') ?>">

<?php
$scriptsPagina = [
    '../controllers/presupuestoVentas.js?' . rand()
];

include '../dependencies/footer.php';
?>