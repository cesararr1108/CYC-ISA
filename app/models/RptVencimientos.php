<?php
session_start();
require_once( 'bd.php' );
require_once( 'funciones.php' );
$conn = conectar();

$dato = trim($_POST["dato"]);
$institucional = $_POST["institucional"];
$controlado = $_POST["controlado"];
$regulado = $_POST["controlado"];
$marca =$_POST["marca"];
$bodega     = $_SESSION["Ses_id_bodega"];

       
    $sql="
    select 
    r.codigo,
    r.descripcion ,
    m.descripcion as marca,
    l.lote,
    l.vencimiento,
    l.stock ,
    l.stock*v.promedio as costo,
    TIMESTAMPDIFF(MONTH,vencimiento,CURRENT_DATE) as meses
    from v_referencias_stock_lotes  l
    inner join t_referencias r on r.id = l.id_referencia 
    left  join t_marcas m on m.id=r.id_marca
    left  join v_costo_promedio v on v.id_referencia=r.id and v.mes=month(CURRENT_DATE) and v.ano=year(CURRENT_DATE)  
    where DATEDIFF(vencimiento,CURRENT_DATE)<=400  and l.id_bodega=$bodega
    ";

    if($marca!='0'){
        $sql.=" and m.id=$marca";
    }
    if($dato!=''){
        $sql.=" and r.descripcion like '%$dato%'";
    }
    if($institucional!='0'){
        $sql.=" and r.institucional ='S'"; 
    }
    if($controlado!='0'){
        $sql.=" and r.controlado ='S'"; 
    }
    if($regulado!='0'){
        $sql.=" and r.regulado ='S'"; 
    }
$sql.="
    order by
    l.stock*v.promedio desc 
";
$proximos = GenerarArray($sql);
echo  json_encode($proximos);