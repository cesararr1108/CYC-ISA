<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Registro paso a paso</title>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
  <title>Empresas - Data Maestra</title>
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
  <!-- Vendors CSS -->
  <link rel="stylesheet" href="../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
  <link rel="stylesheet" href="../../assets/vendor/libs/apex-charts/apex-charts.css" />
  <!-- Page CSS -->
  <link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" type="text/css" />
  <link rel="stylesheet" href="../../assets/vendor/css/select2/select2.css" />
  <link rel="stylesheet" href="../../assets/vendor/css/SwalAlert2/dist/css/sweetalert2.min.css" />
  <link rel="stylesheet" href="../../assets/css/tetris-logo.css?<?php echo rand(); ?>" />
  <!-- Helpers -->
  <script src="../../assets/vendor/js/helpers.js"></script>
  <script src="../../assets/js/config.js"></script>
  <style>
    .stepwizard {
      display: flex;
      justify-content: center;
      margin-bottom: 20px;
    }

    .stepwizard-step {
      text-align: center;
      flex: 1;
    }

    .stepwizard-step .btn {
      border-radius: 50%;
      width: 40px;
      height: 40px;
      padding: 6px 0;
    }

    .stepwizard-step p {
      margin-top: 10px;
    }

    .hidden {
      display: none;
    }
  </style>
</head>

