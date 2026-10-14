<?php
session_start();

$titulo = 'Entradas de inventario';
include '../dependencies/header.php';
?>
<input type="hidden" id="id_bodega_session" value="<?php if (!empty($_SESSION["Ses_id_bodega"])) {
                                                      echo $_SESSION["Ses_id_bodega"];
                                                    } else {
                                                      echo  '0';
                                                    } ?>" />
<div class="card" style="height: auto;">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Inventarios /</span> Entradas de inventario</h5>
    <div class="nav-align-top">
      <ul class="nav nav-tabs" role="tablist" id="tabs">
        <li class="nav-item">
          <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-tercero" aria-controls="navs-tercero" aria-selected="true"> <i class="fa-solid fa-user-tie"></i> &nbsp;Tercero </button>
        </li>
        <li class="nav-item" id="li_item">
          <button id="btn-navs-creacion" type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-creacion" aria-controls="navs-creacion" aria-selected="false"> <i class="fa-regular fa-pen-to-square"></i> &nbsp;Creación </button>
        </li>
        <li class="nav-item">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-temporal" aria-controls="navs-temporal" aria-selected="false"> <i class="fa-regular fa-clock"></i> &nbsp;Temporal </button>
        </li>
        <li class="nav-item" style="flex:1;"></li>
        <li class="nav-item border-danger">
          <button type="button" class="btn btn-outline-success btn-sm" id="btn-csv"><i class="fa-solid fa-file-excel"></i> Subir Excel</button>
          <button type="reset" class="btn btn-sm btn-outline-secondary" id="btnLimpiar"> <i class="fa-regular fa-file"></i> Nuevo</button>
          <button type="submit" class="btn btn-sm btn-outline-primary" id="btnGuardar"> <i class="fa-regular fa-floppy-disk"></i> Guardar</button>
          <button type="button" class="btn btn-sm btn-outline-danger" id="btn-video-tutorial" title="Ver video tutorial"> <i class="fab fa-youtube"></i> <span class="d-none d-md-inline">Tutorial</span></button>
        </li>
        </li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane fade show active" id="navs-tercero" role="tabpanel">
          <form id="FormEncabezado" class="needs-validation  form-basico" novalidate>
            <div class="row">
              <div class="col-md-8">
                <div class="card mb-4">
                  <h5 class="card-header text-primary form-label">Tercero</h5>
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
                    <input type="text" class="form-control form-control-sm text-uppercase" id="textDireccion" readonly>
                    <input type="hidden" id="id_tercero_dir" name="id_tercero_dir" value="0" readonly>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="card mb-4">
                  <h5 class="card-header text-primary form-label">Condiciones</h5>
                  <div class="card-body">
                    <label for="documento" class="form-label">Documento</label>
                    <input type="text" class="form-control form-control-sm bloqueado text-uppercase" id="documento" name="documento" maxlength="20" />
                    <label for="bodega" class="form-label">Bodega</label>
                    <select class="form-select form-select-sm bloqueado" id="bodegas" name="id_bodega">
                    </select>
                    <label for="tipo" class="form-label">Tipo</label>
                    <select class="form-select form-select-sm bloqueado" id="tipo" name="id_tipo_documento">
                    </select>
                    <label for="tipo" class="form-label">Condicion</label>
                    <select class="form-select form-select-sm bloqueado" id="condicion" name="condicion">
                    </select>
                    <label for="notas" class="form-label">Notas</label>
                    <textarea class="form-control " id="notas" name="notas"></textarea>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>
        <div class="tab-pane fade " id="navs-creacion" role="tabpanel">
          <div class="row">
            <div class="col-md-12">
              <input type="hidden" id="id_transaccion" readonly value="0">
              <div id="fila-encabezado">
                <div class="row mt-1">
                  <div class="col-md-3">
                    <div class="input-group input-group-sm input-group-merge">
                      <span class="input-group-text label-totales">Subtotal</span>
                      <input type="text" class="form-control form-control-sm text-end" id="total-sub-total" value="0" readonly>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="input-group input-group-sm input-group-merge">
                      <span class="input-group-text label-totales">Descuento</span>
                      <input type="text" class="form-control form-control-sm text-end" id="total-descuento" value="0" readonly>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="input-group input-group-sm input-group-merge">
                      <span class="input-group-text label-totales">IVA</span>
                      <input type="text" class="form-control form-control-sm text-end" id="total-iva" value="0" readonly>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="input-group input-group-sm input-group-merge">
                      <span class="input-group-text label-totales">Impoconsumo</span>
                      <input type="text" class="form-control form-control-sm text-end" id="total-impoconsumo" value="0" readonly>
                    </div>
                  </div>
                </div>
                <!-- row 2: impuestos adicionales + total bruto -->
                <div class="row mt-1">
                  <div class="col-md-3">
                    <div class="input-group input-group-sm input-group-merge">
                      <span class="input-group-text label-totales">Numero</span>
                      <input type="text" class="form-control form-control-sm text-end" id="numero" name="numero" value="0" readonly>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="input-group input-group-sm input-group-merge">
                      <span class="input-group-text label-totales">IBUA</span>
                      <input type="text" class="form-control form-control-sm text-end" id="total-ibua" value="0" readonly>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="input-group input-group-sm input-group-merge">
                      <span class="input-group-text label-totales">ICUI</span>
                      <input type="text" class="form-control form-control-sm text-end" id="total-icui" value="0" readonly>
                    </div>
                  </div>
                  <!--  <div class="col-md-3"></div>-->
                  <div class="col-md-3">
                    <div class="input-group input-group-sm input-group-merge">
                      <span class="input-group-text label-totales fw-bold">Total Bruto</span>
                      <input type="text" class="form-control form-control-sm text-end fw-bold" id="total-total" value="0" readonly>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="row mt-3">
            <div class="col-md-12">
              <div class="input-group input-group-sm">

                <span class="input-group-text">
                  <i class="bx bx-search"></i>
                </span>

                <input
                  id="filtro"
                  type="text"
                  class="form-control"
                  placeholder="Código, descripción, descripción larga"
                  autocomplete="off">

                <button
                  type="button"
                  class="btn btn-outline-secondary ms-1"
                  id="btnMostrarOcultar">

                  <i class="fa-regular fa-eye-slash"></i>
                  <span class="d-none d-md-inline texto-boton">
                    Ocultar totales
                  </span>

                </button>

              </div>
            </div>
          </div>
          <div id="dvReferencias" class="mt-3 table-responsive">
            <table class="table table-sm table-hover table-striped" id="items_">
              <thead class="table-primary text-nowrap">
                <tr class="text-primary tr-head-items">
                </tr>
              </thead>
              <tbody>
              </tbody>
            </table>
          </div>
        </div>
        <div class="tab-pane fade" id="navs-temporal" role="tabpanel">

          <div class="row g-3 align-items-end">

            <!-- Fecha inicial -->
            <div class="col-md-4">
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
            <div class="col-md-4">
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

            <!-- Botón -->
            <div class="col-md-4">
              <button type="button"
                class="btn btn-sm btn-primary w-100"
                id="buscar-temporal">
                Consultar
              </button>
            </div>

          </div>

          <!-- Resultados -->
          <div class="row mt-3">
            <div class="col">
              <div id="temporales"></div>
            </div>
          </div>

        </div>

      </div>
    </div>
  </div>
