<?php
session_start();

$titulo = 'configuraciones';
include '../dependencies/header.php';
?>
<div class="card" style="height: auto;">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="fw-bold py-0 mb-0">
        <span class="text-muted fw-light">Administración / </span>Configuraciones
      </h5>
      <!-- Botón Nueva -->
      <button class="btn btn-sm btn-primary d-none" id="btnNueva" title="Nueva configuración">
        <i class="fa-solid fa-plus me-1"></i>Nueva Configuración
      </button>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="row mb-4">
      <div class="col-md-6 mb-2">
        <label class="form-label">Buscar por Módulo</label>
        <select id="filtroModulo" class="form-select form-select-sm">
          <option value="">Todos los módulos</option>
        </select>
      </div>
      <div class="col-md-6 mb-2">
        <label class="form-label">Buscar por Descripción o Clave</label>
        <input type="text" class="form-control form-control-sm" id="filtroBusqueda" placeholder="Escribe para filtrar...">
      </div>
    </div>
    <!-- Alerta informativa fija -->
    <div class="alert alert-warning  fade show mb-4" role="alert">
      <div class="d-flex align-items-start">
        <i class="fas fa-info-circle me-3 mt-1"></i>
        <div>
          <strong>Importante:</strong> Una vez aplicada cualquier configuración, debes <strong>cerrar sesión</strong> e <strong>ingresar nuevamente</strong> para que los cambios tomen efecto correctamente.
        </div>
      </div>
    </div>


    <!-- Tabla de configuraciones -->
    <div style="overflow-x: auto;">
      <div id="result"></div>
    </div>
  </div>
</div>

<!-- / Layout wrapper -->

<!-- Formulario Modal -->
<div class="modal fade" id="modalConfiguracion" tabindex="-1" aria-labelledby="formTitulo" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="formTitulo">Nueva Configuración</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formConfig" class="needs-validation" novalidate>
          <input type="hidden" id="idConfig" value="0">

          <div class="mb-3">
            <label for="idModuloSelect" class="form-label">Módulo <span class="text-danger">*</span></label>
            <select id="idModuloSelect" class="form-select form-select-sm" required>
              <option value="0">🌐 Global (Todos los módulos)</option>
            </select>
            <small class="text-muted d-block mt-1">Selecciona 'Global' para aplicar a todos los módulos</small>
            <div class="invalid-feedback">Por favor selecciona un módulo o Global.</div>
          </div>

          <div class="mb-3">
            <label for="descripcionConfig" class="form-label">Descripción <span class="text-danger">*</span></label>
            <textarea class="form-control form-control-sm" id="descripcionConfig" placeholder="Describe esta configuración" rows="3" required></textarea>
            <div class="invalid-feedback">La descripción es requerida.</div>
          </div>

          <div class="mb-3">
            <label for="claveConfig" class="form-label">Clave <span class="text-danger">*</span></label>
            <input type="text" class="form-control form-control-sm" id="claveConfig" placeholder="Ej: HABILITAR_FEATURE_X" required>
            <small class="text-muted d-block mt-1">Identificador único de la configuración</small>
            <div class="invalid-feedback">La clave es requerida.</div>
          </div>

          <div class="mb-3">
            <label for="valorConfig" class="form-label">Valor <span class="text-danger">*</span></label>
            <input type="text" class="form-control form-control-sm" id="valorConfig" placeholder="Valor de la configuración" required>
            <small class="text-muted d-block mt-1">Puede ser texto, número o valores separados por comas</small>
            <div class="invalid-feedback">El valor es requerido.</div>
          </div>

          <div class="mb-3" id="divValidacionConfig">
            <label for="validacionConfig" class="form-label">Validación (JSON)</label>
            <textarea class="form-control form-control-sm" id="validacionConfig" rows="3" placeholder='{"opciones":["SI","NO"],"requerido":true}'></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
          <i class="fa-solid fa-times me-1"></i>Cancelar
        </button>
        <button type="submit" form="formConfig" class="btn btn-sm btn-primary">
          <i class="fa-solid fa-save me-1"></i>Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<?php

$scriptsPagina = [
  '../controllers/configuracion.js?' . rand()
];

include '../dependencies/footer.php';

?>