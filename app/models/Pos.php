<?php
session_start();
require_once('bd.php');
require_once('funciones.php');
// Se retiró servicios-email.php: ninguna operación de este modelo lo usa y cargarlo en cada petición tenía un costo.
$conn = db();   // conexión compartida: todas las consultas de esta petición usan la misma

header('Content-Type: application/json; charset=utf-8');

/* =========================================================
 * HELPERS
 * ========================================================= */
function responder($data)
{
    echo json_encode($data);
    exit;
}

function postInt($clave)
{
    return isset($_POST[$clave]) ? (int)$_POST[$clave] : 0;
}

function postFloat($clave)
{
    return isset($_POST[$clave]) ? (float)$_POST[$clave] : 0.0;
}

/** Fecha válida en formato Y-m-d o null */
function postFecha($clave)
{
    $v = isset($_POST[$clave]) ? trim($_POST[$clave]) : '';
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;
}

/** Deja solo caracteres seguros (letras, números, espacio, guion, punto) */
function limpiarTexto($valor, $max = 50)
{
    $v = preg_replace('/[^\p{L}\p{N}\s\-\.]/u', '', (string)$valor);
    return mb_substr(trim($v), 0, $max);
}

if (empty($_SESSION['Ses_id'])) {
    http_response_code(401);
    responder(['ok' => false, 'message' => 'La sesión ha expirado, ingrese nuevamente.']);
}

// Este modelo solo LEE la sesión: se libera el bloqueo para no frenar otras peticiones del usuario
session_write_close();

$op = $_POST['op'] ?? '';

