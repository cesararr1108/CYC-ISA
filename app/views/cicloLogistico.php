<?php
session_start();
$titulo = 'Ciclo logístico';
include '../dependencies/header.php';
?>
<style>
    .timeline-line {
        flex: 1;
        min-width: 30px;
        max-width: 120px;
        height: 2px;
    }
</style>
<div class="card">
    <div class="card-body">
        <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Logística / </span> Ciclo logístico</h5>

        <div class="row">
            <div class="col-md-6">
                <label class="form-label">Fecha inicial</label>
                <div class="input-group input-group-merge"> <span class="input-group-text"><i class="fa-regular fa-calendar-check"></i></span>
                    <input type="date" class="form-control form-control-sm" id="fhIni" aria-describedby="fhIni">
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Fecha final</label>
                <div class="input-group input-group-merge"> <span class="input-group-text"><i class="fa-regular fa-calendar-check"></i></span>
                    <input type="date" class="form-control form-control-sm" id="fhFin" aria-describedby="fhFin">
                </div>
            </div>
        </div>
        <!-- timeline -->
        <div class="row mt-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start w-100 mb-3">
                            <!-- Temporales -->
                            <div class="d-flex flex-column align-items-center flex-fill">
                                <button type="button"
                                    id="btn_temporales"
                                    class="btn btn-light border-0 filtros-estados p-0"
                                    data-bs-toggle="tooltip"
                                    title="Temporales">
                                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-secondary text-white shadow-sm"
                                        style="width: 42px; height: 42px;">
                                        <i class="fa-regular fa-file"></i>
                                    </span>
                                </button>
                                <span class="mt-2">
                                    <span class="small fw-semibold">Temporales</span>
                                    <span id="p_temporales" class="badge bg-secondary rounded-pill">0</span>
                                </span>
                            </div>

                            <!-- Línea -->
                            <div class="timeline-line bg-secondary mt-3"></div>

                            <!-- Autorización -->
                            <div class="d-flex flex-column align-items-center flex-fill">
                                <button type="button"
                                    id="btn_autorizacion"
                                    class="btn btn-light border-0 filtros-estados p-0"
                                    data-bs-toggle="tooltip"
                                    title="Autorizaciones">

                                    <span id="circle_autorizacion"
                                        class="d-inline-flex align-items-center justify-content-center rounded-circle bg-secondary text-white shadow-sm"
                                        style="width: 42px; height: 42px;">
                                        <i id="icon_autorizacion"
                                            class="fa-solid fa-user-lock"></i>
                                    </span>
                                </button>
                                <span class="mt-2">
                                    <span class="small fw-semibold">Autorización</span>
                                    <span id="p_autorizacion" class="badge bg-secondary rounded-pill">0</span>
                                </span>
                            </div>

                            <!-- Línea -->
                            <div class="timeline-line bg-secondary mt-3"></div>

                            <!-- Confirmados -->
                            <div class="d-flex flex-column align-items-center flex-fill">
                                <button type="button"
                                    id="btn_confirmados"
                                    class="btn btn-light border-0 filtros-estados p-0"
                                    data-bs-toggle="tooltip"
                                    title="Confirmados">

                                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning text-white shadow-sm"
                                        style="width: 42px; height: 42px;">
                                        <i class="fa-solid fa-circle-check"></i>
                                    </span>
                                </button>
                                <span class="mt-2">
                                    <span class="small fw-semibold">Confirmados</span>
                                    <span id="p_confirmados" class="badge bg-warning rounded-pill">0</span>
                                </span>

                            </div>

                            <!-- Línea -->
                            <div class="timeline-line bg-secondary mt-3"></div>

                            <!-- Separados -->
                            <div class="d-flex flex-column align-items-center flex-fill">
                                <button type="button"
                                    id="btn_separados"
                                    class="btn btn-light border-0 filtros-estados p-0"
                                    data-bs-toggle="tooltip"
                                    title="Separados">

                                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-info text-white shadow-sm"
                                        style="width: 42px; height: 42px;">
                                        <i class="fa-solid fa-boxes-stacked"></i>
                                    </span>
                                </button>
                                <span class="mt-2">
                                    <span class="small fw-semibold">Separados</span>
                                    <span id="p_separados" class="badge bg-info rounded-pill">0</span>
                                </span>
                            </div>

                            <!-- Línea -->
                            <div class="timeline-line bg-secondary mt-3"></div>

                            <!-- Facturados -->
                            <div class="d-flex flex-column align-items-center flex-fill">
                                <button type="button"
                                    id="btn_facturados"
                                    class="btn btn-light border-0 filtros-estados p-0"
                                    data-bs-toggle="tooltip"
                                    title="Facturados">

                                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success text-white shadow-sm"
                                        style="width: 42px; height: 42px;">
                                        <i class="fa-solid fa-file-invoice-dollar"></i>
                                    </span>
                                </button>

                                <span class="mt-2">
                                    <span class="small fw-semibold">Facturados</span>
                                    <span id="p_facturados" class="badge bg-success rounded-pill">0</span>
                                </span>
                            </div>

                            <!-- Línea -->
                            <div class="timeline-line bg-secondary mt-3"></div>

                            <!-- Despachados -->
                            <div class="d-flex flex-column align-items-center flex-fill">
                                <button type="button"
                                    id="btn_despachados"
                                    class="btn btn-light border-0 filtros-estados p-0"
                                    data-bs-toggle="tooltip"
                                    title="Despachados">

                                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary text-white shadow-sm"
                                        style="width: 42px; height: 42px;">
                                        <i class="fa-solid fa-truck-fast"></i>
                                    </span>
                                </button>


                                <span class="mt-2">
                                    <span class="small fw-semibold">Despachados</span>
                                    <span id="p_despachados" class="badge bg-primary rounded-pill">0</span>
                                </span>

                            </div>

                        </div>
                        <!-- Información -->
                        <div class="d-flex justify-content-between align-items-center px-1">
                            <span class="small fw-semibold text-muted">
                                <i class="fa-solid fa-list-check me-1 text-primary"></i>
                                Flujo de pedidos
                            </span>

                            <span class="small text-muted">
                                <i class="fa-solid fa-rotate fa-spin me-1 text-success"></i>
                                Actualización automática cada 20 seg.
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="resultado-pedidos" class="mt-3"></div>
    </div>
</div>
</div>
<?php
$scriptsPagina = [
    '../controllers/cicloLogistico.js?' . rand()
];
include '../dependencies/footer.php';
?>