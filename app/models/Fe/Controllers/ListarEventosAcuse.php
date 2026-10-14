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
$tokenUser = $tokes[0]["tokenUser"];
$tokenPassword = $tokes[0]["tokenPassword"];
$fechaIni = $_POST['fechaIni'];
$fechaFin = $_POST['fechaFin'];
try {
    // URL de la API con parámetros GET
    $url = "http://localhost/api-fe/public/index.php/dian-service/listarAcuses?" . http_build_query([
        'tokenUser' => $tokenUser,
        'tokenPassword' => $tokenPassword,
        'fechaInicial' => $fechaIni,
        'fechaFinal' => $fechaFin,
        'prefix' => 'ACR'
    ]);

    // Configurar headers (opcional, si el backend los requiere)
    $headers = [
        "Accept: application/json",
        // Si el backend espera los tokens en headers también:
        "tokenUser: $tokenUser",
        "tokenPassword: $tokenPassword"
    ];

    // Inicializar cURL
    $ch = curl_init($url);

    // Configurar opciones de cURL para GET
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPGET, true); // Método GET
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