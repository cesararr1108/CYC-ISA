<?php

/**
 * Perfiles contables de nómina (t_nomina_perfiles_contables).
 * Define las cuentas de gasto y pasivo usadas en la causación. Las cuentas marcadas como
 * obligatorias se validan al guardar; las opcionales usan la cuenta de sueldos si están vacías.
 */

require_once(__DIR__ . '/nominaHelpers.php');

// Cuentas obligatorias (sin ellas la contabilización se rechaza)
const CUENTAS_OBLIGATORIAS = array(
  'cuenta_gasto_sueldo'           => 'Gasto sueldos',
  'cuenta_salario_por_pagar'      => 'Salarios por pagar',
  'cuenta_pasivo_salud'           => 'Pasivo salud',
  'cuenta_pasivo_pension'         => 'Pasivo pensión',
  'cuenta_pasivo_arl'             => 'Pasivo ARL',
  'cuenta_pasivo_caja'            => 'Pasivo caja de compensación',
  'cuenta_gasto_pension_patronal' => 'Gasto pensión empleador',
  'cuenta_gasto_arl'              => 'Gasto ARL',
  'cuenta_gasto_caja'             => 'Gasto caja de compensación',
  'cuenta_gasto_cesantias'        => 'Gasto cesantías',
  'cuenta_pasivo_cesantias'       => 'Pasivo cesantías',
  'cuenta_gasto_int_cesantias'    => 'Gasto intereses de cesantías',
  'cuenta_pasivo_int_cesantias'   => 'Pasivo intereses de cesantías',
  'cuenta_gasto_prima'            => 'Gasto prima',
  'cuenta_pasivo_prima'           => 'Pasivo prima',
  'cuenta_gasto_vacaciones'       => 'Gasto vacaciones',
  'cuenta_pasivo_vacaciones'      => 'Pasivo vacaciones',
  'cuenta_retencion_fuente'       => 'Retención en la fuente por pagar',
);

// Cuentas que solo son obligatorias si la empresa NO está exonerada (art. 114-1 ET) o hay salarios ≥ 10 SMLMV
const CUENTAS_NO_EXONERADO = array(
  'cuenta_gasto_salud_patronal', 'cuenta_gasto_sena', 'cuenta_gasto_icbf', 'cuenta_pasivo_sena', 'cuenta_pasivo_icbf',
);

const CUENTAS_OPCIONALES = array(
  'cuenta_gasto_aux_transporte', 'cuenta_gasto_horas_extras', 'cuenta_gasto_incapacidades', 'cuenta_otras_deducciones',
);

function datosPerfil()
{
  $campos = array_merge(array('codigo', 'descripcion', 'estado'), array_keys(CUENTAS_OBLIGATORIAS), CUENTAS_NO_EXONERADO, CUENTAS_OPCIONALES);
  $data = array();
  foreach ($campos as $c) {
    if (isset($_POST[$c])) {
      $data[$c] = trim((string)$_POST[$c]);
    }
  }
  $data['estado'] = ($data['estado'] ?? 'ACTIVO') === 'INACTIVO' ? 'INACTIVO' : 'ACTIVO';
  return nomina_filtrar_columnas('t_nomina_perfiles_contables', $data);
}

function validarPerfil($data, $id = 0)
{
  if (($data['codigo'] ?? '') === '' || ($data['descripcion'] ?? '') === '') {
    return 'El código y la descripción son obligatorios';
  }
  $faltan = array();
  foreach (CUENTAS_OBLIGATORIAS as $campo => $nombre) {
    if (nomina_tiene_columna('t_nomina_perfiles_contables', $campo) && trim($data[$campo] ?? '') === '') {
      $faltan[] = $nombre;
    }
  }
  if ($faltan) {
    return 'Faltan cuentas obligatorias: ' . implode(', ', $faltan);
  }
  $codigo = nomina_esc($data['codigo']);
  if (nomina_select("SELECT id FROM t_nomina_perfiles_contables WHERE codigo = '$codigo' AND id <> " . intval($id) . " LIMIT 1")) {
    return "Ya existe un perfil con el código {$data['codigo']}";
  }
  return '';
}

