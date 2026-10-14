  <?php
  session_start();

  $titulo = 'Referencias';
  $cssPagina = [
    'https://unpkg.com/dropzone@5/dist/min/dropzone.min.css'
  ];
  include '../dependencies/header.php';
  ?>
  <style>
    .swal2-container {
      z-index: 20000 !important;
      font-size: 1.5rem !important;
    }

    .precio-calc {
      border: none !important;
      background: transparent !important;
    }

    /* Estilo para el área de Dropzone */
    .dropzone {
      border: 2px dashed #007bff;
      padding: 20px;
      border-radius: 10px;
      background-color: #f8f9fa;
      text-align: center;
      font-family: Arial, sans-serif;
    }

    /* Mensaje predeterminado */
    .dropzone .dz-default {
      color: #6c757d;
      font-size: 18px;
      font-weight: bold;
    }

    /* Mensajes de error */
    .dropzone .dz-error-message {
      color: #dc3545;
      /* Rojo para los mensajes de error */
      font-size: 14px;
      font-weight: bold;
      margin-top: 10px;
    }

    /* Estilo para el botón de "Eliminar archivo" */
    .dropzone .dz-remove {
      color: #007bff;
      font-size: 14px;
      font-weight: bold;
      cursor: pointer;
      margin-top: 5px;
    }

    /* Estilo para el botón de "Cancelar carga" */
    .dropzone .dz-cancel {
      color: #007bff;
      font-size: 14px;
      font-weight: bold;
      cursor: pointer;
      margin-top: 5px;
    }

    /* Estilo para el archivo en progreso */
    .dropzone .dz-progress {
      background-color: #007bff;
      height: 5px;
    }

    /* Estilo para el área de carga cuando no se está arrastrando archivos */
    .dropzone.dz-drag-hover {
      border-color: #28a745;
      /* Verde cuando el usuario está arrastrando archivos */
      background-color: #e9f7ee;
    }

    /* Estilo para el texto cuando un archivo es demasiado grande */
    .dropzone .dz-error-message {
      font-size: 16px;
      font-weight: normal;
      color: #dc3545;
    }

    .dropzone .dz-preview .dz-image img {
      width: 100px;
      height: 100px;
    }

    .switch-activo .form-check-input {
      width: 3rem;
      height: 1.5rem;
      cursor: pointer;
      --bs-form-switch-bg: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3E%3Ccircle r='3' fill='%23ffffff'/%3E%3C/svg%3E");
      background-image: var(--bs-form-switch-bg);
      background-color: var(--bs-danger);
      border-color: var(--bs-danger);
    }

    .switch-activo .form-check-input:checked {
      background-image: var(--bs-form-switch-bg);
    }

    .switch-activo .form-check-input:focus {
      border-color: var(--bs-danger);
      box-shadow: 0 0 0 .25rem rgba(var(--bs-danger-rgb), .25);
    }

    .switch-activo .form-check-input:checked {
      background-color: var(--bs-success);
      border-color: var(--bs-success);
    }

    .switch-activo .form-check-input:checked:focus {
      border-color: var(--bs-success);
      box-shadow: 0 0 0 .25rem rgba(var(--bs-success-rgb), .25);
    }

    .switch-activo .estado-label {
      font-weight: 600;
      color: var(--bs-danger);
    }

    .switch-activo .estado-on {
      display: none;
    }

    .switch-activo .form-check-input:checked~.estado-label {
      color: var(--bs-success);
    }

    .switch-activo .form-check-input:checked~.estado-label .estado-off {
      display: none;
    }

    .switch-activo .form-check-input:checked~.estado-label .estado-on {
      display: inline;
    }

    #textPrecioSinImp,
    #textPrecioConImp,
    #textPrecioSinImp2,
    #textPrecioConImp2,
    #textPrecioSinImp3,
    #textPrecioConImp3,
    #textPrecioSinImp4,
    #textPrecioConImp4 {
      background-color: transparent !important;
      border: none !important;
      border-bottom: 1px solid #bebebe !important;
    }

    .input-sin-bordes {
      background-color: transparent !important;
      border: none !important;
      border-bottom: 1px solid #f6daff !important;
    }

    .ultimo-codigo {
      border: none !important;
      outline: none !important;
      box-shadow: none !important;
      background: transparent !important;
      padding: 0;
      margin: 0;
      width: 90px;
      color: #6c757d;
      font-weight: 600;
      font-size: inherit;
      cursor: default;
    }

    .ultimo-codigo:focus {
      border: none !important;
      outline: none !important;
      box-shadow: none !important;
    }

    .costos-resumen {
      display: flex !important;
      align-items: center;
      justify-content: flex-start;
      width: auto !important;
      gap: 8px;
    }

    .costo-item {
      display: flex;
      align-items: center;
      gap: 5px;
      white-space: nowrap;
    }

    .costo-separador {
      color: #adb5bd;
      margin: 0 4px;
    }

    .input-info {
      border: none !important;
      outline: none !important;
      box-shadow: none !important;
      background: transparent !important;
      padding: 0 !important;
      margin: 0 !important;
      width: 70px;
      color: #495057;
      font-weight: 600;
      font-size: inherit;
      cursor: default;
    }

    #resultado-inventario {
      max-height: 400px;
      overflow-y: auto;
      overflow-x: auto;
    }

    #resultado-inventario .table-responsive {
      overflow-x: visible;
    }

    #resultado-inventario #kardex {
      min-width: 1300px;
      margin-bottom: 0;
    }

    #resultado-inventario #kardex thead th {
      position: sticky;
      top: 0;
      z-index: 10;
      white-space: nowrap;
    }

    .cards-misma-altura {
      display: flex;
      align-items: stretch;
    }

    .cards-misma-altura>.col-md-8,
    .cards-misma-altura>.col-md-4 {
      display: flex;
    }

    .cards-misma-altura .card {
      width: 100%;
    }

    .cards-misma-altura .card-body {
      flex: 0 0 auto;
    }
  </style>

  <div class="card">
    <div class="card-body">
      <h4 class="fw-bold py-3 mb-2"><span class="text-muted fw-light">Maestra /</span> Referencias</h4>
      <div class="nav-align-top ">
        <ul class="nav nav-tabs" role="tablist" id="tabs">
          <li class="nav-item">
            <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-creacion" aria-controls="navs-creacion" aria-selected="true"> <i class="fa-solid fa-file-circle-plus"></i> &nbsp;Creación /Modificación </button>
          </li>
          <li class="nav-item">
            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-listado" aria-controls="navs-listado" aria-selected="false"> <i class="fa-solid fa-list-ol"></i> &nbsp;Listado </button>
          </li>
          <li class="nav-item">
            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-cargas" aria-controls="navs-cargas" aria-selected="false"> <i class="fa-regular fa-circle-up"></i> &nbsp;Cargas masivas </button>
          </li>
        </ul>
        <div class="tab-content">
          <div class="tab-pane fade show active" id="navs-creacion" role="tabpanel">
            <input type="hidden" id="idReferencia" name="idReferencia" value="0">
            <form id="formReferencias" class="needs-validation mt-3" novalidate>

              <div class="row cards-misma-altura">
                <div class="col-md-8">
                  <div class="card mb-4">
                    <div class="card-header bg-white d-flex flex-wrap align-items-center justify-content-between gap-2">
                      <h5 class="mb-0 form-label text-primary">Datos generales</h5>
                      <div class="form-check form-switch d-flex align-items-center gap-2 mb-0">
                        <div class="form-check form-switch switch-activo me-2 mb-2 d-flex align-items-center gap-2">
                          <input type="checkbox" class="form-check-input" id="activo" name="activo">
                          <label for="activo" class="form-label estado-label mb-0 d-flex align-items-center gap-2 mb-0">
                            <span class="estado-off">Inactivo</span>
                            <span class="estado-on">Activo</span>
                          </label>
                        </div>
                      </div>
                    </div>
                    <div class="card-body border-bottom bg-body-tertiary d-flex flex-wrap gap-2">
                      <button type="button" class="btn btn-sm btn-primary" id="btnGuardar"> <i class="fa-regular fa-floppy-disk"></i> Guardar</button>
                      <button type="reset" class="btn btn-sm btn-outline-secondary" id="btnLimpiar"> <i class="fa-regular fa-file"></i> Nuevo</button>
                      <button type="button" class="btn btn-sm btn-outline-info" id="btnAuditoria" disabled="" title="Disponible una vez el registro esté guardado"> <i class="fa-solid fa-clock-rotate-left"></i> Auditoría</button>
                      <button type="button" class="btn btn-sm btn-outline-success" id="btnComportamiento" disabled="" title="Disponible una vez el registro esté guardado"> <i class="fa-solid fa-arrow-trend-up"></i> Comportamiento</button>
                      <button type="button" class="btn btn-sm btn-link text-muted ms-auto text-decoration-none" id="btn-video-tutorial"> <i class="fab fa-youtube text-danger"></i> Ver tutorial</button>
                    </div>
                    <div class="card-body ">
                      <div class="row">
                        <div class="col-md-3">
                          <label for="slcTipoReferencia" class="form-label">Tipo referencia</label>
                          <select id="slcTipoReferencia" name="tipo" class="form-select form-select-sm">
                            <option value="P">PRODUCTO</option>
                            <option value="S">SERVICIO</option>
                          </select>
                        </div>
                        <div class="col-md-9">
                          <label for="txtCodigo" class="form-label">Código (SKU)</label>

                          <div class="input-group input-group-sm input-group-merge">
                            <span class="input-group-text">
                              <i class="bx bx-search"></i>
                            </span>

                            <input type="text"
                              class="form-control"
                              id="txtCodigo"
                              name="codigo"
                              maxlength="20"
                              placeholder="Código o descripción"
                              autocomplete="off"
                              required>

                            <span class="input-group-text btn btn-sm btn-outline-primary"
                              id="btnBusquedaAvanzada">
                              <i class="fa-solid fa-sliders"></i>
                            </span>
                          </div>

                          <div class="form-text d-flex align-items-center gap-1">
                            Presiona el ícono <i class="fa-solid fa-sliders text-primary"></i> para búsqueda avanzada.
                            <span class="ms-2 text-muted">|</span>
                            <span class="ms-1">Último código:</span>
                            <input type="text"
                              id="ultimo_codigo"
                              class="ultimo-codigo"
                              maxlength="10"
                              readonly>
                          </div>
                        </div>
                      </div>
                      <div class="row mt-2">
                        <div class="col-md-12">
                          <label for="txtDescripcion" class="form-label">Descripción corta</label>
                          <input type="text" class="form-control form-control-sm" id="txtDescripcion" name="descripcion" placeholder="Descripción" autocomplete="off" required>
                        </div>
                        <div class="col-md-12">
                          <label for="txtDescripcionlg" class="form-label">Descripción larga</label>
                          <textarea id="txtDescripcionlg" name="descripcion_larga" class="form-control" placeholder="Descripcion larga" required></textarea>
                        </div>
                      </div>
                      <div class="row mt-2">
                        <div class="col-md-4">
                          <label for="txtCostoUnd" class="form-label">Costo (UND)</label>
                          <input type="text" class="form-control form-control-sm moneda" id="txtCostoUnd" name="costo_unitario" maxlength="20" placeholder="Costo UND" autocomplete="off" required>
                        </div>
                        <div class="col-md-4">
                          <label class="form-label" for="slcMarcas">Proveedor/Marca</label>
                          <div class="d-flex">
                            <div class="flex-grow-1">
                              <select class="form-select form-select-sm" id="slcMarcas" name="id_marca"></select>
                            </div>

                            <button
                              class="btn btn-sm btn-outline-primary"
                              type="button"
                              id="btn-add-marca"
                              title="Agregar nueva marca">
                              <i class="fa-regular fa-square-plus"></i>
                            </button>
                          </div>
                        </div>
                        <div class="col-md-4">
                          <label for="slcPerfilContable" class="form-label">Perfil Contable</label>
                          <select class="form-select form-select-sm" id="slcPerfilContable" name="perfil_contable">
                          </select>
                        </div>
                      </div>
                      <div class="row mt-3" style="display:none">
                        <div class="col-md-12">
                          <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="S" id="no_venta" name="no_venta" />
                            <label class="form-check-label" for="defaultCheck3"> No incluir en la venta</label>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                      <h5 class="card-title form-label text-primary">Imagenes</h5>
                      <h6 class="card-subtitle text-muted">Máximo 4 imagenes</h6>
                    </div>
                    <div class="card-body">
                      <div class="form-group">
                        <div class="dropzone" id="myDropzone">
                          <div class="dz-default dz-message">
                            <button class="dz-button" type="button">
                              <i class="fas fa-cloud-upload-alt fa-3x" style="color: #B197FC;"></i><br>
                              <span>Arrastra tus archivos aquí o haz clic para cargar</span> </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-4 d-flex">
                  <div class="card mb-4 w-100 h-100">
                    <h5 class="card-header form-label text-primary">Conversiones</h5>
                    <div class="card-body">
                      <label for="txtConversion" class="d-none form-label">Conversión</label>
                      <input type="text" class="d-none form-control form-control-sm moneda" id="txtConversion" placeholder="conversion" name="conversion" autocomplete="off" value="1" required>
                      <table class="mt-3 table table-sm table-hover" id="tbUnidades">
                        <thead class="table-primary">
                          <tr>
                            <th>Unidad</th>
                            <th>Cantidad</th>
                            <th>Compra</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr>
                            <td><input type="text" class="form-control form-control-sm " id="und_1" name="und_1" value="UND" autocomplete="off" required></td>
                            <td><input type="text" class="form-control form-control-sm moneda " id="cant_1" name="cant_1" placeholder="CANTIDAD 1" autocomplete="off" value="1" readonly required></td>
                            <td align="center"><input type="radio" name="und_compra" class="form-check-input" id="compra_1" value="1" checked></td>
                          </tr>
                          <tr>
                            <td><input type="text" class="form-control form-control-sm clcon" id="und_2" name="und_2" autocomplete="off" placeholder="UNIDAD 2"></td>
                            <td><input type="text" class="form-control form-control-sm moneda clcon" id="cant_2" name="cant_2" placeholder="CANTIDAD 2" autocomplete="off"></td>
                            <td align="center"><input type="radio" name="und_compra" class="form-check-input clcon" id="compra_2" value="2"></td>
                          </tr>
                          <tr>
                            <td><input type="text" class="form-control form-control-sm clcon" id="und_3" name="und_3" autocomplete="off" placeholder="UNIDAD 3"></td>
                            <td><input type="text" class="form-control form-control-sm moneda clcon" id="cant_3" name="cant_3" placeholder="CANTIDAD 3" autocomplete="off"></td>
                            <td align="center"><input type="radio" name="und_compra" class="form-check-input clcon" id="compra_3" value="3"></td>
                          </tr>
                          <tr>
                            <td><input type="text" class="form-control form-control-sm clcon" id="und_4" name="und_4" autocomplete="off" placeholder="UNIDAD 4"></td>
                            <td><input type="text" class="form-control form-control-sm moneda clcon" id="cant_4" name="cant_4" placeholder="CANTIDAD 4" autocomplete="off"></td>
                            <td align="center"><input type="radio" name="und_compra" class="form-check-input clcon" id="compra_4" value="4"></td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
                <div class="col-md-4 d-flex">
                  <div class="card mb-4 w-100 h-100">
                    <h5 class="card-header form-label text-primary">Logístico</h5>
                    <div class="card-body">
                      <label for="slcManejaLote" class="form-label">MANEJA LOTE</label>
                      <select class="form-select form-select-sm" id="slcManejaLote" name="maneja_lote">
                        <option value="S">SI</option>
                        <option value="N">NO</option>
                      </select>
                      <label for="txtStockMin" class="form-label">STOCK MÍNIMO</label>
                      <input type="text" class="form-control form-control-sm" id="txtStockMin" value="0" name="stock_minimo" placeholder="Stock minimo" onKeyPress="return vnumeros(event)" required>
                      <label for="txtStockMax" class="form-label">STOCK MÁXIMO</label>
                      <input type="text" class="form-control form-control-sm" id="txtStockMax" value="0" name="stock_maximo" placeholder="Stock Máximo" onKeyPress="return vnumeros(event)" required>
                      <label for="txtDiasReposicion" class="form-label">REPOSICIÓN (DIAS)</label>
                      <input type="text" class="form-control form-control-sm mb-2" id="txtDiasReposicion" value="0" name="dias_reposicion" placeholder="Dias reposicion" onKeyPress="return vnumeros(event)" required>
                    </div>
                  </div>
                </div>
                <div class="col-md-4 d-flex">
                  <div class="card mb-4 w-100 h-100">
                    <h5 class="card-header form-label text-primary">Impuestos</h5>
                    <div class="card-body">
                      <label for="slcIva" class="form-label">IVA % (Depende del perfil contable)</label>
                      <select class="form-select form-select-sm" id="slcIva" name="id_iva" readonly>
                      </select>
                      <label for="txtImpoconsumo" class="form-label">IMPOCONSUMO ($)</label>
                      <input type="text" class="form-control form-control-sm moneda" id="txtImpoconsumo" value="0" placeholder="Impoconsumo" name="impoconsumo" required>
                      <label for="txtIBUA" class="form-label">IBUA ($)</label>
                      <input type="text" class="form-control form-control-sm moneda" value="0" id="txtIBUA" name="ibua" placeholder="IBUA" required>
                      <label for="slcICUI" class="form-label">ICUI (%)</label>
                      <select class="form-select form-select-sm mb-2" id="slcICUI" name="id_icui">
                      </select>
                    </div>
                  </div>
                </div>
              </div>
              <div class="row mt-3">
                <div class="col-12">
                  <div class="card mb-4">
                    <h5 class="card-header form-label text-primary">Listas de precios</h5>
                    <div class="card-body ">
                      <div class="form-text costos-resumen mb-2">
                        <div class="costo-item">
                          <span>Costo promedio:</span>
                          <input type="text"
                            id="costo_promedio"
                            value="$0"
                            class="input-info"
                            readonly>
                        </div>
                        <span class="costo-separador">|</span>
                        <div class="costo-item">
                          <span>Costo último:</span>
                          <input type="text"
                            id="costo_ultimo"
                            value="$0"
                            class="input-info"
                            readonly>
                        </div>
                        <span class="costo-separador">|</span>
                        <div class="costo-item">
                          <span>Stock actual:</span>
                          <input type="text"
                            id="stock_actual"
                            value="0"
                            class="input-info"
                            readonly>
                        </div>
                      </div>
                      <div class="row mb-2">
                        <!-- Lista -->
                        <div class="col-md-2">
                          <label for="slcLista" class="form-label">Lista</label>
                          <select class="form-select form-select-sm asigar-precio" id="slcLista">
                          </select>
                        </div>
                        <!-- Tipo lista -->
                        <div class="col-md-2">
                          <label for="slcTipoLista" class="form-label">Tipo lista</label>
                          <select class="form-select form-select-sm asigar-precio" id="slcTipoLista">
                          </select>
                        </div>
                        <!-- Rentabilidad 1 -->
                        <div class="col-md-2">
                          <label for="txtRentabildad" class="form-label">Rent/Precio 1</label>
                          <input type="text" class="form-control form-control-sm moneda asigar-precio" id="txtRentabildad" value="0" placeholder="Rentabilidad 1">
                        </div>
                        <!-- Rentabilidad 2 -->
                        <div class="col-md-2">
                          <label for="txtRentabildad2" class="form-label">Rent/Precio 2</label>
                          <input type="text" class="form-control form-control-sm moneda asigar-precio" id="txtRentabildad2" value="0" placeholder="Rentabilidad 2">
                        </div>
                        <!-- Rentabilidad 3 -->
                        <div class="col-md-2">
                          <label for="txtRentabildad3" class="form-label">Rent/Precio 3</label>
                          <input type="text" class="form-control form-control-sm moneda asigar-precio" id="txtRentabildad3" value="0" placeholder="Rentabilidad 3">
                        </div>
                        <!-- Rentabilidad 4 + botón -->
                        <div class="col-md-2">
                          <label for="txtRentabildad4" class="form-label">Rent/Precio 4</label>
                          <div class="input-group">
                            <input type="text" class="form-control form-control-sm moneda asigar-precio" id="txtRentabildad4" value="0" placeholder="Rentabilidad 4">
                          </div>
                        </div>
                      </div>
                      <div class="row mb-3">
                        <div class="col-12"><button class="btn btn-sm btn-outline-primary" type="button" id="btnAddLista">
                            <i class="fa-regular fa-square-plus"></i> Agregar lista
                          </button></div>
                      </div>
                      <div class="row mb-3">
                        <!-- PRECIOS 1 -->
                        <div class="col-md-3">
                          <fieldset class="border p-2">
                            <legend class="float-none w-auto text-primary form-label">PRECIOS CONVERSIÓN 1</legend>
                            <label for="textPrecioSinImp" class="form-label">Precio sin imp.</label>
                            <input type="text" id="textPrecioSinImp" readonly class="form-control form-control-sm">
                            <label for="textPrecioConImp" class="form-label mt-2">Precio con imp.</label>
                            <input type="text" id="textPrecioConImp" readonly class="form-control form-control-sm">
                          </fieldset>
                        </div>

                        <!-- PRECIOS 2 -->
                        <div class="col-md-3">
                          <fieldset class="border p-2">
                            <legend class="float-none w-auto text-primary form-label">PRECIOS CONVERSIÓN 2</legend>
                            <label for="textPrecioSinImp2" class="form-label">Precio sin imp.</label>
                            <input type="text" id="textPrecioSinImp2" readonly class="form-control form-control-sm">
                            <label for="textPrecioConImp2" class="form-label mt-2">Precio con imp.</label>
                            <input type="text" id="textPrecioConImp2" readonly class="form-control form-control-sm">
                          </fieldset>
                        </div>

                        <!-- PRECIOS 3 -->
                        <div class="col-md-3">
                          <fieldset class="border p-2">
                            <legend class="float-none w-auto text-primary form-label">PRECIOS CONVERSIÓN 3</legend>
                            <label for="textPrecioSinImp3" class="form-label">Precio sin imp.</label>
                            <input type="text" id="textPrecioSinImp3" readonly class="form-control form-control-sm">
                            <label for="textPrecioConImp3" class="form-label mt-2">Precio con imp.</label>
                            <input type="text" id="textPrecioConImp3" readonly class="form-control form-control-sm">
                          </fieldset>
                        </div>

                        <!-- PRECIOS 4 -->
                        <div class="col-md-3">
                          <fieldset class="border p-2">
                            <legend class="float-none w-auto text-primary form-label">PRECIOS CONVERSIÓN 4</legend>
                            <label for="textPrecioSinImp4" class="form-label">Precio sin imp.</label>
                            <input type="text" id="textPrecioSinImp4" readonly class="form-control form-control-sm">
                            <label for="textPrecioConImp4" class="form-label mt-2">Precio con imp.</label>
                            <input type="text" id="textPrecioConImp4" readonly class="form-control form-control-sm">
                          </fieldset>
                        </div>

                      </div>
                      <div class="table-responsive text-nowrap">
                        <table class="table table-sm table-hover" id="tbListas">
                          <thead class="table-primary">
                            <tr>
                              <th>Lista</th>
                              <th>Tipo</th>
                              <th>Conversión 1</th>
                              <th>Conversión 2</th>
                              <th>Conversión 3</th>
                              <th>Conversión 4</th>
                              <th>Eliminar</th>
                            </tr>
                          </thead>
                          <tbody>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                  <div class="card bg-light mb-4">
                    <h5 class="card-header form-label text-primary"><input type="checkbox" id="chkComisiones" name="comision"> Comisiones</h5>
                    <div class="card-body">
                      <fieldset class="border p-2">
                        <legend class="float-none w-auto text-primary form-label">Opciones de selección</legend>
                        <div class="row">

                          <div class="col-md-6">
                            <label for="slcListaComision" class="form-label">Lista</label>
                            <select class="form-select form-select-sm asigar-precio element-comisiones" id="slcListaComision" disabled>
                            </select>
                          </div>
                          <!-- Tipo lista -->
                          <div class="col-md-6">
                            <label for="slcTipoListaComision" class="form-label">Cálculo</label>
                            <select class="form-select form-select-sm asigar-precio element-comisiones" id="slcTipoListaComision" disabled>
                              <option value="valor">Valor fijo</option>
                              <option value="porcentaje">Porcentaje</option>
                            </select>
                          </div>

                        </div>
                      </fieldset>
                      <div class="row mt-3">
                        <div class="col-md-3">
                          <fieldset class="border p-2">
                            <legend class="float-none w-auto text-primary form-label">COMISIÓN CONVERSIÓN 1</legend>
                            <input type="text" id="comision_und_1" class="form-control form-control-sm element-comisiones" value="0" disabled>
                            <label id="text_tipo_comision_1" class="float-none w-auto text-primary form-label">Valor Comisión</label>
                            <input type="text" id="vlr_comision_und_1" class="form-control form-control-sm input-sin-bordes element-comisiones-calculo" value="$0" disabled>
                            <label class="float-none w-auto text-primary form-label" id="text_precio_comision_1">Precio Neto (con comisión)</label>
                            <input type="text" id="precio_comision_und_1" class="form-control form-control-sm input-sin-bordes element-comisiones-calculo" value="$0" disabled>
                            <label class="float-none w-auto text-primary form-label" id="text_rentabilidad_comision_1">Rentabilidad %</label>
                            <input type="text" id="rentabilidad_comision_und_1" class="form-control form-control-sm input-sin-bordes element-comisiones-calculo" value="0%" disabled>
                          </fieldset>
                        </div>
                        <div class="col-md-3">
                          <fieldset class="border p-2">
                            <legend class="float-none w-auto text-primary form-label">COMISIÓN CONVERSIÓN 2</legend>
                            <input type="text" id="comision_und_2" class="form-control form-control-sm element-comisiones" value="0" disabled>
                            <label id="text_tipo_comision_2" class="float-none w-auto text-primary form-label">Valor Comisión</label>
                            <input type="text" id="vlr_comision_und_2" class="form-control form-control-sm input-sin-bordes element-comisiones-calculo" value="$0" disabled>
                            <label class="float-none w-auto text-primary form-label" id="text_precio_comision_2">Precio Neto (con comisión)</label>
                            <input type="text" id="precio_comision_und_2" class="form-control form-control-sm input-sin-bordes element-comisiones-calculo" value="$0" disabled>
                            <label class="float-none w-auto text-primary form-label" id="text_rentabilidad_comision_2">Rentabilidad %</label>
                            <input type="text" id="rentabilidad_comision_und_2" class="form-control form-control-sm input-sin-bordes element-comisiones-calculo" value="0%" disabled>
                          </fieldset>
                        </div>
                        <div class="col-md-3">
                          <fieldset class="border p-2">
                            <legend class="float-none w-auto text-primary form-label">COMISIÓN CONVERSIÓN 3</legend>
                            <input type="text" id="comision_und_3" class="form-control form-control-sm element-comisiones" value="0" disabled>
                            <label id="text_tipo_comision_3" class="float-none w-auto text-primary form-label">Valor Comisión</label>
                            <input type="text" id="vlr_comision_und_3" class="form-control form-control-sm input-sin-bordes element-comisiones-calculo" value="$0" disabled>
                            <label class="float-none w-auto text-primary form-label" id="text_precio_comision_3">Precio Neto (con comisión)</label>
                            <input type="text" id="precio_comision_und_3" class="form-control form-control-sm input-sin-bordes element-comisiones-calculo" value="$0" disabled>
                            <label class="float-none w-auto text-primary form-label" id="text_rentabilidad_comision_3">Rentabilidad %</label>
                            <input type="text" id="rentabilidad_comision_und_3" class="form-control form-control-sm input-sin-bordes element-comisiones-calculo" value="0%" disabled>
                        </div>
                        <div class="col-md-3">
                          <fieldset class="border p-2">
                            <legend class="float-none w-auto text-primary form-label">COMISIÓN CONVERSIÓN 4</legend>
                            <input type="text" id="comision_und_4" class="form-control form-control-sm element-comisiones" value="0" disabled>
                            <label id="text_tipo_comision_4" class="float-none w-auto text-primary form-label">Valor Comisión</label>
                            <input type="text" id="vlr_comision_und_4" class="form-control form-control-sm input-sin-bordes element-comisiones-calculo" value="$0" disabled>
                            <label class="float-none w-auto text-primary form-label" id="text_precio_comision_4">Precio Neto (con comisión)</label>
                            <input type="text" id="precio_comision_und_4" class="form-control form-control-sm input-sin-bordes element-comisiones-calculo" value="$0" disabled>
                            <label class="float-none w-auto text-primary form-label" id="text_rentabilidad_comision_4">Rentabilidad %</label>
                            <input type="text" id="rentabilidad_comision_und_4" class="form-control form-control-sm input-sin-bordes element-comisiones-calculo" value="0%" disabled>
                        </div>
                      </div>
                      <div class="row">
                        <div class="col-md-2">
                          <label for="txtValorComision" class="form-label">&nbsp;</label>
                          <button class="btn btn-sm btn-outline-primary form-control element-comisiones" type="button" id="btnAddComision" disabled>
                            <i class="fa-regular fa-square-plus"></i> Agregar comisión
                          </button>
                        </div>
                      </div>

                      <div class="table-responsive text-nowrap mb-3 mt-3" id="ItemsComision">
                        <table class="table table-sm table-hover" id="tbComisiones">
                          <thead class="table-primary">
                            <tr>
                              <th>Lista</th>
                              <th>Calculo</th>
                              <th>Comision 1 </th>
                              <th>Comision 2 </th>
                              <th>Comision 3 </th>
                              <th>Comision 4 </th>
                              <th>Eliminar</th>
                            </tr>
                          </thead>
                          <tbody>
                          </tbody>

                        </table>
                      </div>
                    </div>
                  </div>

                </div>
              </div>
              <!-- -- Códigos de barra, categorias y control -->
              <div class="row">
                <div class="col-md-4 d-flex">
                  <div class="card mb-4 w-100 h-100">
                    <h5 class="card-header form-label text-primary">Códigos de barra (EAN)</h5>
                    <div class="card-body">
                      <div class="row mb-2">
                        <div class="col-md-12">
                          <label for="txtEan" class="form-label">Codigo/EAN</label>
                          <div class="input-group mb-3">
                            <input type="text" class="form-control form-control-sm" id="txtEan" placeholder="Codigo de barras / EAN" onKeyPress="return vnumeros(event)">
                            <button class="btn btn-sm btn-outline-primary" id="btnAddEan" type="button"><i class="fa-regular fa-square-plus"></i></button>
                          </div>
                        </div>
                      </div>
                      <table class="table table-sm table-hover" id="tbCodBarras">
                        <thead>
                          <tr>
                            <th>Codigo/EAN</th>
                            <th>Eliminar</th>
                          </tr>
                        </thead>
                        <tbody>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
                <div class="col-md-4 d-flex">
                  <div class="card mb-4 w-100 h-100">
                    <h5 class="card-header form-label text-primary">Categorias</h5>
                    <div class="card-body">
                      <label for="slcCategoria" class="form-label">Categoria 1</label>
                      <div class="input-group mb-3">
                        <select class="form-select form-select-sm" id="slcCategoria1" name="id_categoria_1">
                        </select>
                        <button class="btn btn-sm btn-outline-primary btnCat" id="btnAddCat1" type="button"><i class="fa-regular fa-square-plus"></i> </button>
                      </div>
                      <label for="slcCategoria1" class="form-label">Categoria 2</label>
                      <div class="input-group mb-3">
                        <select class="form-select form-select-sm" id="slcCategoria2" name="id_categoria_2">
                        </select>
                        <button class="btn btn-sm btn-outline-primary btnCat" id="btnAddCat2" type="button"><i class="fa-regular fa-square-plus"></i></button>
                      </div>
                      <label for="slcCategoria2" class="form-label">Categoria 3</label>
                      <div class="input-group mb-3">
                        <select class="form-select form-select-sm" id="slcCategoria3" name="id_categoria_3">
                        </select>
                        <button class="btn btn-sm btn-outline-primary btnCat" id="btnAddCat3" type="button"><i class="fa-regular fa-square-plus"></i></button>
                      </div>
                      <label for="slcCategoria3" class="form-label">Categoria 4</label>
                      <div class="input-group mb-3">
                        <select class="form-select form-select-sm" id="slcCategoria4" name="id_categoria_4">
                        </select>
                        <button class="btn btn-sm btn-outline-primary btnCat" id="btnAddCat4" type="button"><i class="fa-regular fa-square-plus"></i></button>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-md-4 d-flex">
                  <div class="card mb-4 w-100 h-100">
                    <h5 class="card-header form-label text-primary">Control</h5>
                    <div class="card-body">
                      <label for="txtCum" class="form-label">CUM
                        <i class="fa-solid fa-circle-info ms-1 text-primary"
                          title="Código Único de Medicamento"
                          style="cursor: help;"></i>
                      </label>
                      <input type="text" class="form-control form-control-sm" value="0" id="txtCum" name="codigo_cum" placeholder="CUM">
                      <label for="txtInvima" class="form-label">REGISTRO INVIMA</label>
                      <input type="text" class="form-control form-control-sm" id="txtInvima" name="codigo_invima" placeholder="INVIMA">
                      <label for="txtIum" class="form-label">
                        IUM
                        <i class="fa-solid fa-circle-info ms-1 text-primary"
                          title="Identificador Único de Medicamento"
                          style="cursor: help;"></i>
                      </label>
                      <input type="text" class="form-control form-control-sm" id="txtIum" name="ium" placeholder="IUM" maxlength="19">
                      <label for="txtNumExp" class="form-label">Numero Exp.</label>
                      <input type="text" class="form-control form-control-sm" id="TxtNumeroExp" name="numero_expediente" placeholder="Numero de expediente" maxlength="=19" />
                      <label for="slcRegulado" class="form-label">REGULADO</label>
                      <select class="form-select form-select-sm" name="regulado" id="slcRegulado">
                        <option value="S">SI</option>
                        <option value="N">NO</option>
                      </select>
                      <label for="slcControlado" class="form-label">CONTROLADO</label>
                      <select class="form-select form-select-sm" name="controlado" id="slcControlado">
                        <option value="S">SI</option>
                        <option value="N">NO</option>
                      </select>
                      <label for="slcInstitucional" class="form-label">INSTITUCIONAL</label>
                      <select class="form-select form-select-sm" name="institucional" id="slcInstitucional">
                        <option value="S">SI</option>
                        <option value="N">NO</option>
                      </select>
                      <label class="form-label">UBICACIONES</label>
                      <br>
                      <select class="form-control form-control-sm" id="ubicacion" multiple>
                      </select>
                    </div>
                  </div>
                </div>
              </div>

            </form>
          </div>
          <div class="tab-pane fade show" id="navs-listado" role="tabpanel">
            <div class="row">
              <div class="col-12">
                <div class="row mb-3 g-2">
                  <div class="col-md-9">
                    <div class="input-group input-group-merge"> <span class="input-group-text"><i class="bx bx-search"></i></span>
                      <input name="" id="filtro" type="text" class="form-control form-control-sm" placeholder="Codigo, descripcion..." aria-label="Buscar..." autocomplete="off" />
                      <button class="btn btn-outline-primary btn-sm" id="buscar">Buscar</button>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="input-group input-group-merge"> <span class="input-group-text"><i class="fa-solid fa-filter"></i></span>
                      <select id="estado_filter" class="form-select form-select-sm">
                        <option value="1" selected>Activos</option>
                        <option value="0">Inactivos</option>
                      </select>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-12">
                    <div id="result"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="tab-pane fade show" id="navs-cargas" role="tabpanel">
            <div class="alert alert-primary border border-primary" role="alert" style="background-color: transparent;">
              <h4 class="alert-heading">¡Bienvenido!</h4>
              <p>El sistema le permitirá subir masivamente las referencias formato Excel, por favor de click en el botón de información para conocer la estructura de dicho archivo.</p>
              <p class="mb-0"></p>
              <hr>
              <button class="btn btn-outline-secondary btn-sm" type="button" id="btn-plantilla"><i class="fa-regular fa-circle-down"></i>&nbsp;Descargar plantilla excel</button>
            </div>
            <div class="input-group">
              <input type="file" class="form-control form-control-sm" id="txtFileUp">
              <button class="btn btn-outline-primary btn-sm" type="button" id="btnUp"><i class="fa-regular fa-circle-up"></i>&nbsp;Cargar</button>
              <button class="btn btn-outline-secondary btn-sm" type="button" id="btnClean"><i class="fa-regular fa-file"></i>&nbsp;Nuevo</button>
            </div>
            <div class="mt-4 table-responsive" id="tableContainer" style="max-height: 500px;  overflow-y: auto;"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="modalCategoria" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalCategoriaTitle"></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="modalCategoriaBody">
          <div class="card">
            <div class="card-body">
              <input type="hidden" id="txtIdCategoria" readonly disabled>
              <div class="row">
                <div class="col-md-4">
                  <label for="txtCodigoCategoria" class="form-label">Codigo Categoria</label>
                  <input type="text" class="form-control form-control-sm" id="txtCodigoCategoria" name="codigo_categoria" placeholder="codigo" autocomplete="off">
                </div>
                <div class="col-md-8">
                  <label for="txtDescCategoria" class="form-label">Descripción Categoria</label>
                  <div class="input-group mb-3">
                    <input type="text" class="form-control form-control-sm" id="txtDescCategoria" name="descripcion_categoria" placeholder="Descripción" autocomplete="off">
                    <button class="btn btn-sm btn-outline-primary" id="saveCategoria" type="button"><i class="fa-regular fa-square-plus"></i></button>
                  </div>
                </div>
              </div>
              <div class="row mt-3">
                <table class="table table-sm table-hover" id="tbCategorias">
                  <thead>
                    <tr>
                      <th>Id</th>
                      <th>Codigo</th>
                      <th>Descripcion</th>
                      <th>Eliminar</th>
                    </tr>
                  </thead>
                  <tbody>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal"> Cerrar </button>
        </div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="modalMarcas" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="">Creación de marcas</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="">
          <div class="card">
            <div class="card-body">
              <form id="FormCrear" class=" form-basico" novalidate>
                <div class="row">
                  <div class="col-md-12">
                    <label for="txtDescripcion" class="form-label">Descripción</label>
                    <div class="input-group input-group-sm">
                      <input type="text" class="form-control form-control-sm" name="descripcion" id="txtDescripcion" maxlength="50" autocomplete="off" required>
                      <span class="input-group-text cursor-pointer">
                        <button class="btn btn-sm btn-outline-primary  border-1 border border-primary" type="submit" id="guardar-marca"><i class="fa-regular fa-square-plus"></i></button>
                      </span>
                    </div>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal"> Cerrar </button>
        </div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="modalAuditoriaReferencia" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Historico de auditoria de la referencia <span id="auditoriaReferenciaLabel" class="text-primary"></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="alertAuditoriaReferencia" class="mb-2"></div>
          <div class="row g-2 mb-3">
            <div class="col-md-2">
              <label class="form-label mb-1">Accion</label>
              <select class="form-select form-select-sm" id="filtroAuditoriaAccion">
                <option value="">Todas</option>
                <option value="CREAR">CREAR</option>
                <option value="UPDATE">UPDATE</option>
                <option value="EDITAR">EDITAR</option>
                <option value="DELETE">DELETE</option>
                <option value="ELIMINAR">ELIMINAR</option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label mb-1">Fecha desde</label>
              <input type="date" class="form-control form-control-sm" id="filtroAuditoriaDesde">
            </div>
            <div class="col-md-2">
              <label class="form-label mb-1">Fecha hasta</label>
              <input type="date" class="form-control form-control-sm" id="filtroAuditoriaHasta">
            </div>
            <div class="col-md-3">
              <label class="form-label mb-1">Usuario</label>
              <input type="text" class="form-control form-control-sm" id="filtroAuditoriaUsuario" placeholder="Usuario...">
            </div>
            <div class="col-md-3">
              <label class="form-label mb-1">Texto en detalle</label>
              <div class="input-group input-group-sm">
                <input type="text" class="form-control" id="filtroAuditoriaTexto" placeholder="Buscar en detalle...">
                <button class="btn btn-outline-secondary" type="button" id="btnLimpiarFiltrosAuditoria">
                  <i class="fa-solid fa-filter-circle-xmark"></i>
                </button>
              </div>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table table-sm table-hover table-striped align-middle" id="tbAuditoriaReferencia">
              <thead class="table-primary">
                <tr>
                  <th>Fecha</th>
                  <th>Usuario</th>
                  <th>Accion</th>
                  <th class="text-center">Detalle</th>
                </tr>
              </thead>
              <tbody id="tbodyAuditoriaReferencia"></tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="modalDetalleAuditoria" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Detalle de cambio</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="detalleAuditoriaContainer"></div>
        <div class="modal-footer">
          <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>
  <div id="modal-container"></div>

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


  <div class="modal fade" id="modalComportamientoProducto" tabindex="-1" aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-scrollable">

      <div class="modal-content">

        <!-- ================================================= -->
        <!-- HEADER -->
        <!-- ================================================= -->
        <div class="modal-header">

          <div>
            <h5 class="modal-title text-primary mb-0">
              <i class="fa-solid fa-chart-line me-2"></i>
              Comportamiento del producto
            </h5>

            <small class="text-muted">
              Análisis de ventas, inventario, costos y rentabilidad
            </small>
          </div>

          <button type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Cerrar">
          </button>

        </div>


        <!-- ================================================= -->
        <!-- BODY -->
        <!-- ================================================= -->
        <div class="modal-body">

          <!-- INFORMACIÓN DEL PRODUCTO + FILTROS -->
          <div class="card shadow-sm mb-3">
            <!-- HEADER -->
            <div class="card-body">
              <!-- INFORMACIÓN DEL PRODUCTO -->
              <div class="row g-0">
                <!-- CÓDIGO -->
                <div class="col-md-2 px-3 border-end">
                  <small class="text-muted d-block mb-1">
                    Código
                  </small>
                  <div class="fw-semibold text-dark" id="info_codigo">
                    -
                  </div>
                </div>
                <!-- DESCRIPCIÓN -->
                <div class="col-md-5 px-3 border-end">
                  <small class="text-muted d-block mb-1">
                    Descripción
                  </small>
                  <div class="fw-semibold text-dark text-truncate"
                    id="info_descripcion"
                    title="">
                    -
                  </div>
                </div>
                <!-- ESTADO -->
                <div class="col-md-2 px-3 border-end">
                  <small class="text-muted d-block mb-1">
                    Estado
                  </small>
                  <div>
                    <span id="info_estado" class="badge bg-success">
                      Activo
                    </span>
                  </div>
                </div>
                <!-- COSTOS -->
                <div class="col-md-3 px-3 border-end">
                  <small class="text-muted d-block mb-1">
                    Costos
                  </small>
                  <div class="d-flex align-items-center gap-3">
                    <div>
                      <small class="text-muted d-block">
                        Promedio
                      </small>
                      <strong id="info_costo_promedio">
                        $0
                      </strong>
                    </div>
                    <div class="vr"></div>
                    <div>
                      <small class="text-muted d-block">
                        Último
                      </small>
                      <strong id="info_costo_ultimo">
                        $0
                      </strong>
                    </div>
                  </div>
                </div>
              </div>
              <!-- SEPARADOR -->
              <hr class="my-4">
              <!-- FILTROS -->
              <div class="row g-3 align-items-end">
                <div class="col-md-3">
                  <label for="fecha_inicial" class="form-label mb-1">
                    Fecha inicial
                  </label>
                  <input type="date"
                    id="fecha_inicial"
                    class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                  <label for="fecha_final" class="form-label mb-1">
                    Fecha final
                  </label>
                  <input type="date"
                    id="fecha_final"
                    class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                  <label for="bodegas" class="form-label mb-1">
                    Bodega
                  </label>
                  <select id="bodegas"
                    class="form-select form-select-sm">
                  </select>
                </div>
                <div class="col-md-3">
                  <button type="button"
                    class="btn btn-sm btn-primary w-100"
                    id="btnBuscar">
                    <i class="fa-solid fa-magnifying-glass me-1"></i>
                    Consultar
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- ================================================= -->
          <!-- INDICADORES -->
          <!-- ================================================= -->
          <div class="row g-2 mb-3">
            <div class="col-xl col-md-4 col-sm-6">
              <div class="card shadow-sm h-100">
                <div class="card-body py-2">
                  <small class="text-muted">
                    Stock actual
                  </small>
                  <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0" id="kpi_stock">
                      0
                    </h4>
                    <i class="fa-solid fa-boxes-stacked text-primary fs-4"></i>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-xl col-md-4 col-sm-6">
              <div class="card shadow-sm h-100">
                <div class="card-body py-2">
                  <small class="text-muted">
                    Unidades vendidas
                  </small>
                  <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0" id="kpi_ventas">
                      0
                    </h4>
                    <i class="fa-solid fa-cart-shopping text-success fs-4"></i>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-xl col-md-4 col-sm-6">
              <div class="card shadow-sm h-100">
                <div class="card-body py-2">
                  <small class="text-muted">
                    Unidades compradas
                  </small>
                  <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0" id="kpi_compras">
                      0
                    </h4>
                    <i class="fa-solid fa-truck text-info fs-4"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- ================================================= -->
          <!-- RENTABILIDAD -->
          <!-- ================================================= -->
          <div class="card shadow-sm mb-3">

            <div class="card-header bg-white d-flex justify-content-between align-items-center">
              <div>
                <h6 class="mb-0 text-primary">
                  <i class="fa-solid fa-chart-line me-2"></i>
                  Rentabilidad
                </h6>
                <small class="text-muted">
                  Resultado del período seleccionado
                </small>
              </div>
            </div>

            <div class="card-body">

              <div class="row g-4">

                <!-- GRÁFICA -->
                <div class="col-lg-7">

                  <div class="border rounded p-3 h-100">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <div>
                        <strong>Resultado financiero</strong>
                        <small class="text-muted d-block">
                          Venta vs costo vs utilidad
                        </small>
                      </div>
                    </div>

                    <div style="height: 260px;">
                      <canvas id="graficaRentabilidad"></canvas>
                    </div>

                  </div>

                </div>


                <!-- RESUMEN -->
                <div class="col-lg-5">

                  <div class="border rounded p-3 h-100">

                    <strong class="d-block mb-3">
                      Resumen
                    </strong>

                    <!-- VENTA -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <div>
                        <small class="text-muted d-block">
                          Venta
                        </small>
                        <strong id="rent_venta" class="fs-5 text-primary">
                          $0
                        </strong>
                      </div>

                      <i class="fa-solid fa-dollar-sign text-primary fs-4"></i>
                    </div>

                    <!-- COSTO -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <div>
                        <small class="text-muted d-block">
                          Costo
                        </small>
                        <strong id="rent_costo" class="fs-5 text-warning">
                          $0
                        </strong>
                      </div>

                      <i class="fa-solid fa-coins text-warning fs-4"></i>
                    </div>

                    <!-- UTILIDAD -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <div>
                        <small class="text-muted d-block">
                          Utilidad
                        </small>
                        <strong id="rent_utilidad" class="fs-5 text-success">
                          $0
                        </strong>
                      </div>

                      <i class="fa-solid fa-arrow-trend-up text-success fs-4"></i>
                    </div>

                    <hr>

                    <!-- MARGEN -->
                    <div class="mb-3">

                      <div class="d-flex justify-content-between mb-1">
                        <small class="text-muted">
                          Margen
                        </small>

                        <strong id="rent_margen">
                          0%
                        </strong>
                      </div>

                      <div class="progress" style="height: 8px;">
                        <div id="progress_margen"
                          class="progress-bar bg-success"
                          role="progressbar"
                          style="width: 0%">
                        </div>
                      </div>

                    </div>

                    <!-- MARKUP -->
                    <div>

                      <div class="d-flex justify-content-between mb-1">
                        <small class="text-muted">
                          Markup
                        </small>

                        <strong id="rent_markup">
                          0%
                        </strong>
                      </div>

                      <div class="progress" style="height: 8px;">
                        <div id="progress_markup"
                          class="progress-bar bg-info"
                          role="progressbar"
                          style="width: 0%">
                        </div>
                      </div>

                    </div>

                  </div>

                </div>

              </div>


              <!-- ================================================= -->
              <!-- COMPARACIÓN PP -->
              <!-- ================================================= -->

              <div class="border rounded mt-4">

                <div class="px-3 py-2 border-bottom bg-light">
                  <strong>
                    <i class="fa-solid fa-scale-balanced me-2 text-primary"></i>
                    Comparación de rentabilidad
                  </strong>
                </div>

                <div class="table-responsive">

                  <table class="table table-sm table-hover mb-0 text-center">

                    <thead class="table-primary">
                      <tr>
                        <th class="text-start">Indicador</th>
                        <th>Normal</th>
                        <th>PP</th>
                        <th>Diferencia</th>
                      </tr>
                    </thead>

                    <tbody>

                      <tr>
                        <td class="text-start">
                          Venta
                        </td>

                        <td id="rent_venta_normal">
                          $0
                        </td>

                        <td id="rent_venta_pp">
                          $0
                        </td>

                        <td id="rent_dif_venta">
                          $0
                        </td>
                      </tr>

                      <tr>
                        <td class="text-start">
                          Utilidad
                        </td>

                        <td id="rent_utilidad_normal">
                          $0
                        </td>

                        <td id="rent_utilidad_pp">
                          $0
                        </td>

                        <td id="rent_dif_utilidad">
                          $0
                        </td>
                      </tr>

                      <tr>
                        <td class="text-start">
                          Margen
                        </td>

                        <td id="rent_margen_normal">
                          0%
                        </td>

                        <td id="rent_margen_pp">
                          0%
                        </td>

                        <td id="rent_dif_margen">
                          0%
                        </td>
                      </tr>

                      <tr>
                        <td class="text-start">
                          Markup
                        </td>

                        <td id="rent_markup_normal">
                          0%
                        </td>

                        <td id="rent_markup_pp">
                          0%
                        </td>

                        <td id="rent_dif_markup">
                          0%
                        </td>
                      </tr>

                    </tbody>

                  </table>

                </div>

              </div>

            </div>
          </div>


          <!-- ================================================= -->
          <!-- MOVIMIENTOS -->
          <!-- ================================================= -->
          <div class="card shadow-sm mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
              <h6 class="mb-0 text-primary">
                <i class="fa-solid fa-list me-2"></i>
                Movimiento del producto
              </h6>
              <small class="text-muted">
                Auxiliar de inventario
              </small>
            </div>
            <div class="card-body p-0">
              <div id="resultado-inventario" class="resultado-inventario mt-3"></div>
            </div>
          </div>
          <!-- ================================================= -->
          <!-- PROVEEDORES -->
          <!-- ================================================= -->
          <div class="row g-3 mb-3">
            <div class="col-lg-8">
              <div class="card shadow-sm">
                <div class="card-header bg-white">
                  <h6 class="mb-0 text-primary">
                    <i class="fa-solid fa-truck me-2"></i>
                    Proveedores
                  </h6>
                </div>
                <div class="card-body p-0">
                  <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0"
                      id="tbProveedores">
                      <thead class="table-primary">
                        <tr>
                          <th>Proveedor</th>
                          <th class="text-end">Cantidad</th>
                          <th class="text-end">Último costo</th>
                          <th>Última compra</th>
                        </tr>
                      </thead>
                      <tbody>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                  <h6 class="mb-0 text-primary">
                    Últimas operaciones
                  </h6>
                </div>
                <div class="card-body">
                  <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted">
                      Última venta
                    </span>
                    <strong id="ultima_venta">
                      -
                    </strong>
                  </div>
                  <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted">
                      Última compra
                    </span>
                    <strong id="ultima_compra">
                      -
                    </strong>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- ================================================= -->
        <!-- FOOTER -->
        <!-- ================================================= -->
        <div class="modal-footer">
          <button type="button"
            class="btn btn-sm btn-outline-secondary"
            data-bs-dismiss="modal">
            <i class="fa-solid fa-xmark me-1"></i>
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </div>

  <?php

  $scriptsPagina = [
    'https://unpkg.com/dropzone@5/dist/min/dropzone.min.js',
    '../controllers/comisiones.js?' . rand(),
    '../controllers/Referencias.js?' . rand()
  ];

  include '../dependencies/footer.php';

  ?>