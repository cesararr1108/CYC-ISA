<?php
session_start();

$titulo = 'Pedidos de ventas';
include '../dependencies/header.php';
?>
<style>
  .swal2-container {
    z-index: 20000 !important;
    font-size: 0.8rem !important;
  }

  .form-control:focus {
    background-color: #f5f2ff !important;
  }

  #carrito thead tr th {
    font-size: 0.8rem;
    padding: 3px;
  }

  #carrito tbody tr td {
    font-size: 0.7rem;
    padding: 3px;
  }

  .form-control-plaintext {
    background-color: #ffffff !important;
  }

  #descripcion_grid,
  #stock_grid,
  #laboratorio_grid:disabled {
    background-color: white;
    border-left: none;
    border-right: none;
    border-top: none;
  }

  .data-lotes tbody tr:focus {
    background-color: rgb(95, 50, 255);
    color: #ffffff;
    border: none;
    outline: none;
    cursor: pointer;
  }

  .form-control-sm {
    padding: 1px
  }

  .timeline {
    position: relative;
    padding: 20px 0;
  }

  .timeline::before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    width: 4px;
    background: #ddd;
    left: 50%;
    margin-left: -2px;
  }

  .timeline-item {
    padding: 20px 40px;
    position: relative;
    width: 50%;
  }

  .timeline-item:nth-child(odd) {
    left: 0;
    text-align: right;
  }

  .timeline-item:nth-child(even) {
    left: 50%;
  }

  .timeline-icon {
    position: absolute;
    top: 20px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    text-align: center;
    line-height: 40px;
    color: #fff;
  }

  .timeline-item:nth-child(odd) .timeline-icon {
    right: -20px;
  }

  .timeline-item:nth-child(even) .timeline-icon {
    left: -20px;
  }

  .timeline-content {
    padding: 20px;
    background: #f8f9fa;
    border-radius: 5px;
    position: relative;
  }

  .timeline-title {
    font-weight: bold;
    margin-bottom: 5px;
  }

  .timeline-text {
    margin: 0;
  }
</style>
<input type="hidden" value="" id="tmp" readonly>
<input type="hidden" value="" id="vendedor" readonly>
<input type="hidden" id="id_bodega_session" value="<?php if (!empty($_SESSION["Ses_id_bodega"])) {
                                                      echo $_SESSION["Ses_id_bodega"];
                                                    } else {
                                                      echo  '0';
                                                    } ?>" />
