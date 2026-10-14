<?php

/**
 * Contabilización (causación) de nómina por empleado.
 *
 * Por cada nómina en BORRADOR del período se genera un documento (t_transacciones vía
 * p_volcar_documento) con el asiento completo en partida doble:
 *
 *  DÉBITOS                                          CRÉDITOS
 *  Gasto sueldos / auxilio / extras / etc.          Pasivo salud (NIT EPS)        ← 4 % trabajador + 8,5 % empleador
 *  Gasto aportes empleador (salud, pensión, ARL,    Pasivo pensión (NIT AFP)      ← 4 % + 12 % (+10 alto riesgo) + FSP
 *    caja, SENA, ICBF)                              Pasivo ARL / Caja / SENA / ICBF (NIT de cada entidad)
 *  Gasto provisiones (cesantías, intereses,         Retención en la fuente por pagar
 *    prima, vacaciones)                             Otras deducciones (libranzas, embargos…)
 *  Pasivo prestaciones (si se pagan en nómina)      Pasivo provisiones (NIT empleado)
 *                                                   Salarios por pagar = neto (NIT empleado)
 *
 * Las bases de aportes se recalculan desde t_nomina_detalle para respetar ajustes manuales.
 * Antes de iniciar se valida TODO (parámetros, perfiles, cuentas, terceros) y si algo falta
 * no se crea ningún documento.
 */

require_once(__DIR__ . '/nominaCalculos.php');

/** Cuenta del perfil o cadena vacía */
function nomina_cta($perfil, $campo)
{
  return trim((string)($perfil[$campo] ?? ''));
}

/** Cuenta débito para un devengado según su clase */
function nomina_cuenta_devengado($perfil, $linea)
{
  if (!empty($linea['cuenta'])) {
    return $linea['cuenta']; // cuenta propia del concepto (t_nomina_conceptos.cuenta_contable)
  }
  $concepto = strtoupper($linea['concepto']);
  switch ($linea['clase']) {
    case 'TRANSPORTE':
      return nomina_cta($perfil, 'cuenta_gasto_aux_transporte') ?: nomina_cta($perfil, 'cuenta_gasto_sueldo');
    case 'PRESTACION':
      // Pago de prestaciones previamente provisionadas: se debita el pasivo
      if (!empty($linea['vacaciones'])) {
        return nomina_cta($perfil, 'cuenta_pasivo_vacaciones');
      }
      if (strpos($concepto, 'INT') !== false && strpos($concepto, 'CES') !== false) {
        return nomina_cta($perfil, 'cuenta_pasivo_int_cesantias');
      }
      if (strpos($concepto, 'CES') !== false) {
        return nomina_cta($perfil, 'cuenta_pasivo_cesantias');
      }
      return nomina_cta($perfil, 'cuenta_pasivo_prima');
    case 'AUSENCIA':
      if (!empty($linea['vacaciones'])) {
        return nomina_cta($perfil, 'cuenta_pasivo_vacaciones');
      }
      if (!empty($linea['incapacidad'])) {
        return nomina_cta($perfil, 'cuenta_gasto_incapacidades') ?: nomina_cta($perfil, 'cuenta_gasto_sueldo');
      }
      return nomina_cta($perfil, 'cuenta_gasto_sueldo');
    case 'SALARIAL':
      if (!empty($linea['hora_extra'])) {
        return nomina_cta($perfil, 'cuenta_gasto_horas_extras') ?: nomina_cta($perfil, 'cuenta_gasto_sueldo');
      }
      return nomina_cta($perfil, 'cuenta_gasto_sueldo');
    default:
      return nomina_cta($perfil, 'cuenta_gasto_sueldo');
  }
}

