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

switch ($_POST["op"]) {
    case 'consultar_domicilios':
        $fecha = $_POST['fecha'];
        $sql = "SELECT
                    d.id AS id_factura,
                    d.numero,
                    d.tipo,
                    d.valor_total,
                    t.nit,
                    CASE
                        WHEN t.primer_nombre IS NULL
                            THEN t.razon_comercial
                        ELSE CONCAT_WS(
                            ' ',
                            t.primer_nombre,
                            t.segundo_nombre,
                            t.primer_apellido,
                            t.segundo_apellido
                        )
                    END AS cliente,
                    t.razon_comercial,
                    DATE(d.fecha_hora) AS fecha,
                    COALESCE(t.email_fe, '') AS email,
                    td.celular,
                    td.direccion,
                    td.email,
                    d.notas,
                    dep.departamento,
                    mun.municipio,
                    -- DESPACHO
                    IFNULL(rd.estado,'P') AS estado,
                    rd.id ,
                    rd.inicio ,
                    rd.fin ,
                    rd.nota ,
                    CONCAT_WS( ' ',
                                ud.nombres ,
                                ud.apellidos 
                            ) AS mensajero,
                    ud.telefono AS telefono_mensajero
                FROM t_transacciones AS d
                INNER JOIN t_terceros AS t ON t.id = d.id_tercero
                INNER JOIN t_terceros_direcciones AS td ON td.id = d.id_tercero_dir
                INNER JOIN t_departamentos AS dep ON dep.codigo_dpto = td.codigo_dpto AND dep.codigo_pais = td.codigo_pais
                INNER JOIN t_municipios AS mun ON mun.codigo_dpto = td.codigo_dpto AND mun.codigo = td.codigo_mpio
                LEFT JOIN t_registro_despacho_det AS rd ON rd.tipo_fac = d.tipo AND rd.numero_fac = d.numero
                LEFT JOIN t_registro_despacho AS des ON des.id = rd.id_reg
                LEFT JOIN t_usuarios AS ud ON ud.id = des.usuario_id
                WHERE 
                IFNULL(d.anulado,0) = 0
                AND d.sw = 5
                AND IFNULL(d.es_domicilio,0) = 1
                AND cast(d.fecha_hora as date) = '$fecha'";
        echo json_encode(GenerarArray($sql));
        break;

    case "detalle_domicilio":
        $id_factura = $_POST['id_factura'];
        $sql = "SELECT
                    d.id AS id_factura,
                    d.tipo,
                    d.numero,
                    d.fecha_hora AS fecha_factura,
                    rd.id AS id_detalle,
                    rd.estado,
                    rd.inicio,
                    rd.fin,
                    rd.flete,
                    des.id AS id_despacho,
                    des.usuario_id AS usuario_despacho,
                    des.transportador_id,
                    CONCAT_WS(
                        ' ',
                        ud.nombres,
                        ud.apellidos
                    ) AS usuario_despacho_nombre,
                    CONCAT_WS(
                        ' ',
                        um.nombres,
                        um.apellidos
                    ) AS mensajero,
                    /* ==========================================
                    TIEMPO FACTURA → DESPACHO
                    ========================================== */
                    TIMESTAMPDIFF(
                        MINUTE,
                        d.fecha_hora,
                        rd.inicio
                    ) AS tiempo_factura_despacho,
                    /* ==========================================
                    TIEMPO DESPACHO → ENTREGA
                    ========================================== */
                    TIMESTAMPDIFF(
                        MINUTE,
                        rd.inicio,
                        rd.fin
                    ) AS tiempo_despacho_entrega,
                    /* ==========================================
                    TIEMPO TOTAL
                    ========================================== */
                    TIMESTAMPDIFF(
                        MINUTE,
                        d.fecha_hora,
                        COALESCE(rd.fin, NOW())
                    ) AS tiempo_total
                FROM t_transacciones AS d
                LEFT JOIN t_registro_despacho_det AS rd
                    ON rd.tipo_fac = d.tipo
                    AND rd.numero_fac = d.numero
                LEFT JOIN t_registro_despacho AS des
                    ON des.id = rd.id_reg
                LEFT JOIN t_usuarios AS ud
                    ON ud.id = des.usuario_id
                LEFT JOIN t_usuarios AS um
                    ON um.id = des.transportador_id
                WHERE d.id = '$id_factura'
                ORDER BY
                    COALESCE(rd.inicio, d.fecha_hora) ASC;";
        echo json_encode(GenerarArray($sql));
        break;
    case 'historial_domicilios':
        $fecha_ini = $_POST['fecha_ini'];
        $fecha_fin = $_POST['fecha_fin'];
        $sql = "SELECT
                    d.id AS id_factura,
                    d.tipo,
                    d.numero,
                    d.fecha_hora AS fecha_factura,
                    rd.id AS id_detalle,
                    rd.estado,
                    rd.inicio,
                    rd.fin,
                    rd.flete,
                    des.id AS id_despacho,
                    des.usuario_id AS usuario_despacho,
                    des.transportador_id,
                    CONCAT_WS(
                        ' ',
                        ud.nombres,
                        ud.apellidos
                    ) AS usuario_despacho_nombre,
                    CONCAT_WS(
                        ' ',
                        um.nombres,
                        um.apellidos
                    ) AS mensajero,
                    /* ==========================================
                    TIEMPO FACTURA → DESPACHO
                    ========================================== */
                    TIMESTAMPDIFF(
                        MINUTE,
                        d.fecha_hora,
                        rd.inicio
                    ) AS tiempo_factura_despacho,
                    /* ==========================================
                    TIEMPO DESPACHO → ENTREGA
                    ========================================== */
                    TIMESTAMPDIFF(
                        MINUTE,
                        rd.inicio,
                        rd.fin
                    ) AS tiempo_despacho_entrega,
                    /* ==========================================
                    TIEMPO TOTAL
                    ========================================== */
                    TIMESTAMPDIFF(
                        MINUTE,
                        d.fecha_hora,
                        COALESCE(rd.fin, NOW())
                    ) AS tiempo_total
                FROM t_transacciones AS d
                LEFT JOIN t_registro_despacho_det AS rd
                    ON rd.tipo_fac = d.tipo
                    AND rd.numero_fac = d.numero
                LEFT JOIN t_registro_despacho AS des
                    ON des.id = rd.id_reg
                LEFT JOIN t_usuarios AS ud
                    ON ud.id = des.usuario_id
                LEFT JOIN t_usuarios AS um
                    ON um.id = des.transportador_id
                WHERE 
                    IFNULL(d.anulado,0) = 0
                    AND d.sw = 5
                    AND IFNULL(d.es_domicilio,0) = 1
                    AND d.fecha between '$fecha_ini' and '$fecha_fin'
                ORDER BY
                    COALESCE(rd.inicio, d.fecha_hora) ASC;";
        echo json_encode(GenerarArray($sql));
        break;
}
