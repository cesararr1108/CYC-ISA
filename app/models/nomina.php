<?php

/**
 * Modelo de Liquidación de Nómina
 * Períodos dinámicos (mensual / quincenal), liquidación masiva y gestión del detalle.
 *
 * Flujo de estados: (sin registro) → BORRADOR → CONTABILIZADO → EMITIDO
 *  - BORRADOR: editable, se puede recalcular o eliminar
 *  - CONTABILIZADO: causado en contabilidad; habilita colilla de pago y envío a DIAN
 *  - EMITIDO: aceptado por la DIAN (documento soporte de nómina electrónica)
 */

require_once(__DIR__ . '/nominaCalculos.php');

function datosFiscales()
{
  $datEmpresa = nomina_select("SELECT nit FROM t_empresa LIMIT 1");
  if (!$datEmpresa) {
    return array();
  }
  $connfel = conectarFel();
  $nit = $connfel->real_escape_string($datEmpresa[0]['nit']);
  $sql = "SELECT r.Prefix, r.StartDate, r.EndDate, UPPER(d.razonSocial) AS razonSocial, d.nit,
                 d.direccion, d.codigoCiudad, d.codigoDpto, d.codigoPais, d.ProfileExecutionIDNomina
            FROM t_resoluciones r
           INNER JOIN t_datos_fiscales d ON r.Id_datos_fiscales = d.id
           WHERE d.nit = '$nit' AND r.idTipoDoc = 6
           LIMIT 1";
  $data = GenerarArray($sql, $connfel);
  return $data ? $data[0] : array();
}