<div class="card">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Comercial / </span>Pedidos de ventas</h5>
    <div class="nav-align-top">
      <ul class="nav nav-tabs" role="tablist" id="tabs">
        <li class="nav-item">
          <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-tercero" aria-controls="navs-tercero" aria-selected="true">
            <i class="fa-solid fa-user-tie"></i> &nbsp;Cliente
          </button>
        </li>
        <li class="nav-item">
          <button id="btn-navs-creacion" disabled type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-creacion" aria-controls="navs-creacion" aria-selected="false">
            <i class="fa-solid fa-cart-plus"></i>&nbsp;Carrito
          </button>
        </li>
        <li class="nav-item">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-temporal" aria-controls="navs-temporal" aria-selected="false">
            <i class="fa-solid fa-list-check"></i> &nbsp;Mis pedidos
          </button>
        </li>
        <li class="nav-item" style="flex:1;"> </li>
        <li class="nav-item border-danger">
          <button type="button" class="btn btn-sm btn-outline-secondary" id="nuevo"> <i class="fa-regular fa-file"></i> Nuevo</button>
          <button type="button" class="btn btn-sm btn-outline-primary" id="guardar"> <i class="fa-regular fa-floppy-disk"></i> Guardar</button>
        </li>
      </ul>
      <div class="tab-content " style="height: calc(100vh-20);">
        <div class="tab-pane fade show active m-0 p-0" id="navs-tercero" role="tabpanel">
          <form id="FormEncabezado" class="needs-validation  form-basico" novalidate>
            <div class="row">
              <div class="col-md-8">
                <div class="card p-0">
                  <h5 class="card-header form-label text-primary">Tercero</h5>
                  <div class="card-body">
                    <label for="txtBusquedaTercero" class="form-label">Búsqueda de tercero</label>
                    <div class="input-group input-group-sm input-group-merge"> <span class="input-group-text"><i class="bx bx-search"></i></span>
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
                    <select id="id_tercero_dir" name="id_tercero_dir" class="form-select form-select-sm">
                    </select>
                    <div class="row">
                      <div class="col-6">
                        <label for="lista_almacen" class="form-label">Lista Tercero</label>
                        <input type="text" class="form-control form-control-sm" value="0" id="lista_tercero" readonly />
                      </div>
                      <div class="col-6">
                        <label for="lista_almacen" class="form-label">Lista Almacén</label>
                        <input type="text" class="form-control form-control-sm" value="0" id="lista_almacen" readonly />
                      </div>
                    </div>
                    <div class="row mt-3">
                      <div class="col-12">
                        <button
                          type="button"
                          class="btn btn-sm btn-outline-primary"
                          data-bs-toggle="modal"
                          data-bs-target="#modalInfo"> <i class="fa-regular fa-square-plus"></i>&nbsp;Ver mas información </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="card mb-1">
                  <h5 class="card-header form-label text-primary">Condiciones</h5>
                  <div class="card-body">
                    <label for="condicion" class="form-label">Tipo</label>
                    <select class="form-select form-select-sm" id="tipos" name="id_tipo_documentos">
                    </select>
                    <label for="condicion" class="form-label">Condicion de pago</label>
                    <select class="form-select form-select-sm" id="id_condicion" name="id_condicion">
                    </select>
                    <label for="bodega" class="form-label">Bodega</label>
                    <select class="form-select form-select-sm" id="bodegas" name="id_bodega">
                    </select>
                    <label for="notas" class="form-label">Notas</label>
                    <textarea class="form-control" id="notas" name="notas" maxlength="500" rows="6"></textarea>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>
        <div class="tab-pane fade " id="navs-creacion" role="tabpanel">
          <div class="row">
            <div class="col-md-12">
              <input type="number" style="display: none;" id="id_pedido" readonly value="0">
              <div class=" mb-2">
                <div class="row mb-2">
                  <div class="col-md-3">
                    <div class="input-group input-group-sm"> <span class="input-group-text" id="basic-addon11">Numero</span>
                      <input name="numero" id="numero" type="number" class="form-control form-control-sm border border-1 border-primary" aria-describedby="basic-addon11" readonly />
                    </div>
                  </div>
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
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="row mb-2">
            <div class="col-md-8"></div>
            <div class="col-md-4">

            </div>
          </div>
          <div class="row">
            <div class="col-md-8">
              <span>Búsqueda de productos</span>
              <div class="input-group input-group-merge">
                <span class="input-group-text">
                  <i class="bx bx-search"></i>
                </span>
                <input name="" id="filtro" type="text" class="form-control form-control-sm filtros-busqueda-productos" placeholder="Código, descripción, descripción larga" aria-label="Buscar..." autocomplete="off" />
                <span class="input-group-text btn btn-sm btn-primary" id="btnBusquedaAvanzada">
                  <i class="fa-solid fa-sliders"></i>
                </span>
              </div>
            </div>
            <div class="col-md-4">
              <span>Stock</span>
              <div class="input-group">
                <span class="input-group-text alert-primary text-white" id="basic-addon11">
                  <i class="fa-solid fa-cart-flatbed"></i>
                </span>
                <input type="text" class="form-control form-control-sm data-grid text-center" id="stock_grid" disabled />
              </div><!--  <div class="input-group"> -->

            </div><!--  <div class="col-md-4"> -->
          </div><!--   <div class="row"> -->
          <div class="row mt-2">
            <div class="col-8">
              <input type="text" class="form-control form-control-sm data-grid" id="descripcion_grid" disabled />
            </div>
            <div class="col-4">
              <input type="text" class="form-control form-control-sm data-grid" id="laboratorio_grid" disabled>
            </div>
          </div>
          <div class="row mt-2">
            <div class="col-12 ">
              <table class=" table-data-referencia " cellpadding="2" cellspacing="0" width="100%">
                <thead class="table-primary">
                  <tr class="">
                    <th><b>Código</b></th>
                    <th><b>Precio b</b></th>
                    <th><b>Dto</b></th>
                    <th><b>Iva</b></th>
                    <th><b>Precio n.</b></th>
                    <th rowspan="2"> <select class="form-control form-control-sm data-grid" id="und_grid" style="height: 60px;width: 120px;" multiple></select></th>
                    <th width="15%"><b>Cant</b></th>
                    <th><b>Total</b></th>
                  </tr>
                  <tr>
                    <td><input type="text" size="5" class="form-control form-control-sm data-grid " id="codigo_grid" readonly></td>
                    <td><input type="text" class="form-control form-control-sm data-grid" id="preciob_grid" readonly></td>
                    <td><input type="text" class="form-control form-control-sm data-grid" id="dto_grid" size="4" readonly></td>
                    <td><input type="text" class="form-control form-control-sm data-grid" id="iva_grid" size="4" readonly></td>
                    <td><input type="text" class="form-control form-control-sm data-grid" id="precion_grid" readonly></td>
                    <td width="15%"><input type="number" min="1" class="form-control form-control-sm table-primary" data-grid id="cantidad_grid"></td>
                    <td><input type="text" class="form-control form-control-sm data-grid" id="total_grid" readonly></td>
                  </tr>
                </thead>
              </table>
            </div>
          </div>

          <input type="number" id="stock_real" value="0" readonly style="display:none ;" />
          <input type="number" id="stock_disponible" value="0" readonly style="display:none" />
          <input type="hidden" id="precio_unitario" value="0" readonly />
          <input type="number" id="id_referencia" value="0" readonly style="display:none ;" />
          <input type="text" id="costo_promedio" value="0" style="display: none;" readonly />
          <input type="number" id="id_detalle" style="display:none;" value="0" readonly>
          <div id="dvReferencias" class="mt-3 table-responsive-md">
            <table class="table table-sm" id="carrito" style="width: 100%;">
              <thead class="table-primary">
                <tr>
                  <th>Código</th>
                  <th>Descripción</th>
                  <th>Precio b</th>
                  <th class="text-center">Dcto</th>
                  <th class="text-center">Iva</th>
                  <th>Precio n</th>
                  <th class="text-center">Cant</th>
                  <th class="text-center">Und</th>
                  <th class="text-center">Conv</th>
                  <th>Total</th>
                  <th></th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
              </tbody>
            </table>
          </div>
        </div>
        <div class="tab-pane fade " id="navs-temporal" role="tabpanel">
          <div class="row g-3 align-items-end">
            <div class="col-12 col-md-3">
              <label class="form-label">Fecha inicial</label>
              <div class="input-group input-group-sm">
                <span class="input-group-text">
                  <i class="fa-regular fa-calendar-check"></i>
                </span>
                <input type="date" class="form-control" id="fecha_inicial">
              </div>
            </div>
            <div class="col-12 col-md-3">
              <label class="form-label">Fecha final</label>
              <div class="input-group input-group-sm">
                <span class="input-group-text">
                  <i class="fa-regular fa-calendar-check"></i>
                </span>
                <input type="date" class="form-control" id="fecha_final">
              </div>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label">Cliente</label>
              <div class="input-group input-group-sm">
                <span class="input-group-text">
                  <i class="bx bx-search"></i>
                </span>
                <input
                  id="txtBusquedaTerceroTmp"
                  type="text"
                  class="form-control"
                  placeholder="Buscar cliente">
              </div>
              <input type="hidden" id="nit_tmp" readonly>
            </div>
            <div class="col-12 col-md-2">
              <div class="d-flex gap-2">
                <button class="btn btn-primary btn-sm flex-fill" id="buscar-temporal">
                  <i class="fa-solid fa-magnifying-glass"></i>
                </button>

                <button class="btn btn-outline-secondary btn-sm flex-fill" id="limpiar-filtros">
                  <i class="fa-solid fa-eraser"></i>
                </button>
              </div>
            </div>
          </div>
          <div class="row mt-3">
            <div class="col-12 d-flex">
              <div class="flujo-pedidos" aria-label="Estados del proceso">
                <!-- Temporales -->
                <button type="button"
                  id="btn_temporales"
                  class="btn btn-sm btn-outline-secondary filtros-estados">
                  <i class="fa-regular fa-file me-1"></i>
                  <span class="d-none d-sm-inline">Temporales</span>
                  <span class="d-inline d-sm-none">T</span>
                  <span id="p_temporales" class="badge text-bg-secondary ms-1">0</span>
                </button>

                <!-- Confirmados -->
                <button type="button"
                  id="btn_confirmados"
                  class="btn btn-sm btn-outline-warning filtros-estados">
                  <i class="fa-solid fa-circle-check me-1"></i>
                  <span class="d-none d-sm-inline">Confirmados</span>
                  <span class="d-inline d-sm-none">C</span>
                  <span id="p_confirmados" class="badge text-bg-warning ms-1">0</span>
                </button>

                <!-- Separados -->
                <button type="button"
                  id="btn_separados"
                  class="btn btn-sm btn-outline-info filtros-estados">
                  <i class="fa-solid fa-boxes-stacked me-1"></i>
                  <span class="d-none d-sm-inline">Separados</span>
                  <span class="d-inline d-sm-none">S</span>
                  <span id="p_separados" class="badge text-bg-info ms-1">0</span>
                </button>

                <!-- Facturados -->
                <button type="button"
                  id="btn_facturados"
                  class="btn btn-sm btn-outline-success filtros-estados">
                  <i class="fa-solid fa-file-invoice-dollar me-1"></i>
                  <span class="d-none d-sm-inline">Facturados</span>
                  <span class="d-inline d-sm-none">F</span>
                  <span id="p_facturados" class="badge text-bg-success ms-1">0</span>
                </button>

                <!-- Facturados -->
                <button type="button"
                  id="btn_despachados"
                  class="btn btn-sm btn-outline-primary filtros-estados">
                  <i class="fa-solid fa-truck-fast me-1"></i>
                  <span class="d-none d-sm-inline">Despachados</span>
                  <span class="d-inline d-sm-none">D</span>
                  <span id="p_despachados" class="badge text-bg-dark ms-1">0</span>
                </button>
              </div>
            </div>
          </div>
          <div class="row mt-3">
            <div class="col-12">
              <div id="temporales"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalAdd" aria-hidden="true" data-bs-backdrop="static">
  <form id="FormDetalle" class="needs-validation  form-basico" novalidate>
    <div class="modal-dialog modal-xl " role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="">Agregar referencia</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="">
          <input type="hidden" id="costo_unitario" value="0" readonly>
          <input type="hidden" id="id_detalle" value="0" readonly>
          <div class="row mb-3">
            <div class="col-md-6">
              <div class="form-floating">
                <input type="text" class="form-control" id="descripcion_corta" placeholder="" readonly>
                <label for="floatingInput">Descripción</label>
                <!--<div id="floatingInputHelp" class="form-text">We'll never share your details with anyone else.</div>-->
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-floating">
                <input type="text" class="form-control" id="descripcion_larga" placeholder="" readonly>
                <label for="floatingInput">Descripción larga</label>
                <!--<div id="floatingInputHelp" class="form-text">We'll never share your details with anyone else.</div>-->
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-3">
              <label for="codigo" class="form-label">Codigo</label>
              <input type="hidden" id="id_referencia" name="id_referencia" value="0" readonly>
              <input type="text" class="form-control form-control-sm" id="codigo" required readonly>
            </div>
            <div class="col-md-3">
              <label for="costo" class="form-label">Costo</label>
              <input type="text" class="form-control form-control-sm format-num" maxlength="21" min="1" tabindex="1" required name="costo_unitario" id="costo">
              <div class="invalid-feedback"> El costo debe ser mayor a 0 </div>
            </div>
            <div class="col-md-3">
              <label for="unidad" class="form-label">Unidad</label>
              <select class="form-select form-select-sm" tabindex="2" id="unidad" required multiple style="height: 60px;">
              </select>
              <div class="invalid-feedback"> Debe seleccionar un unidad de conversion </div>
            </div>
            <div class="col-md-3">
              <label for="iva" class="form-label">IVA</label>
              <!-- <select class="form-select form-select-sm" name="pcj_iva" id="iva" required > 
              </select>-->
              <input type="text" class="form-control form-control-sm" id="iva" name="pcj_iva" readonly />
            </div>
          </div>
          <div class="row">
            <div class="col-md-3">
              <label for="costo_neto" class="form-label">Costo Neto</label>
              <input type="text" class="form-control form-control-sm format-num" id="costo_neto" readonly>
            </div>
            <div class="col-md-3">
              <label for="lote" class="form-label">lote</label>
              <input id="id_lote" name="id_lote" type="hidden" value="0" readonly>
              <input type="text" class="form-control form-control-sm" tabindex="3" name="lote" required id="lote">
              <div class="invalid-feedback"> Debe digitar el lote </div>
            </div>
            <div class="col-md-3">
              <label for="vmcto" class="form-label">vencimiento</label>
              <input type="date" class="form-control form-control-sm" tabindex="4" id="vmcto" name="vmcto" required>
              <div class="invalid-feedback"> Debe digitar la fecha de vencimiento </div>
            </div>
            <div class="col-md-3">
              <label for="cantidad" class="form-label">cantidad</label>
              <input type="number" class="form-control form-control-sm" tabindex="5" min="1" name="cantidad" id="cantidad" required onKeyPress="return vnumeros(event)">
              <div class="invalid-feedback"> La cantidad debe ser mayor a 0 </div>
            </div>
          </div>
          <div class="row mt-2">
            <div class="col-12">
              <label>Stock </label>
              <div class="input-group"> <span class="input-group-text"><i class="fa-solid fa-truck-ramp-box"></i></span>
                <input type="text" class="form-control form-control-sm text-center" aria-describedby="stock" id="stock" readonly />
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-sm btn-outline-primary" tabindex="6" id="btnAddReferencia"> Agregar </button>
          <button type="reset" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal"> Cerrar </button>
        </div>
      </div>
    </div>
  </form>
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
            <td>Regulados</td>
            <td id="info_regulados"></td>
          </tr>
          <tr>
            <td>Controlados</td>
            <td id="info_controlados"></td>
          </tr>
          <tr>
            <td>Institucional</td>
            <td id="info_Institucional"></td>
          </tr>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal"> Cerrar </button>
      </div>
    </div>
  </div>
