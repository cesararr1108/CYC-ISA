<?php
session_start();

$titulo = 'Autorización de pedidos';
include '../dependencies/header.php';
?>
<div class="card">
    <div class="card-body">
        <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Comercial / </span>Autorización de pedidos</h5>
        <div class="card shadow-sm">
            <div class="card-body">

                <div class="row g-2 align-items-end">

                    <!-- Fecha inicial -->
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label mb-1">Fecha inicial</label>
                        <input
                            type="date"
                            class="form-control form-control-sm"
                            id="fecha_inicial">
                    </div>

                    <!-- Fecha final -->
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label mb-1">Fecha final</label>
                        <input
                            type="date"
                            class="form-control form-control-sm"
                            id="fecha_final">
                    </div>

                    <!-- Estado -->
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label mb-1">Estado</label>
                        <select
                            class="form-select form-select-sm"
                            id="estado_autorizacion">
                            <option value="">Todos</option>
                            <option value="P">Pendiente</option>
                            <option value="A">Aprobada</option>
                            <option value="R">Rechazada</option>
                        </select>
                    </div>

                    <!-- Buscar -->
                    <div class="col-lg-2 col-md-6 d-grid">
                        <button
                            class="btn btn-primary btn-sm"
                            id="btnBuscarPedidos">
                            <i class="fa-solid fa-magnifying-glass me-1"></i>
                            Buscar
                        </button>
                    </div>

                    <!-- Limpiar -->
                    <div class="col-lg-2 col-md-6 d-grid">
                        <button
                            class="btn btn-outline-secondary btn-sm"
                            id="btnLimpiarPedidos">
                            <i class="fa-solid fa-eraser me-1"></i>
                            Limpiar
                        </button>
                    </div>

                </div>

                <div class="mt-3" id="result"></div>

            </div>
        </div>
    </div>
</div>

<div id="modal-container"></div>

<div class="modal fade" id="modalOpciones" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="">Opciones</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-md-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-body py-3">
                                <div class="row align-items-center">
                                    <!-- ID Pedido -->
                                    <div class="col-md-2 text-center border-end">
                                        <small class="text-muted d-block">
                                            Pedido
                                        </small>
                                        <span class="fw-bold fs-5 text-primary" id="mdl-id-pedido"></span>
                                    </div>
                                    <!-- ID Solicitud -->
                                    <div class="col-md-2 text-center border-end">
                                        <small class="text-muted d-block">
                                            Solicitud
                                        </small>
                                        <span class="fw-bold fs-5 text-secondary" id="mdl-id-solicitud"></span>
                                    </div>
                                    <!-- Cliente -->
                                    <div class="col-md-4">
                                        <small class="text-muted d-block">
                                            Cliente
                                        </small>
                                        <div class="fw-semibold text-dark" id="mdl-cliente"></div>
                                    </div>
                                    <!-- Acciones -->
                                    <div class="col-md-4 text-end">
                                        <button
                                            type="button"
                                            class="btn btn-success btn-sm px-3 me-2"
                                            id="btn-autorizar">
                                            <i class="fa-solid fa-circle-check me-1"></i>
                                            Autorizar
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-danger btn-sm px-3"
                                            id="btn-rechazar">
                                            <i class="fa-solid fa-circle-xmark me-1"></i>
                                            Rechazar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row g-2 mt-3">
                    <!-- Datos Solicitud -->
                    <div class="col-md-6">
                        <div class="card shadow-sm border-primary h-100">
                            <div class="card-header py-2 px-3">
                                <i class="fa-solid fa-paper-plane me-1"></i>
                                <small class="fw-bold">Datos de la Solicitud</small>
                            </div>
                            <div class="list-group list-group-flush small">
                                <div class="list-group-item py-2 px-3 d-flex justify-content-between">
                                    <span class="fw-semibold">
                                        <i class="fa-regular fa-calendar me-1 text-primary"></i>
                                        Fecha Solicitud
                                    </span>
                                    <span id="mdl-fecha-solicitud" class="text-muted"></span>
                                </div>
                                <div class="list-group-item py-2 px-3 d-flex justify-content-between">
                                    <span class="fw-semibold">
                                        <i class="fa-solid fa-user me-1 text-primary"></i>
                                        Usuario Solicita
                                    </span>
                                    <span id="mdl-usuario-solicita" class="text-muted"></span>
                                </div>
                                <div class="list-group-item py-2 px-3">
                                    <span class="fw-semibold d-block">
                                        <i class="fa-solid fa-comment me-1 text-primary"></i>
                                        Observaciones
                                    </span>
                                    <div id="mdl-observaciones" class="text-muted mt-1"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Datos Autorizacion -->
                    <div class="col-md-6">
                        <div class="card shadow-sm border-success h-100">
                            <div class="card-header py-2 px-3">
                                <i class="fa-solid fa-user-check me-1"></i>
                                <small class="fw-bold">Datos de la Autorización</small>
                            </div>
                            <div class="list-group list-group-flush small">
                                <div class="list-group-item py-2 px-3 d-flex justify-content-between">
                                    <span class="fw-semibold">
                                        <i class="fa-regular fa-calendar-check me-1 text-success"></i>
                                        Fecha Autorización
                                    </span>
                                    <span id="mdl-fecha-autorizacion" class="text-muted"></span>
                                </div>
                                <div class="list-group-item py-2 px-3 d-flex justify-content-between">
                                    <span class="fw-semibold">
                                        <i class="fa-solid fa-user-shield me-1 text-success"></i>
                                        Usuario Autoriza
                                    </span>
                                    <span id="mdl-usuario-autoriza" class="text-muted"></span>
                                </div>
                                <div class="list-group-item py-2 px-3">
                                    <span class="fw-semibold d-block">
                                        <i class="fa-solid fa-comment-dots me-1 text-success"></i>
                                        Observaciones
                                    </span>
                                    <div id="mdl-observaciones-autoriza" class="text-muted mt-1"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card shadow-sm border-0 mt-3">

                            <div class="card-header py-2 px-3">
                                <i class="fa-solid fa-list me-1"></i>
                                <small class="fw-bold">Cartera pendiente</small>
                            </div>

                            <div class="card-body py-2 px-3">

                                <div id="mdl-cartera-pendiente"
                                    class="table-responsive text-nowrap"
                                    style="max-height: 250px; overflow-y: auto;">
                                </div>

                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal"> Cerrar </button>
            </div>
        </div>
    </div>
</div>


<?php

$scriptsPagina = [
    '../controllers/autorizaPedidos.js?' . rand()
];

include '../dependencies/footer.php';

?>