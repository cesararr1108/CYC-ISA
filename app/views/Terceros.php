    <?php
    session_start();

    $titulo = 'Terceros';
    include '../dependencies/header.php';
    ?>
    <style>
      /* Ocultar sugerencias de autocomplete */
      input:-webkit-autofill,
      input:-webkit-autofill:hover,
      input:-webkit-autofill:focus {
        -webkit-box-shadow: 0 0 0 30px white inset !important;
        -webkit-text-fill-color: #333 !important;
      }

      input::-webkit-credentials-auto-fill-button {
        display: none !important;
      }
    </style>
    <input type="hidden" readonly id="id" value="0">
    <div class="card">
      <div class="card-body">
        <h4 class="fw-bold py-3 mb-2"><span class="text-muted fw-light">Maestra /</span> Terceros</h4>
        <div class="nav-align-top">
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
          <!-- formulario 1  -->
          <div class="tab-content card">
            <div class="tab-pane fade show active" id="navs-creacion" role="tabpanel">
              <form id="FormTerceroBasicos" class="needs-validation  form-basico" novalidate autocomplete="off">
                <div class="row">
                  <!--panel central-->
                  <div class="col-md-12">
                    <div class="card shadow-sm mb-3">

                      <div class="card-header py-2">
                        <h6 class="mb-0 text-primary form-label">
                          <i class="fa-solid fa-magnifying-glass me-2"></i>
                          Tipo y búsqueda
                        </h6>
                      </div>

                      <div class="card-body">

                        <!-- Filtros -->
                        <div class="row g-2">

                          <div class="col-12 col-md-4">
                            <label for="txtCodigo" class="form-label small mb-1">
                              Tipo de tercero
                            </label>

                            <select
                              id="id_tipo_tercero"
                              name="id_tipo_tercero"
                              class="form-select form-select-sm">
                            </select>
                          </div>


                          <div class="col-12 col-md-8">
                            <label for="txtBusquedaTercero" class="form-label small mb-1">
                              Búsqueda de tercero
                            </label>

                            <div class="input-group input-group-sm input-group-merge">

                              <span class="input-group-text">
                                <i class="bx bx-search"></i>
                              </span>

                              <input
                                type="text"
                                class="form-control"
                                placeholder="Búsqueda de tercero..."
                                aria-label="Búsqueda de tercero..."
                                aria-describedby="basic-addon-search"
                                id="txtBusquedaTercero">

                            </div>
                          </div>


                          <input
                            type="hidden"
                            class="form-control form-control-sm"
                            id="txtCodigo"
                            maxlength="20"
                            placeholder="Código"
                            autocomplete="off"
                            data-bs-original-title="<i class='bx bx-trending-up bx-xs' ></i> <span>Campo automático</span>"
                            data-bs-toggle="tooltip"
                            data-bs-offset="0,4"
                            data-bs-placement="right"
                            data-bs-html="true"
                            readonly>

                        </div>


                        <!-- Acciones -->
                        <div class="row mt-3">

                          <div class="col-12 d-flex flex-wrap gap-2">

                            <button
                              type="reset"
                              class="btn btn-sm btn-outline-secondary"
                              id="btnLimpiar">
                              <i class="fa-regular fa-file me-1"></i>
                              Nuevo
                            </button>


                            <button
                              type="submit"
                              class="btn btn-sm btn-outline-primary"
                              id="btnGuardar">
                              <i class="fa-regular fa-floppy-disk me-1"></i>
                              Guardar
                            </button>


                            <button
                              type="button"
                              class="btn btn-sm btn-outline-info"
                              id="btnAuditoria"
                              disabled>
                              <i class="fa-solid fa-clock-rotate-left me-1"></i>
                              Auditoría
                            </button>


                            <button
                              type="button"
                              class="btn btn-sm btn-outline-danger"
                              id="btn-video-tutorial"
                              title="Ver video tutorial">
                              <i class="fab fa-youtube me-1"></i>
                              Tutorial
                            </button>

                          </div>

                        </div>

                      </div>

                    </div>
                    <div class="card shadow-sm mb-3">

                      <div class="card-header py-2">
                        <h6 class="mb-0 text-primary form-label">
                          <i class="fa-solid fa-file-invoice-dollar me-2"></i>
                          Perfil tributario
                        </h6>
                      </div>

                      <div class="card-body">

                        <div class="row g-2">

                          <div class="col-12 col-lg-4">
                            <label for="id_tipo_identificacion" class="form-label small mb-1">
                              Tipo identificación <span class="text-danger">*</span>
                            </label>
                            <select
                              id="id_tipo_identificacion"
                              name="id_tipo_identificacion"
                              class="form-select form-select-sm"
                              required>
                            </select>
                          </div>

                          <div class="col-8 col-lg-4">
                            <label for="nit" class="form-label small mb-1">
                              NIT <span class="text-danger">*</span>
                            </label>
                            <input
                              type="text"
                              class="form-control form-control-sm"
                              name="nit"
                              id="nit"
                              maxlength="20"
                              placeholder="NIT"
                              autocomplete="off"
                              onKeyPress="return vnumeros(event)"
                              required>
                          </div>

                          <div class="col-4 col-lg-2">
                            <label for="nit_digito" class="form-label small mb-1">
                              DV <span class="text-danger">*</span>
                            </label>
                            <input
                              type="text"
                              class="form-control form-control-sm text-center"
                              name="nit_digito"
                              value="0"
                              readonly
                              id="nit_digito"
                              maxlength="2"
                              size="3"
                              placeholder="Dígito"
                              autocomplete="off"
                              required>
                          </div>

                          <div class="col-12 col-lg-2 d-flex align-items-end">
                            <button
                              type="button"
                              class="btn btn-sm btn-outline-success w-100"
                              id="btnAutocompletar">
                              <i class="fa-solid fa-wand-magic-sparkles me-1"></i>
                              Autocompletar
                            </button>
                          </div>

                        </div>


                        <div class="row g-2 mt-1">

                          <div class="col-12 col-md-4">
                            <label for="id_perfil_tributario" class="form-label small mb-1">
                              Perfil tributario
                            </label>
                            <select
                              id="id_perfil_tributario"
                              name="id_perfil_tributario"
                              class="form-select form-select-sm">
                            </select>
                          </div>

                          <div class="col-12 col-md-4">
                            <label for="id_regimen" class="form-label small mb-1">
                              Régimen
                            </label>
                            <select
                              id="id_regimen"
                              name="id_regimen"
                              class="form-select form-select-sm">
                            </select>
                          </div>

                          <div class="col-12 col-md-4">
                            <label for="email_fe" class="form-label small mb-1">
                              Email FE <span class="text-danger">*</span>
                            </label>
                            <input
                              type="text"
                              class="form-control form-control-sm"
                              name="email_fe"
                              id="email_fe"
                              placeholder="Email factura electrónica"
                              autocomplete="off"
                              required>
                          </div>

                        </div>


                        <div class="border rounded p-2 mt-3">

                          <small class="text-primary fw-semibold d-block mb-2">
                            <i class="fa-solid fa-list-check me-1"></i>
                            Características tributarias
                          </small>

                          <div class="d-flex flex-wrap gap-3">

                            <div class="form-check form-switch">
                              <input
                                class="form-check-input"
                                type="checkbox"
                                id="autorretenedor"
                                name="autorretenedor"
                                value="S" />

                              <label class="form-check-label" for="autorretenedor">
                                Autorretenedor
                              </label>
                            </div>


                            <div class="form-check form-switch">
                              <input
                                class="form-check-input"
                                type="checkbox"
                                id="agente_retenedor"
                                name="agente_retenedor"
                                value="S" />

                              <label class="form-check-label" for="agente_retenedor">
                                Agente retenedor
                              </label>
                            </div>


                            <div class="form-check form-switch">
                              <input
                                class="form-check-input"
                                type="checkbox"
                                id="gran_contribuyente"
                                name="gran_contribuyente"
                                value="S" />

                              <label class="form-check-label" for="gran_contribuyente">
                                Gran contribuyente
                              </label>
                            </div>

                          </div>

                        </div>

                      </div>

                    </div>
                    <div class="card shadow-sm mb-3">

                      <div class="card-header py-2">
                        <h6 class="mb-0 text-primary form-label">
                          <i class="fa-solid fa-user me-2"></i>
                          Generales
                        </h6>
                      </div>

                      <div class="card-body">

                        <div class="row g-2">

                          <div class="col-12 col-md-6">
                            <label for="primer_nombre" class="form-label small mb-1">
                              Primer Nombre <span class="text-danger">*</span>
                            </label>

                            <input
                              type="text"
                              class="form-control form-control-sm text-uppercase"
                              name="primer_nombre"
                              id="primer_nombre"
                              maxlength="20"
                              autocomplete="off"
                              placeholder="Primer nombre"
                              required>
                          </div>


                          <div class="col-12 col-md-6">
                            <label for="segundo_nombre" class="form-label small mb-1">
                              Segundo Nombre
                            </label>

                            <input
                              type="text"
                              class="form-control form-control-sm text-uppercase"
                              name="segundo_nombre"
                              id="segundo_nombre"
                              maxlength="20"
                              autocomplete="off"
                              placeholder="Segundo nombre">
                          </div>

                        </div>


                        <div class="row g-2 mt-1">

                          <div class="col-12 col-md-6">
                            <label for="primer_apellido" class="form-label small mb-1">
                              Primer Apellido <span class="text-danger">*</span>
                            </label>

                            <input
                              type="text"
                              class="form-control form-control-sm text-uppercase"
                              name="primer_apellido"
                              id="primer_apellido"
                              maxlength="20"
                              autocomplete="off"
                              placeholder="Primer apellido"
                              required>
                          </div>


                          <div class="col-12 col-md-6">
                            <label for="segundo_apellido" class="form-label small mb-1">
                              Segundo apellido
                            </label>

                            <input
                              type="text"
                              class="form-control form-control-sm text-uppercase"
                              name="segundo_apellido"
                              id="segundo_apellido"
                              maxlength="20"
                              autocomplete="off"
                              placeholder="Segundo apellido">
                          </div>

                        </div>


                        <div class="row g-2 mt-1">

                          <div class="col-12 col-md-6">
                            <label for="nombres" class="form-label small mb-1">
                              Tratamiento <span class="text-danger">*</span>
                            </label>

                            <input
                              type="text"
                              class="form-control form-control-sm text-uppercase"
                              name="tratamiento"
                              id="tratamiento"
                              autocomplete="off"
                              required
                              readonly
                              placeholder="Tratamiento">
                          </div>


                          <div class="col-12 col-md-6">
                            <label for="txtRazon" class="form-label small mb-1">
                              Razón Comercial <span class="text-danger">*</span>
                            </label>

                            <input
                              type="text"
                              class="form-control form-control-sm text-uppercase"
                              name="razon_comercial"
                              id="razon_comercial"
                              autocomplete="off"
                              placeholder="Razón social/comercial"
                              required>
                          </div>

                        </div>

                      </div>

                    </div>

                  </div>
                </div>
                <div class="row">
                  <div class="col-md-4">
                    <div class="card">
                      <h5 class="card-header text-primary form-label">Condiciones del tercero</h5>
                      <div class="card-body">
                        <label for="cupo_credito" class="form-label">Cupo de crédito <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" value="0" name="cupo_credito" id="cupo_credito" placeholder="Cupo de crédito" autocomplete="off" required>
                        <label for="id_condicion" class="form-label">Condición de pago</label>
                        <select id="id_condicion" name="id_condicion" class="form-select form-select-sm">
                        </select>
                        <label for="id_condicion_pago_prov" class="form-label">Condición de pago proveedor</label>
                        <select id="id_condicion_pago_prov" name="id_condicion_pago_prov" class="form-select form-select-sm">
                        </select>
                        <label for="lista_precios" class="form-label">
                          Lista de precios
                          <button type="button" class="btn btn-sm btn-link p-0 ms-1" data-bs-toggle="tooltip" title="Si NO asigna una lista, se tomará la del almacén.">
                            <i class="fa-solid fa-circle-info"></i>
                          </button>
                        </label>
                        <select id="lista_precios" name="lista_precios" class="form-select form-select-sm">
                        </select>
                      </div>
                    </div>
                    <!--<div class="card" >  -->
                  </div>
                  <div class="col-md-4">
                    <div class="card mb-2 mt-2">
                      <h5 class="card-header text-primary form-label">Comercial</h5>
                      <div class="card-body">
                        <label for="estado" class="form-label">Estado </label>
                        <select id="estado" name="estado" class="form-select form-select-sm">
                          <option value="A">ACTIVO</option>
                          <option value="I">INACTIVO</option>
                        </select>
                        <label for="id_vendedor_principal" class="form-label">Vendedor principal </label>
                        <select id="id_vendedor_principal" name="id_vendedor_principal" class="form-select form-select-sm">
                        </select>
                        <label for="id_vendedor_secundario" class="form-label">Vendedor secundario</label>
                        <select id="id_vendedor_secundario" name="id_vendedor_secundario" class="form-select form-select-sm">
                        </select>
                        <label for="id_zona_ventas" class="form-label">Zona de ventas</label>
                        <select id="id_zona_ventas" name="id_zona_ventas" class="form-select form-select-sm">
                        </select>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="card mb-2 mt-2">
                      <h5 class="card-header text-primary form-label">Control de productos</h5>
                      <div class="card-body">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" value="" id="controlados" name="controlados">
                          <label class="form-check-label" for="controlado"> Controlados </label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" value="" id="institucionales" name="institucionales">
                          <label class="form-check-label" for="institucional"> Institucionales </label>
                        </div>
                      </div>
                    </div>
                    <div class="card mb-2 mt-2">
                      <h5 class="card-header text-primary form-label">Integraciones</h5>
                      <div class="card-body">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" value="" id="edi" name="edi">
                          <label class="form-check-label" for="edi"> Enviar EDI?</label>
                        </div>
                        <label for="ean_edi" class="form-label">EAN <span class="text-danger">*</span></label>
                        <input type="text" value="0" class="form-control form-control-sm" name="ean_edi" id="ean_edi" placeholder="EAN EDI" autocomplete="off" required>
                      </div>
                    </div>
                  </div>
                </div>
              </form>
              <div class="card shadow-sm mb-3">
                <div class="card-header py-2">
                  <h6 class="mb-0 text-primary form-label">
                    <i class="fa-solid fa-location-dot me-2"></i>Direcciones
                  </h6>
                </div>

                <div class="card-body">

                  <!-- País, Departamento, Ciudad -->
                  <div class="row g-2">
                    <div class="col-12 col-lg-4">
                      <label class="form-label small mb-1">País <span class="text-danger">*</span></label>
                      <select id="slcPais" class="form-select form-select-sm"></select>
                    </div>

                    <div class="col-12 col-lg-4">
                      <label class="form-label small mb-1">Departamento <span class="text-danger">*</span></label>
                      <select id="slcDepartamento" class="form-select form-select-sm"></select>
                    </div>

                    <div class="col-12 col-lg-4">
                      <label class="form-label small mb-1">Ciudad <span class="text-danger">*</span></label>
                      <select id="slcCiudad" class="form-select form-select-sm"></select>
                    </div>
                  </div>

                  <!-- Dirección -->
                  <div class="row g-2 mt-1">
                    <div class="col-12">
                      <label class="form-label small mb-1">Dirección <span class="text-danger">*</span></label>
                      <input
                        type="text"
                        id="txtDireccion"
                        class="form-control form-control-sm"
                        placeholder="Ingrese la dirección">
                    </div>
                  </div>

                  <!-- Contacto -->
                  <div class="row g-2 mt-1">

                    <div class="col-12 col-md-4">
                      <label class="form-label small mb-1">Teléfono</label>
                      <input
                        type="text"
                        id="txtTelefono"
                        class="form-control form-control-sm"
                        placeholder="Teléfono">
                    </div>

                    <div class="col-12 col-md-4">
                      <label class="form-label small mb-1">Celular</label>
                      <input
                        type="text"
                        id="txtCelular"
                        class="form-control form-control-sm"
                        placeholder="Celular">
                    </div>

                    <div class="col-12 col-md-4">
                      <label class="form-label small mb-1">Correo</label>
                      <input
                        type="email"
                        id="txtEmail"
                        class="form-control form-control-sm"
                        placeholder="correo@empresa.com">
                    </div>

                  </div>

                  <!-- Botones -->
                  <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 gap-2">

                    <div class="form-check form-switch mb-0">
                      <input class="form-check-input" type="checkbox" id="dir_principal">
                      <label class="form-check-label" for="dir_principal">
                        Dirección principal
                      </label>
                    </div>

                    <button
                      class="btn btn-primary btn-sm"
                      type="button"
                      id="btnAddDireccion">
                      <i class="fa-solid fa-plus me-1"></i>
                      Agregar dirección
                    </button>

                  </div>

                  <hr class="my-3">

                  <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle" id="tbDirecciones">
                      <thead class="table-primary">
                        <tr>
                          <th>País</th>
                          <th>Dpto.</th>
                          <th>Ciudad</th>
                          <th>Dirección</th>
                          <th>Teléfono</th>
                          <th>Celular</th>
                          <th>Email</th>
                          <th class="text-center">Acciones</th>
                          <th class="text-center">Principal</th>
                        </tr>
                      </thead>
                      <tbody></tbody>
                    </table>
                  </div>

                </div>
              </div>
              <div class="card shadow-sm mb-3">
                <div class="card-header py-2">
                  <h6 class="mb-0 text-primary form-label">
                    <i class="fa-solid fa-address-book me-2"></i>Contactos
                  </h6>
                </div>

                <div class="card-body">

                  <!-- Nombre y Apellidos -->
                  <div class="row g-2">

                    <div class="col-12 col-lg-6">
                      <label class="form-label small mb-1">Nombres</label>
                      <input
                        type="text"
                        id="txtContactoNombres"
                        class="form-control form-control-sm"
                        placeholder="Nombres">
                    </div>

                    <div class="col-12 col-lg-6">
                      <label class="form-label small mb-1">Apellidos</label>
                      <input
                        type="text"
                        id="txtContactoApellidos"
                        class="form-control form-control-sm"
                        placeholder="Apellidos">
                    </div>

                  </div>

                  <!-- Teléfono, Celular y Correo -->
                  <div class="row g-2 mt-1">

                    <div class="col-12 col-md-4">
                      <label class="form-label small mb-1">Teléfono</label>
                      <input
                        type="text"
                        id="txtContactoTelefono"
                        class="form-control form-control-sm"
                        placeholder="Teléfono"
                        onkeypress="return vnumeros(event)">
                    </div>

                    <div class="col-12 col-md-4">
                      <label class="form-label small mb-1">Celular</label>
                      <input
                        type="text"
                        id="txtContactoCelular"
                        class="form-control form-control-sm"
                        placeholder="Celular"
                        onkeypress="return vnumeros(event)">
                    </div>

                    <div class="col-12 col-md-4">
                      <label class="form-label small mb-1">Correo electrónico</label>
                      <input
                        type="email"
                        id="txtContactoEmail"
                        class="form-control form-control-sm"
                        placeholder="correo@empresa.com">
                    </div>

                  </div>

                  <!-- Botón -->
                  <div class="d-flex justify-content-end mt-3">
                    <button
                      class="btn btn-primary btn-sm"
                      type="button"
                      id="btnAddContacto">
                      <i class="fa-solid fa-user-plus me-1"></i>
                      Agregar contacto
                    </button>
                  </div>

                  <hr class="my-3">

                  <!-- Tabla -->
                  <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle" id="tbContactos">
                      <thead class="table-primary">
                        <tr>
                          <th>Nombres</th>
                          <th>Apellidos</th>
                          <th>Teléfono</th>
                          <th>Celular</th>
                          <th>Email</th>
                          <th class="text-center">Acciones</th>
                        </tr>
                      </thead>
                      <tbody></tbody>
                    </table>
                  </div>

                </div>
              </div>
              <!--  -->

              <!-- formulario 1  -->
            </div>
            <div class="tab-pane fade" id="navs-listado" role="tabpanel">
              <div class="row">
                <div class="col-12">
                  <div class="row mb-3">
                    <div class="col-md-12">
                      <div class="input-group input-group-merge"> <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input name="" id="filtro" type="text" class="form-control form-control-sm" placeholder="identificación, tratamiento, razón..." aria-label="Buscar..." autocomplete="off" />
                        <button class="btn btn-outline-primary btn-sm" id="buscar">Buscar</button>
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
                <p>El sistema le permitirá subir masivamente los terceros en formato Excel, por favor de click en el botón de información para conocer la estructura de dicho archivo.</p>
                <p class="mb-0"></p>
                <hr>
                <button class="btn btn-outline-secondary btn-sm" type="button" id="btn-plantilla"><i class="fa-regular fa-circle-down"></i>&nbsp;Descargar plantilla excel</button>
              </div>
              <div class="input-group">
                <input type="file" class="form-control form-control-sm" id="txtFileUp">
                <button class="btn btn-outline-primary btn-sm" type="button" id="btnUp"><i class="fa-regular fa-circle-up"></i>&nbsp;Cargar</button>
                <button class="btn btn-outline-secondary btn-sm" type="button" id="btnClean"><i class="fa-regular fa-file"></i>&nbsp;Nuevo</button>
              </div>
              <div class="mt-4" id="tableContainer" style="overflow: auto; max-height: 400px; max-width: 100%;"> </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="modalAuditoriaTercero" aria-hidden="true" data-bs-backdrop="static">
      <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Historico de auditoria del tercero <span id="auditoriaTerceroLabel" class="text-primary"></span></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div id="alertAuditoriaTercero" class="mb-2"></div>
            <div class="row g-2 mb-3">
              <div class="col-md-2">
                <label class="form-label mb-1">Accion</label>
                <select class="form-select form-select-sm" id="filtroAuditoriaTerceroAccion">
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
                <input type="date" class="form-control form-control-sm" id="filtroAuditoriaTerceroDesde">
              </div>
              <div class="col-md-2">
                <label class="form-label mb-1">Fecha hasta</label>
                <input type="date" class="form-control form-control-sm" id="filtroAuditoriaTerceroHasta">
              </div>
              <div class="col-md-3">
                <label class="form-label mb-1">Usuario</label>
                <input type="text" class="form-control form-control-sm" id="filtroAuditoriaTerceroUsuario" placeholder="Usuario...">
              </div>
              <div class="col-md-3">
                <label class="form-label mb-1">Texto en detalle</label>
                <div class="input-group input-group-sm">
                  <input type="text" class="form-control" id="filtroAuditoriaTerceroTexto" placeholder="Buscar en detalle...">
                  <button class="btn btn-outline-secondary" type="button" id="btnLimpiarFiltrosAuditoriaTercero">
                    <i class="fa-solid fa-filter-circle-xmark"></i>
                  </button>
                </div>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-sm table-hover table-striped align-middle" id="tbAuditoriaTercero">
                <thead class="table-primary">
                  <tr>
                    <th>Fecha</th>
                    <th>Usuario</th>
                    <th>Accion</th>
                    <th class="text-center">Detalle</th>
                  </tr>
                </thead>
                <tbody id="tbodyAuditoriaTercero"></tbody>
              </table>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="modalDetalleAuditoriaTercero" aria-hidden="true" data-bs-backdrop="static">
      <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Detalle de cambio</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="detalleAuditoriaTerceroContainer"></div>
          <div class="modal-footer">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
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
      '../controllers/Terceros.js?' . rand()
    ];

    include '../dependencies/footer.php';

    ?>