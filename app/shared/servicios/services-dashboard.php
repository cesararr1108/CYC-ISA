<?php
session_start();
require_once('../../models/bd.php');
require_once('../../models/funciones.php');
$conn = conectar();

/* ---------------------------------------------------------------
 * Período solicitado (año / mes). Se valida como entero y se acota;
 * por defecto, el mes actual (hora de Colombia). No se permiten meses futuros.
 * --------------------------------------------------------------- */
$ahoraDash  = new DateTime('now', new DateTimeZone('America/Bogota'));
$anioHoy    = (int) $ahoraDash->format('Y');
$mesHoy     = (int) $ahoraDash->format('n');
$anio = filter_var($_POST['anio'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 2000, 'max_range' => $anioHoy]]) ?: $anioHoy;
$mes  = filter_var($_POST['mes']  ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1,    'max_range' => 12]])      ?: $mesHoy;
if ($anio === $anioHoy && $mes > $mesHoy) {
    $mes = $mesHoy;
}
$desde      = sprintf('%04d-%02d-01', $anio, $mes);                      // primer día del mes
$hasta      = date('Y-m-d', strtotime($desde . ' +1 month'));           // primer día del mes siguiente
$anioDesde  = sprintf('%04d-01-01', $anio);                              // año completo
$anioHasta  = sprintf('%04d-01-01', $anio + 1);
switch ($_POST["op"]) {
    case 'estado-servicio':
        $connCore = conectarCore();
        $empresa = $_SESSION['Ses_empresa'];
        $sql = "SELECT 
                fecha_vencimiento, dias_gracia, estado_servicio
               FROM t_empresas
               WHERE db_name = '$empresa' AND activo = 1";
        $data = GenerarArray($sql, $connCore);
        echo  json_encode($data);
        break;
    case 'ventas':
        $rol        = $_SESSION["Ses_roles_id"];
        $id_usuario = $_SESSION["Ses_id"];
        $bodega     = $_SESSION["Ses_id_bodega"];
        $sql = "SELECT 
                    IFNULL(ROUND(SUM((d.valor_unitario * (1 - (d.pcj_descuento / 100))) * (1 + (d.pcj_iva / 100))* d.cantidad ),2),0) AS venta_iva,
                    IFNULL(ROUND(SUM(  (d.valor_unitario * (1 - (d.pcj_descuento / 100))) * d.cantidad ), 2), 0) AS venta_neta
                FROM t_transacciones_detalle d
                INNER JOIN t_transacciones t ON t.id = d.id_transaccion AND ifnull(t.anulado,0) = 0
                WHERE
                    d.sw IN (5)  AND 
                    fec >= '$desde' AND fec < '$hasta' AND 
                    d.id_bodega = $bodega
        ";

        if ($rol != 1 && $rol != 4) {
            $sql .= " and t.id_usuario=$id_usuario ";
        }
        $ventas = GenerarArray($sql);
        echo  json_encode($ventas);
        break;
    case 'ventas-mensuales':
        $rol        = $_SESSION["Ses_roles_id"];
        $id_usuario = $_SESSION["Ses_id"];
        $bodega     = $_SESSION["Ses_id_bodega"];
        $sql = "  SELECT 
                    ROUND(SUM(
                        (CASE 
                                WHEN d.sw = 5 
                                THEN d.valor_unitario * (1 - (d.pcj_descuento / 100)) 
                                ELSE (d.valor_unitario * (1 - (d.pcj_descuento / 100))) * -1
                            END) * d.cantidad
                    ), 2) AS venta,
                    ROUND(SUM(
                        (CASE 
                            WHEN d.sw = 5 THEN
                                (d.valor_unitario * (1 - (d.pcj_descuento / 100))) * (1 + (d.pcj_iva / 100)) * d.cantidad
                            ELSE
                                ((d.valor_unitario * (1 - (d.pcj_descuento / 100))) * (1 + (d.pcj_iva / 100)) * d.cantidad) * -1
                        END)
                    ), 2) AS venta_iva,
                    month(d.fec) as mes
                    FROM t_transacciones_detalle d
                    INNER JOIN t_transacciones t ON t.id = d.id_transaccion AND ifnull(t.anulado,0) = 0
                    WHERE d.sw IN (5,4)  AND  fec >= '$anioDesde' AND fec < '$anioHasta'
                    AND d.id_bodega = $bodega
        ";

        if ($rol != 1 && $rol != 4) {
            $sql .= " and t.id_usuario=$id_usuario ";
        }
        $sql .= " GROUP BY 
                    month(d.fec)";
        $ventas = GenerarArray($sql);
        echo  json_encode($ventas);
        break;
    case 'ventas-diarias':
        // Venta neta (ventas - devoluciones) por día del mes seleccionado
        $rol        = $_SESSION["Ses_roles_id"];
        $id_usuario = $_SESSION["Ses_id"];
        $bodega     = $_SESSION["Ses_id_bodega"];
        $sql = "  SELECT 
                    ROUND(SUM(
                        (CASE 
                                WHEN d.sw = 5 
                                THEN d.valor_unitario * (1 - (d.pcj_descuento / 100)) 
                                ELSE (d.valor_unitario * (1 - (d.pcj_descuento / 100))) * -1
                            END) * d.cantidad
                    ), 2) AS venta,
                    ROUND(SUM(
                        (CASE 
                            WHEN d.sw = 5 THEN
                                (d.valor_unitario * (1 - (d.pcj_descuento / 100))) * (1 + (d.pcj_iva / 100)) * d.cantidad
                            ELSE
                                ((d.valor_unitario * (1 - (d.pcj_descuento / 100))) * (1 + (d.pcj_iva / 100)) * d.cantidad) * -1
                        END)
                    ), 2) AS venta_iva,
                    DAY(d.fec) AS dia
                    FROM t_transacciones_detalle d
                    INNER JOIN t_transacciones t ON t.id = d.id_transaccion AND ifnull(t.anulado,0) = 0
                    WHERE d.sw IN (5,4) AND fec >= '$desde' AND fec < '$hasta'
                    AND d.id_bodega = $bodega
        ";

        if ($rol != 1 && $rol != 4) {
            $sql .= " and t.id_usuario=$id_usuario ";
        }
        $sql .= " GROUP BY DAY(d.fec) ORDER BY DAY(d.fec)";
        $ventas = GenerarArray($sql);
        echo  json_encode($ventas);
        break;
    case 'ingresos':
        $rol        = $_SESSION["Ses_roles_id"];
        $id_usuario = $_SESSION["Ses_id"];
        $bodega     = $_SESSION["Ses_id_bodega"];
        $sql = "  SELECT 
                    ifnull(sum(valor_total),0) as ingresos 
                from t_transacciones  t
                inner join t_usuarios u on u.id=t.id_usuario
                where  
                    fecha >= '$desde' AND fecha < '$hasta' AND 
                    IFNULL(t.anulado,0) = 0 AND
                    sw = 10
                and 
                t.id_bodega = $bodega
            ";

        if ($rol != 1 && $rol != 4) {
            $sql .= " and t.id_usuario=$id_usuario ";
        }
        $ingresos = GenerarArray($sql);
        echo  json_encode($ingresos);
        break;
    case 'egresos':
        $rol        = $_SESSION["Ses_roles_id"];
        $id_usuario = $_SESSION["Ses_id"];
        $bodega     = $_SESSION["Ses_id_bodega"];
        $sql = "  SELECT
                    ifnull(sum(valor_total),0) as egresos 
                from t_transacciones  t
                inner join t_usuarios u on u.id=t.id_usuario
                where  
                fecha >= '$desde' AND fecha < '$hasta' AND 
                IFNULL(t.anulado,0) = 0 AND
                sw = 9
                AND t.id_bodega=$bodega
        ";

        if ($rol != 1 && $rol != 4) {
            $sql .= " and t.id_usuario=$id_usuario ";
        }
        $egresos = GenerarArray($sql);
        echo  json_encode($egresos);
        break;
    case 'devoluciones':
        $rol        = $_SESSION["Ses_roles_id"];
        $id_usuario = $_SESSION["Ses_id"];
        $bodega     = $_SESSION["Ses_id_bodega"];
        $sql = " SELECT 
                    IFNULL(ROUND(SUM((d.valor_unitario * (1 - (d.pcj_descuento / 100))) * (1 + (d.pcj_iva / 100))* d.cantidad ),2),0) AS devoluciones_iva,
                    IFNULL(ROUND(SUM((d.valor_unitario * (1 - (d.pcj_descuento / 100))) * d.cantidad ), 2), 0) AS devoluciones
                FROM t_transacciones_detalle d
                INNER JOIN t_transacciones t ON t.id = d.id_transaccion AND ifnull(t.anulado,0) = 0
                WHERE
                 d.sw IN (4)  AND 
                 fec >= '$desde' AND fec < '$hasta' AND 
                 d.id_bodega = $bodega
            ";

        if ($rol != 1 && $rol != 4) {
            $sql .= " and t.id_usuario=$id_usuario ";
        }
        $devoluciones = GenerarArray($sql);
        echo  json_encode($devoluciones);
        break;
    case 'utilidad':
        $rol        = $_SESSION["Ses_roles_id"];
        $id_usuario = $_SESSION["Ses_id"];
        $bodega     = $_SESSION["Ses_id_bodega"];
        $sql = " SELECT 
                    ROUND(SUM(
                        CASE
                            WHEN d.sw = 5 THEN
                                d.valor_unitario * (1 - (d.pcj_descuento / 100)) * d.cantidad
                            ELSE
                                d.valor_unitario * (1 - (d.pcj_descuento / 100)) * d.cantidad * -1
                        END
                    ), 2) AS venta,
                    ROUND(SUM(
                        CASE
                            WHEN d.sw = 5 THEN
                                d.costo_unitario * d.cantidad
                            ELSE
                                d.costo_unitario * d.cantidad * -1
                        END
                    ), 2) AS costo
                FROM t_transacciones_detalle d
                INNER JOIN t_transacciones t ON t.id = d.id_transaccion AND ifnull(t.anulado,0) = 0
                WHERE 
                d.sw IN (5,4)  AND 
                fec >= '$desde' AND fec < '$hasta' AND 
                d.id_bodega = $bodega
        ";

        if ($rol != 1 && $rol != 4) {
            $sql .= " and d.id_usuario=$id_usuario ";
        }
        $devoluciones = GenerarArray($sql);
        echo  json_encode($devoluciones);
        break;
    case 'venta-semestral':
        $rol        = $_SESSION["Ses_roles_id"];
        $id_usuario = $_SESSION["Ses_id"];
        $bodega     = $_SESSION["Ses_id_bodega"];
        $sql = "SELECT 
                    IFNULL(sum(valor_total),0) as venta
                FROM t_transacciones  t
                INNER JOIN t_usuarios u ON u.id=t.id_usuario
                WHERE  
                    fecha >= '$desde' AND fecha < '$hasta' AND 
                    sw = 4
                AND t.id_bodega=$bodega
        ";

        if ($rol != 1 && $rol != 4) {
            $sql .= " AND t.id_usuario=$id_usuario ";
        }
        $devoluciones = GenerarArray($sql);
        echo  json_encode($devoluciones);
        break;
    /* ---------------------------------------------------------------
     * Lotes vencidos o próximos a vencer con stock en la bodega.
     * dias_restantes < 0 -> vencido hace N días.
     * Los días se calculan en SQL para no depender de la zona horaria del navegador.
     * --------------------------------------------------------------- */
    case "productos-vencidos":
        $bodega = (int) $_SESSION["Ses_id_bodega"];
        $mesesVencer = 6; // AJUSTAR: horizonte de alerta hacia adelante
        /*
         * Mismo cálculo de stock por lote que v_referencias_stock_lotes, pero filtrando
         * PRIMERO los lotes por vencimiento y bodega. La vista agrupa todo el movimiento
         * de todas las bodegas antes de filtrar; así solo se suman los lotes que interesan
         * (usa idx_transacciones_lote_ref_bodega y el índice de vencimiento de los lotes).
         */
        $sql = "SELECT
                    r.codigo,
                    r.descripcion,
                    IFNULL(m.descripcion, '') AS laboratorio,
                    s.lote,
                    DATE(s.vencimiento) AS vencimiento,
                    DATEDIFF(s.vencimiento, CURDATE()) AS dias_restantes,
                    s.stock,
                    IFNULL(v.promedio, 0) AS costo,
                    s.stock * IFNULL(v.promedio, 0) AS costo_total
                FROM (
                    SELECT
                        l.id_referencia,
                        tl.lote,
                        tl.vencimiento,
                        SUM(CASE
                                WHEN l.sw IN (1, 2, 4) THEN l.cantidad
                                WHEN l.sw IN (5, 6, 7) THEN l.cantidad * -1
                                ELSE 0
                            END) - IFNULL(MAX(res.reservado), 0) AS stock
                    FROM t_referencias_lotes tl
                    INNER JOIN t_transacciones_detalle l
                        ON l.id_lote = tl.id
                        AND l.id_bodega = $bodega
                    LEFT JOIN (
                        SELECT id_referencia, id_lote, SUM(cantidad) AS reservado
                        FROM t_reservas_inventario
                        WHERE IFNULL(estado, 0) = 1 AND id_bodega = $bodega
                        GROUP BY id_referencia, id_lote
                    ) res ON res.id_lote = tl.id AND res.id_referencia = l.id_referencia
                    WHERE
                        tl.vencimiento IS NOT NULL AND
                        tl.vencimiento <= DATE_ADD(CURRENT_DATE, INTERVAL $mesesVencer MONTH)
                    GROUP BY l.id_referencia, tl.id
                    HAVING stock > 0
                ) s
                INNER JOIN t_referencias r
                    ON r.id = s.id_referencia
                    AND r.tipo = 'P'
                LEFT JOIN t_marcas m
                    ON m.id = r.id_marca
                /* Costo promedio del mes actual: mismo cálculo de v_costo_promedio, pero
                   solo sobre el mes en curso (la vista agrupa todos los meses y años). */
                LEFT JOIN (
                    SELECT
                        m.id_referencia,
                        CASE
                            WHEN SUM(IFNULL(m.cant_ini, 0) + IFNULL(m.cant_ent, 0) - IFNULL(m.cant_sal, 0)) > 0
                            THEN SUM(IFNULL(m.costo_ini, 0) + IFNULL(m.costo_ent, 0) - IFNULL(m.costo_sal, 0))
                                 / SUM(IFNULL(m.cant_ini, 0) + IFNULL(m.cant_ent, 0) - IFNULL(m.cant_sal, 0))
                            ELSE 0
                        END AS promedio
                    FROM t_referencias_mov m
                    WHERE m.ano = YEAR(CURRENT_DATE) AND m.mes = MONTH(CURRENT_DATE)
                    GROUP BY m.id_referencia
                ) v ON v.id_referencia = r.id
                ORDER BY
                    s.vencimiento, costo_total DESC
                LIMIT 1000";
        $proximos = GenerarArray($sql);
        echo  json_encode($proximos);
        break;
    /* ---------------------------------------------------------------
     * Facturas vencidas y próximas a vencer (saldo pendiente).
     * dias_transcurridos > 0  -> vencida hace N días
     * dias_transcurridos <= 0 -> vence en N días (dentro de DIAS_PROXIMAS_VENCER)
     * Los días se calculan en SQL para no depender de la zona horaria del navegador.
     * --------------------------------------------------------------- */
    case "facturas-vencidas":
        $bodega = (int) $_SESSION["Ses_id_bodega"];
        $diasProximas = 7; // AJUSTAR: días hacia adelante que se consideran "próximas a vencer"
        $sql = "SELECT
                    d.sw,
                    d.numero AS numero,
                    d.tipo AS tipo,
                    t.nit,
                    d.valor_total AS valor_total,
                    (d.valor_total - IFNULL(d.valor_aplicado, 0)) AS saldo,
                    t.razon_comercial AS razon_comercial,
                    DATE(d.vencimiento) AS vencimiento,
                    DATEDIFF(CURDATE(), d.vencimiento) AS dias_transcurridos,
                    IF(DATE(d.vencimiento) < CURDATE(), 'vencida', 'proxima') AS estado,
                    DATEDIFF(CURDATE(), d.fecha_hora) AS dias
                FROM t_transacciones d
                JOIN t_terceros t ON t.id = d.id_tercero
                WHERE
                    IFNULL(d.valor_aplicado, 0) <> d.valor_total AND
                    IFNULL(d.anulado, 0) = 0 AND
                    d.sw = 1 AND
                    d.id_bodega = $bodega AND
                    d.vencimiento IS NOT NULL AND
                    d.vencimiento < DATE_ADD(CURDATE(), INTERVAL $diasProximas + 1 DAY)
                ORDER BY
                    d.vencimiento
                LIMIT 500";
        $facturas = GenerarArray($sql);
        echo  json_encode($facturas);
        break;
    case "referencias-stock-minimo":
        $bodega     = $_SESSION["Ses_id_bodega"];
        $sql = " SELECT
                        r.codigo ,
                        r.descripcion ,
                        IFNULL(mar.descripcion, '') AS marca,
                        IFNULL(s.stock,0) AS stock ,
                        r.stock_minimo 
                    FROM t_referencias r 
                    LEFT JOIN t_marcas mar ON mar.id = r.id_marca
                    LEFT JOIN (
                        SELECT 
                            m.id_referencia,
                            (SUM((m.cant_ini + m.cant_ent) - m.cant_sal) - IFNULL(res.cantidad, 0)) AS stock
                        FROM t_referencias_mov m
                        LEFT JOIN (
                            SELECT id_referencia, id_bodega, SUM(cantidad) AS cantidad
                            FROM t_reservas_inventario
                            WHERE estado = 1 AND id_bodega = $bodega
                            GROUP BY id_referencia, id_bodega
                        ) res ON res.id_referencia = m.id_referencia AND res.id_bodega = m.id_bodega
                        WHERE m.id_bodega = $bodega 
                        AND m.mes = MONTH(CURRENT_DATE()) 
                        AND m.ano = YEAR(CURRENT_DATE())
                        GROUP BY m.id_referencia
                    ) s ON s.id_referencia = r.id
                  /*  LEFT JOIN v_stock_total s ON r.id = s.id_referencia AND 
                                                s.ano = YEAR(NOW()) AND 
                                                s.mes  = MONTH(NOW()) AND 
                                                s.id_bodega  = $bodega*/
                    WHERE
                        IFNULL(r.stock_minimo,0)  > 0 AND 
                        (IFNULL(s.stock,0) - r.stock_minimo) < 0 AND  IFNULL(r.activo,0) = 1  ";
        $stockMinimo = GenerarArray($sql);
        echo  json_encode($stockMinimo);
        break;
    case "promociones-vigentes":
        $bodega  = $_SESSION["Ses_id_bodega"];
        $sql = "SELECT 
                    p.id,
                    UPPER(p.nombre) AS nombre,
                    IFNULL(p.id_lista_precios,0) AS id_lista_precios,
                    IFNULL(l.Descripcion,'Todas') AS desc_lista,
                    p.porcentaje_descuento,
                    p.tipo_aplicacion,
                    p.fecha_inicio,
                    p.fecha_final,
                    DATEDIFF(DATE(p.fecha_final), CURDATE()) AS dias_restantes,
                    p.estado,
                    p.observaciones,
                    p.fecha_creacion,
                    IFNULL(alm.descripcion, 'TODAS LAS BODEGAS') AS bodega_desc,
                    u.nombres AS usuario_nombre,
                    COUNT(DISTINCT pr.id) AS cantidad_referencias,
                    COUNT(DISTINCT pm.id) AS cantidad_marcas
                FROM t_promociones p
                LEFT JOIN t_almacenes alm ON alm.id = p.id_bodega
                LEFT JOIN t_usuarios u ON u.id = p.usuario_creacion
                LEFT JOIN t_promociones_referencias pr ON pr.id_promocion = p.id
                LEFT JOIN t_promociones_marcas pm ON pm.id_promocion = p.id
                LEFT JOIN t_listas_precios l ON l.id = p.id_lista_precios
                WHERE 
                    IFNULL(p.estado, 0) = 1
                    AND cast(CURDATE() as date) BETWEEN cast(p.fecha_inicio as date) AND cast(p.fecha_final as date)
                    AND (p.id_bodega = 0 OR p.id_bodega = $bodega)
                GROUP BY p.id
                ORDER BY p.fecha_creacion DESC;";
        $promociones = GenerarArray($sql);
        echo  json_encode($promociones);
        break;

    /* ---------------------------------------------------------------
     * Detalle por documento de las tarjetas del período:
     * ventas (sw 5) y devoluciones (sw 4) con IVA, ingresos (sw 10) y egresos (sw 9).
     * Aplica los mismos filtros que la tarjeta (bodega, mes y usuario según rol).
     * --------------------------------------------------------------- */
    case 'detalle-movimientos':
        $rol        = $_SESSION["Ses_roles_id"];
        $id_usuario = (int) $_SESSION["Ses_id"];
        $bodega     = (int) $_SESSION["Ses_id_bodega"];
        $concepto   = $_POST['concepto'] ?? '';
        $filtroUsuario = ($rol != 1 && $rol != 4) ? " AND t.id_usuario = $id_usuario " : "";
        $limite = 5000;

        if ($concepto === 'ventas' || $concepto === 'devoluciones') {
            $sw = $concepto === 'ventas' ? 5 : 4;
            $sql = "SELECT
                        t.id,
                        t.tipo,
                        t.numero,
                        DATE(MIN(d.fec)) AS fecha,
                        IFNULL(ter.razon_comercial, '') AS tercero,
                        IFNULL(ter.nit, '') AS nit,
                        IFNULL(u.usuario, '') AS usuario,
                        ROUND(SUM((d.valor_unitario * (1 - (d.pcj_descuento / 100))) * d.cantidad), 2) AS valor_neto,
                        ROUND(SUM((d.valor_unitario * (1 - (d.pcj_descuento / 100))) * (1 + (d.pcj_iva / 100)) * d.cantidad), 2) AS valor
                    FROM t_transacciones_detalle d
                    INNER JOIN t_transacciones t ON t.id = d.id_transaccion AND IFNULL(t.anulado, 0) = 0
                    LEFT JOIN t_terceros ter ON ter.id = t.id_tercero
                    LEFT JOIN t_usuarios u ON u.id = t.id_usuario
                    WHERE
                        d.sw = $sw AND
                        d.fec >= '$desde' AND d.fec < '$hasta' AND
                        d.id_bodega = $bodega
                        $filtroUsuario
                    GROUP BY t.id
                    ORDER BY fecha DESC, t.numero DESC
                    LIMIT $limite";
        } elseif ($concepto === 'ingresos' || $concepto === 'egresos') {
            $sw = $concepto === 'ingresos' ? 10 : 9;
            $sql = "SELECT
                        t.id,
                        t.tipo,
                        t.numero,
                        DATE(t.fecha) AS fecha,
                        IFNULL(ter.razon_comercial, '') AS tercero,
                        IFNULL(ter.nit, '') AS nit,
                        IFNULL(u.usuario, '') AS usuario,
                        t.valor_total AS valor
                    FROM t_transacciones t
                    INNER JOIN t_usuarios u ON u.id = t.id_usuario
                    LEFT JOIN t_terceros ter ON ter.id = t.id_tercero
                    WHERE
                        t.fecha >= '$desde' AND t.fecha < '$hasta' AND
                        IFNULL(t.anulado, 0) = 0 AND
                        t.sw = $sw AND
                        t.id_bodega = $bodega
                        $filtroUsuario
                    ORDER BY t.fecha DESC, t.numero DESC
                    LIMIT $limite";
        } else {
            echo json_encode([]);
            break;
        }
        echo json_encode(GenerarArray($sql));
        break;

    /* ---------------------------------------------------------------
     * Cumplimiento del presupuesto de la bodega de la sesión.
     * Devuelve el presupuesto total por tipo (zona / vendedor / usuario)
     * y la venta neta del mes en la bodega (ventas sw 5 - devoluciones sw 4, sin IVA).
     * --------------------------------------------------------------- */
    case 'presupuesto-cumplimiento':
        $bodega = (int) $_SESSION["Ses_id_bodega"];
        $sql = "SELECT tipo, ROUND(SUM(valor), 2) AS presupuesto, COUNT(*) AS registros
                FROM t_presupuesto_ventas
                WHERE anio = $anio AND mes = $mes AND id_bodega = $bodega
                GROUP BY tipo";
        $presupuestos = GenerarArray($sql) ?: [];

        $venta = 0;
        if ($presupuestos) {
            $sql = "SELECT
                        IFNULL(ROUND(SUM(
                            CASE
                                WHEN d.sw = 5 THEN d.valor_unitario * (1 - (d.pcj_descuento / 100)) * d.cantidad
                                ELSE d.valor_unitario * (1 - (d.pcj_descuento / 100)) * d.cantidad * -1
                            END
                        ), 2), 0) AS venta
                    FROM t_transacciones_detalle d
                    INNER JOIN t_transacciones t ON t.id = d.id_transaccion AND IFNULL(t.anulado, 0) = 0
                    WHERE
                        d.sw IN (5, 4) AND
                        d.fec >= '$desde' AND d.fec < '$hasta' AND
                        d.id_bodega = $bodega";
            $r = GenerarArray($sql);
            $venta = (float) ($r[0]['venta'] ?? 0);
        }
        echo json_encode(['presupuestos' => $presupuestos, 'venta' => $venta]);
        break;

    /* Detalle del cumplimiento por zona, vendedor o usuario en la bodega de la sesión */
    case 'presupuesto-detalle':
        // Misma configuración del módulo de presupuesto de ventas
        $TIPOS = [
            'zona'     => ['tabla' => 't_zonas_ventas', 'id' => 'id', 'nombre' => 'descripcion',
                           'venta_join' => 'INNER JOIN t_terceros ter ON ter.id = t.id_tercero',
                           'venta_campo' => 'ter.id_zona_ventas'],
            'vendedor' => ['tabla' => 'v_vendedores',   'id' => 'id', 'nombre' => 'tratamiento',
                           'venta_join' => '',
                           'venta_campo' => 't.vendedor'],
            'usuario'  => ['tabla' => 't_usuarios',     'id' => 'id', 'nombre' => 'usuario',
                           'venta_join' => '',
                           'venta_campo' => 't.id_usuario'],
        ];
        $tipo = $_POST['tipo'] ?? '';
        if (!isset($TIPOS[$tipo])) {
            echo json_encode([]);
            break;
        }
        $t = $TIPOS[$tipo];
        $bodega = (int) $_SESSION["Ses_id_bodega"];

        $sql = "SELECT
                    p.id_referencia,
                    IFNULL(r.{$t['nombre']}, '(sin nombre)') AS referencia,
                    p.valor AS presupuesto,
                    IFNULL(v.venta, 0) AS venta,
                    IF(p.valor > 0, ROUND(IFNULL(v.venta, 0) / p.valor * 100, 1), NULL) AS cumplimiento
                FROM t_presupuesto_ventas p
                LEFT JOIN {$t['tabla']} r ON r.{$t['id']} = p.id_referencia
                LEFT JOIN (
                    SELECT
                        {$t['venta_campo']} AS id_referencia,
                        ROUND(SUM(
                            CASE
                                WHEN d.sw = 5 THEN d.valor_unitario * (1 - (d.pcj_descuento / 100)) * d.cantidad
                                ELSE d.valor_unitario * (1 - (d.pcj_descuento / 100)) * d.cantidad * -1
                            END
                        ), 2) AS venta
                    FROM t_transacciones_detalle d
                    INNER JOIN t_transacciones t ON t.id = d.id_transaccion AND IFNULL(t.anulado, 0) = 0
                    {$t['venta_join']}
                    WHERE
                        d.sw IN (5, 4) AND
                        d.fec >= '$desde' AND d.fec < '$hasta' AND
                        d.id_bodega = $bodega
                    GROUP BY {$t['venta_campo']}
                ) v ON v.id_referencia = p.id_referencia
                WHERE p.anio = $anio AND p.mes = $mes AND p.id_bodega = $bodega AND p.tipo = '$tipo'
                ORDER BY cumplimiento IS NULL, cumplimiento DESC";
        echo json_encode(GenerarArray($sql));
        break;
}