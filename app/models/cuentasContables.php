<?php
session_start();
require_once('bd.php');
require_once('funciones.php');
$conn = conectar();

switch ($_POST["op"]) {
  case "clase":
    $q = GenerarArray("select * from t_cuentas_contables where LENGTH(cuenta) = 1 order by cuenta");
    echo json_encode(array(
      'error' => false,
      'message' => 'success',
      'data' => $q
    ));
    break;
  case "grupo":
    $clase = $_POST['clase'];
    $q = GenerarArray("select * from t_cuentas_contables where LENGTH(cuenta) = 2 and SUBSTRING(cuenta,1,1) = $clase order by cuenta ");
    echo json_encode(array(
      'error' => false,
      'message' => 'success',
      'data' => $q
    ));
    break;
  case "cuenta":
    $grupo = $_POST['grupo'];
    $q = GenerarArray("select * from t_cuentas_contables where LENGTH(cuenta) = 4 and SUBSTRING(cuenta,1,2) = $grupo order by cuenta ");
    echo json_encode(array(
      'error' => false,
      'message' => 'success',
      'data' => $q
    ));
    break;
  case "subcuenta":
    $cuenta = $_POST['cuenta'];
    $q = GenerarArray("select * from t_cuentas_contables where LENGTH(cuenta) = 6 and SUBSTRING(cuenta,1,4) = $cuenta order by cuenta ");
    echo json_encode(array(
      'error' => false,
      'message' => 'success',
      'data' => $q
    ));
    break;
  case "auxiliar":
    $subcuenta = $_POST['subcuenta'];
    $q = GenerarArray("select * from t_cuentas_contables where LENGTH(cuenta) = 8 and SUBSTRING(cuenta,1,6) = $subcuenta order by cuenta ");
    echo json_encode(array(
      'error' => false,
      'message' => 'success',
      'data' => $q
    ));
    break;
  case "validar":
    $cuenta = $_POST['cuenta'];
    $q = GenerarArray("select * from t_cuentas_contables where cuenta = $cuenta ");
    echo json_encode(array(
      'error' => false,
      'message' => 'success',
      'data' => $q
    ));
    break;
  case "create":
    $cuenta = $_POST['cuenta'];
    $descripcion = $_POST['descripcion'];
    $estado = $_POST['estado'];
    $q = Query("insert into t_cuentas_contables (cuenta,descripcion,activa) values('$cuenta','$descripcion','$estado') ");
    if ($q) {
      echo json_encode(array(
        'error' => false,
        'message' => 'Cuenta creada correctamente'
      ));
    } else {
      echo json_encode(array(
        'error' => true,
        'message' => 'Error al intentar crear la cuenta, valide los datos e intente nuevamente.'
      ));
    }

    break;
  case "update":
    $cuenta = $_POST['cuenta'];
    $descripcion = $_POST['descripcion'];
    $estado = $_POST['estado'];
    $q = Query("update t_cuentas_contables set descripcion = '$descripcion', activa = '$estado' where cuenta = '$cuenta';");
    if ($q) {
      echo json_encode(array(
        'error' => false,
        'message' => 'Cuenta creada correctamente'
      ));
    } else {
      echo json_encode(array(
        'error' => true,
        'message' => 'Error al intentar crear la cuenta, valide los datos e intente nuevamente.'
      ));
    }

    break;
  case "delete":
    $id = $_POST['id'];
    //aqui se debe validar si la cuenta tiene movimiento, para no permitir su eliminacion.    

    $q = Query("delete from t_cuentas_contables where id = $id;");
    if ($q) {
      echo json_encode(array(
        'error' => false,
        'message' => 'Cuenta eliminada correctamente'
      ));
    } else {
      echo json_encode(array(
        'error' => true,
        'message' => 'Error al intentar eliminar la cuenta, valide los datos e intente nuevamente.'
      ));
    }

    break;
  case "listar-todos":
    $q = GenerarArray("select id,cuenta,upper(descripcion) as descripcion,activa from t_cuentas_contables order by cuenta");
    echo json_encode(array(
      'error' => false,
      'message' => 'success',
      'data' => $q
    ));
    break;
  case "buscar-cuenta":
    $buscar = isset($_POST["buscar"]) ? trim($_POST["buscar"]) : "";
    $q = GenerarArray("SELECT 
                          id,
                          CONCAT(cuenta, ' - ', upper(descripcion)) as text,
                          cuenta,
                          descripcion,
                          activa
                        FROM t_cuentas_contables 
                        WHERE 
                          cuenta LIKE '%$buscar%' OR descripcion LIKE '%$buscar%' 
                        ORDER BY
                          cuenta");
    echo json_encode([
      "results" => $q
    ]);
    break;
}