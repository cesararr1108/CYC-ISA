<?php
session_start();

$titulo = 'Sube/Baja Costo Promedio';
include '../dependencies/header.php';
?>
<style>
  .swal2-container {
    z-index: 9999999999 !important;
    font-size: 0.8rem !important;
  }

  .select2-container {
    z-index: 9999999999 !important;
  }

  .input-group .select2-container {
    flex: 1 1 auto;
    width: 100% !important;
  }

  /* Altura exacta de Bootstrap */
  .select2-container .select2-selection--single {
    height: 38px !important;
    /* mismo que .form-control */
    display: flex !important;
    align-items: center;
  }

  /* Alinea el texto verticalmente */
  .select2-selection__rendered {
    line-height: 38px !important;
  }

  /* Flecha centrada */
  .select2-selection__arrow {
    height: 38px !important;
  }

  /* Chrome, Edge, Safari */
  input[type="number"]::-webkit-outer-spin-button,
  input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
  }

  /* Firefox */
  input[type="number"] {
    -moz-appearance: textfield;
    appearance: textfield;
  }
</style>
<input type="hidden" value="<?php echo $_SESSION["Ses_nombres"] . ' ' . $_SESSION["Ses_apellidos"]; ?>" readonly id="nombres_usuario" />
<input type="hidden" value="" id="tmp" readonly />
<input type="hidden" value="" id='vendedor' readonly />
<input type="hidden" id="id_bodega_session" value="<?php if (!empty($_SESSION["Ses_id_bodega"])) {
                                                      echo $_SESSION["Ses_id_bodega"];
                                                    } else {
                                                      echo '0';
                                                    } ?>" />


