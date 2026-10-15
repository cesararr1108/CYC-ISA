<?php
   /* require("../models/funciones.php");
    session_start();
    Redireccionar();*/
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<link type="text/css" rel="stylesheet" href="../resources/css/bootstrap.min.css"/>
<link type="text/css" rel="stylesheet" href="../resources/css/BootstrapTabs.css?<?php echo rand()?>"/>
<link type="text/css" rel="stylesheet" href="../resources/css/menu/font-awesome.min.css"/>
<link type="text/css" rel="stylesheet" href="../resources/css/start/jquery-ui-1.9.2.custom.css"/>
<link type="text/css" rel="stylesheet" href="../lib/SweetAlert/sweet-alert.css"/>
<link type="text/css" rel="stylesheet" href="../resources/css/Animate.css?<?php  echo(rand()); ?>" >

<script type="text/javascript" src="../lib/js/jquery-2.1.1.min.js"></script>
<script type="text/javascript" src="../lib/js/jquery-ui-1.9.2.custom.js"></script>
<script type="text/javascript" src="../lib/js/bootstrap.min.js"></script>
<script type="text/javascript" src="../lib/js/funciones.js?<?php echo rand()?>"></script>
<script type="text/javascript" src="../lib/SweetAlert/sweet-alert.min.js"></script>
<script type="text/javascript" src="../controllers/Rifas.js?<?php echo rand();?>"></script>
<title>Rifas</title>
<style>
	body{ padding-top: 10px; }
	.ev-reglas .panel-body{ min-height: 150px; font-size: 13px; }
	.ev-card{ border-radius: 6px; padding: 10px 14px; margin-bottom: 12px; border: 1px solid #ddd; background: #fafafa; }
	.ev-card h4{ margin-top: 4px; }
	.ev-card .ev-big{ font-size: 26px; font-weight: bold; }
	.ev-ok{ border-left: 6px solid #5cb85c; }
	.ev-no{ border-left: 6px solid #d9534f; }
	.ev-wait{ border-left: 6px solid #f0ad4e; }
	.ev-done{ border-left: 6px solid #337ab7; }
	.ev-progress{ height: 22px; margin-bottom: 6px; }
	.ev-progress .progress-bar{ line-height: 22px; font-weight: bold; }
	.ev-ganador{ font-size: 22px; text-align: center; padding: 15px; }
	.table td, .table th{ vertical-align: middle !important; }
	.nav-tabs > li > a{ font-weight: bold; }
</style>
</head>
<body oncontextmenu="return false;">

<input id="TipoU"      type="hidden" value="<?php if(!empty($_SESSION["Ses_Rol"])){ echo $_SESSION["Ses_Rol"]; }else{echo 0;}  ?>" readonly />
<input id="GetUsuario" type="hidden" value="<?php if(!empty($_GET["C0d1g0"])){ echo htmlspecialchars($_GET["C0d1g0"]); }else{echo 0;}  ?>" readonly>

<div class="container-fluid">

  <!-- ===================== REGLAS DEL EVENTO ===================== -->
  <div class="row ev-reglas">
    <div class="col-md-4">
      <div class="panel panel-info">
        <div class="panel-heading"><b><span class="fa fa-plug"></span> ELECTROMENORES</b></div>
        <div class="panel-body">
          Por compras mínimas de <b>$50.000</b> en <b>10 laboratorios patrocinadores</b>,
          el cliente reclama inmediatamente un electromenor.<br>
          <span class="label label-warning">Hasta las 2:00 pm</span>
          <span class="label label-default">Unidades limitadas</span>
          <h4 class="text-center" style="margin-top:10px;">Disponibles: <span id="evElectroDisp">-</span></h4>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="panel panel-warning">
        <div class="panel-heading"><b><span class="fa fa-clock-o"></span> SORTEOS DE LA TARDE</b></div>
        <div class="panel-body">
          Compra total acumulada (cualquier laboratorio):
          <table class="table table-condensed" style="margin-bottom:4px;">
            <tbody id="evReglasSorteos"></tbody>
          </table>
          Los ganadores reciben el <b>5%</b> de su compra (compra máxima $10.000.000).
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="panel panel-success">
        <div class="panel-heading"><b><span class="fa fa-trophy"></span> PREMIOS MAYORES (CIERRE)</b></div>
        <div class="panel-body">
          1 bono de <b>$10.000.000</b> y 2 bonos de <b>$5.000.000</b>.<br>
          <b>Dinámica #1:</b> compras mínimas de $50.000 en 20 labs patrocinadores.<br>
          <b>Dinámica #2:</b> compras mínimas de $1.000.000 en 8 labs patrocinadores.<br>
          <span class="label label-danger">1 SOLA BOLETA POR CLIENTE</span>
        </div>
      </div>
    </div>
  </div>

  <ul class="nav nav-tabs" role="tablist">
    <li class="active"><a href="#tabCliente"   data-toggle="tab"><span class="fa fa-search"></span> Consulta cliente</a></li>
    <li><a href="#tabSorteos"  data-toggle="tab"><span class="fa fa-clock-o"></span> Sorteos de la tarde</a></li>
    <li><a href="#tabMayores"  data-toggle="tab"><span class="fa fa-trophy"></span> Premios mayores</a></li>
    <li><a href="#tabElectro"  data-toggle="tab"><span class="fa fa-plug"></span> Electromenores entregados</a></li>
  </ul>

  <div class="tab-content" style="padding-top:15px;">

    <!-- ===================== CONSULTA CLIENTE ===================== -->
    <div class="tab-pane active" id="tabCliente">
      <div class="row">
        <div class="col-md-4">
          <div class="input-group">
            <span class="input-group-addon"><b>CÓDIGO SAP</b></span>
            <input type="text" class="form-control input-lg" id="TxtPassportRifas" autocomplete="off" maxlength="10"/>
            <span class="input-group-btn">
              <button class="btn btn-primary btn-lg" id="btnConsultar"><span class="fa fa-search"></span></button>
            </span>
          </div>
        </div>
        <div class="col-md-8">
          <h3 id="SpClienteRifas" style="margin-top:6px;"></h3>
        </div>
      </div>

      <div id="evResultado" style="display:none; margin-top:15px;">
        <div class="row">
          <div class="col-md-3 col-sm-6">
            <div class="ev-card"><small>COMPRA TOTAL</small><div class="ev-big" id="SpCompras"></div></div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="ev-card"><small>COMPRAS LAB. PARTICIPANTES</small><div class="ev-big text-danger" id="comprasLabPart"></div></div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="ev-card">
              <small>LABORATORIOS CON / SIN COMPRA</small><br>
              <button class="btn btn-success" id="n_lab_compas" title="Laboratorios con compra"></button> /
              <button class="btn btn-danger"  id="n_lab_sin_compas" title="Ver laboratorios donde no ha comprado"></button>
            </div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="ev-card"><small>REGISTRO EN EL EVENTO</small><div id="evRegistrado" style="font-size:18px;"></div></div>
          </div>
        </div>

        <div class="row">
          <!-- Electromenor -->
          <div class="col-md-4">
            <div class="ev-card" id="cardElectro">
              <h4><span class="fa fa-plug"></span> ELECTROMENOR</h4>
              <div id="evElectroInfo"></div>
              <div class="text-center" style="margin-top:8px;">
                <button class="btn btn-info btn-lg" id="btnElectro" style="display:none;">
                  <span class="fa fa-gift"></span> Entregar electromenor
                </button>
              </div>
            </div>
          </div>
          <!-- Sorteos -->
          <div class="col-md-4">
            <div class="ev-card ev-done" id="cardSorteos">
              <h4><span class="fa fa-clock-o"></span> SORTEOS DE LA TARDE</h4>
              <table class="table table-condensed" style="margin-bottom:0;">
                <thead><tr><th>Hora</th><th>Mínimo</th><th>Compra al corte</th><th>Estado</th></tr></thead>
                <tbody id="evSorteosCliente"></tbody>
              </table>
            </div>
          </div>
          <!-- Premios mayores -->
          <div class="col-md-4">
            <div class="ev-card" id="cardMayores">
              <h4><span class="fa fa-trophy"></span> PREMIOS MAYORES</h4>
              <div id="evMayoresInfo"></div>
              <div class="text-center" style="margin-top:8px;">
                <button class="btn btn-success btn-lg" id="btnMayores" style="display:none;">
                  <span class="fa fa-ticket"></span> Generar boleta
                </button>
              </div>
            </div>
          </div>
        </div>

        <h3 class="text-center">DETALLE DE COMPRAS POR LABORATORIO PATROCINADOR</h3>
        <div class="table-responsive">
          <table class="table table-striped table-condensed table-bordered">
            <thead>
              <tr class="info">
                <th>#</th>
                <th>LABORATORIO</th>
                <th class="text-right">COMPRA</th>
                <th class="text-center">&ge; $50.000</th>
                <th class="text-center">&ge; $1.000.000</th>
              </tr>
            </thead>
            <tbody id="ResultadosRifas"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ===================== SORTEOS DE LA TARDE ===================== -->
    <div class="tab-pane" id="tabSorteos">
      <div class="row">
        <div class="col-md-12" id="evBotonesSorteo" style="margin-bottom:10px;"></div>
      </div>
      <div class="row">
        <div class="col-md-7">
          <div class="panel panel-default">
            <div class="panel-heading">
              <b>PARTICIPANTES <span id="evSorteoTitulo"></span></b>
              <span class="badge pull-right" id="evSorteoTotal">0</span>
            </div>
            <div class="panel-body">
              <div id="evSorteoAviso"></div>
              <div class="text-center" style="margin-bottom:10px;">
                <button class="btn btn-warning btn-lg" id="btnSortear" style="display:none;">
                  <span class="fa fa-random"></span> Sortear ganador
                </button>
              </div>
              <div id="evGanadorActual"></div>
              <div class="table-responsive" style="max-height:500px; overflow:auto;">
                <table class="table table-striped table-condensed">
                  <thead><tr><th>#</th><th>CÓDIGO</th><th>CLIENTE</th><th class="text-right">COMPRA</th><th>REG.</th><th>GANÓ</th></tr></thead>
                  <tbody id="evSorteoParticipantes"></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-5">
          <div class="panel panel-success">
            <div class="panel-heading"><b>GANADORES</b>
              <button class="btn btn-xs btn-default pull-right" id="btnRefrescarGanadores"><span class="fa fa-refresh"></span></button>
            </div>
            <div class="table-responsive">
              <table class="table table-condensed">
                <thead><tr><th>HORA</th><th>CLIENTE</th><th class="text-right">COMPRA</th><th class="text-right">PREMIO 5%</th><th></th></tr></thead>
                <tbody id="evGanadores"></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ===================== PREMIOS MAYORES ===================== -->
    <div class="tab-pane" id="tabMayores">
      <div class="row">
        <div class="col-md-8">
          <div class="panel panel-success">
            <div class="panel-heading"><b>CLIENTES CON BOLETA PARA PREMIOS MAYORES</b>
              <span class="badge pull-right" id="evMayoresTotal">0</span>
            </div>
            <div class="table-responsive" style="max-height:600px; overflow:auto;">
              <table class="table table-striped table-condensed">
                <thead><tr><th>#</th><th>CÓDIGO SAP</th><th>CLIENTE</th></tr></thead>
                <tbody id="evMayoresLista"></tbody>
              </table>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="alert alert-info">
            Las boletas se generan desde <b>Consulta cliente</b> cuando el cliente valida.
            Antes del sorteo final puede generar las boletas de todos los clientes registrados que
            cumplan alguna dinámica y aún no la tengan.
          </div>
          <button class="btn btn-success btn-block" id="btnMayoresMasivo"><span class="fa fa-ticket"></span> Generar boletas pendientes</button>
          <button class="btn btn-default btn-block" id="btnMayoresRefrescar"><span class="fa fa-refresh"></span> Actualizar lista</button>
        </div>
      </div>
    </div>

    <!-- ===================== ELECTROMENORES ===================== -->
    <div class="tab-pane" id="tabElectro">
      <div class="panel panel-info">
        <div class="panel-heading"><b>ELECTROMENORES ENTREGADOS</b>
          <span class="badge pull-right" id="evElectroTotal">0</span>
        </div>
        <div class="table-responsive">
          <table class="table table-striped table-condensed">
            <thead><tr><th>#</th><th>CÓDIGO SAP</th><th>CLIENTE</th><th class="text-center">LABS</th><th class="text-right">COMPRA LABS</th><th>USUARIO</th><th>FECHA</th></tr></thead>
            <tbody id="evElectroLista"></tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>

<div id="ModalFaltantes" class="modal fade bd-example-modal-lg" role="dialog">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">LABORATORIOS DONDE NO HAS COMPRADO</h4>
      </div>
      <div class="modal-body" id="DivLabSinCompras"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-info" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
</body>
</html>
