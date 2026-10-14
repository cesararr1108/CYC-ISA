<?php
session_start();
require 'config.php';
require_once( 'funciones.php' );

if ( empty( $_POST[ 'empresa' ] ) ) {
  echo json_encode( [
    'error' => true,
    'valido' => false,
    'mensaje' => 'No se ha enviado la empresa!'
  ] );
  return;
}

$dbName = $_POST[ 'empresa' ];

if ( !databaseExists( $dbName ) ) {
  echo json_encode( [
    'error' => true,
    'valido' => false,
    'mensaje' => 'La empresa no existe!'
  ] );
  return;
} else {
  $_SESSION[ "Ses_empresa" ] = $dbName;
  echo json_encode( [
    'error' => false,
    'valido' => true,
    'mensaje' => 'La empresa existe!'
  ] );
}

function databaseExists( $dbName ) {
  $serverName = SERVER;
  $username = USER;
  $password = PASS;
  $conn = new mysqli( $serverName, $username, $password );
  if ( $conn->connect_error ) {
    die( "Conexión fallida: " . $conn->connect_error );
  }
  $result = $conn->query( "SHOW DATABASES LIKE '$dbName'" );
  if ( $result && $result->num_rows > 0 ) {
    return true; // La base de datos existe
  } else {
    return false; // La base de datos no existe
  }
  // Cerrar la conexión
  $conn->close();
}