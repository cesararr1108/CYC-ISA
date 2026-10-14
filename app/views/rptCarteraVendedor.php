<?php
session_start();

$titulo = 'Cartera por vendedor - zona';
include '../dependencies/header.php';
?>
<div class="card">
    <div class="card-body">
        <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Reportes / Reportes de cartera / </span> Cartera por vendedor - zona</h5>
        <div class="row  g-2 mt-3">
            <div class="col-md-3">
                <label class="form-label">Año</label>
                <select id="SAno" name="SAno" class="form-select form-select-sm bg-light">
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tipo de Partida</label>
                <select class="form-select form-select-sm bg-light" id="Stipo">
                    <option value="S">SIN COMPENSAR</option>
                    <option value="C">COMPENSADAS</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="slcZonas">Zona</label>
                <select class="form-select form-select-sm" id="slcZonas"></select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="slcVendedores">Vendedor</label>
                <select class="form-select form-select-sm" id="slcVendedores"></select>
            </div>
        </div>
        <!-- Botones -->
        <div class="row mt-3">
            <div class="col-md-12 d-flex justify-content-between align-items-center gap-2">
                <div class="btn-group" role="group">
                    <input type="radio" class="btn-check" name="tipo_vista" id="vista_consolidada" value="consolidada" checked>
                    <label class="btn btn-sm btn-outline-primary" for="vista_consolidada">
                        <i class="fa fa-chart-bar"></i> Vista Consolidada
                    </label>
                    <input type="radio" class="btn-check" name="tipo_vista" id="vista_detallada" value="detallada">
                    <label class="btn btn-sm btn-outline-primary" for="vista_detallada">
                        <i class="fa fa-list"></i> Vista Detallada
                    </label>
                </div>
                <button type="button" class="btn btn-sm btn-primary" id="btn-busqueda"> <i class="fa fa-search"></i> Consultar </button>
            </div>
        </div>
        <!-- Resultado -->
        <div id="result" class="table-responsive mt-3"></div>
    </div>
</div>

<?php

$scriptsPagina = [
    '../controllers/rptCarteraVendedor.js?' . rand()
];

include '../dependencies/footer.php';

?>