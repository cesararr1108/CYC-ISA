<?php
date_default_timezone_set('America/Bogota');

require 'config.php';

/*
 * APP_DEBUG: si config.php no lo define se conserva el comportamiento actual (errores visibles).
 * En producción agregue en config.php:  define('APP_DEBUG', false);
 * Así los errores van al log de PHP y no rompen las respuestas JSON.
 *
 * DB_PERSISTENTE (opcional): define('DB_PERSISTENTE', true); reutiliza conexiones entre peticiones.
 */
if (!defined('APP_DEBUG')) {
  define('APP_DEBUG', true);
}
error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? 1 : 0);
ini_set('log_errors', 1);

/* =====================================================================
 * CONEXIONES
 * ===================================================================== */
function crearConexion($dbName)
{
  $host = (defined('DB_PERSISTENTE') && DB_PERSISTENTE) ? 'p:' . SERVER : SERVER;
  $conn = new mysqli($host, USER, PASS, $dbName);

  if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
  }
  $conn->set_charset("utf8");
  return $conn;
}

/** Conexión NUEVA (igual que antes). Se conserva para el código que la cierra o maneja transacciones propias. */
function conectar()
{
  return crearConexion(DB);
}

function conectarCore()
{
  return crearConexion(DB_CORE);
}

function conectarFel()
{
  return crearConexion(DB_FEL);
}

/**
 * Conexión COMPARTIDA por petición.
 * Antes cada GenerarArray / Query / insertReturnId / update abría su propia conexión
 * (y Query, insertReturnId y update nunca la cerraban). Ahora todas reutilizan esta.
 */
function db()
{
  static $conn = null;
  if ($conn === null) {
    $conn = crearConexion(DB);
  }
  return $conn;
}

/** Usa la conexión recibida o la compartida ('' o null => compartida) */
function resolverConexion($conn)
{
  return ($conn instanceof mysqli) ? $conn : db();
}

/** Libera resultados extra (p. ej. de un CALL) para que la conexión compartida quede lista */
function drenarResultados(mysqli $conn)
{
  while ($conn->more_results() && $conn->next_result()) {
    if ($r = $conn->store_result()) {
      $r->free();
    }
  }
}

/** Los errores siempre van al log; solo se imprimen en modo depuración (antes se imprimían y rompían el JSON) */
function reportarError($mensaje)
{
  error_log('[BD] ' . $mensaje);
  if (APP_DEBUG) {
    echo $mensaje;
  }
}

/** Ejecuta una función dentro de una transacción sobre la conexión compartida */
function transaccion(callable $fn)
{
  $conn = db();
  $conn->begin_transaction();
  try {
    $resultado = $fn($conn);
    $conn->commit();
    return $resultado;
  } catch (\Throwable $e) {
    $conn->rollback();
    throw $e;
  }
}

/* =====================================================================
 * SEGURIDAD DE CAMPOS
 * insertReturnId y update recibían $_POST completo y usaban las claves como
 * nombres de columna (inyección SQL por el nombre del campo). Ahora solo se
 * aceptan claves que existan como columnas reales de la tabla.
 * ===================================================================== */
function columnasTabla(mysqli $conn, $tabla)
{
  static $cache = [];
  if (!preg_match('/^[A-Za-z0-9_.]+$/', $tabla)) {
    return [];
  }
  if (!isset($cache[$tabla])) {
    $cache[$tabla] = [];
    $res = $conn->query("SHOW COLUMNS FROM $tabla");
    if ($res) {
      while ($c = $res->fetch_assoc()) {
        $cache[$tabla][strtolower($c['Field'])] = true;
      }
      $res->free();
    }
  }
  return $cache[$tabla];
}

function filtrarCampos(mysqli $conn, $tabla, array $fields)
{
  $columnas = columnasTabla($conn, $tabla);
  $limpios = [];
  foreach ($fields as $campo => $valor) {
    if (!is_string($campo) || !preg_match('/^[A-Za-z0-9_]+$/', $campo)) {
      continue;
    }
    if ($columnas && !isset($columnas[strtolower($campo)])) {
      continue;
    }
    if (is_array($valor) || is_object($valor)) {
      continue;
    }
    $limpios[$campo] = $valor;
  }
  return $limpios;
}

function tipoParametro($valor)
{
  if (is_int($valor)) return 'i';
  if (is_float($valor)) return 'd';
  return 's';
}

/* =====================================================================
 * CONSULTAS
 * ===================================================================== */
function Query($str, $conn = null)
{
  $conn = resolverConexion($conn);
  $result = $conn->query($str);

  if (!$result) {
    reportarError("Error al ejecutar la consulta: " . $conn->error);
    return false;
  }
  drenarResultados($conn);
  return $result;
}

