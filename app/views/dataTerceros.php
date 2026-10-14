<!DOCTYPE html>
<html
  lang="es"
  class="light-style layout-menu-fixed"
  dir="ltr"
  data-theme="theme-default"
  data-assets-path="../../assets/"
  data-template="vertical-menu-template-free">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
  <title>Terceros - Data Maestra</title>
  <meta name="description" content="" />
  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />
  <!-- Icons. Uncomment required icon fonts -->
  <link rel="stylesheet" href="../../assets/vendor/fonts/boxicons.css" />
  <!-- Core CSS -->
  <link rel="stylesheet" href="../../assets/vendor/css/core.css" class="template-customizer-core-css" />
  <link rel="stylesheet" href="../../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
  <link rel="stylesheet" href="../../assets/css/demo.css" />
  <link rel="stylesheet" href="../../assets/vendor/css/fontawesome-free-6.1.2-web/css/all.min.css" />
  <link rel="stylesheet" href="../../assets/vendor/css/SwalAlert2/dist/css/sweetalert2.min.css" />
  <!-- Vendors CSS -->
  <link rel="stylesheet" href="../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
  <link rel="stylesheet" href="../../assets/vendor/libs/apex-charts/apex-charts.css" />
  <link rel="stylesheet" href="../../assets/css/tetris-logo.css?<?php echo rand(); ?>" />
  <!-- Page CSS -->
  <!-- Helpers -->
  <script src="../../assets/vendor/js/helpers.js"></script>
  <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
  <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
  <script src="../../assets/js/config.js"></script>
</head>

