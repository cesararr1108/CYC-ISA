<?php
session_start();

$titulo = 'Ingreso de mercancías';
include '../dependencies/header.php';
?>
<style>
  #modalBusquedaRef {
    z-index: 20000 !important;
  }

  .swal2-container {
    z-index: 20000 !important;
    font-size: 0.9rem !important;
  }

  .label-totales {
    font-size: 0.72rem;
    font-weight: 600;
    width: 95px;
  }

  .form-control:focus {
    background-color: #f5f2ff !important;
  }

  #items_,
  #tbodyPreviewMasivo,
  #tbodyPreviewMasivo tbody tr td {
    font-size: 0.7rem;
    padding: 1px;
  }

  .selected {
    background-color: rgb(8, 132, 233);
    color: #ffffff;
    border: none;
    outline: none;
  }

  .form-control-plaintext {
    padding: 1px;
    outline: none;
  }

  .table-total tr td {
    padding: 1px;
  }

  .lista-precio-celda {
    min-width: 165px;
    white-space: normal;
    line-height: 1.15;
  }

  .lista-precio-celda .form-control,
  .lista-precio-celda .btn {
    font-size: 0.72rem;
  }

  .lista-precio-celda .texto-negativo {
    color: var(--bs-danger);
    font-weight: 700;
  }

  .rentabilidad-negativa {
    border-color: #dc3545 !important;
    background-color: #fff5f5 !important;
    color: #dc3545 !important;
    font-weight: 700;
    animation: latidoRentabilidad 0.9s infinite;
  }

  @keyframes latidoRentabilidad {
    0% {
      transform: scale(1);
      box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.55);
    }

    50% {
      transform: scale(1.02);
      box-shadow: 0 0 0 6px rgba(220, 53, 69, 0.10);
    }

    100% {
      transform: scale(1);
      box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
    }
  }
</style>

<input type="hidden" id="id_bodega_session" value="<?php if (!empty($_SESSION["Ses_id_bodega"])) {
                                                      echo $_SESSION["Ses_id_bodega"];
                                                    } else {
                                                      echo  '0';
                                                    } ?>" />

