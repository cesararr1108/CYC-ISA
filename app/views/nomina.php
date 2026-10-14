<?php
session_start();
$titulo = 'Nómina liquidación';
include '../dependencies/header.php';
?>
<style>
    .dropdown-menu {
        z-index: 20000 !important;
    }
</style>
<div class="card shadow-sm border-0">
    <div class="card-body p-4">

        <!-- Encabezado -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
            <div>
                <h5 class="fw-bold mb-1">
                    <span class="text-muted fw-light">Nómina /</span>
                    Liquidar nómina
                </h5>
                <p class="text-muted mb-0 small">
                    Calcula y gestiona la nómina de las personas que integran tu equipo de trabajo.
                </p>
            </div>

            <div class="mt-3 mt-md-0">
                <span id="lblStatusDian" class="badge bg-light text-secondary border px-3 py-2">
                    <i class="fa fa-circle-notch fa-spin me-1"></i>
                    Consultando DIAN...
                </span>
            </div>
        </div>


        <!-- Período de liquidación -->
        <div class="border rounded-3 p-3 p-md-4 mb-4 bg-white">

            <div class="row g-3 align-items-end">

                <div class="col-lg-2 col-md-2">
                    <label for="slcAnioLiquidacion" class="form-label fw-semibold mb-1">
                        Año
                    </label>

                    <select class="form-select form-select-sm" id="slcAnioLiquidacion" required>
                        <option value="">Seleccione año...</option>
                    </select>
                </div>

                <div class="col-lg-2 col-md-2">
                    <label for="slcPeriodoLiquidacion" class="form-label fw-semibold mb-1">
                        Período
                    </label>

                    <select class="form-select form-select-sm" id="slcPeriodoLiquidacion" required>
                        <option value="">Seleccione período...</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-2">
                    <label for="slcTipoDocumentoContable" class="form-label fw-semibold mb-1">
                        Tipo documento
                    </label>
                    <select class="form-select form-select-sm" id="slcTipoDocumentoContable"></select>
                </div>
                <div class="col-lg-6">
                    <div class="d-flex flex-wrap justify-content-lg-end gap-2">
                        <button type="button"
                            class="btn btn-sm btn-primary"
                            id="btnCalcularNomina">
                            <i class="fa fa-calculator me-1"></i>
                            Calcular nómina
                        </button>

                        <button type="button"
                            class="btn btn-sm btn-outline-primary"
                            id="btnContabilizar">
                            <i class="fa fa-book me-1"></i>
                            Contabilizar y causar
                        </button>

                        <button type="button"
                            class="btn btn-sm btn-outline-primary"
                            id="btnColillasPeriodo"
                            title="Colillas de las nóminas contabilizadas (seleccionadas o todo el período)">
                            <i class="fa fa-receipt me-1"></i>
                            Colillas de pago
                        </button>

                        <button type="button"
                            class="btn btn-sm btn-outline-primary"
                            id="btnEgresoNomina"
                            title="Genera el comprobante de egreso que paga el neto de las nóminas contabilizadas">
                            <i class="fa fa-money-check-alt me-1"></i>
                            Egreso de pago
                        </button>

                        <button type="button"
                            class="btn btn-sm btn-outline-primary"
                            id="btnProcesarDianMasivo"
                            title="Enviar a la DIAN las nóminas contabilizadas">
                            <i class="fa fa-paper-plane me-1"></i>
                            Enviar a DIAN
                        </button>
                    </div>
                </div>

            </div>

            <!-- Período actual -->
            <div class="mt-3 pt-3 border-top d-flex flex-wrap justify-content-between gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa fa-calendar text-primary"></i>

                    <span class="text-muted small">
                        Período de liquidación:
                    </span>

                    <strong id="lblPeriodoActual" class="text-primary">
                        Seleccione un período
                    </strong>
                </div>
                <small class="text-muted">
                    <i class="fa fa-route me-1"></i>
                    Calcular → Contabilizar → Colillas / Egreso de pago → Enviar a DIAN
                </small>
            </div>

        </div>


        <!-- Resumen -->
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h6 class="fw-bold mb-0">Resumen de nómina</h6>
                <small class="text-muted">
                    Valores correspondientes al período seleccionado
                </small>
            </div>
        </div>

        <div class="row g-3 mb-4">

            <!-- Ingresos -->
            <div class="col-xl-3 col-md-6">
                <div class="border rounded-3 p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start">

                        <div>
                            <small class="text-muted d-block mb-1">
                                Ingresos
                            </small>

                            <strong class="fs-5" id="lblIngresosAdicionales">
                                $ 0.00
                            </strong>
                        </div>

                        <div class="text-primary">
                            <i class="fa fa-arrow-up"></i>
                        </div>

                    </div>
                </div>
            </div>


            <!-- Deducciones -->
            <div class="col-xl-3 col-md-6">
                <div class="border rounded-3 p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start">

                        <div>
                            <small class="text-muted d-block mb-1">
                                Deducciones
                            </small>

                            <strong class="fs-5" id="lblDeducciones">
                                $ 0.00
                            </strong>
                        </div>

                        <div class="text-primary">
                            <i class="fa fa-arrow-down"></i>
                        </div>

                    </div>
                </div>
            </div>


            <!-- Neto a pagar -->
            <div class="col-xl-3 col-md-6">
                <div class="border border-primary rounded-3 p-3 h-100 ">

                    <div class="d-flex justify-content-between align-items-start">

                        <div>
                            <small class="text-primary d-block mb-1 fw-semibold">
                                Neto a pagar
                            </small>

                            <strong class="fs-5 text-primary" id="lblNetoPagar">
                                $ 0.00
                            </strong>
                        </div>

                        <div class="text-primary">
                            <i class="fa fa-money-bill-wave"></i>
                        </div>

                    </div>

                </div>
            </div>


            <!-- Empleados -->
            <div class="col-xl-3 col-md-6">
                <div class="border rounded-3 p-3 h-100">

                    <div class="d-flex justify-content-between align-items-start">

                        <div>
                            <small class="text-muted d-block mb-1">
                                Empleados
                            </small>

                            <strong class="fs-5" id="lblEmpleados">
                                0
                            </strong>
                        </div>

                        <div class="text-primary">
                            <i class="fa fa-users"></i>
                        </div>

                    </div>

                </div>
            </div>

        </div>


        <!-- Nómina -->
        <div class="border rounded-3">

            <!-- Toolbar -->
            <div class="p-3 border-bottom">

                <div class="row g-2 align-items-center">

                    <div class="col-md-6">
                        <h6 class="fw-bold mb-0">
                            Empleados
                        </h6>

                        <small class="text-muted">
                            Marque empleados para calcular, contabilizar o imprimir colillas solo de ellos; sin selección se procesa todo el período.
                        </small>
                    </div>

                    <div class="col-md-6">

                        <div class="input-group input-group-sm">

                            <span class="input-group-text bg-white">
                                <i class="fa fa-search text-muted"></i>
                            </span>

                            <input type="text"
                                id="filtroEmpleados"
                                class="form-control"
                                placeholder="Buscar empleado...">

                        </div>

                    </div>

                </div>

            </div>


            <!-- Tabla -->
            <div class="table-responsive">

                <table class="table table-sm table-hover align-middle mb-0"
                    id="tablaPeriodoEmpleados">

                    <thead class="table-primary">

                        <tr>

                            <th class="ps-3" style="width: 40px;">
                                <input type="checkbox"
                                    class="form-check-input"
                                    id="chkSeleccionarTodos">
                            </th>

                            <th>Prefijo</th>
                            <th>Número</th>
                            <th>Nombre</th>

                            <th class="text-end">
                                Ingresos
                            </th>

                            <th class="text-end">
                                Deducciones
                            </th>

                            <th class="text-end">
                                Total a pagar
                            </th>

                            <th class="text-center">
                                Estado
                            </th>

                            <th class="text-center pe-3">
                                Acciones
                            </th>

                        </tr>

                    </thead>

                    <tbody id="cuerpoTablaEmpleados">

                        <tr>

                            <td colspan="9"
                                class="text-center text-muted py-5">

                                <div class="mb-2">
                                    <i class="fa fa-calendar-alt fa-2x text-muted opacity-50"></i>
                                </div>

                                <div class="fw-semibold">
                                    Seleccione un período
                                </div>

                                <small>
                                    Los empleados aparecerán aquí una vez seleccionado el período de liquidación.
                                </small>

                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </div>
