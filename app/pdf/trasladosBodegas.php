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

$e = GenerarArray("select 
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
foreach ($e as $d) {
  $emp_razon = $d['razon_comercial'];
  $emp_nit = number_format($d['nit'], 0, '', '.');
  $emp_nit_digito = $d['nit_digito'];
  $emp_direccion = $d['direccion'];
  $emp_ciudad = $d['nombre_mpio'];
  $emp_dpto = $d['nombre_dpto'];
  $emp_mail = $d['correo'];
  $emp_telefono = $d['telefono'];
  $emp_logo = !empty($d['logo']) ? $d['logo'] : 'default_logo.png';
}

$t = GenerarArray(" select
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
                        z.tipo,
                        z.descripcion,
                        p.descripcion as nombre_pais,
                        s.departamento  as nombre_dpto,
                        m.municipio  as nombre_mpio,
                        upper(r.direccion) as direccion,
                        r.telefono,
                        a.descripcion as desc_bodega,
                        a2.descripcion as desc_bodega_destino
                    from t_transacciones d
                    inner join t_terceros           t on t.id = d.id_tercero 
                    inner join t_terceros_direcciones r on r.id = d.id_tercero_dir
                    inner join t_usuarios           u on u.id=d.id_usuario
                    inner join t_tipos_documentos   z on z.id=d.id_tipo_documento 
                    inner join t_paises             p on p.pais = r.codigo_pais 
                    inner join t_departamentos s on s.codigo_dpto = r.codigo_dpto  and s.codigo_pais  = r.codigo_pais 
                    inner join t_municipios m on m.codigo_pais = r.codigo_pais and m.codigo_dpto  = r.codigo_dpto and m.codigo =r.codigo_mpio
                    inner join t_almacenes a on a.id  = d.id_bodega
                    inner join t_almacenes a2 on a2.id  = d.id_bodega_destino  
                    where 
                        d.id = $id_doc");

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
  $usuario_crea = $d['usuario'];
  $nombres_usuario = $d['nombres_usuario'];
  $id_bodega = $d['id_bodega'];
  $desc_bodega = substr($d['desc_bodega'], 0, 50);
  $desc_bodega_destino = substr($d['desc_bodega_destino'], 0, 50);
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
                    <strong>Bodega salida</strong><br>
                    $id_bodega - $desc_bodega<br>
                    <strong>Bodega entrada</strong><br>
                    $desc_bodega_destino
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
$q = GenerarArray("SELECT
                      r.codigo,
                      r.descripcion,
                      m.descripcion AS marca,
                      t.und,
                      t.lote,
                      t.vmcto AS vencimiento,
                      (t.costo_unitario * t.can_und) AS costo_unitario,
                      t.can_und,
                      t.pcj_iva AS iva,
                      SUM(CASE WHEN t.sw = 6 THEN (t.cantidad / t.can_und) ELSE 0 END) AS cantidad_salida,
                      SUM(CASE WHEN t.sw = 2 THEN (t.cantidad / t.can_und) ELSE 0 END) AS cantidad_entrada,
                      SUM(CASE WHEN t.sw = 6 THEN (t.cantidad * t.costo_unitario)* ((t.pcj_iva / 100) + 1) ELSE 0 END) AS costo_total_salida,
                      SUM(CASE WHEN t.sw = 2 THEN (t.cantidad * t.costo_unitario)* ((t.pcj_iva / 100) + 1) ELSE 0 END) AS costo_total_entrada,
                      SUM(CASE WHEN t.sw = 6 THEN ((t.costo_unitario * t.cantidad ) * (t.pcj_iva / 100)) ELSE 0 END) AS iva_salida,
                      SUM(CASE WHEN t.sw = 2 THEN ((t.costo_unitario * t.cantidad ) * (t.pcj_iva / 100)) ELSE 0 END) AS iva_entrada
                    FROM
                      t_transacciones_detalle t
                    INNER JOIN t_referencias r ON
                      r.id = t.id_referencia
                    LEFT JOIN t_marcas m ON
                      m.id = r.id_marca
                    WHERE
                      t.id_transaccion = $id_doc
                    GROUP BY
                      r.codigo,
                      r.descripcion,
                      m.descripcion,
                      t.und,
                      t.lote,
                      t.vmcto,
                      t.costo_unitario,
                      t.can_und,
                      t.pcj_iva
                    ORDER BY
                      r.descripcion;");

// Construir tabla con items agrupados
$total = 0;
$valor_iva = 0;
$unds = 0;
$items = 0;
$det = '';

foreach ($q as $item) {
  $cant_salida = $item['cantidad_salida'];
  $cant_entrada = $item['cantidad_entrada'];
  $costo_total_salida = $item['costo_total_salida'];
  $costo_total_entrada = $item['costo_total_entrada'];
  $costo_total = $costo_total_salida;

  $total += $costo_total;
  $valor_iva += $item['iva_salida'];
  $unds += $cant_salida;
  $items++;

  $det .= '<tr>
            <td width="8%" align="center">' . $item['codigo'] . '</td>
            <td width="40%">' . $item['descripcion'] . '</td>
            <td width="10%" align="center">' . $item['marca'] . '</td>
            <td width="10%" align="center">' . $item['lote'] . '</td>
            <td width="10%" align="center">' . substr($item['vencimiento'], 0, 10) . '</td>
            <td width="8%" align="center">' . $item['und'] . '</td>
            <td width="7%" align="center">' . number_format($cant_salida) . '</td>
            <td width="7%" align="center">' . number_format($cant_entrada) . '</td>
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
                   <th style="background-color: #ECECEC;font-weight: bold;" width="40%">DESCRIPCIÓN</th>
                   <th style="background-color: #ECECEC;font-weight: bold;" width="10%" align="center">MARCA</th>
                   <th style="background-color: #ECECEC;font-weight: bold;" width="10%" align="center">LOTE</th>
                   <th style="background-color: #ECECEC;font-weight: bold;" width="10%" align="center">VCMTO</th>
                   <th style="background-color: #ECECEC;font-weight: bold;" width="8%" align="center">UND</th>
                   <th style="background-color: #ECECEC;font-weight: bold;" width="7%" align="center">SALE</th>
                   <th style="background-color: #ECECEC;font-weight: bold;" width="7%" align="center">ENTRA</th>
                 </tr>
                </thead>
                <tbody>$det</tbody>
            </table>
EOD;
$pdf->writeHTML($tblDet, true, false, false, false, '');

//Totales
$subtotal = number_format(($total - $valor_iva), 2);
$total = number_format($total, 2);
$valor_iva = number_format($valor_iva, 2);

$tblTot = <<<EOD
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
              <tbody>
                <tr>
                  <th valign="middle" align="right" colspan="2"></th>
                  <th valign="middle" align="right" style="background-color: #ECECEC;font-weight: bold;">No.Items</th>
                  <th valign="middle" align="right">$items</th>
                  <th valign="middle" align="right" style="background-color: #ECECEC;font-weight: bold;">No.Unidades</th>
                  <th valign="middle" align="right">$unds</th>
                  <th valign="middle" align="right" style="background-color: #ECECEC;font-weight: bold;">SUBTOTAL</th>
                  <th valign="middle" align="right">$subtotal </th>
                </tr>
                <tr>
                  <th colspan="6" rowspan="2"></th>
                  <th valign="middle" align="right" style="background-color: #ECECEC;font-weight: bold;">IVA</th>
                  <th valign="middle" align="right">$valor_iva</th>
                </tr>
                <tr>
                  <th valign="middle" align="right" style="background-color: #ECECEC;font-weight: bold;">TOTAL</th>
                  <th valign="middle" align="right">$total</th>
                </tr>
                <tr>
                  <th valign="middle" align="right" style="background-color: #ECECEC;font-weight: bold;">Elaborado por</th>
                  <th valign="middle" align="left" colspan="7">$usuario_crea - $nombres_usuario</th>
                </tr>
              </tbody>
            </table>
EOD;
$pdf->writeHTML($tblTot, true, false, false, false, '');
if ($modo === 'guardar') {
  $pdf->Output($_GET['archivo'], 'F'); // For saving to a local file
} else {
  $pdf->Output('rpt.pdf', 'I'); // For inline display
}
