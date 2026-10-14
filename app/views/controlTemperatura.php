<?php
session_start();

$titulo = 'Control de Temperatura y Humedad';
include '../dependencies/header.php';
?>
<style>
    .swal2-container {
        z-index: 20000 !important;
    }

    /* Gauge SVG Styles */
    .gauge-container {
        position: relative;
    }

    .gauge-container svg {
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
    }

    /* Sliders personalizados */
    .form-range {
        height: 8px;
        border-radius: 4px;
    }

    .form-range::-webkit-slider-thumb {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #696cff;
        cursor: pointer;
        border: 2px solid white;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        transition: all 0.2s ease;
    }

    .form-range::-webkit-slider-thumb:hover {
        background: #5b63cc;
        transform: scale(1.1);
    }

    .form-range::-moz-range-thumb {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #696cff;
        cursor: pointer;
        border: 2px solid white;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        transition: all 0.2s ease;
    }

    .form-range::-moz-range-thumb:hover {
        background: #5b63cc;
        transform: scale(1.1);
    }

    /* Colores para estados */
    .estado-soleado {
        background: linear-gradient(135deg, #fff3cd 0%, #ffe69c 100%);
    }

    .estado-nublado {
        background: linear-gradient(135deg, #e2e3e5 0%, #d3d4d6 100%);
    }

    .estado-lluvioso {
        background: linear-gradient(135deg, #cfe2ff 0%, #b6d4fe 100%);
    }

    /* Selector de fecha */
    #inputFecha {
        border-color: #696cff;
    }

    #inputFecha:focus {
        border-color: #5b63cc;
        box-shadow: 0 0 0 0.2rem rgba(105, 108, 255, 0.25);
    }

    /* Botones */
    .btn-guardar-registro,
    .btn-eliminar-registro {
        font-size: 0.875rem;
    }

    /* Encabezado */
    .page-header {
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #e9ecef;
    }

    .page-title {
        font-size: 1.75rem;
        font-weight: 600;
        color: #333;
    }

    .page-subtitle {
        color: #6c757d;
        margin-top: 0.5rem;
    }

    /* Contenedor principal */
    #registrosContainer {
        min-height: 400px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .card-body {
            padding: 1rem;
        }

        .page-title {
            font-size: 1.5rem;
        }
    }

    /* Botones pequeños para tabla */
    .btn-xs {
        padding: 0.25rem 0.5rem;
        font-size: 0.7rem;
        border-radius: 3px;
    }

    /* DataTable personalizado */
    .table-sm td {
        vertical-align: middle;
    }

    .table thead th {
        background-color: #f8f9fa;
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
    }

    .table tbody tr:hover {
        background-color: #f5f5f5;
    }
</style>
<div class="card">
    <div class="card-body">
        <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">POS /</span> Control de Temperatura y Humedad</h5>
        <div class="nav-align-top">
            <ul class="nav nav-tabs" role="tablist" id="tabs">
                <!-- Pestaña Registro Diario -->
                <li class="nav-item">
                    <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-registro" aria-controls="navs-registro" aria-selected="true">
                        <i class="fas fa-pencil me-2"></i>Registro Diario
                    </button>
                </li>
                <!-- Pestaña Historial -->
                <li class="nav-item">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-historial" aria-controls="navs-historial" aria-selected="false">
                        <i class="fas fa-history me-2"></i>Historial
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <!-- Tarjeta principal con tabs -->
                <!-- PESTAÑA 1: REGISTRO DIARIO -->
                <div class="tab-pane fade show active" id="navs-registro" role="tabpanel">
                    <!-- Selector de Fecha -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="inputFecha" class="form-label fw-bold">
                                <i class="fas fa-calendar me-2"></i>Seleccionar Fecha
                            </label>
                            <input type="date"
                                id="inputFecha"
                                class="form-control form-control-lg"
                                style="border-width: 2px;">
                            <small class="text-muted d-block mt-2">
                                Selecciona el día a consultar o editar registros
                            </small>
                        </div>

                    </div>

                    <!-- Notas de uso -->
                    <div class="row mt-3 mb-3">
                        <div class="col-md-6">
                            <div class="card border-start border-1 border-success">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="fas fa-lightbulb text-success me-2"></i>Consejos
                                    </h6>
                                    <ul class="small mb-0">
                                        <li>✓ Registra datos en los 3 horarios programados</li>
                                        <li>✓ Mantén rangos: Temp -50°C a 60°C, Humedad 0% a 100%</li>
                                        <li>✓ El gauge se actualiza en tiempo real</li>
                                        <li>✓ Todos los cambios se guardan en la base de datos</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-start border-1 border-warning">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>Validaciones
                                    </h6>
                                    <ul class="small mb-0">
                                        <li>⚠ Solo un registro por fecha y horario</li>
                                        <li>⚠ Temperatura: -50°C a 60°C</li>
                                        <li>⚠ Humedad: 0% a 100%</li>
                                        <li>⚠ Usa "Actualizar" para modificar registros existentes</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Contenedor de registros (3 columnas) -->
                    <div id="registrosContainer" class="mb-3">
                        <!-- Se llena dinámicamente con JavaScript -->
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                            <p>Cargando registros...</p>
                        </div>
                    </div>
                </div>

                <!-- PESTAÑA 2: HISTORIAL -->
                <div class="tab-pane fade" id="navs-historial" role="tabpanel">
                    <!-- Filtros de Búsqueda -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label for="fechaDesde" class="form-label fw-bold">
                                <i class="fas fa-calendar-check me-2"></i>Desde
                            </label>
                            <input type="date" id="fechaDesde" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label for="fechaHasta" class="form-label fw-bold">
                                <i class="fas fa-calendar-times me-2"></i>Hasta
                            </label>
                            <input type="date" id="fechaHasta" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label for="horaFiltro" class="form-label fw-bold">
                                <i class="fas fa-clock me-2"></i>Horario
                            </label>
                            <select id="horaFiltro" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <option value="9AM">9 AM</option>
                                <option value="1PM">1 PM</option>
                                <option value="5PM">5 PM</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button class="btn btn-primary btn-sm w-100" id="btnBuscarHistorial">
                                <i class="fas fa-search me-1"></i>Buscar
                            </button>
                        </div>
                    </div>

                    <!-- Tabla de Historial -->
                    <div style="overflow-x: auto;">
                        <div id="resultadoHistorial">
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-2x mb-3"></i>
                                <p>Usa los filtros para buscar registros</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


</div>


<?php

$scriptsPagina = [
    '../controllers/controlTemperatura.js?' . rand()
];

include '../dependencies/footer.php';

?>