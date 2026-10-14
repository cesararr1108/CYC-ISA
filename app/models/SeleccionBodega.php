<?php
session_start();

if ( empty( $_SESSION[ "Ses_id" ] ) ) {
  echo json_encode( array(
    'ok' => false,
    'message' => 'Session del usuario no iniciada'
  ) );
  return;
}

if ( !isset( $_POST[ "bodega" ] ) ) {
  echo json_encode( array(
    'ok' => false,
    'message' => 'No se envio la bodega'
  ) );
  return;
}
$bodega = $_POST[ "bodega" ];
$id_bodega = $_POST[ "id_bodega" ];
$bodega_des = $_POST[ "bodegaDes" ];


$_SESSION[ "Ses_bodega" ] = $bodega;
$_SESSION[ "Ses_bodega_des" ] = $bodega_des;
$_SESSION[ "Ses_id_bodega" ] = $id_bodega;

echo json_encode( array(
  'ok' => true,
  'message' => 'Bodega seleccionada'
) );