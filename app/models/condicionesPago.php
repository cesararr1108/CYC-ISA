<?php
session_start();
require_once('bd.php');
require_once('funciones.php');
$conn = conectar();

switch ($_POST["op"]) {
  case "listar":
    $sql = "select * from t_condiciones_pago where tipo_condicion = '" . $_POST['tipo'] . "'";
    $tipos = GenerarArray($sql);
    echo json_encode(array(
      'error' => false,
      'message' => 'success',
      'data' => $tipos
    ));
    break;
  case "listar-todos":
    $sql = "select * from t_condiciones_pago";
    $tipos = GenerarArray($sql);
    echo json_encode(array(
      'error' => false,
      'message' => 'success',
      'data' => $tipos
    ));
    break;
  case "agregar":
    $data = $_POST;

    $data["usuario_creacion"] = $_SESSION["Ses_id"];
    $id = insertReturnId('t_condiciones_pago', $data, true);

    if ($id > 0) {
      $data["id"] = $id;
      echo json_encode(array(
        'error' => false,
        'message' => 'Se inserto correctamente el registro',
        'data' => $data
      ));
    } else {
      echo json_encode(array(
        'error' => false,
        'message' => 'Error al insertar el registro',
        'data' => $data
      ));
    }
    break;
  case "update":
    $data = $_POST;
    $data["usuario_creacion"] = $_SESSION["Ses_id"];
    $update = update('t_condiciones_pago', $data, $data["id"]);

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
  case "delete":
    $id = $_POST['id'];

    $sw = validarDependencias(['t_transacciones', 't_transacciones_tmp', 't_pedidos', 't_terceros'], 'id_condicion', $id);

    if (!$sw) {
      echo  json_encode(array(
        'error'     => true,
        'message'   => 'No se puede eliminar el registro ya que existen dependencias ',
        'data'      => null
      ));
      return;
    }

    $sw = validarDependencias(['t_terceros'], 'id_condicion_pago_prov', $id);
    if (!$sw) {
      echo  json_encode(array(
        'error'     => true,
        'message'   => 'No se puede eliminar el registro ya que existen dependencias ',
        'data'      => null
      ));
      return;
    }

    $sql = "delete from t_condiciones_pago where id = $id;";
    if (Query($sql)) {
      echo json_encode(array(
        'error' => false,
        'message' => 'La condicion de pago fue eliminada correctamente.'
      ));
    } else {
      echo json_encode(array(
        'error' => true,
        'message' => 'Hubo un error al intentar eliminar la condicion de pago, verifique e intente nuevamente.'
      ));
    }
    break;
}
