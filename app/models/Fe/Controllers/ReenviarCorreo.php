<?php
$tokenUser = '84d44a5cdd4e7604fce154c938a0ce2aaea62997';
$tokenPassword = '3c53a6402ec51e6fa860d9e3b68158ecb1aeb2d7'; 
$Prefix = $_POST['tipo'];
$Number = $_POST['numero'];
$Email = $_POST['nuevoEmail'];
try {
    // URL de la API con parámetros GET
    $url = "http://thecyctechnology.online/api-fe/public/index.php/dian-service/reenviarCorreo?" . http_build_query([
        'tokenUser' => $tokenUser,
        'tokenPassword' => $tokenPassword,
        'Prefix' => $Prefix,
        'Number' => $Number,
        'Email' => $Email
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
?>