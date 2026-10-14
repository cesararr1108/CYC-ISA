<?php

class AlertasVentas
{
    /**
     * Genera todas las alertas relacionadas con ventas.
     */
    public function generar($comparativo)
    {
        $alertas = [];

        /*
         * Alertas derivadas del comparativo
         */
        $alertas = array_merge(
            $alertas,
            $this->analizarComparativo($comparativo)
        );

        return $alertas;
    }


    /**
     * Analiza variación de ventas frente
     * al período anterior.
     */
    public function analizarComparativo($comparativo)
    {
        $alertas = [];

        if (empty($comparativo)) {
            return $alertas;
        }

        $variacion = $comparativo['variacion'] ?? null;

        if ($variacion === null) {
            return $alertas;
        }


        /*
         * =====================================================
         * CAÍDA CRÍTICA
         * =====================================================
         */

        if ($variacion <= -20) {

            $alertas[] = [
                'tipo' => 'VENTAS',
                'codigo' => 'CAIDA_VENTAS_CRITICA',
                'nivel' => 'CRITICA',
                'titulo' => 'Caída crítica de ventas',

                'mensaje' =>
                'Las ventas disminuyeron un ' .
                    number_format(
                        abs($variacion),
                        2,
                        ',',
                        '.'
                    ) .
                    '% frente al período anterior.',

                'variacion' => round($variacion, 2)
            ];
        }


        /*
         * =====================================================
         * CAÍDA IMPORTANTE
         * =====================================================
         */ elseif ($variacion <= -10) {

            $alertas[] = [
                'tipo' => 'VENTAS',
                'codigo' => 'CAIDA_VENTAS',
                'nivel' => 'ALERTA',
                'titulo' => 'Disminución importante de ventas',

                'mensaje' =>
                'Las ventas disminuyeron un ' .
                    number_format(
                        abs($variacion),
                        2,
                        ',',
                        '.'
                    ) .
                    '% frente al período anterior.',

                'variacion' => round($variacion, 2)
            ];
        }


        /*
         * =====================================================
         * CRECIMIENTO IMPORTANTE
         * =====================================================
         */ elseif ($variacion >= 20) {

            $alertas[] = [
                'tipo' => 'VENTAS',
                'codigo' => 'CRECIMIENTO_VENTAS',
                'nivel' => 'POSITIVA',
                'titulo' => 'Crecimiento importante de ventas',

                'mensaje' =>
                'Las ventas aumentaron un ' .
                    number_format(
                        $variacion,
                        2,
                        ',',
                        '.'
                    ) .
                    '% frente al período anterior.',

                'variacion' => round($variacion, 2)
            ];
        }

        return $alertas;
    }
}
