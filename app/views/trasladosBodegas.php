<?php
session_start();

$titulo = 'Traslados entre bodegas';
include '../dependencies/header.php';
?>
<style>
  #descripcion_grid,
  #stock_grid,
  #laboratorio_grid:disabled {
    background-color: white;
    border-left: none;
    border-right: none;
    border-top: none;
  }
</style>
<input type="hidden" value="<?php echo $_SESSION["Ses_nombres"] . ' ' . $_SESSION["Ses_apellidos"];  ?>" readonly id="nombres_usuario" />
<input type="hidden" value="" id="tmp" readonly />
<input type="hidden" value="" id='vendedor' readonly />
<input type="hidden" id="bodega_session" style="display: ;" value="<?php echo  $_SESSION["Ses_id_bodega"]; ?>" readonly />

<div class="card " style="height: auto;">
  <div class="card-title d-flex justify-content-end align-items-end p-0 m-0 activate-mod-direct">
    <button type="button" class="btn btn-success btn-sm  position-relative activate-mod-direct" style="display:none;">
      Traslado directo
      <i class="fa-solid fa-check"></i>
    </button>
  </div>
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Inventarios / </span>Traslados entre bodegas</h5>
    <div class="nav-align-top">
      <ul class="nav nav-tabs" role="tablist" id="tabs">
        <li class="nav-item">
          <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-tercero" aria-controls="navs-tercero" aria-selected="true"> <i class="fa-solid fa-user-tie"></i> &nbsp;Tercero </button>
        </li>
        <li class="nav-item">
          <button id="btn-navs-creacion" type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-creacion" aria-controls="navs-creacion" aria-selected="false"><i class="fa-solid fa-cart-plus"></i>&nbsp;Items </button>
        </li>
        <li class="nav-item">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-temporal" aria-controls="navs-temporal" aria-selected="false"> <i class="fa-regular fa-clock"></i> &nbsp;Temporal </button>
        </li>
        <li class="nav-item">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-mis-traslados" aria-controls="navs-mis-traslados" aria-selected="false">
            <i class="fa-solid fa-code-compare"></i> &nbsp; Mis traslados
          </button>
        </li>
        <li class="nav-item" style="flex:1;">

        </li>
        <li class="nav-item border-danger">
          <button type="button" class="btn btn-sm btn-warning position-relative  mod-transito" id="traslados_pendientes">
            Pendientes
            <span class="position-absolute top-0 botton-3 start-100 translate-middle badge rounded-pill bg-danger mb-3" id="n_traslados_pendientes" style="display:none;">
            </span>
          </button>
          <button type="button" class="btn btn-sm btn-outline-success" id="btnAutoTraslados"> <i class="fa-solid fa-wand-magic-sparkles"></i> Traslados automáticos</button>
          <button type="button" class="btn btn-sm btn-outline-secondary" id="nuevo"> <i class="fa-regular fa-file"></i> Nuevo</button>
          <button type="button" class="btn btn-sm btn-primary" id="guardar"> <i class="fa-regular fa-floppy-disk"></i> Guardar</button>
        </li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane fade show active m-0 p-0" id="navs-tercero" role="tabpanel">
          <form id="FormEncabezado" class="needs-validation  form-basico" novalidate>
            <div class="row g-3">
              <!-- Columna Izquierda: Tercero -->
              <div class="col-md-8">
                <div class="card h-100">
                  <h5 class="card-header text-primary form-label">Tercero</h5>
                  <div class="card-body d-flex flex-column">

                    <div class="mb-2">
                      <label for="txtBusquedaTercero" class="form-label">Búsqueda de tercero</label>
                      <div class="input-group input-group-sm input-group-merge">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" class="form-control" placeholder="Búsqueda de tercero..." aria-label="Búsqueda de tercero..." aria-describedby="basic-addon-search" id="txtBusquedaTercero">
                      </div>
                    </div>

                    <input type="hidden" id="id_tercero" name="id_tercero" value="0">

                    <div class="mb-2">
                      <label for="nit" class="form-label">NIT</label>
                      <input type="text" class="form-control form-control-sm" name="nit" id="nit" readonly>
                    </div>

                    <div class="mb-2">
                      <label for="tratamiento" class="form-label">Tratamiento</label>
                      <input type="text" class="form-control form-control-sm text-uppercase" name="tratamiento" id="tratamiento" readonly>
                    </div>

                    <div class="mb-2">
                      <label for="razon_comercial" class="form-label">Razón Comercial</label>
                      <input type="text" class="form-control form-control-sm text-uppercase" name="razon_comercial" id="razon_comercial" readonly>
                    </div>

                    <div class="mb-2">
                      <label for="id_tercero_dir" class="form-label">Dirección</label>
                      <select id="id_tercero_dir" name="id_tercero_dir" class="form-select form-select-sm"></select>
                    </div>



                    <div class="mt-auto">
                      <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalInfo">
                        &nbsp;Ver mas informacion
                      </button>
                    </div>

                  </div>
                </div>
              </div>

              <!-- Columna Derecha: Condiciones -->
              <div class="col-md-4">
                <div class="card h-100">
                  <h5 class="card-header form-label text-primary">Condiciones</h5>
                  <div class="card-body d-flex flex-column">

                    <div class="mb-2">
                      <label for="tipos" class="form-label">Tipo</label>
                      <select class="form-select form-select-sm" id="tipos" name="id_tipo_documentos" disabled></select>
                    </div>

                    <div class="mb-2">
                      <label for="id_condicion" class="form-label">Condicion de pago</label>
                      <select class="form-select form-select-sm" id="id_condicion" name="id_condicion" disabled></select>
                    </div>

                    <div class="card shadow-sm mt-2 alert-primary mb-0">
                      <div class="card-body p-2">
                        <div class="mb-2">
                          <label for="bodegas" class="form-label text-primary">*Bodega origen </label>
                          <select class="form-select form-select-sm" id="bodegas" name="id_bodega"></select>
                        </div>
                        <div class="mb-2">
                          <label for="id_bodega_destino" class="form-label text-primary">*Bodega destino</label>
                          <select class="form-select form-select-sm" id="id_bodega_destino" name="id_bodega_destino"></select>
                        </div>
                      </div>
                    </div>
                    <div>
                      <label for="notas" class="form-label">Notas</label>
                      <textarea class="form-control alert-warning" id="notas" name="notas" rows="2"></textarea>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>
        <div class="tab-pane fade " id="navs-creacion" role="tabpanel">

          <div class="row mb-2">
            <div class="col-md-3">
              <div class="input-group input-group-sm ">
                <span class="input-group-text">Numero</span>
                <input name="numero" id="numero" type="number" class="form-control form-control-sm" aria-describedby="basic-addon11" readonly />
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
          <div class="row g-3 mt-2">
            <!-- Búsqueda de productos -->
            <div class="col-md-8">
              <div class="form-label text-primary fw-bold">
                <span><i class="fa fa-search"></i>&nbsp;Búsqueda de productos</span>
              </div>
              <div class="input-group input-group-merge mt-2">
                <span class="input-group-text"><i class="bx bx-search"></i></span>
                <input
                  name="filtro"
                  id="filtro"
                  type="text"
                  class="form-control form-control-sm filtros-busqueda-productos"
                  placeholder="Código, descripción, descripción larga"
                  aria-label="Buscar..."
                  autocomplete="off" />
                <span class="input-group-text btn btn-sm btn-primary" id="btnBusquedaAvanzada" role="button">
                  <i class="fa-solid fa-sliders"></i>
                </span>
              </div>
            </div>

            <!-- Stock -->
            <div class="col-md-4">
              <div class="form-label text-primary fw-bold">
                <span><i class="fa-solid fa-boxes-stacked"></i>&nbsp;Stock</span>
              </div>
              <div class="input-group input-group-merge mt-2">
                <input
                  name="stock_grid"
                  id="stock_grid"
                  type="text"
                  class="form-control form-control-sm"
                  readonly />
                <span class="input-group-text btn btn-sm btn-primary" role="button">
                  <i class="fa-solid fa-box-open"></i>
                </span>
              </div>
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
          <input type="number" id="stock_real" value="0" readonly style="display:none" class="alert-warning" />
          <input type="text" id="id_lote" value="0" readonly style="display:none ; " class="alert-danger" />
          <input type="hidden" id="precio_unitario" value="0" readonly />
          <input type="number" id="id_referencia" value="0" readonly style="display: none;" class="alert-success" />
          <input type="hidden" id="costo_promedio" value="0" class="alert-secondary" readonly />
          <input type="number" id="id_detalle" style="display:none;" value="0" readonly class="alert-primary">
          <input type="number" id="stock_sin_comp" value="0" readonly style="display:none " class="alert-danger" />
          <div id="dvReferencias" class="mt-3 table-responsive-md">

          </div>
        </div>
        <div class="tab-pane fade " id="navs-temporal" role="tabpanel">
          <div class="card mb-4">
            <div class="card-body">
              <div class="row g-3 align-items-end">

                <!-- Fecha inicial -->
                <div class="col-md-3">
                  <label class="form-label" for="fecha_inicial">
                    Fecha inicial
                  </label>

                  <div class="input-group input-group-sm">
                    <span class="input-group-text">
                      <i class="fa-regular fa-calendar-check"></i>
                    </span>

                    <input type="date"
                      class="form-control form-control-sm"
                      id="fecha_inicial"
                      aria-describedby="fecha_inicial">
                  </div>
                </div>

                <!-- Fecha final -->
                <div class="col-md-3">
                  <label class="form-label" for="fecha_final">
                    Fecha final
                  </label>

                  <div class="input-group input-group-sm">
                    <span class="input-group-text">
                      <i class="fa-regular fa-calendar-check"></i>
                    </span>

                    <input type="date"
                      class="form-control form-control-sm"
                      id="fecha_final"
                      aria-describedby="fecha_final">
                  </div>
                </div>

                <!-- Búsqueda de cliente -->
                <div class="col-md-4">
                  <label class="form-label" for="txtBusquedaTerceroTmp">
                    Cliente
                  </label>

                  <div class="input-group input-group-sm">
                    <span class="input-group-text">
                      <i class="bx bx-search"></i>
                    </span>

                    <input id="txtBusquedaTerceroTmp"
                      type="text"
                      class="form-control form-control-sm"
                      placeholder="Búsqueda de cliente"
                      aria-describedby="basic-addon-search31">
                  </div>

                  <input type="hidden"
                    id="nit_tmp"
                    value=""
                    readonly>
                </div>

                <!-- Botón -->
                <div class="col-md-2">
                  <button type="button"
                    class="btn btn-sm btn-primary w-100"
                    id="buscar-temporal">
                    <i class="fa-solid fa-magnifying-glass"></i>
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
        <div class="tab-pane fade " id="navs-mis-traslados" role="tabpanel">
          <div class="card mb-4">
            <div class="card-body">
              <div class="row g-3 align-items-end">

                <!-- Fecha inicial -->
                <div class="col-md-4">
                  <label class="form-label" for="fecha_ini">
                    Fecha inicial
                  </label>

                  <div class="input-group input-group-sm">
                    <span class="input-group-text">
                      <i class="fa-regular fa-calendar-check"></i>
                    </span>

                    <input type="date"
                      class="form-control form-control-sm"
                      id="fecha_ini"
                      aria-describedby="fecha_ini">
                  </div>
                </div>

                <!-- Fecha final -->
                <div class="col-md-4">
                  <label class="form-label" for="fecha_fin">
                    Fecha final
                  </label>

                  <div class="input-group input-group-sm">
                    <span class="input-group-text">
                      <i class="fa-regular fa-calendar-check"></i>
                    </span>

                    <input type="date"
                      class="form-control form-control-sm"
                      id="fecha_fin"
                      aria-describedby="fecha_fin">
                  </div>
                </div>

                <!-- Botón -->
                <div class="col-md-4">
                  <button type="button"
                    class="btn btn-sm btn-primary w-100"
                    id="buscar-traslados">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    Consultar
                  </button>
                </div>

              </div>

              <div class="row mt-3">
                <div class="col-md-4">
                  <div class="mb-3">
                    <label class="form-label">Bodega Origen</label>
                    <select class="form-select form-select-sm" id="filtro_bodega_origen">
                      <option value="">Todas</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="mb-3">
                    <label class="form-label">Bodega Destino</label>
                    <select class="form-select form-select-sm" id="filtro_bodega_destino">
                      <option value="">Todas</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="mb-3">
                    <label class="form-label">Tipo de Movimiento</label>
                    <select class="form-select form-select-sm" id="filtro_tipo_movimiento">
                      <option value="">Todos</option>
                      <option value="SALIDA">Solo Salida</option>
                      <option value="ENTRADA">Solo Entrada</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="row mt-2">
                <div class="col">
                  <div id="dvMisTraslados" class="table-responsive text-nowrap"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalTrasladosPendientes" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalLongTitle">Traslados pendientes</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="traslados-pendientes"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">
          Cerrar
        </button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalTrasladosPendientesDetalle" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalLongTitle">Traslados pendientes</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="traslados-pendientes-detalle"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-warning" id="recibir-todo-traslados-pendientes-detalle">
          Recibir todo
        </button>
        <button type="button" class="btn btn-sm btn-success" id="procesar-traslados-pendientes-detalle">
          Procesar traslados
        </button>
        <button type="button" class="btn btn-sm btn-danger" data-bs-dismiss="modal">
          Cerrar
        </button>
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


