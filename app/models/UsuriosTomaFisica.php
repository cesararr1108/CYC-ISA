<?php
session_start();
require_once( 'bd.php' );
require_once( 'funciones.php' );
$conn = conectar();

switch ( $_POST[ "op" ] ) {
    case 'add-usuarios':
   print_r($_POST);
   return;
   /*
        try {
            // Asumiendo que tienes una conexión PDO en $pdo
            $pdo->beginTransaction();

            $usuarios = explode(',', $_POST['usuarios']);

            if (count($usuarios) > 0) {
                foreach ($usuarios as $u) {
                    $usurio = array(
                        'id_usuario' => $u,
                        'id_bodega' => $_POST["bodega"],
                        'conteo' => 1
                    );

                    $result = insertReturnId('t_usuarios_toma_fisica', $usurio, false);

                    // Puedes verificar si insertó correctamente según tu lógica
                    if (!$result) {
                        throw new Exception("Error al insertar usuario ID $u");
                    }
                }
            }

            $pdo->commit();

            echo json_encode(array(
                'ok' => true,
                'message' => 'Se insertó con éxito los registros'
            ));
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(array(
                'ok' => false,
                'message' => 'Error: ' . $e->getMessage()
            ));
        }*/

    break;
    case "listar":
    break;
}