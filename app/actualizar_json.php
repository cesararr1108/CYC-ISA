<?php
session_start();
require_once('models/bd.php');

$conn = conectar();

// Ruta del archivo JSON
$empresa = $_SESSION['Ses_empresa'] ?? 'CYC';
$jsonFile = dirname(__DIR__) . '/files/' . $empresa . '/traslados_recibidos.json';

echo "Leyendo JSON desde: " . $jsonFile . "<br>";

if (!file_exists($jsonFile)) {
    echo "Archivo no encontrado";
    exit;
}

// Leer JSON actual
$jsonContent = file_get_contents($jsonFile);
$traslados_viejos = json_decode($jsonContent, true) ?? [];

if (empty($traslados_viejos)) {
    echo "JSON vacío";
    exit;
}

echo "Encontrados " . count($traslados_viejos) . " registros<br><br>";

// Regenerar con nuevos campos
$traslados_nuevos = [];

foreach ($traslados_viejos as $t) {
    $id_transaccion = $t['id'];
    
    // Obtener datos del traslado desde la BD
    $sql = "SELECT t.id, t.numero, t.fecha, t.id_bodega, t.id_bodega_destino, t.id_usuario,
                   o.descripcion AS b_origen, d.descripcion AS destino, 
                   u.usuario AS usuario_generador 
            FROM t_transacciones t
            INNER JOIN t_almacenes o ON o.id = t.id_bodega 
            INNER JOIN t_almacenes d ON d.id = t.id_bodega_destino 
            INNER JOIN t_usuarios u ON u.id = t.id_usuario
            WHERE t.id = $id_transaccion";
    
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $traslado = $result->fetch_assoc();
        $traslados_nuevos[] = $traslado;
        echo "✓ ID " . $id_transaccion . " - Bodega Destino: " . $traslado['id_bodega_destino'] . " - Usuario: " . $traslado['id_usuario'] . "<br>";
    } else {
        echo "✗ ID " . $id_transaccion . " - No encontrado en BD<br>";
    }
}

// Guardar JSON actualizado
if (file_put_contents($jsonFile, json_encode($traslados_nuevos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo "<br><strong>✓ JSON actualizado exitosamente</strong><br>";
    echo "Registros regenerados: " . count($traslados_nuevos);
} else {
    echo "<br><strong>✗ Error al escribir el archivo JSON</strong>";
}

$conn->close();
?>
