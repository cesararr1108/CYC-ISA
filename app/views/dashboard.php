<?php
session_start();

if (empty($_SESSION)) {
  $uri = (!empty($_SERVER['HTTPS']) && 'on' == $_SERVER['HTTPS']) ? 'https://' : 'http://';
  $uri .= $_SERVER['HTTP_HOST'];
  header('Location: ' . $uri . '/cyc/app/views/login.php');
  exit; // evita seguir renderizando la vista sin sesión
}

$titulo = 'Dashboard analítica';

/* ---------------------------------------------------------------
 * Período del dashboard (año / mes)
 * Por defecto: mes actual en hora de Colombia.
 * Permite deep-link: dashboard.php?anio=2026&mes=8 (se valida).
 * --------------------------------------------------------------- */
$ahora        = new DateTime('now', new DateTimeZone('America/Bogota'));
$anioActual   = (int) $ahora->format('Y');
$mesActual    = (int) $ahora->format('n');
$anioInicio   = $anioActual - 5; // últimos 6 años

$mesesEs = [
  1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
  5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
  9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

$anioSel = filter_input(INPUT_GET, 'anio', FILTER_VALIDATE_INT, ['options' => ['min_range' => $anioInicio, 'max_range' => $anioActual]]) ?: $anioActual;
$mesSel  = filter_input(INPUT_GET, 'mes', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 12]]) ?: $mesActual;
if ($anioSel === $anioActual && $mesSel > $mesActual) {
  $mesSel = $mesActual; // no se permiten meses futuros
}

include '../dependencies/header.php';
?>

