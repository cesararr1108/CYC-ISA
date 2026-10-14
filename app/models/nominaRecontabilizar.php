<?php

/**
 * Recontabilización de documentos de nómina
 *
 * Los tipos de documento con nomina = 'S' (sw 14 causación y sw 9 egreso de pago) graban su
 * asiento desde el módulo de nómina. Los procedimientos generales add_movimiento_nd_proveedor (sw 14)
 * y add_movimiento_egreso (sw 9) no conocen esa estructura y se bloquean o dejan el documento
 * sin contabilidad. Este archivo regenera esos asientos con la misma lógica de nómina.
 *
 * Uso desde Consulta de documentos (opción "Recalcular contabilización"):
 *
 *   require_once('nominaRecontabilizar.php');
 *   $r = nomina_recontabilizar_documento($idTransaccion, $cuentaBanco);   // cuenta opcional (egresos)
 *   if ($r['es_nomina']) {               // documento de nómina: no se llama al procedimiento
 *     echo json_encode(array('error' => !$r['ok'], 'message' => $r['message'], 'data' => $r));
 *     exit;
 *   }
 *   // ...flujo normal con el procedimiento almacenado
 *
 * También responde por POST: op = 'recontabilizar' | 'es_documento_nomina', id_transaccion.
 */

require_once(__DIR__ . '/nominaContabilizacion.php');

