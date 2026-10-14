<?php
session_start();
$titulo = 'Rótulos y guías';
include '../dependencies/header.php';
?>
<input type="hidden" id="id_bodega_session" value="<?php if (!empty($_SESSION["Ses_id_bodega"])) {
                                                        echo $_SESSION["Ses_id_bodega"];
                                                    } else {
                                                        echo  '0';
                                                    } ?>" />
<div class="card">
    <div class="card-body">
        <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Logística / </span> Rótulos y guías</h5>
        <div class="card shadow-sm">
            <div class="card-header">
                <strong>Datos de impresión</strong>
            </div>

            <div class="card-body">

                <div class="row g-3">

                    <div class="col-md-3">
                        <label for="slcTipoFact" class="form-label">Tipo de factura</label>
                        <select class="form-select form-select-sm" id="slcTipoFact"></select>
                    </div>

                    <div class="col-md-3">
                        <label for="numFact" class="form-label">N.º de factura</label>
                        <input type="text"
                            class="form-control form-control-sm"
                            id="numFact"
                            placeholder="Ingrese el número">
                    </div>

                    <div class="col-md-2">
                        <label for="cantCajas" class="form-label">Cant. cajas</label>
                        <input type="number"
                            class="form-control form-control-sm"
                            id="cantCajas"
                            min="1"
                            value="1">
                    </div>

                    <div class="col-md-4">
                        <label for="slcTipoImp" class="form-label">Documento a imprimir</label>
                        <select class="form-select form-select-sm" id="slcTipoImp">
                            <option value="0">Guía Carta</option>
                            <option value="1">Rótulo Carta</option>
                            <option value="2">Rotulo Sticker</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label for="notas" class="form-label">Observaciones</label>
                        <textarea
                            class="form-control"
                            id="notas"
                            rows="3"
                            maxlength="100"
                            placeholder="Ingrese observaciones o anexos..."></textarea>
                    </div>

                </div>

            </div>

            <div class="card-footer d-flex justify-content-end">
                <button class="btn btn-primary btn-sm" id="btnImprimir">
                    <i class="fa-solid fa-print me-1"></i>
                    Imprimir
                </button>
            </div>
        </div>
    </div>
</div>
<!--Modal PDF Planilla-->
<div class="modal fade" id="mdRotulo" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="staticBackdropLabel">Impresión de documento</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="mdRotuloBody"> ... </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php
$scriptsPagina = [
    '../controllers/rotulosGuias.js?' . rand()
];
include '../dependencies/footer.php';
?>