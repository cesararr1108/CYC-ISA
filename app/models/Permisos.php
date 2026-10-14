<?php
session_start();
require_once( 'bd.php' );
require_once( 'funciones.php' );
$conn = conectar();


switch($_POST["op"]){
    case "listar":

        $filtro     = isset($_POST["filtro"]) ? $_POST["filtro"] : '';

        $sql ="select 
                *
              from t_permisos 
        ";

        if($filtro!=''){
            $sql.="  permiso like '%$filtro%' or descripcion like '%$filtro%'";
        }


        $sql.=" order by permiso ";

        $permisos = GenerarArray($sql);

        echo  json_encode($permisos);

    break;
    case "agregar":

        $data =$_POST;
        $data["usuario_creacion"]=$_SESSION["Ses_id"];

        $id = insertReturnId('t_permisos',$data,true);

        if($id>0){
            echo  json_encode(array(
                'error'     =>false,
                'message'   =>'Se inserto correctamente el registro',
                'data'      =>$id
            ));
        }else{
            echo  json_encode(array(
                'error'     =>false,
                'message'   =>'Error al insertar el registro',
                'data'      =>0
            ));
        }

    break;
    case "update":
        $data =$_POST;
        $data["usuario_creacion"]=$_SESSION["Ses_id"];
        $update = update('t_permisos', $data, $data["id"]);

        if($update){
            echo  json_encode(array(
                'error'     =>false,
                'message'   =>'Se actualizo correctamente el registro',
                'data'      =>$data
            ));   
        }else{
            echo  json_encode(array(
                'error'     =>true,
                'message'   =>'No se pudo elimino el registro',
                'data'      =>$data
            ));
        }
    break;
    case "delete":

        $id =$_POST["id"];
        $sw = validarDependencias(['t_modulos_permisos'],'id_permiso',$id);
  
        if(!$sw){
         echo  json_encode(array(
             'error'     =>true,
             'message'   =>'No se puede eliminar el registro ya ue existen dependencias ',
             'data'      =>null
         ));
          return;
        }
        $delete =Query( "delete from t_permisos where id=$id" );

        if($delete){
            echo  json_encode(array(
                'error'     =>false,
                'message'   =>'Se elimino correctamente el registro',
                'data'      =>$id
            ));  
        }else{
            echo  json_encode(array(
                'error'     =>true,
                'message'   =>'No se puede eliminar el registro',
                'data'      =>$id
            ));  
        }

    break;
}