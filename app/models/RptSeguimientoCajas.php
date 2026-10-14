<?php
session_start();
require_once( 'bd.php' );
require_once( 'funciones.php' );
$conn = conectar();

switch ($_POST["op"]) {
    case 'usuarios':
        # code... 
        $sql="select * from t_usuarios where estado='A' order by usuario";
        echo  json_encode(GenerarArray($sql));
    break;
    case "consultar":

        $usuario       = $_POST["usuario"];
        $fecha_inicial = $_POST["fecha_inicial"];
        $fecha_final   = $_POST["fecha_final"];

        $sql="
            CREATE TEMPORARY TABLE temp_reporte_caja AS
            select 
            u.usuario,
            a.fecha_apertura,
            a.fecha_cierre,
            a.base,
            a.efectivo,
            a.tarjetas,
            a.otros,
            ifnull((
                select 
                sum(pp.valor)
                from t_transacciones_pago pp 
                where pp.id_usuario = a.id_usuario and pp.fecha_hora >= a.fecha_apertura and pp.fecha_hora<=a.fecha_cierre
                and pp.id_medio_pago=1
            ),0) as sys_efectivo,
            ifnull((
                select 
                sum(pp.valor)
                from t_transacciones_pago pp 
                where pp.id_usuario = a.id_usuario and pp.fecha_hora >= a.fecha_apertura and pp.fecha_hora<=a.fecha_cierre
                and pp.id_medio_pago in(2,3)
            ),0) as sys_tarjeta,
            ifnull((
                select 
                    ifnull(sum(t.valor_total),0)
                from t_transacciones t 
                inner join t_condiciones_pago p on p.id=t.id_condicion 
                where t.fecha_hora>=a.fecha_apertura and t.fecha_hora<=a.fecha_cierre and t.id_usuario= a.id_usuario and t.sw = 4 and p.dias=0
            ),0) as dev_contado,
            ifnull((
                select 
                     ifnull(sum(t.valor_total),0)
                from  t_transacciones t
                inner join t_condiciones_pago p on p.id=t.id_condicion
                where t.fecha_hora>=a.fecha_apertura and t.fecha_hora<=a.fecha_cierre and t.id_usuario= a.id_usuario and t.sw = 5 and p.dias=0
            ),0) as contado
            from t_aperturas_cajas a 
            inner join t_usuarios u on u.id= a.id_usuario 
            where a.fecha_apertura>=cast('$fecha_inicial' as datetime) and a.fecha_cierre<=cast('$fecha_final' as datetime);
        ";
 
        if($usuario!='0'){
            $sql.=" and a.id_usuario =$usuario";
        }

        $tmp = $conn->query($sql);

        if($tmp){
            $cierres = GenerarArray("
            SELECT 
            usuario,
            fecha_apertura,
            fecha_cierre,
            base,
            efectivo,
            tarjetas,
            otros,
            sys_efectivo,
            sys_tarjeta,
            dev_contado,
            contado,
             efectivo - sys_efectivo as diferencia
            FROM temp_reporte_caja ",$conn);
        }

        echo  json_encode($cierres);

    break;

}
