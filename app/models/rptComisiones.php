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
  case 's_comisiones_detalle':
    $fhIni = $_POST["fechaInicio"];
    $fhFin = $_POST["fechaFin"];
    $id_usuario = $_SESSION["Ses_id"];
    $id_rol = $_SESSION["Ses_roles_id"];

    // Obtener filtros de bodegas
    $bodegas = intval($_POST["bodegas"]);

    $sql = "SELECT 
              b.descripcion AS desc_bodega,
              r.codigo,
              r.descripcion AS producto,
              r.descripcion_larga,
              m.descripcion AS marca,
              CAST(td.cantidad/td.can_und AS DECIMAL(10,0)) AS cantidad,
              td.und,
              td.can_und,
              td.valor_unitario,
              td.costo_unitario,
              td.pcj_iva,
              td.pcj_descuento,
              td.lote,
              td.vmcto,
              tr.numero,
              td.fec AS fecha_documento,
              tc.razon_comercial,
              ifnull(CONCAT(tc.primer_apellido, ' ', tc.segundo_apellido, ' ', tc.primer_nombre, ' ', tc.segundo_nombre, ' ', tc.tratamiento), '') AS nombres,
              tc.nit,
              CONCAT(IFNULL(u.nombres, ''), ' ', IFNULL(u.apellidos, '')) AS usuario_nombre,
              tv.tratamiento AS vendedor_nombre,
              tr.vendedor AS vendedor_nit,
              b.id as id_bodega,
              td.id_bodega,
              r.id as id_referencia,
              tr.id as id_transaccion,
              td.id as id_transaccion_detalle,
              td.comision,
              /* Cálculo del valor base (valor unitario * cantidad) */
              ROUND(td.valor_unitario * td.cantidad, 2) as valor_bruto,
              /* Aplicar descuento si existe */
              ROUND((td.valor_unitario * td.cantidad) * (1 - (td.pcj_descuento / 100)), 2) as valor_con_descuento,
              /* Valor/Porcentaje asignado para la comisión */

              /* Calcular comisión total: valor unitario de comisión * cantidad, negativa para devoluciones */
              CASE 
                WHEN td.sw = 4 THEN ROUND(IFNULL(td.comision, 0) * td.cantidad * -1, 2)
                ELSE ROUND(IFNULL(td.comision, 0) * td.cantidad, 2)
              END as valor_comision,
              td.sw
            FROM t_transacciones_detalle td
            INNER JOIN t_referencias r ON r.id = td.id_referencia
            INNER JOIN t_transacciones tr ON tr.id = td.id_transaccion
            LEFT JOIN t_terceros tc ON tc.id = tr.id_tercero
            LEFT JOIN t_terceros tv ON tv.nit = tr.vendedor
            LEFT JOIN t_almacenes b ON b.id = td.id_bodega
            LEFT JOIN t_marcas m ON m.id = r.id_marca
            LEFT JOIN t_usuarios u ON u.id = tr.id_usuario
            WHERE 
              CAST(td.fec AS DATE) BETWEEN '$fhIni' AND '$fhFin'
              AND IFNULL(td.comision, 0) > 0 AND (td.sw=4 OR td.sw=5) ";

    // Filtrar por bodegas si se especificaron
    if ($bodegas != 0) {
      $sql .= " AND td.id_bodega = $bodegas";
    }

    // Filtrar por usuario si se especificó
    $id_usuario_filtro = isset($_POST["idUsuario"]) ? intval($_POST["idUsuario"]) : 0;
    if ($id_usuario_filtro > 0) {
      $sql .= " AND tr.id_usuario = $id_usuario_filtro";
    }

    // Filtrar por rol si el usuario no es administrador o gerente
    if ($id_rol != '1' && $id_rol != '4') {
      $sql .= " AND tr.id_usuario = $id_usuario";
    }
    //echo $sql;

    $q = GenerarArray($sql);
    echo json_encode($q);
    break;

  case 's_comisiones_resumen':
    $fhIni = $_POST["fechaInicio"];
    $fhFin = $_POST["fechaFin"];
    $id_usuario = $_SESSION["Ses_id"];
    $id_rol = $_SESSION["Ses_roles_id"];

    // Obtener filtros de bodegas
    $bodegas = intval($_POST["bodegas"]);

    $sql = "SELECT 
              SUM(ROUND(td.valor_unitario * td.cantidad, 2)) as total_ventas_bruto,
              SUM(ROUND((td.valor_unitario * td.cantidad) * (1 - (td.pcj_descuento / 100)), 2)) as total_ventas_neto,
              SUM(
                CASE 
                  WHEN td.sw = 4 THEN ROUND(IFNULL(td.comision, 0) * td.cantidad * -1, 2)
                  ELSE ROUND(IFNULL(td.comision, 0) * td.cantidad, 2)
                END
              ) as total_comisiones,
              COUNT(DISTINCT td.id) as total_items,
              COUNT(DISTINCT tr.id) as total_transacciones
            FROM t_transacciones_detalle td
            INNER JOIN t_referencias r ON r.id = td.id_referencia
            INNER JOIN t_transacciones tr ON tr.id = td.id_transaccion
            LEFT JOIN t_usuarios u ON u.id = tr.id_usuario
            WHERE 
              CAST(td.fec AS DATE) BETWEEN '$fhIni' AND '$fhFin'
              AND IFNULL(td.comision, 0) > 0 AND (td.sw=4 OR td.sw=5)";

    // Filtrar por bodegas si se especificaron
    if ($bodegas != 0) {
      $sql .= " AND td.id_bodega = $bodegas";
    }


    // Filtrar por usuario si se especificó
    $id_usuario_filtro = isset($_POST["idUsuario"]) ? intval($_POST["idUsuario"]) : 0;
    if ($id_usuario_filtro > 0) {
      $sql .= " AND tr.id_usuario = $id_usuario_filtro";
    }

    // Filtrar por rol si el usuario no es administrador o gerente
    if ($id_rol != '1' && $id_rol != '4') {
      $sql .= " AND tr.id_usuario = $id_usuario";
    }

    $q = GenerarArray($sql);
    echo json_encode($q);
    break;

  case 's_comisiones_por_vendedor':
    $fhIni = $_POST["fechaInicio"];
    $fhFin = $_POST["fechaFin"];
    $id_usuario = $_SESSION["Ses_id"];
    $id_rol = $_SESSION["Ses_roles_id"];
    $bodegas = intval($_POST["bodegas"]);

    $sql = "SELECT 
              COALESCE(tv.nit, tr.vendedor, 'Sin vendedor') AS vendedor_nit,
              COALESCE(CONCAT(IFNULL(tv.primer_apellido, ''), ' ', IFNULL(tv.segundo_apellido, ''), ' ', IFNULL(tv.primer_nombre, ''), ' ', IFNULL(tv.segundo_nombre, '')), 'Sin vendedor') AS vendedor_nombre,
              CONCAT(IFNULL(u.nombres, ''), ' ', IFNULL(u.apellidos, '')) AS usuario_nombre,
              COUNT(DISTINCT tr.id) as total_transacciones,
              COUNT(DISTINCT td.id) as total_items,
              /* Ventas netas sin devoluciones */
              SUM(
                CASE 
                  WHEN td.sw = 5 THEN ROUND((td.valor_unitario * td.cantidad) * (1 - (td.pcj_descuento / 100)), 2)
                  ELSE 0
                END
              ) as venta_neta_ventas,
              /* Devoluciones (mostrar como positivo para restar) */
              SUM(
                CASE 
                  WHEN td.sw = 4 THEN ROUND((td.valor_unitario * td.cantidad) * (1 - (td.pcj_descuento / 100)), 2)
                  ELSE 0
                END
              ) as venta_neta_devoluciones,
              /* Comisiones totales (con devoluciones negativas) */
              SUM(
                CASE 
                  WHEN td.sw = 4 THEN ROUND(IFNULL(td.comision, 0) * td.cantidad * -1, 2)
                  ELSE ROUND(IFNULL(td.comision, 0) * td.cantidad, 2)
                END
              ) as total_comisiones
            FROM t_transacciones_detalle td
            INNER JOIN t_referencias r ON r.id = td.id_referencia
            INNER JOIN t_transacciones tr ON tr.id = td.id_transaccion
            LEFT JOIN t_terceros tv ON tv.nit = tr.vendedor
            LEFT JOIN t_almacenes b ON b.id = td.id_bodega
            LEFT JOIN t_usuarios u ON u.id = tr.id_usuario
            WHERE 
              CAST(td.fec AS DATE) BETWEEN '$fhIni' AND '$fhFin'
              AND IFNULL(td.comision,0) > 0 AND (td.sw=4 OR td.sw=5)";

    // Filtrar por bodegas si se especificaron
    if ($bodegas != 0) {
      $sql .= " AND td.id_bodega = $bodegas";
    }

    // Filtrar por usuario si se especificó
    $id_usuario_filtro = isset($_POST["idUsuario"]) ? intval($_POST["idUsuario"]) : 0;
    if ($id_usuario_filtro > 0) {
      $sql .= " AND tr.id_usuario = $id_usuario_filtro";
    }

    // Filtrar por rol si el usuario no es administrador o gerente
    if ($id_rol != '1' && $id_rol != '4') {
      $sql .= " AND tr.id_usuario = $id_usuario";
    }

    $sql .= " GROUP BY tr.vendedor, tv.nit,tv.primer_apellido, tv.segundo_apellido, tv.primer_nombre, tv.segundo_nombre, u.nombres,u.apellidos
              ORDER BY total_comisiones DESC, venta_neta_ventas DESC";


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
              AND comision = 'S'
            ORDER BY codigo
            LIMIT 10";

    $q = GenerarArray($sql);
    echo json_encode($q);
    break;

  case 'buscar-usuarios':
    $filtro = isset($_POST["filtro"]) ? $_POST["filtro"] : "";
    $filtro = trim(htmlspecialchars($filtro));

    if (strlen($filtro) < 2) {
      echo json_encode([]);
      return;
    }

    $sql = "SELECT 
              id as id_usuario,
              CONCAT(IFNULL(nombres, ''), ' ', IFNULL(apellidos, '')) as nombre_usuario,
              usuario as login
            FROM t_usuarios
            WHERE 
              (usuario LIKE '%$filtro%' OR nombres LIKE '%$filtro%' OR apellidos LIKE '%$filtro%')
            ORDER BY nombres
            LIMIT 10";

    $q = GenerarArray($sql);
    echo json_encode($q);
    break;

  case 'exportar_comisiones':
    $fhIni = $_POST["fechaInicio"];
    $fhFin = $_POST["fechaFin"];
    $id_usuario = $_SESSION["Ses_id"];
    $id_rol = $_SESSION["Ses_roles_id"];
    $bodegas = isset($_POST["bodegas"]) ? json_decode($_POST["bodegas"], true) : [];

    $sql = "SELECT 
              b.descripcion AS desc_bodega,
              r.codigo,
              r.descripcion AS producto,
              m.descripcion AS marca,
              td.cantidad,
              td.und,
              td.valor_unitario,
              td.pcj_descuento,
              CONCAT(IFNULL(u.nombres, ''), ' ', IFNULL(u.apellidos, '')) AS usuario_nombre,
              tv.razon_comercial AS vendedor_nombre,
              tr.vendedor AS vendedor_nit,
              td.comision,
              ROUND(td.valor_unitario * td.cantidad, 2) as valor_bruto,
              ROUND((td.valor_unitario * td.cantidad) * (1 - (td.pcj_descuento / 100)), 2) as valor_con_descuento,
              /* Valor/Porcentaje asignado para la comisión - negativa para devoluciones */
              CASE 
                WHEN td.sw = 4 THEN ROUND(IFNULL(td.comision, 0) * td.cantidad * -1, 2)
                ELSE ROUND(IFNULL(td.comision, 0) * td.cantidad, 2)
              END as valor_comision,
              tc.razon_comercial,
              tr.numero,
              td.fec AS fecha_documento,
              td.sw
            FROM t_transacciones_detalle td
            INNER JOIN t_referencias r ON r.id = td.id_referencia
            INNER JOIN t_transacciones tr ON tr.id = td.id_transaccion
            LEFT JOIN t_terceros tc ON tc.id = tr.id_tercero
            LEFT JOIN t_terceros tv ON tv.nit = tr.vendedor
            LEFT JOIN t_almacenes b ON b.id = td.id_bodega
            LEFT JOIN t_marcas m ON m.id = r.id_marca
            LEFT JOIN t_usuarios u ON u.id = tr.id_usuario
            WHERE 
              CAST(td.fec AS DATE) BETWEEN '$fhIni' AND '$fhFin'
              AND IFNULL(td.comision, 0) > 0 AND (td.sw=4 OR td.sw=5)";

    if (!empty($bodegas)) {
      $bodegas_list = "'" . implode("','", array_map('intval', $bodegas)) . "'";
      $sql .= " AND td.id_bodega IN ($bodegas_list)";
    }

    // Filtrar por usuario si se especificó
    $id_usuario_filtro = isset($_POST["idUsuario"]) ? intval($_POST["idUsuario"]) : 0;
    if ($id_usuario_filtro > 0) {
      $sql .= " AND tr.id_usuario = $id_usuario_filtro";
    }

    if ($id_rol != '1' && $id_rol != '4') {
      $sql .= " AND tr.id_usuario = $id_usuario";
    }

    $sql .= " ORDER BY td.fec DESC, r.codigo";
    $datos = GenerarArray($sql);
    echo json_encode($datos);
    break;
}
