<?php
session_start();
require_once('bd.php');
require_once('funciones.php');

header('Content-Type: application/json; charset=utf-8');

$conn = conectar();

/* =====================================================================
   HELPERS
   ===================================================================== */

function cd_responder(array $payload): void
{
  echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
  exit;
}

function cd_ok($data = [], string $message = 'success', array $extra = []): void
{
  cd_responder(array_merge([
    'success' => true,
    'error'   => false,
    'message' => $message,
    'data'    => $data
  ], $extra));
}

function cd_fail(string $message, array $extra = []): void
{
  cd_responder(array_merge([
    'success' => false,
    'error'   => true,
    'message' => $message,
    'data'    => []
  ], $extra));
}

/** SELECT con sentencia preparada. Devuelve filas asociativas. */
function cd_query(mysqli $conn, string $sql, string $types = '', array $params = []): array
{
  $stmt = $conn->prepare($sql);
  if (!$stmt) {
    throw new RuntimeException('Error preparando consulta: ' . $conn->error);
  }
  if ($types !== '') {
    $stmt->bind_param($types, ...$params);
  }
  if (!$stmt->execute()) {
    $err = $stmt->error;
    $stmt->close();
    throw new RuntimeException('Error ejecutando consulta: ' . $err);
  }
  $res  = $stmt->get_result();
  $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
  $stmt->close();
  return $rows;
}

/** INSERT/UPDATE/DELETE con sentencia preparada. Devuelve filas afectadas. */
function cd_exec(mysqli $conn, string $sql, string $types = '', array $params = []): int
{
  $stmt = $conn->prepare($sql);
  if (!$stmt) {
    throw new RuntimeException('Error preparando consulta: ' . $conn->error);
  }
  if ($types !== '') {
    $stmt->bind_param($types, ...$params);
  }
  if (!$stmt->execute()) {
    $err = $stmt->error;
    $stmt->close();
    throw new RuntimeException('Error ejecutando consulta: ' . $err);
  }
  $n = $stmt->affected_rows;
  $stmt->close();
  return $n;
}

function cd_int(string $k): int
{
  return (int) ($_POST[$k] ?? 0);
}

function cd_str(string $k, int $max = 255): string
{
  return mb_substr(trim((string) ($_POST[$k] ?? '')), 0, $max);
}

function cd_fecha(string $k): string
{
  $v = cd_str($k, 10);
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : '';
}

function cd_usuario(): int
{
  $id = (int) ($_SESSION['Ses_id'] ?? 0);
  if ($id <= 0) {
    cd_fail('Su sesión expiró. Inicie sesión nuevamente.', ['sesion' => false]);
  }
  return $id;
}

/* =====================================================================
   CONSULTAS DEL DOCUMENTO
   ===================================================================== */

