<?php
session_start();
try {
  require_once( '../../bd.php' );
  require_once( '../models/modelo.php' );
  require_once( 'Enviar.php' );

  $camposRequeridos = [ 'tokenUser', 'tokenPassword' ];
  foreach ( $camposRequeridos as $campo ) {
    if ( !isset( $_POST[ $campo ] ) ) {
      throw new Exception( "Falta la variable requerida: $campo" );
    }
    if ( empty( trim( $_POST[ $campo ] ) ) ) {
      throw new Exception( "La variable '$campo' no puede estar vacía" );
    }
  }

  $tokenUser = trim( $_POST[ 'tokenUser' ] );
  $tokenPassword = trim( $_POST[ 'tokenPassword' ] );
  $tipoED = 'fv';
  $numero = 990000011;
  $tipo = 'SETP';

  $responsabilidades = array();

  $Factura = new Factura();

  // Seguridad
  $Factura->seguridad->tokenUser = $tokenUser;
  $Factura->seguridad->tokenPassword = $tokenPassword;

  // Documento
  $Factura->documento->tipo = $tipo;
  $Factura->documento->numero = $numero;
  $Factura->documento->tipoED = 'fv';
  $Factura->documento->fecha = date( 'd-m-Y' );

  // Dirección
  $Factura->cliente->direccion->codigo_ciudad = '001';
  $Factura->cliente->direccion->nombre_ciudad = 'MEDELLIN';
  $Factura->cliente->direccion->codigo_dpto = '05';
  $Factura->cliente->direccion->direccion = 'cra 52 #7-30';

  // País
  $Factura->cliente->direccion->pais->codigo_pais = 169;
  $Factura->cliente->direccion->pais->nombre_pais = 'CO';

  // Cliente
  $Factura->cliente->nombres = 'CHRISTIAN BULA MARTINEZ';
  $Factura->cliente->razon = 'DROGUERIA LA MIGAJITA';
  $Factura->cliente->nit = '1069477458';
  $Factura->cliente->digito = '9';
  $Factura->cliente->tipo_persona = '31';
  $Factura->cliente->responsabilidades = 'O-47';

  // Contacto
  $Factura->cliente->contacto->telefono = '3122595393'; // Se asigna directamente sobre la instancia ya creada
  $Factura->cliente->contacto->email = 'cesararr1108@gmail.com';

  // Forma de pago
  $Factura->formaPago->id = '2';
  $Factura->formaPago->codigo = '30';
  $Factura->formaPago->fecha = date( 'Y-m-d' );
  $Factura->formaPago->diasValidez = '30';

  $itemsProcesados = [];
  $montTotalImpuesto = 0;
  $motoBaseImpuesto = 0;

  //  foreach ($detalle as $dato) {
  $item = new Item();
  $item->codigo = '00001';
  $item->descripcion = 'PRODUCTOS TEST';
  $item->muestraGratis = false;
  $item->cantidad = '1.00';
  $item->pcjImpuesto1 = '19.00';
  $item->pcjImpuesto3 = '0.00';
  $item->pcjImpuesto4 = '0.00';
  $item->valorUnitario = '1000.00'; // sin desc sin iva
  $item->pcjDescuento = '10.00';

  $Factura->agregarItem( $item );
  $baseImponible = '900.00'; // con descuento sin iva
  $valorImpuesto = '171.00'; // valor iva 


  $esquemaImpuesto = new EsquemaImpuesto( '01', 'IVA' );
  $categoriaImpuesto = new CategoriaImpuesto( '19.00', $esquemaImpuesto );
  $subtotalImpuesto = new SubtotalImpuesto(
    $baseImponible,
    $valorImpuesto,
    $categoriaImpuesto
  );

  $item->impuestoTotal = $subtotalImpuesto;
  // print_r($Factura);
  $respuesta = enviar( $tokenUser, $tokenPassword, $Factura, $tipoED );
    
  echo json_encode( array(
    'ok' => true,
    'message' => ( $respuesta )
  ) );

} catch ( \Throwable $th ) {
  echo json_encode( array(
    'ok' => false,
    'message' => ( $th )
  ) );
}