</div>



<div class="modal fade" id="modalAdd" aria-hidden="true" data-bs-backdrop="static">
  <form id="FormDetalle" class="needs-validation" autocomplete="off" novalidate>
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
      <div class="modal-content">

        <!-- Header -->
        <div class="modal-header">
          <h5 class="modal-title">Agregar referencia</h5>

          <button type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Close">
          </button>
        </div>

        <!-- Body -->
        <div class="modal-body">

          <input type="hidden" id="costo_unitario" value="0" readonly>
          <input type="hidden" id="id_detalle" value="0" readonly>
          <input type="hidden" id="id_referencia" name="id_referencia" value="0" readonly>

          <div class="row g-3">

            <!-- ========================= -->
            <!-- REFERENCIA -->
            <!-- ========================= -->
            <div class="col-md-8">
              <div class="card h-100">

                <div class="card-body">

                  <h6 class="card-title text-primary form-label mb-3">
                    Referencia seleccionada
                  </h6>

                  <div class="row g-2 align-items-end">

                    <div class="col-md-3">
                      <label for="codigo" class="form-label">
                        Código
                      </label>

                      <input type="text"
                        class="form-control form-control-sm bg-light"
                        id="codigo"
                        required
                        readonly>
                    </div>

                    <div class="col-md-9">
                      <label for="descripcion_larga" class="form-label">
                        Descripción
                      </label>

                      <input type="text"
                        class="form-control form-control-sm bg-light"
                        id="descripcion_larga"
                        readonly>

                      <input type="hidden"
                        id="descripcion_corta"
                        readonly>
                    </div>

                  </div>

                  <div class="mt-3">
                    <div class="small text-muted border rounded p-2">
                      Ingrese los datos de compra para calcular automáticamente
                      el costo neto, el total y el promedio proyectado.
                    </div>
                  </div>

                </div>
              </div>
            </div>


            <!-- ========================= -->
            <!-- IMPUESTOS -->
            <!-- ========================= -->
            <div class="col-md-4">
              <div class="card h-100">

                <div class="card-body">

                  <h6 class="card-title text-primary form-label mb-3">
                    Impuestos
                  </h6>

                  <div class="row g-2">

                    <div class="col-6">
                      <label for="iva" class="form-label">
                        IVA
                      </label>

                      <input type="text"
                        class="form-control form-control-sm bg-light"
                        tabindex="-1"
                        id="iva"
                        name="pcj_iva"
                        readonly>
                    </div>

                    <div class="col-6">
                      <label for="impoconsumo" class="form-label">
                        Impoconsumo
                      </label>

                      <input type="text"
                        class="form-control form-control-sm bg-light"
                        tabindex="-1"
                        id="impoconsumo"
                        name="impoconsumo"
                        readonly>
                    </div>

                    <div class="col-6">
                      <label for="ibua" class="form-label">
                        Ibua
                      </label>

                      <input type="text"
                        class="form-control form-control-sm bg-light"
                        tabindex="-1"
                        id="ibua"
                        name="ibua"
                        readonly>
                    </div>

                    <div class="col-6">
                      <label for="icui" class="form-label">
                        Icui
                      </label>

                      <input type="text"
                        class="form-control form-control-sm bg-light"
                        tabindex="-1"
                        id="icui"
                        name="icui"
                        readonly>
                    </div>

                  </div>

                </div>
              </div>
            </div>


            <!-- ========================= -->
            <!-- DATOS DEL INGRESO -->
            <!-- ========================= -->
            <div class="col-md-8">
              <div class="card h-100">

                <div class="card-body">

                  <h6 class="card-title text-primary form-label mb-3">
                    Datos del ingreso
                  </h6>

                  <div class="row g-3">

                    <!-- Cantidad -->
                    <div class="col-md-2">
                      <label for="cantidad" class="form-label">
                        Cantidad
                      </label>

                      <input type="number"
                        class="form-control form-control-sm calculo-costo"
                        tabindex="1"
                        min="1"
                        name="cantidad"
                        id="cantidad"
                        required
                        onkeypress="return vnumeros(event)">

                      <div class="invalid-feedback">
                        La cantidad debe ser mayor a 0
                      </div>
                    </div>


                    <!-- Costo -->
                    <div class="col-md-2">
                      <label for="costo" class="form-label">
                        Costo
                      </label>

                      <input type="text"
                        class="form-control form-control-sm format-num calculo-costo"
                        maxlength="21"
                        min="1"
                        tabindex="2"
                        required
                        name="costo_unitario"
                        id="costo">

                      <div class="invalid-feedback">
                        El costo debe ser mayor a 0
                      </div>
                    </div>


                    <!-- Descuento -->
                    <div class="col-md-2">
                      <label for="pcj_descuento" class="form-label">
                        Descuento
                      </label>

                      <input type="text"
                        class="form-control form-control-sm format-num calculo-costo"
                        tabindex="3"
                        id="pcj_descuento"
                        name="pcj_descuento"
                        value="0">
                    </div>


                    <!-- Lote -->
                    <div class="col-md-3">
                      <label for="lote" class="form-label">
                        Lote
                      </label>

                      <input type="hidden"
                        readonly
                        id="tmp_">

                      <input type="hidden"
                        id="id_lote"
                        name="id_lote"
                        value="0"
                        readonly>

                      <div class="input-group input-group-sm">

                        <input type="text"
                          class="form-control text-uppercase"
                          tabindex="4"
                          name="lote"
                          required
                          id="lote">

                        <button type="button"
                          class="btn btn-outline-primary"
                          id="btnBuscarLotes"
                          title="Buscar lotes">
                          <i class="fa fa-search"></i>
                        </button>

                      </div>

                      <div class="invalid-feedback">
                        Debe digitar el lote
                      </div>
                    </div>


                    <!-- Vencimiento -->
                    <div class="col-md-3">
                      <label for="vmcto" class="form-label">
                        Vencimiento
                      </label>

                      <input type="date"
                        class="form-control form-control-sm"
                        tabindex="5"
                        id="vmcto"
                        name="vmcto"
                        required>

                      <div class="invalid-feedback">
                        Debe digitar la fecha de vencimiento
                      </div>
                    </div>


                    <!-- Unidad -->
                    <div class="col-md-12">
                      <label for="unidad" class="form-label">
                        Unidad
                      </label>

                      <select class="form-select form-select-sm calculo-costo"
                        tabindex="6"
                        id="unidad"
                        required
                        multiple
                        size="4">
                      </select>

                      <div class="invalid-feedback">
                        Debe seleccionar una unidad de conversión
                      </div>
                    </div>

                  </div>

                </div>
              </div>
            </div>


            <!-- ========================= -->
            <!-- RESUMEN -->
            <!-- ========================= -->
            <div class="col-md-4">
              <div class="card h-100">

                <div class="card-body">

                  <h6 class="card-title text-primary form-label mb-3">
                    Resumen de datos
                  </h6>

                  <div class="row g-2">

                    <!-- Costo Neto -->
                    <div class="col-md-6">
                      <label for="costo_neto" class="form-label">
                        Costo Neto
                      </label>

                      <input type="text"
                        class="form-control form-control-sm bg-light format-num"
                        id="costo_neto"
                        tabindex="-1"
                        readonly>
                    </div>


                    <!-- Costo Total -->
                    <div class="col-md-6">
                      <label for="costo_total" class="form-label">
                        Costo Total
                      </label>

                      <input type="text"
                        class="form-control form-control-sm bg-success-subtle format-num"
                        id="costo_total"
                        tabindex="-1"
                        readonly>
                    </div>


                    <!-- Costo promedio -->
                    <div class="col-md-6">
                      <label for="promedio" class="form-label">
                        Costo promedio
                      </label>

                      <input type="text"
                        class="form-control form-control-sm bg-light format-num"
                        id="promedio"
                        tabindex="-1"
                        readonly>
                    </div>


                    <!-- Promedio calculado -->
                    <div class="col-md-6">
                      <label for="promedio_calculado" class="form-label">
                        Promedio calculado
                      </label>

                      <input type="text"
                        class="form-control form-control-sm bg-success-subtle format-num"
                        id="promedio_calculado"
                        tabindex="-1"
                        readonly>
                    </div>


                    <!-- Último costo -->
                    <div class="col-md-6">
                      <label for="ultimo_costo" class="form-label">
                        Último costo
                      </label>

                      <input type="text"
                        class="form-control form-control-sm bg-light format-num"
                        id="ultimo_costo"
                        tabindex="-1"
                        readonly>
                    </div>


                    <!-- Stock -->
                    <div class="col-md-6">
                      <label for="stock" class="form-label">
                        Stock
                      </label>

                      <input type="text"
                        class="form-control form-control-sm bg-light"
                        id="stock"
                        tabindex="-1"
                        readonly>
                    </div>

                  </div>

                </div>
              </div>
            </div>

          </div>
        </div>


        <!-- Footer -->
        <div class="modal-footer">

          <button type="submit"
            class="btn btn-sm btn-outline-primary"
            tabindex="7"
            id="btnAddReferencia">
            Agregar
          </button>

          <button type="reset"
            class="btn btn-sm btn-outline-secondary"
            data-bs-dismiss="modal">
            Cerrar
          </button>

        </div>

      </div>
    </div>

  </form>
