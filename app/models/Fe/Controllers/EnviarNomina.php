<?php

/**
 * EnviarNomina.php
 * Envía una NominaElectronicaDoc al endpoint de nómina de la API-FE local.
 * Análogo a Enviar.php pero para documentos de nómina electrónica DIAN.
 */

function enviarNomina(string $tokenUser, string $tokenPassword, NominaElectronicaDoc $nomina, string $tipoED = 'ne'): array
{
    try {
        $url = "http://localhost/api-fe/public/index.php/envio/nomina";

        $payload = json_encode([
            'tokenUser'     => $tokenUser,
            'tokenPassword' => $tokenPassword,
            'tipoED'        => $tipoED,
            'nomina'        => $nomina,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        if ($payload === false) {
            throw new \Exception("Error al convertir la nómina a JSON: " . json_last_error_msg());
        }

        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "tokenUser: $tokenUser",
            "tokenPassword: $tokenPassword",
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);

        // Solo usar CAINFO si existe y el endpoint es HTTPS
        if (stripos($url, 'https://') === 0) {
            $caPath = "/etc/ssl/certs/thecyctechnology.com.crt";
            if (file_exists($caPath)) {
                curl_setopt($ch, CURLOPT_CAINFO, $caPath);
            }
        }

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new \Exception("Error en cURL: " . curl_error($ch));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($response, true);

        if ($decoded === null) {
            throw new \Exception("Respuesta de la API no es JSON válido: " . $response);
        }

        if ($httpCode >= 400) {
            throw new \Exception("Error en la API ({$httpCode}): " . json_encode($decoded));
        }

        return [
            "ok"      => true,
            "message" => "Nómina electrónica enviada correctamente",
            "data"    => $decoded,
        ];
    } catch (\Exception $e) {
        return [
            "ok"      => false,
            "message" => $e->getMessage(),
        ];
    }
}
