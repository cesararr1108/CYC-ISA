<?php
session_start();
require_once('../models/bd.php');
require_once('../models/funciones.php');
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0); // no mostrar errores de PHP en la respuesta
$carpeta = basename(__DIR__);
function responder($ok, $mensaje, $extra = []) {
    echo json_encode(array_merge(['ok' => $ok, 'mensaje' => $mensaje], $extra));
    exit;
}

$rutaServicio = __DIR__ . '/../shared/servicios/servicios-fcm.php';
if (!file_exists($rutaServicio)) {
    responder(false, 'No se encontró el servicio FCM');
}
require $rutaServicio;
/*
if (empty($_SESSION['toke-fcm'])) {
    responder(false, 'No existe la variable de sesión');
}*/
/*
$tokenDelUsuario = 'fxa4f1ale6cKrEg363wG4W:APA91bHmu7sBbh80fcKguBZhzGhDU89tuIt02ihaJstkkILFkGVvoTQ1GCXmfcJSR38fippIPCT40od-0riClGcLG4Lk8mJ-9O73j2RSeHeJwoS4SArrIi0';

$title = isset($_POST["title"])? $_POST["title"]:'Nuevo mensaje de ISA';
$message = isset($_POST["message"])? $_POST["message"] : 'Notificacion de ISA';
$url = "https://www.thecyctechnology.com/".$carpeta."/views/home.php?id_traslado=1";
$param=[
    "id_traslado" =>1
];*/
 $dataTokens = [];
switch ($_POST["transaccion"]) {
    case 'traslado':
        $id_transaccion = $_POST["id_transaccion"];
        $sql = "SELECT tipo,numero,fecha_hora,id_bodega_destino FROM t_transacciones WHERE id =? ";
       $parametrosToken=array();
        $parametros[]=array(
            'tipo'=>'i',
            'valor'=>$id_transaccion
        );
        $data  = GenerarArrayPrepare($sql, $parametros);

        if(!$data || count($data)==0){
            echo  json_encode([
                'ok'=>false,
                'message'=>'No se encontraron datos '
            ]);
            return;
        }

        # code...
        $title ='Nuevo traslado '.$data[0]["tipo"].'-'.$data[0]["numero"];
        $message = "Has enviado un traslado de la bodega ".$_SESSION["Ses_bodega_des"]." a las ".$data[0]["fecha_hora"];
        $url = "https://www.thecyctechnology.com/".$carpeta."/views/home.php?id_traslado=".$id_transalado;
        $param=[];

        $parametrosToken=array();
        //con la bodega busco el los tokens a los que debo enviar 
        $sqlToken = "SELECT
                      *
                     FROM t_fcm_tokens
                     WHERE bodega = ? ";
        $parametros []= array(
            'tipo'=>'i',
            'valor'=>$data[0]["id_bodega_destino"]
        );
        $dataTokens  = GenerarArrayPrepare($sqlToken, $parametros);
    break;
    
    default:
        # code...
    break;
}


try {


foreach ($dataTokens as $key) {
    # code...
    $resultado = fcmEnviar(
        $key["token"],
        $title,
        $message,
        $url,
        $param
    );

    if (!$resultado['ok']) {
        $error = $resultado['respuesta']['error']['details'][0]['errorCode'] ?? '';
        if ($resultado['codigo'] === 404 || $error === 'UNREGISTERED') {
            responder(false, 'Token inválido o expirado', ['codigo' => $resultado['codigo']]);
        }
        $msg = $resultado['respuesta']['error']['message'] ?? 'Error desconocido de FCM';
        responder(false, $msg, ['codigo' => $resultado['codigo']]);
    }

    responder(true, 'Notificación enviada');

}


} catch (Throwable $e) {
    error_log('FCM: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    print_r($e);
    responder(false, 'No se pudo enviar la notificación');
}