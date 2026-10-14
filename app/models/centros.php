<?php
session_start();
require_once( 'bd.php' );
require_once( 'funciones.php' );
$conn = conectar();

switch ( $_POST[ "op" ] ) {
  case "listar":
    $sql = "select * from t_centros_costos";
    $centros = GenerarArray( $sql );
    echo json_encode( array(
      'error' => false,
      'message' => 'success',
      'data' => $centros
    ) );
    break;
}