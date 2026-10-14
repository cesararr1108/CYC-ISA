<?php
session_start();
//code...
require_once('../../bd.php');
require_once('../models/modelo.php');
require_once('Enviar.php');
require_once('../shared/funciones.php');
$connfel = conectarFel();

$datEmpresa = GenerarArray("select nit from t_empresa");
$sqlToken = "select * from t_datos_fiscales where nit =" . $datEmpresa[0]["nit"];
$tokes = GenerarArray($sqlToken, $connfel);
//$tokenUser = $tokes[0]["tokenUser"];
//$tokenPassword = $tokes[0]["tokenPassword"];
$tokenUser = "d1a3ef429be3f5508948692f4a955db5b7.27550973";
$tokenPassword = "bdf7894615e4ed118b2a692f4a958c1067.56090658";
$identificationType = $_POST['identificationType'];
$identificationNumber = $_POST['identificationNumber'];
try {
    // URL de la API
    $url = "http://localhost/api-fe/public/index.php/dian-service/consultaAdquirente";

    // Datos a enviar en POST
    $postData = [
        'tokenUser' => $tokenUser,
        'tokenPassword' => $tokenPassword,
        'identificationType' => $identificationType,
        'identificationNumber' => $identificationNumber
    ];

    // Configurar headers (opcional, si el backend los requiere)
    $headers = [
        "Accept: application/json",
        "Content-Type: application/x-www-form-urlencoded"
    ];

    // Inicializar cURL
    $ch = curl_init($url);

    // Configurar opciones de cURL para POST
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true); // Método POST
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Ejecutar la petición y obtener respuesta
    $response = curl_exec($ch);

    // Verificar si hubo un error en cURL
    if (curl_errno($ch)) {
        throw new Exception("Error en cURL: " . curl_error($ch));
    }

    // Obtener código de respuesta HTTP
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Decodificar respuesta JSON
    $decodedResponse = json_decode($response, true);

    // Verificar si la respuesta es un JSON válido
    if ($decodedResponse === null) {
        throw new Exception("Respuesta de la API no es un JSON válido: " . $response);
    }

    // Manejar errores HTTP
    if ($httpCode >= 400) {
        throw new Exception("Error en la API ({$httpCode}): " . json_encode($decodedResponse));
    }

    // Retornar respuesta formateada
    echo json_encode([
        "ok" => true,
        "data" => $decodedResponse
    ]);
} catch (Exception $e) {
    echo json_encode([
        "ok" => false,
        "message" => $e->getMessage()
    ]);
}