</div>
<!-- Modal de Detalles de Nómina -->
<div class="modal fade" id="modalDetallesNomina" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title fs-6 fw-bold" id="tituloModalNomina">
                    DETALLES
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body" id="bodyModalNomina">
                <!-- contenido dinámico -->
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-primary d-none" id="btnColillaModal">
                    <i class="fa fa-receipt me-1"></i> Colilla de pago
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">
                    Cerrar
                </button>
            </div>

        </div>
    </div>
</div>


<!-- Modal egreso de pago de nómina -->
<div class="modal fade" id="modalEgresoNomina" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fs-6 fw-bold mb-0">Egreso de pago de nómina</h5>
                    <small class="text-muted">Paga el neto de las nóminas contabilizadas (<span id="lblEgresoAlcance"></span>)</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small mb-1" for="slcTipoDocEgreso">Tipo de documento</label>
                        <select class="form-select form-select-sm" id="slcTipoDocEgreso"></select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-semibold small mb-1" for="slcCuentaBancoEgreso">Cuenta de caja o banco</label>
                        <select class="form-select form-select-sm" id="slcCuentaBancoEgreso"></select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small mb-1" for="txtFechaEgreso">Fecha del pago</label>
                        <input type="date" class="form-control form-control-sm" id="txtFechaEgreso">
                    </div>
                    <div class="col-12">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="rdModoEgreso" id="rdEgresoTotal" value="TOTAL" checked>
                            <label class="form-check-label small" for="rdEgresoTotal">Un egreso por el total de la nómina</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="rdModoEgreso" id="rdEgresoEmpleado" value="EMPLEADO">
                            <label class="form-check-label small" for="rdEgresoEmpleado">Un egreso por empleado</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center border border-primary rounded p-2 mb-3">
                    <span class="small text-muted"><strong id="lblEgresoCantidad">0</strong> empleado(s) pendientes de pago</span>
                    <strong class="text-primary" id="lblEgresoTotal">$ 0</strong>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-primary">
                            <tr><th>Empleado</th><th>Forma de pago</th><th class="text-end">Neto</th></tr>
                        </thead>
                        <tbody id="tbodyEgreso"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm btn-primary" id="btnConfirmarEgreso">
                    <i class="fa fa-money-check-alt me-1"></i> Generar egreso
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$scriptsPagina = [
    '../controllers/nomina.js?' . rand()
];
include '../dependencies/footer.php';
?>