<style>
  /*
   * Animación de las tarjetas.
   * No usamos "transition: all" para no animar los cambios de color
   * durante el cambio de tema.
   */
  .card {
    transition:
      box-shadow 0.3s ease,
      transform 0.3s ease;
  }

  /* El hover NO aplica al filtro de período ni a la alerta de suscripción */
  .card:not(#filtroPeriodoDashboard .card, #contenedor-suscripcion .card):hover {
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
    transform: translateY(-5px);
  }

  /* ---------- Modo compacto: cards más pequeñas ---------- */
  .dashboard-compact .card-body {
    padding: .85rem 1rem;
  }

  .dashboard-compact .row>.mb-4 {
    margin-bottom: 1rem !important;
  }

  .dashboard-compact .card-title.d-flex {
    margin-bottom: .6rem !important;
  }

  .dashboard-compact .avatar {
    width: 2rem;
    height: 2rem;
  }

  .dashboard-compact .avatar .avatar-initial {
    font-size: .85rem;
  }

  .dashboard-compact .avatar .avatar-initial i.fs-4 {
    font-size: 1rem !important;
  }

  .dashboard-compact .card-body>span,
  .dashboard-compact .card-body>p.mb-1 {
    display: block;
    font-size: .82rem;
  }

  .dashboard-compact h3.card-title {
    font-size: 1.3rem;
    margin-bottom: .15rem !important;
  }

  .dashboard-compact h4.card-title {
    font-size: 1.15rem;
    margin-bottom: .3rem !important;
  }

  .dashboard-compact .card-body small {
    font-size: .75rem;
  }

  .dashboard-compact .card-header {
    padding: .85rem 1rem .5rem;
    font-size: 1rem;
  }

  /* ---------- Barra de período ---------- */
  .dashboard-filtro {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .5rem 1.25rem;
    padding: .6rem 1rem;
  }

  .dashboard-filtro .filtro-titulo {
    display: flex;
    align-items: center;
    gap: .6rem;
    min-width: 0;
    margin-right: auto;
  }

  .dashboard-filtro .filtro-titulo small {
    display: block;
    line-height: 1.1;
    font-size: .72rem;
  }



  .dashboard-filtro .filtro-controles {
    display: flex;
    align-items: center;
    gap: .4rem;
    flex-wrap: wrap;
  }

  .dashboard-filtro .form-select {
    width: auto;
    min-width: 125px;
  }

  @media (max-width: 575.98px) {

    .dashboard-filtro .filtro-controles {
      width: 100%;
    }

    .dashboard-filtro .filtro-controles .form-select {
      flex: 1 1 0;
      min-width: 0;
    }
  }

  /* ---------- Carga tipo "skeleton" (shimmer estilo Facebook) ---------- */
  :root {
    --dash-sk-base: rgba(var(--bs-body-color-rgb, 105, 122, 141), .10);
    --dash-sk-hi: rgba(var(--bs-body-color-rgb, 105, 122, 141), .20);
  }

  @keyframes dashboard-shimmer {
    0% {
      background-position: 200% 0;
    }

    100% {
      background-position: -200% 0;
    }
  }

  /* El valor reaparece con un fundido suave cuando termina la carga */
  [data-periodo] h3.card-title {
    transition: color .45s ease;
  }

  [data-periodo].dashboard-cargando .card {
    pointer-events: none;
  }

  /* Cifras → bloque gris con brillo que se desplaza */
  [data-periodo].dashboard-cargando h3.card-title,
  [data-periodo].dashboard-cargando #dashboard-rentabilidad~small {
    color: transparent !important;
    background: linear-gradient(100deg, var(--dash-sk-base) 30%, var(--dash-sk-hi) 50%, var(--dash-sk-base) 70%);
    background-size: 200% 100%;
    animation: dashboard-shimmer 1.4s linear infinite;
    border-radius: .4rem;
    user-select: none;
  }

  [data-periodo].dashboard-cargando h3.card-title {
    display: block;
    width: 62%;
  }

  [data-periodo].dashboard-cargando #dashboard-rentabilidad~small {
    display: inline-block;
    min-width: 3.5rem;
    min-height: 1em;
  }

  [data-periodo].dashboard-cargando #dashboard-rentabilidad~small * {
    visibility: hidden;
  }

  /* Gráfico → bloque gris con brillo */
  [data-periodo].dashboard-cargando :is(#totalRevenueChart, #ventasDiariasChart) {
    position: relative;
    min-height: 260px;
  }

  [data-periodo].dashboard-cargando :is(#totalRevenueChart, #ventasDiariasChart)>* {
    visibility: hidden;
  }

  [data-periodo].dashboard-cargando :is(#totalRevenueChart, #ventasDiariasChart)::before {
    content: "";
    position: absolute;
    inset: .25rem .5rem 1rem;
    border-radius: .6rem;
    background: linear-gradient(100deg, var(--dash-sk-base) 30%, var(--dash-sk-hi) 50%, var(--dash-sk-base) 70%);
    background-size: 200% 100%;
    animation: dashboard-shimmer 1.4s linear infinite;
  }

  /* Las tarjetas de alertas operativas ocupan todo el alto de su columna,
     así quedan del mismo tamaño aunque su contenido varíe */
  #dashboard-promociones-vigentes,
  #dashboard-proximos-vencer,
  #dashboard-stock-minimo,
  #dashboard-facturas-vencer {
    height: 100%;
  }

  /* ---------- Cumplimiento del presupuesto ---------- */
  .dash-pres-barra {
    height: 6px;
  }

  [data-periodo].dashboard-cargando .dash-pres-extra {
    visibility: hidden;
  }

  .tabla-primary thead th {
    background-color: var(--bs-primary) !important;
    color: #fff !important;
    border-color: var(--bs-primary) !important;
    white-space: nowrap;
    position: sticky;
    top: 0;
    z-index: 2;
  }

  @media (prefers-reduced-motion: reduce) {
    .card {
      transition: none;
    }

    .card:not(#filtroPeriodoDashboard .card, #contenedor-suscripcion .card):hover {
      transform: none;
    }

    [data-periodo] h3.card-title {
      transition: none;
    }

    [data-periodo].dashboard-cargando h3.card-title,
    [data-periodo].dashboard-cargando #dashboard-rentabilidad~small,
    [data-periodo].dashboard-cargando :is(#totalRevenueChart, #ventasDiariasChart)::before {
      animation: none;
    }
  }
</style>

