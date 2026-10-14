<?php

/**
 * Colillas (comprobantes) de pago de nómina.
 *
 * Disponibles en cuanto la nómina queda CONTABILIZADA, sin esperar el PDF del servicio de
 * nómina electrónica. Si ya fue EMITIDA se muestra además el CUNE.
 *
 * Uso (POST o GET, abre en pestaña nueva):
 *   nominaColilla.php?ids=12,15,18            → colillas de esas nóminas
 *   nominaColilla.php?periodo=2026-10         → todas las contabilizadas/emitidas del período
 *   &auto=1                                   → abre el diálogo de impresión al cargar
 */

require_once(__DIR__ . '/nominaHelpers.php');

if (empty($_SESSION['Ses_id'])) {
  http_response_code(401);
  exit('Sesión no válida. Inicie sesión nuevamente.');
}

function colilla_h($v)
{
  return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function colilla_money($v)
{
  return '$ ' . number_format((float)$v, 0, ',', '.');
}

function colilla_cant($v)
{
  $v = (float)$v;
  return $v == 0 ? '' : rtrim(rtrim(number_format($v, 2, ',', '.'), '0'), ',');
}

/** Número a letras (pesos colombianos) */
function colilla_letras($numero)
{
  $numero = (int)round($numero);
  if ($numero === 0) {
    return 'CERO PESOS M/CTE';
  }
  $unidades = array('', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE', 'DIEZ',
    'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE', 'VEINTE',
    'VEINTIÚN', 'VEINTIDÓS', 'VEINTITRÉS', 'VEINTICUATRO', 'VEINTICINCO', 'VEINTISÉIS', 'VEINTISIETE', 'VEINTIOCHO', 'VEINTINUEVE');
  $decenas = array('', '', '', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA');
  $centenas = array('', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS',
    'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS');

  $tres = function ($n) use ($unidades, $decenas, $centenas) {
    if ($n === 100) {
      return 'CIEN';
    }
    $c = intdiv($n, 100);
    $r = $n % 100;
    $txt = $centenas[$c];
    if ($r > 0) {
      if ($r < 30) {
        $txt .= ' ' . $unidades[$r];
      } else {
        $txt .= ' ' . $decenas[intdiv($r, 10)] . ($r % 10 ? ' Y ' . $unidades[$r % 10] : '');
      }
    }
    return trim($txt);
  };

  $millones = intdiv($numero, 1000000);
  $miles = intdiv($numero % 1000000, 1000);
  $resto = $numero % 1000;
  $txt = '';
  if ($millones > 0) {
    $txt .= $millones === 1 ? 'UN MILLÓN' : $tres($millones) . ' MILLONES';
  }
  if ($miles > 0) {
    $txt .= ' ' . ($miles === 1 ? 'MIL' : $tres($miles) . ' MIL');
  }
  if ($resto > 0) {
    $txt .= ' ' . $tres($resto);
  }
  $txt = trim($txt);
  $de = ($resto === 0 && $miles === 0 && $millones > 0) ? ' DE' : '';
  return $txt . $de . ' PESOS M/CTE';
}

/* --------------------------------------------------------------------------- */

$ids = $_REQUEST['ids'] ?? '';
$ids = array_values(array_filter(array_map('intval', is_array($ids) ? $ids : explode(',', (string)$ids))));
$periodo = nomina_resolver_periodo($_REQUEST['periodo'] ?? '');

$where = array("n.estado_emision IN ('CONTABILIZADO','EMITIDO')");
if ($ids) {
  $where[] = 'n.id IN (' . implode(',', $ids) . ')';
} elseif ($periodo) {
  $where[] = "n.periodo_fecha_inicio = '{$periodo['fecha_inicio']}'";
} else {
  exit('Debe indicar las nóminas o el período.');
}

$nominas = nomina_select("SELECT n.*, c.cargo, c.fecha_inicio AS contrato_inicio, c.metodo_pago, c.banco,
                                 c.tipo_cuenta, c.numero_cuenta, c.salario_integral,
                                 eps.codigo_entidad AS eps_codigo, " . nomina_sql_nombre_tercero('teps') . " AS eps_nombre,
                                 afp.codigo_entidad AS afp_codigo, " . nomina_sql_nombre_tercero('tafp') . " AS afp_nombre,
                                 (SELECT rd.cufe FROM t_respuestas_dian rd
                                   WHERE rd.tipo = n.prefijo AND rd.numero = n.consecutivo AND IFNULL(rd.cufe,'') <> ''
                                   ORDER BY rd.fechaValidacion DESC LIMIT 1) AS cufe
                            FROM t_nomina n
                            LEFT JOIN t_nomina_contratos c ON c.id = n.id_contrato
                            LEFT JOIN t_nomina_entidades eps ON eps.id = c.id_entidad_salud
                            LEFT JOIN t_terceros teps ON teps.id = eps.id_tercero
                            LEFT JOIN t_nomina_entidades afp ON afp.id = c.id_entidad_pension
                            LEFT JOIN t_terceros tafp ON tafp.id = afp.id_tercero
                           WHERE " . implode(' AND ', $where) . "
                           ORDER BY n.trabajador_primer_apellido, n.trabajador_primer_nombre");

$detallesPorNomina = array();
if ($nominas) {
  $idsN = implode(',', array_map(function ($n) { return intval($n['id']); }, $nominas));
  foreach (nomina_select("SELECT id_nomina, id_concepto, concepto, tipo, cantidad, valor_unitario, total
                            FROM t_nomina_detalle
                           WHERE id_nomina IN ($idsN) AND total <> 0
                           ORDER BY tipo DESC, id") as $d) {
    $detallesPorNomina[intval($d['id_nomina'])][] = $d;
  }
}

// Empresa: se toleran distintos nombres de columna
$emp = nomina_select("SELECT * FROM t_empresa LIMIT 1");
$emp = $emp ? $emp[0] : array();
$pick = function ($row, $campos) {
  foreach ($campos as $c) {
    if (!empty($row[$c])) {
      return $row[$c];
    }
  }
  return '';
};
$empresa = array(
  'nombre'    => $pick($emp, array('razon_social', 'razonSocial', 'nombre', 'nombre_empresa', 'empresa', 'razon_comercial')),
  'nit'       => $pick($emp, array('nit')) . (($dv = $pick($emp, array('dv', 'digito_verificacion'))) !== '' ? "-$dv" : ''),
  'direccion' => $pick($emp, array('direccion', 'direccion_empresa')),
  'telefono'  => $pick($emp, array('telefono', 'telefonos', 'celular')),
  'ciudad'    => $pick($emp, array('ciudad', 'municipio')),
);

$metodos = array('10' => 'Efectivo', '30' => 'Transferencia débito', '31' => 'Transferencia crédito',
                 '42' => 'Consignación bancaria', '45' => 'Transferencia crédito bancario');
$auto = !empty($_REQUEST['auto']);
$meses = nomina_meses();

/** Etiqueta del período a partir de las fechas de la nómina */
$etiquetaPeriodo = function ($n) use ($meses) {
  $i = strtotime($n['periodo_fecha_inicio']);
  $f = strtotime($n['periodo_fecha_fin']);
  if (!$i || !$f) {
    return $n['periodo'];
  }
  return date('j', $i) . ' al ' . date('j', $f) . ' de ' . $meses[(int)date('n', $f)] . ' de ' . date('Y', $f);
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Colillas de pago<?= $periodo ? ' · ' . colilla_h($periodo['label']) : '' ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #696cff;
      --primary-soft: #e7e7ff;
      --text: #566a7f;
      --heading: #384551;
      --muted: #a1acb8;
      --border: #e4e6e8;
      --bg: #f5f5f9;
    }
    * { box-sizing: border-box; }
    body { margin: 0; background: var(--bg); color: var(--text); font: 12px/1.45 "Public Sans", -apple-system, "Segoe UI", sans-serif; }
    .toolbar { position: sticky; top: 0; z-index: 5; background: #fff; border-bottom: 1px solid var(--border);
               padding: 10px 16px; display: flex; gap: 8px; align-items: center; justify-content: space-between; flex-wrap: wrap; }
    .toolbar strong { color: var(--heading); font-size: 14px; }
    .btn { border: 1px solid var(--primary); background: var(--primary); color: #fff; border-radius: 6px; padding: 6px 14px;
           font: 500 12px "Public Sans", sans-serif; cursor: pointer; }
    .btn-outline { background: #fff; color: var(--primary); }
    .wrap { max-width: 820px; margin: 16px auto; padding: 0 16px; }
    .colilla { background: #fff; border-radius: 8px; box-shadow: 0 2px 6px rgba(67, 89, 113, .12); padding: 20px 22px;
               margin-bottom: 18px; page-break-inside: avoid; break-inside: avoid; }
    .head { display: flex; justify-content: space-between; gap: 16px; border-bottom: 2px solid var(--primary); padding-bottom: 10px; }
    .empresa { font-size: 15px; font-weight: 700; color: var(--heading); }
    .doc { text-align: right; }
    .doc .titulo { font-weight: 700; color: var(--primary); font-size: 13px; text-transform: uppercase; letter-spacing: .3px; }
    .doc .num { font-size: 13px; font-weight: 600; color: var(--heading); }
    .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px 14px; padding: 12px 0; border-bottom: 1px solid var(--border); }
    .grid .lbl { font-size: 10px; text-transform: uppercase; color: var(--muted); letter-spacing: .3px; }
    .grid .val { color: var(--heading); font-weight: 500; word-break: break-word; }
    .span2 { grid-column: span 2; }
    .tablas { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 12px; }
    table { width: 100%; border-collapse: collapse; }
    thead th { background: var(--primary-soft); color: var(--primary); font-weight: 600; font-size: 11px; text-transform: uppercase;
               padding: 6px 8px; text-align: left; }
    td { padding: 5px 8px; border-bottom: 1px solid var(--border); vertical-align: top; }
    .r { text-align: right; white-space: nowrap; }
    tfoot td { font-weight: 700; color: var(--heading); border-bottom: 0; border-top: 1px solid var(--heading); }
    .neto { margin-top: 12px; display: flex; justify-content: space-between; align-items: center; background: var(--primary);
            color: #fff; border-radius: 6px; padding: 10px 14px; }
    .neto .monto { font-size: 18px; font-weight: 700; }
    .letras { font-size: 10px; opacity: .9; }
    .pie { display: flex; justify-content: space-between; gap: 16px; margin-top: 14px; font-size: 10px; color: var(--muted); }
    .firma { min-width: 220px; border-top: 1px solid var(--text); text-align: center; padding-top: 4px; margin-top: 26px; color: var(--text); }
    .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 600; }
    .b-ok { background: #e8fadf; color: #56ca00; }
    .b-pend { background: #fff2d6; color: #b58200; }
    .vacio { background: #fff; border-radius: 8px; padding: 40px; text-align: center; }
    @media (max-width: 640px) {
      .grid { grid-template-columns: 1fr 1fr; }
      .tablas { grid-template-columns: 1fr; }
      .head { flex-direction: column; }
      .doc { text-align: left; }
    }
    @media print {
      @page { size: letter; margin: 10mm; }
      body { background: #fff; }
      .toolbar { display: none; }
      .wrap { max-width: none; margin: 0; padding: 0; }
      .colilla { box-shadow: none; border: 1px solid var(--border); margin-bottom: 8mm; }
      thead th, .neto { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
  </style>
</head>
<body>
  <div class="toolbar">
    <strong>Colillas de pago · <?= count($nominas) ?> empleado<?= count($nominas) === 1 ? '' : 's' ?></strong>
    <div style="display:flex;gap:8px">
      <button class="btn btn-outline" onclick="window.close()">Cerrar</button>
      <button class="btn" onclick="window.print()">Imprimir / Guardar PDF</button>
    </div>
  </div>

  <div class="wrap">
    <?php if (!$nominas): ?>
      <div class="vacio">
        <strong>No hay nóminas contabilizadas para generar colillas.</strong><br>
        Las colillas se habilitan cuando la nómina queda en estado CONTABILIZADO o EMITIDO.
      </div>
    <?php endif; ?>

    <?php foreach ($nominas as $n):
      $dets = $detallesPorNomina[intval($n['id'])] ?? array();
      $dev = array_values(array_filter($dets, function ($d) { return strtoupper($d['tipo']) === 'DEVENGADO'; }));
      $ded = array_values(array_filter($dets, function ($d) { return strtoupper($d['tipo']) === 'DEDUCCION'; }));
      $totDev = array_sum(array_column($dev, 'total'));
      $totDed = array_sum(array_column($ded, 'total'));
      $neto = $totDev - $totDed;
      $dias = 0;
      foreach ($dev as $d) {
        if ($d['concepto'] === 'salario_basico') {
          $dias = (float)$d['cantidad'];
        }
      }
      $nombre = nomina_nombre($n, 'trabajador_');
      $cuenta = trim((string)$n['numero_cuenta']);
      $cuentaMask = $cuenta !== '' ? str_repeat('•', max(0, strlen($cuenta) - 4)) . substr($cuenta, -4) : '';
      $emitido = strtoupper($n['estado_emision']) === 'EMITIDO';
    ?>
      <section class="colilla">
        <div class="head">
          <div>
            <div class="empresa"><?= colilla_h($empresa['nombre']) ?></div>
            <div>NIT <?= colilla_h($empresa['nit']) ?></div>
            <div><?= colilla_h(trim($empresa['direccion'] . ($empresa['ciudad'] ? ' · ' . $empresa['ciudad'] : ''))) ?></div>
            <?php if ($empresa['telefono']): ?><div>Tel. <?= colilla_h($empresa['telefono']) ?></div><?php endif; ?>
          </div>
          <div class="doc">
            <div class="titulo">Comprobante de pago de nómina</div>
            <div class="num"><?= colilla_h(trim($n['prefijo'] . ' ' . $n['consecutivo'])) ?></div>
            <div>Período: <?= colilla_h($etiquetaPeriodo($n)) ?></div>
            <div>Fecha de pago: <?= colilla_h($n['fecha_pago'] ?: $n['periodo_fecha_fin']) ?></div>
          </div>
        </div>

        <div class="grid">
          <div class="span2"><div class="lbl">Empleado</div><div class="val"><?= colilla_h($nombre) ?></div></div>
          <div><div class="lbl">Documento</div><div class="val"><?= colilla_h(trim($n['trabajador_tipo_documento'] . ' ' . $n['trabajador_documento'])) ?></div></div>
          <div><div class="lbl">Cargo</div><div class="val"><?= colilla_h($n['cargo']) ?></div></div>
          <div><div class="lbl">Salario <?= intval($n['salario_integral']) === 1 ? 'integral' : 'básico' ?></div><div class="val"><?= colilla_money($n['trabajador_salario_basico']) ?></div></div>
          <div><div class="lbl">Días liquidados</div><div class="val"><?= colilla_cant($dias) ?: '—' ?></div></div>
          <div><div class="lbl">EPS</div><div class="val"><?= colilla_h($n['eps_nombre'] ?: '—') ?></div></div>
          <div><div class="lbl">Fondo de pensiones</div><div class="val"><?= colilla_h($n['afp_nombre'] ?: '—') ?></div></div>
          <div class="span2"><div class="lbl">Forma de pago</div>
            <div class="val"><?= colilla_h($metodos[$n['metodo_pago']] ?? 'No definida') ?>
              <?= $cuentaMask ? ' · ' . colilla_h(($n['tipo_cuenta'] === 'AH' ? 'Ahorros' : 'Corriente') . ' ' . $cuentaMask) . ($n['banco'] ? ' · Banco ' . colilla_h($n['banco']) : '') : '' ?></div>
          </div>
          <div class="span2"><div class="lbl">Estado</div>
            <div class="val">
              <?php if ($emitido): ?>
                <span class="badge b-ok">Transmitida a la DIAN</span>
              <?php else: ?>
                <span class="badge b-pend">Contabilizada · pendiente de transmisión DIAN</span>
              <?php endif; ?>
              <?php if (!empty($n['egreso_numero'])): ?>
                <span class="badge b-ok">Pagada · <?= colilla_h(trim(($n['egreso_tipo'] ?? '') . ' ' . $n['egreso_numero'])) ?></span>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="tablas">
          <table>
            <thead><tr><th>Devengados</th><th class="r">Cant.</th><th class="r">Valor</th></tr></thead>
            <tbody>
              <?php foreach ($dev as $d): ?>
                <tr>
                  <td><?= colilla_h(nomina_etiqueta_concepto($d['concepto'], $d['id_concepto'])) ?></td>
                  <td class="r"><?= colilla_cant($d['cantidad']) ?></td>
                  <td class="r"><?= colilla_money($d['total']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot><tr><td colspan="2">Total devengado</td><td class="r"><?= colilla_money($totDev) ?></td></tr></tfoot>
          </table>

          <table>
            <thead><tr><th>Deducciones</th><th class="r">%</th><th class="r">Valor</th></tr></thead>
            <tbody>
              <?php foreach ($ded as $d):
                $esLey = in_array($d['concepto'], array('aporte_salud', 'aporte_pension', 'aporte_fsp_solidaridad', 'aporte_fsp_subsistencia'), true); ?>
                <tr>
                  <td><?= colilla_h(nomina_etiqueta_concepto($d['concepto'], $d['id_concepto'])) ?></td>
                  <td class="r"><?= $esLey ? colilla_cant($d['cantidad']) : '' ?></td>
                  <td class="r"><?= colilla_money($d['total']) ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$ded): ?><tr><td colspan="3" style="color:var(--muted)">Sin deducciones</td></tr><?php endif; ?>
            </tbody>
            <tfoot><tr><td colspan="2">Total deducido</td><td class="r"><?= colilla_money($totDed) ?></td></tr></tfoot>
          </table>
        </div>

        <div class="neto">
          <div>
            <div style="font-weight:600">NETO A PAGAR</div>
            <div class="letras"><?= colilla_h(colilla_letras($neto)) ?></div>
          </div>
          <div class="monto"><?= colilla_money($neto) ?></div>
        </div>

        <?php if (trim((string)$n['observaciones']) !== ''): ?>
          <div style="margin-top:10px"><span class="lbl" style="font-size:10px;color:var(--muted);text-transform:uppercase">Observaciones:</span> <?= colilla_h($n['observaciones']) ?></div>
        <?php endif; ?>

        <div class="pie">
          <div>
            <?php if ($emitido && !empty($n['cufe'])): ?>
              <div><strong>CUNE:</strong> <span style="word-break:break-all"><?= colilla_h($n['cufe']) ?></span></div>
            <?php else: ?>
              <div>Este comprobante no reemplaza el documento soporte de pago de nómina electrónica, que se transmite a la DIAN.</div>
            <?php endif; ?>
            <div>Generado el <?= date('Y-m-d H:i') ?> · ISA Software</div>
          </div>
          <div class="firma">Firma del empleado</div>
        </div>
      </section>
    <?php endforeach; ?>
  </div>
  <?php if ($auto && $nominas): ?>
    <script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script>
  <?php endif; ?>
</body>
</html>
