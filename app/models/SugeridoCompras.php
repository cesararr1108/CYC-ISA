<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once('bd.php');
require_once('funciones.php');
$conn = conectar();

function getRotacionMensual($conexion, $meses = 3, $filtros = [], $dias = 30, $agrupar_productos = false, $nit_proveedor = null, $incluir_mes_actual = 1)
{
    // 🔒 Validación   
    $meses = (int) $meses;
    if ($meses <= 0 || $meses > 12) {
        $meses = 3;
    }
    $dias = (int) $dias;
    if ($dias <= 0) {
        $dias = 30;
    }
    $agrupar_productos = isset($filtros['agrupar_productos']) ? (bool) $filtros['agrupar_productos'] : false;
    $nit_proveedor = !empty($nit_proveedor) ? trim($nit_proveedor) : null;

    $cols = [];
    $nombresMes = [];

    // 📅 Obtener mes y año actual para filtrar stock
    $mes_actual = (int) date('m');
    $ano_actual = (int) date('Y');

    // 🧠 Mapeo meses español
    $map = [
        'jan' => 'ene',
        'feb' => 'feb',
        'mar' => 'mar',
        'apr' => 'abr',
        'may' => 'may',
        'jun' => 'jun',
        'jul' => 'jul',
        'aug' => 'ago',
        'sep' => 'sep',
        'oct' => 'oct',
        'nov' => 'nov',
        'dec' => 'dic'
    ];

    // 🔧 Generar columnas dinámicas - INVERTIDAS (primero los más viejos)
    // Usar primer día del mes para evitar problemas de ajuste (ej: 31 marzo -> febrero)
    /*$today = new DateTime('first day of this month');
    $fechaInicio = new DateTime('first day of this month');
    $fechaInicio->modify('-' . ($meses - 1) . ' months');*/
    if ($incluir_mes_actual == 1) {
        $today = new DateTime('first day of this month');
        $fechaInicio = new DateTime('first day of this month');
        $fechaInicio->modify('-' . ($meses - 1) . ' months');
    } else {
        // El último mes será el anterior
        $today = new DateTime('first day of last month');
        $fechaInicio = clone $today;
        $fechaInicio->modify('-' . ($meses - 1) . ' months');
    }

    $fechaBaseSql = $today->format('Y-m-01');


    for ($i = ($meses - 1); $i >= 0; $i--) {
        // Restar meses desde el primer día del mes actual
        $fechaMes = clone $today;

        $fechaMes->modify("-$i months");

        $mesNombre = strtolower($fechaMes->format('M'));
        $anio = $fechaMes->format('Y');
        $alias = ($map[$mesNombre] ?? $mesNombre) . "_" . $anio;
        $nombresMes[] = $alias;
        $cols[] = "
            SUM(CASE 
                WHEN DATE_FORMAT(v.fec, '%Y-%m') = DATE_FORMAT(
                                                        DATE_SUB('$fechaBaseSql', INTERVAL $i MONTH),
                                                        '%Y-%m'
                                                   )
                THEN v.rotacion ELSE 0 END) AS `$alias`
        ";
/*
        $cols[] = "
            SUM(CASE 
                WHEN DATE_FORMAT(v.fec, '%Y-%m') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL $i MONTH), '%Y-%m')
                THEN v.rotacion ELSE 0 END) AS `$alias`
        ";*/
    }

    $cols_sql = implode(",\n", $cols);

    // 🧠 promedio dinámico
    $promedio_sql = "(" . implode(" + ", array_map(fn($m) => "base.`$m`", $nombresMes)) . ") / $meses";

    // 🔨 CONSTRUIR WHERE DINÁMICO con filtros
    $where_parts = [
        "v.fec >= '" . $fechaInicio->format('Y-m-d') . "'",
        "r.tipo = 'P'",
        "r.activo = 1"
    ];

    // Agregar filtros opcionales
    if (!empty($filtros['marca']) && $filtros['marca'] != '') {
        $marca_id = (int) $filtros['marca'];
        $where_parts[] = "r.id_marca = $marca_id";
    }

    if (!empty($filtros['categoria1']) && $filtros['categoria1'] != '') {
        $cat_id = (int) $filtros['categoria1'];
        $where_parts[] = "r.id_categoria_1 = $cat_id";
    }

    if (!empty($filtros['categoria2']) && $filtros['categoria2'] != '') {
        $cat_id = (int) $filtros['categoria2'];
        $where_parts[] = "r.id_categoria_2 = $cat_id";
    }

    if (!empty($filtros['categoria3']) && $filtros['categoria3'] != '') {
        $cat_id = (int) $filtros['categoria3'];
        $where_parts[] = "r.id_categoria_3 = $cat_id";
    }

    if (!empty($filtros['categoria4']) && $filtros['categoria4'] != '') {
        $cat_id = (int) $filtros['categoria4'];
        $where_parts[] = "r.id_categoria_4 = $cat_id";
    }

    // Solo filtrar por bodega si NO está agrupando productos
    if (!$agrupar_productos && !empty($filtros['bodegas']) && is_array($filtros['bodegas'])) {
        $bodegas_escape = array_map(function ($b) {
            return "'" . addslashes($b) . "'";
        }, $filtros['bodegas']);
        $bodegas_list = implode(',', $bodegas_escape);
        $where_parts[] = "v.id_bodega IN ($bodegas_list)";
    }

    $where_sql = implode(" AND ", $where_parts);

    // 🧾 Construir SELECT dinámico según si agrupa o no
    $select_bodega = $agrupar_productos
        ? "'TODAS LAS BODEGAS' AS nombre_bodega"
        : "b.descripcion AS nombre_bodega";

    // Dinámicamente construir base_select y group_by
    if ($agrupar_productos) {
        // MODO AGRUPADO: Sin discriminación por bodega
        $base_select = "
            v.id_referencia,
            r.codigo,
            r.descripcion,
            'TODAS LAS BODEGAS' AS nombre_bodega,
            m.descripcion AS marca,
            c1.descripcion AS categoria1,
            c2.descripcion AS categoria2,
            c3.descripcion AS categoria3,
            c4.descripcion AS categoria4,
            r.costo_unitario,
            SUM(v.rotacion) AS rotacion_total,
            $cols_sql
        ";
        $group_by = "GROUP BY v.id_referencia, r.codigo, r.descripcion, m.descripcion, c1.descripcion, c2.descripcion, c3.descripcion, c4.descripcion, r.costo_unitario";
        $stock_group = "GROUP BY m.id_referencia, m.mes, m.ano";  // Agrupar por referencia, mes y año
        $stock_bp_group = "GROUP BY m.id_referencia, m.mes, m.ano";  // Agrupar por referencia, mes y año
        $stock_join_on = "stock.id_referencia = base.id_referencia AND stock.mes = $mes_actual AND stock.ano = $ano_actual";
        $stock_bp_join_on = "stock_bp.id_referencia = base.id_referencia AND stock_bp.mes = $mes_actual AND stock_bp.ano = $ano_actual";
    } else {
        // MODO NORMAL: Con discriminación por bodega
        $base_select = "
            v.id_referencia,
            v.id_bodega,
            r.codigo,
            r.descripcion,
            b.descripcion AS nombre_bodega,
            m.descripcion AS marca,
            c1.descripcion AS categoria1,
            c2.descripcion AS categoria2,
            c3.descripcion AS categoria3,
            c4.descripcion AS categoria4,
            r.costo_unitario,
            SUM(v.rotacion) AS rotacion_total,
            $cols_sql
        ";
        $group_by = "GROUP BY v.id_referencia, v.id_bodega, r.codigo, r.descripcion, b.descripcion, m.descripcion, c1.descripcion, c2.descripcion, c3.descripcion, c4.descripcion, r.costo_unitario";
        $stock_group = "GROUP BY m.id_referencia, m.id_bodega, m.mes, m.ano";  // Agrupar por referencia, bodega, mes y año
        $stock_bp_group = "GROUP BY m.id_referencia, m.mes, m.ano";  // Stock bodega principal: sin discriminación por bodega
        $stock_join_on = "stock.id_referencia = base.id_referencia AND stock.id_bodega = base.id_bodega AND stock.mes = $mes_actual AND stock.ano = $ano_actual";
        $stock_bp_join_on = "stock_bp.id_referencia = base.id_referencia AND stock_bp.mes = $mes_actual AND stock_bp.ano = $ano_actual";
    }

    // 🧾 QUERY FINAL
    $sql = "
    SELECT 
        base.*,
        IFNULL(stock.stock, 0) AS stock,
        IFNULL(stock.costo_promedio, 0) AS costo_promedio,
        IFNULL(ref.ultimo_costo_compra, 0) AS ultimo_costo_compra,
        IFNULL(bp.descripcion, 'N/A') AS bodega_principal,
        IFNULL(stock_bp.stock, 0) AS stock_bodega_principal,

        IFNULL(ref.und_compra, 1) AS und_compra,
        CASE 
            WHEN ref.und_compra = 1 THEN IFNULL(ref.und_1, 'UNIDAD')
            WHEN ref.und_compra = 2 THEN IFNULL(ref.und_2, 'UNIDAD')
            WHEN ref.und_compra = 3 THEN IFNULL(ref.und_3, 'UNIDAD')
            WHEN ref.und_compra = 4 THEN IFNULL(ref.und_4, 'UNIDAD')
            ELSE 'UNIDAD'
        END AS label_unidad,
        CASE 
            WHEN ref.und_compra = 1 THEN IFNULL(ref.cant_1, 1)
            WHEN ref.und_compra = 2 THEN IFNULL(ref.cant_2, 1)
            WHEN ref.und_compra = 3 THEN IFNULL(ref.cant_3, 1)
            WHEN ref.und_compra = 4 THEN IFNULL(ref.cant_4, 1)
            ELSE 1
        END AS conversion_compra,
        ROUND($promedio_sql,2) AS promedio,
        ROUND(($promedio_sql / 30), 2) AS rotacion_diaria,
        CEIL(($promedio_sql / 30) * $dias) AS sugerido_dias,
        IFNULL(pendientes.cantidad_pendiente, 0) AS cantidad_pendiente,
        CASE 
            WHEN (CEIL(($promedio_sql / 30) * $dias) - COALESCE(IFNULL(stock.stock, NULL), IFNULL(stock_bp.stock, 0)) - IFNULL(pendientes.cantidad_pendiente, 0)) > 0 THEN 'SÍ'
            ELSE 'NO'
        END AS comprar,
        CASE 
            WHEN (CEIL(($promedio_sql / 30) * $dias) - COALESCE(IFNULL(stock.stock, NULL), IFNULL(stock_bp.stock, 0)) - IFNULL(pendientes.cantidad_pendiente, 0)) > 0
            THEN CEIL(($promedio_sql / 30) * $dias) - COALESCE(IFNULL(stock.stock, NULL), IFNULL(stock_bp.stock, 0)) - IFNULL(pendientes.cantidad_pendiente, 0)
            ELSE 0
        END AS cant_pedir,
        CASE 
            WHEN IFNULL(proveedor.precio, 0) > 0 THEN proveedor.precio
            WHEN IFNULL(ref.ultimo_costo_compra, 0) > 0 THEN ref.ultimo_costo_compra
            ELSE IFNULL(stock.costo_promedio, 0)
        END AS costo_compra,
        IFNULL( stock_bp.stock*stock.costo_promedio, 0 ) AS total_cst_bodega_principal,
        IFNULL( stock.stock*stock.costo_promedio, 0 ) AS total_cst_bodega

    FROM (
        SELECT 
            $base_select

        FROM (
            SELECT
                d.id_referencia,
                d.id_bodega,
                DATE(d.fecha_hora) AS fec,
                SUM(
                    CASE 
                        WHEN d.sw = 5 THEN d.cantidad
                        WHEN d.sw = 4 THEN -d.cantidad
                        ELSE 0
                    END
                ) AS rotacion
            FROM t_transacciones_detalle d
            JOIN t_referencias r ON r.id = d.id_referencia
            WHERE d.sw IN (4,5)
              AND r.tipo = 'P'
              AND r.activo = 1
            GROUP BY DATE(d.fecha_hora), d.id_referencia, d.id_bodega
        ) v

        JOIN t_referencias r ON r.id = v.id_referencia
        " . (!$agrupar_productos ? "LEFT JOIN t_almacenes b ON b.id = v.id_bodega" : "") . "
        LEFT JOIN t_marcas m ON m.id = r.id_marca
        LEFT JOIN t_referencias_categoria1 c1 ON c1.id = r.id_categoria_1
        LEFT JOIN t_referencias_categoria2 c2 ON c2.id = r.id_categoria_2
        LEFT JOIN t_referencias_categoria3 c3 ON c3.id = r.id_categoria_3
        LEFT JOIN t_referencias_categoria4 c4 ON c4.id = r.id_categoria_4

        WHERE $where_sql

        $group_by
    ) base

    LEFT JOIN (
        SELECT 
            m.id_referencia" . (!$agrupar_productos ? ",           m.id_bodega" : "") . ",
            m.mes,
            m.ano,
            SUM((IFNULL(m.cant_ini, 0) + IFNULL(m.cant_ent, 0)) - IFNULL(m.cant_sal, 0)) AS stock,
            CASE 
                WHEN SUM((IFNULL(m.cant_ini, 0) + IFNULL(m.cant_ent, 0)) - IFNULL(m.cant_sal, 0)) > 0 
                THEN SUM((IFNULL(m.costo_ini, 0) + IFNULL(m.costo_ent, 0)) - IFNULL(m.costo_sal, 0)) / NULLIF(SUM((IFNULL(m.cant_ini, 0) + IFNULL(m.cant_ent, 0)) - IFNULL(m.cant_sal, 0)), 0)
                ELSE 0
            END  AS costo_promedio
        FROM t_referencias_mov m
        WHERE m.mes = $mes_actual AND m.ano = $ano_actual
        $stock_group
    ) stock ON $stock_join_on
    LEFT JOIN (
        SELECT 
            m.id_referencia,
            m.mes,
            m.ano,
            SUM((IFNULL(m.cant_ini, 0) + IFNULL(m.cant_ent, 0)) - IFNULL(m.cant_sal, 0)) AS stock
        FROM t_referencias_mov m
        JOIN t_almacenes a ON a.id = m.id_bodega AND a.principal = 'S'
        WHERE m.mes = $mes_actual AND m.ano = $ano_actual
        $stock_bp_group
    ) stock_bp ON $stock_bp_join_on
    LEFT JOIN (
        SELECT 
            pd.id_referencia" . (!$agrupar_productos ? ",           pd.id_bodega" : "") . ",
            SUM(pd.cantidad - IFNULL(pd.cantidad_desp, 0)) AS cantidad_pendiente
        FROM t_pedidos_detalle pd
        JOIN t_pedidos p ON p.id = pd.id_pedido AND p.estado IN ('D', 'P')
        WHERE pd.cantidad > IFNULL(pd.cantidad_desp, 0)
        GROUP BY pd.id_referencia" . (!$agrupar_productos ? ", pd.id_bodega" : "") . "
    ) pendientes ON pendientes.id_referencia = base.id_referencia" . (!$agrupar_productos ? " AND pendientes.id_bodega = base.id_bodega" : "") . "
    LEFT JOIN t_referencias ref ON ref.id = base.id_referencia
    LEFT JOIN t_almacenes bp ON bp.principal = 'S'
    " . (!is_null($nit_proveedor) ? "LEFT JOIN t_listados_precios_proveedor proveedor ON proveedor.id_referencia = base.id_referencia AND proveedor.nit_proveedor = '" . addslashes($nit_proveedor) . "'" : "") . "
   -- WHERE base.id_referencia = 4446
    ORDER BY base.descripcion";

    $result = $conexion->query($sql);

    if (!$result) {
        return [
            'ok' => false,
            'error' => $conexion->error,
            'sql' => $sql
        ];
    }

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    // Aplicar filtros post-consulta
    if (!empty($filtros['solo_rotacion']) && $filtros['solo_rotacion'] == 1) {
        $data = array_filter($data, function ($row) {
            return floatval($row['rotacion_total']) > 0;
        });
    }

    if (!empty($filtros['solo_sugerir']) && $filtros['solo_sugerir'] == 1) {
        $data = array_filter($data, function ($row) {
            return $row['comprar'] === 'SÍ';
        });
    }

    return [
        'ok' => true,
        'meses' => $meses,
        'columnas' => $nombresMes,
        'data' => array_values($data),  // Reiniciar índices del array
        'total' => count($data)
    ];
}

