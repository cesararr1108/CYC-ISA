<?php

use App\Controllers\GenerarAcuses;

session_start();
require_once( 'bd.php' );
require_once( 'funciones.php' );
$conn = conectar();
switch($_POST["op"]){
    case "validar-vendedor":
        $codigo =$_POST["codigo"];

        $sql="
            select 
            *
            from t_terceros 
            where nit=$codigo
            limit 1 
        ";
        $vendedor=GenerarArray($sql,'');

        if(count($vendedor)>0){/* 
            if(isset($_POST["numero"]) && isset($_POST["tipo"])){
                $update =mssql_query("update t_pedidos set vendedor='$codigo' where numero=".$_POST["numero"]." and tipo='".$_POST["tipo"]."'");
            }  */ 
        }
        echo  json_encode($vendedor);
    break;
    case "listar-facturas":
        $fecha_inicial      = $_POST["fecha_inicial"];
        $fecha_final        = $_POST["fecha_final"];
        $id_tipo_documento  = $_POST["id_tipo_documento"];
        $idUser             = $_SESSION["Ses_id"];

        $sql=" SELECT 
                d.id,
                d.numero,
                d.tipo,
                d.valor_total,
                t.nit,
                case when t.primer_nombre is null then t.razon_comercial else CONCAT_WS(' ',
                        t.primer_nombre,
                        t.segundo_nombre,
                        t.primer_apellido,
                        t.segundo_apellido
                ) end  as cliente,
                t.razon_comercial,
                cast(d.fecha_hora as date) as fecha,
                ifnull(email_fe,'') as email,
                ifnull(r.cufe,'') as cufe,
                ifnull(xx.documento_electronico,'N') as documento_electronico
            from t_transacciones d
            inner join t_terceros t on t.id=d.id_tercero 
            inner join t_tipos_documentos xx on xx.id=d.id_tipo_documento
            left join t_respuestas_dian r on r.tipo=d.tipo and r.numero=d.numero
           where 
           case when d.anulado is null 
           then ifnull(d.anulado,'N')='N' 
            else d.anulado='' end
            and d.sw=5
            and d.id_tipo_documento =$id_tipo_documento
            and d.id_usuario = $idUser
        ";

        if($fecha_final!=''  && $fecha_inicial!=''){
            $sql.="
                and cast(d.fecha_hora as date)   between '$fecha_inicial' and '$fecha_final'
            ";
        }

        $facturas = GenerarArray($sql,'');

        echo  json_encode($facturas);

    break;
    case "eliminarPago":
        $id_transaccion = $_POST["id_transaccion"];
        $DELETE = Query("DELETE FROM t_transacciones_pago_tmp WHERE id_transaccion =$id_transaccion");
    break;
    case "guardar_retenciones":
        $id_transaccion       = (int)$_POST["id_transaccion"];
        $ret_fte              = (float)$_POST["ret_fte"];
        $ret_ica              = (float)$_POST["ret_ica"];
        $ret_iva              = (float)$_POST["ret_iva"];
        $id_concepto_retencion = (int)$_POST["id_concepto_retencion"];

        $sql = "UPDATE t_transacciones_tmp 
                SET retencion_fuente        = $ret_fte,
                    retencion_ica           = $ret_ica,
                    retencion_iva           = $ret_iva,
                    id_concepto_retencion   = $id_concepto_retencion
                WHERE id = $id_transaccion";
        Query($sql);
        echo json_encode(['ok' => true]);
    break;
    case "update-cantidad2":

            $conn->begin_transaction(); // Iniciar transacción
         
            try {
                $id_det   = $_POST["id_det"];
                $cantidad = $_POST["cantidad"];

                 $update = $conn->query("UPDATE t_pedidos_detalle SET cantidad2 = $cantidad WHERE id = $id_det");
                

                if (!$update) {
                    throw new Exception("Error al actualizar t_pedidos_detalle");
                }

                // 2️⃣ Validar si maneja lote
              //  if ($_POST["ManejaFechaLote"] == 'S') {
                //    if ($_POST['ManejaFIFO'] == 'N') {
                        
                        $id_lote = $_POST["id_lote"];
                        
                        $cantidad_lote = $_POST["cantidad_lote"];
                        
                        $existe_lote = GenerarArray("SELECT * FROM t_pedidos_lotes 
                        WHERE id_lote = $id_lote 
                        AND id_detalle_pedido = $id_det");

                        // Si existe id_pedidos_lotes -> update
                        if (isset($_POST["id_pedidos_lotes"])) {
                            $update_lote = $conn->query("UPDATE 
                            t_pedidos_lotes SET cantidad = $cantidad_lote 
                            WHERE id = " . $_POST["id_pedidos_lotes"]);
                            if (!$update_lote) throw new Exception("Error al actualizar lote");
                        }

                        // Si ya hay lote existente
                        if (count($existe_lote) > 0) {
                            $update_lote = $conn->query("UPDATE t_pedidos_lotes SET cantidad = cantidad + $cantidad_lote WHERE id_detalle_pedido = $id_det");
                            if (!$update_lote) throw new Exception("Error al sumar cantidad del lote");
                        }

                        // Si no hay registro de lote aún
                        if (!isset($_POST["id_pedidos_lotes"]) && count($existe_lote) == 0) {
                            $data = [
                                'id_lote'             => $_POST["id_lote"],
                                'lote'                => trim($_POST["lote"]),
                                'vencimiento'         => $_POST["vencimiento"],
                                'id_detalle_pedido'   => $id_det,
                                'cantidad'            => $_POST["cantidad_lote"],
                                'id_pedido'           =>$_POST["id_pedido"],
                                'id_referencia'        =>$_POST["id_referencia"]
                            ];
                            $id_reg = insertReturnId('t_pedidos_lotes', $data, true,$conn);
                            if (!$id_reg) throw new Exception("Error al insertar lote");
                        }
                    //}
              //  }
                // 4️⃣ Si todo fue bien, confirmar cambios
                $conn->commit();
                // 3️⃣ Consultar lotes del pedido
                $pedidos_lotes = GenerarArray("SELECT count(*) as n FROM t_pedidos_lotes WHERE id_detalle_pedido = " . $id_det);

                echo json_encode([
                    'ok' => true,
                    'message' => 'Se actualizó correctamente la cantidad',
                    'pedidos_lotes' => $pedidos_lotes[0]["n"]
                ]);

            } catch (Exception $e) {
                // ❌ Revertir todo si algo falla
                $conn->rollback();

                echo json_encode([
                    'ok' => false,
                    'message' => 'Error en la transacción: ' . $e->getMessage()
                ]);
            }
    break;
    case "listarLotesPedidos":
         $id_det =$_POST["id_det"];
        $conf = $_SESSION["Ses_confSys"]['confGeneral'];
        $manejaFecha = $conf[0]['ManejaFechaLote'] === 'S';
        $manejaFIFO  = $conf[4]['ManejaFIFO'] === 'S';

        // Caso especial: maneja fecha pero NO FIFO
        $usaStockPorLote = ($manejaFecha && !$manejaFIFO);

        if ($usaStockPorLote) {

            $subsql = "
                SELECT stock
                FROM v_referencias_stock_lotes
                WHERE id_referencia = p.id_referencia
                AND id_bodega     = p.id_bodega
                AND id_lote       = d.id_lote
            ";

        } else {

            $subsql = "
                SELECT stock
                FROM v_stock_total
                WHERE mes = MONTH(CURRENT_DATE)
                AND ano = YEAR(CURRENT_DATE)
                AND id_referencia = d.id_referencia
                AND id_bodega     = p.id_bodega
            ";
        }

         $lotes=GenerarArray("
         select 
            d.id_referencia,
            d.id_detalle_pedido,
            d.id,
            d.lote,
            d.vencimiento ,
            cast(d.cantidad/p.can_und as decimal(19,0)) as cantidad,
            cast(ifnull((
                $subsql
            ),0) as decimal(19,0)) as stock
         from t_pedidos_lotes d 
         inner join t_pedidos_detalle p on p.id= d.id_detalle_pedido
         where id_detalle_pedido = $id_det
         ");
         echo  json_encode($lotes);
    break;
    case "update-item-pedidos-lotes":
            $id=$_POST["id"]; //id de la tabla t_pedidos_lotes  
            $id_pedido = $_POST["id_pedido"];

            $cantidad = $_POST["cantidad"];

            $update = Query("UPDATE     
                                t_pedidos_lotes
                            SET cantidad = $cantidad
                            WHERE id= $id");
            if(!$update){
                echo  json_encode([
                    'ok'=>false,
                    'message'=>'Error al actualizar el registro'
                ]);
                return;
            }
            //actualizo la cantidad despachada en el pedido 
            // cantidad total agregada de los lotes 
            $sql_c= GenerarArray("
                select 
                sum(cantidad) as cantidad
                from t_pedidos_lotes 
                where id_pedido =$id_pedido and id = $id
            ");
          
            
            if(count($sql_c)>0){
              
                $update = Query("UPDATE t_pedidos_detalle
                                    SET cantidad2 = ".$sql_c[0]["cantidad"]."
                                    WHERE id = (
                                        SELECT id_detalle_pedido 
                                        FROM (
                                            SELECT id_detalle_pedido 
                                            FROM t_pedidos_lotes 
                                            WHERE id = $id
                                            LIMIT 1
                                        ) AS tmp
                                    ) "); 
            }
            echo  json_encode([
                'ok'=>true,
                'message'=>'Se actualizo correctamente el registro!'
            ]);

    break;
    case "delete-item-lotes-pedidos":

            try {
                $id = $_POST["id"];
                $id_detalle_pedido = $_POST["id_detalle_pedido"];

                // 1️⃣ Eliminar el lote
                $delete = $conn->query("DELETE FROM t_pedidos_lotes WHERE id = $id");
                if (!$delete) {
                    throw new Exception("Error al eliminar el lote: " . $conn->error);
                }

                // 2️⃣ Consultar la suma de cantidades restantes
                $lotes = GenerarArray("
                    SELECT 
                       SUM(cantidad) AS cantidad
                    FROM t_pedidos_lotes 
                    WHERE id_detalle_pedido = $id_detalle_pedido
                ");

                $cantidad = $lotes[0]["cantidad"] ?? 0; // Evita null si no hay registros

                // 3️⃣ Actualizar el detalle con la nueva cantidad total
                $update_ = $conn->query("UPDATE t_pedidos_detalle SET cantidad2 = $cantidad WHERE id = $id_detalle_pedido");
                if (!$update_) {
                    throw new Exception("Error al actualizar t_pedidos_detalle: " . $conn->error);
                }

  
                $n_lotes = GenerarArray("SELECT count(*) as n FROM t_pedidos_lotes WHERE id_detalle_pedido = " . $id_detalle_pedido);
                echo json_encode([
                    'ok' => true,
                    'message' => 'Lote eliminado y cantidad actualizada correctamente',
                    'n_lotes' => $n_lotes[0]["n"],
                    'cantidad'=>$cantidad

                ]);

            } catch (Exception $e) {
                echo json_encode([
                    'ok' => false,
                    'message' => 'Error en la transacción: ' . $e->getMessage()
                ]);
             
            }
       break;
}
?>