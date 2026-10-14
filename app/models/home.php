<?php
session_start();
require_once( 'bd.php' );
require_once( 'funciones.php' );
$conn = conectar();

switch ( $_POST[ "op" ] ) {
  case "api/modulos/cargarmenu":

    try {

      $rol = $_SESSION[ "Ses_roles_id" ];
      $grupos = GenerarArray( "
            select 
            id, grupo, icono, color
            from t_grupos_modulos
            " );

      $menu = [];

      foreach ( $grupos as $g ) {

        $modulos = GenerarArray( "select 
                                    m.url,
                                    m.titulo
                                  from 
                                  t_modulos  m
                                  inner join t_modulos_permisos   p on p.id_modulo = m.id
                                  inner join t_roles_modulos_permisos     x on x.id_modulo_permiso=p.id_modulo_permiso
                                  where 
                                    m.grupo =" . $g[ "id" ] . " and p.id_permiso = 1 and x.id_rol = $rol
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
  case "validar_cierre":
    $ultimoperiodo = GenerarArray( "SELECT 
                                        MAX(ano) AS max_ano,
                                        (SELECT MAX(mes) 
                                         FROM t_referencias_mov 
                                         WHERE ano = (SELECT MAX(ano) FROM t_referencias_mov)
                                        ) AS max_mes
                                    FROM 
                                        t_referencias_mov;" );

    // Verificar si se obtuvieron resultados
    if ( !empty( $ultimoperiodo ) ) {
      $max_ano = $ultimoperiodo[ 0 ][ "max_ano" ];
      $max_mes = $ultimoperiodo[ 0 ][ "max_mes" ];
      $periodo_actual = DateTime::createFromFormat( 'Y-m', $max_ano . '-' . $max_mes );
      $fecha_actual = DateTime::createFromFormat( 'Y-m', date( 'Y' ) . '-' . date( 'm' ) );

      $nuevo_periodo = clone $periodo_actual;
      $nuevo_periodo->modify( '+1 month' );

      $periodo_actual_str = $periodo_actual->format( 'Y-m' );
      $nuevo_periodo_str = $nuevo_periodo->format( 'Y-m' );
      $fecha_actual_str = $fecha_actual->format( 'Y-m' );

      if ( $periodo_actual_str === $fecha_actual_str ) {
        echo json_encode( [
          'ok' => true
        ] );
      } else {
        echo json_encode( [
          'ok' => false
        ] );
      }
    }
    break;
}