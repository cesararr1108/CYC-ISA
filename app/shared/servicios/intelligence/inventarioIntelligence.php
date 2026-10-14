<?php

class InventarioIntelligence
{
    private $intelligence;

    public function __construct($intelligence)
    {
        $this->intelligence = $intelligence;
    }

    public function inventarioActual($bodega)
    {
        $mes = date('n');
        $ano = date('Y');

        $sql = "SELECT
                    r.id AS id_referencia,
                    r.codigo,
                    r.descripcion AS producto,

                    IFNULL(r.costo_ultimo, 0) AS costo_ultimo,
                    IFNULL(r.costo_unitario, 0) AS costo_unitario,
                    IFNULL(cp.promedio, 0) AS costo_promedio,
                    IFNULL(s.stock, 0) AS stock_total,

                    ROUND(
                        IFNULL(s.stock, 0) *
                        IFNULL(cp.promedio, 0),
                        2
                    ) AS valor_inventario

                FROM t_referencias r
                INNER JOIN t_referencias_mov m 
                    ON m.ano = ? 
                    AND m.mes = ?
                    AND m.id_bodega = ?
                    AND m.id_referencia = r.id
                LEFT JOIN v_stock_total s
                    ON s.id_referencia = r.id
                    AND s.mes = ?
                    AND s.id_bodega = ?

                LEFT JOIN v_costo_promedio cp
                    ON cp.id_referencia = r.id
                    AND cp.mes = ?
                    AND cp.ano = ?

                WHERE 1 = 1

                ORDER BY r.descripcion ASC";

        return $this->intelligence->consultarPreparada(
            $sql,
            [
                $ano,
                $mes,
                $bodega,
                $mes,
                $bodega,
                $mes,
                $ano
            ],
            'iiiiiii'
        );
    }
    /**
     * Calcula la rotación y cobertura del inventario por producto.
     *
     * @param array $inventario
     * @param array $ventasProductos
     * @param int $diasPeriodo
     * @return array
     */
    public function rotacionInventario($inventario, $ventasProductos, $diasPeriodo = 30)
    {
        $resultado = [];

        if (empty($inventario)) {
            return $resultado;
        }

        /*
     * =========================================================
     * INDEXAR VENTAS POR PRODUCTO
     * =========================================================
     */

        $ventasIndexadas = [];

        foreach ($ventasProductos as $venta) {

            $idReferencia = $venta['id_referencia'];

            $ventasIndexadas[$idReferencia] = [
                'cantidad' => isset($venta['cantidad'])
                    ? (float) $venta['cantidad']
                    : 0,

                'venta' => isset($venta['venta'])
                    ? (float) $venta['venta']
                    : 0
            ];
        }


        /*
     * =========================================================
     * CALCULAR ROTACIÓN
     * =========================================================
     */

        foreach ($inventario as $producto) {

            $idReferencia = $producto['id_referencia'];

            $stock = isset($producto['stock_total'])
                ? (float) $producto['stock_total']
                : 0;

            $costoPromedio = isset($producto['costo_promedio'])
                ? (float) $producto['costo_promedio']
                : 0;

            $valorInventario = isset($producto['valor_inventario'])
                ? (float) $producto['valor_inventario']
                : ($stock * $costoPromedio);


            /*
         * =====================================================
         * VENTAS DEL PERÍODO
         * =====================================================
         */

            $cantidadVendida =
                $ventasIndexadas[$idReferencia]['cantidad'] ?? 0;

            $venta =
                $ventasIndexadas[$idReferencia]['venta'] ?? 0;


            /*
         * =====================================================
         * PROMEDIO DIARIO DE VENTA
         * =====================================================
         */

            $promedioDiario = 0;

            if ($diasPeriodo > 0) {

                $promedioDiario =
                    $cantidadVendida / $diasPeriodo;
            }


            /*
         * =====================================================
         * ROTACIÓN
         *
         * Cuántas veces se vendió el stock disponible
         * durante el período.
         * =====================================================
         */

            $rotacion = 0;

            if ($stock > 0) {

                $rotacion =
                    $cantidadVendida / $stock;
            }


            /*
         * =====================================================
         * COBERTURA EN DÍAS
         *
         * Stock actual / venta promedio diaria
         * =====================================================
         */

            $diasCobertura = null;

            if ($promedioDiario > 0) {

                $diasCobertura =
                    $stock / $promedioDiario;
            }


            /*
         * =====================================================
         * CLASIFICACIÓN
         * =====================================================
         */

            if ($stock <= 0) {

                $estado = 'SIN_STOCK';
            } elseif ($cantidadVendida <= 0) {

                $estado = 'SIN_ROTACION';
            } elseif ($diasCobertura <= 7) {

                $estado = 'ROTACION_MUY_ALTA';
            } elseif ($diasCobertura <= 15) {

                $estado = 'ROTACION_ALTA';
            } elseif ($diasCobertura <= 45) {

                $estado = 'ROTACION_NORMAL';
            } elseif ($diasCobertura <= 90) {

                $estado = 'ROTACION_BAJA';
            } else {

                $estado = 'SOBREINVENTARIO';
            }


            /*
         * =====================================================
         * RESULTADO
         * =====================================================
         */

            $resultado[] = [

                'id_referencia' =>
                $idReferencia,

                'codigo' =>
                $producto['codigo'],

                'producto' =>
                $producto['producto'],

                'stock' =>
                round($stock, 2),

                'cantidad_vendida' =>
                round($cantidadVendida, 2),

                'venta' =>
                round($venta, 2),

                'promedio_diario' =>
                round($promedioDiario, 4),

                'rotacion' =>
                round($rotacion, 4),

                'dias_cobertura' =>
                $diasCobertura !== null
                    ? round($diasCobertura, 2)
                    : null,

                'costo_promedio' =>
                round($costoPromedio, 2),

                'valor_inventario' =>
                round($valorInventario, 2),

                'estado' =>
                $estado
            ];
        }


        /*
     * =========================================================
     * ORDENAR
     *
     * Primero los productos con menor cobertura.
     * =========================================================
     */

        usort($resultado, function ($a, $b) {

            $diasA = $a['dias_cobertura'] ?? PHP_FLOAT_MAX;
            $diasB = $b['dias_cobertura'] ?? PHP_FLOAT_MAX;

            return $diasA <=> $diasB;
        });


        return $resultado;
    }

