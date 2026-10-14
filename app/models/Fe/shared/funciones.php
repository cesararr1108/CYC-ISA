<?php
function procesarRespuesta( $respuesta, $tipo, $numero) {
  if ( $respuesta[ 'data' ][ 'IsValid' ] == 'true' || $respuesta[ 'data' ][ 'ErrorMessage' ] == 'Regla: 90, Rechazo: Documento procesado anteriormente.' ) {
    //valida si ya fue enviado previamente y verifico si existe en la tabla , sino loa grego 
    $existe = [];
    if ( $respuesta[ 'data' ][ 'ErrorMessage' ] == 'Regla: 90, Rechazo: Documento procesado anteriormente.' ) {
      $existe = GenerarArray( "select * from t_respuestas_dian where numero=" . $numero . " and tipo='" . $tipo . "'" );
      if ( count( $existe ) > 0 ) {
        return;
      }
    }

    $data = array(
      'tipo' => $tipo,
      'numero' => $numero,
      'cufe' => $respuesta[ 'data' ][ 'UUID' ],
      'QRCode'=>$respuesta['data']['QRCode'],
      'XmlFileName' => $respuesta[ 'data' ][ 'XmlFileName' ],
      'fechaEnvio' => trim( substr( $respuesta[ 'data' ][ 'fechaEnvio' ], 0, 19 ) ), //19
      'fechaValidacion' => trim( substr( $respuesta[ 'data' ][ 'fechaValidacion' ], 0, 19 ) )
    );
    $id = insertReturnId( 't_respuestas_dian', $data ,false) ;
  }
}