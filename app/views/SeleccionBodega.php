 <?php
    session_start();
    ?>
 <!DOCTYPE html>

 <html
     lang="es"
     class="light-style customizer-hide"
     dir="ltr"
     data-theme="theme-default"
     data-assets-path="../../assets/"
     data-template="vertical-menu-template-free">

 <head>
     <meta charset="utf-8" />
     <meta
         name="viewport"
         content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
     <title>Login C&C</title>
     <meta name="description" content="" />
     <link rel="icon" type="image/x-icon" href="../../assets/img/logo-mini.png" />
     <!-- Fonts -->
     <link rel="preconnect" href="https://fonts.googleapis.com" />
     <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
     <link
         href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
         rel="stylesheet" />
     <!-- boostrap -->
     <link rel="stylesheet" href="../../assets/vendor/css/bootstrap-5.0.2-dist/css/bootstrap.min.css" />
     <!-- Icons. Uncomment required icon fonts -->
     <link rel="stylesheet" href="../../assets/vendor/fonts/boxicons.css" />

     <!-- Core CSS -->
     <link rel="stylesheet" href="../../assets/vendor/css/core.css" class="template-customizer-core-css" />
     <link rel="stylesheet" href="../../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
     <link rel="stylesheet" href="../../assets/css/demo.css" />
     <link rel="stylesheet" href="../../assets/vendor/css/SwalAlert2/dist/css/sweetalert2.min.css" />
     <!-- Tetris Logo CSS -->
     <link rel="stylesheet" href="../../assets/css/tetris-logo.css" />
     <!-- Vendors CSS -->
     <link rel="stylesheet" href="../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
     <link rel="stylesheet" href="../../assets/vendor/css/fontawesome-free-6.1.2-web/css/all.css" />
     <!-- Page CSS -->
     <!-- Page -->


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
             height: 100vh;
         }

         body {
             overflow: hidden;
         }
     </style>
 </head>

 <body class="d-flex align-items-center justify-content-center flex-column">
     <input type="hidden" id="logo" value="<?php echo  $_SESSION["Ses_logo"]; ?>">
     <!-- Content -->
     <div class="container p-5 ">
         <!-- Aquí va tu contenido cuando cargue -->
         <!-- Spinner centrado -->
         <div class="text-center">
             <i class="fas fa-spinner fa-spin fa-5x text-primary"></i>
             <p class="mt-3">Cargando...</p>
         </div>
     </div>

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
     <script src="../../assets/js/main.js"></script>

     <script src="../shared/servicios.js?<?php echo rand(); ?>"></script>
     <script src="../shared/shared.js?<?php echo rand(); ?>"></script>
     <!-- js app -->
     <script async defer src="../controllers/SeleccionBodega.js?<?php echo  rand(); ?>"></script>
 </body>

 </html>