/** Recalcula totales de la cabecera a partir del detalle */
function actualizarTotal($idNomina)
{
  $idNomina = (int)$idNomina;
  return Query("UPDATE t_nomina n
                  JOIN (SELECT COALESCE(SUM(IF(tipo='DEVENGADO', total, 0)),0) AS dev,
                               COALESCE(SUM(IF(tipo='DEDUCCION', total, 0)),0) AS ded
                          FROM t_nomina_detalle WHERE id_nomina = $idNomina) t
                   SET n.devengados_total = t.dev,
                       n.deducciones_total = t.ded,
                       n.comprobante_total = t.dev - t.ded
                 WHERE n.id = $idNomina");
}

/** Estado actual de una nómina (o null si no existe) */
function estadoNomina($idNomina)
{
  $row = nomina_select("SELECT estado_emision FROM t_nomina WHERE id = " . intval($idNomina) . " LIMIT 1");
  return $row ? strtoupper((string)$row[0]['estado_emision']) : null;
}

/** Bloquea modificaciones si la nómina no está en BORRADOR */
function exigirBorrador($idNomina)
{
  $estado = estadoNomina($idNomina);
  if ($estado === null) {
    responder(true, 'Nómina no encontrada');
  }
  if ($estado !== 'BORRADOR') {
    responder(true, "La nómina está en estado $estado y no puede modificarse");
  }
}

/** Lee ids enviados como arreglo o como lista separada por comas */
function idsDesdePost($campo)
{
  $raw = $_POST[$campo] ?? array();
  if (!is_array($raw)) {
    $raw = explode(',', (string)$raw);
  }
  return array_values(array_filter(array_map('intval', $raw)));
}

$op = strtolower($_POST['op'] ?? '');

switch ($op) {
  case 'combos_periodos':
    $anio = intval($_POST['anio'] ?? date('Y'));
    responder(false, 'Success', nomina_generar_periodos($anio, nomina_frecuencia_empresa()));
    break;

  case 'listar_periodo':
    $periodo = nomina_resolver_periodo($_POST['periodo'] ?? '');
    if (!$periodo) {
      responder(true, 'Período inválido');
    }
    $ini = $periodo['fecha_inicio'];
    $fin = $periodo['fecha_fin'];

    // Contratos vigentes en el período + cualquier contrato que ya tenga nómina en él
    $colsPago = nomina_tiene_columna('t_nomina', 'id_transaccion_egreso')
      ? "IFNULL(l.egreso_tipo,'') AS egreso_tipo, IFNULL(l.egreso_numero,'') AS egreso_numero,"
      : "'' AS egreso_tipo, '' AS egreso_numero,";
    $sql = "SELECT c.id, l.id AS id_nomina, c.estado, $colsPago
                   TRIM(CONCAT_WS(' ', NULLIF(c.primer_nombre,''), NULLIF(c.segundo_nombre,''),
                                       NULLIF(c.primer_apellido,''), NULLIF(c.segundo_apellido,''))) AS empleado,
                   c.empleado_documento, c.cargo,
                   COALESCE(l.devengados_total, 0) AS devengados_total,
                   COALESCE(l.deducciones_total, 0) AS deducciones_total,
                   COALESCE(l.comprobante_total, 0) AS comprobante_total,
                   COALESCE(l.estado_emision, 'CALCULAR') AS estado_emision,
                   COALESCE(d.dev, 0) AS cantidad_novedades_devengados,
                   COALESCE(d.ded, 0) AS cantidad_novedades_deducciones,
                   IFNULL(l.prefijo, '') AS prefijo,
                   IFNULL(l.consecutivo, '') AS consecutivo,
                   IFNULL(l.observaciones, '') AS notas,
                   IF(IFNULL(c.id_perfil_contable,0) = 0 AND IFNULL(l.id_perfil_contable,0) = 0, 0, 1) AS tiene_perfil
              FROM t_nomina_contratos c
              LEFT JOIN t_nomina l ON l.id_contrato = c.id AND l.periodo_fecha_inicio = '$ini'
              LEFT JOIN (SELECT nd.id_nomina, SUM(nd.tipo='DEVENGADO') AS dev, SUM(nd.tipo='DEDUCCION') AS ded
                           FROM t_nomina_detalle nd
                          INNER JOIN t_nomina nn ON nn.id = nd.id_nomina AND nn.periodo_fecha_inicio = '$ini'
                          GROUP BY nd.id_nomina) d ON d.id_nomina = l.id
             WHERE l.id IS NOT NULL
                OR (c.estado = 'ACTIVO'
                    AND (c.fecha_inicio IS NULL OR c.fecha_inicio <= '$fin')
                    AND (c.fecha_fin IS NULL OR c.fecha_fin >= '$ini'))
             ORDER BY empleado ASC";

    responder(false, 'Success', array('periodo' => $periodo, 'empleados' => nomina_select($sql)));
    break;

  case 'obtener_detalles':
    $idNomina = intval($_POST['id_nomina'] ?? 0);
    if (!$idNomina) {
      responder(true, 'ID de nómina requerido');
    }
    $nomina = nomina_select("SELECT id, estado_emision, observaciones, periodo_fecha_inicio, periodo_fecha_fin,
                                    devengados_total, deducciones_total, comprobante_total
                               FROM t_nomina WHERE id = $idNomina LIMIT 1");
    if (!$nomina) {
      responder(true, 'Nómina no encontrada');
    }
    $detalles = nomina_select("SELECT nd.id, nd.id_concepto, nd.concepto, nd.tipo, nd.cantidad, nd.valor_unitario,
                                      nd.total, nd.observaciones,
                                      (SELECT co.nombre FROM t_nomina_conceptos co
                                        WHERE co.id = nd.id_concepto OR co.clave = nd.concepto
                                        ORDER BY (co.id = IFNULL(nd.id_concepto, 0)) DESC LIMIT 1) AS concepto_nombre
                                 FROM t_nomina_detalle nd
                                WHERE nd.id_nomina = $idNomina
                                ORDER BY nd.tipo DESC, nd.id ASC");
    responder(false, 'Success', array(
      'estado_emision' => $nomina[0]['estado_emision'],
      'nomina' => $nomina[0],
      'detalles' => $detalles
    ));
    break;

  case 'liquidar_masivo':
    $periodo = nomina_resolver_periodo($_POST['periodo'] ?? '');
    if (!$periodo) {
      responder(true, 'Período inválido');
    }
    $faltantes = CalculosNomina::parametrosFaltantes($periodo['anio']);
    if ($faltantes) {
      responder(true, 'No se puede liquidar. Faltan parámetros para ' . $periodo['anio'] . ': ' . implode(', ', $faltantes));
    }

    $ini = $periodo['fecha_inicio'];
    $fin = $periodo['fecha_fin'];
    $idsContratos = idsDesdePost('ids');
    $filtroIds = $idsContratos ? ' AND c.id IN (' . implode(',', $idsContratos) . ')' : '';

    // 1. Contratos vigentes que no tengan la nómina del período en estado final (una sola consulta)
    $contratos = nomina_select("SELECT c.*
                                  FROM t_nomina_contratos c
                                 WHERE c.estado = 'ACTIVO'
                                   AND (c.fecha_inicio IS NULL OR c.fecha_inicio <= '$fin')
                                   AND (c.fecha_fin IS NULL OR c.fecha_fin >= '$ini')
                                   $filtroIds
                                   AND NOT EXISTS (SELECT 1 FROM t_nomina n
                                                    WHERE n.id_contrato = c.id AND n.periodo_fecha_inicio = '$ini'
                                                      AND n.estado_emision IN ('CONTABILIZADO','EMITIDO'))");
    if (!$contratos) {
      responder(false, 'No hay empleados pendientes por liquidar en este período', array('procesados' => 0, 'errores' => array(), 'avisos' => array()));
    }
    $ids = implode(',', array_map(function ($c) { return intval($c['id']); }, $contratos));

    // 2. Novedades del período para todos los contratos (una sola consulta)
    //    En quincenal se toman las novedades cuya fecha cae dentro de la quincena;
    //    las que no tienen fecha se liquidan en la segunda quincena.
    $filtroFecha = '';
    if ($periodo['quincena'] === 1) {
      $filtroFecha = " AND n.fecha_novedad BETWEEN '$ini' AND '$fin'";
    } elseif ($periodo['quincena'] === 2) {
      $filtroFecha = " AND (n.fecha_novedad IS NULL OR n.fecha_novedad >= '$ini')";
    }
    $novedadesPorContrato = array();
    foreach (nomina_select("SELECT n.id_contrato, n.id_concepto, n.cantidad, n.valor_unitario, n.valor_final AS total,
                                   n.dias_incapacidad, co.clave AS concepto, co.nombre, co.tipo, co.subtipo,
                                   co.porcentaje_fijo, co.requiere_cantidad
                              FROM t_nomina_novedades n
                             INNER JOIN t_nomina_conceptos co ON co.id = n.id_concepto
                             WHERE n.id_contrato IN ($ids)
                               AND n.periodo = '{$periodo['periodo']}'
                               AND IFNULL(n.estado,'ACTIVO') = 'ACTIVO' $filtroFecha") as $n) {
      $novedadesPorContrato[intval($n['id_contrato'])][] = $n;
    }

    // 3. Nóminas existentes del período y acumulado de la 1ª quincena (para retención mensual)
    $existentes = array();
    foreach (nomina_select("SELECT id, id_contrato FROM t_nomina WHERE id_contrato IN ($ids) AND periodo_fecha_inicio = '$ini'") as $r) {
      $existentes[intval($r['id_contrato'])] = intval($r['id']);
    }
    $acumulados = array();
    if ($periodo['quincena'] === 2) {
      $q1 = substr($ini, 0, 8) . '01';
      foreach (nomina_select("SELECT n.id_contrato, n.devengados_total,
                                     SUM(IF(d.concepto IN ('aporte_salud','aporte_pension','aporte_fsp_solidaridad','aporte_fsp_subsistencia'), d.total, 0)) AS no_gravados,
                                     SUM(IF(d.concepto = 'retencion_fuente', d.total, 0)) AS retencion
                                FROM t_nomina n
                                LEFT JOIN t_nomina_detalle d ON d.id_nomina = n.id
                               WHERE n.id_contrato IN ($ids) AND n.periodo_fecha_inicio = '$q1'
                               GROUP BY n.id") as $r) {
        $acumulados[intval($r['id_contrato'])] = array(
          'ingresos' => (float)$r['devengados_total'],
          'no_gravados' => (float)$r['no_gravados'],
          'retencion' => (float)$r['retencion']
        );
      }
    }

    $procesados = 0;
    $omitidos = 0;
    $errores = array();
    $avisos = array();
    $usuario = intval($_SESSION['Ses_id'] ?? 1);
    $conn = nomina_conn();
    $tieneDiasCol = nomina_tiene_columna('t_nomina', 'dias_trabajados');

    foreach ($contratos as $contrato) {
      $idContrato = intval($contrato['id']);
      $nombre = nomina_nombre($contrato);

      if (empty($contrato['id_perfil_contable'])) {
        $avisos[] = "$nombre: el contrato no tiene perfil contable; no podrá contabilizarse";
      }

      $liq = CalculosNomina::calcularLiquidacion(
        $contrato,
        $novedadesPorContrato[$idContrato] ?? array(),
        $periodo,
        $acumulados[$idContrato] ?? array()
      );
      if ($liq === null) {
        $omitidos++;
        continue;
      }
      foreach ($liq['avisos'] as $a) {
        if (stripos($a, 'Auxilio de transporte no aplica') === false) {
          $avisos[] = "$nombre: $a";
        }
      }

      $cab = array(
        'id_contrato'                 => $idContrato,
        'id_perfil_contable'          => $contrato['id_perfil_contable'] ?: null,
        'periodo'                     => $periodo['periodo'],
        'periodo_fecha_inicio'        => $ini,
        'periodo_fecha_fin'           => $fin,
        'fecha_pago'                  => $fin,
        'trabajador_tipo_documento'   => $contrato['tipo_documento'] ?: 'CC',
        'trabajador_documento'        => $contrato['empleado_documento'],
        'trabajador_primer_nombre'    => $contrato['primer_nombre'] ?? '',
        'trabajador_segundo_nombre'   => $contrato['segundo_nombre'] ?: null,
        'trabajador_primer_apellido'  => $contrato['primer_apellido'] ?? '',
        'trabajador_segundo_apellido' => $contrato['segundo_apellido'] ?: null,
        'trabajador_salario_basico'   => (float)$contrato['salario_basico'],
        'trabajador_subtipo'          => $contrato['subtipo_trabajador'] ?: '00',
        'trabajador_alto_riesgo'      => intval($contrato['alto_riesgo'] ?? 0),
        'devengados_total'            => $liq['totales']['devengos_total'],
        'deducciones_total'           => $liq['totales']['deducciones_total'],
        'comprobante_total'           => $liq['totales']['neto_pago'],
        'estado_emision'              => 'BORRADOR',
      );
      if ($tieneDiasCol) {
        $cab['dias_trabajados'] = $liq['dias']['trabajados'];
      }

      $conn->begin_transaction();
      try {
        $idNomina = $existentes[$idContrato] ?? 0;
        if ($idNomina) {
          $sets = array();
          foreach ($cab as $k => $v) {
            $sets[] = "$k = " . ($v === null ? 'NULL' : "'" . $conn->real_escape_string((string)$v) . "'");
          }
          if (!$conn->query("UPDATE t_nomina SET " . implode(', ', $sets) . " WHERE id = $idNomina AND estado_emision = 'BORRADOR'")) {
            throw new Exception($conn->error);
          }
          if (!$conn->query("DELETE FROM t_nomina_detalle WHERE id_nomina = $idNomina")) {
            throw new Exception($conn->error);
          }
        } else {
          $cab['usuario_creacion'] = $usuario;
          $vals = array_map(function ($v) use ($conn) {
            return $v === null ? 'NULL' : "'" . $conn->real_escape_string((string)$v) . "'";
          }, array_values($cab));
          if (!$conn->query("INSERT INTO t_nomina (" . implode(', ', array_keys($cab)) . ") VALUES (" . implode(', ', $vals) . ")")) {
            if ($conn->errno == 1062) {
              throw new Exception('ya existe una nómina para este contrato y mes. Si liquida quincenal, ejecute el script de migración que ajusta el índice único de t_nomina');
            }
            throw new Exception($conn->error);
          }
          $idNomina = $conn->insert_id;
        }

        // Detalle en un solo INSERT
        $filas = array();
        foreach (array('DEVENGADO' => $liq['devengos'], 'DEDUCCION' => $liq['deducciones']) as $tipo => $lineas) {
          foreach ($lineas as $l) {
            $filas[] = sprintf(
              "(%d, %s, '%s', '%s', %s, %s, %s)",
              $idNomina,
              empty($l['id_concepto']) ? 'NULL' : intval($l['id_concepto']),
              $conn->real_escape_string($l['concepto']),
              $tipo,
              round((float)($l['cantidad'] ?? 0), 4),
              round((float)($l['valor_unitario'] ?? 0), 2),
              round((float)$l['total'], 2)
            );
          }
        }
        if ($filas && !$conn->query("INSERT INTO t_nomina_detalle (id_nomina, id_concepto, concepto, tipo, cantidad, valor_unitario, total) VALUES " . implode(', ', $filas))) {
          throw new Exception($conn->error);
        }

        $conn->commit();
        $procesados++;
      } catch (Exception $e) {
        $conn->rollback();
        $errores[] = "$nombre: " . $e->getMessage();
      }
    }

    $mensaje = "Se liquidaron $procesados empleados";
    if ($omitidos) {
      $mensaje .= ", $omitidos sin días vigentes en el período";
    }
    if ($errores) {
      $mensaje .= ' (' . count($errores) . ' con error)';
    }
    responder(false, $mensaje, array('procesados' => $procesados, 'errores' => $errores, 'avisos' => $avisos));
    break;

  case 'eliminar_detalle':
    $idDetalle = intval($_POST['id_detalle'] ?? 0);
    $row = nomina_select("SELECT id_nomina FROM t_nomina_detalle WHERE id = $idDetalle LIMIT 1");
    if (!$row) {
      responder(true, 'No se encontró el detalle');
    }
    $idNomina = intval($row[0]['id_nomina']);
    exigirBorrador($idNomina);
    if (!Query("DELETE FROM t_nomina_detalle WHERE id = $idDetalle") || !actualizarTotal($idNomina)) {
      responder(true, 'Error al eliminar el detalle');
    }
    responder(false, 'Detalle eliminado correctamente');
    break;

  case 'actualizar_detalle':
    $idDetalle = intval($_POST['id_detalle'] ?? 0);
    $total = round(floatval($_POST['total'] ?? 0), 2);
    if ($total < 0) {
      responder(true, 'El valor no puede ser negativo');
    }
    $row = nomina_select("SELECT id_nomina FROM t_nomina_detalle WHERE id = $idDetalle LIMIT 1");
    if (!$row) {
      responder(true, 'Detalle no encontrado');
    }
    $idNomina = intval($row[0]['id_nomina']);
    exigirBorrador($idNomina);
    if (!Query("UPDATE t_nomina_detalle SET total = $total WHERE id = $idDetalle") || !actualizarTotal($idNomina)) {
      responder(true, 'Error al actualizar el detalle');
    }
    responder(false, 'Detalle actualizado correctamente');
    break;

  case 'actualizar_estado':
    // Usado por el flujo DIAN: solo permite CONTABILIZADO → EMITIDO
    $idNomina = intval($_POST['id_nomina'] ?? 0);
    $nuevo = strtoupper(trim($_POST['nuevo_estado'] ?? ''));
    if ($nuevo !== 'EMITIDO') {
      responder(true, 'Transición de estado no permitida');
    }
    $actual = estadoNomina($idNomina);
    if ($actual !== 'CONTABILIZADO' && $actual !== 'EMITIDO') {
      responder(true, 'Solo una nómina contabilizada puede marcarse como emitida');
    }
    Query("UPDATE t_nomina SET estado_emision = 'EMITIDO' WHERE id = $idNomina");
    responder(false, 'Estado de nómina actualizado correctamente');
    break;

  case 'informacion_dian':
    $tipo = nomina_esc($_POST['tipo'] ?? '');
    $numero = nomina_esc($_POST['numero'] ?? '');
    responder(false, 'Información obtenida', nomina_select("SELECT tipo, numero, cufe, XmlFileName, fechaEnvio, fechaValidacion,
                                                                    fechaCreacion, QRCode
                                                               FROM t_respuestas_dian
                                                              WHERE tipo = '$tipo' AND numero = '$numero'"));
    break;

  case 'obtener_estado_dian':
    $datosEmpresa = datosFiscales();
    if (empty($datosEmpresa)) {
      responder(true, 'No se encontraron datos fiscales para la empresa');
    }
    responder(false, 'Información obtenida', $datosEmpresa['ProfileExecutionIDNomina'] ?? 'Desconocido');
    break;

  case 'eliminar_nomina':
    $idNomina = intval($_POST['id_nomina'] ?? 0);
    exigirBorrador($idNomina);
    $c = nomina_conn();
    $c->begin_transaction();
    $ok = $c->query("DELETE FROM t_nomina_detalle WHERE id_nomina = $idNomina")
      && $c->query("DELETE FROM t_nomina WHERE id = $idNomina AND estado_emision = 'BORRADOR'");
    $ok ? $c->commit() : $c->rollback();
    responder(!$ok, $ok ? 'Nómina eliminada correctamente' : 'Error al eliminar nómina: ' . $c->error);
    break;

  case 'guardar_nota_nomina':
    $idNomina = intval($_POST['id_nomina'] ?? 0);
    $nota = nomina_esc(mb_substr(trim($_POST['nota'] ?? ''), 0, 500));
    $ok = Query("UPDATE t_nomina SET observaciones = '$nota' WHERE id = $idNomina");
    responder(!$ok, $ok ? 'Nota guardada correctamente' : 'Error al guardar la nota');
    break;

  case 's_documentos':
    $data = nomina_select("SELECT * FROM t_tipos_documentos WHERE sw = 14 AND nomina = 'S'");
    echo json_encode(array('data' => $data));
    break;

  default:
    responder(true, 'Operación no válida');
}
