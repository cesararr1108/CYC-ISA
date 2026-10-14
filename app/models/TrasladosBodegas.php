<?php
session_start();
require_once('bd.php');
require_once('funciones.php');
$conn = conectar();

switch ($_POST['op']) {
    case "traslados_pendientes":
        $bodega = $_POST['bodega'];
        $sql = "SELECT	  
          ttr.fecha,
          DATE_FORMAT(ttr.fecha_hora, '%H:%i')   AS hora,
          ttr.numero,
          SUM(CAST(ttd.cantidad/ttd.can_und AS decimal(19,0))) as cantidad_enviada,
          SUM(CAST(ttd.cantidad as decimal(19,0))) as cantidad_real,
          a.descripcion as bodega_origen,
          a2.descripcion as bodega_destino,
          u.usuario 
       FROM t_traslados_transito tt
       INNER JOIN t_transacciones_detalle ttd ON ttd.id = tt.id_transacciones_detalle
       INNER JOIN t_transacciones   ttr ON ttr.id = ttd.id_transaccion
       INNER JOIN t_almacenes       a   ON a.id   = ttr.id_bodega 
       INNER JOIN t_almacenes       a2  ON a2.id  = tt.id_bodega_destino
       INNER JOIN t_usuarios         u   ON u.id   = ttr.id_usuario
       WHERE tt.id_bodega_destino = $bodega 
       GROUP BY ttr.numero, ttr.fecha, a.descripcion, a2.descripcion, u.usuario ,  ttr.fecha_hora
       ORDER BY ttr.fecha DESC";

        echo json_encode(GenerarArray($sql));
        break;
    case 'traslados_pendientes_detalle':
        $bodega = $_POST['bodega'];
        $numero = $_POST['numero'];

        $sql = "SELECT	
		  ttd.id as id_det , 
          ttd.fecha_hora as fecha,
          ttr.numero,
          CAST(ttd.cantidad/ttd.can_und AS decimal(19,0)) AS cantidad_enviada,
          CAST(ifnull(vv.cantidad_recibida,0) AS decimal(19,0)) AS cantidad_recibida,
          CAST(ttd.cantidad  AS decimal(19,0)) AS cantidad_real,
          ttd.und,
          ttd.can_und ,
          r.codigo,
          r.id as id_referencia,
          r.descripcion,
          ttd.id_lote,
          ttd.lote,
          ttd.vmcto,
          ifnull(m.descripcion,'') as laboratorio,
          a.descripcion as bodega_origen,
          a2.descripcion as bodega_destino,
          u.usuario 
       FROM t_traslados_transito tt
       INNER JOIN t_transacciones_detalle ttd ON ttd.id = tt.id_transacciones_detalle
       LEFT  JOIN v_traslados_transito_recibidos vv ON vv.id_transacciones_detalle = ttd.id
       INNER JOIN t_transacciones   ttr ON ttr.id = ttd.id_transaccion
       INNER JOIN t_referencias     r   ON r.id   = ttd.id_referencia
       INNER JOIN t_almacenes       a   ON a.id   = ttr.id_bodega 
       INNER JOIN t_almacenes       a2  ON a2.id  = tt.id_bodega_destino
       LEFT JOIN t_marcas           m   ON m.id   = r.id_marca 
       INNER JOIN t_usuarios         u   ON u.id   = ttr.id_usuario
       WHERE tt.id_bodega_destino = $bodega AND ttr.numero = '$numero' 
       ORDER BY r.descripcion DESC";

        echo json_encode(GenerarArray($sql));

        break;
    case "procesar_traslado_pendiente":
        $conn->begin_transaction();

        try {

            $detalles = json_decode($_POST['detalles'], true);
            $id_bodega_destino = $_POST['id_bodega_destino'];

            foreach ($detalles as $detalle) {

                $sql = "
            INSERT INTO t_transacciones_detalle(
                id_transaccion,
                id_referencia,
                cantidad,
                valor_unitario,
                costo_unitario,
                id_lote,
                pcj_iva,
                pcj_descuento,
                und,
                can_und,
                vmcto,
                lote,
                sw,
                id_bodega,
                id_pedido,
                impoconsumo,
                icui,
                ibua,
                id_transaccion_ref,
                fec,
                fecha_hora
            )
            SELECT 
                tt.id_transaccion,
                ttd.id_referencia,
                " . $detalle["cantidad"] . ", 
                ttd.valor_unitario,
                ttd.costo_unitario,
                ttd.id_lote,
                ttd.pcj_iva,
                ttd.pcj_descuento,
                ttd.und,
                ttd.can_und,
                ttd.vmcto,
                ttd.lote,
                ttd.sw,
                $id_bodega_destino,
                ttd.id_pedido,
                ttd.impoconsumo,
                ttd.icui,
                ttd.ibua,
                ttd.id_transaccion_ref,
                ttd.fec,
                CURRENT_TIMESTAMP()
            FROM t_traslados_transito tt
            INNER JOIN t_transacciones_detalle ttd 
                ON ttd.id = tt.id_transacciones_detalle
            WHERE tt.id_transacciones_detalle = {$detalle['id_det']}
        ";

                if (!$conn->query($sql)) {
                    throw new Exception($conn->error);
                }
            }

            // Si todo salió bien 
            $conn->commit();
        } catch (Exception $e) {

            // Algo falló → deshacer todo 
            $conn->rollback();
            echo "Error en la transacción: " . $e->getMessage();
        }

        break;
    case "recibir_traslado":

        $conn->begin_transaction();

        try {

            $id_bodega_destino = $_POST['id_bodega_destino'];
            $detalles = json_decode($_POST['items'], true);


            if (empty($detalles)) {
                throw new Exception("No se recibieron detalles para procesar el traslado.");
            }
            // preparar llamada al procedimiento UNA SOLA VEZ
            $stmt = $conn->prepare("CALL sp_insertar_traslado_detalle(?, ?, ?)");

            if (!$stmt) {
                throw new Exception("Error preparando SP: " . $conn->error);
            }

            foreach ($detalles as $detalle) {

                $cantidad = (float)$detalle['cantidad'];
                $id_det   = (int)$detalle['id_det'];
                $id_bodega_destino = (int)$id_bodega_destino;

                // bind de parámetros
                $stmt->bind_param("idi", $id_bodega_destino, $cantidad, $id_det);

                if (!$stmt->execute()) {
                    throw new Exception("Error al ejecutar SP: " . $stmt->error);
                }

                // MUY IMPORTANTE al llamar SP en loop
                // limpiar resultados pendientes
                while ($conn->more_results() && $conn->next_result()) {;
                }
            }

            $stmt->close();
            $conn->commit();

            echo json_encode([
                "ok" => true,
                "message" => "Traslado recibido exitosamente."
            ]);
        } catch (Exception $e) {

            $conn->rollback();

            echo json_encode([
                "ok" => false,
                "message" => $e->getMessage()
            ]);
        }

        break;
    case "consultar-traslados":
        $bodega = $_SESSION['Ses_id_bodega'];
        $fh_ini = $_POST['fh_ini'];
        $fh_fin = $_POST['fh_fin'];
       // $usuario = $_SESSION['Ses_usuario'];
        $sql = "SELECT 
                    t.id,
                    t.tipo ,
                    t.numero ,
                    t.fecha ,
                    t.fecha_hora,
                    t.id_bodega,
                    t.id_bodega_destino,
                    o.descripcion  AS b_origen,
                    d.descripcion  AS destino,
                    u.usuario AS usuario_generador,
                    COUNT(td.id) AS cantidad_items,
                    IFNULL(t.notas, '') AS notas,
                    t.sw,
                    CASE
                        WHEN t.id_bodega = $bodega THEN 'SALIDA'
                        WHEN t.id_bodega_destino = $bodega THEN 'ENTRADA'
                    END AS tipo_movimiento
                FROM
                    t_transacciones t
                    INNER JOIN  t_almacenes o ON o.id = t.id_bodega 
                    INNER JOIN  t_almacenes d ON d.id = t.id_bodega_destino 
                    INNER JOIN  t_usuarios u ON u.id = t.id_usuario
                    LEFT JOIN t_transacciones_detalle td ON td.id_transaccion = t.id
                WHERE
                    t.sw IN(3)
                    AND CAST(t.fecha as DATE) BETWEEN CAST('$fh_ini'  AS DATE) AND CAST('$fh_fin'  AS DATE)
                    AND (t.id_bodega = $bodega OR t.id_bodega_destino = $bodega)
                GROUP BY t.id, t.tipo, t.numero, t.fecha, t.fecha_hora, t.id_bodega, t.id_bodega_destino, o.descripcion, d.descripcion, u.usuario, t.notas, t.sw ";
        echo json_encode(GenerarArray($sql));
        break;
    case "detalle-traslado":
        $id_traslado = $_POST['id_traslado'];
        $sql = "SELECT 
                    td.id,
                    r.codigo,
                    r.descripcion,
                    CAST(td.cantidad / td.can_und AS DECIMAL(19,0)) AS cantidad,
                    td.und as unidad,
                    td.can_und,
                    td.costo_unitario as costo,
                    IFNULL(m.descripcion, '') AS laboratorio,
                    td.id_lote,
                    td.lote,
                    td.vmcto,
                    td.sw,
                    td.id_referencia,
                    td.id_bodega,
                    month(t.fecha_hora) as mes,
                    year(t.fecha_hora) as ano,
                    t.id_bodega_destino,
                    ifnull((
                            SELECT 
                               stock
                            FROM v_referencias_stock_lotes
                            WHERE id_referencia = td.id_referencia
                            AND id_bodega       = td.id_bodega
                            AND id_lote         = td.id_lote
                    ),0) as stock_lote
                FROM
                    t_transacciones_detalle td
                    INNER JOIN t_referencias    r ON r.id = td.id_referencia
                    LEFT JOIN t_marcas          m ON m.id = r.id_marca
                    INNER JOIN t_transacciones  t ON t.id = td.id_transaccion
                WHERE
                    td.id_transaccion = $id_traslado
                ORDER BY r.descripcion ASC";
              
        echo json_encode(GenerarArray($sql));
        break;
    case "traslados-recibidos":
    // Leer traslados recibidos desde la tabla de alertas
    $bodega_sesion     = (int) $_SESSION['Ses_id_bodega'];
    $id_usuario_sesion = (int) $_SESSION['Ses_id'];

    $sql = "SELECT t.id, t.numero, t.fecha, t.id_bodega, t.id_bodega_destino, t.id_usuario,
                   o.descripcion AS b_origen,
                   d.descripcion AS destino,
                   u.usuario     AS usuario_generador,
                   a.id_alerta, a.fecha_envio, a.fecha_visto
            FROM t_alertas a
            INNER JOIN t_transacciones t ON t.id = a.id_transaccion
            INNER JOIN t_almacenes o     ON o.id = t.id_bodega
            INNER JOIN t_almacenes d     ON d.id = t.id_bodega_destino
            INNER JOIN t_usuarios u      ON u.id = t.id_usuario
            WHERE a.transaccion = 'traslados'
              AND a.id_bodega = ?
              AND a.id_usuario <> ?
              -- AND a.fecha_visto IS NULL   -- descomenta si solo quieres los no vistos
            ORDER BY a.fecha_envio DESC";

    $traslados = [];
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ii", $bodega_sesion, $id_usuario_sesion);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $traslados[] = $row;
        }
        $stmt->close();
    }

    echo json_encode($traslados);
    break;
    case "traslados-salidas":
        try {
            //code...        
            // cuenta los traslados hechos desde mi bodega , por el administrador  
        $empresa = $_SESSION['Ses_empresa'];
        $bodega_sesion = $_SESSION['Ses_id_bodega'];
        $id_usuario_sesion = $_SESSION['Ses_id'];
        //VALIDO LAS ALERTAS CON BODEGA ACTUAL  
        $sql="SELECT 
                * 
              FROM t_alertas
              WHERE ID_BODEGA = ?  AND ID_USUARIO<>?
        ";
        $parametros = array();

        $parametros []=array(
            'tipo'=>'i',
            'valor'=>$bodega_sesion
        );
        $parametros []=array(
            'tipo'=>'i',
            'valor'=>$id_usuario_sesion
        );

        $traslados = GenerarArrayPrepare($sql,$parametros);

        echo json_encode($traslados);
        
        } catch (\Throwable $th) {
            //throw $th;
            echo json_encode(['error'=>($th->getMessage())]);
        }

    break;
    case "eliminar-alerta":
        $id_alerta = (int) $_POST['id'];

        $stmt = $conn->prepare("DELETE FROM t_alertas WHERE id_alerta = ?");
        $stmt->bind_param("i", $id_alerta);
        $ok = $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => $ok]);
    break;
    case "guardar-traslado-json":
        // Guardar traslado en JSON
        $id_traslado = $_POST['id_traslado'];
        $empresa = $_SESSION['Ses_empresa'];
        $bodega_sesion = $_SESSION['Ses_id_bodega'];
        $id_usuario_sesion = $_SESSION['Ses_id'];
        
        // Crear directorio con ruta absoluta (en dev/files)
        $basePath = dirname(dirname(__DIR__)) . '/files/' . $empresa;
        if (!is_dir($basePath)) {
            if (!mkdir($basePath, 0777, true)) {
                echo json_encode(['ok' => false, 'message' => 'No se pudo crear el directorio']);
                break;
            }
        }
        
        $jsonFile = $basePath . '/traslados_recibidos.json';
        
        // Leer JSON existente
        $traslados = [];
        if (file_exists($jsonFile)) {
            $jsonContent = file_get_contents($jsonFile);
            $traslados = json_decode($jsonContent, true) ?? [];
        }
        
        // Verificar si el traslado ya existe
        $existe = false;
        foreach ($traslados as $t) {
            if ($t['id'] == $id_traslado) {
                $existe = true;
                break;
            }
        }
        
        // Agregar traslado si no existe
        if (!$existe) {
            // Obtener datos del traslado con validación de bodega y usuario
            $sql = "SELECT t.id, t.numero, t.fecha, t.id_bodega, t.id_bodega_destino, t.id_usuario,
                           o.descripcion AS b_origen, d.descripcion AS destino, 
                           u.usuario AS usuario_generador FROM t_transacciones t
                    INNER JOIN t_almacenes o ON o.id = t.id_bodega 
                    INNER JOIN t_almacenes d ON d.id = t.id_bodega_destino 
                    INNER JOIN t_usuarios u ON u.id = t.id_usuario
                    WHERE t.id = $id_traslado";
            
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                $traslado = $result->fetch_assoc();
                
                // Validar que: bodega salida ≠ sesión AND bodega destino = sesión AND usuario ≠ sesión
                if ($traslado['id_bodega'] != $bodega_sesion ) {
                    $traslados[] = $traslado;
                    
                    // Guardar JSON
                    if (file_put_contents($jsonFile, json_encode($traslados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                        echo json_encode(['ok' => true, 'message' => 'Traslado guardado']);
                    } else {
                        echo json_encode(['ok' => false, 'message' => 'Error escribiendo archivo JSON']);
                    }
                } else {
                    echo json_encode(['ok' => false, 'message' => 'El traslado no es válido para esta bodega']);
                }
            } else {
                echo json_encode(['ok' => false, 'message' => 'Traslado no encontrado']);
            }
        } else {
            echo json_encode(['ok' => true, 'message' => 'Traslado ya guardado']);
        }
        break;
    case "eliminar-traslado-json":
        // Eliminar traslado del JSON
        $id_traslado = $_POST['id_traslado'];
        $empresa = $_SESSION['Ses_empresa'];
        
        $jsonFile = dirname(dirname(__DIR__)) . '/files/' . $empresa . '/traslados_recibidos.json';
        
        if (file_exists($jsonFile)) {
            $jsonContent = file_get_contents($jsonFile);
            $traslados = json_decode($jsonContent, true) ?? [];
            
            // Eliminar traslado por ID
            $traslados = array_filter($traslados, function($t) use ($id_traslado) {
                return $t['id'] != $id_traslado;
            });
            
            // Reindexar array
            $traslados = array_values($traslados);
            
            // Guardar JSON actualizado
            if (file_put_contents($jsonFile, json_encode($traslados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                echo json_encode(['ok' => true, 'message' => 'Traslado eliminado']);
            } else {
                echo json_encode(['ok' => false, 'message' => 'Error escribiendo archivo JSON']);
            }
        } else {
            echo json_encode(['ok' => true, 'message' => 'Archivo no encontrado']);
        }
        break;
    case "validar-stock-item-traslado":
        // Validar stock disponible de un ítem para ser trasladado
        $id_bodega = $_POST['id_bodega'];
        $id_referencia = $_POST['id_referencia'];
        $cantidad_und = isset($_POST['cantidad_und']) ? $_POST['cantidad_und'] : 1;
        $cantidad_solicitada = $_POST['cantidad']*$cantidad_und; // Convertir a cantidad en unidad de base
        $id_lote = isset($_POST['id_lote']) ? $_POST['id_lote'] : null;
       
        try {
            if ($id_lote) {
                // Validar con lote específico
                $sql = "SELECT 
                            v.stock as stock_disponible,
                            v.id as id_lote_actual
                        FROM v_referencias_stock_lotes v
                        WHERE v.id_bodega = $id_bodega 
                        AND v.id_referencia = $id_referencia
                        AND v.id = $id_lote";
                        
            } else {
                // Validar stock total sin importar lote
                $sql = "SELECT 
                            SUM(v.stock) as stock_disponible
                        FROM v_referencias_stock_lotes v
                        WHERE v.id_bodega = $id_bodega 
                        AND v.id_referencia = $id_referencia";
            }
            
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $stock_disponible = (float)$row['stock_disponible'];
                
                if ($stock_disponible >= (float)$cantidad_solicitada) {
                    echo json_encode([
                        'ok' => true,
                        'mensaje' => 'Stock disponible',
                        'stock_disponible' => (int)($stock_disponible/$cantidad_und)
                    ]);
                } else {
                    echo json_encode([
                        'ok' => false,
                        'mensaje' => 'Stock insuficiente. Stock disponible: ' . (int)($stock_disponible/$cantidad_und). ' vs cantidad solicitada: ' . $_POST['cantidad'],
                        'stock_disponible' => (int)($stock_disponible/$cantidad_und)
                    ]);
                }
            } else {
                echo json_encode([
                    'ok' => false,
                    'mensaje' => 'No se encontró stock para este ítem en la bodega especificada',
                    'stock_disponible' => 0
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Error al validar stock: ' . $e->getMessage()
            ]);
        }
        break;
    case "actualizar-items-traslado":
        return;
        // Actualizar cantidades de items en un traslado
        $conn->begin_transaction();
        
        try {
            $id_traslado = (int)$_POST['id_traslado'];
            $items = json_decode($_POST['items'], true);
            
            if (empty($items)) {
                throw new Exception("No se recibieron items para actualizar");
            }
            
            foreach ($items as $item) {

                $id_bodega_destino = (int)$item['id_bodega_destino'];
                $id_bodega = (int)$item['id_bodega'];
                $mes = (int)$item['mes'];
                $ano = (int)$item['ano'];
                $data_detalle = GenerarArray("SELECT 
                                                *
                                             FROM t_transacciones_detalle
                                             WHERE id =".$item['id_detalle'],'');
                
                // Convertir la cantidad a la unidad de base (multiplicar por can_und)
                $cantidad_base = $_POST['cantidad'] * $can_und;
                
                // Actualizar cantidad en t_transacciones_detalle tanto para la salida como para la entrada
                $sql = "UPDATE t_transacciones_detalle 
                        SET cantidad = $cantidad_base
                        WHERE 
                            id_referencia = ".$item['id_referencia']."
                        AND id_transaccion = $id_traslado
                        AND id_lote = ".$item['id_lote'];
                
                if (!$conn->query($sql)) {
                    throw new Exception("Error actualizando item: " . $conn->error);
                }
               //debo ejecutar p_recalculo_inventario_detallado , recibe p_mes, p_ano,p_id_bodega, p_id_referencia
                $sql1 = "CALL p_recalculo_inventario_detallado($mes, $ano, $id_bodega,".$item['id_referencia'].")";
                if (!$conn->query($sql1)) {
                    throw new Exception("Error ejecutando p_recalculo_inventario_detallado: " . $conn->error);
                }
                //debo ejecutar p_recalculo_inventario_detallado , recibe p_mes, p_ano,p_id_bodega, p_id_referencia
                $sql2 = "CALL p_recalculo_inventario_detallado($mes, $ano, $id_bodega_destino, ".$item['id_referencia'].")";
                if (!$conn->query($sql2)) {
                    throw new Exception("Error ejecutando p_recalculo_inventario_detallado: " . $conn->error);
                }

            }

            $conn->commit();
            echo json_encode([
                'ok' => true,
                'mensaje' => 'Items del traslado actualizados exitosamente'
            ]);
            
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Error al actualizar items: ' . $e->getMessage()
            ]);
        }
        break;
    case "enviar-notificacion":

        header('Content-Type: application/json; charset=utf-8');
        ini_set('display_errors', 1); // no mostrar errores de PHP en la respuesta
        $carpeta = basename(__DIR__);// directorio actual 
        //respuestas 
        function responder($ok, $mensaje, $extra = []) {
            echo json_encode(array_merge(['ok' => $ok, 'mensaje' => $mensaje], $extra));
            exit;
        }

        $rutaServicio = '../shared/servicios/servicios-fcm.php';
        if (!file_exists($rutaServicio)) {
            responder(false, 'No se encontró el servicio FCM');
        }
        require $rutaServicio;
        $id_bodega        = $_SESSION["Ses_id_bodega"];
        $dataTokens = [];
        switch ($_POST["transaccion"]) {
            case 'traslados':
                $id_transaccion = $_POST["id_transaccion"];
                $sql = "SELECT 
                            t.tipo,
                            t.numero,
                            t.fecha_hora,
                            t.id_bodega_destino ,
                            t.id_bodega ,
                            a.descripcion as almacen_destino
                            FROM t_transacciones t
                            INNER JOIN t_almacenes a on a.id = t.id_bodega_destino
                        WHERE t.id =? ";
                $parametrosToken=array();
                $parametrosToken[]=array(
                    'tipo'=>'i',
                    'valor'=>$id_transaccion
                );
                $data  = GenerarArrayPrepare($sql, $parametrosToken);

                if(!$data || count($data)==0){
                   responder(false, 'No se encontró el traslado');
                    return;
                }

                # code...
                $title ='Nuevo traslado '.$data[0]["tipo"].'-'.$data[0]["numero"];
                $message = "Se ha realizado un traslado desde tú bodega hacia  ".$data[0]["almacen_destino"]." a las ".$data[0]["fecha_hora"];
                $url = "https://www.thecyctechnology.com/".$carpeta."/views/home.php?id_traslado=".$id_transaccion;
                $param=[];

                $parametrosToken=array();
                //con la bodega busco el los tokens a los que debo enviar 
                $sqlToken = "SELECT
                            *
                            FROM t_fcm_tokens
                            WHERE bodega = ? ";
                $parametros []= array(
                    'tipo'=>'i',
                    'valor'=>$id_bodega
                );
                $dataTokens  = GenerarArrayPrepare($sqlToken, $parametros);
  

                try {

                        foreach ($dataTokens as $key) {
                            # code...
                            $resultado = fcmEnviar(
                                $key["token"],
                                $title,
                                $message,
                                $url,
                                $param
                            );
                            /*
                            if (!$resultado['ok']) {
                                $error = $resultado['respuesta']['error']['details'][0]['errorCode'] ?? '';
                                if ($resultado['codigo'] === 404 || $error === 'UNREGISTERED') {
                                    responder(false, 'Token inválido o expirado', ['codigo' => $resultado['codigo']]);
                                }
                                $msg = $resultado['respuesta']['error']['message'] ?? 'Error desconocido de FCM';
                                responder(false, $msg, ['codigo' => $resultado['codigo']]);
                            }*/

                        

                        }
                            responder(true, 'Notificación enviada '. $message.' title :'. $title);

                        } catch (Throwable $e) {
                            error_log('FCM: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
                            print_r($e);
                            responder(false, 'No se pudo enviar la notificación');
                        }
            break;
    
    default:
        # code...
    break;
}


try {


foreach ($dataTokens as $key) {
    # code...
    $resultado = fcmEnviar(
        $key["token"],
        $title,
        $message,
        $url,
        $param
    );

    if (!$resultado['ok']) {
        $error = $resultado['respuesta']['error']['details'][0]['errorCode'] ?? '';
        if ($resultado['codigo'] === 404 || $error === 'UNREGISTERED') {
            responder(false, 'Token inválido o expirado', ['codigo' => $resultado['codigo']]);
        }
        $msg = $resultado['respuesta']['error']['message'] ?? 'Error desconocido de FCM';
        responder(false, $msg, ['codigo' => $resultado['codigo']]);
    }

    responder(true, 'Notificación enviada');

}


} catch (Throwable $e) {
    error_log('FCM: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    print_r($e);
    responder(false, 'No se pudo enviar la notificación');
}

    break;
}