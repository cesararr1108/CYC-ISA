<?php
session_start();

$titulo = 'Factura de ventas';
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
    font-size: 0.7rem;
    padding: 2px;
  }

  #carrito tbody tr td {
    font-size: 0.7rem;
    padding: 1px;
  }

  .datos tbody tr td {
    font-size: 0.7rem;
    padding: 1px;
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

  .ui-autocomplete {
    z-index: 2000 !important;
    /* mayor que el modal */
  }

  #modalDetallePedido {
    z-index: 99999;
  }

  .table-dif-cantidad-cantidad2 tbody tr td {
    font-size: 0.7rem;
  }

  .table-dif-cantidad-cantidad2 thead tr td {
    font-size: 0.7rem;
  }

  .label-totales {
    font-size: 0.72rem;
    font-weight: 600;
    width: 78px;
  }

  input[type=number]::-webkit-inner-spin-button,
  input[type=number]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
  }

  .nota-amarilla {
    background: #fff8d6;
    border: 1px solid #f3d97a;
    border-radius: 6px;
    color: #5c4b00;
    padding: .75rem;
    resize: vertical;
    box-shadow: inset 0 1px 2px rgba(0, 0, 0, .04);
    transition: all .2s ease;
  }

  .nota-amarilla:focus {
    background: #fffbe8;
    border-color: #e8bf3d;
    box-shadow: 0 0 0 .15rem rgba(255, 193, 7, .15);
    outline: none;
  }

  .nota-amarilla::placeholder {
    color: #9a8740;
    font-style: italic;
  }
</style>
<input type="hidden" value="<?php echo $_SESSION["Ses_nombres"] . ' ' . $_SESSION["Ses_apellidos"];  ?>" readonly id="nombres_usuario" />
<input type="hidden" value="" id="tmp" readonly />
<input type="hidden" value="" id='vendedor' readonly />
<input type="hidden" value="0" id='id_pedido' class="alert-danger" readonly />
<input type="hidden" value="0" id="id_pedidos_lotes" readonly />
<input type="hidden" id="id_bodega_session" value="<?php if (!empty($_SESSION["Ses_id_bodega"])) {
                                                      echo $_SESSION["Ses_id_bodega"];
                                                    } else {
                                                      echo  '0';
                                                    } ?>" />

