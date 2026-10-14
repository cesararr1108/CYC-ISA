<?php
session_start();

$titulo = 'Consulta documentos';
include '../dependencies/header.php';
?>
<div class="card">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Financiero /</span> Consulta documentos</h5>

    <!-- FILTROS -->
    <div class="row g-2 mb-3 align-items-end">
      <div class="col-6 col-md-2">
        <label class="form-label" for="bodega">Bodega</label>
        <select class="form-select form-select-sm insearch" id="bodega" name="bodega"></select>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label" for="sw">Tipo movimiento (SW)</label>
        <select class="form-select form-select-sm insearch" id="sw" name="sw"></select>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label" for="tipo">Tipo documento</label>
        <select class="form-select form-select-sm insearch" id="tipo" name="tipo"></select>
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label" for="numero">Número</label>
        <div class="input-group input-group-sm">
          <input type="text" class="form-control insearch" id="numero" name="numero"
                 autocomplete="off" inputmode="numeric" placeholder="Número y Enter...">
          <button class="btn btn-outline-primary" id="btn-search-doc" title="Búsqueda avanzada de documentos">
            <i class="fa-solid fa-magnifying-glass"></i>
          </button>
          <button class="btn btn-outline-secondary" id="btn-nuevo" title="Nueva consulta">
            <i class="fa-regular fa-file"></i>
          </button>
        </div>
      </div>
      <div class="col-12 col-md-3">
        <div class="d-flex align-items-end gap-2">
          <div class="dropdown">
            <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button"
                    data-bs-toggle="dropdown" aria-expanded="false">
              <i class="fa-solid fa-gear me-1"></i> Opciones
            </button>
            <ul class="dropdown-menu">
              <li>
                <button class="dropdown-item" id="btn-consultar">
                  <i class="fa-solid fa-download me-2"></i> Cargar
                </button>
              </li>
              <li>
                <button class="dropdown-item" id="btn-print" disabled>
                  <i class="fa-solid fa-print me-2"></i> Imprimir
                </button>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <button class="dropdown-item text-danger" id="btn-anular" disabled>
                  <i class="fa-solid fa-ban me-2"></i> Anular
                </button>
              </li>
            </ul>
          </div>
          <button class="btn btn-outline-secondary btn-sm" id="btn-video-tutorial" title="Ver video tutorial">
            <i class="fa-brands fa-youtube"></i>
            <span class="d-none d-lg-inline ms-1">Tutorial</span>
          </button>
        </div>
      </div>
    </div>

    <!-- PESTAÑAS -->
    <div class="nav-align-top">
      <ul class="nav nav-tabs" role="tablist" id="tabs">
        <li class="nav-item">
          <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                  data-bs-target="#navs-basicos" aria-controls="navs-basicos" aria-selected="true">
            <i class="fa-solid fa-file me-1"></i> Básicos
          </button>
        </li>
        <li class="nav-item">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                  data-bs-target="#navs-valores" aria-controls="navs-valores" aria-selected="false">
            <i class="fa-solid fa-sack-dollar me-1"></i> Valores
          </button>
        </li>
        <li class="nav-item">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                  data-bs-target="#navs-detalle" aria-controls="navs-detalle" aria-selected="false">
            <i class="fa-solid fa-list-ol me-1"></i> Detalle
            <span class="badge rounded-pill bg-label-primary ms-1 d-none" id="cnt-detalle"></span>
          </button>
        </li>
        <li class="nav-item">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                  data-bs-target="#navs-cruces" aria-controls="navs-cruces" aria-selected="false">
            <i class="fa-solid fa-arrow-right-arrow-left me-1"></i> Cruces
            <span class="badge rounded-pill bg-label-primary ms-1 d-none" id="cnt-cruces"></span>
          </button>
        </li>
        <li class="nav-item">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                  data-bs-target="#navs-medios-pago" aria-controls="navs-medios-pago" aria-selected="false">
            <i class="fa-solid fa-coins me-1"></i> Medios de pago
            <span class="badge rounded-pill bg-label-primary ms-1 d-none" id="cnt-medios"></span>
          </button>
        </li>
        <li class="nav-item">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                  data-bs-target="#navs-contable" aria-controls="navs-contable" aria-selected="false">
            <i class="fa-solid fa-receipt me-1"></i> Contable
            <span class="badge rounded-pill bg-label-primary ms-1 d-none" id="cnt-contable"></span>
          </button>
        </li>
      </ul>

      <div class="tab-content">

        <!-- BÁSICOS -->
        <div class="tab-pane fade show active" id="navs-basicos" role="tabpanel">
          <div class="row g-3">
            <div class="col-md-4 d-flex">
              <div class="card h-100 w-100 shadow-none border">
                <div class="card-header py-2">
                  <h6 class="mb-0 text-primary">Tercero</h6>
                </div>
                <div class="card-body pt-0">
                  <input type="hidden" id="doc-id">
                  <label class="form-label" for="doc-nit">NIT</label>
                  <input type="text" class="form-control form-control-sm mb-2" id="doc-nit" readonly disabled>
                  <label class="form-label" for="doc-nombres">Nombres</label>
                  <input type="text" class="form-control form-control-sm mb-2" id="doc-nombres" readonly disabled>
                  <label class="form-label" for="doc-razon">Razón comercial</label>
                  <input type="text" class="form-control form-control-sm mb-2" id="doc-razon" readonly disabled>
                  <label class="form-label" for="doc-email">Email</label>
                  <input type="text" class="form-control form-control-sm mb-2" id="doc-email" readonly disabled>
                  <label class="form-label" for="doc-celular">Celular</label>
                  <input type="text" class="form-control form-control-sm" id="doc-celular" readonly disabled>
                </div>
              </div>
            </div>

            <div class="col-md-8 d-flex">
              <div class="card h-100 w-100 shadow-none border">
                <div class="card-header py-2 d-flex align-items-center justify-content-between">
                  <h6 class="mb-0 text-primary">Documento</h6>
                  <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-primary" id="btn-whatsapp" title="Enviar por WhatsApp" disabled>
                      <i class="fab fa-whatsapp"></i><span class="d-none d-lg-inline ms-1">WhatsApp</span>
                    </button>
                    <button class="btn btn-sm btn-outline-primary" id="btn-correo" title="Enviar por correo" disabled>
                      <i class="fa-solid fa-envelope"></i><span class="d-none d-lg-inline ms-1">Correo</span>
                    </button>
                  </div>
                </div>
                <div class="card-body pt-0">
                  <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <span class="badge d-none" id="status"></span>
                    <span class="badge d-none" id="status-pago"></span>
                    <span class="badge d-none" id="status-vencimiento"></span>
                  </div>
                  <small class="text-muted d-block mb-2" id="txt-status"></small>

                  <div class="row g-2">
                    <div class="col-md-4">
                      <label class="form-label" for="doc-fecha">Fecha creación</label>
                      <input type="text" id="doc-fecha" class="form-control form-control-sm" readonly disabled>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label" for="doc-usuario">Usuario creación</label>
                      <input type="text" id="doc-usuario" class="form-control form-control-sm" readonly disabled>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label" for="doc-referencia">Documento referencia</label>
                      <input type="text" id="doc-referencia" class="form-control form-control-sm" readonly disabled>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label" for="doc-valor">Valor</label>
                      <input type="text" id="doc-valor" class="form-control form-control-sm" readonly disabled>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label" for="doc-valor-aplicado">Valor aplicado</label>
                      <input type="text" id="doc-valor-aplicado" class="form-control form-control-sm" readonly disabled>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label" for="doc-vencimiento">Vencimiento</label>
                      <input type="text" id="doc-vencimiento" class="form-control form-control-sm" readonly disabled>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label" for="doc-condicion">Condición</label>
                      <input type="text" id="doc-condicion" class="form-control form-control-sm" readonly disabled>
                    </div>
                    <div class="col-6 col-md-3">
                      <label class="form-label" for="doc-condicion-dias">Días</label>
                      <input type="text" id="doc-condicion-dias" class="form-control form-control-sm" readonly disabled>
                    </div>
                    <div class="col-6 col-md-3">
                      <label class="form-label" for="doc-condicion-dias-gracia">Días de gracia</label>
                      <input type="text" id="doc-condicion-dias-gracia" class="form-control form-control-sm" readonly disabled>
                    </div>
                    <div class="col-12">
                      <label class="form-label" for="doc-notas">Notas</label>
                      <textarea class="form-control form-control-sm" id="doc-notas" rows="2" readonly disabled></textarea>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- VALORES -->
        <div class="tab-pane fade" id="navs-valores" role="tabpanel">
          <div class="row g-3">
            <div class="col-md-6">
              <div class="card h-100 shadow-none border">
                <div class="card-header py-2">
                  <h6 class="mb-0 text-primary">Impuestos y totales</h6>
                </div>
                <div class="card-body pt-0">
                  <div class="row g-2">
                    <div class="col-6">
                      <label class="form-label" for="doc-subtotal">Subtotal</label>
                      <input type="text" id="doc-subtotal" class="form-control form-control-sm text-end" readonly disabled>
                    </div>
                    <div class="col-6">
                      <label class="form-label" for="doc-descuento">Descuento</label>
                      <input type="text" id="doc-descuento" class="form-control form-control-sm text-end" readonly disabled>
                    </div>
                    <div class="col-6">
                      <label class="form-label" for="doc-iva">IVA</label>
                      <input type="text" id="doc-iva" class="form-control form-control-sm text-end" readonly disabled>
                    </div>
                    <div class="col-6">
                      <label class="form-label" for="doc-impoconsumo">Impoconsumo</label>
                      <input type="text" id="doc-impoconsumo" class="form-control form-control-sm text-end" readonly disabled>
                    </div>
                    <div class="col-6">
                      <label class="form-label" for="doc-ibua">IBUA</label>
                      <input type="text" id="doc-ibua" class="form-control form-control-sm text-end" readonly disabled>
                    </div>
                    <div class="col-6">
                      <label class="form-label" for="doc-icui">ICUI</label>
                      <input type="text" id="doc-icui" class="form-control form-control-sm text-end" readonly disabled>
                    </div>
                    <div class="col-12">
                      <label class="form-label fw-semibold" for="doc-total-bruto">Total documento</label>
                      <input type="text" id="doc-total-bruto" class="form-control form-control-sm text-end fw-semibold" readonly disabled>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card h-100 shadow-none border">
                <div class="card-header py-2">
                  <h6 class="mb-0 text-primary">Retenciones y neto</h6>
                </div>
                <div class="card-body pt-0">
                  <div class="row g-2">
                    <div class="col-6">
                      <label class="form-label" for="doc-ret-fuente">ReteFuente</label>
                      <input type="text" id="doc-ret-fuente" class="form-control form-control-sm text-end" readonly disabled>
                    </div>
                    <div class="col-6">
                      <label class="form-label" for="doc-ret-ica">ReteICA</label>
                      <input type="text" id="doc-ret-ica" class="form-control form-control-sm text-end" readonly disabled>
                    </div>
                    <div class="col-6">
                      <label class="form-label" for="doc-ret-iva">ReteIVA</label>
                      <input type="text" id="doc-ret-iva" class="form-control form-control-sm text-end" readonly disabled>
                    </div>
                    <div class="col-6">
                      <label class="form-label" for="doc-ret-cree">ReteCREE</label>
                      <input type="text" id="doc-ret-cree" class="form-control form-control-sm text-end" readonly disabled>
                    </div>
                    <div class="col-12">
                      <label class="form-label fw-semibold" for="doc-total-ret">Total retenciones</label>
                      <input type="text" id="doc-total-ret" class="form-control form-control-sm text-end" readonly disabled>
                    </div>
                    <div class="col-12">
                      <label class="form-label fw-semibold text-primary" for="doc-neto-pagar">Neto a pagar</label>
                      <input type="text" id="doc-neto-pagar" class="form-control form-control-sm text-end fw-semibold" readonly disabled>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="tab-pane fade" id="navs-detalle" role="tabpanel"></div>
        <div class="tab-pane fade" id="navs-cruces" role="tabpanel"></div>
        <div class="tab-pane fade" id="navs-medios-pago" role="tabpanel"></div>
        <div class="tab-pane fade" id="navs-contable" role="tabpanel"></div>
      </div>
    </div>
  </div>
