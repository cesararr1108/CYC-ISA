<?php
session_start();
require_once( 'bd.php' );
require_once( 'funciones.php' );
$conn = conectar();

switch($_POST["op"]){

    case "listarRolesPermisos":

        $rol    =$_POST["rol"];
        $modulo =$_POST["modulo"];

        $sql ="select 
                p.*,
                x.id_modulo_permiso,
                ifnull(r.id,0) as asociado
                from t_permisos  p 
                inner join t_modulos_permisos       x on x.id_permiso=p.id
                left  join t_roles_modulos_permisos r on r.id_modulo_permiso=x.id_modulo_permiso and r.id_rol =$rol
                where  x.id_modulo=$modulo  ";

        
        $result = GenerarArray($sql);

        echo  json_encode(array(
            'error'=>false,
            'message'=>'success',
            'data'=>$result
        ));
    break;
    case "agregar":

        $data=$_POST;
   
        $id =  insertReturnId('t_roles_modulos_permisos',$data,true);

        if($id>0){
            echo  json_encode(array(
                'error'=>false,
                'message'=>'Se inserto correctamente el registro',
                'data'=>$id
            ));
        }else{
            echo  json_encode(array(
                'error'=>false,
                'message'=>'success',
                'data'=>0
            ));
        }
    break;
    case "delete":
        $id=$_POST["id"];

        $delete =Query( "delete from t_roles_modulos_permisos where id=$id");

        if($delete ){
            echo  json_encode(array(
                'error'=>false,
                'message'=>'Se elimino correctamente el registro',
                'data'=>null
            ));
        } else{
            echo  json_encode(array(
                'error'=>true,
                'message'=>'Error al intentar eliminar el registro',
                'data'=>null
            ));
        }
        
    break;
}