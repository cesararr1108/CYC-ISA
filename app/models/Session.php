<?php
session_start();
require_once( 'bd.php' );
require_once( 'funciones.php' );
$conn = conectar();
switch($_POST["op"]){
    case "valid_session":
     
        if(empty($_SESSION["Ses_id"] ) || empty($_SESSION["Ses_token"])){
            echo  json_encode(array(
                'activa' => false,
                'message'=>'No hay sesión activa',
                'Ses_id' => $_SESSION["Ses_id"] ?? null,
                'Ses_token' => $_SESSION["Ses_token"] ?? null
            ));
            return;
        }

        $id = $_SESSION["Ses_id"] ?? null;
        $token = $_SESSION["Ses_token"] ?? null;

        if (!$id || !$token) {
            echo  json_encode(array(
                'activa' => false,
                'message'=>'El id de usuatio o token estan vacios'
            ));
            exit;
        }

        $sql = "SELECT session_token FROM t_usuarios WHERE id=$id LIMIT 1";
        $res = mysqli_query($conn, $sql);
        $row = mysqli_fetch_assoc($res);


        if ($row["session_token"] !== $token) {
            // alguien abrió sesión en otro navegador
            session_destroy();
            echo  json_encode(array(
                'activa' => true,
                'unica_session'=>false
            ));
            exit;
        }
        echo  json_encode(array(
            'activa' => true ,
            'unica_session'=>true,
            'Ses_id' => $_SESSION["Ses_id"]
        ));
       
    break;
}