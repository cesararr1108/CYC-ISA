<?php

/**
 * Maestra de tipos de documentos (t_tipos_documentos)
 *
 * Banderas especiales:
 *  - nomina = 'S'            → sw 14 (causación de nómina) o sw 9 (egreso de pago de nómina).
 *                              Estos documentos graban su propio asiento desde el módulo de nómina.
 *  - documento_soporte = 'S' → solo sw 14 (documento soporte a no obligados a facturar).
 *  Ambas banderas son excluyentes.
 *
 * Las respuestas de 'listar', 'listar_sw', 'buscar-tipo' y las de retención conservan su formato
 * original porque otros módulos las consumen.
 */

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once('bd.php');
require_once('funciones.php');
$conn = conectar();

const TD_SW_NOMINA = array(9, 14);
const TD_SW_SOPORTE = array(14);

function td_json($data)
{
  echo json_encode($data);
  exit;
}

function td_responder($error, $message, $data = null)
{
  td_json(array('error' => $error, 'message' => $message, 'data' => $data));
}

function td_esc($v)
{
  global $conn;
  return $conn->real_escape_string((string)$v);
}

function td_select($sql)
{
  global $conn;
  $res = $conn->query($sql);
  if (!$res) {
    return array();
  }
  $rows = $res->fetch_all(MYSQLI_ASSOC);
  $res->free();
  return $rows;
}

/** Columnas reales de una tabla (evita grabar llaves arbitrarias del POST) */
function td_columnas($tabla)
{
  static $cache = array();
  if (!isset($cache[$tabla])) {
    $cache[$tabla] = array_column(td_select("SHOW COLUMNS FROM `$tabla`"), 'Field');
  }
  return $cache[$tabla];
}

function td_filtrar($tabla, array $data)
{
  return array_intersect_key($data, array_flip(td_columnas($tabla)));
}

/** SELECT base para listados (LEFT JOIN: un usuario o almacén borrado no oculta el documento) */
function td_sql_base()
{
  return "SELECT t.*,
                 IFNULL(a.descripcion, 'Todos') AS almacen_des,
                 IF(t.inactivo = 1, 'INACTIVO', 'ACTIVO') AS inactivo_des,
                 IFNULL(u.usuario, '') AS usuario
            FROM t_tipos_documentos t
            LEFT JOIN t_almacenes a ON a.id = t.almacen
            LEFT JOIN t_usuarios u ON u.id = t.usuario_creacion";
}

/** Normaliza y valida los datos del formulario */
function td_preparar(array $post, $id = 0)
{
  $d = array(
    'tipo'                  => strtoupper(trim($post['tipo'] ?? '')),
    'descripcion'           => trim($post['descripcion'] ?? ''),
    'sw'                    => intval($post['sw'] ?? -1),
    'almacen'               => intval($post['almacen'] ?? 0),
    'rango_inicial'         => intval($post['rango_inicial'] ?? 0),
    'rango_final'           => intval($post['rango_final'] ?? 0),
    'numero'                => intval($post['numero'] ?? 0),
    'leyenda1'              => mb_substr(trim($post['leyenda1'] ?? ''), 0, 255),
    'leyenda2'              => mb_substr(trim($post['leyenda2'] ?? ''), 0, 255),
    'inactivo'              => intval($post['inactivo'] ?? 0) === 1 ? 1 : 0,
    'fecha_inicio'          => trim($post['fecha_inicio'] ?? '') ?: null,
    'fecha_fin'             => trim($post['fecha_fin'] ?? '') ?: null,
    'resolucion'            => trim($post['resolucion'] ?? '0') ?: '0',
    'documento_electronico' => ($post['documento_electronico'] ?? 'N') === 'S' ? 'S' : 'N',
    'documento_soporte'     => ($post['documento_soporte'] ?? 'N') === 'S' ? 'S' : 'N',
    'nomina'                => ($post['nomina'] ?? 'N') === 'S' ? 'S' : 'N',
  );

  if ($d['tipo'] === '' || !preg_match('/^[A-Z0-9_-]{1,10}$/', $d['tipo'])) {
    return 'El tipo es obligatorio: hasta 10 letras o números, sin espacios';
  }
  if ($d['descripcion'] === '') {
    return 'La descripción es obligatoria';
  }
  if ($d['sw'] < 0) {
    return 'Seleccione el sw del documento';
  }
  if ($d['rango_inicial'] < 0 || $d['rango_final'] < 0 || $d['numero'] < 0) {
    return 'Los rangos y el número no pueden ser negativos';
  }
  if ($d['rango_final'] > 0 && $d['rango_inicial'] > $d['rango_final']) {
    return 'El rango inicial no puede ser mayor que el rango final';
  }
  if ($d['rango_final'] > 0 && $d['numero'] > $d['rango_final']) {
    return 'El número actual supera el rango final autorizado';
  }
  if ($d['fecha_inicio'] && $d['fecha_fin'] && $d['fecha_fin'] < $d['fecha_inicio']) {
    return 'La fecha final no puede ser anterior a la fecha inicial';
  }

  // Banderas según el sw
  if (!in_array($d['sw'], TD_SW_SOPORTE, true)) {
    $d['documento_soporte'] = 'N';
  }
  if (!in_array($d['sw'], TD_SW_NOMINA, true)) {
    $d['nomina'] = 'N';
  }
  if ($d['nomina'] === 'S' && $d['documento_soporte'] === 'S') {
    return 'Un documento no puede ser de nómina y documento soporte al mismo tiempo';
  }

  $tipoEsc = td_esc($d['tipo']);
  $dup = td_select("SELECT id FROM t_tipos_documentos WHERE tipo = '$tipoEsc' AND id <> " . intval($id) . " LIMIT 1");
  if ($dup) {
    return "Ya existe un tipo de documento con el código {$d['tipo']}";
  }
  return $d;
}

