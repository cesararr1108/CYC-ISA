<!DOCTYPE html>
<html lang="es" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../../assets/" data-template="vertical-menu-template-free">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
  <title>Configuraciones del Sistema</title>
  <meta name="description" content="" />
  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />
  <!-- Icons -->
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
  <!-- Helpers -->
  <script src="../../assets/vendor/js/helpers.js"></script>
  <script src="../../assets/js/config.js"></script>
  <link rel="stylesheet" href="../../assets/vendor/libs/select2/select2.css" />
  <style>
    .swal2-container {
      z-index: 20000 !important;
    }
  </style>
</head>

<body>
  <!-- Layout wrapper -->
  <div class="layout-wrapper d-flex flex-column">
    <!-- Content wrapper -->
    <div class="content-wrapper">
      <!-- Content -->
      <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
          <div class="col-lg-12">
            <!-- Encabezado -->
            <div class="card mb-4">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                  <i class="fa-solid fa-sliders me-2"></i>Configuraciones del Sistema
                </h5>
                <button class="btn btn-sm btn-primary" id="btnNueva" title="Nueva configuración">
                  <i class="fa-solid fa-plus me-1"></i>Nueva
                </button>
              </div>
            </div>

            <!-- Formulario -->
            <div class="card mb-4" id="formularioContainer" style="display: none;">
              <div class="card-header bg-light">
                <h6 class="mb-0">
                  <span id="formTitulo">Nueva Configuración</span>
                </h6>
              </div>
              <div class="card-body">
                <form id="formConfig" class="needs-validation" novalidate>
                  <input type="hidden" id="idConfig" value="0">
                  
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label for="idModuloSelect" class="form-label">Módulo <span class="text-danger">*</span></label>
                      <select id="idModuloSelect" class="form-select form-select-sm" required>
                        <option value="">Seleccionar módulo</option>
                      </select>
                      <div class="invalid-feedback">Por favor selecciona un módulo.</div>
                    </div>

                    <div class="col-md-6 mb-3">
                      <label for="descripcionConfig" class="form-label">Descripción <span class="text-danger">*</span></label>
                      <input type="text" class="form-control form-control-sm" id="descripcionConfig" placeholder="Describe esta configuración" required>
                      <div class="invalid-feedback">La descripción es requerida.</div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label for="claveConfig" class="form-label">Clave <span class="text-danger">*</span></label>
                      <input type="text" class="form-control form-control-sm" id="claveConfig" placeholder="Ej: HABILITAR_FEATURE_X" required>
                      <small class="text-muted">Identificador único de la configuración</small>
                      <div class="invalid-feedback">La clave es requerida.</div>
                    </div>

                    <div class="col-md-6 mb-3">
                      <label for="valorConfig" class="form-label">Valor <span class="text-danger">*</span></label>
                      <input type="text" class="form-control form-control-sm" id="valorConfig" placeholder="Valor de la configuración" required>
                      <small class="text-muted">Puede ser texto, número o valores separados por comas</small>
                      <div class="invalid-feedback">El valor es requerido.</div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-12">
                      <button type="submit" class="btn btn-sm btn-success me-2" id="btnGuardar">
                        <i class="fa-solid fa-save me-1"></i>Guardar
                      </button>
                      <button type="button" class="btn btn-sm btn-secondary" id="btnCancelar">
                        <i class="fa-solid fa-times me-1"></i>Cancelar
                      </button>
                    </div>
                  </div>
                </form>
              </div>
            </div>

            <!-- Filtros y búsqueda -->
            <div class="card mb-4">
              <div class="card-header">
                <h6 class="mb-0">Filtros</h6>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6 mb-2">
                    <label class="form-label">Buscar por Módulo</label>
                    <select id="filtroModulo" class="form-select form-select-sm">
                      <option value="">Todos los módulos</option>
                    </select>
                  </div>
                  <div class="col-md-6 mb-2">
                    <label class="form-label">Buscar por Descripción o Clave</label>
                    <input type="text" class="form-control form-control-sm" id="filtroBusqueda" placeholder="Escribe para filtrar...">
                  </div>
                </div>
              </div>
            </div>

            <!-- Tabla de configuraciones -->
            <div class="card">
              <div class="card-header">
                <h6 class="mb-0">Configuraciones Registradas</h6>
              </div>
              <div class="card-body" style="overflow-x: auto;">
                <div id="result"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- / Content -->
    </div>
  </div>
  <!-- / Layout wrapper -->

  <!-- Scripts -->
  <script src="../../assets/vendor/libs/jquery/jquery.js"></script>
  <script src="../../assets/vendor/libs/popper/popper.js"></script>
  <script src="../../assets/vendor/js/bootstrap.js"></script>
  <script src="../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
  <script src="../../assets/vendor/js/menu.js"></script>
  <script src="../../assets/vendor/css/SwalAlert2/dist/js/sweetalert2.all.min.js"></script>
  <script src="../../assets/vendor/libs/apex-charts/apexcharts.js"></script>
  <script src="../../assets/js/main.js"></script>
  <script src="../../libs/DataTable/datatables.min.js"></script>
  <script src="../shared/servicios.js"></script>
  <script src="../shared/shared.js"></script>
  <script src="../controllers/Config.js"></script>
</body>

</html> 
<script async src="../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script> 
<script src="../../assets/vendor/js/menu.js"></script> 
<script src="../../assets/vendor/css/SwalAlert2/dist/js/sweetalert2.all.min.js"></script> 
<script async src="../../assets/js/main.js"></script> 
<script async src="../../libs/DataTable/datatables.min.js"></script> 
<script src="../shared/shared.js?<?php  echo(rand()); ?>"></script> 
<script>
  showLoadingSwalAlert2('  <i class="fa-solid fa-spinner text-primary fa-spin-pulse"></i> Cargando información','<span class="text-primary">Por favor espere!</span>',false)
</script>
<script src="../shared/servicios.js?<?php  echo(rand()); ?>"></script>
<script src="../controllers/Config.js?<?php  echo(rand()); ?>"></script>
</body>
</html>
