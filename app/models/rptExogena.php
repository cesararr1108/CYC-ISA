<?php
session_start();
require_once('bd.php');
require_once('funciones.php');
$conn = conectar();


switch ($_POST["op"]) {

    case "1008":
        $anio = $_POST['anio'];

        //necesito preparar la consulta para que no me traiga los datos en crudo

        $sql = "SELECT 
                e.concepto,
                i.tipo_dian AS tipo,
                m.nit ,
                c.nit_digito AS dv ,
                c.primer_apellido,
                c.segundo_apellido,
                c.primer_nombre,
                c.segundo_nombre,
                c.razon_comercial,
                dd.direccion,
                yd.codigo_dpto,
                ym.codigo AS codigo_municipio,
                dd.codigo_pais AS pais,
                CAST( round(sum(m.debito) - sum(m.credito),2)  AS CHAR) as saldo
                FROM t_movimiento_contable m 
                INNER JOIN t_transacciones 			t  ON t.id = m.id_transaccion 
                INNER JOIN t_terceros 				c  ON c.id = t.id_tercero
                LEFT  JOIN t_terceros_direcciones 	dd ON dd.id_tercero = c.id AND dd.principal='1'
                LEFT  JOIN t_departamentos          yd ON yd.codigo_dpto = dd.codigo_dpto
                LEFT  JOIN t_municipios             ym ON ym.codigo_dpto= yd.codigo_dpto AND ym.codigo = dd.codigo_mpio
                INNER JOIN t_tipo_identificaciones 	i  ON i.id = c.id_tipo_identificacion
                INNER JOIN t_exogena_parametros     e  ON e.cuenta = m.cuenta 
                WHERE e.formato=? AND YEAR(m.fecha_hora) =?
                GROUP BY
                e.concepto,
                i.tipo_dian ,
                m.nit ,
                c.nit_digito ,
                c.primer_apellido,
                c.segundo_apellido,
                c.primer_nombre,
                c.segundo_nombre,
                c.razon_comercial,
                dd.direccion,
                yd.codigo_dpto,
                ym.codigo ,
                dd.codigo_pais ";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            die(mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, "ii", $_POST['op'], $anio);

        if (!mysqli_stmt_execute($stmt)) {
            die(mysqli_stmt_error($stmt));
        }

        $result = mysqli_stmt_get_result($stmt);

        $data = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        echo json_encode($data);

        break;
    case "1009":
        $anio = $_POST['anio'];

        //necesito preparar la consulta para que no me traiga los datos en crudo

        $sql = "SELECT 
                e.concepto,
                i.tipo_dian AS tipo,
                m.nit ,
                c.nit_digito AS dv ,
                c.primer_apellido,
                c.segundo_apellido,
                c.primer_nombre,
                c.segundo_nombre,
                c.razon_comercial,
                dd.direccion,
                yd.codigo_dpto,
                ym.codigo AS codigo_municipio,
                dd.codigo_pais AS pais,
                CAST( round(sum(m.debito) - sum(m.credito),2)  AS CHAR) as saldo
                FROM t_movimiento_contable m 
                INNER JOIN t_transacciones 			t  ON t.id = m.id_transaccion 
                INNER JOIN t_terceros 				c  ON c.id = t.id_tercero
                LEFT  JOIN t_terceros_direcciones 	dd ON dd.id_tercero = c.id AND dd.principal='1'
                LEFT  JOIN t_departamentos          yd ON yd.codigo_dpto = dd.codigo_dpto
                LEFT  JOIN t_municipios             ym ON ym.codigo_dpto= yd.codigo_dpto AND ym.codigo = dd.codigo_mpio
                INNER JOIN t_tipo_identificaciones 	i  ON i.id = c.id_tipo_identificacion
                INNER JOIN t_exogena_parametros     e  ON e.cuenta = m.cuenta 
                WHERE e.formato=? AND YEAR(m.fecha_hora) =?
                GROUP BY
                e.concepto,
                i.tipo_dian ,
                m.nit ,
                c.nit_digito ,
                c.primer_apellido,
                c.segundo_apellido,
                c.primer_nombre,
                c.segundo_nombre,
                c.razon_comercial,
                dd.direccion,
                yd.codigo_dpto,
                ym.codigo ,
                dd.codigo_pais ";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            die(mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, "ii", $_POST['op'], $anio);

        if (!mysqli_stmt_execute($stmt)) {
            die(mysqli_stmt_error($stmt));
        }

        $result = mysqli_stmt_get_result($stmt);

        $data = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        echo json_encode($data);

        break;
}