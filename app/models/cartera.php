<?php
session_start();
require_once( 'funciones.php' );
require_once( 'bd.php' );
$conn = conectar();

switch ( $_POST[ "op" ] ) {
    case "cartera-cliente":
    break;
}