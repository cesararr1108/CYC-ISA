<?php
session_start();
require_once( 'bd.php' );
require_once( 'funciones.php' );
require_once('../../assets/tcpdf/tcpdf.php');

$conn = conectar();
if ( !sessionExists() ) {
  echo json_encode( array(
    'error' => true,
    'message' => 'La sesion se ha cerrado, por favor ingrese nuevamente'
  ) );
  return;
}

switch ( $_POST[ "op" ] ) {
  case 's_ventas':
    $fhIni = $_POST[ "fechaInicio" ];
    $fhFin = $_POST[ "fechaFin" ];
    $tipo = $_POST[ "tipo" ];
    $sql = "SELECT 
                v.id_bodega,
                v.desc_bodega,
                v.tipo ,
                v.numero ,
                v.usuario ,
                v.documento_electronico,
                v.nit,
                v.nombres as tratamiento,
                v.razon_comercial ,
                v.fecha_documento  as fecha,
                v.vencimiento_documento  as vencimiento,
                round(sum(v.precio_neto_unidad * v.cantidad),0) as valor_total,
                MIN(v.id_transaccion) as id_transaccion,
                MAX(v.sw) as sw
            FROM v_ventas_general v 
            WHERE 
               v.fecha_documento BETWEEN '$fhIni' AND '$fhFin'";
    if (!empty($tipo)) {
        $sql .= " AND v.documento_electronico = '$tipo'";
    }
    $sql .= " GROUP BY 
                v.id_bodega,
                v.desc_bodega,
                v.tipo ,
                v.numero ,
                v.usuario ,
                v.nit,
                v.documento_electronico,
                v.nombres,
                v.razon_comercial ,
                v.fecha_documento ,
                v.vencimiento_documento ";
    $q = GenerarArray( $sql );
    echo json_encode( $q );
    break;

  case 'export_csv':
    $fhIni = $_POST[ "fechaInicio" ];
    $fhFin = $_POST[ "fechaFin" ];
    
    $sql = "SELECT 
                v.id_bodega,
                v.desc_bodega,
                v.tipo ,
                v.numero ,
                v.usuario ,
                v.nombres as tratamiento,
                v.razon_comercial ,
                v.fecha_documento  as fecha,
                v.vencimiento_documento  as vencimiento,
                round(sum(v.precio_neto_unidad * v.cantidad),0) as valor_total
            FROM v_ventas_general v 
            WHERE 
               v.fecha_documento BETWEEN '$fhIni' AND '$fhFin'
            GROUP BY 
                v.id_bodega,
                v.desc_bodega,
                v.tipo ,
                v.numero ,
                v.usuario ,
                v.nombres,
                v.razon_comercial ,
                v.fecha_documento,
                v.vencimiento_documento";
    
    $result = mysqli_query($conn, $sql);
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="ventas_' . date('Y-m-d_H-i-s') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Headers
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM para UTF-8
    fputcsv($output, ['ID Bodega', 'Desc. Bodega', 'Tipo', 'Número', 'Usuario', 'Tratamiento', 'Razón Comercial', 'Fecha', 'Vencimiento', 'Valor Total'], ';');
    
    while ($record = mysqli_fetch_assoc($result)) {
      fputcsv($output, [
        $record['id_bodega'],
        $record['desc_bodega'],
        $record['tipo'],
        $record['numero'],
        $record['usuario'],
        $record['tratamiento'],
        $record['razon_comercial'],
        $record['fecha'],
        $record['vencimiento'],
        $record['valor_total']
      ], ';');
    }
    
    fclose($output);
    exit();
    break;

  case 'export_pdf':
    $fhIni = $_POST[ "fechaInicio" ];
    $fhFin = $_POST[ "fechaFin" ];
    
    $sql = "SELECT 
                v.id_bodega,
                v.desc_bodega,
                v.tipo ,
                v.numero ,
                v.usuario ,
                v.nombres as tratamiento,
                v.razon_comercial ,
                v.fecha_documento  as fecha,
                v.vencimiento_documento  as vencimiento,
                round(sum(v.precio_neto_unidad * v.cantidad),0) as valor_total
            FROM v_ventas_general v 
            WHERE 
               v.fecha_documento BETWEEN '$fhIni' AND '$fhFin'
            GROUP BY 
                v.id_bodega,
                v.desc_bodega,
                v.tipo ,
                v.numero ,
                v.usuario ,
                v.nombres,
                v.razon_comercial ,
                v.fecha_documento,
                v.vencimiento_documento";
    
    $result = mysqli_query($conn, $sql);
    
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetDefaultMonospacedFont('courier');
    $pdf->SetMargins(8, 8, 8);
    $pdf->AddPage('L'); // Landscape
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'REPORTE DE VENTAS', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, 'Período: ' . $fhIni . ' a ' . $fhFin, 0, 1, 'C');
    $pdf->Ln(3);
    
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(200, 220, 255);
    $pdf->Cell(12, 6, 'ID', 1, 0, 'C', true);
    $pdf->Cell(18, 6, 'Bodega', 1, 0, 'C', true);
    $pdf->Cell(12, 6, 'Tipo', 1, 0, 'C', true);
    $pdf->Cell(15, 6, 'Número', 1, 0, 'C', true);
    $pdf->Cell(15, 6, 'Usuario', 1, 0, 'C', true);
    $pdf->Cell(20, 6, 'Tratamiento', 1, 0, 'L', true);
    $pdf->Cell(35, 6, 'Razón Comercial', 1, 0, 'L', true);
    $pdf->Cell(15, 6, 'Fecha', 1, 0, 'C', true);
    $pdf->Cell(18, 6, 'Vencimiento', 1, 0, 'C', true);
    $pdf->Cell(22, 6, 'Valor Total', 1, 1, 'R', true);
    
    $pdf->SetFont('helvetica', '', 7);
    while ($record = mysqli_fetch_assoc($result)) {
      $pdf->Cell(12, 5, $record['id_bodega'], 1, 0, 'C');
      $pdf->Cell(18, 5, substr($record['desc_bodega'], 0, 12), 1, 0, 'C');
      $pdf->Cell(12, 5, $record['tipo'], 1, 0, 'C');
      $pdf->Cell(15, 5, $record['numero'], 1, 0, 'C');
      $pdf->Cell(15, 5, $record['usuario'], 1, 0, 'C');
      $pdf->Cell(20, 5, substr($record['tratamiento'], 0, 15), 1, 0, 'L');
      $pdf->Cell(35, 5, substr($record['razon_comercial'], 0, 30), 1, 0, 'L');
      $pdf->Cell(15, 5, $record['fecha'], 1, 0, 'C');
      $pdf->Cell(18, 5, $record['vencimiento'], 1, 0, 'C');
      $pdf->Cell(22, 5, '$' . number_format($record['valor_total'], 0, '.', ','), 1, 1, 'R');
    }
    
    $pdf->Output('ventas_' . date('Y-m-d_H-i-s') . '.pdf', 'D');
    exit();
    break;
}

/**
 * Genera un archivo XLSX real (compatible con Excel)
 */
function generarExcel($records, $fhIni, $fhFin, $totalGeneral) {
  // Esta función ya no se usa - Excel se exporta desde JavaScript usando XLSX.js
}