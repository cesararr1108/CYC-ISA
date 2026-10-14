<?php
session_start();

$titulo = 'Sube/Baja Costo Promedio';
include '../dependencies/header.php';
?>
<input type="hidden" id="bodega" value="<?php echo !empty($_SESSION['Ses_bodega']) ? $_SESSION['Ses_bodega'] : (!empty($_SESSION['Ses_id_bodega']) ? $_SESSION['Ses_id_bodega'] : 0); ?>" readonly>

<div class="card">
    <div class="card-body">
        <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Inventario /</span> Sube - Baja costo promedio</h5>

        <div class="nav-align-top">
            <ul class="nav nav-tabs" role="tablist" id="tabsSubeBajaCosto">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-tercero" type="button">
                        <i class="fa-solid fa-user-tie"></i> Tercero
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-items" type="button">
                        <i class="fa-solid fa-boxes-stacked"></i> Items
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-misajustes" type="button">
                        <i class="fa-solid fa-history"></i> Mis Ajustes
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="tab-tercero">
                    <div class="row mt-3">
                        <div class="col-md-8">
                            <div class="card h-100 mb-3">
                                <h6 class="card-header text-primary form-label">Tercero</h6>
                                <div class="card-body">
                                    <div class="row g-2">
                                        <div class="col-md-12">
                                            <label class="form-label">Búsqueda de tercero</label>
                                            <input type="text" id="txtBusquedaTercero" class="form-control form-control-sm" placeholder="NIT, Razón social, nombre..." autocomplete="off">
                                        </div>
                                    </div>
                                    <div class="row g-2 mt-1">
                                        <div class="col-md-12">
                                            <label class="form-label">NIT</label>
                                            <input type="text" id="nit" class="form-control form-control-sm" readonly>
                                        </div>
                                    </div>
                                    <div class="row g-2 mt-1">
                                        <div class="col-md-12">
                                            <label class="form-label">Tratamiento</label>
                                            <input type="text" id="tratamiento" class="form-control form-control-sm" readonly>
                                        </div>
                                    </div>
                                    <div class="row g-2 mt-1">
                                        <div class="col-md-12">
                                            <label class="form-label">Razon Comercial</label>
                                            <input type="text" id="razon_comercial" class="form-control form-control-sm" readonly>
                                        </div>
                                    </div>
                                    <div class="row g-2 mt-1">
                                        <div class="col-md-12">
                                            <label class="form-label">Direccion</label>
                                            <select id="id_tercero_dir" class="form-select form-select-sm"></select>
                                        </div>
                                    </div>
                                    <input type="hidden" id="id_tercero" value="0">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card h-100 mb-3">
                                <h6 class="card-header text-primary form-label">Condiciones</h6>
                                <div class="card-body">
                                    <div class="row g-2 mt-1">
                                        <div class="col-md-12">
                                            <label class="form-label">SW</label>
                                            <select id="sw" class="form-select form-select-sm"></select>
                                        </div>
                                    </div>
                                    <div class="row g-2 mt-1">
                                        <div class="col-md-12">
                                            <label class="form-label">Tipo</label>
                                            <select id="id_tipo_documento" class="form-select form-select-sm"></select>
                                        </div>
                                    </div>
                                    <div class="row g-2 mt-1">
                                        <div class="col-md-12">
                                            <label class="form-label">Bodega</label>
                                            <input type="text" id="bodega_actual" class="form-control form-control-sm" readonly>
                                        </div>
                                    </div>

                                    <div class="row g-2 mt-1">
                                        <div class="col-md-12">
                                            <label class="form-label">Condicion de pago</label>
                                            <select id="condicion" class="form-select form-select-sm"></select>
                                        </div>
                                    </div>
                                    <div class="row g-2 mt-1">
                                        <div class="col-md-12">
                                            <label class="form-label">Notas</label>
                                            <textarea id="notas" class="form-control form-control-sm" rows="2" placeholder="Motivo del ajuste de costo promedio"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-items">
                    <div class="card mb-3 mt-3">
                        <div class="card-body">
                            <div class="row mt-2">
                                <div class="col-md-12">
                                    <span><i class="fa fa-search"></i> Búsqueda de productos</span>
                                    <div class="input-group input-group-merge mt-2">
                                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                                        <input name="" id="filtro" type="text" class="form-control form-control-sm filtros-busqueda-productos" placeholder="Codigo, descripcion, descripcion larga" aria-label="Buscar..." autocomplete="off" />
                                        <span class="input-group-text btn btn-sm btn-primary" id="btnBusquedaAvanzada"><i class="fa-solid fa-sliders"></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive mb-3">
                        <table id="tb-productos" class="table table-sm table-bordered table-hover">
                            <thead class="table-primary">
                                <tr>
                                    <th>Codigo</th>
                                    <th>Descripcion</th>
                                    <th class="text-end">Stock</th>
                                    <th class="text-end">Promedio Actual</th>
                                    <th class="text-end">Nuevo Promedio</th>
                                    <th class="text-end">Delta Unitario</th>
                                    <th class="text-end">Ajuste Total</th>
                                    <th style="width:60px;">Quitar</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    <div class="row align-items-center mb-3">
                        <div class="col-md-6">
                            <div class="summary-card">
                                <strong>Impacto total en costo_ent:</strong>
                                <span id="lbl-impacto-total" class="text-primary fw-bold">$0</span>
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <button type="button" id="btn-guardar" class="btn btn-sm btn-success">
                                <i class="fa-solid fa-floppy-disk"></i> Guardar Documento y Ajustar Costo
                            </button>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-misajustes">
                    <div class="card mb-3 mt-3">
                        <div class="card-body">
                            <div class="row g-2">
                                <div class="col-md-5">
                                    <label class="form-label">Fecha Inicial</label>
                                    <input type="date" id="fechaInicial" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Fecha Final</label>
                                    <input type="date" id="fechaFinal" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="button" id="btnBuscarAjustes" class="btn btn-sm btn-primary w-100">
                                        <i class="fa-solid fa-search"></i> Buscar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive text-nowrap mb-3">
                        <table id="tb-misajustes" class="table table-sm table-bordered table-hover">
                            <thead class="table-primary">
                                <tr>
                                    <th>Fecha/Hora</th>
                                    <th>Documento</th>
                                    <th>SW</th>
                                    <th>Tercero</th>
                                    <th>Producto</th>
                                    <th class="text-end">Stock</th>
                                    <th class="text-end">Promedio Anterior</th>
                                    <th class="text-end">Nuevo Promedio</th>
                                    <th class="text-end">Delta</th>
                                    <th class="text-end">Ajuste Total</th>
                                    <th class="text-center" style="width: 90px;">Imprimir</th>
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

<div id="modal-container"></div>



<?php

$scriptsPagina = [
    '../controllers/subeBajaCosto.js?' . rand()
];

include '../dependencies/footer.php';

?>