<?php
session_start();

$titulo = 'Listas de precios';
include '../dependencies/header.php';
?>

<div class="card">
    <div class="card-body">
        <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Reportes / Reportes de ventas / </span> Listas de precios</h5>
        <div class="nav-align-top ">
            <ul class="nav nav-tabs" role="tablist" id="tabs">
                <li class="nav-item">
                    <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-busqueda" aria-controls="navs-busqueda" aria-selected="true"> <i class="fa-solid fa-file-circle-plus"></i> &nbsp;Filtros de búsqueda </button>
                </li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="navs-busqueda" role="tabpanel">
                    <div class="row g-3 mt-1">
                        <!-- Card: Parámetros -->
                        <div class="col-12">

                            <div class="row g-2">
                                <div class="col-4">
                                    <label for="marcas" class="form-label fw-semibold mb-1">Marca</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text"><i class="fa-solid fa-flask"></i></span>
                                        <select class="form-select form-select-sm" id="marcas"></select>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <label for="listas" class="form-label fw-semibold mb-1">Lista de precios <span class="text-danger">*</span></label>
                                    <select name="listas" id="listas" class="form-select form-select-sm">
                                        <option value="">Seleccione una lista</option>
                                    </select>
                                </div>
                                <div class="col-4">
                                    <label for="bodegas" class="form-label fw-semibold mb-1">Bodega</label>
                                    <select name="bodegas" id="bodegas" class="form-select form-select-sm">
                                        <option value="">Seleccione una bodega</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <!-- Botones -->
                        <div class="col-12 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-limpiar"><i class="fa-solid fa-eraser"></i> Limpiar</button>
                            <button type="button" class="btn btn-sm btn-primary" id="btn-busqueda"><i class="fa fa-search"></i> Consultar</button>
                        </div>

                    </div>

                    <div id="resultado" class="mt-3 table-responsive text-nowrap"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php

$scriptsPagina = [
    '../controllers/rptListasPrecios.js?' . rand()
];

include '../dependencies/footer.php';

?>