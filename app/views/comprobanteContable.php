<?php
session_start();
$titulo = 'Comprobante contable';
include '../dependencies/header.php';
?>
<input type="hidden" id="id_bodega_session" value="<?php if (!empty($_SESSION["Ses_id_bodega"])) {
                                                        echo $_SESSION["Ses_id_bodega"];
                                                    } else {
                                                        echo  '0';
                                                    } ?>" />
<input type="hidden" id="id_transaccion" readonly />
<div class="card" style="height: auto;">
    <div class="card-body">
        <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Financiero /</span> Movimientos y comprobantes</h5>
        <div class="nav-align-top">
            <ul class="nav nav-tabs" role="tablist" id="tabs">
                <li class="nav-item">
                    <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-crear" aria-controls="navs-crear" aria-selected="true">
                        <i class="fa-solid fa-file-circle-plus"></i> &nbsp;Crear
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-consultar" aria-controls="navs-consultar" aria-selected="true">
                        <i class="fa-solid fa-list-check"></i> &nbsp;Consultar
                    </button>
                </li>

                <li class="nav-item" style="flex:1;"></li>
                <li class="nav-item border-danger">
                    <button type="reset" class="btn btn-sm btn-outline-secondary" id="btnLimpiar">
                        <i class="fa-regular fa-file"></i>
                        <span class="d-none d-md-inline">Nuevo</span>
                    </button>
                    <button type="submit" class="btn btn-sm btn-outline-primary" id="btnGuardar">
                        <i class="fa-regular fa-floppy-disk"></i>
                        <span class="d-none d-md-inline">Guardar</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="btn-video-tutorial" title="Ver video tutorial">
                        <i class="fab fa-youtube"></i>
                        <span class="d-none d-md-inline">Tutorial</span>
                    </button>
                </li>
                </li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="navs-crear" role="tabpanel">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <i class="fa-solid fa-file-invoice"></i>
                                    Datos del comprobante
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Tipo</label>
                                            <select class="form-select form-select-sm" id="tipo" name="id_tipo_documento">
                                                <option value="">Seleccione...</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Fecha</label>
                                            <input type="date"
                                                class="form-control form-control-sm"
                                                id="fecha">
                                        </div>
                                    </div>
                                    <div class="row g-3 mt-2">
                                        <div class="col-md-12">
                                            <label class="form-label">Observación general</label>
                                            <textarea
                                                class="form-control form-control-sm"
                                                id="observacion"
                                                rows="2"
                                                maxlength="500"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <i class="fa-solid fa-book"></i>
                                    Movimiento contable
                                </div>
                                <div class="card-body">
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label class="form-label">Cuenta</label>
                                            <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cuenta" name="cuenta">
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="hidden" id="id_tercero" name="id_tercero" class="form-control form-control-sm" readonly value="0">
                                            <input type="hidden" id="nit" name="nit" class="form-control form-control-sm" readonly value="0">
                                            <label class="form-label">Tercero</label>
                                            <div class="input-group input-group-sm input-group-merge"> <span class="input-group-text"><i class="bx bx-search"></i></span>
                                                <input type="text" class="form-control" placeholder="Búsqueda de tercero..." aria-label="Búsqueda de tercero..." aria-describedby="basic-addon-search" id="txtBusquedaTercero">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Centro costo</label>
                                            <select class="form-select form-select-sm"
                                                id="centro_costo">
                                                <option value="">Seleccione...</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label class="form-label">Detalle movimiento</label>
                                            <input type="text"
                                                class="form-control form-control-sm"
                                                id="detalle"
                                                placeholder="Concepto del movimiento">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Débito</label>
                                            <input type="text"
                                                class="form-control form-control-sm text-end"
                                                id="debito"
                                                value="0">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Crédito</label>
                                            <input type="text"
                                                class="form-control form-control-sm text-end"
                                                id="credito"
                                                value="0">
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-12 text-end">
                                            <button
                                                type="button"
                                                class="btn btn-primary btn-sm"
                                                id="btnAgregarMovimiento">
                                                <i class="fa-solid fa-plus"></i>
                                                Agregar movimiento
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="card border-success">
                                <div class="card-body text-center">
                                    <small class="text-muted">
                                        Total Débitos
                                    </small>
                                    <h4 class="mb-0 text-success"
                                        id="totalDebito">
                                        0.00
                                    </h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-danger">
                                <div class="card-body text-center">
                                    <small class="text-muted">
                                        Total Créditos
                                    </small>
                                    <h4 class="mb-0 text-danger"
                                        id="totalCredito">
                                        0.00
                                    </h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-primary">
                                <div class="card-body text-center">
                                    <small class="text-muted">
                                        Diferencia
                                    </small>
                                    <h4 class="mb-0 text-primary"
                                        id="diferencia">
                                        0.00
                                    </h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>
                                <i class="fa-solid fa-list"></i>
                                Detalle del comprobante
                            </span>
                            <div>
                                <span
                                    class="badge bg-success d-none"
                                    id="badgeCuadrado">
                                    CUADRADO
                                </span>
                                <span
                                    class="badge bg-danger"
                                    id="badgeDescuadrado">
                                    DESCUADRADO
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table
                                    class="table table-hover table-bordered table-sm align-middle"
                                    id="tblMovimientos">
                                    <thead class="table-primary">
                                        <tr>
                                            <th>Cuenta</th>
                                            <th>Nombre cuenta </th>
                                            <th>Tercero </th>
                                            <th>CECO</th>
                                            <th>Detalle</th>
                                            <th class="text-end">Débito</th>
                                            <th class="text-end"> Crédito </th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                    <tfoot class="table-primary">
                                        <tr>
                                            <th colspan="5" class="text-end">Totales</th>
                                            <th class="text-end" id="tfootDebito">0.00</th>
                                            <th class="text-end" id="tfootCredito">0.00</th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade show" id="navs-consultar" role="tabpanel">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="basic-icon-default-fullname">Fecha inicial</label>
                                        <div class="input-group input-group-merge"> <span class="input-group-text"><i class="fa-regular fa-calendar-check"></i></span>
                                            <input
                                                type="date"
                                                class="form-control form-control-sm"
                                                id="fecha_inicial"
                                                aria-describedby="fecha_inicial" />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="basic-icon-default-fullname">Fecha final</label>
                                        <div class="input-group input-group-merge"> <span class="input-group-text "><i class="fa-regular fa-calendar-check"></i></span>
                                            <input
                                                type="date"
                                                class="form-control form-control-sm"
                                                id="fecha_final"
                                                aria-describedby="fecha_final" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <button class="btn btn-sm btn-primary" id="btnConsultar"> Consultar</button>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col">
                                    <div id="lista-comprobantes"></div>
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
    '../controllers/comprobanteContable.js?' . rand()
];
include '../dependencies/footer.php';
?>