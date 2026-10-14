<?php session_start(); ?>
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
  <title>Egresos de cajas</title>
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

  <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Administración /</span>Egresos de cajas</h5>
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
          <i class="fa-solid fa-file-circle-plus"></i> &nbsp;Crear
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
          <i class="fa-solid fa-list-ol"></i> &nbsp;Historial
        </button>
      </li>
    </ul>
    <div class="tab-content">
      <div class="tab-pane fade show active" id="navs-creacion" role="tabpanel">
        <form id="FormCrear" class="needs-validation  form-basico" novalidate>

          <div class="row p-2 d-flex d-align-items-center  justify-content-center fle-column">
            <div class="col-md-12" style="width:60vw">
              <div class="mb-1" bis_skin_checked="1">
                <div bis_skin_checked="1">

                  <label for="usuario" class="form-label">Usuario</label>
                  <input type="text" class="form-control form-control-sm text-uppercase" value="<?php echo  $_SESSION["Ses_usuario"]; ?>" id="usuario" readonly />
                  <input type="hidden" value="<?php echo  $_SESSION["Ses_id"]; ?>" id="id_usuario" name="id_usuario" readonly />
                </div>
                <div bis_skin_checked="1">
                  <label for="bodegas" class="form-label">Almacen/Bodega</label>
                  <select class="form-control form-control-sm" id="bodegas" name="bodegas"></select>
                  <input type="hidden" id="bodega_session" value="<?php echo  $_SESSION["Ses_id_bodega"]; ?>" readonly />
                </div>
                <div bis_skin_checked="1">
                  <label for="tipos" class="form-label">Tipo de documento</label>
                  <select class="form-control form-control-sm" id="id_tipos_documentos" name="id_tipos_documentos"></select>
                </div>
                <div class="row">
                  <div class="col-md-5">
                    <label for="nit" class="form-label">Nit</label>
                    <input type="number" class="form-control form-control-sm" id="nit" autocomplete="off" />
                  </div>
                  <div class="col-md-7">
                    <label for="tercero" class="form-label">tercero</label>
                    <input type="text" class="form-control form-control-sm" id="tercero" autocomplete="off" />
                    <input type="hidden" readonly id="id_tercero" value="0" />
                    <input type="hidden" readonly id="id_tercero_dir" value="0" />
                  </div>
                </div>

                <div bis_skin_checked="1">
                  <label for="condicion" class="form-label">Condicion</label>
                  <select class="form-control form-control-sm" name="id_condicion" id="id_condicion"></select>
                </div>
                <div bis_skin_checked="1">
                  <label for="base" class="form-label">Bruto</label>
                  <input type="text" class="form-control form-control-sm valores" name="bruto" id="bruto" autocomplete="off" value="$0" required />
                </div>
                <div bis_skin_checked="1">
                  <label for="iva" class="form-label">Iva</label>
                  <input type="text" class="form-control form-control-sm valores" name="iva" id="iva" autocomplete="off" value="$0" required />
                </div>
                <div bis_skin_checked="1">
                  <label for="total" class="form-label">Total</label>
                  <input type="text" class="form-control form-control-sm valores" name="total" id="total" readonly value="$0" required />
                </div>
                <div bis_skin_checked="1">
                  <label for="notas" class="form-label">Notas</label>
                  <textarea cols="4" rows="3" class="form-control alert-warning" id="notas"></textarea>
                </div>
                <div bis_skin_checked="1" class="mt-3">
                  <button class="btn btn-sm btn-primary guardar" id="guardar" type="button"> <i class="fa-solid fa-floppy-disk"></i> Guardar</button>
                  <button class="btn btn-sm btn-secondary" id="nuevo" type="button"> <i class="fa-solid fa-file"></i> Nuevo</button>
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
  <script src="../controllers/EgresosCajas.js?<?php echo (rand()); ?>"></script>
</body>

</html>