<?php
session_start();
require_once('../../models/bd.php');
require_once('../../models/funciones.php');
$conn = conectar();
date_default_timezone_set('America/Bogota');

$op = $_REQUEST['op'] ?? '';

switch ($op) {
    case 'cambiar_clave':
        cambiarClave($conn);
        break;

    default:
        echo json_encode(['ok' => false, 'msg' => 'Operación no válida']);
        break;
}

function cambiarClave($conn) {
    $token = $_POST["data"]['token'] ?? '';
    $pass1 = $_POST["data"]['new_password'] ?? '';
    $pass2 = $_POST["data"]['new_password_confirm'] ?? '';


    if ($token == '' || $pass1 == '' || $pass1 !== $pass2) {
        echo json_encode(['ok' => false, 'msg' => 'Verifica los datos ingresados']);
        exit;
    }

    $token_hash = hash('sha256', $token);

    $stmt = $conn->prepare("SELECT * FROM password_reset WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1");
    $stmt->bind_param('s', $token_hash);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows == 0) {
        echo json_encode(['ok' => false, 'msg' => 'Token inválido o expirado']);
        exit;
    }

    $row = $res->fetch_assoc();
    $user_id = $row['user_id'];

    // Actualizar clave
    $hash = password_hash($pass1, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE t_usuarios SET clave = ? WHERE id = ?");
    $stmt->bind_param('si', $hash, $user_id);
    $stmt->execute();

    // Marcar token como usado
    $stmt = $conn->prepare("UPDATE password_reset SET used_at = NOW() WHERE id = ?");
    $stmt->bind_param('i', $row['id']);
    $stmt->execute();

    // Invalidar sesiones (si manejas sesiones de usuario)
    if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] == $user_id) {
        session_destroy();
    }

    echo json_encode(['ok' => true, 'msg' => 'Contraseña actualizada correctamente.']);
}
