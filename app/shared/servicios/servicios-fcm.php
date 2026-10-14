<?php
/**
 * Envío de notificaciones push con Firebase Cloud Messaging (API HTTP v1)
 * PHP puro, sin Composer. Requiere las extensiones curl y openssl.
 */

// Ruta al JSON de la cuenta de servicio (FUERA de la carpeta pública)
define('FCM_CREDENCIALES', __DIR__ . '/../../../firebase/service-account.json');
// Archivo donde se cachea el access token (dura 1 hora)
define('FCM_CACHE_TOKEN', sys_get_temp_dir() . '/fcm_access_token.json');

function base64url($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Obtiene un access token OAuth2 a partir de la cuenta de servicio.
 * Lo guarda en caché para no pedir uno nuevo en cada envío.
 */
function fcmObtenerAccessToken() {
    if (file_exists(FCM_CACHE_TOKEN)) {
        $cache = json_decode(file_get_contents(FCM_CACHE_TOKEN), true);
        if ($cache && $cache['expira'] > time() + 60) {
            return $cache['token'];
        }
    }

    $cred = json_decode(file_get_contents(FCM_CREDENCIALES), true);
    $ahora = time();

    $header = base64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $claims = base64url(json_encode([
        'iss'   => $cred['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud'   => 'https://oauth2.googleapis.com/token',
        'iat'   => $ahora,
        'exp'   => $ahora + 3600,
    ]));

    openssl_sign("$header.$claims", $firma, $cred['private_key'], 'sha256WithRSAEncryption');
    $jwt = "$header.$claims." . base64url($firma);

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]),
    ]);
    $respuesta = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (empty($respuesta['access_token'])) {
        throw new Exception('No se pudo obtener el access token: ' . json_encode($respuesta));
    }

    file_put_contents(FCM_CACHE_TOKEN, json_encode([
        'token'  => $respuesta['access_token'],
        'expira' => $ahora + $respuesta['expires_in'],
    ]));

    return $respuesta['access_token'];
}

/**
 * Envía una notificación a un token de dispositivo/navegador.
 *
 * @param string $tokenDestino Token FCM que obtuviste con getToken() en el navegador
 * @param string $titulo
 * @param string $cuerpo
 * @param string|null $url    Página que se abre al hacer clic (debe ser HTTPS)
 * @param array  $datos       Datos extra (solo valores string)
 * @return array ['ok' => bool, 'codigo' => int, 'respuesta' => array]
 */
function fcmEnviar($tokenDestino, $titulo, $cuerpo, $url = null, $datos = []) {
    $cred = json_decode(file_get_contents(FCM_CREDENCIALES), true);
    $projectId = $cred['project_id'];

    $mensaje = [
        'message' => [
            'token' => $tokenDestino,
            'notification' => [
                'title' => $titulo,
                'body'  => $cuerpo,
            ],
            'webpush' => [
                'notification' => [
                    'icon' => 'https://thecyctechnology.com/development/assets/img/logo-mini.png',
                ],
            ],
        ],
    ];

    if ($url) {
        $mensaje['message']['webpush']['fcm_options'] = ['link' => $url];
    }
    if ($datos) {
        // FCM exige que todos los valores de 'data' sean strings
        $mensaje['message']['data'] = array_map('strval', $datos);
    }

    $ch = curl_init("https://fcm.googleapis.com/v1/projects/$projectId/messages:send");
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . fcmObtenerAccessToken(),
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS     => json_encode($mensaje),
    ]);
    $respuesta = json_decode(curl_exec($ch), true);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['ok' => $codigo === 200, 'codigo' => $codigo, 'respuesta' => $respuesta];
}

/* ===================== EJEMPLO DE USO =====================

require 'fcm.php';

$resultado = fcmEnviar(
    $tokenDelUsuario,                 // sacado de tu base de datos
    'Nuevo gasto aprobado',
    'Tu anticipo de viaje fue aprobado',
    'https://thecyctechnology.com/gastos/123',
    ['id_gasto' => 123]
);

if (!$resultado['ok']) {
    // 404 / UNREGISTERED = token vencido o el usuario revocó el permiso -> bórralo de la BD
    $error = $resultado['respuesta']['error']['details'][0]['errorCode'] ?? '';
    if ($resultado['codigo'] === 404 || $error === 'UNREGISTERED') {
        // DELETE FROM tokens_push WHERE token = ...
    }
}

============================================================ */