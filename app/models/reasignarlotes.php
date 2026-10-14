<?php
session_start();
require_once('bd.php');
require_once('funciones.php');
$conn = conectar();

switch($_POST["op"]){
    
    /**
     * Búsqueda de lotes en t_referencias_lotes
     * Permite buscar por: referencia, lote, o ambos
     */
    case "buscar_lote":
        $id_referencia = $_POST["id_referencia"] ?? '';
        $lote = $_POST["lote"] ?? '';
        
        $id_referencia = trim($id_referencia);
        $lote = trim($lote);
        
        if(empty($id_referencia) && empty($lote)) {
            echo json_encode(array('ok' => false, 'message' => 'Ingrese una referencia o lote a buscar'));
            exit;
        }

        $sql = "SELECT 
                    id,
                    lote,
                    id_referencia,
                    vencimiento
                FROM t_referencias_lotes
                WHERE 1=1";
        
        // Agregar condiciones según los parámetros
        if(!empty($id_referencia)) {
            $id_referencia = intval($id_referencia);
            $sql .= " AND id_referencia = $id_referencia";
        }
        
        if(!empty($lote)) {
            $lote_escaped = $conn->real_escape_string($lote);
            $sql .= " AND lote LIKE '%$lote_escaped%'";
        }
        
        $sql .= " ORDER BY id_referencia DESC, vencimiento DESC LIMIT 50";
        
        $result = $conn->query($sql);
        $datos = array();
        
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $datos[] = $row;
            }
            echo json_encode(array('ok' => true, 'data' => $datos, 'count' => count($datos)));
        } else {
            echo json_encode(array('ok' => false, 'message' => 'No se encontraron resultados', 'data' => array()));
        }
    break;

    /**
     * Obtener transacciones/movimientos de un lote específico con todos los detalles
     */
    case "obtener_transacciones":
        $id_lote = $_POST["id_lote"] ?? 0;
        $id_referencia = $_POST["id_referencia"] ?? 0;
        $lote = $_POST["lote"] ?? '';
        
        if(empty($id_lote) || empty($id_referencia)) {
            echo json_encode(array('ok' => false, 'message' => 'Parámetros inválidos'));
            exit;
        }

        $sql = "SELECT 
                    a.descripcion as bodega,
                    d.tipo,
                    d.descripcion as transaccion,
                    x.fecha_hora,
                    CAST(t.cantidad AS DECIMAL(10,0)) as cantidad,
                    t.und,
                    t.lote,
                    t.vmcto as vencimiento
                FROM t_transacciones_detalle t 
                INNER JOIN t_transacciones x ON x.id = t.id_transaccion
                INNER JOIN t_tipos_documentos d ON d.id = x.id_tipo_documento
                INNER JOIN t_almacenes a ON a.id = t.id_bodega
                INNER JOIN t_referencias r ON r.id = t.id_referencia 
                WHERE t.lote = ? 
                AND t.id_referencia = ?
                ORDER BY x.fecha_hora DESC";
        
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $lote, $id_referencia);
        $stmt->execute();
        $result = $stmt->get_result();
        $datos = array();
        
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $datos[] = $row;
            }
        }
        
        echo json_encode(array('ok' => true, 'data' => $datos, 'count' => count($datos)));
    break;

    /**
     * Guardar la reasignación de lote
     * Actualiza tanto t_referencias_lotes como t_transacciones_detalle más auditoría
     */
    case "guardar_reasignacion":
        try {
            $id_referencia = $_POST["id_referencia"] ?? 0;
            $id_lote = $_POST["id_lote"] ?? 0;
            $lote_actual = $_POST["lote_actual"] ?? '';
            $vencimiento_actual = $_POST["vencimiento_actual"] ?? '';
            $nuevo_lote = $_POST["nuevo_lote"] ?? '';
            $nuevo_vencimiento = $_POST["nuevo_vencimiento"] ?? '';
            
            $usuario_id = $_SESSION["Ses_id"] ?? 0;
            
            if(!$id_referencia || !$id_lote || !$nuevo_lote || !$nuevo_vencimiento) {
                echo json_encode(array('ok' => false, 'message' => 'Parámetros inválidos'));
                exit;
            }

            // Iniciar transacción
            $conn->begin_transaction();

            // 1. Actualizar t_referencias_lotes
            $sql_update_ref = "UPDATE t_referencias_lotes 
                               SET lote = ?, vencimiento = ? 
                               WHERE id = ? AND id_referencia = ?";
            
            $stmt = $conn->prepare($sql_update_ref);
            $stmt->bind_param("ssii", $nuevo_lote, $nuevo_vencimiento, $id_lote, $id_referencia);
            
            if(!$stmt->execute()) {
                throw new Exception("Error al actualizar t_referencias_lotes: " . $stmt->error);
            }

            // 2. Actualizar t_transacciones_detalle
            $sql_update_trans = "UPDATE t_transacciones_detalle 
                                SET lote = ?, vmCto = ? 
                                WHERE id_lote = ? AND id_referencia = ?";
            
            $stmt2 = $conn->prepare($sql_update_trans);
            $stmt2->bind_param("ssii", $nuevo_lote, $nuevo_vencimiento, $id_lote, $id_referencia);
            
            if(!$stmt2->execute()) {
                throw new Exception("Error al actualizar t_transacciones_detalle: " . $stmt2->error);
            }

            $registros_afectados = $stmt2->affected_rows;
          
            // 3. Registrar en t_auditoria para t_referencias_lotes
            $detalle_ref = json_encode([
                "id_referencia" => $id_referencia,
                "lote_anterior" => $lote_actual,
                "lote_nuevo" => $nuevo_lote,
                "vencimiento_anterior" => $vencimiento_actual,
                "vencimiento_nuevo" => $nuevo_vencimiento
            ]);

            $sql_aud_ref = "INSERT INTO t_auditoria (usuario_id, accion, tabla_afectada, id_afectado, detalle) 
                            VALUES (?, 'UPDATE', 't_referencias_lotes', ?, ?)";
            
            $stmt_aud = $conn->prepare($sql_aud_ref);
            $stmt_aud->bind_param("iss", $usuario_id, $id_lote, $detalle_ref);
            
            if(!$stmt_aud->execute()) {
                throw new Exception("Error al registrar auditoría: " . $stmt_aud->error);
            }

            // 4. Registrar en t_auditoria para t_transacciones_detalle
            $detalle_trans = json_encode([
                "id_referencia" => $id_referencia,
                "id_lote" => $id_lote,
                "lote_anterior" => $lote_actual,
                "lote_nuevo" => $nuevo_lote,
                "vencimiento_anterior" => $vencimiento_actual,
                "vencimiento_nuevo" => $nuevo_vencimiento,
                "registros_afectados" => $registros_afectados
            ]);

            $sql_aud_trans = "INSERT INTO t_auditoria (usuario_id, accion, tabla_afectada, id_afectado, detalle) 
                              VALUES (?, 'UPDATE', 't_transacciones_detalle', ?, ?)";
            
            $stmt_aud2 = $conn->prepare($sql_aud_trans);
            $stmt_aud2->bind_param("iss", $usuario_id, $id_lote, $detalle_trans);
            
            if(!$stmt_aud2->execute()) {
                throw new Exception("Error al registrar auditoría de transacciones: " . $stmt_aud2->error);
            }

            // Commit
            $conn->commit();

            echo json_encode(array(
                'ok' => true, 
                'message' => "Reasignación completada. Se actualizaron $registros_afectados movimientos.",
                'registros_afectados' => $registros_afectados
            ));

        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(array('ok' => false, 'message' => 'Error: ' . $e->getMessage()));
        }
    break;

    /**
     * Obtener historial de auditoría
     */
