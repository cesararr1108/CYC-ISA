<?php
session_start();
require_once('bd.php');
require_once('funciones.php');
$conn = conectar();


switch ($_POST["op"]) {

    case "agregar":
        $data = $_POST;
        $data["usuario_creacion"] = $_SESSION["Ses_id"];
        $data["estado"] = 'A';
        $id = insertReturnId('t_roles', $data, true);

        if ($id > 0) {
            $data["id"] = $id;
            echo  json_encode(array(
                'error'     => false,
                'message'   => 'Se inserto correctamente el registro',
                'data'      => $data
            ));
        } else {
            echo  json_encode(array(
                'error'     => false,
                'message'   => 'Error al insertar el registro',
                'data'      => $data
            ));
        }
        break;
    case "update":
        $data = $_POST;
        $data["usuario_creacion"] = $_SESSION["Ses_id"];
        $update = update('t_roles', $data, $data["id"]);

        if ($update) {
            echo  json_encode(array(
                'error'     => false,
                'message'   => 'Se actualizo correctamente el registro',
                'data'      => $data
            ));
        } else {
            echo  json_encode(array(
                'error'     => true,
                'message'   => 'No se pudo actualizar el registro',
                'data'      => $data
            ));
        }
        break;
    case "listar":
        $filtro     = isset($_POST["filtro"]) ? $_POST["filtro"] : '';

        $sql = "select 
                id,
                descripcion,
                estado
              from t_roles 
             
             ";

        if ($filtro != '') {
            $sql .= " where descripcion like '%$filtro%' and estado='A'";
        }


        $sql .= " order by descripcion ";

        $roles = GenerarArray($sql);

        echo  json_encode(array(
            'error'     => false,
            'message'   => 'Success',
            'data'      => $roles
        ));
        break;
    case "delete":
        $id = $_POST["id"];  //t_roles_modulos_permisos  t_permisos_roles 

        $sw = validarDependencias(['t_roles_modulos_permisos'], 'id_rol', $id);

        if (!$sw) {
            echo  json_encode(array(
                'error'     => true,
                'message'   => 'No se puede eliminar el registro ya ue existen dependencias ',
                'data'      => null
            ));
            return;
        }
        $sw = validarDependencias(['t_permisos_roles'], 'roles_id', $id);

        if (!$sw) {
            echo  json_encode(array(
                'error'     => true,
                'message'   => 'No se puede eliminar el registro ya ue existen dependencias ',
                'data'      => null
            ));
            return;
        }

        $delete = Query("delete from t_roles where id=$id");

        if ($delete) {
            echo  json_encode(array(
                'error'     => false,
                'message'   => 'Se elimino correctamente el registro',
                'data'      => null
            ));
        } else {
            echo  json_encode(array(
                'error'     => false,
                'message'   => 'Error al eliminar el registro',
                'data'      => null
            ));
        }
        break;
}
