  <?php
  session_start();
  require_once('bd.php');
  require_once('funciones.php');
  $conn = conectar();
  $id_referencia  = $_POST["id_referencia"];
  $fecha_inicial  = $_POST["fecha_inicial"];
  $fecha_final    = $_POST["fecha_final"];
  $bodega         = isset($_POST["id_bodega"]) ? $_POST["id_bodega"] : $_SESSION["Ses_id_bodega"];
/*
  $sql = "WITH saldo_inicial AS (
            SELECT 
              r.codigo,
              SUM(
                CASE 
                  WHEN d.sw IN (1,2,3,4) THEN d.cantidad 
                  WHEN d.sw IN (5,6,7) THEN -d.cantidad
                  ELSE 0
                END
              ) AS saldo_inicial
            FROM t_transacciones_detalle d
            INNER JOIN t_transacciones t on t.id = d.id_transaccion
            INNER JOIN t_referencias r ON r.id = d.id_referencia
            WHERE CAST(d.fec AS date) < '$fecha_inicial' and d.id_referencia=$id_referencia AND d.id_bodega=$bodega
            GROUP BY r.codigo
          ),
          movimientos AS (
            SELECT 
              r.codigo,
              d.fecha_hora AS fecha,
              s.descripcion AS movimiento,
              CASE 
                WHEN d.sw IN (1,2,3,4) THEN d.cantidad 
                WHEN d.sw IN (5,6,7) THEN -d.cantidad 
                ELSE 0
              END AS cantidad,
              d.sw,
              d.und ,
              d.lote ,
              d.vmcto ,
              d.pcj_iva ,
              d.pcj_descuento ,
              ((d.costo_unitario*(1-(d.pcj_descuento/100))))+(ifnull(d.ibua,0))+(  ((d.costo_unitario*(1-(d.pcj_descuento/100)))) *(ifnull(d.icui,0)/100) ) as costo,
              d.valor_unitario as precio,
              t.tipo,
              t.numero,
              x.nit,
              CASE WHEN tti.tipo_identificacion='N' then x.razon_comercial ELSE 
                CONCAT(x.primer_nombre ,x.segundo_nombre ,' ',x.primer_apellido ,' ',x.segundo_apellido )
              END as tercero,
              tu.usuario 
            FROM t_transacciones_detalle d
            INNER JOIN t_referencias r ON r.id = d.id_referencia
            INNER JOIN t_transacciones t ON t.id = d.id_transaccion
            INNER JOIN t_usuarios tu on tu.id=t.id_usuario 
            INNER JOIN t_terceros x on x.id=t.id_tercero 
            INNER JOIN t_tipo_identificaciones tti on tti.id=x.id_tipo_identificacion 
            INNER JOIN t_sw s ON s.sw = t.sw
            WHERE d.sw IN (1,2,3,4,5,6,7)
            AND CAST(d.fec AS date) BETWEEN '$fecha_inicial' AND '$fecha_final' and d.id_referencia=$id_referencia 
            AND (d.id_bodega=$bodega or $bodega = 0)
            )";

  $sql .= "SELECT 
            m.codigo,
            cast(ifnull(si.saldo_inicial,0) as decimal(19,0)) AS saldo_inicial,
            m.fecha,
            m.movimiento,
            cast(m.cantidad as decimal(19,0)) as cantidad,
            cast(SUM(m.cantidad) OVER (PARTITION BY m.codigo ORDER BY m.fecha ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW)
              + ifnull(si.saldo_inicial,0) as decimal(19,0)) AS saldo_acumulado,
              m.und ,
              m.lote ,
              m.vmcto ,
              m.pcj_iva ,
              m.pcj_descuento ,
              m.costo,
              m.precio,
              m.tipo,
              m.numero,
              m.nit,
              m.tercero ,
              m.usuario ,
              m.sw
          FROM movimientos m
          LEFT JOIN saldo_inicial si ON si.codigo = m.codigo
          ORDER BY m.codigo, m.fecha;";*/
         // echo $sql;
  $sql = "WITH saldo_inicial AS (
                SELECT 
                    r.codigo,
                    SUM(
                        CASE
                            WHEN d.sw IN (1,2,3,4) THEN d.cantidad
                            WHEN d.sw IN (5,6,7) THEN -d.cantidad
                            ELSE 0
                        END
                    ) AS saldo_inicial
                FROM t_transacciones_detalle d
                INNER JOIN t_transacciones t
                    ON t.id = d.id_transaccion
                INNER JOIN t_referencias r
                    ON r.id = d.id_referencia
                WHERE CAST(d.fec AS DATE) < '$fecha_inicial'
                  AND d.id_referencia = $id_referencia
                  AND d.id_bodega = $bodega
                GROUP BY r.codigo
            ),
            movimientos AS (
                SELECT
                    d.id AS id_detalle,
                    r.codigo,
                    d.fecha_hora AS fecha,
                    s.descripcion AS movimiento,
                    CASE
                        WHEN d.sw IN (1,2,3,4) THEN d.cantidad
                        WHEN d.sw IN (5,6,7) THEN -d.cantidad
                        ELSE 0
                    END AS cantidad,
                    d.sw,
                    d.und,
                    d.lote,
                    d.vmcto,
                    d.pcj_iva,
                    d.pcj_descuento,
                    (
                        (d.costo_unitario * (1 - (d.pcj_descuento / 100)))
                        + IFNULL(d.ibua,0)
                        + (
                            (d.costo_unitario * (1 - (d.pcj_descuento / 100)))
                            * (IFNULL(d.icui,0) / 100)
                        )
                    ) AS costo,
                    d.valor_unitario AS precio,
                    t.tipo,
                    t.numero,
                    x.nit,
                    CASE
                        WHEN tti.tipo_identificacion = 'N'
                            THEN x.razon_comercial
                        ELSE CONCAT(
                                x.primer_nombre,' ',
                                x.segundo_nombre,' ',
                                x.primer_apellido,' ',
                                x.segundo_apellido
                            )
                    END AS tercero,
                    tu.usuario
                FROM t_transacciones_detalle d
                INNER JOIN t_referencias r
                    ON r.id = d.id_referencia
                INNER JOIN t_transacciones t
                    ON t.id = d.id_transaccion
                INNER JOIN t_usuarios tu
                    ON tu.id = t.id_usuario
                INNER JOIN t_terceros x
                    ON x.id = t.id_tercero
                INNER JOIN t_tipo_identificaciones tti
                    ON tti.id = x.id_tipo_identificacion
                INNER JOIN t_sw s
                    ON s.sw = t.sw
                WHERE d.sw IN (1,2,3,4,5,6,7)
                  AND CAST(d.fec AS DATE) BETWEEN '$fecha_inicial' AND '$fecha_final'
                  AND d.id_referencia = $id_referencia
                  AND (d.id_bodega = $bodega OR $bodega = 0)
            )
            SELECT
                m.codigo,
                CAST(IFNULL(si.saldo_inicial,0) AS DECIMAL(19,0)) AS saldo_inicial,
                m.fecha,
                m.movimiento,
                CAST(m.cantidad AS DECIMAL(19,0)) AS cantidad,
                CAST(
                    IFNULL(si.saldo_inicial,0)
                    +
                    SUM(m.cantidad) OVER (
                        PARTITION BY m.codigo
                        ORDER BY
                            m.fecha,
                            m.id_detalle
                        ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
                    )
                AS DECIMAL(19,0)) AS saldo_acumulado,
                m.und,
                m.lote,
                m.vmcto,
                m.pcj_iva,
                m.pcj_descuento,
                m.costo,
                m.precio,
                m.tipo,
                m.numero,
                m.nit,
                m.tercero,
                m.usuario,
                m.sw
            FROM movimientos m
            LEFT JOIN saldo_inicial si
                ON si.codigo = m.codigo
            ORDER BY
                m.codigo,
                m.fecha,
                m.id_detalle;";
  $result = GenerarArray($sql);

  echo  json_encode($result);
