<?php
session_start();
$titulo = 'Nómina novedades';
include '../dependencies/header.php';
?>
<div class="card shadow-sm border-0">
    <div class="card-body p-4">

        <!-- Encabezado -->
        <div class="mb-4">
            <h5 class="fw-bold mb-1">
                <span class="text-muted fw-light">Nómina /</span>
                Novedades
            </h5>

            <p class="text-muted small mb-0">
                Registre y consulte novedades que afectan la liquidación de nómina de sus empleados.
            </p>
        </div>


        <!-- Navegación -->
        <ul class="nav nav-tabs" role="tablist">

            <li class="nav-item" role="presentation">
                <button type="button"
                    class="nav-link active"
                    data-bs-toggle="tab"
                    data-bs-target="#tabNovedadCrear"
                    role="tab"
                    aria-selected="true">

                    <i class="fa fa-plus-circle me-1"></i>
                    Registrar novedad

                </button>
            </li>

            <li class="nav-item" role="presentation">
                <button type="button"
                    class="nav-link"
                    data-bs-toggle="tab"
                    data-bs-target="#tabNovedadLista"
                    role="tab"
                    aria-selected="false">

                    <i class="fa fa-list me-1"></i>
                    Novedades registradas

                </button>
            </li>

        </ul>


        <div class="tab-content pt-4">

            <!-- ========================================================= -->
            <!-- REGISTRO -->
            <!-- ========================================================= -->

            <div class="tab-pane fade show active"
                id="tabNovedadCrear"
                role="tabpanel">

                <form id="FormNovedades"
                    class="needs-validation"
                    novalidate>

                    <input type="hidden"
                        id="txtIdNovedad"
                        name="id"
                        value="0">

                    <input type="hidden"
                        id="txtIdConcepto"
                        name="id_concepto"
                        value="0">


                    <!-- ================================================= -->
                    <!-- EMPLEADO Y PERÍODO -->
                    <!-- ================================================= -->

                    <div class="border rounded-3 p-3 p-md-4 mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <div>
                                <h6 class="form-label text-primary fw-bold mb-0">
                                    Empleado y período
                                </h6>
                                <small class="text-muted">
                                    Indique a quién y en qué período aplica la novedad.
                                </small>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-lg-4 col-md-6">
                                <label for="slcContratoNovedad"
                                    class="form-label fw-semibold">
                                    Contrato / Empleado
                                </label>
                                <select class="form-select form-select-sm"
                                    id="slcContratoNovedad"
                                    name="id_contrato"
                                    required>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label for="slcAnioNovedad"
                                    class="form-label fw-semibold">
                                    Año
                                </label>
                                <select class="form-select form-select-sm"
                                    id="slcAnioNovedad">
                                    <option value="">
                                        Seleccione...
                                    </option>
                                </select>
                            </div>

                            <div class="col-lg-2 col-md-6">
                                <label for="slcPeriodoNovedad"
                                    class="form-label fw-semibold">
                                    Período
                                </label>
                                <select class="form-select form-select-sm"
                                    id="slcPeriodoNovedad"
                                    name="periodo"
                                    required>
                                    <option value="">
                                        Seleccione período...
                                    </option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label fw-semibold">
                                    Fecha de novedad
                                </label>
                                <input type="date"
                                    class="form-control form-control-sm"
                                    id="txtFechaNovedad"
                                    name="fecha_novedad"
                                    required>
                                <small class="text-muted">En quincenal define la quincena.</small>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label fw-semibold">
                                    Estado
                                </label>
                                <select class="form-select form-select-sm"
                                    id="slcEstadoNovedad"
                                    name="estado">
                                    <option value="ACTIVO">
                                        ACTIVO
                                    </option>
                                    <option value="INACTIVO">
                                        INACTIVO
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>


                    <!-- ================================================= -->
                    <!-- CONCEPTO -->
                    <!-- ================================================= -->

                    <div class="border rounded-3 p-3 p-md-4 mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <div>
                                <h6 class="form-label text-primary fw-bold mb-0">
                                    Concepto de la novedad
                                </h6>
                                <small class="text-muted">
                                    Seleccione el tipo y concepto que desea registrar.
                                </small>
                            </div>
                        </div>


                        <div class="row g-3">
                            <div class="col-lg-3 col-md-6">
                                <label for="slcTipoNovedad"
                                    class="form-label fw-semibold">
                                    Tipo de novedad
                                </label>
                                <select class="form-select form-select-sm"
                                    id="slcTipoNovedad"
                                    name="tipo"
                                    required>
                                    <option value="">
                                        Seleccione tipo...
                                    </option>
                                    <option value="DEVENGADO">
                                        DEVENGADO
                                    </option>
                                    <option value="DEDUCCION">
                                        DEDUCCIÓN
                                    </option>
                                </select>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <label for="slcTipoConceptoCategoria"
                                    class="form-label fw-semibold">
                                    Categoría
                                </label>
                                <select class="form-select form-select-sm"
                                    id="slcTipoConceptoCategoria"
                                    required>
                                    <option value="">
                                        Seleccione categoría...
                                    </option>
                                </select>
                            </div>

                            <div class="col-lg-6">
                                <label for="slcConceptoNovedad"
                                    class="form-label fw-semibold">
                                    Concepto predefinido DIAN
                                </label>
                                <select class="form-select form-select-sm"
                                    id="slcConceptoNovedad"
                                    name="id_concepto"
                                    required>
                                    <option value="">
                                        Seleccione concepto...
                                    </option>
                                </select>
                                <small class="text-muted">
                                    <i class="fa fa-info-circle me-1"></i>
                                    Seleccione un concepto para completar automáticamente la información.
                                </small>
                            </div>
                        </div>

                        <!-- Concepto autollenado -->
                        <div class="row g-3 mt-1">
                            <div class="col-lg-6">
                                <label for="txtConceptoNovedad"
                                    class="form-label fw-semibold">
                                    Concepto
                                </label>
                                <input type="text"
                                    class="form-control form-control-sm bg-light"
                                    id="txtConceptoNovedad"
                                    name="concepto"
                                    maxlength="120"
                                    readonly>
                                <small class="text-muted"
                                    id="infoConceptoDIAN">
                                </small>
                            </div>

                            <div class="col-lg-3">
                                <label for="txtPorcentajehNovedad"
                                    class="form-label fw-semibold">
                                    Porcentaje aplicado
                                </label>
                                <div class="input-group input-group-sm">
                                    <input type="number"
                                        step="0.01"
                                        class="form-control form-control-sm bg-light"
                                        id="txtPorcentajehNovedad"
                                        name="porcentaje_aplicado"
                                        value="0"
                                        readonly>
                                    <span class="input-group-text">
                                        %
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ================================================= -->
                    <!-- VALORES -->
                    <!-- ================================================= -->

                    <div class="border rounded-3 p-3 p-md-4 mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <div>
                                <h6 class="form-label text-primary fw-bold mb-0">
                                    Valores de la novedad
                                </h6>
                                <small class="text-muted">
                                    Indique las cantidades y valores correspondientes.
                                </small>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-lg-3 col-md-6">
                                <label for="txtCantidadNovedad"
                                    class="form-label fw-semibold">
                                    Cantidad
                                    <span class="text-muted fw-normal"
                                        id="labelUnidadCantidad">
                                        (Días)
                                    </span>
                                </label>
                                <input type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control form-control-sm"
                                    id="txtCantidadNovedad"
                                    name="cantidad"
                                    value="1"
                                    required>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <label for="txtValorUnitarioNovedad"
                                    class="form-label fw-semibold">
                                    Valor unitario
                                </label>
                                <input type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control form-control-sm"
                                    id="txtValorUnitarioNovedad"
                                    name="valor_unitario"
                                    value="0"
                                    required>
                                <small class="text-muted" id="ayudaValorUnitario"></small>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <label for="txtTotalNovedad"
                                    class="form-label fw-semibold">
                                    Total
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">
                                        $
                                    </span>
                                    <input type="number"
                                        step="0.01"
                                        class="form-control form-control-sm bg-light fw-semibold"
                                        id="txtTotalNovedad"
                                        name="total"
                                        value="0.00"
                                        readonly>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <label for="txtValorFinalNovedad"
                                    class="form-label fw-semibold">
                                    Valor final
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-primary text-white">
                                        $
                                    </span>
                                    <input type="number"
                                        step="0.01"
                                        class="form-control form-control-sm fw-bold"
                                        id="txtValorFinalNovedad"
                                        name="valor_final"
                                        value="0.00"
                                        readonly>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- ================================================= -->
                    <!-- INFORMACIÓN ADICIONAL -->
                    <!-- ================================================= -->

                    <div class="border rounded-3 p-3 p-md-4 mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <div>
                                <h6 class="form-label text-primary fw-bold mb-0">
                                    Información adicional
                                </h6>
                                <small class="text-muted">
                                    Estos campos aparecerán cuando el concepto lo requiera.
                                </small>
                            </div>
                        </div>


                        <!-- Incapacidad -->
                        <div class="row g-3"
                            id="rowIncapacidad"
                            style="display:none;">
                            <div class="col-lg-4">
                                <label class="form-label fw-semibold">
                                    Días de incapacidad
                                </label>
                                <input type="number"
                                    min="1"
                                    max="30"
                                    class="form-control form-control-sm"
                                    name="dias_incapacidad">
                            </div>

                            <div class="col-lg-8">
                                <label class="form-label fw-semibold">
                                    Documento de respaldo
                                </label>
                                <input type="text"
                                    class="form-control form-control-sm"
                                    name="documento_respaldo"
                                    placeholder="Referencia o URL del documento">
                                <small class="text-muted">
                                    Ejemplo: certificado-medico-123.pdf
                                </small>
                            </div>
                        </div>

                        <!-- Embargo -->
                        <div class="row g-3"
                            id="rowEmbargo"
                            style="display:none;">
                            <div class="col-12">
                                <label class="form-label fw-semibold">
                                    Número de orden de embargo
                                </label>
                                <input type="text"
                                    class="form-control form-control-sm"
                                    name="orden_embargo"
                                    placeholder="Referencia judicial">
                            </div>
                        </div>

                        <!-- Observaciones -->
                        <div class="row g-3 mt-1">
                            <div class="col-12">
                                <label class="form-label fw-semibold">
                                    Observaciones
                                </label>
                                <textarea class="form-control form-control-sm"
                                    name="observaciones"
                                    maxlength="255"
                                    rows="2"
                                    placeholder="Agregue una observación si es necesario..."></textarea>
                            </div>
                        </div>
                    </div>
                    <!-- Acciones -->
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button"
                            class="btn btn-sm btn-secondary"
                            id="btnNuevoNovedades">
                            <i class="fa fa-plus me-1"></i>
                            Nueva
                        </button>
                        <button type="submit"
                            class="btn btn-sm btn-primary">
                            <i class="fa fa-save me-1"></i>
                            Guardar novedad
                        </button>

                    </div>

                </form>

            </div>


            <!-- ========================================================= -->
            <!-- LISTADO -->
            <!-- ========================================================= -->

            <div class="tab-pane fade"
                id="tabNovedadLista"
                role="tabpanel">
                <div class="border rounded-3">
                    <!-- Encabezado listado -->
                    <div class="p-3 border-bottom">
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-4 col-md-6">
                                <label for="filtroContratoNovedades"
                                    class="form-label fw-semibold mb-1">
                                    Contrato / Empleado
                                </label>
                                <select class="form-select form-select-sm"
                                    id="filtroContratoNovedades">
                                    <option value="0">
                                        Todos los contratos
                                    </option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label for="filtroPeriodoNovedades"
                                    class="form-label fw-semibold mb-1">
                                    Período
                                </label>
                                <input type="month"
                                    class="form-control form-control-sm"
                                    id="filtroPeriodoNovedades">
                            </div>

                            <div class="col-lg-4 col-md-8">
                                <label for="filtroNovedades"
                                    class="form-label fw-semibold mb-1">
                                    Buscar
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white">
                                        <i class="fa fa-search text-muted"></i>
                                    </span>
                                    <input type="text"
                                        class="form-control form-control-sm"
                                        id="filtroNovedades"
                                        placeholder="Concepto, documento, nombre..."
                                        autocomplete="off">
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-4">
                                <button type="button"
                                    id="btnBuscarNovedades"
                                    class="btn btn-sm btn-primary w-100">
                                    <i class="fa fa-search me-1"></i>
                                    Buscar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Resultados -->
                    <div class="table-responsive">
                        <div id="resultNovedades">
                            <!-- La tabla se carga dinámicamente aquí -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<?php
$scriptsPagina = [
    '../controllers/nominaNovedades.js?' . rand()
];
include '../dependencies/footer.php';
?>