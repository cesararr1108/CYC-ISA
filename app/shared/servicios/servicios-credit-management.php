<?php
session_start();
require_once( '../../models/bd.php' );
require_once( '../../models/funciones.php' );
require_once( 'servicios-sftp.php' );

$conn = conectar();

if(!isset($_POST["op"])){
    echo  json_encode([
        ''=>false,
        ''=>''
    ]);
}

switch ($_POST["op"]) {

    case 'validar-disponibilidad':
        if(!isset($_POST["nit"])){
            echo  json_encode([
                'ok'=>false,
                'message'=>'No se envio el nit'
            ]);
        }
        $nit = $_POST["nit"];
        # code...
        $sql ="
            select 
                t.cupo_credito,
                ( ifnull((
                    select 
                        sum(total_vencida)
                    from v_cartera
                    where nit = t.nit  
                ),0)) as cartera_vencida
            from t_terceros t 
            where t.nit = $nit
        ";
        $movimientos = GenerarArray($sql);
        echo  json_encode($movimientos);
    break;
    
    default:
        # code...
        break;
}