<?php
session_start();
require_once('bd.php');
require_once('funciones.php');

$conn = conectar();

if (!sessionExists()) {
    echo json_encode(array(
        'error' => true,
        'message' => 'La sesion se ha cerrado, por favor ingrese nuevamente'
    ));
    return;
}

if ($_POST["op"] == 'consultar') {

    $fhIni      = $_POST["fecha_inicio"];
    $fhFin      = $_POST["fecha_fin"];
    $id_bodega  = intval($_POST["id_bodega"]);
    $id_marca   = intval($_POST["id_marca"]);

    /* =====================================================
       FILTROS PRINCIPALES
       ===================================================== */

    $filtro_bodega = "";
    $filtro_marca  = "";

    /* Filtros para el subquery del total */
    $filtro_bodega_total = "";
    $filtro_marca_total  = "";


    /* =====================================================
       FILTRO BODEGA
       ===================================================== */

    if ($id_bodega > 0) {

        $filtro_bodega = "
            AND d.id_bodega = {$id_bodega}
        ";

        $filtro_bodega_total = "
            AND d2.id_bodega = {$id_bodega}
        ";
    }


    /* =====================================================
       FILTRO MARCA
       ===================================================== */

    if ($id_marca > 0) {

        $filtro_marca = "
            AND COALESCE(m.id,0) = {$id_marca}
        ";

        $filtro_marca_total = "
            AND COALESCE(m2.id,0) = {$id_marca}
        ";
    }


    /* =====================================================
       CONSULTA
       ===================================================== */

    $sql = "SELECT
            x.codigo,
            x.marca,
            ROUND(
                SUM(x.unidades),
                2
            ) AS unidades,
            ROUND(
                SUM(x.compra_bruta),
                2
            ) AS compra_bruta,
            ROUND(
                SUM(x.devoluciones),
                2
            ) AS devoluciones,
            ROUND(
                SUM(x.compra_neta),
                2
            ) AS compra_neta,
            ROUND(
                SUM(x.impuestos),
                2
            ) AS impuestos,
            ROUND(
                SUM(x.costo_total),
                2
            ) AS costo_total,

            /* =================================================
               PARTICIPACION
               ================================================= */

            ROUND(

                (

                    SUM(x.compra_neta)

                    /

                    NULLIF(

                        (

                            SELECT

                                SUM(

                                    CASE

                                        WHEN d2.sw = 1 THEN

                                            (
                                                (
                                                    d2.valor_unitario
                                                    *
                                                    (
                                                        1 -
                                                        (
                                                            d2.pcj_descuento / 100
                                                        )
                                                    )
                                                )
                                                *
                                                d2.cantidad
                                            )

                                        ELSE

                                            (
                                                (
                                                    d2.valor_unitario
                                                    *
                                                    (
                                                        1 -
                                                        (
                                                            d2.pcj_descuento / 100
                                                        )
                                                    )
                                                )
                                                *
                                                d2.cantidad
                                            )
                                            *
                                            -1

                                    END

                                )

                            FROM t_transacciones_detalle d2


                            /* =================================
                               TRANSACCION
                               ================================= */

                            INNER JOIN t_transacciones t2

                                ON t2.id = d2.id_transaccion

                               AND IFNULL(
                                    t2.anulado,
                                    0
                               ) = 0


                            /* =================================
                               REFERENCIA
                               ================================= */

                            INNER JOIN t_referencias r2

                                ON r2.id = d2.id_referencia


                            /* =================================
                               MARCA
                               ================================= */

                            LEFT JOIN t_marcas m2

                                ON m2.id = r2.id_marca


                            /* =================================
                               FILTROS TOTAL
                               ================================= */

                            WHERE d2.sw IN (1,7)

                              AND CAST(d2.fec AS DATE)
                                  BETWEEN '{$fhIni}'
                                  AND '{$fhFin}'

                              {$filtro_bodega_total}

                              {$filtro_marca_total}

                        ),

                        0

                    )

                ) * 100,

                2

            ) AS participacion


        /* =====================================================
           CONSULTA PRINCIPAL
           ===================================================== */

        FROM

        (

            SELECT


                /* =============================================
                   CODIGO MARCA
                   ============================================= */

                COALESCE(
                    m.id,
                    0
                ) AS codigo,


                /* =============================================
                   NOMBRE MARCA
                   ============================================= */

                COALESCE(
                    m.descripcion,
                    'SIN MARCA'
                ) AS marca,


                /* =============================================
                   UNIDADES
                   ============================================= */

                CASE

                    WHEN d.sw = 1 THEN
                        d.cantidad

                    ELSE
                        d.cantidad * -1

                END AS unidades,


                /* =============================================
                   COMPRA BRUTA
                   ============================================= */

                CASE

                    WHEN d.sw = 1 THEN

                        (
                            (
                                d.valor_unitario
                                *
                                (
                                    1 -
                                    (
                                        d.pcj_descuento / 100
                                    )
                                )
                            )
                            *
                            d.cantidad
                        )

                    ELSE
                        0

                END AS compra_bruta,


                /* =============================================
                   DEVOLUCIONES
                   ============================================= */

                CASE

                    WHEN d.sw = 7 THEN

                        (
                            (
                                d.valor_unitario
                                *
                                (
                                    1 -
                                    (
                                        d.pcj_descuento / 100
                                    )
                                )
                            )
                            *
                            d.cantidad
                        )

                    ELSE
                        0

                END AS devoluciones,


                /* =============================================
                   COMPRA NETA
                   ============================================= */

                CASE

                    WHEN d.sw = 1 THEN

                        (
                            (
                                d.valor_unitario
                                *
                                (
                                    1 -
                                    (
                                        d.pcj_descuento / 100
                                    )
                                )
                            )
                            *
                            d.cantidad
                        )

                    ELSE

                        (
                            (
                                d.valor_unitario
                                *
                                (
                                    1 -
                                    (
                                        d.pcj_descuento / 100
                                    )
                                )
                            )
                            *
                            d.cantidad
                        )
                        *
                        -1

                END AS compra_neta,


                /* =============================================
                   IMPUESTOS
                   ============================================= */

                CASE

                    WHEN d.sw = 1 THEN

                        (

                            (

                                (

                                    (
                                        d.valor_unitario
                                        *
                                        (
                                            1 -
                                            (
                                                d.pcj_descuento / 100
                                            )
                                        )
                                    )
                                    *
                                    (
                                        d.pcj_iva / 100
                                    )

                                )

                                +

                                IFNULL(
                                    d.impoconsumo,
                                    0
                                )

                                +

                                IFNULL(
                                    d.ibua,
                                    0
                                )

                                +

                                (

                                    (
                                        d.valor_unitario
                                        *
                                        (
                                            1 -
                                            (
                                                d.pcj_descuento / 100
                                            )
                                        )
                                    )
                                    *
                                    (
                                        IFNULL(
                                            d.icui,
                                            0
                                        ) / 100
                                    )

                                )

                            )
                            *
                            d.cantidad

                        )

                    ELSE

                        (

                            (

                                (

                                    (
                                        d.valor_unitario
                                        *
                                        (
                                            1 -
                                            (
                                                d.pcj_descuento / 100
                                            )
                                        )
                                    )
                                    *
                                    (
                                        d.pcj_iva / 100
                                    )

                                )

                                +

                                IFNULL(
                                    d.impoconsumo,
                                    0
                                )

                                +

                                IFNULL(
                                    d.ibua,
                                    0
                                )

                                +

                                (

                                    (
                                        d.valor_unitario
                                        *
                                        (
                                            1 -
                                            (
                                                d.pcj_descuento / 100
                                            )
                                        )
                                    )
                                    *
                                    (
                                        IFNULL(
                                            d.icui,
                                            0
                                        ) / 100
                                    )

                                )

                            )
                            *
                            d.cantidad

                        )
                        *
                        -1

                END AS impuestos,


                /* =============================================
                   COSTO TOTAL
                   ============================================= */

                CASE

                    WHEN d.sw = 1 THEN

                        (

                            (

                                (
                                    d.valor_unitario
                                    *
                                    (
                                        1 -
                                        (
                                            d.pcj_descuento / 100
                                        )
                                    )
                                )

                                +

                                (

                                    (
                                        d.valor_unitario
                                        *
                                        (
                                            1 -
                                            (
                                                d.pcj_descuento / 100
                                            )
                                        )
                                    )
                                    *
                                    (
                                        d.pcj_iva / 100
                                    )

                                )

                                +

                                IFNULL(
                                    d.impoconsumo,
                                    0
                                )

                                +

                                IFNULL(
                                    d.ibua,
                                    0
                                )

                                +

                                (

                                    (
                                        d.valor_unitario
                                        *
                                        (
                                            1 -
                                            (
                                                d.pcj_descuento / 100
                                            )
                                        )
                                    )
                                    *
                                    (
                                        IFNULL(
                                            d.icui,
                                            0
                                        ) / 100
                                    )

                                )

                            )
                            *
                            d.cantidad

                        )

                    ELSE

                        (

                            (

                                (
                                    d.valor_unitario
                                    *
                                    (
                                        1 -
                                        (
                                            d.pcj_descuento / 100
                                        )
                                    )
                                )

                                +

                                (

                                    (
                                        d.valor_unitario
                                        *
                                        (
                                            1 -
                                            (
                                                d.pcj_descuento / 100
                                            )
                                        )
                                    )
                                    *
                                    (
                                        d.pcj_iva / 100
                                    )

                                )

                                +

                                IFNULL(
                                    d.impoconsumo,
                                    0
                                )

                                +

                                IFNULL(
                                    d.ibua,
                                    0
                                )

                                +

                                (

                                    (
                                        d.valor_unitario
                                        *
                                        (
                                            1 -
                                            (
                                                d.pcj_descuento / 100
                                            )
                                        )
                                    )
                                    *
                                    (
                                        IFNULL(
                                            d.icui,
                                            0
                                        ) / 100
                                    )

                                )

                            )
                            *
                            d.cantidad

                        )
                        *
                        -1

                END AS costo_total


            /* =================================================
               TABLAS
               ================================================= */

            FROM t_transacciones_detalle d


            INNER JOIN t_transacciones t

                ON t.id = d.id_transaccion

               AND IFNULL(
                    t.anulado,
                    0
               ) = 0

            INNER JOIN t_referencias r
                ON r.id = d.id_referencia
            LEFT JOIN t_marcas m
                ON m.id = r.id_marca
            INNER JOIN t_almacenes b

                ON b.id = d.id_bodega


            /* =================================================
               WHERE
               ================================================= */

            WHERE d.sw IN (1,7)

              AND CAST(d.fec AS DATE)
                  BETWEEN '{$fhIni}'
                  AND '{$fhFin}'

              {$filtro_bodega}

              {$filtro_marca}


        ) x


        /* =====================================================
           AGRUPACION
           ===================================================== */

        GROUP BY
            x.codigo,
            x.marca


        /* =====================================================
           ORDEN
           ===================================================== */

        ORDER BY
            compra_neta DESC

    ";


    /* =====================================================
       EJECUTAR
       ===================================================== */

    $resultado = GenerarArray($sql);


    /* =====================================================
       RESPUESTA JSON
       ===================================================== */

    echo json_encode(
        array(
            'data' => $resultado
        )
    );
}
