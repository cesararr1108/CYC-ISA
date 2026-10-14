  <?php
    session_start();
    require_once('bd.php');
    require_once('funciones.php');
    $conn = conectar();


    $fecha_inicial = $_POST["fecha_inicial"];
    $fecha_final   = $_POST["fecha_final"];
    $id_usuario    = $_POST["id_usuario"];
    $tipo_vista    = $_POST["tipo_vista"] ?? "consolidada"; // consolidada o detallada

    if ($tipo_vista === "detallada") {
        // VISTA DETALLADA: Línea por línea de transacción
        $sql = "SELECT
                    v.numero,
                    v.tipo,
                    v.fecha_documento,
                    v.razon_comercial as cliente,
                    v.usuario,
                    u.nombres,
                    u.apellidos,
                    v.desc_bodega,
                    v.codigo,
                    v.producto,
                    v.marca,
                    CAST(v.cantidad AS UNSIGNED) as cantidad,
                    v.und,
                    v.porcentaje_descuento,
                    v.porcentaje_iva,
                    v.precio_unitario_unidad,
                    v.precio_neto_unidad,
                    v.costo_unidad,
                    v.valor_total,
                    v.valor_iva,
                    v.valor_descuento,
                    ROUND((v.precio_neto_unidad * v.cantidad) - (v.costo_unidad * v.cantidad), 0) as utilidad,
                    ROUND((((v.precio_neto_unidad * v.cantidad) - (v.costo_unidad * v.cantidad)) / NULLIF(v.precio_neto_unidad * v.cantidad, 0)) * 100, 2) as margen
                FROM v_ventas_general v 
                INNER JOIN t_usuarios u on u.id = v.id_usuario 
                WHERE 
                 ifnull(v.anulado,0) =0 and
                 v.fecha_documento BETWEEN '$fecha_inicial' AND '$fecha_final'";
        if ($id_usuario != 0) {
            $sql .= " and v.id_usuario = $id_usuario ";
        }
        $sql .= " ORDER BY v.fecha_documento DESC, v.numero DESC, v.usuario";
    } else {
        // VISTA CONSOLIDADA: Resumen por vendedor
        $sql = "SELECT
                    v.id_bodega,
                    v.desc_bodega,
                    v.usuario,
                    u.nombres,
                    u.apellidos,
                    round(sum(v.precio_neto_unidad * v.cantidad), 0) as valor_total,
                    round(sum(v.costo_unidad * v.cantidad), 0) as costo_total,
                    ROUND(SUM(v.precio_neto_unidad * v.cantidad), 0) - ROUND(SUM(v.costo_unidad * v.cantidad), 0) AS utilidad,
                    ROUND((
                            (SUM(v.precio_neto_unidad * v.cantidad) -  SUM(v.costo_unidad * v.cantidad))
                            / NULLIF(SUM(v.precio_neto_unidad * v.cantidad), 0)
                        ) * 100,2 ) AS margen
                FROM v_ventas_general v 
                INNER JOIN t_usuarios u on u.id = v.id_usuario 
                WHERE 
                 ifnull(v.anulado,0) =0 and
                 v.fecha_documento BETWEEN '$fecha_inicial' AND '$fecha_final'";
        if ($id_usuario != 0) {
            $sql .= " and v.id_usuario = $id_usuario ";
        }
        $sql .= " GROUP BY 
                v.id_bodega,
                v.desc_bodega,
                v.usuario,
                u.nombres,
                u.apellidos
                ORDER BY valor_total DESC";
    }

    $ventas = GenerarArray($sql);

    echo  json_encode($ventas);