function validarPedidoHeader($data)
{
    // 1. numero
    if (!isset($data['numero']) || !is_numeric($data['numero']) || (int) $data['numero'] <= 0) {
        throw new Exception("El campo 'numero' del pedido debe ser un entero positivo.");
    }
    $val_numero = (int) $data['numero'];

    // 2. id_usuario
    if (!isset($data['id_usuario']) || !is_numeric($data['id_usuario']) || (int) $data['id_usuario'] <= 0) {
        throw new Exception("El campo 'id_usuario' debe ser un entero positivo.");
    }
    $val_id_usuario = (int) $data['id_usuario'];

    // 3. id_tercero
    if (!isset($data['id_tercero']) || !is_numeric($data['id_tercero']) || (int) $data['id_tercero'] <= 0) {
        throw new Exception("El campo 'id_tercero' (proveedor) debe ser un entero positivo.");
    }
    $val_id_tercero = (int) $data['id_tercero'];

    // 4. id_bodega
    if (!isset($data['id_bodega']) || !is_numeric($data['id_bodega']) || (int) $data['id_bodega'] <= 0) {
        throw new Exception("El campo 'id_bodega' debe ser un entero positivo.");
    }
    $val_id_bodega = (int) $data['id_bodega'];

    // 5. id_condicion
    if (!isset($data['id_condicion']) || !is_numeric($data['id_condicion']) || (int) $data['id_condicion'] <= 0) {
        throw new Exception("El campo 'id_condicion' debe ser un entero positivo.");
    }
    $val_id_condicion = (int) $data['id_condicion'];

    // 6. id_tercero_dir
    $val_id_tercero_dir = isset($data['id_tercero_dir']) && is_numeric($data['id_tercero_dir']) ? (int) $data['id_tercero_dir'] : 0;
    if ($val_id_tercero_dir < 0) {
        throw new Exception("El campo 'id_tercero_dir' debe ser un entero no negativo.");
    }

    // 7. id_tipo_documento
    if (!isset($data['id_tipo_documento']) || !is_numeric($data['id_tipo_documento']) || (int) $data['id_tipo_documento'] <= 0) {
        throw new Exception("El campo 'id_tipo_documento' debe ser un entero positivo.");
    }
    $val_id_tipo_documento = (int) $data['id_tipo_documento'];

    // 8. estado
    $val_estado = isset($data['estado']) ? trim($data['estado']) : 'T';
    if ($val_estado !== 'T' && $val_estado !== 'P') {
        throw new Exception("El campo 'estado' debe ser 'T' o 'P'.");
    }

    // 9. valor_total
    if (!isset($data['valor_total']) || !is_numeric($data['valor_total'])) {
        throw new Exception("El campo 'valor_total' debe ser numérico.");
    }
    $val_valor_total = floatval($data['valor_total']);
    if ($val_valor_total < 0) {
        throw new Exception("El campo 'valor_total' no puede ser negativo.");
    }

    // 10. sw
    if (!isset($data['sw']) || !is_numeric($data['sw']) || (int) $data['sw'] !== 16) {
        throw new Exception("El campo 'sw' del pedido debe ser 16 (Entrada/Compra).");
    }
    $val_sw = (int) $data['sw'];

    // 11. iva
    $val_iva = isset($data['iva']) && is_numeric($data['iva']) ? floatval($data['iva']) : 0.0;
    if ($val_iva < 0) {
        throw new Exception("El campo 'iva' no puede ser negativo.");
    }

    // 12. descuento
    $val_descuento = isset($data['descuento']) && is_numeric($data['descuento']) ? floatval($data['descuento']) : 0.0;
    if ($val_descuento < 0) {
        throw new Exception("El campo 'descuento' no puede ser negativo.");
    }

    // 13. zona_ventas
    $val_zona_ventas = isset($data['zona_ventas']) ? substr(trim($data['zona_ventas']), 0, 30) : '';

    // 14. zona_descripcion
    $val_zona_descripcion = isset($data['zona_descripcion']) ? substr(trim($data['zona_descripcion']), 0, 100) : '';

    // 15. vendedor
    $val_vendedor = isset($data['vendedor']) && is_numeric($data['vendedor']) ? (int) $data['vendedor'] : 0;

    // 16. estado_edi
    $val_estado_edi = isset($data['estado_edi']) ? substr(trim($data['estado_edi']), 0, 50) : '';

    // 17. respuesta_edi
    $val_respuesta_edi = isset($data['respuesta_edi']) ? substr(trim($data['respuesta_edi']), 0, 255) : '';

    // 18. fecha_edi
    $val_fecha_edi = !empty($data['fecha_edi']) ? trim($data['fecha_edi']) : null;
    if ($val_fecha_edi !== null) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}(\s\d{2}:\d{2}:\d{2})?$/', $val_fecha_edi)) {
            throw new Exception("El formato de 'fecha_edi' es incorrecto.");
        }
    }

    // 19. notas
    $val_notas = isset($data['notas']) ? trim($data['notas']) : '';

    // 20. fecha_hora
    $val_fecha_hora = isset($data['fecha_hora']) ? trim($data['fecha_hora']) : date('Y-m-d H:i:s');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/', $val_fecha_hora)) {
        throw new Exception("El formato de 'fecha_hora' es incorrecto.");
    }

    // 21. lista_precios
    $val_lista_precios = isset($data['lista_precios']) && is_numeric($data['lista_precios']) ? (int) $data['lista_precios'] : 0;

    return [
        'numero' => $val_numero,
        'id_usuario' => $val_id_usuario,
        'id_tercero' => $val_id_tercero,
        'id_bodega' => $val_id_bodega,
        'id_condicion' => $val_id_condicion,
        'id_tercero_dir' => $val_id_tercero_dir,
        'id_tipo_documento' => $val_id_tipo_documento,
        'estado' => $val_estado,
        'valor_total' => $val_valor_total,
        'sw' => $val_sw,
        'iva' => $val_iva,
        'descuento' => $val_descuento,
        'zona_ventas' => $val_zona_ventas,
        'zona_descripcion' => $val_zona_descripcion,
        'vendedor' => $val_vendedor,
        'estado_edi' => $val_estado_edi,
        'respuesta_edi' => $val_respuesta_edi,
        'fecha_edi' => $val_fecha_edi,
        'notas' => $val_notas,
        'fecha_hora' => $val_fecha_hora,
        'lista_precios' => $val_lista_precios
    ];
}

