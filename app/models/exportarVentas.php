<?php
/**
 * ✅ EXPORTACIÓN SEGURA DEL LADO DEL SERVIDOR
 * Genera archivos Excel y CSV con todos los datos
 * Usa los mismos filtros del DataTable
 */
session_start();
require_once('bd.php');
require_once('funciones.php');

$conn = conectar();

// Validar sesión
if (!sessionExists()) {
  http_response_code(401);
  die(json_encode(array('error' => 'Sesión expirada')));
}

// Validar operación
$op = isset($_POST['op']) ? $_POST['op'] : '';
if (!in_array($op, ['export_excel', 'export_csv', 'export_pdf'])) {
  http_response_code(400);
  die(json_encode(array('error' => 'Operación no válida')));
}

// Obtener y validar filtros
$fechaInicio = isset($_POST['fechaInicio']) ? $_POST['fechaInicio'] : date('Y-m-01');
$fechaFin = isset($_POST['fechaFin']) ? $_POST['fechaFin'] : date('Y-m-d');
$search = isset($_POST['search']) ? trim($_POST['search']) : '';

// Validar que las fechas sean válidas
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio) || 
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin)) {
  http_response_code(400);
  die(json_encode(array('error' => 'Fechas inválidas')));
}

// Construir WHERE con filtros
$where = "WHERE v.fecha_documento BETWEEN '$fechaInicio' AND '$fechaFin'";

// Agregar búsqueda si existe
if (!empty($search)) {
  $searchEsc = $conn->real_escape_string($search);
  $where .= " AND (
    v.id_bodega LIKE '%$searchEsc%' OR
    v.desc_bodega LIKE '%$searchEsc%' OR
    v.tipo LIKE '%$searchEsc%' OR
    v.numero LIKE '%$searchEsc%' OR
    v.usuario LIKE '%$searchEsc%' OR
    v.nombres LIKE '%$searchEsc%' OR
    v.razon_comercial LIKE '%$searchEsc%'
  )";
}

// Query para obtener todos los datos
$sql = "SELECT 
          v.id_bodega,
          v.desc_bodega,
          v.tipo,
          v.numero,
          v.usuario,
          v.nombres as tratamiento,
          v.razon_comercial,
          v.fecha_documento as fecha,
          v.vencimiento_documento as vencimiento,
          round(sum(v.precio_neto_unidad * v.cantidad),0) as valor_total
        FROM v_ventas_general v 
        $where
        GROUP BY 
          v.id_bodega,
          v.desc_bodega,
          v.tipo,
          v.numero,
          v.usuario,
          v.nombres,
          v.razon_comercial,
          v.fecha_documento,
          v.vencimiento_documento
        ORDER BY v.fecha_documento DESC";

$result = $conn->query($sql);

if (!$result || $result->num_rows === 0) {
  http_response_code(404);
  die('No hay datos para exportar');
}

// Encabezados
$headers = array(
  'Bodega',
  'Desc. Bodega',
  'Tipo',
  'Número',
  'Usuario',
  'Tratamiento',
  'Razón Comercial',
  'Fecha',
  'Vencimiento',
  'Valor Total'
);

// Procesar según formato
switch ($op) {
  case 'export_excel':
    exportarExcel($result, $headers);
    break;
  case 'export_csv':
    exportarCSV($result, $headers);
    break;
  case 'export_pdf':
    exportarPDF($result, $headers, $fechaInicio, $fechaFin);
    break;
}

/**
 * ✅ Exportar a EXCEL (XLSX)
 */
function exportarExcel($result, $headers) {
  // Crear array de datos
  $datos = array($headers);
  
  while ($row = $result->fetch_assoc()) {
    $datos[] = array(
      $row['id_bodega'] ?? '',
      $row['desc_bodega'] ?? '',
      $row['tipo'] ?? '',
      $row['numero'] ?? '',
      $row['usuario'] ?? '',
      $row['tratamiento'] ?? '',
      $row['razon_comercial'] ?? '',
      $row['fecha'] ?? '',
      $row['vencimiento'] ?? '',
      $row['valor_total'] ?? 0
    );
  }
  
  // Usar librería nativa: generar XML manualmente
  $xlsx = crearExcelXLSX($datos, 'Ventas');
  
  // Headers de descarga
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment; filename="ventas_' . date('Y-m-d_His') . '.xlsx"');
  header('Cache-Control: max-age=0');
  header('Pragma: public');
  header('Expires: 0');
  
  echo $xlsx;
  exit;
}

/**
 * ✅ Exportar a CSV
 */
