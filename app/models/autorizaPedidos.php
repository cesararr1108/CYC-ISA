<?php
session_start();
require_once('bd.php');
require_once('funciones.php');
require_once('../shared/servicios/servicios-email.php');
$conn = conectar();

switch ($_POST["op"]) {
    case 'consultar_pedidos':
        $fecha_inicial = $_POST['fecha_inicial'];
        $fecha_final = $_POST['fecha_final'];
        $estado = $_POST['estado'];
        $parametros = [];

        $sql = "SELECT
                    a.fecha_solicitud,
                    t.tratamiento,
                    t.razon_comercial,
                    p.valor_total,
                    a.observaciones,
                    a.observaciones_autoriza,
                    a.estado,
                    CASE
                        WHEN a.estado = 'P' THEN 'Pendiente'
                        WHEN a.estado = 'A' THEN 'Aprobada'
                        WHEN a.estado = 'R' THEN 'Rechazada'
                        ELSE 'Sin estado'
                    END AS desc_estado,
                    a.fecha_autorizacion,
                    a.id,
                    us.usuario AS usuario_solicita,
                    CONCAT_WS(' ', us.nombres, us.apellidos) AS nombres_usuario_solicita,
                    ua.usuario AS usuario_autoriza,
                    CONCAT_WS(' ', ua.nombres, ua.apellidos) AS nombres_usuario_autoriza,
                    p.id AS id_pedido,
                    t.id AS id_tercero
                FROM
                    t_pedidos_autorizaciones a
                INNER JOIN t_pedidos p 
                    ON p.id = a.id_pedido
                INNER JOIN t_terceros t 
                    ON t.id = p.id_tercero
                INNER JOIN t_usuarios us 
                    ON us.id = a.id_usuario_solicita
                LEFT JOIN t_usuarios ua 
                    ON ua.id = a.id_usuario_autoriza
                WHERE 1 = 1
            ";

        if (!empty($fecha_inicial) && !empty($fecha_final)) {
            $sql .= " AND a.fecha_solicitud BETWEEN ? AND ?";
            $parametros[] =array(
                'tipo'=>'s',
                'valor'=>$fecha_inicial
            );
            $parametros[] = array(
                'tipo' => 's',
                'valor' => $fecha_final
            );
        }

        if ($estado !== '') {
            $sql .= " AND a.estado = ?";
            $parametros[] = array(
                'tipo' => 's',
                'valor' => $estado
            );
        }
        $sql .= " ORDER BY a.fecha_solicitud DESC;";
  
        $result = GenerarArrayPrepare($sql, $parametros);
        echo json_encode($result);
        break;

    case 'autorizar_pedido':


        $id = intval($_POST['id']);
        $observaciones = isset($_POST['observaciones']) ? addslashes(trim($_POST['observaciones'])) : '';
        $idUsuario = intval($_SESSION['Ses_id']);

        if ($id <= 0) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Solicitud inválida.'
            ]);
            break;
        }
        $q = update('t_pedidos_autorizaciones', $fields = [
            'estado' => 'A',
            'fecha_autorizacion' => date('Y-m-d H:i:s'),
            'id_usuario_autoriza' => $idUsuario,
            'observaciones_autoriza' => $observaciones
        ], [
            "id" => $id,
            "estado" => 'P'
        ]);


        if ($q === false) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'No fue posible autorizar el pedido.'
            ]);
            break;
        }

        echo json_encode([
            'ok' => true,
            'mensaje' => 'Pedido autorizado correctamente.'
        ]);
        break;

    case 'rechazar_pedido':
        $id = intval($_POST['id']);
        $observaciones = isset($_POST['observaciones']) ? addslashes(trim($_POST['observaciones'])) : '';
        $idUsuario = intval($_SESSION['Ses_id']);

        if ($id <= 0) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Solicitud inválida.'
            ]);
            break;
        }

        /*$sql = "UPDATE t_pedidos_autorizaciones
                SET estado = 'R',
                    fecha_autorizacion = NOW(),
                    id_usuario_autoriza = $idUsuario,
                    observaciones_autoriza = '$observaciones'
                WHERE id = $id
                  AND estado = 'P'";

        $q = Query($sql);*/

        $q= update('t_pedidos_autorizaciones', $fields = [
            'estado' => 'R',
            'fecha_autorizacion' => date('Y-m-d H:i:s'),
            'id_usuario_autoriza' => $idUsuario,
            'observaciones_autoriza' => $observaciones
        ], [
            "id" => $id,
            "estado" => 'P'
        ]);

        if ($q === false) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'No fue posible rechazar el pedido.'
            ]);
            break;
        }

        echo json_encode([
            'ok' => true,
            'mensaje' => 'Pedido rechazado correctamente.'
        ]);
        break;
}
