<?php
session_start();
require_once( '../../models/bd.php' );
require_once( '../../models/funciones.php' );

if(!isset($_SESSION["Ses_confSys"])){

  echo  json_encode(array(
    'ok'=>false,
    'message'=>'No se detecto la configuración inicial'
  ));
  return ;

}
echo  json_encode($_SESSION["Ses_confSys"]);
