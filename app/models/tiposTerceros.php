<?php
session_start();
require_once( 'bd.php' );
require_once( 'funciones.php' );
$conn = conectar();

switch ( $_POST[ "op" ] ) {
  case "listar":
    $sql = "
    select 
    * 
    from t_tipos_terceros";
    $tipos_terceros = GenerarArray( $sql );
    echo json_encode( array(
      'error' => false,
      'message' => 'success',
      'data' => $tipos_terceros
    ) );
    break;
    case "agregar":
      $data =$_POST;
      $data["usuario_creacion"]=$_SESSION["Ses_id"];
      $data["inactivo"]='0';
      $id = insertReturnId('t_tipos_terceros',$data,true);
     
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
      $update = update('t_tipos_terceros', $data, $data["id"]);

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
    
      $sw = validarDependencias(['t_terceros'],'id_tipo_identificacion',$id);
  
      if(!$sw){
       echo  json_encode(array(
           'error'     =>true,
           'message'   =>'No se puede eliminar el registro ya ue existen dependencias ',
           'data'      =>null
       ));
        return;
      }

      $delete = Query("delete from t_tipos_terceros where id=$id");

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