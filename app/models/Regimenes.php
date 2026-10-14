<?php
session_start();
require_once( 'bd.php' );
require_once( 'funciones.php' );
$conn = conectar();

switch($_POST["op"]){
    case "listar":
        $sql="select * from t_regimenes";
        $tipos_terceros =GenerarArray($sql);
        echo json_encode(array(

            'error'=>false,
            'message'=>'success',
            'data'=>$tipos_terceros
        ));
    break;
}