<?php
session_start();
require_once( '../../models/bd.php' );
require_once( '../../models/funciones.php' );
$conn = conectar();

if (!sessionExists()) {
  echo json_encode(array(
    'error' => true,
    'message' => 'La sesion se ha cerrado, por favor ingrese nuevamente'
  ));
  exit;
}

$op = isset($_REQUEST['op']) ? $_REQUEST['op'] : '';

switch ($op) {
  case 'procesar_preview':
    procesarPreview();
    break;

  case 'guardar_listado':
    guardarListado();
    break;

  case 'descargar_plantilla':
    descargarPlantilla();
    break;

  default:
    echo json_encode(array('error' => true, 'message' => 'Operación no válida'));
    break;
}

/**
 * Procesa la previsualización del archivo
 */
function procesarPreview() {
  if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(array(
      'error' => true,
      'message' => 'Error en la carga del archivo'
    ));
    return;
  }

  $archivo = $_FILES['archivo'];
  $nit_proveedor = isset($_POST['nit_proveedor']) ? (int)$_POST['nit_proveedor'] : 0;

  if ($nit_proveedor <= 0) {
    echo json_encode(array(
      'error' => true,
      'message' => 'Proveedor inválido'
    ));
    return;
  }

  $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
  
  // Validar extensión
  if (!in_array(strtolower($extension), ['csv', 'xlsx', 'xls'])) {
    echo json_encode(array(
      'error' => true,
      'message' => 'Solo se permiten archivos CSV, XLSX o XLS'
    ));
    return;
  }

  // Leer el archivo
  $datos = array();
  $errores = array();
  $linea = 0;

  if (strtolower($extension) === 'csv') {
    $handle = fopen($archivo['tmp_name'], 'r');
    $cabecera = fgetcsv($handle, 1000, ",");
    
    while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
      $linea++;
      
      if (empty($row[0])) continue; // Saltar filas vacías
      
      $ean = trim($row[0]);
      $precio = isset($row[1]) ? (float)$row[1] : 0;
      $precio_iva = isset($row[2]) ? (float)$row[2] : 0;

      // Validar que no estén vacíos
      if (empty($ean) || $precio <= 0) {
        $errores[] = "Línea $linea: EAN o precio inválido";
        continue;
      }

      // Buscar la referencia por EAN
      $conn = conectar();
      $ean_escape = $conn->real_escape_string($ean);
      $query = "SELECT r.id, r.codigo, r.descripcion, e.ean 
                FROM t_referencias r
                LEFT JOIN t_referencias_ean e ON e.id_referencia = r.id
                WHERE e.ean = '$ean_escape' OR r.codigo = '$ean_escape'
                LIMIT 1";
      $resultado = GenerarArray($query);

      if (count($resultado) > 0) {
        $referencia = $resultado[0];
        $datos[] = array(
          'ean' => $ean,
          'id_referencia' => $referencia['id'],
          'codigo' => $referencia['codigo'],
          'descripcion' => $referencia['descripcion'],
          'precio' => $precio,
          'precio_iva' => $precio_iva,
          'estado' => 'encontrado',
          'linea' => $linea
        );
      } else {
        $datos[] = array(
          'ean' => $ean,
          'precio' => $precio,
          'precio_iva' => $precio_iva,
          'estado' => 'no_encontrado',
          'linea' => $linea,
          'error' => 'El EAN no existe en el sistema'
        );
        $errores[] = "Línea $linea: EAN '$ean' no encontrado en el sistema";
      }
    }
    fclose($handle);
  } else {
    // Para archivos XLSX/XLS, intentar usar SimpleXML o convertir a CSV
    echo json_encode(array(
      'error' => true,
      'message' => 'Por favor, cargue el archivo en formato CSV. Puede exportar desde Excel como CSV (separado por comas)'
    ));
    return;
  }

  if (count($datos) === 0) {
    echo json_encode(array(
      'error' => true,
      'message' => 'El archivo no contiene datos válidos'
    ));
    return;
  }

  echo json_encode(array(
    'error' => false,
    'datos' => $datos,
    'errores' => $errores,
    'total_filas' => count($datos),
    'filas_validas' => count(array_filter($datos, function($d) { return $d['estado'] === 'encontrado'; })),
    'filas_problematicas' => count(array_filter($datos, function($d) { return $d['estado'] === 'no_encontrado'; }))
  ));
}