function validarPedidoDetalle($data)
{
    // 1. id_pedido
    if (!isset($data['id_pedido']) || !is_numeric($data['id_pedido']) || (int) $data['id_pedido'] <= 0) {
        throw new Exception("El campo 'id_pedido' del detalle debe ser un entero positivo.");
    }
    $val_id_pedido = (int) $data['id_pedido'];

    // 2. id_referencia
    if (!isset($data['id_referencia']) || !is_numeric($data['id_referencia']) || (int) $data['id_referencia'] <= 0) {
        throw new Exception("El campo 'id_referencia' del detalle debe ser un entero positivo.");
    }
    $val_id_referencia = (int) $data['id_referencia'];

    // 3. cantidad
    if (!isset($data['cantidad']) || !is_numeric($data['cantidad']) || (int) $data['cantidad'] <= 0) {
        throw new Exception("El campo 'cantidad' del detalle debe ser un entero positivo.");
    }
    $val_cantidad = (int) $data['cantidad'];

    // 4. valor_unitario
    if (!isset($data['valor_unitario']) || !is_numeric($data['valor_unitario'])) {
        throw new Exception("El campo 'valor_unitario' del detalle debe ser numérico.");
    }
    $val_valor_unitario = floatval($data['valor_unitario']);
    if ($val_valor_unitario < 0) {
        throw new Exception("El campo 'valor_unitario' del detalle no puede ser negativo.");
    }

    // 5. costo_unitario
    if (!isset($data['costo_unitario']) || !is_numeric($data['costo_unitario'])) {
        throw new Exception("El campo 'costo_unitario' del detalle debe ser numérico.");
    }
    $val_costo_unitario = floatval($data['costo_unitario']);
    if ($val_costo_unitario < 0) {
        throw new Exception("El campo 'costo_unitario' del detalle no puede ser negativo.");
    }

    // 6. pcj_iva
    $val_pcj_iva = isset($data['pcj_iva']) && is_numeric($data['pcj_iva']) ? floatval($data['pcj_iva']) : 0.0;
    if ($val_pcj_iva < 0 || $val_pcj_iva > 100) {
        throw new Exception("El campo 'pcj_iva' del detalle debe estar entre 0 y 100.");
    }

    // 7. pcj_descuento
    $val_pcj_descuento = isset($data['pcj_descuento']) && is_numeric($data['pcj_descuento']) ? floatval($data['pcj_descuento']) : 0.0;
    if ($val_pcj_descuento < 0 || $val_pcj_descuento > 100) {
        throw new Exception("El campo 'pcj_descuento' del detalle debe estar entre 0 y 100.");
    }

    // 8. und
    $val_und = isset($data['und']) ? trim($data['und']) : 'UND';
    if (empty($val_und)) {
        throw new Exception("El campo 'und' del detalle no puede estar vacío.");
    }
    $val_und = substr($val_und, 0, 10);

    // 9. can_und
    $val_can_und = isset($data['can_und']) && is_numeric($data['can_und']) ? (int) $data['can_und'] : 1;
    if ($val_can_und < 1) {
        throw new Exception("El campo 'can_und' (conversión) debe ser un entero mayor o igual a 1.");
    }

    // 10. sw
    if (!isset($data['sw']) || !is_numeric($data['sw']) || (int) $data['sw'] !== 16) {
        throw new Exception("El campo 'sw' del detalle debe ser 16 (Entrada/Compra).");
    }
    $val_sw = (int) $data['sw'];

    // 11. id_bodega
    if (!isset($data['id_bodega']) || !is_numeric($data['id_bodega']) || (int) $data['id_bodega'] <= 0) {
        throw new Exception("El campo 'id_bodega' del detalle debe ser un entero positivo.");
    }
    $val_id_bodega = (int) $data['id_bodega'];

    // 12. cantidad_desp
    $val_cantidad_desp = isset($data['cantidad_desp']) && is_numeric($data['cantidad_desp']) ? (int) $data['cantidad_desp'] : 0;
    if ($val_cantidad_desp < 0) {
        throw new Exception("El campo 'cantidad_desp' no puede ser negativo.");
    }

    // 13. vendedor
    $val_vendedor = isset($data['vendedor']) && is_numeric($data['vendedor']) ? (int) $data['vendedor'] : 0;

    // 14. fecha
    $val_fecha = isset($data['fecha']) ? trim($data['fecha']) : date('Y-m-d H:i:s');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}(\s\d{2}:\d{2}:\d{2})?$/', $val_fecha)) {
        throw new Exception("El formato de 'fecha' en el detalle es incorrecto.");
    }

    // 15. cantidad2
    $val_cantidad2 = isset($data['cantidad2']) && is_numeric($data['cantidad2']) ? (int) $data['cantidad2'] : 0;

    // 16. impoconsumo
    $val_impoconsumo = isset($data['impoconsumo']) && is_numeric($data['impoconsumo']) ? floatval($data['impoconsumo']) : 0.0;
    if ($val_impoconsumo < 0) {
        throw new Exception("El campo 'impoconsumo' no puede ser negativo.");
    }

    // 17. ibua
    $val_ibua = isset($data['ibua']) && is_numeric($data['ibua']) ? floatval($data['ibua']) : 0.0;
    if ($val_ibua < 0) {
        throw new Exception("El campo 'ibua' no puede ser negativo.");
    }

    // 18. icui
    $val_icui = isset($data['icui']) && is_numeric($data['icui']) ? floatval($data['icui']) : 0.0;
    if ($val_icui < 0) {
        throw new Exception("El campo 'icui' no puede ser negativo.");
    }

    return [
        'id_pedido' => $val_id_pedido,
        'id_referencia' => $val_id_referencia,
        'cantidad' => $val_cantidad,
        'valor_unitario' => $val_valor_unitario,
        'costo_unitario' => $val_costo_unitario,
        'pcj_iva' => $val_pcj_iva,
        'pcj_descuento' => $val_pcj_descuento,
        'und' => $val_und,
        'can_und' => $val_can_und,
        'sw' => $val_sw,
        'id_bodega' => $val_id_bodega,
        'cantidad_desp' => $val_cantidad_desp,
        'vendedor' => $val_vendedor,
        'fecha' => $val_fecha,
        'cantidad2' => $val_cantidad2,
        'impoconsumo' => $val_impoconsumo,
        'ibua' => $val_ibua,
        'icui' => $val_icui
    ];
}

