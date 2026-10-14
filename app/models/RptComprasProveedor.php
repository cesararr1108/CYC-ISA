  <?php
    session_start();
    require_once('bd.php');
    require_once('funciones.php');
    $conn = conectar();

    $fecha_inicial = $_POST["fecha_inicial"];
    $fecha_final   = $_POST["fecha_final"];
    $id_tercero    = $_POST["id_tercero"];

    switch ($_POST["op"]) {
        case 'consolidado':
            # code...
            $sql = "SELECT 
                        t.documento AS doc_ref,
                        t.fecha_hora,
                        t.fecha,
                        p.nit,
                        p.razon_comercial,
                        cp.descripcion AS condicion,
                        t.vencimiento,
                        t.tipo,
                        t.numero,
                        t.valor_total,
                        IFNULL(t.descuento,0) AS descuento,
                        IFNULL(t.iva,0) AS iva,
                        IFNULL(t.impoconsumo,0) AS impoconsumo,
                        IFNULL(t.ibua,0) AS ibua,
                        IFNULL(t.icui,0) AS icui,
                        (
                            (t.valor_total + IFNULL(t.descuento,0))
                            - IFNULL(t.iva,0)
                            - IFNULL(t.impoconsumo,0)
                            - IFNULL(t.ibua,0)
                            - IFNULL(t.icui,0)
                        ) AS subtotal,
                        IFNULL(t.retencion_fuente,0) AS ReteFte,
                        (t.valor_total - IFNULL(t.retencion_fuente,0)) AS neto,
                        t.valor_aplicado AS abono,
                        u.usuario,
                        t.id
                    FROM t_transacciones t
                    INNER JOIN t_terceros 			p  ON p.id= t.id_tercero
                    INNER JOIN t_condiciones_pago	cp ON cp.id=t.id_condicion AND cp.tipo_condicion='P'
                    INNER JOIN t_usuarios 			u  ON u.id=t.id_usuario
                    WHERE t.sw=1 AND IFNULL(t.anulado,0)=0 AND CAST(t.fecha_hora AS DATE) >= CAST('$fecha_inicial' AS DATE) AND  
                    CAST(t.fecha_hora AS DATE) <=CAST('$fecha_final' AS DATE) ";

            if ($id_tercero != '0') {
                $sql .= " and t.id_tercero = $id_tercero";
            }
            $sql .= "
            order by 
            t.id_tercero,
            t.tipo,
            t.numero
        ";

            $compras = GenerarArray($sql);

            echo  json_encode($compras);
            break;

        case "detallado":
            # code...
            $sql = "SELECT 
                            t.documento as doc_ref, 
                            t.fecha_hora,
                            p.nit,
                            p.razon_comercial,
                            cp.descripcion AS condicion,
                            t.vencimiento,
                            t.tipo,
                            t.numero,
                            t.valor_total,
                            t.descuento,
                            t.valor_aplicado as abono,
                            u.usuario,
                            r.codigo,
                            r.descripcion as producto,
                            CAST(d.cantidad/d.can_und AS DECIMAL(19,0)) as cantidad,
                            d.und,
                            d.costo_unitario * d.can_und as costo,
                            d.pcj_iva as porcentaje_iva,
                            d.pcj_descuento as porcentaje_descuento,
                            m.descripcion as marca,
                            d.lote,
                            d.vmcto as vencimiento,
                            ROUND((((d.costo_unitario * (1-(d.pcj_descuento/100)))
                                    * ((d.pcj_iva/100)+1))
                                    * d.cantidad),0) as total,
                            IFNULL(vr.precion,0) as precio_lista,
                            v.promedio as costo_promedio,
                            -- Utilidad
                            (
                                IFNULL(vr.precion,0) -
                                COALESCE(NULLIF(v.promedio,0), d.costo_unitario * d.can_und)
                            ) AS utilidad,
                            -- Rentabilidad sobre el costo
                            ROUND(
                                CASE
                                    WHEN COALESCE(NULLIF(v.promedio,0), d.costo_unitario * d.can_und) > 0 THEN
                                        (
                                            (
                                                IFNULL(vr.precion,0) -
                                                COALESCE(NULLIF(v.promedio,0), d.costo_unitario * d.can_und)
                                            )
                                            /
                                            COALESCE(NULLIF(v.promedio,0), d.costo_unitario * d.can_und)
                                        ) * 100
                                    ELSE 0
                                END,
                            2) AS rentabilidad,
                            -- Margen sobre el precio de venta
                            ROUND(
                                CASE
                                    WHEN IFNULL(vr.precion,0) > 0 THEN
                                        (
                                            (
                                                IFNULL(vr.precion,0) -
                                                COALESCE(NULLIF(v.promedio,0), d.costo_unitario * d.can_und)
                                            )
                                            / IFNULL(vr.precion,0)
                                        ) * 100
                                    ELSE 0
                                END,
                            2) AS margen
                    FROM t_transacciones t
                    INNER JOIN t_transacciones_detalle d ON d.id_transaccion = t.id
                    INNER JOIN t_referencias r ON r.id = d.id_referencia
                    LEFT JOIN t_marcas m ON m.id = r.id_marca
                    INNER JOIN t_terceros p ON p.id = t.id_tercero
                    INNER JOIN t_condiciones_pago cp ON cp.id = t.id_condicion
                        AND cp.tipo_condicion = 'P'
                    INNER JOIN t_usuarios u ON u.id = t.id_usuario
                    LEFT JOIN v_referencias vr ON vr.id = r.id
                        AND vr.lista = 1
                    LEFT JOIN v_costo_promedio v ON v.id_referencia = r.id
                        AND v.ano = YEAR(NOW())
                        AND v.mes = MONTH(NOW())
                    WHERE
                    t.sw=1 and 
                    ifnull(t.anulado,0)=0 and 
                    cast(t.fecha_hora as date) >= cast('$fecha_inicial' as date) and  
                    cast(t.fecha_hora as date) <=cast('$fecha_final' as date) ";

            if ($id_tercero != '0') {
                $sql .= " and t.id_tercero = $id_tercero";
            }
            $sql .= "
                order by 
                    t.id_tercero,
                    t.tipo,
                    t.numero,
                    r.descripcion 
        ";
            $compras = GenerarArray($sql);
            echo  json_encode($compras);
            break;
    }