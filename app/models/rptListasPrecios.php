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
    case 's_lista_precios':
        $sql = "SELECT
                    t.id,
                    t.codigo,
                    t.descripcion,
                    IFNULL(t.descripcion_larga,'') AS descripcion_larga,
                    t.tipo,
                    t.porcentaje_iva,
                    IFNULL(t.promedio, 0) AS costo_promedio,
                    IFNULL(t.costo_unitario, 0) AS costo_unitario,
                    IFNULL(t.precion, 0) AS precion,
                    t.tipo_lista,
                    IFNULL(s.stock, 0) AS total_stock,
                    (IFNULL(t.precion,0) - IFNULL(t.costo_unitario,0)) AS rentabilidad_valor,
                    ROUND(CASE 
                                WHEN t.costo_unitario = 0 THEN 0
                                ELSE ((t.precion - t.costo_unitario) / t.costo_unitario) * 100
                            END,2) AS rentabilidad_porcentaje
                FROM v_referencias t
                LEFT JOIN v_stock_total s
                    ON s.id_referencia = t.id
                    AND s.mes = MONTH(CURRENT_DATE())
                    AND s.ano = YEAR(CURRENT_DATE())
                WHERE 1=1 ";

        // Filtro por lista
        if (!empty($_POST['lista'])) {
            $lista = intval($_POST['lista']);
            $sql .= " AND t.lista = $lista ";
        }

        // Filtro por bodega
        if (!empty($_POST['bodega']) && $_POST['bodega'] != '0') {
            $bodega = intval($_POST['bodega']);
            $sql .= " AND s.id_bodega = $bodega ";
        }

        // Filtro por marca
        if (!empty($_POST['marca']) && $_POST['marca'] != '0') {
            $marca = intval($_POST['marca']);
            $sql .= " AND t.id_marca = $marca ";
        }

        // Filtro por descripcion
        if (!empty($_POST['descripcion'])) {
            $desc = addslashes($_POST['descripcion']);
            $sql .= " AND (t.descripcion LIKE '%$desc%' OR t.descripcion_larga LIKE '%$desc%') ";
        }

        $sql .= " ORDER BY t.descripcion ASC ";

        $q = GenerarArray($sql);
        echo json_encode($q);
        break;
}
