<?php
session_start();
require_once( 'bd.php' );
require_once( 'funciones.php' );
$conn = conectar();

switch ( $_POST[ "op" ] ) {
  case 'listar':
    $sql = "select * from t_perfil_tributario where anulado = 0";
    $data = GenerarArray( $sql );
    echo json_encode( array(
      'error' => false,
      'message' => '',
      'data' => $data
    ) );
    break;
  case 'crearPerfil':
    $tipo = $_POST[ 'tipo' ];
    $desc = $_POST[ 'descripcion' ];
    $id_usr = $_SESSION[ 'Ses_id' ];
    $sql = "INSERT INTO t_perfil_tributario(tipo,descripcion,anulado,usuario_creacion,editable)values('$tipo','$desc',0,$id_usr,'S');";
    $q = Query( $sql );
    if ( $q ) {
      echo json_encode( array(
        'error' => false,
        'message' => 'Perfil creado correctamente'
      ) );
    } else {
      echo json_encode( array(
        'error' => true,
        'message' => 'No fue posible crear el perfil'
      ) );
    }
    break;
  case 'editarPerfil':

    $id = $_POST[ 'id' ];
    $tipo = $_POST[ 'tipo' ];
    $desc = $_POST[ 'descripcion' ];

    $sql = "UPDATE t_perfil_tributario 
            SET tipo = '$tipo',
                descripcion = '$desc'
            WHERE id = $id";

    $q = Query( $sql );

    if ( $q ) {
      echo json_encode( array(
        'error' => false,
        'message' => 'Perfil actualizado correctamente'
      ) );
    } else {
      echo json_encode( array(
        'error' => true,
        'message' => 'No fue posible actualizar el perfil'
      ) );
    }

    break;
  case 'eliminarPerfil':

    $id = $_POST[ 'id' ];

    $sql = "UPDATE t_perfil_tributario 
            SET anulado = 1
            WHERE id = $id";

    $q = Query( $sql );

    if ( $q ) {
      echo json_encode( array(
        'error' => false,
        'message' => 'Perfil eliminado correctamente'
      ) );
    } else {
      echo json_encode( array(
        'error' => true,
        'message' => 'No fue posible eliminar el perfil'
      ) );
    }

    break;
  case 'listarPerfildefinicion':
    $sw = $_POST[ 'sw' ];
    $bodega = $_POST[ 'bodega' ];
    $id_definicion = $_POST[ 'id_definicion' ];
    $sql = "SELECT * FROM t_perfil_tributario_definiciones WHERE sw = $sw and bodega = $bodega and id_definicion = $id_definicion";
    $data = GenerarArray( $sql );
    echo json_encode( array(
      'error' => false,
      'message' => '',
      'data' => $data
    ) );
    break;

  case 'editarPerfilDefinicion':

    // Recibir datos
    $sw = $_POST[ 'sw' ];
    $bodega = $_POST[ 'bodega' ];
    $id_definicion = $_POST[ 'id_definicion' ];
    $ret_fte = $_POST[ 'ret_fte' ] ?? null;
    $ret_iva = $_POST[ 'ret_iva' ] ?? null;
    $ret_ica = $_POST[ 'ret_ica' ] ?? null;
    $ret_cree = $_POST[ 'ret_cree' ] ?? null;

    // 1️⃣ Verificar si existe la combinación
    $sqlCheck = "SELECT COUNT(*) as total 
                 FROM t_perfil_tributario_definiciones 
                 WHERE sw = $sw AND bodega = $bodega AND id_definicion = $id_definicion";

    $rowCheck = mysqli_fetch_assoc( Query( $sqlCheck ) );

    if ( $rowCheck[ 'total' ] > 0 ) {
      // Existe → actualizamos
      $sqlUpdate = "UPDATE t_perfil_tributario_definiciones
                      SET ret_fte = " . ( $ret_fte !== null ? "'$ret_fte'" : "NULL" ) . ",
                          ret_iva = " . ( $ret_iva !== null ? "'$ret_iva'" : "NULL" ) . ",
                          ret_ica = " . ( $ret_ica !== null ? "'$ret_ica'" : "NULL" ) . ",
                          ret_cree = " . ( $ret_cree !== null ? "'$ret_cree'" : "NULL" ) . "
                      WHERE sw = $sw AND bodega = $bodega AND id_definicion = $id_definicion";

      $q = Query( $sqlUpdate );

      if ( $q ) {
        echo json_encode( [
          'error' => false,
          'message' => 'Perfil tributario actualizado correctamente'
        ] );
      } else {
        echo json_encode( [
          'error' => true,
          'message' => 'No fue posible actualizar el perfil tributario'
        ] );
      }

    } else {
      // No existe → insertamos
      $sqlInsert = "INSERT INTO t_perfil_tributario_definiciones
                      (id_definicion, sw, bodega, ret_fte, ret_iva, ret_ica, ret_cree)
                      VALUES
                      ($id_definicion, $sw, $bodega,
                       " . ( $ret_fte !== null ? "'$ret_fte'" : "NULL" ) . ",
                       " . ( $ret_iva !== null ? "'$ret_iva'" : "NULL" ) . ",
                       " . ( $ret_ica !== null ? "'$ret_ica'" : "NULL" ) . ",
                       " . ( $ret_cree !== null ? "'$ret_cree'" : "NULL" ) . ")";

      $q = Query( $sqlInsert );

      if ( $q ) {
        echo json_encode( [
          'error' => false,
          'message' => 'Perfil tributario creado correctamente'
        ] );
      } else {
        echo json_encode( [
          'error' => true,
          'message' => 'No fue posible crear el perfil tributario'
        ] );
      }
    }

    break;
}