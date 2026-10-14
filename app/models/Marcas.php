<?php
session_start();
require_once( 'bd.php' );
require_once( 'funciones.php' );
$conn = conectar();

switch($_POST["op"]){
    case "listar":

        $filtro = isset($_POST["filtro"]) ? $_POST["filtro"]:'';

        $sql="select 
         * ,
         case when inactivo =0  then 'Activo' else 'Inactivo' end  as inactivo_des 
        from t_marcas";

        if($filtro!=''){
            $sql.=" where descripcion like '%".$filtro."%'";
        }
        $marcas =GenerarArray($sql);
        echo json_encode(array(
            'error'=>false,
            'message'=>'success',
            'data'=>$marcas
        ));
    break;

    case "agregar":
        $data =$_POST;
        $data["usuario_creacion"]=$_SESSION["Ses_id"];
        
        $id = insertReturnId('t_marcas',$data,true);
       
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
        $data["usuario_creacion"]=$_SESSION["Ses_id"];
        $update = update('t_marcas', $data, $data["id"]);
  
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
        $sw = validarDependencias(['t_referencias'],'id_marca',$id);

        if(!$sw){
         echo  json_encode(array(
             'error'     =>true,
             'message'   =>'No se puede eliminar el registro ya ue existen dependencias ',
             'data'      =>null
         ));
          return;
        }
  
        $delete = Query("delete from t_marcas where id=$id");
  
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