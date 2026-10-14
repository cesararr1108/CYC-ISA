<?php
session_start();
require_once('bd.php');
require_once('funciones.php');

/**
 * Estructura de respuesta estándar del proyecto (definida en el prompt maestro):
 *
 * Éxito:
 * { "success": true, "message": "...", "data": {} }
 *
 * Error:
 * { "success": false, "message": "...", "errors": [] }
 */

function responderJson($success, $message, $extra = [])
{
    $respuesta = [
        'success' => $success,
        'message' => $message,
    ];

    // Fusionamos 'data' o 'errors' según corresponda, sin sobreescribir success/message.
    foreach ($extra as $llave => $valor) {
        $respuesta[$llave] = $valor;
    }

    echo json_encode($respuesta);
    exit;
}

// 1. Validar sesión / bodega
// SUPOSICIÓN: si no hay sesión iniciada o no existe Ses_bodega, se trata como
// sesión expirada. Ajusta el mensaje/código si tu sistema maneja esto distinto
// (ej. redirección a login en vez de JSON).
if (!isset($_SESSION['Ses_bodega']) || !is_numeric($_SESSION['Ses_bodega'])) {
    responderJson(false, 'Sesión expirada o bodega no definida.', ['errors' => ['sesion_invalida']]);
}

$idBodega = (int) $_SESSION['Ses_bodega'];

// 2. Validar método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(false, 'Método de solicitud no permitido.', ['errors' => ['metodo_invalido']]);
}

// 3. Validar parámetros de entrada (mes, anio)
$mes  = $_POST['mes']  ?? null;
$anio = $_POST['anio'] ?? null;

if (!ctype_digit((string) $mes) || !ctype_digit((string) $anio)) {
    responderJson(false, 'Parámetros de mes/año inválidos.', ['errors' => ['parametros_invalidos']]);
}

$mes  = (int) $mes;
$anio = (int) $anio;

if ($mes < 1 || $mes > 12) {
    responderJson(false, 'El mes debe estar entre 1 y 12.', ['errors' => ['mes_invalido']]);
}

// Rango razonable de años para evitar valores absurdos (ajustable si lo necesitas).
if ($anio < 2000 || $anio > 2100) {
    responderJson(false, 'Año inválido.', ['errors' => ['anio_invalido']]);
}

// 4. Calcular rango de fechas del mes solicitado
$fechaInicio = sprintf('%04d-%02d-01', $anio, $mes);
$ultimoDia   = (int) date('t', strtotime($fechaInicio)); // días reales del mes (28/29/30/31)
$fechaFin    = sprintf('%04d-%02d-%02d', $anio, $mes, $ultimoDia);

// 5. Invocar el modelo
$resultado = obtenerCarteraPorPagarCalendario($idBodega, $fechaInicio, $fechaFin);

if (!$resultado['ok']) {
    responderJson(false, $resultado['error'], ['errors' => ['error_consulta']]);
}

// 6. Responder éxito
responderJson(true, 'Cartera por pagar obtenida correctamente.', ['data' => $resultado['data']]);

/**
 * Obtiene la cartera por pagar (sw IN 1,13,14) con saldo pendiente,
 * cuyo vencimiento cae dentro del rango de fechas indicado (mes visible
 * en el calendario), filtrada por bodega.
 *
 * @param int    $idBodega     ID de bodega (tomado de $_SESSION['Ses_bodega'] en el controlador)
 * @param string $fechaInicio  Formato 'YYYY-MM-DD' (primer día del mes visible)
 * @param string $fechaFin     Formato 'YYYY-MM-DD' (último día del mes visible)
 *
 * @return array Arreglo con dos llaves:
 *               - 'ok'   => bool
 *               - 'data' => array agrupado por fecha de vencimiento, ej:
 *                           [
 *                             '2026-08-15' => [
 *                                 [
 *                                   'documento'          => 'FC-1023',
 *                                   'tipo'                => 1,
 *                                   'numero'              => 1023,
 *                                   'nit'                 => '900123456',
 *                                   'razon_comercial'     => 'Proveedor X',
 *                                   'valor_total'         => 500000.00,
 *                                   'valor_aplicado'      => 300000.00,
 *                                   'saldo'               => 200000.00,
 *                                   'vencimiento'         => '2026-08-15',
 *                                   'dias_transcurridos'  => 5,
 *                                 ],
 *                                 ...
 *                             ],
 *                             ...
 *                           ]
 *               - 'error'=> string (solo si ok = false)
 */
