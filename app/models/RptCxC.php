<?php
session_start();
require_once( 'bd.php' );
require_once( 'funciones.php' );
$conn = conectar();

$fecha_inicial = $_POST[ "fecha_inicial" ] ?? null;
$fecha_final = $_POST[ "fecha_final" ] ?? null;
$id_tercero = $_POST[ "id_tercero" ] ?? null;

switch ( $_POST[ "op" ] ) {
  case 'consultar':
    $sql = "
      SELECT 
        v.valor_total,
        v.valor_aplicado,
        v.saldo,
        v.nit,
        v.razon_comercial,
        v.primer_nombre,
        v.primer_apellido,
        v.segundo_nombre,
        v.segundo_apellido,
        v.numero,
        v.tipo,
        v.fecha,
        v.id_tercero,
        v.tratamiento,
        v.documento,
        v.notas
      FROM v_cxc v
      WHERE 1=1
    ";

    // Filtros dinámicos
    if ( !empty( $id_tercero ) ) {
      $sql .= " AND v.id_tercero = '" . $id_tercero . "'";
    }

    if ( !empty( $fecha_inicial ) && !empty( $fecha_final ) ) {
      $sql .= " AND cast(v.fecha as date) BETWEEN cast('" . $fecha_inicial . "' as date)  AND cast('" . $fecha_final . "' as date)";
    }
    $sql .= " ORDER BY v.fecha DESC";

    $data = generarArray( $sql );
    echo json_encode( $data );
    break;
}
?>
