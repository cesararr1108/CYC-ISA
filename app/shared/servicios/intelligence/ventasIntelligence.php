<?php

class VentasIntelligence
{
    private $intelligence;

    public function __construct($intelligence)
    {
        $this->intelligence = $intelligence;
    }

    /**
     * Ventas de un período.
     */
    public function ventasPeriodo($fechaInicial, $fechaFinal, $bodega)
    {
        $sql = "SELECT 
                        ROUND(
                            SUM(
                                (
                                    CASE 
                                        WHEN d.sw = 5 
                                        THEN d.valor_unitario *
                                            (1 - (d.pcj_descuento / 100))

                                        ELSE
                                            (
                                                d.valor_unitario *
                                                (1 - (d.pcj_descuento / 100))
                                            ) * -1
                                    END
                                ) * d.cantidad
                            ),
                            2
                        ) AS venta,
                        ROUND(
                            SUM(
                                CASE 
                                    WHEN d.sw = 5 THEN
                                        (
                                            d.valor_unitario *
                                            (1 - (d.pcj_descuento / 100)) *
                                            (1 + (d.pcj_iva / 100)) *
                                            d.cantidad
                                        )

                                    ELSE
                                        (
                                            d.valor_unitario *
                                            (1 - (d.pcj_descuento / 100)) *
                                            (1 + (d.pcj_iva / 100)) *
                                            d.cantidad
                                        ) * -1
                                END
                            ),
                            2
                        ) AS venta_iva
                    FROM t_transacciones_detalle d
                    INNER JOIN t_transacciones t
                        ON t.id = d.id_transaccion
                        AND IFNULL(t.anulado, 0) = 0
                    WHERE d.sw IN (5, 4)
                        AND d.fec >= ?
                        AND d.fec <= ?
                        AND d.id_bodega = ?";

        return $this->intelligence->consultarPreparada(
            $sql,
            [
                $fechaInicial,
                $fechaFinal,
                $bodega
            ],
            'ssi'
        );
    }
    /**
     * Resumen de ventas de un período.
     */
    public function resumenVentas($fechaInicial, $fechaFinal, $bodega)
    {
        $datos = $this->ventasPeriodo(
            $fechaInicial,
            $fechaFinal,
            $bodega
        );

        if (empty($datos)) {
            return [
                'venta_total' => 0,
                'venta_total_iva' => 0
            ];
        }

        return [
            'venta_total' => round((float) $datos[0]['venta'], 2),
            'venta_total_iva' => round((float) $datos[0]['venta_iva'], 2)
        ];
    }
    /**
     * Comparación de ventas.
     */
    public function comparativo($fechaInicio, $fechaFin, $bodega)
    {
        $inicio = new DateTime($fechaInicio);
        $fin = new DateTime($fechaFin);

        /*
     * Período anterior:
     * mismas fechas del mes anterior
     */

        $inicioAnterior = clone $inicio;
        $finAnterior = clone $fin;

        $inicioAnterior->modify('-1 month');
        $finAnterior->modify('-1 month');

        /*
     * Obtener ventas del período actual
     */
        $actual = $this->ventasPeriodo(
            $fechaInicio,
            $fechaFin,
            $bodega
        );

        /*
     * Obtener ventas del mismo período
     * del mes anterior
     */
        $anterior = $this->ventasPeriodo(
            $inicioAnterior->format('Y-m-d'),
            $finAnterior->format('Y-m-d'),
            $bodega
        );

        $ventaActual = !empty($actual)
            ? (float) $actual[0]['venta']
            : 0;

        $ventaAnterior = !empty($anterior)
            ? (float) $anterior[0]['venta']
            : 0;

        $ivaActual = !empty($actual)
            ? (float) $actual[0]['venta_iva']
            : 0;

        $ivaAnterior = !empty($anterior)
            ? (float) $anterior[0]['venta_iva']
            : 0;

        /*
     * Calcular variación
     */
        if ($ventaAnterior != 0) {

            $variacion = (
                ($ventaActual - $ventaAnterior)
                / $ventaAnterior
            ) * 100;
        } else {

            $variacion = null;
        }

        /*
     * Determinar tendencia
     */
        if ($variacion === null) {

            $tendencia = 'SIN_REFERENCIA';
        } elseif ($variacion > 0) {

            $tendencia = 'CRECIMIENTO';
        } elseif ($variacion < 0) {

            $tendencia = 'DISMINUCION';
        } else {

            $tendencia = 'ESTABLE';
        }

        return [

            'periodo_actual' => [

                'fecha_inicio' => $fechaInicio,

                'fecha_fin' => $fechaFin,

                'venta' => round(
                    $ventaActual,
                    2
                ),

                'venta_iva' => round(
                    $ivaActual,
                    2
                )
            ],

            'periodo_anterior' => [

                'fecha_inicio' =>
                $inicioAnterior->format('Y-m-d'),

                'fecha_fin' =>
                $finAnterior->format('Y-m-d'),

                'venta' => round(
                    $ventaAnterior,
                    2
                ),

                'venta_iva' => round(
                    $ivaAnterior,
                    2
                )
            ],

            'variacion' => $variacion !== null
                ? round($variacion, 2)
                : null,

            'tendencia' => $tendencia
        ];
    }
    /**
     * Ventas por producto.
     */
    public function ventasPorProducto($fechaInicial, $fechaFinal, $bodega = 0)
    {
        $sql = "SELECT  
                    r.id AS id_referencia, 
                    r.codigo, 
                    r.descripcion AS producto, 
                    d.id_bodega, 
                    b.descripcion AS desc_bodega, 

                    ROUND( 
                        SUM( 
                            CASE  
                                WHEN d.sw = 5 THEN 
                                    (
                                        d.valor_unitario * 
                                        (1 - (d.pcj_descuento / 100))
                                    ) * d.cantidad 
                                ELSE 
                                    (
                                        d.valor_unitario * 
                                        (1 - (d.pcj_descuento / 100))
                                    ) * d.cantidad * -1 
                            END 
                        ), 
                        2 
                    ) AS venta, 

                    ROUND( 
                        SUM( 
                            CASE  
                                WHEN d.sw = 5 THEN 
                                    d.costo_unitario * d.cantidad 
                                ELSE 
                                    d.costo_unitario * d.cantidad * -1 
                            END 
                        ), 
                        2 
                    ) AS costo, 

                    ROUND( 
                        SUM( 
                            CASE  
                                WHEN d.sw = 5 THEN 
                                    (
                                        (
                                            d.valor_unitario * 
                                            (1 - (d.pcj_descuento / 100))
                                        ) - d.costo_unitario
                                    ) * d.cantidad 
                                ELSE 
                                    (
                                        (
                                            d.valor_unitario * 
                                            (1 - (d.pcj_descuento / 100))
                                        ) - d.costo_unitario
                                    ) * d.cantidad * -1 
                            END 
                        ), 
                        2 
                    ) AS utilidad, 

                    CASE  
                        WHEN SUM( 
                            CASE  
                                WHEN d.sw = 5 THEN 
                                    (
                                        d.valor_unitario * 
                                        (1 - (d.pcj_descuento / 100))
                                    ) * d.cantidad 
                                ELSE 
                                    (
                                        d.valor_unitario * 
                                        (1 - (d.pcj_descuento / 100))
                                    ) * d.cantidad * -1 
                            END 
                        ) = 0 THEN 0 

                        ELSE ROUND( 
                            (
                                SUM( 
                                    CASE  
                                        WHEN d.sw = 5 THEN 
                                            (
                                                (
                                                    d.valor_unitario * 
                                                    (1 - (d.pcj_descuento / 100))
                                                ) - d.costo_unitario
                                            ) * d.cantidad 
                                        ELSE 
                                            (
                                                (
                                                    d.valor_unitario * 
                                                    (1 - (d.pcj_descuento / 100))
                                                ) - d.costo_unitario
                                            ) * d.cantidad * -1 
                                    END 
                                )
                                /
                                SUM( 
                                    CASE  
                                        WHEN d.sw = 5 THEN 
                                            (
                                                d.valor_unitario * 
                                                (1 - (d.pcj_descuento / 100))
                                            ) * d.cantidad 
                                        ELSE 
                                            (
                                                d.valor_unitario * 
                                                (1 - (d.pcj_descuento / 100))
                                            ) * d.cantidad * -1 
                                    END 
                                )
                            ) * 100, 
                            2 
                        ) 
                    END AS margen, 

                    SUM(d.cantidad) AS cantidad 

                FROM t_transacciones_detalle d 

                INNER JOIN t_transacciones t 
                    ON t.id = d.id_transaccion 
                    AND IFNULL(t.anulado, 0) = 0 

                LEFT JOIN t_condiciones_pago c 
                    ON c.id = t.id_condicion 

                INNER JOIN t_referencias r 
                    ON d.id_referencia = r.id 

                INNER JOIN t_almacenes b 
                    ON b.id = d.id_bodega 

                WHERE d.sw IN (5, 4) 

                AND CAST(d.fec AS DATE) BETWEEN ? AND ?";

        // Parámetros
        $params = [
            $fechaInicial,
            $fechaFinal
        ];

        // Tipos de parámetros
        $types = "ss";

        // Filtro por bodega
        if ($bodega != 0) {

            $sql .= " AND d.id_bodega = ? ";

            $params[] = $bodega;

            $types .= "i";
        }

        $sql .= " 
            GROUP BY 
                r.id, 
                r.codigo, 
                r.descripcion, 
                d.id_bodega, 
                b.descripcion 

            HAVING 
                ROUND( 
                    SUM( 
                        CASE 
                            WHEN d.sw = 5 THEN 
                                (
                                    d.valor_unitario * 
                                    (1 - (d.pcj_descuento / 100))
                                ) * d.cantidad 
                            ELSE 
                                (
                                    d.valor_unitario * 
                                    (1 - (d.pcj_descuento / 100))
                                ) * d.cantidad * -1 
                        END 
                    ), 
                    2 
                ) > 0 

            ORDER BY r.descripcion ASC";

        return $this->intelligence->consultarPreparada(
            $sql,
            $params,
            $types
        );
    }

