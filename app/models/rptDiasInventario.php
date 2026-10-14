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
    $dias_rot = intval($_POST['dias_rot']) * 30;
    $dias_inv = intval($_POST['dias_inv']);
    $id_bodega = intval($_POST["id_bodega"]);
    $id_usuario = $_SESSION["Ses_id"];

    // Filtro dinámico de bodega (si es mayor a 0 filtra por la bodega específica, si es 0 trae todas)
    $filtro_bodega = "";
    if ($id_bodega > 0) {
        $filtro_bodega = " AND s.id_bodega = $id_bodega";
    }

    $sql = "WITH BaseCalculations AS (
                SELECT 
                    a.descripcion AS bodega,
                    r.codigo,
                    r.descripcion AS producto,
                    IFNULL(m.descripcion,'SIN ASIGNAR') AS marca,
                    s.stock,
                    -- Si no hay transacciones, la rotación y el stock mínimo serán 0
                    ROUND(IFNULL(SUM(d.cantidad), 0) / $dias_rot, 2) AS rotacion_diaria_prom,
                    (ROUND(IFNULL(SUM(d.cantidad), 0) / $dias_rot, 0)) * $dias_inv AS stock_min,
                    -- Usamos MAX para evitar duplicidad de filas si el producto tuvo varios precios en el periodo
                    MAX(IFNULL(d.valor_unitario, 0)) AS precio_prom 
                FROM v_stock_total s
                INNER JOIN t_referencias r ON r.id = s.id_referencia
                INNER JOIN t_almacenes a ON a.id = s.id_bodega
                LEFT JOIN t_marcas m ON m.id = r.id_marca 
                -- Hacemos LEFT JOIN para incluir productos que NO tienen transacciones en el periodo
                LEFT JOIN t_transacciones_detalle d ON d.id_referencia = s.id_referencia 
                    AND d.id_bodega = s.id_bodega 
                    AND d.fecha_hora >= DATE_SUB(NOW(), INTERVAL $dias_rot DAY) 
                    AND d.sw = 5
                WHERE s.ano = YEAR(NOW()) 
                  AND s.mes = MONTH(NOW())
                  $filtro_bodega
                GROUP BY a.descripcion, r.codigo, r.descripcion, m.descripcion, s.stock
            )
            SELECT 
                bodega,
                codigo,
                producto,
                marca,
                rotacion_diaria_prom,
                stock_min,
                stock AS stock_actual, 
                -- 1. StockDias en el inventario
                CASE 
                    WHEN rotacion_diaria_prom > 0 THEN ROUND(stock / rotacion_diaria_prom, 2)
                    ELSE 9999 -- Representa infinito o sin rotación
                END AS stock_dias_inventario,  
                -- 2. Días restantes en el inventario
                CASE 
                    WHEN rotacion_diaria_prom > 0 THEN ROUND((stock - stock_min) / rotacion_diaria_prom, 2)
                    ELSE 9999 
                END AS dias_restantes_inventario,    
                -- 3. Comprar (Si no hay rotación y el stock es 0, sugerirá comprar el stock mínimo si aplica)
                CASE 
                    WHEN stock < stock_min THEN stock_min - stock 
                    ELSE 0 
                END AS comprar,    
                -- 4. Exceso
                CASE 
                    WHEN stock > (stock_min * 2) THEN stock - (stock_min * 2) 
                    ELSE 0 
                END AS exceso,    
                precio_prom,   
                -- 5. Sub total exceso
                (CASE WHEN stock > (stock_min * 2) THEN stock - (stock_min * 2) ELSE 0 END) * precio_prom AS sub_total_exceso,
                -- 6. Estado del inventario (Añadimos 'Sin Rotación' explícitamente)
                CASE 
                    WHEN rotacion_diaria_prom = 0 AND stock > 0 THEN 'Sin Rotación'
                    WHEN stock < stock_min THEN 'Comprar'
                    WHEN stock > (stock_min * 2) THEN 'Exceso'
                    ELSE 'Normal'
                END AS estado
            FROM BaseCalculations;";

    $resultado = GenerarArray($sql);

    if ($resultado) {
        echo json_encode(array(
            'error' => false,
            'data' => $resultado
        ));
    } else {
        echo json_encode(array(
            'error' => true,
            'mensaje' => 'Error al consultar los datos o sin registros'
        ));
    }
}
