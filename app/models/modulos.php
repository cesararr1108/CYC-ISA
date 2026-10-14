<?php
session_start();
require_once('bd.php');
require_once('funciones.php');
$conn = conectar();


switch ($_POST["op"]) {
  case "cargarGrupos":
    $sql = "
        select 
        *
        from t_grupos_modulos 
        order by
        grupo 
    ";
    $grupos = GenerarArray($sql);
    echo json_encode($grupos);
    break;
  case "agregar":

    $data = $_POST;
    $data["usuario_creacion"] = $_SESSION["Ses_id"];
    $id = GenerarArray("select max(id)+1 as id from t_modulos");
    $data["id"] = $id[0]["id"];

    $idReturn = insertReturnId('t_modulos', $data, false);


    if ($idReturn > 0) {
      echo json_encode(array(
        'error' => false,
        'message' => 'Se inserto correctamente el registro',
        'data' => $idReturn
      ));
    } else {
      echo json_encode(array(
        'error' => true,
        'message' => 'Error al insertar el registro',
        'data' => 0
      ));
    }

    break;
  case "update":
    $data = $_POST;
    $update = update('t_modulos', $data, $_POST["id"]);
    if ($update) {
      echo json_encode(array(
        'error' => false,
        'message' => 'Se actualizó correctamente el registro',
        'data' => $data
      ));
    } else {
      echo json_encode(array(
        'error' => true,
        'message' => 'Error al actualizar el registro',
        'data' => $data
      ));
    }
    break;
  case "listar":
    $filtro = $_POST["filtro"];
    $sql = "select 
      m.id,
      m.titulo,
      m.descripcion,
      m.grupo,
      m.version,
      m.url,
      m.icono,
      g.grupo as  nombre_grupo,
      m.grupo_rpt
      from t_modulos m
      left join t_grupos_modulos g on g.id=m.grupo
    ";

    if ($filtro != '') {
      $sql .= " where titulo like '%$filtro%' or descripcion like '%$filtro%'";
    }

    $sql .= "
     order by
      g.grupo,m.titulo ";

    $modulos = GenerarArray($sql);

    echo json_encode(array(
      'error' => false,
      'message' => 'Modulos listados correctamente',
      'data' => $modulos
    ));
    break;
  case "delete":
    $id = $_POST["id"];
    $delete = Query("delete from t_modulos where id=$id");
    if ($delete) {
      echo json_encode(array(
        'error' => false,
        'message' => 'Se elimino correctamente el registro',
        'data' => $id
      ));
    } else {
      echo json_encode(array(
        'error' => true,
        'message' => 'Error al intentar eliminar el registro',
        'data' => $id
      ));
    }
    break;
  case "cargarGruposRpt":
    $sql = "
          select 
          *
          from t_grupos_modulos_rpt 
          order by
          grupo 
      ";
    $grupos = GenerarArray($sql);
    echo json_encode($grupos);
    break;
}
