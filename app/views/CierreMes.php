<?php
session_start();

$titulo = 'Cierre de mes';
include '../dependencies/header.php';
?>
<div class="card" style="height: 95vh;">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Inventarios /</span> Cierre de mes</h5>
    <div class="row g-4">

      <!-- CONTROL DE PERÍODOS -->
      <div class="col-lg-7">

        <div class="card h-100 shadow-sm border-0">

          <div class="card-header border-0 py-3">
            <div class="d-flex align-items-center">
              <div>
                <h5 class="mb-1">Control de períodos</h5>
                <small class="opacity-75">
                  Consulta y cierre del período contable
                </small>
              </div>
            </div>
          </div>

          <div class="card-body">

            <!-- INFORMACIÓN DEL PERÍODO -->
            <div class="mb-4">
              <div class="d-flex align-items-center mb-3">
                <h6 class="mb-0 fw-semibold">Información del período</h6>
              </div>
              <div class="row g-3">
                <!-- Fecha actual -->
                <div class="col-md-12">
                  <label
                    for="fecha_actual"
                    class="form-label small fw-semibold text-muted mb-1">
                    Fecha actual
                  </label>
                  <div class="input-group input-group-sm">
                    <span class="input-group-text">
                      <i class="fa-regular fa-calendar-check text-primary"></i>
                    </span>
                    <input
                      type="month"
                      class="form-control"
                      id="fecha_actual"
                      readonly>
                  </div>
                </div>
                <!-- Último período cerrado -->
                <div class="col-md-12">
                  <label
                    for="periodo_cerrado"
                    class="form-label small fw-semibold text-muted mb-1">
                    Último período cerrado
                  </label>
                  <div class="input-group input-group-sm">
                    <span class="input-group-text">
                      <i class="fa-solid fa-lock text-secondary"></i>
                    </span>
                    <input
                      type="month"
                      class="form-control"
                      id="periodo_cerrado"
                      readonly>
                  </div>
                </div>
              </div>
            </div>
            <hr class="my-4">
            <!-- MOVIMIENTOS -->
            <div>
              <div class="d-flex align-items-center mb-3">
                <h6 class="mb-0 fw-semibold">Movimientos</h6>
              </div>
              <div class="row g-3">
                <!-- Período actual -->
                <div class="col-md-12">
                  <label
                    for="periodo_actual"
                    class="form-label small fw-semibold text-muted mb-1">
                    Período actual
                  </label>
                  <div class="input-group input-group-sm">
                    <span class="input-group-text">
                      <i class="fa-solid fa-calendar-day text-primary"></i>
                    </span>
                    <input
                      type="month"
                      class="form-control"
                      id="periodo_actual"
                      readonly>
                  </div>
                  <div class="form-text">
                    Período donde se registran los movimientos.
                  </div>
                </div>
                <!-- Período a cerrar -->
                <div class="col-md-12">
                  <label
                    for="periodo_cierre"
                    class="form-label small fw-semibold text-muted mb-1">
                    Período nuevo
                  </label>
                  <div class="input-group input-group-sm">
                    <span class="input-group-text">
                      <i class="fa-solid fa-calendar-xmark text-warning"></i>
                    </span>
                    <input
                      type="month"
                      class="form-control"
                      id="periodo_cierre"
                      readonly>
                  </div>
                  <div class="form-text">
                    Nuevo período que se abrirá.
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- ACCIÓN -->
          <div class="card-footer bg-white border-top p-3">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
              <div class="d-flex align-items-center">
                <i class="fa-solid fa-triangle-exclamation text-warning me-2"></i>
                <div>
                  <div class="fw-semibold">
                    Cierre de período
                  </div>
                  <small class="text-muted">
                    Verifica los datos antes de continuar.
                  </small>
                </div>
              </div>
              <button
                type="button"
                class="btn btn-primary btn-sm px-4"
                id="btn-cierre">
                <i class="fa-solid fa-lock me-1"></i>
                Hacer cierre
              </button>
            </div>
          </div>
        </div>
      </div>
      <!-- INFORMACIÓN IMPORTANTE -->
      <div class="col-lg-5">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-header bg-warning-subtle border-0 py-3">
            <div class="d-flex align-items-center">
              <div>
                <h5 class="mb-1">Información importante</h5>
                <small class="text-muted">
                  Consideraciones antes de realizar el cierre
                </small>
              </div>
            </div>
          </div>
          <div class="card-body">
            <div class="alert border border-warning mb-4" role="alert">
              <div class="d-flex">
                <i class="fa-solid fa-triangle-exclamation text-warning me-3 mt-1"></i>
                <div>
                  <h6 class="alert-heading fw-bold text-warning">
                    Cierre mensual
                  </h6>
                  <p class="mb-0">
                    El cierre de mes consolida la información del período,
                    asegurando que los datos registrados estén actualizados
                    y correctamente procesados antes de iniciar el siguiente
                    período.
                  </p>
                </div>
              </div>
            </div>
            <!-- REGLAS -->
            <h6 class="fw-semibold mb-3">
              Consideraciones
            </h6>
            <ul class="list-group list-group-flush">
              <li class="list-group-item px-0 d-flex align-items-start">
                <i class="fa-solid fa-circle-check text-success me-2 mt-1"></i>
                <span>
                  El cierre debe realizarse el primer día de cada mes.
                </span>
              </li>
              <li class="list-group-item px-0 d-flex align-items-start">
                <i class="fa-solid fa-circle-check text-success me-2 mt-1"></i>
                <span>
                  El proceso debe ejecutarse una única vez por período.
                </span>
              </li>

              <li class="list-group-item px-0 d-flex align-items-start">
                <i class="fa-solid fa-circle-check text-success me-2 mt-1"></i>
                <span>
                  Verifica que todos los movimientos del período estén
                  correctamente registrados.
                </span>
              </li>
              <li class="list-group-item px-0 d-flex align-items-start">
                <i class="fa-solid fa-circle-check text-success me-2 mt-1"></i>
                <span>
                  Si el cierre ya fue realizado, el sistema mostrará una
                  alerta y pedirá confirmación antes de continuar.
                </span>
              </li>

            </ul>

          </div>

          <div class="card-footer bg-light border-0">

            <div class="d-flex align-items-center">

              <span class="badge text-bg-primary me-2">
                <i class="fa-solid fa-shield-halved me-1"></i>
                Importante
              </span>

              <small class="text-muted">
                Revisa la información antes de confirmar el cierre.
              </small>

            </div>

          </div>

        </div>

      </div>

    </div>
  </div>
</div>

<?php

$scriptsPagina = [
  '../controllers/CierreMes.js?' . rand()
];

include '../dependencies/footer.php';

?>