function exportarCSV($result, $headers) {
  // Headers para descarga
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="ventas_' . date('Y-m-d_His') . '.csv"');
  header('Pragma: no-cache');
  header('Expires: 0');
  
  $output = fopen('php://output', 'w');
  
  // Escribir BOM para Excel (UTF-8)
  fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
  
  // Escribir encabezados
  fputcsv($output, $headers, ';', '"');
  
  // Escribir datos
  while ($row = $result->fetch_assoc()) {
    $LineData = array(
      $row['id_bodega'] ?? '',
      $row['desc_bodega'] ?? '',
      $row['tipo'] ?? '',
      $row['numero'] ?? '',
      $row['usuario'] ?? '',
      $row['tratamiento'] ?? '',
      $row['razon_comercial'] ?? '',
      $row['fecha'] ?? '',
      $row['vencimiento'] ?? '',
      $row['valor_total'] ?? 0
    );
    fputcsv($output, $LineData, ';', '"');
  }
  
  fclose($output);
  exit;
}

/**
 * ✅ Exportar a PDF (HTML simple)
 */
function exportarPDF($result, $headers, $fechaInicio, $fechaFin) {
  // Generar HTML
  $html = '<!DOCTYPE html><html><head>';
  $html .= '<meta charset="UTF-8">';
  $html .= '<title>Reporte de Ventas</title>';
  $html .= '<style>';
  $html .= 'body { font-family: Arial, sans-serif; margin: 20px; }';
  $html .= 'h1 { text-align: center; color: #333; }';
  $html .= 'p { text-align: center; color: #666; margin-bottom: 20px; }';
  $html .= 'table { width: 100%; border-collapse: collapse; margin-top: 20px; }';
  $html .= 'th { background-color: #007bff; color: white; padding: 12px; text-align: left; border: 1px solid #ddd; }';
  $html .= 'td { padding: 10px; border: 1px solid #ddd; }';
  $html .= 'tr:nth-child(even) { background-color: #f9f9f9; }';
  $html .= 'tr:hover { background-color: #f5f5f5; }';
  $html .= '.total { background-color: #e7f3ff; font-weight: bold; }';
  $html .= '</style>';
  $html .= '</head><body>';
  
  $html .= '<h1>Reporte de Ventas</h1>';
  $html .= '<p>Período: <strong>' . $fechaInicio . '</strong> a <strong>' . $fechaFin . '</strong></p>';
  
  $html .= '<table>';
  $html .= '<thead><tr>';
  foreach ($headers as $header) {
    $html .= '<th>' . htmlspecialchars($header) . '</th>';
  }
  $html .= '</tr></thead>';
  $html .= '<tbody>';
  
  $totalGeneral = 0;
  while ($row = $result->fetch_assoc()) {
    $valorTotal = floatval($row['valor_total'] ?? 0);
    $totalGeneral += $valorTotal;
    
    $html .= '<tr>';
    $html .= '<td>' . htmlspecialchars($row['id_bodega'] ?? '') . '</td>';
    $html .= '<td>' . htmlspecialchars($row['desc_bodega'] ?? '') . '</td>';
    $html .= '<td>' . htmlspecialchars($row['tipo'] ?? '') . '</td>';
    $html .= '<td>' . htmlspecialchars($row['numero'] ?? '') . '</td>';
    $html .= '<td>' . htmlspecialchars($row['usuario'] ?? '') . '</td>';
    $html .= '<td>' . htmlspecialchars($row['tratamiento'] ?? '') . '</td>';
    $html .= '<td>' . htmlspecialchars($row['razon_comercial'] ?? '') . '</td>';
    $html .= '<td>' . htmlspecialchars($row['fecha'] ?? '') . '</td>';
    $html .= '<td>' . htmlspecialchars($row['vencimiento'] ?? '') . '</td>';
    $html .= '<td style="text-align: right;">$ ' . number_format($valorTotal, 0, ',', '.') . '</td>';
    $html .= '</tr>';
  }
  
  $html .= '<tr class="total">';
  $html .= '<td colspan="9">TOTAL GENERAL:</td>';
  $html .= '<td style="text-align: right;">$ ' . number_format($totalGeneral, 0, ',', '.') . '</td>';
  $html .= '</tr>';
  $html .= '</tbody></table>';
  $html .= '</body></html>';
  
  // Headers PDF
  header('Content-Type: text/html; charset=utf-8');
  header('Content-Disposition: inline; filename="ventas_' . date('Y-m-d_His') . '.html"');
  
  echo $html;
  exit;
}

/**
 * ✅ Crear XLSX sin librerías externas
 * Genera estructura ZIP válida para Excel
 */
