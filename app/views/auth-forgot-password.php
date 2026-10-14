
<!DOCTYPE html>
<html
  lang="en"
  class="light-style customizer-hide"
  dir="ltr"
  data-theme="theme-default"
  data-assets-path="../assets/"
  data-template="vertical-menu-template-free"
>
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0"
    />

    <title>Forgot Password Basic - Pages | Sneat - Bootstrap 5 HTML Admin Template - Pro</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
      rel="stylesheet"
    />

    <!-- Icons. Uncomment required icon fonts -->
    <link rel="stylesheet" href="../../assets/vendor/fonts/boxicons.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="../../assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="../../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="../../assets/css/demo.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
     <link rel="stylesheet" href="../../assets/vendor/css/SwalAlert2/dist/css/sweetalert2.min.css" />
    <!-- Page CSS -->
    <!-- Page -->
    <link rel="stylesheet" href="../../assets/vendor/css/pages/page-auth.css" />
    <!-- Helpers -->
    <script src="../../assets/vendor/js/helpers.js"></script>

    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="../../assets/js/config.js"></script>
  </head>

  <body>
    <!-- Content -->
<input type="hidden" id="empresa" value="<?php echo  $_GET["empresa"];  ?>" readonly />
    <div class="container-xxl">
      <div class="authentication-wrapper authentication-basic container-p-y">
        <div class="authentication-inner py-4">
          <!-- Forgot Password -->
          <div class="card">
            <div class="card-body">
              <!-- Logo -->
              <div class="app-brand justify-content-center">
                <a href="index.html" class="app-brand-link gap-2">
                  <span class="app-brand-logo demo">
                        <img src="https://thecyctechnology.com/cyc/assets/img/logo-mini.png" />
                  </span>
                 
                </a>
              </div>
              <!-- /Logo -->
              <h4 class="mb-2">Has olvidado tu contraseña? 🔒</h4>
              <p class="mb-4">Ingresa tu correo electrónico y te enviaremos instrucciones para restablecer tu contraseña</p>
              <form id="formRecuperar" method="POST">
                <div class="mb-3">
                  <label for="email" class="form-label">Email</label>
                  <input type="text" class="form-control" id="email" name="email" placeholder="Digita tu email" autofocus required />
                </div>

               
                <br>
                <button type="submit" class="btn btn-primary d-grid w-100">Enviar link de recuperación</button>
              </form>
              <div class="text-center mt-2">
                <a href="login.php" class="d-flex align-items-center justify-content-center">
                  <i class="bx bx-chevron-left scaleX-n1-rtl bx-sm"></i>
                  Regresar al login
                </a>
              </div>
            </div>
          </div>
          <!-- /Forgot Password -->
        </div>
      </div>
    </div>


    <!-- Core JS -->
    <!-- build:js assets/vendor/js/core.js -->
 <script src="https://www.google.com/recaptcha/api.js?render=6LfeaPwrAAAAAAdKqghAiT4Kd0ty3ObyMTeOvjc4"></script>
<script>
  grecaptcha.ready(function() {
    grecaptcha.execute('6LfeaPwrAAAAAAdKqghAiT4Kd0ty3ObyMTeOvjc4', {action: 'forgot_password'}).then(function(token) {
      // Agrega el token al formulario
      const form = document.getElementById('formRecuperar');
      if (form) {
        // Si ya existe un input con el token, lo actualiza
        let input = form.querySelector('input[name="g-recaptcha-response"]');
        if (!input) {
          input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'g-recaptcha-response';
          form.appendChild(input);
        }
        input.value = token;
      }
    });
  });
</script>

    <script src="../../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../../assets/vendor/libs/popper/popper.js"></script>
    <script src="../../assets/vendor/js/bootstrap.js"></script>
    <script src="../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    
    <script src="../../assets/vendor/js/menu.js"></script>
    <!-- endbuild -->

    <!-- Vendors JS -->

    <!-- Main JS -->
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/vendor/css/SwalAlert2/dist/js/sweetalert2.all.min.js"></script> 
    <script src="../shared/servicios.js?<?php echo rand(); ?>"></script> 
    <script src="../shared/shared.js?<?php echo rand(); ?>"></script> 
    <script src="../controllers/auth-forgot-password.js"></script>
    <!-- Page JS -->

  </body>
</html>
