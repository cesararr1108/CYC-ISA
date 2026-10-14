<?php

/**
 * Utilidades compartidas del módulo de nómina.
 * Centraliza períodos, días comerciales, metadatos de conceptos y respuesta JSON
 * para no duplicar lógica entre nomina.php, nominaNovedades.php, contabilización y colillas.
 */

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once('bd.php');
require_once('funciones.php');

if (!isset($conn) || !($conn instanceof mysqli)) {
  $conn = conectar();
}

if (!function_exists('responder')) {
  function responder($error, $message, $data = null)
  {
    if (!headers_sent()) {
      header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(array(
      'error'   => $error,
      'message' => $message,
      'data'    => $data
    ));
    exit;
  }
}

/** Conexión mysqli compartida */
function nomina_conn()
{
  global $conn;
  if (!($conn instanceof mysqli)) {
    $conn = conectar();
  }
  return $conn;
}

/** Escapa un valor para SQL usando la conexión activa */
function nomina_esc($valor)
{
  return nomina_conn()->real_escape_string((string)$valor);
}

/** Ejecuta un SELECT y devuelve todas las filas (array asociativo) */
function nomina_select($sql)
{
  $res = nomina_conn()->query($sql);
  if (!$res) {
    return array();
  }
  $rows = $res->fetch_all(MYSQLI_ASSOC);
  $res->free();
  return $rows;
}

/** Columnas de una tabla (con caché por request) */
function nomina_columnas($tabla)
{
  static $cache = array();
  if (!isset($cache[$tabla])) {
    $cache[$tabla] = array();
    foreach (nomina_select("SHOW COLUMNS FROM `" . str_replace('`', '', $tabla) . "`") as $row) {
      $cache[$tabla][] = $row['Field'];
    }
  }
  return $cache[$tabla];
}

function nomina_tiene_columna($tabla, $columna)
{
  return in_array($columna, nomina_columnas($tabla), true);
}

/** Deja solo las llaves que existen como columnas en la tabla */
function nomina_filtrar_columnas($tabla, array $data)
{
  $cols = nomina_columnas($tabla);
  if (!$cols) {
    return $data;
  }
  return array_intersect_key($data, array_flip($cols));
}

function nomina_meses()
{
  return array(
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
    7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
  );
}

/** 1 = Quincenal, 2 = Mensual */
function nomina_frecuencia_empresa()
{
  static $frecuencia = null;
  if ($frecuencia === null) {
    $row = nomina_select("SELECT IFNULL(id_frecuencia_pago, 2) AS f FROM t_empresa LIMIT 1");
    $frecuencia = intval($row[0]['f'] ?? 2) === 1 ? 1 : 2;
  }
  return $frecuencia;
}

function nomina_generar_periodos($anio, $frecuenciaPago = 2)
{
  $anio = intval($anio);
  $meses = nomina_meses();
  $periodos = array();

  for ($mes = 1; $mes <= 12; $mes++) {
    $ultimo = date('t', mktime(0, 0, 0, $mes, 1, $anio));
    $m = str_pad($mes, 2, '0', STR_PAD_LEFT);

    if (intval($frecuenciaPago) === 1) {
      $periodos[] = array(
        'id' => "$anio-$m-01", 'label' => $meses[$mes] . ' 1-15',
        'fecha_inicio' => "$anio-$m-01", 'fecha_fin' => "$anio-$m-15", 'frecuencia' => 'QUINCENAL'
      );
      $periodos[] = array(
        'id' => "$anio-$m-16", 'label' => $meses[$mes] . ' 16-' . $ultimo,
        'fecha_inicio' => "$anio-$m-16", 'fecha_fin' => "$anio-$m-$ultimo", 'frecuencia' => 'QUINCENAL'
      );
    } else {
      $periodos[] = array(
        'id' => "$anio-$m", 'label' => $meses[$mes] . ' 1-' . $ultimo,
        'fecha_inicio' => "$anio-$m-01", 'fecha_fin' => "$anio-$m-$ultimo", 'frecuencia' => 'MENSUAL'
      );
    }
  }
  return $periodos;
}

/**
 * Convierte el id de período que envía la vista ('YYYY-MM', 'YYYY-MM-01', 'YYYY-MM-16')
 * en un arreglo con fechas reales. Antes ambas quincenas se guardaban bajo 'YYYY-MM' y
 * la segunda sobrescribía la primera; ahora cada liquidación se identifica por fecha de inicio.
 */
function nomina_resolver_periodo($periodoId, $frecuencia = null)
{
  $periodoId = trim((string)$periodoId);
  if (!preg_match('/^(\d{4})-(\d{2})(?:-(\d{2}))?$/', $periodoId, $m)) {
    return null;
  }
  $anio = intval($m[1]);
  $mes = intval($m[2]);
  if ($mes < 1 || $mes > 12) {
    return null;
  }
  $frecuencia = $frecuencia ?: nomina_frecuencia_empresa();
  $dia = isset($m[3]) ? intval($m[3]) : 0;
  $ultimo = intval(date('t', mktime(0, 0, 0, $mes, 1, $anio)));
  $mm = str_pad($mes, 2, '0', STR_PAD_LEFT);
  $meses = nomina_meses();

  if ($frecuencia === 1 || $dia === 16) {
    $quincena = $dia >= 16 ? 2 : 1;
    $ini = $quincena === 1 ? "$anio-$mm-01" : "$anio-$mm-16";
    $fin = $quincena === 1 ? "$anio-$mm-15" : "$anio-$mm-$ultimo";
    return array(
      'id' => $ini, 'periodo' => "$anio-$mm", 'anio' => $anio, 'mes' => $mes,
      'fecha_inicio' => $ini, 'fecha_fin' => $fin, 'frecuencia' => 'QUINCENAL',
      'quincena' => $quincena, 'dias_periodo' => 15,
      'label' => $meses[$mes] . ($quincena === 1 ? ' 1-15' : " 16-$ultimo") . " $anio"
    );
  }

  return array(
    'id' => "$anio-$mm", 'periodo' => "$anio-$mm", 'anio' => $anio, 'mes' => $mes,
    'fecha_inicio' => "$anio-$mm-01", 'fecha_fin' => "$anio-$mm-$ultimo", 'frecuencia' => 'MENSUAL',
    'quincena' => 0, 'dias_periodo' => 30,
    'label' => $meses[$mes] . " 1-$ultimo $anio"
  );
}

/**
 * Días comerciales (mes de 30 días, art. 134 CST) entre dos fechas, ambas inclusive.
 * Febrero completo = 30 días; segunda quincena de un mes de 31 días = 15 días.
 */
function nomina_dias_comerciales($desde, $hasta)
{
  $d1 = strtotime($desde);
  $d2 = strtotime($hasta);
  if (!$d1 || !$d2 || $d2 < $d1) {
    return 0;
  }
  $y1 = (int)date('Y', $d1); $m1 = (int)date('n', $d1); $dd1 = min(30, (int)date('j', $d1));
  $y2 = (int)date('Y', $d2); $m2 = (int)date('n', $d2); $dd2 = (int)date('j', $d2);
  if ($dd2 >= 30 || $dd2 === (int)date('t', $d2)) {
    $dd2 = 30;
  }
  return max(0, ($y2 - $y1) * 360 + ($m2 - $m1) * 30 + ($dd2 - $dd1) + 1);
}

/** Días del período en que el contrato estuvo vigente */
function nomina_dias_contrato_en_periodo(array $contrato, array $periodo)
{
  $ini = $periodo['fecha_inicio'];
  $fin = $periodo['fecha_fin'];
  if (!empty($contrato['fecha_inicio']) && $contrato['fecha_inicio'] > $ini) {
    $ini = substr($contrato['fecha_inicio'], 0, 10);
  }
  if (!empty($contrato['fecha_fin']) && $contrato['fecha_fin'] !== '0000-00-00' && $contrato['fecha_fin'] < $fin) {
    $fin = substr($contrato['fecha_fin'], 0, 10);
  }
  if ($fin < $ini) {
    return 0;
  }
  // Si el período completo está cubierto se respetan los 15/30 días comerciales.
  if ($ini === $periodo['fecha_inicio'] && $fin === $periodo['fecha_fin']) {
    return $periodo['dias_periodo'];
  }
  return min($periodo['dias_periodo'], nomina_dias_comerciales($ini, $fin));
}

function nomina_nombre($row, $prefijo = '')
{
  $partes = array();
  foreach (array('primer_nombre', 'segundo_nombre', 'primer_apellido', 'segundo_apellido') as $c) {
    $v = trim((string)($row[$prefijo . $c] ?? ''));
    if ($v !== '') {
      $partes[] = $v;
    }
  }
  return implode(' ', $partes);
}

/** Expresión SQL de nombre de tercero (razón comercial o nombres) */
function nomina_sql_nombre_tercero($alias)
{
  return "CASE WHEN TRIM(IFNULL($alias.razon_comercial,'')) <> '' THEN $alias.razon_comercial
               ELSE TRIM(CONCAT_WS(' ', NULLIF($alias.primer_nombre,''), NULLIF($alias.segundo_nombre,''),
                                       NULLIF($alias.primer_apellido,''), NULLIF($alias.segundo_apellido,''))) END";
}

/* ---------------------------------------------------------------------------
 * Conceptos: clasificación para IBC, prestaciones y contabilización
 * ------------------------------------------------------------------------- */

/** Metadatos de conceptos por id (y por clave como respaldo) */
function nomina_conceptos_meta()
{
  static $cache = null;
  if ($cache === null) {
    $cache = array('id' => array(), 'clave' => array());
    foreach (nomina_select("SELECT * FROM t_nomina_conceptos") as $row) {
      $cache['id'][intval($row['id'])] = $row;
      $cache['clave'][strtoupper(trim((string)$row['clave']))] = $row;
    }
  }
  return $cache;
}

function nomina_concepto($idConcepto, $clave = '')
{
  $meta = nomina_conceptos_meta();
  $idConcepto = intval($idConcepto);
  if ($idConcepto > 0 && isset($meta['id'][$idConcepto])) {
    return $meta['id'][$idConcepto];
  }
  $clave = strtoupper(trim((string)$clave));
  return $meta['clave'][$clave] ?? null;
}

/** Valor S/N/1/0 de una columna opcional; null si no está definida */
function nomina_flag($row, $campo)
{
  if (!is_array($row) || !array_key_exists($campo, $row) || $row[$campo] === null || $row[$campo] === '') {
    return null;
  }
  $v = strtoupper(trim((string)$row[$campo]));
  return in_array($v, array('S', 'SI', '1', 'Y', 'TRUE'), true);
}

/**
 * Clasifica un devengado:
 *  - SALARIAL     : hace parte del IBC y de la base de prestaciones (horas extra, recargos, comisiones…)
 *  - AUSENCIA     : incapacidades, licencias y vacaciones; descuentan días de salario y cotizan
 *  - PRESTACION   : pago de prima, cesantías o intereses; no es base de aportes
 *  - NO_SALARIAL  : auxilios/bonificaciones no constitutivas (art. 128 CST), sujetas a la regla del 40 %
 * Si la tabla de conceptos tiene las columnas es_salarial / afecta_dias se respetan;
 * si no, se infiere por subtipo y clave.
 */
function nomina_clasificar_devengo($concepto)
{
  $subtipo = strtoupper(trim((string)($concepto['subtipo'] ?? '')));
  $clave = strtoupper(trim((string)($concepto['clave'] ?? '')));
  $nombre = strtoupper(trim((string)($concepto['nombre'] ?? '')));
  $texto = "$subtipo $clave $nombre";

  $esPrestacion = preg_match('/PRESTACION|PRIMA|CESANT/', $texto);
  $esAusencia = preg_match('/INCAPACIDAD|LICENCIA|VACACION|SUSPENSION|AUSENCIA/', $texto);
  $esIncapacidad = (bool)preg_match('/INCAPACIDAD/', $texto);
  $esNoSalarial = preg_match('/NO_SALARIAL|NO SALARIAL|NOSALARIAL|AUXILIO|DOTACION|VIATICO|INDEMNIZ|APOYO|TERCERO|ANTICIPO|REINTEGRO|TELETRABAJO/', $texto);

  $esTransporte = (bool)preg_match('/AUXILIO_TRANSPORTE|AUX_TRANSPORTE|AUXILIO DE TRANSPORTE/', $texto);
  $esVacaciones = (bool)preg_match('/VACACION/', $texto);
  $flagDias = nomina_flag($concepto, 'afecta_dias');

  if ($esTransporte) {
    $clase = 'TRANSPORTE'; // no es base de aportes, sí de prima y cesantías
  } elseif ($esVacaciones) {
    // Disfrutadas (descuentan días) = ausencia remunerada; compensadas en dinero = pago de prestación
    $clase = $flagDias === false ? 'PRESTACION' : 'AUSENCIA';
  } elseif ($esPrestacion) {
    $clase = 'PRESTACION';
  } elseif ($esAusencia) {
    $clase = 'AUSENCIA';
  } elseif ($esNoSalarial) {
    $clase = 'NO_SALARIAL';
  } else {
    $clase = 'SALARIAL'; // art. 127 CST: todo pago que remunera el servicio es salario salvo pacto en contrario
  }

  $flagSalarial = nomina_flag($concepto, 'es_salarial');
  if ($flagSalarial !== null && !in_array($clase, array('AUSENCIA', 'PRESTACION', 'TRANSPORTE'), true)) {
    $clase = $flagSalarial ? 'SALARIAL' : 'NO_SALARIAL';
  }

  $afectaDias = $flagDias;
  if ($afectaDias === null) {
    $afectaDias = ($clase === 'AUSENCIA');
  }

  return array(
    'clase'        => $clase,
    'afecta_dias'  => $afectaDias,
    'incapacidad'  => $esIncapacidad,
    'vacaciones'   => $esVacaciones,
    'hora_extra'   => (bool)preg_match('/EXTRA|RECARGO|HED|HEN|HRN|HDD|HND/', $texto),
    'cuenta'       => trim((string)($concepto['cuenta_contable'] ?? '')),
  );
}

/** Nombre legible de un concepto del detalle de nómina */
function nomina_etiqueta_concepto($concepto, $idConcepto = 0)
{
  $sistema = array(
    'salario_basico'          => 'Salario básico',
    'aux_transporte'          => 'Auxilio de transporte',
    'aporte_salud'            => 'Aporte salud',
    'aporte_pension'          => 'Aporte pensión',
    'aporte_fsp_solidaridad'  => 'Fondo de solidaridad pensional',
    'aporte_fsp_subsistencia' => 'Fondo de subsistencia',
    'retencion_fuente'        => 'Retención en la fuente',
  );
  $k = strtolower(trim((string)$concepto));
  if (isset($sistema[$k])) {
    return $sistema[$k];
  }
  $meta = nomina_concepto($idConcepto, $concepto);
  return trim((string)($meta['nombre'] ?? '')) ?: (string)$concepto;
}

/** Formato de moneda para textos */
function nomina_money($v)
{
  return '$' . number_format((float)$v, 0, ',', '.');
}
