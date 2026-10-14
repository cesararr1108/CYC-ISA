<?php
session_start();
require_once( 'bd.php' );
require_once( 'funciones.php' );
$conn = conectar();

switch($_POST["op"]){
    case "listarPermisosModulos":
        $id_modulo=$_POST["id_modulo"];

        $sql="select 
        p.id,
        p.permiso ,
        p.descripcion ,
        ifnull(m.id_permiso,0) as asociado
        from t_permisos p
        left join t_modulos_permisos m on m.id_permiso=p.id and m.id_modulo=$id_modulo
        order by
        p.permiso";
        
        $permisos =GenerarArray($sql);

        echo  json_encode(array(
            'error'=>false,
            'message'=>'success',
            'data'=>$permisos
        ));

    break;
    case "listar":

        $filtro =isset($_POST["filtro"]) ? $_POST["filtro"]:'';

        $sql="
             select distinct
                m.id_modulo_permiso as id,
                x.id as id_modulo,
                x.titulo,
                p.permiso ,
                z.grupo as nombre_grupo
                from t_permisos p
                inner join t_modulos_permisos   m on m.id_permiso = p.id
                inner join t_modulos            x on x.id=m.id_modulo 
                inner join t_grupos_modulos     z on z.id=x.grupo   
        ";

        if($filtro!=''){
            $sql.=" where p.permiso like '%$filtro%' or x.titulo like '%$filtro%'";
        }

        $sql.=" order by
                z.grupo,x.titulo";

        $permisosmodulos = GenerarArray($sql);

        echo  json_encode(array(
            'error'=>false,
            'message'=>'success',
            'data'=>$permisosmodulos
        ));

    break;
    case "agregar":

        $data =$_POST;
        $idReturn   = insertReturnId('t_modulos_permisos',$data,true);

        if(  $idReturn > 0){
            echo  json_encode(array(
                'error'     =>false,
                'message'   =>'Se inserto correctamente el registro',
                'data'      =>$idReturn
            ));
        }else{
            echo  json_encode(array(
                'error'     =>false,
                'message'   =>'error al insertar el registro',
                'data'      =>0
            ));
        }

    break;
    case "delete":
        $id =$_POST["id"];

        $delete = Query("delete from t_modulos_permisos where id_modulo_permiso = $id");

        if($delete){
            echo  json_encode(array(
                'error'     =>false,
                'message'   =>'Se elimino correctamente el registro!',
                'data'      =>$id
            ));
        }else{
            echo  json_encode(array(
                'error'     =>true,
                'message'   =>'Error al eliminar el registro!',
                'data'      =>$id
            ));
        }
    break;
}