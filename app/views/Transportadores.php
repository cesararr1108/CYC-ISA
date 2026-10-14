<?php
session_start();
$titulo = 'Transportadores';
include '../dependencies/header.php';
?>
<div class="card">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Logística /</span> Transportadores</h5>
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
          <input type="hidden" id="IdTrans" class="form-control form-control-sm" value="0" readonly disabled>
          <div class="row">
            <div class="col-md-3">
              <label class="form-label">Tipo Identificación</label>
              <select id="id_tipo_identificacion" class="form-select form-select-sm">
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Identificación</label>
              <input type="text" id="Nit" class="form-control form-control-sm" onkeypress="return vnumeros(event)" autofocus>
            </div>
            <div class="col-md-3">
              <label class="form-label">Nombres</label>
              <input type="text" id="Nombres" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
              <label class="form-label">Razón Social</label>
              <input type="text" id="Razon" class="form-control form-control-sm">
            </div>
          </div>
          <div class="row">
            <div class="col-md-3">
              <label class="form-label">Celular</label>
              <input type="text" id="Celular" class="form-control form-control-sm" onkeypress="return vnumeros(event)">
            </div>
            <div class="col-md-3">
              <label class="form-label">Pais</label>
              <select id="pais" class="form-select form-select-sm">
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Departamento</label>
              <select id="dpto" class="form-select form-select-sm">
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Municipio</label>
              <select id="mpio" class="form-select form-select-sm">
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-3">
              <label class="form-label">Vehiculo</label>
              <input type="text" id="Vehiculo" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
              <label class="form-label">Placas</label>
              <input type="text" id="Placas" class="form-control form-control-sm" placeholder="XXX-000">
            </div>
            <div class="col-md-3">
              <label class="form-label">Bloqueo Planilla</label>
              <select id="BloqueoPlanilla" class="form-select form-select-sm">
                <option value="1" selected>SI</option>
                <option value="0">NO</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Tipo Transportador</label>
              <select id="SlcTipoT" class="form-select form-select-sm">
                <option value="M" selected>MENSAJERO</option>
                <option value="T">TRANSPORTADORA</option>
              </select>
            </div>
          </div>
          <div class="row mt-3">
            <div class="col-md-12">
              <button type="button" class="btn btn-sm btn-outline-primary" id="btnGuardar"><i class="fa-regular fa-floppy-disk"></i> Guardar</button>
              <button type="button" class="btn btn-sm btn-outline-secondary" id="btnLimpiar"> <i class="fa-regular fa-file"></i> Nuevo</button>
              <button type="button" class="btn btn-sm btn-outline-danger" id="btnEliminar"> <i class="fa-solid fa-delete-left"></i> Eliminar</button>
            </div>
          </div>
        </div>
        <!--Segundo tab--->
        <div class="tab-pane fade show" id="bordered-detalle" role="tabpanel">
          <div class="row">
            <div class="col-md-12">
              <label class="form-label">Búsqueda de transportadores</label>
              <div class="input-group mb-3"> <span class="input-group-text"><i class="fa-solid fa-magnifying-glass text-primary"></i></span>
                <input type="text" id="txtSearch" class="form-control form-control-sm" placeholder="">
                <button type="button" id="btnListar" class="btn btn-sm btn-outline-primary"> <i class="fa-solid fa-list"></i> Listar</button>
              </div>
            </div>
          </div>
          <div id="result"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$scriptsPagina = [
  '../controllers/Transportadores.js?' . rand()
];
include '../dependencies/footer.php';
?>