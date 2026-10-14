  <?php
    session_start();
    require_once('bd.php');
    require_once('funciones.php');
    $conn = conectar();

    $fecha_inicial = $_POST["fecha_inicial"];
    $fecha_final   = $_POST["fecha_final"];
    $id_tercero    = $_POST["id_tercero"];
    $id_marca      = intval($_POST["id_marca"] ?? 0);

    switch ($_POST["op"]) {
        case 'consolidado':
            $sql ="SELECT
                        p.nit,
                        p.razon_comercial,
                        m.descripcion AS marca,
                        ROUND(
                            SUM(
                                (
                                    d.valor_unitario *
                                    (1 - (d.pcj_descuento / 100))
                                ) * d.cantidad
                            ),
                            0
                        ) AS total_sin_iva,
                        ROUND(
                            SUM(
                                (
                                    (
                                        d.valor_unitario *
                                        (1 - (d.pcj_descuento / 100))
                                    ) *
                                    ((d.pcj_iva / 100) + 1)
                                ) * d.cantidad
                            ),
                            0
                        ) AS total_con_iva,
                        ROUND(
                            SUM(
                                (
                                    d.costo_unitario *
                                    (1 - (d.pcj_descuento / 100))
                                ) * d.cantidad
                            ),
                            0
                        ) AS total_costo_sin_iva,
                        ROUND(
                            SUM(
                                (
                                    (
                                        d.costo_unitario *
                                        (1 - (d.pcj_descuento / 100))
                                    ) *
                                    ((d.pcj_iva / 100) + 1)
                                ) * d.cantidad
                            ),
                            0
                        ) AS total_costo_con_iva
                    FROM t_transacciones t
                    INNER JOIN t_transacciones_detalle d  
                            ON d.id_transaccion = t.id
                    INNER JOIN t_referencias r  
                            ON r.id = d.id_referencia
                    LEFT JOIN t_marcas m  
                            ON m.id = r.id_marca
                    INNER JOIN t_terceros p  
                            ON p.id = t.id_tercero
                    INNER JOIN t_condiciones_pago cp 
                            ON cp.id = t.id_condicion 
                        AND cp.tipo_condicion = 'P'
                    INNER JOIN t_usuarios u  
                            ON u.id = t.id_usuario
                    WHERE 
                        t.sw = 1
                        AND IFNULL(t.anulado,0) = 0
                        AND CAST(t.fecha_hora AS DATE) 
                            BETWEEN CAST('$fecha_inicial' AS DATE) 
                                AND CAST('$fecha_final' AS DATE)";
            if ($id_tercero != '0') {
                $sql .= " AND t.id_tercero = $id_tercero";
            }
            if ($id_marca > 0) {
                $sql .= " AND m.id = $id_marca";
            }
            $sql .= " GROUP BY 
                        p.nit,
                        p.razon_comercial,
                        m.descripcion
                    ORDER BY 
                        total_con_iva DESC;";
            $compras = GenerarArray($sql);

            echo  json_encode($compras);
            break;
    }