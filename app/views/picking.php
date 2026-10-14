<?php
session_start();
$titulo = 'Picking (Separación)';
include '../dependencies/header.php';
?>
<input type="hidden" id="id_pedido" value="" readonly />
<div class="card">
    <div class="card-body">
        <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Logística / </span> Picking (Separación)</h5>

        <div class="container-fluid vh-100 d-flex flex-column p-0 bg-light">
            <header class="sticky-top bg-white shadow-sm p-3 d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-bold d-block" style="font-size:0.65rem;">PEDIDO ACTUAL</small>
                    <div class="d-flex align-items-center gap-3">
                        <span id="codigo" class="fw-bold fs-5">---</span>
                        <span id="tiempo" class="fw-bold font-monospace text-primary">00:00:00</span>
                    </div>
                </div>
                <div class="dropdown">
                    <button class="btn btn-outline-primary" type="button" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li>
                            <h6 class="dropdown-header">Filtros de lista</h6>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" id="btnPedPendientes">
                                <i class="fa-regular fa-calendar text-primary me-2"></i>Pendientes
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" id="btnSinFinalizar">
                                <i class="fa-solid fa-list-check text-warning me-2"></i>Sin finalizar
                            </a>
                        </li>
                    </ul>
                </div>
            </header>

            <div class="bg-white border-bottom p-3">
                <div class="d-grid gap-2">
                    <button type="button" id="btnIniciar" class="btn btn-primary btn-lg  shadow-sm">
                        <i class="fa-solid fa-play me-2"></i>INICIAR SIGUIENTE
                    </button>
                    <button type="button" id="btnFinalizar" class="btn btn-danger btn-lg shadow-sm d-none">
                        <i class="fa-solid fa-check-double me-2"></i>FINALIZAR PEDIDO
                    </button>
                </div>
            </div>

            <div class="bg-white border-bottom p-3">
                <div class="row g-2 text-center">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-2">
                                <small class="text-muted fw-bold">PRODUCTOS</small>
                                <h5 class="mb-0 fw-bold" id="totalProductos">0</h5>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-2">
                                <small class="text-muted fw-bold">SEPARADOS</small>
                                <h5 class="mb-0 fw-bold" id="totalSeparados">0</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-2">
                                <small class="text-muted fw-bold">PENDIENTES</small>
                                <h5 class="mb-0 fw-bold" id="totalPendientes">0</h5>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="progress mt-3" style="height:10px;">
                    <div id="barProgreso" class="progress-bar bg-success" style="width:0%"></div>
                </div>
            </div>

            <main class="flex-grow-1 overflow-auto p-3 bg-white" id="main-content">

                <div id="empty-state" class="text-center mt-5">
                    <div class="card border-0 shadow-sm p-4 align-items-center">

                        <i class="fa-solid fa-boxes-stacked fs-1 text-primary opacity-50 mb-3"></i>

                        <h4 class="fw-bold text-dark">
                            ¿Listo para separar?
                        </h4>

                        <p class="text-muted mb-0">
                            Usa el botón iniciar o selecciona un pedido pendiente en opciones.
                        </p>

                    </div>
                </div>

                <div id="lista" class="row g-3"></div>

                <div id="seccion-notas" class="d-none mt-3">
                    <div class="alert alert-warning shadow-sm">
                        <h6 class="fw-bold">
                            <i class="fa-regular fa-note-sticky me-1"></i>Notas del Pedido
                        </h6>
                        <p id="txtNotasDisplay" class="small mb-0"></p>
                        <textarea id="txtNotas" class="d-none"></textarea>
                    </div>
                </div>

            </main>

            <div class="modal fade" id="ModalPendientes" tabindex="-1">
                <div class="modal-dialog modal-dialog-scrollable">
                    <div class="modal-content bg-light">
                        <div class="modal-header bg-white">
                            <h5 class="modal-title fw-bold" id="TituloModalPendientes">Pedidos</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-0" id="ContenidoPendientes"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<!-- Modal pendientes -->
<div class="modal fade modal-fullscreen-mobile" id="ModalPendientes" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content bg-light">
            <div class="modal-header bg-white border-bottom">
                <h5 class="modal-title fw-bold" id="TituloModalPendientes">Pedidos pendientes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" id="ContenidoPendientes"></div>
        </div>
    </div>
</div>
<?php
$scriptsPagina = [
    '../controllers/picking.js?' . rand()
];
include '../dependencies/footer.php';
?>