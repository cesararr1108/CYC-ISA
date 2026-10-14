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
  <title>Roles empresas</title>
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
  <link rel="stylesheet" href="../../libs/DataTable/datatables.min.css" />
  <link rel="stylesheet" href="../../assets/css/tetris-logo.css?<?php echo rand(); ?>" />
  <!-- Page CSS -->
  <!-- Helpers -->
  <script src="../../assets/vendor/js/helpers.js"></script>
  <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
  <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
  <script src="../../assets/js/config.js"></script>
</head>

<body>

  <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Administración /</span> Roles empresas</h5>
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
          <i class="fa-solid fa-file-circle-plus"></i> &nbsp;Creación
        </button>
      </li>
      <li class="nav-item">
        <button
          type="button"
          class="nav-link"
          role="tab"
          data-bs-toggle="tab"
          data-bs-target="#navs-listado"
          aria-controls="navs-listado"
          aria-selected="false">
          <i class="fa-solid fa-list-ol"></i> &nbsp;Listado
        </button>
      </li>
    </ul>
    <div class="tab-content">
      <div class="tab-pane fade show active" id="navs-creacion" role="tabpanel">
        <form id="FormCrear" class="needs-validation  form-basico" novalidate>
          <div class="row">
            <div class="col-md-12">
              <div class="mb-1">
                <div class="row">
                  <div class="col-12">
                    <div class="demo-inline-spacing d-flex justify-content-end">
                      <button class="btn btn-sm btn-light border-1 border border-light" type="button" id="btnNuevo">Nuevo</button>
                      <button class="btn btn-sm btn-primary  border-1 border border-primary" type="submit" id="btnGuardar">Guardar</button>
                    </div>
                  </div>
                </div>
                <div class="row mb-3">
                  <div class="col-12">
                    <label for="txtId" class="form-label">Id</label>
                    <input type="text" class="form-control form-control-sm" value="" id="txtId" name="id" autocomplete="off" readonly>
                  </div>
                </div>
                <div class="row">
                  <div class="col-6">
                    <label for="txtPermisos" class="form-label w-100">Empresas
                      <div class="input-group">
                        <span class="input-group-text" id="basic-addon11">
                          <i class="fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input
                          id="txtEmpresas"
                          type="text"
                          class="form-control"
                          aria-describedby="basic-addon11" />
                      </div>
                    </label>
                    <select
                      required
                      multiple
                      class="form-select form-select-sm"
                      id="slcEmpresas"
                      name="empresa_id"></select>
                  </div>
                  <div class="col-6">
                    <label for="txtRoles" class="form-label w-100">Roles
                      <div class="input-group">
                        <span class="input-group-text" id="basic-addon11">
                          <i class="fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input
                          id="txtRoles"
                          type="text"
                          class="form-control"
                          aria-describedby="basic-addon11" />
                      </div>
                    </label>
                    <select
                      multiple
                      class="form-select form-select-sm"
                      id="slcRoles"
                      name="rol_id"
                      required></select>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="tab-pane fade" id="navs-listado" role="tabpanel">
        <div class="row">
          <div class="col-12">
            <div class="row mb-3">
              <div class="col-9">
                <div class="input-group input-group-merge">
                  <span class="input-group-text"><i class="bx bx-search"></i></span>
                  <input
                    name=""
                    id="filtro"
                    type="text"
                    class="form-control form-control-sm"
                    placeholder="permiso,modulo..."
                    aria-label="Search..." />
                </div>

              </div>
              <div class="col-3">
                <button class="btn btn-primary btn-sm" id="buscar">Buscar </button>
              </div>
            </div>
            <div class="row">
              <div class="col-12">
                <div id="result"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div><!--    <div class="tab-content">  -->
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
  <script src="../../libs/DataTable/datatables.min.js"></script>
  <!-- data JS -->
  <script src="../shared/shared.js?<?php echo (rand()); ?>"></script>
  <script>
    showLoadingWithTetrisLogo('Cargando por favor espere...', 'ISA Software')
  </script>
  <script src="../shared/servicios.js?<?php echo (rand()); ?>"></script>
  <script src="../controllers/RolesEmpresas.js?<?php echo (rand()); ?>"></script>
</body>

</html>