<?php
session_start();
$titulo = 'Parámetros exógena';
include '../dependencies/header.php';
?>
<style>
    .swal2-container {
        z-index: 20000 !important;
        font-size: 0.9rem !important;
    }

    .formato-item {
        border: none;
        border-radius: .5rem;
        margin-bottom: .35rem;
        transition: all .2s ease-in-out;
    }

    .formato-item:hover {
        background: rgba(var(--bs-primary-rgb), .08);
    }

    .formato-icon {
        color: var(--bs-primary);
        transition: color .2s ease;
    }

    .formato-item.active {
        background: var(--bs-primary) !important;
        border-color: var(--bs-primary) !important;
        color: var(--bs-white) !important;
    }

    .formato-item.active .formato-icon,
    .formato-item.active .formato-nombre {
        color: var(--bs-white) !important;
    }

    .formato-item.active .badge {
        background: var(--bs-white) !important;
        color: var(--bs-primary) !important;
    }

    .formato-item:focus {
        box-shadow: 0 0 0 .2rem rgba(var(--bs-primary-rgb), .25);
    }
</style>
<div class="card">
    <div class="card-body">
        <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Financiero /</span> Parámetros exógena</h5>
        <div class="row">

            <!-- Sidebar: lista de formatos -->
            <div class="col-lg-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header fw-bold form-label text-primary">
                        <i class="fa fa-folder-open me-2"></i>
                        Formatos
                    </div>
                    <div class="card-body p-2">
                        <div class="list-group list-group-flush" id="listaFormatos"></div>
                    </div>
                </div>
            </div>

            <!-- Panel principal -->
            <div class="col-9">

                <!-- Info del formato seleccionado -->
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3 align-items-end">

                            <div class="col-md-3">
                                <label for="selVigencia" class="form-label fw-bold text-primary">
                                    Vigencia
                                </label>
                                <select id="selVigencia" name="vigencia" class="form-select form-select-sm">
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-primary">
                                    Formato seleccionado
                                </label>

                                <div class="form-control form-control-sm bg-light d-flex align-items-center">
                                    <i class="fa-solid fa-file-lines text-primary me-2"></i>
                                    <span id="lblFormato" class="fw-semibold text-dark">
                                        Seleccione un formato
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label d-block">&nbsp;</label>
                                <button type="button"
                                    id="btnCopiarVigencia"
                                    class="btn btn-sm btn-outline-primary w-100">
                                    <i class="fa fa-copy me-1"></i>
                                    Copiar configuración
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulario de parámetros -->
                <div class="card mt-3">
                    <div class="card-header fw-bold text-primary">Configuración de parámetro</div>
                    <div class="card-body">
                        <form id="form-parametro-exogena" class="needs-validation" novalidate>
                            <input type="hidden" id="param_id" name="id" />
                            <input type="hidden" id="inputFormato" name="formato" />
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="selConceptos" class="form-label">Concepto</label>
                                    <select id="selConceptos" name="concepto" class="form-select form-select-sm" required>
                                        <option value="">Seleccione un concepto</option>
                                    </select>
                                    <div class="invalid-feedback">Debe seleccionar un concepto.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="selectCuenta" class="form-label">Cuenta</label>
                                    <select id="selectCuenta" name="cuenta" class="form-select form-select-sm" required>
                                        <option value="">Seleccione una cuenta</option>
                                    </select>
                                    <div class="invalid-feedback">Debe seleccionar una cuenta.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="selectNaturaleza" class="form-label">Naturaleza</label>
                                    <select id="selectNaturaleza" name="naturaleza" class="form-select form-select-sm" required>
                                        <option value="">Seleccione naturaleza</option>
                                        <option value="D">Débito</option>
                                        <option value="C">Crédito</option>
                                        <option value="S">Saldo</option>
                                    </select>
                                    <div class="invalid-feedback">Debe seleccionar una naturaleza.</div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-3 d-grid">
                                    <button type="button" class="btn btn-primary btn-sm" id="btnGuardarParametro">
                                        <i class="fa fa-save me-1"></i>
                                        Guardar parámetro
                                    </button>
                                </div>
                                <div class="col-md-3 d-grid">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCancelarParametro">
                                        <i class="fa fa-times me-1"></i>
                                        Cancelar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabla de parámetros -->
                <div class="card mt-3">
                    <div class="card-header fw-bold text-primary">Parámetros registrados</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Concepto</th>
                                        <th>Cuenta</th>
                                        <th>Descripción</th>
                                        <th>Naturaleza</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyParametros"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php
$scriptsPagina = [
  '../controllers/parametrosExogena.js?' . rand()
];
include '../dependencies/footer.php';
?>