switch ($_POST["op"]) {
    case "getMarcas":
        $marcas = GenerarArray("SELECT DISTINCT id,descripcion FROM t_marcas  ORDER BY descripcion");;
        echo json_encode($marcas);
        break;
    case "getCategorias1":
        $categorias = GenerarArray("SELECT DISTINCT id,descripcion FROM t_referencias_categoria1   ORDER BY descripcion");;
        echo json_encode($categorias);
        break;
    case "getCategorias2":
        $categorias = GenerarArray("SELECT DISTINCT id,descripcion FROM t_referencias_categoria2  ORDER BY descripcion");;
        echo json_encode($categorias);
        break;
    case "getCategorias3":
        $categorias = GenerarArray("SELECT DISTINCT id,descripcion FROM t_referencias_categoria3  ORDER BY descripcion");;
        echo json_encode($categorias);
        break;
    case "getCategorias4":
        $categorias = GenerarArray("SELECT DISTINCT id,descripcion FROM t_referencias_categoria4 ORDER BY descripcion");;
        echo json_encode($categorias);
        break;

    case "generarSugerido":
        // Capturar y validar parámetros
        $marca = isset($_POST["marca"]) ? trim($_POST["marca"]) : '';
        $categoria1 = isset($_POST["categoria1"]) ? trim($_POST["categoria1"]) : '';
        $categoria2 = isset($_POST["categoria2"]) ? trim($_POST["categoria2"]) : '';
        $categoria3 = isset($_POST["categoria3"]) ? trim($_POST["categoria3"]) : '';
        $categoria4 = isset($_POST["categoria4"]) ? trim($_POST["categoria4"]) : '';
        $bodegas = isset($_POST["bodegas"]) ? json_decode($_POST["bodegas"], true) : [];
        $meses = isset($_POST["meses"]) ? (int) $_POST["meses"] : 3;
        $dias = isset($_POST["dias"]) ? (int) $_POST["dias"] : 30;
        $solo_rotacion = isset($_POST["solo_rotacion"]) ? (int) $_POST["solo_rotacion"] : 0;
        $solo_sugerir = isset($_POST["solo_sugerir"]) ? (int) $_POST["solo_sugerir"] : 0;
        $nit_proveedor = isset($_POST["nit_proveedor"]) ? trim($_POST["nit_proveedor"]) : null;
        $incluir_mes_actual = isset($_POST["incluir_mes_actual"]) ? (int) $_POST["incluir_mes_actual"] : 0;

        // Validar que tenga al menos una bodega, esto solo si agruparProductos es 0
        $agrupar_productos = isset($_POST["agruparProductos"]) ? (int) $_POST["agruparProductos"] : 0;
        if (!$agrupar_productos) {
            if (empty($bodegas) || !is_array($bodegas)) {
                echo json_encode([
                    'ok' => false,
                    'error' => 'Debe seleccionar al menos una bodega'
                ]);
                break;
            }
        }

        // Armar array de filtros  
        $filtros = [
            'marca' => $marca,
            'categoria1' => $categoria1,
            'categoria2' => $categoria2,
            'categoria3' => $categoria3,
            'categoria4' => $categoria4,
            'bodegas' => $bodegas,
            'solo_rotacion' => $solo_rotacion,
            'solo_sugerir' => $solo_sugerir,
            'agrupar_productos' => $agrupar_productos
        ];

        // Llamar función con filtros y nit_proveedor
        $response = getRotacionMensual($conn, $meses, $filtros, $dias, false, $nit_proveedor, $incluir_mes_actual);

        // Verificar si hubo error en la consulta
        if (!$response['ok']) {
            echo json_encode($response);
            break;
        }

        // Construir columnas para DataTable
        if ($filtros['agrupar_productos']) {
            // Cuando está agrupado, no mostrar nombre_bodega pero SÍ descripción 
            $columnas = ['codigo', 'descripcion', 'marca', 'label_unidad', 'conversion_compra'];
        } else {
            // Cuando NO está agrupado, mostrar id_bodega ANTES de nombre_bodega
            $columnas = ['codigo', 'descripcion', 'id_bodega', 'nombre_bodega', 'bodega_principal', 'marca', 'label_unidad', 'conversion_compra'];
        }
        $columnas = array_merge($columnas, $response['columnas']);
        $columnas = array_merge($columnas, [
            'rotacion_total',
            'promedio',
            'rotacion_diaria',
            'sugerido_dias',
            'stock',
            'stock_bodega_principal',
            'cantidad_pendiente',
            'cant_pedir',
            'comprar',
            'costo_promedio',
            'ultimo_costo_compra',
            'costo_compra',
            'label_unidad',
            'conversion_compra',
            'und_compra'
        ]);

        // Calcular costo total de compra usando costo_compra
        $costo_total_compra = 0;
        foreach ($response['data'] as $row) {
            if ($row['comprar'] === 'SÍ') {
                $cant_pedir = floatval($row['cant_pedir']) ?? 0;
                $costo_compra = floatval($row['costo_compra']) ?? 0;
                $costo_total_compra += ($cant_pedir * $costo_compra);
            }
        }

        // Respuesta optimizada para DataTable
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => true,
            'meses' => $response['meses'],
            'dias' => $dias,
            'columnas' => $columnas,
            'columnas_meses' => $response['columnas'],
            'registros' => $response['data'],
            'total' => $response['total'],
            'agrupar_productos' => (int) $filtros['agrupar_productos'],
            'nit_proveedor' => $nit_proveedor,
            'resumen' => [
                'total_productos' => count(array_filter($response['data'], fn($r) => $r['comprar'] === 'SÍ')),
                'meses_analizados' => $response['meses'],
                'costo_total_compra' => round($costo_total_compra, 2)
            ]
        ]);
        break;

    case "guardarOrdenCompras":

        // Validar datos requeridos
        $modo = isset($_POST["modo"]) ? trim($_POST["modo"]) : 'consolidado';
        $id_usuario = isset($_POST["id_usuario"]) ? (int) $_POST["id_usuario"] : 0;
        $id_tercero = isset($_POST["id_tercero"]) ? (int) $_POST["id_tercero"] : 0;
        $id_bodega_default = isset($_POST["id_bodega_default"]) ? (int) $_POST["id_bodega_default"] : 0;
        $id_condicion = isset($_POST["id_condicion"]) ? (int) $_POST["id_condicion"] : 0;
        $id_tercero_dir = isset($_POST["id_tercero_dir"]) ? (int) $_POST["id_tercero_dir"] : 0;
        $id_tipo_documento = isset($_POST["id_tipo_documento"]) ? (int) $_POST["id_tipo_documento"] : 0;

        $orden = isset($_POST["orden"]) ? json_decode($_POST["orden"], true) : [];
        $resumen = isset($_POST["resumen"]) ? json_decode($_POST["resumen"], true) : [];


        // Validaciones
        if (!$id_usuario || !$id_tercero || !$id_condicion || !$id_tipo_documento || empty($orden)) {
            echo json_encode([
                'ok' => false,
                'error' => 'Faltan datos requeridos para guardar la orden',
                'details' => "usuario: $id_usuario, tercero: $id_tercero, condición: $id_condicion, documento: $id_tipo_documento"
            ]);
            break;
        }


        try {
            // Iniciar transacción


            $ordenes_creadas = [];
            $fecha_actual = date('Y-m-d H:i:s');

            if ($modo === 'consolidado') {
                // 🔹 MODO CONSOLIDADO: Una orden con bodega default
                $numero_orden = numeroSiguiente($id_tipo_documento, 't_pedidos');

                $valor_total = isset($resumen['totalCosto']) ? floatval($resumen['totalCosto']) : 0.0;
                $sw = 5;  // 5 = Entrada (compra)

                $header_raw = [
                    'numero' => $numero_orden,
                    'id_usuario' => $id_usuario,
                    'id_tercero' => $id_tercero,
                    'id_bodega' => $id_bodega_default,
                    'id_condicion' => $id_condicion,
                    'id_tercero_dir' => $id_tercero_dir,
                    'id_tipo_documento' => $id_tipo_documento,
                    'estado' => 'P', // consolidated usually is 'A' (Approved/Active)
                    'valor_total' => $valor_total,
                    'sw' => $sw,
                    'iva' => 0.0,
                    'descuento' => 0.0,
                    'zona_ventas' => '',
                    'zona_descripcion' => '',
                    'vendedor' => 0,
                    'estado_edi' => '',
                    'respuesta_edi' => '',
                    'fecha_edi' => null,
                    'notas' => 'Consolidado desde sugerido de compras',
                    'fecha_hora' => $fecha_actual,
                    'lista_precios' => 0
                ];

                $header_valid = validarPedidoHeader($header_raw);

                $sql_pedido = "INSERT INTO t_pedidos (
                    numero, 
                    id_usuario, 
                    id_tercero, 
                    id_bodega, 
                    id_condicion, 
                    id_tercero_dir,
                    id_tipo_documento, 
                    estado, 
                    valor_total, 
                    sw, 
                    iva, 
                    descuento,
                    zona_ventas, 
                    zona_descripcion, 
                    vendedor, 
                    estado_edi, 
                    respuesta_edi, 
                    fecha_edi,
                    notas, 
                    fecha_hora, 
                    lista_precios
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt_pedido = $conn->prepare($sql_pedido);
                if (!$stmt_pedido) {
                    throw new Exception("Error preparando insert pedido: " . $conn->error);
                }

                $stmt_pedido->bind_param(
                    "iiiiiiisdiddssisssssi",
                    $header_valid['numero'], //int
                    $header_valid['id_usuario'], //int
                    $header_valid['id_tercero'], //int
                    $header_valid['id_bodega'], //int
                    $header_valid['id_condicion'], //int
                    $header_valid['id_tercero_dir'], //int
                    $header_valid['id_tipo_documento'], //int
                    $header_valid['estado'], //string
                    $header_valid['valor_total'], //decimal
                    $header_valid['sw'], //int
                    $header_valid['iva'], //decimal
                    $header_valid['descuento'], //decimal
                    $header_valid['zona_ventas'], //string
                    $header_valid['zona_descripcion'], //string
                    $header_valid['vendedor'], //int
                    $header_valid['estado_edi'], //string
                    $header_valid['respuesta_edi'], //string
                    $header_valid['fecha_edi'], //string
                    $header_valid['notas'], //string 
                    $header_valid['fecha_hora'], //string
                    $header_valid['lista_precios'] //int 
                );

                if (!$stmt_pedido->execute()) {
                    throw new Exception("Error al insertar pedido: " . $stmt_pedido->error);
                }

                $id_pedido = $conn->insert_id;
                $ordenes_creadas[] = $numero_orden;
                $stmt_pedido->close();

                // Insertar detalles
                $sql_detalle = "INSERT INTO t_pedidos_detalle (
                    id_pedido, 
                    id_referencia, 
                    cantidad, 
                    valor_unitario, 
                    costo_unitario,
                    pcj_iva, 
                    pcj_descuento, 
                    und, 
                    can_und, 
                    sw, 
                    id_bodega,
                    fecha, 
                    cantidad_desp, 
                    vendedor, 
                    cantidad2, 
                    impoconsumo, 
                    ibua, 
                    icui
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt_detalle = $conn->prepare($sql_detalle);
                if (!$stmt_detalle) {
                    throw new Exception("Error preparando insert detalle: " . $conn->error);
                }

                // Procesar todos los items de todas las bodegas
                foreach ($orden as $bodegaItems) {
                    foreach ($bodegaItems as $item) {
                        $id_referencia = isset($item['id_referencia']) ? (int) $item['id_referencia'] : 0;

                        // Si no tiene id_referencia, buscar por código
                        if ($id_referencia <= 0 && isset($item['codigo'])) {
                            $codigo = trim($item['codigo']);
                            $sql_buscar = "SELECT id FROM t_referencias WHERE codigo = ? LIMIT 1";
                            $stmt_buscar = $conn->prepare($sql_buscar);
                            if ($stmt_buscar) {
                                $stmt_buscar->bind_param("s", $codigo);
                                $stmt_buscar->execute();
                                $result_buscar = $stmt_buscar->get_result();
                                if ($row_buscar = $result_buscar->fetch_assoc()) {
                                    $id_referencia = (int) $row_buscar['id'];
                                }
                                $stmt_buscar->close();
                            }
                        }

                        if ($id_referencia <= 0) {
                            throw new Exception("No se encontró referencia para código: " . $item['codigo']);
                        }

                        $cantidad = isset($item['cantidad']) ? (int) $item['cantidad'] : 0;
                        $valor_unitario = isset($item['costoCompra']) ? floatval($item['costoCompra']) : 0.0;
                        $pcj_iva = isset($item['pcj_iva']) ? floatval($item['pcj_iva']) : 0.0;
                        $pcj_descuento = isset($item['pcj_descuento']) ? floatval($item['pcj_descuento']) : 0.0;
                        $und = isset($item['und']) ? $item['und'] : 'UND';
                        $can_und = isset($item['can_und']) ? (int) $item['can_und'] : 1;

                        $detalle_raw = [
                            'id_pedido' => $id_pedido,
                            'id_referencia' => $id_referencia,
                            'cantidad' => $cantidad,
                            'valor_unitario' => $valor_unitario,
                            'costo_unitario' => $valor_unitario,
                            'pcj_iva' => $pcj_iva,
                            'pcj_descuento' => $pcj_descuento,
                            'und' => $und,
                            'can_und' => $can_und,
                            'sw' => 16,
                            'id_bodega' => $id_bodega_default,
                            'cantidad_desp' => 0,
                            'vendedor' => 0,
                            'fecha' => $fecha_actual,
                            'cantidad2' => 0,
                            'impoconsumo' => 0.0,
                            'ibua' => 0.0,
                            'icui' => 0.0
                        ];

                        $detalle_valid = validarPedidoDetalle($detalle_raw);

                        $stmt_detalle->bind_param(
                            "iiiddddsiiisiiiddd",
                            $detalle_valid['id_pedido'], //int
                            $detalle_valid['id_referencia'], //int
                            $detalle_valid['cantidad'], //int
                            $detalle_valid['valor_unitario'], //double
                            $detalle_valid['costo_unitario'], //double
                            $detalle_valid['pcj_iva'], //double
                            $detalle_valid['pcj_descuento'], //double
                            $detalle_valid['und'], //string
                            $detalle_valid['can_und'], //int
                            $detalle_valid['sw'], //int
                            $detalle_valid['id_bodega'], //int
                            $detalle_valid['fecha'], //string
                            $detalle_valid['cantidad_desp'], //int
                            $detalle_valid['vendedor'], //int
                            $detalle_valid['cantidad2'], //int
                            $detalle_valid['impoconsumo'], //double
                            $detalle_valid['ibua'], //double
                            $detalle_valid['icui'] //double
                        );

                        if (!$stmt_detalle->execute()) {
                            throw new Exception("Error al insertar detalle: " . $stmt_detalle->error);
                        }
                    }
                }

                // Actualizar contador en t_tipos_documentos , si todo se guardo bien 
                $sql_update = "UPDATE t_tipos_documentos SET numero = ? WHERE id = ?";
                $stmt_update = $conn->prepare($sql_update);
                if ($stmt_update) {
                    $stmt_update->bind_param("ii", $numero_orden, $id_tipo_documento);
                    $stmt_update->execute();
                    $stmt_update->close();
                }

                $stmt_detalle->close();
            } else {
                // 🔹 MODO POR BODEGA: Una orden por cada bodega
                foreach ($orden as $bodegaItems) {
                    $conn->begin_transaction();

                    if (empty($bodegaItems))
                        continue;

                    // Obtener bodega del primer item
                    /* $id_bodega_orden = 0;
                     if (isset($bodegaItems[0]['id_bodega'])) {
                         $id_bodega_orden = (int) $bodegaItems[0]['id_bodega'];
                     } else if (isset($bodegaItems[0]['bodega']) && is_numeric($bodegaItems[0]['bodega'])) {
                         $id_bodega_orden = (int) $bodegaItems[0]['bodega'];
                     } else {
                         $id_bodega_orden = $id_bodega_default;
                     }

                     if ($id_bodega_orden <= 0) {
                         $id_bodega_orden = $id_bodega_default;
                     }*/
                    $id_bodega_orden = $bodegaItems[0]['bodega'];

                    $numero_orden = numeroSiguiente($id_tipo_documento, 't_pedidos');
                    $valor_total_bodega = 0.0;

                    // Calcular total por bodega
                    foreach ($bodegaItems as $item) {
                        $cantidad = isset($item['cantidad']) ? (int) $item['cantidad'] : 0;
                        $costo = isset($item['costoCompra']) ? floatval($item['costoCompra']) : 0.0;
                        $valor_total_bodega += ($cantidad * $costo);
                    }

                    $header_raw = [
                        'numero' => $numero_orden,
                        'id_usuario' => $id_usuario,
                        'id_tercero' => $id_tercero,
                        'id_bodega' => $id_bodega_orden,
                        'id_condicion' => $id_condicion,
                        'id_tercero_dir' => $id_tercero_dir,
                        'id_tipo_documento' => $id_tipo_documento,
                        'estado' => 'P', // drafts usually 'D'
                        'valor_total' => $valor_total_bodega,
                        'sw' => 16,
                        'iva' => 0.0,
                        'descuento' => 0.0,
                        'zona_ventas' => '',
                        'zona_descripcion' => '',
                        'vendedor' => 0,
                        'estado_edi' => '',
                        'respuesta_edi' => '',
                        'fecha_edi' => null,
                        'notas' => 'Sugerido por bodega',
                        'fecha_hora' => $fecha_actual,
                        'lista_precios' => 0
                    ];

                    $header_valid = validarPedidoHeader($header_raw);

                    $sql_pedido = "INSERT INTO t_pedidos (
                        numero, 
                        id_usuario, 
                        id_tercero, 
                        id_bodega, 
                        id_condicion, 
                        id_tercero_dir,
                        id_tipo_documento, 
                        estado, 
                        valor_total, 
                        sw, 
                        iva, 
                        descuento,
                        zona_ventas, 
                        zona_descripcion, 
                        vendedor, 
                        estado_edi, 
                        respuesta_edi, 
                        fecha_edi,
                        notas, 
                        fecha_hora, 
                        lista_precios
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                    $stmt_pedido = $conn->prepare($sql_pedido);
                    if (!$stmt_pedido) {
                        throw new Exception("Error preparando insert pedido: " . $conn->error);
                    }

                    $stmt_pedido->bind_param(
                        "iiiiiiisdiddssisssssi",
                        $header_valid['numero'], //i
                        $header_valid['id_usuario'], //i
                        $header_valid['id_tercero'], //i
                        $header_valid['id_bodega'], //i
                        $header_valid['id_condicion'], //i
                        $header_valid['id_tercero_dir'], //i
                        $header_valid['id_tipo_documento'], //i
                        $header_valid['estado'], //s
                        $header_valid['valor_total'], //d
                        $header_valid['sw'], //i
                        $header_valid['iva'], //d
                        $header_valid['descuento'], //d
                        $header_valid['zona_ventas'], //s
                        $header_valid['zona_descripcion'], //s
                        $header_valid['vendedor'], //i
                        $header_valid['estado_edi'], //s
                        $header_valid['respuesta_edi'], //s
                        $header_valid['fecha_edi'], //s
                        $header_valid['notas'], //s
                        $header_valid['fecha_hora'], //s
                        $header_valid['lista_precios'] // i
                    );

                    if (!$stmt_pedido->execute()) {
                        throw new Exception("Error al insertar pedido: " . $stmt_pedido->error);
                    }

                    $id_pedido = $conn->insert_id;
                    $ordenes_creadas[] = $numero_orden;
                    $stmt_pedido->close();

                    // Actualizar contador en t_tipos_documentos
                    $sql_update = "UPDATE t_tipos_documentos SET numero = ? WHERE id = ?";
                    $stmt_update = $conn->prepare($sql_update);
                    if ($stmt_update) {
                        $stmt_update->bind_param("ii", $numero_orden, $id_tipo_documento);
                        $stmt_update->execute();
                        $stmt_update->close();
                    }


                    // Insertar detalles por bodega
                    $sql_detalle = "INSERT INTO t_pedidos_detalle (
                        id_pedido, 
                        id_referencia, 
                        cantidad, 
                        valor_unitario, 
                        costo_unitario,
                        pcj_iva, 
                        pcj_descuento, 
                        und, 
                        can_und, 
                        sw, 
                        id_bodega,
                        fecha, 
                        cantidad_desp, 
                        vendedor, 
                        cantidad2, 
                        impoconsumo, 
                        ibua, 
                        icui
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                    $stmt_detalle = $conn->prepare($sql_detalle);
                    if (!$stmt_detalle) {
                        throw new Exception("Error preparando insert detalle: " . $conn->error);
                    }

                    foreach ($bodegaItems as $item) {
                        $id_referencia = isset($item['id_referencia']) ? (int) $item['id_referencia'] : 0;

                        // Si no tiene id_referencia, buscar por código
                        if ($id_referencia <= 0 && isset($item['codigo'])) {
                            $codigo = trim($item['codigo']);
                            $sql_buscar = "SELECT id FROM t_referencias WHERE codigo = ? LIMIT 1";
                            $stmt_buscar = $conn->prepare($sql_buscar);
                            if ($stmt_buscar) {
                                $stmt_buscar->bind_param("s", $codigo);
                                $stmt_buscar->execute();
                                $result_buscar = $stmt_buscar->get_result();
                                if ($row_buscar = $result_buscar->fetch_assoc()) {
                                    $id_referencia = (int) $row_buscar['id'];
                                }
                                $stmt_buscar->close();
                            }
                        }

                        if ($id_referencia <= 0) {
                            throw new Exception("No se encontró referencia para código: " . $item['codigo']);
                        }

                        $cantidad = isset($item['cantidad']) ? (int) $item['cantidad'] : 0;
                        $valor_unitario = isset($item['costoCompra']) ? floatval($item['costoCompra']) : 0.0;
                        $pcj_iva = isset($item['pcj_iva']) ? floatval($item['pcj_iva']) : 0.0;
                        $pcj_descuento = isset($item['pcj_descuento']) ? floatval($item['pcj_descuento']) : 0.0;
                        $und = isset($item['und']) ? $item['und'] : 'UND';
                        $can_und = isset($item['can_und']) ? (int) $item['can_und'] : 1;
                        $sw = 16;
                        $detalle_raw = [
                            'id_pedido' => $id_pedido,
                            'id_referencia' => $id_referencia,
                            'cantidad' => $cantidad,
                            'valor_unitario' => $valor_unitario,
                            'costo_unitario' => $valor_unitario,
                            'pcj_iva' => $pcj_iva,
                            'pcj_descuento' => $pcj_descuento,
                            'und' => $und,
                            'can_und' => $can_und,
                            'sw' => $sw,
                            'id_bodega' => $item["bodega"],
                            'cantidad_desp' => 0,
                            'vendedor' => 0,
                            'fecha' => $fecha_actual,
                            'cantidad2' => 0,
                            'impoconsumo' => 0.0,
                            'ibua' => 0.0,
                            'icui' => 0.0
                        ];

                        $detalle_valid = validarPedidoDetalle($detalle_raw);

                        // print_r($detalle_valid);  

                        $stmt_detalle->bind_param(
                            "iiiddddsiiisiiiddd",
                            $detalle_valid['id_pedido'], //int
                            $detalle_valid['id_referencia'], //int
                            $detalle_valid['cantidad'], //int
                            $detalle_valid['valor_unitario'], //double
                            $detalle_valid['costo_unitario'], //double
                            $detalle_valid['pcj_iva'], //double
                            $detalle_valid['pcj_descuento'], //double
                            $detalle_valid['und'], //str
                            $detalle_valid['can_und'], //int
                            $detalle_valid['sw'], //int
                            $detalle_valid['id_bodega'], //int
                            $detalle_valid['fecha'], //str
                            $detalle_valid['cantidad_desp'], //int
                            $detalle_valid['vendedor'], //int
                            $detalle_valid['cantidad2'], //int
                            $detalle_valid['impoconsumo'], //double
                            $detalle_valid['ibua'], //double
                            $detalle_valid['icui'] //double 
                        );

                        if (!$stmt_detalle->execute()) {
                            throw new Exception("Error al insertar detalle: " . $stmt_detalle->error);
                        }
                    }

                    $stmt_detalle->close();
                    $conn->commit();
                } // 
            }

            // Confirmar transacción


            echo json_encode([
                'ok' => true,
                'message' => count($ordenes_creadas) . ' orden(es) guardada(s) exitosamente',
                'ordenes_creadas' => $ordenes_creadas,
                'numero_orden' => count($ordenes_creadas) === 1 ? $ordenes_creadas[0] : null
            ]);
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $conn->rollback();

            echo json_encode([
                'ok' => false,
                'error' => 'Error al guardar la orden',
                'details' => $e->getMessage()
            ]);
        }
        break;
    case "getInfRef":
        $codigo = $_POST["codigo"];
        $sql = "SELECT 
               ifnull(r.descripcion,'')  as producto ,
               ifnull(m.descripcion,'')  as laboratorio,
               case when r.und_compra  =1 then r.und_1 
                    when r.und_compra  =2 then r.und_2
                    when r.und_compra  =3 then r.und_3
                end as unidad,
                case when r.und_compra =1 then r.cant_1 
                     when r.und_compra  =2 then r.cant_2
                     when r.und_compra  =3 then r.cant_3
                end as conversion
            FROM t_referencias r 
            LEFT JOIN t_marcas m on m.id = r.id_marca
            WHERE r.codigo = '$codigo'";

        $data = GenerarArray($sql);
        echo json_encode($data);

        break;
}
