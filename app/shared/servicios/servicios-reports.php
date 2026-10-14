<?php
session_start();
require_once( '../../models/bd.php' );
require_once( '../../models/funciones.php' );
$conn = conectar();
switch ($_POST["op"]) {
    case "cartera-cliente":
        $sql="
            
        ";
    break;
    case "produtos-vencidos":
         $bodega     = $_SESSION["Ses_id_bodega"];
         $sql="
            select 
            r.codigo,
            r.descripcion ,
            m.descripcion as laboratorio,
            l.lote,
            l.vencimiento,
            l.stock ,
            v.promedio as costo,
            l.stock*v.promedio as costo_total
            from v_referencias_stock_lotes  l
            inner join t_referencias r on r.id = l.id_referencia 
            left  join t_marcas m on m.id=r.id_marca
            left  join v_costo_promedio v on v.id_referencia=r.id and v.mes=month(CURRENT_DATE) and v.ano=year(CURRENT_DATE)  
            where DATEDIFF(vencimiento,CURRENT_DATE)<=400 ";
            
        if($bodega!='0'){
            $sql.=" and l.id_bodega=$bodega ";
        }    
        
        $sql.="
            order by
            l.stock*v.promedio desc 
            
         ";
        $proximos = GenerarArray($sql);
        echo  json_encode($proximos);
    break;
    case "agotados":

    break;
}