function GenerarArray($str, $conn = null)
{
  $conn = resolverConexion($conn);
  $result = $conn->query($str);

  if (!$result) {
    reportarError("Error al ejecutar la consulta: " . $conn->error);
    return false;
  }
  if ($result === true) {   // la sentencia no devuelve filas (INSERT/UPDATE/...)
    drenarResultados($conn);
    return [];
  }

  $matriz = $result->fetch_all(MYSQLI_ASSOC);   // más rápido que fetch_assoc fila a fila
  $result->free();
  drenarResultados($conn);
  return $matriz;
}

function GenerarArrayPrepare($sql, $parametros = array(), $conn = null)
{
  $conn = resolverConexion($conn);
  $stmt = $conn->prepare($sql);

  if (!$stmt) {
    die("Error en prepare: " . $conn->error);
  }

  if (count($parametros) > 0) {
    $tipos = '';
    $valores = array();
    foreach ($parametros as $parametro) {
      $tipos .= $parametro['tipo'];
      $valores[] = $parametro['valor'];
    }
    $stmt->bind_param($tipos, ...$valores);
  }

  if (!$stmt->execute()) {
    die("Error ejecutando consulta: " . $stmt->error);
  }

  $resultado = $stmt->get_result();
  $datos = $resultado ? $resultado->fetch_all(MYSQLI_ASSOC) : [];
  $stmt->close();
  return $datos;
}

function ejecutarPrepared($conn, $sql, $params = [], $types = "")
{
  $conn = resolverConexion($conn);
  $stmt = $conn->prepare($sql);

  if (!$stmt) {
    die("Error al preparar: " . $conn->error);
  }
  if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
  }
  $stmt->execute();
  $result = $stmt->get_result();
  $data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
  $stmt->close();
  return $data;
}

/* =====================================================================
 * ESCRITURA
 * ===================================================================== */
function insertReturnId($table, $fields, $deleteId = true, $conn = null, $auto = true)
{
  try {
    $conn = resolverConexion($conn);

    if ($deleteId) {
      unset($fields['id']);
    }
    unset($fields['op'], $fields['link']);

    $fields = filtrarCampos($conn, $table, $fields);
    if (empty($fields)) {
      throw new Exception("No hay campos válidos para insertar en $table.");
    }

    $insertQuery = "INSERT INTO $table (" . implode(', ', array_keys($fields)) . ") VALUES (" .
      implode(', ', array_fill(0, count($fields), '?')) . ")";

    $stmt = $conn->prepare($insertQuery);
    if ($stmt === false) {
      throw new Exception("Error al preparar la consulta de inserción: " . $conn->error);
    }

    $params = array_values($fields);
    $types = implode('', array_map('tipoParametro', $params));
    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) {
      throw new Exception("La consulta de inserción falló: " . $stmt->error);
    }

    $lastInsertId = $conn->insert_id;
    $stmt->close();
    return $lastInsertId;
  } catch (\Throwable $e) {
    reportarError("Error: " . $e->getMessage());
    return false;
  }
}

function update($tabla, $datos, $condicion, $conn = null)
{
  try {
    $conn = resolverConexion($conn);

    unset($datos['id'], $datos['op'], $datos['link']);
    $datos = filtrarCampos($conn, $tabla, $datos);

    if (empty($datos)) {
      throw new Exception("No hay datos válidos para actualizar en $tabla.");
    }

    $columnas = [];
    $valores = [];
    foreach ($datos as $campo => $valor) {
      $columnas[] = "$campo = ?";
      $valores[] = $valor;
    }
    $valores[] = $condicion;
    $tipos = implode('', array_map('tipoParametro', $valores));

    $stmt = $conn->prepare("UPDATE $tabla SET " . implode(', ', $columnas) . " WHERE id = ?");
    if ($stmt === false) {
      throw new Exception("Error al preparar la consulta: " . $conn->error);
    }
    $stmt->bind_param($tipos, ...$valores);

    if (!$stmt->execute()) {
      throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
    }
    $stmt->close();
    return true;
  } catch (\Throwable $e) {
    reportarError("Error: " . $e->getMessage());
    return false;
  }
}

function delete($tabla, $condicion, $conn = null)
{
  try {
    $conn = resolverConexion($conn);

    if (!is_array($condicion) || count($condicion) == 0) {
      throw new Exception("Debe enviar al menos una condición para eliminar.");
    }

    $whereColumns = [];
    $whereValues = [];
    foreach ($condicion as $campo => $valor) {
      if (!preg_match('/^[A-Za-z0-9_]+$/', $campo)) {
        throw new Exception("Campo de condición no válido: $campo");
      }
      $whereColumns[] = "$campo = ?";
      $whereValues[] = $valor;
    }

    $stmt = $conn->prepare("DELETE FROM $tabla WHERE " . implode(" AND ", $whereColumns));
    if ($stmt === false) {
      throw new Exception("Error al preparar la consulta: " . $conn->error);
    }

    $types = implode('', array_map('tipoParametro', $whereValues));
    $stmt->bind_param($types, ...$whereValues);

    if (!$stmt->execute()) {
      throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
    }
    $stmt->close();
    return true;
  } catch (\Throwable $e) {
    reportarError("Error: " . $e->getMessage());
    return false;
  }
}