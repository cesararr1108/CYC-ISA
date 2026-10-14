<?php

/**
 * Contratos de nómina
 * Valida condiciones legales del contrato (SMLMV, salario integral, vigencia, afiliaciones)
 * y evita contratos activos duplicados para un mismo empleado (doble liquidación).
 */

require_once(__DIR__ . '/nominaCalculos.php');

function obtenerEmpleadoTercero($idTercero, $idTipoIdentificacion, $documento)
{
  if ($idTercero > 0) {
    $filtro = " AND t.id = " . intval($idTercero);
  } else {
    $documento = nomina_esc(trim($documento));
    $idTipoIdentificacion = intval($idTipoIdentificacion);
    if ($documento === '' || $idTipoIdentificacion <= 0) {
      return null;
    }
    $filtro = " AND t.nit = '$documento' AND t.id_tipo_identificacion = $idTipoIdentificacion";
  }
  $data = nomina_select("SELECT t.id, t.id_tipo_identificacion, ti.tipo_identificacion, t.nit AS documento,
                                t.primer_nombre, t.segundo_nombre, t.primer_apellido, t.segundo_apellido,
                                t.razon_comercial, t.tratamiento
                           FROM t_terceros t
                          INNER JOIN t_tipos_terceros tt ON tt.id = t.id_tipo_tercero
                           LEFT JOIN t_tipo_identificaciones ti ON ti.id = t.id_tipo_identificacion
                          WHERE (IFNULL(tt.es_empleado,0) = 1 OR UPPER(IFNULL(tt.es_empleado,'')) = 'S') $filtro
                          LIMIT 1");
  return $data ? $data[0] : null;
}

function validarDatosPagoContrato(&$data)
{
  $metodoPago = trim($data['metodo_pago'] ?? '');
  if ($metodoPago === '') {
    $data['metodo_pago'] = $data['banco'] = $data['tipo_cuenta'] = $data['numero_cuenta'] = null;
    return '';
  }
  if (!in_array($metodoPago, array('10', '30', '31', '42', '45'), true)) {
    return 'Código de método de pago inválido: ' . $metodoPago;
  }
  if ($metodoPago === '10') {
    $data['banco'] = $data['tipo_cuenta'] = $data['numero_cuenta'] = null;
    return '';
  }
  $banco = trim($data['banco'] ?? '');
  $tipoCuenta = strtoupper(trim($data['tipo_cuenta'] ?? ''));
  $numeroCuenta = preg_replace('/\s+/', '', (string)($data['numero_cuenta'] ?? ''));
  if ($banco === '') return 'El banco es requerido para pagos por transferencia o consignación';
  if (!in_array($tipoCuenta, array('CC', 'AH'), true)) return 'Tipo de cuenta inválido: use CC (corriente) o AH (ahorros)';
  if (!preg_match('/^\d{8,30}$/', $numeroCuenta)) return 'Número de cuenta inválido: debe tener entre 8 y 30 dígitos';
  $data['banco'] = $banco;
  $data['tipo_cuenta'] = $tipoCuenta;
  $data['numero_cuenta'] = $numeroCuenta;
  return '';
}

/** Validaciones comunes para crear/actualizar */
function prepararContrato(array $data, $idContrato = 0)
{
  $tipoContrato = nomina_select("SELECT id FROM t_nomina_tipos_contratos WHERE id = " . intval($data['id_tipo_contrato'] ?? 0) . " AND estado = 'ACTIVO' LIMIT 1");
  if (!$tipoContrato) {
    return 'Debe seleccionar un tipo de contrato válido y activo';
  }
  $idPerfil = intval($data['id_perfil_contable'] ?? 0);
  if ($idPerfil <= 0 || !nomina_select("SELECT id FROM t_nomina_perfiles_contables WHERE id = $idPerfil AND estado = 'ACTIVO' LIMIT 1")) {
    return 'Debe seleccionar un perfil contable activo (se requiere para contabilizar la nómina)';
  }

  $empleado = obtenerEmpleadoTercero(intval($data['id_tercero'] ?? 0), intval($data['id_tipo_identificacion'] ?? 0), $data['empleado_documento'] ?? '');
  if ($empleado === null) {
    return 'El tercero debe existir y estar configurado como empleado en Terceros';
  }
  $data['tipo_documento'] = $empleado['tipo_identificacion'] ?: ($data['tipo_documento'] ?? 'CC');
  $data['empleado_documento'] = $empleado['documento'];
  foreach (array('primer_nombre', 'segundo_nombre', 'primer_apellido', 'segundo_apellido') as $c) {
    $data[$c] = $empleado[$c] ?? '';
  }

  // Fechas
  $data['fecha_inicio'] = trim($data['fecha_inicio'] ?? '');
  $data['fecha_fin'] = trim($data['fecha_fin'] ?? '') ?: null;
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['fecha_inicio'])) {
    return 'La fecha de inicio es obligatoria';
  }
  if ($data['fecha_fin'] && $data['fecha_fin'] < $data['fecha_inicio']) {
    return 'La fecha de finalización no puede ser anterior a la fecha de inicio';
  }

  // Salario
  $anio = intval(substr(max($data['fecha_inicio'], date('Y-m-d')), 0, 4));
  $p = CalculosNomina::cargarParametros($anio);
  $smlv = (float)($p['smlv'] ?? 0);
  $salario = round(floatval($data['salario_basico'] ?? 0), 2);
  $data['salario_integral'] = intval($data['salario_integral'] ?? 0) === 1 ? 1 : 0;
  $jornada = strtoupper(trim($data['jornada'] ?? 'COMPLETA'));
  if ($salario <= 0) {
    return 'El salario básico debe ser mayor a cero';
  }
  if ($smlv > 0 && $jornada === 'COMPLETA' && $salario < $smlv) {
    return 'El salario de jornada completa no puede ser inferior al SMLMV (' . nomina_money($smlv) . ')';
  }
  if ($data['salario_integral'] && $smlv > 0 && $salario < $smlv * 13) {
    return 'El salario integral debe ser mínimo 13 SMLMV (' . nomina_money($smlv * 13) . ') – art. 132 CST';
  }
  $data['salario_basico'] = $salario;
  $aux = trim((string)($data['aux_transporte'] ?? ''));
  $data['aux_transporte'] = $aux === '' ? null : round(floatval($aux), 2);
  if ($data['salario_integral']) {
    $data['aux_transporte'] = 0;
  }

  $data['subtipo_trabajador'] = in_array(trim($data['subtipo_trabajador'] ?? '00'), array('00', '01'), true) ? trim($data['subtipo_trabajador']) : '00';
  $data['alto_riesgo'] = intval($data['alto_riesgo'] ?? 0) === 1 ? 1 : 0;
  $data['jornada'] = in_array($jornada, array('COMPLETA', 'PARCIAL'), true) ? $jornada : 'COMPLETA';
  $data['clase_riesgo_arl'] = max(1, min(5, intval($data['clase_riesgo_arl'] ?? 1)));
  foreach (array('id_entidad_salud', 'id_entidad_pension', 'id_entidad_cesantias', 'id_entidad_caja', 'id_entidad_arl') as $c) {
    $data[$c] = intval($data[$c] ?? 0) ?: null;
  }
  $data['estado'] = ($data['estado'] ?? 'ACTIVO') === 'INACTIVO' ? 'INACTIVO' : 'ACTIVO';

  if ($data['estado'] === 'ACTIVO') {
    if (empty($data['id_entidad_salud'])) {
      return 'Debe seleccionar la EPS del empleado';
    }
    if (empty($data['id_entidad_pension']) && $data['subtipo_trabajador'] !== '01') {
      return 'Debe seleccionar el fondo de pensiones (solo se omite para pensionados – subtipo 01)';
    }
    // Un empleado no puede tener dos contratos activos que se crucen (se liquidaría dos veces)
    $doc = nomina_esc($data['empleado_documento']);
    $fin = $data['fecha_fin'] ? "'" . $data['fecha_fin'] . "'" : "'9999-12-31'";
    $dup = nomina_select("SELECT id FROM t_nomina_contratos
                           WHERE empleado_documento = '$doc' AND estado = 'ACTIVO' AND id <> " . intval($idContrato) . "
                             AND fecha_inicio <= $fin
                             AND (fecha_fin IS NULL OR fecha_fin >= '{$data['fecha_inicio']}')
                           LIMIT 1");
    if ($dup) {
      return 'El empleado ya tiene un contrato activo (#' . $dup[0]['id'] . ') en esas fechas. Inactívelo o fije su fecha de finalización.';
    }
  }

  $validacionPago = validarDatosPagoContrato($data);
  if ($validacionPago !== '') {
    return $validacionPago;
  }

  unset($data['id_tercero'], $data['id_tipo_identificacion'], $data['op'], $data['link']);
  return $data;
}

