<?php
session_start();

if (empty($_SESSION)) {
  if (!empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS'])) {
    $uri = 'https://';
  } else {
    $uri = 'http://';
  }
  $uri .= $_SERVER['HTTP_HOST'];
  header('Location: ' . $uri . '/development/app/views/login.php');
}
?>
<!DOCTYPE html>

<html lang="es" class="light-style customizer-hide" dir="ltr" data-theme="theme-default" data-assets-path="../../assets/" data-template="vertical-menu-template-free">

<head>
  <script>
    (function() {
      try {

        var theme = localStorage.getItem('isa_theme') || 'light';

        document.documentElement.setAttribute(
          'data-color-scheme',
          theme
        );

      } catch (e) {}
    })();
  </script>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
  <title>C&C Technology</title>
  <meta name="description" content="" />
  <link rel="icon" type="image/x-icon" href="../../assets/img/favicon-48.png" />
  <link rel="manifest" href="../../manifest.json" />
  <meta name="theme-color" content="#ca18e5" />
  <link rel="apple-touch-icon" href="../../assets/img/favicon-48.png" />
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />
  <!-- Icons. Uncomment required icon fonts -->
  <link rel="stylesheet" href="../../assets/vendor/fonts/boxicons.css" />

  <!-- Core CSS -->
  <link rel="stylesheet" href="../../assets/vendor/css/core.css" class="template-customizer-core-css" />
  <link rel="stylesheet" href="../../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
  <link rel="stylesheet" href="../../assets/css/demo.css?<?php echo  rand(); ?>" />
  <link rel="stylesheet" href="../../assets/vendor/css/fontawesome-free-6.1.2-web/css/all.min.css" />
  <link rel="stylesheet" href="../../assets/vendor/css/SwalAlert2/dist/css/sweetalert2.min.css" />
  <!-- Tetris Logo CSS -->
  <link rel="stylesheet" href="../../assets/css/tetris-logo.css" />
  <!-- Vendors CSS -->
  <link rel="stylesheet" href="../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
  <link rel="stylesheet" href="../../assets/vendor/libs/apex-charts/apex-charts.css" />

  <!-- Page CSS -->
  <link rel="stylesheet" href="../../assets/vendor/css/dark-theme.css" />

  <!-- Helpers -->
  <script src="../../assets/vendor/js/helpers.js"></script>

  <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
  <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
  <script src="../../assets/js/config.js"></script>

</head>

<style>
  body {
    overflow: hidden;
  }

  #logo-empresa,
  #name-empresa {
    display: flex;
    justify-content: center;
    align-items: center;
  }