$op = $_POST['op'] ?? '';
$alias = array('listar' => 'perfiles_listar', 'agregar' => 'perfiles_agregar', 'update' => 'perfiles_update', 'delete' => 'perfiles_delete');
$op = $alias[$op] ?? $op;

switch ($op) {
  case 'combos_perfiles':
    responder(false, 'Success', nomina_select("SELECT id, codigo, descripcion FROM t_nomina_perfiles_contables WHERE estado = 'ACTIVO' ORDER BY codigo"));
    break;

  case 'perfiles_listar':
    $filtro = nomina_esc(trim($_POST['filtro'] ?? ''));
    $sql = "SELECT p.*, (SELECT COUNT(*) FROM t_nomina_contratos c WHERE c.id_perfil_contable = p.id AND c.estado = 'ACTIVO') AS contratos
              FROM t_nomina_perfiles_contables p";
    if ($filtro !== '') {
      $sql .= " WHERE p.codigo LIKE '%$filtro%' OR p.descripcion LIKE '%$filtro%' OR p.cuenta_gasto_sueldo LIKE '%$filtro%' OR p.cuenta_salario_por_pagar LIKE '%$filtro%'";
    }
    $sql .= " ORDER BY p.id DESC";
    responder(false, 'Success', nomina_select($sql));
    break;

  case 'campos_perfil':
    // Columnas disponibles (permite a la vista ocultar campos si la migración no se ha ejecutado)
    responder(false, 'Success', nomina_columnas('t_nomina_perfiles_contables'));
    break;

  case 'perfiles_agregar':
  case 'perfiles_update':
    $id = intval($_POST['id'] ?? 0);
    $data = datosPerfil();
    $error = validarPerfil($data, $id);
    if ($error !== '') {
      responder(true, $error);
    }
    if ($op === 'perfiles_agregar') {
      $data['usuario_creacion'] = intval($_SESSION['Ses_id'] ?? 0);
      $nuevo = insertReturnId('t_nomina_perfiles_contables', nomina_filtrar_columnas('t_nomina_perfiles_contables', $data), true);
      responder(!($nuevo > 0), $nuevo > 0 ? 'Perfil contable creado correctamente' : 'No se pudo crear el perfil contable', array('id' => $nuevo));
    }
    if ($id <= 0) {
      responder(true, 'Perfil no válido');
    }
    if ($data['estado'] === 'INACTIVO') {
      $uso = nomina_select("SELECT COUNT(*) AS n FROM t_nomina_contratos WHERE id_perfil_contable = $id AND estado = 'ACTIVO'");
      if (intval($uso[0]['n'] ?? 0) > 0) {
        responder(true, 'No se puede inactivar: hay ' . $uso[0]['n'] . ' contrato(s) activos con este perfil');
      }
    }
    $ok = update('t_nomina_perfiles_contables', $data, $id);
    responder(!$ok, $ok ? 'Perfil contable actualizado correctamente' : 'No se pudo actualizar el perfil contable', $data);
    break;

  case 'perfiles_delete':
    $id = intval($_POST['id'] ?? 0);
    $uso = nomina_select("SELECT (SELECT COUNT(*) FROM t_nomina WHERE id_perfil_contable = $id) AS nominas,
                                 (SELECT COUNT(*) FROM t_nomina_contratos WHERE id_perfil_contable = $id) AS contratos");
    if (intval($uso[0]['nominas'] ?? 0) > 0 || intval($uso[0]['contratos'] ?? 0) > 0) {
      responder(true, 'No se puede eliminar: el perfil está asociado a contratos o liquidaciones. Puede inactivarlo.');
    }
    $ok = Query("DELETE FROM t_nomina_perfiles_contables WHERE id = $id");
    responder(!$ok, $ok ? 'Perfil contable eliminado correctamente' : 'No se pudo eliminar el perfil contable');
    break;

  default:
    responder(true, 'Operación no válida');
}
