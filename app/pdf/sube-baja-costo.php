<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once '../../assets/tcpdf/tcpdf.php';
require_once('../models/bd.php');
require_once('../models/funciones.php');

class MYPDF extends TCPDF
{
  public function Header() {}

  public function Footer()
  {
    $this->SetY(-20);
    $this->SetFont('helvetica', 'I', 8);
    $this->Cell(0, 5, 'Desarrollado por CYC Technology - Todos los derechos reservados - Medellin, Antioquia', 0, 1, 'C', 0, '', 0, false, 'T', 'M');
    $this->Cell(0, 5, 'Pagina ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
  }
}


$idDoc = intval($_GET['id'] ?? 0);
$modo = $_GET['modo'] ?? 'display'; // Valor predeterminado 'I' si no se proporciona

$empresa = GenerarArray("SELECT
    t.razon_comercial,
    t.nit,
    t.nit_digito,
    t.correo,
    t.direccion,
    t.telefono,
    t.logo,
    d.departamento AS nombre_dpto,
    m.municipio AS nombre_mpio
  FROM t_empresa t
  INNER JOIN t_departamentos d ON d.codigo_dpto = t.codigo_dpto AND d.codigo_pais = t.codigo_pais
  INNER JOIN t_municipios m ON m.codigo_pais = t.codigo_pais AND m.codigo_dpto = t.codigo_dpto AND m.codigo = t.codigo_mpio
  LIMIT 1");

$enc = GenerarArray("SELECT
    t.id,
    t.numero,
    t.fecha_hora,
    t.sw,
    t.notas,
    t.valor_total,
    td.tipo,
    td.descripcion AS tipo_documento,
    ter.nit,
    ter.nit_digito,
    ter.razon_comercial,
    dir.direccion,
    dir.telefono,
    b.id AS id_bodega,
    b.descripcion AS bodega,
    UPPER(u.usuario) AS usuario,
    UPPER(CONCAT(u.nombres, ' ', u.apellidos)) AS nombres_usuario
  FROM t_transacciones t
  INNER JOIN t_tipos_documentos td ON td.id = t.id_tipo_documento
  INNER JOIN t_terceros ter ON ter.id = t.id_tercero
  LEFT JOIN t_terceros_direcciones dir ON dir.id = t.id_tercero_dir
  INNER JOIN t_almacenes b ON b.id = t.id_bodega
  INNER JOIN t_usuarios u ON u.id = t.id_usuario
  WHERE t.id = $idDoc
    AND t.sw IN (17, 18)
    AND t.anulado = 0
  LIMIT 1");

if (!is_array($empresa) || count($empresa) === 0) {
  die('No hay configuracion de empresa para imprimir.');
}

if (!is_array($enc) || count($enc) === 0) {
  die('No se encontro el documento o no tiene permisos para visualizarlo.');
}

$e = $empresa[0];
$h = $enc[0];

$det = GenerarArray("SELECT
    r.codigo,
    r.descripcion,
    IFNULL(d.cantidad, 0) AS cantidad,
    IFNULL(d.valor_unitario, 0) AS valor_unitario,
    (CASE WHEN t.sw = 17 THEN ABS(IFNULL(d.valor_unitario, 0)) ELSE -ABS(IFNULL(d.valor_unitario, 0)) END) AS delta_unitario,
    ((CASE WHEN t.sw = 17 THEN ABS(IFNULL(d.valor_unitario, 0)) ELSE -ABS(IFNULL(d.valor_unitario, 0)) END) * IFNULL(d.cantidad, 0)) AS ajuste_total
  FROM t_transacciones_detalle d
  INNER JOIN t_transacciones t ON t.id = d.id_transaccion
  INNER JOIN t_referencias r ON r.id = d.id_referencia
  WHERE d.id_transaccion = $idDoc
  ORDER BY r.codigo");

$pdf = new MYPDF('P', 'mm', 'LETTER');
$pdf->Open();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

$logo = !empty($e['logo']) ? $e['logo'] : 'default_logo.png';
$logoPath = '../../assets/img/logo/' . $logo;
if (file_exists($logoPath)) {
  $pdf->Image(
    $logoPath,
    18,
    10,
    25,
    0,
    '',
    '',
    '',
    false,
    300,
    '',
    false,
    false,
    0,
    false,
    false,
    false,
    false
  );
}

$fechaImpresion = date('Y-m-d H:i:s');
$empRazon = strtoupper($e['razon_comercial']);
$empNit = number_format($e['nit'], 0, '', '.');
$empDigito = $e['nit_digito'];
$empDireccion = $e['direccion'];
$empCiudad = $e['nombre_mpio'];
$empDpto = $e['nombre_dpto'];
$empCorreo = strtoupper($e['correo']);
$empTelefono = $e['telefono'];

$terRazon = strtoupper($h['razon_comercial']);
$terNit = number_format($h['nit'], 0, '', '.');
$terDigito = $h['nit_digito'];
$terDireccion = strtoupper($h['direccion'] ?? '');
$terTelefono = $h['telefono'] ?? '';
$tipoDoc = $h['tipo'];
$descDoc = strtoupper($h['tipo_documento']);
$numeroDoc = $h['numero'];
$fechaHora = $h['fecha_hora'];
$usuario = $h['usuario'];
$usuarioNombre = $h['nombres_usuario'];
$bodegaId = $h['id_bodega'];
$bodegaDesc = strtoupper($h['bodega']);

$tbl = '<style>
  table.first {
    color: #003300;
    font-family: helvetica;
    font-size: 10pt;
    border-collapse: collapse;
    border: 1px solid #DADADA;
  }
  table.first td, table.first th {
    border: 1px solid #DADADA;
    padding: 5px;
  }
</style>
<table style="width: 100%;" class="first">
  <tr>
    <td style="width: 20%; text-align: center;"></td>
    <td style="width: 50%; text-align: center;">
      <strong>' . $empRazon . '</strong><br>
      Nit: ' . $empNit . ' - ' . $empDigito . '<br>
      Direccion: ' . $empDireccion . '<br>
      Ciudad: ' . $empDpto . ' - ' . $empCiudad . '<br>
      Email: ' . $empCorreo . '<br>
      Telefono: ' . $empTelefono . '
    </td>
    <td style="width: 30%; text-align: center;">
      <strong>AJUSTE COSTO PROMEDIO SW ' . $h['sw'] . '</strong><br>
      <strong>' . $descDoc . '</strong><br>
      <strong>' . $tipoDoc . '</strong> - ' . $numeroDoc . '<br>
      <strong>ALMACEN</strong><br>
      ' . $bodegaId . ' - ' . $bodegaDesc . '
    </td>
  </tr>
</table>';
$pdf->writeHTML($tbl, true, false, false, false, '');

$tblTer = '<style>
  table.first {
    color: #003300;
    font-family: helvetica;
    font-size: 8pt;
    border-collapse: collapse;
    border: 1px solid #DADADA;
  }
  table.first td, table.first th {
    border: 1px solid #DADADA;
    padding: 5px;
  }
</style>
<table width="100%" class="first">
  <tr>
    <td style="background-color: #ECECEC;"><b>TERCERO</b></td>
    <td colspan="3">' . $terRazon . '</td>
  </tr>
  <tr>
    <td style="background-color: #ECECEC;"><b>NIT</b></td>
    <td>' . $terNit . ' - ' . $terDigito . '</td>
    <td style="background-color: #ECECEC;"><b>DIRECCION</b></td>
    <td>' . $terDireccion . '</td>
  </tr>
  <tr>
    <td style="background-color: #ECECEC;"><b>TELEFONO</b></td>
    <td>' . $terTelefono . '</td>
    <td style="background-color: #ECECEC;"><b>FECHA CREACION</b></td>
    <td>' . $fechaHora . '</td>
  </tr>
  <tr>
    <td style="background-color: #ECECEC;"><b>USUARIO</b></td>
    <td>' . $usuario . ' - ' . $usuarioNombre . '</td>
    <td style="background-color: #ECECEC;"><b>FECHA IMPRESION</b></td>
    <td>' . $fechaImpresion . '</td>
  </tr>
</table>';
$pdf->writeHTML($tblTer, true, false, false, false, '');

$detHtml = '<style>
  table.first {
    color: #003300;
    font-family: helvetica;
    font-size: 8pt;
    border-collapse: collapse;
    border: 1px solid #DADADA;
  }
  table.first td, table.first th {
    border: 1px solid #DADADA;
    padding: 5px;
  }
</style>
<table width="100%" class="first">
  <thead>
    <tr>
      <th style="width: 12%; background-color: #ECECEC;"><b>CODIGO</b></th>
      <th style="width: 34%; background-color: #ECECEC;"><b>DESCRIPCION</b></th>
      <th style="width: 12%; background-color: #ECECEC; text-align: right;"><b>STOCK</b></th>
      <th style="width: 12%; background-color: #ECECEC; text-align: right;"><b>DELTA</b></th>
      <th style="width: 15%; background-color: #ECECEC; text-align: right;"><b>AJUSTE TOTAL</b></th>
      <th style="width: 15%; background-color: #ECECEC;"><b>NATURALEZA</b></th>
    </tr>
  </thead>
  <tbody>';

foreach ($det as $d) {
  $naturaleza = ($h['sw'] == 17) ? 'SUBE' : 'BAJA';
  $detHtml .= '<tr>
    <td style="width: 12%;">' . $d['codigo'] . '</td>
    <td style="width: 34%;">' . strtoupper($d['descripcion']) . '</td>
    <td style="width: 12%; text-align: right;">' . number_format(floatval($d['cantidad']), 2, '.', ',') . '</td>
    <td style="width: 12%; text-align: right;">' . number_format(floatval($d['delta_unitario']), 2, '.', ',') . '</td>
    <td style="width: 15%; text-align: right;">' . number_format(floatval($d['ajuste_total']), 2, '.', ',') . '</td>
    <td style="width: 15%;">' . $naturaleza . '</td>
  </tr>';
}

if (!is_array($det) || count($det) === 0) {
  $detHtml .= '<tr><td colspan="6" style="text-align:center;">Sin detalle para este documento.</td></tr>';
}

$detHtml .= '</tbody></table>';
$pdf->writeHTML($detHtml, true, false, false, false, '');

$tblTot = '<style>
  table.first {
    width: 100%;
    color: #003300;
    font-family: helvetica;
    font-size: 8pt;
    border-collapse: collapse;
    border: 1px solid #DADADA;
  }
  table.first td, table.first th {
    border: 1px solid #DADADA;
    padding: 6px 8px;
    vertical-align: middle;
  }
  table.first th.label {
    background-color: #ECECEC;
    font-weight: bold;
    text-align: right;
    width: 20%;
  }
  table.first td.value {
    text-align: left;
    width: 80%;
  }
</style>
<table class="first">
  <tbody>
    <tr>
      <th class="label">Notas:</th>
      <td class="value">' . nl2br(htmlspecialchars($h['notas'] ?? '')) . '</td>
    </tr>
    <tr>
      <th class="label">Total Documento:</th>
      <td class="value"><b>$ ' . number_format(floatval($h['valor_total']), 2, '.', ',') . '</b></td>
    </tr>
  </tbody>
</table>';

$pdf->writeHTML($tblTot, true, false, false, false, '');


if ($modo === 'guardar') {
  $pdf->Output($_GET['archivo'], 'F'); // For saving to a local file
} else {
  $pdf->Output('sube_baja_costo.pdf', 'I'); // For inline display
}
