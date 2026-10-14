<?php

class AlertasProductos
{
    /**
     * Genera alertas a partir del comparativo de productos.
     *
     * @param array $comparativoProductos
     * @return array
     */
    public function generar($comparativoProductos)
    {
        $alertas = [];

        if (empty($comparativoProductos)) {
            return $alertas;
        }

        $productos = $comparativoProductos['productos'] ?? [];

        if (empty($productos)) {
            return $alertas;
        }

        foreach ($productos as $producto) {

            $variacion = isset($producto['variacion'])
                ? (float) $producto['variacion']
                : null;

            $impacto = isset($producto['impacto'])
                ? (float) $producto['impacto']
                : 0;

            $ventaActual = isset($producto['venta_actual'])
                ? (float) $producto['venta_actual']
                : 0;

            $ventaAnterior = isset($producto['venta_anterior'])
                ? (float) $producto['venta_anterior']
                : 0;

            $estado = $producto['estado'] ?? '';

            /*
         * =====================================================
         * 1. PRODUCTO SIN VENTAS ACTUALES
         * =====================================================
         */

            if (
                $estado === 'SIN_VENTAS_ACTUALES' &&
                $ventaAnterior > 0
            ) {

                $alertas[] = [
                    'tipo' => 'PRODUCTOS',
                    'codigo' => 'PRODUCTO_SIN_VENTAS',
                    'nivel' => 'CRITICA',
                    'titulo' => 'Producto sin ventas',
                    'mensaje' => 'El producto ' .
                        $producto['producto'] .
                        ' no registra ventas en el período actual y anteriormente vendió $' .
                        number_format($ventaAnterior, 0, ',', '.') . '.',
                    'producto' => $producto['producto'],
                    'codigo_producto' => $producto['codigo'],
                    'impacto' => round($impacto, 2),
                    'variacion' => $variacion
                ];

                continue;
            }


            /*
         * =====================================================
         * 2. CAÍDA CRÍTICA
         * =====================================================
         */

            if (
                $variacion !== null &&
                $variacion <= -70 &&
                $impacto <= -100000
            ) {

                $alertas[] = [
                    'tipo' => 'PRODUCTOS',
                    'codigo' => 'CAIDA_PRODUCTO_CRITICA',
                    'nivel' => 'CRITICA',
                    'titulo' => 'Caída crítica de producto',
                    'mensaje' => 'El producto ' .
                        $producto['producto'] .
                        ' presenta una caída del ' .
                        number_format(abs($variacion), 2, ',', '.') .
                        '% con un impacto negativo de $' .
                        number_format(abs($impacto), 0, ',', '.') . '.',
                    'producto' => $producto['producto'],
                    'codigo_producto' => $producto['codigo'],
                    'impacto' => round($impacto, 2),
                    'variacion' => round($variacion, 2)
                ];

                continue;
            }


            /*
         * =====================================================
         * 3. CAÍDA IMPORTANTE
         * =====================================================
         */

            if (
                $variacion !== null &&
                $variacion <= -30 &&
                $impacto <= -50000
            ) {

                $alertas[] = [
                    'tipo' => 'PRODUCTOS',
                    'codigo' => 'CAIDA_PRODUCTO',
                    'nivel' => 'ALERTA',
                    'titulo' => 'Disminución importante de producto',
                    'mensaje' => 'El producto ' .
                        $producto['producto'] .
                        ' disminuyó un ' .
                        number_format(abs($variacion), 2, ',', '.') .
                        '% y genera un impacto negativo de $' .
                        number_format(abs($impacto), 0, ',', '.') . '.',
                    'producto' => $producto['producto'],
                    'codigo_producto' => $producto['codigo'],
                    'impacto' => round($impacto, 2),
                    'variacion' => round($variacion, 2)
                ];

                continue;
            }


            /*
         * =====================================================
         * 4. CRECIMIENTO FUERTE
         * =====================================================
         */

            if (
                $variacion !== null &&
                $variacion >= 100 &&
                $impacto >= 50000
            ) {

                $alertas[] = [
                    'tipo' => 'PRODUCTOS',
                    'codigo' => 'CRECIMIENTO_PRODUCTO',
                    'nivel' => 'POSITIVA',
                    'titulo' => 'Crecimiento importante de producto',
                    'mensaje' => 'El producto ' .
                        $producto['producto'] .
                        ' aumentó sus ventas un ' .
                        number_format($variacion, 2, ',', '.') .
                        '% generando $' .
                        number_format($impacto, 0, ',', '.') .
                        ' adicionales.',
                    'producto' => $producto['producto'],
                    'codigo_producto' => $producto['codigo'],
                    'impacto' => round($impacto, 2),
                    'variacion' => round($variacion, 2)
                ];

                continue;
            }
        }


        /*
     * =========================================================
     * 5. ORDENAR POR IMPACTO ECONÓMICO
     * =========================================================
     */

        usort($alertas, function ($a, $b) {

            /*
         * Primero nivel de alerta
         */
            $prioridad = [
                'CRITICA' => 3,
                'ALERTA' => 2,
                'POSITIVA' => 1
            ];

            $nivelA = $prioridad[$a['nivel']] ?? 0;
            $nivelB = $prioridad[$b['nivel']] ?? 0;

            if ($nivelA !== $nivelB) {
                return $nivelB <=> $nivelA;
            }

            /*
         * Si tienen el mismo nivel,
         * ordenar por impacto económico
         */
            return abs($b['impacto']) <=> abs($a['impacto']);
        });


        /*
     * =========================================================
     * 6. LIMITAR ALERTAS
     * =========================================================
     *
     * Solo devolvemos las 20 alertas más importantes.
     */

        $alertas = array_slice($alertas, 0, 20);


        return $alertas;
    }
}
