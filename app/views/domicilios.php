<?php
session_start();
$titulo = 'Domicilios';
include '../dependencies/header.php';
?>
<input type="hidden" id="id_pedido" value="" readonly />
<div class="card">
    <div class="card-body">
        <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Logística / </span> Domicilios</h5>
        <div class="nav-align-top ">
            <ul class="nav nav-tabs" role="tablist" id="tabs">
                <li class="nav-item">
                    <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-creacion" aria-controls="navs-creacion" aria-selected="true">
                        <i class="fa-solid fa-motorcycle"></i> Creación
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-historial" aria-controls="navs-historial" aria-selected="false">
                        <i class="fa-solid fa-clock-rotate-left"></i> Historial
                    </button>
                </li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="navs-creacion" role="tabpanel">
                    <!-- Cabecera -->
                    <div class="bg-white">
                        <div class="row g-2 text-center">
                            <!-- FECHA ACTUAL -->
                            <div class="col-12 col-md-3">
                                <div class="card border-0 shadow-sm h-100 rounded-3">
                                    <div class="card-body py-2 px-2">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <small class="text-muted fw-bold" style="font-size: 0.65rem;">
                                                FECHA ACTUAL
                                            </small>
                                            <i class="fa-solid fa-calendar-days text-primary"></i>
                                        </div>
                                        <div class="input-group input-group-sm">
                                            <input
                                                type="date"
                                                class="form-control form-control-sm"
                                                id="fecha_actual">
                                            <button
                                                type="button"
                                                class="btn btn-primary"
                                                id="btn-consultar-fecha">
                                                <i class="fa-solid fa-magnifying-glass me-1"></i>
                                                Consultar
                                            </button>
                                        </div>
                                        <div class="text-muted mt-1" style="font-size: 9px;">
                                            Filtrar pedidos por fecha
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- PENDIENTES -->
                            <div class="col-12 col-md-3">
                                <div class="card border-0 shadow-sm h-100 rounded-3">
                                    <div class="card-body py-2 px-2">
                                        <div class="d-inline-flex align-items-center justify-content-center bg-warning rounded-circle p-2 mb-1">
                                            <i class="fa-solid fa-clock-rotate-left text-white"></i>
                                        </div>
                                        <div class="text-warning fw-bold" style="font-size: 10px;">
                                            PENDIENTES
                                        </div>
                                        <div class="fw-bold fs-5 text-secondary lh-1" id="totalPendientes">
                                            0
                                        </div>
                                        <small class="text-muted" style="font-size: 9px;">
                                            Por procesar
                                        </small>

                                    </div>
                                </div>
                            </div>
                            <!-- EN CAMINO -->
                            <div class="col-12 col-md-3">
                                <div class="card border-0 shadow-sm h-100 rounded-3">
                                    <div class="card-body py-2 px-2">
                                        <div class="d-inline-flex align-items-center justify-content-center bg-primary rounded-circle p-2 mb-1">
                                            <i class="fa-solid fa-motorcycle text-white"></i>
                                        </div>
                                        <div class="text-primary fw-bold" style="font-size: 10px;">
                                            EN CAMINO
                                        </div>
                                        <div class="fw-bold fs-5 text-secondary lh-1" id="totalEnCamino">
                                            0
                                        </div>
                                        <small class="text-muted" style="font-size: 9px;">
                                            En entrega
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <!-- ENTREGADOS -->
                            <div class="col-12 col-md-3">
                                <div class="card border-0 shadow-sm h-100 rounded-3">
                                    <div class="card-body py-2 px-2">
                                        <div class="d-inline-flex align-items-center justify-content-center bg-success rounded-circle p-2 mb-1">
                                            <i class="fa-solid fa-house-circle-check text-white"></i>
                                        </div>
                                        <div class="text-success fw-bold" style="font-size: 10px;">
                                            ENTREGADOS
                                        </div>
                                        <div class="fw-bold fs-5 text-secondary lh-1" id="totalEntregados">
                                            0
                                        </div>
                                        <small class="text-muted" style="font-size: 9px;">
                                            Completados
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Cuerpo -->
                    <div class="card border-0 shadow-sm rounded-3 mt-3">
                        <div class="card-body p-2">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white">
                                    <i class="fa-solid fa-magnifying-glass text-muted"></i>
                                </span>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="filtroDomicilios"
                                    placeholder="Buscar por cliente, factura, dirección, mensajero, teléfono..."
                                    autocomplete="off">

                                <button
                                    type="button"
                                    class="btn btn-outline-secondary"
                                    id="btn-limpiar-filtro"
                                    title="Limpiar búsqueda">
                                    <i class="fa-solid fa-delete-left"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- Detalles -->
                    <div id="listaDomicilios" class="row mt-3 g-3"></div>
                </div>
                <div class="tab-pane fade show" id="navs-historial" role="tabpanel">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label mb-1">Fecha inicial</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">
                                    <i class="fa-regular fa-calendar-check"></i>
                                </span>
                                <input type="date" class="form-control" id="fecha_ini_his">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label mb-1">Fecha final</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">
                                    <i class="fa-regular fa-calendar-check"></i>
                                </span>
                                <input type="date" class="form-control" id="fecha_fin_his">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <button class="btn btn-sm btn-primary w-100" id="btn_consultar_his">
                                <i class="fa-solid fa-magnifying-glass me-1"></i>
                                Consultar
                            </button>
                        </div>

                    </div>

                    <div id="his_result" class="mt-3">
                    </div>
                </div>
            </div>


        </div>
    </div>
