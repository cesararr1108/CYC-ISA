<?php
session_start();
require_once('../../models/bd.php');
require_once('../../models/funciones.php');

$conn = conectar();

function updatePedidoEdi($conn, $id, $estadoPedido, $estadoEdi, $respuestaJson)
{
  // Crea columnas EDI en t_pedidos si no existen.
  $hasEstado = mysqli_query($conn, "SHOW COLUMNS FROM t_pedidos LIKE 'estado_edi'");
  if ($hasEstado && mysqli_num_rows($hasEstado) === 0) {
    mysqli_query($conn, "ALTER TABLE t_pedidos ADD COLUMN estado_edi VARCHAR(20) NULL DEFAULT NULL AFTER estado");
  }

  $hasRespuesta = mysqli_query($conn, "SHOW COLUMNS FROM t_pedidos LIKE 'respuesta_edi'");
  if ($hasRespuesta && mysqli_num_rows($hasRespuesta) === 0) {
    mysqli_query($conn, "ALTER TABLE t_pedidos ADD COLUMN respuesta_edi LONGTEXT NULL AFTER estado_edi");
  }

  $hasFecha = mysqli_query($conn, "SHOW COLUMNS FROM t_pedidos LIKE 'fecha_edi'");
  if ($hasFecha && mysqli_num_rows($hasFecha) === 0) {
    mysqli_query($conn, "ALTER TABLE t_pedidos ADD COLUMN fecha_edi DATETIME NULL AFTER respuesta_edi");
  }

  $estadoPedidoEsc = mysqli_real_escape_string($conn, $estadoPedido);
  $estadoEdiEsc = mysqli_real_escape_string($conn, $estadoEdi);
  $respuestaEsc = mysqli_real_escape_string($conn, $respuestaJson);

  $sql = "UPDATE t_pedidos
          SET 
              estado_edi = '$estadoEdiEsc',
              respuesta_edi = '$respuestaEsc',
              fecha_edi = NOW()
          WHERE id = $id";

  return Query($sql);
}

$id = $_POST['id'] ?? null;

if (!isset($id) || !is_numeric($id)) {
  die(json_encode(['error' => true, 'msj' => 'ID inválido.']));
}

$id = (int) $id;

// Consulta del pedido con todos los campos que necesita el documento EDI
$sql = "SELECT
              t.nit,
              CONCAT(DATE_FORMAT(d.fecha_hora, '%Y%m%d'), DATE_FORMAT(d.fecha_hora, '%H%i')) AS fh_doc,
              CONCAT(DATE_FORMAT(DATE_ADD(d.fecha_hora, INTERVAL 8 DAY), '%Y%m%d'),
                    DATE_FORMAT(DATE_ADD(d.fecha_hora, INTERVAL 8 DAY), '%H%i'))             AS fh_ent,
              r.codigo,
              ROUND((p.cantidad / p.can_und), 0)                                             AS cantidad,
              p.pcj_iva,
              ROUND((p.valor_unitario * p.can_und), 0)                                       AS valor_unitario,
              a.ean_edi                                                                      AS ean_bodega,
              (SELECT ean_edi FROM t_empresa LIMIT 1)                                        AS ean_empresa,
              t.ean_edi                                                                      AS ean_proveedor,
              e.ean  																		                                     AS ean_referencia,
              ROW_NUMBER() OVER (ORDER BY d.id)                                              AS consecutivo
          FROM t_pedidos d
          INNER JOIN t_pedidos_detalle p ON p.id_pedido = d.id
          INNER JOIN t_referencias     r ON r.id        = p.id_referencia
          LEFT JOIN (
              SELECT id_referencia, MIN(ean) AS ean
              FROM t_referencias_ean
              GROUP BY id_referencia
          ) e ON e.id_referencia = p.id_referencia
          INNER JOIN t_terceros        t ON t.id        = d.id_tercero
          INNER JOIN t_usuarios        u ON u.id        = d.id_usuario
          INNER JOIN t_almacenes       a ON a.id        = d.id_bodega
          WHERE d.id = $id";

$datos = GenerarArray($sql);

//Necesito validar que todos los campos requeridos para el EDI estén presentes antes de enviar la solicitud a la API. 
//Si falta alguno, se debe devolver un error y no continuar con la solicitud.

$validaciones = [
    'ean_empresa'   => 'El EAN de la empresa no está cargado.',
    'ean_proveedor' => 'El EAN del proveedor no está cargado.',
    'ean_bodega'    => 'El EAN de la bodega no está cargado.',
];

$referenciasSinEAN = [];

