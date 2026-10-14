<?php

/**
 * Incremento salarial de inicio de año (t_nomina_contratos)
 *
 * Reglas:
 *  - Quien devenga el SMLMV del año anterior (jornada completa) pasa al SMLMV del nuevo año.
 *  - Jornada parcial con salario inferior al mínimo: se ajusta en la misma proporción que el SMLMV.
 *  - Los demás salarios: porcentaje indicado por la empresa (o sin cambio si se elige "solo mínimos").
 *    Ningún salario de jornada completa puede quedar por debajo del nuevo SMLMV.
 *  - Salario integral: mínimo 13 SMLMV del nuevo año (art. 132 CST).
 *  - Auxilio de transporte del contrato: si era el legal del año anterior, pasa al legal del nuevo año;
 *    vacío (= valor legal) o 0 (no recibe) no se tocan.
 *  - Cada cambio queda en t_nomina_contratos_salarios_hist y puede revertirse mientras no existan
 *    nóminas contabilizadas o emitidas desde la fecha efectiva.
 *
 * Los valores siempre se recalculan en el servidor: el navegador solo envía qué contratos aplicar.
 */

require_once(__DIR__ . '/nominaCalculos.php');

const INC_TABLA_HIST = 't_nomina_contratos_salarios_hist';

function inc_redondear($valor, $multiplo)
{
  $multiplo = max(1, intval($multiplo));
  return ceil(round($valor, 2) / $multiplo) * $multiplo;
}

/**
 * Calcula la propuesta de incremento para los contratos activos.
 * @return array ['error' => string|null, 'filas' => [...], 'resumen' => [...]]
 */
