<?php
session_start();
$titulo = 'Nómina perfil contable';
include '../dependencies/header.php';
?>
<div class="card shadow-sm border-0">
    <div class="card-body p-4">

        <!-- Encabezado -->
        <div class="mb-4">
            <h5 class="fw-bold mb-1">
                <span class="text-muted fw-light">Nómina /</span>
                Perfil contable
            </h5>

            <p class="text-muted small mb-0">
                Configure las cuentas contables utilizadas para registrar los conceptos de nómina.
            </p>
        </div>


        <!-- Navegación -->
        <ul class="nav nav-tabs mb-4" role="tablist">

            <li class="nav-item" role="presentation">
                <button type="button"
                    class="nav-link active"
                    data-bs-toggle="tab"
                    data-bs-target="#tabPerfilCrear"
                    role="tab"
                    aria-selected="true">
                    <i class="fa fa-plus-circle me-1"></i>
                    Crear perfil
                </button>
            </li>

            <li class="nav-item" role="presentation">
                <button type="button"
                    class="nav-link"
                    data-bs-toggle="tab"
                    data-bs-target="#tabPerfilLista"
                    role="tab"
                    aria-selected="false">
                    <i class="fa fa-list me-1"></i>
                    Perfiles registrados
                </button>
            </li>

        </ul>


        <div class="tab-content">

            <!-- =========================================================
                 CREAR / EDITAR PERFIL
            ========================================================== -->
            <div class="tab-pane fade show active"
                id="tabPerfilCrear"
                role="tabpanel">

                <form id="FormPerfiles"
                    class="needs-validation"
                    novalidate>

                    <input type="hidden"
                        id="txtIdPerfil"
                        name="id"
                        value="0">


                    <!-- Datos generales -->
                    <div class="border rounded-3 p-3 p-md-4 mb-4">

                        <div class="mb-3">
                            <h6 class="form-label text-primary fw-bold mb-0">
                                Información del perfil
                            </h6>
                            <small class="text-muted">
                                Identifique y defina el estado del perfil contable.
                            </small>
                        </div>

                        <div class="row g-3">

                            <div class="col-lg-2 col-md-4">
                                <label class="form-label fw-semibold">Código</label>
                                <input type="text"
                                    class="form-control form-control-sm"
                                    name="codigo"
                                    maxlength="20"
                                    required>
                                <div class="invalid-feedback">
                                    Ingrese el código del perfil.
                                </div>
                            </div>

                            <div class="col-lg-5 col-md-8">
                                <label class="form-label fw-semibold">Descripción</label>
                                <input type="text"
                                    class="form-control form-control-sm"
                                    name="descripcion"
                                    maxlength="120"
                                    placeholder="Ej. Nómina administrativa"
                                    required>
                                <div class="invalid-feedback">
                                    Ingrese una descripción.
                                </div>
                            </div>

                            <div class="col-lg-2 col-md-4">
                                <label class="form-label fw-semibold">Estado</label>
                                <select class="form-select form-select-sm" name="estado">
                                    <option value="ACTIVO">ACTIVO</option>
                                    <option value="INACTIVO">INACTIVO</option>
                                </select>
                            </div>

                            <div class="col-lg-3 col-md-8 d-flex align-items-end justify-content-end gap-2">
                                <button type="button"
                                    class="btn btn-sm btn-outline-secondary"
                                    id="btnNuevoperfiles">
                                    <i class="fa fa-file me-1"></i>
                                    Nuevo
                                </button>

                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="fa fa-save me-1"></i>
                                    Guardar perfil
                                </button>
                            </div>

                        </div>

                    </div>


                    <!-- Configuración contable -->
                    <div class="border rounded-3 p-3 p-md-4">

                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <h6 class="fw-bold form-label text-primary mb-0">
                                    Configuración contable
                                </h6>
                                <small class="text-muted">
                                    Asigne las cuentas de cada concepto. Las marcadas con <span class="text-danger">*</span> son obligatorias para contabilizar; las de salud empleador, SENA e ICBF solo se usan si la empresa no está exonerada (art. 114-1 ET) o hay salarios desde 10 SMLMV.
                                </small>
                            </div>

                            <span class="badge bg-primary">Cuentas</span>
                        </div>


                        <div class="mb-4">

                            <div class="small fw-semibold text-muted text-uppercase mb-3">
                                Sueldos y salarios
                            </div>

                            <div class="row g-3">

                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label">
                                        Gasto sueldos / devengados <span class="text-danger">*</span>
                                        <small class="text-muted">(510506)</small>
                                    </label>

                                    <select class="form-select form-select-sm nomina-cuenta"
                                        name="cuenta_gasto_sueldo"
                                        data-placeholder="Seleccione una cuenta">
                                    </select>
                                </div>

                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label">
                                        Salarios por pagar <span class="text-danger">*</span>
                                        <small class="text-muted">(250505)</small>
                                    </label>

                                    <select class="form-select form-select-sm nomina-cuenta"
                                        name="cuenta_salario_por_pagar"
                                        data-placeholder="Seleccione una cuenta">
                                    </select>
                                </div>

                                <div class="col-lg-3 col-md-6 campo-opcional">
                                    <label class="form-label">
                                        Auxilio de transporte
                                        <small class="text-muted">(510527)</small>
                                    </label>
                                    <select class="form-select form-select-sm nomina-cuenta"
                                        name="cuenta_gasto_aux_transporte"
                                        data-placeholder="Igual a sueldos">
                                    </select>
                                </div>

                                <div class="col-lg-3 col-md-6 campo-opcional">
                                    <label class="form-label">
                                        Horas extras y recargos
                                        <small class="text-muted">(510515)</small>
                                    </label>
                                    <select class="form-select form-select-sm nomina-cuenta"
                                        name="cuenta_gasto_horas_extras"
                                        data-placeholder="Igual a sueldos">
                                    </select>
                                </div>

                                <div class="col-lg-3 col-md-6 campo-opcional">
                                    <label class="form-label">
                                        Incapacidades
                                        <small class="text-muted">(510524)</small>
                                    </label>
                                    <select class="form-select form-select-sm nomina-cuenta"
                                        name="cuenta_gasto_incapacidades"
                                        data-placeholder="Igual a sueldos">
                                    </select>
                                </div>

                                <div class="col-lg-3 col-md-6 campo-opcional">
                                    <label class="form-label">
                                        Retención en la fuente por pagar <span class="text-danger">*</span>
                                        <small class="text-muted">(236505)</small>
                                    </label>
                                    <select class="form-select form-select-sm nomina-cuenta"
                                        name="cuenta_retencion_fuente"
                                        data-placeholder="Seleccione una cuenta">
                                    </select>
                                </div>

                                <div class="col-lg-3 col-md-6 campo-opcional">
                                    <label class="form-label">
                                        Otras deducciones (libranzas, embargos…)
                                        <small class="text-muted">(2380xx)</small>
                                    </label>
                                    <select class="form-select form-select-sm nomina-cuenta"
                                        name="cuenta_otras_deducciones"
                                        data-placeholder="Seleccione una cuenta">
                                    </select>
                                    <small class="text-muted">Si el concepto tiene cuenta propia, se usa esa.</small>
                                </div>

                            </div>

                        </div>

                        <div class="pt-3 border-top mb-4">

                            <div class="small fw-semibold text-muted text-uppercase mb-3">
                                Seguridad social y parafiscales · Pasivo
                            </div>

                            <div class="row g-3">

                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label">
                                        Aportes EPS / Salud <span class="text-danger">*</span>
                                        <small class="text-muted">(237005)</small>
                                    </label>

                                    <select class="form-select form-select-sm nomina-cuenta"
                                        name="cuenta_pasivo_salud"
                                        data-placeholder="Seleccione una cuenta">
                                    </select>
                                </div>

                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label">
                                        Fondos de pensión <span class="text-danger">*</span>
                                        <small class="text-muted">(238030)</small>
                                    </label>

                                    <select class="form-select form-select-sm nomina-cuenta"
                                        name="cuenta_pasivo_pension"
                                        data-placeholder="Seleccione una cuenta">
                                    </select>
                                </div>

                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label">
                                        ARL / Riesgos <span class="text-danger">*</span>
                                        <small class="text-muted">(237006)</small>
                                    </label>

                                    <select class="form-select form-select-sm nomina-cuenta"
                                        name="cuenta_pasivo_arl"
                                        data-placeholder="Seleccione una cuenta">
                                    </select>
                                </div>

                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label">
                                        Caja de compensación <span class="text-danger">*</span>
                                        <small class="text-muted">(237010)</small>
                                    </label>

                                    <select class="form-select form-select-sm nomina-cuenta"
                                        name="cuenta_pasivo_caja"
                                        data-placeholder="Seleccione una cuenta">
                                    </select>
                                </div>

                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label">
                                        SENA
                                        <small class="text-muted">(237010)</small>
                                    </label>

                                    <select class="form-select form-select-sm nomina-cuenta"
                                        name="cuenta_pasivo_sena"
                                        data-placeholder="Seleccione una cuenta">
                                    </select>
                                </div>

                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label">
                                        ICBF
                                        <small class="text-muted">(237010)</small>
                                    </label>

                                    <select class="form-select form-select-sm nomina-cuenta"
                                        name="cuenta_pasivo_icbf"
                                        data-placeholder="Seleccione una cuenta">
                                    </select>
                                </div>

                            </div>

                        </div>

                        <div class="pt-3 border-top mb-4">

                            <div class="small fw-semibold text-muted text-uppercase mb-3">
                                Aportes del empleador · Gasto
                            </div>

                            <div class="row g-3">

                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label">
                                        Salud empleador
                                        <small class="text-muted">(510569)</small>
                                    </label>

                                    <select class="form-select form-select-sm nomina-cuenta"
                                        name="cuenta_gasto_salud_patronal"
                                        data-placeholder="Seleccione una cuenta">
                                    </select>
                                </div>

                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label">
                                        Pensión empleador <span class="text-danger">*</span>
                                        <small class="text-muted">(510570)</small>
                                    </label>

                                    <select class="form-select form-select-sm nomina-cuenta"
                                        name="cuenta_gasto_pension_patronal"
                                        data-placeholder="Seleccione una cuenta">
                                    </select>
                                </div>

                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label">
                                        ARL <span class="text-danger">*</span>
                                        <small class="text-muted">(510568)</small>
                                    </label>

                                    <select class="form-select form-select-sm nomina-cuenta"
                                        name="cuenta_gasto_arl"
                                        data-placeholder="Seleccione una cuenta">
                                    </select>
                                </div>

                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label">
                                        Caja de compensación <span class="text-danger">*</span>
                                        <small class="text-muted">(510572)</small>
                                    </label>

                                    <select class="form-select form-select-sm nomina-cuenta"
                                        name="cuenta_gasto_caja"
                                        data-placeholder="Seleccione una cuenta">
                                    </select>
                                </div>

                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label">
                                        SENA
                                        <small class="text-muted">(510575)</small>
                                    </label>

                                    <select class="form-select form-select-sm nomina-cuenta"
                                        name="cuenta_gasto_sena"
                                        data-placeholder="Seleccione una cuenta">
                                    </select>
                                </div>

                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label">
                                        ICBF
                                        <small class="text-muted">(510578)</small>
                                    </label>

                                    <select class="form-select form-select-sm nomina-cuenta"
                                        name="cuenta_gasto_icbf"
                                        data-placeholder="Seleccione una cuenta">
                                    </select>
                                </div>

                            </div>

                        </div>

                        <div class="pt-3 border-top mb-4">

                            <div class="small fw-semibold text-muted text-uppercase mb-3">
                                Prestaciones sociales · Gasto
                            </div>

                            <div class="row g-3">

                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label">
                                        Cesantías <span class="text-danger">*</span>
                                        <small class="text-muted">(510530)</small>
                                    </label>

                                    <select class="form-select form-select-sm nomina-cuenta"
                                        name="cuenta_gasto_cesantias"
                                        data-placeholder="Seleccione una cuenta">
                                    </select>
                                </div>

                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label">
                                        Intereses de cesantías <span class="text-danger">*</span>
                                        <small class="text-muted">(510533)</small>
                                    </label>

                                    <select class="form-select form-select-sm nomina-cuenta"
                                        name="cuenta_gasto_int_cesantias"
                                        data-placeholder="Seleccione una cuenta">
                                    </select>
                                </div>

                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label">
                                        Prima de servicios <span class="text-danger">*</span>
                                        <small class="text-muted">(510536)</small>
                                    </label>

                                    <select class="form-select form-select-sm nomina-cuenta"
                                        name="cuenta_gasto_prima"
                                        data-placeholder="Seleccione una cuenta">
                                    </select>
                                </div>

                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label">
                                        Vacaciones <span class="text-danger">*</span>
                                        <small class="text-muted">(510539)</small>
                                    </label>

                                    <select class="form-select form-select-sm nomina-cuenta"
                                        name="cuenta_gasto_vacaciones"
                                        data-placeholder="Seleccione una cuenta">
                                    </select>
                                </div>

                            </div>

                        </div>

                        <div class="pt-3 border-top mb-0">

                            <div class="small fw-semibold text-muted text-uppercase mb-3">
                                Prestaciones sociales · Pasivo provisión
                            </div>

                            <div class="row g-3">

                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label">
                                        Cesantías <span class="text-danger">*</span>
                                        <small class="text-muted">(261005)</small>
                                    </label>

                                    <select class="form-select form-select-sm nomina-cuenta"
                                        name="cuenta_pasivo_cesantias"
                                        data-placeholder="Seleccione una cuenta">
                                    </select>
                                </div>

                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label">
                                        Intereses de cesantías <span class="text-danger">*</span>
                                        <small class="text-muted">(261010)</small>
                                    </label>

                                    <select class="form-select form-select-sm nomina-cuenta"
                                        name="cuenta_pasivo_int_cesantias"
                                        data-placeholder="Seleccione una cuenta">
                                    </select>
                                </div>

                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label">
                                        Prima de servicios <span class="text-danger">*</span>
                                        <small class="text-muted">(261015)</small>
                                    </label>

                                    <select class="form-select form-select-sm nomina-cuenta"
                                        name="cuenta_pasivo_prima"
                                        data-placeholder="Seleccione una cuenta">
                                    </select>
                                </div>

                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label">
                                        Vacaciones <span class="text-danger">*</span>
                                        <small class="text-muted">(261020)</small>
                                    </label>

                                    <select class="form-select form-select-sm nomina-cuenta"
                                        name="cuenta_pasivo_vacaciones"
                                        data-placeholder="Seleccione una cuenta">
                                    </select>
                                </div>

                            </div>

                        </div>

                    </div>

                </form>

            </div>


            <!-- =========================================================
                 LISTADO
            ========================================================== -->
            <div class="tab-pane fade"
                id="tabPerfilLista"
                role="tabpanel">

                <div class="border rounded-3">

                    <!-- Toolbar -->
                    <div class="p-3 border-bottom">
                        <div class="row g-2 align-items-center">

                            <div class="col-lg-5">
                                <h6 class="fw-bold mb-0">Perfiles contables</h6>
                                <small class="text-muted">
                                    Consulte y administre los perfiles configurados.
                                </small>
                            </div>

                            <div class="col-lg-7">
                                <div class="input-group">
                                    <span class="input-group-text bg-white">
                                        <i class="fa fa-search text-muted"></i>
                                    </span>

                                    <input type="text"
                                        id="filtroPerfiles"
                                        class="form-control"
                                        placeholder="Buscar por código, descripción o cuenta de sueldo / salarios...">

                                    <button id="btnBuscarperfiles"
                                        type="button"
                                        class="btn btn-primary">
                                        <i class="fa fa-search me-1"></i>
                                        Buscar
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>


                    <!-- Resultados -->
                    <div class="table-responsive">
                        <div id="resultPerfiles">
                            <!-- Aquí se carga la tabla -->
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>
</div>



<?php
$scriptsPagina = [
    '../controllers/nominaPerfilContable.js?' . rand()
];
include '../dependencies/footer.php';
?>