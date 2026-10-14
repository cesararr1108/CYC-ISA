<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
header('Content-Type: application/json');

try {

    $input = json_decode(file_get_contents('php://input'), true);

    $sw = intval($input['sw'] ?? -1);
    $id = intval($input['id'] ?? 0);
    $credito = !empty($input['credito']);
    $nombreArchivo = $input['NombreArchivo'] ?? '';
    $formato = $input['formato'] ?? 'C';
    $expiracionHoras = intval($input['expiracionHoras'] ?? 24);

    if ($id <= 0) {
        throw new Exception('ID de documento inválido');
    }

    /*
     * Determinar qué PHP genera el documento
     */

    switch ($sw) {

        case 0:
            $script = 'comprobante-contable.php';
            break;

        case 1:
            $script = ($formato === 'C')
                ? 'compras.php'
                : 'compras-pos.php';
            break;

        case 2:
        case 6:
            $script = 'entradas-salidas.php';
            break;

        case 3:
            $script = 'trasladosBodegas.php';
            break;

        case 4:
            $script = ($formato === 'C')
                ? 'dev-ventas.php'
                : 'dev-ventas-pos.php';
            break;

        case 7:
            $script = 'devoluciones-compras.php';
            break;

        case 8:
            $script = 'pedidos-ventas.php';
            break;

        case 200:
            $script = 'pedidos-compra.php';
            break;

        case 5:
            $script = $credito
                ? 'factura.php'
                : 'factura-pos.php';
            break;

        case 9:
            $script = $credito
                ? 'egreso.php'
                : 'egreso-pos.php';
            break;

        case 10:
            $script = $credito
                ? 'ingreso.php'
                : 'ingreso-pos.php';
            break;

        case 17:
        case 18:
            $script = 'sube-baja-costo.php';
            break;

        case 11:
        case 12:
        case 13:
        case 14:
            $script = 'notas.php';
            break;
        case '1001': // Factura temporal para POS
            $script = 'factura-tmp-pos.php';
            break;
        default:
            throw new Exception('Tipo de documento no soportado');
    }

    /*
     * Empresa
     */
    $client = $_SESSION["Ses_empresa"];

    /*
     * Directorio privado
     */

    $directorio = __DIR__ . '/../../../documentos_privados/'.$client.'/';



    if (!is_dir($directorio)) {
        mkdir($directorio, 0750, true);
    }


    /*
     * Token aleatorio
     */

    $token = bin2hex(random_bytes(32));

    /*
     * Nombre interno del archivo
     *
     * No usamos el ID como nombre público.
     */

    $nombreInterno = $token . '.pdf';

    $rutaArchivo = $directorio . $nombreInterno;


    /*
     * Generar PDF
     *
     * Los scripts deben soportar modo=guardar
     */

    $_GET['id'] = $id;
    $_GET['modo'] = 'guardar';
    $_GET['archivo'] = $rutaArchivo;

    if ($sw == 8) {
        $_GET['NombreArchivo'] = $nombreArchivo;
    }

    include __DIR__ . '/../pdf/' . $script;


    /*
     * Verificar que realmente se creó
     */

    if (!file_exists($rutaArchivo)) {
        throw new Exception(
            'El documento no fue generado correctamente'
        );
    }


    /*
     * Fecha de expiración
     */

    $expira = date(
        'Y-m-d H:i:s',
        time() + ($expiracionHoras * 3600)
    );

    require_once('funciones.php');
    require_once('bd.php');

    $conn = conectar();

    $sql = "INSERT INTO t_documentos_temporales (
            token,
            archivo,
            fecha_expiracion,
            id_documento,
            sw
        )
        VALUES (?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Error preparando INSERT: " . $conn->error);
    }

    $stmt->bind_param(
        "sssis",
        $token,
        $nombreInterno,
        $expira,
        $id,
        $sw
    );

    if (!$stmt->execute()) {
        die("Error ejecutando INSERT: " . $stmt->error);
    }

    $stmt->close();



    /*
     * URL pública protegida
     */
    $url = 'https://thecyctechnology.com/documento.php?&client=' . urlencode($client) . '&token=' . urlencode($token);


    echo json_encode([
        'ok' => true,
        'url' => $url,
        'archivo' => $nombreInterno,
        'expira' => $expira
    ]);
} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => $e->getMessage()
    ]);
}
