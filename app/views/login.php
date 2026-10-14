<!DOCTYPE html>
<html
  lang="es"
  class="light-style customizer-hide"
  dir="ltr"
  data-theme="theme-default"
  data-assets-path="../../assets/"
  data-template="vertical-menu-template-free"
>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
<title>Login C&C</title>
<meta name="description" content="" />

<!-- Favicon -->
<link rel="icon" type="image/x-icon" href="../../assets/img/logo-mini.png" />

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />
<!-- boostrap -->
<link rel="stylesheet" href="../../assets/vendor/css/bootstrap-5.0.2-dist/css/bootstrap.min.css" />
<!-- Icons. Uncomment required icon fonts -->
<link rel="stylesheet" href="../../assets/vendor/fonts/boxicons.css" />

<!-- Core CSS -->
<link rel="stylesheet" href="../../assets/vendor/css/core.css" class="template-customizer-core-css" />
<link rel="stylesheet" href="../../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
<link rel="stylesheet" href="../../assets/css/demo.css" />
<link rel="stylesheet" href="../../assets/vendor/css/SwalAlert2/dist/css/sweetalert2.min.css" />
<!-- Vendors CSS -->
<link rel="stylesheet" href="../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
<link rel="stylesheet" href="../../assets/vendor/css/fontawesome-free-6.1.2-web/css/all.css" />
<link rel="stylesheet" href="../../assets/vendor/css/pages/page-auth.css" />
<link rel="stylesheet" href="../../assets/css/tetris-logo.css" />
<style>
/* Inicialmente el logo-icono es visible y el container-xxl está oculto */

.fade-in-image {
    opacity: 0;
    animation: fadeIn 2s forwards;
}
@keyframes fadeIn {
to {
opacity: 1;
}
}
.hide-in-image {
    opacity: 1;
    animation: fadeOut 1.5s forwards;
}
@keyframes fadeOut {
to {
opacity: 0;
}
}
.full-height {
    height: 85vh;
    overflow: none
}
</style>
</head>

<body class="d-flex align-items-center justify-content-center flex-column">
<!-- Content -->
  <div class="authentication-wrapper authentication-basic ">
    <div class="authentication-inner"> 
    <div class=" d-flex align-items-center justify-content-center flex-column full-height"> 
      <!-- Register -->
      <div class="card" >
        <div class="card-body"> 
          <!-- Logo -->
          <div class="app-brand justify-content-center"> 
            <div class="d-flex flex-column align-items-center gap-2">
              <div class="tetris-logo-container">
                <!-- Fila 1: pequeño, mediano -->
                <div class="tetris-row">
                  <div class="tetris-block size-1 purple-light"></div>
                  <div class="tetris-block size-2 purple-medium"></div>
                </div>
                <!-- Fila 2-3: grande a la izquierda (2x2), columna derecha con size-3 y size-2-tall -->
                <div class="tetris-row-middle">
                  <div class="tetris-block size-4-v purple-dark"></div>
                  <div class="tetris-right-column">
                    <div class="tetris-block size-3-tall purple-light"></div>
                    <div class="tetris-block size-2-tall purple-medium"></div>
                  </div>
                </div>
              </div>
              <div class="tetris-logo-text">ISA Software</div>
            </div>
          </div>
          <!-- /Logo -->
          <p class="mb-1 text-center">Por favor, ingresa tu usuario y clave</p>
          <form id="formAuthentication"  class=" needs-validation mt-1"  novalidate >
            <br>
            <label class="form-label text-primary" for="empresa">Empresa</label>
            <div class="input-group input-group-merge mb-2">
              <input
                      type="text"
                      id="empresa"
                      class="form-control"
                      name="empresa"
                      required
                      aria-describedby="empresa"
                    />
              <span class="input-group-text cursor-pointer"><i class="bx bx-building"></i></span> </div>
            <div class="valid-feedback text-primary"> Debe digitar la empresa! </div>
            <label class="form-label text-primary" for="password">Usuario</label>
            <div class="input-group input-group-merge mb-2">
              <input
                      type="text"
                      id="usuario"
                      class="form-control"
                      name="usuario"
                      required
                      aria-describedby="Usuario"
                    />
              <span class="input-group-text cursor-pointer"><i class="bx bx-user"></i></span> </div>
            <div class="valid-feedback text-primary"> Debe digitar el usuario! </div>
            <div class="mb-3 form-password-toggle" >
              <div class="d-flex justify-content-between">
                <label class="form-label text-primary" for="password">Clave</label>
              </div>
              <div class="input-group input-group-merge">
                <input
                      type="password"
                      id="clave"
                      class="form-control"
                      name="clave"
                      placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                      aria-describedby="clave"
                      required
                    />
                <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span> </div>
              <div class="valid-feedback "> Debe digitar la clave! </div>
            </div>
            <div class="mb-3 d-flex justify-content-between">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="remember-me" />
                <label class="form-check-label" for="remember-me"> Recordar </label>
              </div>
              <a id="recuperar" href="#" class="text-primary"> <small>He olvidado mi clave?</small> </a> </div>
            <div class="mb-3">
              <button class="btn btn-primary d-grid w-100" type="submit" id="login">Iniciar sesión</button>
            </div>
          </form>
            <div class="p-0 text-center " style="text-align:center;">
                © 2025, desarrollado por <a href="https://thecyctechnology.com" target="_blank" class="footer-link fw-bolder">CYC Technology S.A.S</a> </div>
            </div>
        </div>
      </div>
      <!-- /Register --> 
    </div>
  </div>
</div>
<!-- Core JS --> 
<!-- build:js assets/vendor/js/core.js --> 
<script src="../../assets/vendor/libs/jquery/jquery.js"></script> 
<script src="../../assets/vendor/css/bootstrap-5.0.2-dist/js/bootstrap.min.js"></script> 
<script src="../../assets/vendor/css/bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script> 
<script src="../../assets/vendor/libs/popper/popper.js"></script> 
<script src="../../assets/vendor/js/bootstrap.js"></script> 
<script src="../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script> 
<script src="../../assets/vendor/js/menu.js"></script> 
<script src="../../assets/vendor/css/SwalAlert2/dist/js/sweetalert2.all.min.js"></script> 
<script src="../../assets/vendor/css/fontawesome-free-6.1.2-web/js/all.min.js"></script> 
<!-- Helpers --> 
<script src="../../assets/vendor/js/helpers.js"></script> 
<script src="../../assets/js/config.js"></script> 
<!-- endbuild --> 

<!-- Vendors JS --> 

<!-- Main JS --> 
<script src="../../assets/js/main.js"></script> 

<!-- Page JS --> 

<!-- Place this tag in your head or just before your close body tag. --> 
<!--<script async defer src="https://buttons.github.io/buttons.js"></script>--> 

<script src="../shared/servicios.js?<?php echo rand(); ?>"></script> 
<script src="../shared/shared.js?<?php echo rand(); ?>"></script> 
<!-- js app --> 
<script async defer src="../controllers/login.js?<?php echo  rand(); ?>"></script>
</body>
</html>