</div>

<!-- modalFechaLotes -->
<div class="modal fade" id="modalFechaLotes" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="">Selccion de lotes </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="result-lotes"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal"> Cerrar </button>
      </div>
    </div>
  </div>
</div>
<div id="modal-container"></div>

<!-- Modal opciones de pedidos -->
<div class="modal fade" id="modalOptions" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content shadow-lg border-0">

      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-0">
            <i class="fa-solid fa-file-circle-check me-2"></i>
            Gestión de pedido
          </h5>
          <small class="text-muted" id="mdl-header-info"></small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-3">

        <div class="card shadow-sm border-0 mb-3">
          <div class="card-body py-2">
            <div class="row text-center">
              <div class="col-md-3">
                <small class="text-muted">ID Pedido</small>
                <div class="fw-bold" id="mdl-id-pedido"></div>
              </div>
              <div class="col-md-3">
                <small class="text-muted">Pedido</small>
                <div class="fw-bold" id="mdl-pedido"></div>
              </div>
              <div class="col-md-3">
                <small class="text-muted">Estado</small>
                <div id="mdl-estado"></div>
              </div>
              <div class="col-md-3">
                <small class="text-muted">Usuario</small>
                <div class="fw-bold" id="mdl-usuario"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="card shadow-sm border-0">
          <div class="card-header bg-white p-0">
            <ul class="nav nav-tabs" role="tablist">
              <li class="nav-item">
                <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-opciones">
                  <i class="fa-solid fa-list-check me-1"></i> Opciones
                </button>
              </li>
              <li class="nav-item">
                <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-flujo">
                  <i class="fa-solid fa-timeline me-1"></i> Flujo
                </button>
              </li>
            </ul>
          </div>

          <div class="tab-content p-3">

            <div class="tab-pane fade show active" id="navs-opciones">
              <div class="row g-2">
                <div class="col-md-4">
                  <div class="list-group shadow-sm">
                    <button id="btn-desbloquear" class="list-group-item list-group-item-action">
                      <i class="fa-solid fa-lock-open text-success me-2"></i>Desbloquear
                    </button>
                    <button id="btn-guardar" class="list-group-item list-group-item-action">
                      <i class="fa-solid fa-circle-check text-warning me-2"></i>Guardar / Confirmar
                    </button>
                    <button id="btn-editar" class="list-group-item list-group-item-action">
                      <i class="fa-solid fa-pen-to-square text-primary me-2"></i>Editar pedido
                    </button>
                    <button id="btn-eliminar" class="list-group-item list-group-item-action">
                      <i class="fa-solid fa-trash text-danger me-2"></i>Eliminar
                    </button>
                    <button id="btn-pdf-pedido" class="list-group-item list-group-item-action">
                      <i class="fa-solid fa-file-pdf text-danger me-2"></i>PDF Pedido
                    </button>
                    <button id="btn-pdf-factura" class="list-group-item list-group-item-action">
                      <i class="fa-solid fa-file-invoice text-success me-2"></i>PDF Factura
                    </button>
                  </div>
                </div>

                <div class="col-md-8">
                  <div class="list-group shadow-sm">

                    <div class="list-group-item d-flex justify-content-between align-items-center">
                      <span class="fw-semibold">
                        <i class="fa-regular fa-calendar me-2 text-primary"></i>Fecha
                      </span>
                      <span id="mdl-fecha" class="text-muted"></span>
                    </div>

                    <div class="list-group-item d-flex justify-content-between align-items-center">
                      <span class="fw-semibold">
                        <i class="fa-solid fa-user me-2 text-primary"></i>Cliente
                      </span>
                      <span id="mdl-cliente" class="text-muted text-end"></span>
                    </div>

                    <div class="list-group-item d-flex justify-content-between align-items-center">
                      <span class="fw-semibold">
                        <i class="fa-solid fa-key me-2 text-warning"></i>Autorización
                      </span>
                      <span id="mdl-autorizacion" class="text-muted"></span>
                    </div>

                    <div class="list-group-item d-flex justify-content-between align-items-center">
                      <span class="fw-semibold">
                        <i class="fa-solid fa-file-invoice me-2 text-success"></i>Factura
                      </span>
                      <span id="mdl-factura" class="text-muted"></span>
                    </div>

                    <div class="list-group-item">
                      <div class="fw-semibold mb-1">
                        <i class="fa-solid fa-note-sticky me-2 text-secondary"></i>Notas
                      </div>
                      <div id="mdl-notas" class="text-muted small"></div>
                    </div>

                  </div>
                </div>
              </div>
            </div>
            <div class="tab-pane fade" id="navs-flujo">
              <div id="pedido-timeline" class="pedido-timeline">
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>

    </div>
  </div>
</div>

<?php

$scriptsPagina = [
  '../controllers/Pedidos.js?' . rand()
];

include '../dependencies/footer.php';

?>