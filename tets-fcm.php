<?php
session_start();
print_r($_POST);
require 'app/shared/servicios/servicios-fcm.php';
if(!isset($_SESSION["token"])){
    echo 'No existe la variable de sesion';
    return;
}
$tokenDelUsuario = $_SESSION["token"];
$resultado = fcmEnviar(
    $tokenDelUsuario,                 // sacado de tu base de datos
    'Nuevo gasto aprobado',
    'Tu anticipo de viaje fue aprobado',
    'https://thecyctechnology.com/development/app/views/home.php?noti=1',
    ['id_gasto' => 1]
);

if (!$resultado['ok']) {
    // 404 / UNREGISTERED = token vencido o el usuario revocó el permiso -> bórralo de la BD
    $error = $resultado['respuesta']['error']['details'][0]['errorCode'] ?? '';
    if ($resultado['codigo'] === 404 || $error === 'UNREGISTERED') {
        // DELETE FROM tokens_push WHERE token = ...
    }
}