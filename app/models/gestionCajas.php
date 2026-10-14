<?php
session_start();
require_once( 'bd.php' );
require_once( 'funciones.php' );
$conn = conectar();

function responder( $error, $message, $data = null ) {
  echo json_encode( array(
    'error' => $error,
    'message' => $message,
    'data' => $data
  ) );
}

switch ( $_POST[ "op" ] ) {
  case "listar-cuentas":
    $sql = "SELECT 
                    id,
                    cuenta,
                    upper(descripcion) as descripcion
                FROM t_cuentas_contables tcc
                WHERE 
                    LEFT(tcc.cuenta, 4) IN ('1110', '1105','1120')";
    $data = GenerarArray( $sql );
    responder( false, 'success', $data );
    break;
  case 'listar-cuentas-conceptos':
    $sql = "SELECT
                    id,
                    cuenta,
                    upper(descripcion) as descripcion
                FROM t_cuentas_contables tcc
                WHERE
                    LEFT(tcc.cuenta, 1) IN ('4', '5' ,'2')
                ORDER BY tcc.cuenta";
    $data = GenerarArray( $sql );
    responder( false, 'success', $data );
    break;
  case 'guardar-caja-banco':
    $data = $_POST;
    $cuenta = $data[ 'cuenta' ];
    $q = GenerarArray( "SELECT id FROM t_cajas_bancos WHERE cuenta = '$cuenta'" );

    if ( count( $q ) > 0 ) {
      responder( true, 'La cuenta ya ha sido asociada, no es posible asociarla nuevamente.' );
      return;
    }

    $id = insertReturnId( 't_cajas_bancos', $data, true );
    if ( $id > 0 ) {
      responder( false, 'Caja/Banco creado correctamente.' );
    } else {
      responder( true, 'No se pudo crear la Caja/Banco' );
    }
    break;
  case 'editar-caja-banco':
    $data = $_POST;
    $cuenta = $data[ 'cuenta' ];
    /*$query = GenerarArray( "SELECT COUNT(*) as movimientos  FROM t_movimiento_contable tmc WHERE tmc.cuenta = '$cuenta'" );

    if ( $query[ 0 ][ 'movimientos' ] > 0 ) {
      responder( true, 'La cuenta presenta movimientos, no es posible su eliminación.' );
      return;
    }*/
    $update = update( 't_cajas_bancos', $data, $data[ "id" ] );
    if ( $update ) {
      responder( false, 'Caja/Banco actualizada correctamente.', $data );
    } else {
      responder( true, 'No se pudo actualizar la Caja/Banco' );
    }
    break;
  case 'listar-caja-banco':
    $sql = "select 
                c.id,
                c.cuenta,
                c.descripcion ,
                c.fecha,
                c.nombre ,
                c.saldo_inicial 
            from t_cajas_bancos c ";
    $data = GenerarArray( $sql );
    responder( false, 'success', $data );
    break;
  case 'eliminar-caja-banco':
    $data = $_POST;
    $id = $data[ 'id' ];
    $cuenta = $data[ 'cuenta' ];
    $query = GenerarArray( "SELECT COUNT(*) as movimientos  FROM t_movimiento_contable tmc WHERE tmc.cuenta = '$cuenta'" );

    if ( $query[ 0 ][ 'movimientos' ] > 0 ) {
      responder( true, 'La cuenta presenta movimientos, no es posible su eliminación.' );
      return;
    }
    $q = Query( "delete from t_cajas_bancos where id = $id" );
    if ( $q ) {
      responder( false, 'Caja/Banco eliminado correctamente.' );
    } else {
      responder( true, 'No se pudo eliminar la Caja/Banco' );
    }
    break;

  case 'listar-bodegas':
    $sql = "select id, upper(descripcion) as descripcion
            from t_almacenes
            where ifnull(inactivo,'0') = '0'
            order by descripcion";
    responder( false, 'success', GenerarArray( $sql ) );
    break;

  case 'listar-conceptos-cajas':
    $filtro = trim( $_POST['filtro'] ?? '' );
    $sql = "select
              cc.id,
              cc.id_bodega,
              upper(case when cc.id_bodega = 0 then 'TODAS' else ifnull(a.descripcion,'') end) as bodega,
              cc.cuenta,
              upper(ifnull(tc.descripcion,'')) as cuenta_descripcion,
              cc.concepto,
              cc.descripcion,
              cc.tipo
            from t_cajas_conceptos cc
            left join t_almacenes a on a.id = cc.id_bodega
            left join t_cuentas_contables tc on tc.cuenta = cc.cuenta";
    if ( $filtro != '' ) {
      $sql .= " where cc.concepto like '%$filtro%'
                or cc.cuenta like '%$filtro%'
                or a.descripcion like '%$filtro%'
                or ifnull(cc.descripcion,'') like '%$filtro%'";
    }
    $sql .= " order by cc.id desc";
    responder( false, 'success', GenerarArray( $sql ) );
    break;

  case 'guardar-concepto-caja':
    $data = $_POST;
    $idBodega = intval( $data['id_bodega'] ?? 0 );
    $cuenta = trim( $data['cuenta'] ?? '' );
    $concepto = trim( $data['concepto'] ?? '' );
    $tipo = strtoupper( trim( $data['tipo'] ?? '' ) );

    if ( $idBodega < 0 || $cuenta == '' || $concepto == '' || ( $tipo != 'E' && $tipo != 'I' ) ) {
      responder( true, 'Debe diligenciar bodega, cuenta, concepto y tipo validos.' );
      return;
    }

    $existe = GenerarArray( "select id from t_cajas_conceptos where id_bodega = $idBodega and concepto = '$concepto' and tipo = '$tipo' limit 1" );
    if ( count( $existe ) > 0 ) {
      responder( true, 'Ya existe un concepto con el mismo nombre y tipo para esta bodega.' );
      return;
    }

    $id = insertReturnId( 't_cajas_conceptos', $data, true );
    if ( $id > 0 ) {
      responder( false, 'Concepto de caja creado correctamente.', array( 'id' => $id ) );
    } else {
      responder( true, 'No se pudo crear el concepto de caja.' );
    }
    break;

  case 'editar-concepto-caja':
    $data = $_POST;
    $id = intval( $data['id'] ?? 0 );
    $idBodega = intval( $data['id_bodega'] ?? 0 );
    $cuenta = trim( $data['cuenta'] ?? '' );
    $concepto = trim( $data['concepto'] ?? '' );
    $tipo = strtoupper( trim( $data['tipo'] ?? '' ) );

    if ( $id <= 0 || $idBodega < 0 || $cuenta == '' || $concepto == '' || ( $tipo != 'E' && $tipo != 'I' ) ) {
      responder( true, 'Debe diligenciar bodega, cuenta, concepto y tipo validos.' );
      return;
    }

    $existe = GenerarArray( "select id from t_cajas_conceptos where id_bodega = $idBodega and concepto = '$concepto' and tipo = '$tipo' and id != $id limit 1" );
    if ( count( $existe ) > 0 ) {
      responder( true, 'Ya existe un concepto con el mismo nombre y tipo para esta bodega.' );
      return;
    }

    $ok = update( 't_cajas_conceptos', $data, $id );
    if ( $ok ) {
      responder( false, 'Concepto de caja actualizado correctamente.', $data );
    } else {
      responder( true, 'No se pudo actualizar el concepto de caja.' );
    }
    break;

  case 'eliminar-concepto-caja':
    $id = intval( $_POST['id'] ?? 0 );
    if ( $id <= 0 ) {
      responder( true, 'ID invalido para eliminar el concepto.' );
      return;
    }

    $ok = Query( "delete from t_cajas_conceptos where id = $id" );
    if ( $ok ) {
      responder( false, 'Concepto de caja eliminado correctamente.' );
    } else {
      responder( true, 'No se pudo eliminar el concepto de caja.' );
    }
    break;

  default:
    responder( true, 'Operacion no valida.' );
    break;
}