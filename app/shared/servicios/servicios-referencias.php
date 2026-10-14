<?php
session_start();
require_once('../../models/bd.php');
require_once('../../models/funciones.php');
$conn = conectar();
switch ($_POST["op"]) {
    case 's_rentabilidad':

        $id_referencia = (int) $_POST['id_referencia'];
        $bodega = (int) $_POST['bodega'];
        $fecha_inicial = $_POST['fecha_inicial'];
        $fecha_final = $_POST['fecha_final'];

        // Si bodega = 0, consulta todas las bodegas
        $filtro_bodega = '';

        if ($bodega !== 0) {
            $filtro_bodega = "AND d.id_bodega = $bodega";
        }

        $sql = "SELECT
                r.id,
                r.codigo,
                r.descripcion producto,
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

            FROM t_transacciones_detalle d

            INNER JOIN t_transacciones t
                ON t.id = d.id_transaccion
                AND IFNULL(t.anulado, 0) = 0

            LEFT JOIN t_condiciones_pago c
                ON c.id = t.id_condicion

            INNER JOIN t_referencias r
                ON d.id_referencia = r.id

            WHERE d.sw IN (5, 4)
              AND CAST(d.fec AS DATE) BETWEEN '$fecha_inicial' AND '$fecha_final'
              AND d.id_referencia = $id_referencia
              $filtro_bodega

            GROUP BY
                r.id,
                r.codigo,
                r.descripcion

            HAVING
                ROUND(
                    SUM(
                        CASE 
                            WHEN d.sw = 5 
                                THEN (d.valor_unitario * (1 - (d.pcj_descuento / 100))) * d.cantidad
                            ELSE (d.valor_unitario * (1 - (d.pcj_descuento / 100))) * d.cantidad * -1
                        END
                    ), 2
                ) > 0

            ORDER BY r.descripcion ASC;";

        $resultado = GenerarArray($sql);

        echo json_encode($resultado);
        break;
    case 's_proveedores':
        $id_referencia = (int) $_POST['id_referencia'];
        $bodega = (int) $_POST['bodega'];
        $fecha_inicial = $_POST['fecha_inicial'];
        $fecha_final = $_POST['fecha_final'];

        // Si bodega = 0, consulta todas las bodegas
        $filtro_bodega = '';

        if ($bodega !== 0) {
            $filtro_bodega = "AND d.id_bodega = $bodega";
        }

        $sql = "SELECT
                nit,
                razon_comercial,
                tratamiento,
                cantidad,
                costo_unitario,
                fecha_hora
            FROM (
                SELECT
                    t.nit,
                    p.razon_comercial,
                    p.tratamiento,
                    d.cantidad,
                    d.costo_unitario,
                    d.fecha_hora,

                    ROW_NUMBER() OVER (
                        PARTITION BY t.nit
                        ORDER BY d.fecha_hora DESC
                    ) AS rn

                FROM t_transacciones_detalle d

                INNER JOIN t_transacciones t
                    ON t.id = d.id_transaccion
                    AND IFNULL(t.anulado, 0) = 0

                INNER JOIN t_terceros p
                    ON p.id = t.id_tercero

                WHERE d.id_referencia = $id_referencia
                  AND d.sw = 1
                  $filtro_bodega
                  AND t.fecha BETWEEN '$fecha_inicial' AND '$fecha_final'
            ) x

            WHERE rn = 1
            ORDER BY fecha_hora desc;";

        $resultado = GenerarArray($sql);

        echo json_encode($resultado);
        break;

    case 's_comportamiento_referencia':
        $id_referencia = $_POST['id_referencia'];
        $bodega = $_POST['bodega'];
        $fecha_inicial = $_POST['fecha_inicial'];
        $fecha_final = $_POST['fecha_final'];

        $filtro_bodega = '';

        if ((int)$bodega !== 0) {
            $filtro_bodega = "AND d.id_bodega = $bodega";
        }

        $sql = "SELECT
                IFNULL(SUM(CASE 
                    WHEN d.sw = 1 THEN d.cantidad 
                    ELSE 0 
                END),0) AS unidades_compradas,

                IFNULL(SUM(CASE 
                    WHEN d.sw = 5 THEN d.cantidad 
                    ELSE 0 
                END),0) AS unidades_vendidas,

                IFNULL(MAX(CASE 
                    WHEN d.sw = 1 THEN t.fecha 
                    ELSE '-' 
                END),'-') AS ultima_fecha_compra,

                IFNULL(MAX(CASE 
                    WHEN d.sw = 5 THEN t.fecha 
                    ELSE '-' 
                END),'-') AS ultima_fecha_venta

            FROM t_transacciones_detalle d

            INNER JOIN t_transacciones t
                ON t.id = d.id_transaccion
                AND IFNULL(t.anulado, 0) = 0

            WHERE d.id_referencia = $id_referencia
              AND d.sw IN (1, 5)
              $filtro_bodega
              AND t.fecha BETWEEN '$fecha_inicial' AND '$fecha_final';";

        $datos = GenerarArray($sql);

        // Consulta de stock
        $filtro_bodega_stock = '';

        if ((int)$bodega !== 0) {
            $filtro_bodega_stock = "AND v.id_bodega = $bodega";
        }

        $sqlStock = "SELECT 
                    IFNULL(SUM(stock), 0) AS stock
                 FROM v_referencias_stock_lotes v
                 WHERE v.id_referencia = $id_referencia
                 $filtro_bodega_stock;";

        $stock = GenerarArray($sqlStock);

        // Tomamos el único registro de cada consulta
        $resultado = isset($datos[0]) ? $datos[0] : array();

        // Agregamos el stock al mismo registro
        $resultado['stock'] = isset($stock[0]['stock']) ? $stock[0]['stock'] : 0;

        echo json_encode($resultado);
        break;
}