</div>
<!-- Modal Listar Documentos  -->
<div class="modal fade" id="mdl-asignar-mensajero" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLongTitle">Asignar Mensajero</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="mdl-id-factura" value="" readonly />
                <!-- Tipo y número de factura -->
                <div class="row g-2 mb-3">

                    <div class="col-5">
                        <label class="form-label fw-semibold mb-1">
                            Tipo factura
                        </label>

                        <input type="text"
                            class="form-control form-control-sm"
                            id="mdl-tipo-factura"
                            readonly>
                    </div>

                    <div class="col-7">
                        <label class="form-label fw-semibold mb-1">
                            N.º factura
                        </label>

                        <input type="text"
                            class="form-control form-control-sm"
                            id="mdl-numero-factura"
                            readonly>
                    </div>

                </div>
                <!-- Mensajero -->
                <div class="mb-3">
                    <label class="form-label fw-semibold mb-1">
                        Mensajero
                    </label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">
                            <i class="bx bx-user"></i>
                        </span>

                        <select class="form-select form-select-sm" id="mdl-mensajero">
                            <option value="">Seleccione...</option>
                        </select>
                    </div>
                </div>
                <!-- Valor domicilio -->
                <div class="mb-3">
                    <label class="form-label fw-semibold mb-1">
                        Valor domicilio
                    </label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">
                            <i class="bx bx-dollar"></i>
                        </span>

                        <input type="number"
                            class="form-control form-control-sm"
                            id="mdl-valor-domicilio"
                            placeholder="0"
                            min="0"
                            step="100">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-primary" id="btn-guardar-mensajero">
                    <i class="fa-solid fa-floppy-disk me-1"></i>
                    Guardar
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal"> Cerrar </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detalle Domicilio  -->
<div class="modal fade" id="mdl-detalles" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLongTitle">Detalles del Domicilio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="timeline-domicilio">
                    <!-- ================================================= -->
                    <!-- FACTURA -->
                    <!-- ================================================= -->
                    <div class="d-flex">
                        <div class="d-flex flex-column align-items-center me-3">
                            <div class="bg-secondary text-white rounded-circle p-2">
                                <i class="fa-solid fa-file-invoice"></i>
                            </div>
                            <div class="border-start flex-grow-1"></div>
                        </div>
                        <div class="pb-4 flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h6 class="mb-0 fw-bold">
                                    Factura creada
                                </h6>
                            </div>
                            <div class="small text-muted mb-2">
                                <i class="fa-regular fa-clock me-1"></i>
                                <span id="timeline-fecha-factura">
                                    -
                                </span>
                            </div>
                            <!-- INFORMACIÓN FACTURA -->
                            <div class="card bg-light border-0">
                                <div class="card-body py-2 px-3">
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <small class="text-muted d-block">
                                                Tipo
                                            </small>
                                            <span
                                                class="fw-semibold"
                                                id="timeline-tipo">
                                                -
                                            </span>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted d-block">
                                                Número
                                            </small>
                                            <span
                                                class="fw-semibold"
                                                id="timeline-numero">
                                                -
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- TIEMPO HASTA DESPACHO -->
                            <div class="text-muted small mt-2">
                                <i class="fa-solid fa-arrow-down me-1"></i>
                                <span id="timeline-tiempo-factura-despacho">
                                    -
                                </span>
                                hasta despacho
                            </div>
                        </div>
                    </div>
                    <!-- ================================================= -->
                    <!-- DESPACHO -->
                    <!-- ================================================= -->
                    <div class="d-flex">
                        <div class="d-flex flex-column align-items-center me-3">
                            <div
                                id="timeline-icon-despacho"
                                class="bg-primary text-white rounded-circle p-2">
                                <i class="fa-solid fa-motorcycle"></i>
                            </div>
                            <div class="border-start flex-grow-1"></div>
                        </div>
                        <div class="pb-4 flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h6 class="mb-0 fw-bold">
                                    Domicilio despachado
                                </h6>
                            </div>
                            <div class="small text-muted mb-2">
                                <i class="fa-regular fa-clock me-1"></i>
                                <span id="timeline-inicio">
                                    -
                                </span>
                            </div>
                            <!-- INFORMACIÓN DESPACHO -->
                            <div class="card bg-light border-0">
                                <div class="card-body py-2 px-3">
                                    <div class="row g-2">
                                        <!-- MENSAJERO -->
                                        <div class="col-md-6">
                                            <small class="text-muted d-block">
                                                Mensajero
                                            </small>
                                            <div class="fw-semibold">
                                                <i class="fa-solid fa-user text-primary me-1"></i>
                                                <span id="timeline-mensajero">
                                                    -
                                                </span>
                                            </div>
                                        </div>
                                        <!-- USUARIO -->
                                        <div class="col-md-6">
                                            <small class="text-muted d-block">
                                                Registrado por
                                            </small>
                                            <div class="fw-semibold">
                                                <i class="fa-solid fa-user-pen text-primary me-1"></i>
                                                <span id="timeline-usuario">
                                                    -
                                                </span>
                                            </div>
                                        </div>
                                        <!-- VALOR DOMICILIO -->
                                        <div class="col-md-6">
                                            <small class="text-muted d-block">
                                                Valor domicilio
                                            </small>
                                            <div class="fw-bold text-success">
                                                <span id="timeline-flete">
                                                    $0
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- TIEMPO HASTA ENTREGA -->
                            <div class="text-muted small mt-2">
                                <i class="fa-solid fa-arrow-down me-1"></i>
                                <span id="timeline-tiempo-despacho-entrega">
                                    -
                                </span>
                                hasta entrega
                            </div>
                        </div>
                    </div>
                    <!-- ================================================= -->
                    <!-- ENTREGA -->
                    <!-- ================================================= -->
                    <div class="d-flex">
                        <div class="d-flex flex-column align-items-center me-3">
                            <div
                                id="timeline-icon-entrega"
                                class="bg-success text-white rounded-circle p-2">
                                <i class="fa-solid fa-house-circle-check"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h6 class="mb-0 fw-bold">
                                    Domicilio entregado
                                </h6>
                            </div>
                            <div class="small text-muted mb-2">
                                <i class="fa-regular fa-clock me-1"></i>
                                <span id="timeline-fin">
                                    Pendiente de entrega
                                </span>
                            </div>
                            <!-- TIEMPO DE ENTREGA -->
                            <div
                                id="timeline-tiempo-container"
                                class="alert alert-success py-2 px-3 mb-2">
                                <div class="d-flex align-items-center">
                                    <i class="fa-solid fa-stopwatch me-2"></i>
                                    <div>
                                        <small class="d-block text-muted">
                                            Tiempo despacho → entrega
                                        </small>
                                        <strong id="timeline-tiempo">
                                            -
                                        </strong>
                                    </div>
                                </div>
                            </div>
                            <!-- TIEMPO TOTAL -->
                            <div class="alert alert-primary py-2 px-3 mb-0">
                                <div class="d-flex align-items-center">
                                    <i class="fa-solid fa-hourglass-half me-2"></i>
                                    <div>
                                        <small class="d-block text-muted">
                                            Tiempo total del domicilio
                                        </small>
                                        <strong id="timeline-tiempo-total">
                                            -
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal"> Cerrar </button>
            </div>
        </div>
    </div>
</div>

<?php
$scriptsPagina = [
    '../controllers/domicilios.js?' . rand()
];
include '../dependencies/footer.php';
?>