/**
 * Guarda el listado de precios
 */
function guardarListado() {
  $datos = isset($_POST['datos']) ? json_decode($_POST['datos'], true) : array();
  $nit_proveedor = isset($_POST['nit_proveedor']) ? (int)$_POST['nit_proveedor'] : 0;
  $usuario_id = $_SESSION['Ses_id'];

  if ($nit_proveedor <= 0 || empty($datos)) {
    echo json_encode(array(
      'error' => true,
      'message' => 'Datos incompletos'
    ));
    return;
  }

  $guardados = 0;
  $errores = array();
  $conn = conectar();

  foreach ($datos as $item) {
    if ($item['estado'] !== 'encontrado') {
      continue; // Solo guardar los que fueron encontrados
    }

    $ean = $conn->real_escape_string($item['ean']);
    $id_referencia = (int)$item['id_referencia'];
    $precio = (float)$item['precio'];
    $precio_iva = (float)$item['precio_iva'];
    
    //omito los precios en 0
    if ($precio <= 0 || $precio_iva <= 0) {
      continue;
    }
    // Verificar si ya existe
    $query_check = "SELECT id_referencia FROM t_listados_precios_proveedor 
                    WHERE ean = '$ean' AND id_referencia = $id_referencia AND nit_proveedor = $nit_proveedor
                    LIMIT 1";
    $existe = GenerarArray($query_check);

    try {
      if (count($existe) > 0) {
        // Actualizar
        $query_update = "UPDATE t_listados_precios_proveedor 
                         SET precio = $precio+".rand(0,1000).", precio_iva = $precio_iva+".rand(0,1000).", 
                              usuario_actualizacion = $usuario_id,
                             fecha_actualizacion = NOW()
                         WHERE ean = '$ean' AND id_referencia = $id_referencia AND nit_proveedor = $nit_proveedor";
        Query($query_update);
        registrarAuditoria('ACTUALIZAR', 't_listados_precios_proveedor', $existe[0]['id_referencia'], 
          "Actualización masiva: precio: $precio", $usuario_id, $conn);
      } else {
        // Insertar
        $data = array(
          'ean' => $ean,
          'id_referencia' => $id_referencia,
          'nit_proveedor' => $nit_proveedor,
          'precio' => $precio,
          'precio_iva' => $precio_iva,
          'usuario_actualizacion' => $usuario_id
        );
        
        $id = insertReturnId('t_listados_precios_proveedor', $data, false);
        registrarAuditoria('INSERTAR', 't_listados_precios_proveedor', $id, 
          "Carga masiva: precio: $precio", $usuario_id, $conn);
      }
      $guardados++;
    } catch (Exception $e) {
      $errores[] = "Error al guardar EAN " . $item['ean'] . ": " . $e->getMessage();
    }
  }

  echo json_encode(array(
    'error' => false,
    'message' => "Se guardaron $guardados registros correctamente",
    'guardados' => $guardados,
    'errores' => $errores
  ));
}

/**
 * Descarga una plantilla de ejemplo
 */
function descargarPlantilla() {
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename="plantilla_listado_precios.csv"');

  $output = fopen('php://output', 'w');
  
  // Escribir encabezado
  fputcsv($output, array('EAN', 'Precio', 'Precio IVA'));
  
  // Escribir ejemplos
  fputcsv($output, array('7891000000005', '10000', '11900'));
  fputcsv($output, array('7891000000012', '15000', '17850'));
  
  fclose($output);
  exit;
}
?>
