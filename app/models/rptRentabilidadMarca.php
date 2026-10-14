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

  // Query para rentabilidad por marca (agrupado)
  $sql = "SELECT 
            COALESCE(m.id, 0) as codigo,
            COALESCE(m.descripcion, 'SIN MARCA') as marca,
            ROUND(SUM(
              CASE 
                WHEN d.sw = 5 
                THEN (d.valor_unitario * (1 - (d.pcj_descuento / 100))) * d.cantidad 
                ELSE (d.valor_unitario * (1 - (d.pcj_descuento / 100))) * d.cantidad * -1
              END
            ), 2) as venta,
            ROUND(SUM(
              CASE 
                WHEN d.sw = 5 
                THEN d.costo_unitario * d.cantidad 
                ELSE d.costo_unitario * d.cantidad * -1
              END
            ), 2) as costo,
            ROUND(SUM(
              CASE 
                WHEN d.sw = 5 
                THEN ((d.valor_unitario * (1 - (d.pcj_descuento / 100))) - d.costo_unitario) * d.cantidad 
                ELSE ((d.valor_unitario * (1 - (d.pcj_descuento / 100))) - d.costo_unitario) * d.cantidad * -1
              END
            ), 2) as utilidad,
            -- =========================
            -- MARGEN (% sobre venta)
            -- =========================
            CASE 
              WHEN SUM(
                CASE 
                  WHEN d.sw = 5 
                  THEN (d.valor_unitario * (1 - (d.pcj_descuento / 100))) * d.cantidad
                  ELSE (d.valor_unitario * (1 - (d.pcj_descuento / 100))) * d.cantidad * -1
                END
              ) = 0 THEN 0
              ELSE ROUND(
                (
                  SUM(
                    CASE 
                      WHEN d.sw = 5 
                      THEN ((d.valor_unitario * (1 - (d.pcj_descuento / 100))) - d.costo_unitario) * d.cantidad 
                      ELSE ((d.valor_unitario * (1 - (d.pcj_descuento / 100))) - d.costo_unitario) * d.cantidad * -1
                    END
                  )
                  /
                  SUM(
                    CASE 
                      WHEN d.sw = 5 
                      THEN (d.valor_unitario * (1 - (d.pcj_descuento / 100))) * d.cantidad
                      ELSE (d.valor_unitario * (1 - (d.pcj_descuento / 100))) * d.cantidad * -1
                    END
                  )
                ) * 100, 2
              )
            END as margen,
            -- =========================
            -- MARKUP (% sobre costo)
            -- =========================
            CASE 
              WHEN SUM(
                CASE 
                  WHEN d.sw = 5 
                  THEN d.costo_unitario * d.cantidad 
                  ELSE d.costo_unitario * d.cantidad * -1
                END
              ) = 0 THEN 0
              ELSE ROUND(
                (
                  SUM(
                    CASE 
                      WHEN d.sw = 5 
                      THEN ((d.valor_unitario * (1 - (d.pcj_descuento / 100))) - d.costo_unitario) * d.cantidad 
                      ELSE ((d.valor_unitario * (1 - (d.pcj_descuento / 100))) - d.costo_unitario) * d.cantidad * -1
                    END
                  )
                  /
                  SUM(
                    CASE 
                      WHEN d.sw = 5 
                      THEN d.costo_unitario * d.cantidad 
                      ELSE d.costo_unitario * d.cantidad * -1
                    END
                  )
                ) * 100, 2
              )
            END as rentabilidad
        FROM t_transacciones_detalle d
        INNER JOIN t_referencias r ON d.id_referencia = r.id
        LEFT JOIN t_marcas m ON r.id_marca = m.id
        INNER JOIN t_almacenes b ON d.id_bodega = b.id
        WHERE d.sw IN (5, 4)
          AND CAST(d.fec AS DATE) BETWEEN '$fhIni' AND '$fhFin'";

  if ($id_bodega != 0) {
    $sql .= " AND d.id_bodega = $id_bodega";
  }

  if ($id_rol != '1' && $id_rol != '4') {
    $sql .= " AND d.id_usuario = $id_usuario";
  }

  $sql .= " 
          GROUP BY 
          COALESCE(m.id, 0), 
          COALESCE(m.descripcion, 'SIN MARCA')
    ORDER BY 
          COALESCE(m.descripcion, 'SIN MARCA') ASC";

  $resultado = GenerarArray($sql);
  echo json_encode(array(
    'data' => $resultado
  ));
}
