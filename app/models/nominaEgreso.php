<?php

/**
 * Egreso (pago) de nómina — sw 9
 *
 * Genera desde el módulo de nómina el comprobante de egreso que paga el neto de las nóminas
 * contabilizadas del período, para evitar digitarlo a mano en Egresos y Pagos.
 *
 *  Asiento por empleado:  Débito  Salarios por pagar (perfil del contrato)  NIT empleado
 *                         Crédito Caja / Banco seleccionado                  NIT empleado (o empresa en modo total)
 *
 *  - Modo TOTAL: un solo egreso por el total de la nómina (tercero = empresa).
 *  - Modo EMPLEADO: un egreso por cada empleado (tercero = empleado).
 *  - Cada nómina pagada se cruza con su documento de causación en t_transacciones_cruce,
 *    así la cuenta por pagar del empleado queda saldada en cartera.
 *  - Una nómina solo puede pagarse una vez (t_nomina.id_transaccion_egreso).
 *
 * Requiere un tipo de documento sw = 9 marcado con nomina = 'S'.
 */

require_once(__DIR__ . '/nominaHelpers.php');

function egreso_cta($perfil, $campo)
{
  return trim((string)($perfil[$campo] ?? ''));
}

/** Nóminas contabilizadas o emitidas del período pendientes de pago */
function egreso_pendientes($periodo, array $ids = array())
{
  $filtro = $ids ? ' AND n.id IN (' . implode(',', array_map('intval', $ids)) . ')' : '';
  return nomina_select("SELECT n.id, n.id_contrato, n.id_tipo_documento, n.prefijo, n.consecutivo, n.trabajador_documento,
                               n.trabajador_primer_nombre, n.trabajador_primer_apellido, n.comprobante_total,
                               COALESCE(NULLIF(n.id_perfil_contable,0), c.id_perfil_contable) AS id_perfil,
                               c.metodo_pago, c.banco, c.tipo_cuenta, c.numero_cuenta,
                               IFNULL((SELECT td.sw FROM t_tipos_documentos td WHERE td.id = n.id_tipo_documento), 14) AS sw_causacion
                          FROM t_nomina n
                          LEFT JOIN t_nomina_contratos c ON c.id = n.id_contrato
                         WHERE n.periodo_fecha_inicio = '{$periodo['fecha_inicio']}'
                           AND n.estado_emision IN ('CONTABILIZADO','EMITIDO')
                           AND IFNULL(n.id_transaccion_egreso, 0) = 0
                           AND n.comprobante_total > 0 $filtro
                         ORDER BY n.trabajador_primer_apellido, n.trabajador_primer_nombre");
}

if (!nomina_tiene_columna('t_nomina', 'id_transaccion_egreso')) {
  responder(true, 'Falta ejecutar el script sql/nomina_egreso_2026_10.sql (columnas de pago en t_nomina).');
}

$op = $_POST['op'] ?? '';

switch ($op) {
  case 'datos_egreso':
    // Información para el modal: tipos de documento, resumen de lo pendiente y método de pago
    $periodo = nomina_resolver_periodo($_POST['periodo'] ?? '');
    if (!$periodo) {
      responder(true, 'Período inválido');
    }
    $rawIds = $_POST['ids'] ?? '';
    $ids = array_filter(array_map('intval', is_array($rawIds) ? $rawIds : explode(',', (string)$rawIds)));
    $pend = egreso_pendientes($periodo, $ids);
    $total = 0;
    foreach ($pend as $p) {
      $total += (float)$p['comprobante_total'];
    }
    responder(false, 'Success', array(
      'tipos' => nomina_select("SELECT id, tipo, descripcion FROM t_tipos_documentos WHERE sw = 9 AND nomina = 'S'"),
      'cantidad' => count($pend),
      'total' => round($total, 2),
      'empleados' => array_map(function ($p) {
        return array(
          'id' => $p['id'],
          'empleado' => trim($p['trabajador_primer_nombre'] . ' ' . $p['trabajador_primer_apellido']),
          'documento' => $p['trabajador_documento'],
          'neto' => (float)$p['comprobante_total'],
          'pago' => trim(($p['banco'] ? 'Banco ' . $p['banco'] . ' ' : '') . ($p['numero_cuenta'] ? '···' . substr($p['numero_cuenta'], -4) : ($p['metodo_pago'] === '10' ? 'Efectivo' : '')))
        );
      }, $pend),
      'fecha_sugerida' => min($periodo['fecha_fin'], date('Y-m-d')),
    ));
    break;

  case 'generar_egreso':
    $periodo = nomina_resolver_periodo($_POST['periodo'] ?? '');
    $idTipoDocumento = intval($_POST['id_tipo_documento'] ?? 0);
    $cuentaBanco = trim($_POST['cuenta_banco'] ?? '');
    $modo = ($_POST['modo'] ?? 'TOTAL') === 'EMPLEADO' ? 'EMPLEADO' : 'TOTAL';
    $fecha = trim($_POST['fecha'] ?? date('Y-m-d'));
    $rawIds = $_POST['ids'] ?? '';
    $ids = array_filter(array_map('intval', is_array($rawIds) ? $rawIds : explode(',', (string)$rawIds)));

    if (!$periodo) {
      responder(true, 'Período inválido');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || $fecha > date('Y-m-d')) {
      responder(true, 'La fecha del egreso no es válida (no puede ser futura)');
    }
    if ($cuentaBanco === '') {
      responder(true, 'Seleccione la cuenta de caja o banco de la que sale el pago');
    }
    $tipoDoc = nomina_select("SELECT sw, tipo FROM t_tipos_documentos WHERE id = $idTipoDocumento AND sw = 9 AND nomina = 'S' LIMIT 1");
    if (!$tipoDoc) {
      responder(true, "Seleccione un tipo de documento de egreso (sw 9) marcado como nómina");
    }
    $prefijo = nomina_esc($tipoDoc[0]['tipo']);

    $pend = egreso_pendientes($periodo, $ids);
    if (!$pend) {
      responder(true, 'No hay nóminas contabilizadas pendientes de pago en este período');
    }

    // Precarga y validación
    $perfiles = array();
    foreach (nomina_select("SELECT * FROM t_nomina_perfiles_contables") as $pf) {
      $perfiles[intval($pf['id'])] = $pf;
    }
    $docs = array_unique(array_map(function ($p) { return "'" . nomina_esc(trim($p['trabajador_documento'])) . "'"; }, $pend));
    $terceros = array();
    foreach (nomina_select("SELECT t.id, t.nit, (SELECT d.id FROM t_terceros_direcciones d WHERE d.id_tercero = t.id ORDER BY d.id LIMIT 1) AS id_dir
                              FROM t_terceros t WHERE t.nit IN (" . implode(',', $docs) . ")") as $t) {
      $terceros[trim($t['nit'])] = $terceros[trim($t['nit'])] ?? $t;
    }
    $empresa = nomina_select("SELECT t.id, t.nit, (SELECT d.id FROM t_terceros_direcciones d WHERE d.id_tercero = t.id ORDER BY d.id LIMIT 1) AS id_dir
                                FROM t_empresa e INNER JOIN t_terceros t ON t.nit = e.nit LIMIT 1");

    $errores = array();
    $lineas = array();
    foreach ($pend as $p) {
      $doc = trim($p['trabajador_documento']);
      $nombre = trim($p['trabajador_primer_nombre'] . ' ' . $p['trabajador_primer_apellido']) . " ($doc)";
      $perfil = $perfiles[intval($p['id_perfil'])] ?? null;
      $cuentaPagar = $perfil ? egreso_cta($perfil, 'cuenta_salario_por_pagar') : '';
      if ($cuentaPagar === '') {
        $errores[] = "$nombre: el perfil contable no tiene cuenta de salarios por pagar";
        continue;
      }
      if (!isset($terceros[$doc])) {
        $errores[] = "$nombre: el empleado no existe como tercero";
        continue;
      }
      if ($p['prefijo'] === '' || $p['consecutivo'] === '') {
        $errores[] = "$nombre: la nómina no tiene documento de causación";
        continue;
      }
      $lineas[] = $p + array('doc' => $doc, 'cuenta_pagar' => $cuentaPagar, 'tercero' => $terceros[$doc]);
    }
    if ($modo === 'TOTAL' && !$empresa) {
      $errores[] = 'Para el egreso por el total, el NIT de la empresa debe existir como tercero';
    }
    if ($errores) {
      responder(true, 'No se generó el egreso. Corrija lo siguiente:', array('errores' => $errores));
    }

    // Agrupación: un egreso total o uno por empleado
    $grupos = $modo === 'TOTAL'
      ? array(array('tercero' => $empresa[0], 'nit_banco' => trim($empresa[0]['nit']), 'lineas' => $lineas))
      : array_map(function ($l) {
          return array('tercero' => $l['tercero'], 'nit_banco' => $l['doc'], 'lineas' => array($l));
        }, $lineas);

    $conn = nomina_conn();
    $idUsuario = intval($_SESSION['Ses_id'] ?? 1);
    $fechaHora = $fecha . ' ' . date('H:i:s');
    $cuentaBancoEsc = nomina_esc($cuentaBanco);
    // La cuenta se guarda para poder regenerar la contabilización del egreso más adelante
    $setCuenta = nomina_tiene_columna('t_nomina', 'egreso_cuenta') ? ", egreso_cuenta = '$cuentaBancoEsc'" : '';
    $creados = array();
    $aplicados = array();   // causaciones a las que se sumó valor_aplicado (para revertir si algo falla)
    $documentos = array();

    $conn->autocommit(false);
    $conn->begin_transaction();
    try {
      foreach ($grupos as $g) {
        $total = 0;
        foreach ($g['lineas'] as $l) {
          $total += (float)$l['comprobante_total'];
        }
        $total = round($total, 2);
        $idTercero = intval($g['tercero']['id']);
        $idDir = intval($g['tercero']['id_dir'] ?? 0);
        $notas = nomina_esc($modo === 'TOTAL'
          ? "Pago nómina {$periodo['label']} - " . count($g['lineas']) . " empleados"
          : "Pago nómina {$periodo['label']} - CC {$g['lineas'][0]['doc']}");

        if (!$conn->query("INSERT INTO t_transacciones_tmp
                             (numero, id_tipo_documento, id_usuario, id_tercero, id_bodega, notas, fecha_hora, fecha,
                              valor_total, sw, id_condicion, id_tercero_dir, documento, es_credito)
                           VALUES (0, $idTipoDocumento, $idUsuario, $idTercero, 1, '$notas', '$fechaHora', '$fecha',
                                   $total, 9, 1, $idDir, '', 0)")) {
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
          $b = nomina_select("SELECT id FROM t_transacciones WHERE id_tipo_documento = $idTipoDocumento AND id_tercero = $idTercero
                                 AND notas = '$notas' AND fecha = '$fecha' ORDER BY id DESC LIMIT 1");
          $idTrans = intval($b[0]['id'] ?? 0);
        }
        if ($idTrans <= 0) {
          throw new Exception('p_volcar_documento no devolvió el ID del egreso');
        }
        $creados[] = $idTrans;
        $num = nomina_select("SELECT numero FROM t_transacciones WHERE id = $idTrans LIMIT 1");
        $numero = nomina_esc($num[0]['numero'] ?? '');

        $movs = array();
        $cruces = array();
        foreach ($g['lineas'] as $l) {
          $neto = round((float)$l['comprobante_total'], 2);
          $docEsc = nomina_esc($l['doc']);
          $det = nomina_esc("Pago nómina {$l['prefijo']} {$l['consecutivo']} - CC {$l['doc']}");
          $movs[] = sprintf("(%d, '%s', '%s', '%s', '%s', %.2f, 0.00, '%s', 0, '%s')",
            $idTrans, $prefijo, $numero, nomina_esc($l['cuenta_pagar']), $docEsc, $neto, $fechaHora, $det);
          if ($modo === 'EMPLEADO') {
            $movs[] = sprintf("(%d, '%s', '%s', '%s', '%s', 0.00, %.2f, '%s', 0, '%s')",
              $idTrans, $prefijo, $numero, $cuentaBancoEsc, $docEsc, $neto, $fechaHora, $det);
          }
          // tipo/numero = egreso; sw/tipo_aplica/numero_aplica = documento de causación aplicado
          $cruces[] = sprintf("(%d, '%s', '%s', %d, '%s', '%s', %.2f, 0, 0, 0, 0, '%s')",
            $idTrans, $prefijo, $numero, intval($l['sw_causacion']), nomina_esc($l['prefijo']), nomina_esc($l['consecutivo']), $neto, $fecha);
        }
        if ($modo === 'TOTAL') {
          $movs[] = sprintf("(%d, '%s', '%s', '%s', '%s', 0.00, %.2f, '%s', 0, '%s')",
            $idTrans, $prefijo, $numero, $cuentaBancoEsc, nomina_esc($g['nit_banco']), $total, $fechaHora,
            nomina_esc("Pago nómina {$periodo['label']}"));
        }

        if (!$conn->query("INSERT INTO t_movimiento_contable
                             (id_transaccion, tipo, numero, cuenta, nit, debito, credito, fecha_hora, ceco, detalle)
                           VALUES " . implode(', ', $movs))) {
          throw new Exception('t_movimiento_contable: ' . $conn->error);
        }
        // Cruce del egreso contra la causación: salda la cuenta por pagar del empleado en cartera
        if (!$conn->query("INSERT INTO t_transacciones_cruce
                             (id_transaccion, tipo, numero, sw, tipo_aplica, numero_aplica, valor, descuento, ret_ICA, ret_IVA, ret_FTE, fecha)
                           VALUES " . implode(', ', $cruces))) {
          throw new Exception('t_transacciones_cruce: ' . $conn->error);
        }

        // Saldo en cartera: el documento de causación queda con valor_aplicado = lo pagado
        foreach ($g['lineas'] as $l) {
          $neto = round((float)$l['comprobante_total'], 2);
          $idTipoCaus = intval($l['id_tipo_documento']);
          $numCaus = nomina_esc($l['consecutivo']);
          if (!$conn->query("UPDATE t_transacciones
                                SET valor_aplicado = IFNULL(valor_aplicado, 0) + $neto
                              WHERE id_tipo_documento = $idTipoCaus AND numero = '$numCaus'
                                AND IFNULL(valor_aplicado, 0) + $neto <= valor_total + 0.01")
            || $conn->affected_rows !== 1) {
            throw new Exception("No se pudo aplicar el pago a la causación {$l['prefijo']} {$l['consecutivo']} (no existe o ya está pagada)");
          }
          $aplicados[] = array($idTipoCaus, $numCaus, $neto);
        }
        // El egreso queda totalmente aplicado (no es un anticipo)
        $conn->query("UPDATE t_transacciones SET valor_aplicado = valor_total WHERE id = $idTrans");

        $idsGrupo = implode(',', array_map(function ($l) { return intval($l['id']); }, $g['lineas']));
        if (!$conn->query("UPDATE t_nomina
                              SET id_transaccion_egreso = $idTrans, egreso_tipo = '$prefijo', egreso_numero = '$numero', fecha_pago = '$fecha'" . $setCuenta . "
                            WHERE id IN ($idsGrupo) AND IFNULL(id_transaccion_egreso,0) = 0")
          || $conn->affected_rows !== count($g['lineas'])) {
          throw new Exception('Alguna nómina fue pagada por otro usuario durante el proceso');
        }
        $documentos[] = trim($prefijo . ' ' . $numero);
      }

      $conn->commit();
      $conn->autocommit(true);
      responder(false, count($documentos) === 1
        ? 'Se generó el egreso ' . $documentos[0] . ' por ' . nomina_money(array_sum(array_column($lineas, 'comprobante_total')))
        : 'Se generaron ' . count($documentos) . ' egresos (' . $documentos[0] . ' a ' . end($documentos) . ')',
        array('documentos' => $documentos, 'empleados' => count($lineas)));
    } catch (Exception $e) {
      $conn->rollback();
      // Solo si p_volcar_documento confirmó por su cuenta (el egreso sigue existiendo tras el rollback)
      // se revierten a mano los valores aplicados; si no, el rollback ya los deshizo
      $persistio = $creados && nomina_select("SELECT 1 FROM t_transacciones WHERE id IN (" . implode(',', $creados) . ") LIMIT 1");
      foreach ($persistio ? $aplicados : array() as $a) {
        $conn->query("UPDATE t_transacciones SET valor_aplicado = GREATEST(0, IFNULL(valor_aplicado,0) - {$a[2]})
                       WHERE id_tipo_documento = {$a[0]} AND numero = '{$a[1]}'");
      }
      if ($creados) {
        $lista = implode(',', $creados);
        $conn->query("DELETE FROM t_transacciones_cruce WHERE id_transaccion IN ($lista)");
        $conn->query("DELETE FROM t_movimiento_contable WHERE id_transaccion IN ($lista)");
        $conn->query("DELETE FROM t_transacciones WHERE id IN ($lista)");
      }
      $conn->autocommit(true);
      responder(true, 'Error al generar el egreso: ' . $e->getMessage());
    }
    break;

  default:
    responder(true, 'Operación no válida');
}