/** Datos de un concepto de retención */
function td_retencion(array $post)
{
  $d = array(
    'id_tipo_documento'     => intval($post['id_tipo_documento'] ?? 0),
    'concepto_descripcion'  => trim($post['concepto_descripcion'] ?? ''),
    'cuenta_retefuente'     => trim($post['cuenta_retefuente'] ?? ''),
    'base_retefuente'       => floatval($post['base_retefuente'] ?? 0),
    'porcentaje_retefuente' => floatval($post['porcentaje_retefuente'] ?? 0),
    'cuenta_reteica'        => trim($post['cuenta_reteica'] ?? ''),
    'base_reteica'          => floatval($post['base_reteica'] ?? 0),
    'porcentaje_reteica'    => floatval($post['porcentaje_reteica'] ?? 0),
    'cuenta_reteiva'        => trim($post['cuenta_reteiva'] ?? ''),
    'base_reteiva'          => floatval($post['base_reteiva'] ?? 0),
    'porcentaje_reteiva'    => floatval($post['porcentaje_reteiva'] ?? 0),
  );
  if ($d['id_tipo_documento'] <= 0) {
    return 'Guarde o seleccione el tipo de documento primero';
  }
  if ($d['concepto_descripcion'] === '') {
    return 'La descripción del concepto es obligatoria';
  }
  foreach (array('retefuente', 'reteica', 'reteiva') as $r) {
    if ($d["porcentaje_$r"] < 0 || $d["porcentaje_$r"] > 100 || $d["base_$r"] < 0) {
      return 'Bases y porcentajes deben ser positivos (porcentaje máximo 100)';
    }
    if ($d["porcentaje_$r"] > 0 && $d["cuenta_$r"] === '') {
      return 'Indique la cuenta de ' . strtoupper(str_replace('rete', 'rete ', $r)) . ' para el porcentaje registrado';
    }
  }
  return $d;
}

$op = $_POST['op'] ?? '';

