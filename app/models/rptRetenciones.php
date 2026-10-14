<?php
session_start();
require_once('bd.php');

$conn = conectar();

if (!isset($_POST['op'])) {
    echo json_encode(['error' => true, 'mensaje' => 'Operacion no especificada']);
    exit;
}

$op = $_POST['op'];

function responseJson($error, $data = [], $mensaje = '')
{
    echo json_encode([
        'error' => $error,
        'data' => $data,
        'mensaje' => $mensaje
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

switch ($op) {
    case 'consultar':
        $fechaInicial = isset($_POST['fecha_inicial']) ? trim($_POST['fecha_inicial']) : '';
        $fechaFinal = isset($_POST['fecha_final']) ? trim($_POST['fecha_final']) : '';
        $tipoConsulta = isset($_POST['tipo_consulta']) ? trim($_POST['tipo_consulta']) : 'transacciones';

        if ($fechaInicial === '' || $fechaFinal === '') {
            responseJson(true, [], 'La fecha inicial y final son obligatorias');
        }

        if (!in_array($tipoConsulta, ['transacciones', 'contable'], true)) {
            responseJson(true, [], 'Tipo de consulta no valido');
        }

        if ($tipoConsulta === 'contable') {
            $sql = "SELECT
                        mc.id_transaccion,
                        mc.nit,
                        mc.tipo,
                        mc.numero,
                        SUM(
                            CASE
                                WHEN ct.tipo = 'RETEFUENTE_COMPRA'
                                THEN IFNULL(mc.credito,0) - IFNULL(mc.debito,0)
                                ELSE 0
                            END
                        ) AS retefuente_compra,
                        SUM(
                            CASE
                                WHEN ct.tipo = 'RETEFUENTE_VENTA'
                                THEN IFNULL(mc.credito,0) - IFNULL(mc.debito,0)
                                ELSE 0
                            END
                        ) AS retefuente_venta,
                        SUM(
                            CASE
                                WHEN ct.tipo = 'RETEIVA_COMPRA'
                                THEN IFNULL(mc.credito,0) - IFNULL(mc.debito,0)
                                ELSE 0
                            END
                        ) AS reteiva_compra,
                        SUM(
                            CASE
                                WHEN ct.tipo = 'RETEIVA_VENTA'
                                THEN IFNULL(mc.credito,0) - IFNULL(mc.debito,0)
                                ELSE 0
                            END
                        ) AS reteiva_venta,
                        SUM(
                            CASE
                                WHEN ct.tipo = 'RETEICA_COMPRA'
                                THEN IFNULL(mc.credito,0) - IFNULL(mc.debito,0)
                                ELSE 0
                            END
                        ) AS reteica_compra,
                        SUM(
                            CASE
                                WHEN ct.tipo = 'RETEICA_VENTA'
                                THEN IFNULL(mc.credito,0) - IFNULL(mc.debito,0)
                                ELSE 0
                            END
                        ) AS reteica_venta
                    FROM t_movimiento_contable mc
                    INNER JOIN t_cuentas_contables cc
                        ON cc.cuenta = mc.cuenta
                    INNER JOIN t_cuentas_tributarias ct
                        ON ct.id_cuenta_contable = cc.id
                    WHERE CAST(mc.fecha_hora AS DATE) BETWEEN ? AND ?
                    GROUP BY
                        mc.id_transaccion,
                        mc.nit,
                        mc.tipo,
                        mc.numero
                    HAVING
                        retefuente_compra <> 0
                        OR retefuente_venta <> 0
                        OR reteiva_compra <> 0
                        OR reteiva_venta <> 0
                        OR reteica_compra <> 0
                        OR reteica_venta <> 0
                    ORDER BY
                        mc.tipo,
                        mc.numero;";
        } else {
            $sql = "SELECT
                        s.descripcion as tipo,
                        t.fecha,
                        t.tipo,
                        t.numero,
                        r.nit,
                        r.razon_comercial AS tercero,
                        r.tratamiento,
                        t.valor_total,
                        IFNULL(t.iva,0) AS iva,
                        IFNULL(t.impoconsumo,0) AS impoconsumo,
                        IFNULL(t.ibua,0) AS ibua,
                        IFNULL(t.icui,0) AS icui,
                        (
                            t.valor_total
                            - IFNULL(t.iva,0)
                            - IFNULL(t.impoconsumo,0)
                            - IFNULL(t.ibua,0)
                            - IFNULL(t.icui,0)
                        ) AS base_gravable,
                        t.retencion_fuente,
                        ROUND(
                            (
                                t.retencion_fuente /
                                NULLIF(
                                    (
                                        t.valor_total
                                        - IFNULL(t.iva,0)
                                        - IFNULL(t.impoconsumo,0)
                                        - IFNULL(t.ibua,0)
                                        - IFNULL(t.icui,0)
                                    ),
                                    0
                                )
                            ) * 100,
                            2
                        ) AS porcentaje_retencion,
                        t.id
                    FROM t_transacciones t
                    INNER JOIN t_terceros r
                        ON r.id = t.id_tercero
                    INNER JOIN t_sw s 
                        ON s.sw = t.sw 
                    WHERE t.sw in(1,5,4,7) 
                    AND t.retencion_fuente > 0
                    AND IFNULL(t.anulado,0) = 0
                    AND CAST(t.fecha_hora AS DATE) BETWEEN ? AND ?
                    ORDER BY
                        t.sw,
                        t.fecha,
                        r.razon_comercial,
                        t.numero;";
        }

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            responseJson(true, [], 'Error al preparar la consulta');
        }

        $stmt->bind_param('ss', $fechaInicial, $fechaFinal);
        if (!$stmt->execute()) {
            responseJson(true, [], 'Error al ejecutar la consulta');
        }

        $result = $stmt->get_result();
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        responseJson(false, $data, 'Operacion exitosa');
        break;

    default:
        responseJson(true, [], 'Operacion no valida');
        break;
}
