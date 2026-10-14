<?php
session_start();

$titulo = 'Salidas de inventarios';
include '../dependencies/header.php';
?>
<input type="hidden" value="<?php echo $_SESSION["Ses_nombres"] . ' ' . $_SESSION["Ses_apellidos"];  ?>" readonly id="nombres_usuario" />
<input type="hidden" value="" id="tmp" readonly />
<input type="hidden" value="" id='vendedor' readonly />
<input type="hidden" id="id_bodega_session" value="<?php if (!empty($_SESSION["Ses_id_bodega"])) {
                                                      echo $_SESSION["Ses_id_bodega"];
                                                    } else {
                                                      echo  '0';
                                                    } ?>" />


<div class="card " style="height: auto;">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Inventarios / </span>Salidas de inventarios</h5>
    <div class="nav-align-top">
      <ul class="nav nav-tabs" role="tablist" id="tabs">
        <li class="nav-item">
          <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-tercero" aria-controls="navs-tercero" aria-selected="true"> <i class="fa-solid fa-user-tie"></i> &nbsp;Cliente </button>
        </li>
        <li class="nav-item">
          <button id="btn-navs-creacion" disabled type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-creacion" aria-controls="navs-creacion" aria-selected="false"><i class="fa-solid fa-cart-plus"></i>&nbsp;Carrito </button>
        </li>
        <li class="nav-item">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-temporal" aria-controls="navs-temporal" aria-selected="false"> <i class="fa-regular fa-clock"></i> &nbsp;Temporal </button>
        </li>
        <li class="nav-item" style="flex:1;">

        </li>
        <li class="nav-item border-danger">
          <button type="button" class="btn btn-sm btn-outline-secondary" id="nuevo"> <i class="fa-regular fa-file"></i> Nuevo</button>
          <button type="button" class="btn btn-sm btn-primary" id="guardar"> <i class="fa-regular fa-floppy-disk"></i> Guardar</button>
        </li>
      </ul>
      <div class="tab-content">

        <div class="tab-pane fade show active m-0 p-0" id="navs-tercero" role="tabpanel">

          <form id="FormEncabezado" class="needs-validation  form-basico" novalidate>
            <div class="row">
              <div class="col-md-8">
                <div class="card h-100 p-0">
                  <h5 class="card-header form-label text-primary">Tercero</h5>
                  <div class="card-body">
                    <label for="txtBusquedaTercero" class="form-label">Búsqueda de tercero</label>
                    <div class="input-group input-group-sm input-group-merge">
                      <span class="input-group-text"><i class="bx bx-search"></i></span>
                      <input type="text" class="form-control" placeholder="Búsqueda de tercero..." aria-label="Búsqueda de tercero..." aria-describedby="basic-addon-search" id="txtBusquedaTercero">
                    </div>
                    <label for="id_tercero" class="form-label"></label>
                    <input type="hidden" id="id_tercero" name="id_tercero" class="form-control form-control-sm" readonly value="0">
                    <label for="nit" class="form-label">NIT</label>
                    <input type="text" class="form-control form-control-sm" name="nit" id="nit" readonly>
                    <label for="nombres" class="form-label">Tratamiento</label>
                    <input type="text" class="form-control form-control-sm text-uppercase" name="tratamiento" id="tratamiento" readonly>
                    <label for="txtRazon" class="form-label">Razón Comercial</label>
                    <input type="text" class="form-control form-control-sm text-uppercase" name="razon_comercial" id="razon_comercial" readonly>
                    <label for="textDireccion" class="form-label">Dirección</label>
                    <select id="id_tercero_dir" name="id_tercero_dir" class="form-select form-select-sm"></select>
                    <div class="row mt-3">
                      <div class="col-12">
                        <button
                          type="button"
                          class="btn btn-sm btn-outline-primary"
                          data-bs-toggle="modal"
                          data-bs-target="#modalInfo">
                          &nbsp;Ver mas informacion
                        </button>

                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="card h-100 mb-4">
                  <h5 class="card-header form-label text-primary">Condiciones</h5>
                  <div class="card-body">
                    <label for="condicion" class="form-label">Tipo</label>
                    <select class="form-select form-select-sm" id="tipos" name="id_tipo_documentos" disabled>
                    </select>
                    <label for="condicion" class="form-label">Condicion de pago</label>
                    <select class="form-select form-select-sm" id="id_condicion" name="id_condicion" disabled>
                    </select>
                    <label for="bodega" class="form-label">Bodega</label>
                    <select class="form-select form-select-sm" id="bodegas" name="id_bodega">
                    </select>
                    <label for="notas" class="form-label">Notas</label>
                    <textarea class="form-control" id="notas" name="notas"></textarea>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div><!--  <div class="tab-pane fade show active" id="navs-tercero" role="tabpanel"> -->

        <div class="tab-pane fade " id="navs-creacion" role="tabpanel">

          <div class="row mb-2">
            <div class="col-md-3">
              <div class="input-group input-group-sm ">
                <span class="input-group-text">Numero</span>
                <input
                  name="numero"
                  id="numero"
                  type="number"
                  class="form-control form-control-sm"
                  aria-describedby="basic-addon11"
                  readonly />
              </div>
            </div><!-- col-md-3 -->
            <div class="col-md-9">
              <div class="row">
                <div class="col-md-3">
                  <div class="input-group input-group-sm input-group-merge">
                    <span class="input-group-text">Valor Bruto</span>
                    <input type="text" class="form-control form-control-sm text-end" name="t_bruto" id="t_bruto" value="0" readonly>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="input-group input-group-sm input-group-merge">
                    <span class="input-group-text">Dcto</span>
                    <input type="text" class="form-control form-control-sm text-end" name="t_dcto" id="t_dcto" value="0" readonly>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="input-group input-group-sm input-group-merge">
                    <span class="input-group-text">Iva</span>
                    <input type="text" class="form-control form-control-sm text-end" name="t_iva" id="t_iva" value="0" readonly>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="input-group input-group-sm input-group-merge ">
                    <span class="input-group-text">Total</span>
                    <input type="text" class="form-control form-control-sm text-end" name="t_total" id="t_total" value="0" readonly>
                  </div>
                </div>
              </div><!-- row -->
            </div><!-- col-md-9 -->
          </div>


          <input type="number" style="display: none;" id="id_transaccion" readonly value="0">

          <div class="row">
            <div class="col-md-9">

            </div>
            <div class="col-md-3">

            </div>
          </div>


          <div class="row mt-2">
            <div class="col-md-8">
              <div class="form-label text-primary fw-bold">
                <span><i class="bx bx-search"></i>Búsqueda de productos</span>
                <div class="input-group input-group-merge mt-2">
                  <span class="input-group-text"><i class="bx bx-search"></i></span>
                  <input name="" id="filtro" type="text" class="form-control form-control-sm filtros-busqueda-productos" placeholder="Código, descripción, descripción larga" aria-label="Buscar..." autocomplete="off" />
                  <span class="input-group-text btn btn-sm btn-primary"
                    id="btnBusquedaAvanzada"><i class="fa-solid fa-sliders"></i></span>
                </div><!-- <div class="input-group input-group-merge">  -->
              </div><!-- <div class=" mb-2">  -->
            </div><!-- col-md-8 -->
            <div class="col-md-4">
              <div class="form-label text-primary fw-bold">
                <span><i class="fa-solid fa-boxes-stacked"></i> Stock</span>
                <div class="input-group input-group-merge mt-2">
                  <input name="stock_grid" id="stock_grid" type="text" class="form-control form-control-sm " readonly />
                  <span class="input-group-text btn btn-sm btn-primary"><i class="fa-solid fa-box-open"></i></span>
                </div><!-- <div class="input-group input-group-merge">  -->
              </div><!-- <div class=" mb-2">   -->
            </div>
            <div><!-- row -->
            </div>
          </div>

          <div class="row mt-2">
            <div class="col-6">
              <input type="text" class="form-control form-control-sm data-grid" id="descripcion_grid" disabled />
            </div>
            <div class="col-6">
              <input type="text" class="form-control form-control-sm data-grid" id="laboratorio_grid" disabled>
            </div>
          </div>

          <div class="row mt-1">
            <div class="col-12 div-table-data-referencia">
            </div>
          </div>
          <input type="number" id="stock_real" value="0" readonly style="display:none;" class="alert-warning" />
          <input type="number" id="id_lote" value="0" readonly style="display:none ; " class="alert-danger" />
          <input type="hidden" id="precio_unitario" value="0" readonly />
          <input type="number" id="id_referencia" value="0" readonly style="display: none;" class="alert-success" />
          <input type="hidden" id="costo_promedio" value="0" class="alert-secondary" readonly />
          <input type="number" id="id_detalle" style="display:none;" value="0" readonly class="alert-primary">

          <div id="dvReferencias" class="mt-3 table-responsive-md">

          </div>
        </div>
        <div class="tab-pane fade " id="navs-temporal" role="tabpanel">
          <div class="card mb-4">
            <div class="card-body">
              <div class="row align-items-end g-2">
                <div class="col-md-2">
                  <label class="form-label">Fecha inicial</label>
                  <div class="input-group input-group-sm">
                    <span class="input-group-text">
                      <i class="fa-regular fa-calendar-check"></i>
                    </span>
                    <input
                      type="date"
                      class="form-control"
                      id="fecha_inicial">
                  </div>
                </div>
                <div class="col-md-2">
                  <label class="form-label">Fecha final</label>
                  <div class="input-group input-group-sm">
                    <span class="input-group-text">
                      <i class="fa-regular fa-calendar-check"></i>
                    </span>
                    <input
                      type="date"
                      class="form-control"
                      id="fecha_final">
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Cliente</label>
                  <div class="input-group input-group-sm">
                    <span class="input-group-text">
                      <i class="bx bx-search"></i>
                    </span>
                    <input
                      id="txtBusquedaTerceroTmp"
                      type="text"
                      class="form-control"
                      placeholder="Buscar cliente...">
                  </div>
                  <input type="hidden" id="nit_tmp" readonly>
                </div>
                <div class="col-md-2 d-grid">
                  <button
                    class="btn btn-sm btn-primary"
                    id="buscar-temporal">
                    <i class="fa-solid fa-magnifying-glass me-1"></i>
                    Consultar
                  </button>
                </div>
              </div>
              <div class="row mt-2">
                <div class="col">
                  <div id="temporales"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalInfo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalLongTitle">Informacion del cliente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <table class="table">
          <tr>
            <td>Condicion</td>
            <td id="info_condicion"></td>
          </tr>
          <tr>
            <td>Lista</td>
            <td id="info_lista"></td>
          </tr>
          <tr>
            <td>Cupo de credito</td>
            <td id="info_credito"></td>
          </tr>
          <tr>
            <td>Zona de ventas</td>
            <td id="info_zona_venta"></td>
          </tr>
          <tr>
            <td>Descripción zona</td>
            <td id="info_zona_descripcion"></td>
          </tr>
          <tr>
            <td>Email</td>
            <td id="info_email"></td>
          </tr>
          <tr>
            <td>Controlados</td>
            <td id="info_controlados"></td>
          </tr>
          <tr>
            <td>Institucional</td>
            <td id="info_institucional"></td>
          </tr>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">
          Cerrar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- modalFechaLotes -->
<!-- Large Modal -->
<div class="modal fade" id="modalFechaLotes" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="">Selccion de lotes </h5>
        <button
          type="button"
          class="btn-close"
          data-bs-dismiss="modal"
          aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="result-lotes"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">
          Cerrar
        </button>
      </div>
    </div>
  </div>
</div>

<div id="modal-container"></div>
<?php

$scriptsPagina = [
  '../controllers/Salidas.js?' . rand()
];

include '../dependencies/footer.php';

?>