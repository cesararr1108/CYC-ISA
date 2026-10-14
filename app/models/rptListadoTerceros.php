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
  case 's_terceros_detalle':
    $sql = "SELECT 
                t.nit ,
                t.nit_digito,
                ti.descripcion as tipo_identificacion,
                t.razon_comercial ,
                CONCAT(t.id_tipo_tercero,' ',t.primer_nombre,' ',t.segundo_nombre,' ',t.primer_apellido,' ',t.segundo_apellido) as nombres,
                tt.descripcion  as tipo_tercero,
                td.departamento,
                tm.municipio ,
                d.direccion,
                CONCAT(IFNULL(z.codigo_zona,''),'-',IFNULL(z.descripcion,'')) as zona_ventas,
                IFNULL(p.descripcion,'') as des_condicion,
                IFNULL(p2.descripcion,'') as des_condicion_prov,
                d.email ,
                d.telefono,
                t.id_tipo_identificacion 
            FROM t_terceros t 
            inner join t_tipos_terceros tt on tt.id  = t.id_tipo_tercero 
            inner join t_tipo_identificaciones ti on ti.id = t.id_tipo_identificacion 
            left join t_terceros_direcciones  d  on d.id_tercero = t.id and ifnull(d.principal,0) = 1
            left join t_departamentos         td on td.codigo_pais = d.codigo_pais and td.codigo_dpto  = d.codigo_dpto 
            left join t_municipios            tm on tm.codigo_pais  = d.codigo_pais  and tm.codigo_dpto  = d.codigo_dpto  and tm.codigo  = d.codigo_mpio 
            left join t_condiciones_pago      p  on p.id=t.id_condicion and p.tipo_condicion='C'
            left join t_condiciones_pago      p2 on p2.id=t.id_condicion_pago_prov and p2.tipo_condicion='P'
            left join t_zonas_ventas          z  on z.id=t.id_zona_ventas
            where 1 = 1 ";
    if ( $_POST[ 'tipo_tercero' ] != '' ) {
      $sql .= " and  t.id_tipo_tercero = " . $_POST[ 'tipo_tercero' ];
    }
    if ( $_POST[ 'nombre_tercero' ] != '' ) {
      $desc = addslashes( $_POST[ 'nombre_tercero' ] );
      $sql .= " AND ( CONCAT(t.primer_nombre,' ',t.segundo_nombre,' ',t.primer_apellido,' ',t.segundo_apellido) LIKE '%$desc%' OR tt.descripcion like '%$desc%')";
    }
    //    echo $sql;
    $q = GenerarArray( $sql );
    echo json_encode( $q );
    break;
}