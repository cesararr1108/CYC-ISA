<?php
session_start();

$titulo = 'POS Móvil';
include '../dependencies/header.php';
?>
<style>
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

  .label-totales {
    font-size: 0.72rem;
    font-weight: 600;
    width: 78px;
  }

  .input-group.input-group-sm .input-group-text {
    font-size: 0.72rem;
    font-weight: 600;
  }

  input[type=number]::-webkit-inner-spin-button,
  input[type=number]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
  }

  .pagos-scroll {
    max-height: 220px;
    overflow-y: auto;
  }

  #table-pagos thead th {
    position: sticky;
    top: 0;
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

  /* ===== Versión móvil ===== */
  html, body { overflow-x: hidden; }
  .layout-page .content-wrapper .container-xxl,
  .container-xxl { padding-left: .5rem; padding-right: .5rem; }
  .form-control, .form-select, .btn, .input-group-text { font-size: 16px; } /* evita zoom en iOS */
  .form-control, .form-select { min-height: 42px; }
  .btn { min-height: 40px; }
  .card-body { padding: .75rem; }

  /* pestañas con scroll horizontal */
  #tabs { flex-wrap: nowrap; overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch; scrollbar-width: none; }
  #tabs::-webkit-scrollbar { display: none; }
  #tabs .nav-link { white-space: nowrap; padding: .6rem .8rem; font-size: .9rem; }

  /* barra de acciones fija abajo */
  .pos-actions {
    position: fixed; left: 0; right: 0; bottom: 0; z-index: 1030;
    display: flex; gap: .4rem; padding: .5rem;
    background: #fff; box-shadow: 0 -2px 8px rgba(0,0,0,.15);
  }
  .pos-actions .btn { flex: 1 1 0; padding: .4rem .2rem; line-height: 1.1; display: flex; flex-direction: column; align-items: center; justify-content: center; font-size: .72rem; }
  .pos-actions .btn i { font-size: 1.1rem; margin-bottom: 2px; }
  .pos-actions .btn span { white-space: nowrap; }
  .pos-actions #guardar { flex: 1.4 1 0; }
  .content-wrapper, #modal-container { padding-bottom: 76px; }
  body { padding-bottom: 76px; }

  .label-totales { width: 74px; font-size: .75rem; }

  /* resumen de totales */
  .pos-totales .input-group-text { min-width: 78px; }

  /* grilla del producto seleccionado (tarjeta) */
  .pos-producto { display: grid; grid-template-columns: repeat(3, 1fr); gap: .5rem; align-items: end; }
  .pos-producto .campo label { font-size: .7rem; font-weight: 600; color: #566a7f; margin-bottom: 2px; display: block; }
  .pos-producto .campo.span2 { grid-column: span 2; }
  .pos-producto .campo.span3 { grid-column: span 3; }
  .pos-producto .form-control, .pos-producto .form-select { padding: .3rem .4rem; }
  .pos-producto select[multiple] { height: 84px; padding: 0; }
  #cantidad_grid { font-weight: 700; text-align: center; }

  /* carrito como tarjetas */
  #dvReferencias { overflow: visible; }
  #carrito, #carrito tbody { display: block; width: 100% !important; }
  #carrito thead { display: none; }
  #carrito tbody tr {
    display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 2px .5rem;
    background: #fff; border: 1px solid #d9dee3; border-left: 4px solid #696cff;
    border-radius: 8px; padding: .5rem .6rem; margin-bottom: .6rem;
    box-shadow: 0 1px 3px rgba(0,0,0,.08); position: relative;
  }
  #carrito tbody tr td {
    display: block; border: 0; padding: 0 !important; font-size: .82rem; text-align: left !important; width: auto !important;
  }
  #carrito tbody tr td[data-label]::before {
    content: attr(data-label); display: block; font-size: .65rem; font-weight: 600; text-transform: uppercase; color: #8592a3;
  }
  #carrito tbody tr td.c-desc { grid-column: 1 / -1; font-weight: 600; font-size: .9rem; padding-right: 0; }
  #carrito tbody tr td.c-desc::before { display: none; }
  #carrito tbody tr td.c-total { grid-column: 1 / -1; font-weight: 700; font-size: 1rem; color: #696cff; text-align: right !important; }
  #carrito tbody tr td.c-total::before { display: inline; margin-right: .5rem; }
  #carrito tbody tr td.c-acc { grid-column: span 1; margin-top: .3rem; }
  #carrito tbody tr td.c-acc .btn { width: 100%; }
  #carrito tbody tr td.d-none { display: none !important; }

  /* listados (temporal / FE) como tablas desplazables */
  .table-responsive { -webkit-overflow-scrolling: touch; }

  /* modales a pantalla completa */
  .modal-dialog { margin: 0; max-width: 100%; min-height: 100%; }
  .modal-dialog.modal-dialog-centered { min-height: 100%; }
  .modal-content { min-height: 100vh; border-radius: 0; }
  #modalConfirmarGuardar .modal-dialog { margin: 1rem; min-height: auto; }
  #modalConfirmarGuardar .modal-content { min-height: auto; border-radius: .5rem; }
  .modal-footer { position: sticky; bottom: 0; background: #fff; flex-wrap: wrap; }
  .modal-footer .btn { flex: 1 1 auto; }
  #table-pagos { font-size: .8rem; }
</style>
<input type="hidden" value="<?php echo $_SESSION["Ses_nombres"] . ' ' . $_SESSION["Ses_apellidos"];  ?>" readonly id="nombres_usuario" />
<input type="hidden" value="" id="tmp" readonly />
<input type="hidden" value="" id='vendedor' readonly />
<input type="hidden" id="id_bodega_session" value="<?php if (!empty($_SESSION["Ses_id_bodega"])) {
                                                      echo $_SESSION["Ses_id_bodega"];
                                                    } else {
                                                      echo  '0';
                                                    } ?>" />
