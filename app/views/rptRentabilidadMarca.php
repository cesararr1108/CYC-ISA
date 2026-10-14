<?php
session_start();

$titulo = 'Listas de precios';
include '../dependencies/header.php';
?>

<div class="card">
    <div class="card-body">
        <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Reportes / Reportes de ventas / </span> Rentabilidad por marca</h5>
        <div class="nav-align-top ">
            <ul class="nav nav-tabs" role="tablist" id="tabs">
                <li class="nav-item">
                    <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-busqueda" aria-controls="navs-busqueda" aria-selected="true"> <i class="fa-solid fa-file-circle-plus"></i> &nbsp;Filtros de búsqueda </button>
                </li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="navs-busqueda" role="tabpanel">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label" for="basic-icon-default-fullname">Fecha inicial</label>
                                <div class="input-group input-group-merge"> <span class="input-group-text"><i class="fa-regular fa-calendar-check"></i></span>
                                    <input type="date" class="form-control form-control-sm" id="fe_fecha_inicial" aria-describedby="fe_fecha_inicial">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label" for="basic-icon-default-fullname">Fecha final</label>
                                <div class="input-group input-group-merge"> <span class="input-group-text"><i class="fa-regular fa-calendar-check"></i></span>
                                    <input type="date" class="form-control form-control-sm" id="fe_fecha_final" aria-describedby="fe_fecha_final">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Marca</label>
                                <select id="marcas" class="form-select form-select-sm" data-placeholder="Todas las marcas">
                                    <option value="">Todas las marcas</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
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
    '../controllers/rptRentabilidadMarca.js?' . rand()
];

include '../dependencies/footer.php';

?>