    /**
     * Analiza la rotación del inventario y genera un resumen general.
     *
     * @param array $rotacion
     * @return array
     */
    public function analizar($rotacion)
    {
        $resultado = [
            'total_productos' => 0,
            'valor_inventario' => 0,
            'productos_sin_stock' => 0,
            'productos_sin_rotacion' => 0,
            'productos_rotacion_muy_alta' => 0,
            'productos_rotacion_alta' => 0,
            'productos_rotacion_normal' => 0,
            'productos_rotacion_baja' => 0,
            'productos_sobreinventario' => 0,

            'productos' => []
        ];

        if (empty($rotacion)) {
            return $resultado;
        }

        foreach ($rotacion as $producto) {

            $estado = $producto['estado'] ?? '';

            $valorInventario = isset($producto['valor_inventario'])
                ? (float) $producto['valor_inventario']
                : 0;

            $diasCobertura = isset($producto['dias_cobertura'])
                ? $producto['dias_cobertura']
                : null;


            /*
         * =========================================================
         * CLASIFICACIÓN
         * =========================================================
         */

            switch ($estado) {
                case 'SIN_STOCK':
                    $resultado['productos_sin_stock']++;

                    break;


                case 'SIN_ROTACION':

                    $resultado['productos_sin_rotacion']++;

                    break;


                case 'ROTACION_MUY_ALTA':

                    $resultado['productos_rotacion_muy_alta']++;

                    break;


                case 'ROTACION_ALTA':

                    $resultado['productos_rotacion_alta']++;

                    break;


                case 'ROTACION_NORMAL':

                    $resultado['productos_rotacion_normal']++;

                    break;


                case 'ROTACION_BAJA':

                    $resultado['productos_rotacion_baja']++;

                    break;


                case 'SOBREINVENTARIO':

                    $resultado['productos_sobreinventario']++;

                    break;
            }


            /*
         * =========================================================
         * NIVEL DE RIESGO
         * =========================================================
         */

            $nivel = 'NORMAL';

            if ($estado === 'SIN_STOCK') {

                $nivel = 'CRITICO';
            } elseif ($estado === 'SIN_ROTACION') {

                $nivel = 'CRITICO';
            } elseif ($estado === 'ROTACION_MUY_ALTA') {

                $nivel = 'ADVERTENCIA';
            } elseif ($estado === 'ROTACION_ALTA') {

                $nivel = 'POSITIVO';
            } elseif ($estado === 'ROTACION_BAJA') {

                $nivel = 'ADVERTENCIA';
            } elseif ($estado === 'SOBREINVENTARIO') {

                $nivel = 'CRITICO';
            }


            /*
         * =========================================================
         * AGREGAR NIVEL AL PRODUCTO
         * =========================================================
         */

            $producto['nivel'] = $nivel;

            $resultado['productos'][] = $producto;


            /*
         * =========================================================
         * ACUMULAR VALOR
         * =========================================================
         */

            $resultado['valor_inventario'] += $valorInventario;

            $resultado['total_productos']++;
        }


        /*
     * =========================================================
     * REDONDEAR VALOR TOTAL
     * =========================================================
     */

        $resultado['valor_inventario'] = round(
            $resultado['valor_inventario'],
            2
        );


        /*
     * =========================================================
     * RESUMEN EJECUTIVO
     * =========================================================
     */

        if ($resultado['productos_sobreinventario'] > 0) {

            $resultado['estado'] = 'SOBREINVENTARIO';

            $resultado['nivel'] = 'ALERTA';

            $resultado['mensaje'] =
                'Se detectaron ' .
                $resultado['productos_sobreinventario'] .
                ' productos con niveles de inventario superiores a su ritmo de rotación.';
        } elseif ($resultado['productos_sin_rotacion'] > 0) {

            $resultado['estado'] = 'SIN_ROTACION';

            $resultado['nivel'] = 'ALERTA';

            $resultado['mensaje'] =
                'Se detectaron ' .
                $resultado['productos_sin_rotacion'] .
                ' productos con inventario pero sin ventas durante el período analizado.';
        } elseif ($resultado['productos_sin_stock'] > 0) {

            $resultado['estado'] = 'RIESGO_AGOTAMIENTO';

            $resultado['nivel'] = 'ADVERTENCIA';

            $resultado['mensaje'] =
                'Se detectaron ' .
                $resultado['productos_sin_stock'] .
                ' productos sin existencia.';
        } else {

            $resultado['estado'] = 'INVENTARIO_CONTROLADO';

            $resultado['nivel'] = 'NORMAL';

            $resultado['mensaje'] =
                'El inventario presenta un comportamiento acorde con la rotación registrada.';
        }


        return $resultado;
    }