switch ($op) {
  case 'listar':
    $filtro = td_esc(trim($_POST['filtro'] ?? ''));
    $where = '';
    if ($filtro !== '') {
      $where = " WHERE (t.tipo LIKE '%$filtro%' OR t.descripcion LIKE '%$filtro%'"
        . (ctype_digit($filtro) ? " OR t.sw = " . intval($filtro) : '') . ")";
    }
    td_responder(false, 'Success', td_select(td_sql_base() . $where . " ORDER BY t.inactivo, t.sw, t.tipo"));
    break;

  case 'agregar':
  case 'update':
    $id = intval($_POST['id'] ?? 0);
    if ($op === 'update') {
      $actual = td_select("SELECT editable FROM t_tipos_documentos WHERE id = $id LIMIT 1");
      if (!$actual) {
        td_responder(true, 'El tipo de documento no existe');
      }
      if (strtoupper((string)($actual[0]['editable'] ?? 'S')) === 'N') {
        td_responder(true, 'Este tipo de documento es del sistema y no se puede modificar');
      }
    }
    $data = td_preparar($_POST, $id);
    if (is_string($data)) {
      td_responder(true, $data);
    }
    if ($op === 'agregar') {
      $data['usuario_creacion'] = intval($_SESSION['Ses_id'] ?? 0);
      $nuevo = insertReturnId('t_tipos_documentos', td_filtrar('t_tipos_documentos', $data), true);
      if ($nuevo > 0) {
        $data['id'] = $nuevo;
        td_responder(false, 'Tipo de documento creado correctamente', $data);
      }
      td_responder(true, 'No se pudo crear el tipo de documento');
    }
    // En la edición no se cambia el sw (los documentos ya grabados dependen de él) ni el usuario creador
    unset($data['sw']);
    $data = td_filtrar('t_tipos_documentos', $data);
    $ok = update('t_tipos_documentos', $data, $id);
    td_responder(!$ok, $ok ? 'Tipo de documento actualizado correctamente' : 'No se pudo actualizar el tipo de documento', $data + array('id' => $id));
    break;

  case 'delete':
    $id = intval($_POST['id'] ?? 0);
    $actual = td_select("SELECT editable FROM t_tipos_documentos WHERE id = $id LIMIT 1");
    if (!$actual) {
      td_responder(true, 'El tipo de documento no existe');
    }
    if (strtoupper((string)($actual[0]['editable'] ?? 'S')) === 'N') {
      td_responder(true, 'Este tipo de documento es del sistema y no se puede eliminar');
    }
    if (!validarDependencias(array('t_transacciones', 't_transacciones_tmp', 't_pedidos'), 'id_tipo_documento', $id)) {
      td_responder(true, 'No se puede eliminar: ya existen documentos de este tipo. Puede inactivarlo.');
    }
    $ok = Query("DELETE FROM t_tipos_documentos WHERE id = $id");
    td_responder(!$ok, $ok ? 'Tipo de documento eliminado correctamente' : 'No se pudo eliminar el tipo de documento');
    break;

  case 'listar_sw':
    // Usado por otros módulos: tipos activos de un sw para una bodega
    $sw = intval($_POST['sw'] ?? 0);
    $bodega = intval($_POST['bodega'] ?? 0);
    td_responder(false, 'Success', td_select("SELECT * FROM t_tipos_documentos
                                                WHERE sw = $sw AND inactivo = 0 AND almacen IN (0, $bodega)
                                                ORDER BY tipo"));
    break;

  case 'buscar-tipo':
    $tipo = td_esc(strtoupper(trim($_POST['tipo'] ?? '')));
    td_json(td_select(td_sql_base() . " WHERE t.tipo = '$tipo' LIMIT 1"));
    break;

  /* ---------------- Conceptos de retención ---------------- */
  case 'create_retencion':
  case 'update_retencion':
    $data = td_retencion($_POST);
    if (is_string($data)) {
      td_json(array('ok' => false, 'message' => $data));
    }
    if ($op === 'create_retencion') {
      $data['estado'] = 1;
      $id = insertReturnId('t_tipos_documentos_concep_ret', td_filtrar('t_tipos_documentos_concep_ret', $data));
      td_json($id
        ? array('ok' => true, 'message' => 'Concepto de retención creado', 'id' => $id)
        : array('ok' => false, 'message' => 'No se pudo crear el concepto'));
    }
    $id = intval($_POST['id'] ?? 0);
    $ok = $id > 0 && update('t_tipos_documentos_concep_ret', td_filtrar('t_tipos_documentos_concep_ret', $data), $id);
    td_json(array('ok' => (bool)$ok, 'message' => $ok ? 'Concepto de retención actualizado' : 'No se pudo actualizar el concepto'));
    break;

  case 'delete_retencion':
    $id = intval($_POST['id'] ?? 0);
    $ok = $id > 0 && Query("UPDATE t_tipos_documentos_concep_ret SET estado = 0 WHERE id = $id");
    td_json(array('ok' => (bool)$ok, 'message' => $ok ? 'Concepto de retención eliminado' : 'No se pudo eliminar el concepto'));
    break;

  case 'list_retencion':
    $idTipo = intval($_POST['id_tipo_documento'] ?? 0);
    td_json(array('ok' => true, 'data' => td_select("SELECT * FROM t_tipos_documentos_concep_ret
                                                       WHERE estado = 1 AND id_tipo_documento = $idTipo
                                                       ORDER BY concepto_descripcion")));
    break;

  case 'get_retencion':
    $id = intval($_POST['id'] ?? 0);
    $row = td_select("SELECT * FROM t_tipos_documentos_concep_ret WHERE id = $id LIMIT 1");
    td_json(array('ok' => (bool)$row, 'data' => $row[0] ?? null, 'message' => $row ? '' : 'Concepto no encontrado'));
    break;

  default:
    td_responder(true, 'Operación no válida');
}
