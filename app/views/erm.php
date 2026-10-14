    <?php
    session_start();

    $titulo = 'E.R.M Enterprise Relationship Management';
    include '../dependencies/header.php';
    ?>
    <div class="card">
      <div class="card-body">
        <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Financiero /</span> E.R.M Enterprise Relationship Management</h5>
        <div class="card mt-3 mb-3">
          <div class="card-body">
            <div class="row g-2">
              <!-- Tipo tercero -->
              <div class="col-12 col-md-4">
                <label for="id_tipo_tercero" class="form-label mb-0">
                  Tipo tercero
                </label>
                <select id="id_tipo_tercero"
                  name="id_tipo_tercero"
                  class="form-select form-select-sm">
                </select>
              </div>
              <!-- Búsqueda -->
              <div class="col-12 col-md-8">
                <label for="txtBusquedaTercero" class="form-label mb-0">
                  Búsqueda
                </label>
                <div class="input-group input-group-sm input-group-merge">
                  <span class="input-group-text">
                    <i class="bx bx-search"></i>
                  </span>
                  <input type="text"
                    class="form-control"
                    placeholder="Búsqueda de tercero..."
                    aria-label="Búsqueda de tercero..."
                    aria-describedby="basic-addon-search"
                    id="txtBusquedaTercero">
                </div>
              </div>
            </div>
            <!-- Botón -->
            <div class="mt-3">
              <button type="button"
                class="btn btn-outline-primary btn-sm"
                id="btn-nuevo">
                <i class="fa-solid fa-arrow-rotate-right me-1"></i>
                Nuevo
              </button>
            </div>
          </div>
        </div>
        <div class="nav-align-top">
          <ul class="nav nav-tabs" role="tablist" id="tabs">
            <li class="nav-item">
              <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#dvTercero" aria-controls="dvTercero" aria-selected="true"> <i class="fa-solid fa-user"></i> &nbsp;Tercero </button>
            </li>
            <li class="nav-item">
              <button class="nav-link" data-bs-toggle="tab" data-bs-target="#dvDocumentos" type="button" role="tab" aria-controls="home" aria-selected="true"> <i class="fa-solid fa-file"></i> Documentos </button>
            </li>
          </ul>
          <div class="tab-content">
            <div class="tab-pane fade show active" id="dvTercero" role="tabpanel">
              <input id="tId" type="hidden" class="form-control form-control-sm" disabled>
              <div class="row">
                <div class="col-12 col-lg-5">
                  <div class="card mt-3">
                    <div class="card-body">
                      <div class="d-flex justify-content-between align-items-center mb-3">
                        <small class="fw-bold text-primary text-uppercase"
                          style="font-size: 0.7rem; letter-spacing: 1px;">
                          Datos básicos
                        </small>
                      </div>
                      <div class="row g-3">
                        <div class="col-12 data-field">
                          <label for="tRazon" class="form-label">
                            Razón Comercial
                          </label>
                          <input id="tRazon"
                            class="form-control form-control-sm"
                            disabled>
                        </div>
                        <div class="col-12 data-field">
                          <label for="tNombres" class="form-label">
                            Nombres Contacto
                          </label>
                          <input id="tNombres"
                            class="form-control form-control-sm"
                            disabled>
                        </div>
                        <div class="col-12 data-field">
                          <label for="tDireccion" class="form-label">
                            Dirección
                          </label>
                          <input id="tDireccion"
                            class="form-control form-control-sm"
                            disabled>
                        </div>
                        <div class="col-12 data-field">
                          <label for="tTelefono" class="form-label">
                            Teléfono
                          </label>
                          <input id="tTelefono"
                            class="form-control form-control-sm"
                            disabled>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="card mt-3">
                    <div class="card-body">
                      <div class="d-flex justify-content-between align-items-center mb-3">
                        <small class="fw-bold text-primary text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px;">
                          Comercial
                        </small>
                      </div>
                      <div class="mb-3">
                        <label for="id_vendedor_principal" class="form-label">
                          Vendedor principal
                        </label>
                        <select id="id_vendedor_principal"
                          name="id_vendedor_principal"
                          class="form-select form-select-sm">
                        </select>
                      </div>
                      <div class="mb-3">
                        <label for="id_vendedor_secundario" class="form-label">
                          Vendedor secundario
                        </label>
                        <select id="id_vendedor_secundario"
                          name="id_vendedor_secundario"
                          class="form-select form-select-sm">
                        </select>
                      </div>
                      <div class="mb-0">
                        <label for="id_zona_ventas" class="form-label">
                          Zona de ventas
                        </label>
                        <select id="id_zona_ventas"
                          name="id_zona_ventas"
                          class="form-select form-select-sm">
                        </select>
                      </div>
                    </div>
                  </div>

                  <div class="card mt-3">
                    <div class="card-body">
                      <div class="d-flex justify-content-between align-items-center mb-3">
                        <small class="fw-bold text-primary text-uppercase"
                          style="font-size: 0.7rem; letter-spacing: 1px;">
                          Perfil Tributario
                        </small>
                      </div>
                      <div class="row g-2">
                        <div class="col-8">
                          <label for="tNit" class="form-label mb-0">
                            NIT
                          </label>
                          <input id="tNit"
                            class="form-control form-control-sm"
                            disabled>
                        </div>
                        <div class="col-2">
                          <label for="nit_digito" class="form-label mb-0">
                            DV
                          </label>
                          <input type="text"
                            class="form-control form-control-sm"
                            name="nit_digito"
                            value="0"
                            readonly
                            id="nit_digito"
                            maxlength="2"
                            size="3"
                            placeholder="Dígito"
                            autocomplete="off">
                        </div>
                        <div class="col-2">
                          <label for="id_tipo_identificacion" class="form-label mb-0">
                            Tipo
                          </label>
                          <select id="id_tipo_identificacion"
                            name="id_tipo_identificacion"
                            class="form-select form-select-sm">
                          </select>
                        </div>
                      </div>
                    </div>
                  </div>

                </div>
                <div class="col-12 col-lg-7">
                  <div class="card mt-3">
                    <div class="card-body">
                      <div class="d-flex justify-content-between align-items-center mb-3">
                        <small class="fw-bold text-primary text-uppercase"
                          style="font-size: 0.7rem; letter-spacing: 1px;">
                          Estado financiero
                        </small>
                      </div>
                      <div class="row g-3">
                        <!-- Gráfico -->
                        <div class="col-12 col-md-8">
                          <div id="CarteraChart" class="echart"></div>
                        </div>
                        <!-- Información financiera -->
                        <div class="col-12 col-md-4">
                          <!-- Cupo Total -->
                          <div class="d-flex align-items-center p-3 rounded shadow-sm"
                            style="background-color: #f0f4ff; border: 1px solid #d0d9ff;">
                            <i class="fas fa-dollar-sign fa-lg text-primary me-3"></i>
                            <div class="flex-grow-1">
                              <small class="text-muted d-block">Cupo Total</small>
                              <span id="tCupo"
                                class="fw-bold fs-5 text-primary"></span>
                            </div>
                          </div>
                          <!-- Condición -->
                          <div class="d-flex align-items-center mt-3 p-3 rounded shadow-sm"
                            style="background-color: #f0f4ff; border: 1px solid #d0d9ff;">
                            <i class="fas fa-info-circle fa-lg text-primary me-3"></i>
                            <div class="flex-grow-1">
                              <small class="text-muted d-block">Condición</small>
                              <span id="tCondicion"
                                class="fw-bold fs-5 text-primary"></span>
                            </div>
                          </div>
                          <!-- Lista -->
                          <div class="d-flex align-items-center mt-3 p-3 rounded shadow-sm"
                            style="background-color: #f0f4ff; border: 1px solid #d0d9ff;">
                            <i class="fas fa-list fa-lg text-primary me-3"></i>
                            <div class="flex-grow-1">
                              <small class="text-muted d-block">Lista</small>
                              <span id="tLista"
                                class="fw-bold fs-5 text-primary"></span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="card mt-3 shadow-sm">
                    <div class="card-body">
                      <div class="d-flex justify-content-between align-items-center mb-3">
                        <small class="fw-bold text-primary text-uppercase"
                          style="font-size: 0.7rem; letter-spacing: 1px;">
                          Comportamiento comercial año actual
                        </small>
                      </div>
                      <div id="VentasChart" class="echart"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="tab-pane fade" id="dvDocumentos" role="tabpanel">
              <div class="row" style="margin-bottom: 20px">
                <div class="col-md-3">
                  <label class="form-label m-1">Año</label>
                  <select id="SAno" name="SAno" class="form-select form-select-sm bg-light">
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label m-1">Tipo de Partida</label>
                  <select class="form-select form-select-sm bg-light" id="Stipo">
                    <option value="S">SIN COMPENSAR</option>
                    <option value="C">COMPENSADAS</option>
                  </select>
                </div>
              </div>
              <div id="Result" class="table-container"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php

    $scriptsPagina = [
      '../controllers/erm.js?' . rand()
    ];

    include '../dependencies/footer.php';

    ?>