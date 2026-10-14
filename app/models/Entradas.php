<?php
session_start();
require_once( 'bd.php' );
require_once( 'funciones.php' );
$conn = conectar();

switch ( $_POST[ "op" ] ) {

  case 'cargar_importados':
    $data = json_decode( $_POST[ 'datos' ], true );
  //  $eliminarInventario = $_POST[ 'EliminarInv' ] ?? 'N';

    // Validar que datos no estén vacíos
    if ( empty( $data ) || !is_array( $data ) ) {
      echo json_encode( [ 'errores' => [ 'No se recibieron datos válidos' ] ] );
      return;
    }

    // Validar parámetros obligatorios
    $requiredFields = [ 'id_bodega', 'id_tipo_documento', 'id_tercero', 'id_tercero_dir', 'id_condicion' ];
    foreach ( $requiredFields as $field ) {
      if ( empty( $_POST[ $field ] ) ) {
        echo json_encode( [ 'errores' => [ "Falta el parámetro obligatorio: $field" ] ] );
        return;
      }
    }
  /*
    if($eliminarInventario=='S'){
            // Reset de tablas temporales
      Query( "TRUNCATE TABLE t_referencias_lotes;" );
      Query( "TRUNCATE TABLE t_transacciones_detalle_tmp;" );
      Query( "TRUNCATE TABLE t_transacciones_tmp;" );
      Query( "UPDATE t_tipos_documentos SET numero = 0 " );
      //Esto es nuevo para preguntarle a cesar
      Query( "DELETE FROM t_transacciones " );
      Query( "DELETE FROM t_transacciones_detalle " );
      Query( "DELETE FROM t_referencias_mov " );
        
    }*/

        

    $errores = [];
    $datos_validos = [];
    $codigosCargados = [];
    $fila = 1;

    foreach ( $data as $row ) {
      $codigo = trim( $row[ 'codigo' ] ?? '' );
      $costo = $row[ 'costo' ] ?? null;
      $cantidad = $row[ 'cantidad' ] ?? null;

      // Validar campos obligatorios
      if ( $codigo === '' || $costo === null || $cantidad === null ) {
        $errores[] = "Fila $fila: faltan datos obligatorios (codigo, costo, cantidad)";
        $fila++;
        continue;
      }
/*
      if($archico_lotes === 'S') {
          $lote = trim( $row[ 'lote' ] ?? '' );
          $vencimiento = trim( $row[ 'vencimiento' ] ?? '' );

          if ( $lote === '' || $vencimiento === '' ) {
            $errores[] = "Fila $fila: faltan datos obligatorios para archivo de lotes (lote, vencimiento)";
            $fila++;
            continue;
          }

      }else{
          // Validar duplicados
          if ( in_array( $codigo, $codigosCargados ) ) {
            $errores[] = "Fila $fila: código duplicado $codigo";
            $fila++;
            continue;
          }
      }*/
    
      $lote = trim( $row[ 'lote' ] ?? '' );
      $vencimiento = trim( $row[ 'vmcto' ] ?? '' );

      if ( $lote === '' || $vencimiento === '' ) {
        $errores[] = "Fila $fila: faltan datos obligatorios para archivo de lotes (lote, vencimiento)";
        $fila++;
        continue;
      }

      // Validar existencia en referencias
      $ref = GenerarArray( "
            SELECT r.id, r.codigo, i.porcentaje_iva
            FROM t_referencias r
            INNER JOIN t_iva i ON i.id = r.id_iva
            WHERE r.codigo = '$codigo'
        " );

      if ( count( $ref ) === 0 ) {
        $errores[] = "Fila $fila: código $codigo no existe en referencias";
        $fila++;
        continue;
      }

      // Validar numéricos
      if ( !filter_var( $cantidad, FILTER_VALIDATE_INT, [ "options" => [ "min_range" => 1 ] ] ) ) {
        $errores[] = "Fila $fila: cantidad inválida ($cantidad) para código $codigo";
        $fila++;
        continue;
      }
      if ( !filter_var( $costo, FILTER_VALIDATE_FLOAT ) || $costo <= 0 ) {
        $errores[] = "Fila $fila: costo inválido ($costo) para código $codigo";
        $fila++;
        continue;
      }

      $loteExist = GenerarArray( "
            SELECT id FROM t_referencias_lotes 
            WHERE id_referencia = {$ref[0]['id']} AND lote = '$lote'
        " );

      if ( count( $loteExist ) > 0 ) {
        $id_lote = $loteExist[ 0 ][ 'id' ];
      } else {
        $fields = [
          "id_referencia" => $ref[ 0 ][ "id" ],
          "lote" => $lote,
          "vencimiento" => $vencimiento
        ];
        $id_lote = insertReturnId( 't_referencias_lotes', $fields, true );
      }

      $datos_validos[] = [
        "id_referencia" => $ref[ 0 ][ "id" ],
        "cantidad" => ( int )$cantidad,
        "codigo" => $codigo,
        "valor_unitario" => ( float )$costo,
        "costo_unitario" => ( float )$costo,
        "id_lote" => $id_lote,
        "pcj_iva" => $ref[ 0 ][ "porcentaje_iva" ],
        "pcj_descuento" => 0,
        "und" => 'und',
        "can_und" => 1,
        "vmcto" => $vencimiento,
        "lote" => $lote,
        "sw" => 2,
        "id_bodega" => $_POST[ "id_bodega" ],
        "id_pedido" => 0,
        "impoconsumo" => 0,
        "ibua" => 0,
        "icui" => 0,
        "id_transaccion_ref" => 0,
        "fec" => date( "Y-m-d" )
      ];

      $codigosCargados[] = $codigo;
      $fila++;
    }

    // Calcular totales
    $valor_total = 0;
    foreach ( $datos_validos as $item ) {
      $valor_total += $item[ "costo_unitario" ] * $item[ "cantidad" ];
    }

    if ( $valor_total <= 0 ) {
      echo json_encode( [ 'errores' => [ 'El valor total debe ser mayor a 0' ] ] );
      return;
    }

    // Insertar encabezado
    $numero = numeroSiguiente( $_POST[ "id_tipo_documento" ] );
    $data_enc = [
      "numero" => $numero,
      "id_tipo_documento" => $_POST[ "id_tipo_documento" ],
      "id_usuario" => $_SESSION[ "Ses_id" ],
      "id_tercero" => $_POST[ "id_tercero" ],
      "id_bodega" => $_POST[ "id_bodega" ],
      "notas" => $_POST[ "notas" ] ?? '',
      "valor_total" => $valor_total,
      "sw" => 2,
      "id_tercero_dir" => $_POST[ "id_tercero_dir" ],
      "id_pedido" => 0,
      "id_condicion" => $_POST[ "id_condicion" ],
      "lista" => 0,
      "iva" => 0,
      "descuento" => 0,
      "zona_ventas" => '',
      "zona_descripcion" => '',
      "vendedor" => 0,
      "fecha" => date( "Y-m-d" ),
      "documento" => '',
      "ibua" => 0,
      "icui" => 0,
      "impoconsumo" => 0,
      "valor_aplicado" => 0,
      "vencimiento" => date( "Y-m-d" )
    ];

    $insertEnc = insertReturnId( 't_transacciones_tmp', $data_enc, true );

    if ( $insertEnc > 0 ) {
      $det_insert = [];
      foreach ( $datos_validos as $key ) {
        $key[ "id_transaccion" ] = $insertEnc;
        $codigo = $key[ "codigo" ];
        unset( $key[ "codigo" ] );

        $detalleInsert = insertReturnId( 't_transacciones_detalle_tmp', $key, true );

        if ( $detalleInsert > 0 ) {
          $det_insert[] = $codigo;
        } else {
          $errores[] = "No se insertó el código $codigo";
        }
      }
    } else {
      echo json_encode( [ 'errores' => [ 'No se insertó el encabezado' ] ] );
      return;
    }

    // Respuesta final
    header( "Content-Type: application/json" );
    echo json_encode( [
      "errores" => $errores,
      "datos_validos" => $datos_validos,
      "data_enc" => $data_enc,
      "det_insert" => $det_insert ?? [],
      "id" => $insertEnc
    ] );
    break;
}