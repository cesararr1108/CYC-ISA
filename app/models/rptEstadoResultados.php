<?php
session_start();
require_once( 'bd.php' );
require_once( 'funciones.php' );
require_once( '../shared/servicios/servicios-email.php' );
$conn = conectar();
switch ($_POST["op"]) {
  case 'consultar-estado-resultados':
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
    $sql = " select 
                  v.categoria,
                  v.cuenta,
                  v.descripcion,
                  sum(v.total_debito) as total_debito,
                  sum(v.total_credito) as total_credito,
                  sum(v.resultado) as resultado
              from v_estado_resultados v 
              where 
                (v.total_credito > 0 or v.total_debito > 0) and 
                v.fecha BETWEEN  '" . $_POST[ "fhIni" ] . "' and '" . $_POST[ "fhFin" ] . "'
              GROUP BY
                v.categoria,
                v.cuenta,
                v.descripcion
    ";

    // Usar función preparada (según cómo esté hecha GenerarArray)
    $q = GenerarArray($sql);

    echo json_encode([
      "success" => true,
      "data"    => $q
    ]);
    break;
}
