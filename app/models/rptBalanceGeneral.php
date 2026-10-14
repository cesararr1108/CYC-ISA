<?php
session_start();
require_once('bd.php');
require_once('funciones.php');
require_once('../shared/servicios/servicios-email.php');
$conn = conectar();

try {
    $mysqli = $conn;
    $op = $_POST['op'] ?? 'consultar';

    // =========================
    // 📌 OPERACIÓN: OBTENER CUENTAS
    // =========================
    if ($op === 'cuentas') {
        $sql = "SELECT DISTINCT cuenta, descripcion 
                FROM t_cuentas_contables 
                ORDER BY cuenta";
        
        $result = $mysqli->query($sql);
        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        echo json_encode([
            "success" => true,
            "data" => $data
        ]);

        $mysqli->close();
        exit;
    }

    // =========================
    // 📌 OPERACIÓN: CONSULTAR BALANCE
    // =========================
    $fecha_inicial = $_POST['fecha_inicial'] ?? '';
    $fecha_final   = $_POST['fecha_final'] ?? '';
    $cu_inicial    = $_POST['cu_inicial'] ?? '';
    $cu_final      = $_POST['cu_final'] ?? '';

    // =========================
    // 📌 VALIDACIONES
    // =========================
    if (!$fecha_inicial || !$fecha_final) {
        throw new Exception("Debe seleccionar ambas fechas.");
    }

    if ($fecha_final < $fecha_inicial) {
        throw new Exception("La fecha final no puede ser anterior a la inicial.");
    }

    // Convertir fechas a formato YYYYMM
    $periodo_inicial = intval(date("Ym", strtotime($fecha_inicial)));
    $periodo_final   = intval(date("Ym", strtotime($fecha_final)));

    // =========================
    // 📌 FILTROS DINÁMICOS
    // =========================
    $where = [];
    $params = [];
    $types = "";

    // 🔥 Filtro correcto de periodo
    $where[] = "(ano * 100 + mes) BETWEEN ? AND ?";
    $params[] = $periodo_inicial;
    $params[] = $periodo_final;
    $types .= "ii";

    // =========================
    // 📌 FILTRO DE CUENTAS (PRO)
    // =========================
    if ($cu_inicial && $cu_final) {

        if ($cu_inicial === $cu_final) {
            // 👉 MODO PREFIJO (ej: 2365 → trae todo el grupo)
            $where[] = "CAST(cuenta AS CHAR) LIKE ?";
            $params[] = $cu_inicial . "%";
            $types .= "s";

        } else {
            // 👉 MODO RANGO (ej: 1305 a 1399)
            $where[] = "CAST(cuenta AS CHAR) >= ?";
            $where[] = "CAST(cuenta AS CHAR) <= ?";
            $params[] = $cu_inicial;
            $params[] = $cu_final;
            $types .= "ss";
        }

    } elseif ($cu_inicial) {
        // Solo inicial → prefijo
        $where[] = "CAST(cuenta AS CHAR) LIKE ?";
        $params[] = $cu_inicial . "%";
        $types .= "s";
    }

    // =========================
    // 📌 SQL JERÁRQUICO
    // =========================
    $sql = "SELECT 
    bg.tipo,
    bg.grupo,
    bg.cuenta,
    bg.descripcion,
    ROUND(SUM(bg.saldo),2) AS saldo
FROM
(
    SELECT
        CASE
            WHEN LEFT(vbg.cuenta,1) = '1' THEN 'ACTIVO'
            WHEN LEFT(vbg.cuenta,1) = '2' THEN 'PASIVO'
            WHEN LEFT(vbg.cuenta,1) = '3' THEN 'PATRIMONIO'
        END AS tipo,

        CASE
            WHEN LEFT(vbg.cuenta,1) = '1' THEN '1 - ACTIVOS'
            WHEN LEFT(vbg.cuenta,1) = '2' THEN '2 - PASIVOS'
            WHEN LEFT(vbg.cuenta,1) = '3' THEN '3 - PATRIMONIO'
        END AS grupo,

        vbg.cuenta,
        vbg.descripcion,

        CASE
            WHEN LEFT(vbg.cuenta,1) = '1'
                THEN (
                    IFNULL(vbg.saldo_inicial,0) +
                    IFNULL(vbg.debito,0) -
                    IFNULL(vbg.credito,0)
                )

            WHEN LEFT(vbg.cuenta,1) IN ('2','3')
                THEN (
                    IFNULL(vbg.saldo_inicial,0) +
                    IFNULL(vbg.credito,0) -
                    IFNULL(vbg.debito,0)
                )

            ELSE 0
        END AS saldo

    FROM v_balance_general vbg

    WHERE " . implode(" AND ", $where) . "
      AND LEFT(vbg.cuenta,1) IN ('1','2','3')

) bg

GROUP BY
    bg.tipo,
    bg.grupo,
    bg.cuenta,
    bg.descripcion

HAVING ROUND(SUM(bg.saldo),2) <> 0

ORDER BY
    bg.cuenta";

    // =========================
    // 📌 EJECUCIÓN
    // =========================
    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        throw new Exception("Error en la consulta: " . $mysqli->error);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode([
        "success" => true,
        "data" => $data
    ]);

    $stmt->close();
    $mysqli->close();

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