<div class="modal fade" id="modalTrasladosAuto" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
    <div class="modal-content">
      <!-- HEADER -->
      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-0">
            <i class="fa-solid fa-shuffle me-2"></i>
            Traslados automáticos
          </h5>
          <small class="text-muted">
            Puedes crear un traslado basado en la rotación y comportamiento de tu bodega, teniendo en cuenta el inventario de la bodega de origen
          </small>
        </div>
        <button type="button"
          class="btn-close"
          data-bs-dismiss="modal"
          aria-label="Close">
        </button>
      </div>

      <!-- BODY -->
      <div class="modal-body">
        <div class="row align-items-end g-2">
          <!-- Período -->
          <!-- Período (Meses) -->
          <div class="col-md-3">
            <div>
              <label for="filtro_meses" class="form-label fw-semibold mb-1 d-flex align-items-center ">
                <i class="bx bx-info-circle text-primary cursor-pointer"
                  data-bs-toggle="tooltip"
                  data-bs-placement="top"
                  title="Define el rango de meses históricos hacia atrás que se evaluará para calcular el promedio de rotación o ventas del producto."></i>
                &nbsp;
                <span>Período (Meses)</span>
              </label>
              <select id="filtro_meses" class="form-select form-select-sm">
                <option value="1">1 mes</option>
                <option value="2">2 meses</option>
                <option value="3" selected>3 meses</option>
                <option value="4">4 meses</option>
                <option value="6">6 meses</option>
                <option value="12">12 meses</option>
              </select>
            </div>
          </div>

          <!-- Días de Inventario -->
          <div class="col-md-3">
            <div>
              <label for="filtro_dias" class="form-label fw-semibold mb-1 d-flex align-items-center">
                <i class="bx bx-info-circle text-primary cursor-pointer"
                  data-bs-toggle="tooltip"
                  data-bs-placement="top"
                  title="Indica cuántos días de cobertura futura se desean asegurar en la bodega de destino con base en la rotación calculada."></i>
                &nbsp;
                <span>Días de Inventario</span>
              </label>
              <select id="filtro_dias" class="form-select form-select-sm">
                <option value="7">7 días</option>
                <option value="15">15 días</option>
                <option value="30" selected>30 días</option>
                <option value="45">45 días</option>
                <option value="60">60 días</option>
                <option value="90">90 días</option>
                <option value="120">120 días</option>
              </select>
            </div>
          </div>
          <!-- Botones -->
          <div class="col-md-6 d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-sm btn-primary" id="btn-busqueda">
              <i class="fa fa-search"></i> Consultar
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-limpiar">
              <i class="fa-solid fa-eraser"></i> Limpiar
            </button>
            <button type="button" class="btn btn-outline-success btn-sm" onclick="exportarSugeridosExcel()">
              <i class="bx bx-file"></i> Exportar a Excel
            </button>
            <button type="button" class="btn d-none btn-sm btn-outline-secondary" id="btn-solo-disponible" onclick="seleccionarSoloDisponiblesEnArray()">
              <i class="bx bx-check-square"></i> Solo disponibles en origen
            </button>
            <button type="button" class="btn d-none btn-sm btn-outline-info" id="btn-procesar-traslado" onclick="enviarProcesoTraslados()">
              <i class="fa-solid fa-building-circle-arrow-right"></i> Crear traslado
            </button>
          </div>
        </div>

        <div class="mt-3" id="resultado_sugerido"></div>

      </div>
    </div>
  </div>
</div>

<?php

$scriptsPagina = [
  '../controllers/trasladosBodegas.js?' . rand(),
  '../controllers/trasladosAutomaticos.js?' . rand()
];

include '../dependencies/footer.php';

?>