<?php
session_start();
$_SESSION["Ses_empresa"]=$_POST["empresa"];
require_once('../../models/bd.php');
require_once('../../models/funciones.php');
require_once('../../shared/servicios/servicios-email.php');

$conn = conectar();
date_default_timezone_set('America/Bogota');

$op = $_POST['op'] ?? '';

switch ($op) {
    case 'solicitar_reset':
        solicitarReset($conn);
        break;

    default:
        echo json_encode(['ok' => false, 'msg' => 'Operación no válida']);
        break;
}

function solicitarReset($conn) {
    // 🔐 Validar reCAPTCHA
    $secret = '6LfeaPwrAAAAAOUas4uDFsBholpYpbI26mX9SW18'; // la clave secreta (no la site key)
    $token  = $_POST['g-recaptcha-response'];

    $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secret}&response={$token}");
    $captcha_success = json_decode($verify);

    if (!$captcha_success->success || $captcha_success->score < 0.5) {
        echo json_encode(["ok" => false, "msg" => "Error en la verificación reCAPTCHA , por favor actualice la pagina"]);
        exit;
    }

    $email = trim($_POST['email'] ?? '');
    if ($email == '') {
        echo json_encode(['ok' => false, 'msg' => 'Correo requerido']);
        exit;
    }

    // Buscar usuario
    $stmt = $conn->prepare("SELECT id, usuario, nombres, email FROM t_usuarios WHERE email = ? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows == 0) {
        echo json_encode(['ok' => false, 'msg' => 'No existe una cuenta con ese correo']);
        exit;
    }

    $user = $res->fetch_assoc();
    $user_id = $user['id'];

    // Generar token seguro
    $token = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $token);
    $expires_at = date('Y-m-d H:i:s', strtotime('+45 minutes'));
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // Guardar en BD
    $stmt = $conn->prepare("INSERT INTO password_reset (user_id, token_hash, created_at, expires_at, ip, user_agent)
                            VALUES (?, ?, NOW(), ?, ?, ?)");
    $stmt->bind_param('issss', $user_id, $token_hash, $expires_at, $ip, $ua);
    $stmt->execute();

    // Enlace de reseteo
    $link = "https://thecyctechnology.com/cyc/app/views/auth/reset-password-change.php?token=" . urlencode($token)."&empresa=".$_SESSION["Ses_empresa"];

    // Contenido del correo
    $html = "
        <h3>Hola {$user['nombres']}</h3>
        <p>Recibimos una solicitud para restablecer tu contraseña.</p>
        <p><a href='{$link}' target='_blank' style='background:#7367f0;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Cambiar contraseña</a></p>
        <p>Este enlace caduca en 45 minutos. Si no solicitaste este cambio, ignora este correo.</p>
    ";

    try {
        $correo = new Correo(true, 'CYC');
        $correo->enviarCorreo($email, mb_convert_encoding("Recupera tu contraseña", 'UTF-8', 'ISO-8859-1'), $html);
        echo json_encode(['ok' => true, 'msg' => 'Se envió un enlace de recuperación a tu correo.']);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'msg' => 'Error enviando correo: ' . $e->getMessage()]);
    }
}
