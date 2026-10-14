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

switch ($_POST["op"]) {
    case 'consultar':
        $dias_rot = intval($_POST['dias_rot']) * 30;
        $dias_inv = intval($_POST['dias_inv']);
        $id_bodega_destino = intval($_POST["id_bodega_destino"]);
        $id_bodega_origen = intval($_POST["id_bodega_origen"]);
        $id_usuario = $_SESSION["Ses_id"];

        $sql = "WITH BaseCalculations AS (
                SELECT 
                    a.descripcion AS bodega,
                    r.codigo,
                    r.descripcion AS producto,
                    IFNULL(m.descripcion,'SIN ASIGNAR') AS marca,
                    s.stock AS stock_destino,
                    IFNULL(s_orig.stock, 0) AS stock_origen,
                    -- Rotación y stock mínimo
                    ROUND(IFNULL(SUM(d.cantidad), 0) / $dias_rot, 2) AS rotacion_diaria_prom,
                    (ROUND(IFNULL(SUM(d.cantidad), 0) / $dias_rot, 0)) * $dias_inv AS stock_min,
                    MAX(IFNULL(d.valor_unitario, 0)) AS precio_prom,
                    IFNULL(v.promedio, 0) AS costo_promedio,
                    r.und_1 AS und,
                    r.id AS id_referencia
                FROM v_stock_total s
                INNER JOIN t_referencias r ON r.id = s.id_referencia
                INNER JOIN t_almacenes a ON a.id = s.id_bodega
                LEFT JOIN v_costo_promedio v ON v.id_referencia = r.id 
                     AND v.mes = MONTH(NOW()) 
                     AND v.ano = YEAR(NOW())
                LEFT JOIN t_marcas m ON m.id = r.id_marca 
                -- Stock de la bodega de ORIGEN
                LEFT JOIN v_stock_total s_orig ON s_orig.id_referencia = s.id_referencia 
                    AND s_orig.id_bodega = $id_bodega_origen 
                    AND s_orig.ano = YEAR(NOW()) 
                    AND s_orig.mes = MONTH(NOW())
                -- Transacciones de la bodega DESTINO
                LEFT JOIN t_transacciones_detalle d ON d.id_referencia = s.id_referencia 
                    AND d.id_bodega = s.id_bodega 
                    AND d.fecha_hora >= DATE_SUB(NOW(), INTERVAL $dias_rot DAY) 
                    AND d.sw = 5
                WHERE s.ano = YEAR(NOW()) 
                  AND s.mes = MONTH(NOW())
                  AND s.id_bodega = $id_bodega_destino
                GROUP BY a.descripcion, r.codigo, r.descripcion, m.descripcion, s.stock, s_orig.stock, v.promedio, r.und_1, r.id
            ),
            Calculated AS (
                SELECT 
                    bodega AS bodega_destino,
                    codigo,
                    producto,
                    marca,
                    rotacion_diaria_prom,
                    stock_min,
                    stock_destino AS stock_actual,
                    stock_origen,
                    CASE 
                        WHEN rotacion_diaria_prom > 0 THEN ROUND(stock_destino / rotacion_diaria_prom, 2)
                        ELSE 9999 
                    END AS stock_dias_inventario,     
                    CASE 
                        WHEN rotacion_diaria_prom > 0 THEN ROUND((stock_destino - stock_min) / rotacion_diaria_prom, 2)
                        ELSE 9999 
                    END AS dias_restantes_inventario,     
                    -- Faltante total para llegar al stock mínimo
                    CASE 
                        WHEN stock_destino < stock_min THEN stock_min - stock_destino 
                        ELSE 0 
                    END AS faltante_necesario,     
                    precio_prom,
                    costo_promedio,   
                    und,
                    id_referencia,
                    'Comprar' AS estado
                FROM BaseCalculations
            )
            SELECT 
                bodega_destino,
                codigo,
                producto,
                marca,
                rotacion_diaria_prom,
                stock_min,
                stock_actual,
                stock_origen,
                stock_dias_inventario,
                dias_restantes_inventario,
                faltante_necesario,
                -- 1. Sugerencia de traslado basada en la disponibilidad del origen
                LEAST(faltante_necesario, stock_origen) AS sugerido_traslado,
                -- 2. Lo que aún queda por comprar si el origen no alcanza
                GREATEST(faltante_necesario - stock_origen, 0) AS comprar_restante,
                precio_prom,
                costo_promedio,
                -- 3. Subtotal basado en el costo promedio de compra
                (GREATEST(faltante_necesario - stock_origen, 0) * costo_promedio) AS sub_total_comprar,
                estado,
                id_referencia,
                und
            FROM Calculated
            WHERE stock_actual < stock_min;";

        $resultado = GenerarArray($sql);

        echo json_encode($resultado);
        break;

    case 'procesar_traslados':
        try {
            // Opcional pero recomendado: Iniciar transacción de base de datos si tu gestor lo soporta
            // mysqli_begin_transaction($conn);

            $id_bodega_origen = intval($_POST['id_bodega_origen'] ?? 0);
            $id_bodega_destino = intval($_POST['id_bodega_destino'] ?? 0);
            $id_usuario = $_SESSION["Ses_id"] ?? 0;
            $productos = $_POST['productos'] ?? [];

            if (is_string($productos)) {
                $productos = json_decode($productos, true) ?? [];
            }

            $numero = numeroSiguiente(intval($_POST['id_tipo_documento'] ?? 0));
            $dataEnc = [
                'id_tipo_documento' => intval($_POST['id_tipo_documento'] ?? 0),
                'id_tercero'        => intval($_POST['id_tercero'] ?? 0),
                'id_bodega'         => intval($_POST['id_bodega'] ?? 0),
                'id_bodega_destino' => intval($_POST['id_bodega_destino'] ?? 0),
                'notas'             => $_POST['notas'] ?? '',
                'valor_total'       => 0,
                'iva'               => 0,
                'descuento'         => 0,
                'sw'                => intval($_POST['sw'] ?? 3),
                'id_tercero_dir'    => intval($_POST['id_tercero_dir'] ?? 0),
                'id_condicion'      => intval($_POST['id_condicion'] ?? 0),
                'zona_ventas'       => trim($_POST['zona_ventas'] ?? ''),
                'zona_descripcion'  => trim($_POST['zona_descripcion'] ?? ''),
                'id_usuario'        => $_SESSION["Ses_id"],
                'fecha'             => date("Y-m-d"),
                'numero'            => $numero
            ];

            $id_transaccion = insertReturnId('t_transacciones_tmp', $dataEnc, true, $conn);

            if (!$id_transaccion) {
                throw new Exception("No se pudo generar el encabezado de la transacción.");
            }

            foreach ($productos as $prod) {
                $id_referencia = intval($prod['id_referencia'] ?? 0);
                $sugerido_traslado = floatval($prod['sugerido_traslado'] ?? 0);
                $costo_promedio = floatval($prod['costo_promedio'] ?? 0);
                $precio_prom = floatval($prod['precio_prom'] ?? 0);
                $und = !empty($prod['und']) ? $prod['und'] : 'UND';

                if ($sugerido_traslado <= 0 || $id_referencia <= 0) {
                    continue;
                }

                // Consultar lotes disponibles (FEFO)
                $sql_lotes = "SELECT id_lote, lote, vencimiento, stock 
                          FROM v_referencias_stock_lotes 
                          WHERE id_bodega = $id_bodega_origen 
                            AND id_referencia = $id_referencia 
                            AND stock > 0 
                          ORDER BY vencimiento ASC";

                $lotes_disponibles = GenerarArray($sql_lotes);
                $cantidad_pendiente = $sugerido_traslado;

                foreach ($lotes_disponibles as $lote_info) {
                    if ($cantidad_pendiente <= 0) break;

                    $cantidad_a_sacar = min($cantidad_pendiente, floatval($lote_info['stock']));

                    $datosDetalle = [
                        'id_transaccion' => $id_transaccion,
                        'id_referencia'  => $id_referencia,
                        'cantidad'       => $cantidad_a_sacar,
                        'valor_unitario' => $precio_prom,
                        'costo_unitario' => $costo_promedio,
                        'id_lote'        => $lote_info['id_lote'],
                        'lote'           => $lote_info['lote'],
                        'vmcto'          => $lote_info['vencimiento'],
                        'pcj_iva'        => 0,
                        'pcj_descuento'  => 0,
                        'und'            => $und,
                        'can_und'        => 1,
                        'sw'             => 6,
                        'id_bodega'      => $id_bodega_origen,
                        'id_pedido'      => 0,
                        'impoconsumo'    => 0,
                        'ibua'           => 0,
                        'icui'           => 0
                    ];

                    $id_detalle = insertReturnId('t_transacciones_detalle_tmp', $datosDetalle, true, $conn);

                    if ($id_detalle > 0) {
                        actualizarTotalesEncabezado($id_transaccion);
                    }

                    $cantidad_pendiente -= $cantidad_a_sacar;
                }
            }

            // Consultar los datos del tercero para armar la respuesta requerida
            $id_tercero_val = $dataEnc['id_tercero'];
            $sql_ter = "SELECT 
                            td.direccion, 
                            t.nit, 
                            concat(t.primer_apellido, ' ', t.segundo_apellido, ' ', t.primer_nombre, ' ', t.segundo_nombre) as nombres,
                            t.razon_comercial,
                            t.lista_precios,
                            t.cupo_credito ,
                            t.email_fe,
                            z.codigo_zona  AS zona_ventas,
                            z.descripcion AS zona_descripcion,
                            c.id AS id_condicion_pago,
                            c.descripcion AS des_condicion
                        FROM t_terceros t
                        INNER JOIN t_terceros_direcciones td on t.id = td.id_tercero and td.principal = 1
                        INNER JOIN t_condiciones_pago c   on c.id = t.id_condicion  
                        LEFT JOIN t_zonas_ventas z ON z.id = t.id_zona_ventas
                        WHERE t.id  = $id_tercero_val 
                        LIMIT 1";
            $datater = GenerarArray($sql_ter);

            // Datos por defecto si el tercero no retorna registros
            $tercero_info = $datater[0] ?? [
                'direccion' => '',
                'nit' => '',
                'nombres' => '',
                'razon_comercial' => ''
            ];

            // Respuesta exitosa con la estructura exacta solicitada
            echo json_encode([
                'ok' => true,
                'message' => 'Carga masiva insertada correctamente',
                'id_transaccion' => $id_transaccion,
                'data' => [
                    'id_transaccion'        => $id_transaccion,
                    'id_tercero'            => $dataEnc['id_tercero'],
                    'id_tercero_dir'        => $dataEnc['id_tercero_dir'],
                    'direccion'             => $tercero_info['direccion'],
                    'nit'                   => $tercero_info['nit'],
                    'nombres'               => $tercero_info['nombres'],
                    'razon_comercial'       => $tercero_info['razon_comercial'],
                    'id_bodega'             => $dataEnc['id_bodega'],
                    'id_bodega_destino'     => $dataEnc['id_bodega_destino'],
                    'id_tipo_documento'     => $dataEnc['id_tipo_documento'],
                    'id_condicion_pago'     => $dataEnc['id_condicion'],
                    'notas'                 => $dataEnc['notas'],
                    'valor_total'           => $dataEnc['valor_total'],
                    'numero'                => $dataEnc['numero'] ?? '',
                    'documento'             => $dataEnc['documento'] ?? '',
                    'id_concepto_retencion' => $dataEnc['id_concepto_retencion'] ?? 0,
                    'lista_precios'         => $dataEnc['lista_precios'] ?? 0,
                    'cupo_credito'          => $dataEnc['cupo_credito'] ?? 0,
                    'email_fe'              => $dataEnc['email_fe'] ?? '',
                    'zona_ventas'           => $dataEnc['zona_ventas'] ?? '',
                    'zona_descripcion'      => $dataEnc['zona_descripcion'] ?? '',
                    'des_condicion'         => $dataEnc['des_condicion'] ?? ''
                ]
            ]);
        } catch (Exception $e) {
            // En caso de error, retornamos la estructura de fallo controlada
            echo json_encode([
                'ok' => false,
                'message' => 'Error al procesar los traslados: ' . $e->getMessage()
            ]);
        }
        break;
}
