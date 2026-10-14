<?php
session_start();

$titulo = 'Almacenes';
include '../dependencies/header.php';
?>
<div class="card">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Maestra /</span> Almacenes</h5>
    <div class="nav-align-top ">
      <ul class="nav nav-tabs" role="tablist" id="tabs">
        <li class="nav-item">
          <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-creacion" aria-controls="navs-creacion" aria-selected="true"> <i class="fa-solid fa-file-circle-plus"></i> &nbsp;Creación </button>
        </li>
        <li class="nav-item">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-listado" aria-controls="navs-listado" aria-selected="false"> <i class="fa-solid fa-list-ol"></i> &nbsp;Listado </button>
        </li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane fade show active" id="navs-creacion" role="tabpanel">
          <form id="FormCrear" class="needs-validation form-basico" novalidate>
            <input type="hidden" id="txtId" name="id">

            <!-- Acciones -->
            <div class="d-flex justify-content-end gap-2 mb-3">
              <button class="btn btn-sm btn-outline-secondary" type="button" id="btnNuevo"><i class="fa-solid fa-file"></i>&nbsp;Nuevo</button>
              <button class="btn btn-sm btn-primary" type="submit" id="btnGuardar"><i class="fa-solid fa-floppy-disk"></i>&nbsp;Guardar</button>
            </div>

            <!-- Fila 1: Código, Centro, Descripción -->
            <div class="row mb-2">
              <div class="col-md-2">
                <label for="txtAlmacen" class="form-label">Código</label>
                <input type="text" class="form-control form-control-sm" id="txtAlmacen" name="almacen" maxlength="5" autocomplete="off" required>
              </div>
              <div class="col-md-4">
                <label for="slcCentros" class="form-label">Centro de costo</label>
                <select class="form-select form-select-sm" name="centro" id="slcCentros" required></select>
              </div>
              <div class="col-md-6">
                <label for="txtDescripcion" class="form-label">Descripción</label>
                <input type="text" class="form-control form-control-sm" name="descripcion" id="txtDescripcion" maxlength="100" autocomplete="off" required>
              </div>
            </div>

            <!-- Fila 2: País, Departamento, Ciudad -->
            <div class="row mb-2">
              <div class="col-md-4">
                <label class="form-label">País</label>
                <select class="form-select form-select-sm" id="slcPais"></select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Departamento</label>
                <select class="form-select form-select-sm" id="slcDepartamento" name="departamento"></select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Ciudad</label>
                <select class="form-select form-select-sm" id="slcCiudad" name="municipio"></select>
              </div>
            </div>

            <!-- Fila 3: Dirección, Teléfono -->
            <div class="row mb-2">
              <div class="col-md-8">
                <label for="txtDireccion" class="form-label">Dirección</label>
                <input type="text" class="form-control form-control-sm" name="direccion" id="txtDireccion" maxlength="255" autocomplete="off">
              </div>
              <div class="col-md-4">
                <label for="txtTelefono" class="form-label">Teléfono</label>
                <input type="text" class="form-control form-control-sm" name="telefono" id="txtTelefono" maxlength="20">
              </div>
            </div>

            <!-- Fila 4: Lista de precios, EAN EDI, Inactivo, Virtual -->
            <div class="row mb-2 align-items-end">
              <div class="col-md-4">
                <label for="lista_precios" class="form-label">Lista de precios <small class="text-danger">(prioriza la del tercero)</small></label>
                <div class="input-group input-group-sm">
                  <select class="form-select" name="lista_precios" id="lista_precios"></select>
                  <button class="btn btn-outline-primary" type="button" id="btnGestionListas"><i class="fa fa-plus"></i></button>
                </div>
              </div>
              <div class="col-md-3">
                <label for="txtEanEdi" class="form-label">EAN EDI <small class="text-muted">(bodega)</small></label>
                <input type="text" class="form-control form-control-sm" name="ean_edi" id="txtEanEdi" maxlength="50" autocomplete="off" placeholder="Código EAN EDI">
              </div>
              <div class="col-md-2">
                <label for="txtInactivo" class="form-label">Inactivo</label>
                <select class="form-select form-select-sm" name="inactivo" id="txtInactivo">
                  <option value="0">No</option>
                  <option value="1">Sí</option>
                </select>
              </div>
              <div class="col-md-3 d-flex align-items-center gap-2 pt-3">
                <input class="form-check-input mt-0" type="checkbox" name="transito" value="0" id="Chktransito" />
                <label class="form-check-label" for="Chktransito">Bodega virtual</label>
              </div>
            </div>

          </form>
        </div>
        <div class="tab-pane fade " id="navs-listado" role="tabpanel">
          <div class="row">
            <div class="col-12">
              <div class="row mb-3">
                <div class="input-group input-group-merge"> <span class="input-group-text"><i class="bx bx-search"></i></span>
                  <input name="" id="filtro" type="text" class="form-control form-control-sm" placeholder="Codigo, descripcion..." aria-label="Buscar..." autocomplete="off">
                  <button class="btn btn-outline-primary btn-sm" id="buscar">Buscar</button>
                </div>
              </div>
              <div class="row">
                <div class="col-12">
                  <div id="result"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!--    <div class="tab-content">  -->
    </div>
  </div>
</div>
<!-- Core JS -->

<div class="modal fade" id="modalListas" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title">Gestionar Listas</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <input type="hidden" id="lista_id">
          <input type="text" class="form-control form-control-sm"
            id="lista_descripcion" placeholder="Descripción">
        </div>
        <div class="d-flex gap-2 mb-3">
          <button class="btn btn-outline-success btn-sm w-100" id="btnGuardarLista"> Guardar </button>
          <button class="btn btn-outline-secondary btn-sm w-100" id="btnNuevoLista"> Nuevo </button>
        </div>
        <hr>
        <div id="contenedorListas" style="max-height:200px; overflow:auto;"></div>
      </div>
    </div>
  </div>
</div>

<?php

$scriptsPagina = [
  '../controllers/Almacenes.js?' . rand()
];

include '../dependencies/footer.php';

?>