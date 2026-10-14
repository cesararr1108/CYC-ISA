<?php
session_start();
require_once('../../bd.php');
require_once('../models/modelo.php');
require_once('Enviar.php');
require_once('../shared/funciones.php');
$connfel = conectarFel();

try {
    $codigo = $_POST['evento'];
    $cufe = $_POST['cufe'];
    $rechazo = $_POST['rechazo'];

    $datEmpresa = GenerarArray("select nit from t_empresa");
    $sqlToken = "select * from t_datos_fiscales where nit =" . $datEmpresa[0]["nit"];
    $token = GenerarArray($sqlToken, $connfel);
    $user = $token[0]["tokenUser"];
    $password = $token[0]["tokenPassword"];
    echo $respuesta = eventosAcuse($user, $password, $codigo, $cufe, $rechazo);
} catch (Exception $e) {
    return [
        "ok" => false,
        "message" => $e->getMessage()
    ];
}
