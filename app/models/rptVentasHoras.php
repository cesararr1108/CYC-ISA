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
                    DATE_FORMAT(d.fecha_hora, '%h:00 %p') AS hora,
                    SUM(
                        (d.valor_unitario * (1 - (d.pcj_descuento / 100))) * d.cantidad
                    ) AS total_venta
                FROM t_transacciones_detalle d
                WHERE 
                    d.sw IN (5,4)
                    AND cast(d.fec as date) BETWEEN cast('$fhIni' as date) AND cast('$fhFin' as date) ";
                    if ($bodegaSeleccionada && $bodegaSeleccionada != "0") {
                        $sql .= " AND d.id_bodega = $bodegaSeleccionada";
                    }
        $sql .= "
                GROUP BY 
                    HOUR(d.fecha_hora),
                    DATE_FORMAT(d.fecha_hora, '%h:00 %p')
                ORDER BY 
                    HOUR(d.fecha_hora);";
        $q = GenerarArray($sql);
        echo json_encode($q);
        break;
}
