<?php
session_start();

$titulo = 'Información exógena';
include '../dependencies/header.php';
?>
<div class="card">
    <div class="card-body">
        <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Financiero /</span> Información exógena</h5>
        <div class="card">
            <div class="card-body">
                <div class="row gy-3 align-items-end">
                    <div class="col-md-5">
                        <label for="selVigencia" class="form-label fw-bold text-primary mb-1">
                            Vigencia
                        </label>
                        <select id="selVigencia" name="vigencia" class="form-select form-select-sm">
                        </select>
                    </div>

                    <div class="col-md-5">
                        <label for="selFormato" class="form-label fw-bold text-primary mb-1">
                            Formato
                        </label>
                        <select id="selFormato" name="formato" class="form-select form-select-sm">
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button type="button" class="btn btn-sm btn-primary w-100" id="btnBuscarParametros">
                            <i class="fa-solid fa-magnifying-glass me-1"></i>
                            Generar reporte
                        </button>
                    </div>

                </div>
                <div class="alert alert-info mt-3 mb-0" role="alert">
                    <i class="fa-solid fa-circle-info me-2"></i>
                    <strong>Importante:</strong> La información exógena generada depende de la correcta
                    configuración de los parámetros definidos en el módulo
                    <strong>Financiero &gt;&gt; Parámetros Exógena</strong>. Antes de generar los informes,
                    verifique que dicha configuración corresponda a la normatividad y a las políticas de su empresa.
                    <br><br>
                    Es responsabilidad del usuario revisar y validar la información contenida en los reportes
                    generados antes de su presentación o envío a las entidades correspondientes.
                </div>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-12 table-responsive text-nowrap">
                <div id="result"></div>
            </div>
        </div>
    </div>
</div>


<?php

$scriptsPagina = [
    '../controllers/rptExogena.js?' . rand()
];

include '../dependencies/footer.php';

?>