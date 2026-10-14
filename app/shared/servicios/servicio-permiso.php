<?php
session_start();
require_once( '../../models/bd.php' );
require_once( '../../models/funciones.php' );
$conn = conectar();

$modulo = isset($_POST["id_modulo"])? $_POST["id_modulo"]:null;

if(!$modulo){
    echo  json_encode(array(
        'ok'=>false,
        'message'=>'No se envio el modulo'
    ));
    return;
}
$rol = $_SESSION["Ses_roles_id"];

if(!is_numeric($rol)){
      echo  json_encode(array(
        'ok'=>false,
        'message'=>'No se detecto el rol'
    ));
    return;  
}
$sql="
    select 
    pp.permiso ,
    p.id_modulo 
  from 
 t_modulos  m
 inner join t_modulos_permisos   p on p.id_modulo = m.id
 inner join t_roles_modulos_permisos     x on x.id_modulo_permiso=p.id_modulo_permiso
 inner join t_permisos pp on pp.id=p.id_permiso 
where   x.id_rol = $rol  and p.id_modulo =$modulo
                
";

$permisos = GenerarArray($sql,'');
echo  json_encode($permisos);