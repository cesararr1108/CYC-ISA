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
switch ($_POST["op"]) {
  case 's_ventas_detalle':
    $fhIni = $_POST["fechaInicio"];
    $fhFin = $_POST["fechaFin"];
    $id_usuario     = $_SESSION["Ses_id"];
    $id_rol         = $_SESSION["Ses_roles_id"];
    
    // Obtener filtros de bodegas y referencias
    $bodegas = isset($_POST["bodegas"]) ? json_decode($_POST["bodegas"], true) : [];
    $id_referencia = isset($_POST["id_referencia"]) ? $_POST["id_referencia"] : "0";
    $id_marca = isset($_POST["id_marca"]) ? intval($_POST["id_marca"]) : 0;
    
    $sql = "SELECT 
                v.desc_bodega,
                v.codigo ,
                v.producto ,
                v.marca ,
                round(v.cantidad ,2) as cantidad,
                v.und ,
                v.can_und ,
                v.porcentaje_iva ,
                v.porcentaje_descuento ,
                v.lote ,
                v.vencimiento_lote as vencimiento ,
                round(v.precio_neto_unidad ,2) as precio_neto_unidad,
                round(v.precio_unitario_unidad,2) as precio_unitario_unidad,
                round(v.precio_neto_unidad * v.cantidad,2) as precio_neto_total,
                v.tipo ,
                v.numero ,
                v.nit,
                v.nombres ,
                v.razon_comercial ,
                CAST(v.fecha_hora_documento AS DATE) as fecha,
                round(v.valor_iva,2) as valor_iva,
                round(v.valor_descuento,2) as valor_descuento,
                round(v.valor_total,2) as valor_total,
                round(v.valor_total_con_iva,2) as valor_total_con_iva,
                IFNULL(v.ean,'') as ean
            FROM v_ventas_general v
            WHERE 
              CAST(v.fecha_hora_documento AS DATE) BETWEEN '$fhIni' AND '$fhFin'";
    
    // Filtrar por bodegas si se especificaron
    if (!empty($bodegas)) {
      $bodegas_list = "'" . implode("','", array_map('intval', $bodegas)) . "'";
      $sql .= " AND v.id_bodega IN ($bodegas_list)";
    }
    
    // Filtrar por referencia si se especificó
    if ($id_referencia !== "0" && !empty($id_referencia)) {
      $id_referencia = intval($id_referencia);
      $sql .= " AND v.id_referencia = $id_referencia";
    }

    // Filtrar por marca si se especificó
    if ($id_marca > 0) {
      $sql .= " AND v.marca = (SELECT descripcion FROM t_marcas WHERE id = $id_marca LIMIT 1)";
    }
    
    if ($id_rol != '1' && $id_rol != '4') {
      $sql .= " and v.id_usuario = $id_usuario";
    }
    $sql .= "
            ORDER BY 
                v.fecha_hora_documento";
                //echo $sql;
    $q = GenerarArray($sql);
    echo json_encode($q);
    break;

  case 'buscar-referencias':
    $filtro = isset($_POST["filtro"]) ? $_POST["filtro"] : "";
    $filtro = trim(htmlspecialchars($filtro));
    
    if (strlen($filtro) < 2) {
      echo json_encode([]);
      return;
    }
    
    $sql = "SELECT 
              id as id_referencia,
              codigo,
              descripcion
            FROM t_referencias
            WHERE 
              (codigo LIKE '%$filtro%' OR descripcion LIKE '%$filtro%')
            ORDER BY codigo
            LIMIT 10";
    
    $q = GenerarArray($sql);
    echo json_encode($q);
    break;
}