function crearExcelXLSX($datos, $nombreHoja = 'Hoja1') {
  $temp = sys_get_temp_dir() . '/xlsx_' . uniqid();
  @mkdir($temp, 0777, true);
  @mkdir($temp . '/_rels', 0777, true);
  @mkdir($temp . '/xl', 0777, true);
  @mkdir($temp . '/xl/_rels', 0777, true);
  @mkdir($temp . '/xl/worksheets', 0777, true);
  
  // Crear [Content_Types].xml
  $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
  $contentTypes .= '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' . "\n";
  $contentTypes .= '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' . "\n";
  $contentTypes .= '<Default Extension="xml" ContentType="application/xml"/>' . "\n";
  $contentTypes .= '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' . "\n";
  $contentTypes .= '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' . "\n";
  $contentTypes .= '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' . "\n";
  $contentTypes .= '</Types>';
  file_put_contents($temp . '/[Content_Types].xml', $contentTypes);
  
  // Crear _rels/.rels
  $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
  $rels .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . "\n";
  $rels .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' . "\n";
  $rels .= '</Relationships>';
  file_put_contents($temp . '/_rels/.rels', $rels);
  
  // Crear xl/workbook.xml
  $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
  $workbook .= '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' . "\n";
  $workbook .= '<sheets><sheet name="' . htmlspecialchars($nombreHoja) . '" sheetId="1" r:id="rId2"/></sheets>' . "\n";
  $workbook .= '</workbook>';
  file_put_contents($temp . '/xl/workbook.xml', $workbook);
  
  // Crear xl/_rels/workbook.xml.rels
  $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
  $workbookRels .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . "\n";
  $workbookRels .= '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' . "\n";
  $workbookRels .= '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' . "\n";
  $workbookRels .= '</Relationships>';
  file_put_contents($temp . '/xl/_rels/workbook.xml.rels', $workbookRels);
  
  // Crear xl/styles.xml
  $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
  $styles .= '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . "\n";
  $styles .= '<fonts><font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font></fonts>' . "\n";
  $styles .= '<fills><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>' . "\n";
  $styles .= '<borders><border><left/><right/><top/><bottom/><diagonal/></border></borders>' . "\n";
  $styles .= '<cellStyleXfs><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' . "\n";
  $styles .= '<cellXfs><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>' . "\n";
  $styles .= '</styleSheet>';
  file_put_contents($temp . '/xl/styles.xml', $styles);
  
  // Crear xl/worksheets/sheet1.xml
  $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
  $sheet .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . "\n";
  $sheet .= '<sheetData>' . "\n";
  
  foreach ($datos as $rowIdx => $row) {
    $sheet .= '<row r="' . ($rowIdx + 1) . '">' . "\n";
    foreach ($row as $colIdx => $value) {
      $col = chr(65 + ($colIdx % 26)) . ($colIdx >= 26 ? chr(64 + intval($colIdx / 26)) : '');
      if ($colIdx >= 26) $col = chr(64 + intval($colIdx / 26)) . chr(65 + ($colIdx % 26));
      
      $cellRef = $col . ($rowIdx + 1);
      $isNumeric = is_numeric($value) && $value !== '';
      
      if ($isNumeric) {
        $sheet .= '<c r="' . $cellRef . '" t="n"><v>' . floatval($value) . '</v></c>' . "\n";
      } else {
        $sheet .= '<c r="' . $cellRef . '" t="inlineStr"><is><t>' . htmlspecialchars($value) . '</t></is></c>' . "\n";
      }
    }
    $sheet .= '</row>' . "\n";
  }
  
  $sheet .= '</sheetData></worksheet>';
  file_put_contents($temp . '/xl/worksheets/sheet1.xml', $sheet);
  
  // Crear ZIP
  $zipFile = sys_get_temp_dir() . '/output_' . uniqid() . '.xlsx';
  $zip = new ZipArchive();
  $zip->open($zipFile, ZipArchive::CREATE);
  
  // Agregar archivos al ZIP
  $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temp), RecursiveIteratorIterator::LEAVES_ONLY);
  foreach ($files as $file) {
    if (!$file->isDir()) {
      $filePath = $file->getRealPath();
      $relativePath = substr($filePath, strlen($temp) + 1);
      $zip->addFile($filePath, str_replace('\\', '/', $relativePath));
    }
  }
  $zip->close();
  
  // Leer contenido
  $content = file_get_contents($zipFile);
  
  // Limpiar archivos temporales
  removerDirectorio($temp);
  @unlink($zipFile);
  
  return $content;
}

/**
 * ✅ Eliminar directorio recursivamente
 */
function removerDirectorio($dir) {
  if (is_dir($dir)) {
    $files = @scandir($dir);
    if ($files !== false) {
      foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
          $path = $dir . DIRECTORY_SEPARATOR . $file;
          if (is_dir($path)) {
            removerDirectorio($path);
          } else {
            @unlink($path);
          }
        }
      }
    }
    @rmdir($dir);
  }
}