</div>

<!-- MODAL BÚSQUEDA DE DOCUMENTOS -->
<div class="modal fade" id="mdl-listar-documentos" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-fullscreen-lg-down modal-xl modal-dialog-scrollable" style="max-width:95vw;">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title">
          <i class="fa-regular fa-file-lines me-2"></i>Buscar documentos
          <small class="text-muted fw-normal" id="mdl-subtitulo"></small>
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body p-3">
        <div class="row g-2 mb-3 align-items-end">
          <div class="col-md-4">
            <label class="form-label" for="mdl-filtro-tercero">Tercero (nombre / NIT)</label>
            <div class="input-group input-group-sm">
              <span class="input-group-text"><i class="bx bx-user"></i></span>
              <input type="text" class="form-control" id="mdl-filtro-tercero" placeholder="Nombre o NIT...">
            </div>
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label" for="mdl-fecha-ini">Fecha inicial</label>
            <input type="date" class="form-control form-control-sm" id="mdl-fecha-ini">
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label" for="mdl-fecha-fin">Fecha final</label>
            <input type="date" class="form-control form-control-sm" id="mdl-fecha-fin">
          </div>
          <div class="col-md-2">
            <button class="btn btn-sm btn-primary w-100" id="btn-mdl-consultar">
              <i class="fa-solid fa-search me-1"></i> Consultar
            </button>
          </div>
        </div>
        <div id="body-listar-documentos"></div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">
          <i class="fa-regular fa-rectangle-xmark me-1"></i> Cerrar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL VIDEO TUTORIAL -->
<div class="modal fade" id="modalVideoTutorial" tabindex="-1" aria-labelledby="modalVideoTutorialLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title" id="modalVideoTutorialLabel"><i class="fab fa-youtube me-1"></i> Tutorial</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body p-0">
        <div class="ratio ratio-16x9">
          <iframe id="videoFrameTutorial" src="" title="Video tutorial" frameborder="0"
                  allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                  allowfullscreen></iframe>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$scriptsPagina = [
  '../controllers/consultaDocumentos.js?' . rand()
];
include '../dependencies/footer.php';
?>
