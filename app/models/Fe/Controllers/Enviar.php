<?php
function eventosAcuse($tokenUser, $tokenPassword, $evento, $cufe, $rechazo)
{
    try {
        // URL de la API con parámetros GET
        $url = "http://localhost/api-fe/public/index.php/dian-service/acuses?" . http_build_query([
            'tokenUser' => $tokenUser,
            'tokenPassword' => $tokenPassword,
            'codigo' => $evento,
            'cufe' => $cufe,
            'rechazo' => $rechazo
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
}
function enviarAcuses($tokenUser, $tokenPassword, $method = 'POST')
{
    try {
        $url = 'http://localhost/api-fe/public/index.php/dian-service/acusesAutomaticos';
        // Datos para enviar (solo si es POST, PUT, etc.)
        $jsonFactura = json_encode([
            'tokenUser' => $tokenUser,
            'tokenPassword' => $tokenPassword
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        if ($jsonFactura === false) {
            return [
                "ok" => false,
                "message" => "Error al convertir a JSON: " . json_last_error_msg()
            ];
        }

        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "tokenUser: $tokenUser",
            "tokenPassword: $tokenPassword"
        ];

        $ch = curl_init($url);

        // Configurar opciones de cURL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonFactura);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Ejecutar la petición y obtener respuesta
        $response = curl_exec($ch);

        // Verificar si hubo un error en la ejecución de cURL
        if (curl_errno($ch)) {
            return [
                "ok" => false,
                "message" => "Error en cURL: " . curl_error($ch)
            ];
        }

        // Obtener código de respuesta HTTP
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Decodificar respuesta JSON
        $decodedResponse = json_decode($response, true);

        // Verificar si la respuesta es válida
        if ($decodedResponse === null) {
            return $response;
        }

        // Manejar errores HTTP
        if ($httpCode >= 400) {
            return $decodedResponse;
        }

        // Retornar respuesta formateada
        return [
            "ok" => true,
            "message" => $decodedResponse
        ];
    } catch (Exception $e) {
        return [
            "ok" => false,
            "message" => $e->getMessage()
        ];
    }
}
function enviar($tokenUser, $tokenPassword, $Factura, $tipoED)
{
    try {
        // URL de la API
        $url = "http://localhost/api-fe/public/index.php/envio/documento";

        // Convertir objeto a JSON
        $jsonFactura = json_encode(array(
            'tokenUser' => $tokenUser,
            'tokenPassword' => $tokenPassword,
            'numeroFact' => $Factura->documento->numero,
            'NumAdq' => $Factura->cliente->nit,
            'tipo' => $Factura->documento->tipo,
            'Factura' =>   $Factura,
            'tipoED' => $tipoED
        ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        // Verificar si la conversión a JSON falló
        if ($jsonFactura === false) {
            throw new Exception("Error al convertir la factura a JSON: " . json_last_error_msg());
        }

        // Configurar headers
        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "tokenUser: $tokenUser",
            "tokenPassword: $tokenPassword"
        ];

        // Inicializar cURL
        $ch = curl_init($url);

        // Configurar opciones de cURL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonFactura);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CAINFO, "/etc/ssl/certs/thecyctechnology.com.crt");
        //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 
        // Ejecutar la petición y obtener respuesta
        $response = curl_exec($ch);

        // Verificar si hubo un error en la ejecución de cURL
        if (curl_errno($ch)) {
            throw new Exception("Error en cURL: " . curl_error($ch));
        }

        // Obtener código de respuesta HTTP
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Decodificar respuesta JSON
        $decodedResponse = json_decode($response, true);

        // Verificar si la respuesta es válida
        if ($decodedResponse === null) {
            throw new Exception("Respuesta de la API no es un JSON válido: " . $response);
        }

        // Manejar errores HTTP
        if ($httpCode >= 400) {
            throw new Exception("Error en la API ({$httpCode}): " . json_encode($decodedResponse));
        }

        // Retornar respuesta formateada
        return [
            "ok" => true,
            "message" => "Factura enviada correctamente",
            "data" => $decodedResponse
        ];
    } catch (Exception $e) {
        return [
            "ok" => false,
            "message" => $e->getMessage()
        ];
    }
}
