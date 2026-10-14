 <?php
session_start();
require_once( 'bd.php' );
require_once( 'funciones.php' );
$conn = conectar();


switch ( $_POST[ "op" ] ) {

    case "buscar-factura":
        $numero         = $_POST["numero"];
        $tipo           = $_POST["tipo"];
        $id_tercero     = $_POST["id_tercero"];
        $fecha_inicial  = $_POST["fecha_inicial"];
        $fecha_final    = $_POST["fecha_final"];
        $bodega         = $_POST["bodega"];
        
        $sql="
            select 
                d.id, 
                d.numero,
                d.tipo,
                t.nit,
                CONCAT(t.primer_apellido,' ',t.segundo_apellido,' ',t.primer_nombre,' ',t.segundo_nombre) as tratamiento,
                t.razon_comercial,
                CONCAT(v.primer_apellido,' ',v.segundo_apellido,' ',v.primer_nombre,' ',v.segundo_nombre) as vendedor,
                d.fecha_hora,
                d.id_bodega,
                d.valor_total,
                d.id_tercero_dir as id_direccion,
                (select 
                        direccion
                    from t_terceros_direcciones 
                    where id=d.id_tercero_dir
                limit 1 ) as direccion,
                t.id as id_tercero,
   
                 DATE_ADD(CURDATE(), INTERVAL pp.dias DAY) as vencimiento,
                 pp.descripcion as condicion,
                 case when d.vencimiento> d.fecha then 'CREDITO' else 'CONTADO' end  as tipo_factura,
                 d.vendedor as cod_vendedor,
                 ifnull(pp.dias,0) as dias,
                 '0' as codigo_zona,
                 '' as des_zona_ventas
            from t_transacciones d 
            inner join t_terceros           t on t.id           = d.id_tercero
            left  join t_terceros           v on v.nit          = d.vendedor
            left  join t_condiciones_pago   pp on pp.id         = d.id_condicion AND pp.tipo_condicion ='P'
            left  join t_zonas_ventas       z on z.codigo_zona  = d.zona_ventas
            where ifnull(d.anulado,'0')<>'1' 
            and d.sw=1 
        ";

        if($numero!=''){
            $sql.=" and d.numero = $numero ";           
        }
        if($tipo!=''){
            $sql.=" and d.tipo='$tipo'  ";
        }
        if($id_tercero!='0'){
            $sql.=" and d.id_tercero=$id_tercero";
        }

        if($fecha_inicial !='' && $fecha_final!=''){
            $sql.=" and CAST(d.fecha_hora AS date) BETWEEN CAST('$fecha_inicial' as date) and CAST('$fecha_final' as date)";
        }

        $sql.="            
            order by
            d.fecha_hora 
            limit 50";

        $facturas = GenerarArray($sql);

        if( count($facturas) > 0 && $facturas[0]['id_bodega']!=$bodega){

            echo  json_encode(array('ok'=>false,'message'=>'La factura de compra seleccionada no pertenece a la bodega seleccionada'));
            return;

        }
      
        echo  json_encode(array('ok'=>true,'data'=>$facturas));

    break;
    case "detalle-fact": 
        $id_transaccion = $_POST["id_transaccion"];
        $sql="SELECT 
                    d.id_transaccion,
                    d.id_referencia,
                    r.codigo,
                    r.descripcion,
                    ifnull(m.descripcion,'Sin asignar') as lab,
                    cast(d.cantidad/d.can_und as decimal(19,0)) as cantidad,
                    d.pcj_iva ,
                    d.pcj_descuento,
                    round((((d.costo_unitario*(1-(d.pcj_descuento/100))))*((d.pcj_iva/100)+1)) * d.can_und,0) as precio ,
                    cast(ifnull((
                        SELECT
                        sum(cantidad/can_und)
                        FROM t_transacciones_detalle 
                        WHERE id_transaccion_ref = d.id_transaccion 
                        and id_referencia = d.id_referencia 
                        and id_lote = d.id_lote
                        and sw=7
                    ),0) as decimal(19,0)) as cantidad_devuelta,
                    d.costo_unitario,
                    d.can_und,
                    ifnull((select 
                       promedio
                    from v_costo_promedio where id_referencia= r.id and mes = MONTH(CURRENT_DATE) AND ano = YEAR(CURRENT_DATE)
                    ),(
                       SELECT 
                            costo_unitario
                        FROM t_transacciones_detalle
                        WHERE id_referencia = r.id 
                        AND sw <> 1
                        ORDER BY fec DESC
                        LIMIT 1
                    )) as costo_promedio,
                     d.id_bodega ,
                     d.id_lote,
                     d.lote,
                     d.vmcto,
                     d.und,
                     ifnull(d.impoconsumo,0) as impoconsumo,
                     ifnull(d.ibua,0) as ibua,
                     ifnull(d.icui,0) as icui,
                     d.id_transaccion_ref ,
                    z.codigo_zona,
                    z.descripcion as des_zona_ventas,
                    tt.id_tercero_dir as id_direccion,
                     (select 
                        direccion
                    from t_terceros_direcciones 
                    where id=tt.id_tercero_dir
                    limit 1 ) as direccion,
                    tt.vendedor,
                    tt.vencimiento,
                    pp.descripcion as condicion
                from t_transacciones_detalle d
                inner join t_transacciones tt on tt.id  =d.id_transaccion
                inner join t_terceros       t on t.id   =tt.id_tercero
                inner join t_condiciones_pago pp on pp.id=tt.id_condicion
                left  join t_zonas_ventas   z on z.codigo_zona=tt.zona_ventas

                inner join t_referencias r on r.id=d.id_referencia
                left  join t_marcas m on m.id=r.id_marca
                WHERE d.id_transaccion = $id_transaccion 
                order by
                r.descripcion ";

        $detalle = GenerarArray($sql);

        echo  json_encode($detalle);
    break;
    case "guardar-devolucion":
        try {

            //code...
            if(!isset($_POST["detalle"] )){
                echo json_encode(array(
                    'ok'=>false,
                    'message'=>'No se envio el detalle la devolucion',
                    'id_transaccion'=>0
                ));
                return;
            }
            $numero =0;
            $items = json_decode($_POST["detalle"],true);

            if(isset($_POST[ "id_tipo_documento" ])){
                $numero = numeroSiguiente( $_POST[ "id_tipo_documento" ]);
            }

            if($numero==0){
                 echo json_encode(array(
                    'ok'=>false,
                    'message'=>'El consecutivo no se pudo generar',
                    'id_transaccion'=>0
                ));
                return;
            }
    
            // valido el stcok de cada lote 
             foreach ($items as $det) {
                $sql="SELECT
                        s.stock  ,
                        r.descripcion as referencia
                      FROM v_referencias_stock_lotes s
                      INNER JOIN t_referencias r ON r.id = s.id_referencia 
                      WHERE id_referencia = ".$det["id_referencia"]."
                             AND id_bodega = ".$det["id_bodega"]."
                             AND id_lote = ".$det["id_lote"]." ";
                $StockLote = GenerarArray($sql,'');

                if(count($StockLote)>0){
                    $cantidad= $det["cantidad"];
                    $stock = $StockLote[0]["stock"];

                    if($stock<$cantidad){
                        echo json_encode(array(
                            'ok'=>false,
                            'message'=>'El stock de la referencia '.$StockLote[0]["referencia"]." es menor que la cantidad a devolver",
                            'id_transaccion'=>0
                        ));
                        return; 
                    }
                }else{
                        echo json_encode(array(
                            'ok'=>false,
                            'message'=>'No se encontro stock de la referencia '.$StockLote[0]["referencia"].".",
                            'id_transaccion'=>0
                        ));
                        return;
                }           
             }

            $DataEncanezado = [
                'numero'=>$numero,
                'id_tipo_documento'=>$_POST["id_tipo_documento"],
                'id_tercero'=>$_POST["id_tercero"],
                'id_bodega'=>$_POST["id_bodega"],
                'notas'=>$_POST["notas"],
                'valor_total'=>$_POST["valor_total"],
                'iva'=>$_POST["iva"],
                'descuento'=>$_POST["descuento"],
                'sw'=>$_POST["sw"],
                'id_tercero_dir'=>$_POST["id_tercero_dir"],
                'id_condicion'=>$_POST["id_condicion"],
                'zona_ventas'=>$_POST["zona_ventas"],
                'zona_descripcion'=>$_POST["zona_descripcion"],
                'id_usuario'=>$_SESSION["Ses_id"],
                'fecha'=>date("Y-m-d"),
                'vendedor'=>$_POST["vendedor"],
                'documento'=>$_POST["documento"],
                'vencimiento'=>$_POST["vencimiento"],
                'valor_aplicado'=>0,
                'es_credito'=>$_POST["es_credito"],
                //'id_transaccion_ref'=>$_POST["id_transaccion_ref"]
            ];
 
            $IdEnc = insertReturnId('t_transacciones_tmp',$DataEncanezado,true);

            if($IdEnc>0){

                foreach ($items as $key) {
                   $key["id_transaccion"]=$IdEnc;

                   if(trim($_SESSION["Ses_confSys"]["confGeneral"][0]["ManejaFechaLote"])=='N'){
                        unset($key["id_lote"]);
                        unset($key["vmcto"]);
                        unset($key["lote"]);
                   }
                  $id_detalle =  insertReturnId('t_transacciones_detalle_tmp',$key,true);
                }
             
                //hago el volcado del documento
                /***VOLCADO */
                $query = "CALL p_volcar_documento(?,@nuevoid)";

                    $stmt = $conn->prepare( $query );

                    if ( !$stmt ) {
                        echo json_encode( array( 'ok' => false, 'message' => $conn->error, 'id' => 0 ) );
                        return;
                    }

                    $stmt->bind_param( "i", $IdEnc );
                    $stmt->execute();

                    if ( !$stmt ) {
                        echo json_encode( array( 'ok' => false, 'message' => print_r( mysql_error_list( $conn ) ), 'id' => 0 ) );
                        return;
                    }

                    $result = $conn->query( "SELECT @nuevoid AS nuevoid" );

                    if ( !$result ) {
                        echo json_encode( array( 'ok' => false, 'message' =>  print_r( mysql_error_list( $conn ) )) );
                        return;
                    }

                    $nuevoid = $result->fetch_assoc();

                    if ( $nuevoid[ 'nuevoid' ] > 0 ) {
    
                        echo  json_encode(array(
                                'ok'     =>true,
                                'message'=>'Se inserto correctamente el registro',
                                'id_transaccion'=>$nuevoid[ 'nuevoid' ] 
                            ));
                    } else {
                        echo json_encode( array( 'ok' => false, 'message' => 'Error al insertar el registro '.$nuevoid[ 'nuevoid' ]) );
                    }

            }else{
                echo  json_encode(array(
                    'ok'        =>false,
                    'message'   =>'Error al insertar el encabezado '
                ));
            }
        } catch (\Throwable $th) {
            //throw $th;
            echo json_encode(array(
                'ok'        =>false,
                'message'   =>$th->getMessage(),
                 'id_transaccion'=>0
            ));
        }
        
    break;
}