 <?php
    session_start();
    require_once('bd.php');
    require_once('funciones.php');
    $conn = conectar();

    $fecha_inicial  = $_POST["fecha_inicial"];
    $fecha_final    = $_POST["fecha_final"];
    $id_tercero     = $_POST["id_tercero"];
    $id_usuario     = $_SESSION["Ses_id"];
    $id_rol         = $_SESSION["Ses_roles_id"];

    $sql = "  SELECT 
                    cast(fecha_hora_documento as date) as fecha,
                    nit,
                    razon_comercial,
                    nombres as cliente,
                    descripcion_condicion as condicion,
                    tipo,
                    numero,
                    sum(precio_unitario_unidad* cantidad) as valor_bruto,
                    sum((precio_unitario_unidad*(porcentaje_descuento/100))*cantidad) as descuento,
                    SUM(((precio_unitario_unidad - (precio_unitario_unidad * (porcentaje_descuento / 100))) * (porcentaje_iva / 100)) * cantidad) AS iva,
                    SUM(((precio_unitario_unidad - (precio_unitario_unidad * (porcentaje_descuento / 100))) * (1 + (porcentaje_iva / 100))) * cantidad) AS total
                from v_ventas_general 
                where 
                cast(fecha_hora_documento as date) >= cast('$fecha_inicial' as date) and  
                cast(fecha_hora_documento as date) <= cast('$fecha_final' as date)  
    ";
    if ($id_rol != '1' && $id_rol != '4') {
        $sql .= " and id_usuario = $id_usuario";
    }
    if ($id_tercero != '0') {
        $sql .= " and id_tercero = $id_tercero";
    }

    $sql .= " GROUP BY
                nit,
                razon_comercial,
                nombres ,
                descripcion_condicion,
                tipo,
                numero,
                cast(fecha_hora_documento as date)
    ";

    $ventas = GenerarArray($sql);

    echo  json_encode($ventas);