<body class="bg-light">
  <div class="container mt-3">
    <h3 class="mb-4 text-center text-primary fw-bold"> 🎉 ¡Bienvenido(a)! <br>
    </h3>
    <h5 class="mb-4 text-center text-secondary"> En CYC Technology estamos felices de que nos elijas, te guiaremos en tu configuración inicial.</h5>
    <!-- Indicadores -->
    <div class="stepwizard mb-4">
      <div class="stepwizard-step">
        <button type="button" class="btn btn-primary btn-circle" disabled>1</button>
        <p>Empresa</p>
      </div>
      <div class="stepwizard-step">
        <button type="button" class="btn btn-secondary btn-circle" disabled>2</button>
        <p>Representante</p>
      </div>
      <div class="stepwizard-step">
        <button type="button" class="btn btn-secondary btn-circle" disabled>3</button>
        <p>Referencias</p>
      </div>
      <div class="stepwizard-step">
        <button type="button" class="btn btn-secondary btn-circle" disabled>4</button>
        <p>Inventario</p>
      </div>
      <div class="stepwizard-step">
        <button type="button" class="btn btn-secondary btn-circle" disabled>5</button>
        <p>Terceros</p>
      </div>
    </div>
    <!-- Paso 1 -->
    <div class="card step" id="step-1">
      <div class="card-body">
        <h5 class="text-primary">Datos de la empresa</h5>
        <div class="alert alert-primary" role="alert"><i class="fa-solid fa-circle-info"></i> Luego podrás modificar estos datos en el menú <b>Maestra->Empresas</b></div>
        <form id="form-empresa">
          <div class="row">
            <div class="col-md-12">
              <label for="txtRazon" class="form-label campo-obligatorio">Razón Comercial</label>
              <input type="text" class="form-control form-control-sm text-uppercase" name="razon_comercial" id="razon_comercial" autocomplete="off" placeholder="Razón social/comercial" required>
            </div>
          </div>
          <div class="row">
            <div class="col-md-5">
              <label for="id_tipo_identificacion campo-obligatorio" class="form-label">Tipo identificación</label>
              <select id="id_tipo_identificacion" name="id_tipo_identificacion" class="form-select form-select-sm" required>
              </select>
            </div>
            <div class="col-md-5">
              <label for="nit" class="form-label campo-obligatorio">NIT</label>
              <input type="text" class="form-control form-control-sm" name="nit" id="nit" maxlength="20" placeholder="NIT" autocomplete="off" onKeyPress="return vnumeros(event)" required>
            </div>
            <div class="col-md-2">
              <label for="nit_digito" class="form-label">DV</label>
              <input type="text" class="form-control form-control-sm" name="nit_digito" value="0" readonly id="nit_digito" maxlength="2" size="3" placeholder="Dígito" autocomplete="off" required>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4">
              <label for="codigo_pais" class="form-label campo-obligatorio">Pais</label>
              <select id="codigo_pais" name="codigo_pais" class="form-select form-select-sm">
              </select>
            </div>
            <div class="col-md-4">
              <label for="codigo_dpto" class="form-label campo-obligatorio">Departamento</label>
              <select id="codigo_dpto" name="codigo_dpto" class="form-select form-select-sm">
              </select>
            </div>
            <div class="col-md-4">
              <label for="codigo_mpio" class="form-label campo-obligatorio">Ciudad</label>
              <select id="codigo_mpio" name="codigo_mpio" class="form-select form-select-sm">
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4">
              <label for="direccion" class="form-label campo-obligatorio">Dirección</label>
              <input type="text" name="direccion" class="form-control form-control-sm" id="direccion" placeholder="direccion" required>
            </div>
            <div class="col-md-4">
              <label for="telefono" class="form-label campo-obligatorio">Teléfono </label>
              <input type="text" name="telefono" class="form-control form-control-sm" id="telefono" placeholder="telefono" autocomplete="off" onKeyPress="return vnumeros(event)" required>
            </div>
            <div class="col-md-4">
              <label for="email" class="form-label campo-obligatorio">Email</label>
              <input type="email" class="form-control form-control-sm" id="correo" name="correo" placeholder="Email" autocomplete="off" required>
            </div>
          </div>
          <br>
          <button type="button" class="btn btn-outline-primary btn-sm next ">Siguiente <i class="fa-solid fa-right-long"></i></button>
        </form>
      </div>
    </div>

    <!-- Paso 2 -->
    <div class="card step hidden" id="step-2">
      <div class="card-body">
        <h5 class="text-primary">Datos del representante legal</h5>
        <div class="alert alert-primary" role="alert"><i class="fa-solid fa-circle-info"></i> Luego podrás modificar estos datos en el menú <b>Maestra->Terceros</b></div>
        <form id="form-representante">
          <div class="row">
            <div class="col-md-5">
              <label for="nombres" class="form-label campo-obligatorio">Representante legal</label>
              <input type="text" class="form-control form-control-sm text-uppercase" name="representante_legal" id="representante_legal" autocomplete="off" required placeholder="Representante legal">
            </div>
            <div class="col-md-5">
              <label for="nit_rl" class="form-label">NIT</label>
              <input type="text" class="form-control form-control-sm" name="nit_rl" id="nit_rl" maxlength="20" placeholder="NIT" autocomplete="off" onKeyPress="return vnumeros(event)" required>
            </div>
            <div class="col-md-2">
              <label for="nit_digito_rl" class="form-label">DV</label>
              <input type="text" class="form-control form-control-sm" name="nit_digito_rl" value="0" readonly id="nit_digito_rl" maxlength="2" size="3" placeholder="Dígito" autocomplete="off" required>
            </div>
          </div>
          <div class="row">
            <div class="col-md-3">
              <label for="primer_nombre" class="form-label">Primer Nombre</label>
              <input type="text" class="form-control form-control-sm text-uppercase" name="primer_nombre" id="primer_nombre" maxlength="20" autocomplete="off" placeholder="Primer nombre" required>
            </div>
            <div class="col-md-3">
              <label for="segundo_nombre" class="form-label">Segundo Nombre</label>
              <input type="text" class="form-control form-control-sm text-uppercase" name="segundo_nombre" id="segundo_nombre" maxlength="20" autocomplete="off" placeholder="Segundo nombre">
            </div>
            <div class="col-md-3">
              <label for="primer_apellido" class="form-label">Primer Apellido</label>
              <input type="text" class="form-control form-control-sm  text-uppercase" name="primer_apellido" id="primer_apellido" maxlength="20" autocomplete="off" placeholder="Primer apellido" required>
            </div>
            <div class="col-md-3">
              <label for="segundo_apellido" class="form-label">Segundo apellido</label>
              <input type="text" class="form-control form-control-sm text-uppercase" name="segundo_apellido" id="segundo_apellido" maxlength="20" autocomplete="off" placeholder="Segundo apellido">
            </div>
          </div>
          <br>
          <button type="button" class="btn btn-outline-secondary btn-sm prev"><i class="fa-solid fa-left-long"></i> Atrás</button>
          <button type="button" class="btn btn-outline-primary next btn-sm">Siguiente <i class="fa-solid fa-right-long"></i></button>
        </form>
      </div>
    </div>

    <!-- Paso 3 -->
    <div class="card step hidden" id="step-3">
      <div class="card-body">
        <h5 class="text-primary">Carga de referencias</h5>
        <div class="alert alert-primary" role="alert"><i class="fa-solid fa-circle-info"></i> Luego podrás modificar estos datos en el menú <b>Maestra->Referencias</b></div>
        <div class="alert alert-primary border border-primary" role="alert" style="background-color: transparent;">
          <p>El sistema le permitirá subir masivamente las referencias en formato Excel, por favor de click en el botón de <b>(Descargar plantilla)</b> para conocer la estructura de dicho archivo.</p>
          <p class="mb-0"></p>
          <hr>
          <button class="btn btn-outline-secondary btn-sm" type="button" id="btn-plantilla-ref"><i class="fa-regular fa-circle-down"></i>&nbsp;Descargar plantilla </button>
        </div>
        <form id="form-referencias">
          <div class="input-group">
            <input type="file" class="form-control form-control-sm" id="txtFileUp-referencia">
            <button class="btn btn-outline-secondary btn-sm" type="button" id="btnClean-referencia"><i class="fa-regular fa-file"></i>&nbsp;Nuevo</button>
          </div>
          <br>
          <button type="button" class="btn btn-outline-secondary btn-sm prev"><i class="fa-solid fa-left-long"></i> Atrás</button>
          <button type="button" class="btn btn-outline-primary next btn-sm">Siguiente <i class="fa-solid fa-right-long"></i></button>
        </form>
      </div>
    </div>

    <!-- Paso 4 -->
    <div class="card step hidden" id="step-4">
      <div class="card-body">
        <h5 class="text-primary">Carga de inventario</h5>
        <div class="alert alert-primary" role="alert"><i class="fa-solid fa-circle-info"></i> Luego podrás modificar estos datos en el menú <b>Inventarios->Entradas de inventario</b></div>
        <div class="alert alert-primary border border-primary" role="alert" style="background-color: transparent;">
          <p>El sistema le permitirá subir masivamente tu inventario en formato Excel, por favor de click en el botón de <b>(Descargar plantilla)</b> para conocer la estructura de dicho archivo.</p>
          <p class="mb-0"></p>
          <hr>
          <button class="btn btn-outline-secondary btn-sm" type="button" id="btn-plantilla-inv"><i class="fa-regular fa-circle-down"></i>&nbsp;Descargar plantilla</button>
        </div>
        <form id="form-inventario">
          <div class="input-group">
            <input type="file" class="form-control form-control-sm" id="txtFileUp-inventario">
            <button class="btn btn-outline-secondary btn-sm" type="button" id="btnClean-inventario"><i class="fa-regular fa-file"></i>&nbsp;Nuevo</button>
          </div>
          <br>
          <button type="button" class="btn btn-outline-secondary btn-sm prev"><i class="fa-solid fa-left-long"></i> Atrás</button>
          <button type="button" class="btn btn-outline-primary next btn-sm">Siguiente <i class="fa-solid fa-right-long"></i></button>
        </form>
      </div>
    </div>

    <!-- Paso 5 -->
    <div class="card step hidden" id="step-5">
      <div class="card-body">
        <h5 class="text-primary">Carga de terceros</h5>
        <div class="alert alert-primary" role="alert"><i class="fa-solid fa-circle-info"></i> Luego podrás modificar estos datos en el menú <b>Maestra->Terceros</b></div>
        <div class="alert alert-primary border border-primary" role="alert" style="background-color: transparent;">
          <p>El sistema le permitirá subir masivamente tu inventario en formato Excel, por favor de click en el botón de <b>(Descargar plantilla)</b> para conocer la estructura de dicho archivo.</p>
          <p class="mb-0"></p>
          <hr>
          <button class="btn btn-outline-secondary btn-sm" type="button" id="btn-plantilla-ter"><i class="fa-regular fa-circle-down"></i>&nbsp;Descargar plantilla</button>
        </div>
        <form id="form-terceros">
          <div class="input-group">
            <input type="file" class="form-control form-control-sm" id="txtFileUp-terceros">
            <button class="btn btn-outline-secondary btn-sm" type="button" id="btnClean-terceros"><i class="fa-regular fa-file"></i>&nbsp;Nuevo</button>
          </div>
          <br>
          <button type="button" class="btn btn-outline-secondary btn-sm prev"><i class="fa-solid fa-left-long"></i> Atrás</button>
          <button type="submit" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-flag-checkered"></i> Finalizar y guardar</button>
        </form>
      </div>
    </div>
  </div>


  <div class="modal fade" id="modalPreview" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="">Vista previa de datos cargados</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="previewContent"> </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal"><i class="fa-regular fa-rectangle-xmark"></i> Cerrar </button>
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
  <script src="https://cdn.jsdelivr.net/npm/cleave.js/dist/cleave.min.js"></script>
  <!-- endbuild -->
  <!-- Vendors JS -->
  <script src="../../assets/vendor/libs/apex-charts/apexcharts.js"></script>
  <script src="../../libs/DataTable/datatables.min.js"></script>
  <!-- Main JS -->
  <script src="../../assets/js/main.js"></script>
  <script src="../../assets/vendor/css/SwalAlert2/dist/js/sweetalert2.all.min.js"></script>
  <script src="../shared/shared.js?<?php echo  rand(); ?>"></script>
  <script>
    showLoadingWithTetrisLogo('Cargando por favor espere...', 'ISA Software')
  </script>
  <script src="../shared/servicios.js?<?php echo  rand(); ?>"></script>
  <script src="../../assets/vendor/js/select2/select2.js"></script>
  <!-- Page JS -->
  <script src="../../assets/js/ui-toasts.js"></script>
  <script src="../../assets/js/dashboards-analytics.js"></script>
  <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
  <!-- User JS -->
  <script src="../controllers/wizard.js?<?php echo  rand(); ?>"></script>
</body>

</html>