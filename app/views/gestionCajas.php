<?php
session_start();

$titulo = 'gestión de cajas y bancos';
include '../dependencies/header.php';
?>
<div class="card" style="height: 95vh;">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Tesoreria /</span> Gestión de cajas y bancos</h5>
    <div class="nav-align-top ">
      <ul class="nav nav-tabs" role="tablist" id="tabs">
        <li class="nav-item">
          <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-busqueda" aria-controls="navs-busqueda" aria-selected="true"> <i class="fa-solid fa-file-circle-plus"></i> &nbsp;Gestión de cajas </button>
        </li>
        <li class="nav-item">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-conceptos" aria-controls="navs-conceptos" aria-selected="false"> <i class="fa-solid fa-list-check"></i> &nbsp;Conceptos de cajas </button>
        </li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane fade show active" id="navs-busqueda" role="tabpanel">
          <!-- Header -->
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <h4 class="fw-bold"></h4>
              <p class="text-muted">Controla tus movimientos de dinero con tus cuentas de banco, efectivo y tarjetas de crédito.</p>
            </div>
            <!-- Botón que abre el modal -->
            <button class="btn btn-primary" id="btn-add-caja"> <i class="fa fa-plus me-1"></i> Agregar caja/banco </button>
          </div>
          <div class="card card-custom p-3" id="result-cajas"> </div>
        </div>
        <div class="tab-pane fade" id="navs-conceptos" role="tabpanel">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <p class="text-muted mb-0">Administra los conceptos por bodega para ingresos y egresos de caja.</p>
            </div>
            <button class="btn btn-primary" id="btn-add-concepto"> <i class="fa fa-plus me-1"></i> Agregar concepto </button>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-md-10">
              <input type="text" class="form-control form-control-sm" id="filtro-conceptos" placeholder="Buscar por bodega, cuenta, concepto o descripción">
            </div>
            <div class="col-md-2 d-grid">
              <button class="btn btn-sm btn-outline-primary" id="btn-buscar-conceptos">Buscar</button>
            </div>
          </div>
          <div class="card card-custom p-3" id="result-conceptos"> </div>
        </div>
      </div>
      <!--    <div class="tab-content">  -->
    </div>

    <div class="modal fade" id="mdl-nueva-cuenta" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 shadow">
          <div class="modal-header border-0">
            <h5 class="modal-title fw-bold">Crear cuenta</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <p class="text-muted">Crea tus cuentas de banco, efectivo o tarjetas de crédito.</p>
            <form id="form-nueva-cuenta" class="needs-validation form-basico" novalidate>
              <div class="row g-3">
                <!-- Nombre cuenta -->
                <input type="text" class="form-control  form-control-sm" name="id" id="id" autocomplete="off" value="0" readonly>
                <div class="col-md-6">
                  <label class="form-label">Nombre de la cuenta <span class="text-danger">*</span></label>
                  <input type="text" class="form-control  form-control-sm" name="nombre" id="nombre" autocomplete="off" required>
                </div>
                <!-- Número cuenta -->
                <div class="col-md-6">
                  <label class="form-label">Número de cuenta <span class="text-danger">*</span></label>
                  <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cuenta" name="cuenta" required>
                  </select>
                </div>
                <!-- Saldo inicial -->
                <div class="col-md-6">
                  <label class="form-label">Saldo inicial <span class="text-danger">*</span></label>
                  <input type="number" class="form-control form-control-sm" name="saldo_inicial" autocomplete="off" required>
                </div>
                <!-- Descripción -->
                <div class="col-md-12">
                  <label class="form-label">Descripción <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="descripcion" id="descripcion" autocomplete="off" required>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer border-0">
            <button type="submit" form="form-nueva-cuenta" class="btn btn-primary w-100 fw-bold"> Crear </button>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="mdl-concepto-caja" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 shadow">
          <div class="modal-header border-0">
            <h5 class="modal-title fw-bold">Crear concepto</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <p class="text-muted">Define conceptos para clasificar ingresos y egresos por bodega.</p>
            <form id="form-concepto-caja" class="needs-validation form-basico" novalidate>
              <div class="row g-3">
                <input type="hidden" class="form-control form-control-sm" name="id" id="concepto-id" value="0" readonly>

                <div class="col-md-12">
                  <label class="form-label">Bodega <span class="text-danger">*</span></label>
                  <select class="form-select form-select-sm" id="concepto-id-bodega" name="id_bodega" required></select>
                </div>

                <div class="col-md-12">
                  <label class="form-label">Cuenta contable <span class="text-danger">*</span></label>
                  <select class="form-select form-select-sm" id="concepto-cuenta" name="cuenta" required></select>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Tipo <span class="text-danger">*</span></label>
                  <select class="form-select form-select-sm" id="concepto-tipo" name="tipo" required>
                    <option value="I">Ingreso</option>
                    <option value="E">Egreso</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Concepto <span class="text-danger">*</span></label>
                  <input type="text" class="form-control form-control-sm" id="concepto-nombre" name="concepto" maxlength="50" required>
                </div>

                <div class="col-md-12">
                  <label class="form-label">Descripción</label>
                  <input type="text" class="form-control form-control-sm" id="concepto-descripcion" name="descripcion" maxlength="150">
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer border-0">
            <button type="submit" form="form-concepto-caja" class="btn btn-primary w-100 fw-bold">Crear</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>


<?php

$scriptsPagina = [
  '../controllers/gestionCajas.js?' . rand()
];

include '../dependencies/footer.php';

?>