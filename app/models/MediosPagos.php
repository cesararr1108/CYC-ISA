<?php
session_start();
require_once( 'bd.php' );
require_once( 'funciones.php' );
$conn = conectar();

switch ( $_POST[ "op" ] ) {
  case "listar-cuentas":
    $sql = "SELECT 
                id,
                cuenta,
                upper(descripcion) as descripcion
            FROM t_cuentas_contables 
            ORDER BY cuenta";
    $cuentas = GenerarArray( $sql );
    echo json_encode( array(
      'error' => false,
      'message' => 'success',
      'data' => $cuentas
    ) );
    break;

  case "listar":

    $filtro = isset( $_POST[ "filtro" ] ) && $_POST[ "filtro" ] != '' ? $_POST[ "filtro" ] : '';

    $sql = "select 
                id,
                naturaleza,
                descripcion,
                cuenta,
                fecha_creacion,
                usuario_creacion,
                inactivo,
                editable
        from t_medios_pagos";

    if ( !empty( $filtro ) ) {
      $sql .= " where descripcion like '%$filtro%' or cuenta like '%$filtro%' ";
    }
    $sql .= " order by descripcion ";

    $medios = GenerarArray( $sql );
    echo json_encode( array(
      'error' => false,
      'message' => 'success',
      'data' => $medios
    ) );
    break;

  case "agregar":
    $data = $_POST;
    $data[ "inactivo" ] = isset( $data[ "inactivo" ] ) ? $data[ "inactivo" ] : 0;
    $data[ "usuario_creacion" ] = $_SESSION[ "Ses_id" ];
    $id = insertReturnId( 't_medios_pagos', $data, true );

    if ( $id > 0 ) {
      echo json_encode( [
        'error' => false,
        'message' => 'Medio de pago creado correctamente',
        'data' => $data,
        'id' => $id
      ] );
    } else {
      echo json_encode( [
        'error' => true,
        'message' => 'Error al crear medio de pago'
      ] );
    }
    break;

  case "update":
    $data = $_POST;
    $update = update( 't_medios_pagos', $data, $data[ "id" ] );

    if ( $update ) {
      echo json_encode( array(
        'error' => false,
        'message' => 'Se actualizó correctamente el registro',
        'data' => $data
      ) );
    } else {
      echo json_encode( array(
        'error' => true,
        'message' => 'No se pudo actualizar el registro',
        'data' => $data
      ) );
    }
    break;

  case "delete":
    $id = $_POST[ "id" ];

    $delete = Query( "delete from t_medios_pagos where id=$id" );

    if ( $delete ) {
      echo json_encode( array(
        'error' => false,
        'message' => 'Se eliminó correctamente el registro',
        'data' => null
      ) );
    } else {
      echo json_encode( array(
        'error' => true,
        'message' => 'Error al eliminar el registro',
        'data' => null
      ) );
    }
    break;

}
?>
