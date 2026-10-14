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

if ($_POST["op"] == 'consultar') {

    $fhIni      = $_POST["fecha_inicio"];
    $fhFin      = $_POST["fecha_fin"];
    $id_bodega  = intval($_POST["id_bodega"]);
    $id_marca   = intval($_POST["id_marca"]);

    $filtro_bodega = "";
    $filtro_marca  = "";

    if ($id_bodega > 0) {
        $filtro_bodega = " AND d.id_bodega = {$id_bodega} ";
    }

    if ($id_marca > 0) {
        $filtro_marca = " AND COALESCE(m.id,0) = {$id_marca} ";
    }

    $filtro_bodega_total = str_replace("d.", "d2.", $filtro_bodega);
    $filtro_marca_total  = str_replace("m.", "m2.", $filtro_marca);

    $sql = "SELECT 
        x.codigo,
        x.marca,
        ROUND(SUM(x.unidades),2) AS unidades,
        ROUND(SUM(x.venta_bruta),2) AS venta_bruta,
        ROUND(SUM(x.devoluciones),2) AS devoluciones,
        ROUND(SUM(x.venta_neta),2) AS venta_neta,
        ROUND(SUM(x.costo),2) AS costo,
        ROUND(SUM(x.utilidad),2) AS utilidad,
        ROUND(AVG(x.pcj_descuento),2) AS promedio_descuento,

        CASE
            WHEN SUM(x.venta_neta) = 0 THEN 0
            ELSE ROUND((SUM(x.utilidad) / SUM(x.venta_neta)) * 100,2)
        END AS margen,

        CASE
            WHEN SUM(x.costo) = 0 THEN 0
            ELSE ROUND((SUM(x.utilidad) / SUM(x.costo)) * 100,2)
        END AS markup,

        ROUND(
            (
                SUM(x.venta_neta) /
                (
                    SELECT 
                        SUM(
                            CASE
                                WHEN d2.sw = 5 
                                THEN ((d2.valor_unitario * (1 - (d2.pcj_descuento / 100))) * d2.cantidad)

                                ELSE ((d2.valor_unitario * (1 - (d2.pcj_descuento / 100))) * d2.cantidad) * -1
                            END
                        )
                    FROM t_transacciones_detalle d2

                    INNER JOIN t_transacciones t2
                        ON t2.id = d2.id_transaccion
                       AND IFNULL(t2.anulado,0) = 0

                    INNER JOIN t_referencias r2
                        ON r2.id = d2.id_referencia

                    LEFT JOIN t_marcas m2
                        ON m2.id = r2.id_marca {$filtro_marca_total}

                    WHERE d2.sw IN (4,5)
                      AND CAST(d2.fec AS DATE)
                          BETWEEN '{$fhIni}' AND '{$fhFin}'
                      {$filtro_bodega_total}
                    
                )
            ) * 100,2
        ) AS participacion

    FROM
    (
        SELECT
            COALESCE(m.id,0) AS codigo,
            COALESCE(m.descripcion,'SIN MARCA') AS marca,
            d.pcj_descuento,

            CASE
                WHEN d.sw = 5 THEN d.cantidad
                ELSE d.cantidad * -1
            END AS unidades,

            CASE
                WHEN d.sw = 5
                THEN ((d.valor_unitario * (1 - (d.pcj_descuento / 100))) * d.cantidad)
                ELSE 0
            END AS venta_bruta,

            CASE
                WHEN d.sw = 4
                THEN ((d.valor_unitario * (1 - (d.pcj_descuento / 100))) * d.cantidad)
                ELSE 0
            END AS devoluciones,

            CASE
                WHEN d.sw = 5
                THEN ((d.valor_unitario * (1 - (d.pcj_descuento / 100))) * d.cantidad)

                ELSE ((d.valor_unitario * (1 - (d.pcj_descuento / 100))) * d.cantidad) * -1
            END AS venta_neta,

            CASE
                WHEN d.sw = 5
                THEN (d.costo_unitario * d.cantidad)

                ELSE (d.costo_unitario * d.cantidad) * -1
            END AS costo,

            CASE
                WHEN d.sw = 5
                THEN (
                    (
                        (d.valor_unitario * (1 - (d.pcj_descuento / 100)))
                        - d.costo_unitario
                    ) * d.cantidad
                )

                ELSE (
                    (
                        (d.valor_unitario * (1 - (d.pcj_descuento / 100)))
                        - d.costo_unitario
                    ) * d.cantidad
                ) * -1
            END AS utilidad

        FROM t_transacciones_detalle d

        INNER JOIN t_transacciones t
            ON t.id = d.id_transaccion
           AND IFNULL(t.anulado,0) = 0

        INNER JOIN t_referencias r
            ON r.id = d.id_referencia

        LEFT JOIN t_marcas m
            ON m.id = r.id_marca {$filtro_marca}

        INNER JOIN t_almacenes b
            ON b.id = d.id_bodega

        WHERE d.sw IN (4,5)
          AND CAST(d.fec AS DATE)
              BETWEEN '{$fhIni}' AND '{$fhFin}'
          {$filtro_bodega}
         

    ) x

    GROUP BY x.codigo,x.marca
    ORDER BY venta_neta DESC
    ";

    $resultado = GenerarArray($sql);

    echo json_encode(array(
        'data' => $resultado
    ));
}
?>