function cd_encabezado(mysqli $conn, string $tipo, int $numero): ?array
{
  $sql = "SELECT
            t.id,
            t.sw,
            t.id_bodega,
            t.tipo,
            t.numero,
            c.nit,
            c.razon_comercial,
            c.tratamiento,
            c.email_fe,
            t.fecha_hora,
            UPPER(t.usuario) AS usuario,
            t.notas,
            IFNULL(t.valor_total, 0) AS valor_documento,
            ( IFNULL(t.valor_total, 0)
              - IFNULL(t.retencion_ica, 0)
              - IFNULL(t.retencion_iva, 0)
              - IFNULL(t.retencion_cree, 0) ) AS valor_total,
            IFNULL(t.valor_aplicado, 0)   AS valor_aplicado,
            IFNULL(t.descuento, 0)        AS descuento,
            IFNULL(t.iva, 0)              AS iva,
            IFNULL(t.impoconsumo, 0)      AS impoconsumo,
            IFNULL(t.ibua, 0)             AS ibua,
            IFNULL(t.icui, 0)             AS icui,
            IFNULL(t.retencion_fuente, 0) AS retencion_fuente,
            IFNULL(t.retencion_ica, 0)    AS retencion_ica,
            IFNULL(t.retencion_iva, 0)    AS retencion_iva,
            IFNULL(t.retencion_cree, 0)   AS retencion_cree,
            t.id_condicion                AS condicion,
            IFNULL(p.descripcion, '')     AS descripcion,
            IFNULL(p.dias, 0)             AS dias,
            IFNULL(p.dias_gracia, 0)      AS dias_gracia,
            IF(p.id IS NULL, NULL,
               DATE(DATE_ADD(t.fecha_hora, INTERVAL (IFNULL(p.dias,0) + IFNULL(p.dias_gracia,0)) DAY))
            ) AS fecha_vencimiento,
            IF(p.id IS NULL, NULL,
               DATEDIFF(DATE_ADD(t.fecha_hora, INTERVAL (IFNULL(p.dias,0) + IFNULL(p.dias_gracia,0)) DAY), CURDATE())
            ) AS dias_para_vencer,
            t.anulado,
            t.fecha_anulacion,
            t.motivo_anulacion,
            IFNULL(u.usuario, '') AS usuario_anulacion,
            IFNULL((
              SELECT d.celular
              FROM t_terceros_direcciones d
              WHERE d.id_tercero = c.id AND d.principal = 1
              LIMIT 1
            ), '') AS celular,
            t.documento,
            IFNULL(td.nomina, 'N') AS nomina
          FROM t_transacciones t
          INNER JOIN t_terceros c        ON c.id = t.id_tercero
          LEFT  JOIN t_tipos_documentos td ON td.id = t.id_tipo_documento
          LEFT  JOIN t_condiciones_pago p ON p.id = t.id_condicion
          LEFT  JOIN t_usuarios u         ON u.id = t.id_usuario_anulacion
          WHERE t.tipo = ? AND t.numero = ?
          LIMIT 1";
  $rows = cd_query($conn, $sql, 'si', [$tipo, $numero]);
  return $rows[0] ?? null;
}

function cd_detalle(mysqli $conn, int $id): array
{
  $sql = "SELECT
            r.codigo,
            r.descripcion,
            d.costo_unitario,
            IFNULL(d.lote, '-')  AS lote,
            IFNULL(d.vmcto, '-') AS vmcto,
            IFNULL(d.cantidad / NULLIF(d.can_und, 0), d.cantidad) AS cantidad,
            d.und,
            d.can_und AS cantidad_und,
            d.pcj_descuento,
            d.pcj_iva,
            IFNULL(m.descripcion, '') AS marca,
            ROUND(((d.costo_unitario * d.cantidad) * (1 - (d.pcj_descuento / 100))) * (1 + (d.pcj_iva / 100)), 2) AS costo_total
          FROM t_transacciones_detalle d
          INNER JOIN t_referencias r ON r.id = d.id_referencia
          LEFT  JOIN t_marcas m      ON m.id = r.id_marca
          WHERE d.id_transaccion = ?";
  return cd_query($conn, $sql, 'i', [$id]);
}

function cd_contable(mysqli $conn, int $id): array
{
  $sql = "SELECT
            m.id,
            m.fecha_hora,
            m.tipo,
            m.numero,
            m.nit,
            m.cuenta,
            UPPER(IFNULL(c.descripcion, 'CUENTA NO EXISTE')) AS cuenta_desc,
            m.debito,
            m.credito
          FROM t_movimiento_contable m
          LEFT JOIN t_cuentas_contables c ON c.cuenta = m.cuenta
          WHERE m.id_transaccion = ?
          ORDER BY m.id";
  return cd_query($conn, $sql, 'i', [$id]);
}

function cd_medios_pago(mysqli $conn, int $id): array
{
  $sql = "SELECT
            p.id,
            m.id          AS id_medio_pago,
            m.cuenta      AS cuenta_medio_pago,
            m.descripcion AS medio_pago,
            p.valor,
            IFNULL(p.voucher, '') AS voucher
          FROM t_transacciones_pago p
          INNER JOIN t_medios_pagos m ON m.id = p.id_medio_pago
          WHERE p.id_transaccion = ?";
  return cd_query($conn, $sql, 'i', [$id]);
}

