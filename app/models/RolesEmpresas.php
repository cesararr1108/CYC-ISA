<?php
session_start();
require_once( 'bd.php' );
require_once( 'funciones.php' );
$conn = conectar();

switch($_POST["op"]){

    case "agregar":
    break;
    case "update":
    break;
}