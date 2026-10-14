<?php

session_start();
require_once('bd.php');
require_once('funciones.php');
$conn = conectar();
switch ($_POST["op"]) {
    case "crear-reserva":
        try {

            $conn->begin_transaction();

            $detalles = json_decode($_POST["detalles"] ?? '', true);

            if (!is_array($detalles) || empty($detalles)) {
                throw new Exception("Detalles inválidos");
            }

            if (!isset($_SESSION["Ses_id"])) {
                throw new Exception("Sesión no válida");
            }

            $id_bodega = (int) $_POST["id_bodega"];
            $id_tercero = (int) $_POST["id_tercero"];
            $id_usuario = (int) $_SESSION["Ses_id"];
            $notas = trim($_POST["notas"] ?? '');

            $data = [
                'id_bodega' => $id_bodega,
                'id_tercero' => $id_tercero,
                'id_usuario_crea' => $id_usuario,
                'notas' => $notas
            ];

            $id = insertReturnId('t_reservas', $data, true, $conn);

            if (!$id) {
                throw new Exception("No se pudo crear la reserva");
            }

            foreach ($detalles as $detalle) {

                if (!isset($detalle['idReferencia'], $detalle['idLote'], $detalle['cantidad'])) {
                    throw new Exception('Detalle de reserva incompleto');
                }

                $data_detalle = [
                    'id_reserva' => $id,
                    'id_referencia' => (int) $detalle['idReferencia'],
                    'id_lote' => (int) $detalle['idLote'],
                    'id_bodega' => $id_bodega,
                    'id_usuario_crea' => $id_usuario,
                    'cantidad' => (float) $detalle['cantidad']
                ];

                insertReturnId('t_reservas_inventario', $data_detalle, true, $conn);
            }

            $conn->commit();

            echo json_encode([
                'estado' => true,
                'message' => 'Reserva creada correctamente'
            ]);
        } catch (\Throwable $th) {

            $conn->rollback();

            echo json_encode([
                'estado' => false,
                'message' => $th->getMessage()
            ]);
        }
        break;
    case "listar-reservas":
        try {

            $id_bodega = intval($_POST["id_bodega"] ?? 0);
            $fecha_desde = $_POST["fecha_desde"] ?? '';
            $fecha_hasta = $_POST["fecha_hasta"] ?? '';
            $estado = $_POST["estado"] ?? '';

            $where = "r.id_bodega = $id_bodega";

            if (!empty($fecha_desde)) {
                $where .= " AND DATE(r.fecha_hora_crea) >= '$fecha_desde'";
            }

            if (!empty($fecha_hasta)) {
                $where .= " AND DATE(r.fecha_hora_crea) <= '$fecha_hasta'";
            }

            if ($estado !== '') {
                $estado = intval($estado);
                $where .= " AND r.estado = $estado";
            }

            $sql = "SELECT 
                    r.id AS id_reserva,
                    r.fecha_hora_crea AS fecha_reserva,
                    r.notas,
                    r.estado,
                    r.id_bodega,
                    alm.descripcion AS bodega_desc,
                    r.id_tercero,
                    t.razon_comercial AS tercero_nombre,
                    r.id_usuario_crea,
                    u.nombres AS usuario_nombre,
                    d.id AS id_detalle,
                    d.id_referencia,
                    d.estado AS estado_referencia,
                    ref.codigo,
                    ref.descripcion AS referencia_desc,
                    d.id_lote,
                    IFNULL(l.lote, 'N/A') AS lote,
                    IFNULL(l.vencimiento, 'N/A') AS vencimiento,
                    d.cantidad AS cantidad_reservada
                FROM t_reservas r
                INNER JOIN t_reservas_inventario d ON d.id_reserva = r.id
                INNER JOIN t_referencias ref ON ref.id = d.id_referencia
                INNER JOIN t_almacenes alm  ON alm.id = r.id_bodega
                LEFT JOIN t_referencias_lotes l  ON l.id = d.id_lote
                LEFT JOIN t_terceros t ON t.id = r.id_tercero
                LEFT JOIN t_usuarios u ON u.id = r.id_usuario_crea
                WHERE $where
                ORDER BY r.id DESC, d.id ASC";

            $reservas = GenerarArray($sql);

            echo json_encode([
                'error' => false,
                'data' => $reservas,
                'message' => 'Reservas cargadas correctamente'
            ]);
        } catch (\Throwable $th) {

            echo json_encode([
                'error' => true,
                'message' => $th->getMessage()
            ]);
        }
        break;

    case "eliminar-reserva":
        $id = intval($_POST["id"] ?? 0);
        if ($id > 0) {
            $data = [
                'estado' => 0
            ];
            $where = "id = $id";
            $resultado = Query("UPDATE t_reservas_inventario SET estado = 0 WHERE $where");
            if ($resultado) {
                echo json_encode(['estado' => true, 'message' => 'Reserva eliminada correctamente']);
            } else {
                echo json_encode(['estado' => false, 'message' => 'Error al eliminar la reserva']);
            }
        } else {
            echo json_encode(['estado' => false, 'message' => 'ID de reserva no válido']);
        }
        break;
    case "eliminar-reserva-total":
        $id = intval($_POST["id"] ?? 0);
        if ($id > 0) {
            $data = [
                'estado' => 0
            ];
            $where = "id_reserva = $id";
            $resultado = Query("UPDATE t_reservas_inventario SET estado = 0 WHERE $where");
            if ($resultado) {
                Query("UPDATE t_reservas SET estado = 0 WHERE id = $id");
                echo json_encode(['estado' => true, 'message' => 'Reserva eliminada completamente']);
            } else {
                echo json_encode(['estado' => false, 'message' => 'Error al eliminar la reserva']);
            }
        } else {
            echo json_encode(['estado' => false, 'message' => 'ID de reserva no válido']);
        }
        break;
}