    /**
     * Identifica productos con inventario pero sin ventas.
     *
     * @param array $inventario
     * @param array $ventasProductos
     * @return array
     */
    public function inventarioSinMovimiento($inventario, $ventasProductos) {
        $resultado = [];

        if (empty($inventario)) {
            return $resultado;
        }

        /*
     * =========================================================
     * INDEXAR VENTAS POR PRODUCTO
     * =========================================================
     */

        $ventasIndexadas = [];

        foreach ($ventasProductos as $venta) {

            $idReferencia = $venta['id_referencia'];

            $ventasIndexadas[$idReferencia] =
                isset($venta['cantidad'])
                ? (float) $venta['cantidad']
                : 0;
        }


        /*
     * =========================================================
     * BUSCAR INVENTARIO SIN MOVIMIENTO
     * =========================================================
     */

        foreach ($inventario as $producto) {

            $idReferencia = $producto['id_referencia'];

            $stock = isset($producto['stock_total'])
                ? (float) $producto['stock_total']
                : 0;

            $valorInventario = isset($producto['valor_inventario'])
                ? (float) $producto['valor_inventario']
                : 0;

            $cantidadVendida =
                $ventasIndexadas[$idReferencia] ?? 0;


            /*
         * Tiene inventario pero no vendió
         */

            if ($stock > 0 && $cantidadVendida <= 0) {

                $resultado[] = [

                    'id_referencia' =>
                    $idReferencia,

                    'codigo' =>
                    $producto['codigo'],

                    'producto' =>
                    $producto['producto'],

                    'stock' =>
                    round($stock, 2),

                    'costo_promedio' =>
                    round(
                        (float) ($producto['costo_promedio'] ?? 0),
                        2
                    ),

                    'valor_inventario' =>
                    round($valorInventario, 2),

                    'cantidad_vendida' =>
                    round($cantidadVendida, 2),

                    'estado' =>
                    'SIN_MOVIMIENTO',

                    'nivel' =>
                    'ALERTA'
                ];
            }
        }


        /*
     * =========================================================
     * ORDENAR POR CAPITAL INMOVILIZADO
     * =========================================================
     */

        usort($resultado, function ($a, $b) {

            return $b['valor_inventario']
                <=> $a['valor_inventario'];
        });


        return $resultado;
    }
}
