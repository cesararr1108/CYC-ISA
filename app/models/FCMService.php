<?php
/**
 * FCMService.php
 *
 * Requiere: composer require google/apiclient:^2.15
 *
 * Uso:
 *   $fcm = new FCMService();
 *   $fcm->enviarATokens(['token1','token2'], 'Título', 'Cuerpo del mensaje', [
 *       'url' => '/pedidos/ver.php?id=123',
 *       'tag' => 'autorizacion-pedido'
 *   ]);
 */

require_once __DIR__ . '/vendor/autoload.php';

class FCMService {

    // Ruta al JSON de la cuenta de servicio.
    // Firebase Console > Configuración del proyecto > Cuentas de servicio
    // > "Generar nueva clave privada". GUÁRDALO FUERA del webroot público
    // (por ejemplo un nivel arriba de public_html) y protégelo con permisos.
    private string $rutaCredenciales = __DIR__ . '/credenciales/firebase-service-account.json';

    private string $projectId = 'TU_PROJECT_ID'; // el project_id que viene dentro del JSON

    private ?string $accessToken = null;

    /**
     * Obtiene un access token OAuth2 válido usando la cuenta de servicio.
     */
    private function obtenerAccessToken(): string {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        $client = new Google_Client();
        $client->setAuthConfig($this->rutaCredenciales);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

        $token = $client->fetchAccessTokenWithAssertion();

        if (!isset($token['access_token'])) {
            throw new Exception('No se pudo obtener el access token de Google.');
        }

        $this->accessToken = $token['access_token'];
        return $this->accessToken;
    }

    /**
     * Envía la notificación a un solo token de dispositivo.
     */
    public function enviarAToken(string $token, string $titulo, string $cuerpo, array $data = []): array {
        $accessToken = $this->obtenerAccessToken();

        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        // Los valores de "data" deben ser todos string
        $dataString = array_map('strval', $data);

        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $titulo,
                    'body'  => $cuerpo
                ],
                'data' => $dataString,
                'webpush' => [
                    'fcm_options' => [
                        'link' => $data['url'] ?? '/'
                    ]
                ]
            ]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($payload)
        ]);

        $respuesta = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error     = curl_error($ch);
        curl_close($ch);

        return [
            'ok' => $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'respuesta' => $respuesta,
            'error' => $error ?: null
        ];
    }

    /**
     * Envía la misma notificación a varios tokens (ej: el mismo usuario
     * logueado en varios navegadores). Los tokens inválidos/expirados
     * (http_code 404) los devuelve en 'tokens_invalidos' para que los borres de tu BD.
     */
    public function enviarATokens(array $tokens, string $titulo, string $cuerpo, array $data = []): array {
        $resultados = [];
        $tokensInvalidos = [];

        foreach ($tokens as $token) {
            $res = $this->enviarAToken($token, $titulo, $cuerpo, $data);
            $resultados[] = $res;

            if ($res['http_code'] === 404 || $res['http_code'] === 400) {
                $tokensInvalidos[] = $token;
            }
        }

        return [
            'resultados' => $resultados,
            'tokens_invalidos' => $tokensInvalidos
        ];
    }
}
