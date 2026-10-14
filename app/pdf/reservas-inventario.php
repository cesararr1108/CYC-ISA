<?php

session_start();

require_once '../../assets/tcpdf/tcpdf.php';
require_once('../models/bd.php');
require_once('../models/funciones.php');

class MYPDF extends TCPDF
{
    public function Header() {}

    public function Footer()
    {
        $this->SetY(-18);
        $this->SetFont('helvetica', 'I', 8);

        $this->Cell(0, 5,
            'Desarrollado por CYC Technology - Medellín, Antioquia',
            0, 1, 'C'
        );

        $this->Cell(0, 5,
            'Página ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(),
            0, 0, 'C'
        );
    }
}

$id_doc = intval($_GET['id']);

$pdf = new MYPDF('P', 'mm', 'LETTER');
$pdf->SetAutoPageBreak(TRUE, 15);
$pdf->AddPage();

/* =========================
   EMPRESA
========================= */

$e = GenerarArray("SELECT 
    UPPER(razon_comercial) razon_comercial,
    nit,
    nit_digito,
    direccion,
    correo,
    telefono,
    logo
FROM t_empresa
LIMIT 1");

foreach ($e as $d) {
    $emp_razon = $d['razon_comercial'];
    $emp_nit = number_format($d['nit'], 0, '', '.');
    $emp_dig = $d['nit_digito'];
    $emp_dir = $d['direccion'];
    $emp_mail = $d['correo'];
    $emp_tel = $d['telefono'];
    $emp_logo = $d['logo'] ?: 'default_logo.png';
}

/* =========================
   ENCABEZADO RESERVA
========================= */

$t = GenerarArray("SELECT
    r.id,
    r.fecha_hora_crea,
    r.notas,
    r.estado,

    a.descripcion AS bodega,

    CONCAT(
        te.primer_nombre,' ',
        te.segundo_nombre,' ',
        te.primer_apellido,' ',
        te.segundo_apellido
    ) AS tercero,

    CONCAT(u.nombres,' ',u.apellidos) AS usuario_nombre

FROM t_reservas r

INNER JOIN t_almacenes a ON a.id = r.id_bodega
INNER JOIN t_terceros te ON te.id = r.id_tercero
INNER JOIN t_usuarios u ON u.id = r.id_usuario_crea

WHERE r.id = $id_doc");

foreach ($t as $d) {

    $fecha = $d['fecha_hora_crea'];
    $notas = $d['notas'];
    $estado = $d['estado'] == 1 ? 'ACTIVA' : 'INACTIVA';
    $bodega = $d['bodega'];
    $tercero = $d['tercero'];
    $usuario = $d['usuario_nombre'];
}

/* =========================
   LOGO
========================= */

$logoPath = '../../assets/img/logo/' . $emp_logo;

$pdf->Image($logoPath, 15, 12, 22);

/* =========================
   HEADER EMPRESA (ESTILO ERP)
========================= */

$header = <<<HTML
<style>
table.first {
    font-family: helvetica;
    font-size: 10pt;
    border-collapse: collapse;
    border: 1px solid #DADADA;
}

table.first td {
    border: 1px solid #DADADA;
    padding: 5px;
}
</style>

<table class="first" width="100%">

<tr>

<td width="20%"></td>

<td width="50%" align="center">
<strong>$emp_razon</strong><br>
Nit: $emp_nit - $emp_dig<br>
$emp_dir<br>
$emp_mail<br>
Tel: $emp_tel
</td>

<td width="30%" align="center">
<strong style="color:#d00;">RESERVA INVENTARIO</strong><br>
ID: $id_doc<br>
Estado: $estado<br>
Bodega: $bodega
</td>

</tr>

</table>
HTML;

$pdf->writeHTML($header, true, false, false, false, '');

/* =========================
   TERCERO
========================= */

$fecha_impresion = date('Y-m-d H:i:s');
$ter = <<<HTML
<style>
table.first {
    font-family: helvetica;
    font-size: 8pt;
    border-collapse: collapse;
    border: 1px solid #DADADA;
}

table.first td {
    border: 1px solid #DADADA;
    padding: 5px;
}
</style>

<table class="first" width="100%">

<tr>
<td style="background:#ECECEC;"><b>TERCERO</b></td>
<td colspan="3">$tercero</td>
</tr>

<tr>
<td style="background:#ECECEC;"><b>USUARIO</b></td>
<td colspan="3">$usuario</td>
</tr>

<tr>
<td style="background:#ECECEC;"><b>FECHA CREACIÓN</b></td>
<td>$fecha</td>

<td style="background:#ECECEC;"><b>IMPRESIÓN</b></td>
<td>$fecha_impresion</td>
</tr>

</table>
HTML;

$pdf->writeHTML($ter, true, false, false, false, '');

/* =========================
   DETALLE
========================= */

$q = GenerarArray("SELECT
                        d.id,
                        d.cantidad,
                        d.estado,
                        r.codigo,
                        r.descripcion,
                        l.lote,
                        l.vencimiento
                    FROM t_reservas_inventario d
                    INNER JOIN t_referencias r ON r.id = d.id_referencia
                    LEFT JOIN t_referencias_lotes l ON l.id = d.id_lote
                    WHERE 
                        d.id_reserva = $id_doc
                    ORDER BY 
                        r.descripcion");

$rows = '';
$total = 0;
$items = 0;

foreach ($q as $d) {

    $items++;
    $total += $d['cantidad'];

    $estado = ((int)$d['estado'] === 1)
        ? '<span style="color:#0a0;font-weight:bold;">ACTIVO</span>'
        : '<span style="color:#c00;font-weight:bold;">INACTIVO</span>';

    $rows .= '
    <tr>
        <td width="12%" align="center">'.$d['codigo'].'</td>
        <td width="43%">'.$d['descripcion'].'</td>
        <td width="10%" align="center">'.($d['lote'] ?: 'N/A').'</td>
        <td width="10%" align="center">'.($d['vencimiento'] ?: 'N/A').'</td>
        <td width="10%" align="right">'.number_format($d['cantidad'], 0).'</td>
        <td width="15%" align="center">'.$estado.'</td>
    </tr>';
}

/* =========================
   TABLA DETALLE (ESTILO ERP)
========================= */

$detalle = <<<HTML
<style>
table.first {
    font-family: helvetica;
    font-size: 8pt;
    border-collapse: collapse;
    border: 1px solid #DADADA;
}

table.first td, table.first th {
    border: 1px solid #DADADA;
    padding: 4px;
}
</style>

<table class="first" width="100%">

<thead>
<tr>
<th style="background:#ECECEC;" width="12%" align="center"><b>CODIGO</b></th>
<th style="background:#ECECEC;" width="43%"><b>DESCRIPCION</b></th>
<th style="background:#ECECEC;" width="10%" align="center"><b>LOTE</b></th>
<th style="background:#ECECEC;" width="10%" align="center"><b>VMCTO</b></th>
<th style="background:#ECECEC;" width="10%" align="right"><b>CANT</b></th>
<th style="background:#ECECEC;" width="15%" align="center"><b>ESTADO</b></th>
</tr>
</thead>

<tbody>
$rows
</tbody>

</table>
HTML;

$pdf->writeHTML($detalle, true, false, false, false, '');

/* =========================
   TOTALES
========================= */

$totales = <<<HTML
<style>
table.first {
    font-family: helvetica;
    font-size: 8pt;
    border-collapse: collapse;
    border: 1px solid #DADADA;
}

table.first td {
    border: 1px solid #DADADA;
    padding: 5px;
}
</style>

<table class="first" width="100%">

<tr>
<td style="background:#ECECEC;"><b>ITEMS</b></td>
<td>$items</td>

<td style="background:#ECECEC;"><b>TOTAL CANTIDAD</b></td>
<td>$total</td>
</tr>

<tr>
<td style="background:#ECECEC;"><b>NOTAS</b></td>
<td colspan="3">$notas</td>
</tr>

</table>
HTML;

$pdf->writeHTML($totales, true, false, false, false, '');

/* =========================
   OUTPUT
========================= */

$pdf->Output('reserva_'.$id_doc.'.pdf', 'I');