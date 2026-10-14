<?php

class AlertasInventario
{
    /**
     * Genera alertas a partir del análisis de inventario.
     *
     * @param array $analisis
     * @return array
     */
    public function generar($analisis)
    {
        $alertas = [];

        if (empty($analisis)) {
            return $alertas;
        }

        $productos = $analisis['productos'] ?? [];

        if (empty($productos)) {
            return $alertas;
        }

        foreach ($productos as $producto) {

            $estado = $producto['estado'] ?? '';

            $stock = (float) ($producto['stock'] ?? 0);
            $valorInventario = (float) ($producto['valor_inventario'] ?? 0);
            $rotacion = $producto['rotacion'] ?? null;
            $diasInventario = $producto['dias_cobertura'] ?? null;

            /*
             * =====================================================
             * 1. STOCK AGOTADO
             * =====================================================
             */

            if ($estado === 'SIN_STOCK') {

                $alertas[] = [
                    'tipo' => 'INVENTARIO',
                    'codigo' => 'PRODUCTO_SIN_STOCK',
                    'nivel' => 'CRITICA',
                    'titulo' => 'Producto sin inventario',
                    'mensaje' => 'El producto ' .
                        $producto['producto'] .
                        ' no tiene existencias disponibles.',
                    'producto' => $producto['producto'],
                    'codigo_producto' => $producto['codigo'],
                    'stock' => $stock,
                    'valor_inventario' => round($valorInventario, 2),
                    'rotacion' => $rotacion,
                    'dias_inventario' => $diasInventario
                ];

                continue;
            }


            /*
             * =====================================================
             * 2. INVENTARIO DE MUY BAJA ROTACIÓN
             * =====================================================
             */

            if (
                $rotacion !== null &&
                $rotacion < 1 &&
                $valorInventario >= 100000
            ) {

                $alertas[] = [
                    'tipo' => 'INVENTARIO',
                    'codigo' => 'INVENTARIO_BAJA_ROTACION',
                    'nivel' => 'ALERTA',
                    'titulo' => 'Inventario de baja rotación',
                    'mensaje' => 'El producto ' .
                        $producto['producto'] .
                        ' presenta una rotación muy baja y mantiene $' .
                        number_format(
                            abs($valorInventario),
                            0,
                            ',',
                            '.'
                        ) .
                        ' en inventario.',
                    'producto' => $producto['producto'],
                    'codigo_producto' => $producto['codigo'],
                    'stock' => $stock,
                    'valor_inventario' => round($valorInventario, 2),
                    'rotacion' => round($rotacion, 2),
                    'dias_inventario' => $diasInventario
                ];

                continue;
            }


            /*
             * =====================================================
             * 3. INVENTARIO EXCESIVO
             * =====================================================
             */

            if (
                $diasInventario !== null &&
                $diasInventario > 180 &&
                $valorInventario >= 100000
            ) {

                $alertas[] = [
                    'tipo' => 'INVENTARIO',
                    'codigo' => 'INVENTARIO_EXCESIVO',
                    'nivel' => 'ALERTA',
                    'titulo' => 'Inventario excesivo',
                    'mensaje' => 'El producto ' .
                        $producto['producto'] .
                        ' tiene aproximadamente ' .
                        number_format($diasInventario, 0, ',', '.') .
                        ' días de inventario.',
                    'producto' => $producto['producto'],
                    'codigo_producto' => $producto['codigo'],
                    'stock' => $stock,
                    'valor_inventario' => round($valorInventario, 2),
                    'rotacion' => $rotacion,
                    'dias_inventario' => round($diasInventario, 2)
                ];

                continue;
            }


            /*
             * =====================================================
             * 4. INVENTARIO SALUDABLE
             * =====================================================
             *
             * No generamos alerta.
             */
        }


        /*
         * =========================================================
         * ORDENAR POR IMPACTO ECONÓMICO
         * =========================================================
         */

        usort($alertas, function ($a, $b) {

            return abs($b['valor_inventario'])
                <=> abs($a['valor_inventario']);
        });


        return $alertas;
    }
}