/** Cruces vivos. UNION ALL en lugar de OR para que use índices. */
function cd_cruces(mysqli $conn, int $id, string $tipo, int $numero): array
{
  $campos = "c.tipo, c.numero, c.tipo_aplica, c.numero_aplica,
             c.valor, c.descuento, c.ret_FTE, c.ret_IVA, c.ret_ICA,
             c.fecha, t.usuario";
  $sql = "SELECT * FROM (
            SELECT $campos
            FROM t_transacciones_cruce c
            INNER JOIN t_transacciones t ON t.id = c.id_transaccion AND t.anulado = 0
            WHERE c.id_transaccion = ?
            UNION ALL
            SELECT $campos
            FROM t_transacciones_cruce c
            INNER JOIN t_transacciones t ON t.id = c.id_transaccion AND t.anulado = 0
            WHERE c.tipo_aplica = ? AND c.numero_aplica = ? AND c.id_transaccion <> ?
          ) x
          ORDER BY x.fecha";
  return cd_query($conn, $sql, 'isii', [$id, $tipo, $numero, $id]);
}

/** Cruces que se liberaron al anular este documento (histórico). */
function cd_cruces_liberados(mysqli $conn, int $id): array
{
  try {
    $sql = "SELECT
              c.tipo, c.numero, c.tipo_aplica, c.numero_aplica,
              c.valor, c.descuento, c.ret_FTE, c.ret_IVA, c.ret_ICA,
              c.fecha,
              IFNULL(t.usuario, '') AS usuario,
              c.fecha_liberacion,
              IFNULL(u.usuario, '') AS usuario_liberacion
            FROM t_transacciones_cruce_anulados c
            LEFT JOIN t_transacciones t ON t.id = c.id_transaccion
            LEFT JOIN t_usuarios u      ON u.id = c.id_usuario_liberacion
            WHERE c.id_transaccion_anulada = ?
            ORDER BY c.fecha";
    return cd_query($conn, $sql, 'i', [$id]);
  } catch (Throwable $e) {
    // La tabla histórico aún no existe: no se interrumpe la consulta.
    return [];
  }
}

function cd_estado(mysqli $conn, int $id): ?array
{
  $rows = cd_query(
    $conn,
    "SELECT t.id, t.sw, t.anulado, IFNULL(td.nomina, 'N') AS nomina
     FROM t_transacciones t
     LEFT JOIN t_tipos_documentos td ON td.id = t.id_tipo_documento
     WHERE t.id = ?",
    'i',
    [$id]
  );
  return $rows[0] ?? null;
}

/* =====================================================================
   OPERACIONES
   ===================================================================== */

