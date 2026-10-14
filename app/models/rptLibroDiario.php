<?php
session_start();
require_once( 'bd.php' );
require_once( 'funciones.php' );
require_once( '../shared/servicios/servicios-email.php' );
$conn = conectar();
switch ($_POST["op"]) {
  case 'consultar-libro':
    // Validar fechas
    $fhIni = isset($_POST["fhIni"]) ? $_POST["fhIni"] : null;
    $fhFin = isset($_POST["fhFin"]) ? $_POST["fhFin"] : null;

    if (!$fhIni || !$fhFin) {
      echo json_encode([
        "success" => false,
        "error"   => "Fechas inválidas o no enviadas"
      ]);
      break;
    }
    $sql = "
      SELECT 
        v.fecha,
        v.tipo,
        v.numero,
        v.cuenta,
        v.cuenta_desc,
        v.nit,
        v.razon_comercial,
        v.tratamiento,
        v.debito,
        v.credito
      FROM v_libro_diario v
      WHERE  
        v.fecha between '" . $_POST[ "fhIni" ] . "' and '" . $_POST[ "fhFin" ] . "'
      ORDER BY 
        v.fecha, 
        v.numero
    ";

    // Usar función preparada (según cómo esté hecha GenerarArray)
    $q = GenerarArray($sql);

    echo json_encode([
      "success" => true,
      "data"    => $q
    ]);
    break;
}