    public function comparativoProductosPromedio() {}

    public function impactoProductos($comparativoProductos)
    {
        $resultado = [

            'venta_actual' => 0,

            'venta_anterior' => 0,

            'variacion' => null,

            'impacto_total' => 0,

            'impacto_caidas' => 0,

            'impacto_crecimientos' => 0,

            'productos_caida' => 0,

            'productos_crecimiento' => 0,

            'productos_sin_ventas_actuales' => 0
        ];


        if (empty($comparativoProductos['productos'])) {
            return $resultado;
        }


        /*
     * Si no existe histórico suficiente,
     * no hacemos análisis comparativo.
     */

        if (
            isset($comparativoProductos['historico_suficiente']) &&
            !$comparativoProductos['historico_suficiente']
        ) {

            $resultado['historico_suficiente'] = false;

            return $resultado;
        }


        $resultado['historico_suficiente'] = true;


        /*
     * Recorrer productos.
     */

        foreach ($comparativoProductos['productos'] as $producto) {

            $ventaActual =
                (float)$producto['venta_actual'];

            $ventaAnterior =
                (float)$producto['venta_anterior'];

            $impacto =
                (float)$producto['impacto'];


            /*
         * Totales de ventas.
         */

            $resultado['venta_actual'] += $ventaActual;

            $resultado['venta_anterior'] += $ventaAnterior;


            /*
         * Impacto total.
         */

            $resultado['impacto_total'] += $impacto;


            /*
         * Impacto negativo.
         */

            if ($impacto < 0) {

                $resultado['impacto_caidas'] += $impacto;

                $resultado['productos_caida']++;
            }


            /*
         * Impacto positivo.
         */ elseif ($impacto > 0) {

                $resultado['impacto_crecimientos'] += $impacto;

                $resultado['productos_crecimiento']++;
            }


            /*
         * Productos que dejaron de vender.
         */

            if (
                $producto['estado'] ===
                'SIN_VENTAS_ACTUALES'
            ) {

                $resultado['productos_sin_ventas_actuales']++;
            }
        }


        /*
     * Redondear valores.
     */

        $resultado['venta_actual'] =
            round($resultado['venta_actual'], 2);

        $resultado['venta_anterior'] =
            round($resultado['venta_anterior'], 2);

        $resultado['impacto_total'] =
            round($resultado['impacto_total'], 2);

        $resultado['impacto_caidas'] =
            round($resultado['impacto_caidas'], 2);

        $resultado['impacto_crecimientos'] =
            round($resultado['impacto_crecimientos'], 2);


        /*
     * Variación general.
     */

        if ($resultado['venta_anterior'] != 0) {

            $resultado['variacion'] = round(
                (
                    (
                        $resultado['venta_actual']
                        -
                        $resultado['venta_anterior']
                    )
                    /
                    $resultado['venta_anterior']
                ) * 100,
                2
            );
        }


        return $resultado;
    }
    public function productosCrecimiento($comparativoProductos, $limite = 20)
    {
        $resultado = [];

        if (empty($comparativoProductos['productos'])) {
            return $resultado;
        }

        foreach ($comparativoProductos['productos'] as $producto) {

            /*
         * No analizar crecimiento si no existe
         * histórico suficiente.
         */
            if (
                isset($comparativoProductos['historico_suficiente']) &&
                !$comparativoProductos['historico_suficiente']
            ) {
                continue;
            }

            /*
         * Estados de crecimiento.
         */
            $estadosCrecimiento = [
                'CRECIMIENTO',
                'CRECIMIENTO_FUERTE'
            ];

            if (!in_array($producto['estado'], $estadosCrecimiento)) {
                continue;
            }

            /*
         * El impacto debe ser positivo.
         */
            if ((float)$producto['impacto'] <= 0) {
                continue;
            }

            $resultado[] = [
                'id_referencia' => $producto['id_referencia'],
                'codigo' => $producto['codigo'],
                'producto' => $producto['producto'],
                'id_bodega' => $producto['id_bodega'],
                'desc_bodega' => $producto['desc_bodega'],

                'venta_actual' => $producto['venta_actual'],
                'venta_anterior' => $producto['venta_anterior'],

                'impacto' => $producto['impacto'],
                'variacion' => $producto['variacion'],

                'estado' => $producto['estado']
            ];
        }

        /*
     * Ordenar desde el mayor crecimiento monetario.
     */

        usort(
            $resultado,
            function ($a, $b) {
                return $b['impacto'] <=> $a['impacto'];
            }
        );

        /*
     * Limitar resultados.
     */

        if ($limite > 0) {
            $resultado = array_slice($resultado, 0, $limite);
        }

        return $resultado;
    }
    public function productosCaida($comparativoProductos, $limite = 20)
    {
        $resultado = [];

        if (empty($comparativoProductos['productos'])) {
            return $resultado;
        }

        foreach ($comparativoProductos['productos'] as $producto) {

            /*
         * Solo analizamos productos con histórico suficiente.
         */
            if (
                isset($comparativoProductos['historico_suficiente']) &&
                !$comparativoProductos['historico_suficiente']
            ) {
                continue;
            }

            /*
         * Productos que presentan disminución.
         */
            $estadosCaida = [
                'CAIDA',
                'CAIDA_FUERTE',
                'SIN_VENTAS_ACTUALES'
            ];

            if (!in_array($producto['estado'], $estadosCaida)) {
                continue;
            }

            /*
         * Solo productos cuyo impacto sea negativo.
         */
            if ((float)$producto['impacto'] >= 0) {
                continue;
            }

            $resultado[] = [
                'id_referencia' => $producto['id_referencia'],
                'codigo' => $producto['codigo'],
                'producto' => $producto['producto'],
                'id_bodega' => $producto['id_bodega'],
                'desc_bodega' => $producto['desc_bodega'],

                'venta_actual' => $producto['venta_actual'],
                'venta_anterior' => $producto['venta_anterior'],

                'impacto' => $producto['impacto'],
                'variacion' => $producto['variacion'],

                'estado' => $producto['estado']
            ];
        }

        /*
     * Ordenar desde la mayor pérdida monetaria.
     *
     * Ejemplo:
     *
     * -10.000.000
     * -5.000.000
     * -500.000
     */

        usort(
            $resultado,
            function ($a, $b) {
                return $a['impacto'] <=> $b['impacto'];
            }
        );

        /*
     * Limitar resultados.
     */

        if ($limite > 0) {
            $resultado = array_slice($resultado, 0, $limite);
        }

        return $resultado;
    }