case "obtener_historial":

    $filtro = $_POST["filtro"] ?? '';
    $filtro = trim($filtro);

    $sql = "SELECT 
                a.id,
                a.fecha,
                u.nombres,
                u.apellidos,
                u.email,
                a.accion,
                a.tabla_afectada,
                a.detalle,

                r.descripcion AS referencia_descripcion,
                r.codigo AS referencia_codigo

            FROM t_auditoria a

            LEFT JOIN t_usuarios u 
                ON u.id = a.usuario_id

            LEFT JOIN t_referencias r 
                ON r.id = JSON_UNQUOTE(
                    JSON_EXTRACT(a.detalle, '$.id_referencia')
                )

            WHERE a.tabla_afectada IN (
                't_referencias_lotes',
                't_transacciones_detalle'
            )

            AND a.accion = 'UPDATE'";


    /*
     * Filtro de búsqueda
     *
     * Permite buscar por:
     * - Nombre del usuario
     * - Apellido del usuario
     * - Email
     * - Acción
     * - Código de referencia
     * - Descripción de referencia
     */
    if (!empty($filtro)) {

        /*
         * Escapar el filtro para evitar problemas
         * con comillas u otros caracteres.
         */
        $filtro_sql = $conn->real_escape_string($filtro);

        $sql .= " AND (
                    CONCAT(u.nombres, ' ', u.apellidos) LIKE '%$filtro_sql%'
                    OR a.accion LIKE '%$filtro_sql%'
                    OR u.email LIKE '%$filtro_sql%'
                    OR r.codigo LIKE '%$filtro_sql%'
                    OR r.descripcion LIKE '%$filtro_sql%'
                )";
    }


    /*
     * Ordenar por fecha más reciente
     */
    $sql .= " ORDER BY a.fecha DESC LIMIT 100";


    /*
     * Ejecutar consulta
     */
    $result = $conn->query($sql);

    $datos = array();


    /*
     * Validar resultado de la consulta
     */
    if ($result && $result->num_rows > 0) {

        while ($row = $result->fetch_assoc()) {


            /*
             * Parsear el detalle JSON
             */
            $detalle_obj = json_decode($row['detalle'], true);


            /*
             * Si el JSON no es válido,
             * crear un array vacío.
             */
            if (!is_array($detalle_obj)) {
                $detalle_obj = array();
            }


            /*
             * Nombre completo del usuario
             */
            $detalle_obj['usuario_nombre'] = trim(
                ($row['nombres'] ?? '') . ' ' .
                ($row['apellidos'] ?? '')
            );


            /*
             * Agregar información de la referencia.
             *
             * Si el JSON tiene id_referencia y existe
             * la referencia en t_referencias, el JOIN
             * ya nos entrega estos datos.
             *
             * Si no existe id_referencia, quedarán vacíos.
             */
            $detalle_obj['referencia_descripcion'] =
                $row['referencia_descripcion'] ?? '';

            $detalle_obj['referencia_codigo'] =
                $row['referencia_codigo'] ?? '';


            /*
             * Guardar el objeto procesado
             */
            $row['detalle_obj'] = $detalle_obj;


            /*
             * Agregar al resultado
             */
            $datos[] = $row;
        }


        /*
         * Respuesta exitosa con datos
         */
        echo json_encode(array(
            'ok'   => true,
            'data' => $datos
        ));

    } else {

        /*
         * No hay registros
         */
        echo json_encode(array(
            'ok'   => true,
            'data' => array()
        ));
    }

break;

    default:
        echo json_encode(array('ok' => false, 'message' => 'Operación no definida'));
    break;
}

$conn->close();
?>