</style>
  <!-- Layout wrapper -->
  <div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
      <!-- Menu -->
      <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme ">
        <div class="app-brand demo">
          <a href="home.php" class="">
            <!--<span class="app-brand-logo demo" id="logo-empresa"> </span> -->
            <span class="" id="logo-empresa"> </span>
            <span class="app-brand-text demo fw-bolder mt-2" id="name-empresa"></span>
          </a>
          <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
            <i class="bx bx-chevron-left bx-sm align-middle"></i>
          </a>
        </div>
        <div class="menu-inner-shadow"></div>
        <div class="" style="height: 90vh;">
          <ul class="menu-inner py-1 " id="menu-principal">
          </ul>
        </div>
      </aside>
      <!-- / Menu -->
      <!-- Layout container -->
      <div class="layout-page">
        <!-- Navbar -->
        <nav class="layout-navbar container-fluid p-1 navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme mb-2" id="layout-navbar">
          <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none"> <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)"> <i class="bx bx-menu bx-sm"></i> </a> </div>
          <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
            <!-- Search -->
            <div class="navbar-nav align-items-center">
              <div class="nav-item d-flex align-items-center"> <i class="bx bx-search fs-4 lh-0 p-2"></i>
                <input type="text" class="form-control border-0 shadow-none" id="menu-search" placeholder="Búscar módulo..." aria-label="Búscar módulo..." />
              </div>
            </div>

            <!-- /Search -->
            <ul class="navbar-nav flex-row align-items-center ms-auto">
              <div id="network-status" style="display: flex; align-items: center; gap: 8px; font-family: sans-serif; font-size: 14px;">
                <!-- Usamos un icono por defecto de FontAwesome -->
                <i id="conn-icon" class="fas fa-wifi" style="color: gray; font-size: 16px;"></i>
                <span id="conn-text">Verificando red...</span>
              </div>
              <!-- Cambiar tema claro/oscuro -->
               <li class="nav-item lh-1 me-2" id="li-activar-push">
                <a class="nav-link cursor-pointer" onclick="activarNotificaciones()" title="Activar notificaciones">
                  <i class="fa-solid fa-bell text-primary"></i>
                </a>
              </li>
              <li class="nav-item lh-1 me-2">
                <a class="nav-link position-relative cursor-pointer" href="javascript:void(0);" onclick="toggleTheme()" title="Cambiar tema">
                  <i class="bx bx-moon fs-4 text-primary" id="theme-toggle-icon"></i>
                </a>
              </li>
              <!-- Notificaciones Traslados Recibidos -->
              <li class="nav-item lh-1 me-2">
                <a class="nav-link position-relative cursor-pointer" data-size="large" id="btn-notificaciones-traslados" title="Traslados recibidos">
                  <i class="fa-solid fa-dolly text-primary"></i>
                  <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="badge-traslados" style="display: none;">
                    <span id="count-notificaciones">0</span>
                  </span>
                </a>
              </li>
              <!-- Duplicar Ventana -->
              <li class="nav-item lh-1 me-2">
                <a class="nav-link position-relative cursor-pointer" id="btn-duplicar-ventana" title="Duplicar ventana">
                  <i class="fa-solid fa-copy text-primary"></i>
                </a>
              </li>
              <!-- Soporte WhatsApp -->
              <li class="nav-item lh-1 me-2">
                <a
                  href="#"
                  target="_blank"
                  class="nav-link position-relative cursor-pointer"
                  id="btn-whatsapp-soporte"
                  title="WhatsApp Soporte">
                  <i class="fa-brands fa-whatsapp text-success"></i>
                </a>
              </li>
              <!-- Place this tag where you want the button to render. -->
              <li class="nav-item lh-1 me-2" id="listado-bodegas">
                <a
                  class="nav-link position-relative cursor-pointer"
                  href="#"
                  data-icon="octicon-star"
                  data-size="large"
                  data-show-count="true"
                  title="Seleccione bodega de trabajo"
                  aria-label="Seleccione bodega de trabajo">
                  <i class="fa-solid fa-location-dot"></i>
                </a>
              </li>

              <!-- User -->
              <li class="nav-item navbar-dropdown dropdown-user dropdown"> <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                  <div class="avatar avatar-online"> <img src="../../assets/img/usuarios/<?php echo !empty($_SESSION['Ses_foto']) ? $_SESSION['Ses_foto'] : 'user_default.png'; ?>" alt="Foto de usuario"></div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" style="min-width: 280px;">
                  <!-- Header de usuario -->
                  <li class="p-3">
                    <div class="d-flex align-items-center">
                      <div class="me-3">
                        <div class="avatar avatar-online">
                          <img src="../../assets/img/usuarios/<?php echo !empty($_SESSION['Ses_foto']) ? $_SESSION['Ses_foto'] : 'user_default.png'; ?>" alt="Avatar">
                        </div>
                      </div>
                      <div class="flex-grow-1">
                        <span class="fw-bold d-block" id="usuario_logueado" style="font-size: 0.95rem; color: #333;">
                          <?php echo strtoupper($_SESSION["Ses_usuario"]); ?>
                        </span>
                        <span class="d-block" style="font-size: 0.85rem; color: #666;" id="user_name">
                          <?php echo $_SESSION["Ses_nombres"] . ' ' . $_SESSION["Ses_apellidos"]; ?>
                        </span>
                        <small class="d-block" style="color: #999;" id="user_rol">
                          <i class="fa-solid fa-shield-halved me-1"></i><?php echo $_SESSION["Ses_rol"]; ?>
                        </small>
                      </div>
                    </div>
                  </li>
                  <li>
                    <div class="dropdown-divider m-2"></div>
                  </li>
                  <!-- Opciones -->
                  <li>
                    <a class="dropdown-item" href="#" id="miperfil">
                      <i class="icon-base bx bx-user icon-md me-3"></i>
                      <span class="align-middle fw-500">Mi perfil</span>
                    </a>
                  </li>
                  <li>
                    <div class="dropdown-divider m-2"></div>
                  </li>
                  <li>
                    <a class="dropdown-item text-danger" href="#" id="logout">
                      <i class="icon-base bx bx-power-off icon-md me-3"></i>
                      <span class="align-middle fw-500">Salir</span>
                    </a>
                  </li>
                </ul>
              </li>
              <!--/ User -->
            </ul>
          </div>
        </nav>
        <!-- / Navbar -->
        <!-- Content wrapper -->
        <div class="content-wrapper ">
          <!-- Content -->
          <div class="container-fluid">
            <div class="row p-0">
              <div class="col-lg-12 col-12">
                <iframe style="height:85vh;" src="load.html" class="w-100" id="iframe"></iframe>
              </div>
            </div>
            <!-- Footer -->
            <footer class="content-footer footer boder boder-1 border-success">
              <div class="container-xxl ">
                <div class="p-0 text-center " style="text-align:center;">
                  © <?php echo date('Y'); ?>, desarrollado por <a href="https://thecyctechnology.com" target="_blank" class="footer-link fw-bolder">CYC Technology S.A.S</a> </div>
              </div>
            </footer>
            <!-- / Footer -->
          </div>
          <!-- / Content -->
          <div class="content-backdrop fade"></div>
        </div>
        <!-- Content wrapper -->
      </div>
      <!-- / Layout page -->
    </div>
    <!-- Overlay -->
    <div class="layout-overlay layout-menu-toggle"></div>
  </div>
  <!-- / Layout wrapper -->
  <div class="modal fade" id="modal-selected-bodegas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="card align-items-center justify-content-center flex-column" style="display: flex; flex-direction: column;">
            <div style="display: flex; justify-content: center; align-items: center; padding: 5px 10px;">
              <div class="tetris-logo-container" style="animation: none; transform: scale(0.6);">
                <div class="tetris-block size-1 purple-light" style="animation: none;"></div>
                <div class="tetris-block size-2 purple-medium" style="animation: none;"></div>
                <div class="tetris-block size-3-tall purple-dark" style="animation: none;"></div>
                <div class="tetris-block size-4-v purple-light" style="animation: none;"></div>
                <div class="tetris-block size-4-v purple-medium" style="animation: none;"></div>
                <div class="tetris-block size-2-tall purple-dark" style="animation: none;"></div>
              </div>
            </div>
            <div class="card-body">
              <div class="card-title mt-1 text-muted text-center">
                <h5 class="mb-0"><strong>SELECCIONE LA BODEGA DE TRABAJO</strong></h5>
              </div>
              <ul class="list-group" id="bodegas-usuario" style="margin-top: 10px;">
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal -->
  <div class="modal fade" id="modalEmpresa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content" style="height:90vh;">
        <!-- Header -->
        <div class="modal-header">
          <h5 class="modal-title fw-bold text-primary">
            ⚠️ Configuración pendiente
          </h5>
        </div>
        <!-- Contenido principal -->
        <div class="modal-body p-0">
          <iframe src="wizard.php" style="width:100%; height:100%; border:none;"></iframe>
        </div>
      </div>
    </div>
  </div>
  <!-- Modal Cambiar Contraseña -->
  <div class="modal fade" id="modalCambiarPassword" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header ">
          <h5 class="modal-title fw-bold">
            <i class="fa-solid fa-lock me-2"></i>Cambiar Contraseña
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="formCambiarPassword">
            <div class="mb-3">
              <label for="passwordActual" class="form-label">
                <i class="fa-solid fa-key me-2 text-warning"></i>Contraseña Actual
              </label>
              <input type="password" class="form-control" id="passwordActual" placeholder="Ingresa tu contraseña actual" required>
              <small class="text-muted d-block mt-1">Debes verificar tu contraseña actual</small>
            </div>
            <hr>
            <div class="mb-3">
              <label for="passwordNueva" class="form-label">
                <i class="fa-solid fa-lock-open me-2 text-success"></i>Nueva Contraseña
              </label>
              <input type="password" class="form-control" id="passwordNueva" placeholder="Ingresa tu nueva contraseña" required>
              <small class="text-muted d-block mt-1">Mínimo 8 caracteres, incluye mayúsculas, números y símbolos</small>
            </div>
            <div class="mb-3">
              <label for="passwordConfirmar" class="form-label">
                <i class="fa-solid fa-check-double me-2 text-info"></i>Confirmar Nueva Contraseña
              </label>
              <input type="password" class="form-control" id="passwordConfirmar" placeholder="Confirma tu nueva contraseña" required>
              <small class="text-muted d-block mt-1">Debe coincidir con la nueva contraseña</small>
            </div>
            <div id="password-strength" class="mb-3">
              <div class="d-flex align-items-center">
                <span class="me-2">Seguridad:</span>
                <div class="progress flex-grow-1" style="height: 5px;">
                  <div id="strength-bar" class="progress-bar" role="progressbar" style="width: 0%;"></div>
                </div>
                <span id="strength-text" class="ms-2 small" style="min-width: 60px;"></span>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fa-solid fa-times me-2"></i>Cancelar
          </button>
          <button type="button" class="btn btn-primary" id="btnGuardarPassword">
            <i class="fa-solid fa-save me-2"></i>Guardar Cambios
          </button>
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
  <script src="../shared/shared.js?<?php echo  rand(); ?>"></script>
  <script src="../../assets/js/dashboards-analytics.js"></script>
  <script src="../shared/servicios.js?<?php echo  rand(); ?>"></script>
  <script src="../shared/servicios-dashboard.js?<?php echo  rand(); ?>"></script>

  <script src="../controllers/home.js?<?php echo  rand(); ?>"></script>
  <script src="../../assets/vendor/js/theme-toggle.js"></script>

  <script src="https://www.gstatic.com/firebasejs/12.18.0/firebase-app-compat.js"></script>
  <script src="https://www.gstatic.com/firebasejs/12.18.0/firebase-messaging-compat.js"></script>
  <script src="../shared/push-notifications.js?<?php  echo  rand(); ?>" type="text/javascript"></script>
