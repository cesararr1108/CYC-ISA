<?php
session_start();
require_once('bd.php');
require_once('funciones.php');
require_once('../shared/servicios/servicios-email.php');
$conn = conectar();

try {
    $mysqli = $conn;

    // =========================
    // 📌 PARÁMETROS
    // =========================
    $fecha_inicial = $_POST['fecha_inicial'] ?? '';
    $fecha_final   = $_POST['fecha_final'] ?? '';
    $cuenta_inicial = $_POST['cuenta_inicial'] ?? '';
    $cuenta_final   = $_POST['cuenta_final'] ?? '';
    $ver_terceros = !empty($_POST['ver_terceros']);

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
    if ($cuenta_inicial && $cuenta_final) {

        if ($cuenta_inicial === $cuenta_final) {
            // 👉 MODO PREFIJO (ej: 2365 → trae todo el grupo)
            $where[] = "CAST(cuenta AS CHAR) LIKE ?";
            $params[] = $cuenta_inicial . "%";
            $types .= "s";

        } else {
            // 👉 MODO RANGO (ej: 1305 a 1399)
            $where[] = "CAST(cuenta AS CHAR) >= ?";
            $where[] = "CAST(cuenta AS CHAR) <= ?";
            $params[] = $cuenta_inicial;
            $params[] = $cuenta_final;
            $types .= "ss";
        }

    } elseif ($cuenta_inicial) {
        // Solo inicial → prefijo
        $where[] = "CAST(cuenta AS CHAR) LIKE ?";
        $params[] = $cuenta_inicial . "%";
        $types .= "s";
    }

    // =========================
    // 📌 SQL
    // =========================
    $sql = "SELECT 
                ano, 
                mes, 
                cuenta,
                descripcion, 
                SUM(ROUND(saldo_inicial,0)) AS saldo_inicial, 
                SUM(ROUND(debito,0)) AS debito, 
                SUM(ROUND(credito,0)) AS credito, 
                SUM(ROUND(saldo_final,0)) AS saldo_final";

    if ($ver_terceros) {
        $sql .= ", nit";
    }

    $sql .= " FROM v_balance_general";

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    // =========================
    // 📌 GROUP BY
    // =========================
    $groupBy = ["ano", "mes", "cuenta", "descripcion"];

    if ($ver_terceros) {
        $groupBy[] = "nit";
    }

    $sql .= " GROUP BY " . implode(", ", $groupBy);

    // =========================
    // 📌 ORDER
    // =========================
    $sql .= " ORDER BY cuenta, ano, mes";

    if ($ver_terceros) {
        $sql .= ", nit";
    }

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
        "mensaje" => $e->getMessage()
    ]);
}