$op = $_POST['op'] ?? '';
$alias = array('listar' => 'contratos_listar', 'agregar' => 'contratos_agregar', 'update' => 'contratos_update', 'delete' => 'contratos_delete');
$op = $alias[$op] ?? $op;

switch ($op) {
  case 'combos_tipos_contratos':
    responder(false, 'Success', nomina_select("SELECT id, codigo, descripcion FROM t_nomina_tipos_contratos WHERE estado = 'ACTIVO' ORDER BY codigo"));
    break;

  case 'combos_entidades_afiliacion':
    $tipo = nomina_esc(strtoupper(trim($_POST['tipo_entidad'] ?? '')));
    if ($tipo === '') {
      responder(false, 'Success', array());
    }
    responder(false, 'Success', nomina_select("SELECT e.id, e.tipo_entidad, e.codigo_entidad, t.nit, " . nomina_sql_nombre_tercero('t') . " AS nombre
                                                 FROM t_nomina_entidades e
                                                INNER JOIN t_terceros t ON t.id = e.id_tercero
                                                WHERE e.estado = 'ACTIVO' AND e.tipo_entidad = '$tipo'
                                                ORDER BY nombre"));
    break;

  case 'buscar_terceros_empleado':
    $filtro = nomina_esc(trim($_POST['filtro'] ?? ''));
    if ($filtro === '') {
      responder(false, 'Success', array());
    }
    responder(false, 'Success', nomina_select("SELECT t.id, t.id_tipo_identificacion, ti.tipo_identificacion, t.nit AS documento,
                                                      t.primer_nombre, t.segundo_nombre, t.primer_apellido, t.segundo_apellido,
                                                      t.razon_comercial, t.tratamiento,
                                                      CONCAT(t.nit, ' - ', " . nomina_sql_nombre_tercero('t') . ") AS value
                                                 FROM t_terceros t
                                                INNER JOIN t_tipos_terceros tt ON tt.id = t.id_tipo_tercero
                                                 LEFT JOIN t_tipo_identificaciones ti ON ti.id = t.id_tipo_identificacion
                                                WHERE (IFNULL(tt.es_empleado,0) = 1 OR UPPER(IFNULL(tt.es_empleado,'')) = 'S')
                                                  AND (t.nit LIKE '%$filtro%' OR CONCAT_WS(' ', t.primer_nombre, t.segundo_nombre, t.primer_apellido, t.segundo_apellido) LIKE '%$filtro%'
                                                       OR IFNULL(t.razon_comercial,'') LIKE '%$filtro%')
                                                ORDER BY t.primer_nombre, t.primer_apellido
                                                LIMIT 20"));
    break;

  case 'combos_contratos':
    responder(false, 'Success', nomina_select("SELECT id, empleado_documento,
                                                      TRIM(CONCAT_WS(' ', primer_nombre, NULLIF(segundo_nombre,''), primer_apellido, NULLIF(segundo_apellido,''))) AS empleado
                                                 FROM t_nomina_contratos WHERE estado = 'ACTIVO'
                                                ORDER BY primer_nombre, primer_apellido"));
    break;

  case 'contratos_listar':
    $filtro = nomina_esc(trim($_POST['filtro'] ?? ''));
    $documentoExacto = nomina_esc(trim($_POST['documento'] ?? ''));
    $where = array();
    if ($documentoExacto !== '') {
      $where[] = "c.empleado_documento = '$documentoExacto'";
    }
    if ($filtro !== '') {
      $where[] = "(c.empleado_documento LIKE '%$filtro%' OR CONCAT_WS(' ', c.primer_nombre, c.segundo_nombre, c.primer_apellido, c.segundo_apellido) LIKE '%$filtro%'
                  OR c.cargo LIKE '%$filtro%' OR IFNULL(ts.razon_comercial,'') LIKE '%$filtro%' OR IFNULL(tp.razon_comercial,'') LIKE '%$filtro%')";
    }
    $sql = "SELECT c.*,
                   ttc.codigo AS tipo_contrato_codigo, ttc.descripcion AS tipo_contrato_descripcion,
                   tpc.codigo AS perfil_contable_codigo, tpc.descripcion AS perfil_contable_descripcion,
                   es.codigo_entidad AS codigo_eps, ep.codigo_entidad AS codigo_pension,
                   ec.codigo_entidad AS codigo_cesantias, ej.codigo_entidad AS codigo_caja,
                   " . nomina_sql_nombre_tercero('ts') . " AS eps_nombre,
                   " . nomina_sql_nombre_tercero('tp') . " AS pension_nombre,
                   " . nomina_sql_nombre_tercero('tc') . " AS cesantias_nombre,
                   " . nomina_sql_nombre_tercero('tj') . " AS caja_nombre,
                   ti.id AS id_tipo_identificacion,
                   (SELECT t3.id FROM t_terceros t3 WHERE t3.nit = c.empleado_documento
                     ORDER BY (t3.id_tipo_identificacion = ti.id) DESC LIMIT 1) AS id_tercero
              FROM t_nomina_contratos c
              LEFT JOIN t_nomina_tipos_contratos ttc ON ttc.id = c.id_tipo_contrato
              LEFT JOIN t_nomina_perfiles_contables tpc ON tpc.id = c.id_perfil_contable
              LEFT JOIN t_tipo_identificaciones ti ON ti.tipo_identificacion = c.tipo_documento
              LEFT JOIN t_nomina_entidades es ON es.id = c.id_entidad_salud
              LEFT JOIN t_terceros ts ON ts.id = es.id_tercero
              LEFT JOIN t_nomina_entidades ep ON ep.id = c.id_entidad_pension
              LEFT JOIN t_terceros tp ON tp.id = ep.id_tercero
              LEFT JOIN t_nomina_entidades ec ON ec.id = c.id_entidad_cesantias
              LEFT JOIN t_terceros tc ON tc.id = ec.id_tercero
              LEFT JOIN t_nomina_entidades ej ON ej.id = c.id_entidad_caja
              LEFT JOIN t_terceros tj ON tj.id = ej.id_tercero"
      . ($where ? ' WHERE ' . implode(' AND ', $where) : '') .
      " ORDER BY c.estado = 'ACTIVO' DESC, c.id DESC";
    responder(false, 'Success', nomina_select($sql));
    break;

  case 'contratos_agregar':
  case 'contratos_update':
    $id = intval($_POST['id'] ?? 0);
    if ($op === 'contratos_update' && $id <= 0) {
      responder(true, 'Contrato no válido');
    }
    $data = prepararContrato($_POST, $id);
    if (is_string($data)) {
      responder(true, $data);
    }
    unset($data['id']);
    if ($op === 'contratos_agregar') {
      $data['usuario_creacion'] = intval($_SESSION['Ses_id'] ?? 0);
    }
    $data = nomina_filtrar_columnas('t_nomina_contratos', $data);

    if ($op === 'contratos_agregar') {
      $nuevoId = insertReturnId('t_nomina_contratos', $data, true);
      responder(!($nuevoId > 0), $nuevoId > 0 ? 'Contrato creado correctamente' : 'No se pudo crear el contrato', array('id' => $nuevoId));
    }
    $ok = update('t_nomina_contratos', $data, $id);
    responder(!$ok, $ok ? 'Contrato actualizado correctamente' : 'No se pudo actualizar el contrato', $data);
    break;

  case 'contratos_delete':
    $id = intval($_POST['id'] ?? 0);
    $uso = nomina_select("SELECT (SELECT COUNT(*) FROM t_nomina WHERE id_contrato = $id) AS nominas,
                                 (SELECT COUNT(*) FROM t_nomina_novedades WHERE id_contrato = $id) AS novedades");
    if (intval($uso[0]['nominas'] ?? 0) > 0) {
      responder(true, 'No se puede eliminar: el contrato tiene liquidaciones. Puede inactivarlo.');
    }
    if (intval($uso[0]['novedades'] ?? 0) > 0) {
      responder(true, 'No se puede eliminar: el contrato tiene novedades registradas. Puede inactivarlo.');
    }
    $ok = Query("DELETE FROM t_nomina_contratos WHERE id = $id");
    responder(!$ok, $ok ? 'Contrato eliminado correctamente' : 'No se pudo eliminar el contrato');
    break;

  default:
    responder(true, 'Operación no válida');
}
