<?php
session_start();
require_once('../../bd.php');
require_once('../models/modelo.php');
require_once('Enviar.php');
require_once('../shared/funciones.php');
$connfel = conectarFel();

try {
    $sql = "select 
            t.tokenUser,
            t.tokenPassword
          from t_datos_fiscales t 
          where 
            t.servicioAcuse = 'S' and 
            t.ProfileExecutionID  = 1";
    $usuarios = GenerarArray($sql, $connfel);

    foreach ($usuarios as $row) {
        $respuesta = enviarAcuses($row['tokenUser'], $row['tokenPassword']);
        echo json_encode($respuesta);
    }
} catch (Exception $e) {
    return [
        "ok" => false,
        "message" => $e->getMessage()
    ];
}
