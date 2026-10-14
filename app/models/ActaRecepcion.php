<?php
session_start();

require_once( 'bd.php' );
require_once( 'funciones.php' );
$conn = conectar();

switch ($_POST["op"]) {
    case 'acta-recepcion':
        $fecha_inicial = $_POST["fecha_inicial"];
        $fecha_final   = $_POST["fecha_final"];
        $id_tercero    = $_POST["id_tercero"];
        $tipo          = trim($_POST["tipo"]);
        $numero        = trim($_POST["numero"]);
        $parametros =[];
 
        $sql="SELECT
                t.*,
                p.razon_comercial  AS proveedor,    
                IFNULL((SELECT 
                    COUNT(*)
                FROM t_transacciones_detalle
                WHERE id_transaccion = t.id ),0) AS items
              FROM t_transacciones  t 
              INNER JOIN t_terceros p  ON p.id = t.id_tercero
              WHERE IFNULL(t.anulado,0)=0 AND t.sw in(1,2)
        ";

        if($id_tercero>0){
            $sql.=" AND t.id_tercero =  ? ";
            $parametros[]=array(
                'tipo'=>'i',
                'valor'=>$id_tercero
            );
        }

        if($fecha_inicial!='' && $fecha_final!=''){
            $sql.=" AND CAST(t.fecha_hora AS DATE) BETWEEN CAST( ? AS DATE)";
            $parametros[]=array(
                'tipo'=>'s',
                'valor'=>$fecha_inicial
            );
            $sql.="  AND CAST( ?  AS DATE)  ";
            $parametros[]=array(
                'tipo'=>'s',
                'valor'=>$fecha_final
            );
        }

        if($tipo!=''){  
            $sql.=" AND t.id_tipo_documento = ?";
            $parametros[]=array(
                'tipo'=>'i',
                'valor'=>$tipo
            );
        }

        if($numero!=''){
            $sql.=" AND  t.numero=? ";
            $parametros[]=array(
                'tipo'=>'i',
                'valor'=>$numero
            );
        }

        $documentos = GenerarArrayPrepare($sql,$parametros);

        echo  json_encode($documentos);
    break;
    case "carga-detalle":
        $id_transaccion = intval($_POST["id_transaccion"]);
        $parametros =[];
        $sql="SELECT
                r.codigo,
                r.descripcion,
                IFNULL(m.descripcion,'') AS marca,
                r.id,
                CAST(t.cantidad/t.can_und AS DECIMAL(19,0)) AS cantidad,
                t.und,
                t.lote,
                t.id_lote,
                t.vmcto,
                ifnull(r.codigo_invima,'') as invima
            FROM t_transacciones_detalle t
            INNER JOIN t_referencias    r ON r.id= t.id_referencia 
            LEFT  JOIN t_marcas         m ON m.id=r.id_marca
            WHERE t.id_transaccion = ? 
              AND NOT EXISTS (
                SELECT 1 FROM t_actas_recepcion ar
                WHERE ar.id_transaccion = t.id_transaccion
                  AND ar.id_referencia  = t.id_referencia
              )
            ORDER BY r.descripcion
        ";

        $parametros[]=array(
            'tipo'=>'i',
            'valor'=>$id_transaccion
        );
        $detalle = GenerarArrayPrepare($sql,$parametros);
        echo json_encode($detalle);
    break;
    case "guardar":
        $items=  json_decode( $_POST["items_"],true);


        if(count($items)==0){
            echo json_encode([
                'ok'=>false,
                'message'=>'No se enviaron items para guardar!'
            ]);
            return;
        }
        $error = [];
        $ids_guardados = [];
        try {
            //code...
            $conn->begin_transaction();
            foreach ($items as $key) {
                    # code...
                $id = insertReturnId('t_actas_recepcion',$key,false,$conn,true);

                if($id ==0 || !$id ){
                    $conn->rollback();
                    echo  json_encode([
                        'ok'=>false,
                        'message'=>$conn->mysql_error_list(),
                        'error'=>$conn->mysql_error_list()
                    ]);
                    exit;
                }
                //acutalizo el invima de la referencia 
                $update = $conn->query("update t_referencias set codigo_invima='{$key["invima"]}' where id ='{$key["id_referencia"]}'");
                $ids_guardados[] = $id;
            }
            $conn->commit();

            // Obtener tipo y numero de la transacción para el PDF
            $id_transaccion = isset($items[0]['id_transaccion']) ? intval($items[0]['id_transaccion']) : 0;
            $tipo_num = ['tipo' => '', 'numero' => ''];
            if ($id_transaccion > 0) {
                $rTrans = GenerarArray("SELECT tipo, numero FROM t_transacciones WHERE id = $id_transaccion");
                if (!empty($rTrans)) {
                    $tipo_num['tipo']   = $rTrans[0]['tipo'];
                    $tipo_num['numero'] = $rTrans[0]['numero'];
                }
            }

            echo  json_encode(array(
                'ok'=>true,
                'message'=>'Registros guardados correctamente',
                'ids' => $ids_guardados,
                'id_acta' => isset($ids_guardados[0]) ? $ids_guardados[0] : null,
                'tipo'   => $tipo_num['tipo'],
                'numero' => $tipo_num['numero']
            ));
        } catch (\Throwable $th) {
            //throw $th;
            $conn->rollback();
            echo  json_encode([
                'ok'=>false,
                'message'=>$th->getMessage()
            ]);
            exit;
        }

    break;
    case "historial":
/*
        $numero     = $_POST["numero"];
        $tipo       = $_POST["tipo"];
        $documento  = $_POST["documento"];
        $id_tercero = $_POST["id_tercero"];
        $id_bodega  = $_SESSION["Ses_id_bodega"];

        $sql="SELECT 
            d.tipo,
            d.numero,
            d.fecha AS fecha_doc,
            a.fecha_creacion ,
            t.nit,
            t.razon_comercial AS proveedor,
            count(*) AS items
            from t_actas_recepcion a 
            INNER JOIN t_terceros       t ON  t.id = a.id_tercero
            INNER JOIN t_transacciones  d  ON d.id = a.id_transaccion
            WHERE a.id_bodega = $id_bodega 
        ";
        
        if($fecha_inicial1='' && $fecha_final!=''){
            $sql.=" AND BETWEEN CAST('$fecha_inicial' AS DATE) AND CAST('$fecha_final' AS DATE)";
        }

        if($id_tercero!=''){
            $sql.=" AND a.id_tercero =$id_tercero ";
        }

        if($numero!=''){
            $sql.=" AND d.numero=$numero";
        }
        if($tipo!=''){
            $sql.=" AND d.tipo='$tipo'";
        }

        $sql.="            
            GROUP BY
            d.tipo,
            d.numero,
            d.fecha ,
            a.fecha_creacion ,
            t.nit,
            t.razon_comercial ";

        $actas = GenerarArray($sql,'');

        echo  json_encode($actas);*/
        $numero         = $_POST["numero"] ?? null;
        $tipo           = $_POST["tipo"] ?? null;
        $id_tercero     = $_POST["id_tercero"] ?? null;
        $fecha_inicial  = $_POST["fecha_inicial"] ?? null;
        $fecha_final    = $_POST["fecha_final"]   ?? null;
        $id_bodega      = (int)$_SESSION["Ses_id_bodega"];
        $id_usuario     = (int)$_SESSION["Ses_id"];

        $conn = conectar();

        // Base query
        $sql = "SELECT 
                    d.tipo,
                    d.numero,
                    d.fecha AS fecha_doc,
                    a.fecha_creacion,
                    t.nit,
                    t.razon_comercial AS proveedor,
                    COUNT(*) AS items
                FROM t_actas_recepcion a
                INNER JOIN t_terceros t ON t.id = a.id_tercero
                INNER JOIN t_transacciones d ON d.id = a.id_transaccion
                WHERE a.id_bodega = ? AND a.id_usuario  = ? 
                ";

        $parametros = array();
        $parametros[] = array(
            'tipo'=>'i',
            'valor'=>$id_bodega
        );
        $parametros[] = array(
            'tipo'=>'i',
            'valor'=>$id_usuario
        );

        // ----------------------
        // Filtros opcionales
        // ----------------------

        if (!empty($fecha_inicial) && !empty($fecha_final)) {
            $sql .= " AND CAST(a.fecha_creacion AS DATE) BETWEEN ? AND ? ";
            $parametros[] =array(
                'tipo'=>'s',
                'valor'=>$fecha_inicial
            );
           $parametros[] =array(
                'tipo'=>'s',
                'valor'=>$fecha_final
            );

        }

        if (!empty($id_tercero)) {
            $sql .= " AND a.id_tercero = ? ";
            $parametros[] =array(
                'tipo'=>'i',
                'valor'=>$id_tercero
            );
            //$types .= "i";
        }

        if (!empty($numero)) {
            $sql .= " AND d.numero = ? ";
            $parametros[] =array(
                'tipo'=>'i',
                'valor'=>$numero
            );
            //$types .= "i";
        }

        if (!empty($tipo)) {
            $sql .= " AND d.tipo = ? ";
            $parametros[] =array(
                'tipo'=>'s',
                'valor'=>$tipo
            );
            //$types .= "s";
        }

        $sql .= " GROUP BY d.tipo, d.numero, d.fecha, a.fecha_creacion, t.nit, t.razon_comercial";

        // Ejecutar
        $actas = GenerarArrayPrepare($sql,$parametros);

        echo json_encode($actas);

    break;
}