<input type="hidden" id="tipo_referencia" value="" />

<div class="card" style="height: auto;">
  <div class="card-body px-2">
    <h6 class="fw-bold py-0 mb-2"><span class="text-muted fw-light">POS / </span>Ventas</h6>
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
      </ul>
      <!-- Barra de acciones (fija abajo) -->
      <div class="pos-actions">
        <button type="button" class="btn btn-outline-secondary" id="domicilio"> <i class="fa-solid fa-motorcycle"></i><span>Domicilio</span></button>
        <button type="button" class="btn btn-outline-secondary" id="nuevo"> <i class="fa-regular fa-file"></i><span>Nuevo</span></button>
        <button type="button" class="btn btn-outline-secondary" id="btn-crear-cliente"><i class="fa-regular fa-address-book"></i><span>Cliente</span></button>
        <button type="button" class="btn btn-primary" id="guardar"> <i class="fa-regular fa-floppy-disk"></i><span>Guardar</span></button>
      </div>
      <div class="tab-content">
        <div class="tab-pane fade show active m-0 p-0" id="navs-tercero" role="tabpanel">
          <form id="FormEncabezado" class="needs-validation  form-basico" novalidate>
            <div class="row">
              <div class="col-12">
                <div class="card p-0 mb-3">
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
              <div class="col-12">
                <div class="card mb-3">
                  <h5 class="card-header text-primary form-label fw-bold">Condiciones</h5>
                  <div class="card-body">
                    <label for="condicion" class="form-label">Tipo</label>
                    <select class="form-select form-select-sm" id="tipos" name="id_tipo_documentos">
                    </select>
                    <label for="condicion" class="form-label">Condicion de pago</label>
                    <select class="form-select form-select-sm" id="id_condicion" name="id_condicion" disabled>
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
          <div class="pos-totales mb-2">
            <div class="row g-1">
              <div class="col-12">
                <div class="input-group input-group-sm">
                  <span class="input-group-text label-totales">Numero</span>
                  <input name="numero" id="numero" type="number" class="form-control form-control-sm" aria-describedby="basic-addon11" readonly />
                </div>
              </div>
              <div class="col-6">
                <div class="input-group input-group-sm input-group-merge">
                  <span class="input-group-text label-totales">Bruto</span>
                  <input type="text" class="form-control form-control-sm text-end" name="t_bruto" id="t_bruto" value="0" readonly>
                </div>
              </div>
              <div class="col-6">
                <div class="input-group input-group-sm input-group-merge">
                  <span class="input-group-text label-totales">Dcto</span>
                  <input type="text" class="form-control form-control-sm text-end" name="t_dcto" id="t_dcto" value="0" readonly>
                </div>
              </div>
              <div class="col-6">
                <div class="input-group input-group-sm input-group-merge">
                  <span class="input-group-text label-totales">Iva</span>
                  <input type="text" class="form-control form-control-sm text-end" name="t_iva" id="t_iva" value="0" readonly>
                </div>
              </div>
              <div class="col-6">
                <div class="input-group input-group-sm input-group-merge "> <span class="input-group-text label-totales">Total</span>
                  <input type="text" class="form-control form-control-sm text-end fw-bold" name="t_total" id="t_total" value="0" readonly>
                </div>
              </div>
              <div class="col-6">
                <div class="input-group input-group-sm input-group-merge">
                  <span class="input-group-text label-totales">RteFte</span>
                  <input type="text" class="form-control form-control-sm text-end fw-bold" id="t_ret_fte" value="$0" readonly>
                </div>
              </div>
              <div class="col-6">
                <div class="input-group input-group-sm input-group-merge">
                  <span class="input-group-text label-totales">ReteICA</span>
                  <input type="text" class="form-control form-control-sm text-end fw-bold" id="t_ret_ica" value="$0" readonly>
                </div>
              </div>
              <div class="col-6">
                <div class="input-group input-group-sm input-group-merge">
                  <span class="input-group-text label-totales">ReteIVA</span>
                  <input type="text" class="form-control form-control-sm text-end fw-bold" id="t_ret_iva" value="$0" readonly>
                </div>
              </div>
              <div class="col-6">
                <div class="input-group input-group-sm input-group-merge">
                  <span class="input-group-text label-totales">Tot.Ret.</span>
                  <input type="text" class="form-control form-control-sm text-end fw-bold" id="t_ret_total" value="$0" readonly>
                </div>
              </div>
              <div class="col-12">
                <div class="input-group input-group-sm input-group-merge">
                  <span class="input-group-text text-primary label-totales">Neto</span>
                  <input type="text" class="form-control form-control-sm text-end text-primary fw-bold" id="t_neto_pagar" value="$0" readonly>
                </div>
              </div>
            </div>
          </div>
          <input type="number" style="display: none;" id="id_transaccion" readonly value="0">
          <div class="row">
            <div class="col-md-9"> </div>
            <div class="col-md-3"> </div>
          </div>
          <div class="row mt-2">
            <div class="col-md-8">
              <div class="form-label text-primary fw-bold"> <span><i class="fa fa-search"></i>&nbsp;Búsqueda de productos</span>
                <div class="input-group input-group-merge mt-2"> <span class="input-group-text"><i class="bx bx-search"></i></span>
                  <input name="" id="filtro" type="text" class="form-control form-control-sm filtros-busqueda-productos" placeholder="Código, descripción, descripción larga" aria-label="Buscar..." autocomplete="off" />
                  <span class="input-group-text btn btn-sm btn-primary"
                    id="btnBusquedaAvanzada"><i class="fa-solid fa-sliders"></i></span>
                </div>
                <!-- <div class="input-group input-group-merge">  -->
              </div>
              <!-- <div class=" mb-2">  -->
            </div>
            <!-- col-md-8 -->
            <div class="col-md-4">
              <div class="form-label text-primary fw-bold"> <span><i class="fa-solid fa-boxes-stacked"></i>&nbsp;Stock</span>
                <div class="input-group input-group-sm input-group-merge ">
                  <input name="stock_grid" id="stock_grid" type="text" class="form-control form-control-sm " readonly />
                  <span class="input-group-text">
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-primary position-relative"
                      id="btn_unidades_comprometidas"
                      title="Unidades comprometidas">
                      <i class="fa fa-clock"></i>
                      <span
                        id="unidades_comprometidas"
                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <span class="visually-hidden">0</span>
                      </span>
                    </button>

                    <button
                      type="button"
                      class="btn btn-sm btn-outline-primary ms-1"
                      id="btn_stock_otras_bodegas"
                      title="Stock en otras bodegas">
                      <i class="fa fa-warehouse"></i>
                    </button>

                    <!-- Indicador de comisión -->
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-success ms-1 d-none"
                      id="btn_comision_producto"
                      title="Producto con comisión">
                      <i class="fa fa-percent"></i>
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
          <div class="row mt-2 g-3">
            <div class="col-md-8">
              <input
                type="text"
                class="form-control form-control-sm data-grid"
                id="descripcion_grid"
                disabled />
            </div>
            <div class="col-md-4">
              <input
                type="text"
                class="form-control form-control-sm data-grid"
                id="laboratorio_grid"
                disabled />
            </div>
          </div>

          <div class="row mt-1">
            <div class="col-12 div-table-data-referencia"> </div>
          </div>
          <input type="number" id="stock_real" value="0" readonly style="display:none;" class="alert-warning" />
          <input type="number" id="id_lote" value="0" readonly style="display:none;" class="alert-danger" />
          <input type="text" id="precio_unitario" style="display:none;" value="0" readonly />
          <input type="number" id="id_referencia" value="0" readonly style="display: none;" class="alert-success" />
          <input type="hidden" id="costo_promedio" value="0" class="alert-secondary" readonly />
          <input type="number" id="id_detalle" style="display:none;" value="0" readonly class="alert-primary">
          <input type="hidden" id="tmp_precio" readonly />
          <input type="hidden" id="comision_grid" readonly />
          <div id="dvReferencias" class="mt-3 table-responsive-md"> </div>
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
                <input
                  type="text"
                  class="form-control form-control-sm"
                  aria-describedby="stock"
                  id="stock"
                  readonly />
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
<!-- modalNewCliente -->
<div class="modal fade" id="modalNewCliente" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="">Nuevo cliente </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body"> </div>
    </div>
  </div>
