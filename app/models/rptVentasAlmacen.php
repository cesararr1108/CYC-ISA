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
    case 's_ventas_bodega':
        $fhIni = $_POST["fechaInicio"];
        $fhFin = $_POST["fechaFin"];
        $bodegaSeleccionada = $_POST["bodegaSeleccionada"];
        $sql = "SELECT 
                    id_bodega,
                    descripcion,
                    venta,
                    costo,
                    vlr_iva,
                    venta_iva,
                    (venta - costo) AS utilidad
                FROM (
                    SELECT 
                        d.id_bodega,
                        a.descripcion,
                        SUM(
                            (CASE 
                                WHEN d.sw = 5 
                                THEN d.valor_unitario * (1 - (d.pcj_descuento / 100)) 
                                ELSE (d.valor_unitario * (1 - (d.pcj_descuento / 100))) * -1
                            END) * d.cantidad
                        ) AS venta,
                        SUM(
                            (CASE 
                                WHEN d.sw = 5 
                                THEN d.costo_unitario
                                ELSE d.costo_unitario * -1
                            END) * d.cantidad
                        ) AS costo,
                        SUM(
                            (
                                CASE
                                    WHEN d.sw = 5 THEN
                                        (d.valor_unitario * (1 - (d.pcj_descuento / 100))) * (1 + (d.pcj_iva / 100))
                                    ELSE
                                        ((d.valor_unitario * (1 - (d.pcj_descuento / 100))) * (1 + (d.pcj_iva / 100))) * -1
                                END
                            ) * d.cantidad
                        ) AS venta_iva,
                        SUM(
                            (
                                CASE
                                    WHEN d.sw = 5
                                    THEN (d.valor_unitario * (1 - (d.pcj_descuento / 100))) * (d.pcj_iva / 100)
                                    ELSE ((d.valor_unitario * (1 - (d.pcj_descuento / 100))) * (d.pcj_iva / 100)) * -1
                                END
                            ) * d.cantidad
                        ) AS vlr_iva
                    FROM t_transacciones_detalle d
                    INNER JOIN t_transacciones t ON t.id = d.id_transaccion and ifnull(t.anulado,0) = 0
                    INNER JOIN t_almacenes a ON a.id = d.id_bodega
                    WHERE 
                    d.sw IN (5,4) and cast(d.fec as date )BETWEEN cast('$fhIni' as date) AND cast('$fhFin' as date)";
                    if ($bodegaSeleccionada && $bodegaSeleccionada != "0") {
                        $sql .= " AND d.id_bodega = $bodegaSeleccionada";
                    }
                    $sql .= "
                    GROUP BY d.id_bodega, a.descripcion
                ) t;";
        $q = GenerarArray($sql);
        echo json_encode($q);
        break;
}