foreach ($datos as $item) {

    foreach ($validaciones as $campo => $mensaje) {
        if (empty(trim($item[$campo] ?? ''))) {
            die(json_encode([
                'error' => true,
                'msj'   => $mensaje . ' Imposible continuar.'
            ]));
        }
    }

    if (empty(trim($item['ean_referencia'] ?? ''))) {
        $referenciasSinEAN[] = $item['codigo'];
    }
}

if (!empty($referenciasSinEAN)) {
    die(json_encode([
        'error' => true,
        'msj'   => 'Las siguientes referencias no tienen EAN configurado: ' .
                    implode(', ', $referenciasSinEAN)
    ]));
}
if (empty($datos)) {
  die(json_encode(['error' => true, 'msj' => "No se encontró el pedido con id: $id"]));
}

$item  = reset($datos);
$EAN_1 = $item['ean_empresa']   ?? '';
$EAN_2 = $item['ean_proveedor'] ?? '';
$EAN_3 = $item['ean_bodega']    ?? '';

$empresa = GenerarArray("SELECT
                          sftp_edi as sftp_host,
                          22 as sftp_puerto,
                          usuario_edi as  sftp_usuario,
                          pass_edi as sftp_clave
                        FROM
                          t_empresa
                        LIMIT 1");
$sftpConf = $empresa[0] ?? [];
$sftpHost = trim($sftpConf['sftp_host'] ?? '');
$sftpPort = (int)($sftpConf['sftp_puerto'] ?? 22);
$sftpUser = trim($sftpConf['sftp_usuario'] ?? '');
$sftpPass = (string)($sftpConf['sftp_clave'] ?? '');

// Validaciones de campos EDI requeridos
if (!$EAN_1) die(json_encode(['error' => true, 'msj' => 'El EAN de la empresa no está cargado, imposible continuar.']));
if (!$EAN_2) die(json_encode(['error' => true, 'msj' => 'El EAN del proveedor no está cargado, imposible continuar.']));
if (!$EAN_3) die(json_encode(['error' => true, 'msj' => 'El EAN de la bodega no está cargado, imposible continuar.']));
if ($sftpHost === '' || $sftpUser === '' || $sftpPass === '') die(json_encode(['error' => true, 'msj' => 'No estan configuradas las credenciales SFTP en t_empresa.']));

// Enviar datos a la API EDI
$url     = "http://www.delcosoft.com.co/api-fe/public/index.php/edi/enviar";
$payload = json_encode([
  'id_pedido' => $id,
  'datos'     => $datos,
  'sftp_host' => $sftpHost,
  'sftp_puerto' => $sftpPort,
  'sftp_usuario' => $sftpUser,
  'sftp_clave' => $sftpPass,
], JSON_UNESCAPED_UNICODE);

$headers = [
  'Content-Type: application/json',
  'Accept: application/json',
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_CAINFO, "/etc/ssl/certs/thecyctechnology.com.crt");

$response = curl_exec($ch);

if (curl_errno($ch)) {
  $error = curl_error($ch);
  curl_close($ch);
  $respuestaError = json_encode(['error' => true, 'msj' => "Error de conexión con la API EDI: $error"], JSON_UNESCAPED_UNICODE);
  updatePedidoEdi($conn, $id, 'P', 'ERROR', $respuestaError);
  die(json_encode(['error' => true, 'msj' => "Error de conexión con la API EDI: $error"]));
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$decoded = json_decode($response, true);

if ($decoded === null) {
  $respuestaInvalida = json_encode(['error' => true, 'msj' => 'La API EDI devolvió una respuesta inválida.', 'raw' => $response], JSON_UNESCAPED_UNICODE);
  updatePedidoEdi($conn, $id, 'P', 'ERROR', $respuestaInvalida);
  die(json_encode(['error' => true, 'msj' => 'La API EDI devolvió una respuesta inválida.', 'raw' => $response]));
}

$ok = !empty($decoded['ok']) && $decoded['ok'] === true;
$estadoPedido = $ok ? 'I' : 'P';
$estadoEdi = $ok ? 'ENVIADO' : 'ERROR';
$respuestaJson = json_encode($decoded, JSON_UNESCAPED_UNICODE);

$upd = updatePedidoEdi($conn, $id, $estadoPedido, $estadoEdi, $respuestaJson);

echo json_encode([
  'error' => !$ok,
  'ok' => $ok,
  'estado_pedido' => $estadoPedido,
  'estado_edi' => $estadoEdi,
  'pedido_actualizado' => $upd ? true : false,
  'api' => $decoded
], JSON_UNESCAPED_UNICODE);