<div class="card " style="height: auto;">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Comercial / </span>Factura de ventas</h5>
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
        <li class="nav-item">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-fe" aria-controls="navs-fe" aria-selected="false"><i class="fa-solid fa-file-invoice"></i> &nbsp;FE </button>
        </li>
        <li class="nav-item" style="flex:1;"> </li>
        <li class="nav-item border-danger btn-group">
          <div id="div_Btnpedidos"></div>
          <div>
            <button type="button" class="btn btn-sm btn-outline-secondary m-1" id="nuevo"> <i class="fa-regular fa-file"></i> Nuevo</button>
          </div>
          <div>
            <button type="button" class="btn btn-sm btn-primary m-1" id="guardar"> <i class="fa-regular fa-floppy-disk"></i> Guardar</button>
          </div>
        </li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane fade show active m-0 p-0" id="navs-tercero" role="tabpanel">
          <form id="FormEncabezado" class="needs-validation  form-basico" novalidate>
            <div class="row">
              <div class="col-md-8">
                <div class="card p-0">
                  <h5 class="card-header text-primary form-label fw-bold">Tercero</h5>
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
                <div class="card mb-4">
                  <h5 class="card-header text-primary form-label fw-bold">Condiciones</h5>
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
                    <input type="hidden" id="id_perfil_tributario" value="0" readonly>
                    <label class="form-label">Perfil tributario</label>
                    <select class="form-select form-select-sm" id="perfil_tributario_compra" disabled>
                      <option value="">NO APLICA</option>
                    </select>
                    <label class="form-label">Concepto de retención</label>
                    <select class="form-select form-select-sm" id="concepto_retencion" name="id_concepto_retencion">
                      <option value="">NO APLICA</option>
                    </select>
                    <label for="notas" class="form-label">Notas</label>
                    <textarea
                      class="form-control nota-amarilla"
                      id="notas"
                      name="notas"
                      maxlength="500"
                      rows="2"
                      placeholder=""></textarea>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>
        <div class="tab-pane fade " id="navs-creacion" role="tabpanel">
          <div class="row">
            <div class="col-md-3">
              <div class="input-group input-group-sm"> <span class="input-group-text">Numero pedido</span>
                <input name="numero_pedido" id="numero_pedido" type="number" class="form-control form-control-sm" aria-describedby="basic-addon11" readonly />
              </div>
              <div class="input-group input-group-sm mt-1"> <span class="input-group-text">Numero factura</span>
                <input name="numero" id="numero" type="number" class="form-control form-control-sm" aria-describedby="basic-addon11" readonly />
              </div>
            </div>
            <!-- col-md-3 -->
            <div class="col-md-9">
              <div class="row">
                <div class="col-md-3">
                  <div class="input-group input-group-sm input-group-merge">
                    <span class="input-group-text label-totales">Valor Bruto</span>
                    <input type="text" class="form-control form-control-sm text-end fw-bold" name="t_bruto" id="t_bruto" value="0" readonly>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="input-group input-group-sm input-group-merge">
                    <span class="input-group-text label-totales">Dcto</span>
                    <input type="text" class="form-control form-control-sm text-end fw-bold" name="t_dcto" id="t_dcto" value="0" readonly>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="input-group input-group-sm input-group-merge">
                    <span class="input-group-text label-totales">Iva</span>
                    <input type="text" class="form-control form-control-sm text-end fw-bold" name="t_iva" id="t_iva" value="0" readonly>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="input-group input-group-sm input-group-merge ">
                    <span class="input-group-text label-totales">Total</span>
                    <input type="text" class="form-control form-control-sm text-end fw-bold" name="t_total" id="t_total" value="0" readonly>
                  </div>
                </div>
              </div>
              <!-- row retenciones -->
              <div class="row mt-1">
                <div class="col-md-3">
                  <div class="input-group input-group-sm input-group-merge">
                    <span class="input-group-text label-totales">RteFte</span>
                    <input type="text" class="form-control form-control-sm text-end fw-bold" id="t_ret_fte" value="$0" readonly>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="input-group input-group-sm input-group-merge">
                    <span class="input-group-text label-totales">ReteICA</span>
                    <input type="text" class="form-control form-control-sm text-end fw-bold" id="t_ret_ica" value="$0" readonly>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="input-group input-group-sm input-group-merge">
                    <span class="input-group-text label-totales">ReteIVA</span>
                    <input type="text" class="form-control form-control-sm text-end  fw-bold" id="t_ret_iva" value="$0" readonly>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="input-group input-group-sm input-group-merge">
                    <span class="input-group-text label-totales">Tot. Ret.</span>
                    <input type="text" class="form-control form-control-sm text-end fw-bold" id="t_ret_total" value="$0" readonly>
                  </div>
                </div>
              </div>
              <!-- row neto a pagar -->
              <div class="row mt-1 justify-content-end">
                <div class="col-md-3">
                  <div class="input-group input-group-sm input-group-merge">
                    <span class="input-group-text text-primary label-totales">Neto Pagar</span>
                    <input type="text" class="form-control form-control-sm text-end text-primary fw-bold" id="t_neto_pagar" value="$0" readonly>
                  </div>
                </div>
              </div>
              <!-- row -->
            </div>
            <!-- col-md-9 -->
          </div>
          <input type="number" style="display:none ;" class="alert-warning" id="id_transaccion" readonly value="0">
          <div class="row">
            <div class="col-md-9"> </div>
            <div class="col-md-3"> </div>
          </div>
          <div class="row mt-2">
            <div class="col-md-8">
              <div class="form-label text-primary fw-bold">
                <!-- Encabezado con título y opción de EAN alineados -->
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span><i class="fa fa-search me-1"></i> Búsqueda de productos</span>
                  <!-- Checkbox interactivo con icono de código de barras -->
                  <label for="cod_barras" class="cursor-pointer d-flex align-items-center gap-1 text-muted fw-normal" style="font-size: 0.85rem;">
                    <i class="fa-solid fa-barcode"></i> EAN
                    <input type="checkbox" id="cod_barras" class="form-check-input mt-0 ms-1">
                  </label>
                </div>
                <!-- Grupo del input de búsqueda -->
                <div class="input-group input-group-merge">
                  <span class="input-group-text"><i class="bx bx-search"></i></span>
                  <input name="" id="filtro" type="text" class="form-control form-control-sm filtros-busqueda-productos" placeholder="Código, descripción, descripción larga" aria-label="Buscar..." autocomplete="off" />
                  <span class="input-group-text btn btn-sm btn-primary" id="btnBusquedaAvanzada" title="Búsqueda avanzada">
                    <i class="fa-solid fa-sliders"></i>
                  </span>
                </div>
              </div>
            </div>
            <!-- col-md-8 -->
            <div class="col-md-4">
              <div class="form-label text-primary fw-bold"> <span><i class="fa-solid fa-boxes-stacked"></i>&nbsp;Stock</span>
                <div class="input-group input-group-sm input-group-merge ">
                  <input name="stock_grid" id="stock_grid" type="text" class="form-control form-control-sm " readonly />
                  <span class="input-group-text ">
                    <!-- <i class="fa-solid fa-box-open"></i>  -->
                    <button type="button" class="btn btn-sm btn-outline-primary position-relative" id="btn_unidades_comprometidas" title="Unidades comprometidas">
                      <i class="fa fa-clock"></i>
                      <span id="unidades_comprometidas" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <span class="visually-hidden">0</span>
                      </span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary ms-1" id="btn_stock_otras_bodegas" title="Stock en otras bodegas">
                      <i class="fa fa-warehouse"></i>
                    </button>
                  </span>
                </div>
                <!-- <div class="input-group input-group-merge">  -->
              </div>
              <!-- <div class=" mb-2">   -->
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
              <table width="100%" class=" table-data-referencia" cellpadding="2" cellspacing="0">
                <thead>
                  <tr>
                    <th><b>Código</b></th>
                    <th><b>Precio b</b></th>
                    <th><b>Dto</b></th>
                    <th><b>Iva</b></th>
                    <th><b>Precio n.</b></th>
                    <th><b>Lote</b></th>
                    <th><b>Vcmto</b></th>
                    <th rowspan="2"> <select class="form-control p-0 data-grid" id="und_grid" style="height: 60px;width: 120px;" multiple>
                      </select></th>
                    <th><b>Cant</b></th>
                    <th><b>Total</b></th>
                  </tr>
                  <tr>
                    <td><input type="text" size="5" class="form-control form-control-sm data-grid " id="codigo_grid" readonly></td>
                    <td><input type="text" class="form-control form-control-sm data-grid" id="preciob_grid" readonly></td>
                    <td><input type="text" class="form-control form-control-sm data-grid" id="dto_grid" size="4"></td>
                    <td><input type="text" class="form-control form-control-sm data-grid" id="iva_grid" size="4" readonly></td>
                    <td>
                      <div class="input-group">
                        <input type="text" class="form-control form-control-sm data-grid" id="precion_grid" readonly>
                        <span id="basic-default-password2" class="input-group-text cursor-pointer btn btn-sm btn-outline-primary btn-editar-precio d-none"><i class="bx bx-pencil"></i></span>
                      </div>
                    </td>
                    <td><input type="text" class="form-control form-control-sm data-grid" id="lote_grid" readonly></td>
                    <td><input type="date" size="6" class="form-control form-control-sm data-grid" id="vcmto_grid" readonly></td>
                    <td><input type="number" min="1" class="form-control form-control-sm table-primary" data-grid id="cantidad_grid"></td>
                    <td><input type="text" class="form-control form-control-sm data-grid" id="total_grid" readonly></td>
                  </tr>
                </thead>
              </table>
            </div>
          </div>
          <input type="number" id="stock_real" value="0" readonly style="display:none;" class="alert-warning" />
          <input type="number" id="id_lote" value="0" readonly style="display:none ; " class="alert-danger" />
          <input type="hidden" id="precio_unitario" value="0" readonly />
          <input type="number" id="id_referencia" value="0" readonly style="display: none;" class="alert-success" />
          <input type="hidden" id="costo_promedio" value="0" class="alert-secondary" readonly />
          <input type="number" id="id_detalle" style="display:none;" value="0" readonly class="alert-primary">
          <input type="hidden" id="tmp_precio" readonly />
          <input type="hidden" id="tipo_referencia" value="" />
          <div id="dvReferencias" class="mt-3 table-responsive text-nowrap">
            <table class="table table-sm " style="width: 100%" width="100%" id="carrito">
              <thead class="table-primary">
                <tr>
                  <th>Código</th>
                  <th>Descripción</th>
                  <th>Precio b</th>
                  <th class="text-center">Dcto</th>
                  <th class="text-center">Iva</th>
                  <th>Precio n</th>
                  <th class="text-center">Cant</th>
                  <th class="text-center">Cant desp.</th>
                  <th class="text-center">Und</th>
                  <th class="text-center">Conv</th>
                  <th>F.Vto</th>
                  <th>Lote</th>
                  <th>Total</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
              </tbody>
            </table>
          </div>
        </div>
        <div class="tab-pane fade " id="navs-temporal" role="tabpanel">
          <div class="card mb-4">
            <div class="card-body">
              <div class="row g-3 align-items-end">
                <div class="col-md-2">
                  <label class="form-label fw-bold mb-1">
                    Fecha inicial
                  </label>
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
                  <label class="form-label fw-bold mb-1">
                    Fecha final
                  </label>
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
                  <label class="form-label fw-bold mb-1">
                    Cliente
                  </label>
                  <div class="input-group input-group-sm">
                    <span class="input-group-text">
                      <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input
                      id="txtBusquedaTerceroTmp"
                      type="text"
                      class="form-control"
                      placeholder="Buscar por nombre, identificación o NIT">
                  </div>
                  <input type="hidden" id="nit_tmp" readonly>
                </div>
                <div class="col-md-2 d-grid">
                  <button
                    class="btn btn-primary btn-sm"
                    id="buscar-temporal">
                    <i class="fa-solid fa-magnifying-glass me-1"></i>
                    Consultar
                  </button>
                </div>
              </div>
              <div class="row mt-2">
                <div class="col">
                  <div id="temporales" class="table-responsive text-nowrap"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="tab-pane fade " id="navs-fe" role="tabpanel">

          <div class="row g-3 align-items-end">

            <div class="col-md-4">
              <label class="form-label mb-1 fw-bold">Fecha inicial</label>
              <div class="input-group input-group-sm">
                <span class="input-group-text">
                  <i class="fa-regular fa-calendar-check"></i>
                </span>
                <input
                  type="date"
                  class="form-control"
                  id="fe_fecha_inicial">
              </div>
            </div>

            <div class="col-md-4">
              <label class="form-label mb-1 fw-bold">Fecha final</label>
              <div class="input-group input-group-sm">
                <span class="input-group-text">
                  <i class="fa-regular fa-calendar-check"></i>
                </span>
                <input
                  type="date"
                  class="form-control"
                  id="fe_fecha_final">
              </div>
            </div>

            <div class="col-md-4">
              <button
                class="btn btn-sm btn-primary w-100"
                id="consultar-fe">
                <i class="fa-solid fa-magnifying-glass me-1"></i>
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
<div class="modal fade" id="modalLotesPedidos" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-md " role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="">Lotes</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="">
        <div id="resultLotesPedidos"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal"> Cerrar </button>
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
        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal"> Cerrar </button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="modalDetallePedido" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="">Detalle de pedido </h5>
        <button
          type="button"
          class="btn-close"
          data-bs-dismiss="modal"
          aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="result-detalle-pedido" class="table-responsive text-nowrap"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal"> Cerrar </button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="modalPedidos" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="">Pedidos pendientes </h5>
        <button
          type="button"
          class="btn-close"
          data-bs-dismiss="modal"
          aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3 align-items-end">

          <!-- Fecha inicial -->
          <div class="col-md-3">
            <label class="form-label mb-1" for="fecha_inicial_pedido">
              Fecha inicial
            </label>
            <input
              type="date"
              class="form-control form-control-sm"
              id="fecha_inicial_pedido">
          </div>

          <!-- Fecha final -->
          <div class="col-md-3">
            <label class="form-label mb-1" for="fecha_final_pedido">
              Fecha final
            </label>
            <input
              type="date"
              class="form-control form-control-sm"
              id="fecha_final_pedido">

          </div>

          <!-- Cliente -->
          <div class="col-md-4">
            <label class="form-label mb-1" for="cliente_pedido">
              Cliente
            </label>
            <input
              type="text"
              class="form-control form-control-sm"
              id="cliente_pedido"
              placeholder="Buscar por cliente...">

            <input
              type="hidden"
              id="nit_tercero_pedido"
              value="0">
          </div>

          <!-- Botón -->
          <div class="col-md-2">
            <button
              type="button"
              class="btn btn-sm btn-primary w-100"
              id="listar-pedidos">
              <i class="fa-solid fa-magnifying-glass me-1"></i>
              Buscar pedidos
            </button>
          </div>

        </div>

        <!-- Resultado -->
        <div
          id="result-pedidos"
          class="mt-3 table-responsive">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal"> Cerrar </button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="detalleCartera" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="">Detalle de cartera vencida</h5>
        <button
          type="button"
          class="btn-close"
          data-bs-dismiss="modal"
          aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="table-response-md" id="tabla-detalle-carter"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal"> Cerrar </button>
      </div>
    </div>
  </div>
</div>
<div id="modal-container"></div>
</div>


<?php

$scriptsPagina = [
  '../controllers/Facturacion.js?' . rand()
];

include '../dependencies/footer.php';

?>