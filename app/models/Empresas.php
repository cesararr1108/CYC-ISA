<?php
session_start();
require_once('bd.php');
require_once('funciones.php');
$conn = conectar();

function deleteLogo($id, $logo)
{
  $id = (int)$id;

  // Evitar que alguien mande una ruta
  $logo = basename($logo);

  // Buscar logo actual en BD
  $result = Query("SELECT logo FROM t_empresa WHERE id = $id");

  if (!$result) {
    echo json_encode([
      'error' => true,
      'message' => 'No se pudo consultar la empresa.'
    ]);
    return;
  }

  $row = mysqli_fetch_assoc($result);

  if (!$row || empty($row['logo'])) {
    echo json_encode([
      'error' => true,
      'message' => 'La empresa no tiene un logo.'
    ]);
    return;
  }

  // Verificar que el logo que se quiere eliminar
  // sea realmente el que está registrado
  if ($row['logo'] !== $logo) {
    echo json_encode([
      'error' => true,
      'message' => 'El logo no coincide con el registrado.'
    ]);
    return;
  }

  // Ruta física
  $imagenPath = __DIR__ . "/../../assets/img/logo/" . $logo;

  // Eliminar archivo
  if (file_exists($imagenPath)) {

    if (!unlink($imagenPath)) {
      echo json_encode([
        'error' => true,
        'message' => 'No se pudo eliminar el archivo.'
      ]);
      return;
    }
  }

  // Eliminar referencia de BD
  Query("
        UPDATE t_empresa
        SET logo = ''
        WHERE id = $id
    ");

  echo json_encode([
    'error' => false,
    'message' => 'Logo eliminado correctamente.'
  ]);
}

function upLogo($data, $id)
{
  $id = (int)$id;

  // Obtener logo actual
  $result = Query("SELECT logo FROM t_empresa WHERE id = $id");

  if ($result) {
    $row = mysqli_fetch_assoc($result);

    if (!empty($row['logo'])) {
      $imagenPath = __DIR__ . "/../../assets/img/logo/" . basename($row['logo']);

      if (file_exists($imagenPath)) {
        unlink($imagenPath);
      }
    }

    Query("UPDATE t_empresa SET logo = '' WHERE id = $id");
  }

  // Verificar que llegó el archivo
  if (
    !isset($_FILES['file']) ||
    $_FILES['file']['error'] !== UPLOAD_ERR_OK
  ) {
    echo json_encode([
      'error' => true,
      'message' => 'No se recibió correctamente el archivo.'
    ]);
    return;
  }

  $fileTmpPath = $_FILES['file']['tmp_name'];
  $fileName    = $_FILES['file']['name'];
  $fileSize    = $_FILES['file']['size'];

  // Tamaño máximo: 10 MB
  $maxFileSize = 10 * 1024 * 1024;

  if ($fileSize > $maxFileSize) {
    echo json_encode([
      'error' => true,
      'message' => 'El archivo es demasiado grande.'
    ]);
    return;
  }

  // Detectar MIME real
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $fileType = finfo_file($finfo, $fileTmpPath);
  finfo_close($finfo);

  $allowedFileTypes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif'
  ];

  if (!isset($allowedFileTypes[$fileType])) {
    echo json_encode([
      'error' => true,
      'message' => 'Archivo no permitido: ' . $fileName
    ]);
    return;
  }

  // Crear carpeta
  $ruta = __DIR__ . "/../../assets/img/logo/";

  if (!is_dir($ruta)) {
    if (!mkdir($ruta, 0777, true)) {
      echo json_encode([
        'error' => true,
        'message' => 'No se pudo crear la carpeta de logos.'
      ]);
      return;
    }
  }

  // Nombre nuevo
  $extension = $allowedFileTypes[$fileType];

  $newFileName = uniqid('img_', true) . '.' . $extension;

  $destino = $ruta . $newFileName;

  // Mover archivo
  if (!move_uploaded_file($fileTmpPath, $destino)) {
    echo json_encode([
      'error' => true,
      'message' => 'No se pudo guardar el archivo.'
    ]);
    return;
  }

  // Guardar nombre en BD
  Query("UPDATE t_empresa
        SET logo = '$newFileName'
        WHERE id = $id
    ");

  echo json_encode([
    'error' => false,
    'message' => 'Logo actualizado correctamente.',
    'file' => $newFileName
  ]);
}

switch ($_POST["op"]) {
  case "listar":
    $sql = "select * from t_empresa ";
    $empresa = GenerarArray($sql);
    echo json_encode($empresa);
    break;
  case 'upLogo':
    upLogo($_POST, (int)$_POST['id']);
    break;
  case 'deleteLogo':
    deleteLogo($_POST['id'], $_POST['logo']);
    break;
  case "u_empresa":
    $data = $_POST;
    $update = update('t_empresa', $data, $data["id"]);
    if ($update) {
      $id = $data["id"];
      echo json_encode(array(
        'error' => false,
        'message' => 'Empresa actualizada correctamente.',
        'data' => $data
      ));
    } else {
      echo json_encode(array(
        'error' => true,
        'message' => 'No se pudo actualizar la Empresa'
      ));
    }
    break;
  case "g_empresa":
    $data = $_POST;
    $id = insertReturnId('t_empresa', $data, true);
    if ($id > 0) {
      echo json_encode(array(
        'error' => false,
        'message' => 'Empresa crear correctamente.',
        'data' => $data
      ));
    } else {
      echo json_encode(array(
        'error' => true,
        'message' => 'No se pudo crear la Empresa'
      ));
    }

    break;
  case 's_referencia_logo':
    $imagePath = '../../assets/img/logo/' . basename($_POST['logo']);
    if (file_exists($imagePath)) {
      $size = filesize($imagePath); // Obtiene el tamaño en bytes
    } else {
      $size = 0; // Si no existe la imagen, asignamos tamaño 0
    }
    // Añadimos la URL de la imagen junto con el tamaño
    $result[] = [
      'imagen' => $_POST['logo'],
      'size' => $size,
      'path' => $imagePath
    ];

    echo json_encode(array(
      'error' => false,
      'data' => $result
    ));
    break;
}
