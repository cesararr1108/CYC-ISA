<?php
session_start();

$titulo = 'Ventas por Almacen o Bodega';
include '../dependencies/header.php';
?>

<style>
    .estados-table th.col-cuenta,
    .estados-table td.col-cuenta {
        width: 120px;
        white-space: nowrap;
    }

    .estados-table th.col-desc,
    .estados-table td.col-desc {
        width: 280px;
        max-width: 280px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .estados-table th.col-saldo,
    .estados-table td.col-saldo,
    .estados-table th.col-debito,
    .estados-table td.col-debito,
    .estados-table th.col-credito,
    .estados-table td.col-credito {
        width: 140px;
        white-space: nowrap;
    }
</style>
<div class="card">
    <div class="card-body">
        <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Reportes / Reportes de ventas / </span> Ventas por almacen o bodega</h5>
        <div class="nav-align-top ">
            <ul class="nav nav-tabs" role="tablist" id="tabs">
                <li class="nav-item">
                    <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-busqueda" aria-controls="navs-busqueda" aria-selected="true"> <i class="fa-solid fa-file-circle-plus"></i> &nbsp;Filtros de búsqueda </button>
                </li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="navs-busqueda" role="tabpanel">
                    <div class="row">
                        <div class="col-4">
                            <div class="mb-3">
                                <label class="form-label" for="fe_fecha_inicial">Fecha inicial</label>
                                <div class="input-group input-group-merge"> <span class="input-group-text"><i class="fa-regular fa-calendar-check"></i></span>
                                    <input type="date" class="form-control form-control-sm" id="fe_fecha_inicial" aria-describedby="fe_fecha_inicial">
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="mb-3">
                                <label class="form-label" for="fe_fecha_final">Fecha final</label>
                                <div class="input-group input-group-merge"> <span class="input-group-text"><i class="fa-regular fa-calendar-check"></i></span>
                                    <input type="date" class="form-control form-control-sm" id="fe_fecha_final" aria-describedby="fe_fecha_final">
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="mb-3">
                                <label class="form-label" for="slcBodegas">Bodegas</label>
                                <select class="form-select form-select-sm" id="slcBodegas" name="slcBodegas"> </select>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Botones -->
                <div class="row mt-3">
                    <div class="col-md-12 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-limpiar"> <i class="fa-solid fa-eraser"></i> Limpiar </button>
                        <button type="button" class="btn btn-sm btn-primary" id="btn-busqueda"> <i class="fa fa-search"></i> Consultar </button>
                    </div>
                </div>

                <!-- Resumen -->
                <div id="resumen-ventas" class="row mt-3"></div>
                <!-- Grafico -->
                <div id="grafico-ventas" style="height: 400px;"></div>
                <!-- Detalle -->
                <div id="resultado-ventas" class="table-responsive mt-3"></div>
            </div>
        </div>
    </div>
</div>

<?php

$scriptsPagina = [
    '../controllers/rptVentasAlmacen.js?' . rand()
];

include '../dependencies/footer.php';

?>