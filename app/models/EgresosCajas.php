 <?php
session_start();
require_once( 'bd.php' );
require_once( 'funciones.php' );
$conn = conectar();

switch ($_POST["op"]) {
    case 'i_egreso':

        try {
        # code...
            $data = $_POST;
            $numero = numeroSiguiente($data['id_tipo_documento'],'t_transacciones');
            $data["numero"]=$numero;
            
            $id = insertReturnId( 't_transacciones', $data, true );

            if($id>0){
                echo  json_encode(array(
                        'ok'=>true,
                        'message'=>'Se guardo correctamente el egreso!'
                    ));
            }else{
                echo  json_encode(array(
                        'ok'=>false,
                        'message'=>'No se puedo guardar el documento!'
                    ));
            }
        } catch (\Throwable $th) {
            //throw $th;
            echo  json_encode(array(
                'ok'=>false,
                'message'=>$th->getMessage()
            ));
        }

    break;
}