/** Datos del documento y de su tipo; null si no existe */
function nomina_doc_transaccion($idTransaccion)
{
  $row = nomina_select("SELECT t.id, t.id_tipo_documento, t.numero, t.id_tercero, t.fecha, t.fecha_hora, t.valor_total,
                               td.tipo, td.sw, IFNULL(td.nomina, 'N') AS nomina
                          FROM t_transacciones t
                         INNER JOIN t_tipos_documentos td ON td.id = t.id_tipo_documento
                         WHERE t.id = " . intval($idTransaccion) . " LIMIT 1");
  return $row ? $row[0] : null;
}

/** ¿El documento lo contabiliza el módulo de nómina? */
function nomina_es_documento_nomina($idTransaccion)
{
  $doc = nomina_doc_transaccion($idTransaccion);
  return $doc && strtoupper($doc['nomina']) === 'S' && in_array(intval($doc['sw']), array(9, 14), true);
}

/** Reemplaza los movimientos del documento (todo o nada) */
function nomina_reemplazar_movimientos(array $doc, array $movs)
{
  $sd = 0;
  $sc = 0;
  foreach ($movs as $m) {
    $sd += $m['debito'];
    $sc += $m['credito'];
  }
  if (!$movs || abs(round($sd - $sc, 2)) > 0.009) {
    return 'El asiento regenerado no cuadra (débitos ' . nomina_money($sd) . ' / créditos ' . nomina_money($sc) . '); no se modificó nada';
  }
  $conn = nomina_conn();
  $id = intval($doc['id']);
  $tipo = nomina_esc($doc['tipo']);
  $numero = nomina_esc($doc['numero']);
  $fechaHora = nomina_esc($doc['fecha_hora'] ?: ($doc['fecha'] . ' 00:00:00'));
  $vals = array();
  foreach ($movs as $m) {
    $vals[] = sprintf("(%d, '%s', '%s', '%s', '%s', %.2f, %.2f, '%s', 0, '%s')",
      $id, $tipo, $numero, nomina_esc($m['cuenta']), nomina_esc($m['nit']), $m['debito'], $m['credito'],
      $fechaHora, nomina_esc(mb_substr($m['detalle'], 0, 250)));
  }
  $conn->begin_transaction();
  try {
    if (!$conn->query("DELETE FROM t_movimiento_contable WHERE id_transaccion = $id")) {
      throw new Exception($conn->error);
    }
    if (!$conn->query("INSERT INTO t_movimiento_contable
                         (id_transaccion, tipo, numero, cuenta, nit, debito, credito, fecha_hora, ceco, detalle)
                       VALUES " . implode(', ', $vals))) {
      throw new Exception($conn->error);
    }
    $conn->commit();
    return '';
  } catch (Exception $e) {
    $conn->rollback();
    return 'Error al grabar el asiento: ' . $e->getMessage();
  }
}

/** Causación de nómina (sw 14): se reconstruye desde t_nomina / t_nomina_detalle */
function nomina_recontabilizar_causacion(array $doc)
{
  $numero = nomina_esc($doc['numero']);
  $n = nomina_select("SELECT n.id AS id_nomina, n.*, COALESCE(NULLIF(n.id_perfil_contable,0), c.id_perfil_contable) AS id_perfil
                        FROM t_nomina n LEFT JOIN t_nomina_contratos c ON c.id = n.id_contrato
                       WHERE n.id_tipo_documento = " . intval($doc['id_tipo_documento']) . " AND n.consecutivo = '$numero'
                       LIMIT 1");
  if (!$n) {
    return 'No se encontró la nómina asociada a este documento';
  }
  $nom = $n[0];
  $contrato = nomina_select("SELECT * FROM t_nomina_contratos WHERE id = " . intval($nom['id_contrato']) . " LIMIT 1");
  $perfil = nomina_select("SELECT * FROM t_nomina_perfiles_contables WHERE id = " . intval($nom['id_perfil']) . " LIMIT 1");
  if (!$contrato || !$perfil) {
    return 'La nómina no tiene contrato o perfil contable válido';
  }
  $periodo = nomina_resolver_periodo(nomina_frecuencia_empresa() === 1 ? $nom['periodo_fecha_inicio'] : $nom['periodo']);
  if (!$periodo) {
    return 'No se pudo determinar el período de la nómina';
  }
  $faltantes = CalculosNomina::parametrosFaltantes($periodo['anio']);
  if ($faltantes) {
    return 'Faltan parámetros de nómina para ' . $periodo['anio'] . ': ' . implode(', ', $faltantes);
  }
  $dets = nomina_select("SELECT id_concepto, concepto, tipo, cantidad, total
                           FROM t_nomina_detalle WHERE id_nomina = " . intval($nom['id_nomina']) . " AND total <> 0");
  list($entidadNit, $entidadTipo) = nomina_mapas_entidades();
  $avisos = array();
  $r = nomina_asiento_causacion($nom, $contrato[0], $perfil[0], $dets, $periodo, $entidadNit, $entidadTipo, $avisos);
  if ($r['error']) {
    return $r['error'];
  }
  if (abs($r['asiento']['neto'] - (float)$doc['valor_total']) > 0.01) {
    return 'El neto de la nómina (' . nomina_money($r['asiento']['neto']) . ') no coincide con el valor del documento ('
      . nomina_money($doc['valor_total']) . '); revise el detalle antes de recontabilizar';
  }
  return nomina_reemplazar_movimientos($doc, $r['asiento']['movs']);
}

/** Egreso de nómina (sw 9): se reconstruye desde sus cruces contra las causaciones */
function nomina_recontabilizar_egreso(array $doc, $cuentaElegida = '', &$requiereCuenta = false)
{
  $id = intval($doc['id']);
  $cruces = nomina_select("SELECT c.tipo_aplica, c.numero_aplica, c.valor, n.trabajador_documento,
                                  COALESCE(NULLIF(n.id_perfil_contable,0), ct.id_perfil_contable) AS id_perfil
                             FROM t_transacciones_cruce c
                             LEFT JOIN t_nomina n ON n.prefijo = c.tipo_aplica AND n.consecutivo = c.numero_aplica
                             LEFT JOIN t_nomina_contratos ct ON ct.id = n.id_contrato
                            WHERE c.id_transaccion = $id");
  if (!$cruces) {
    return 'El egreso no tiene nóminas cruzadas; no se puede reconstruir su contabilidad';
  }
  $perfiles = array();
  foreach (nomina_select("SELECT id, cuenta_salario_por_pagar FROM t_nomina_perfiles_contables") as $p) {
    $perfiles[intval($p['id'])] = trim((string)$p['cuenta_salario_por_pagar']);
  }

  // Cuenta de caja/banco, en orden de prioridad:
  //  1) la que elige el usuario en Consulta de documentos
  //  2) la guardada al generar el egreso (t_nomina.egreso_cuenta)
  //  3) la de t_transacciones_che (egresos con cheque/banco)
  //  4) el crédito actual del asiento que no sea "salarios por pagar"
  $cuentasPagar = array_filter(array_values($perfiles));
  $cuentaBanco = trim((string)$cuentaElegida);
  if ($cuentaBanco !== '') {
    $existe = nomina_select("SELECT cuenta FROM t_cuentas_contables WHERE cuenta = '" . nomina_esc($cuentaBanco) . "' LIMIT 1");
    if (!$existe) {
      return "La cuenta $cuentaBanco no existe en el plan de cuentas";
    }
  }
  if ($cuentaBanco === '' && nomina_tiene_columna('t_nomina', 'egreso_cuenta')) {
    $r = nomina_select("SELECT egreso_cuenta FROM t_nomina WHERE id_transaccion_egreso = $id AND IFNULL(egreso_cuenta,'') <> '' LIMIT 1");
    $cuentaBanco = trim((string)($r[0]['egreso_cuenta'] ?? ''));
  }
  if ($cuentaBanco === '') {
    $r = nomina_select("SELECT cuenta FROM t_transacciones_che WHERE id_transaccion = $id AND IFNULL(cuenta,'') <> '' ORDER BY valor DESC LIMIT 1");
    $cuentaBanco = trim((string)($r[0]['cuenta'] ?? ''));
  }
  if ($cuentaBanco === '') {
    $actual = nomina_select("SELECT cuenta, SUM(credito) AS c FROM t_movimiento_contable
                              WHERE id_transaccion = $id AND credito > 0 GROUP BY cuenta ORDER BY c DESC");
    foreach ($actual as $a) {
      if (!in_array(trim($a['cuenta']), $cuentasPagar, true)) {
        $cuentaBanco = trim($a['cuenta']);
        break;
      }
    }
  }
  if ($cuentaBanco === '') {
    $requiereCuenta = true;
    return 'No se encontró la cuenta de caja o banco de este egreso. Selecciónela para regenerar la contabilización.';
  }

  $movs = array();
  $total = 0;
  foreach ($cruces as $c) {
    $cuenta = $perfiles[intval($c['id_perfil'])] ?? '';
    if ($cuenta === '' || empty($c['trabajador_documento'])) {
      return "No se encontró la nómina o la cuenta de salarios por pagar de {$c['tipo_aplica']} {$c['numero_aplica']}";
    }
    $valor = round((float)$c['valor'], 2);
    $total += $valor;
    $movs[] = array('cuenta' => $cuenta, 'nit' => trim($c['trabajador_documento']), 'debito' => $valor, 'credito' => 0,
                    'detalle' => "Pago nómina {$c['tipo_aplica']} {$c['numero_aplica']} - CC " . trim($c['trabajador_documento']));
  }
  // Crédito al banco: con el NIT del empleado si es un egreso individual; con el del tercero del egreso si es por el total
  if (count($cruces) === 1) {
    $nitBanco = trim($cruces[0]['trabajador_documento']);
  } else {
    $t = nomina_select("SELECT nit FROM t_terceros WHERE id = " . intval($doc['id_tercero']) . " LIMIT 1");
    $nitBanco = trim($t[0]['nit'] ?? $cruces[0]['trabajador_documento']);
  }
  $movs[] = array('cuenta' => $cuentaBanco, 'nit' => $nitBanco, 'debito' => 0, 'credito' => round($total, 2),
                  'detalle' => 'Pago nómina ' . $doc['tipo'] . ' ' . $doc['numero']);

  if (abs($total - (float)$doc['valor_total']) > 0.01) {
    return 'La suma de los cruces (' . nomina_money($total) . ') no coincide con el valor del egreso ('
      . nomina_money($doc['valor_total']) . '); no se modificó nada';
  }
  $error = nomina_reemplazar_movimientos($doc, $movs);
  // Se recuerda la cuenta para futuras regeneraciones
  if ($error === '' && nomina_tiene_columna('t_nomina', 'egreso_cuenta')) {
    Query("UPDATE t_nomina SET egreso_cuenta = '" . nomina_esc($cuentaBanco) . "' WHERE id_transaccion_egreso = $id");
  }
  return $error;
}

/**
 * Punto de entrada.
 * @return array ['es_nomina' => bool, 'ok' => bool, 'message' => string]
 */
function nomina_recontabilizar_documento($idTransaccion, $cuentaBanco = '')
{
  $doc = nomina_doc_transaccion($idTransaccion);
  if (!$doc || strtoupper($doc['nomina']) !== 'S' || !in_array(intval($doc['sw']), array(9, 14), true)) {
    return array('es_nomina' => false, 'ok' => false, 'message' => '');
  }
  $requiereCuenta = false;
  $error = intval($doc['sw']) === 14
    ? nomina_recontabilizar_causacion($doc)
    : nomina_recontabilizar_egreso($doc, $cuentaBanco, $requiereCuenta);
  return array(
    'es_nomina' => true,
    'ok' => $error === '',
    'requiere_cuenta' => $requiereCuenta,
    'message' => $error === ''
      ? "Contabilización de {$doc['tipo']} {$doc['numero']} regenerada desde el módulo de nómina"
      : $error,
  );
}

/* Endpoints directos */
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
  $idT = intval($_POST['id_transaccion'] ?? 0);
  switch ($_POST['op'] ?? '') {
    case 'es_documento_nomina':
      responder(false, 'Success', array('es_nomina' => nomina_es_documento_nomina($idT)));
      break;
    case 'recontabilizar':
      $r = nomina_recontabilizar_documento($idT, trim($_POST['cuenta_banco'] ?? ''));
      if (!$r['es_nomina']) {
        responder(true, 'El documento no es de nómina; use la recontabilización general');
      }
      responder(!$r['ok'], $r['message'], $r);
      break;
    default:
      responder(true, 'Operación no válida');
  }
}