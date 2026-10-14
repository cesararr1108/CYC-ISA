<?php

/**
 * Entidades de nómina (EPS, AFP, cesantías, caja, ARL, SENA, ICBF, DIAN).
 * El NIT del tercero asociado se usa como tercero de los pasivos al contabilizar,
 * lo que permite conciliar la PILA y la retención por entidad.
 */

require_once(__DIR__ . '/nominaHelpers.php');

const TIPOS_ENTIDAD = array('SALUD', 'PENSION', 'FONDO_CESANTIAS', 'CAJA_COMPENSACION', 'ARL', 'SENA', 'ICBF', 'DIAN');

/** Campos permitidos (evita que se graben llaves arbitrarias del POST) */
function datosEntidad()
{
  $data = array(
    'tipo_entidad'   => strtoupper(trim($_POST['tipo_entidad'] ?? '')),
    'codigo_entidad' => trim($_POST['codigo_entidad'] ?? ''),
    'id_tercero'     => intval($_POST['id_tercero'] ?? 0),
    'estado'         => ($_POST['estado'] ?? 'ACTIVO') === 'INACTIVO' ? 'INACTIVO' : 'ACTIVO',
    'observaciones'  => mb_substr(trim($_POST['observaciones'] ?? ''), 0, 250),
  );
  return nomina_filtrar_columnas('t_nomina_entidades', $data);
}

function validarEntidad($data, $id = 0)
{
  if (!in_array($data['tipo_entidad'], TIPOS_ENTIDAD, true)) {
    return 'Tipo de entidad no válido';
  }
  if ($data['codigo_entidad'] === '') {
    return 'El código de la entidad es obligatorio';
  }
  if ($data['id_tercero'] <= 0 || !nomina_select("SELECT id FROM t_terceros WHERE id = {$data['id_tercero']} LIMIT 1")) {
    return 'Debe seleccionar un tercero válido';
  }
  $tipo = nomina_esc($data['tipo_entidad']);
  $codigo = nomina_esc($data['codigo_entidad']);
  $dup = nomina_select("SELECT id FROM t_nomina_entidades
                         WHERE tipo_entidad = '$tipo' AND id <> " . intval($id) . "
                           AND (codigo_entidad = '$codigo' OR id_tercero = {$data['id_tercero']})
                         LIMIT 1");
  if ($dup) {
    return 'Ya existe una entidad de tipo ' . $data['tipo_entidad'] . ' con ese código o tercero (#' . $dup[0]['id'] . ')';
  }
  return '';
}

$op = $_POST['op'] ?? '';
$alias = array('listar' => 'entidades_listar', 'agregar' => 'entidades_agregar', 'update' => 'entidades_update', 'delete' => 'entidades_delete');
$op = $alias[$op] ?? $op;

switch ($op) {
  case 'buscar_terceros_general':
    $filtro = nomina_esc(trim($_POST['filtro'] ?? ''));
    if ($filtro === '') {
      responder(false, 'Success', array());
    }
    responder(false, 'Success', nomina_select("SELECT t.id, t.nit, t.primer_nombre, t.segundo_nombre, t.primer_apellido, t.segundo_apellido,
                                                      t.razon_comercial, t.tratamiento,
                                                      CONCAT(t.nit, ' - ', " . nomina_sql_nombre_tercero('t') . ") AS value
                                                 FROM t_terceros t
                                                WHERE t.nit LIKE '%$filtro%'
                                                   OR IFNULL(t.razon_comercial,'') LIKE '%$filtro%'
                                                   OR CONCAT_WS(' ', t.primer_nombre, t.segundo_nombre, t.primer_apellido, t.segundo_apellido) LIKE '%$filtro%'
                                                ORDER BY t.razon_comercial, t.primer_nombre
                                                LIMIT 20"));
    break;

  case 'entidades_listar':
    $filtro = nomina_esc(trim($_POST['filtro'] ?? ''));
    $sql = "SELECT e.*, t.nit,
                   TRIM(CONCAT_WS(' ', t.primer_nombre, t.segundo_nombre, t.primer_apellido, t.segundo_apellido)) AS tercero_nombre,
                   IFNULL(t.razon_comercial,'') AS razon_comercial,
                   (SELECT COUNT(*) FROM t_nomina_contratos c
                     WHERE c.estado = 'ACTIVO'
                       AND e.id IN (c.id_entidad_salud, c.id_entidad_pension, c.id_entidad_cesantias, c.id_entidad_caja"
      . (nomina_tiene_columna('t_nomina_contratos', 'id_entidad_arl') ? ', c.id_entidad_arl' : '') . ")) AS contratos
              FROM t_nomina_entidades e
             INNER JOIN t_terceros t ON t.id = e.id_tercero";
    if ($filtro !== '') {
      $sql .= " WHERE e.tipo_entidad LIKE '%$filtro%' OR IFNULL(e.codigo_entidad,'') LIKE '%$filtro%' OR t.nit LIKE '%$filtro%'
                   OR IFNULL(t.razon_comercial,'') LIKE '%$filtro%' OR CONCAT_WS(' ', t.primer_nombre, t.primer_apellido) LIKE '%$filtro%'";
    }
    $sql .= " ORDER BY e.tipo_entidad, e.id DESC";
    responder(false, 'Success', nomina_select($sql));
    break;

  case 'entidades_agregar':
  case 'entidades_update':
    $id = intval($_POST['id'] ?? 0);
    $data = datosEntidad();
    $error = validarEntidad($data, $id);
    if ($error !== '') {
      responder(true, $error);
    }
    if ($op === 'entidades_agregar') {
      $data['usuario_creacion'] = intval($_SESSION['Ses_id'] ?? 0);
      $nuevo = insertReturnId('t_nomina_entidades', nomina_filtrar_columnas('t_nomina_entidades', $data), true);
      responder(!($nuevo > 0), $nuevo > 0 ? 'Entidad creada correctamente' : 'No se pudo crear la entidad. Si el tipo es SENA, ICBF o DIAN, ejecute el script de migración.', array('id' => $nuevo));
    }
    if ($id <= 0) {
      responder(true, 'Entidad no válida');
    }
    $ok = update('t_nomina_entidades', $data, $id);
    responder(!$ok, $ok ? 'Entidad actualizada correctamente' : 'No se pudo actualizar la entidad', $data);
    break;

  case 'entidades_delete':
    $id = intval($_POST['id'] ?? 0);
    $cols = 'c.id_entidad_salud, c.id_entidad_pension, c.id_entidad_cesantias, c.id_entidad_caja'
      . (nomina_tiene_columna('t_nomina_contratos', 'id_entidad_arl') ? ', c.id_entidad_arl' : '');
    $uso = nomina_select("SELECT COUNT(*) AS n FROM t_nomina_contratos c WHERE $id IN ($cols)");
    if (intval($uso[0]['n'] ?? 0) > 0) {
      responder(true, 'No se puede eliminar: la entidad está asignada a ' . $uso[0]['n'] . ' contrato(s). Puede inactivarla.');
    }
    $ok = Query("DELETE FROM t_nomina_entidades WHERE id = $id");
    responder(!$ok, $ok ? 'Entidad eliminada correctamente' : 'No se pudo eliminar la entidad');
    break;

  default:
    responder(true, 'Operación no válida');
}
