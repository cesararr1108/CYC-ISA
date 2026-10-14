<?php
session_start();
$titulo = 'Contratos de nómina';
include '../dependencies/header.php';
?>
<div class="card shadow-sm border-0">
    <div class="card-body p-4">

        <!-- ========================================================= -->
        <!-- ENCABEZADO -->
        <!-- ========================================================= -->

        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">

            <div>
                <h5 class="fw-bold mb-1">
                    <span class="text-muted fw-light">Nómina /</span>
                    Contratos
                </h5>

                <p class="text-muted small mb-0">
                    Administre las condiciones laborales, salariales y de nómina de sus empleados.
                </p>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <button type="button"
                        class="btn btn-sm btn-outline-primary"
                        id="btnIncrementoSalarial"
                        title="Actualizar los salarios de los contratos activos al iniciar el año">

                    <i class="bx bx-trending-up me-1"></i>
                    Incremento salarial

                </button>

                <button type="button"
                        class="btn btn-sm btn-outline-primary"
                        id="btnConfigurarParametrosNomina">

                    <i class="bx bx-cog me-1"></i>
                    Parámetros de nómina

                </button>
            </div>

        </div>


        <!-- ========================================================= -->
        <!-- TABS -->
        <!-- ========================================================= -->

        <ul class="nav nav-tabs" role="tablist">

            <li class="nav-item" role="presentation">

                <button type="button"
                        class="nav-link active"
                        data-bs-toggle="tab"
                        data-bs-target="#tabContratoCrear"
                        role="tab"
                        aria-selected="true">

                    <i class="fa fa-plus me-1"></i>
                    Crear contrato

                </button>

            </li>

            <li class="nav-item" role="presentation">

                <button type="button"
                        class="nav-link"
                        data-bs-toggle="tab"
                        data-bs-target="#tabContratoLista"
                        role="tab"
                        aria-selected="false">

                    <i class="fa fa-list-ul me-1"></i>
                    Contratos registrados

                </button>

            </li>

        </ul>


        <div class="tab-content pt-4">


            <!-- ===================================================== -->
            <!-- CREAR / EDITAR CONTRATO -->
            <!-- ===================================================== -->

            <div class="tab-pane fade show active"
                 id="tabContratoCrear"
                 role="tabpanel">

                <form id="FormContratos"
                      class="needs-validation"
                      novalidate>

                    <input type="hidden"
                           id="txtIdContrato"
                           name="id"
                           value="0">

                    <input type="hidden"
                           id="txtIdTerceroContrato"
                           name="id_tercero"
                           value="0">


                    <!-- ================================================= -->
                    <!-- EMPLEADO -->
                    <!-- ================================================= -->

                    <div class="border rounded-3 p-3 p-md-4 mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <div>
                                <h6 class="form-label text-primary fw-bold mb-0">
                                    Empleado
                                </h6>
                                <small class="text-muted">
                                    Seleccione el empleado al que pertenece el contrato.
                                </small>
                            </div>
                        </div>


                        <div class="row g-3">

                            <div class="col-lg-6">

                                <label for="txtBusquedaEmpleadoContrato"
                                       class="form-label fw-semibold">

                                    Buscar empleado

                                </label>

                                <div class="input-group input-group-sm">

                                    <span class="input-group-text bg-white">
                                        <i class="bx bx-search text-muted"></i>
                                    </span>

                                    <input type="text"
                                           class="form-control form-control-sm"
                                           id="txtBusquedaEmpleadoContrato"
                                           placeholder="Documento o nombre del empleado..."
                                           autocomplete="off">

                                </div>

                                <small class="text-muted">
                                    Busque y seleccione un tercero registrado.
                                </small>

                            </div>


                            <div class="col-lg-3 col-md-6">

                                <label for="slcTipoDocContrato"
                                       class="form-label fw-semibold">

                                    Tipo de documento

                                </label>

                                <select class="form-select form-select-sm"
                                        id="slcTipoDocContrato"
                                        name="id_tipo_identificacion"
                                        required>
                                </select>

                            </div>


                            <div class="col-lg-3 col-md-6">

                                <label for="txtDocumentoContrato"
                                       class="form-label fw-semibold">

                                    Documento

                                </label>

                                <input class="form-control form-control-sm bg-light"
                                       id="txtDocumentoContrato"
                                       name="empleado_documento"
                                       required
                                       readonly>

                            </div>


                            <!-- Nombre completo -->
                            <div class="col-md-3">

                                <label class="form-label fw-semibold">
                                    Primer nombre
                                </label>

                                <input class="form-control form-control-sm bg-light"
                                       id="txtPrimerNombreContrato"
                                       name="primer_nombre"
                                       required
                                       readonly>

                            </div>


                            <div class="col-md-3">

                                <label class="form-label fw-semibold">
                                    Segundo nombre
                                </label>

                                <input class="form-control form-control-sm bg-light"
                                       id="txtSegundoNombreContrato"
                                       name="segundo_nombre"
                                       readonly>

                            </div>


                            <div class="col-md-3">

                                <label class="form-label fw-semibold">
                                    Primer apellido
                                </label>

                                <input class="form-control form-control-sm bg-light"
                                       id="txtPrimerApellidoContrato"
                                       name="primer_apellido"
                                       required
                                       readonly>

                            </div>


                            <div class="col-md-3">

                                <label class="form-label fw-semibold">
                                    Segundo apellido
                                </label>

                                <input class="form-control form-control-sm bg-light"
                                       id="txtSegundoApellidoContrato"
                                       name="segundo_apellido"
                                       readonly>

                            </div>

                        </div>

                    </div>


                    <!-- ================================================= -->
                    <!-- INFORMACIÓN DEL CONTRATO -->
                    <!-- ================================================= -->

                    <div class="border rounded-3 p-3 p-md-4 mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <div>
                                <h6 class="form-label text-primary fw-bold mb-0">
                                    Condiciones contractuales
                                </h6>
                                <small class="text-muted">
                                    Defina el cargo, tipo de contrato, jornada y vigencia.
                                </small>
                            </div>
                        </div>


                        <div class="row g-3">

                            <div class="col-xl-3 col-md-6">

                                <label class="form-label fw-semibold">
                                    Cargo
                                </label>

                                <input class="form-control form-control-sm"
                                       id="cargo"
                                       name="cargo"
                                       placeholder="Ej. Analista contable"
                                       required>

                            </div>


                            <div class="col-xl-3 col-md-6">

                                <label class="form-label fw-semibold">
                                    Tipo de contrato
                                </label>

                                <select class="form-select form-select-sm"
                                        id="slcTipoContratoContrato"
                                        name="id_tipo_contrato"
                                        required>
                                </select>

                            </div>


                            <div class="col-xl-2 col-md-6">

                                <label class="form-label fw-semibold">
                                    Jornada
                                </label>

                                <select class="form-select form-select-sm"
                                        name="jornada">

                                    <option value="COMPLETA">
                                        Completa
                                    </option>

                                    <option value="PARCIAL">
                                        Parcial
                                    </option>

                                </select>

                            </div>


                            <div class="col-xl-2 col-md-6">

                                <label class="form-label fw-semibold">
                                    Fecha de inicio
                                </label>

                                <input type="date"
                                       class="form-control form-control-sm"
                                       name="fecha_inicio"
                                       required>

                            </div>


                            <div class="col-xl-2 col-md-6">

                                <label class="form-label fw-semibold">
                                    Fecha de finalización
                                </label>

                                <input type="date"
                                       class="form-control form-control-sm"
                                       name="fecha_fin">

                                <small class="text-muted">
                                    Opcional
                                </small>

                            </div>


                            <div class="col-xl-3 col-md-6">

                                <label class="form-label fw-semibold">
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


                            <div class="col-xl-5 col-md-6">

                                <label class="form-label fw-semibold">
                                    Perfil contable
                                </label>

                                <select class="form-select form-select-sm"
                                        name="id_perfil_contable"
                                        id="slcPerfilContable"
                                        required>
                                </select>

                                <small class="text-muted">
                                    Determina las cuentas utilizadas en la contabilización de nómina.
                                </small>

                            </div>

                        </div>

                    </div>


                    <!-- ================================================= -->
                    <!-- SALARIO Y PAGO -->
                    <!-- ================================================= -->

                    <div class="border rounded-3 p-3 p-md-4 mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <div>
                                <h6 class="form-label text-primary fw-bold mb-0">
                                    Salario y forma de pago
                                </h6>
                                <small class="text-muted">
                                    Configure los valores salariales y la información para el pago.
                                </small>
                            </div>
                        </div>

                        <div class="row g-3">
                            <!-- Salario -->
                            <div class="col-xl-3 col-md-6">

                                <label for="txtSalarioBasicoContrato"
                                       class="form-label fw-semibold">

                                    Salario básico

                                </label>

                                <div class="input-group input-group-sm">

                                    <span class="input-group-text">
                                        $
                                    </span>

                                    <input type="text"
                                           inputmode="decimal"
                                           class="form-control form-control-sm text-end"
                                           id="txtSalarioBasicoContrato"
                                           name="salario_basico"
                                           required>

                                    <button type="button"
                                            class="btn btn-outline-primary"
                                            id="btnTraerSalarioConfiguracion"
                                            title="Traer salario desde la configuración">

                                        <i class="fa-solid fa-check-double"></i>

                                    </button>

                                </div>

                            </div>


                            <!-- Auxilio -->
                            <div class="col-xl-3 col-md-6">

                                <label for="txtAuxTransporteContrato"
                                       class="form-label fw-semibold">

                                    Auxilio de transporte
                                    <small class="text-muted fw-normal">(vacío = valor legal)</small>

                                </label>

                                <div class="input-group input-group-sm">

                                    <span class="input-group-text">
                                        $
                                    </span>

                                    <input type="text"
                                           inputmode="decimal"
                                           class="form-control form-control-sm text-end"
                                           id="txtAuxTransporteContrato"
                                           name="aux_transporte">

                                    <button type="button"
                                            class="btn btn-outline-primary"
                                            id="btnTraerAuxConfiguracion"
                                            title="Traer auxilio desde la configuración">

                                        <i class="fa-solid fa-check-double"></i>

                                    </button>

                                </div>

                            </div>


                            <!-- Método de pago -->
                            <div class="col-xl-3 col-md-6">

                                <label for="slcMetodoPagoContrato"
                                       class="form-label fw-semibold">

                                    Método de pago

                                </label>

                                <select class="form-select form-select-sm"
                                        id="slcMetodoPagoContrato"
                                        name="metodo_pago">

                                    <option value="">
                                        Seleccione...
                                    </option>

                                    <option value="10">
                                        10 - Efectivo
                                    </option>

                                    <option value="30">
                                        30 - Transferencia débito
                                    </option>

                                    <option value="31">
                                        31 - Transferencia crédito
                                    </option>

                                    <option value="42">
                                        42 - Consignación bancaria
                                    </option>

                                    <option value="45">
                                        45 - Transferencia crédito bancario
                                    </option>

                                </select>

                            </div>


                            <!-- Banco -->
                            <div class="col-xl-3 col-md-6">

                                <label for="txtBancoContrato"
                                       class="form-label fw-semibold">

                                    Banco
                                    <span class="text-danger d-none"
                                          id="spanBancoRequerido">
                                        *
                                    </span>

                                </label>

                                <input type="text"
                                       class="form-control form-control-sm"
                                       id="txtBancoContrato"
                                       name="banco"
                                       placeholder="Código DIAN del banco">

                                <small class="text-muted">
                                    Ej. 001, 007, 012
                                </small>

                            </div>


                            <!-- Tipo cuenta -->
                            <div class="col-xl-4 col-md-6">

                                <label for="slcTipoCuentaContrato"
                                       class="form-label fw-semibold">

                                    Tipo de cuenta
                                    <span class="text-danger d-none"
                                          id="spanTipoCuentaRequerido">
                                        *
                                    </span>

                                </label>

                                <select class="form-select form-select-sm"
                                        id="slcTipoCuentaContrato"
                                        name="tipo_cuenta">

                                    <option value="">
                                        Seleccione...
                                    </option>

                                    <option value="CC">
                                        CC - Corriente
                                    </option>

                                    <option value="AH">
                                        AH - Ahorros
                                    </option>

                                </select>

                            </div>


                            <!-- Número cuenta -->
                            <div class="col-xl-5 col-md-6">

                                <label for="txtNumeroCuentaContrato"
                                       class="form-label fw-semibold">

                                    Número de cuenta
                                    <span class="text-danger d-none"
                                          id="spanNumeroCuentaRequerido">
                                        *
                                    </span>

                                </label>

                                <input type="text"
                                       class="form-control form-control-sm"
                                       id="txtNumeroCuentaContrato"
                                       name="numero_cuenta"
                                       placeholder="Sin espacios ni guiones"
                                       maxlength="30">

                            </div>

                        </div>

                    </div>


                    <!-- ================================================= -->
                    <!-- AFILIACIONES -->
                    <!-- ================================================= -->

                    <div class="border rounded-3 p-3 p-md-4 mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <div>
                                <h6 class="form-label text-primary fw-bold mb-0">
                                    Afiliaciones
                                </h6>
                                <small class="text-muted">
                                    Entidades de seguridad social del empleado. Sus NIT se usan como tercero en los pasivos al contabilizar.
                                </small>
                            </div>
                        </div>


                        <div class="row g-3">

                            <div class="col-xl-3 col-md-6">

                                <label class="form-label fw-semibold">
                                    EPS <span class="text-danger">*</span>
                                </label>

                                <select class="form-select form-select-sm"
                                        id="slcEntidadSaludContrato"
                                        name="id_entidad_salud"
                                        required>
                                </select>

                            </div>


                            <div class="col-xl-3 col-md-6">

                                <label class="form-label fw-semibold">
                                    Fondo de pensiones
                                </label>

                                <select class="form-select form-select-sm"
                                        id="slcEntidadPensionContrato"
                                        name="id_entidad_pension">
                                </select>

                            </div>


                            <div class="col-xl-3 col-md-6">

                                <label class="form-label fw-semibold">
                                    Fondo de cesantías
                                </label>

                                <select class="form-select form-select-sm"
                                        id="slcEntidadCesantiasContrato"
                                        name="id_entidad_cesantias">
                                </select>

                            </div>


                            <div class="col-xl-3 col-md-6">

                                <label class="form-label fw-semibold">
                                    Caja de compensación
                                </label>

                                <select class="form-select form-select-sm"
                                        id="slcEntidadCajaContrato"
                                        name="id_entidad_caja">
                                </select>

                            </div>


                            <div class="col-xl-3 col-md-6">

                                <label class="form-label fw-semibold">
                                    ARL
                                </label>

                                <select class="form-select form-select-sm"
                                        id="slcEntidadArlContrato"
                                        name="id_entidad_arl">
                                </select>

                            </div>


                            <div class="col-xl-3 col-md-6">

                                <label for="slcClaseRiesgoArl" class="form-label fw-semibold">
                                    Clase de riesgo ARL
                                </label>

                                <select class="form-select form-select-sm"
                                        id="slcClaseRiesgoArl"
                                        name="clase_riesgo_arl">
                                    <option value="1">I – Mínimo (0,522 %)</option>
                                    <option value="2">II – Bajo (1,044 %)</option>
                                    <option value="3">III – Medio (2,436 %)</option>
                                    <option value="4">IV – Alto (4,350 %)</option>
                                    <option value="5">V – Máximo (6,960 %)</option>
                                </select>

                                <small class="text-muted">
                                    Decreto 1295/1994 · tarifa por actividad del cargo.
                                </small>

                            </div>

                        </div>

                    </div>


                    <!-- ================================================= -->
                    <!-- CONFIGURACIÓN ESPECIAL -->
                    <!-- ================================================= -->

                    <div class="border rounded-3 p-3 p-md-4 mb-4">

                        <div class="d-flex align-items-center mb-3">
                            <div>
                                <h6 class="form-label text-primary fw-bold mb-0">
                                    Configuración especial
                                </h6>
                                <small class="text-muted">
                                    Opciones que afectan el cálculo y tratamiento de la nómina.
                                </small>
                            </div>
                        </div>


                        <div class="row g-3 align-items-start">

                            <div class="col-lg-5">

                                <label for="slcSubtipoTrabajadorContrato"
                                       class="form-label fw-semibold">

                                    Subtipo de trabajador

                                </label>

                                <select class="form-select form-select-sm"
                                        id="slcSubtipoTrabajadorContrato"
                                        name="subtipo_trabajador">

                                    <option value="00">
                                        00 - No aplica
                                    </option>

                                    <option value="01">
                                        01 - Dependiente pensionado por vejez activo
                                    </option>

                                </select>

                            </div>


                            <div class="col-lg-7">

                                <div class="d-flex flex-column gap-3 pt-lg-4">

                                    <div class="form-check">

                                        <input class="form-check-input"
                                               type="checkbox"
                                               id="chkAltoRiesgoPension"
                                               name="alto_riesgo"
                                               value="1">

                                        <label class="form-check-label fw-semibold"
                                               for="chkAltoRiesgoPension">

                                            Alto riesgo de pensión
                                            <small class="text-muted fw-normal">(+10 puntos de pensión a cargo del empleador · Decreto 2090/2003)</small>

                                        </label>

                                    </div>


                                    <div class="form-check">

                                        <input class="form-check-input"
                                               type="checkbox"
                                               id="chkSalarioIntegral"
                                               name="salario_integral"
                                               value="1">

                                        <label class="form-check-label fw-semibold"
                                               for="chkSalarioIntegral">

                                            Salario integral

                                            <i class="bx bx-info-circle text-info ms-1"
                                               style="cursor: help;"
                                               title="Mínimo 13 SMLMV. Aportes sobre el 70 %; no genera prima, cesantías, intereses ni auxilio de transporte.">

                                            </i>

                                        </label>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- ================================================= -->
                    <!-- ACCIONES -->
                    <!-- ================================================= -->

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button"
                                class="btn btn-sm btn-secondary"
                                id="btnNuevocontratos">
                            <i class="bx bx-plus me-1"></i>
                            Nuevo
                        </button>
                        <button type="submit"
                                class="btn btn-sm btn-primary">
                            <i class="bx bx-save me-1"></i>
                            Guardar contrato
                        </button>
                    </div>
                </form>
            </div>


            <!-- ===================================================== -->
            <!-- LISTADO -->
            <!-- ===================================================== -->

            <div class="tab-pane fade"
                 id="tabContratoLista"
                 role="tabpanel">

                <div class="border rounded-3">

                    <div class="p-3 border-bottom">

                        <div class="row g-2 align-items-center">

                            <div class="col-lg-5">

                                <h6 class="fw-bold mb-0">
                                    Contratos registrados
                                </h6>

                                <small class="text-muted">
                                    Consulte y gestione los contratos de sus empleados.
                                </small>

                            </div>


                            <div class="col-lg-7">

                                <div class="input-group input-group-sm">

                                    <span class="input-group-text bg-white">
                                        <i class="bx bx-search text-muted"></i>
                                    </span>

                                    <input name="filtroContratos"
                                           id="filtroContratos"
                                           type="text"
                                           class="form-control form-control-sm"
                                           placeholder="Buscar por documento, empleado o cargo..."
                                           autocomplete="off">

                                    <button type="button"
                                            class="btn btn-primary"
                                            id="btnBuscarcontratos">

                                        <i class="bx bx-search me-1"></i>
                                        Buscar

                                    </button>

                                </div>

                            </div>

                        </div>

                    </div>


                    <div class="table-responsive">

                        <div id="resultContratos">

                            <!-- Resultados cargados dinámicamente -->

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- ============================================================= -->
    <!-- MODAL PARÁMETROS DE NÓMINA -->
    <!-- ============================================================= -->

    <div class="modal fade"
         id="modalParametrosNomina"
         tabindex="-1"
         aria-hidden="true">

        <div class="modal-dialog modal-xl modal-dialog-scrollable">

            <div class="modal-content">


                <!-- Modal header -->
                <div class="modal-header">

                    <div>

                        <h5 class="modal-title fw-bold mb-1">
                            Parámetros de nómina
                        </h5>

                        <small class="text-muted">
                            Configure los valores utilizados para el cálculo de nómina.
                        </small>

                    </div>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Cerrar">
                    </button>

                </div>


                <div class="modal-body">


                    <!-- ================================================= -->
                    <!-- CONTROLES DEL AÑO -->
                    <!-- ================================================= -->

                    <div class="border rounded-3 p-3 mb-4">

                        <div class="row g-3 align-items-end">

                            <div class="col-md-3">

                                <label for="slcAnioParametrosNomina"
                                       class="form-label fw-semibold">

                                    Año

                                </label>

                                <select class="form-select form-select-sm"
                                        id="slcAnioParametrosNomina">
                                </select>

                            </div>


                            <div class="col-md-9">

                                <div class="d-flex justify-content-md-end gap-2 flex-wrap">

                                    <button type="button"
                                            class="btn btn-sm btn-secondary"
                                            id="btnRecargarParametrosNomina">

                                        <i class="bx bx-refresh me-1"></i>
                                        Recargar

                                    </button>


                                    <button type="button"
                                            class="btn btn-sm btn-outline-secondary"
                                            id="btnCopiarParametrosNomina">

                                        <i class="bx bx-copy me-1"></i>
                                        Copiar año

                                    </button>


                                    <button type="button"
                                            class="btn btn-sm btn-primary"
                                            id="btnNuevoParametroNomina">

                                        <i class="bx bx-plus me-1"></i>
                                        Nuevo parámetro

                                    </button>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- ================================================= -->
                    <!-- FORMULARIO PARÁMETRO -->
                    <!-- ================================================= -->

                    <div class="border rounded-3 p-3 p-md-4 mb-4">

                        <div class="d-flex align-items-center mb-3">
                            <div>
                                <h6 class="form-label text-primary fw-bold mb-0">
                                    Parámetro
                                </h6>
                                <small class="text-muted">
                                    Cree o edite un parámetro de cálculo.
                                </small>
                            </div>
                        </div>


                        <form id="FormParametroNomina"
                              class="needs-validation"
                              novalidate>

                            <input type="hidden"
                                   id="txtIdParametroNomina"
                                   name="id"
                                   value="0">


                            <div class="row g-3">

                                <div class="col-xl-2 col-md-4">

                                    <label class="form-label fw-semibold">
                                        Año
                                    </label>

                                    <input type="number"
                                           class="form-control form-control-sm"
                                           id="txtAnioParametroNomina"
                                           name="anio"
                                           min="2020"
                                           max="2100"
                                           required>

                                </div>


                                <div class="col-xl-3 col-md-4">

                                    <label class="form-label fw-semibold">
                                        Clave
                                    </label>

                                    <input type="text"
                                           class="form-control form-control-sm"
                                           id="txtClaveParametroNomina"
                                           name="clave"
                                           placeholder="ej. smlv"
                                           required>

                                </div>


                                <div class="col-xl-4 col-md-4">

                                    <label class="form-label fw-semibold">
                                        Nombre
                                    </label>

                                    <input type="text"
                                           class="form-control form-control-sm"
                                           id="txtNombreParametroNomina"
                                           name="nombre"
                                           required>

                                </div>


                                <div class="col-xl-3 col-md-4">

                                    <label class="form-label fw-semibold">
                                        Valor
                                    </label>

                                    <input type="number"
                                           step="0.000001"
                                           class="form-control form-control-sm text-end"
                                           id="txtValorParametroNomina"
                                           name="valor"
                                           required>

                                </div>


                                <div class="col-xl-3 col-md-4">

                                    <label class="form-label fw-semibold">
                                        Tipo de valor
                                    </label>

                                    <select class="form-select form-select-sm"
                                            id="slcTipoParametroNomina"
                                            name="tipo"
                                            required>

                                        <option value="PORCENTAJE">
                                            Porcentaje
                                        </option>

                                        <option value="VALOR_PESOS">
                                            Valor en pesos
                                        </option>

                                        <option value="FACTOR">
                                            Factor
                                        </option>

                                        <option value="MULTIPLO_SMLV">
                                            Múltiplo SMLV
                                        </option>

                                        <option value="UVT">
                                            UVT
                                        </option>

                                    </select>

                                </div>


                                <div class="col-xl-3 col-md-4">

                                    <label class="form-label fw-semibold">
                                        Estado
                                    </label>

                                    <select class="form-select form-select-sm"
                                            id="slcEstadoParametroNomina"
                                            name="estado"
                                            required>

                                        <option value="ACTIVO">
                                            ACTIVO
                                        </option>

                                        <option value="INACTIVO">
                                            INACTIVO
                                        </option>

                                    </select>

                                </div>


                                <div class="col-xl-6">

                                    <label class="form-label fw-semibold">
                                        Descripción
                                    </label>

                                    <input type="text"
                                           class="form-control form-control-sm"
                                           id="txtDescripcionParametroNomina"
                                           name="descripcion"
                                           placeholder="Descripción del parámetro">

                                </div>

                            </div>


                            <div class="d-flex justify-content-end gap-2 mt-4">

                                <button type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        id="btnCancelarParametroNomina">

                                    <i class="bx bx-eraser me-1"></i>
                                    Limpiar
                                </button>


                                <button type="submit"
                                        class="btn btn-sm btn-primary">

                                    <i class="bx bx-save me-1"></i>
                                    Guardar parámetro

                                </button>

                            </div>

                        </form>

                    </div>


                    <!-- ================================================= -->
                    <!-- LISTADO DE PARÁMETROS -->
                    <!-- ================================================= -->

                    <div>

                        <div class="d-flex justify-content-between align-items-center mb-3">

                            <div>

                                <h6 class="fw-bold mb-0">
                                    Parámetros configurados
                                </h6>

                                <small class="text-muted">
                                    Valores disponibles para el año seleccionado.
                                </small>

                            </div>

                        </div>


                        <div class="table-responsive">

                            <div id="resultParametrosNomina">

                                <!-- Resultados cargados dinámicamente -->

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


    <!-- ============================================================= -->
    <!-- MODAL INCREMENTO SALARIAL -->
    <!-- ============================================================= -->

    <div class="modal fade" id="modalIncrementoSalarial" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold mb-1">Incremento salarial anual</h5>
                        <small class="text-muted">Actualiza el salario de los contratos activos según el SMLMV y el porcentaje definido por la empresa.</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <div class="border rounded-3 p-3 mb-3">
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-2 col-md-3">
                                <label class="form-label fw-semibold" for="txtIncAnio">Año</label>
                                <input type="number" class="form-control form-control-sm" id="txtIncAnio" min="2020" max="2100">
                            </div>
                            <div class="col-lg-2 col-md-3">
                                <label class="form-label fw-semibold" for="txtIncFecha">Fecha efectiva</label>
                                <input type="date" class="form-control form-control-sm" id="txtIncFecha">
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label class="form-label fw-semibold" for="slcIncModo">Salarios superiores al mínimo</label>
                                <select class="form-select form-select-sm" id="slcIncModo">
                                    <option value="PORCENTAJE">Incrementar un porcentaje</option>
                                    <option value="SOLO_MINIMO">No modificar (solo mínimos)</option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-4" id="colIncPorcentaje">
                                <label class="form-label fw-semibold" for="txtIncPorcentaje">Porcentaje</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" min="0" max="100" class="form-control text-end" id="txtIncPorcentaje">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-lg-1 col-md-4">
                                <label class="form-label fw-semibold" for="slcIncRedondeo">Redondeo</label>
                                <select class="form-select form-select-sm" id="slcIncRedondeo">
                                    <option value="1">$1</option>
                                    <option value="100">$100</option>
                                    <option value="1000">$1.000</option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-4">
                                <button type="button" class="btn btn-sm btn-primary w-100" id="btnIncPrevisualizar">
                                    <i class="bx bx-show me-1"></i> Previsualizar
                                </button>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-2" id="lblIncSmlv"></small>
                    </div>

                    <div id="incAvisos"></div>

                    <div class="row g-3 mb-3 d-none" id="incResumen">
                        <div class="col-md-3"><div class="border rounded p-2"><small class="text-muted">Contratos a actualizar</small><div class="fw-bold" id="incResContratos">0</div></div></div>
                        <div class="col-md-3"><div class="border rounded p-2"><small class="text-muted">Salario mínimo / porcentaje / integral</small><div class="fw-bold" id="incResTipos">0 / 0 / 0</div></div></div>
                        <div class="col-md-3"><div class="border rounded p-2"><small class="text-muted">Nómina básica actual</small><div class="fw-bold" id="incResActual">$ 0</div></div></div>
                        <div class="col-md-3"><div class="border border-primary rounded p-2"><small class="text-primary">Nómina básica nueva</small><div class="fw-bold text-primary" id="incResNueva">$ 0</div></div></div>
                    </div>

                    <div class="table-responsive">
                        <div id="incResultado">
                            <div class="text-center text-muted small py-4">Indique el año y pulse <strong>Previsualizar</strong>.</div>
                        </div>
                    </div>

                    <div id="incHistorial" class="mt-3"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-sm btn-primary" id="btnIncAplicar" disabled>
                        <i class="bx bx-check me-1"></i> Aplicar a seleccionados
                    </button>
                </div>
            </div>
        </div>
    </div>



<?php
$scriptsPagina = [
    '../controllers/nominaContratos.js?' . rand()
];
include '../dependencies/footer.php';
?>