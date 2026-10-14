<?php
session_start();

$titulo = 'Actualización de precios';
include '../dependencies/header.php';
?>
<style>
  input[required],
  textarea[required],
  select[required] {
    border-color: #7300ffff;
    box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.12);
  }

  .swal2-container {
    z-index: 20000 !important;
    font-size: 1.5rem !important;
  }

  #descripcion_grid,
  #precio_grid,
  #laboratorio_grid:disabled {
    background-color: white;
    border-left: none;
    border-right: none;
    border-top: none;
  }
</style>
<input type="hidden" id="bodega_session" value="<?php echo  $_SESSION["Ses_id_bodega"]; ?>" readonly />
<div class="card" style="height: auto;">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Maestra /</span> Actualización de precios</h5>
    <div class="nav-align-top">
      <ul class="nav nav-tabs" role="tablist" id="tabs">
        <li class="nav-item">
          <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-listas" aria-controls="navs-listas" aria-selected="true">
            <i class="fa-solid fa-list"></i> &nbsp;Listas
          </button>
        </li>
        <li class="nav-item" style="flex:1;"></li>
        <li class="nav-item border-danger">
          <button type="button" class="btn btn-outline-success btn-sm" id="btn-csv"><i class="fa-solid fa-file-excel"></i> Subir Excel</button>
          <button type="reset" class="btn btn-sm btn-outline-secondary" id="btnLimpiar"> <i class="fa-regular fa-file"></i> Nuevo</button>
          <button type="submit" class="btn btn-sm btn-outline-primary" id="btnGuardar"> <i class="fa-regular fa-floppy-disk"></i> Guardar</button>
        </li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane fade show active" id="navs-listas" role="tabpanel">
          <form id="FormEncabezado" class="needs-validation  form-basico" novalidate>
            <div class="row">
              <div class="col-2">
                <label for="lista_precios" class="form-label"> Lista de precios </label>
                <select class="form-select form-select-sm" name="lista_precios" id="lista_precios">
                </select>
              </div>
              <div class="col-2">
                <label for="slcTipoLista" class="form-label">Tipo lista</label>
                <select class="form-select form-select-sm asigar-precio" id="slcTipoLista">
                </select>
              </div>
              <div class="col-5">
                <label for="txtRentabildad" class="form-label">Código (SKU)</label>
                <div class="input-group input-group-sm input-group-merge">
                  <span class="input-group-text"><i class="bx bx-search"></i></span>
                  <input type="text" class="form-control" id="txt-referencia" placeholder="Código, descripción, descripción larga" autocomplete="off">
                  <span class="input-group-text btn btn-sm btn-primary" id="btnBusquedaAvanzada"><i class="fa-solid fa-sliders"></i></span>
                </div>
              </div>
              <div class="col-3">
                <label for="txtRentabildad" class="form-label">Precio/Rentabilidad</label>
                <div class="input-group mb-3">
                  <input type="text" class="form-control form-control-sm moneda asigar-precio" id="txtRentabildad" placeholder="Precio/Rentabilidad">
                  <button class="btn btn-sm btn-outline-primary" type="button" id="btnAddLista"><i class="fa-regular fa-square-plus"></i> </button>
                </div>
              </div>
            </div>
            <div class="row mt-2">
              <div class="col-6">
                <input type="text" class="form-control form-control-sm data-grid" id="descripcion_grid" disabled="" wfd-id="id19">
              </div>
              <div class="col-3">
                <input type="text" class="form-control form-control-sm data-grid" id="laboratorio_grid" disabled="" wfd-id="id20">
              </div>
              <div class="col-3">
                <input type="text" class="form-control form-control-sm data-grid" id="precio_grid" disabled="" wfd-id="id21">
              </div>
            </div>
          </form>
          <hr>
          <div class="table-responsive mt-3">
            <table class="table table-sm table-bordered" id="tablaItems">
              <thead class="table-primary">
                <tr>
                  <th>Código</th>
                  <th>Descripción</th>
                  <th>Precio Actual</th>
                  <th>Lista</th>
                  <th>Tipo Lista</th>
                  <th>Nuevo Precio/Rentabilidad</th>
                  <th></th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<div id="modal-container"></div>

<!-- Modal Subir Excel -->
<div class="modal fade" id="modalExcel" tabindex="-1" aria-labelledby="modalExcelLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalExcelLabel">Subir Excel - Actualización de Precios</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p>El archivo Excel debe tener la siguiente estructura:</p>
        <table class="table table-sm table-bordered">
          <thead class="table-light">
            <tr>
              <th>Código</th>
              <th>Nuevo Precio</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>REF001</td>
              <td>12500</td>
            </tr>
            <tr>
              <td>REF002</td>
              <td>9800</td>
            </tr>
          </tbody>
        </table>
        <p style="color:red;"><b>Notas:</b> </p>
        <ul>
          <li>El archivo debe ser .xlsx o .xls.</li>
          <li>La primera fila se considera encabezado y se ignorará al cargar.</li>
          <li>La lista y el tipo de lista sera tomado del menu principal campos (TIPO LISTA y LISTA PRECIOS).</li>
        </ul>
        <div class="mt-3">
          <input type="file" id="inputExcel" class="form-control form-control-sm" accept=".xlsx, .xls">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<?php

$scriptsPagina = [
  '../controllers/ActualizacionPrecios.js?' . rand()
];

include '../dependencies/footer.php';

?>