  <?php
    session_start();
    require_once('bd.php');
    require_once('funciones.php');
    $conn = conectar();
    switch ($_POST['op']) {
        case 'consultar':
            $fecha_inicial = $_POST["fecha_inicial"];
            $fecha_final   = $_POST["fecha_final"];
            $id_vendedor    = $_POST["id_vendedor"];
            $id_zona        = $_POST["id_zona"];

            $sql = "SELECT
                        IFNULL(z.codigo_zona, 'S/A') as codigo_zona,
                        IFNULL(z.descripcion, 'S/A') as zona,
                        IFNULL(vp.nit,0) AS nit_vendedor,
                        IFNULL(vp.razon_comercial,'S/A') AS vendedor,
                        IFNULL(vs.nit,0) AS nit_vendedor2,
                        IFNULL(vs.razon_comercial,'S/A') AS vendedor2,
                        COUNT(DISTINCT t.id_tercero) AS impactos,
                        ROUND(SUM(
                            CASE WHEN d.sw = 5 THEN
                                d.valor_unitario * (1 - (d.pcj_descuento / 100)) * d.cantidad
                            ELSE 0 END
                        ),2) AS ventas_netas,
                        ROUND(SUM(
                            CASE WHEN d.sw = 4 THEN
                                d.valor_unitario * (1 - (d.pcj_descuento / 100)) * d.cantidad
                            ELSE 0 END
                        ),2) AS devoluciones_netas,
                        ROUND(SUM(
                            CASE WHEN d.sw = 5 THEN
                                (d.valor_unitario * (1 - (d.pcj_descuento / 100)))
                                * (1 + (d.pcj_iva / 100)) * d.cantidad
                            ELSE 0 END
                        ),2) AS ventas_iva,
                        ROUND(SUM(
                            CASE WHEN d.sw = 4 THEN
                                (d.valor_unitario * (1 - (d.pcj_descuento / 100)))
                                * (1 + (d.pcj_iva / 100)) * d.cantidad
                            ELSE 0 END
                        ),2) AS devoluciones_iva,
                        /* NETO REAL (lo que realmente queda en ventas) */
                        ROUND(
                            SUM(
                                CASE WHEN d.sw = 5 THEN
                                    d.valor_unitario * (1 - (d.pcj_descuento / 100)) * d.cantidad
                                ELSE
                                    -(d.valor_unitario * (1 - (d.pcj_descuento / 100)) * d.cantidad)
                                END
                            )
                        ,2) AS venta_neta_real,
                        /* IVA NETO (ventas - devoluciones) */
                        ROUND(
                            SUM(
                                CASE WHEN d.sw = 5 THEN
                                    (d.valor_unitario * (1 - (d.pcj_descuento / 100)))
                                    * (d.pcj_iva / 100) * d.cantidad
                                ELSE
                                    -((d.valor_unitario * (1 - (d.pcj_descuento / 100)))
                                    * (d.pcj_iva / 100) * d.cantidad)
                                END
                            )
                        ,2) AS iva_neto
                    FROM t_transacciones_detalle d
                    INNER JOIN t_transacciones t
                        ON t.id = d.id_transaccion
                    AND IFNULL(t.anulado,0)=0
                    INNER JOIN t_terceros c
                        ON c.id = t.id_tercero
                    LEFT JOIN t_terceros vp
                        ON vp.id = c.id_vendedor_principal
                    LEFT JOIN t_terceros vs
                        ON vs.id = c.id_vendedor_secundario 
                    LEFT JOIN t_zonas_ventas z 
                        ON z.id = c.id_zona_ventas 
                    WHERE
                        d.sw IN (5,4)
                        AND t.fecha BETWEEN '$fecha_inicial' AND '$fecha_final'
                        AND d.id_bodega = 1";
            if ($id_vendedor != 0) {
                $sql .= " AND (vp.id = $id_vendedor OR vs.id = $id_vendedor)";
            }
            if ($id_zona != 0) {
                $sql .= " AND z.id = $id_zona";
            }
            $sql .= "
                    GROUP BY
                        z.codigo_zona,
                        z.descripcion,
                        vp.nit,
                        vp.razon_comercial,
                        vs.nit,
                        vs.razon_comercial
                    ORDER BY 
                        vp.nit;";

            $ventas = GenerarArray($sql);

            echo  json_encode($ventas);

            break;
        case 'consultar_zonas':
            $sql = "SELECT id, codigo_zona, descripcion FROM t_zonas_ventas ORDER BY codigo_zona";
            $zonas = GenerarArray($sql);
            echo json_encode($zonas);
            break;
        case 'consultar_vendedores':
            $sql = "SELECT t.id, t.razon_comercial, t.tratamiento, t.nit
            FROM t_terceros t
            INNER JOIN t_tipos_terceros tt ON t.id_tipo_tercero = tt.id
            WHERE tt.es_vendedor = 'S' AND t.estado = 'A'
            ORDER BY t.razon_comercial ASC";
            $datos = GenerarArray($sql);
            echo json_encode(array(
                'error' => false,
                'data' => $datos
            ));
            break;
    }
