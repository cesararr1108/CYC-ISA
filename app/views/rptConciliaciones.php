<?php
session_start();

$titulo = 'Conciliación de movimientos';
include '../dependencies/header.php';
?>

<div class="card">
    <div class="card-body">
        <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Reportes / Reportes de Varios / </span> Conciliación de movimientos </h5>
        <div class="nav-align-top ">
            <ul class="nav nav-tabs" role="tablist" id="tabs">
                <li class="nav-item">
                    <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-busqueda" aria-controls="navs-busqueda" aria-selected="true"> <i class="fa-solid fa-file-circle-plus"></i> &nbsp;Filtros de búsqueda </button>
                </li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="navs-busqueda" role="tabpanel">
                    <div class="row">
                        <div class="col-4"> <!-- Aumentado a col-3 para dar espacio al selector de fecha y hora -->
                            <div class="mb-3">
                                <label class="form-label" for="fe_fecha_inicial">Fecha y hora inicial</label>
                                <div class="input-group input-group-merge input-group-sm">
                                    <span class="input-group-text"><i class="fa-regular fa-clock"></i></span>
                                    <input type="datetime-local" class="form-control form-control-sm" id="fe_fecha_inicial" aria-describedby="fe_fecha_inicial">
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="mb-3">
                                <label class="form-label" for="fe_fecha_final">Fecha y hora final</label>
                                <div class="input-group input-group-merge input-group-sm">
                                    <span class="input-group-text"><i class="fa-regular fa-clock"></i></span>
                                    <input type="datetime-local" class="form-control form-control-sm" id="fe_fecha_final" aria-describedby="fe_fecha_final">
                                </div>
                            </div>
                        </div>
                        <div class="col-2">
                            <label for="naturaleza" class="form-label">Naturaleza</label>
                            <select name="naturaleza" id="naturaleza" class="form-select form-select-sm">
                                <option value="T">Todas</option>
                                <option value="E">Efectivo</option>
                                <option value="B">Banco</option>
                            </select>
                        </div>
                        <div class="col-2">
                            <label for="bodegas" class="form-label">Bodega</label>
                            <select name="bodegas" id="bodegas" class="form-select form-select-sm">
                                <option value="">Seleccione una bodega</option>
                            </select>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="row mt-3">
                        <div class="col-md-12 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-limpiar"> <i class="fa-solid fa-eraser"></i> Limpiar </button>
                            <button type="button" class="btn btn-sm btn-primary" id="btn-busqueda"> <i class="fa fa-search"></i> Consultar </button>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col">
                            <div id="resultado"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php

$scriptsPagina = [
    '../controllers/rptConciliaciones.js?' . rand()
];

include '../dependencies/footer.php';

?>