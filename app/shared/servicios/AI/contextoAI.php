<?php

class ContextoAI
{
    /**
     * Construye el contexto empresarial que será enviado a la IA.
     *
     * Esta clase NO consulta directamente la base de datos.
     * Recibe información previamente procesada por ISA Intelligence.
     *
     * @param array $datos
     * @return array
     */
    public function construir($datos)
    {
        return [
            'empresa' => $datos['empresa'] ?? null,

            'bodega' => $datos['bodega'] ?? null,

            'periodo' => [
                'fecha_inicio' => $datos['periodo']['fecha_inicio'] ?? null,
                'fecha_fin' => $datos['periodo']['fecha_fin'] ?? null
            ],

            /*
             * =====================================================
             * VENTAS
             * =====================================================
             */

            'ventas' => $datos['ventas'] ?? [],

            /*
             * =====================================================
             * PRODUCTOS
             * =====================================================
             */

            'productos' => $datos['productos'] ?? [],

            /*
             * =====================================================
             * INVENTARIO
             * =====================================================
             */

            'inventario' => $datos['inventario'] ?? [],

            /*
             * =====================================================
             * ALERTAS
             * =====================================================
             */

            'alertas' => [
                'ventas' => $datos['alertas_ventas'] ?? [],
                'productos' => $datos['alertas_productos'] ?? [],
                'inventario' => $datos['alertas_inventario'] ?? []
            ],

            /*
             * =====================================================
             * FECHA DE GENERACIÓN
             * =====================================================
             */

            'generado' => date('Y-m-d H:i:s')
        ];
    }


    /**
     * Agrega información adicional al contexto.
     *
     * Permite ampliar ISA Intelligence posteriormente
     * sin modificar la estructura principal.
     *
     * @param array $contexto
     * @param string $nombre
     * @param mixed $valor
     * @return array
     */
    public function agregar($contexto, $nombre, $valor)
    {
        $contexto[$nombre] = $valor;

        return $contexto;
    }


    /**
     * Devuelve el contexto en formato JSON.
     *
     * Este método será utilizado posteriormente por IAService
     * para enviar la información al modelo de IA.
     *
     * @param array $contexto
     * @return string
     */
    public function comoJson($contexto)
    {
        return json_encode(
            $contexto,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES |
            JSON_PRETTY_PRINT
        );
    }
}