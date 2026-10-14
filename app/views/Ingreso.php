<?php
session_start();

$titulo = 'Ingreso de mercancía OC';
include '../dependencies/header.php';
?>
<style>
  .swal2-container {
    z-index: 10000 !important;
  }

  .swal2-overlay {
    z-index: 10000 !important;
  }

  .swal2-modal {
    z-index: 10000 !important;
  }

  .treeview .list-group-item {
    cursor: pointer
  }

  .treeview span.indent {
    margin-left: 10px;
    margin-right: 10px
  }

  .treeview span.icon {
    width: 12px;
    margin-right: 5px
  }

  .treeview .node-disabled {
    color: silver;
    cursor: not-allowed
  }

  .node-treeview1:not(.node-disabled):hover {
    background-color: #F5F5F5;
  }

  .label-totales {
    font-size: 0.72rem;
    font-weight: 600;
    width: 78px;
  }
</style>
<input type="hidden" id="id_bodega_session" value="<?php if (!empty($_SESSION["Ses_id_bodega"])) {
                                                      echo $_SESSION["Ses_id_bodega"];
                                                    } else {
                                                      echo  '0';
                                                    } ?>" />
<input type="hidden" id="id_usuario" value="<?php if (!empty($_SESSION["Ses_id"])) {
                                              echo $_SESSION["Ses_id"];
                                            } else {
                                              echo  '0';
                                            } ?>" />
<input type="hidden" id="id_rol" value="<?php if (!empty($_SESSION["Ses_roles_id"])) {
                                          echo $_SESSION["Ses_roles_id"];
                                        } else {
                                          echo  '0';
                                        } ?>" />

