<?php
session_start();
require_once('bd.php');
require_once('funciones.php');

$conn = conectar();

if (!sessionExists()) {
  echo json_encode(array(
    'error' => true,
    'mensaje' => 'La sesión se ha cerrado, por favor ingrese nuevamente'
  ));
  exit;
}

if ($_POST["op"] == 'consultar') {
  $fhIni = $_POST["fecha_inicio"];
  $fhFin = $_POST["fecha_fin"];
  $id_bodega = intval($_POST["id_bodega"]);
  $id_usuario = $_SESSION["Ses_id"];
  $id_rol = $_SESSION["Ses_roles_id"];

  // Query para rentabilidad por producto
  $sql = "SELECT
            r.codigo,
            r.descripcion producto,
            d.id_bodega,
            b.descripcion desc_bodega,
            ROUND(SUM(CASE WHEN d.sw = 5 THEN (d.valor_unitario *(1-(d.pcj_descuento / 100)))* d.cantidad ELSE (d.valor_unitario *(1-(d.pcj_descuento / 100)))* d.cantidad *-1 END), 2) venta,
            ROUND(SUM(CASE WHEN d.sw = 5 THEN d.costo_unitario * d.cantidad ELSE d.costo_unitario * d.cantidad *-1 END), 2) costo,
            ROUND(SUM(CASE WHEN d.sw = 5 THEN ((d.valor_unitario *(1-(d.pcj_descuento / 100)))-d.costo_unitario)* d.cantidad ELSE ((d.valor_unitario *(1-(d.pcj_descuento / 100)))-d.costo_unitario)* d.cantidad *-1 END), 2) utilidad,
            CASE
              WHEN SUM(CASE WHEN d.sw = 5 THEN (d.valor_unitario *(1-(d.pcj_descuento / 100)))* d.cantidad ELSE (d.valor_unitario *(1-(d.pcj_descuento / 100)))* d.cantidad *-1 END)= 0 THEN 0
              ELSE ROUND((SUM(CASE WHEN d.sw = 5 THEN ((d.valor_unitario *(1-(d.pcj_descuento / 100)))-d.costo_unitario)* d.cantidad ELSE ((d.valor_unitario *(1-(d.pcj_descuento / 100)))-d.costo_unitario)* d.cantidad *-1 END)/ SUM(CASE WHEN d.sw = 5 THEN (d.valor_unitario *(1-(d.pcj_descuento / 100)))* d.cantidad ELSE (d.valor_unitario *(1-(d.pcj_descuento / 100)))* d.cantidad *-1 END))* 100, 2)
            END margen,
            CASE
              WHEN SUM(CASE WHEN d.sw = 5 THEN d.costo_unitario * d.cantidad ELSE d.costo_unitario * d.cantidad *-1 END)= 0 THEN 0
              ELSE ROUND((SUM(CASE WHEN d.sw = 5 THEN ((d.valor_unitario *(1-(d.pcj_descuento / 100)))-d.costo_unitario)* d.cantidad ELSE ((d.valor_unitario *(1-(d.pcj_descuento / 100)))-d.costo_unitario)* d.cantidad *-1 END)/ SUM(CASE WHEN d.sw = 5 THEN d.costo_unitario * d.cantidad ELSE d.costo_unitario * d.cantidad *-1 END))* 100, 2)
            END markup,
            ROUND(SUM(CASE WHEN d.sw = 5 THEN (d.valor_unitario *(1-(d.pcj_descuento / 100))*(1-(COALESCE(c.dto, 0)/ 100)))* d.cantidad ELSE (d.valor_unitario *(1-(d.pcj_descuento / 100))*(1-(COALESCE(c.dto, 0)/ 100)))* d.cantidad *-1 END), 2) venta_pp,
            ROUND(SUM(CASE WHEN d.sw = 5 THEN (((d.valor_unitario *(1-(d.pcj_descuento / 100))*(1-(COALESCE(c.dto, 0)/ 100)))-d.costo_unitario)* d.cantidad) ELSE (((d.valor_unitario *(1-(d.pcj_descuento / 100))*(1-(COALESCE(c.dto, 0)/ 100)))-d.costo_unitario)* d.cantidad)*-1 END), 2) utilidad_pp,
            CASE
              WHEN SUM(CASE WHEN d.sw = 5 THEN (d.valor_unitario *(1-(d.pcj_descuento / 100))*(1-(COALESCE(c.dto, 0)/ 100)))* d.cantidad ELSE (d.valor_unitario *(1-(d.pcj_descuento / 100))*(1-(COALESCE(c.dto, 0)/ 100)))* d.cantidad *-1 END)= 0 THEN 0
              ELSE ROUND((SUM(CASE WHEN d.sw = 5 THEN (((d.valor_unitario *(1-(d.pcj_descuento / 100))*(1-(COALESCE(c.dto, 0)/ 100)))-d.costo_unitario)* d.cantidad) ELSE (((d.valor_unitario *(1-(d.pcj_descuento / 100))*(1-(COALESCE(c.dto, 0)/ 100)))-d.costo_unitario)* d.cantidad)*-1 END)/ SUM(CASE WHEN d.sw = 5 THEN (d.valor_unitario *(1-(d.pcj_descuento / 100))*(1-(COALESCE(c.dto, 0)/ 100)))* d.cantidad ELSE (d.valor_unitario *(1-(d.pcj_descuento / 100))*(1-(COALESCE(c.dto, 0)/ 100)))* d.cantidad *-1 END))* 100, 2)
            END margen_pp,
            CASE
              WHEN SUM(CASE WHEN d.sw = 5 THEN d.costo_unitario * d.cantidad ELSE d.costo_unitario * d.cantidad *-1 END)= 0 THEN 0
              ELSE ROUND((SUM(CASE WHEN d.sw = 5 THEN (((d.valor_unitario *(1-(d.pcj_descuento / 100))*(1-(COALESCE(c.dto, 0)/ 100)))-d.costo_unitario)* d.cantidad) ELSE (((d.valor_unitario *(1-(d.pcj_descuento / 100))*(1-(COALESCE(c.dto, 0)/ 100)))-d.costo_unitario)* d.cantidad)*-1 END)/ SUM(CASE WHEN d.sw = 5 THEN d.costo_unitario * d.cantidad ELSE d.costo_unitario * d.cantidad *-1 END))* 100, 2)
            END markup_pp,
            SUM(d.cantidad) cantidad
          FROM
            t_transacciones_detalle d
          INNER JOIN t_transacciones t ON
            t.id = d.id_transaccion
          LEFT JOIN t_condiciones_pago c ON
            c.id = t.id_condicion
          INNER JOIN t_referencias r ON
            d.id_referencia = r.id
          INNER JOIN t_almacenes b ON
            b.id = d.id_bodega
          WHERE
            d.sw IN(5, 4)
              AND CAST(d.fec AS DATE) BETWEEN '$fhIni' AND '$fhFin'";

  if ($id_bodega != 0) {
    $sql .= " AND d.id_bodega = $id_bodega ";
  }

  $sql .= "GROUP BY
            r.id,
            r.codigo,
            r.descripcion,
            d.id_bodega,
            b.descripcion
          HAVING
            ROUND(SUM(CASE WHEN d.sw = 5 THEN (d.valor_unitario *(1-(d.pcj_descuento / 100)))* d.cantidad ELSE (d.valor_unitario *(1-(d.pcj_descuento / 100)))* d.cantidad *-1 END), 2)>0
          ORDER BY
            r.descripcion ASC;";
  //echo $sql;
  $resultado = GenerarArray($sql);

  if ($resultado) {
    echo json_encode(array(
      'error' => false,
      'data' => $resultado
    ));
  } else {
    echo json_encode(array(
      'error' => true,
      'mensaje' => 'Error al consultar los datos'
    ));
  }
}
