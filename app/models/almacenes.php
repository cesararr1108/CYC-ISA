<?php
session_start();
require_once( 'bd.php' );
require_once( 'funciones.php' );
$conn = conectar();

switch ( $_POST[ "op" ] ) {
  case "listar":

    $filtro = isset( $_POST[ "filtro" ] ) && $_POST[ "filtro" ] != '' ? $_POST[ "filtro" ] : '';
    $parametros = [];
    $sql = "select 
                a.id,
                a.descripcion,
                a.almacen,
                ifnull(a.centro,0) as centro,
                a.direccion,
                a.telefono,
                case when a.transito= 1 then 'Si' else 'No' end as transito_des,
                a.transito,
                a.departamento,
                a.municipio,
                d.departamento as dpto_des,
                m.municipio as municipio_des,
                a.usuario_creacion,
                a.fecha_creacion,
                a.tipo_doc_pos,
                c.centro as centro_des,
                a.inactivo,
                a.lista_precios,
                ifnull(a.ean_edi,'') as ean_edi
        from t_almacenes a
        left join t_centros_costos    c on  c.id             =a.centro
        left join t_departamentos      d on  d.codigo_dpto    =a.departamento
        left join t_municipios         m on  m.codigo_dpto    =a.departamento and m.codigo=a.municipio 
     
        ";

    if ( !empty( $filtro ) ) {
      $sql .= " where   a.descripcion like ?  ";
      $parametros[] = array(
          'tipo'=>'s',
          'valor'=>"%$filtro%"
      );
    }
    $sql .= " order by a.almacen  ";

    $centros = GenerarArrayPrepare( $sql, $parametros );
    echo json_encode( array(
      'error' => false,
      'message' => 'success',
      'data' => $centros
    ) );
    break;
  case "agregar":

    if ( $_SESSION[ "Ses_empresa" ] != 'CYC' ) {

      $empresa = $_SESSION[ "Ses_empresa" ];
      $connCore = conectarCore();
      $parametros = [];
      $sql = "SELECT bodegas FROM t_empresas WHERE db_name = ? AND activo = 1";
      $parametros[] = array(
          'tipo'=>'s',
          'valor'=>$empresa
      );
      $plan = GenerarArrayPrepare( $sql, $parametros  , $connCore );

      if ( !$plan ) {
        echo json_encode( [
          'error' => true,
          'message' => 'Empresa no válida o inactiva',
          'id' => 0
        ] );
        return;
      }

      $limiteAlmacenes = ( int )$plan[ 0 ][ "bodegas" ];

      /* 3️⃣ Contar bodegas activas en la empresa */
      $res = GenerarArray( " SELECT COUNT(*) as total FROM t_almacenes " );
      $almacenesActuales = ( int )$res[ 0 ][ "total" ];


      /* 4️⃣ Validar límite */
      if ( $almacenesActuales >= $limiteAlmacenes ) {
        echo json_encode( [
          'error' => true,
          'message' => 'Has alcanzado el límite de ' . $limiteAlmacenes . ' almacenes de tu plan actual, consulte el area comercial para ampliarlo',
          'id' => 0
        ] );
        return;
      }
    }
    /* 5️⃣ Crear usuario */
    $data = $_POST;
    $data[ "usuario_creacion" ] = $_SESSION[ "Ses_id" ];
    $id = insertReturnId( 't_almacenes', $data, true );

    if ( $id > 0 ) {
      echo json_encode( [
        'error' => false,
        'message' => 'Almacen creado correctamente',
        'data' => $data
      ] );
    } else {
      echo json_encode( [
        'error' => true,
        'message' => 'Error al crear Almacen'
      ] );
    }

    break;
  case "update":
    $data = $_POST;

    $update = update( 't_almacenes', $data, $data[ "id" ] );

    if ( $update ) {
      echo json_encode( array(
        'error' => false,
        'message' => 'Se actualizo correctamente el registro',
        'data' => $data
      ) );
    } else {
      echo json_encode( array(
        'error' => true,
        'message' => 'No se pudo actualizar el registro',
        'data' => $data
      ) );
    }
    break;
  case "delete":
    $id = $_POST[ "id" ];

    $sw = validarDependencias( [ 't_transacciones', 't_transacciones_tmp', 't_referencias_mov' ], 'id_bodega', $id );

    if ( !$sw ) {
      echo json_encode( array(
        'error' => true,
        'message' => 'No se puede eliminar el registro ya ue existen dependencias ',
        'data' => null
      ) );
      return;
    }

    $delete = Query( "delete from t_almacenes where id=$id" );

    if ( $delete ) {
      echo json_encode( array(
        'error' => false,
        'message' => 'Se elimino correctamente el registro',
        'data' => null
      ) );
    } else {
      echo json_encode( array(
        'error' => true,
        'message' => 'Error al eliminar el registro',
        'data' => null
      ) );
    }
    break;

}