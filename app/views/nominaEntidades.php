<?php
session_start();
$titulo = 'Entidades de nómina';
include '../dependencies/header.php';
?>
<div class="card shadow-sm border-0">
    <div class="card-body">

        <!-- Encabezado -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
            <div>
                <h5 class="fw-bold mb-1">
                    <span class="text-muted fw-light">Nómina /</span> Entidades
                </h5>
                <small class="text-muted">
                    Administra las entidades asociadas a salud, pensión, cesantías, caja y ARL.
                </small>
            </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button type="button"
                        class="nav-link active"
                        data-bs-toggle="tab"
                        data-bs-target="#tabEntidadCrear"
                        role="tab"
                        aria-selected="true">
                    <i class="bx bx-plus-circle me-1"></i>
                    Crear entidad
                </button>
            </li>

            <li class="nav-item" role="presentation">
                <button type="button"
                        class="nav-link"
                        data-bs-toggle="tab"
                        data-bs-target="#tabEntidadLista"
                        role="tab"
                        aria-selected="false">
                    <i class="bx bx-list-ul me-1"></i>
                    Buscar / Listar
                </button>
            </li>
        </ul>

        <div class="tab-content pt-4">

            <!-- ===================================================== -->
            <!-- CREAR / EDITAR -->
            <!-- ===================================================== -->
            <div class="tab-pane fade show active"
                 id="tabEntidadCrear"
                 role="tabpanel">

                <form id="FormEntidades"
                      class="needs-validation"
                      novalidate>

                    <input type="hidden"
                           id="txtIdEntidad"
                           name="id"
                           value="0">

                    <input type="hidden"
                           id="txtIdTerceroEntidad"
                           name="id_tercero"
                           value="0">

                    <!-- Información de la entidad -->
                    <div class="card border mb-3">
                        <div class="card-header py-2">
                            <div class="d-flex align-items-center">
                                <div>
                                    <h6 class="form-label text-primary mb-0 fw-semibold">
                                        Información de la entidad
                                    </h6>
                                    <small class="text-muted">
                                        Define el tipo y código de la entidad.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="row g-3">

                                <div class="col-md-3">
                                    <label class="form-label">
                                        Tipo entidad
                                        <span class="text-danger">*</span>
                                    </label>

                                    <select class="form-select form-select-sm"
                                            name="tipo_entidad"
                                            required>
                                        <option value="SALUD">
                                            EPS / SALUD
                                        </option>
                                        <option value="PENSION">
                                            FONDO DE PENSIONES
                                        </option>
                                        <option value="FONDO_CESANTIAS">
                                            FONDO DE CESANTÍAS
                                        </option>
                                        <option value="CAJA_COMPENSACION">
                                            CAJA DE COMPENSACIÓN
                                        </option>
                                        <option value="ARL">
                                            ARL – RIESGOS LABORALES
                                        </option>
                                        <option value="SENA">
                                            SENA (PARAFISCAL)
                                        </option>
                                        <option value="ICBF">
                                            ICBF (PARAFISCAL)
                                        </option>
                                        <option value="DIAN">
                                            DIAN (RETENCIÓN EN LA FUENTE)
                                        </option>
                                    </select>

                                    <div class="invalid-feedback">
                                        Selecciona el tipo de entidad.
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">
                                        Código entidad
                                        <span class="text-danger">*</span>
                                    </label>

                                    <input type="text"
                                           class="form-control form-control-sm"
                                           name="codigo_entidad"
                                           placeholder="Ej. EPS001"
                                           required>

                                    <div class="invalid-feedback">
                                        Ingresa el código de la entidad.
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">
                                        Tercero asociado
                                        <span class="text-danger">*</span>
                                    </label>

                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light">
                                            <i class="bx bx-search"></i>
                                        </span>

                                        <input type="text"
                                               class="form-control"
                                               id="txtBusquedaEntidadTercero"
                                               placeholder="Buscar por NIT, documento o nombre..."
                                               autocomplete="off"
                                               required>

                                        <button type="button"
                                                class="btn btn-outline-primary"
                                                id="btnBuscarTerceroEntidad"
                                                title="Buscar tercero">
                                            <i class="bx bx-search"></i>
                                        </button>
                                    </div>

                                    <small class="text-muted">
                                        Busca y selecciona el tercero al que pertenece la entidad.
                                    </small>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Tercero seleccionado -->
                    <div class="card border mb-3"
                         id="cardTerceroEntidad">

                        <div class="card-header py-2">
                            <div class="d-flex align-items-center">
                                <div>
                                    <h6 class="form-label text-primary mb-0 fw-semibold">
                                        Tercero seleccionado
                                    </h6>
                                    <small class="text-muted">
                                        Información del tercero asociado.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="row g-3">

                                <div class="col-md-3">
                                    <label class="form-label">
                                        NIT / Documento
                                    </label>

                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">
                                            <i class="bx bx-id-card"></i>
                                        </span>

                                        <input type="text"
                                               class="form-control"
                                               id="txtNitEntidad"
                                               placeholder="Sin seleccionar"
                                               readonly>
                                    </div>
                                </div>

                                <div class="col-md-5">
                                    <label class="form-label">
                                        Nombre tercero
                                    </label>

                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">
                                            <i class="bx bx-user"></i>
                                        </span>

                                        <input type="text"
                                               class="form-control"
                                               id="txtNombreEntidad"
                                               placeholder="Sin seleccionar"
                                               readonly>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">
                                        Estado
                                    </label>

                                    <select class="form-select form-select-sm"
                                            name="estado">
                                        <option value="ACTIVO">
                                            ACTIVO
                                        </option>
                                        <option value="INACTIVO">
                                            INACTIVO
                                        </option>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">
                                        Código interno
                                    </label>

                                    <input type="text"
                                           class="form-control form-control-sm"
                                           id="txtIdEntidadVisual"
                                           value="Automático"
                                           readonly>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Información adicional -->
                    <div class="card border mb-3">
                        <div class="card-header py-2">
                            <div class="d-flex align-items-center">
                                <div>
                                    <h6 class="form-label text-primary mb-0 fw-semibold">
                                        Información adicional
                                    </h6>
                                    <small class="text-muted">
                                        Agrega información opcional para facilitar la gestión.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="row g-3">

                                <div class="col-md-10">
                                    <label class="form-label">
                                        Observaciones
                                    </label>

                                    <input type="text"
                                           class="form-control form-control-sm"
                                           name="observaciones"
                                           maxlength="250"
                                           placeholder="Escribe una observación si es necesario...">
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">
                                        Estado visual
                                    </label>

                                    <div class="pt-1">
                                        <span class="badge bg-label-success">
                                            <i class="bx bx-check-circle me-1"></i>
                                            Activo
                                        </span>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="d-flex flex-column flex-sm-row justify-content-end gap-2 pt-2">

                        <button type="button"
                                class="btn btn-sm btn-secondary"
                                id="btnNuevoentidades">
                            <i class="bx bx-refresh me-1"></i>
                            Limpiar
                        </button>

                        <button type="submit"
                                class="btn btn-sm btn-primary px-4">
                            <i class="bx bx-save me-1"></i>
                            Guardar entidad
                        </button>

                    </div>

                </form>
            </div>


            <!-- ===================================================== -->
            <!-- LISTADO -->
            <!-- ===================================================== -->
            <div class="tab-pane fade"
                 id="tabEntidadLista"
                 role="tabpanel">

                <div class="card border">
                    <div class="card-header bg-light py-3">

                        <div class="d-flex flex-column flex-md-row
                                    justify-content-between
                                    align-items-md-center gap-2">

                            <div>
                                <h6 class="mb-1 fw-semibold">
                                    Entidades registradas
                                </h6>

                                <small class="text-muted">
                                    Consulta y administra las entidades existentes.
                                </small>
                            </div>

                            <button type="button"
                                    class="btn btn-sm btn-primary"
                                    id="btnNuevaEntidadDesdeLista">
                                <i class="bx bx-plus me-1"></i>
                                Nueva entidad
                            </button>

                        </div>
                    </div>

                    <div class="card-body">

                        <!-- Filtros -->
                        <div class="row g-2 align-items-end">

                            <div class="col-md-9">
                                <label class="form-label">
                                    Buscar entidad
                                </label>

                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light">
                                        <i class="bx bx-search"></i>
                                    </span>

                                    <input type="text"
                                           id="filtroEntidades"
                                           class="form-control"
                                           placeholder="Código, NIT, tercero o tipo de entidad..."
                                           autocomplete="off">

                                    <button type="button"
                                            class="btn btn-outline-secondary"
                                            id="btnLimpiarFiltroEntidades"
                                            title="Limpiar búsqueda">
                                        <i class="bx bx-x"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <button type="button"
                                        id="btnBuscarentidades"
                                        class="btn btn-sm btn-primary w-100">
                                    <i class="bx bx-search me-1"></i>
                                    Buscar
                                </button>
                            </div>

                        </div>

                        <!-- Resultado -->
                        <div class="mt-4">

                            <div id="loadingEntidades"
                                 class="text-center py-4 d-none">
                                <div class="spinner-border spinner-border-sm text-primary"
                                     role="status">
                                </div>

                                <span class="ms-2 text-muted">
                                    Consultando entidades...
                                </span>
                            </div>

                            <div id="resultEntidades"></div>

                        </div>

                    </div>
                </div>

            </div>

        </div>
    </div>
</div>


<?php
$scriptsPagina = [
    '../controllers/nominaEntidades.js?' . rand()
];
include '../dependencies/footer.php';
?>