<?php
/**
 * ============================================================================
 *  REPLICACIÓN DE ESTRUCTURA  CYC (principal / pruebas)  →  BASES DE PRODUCCIÓN
 * ============================================================================
 *
 *  QUÉ HACE (sin tocar datos):
 *   1. Tablas faltantes     → CREATE TABLE (solo estructura, sin filas y sin el AUTO_INCREMENT de CYC).
 *   2. Columnas faltantes   → ALTER TABLE ... ADD COLUMN, en la misma posición que en CYC.
 *   3. Columnas diferentes  → MODIFY solo si es un cambio SEGURO:
 *                               varchar(50)→varchar(100), int→bigint, decimal(10,2)→decimal(14,2),
 *                               text→mediumtext, enum con valores nuevos, NOT NULL→NULL, cambio de DEFAULT.
 *                             Cualquier otro cambio (reducir, cambiar tipo, NULL→NOT NULL, collation,
 *                             auto_increment) se REPORTA con el SQL sugerido, pero NO se ejecuta.
 *   4. Índices faltantes    → se crean solo los NO únicos. PRIMARY y UNIQUE se reportan.
 *   5. Funciones, vistas y procedimientos → solo se recrean si su definición cambió.
 *        - Vistas: CREATE OR REPLACE (si falla, la vista anterior queda intacta).
 *        - Rutinas: si la nueva versión falla, se restaura automáticamente la anterior.
 *
 *  QUÉ NUNCA HACE: DROP TABLE, DROP COLUMN, TRUNCATE, DELETE, UPDATE, INSERT, reducir tipos o
 *  longitudes, ni cambiar charset/collation. Lo que existe solo en producción se informa y se deja.
 *
 *  USO:
 *   - Simulación (por defecto, no cambia nada):  services-replication-database.php
 *   - Aplicar cambios:                            services-replication-database.php?aplicar=1
 *   - Una sola base:                              services-replication-database.php?db=901128361[&aplicar=1]
 *   - Consola:                                    php services-replication-database.php --aplicar --db=901128361
 * ============================================================================
 */

session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);
ini_set('memory_limit', '512M');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once('../../models/config.php');
require_once('../../models/funciones.php');

$inicio = microtime(true);
$esCli  = (PHP_SAPI === 'cli');

// Recomendado: restringir el acceso a este script. Ajuste la condición a la sesión de su sistema.
// if (!$esCli && empty($_SESSION['id_usuario'])) { http_response_code(403); exit('Acceso denegado'); }

// ============================================================================
// CONFIGURACIÓN
// ============================================================================
$host = SERVER;
$user = USER;
$pass = PASS;
$db_principal = 'CYC';
$db_destinos = [
    '901128361',
    '901978386',
    '1005053275',
    '60449183',
    '901919770',
    '901978386',
    '890932574',
    '901003294',
    '1233341108',
    '1064999750',
    '1118806271',
    '1067904527',
    '1000899781',
    '902098856',
    '901858154'
];

$OPC = [
    'crear_tablas'            => true,
    'agregar_columnas'        => true,
    'ampliar_columnas'        => true,  // solo cambios seguros (ver encabezado)
    'crear_indices'           => true,  // solo índices NO únicos
    'replicar_funciones'      => true,
    'replicar_vistas'         => true,
    'replicar_procedimientos' => true,
    'reportar_sobrantes'      => true,  // informa tablas/columnas que existen solo en producción
    // En vistas siempre se cambia `CYC`. por la base destino (igual que antes).
    // En funciones/procedimientos se deja como estaba en su script original (false).
    'reemplazar_esquema_en_rutinas' => false,
];

// Objetos de CYC que NO deben llegar a producción (pruebas, temporales, respaldos...)
$EXCLUIR = [
    'tablas'         => [],
    'vistas'         => [],
    'procedimientos' => [],
    'funciones'      => [],
];

// ---- Parámetros de ejecución ----
$args     = $esCli ? ($argv ?? []) : [];
$aplicar  = $esCli ? in_array('--aplicar', $args, true) : (($_GET['aplicar'] ?? '') === '1');
$dbFiltro = null;
if ($esCli) {
    foreach ($args as $a) {
        if (strpos($a, '--db=') === 0) $dbFiltro = substr($a, 5);
    }
} elseif (!empty($_GET['db'])) {
    $dbFiltro = (string)$_GET['db'];
}

define('SIMULAR', !$aplicar);

if ($dbFiltro !== null) {
    if (!in_array($dbFiltro, $db_destinos, true)) {
        exit('La base indicada no está en la lista de destinos.');
    }
    $db_destinos = [$dbFiltro];
}

const ENTEROS = ['tinyint' => 1, 'smallint' => 2, 'mediumint' => 3, 'int' => 4, 'integer' => 4, 'bigint' => 5];

// ============================================================================
// UTILIDADES
// ============================================================================
$LOG = [];

