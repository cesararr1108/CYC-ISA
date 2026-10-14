<?php
session_start();
require_once( 'bd.php' );
require_once( 'funciones.php' );
$conn = conectar();

switch ($_POST["op"]) {
  case 'consultar':
    # code...
      $fecha_inicial = $_POST["fecha_inicial"];
      $fecha_final    = $_POST["fecha_final"];
      $bodega        = $_POST["bodega"];
      $usuario_caja = $_POST["usuario_caja"];


      $sql_doc ="
      select 
      case when  d.sw=5 and c.dias=0
          then ROUND(sum(((d.valor_unitario*(1-(d.pcj_descuento/100)))*((d.pcj_iva/100)+1))*d.cantidad),0)
        else 0 end  as venta_contado,
        case when  d.sw=5 and c.dias>0
          then ROUND(sum(((d.valor_unitario*(1-(d.pcj_descuento/100)))*((d.pcj_iva/100)+1))*d.cantidad),0)
        else 0 end  as venta_credito,
        case when  d.sw=4 and c.dias=0
          then ROUND(sum(((d.valor_unitario*(1-(d.pcj_descuento/100)))*((d.pcj_iva/100)+1))*d.cantidad),0)
        else 0 end  as dev_contado,
        case when  d.sw=4 and c.dias>0
          then ROUND(sum(((d.valor_unitario*(1-(d.pcj_descuento/100)))*((d.pcj_iva/100)+1))*d.cantidad),0)
        else 0 end  as dev_credito,
        max(case when d.sw=5 then t.numero else 0 end ) as num_max_ven,
        min(case when d.sw=5 then t.numero else 0 end ) as nim_max_ven,
        cast(NOW() as date) as fecha
      from t_transacciones_detalle d 
      inner join t_transacciones t on t.id=d.id_transaccion
      inner join t_condiciones_pago c on c.id=t.id_condicion
      where d.sw in(4,5) and cast(t.fecha_hora as date) between cast('$fecha_inicial' as date) and cast('$fecha_final' as date)
      and t.id_bodega = $bodega ";

      if($usuario_caja!='0'){
         $sql_doc.="and t.id_usuario = $usuario_caja ";
      }
     
     $sql_doc.="
      GROUP BY
      d.sw,
      c.dias

      ";
      $documentos = GenerarArray($sql_doc);
    
    // otros ingresos 
    $sql_otr_i = "
      select 
        ifnull(sum(t.valor_total),0) as valor_ingresos
      from t_transacciones  t 
      where sw=10  and cast(t.fecha_hora as date) between cast('$fecha_inicial' as date) and cast('$fecha_final' as date) 
      and t.id_bodega = $bodega
    ";
    if($usuario_caja!='0'){
         $sql_otr_i.="and t.id_usuario = $usuario_caja ";
    }
    $otros_ingresos = GenerarArray($sql_otr_i);

    $sql_pagos = "select 
                    m.descripcion as medio,
                    m.naturaleza,
                    ifnull(sum(t.valor),0) as valor
                    from  t_medios_pagos m
                    left join  t_transacciones_pago t on  t.id_medio_pago=m.id
                    left join t_transacciones x on x.id = t.id_transaccion                     
                    where 
                    cast(x.fecha_hora as date)  between cast('$fecha_inicial' as date) and cast('$fecha_final' as date) and 
                    x.id_bodega = $bodega  ";

      if($usuario_caja!='0'){
         $sql_pagos.=" and  x.id_usuario = $usuario_caja ";
      }
      $sql_pagos.="              
                    group BY
                    m.descripcion,
                    m.naturaleza";
      $pagos = GenerarArray($sql_pagos);

      $sql_e = "
        select 
          ifnull(sum(valor_total),0) as egresos 
        from t_transacciones x
         where x.sw=9 and x.id_bodega =$bodega  and cast(x.fecha_hora as date) between cast('$fecha_inicial' as date) and cast('$fecha_final' as date) 
      ";
    if($usuario_caja!='0'){
         $sql_e.=" and x.id_usuario = $usuario_caja ";
    }
      $egresos  = GenerarArray($sql_e);

      echo  json_encode(array(
        'documentos'=>$documentos,
        'otros_ingresos'=>$otros_ingresos,
        'egresos'=>$egresos,
        'pagos'=>$pagos
      ));

    break;
  
  default:
    # code...
    break;
}

