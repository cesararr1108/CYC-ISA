<?php
session_start();
require_once('bd.php');
require_once('funciones.php');
$conn = conectar();

if (!sessionExists()) {
  echo json_encode(array(
    'error' => true,
    'message' => 'La sesion se ha cerrado, por favor ingrese nuevamente'
  ));
  return;
}

/**
 * Obtiene los proveedores disponibles
 */
function obtenerProveedores() {
  $query = "SELECT id, nit, CONCAT(tratamiento, ' ', razon_comercial) as nombre, razon_comercial
            FROM t_terceros 
            WHERE es_proveedor = 'S' AND activo = 'S'
            ORDER BY razon_comercial ASC";
  return GenerarArray($query);
}

/**
 * Obtiene información del proveedor por NIT
 */
function obtenerProveedorPorNit($nit) {
  $nit = (int)$nit;
  $query = "SELECT id, nit, CONCAT(tratamiento, ' ', razon_comercial) as nombre
            FROM t_terceros 
            WHERE nit = $nit AND es_proveedor = 'S' AND activo = 'S'
            LIMIT 1";
  $resultado = GenerarArray($query);
  return (count($resultado) > 0) ? $resultado[0] : null;
}

/**
 * Busca una referencia por EAN
 */
function buscarReferenciaPorEan($ean) {
  $conn = conectar();
  $ean = $conn->real_escape_string($ean);
  $query = "SELECT r.id, r.codigo, r.descripcion, e.ean 
            FROM t_referencias r
            LEFT JOIN t_referencias_ean e ON e.id_referencia = r.id
            WHERE e.ean = '$ean' OR r.codigo = '$ean'
            LIMIT 1";
  $resultado = GenerarArray($query);
  return (count($resultado) > 0) ? $resultado[0] : null;
}

/**
 * Guarda o actualiza el precio del listado
 */
function guardarListadoPrecio($ean, $id_referencia, $nit_proveedor, $precio, $precio_iva, $usuario_id) {
  $conn = conectar();
  
  $ean = $conn->real_escape_string($ean);
  $id_referencia = (int)$id_referencia;
  $nit_proveedor = (int)$nit_proveedor;
  $precio = (float)$precio;
  $precio_iva = (float)$precio_iva;
  $usuario_id = (int)$usuario_id;

  // Verificar si ya existe el registro
  $query_check = "SELECT id FROM t_listados_precios_proveedor 
                  WHERE ean = '$ean' AND id_referencia = $id_referencia AND nit_proveedor = $nit_proveedor
                  LIMIT 1";
  $existe = GenerarArray($query_check);

  if (count($existe) > 0) {
    // Actualizar registro existente
    $query_update = "UPDATE t_listados_precios_proveedor 
                     SET precio = $precio, precio_iva = $precio_iva, usuario_actualizacion = $usuario_id,
                         fecha_actualizacion = NOW()
                     WHERE ean = '$ean' AND id_referencia = $id_referencia AND nit_proveedor = $nit_proveedor";
    $resultado = Query($query_update);
    
    registrarAuditoria('ACTUALIZAR', 't_listados_precios_proveedor', $existe[0]['id'], 
      "Actualización de precio: $precio, precio_iva: $precio_iva", $usuario_id, $conn);
    
    return array('exito' => true, 'accion' => 'actualizar', 'id' => $existe[0]['id']);
  } else {
    // Insertar nuevo registro
    $data = array(
      'ean' => $ean,
      'id_referencia' => $id_referencia,
      'nit_proveedor' => $nit_proveedor,
      'precio' => $precio,
      'precio_iva' => $precio_iva,
      'usuario_actualizacion' => $usuario_id
    );
    
    $id = insertReturnId('t_listados_precios_proveedor', $data, false);
    
    if ($id > 0) {
      registrarAuditoria('INSERTAR', 't_listados_precios_proveedor', $id, 
        "Nuevo listado de precios: precio: $precio, precio_iva: $precio_iva", $usuario_id, $conn);
      return array('exito' => true, 'accion' => 'insertar', 'id' => $id);
    } else {
      return array('exito' => false, 'error' => 'No se pudo guardar el registro');
    }
  }
}

// Procesar operaciones
$op = isset($_POST['op']) ? $_POST['op'] : '';

switch ($op) {
  case 'obtener_proveedores':
    $proveedores = obtenerProveedores();
    echo json_encode(array('exito' => true, 'proveedores' => $proveedores));
    break;

  case 'obtener_proveedor_nit':
    $nit = isset($_POST['nit']) ? $_POST['nit'] : '';
    $proveedor = obtenerProveedorPorNit($nit);
    if ($proveedor) {
      echo json_encode(array('exito' => true, 'proveedor' => $proveedor));
    } else {
      echo json_encode(array('exito' => false, 'mensaje' => 'Proveedor no encontrado'));
    }
    break;

  case 'validar_ean':
    $ean = isset($_POST['ean']) ? $_POST['ean'] : '';
    $referencia = buscarReferenciaPorEan($ean);
    if ($referencia) {
      echo json_encode(array(
        'exito' => true, 
        'referencia' => $referencia,
        'encontrado' => true
      ));
    } else {
      echo json_encode(array(
        'exito' => false, 
        'encontrado' => false,
        'mensaje' => 'El EAN no existe en el sistema'
      ));
    }
    break;

  default:
    echo json_encode(array('error' => true, 'message' => 'Operación no válida'));
    break;
}
?>
