<?php
session_start();

$titulo = 'Devoluciones en ventas';
include '../dependencies/header.php';
?>
<input type="hidden" id="tmp" readonly />
<input type="hidden" id="id_transaccion_ref" value="0" readonly />
<input type="hidden" id="id_bodega_session" value="<?php if (!empty($_SESSION["Ses_id_bodega"])) {
                                                      echo $_SESSION["Ses_id_bodega"];
                                                    } else {
                                                      echo  '0';
                                                    } ?>" />

<div class="card" style="height: 95vh;">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Comercial / </span>Devoluciones en ventas</h5>
    <div class="nav-align-top">
      <ul class="nav nav-tabs" role="tablist" id="tabs">
        <li class="nav-item">
          <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-busqueda" aria-controls="navs-busqueda" aria-selected="true">
            <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Búsqueda
          </button>
        </li>
        <li class="nav-item">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-items" aria-controls="navs-items" aria-selected="false" disabled>
            <i class="fa-solid fa-list-ol"></i>&nbsp;Items
          </button>
        </li>
        <li class="nav-item">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-fe" aria-controls="navs-fe" aria-selected="false">
            <i class="fa-solid fa-file-invoice"></i> &nbsp;FE
          </button>
        </li>
        <li class="nav-item" style="flex:1;"></li>
        <li class="nav-item border-danger">
          <button type="button" class="btn btn-sm btn-outline-secondary" id="nuevo"> <i class="fa-regular fa-file"></i> Nuevo</button>
          <button type="button" class="btn btn-sm btn-primary" id="guardar"> <i class="fa-regular fa-floppy-disk"></i> Guardar</button>
        </li>
      </ul>
      <div class="tab-content">
        <!-- TAB 1 -->
        <div class="tab-pane fade show active" id="navs-busqueda" role="tabpanel">
          <div class="row">

            <div class="col-8">
              <div class="card h-100 mb-3">
                <h6 class="card-header text-primary form-label">Datos de Factura y Tercero</h6>
                <div class="card-body">
                  <div class="row mb-2">
                    <div class="col-md-6">
                      <div>
                        <label class="form-label">Tipo</label>
                        <input type="text" class="form-control form-control-sm " id="tipo-factura" name="tipo-factura" placeholder="Tipo de factura" />
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div>
                        <label class="form-label">Numero</label>
                        <input type="number" class="form-control form-control-sm " id="numero-factura" name="numero-factura" placeholder="Numero de factura" />
                      </div>
                    </div>
                  </div>
                  <div class="row mt-3 pb-2">
                    <div class="col-12">
                      <div>
                        <label class="form-label">Búsqueda de tercero</label>
                        <div class="input-group input-group-sm input-group-merge"> <span class="input-group-text" id="basic-addon-search31"><i class="bx bx-search"></i></span>
                          <input id="busqueda-tercero" type="text" class="form-control border-1 border " placeholder="Buscar cliente" aria-describedby="basic-addon-search31" />
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Nit</label>
                      <input type="text" class="form-control form-control-sm" id="nit" readonly />
                      <input type="number" id="id_tercero" name="id_tercero" readonly value="0" style="display:none" />
                      <input type="hidden" id="id_tercero_dir" readonly value="0" />
                    </div>
                    <div class="col-md-6">
                      <div>
                        <label class="form-label">Tercero</label>
                        <input type="text" class="form-control form-control-sm" id="cliente" name="cliente" readonly />
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div>
                        <label class="form-label">Fecha inicial</label>
                        <input type="date" class="form-control form-control-sm border-1 border " id="fecha-inicial" name="fecha-inicial" />
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div>
                        <label class="form-label">Fecha final</label>
                        <input type="date" class="form-control form-control-sm border-1 border " id="fecha-final" name="fecha-final" />
                      </div>
                    </div>
                    <div class="col mt-3">
                      <button class="btn btn-outline-danger btn-sm" id="buscar-factura"><i class="fa-solid fa-magnifying-glass"></i> Buscar factura</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-4">
              <div class="card h-100 mb-3">
                <h6 class="card-header text-primary form-label">Condiciones</h6>
                <div class="card-body">
                  <div class="row">
                    <div class="col-12">
                      <label class="mb-1 mt-3 form-label">Condiciones</label>
                      <div class="input-group input-group-sm input-group-merge"> <span class="input-group-text"><i class="fa-solid fa-percent"></i></span>
                        <select class="form-control form-control-sm" id="id_condicion">
                        </select>
                      </div>
                    </div>
                    <div class="col-12 ">
                      <label class="mb-1 mt-3 form-label">Tipos de documentos</label>
                      <div class="input-group input-group-sm input-group-merge"> <span class="input-group-text"><i class="fa-solid fa-grip-lines"></i></span>
                        <select class="form-control form-control-sm" id="tipos">
                        </select>
                      </div>
                    </div>
                    <div class="col-12">
                      <label class="mb-1 mt-3 form-label">Bodegas</label>
                      <div class="input-group input-group-sm input-group-merge"> <span class="input-group-text"><i class="fa-solid fa-map-location-dot"></i></span>
                        <select class="form-control form-control-sm" id="bodegas">
                        </select>
                      </div>
                    </div>
                    <div class="col-12">
                      <label class="form-label">Notas</label>
                      <textarea class="form-control form-control-sm alert-warning" id="notas"></textarea>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="row mt-3 div-result d-none">
            <div class="col">
              <h4>Facturas encontradas</h4>
            </div>
            <div id="result" class="card shadow"></div>
          </div>
        </div>
        <!-- TAB 2 -->
        <div class="tab-pane fade" id="navs-items" role="tabpanel">


          <div class="row mb-2">

            <div class="col-md-9">
              <div class="row">
                <div class="col-md-3">
                  <div class="input-group input-group-sm input-group-merge"> <span class="input-group-text">Valor Bruto</span>
                    <input type="text" class="form-control form-control-sm text-end" name="t_bruto" id="t_bruto" value="0" readonly>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="input-group input-group-sm input-group-merge"> <span class="input-group-text">Dcto</span>
                    <input type="text" class="form-control form-control-sm text-end" name="t_dcto" id="t_dcto" value="0" readonly>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="input-group input-group-sm input-group-merge"> <span class="input-group-text">Iva</span>
                    <input type="text" class="form-control form-control-sm text-end" name="t_iva" id="t_iva" value="0" readonly>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="input-group input-group-sm input-group-merge "> <span class="input-group-text">Total</span>
                    <input type="text" class="form-control form-control-sm text-end" name="t_total" id="t_total" value="0" readonly>
                  </div>
                </div>
              </div>
              <!-- row -->
            </div>
            <!-- col-md-9 -->
          </div>
          <!-- row-->
          <hr>
          <div class="row">
            <div class="table-response-lg" id="items-devolver"></div>
          </div>
        </div>
        <!-- TAB 3 OPCIONAL -->
        <div class="tab-pane fade " id="navs-fe" role="tabpanel">
          <div class="card-body">
            <div class="row g-3 align-items-end">

              <!-- Fecha inicial -->
              <div class="col-md-4">
                <label class="form-label" for="fe_fecha_inicial">
                  Fecha inicial
                </label>

                <div class="input-group input-group-sm">
                  <span class="input-group-text">
                    <i class="fa-regular fa-calendar-check"></i>
                  </span>

                  <input type="date"
                    class="form-control form-control-sm"
                    id="fe_fecha_inicial"
                    aria-describedby="fe_fecha_inicial">
                </div>
              </div>

              <!-- Fecha final -->
              <div class="col-md-4">
                <label class="form-label" for="fe_fecha_final">
                  Fecha final
                </label>

                <div class="input-group input-group-sm">
                  <span class="input-group-text">
                    <i class="fa-regular fa-calendar-check"></i>
                  </span>

                  <input type="date"
                    class="form-control form-control-sm"
                    id="fe_fecha_final"
                    aria-describedby="fe_fecha_final">
                </div>
              </div>

              <!-- Botón -->
              <div class="col-md-4">
                <button type="button"
                  class="btn btn-sm btn-primary w-100"
                  id="consultar-fe">
                  <i class="fa-solid fa-magnifying-glass"></i>
                  Consultar
                </button>
              </div>

            </div>

            <div class="row mt-3">
              <div class="col">
                <div id="listado-facturas" class="table-responsive"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<div id="modal-container-terceros"></div>
