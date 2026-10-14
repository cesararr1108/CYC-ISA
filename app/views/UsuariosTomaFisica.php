<?php
session_start();

$titulo = 'Usuarios para toma fisica';
include '../dependencies/header.php';
?>
<input type="hidden" id="id_bodega_session" value="<?php if (!empty($_SESSION["Ses_id_bodega"])) {
                                                      echo $_SESSION["Ses_id_bodega"];
                                                    } else {
                                                      echo  '0';
                                                    } ?>" />

<div class="card" style="height: 95vh;">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Inventario /</span> Usuarios para toma fisica</h5>

    <div class="nav-align-top ">
      <ul class="nav nav-tabs" role="tablist" id="tabs">
        <li class="nav-item">
          <button
            type="button"
            class="nav-link active"
            role="tab"
            data-bs-toggle="tab"
            data-bs-target="#navs-creacion"
            aria-controls="navs-creacion"
            aria-selected="true">
            <i class="fa-solid fa-file-circle-plus"></i> &nbsp;Creación
          </button>
        </li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane fade show active" id="navs-creacion" role="tabpanel">
          <div class="row mb-4">
            <div class="col">
              <label class="form-label">Bodega</label>
              <select class="form-select form-select-sm" id="bodegas">

              </select>
            </div>
          </div>
          <div class="row mt-2">
            <div class="col-md-12">
              <div class="row">
                <div class="col">
                  <label class="form-label">Seleccionar todos</label>
                    <input type="checkbox" id="seleccionar-totos" class="" />
                </div>
              </div>
              <div class="row">
                <div class="col-md-12">
                  <div class="mb-1" bis_skin_checked="1">
                    <div class="row">
                      <div class="col-12">
                        <select class="form-control form-control-sm" id="usuarios" multiple>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>
              </div><!-- row -->
              <div class="row mt-3">
                <div class="col">
                  <button class="btn btn-sm btn-outline-primary" id="agregar">
                   <i class="fa-solid fa-plus"></i> Agregar a tablas de conteo
                  </button>
                </div>
              </div>
            </div>
            <div class="col-md-12">
              <div class="card mt-3">
                <div class="card-header border-0 py-3">
                  <h6 class="form-label"><i class="fa-solid fa-users"></i> Usuarios agregados</h6>
                </div>
                <div class="card-body">

                  <div id="result"></div>
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
  '../controllers/UsuariosTomaFisica.js?' . rand()
];

include '../dependencies/footer.php';

?>