<div class="card">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Inventario / </span>Toma fisica</h5>
    <div class="nav-align-top">
      <ul class="nav nav-tabs" role="tablist" id="tabs">
        <li class="nav-item">
          <button id="btn-navs-conteos" type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
            data-bs-target="#navs-conteos" aria-controls="navs-conteos" aria-selected="false">
            <i class="fa-solid fa-cart-plus"></i>&nbsp;Conteos</button>
        </li>
        <li class="nav-item" id="li_informe" style="display: none;">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-informe"
            aria-controls="navs-informe" aria-selected="false"><i class="fa-solid fa-chart-pie"></i>
            &nbsp;Informe/Seguimiento </button>
        </li>
        <li class="nav-item" id="li_ajuste" style="display: none;">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-ajuste"
            aria-controls="navs-ajuste" aria-selected="false"> <i class="fa-solid fa-plus-minus"></i> &nbsp;Ajuste
          </button>
        </li>
      </ul>
      <div class="tab-content">

        <div class="tab-pane fade show active mt-1 p-0" id="navs-conteos" role="tabpanel">

          <div class="alert alert-warning info-conteo mt-1">Conteo 1</div>
          <div class="d-flex justify-content-end">
            <button class="btn btn-outline-primary" id="marcar-productos" style="display: none;"><i
                class="fa-solid fa-list-check"></i> Marcar
              productos</button>
          </div>

          <div class="row">
            <div class="col">
              <label class="form-label">Producto</label>
              <div class="input-group">
                <span class="input-group-text" id="basic-addon11"><i class="fa-solid fa-barcode"></i></span>
                <input type="text" class="form-control" placeholder="Búsqueda de producto" aria-label="Username"
                  aria-describedby="basic-addon11" id="busqueda" />
                <input type="hidden" id="id_referencia" readonly />
                <span class="input-group-text" id="basic-addon11">
                  <button class="btn btn-sm btn-outline-secondary" type="button" id="limpiarBusqueda"><i
                      class="fa-solid fa-eraser"></i></button>
                </span>
              </div>
            </div>
            <div class="col">
              <label class="form-label">Descripción</label>
              <div class="input-group p-1">
                <span class="input-group-text" id="basic-addon12"><i class="fa-solid fa-barcode"></i></span>
                <input type="text" class="form-control" id="descripcion" aria-describedby="basic-addon12" readonly />
              </div>
            </div>
          </div>
          <div class="row mt-2">
            <div class="col-md-6">
              <label class="form-label">Lotes</label>
              <div class="input-group mt-1">
                <button class="input-group-text btn  btn-outline-success " id="btn-crear-lotes"> <i
                    class="fas fa-plus"></i> </button>
                <div class="flex-grow-1">
                  <select id="lotes" class="form-control p-2">
                    <option>Seleccione un lote</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Cantidad</label>
              <div class="input-group p-1">

                <input type="number" class="form-control" id="cantidad" inputmode="numeric" pattern="[0-9]*"
                  aria-describedby="basic-addon12" />
                <span class="input-group-text" id="btn-guardar-conteo">
                  <i class="fa-solid fa-save text-primary" title="Guardar cantidad" style="cursor: pointer;"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
        <div class="tab-pane fade show  m-0 p-0" id="navs-informe" role="tabpanel">
          <div class="row">
            <div class="col-md-6">
              <label class="form-label">Usuarios</label>
              <div class="input-group input-group-sm mt-1">
                <span class="input-group-text" id="basic-addon11">
                  <i class="fa-solid fa-user"></i>
                </span>
                <div class="flex-grow-1">
                  <select id="select_usuario" class="form-control form-control-sm p-2">
                    <option></option>
                  </select>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Conteos</label>
              <div class="input-group input-group-sm mt-1">

                <div class="flex-grow-1">
                  <select id="select_conteo" class="form-control form-control-sm p-2">
                    <option value="">Seleccionar</option>
                    <option value="0">Todos</option>
                    <option value="1">Conteo 1 </option>
                    <option value="2">Conteo 2 </option>
                    <option value="3">Conteo 3 </option>
                  </select>
                </div>
                <span class="input-group-text" id="basic-addon11">
                  <i class="fa-solid fa-trash-can text-danger" id="BtnEliminarConteo"
                    style="display: none; cursor: pointer;"></i>
                </span>
              </div>
            </div>

          </div><!--  <div class="row"> -->
          <div class="row mt-2">
            <div class="col">
              <button class="btn btn-sm btn-outline-success" id="consultar"><i class="fa fa-search"></i>
                Consultar</button>
            </div>
          </div>
          <div class="row mt-3">
            <div class="col-md-4 mb-3">

            </div>
          </div>
          <div class="row mt-3">
            <div class="col">
              <div id="result-info" class="table-responsive text-nowrap"></div>
            </div>
          </div>
        </div>
        <div class="tab-pane fade show m-0 p-0" id="navs-ajuste" role="tabpanel">
          <div class="card">
            <div class="card-body">
              <div class="row">
                <div class="col-6">
                  <label for="condicion" class="form-label">Tercero</label>
                  <input type="text" class="form-control form-control-sm" id="tercero" disabled />
                  <input type="hidden" readonly id="id_tercero">
                  <label for="direccion" class="form-label">Dirección</label>
                  <input type="text" class="form-control form-control-sm" id="direccion" readonly />
                  <input type="hidden" readonly id="id_direccion" />
                  <label style="display:none" for="condicion" class="form-label">Condicion de pago</label>
                  <select style="display:none" class="form-select form-select-sm" id="id_condicion"
                    name="id_condicion" disabled>
                  </select>
                </div>
                <div class="col-6">
                  <label for="tipo_doc_salida" class="form-label">Tipo doc. salida</label>
                  <select class="form-select form-select-sm" id="tipo_doc_salida" name="tipo_doc_salida">
                  </select>
                  <label for="tipo_doc_entrada" class="form-label">Tipo doc. entrada</label>
                  <select class="form-select form-select-sm" id="tipo_doc_entrada" name="tipo_doc_entrada">
                  </select>
                </div>
              </div>
              <div class="row">
                <div class="col-12">
                  <label for="notas" class="form-label">Notas</label>
                  <textarea class="form-control" id="notas" name="notas"></textarea>
                </div>
              </div>
            </div>
          </div>


          <div class="row mt-2">
            <div class="col-md-5 mb-3">
              <div class="card shadow-sm border-primary text-center p-3 rounded-3">

                <div class="row">
                  <div class="col-md-8">
                    <label>AJUSTE POR SALIDA</label>
                    <h4 class="fw-bold text-danger mt-1" id="ajuste_salidas">0</h4>
                  </div>
                  <div class="col-md-4 d-flex jsutify-content-center align-items-center">

                    <div class="rounded-circle alert-danger d-flex justify-content-center align-items-center p-1">
                      <i class="fa-solid fa-circle-down fa-2x text-danger"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-5 mb-3">
              <div class="card shadow-sm border-primary text-center p-3 rounded-3">
                <div class="row">
                  <div class="col-md-8">
                    <label>AJUSTE POR ENTRADA</label>
                    <h4 class="fw-bold text-success mt-1" id="ajuste_entradas">0</h4>
                  </div>
                  <div class="col-md-4 d-flex jsutify-content-center align-items-center">
                    <div class="rounded-circle alert-success d-flex justify-content-center align-items-center p-1">
                      <i class="fa-solid fa-circle-up fa-2x text-success"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-2 mb-3">
              <button class="btn btn-outline-primary w-100" id="consultar_pre_ajuste">
                <i class="fa-solid fa-search"></i> Consultar
              </button>
              <button class="btn btn-danger w-100 mt-2" id="realizar_ajuste">
                <i class="fa-solid fa-arrow-right-arrow-left"></i>
                Realizar ajuste
              </button>
            </div>

          </div>
          <div class="row">
            <div class="col">
              <div id="result-pre-ajuste" class="mt-4 table-responsive text-nowrap"></div>
            </div>
          </div>
        </div>

      </div><!--  <div class="tab-content"> -->
    </div><!-- <div class="nav-align-top"> -->
  </div><!--  <div class="card " style="height: 95vh;"> -->

  <div id="modal-container"></div>

  <div class="modal fade" id="listado-productos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="">Seleccione el producto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="result-referencias"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="modalCrearLote" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Creación de lotes</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="input-group input-group-merge mb-3">
            <span class="input-group-text"><i class="fa-solid fa-barcode"></i></span>
            <input type="number" readonly id="id_referencia_lote" class="form-control" />
          </div>
          <div class="input-group input-group-merge mb-3">
            <span class="input-group-text"><i class="fa-solid fa-boxes-stacked"></i></span>
            <input type="text" readonly id="descripcion_referencia_lote" class="form-control" />
          </div>
          <div class="input-group input-group-merge mb-3">
            <span class="input-group-text"><i class="fa-solid fa-check-double"></i></span>
            <input type="text" id="lote_lote" maxlength="30" class="form-control" />
          </div>
          <div class="input-group input-group-merge mb-3">
            <span class="input-group-text"><i class="fa-solid fa-calendar"></i></span>
            <input type="date" id="fecha_vencimiento_lote" placeholder="dd-mm-aaaa" class="form-control" />
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-primary" id="crear-lote">Crear lote</button>
          <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="modalMarcarProductos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Marcar productos</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row mt-3">
            <div class="col-12">
              <label for="filtro_referencias" class="form-label">Tipo de marcado</label>
              <select class="form-select mb-4" id="filtro_referencias">
                <option value="">Seleccione</option>
                <option value="Ubicacion">Por Ubicacion</option>
                <option value="Marca">Por Marca</option>
                <!-- <option value="Categorias">Por categorias</option>-->
                <option value="Todas">Con movimientos</option>
              </select>
              <div id="filtros">
                <!-- ubicaciones -->
                <div class="card mb-2 card-filtros card-ubicaciones" style="display:none">
                  <h5 class="card-header text-primary">Filtro por ubicaciones</h5>
                  <div class="card-body">
                    <label for="ubicaciones" class="form-label">Ubicaciones</label><br>
                    <select class="form-select form-select-sm" style="width:100%" id="ubicaciones" multiple></select>
                  </div>
                </div>
                <!-- MARCAS -->
                <div class="card mb-2 card-filtros card-marcas" style="display:none">
                  <h5 class="card-header text-primary">Filtro por marcas</h5>
                  <div class="card-body">
                    <label for="bodega" class="form-label">Marcas</label><br>
                    <select class="form-select" style="width:100%" id="marcas" multiple></select>
                  </div>
                </div>

                <div>
                  <div class="card mb-2 card-filtros card-categorias" style="display:none">
                    <h5 class="card-header text-primary">Filtro por categorias</h5>
                    <div class="card-body">
                      <label for="slcCategoria" class="form-label">Categoria 1</label>
                      <div class="input-group mb-1">
                        <select class="form-select" style="width:100%" id="slcCategoria1" multiple>
                        </select>

                      </div>
                      <label for="slcCategoria1" class="form-label">Categoria 2</label>
                      <div class="input-group mb-1">
                        <select class="form-select" style="width:100%" id="slcCategoria2" multiple>
                        </select>

                      </div>
                      <label for="slcCategoria2" class="form-label">Categoria 3</label>
                      <div class="input-group mb-1">
                        <select class="form-select" style="width:100%" id="slcCategoria3" multiple>
                        </select>

                      </div>
                      <label for="slcCategoria3" class="form-label">Categoria 4</label>
                      <div class="input-group mb-1">
                        <select class="form-select" style="width:100%" id="slcCategoria4" multiple>
                        </select>

                      </div>
                    </div>
                  </div>
                </div>
                <div class="card card-filtros card-totas">
                  <div class="card-body">
                    <div class="alert alert-danger">
                      <p>Se marcaran todas las referencias para inventarios. </p>
                      <ul>
                        <li>Todas las referencias activas</li>
                        <li>Todas las categorias</li>
                        <li>Todas las marcas</li>
                        <li>Todas las ubicaciones</li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class=" d-flex justify-content-center align-items-center mt-4 gap-2">
            <button class="btn btn-outline-success" id="marcar-referencias"><i class="fa-solid fa-check-double"></i>
              Marcar referencias</button>
            <button class="btn btn-outline-danger" id="desmarcar-referencias">Desmarcar referencias</button>
          </div>


        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </div>



<?php

$scriptsPagina = [
    '../controllers/TomaFisica.js?' . rand()
];

include '../dependencies/footer.php';

?>