<?php

session_start();

require_once '../../../models/bd.php';
require_once '../../../models/funciones.php';
require_once 'intelligenceService.php';
require_once 'ventasIntelligence.php';
require_once 'analisisVentas.php';
require_once 'alertasVentas.php';
require_once 'alertasProductos.php';
require_once 'inventarioIntelligence.php';
require_once 'alertasInventario.php';
require_once '../AI/contextoAI.php';
require_once '../AI/promptsAI.php';
require_once '../AI/AIService.php';
try {

    /*
     * ==========================================================
     * 1. VERIFICAR EMPRESA Y BODEGA
     * ==========================================================
     */

    echo "<h2>1. Empresa actual</h2>";

    echo "<pre>";
    //print_r( $_SESSION['Ses_empresa'] ?? 'NO EXISTE SESIÓN' );
    echo "</pre>";

    echo "<h2>Bodega actual</h2>";

    echo "<pre>";
    //print_r( $_SESSION['Ses_bodega'] ?? 'NO EXISTE SESIÓN' );
    echo "</pre>";


    /*
     * ==========================================================
     * 2. CREAR INTELLIGENCE SERVICE
     * ==========================================================
     */

    $intelligence = new IntelligenceService();

    echo "<h2>2. IntelligenceService</h2>";

    echo "<pre>";
    //print_r( $intelligence->contexto() );
    echo "</pre>";


    /*
     * ==========================================================
     * 3. CREAR VENTAS INTELLIGENCE
     * ==========================================================
     */

    $ventas = new VentasIntelligence(
        $intelligence
    );


    /*
     * ==========================================================
     * 4. PARÁMETROS DE PRUEBA
     * ==========================================================
     */

    $fechaInicial = '2026-01-01';

    $fechaFinal = '2026-08-25';

    $bodega = (int)(
        $_SESSION['Ses_bodega'] ?? 0
    );


    /*
     * ==========================================================
     * 5. VENTAS POR PERÍODO
     * ==========================================================
     */

    echo "<h2>3. Ventas por período</h2>";

    $ventasPeriodo = $ventas->ventasPeriodo(
        $fechaInicial,
        $fechaFinal,
        $bodega
    );

    echo "<pre>";
    //print_r($ventasPeriodo);
    echo "</pre>";


    /*
     * ==========================================================
     * 6. RESUMEN DE VENTAS
     * ==========================================================
     */

    echo "<h2>4. Resumen de ventas</h2>";

    $resumen = $ventas->resumenVentas(
        $fechaInicial,
        $fechaFinal,
        $bodega
    );

    echo "<pre>";
    //print_r($resumen);
    echo "</pre>";


    /*
     * ==========================================================
     * 7. COMPARATIVO DE VENTAS
     * ==========================================================
     */

    echo "<h2>5. Comparativo de ventas</h2>";

    $comparativo = $ventas->comparativo(
        '2026-08-01',
        '2026-08-25',
        $bodega
    );

    echo "<pre>";
    //print_r($comparativo);
    echo "</pre>";


    /*
     * ==========================================================
     * 8. ANÁLISIS DE VENTAS
     * ==========================================================
     */

    echo "<h2>6. Análisis de ventas</h2>";

    $analisis = new AnalisisVentas();

    $resultadoAnalisis =
        $analisis->analizarComparativo(
            $comparativo
        );

    echo "<pre>";
    //print_r($resultadoAnalisis);
    echo "</pre>";


    /*
     * ==========================================================
     * 9. ALERTAS DE VENTAS
     * ==========================================================
     */

    echo "<h2>7. Alertas de ventas</h2>";

    $alertasVentas = new AlertasVentas();

    /*
     * Si tu clase utiliza generar()
     */
    $alertas = $alertasVentas->generar(
        $comparativo
    );

    echo "<pre>";
    //print_r($alertas);
    echo "</pre>";


    /*
     * ==========================================================
     * 10. VENTAS POR PRODUCTO
     * ==========================================================
     */

    echo "<h2>10. Ventas por producto</h2>";

    $ventasPorProducto =
        $ventas->ventasPorProducto(
            $fechaInicial,
            $fechaFinal,
            $bodega
        );

    echo "<pre>";
    //print_r($ventasPorProducto);
    echo "</pre>";


    /*
     * ==========================================================
     * 11. COMPARATIVO DE PRODUCTOS
     * ==========================================================
     */

    echo "<h2>11. Comparativo de productos</h2>";

    $comparativoProductos =
        $ventas->comparativoProductos(
            '2026-08-01',
            '2026-08-25',
            $bodega
        );

    echo "<pre>";
    //print_r($comparativoProductos);
    echo "</pre>";


    /*
     * ==========================================================
     * 12. PRODUCTOS EN CAÍDA
     * ==========================================================
     */

    echo "<h2>12. Productos en caída</h2>";

    $productosCaida =
        $ventas->productosCaida(
            $comparativoProductos
        );

    echo "<pre>";
    //print_r($productosCaida);
    echo "</pre>";


    /*
     * ==========================================================
     * 13. PRODUCTOS EN CRECIMIENTO
     * ==========================================================
     */

    echo "<h2>13. Productos en crecimiento</h2>";

    $productosCrecimiento =
        $ventas->productosCrecimiento(
            $comparativoProductos
        );

    echo "<pre>";
    //print_r($productosCrecimiento);
    echo "</pre>";


    /*
     * ==========================================================
     * 14. IMPACTO DE PRODUCTOS
     * ==========================================================
     */

    echo "<h2>14. Impacto de productos</h2>";

    $impactoProductos =
        $ventas->impactoProductos(
            $comparativoProductos
        );

    echo "<pre>";
    //print_r($impactoProductos);
    echo "</pre>";
    /*
     * ==========================================================
     * 15. ALERTAS DE PRODUCTOS
     * ==========================================================
     */

    echo "<h2>15. Alertas de productos</h2>";

    $alertasProductos = new AlertasProductos();

    $alertas = $alertasProductos->generar(
        $comparativoProductos
    );

    echo "<pre>";
    //print_r($alertas);
    echo "</pre>";

    /*
     * ==========================================================
     * 16. INVENTARIO ACTUAL
     * ==========================================================
     */
    echo "<h2>16. Inventario actual</h2>";
    $inventario = new InventarioIntelligence($intelligence);

    $inventarioActual = $inventario->inventarioActual(
        $bodega
    );

    echo "<pre>";
    //print_r($inventarioActual);
    echo "</pre>";
    /*
    * ==========================================================
    * 17. ROTACION DE INVENTARIO
    * ==========================================================
    */

    echo "<h2>17. Rotación de inventario</h2>";


    $rotacion = $inventario->rotacionInventario(
        $inventarioActual,
        $ventasPorProducto,
        25
    );

    echo "<pre>";
    //print_r($rotacion);
    echo "</pre>";

    /*
    * ==========================================================
    * 18. ANALISIS DE INVENTARIO
    * ==========================================================
    */

    echo "<h2>18. Análisis de inventario</h2>";


    $resultadoInventario = $inventario->analizar(
        $rotacion
    );

    echo "<pre>";
    //print_r($resultadoInventario);
    echo "</pre>";
    /*
    * ==========================================================
    * 19. INVENTARIO SIN MOVIMIENTO
    * ==========================================================
    */

    echo "<h2>19. Inventario sin movimiento</h2>";

    $inventarioSinMovimiento =  $inventario->inventarioSinMovimiento(
        $inventarioActual,
        $ventasPorProducto
    );

    echo "<pre>";
    //print_r($inventarioSinMovimiento);
    echo "</pre>";

    /*
    * ==========================================================
    * 20. ALERTAS DE INVENTARIO
    * ==========================================================
    */

    echo "<h2>20. Alertas de inventario</h2>";

    $alertasInventario = new AlertasInventario();

    $alertas = $alertasInventario->generar(
        $resultadoInventario
    );

    echo "<pre>";
    //print_r($alertas);
    echo "</pre>";

    /*
 * ==========================================================
 * 21. CONTEXTO PARA IA
 * ==========================================================
 */

    echo "<h2>21. Contexto IA</h2>";

    $contextoIA = new ContextoAI();

    $contexto = $contextoIA->construir([

        'empresa' => $_SESSION['Ses_empresa'] ?? null,

        'bodega' => $bodega,

        'periodo' => [
            'fecha_inicio' => '2026-08-01',
            'fecha_fin' => '2026-08-25'
        ],

        'ventas' => [
            'periodo' => $ventasPeriodo,
            'resumen' => $resumen,
            'comparativo' => $comparativo,
            'analisis' => $resultadoAnalisis
        ],

        'productos' => [
            'comparativo' => $comparativoProductos
        ],

        'inventario' => [
            'actual' => $inventarioActual,
            'rotacion' => $rotacion,
            'analisis' => $resultadoInventario,
            'sin_movimiento' => $inventarioSinMovimiento
        ],

        'alertas_ventas' => $alertas,

        'alertas_productos' => $alertasProductos,

        'alertas_inventario' => $alertasInventario
    ]);

    echo "<pre>";
    //print_r($contexto);
    echo "</pre>";

    echo "<h2>JSON para IA</h2>";

    echo "<pre>";
    //  echo $contextoIA->comoJson($contexto);
    echo "</pre>";



    $prompts = new PromptsAI();

    $prompt = $prompts->analisisGeneral($contexto);

    echo "<h2>Prompt para IA</h2>";
    echo "<pre>";
    print_r($prompt);
    echo "</pre>";
    
    $ia = new AIService();

    $ia->analizar('Analiza esto', $prompt);

    /*
     * ==========================================================
     * FINAL
     * ==========================================================
     */

    echo "<h2 style='color:green;'>
            ISA Intelligence funcionando correctamente
          </h2>";
} catch (Exception $e) {

    echo "<h2 style='color:red;'>
            ERROR
          </h2>";

    echo "<pre>";
    echo $e->getMessage();
    echo "</pre>";
}