<script type="module">

        import {
            initializeApp
        } from "https://www.gstatic.com/firebasejs/12.18.0/firebase-app.js";

        import {
            getMessaging,
            getToken
        } from "https://www.gstatic.com/firebasejs/12.18.0/firebase-messaging.js";


        const firebaseConfig = {

            apiKey: "AIzaSyATCXBm38Vm0rWPTL4lK3oPV0Jfa8vektE",
            authDomain:
                "cyc-notifications-push.firebaseapp.com",
            projectId:
                "cyc-notifications-push",
            storageBucket:
                "cyc-notifications-push.firebasestorage.app",
            messagingSenderId:
                "943820201444",
            appId:
                "1:943820201444:web:171e3531c7b861bfa7fbf1",
            measurementId:
                "G-BLP2M3DP5W"
        };


        // Inicializar Firebase
        const app = initializeApp(firebaseConfig);

        const messaging = getMessaging(app);

        const Notificacion =async ()=>{
            try {

                // ========================================
                // VERIFICAR SOPORTE
                // ========================================

                if (!('Notification' in window)) {
                    $("#li-activar-push").find("a").find("i").removeClass("text-primary")
                    $("#li-activar-push").find("a").find("i").addClass("text-danger")
                    console.log('Notificacion en window')
                    return;
                }else{
                  $("#li-activar-push").find("a").find("i").removeClass("text-danger")
                  $("#li-activar-push").find("a").find("i").addClass("text-primary")
                }

                if (!('serviceWorker' in navigator)) {
                    $("#li-activar-push").find("a").find("i").removeClass("text-primary")
                    $("#li-activar-push").find("a").find("i").addClass("text-danger")
                     console.log('serviceworker')
                    return;
                }else{
                  $("#li-activar-push").find("a").find("i").removeClass("text-danger")
                  $("#li-activar-push").find("a").find("i").addClass("text-primary")
                }
                // ========================================
                // SOLICITAR PERMISO
                // ========================================

                const permission =  await Notification.requestPermission();
                if (permission !== 'granted') {
                    console.log('permission')
          
                    $("#li-activar-push").find("a").find("i").removeClass("text-primary")
                    $("#li-activar-push").find("a").find("i").addClass("text-danger")
                    return;
                }else{
                  $("#li-activar-push").find("a").find("i").removeClass("text-danger")
                  $("#li-activar-push").find("a").find("i").addClass("text-primary")
                }

                // ========================================
                // REGISTRAR SERVICE WORKER
                // ========================================

                const registration =
                    await navigator.serviceWorker.register(
                        '../../../firebase-messaging-sw.js?'+Math.random()
                    );

                // ========================================
                // ESPERAR SERVICE WORKER
                // ========================================

                await navigator.serviceWorker.ready;


                // ========================================
                // OBTENER TOKEN FCM
                // ========================================


                const currentToken =
                    await getToken(
                        messaging,
                        {

                            vapidKey:
                                'BMkxZL77tHSmA4F5cheCQdqkMy2UBMpFTD1y4Lja7ZY5BFSySxIcTs7K1IZTlBtC1E8fFxY7ekEMDgTHPARx8RU',

                            serviceWorkerRegistration:
                                registration

                        }
                    );

                // ========================================
                // VALIDAR TOKEN
                // ========================================

                if (currentToken) {
                
                    const ua = navigator.userAgent;
                    const so = /Windows/.test(ua) ? 'Windows' : /Android/.test(ua) ? 'Android'
                      : /iPhone|iPad/.test(ua) ? 'iOS' : /Mac/.test(ua) ? 'macOS'
                      : /Linux/.test(ua) ? 'Linux' : 'Desconocido';
                    const nav = /Edg\//.test(ua) ? 'Edge' : /OPR\//.test(ua) ? 'Opera'
                      : /Firefox\//.test(ua) ? 'Firefox' : /Chrome\//.test(ua) ? 'Chrome'
                      : /Safari\//.test(ua) ? 'Safari' : 'Desconocido';
                  // guardo el token en la base de datos 
                  const resp = await enviarPeticion({
                    link : '../shared/servicios/servicios.php',
                    op:'guardar_token_fcm',
                    token :currentToken,
                    navegador :nav

                  })
    
                } else {
                  console.log('No se guardo ')
                }


            } catch (error) {
            alert(error.message)
                console.error(
                    'Error completo:',
                    error
                );
             
            }
        }
   
                // ============================================
        // ACTIVAR NOTIFICACIONES
        // ============================================

        Notificacion()
          window.activarNotificaciones = Notificacion;

        var yaTienePermiso = ("Notification" in window) && Notification.permission === "granted";
        if (yaTienePermiso) {
          Notificacion(); // ya dio permiso antes: solo refresca el token, sin preguntar
          $("#li-activar-push").find("a").find("i").removeClass("text-danger")
          $("#li-activar-push").find("a").find("i").addClass("text-primary")
        }
    </script>

</body>

</html>