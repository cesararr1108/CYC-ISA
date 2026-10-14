<!DOCTYPE html>
<html lang="es">

<head>
    <!-- =========================================================
         TEMA ISA
         Debe ejecutarse ANTES de cargar los CSS
         ========================================================= -->

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
    <title><?= isset($titulo) ? htmlspecialchars($titulo) : 'ISA Software' ?></title>
    <meta name="description" content="" />
    <link rel="icon" type="image/x-icon" href="../../assets/img/logo-mini.png" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />
    <!-- Core CSS -->
    <link rel="stylesheet" href="../../assets/vendor/fonts/boxicons.css" />
    <link rel="stylesheet" href="../../assets/vendor/css/core.css?<?= rand() ?>" class="template-customizer-core-css" />
    <link rel="stylesheet" href="../../assets/vendor/css/theme-default.css?<?= rand() ?>" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="../../assets/vendor/css/dark-theme.css?<?= rand() ?>" />
    <link rel="stylesheet" href="../../assets/vendor/libs/jquery/jquery-ui/jquery-ui.css?<?= rand() ?>" />
    <!-- Vendors CSS -->
    <link rel="stylesheet" href="../../assets/vendor/css/fontawesome-free-6.1.2-web/css/all.min.css" />
    <link rel="stylesheet" href="../../assets/vendor/css/SwalAlert2/dist/css/sweetalert2.min.css" />
    <link rel="stylesheet" href="../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="../../assets/vendor/libs/apex-charts/apex-charts.css" />
    <!-- Page CSS -->
    <link rel="stylesheet" href="../../libs/DataTable/datatables.min.css?<?php echo rand(); ?>" />
    <link rel="stylesheet" href="../../assets/css/demo.css?<?= rand() ?>" />
    <link rel="stylesheet" href="../../assets/css/busqueda-referencias-componente.css?<?php echo rand(); ?>" />
    <link rel="stylesheet" href="../../assets/css/tetris-logo.css?<?= rand() ?>" />
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <!-- Handsontable CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/handsontable@14.0.0/dist/handsontable.full.min.css" />
    <?php if (!empty($cssPagina)): ?>

        <?php foreach ($cssPagina as $css): ?>
            <link rel="stylesheet" href="<?= htmlspecialchars($css) ?>" />
        <?php endforeach; ?>

    <?php endif; ?>

    <!-- =========================================================
         ESTILOS CRÍTICOS DEL TEMA
         ========================================================= -->

    <style>
        /*
         * Tamaño de fuente global para el tema ISA.
         * Se aplica a todos los elementos para evitar problemas de herencia.
         */
        .swal2-container {
            z-index: 20000 !important;
            font-size: 0.8rem !important;
        }

        /*
         * Evita que el navegador pinte inicialmente
         * un fondo blanco cuando el tema es oscuro.
         */

        html[data-color-scheme="dark"] {
            background-color: #1e1e2d !important;
        }

        html[data-color-scheme="dark"] body {
            background-color: #1e1e2d !important;
        }

        /* Evita que Bootstrap y SweetAlert2 desplacen el contenido
         * al compensar la barra de scroll (se nota dentro del iframe) 
         */
        body.modal-open,
        body.swal2-shown,
        body.swal2-height-auto {
            padding-right: 0 !important;
            overflow: auto !important;
        }

        html.swal2-shown,
        html.swal2-height-auto {
            height: auto !important;
            overflow: auto !important;
        }
    </style>

</head>

<body>