try {
  switch ($_POST['op'] ?? '') {

    /* Todo el documento en una sola petición (antes eran 5 secuenciales) */
    case 'documento-completo':
      $tipo   = cd_str('tipo', 10);
      $numero = cd_int('numero');
      if ($tipo === '' || $numero <= 0) {
        cd_fail('Seleccione el tipo de documento e ingrese un número válido.');
      }
      $enc = cd_encabezado($conn, $tipo, $numero);
      if (!$enc) {
        cd_fail("No existe el documento $tipo $numero.", ['codigo' => 'NO_EXISTE']);
      }
      $id      = (int) $enc['id'];
      $anulado = (int) $enc['anulado'] === 1;

      cd_ok([
        'encabezado'        => $enc,
        'detalle'           => cd_detalle($conn, $id),
        'contable'          => cd_contable($conn, $id),
        'medios_pago'       => cd_medios_pago($conn, $id),
        'cruces'            => $anulado ? cd_cruces_liberados($conn, $id) : cd_cruces($conn, $id, $tipo, $numero),
        'cruces_historicos' => $anulado
      ]);
      break;

    /* ---- Operaciones individuales (compatibilidad y recargas parciales) ---- */
    case 'documento-encabezado':
      $enc = cd_encabezado($conn, cd_str('tipo', 10), cd_int('numero'));
      cd_ok($enc ? [$enc] : []);
      break;

    case 'documento-detalle':
      cd_ok(cd_detalle($conn, cd_int('id')));
      break;

    case 'documento-contable':
      cd_ok(cd_contable($conn, cd_int('id')));
      break;

    case 'documento-medio-pago':
      cd_ok(cd_medios_pago($conn, cd_int('id')));
      break;

    case 'documento-cruces':
      $tipo   = cd_str('tipo', 10);
      $numero = cd_int('numero');
      $enc    = cd_query($conn, "SELECT id FROM t_transacciones WHERE tipo = ? AND numero = ? LIMIT 1", 'si', [$tipo, $numero]);
      cd_ok($enc ? cd_cruces($conn, (int) $enc[0]['id'], $tipo, $numero) : []);
      break;

    case 'validar-anulado':
      $est = cd_estado($conn, cd_int('id'));
      cd_ok($est ? [['anulado' => $est['anulado']]] : []);
      break;

    case 'generar-contabilidad':
      cd_usuario();
      $id  = cd_int('id');
      $est = cd_estado($conn, $id);
      if (!$est) {
        cd_fail('El documento no existe.');
      }
      if ((int) $est['anulado'] === 1) {
        cd_fail('El documento está anulado; no es posible generar su contabilización.');
      }
      // Documentos de nómina (causación sw 14 / egreso sw 9): su asiento lo genera el módulo de nómina.
      // Los procedimientos add_movimiento_nd_proveedor / add_movimiento_egresos no aplican a ellos.
      if ($est['nomina'] === 'S' && in_array((int) $est['sw'], [9, 14], true)) {
        require_once(__DIR__ . '/nominaRecontabilizar.php');
        // En egresos la cuenta de caja/banco puede venir elegida por el usuario (cuando no se encuentra guardada)
        $r = nomina_recontabilizar_documento($id, cd_str('cuenta_banco', 30));
        if ($r['ok']) {
          cd_ok([], $r['message'], ['nomina' => true]);
        }
        cd_fail(
          $r['message'] ?: 'No fue posible regenerar la contabilización de nómina.',
          ['nomina' => true, 'requiere_cuenta' => !empty($r['requiere_cuenta'])]
        );
      }
      // El sw se toma de la base de datos, no del cliente
      cd_responder(generarMovimientoContable((int) $est['sw'], $id));
      break;

    case 'listar-documentos':
      $sw      = cd_int('sw');
      $tipo    = cd_str('tipo', 10);
      $almacen = cd_int('almacen');
      $tercero = cd_str('tercero', 80);
      $fecIni  = cd_fecha('fecha_ini');
      $fecFin  = cd_fecha('fecha_fin');
      $limite  = 500;

      $sql = "SELECT
                t.id, t.fecha_hora, t.tipo, t.numero, t.nit, t.anulado,
                IFNULL(tt.razon_comercial, t.nit) AS razon_comercial,
                IFNULL(t.valor_total, 0)    AS valor_total,
                IFNULL(t.valor_aplicado, 0) AS valor_aplicado
              FROM t_transacciones t
              INNER JOIN t_terceros tt ON tt.id = t.id_tercero
              WHERE t.sw = ?";
      $types  = 'i';
      $params = [$sw];

      if ($tipo !== '') {
        $sql .= " AND t.tipo = ?";
        $types .= 's';
        $params[] = $tipo;
      }
      if ($almacen > 0) {
        $sql .= " AND t.id_bodega = ?";
        $types .= 'i';
        $params[] = $almacen;
      }
      if ($tercero !== '') {
        $sql .= " AND (tt.razon_comercial LIKE ? OR t.nit LIKE ?)";
        $types .= 'ss';
        $like = '%' . $tercero . '%';
        $params[] = $like;
        $params[] = $like;
      }
      // Rangos sobre la columna (sin DATE()) para poder usar el índice
      if ($fecIni !== '') {
        $sql .= " AND t.fecha_hora >= ?";
        $types .= 's';
        $params[] = $fecIni . ' 00:00:00';
      }
      if ($fecFin !== '') {
        $sql .= " AND t.fecha_hora < DATE_ADD(?, INTERVAL 1 DAY)";
        $types .= 's';
        $params[] = $fecFin;
      }
      $sql .= " ORDER BY t.fecha_hora DESC LIMIT " . ($limite + 1);

      $data      = cd_query($conn, $sql, $types, $params);
      $limitado  = count($data) > $limite;
      if ($limitado) {
        array_pop($data);
      }
      cd_ok($data, 'success', ['limitado' => $limitado, 'limite' => $limite]);
      break;

    case 'anular-documento':
      $usuario = cd_usuario();
      $id      = cd_int('id');
      $motivo  = cd_str('motivo', 100);
      $liberar = cd_int('liberar_cruces') === 1 ? 1 : 0;

      if ($id <= 0) {
        cd_fail('Documento no válido.');
      }
      if (mb_strlen($motivo) < 5) {
        cd_fail('El motivo de anulación debe tener al menos 5 caracteres.');
      }

      $stmt = $conn->prepare('CALL p_anular_documento(?, ?, ?, ?)');
      if (!$stmt) {
        cd_fail('Error preparando la anulación: ' . $conn->error);
      }
      $stmt->bind_param('iisi', $id, $usuario, $motivo, $liberar);
      if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        cd_fail('Error ejecutando la anulación: ' . $err);
      }
      $res = $stmt->get_result();
      $row = $res ? $res->fetch_assoc() : null;
      if ($res) {
        $res->free();
      }
      // Consumir los result sets restantes del CALL
      while ($stmt->more_results() && $stmt->next_result()) {
        $r = $stmt->get_result();
        if ($r) {
          $r->free();
        }
      }
      $stmt->close();

      $status = (int) ($row['status'] ?? 0);
      $msg    = $row['resultado'] ?? 'Respuesta inesperada del procedimiento.';
      $cruces = (int) ($row['cruces'] ?? 0);

      if ($status === 1) {
        cd_ok([], $msg, ['cruces' => $cruces]);
      }
      if ($status === 2) {
        cd_fail($msg, ['requiere_confirmacion' => true, 'cruces' => $cruces]);
      }
      if ($status === 3) {
        cd_fail($msg, ['bloqueado_por_pagos' => true]);
      }
      cd_fail($msg);
      break;

    case 'documento-cambiar-medio-pago':
      cd_usuario();
      $idPago  = cd_int('id_pago');
      $idMedio = cd_int('id_medio_pago');

      $pago = cd_query(
        $conn,
        "SELECT p.id, p.id_medio_pago, t.anulado
         FROM t_transacciones_pago p
         INNER JOIN t_transacciones t ON t.id = p.id_transaccion
         WHERE p.id = ?",
        'i',
        [$idPago]
      );
      if (!$pago) {
        cd_fail('El registro de pago no existe.');
      }
      if ((int) $pago[0]['anulado'] === 1) {
        cd_fail('El documento está anulado; no se puede modificar.');
      }
      if (!cd_query($conn, "SELECT id FROM t_medios_pagos WHERE id = ?", 'i', [$idMedio])) {
        cd_fail('El medio de pago seleccionado no existe.');
      }
      if ((int) $pago[0]['id_medio_pago'] === $idMedio) {
        cd_ok([], 'El medio de pago no cambió.', ['sin_cambios' => true]);
      }

      cd_exec($conn, "UPDATE t_transacciones_pago SET id_medio_pago = ? WHERE id = ?", 'ii', [$idMedio, $idPago]);
      cd_ok([], 'Medio de pago actualizado correctamente.');
      break;

    case 'update-movimiento-cuenta':
      cd_usuario();
      $id     = cd_int('id');
      $cuenta = cd_str('cuenta', 30);
      if ($id <= 0 || $cuenta === '') {
        cd_fail('Datos incompletos para actualizar la cuenta.');
      }

      $mov = cd_query(
        $conn,
        "SELECT m.id, t.anulado
         FROM t_movimiento_contable m
         INNER JOIN t_transacciones t ON t.id = m.id_transaccion
         WHERE m.id = ?",
        'i',
        [$id]
      );
      if (!$mov) {
        cd_fail('El movimiento contable no existe.');
      }
      if ((int) $mov[0]['anulado'] === 1) {
        cd_fail('El documento está anulado; no se puede modificar.');
      }

      $cta = cd_query(
        $conn,
        "SELECT cuenta, UPPER(descripcion) AS descripcion FROM t_cuentas_contables WHERE cuenta = ? LIMIT 1",
        's',
        [$cuenta]
      );
      if (!$cta) {
        cd_fail('La cuenta contable no existe.');
      }

      cd_exec($conn, "UPDATE t_movimiento_contable SET cuenta = ? WHERE id = ?", 'si', [$cuenta, $id]);
      cd_ok(
        ['cuenta' => $cta[0]['cuenta'], 'texto' => $cta[0]['cuenta'] . ' - ' . $cta[0]['descripcion']],
        'Movimiento contable actualizado correctamente.'
      );
      break;

    default:
      cd_fail('Operación no válida.');
  }
} catch (Throwable $e) {
  error_log('[consultaDocumentos] ' . $e->getMessage());
  cd_fail('Error interno: ' . $e->getMessage());
}
