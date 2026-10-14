<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

session_start();
require_once('bd.php');
require_once('funciones.php');

$conn = conectar();

function responseJson($error, $data = [], $mensaje = '')
{
    echo json_encode([
        'error' => $error,
        'data' => $data,
        'mensaje' => $mensaje
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$op = isset($_REQUEST['op']) ? trim($_REQUEST['op']) : '';

switch ($op) {
    case 'listar-formatos':
        $sql = "SELECT * FROM t_exogena_formatos WHERE estado = 'A' ORDER BY formato";
        $resp = GenerarArray($sql);
        echo json_encode($resp);
        break;

    case 'listar-conceptos':
        $formato = isset($_REQUEST['formato']) ? trim($_REQUEST['formato']) : '';
        $sql = "SELECT * FROM t_exogena_conceptos WHERE estado = 'A' AND formato = '" . $conn->real_escape_string($formato) . "' ORDER BY concepto";
        $resp = GenerarArray($sql);
        echo json_encode($resp);
        break;

    case 'listar-parametros':
        $formato = isset($_REQUEST['formato']) ? trim($_REQUEST['formato']) : '';
        $vigencia = isset($_REQUEST['vigencia']) ? trim($_REQUEST['vigencia']) : '';

        if ($formato === '' || $vigencia === '') {
            responseJson(true, [], 'Formato y vigencia son obligatorios');
        }

        $sql = "SELECT p.id, p.vigencia, p.formato, p.concepto, p.cuenta, p.naturaleza, UPPER(COALESCE(c.descripcion, '')) AS descripcion_cuenta
                FROM t_exogena_parametros p
                LEFT JOIN t_cuentas_contables c ON c.cuenta = p.cuenta
                WHERE p.formato = ? AND p.vigencia = ?
                ORDER BY p.concepto, p.cuenta";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            responseJson(true, [], 'Error al preparar la consulta de parámetros');
        }
        $stmt->bind_param('ss', $formato, $vigencia);
        if (!$stmt->execute()) {
            responseJson(true, [], 'Error al ejecutar la consulta de parámetros');
        }

        $result = $stmt->get_result();
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        echo json_encode($data);
        break;

    case 'i_parametro':
        $vigencia = isset($_REQUEST['vigencia']) ? trim($_REQUEST['vigencia']) : '';
        $formato = isset($_REQUEST['formato']) ? trim($_REQUEST['formato']) : '';
        $concepto = isset($_REQUEST['concepto']) ? trim($_REQUEST['concepto']) : '';
        $cuenta = isset($_REQUEST['cuenta']) ? trim($_REQUEST['cuenta']) : '';
        $naturaleza = isset($_REQUEST['naturaleza']) ? trim($_REQUEST['naturaleza']) : 'S';

        if ($vigencia === '' || $formato === '' || $concepto === '' || $cuenta === '') {
            responseJson(true, [], 'Los campos vigencia, formato, concepto y cuenta son obligatorios');
        }

        $stmt = $conn->prepare("INSERT INTO t_exogena_parametros (vigencia, formato, concepto, cuenta, naturaleza) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            responseJson(true, [], 'Error al preparar la inserción');
        }

        $stmt->bind_param('sssss', $vigencia, $formato, $concepto, $cuenta, $naturaleza);
        if (!$stmt->execute()) {
            if ($stmt->errno === 1062) {
                responseJson(true, [], 'Ya existe un parámetro con la misma vigencia, formato, concepto y cuenta');
            }
            responseJson(true, [], 'Error al insertar el parámetro');
        }

        responseJson(false, ['id' => $stmt->insert_id], 'Parámetro creado correctamente');
        break;

    case 'u_parametro':
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        $vigencia = isset($_REQUEST['vigencia']) ? trim($_REQUEST['vigencia']) : '';
        $formato = isset($_REQUEST['formato']) ? trim($_REQUEST['formato']) : '';
        $concepto = isset($_REQUEST['concepto']) ? trim($_REQUEST['concepto']) : '';
        $cuenta = isset($_REQUEST['cuenta']) ? trim($_REQUEST['cuenta']) : '';
        $naturaleza = isset($_REQUEST['naturaleza']) ? trim($_REQUEST['naturaleza']) : 'S';

        if ($id <= 0 || $vigencia === '' || $formato === '' || $concepto === '' || $cuenta === '') {
            responseJson(true, [], 'ID, vigencia, formato, concepto y cuenta son obligatorios');
        }

        $stmt = $conn->prepare("UPDATE t_exogena_parametros SET vigencia = ?, formato = ?, concepto = ?, cuenta = ?, naturaleza = ? WHERE id = ?");
        if (!$stmt) {
            responseJson(true, [], 'Error al preparar la actualización');
        }

        $stmt->bind_param('sssssi', $vigencia, $formato, $concepto, $cuenta, $naturaleza, $id);
        if (!$stmt->execute()) {
            if ($stmt->errno === 1062) {
                responseJson(true, [], 'Ya existe un parámetro con la misma vigencia, formato, concepto y cuenta');
            }
            responseJson(true, [], 'Error al actualizar el parámetro');
        }

        responseJson(false, ['id' => $id], 'Parámetro actualizado correctamente');
        break;

    case 'd_parametro':
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        if ($id <= 0) {
            responseJson(true, [], 'ID de parámetro inválido');
        }

        $stmt = $conn->prepare('DELETE FROM t_exogena_parametros WHERE id = ?');
        if (!$stmt) {
            responseJson(true, [], 'Error al preparar la eliminación');
        }
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) {
            responseJson(true, [], 'Error al eliminar el parámetro');
        }

        responseJson(false, ['id' => $id], 'Parámetro eliminado correctamente');
        break;

    case 'copiar-parametros':
        $formato = isset($_REQUEST['formato']) ? trim($_REQUEST['formato']) : '';
        $from = isset($_REQUEST['from_vigencia']) ? trim($_REQUEST['from_vigencia']) : '';
        $to = isset($_REQUEST['to_vigencia']) ? trim($_REQUEST['to_vigencia']) : '';

        if ($formato === '' || $from === '' || $to === '') {
            responseJson(true, [], 'Formato, vigencia origen y destino son obligatorios');
        }

        if ($from === $to) {
            responseJson(true, [], 'La vigencia origen y destino deben ser diferentes');
        }

        $conn->begin_transaction();
        try {
            $sql = "SELECT concepto, cuenta, naturaleza FROM t_exogena_parametros WHERE formato = ? AND vigencia = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $conn->rollback();
                responseJson(true, [], 'Error al preparar la consulta de parámetros origen');
            }
            $stmt->bind_param('ss', $formato, $from);
            if (!$stmt->execute()) {
                $conn->rollback();
                responseJson(true, [], 'Error al ejecutar consulta de parámetros origen');
            }

            $result = $stmt->get_result();
            if (!$result || $result->num_rows === 0) {
                $conn->rollback();
                responseJson(true, [], 'No hay parámetros para copiar en la vigencia origen');
            }

            $checkStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM t_exogena_parametros WHERE formato = ? AND vigencia = ? AND concepto = ? AND cuenta = ?");
            if (!$checkStmt) {
                $conn->rollback();
                responseJson(true, [], 'Error al preparar comprobación de duplicados');
            }

            $insertStmt = $conn->prepare("INSERT INTO t_exogena_parametros (vigencia, formato, concepto, cuenta, naturaleza) VALUES (?, ?, ?, ?, ?)");
            if (!$insertStmt) {
                $conn->rollback();
                responseJson(true, [], 'Error al preparar inserción de parámetros');
            }

            $copiados = 0;
            $omitidos = 0;

            while ($row = $result->fetch_assoc()) {
                $concepto = $row['concepto'];
                $cuenta = $row['cuenta'];
                $naturaleza = isset($row['naturaleza']) ? $row['naturaleza'] : null;

                $checkStmt->bind_param('ssss', $formato, $to, $concepto, $cuenta);
                if (!$checkStmt->execute()) {
                    $conn->rollback();
                    responseJson(true, [], 'Error al comprobar existencia de parámetro');
                }
                $r = $checkStmt->get_result()->fetch_assoc();
                if ($r && intval($r['cnt']) > 0) {
                    $omitidos++;
                    continue;
                }

                $insertStmt->bind_param('sssss', $to, $formato, $concepto, $cuenta, $naturaleza);
                if (!$insertStmt->execute()) {
                    if ($insertStmt->errno === 1062) {
                        $omitidos++;
                        continue;
                    }
                    $conn->rollback();
                    responseJson(true, [], 'Error al insertar parámetro durante la copia');
                }

                $copiados++;
            }

            $conn->commit();
            responseJson(false, ['copiados' => $copiados, 'omitidos' => $omitidos], 'Copia finalizada correctamente');
        } catch (Exception $e) {
            $conn->rollback();
            responseJson(true, [], 'Error interno al copiar parámetros');
        }

        break;

    default:
        responseJson(true, [], 'Operación no válida');
        break;
}
