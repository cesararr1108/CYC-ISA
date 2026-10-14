<?php

/**
 * Novedades de nómina
 *
 * PERÍODOS: se generan según id_frecuencia_pago de t_empresa (1 = quincenal, 2 = mensual).
 * La novedad guarda el mes (YYYY-MM) en `periodo` y su fecha en `fecha_novedad`;
 * en nómina quincenal la fecha determina en qué quincena se liquida.
 */

require_once(__DIR__ . '/nominaCalculos.php');

/** Estado de la nómina del contrato para la fecha/periodo de la novedad */
function nominaCerradaParaNovedad($idContrato, $periodo, $fechaNovedad)
{
  $per = nomina_resolver_periodo(nomina_frecuencia_empresa() === 1 && $fechaNovedad
    ? substr($fechaNovedad, 0, 8) . (intval(substr($fechaNovedad, 8, 2)) >= 16 ? '16' : '01')
    : $periodo);
  if (!$per) {
    return null;
  }
  $row = nomina_select("SELECT estado_emision FROM t_nomina
                         WHERE id_contrato = " . intval($idContrato) . " AND periodo_fecha_inicio = '{$per['fecha_inicio']}'
                           AND estado_emision IN ('CONTABILIZADO','EMITIDO') LIMIT 1");
  return $row ? $row[0]['estado_emision'] : null;
}

/** Valida la novedad con los datos reales del concepto (no confía en lo que envía el navegador) */
function validarNovedad(&$data, $idContrato, $idNovedad = 0)
{
  $idConcepto = intval($data['id_concepto'] ?? 0);
  $concepto = $idConcepto > 0 ? nomina_select("SELECT * FROM t_nomina_conceptos WHERE id = $idConcepto AND estado = 'ACTIVO' LIMIT 1") : array();
  if (!$concepto) {
    return 'Seleccione un concepto válido y activo';
  }
  $concepto = $concepto[0];
  $subtipo = strtoupper(trim((string)$concepto['subtipo']));
  $clave = strtolower(trim((string)$concepto['clave']));

  // Estos conceptos los calcula la liquidación automáticamente; registrarlos como novedad los duplicaría
  if (in_array($subtipo, array('SEGURIDAD_SOCIAL', 'RETENCION'), true)
    || in_array($clave, array('con_salario_ordinario', 'con_auxilio_transporte'), true)) {
    return 'El concepto "' . $concepto['nombre'] . '" se calcula automáticamente al liquidar; no se registra como novedad';
  }
  $data['tipo'] = $concepto['tipo'];
  $data['concepto'] = $concepto['clave'];
  $data['subtipo_concepto'] = $concepto['subtipo'];

  $cantidad = round(floatval(str_replace(',', '.', $data['cantidad'] ?? 1)), 4);
  $valorUnitario = round(floatval(str_replace(',', '.', $data['valor_unitario'] ?? 0)), 2);
  if ($cantidad < 0 || $valorUnitario < 0) {
    return 'Cantidad y valor unitario no pueden ser negativos';
  }
  $data['cantidad'] = $cantidad;
  $data['valor_unitario'] = $valorUnitario;
  $data['total'] = round($cantidad * $valorUnitario, 2);

  // porcentaje_fijo es el RECARGO. En horas extra se paga la hora ordinaria + el recargo
  // (HED 25 % ⇒ 125 %); en recargos (nocturno, dominical) solo el recargo (art. 168 y 179 CST).
  $pct = floatval($concepto['porcentaje_fijo'] ?? 0);
  $factor = $pct > 0 ? ($subtipo === 'EXTRA' ? 1 + $pct / 100 : $pct / 100) : 1;
  $data['porcentaje_aplicado'] = $pct > 0 ? $pct : 0;
  $data['total_porcentaje'] = $pct > 0 ? round($data['total'] * $factor, 2) : null;
  $data['valor_final'] = round($data['total'] * $factor, 2);

  $permiteCero = nomina_flag($concepto, 'afecta_dias') === true && intval($concepto['requiere_valor'] ?? 1) === 0;
  if ($data['valor_final'] <= 0 && !$permiteCero) {
    return 'El valor final de la novedad debe ser mayor a cero';
  }
  if ($permiteCero && $cantidad <= 0) {
    return 'Indique los días de la licencia en la cantidad';
  }

  $per = nomina_resolver_periodo($data['periodo_id'] ?? $data['periodo']);
  $contrato = nomina_select("SELECT salario_basico FROM t_nomina_contratos WHERE id = " . intval($idContrato) . " LIMIT 1");
  $salario = floatval($contrato[0]['salario_basico'] ?? 0);

  // Horas extra: máximo legal semanal (Ley 2101/2021 y art. 22 Ley 50/1990) × semanas del período
  if ($data['tipo'] === 'DEVENGADO' && $subtipo === 'EXTRA' && $per) {
    $max = CalculosNomina::param('max_horas_extra_semana', $per['anio']) * ($per['dias_periodo'] / 7);
    if ($cantidad > ceil($max)) {
      return 'Las horas extra superan el máximo legal del período (' . ceil($max) . ' horas)';
    }
  }

  if ($subtipo === 'INCAPACIDAD') {
    if (trim($data['documento_respaldo'] ?? '') === '') {
      return 'La incapacidad requiere documento de respaldo (certificado médico)';
    }
    $dias = intval($data['dias_incapacidad'] ?? 0);
    if ($dias <= 0 || $dias > 30) {
      return 'Los días de incapacidad deben estar entre 1 y 30';
    }
  }

  if ($subtipo === 'EMBARGO' && trim($data['orden_embargo'] ?? '') === '') {
    return 'El embargo requiere el número de orden judicial';
  }

  // Deducciones voluntarias: no pueden superar el 50 % del salario del mes (arts. 149 y 154 CST),
  // sumando las demás deducciones activas del mismo mes.
  if ($data['tipo'] === 'DEDUCCION' && $salario > 0 && $subtipo !== 'EMBARGO') {
    $otras = nomina_select("SELECT COALESCE(SUM(valor_final),0) AS t FROM t_nomina_novedades
                             WHERE id_contrato = " . intval($idContrato) . " AND periodo = '" . nomina_esc($data['periodo']) . "'
                               AND tipo = 'DEDUCCION' AND IFNULL(estado,'ACTIVO') = 'ACTIVO' AND id <> " . intval($idNovedad));
    $acum = floatval($otras[0]['t'] ?? 0) + $data['valor_final'];
    if ($acum > $salario * 0.5) {
      return 'Las deducciones del mes (' . nomina_money($acum) . ') superan el 50 % del salario (' . nomina_money($salario * 0.5) . ')';
    }
  }
  return '';
}

$op = $_POST['op'] ?? '';

switch ($op) {
  case 'combos_periodos':
    $anio = intval($_POST['anio'] ?? date('Y'));
    responder(false, 'Success', nomina_generar_periodos($anio, nomina_frecuencia_empresa()));
    break;

  case 'combos_contratos':
    responder(false, 'Success', nomina_select("SELECT id, empleado_documento, salario_basico,
                                                      TRIM(CONCAT_WS(' ', primer_nombre, NULLIF(segundo_nombre,''), primer_apellido, NULLIF(segundo_apellido,''))) AS empleado
                                                 FROM t_nomina_contratos WHERE estado = 'ACTIVO'
                                                ORDER BY primer_nombre, primer_apellido"));
    break;

  case 'combos_subtipos':
    $tipo = nomina_esc(strtoupper(trim($_POST['tipo'] ?? '')));
    if ($tipo === '') {
      responder(false, 'Success', array());
    }
    $rows = nomina_select("SELECT DISTINCT subtipo FROM t_nomina_conceptos
                            WHERE estado = 'ACTIVO' AND tipo = '$tipo' AND IFNULL(subtipo,'') <> ''
                              AND UPPER(subtipo) NOT IN ('SEGURIDAD_SOCIAL','RETENCION') ORDER BY subtipo");
    responder(false, 'Success', array_map(function ($r) {
      return array('id' => $r['subtipo'], 'nombre' => $r['subtipo']);
    }, $rows));
    break;

  case 'combos_conceptos':
    $tipo = nomina_esc(strtoupper(trim($_POST['tipo'] ?? '')));
    $subtipo = nomina_esc(strtoupper(trim($_POST['subtipo'] ?? '')));
    // Se excluyen los conceptos que la liquidación calcula automáticamente
    $sql = "SELECT id, clave, nombre, tipo, subtipo, porcentaje_fijo, requiere_cantidad, requiere_valor
              FROM t_nomina_conceptos
             WHERE estado = 'ACTIVO'
               AND UPPER(IFNULL(subtipo,'')) NOT IN ('SEGURIDAD_SOCIAL','RETENCION')
               AND clave NOT IN ('con_salario_ordinario','con_auxilio_transporte')"
      . ($tipo !== '' ? " AND tipo = '$tipo'" : '')
      . ($subtipo !== '' ? " AND subtipo = '$subtipo'" : '')
      . " ORDER BY tipo, subtipo, nombre";
    responder(false, 'Success', nomina_select($sql));
    break;

  case 'valor_hora':
    // Valor de la hora ordinaria del contrato (base para horas extra y recargos)
    $idContrato = intval($_POST['id_contrato'] ?? 0);
    $per = nomina_resolver_periodo($_POST['periodo'] ?? date('Y-m'));
    $c = nomina_select("SELECT salario_basico FROM t_nomina_contratos WHERE id = $idContrato LIMIT 1");
    if (!$c) {
      responder(true, 'Seleccione el contrato');
    }
    $anio = $per ? $per['anio'] : intval(date('Y'));
    responder(false, 'Success', array(
      'valor_hora' => CalculosNomina::calcularValorHora((float)$c[0]['salario_basico'], $anio),
      'horas_mes' => CalculosNomina::param('horas_semanales', $anio) * 5,
      'valor_dia' => round((float)$c[0]['salario_basico'] / 30, 2),
    ));
    break;

  case 'novedades_listar':
    $where = array();
    $idContrato = intval($_POST['id_contrato'] ?? 0);
    $periodo = nomina_esc(substr(trim($_POST['periodo'] ?? ''), 0, 7));
    $filtro = nomina_esc(trim($_POST['filtro'] ?? ''));
    if ($idContrato > 0) {
      $where[] = "n.id_contrato = $idContrato";
    }
    if ($periodo !== '') {
      $where[] = "n.periodo = '$periodo'";
    }
    if ($filtro !== '') {
      $where[] = "(n.concepto LIKE '%$filtro%' OR co.nombre LIKE '%$filtro%' OR c.empleado_documento LIKE '%$filtro%'
                   OR CONCAT_WS(' ', c.primer_nombre, c.primer_apellido) LIKE '%$filtro%')";
    }
    $sql = "SELECT n.*, c.empleado_documento, co.nombre AS concepto_nombre,
                   TRIM(CONCAT_WS(' ', c.primer_nombre, NULLIF(c.segundo_nombre,''), c.primer_apellido, NULLIF(c.segundo_apellido,''))) AS empleado
              FROM t_nomina_novedades n
             INNER JOIN t_nomina_contratos c ON c.id = n.id_contrato
              LEFT JOIN t_nomina_conceptos co ON co.id = n.id_concepto"
      . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
      . " ORDER BY n.periodo DESC, n.id DESC LIMIT 2000";
    responder(false, 'Success', nomina_select($sql));
    break;

  case 'novedades_agregar':
  case 'novedades_update':
    $data = $_POST;
    $id = intval($data['id'] ?? 0);
    if ($op === 'novedades_update' && $id <= 0) {
      responder(true, 'ID de novedad no válido');
    }
    $idContrato = intval($data['id_contrato'] ?? 0);
    if (!nomina_select("SELECT id FROM t_nomina_contratos WHERE id = $idContrato AND estado = 'ACTIVO' LIMIT 1")) {
      responder(true, 'Debe seleccionar un contrato activo válido');
    }

    // El período llega como 'YYYY-MM' o 'YYYY-MM-01/16'; se valida que la fecha caiga dentro
    $per = nomina_resolver_periodo($data['periodo'] ?? '');
    if (!$per) {
      responder(true, 'Debe seleccionar un período válido');
    }
    $data['periodo_id'] = $per['id'];
    $data['periodo'] = $per['periodo'];
    $fecha = trim($data['fecha_novedad'] ?? '');
    if ($fecha === '') {
      $fecha = $per['fecha_inicio'];
    }
    if ($fecha < $per['fecha_inicio'] || $fecha > $per['fecha_fin']) {
      responder(true, 'La fecha de la novedad debe estar dentro del período seleccionado (' . $per['fecha_inicio'] . ' a ' . $per['fecha_fin'] . ')');
    }
    $data['fecha_novedad'] = $fecha;

    $error = validarNovedad($data, $idContrato, $id);
    if ($error !== '') {
      responder(true, $error);
    }

    // No se permite modificar novedades de una nómina ya contabilizada/emitida
    if ($id > 0) {
      $ant = nomina_select("SELECT id_contrato, periodo, fecha_novedad FROM t_nomina_novedades WHERE id = $id LIMIT 1");
      if ($ant && ($e = nominaCerradaParaNovedad($ant[0]['id_contrato'], $ant[0]['periodo'], $ant[0]['fecha_novedad']))) {
        responder(true, "La nómina de ese período ya está $e; la novedad no puede modificarse");
      }
    }
    if ($e = nominaCerradaParaNovedad($idContrato, $per['id'], $fecha)) {
      responder(true, "La nómina del empleado para ese período ya está $e. Registre la novedad en el siguiente período.");
    }

    $data['dias_incapacidad'] = intval($data['dias_incapacidad'] ?? 0) ?: null;
    $data['estado'] = ($data['estado'] ?? 'ACTIVO') === 'INACTIVO' ? 'INACTIVO' : 'ACTIVO';
    if ($op === 'novedades_agregar') {
      $data['usuario_creacion'] = intval($_SESSION['Ses_id'] ?? 0);
    } else {
      $data['usuario_modificacion'] = intval($_SESSION['Ses_id'] ?? 0);
    }
    unset($data['id'], $data['op'], $data['link'], $data['periodo_id']);
    $data = nomina_filtrar_columnas('t_nomina_novedades', $data);

    if ($op === 'novedades_agregar') {
      $nuevo = insertReturnId('t_nomina_novedades', $data, true);
      responder(!($nuevo > 0), $nuevo > 0 ? 'Novedad registrada correctamente' : 'No se pudo registrar la novedad', array('id' => $nuevo));
    }
    $ok = update('t_nomina_novedades', $data, $id);
    responder(!$ok, $ok ? 'Novedad actualizada correctamente' : 'No se pudo actualizar la novedad', $data);
    break;

  case 'novedades_delete':
    $id = intval($_POST['id'] ?? 0);
    $ant = nomina_select("SELECT id_contrato, periodo, fecha_novedad FROM t_nomina_novedades WHERE id = $id LIMIT 1");
    if (!$ant) {
      responder(true, 'Novedad no encontrada');
    }
    if ($e = nominaCerradaParaNovedad($ant[0]['id_contrato'], $ant[0]['periodo'], $ant[0]['fecha_novedad'])) {
      responder(true, "La nómina de ese período ya está $e; la novedad no puede eliminarse");
    }
    $ok = Query("DELETE FROM t_nomina_novedades WHERE id = $id");
    responder(!$ok, $ok ? 'Novedad eliminada correctamente' : 'No se pudo eliminar la novedad');
    break;

  default:
    responder(true, 'Operación no reconocida');
}
