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
                    UPPER(t.razon_comercial) AS razon_comercial,
                    UPPER(t.representante_legal) AS representante_legal,
                    t.nit,
                    t.nit_digito,
                    LOWER(t.correo) AS correo,
                    TRIM(t.direccion) AS direccion,
                    p.descripcion AS nombre_pais,
                    d.departamento AS nombre_dpto,
                    m.municipio AS nombre_mpio,
                    t.telefono,
                    t.logo
                FROM t_empresa t
                INNER JOIN t_paises p 
                    ON p.pais = t.codigo_pais 
                INNER JOIN t_departamentos d 
                    ON d.codigo_dpto = t.codigo_dpto  
                  AND d.codigo_pais = t.codigo_pais 
                INNER JOIN t_municipios m 
                    ON m.codigo_pais = t.codigo_pais 
                  AND m.codigo_dpto = t.codigo_dpto 
                  AND m.codigo = t.codigo_mpio");


$d = reset($e);

$dataEmp = [
  'razon' => $d['razon_comercial'] ?? '',
  'representante' => $d['representante_legal'] ?? '',
  'nit' => isset($d['nit']) ? number_format($d['nit'], 0, '', '.') : '',
  'digito' => $d['nit_digito'] ?? '',
  'direccion' => $d['direccion'] ?? '',
  'ciudad' => $d['nombre_mpio'] ?? '',
  'dpto' => $d['nombre_dpto'] ?? '',
  'pais' => $d['nombre_pais'] ?? '',
  'correo' => $d['correo'] ?? '',
  'telefono' => $d['telefono'] ?? '',
  'logo' => !empty($d['logo']) ? $d['logo'] : 'default_logo.png'
];