<div class="card" style="height: auto;">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Inventarios /</span> Compras e ingreso de mercancías</h5>
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
          <button type="button" class="btn btn-sm btn-outline-success" id="btnCargaMasiva"><i class="fa-solid fa-file-import"></i> Cargar Archivo</button>
          <button type="reset" class="btn btn-sm btn-outline-secondary " id="btnLimpiar"> <i class="fa-regular fa-file"></i> <span class="d-none d-md-inline">Nuevo</span></button>
          <button type="submit" class="btn btn-sm btn-outline-primary" id="btnGuardar"> <i class="fa-regular fa-floppy-disk"></i> <span class="d-none d-md-inline">Guardar</span></button>
          <button type="button" class="btn btn-sm btn-outline-danger" id="btn-video-tutorial" title="Ver video tutorial"> <i class="fab fa-youtube"></i> <span class="d-none d-md-inline">Tutorial</span></button>
        </li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane fade show active" id="navs-tercero" role="tabpanel">

          <form id="FormEncabezado" class="needs-validation form-basico" autocomplete="off" novalidate>
            <div class="row">
              <div class="col-md-8">
                <div class="card h-100 mb-4">
                  <h5 class="card-header text-primary form-label">Tercero</h5>
                  <div class="card-body mt-auto ">
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
                    <label for="notas" class="form-label">Notas</label>
                    <textarea class="form-control " id="notas" name="notas"></textarea>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="card h-100 mb-4">
                  <h5 class="card-header text-primary form-label">Condiciones</h5>
                  <div class="card-body">
                    <label for="documento" class="form-label">Documento</label>
                    <input type="text" class="form-control form-control-sm bloqueado text-uppercase" id="documento" name="documento" maxlength="20" />
                    <label for="fecha" class="form-label">Fecha de Documento</label>
                    <div class="input-group input-group-merge"> <span class="input-group-text"><i class="fa-regular fa-calendar-check"></i></span>
                      <input type="date" class="form-control form-control-sm" id="fecha" aria-describedby="fecha" />
                    </div>
                    <label for="bodega" class="form-label">Bodega</label>
                    <select class="form-select form-select-sm bloqueado" id="bodegas" name="id_bodega">
                    </select>
                    <label for="tipo" class="form-label">Tipo</label>
                    <select class="form-select form-select-sm bloqueado" id="tipo" name="id_tipo_documento">
                    </select>
                    <input type="hidden" id="id_perfil_tributario" value="0" readonly>
                    <label for="perfil_tributario_compra" class="form-label">Perfil tributario</label>
                    <select class="form-select form-select-sm" id="perfil_tributario_compra" disabled>
                      <option value="">NO APLICA</option>
                    </select>
                    <label for="concepto_retencion" class="form-label">Concepto de retención</label>
                    <select class="form-select form-select-sm bloqueado" id="concepto_retencion" name="id_concepto_retencion">
                      <option value="">NO APLICA</option>
                    </select>
                    <label for="tipo" class="form-label">Condicion</label>
                    <select class="form-select form-select-sm bloqueado" id="condicion" name="condicion">
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>
        <div class="tab-pane fade " id="navs-creacion" role="tabpanel">
          <div id="fila-encabezado">
            <input type="hidden" id="id_transaccion" readonly value="0">
            <!--<label for="numero">Numero</label>
                <input type="text" class="form-control-plaintext" name="numero" id="numero" readonly>-->
            <!-- row 1: valores principales -->
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
            <!-- row 3: retenciones -->
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
            <!-- row 4: neto a pagar -->
            <div class="row mt-1 justify-content-end">
              <div class="col-md-3">
                <div class="input-group input-group-sm input-group-merge">
                  <span class="input-group-text text-primary label-totales">Neto a Pagar</span>
                  <input type="text" class="form-control form-control-sm text-end text-primary fw-bold" id="total-neto-pagar" value="$0" readonly>
                </div>
              </div>
            </div>
          </div>
          <div class="row mt-3 align-items-center">

            <!-- Buscador -->
            <div class="col">
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
              </div>
            </div>

            <!-- Botón Crear -->
            <div class="col-auto">
              <button
                type="button"
                class="btn btn-sm btn-outline-success d-none"
                id="btnCrearReferencia">
                <i class="fa-solid fa-plus"></i>
                <span class="d-none d-lg-inline">Crear referencia</span>
              </button>
            </div>

            <!-- Botón Ocultar -->
            <div class="col-auto">
              <button
                type="button"
                class="btn btn-sm btn-outline-secondary"
                id="btnMostrarOcultar">
                <i class="fa-regular fa-eye-slash"></i>
                <span class="d-none d-lg-inline">Ocultar totales</span>
              </button>
            </div>

          </div>
          <div id="dvReferencias" class="mt-3 table-responsive-md">
            <table class="table table-sm table-hover table-striped" id="items_">
              <thead class="table-primary">
                <tr class="text-primary tr-head-items"> </tr>
              </thead>
              <tbody>
              </tbody>
            </table>
          </div>
        </div>
        <div class="tab-pane fade " id="navs-temporal" role="tabpanel">
          <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">

              <div class="row g-2 align-items-end">

                <!-- Fecha inicial -->
                <div class="col-4">
                  <label class="form-label small fw-semibold mb-1" for="fecha_inicial">
                    Fecha inicial
                  </label>

                  <div class="input-group input-group-sm input-group-merge">
                    <span class="input-group-text">
                      <i class="fa-regular fa-calendar-check"></i>
                    </span>

                    <input
                      type="date"
                      class="form-control form-control-sm"
                      id="fecha_inicial"
                      aria-describedby="fecha_inicial" />
                  </div>
                </div>

                <!-- Fecha final -->
                <div class="col-4">
                  <label class="form-label small fw-semibold mb-1" for="fecha_final">
                    Fecha final
                  </label>

                  <div class="input-group input-group-sm input-group-merge">
                    <span class="input-group-text">
                      <i class="fa-regular fa-calendar-check"></i>
                    </span>

                    <input
                      type="date"
                      class="form-control form-control-sm"
                      id="fecha_final"
                      aria-describedby="fecha_final" />
                  </div>
                </div>

                <!-- Botón -->
                <div class="col-4">
                  <button
                    type="button"
                    class="btn btn-sm btn-primary w-100"
                    id="buscar-temporal">
                    <i class="fa-solid fa-magnifying-glass me-1"></i>
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
  </div>
