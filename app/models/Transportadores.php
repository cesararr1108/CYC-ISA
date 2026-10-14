<?php
session_start();
require_once( 'bd.php' );
require_once( 'funciones.php' );
$conn = conectar();
switch ( $_POST[ "op" ] ) {
  case 's_nit':
    $nit = $_POST[ 'nit' ];
    $sql = "select 
              t.id,
              t.nit,
              t.id_tipo_identificacion,
              t.nombres,
              t.razon_social,
              t.celular,
              d.departamento,
              c.municipio,
              t.vehiculo,
              t.placas,
              t.bloqueo_planilla,
              t.tipo_transportador,
              case t.tipo_transportador
                when 'M' then 'MENSAJERO'
                when 'T' then 'TRANSPORTADOR'
              end as tipo_desc,
              t.fecha_creacion,
              t.codigo_dpto,
              t.codigo_mpio 
            from t_transportadores t
            inner join t_departamentos d on d.codigo_dpto  = t.codigo_dpto 
            inner join t_municipios  c on c.codigo_dpto  = t.codigo_dpto and c.codigo = t.codigo_mpio 
			where 
				t.nit = $nit";
    $data = GenerarArray( $sql );
    echo json_encode( array(
      'Ok' => true,
      'data' => $data
    ) );
    break;
  case 'g_transportador':
    $data = $_POST;
    $nit = $data[ 'nit' ];
    $id = $data[ 'id' ];

    if ( $id == 0 ) {
      $q = GenerarArray( "SELECT id FROM t_transportadores WHERE nit = '$nit'" );

      if ( count( $q ) > 0 ) {
        echo json_encode( [
          'error' => true,
          'message' => 'La identificación de este transportador ya se encuentra registrada, no es posible registrarla nuevamente.'
        ] );
        return;
      }
      $id = insertReturnId( 't_transportadores', $data, true );
      if ( $id > 0 ) {
        echo json_encode( array(
          'error' => false,
          'message' => 'Transportador creado correctamente.'
        ) );
      } else {
        echo json_encode( array(
          'error' => true,
          'message' => 'No se pudo crear el transportador'
        ) );
      }
    } else {
      $update = update( 't_transportadores', $data, $data[ "id" ] );
      if ( $update ) {
        $id = $data[ "id" ];
        echo json_encode( array(
          'error' => false,
          'message' => 'Transportador actualizado correctamente.',
          'data' => $data
        ) );
      } else {
        echo json_encode( array(
          'error' => true,
          'message' => 'No se pudo actualizar el transportador'
        ) );
      }
    }
    break;
  case 's_transportadores':
    $sql = "select 
              t.id,
              t.id_tipo_identificacion,
              t.nit,
              t.nombres,
              t.razon_social,
              t.celular,
              d.departamento,
              c.municipio,
              t.vehiculo,
              t.placas,
              t.bloqueo_planilla,
              t.tipo_transportador,
              case t.tipo_transportador
                when 'M' then 'MENSAJERO'
                when 'T' then 'TRANSPORTADOR'
              end as tipo_desc,
              t.fecha_creacion,
              t.codigo_dpto,
              t.codigo_mpio 
            from t_transportadores t
            inner join t_departamentos d on d.codigo_dpto  = t.codigo_dpto 
            inner join t_municipios  c on c.codigo_dpto  = t.codigo_dpto and c.codigo = t.codigo_mpio ";
    $data = GenerarArray( $sql );
    echo json_encode( array(
      'Ok' => true,
      'data' => $data,
      'SQL' => $sql
    ) );
    break;
  case 'd_transportador':
    $id = $_POST[ 'id' ];
    $sql = "delete from t_transportadores where id = $id";
    if ( Query( $sql ) ) {
      echo json_encode( array(
        'error' => false,
        'message' => 'Transportador eliminado correctamente'
      ) );
    } else {
      echo json_encode( array(
        'error' => true,
        'message' => 'No fue posible eliminar el transportador'
      ) );
    }
    break;
}