</div>
<!-- modalMediosPagos -->
<div class="modal fade" id="modalMediosPagos" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <!-- Encabezado -->
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fa-solid fa-money-check-dollar"></i>
          Formas de pago
        </h5>
        <button class="btn-close" data-bs-dismiss="modal"> </button>
      </div>
      <div class="modal-body">
        <!-- Captura -->
        <div class="card shadow-sm mb-3">
          <div class="card-header py-2">
            <strong>
              Agregar pago
            </strong>
          </div>
          <div class="card-body">
            <small class="text-muted mb-3">
              Seleccione el medio de pago, digite el valor recibido y confirme presionando <b>ENTER</b> o el botón <b>AGREGAR PAGO</b> para registrar el pago.
            </small>
            <div class="row g-3 align-items-end p-2">

              <div class="col-lg-3">
                <label class="form-label fw-bold text-primary small">
                  <i class="fa-solid fa-money-bill-wave me-1"></i>
                  MEDIO DE PAGO
                </label>
                <select class="form-select form-select-sm shadow-sm" id="forma_pago">
                </select>
              </div>

              <div class="col-lg-3">
                <label class="form-label fw-bold text-primary small">
                  <i class="fa-solid fa-dollar-sign me-1"></i>
                  VALOR RECIBIDO
                </label>

                <div class="input-group input-group-sm shadow-sm">
                  <span class="input-group-text">
                    <i class="fa-solid fa-dollar-sign"></i>
                  </span>

                  <input
                    id="valor_recibido"
                    class="form-control text-end fw-bold"
                    autocomplete="off"
                    placeholder="0">
                </div>
              </div>

              <div class="col-lg-2">
                <label class="form-label fw-bold text-primary small">
                  <i class="fa-solid fa-credit-card me-1"></i>
                  TIPO TARJETA
                </label>
                <select class="form-select form-select-sm shadow-sm" id="tipo_tarjeta">
                  <option value="0">No aplica</option>
                  <option value="1">Débito</option>
                  <option value="2">Crédito</option>
                </select>
              </div>
              <div class="col-lg-2">
                <label class="form-label fw-bold text-primary small">
                  <i class="fa-solid fa-receipt me-1"></i>
                  VOUCHER
                </label>
                <input
                  class="form-control form-control-sm shadow-sm"
                  id="voucher"
                  maxlength="20"
                  autocomplete="off"
                  placeholder="Número voucher">
              </div>
              <div class="col-lg-2 d-grid">
                <button class="btn btn-primary btn-sm shadow-sm" id="addPay">
                  <i class="fa-solid fa-plus me-1"></i>
                  Agregar pago
                </button>
              </div>
            </div>
          </div>
        </div>
        <!-- Pagos -->
        <div class="card">
          <div class="card-header py-2">
            <strong>
              Pagos registrados
            </strong>
          </div>
          <div class="card-body">
            <div class="table-responsive pagos-scroll">
              <table class="table table-sm table-hover mb-0" id="table-pagos">
                <thead class="table-primary">
                  <tr>
                    <th>Id</th>
                    <th>Forma</th>
                    <th>Valor</th>
                    <th>Tarjeta</th>
                    <th>Voucher</th>
                    <th>Eliminar</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>
        <!-- Resumen -->
        <div class="row mt-3 py-2">
          <div class="col">
            <div class="card border-primary text-center">
              <small>Factura</small>
              <h5 id="valor_factura" class="text-primary">$0</h5>
            </div>
          </div>
          <div class="col">
            <div class="card border-success text-center">
              <small>Pagado</small>
              <h5 id="valor_pago" class="text-success">$0</h5>
            </div>
          </div>
          <div class="col">
            <div class="card border-warning text-center">
              <small>Faltante</small>
              <h5 id="faltante" class="text-warning">$0</h5>
            </div>
          </div>
          <div class="col">
            <div class="card border-danger text-center">
              <small>Cambio</small>
              <h5 id="cambio" class="text-danger">$0</h5>
            </div>
          </div>
        </div>
      </div>
      <!-- Footer -->
      <div class="modal-footer justify-content-between">
        <small class="text-muted">
          Agregue uno o varios medios de pago hasta completar el valor de la factura.
        </small>
        <button class="btn btn-primary" id="guardar-documento">
          <i class="fa-solid fa-floppy-disk"></i>
          Guardar documento
        </button>
      </div>
    </div>
  </div>
</div>
<!-- Modal de confirmacion de guardado -->
<div class="modal fade" id="modalConfirmarGuardar" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body text-center">
        <i class="fa-solid fa-circle-question fa-3x text-primary mb-3"></i>
        <h5>
          ¿Está seguro de guardar este documento?
        </h5>
      </div>
      <div class="modal-footer justify-content-center">
        <button
          id="btnCancelarConfirm"
          class="btn btn-outline-secondary">
          Cancelar
        </button>
        <button
          id="btnAceptarConfirm"
          class="btn btn-outline-primary">
          Sí, guardar
        </button>
      </div>
    </div>
  </div>
</div>
<!-- Modal container -->
<div id="modal-container"></div>



<?php

$scriptsPagina = [
  '../controllers/pos-movil.js?' . rand()
];

include '../dependencies/footer.php';

?>