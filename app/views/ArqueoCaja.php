<?php
session_start();

$titulo = 'Traslados entre bodegas';
include '../dependencies/header.php';
?>
<input type="number" id="bodega_session" style="display: none;" value="<?php echo  $_SESSION["Ses_id_bodega"]; ?>" readonly />
<div class="card">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">POS /</span> Arqueo de caja</h5>
    <div class="nav-align-top ">
      <ul class="nav nav-tabs" role="tablist" id="tabs">
        <li class="nav-item">
          <button
            type="button"
            class="nav-link active"
            role="tab"
            data-bs-toggle="tab"
            data-bs-target="#navs-creacion"
            aria-controls="navs-creacion"
            aria-selected="true">
            <i class="fa-solid fa-file-circle-plus"></i> &nbsp;Proceso
          </button>
        </li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane fade show active" id="navs-creacion" role="tabpanel">
          <form id="FormCrear" class="needs-validation  form-basico" novalidate>
            <div class="row p-2 d-flex d-align-items-center  justify-content-center fle-column">
              <div class="col-md-5 filtros">
                <div class="mb-1" bis_skin_checked="1">
                  <label for="bodega" class="form-label">Almacen/Bodega</label>
                  <div bis_skin_checked="1">
                    <select class="form-select form-select-sm" id="bodegas"></select>
                  </div>
                  <div bis_skin_checked="1">
                    <label for="fecha_inicial" class="form-label">Fecha inicial</label>
                    <input type="date" class="form-control form-control-sm valores" name="fecha_inicial" id="fecha_inicial" autocomplete="off" required />

                  </div>
                  <div bis_skin_checked="1">
                    <label for="fecha_final" class="form-label">Fecha final</label>
                    <input type="date" class="form-control form-control-sm valores" name="fecha_final" id="fecha_final" autocomplete="off" required />
                  </div>
                  <div>
                    <label>Usuario</label>
                    <select class="form-control form-control-sm" id="usuario_caja"></select>
                  </div>
                  <div bis_skin_checked="1" class="mt-3">
                    <button class="btn btn-sm btn-success guardar" id="generar_comprobante" type="button"> <i class="fa-solid fa-magnifying-glass-dollar"></i> Generar comprobante</button>
                    <button class="btn btn-sm btn-primary" type="button"> <i class="fa-solid fa-file"></i> Nuevo</button>
                  </div>
                </div>
              </div>
              <div class="col-md-7">
                <h4 class="text-center">Comprobante de arqueo de caja</h4>
                <div class="row">
                  <div class="col">
                    <table class="table">
                      <thead>
                        <tr>
                          <th>Nit </th>
                          <th id="nit"></th>
                        </tr>
                        <tr>
                          <th>Razon comercial </th>
                          <th id="razon_comercial"></th>
                        </tr>
                        <tr>
                          <th>Dirección </th>
                          <th id="direccion"></th>
                        </tr>
                        <tr>
                          <th>Fecha </th>
                          <th id="fecha"></th>
                        </tr>
                      </thead>
                    </table>
                  </div>
                </div>
                <div class="row">
                  <div class="col">
                    <div bis_skin_checked="1">
                      <input type="hidden" id="id_apertura" readonly value="0" />
                      <label for="usuario" class="form-label">Usuario</label>
                      <input type="text" class="form-control form-control-sm" value="<?php echo  $_SESSION["Ses_usuario"]; ?>" id="usuario" readonly />
                      <input type="hidden" value="<?php echo  $_SESSION["Ses_id"]; ?>" id="id_usuario" name="id_usuario" readonly />
                    </div>
                    <div bis_skin_checked="1">
                      <label for="" class="form-label">Rango de fechas</label>
                      <div class="row">
                        <div class="col" class="form-control form-control-sm valores p-3 border border-1 border-secondary" id="fecha_inicial_result">dd-mm-yyyy</div>
                        <div class="col" class="form-control form-control-sm valores p-3 border border-1 border-secondary" id="fecha_final_result">dd-mm-yyyy</div>
                      </div>
                    </div>
                    <div bis_skin_checked="1">

                      <div class="row mt-2">
                        <div class="col">
                          <label>Rango inicial</label>
                          <div class="col-md-6" class="form-control form-control-sm valores border border-1 border-secondary" name="doc_inicial" id="doc_inicial"></div>
                        </div>
                        <div class="col">
                          <label>Rango final</label>
                          <div class="col-md-6" class="form-control form-control-sm valores border border-1 border-secondary" name="doc_final" id="doc_final"></div>
                        </div>
                      </div>
                    </div>
                    <div class="row">
                      <div class="col">
                        <table class="table table-ms">
                          <tr>
                            <th class="text-center" colspan="2"><i class="fa-solid fa-circle-plus fa-21"></i> INGRESOS</th>
                          <tr>
                          <tr>
                            <td>Venta de contado</td>
                            <td id="aq_contado">$0</td>
                          </tr>
                          <tr>
                            <td>Venta credito</td>
                            <td id="aq_credito">$0</td>
                          </tr>
                          <tr>
                            <th class="text-center" colspan="2"><i class="fa-solid fa-circle-minus fa-1x"></i> EGRESOS</th>
                          <tr>
                          <tr>
                            <td>Devolucion contado</td>
                            <td id="aq_dv_contado" class="text-danger">$0</td>
                          </tr>
                          <tr>
                            <td>Devolucion credito</td>
                            <td id="aq_dv_credito" class="text-danger">$0</td>
                          </tr>
                          <tr>
                            <td>Gastos</td>
                            <td id="aq_egresos" class="text-danger">$0</td>
                          </tr>
                          <tr>
                            <th class="text-center" colspan="2">DETALLE DE PAGOS</th>
                          <tr>
                          <tr>
                            <td id="aq_pagos" colspan="2"></td>
                          </tr>
                        </table>
                        <div class="btn-finales">
                          <button class="btn btn-secondary btn-sm" id="imprimirDiv" type="button">Imprimir</button>
                        </div>
                        <!-- <button class="btn btn-primary btn-sm">Guardar/Imprimir</button> -->
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>
        <div class="tab-pane fade " id="navs-listado" role="tabpanel">

          <div class="row mb-3">
            <div class="col-6">
              <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-calendar-days"></i></span>
                <input
                  id="fecha_inicial"
                  type="date"
                  class="form-control"
                  aria-label="Username"
                  aria-describedby="basic-addon11" />
              </div>
            </div>
            <div class="col-6">
              <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-calendar-days"></i></span>
                <input
                  id="fecha_final"
                  type="date"
                  class="form-control"
                  aria-label="Username"
                  aria-describedby="basic-addon11" />
              </div>
            </div>
          </div>
          <div class="row mt-2">
            <div class="col">
              <button class="btn btn-outline-primary" id="buscar"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
            </div>
          </div>
          <div class="row mt-3">
            <div class="col-12">
              <div id="result"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php

$scriptsPagina = [
  '../controllers/ArqueoCaja.js?' . rand()
];

include '../dependencies/footer.php';

?>