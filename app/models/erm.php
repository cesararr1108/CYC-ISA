<?php
session_start();
require_once( 'bd.php' );
require_once( 'funciones.php' );
$conn = conectar();
if ( !sessionExists() ) {
  echo json_encode( array(
    'error' => true,
    'message' => 'La sesion se ha cerrado, por favor ingrese nuevamente'
  ) );
  return;
}

switch ( $_POST[ "op" ] ) {
  case 's_ventas':
    $id_tercero = $_POST[ 'id_tercero' ];
    $sql = "SELECT
                year(v.fecha_documento) as ano,
                month(v.fecha_documento) as mes,
                round(sum(v.valor_total),0) as valor ,
                v.id_tercero
            from
                v_ventas_general v
            where 
                v.id_tercero = $id_tercero
            group by
                v.id_tercero ,
                year(v.fecha_documento) ,
                month(v.fecha_documento)
            order by 
                year(v.fecha_documento),
                month(v.fecha_documento) ";
    $data = GenerarArray( $sql );
    echo json_encode( $data );
    break;
  case 's_documentos':
    $id_tercero = $_POST[ 'id_tercero' ];
    $estado = $_POST[ 'estado' ];
    $sql = "SELECT 
                v.id,
                v.sw,
                v.tipo ,
                v.numero ,
                v.documento,
                v.estado ,
                v.fecha ,
                v.vencimiento ,
                v.dias,
                v.total_vencida ,
                v.total_sin_vencer,
                v.c_1_30 ,
                v.c_31_60 ,
                v.c_61_90 ,
                v.c_91_120 ,
	            v.total_aplicado,
                v.total
            from v_cartera v
            where 
                v.id_tercero  = $id_tercero and v.estado = '$estado'
            order by 
                v.fecha ";
    $data = GenerarArray( $sql );
    echo json_encode( $data );
    break;
}