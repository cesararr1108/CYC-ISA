<?php
session_start();
require_once('bd.php');
require_once('funciones.php');
$conn = conectar();

/*
 * Tipos de presupuesto permitidos (lista blanca).
 * AJUSTAR: tabla y columnas reales de zonas, vendedores y usuarios.
 *  - filtro: condición para mostrar solo los registros activos en la grilla
 *            (ej. "r.estado = 1"). Vacío = todos. Los que ya tengan presupuesto
 *            en el período se muestran siempre, aunque estén inactivos.
 *  - venta_campo / venta_join: cómo se relaciona cada venta (t_transacciones t) con
 *    la zona, el vendedor o el usuario; se usan en el cumplimiento del presupuesto.
 */
$TIPOS = [
    'zona'     => [
        'tabla' => 't_zonas_ventas',
        'id' => 'id',
        'nombre' => 'descripcion',
        'filtro' => '',
        'venta_join' => 'INNER JOIN t_terceros ter ON ter.id = t.id_tercero',
        'venta_campo' => 'ter.id_zona_ventas'
    ],
    'vendedor' => [
        'tabla' => 'v_vendedores',
        'id' => 'id',
        'nombre' => 'tratamiento',
        'filtro' => '',
        'venta_join' => '',
        'venta_campo' => 't.vendedor'
    ],
    'usuario'  => [
        'tabla' => 't_usuarios',
        'id' => 'id',
        'nombre' => 'usuario',
        'filtro' => '',
        'venta_join' => '',
        'venta_campo' => 't.id_usuario'
    ],
];

const MAX_ITEMS_LOTE = 5000;

/* Lee y valida año, mes, bodega y tipo. Devuelve null si algo no es válido. */
function leerPeriodo(array $TIPOS, bool $bodegaObligatoria = true): ?array
{
    $anio   = (int)($_POST["anio"] ?? 0);
    $mes    = (int)($_POST["mes"] ?? 0);
    $bodega = (int)($_POST["bodega"] ?? 0);
    $tipo   = $_POST["tipo"] ?? '';
    if ($anio < 2000 || $anio > 2100 || $mes < 1 || $mes > 12 || !isset($TIPOS[$tipo])) return null;
    if ($bodegaObligatoria && !$bodega) return null;
    return [$anio, $mes, $bodega, $tipo];
}

$op = $_POST["op"] ?? '';