function inc_calcular($anio, $modo, $porcentaje, $redondeo, $fechaEfectiva, array $soloIds = array())
{
  $anio = intval($anio);
  $pNuevo = CalculosNomina::cargarParametros($anio);
  $faltNuevo = CalculosNomina::parametrosFaltantes($anio);
  $faltAnt = CalculosNomina::parametrosFaltantes($anio - 1);
  if (in_array('Salario mínimo (smlv)', $faltNuevo, true) || in_array('Auxilio de transporte (aux_transporte)', $faltNuevo, true)) {
    return array('error' => "Registre primero el SMLMV y el auxilio de transporte de $anio en Parámetros de nómina");
  }
  if (in_array('Salario mínimo (smlv)', $faltAnt, true)) {
    return array('error' => 'No hay SMLMV registrado para ' . ($anio - 1) . ': no se puede identificar quién devengaba el mínimo');
  }
  $pAnt = CalculosNomina::cargarParametros($anio - 1);
  $smlvNuevo = (float)($pNuevo['smlv'] ?? 0);
  $smlvAnt = (float)($pAnt['smlv'] ?? 0);
  $auxNuevo = (float)($pNuevo['aux_transporte'] ?? 0);
  $auxAnt = (float)($pAnt['aux_transporte'] ?? 0);
  if ($smlvNuevo < $smlvAnt) {
    return array('error' => "El SMLMV de $anio es menor que el de " . ($anio - 1) . '; revise los parámetros');
  }

  $filtro = $soloIds ? ' AND c.id IN (' . implode(',', array_map('intval', $soloIds)) . ')' : '';
  $fechaEsc = nomina_esc($fechaEfectiva);
  $contratos = nomina_select("SELECT c.id, c.empleado_documento, c.cargo, c.jornada, c.salario_basico, c.aux_transporte,
                                     c.salario_integral,
                                     TRIM(CONCAT_WS(' ', c.primer_nombre, NULLIF(c.segundo_nombre,''), c.primer_apellido, NULLIF(c.segundo_apellido,''))) AS empleado
                                FROM t_nomina_contratos c
                               WHERE c.estado = 'ACTIVO'
                                 AND (c.fecha_fin IS NULL OR c.fecha_fin >= '$fechaEsc') $filtro
                               ORDER BY c.primer_apellido, c.primer_nombre");

  $aplicados = array();
  if (inc_hist_existe()) {
    foreach (nomina_select("SELECT id_contrato FROM " . INC_TABLA_HIST . " WHERE anio = $anio AND revertido = 0") as $h) {
      $aplicados[intval($h['id_contrato'])] = true;
    }
  }

  $filas = array();
  $resumen = array('contratos' => 0, 'minimos' => 0, 'porcentaje' => 0, 'integrales' => 0, 'ya_aplicados' => 0,
                   'nomina_actual' => 0, 'nomina_nueva' => 0);
  $factor = 1 + floatval($porcentaje) / 100;
  $ratioMinimo = $smlvAnt > 0 ? $smlvNuevo / $smlvAnt : 1;

  foreach ($contratos as $c) {
    $id = intval($c['id']);
    $actual = (float)$c['salario_basico'];
    $integral = intval($c['salario_integral']) === 1;
    $parcial = strtoupper((string)$c['jornada']) === 'PARCIAL';

    if (isset($aplicados[$id])) {
      $resumen['ya_aplicados']++;
      continue;
    }

    if ($integral) {
      $nuevo = $modo === 'PORCENTAJE' ? inc_redondear($actual * $factor, $redondeo) : $actual;
      $minIntegral = $smlvNuevo * 13;
      $motivo = $nuevo < $minIntegral ? 'Ajuste al mínimo integral (13 SMLMV)' : ($modo === 'PORCENTAJE' ? 'Incremento ' . floatval($porcentaje) . ' %' : 'Sin cambio');
      $nuevo = max($nuevo, $minIntegral);
      $tipo = 'INTEGRAL';
    } elseif (!$parcial && $actual <= $smlvAnt + 1) {
      $nuevo = $smlvNuevo;
      $motivo = 'Salario mínimo ' . ($anio - 1) . ' → ' . $anio;
      $tipo = 'MINIMO';
    } elseif ($parcial && $actual < $smlvAnt) {
      $nuevo = inc_redondear($actual * $ratioMinimo, $redondeo);
      $motivo = 'Jornada parcial: mismo incremento del SMLMV';
      $tipo = 'MINIMO';
    } else {
      $nuevo = $modo === 'PORCENTAJE' ? inc_redondear($actual * $factor, $redondeo) : $actual;
      $motivo = $modo === 'PORCENTAJE' ? 'Incremento ' . floatval($porcentaje) . ' %' : 'Sin cambio';
      $tipo = 'PORCENTAJE';
      if (!$parcial && $nuevo < $smlvNuevo) {
        $nuevo = $smlvNuevo;
        $motivo = 'Ajuste al nuevo SMLMV';
        $tipo = 'MINIMO';
      }
    }

    // Auxilio de transporte fijado en el contrato
    $auxActual = $c['aux_transporte'];
    $auxNuevoContrato = $auxActual;
    if ($auxActual !== null && $auxActual !== '' && (float)$auxActual > 0 && abs((float)$auxActual - $auxAnt) <= 1) {
      $auxNuevoContrato = $auxNuevo;
    }

    if (round($nuevo, 2) == round($actual, 2) && $auxNuevoContrato == $auxActual) {
      continue;
    }

    $resumen['contratos']++;
    $resumen[$tipo === 'MINIMO' ? 'minimos' : ($tipo === 'INTEGRAL' ? 'integrales' : 'porcentaje')]++;
    $resumen['nomina_actual'] += $actual;
    $resumen['nomina_nueva'] += $nuevo;

    $filas[] = array(
      'id' => $id,
      'empleado' => $c['empleado'],
      'documento' => $c['empleado_documento'],
      'cargo' => $c['cargo'],
      'jornada' => $c['jornada'],
      'tipo' => $tipo,
      'motivo' => $motivo,
      'salario_actual' => round($actual, 2),
      'salario_nuevo' => round($nuevo, 2),
      'variacion' => $actual > 0 ? round(($nuevo / $actual - 1) * 100, 2) : 0,
      'aux_actual' => $auxActual === null || $auxActual === '' ? null : (float)$auxActual,
      'aux_nuevo' => $auxNuevoContrato === null || $auxNuevoContrato === '' ? null : (float)$auxNuevoContrato,
    );
  }

  // Aviso: nóminas en borrador de períodos anteriores a la fecha efectiva se recalcularían con el salario nuevo
  $pendientes = nomina_select("SELECT COUNT(*) AS n FROM t_nomina
                                WHERE estado_emision = 'BORRADOR' AND periodo_fecha_inicio < '$fechaEsc'");
  $cerradas = nomina_select("SELECT COUNT(*) AS n FROM t_nomina
                              WHERE estado_emision IN ('CONTABILIZADO','EMITIDO') AND periodo_fecha_inicio >= '$fechaEsc'");

  $resumen['nomina_actual'] = round($resumen['nomina_actual'], 2);
  $resumen['nomina_nueva'] = round($resumen['nomina_nueva'], 2);
  return array(
    'error' => null,
    'filas' => $filas,
    'resumen' => $resumen,
    'parametros' => array('smlv_anterior' => $smlvAnt, 'smlv_nuevo' => $smlvNuevo, 'aux_anterior' => $auxAnt, 'aux_nuevo' => $auxNuevo,
                          'incremento_smlv' => $smlvAnt > 0 ? round(($smlvNuevo / $smlvAnt - 1) * 100, 2) : 0),
    'avisos' => array_values(array_filter(array(
      intval($pendientes[0]['n'] ?? 0) > 0
        ? 'Hay ' . $pendientes[0]['n'] . ' nómina(s) en borrador de períodos anteriores a la fecha efectiva: contabilícelas antes de aplicar, o al recalcularlas tomarán el salario nuevo.'
        : null,
      intval($cerradas[0]['n'] ?? 0) > 0
        ? 'Ya hay ' . $cerradas[0]['n'] . ' nómina(s) contabilizada(s) desde la fecha efectiva: el incremento no las modifica; registre el retroactivo como novedad.'
        : null,
    ))),
  );
}

function inc_hist_existe()
{
  static $existe = null;
  if ($existe === null) {
    $existe = (bool)nomina_select("SHOW TABLES LIKE '" . INC_TABLA_HIST . "'");
  }
  return $existe;
}

function inc_datos_post()
{
  $anio = intval($_POST['anio'] ?? (date('Y') + 1));
  $fecha = trim($_POST['fecha_efectiva'] ?? '') ?: "$anio-01-01";
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || intval(substr($fecha, 0, 4)) !== $anio) {
    responder(true, 'La fecha efectiva debe estar dentro del año del incremento');
  }
  $modo = ($_POST['modo'] ?? 'PORCENTAJE') === 'SOLO_MINIMO' ? 'SOLO_MINIMO' : 'PORCENTAJE';
  $pct = round(floatval(str_replace(',', '.', $_POST['porcentaje'] ?? 0)), 4);
  if ($modo === 'PORCENTAJE' && ($pct < 0 || $pct > 100)) {
    responder(true, 'El porcentaje de incremento debe estar entre 0 y 100');
  }
  $redondeo = in_array(intval($_POST['redondeo'] ?? 1), array(1, 100, 1000), true) ? intval($_POST['redondeo']) : 1;
  return array($anio, $modo, $pct, $redondeo, $fecha);
}

// Endpoints solo cuando el archivo se invoca directamente
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') !== realpath(__FILE__)) {
  return;
}

$op = $_POST['op'] ?? '';

switch ($op) {
  case 'sugerencia':
    // Valores sugeridos para el formulario: año, incremento del SMLMV registrado
    $anio = intval($_POST['anio'] ?? (date('Y') + (date('n') >= 11 ? 1 : 0)));
    $pN = CalculosNomina::cargarParametros($anio);
    $pA = CalculosNomina::cargarParametros($anio - 1);
    $tieneNuevo = !in_array('Salario mínimo (smlv)', CalculosNomina::parametrosFaltantes($anio), true);
    $tieneAnterior = !in_array('Salario mínimo (smlv)', CalculosNomina::parametrosFaltantes($anio - 1), true);
    $smlvNuevo = $tieneNuevo ? (float)($pN['smlv'] ?? 0) : null;
    $smlvAnterior = $tieneAnterior ? (float)($pA['smlv'] ?? 0) : null;
    responder(false, 'Success', array(
      'anio' => $anio,
      'smlv_nuevo' => $smlvNuevo,
      'smlv_anterior' => $smlvAnterior,
      'incremento_smlv' => ($smlvNuevo && $smlvAnterior) ? round(($smlvNuevo / $smlvAnterior - 1) * 100, 2) : null,
      'historial' => inc_hist_existe() ? nomina_select("SELECT anio, COUNT(*) AS contratos, MIN(fecha_efectiva) AS fecha_efectiva,
                                                               SUM(revertido = 1) AS revertidos, MAX(fecha_registro) AS fecha_registro
                                                          FROM " . INC_TABLA_HIST . " GROUP BY anio ORDER BY anio DESC LIMIT 5") : array(),
      'historial_disponible' => inc_hist_existe(),
    ));
    break;

  case 'previsualizar':
    list($anio, $modo, $pct, $redondeo, $fecha) = inc_datos_post();
    $r = inc_calcular($anio, $modo, $pct, $redondeo, $fecha);
    if ($r['error']) {
      responder(true, $r['error']);
    }
    responder(false, 'Success', $r);
    break;

  case 'aplicar':
    if (!inc_hist_existe()) {
      responder(true, 'Ejecute primero el script sql/nomina_incremento_salarial.sql (historial de incrementos)');
    }
    list($anio, $modo, $pct, $redondeo, $fecha) = inc_datos_post();
    $ids = array_filter(array_map('intval', explode(',', (string)($_POST['ids'] ?? ''))));
    if (!$ids) {
      responder(true, 'Seleccione al menos un contrato');
    }
    $r = inc_calcular($anio, $modo, $pct, $redondeo, $fecha, $ids);   // se recalcula en el servidor
    if ($r['error']) {
      responder(true, $r['error']);
    }
    if (!$r['filas']) {
      responder(true, 'No hay cambios por aplicar en los contratos seleccionados');
    }

    $conn = nomina_conn();
    $usuario = intval($_SESSION['Ses_id'] ?? 0);
    $fechaEsc = nomina_esc($fecha);
    $modoEsc = nomina_esc($modo);
    $conn->begin_transaction();
    try {
      foreach ($r['filas'] as $f) {
        $aux = $f['aux_nuevo'] === null ? 'NULL' : $f['aux_nuevo'];
        $auxAnt = $f['aux_actual'] === null ? 'NULL' : $f['aux_actual'];
        // Solo si el salario no cambió desde la vista previa (evita pisar una edición concurrente)
        if (!$conn->query("UPDATE t_nomina_contratos
                              SET salario_basico = {$f['salario_nuevo']}, aux_transporte = $aux
                            WHERE id = {$f['id']} AND ROUND(salario_basico, 2) = {$f['salario_actual']}")
          || $conn->affected_rows !== 1) {
          throw new Exception("El contrato de {$f['empleado']} cambió durante el proceso; vuelva a previsualizar");
        }
        if (!$conn->query("INSERT INTO " . INC_TABLA_HIST . "
                             (id_contrato, anio, fecha_efectiva, modo, porcentaje, salario_anterior, salario_nuevo,
                              aux_anterior, aux_nuevo, motivo, usuario, fecha_registro, revertido)
                           VALUES ({$f['id']}, $anio, '$fechaEsc', '$modoEsc', $pct, {$f['salario_actual']}, {$f['salario_nuevo']},
                                   $auxAnt, $aux, '" . nomina_esc($f['motivo']) . "', $usuario, NOW(), 0)")) {
          throw new Exception($conn->error);
        }
      }
      $conn->commit();
      responder(false, 'Se actualizaron ' . count($r['filas']) . " contratos con los salarios de $anio", array('aplicados' => count($r['filas'])));
    } catch (Exception $e) {
      $conn->rollback();
      responder(true, 'No se aplicó ningún cambio: ' . $e->getMessage());
    }
    break;

  case 'revertir':
    if (!inc_hist_existe()) {
      responder(true, 'No existe historial de incrementos');
    }
    $anio = intval($_POST['anio'] ?? 0);
    $hist = nomina_select("SELECT h.*, c.salario_basico AS salario_hoy
                             FROM " . INC_TABLA_HIST . " h
                             INNER JOIN t_nomina_contratos c ON c.id = h.id_contrato
                            WHERE h.anio = $anio AND h.revertido = 0");
    if (!$hist) {
      responder(true, "No hay incrementos de $anio para revertir");
    }
    $fechaEf = nomina_esc($hist[0]['fecha_efectiva']);
    $ids = implode(',', array_map(function ($h) { return intval($h['id_contrato']); }, $hist));
    $cerradas = nomina_select("SELECT COUNT(*) AS n FROM t_nomina
                                WHERE id_contrato IN ($ids) AND periodo_fecha_inicio >= '$fechaEf'
                                  AND estado_emision IN ('CONTABILIZADO','EMITIDO')");
    if (intval($cerradas[0]['n'] ?? 0) > 0) {
      responder(true, 'No se puede revertir: ya hay nóminas contabilizadas o emitidas con los salarios nuevos');
    }
    $conn = nomina_conn();
    $conn->begin_transaction();
    $revertidos = 0;
    $omitidos = 0;
    foreach ($hist as $h) {
      // Si el salario fue editado a mano después del incremento, no se toca
      if (round((float)$h['salario_hoy'], 2) != round((float)$h['salario_nuevo'], 2)) {
        $omitidos++;
        continue;
      }
      $aux = $h['aux_anterior'] === null ? 'NULL' : (float)$h['aux_anterior'];
      $conn->query("UPDATE t_nomina_contratos SET salario_basico = " . (float)$h['salario_anterior'] . ", aux_transporte = $aux
                     WHERE id = " . intval($h['id_contrato']));
      $conn->query("UPDATE " . INC_TABLA_HIST . " SET revertido = 1 WHERE id = " . intval($h['id']));
      $revertidos++;
    }
    $conn->commit();
    responder(false, "Se revirtieron $revertidos contratos" . ($omitidos ? " ($omitidos con cambios posteriores no se tocaron)" : ''));
    break;

  default:
    responder(true, 'Operación no válida');
}