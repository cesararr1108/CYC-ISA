<?php

class AnalisisProductos
{
    public function analizarImpacto($impacto)
    {
        if (empty($impacto)) {
            return [
                'estado' => 'SIN_DATOS',
                'nivel' => 'INFORMATIVO',
                'mensaje' => 'No existe información suficiente para analizar el comportamiento de los productos.'
            ];
        }

        $variacion = (float) ($impacto['variacion'] ?? 0);
        $impactoCaidas = (float) ($impacto['impacto_caidas'] ?? 0);
        $impactoCrecimientos = (float) ($impacto['impacto_crecimientos'] ?? 0);

        if ($variacion <= -10) {
            return [
                'estado' => 'CAIDA_CRITICA',
                'nivel' => 'CRITICA',
                'mensaje' => 'Las ventas presentan una caída crítica y los productos en disminución están generando un impacto importante sobre el resultado.'
            ];
        }

        if ($variacion < 0) {
            if (abs($impactoCaidas) > $impactoCrecimientos) {
                return [
                    'estado' => 'CAIDA_POR_PRODUCTOS',
                    'nivel' => 'ALERTA',
                    'mensaje' => 'La disminución de ventas está siendo impulsada principalmente por productos que presentan caída en sus ventas.'
                ];
            }

            return [
                'estado' => 'CAIDA_COMPENSADA',
                'nivel' => 'ADVERTENCIA',
                'mensaje' => 'Las ventas disminuyeron, pero el crecimiento de algunos productos está compensando parcialmente la caída.'
            ];
        }

        if ($variacion >= 10) {
            return [
                'estado' => 'CRECIMIENTO_FUERTE',
                'nivel' => 'POSITIVO',
                'mensaje' => 'Las ventas presentan un crecimiento importante impulsado por el comportamiento positivo de los productos.'
            ];
        }

        if ($variacion > 0) {
            return [
                'estado' => 'CRECIMIENTO',
                'nivel' => 'POSITIVO',
                'mensaje' => 'Las ventas presentan crecimiento frente al período anterior.'
            ];
        }

        return [
            'estado' => 'ESTABLE',
            'nivel' => 'NEUTRO',
            'mensaje' => 'El comportamiento general de las ventas se mantiene estable.'
        ];
    }
}
