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

if ($_POST["op"] == 'consultar') {
    // Reemplazamos la 'T' y le agregamos los segundos correspondientes
    $fhIni = str_replace('T', ' ', $_POST["fecha_inicio"]) . ':00'; // Inicia en el segundo 00
    $fhFin = str_replace('T', ' ', $_POST["fecha_fin"]) . ':59';       // Termina en el segundo 59

    $naturaleza = $_POST['naturaleza'];
    $id_bodega = intval($_POST["id_bodega"]);
    $id_usuario = $_SESSION["Ses_id"];

    $sql = "SELECT
                v.id_bodega,
                v.fecha_hora,
                v.tipo_mov,
                v.tipo,
                v.numero,
                v.usuario,
                v.valor,
                v.valor_conciliacion,
                v.medio_pago,
                v.cuenta,
                v.naturaleza,
                v.voucher
            FROM v_movimientos_conciliacion v
            WHERE
                v.fecha_hora BETWEEN '$fhIni' AND '$fhFin' ";

    if ($id_bodega != 0) {
        $sql .= " AND v.id_bodega = $id_bodega ";
    }
    if ($naturaleza != 'T') {
        $sql .= " AND v.naturaleza = '$naturaleza' ";
    }

    $resultado = GenerarArray($sql);
    echo json_encode(array(
        'error' => false,
        'data' => $resultado
    ));
}
