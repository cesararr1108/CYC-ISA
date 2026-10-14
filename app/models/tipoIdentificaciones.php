<?php
session_start();
require_once('bd.php');
require_once('funciones.php');
$conn = conectar();

switch ($_POST['op']) {
  case 'listar':
    $sql = 'select * from t_tipo_identificaciones';
    $tipos = GenerarArray($sql);
    echo json_encode(array(
      'error' => false,
      'message' => 'success',
      'data' => $tipos
    ));
    break;

  case 'agregar':
    $data = $_POST;
    $data['usuario_creacion'] = $_SESSION['Ses_id'];
    $data['estado'] = 'A';
    $id = insertReturnId('t_tipo_identificaciones', $data, true);

    if ($id > 0) {
      $data['id'] = $id;
      echo json_encode(array(
        'error' => false,
        'message' => 'Se inserto correctamente el registro',
        'data' => $data
      ));
    } else {
      echo json_encode(array(
        'error' => true,
        'message' => 'Error al insertar el registro',
        'data' => $data
      ));
    }
    break;

  case 'update':
    $data = $_POST;
    $data['usuario_creacion'] = $_SESSION['Ses_id'];
    $update = update('t_tipo_identificaciones', $data, $data['id']);

    if ($update) {
      echo json_encode(array(
        'error' => false,
        'message' => 'Se actualizo correctamente el registro',
        'data' => $data
      ));
    } else {
      echo json_encode(array(
        'error' => true,
        'message' => 'No se pudo actualizar el registro',
        'data' => $data
      ));
    }
    break;

  case 'delete':
    $id = $_POST['id'];

    $existe = GenerarArray("select * from t_terceros where id_tipo_identificacion=$id");

    if (count($existe) > 0) {
      echo json_encode(array(
        'error' => true,
        'message' => 'No se puede eliminar, existen terceros asociados',
        'data' => null
      ));
      return;
    }

    $delete = Query("delete from t_tipo_identificaciones where id=$id");

    if ($delete) {
      echo json_encode(array(
        'error' => false,
        'message' => 'Se elimino correctamente el registro',
        'data' => null
      ));
    } else {
      echo json_encode(array(
        'error' => true,
        'message' => 'Error al eliminar el registro',
        'data' => null
      ));
    }
    break;
}
