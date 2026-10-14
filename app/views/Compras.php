<?php
session_start();

$titulo = 'Compras';
include '../dependencies/header.php';
?>
<input type="hidden" id="id_usuario" value="<?php echo $_SESSION['Ses_id']; ?>">
<input type="hidden" id="id_rol" value="<?php echo $_SESSION['Ses_roles_id']; ?>">
<input type="hidden" id="id_bodega_session" value="<?php echo $_SESSION['Ses_id_bodega']; ?>">

<div class="modal fade" id="modalCsv" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-xl " role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="">Cargar orden de compra por archivo plano</h5>
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
                <strong>Bodega,Código, Costo, Cantidad, nit proveedor</strong>.
              </li>
              <li>
                <strong>nit proveedor</strong> es obligatorio. El proveedor debe estar creado en el sistema.
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

<div class="card" style="height: auto;">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Inventarios /</span> Ordenes de compra</h5>
    <div class="nav-align-top ">
      <ul class="nav nav-tabs" role="tablist" id="tabs">
        <li class="nav-item">
          <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-tercero" aria-controls="navs-tercero" aria-selected="true">
            <i class="fa-solid fa-bag-shopping"></i> &nbsp;Crear orden 
          </button>
        </li>
        <li class="nav-item">
          <button type="button" class="nav-link disabled" role="tab" data-bs-toggle="tab" data-bs-target="#navs-productos" aria-controls="navs-creacion" aria-selected="false"> 
            <i class="fa-regular fa-pen-to-square"></i> &nbsp;Creación 
          </button>
        </li>
        <li class="nav-item">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-temporal" aria-controls="navs-temporal" aria-selected="false"> <i class="fa-regular fa-clock"></i> &nbsp;Mis ordenes </button>
        </li>
        <li class="nav-item" style="flex:1;"></li>
        <li class="nav-item border-danger">
          <button type="button" class="btn btn-outline-success btn-sm" id="btn-csv"><i class="fa-solid fa-file-excel"></i> Subir Excel</button>
        </li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane fade show active" id="navs-tercero" role="tabpanel">
          <div id="inf_add">
            <div class="row">
              <div class="col-md-8">
                <div class="card h-100">
                  <h5 class="card-header text-primary form-label mb-3">Tercero</h5>
                  <div class="card-body">
                    <label for="buscar_ter" class="form-label">BÚSQUEDA DE TERCERO</label>
                    <div class="input-group input-group-sm input-group-merge">
                      <span class="input-group-text"><i class="bx bx-search"></i></span>
                      <input type="text" class="form-control" placeholder="Búsqueda de tercero..." aria-label="Búsqueda de tercero..." aria-describedby="basic-addon-search" id="buscar_ter">
                    </div>

                    <div class="">
                      <label for="nit" class="form-label">NIT</label>
                      <input type="text" class="form-control form-control-sm" name="nit" id="nit" maxlength="20" placeholder="NIT" autocomplete="off" onKeyPress="return vnumeros(event)" readonly>
                    </div>
                    <div class="">
                      <label for="tratamiento" class="form-label">TRATAMIENTO</label>
                      <input type="text" class="form-control form-control-sm text-uppercase" name="tratamiento" id="tratamiento" autocomplete="off" readonly placeholder="Tratamiento">
                    </div>
                    <div class="">
                      <label for="razon_comercial" class="form-label">RAZÓN COMERCIAL</label>
                      <input type="text" class="form-control form-control-sm text-uppercase" name="razon_comercial" id="razon_comercial" autocomplete="off" placeholder="Razón social/comercial" readonly>
                    </div>
                    <div class="">
                      <label for="direcciones" class="form-label">DIRECCIÓN</label>
                      <select class="form-select form-select-sm" id="direcciones">
                      </select>
                    </div>
                    <!-- Campo oculto para mantener compatibilidad -->
                    <input type="hidden" id="id_tercero" name="id_tercero" placeholder="Código" autocomplete="off" readonly>
                  </div>
                </div>
              </div>

              <!-- COLUMNA DERECHA: CONDICIONES -->
              <div class="col-md-4">
                <div class="card h-100">
                  <h5 class="card-header text-primary form-label">Condiciones</h5>
                  <div class="card-body">
                    <div class="">
                      <label for="bodegas" class="form-label">BODEGA</label>
                      <select class="form-select form-select-sm" id="bodegas" name="id_bodega">
                      </select>
                    </div>
                    <div class="">
                      <label for="id_tipo_documento" class="form-label">TIPO DE DOCUMENTO</label>
                      <select class="form-select form-select-sm" id="id_tipo_documento" name="id_tipo_documento">
                      </select>
                    </div>
                    <div class="">
                      <label for="id_condicion" class="form-label">CONDICIÓN PAGO PROVEEDOR</label>
                      <select class="form-select form-select-sm" id="id_condicion" name="id_condicion">
                      </select>
                    </div>
                    <div class="">
                      <label for="email_fe" class="form-label">EMAIL</label>
                      <input type="text" class="form-control form-control-sm text-uppercase" name="email_fe" id="email_fe" autocomplete="off" placeholder="Email" readonly>
                    </div>
                    <div class="">
                      <label for="edi" class="form-label">EDI</label>
                      <div class="input-group">
                        <input type="text" class="form-control form-control-sm text-uppercase" name="edi" id="edi" readonly>
                        <input type="text" class="form-control form-control-sm text-uppercase" name="ean_edi" id="ean_edi" readonly>
                      </div>
                    </div>
                    <div class="">
                      <label for="notas" class="form-label">NOTAS</label>
                      <textarea class="form-control" id="notas" name="notas" rows="3"></textarea>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
        <!--tab1 fin-->
        <div class="tab-pane fade" id="navs-productos" role="tabpanel" style="height: calc(67vh);">
          <div class=" mt-4">
            <div class="card-body">
              <div class="row">
                <div class="col-md-7">
                  <button class="btn btn-outline-primary btn-sm" id="btnGuardar"><i class="fa-regular fa-floppy-disk"></i>&nbsp;Guardar</button>
                  <button class="btn btn-outline-secondary btn-sm" id="btnLimpiar"><i class="fa-regular fa-file"></i>&nbsp;Nuevo</button>
                </div>
                <div class="col-md-5">
                  <div class="input-group input-group-sm"> <span class="input-group-text text-primary">Número</span>
                    <input type="text" class="form-control form-control-sm" id="id_pedido" readonly>
                    <span class="input-group-text text-primary">Total</span>
                    <input type="text" class="form-control form-control-sm" id="n_total" readonly>
                  </div>
                </div>
              </div>
              <div class="input-group input-group-sm input-group-merge mt-4"> <span class="input-group-text"><i class="bx bx-search"></i></span>
                <input type="text" class="form-control" placeholder="Búsqueda de referencia..." aria-label="Búsqueda de referencia..." aria-describedby="basic-addon-search" id="buscar_ref" autocomplete="off" readonly>
              </div>
              <div id="dvReferencias" class="mt-3 table-responsive text-nowrap" style="height: calc(70vh);">
                <table class="table table-sm table-hover table-striped" id="items_">
                  <thead class="table-primary">
                    <tr class="text-primary">
                      <th>Código</th>
                      <th>Descripción</th>
                      <th>Costo</th>
                      <th>Costo_Neto</th>
                      <th>IVA(%)</th>
                      <th>DTO(%)</th>
                      <th>Cantidad</th>
                      <th>Unidad</th>
                      <th>Can_und</th>
                      <th>Costo_Total</th>
                      <th>Eliminar</th>
                      <th>Editar</th>
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
          <div class="card mb-4">
            <div class="card-body">
              <div class="row">
                <div class="col-4">
                  <div class="mb-3">
                    <label class="form-label" for="">Fecha inicial</label>
                    <div class="input-group input-group-merge"> <span class="input-group-text"><i class="fa-regular fa-calendar-check"></i></span>
                      <input type="date" class="form-control form-control-sm" id="fecha_inicial" aria-describedby="fecha_inicial" />
                    </div>
                  </div>
                </div>
                <div class="col-4">
                  <div class="mb-3">
                    <label class="form-label" for="">Fecha final</label>
                    <div class="input-group input-group-merge"> <span class="input-group-text "><i class="fa-regular fa-calendar-check"></i></span>
                      <input type="date" class="form-control form-control-sm" id="fecha_final" aria-describedby="fecha_final" />
                    </div>
                  </div>
                </div>
                <div class="col-4">
                  <div class="mb-3">
                    <label class="form-label" for="">estados</label>
                    </br>
                    <div class="btn-group" role="group" aria-label="Second group" id="btnGroupEstado">
                      <button class="btn btn-outline-warning btn-sm active" data-estado="T"><i class="fa-regular fa-clock"></i>&nbsp;Temporal</button>
                      <button class="btn btn-outline-success btn-sm" data-estado="P"><i class="fa-solid fa-check-double"></i>&nbsp;Pedido</button>
                      <button class="btn btn-outline-info btn-sm" data-estado="I"><i class="fa-solid fa-bolt-lightning"></i>&nbsp;Ingresado</button>
                    </div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col">
                  <button class="btn btn-sm btn-outline-primary" id="btnBuscarTemporal"><i class="fa-solid fa-magnifying-glass"></i>&nbsp;Consultar</button>
                </div>
              </div>
              <div id="temporales"></div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal fade" id="modalAdd" aria-hidden="true" tabindex="-1" data-bs-backdrop="static">
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
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-floating">
                      <input type="text" class="form-control" id="descripcion_larga" placeholder="" readonly>
                      <label for="floatingInput">Descripción larga</label>
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
                  </div>
                  <div class="col-md-3">
                    <label for="unidad" class="form-label">Unidad</label>
                    <select class="form-select form-select-sm" tabindex="2" id="unidad" required multiple style="height: 60px;">
                    </select>
                  </div>
                  <div class="col-md-3">
                    <label for="iva" class="form-label">IVA</label>
                    <input type="text" class="form-control form-control-sm" id="iva" name="pcj_iva" readonly />
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-3">
                    <label for="costo_neto" class="form-label">Costo Neto</label>
                    <input type="text" class="form-control form-control-sm format-num" id="costo_neto" readonly>
                  </div>
                  <div class="col-md-3">
                    <label for="stock" class="form-label">stock</label>
                    <div class="input-group"> <span class="input-group-text"><i class="fa-solid fa-truck-ramp-box"></i></span>
                      <input type="text" class="form-control form-control-sm format-num" aria-describedby="stock" id="stock" readonly />
                    </div>
                  </div>
                  <div class="col-md-3">
                    <label for="stock" class="form-label">Descuento %</label>
                    <input type="text" class="form-control form-control-sm format-num" tabindex="3" id="pcj_descuento" name="pcj_descuento" />
                  </div>
                  <div class="col-md-3">
                    <label for="cantidad" class="form-label">cantidad</label>
                    <input type="number" class="form-control form-control-sm" tabindex="4" name="cantidad" min="1" id="cantidad" required onKeyPress="return vnumeros(event)" autocomplete="off">
                    <div class="invalid-feedback"> La cantidad debe ser mayor a 0 </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-3">
                    <label for="ultimo_costo" class="form-label">Ultimo costo</label>
                    <input type="text" class="form-control form-control-sm format-num" id="ultimo_costo" readonly>
                  </div>
                  <div class="col-md-9">
                    <label for="costo_total" class="form-label">Costo total</label>
                    <input type="text" class="form-control form-control-sm format-num" id="costo_total" readonly>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="submit" class="btn btn-sm btn-outline-primary" tabindex="5" id="btnAddReferencia"> Agregar </button>
                <button type="reset" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal"> Cerrar </button>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div id="modal-container"></div>
    </div>
  </div>
<?php

$scriptsPagina = [
  '../controllers/Compras.js?' . rand()
];
include '../dependencies/footer.php';

?>
