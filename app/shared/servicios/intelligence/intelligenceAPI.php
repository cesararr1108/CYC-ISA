<?php
/**
 * ------------------------------------------------------------
 * Endpoint JSON para el Dashboard Gerencial (ISA Intelligence).
 * Reutiliza las mismas clases que tu script de pruebas
 * (IntelligenceService, VentasIntelligence, AnalisisVentas,
 * AlertasVentas, AlertasProductos, InventarioIntelligence,
 * AlertasInventario) pero en vez de imprimir con print_r,
 * arma un solo JSON que el dashboard consume vía AJAX (jQuery).
 *
 * Colócalo en la MISMA carpeta que tu script de prueba original,
 * porque usa los mismos require_once relativos.
 *
 * Parámetros GET opcionales:
 *   fecha_inicial=YYYY-MM-DD
 *   fecha_final=YYYY-MM-DD
 *   bodega=<int>
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../../../models/bd.php';
require_once '../../../models/funciones.php';
require_once 'intelligenceService.php';
require_once 'ventasIntelligence.php';
require_once 'analisisVentas.php';
require_once 'alertasVentas.php';
require_once 'alertasProductos.php';
require_once 'inventarioIntelligence.php';
require_once 'alertasInventario.php';

$respuesta = [
    'ok'   => true,
    'data' => [],
    'meta' => [],
];

try {

    // ---------------------------------------------------------
    // Parámetros (con defaults razonables tipo "mes en curso")
    // ---------------------------------------------------------
    $fechaInicial = $_GET['fecha_inicial'] ?? date('Y-m-01');
    $fechaFinal   = $_GET['fecha_final']   ?? date('Y-m-01');
    $bodega       = (int)($_GET['bodega'] ?? ($_SESSION['Ses_bodega'] ?? 0));

    $respuesta['meta'] = [
        'fecha_inicial' => $fechaInicial,
        'fecha_final'   => $fechaFinal,
        'bodega'        => $bodega,
        'empresa'       => $_SESSION['Ses_empresa'] ?? null,
    ];

    // ---------------------------------------------------------
    // Servicios
    // ---------------------------------------------------------
    $intelligence = new IntelligenceService();
    $ventas       = new VentasIntelligence($intelligence);
    $analisis     = new AnalisisVentas();
    $alertasVta   = new AlertasVentas();
    $alertasProd  = new AlertasProductos();
    $inventario   = new InventarioIntelligence($intelligence);
    $alertasInv   = new AlertasInventario();

    // ---------------------------------------------------------
    // Ventas
    // ---------------------------------------------------------
    $ventasPeriodo = $ventas->ventasPeriodo($fechaInicial, $fechaFinal, $bodega);
    $resumen       = $ventas->resumenVentas($fechaInicial, $fechaFinal, $bodega);
    $comparativo   = $ventas->comparativo($fechaInicial, $fechaFinal, $bodega);
    $analisisVtas  = $analisis->analizarComparativo($comparativo);
    $alertas_ventas = $alertasVta->generar($comparativo);

    // ---------------------------------------------------------
    // Productos
    // ---------------------------------------------------------
    $ventasPorProducto     = $ventas->ventasPorProducto($fechaInicial, $fechaFinal, $bodega);
    $comparativoProductos  = $ventas->comparativoProductos($fechaInicial, $fechaFinal, $bodega);
    $productosCaida        = $ventas->productosCaida($comparativoProductos);
    $productosCrecimiento  = $ventas->productosCrecimiento($comparativoProductos);
    $impactoProductos      = $ventas->impactoProductos($comparativoProductos);
    $alertas_productos     = $alertasProd->generar($comparativoProductos);

    // ---------------------------------------------------------
    // Inventario
    // ---------------------------------------------------------
    $inventarioActual         = $inventario->inventarioActual($bodega);
    $rotacion                 = $inventario->rotacionInventario($inventarioActual, $ventasPorProducto, 25);
    $analisisInventario       = $inventario->analizar($rotacion);
    $inventarioSinMovimiento  = $inventario->inventarioSinMovimiento($inventarioActual, $ventasPorProducto);
    $alertas_inventario       = $alertasInv->generar($analisisInventario);

    // ---------------------------------------------------------
    // Payload final
    // ---------------------------------------------------------
    $respuesta['data'] = [
        'ventas' => [
            'periodo'      => $ventasPeriodo,
            'resumen'      => $resumen,
            'comparativo'  => $comparativo,
            'analisis'     => $analisisVtas,
            'alertas'      => $alertas_ventas,
        ],
        'productos' => [
            'ventas_por_producto'    => $ventasPorProducto,
            'comparativo'            => $comparativoProductos,
            'caida'                  => $productosCaida,
            'crecimiento'            => $productosCrecimiento,
            'impacto'                => $impactoProductos,
            'alertas'                => $alertas_productos,
        ],
        'inventario' => [
            'actual'          => $inventarioActual,
            'rotacion'        => $rotacion,
            'analisis'        => $analisisInventario,
            'sin_movimiento'  => $inventarioSinMovimiento,
            'alertas'         => $alertas_inventario,
        ],
    ];

} catch (Exception $e) {
    http_response_code(500);
    $respuesta['ok'] = false;
    $respuesta['error'] = $e->getMessage();
}

echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);