/** Cuenta crédito y entidad para una deducción */
function nomina_cuenta_deduccion($perfil, $det)
{
  $concepto = strtolower(trim($det['concepto']));
  switch ($concepto) {
    case 'aporte_salud':
      return array(nomina_cta($perfil, 'cuenta_pasivo_salud'), 'SALUD');
    case 'aporte_pension':
    case 'aporte_fsp_solidaridad':
    case 'aporte_fsp_subsistencia':
      return array(nomina_cta($perfil, 'cuenta_pasivo_pension'), 'PENSION');
    case 'retencion_fuente':
      return array(nomina_cta($perfil, 'cuenta_retencion_fuente'), 'DIAN');
  }
  $meta = nomina_concepto($det['id_concepto'] ?? 0, $det['concepto']);
  $cuenta = trim((string)($meta['cuenta_contable'] ?? ''));
  return array($cuenta ?: nomina_cta($perfil, 'cuenta_otras_deducciones'), null);
}

/**
 * Construye en memoria el asiento de causación de una nómina (no graba nada).
 * Lo usan la contabilización del período y la recontabilización de un documento.
 *
 * @return array ['error' => string|null, 'asiento' => ['movs' => [...], 'neto' => ..., ...]]
 */
function nomina_asiento_causacion(array $nom, array $contrato, array $perfil, array $dets, array $periodo,
                                  array $entidadNit, array $entidadTipo, array &$avisos)
{
  $idNomina = intval($nom['id_nomina']);
  $doc = trim($nom['trabajador_documento']);
  $nombre = trim($nom['trabajador_primer_nombre'] . ' ' . $nom['trabajador_primer_apellido']) . " ($doc)";

    $nitEntidad = function ($tipo) use ($contrato, $entidadNit, $entidadTipo, $doc, $nombre, &$avisos) {
      $mapa = array('SALUD' => 'id_entidad_salud', 'PENSION' => 'id_entidad_pension',
                    'ARL' => 'id_entidad_arl', 'CAJA_COMPENSACION' => 'id_entidad_caja');
      if (isset($mapa[$tipo]) && !empty($contrato[$mapa[$tipo]]) && isset($entidadNit[intval($contrato[$mapa[$tipo]])])) {
        return $entidadNit[intval($contrato[$mapa[$tipo]])];
      }
      if (isset($entidadTipo[$tipo])) {
        return $entidadTipo[$tipo];
      }
      $avisos[$tipo] = "No hay entidad de tipo $tipo configurada; el pasivo se registró con el NIT del empleado";
      return $doc;
    };

    $movs = array();
    $faltaCuenta = array();
    $mov = function ($cuenta, $nit, $deb, $cre, $detalle, $etiqueta) use (&$movs, &$faltaCuenta) {
      $deb = round($deb, 2);
      $cre = round($cre, 2);
      if ($deb == 0 && $cre == 0) {
        return;
      }
      if ($cuenta === '') {
        $faltaCuenta[$etiqueta] = true;
        return;
      }
      $movs[] = array('cuenta' => $cuenta, 'nit' => $nit, 'debito' => $deb, 'credito' => $cre, 'detalle' => $detalle);
    };


    // a) Devengados
    $lineas = CalculosNomina::lineasDesdeDetalle($dets);
    foreach ($lineas as $l) {
      $etq = nomina_etiqueta_concepto($l['concepto']);
      $mov(nomina_cuenta_devengado($perfil, $l), $doc, $l['total'], 0, "$etq - CC $doc", "Devengado: $etq");
    }

    // b) Deducciones
    foreach ($dets as $d) {
      if (strtoupper($d['tipo']) !== 'DEDUCCION') {
        continue;
      }
      list($cuenta, $entidad) = nomina_cuenta_deduccion($perfil, $d);
      $etq = nomina_etiqueta_concepto($d['concepto'], $d['id_concepto'] ?? 0);
      $mov($cuenta, $entidad ? $nitEntidad($entidad) : $doc, 0, $d['total'], "Deducción $etq - CC $doc", "Deducción: $etq");
    }

    // c) Neto a pagar
    $totDev = 0;
    $totDed = 0;
    foreach ($dets as $d) {
      if (strtoupper($d['tipo']) === 'DEVENGADO') {
        $totDev += $d['total'];
      } else {
        $totDed += $d['total'];
      }
    }
    $neto = round($totDev - $totDed, 2);
    if ($neto < 0) {
      return array('error' => "$nombre: el neto a pagar es negativo (" . nomina_money($neto) . ")");
    }
    $mov(nomina_cta($perfil, 'cuenta_salario_por_pagar'), $doc, 0, $neto, "Neto a pagar nómina - CC $doc", 'Salarios por pagar');

    // d) Aportes del empleador y provisiones (bases recalculadas desde el detalle)
    $diasContrato = nomina_dias_contrato_en_periodo($contrato, $periodo);
    $bases = CalculosNomina::calcularBases($lineas, $contrato, $diasContrato, $periodo['anio']);
    $cargas = CalculosNomina::calcularCargasEmpleador($bases, $contrato, $periodo['anio']);
    foreach ($cargas['items'] as $it) {
      $nitPasivo = $it['entidad'] ? $nitEntidad($it['entidad']) : $doc;
      $txt = ($it['grupo'] === 'APORTE' ? 'Aporte ' : '') . $it['nombre'] . " - CC $doc";
      $mov(nomina_cta($perfil, $it['cuenta_gasto']), $doc, $it['valor'], 0, $txt, $it['nombre'] . ' (gasto)');
      $mov(nomina_cta($perfil, $it['cuenta_pasivo']), $nitPasivo, 0, $it['valor'], $txt, $it['nombre'] . ' (pasivo)');
    }

    if ($faltaCuenta) {
      return array('error' => "$nombre: el perfil '" . $perfil['codigo'] . "' no tiene cuenta para " . implode(', ', array_keys($faltaCuenta)));
    }

    // e) Partida doble
    $sd = 0;
    $sc = 0;
    foreach ($movs as $m) {
      $sd += $m['debito'];
      $sc += $m['credito'];
    }
    if (abs(round($sd - $sc, 2)) > 0.009) {
      return array('error' => "$nombre: el asiento no cuadra (débitos " . nomina_money($sd) . " / créditos " . nomina_money($sc) . ")");
    }
    if (abs($neto - (float)$nom['comprobante_total']) > 0.01) {
      $avisos[] = "$nombre: los totales de la cabecera no coincidían con el detalle y fueron corregidos";
    }

    return array('error' => null, 'asiento' => array(
      'id_nomina' => $idNomina, 'doc' => $doc, 'nombre' => $nombre, 'neto' => $neto,
      'dev' => round($totDev, 2), 'ded' => round($totDed, 2),
      'movs' => $movs, 'exonerado' => $cargas['exonerado']
    ));
}

