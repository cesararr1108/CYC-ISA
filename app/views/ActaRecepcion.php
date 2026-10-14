<?php
session_start();

$titulo = 'Acta de recepción';
include '../dependencies/header.php';
?>

<input type="hidden" value="<?php echo $_SESSION["Ses_id"];  ?>" readonly id="id_usuario" />
<input type="hidden" value="" id="tmp" readonly />
<input type="hidden" value="" id="id_transaccion" readonly />
<input type="hidden" value="" id='vendedor' readonly />
<input type="hidden" id="id_bodega_session" value="<?php if (!empty($_SESSION["Ses_id_bodega"])) {
                                                      echo $_SESSION["Ses_id_bodega"];
                                                    } else {
                                                      echo  '0';
                                                    } ?>" />
<div class="card " style="height: auto;">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Inventarios / </span>Acta de recepción</h5>
    <div class="nav-align-top">
      <ul class="nav nav-tabs" role="tablist" id="tabs">
        <li class="nav-item">
          <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-tercero" aria-controls="navs-tercero" aria-selected="true"> <i class="fa-solid fa-user-tie"></i> Búsqueda de productos&nbsp; </button>
        </li>
        <li class="nav-item">
          <button id="btn-navs-creacion" type="button" class="nav-link disabled" role="tab" data-bs-toggle="tab" data-bs-target="#navs-creacion" aria-controls="navs-creacion" aria-selected="false" tabindex="-1"><i class="fa-solid fa-check-double"></i>&nbsp;Aceptación y aprobación </button>
        </li>
        <li class="nav-item">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-temporal" aria-controls="navs-temporal" aria-selected="false"> <i class="fa-regular fa-clock"></i> &nbsp;Historial </button>
        </li>
        <li class="nav-item" style="flex:1;"> </li>
        <li class="nav-item border-danger">
          <button type="button" class="btn btn-sm btn-outline-secondary" id="nuevo"> <i class="fa-regular fa-file"></i> Nuevo</button>
          <button type="button" class="btn btn-sm btn-primary" id="guardar"> <i class="fa-regular fa-floppy-disk"></i> Guardar</button>
        </li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane fade show active m-0 p-0 " id="navs-tercero" role="tabpanel">
          <div class="row">
            <div class="col">
              <label class="form-label">Búsqueda</label>
              <input type="hidden" id="id_tercero" readonly />
              <div class="input-group input-group-sm input-group-merge">
                <span class="input-group-text">
                  <i class="bx bx-search"></i>
                </span>
                <input type="text" class="form-control form-control-sm" placeholder="Buscar proveedor..." id="busqueda-proveedor" autocomplete="off" wfd-id="id6" />
              </div>
            </div>
            <div class="col">
              <label class="form-label">Proveedor</label>
              <div class="input-group input-group-merge">
                <span class="input-group-text">
                  <i class="fa-solid fa-user-tie"></i>
                </span>
                <input id="proveedor" readonly type="text" class="form-control form-control-sm" />
              </div>
            </div>
          </div>
          <div class="row mt-2">
            <div class="col">
              <label class="form-label">Fecha inicial</label>
              <div class="input-group input-group-merge mt-2">
                <span class="input-group-text"><i class="fa-regular fa-calendar"></i></span>
                <input type="date" class="form-control form-control-sm" id="fecha-inicial" />
              </div>
            </div>
            <div class="col">
              <label class="form-label">Fecha final</label>
              <div class="input-group input-group-merge mt-2">
                <span class="input-group-text"><i class="fa-regular fa-calendar"></i></span>
                <input type="date" class="form-control form-control-sm" id="fecha-final" />
              </div>
            </div>
          </div>
          <div class="row mt-2">
            <div class="col">
              <label class="form-label">Tipo</label>
              <select class="form-select form-select-sm mt-2" id="tipos"></select>
            </div>
            <div class="col">
              <label class="form-label">Numero</label>
              <div class="input-group input-group-merge mt-2">
                <span class="input-group-text"><i class="fa-solid fa-hashtag"></i></span>
                <input id="numero" type="number" class="form-control form-control-sm" placeholder="Número  de documento..." />
              </div>
            </div>
          </div>
          <div class="row mt-2">
            <div class="col d-flex justify-content-end">
              <button class="btn btn-sm btn-outline-primary" id="consultar"><i class="fa fa-search"></i> Consultar</button>
            </div>
          </div>
        </div>
        <!--  <div class="tab-pane fade show active" id="navs-tercero" role="tabpanel"> -->

        <div class="tab-pane fade " id="navs-creacion" role="tabpanel">
          <h4>Productos cargados</h4>
          <div id="items-recepcion" class="table-responsive-md"></div>
        </div>
        <div class="tab-pane fade " id="navs-temporal" role="tabpanel">

          <div class="row mt-2">
            <div class="col">
              <input type="hidden" id="id_tercero_his" readonly />
              <label class="form-label">Busqueda proveedor</label>
              <div class="input-group input-group-merge mt-2">
                <span class="input-group-text">
                  <i class="bx bx-search"></i>
                </span>
                <input type="text" class="form-control form-control-sm" id="busqueda_proveedor_his" />
              </div>
            </div>
            <div class="col">
              <label class="form-label">Proveedor</label>
              <div class="input-group input-group-merge mt-2">
                <span class="input-group-text">
                  <i class="fa-solid fa-user-tie"></i>
                </span>
                <input readonly type="text" class="form-control form-control-sm" id="proveedor_his" />
              </div>
            </div>
          </div>

          <div class="row mt-2">
            <div class="col">
              <label class="form-label">Fecha inicial</label>
              <div class="input-group input-group-merge mt-2">
                <span class="input-group-text"><i class="fa-regular fa-calendar"></i></span>
                <input type="date" class="form-control form-control-sm" id="fecha-inicial_his" />
              </div>
            </div>
            <div class="col">
              <label class="form-label">Fecha final</label>
              <div class="input-group input-group-merge mt-2">
                <span class="input-group-text"><i class="fa-regular fa-calendar"></i></span>
                <input type="date" class="form-control form-control-sm" id="fecha-final_his" />
              </div>
            </div>
          </div>
          <div class="row mt-2">
            <div class="col">
              <button class="btn btn-sm btn-outline-primary" id="consultar_his"><i class="fa fa-search"></i> Consultar</button>
            </div>
          </div>
          <div class="row mt-3">
            <div class="table-responsive text-nowrap" id="result_his"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalDocumentos" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="">Documentos encontrados </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="result-documentos" class="table-responsive text-nowrap"></div>
      </div>
    </div>
  </div>
</div>

<div id="modal-container"></div>

<!-- Modal PDF Acta -->
<div class="modal fade" id="modalPdfActa" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-fullscreen" role="document">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h5 class="modal-title"><i class="fa fa-print me-2"></i>Acta de recepción</h5>
        <button type="button" class="btn btn-sm btn-icon btn-text-secondary ms-auto" data-bs-dismiss="modal" aria-label="Close">
          <i class="fa-solid fa-xmark fa-lg"></i>
        </button>
      </div>
      <div class="modal-body p-0">
        <iframe id="iframe-pdf-acta" src="" style="width:100%;height:100%;border:none;"></iframe>
      </div>
    </div>
  </div>
</div>

<?php

$scriptsPagina = [
  '../controllers/ActasRecepcion.js?' . rand()
];
include '../dependencies/footer.php';

?>