</div>
<div id="modal-container"></div>

<div class="modal fade" id="modalCsv" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-xl " role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="">Cargar inventario por archivo plano</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="">
        <div class="alert alert-primary d-flex align-items-start">
          <i class="fa fa-exclamation-triangle me-3 mt-1"></i>
          <div>
            <h6 class="alert-heading mb-3">Instrucciones para el archivo Excel</h6>
            <ul class="mb-2">
              <li>
                El archivo debe contener las siguientes columnas en este orden:
                <strong>Código, Costo, Cantidad, Lote, Vmcto</strong>.
              </li>
              <li>
                <strong>Lote</strong> es obligatorio. Si no se cuenta con un lote específico,
                utilice <strong>"INICIAL"</strong> como valor por defecto.
              </li>
              <li>
                <strong>Vmcto</strong> es obligatorio. Si no se dispone de una fecha de vencimiento,
                utilice <strong>"1900-01-01"</strong> como valor por defecto.
              </li>
              <li>
                Verifique que el archivo esté correctamente formateado para evitar errores durante la carga.
              </li>
            </ul>
          </div>
        </div>
        <div class="input-group input-group-sm mb-3">
          <input type="file" class="form-control" id="csv" aria-label="Upload" />
          <button class="btn btn-sm btn-outline-primary" type="button" id="subir">Subir archivo</button>
          <button class="btn btn-sm btn-outline-success" type="button" id="btnUp">Actualizar inv.</button>
        </div>
        <hr>
        <div class="row">
          <div class="col">
            <div id="tableContainer" class="table-responsive text-nowrap"></div>
          </div>
        </div>
        <div class="row">
          <div id="errores"></div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- Modal para seleccionar lotes -->
<div class="modal fade" id="modalLotes" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Seleccionar lote</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="table-responsive">
          <table class="table table-hover table-sm align-middle">
            <thead class="table-primary">
              <tr>
                <th>Lote</th>
                <th>Vencimiento</th>
                <th width="100">Acción</th>
              </tr>
            </thead>
            <tbody id="tblLotesBody">
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>
<!-- Modal Video YouTube -->
<div class="modal fade" id="modalVideoTutorial" tabindex="-1" aria-labelledby="modalVideoTutorialLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalVideoTutorialLabel">
          <i class="fab fa-youtube"></i> Tutorial Video
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <div class="ratio ratio-16x9">
          <iframe id="videoFrameTutorial" src="" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
        </div>
      </div>
    </div>
  </div>
</div>


<?php

$scriptsPagina = [
  '../controllers/Entradas.js?' . rand()
];

include '../dependencies/footer.php';

?>