<div class="card" style="height: auto;">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Inventarios /</span> Compras e ingreso de mercancía con OC</h5>
    <div class="nav-align-top ">
      <ul class="nav nav-tabs" role="tablist" id="tabs">
        <li class="nav-item">
          <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-creacion" aria-selected="true"><i class="fa-solid fa-user-tie"></i>&nbsp;Tercero</button>
        </li>
        <li class="nav-item">
          <button type="button" class="nav-link disabled" role="tab" data-bs-toggle="tab" data-bs-target="#navs-productos" aria-selected="false"> <i class="fa-regular fa-pen-to-square"></i> &nbsp;Productos </button>
        </li>
        <li class="nav-item">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-temporal" aria-controls="navs-temporal" aria-selected="false"> <i class="fa-regular fa-clock"></i> &nbsp;Mis ordenes </button>
        </li>
        <li class="nav-item" style="flex:1;"></li>
        <li class="nav-item">
          <button type="button" class="btn btn-sm btn-outline-secondary" id="btnLimpiar"><i class="fa-regular fa-file"></i> Nuevo</button>
          <button type="button" class="btn btn-sm btn-outline-primary" id="btnGuardar"><i class="fa-regular fa-floppy-disk"></i> Guardar</button>
        </li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane fade show active" id="navs-creacion" role="tabpanel"><!--tab1 inicio-->
          <div class="row">
            <div class="col-md-8">
              <!-- COLUMNA IZQUIERDA: TERCERO -->
              <div class="card">
                <h5 class="card-header text-primary form-label mb-3">Tercero</h5>
                <div class="card-body">
                  <div class="mb-3">
                    <label for="nit" class="form-label">NIT</label>
                    <input type="text" class="form-control form-control-sm" name="nit" id="nit" maxlength="20" placeholder="NIT" autocomplete="off" onKeyPress="return vnumeros(event)" readonly>
                  </div>
                  <div class="mb-3">
                    <label for="tratamiento" class="form-label">TRATAMIENTO</label>
                    <input type="text" class="form-control form-control-sm text-uppercase" name="tratamiento" id="tratamiento" autocomplete="off" readonly placeholder="Tratamiento">
                  </div>
                  <div class="mb-3">
                    <label for="razon_comercial" class="form-label">RAZÓN COMERCIAL</label>
                    <input type="text" class="form-control form-control-sm text-uppercase" name="razon_comercial" id="razon_comercial" autocomplete="off" placeholder="Razón social/comercial" readonly>
                  </div>
                  <div class="mb-3">
                    <label for="direcciones" class="form-label">DIRECCIÓN</label>
                    <select class="form-select form-select-sm" id="direcciones">
                    </select>
                  </div>
                  <!-- Campos ocultos para mantener compatibilidad -->
                  <input type="hidden" id="id_tercero" name="id_tercero" placeholder="Código" autocomplete="off" readonly>
                  <input type="hidden" name="email_fe" id="email_fe" autocomplete="off" placeholder="Email" readonly>
                  <label for="notas" class="form-label">NOTAS</label>
                  <textarea class="form-control" id="notas" name="notas" rows="3"></textarea>
                </div>
              </div>
            </div>

            <!-- COLUMNA DERECHA: CONDICIONES -->
            <div class="col-md-4">
              <div class="card">
                <h5 class="card-header text-primary form-label mb-3">Condiciones</h5>
                <div class="card-body">
                  <label for="referencia" class="form-label">DOCUMENTO</label>
                  <input type="text" class="form-control form-control-sm text-uppercase" name="referencia" id="referencia" autocomplete="off" placeholder="Documento/referencia">
                  <label for="fecha" class="form-label">FECHA DE DOCUMENTO</label>
                  <input type="date" class="form-control form-control-sm" id="fecha" name="fecha" value="<?php echo date("Y-m-d"); ?>">
                  <label for="bodegas" class="form-label">BODEGA</label>
                  <select class="form-select form-select-sm" id="bodegas" name="id_bodega">
                  </select>
                  <label for="tipo" class="form-label">TIPO</label>
                  <select class="form-select form-select-sm" id="tipo" name="id_tipo_documento">
                  </select>
                  <input type="hidden" id="id_perfil_tributario" value="0" readonly>
                  <label for="perfil_tributario_compra" class="form-label">PERFIL TRIBUTARIO</label>
                  <select class="form-select form-select-sm" id="perfil_tributario_compra" disabled>
                    <option value="">NO APLICA</option>
                  </select>
                  <label for="concepto_retencion" class="form-label">CONCEPTO DE RETENCIÓN</label>
                  <select class="form-select form-select-sm bloqueado" id="concepto_retencion" name="id_concepto_retencion">
                    <option value="">NO APLICA</option>
                  </select>
                  <label for="condicion" class="form-label">CONDICIÓN</label>
                  <select class="form-select form-select-sm" id="condicion" name="id_condicion">
                  </select>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!--tab1 fin-->
        <div class="tab-pane fade" id="navs-productos" role="tabpanel">
          <div class=" mt-4">
            <input type="hidden" class="form-control" id="id_transaccion" value="0">

            <h5 class="card-header text-primary">Búsqueda de referencias</h5>
            <div class="card-body">
              <div class="row mb-2">
                <div class="col-md-3">
                  <div class="input-group input-group-sm ">
                    <span class="input-group-text label-totales">Numero</span>
                    <input name="numero" id="id_pedido" type="number" class="form-control form-control-sm" readonly />
                  </div>
                  <div class="input-group input-group-sm ">
                    <span class="input-group-text label-totales">Valor oc</span>
                    <input name="valor_oc" id="n_total" type="text" class="form-control form-control-sm" readonly />
                  </div>
                </div>
                <!-- col-md-3 -->
                <div class="col-md-9">
                  <div class="row">
                    <div class="col-md-3">
                      <div class="input-group input-group-sm input-group-merge">
                        <span class="input-group-text label-totales">Valor Bruto</span>
                        <input type="text" class="form-control form-control-sm text-end" name="t_bruto" id="in-total-bruto" value="0" readonly>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="input-group input-group-sm input-group-merge">
                        <span class="input-group-text label-totales">Dcto</span>
                        <input type="text" class="form-control form-control-sm text-end" name="t_dcto" id="in-total-dcto" value="0" readonly>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="input-group input-group-sm input-group-merge">
                        <span class="input-group-text label-totales">Iva</span>
                        <input type="text" class="form-control form-control-sm text-end" name="t_iva" id="in-total-iva" value="0" readonly>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="input-group input-group-sm input-group-merge "> <span class="input-group-text label-totales">Total</span>
                        <input type="text" class="form-control form-control-sm text-end fw-bold" name="in-total" id="in-total" value="0" readonly>
                      </div>
                    </div>
                  </div>
                  <!-- row retenciones -->
                  <div class="row mt-1">
                    <div class="col-md-3">
                      <div class="input-group input-group-sm input-group-merge">
                        <span class="input-group-text label-totales">RteFte</span>
                        <input type="text" class="form-control form-control-sm text-end fw-bold" id="total-ret-fte" value="$0" readonly>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="input-group input-group-sm input-group-merge">
                        <span class="input-group-text label-totales">ReteICA</span>
                        <input type="text" class="form-control form-control-sm text-end fw-bold" id="total-ret-ica" value="$0" readonly>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="input-group input-group-sm input-group-merge">
                        <span class="input-group-text label-totales">ReteIVA</span>
                        <input type="text" class="form-control form-control-sm text-end fw-bold" id="total-ret-iva" value="$0" readonly>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="input-group input-group-sm input-group-merge">
                        <span class="input-group-text label-totales">Tot. Ret.</span>
                        <input type="text" class="form-control form-control-sm text-end fw-bold" id="total-ret-total" value="$0" readonly>
                      </div>
                    </div>
                  </div>
                  <!-- row neto a pagar -->
                  <div class="row mt-1 justify-content-end">
                    <div class="col-md-3">
                      <div class="input-group input-group-sm input-group-merge">
                        <span class="input-group-text text-primary label-totales">Neto Pagar</span>
                        <input type="text" class="form-control form-control-sm text-end text-primary fw-bold" id="total-neto-pagar" value="$0" readonly>
                      </div>
                    </div>
                  </div>
                  <!-- row -->
                </div>
                <!-- col-md-9 -->
              </div>
              <!-- Búsqueda de Referencias -->
              <div class="input-group input-group-sm input-group-merge mb-3">
                <span class="input-group-text"><i class="bx bx-search"></i></span>
                <input type="text"
                  class="form-control"
                  placeholder="Búsqueda de referencia..."
                  aria-label="Búsqueda de referencia..."
                  aria-describedby="basic-addon-search" id="buscar_ref_oc">
                <span class="input-group-text btn btn-sm btn-primary"
                  id="btnBusquedaAvanzada"><i class="fa-solid fa-sliders"></i></span>
              </div>
              <div id="dvReferencias" class="mt-3 table-responsive text-nowrap" style="height: 90%; ">
                <table class="table table-sm table-hover table-striped" id="items_">
                  <thead class="table-primary">
                    <tr class="text-primary">
                      <th>Referencia</th>
                      <th>Costo</th>
                      <th>IVA(%)</th>
                      <th>DTO(%)</th>
                      <th>Costo_Neto</th>
                      <th>Cantidad</th>
                      <th>Costo_Total</th>
                      <th>Confirmadas</th>
                      <th>Pendientes</th>
                      <th>Ingreso</th>
                      <th>Opciones</th>
                    </tr>
                  </thead>
                  <tbody>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <div class="tab-pane fade" id="navs-temporal" role="tabpanel">
          <div class="row">
            <div class="col-4">
              <div class="mb-3">
                <label class="form-label" for="">Fecha inicial</label>
                <div class="input-group input-group-merge"> <span class="input-group-text"><i class="fa-regular fa-calendar-check"></i></span>
                  <input type="date" class="form-control form-control-sm" id="fhIni" aria-describedby="fecha_inicial" />
                </div>
              </div>
            </div>
            <div class="col-4">
              <div class="mb-3">
                <label class="form-label" for="">Fecha final</label>
                <div class="input-group input-group-merge"> <span class="input-group-text "><i class="fa-regular fa-calendar-check"></i></span>
                  <input type="date" class="form-control form-control-sm" id="fhFin" aria-describedby="fecha_final" />
                </div>
              </div>
            </div>
            <div class="col-4">
              <div class="mb-3">
                <label class="form-label" for="">&nbsp;</label><br>
                <button class="btn btn-sm btn-outline-primary w-100" id="btnBuscarOrden"><i class="fa-solid fa-magnifying-glass"></i>&nbsp;Consultar</button>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col">
            </div>
          </div>
          <div class="" id="result-ordenes"></div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="flujo" aria-hidden="true" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl " role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="">Flujo</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="">
          <div id="treeview1" class="treeview">
            <ul class="list-group">
              <li class="list-group-item node-treeview1" data-nodeid="0" style="color:undefined;background-color:undefined;"><i class="fa-solid fa-circle-dot"></i>&nbsp;<span id="fl-cabecera"></span></li>
              <div id="fl-cuerpo"></div>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!--Modal posiciones tmp-->
  <div class="modal fade" id="modal-posiciones" aria-hidden="true" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="">Posiciones agregadas</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body table-responsive text-nowrap" id="modal-posiciones-body"> </div>
      </div>
    </div>
  </div>

  <!-- Modal de Productos Pendientes -->
  <div class="modal fade" id="modal-productos-pendientes" aria-hidden="true" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header bg-warning-subtle border-bottom">
          <div>
            <h5 class="modal-title mb-1"><i class="fa-solid fa-triangle-exclamation text-warning"></i> Productos con Pendientes</h5>
            <p class="text-muted mb-0" style="font-size: 0.85rem;">Los siguientes productos aún tienen cantidades pendientes por ingresar</p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="modal-pendientes-content" class="table-responsive">
            <!-- Se llenará dinámicamente con los datos -->
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fa-solid fa-arrow-left"></i> Volver
          </button>
          <button type="button" class="btn btn-primary" id="btn-confirmar-pendientes">
            <i class="fa-solid fa-check"></i> Continuar con Guardado
          </button>
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
                  <input type="text" class="form-control" id="in-descripcion_corta" placeholder="" readonly>
                  <label for="in-descripcion_corta">Descripción</label>
                  <!--<div id="floatingInputHelp" class="form-text">We'll  never share your details with anyone else.</div>-->
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-floating">
                  <input type="text" class="form-control" id="in-descripcion_larga" placeholder="" readonly>
                  <label for="in-descripcion_larga">Descripción larga</label>
                  <!--<div id="floatingInputHelp" class="form-text">We'll never share your details with anyone else.</div>-->
                </div>
              </div>
            </div>
            <div class="row">
              <input type="hidden" id="in-maneja-lote" readonly disabled>
              <div class="col-md-3">
                <label for="codigo" class="form-label">Codigo</label>
                <input type="hidden" id="id_referencia" name="id_referencia" value="0" readonly>
                <input type="hidden" id="id_pedido_ref" value="0" readonly>
                <input type="text" class="form-control form-control-sm" id="in-codigo" required readonly>
              </div>
              <div class="col-md-3">
                <label for="costo" class="form-label">Costo</label>
                <input type="text" class="form-control form-control-sm format-num calculo-costo" maxlength="21" min="1" tabindex="1" required name="costo_unitario" id="in-costo">
                <div class="invalid-feedback"> El costo debe ser mayor a 0 </div>
              </div>
              <div class="col-md-3">
                <label for="unidad" class="form-label">Unidad</label>
                <select class="form-select form-select-sm calculo-costo" tabindex="2" id="in-unidad" required multiple style="height: 60px;">
                </select>
                <div class="invalid-feedback"> Debe seleccionar un unidad de conversion </div>
              </div>
              <div class="col-md-3">
                <label for="in-pcj_descuento" class="form-label">Descuento</label>
                <input type="text" class="form-control form-control-sm format-num calculo-costo" tabindex="3" id="in-pcj_descuento" name="pcj_descuento" value="0" />
              </div>
            </div>
            <div class="row">
              <div class="col-md-3">
                <label for="iva" class="form-label">IVA</label>
                <input type="text" class="form-control form-control-sm" tabindex="4" id="in-iva" name="pcj_iva" readonly />
              </div>
              <div class="col-md-3">
                <label for="costo_neto" class="form-label">Impoconsumo</label>
                <input type="text" class="form-control form-control-sm " tabindex="5" id="in-impoconsumo" name="impoconsumo" readonly>
              </div>
              <div class="col-md-3">
                <label for="in-ibua" class="form-label">Ibua</label>
                <input type="text" class="form-control form-control-sm format-num" tabindex="6" id="in-ibua" name="ibua" readonly />
              </div>
              <div class="col-md-3">
                <label for="costo_neto" class="form-label">Icui</label>
                <input type="text" class="form-control form-control-sm" tabindex="7" id="in-icui" name="icui" readonly />
              </div>
            </div>
            <div class="row mt-2">
              <div class="col-md-3 clase-col-md-3-costo-add-item">
                <label for="in-costo_neto" class="form-label">Costo Neto</label>
                <input type="text" class="form-control form-control-sm format-num" id="in-costo_neto" readonly>
              </div>
              <div class="col-md-3 maneja-fecha-lotes">
                <label for="in-lote" class="form-label">lote</label>
                <input type="hidden" readonly id="tmp_" />
                <input id="id_lote" name="id_lote" type="hidden" value="0" readonly>
                <input type="text" class="form-control form-control-sm maneja-fecha-lotes text-uppercase" tabindex="8" name="lote" required id="in-lote" />
                <div class="invalid-feedback"> Debe digitar el lote </div>
              </div>
              <div class="col-md-3 maneja-fecha-lotes">
                <label for="in-vmcto" class="form-label">vencimiento</label>
                <input type="date" class="form-control form-control-sm" tabindex="9" id="in-vmcto" name="vmcto" required />
                <div class="invalid-feedback"> Debe digitar la fecha de vencimiento </div>
              </div>
              <div class="col-md-3 clase-col-md-3-costo-add-item">
                <label for="in-cantidad" class="form-label">cantidad</label>
                <input type="number" class="form-control form-control-sm calculo-costo" tabindex="10" min="1" name="cantidad" id="in-cantidad" required onKeyPress="return vnumeros(event)">
                <div class="invalid-feedback"> La cantidad debe ser mayor a 0 </div>
              </div>
              <div class="col-md-3">
                <label for="" class="form-label">pendientes</label>
                <input type="text" class="form-control form-control-sm" id="in-pendientes" readonly>
              </div>
            </div>
            <div class="row mt-2 p-2">
              <div class="col-3">
                <label for="costo_total" class="form-label">Costo Total</label>
                <input type="text" class="form-control form-control-sm format-num table-success" id="costo_total" readonly>
              </div>
              <div class="col-3">
                <label for="promedio" class="form-label">Costo promedio</label>
                <input type="text" class="form-control form-control-sm format-num" id="promedio" readonly>
              </div>
              <div class="col-3">
                <label for="ultimo_costo" class="form-label">Ultimo costo</label>
                <input type="text" class="form-control form-control-sm format-num" id="ultimo_costo" readonly>
              </div>
              <div class="col-3">
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
            <div class="row mt-3 alert-primary shadow-sm p-2" style="display:none;" id="div-calculo-precio">
              <div class="col-4">
                <label>Tipo de precio</label>
                <select class="form-control form-control-sm" id="tipo_precio">
                  <option value="PF">PF - Precio Fijo</option>
                  <option value="CP">CP - Costo Promedio</option>
                  <option value="CU">CU - Costo Ultimo</option>
                </select>
              </div>
              <div class="col-4">
                <label>Valor</label>
                <input type="text" class="form-control form-control-sm" id="valor_tipo_precio" value="0" />
              </div>
              <div class="col-4">
                <label>Precio venta</label>
                <input type="text" class="form-control form-control-sm" id="precio_venta" readonly value="0" />
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-sm btn-outline-primary" tabindex="11" id="btnAddReferencia"> Agregar </button>
            <button type="reset" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal"> Cerrar </button>
          </div>
        </div>
      </div>
    </form>
  </div>

  <div id="modal-container"></div>

  <?php

  $scriptsPagina = [
    '../controllers/Ingreso.js?' . rand()
  ];

  include '../dependencies/footer.php';

  ?>