<?php

class AnalisisVentas
{
    /**
     * Analiza el comparativo de ventas.
     */
    public function analizarComparativo($comparativo)
    {
        $variacion = $comparativo['variacion'] ?? null;

        /*
         * No existe información suficiente
         */
        if ($variacion === null) {

            return [
                'estado' => 'SIN_REFERENCIA',
                'nivel' => 'INFORMATIVO',
                'mensaje' => 'No existe información suficiente para realizar el comparativo.'
            ];
        }

        /*
         * CRECIMIENTO FUERTE
         * Mayor o igual al 10%
         */
        if ($variacion >= 10) {

            return [
                'estado' => 'CRECIMIENTO_FUERTE',
                'nivel' => 'POSITIVO',
                'mensaje' => 'Las ventas presentan un crecimiento fuerte frente al período anterior.'
            ];
        }

        /*
         * CRECIMIENTO
         * Entre 5% y 10%
         */
        if ($variacion >= 5) {

            return [
                'estado' => 'CRECIMIENTO',
                'nivel' => 'POSITIVO',
                'mensaje' => 'Las ventas presentan crecimiento frente al período anterior.'
            ];
        }

        /*
         * ESTABLE
         * Entre -5% y +5%
         */
        if ($variacion >= -5) {

            return [
                'estado' => 'ESTABLE',
                'nivel' => 'NEUTRO',
                'mensaje' => 'Las ventas se mantienen estables frente al período anterior.'
            ];
        }

        /*
         * CAÍDA
         * Entre -5% y -10%
         */
        if ($variacion > -10) {

            return [
                'estado' => 'CAIDA',
                'nivel' => 'ADVERTENCIA',
                'mensaje' => 'Las ventas presentan una disminución frente al período anterior.'
            ];
        }

        /*
         * CAÍDA FUERTE
         * Menor o igual a -10%
         */
        return [
            'estado' => 'CAIDA_FUERTE',
            'nivel' => 'ALERTA',
            'mensaje' => 'Las ventas presentan una caída importante frente al período anterior.'
        ];
    }
}