</div>
<!-- Modal modalAdd -->
<div class="modal fade" id="modalAdd" aria-hidden="true" data-bs-backdrop="static">
  <form id="FormDetalle" class="needs-validation form-basico" autocomplete="off" novalidate>
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
      <div class="modal-content">

        <div class="modal-header">
          <h5 class="modal-title">Agregar referencia</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body modal-add-body">

          <input type="hidden" id="costo_unitario" value="0" readonly>
          <input type="hidden" id="id_detalle" value="0" readonly>
          <input type="hidden" id="id_referencia" name="id_referencia" value="0" readonly>

          <div class="row g-3">

            <!-- Referencia seleccionada -->
            <div class="col-md-8">
              <div class="card h-100 border-primary-subtle shadow-sm">
                <div class="card-header py-2 text-primary form-label fw-semibold">
                  Referencia seleccionada
                </div>

                <div class="card-body">
                  <div class="row g-2 align-items-end">

                    <div class="col-md-3">
                      <label for="codigo" class="form-label">Código</label>
                      <input type="text"
                        class="form-control form-control-sm bg-light"
                        id="codigo"
                        required
                        readonly>
                    </div>

                    <div class="col-md-9">
                      <label for="descripcion_larga" class="form-label">Descripción</label>
                      <input type="text"
                        class="form-control form-control-sm bg-light"
                        id="descripcion_larga"
                        readonly>

                      <input type="hidden"
                        class="form-control"
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

            <!-- Impuestos -->
            <div class="col-md-4">
              <div class="card h-100 border-primary-subtle shadow-sm">

                <div class="card-header py-2 text-primary form-label fw-semibold">
                  Impuestos
                </div>

                <div class="card-body">
                  <div class="row g-2">

                    <div class="col-6">
                      <label for="iva" class="form-label">IVA</label>
                      <input type="text"
                        class="form-control form-control-sm bg-light"
                        tabindex="-1"
                        id="iva"
                        name="pcj_iva"
                        readonly>
                    </div>

                    <div class="col-6">
                      <label for="impoconsumo" class="form-label">Impoconsumo</label>
                      <input type="text"
                        class="form-control form-control-sm bg-light"
                        tabindex="-1"
                        id="impoconsumo"
                        name="impoconsumo"
                        readonly>
                    </div>

                    <div class="col-6">
                      <label for="ibua" class="form-label">Ibua</label>
                      <input type="text"
                        class="form-control form-control-sm format-num bg-light"
                        tabindex="-1"
                        id="ibua"
                        name="ibua"
                        readonly>
                    </div>

                    <div class="col-6">
                      <label for="icui" class="form-label">Icui</label>
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

            <!-- Datos del ingreso -->
            <div class="col-md-8">
              <div class="card h-100 border-primary-subtle shadow-sm">

                <div class="card-header py-2 text-primary form-label fw-semibold">
                  Datos del ingreso
                  <!-- Badge o Label donde se reflejará el estado -->
                  <div class="text-end">
                    <span id="badge_variacion_costo" class="badge bg-secondary">Sin cambios</span>
                  </div>
                </div>

                <div class="card-body">

                  <div class="row g-3">
                    <!-- Cantidad -->
                    <div class="col-md-2">
                      <label for="cantidad" class="form-label">Cantidad</label>

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
                      <label for="costo" class="form-label">Costo ($)</label>

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
                      <label for="pcj_descuento" class="form-label">Descuento (%)</label>

                      <input type="text"
                        class="form-control form-control-sm format-num calculo-costo"
                        tabindex="3"
                        id="pcj_descuento"
                        name="pcj_descuento"
                        value="0">
                    </div>

                    <!-- Lote -->
                    <div class="col-md-3 maneja-fecha-lotes">
                      <label for="lote" class="form-label">Lote</label>

                      <input type="hidden" readonly id="tmp_">

                      <input id="id_lote"
                        name="id_lote"
                        type="hidden"
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
                    <div class="col-md-3 maneja-fecha-lotes">
                      <label for="vmcto" class="form-label">Vencimiento</label>

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
                      <label for="unidad" class="form-label">Unidad</label>

                      <select class="form-select form-select-sm calculo-costo"
                        tabindex="6"
                        id="unidad"
                        required
                        multiple
                        style="height: 92px;">
                      </select>

                      <div class="invalid-feedback">
                        Debe seleccionar una unidad de conversión
                      </div>
                    </div>

                  </div>
                </div>
              </div>
            </div>

            <!-- Resumen de datos -->
            <div class="col-md-4">
              <div class="card h-100 border-primary-subtle shadow-sm">

                <div class="card-header py-2 text-primary form-label fw-semibold">
                  Resumen de datos
                </div>

                <div class="card-body">
                  <div class="row g-2">

                    <div class="col-md-6">
                      <label for="costo_neto" class="form-label">Costo Neto</label>

                      <input type="text"
                        class="form-control form-control-sm format-num bg-light"
                        id="costo_neto"
                        tabindex="-1"
                        readonly>
                    </div>

                    <div class="col-md-6">
                      <label for="costo_total" class="form-label">Costo Total</label>

                      <input type="text"
                        class="form-control form-control-sm format-num bg-success-subtle"
                        id="costo_total"
                        tabindex="-1"
                        readonly>
                    </div>

                    <div class="col-md-6">
                      <label for="promedio" class="form-label">Costo promedio</label>

                      <input type="text"
                        class="form-control form-control-sm format-num bg-light"
                        id="promedio"
                        tabindex="-1"
                        readonly>
                    </div>

                    <div class="col-md-6">
                      <label for="promedio_calculado" class="form-label">Promedio calculado</label>

                      <input type="text"
                        class="form-control form-control-sm format-num bg-success-subtle"
                        id="promedio_calculado"
                        tabindex="-1"
                        readonly>
                    </div>

                    <div class="col-md-6">
                      <label for="ultimo_costo" class="form-label">Último costo</label>

                      <input type="text"
                        class="form-control form-control-sm format-num bg-light"
                        id="ultimo_costo"
                        tabindex="-1"
                        readonly>
                    </div>

                    <div class="col-md-6">
                      <label for="stock" class="form-label">Stock</label>

                      <input type="text"
                        class="form-control form-control-sm bg-light"
                        aria-describedby="stock"
                        id="stock"
                        tabindex="-1"
                        readonly>
                    </div>

                  </div>
                </div>
              </div>
            </div>

            <!-- Listas de precios -->
            <div class="col-12">
              <div class="card border-primary-subtle shadow-sm h-100">

                <div class="card-header py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                  <span class="text-primary fw-semibold form-label">
                    Listas de precios de la referencia
                  </span>

                  <div class="small text-muted border rounded p-2">
                    Edite el valor deseado. Al agregar la referencia se guarda
                    temporalmente en recepción.
                  </div>
                </div>

                <div class="card-body p-2">
                  <div class="table-responsive">

                    <table class="table table-sm table-hover align-middle mb-0"
                      id="tbListasModal">

                      <thead class="table-primary">
                        <tr>
                          <th>Lista</th>
                          <th>Tipo</th>
                          <th>Conversión 1</th>
                          <th>Conversión 2</th>
                          <th>Conversión 3</th>
                          <th>Conversión 4</th>
                        </tr>
                      </thead>

                      <tbody id="tbodyListasModal">
                        <tr>
                          <td colspan="6" class="text-center text-muted">
                            Cargue una referencia para visualizar sus listas de precios
                          </td>
                        </tr>
                      </tbody>

                    </table>

                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

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

