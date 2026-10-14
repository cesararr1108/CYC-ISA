<?php
session_start();
$titulo = 'Despachos';
include '../dependencies/header.php';
?>
<div class="card">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Logística /</span> Despachos</h5>
    <div class="nav-align-top ">
      <ul class="nav nav-tabs" role="tablist" id="tabs">
        <li class="nav-item">
          <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-busqueda" aria-controls="navs-busqueda" aria-selected="true"> <i class="fa-solid fa-file-circle-plus"></i> &nbsp;Creación </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="detale-tab" data-bs-toggle="tab" data-bs-target="#bordered-detalle" type="button" role="tab" aria-controls="home" aria-selected="true"> <i class="fa-solid fa-magnifying-glass"></i> Búsqueda </button>
        </li>
      </ul>
      <div class="tab-content">
        <!--Primer tab--->
        <div class="tab-pane fade show active" id="navs-busqueda" role="tabpanel">
          <div class="row">
            <div class="col-md-3">
              <label class="form-label">Tipo Transportador</label>
              <select id="SlcTipoT" class="form-select form-select-sm">
                <option value="M" selected>MENSAJERO</option>
                <option value="T">TRANSPORTADORA</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Transportador</label>
              <select id="SlcTransportador" class="form-select form-select-sm">
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Tipo Factura</label>
              <select id="slcTipoFactura" class="form-select form-select-sm">
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Factura</label>
              <input type="text" id="nFactura" class="form-control form-control-sm" onkeypress="return vnumeros(event)" autocomplete="off">
              <input type="hidden" id="idFactura" class="form-control form-control-sm" disabled readonly>
            </div>
          </div>
          <div class="row">
            <div class="col-md-3">
              <label class="form-label">Cajas</label>
              <select id="nCajas" class="form-select form-select-sm">
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Flete</label>
              <input type="text" id="nFlete" class="form-control form-control-sm" onkeypress="return vnumeros(event)" autocomplete="off">
            </div>
            <div class="col-md-3">
              <label class="form-label">Guia</label>
              <input type="text" id="nGuia" class="form-control form-control-sm" onkeypress="return vnumeros(event)" autocomplete="off">
            </div>
            <div class="col-md-3">
              <label class="form-label">&nbsp;</label>
              <div class="input-group mb-3">
                <button type="button" class="btn btn-sm btn-outline-primary" id="btnAdd"><i class="fa-solid fa-circle-plus"></i> Agregar</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnLimpiar"> <i class="fa-regular fa-file"></i> Limpiar</button>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-12">
              <textarea class="form-control form-control-sm alert alert-warning p-1" id="nota" name="nota" maxlength="100" placeholder="Notas de transporte, longitud maxima de 100 caracteres"></textarea>
            </div>
          </div>
          <div class="row">
            <div class="col-md-12">
              <button type="button" class="btn btn-sm btn-outline-primary" id="btnGuardar"><i class="fa-regular fa-floppy-disk"></i> Guardar</button>
              <button type="button" class="btn btn-sm btn-outline-secondary" id="btnNuevo"> <i class="fa-regular fa-file"></i> Nuevo</button>
            </div>
          </div>
          <div class="mt-3">
            <table class="table table-sm table-striped table-hover table-bordered table-responsive-md">
              <thead class="cabecera table-primary">
                <tr>
                  <th>Tipo</th>
                  <th>Número</th>
                  <th>NIT</th>
                  <th>Nombres</th>
                  <th>Razón</th>
                  <th>Dpto</th>
                  <th>Ciudad</th>
                  <th>Cajas</th>
                  <th>Flete</th>
                  <th>Guia</th>
                  <th>Nota</th>
                  <th>Eliminar</th>
                </tr>
              </thead>
              <tbody id="tdPlanillaDetalle">
              </tbody>
            </table>
          </div>
        </div>
        <!--Segundo tab--->
        <div class="tab-pane fade show" id="bordered-detalle" role="tabpanel">
          <div class="row">
            <div class="col-md-6">
              <label class="form-label">Fecha inicial</label>
              <div class="input-group input-group-merge"> <span class="input-group-text"><i class="fa-regular fa-calendar-check"></i></span>
                <input type="date" class="form-control form-control-sm" id="fhIni" aria-describedby="fhIni">
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Fecha final</label>
              <div class="input-group input-group-merge"> <span class="input-group-text"><i class="fa-regular fa-calendar-check"></i></span>
                <input type="date" class="form-control form-control-sm" id="fhFin" aria-describedby="fhFin">
              </div>
            </div>
          </div>
          <div class="row mt-3">
            <div class="col-md-12">
              <button type="button" class="btn btn-sm btn-outline-primary" id="btnConsultarDespachos"><i class="fa-solid fa-magnifying-glass"></i> Consultar</button>
            </div>
          </div>
          <div id="dvListarDespachos"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!--Modal PDF Planilla-->
<div class="modal fade" id="mdPlanilla" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="staticBackdropLabel">Planilla de despacho</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="mdPlanillaBody"> ... </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
<!--Modal PDF Planilla Detalle-->
<div class="modal fade" id="mdPlanillaDetalle" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="staticBackdropLabel">Planilla de despacho</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="mdPlanillaDetalleBody"> ... </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
<?php
$scriptsPagina = [
  '../controllers/Despachos.js?' . rand()
];
include '../dependencies/footer.php';
?>