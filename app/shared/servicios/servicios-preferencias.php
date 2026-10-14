<?php
session_start();
require_once('../../models/bd.php');
require_once('../../models/funciones.php');
$conn = conectar();

$id_modulo = $_POST["id_modulo"];
$sql = " SELECT 
            id_usuario,
            id_modulo,
            clave,
            valor 
          FROM t_preferencias  
          WHERE 
          id_modulo = $id_modulo AND 
          id_usuario = " . $_SESSION["Ses_id"];
$datos = GenerarArray($sql);
echo  json_encode(array('data' => $datos));
