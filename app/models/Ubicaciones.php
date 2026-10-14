<?php
session_start();
require_once( 'bd.php' );
require_once( 'funciones.php' );
$conn = conectar();

switch($_POST["op"]){
    case "listar":

        $filtro = isset($_POST["filtro"]) ? $_POST["filtro"]:'';

        $sql="select 
          u.* ,
          x.usuario
        from t_ubicaciones u 
        inner join t_usuarios x on x.id=u.usuario";

        if($filtro!=''){
            $sql.=" where ubicacion like '%".$filtro."%'";
        }
        $ubicaciones =GenerarArray($sql);
        echo json_encode(array(
            'error'=>false,
            'message'=>'success',
            'data'=>$ubicaciones
        ));
    break;

    case "agregar":
        $data =$_POST;
        $data["usuario"]=$_SESSION["Ses_id"];
        
        $id = insertReturnId('t_ubicaciones',$data,true);
       
        if($id>0){
            $data["id"]=$id;
            echo  json_encode(array(
                'error'     =>false,
                'message'   =>'Se inserto correctamente el registro',
                'data'      =>$data
            ));
        }else{
            echo  json_encode(array(
                'error'     =>false,
                'message'   =>'Error al insertar el registro',
                'data'      =>$data
            ));
        }
    break;
    case "update":
        $data =$_POST;
        $data["usuario"]=$_SESSION["Ses_id"];
        $update = update('t_ubicaciones', $data, $data["id"]);
  
        if($update){
            echo  json_encode(array(
                'error'     =>false,
                'message'   =>'Se actualizo correctamente el registro',
                'data'      =>$data
            ));   
        }else{
            echo  json_encode(array(
                'error'     =>true,
                'message'   =>'No se pudo actualizar el registro',
                'data'      =>$data
            ));
        }
    break;
    case "delete":
        $id=$_POST["id"];
        $sw = validarDependencias(['t_ubicaciones_referencias'],'id_ubicacion',$id);

        if(!$sw){
         echo  json_encode(array(
             'error'     =>true,
             'message'   =>'No se puede eliminar el registro ya ue existen dependencias ',
             'data'      =>null
         ));
          return;
        }
  
        $delete = Query("delete from t_ubicaciones where id=$id");
  
        if($delete){
            echo  json_encode(array(
                'error'     =>false,
                'message'   =>'Se elimino correctamente el registro',
                'data'      =>null
            ));
        }else{
            echo  json_encode(array(
                'error'     =>false,
                'message'   =>'Error al eliminar el registro',
                'data'      =>null
            ));
        }
    break;
}