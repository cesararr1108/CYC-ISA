<?php
session_start();
require_once( 'bd.php' );
require_once( 'funciones.php' );
$conn = conectar();

switch ( $_POST[ "op" ] ) {
  case 'create':
    $data = $_POST;
    $id = insertReturnId( 't_perfiles_contables', $data, true );
    if ( $id > 0 ) {
      echo json_encode( array(
        'error' => false,
        'message' => 'Perfil contable, creado correctamente'
      ) );
    } else {
      echo json_encode( array(
        'error' => true,
        'message' => 'Ocurrio un error, no fue posible crear el perfil contable'
      ) );
    }
    break;
  case 'update':
    $data = $_POST;
    $update = update( 't_perfiles_contables', $data, $data[ "id" ] );
    if ( $update ) {
      echo json_encode( array(
        'error' => false,
        'message' => 'Perfil contable, actualizado correctamente'
      ) );
    } else {
      echo json_encode( array(
        'error' => true,
        'message' => 'Ocurrio un error, no fue posible actualizar el perfil contable'
      ) );
    }
    break;
  case 'delete':
    $id = $_POST[ 'id' ];
    $sql_check = GenerarArray( "SELECT * FROM t_referencias WHERE perfil_contable = $id" );
    if ( count( $sql_check ) > 0 ) {
      echo json_encode( array(
        'error' => true,
        'message' => 'No es posible eliminar el perfil, ya que está asignado a una o más referencias.'
      ) );
      break;
    }
    $sql = "delete from t_perfiles_contables where id = $id";
    if ( Query( $sql ) ) {
      echo json_encode( array(
        'error' => false,
        'message' => 'Perfil contable, eliminado correctamente'
      ) );
    } else {
      echo json_encode( array(
        'error' => true,
        'message' => 'Ocurrio un error, no fue posible eliminar el perfil contable'
      ) );
    }
    break;
  case 'listar-todos':
    $q = GenerarArray( "select 
                            p.id,
                            p.id_iva,
                            i.porcentaje_iva,
                            p.descripcion,
                            p.cta_ventas,
                            p.cta_iva_ventas,
                            p.cta_dev_ventas,
                            p.cta_iva_dev,
                            p.cta_iva_compras,
                            p.cta_compras ,
                            p.cta_impoconsumo,
                            p.cta_icui,
                            p.cta_ibua
                        from t_perfiles_contables p
                        inner join t_iva i on i.id  = p.id_iva 
                        order 
                            by p.id" );
    echo json_encode( array(
      'error' => false,
      'message' => 'success',
      'data' => $q
    ) );
    break;
}
?>