function registrar(string $db, string $tipo, string $objeto, string $accion, string $estado, string $detalle = '', string $sql = ''): void
{
    global $LOG;
    $LOG[] = compact('db', 'tipo', 'objeto', 'accion', 'estado', 'detalle', 'sql');
}

function q(string $nombre): string
{
    return '`' . str_replace('`', '``', $nombre) . '`';
}

/** Ejecuta solo si NO estamos en simulación. */
function ejecutar(mysqli $m, string $sql): void
{
    if (!SIMULAR) $m->query($sql);
}

function estadoAplicado(): string
{
    return SIMULAR ? 'SIMULADO' : 'OK';
}

function consultar(mysqli $m, string $sql, array $params = []): array
{
    $st = $m->prepare($sql);
    if ($params) $st->bind_param(str_repeat('s', count($params)), ...$params);
    $st->execute();
    $filas = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
    return $filas;
}

function quitarDefiner(string $sql): string
{
    return preg_replace('/\s*DEFINER\s*=\s*`[^`]*`@`[^`]*`/i', '', $sql);
}

function normalizarSql(string $sql): string
{
    return preg_replace('/\s+/', ' ', trim($sql));
}

function existeBase(mysqli $m, string $db): bool
{
    return (bool)consultar($m, 'SELECT 1 FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?', [$db]);
}

