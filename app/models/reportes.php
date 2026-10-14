<?php
session_start();
require_once( 'bd.php' );
require_once( 'funciones.php' );
$conn = conectar();

switch ( $_POST[ "op" ] ) {
  case "s_modulos_reportes":
    try {

      $rol = $_SESSION[ "Ses_roles_id" ];
      $grupos = GenerarArray( " select id, grupo,icono,color from t_grupos_modulos_rpt " );
      $menu = [];
      foreach ( $grupos as $g ) {
        $modulos = GenerarArray( "select 
                                    m.grupo_rpt as id,
                                    m.url,
                                    m.titulo
                                  from 
                                  t_modulos  m
                                  inner join t_modulos_permisos   p on p.id_modulo = m.id
                                  inner join t_roles_modulos_permisos     x on x.id_modulo_permiso=p.id_modulo_permiso
                                  where 
                                    m.grupo_rpt =" . $g[ "id" ] . " and p.id_permiso = 1 and x.id_rol = $rol
                                  order by
                                    m.titulo 
                " );
        $menu[] = array(
          'grupo' => $g,
          'modulos' => $modulos
        );
      }
      echo json_encode( array(
        'status' => 200,
        'data' => $menu,
        'error' => false,
      ) );

    } catch ( \Throwable $th ) {
      echo json_encode( [
        'status' => 500,
        'message' => 'Ocurrió un error al obtener el menú',
        'error' => true,
        'details' => print_r( $th )
      ] );
    }
    break;
}