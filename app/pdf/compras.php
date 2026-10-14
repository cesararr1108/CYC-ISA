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
    // Posiciona a 15 mm desde el final
    $this->SetY(-20);
    // Fuente pequeña
    $this->SetFont('helvetica', 'I', 8);
    // Información de la empresa
    $desarrolladorInfo = "Desarrollado por CYC Technology - Todos los derechos reservados - Medellín, Antioquia";
    // Línea 1: desarrollado por...
    $this->Cell(0, 5, $desarrolladorInfo, 0, 1, 'C', 0, '', 0, false, 'T', 'M');
    // Línea 2: número de página
    $this->Cell(0, 5, 'Página ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
  }
}

$id_doc = $_GET['id'];
$modo = $_GET['modo'] ?? 'display'; // Valor predeterminado 'I' si no se proporciona

$pdf = new MYPDF('P', 'mm', 'LETTER');
$pdf->Open();
$pdf->AddPage();
// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

$e = GenerarArray("SELECT 
                        upper(t.razon_comercial) as razon_comercial,
                        upper(t.representante_legal) as representante_legal,
                        t.nit,
                        t.nit_digito,
                        upper(t.correo) as correo,
                        t.direccion ,
                        p.descripcion as nombre_pais,
                        d.departamento  as nombre_dpto,
                        m.municipio  as nombre_mpio,
	                      t.telefono,
                        t.logo
                    from t_empresa t
                    inner join t_paises p on p.pais = t.codigo_pais 
                    inner join t_departamentos d on d.codigo_dpto = t.codigo_dpto  and d.codigo_pais  = t.codigo_pais 
                    inner join t_municipios m on m.codigo_pais = t.codigo_pais and m.codigo_dpto  = t.codigo_dpto and m.codigo =t.codigo_mpio");
$logo = '';
$emp_razon = '';
$emp_nit = '';
$emp_nit_digito = '';
$emp_direccion = '';
$emp_ciudad = '';
$emp_dpto = '';
$emp_mail = '';
$emp_telefono = '';
$emp_logo = '';
foreach ($e as $d) {
  $logo = !empty($d['logo']) ? $d['logo'] : 'default_logo.png';
  $emp_razon = $d['razon_comercial'];
  $emp_nit = number_format($d['nit'], 0, '', '.');
  $emp_nit_digito = $d['nit_digito'];
  $emp_direccion = $d['direccion'];
  $emp_ciudad = $d['nombre_mpio'];
  $emp_dpto = $d['nombre_dpto'];
  $emp_mail = $d['correo'];
  $emp_telefono = $d['telefono'];
  $emp_logo = $logo;
}

$t = GenerarArray(" SELECT
                        t.nit,
                        t.nit_digito,
                        CONCAT(t.primer_nombre,' ',t.segundo_nombre,' ',t.primer_apellido,' ',t.segundo_apellido) as nombres,
                        t.razon_comercial,
                        d.id_bodega,
                        d.id_tipo_documento,
                        d.notas,
                        d.id as id_transaccion,
                        d.numero,
                        UPPER(u.usuario) as usuario,
                        UPPER(CONCAT(u.nombres,' ',u.apellidos)) as nombres_usuario, 
                        d.fecha_hora,
                        z.tipo,
                        z.descripcion,
                        p.descripcion as nombre_pais,
                        s.departamento  as nombre_dpto,
                        m.municipio  as nombre_mpio,
                        upper(r.direccion) as direccion,
                        r.telefono,
                        a.descripcion as desc_bodega,
                        d.retencion_fuente ,
                        d.retencion_cree ,
                        d.retencion_ica ,
                        d.retencion_iva ,
                        d.descuento ,
                        d.valor_total,
                        d.iva ,
                        d.ibua ,
                        d.icui ,
                        ifnull(d.impoconsumo, 0) as impoconsumo,
                        d.documento as doc_referencia
                    from t_transacciones d
                    inner join t_terceros           t on t.id = d.id_tercero 
                    inner join t_terceros_direcciones r on r.id = d.id_tercero_dir
                    inner join t_usuarios           u on u.id=d.id_usuario
                    inner join t_tipos_documentos   z on z.id=d.id_tipo_documento 
                    inner join t_paises             p on p.pais = r.codigo_pais 
                    inner join t_departamentos s on s.codigo_dpto = r.codigo_dpto  and s.codigo_pais  = r.codigo_pais 
                    inner join t_municipios m on m.codigo_pais = r.codigo_pais and m.codigo_dpto  = r.codigo_dpto and m.codigo =r.codigo_mpio
                    inner join t_almacenes a on a.id  = d.id_bodega 
                    where 
                        d.id = $id_doc");
$hdr_valor_total   = 0;
$hdr_iva           = 0;
$hdr_ibua          = 0;
$hdr_icui          = 0;
$hdr_impoconsumo   = 0;
$hdr_descuento     = 0;
$hdr_ret_fuente    = 0;
$hdr_ret_cree      = 0;
$hdr_ret_ica       = 0;
$hdr_ret_iva       = 0;
$ter_nit  = 0;
$notas = '';
foreach ($t as $d) {
  $ter_nombres = $d['nombres'];
  $ter_razon = $d['razon_comercial'];
  $ter_nit = number_format($d['nit'], 0, '', '.');
  $ter_nit_digito = $d['nit_digito'];
  $ter_direccion = $d['direccion'];
  $ter_ciudad = $d['nombre_mpio'];
  $ter_dpto = $d['nombre_dpto'];
  $ter_telefono = $d['telefono'];
  $tipo_doc = $d['tipo'];
  $tipo_desc_doc = $d['descripcion'];
  $numero_doc = $d['numero'];
  $fecha_hora = $d['fecha_hora'];
  $notas = trim((string)$d['notas']);
  $usuario_crea = $d['usuario'];
  $nombres_usuario = $d['nombres_usuario'];
  $id_bodega        = $d['id_bodega'];
  $desc_bodega       = $d['desc_bodega'];
  $hdr_valor_total   = floatval($d['valor_total']);
  $hdr_iva           = floatval($d['iva']);
  $hdr_ibua          = floatval($d['ibua']);
  $hdr_icui          = floatval($d['icui']);
  $hdr_impoconsumo   = floatval($d['impoconsumo']);
  $hdr_descuento     = floatval($d['descuento']);
  $hdr_ret_fuente    = floatval($d['retencion_fuente']);
  $hdr_ret_cree      = floatval($d['retencion_cree']);
  $hdr_ret_ica       = floatval($d['retencion_ica']);
  $hdr_ret_iva       = floatval($d['retencion_iva']);
  $doc_referencia    = $d['doc_referencia'];
}
// Ruta del logo
$logoPath = '../../assets/img/logo/' . $emp_logo;
$pdf->Image(
  $logoPath, // ruta
  20, // X en mm desde el borde izquierdo
  12, // Y en mm desde el borde superior
  20, // ancho en mm
  0, // alto (0 = proporcional al ancho)
  '', // tipo de imagen (TCPDF lo detecta solo)
  '', // link (vacío si no hay enlace)
  '', // align
  false, // resize
  400, // dpi
  '', // palign
  false, // ismask
  false, // imgmask
  0, // border
  false, // fitbox
  false, // hidden
  false, // fitonpage
  false // alt
);
$fechaGeneracion = date('Y-m-d H:i:s');
$tbl = <<<EOD
            <style>
               table.first {
                    color: #003300;
                    font-family: helvetica;
                    font-size: 10pt;
                    border-collapse: collapse; /* Une los bordes */
                    border: 1px solid #DADADA; /* Borde principal */
                }

                table.first td, table.first th {
                    border: 1px solid #DADADA; /* Bordes para celdas */
                    padding: 5px; /* Espaciado opcional */
                }
            </style>
            <table style="width: 100%;" class="first">
            <tr>
                <td style="width: 20%; text-align: center; ">
                </td>
                <td style="width: 50%; text-align: center;">
                    <strong>$emp_razon</strong><br>
                    Nit: $emp_nit - $emp_nit_digito<br>
                    Dirección: $emp_direccion<br>
                    Ciudad: $emp_ciudad - $emp_ciudad <br>
                    Email: $emp_mail <br>
                    Teléfono: $emp_telefono
                    
                </td>
                <td style="width: 30%; text-align: center;">
                &nbsp;<br>
                    <strong>$tipo_desc_doc</strong><br>
                    <strong>$tipo_doc</strong> - $numero_doc<br>
                    <strong>ALMACEN</strong><br>
                    $id_bodega - $desc_bodega
                    <strong>DOC. REFERENCIA</strong><br>
                    $doc_referencia
                </td>
            </tr>
        </table>
EOD;
$pdf->writeHTML($tbl, true, false, false, false, '');

$tblTer = <<<EOD
            <style>
               table.first {
                    color: #003300;
                    font-family: helvetica;
                    font-size: 8pt;
                    border-collapse: collapse; /* Une los bordes */
                    border: 1px solid #DADADA; /* Borde principal */
                }

                table.first td, table.first th {
                    border: 1px solid #DADADA; /* Bordes para celdas */
                    padding: 5px; /* Espaciado opcional */
                }
            </style>
            <table width="100%" class="first">
              <tr>
                <td style="background-color: #ECECEC;"><b>TERCERO</b></td>
                <td colspan="3" >$ter_nombres</td>
              </tr>
              <tr>
                <td style="background-color: #ECECEC;"><b>RAZÓN COMERCIAL</b></td>
                <td colspan="3" >$ter_razon</td>
              </tr>
              <tr>
                <td style="background-color: #ECECEC;"><b>NIT</b></td>
                <td>$ter_nit - $ter_nit_digito</td>
                <td style="background-color: #ECECEC;"><b>DIRECCIÓN</b></td>
                <td>$ter_direccion</td>
              </tr>
              <tr>
                <td style="background-color: #ECECEC;"><b>CIUDAD</b></td>
                <td>$ter_ciudad - $ter_dpto</td>
                <td style="background-color: #ECECEC;"><b>TELÉFONO</b></td>
                <td>$ter_telefono</td>
              </tr>
              <tr>
                <td style="background-color: #ECECEC;"><b>FECHA CREACIÓN</b></td>
                <td>$fecha_hora</td>
                <td style="background-color: #ECECEC;"><b>FECHA IMPRESIÓN</b></td>
                <td>$fechaGeneracion</td>
              </tr>
            </table>
EOD;
$pdf->writeHTML($tblTer, true, false, false, false, '');
$det = '';
$q = GenerarArray("select 
                         r.id,
                         r.codigo,
                         r.descripcion,
                         r.descripcion_larga,
                         r.und_1,
                         r.und_2,
                         r.und_3,
                         r.und_4,
                         r.cant_1,
                         r.cant_2,
                         r.cant_3,
                         r.cant_4,
                         t.id as id_detalle,
                         t.id_transaccion,
                         t.cantidad,
                         t.valor_unitario,
                         t.costo_unitario,
                         t.id_lote,
                         t.pcj_iva as iva,
                         ifnull(t.pcj_descuento,0) as dto,
                         t.und,
                         case when t.can_und=r.cant_1 then 1 
                              when t.can_und=r.cant_2 then 2
                              when t.can_und=r.cant_3 then 3
                              when t.can_und=r.cant_4 then 4
                         end  as und_compra,
                         t.can_und,
                         t.lote,
                         t.vmcto as vencimiento,
                         ifnull(t.impoconsumo,0) as impoconsumo,
                         ifnull(t.ibua,0) as ibua,
                         ifnull(t.icui,0) as icui
                        from t_transacciones_detalle t
                        inner join t_referencias r on r.id=t.id_referencia 
                        where 
                         t.id_transaccion = $id_doc
                        order by 
                         r.descripcion");
$unds = 0;
$items = 0;

foreach ($q as $d) {

  $cantidad         = $d['cantidad'];
  $costo            = $d['costo_unitario'];
  $vlr_dto          = $costo * ($d['dto'] / 100);
  $vlr_impoconsumo  = $d['impoconsumo'];
  $vlr_ibua         = $d['ibua'];
  $vlr_icui         = ($costo - $vlr_dto) * $d['icui'] / 100;
  $vlr_iva          = ($costo - $vlr_dto) * ($d['iva'] / 100);
  $costo_neto       = $costo - $vlr_dto + $vlr_impoconsumo + $vlr_icui + $vlr_ibua + $vlr_iva;
  $costo_total      = $costo_neto * $cantidad;

  $items++;
  $unds += $cantidad;

  $descripcion = $d['descripcion'];


  if ($_SESSION["Ses_confSys"]['confGeneral'][0]['ManejaFechaLote'] == 'S') {
    $descripcion = $d['descripcion'] . '<br><b>Lote:</b>' . $d['lote'] . '<br><b>Vmcto:</b>' . $d['vencimiento'];
  }


  $det .= '<tr>
            <td width="8%" align="center">' . $d['codigo'] . '</td>
            <td width="35%">' . $descripcion . '</td>
            <td width="6%" align="center">' . $d['und'] . '</td>
            <td width="6%" align="center">' . number_format($cantidad) . '</td>
            <td width="11%" align="right">' . number_format($d["costo_unitario"], 2) . '</td>
            <td width="6%" align="center">' . $d['iva'] . '</td>
            <td width="6%" align="center">' . $d['dto'] . '</td>
            <td width="11%" align="right">' . number_format($costo_neto, 2) . '</td>                 
            <td width="11%" align="right">' . number_format($costo_total, 2) . '</td>
          </tr>';
}

$tblDet = <<<EOD
            <style>
               table.first {
                    color: #003300;
                    font-family: helvetica;
                    font-size: 8pt;
                    border-collapse: collapse; /* Une los bordes */
                    border: 1px solid #DADADA; /* Borde principal */
                }
                table.first td, table.first th {
                    border: 1px solid #DADADA; /* Bordes para celdas */

                }
            </style>
            <table width="100%" class="first">
                <thead>
                 <tr>
                   <th style="background-color: #ECECEC;font-weight: bold;" width="8%" align="center">CÓDIGO</th>
                   <th style="background-color: #ECECEC;font-weight: bold;" width="35%">DESCRIPCION</th>
                   <th style="background-color: #ECECEC;font-weight: bold;" width="6%" align="center">UND</th>
                   <th style="background-color: #ECECEC;font-weight: bold;" width="6%" align="center">CANT</th>
                   <th style="background-color: #ECECEC;font-weight: bold;" width="11%">COSTO</th>
                   <th style="background-color: #ECECEC;font-weight: bold;" width="6%" align="center">IVA</th>
                   <th style="background-color: #ECECEC;font-weight: bold;" width="6%" align="center">DTO</th>
                   <th style="background-color: #ECECEC;font-weight: bold;" width="11%">NETO</th>                       
                   <th style="background-color: #ECECEC;font-weight: bold;" width="11%">TOTAL</th>
                 </tr>
                </thead>
                <tbody>$det</tbody>
            </table>
EOD;
$pdf->writeHTML($tblDet, true, false, false, false, '');

// Totales desde encabezado
$vlr_subtotal          = $hdr_valor_total + $hdr_descuento - $hdr_iva - $hdr_impoconsumo - $hdr_ibua - $hdr_icui;
$vlr_total_retenciones = $hdr_ret_fuente + $hdr_ret_ica + $hdr_ret_iva;
$vlr_neto_pagar        = $hdr_valor_total - $vlr_total_retenciones;
$vlr_saludables        = $hdr_ibua + $hdr_icui;

$fmt_subtotal          = number_format($vlr_subtotal, 2);
$fmt_descuento         = number_format($hdr_descuento, 2);
$fmt_iva               = number_format($hdr_iva, 2);
$fmt_impoconsumo       = number_format($hdr_impoconsumo, 2);
$fmt_saludables        = number_format($vlr_saludables, 2);
$fmt_total             = number_format($hdr_valor_total, 2);
$fmt_ret_fuente        = number_format($hdr_ret_fuente, 2);
$fmt_ret_ica           = number_format($hdr_ret_ica, 2);
$fmt_ret_iva           = number_format($hdr_ret_iva, 2);
$fmt_ret_cree          = number_format($hdr_ret_cree, 2);
$fmt_total_retenciones = number_format($vlr_total_retenciones, 2);
$fmt_neto_pagar        = number_format($vlr_neto_pagar, 2);

// Build totals rows — only render concepts with value > 0
$rightRows = array();
if ($hdr_descuento > 0) {
  $rightRows[] = '<th valign="middle" align="right" style="background-color: #ECECEC;font-weight: bold;">DESCUENTO</th><th valign="middle" align="right">' . $fmt_descuento . '</th>';
}
if ($hdr_iva > 0) {
  $rightRows[] = '<th valign="middle" align="right" style="background-color: #ECECEC;font-weight: bold;">IVA</th><th valign="middle" align="right">' . $fmt_iva . '</th>';
}
if ($hdr_impoconsumo > 0) {
  $rightRows[] = '<th valign="middle" align="right" style="background-color: #ECECEC;font-weight: bold;">IMPOCONSUMO</th><th valign="middle" align="right">' . $fmt_impoconsumo . '</th>';
}
if ($vlr_saludables > 0) {
  $rightRows[] = '<th valign="middle" align="right" style="background-color: #ECECEC;font-weight: bold;">SALUDABLE</th><th valign="middle" align="right">' . $fmt_saludables . '</th>';
}
$rightRows[] = '<th valign="middle" align="right" style="background-color: #ECECEC;font-weight: bold;">TOTAL BRUTO</th><th valign="middle" align="right"><b>' . $fmt_total . '</b></th>';
if ($hdr_ret_fuente > 0) {
  $rightRows[] = '<th valign="middle" align="right" style="background-color: #FFEDED;font-weight: bold;">RTE FUENTE</th><th valign="middle" align="right" style="color:#CC0000;">' . $fmt_ret_fuente . '</th>';
}
if ($hdr_ret_ica > 0) {
  $rightRows[] = '<th valign="middle" align="right" style="background-color: #FFEDED;font-weight: bold;">RTE ICA</th><th valign="middle" align="right" style="color:#CC0000;">' . $fmt_ret_ica . '</th>';
}
if ($hdr_ret_iva > 0) {
  $rightRows[] = '<th valign="middle" align="right" style="background-color: #FFEDED;font-weight: bold;">RTE IVA</th><th valign="middle" align="right" style="color:#CC0000;">' . $fmt_ret_iva . '</th>';
}
if ($vlr_total_retenciones > 0) {
  $rightRows[] = '<th valign="middle" align="right" style="background-color: #FFEDED;font-weight: bold;">TOTAL RET.</th><th valign="middle" align="right" style="color:#CC0000;"><b>' . $fmt_total_retenciones . '</b></th>';
}
$rightRows[] = '<th valign="middle" align="right" style="background-color: #EAFFEA;font-weight: bold;">NETO A PAGAR</th><th valign="middle" align="right" style="color:#006600;"><b>' . $fmt_neto_pagar . '</b></th>';

$notasSanitizadas = nl2br(htmlspecialchars($notas, ENT_QUOTES, 'UTF-8'));
if (trim($notasSanitizadas) === '') {
  $notasSanitizadas = 'SIN NOTAS';
}

$midRows = '';
$rowspanNotas = count($rightRows);
foreach ($rightRows as $idx => $filaDerecha) {
  $midRows .= '<tr>';
  if ($idx === 0) {
    $midRows .= '<td colspan="6" rowspan="' . $rowspanNotas . '" valign="top" style="padding:6px;"><b>NOTAS:</b><br>' . $notasSanitizadas . '</td>';
  }
  $midRows .= $filaDerecha . '</tr>';
}

$tblTot = '
            <style>
               table.first {
                    color: #003300;
                    font-family: helvetica;
                    font-size: 8pt;
                    border-collapse: collapse;
                    border: 1px solid #DADADA;
                }
                table.first td, table.first th {
                    border: 1px solid #DADADA;
                }
            </style>
           <table width="100%" class="first" border="1">
              <tbody>
                <tr>
                  <th valign="middle" align="right" colspan="2"></th>
                  <th valign="middle" align="right" style="background-color: #ECECEC;font-weight: bold;">No.Items</th>
                  <th valign="middle" align="right">' . $items . '</th>
                  <th valign="middle" align="right" style="background-color: #ECECEC;font-weight: bold;">No.Unidades</th>
                  <th valign="middle" align="right">' . $unds . '</th>
                  <th valign="middle" align="right" style="background-color: #ECECEC;font-weight: bold;">SUBTOTAL</th>
                  <th valign="middle" align="right">' . $fmt_subtotal . '</th>
                </tr>
                ' . $midRows . '
                <tr>
                  <th valign="middle" align="right" style="background-color: #ECECEC;font-weight: bold;">Elaborado por</th>
                  <th valign="middle" align="left" colspan="7">' . $usuario_crea . ' - ' . $nombres_usuario . '</th>
                </tr>
              </tbody>
            </table>';
$pdf->writeHTML($tblTot, true, false, false, false, '');
if ($modo === 'guardar') {
  $pdf->Output($_GET['archivo'], 'F'); // For saving to a local file
} else {
  $pdf->Output('rpt.pdf', 'I'); // For inline display
}
