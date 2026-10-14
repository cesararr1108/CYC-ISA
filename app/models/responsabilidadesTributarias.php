<?php
session_start();
require_once( 'bd.php' );
require_once( 'funciones.php' );
$conn = conectar();

switch ( $_POST[ "op" ] ) {
  case "responsabilidades":
    $sql = "select * from t_facturacion_electronica_responsabilidades";
    $res = GenerarArray( $sql );
    echo json_encode( array(
      'error' => false,
      'message' => 'success',
      'data' => $res
    ) );
    break;
  case "impuestos":
    $sql = "select * from t_facturacion_electronica_impuestos";
    $res = GenerarArray( $sql );
    echo json_encode( array(
      'error' => false,
      'message' => 'success',
      'data' => $res
    ) );
    break;
}