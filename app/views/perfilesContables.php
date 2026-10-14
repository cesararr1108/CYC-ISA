<?php
session_start();
$titulo = 'Perfiles contables';
include '../dependencies/header.php';
?>
<div class="card">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Financiero /</span> Perfiles contables</h5>
    <div class="nav-align-top ">
      <ul class="nav nav-tabs" role="tablist" id="tabs">
        <li class="nav-item">
          <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-creacion" aria-controls="navs-creacion" aria-selected="true"> <i class="fa-solid fa-file-circle-plus"></i> &nbsp;Creación </button>
        </li>
        <li class="nav-item">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-listado" aria-controls="navs-listado" aria-selected="false" id="buscar"> <i class="fa-solid fa-list-ol"></i> &nbsp;Listado </button>
        </li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane fade show active" id="navs-creacion" role="tabpanel">
          <form id="form-perfiles-contables" class="needs-validation  form-basico" novalidate>
            <div class="row mb-3 d-none">
              <label for="clase" class="col-sm-2 col-form-label">Id</label>
              <div class="col-sm-10">
                <input class="form-control form-control-sm" type="text" id="id" name="id" autocomplete="off" value="0" readonly>
              </div>
            </div>
            <div class="row mb-3">
              <label for="clase" class="col-sm-2 col-form-label">Tarifa %</label>
              <div class="col-sm-10">
                <select class="form-select form-select-sm" id="slcIva" name="id_iva">
                </select>
              </div>
            </div>
            <div class="row mb-3">
              <label for="clase" class="col-sm-2 col-form-label"> Descripción </label>
              <div class="col-sm-10">
                <input class="form-control form-control-sm" type="text" id="descripcion" name="descripcion" autocomplete="off" required>
              </div>
            </div>
            <div class="row mb-3">
              <label for="cta_ventas" class="col-sm-2 col-form-label"> Cuenta de Ventas </label>
              <div class="col-sm-10">
                <select class="form-select form-select-sm" id="cta_ventas" name="cta_ventas" data-placeholder="Escoja una cuenta">
                  <!-- No se incluyen opciones -->
                </select>
              </div>
            </div>
            <div class="row mb-3">
              <label for="cta_iva_ventas" class="col-sm-2 col-form-label">Cuenta IVA Ventas</label>
              <div class="col-sm-10">
                <select class="form-select form-select-sm" id="cta_iva_ventas" name="cta_iva_ventas" data-placeholder="Escoja una cuenta">
                  <!-- No se incluyen opciones -->
                </select>
              </div>
            </div>
            <div class="row mb-3">
              <label for="cta_dev_ventas" class="col-sm-2 col-form-label">Cuenta Devolución</label>
              <div class="col-sm-10">
                <select class="form-select form-select-sm" id="cta_dev_ventas" name="cta_dev_ventas" data-placeholder="Escoja una cuenta">
                  <!-- No se incluyen opciones -->
                </select>
              </div>
            </div>
            <div class="row mb-3">
              <label for="cta_iva_dev" class="col-sm-2 col-form-label">Cuenta IVA Devolución</label>
              <div class="col-sm-10">
                <select class="form-select form-select-sm" id="cta_iva_dev" name="cta_iva_dev" data-placeholder="Escoja una cuenta">
                  <!-- No se incluyen opciones -->
                </select>
              </div>
            </div>
            <div class="row mb-3">
              <label for="cta_compras" class="col-sm-2 col-form-label">Cuenta de Compras</label>
              <div class="col-sm-10">
                <select class="form-select form-select-sm" id="cta_compras" data-placeholder="Escoja una cuenta" name="cta_compras">
                  <!-- No se incluyen opciones -->
                </select>
              </div>
            </div>
            <div class="row mb-3">
              <label for="cta_iva_compras" class="col-sm-2 col-form-label">Cuenta IVA Compras</label>
              <div class="col-sm-10">
                <select class="form-select form-select-sm" id="cta_iva_compras" data-placeholder="Escoja una cuenta" name="cta_iva_compras">
                  <!-- No se incluyen opciones -->
                </select>
              </div>
            </div>
            <div class="row mb-3">
              <label for="cta_impoconsumo" class="col-sm-2 col-form-label">Cuenta Impoconsumo</label>
              <div class="col-sm-10">
                <select class="form-select form-select-sm" id="cta_impoconsumo" data-placeholder="Escoja una cuenta" name="cta_impoconsumo">
                  <!-- No se incluyen opciones -->
                </select>
              </div>
            </div>
            <div class="row mb-3">
              <label for="cta_ibua" class="col-sm-2 col-form-label">Cuenta IBUA</label>
              <div class="col-sm-10">
                <select class="form-select form-select-sm" id="cta_ibua" data-placeholder="Escoja una cuenta" name="cta_ibua">
                  <!-- No se incluyen opciones -->
                </select>
              </div>
            </div>
            <div class="row mb-3">
              <label for="cta_icui" class="col-sm-2 col-form-label">Cuenta ICUI</label>
              <div class="col-sm-10">
                <select class="form-select form-select-sm" id="cta_icui" data-placeholder="Escoja una cuenta" name="cta_icui">
                  <!-- No se incluyen opciones -->
                </select>
              </div>
            </div>
            <button type="submit" class="btn btn-outline-primary btn-sm"><i class="fa-regular fa-floppy-disk"></i> Guardar</button>
            <button type="button" id="btnNew" class="btn btn-outline-secondary btn-sm"><i class="fa-regular fa-file"></i> Nuevo</button>
          </form>
        </div>
        <div class="tab-pane fade " id="navs-listado" role="tabpanel">
          <div class="row">
            <div class="col-12">
              <div class="table-responsive text-nowrap">
                <div id="result"></div>
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
  '../controllers/perfilesContables.js?' . rand()
];
include '../dependencies/footer.php';
?>