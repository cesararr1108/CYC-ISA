<?php
session_start();
require_once( '../../models/bd.php' );
require_once( '../../models/funciones.php' );
$conn = conectar();
try {

    if(!isset($_POST["id"])){
        return;
    }
    $id=$_POST["id"];

    //code...
    $directorio = "../../../assets/img/usuarios/";

    if (!file_exists($directorio)) {
        mkdir($directorio, 0755, true);
    }

    // Verifica permisos de escritura
    if (!is_writable($directorio)) {
        echo json_encode(array(
            'ok' => false,
            'message' => 'La carpeta no tiene permisos de escritura: ' . realpath($directorio)
        ));
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
        $archivo = $_FILES['archivo'];
        $extension = pathinfo($archivo["name"], PATHINFO_EXTENSION);

        $clave = bin2hex(random_bytes(8)); // 16 caracteres aleatorios seguros
        $nombreFinal= $clave . '.' . $extension;
        $rutaDestino = $directorio . $nombreFinal;

        // Validaciones básicas
        $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'docx', 'txt'];
        if (!in_array(strtolower($extension), $extensionesPermitidas)) {
             echo json_encode(array(
                'ok'=>false,
                'message'=>'extension no permitida'
            ));
            exit;
        }

        if (move_uploaded_file($archivo["tmp_name"], $rutaDestino)) {
           $update = Query("update t_usuarios set foto='$nombreFinal' where id=$id");
            echo  json_encode(array(
                'ok'=>true,
                'message'=>'Archivo cargado correctamente'
            ));
        } else {
                $lastError = error_get_last();
                $errorMessage = $lastError ? $lastError['message'] : 'No se pudo mover el archivo. Verifique permisos y ruta.';

            echo json_encode(array(
                'ok'=>false,
                'message'=>$errorMessage
            ));
        }
    } else {
            echo json_encode(array(
                'ok'=>false,
                'message'=>'no se envio el archivo'
            ));
    }
} catch (\Throwable $th) {

    //throw $th;
             echo json_encode(array(
            'ok'=>false,
            'message'=>$th->getMessage()
        ));
}

?>
