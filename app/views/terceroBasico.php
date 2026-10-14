<!DOCTYPE html>
<html lang="es" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../../assets/" data-template="vertical-menu-template-free">

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
  <!-- Page CSS -->
  <link rel="stylesheet" href="../../assets/vendor/libs/jquery/jquery-ui/jquery-ui.css" />
  <link rel="stylesheet" href="../../assets/css/tetris-logo.css?<?php echo rand(); ?>" />
  <!-- Helpers -->
  <script src="../../assets/vendor/js/helpers.js"></script>
  <script src="../../assets/js/config.js"></script>
</head>

<body>
  <div class="card shadow-sm border-0">
    <div class="card-body">
      <form id="FormTerceroBasicos" class="needs-validation form-basico" novalidate>
        <input type="hidden" class="form-control form-control-sm" id="id_tercero" name="id_tercero" readonly>
        <h6 class="text-primary fw-bold mb-2 border-start border-4 border-primary ps-2">Identificación</h6>
        <div class="row g-3 align-items-end">
          <div class="col-md-3">
            <label for="id_tipo_identificacion" class="form-label campo-obligatorio">Tipo de Identificación</label>
            <select id="id_tipo_identificacion" name="id_tipo_identificacion" class="form-select form-select-sm" required>
            </select>
          </div>
          <div class="col-md-3">
            <label for="nit" class="form-label campo-obligatorio">NIT</label>
            <input type="text" class="form-control form-control-sm" name="nit" id="nit" maxlength="20" placeholder="Ej: 900123456" autocomplete="off" onkeypress="return vnumeros(event)" required>
          </div>
          <div class="col-md-1">
            <label for="nit_digito" class="form-label">DV</label>
            <input type="text" class="form-control form-control-sm" name="nit_digito" value="0" readonly id="nit_digito" maxlength="2" placeholder="00" required>
          </div>
          <div class="col-md-5 d-flex gap-2 align-items-end">
            <button type="button" class="btn btn-sm btn-outline-success flex-grow-1" id="btnAutocompletar">
              <i class="fa-solid fa-wand-magic-sparkles"></i>&nbsp;Autocompletar
            </button>
            <button type="reset" class="btn btn-sm btn-outline-secondary flex-grow-1" id="btnLimpiar">
              <i class="fa-regular fa-file"></i> Nuevo
            </button>
            <button type="submit" class="btn btn-sm btn-outline-primary flex-grow-1" id="btnGuardar">
              <i class="fa-regular fa-floppy-disk"></i> Guardar
            </button>
          </div>
        </div>
        <h6 class="text-primary fw-bold mt-3 mb-2 border-start border-4 border-primary ps-2">Datos Personales</h6>
        <div class="row g-3 align-items-end">
          <div class="col-md-12">
            <label for="razon_comercial" class="form-label">Razón Comercial</label>
            <input type="text" class="form-control form-control-sm text-uppercase" name="razon_comercial" id="razon_comercial" placeholder="Ej: Drogueria la mejor">
          </div>
        </div>
        <div class="row g-3">
          <div class="col-md-3">
            <label for="primer_nombre" class="form-label campo-obligatorio">Primer Nombre</label>
            <input type="text" class="form-control form-control-sm text-uppercase" name="primer_nombre" id="primer_nombre" placeholder="Ej: JUAN" required>
          </div>
          <div class="col-md-3">
            <label for="segundo_nombre" class="form-label">Segundo Nombre</label>
            <input type="text" class="form-control form-control-sm text-uppercase" name="segundo_nombre" id="segundo_nombre" placeholder="Ej: CARLOS">
          </div>
          <div class="col-md-3">
            <label for="primer_apellido" class="form-label campo-obligatorio">Primer Apellido</label>
            <input type="text" class="form-control form-control-sm text-uppercase" name="primer_apellido" id="primer_apellido" placeholder="Ej: PÉREZ" required>
          </div>
          <div class="col-md-3">
            <label for="segundo_apellido" class="form-label">Segundo Apellido</label>
            <input type="text" class="form-control form-control-sm text-uppercase" name="segundo_apellido" id="segundo_apellido" placeholder="Ej: GÓMEZ">
          </div>
        </div>
        <div class="d-none">
          <div class="col-md-6">
            <label for="tratamiento" class="form-label">Tratamiento</label>
            <input type="text" class="form-control form-control-sm text-uppercase" id="tratamiento" name="tratamiento" readonly placeholder="Se genera automáticamente">
          </div>
        </div>
        <h6 class="text-primary fw-bold mt-3 mb-3 border-start border-4 border-primary ps-2">Dirección</h6>
        <div class="row g-3">
          <div class="col-md-2">
            <label for="slcPais" class="form-label">País</label>
            <select id="slcPais" name="slcPais" class="form-select form-select-sm">
            </select>
          </div>
          <div class="col-md-2">
            <label for="slcDepartamento" class="form-label">Departamento</label>
            <select id="slcDepartamento" name="slcDepartamento" class="form-select form-select-sm">
            </select>
          </div>
          <div class="col-md-2">
            <label for="slcCiudad" class="form-label">Ciudad</label>
            <select id="slcCiudad" name="slcCiudad" class="form-select form-select-sm">
            </select>
          </div>
          <div class="col-md-6">
            <label for="direccion" class="form-label campo-obligatorio">Dirección</label>
            <input type="hidden" class="form-control form-control-sm" name="id_direccion" id="id_direccion" value="0">
            <input type="text" class="form-control form-control-sm" name="direccion" id="direccion" placeholder="Ej: Calle 10 #25-36" required>
          </div>
        </div>
        <h6 class="text-primary fw-bold mt-3 mb-3 border-start border-4 border-primary ps-2">Contacto</h6>
        <div class="row g-4">
          <div class="col-md-4">
            <label for="telefono" class="form-label campo-obligatorio">Teléfono</label>
            <input type="text" class="form-control form-control-sm" name="telefono" id="telefono" placeholder="Ej: 6071234567" onkeypress="return vnumeros(event)" required>
          </div>
          <div class="col-md-4">
            <label for="celular" class="form-label campo-obligatorio">Celular</label>
            <input type="text" class="form-control form-control-sm" name="celular" id="celular" placeholder="Ej: 3201234567" onkeypress="return vnumeros(event)" required>
          </div>
          <div class="col-md-4">
            <label for="email_fe" class="form-label campo-obligatorio">Correo Electrónico</label>
            <input type="email" class="form-control form-control-sm" name="email_fe" id="email_fe" placeholder="Ej: cliente@correo.com" required>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Core JS -->
  <!-- build:js assets/vendor/js/core.js -->
  <script src="../../assets/vendor/libs/jquery/jquery.js"></script>
  <script src="../../assets/vendor/libs/jquery/jquery-ui/jquery-ui.js"></script>
  <script src="../../assets/vendor/libs/popper/popper.js"></script>
  <script src="../../assets/vendor/js/bootstrap.js"></script>
  <script src="../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
  <script src="../../assets/vendor/js/menu.js"></script>
  <script src="../../assets/vendor/css/SwalAlert2/dist/js/sweetalert2.all.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/cleave.js/dist/cleave.min.js"></script>
  <!-- endbuild -->
  <!-- Vendors JS -->
  <script src="../../assets/vendor/libs/apex-charts/apexcharts.js"></script>
  <script src="../../libs/DataTable/datatables.min.js"></script>
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
  <!-- Excel JS -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
  <script src="../controllers/terceroBasico.js?<?php echo (rand()); ?>"></script>
</body>

</html>