$t = GenerarArray(" SELECT
                        t.id as id_tercero,
                        t.nit,
                        t.nit_digito,
                        CONCAT(t.primer_nombre,' ',t.segundo_nombre,' ',t.primer_apellido,' ',t.segundo_apellido) as nombres,
                        t.razon_comercial,
                        d.id_bodega,
                        d.id_tipo_documento,
                        d.notas,
                        d.id as id_transaccion,
                        d.valor_total,
                        d.numero,
                        UPPER(u.usuario) as usuario,
                        UPPER(CONCAT(u.nombres,' ',u.apellidos)) as nombres_usuario, 
                        d.fecha_hora,
                        p.descripcion as nombre_pais,
                        s.departamento  as nombre_dpto,
                        m.municipio  as nombre_mpio,
                        upper(r.direccion) as direccion,
                        r.telefono,
                        a.descripcion as desc_bodega,
                        rd.fechaEnvio,
                        rd.cufe,
                        rd.fechaValidacion,
                        rd.QRCode,
                        d.vencimiento,
                        z.tipo,
                        z.descripcion,
                        z.rango_inicial,
                        z.rango_final,
                        z.fecha_inicio ,
                        z.fecha_fin,
                        z.resolucion,
                        z.documento_electronico,
                        d.dias,
                        d.descripcion_condicion
                    from t_transacciones d
                    inner join t_terceros             t on t.id = d.id_tercero 
                    inner join t_terceros_direcciones r on r.id = d.id_tercero_dir
                    inner join t_usuarios             u on u.id=d.id_usuario
                    inner join t_tipos_documentos     z on z.id=d.id_tipo_documento 
                    inner join t_paises               p on p.pais = r.codigo_pais 
                    inner join t_departamentos        s on s.codigo_dpto = r.codigo_dpto  and s.codigo_pais = r.codigo_pais 
                    inner join t_municipios           m on m.codigo_pais = r.codigo_pais and m.codigo_dpto  = r.codigo_dpto and m.codigo =r.codigo_mpio
                    inner join t_almacenes            a on a.id = d.id_bodega 
                    left  join t_respuestas_dian     rd on rd.tipo = d.tipo and rd.numero= d.numero 
                    where 
                        d.id = $id_doc");

$d = reset($t);
$dataCli = [
  'nombres' => $d['nombres'] ?? '',
  'razon' => $d['razon_comercial'] ?? '',
  'nit' => isset($d['nit']) ? number_format($d['nit'], 0, '', '.') : '',
  'digito' => $d['nit_digito'] ?? '',
  'direccion' => $d['direccion'] ?? '',
  'ciudad' => $d['nombre_mpio'] ?? '',
  'dpto' => $d['nombre_dpto'] ?? '',
  'telefono' => $d['telefono'] ?? '',
  'tipo_doc' => $d['tipo'] ?? '',
  'desc_doc' => $d['descripcion'] ?? '',
  'numero_doc' => $d['numero'] ?? '',
  'fecha_hora' => $d['fecha_hora'] ?? '',
  'usuario_crea' => $d['usuario'] ?? '',
  'usuario_nombre' => $d['nombres_usuario'] ?? '',
  'bodega_id' => $d['id_bodega'] ?? '',
  'bodega_desc' => $d['desc_bodega'] ?? '',
  'cufe' => $d['cufe'] ?? '',
  'qrcode' => $d['QRCode'] ?? '',
  'fecha_envio' => $d['fechaEnvio'] ?? '',
  'fecha_valid' => $d['fechaValidacion'] ?? '',
  'vencimiento' => $d['vencimiento'] ?? '',
  'dias' => $d['dias'] ?? '',
  'condicion' => $d['descripcion_condicion'] ?? '',
  'res_consecutivo' => trim(($d['rango_inicial'] ?? '') . ' a ' . ($d['rango_final'] ?? '')),
  'res_fecha' => trim(($d['fecha_inicio'] ?? '') . ' a ' . ($d['fecha_fin'] ?? '')),
  'res_numero' => $d['resolucion'] ?? '',
  'res_prefijo' => $d['tipo'] ?? '',
  'notas' => $d['notas'] ?? ''
];

// Ruta del logo
$logoPath = '../../assets/img/logo/' . $dataEmp['logo'];
$pdf->Image(
  $logoPath, // ruta
  12, // X en mm desde el borde izquierdo
  20, // Y en mm desde el borde superior
  35, // ancho en mm
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
$config = $_SESSION["Ses_confSys"]['confGeneral'];
$configManejaLote = false;
$configManejaFE = false;
$espacio = '';
$textoCufe = '';
foreach ($config as $item) {
  // Validar lote y fecha de vencimiento
  if (isset($item['ManejaFechaLote']) && $item['ManejaFechaLote'] === 'S') {
    $configManejaLote = true;
  }
  // Validar facturación electronica
  if (isset($item['ManejaFe']) && $item['ManejaFe'] === 'S') {
    $configManejaFE = true;
    $espacio = '&nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;<br> &nbsp;<br>&nbsp;<br>&nbsp;';
    $textoCufe = '<tr style=" text-align: left; font-size:8px">
                   <td colspan="3"><b>Fecha validación :</b>' . $dataCli['fecha_valid'] . '</td>
                  </tr>
                  <tr style=" text-align: left; font-size:8px">
                   <td colspan="3"><b>Cufe:</b>' . $dataCli['cufe'] . '</td>
                  </tr>';
  }
}


$tbl = '<style>
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
                    <strong>' . $dataEmp['razon'] . '</strong><br>
                    Nit: ' . $dataEmp['nit'] . ' - ' . $dataEmp['digito'] . '<br>
                    Dirección: ' . $dataEmp['direccion'] . '<br>
                    Ciudad: ' . $dataEmp['dpto'] . ' - ' . $dataEmp['ciudad'] . ' <br>
                    Email: ' . $dataEmp['correo'] . ' <br>
                    Teléfono: ' . $dataEmp['telefono'] . ' <br>
                    Consecutivo:' . $dataCli['res_consecutivo'] . '<br>
                    Fecha: ' . $dataCli['res_fecha'] . ' <br>
                    Resolución: ' . $dataCli['res_numero'] . ' Prefijo: ' . $dataCli['res_prefijo'] . '<br>
                    
                </td>
                <td style="width: 30%; text-align: center;">                
                    <strong>' . $dataCli['desc_doc'] . '</strong><br>
                    <strong>' . $dataCli['tipo_doc'] . '</strong> - ' . $dataCli['numero_doc'] . '<br>
                    <strong>ALMACEN</strong><br>
                    ' . $dataCli['bodega_id'] . ' - ' . $dataCli['bodega_desc'] . '
                    ' . $espacio . '<br>                   
                </td>
            </tr>' . $textoCufe . '
           
        </table>
';
$pdf->writeHTML($tbl, true, false, false, false, '');

if ($configManejaFE) {
  $x = 162;
  $y = 28;
  $width = 30;
  $height = 30;
  $style = array(
    'border' => false,
    'vpadding' => 'auto',
    'hpadding' => 'auto',
    'fgcolor' => array(0, 0, 0),
    'bgcolor' => false,
    'module_width' => 1,
    'module_height' => 1
  );
  $pdf->write2DBarcode($dataCli['qrcode'], 'QRCODE,M', $x, $y, $width, $height, $style, 'N');
  $pdf->Ln(13);
}

$tblTer = '<style>
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
                <td style="background-color: #ECECEC;"><b>CLIENTE</b></td>
                <td colspan="3" >' . $dataCli['nombres'] . ' </td>
              </tr>
              <tr>
                <td style="background-color: #ECECEC;"><b>RAZÓN COMERCIAL</b></td>
                <td colspan="3" >' . $dataCli['razon'] . '</td>
              </tr>
              <tr>
                <td style="background-color: #ECECEC;"><b>NIT</b></td>
                <td>' . $dataCli['nit'] . ' - ' . $dataCli['digito'] . '</td>
                <td style="background-color: #ECECEC;"><b>DIRECCIÓN</b></td>
                <td>' . $dataCli['direccion'] . '</td>
              </tr>
              <tr>
                <td style="background-color: #ECECEC;"><b>CIUDAD</b></td>
                <td>' . $dataCli['ciudad'] . ' - ' . $dataCli['dpto'] . '</td>
                <td style="background-color: #ECECEC;"><b>TELÉFONO</b></td>
                <td>' . $dataCli['telefono'] . '</td>
              </tr>
              <tr>
                <td style="background-color: #ECECEC;"><b>FECHA CREACIÓN</b></td>
                <td>' . $dataCli['fecha_hora'] . '</td>
                <td style="background-color: #ECECEC;"><b>FECHA IMPRESIÓN</b></td>
                <td>' . $fechaGeneracion . '</td>
              </tr>
              <tr>
                <td style="background-color: #ECECEC;"><b>VENCIMIENTO</b></td>
                <td>' . $dataCli['vencimiento'] . '</td>
                <td style="background-color: #ECECEC;"><b>FORMA DE PAGO</b></td>
                <td>' . $dataCli['condicion'] . '</td>
              </tr>
            </table>';
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
                         ifnull(t.icui,0) as icui,
                         r.tipo,
                         ((t.valor_unitario * (1-(t.pcj_descuento /100)))*(t.pcj_iva /100)) * t.cantidad as valor_iva
                        from t_transacciones_detalle t
                        inner join t_referencias r on r.id=t.id_referencia 
                        where 
                         t.id_transaccion = $id_doc
                        order by 
                         r.descripcion");
$total = 0;
$valor_iva = 0;
$unds = 0;
$items = 0;

//totales
$vlr_total_compra = 0;
$vlr_total_iva = 0;
$vlr_total_dto = 0;
$vlr_total_impoconsumo = 0;
$vlr_total_ibua = 0;
$vlr_total_icui = 0;
$vlr_subtotal = 0;

foreach ($q as $d) {

  $cantidad = $d['cantidad'];
  $costo = $d['valor_unitario'];
  $vlr_dto = $d['valor_unitario'] * ($d["dto"] / 100);
  $vlr_impoconsumo = $d["impoconsumo"];
  $vlr_ibua = $d["ibua"];
  $vlr_icui = ($costo - $vlr_dto) * $d["icui"] / 100;
  $vlr_iva = ($costo - $vlr_dto) * ($d["iva"] / 100);


  $costo_neto = $costo - $vlr_dto + $vlr_impoconsumo + $vlr_icui + $vlr_ibua + $vlr_iva;
  $costo_total = $costo_neto * $cantidad;

  $vlr_subtotal += $costo * $cantidad;
  $vlr_total_compra += $costo_total;
  $vlr_total_dto += $vlr_dto * $cantidad;
  $vlr_total_ibua += $vlr_ibua * $cantidad;
  $vlr_total_icui += $vlr_icui * $cantidad;
  $vlr_total_iva += $vlr_iva * $cantidad;
  $vlr_total_impoconsumo += $vlr_impoconsumo * $cantidad;

  $items++;
  $unds += $cantidad;

  $descripcion = $d['descripcion'];


  if ($configManejaLote && $d["tipo"] == 'P') {
    $descripcion = $d['descripcion'] . '<br><b>Lote:</b>' . $d['lote'] . '<br><b>Vmcto:</b>' . $d['vencimiento'];
  }


  $det .= '<tr>
            <td width="8%" align="center">' . $d['codigo'] . '</td>
            <td width="35%">' . $descripcion . '</td>
            <td width="6%" align="center">' . $d['und'] . '</td>
            <td width="6%" align="center">' . number_format($cantidad) . '</td>
            <td width="11%" align="right">' . number_format($d["valor_unitario"], 2) . '</td>
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
                   <th style="background-color: #ECECEC;font-weight: bold;" width="11%">PRECIO</th>
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
// Medios de Pago
$medios_pago = [];
$m = GenerarArray("
    SELECT 
        ttp.valor,
        ttp.cambio,
        tmp.descripcion 
    FROM t_transacciones_pago ttp 
    INNER JOIN t_medios_pagos tmp ON tmp.id = ttp.id_medio_pago 
    WHERE 
        ttp.tipo   = '" . $dataCli['tipo_doc'] . "' 
        AND ttp.numero = '" . $dataCli['numero_doc'] . "' 
");

foreach ($m as $mp) {
  $medios_pago[] = [
    'descripcion' => $mp['descripcion'],
    'valor'       => (float)$mp['valor'],
    'cambio'      => (float)$mp['cambio']
  ];
}
$htmlMedios = '
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
           <table width="50%" class="first" border="0">
  <thead>
    <tr>
      <th style="background-color: #ECECEC;font-weight: bold;">Medio de pago</th>
      <th style="background-color: #ECECEC;font-weight: bold;">Valor</th>
      <th style="background-color: #ECECEC;font-weight: bold;">Cambio</th>
    </tr>
  </thead>
  <tbody>';

$totalPagado = 0;
foreach ($medios_pago as $mp) {
  $htmlMedios .= '
    <tr>
      <td>' . htmlspecialchars($mp['descripcion']) . '</td>
      <td style="text-align:right;">' . number_format($mp['valor'], 2, ',', '.') . '</td>
      <td style="text-align:right;">' . number_format($mp['cambio'], 2, ',', '.') . '</td>
    </tr>';
  $totalPagado += $mp['valor'];
}

$htmlMedios .=  '
  </tbody>
  <tfoot>
    <tr>
      <td>TOTAL</td>
      <td style="text-align:right;" colspan="2">' . number_format($totalPagado, 2, ',', '.') . '</td>
    </tr>
  </tfoot>
</table>';
//$pdf->writeHTML( $htmlMedios, true, false, false, false, '' );
//Totales
$vlr_subtotal = number_format(($vlr_subtotal), 2);
$vlr_total_dto = number_format(($vlr_total_dto), 2);
$vlr_total_impoconsumo = number_format(($vlr_total_impoconsumo), 2);
$vlr_total_compra = number_format($vlr_total_compra, 2);
$vlr_total_iva = number_format($vlr_total_iva, 2);
$vlr_total_saludables = number_format($vlr_total_ibua + $vlr_total_icui);

$tblTot = '<style>
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
           <table width="100%" class="first" border="0">
              <tbody>
                <tr>
                  <th valign="middle" align="right" colspan="2"></th>
                  <th valign="middle" align="right" style="background-color: #ECECEC;font-weight: bold;">No.Items</th>
                  <th valign="middle" align="right">' . $items . '</th>
                  <th valign="middle" align="right" style="background-color: #ECECEC;font-weight: bold;">No.Unidades</th>
                  <th valign="middle" align="right">' . $unds . '</th>
                  <th valign="middle" align="right" style="background-color: #ECECEC;font-weight: bold;">SUBTOTAL</th>
                  <th valign="middle" align="right">' . $vlr_subtotal . ' </th>
                </tr>
                <tr>
                  <th colspan="6" rowspan="5"><b>Notas: </b>' . $dataCli['notas'] . '</th>
                  <th valign="middle" align="right" style="background-color: #ECECEC;font-weight: bold;">DTO</th>
                  <th valign="middle" align="right">' . $vlr_total_dto . '</th>
                </tr>
                <tr>
                  <th valign="middle" align="right" style="background-color: #ECECEC;font-weight: bold;">IVA</th>
                  <th valign="middle" align="right">' . $vlr_total_iva . '</th>
                </tr>
                <tr>
                  <th valign="middle" align="right" style="background-color: #ECECEC;font-weight: bold;">TOTAL</th>
                  <th valign="middle" align="right">' . $vlr_total_compra . '</th>
                </tr>
              </tbody>
            </table>
            <table width="100%" class="first" border="0">
              <tbody>
                <tr>
                  <th valign="middle" align="right" style="background-color: #ECECEC;font-weight: bold;">Elaborado por</th>
                  <th valign="middle" align="left" colspan="7">' . $dataCli['usuario_crea'] . ' - ' . $dataCli['usuario_nombre'] . '</th>
                </tr>
              </tbody>
            </table>
';
$pdf->writeHTML($tblTot, true, false, false, false, '');

if ($modo === 'guardar') {
  $pdf->Output($_GET['archivo'], 'F'); // For saving to a local file
} else {
  $pdf->Output('rpt.pdf', 'I'); // For inline display
}
