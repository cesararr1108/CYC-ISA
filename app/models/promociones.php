<?php

session_start();
require_once('bd.php');
require_once('funciones.php');
$conn = conectar();

switch ($_POST["op"]) {
    // ========================================
    // OPERACIÓN: CREAR PROMOCIÓN
    // ========================================
    case "crear-promocion":
        try {
            $nombre = $_POST["nombre"] ?? '';
            $descripcion = $_POST["descripcion"] ?? '';
            $id_bodega = intval($_POST["id_bodega"] ?? 0);
            $id_lista_precios = intval($_POST["id_lista_precios"] ?? 0);
            $porcentaje = floatval($_POST["porcentaje_descuento"] ?? 0);
            $tipo_aplicacion = $_POST["tipo_aplicacion"] ?? '1';
            $fecha_inicio = $_POST["fecha_inicio"] ?? '';
            $fecha_final = $_POST["fecha_final"] ?? '';
            $observaciones = $_POST["observaciones"] ?? '';
            $referencias_seleccionadas = json_decode($_POST["referencias_seleccionadas"] ?? '[]', true);
            $marcas_seleccionadas = json_decode($_POST["marcas_seleccionadas"] ?? '[]', true);

            // Validaciones
            if (empty($nombre)) {
                echo json_encode(['estado' => false, 'message' => 'El nombre de la promoción es requerido']);
                exit;
            }
            if ($id_bodega < 0) {
                echo json_encode(['estado' => false, 'message' => 'Selección de bodega inválida']);
                exit;
            }
            if ($porcentaje <= 0 || $porcentaje > 100) {
                echo json_encode(['estado' => false, 'message' => 'El porcentaje debe estar entre 1 y 100']);
                exit;
            }
            if (empty($fecha_inicio) || empty($fecha_final)) {
                echo json_encode(['estado' => false, 'message' => 'Las fechas inicial y final son requeridas']);
                exit;
            }
            if ($tipo_aplicacion !== '1' && empty($referencias_seleccionadas) && empty($marcas_seleccionadas)) {
                echo json_encode(['estado' => false, 'message' => 'Debe seleccionar al menos una referencia o marca']);
                exit;
            }

            // Preparar datos para inserción
            $data = [
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'id_bodega' => $id_bodega,
                'id_lista_precios' => $id_lista_precios > 0 ? $id_lista_precios : null,
                'porcentaje_descuento' => $porcentaje,
                'tipo_aplicacion' => $tipo_aplicacion,
                'fecha_inicio' => $fecha_inicio,
                'fecha_final' => $fecha_final,
                'observaciones' => $observaciones,
                'usuario_creacion' => $_SESSION["Ses_id"]
            ];

            $id_promocion = insertReturnId('t_promociones', $data, true, $conn);

            if ($id_promocion > 0) {
                // Insertar referencias específicas
                if ($tipo_aplicacion === '2' && !empty($referencias_seleccionadas)) {
                    foreach ($referencias_seleccionadas as $id_ref) {
                        $data_ref = [
                            'id_promocion' => $id_promocion,
                            'id_referencia' => intval($id_ref)
                        ];
                        insertReturnId('t_promociones_referencias', $data_ref, true, $conn);
                    }
                }

                // Insertar marcas específicas
                if ($tipo_aplicacion === '3' && !empty($marcas_seleccionadas)) {
                    foreach ($marcas_seleccionadas as $id_marca) {
                        $data_marca = [
                            'id_promocion' => $id_promocion,
                            'id_marca' => intval($id_marca)
                        ];
                        insertReturnId('t_promociones_marcas', $data_marca, true, $conn);
                    }
                }

                echo json_encode([
                    'estado' => true,
                    'message' => 'Promoción creada correctamente',
                    'id' => $id_promocion
                ]);
            } else {
                echo json_encode(['estado' => false, 'message' => 'Error al crear la promoción']);
            }
        } catch (\Throwable $th) {
            echo json_encode(['estado' => false, 'message' => 'Error: ' . $th->getMessage()]);
        }
        break;

    // ========================================
    // OPERACIÓN: LISTAR PROMOCIONES
    // ========================================
    case "listar-promociones":
        try {
            $id_bodega = intval($_POST["id_bodega"] ?? 0);
            $fecha_desde = $_POST["fecha_desde"] ?? '';
            $fecha_hasta = $_POST["fecha_hasta"] ?? '';
            $estado = $_POST["estado"] !== '' ? intval($_POST["estado"]) : null;

            $where = "1=1";

            if ($id_bodega > 0) {
                $where .= " AND p.id_bodega = $id_bodega";
            }
            // Si id_bodega = 0, lista todas las bodegas (sin filtro)

            if (!empty($fecha_desde)) {
                $where .= " AND DATE(p.fecha_inicio) >= '$fecha_desde'";
            }

            if (!empty($fecha_hasta)) {
                $where .= " AND DATE(p.fecha_inicio) <= '$fecha_hasta'";
            }

            if ($estado !== null) {
                $where .= " AND p.estado = $estado";
            }

            $sql = "SELECT 
                        p.id,
                        UPPER(p.nombre) AS nombre,
                        p.porcentaje_descuento,
                        p.tipo_aplicacion,
                        p.fecha_inicio,
                        p.fecha_final,
                        p.estado,
                        IFNULL(p.id_lista_precios,0) AS id_lista_precios,
                        IFNULL(l.Descripcion,'Todas') AS desc_lista,
                        p.observaciones,
                        p.fecha_creacion,
                        IFNULL(alm.descripcion, 'Todas') AS bodega_desc,
                        u.nombres AS usuario_nombre,
                        COUNT(DISTINCT pr.id) AS cantidad_referencias,
                        COUNT(DISTINCT pm.id) AS cantidad_marcas
                    FROM t_promociones p
                    LEFT JOIN t_almacenes alm ON alm.id = p.id_bodega
                    LEFT JOIN t_usuarios u ON u.id = p.usuario_creacion
                    LEFT JOIN t_promociones_referencias pr ON pr.id_promocion = p.id
                    LEFT JOIN t_promociones_marcas pm ON pm.id_promocion = p.id
                    LEFT JOIN t_listas_precios l ON l.id = p.id_lista_precios 
                    WHERE $where
                    GROUP BY p.id
                    ORDER BY p.fecha_creacion DESC";

            $promociones = GenerarArray($sql);

            echo json_encode([
                'error' => false,
                'data' => $promociones,
                'message' => 'Promociones cargadas correctamente',
                'SQL' => $sql
            ]);
        } catch (\Throwable $th) {
            echo json_encode([
                'error' => true,
                'message' => 'Error: ' . $th->getMessage()
            ]);
        }
        break;

    // ========================================
    // OPERACIÓN: OBTENER DETALLE DE PROMOCIÓN
    // ========================================
    case "obtener-promocion":
        try {
            $id = intval($_POST["id"] ?? 0);

            if ($id <= 0) {
                echo json_encode(['error' => true, 'message' => 'ID inválido']);
                exit;
            }

            // Obtener datos principales
            $sql_promo = "SELECT * FROM t_promociones WHERE id = $id";
            $result_promo = GenerarArray($sql_promo);

            if (empty($result_promo)) {
                echo json_encode(['error' => true, 'message' => 'Promoción no encontrada']);
                exit;
            }

            $promocion = $result_promo[0];

            // Obtener referencias si aplica
            $referencias = [];
            if ($promocion['tipo_aplicacion'] === '2') {
                $sql_refs = "SELECT id_referencia FROM t_promociones_referencias WHERE id_promocion = $id";
                $refs = GenerarArray($sql_refs);
                $referencias = array_column($refs, 'id_referencia');
            }

            // Obtener marcas si aplica
            $marcas = [];
            if ($promocion['tipo_aplicacion'] === '3') {
                $sql_marcas = "SELECT id_marca FROM t_promociones_marcas WHERE id_promocion = $id";
                $marcas_result = GenerarArray($sql_marcas);
                $marcas = array_column($marcas_result, 'id_marca');
            }

            echo json_encode([
                'error' => false,
                'data' => $promocion,
                'referencias' => $referencias,
                'marcas' => $marcas
            ]);
        } catch (\Throwable $th) {
            echo json_encode(['error' => true, 'message' => 'Error: ' . $th->getMessage()]);
        }
        break;

    // ========================================
    // OPERACIÓN: EDITAR PROMOCIÓN
    // ========================================
    case "editar-promocion":
        try {
            $id = intval($_POST["id"] ?? 0);
            $nombre = $_POST["nombre"] ?? '';
            $descripcion = $_POST["descripcion"] ?? '';
            $porcentaje = floatval($_POST["porcentaje_descuento"] ?? 0);
            $tipo_aplicacion = $_POST["tipo_aplicacion"] ?? '1';
            $fecha_inicio = $_POST["fecha_inicio"] ?? '';
            $fecha_final = $_POST["fecha_final"] ?? '';
            $observaciones = $_POST["observaciones"] ?? '';
            $referencias_seleccionadas = json_decode($_POST["referencias_seleccionadas"] ?? '[]', true);
            $marcas_seleccionadas = json_decode($_POST["marcas_seleccionadas"] ?? '[]', true);

            if ($id <= 0) {
                echo json_encode(['estado' => false, 'message' => 'ID inválido']);
                exit;
            }

            // Validaciones
            if (empty($nombre) || $porcentaje <= 0 || empty($fecha_inicio) || empty($fecha_final)) {
                echo json_encode(['estado' => false, 'message' => 'Campos requeridos vacíos']);
                exit;
            }

            // Actualizar promoción
            $sql = "UPDATE t_promociones SET 
                        nombre = '$nombre',
                        descripcion = '$descripcion',
                        porcentaje_descuento = $porcentaje,
                        tipo_aplicacion = '$tipo_aplicacion',
                        fecha_inicio = '$fecha_inicio',
                        fecha_final = '$fecha_final',
                        observaciones = '$observaciones'
                    WHERE id = $id";

            if (Query($sql)) {
                // Limpiar referencias antiguas
                Query("DELETE FROM t_promociones_referencias WHERE id_promocion = $id");

                // Insertar nuevas referencias
                if ($tipo_aplicacion === '2' && !empty($referencias_seleccionadas)) {
                    foreach ($referencias_seleccionadas as $id_ref) {
                        $data_ref = [
                            'id_promocion' => $id,
                            'id_referencia' => intval($id_ref)
                        ];
                        insertReturnId('t_promociones_referencias', $data_ref, true, $conn);
                    }
                }

                // Limpiar marcas antiguas
                Query("DELETE FROM t_promociones_marcas WHERE id_promocion = $id");

                // Insertar nuevas marcas
                if ($tipo_aplicacion === '3' && !empty($marcas_seleccionadas)) {
                    foreach ($marcas_seleccionadas as $id_marca) {
                        $data_marca = [
                            'id_promocion' => $id,
                            'id_marca' => intval($id_marca)
                        ];
                        insertReturnId('t_promociones_marcas', $data_marca, true, $conn);
                    }
                }

                echo json_encode(['estado' => true, 'message' => 'Promoción actualizada correctamente']);
            } else {
                echo json_encode(['estado' => false, 'message' => 'Error al actualizar la promoción']);
            }
        } catch (\Throwable $th) {
            echo json_encode(['estado' => false, 'message' => 'Error: ' . $th->getMessage()]);
        }
        break;

    // ========================================
    // OPERACIÓN: ELIMINAR/INACTIVAR PROMOCIÓN
    // ========================================
    case "eliminar-promocion":
        try {
            $id = intval($_POST["id"] ?? 0);

            if ($id <= 0) {
                echo json_encode(['estado' => false, 'message' => 'ID inválido']);
                exit;
            }

            $sql = "UPDATE t_promociones SET estado = 0 WHERE id = $id";

            if (Query($sql)) {
                echo json_encode(['estado' => true, 'message' => 'Promoción inactivada correctamente']);
            } else {
                echo json_encode(['estado' => false, 'message' => 'Error al inactivar la promoción']);
            }
        } catch (\Throwable $th) {
            echo json_encode(['estado' => false, 'message' => 'Error: ' . $th->getMessage()]);
        }
        break;

    // ========================================
    // OPERACIÓN: LISTAR REFERENCIAS ACTIVAS
    // ========================================
    case "listar-referencias-activas":
        try {
            $sql = "SELECT id, codigo, descripcion 
                    FROM t_referencias 
                    WHERE activo = 1 
                    ORDER BY descripcion ASC";

            $referencias = GenerarArray($sql);

            echo json_encode([
                'error' => false,
                'data' => $referencias
            ]);
        } catch (\Throwable $th) {
            echo json_encode(['error' => true, 'message' => 'Error: ' . $th->getMessage()]);
        }
        break;

    // ========================================
    // OPERACIÓN: LISTAR MARCAS ACTIVAS
    // ========================================
    case "listar-marcas-activas":
        try {
            $sql = "SELECT id, descripcion 
                    FROM t_marcas 
                    ORDER BY descripcion ASC";

            $marcas = GenerarArray($sql);

            echo json_encode([
                'error' => false,
                'data' => $marcas
            ]);
        } catch (\Throwable $th) {
            echo json_encode(['error' => true, 'message' => 'Error: ' . $th->getMessage()]);
        }
        break;

    // ========================================
    // OPERACIÓN: OBTENER DESCUENTOS POR REFERENCIA
    // ========================================
    case "obtener-descuentos-referencia":
        try {
            $id_referencia = intval($_POST["id_referencia"] ?? 0);
            $id_bodega = intval($_POST["id_bodega"] ?? 0);

            if ($id_referencia <= 0) {
                echo json_encode(['error' => true, 'message' => 'ID de referencia inválido']);
                exit;
            }

            // Obtener referencia con marca
            $sql_ref = "SELECT id_marca FROM t_referencias WHERE id = $id_referencia LIMIT 1";
            $ref_result = GenerarArray($sql_ref);
            
            if (empty($ref_result)) {
                echo json_encode([
                    'error' => false,
                    'data' => [],
                    'descuento_total' => 0
                ]);
                exit;
            }

            $id_marca = $ref_result[0]['id_marca'];

            // Obtener descuentos activos aplicables a la referencia
            $bodega_filter = ($id_bodega > 0) ? "AND (p.id_bodega = $id_bodega OR p.id_bodega = 0)" : "AND (p.id_bodega = 0 OR p.id_bodega > 0)";

            $sql = "SELECT DISTINCT
                        p.id,
                        p.nombre,
                        p.porcentaje_descuento,
                        p.tipo_aplicacion,
                        p.fecha_inicio,
                        p.fecha_final,
                        p.observaciones,
                        alm.descripcion AS bodega_desc,
                        CASE 
                            WHEN p.tipo_aplicacion = '1' THEN 'Todas las referencias'
                            WHEN p.tipo_aplicacion = '2' THEN 'Por referencia'
                            WHEN p.tipo_aplicacion = '3' THEN 'Por marca'
                        END AS tipo_descuento
                    FROM t_promociones p
                    LEFT JOIN t_almacenes alm ON alm.id = p.id_bodega
                    LEFT JOIN t_promociones_referencias pr ON (pr.id_promocion = p.id AND pr.id_referencia = $id_referencia)
                    LEFT JOIN t_promociones_marcas pm ON (pm.id_promocion = p.id AND pm.id_marca = $id_marca)
                    WHERE 
                        p.estado = 1
                        AND DATE(p.fecha_inicio) <= CURDATE()
                        AND DATE(p.fecha_final) >= CURDATE()
                        $bodega_filter
                        AND (
                            (p.tipo_aplicacion = '1')
                            OR (p.tipo_aplicacion = '2' AND pr.id_referencia = $id_referencia)
                            OR (p.tipo_aplicacion = '3' AND pm.id_marca = $id_marca)
                        )
                    ORDER BY p.porcentaje_descuento DESC";

            $descuentos = GenerarArray($sql);

            // Calcular descuento total
            $descuento_total = 0;
            if (!empty($descuentos)) {
                foreach ($descuentos as $desc) {
                    $descuento_total += floatval($desc['porcentaje_descuento']);
                }
            }

            echo json_encode([
                'error' => false,
                'data' => $descuentos,
                'descuento_total' => $descuento_total
            ]);
        } catch (\Throwable $th) {
            echo json_encode(['error' => true, 'message' => 'Error: ' . $th->getMessage()]);
        }
        break;

    default:
        echo json_encode(['error' => true, 'message' => 'Operación no reconocida']);
        break;
}

?>