/** Mapas de NIT de entidades: por id y por tipo (primera entidad activa de cada tipo) */
function nomina_mapas_entidades()
{
  $porId = array();
  $porTipo = array();
  foreach (nomina_select("SELECT e.id, e.tipo_entidad, t.nit FROM t_nomina_entidades e
                            INNER JOIN t_terceros t ON t.id = e.id_tercero
                           WHERE e.estado = 'ACTIVO' ORDER BY e.id") as $e) {
    $porId[intval($e['id'])] = trim($e['nit']);
    $tipoE = strtoupper($e['tipo_entidad']);
    if (!isset($porTipo[$tipoE])) {
      $porTipo[$tipoE] = trim($e['nit']);
    }
  }
  return array($porId, $porTipo);
}

// Endpoints solo cuando este archivo es invocado directamente (también se incluye desde la recontabilización)
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') !== realpath(__FILE__)) {
  return;
}

$op = $_POST['op'] ?? '';

switch ($op) {
  case 'contabilizar':
    $periodo = nomina_resolver_periodo($_POST['periodo'] ?? '');
    $idTipoDocumento = intval($_POST['id_tipo_documento'] ?? 0);
    $rawIds = $_POST['ids'] ?? array();
    $idsNomina = array_values(array_filter(array_map('intval', is_array($rawIds) ? $rawIds : explode(',', (string)$rawIds))));

    if (!$periodo) {
      responder(true, 'Debe seleccionar un período válido para contabilizar');
    }
    if ($idTipoDocumento <= 0) {
      responder(true, 'Debe seleccionar un tipo de documento válido');
    }

    // 0. Parámetros
    $faltantes = CalculosNomina::parametrosFaltantes($periodo['anio']);
    if ($faltantes) {
      responder(true, 'No se puede contabilizar. Faltan parámetros para ' . $periodo['anio'] . ': ' . implode(', ', $faltantes));
    }

    $conn = nomina_conn();
    $tipoDoc = nomina_select("SELECT sw, tipo FROM t_tipos_documentos WHERE id = $idTipoDocumento LIMIT 1");
    if (!$tipoDoc) {
      responder(true, "El tipo de documento seleccionado (ID $idTipoDocumento) no existe.");
    }
    $swDoc = intval($tipoDoc[0]['sw']);
    $prefijoDoc = nomina_esc($tipoDoc[0]['tipo']);

    // 1. Nóminas en borrador del período (un único perfil: el de la nómina o, en su defecto, el del contrato)
    $ini = $periodo['fecha_inicio'];
    $filtroIds = $idsNomina ? ' AND n.id IN (' . implode(',', $idsNomina) . ')' : '';
    $nominas = nomina_select("SELECT n.id AS id_nomina, n.id_contrato, n.trabajador_documento,
                                     n.trabajador_primer_nombre, n.trabajador_primer_apellido,
                                     n.devengados_total, n.deducciones_total, n.comprobante_total,
                                     COALESCE(NULLIF(n.id_perfil_contable,0), c.id_perfil_contable) AS id_perfil
                                FROM t_nomina n
                                LEFT JOIN t_nomina_contratos c ON c.id = n.id_contrato
                               WHERE n.estado_emision = 'BORRADOR'
                                 AND n.periodo_fecha_inicio = '$ini' $filtroIds");
    if (!$nominas) {
      responder(true, 'No hay nóminas en borrador para contabilizar en este período.');
    }

    $idsN = implode(',', array_map(function ($n) { return intval($n['id_nomina']); }, $nominas));
    $idsC = implode(',', array_map(function ($n) { return intval($n['id_contrato']); }, $nominas));

    // 2. Precarga de datos (evita consultas por empleado)
    $contratos = array();
    foreach (nomina_select("SELECT * FROM t_nomina_contratos WHERE id IN ($idsC)") as $c) {
      $contratos[intval($c['id'])] = $c;
    }
    $perfiles = array();
    foreach (nomina_select("SELECT * FROM t_nomina_perfiles_contables") as $pf) {
      $perfiles[intval($pf['id'])] = $pf;
    }
    $detalles = array();
    foreach (nomina_select("SELECT id_nomina, id_concepto, concepto, tipo, cantidad, total
                              FROM t_nomina_detalle WHERE id_nomina IN ($idsN) AND total <> 0") as $d) {
      $detalles[intval($d['id_nomina'])][] = $d;
    }
    // Terceros (empleados)
    $docs = array_unique(array_map(function ($n) { return "'" . nomina_esc(trim($n['trabajador_documento'])) . "'"; }, $nominas));
    $terceros = array();
    foreach (nomina_select("SELECT t.id, t.nit, (SELECT d.id FROM t_terceros_direcciones d WHERE d.id_tercero = t.id ORDER BY d.id LIMIT 1) AS id_dir
                              FROM t_terceros t WHERE t.nit IN (" . implode(',', $docs) . ")") as $t) {
      if (!isset($terceros[trim($t['nit'])])) {
        $terceros[trim($t['nit'])] = $t;
      }
    }
    // Entidades: NIT por id y NIT por defecto por tipo (SENA, ICBF, DIAN…)
    list($entidadNit, $entidadTipo) = nomina_mapas_entidades();

    // 3. Construcción y validación de todos los asientos en memoria
    $asientos = array();
    $errores = array();
    $avisos = array();

    foreach ($nominas as $nom) {
      $idNomina = intval($nom['id_nomina']);
      $doc = trim($nom['trabajador_documento']);
      $nombre = trim($nom['trabajador_primer_nombre'] . ' ' . $nom['trabajador_primer_apellido']) . " ($doc)";
      $contrato = $contratos[intval($nom['id_contrato'])] ?? null;
      $perfil = $perfiles[intval($nom['id_perfil'])] ?? null;

      if (!$contrato) {
        $errores[] = "$nombre: contrato no encontrado";
        continue;
      }
      if (!$perfil) {
        $errores[] = "$nombre: el contrato no tiene perfil contable asignado";
        continue;
      }
      if (!isset($terceros[$doc])) {
        $errores[] = "$nombre: el empleado no existe como tercero";
        continue;
      }

      $r = nomina_asiento_causacion($nom, $contrato, $perfil, $detalles[$idNomina] ?? array(), $periodo,
                                    $entidadNit, $entidadTipo, $avisos);
      if ($r['error']) {
        $errores[] = $r['error'];
        continue;
      }
      $asientos[] = $r['asiento'] + array('tercero' => $terceros[$doc]);
    }

    if ($errores) {
      responder(true, 'No se contabilizó ningún documento. Corrija lo siguiente:', array('errores' => $errores, 'avisos' => array_values($avisos)));
    }

    // 4. Grabación (todo o nada)
    $hoy = date('Y-m-d');
    $fechaDoc = min($periodo['fecha_fin'], $hoy);
    $fechaHora = $fechaDoc . ' ' . date('H:i:s');
    $idUsuario = intval($_SESSION['Ses_id'] ?? 1);
    $creados = array();

    $conn->autocommit(false);
    $conn->begin_transaction();
    try {
      foreach ($asientos as $a) {
        $idTercero = intval($a['tercero']['id']);
        $idDir = intval($a['tercero']['id_dir'] ?? 0);
        $notas = nomina_esc("Causación nómina {$periodo['label']} - CC {$a['doc']}");

        if (!$conn->query("INSERT INTO t_transacciones_tmp
                             (numero, id_tipo_documento, id_usuario, id_tercero, id_bodega, notas, fecha_hora, fecha,
                              valor_total, sw, id_condicion, id_tercero_dir, documento, es_credito)
                           VALUES (0, $idTipoDocumento, $idUsuario, $idTercero, 1, '$notas', '$fechaHora', '$fechaDoc',
                                   {$a['neto']}, $swDoc, 1, $idDir, '', 1)")) {
          throw new Exception('t_transacciones_tmp: ' . $conn->error);
        }
        $idTmp = $conn->insert_id;

        if (!$conn->query("CALL p_volcar_documento($idTmp, @nuevoid)")) {
          throw new Exception('p_volcar_documento: ' . $conn->error);
        }
        while ($conn->more_results() && $conn->next_result()) {
          if ($r = $conn->store_result()) {
            $r->free();
          }
        }
        $out = nomina_select("SELECT @nuevoid AS id");
        $idTrans = intval($out[0]['id'] ?? 0);
        if ($idTrans <= 0) {
          // Respaldo: ubicar el documento recién volcado por tipo, tercero y notas (nunca "el último del tipo")
          $busca = nomina_select("SELECT id FROM t_transacciones
                                   WHERE id_tipo_documento = $idTipoDocumento AND id_tercero = $idTercero
                                     AND notas = '$notas' AND fecha = '$fechaDoc'
                                   ORDER BY id DESC LIMIT 1");
          $idTrans = intval($busca[0]['id'] ?? 0);
        }
        if ($idTrans <= 0) {
          throw new Exception("p_volcar_documento no devolvió el ID del documento para CC {$a['doc']}");
        }
        $creados[] = $idTrans;

        $num = nomina_select("SELECT numero FROM t_transacciones WHERE id = $idTrans LIMIT 1");
        $numero = nomina_esc($num[0]['numero'] ?? '');

        $vals = array();
        foreach ($a['movs'] as $m) {
          $vals[] = sprintf(
            "(%d, '%s', '%s', '%s', '%s', %.2f, %.2f, '%s', 0, '%s')",
            $idTrans, $prefijoDoc, $numero, nomina_esc($m['cuenta']), nomina_esc($m['nit']),
            $m['debito'], $m['credito'], $fechaHora, nomina_esc(mb_substr($m['detalle'], 0, 250))
          );
        }
        if (!$conn->query("INSERT INTO t_movimiento_contable
                             (id_transaccion, tipo, numero, cuenta, nit, debito, credito, fecha_hora, ceco, detalle)
                           VALUES " . implode(', ', $vals))) {
          throw new Exception('t_movimiento_contable: ' . $conn->error);
        }

        if (!$conn->query("UPDATE t_nomina
                              SET prefijo = '$prefijoDoc', id_tipo_documento = $idTipoDocumento, consecutivo = '$numero',
                                  devengados_total = {$a['dev']}, deducciones_total = {$a['ded']}, comprobante_total = {$a['neto']},
                                  estado_emision = 'CONTABILIZADO'
                            WHERE id = {$a['id_nomina']} AND estado_emision = 'BORRADOR'")
          || $conn->affected_rows !== 1) {
          throw new Exception("La nómina #{$a['id_nomina']} cambió de estado durante el proceso");
        }
      }

      $conn->commit();
      $conn->autocommit(true);
      responder(false, 'Se causaron y contabilizaron ' . count($asientos) . ' documentos. Ya puede generar las colillas de pago.', array(
        'total_transacciones' => count($asientos),
        'periodo' => $periodo['id'],
        'avisos' => array_values($avisos)
      ));
    } catch (Exception $e) {
      $conn->rollback();
      // p_volcar_documento puede confirmar internamente: se limpian los documentos creados
      if ($creados) {
        $lista = implode(',', $creados);
        $conn->query("DELETE FROM t_movimiento_contable WHERE id_transaccion IN ($lista)");
        $conn->query("DELETE FROM t_transacciones WHERE id IN ($lista)");
      }
      $conn->autocommit(true);
      responder(true, 'Error al contabilizar: ' . $e->getMessage());
    }
    break;

  case 'vista_previa':
    // Muestra el asiento que se generaría para una nómina (sin grabar)
    $idNomina = intval($_POST['id_nomina'] ?? 0);
    $n = nomina_select("SELECT n.*, COALESCE(NULLIF(n.id_perfil_contable,0), c.id_perfil_contable) AS id_perfil
                          FROM t_nomina n LEFT JOIN t_nomina_contratos c ON c.id = n.id_contrato
                         WHERE n.id = $idNomina LIMIT 1");
    if (!$n) {
      responder(true, 'Nómina no encontrada');
    }
    $n = $n[0];
    $periodo = nomina_resolver_periodo(nomina_frecuencia_empresa() === 1 ? $n['periodo_fecha_inicio'] : $n['periodo']);
    $contrato = nomina_select("SELECT * FROM t_nomina_contratos WHERE id = " . intval($n['id_contrato']));
    $dets = nomina_select("SELECT id_concepto, concepto, tipo, cantidad, total FROM t_nomina_detalle WHERE id_nomina = $idNomina AND total <> 0");
    $lineas = CalculosNomina::lineasDesdeDetalle($dets);
    $bases = CalculosNomina::calcularBases($lineas, $contrato[0] ?? array(), nomina_dias_contrato_en_periodo($contrato[0] ?? array(), $periodo), $periodo['anio']);
    responder(false, 'Vista previa', array(
      'bases' => $bases,
      'cargas' => CalculosNomina::calcularCargasEmpleador($bases, $contrato[0] ?? array(), $periodo['anio'])
    ));
    break;

  default:
    responder(true, 'Operación no válida');
}