// ============================================================================
// LECTURA DE ESTRUCTURA
// ============================================================================
function leerEsquema(mysqli $m, string $db): array
{
    $esq = ['tablas' => [], 'columnas' => [], 'indices' => []];

    foreach (consultar($m, "SELECT TABLE_NAME AS tabla FROM information_schema.TABLES
                            WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'", [$db]) as $f) {
        $esq['tablas'][$f['tabla']] = true;
    }

    $sqlCols = "SELECT c.TABLE_NAME AS tabla, c.COLUMN_NAME AS columna, c.COLUMN_TYPE AS tipo,
                       c.IS_NULLABLE AS nulo, c.COLUMN_DEFAULT AS defecto, c.EXTRA AS extra,
                       c.COLLATION_NAME AS collation
                FROM information_schema.COLUMNS c
                JOIN information_schema.TABLES t
                  ON t.TABLE_SCHEMA = c.TABLE_SCHEMA AND t.TABLE_NAME = c.TABLE_NAME AND t.TABLE_TYPE = 'BASE TABLE'
                WHERE c.TABLE_SCHEMA = ?
                ORDER BY c.TABLE_NAME, c.ORDINAL_POSITION";
    foreach (consultar($m, $sqlCols, [$db]) as $f) {
        $esq['columnas'][$f['tabla']][$f['columna']] = $f;
    }

    $sqlIdx = "SELECT TABLE_NAME AS tabla, INDEX_NAME AS indice, NON_UNIQUE AS no_unico,
                      COLUMN_NAME AS columna, SUB_PART AS sub, INDEX_TYPE AS tipo
               FROM information_schema.STATISTICS
               WHERE TABLE_SCHEMA = ?
               ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX";
    foreach (consultar($m, $sqlIdx, [$db]) as $f) {
        $esq['indices'][$f['tabla']][$f['indice']] ??= [
            'unico' => ((int)$f['no_unico'] === 0),
            'tipo'  => strtoupper((string)$f['tipo']),
            'cols'  => [],
        ];
        $esq['indices'][$f['tabla']][$f['indice']]['cols'][] = ['col' => $f['columna'], 'sub' => $f['sub']];
    }

    return $esq;
}

/** Definición exacta de cada columna tomada de SHOW CREATE TABLE (sirve para ADD/MODIFY). */
function definicionesColumnas(mysqli $m, string $db, string $tabla): array
{
    $sql  = $m->query('SHOW CREATE TABLE ' . q($db) . '.' . q($tabla))->fetch_assoc()['Create Table'];
    $defs = [];
    foreach (explode("\n", $sql) as $linea) {
        if (preg_match('/^\s*`((?:[^`]|``)+)`\s+(.*?),?\s*$/', $linea, $mm)) {
            $defs[str_replace('``', '`', $mm[1])] = $mm[2];
        }
    }
    return $defs;
}

function leerVistas(mysqli $m, string $db): array
{
    $vistas = [];
    foreach (consultar($m, 'SELECT TABLE_NAME AS nombre FROM information_schema.VIEWS WHERE TABLE_SCHEMA = ?', [$db]) as $f) {
        try {
            $fila = $m->query('SHOW CREATE VIEW ' . q($db) . '.' . q($f['nombre']))->fetch_assoc();
            $vistas[$f['nombre']] = quitarDefiner($fila['Create View']);
        } catch (\Throwable $e) {
            registrar($db, 'VISTA', $f['nombre'], 'Leer definición', 'ADVERTENCIA', $e->getMessage());
        }
    }
    return $vistas;
}

/** $tipo: PROCEDURE | FUNCTION */
function leerRutinas(mysqli $m, string $db, string $tipo): array
{
    $rutinas = [];
    $columna = $tipo === 'PROCEDURE' ? 'Create Procedure' : 'Create Function';
    $filas   = consultar($m, 'SELECT ROUTINE_NAME AS nombre FROM information_schema.ROUTINES
                              WHERE ROUTINE_SCHEMA = ? AND ROUTINE_TYPE = ?', [$db, $tipo]);
    foreach ($filas as $f) {
        try {
            $fila = $m->query("SHOW CREATE $tipo " . q($db) . '.' . q($f['nombre']))->fetch_assoc();
            if (empty($fila[$columna])) {
                registrar($db, $tipo, $f['nombre'], 'Leer definición', 'ADVERTENCIA', 'El usuario no tiene permiso para ver el cuerpo de la rutina.');
                continue;
            }
            $rutinas[$f['nombre']] = ['sql' => quitarDefiner($fila[$columna]), 'sql_mode' => (string)($fila['sql_mode'] ?? '')];
        } catch (\Throwable $e) {
            registrar($db, $tipo, $f['nombre'], 'Leer definición', 'ADVERTENCIA', $e->getMessage());
        }
    }
    return $rutinas;
}

// ============================================================================
// COMPARACIÓN DE TIPOS
// ============================================================================
function analizarTipo(string $tipo): array
{
    $tipo = strtolower(trim($tipo));
    preg_match('/^([a-z]+)\s*(?:\((.*)\))?\s*(.*)$/s', $tipo, $m);
    $base = $m[1] ?? $tipo;
    return [
        'base'     => $base === 'integer' ? 'int' : $base,
        'params'   => $m[2] ?? '',
        'resto'    => trim($m[3] ?? ''),
        'unsigned' => strpos($m[3] ?? '', 'unsigned') !== false,
    ];
}

/** Ignora el ancho de visualización de los enteros (int(11) = int), que no afecta los datos. */
function normalizarTipo(string $tipo): string
{
    $t = analizarTipo($tipo);
    if (isset(ENTEROS[$t['base']])) $t['params'] = '';
    return trim($t['base'] . ($t['params'] !== '' ? "({$t['params']})" : '') . ' ' . $t['resto']);
}

/** true si pasar la columna de $destino (producción) a $origen (CYC) no puede perder ni alterar datos. */
function esAmpliacionSegura(string $origen, string $destino): bool
{
    $o = analizarTipo($origen);
    $d = analizarTipo($destino);
    $ob = $o['base'];
    $db = $d['base'];

    // Enteros: mismo signo y igual o mayor capacidad
    if (isset(ENTEROS[$ob], ENTEROS[$db])) {
        return $o['unsigned'] === $d['unsigned'] && ENTEROS[$ob] >= ENTEROS[$db];
    }

    // Decimales: misma o mayor parte entera y misma o mayor parte decimal
    if (in_array($ob, ['decimal', 'numeric'], true) && in_array($db, ['decimal', 'numeric'], true)) {
        [$po, $so] = array_map('intval', array_pad(explode(',', $o['params'] ?: '10,0'), 2, 0));
        [$pd, $sd] = array_map('intval', array_pad(explode(',', $d['params'] ?: '10,0'), 2, 0));
        return $o['unsigned'] === $d['unsigned'] && $so >= $sd && ($po - $so) >= ($pd - $sd);
    }

    // float → double
    if ($db === 'float' && $ob === 'double') return $o['unsigned'] === $d['unsigned'];

    // char/varchar y binary/varbinary: igual o mayor longitud (varchar→char NO, cambia espacios finales)
    foreach ([['char', 'varchar'], ['binary', 'varbinary']] as [$fijo, $variable]) {
        if (in_array($ob, [$fijo, $variable], true) && in_array($db, [$fijo, $variable], true)) {
            if ($db === $variable && $ob === $fijo) return false;
            return (int)$o['params'] >= (int)$d['params'];
        }
    }

    // Familias text / blob: igual o mayor tamaño
    foreach ([['tinytext', 'text', 'mediumtext', 'longtext'], ['tinyblob', 'blob', 'mediumblob', 'longblob']] as $familia) {
        $io = array_search($ob, $familia, true);
        $id = array_search($db, $familia, true);
        if ($io !== false && $id !== false) return $io >= $id;
    }

    // enum / set: se permite si conserva todos los valores que ya existen en producción
    if ($ob === $db && in_array($ob, ['enum', 'set'], true)) {
        $vo = str_getcsv($o['params'], ',', "'");
        $vd = str_getcsv($d['params'], ',', "'");
        return !array_diff($vd, $vo);
    }

    // Fecha/hora: igual o mayor precisión fraccional
    if ($ob === $db && in_array($ob, ['datetime', 'timestamp', 'time'], true)) {
        return (int)$o['params'] >= (int)$d['params'];
    }

    return false;
}

function extraNormalizado(?string $extra): string
{
    return strtolower(trim(str_ireplace('DEFAULT_GENERATED', '', (string)$extra)));
}

// ============================================================================
// SINCRONIZACIÓN DE TABLAS, COLUMNAS E ÍNDICES
// ============================================================================
function crearTabla(mysqli $m, string $dbOri, string $dbDest, string $tabla): void
{
    $sql = $m->query('SHOW CREATE TABLE ' . q($dbOri) . '.' . q($tabla))->fetch_assoc()['Create Table'];
    $sql = preg_replace('/\sAUTO_INCREMENT=\d+/i', '', $sql);                 // no heredar el contador de CYC
    $sql = preg_replace('/^CREATE TABLE\s+/i', 'CREATE TABLE IF NOT EXISTS ', $sql, 1);
    $sql = str_ireplace(q($dbOri) . '.', q($dbDest) . '.', $sql);            // FKs a otras tablas de CYC

    try {
        if (!SIMULAR) {
            $m->query('SET FOREIGN_KEY_CHECKS = 0');  // solo para crear la estructura en cualquier orden
            $m->query($sql);
        }
        registrar($dbDest, 'TABLA', $tabla, 'Crear tabla (solo estructura)', estadoAplicado(), '', $sql);
    } catch (\Throwable $e) {
        registrar($dbDest, 'TABLA', $tabla, 'Crear tabla', 'ERROR', $e->getMessage(), $sql);
    } finally {
        if (!SIMULAR) $m->query('SET FOREIGN_KEY_CHECKS = 1');
    }
}

function sincronizarColumnas(mysqli $m, string $dbOri, string $dbDest, string $tabla, array $colsOri, array &$colsDest, array $OPC): void
{
    $defs     = null; // se cargan solo si hacen falta
    $anterior = null;
    $tablaSql = q($dbDest) . '.' . q($tabla);
    $fmt      = function ($v) { return $v === null ? 'NULL' : "'$v'"; };

    foreach ($colsOri as $col => $o) {

        // ---------- Columna faltante ----------
        if (!isset($colsDest[$col])) {
            if ($OPC['agregar_columnas']) {
                $defs = $defs ?? definicionesColumnas($m, $dbOri, $tabla);
                $def  = $defs[$col] ?? null;

                if ($def === null) {
                    registrar($dbDest, 'COLUMNA', "$tabla.$col", 'Agregar columna', 'ERROR', 'No se pudo leer la definición en CYC.');
                } elseif (stripos((string)$o['extra'], 'auto_increment') !== false) {
                    $sql = "ALTER TABLE $tablaSql ADD COLUMN " . q($col) . " $def";
                    registrar($dbDest, 'COLUMNA', "$tabla.$col", 'Agregar columna', 'ADVERTENCIA',
                        'Columna AUTO_INCREMENT: implica cambiar la llave primaria, revisar manualmente.', $sql);
                } else {
                    $pos = $anterior === null ? ' FIRST' : (isset($colsDest[$anterior]) ? ' AFTER ' . q($anterior) : '');
                    $sql = "ALTER TABLE $tablaSql ADD COLUMN " . q($col) . " $def$pos";
                    try {
                        ejecutar($m, $sql);
                        $colsDest[$col] = $o;
                        registrar($dbDest, 'COLUMNA', "$tabla.$col", 'Agregar columna', estadoAplicado(), $o['tipo'], $sql);
                    } catch (\Throwable $e) {
                        registrar($dbDest, 'COLUMNA', "$tabla.$col", 'Agregar columna', 'ERROR', $e->getMessage(), $sql);
                    }
                }
            }
            $anterior = $col;
            continue;
        }

        // ---------- Columna existente: comparar ----------
        $d = $colsDest[$col];
        $difs = [];

        $tipoIgual = normalizarTipo($o['tipo']) === normalizarTipo($d['tipo']);
        if (!$tipoIgual)                         $difs[] = "Tipo: {$d['tipo']} → {$o['tipo']}";
        if ($o['nulo'] !== $d['nulo'])           $difs[] = "Nulo: {$d['nulo']} → {$o['nulo']}";
        if ($o['defecto'] !== $d['defecto'])     $difs[] = 'Default: ' . $fmt($d['defecto']) . ' → ' . $fmt($o['defecto']);
        $extraO = extraNormalizado($o['extra']);
        $extraD = extraNormalizado($d['extra']);
        if ($extraO !== $extraD)                 $difs[] = "Extra: '$extraD' → '$extraO'";
        $collDif = $o['collation'] !== $d['collation'];
        if ($collDif)                            $difs[] = "Collation: {$d['collation']} → {$o['collation']}";

        if ($difs) {
            $defs = $defs ?? definicionesColumnas($m, $dbOri, $tabla);
            $sql  = "ALTER TABLE $tablaSql MODIFY COLUMN " . q($col) . ' ' . ($defs[$col] ?? $o['tipo']);

            $cambioEspecial = (strpos($extraO, 'auto_increment') !== false) !== (strpos($extraD, 'auto_increment') !== false)
                           || (strpos($extraO, 'generated') !== false) !== (strpos($extraD, 'generated') !== false);

            $segura = ($tipoIgual || esAmpliacionSegura($o['tipo'], $d['tipo']))
                   && ($o['nulo'] === $d['nulo'] || $o['nulo'] === 'YES')   // solo se permite pasar a NULL
                   && !$cambioEspecial
                   && !$collDif;

            if ($segura && $OPC['ampliar_columnas']) {
                try {
                    ejecutar($m, $sql);
                    registrar($dbDest, 'COLUMNA', "$tabla.$col", 'Ajustar columna (cambio seguro)', estadoAplicado(), implode(' | ', $difs), $sql);
                } catch (\Throwable $e) {
                    registrar($dbDest, 'COLUMNA', "$tabla.$col", 'Ajustar columna', 'ERROR', $e->getMessage(), $sql);
                }
            } else {
                registrar($dbDest, 'COLUMNA', "$tabla.$col", 'Revisión manual (podría afectar datos)', 'ADVERTENCIA', implode(' | ', $difs), $sql);
            }
        }
        $anterior = $col;
    }

    if ($OPC['reportar_sobrantes']) {
        foreach ($colsDest as $col => $_) {
            if (!isset($colsOri[$col])) {
                registrar($dbDest, 'COLUMNA', "$tabla.$col", 'Solo existe en producción', 'INFO', 'No se modifica ni se elimina.');
            }
        }
    }
}

function firmaIndice(array $i): string
{
    $cols = array_map(function ($c) { return $c['col'] . ($c['sub'] ? "({$c['sub']})" : ''); }, $i['cols']);
    return ($i['unico'] ? 'UNIQUE' : 'INDEX') . " {$i['tipo']} (" . implode(', ', $cols) . ')';
}

function sqlIndice(string $tablaSql, string $nombre, array $i): string
{
    $cols = implode(', ', array_map(function ($c) { return q((string)$c['col']) . ($c['sub'] ? "({$c['sub']})" : ''); }, $i['cols']));
    if ($nombre === 'PRIMARY')        return "ALTER TABLE $tablaSql ADD PRIMARY KEY ($cols)";
    if ($i['unico'])                  return "ALTER TABLE $tablaSql ADD UNIQUE INDEX " . q($nombre) . " ($cols)";
    if ($i['tipo'] === 'FULLTEXT')    return "ALTER TABLE $tablaSql ADD FULLTEXT INDEX " . q($nombre) . " ($cols)";
    if ($i['tipo'] === 'SPATIAL')     return "ALTER TABLE $tablaSql ADD SPATIAL INDEX " . q($nombre) . " ($cols)";
    return "ALTER TABLE $tablaSql ADD INDEX " . q($nombre) . " ($cols)";
}

function sincronizarIndices(mysqli $m, string $dbDest, string $tabla, array $idxOri, array $idxDest, array $colsDest): void
{
    $tablaSql = q($dbDest) . '.' . q($tabla);

    foreach ($idxOri as $nombre => $i) {
        $firma = firmaIndice($i);

        if (isset($idxDest[$nombre])) {
            if (firmaIndice($idxDest[$nombre]) !== $firma) {
                registrar($dbDest, 'ÍNDICE', "$tabla.$nombre", 'Existe con distinta definición', 'ADVERTENCIA',
                    'Producción: ' . firmaIndice($idxDest[$nombre]) . ' | CYC: ' . $firma);
            }
            continue;
        }

        // ¿Existe uno equivalente con otro nombre?
        foreach ($idxDest as $nombreDest => $iDest) {
            if (firmaIndice($iDest) === $firma) {
                registrar($dbDest, 'ÍNDICE', "$tabla.$nombre", 'Equivalente con otro nombre', 'INFO', "En producción se llama $nombreDest.");
                continue 2;
            }
        }

        $sql = sqlIndice($tablaSql, $nombre, $i);

        if ($nombre === 'PRIMARY' || $i['unico']) {
            registrar($dbDest, 'ÍNDICE', "$tabla.$nombre", 'Revisión manual', 'ADVERTENCIA',
                'Índice ' . ($nombre === 'PRIMARY' ? 'PRIMARY' : 'UNIQUE') . ' faltante: no se crea automáticamente porque puede fallar con datos duplicados o bloquear inserciones que hoy son válidas.', $sql);
            continue;
        }

        foreach ($i['cols'] as $c) {
            if ($c['col'] === null || !isset($colsDest[$c['col']])) {
                registrar($dbDest, 'ÍNDICE', "$tabla.$nombre", 'Crear índice', 'ADVERTENCIA',
                    'Índice funcional o con columnas que no existen en producción.', $sql);
                continue 2;
            }
        }

        try {
            ejecutar($m, $sql);
            registrar($dbDest, 'ÍNDICE', "$tabla.$nombre", 'Crear índice', estadoAplicado(), $firma, $sql);
        } catch (\Throwable $e) {
            registrar($dbDest, 'ÍNDICE', "$tabla.$nombre", 'Crear índice', 'ERROR', $e->getMessage(), $sql);
        }
    }
}

function sincronizarTablas(mysqli $m, string $dbOri, string $dbDest, array $ori, array $dest, array $OPC, array $excluir): void
{
    foreach ($ori['tablas'] as $tabla => $_) {
        if (in_array($tabla, $excluir, true)) continue;

        if (!isset($dest['tablas'][$tabla])) {
            if ($OPC['crear_tablas']) crearTabla($m, $dbOri, $dbDest, $tabla);
            continue;
        }

        $colsDest = $dest['columnas'][$tabla] ?? [];
        sincronizarColumnas($m, $dbOri, $dbDest, $tabla, $ori['columnas'][$tabla] ?? [], $colsDest, $OPC);

        if ($OPC['crear_indices']) {
            sincronizarIndices($m, $dbDest, $tabla, $ori['indices'][$tabla] ?? [], $dest['indices'][$tabla] ?? [], $colsDest);
        }
    }

    if ($OPC['reportar_sobrantes']) {
        foreach ($dest['tablas'] as $tabla => $_) {
            if (!isset($ori['tablas'][$tabla])) {
                registrar($dbDest, 'TABLA', $tabla, 'Solo existe en producción', 'INFO', 'No se modifica ni se elimina.');
            }
        }
    }
}

// ============================================================================
// SINCRONIZACIÓN DE VISTAS Y RUTINAS
// ============================================================================
function sincronizarVistas(mysqli $m, string $dbOri, string $dbDest, array $vistasOri, array $excluir): void
{
    $vistasDest = leerVistas($m, $dbDest);
    $pendientes = [];
    $sinCambios = 0;

    foreach ($vistasOri as $nombre => $sqlOri) {
        if (in_array($nombre, $excluir, true)) continue;
        $sqlDest = str_ireplace(q($dbOri) . '.', q($dbDest) . '.', $sqlOri);

        if (isset($vistasDest[$nombre]) && normalizarSql($vistasDest[$nombre]) === normalizarSql($sqlDest)) {
            $sinCambios++;
            continue;
        }
        $pendientes[$nombre] = preg_replace('/^CREATE\s+/i', 'CREATE OR REPLACE ', $sqlDest, 1);
    }

    // Varias pasadas: una vista puede depender de otra que todavía no se ha creado
    $errores = [];
    for ($pasada = 1; $pendientes && $pasada <= 5; $pasada++) {
        $exitos = 0;
        foreach ($pendientes as $nombre => $sql) {
            try {
                ejecutar($m, $sql);
                registrar($dbDest, 'VISTA', $nombre, isset($vistasDest[$nombre]) ? 'Actualizar vista' : 'Crear vista', estadoAplicado(), '', $sql);
                unset($pendientes[$nombre], $errores[$nombre]);
                $exitos++;
            } catch (\Throwable $e) {
                $errores[$nombre] = $e->getMessage();
            }
        }
        if ($exitos === 0) break;
    }

    foreach ($pendientes as $nombre => $sql) {
        registrar($dbDest, 'VISTA', $nombre, 'Replicar vista', 'ERROR',
            ($errores[$nombre] ?? '') . (isset($vistasDest[$nombre]) ? ' | Se conserva la versión anterior.' : ''), $sql);
    }
    if ($sinCambios) registrar($dbDest, 'VISTA', '-', 'Comparar', 'INFO', "$sinCambios vistas sin cambios.");
}

function sincronizarRutinas(mysqli $m, string $tipo, string $dbOri, string $dbDest, array $rutOri, array $excluir, bool $reemplazarEsquema): void
{
    $rutDest    = leerRutinas($m, $dbDest, $tipo);
    $etiqueta   = $tipo === 'PROCEDURE' ? 'PROCEDIMIENTO' : 'FUNCIÓN';
    $sinCambios = 0;

    foreach ($rutOri as $nombre => $info) {
        if (in_array($nombre, $excluir, true)) continue;

        $sqlNuevo = $reemplazarEsquema ? str_ireplace(q($dbOri) . '.', q($dbDest) . '.', $info['sql']) : $info['sql'];
        $anterior = $rutDest[$nombre] ?? null;

        if ($anterior && normalizarSql($anterior['sql']) === normalizarSql($sqlNuevo)) {
            $sinCambios++;
            continue;
        }

        $accion = $anterior ? 'Actualizar' : 'Crear';
        if (SIMULAR) {
            registrar($dbDest, $etiqueta, $nombre, $accion, 'SIMULADO', '', $sqlNuevo);
            continue;
        }

        $modoSesion = $m->query('SELECT @@SESSION.sql_mode AS modo')->fetch_assoc()['modo'];
        try {
            $m->query("SET SESSION sql_mode = '" . $m->real_escape_string($info['sql_mode']) . "'");
            if ($anterior) $m->query("DROP $tipo IF EXISTS " . q($nombre));
            $m->query($sqlNuevo);
            registrar($dbDest, $etiqueta, $nombre, $accion, 'OK', '', $sqlNuevo);
        } catch (\Throwable $e) {
            $detalle = $e->getMessage();
            if ($anterior) {
                try {
                    $m->query("SET SESSION sql_mode = '" . $m->real_escape_string($anterior['sql_mode']) . "'");
                    $m->query($anterior['sql']);
                    $detalle .= ' | Se restauró la versión anterior.';
                } catch (\Throwable $e2) {
                    $detalle .= ' | ¡NO se pudo restaurar la versión anterior!: ' . $e2->getMessage();
                }
            }
            registrar($dbDest, $etiqueta, $nombre, $accion, 'ERROR', $detalle, $sqlNuevo);
        } finally {
            $m->query("SET SESSION sql_mode = '" . $m->real_escape_string($modoSesion) . "'");
        }
    }

    if ($sinCambios) registrar($dbDest, $etiqueta, '-', 'Comparar', 'INFO', "$sinCambios rutinas sin cambios.");
}

// ============================================================================
// PROCESO PRINCIPAL
// ============================================================================
try {
    $mysqli = new mysqli($host, $user, $pass, $db_principal);
    $mysqli->set_charset('utf8mb4');

    // Estructura de CYC (se lee una sola vez)
    $origen      = leerEsquema($mysqli, $db_principal);
    $funcOrigen  = $OPC['replicar_funciones']      ? leerRutinas($mysqli, $db_principal, 'FUNCTION')  : [];
    $procOrigen  = $OPC['replicar_procedimientos'] ? leerRutinas($mysqli, $db_principal, 'PROCEDURE') : [];
    $vistaOrigen = $OPC['replicar_vistas']         ? leerVistas($mysqli, $db_principal)               : [];

    foreach ($db_destinos as $dbDest) {
        if ($dbDest === $db_principal) continue;

        try {
            if (!existeBase($mysqli, $dbDest)) {
                registrar($dbDest, 'BASE', $dbDest, 'Validar base', 'ERROR', 'La base de datos no existe en el servidor.');
                continue;
            }
            $mysqli->select_db($dbDest);

            // Orden: tablas/columnas/índices → funciones → vistas → procedimientos
            $destino = leerEsquema($mysqli, $dbDest);
            sincronizarTablas($mysqli, $db_principal, $dbDest, $origen, $destino, $OPC, $EXCLUIR['tablas']);

            if ($OPC['replicar_funciones']) {
                sincronizarRutinas($mysqli, 'FUNCTION', $db_principal, $dbDest, $funcOrigen, $EXCLUIR['funciones'], $OPC['reemplazar_esquema_en_rutinas']);
            }
            if ($OPC['replicar_vistas']) {
                sincronizarVistas($mysqli, $db_principal, $dbDest, $vistaOrigen, $EXCLUIR['vistas']);
            }
            if ($OPC['replicar_procedimientos']) {
                sincronizarRutinas($mysqli, 'PROCEDURE', $db_principal, $dbDest, $procOrigen, $EXCLUIR['procedimientos'], $OPC['reemplazar_esquema_en_rutinas']);
            }
        } catch (\Throwable $e) {
            registrar($dbDest, 'BASE', $dbDest, 'Procesar base', 'ERROR', $e->getMessage());
        }

        $mysqli->select_db($db_principal);
    }

    $mysqli->close();
} catch (\Throwable $e) {
    registrar('-', 'GENERAL', '-', 'Conexión', 'ERROR', 'Error fatal: ' . $e->getMessage());
}

// ============================================================================
// REPORTE
// ============================================================================
$duracion = round(microtime(true) - $inicio, 1);
$resumen  = array_count_values(array_column($LOG, 'estado'));

if ($esCli) {
    foreach ($LOG as $l) {
        echo "[{$l['estado']}] {$l['db']} | {$l['tipo']} {$l['objeto']} | {$l['accion']}" . ($l['detalle'] !== '' ? " | {$l['detalle']}" : '') . PHP_EOL;
    }
    echo PHP_EOL . (SIMULAR ? 'MODO SIMULACIÓN: no se aplicó ningún cambio.' : 'Cambios aplicados.') . " Duración: {$duracion}s" . PHP_EOL;
    foreach ($resumen as $estado => $n) echo "$estado: $n" . PHP_EOL;
    exit;
}

$h = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
$colores = ['OK' => 'success', 'SIMULADO' => 'primary', 'ADVERTENCIA' => 'warning', 'ERROR' => 'danger', 'INFO' => 'secondary'];
$bases   = array_values(array_unique(array_column($LOG, 'db')));
$urlAplicar = '?aplicar=1' . ($dbFiltro ? '&db=' . urlencode($dbFiltro) : '');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Replicación de estructura</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --bs-primary: #696cff; --bs-primary-rgb: 105, 108, 255; }
        body { background: #f5f5f9; font-size: .8125rem; }
        .btn-primary { --bs-btn-bg: #696cff; --bs-btn-border-color: #696cff; --bs-btn-hover-bg: #5f61e6; --bs-btn-hover-border-color: #5f61e6; }
        .table-primary { --bs-table-bg: #e7e7ff; --bs-table-color: #696cff; }
        .text-bg-primary { background-color: #696cff !important; }
        pre.sql { white-space: pre-wrap; font-size: .72rem; margin: .25rem 0 0; max-height: 220px; overflow: auto; background: #f5f5f9; padding: .4rem; border-radius: .25rem; }
        td { word-break: break-word; }
    </style>
</head>
<body>
<div class="container-fluid py-3">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h6 class="mb-0">Replicación de estructura <?= $h($db_principal) ?> → producción</h6>
                <small class="text-muted"><?= count($db_destinos) ?> base(s) · <?= $duracion ?> s</small>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <?php if (SIMULAR): ?>
                    <span class="badge text-bg-warning">SIMULACIÓN · no se aplicó ningún cambio</span>
                    <a href="<?= $h($urlAplicar) ?>" class="btn btn-primary btn-sm"
                       onclick="return confirm('Se aplicarán los cambios marcados como SIMULADO en producción. ¿Continuar?')">Aplicar cambios</a>
                <?php else: ?>
                    <span class="badge text-bg-success">CAMBIOS APLICADOS</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <?php foreach ($colores as $estado => $color): ?>
                    <span class="badge text-bg-<?= $color ?>"><?= $estado ?>: <?= (int)($resumen[$estado] ?? 0) ?></span>
                <?php endforeach; ?>
            </div>

            <div class="row g-2 mb-2">
                <div class="col-sm-3">
                    <select id="fDb" class="form-select form-select-sm">
                        <option value="">Todas las bases</option>
                        <?php foreach ($bases as $b): ?><option value="<?= $h($b) ?>"><?= $h($b) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-3">
                    <select id="fEstado" class="form-select form-select-sm">
                        <option value="">Todos los estados</option>
                        <?php foreach (array_keys($colores) as $e): ?><option value="<?= $e ?>"><?= $e ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-6">
                    <input id="fTxt" type="search" class="form-control form-control-sm" placeholder="Buscar tabla, columna, vista...">
                </div>
            </div>

            <div class="table-responsive">
                <table id="tblLog" class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-primary">
                    <tr>
                        <th>Base</th><th>Tipo</th><th>Objeto</th><th>Acción</th><th>Estado</th><th>Detalle</th><th>SQL</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!$LOG): ?>
                        <tr><td colspan="7" class="text-center text-muted py-3">No se encontraron diferencias.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($LOG as $l): ?>
                        <tr data-db="<?= $h($l['db']) ?>" data-estado="<?= $h($l['estado']) ?>">
                            <td class="text-nowrap"><?= $h($l['db']) ?></td>
                            <td class="text-nowrap"><?= $h($l['tipo']) ?></td>
                            <td><?= $h($l['objeto']) ?></td>
                            <td><?= $h($l['accion']) ?></td>
                            <td><span class="badge text-bg-<?= $colores[$l['estado']] ?? 'secondary' ?>"><?= $h($l['estado']) ?></span></td>
                            <td><?= $h($l['detalle']) ?></td>
                            <td style="min-width:120px">
                                <?php if ($l['sql'] !== ''): ?>
                                    <details><summary class="text-primary">Ver</summary><pre class="sql"><?= $h($l['sql']) ?></pre></details>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
    const fDb = document.getElementById('fDb'), fEstado = document.getElementById('fEstado'), fTxt = document.getElementById('fTxt');
    function filtrar() {
        const db = fDb.value, es = fEstado.value, t = fTxt.value.toLowerCase();
        document.querySelectorAll('#tblLog tbody tr[data-db]').forEach(tr => {
            const ok = (!db || tr.dataset.db === db) && (!es || tr.dataset.estado === es) && (!t || tr.textContent.toLowerCase().includes(t));
            tr.classList.toggle('d-none', !ok);
        });
    }
    fDb.addEventListener('change', filtrar);
    fEstado.addEventListener('change', filtrar);
    fTxt.addEventListener('input', filtrar);
</script>
</body>
</html>