<!--  MODAL DE FACTURAS -->
<div class="modal fade" id="modalFacturas" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel4">facturas asociadas</h5>
        <button
          type="button"
          class="btn-close"
          data-bs-dismiss="modal"
          aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="card">
          <div class="row" style="display:none">
            <div class="col-6">
              <label for="">Cliente</label>
              <input type="hidden" readonly id="tipo_factura" />
              <input
                type="text"
                class="form-control form-control-sm"

                aria-label="Search..."
                aria-describedby="basic-addon-search31"
                id="fact-cliente"
                readonly />
            </div>
            <div class="col-6">
              <label for="">Direccion</label>
              <input type="hidden" id="id_direccion" value="" readonly />
              <input
                type="text"
                class="form-control form-control-sm"
                aria-label="Search..."
                aria-describedby="basic-addon-search31"
                id="fact-direccion"
                readonly />
            </div>
          </div>
          <!-- row -->
        </div>
        <hr>
        <h5>Listado de facturas</h5>
        <div id="result-facturas" class="table-responsive text-nowrap"></div>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="modalFacturasDetalle" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel4">Detalle de factura <span id="documento-ref"></span></h5>
        <button
          type="button"
          class="btn-close"
          data-bs-dismiss="modal"
          aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-4">
            <label for="">Condición</label>
            <input
              type="text"
              class="form-control form-control-sm"

              aria-label="Search..."
              aria-describedby="basic-addon-search31"
              id="condicion"
              readonly />
          </div>
          <div class="col-4">
            <label for="">Vencimiento</label>
            <input
              type="text"
              class="form-control form-control-sm"

              aria-label="Search..."
              aria-describedby="basic-addon-search31"
              id="vencimiento"
              readonly />
          </div>
          <div class="col-4">
            <label for="">Dias</label>
            <input
              type="text"
              class="form-control form-control-sm"

              aria-label="Search..."
              aria-describedby="basic-addon-search31"
              id="dias-vencimiento"
              readonly />
          </div>
        </div>
        <div class="row">
          <div class="col-6">
            <label for="">Codigo zona de ventas</label>
            <input
              type="text"
              class="form-control form-control-sm"

              aria-label="Search..."
              aria-describedby="basic-addon-search31"
              id="cod_zona"
              readonly />
          </div>
          <div class="col-6">
            <label for="">Zona</label>
            <input
              type="text"
              class="form-control form-control-sm"

              aria-label="Search..."
              aria-describedby="basic-addon-search31"
              id="zona"
              readonly />
          </div>
        </div>
        <!-- row -->
        <div class="row">
          <div class="col-md-6">
            <label for="">Filtrar</label>
            <div class="input-group mb-3">
              <span class="input-group-text" id="basic-addon11">
                <i class="menu-icon fa-solid fa-search text-primary"></i></span>
              <input
                type="text"
                class="form-control form-control-sm"
                value=""
                placeholder="Filtrar por referencia o descripcion"
                id="filtro-items" />
            </div>
          </div>
          <div class="col-md-6">
            <label for="">Valor devolución</label>
            <div class="input-group input-group-sm mb-3"> <span class="input-group-text" id="basic-addon11"><i class="menu-icon fa-solid fa-sack-dollar text-primary"></i></span>
              <input
                type="text"
                class="form-control"
                disabled
                readonly
                value="$0"
                id="valor-devolucion-detalle-factura" />
            </div>
          </div>
        </div>
        <div class="table-responsive text-nowrap" id="result-facturas-detalle"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" id="cargar-item"><i class="fa-solid fa-arrow-right-to-bracket"></i>&nbsp;Cargar items</button>
      </div>
    </div>
  </div>
</div>

<?php

$scriptsPagina = [
  '../controllers/DevVentas.js?' . rand()
];

include '../dependencies/footer.php';

?>