switch ($op) {

    case 'validar-vendedor':
        // Antes: where nit=$codigo sin comillas ni limpieza (inyección SQL y sin uso de índice en nit varchar)
        $codigo = limpiarTexto($_POST['codigo'] ?? '', 20);
        if ($codigo === '') {
            responder([]);
        }
        $sql = "SELECT id, nit, razon_comercial, primer_nombre, primer_apellido
                FROM t_terceros
                WHERE nit = '$codigo'
                LIMIT 1";
        responder(GenerarArray($sql, ''));
        break;

    case 'insertarPago':
        try {
            $datos          = json_decode($_POST['pagos'] ?? '[]', true);
            $datos          = is_array($datos) ? $datos : [];
            $id_transaccion = postInt('id_transaccion');
            $id_usuario     = (int)$_SESSION['Ses_id'];

            if ($id_transaccion <= 0) {
                responder(['ok' => false, 'message' => 'Documento no válido']);
            }

            // Neto a pagar del encabezado (valor_total - retenciones); el servidor es la fuente de verdad
            $valor_total = 0;
            $encabezado = GenerarArray("SELECT
                                            IFNULL(valor_total,0)      AS valor_total,
                                            IFNULL(retencion_fuente,0) AS retencion_fuente,
                                            IFNULL(retencion_ica,0)    AS retencion_ica,
                                            IFNULL(retencion_iva,0)    AS retencion_iva
                                        FROM t_transacciones_tmp
                                        WHERE id = $id_transaccion
                                        LIMIT 1", '');
            if (count($encabezado) > 0) {
                $e = $encabezado[0];
                $valor_neto = (float)$e['valor_total'] - ((float)$e['retencion_fuente'] + (float)$e['retencion_ica'] + (float)$e['retencion_iva']);
                if ($valor_neto > 0) {
                    $valor_total = $valor_neto;
                }
            }

            // Lista blanca de campos: antes se insertaba lo que llegara del navegador
            $filas      = [];
            $pago_total = 0;
            foreach ($datos as $reg) {
                $fila = [
                    'id_transaccion' => $id_transaccion,
                    'id_medio_pago'  => (int)($reg['id_medio_pago'] ?? 0),
                    'valor'          => (float)($reg['valor'] ?? 0),
                    'tipo_tarjeta'   => (int)($reg['tipo_tarjeta'] ?? 0),
                    'voucher'        => limpiarTexto($reg['voucher'] ?? '', 20),
                    'numero'         => (int)($reg['numero'] ?? 0),
                    'tipo'           => limpiarTexto($reg['tipo'] ?? '', 20),
                    'cambio'         => (float)($reg['cambio'] ?? 0),
                    'id_usuario'     => $id_usuario,
                ];
                if ($fila['id_medio_pago'] <= 0 || $fila['valor'] <= 0) {
                    continue;
                }
                $pago_total += $fila['valor'];
                $filas[] = $fila;
            }

            // Se valida ANTES de tocar la BD (antes se borraba e insertaba y luego se validaba)
            if (abs($pago_total - $valor_total) > 5) {
                responder([
                    'ok' => false,
                    'message' => 'El valor pago es diferente al valor de la factura, Valor Pagado : ' . $pago_total . ', Valor facturado : ' . $valor_total
                ]);
            }

            // Borrado + inserción atómicos: si una fila falla no quedan pagos a medias
            transaccion(function ($db) use ($id_transaccion, $filas) {
                if (Query("DELETE FROM t_transacciones_pago_tmp WHERE id_transaccion = $id_transaccion", $db) === false) {
                    throw new Exception('No se pudieron limpiar los pagos anteriores');
                }
                foreach ($filas as $fila) {
                    if (!insertReturnId('t_transacciones_pago_tmp', $fila, false, $db)) {
                        throw new Exception('No se pudo registrar un pago');
                    }
                }
            });

            responder(['ok' => true, 'message' => 'Inserción completa']);
        } catch (\Throwable $th) {
            error_log('POS insertarPago: ' . $th->getMessage());
            responder(['ok' => false, 'message' => 'Error al registrar los pagos']);
        }
        break;

    case 'listar-facturas':
        $fecha_inicial = postFecha('fecha_inicial');
        $fecha_final   = postFecha('fecha_final');
        $idUser        = (int)$_SESSION['Ses_id'];

        $sql = "SELECT
                    d.id,
                    d.numero,
                    d.tipo,
                    d.valor_total,
                    t.nit,
                    CASE WHEN t.primer_nombre IS NULL THEN t.razon_comercial
                         ELSE CONCAT_WS(' ', t.primer_nombre, t.segundo_nombre, t.primer_apellido, t.segundo_apellido)
                    END AS cliente,
                    t.razon_comercial,
                    -- t.celular,  -- VERIFICAR nombre de columna: el envío por WhatsApp desde esta pestaña hoy llega sin celular
                    CAST(d.fecha_hora AS DATE) AS fecha,
                    IFNULL(t.email_fe,'') AS email,
                    IFNULL(r.cufe,'') AS cufe,
                    IFNULL(xx.documento_electronico,'N') AS documento_electronico
                FROM t_transacciones d
                INNER JOIN t_terceros t          ON t.id = d.id_tercero
                INNER JOIN t_tipos_documentos xx ON xx.id = d.id_tipo_documento
                LEFT  JOIN t_respuestas_dian r   ON r.tipo = d.tipo AND r.numero = d.numero
                WHERE d.sw = 5
                  AND d.id_usuario = $idUser
                  AND (d.anulado = 0 OR d.anulado IS NULL)";

        if ($fecha_inicial && $fecha_final) {
            // Rango sobre la columna sin CAST: permite usar el índice (sw, id_usuario, fecha_hora)
            $sql .= " AND d.fecha_hora >= '$fecha_inicial 00:00:00'
                      AND d.fecha_hora <  DATE_ADD('$fecha_final', INTERVAL 1 DAY)";
        }
        $sql .= " ORDER BY d.fecha_hora DESC";

        responder(GenerarArray($sql, ''));
        break;

    case 'eliminarPago':
        $id_transaccion = postInt('id_transaccion');
        if ($id_transaccion > 0) {
            Query("DELETE FROM t_transacciones_pago_tmp WHERE id_transaccion = $id_transaccion");
        }
        responder(['ok' => true]);
        break;

    case 'listar-comprometidos':
        $id_bodega   = postInt('id_bodega');
        $id_producto = postInt('id_producto');

        $sql = "SELECT
                    d.tipo,
                    tt.numero,
                    r.razon_comercial,
                    r.tratamiento,
                    tt.fecha_hora,
                    t.cantidad,
                    t.vmcto,
                    t.lote,
                    t.id_lote,
                    t.id_bodega,
                    t.id_referencia,
                    u.usuario
                FROM t_transacciones_detalle_tmp AS t
                INNER JOIN t_transacciones_tmp AS tt ON tt.id = t.id_transaccion
                INNER JOIN t_tipos_documentos  AS d  ON d.id  = tt.id_tipo_documento
                INNER JOIN t_terceros          AS r  ON r.id  = tt.id_tercero
                INNER JOIN t_usuarios          AS u  ON u.id  = tt.id_usuario
                WHERE t.id_referencia = $id_producto
                  AND t.id_bodega     = $id_bodega
                  AND t.sw IN (3,5,6,7)
                ORDER BY tt.fecha_hora DESC";
        responder(GenerarArray($sql, ''));
        break;

    case 'stock-otras-bodegas':
        $id_bodega   = postInt('id_bodega');
        $id_producto = postInt('id_producto');

        $sql = "SELECT
                    a.descripcion AS bodega,
                    SUM(s.stock)  AS stock_total
                FROM v_referencias_stock_lotes s
                INNER JOIN t_almacenes a ON a.id = s.id_bodega
                WHERE s.id_referencia = $id_producto
                  AND s.id_bodega <> $id_bodega
                GROUP BY s.id_bodega, a.descripcion
                HAVING SUM(s.stock) > 0
                ORDER BY a.descripcion";
        responder(GenerarArray($sql, ''));
        break;

    case 'guardar_retenciones':
        $id_transaccion        = postInt('id_transaccion');
        $ret_fte               = postFloat('ret_fte');
        $ret_ica               = postFloat('ret_ica');
        $ret_iva               = postFloat('ret_iva');
        $id_concepto_retencion = postInt('id_concepto_retencion');

        if ($id_transaccion <= 0) {
            responder(['ok' => false]);
        }

        Query("UPDATE t_transacciones_tmp
               SET retencion_fuente      = $ret_fte,
                   retencion_ica         = $ret_ica,
                   retencion_iva         = $ret_iva,
                   id_concepto_retencion = $id_concepto_retencion
               WHERE id = $id_transaccion");
        responder(['ok' => true]);
        break;

    default:
        http_response_code(400);
        responder(['ok' => false, 'message' => 'Operación no válida']);
}