function obtenerCarteraPorPagarCalendario($idBodega, $fechaInicio, $fechaFin)
{
    $conn = conectar();

    if (!$conn) {
        return [
            'ok'    => false,
            'data'  => [],
            'error' => 'No fue posible establecer conexión con la base de datos.',
        ];
    }

    // Validaciones básicas de tipo/formato antes de tocar la BD.
    // No confiamos en que el controlador ya haya validado todo.
    if (!is_numeric($idBodega)) {
        return [
            'ok'    => false,
            'data'  => [],
            'error' => 'Bodega inválida.',
        ];
    }

    if (!validarFormatoFecha($fechaInicio) || !validarFormatoFecha($fechaFin)) {
        return [
            'ok'    => false,
            'data'  => [],
            'error' => 'Rango de fechas inválido.',
        ];
    }

    $sql = "
        SELECT
            CONCAT(d.tipo, '-', d.numero) AS documento,
            d.tipo,
            d.numero,
            t.nit,
            t.razon_comercial,
            d.valor_total,
            IFNULL(d.valor_aplicado, 0) AS valor_aplicado,
            (d.valor_total - IFNULL(d.valor_aplicado, 0)) AS saldo,
            d.vencimiento,
            DATEDIFF(CURDATE(), d.vencimiento) AS dias_transcurridos
        FROM t_transacciones d
        JOIN t_terceros t ON t.id = d.id_tercero
        WHERE
            IFNULL(d.valor_aplicado, 0) <> d.valor_total
            AND d.sw IN (1, 13, 14)
            AND d.id_bodega = ?
            AND d.vencimiento BETWEEN ? AND ?
        ORDER BY d.vencimiento
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        // No exponemos $conn->error directamente al usuario final (esto lo maneja
        // el controlador); aquí solo lo registramos para depuración interna.
        error_log('Error al preparar consulta calendarioPagos: ' . $conn->error);
        return [
            'ok'    => false,
            'data'  => [],
            'error' => 'Error interno al consultar la cartera por pagar.',
        ];
    }

    // i = entero (bodega), s = string, s = string (fechas como 'YYYY-MM-DD')
    $stmt->bind_param('iss', $idBodega, $fechaInicio, $fechaFin);

    if (!$stmt->execute()) {
        error_log('Error al ejecutar consulta calendarioPagos: ' . $stmt->error);
        $stmt->close();
        return [
            'ok'    => false,
            'data'  => [],
            'error' => 'Error interno al consultar la cartera por pagar.',
        ];
    }

    $resultado = $stmt->get_result();

    $agrupadoPorFecha = [];

    while ($fila = $resultado->fetch_assoc()) {
        $fecha = $fila['vencimiento'];

        if (!isset($agrupadoPorFecha[$fecha])) {
            $agrupadoPorFecha[$fecha] = [];
        }

        $agrupadoPorFecha[$fecha][] = [
            'documento'         => $fila['documento'],
            'tipo'              => (int) $fila['tipo'],
            'numero'            => $fila['numero'],
            'nit'               => $fila['nit'],
            'razon_comercial'   => $fila['razon_comercial'],
            'valor_total'       => (float) $fila['valor_total'],
            'valor_aplicado'    => (float) $fila['valor_aplicado'],
            'saldo'             => (float) $fila['saldo'],
            'vencimiento'       => $fila['vencimiento'],
            'dias_transcurridos'=> (int) $fila['dias_transcurridos'],
        ];
    }

    $stmt->close();

    return [
        'ok'   => true,
        'data' => $agrupadoPorFecha,
    ];
}

/**
 * Valida que una fecha tenga formato YYYY-MM-DD y sea una fecha real
 * (protege contra fechas inválidas como '2026-02-30').
 */
function validarFormatoFecha($fecha)
{
    $partes = explode('-', $fecha);

    if (count($partes) !== 3) {
        return false;
    }

    [$anio, $mes, $dia] = $partes;

    if (!ctype_digit($anio) || !ctype_digit($mes) || !ctype_digit($dia)) {
        return false;
    }

    return checkdate((int) $mes, (int) $dia, (int) $anio);
}