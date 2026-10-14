<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

session_start();

try {
    require_once('bd.php');
    require_once('funciones.php');
    
    // Obtener operación
    $op = isset($_POST['op']) ? trim($_POST['op']) : (isset($_GET['op']) ? trim($_GET['op']) : '');

    if (empty($op)) {
        throw new Exception('Operación no especificada');
    }

    // Ejecutar operación
    switch ($op) {
        case 'getBodegas':
            getBodegas();
            break;
        case 'getCategorias':
            getCategorias();
            break;
        case 'getMarcas':
            getMarcas();
            break;
        case 'getSugeridoCompras':
            getSugeridoCompras();
            break;
        default:
            throw new Exception('Operación no válida: ' . $op);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
    exit;
} catch (Throwable $e) {
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
    exit;
}

/**
 * Obtener todas las bodegas disponibles
 */
function getBodegas() {
    try {
        $sql = "SELECT id, descripcion, almacen FROM t_almacenes 
                WHERE inactivo = 0 
                ORDER BY descripcion";
        
        $bodegas = GenerarArray($sql);
        
        if ($bodegas === false) {
            $bodegas = [];
        }
        
        // Renombrar campo almacen a bodega para compatibilidad con frontend
        $bodegasFormato = [];
        if (is_array($bodegas)) {
            foreach ($bodegas as $bodega) {
                $bodegasFormato[] = [
                    'id' => $bodega['id'],
                    'bodega' => $bodega['almacen'],
                    'descripcion' => $bodega['descripcion']
                ];
            }
        }
        
        echo json_encode([
            'error' => false,
            'bodegas' => $bodegasFormato
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'error' => true,
            'bodegas' => [],
            'message' => 'Error al consultar bodegas: ' . $e->getMessage()
        ]);
    }
}

/**
 * Obtener categorías (1, 2, 3, 4)
 */
function getCategorias() {
    $categorias = [
        'categoria1' => [],
        'categoria2' => [],
        'categoria3' => [],
        'categoria4' => []
    ];
    
    try {
        // Obtener Categoría 1
        $sql1 = "SELECT id, codigo, descripcion FROM t_referencias_categoria1 ORDER BY descripcion";
        $cat1 = GenerarArray($sql1);
        if ($cat1 !== false && is_array($cat1)) {
            $categorias['categoria1'] = $cat1;
        }
        
        // Obtener Categoría 2
        $sql2 = "SELECT id, codigo, descripcion FROM t_referencias_categoria2 ORDER BY descripcion";
        $cat2 = GenerarArray($sql2);
        if ($cat2 !== false && is_array($cat2)) {
            $categorias['categoria2'] = $cat2;
        }
        
        // Obtener Categoría 3
        $sql3 = "SELECT id, codigo, descripcion FROM t_referencias_categoria3 ORDER BY descripcion";
        $cat3 = GenerarArray($sql3);
        if ($cat3 !== false && is_array($cat3)) {
            $categorias['categoria3'] = $cat3;
        }
        
        // Obtener Categoría 4
        $sql4 = "SELECT id, codigo, descripcion FROM t_referencias_categoria4 ORDER BY descripcion";
        $cat4 = GenerarArray($sql4);
        if ($cat4 !== false && is_array($cat4)) {
            $categorias['categoria4'] = $cat4;
        }
        
        echo json_encode([
            'error' => false,
            'categoria1' => $categorias['categoria1'],
            'categoria2' => $categorias['categoria2'],
            'categoria3' => $categorias['categoria3'],
            'categoria4' => $categorias['categoria4']
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'error' => true,
            'category1' => [],
            'categoria2' => [],
            'categoria3' => [],
            'categoria4' => [],
            'message' => 'Error al consultar categorías: ' . $e->getMessage()
        ]);
    }
}

/**
 * Obtener marcas disponibles
 */
function getMarcas() {
    try {
        $sql = "SELECT DISTINCT id, descripcion FROM t_marcas 
                WHERE inactivo = 0 
                ORDER BY descripcion";
        
        $marcas = GenerarArray($sql);
        
        if ($marcas === false) {
            $marcas = [];
        }
        
        // Renombrar campos para compatibilidad con frontend
        $marcasFormato = [];
        if (is_array($marcas)) {
            foreach ($marcas as $marca) {
                $marcasFormato[] = [
                    'id_marca' => $marca['id'],
                    'nombre_marca' => $marca['descripcion']
                ];
            }
        }
        
        echo json_encode([
            'error' => false,
            'marcas' => $marcasFormato
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'error' => true,
            'marcas' => [],
            'message' => 'Error al consultar marcas: ' . $e->getMessage()
        ]);
    }
}

/**
 * Generar sugerido de compras basado en los filtros
 * USA v_rotacion_referencias y PIVOT dinámico por mes
 */
function getSugeridoCompras() {
    try {
        $id_empresa = isset($_SESSION['Ses_empresa']) ? $_SESSION['Ses_empresa'] : '';
        
        if (empty($id_empresa)) {
            throw new Exception('Empresa no identificada en sesión');
        }
        
        // Obtener parámetros
        $bodegas = isset($_POST['bodegas']) ? json_decode($_POST['bodegas'], true) : [];
        $marca = isset($_POST['marca']) && $_POST['marca'] !== '' ? $_POST['marca'] : null;
        $meses = isset($_POST['meses']) ? (int)$_POST['meses'] : 3;
        $dias = isset($_POST['dias']) ? (int)$_POST['dias'] : 30;
        $categoria1 = isset($_POST['categoria1']) && $_POST['categoria1'] !== '' ? $_POST['categoria1'] : null;
        $categoria2 = isset($_POST['categoria2']) && $_POST['categoria2'] !== '' ? $_POST['categoria2'] : null;
        $categoria3 = isset($_POST['categoria3']) && $_POST['categoria3'] !== '' ? $_POST['categoria3'] : null;
        $categoria4 = isset($_POST['categoria4']) && $_POST['categoria4'] !== '' ? $_POST['categoria4'] : null;
        
        // Validación
        if (empty($bodegas) || !is_array($bodegas)) {
            throw new Exception('Debe seleccionar al menos una bodega');
        }
        
        // Construir lista de bodegas (escapar para prevenir inyecciones)
        $bodegasEscape = array_map(function($b) { return "'" . addslashes($b) . "'"; }, $bodegas);
        $bodegasList = implode(',', $bodegasEscape);
        
        // Calcular días del período
        $dias_periodo = $meses * 30; // Aproximación: 30 días por mes
        
        // GENERAR ETIQUETAS DE MESES - Para armar pivots dinámicos
        $mes_labels = []; // Almacenar info de meses para el frontend
        
        for ($i = 0; $i < $meses; $i++) {
            $date_back = new DateTime();
            $date_back->modify("-$i months");
            $year_mes = (int)$date_back->format('Y');
            $mes_num = (int)$date_back->format('m');
            $mes_label = strtolower($date_back->format('F')); // enero, febrero, etc
            
            $mes_labels[] = [
                'mes_num' => $mes_num,
                'ano' => $year_mes,
                'label' => $mes_label,
                'codigo' => $year_mes . '-' . str_pad($mes_num, 2, '0', STR_PAD_LEFT)
            ];
        }
        
        // ========== CONSULTA 1: Stock e información base del producto ==========
        $sql_base = "
            SELECT 
                r.id as id_referencia,
                r.codigo as codigo_referencia,
                r.descripcion as descripcion_referencia,
                r.id_marca,
                COALESCE(m.descripcion, '-') as nombre_marca,
                b.id as id_bodega,
                b.descripcion as nombre_bodega,
                COALESCE(r.costo_unitario, 0) as costo_unitario,
                COALESCE(r.id_categoria_1, 0) as id_categoria1,
                COALESCE(r.id_categoria_2, 0) as id_categoria2,
                COALESCE(r.id_categoria_3, 0) as id_categoria3,
                COALESCE(r.id_categoria_4, 0) as id_categoria4,
                COALESCE(c1.descripcion, '-') as categoria,
                COALESCE(SUM(CASE WHEN t.tipo IN ('E', 'T') THEN td.cantidad ELSE -td.cantidad END), 0) as existencia
            FROM 
                t_referencias r
            LEFT JOIN t_marcas m ON r.id_marca = m.id
            LEFT JOIN t_referencias_categoria1 c1 ON r.id_categoria_1 = c1.id
            CROSS JOIN t_almacenes b
            LEFT JOIN t_transacciones_detalle td ON r.id = td.id_referencia AND b.id = td.id_bodega
            LEFT JOIN t_transacciones t ON td.id_transaccion = t.id 
            WHERE 
                b.id IN ($bodegasList)
                AND b.inactivo = 0
                AND r.activo = 1
            GROUP BY r.id, b.id
        ";
        
        // Agregar filtros opcionales
        if ($marca !== null) {
            $sql_base .= " AND r.id_marca = '" . addslashes($marca) . "'";
        }
        
        if ($categoria1 !== null) {
            $sql_base .= " AND r.id_categoria_1 = '" . addslashes($categoria1) . "'";
        }
        
        if ($categoria2 !== null) {
            $sql_base .= " AND r.id_categoria_2 = '" . addslashes($categoria2) . "'";
        }
        
        if ($categoria3 !== null) {
            $sql_base .= " AND r.id_categoria_3 = '" . addslashes($categoria3) . "'";
        }
        
        if ($categoria4 !== null) {
            $sql_base .= " AND r.id_categoria_4 = '" . addslashes($categoria4) . "'";
        }
        
        // ========== CONSULTA 2: Rotación total de los últimos N meses DESDE v_rotacion_referencias ==========
        $sql_rotacion_total = "
            SELECT 
                vr.id_referencia,
                vr.id_bodega,
                SUM(vr.rotacion) as rotacion_total
            FROM v_rotacion_referencias vr
            WHERE 
                vr.id_bodega IN ($bodegasList)
                AND vr.fec >= DATE_SUB(NOW(), INTERVAL $meses MONTH)
            GROUP BY vr.id_referencia, vr.id_bodega
            HAVING rotacion_total > 0
        ";
        
        // ========== CONSULTA 3: Rotación MES A MES para el PIVOT ==========
        $sql_rotacion_por_mes = "
            SELECT 
                vr.id_referencia,
                vr.id_bodega,
                YEAR(vr.fec) as ano,
                MONTH(vr.fec) as mes,
                SUM(vr.rotacion) as total_mes
            FROM v_rotacion_referencias vr
            WHERE 
                vr.id_bodega IN ($bodegasList)
                AND vr.fec >= DATE_SUB(NOW(), INTERVAL $meses MONTH)
            GROUP BY vr.id_referencia, vr.id_bodega, YEAR(vr.fec), MONTH(vr.fec)
        ";
        
        // Ejecutar consultas
        $base_raw = GenerarArray($sql_base);
        $rotacion_total_raw = GenerarArray($sql_rotacion_total);
        $rotacion_por_mes_raw = GenerarArray($sql_rotacion_por_mes);
        
        // Indexar rotación total
        $rotacion_total_index = [];
        if ($rotacion_total_raw !== false && is_array($rotacion_total_raw)) {
            foreach ($rotacion_total_raw as $rot) {
                $key = $rot['id_referencia'] . '-' . $rot['id_bodega'];
                $rotacion_total_index[$key] = (int)$rot['rotacion_total'];
            }
        }
        
        // Indexar rotación por mes
        $rotacion_mes_index = [];
        if ($rotacion_por_mes_raw !== false && is_array($rotacion_por_mes_raw)) {
            foreach ($rotacion_por_mes_raw as $rot) {
                $key = $rot['id_referencia'] . '-' . $rot['id_bodega'] . '-' . $rot['ano'] . '-' . $rot['mes'];
                $rotacion_mes_index[$key] = (int)$rot['total_mes'];
            }
        }
        
        $resultados = [];
        $resumen = [
            'total_productos' => 0,
            'cantidad_total' => 0,
            'valor_total' => 0
        ];
        
        // Procesar datos base con rotaciones
        if ($base_raw !== false && is_array($base_raw)) {
            foreach ($base_raw as $row) {
                $key_rot = $row['id_referencia'] . '-' . $row['id_bodega'];
                
                // Verificar que tenga rotación en el período
                if (!isset($rotacion_total_index[$key_rot])) {
                    continue; // Skip si no tiene rotación
                }
                
                $rotacion_total = $rotacion_total_index[$key_rot];
                
                // Rotación diaria dinámica = total vendido / días en el período
                $rotacion_diaria = $rotacion_total / $dias_periodo;
                // Rotación anualizada dinámica
                $rotacion_anualizada = $rotacion_diaria * 365;
                
                // LÓGICA INTELIGENTE: Solo sugerir compra si la rotación promedio diaria es > 0.5
                $cantidad_sugerida = 0;
                $sugerir_compra = false;
                
                if ($rotacion_diaria > 0.5) {
                    // Cantidad para los días especificados
                    $cantidad_sugerida = ceil($rotacion_diaria * $dias);
                    $sugerir_compra = true;
                }
                
                $valor_sugerido = $cantidad_sugerida * $row['costo_unitario'];
                
                // Construir fila con todas las columnas de meses
                $fila = [
                    'id_referencia' => $row['id_referencia'],
                    'id_bodega' => $row['id_bodega'],
                    'codigo_referencia' => $row['codigo_referencia'],
                    'descripcion_referencia' => $row['descripcion_referencia'],
                    'nombre_bodega' => $row['nombre_bodega'],
                    'nombre_marca' => $row['nombre_marca'],
                    'categoria' => $row['categoria'],
                    'existencia' => (int)$row['existencia'],
                    'costo_unitario' => (float)$row['costo_unitario']
                ];
                
                // AGREGAR ROTACIÓN PARA CADA MES - Buscando en el índice
                foreach ($mes_labels as $idx => $mes_info) {
                    $col_key = 'mes_' . str_pad($idx, 2, '0', STR_PAD_LEFT) . '_' . $mes_info['label'];
                    $search_key = $row['id_referencia'] . '-' . $row['id_bodega'] . '-' . $mes_info['ano'] . '-' . $mes_info['mes_num'];
                    $fila[$col_key] = isset($rotacion_mes_index[$search_key]) ? $rotacion_mes_index[$search_key] : 0;
                }
                
                // Agregar resumen
                $fila['rotacion_total'] = $rotacion_total;
                $fila['rotacion_diaria'] = round($rotacion_diaria, 4);
                $fila['rotacion_anualizada'] = round($rotacion_anualizada, 0);
                $fila['cantidad_sugerida'] = (int)$cantidad_sugerida;
                $fila['valor_sugerido'] = (float)$valor_sugerido;
                $fila['sugerir_compra'] = $sugerir_compra ? 'SÍ' : 'NO';
                
                $resultados[] = $fila;
                
                if ($sugerir_compra) {
                    $resumen['total_productos']++;
                    $resumen['cantidad_total'] += $cantidad_sugerida;
                    $resumen['valor_total'] += $valor_sugerido;
                }
            }
        }
        
        // Ordenar resultados por rotación total descendente
        usort($resultados, function($a, $b) {
            return $b['rotacion_total'] <=> $a['rotacion_total'];
        });
        
        // Agregar información dinámica del análisis
        $resumen['meses_evaluados'] = $meses;
        $resumen['dias_periodo'] = $dias_periodo;
        $resumen['dias_sugerencia'] = $dias;
        $resumen['mes_labels'] = $mes_labels; // Enviar etiquetas de meses al frontend
        
        echo json_encode([
            'error' => false,
            'resultados' => $resultados,
            'resumen' => $resumen
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'error' => true,
            'resultados' => [],
            'resumen' => [],
            'message' => 'Error al generar sugerido: ' . $e->getMessage()
        ]);
    }
}

?>