<!-- Modal container-->
<div id="modal-container"></div>
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
<!-- Modal creacion referencia -->
<div class="modal fade" id="modalCrearReferencia" tabindex="-1" aria-labelledby="modalCrearReferenciaLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalCrearReferenciaLabel">CREACIÓN BÁSICA DE REFERENCIA</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="frmReferencia">
          <div class="card mb-4">

            <div class="card-header">

              <div class="d-flex align-items-start gap-2">

                <i class="fa-solid fa-circle-info text-primary mt-1"></i>

                <div>
                  <h6 class="mb-1 text-primary">
                    Información
                  </h6>

                  <small class="text-muted">
                    En este formulario solo se registran los datos básicos de la referencia.
                    La información adicional, como listas de precios, códigos EAN, conversiones
                    y otras configuraciones, podrá completarse posteriormente desde el módulo de Referencias.
                  </small>
                </div>

              </div>

            </div>

            <div class="card-body">

              <!-- Botones -->
              <div class="d-flex justify-content-end flex-wrap gap-2 mb-4">
                <button type="reset" class="btn btn-sm btn-outline-secondary" id="btnLimpiarReferencia">
                  <i class="fa-regular fa-file"></i> Nuevo
                </button>

                <button type="button" class="btn btn-sm btn-outline-primary" id="btnGuardarReferencia">
                  <i class="fa-regular fa-floppy-disk"></i> Guardar
                </button>
              </div>

              <!-- Tipo y códigos -->
              <div class="row g-3">

                <div class="col-md-4">
                  <label for="slcTipoReferencia" class="form-label">Tipo</label>
                  <select id="slcTipoReferencia" name="tipo" class="form-select form-select-sm">
                    <option value="P">PRODUCTO</option>
                    <option value="S">SERVICIO</option>
                  </select>
                </div>

                <div class="col-md-4">
                  <label for="ultimo_codigo" class="form-label">Último código</label>
                  <input type="text"
                    class="form-control form-control-sm"
                    id="ultimo_codigo"
                    readonly>
                </div>

                <div class="col-md-4">
                  <label for="txtCodigo" class="form-label">Código nuevo</label>
                  <input type="text"
                    class="form-control form-control-sm"
                    id="txtCodigo"
                    name="codigo"
                    maxlength="20"
                    placeholder="Código del nuevo producto"
                    autocomplete="off"
                    required>
                </div>

              </div>

              <!-- Descripción -->
              <div class="row mt-3">
                <div class="col-md-12">
                  <label for="txtDescripcion" class="form-label">Descripción</label>
                  <input type="text"
                    class="form-control form-control-sm"
                    id="txtDescripcion"
                    name="descripcion"
                    placeholder="Descripción del producto o servicio"
                    autocomplete="off"
                    required>
                </div>

              </div>

              <!-- Datos adicionales -->
              <div class="row mt-3 g-3">

                <div class="col-lg-3 col-md-6">
                  <label for="txtCostoUnd" class="form-label">Costo (UND)</label>
                  <input type="text"
                    class="form-control form-control-sm moneda"
                    id="txtCostoUnd"
                    name="costo_unitario"
                    maxlength="20"
                    placeholder="0.00"
                    autocomplete="off"
                    required>
                </div>

                <div class="col-lg-3 col-md-6">
                  <label for="slcMarcas" class="form-label">Proveedor / Marca</label>
                  <select class="form-select form-select-sm"
                    id="slcMarcas"
                    name="id_marca">
                  </select>
                </div>

                <div class="col-lg-3 col-md-6">
                  <label for="slcPerfilContable" class="form-label">Perfil contable</label>

                  <select class="form-select form-select-sm"
                    id="slcPerfilContable"
                    name="perfil_contable">
                  </select>
                </div>

                <div class="col-lg-3 col-md-6">
                  <label for="slcManejaLote" class="form-label">Maneja lote</label>

                  <select class="form-select form-select-sm"
                    id="slcManejaLote"
                    name="maneja_lote">
                    <option value="S">Sí</option>
                    <option value="N">No</option>
                  </select>
                </div>

              </div>

            </div>

          </div>
        </form>
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
<!-- Modal Video modalCargaMasiva -->
<div class="modal fade" id="modalCargaMasiva" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
    <div class="modal-content">
      <!-- HEADER -->
      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-0">
            <i class="fa-solid fa-file-arrow-up me-2"></i>
            Carga masiva de ingreso de mercancías
          </h5>
          <small class="text-muted">
            Puedes cargar un archivo plano con la estructura
            definida por ISA o importar directamente una
            factura electrónica XML de la DIAN.
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
        <!-- TIPO DE IMPORTACIÓN -->
        <div class="card border-0 shadow-sm rounded-4 mb-3">
          <div class="card-body p-4">
            <div class="row g-3">
              <!-- PLANO -->
              <div class="col-md-2">
                <input type="radio"
                  class="btn-check"
                  name="tipoCargaMasiva"
                  id="tipoCargaPlano"
                  value="PLANO"
                  autocomplete="off"
                  checked>
                <label class="btn btn-outline-primary rounded-3 w-100 h-100 text-start p-3"
                  for="tipoCargaPlano">
                  <div class="d-flex align-items-center gap-3">
                    <i class="fa-solid fa-file-excel fa-xl"></i>
                    <div>
                      <div class="fw-semibold">
                        Archivo plano
                      </div>
                      <div class="small text-body-secondary">
                        CSV, TXT, XLS o XLSX
                      </div>
                    </div>
                  </div>
                </label>
              </div>
              <!-- XML -->
              <div class="col-md-2">
                <input type="radio"
                  class="btn-check"
                  name="tipoCargaMasiva"
                  id="tipoCargaXML"
                  value="XML"
                  autocomplete="off">
                <label class="btn btn-outline-success rounded-3 w-100 h-100 text-start p-3" for="tipoCargaXML">
                  <div class="d-flex align-items-center gap-3">
                    <i class="fa-solid fa-file-code fa-xl"></i>
                    <div>
                      <div class="fw-semibold">
                        Factura electrónica
                      </div>
                      <div class="small text-body-secondary">
                        XML DIAN / UBL
                      </div>
                    </div>
                  </div>
                </label>
              </div>
              <div class="col-md-8">
                <!-- INFORMACIÓN PLANO -->
                <div id="infoCargaPlano" class="alert alert-primary border-0 rounded-3 mb-0 h-100">
                  <div class="d-flex align-items-start gap-2">
                    <i class="fa-solid fa-circle-info mt-1"></i>
                    <div>
                      <div class="fw-semibold mb-1">
                        Formato plano
                      </div>
                      <div>
                        ISA extraerá automáticamente:
                      </div>
                      <div class="d-flex flex-wrap gap-1 mt-2">
                        <span class="badge bg-primary rounded-pill fw-normal">Factura</span>
                        <span class="badge bg-primary rounded-pill fw-normal">Código</span>
                        <span class="badge bg-primary rounded-pill fw-normal">Descripción</span>
                        <span class="badge bg-primary rounded-pill fw-normal">NIT</span>
                        <span class="badge bg-primary rounded-pill fw-normal">Cantidad</span>
                        <span class="badge bg-primary rounded-pill fw-normal">% Descuento</span>
                        <span class="badge bg-primary rounded-pill fw-normal">Valor</span>
                        <span class="badge bg-primary rounded-pill fw-normal">Vencimiento</span>
                        <span class="badge bg-primary rounded-pill fw-normal">Lote</span>
                      </div>
                      <div class="small text-body-secondary mt-2">
                        Los datos que no sean enviados por el proveedor
                        podrán completarse durante la homologación.
                      </div>
                    </div>
                  </div>
                </div>
                <!-- INFORMACIÓN XML -->
                <div id="infoCargaXML" class="alert alert-success border-0 rounded-3 mb-0 h-100" style="display:none;">
                  <div class="d-flex align-items-start gap-2">
                    <i class="fa-solid fa-circle-info mt-1"></i>
                    <div>
                      <div class="fw-semibold mb-1">
                        Factura electrónica DIAN
                      </div>
                      <div>
                        ISA extraerá automáticamente:
                      </div>
                      <div class="d-flex flex-wrap gap-1 mt-2">
                        <span class="badge bg-success rounded-pill fw-normal">NIT</span>
                        <span class="badge bg-success rounded-pill fw-normal">Factura</span>
                        <span class="badge bg-success rounded-pill fw-normal">Producto</span>
                        <span class="badge bg-success rounded-pill fw-normal">Cantidad</span>
                        <span class="badge bg-success rounded-pill fw-normal">Costo</span>
                        <span class="badge bg-success rounded-pill fw-normal">Descuento</span>
                        <span class="badge bg-success rounded-pill fw-normal">Lote</span>
                        <span class="badge bg-success rounded-pill fw-normal">Vencimiento</span>
                      </div>
                      <div class="small text-body-secondary mt-2">
                        Los datos que no sean enviados por el proveedor
                        podrán completarse durante la homologación.
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ARCHIVO -->
        <div class="card border-0 shadow-sm rounded-4 mb-3">
          <div class="card-body p-4">
            <div class="row g-3 align-items-end">
              <div class="col-md-8">
                <label class="form-label fw-semibold small" for="inputArchivoMasivo">
                  Archivo (.csv, .xlsx, .xls)
                </label>
                <input type="file" id="inputArchivoMasivo" class="form-control form-control-sm" accept=".txt,.csv,.xlsx,.xls">
              </div>
              <div class="col-md-4">

                <button type="button" class="btn btn-sm btn-outline-primary" id="btnPrevisualizarMasivo">
                  <i class="fa-solid fa-eye me-1"></i> Previsualizar
                </button>
                <button type="button" class="btn btn-sm btn-outline-success" id="btnInsertarMasivo">
                  <i class="fa-solid fa-cloud-arrow-up me-1"></i> Insertar TMP
                </button>
                <button type="button" class="btn btn-sm  btn-outline-danger" id="btnLimpiarMasivo" title="Limpiar">
                  <i class="fa-solid fa-trash"></i> Limpiar
                </button>

              </div>
            </div>
          </div>
        </div>

        <!-- INDICADORES -->
        <div class="row g-2 mb-3">
          <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-body p-2 d-flex align-items-center gap-2">
                <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                  style="width:32px;height:32px;">
                  <i class="fa-solid fa-list-ol small"></i>
                </div>
                <div class="lh-sm">
                  <div class="small text-uppercase text-muted fw-semibold"
                    style="font-size:.65rem;">
                    Total leídas
                  </div>
                  <div class="fw-bold"
                    id="lblTotalMasivo">
                    0
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-body p-2 d-flex align-items-center gap-2">
                <div class="bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                  style="width:32px;height:32px;">
                  <i class="fa-solid fa-circle-check small"></i>
                </div>
                <div class="lh-sm">
                  <div class="small text-uppercase text-muted fw-semibold"
                    style="font-size:.65rem;">
                    Válidas
                  </div>
                  <div class="fw-bold"
                    id="lblValidasMasivo">
                    0
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-body p-2 d-flex align-items-center gap-2">
                <div class="bg-danger-subtle text-danger rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                  style="width:32px;height:32px;">
                  <i class="fa-solid fa-circle-xmark small"></i>
                </div>
                <div class="lh-sm">
                  <div class="small text-uppercase text-muted fw-semibold"
                    style="font-size:.65rem;">
                    Inválidas
                  </div>
                  <div class="fw-bold"
                    id="lblInvalidasMasivo">
                    0
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-body p-2 d-flex align-items-center gap-2">
                <div class="bg-warning-subtle text-warning-emphasis rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                  style="width:32px;height:32px;">
                  <i class="fa-solid fa-database small"></i>
                </div>
                <div class="lh-sm">
                  <div class="small text-uppercase text-muted fw-semibold"
                    style="font-size:.65rem;">
                    Total en TMP
                  </div>
                  <div class="fw-bold"
                    id="lblTotal">
                    0
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- ERRORES -->
        <div id="errores"
          style="display:none"
          class="alert alert-danger shadow-sm"
          role="alert">
          <h6 class="alert-heading mb-1">
            <i class="fa-solid fa-triangle-exclamation"></i>
            Errores de validación
          </h6>
        </div>

        <!-- PREVIEW -->
        <div class="card shadow-sm">
          <div class="table-responsive"
            style="max-height:55vh;">
            <table class="table table-sm table-striped table-hover mb-0"
              id="tblPreviewMasivo">
              <thead class="table-primary text-nowrap sticky-top">
                <tr>
                  <th>#</th>
                  <th>Estado</th>
                  <th>Acción</th>
                  <th>NIT</th>
                  <th>Factura</th>
                  <th>Código proveedor</th>
                  <th>Referencia</th>
                  <th>Código sistema</th>
                  <th>Ref. sistema</th>
                  <th class="d-none">Laboratorio</th>
                  <th>Cantidad</th>
                  <th>Costo</th>
                  <th>Descuento</th>
                  <th>Iva</th>
                  <th>Costo neto</th>
                  <th>Und. compras</th>
                  <th>Conv</th>
                  <th>Cant real.</th>
                  <th>Costo real</th>
                  <th>Total</th>
                  <th>Fecha</th>
                  <th>Lote</th>
                </tr>
              </thead>
              <tbody id="tbodyPreviewMasivo">
                <tr>
                  <td colspan="22"
                    class="text-center text-muted py-4">
                    <i class="fa-solid fa-inbox fa-lg d-block mb-2"></i>
                    Sin datos para previsualizar
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <!-- FOOTER -->
      <div class="modal-footer">
        <small class="text-muted me-auto">
          <i class="fa-solid fa-circle-info"></i>
          Revisa las filas marcadas como inválidas antes de insertar
          en TMP.
        </small>
        <button type="button"
          class="btn btn-outline-secondary btn-sm"
          data-bs-dismiss="modal">
          Cerrar
        </button>
      </div>
    </div>
  </div>
</div>
<!-- Modal Video modalBusquedaRef -->
<div class="modal fade" id="modalBusquedaRef" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Búsqueda de referencias</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row mb-2">
          <div class="col-md-6">
            <div class="input-group input-group-sm input-group-merge"> <span class="input-group-text"><i class="bx bx-search"></i></span>
              <input type="text" id="txtBusquedaReferencia" class="form-control form-control-sm" placeholder="Código, descripción, descripción larga" aria-label="Buscar..." autocomplete="off" />
            </div>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-striped table-hover" id="tblBusquedaReferencia">
            <thead class="table-primary">
              <tr>
                <th>Código</th>
                <th>Descripción</th>
                <th>Descripción larga</th>
                <th>Laboratorio</th>
                <th>Unidad de compra</th>
                <th>Stock</th>
                <th>Costo</th>
                <th>Acción</th>
              </tr>
            </thead>
            <tbody id="tbodyBusquedaReferencia">
              <tr>
                <td colspan="9" class="text-center text-muted">Sin datos para mostrar</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer">

      </div>
    </div>
  </div>
</div>

<?php

$scriptsPagina = [
  '../controllers/IngresosMercancias.js?' . rand()
];

include '../dependencies/footer.php';

?>