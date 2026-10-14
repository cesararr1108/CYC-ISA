<!DOCTYPE html>
<html lang="es" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../../assets/" data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Calendario de Pagos</title>
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

    <link rel="stylesheet" href="../../assets/vendor/css/dark-theme.css" />
    <!-- Helpers -->
    <script src="../../assets/vendor/js/helpers.js"></script>
    <script src="../../assets/js/config.js"></script>
    <style>
        /* Reset para que la vista se ajuste al iframe que la contiene,
           sin generar márgenes/scrollbars dobles */
        html,
        body {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
            overflow-x: hidden;
        }

        body {
            overflow-y: auto;
        }

        .container-fluid {
            padding-left: 0.75rem;
            padding-right: 0.75rem;
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
            max-width: 100%;
        }

        /* Efecto hover sobre cada celda de día del calendario */
        .celda-dia {
            transition: background-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .celda-dia:hover {
            background-color: rgba(13, 110, 253, 0.06);
            box-shadow: inset 0 0 0 1px rgba(13, 110, 253, 0.35);
            cursor: default;
        }

        .badge-documento:hover {
            filter: brightness(0.92);
        }

        /* Altura de celda responsiva: se reduce en iframes/pantallas más
           pequeñas en vez de mantener un alto fijo de 90px siempre */
        .celda-dia,
        .celda-vacia {
            min-height: 70px;
        }

        @media (min-width: 576px) {

            .celda-dia,
            .celda-vacia {
                min-height: 90px;
            }
        }

        /* El grid nunca debe forzar scroll horizontal dentro del iframe */
        #gridCalendario {
            width: 100%;
        }

        .badge-documento {
            font-size: 0.7rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>

<body>

    <div class="container-fluid py-3">

        <div class="card shadow-sm">
            <div class="card-header bg-white">

                <div class="d-flex align-items-center justify-content-between mb-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnMesAnterior">
                        <i class="fa-solid fa-chevron-left"></i> Anterior
                    </button>

                    <h5 class="mb-0" id="tituloMesAnio">&nbsp;</h5>

                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnMesSiguiente">
                        Siguiente <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>

                <!-- Leyenda / Referencia (arriba, dentro del header) -->
                <div class="d-flex gap-3 align-items-center flex-wrap border-top pt-2">
                    <span class="text-muted small fw-bold">
                        <i class="fa-solid fa-circle-info"></i> Referencia:
                    </span>
                    <span class="badge bg-danger">
                        <i class="fa-solid fa-triangle-exclamation"></i> Vencido
                    </span>
                    <span class="badge bg-warning text-dark">
                        <i class="fa-solid fa-clock"></i> Pendiente
                    </span>
                    <span class="badge  bg-opacity-10 text-primary border border-primary">
                        <i class="fa-solid fa-calendar-day"></i> Hoy
                    </span>
                </div>

            </div>

            <div class="card-body">

                <!-- Alerta de error -->
                <div id="alertaErrorCalendario" class="alert alert-danger d-none" role="alert"></div>

                <!-- Encabezado de días de la semana -->
                <div class="row text-center fw-bold text-secondary border-bottom pb-2 mb-2 d-none" id="encabezadoDias">
                    <div class="col">Dom</div>
                    <div class="col">Lun</div>
                    <div class="col">Mar</div>
                    <div class="col">Mié</div>
                    <div class="col">Jue</div>
                    <div class="col">Vie</div>
                    <div class="col">Sáb</div>
                </div>

                <!-- Grid del calendario (se construye dinámicamente por JS) -->
                <div id="gridCalendario"></div>

            </div>
        </div>

    </div>

    <!-- Modal de detalle del documento 
     modal-dialog-centered 
    -->


    <div class="modal fade" id="modalDetalleDocumento" tabindex="-1" aria-labelledby="modalDetalleDocumentoLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalDetalleDocumentoLabel">
                        Detalle del documento
                    </h5>

                    <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Cerrar">
                    </button>
                </div>
                <div class="modal-body">

                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <div class="text-muted small">Documento</div>
                            <div class="fs-5 fw-bold" id="detDocumento"></div>
                        </div>
                        <span id="detEstadoBadge" class="badge fs-6"></span>
                    </div>

                    <table class="table table-sm align-middle mb-0">
                        <tbody>
                            <tr>
                                <th class="text-muted" style="width: 45%;">
                                    Tercero
                                </th>
                                <td id="detTercero"></td>
                            </tr>
                            <tr>
                                <th class="text-muted">
                                    NIT
                                </th>
                                <td id="detNit"></td>
                            </tr>
                            <tr>
                                <th class="text-muted">
                                    Valor total
                                </th>
                                <td id="detValorTotal"></td>
                            </tr>
                            <tr>
                                <th class="text-muted">
                                    Saldo
                                </th>
                                <td class="fw-bold" id="detSaldo"></td>
                            </tr>
                            <tr>
                                <th class="text-muted">
                                    Vencimiento
                                </th>
                                <td id="detVencimiento"></td>
                            </tr>
                            <tr>
                                <th class="text-muted">
                                    Días vencido
                                </th>
                                <td id="detDiasVencido"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- jQuery -->
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
    <script src="../controllers/calendarioPagos.js"></script>

</body>

</html>