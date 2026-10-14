<?php
session_start();
require_once('bd.php');
require_once('funciones.php');
$conn = conectar();

switch ($_POST["op"]) {
    case 'add-usuarios':
        try {

            $conn->begin_transaction();

           /// $usuarios = $_POST['usuarios'];

            $usuarios = json_decode($_POST['usuarios'], true);
       
            $bodega = (int)($_POST['bodega'] ?? 0);

            if (!is_array($usuarios)) {
                throw new Exception("El parámetro usuarios no es un array válido");
            }

            if (count($usuarios) == 0) {
                throw new Exception("No se seleccionaron usuarios");
            }

            foreach ($usuarios as $u) {

                $usuario = (int)$u;

                $sql = "
                    INSERT INTO t_usuarios_toma_fisica
                    (
                        id_usuario,
                        id_bodega,
                        conteo
                    )
                    VALUES
                    (
                        $usuario,
                        $bodega,
                        1
                    )
                ";
       
              
                $result = $conn->query($sql);

                if (!$result) {
                    throw new Exception(
                        "Error al insertar usuario ID $usuario: " . $conn->error
                    );
                }
            }

            $conn->commit();

            echo json_encode([
                'ok' => true,
                'message' => 'Se insertaron los usuarios correctamente'
            ]);

        } catch (Throwable $e) {

            $conn->rollback();

            echo json_encode([
                'ok' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }

    break;
    case "listar":
        $bodega = $_POST["bodega"];

        $sql = "
            select 
                u.usuario,
                a.descripcion as bodega,
                a.id as id_bodega,
                f.conteo,
                f.id_usuario
            from t_usuarios_toma_fisica f
            inner join t_usuarios u on u.id=f.id_usuario 
            inner join t_almacenes a on a.id=f.id_bodega
            where f.id_bodega=$bodega
        ";
        $usuarios = GenerarArray($sql);
        echo json_encode($usuarios);
        break;
    case "update":
        $usuario = $_POST["usuario"];
        $bodega = $_POST["bodega"];
        $conteo = $_POST["conteo"];
        $sql = "UPDATE t_usuarios_toma_fisica SET conteo= $conteo WHERE  id_usuario =$usuario  and id_bodega=$bodega";
        $update = $conn->query($sql);

        if ($update) {
            echo json_encode(array(
                'ok' => true,
                '' => 'Se actualizo correctamente el registro'
            ));
        } else {
            echo json_encode(array(
                'ok' => false,
                '' => 'Error al actualizar el registro'
            ));
        }
        break;
    case "elimnar-usuario":
        $usuario = $_POST["id_usuario"];
        $bodega = $_POST["id_bodega"];
        $sql = "DELETE FROM t_usuarios_toma_fisica WHERE  id_usuario =$usuario  and id_bodega=$bodega";
        $delete = $conn->query($sql);

        if ($delete) {
            echo json_encode(array(
                'ok' => true,
                '' => 'Se elimino correctamente el registro'
            ));
        } else {
            echo json_encode(array(
                'ok' => false,
                '' => 'Error al eliminar el registro'
            ));
        }
        break;

}