    public function comparativoProductos($fechaInicial, $fechaFinal, $bodega = 0, $tipoComparativo = 'MES_ANTERIOR')
    {
        /*
     * =====================================================
     * 1. Fechas del período actual
     * =====================================================
     */

        $inicioActual = new DateTime($fechaInicial);
        $finActual = new DateTime($fechaFinal);


        /*
     * Cantidad de días del período actual
     *
     * Ejemplo:
     *
     * 01/08/2026 - 25/08/2026
     *
     * = 25 días
     */

        $diasPeriodo = $inicioActual->diff($finActual)->days + 1;


        /*
     * =====================================================
     * 2. Determinar período anterior
     * =====================================================
     *
     * MES_ANTERIOR:
     *
     * 01/08/2026 - 25/08/2026
     *
     * se compara contra:
     *
     * 01/07/2026 - 25/07/2026
     */

        switch ($tipoComparativo) {

            case 'MES_ANTERIOR':

                /*
             * Primer día del mes anterior
             */

                $inicioAnterior = clone $inicioActual;

                $inicioAnterior->modify('first day of previous month');


                /*
             * Mantener el mismo número de día
             *
             * Si actual:
             *
             * 01/08 - 25/08
             *
             * anterior:
             *
             * 01/07 - 25/07
             */

                $finAnterior = clone $inicioAnterior;

                $finAnterior->modify(
                    '+' . ($diasPeriodo - 1) . ' days'
                );

                break;


            case 'PROMEDIO_3_MESES':

                /*
             * Esta opción la dejamos preparada.
             *
             * El período anterior será calculado
             * posteriormente mediante varios meses.
             */

                return $this->comparativoProductosPromedio(
                    $fechaInicial,
                    $fechaFinal,
                    $bodega,
                    3
                );


            case 'PROMEDIO_6_MESES':

                return $this->comparativoProductosPromedio(
                    $fechaInicial,
                    $fechaFinal,
                    $bodega,
                    6
                );


            default:

                /*
             * Si llega un tipo desconocido,
             * utilizamos MES_ANTERIOR.
             */

                $inicioAnterior = clone $inicioActual;

                $inicioAnterior->modify(
                    'first day of previous month'
                );

                $finAnterior = clone $inicioAnterior;

                $finAnterior->modify(
                    '+' . ($diasPeriodo - 1) . ' days'
                );

                $tipoComparativo = 'MES_ANTERIOR';

                break;
        }


        /*
     * =====================================================
     * 3. Validar fechas
     * =====================================================
     */

        if ($finAnterior < $inicioAnterior) {

            return [
                'historico_suficiente' => false,

                'tipo_comparativo' => $tipoComparativo,

                'periodo_actual' => [
                    'fecha_inicio' => $inicioActual->format('Y-m-d'),
                    'fecha_fin' => $finActual->format('Y-m-d')
                ],

                'periodo_anterior' => [
                    'fecha_inicio' => $inicioAnterior->format('Y-m-d'),
                    'fecha_fin' => $finAnterior->format('Y-m-d')
                ],

                'productos' => []
            ];
        }


        /*
     * =====================================================
     * 4. Obtener primera fecha de ventas
     * =====================================================
     */

        $fechaPrimeraVenta = $this->fechaPrimeraVenta();


        /*
     * =====================================================
     * 5. Determinar si existe histórico suficiente
     * =====================================================
     */

        $historicoSuficiente = true;

        if ($fechaPrimeraVenta === null) {

            $historicoSuficiente = false;
        } else {

            $primeraFecha = new DateTime($fechaPrimeraVenta);

            if ($primeraFecha > $inicioAnterior) {

                $historicoSuficiente = false;
            }
        }


        /*
     * =====================================================
     * 6. Obtener productos actuales
     * =====================================================
     */

        $productosActuales = $this->ventasPorProducto(
            $inicioActual->format('Y-m-d'),
            $finActual->format('Y-m-d'),
            $bodega
        );


        /*
     * =====================================================
     * 7. Obtener productos anteriores
     * =====================================================
     */

        $productosAnteriores = [];

        if ($historicoSuficiente) {

            $productosAnteriores = $this->ventasPorProducto(
                $inicioAnterior->format('Y-m-d'),
                $finAnterior->format('Y-m-d'),
                $bodega
            );
        }


        /*
     * =====================================================
     * 8. Indexar productos actuales
     * =====================================================
     */

        $actual = [];

        foreach ($productosActuales as $producto) {

            $id = $producto['id_referencia'];

            $actual[$id] = $producto;
        }


        /*
     * =====================================================
     * 9. Indexar productos anteriores
     * =====================================================
     */

        $anterior = [];

        foreach ($productosAnteriores as $producto) {

            $id = $producto['id_referencia'];

            $anterior[$id] = $producto;
        }


        /*
     * =====================================================
     * 10. Unir productos
     * =====================================================
     */

        $idsProductos = array_unique(
            array_merge(
                array_keys($actual),
                array_keys($anterior)
            )
        );


        $resultado = [];


        /*
     * =====================================================
     * 11. Comparar productos
     * =====================================================
     */

        foreach ($idsProductos as $id) {

            $productoActual =
                $actual[$id] ?? null;

            $productoAnterior =
                $anterior[$id] ?? null;


            /*
         * Utilizamos la información actual
         * como base cuando existe.
         */

            $productoBase =
                $productoActual ??
                $productoAnterior;


            $ventaActual =
                $productoActual
                ? (float)$productoActual['venta']
                : 0;


            $ventaAnterior =
                $productoAnterior
                ? (float)$productoAnterior['venta']
                : 0;


            /*
         * =================================================
         * Determinar estado
         * =================================================
         */

            $variacion = null;


            /*
         * No existe histórico
         */

            if (!$historicoSuficiente) {

                $estado = 'SIN_HISTORICO';
            }


            /*
         * Existe histórico
         */ else {

                /*
             * Producto sin venta anterior
             */

                if ($ventaAnterior == 0) {

                    if ($ventaActual > 0) {

                        $estado = 'NUEVO';
                    } else {

                        $estado = 'SIN_VENTAS';
                    }
                }


                /*
             * Existe venta anterior
             */ else {

                    $variacion = round(
                        (
                            ($ventaActual - $ventaAnterior)
                            / $ventaAnterior
                        ) * 100,
                        2
                    );


                    /*
                 * Dejó de vender
                 */

                    if ($ventaActual == 0) {

                        $estado = 'SIN_VENTAS_ACTUALES';
                    }


                    /*
                 * Caída fuerte
                 */ elseif ($variacion <= -10) {

                        $estado = 'CAIDA_FUERTE';
                    }


                    /*
                 * Caída
                 */ elseif ($variacion < -5) {

                        $estado = 'CAIDA';
                    }


                    /*
                 * Crecimiento fuerte
                 */ elseif ($variacion >= 10) {

                        $estado = 'CRECIMIENTO_FUERTE';
                    }


                    /*
                 * Crecimiento
                 */ elseif ($variacion > 5) {

                        $estado = 'CRECIMIENTO';
                    }


                    /*
                 * Estable
                 */ else {

                        $estado = 'ESTABLE';
                    }
                }
            }


            /*
         * =================================================
         * 12. Impacto económico
         * =================================================
         */

            $impacto = round(
                $ventaActual - $ventaAnterior,
                2
            );


            /*
         * =================================================
         * 13. Agregar resultado
         * =================================================
         */

            $resultado[] = [

                'id_referencia' =>
                $productoBase['id_referencia'],

                'codigo' =>
                $productoBase['codigo'],

                'producto' =>
                $productoBase['producto'],

                'id_bodega' =>
                $productoBase['id_bodega'],

                'desc_bodega' =>
                $productoBase['desc_bodega'],

                'venta_actual' =>
                round($ventaActual, 2),

                'venta_anterior' =>
                round($ventaAnterior, 2),

                'impacto' =>
                $impacto,

                'variacion' =>
                $variacion,

                'estado' =>
                $estado
            ];
        }


        /*
     * =====================================================
     * 14. Ordenar por impacto
     * =====================================================
     */

        usort(
            $resultado,
            function ($a, $b) {

                return $a['impacto'] <=> $b['impacto'];
            }
        );


        /*
     * =====================================================
     * 15. Resultado final
     * =====================================================
     */

        return [

            'historico_suficiente' =>
            $historicoSuficiente,

            'fecha_primera_venta' =>
            $fechaPrimeraVenta,

            'tipo_comparativo' =>
            $tipoComparativo,

            'periodo_actual' => [

                'fecha_inicio' =>
                $inicioActual->format('Y-m-d'),

                'fecha_fin' =>
                $finActual->format('Y-m-d')
            ],

            'periodo_anterior' => [

                'fecha_inicio' =>
                $inicioAnterior->format('Y-m-d'),

                'fecha_fin' =>
                $finAnterior->format('Y-m-d')
            ],

            'productos' =>
            $resultado
        ];
    }

    public function fechaPrimeraVenta()
    {
        $sql = "SELECT MIN(CAST(d.fec AS DATE)) AS fecha_minima
            FROM t_transacciones_detalle d
            INNER JOIN t_transacciones t
                ON t.id = d.id_transaccion
                AND IFNULL(t.anulado, 0) = 0
            WHERE d.sw IN (5, 4)";

        $resultado = $this->intelligence->consultarPreparada(
            $sql,
            [],
            ""
        );

        if (empty($resultado)) {
            return null;
        }

        return $resultado[0]['fecha_minima'] ?? null;
    }
}