switch ($op) {
    case 'bodegas':
        $sql = "SELECT id, descripcion AS nombre
                FROM t_almacenes
                ORDER BY descripcion";
        echo json_encode(GenerarArray($sql));
        break;

    /*
     * Grilla de ingreso: todas las zonas / vendedores / usuarios con su presupuesto
     * del período (null si no tiene) y el del mes anterior como referencia.
     */
    case 'grilla':
        $p = leerPeriodo($TIPOS);
        if (!$p) {
            echo json_encode([]);
            break;
        }
        [$anio, $mes, $bodega, $tipo] = $p;
        $t = $TIPOS[$tipo];

        $anioAnt = $mes === 1 ? $anio - 1 : $anio;
        $mesAnt  = $mes === 1 ? 12 : $mes - 1;
        $filtro  = $t['filtro'] !== '' ? $t['filtro'] : '1 = 1';

        $sql = "SELECT
                    r.{$t['id']} AS id_referencia,
                    r.{$t['nombre']} AS referencia,
                    p.valor,
                    pa.valor AS valor_anterior
                FROM {$t['tabla']} r
                LEFT JOIN t_presupuesto_ventas p
                    ON p.id_referencia = r.{$t['id']} AND p.tipo = '$tipo'
                    AND p.anio = $anio AND p.mes = $mes AND p.id_bodega = $bodega
                LEFT JOIN t_presupuesto_ventas pa
                    ON pa.id_referencia = r.{$t['id']} AND pa.tipo = '$tipo'
                    AND pa.anio = $anioAnt AND pa.mes = $mesAnt AND pa.id_bodega = $bodega
                WHERE ($filtro) OR p.id IS NOT NULL
                ORDER BY r.{$t['nombre']}";
        echo json_encode(GenerarArray($sql));
        break;

    /* Valores registrados en un período (para "Traer valores de otro mes") */
    case 'valores':
        $p = leerPeriodo($TIPOS);
        if (!$p) {
            echo json_encode([]);
            break;
        }
        [$anio, $mes, $bodega, $tipo] = $p;

        $sql = "SELECT id_referencia, valor
                FROM t_presupuesto_ventas
                WHERE anio = $anio AND mes = $mes AND id_bodega = $bodega AND tipo = '$tipo'";
        echo json_encode(GenerarArray($sql));
        break;

    /*
     * Guarda en bloque los cambios de la grilla (escritos a mano o cargados desde archivo).
     * items: JSON [{ id, valor }]. valor null = quitar el presupuesto de ese registro.
     * Todo se hace en una transacción: o se guarda todo o no se guarda nada.
     */
    case 'guardar_lote':
        $id_usuario = (int)($_SESSION["Ses_id"] ?? 0);
        $p = leerPeriodo($TIPOS);
        $items = json_decode($_POST["items"] ?? '', true);

        if (!$p || !$id_usuario || !is_array($items) || !$items) {
            echo json_encode(['ok' => false, 'msg' => 'Datos incompletos']);
            break;
        }
        if (count($items) > MAX_ITEMS_LOTE) {
            echo json_encode(['ok' => false, 'msg' => 'Se permiten máximo ' . MAX_ITEMS_LOTE . ' registros por guardado']);
            break;
        }
        [$anio, $mes, $bodega, $tipo] = $p;
        $t = $TIPOS[$tipo];

        // Validación de cada fila
        $limpios = [];
        foreach ($items as $i => $it) {
            $id = (int)($it['id'] ?? 0);
            $valor = $it['valor'] ?? null;
            if ($id <= 0) {
                echo json_encode(['ok' => false, 'msg' => 'Fila ' . ($i + 1) . ': código inválido']);
                break 2;
            }
            if ($valor !== null && (!is_numeric($valor) || (float)$valor < 0)) {
                echo json_encode(['ok' => false, 'msg' => "Código $id: el valor debe ser mayor o igual a cero"]);
                break 2;
            }
            $limpios[$id] = $valor === null ? null : round((float)$valor, 2); // si se repite, queda el último
        }

        // Los códigos deben existir en la tabla del tipo (zonas, vendedores o usuarios)
        $lista = implode(',', array_keys($limpios));
        $existentes = array_map('intval', array_column(
            GenerarArray("SELECT {$t['id']} AS id FROM {$t['tabla']} WHERE {$t['id']} IN ($lista)") ?: [],
            'id'
        ));
        $faltantes = array_diff(array_keys($limpios), $existentes);
        if ($faltantes) {
            echo json_encode([
                'ok' => false,
                'msg' => 'No existen los códigos: ' . implode(', ', array_slice($faltantes, 0, 15)) . (count($faltantes) > 15 ? '...' : '')
            ]);
            break;
        }

        $guardados = 0;
        $quitados = 0;
        try {
            $conn->begin_transaction();

            $stUp = $conn->prepare("INSERT INTO t_presupuesto_ventas
                        (anio, mes, id_bodega, tipo, id_referencia, valor, id_usuario, fecha_registro)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                        valor = VALUES(valor),
                        id_usuario = VALUES(id_usuario),
                        fecha_registro = NOW()");
            $stDel = $conn->prepare("DELETE FROM t_presupuesto_ventas
                    WHERE anio = ? AND mes = ? AND id_bodega = ? AND tipo = ? AND id_referencia = ?");

            $ref = 0;
            $valor = 0.0;
            $stUp->bind_param('iiisidi', $anio, $mes, $bodega, $tipo, $ref, $valor, $id_usuario);
            $stDel->bind_param('iiisi', $anio, $mes, $bodega, $tipo, $ref);

            foreach ($limpios as $ref => $v) {
                if ($v === null) {
                    if (!$stDel->execute()) throw new Exception($stDel->error);
                    $quitados += $stDel->affected_rows > 0 ? 1 : 0;
                } else {
                    $valor = $v;
                    if (!$stUp->execute()) throw new Exception($stUp->error);
                    $guardados++;
                }
            }
            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            echo json_encode(['ok' => false, 'msg' => 'No se pudo guardar: ' . $e->getMessage()]);
            break;
        }

        $partes = [];
        if ($guardados) $partes[] = "$guardados guardado(s)";
        if ($quitados)  $partes[] = "$quitados quitado(s)";
        echo json_encode(['ok' => true, 'msg' => 'Presupuesto actualizado: ' . ($partes ? implode(', ', $partes) : 'sin cambios')]);
        break;

    case 'cumplimiento':
        $p = leerPeriodo($TIPOS, false); // bodega 0 = todas las bodegas
        if (!$p) {
            echo json_encode([]);
            break;
        }
        [$anio, $mes, $bodega, $tipo] = $p;
        $t = $TIPOS[$tipo];
        $filtroBodegaP = $bodega ? "AND id_bodega = $bodega" : "";
        $filtroBodegaV = $bodega ? "AND d.id_bodega = $bodega" : "";

        // Venta neta del mes (ventas sw 5 menos devoluciones sw 4, sin IVA), como en el dashboard
        $sql = "SELECT
                    p.id_referencia,
                    r.{$t['nombre']} AS referencia,
                    p.valor AS presupuesto,
                    IFNULL(v.venta, 0) AS venta,
                    IFNULL(v.venta, 0) - p.valor AS diferencia,
                    IF(p.valor > 0, ROUND(IFNULL(v.venta, 0) / p.valor * 100, 1), NULL) AS cumplimiento
                FROM (
                    SELECT id_referencia, SUM(valor) AS valor
                    FROM t_presupuesto_ventas
                    WHERE anio = $anio AND mes = $mes AND tipo = '$tipo' $filtroBodegaP
                    GROUP BY id_referencia
                ) p
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
                        d.fec >= '$anio-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01' AND
                        d.fec <  DATE_ADD('$anio-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01', INTERVAL 1 MONTH)
                        $filtroBodegaV
                    GROUP BY {$t['venta_campo']}
                ) v ON v.id_referencia = p.id_referencia
                ORDER BY r.{$t['nombre']}";
        echo json_encode(GenerarArray($sql));
        break;

    default:
        echo json_encode(['ok' => false, 'msg' => 'Operación no válida']);
        break;
}