<!-- Content wrapper -->
<div class="content-wrapper">
  <!-- Content -->
  <div class="container-fluid p-0 overflow-hidden dashboard-compact">
    <!-- ============ Filtro de período (año / mes) — primera card ============ -->
    <div class="row" id="filtroPeriodoDashboard">
      <div class="col-12 mb-4">
        <div class="card">
          <form class="dashboard-filtro" id="formPeriodoDashboard" onsubmit="return false;" aria-label="Filtro de período del dashboard">
            <div class="filtro-titulo">
              <div class="avatar flex-shrink-0">
                <span class="avatar-initial rounded bg-label-primary"><i class="fa-regular fa-calendar"></i></span>
              </div>
              <div class="min-w-0">
                <small class="text-muted">Período de análisis</small>
                <span class="fw-semibold" id="dashboardPeriodoEtiqueta" aria-live="polite"><?php echo $mesesEs[$mesSel] . ' ' . $anioSel; ?></span>
                <span class="spinner-border spinner-border-sm text-primary ms-2 d-none" id="dashboardPeriodoSpinner" role="status" aria-label="Cargando"></span>
              </div>
            </div>

            <div class="filtro-controles">
              <button type="button" class="btn btn-sm btn-outline-secondary" id="dashboardMesAnterior" title="Mes anterior" aria-label="Mes anterior">
                <i class="fa-solid fa-chevron-left"></i>
              </button>

              <label for="dashboardMes" class="visually-hidden">Mes</label>
              <select id="dashboardMes" class="form-select form-select-sm" aria-label="Mes">
                <?php foreach ($mesesEs as $num => $nombre): ?>
                  <option value="<?php echo $num; ?>" <?php echo $num === $mesSel ? 'selected' : ''; ?>><?php echo $nombre; ?></option>
                <?php endforeach; ?>
              </select>

              <label for="dashboardAnio" class="visually-hidden">Año</label>
              <select id="dashboardAnio" class="form-select form-select-sm" aria-label="Año">
                <?php for ($a = $anioActual; $a >= $anioInicio; $a--): ?>
                  <option value="<?php echo $a; ?>" <?php echo $a === $anioSel ? 'selected' : ''; ?>><?php echo $a; ?></option>
                <?php endfor; ?>
              </select>

              <button type="button" class="btn btn-sm btn-outline-secondary" id="dashboardMesSiguiente" title="Mes siguiente" aria-label="Mes siguiente">
                <i class="fa-solid fa-chevron-right"></i>
              </button>

              <button type="button" class="btn btn-sm btn-outline-primary" id="dashboardPeriodoHoy" title="Volver al mes actual">
                <i class="fa-solid fa-rotate-left me-1"></i> Mes actual
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <!-- ============ /Filtro de período ============ -->

    <div class="row">
      <div class="col-12">
        <div id="contenedor-suscripcion"></div>
      </div>
    </div>

    <!-- Bienvenida + Rentabilidad -->
    <div class="row">
      <div class="col-lg-9 mb-4 order-0" data-no-reorganizar="true">
        <div class="card h-100">
          <div class="d-flex align-items-center row h-100">

            <div class="col-sm-7">
              <div class="card-body">
                <h5 class="card-title text-primary mb-2"> Bienvenido a <b>ISA Software!</b> 🎉 </h5>
                <p class="mb-2">Aquí encontrarás todo para estar al día con tu negocio.</p>

                <small class="text-primary fw-semibold">
                  <i class="fa fa-location-dot"></i>
                  <?php echo htmlspecialchars(strtoupper($_SESSION["Ses_bodega_des"] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                </small>
                <br>

                <small class="text-primary fw-semibold">
                  <i class="fa fa-user"></i>
                  <?php echo htmlspecialchars(strtoupper($_SESSION["Ses_usuario"] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                </small>
              </div>
            </div>

            <div class="col-sm-5 text-center text-sm-left align-self-end">
              <div class="card-body pb-0 px-0 px-md-4">
                <img
                  src="../../assets/img/illustrations/man-with-laptop-light.png"
                  height="90"
                  alt="">
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- Rentabilidad -->
      <div class="col-lg-3 mb-4 d-none" data-permiso="dashboard-rentabilidad" data-periodo="mes">
        <div class="card h-100">
          <div class="card-body">
            <div class="card-title d-flex align-items-start justify-content-between">
              <div class="avatar flex-shrink-0 me-4"> <span class="avatar-initial rounded bg-label-primary"><i class="fa-solid fa-chart-simple"></i></span> </div>
            </div>
            <span>Rentabilidad</span>
            <h3 class="card-title text-nowrap mb-1" id="dashboard-rentabilidad">$0</h3>
            <small class="text-success fw-semibold"><i class="bx bx-up-arrow-alt"></i> </small>
          </div>
        </div>
      </div>
      <!-- /Rentabilidad -->

      <!-- Cumplimiento del presupuesto (bodega de la sesión) -->
      <div class="col-lg-3 mb-4 d-none" data-permiso="dashboard-presupuesto" data-periodo="mes">
        <div class="card h-100">
          <div class="card-body">
            <div class="card-title d-flex align-items-start justify-content-between">
              <div class="avatar flex-shrink-0 me-4"> <span class="avatar-initial rounded bg-label-primary"><i class="fa-solid fa-bullseye"></i></span> </div>
              <div class="dropdown">
                <button class="btn p-0" type="button" id="cardPresupuesto" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Opciones">
                  <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardPresupuesto">
                  <a class="dropdown-item" href="javascript:void(0);" onclick="mostrarDetallePresupuesto()"><i class="fa-solid fa-list"></i>&nbsp;Ver detalle</a>
                </div>
              </div>
            </div>
            <span>Cumplimiento presupuesto</span>
            <h3 class="card-title text-nowrap mb-1" id="dashboard-presupuesto">0%</h3>
            <div class="dash-pres-extra">
              <div class="progress dash-pres-barra mb-1">
                <div class="progress-bar bg-primary" id="dashboard-presupuesto-barra" role="progressbar" style="width:0%"></div>
              </div>
              <small class="text-muted d-block text-truncate" id="dashboard-presupuesto-resumen">—</small>
            </div>
          </div>
        </div>
      </div>
      <!-- /Cumplimiento del presupuesto -->
    </div>


    <!-- Indicadores del período -->
    <div class="row">
      <!-- Ventas -->
      <div class="col-lg-3 col-md-12 col-12 mb-4 d-none" data-permiso="dashboard-ventas" data-periodo="mes">
        <div class="card">
          <div class="card-body">
            <div class="card-title d-flex align-items-start justify-content-between">
              <div class="avatar flex-shrink-0 me-4"> <span class="avatar-initial rounded bg-label-success"><i class="fa-solid fa-wallet"></i></span> </div>
              <div class="dropdown">
                <button class="btn p-0" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Opciones">
                  <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                  <a class="dropdown-item" href="javascript:void(0);" onclick="mostrarDetalleMovimientos('ventas')"><i class="fa-solid fa-list"></i>&nbsp;Ver detalle</a>
                </div>
              </div>
            </div>
            <span>Ventas mes + IVA</span>
            <h3 class="card-title text-nowrap mb-1" id="dashboard-ventas">$0</h3>
            <small class="text-success fw-semibold"><i class="bx bx-up-arrow-alt"></i> Salidas de productos</small>
          </div>
        </div>
      </div>
      <!-- Devoluciones -->
      <div class="col-lg-3 col-md-12 col-12 mb-4 d-none" data-permiso="dashboard-devoluciones" data-periodo="mes">
        <div class="card">
          <div class="card-body">
            <div class="card-title d-flex align-items-start justify-content-between">
              <div class="avatar flex-shrink-0 me-4"> <span class="avatar-initial rounded bg-label-danger"><i class="fa-solid fa-person-walking-arrow-loop-left"></i> </span></div>
              <div class="dropdown">
                <button class="btn p-0" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Opciones">
                  <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                  <a class="dropdown-item" href="javascript:void(0);" onclick="mostrarDetalleMovimientos('devoluciones')"><i class="fa-solid fa-list"></i>&nbsp;Ver detalle</a>
                </div>
              </div>
            </div>
            <span>Devoluciones mes + IVA</span>
            <h3 class="card-title text-nowrap mb-1" id="dashboard-devoluciones">$0</h3>
            <small class="text-danger fw-semibold"><i class="bx bx-down-arrow-alt"></i> Entradas de productos</small>
          </div>
        </div>
      </div>
      <!-- Ingresos -->
      <div class="col-lg-3 col-md-12 col-12 mb-4 d-none" data-permiso="dashboard-ingresos" data-periodo="mes">
        <div class="card">
          <div class="card-body">
            <div class="card-title d-flex align-items-start justify-content-between">
              <div class="avatar flex-shrink-0 me-4"> <span class="avatar-initial rounded bg-label-info"> <i class="fa-solid fa-inbox"></i> </span></div>
              <div class="dropdown">
                <button class="btn p-0" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Opciones">
                  <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                  <a class="dropdown-item" href="javascript:void(0);" onclick="mostrarDetalleMovimientos('ingresos')"><i class="fa-solid fa-list"></i>&nbsp;Ver detalle</a>
                </div>
              </div>
            </div>
            <span>Ingresos</span>
            <h3 class="card-title text-nowrap mb-1" id="dashboard-ingresos">$0</h3>
            <small class="text-info fw-semibold"><i class="bx bx-up-arrow-alt"></i> Entradas de dinero</small>
          </div>
        </div>
      </div>
      <!-- Egresos -->
      <div class="col-lg-3 col-md-12 col-12 mb-4 d-none" data-permiso="dashboard-egresos" data-periodo="mes">
        <div class="card">
          <div class="card-body">
            <div class="card-title d-flex align-items-start justify-content-between">
              <div class="avatar flex-shrink-0 me-4"> <span class="avatar-initial rounded bg-label-warning"> <i class="fa-solid fa-right-from-bracket"></i> </span></div>
              <div class="dropdown">
                <button class="btn p-0" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Opciones">
                  <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                  <a class="dropdown-item" href="javascript:void(0);" onclick="mostrarDetalleMovimientos('egresos')"><i class="fa-solid fa-list"></i>&nbsp;Ver detalle</a>
                </div>
              </div>
            </div>
            <span>Egresos</span>
            <h3 class="card-title text-nowrap mb-1" id="dashboard-egresos">$0</h3>
            <small class="text-warning fw-semibold"><i class="bx bx-down-arrow-alt"></i> Salidas de dinero</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Alertas operativas (estado actual: NO dependen del período) -->
    <div class="row">
      <!-- Promociones vigentes -->
      <div class="col-12 col-lg-3 order-2 order-md-3 order-lg-2 mb-4 d-none" data-permiso="dashboard-promociones">
        <div id="dashboard-promociones-vigentes"></div>
      </div>
      <!-- Productos a vencer -->
      <div class="col-12 col-lg-3 order-2 order-md-3 order-lg-2 mb-4 d-none" data-permiso="dashboard-vencimientos">
        <div id="dashboard-proximos-vencer"></div>
      </div>
      <!-- Stock por debajo del mínimo -->
      <div class="col-12 col-lg-3 order-2 order-md-3 order-lg-2 mb-4 d-none" data-permiso="dashboard-stock-minimo">
        <div id="dashboard-stock-minimo"></div>
      </div>
      <!-- Facturas a vencer -->
      <div class="col-12 col-lg-3 order-2 order-md-3 order-lg-2 mb-4 d-none" data-permiso="dashboard-FacturasVmcto">
        <div id="dashboard-facturas-vencer"></div>
      </div>
    </div>

    <div class="row">
      <!-- Ventas mensuales -->
      <div class="col-12 col-lg-4 order-2 order-md-3 order-lg-2 mb-4 d-none" data-permiso="dashboard-ventaMes" data-periodo="anio">
        <div class="card" style="height: 100%;">
          <div class="row row-bordered g-0" style="height: 100%; display: flex; flex-direction: column;">
            <h5 class="card-header m-0 me-2 pb-3">Ventas mensuales <span class="text-muted fw-normal" id="ventasMensualesAnio"><?php echo $mesesEs[$mesSel] . ' ' . $anioSel; ?></span></h5>
            <div id="totalRevenueChart" class="px-2" style="flex: 1;  max-height: 450px;"></div>
          </div>
        </div>
      </div>
      <!--/ Ventas mensuales -->
      <!-- Ventas diarias -->
      <div class="col-12 col-lg-8 order-2 order-md-3 order-lg-2 mb-4 d-none" data-permiso="dashboard-ventaDia" data-periodo="mes">
        <div class="card" style="height: 100%;">
          <div class="row row-bordered g-0" style="height: 100%; display: flex; flex-direction: column;">
            <h5 class="card-header m-0 me-2 pb-3">Ventas diarias <span class="text-muted fw-normal" id="ventasDiariasPeriodo"><?php echo $mesesEs[$mesSel] . ' ' . $anioSel; ?></span></h5>
            <div id="ventasDiariasChart" class="px-2" style="flex: 1;  max-height: 450px;"></div>
          </div>
        </div>
      </div>
      <!--/ Ventas diarias -->
    </div>
  </div>
</div>


<div class="modal fade" id="modalCalendarioFacturas" tabindex="-1" aria-labelledby="modalCalendarioFacturasLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="modalCalendarioFacturasLabel">
          Calendario de pagos
        </h5>

        <button type="button"
          class="btn-close"
          data-bs-dismiss="modal"
          aria-label="Cerrar">
        </button>
      </div>

      <div class="modal-body p-0">
        <iframe
          id="iframeCalendarioFacturas"
          src="about:blank"
          width="100%"
          height="400"
          frameborder="0"
          style="border: none;">
        </iframe>
      </div>

    </div>
  </div>
</div>

<!-- Modal: detalle de ventas / devoluciones / ingresos / egresos -->
<div class="modal fade" id="modalMovimientos" tabindex="-1" aria-labelledby="modalMovimientosLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header border-bottom">
        <h5 class="modal-title" id="modalMovimientosLabel">
          <span id="movTitulo">Detalle</span>
          <small class="text-muted fw-normal d-block mt-1" id="movPeriodo"></small>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <!-- Resumen -->
        <div class="row g-3 mb-3">
          <div class="col-6 col-md-3">
            <small class="text-muted d-block">Total</small>
            <h6 class="mb-0 text-primary" id="movTotal">—</h6>
          </div>
          <div class="col-6 col-md-3">
            <small class="text-muted d-block">Documentos</small>
            <h6 class="mb-0" id="movCantidad">—</h6>
          </div>
          <div class="col-6 col-md-3">
            <small class="text-muted d-block">Promedio por documento</small>
            <h6 class="mb-0" id="movPromedio">—</h6>
          </div>
          <div class="col-6 col-md-3" id="movNetoCol">
            <small class="text-muted d-block">Valor sin IVA</small>
            <h6 class="mb-0" id="movNeto">—</h6>
          </div>
        </div>

        <!-- Vista y búsqueda -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
          <div class="btn-group btn-group-sm" role="group" id="movVistas">
            <input type="radio" class="btn-check" name="movVista" id="movVistaDoc" value="documento" checked>
            <label class="btn btn-outline-primary" for="movVistaDoc"><i class="fa-regular fa-file-lines"></i> Documentos</label>
            <input type="radio" class="btn-check" name="movVista" id="movVistaDia" value="dia">
            <label class="btn btn-outline-primary" for="movVistaDia"><i class="fa-regular fa-calendar"></i> Por día</label>
            <input type="radio" class="btn-check" name="movVista" id="movVistaUsuario" value="usuario">
            <label class="btn btn-outline-primary" for="movVistaUsuario"><i class="fa-solid fa-user"></i> Por usuario</label>
            <input type="radio" class="btn-check" name="movVista" id="movVistaTercero" value="tercero">
            <label class="btn btn-outline-primary" for="movVistaTercero"><i class="fa-solid fa-address-book"></i> Por tercero</label>
          </div>
          <div class="input-group input-group-sm" style="max-width: 280px;">
            <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input type="search" class="form-control" id="movBuscar" placeholder="Buscar...">
          </div>
        </div>

        <div class="table-responsive border rounded" style="max-height: 55vh;">
          <table class="table table-sm table-hover align-middle mb-0 tabla-primary" id="tblMovimientos">
            <thead></thead>
            <tbody></tbody>
          </table>
        </div>
        <div class="form-text" id="movNota"></div>
      </div>
      <div class="modal-footer border-top">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: detalle del cumplimiento del presupuesto -->
<div class="modal fade" id="modalPresupuesto" tabindex="-1" aria-labelledby="modalPresupuestoLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header border-bottom">
        <h5 class="modal-title" id="modalPresupuestoLabel">
          <i class="fa-solid fa-bullseye"></i> Cumplimiento del presupuesto
          <small class="text-muted fw-normal d-block mt-1" id="presDetallePeriodo"></small>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <!-- Resumen -->
        <div class="row g-3 mb-3">
          <div class="col-6 col-md-3">
            <small class="text-muted d-block">Presupuesto</small>
            <h6 class="mb-0" id="presDetPresupuesto">—</h6>
          </div>
          <div class="col-6 col-md-3">
            <small class="text-muted d-block">Venta neta</small>
            <h6 class="mb-0" id="presDetVenta">—</h6>
          </div>
          <div class="col-6 col-md-3">
            <small class="text-muted d-block">Cumplimiento</small>
            <h6 class="mb-0" id="presDetCumplimiento">—</h6>
          </div>
          <div class="col-6 col-md-3">
            <small class="text-muted d-block" id="presDetFaltaTitulo">Falta por vender</small>
            <h6 class="mb-0" id="presDetFalta">—</h6>
          </div>
        </div>
        <div class="alert alert-primary py-2 small mb-3 d-none" id="presDetRitmo"></div>

        <!-- Tipo de presupuesto (solo los que tienen valores en el mes) -->
        <ul class="nav nav-pills mb-3" id="presDetTipos" role="tablist"></ul>

        <div class="table-responsive border rounded" style="max-height: 50vh;">
          <table class="table table-sm table-hover align-middle mb-0 tabla-primary" id="tblPresupuestoDetalle">
            <thead>
              <tr>
                <th id="presDetThRef">Zona</th>
                <th class="text-end">Presupuesto</th>
                <th class="text-end">Venta</th>
                <th class="text-end">Diferencia</th>
                <th style="min-width: 150px;">Cumplimiento</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
        <div class="form-text" id="presDetNota"></div>
      </div>
      <div class="modal-footer border-top">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!--
  Módulo de período del dashboard.
  Se ejecuta ANTES de dashboard.js (que se carga desde el footer), por lo que
  window.DashboardPeriodo ya existe cuando el controlador arranca.
  data-periodo="mes"  -> se recarga al cambiar año o mes
  data-periodo="anio" -> solo se recarga cuando cambia el año
-->
<script>
  (function() {
    'use strict';

    var MESES = <?php echo json_encode(array_values($mesesEs), JSON_UNESCAPED_UNICODE); ?>;
    var HOY = { anio: <?php echo $anioActual; ?>, mes: <?php echo $mesActual; ?> };
    var MIN_ANIO = <?php echo $anioInicio; ?>;

    var elAnio = document.getElementById('dashboardAnio');
    var elMes = document.getElementById('dashboardMes');
    var elHoy = document.getElementById('dashboardPeriodoHoy');
    var elPrev = document.getElementById('dashboardMesAnterior');
    var elNext = document.getElementById('dashboardMesSiguiente');
    var elEtiqueta = document.getElementById('dashboardPeriodoEtiqueta');
    var elSpinner = document.getElementById('dashboardPeriodoSpinner');

    var estado = {
      anio: parseInt(elAnio.value, 10),
      mes: parseInt(elMes.value, 10),
      token: 0
    };
    var temporizadorSeguridad = null;

    // Deshabilita los meses futuros cuando el año seleccionado es el actual
    function ajustarMeses() {
      var esAnioActual = parseInt(elAnio.value, 10) === HOY.anio;
      Array.prototype.forEach.call(elMes.options, function(op) {
        op.disabled = esAnioActual && parseInt(op.value, 10) > HOY.mes;
      });
      if (esAnioActual && parseInt(elMes.value, 10) > HOY.mes) {
        elMes.value = HOY.mes;
      }
    }

    function actualizarUI() {
      var a = parseInt(elAnio.value, 10);
      var m = parseInt(elMes.value, 10);
      var esActual = a === HOY.anio && m === HOY.mes;

      elEtiqueta.textContent = MESES[m - 1] + ' ' + a;
      elHoy.classList.toggle('d-none', esActual);
      elPrev.disabled = a === MIN_ANIO && m === 1;
      elNext.disabled = esActual;

      // El período queda en la URL (compartible / F5 lo conserva) sin recargar
      try {
        var url = new URL(window.location.href);
        if (esActual) {
          url.searchParams.delete('anio');
          url.searchParams.delete('mes');
        } else {
          url.searchParams.set('anio', a);
          url.searchParams.set('mes', m);
        }
        window.history.replaceState(null, '', url);
      } catch (e) {}
    }

    function iniciarCarga(cambioAnio) {
      var selector = cambioAnio ? '[data-periodo]' : '[data-periodo="mes"]';
      document.querySelectorAll(selector).forEach(function(t) {
        t.classList.add('dashboard-cargando');
        t.setAttribute('aria-busy', 'true');
      });
      elSpinner.classList.remove('d-none');
      // Seguro: si el controlador no avisa, no dejamos todo "cargando" para siempre
      clearTimeout(temporizadorSeguridad);
      temporizadorSeguridad = setTimeout(function() { finalizarCarga(); }, 20000);
    }

    function finalizarCarga(token) {
      // Respuesta de un período anterior: se ignora
      if (typeof token === 'number' && token !== estado.token) return;
      clearTimeout(temporizadorSeguridad);
      document.querySelectorAll('[data-periodo]').forEach(function(t) {
        t.classList.remove('dashboard-cargando');
        t.removeAttribute('aria-busy');
      });
      elSpinner.classList.add('d-none');
    }

    function aplicar() {
      var anio = parseInt(elAnio.value, 10);
      var mes = parseInt(elMes.value, 10);
      actualizarUI();
      if (anio === estado.anio && mes === estado.mes) return;

      var cambioAnio = anio !== estado.anio;
      estado.anio = anio;
      estado.mes = mes;
      estado.token++;

      iniciarCarga(cambioAnio);

      document.dispatchEvent(new CustomEvent('dashboard:periodo', {
        detail: {
          anio: estado.anio,
          mes: estado.mes,
          token: estado.token,
          cambioAnio: cambioAnio,
          esMesActual: estado.anio === HOY.anio && estado.mes === HOY.mes
        }
      }));
    }

    function moverMes(delta) {
      var a = parseInt(elAnio.value, 10);
      var m = parseInt(elMes.value, 10) + delta;
      if (m < 1) { m = 12; a--; }
      if (m > 12) { m = 1; a++; }
      if (a < MIN_ANIO || a > HOY.anio || (a === HOY.anio && m > HOY.mes)) return;
      elAnio.value = a;
      ajustarMeses();
      elMes.value = m;
      aplicar();
    }

    elAnio.addEventListener('change', function() { ajustarMeses(); aplicar(); });
    elMes.addEventListener('change', aplicar);
    elPrev.addEventListener('click', function() { moverMes(-1); });
    elNext.addEventListener('click', function() { moverMes(1); });
    elHoy.addEventListener('click', function() {
      elAnio.value = HOY.anio;
      ajustarMeses();
      elMes.value = HOY.mes;
      aplicar();
    });

    ajustarMeses();
    actualizarUI();

    // API pública para dashboard.js
    window.DashboardPeriodo = {
      get: function() { return { anio: estado.anio, mes: estado.mes, token: estado.token }; },
      iniciarCarga: iniciarCarga,
      finalizarCarga: finalizarCarga
    };
  })();
</script>

<?php

$scriptsPagina = [
  '../shared/servicios-dashboard.js?' . rand(),
  '../controllers/dashboard.js?' . rand()/*,  
  'https://www.gstatic.com/firebasejs/12.18.0/firebase-app-compat.js',
  'https://www.gstatic.com/firebasejs/12.18.0/firebase-messaging-compat.js',
  'https://thecyctechnology.com/development/push-notifications.js'*/
];

include '../dependencies/footer.php';

?>