<body>

  <input type="hidden" readonly id="id" value="0">
  <div class="container-xxl flex-grow-1 container-p-y">
    <h5 class="fw-bold py-0 mb-2"><span class="text-muted fw-light">Datos maestros /</span> Terceros</h5>
    <div class="nav-align-top">
      <ul class="nav nav-tabs" role="tablist" id="borderedTab">
        <li class="nav-item">
          <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-tab1" aria-controls="navs-tab1" aria-selected="true"> Básico &nbsp;<span id="respond_basico" class="respond-icon"><i class="fa-solid fa-spinner fa-spin-pulse text-primary d-none "></i> <i class="fa-solid  fa-times text-danger d-none "></i></span></button>
        </li>
        <li class="nav-item">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-tab2" aria-controls="navs-tab3" aria-selected="false"> Direcciones &nbsp;<span id="respond_direcciones" class="respond-icon"><i class="fa-solid fa-spinner fa-spin-pulse text-primary d-none "></i><i class="fa-solid  fa-times text-danger d-none "></i></span></button>
        </li>
        <li class="nav-item">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-tab3" aria-controls="navs-tab3" aria-selected="false"> Contactos &nbsp;<span id="respond_contactos" class="respond-icon"><i class="fa-solid fa-spinner fa-spin-pulse text-primary d-none "></i><i class="fa-solid fa-times text-danger d-none "></i></span></button>
        </li>
      </ul>
      <form id="FormTerceroBasicos" class="needs-validation  form-basico" novalidate>
        <!--Form1-->
        <div class="tab-content card" bis_skin_checked="1">

          <div class="row">
            <div class="col-md-8">
              <div class="input-group input-group-sm" bis_skin_checked="1"> <span class="input-group-text " id="basic-addon-search"><i class="bx bx-search text-primary"></i></span>
                <input type="text" class="form-control" placeholder="Búsqueda de tercero..." aria-label="Búsqueda de tercero..." aria-describedby="basic-addon-search" id="txtBusquedaTercero">
              </div>
            </div>
            <div class="col-md-4">
              <div class="input-group input-group-sm" bis_skin_checked="1">
                <select id="slcTipoTercero" class="form-select form-select-sm">
                </select>
                <button type="reset" class="btn btn-sm btn-outline-secondary" id="btnLimpiarTercero"> <i class="fa-regular fa-file"></i> Nuevo</button>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btnGuardarTercero"> <i class="fa-regular fa-floppy-disk"></i> Guardar</button>
              </div>
            </div>
          </div>
          <div class="divider divider-primary m-0 p-9" bis_skin_checked="1">
            <div class="divider-text" bis_skin_checked="1"><i class="fa-regular fa-bookmark" style="color: #696cff;"></i></div>
          </div>
          <div class="tab-pane fade active show" id="navs-tab1" role="tabpanel" bis_skin_checked="1">
            <div class="row">
              <div class="col-md-6">
                <div class="card mb-4" bis_skin_checked="1">
                  <h5 class="card-header">Generales</h5>
                  <div class="card-body" bis_skin_checked="1">
                    <div bis_skin_checked="1">
                      <label for="txtCodigo" class="form-label">Código</label>
                      <input type="text" class="form-control form-control-sm" id="txtCodigo" maxlength="20" placeholder="Código" autocomplete="off" data-bs-original-title="<i class='bx bx-trending-up bx-xs' ></i> <span>Campo automático</span>" data-bs-toggle="tooltip" data-bs-offset="0,4" data-bs-placement="right" data-bs-html="true" readonly>
                    </div>
                    <div bis_skin_checked="1">
                      <label for="txtNombres" class="form-label">Nombres</label>
                      <input type="text" class="form-control form-control-sm" name="nombres" id="txtNombres" maxlength="20" autocomplete="off" required>
                    </div>
                    <div bis_skin_checked="1">
                      <label for="txtApellidos" class="form-label">Apellidos</label>
                      <input type="text" class="form-control form-control-sm" name="apellidos" id="txtApellidos" maxlength="20" autocomplete="off" required>
                    </div>
                    <div bis_skin_checked="1">
                      <label for="txtRazon" class="form-label">Razón Comercial</label>
                      <input type="text" class="form-control form-control-sm" name="razon_comercial" id="txtRazon" maxlength="50" autocomplete="off" required>
                    </div>
                  </div>
                </div>
                <div class="card" bis_skin_checked="1">
                  <h5 class="card-header">Control de productos</h5>
                  <div class="card-body" bis_skin_checked="1">
                    <div class="form-check" bis_skin_checked="1">
                      <input class="form-check-input" type="checkbox" value="" id="controlado" name="eps" wfd-id="id12">
                      <label class="form-check-label" for="controlado"> Controlados </label>
                    </div>
                    <div class="form-check" bis_skin_checked="1">
                      <input class="form-check-input" type="checkbox" value="" id="institucional" name="institucional" wfd-id="id12">
                      <label class="form-check-label" for="institucional"> Institucionales </label>
                    </div>
                  </div>
                </div>
                <div class="card mb-4" bis_skin_checked="1">
                  <h5 class="card-header">Condiciones del tercero</h5>
                  <div class="card-body" bis_skin_checked="1">
                    <div bis_skin_checked="1">
                      <label for="txtCupo" class="form-label">Cupo de crédito</label>
                      <input type="text" class="form-control form-control-sm" maxlength="15" id="txtCupo" placeholder="Cupo de crédito" autocomplete="off" required>
                    </div>
                    <div bis_skin_checked="1">
                      <label for="slcCondicion" class="form-label">Condición de pago</label>
                      <select id="slcCondicion" class="form-select form-select-sm">
                      </select>
                    </div>
                    <div bis_skin_checked="1">
                      <label for="slcListaPrecios" class="form-label">Lista de precios</label>
                      <select id="slcListaPrecios" class="form-select form-select-sm">
                      </select>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="card mb-4" bis_skin_checked="1">
                  <h5 class="card-header">Perfil tributario</h5>
                  <div class="card-body" bis_skin_checked="1">
                    <div bis_skin_checked="1">
                      <label for="slcTipoIdentificacion" class="form-label">Tipo identificación</label>
                      <select id="slcTipoIdentificacion" name="id_tipo_identificacion" class="form-select form-select-sm" required>
                      </select>
                    </div>
                    <div bis_skin_checked="1">
                      <label for="txtNit" class="form-label">NIT</label>
                      <div class="row">
                        <div class="col-md-8">
                          <input type="text" class="form-control form-control-sm vld-number" name="nit" id="txtNit" maxlength="20" placeholder="NIT" autocomplete="off" required>
                        </div>
                        <div class="col-md-4">
                          <input type="text" class="form-control form-control-sm" name="digito" value="0" readonly id="txtDigito" maxlength="2" size="3" placeholder="Dígito" autocomplete="off" required>
                        </div>
                      </div>
                    </div>
                    <div bis_skin_checked="1">
                      <label for="slcPerfilTributario" class="form-label">Perfil tributario</label>
                      <select id="slcPerfilTributario" class="form-select form-select-sm">
                      </select>
                    </div>
                    <div bis_skin_checked="1">
                      <label for="slcRegimen" class="form-label">Régimen</label>
                      <select id="slcRegimen" class="form-select form-select-sm">
                      </select>
                    </div>
                    <div bis_skin_checked="1">
                      <small class="text-light fw-semibold d-block"></small>
                      <div class="form-check form-check-inline mt-3">
                        <input class="form-check-input" type="checkbox" id="autorretenedor" name="autorretenedor" value="0" />
                        <label class="form-check-label" for="autorretenedor">Autorretenedor</label>
                      </div>
                      <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="agente_retenedor" name="agente_retenedor" value="0" />
                        <label class="form-check-label" for="agente_retenedor">Agente retenedor</label>
                      </div>
                      <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="gran_contribuyente" name="gran_contribuyente" value="0" />
                        <label class="form-check-label" for="gran_contribuyente">Gran contribuyente</label>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="card mb-4" bis_skin_checked="1">
                  <h5 class="card-header">Comercial</h5>
                  <div class="card-body" bis_skin_checked="1">
                    <div bis_skin_checked="1">
                      <label for="txtVendedor1" class="form-label">Vendedor 1 </label>
                      <input type="text" class="form-control form-control-sm" id="txtVendedor1" placeholder="Vendedor 1" autocomplete="off">
                    </div>
                    <div bis_skin_checked="1">
                      <label for="txtVendedor2" class="form-label">Vendedor 2</label>
                      <input type="text" class="form-control form-control-sm" id="txtVendedor2" placeholder="Vendedor 2" autocomplete="off">
                    </div>
                    <div bis_skin_checked="1">
                      <label for="slcZona" class="form-label">Zona de ventas</label>
                      <select id="slcZona" class="form-select form-select-sm">
                      </select>
                    </div>
                  </div>
                </div>
                <!-- <div class="card mb-4">
             <h5 class="card-header"><i class="fa-solid fa-building"></i>&nbsp;Empresas</h5>
            <div class="card-body" bis_skin_checked="1">
                <small>Seleccione la empresa principal</small>
                <div class="mt-3" id="">
                    <select class="form-select form-select-sm" id="SlcEmpresas"  required >
                    </select>
                </div>
            </div>
          </div>-->
                <!-- <div class="card mb-4">
            <h5 class="card-title">Empresas extendidas</h5>
            <div class="card-body">
              <ul class="list-group " id="empresas_extendidas">

              </ul>
            </div>
          </div>-->
              </div>
            </div>
      </form>
      <!--Form1-->
    </div>
    <div class="tab-pane fade" id="navs-tab2" role="tabpanel" bis_skin_checked="1">
      <!-- formulario 2-->
      <form id="FormTerceroDireccion" class="needs-validation form-direccion mt-3" novalidate>
        <div class="row">
          <div class="col-md-6">
            <div class="card mb-4" bis_skin_checked="1">
              <h5 class="card-header">Dirección</h5>
              <div class="card-body" bis_skin_checked="1">
                <div bis_skin_checked="1">
                  <label for="slcPais" class="form-label">Pais</label>
                  <select id="slcPais" class="form-select form-select-sm" required>
                  </select>
                </div>
                <div bis_skin_checked="1">
                  <label for="slcDepartamento" class="form-label">Departamento</label>
                  <select id="slcDepartamento" name="codigo_dpto" class="form-select form-select-sm" required>
                  </select>
                </div>
                <div bis_skin_checked="1">
                  <label for="slcCiudad" class="form-label">Ciudad</label>
                  <select id="slcCiudad" name="codigo_municipio" class="form-select form-select-sm" required>
                  </select>
                </div>
                <div bis_skin_checked="1">
                  <label for="txtDireccion" class="form-label">Dirección</label>
                  <input type="text" name="direccion" class="form-control form-control-sm" id="txtDireccion" placeholder="Dirección" autocomplete="off" required>
                </div>
                <div bis_skin_checked="1">
                  <label for="txtTelefono" class="form-label">Teléfono </label>
                  <input type="text" name="telefono" class="form-control form-control-sm" id="txtTelefono" placeholder="Teléfono" autocomplete="off">
                </div>
                <div bis_skin_checked="1">
                  <label for="txtCelular" class="form-label">Celular</label>
                  <input type="text" name="celular" class="form-control form-control-sm" id="txtCelular" placeholder="Celular" autocomplete="off">
                </div>
                <div bis_skin_checked="1">
                  <label for="txtEmail" class="form-label">Email</label>
                  <input type="email" name="email" class="form-control form-control-sm" id="txtEmail" placeholder="Email" autocomplete="off" required>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card mb-4" bis_skin_checked="1">
              <h5 class="card-header">Lista de direcciones</h5>
              <div class="card-body" bis_skin_checked="1"></div>
            </div>
          </div>
        </div>
      </form>
      <!-- formulario 2 -->
    </div>
    <div class="tab-pane fade" id="navs-tab3" role="tabpanel" bis_skin_checked="1">
      <!-- formulario 3  -->
      <form id="FormTerceroContacto" class="needs-validation mt-3 form-contacto" novalidate>
        <div class="row">
          <div class="col-md-6">
            <div class="card mb-4" bis_skin_checked="1">
              <h5 class="card-header">Contactos</h5>
              <div class="card-body" bis_skin_checked="1">
                <div bis_skin_checked="1">
                  <label for="txtContactoNombres" class="form-label">Nombres</label>
                  <input type="text" name="nombres" class="form-control form-control-sm" id="txtContactoNombres" placeholder="Nombres" autocomplete="off">
                </div>
                <div bis_skin_checked="1">
                  <label for="txtContactoApellidos" class="form-label">Apellidos</label>
                  <input type="text" name="apellidos" class="form-control form-control-sm" id="txtContactoApellidos" placeholder="Apellidos" autocomplete="off">
                </div>
                <div bis_skin_checked="1">
                  <label for="txtContactoTelefono" class="form-label">Teléfono</label>
                  <input type="text" name="telefono" class="form-control form-control-sm" id="txtContactoTelefono" placeholder="Telefono" autocomplete="off">
                </div>
                <div bis_skin_checked="1">
                  <label for="txtContactoCelular" class="form-label">Celular</label>
                  <input type="text" name="celular" class="form-control form-control-sm" id="txtContactoCelular" placeholder="Celular" autocomplete="off">
                </div>
                <div bis_skin_checked="1">
                  <label for="txtContactoEmail" class="form-label">Email</label>
                  <input type="email" name="email" class="form-control form-control-sm" id="txtContactoEmail" placeholder="Email" autocomplete="off">
                </div>
              </div><!-- <div class="card-body" bis_skin_checked="1"> -->
            </div>
          </div>
      </form>
      <div class="col-md-6">
        <div class="card mb-4" bis_skin_checked="1">
          <h5 class="card-header">Lista contactos</h5>
          <div class="card-body" bis_skin_checked="1"></div>
        </div>
      </div>
    </div>
  </div>
  </div>
  </div>
  </div>
  <!-- Core JS -->
  <!-- build:js assets/vendor/js/core.js -->
  <script src="../../assets/vendor/libs/jquery/jquery.js"></script>
  <script src="../../assets/vendor/libs/popper/popper.js"></script>
  <script src="../../assets/vendor/js/bootstrap.js"></script>
  <script src="../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
  <script src="../../assets/vendor/js/menu.js"></script>
  <script src="../../assets/vendor/css/SwalAlert2/dist/js/sweetalert2.all.min.js"></script>
  <!-- endbuild -->
  <!-- Vendors JS -->
  <script src="../../assets/vendor/libs/apex-charts/apexcharts.js"></script>
  <!-- Main JS -->
  <script src="../../assets/js/main.js"></script>
  <!-- Page JS -->
  <script src="../../assets/js/dashboards-analytics.js"></script>
  <!-- data JS -->
  <script src="../shared/shared.js?<?php echo (rand()); ?>"></script>
  <script>
    showLoadingWithTetrisLogo('Cargando por favor espere...', 'ISA Software')
  </script>
  <script src="../shared/servicios.js?<?php echo (rand()); ?>"></script>
  <script src="../controllers/dataTerceros.js?<?php echo (rand()); ?>"></script>
</body>

</html>