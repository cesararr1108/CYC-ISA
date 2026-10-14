<?php
session_start();

$titulo = 'Dias de inventarios';
include '../dependencies/header.php';
?>

<div class="card">
    <div class="card-body">
        <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Reportes / Reportes de Compras / </span> Dias de inventarios </h5>
        <div class="nav-align-top ">
            <ul class="nav nav-tabs" role="tablist" id="tabs">
                <li class="nav-item">
                    <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-busqueda" aria-controls="navs-busqueda" aria-selected="true"> <i class="fa-solid fa-file-circle-plus"></i> &nbsp;Filtros de búsqueda </button>
                </li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="navs-busqueda" role="tabpanel">
                    <div class="row align-items-end g-2">
                        <!-- Período -->
                        <div class="col-md-2">
                            <div>
                                <label for="filtro_meses" class="form-label fw-semibold mb-1">
                                    Período (Meses)
                                </label>
                                <select id="filtro_meses" class="form-select form-select-sm">
                                    <option value="1">1 mes</option>
                                    <option value="2">2 meses</option>
                                    <option value="3" selected>3 meses</option>
                                    <option value="4">4 meses</option>
                                    <option value="6">6 meses</option>
                                    <option value="12">12 meses</option>
                                </select>
                            </div>
                        </div>

                        <!-- Días de Inventario -->
                        <div class="col-md-2">
                            <div>
                                <label for="filtro_dias" class="form-label fw-semibold mb-1">
                                    Días de Inventario
                                </label>
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

                        <!-- Bodega -->
                        <div class="col-md-4">
                            <div>
                                <label for="bodegas" class="form-label fw-semibold mb-1">
                                    Bodega
                                </label>
                                <select name="bodegas" id="bodegas" class="form-select form-select-sm">
                                    <option value="">Seleccione una bodega</option>
                                </select>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="col-md-4 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-limpiar">
                                <i class="fa-solid fa-eraser"></i> Limpiar
                            </button>

                            <button type="button" class="btn btn-sm btn-primary" id="btn-busqueda">
                                <i class="fa fa-search"></i> Consultar
                            </button>
                        </div>
                    </div>


                    <div class="row mt-3">
                        <div class="col">
                            <div class="row mt-3">
                                <div class="col-6">
                                    <div id="chart-estados"></div>
                                </div>
                                <div class="col-6">
                                    <div id="unidades-estados"></div>
                                </div>
                            </div>
                            <div id="resultado" class="text-nowrap table-responsive"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php

$scriptsPagina = [
    '../controllers/rptDiasInventario.js?' . rand()
];

include '../dependencies/footer.php';

?>