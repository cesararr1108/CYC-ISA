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

$tipo = $_GET['tipo'];
$numero = $_GET['numero'];

$pdf = new MYPDF('P', 'mm', 'LETTER');
$pdf->Open();
$pdf->AddPage();
// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Obtener datos de la empresa
$e = GenerarArray("
    select 
        upper(t.razon_comercial) as razon_comercial,
        upper(t.representante_legal) as representante_legal,
        t.nit,
        t.nit_digito,
        upper(t.correo) as correo,
        t.direccion,
        p.descripcion as nombre_pais,
        d.departamento as nombre_dpto,
        m.municipio as nombre_mpio,
        t.telefono,
        t.logo
    from t_empresa t
    inner join t_paises p 
        on p.pais = t.codigo_pais 
    inner join t_departamentos d 
        on d.codigo_dpto = t.codigo_dpto  
        and d.codigo_pais = t.codigo_pais 
    inner join t_municipios m 
        on m.codigo_pais = t.codigo_pais 
        and m.codigo_dpto = t.codigo_dpto 
        and m.codigo = t.codigo_mpio
");

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

// Obtener datos del acta de recepción
$a = GenerarArray("SELECT
                      ar.id,
                      ar.id_referencia,
                      ar.id_transaccion,
                      ar.cantidad,
                      ar.lote,
                      ar.vmcto,
                      ar.nota,
                      ar.id_usuario,
                      ar.fecha_creacion,
                      ar.id_tercero,
                      ar.id_bodega,
                      ar.invima,
                      ar.aprobado,
                      r.codigo,
                      r.descripcion,
                      r.id_marca,
                      r.und_1,
                      r.und_2,
                      r.und_3,
                      r.und_4,
                      r.cant_1,
                      r.cant_2,
                      r.cant_3,
                      r.cant_4,
                      x.descripcion AS marca,
                      ter.razon_comercial,
                      ter.nit,
                      ter.nit_digito,
                      CONCAT(ter.primer_nombre, ' ', ter.segundo_nombre, ' ', ter.primer_apellido, ' ', ter.segundo_apellido) AS nombres_tercero,
                      td.direccion AS dir_tercero,
                      td.telefono AS tel_tercero,
                      t.numero AS numero_transaccion,
                      t.fecha_hora AS fecha_transaccion,
                      al.descripcion AS nombre_bodega,
                      UPPER(CONCAT(u.nombres, ' ', u.apellidos)) AS nombres_usuario,
                      UPPER(u.usuario) AS usuario_creacion,
                      t.tipo,
                      t.numero
                  FROM t_actas_recepcion ar
                  INNER JOIN t_referencias r 
                      ON r.id = ar.id_referencia
                  LEFT JOIN t_marcas x 
                      ON x.id = r.id_marca
                  INNER JOIN t_terceros ter 
                      ON ter.id = ar.id_tercero 
                  LEFT JOIN (select * from t_terceros_direcciones) td 
                      ON td.id_tercero = ter.id
                  LEFT JOIN t_paises p 
                      ON p.pais = td.codigo_pais
                  LEFT JOIN t_departamentos dep 
                      ON dep.codigo_pais = td.codigo_pais
                    AND dep.codigo_dpto = td.codigo_dpto
                  LEFT JOIN t_municipios m 
                      ON m.codigo_pais = td.codigo_pais
                    AND m.codigo_dpto = td.codigo_dpto
                    AND m.codigo = td.codigo_mpio
                  INNER JOIN t_transacciones t 
                      ON t.id = ar.id_transaccion
                  INNER JOIN t_almacenes al 
                      ON al.id = ar.id_bodega
                  INNER JOIN t_usuarios u 
                      ON u.id = ar.id_usuario
                  WHERE t.tipo = '$tipo'
                    AND t.numero = '$numero'");

$acta_encontrada = false;
$det_rows = '';
$first = true;

foreach ($a as $d) {
  $acta_encontrada = true;

  // Datos de encabezado: solo de la primera fila
  if ($first) {
    $acta_id = $d['id'];
    $acta_tipo = $d['tipo'];
    $acta_numero = $d['numero'];
    $ter_razon = $d['razon_comercial'];
    $ter_nit = number_format($d['nit'], 0, '', '.');
    $ter_nit_digito = $d['nit_digito'];
    $ter_nombres = $d['nombres_tercero'];
    $ter_dir = $d['dir_tercero'];
    $ter_tel = $d['tel_tercero'];
    $num_transaccion = $d['numero_transaccion'];
    $fecha_transaccion = $d['fecha_transaccion'];
    $nombre_bodega = $d['nombre_bodega'];
    $usuario = $d['usuario_creacion'];
    $nombres_usuario = $d['nombres_usuario'];
    $fecha_creacion = $d['fecha_creacion'];
    $first = false;
  }

  // Datos de cada producto
  $p_codigo   = htmlspecialchars($d['codigo']);
  $p_desc     = htmlspecialchars($d['descripcion']);
  $p_marca    = !empty($d['marca']) ? htmlspecialchars($d['marca']) : 'N/A';
  $p_cantidad = $d['cantidad'];
  $p_lote     = htmlspecialchars($d['lote']);
  $p_vmcto    = htmlspecialchars($d['vmcto']);
  $p_nota     = !empty($d['nota']) ? htmlspecialchars($d['nota']) : '';
  $p_invima   = htmlspecialchars($d['invima']);
  $p_aprobado = $d['aprobado'];

  // Determinar unidad de compra
  $p_und = 'UND';
  if ($d['cant_1'] == $p_cantidad) $p_und = $d['und_1'];
  elseif ($d['cant_2'] == $p_cantidad) $p_und = $d['und_2'];
  elseif ($d['cant_3'] == $p_cantidad) $p_und = $d['und_3'];
  elseif ($d['cant_4'] == $p_cantidad) $p_und = $d['und_4'];

  $p_estado_text  = $p_aprobado == 1 ? 'ACEPTADO' : 'NO ACEPTADO';
  $p_color_estado = $p_aprobado == 1 ? '#90EE90' : '#FFB6C6';

  $det_rows .= '<tr>
                    <td width="8%" style="text-align:center;font-size:7pt;">' . $p_codigo . '</td>
                    <td width="26%" style="text-align:center;font-size:7pt;">' . $p_desc . '</td>
                    <td width="10%" style="text-align:center;font-size:7pt;">' . $p_marca . '</td>
                    <td width="5%" style="text-align:center;font-size:7pt;">' . $p_cantidad . '</td>
                    <td width="5%" style="text-align:center;font-size:7pt;">' . $p_und . '</td>
                    <td width="8%" style="text-align:center;font-size:7pt;">' . $p_invima . '</td>
                    <td width="9%" style="text-align:center;font-size:7pt;">' . $p_lote . '</td>
                    <td width="9%" style="text-align:center;font-size:7pt;">' . $p_vmcto . '</td>
                    <td width="12%" style="text-align:center;font-size:7pt;">' . $p_nota . '</td>
                    <td width="8%" style="background-color:' . $p_color_estado . '; text-align:center;font-size:7pt;">' . $p_estado_text . '</td>
                  </tr>';
}

if (!$acta_encontrada) {
  die('Acta de recepción no encontrada');
}

// Ruta del logo
$logoPath = '../../assets/img/logo/' . $dataEmp['logo'];
$pdf->Image(
  $logoPath, // ruta
  18, // X en mm desde el borde izquierdo
  15, // Y en mm desde el borde superior
  25, // ancho en mm
  0, // alto (0 = proporcional al ancho)
  '', // tipo de imagen (TCPDF lo detecta solo)
  '', // link (vacío si no hay enlace)
  '', // align
  false, // resize
  300, // dpi
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
                    Fecha impresión: ' . $fechaGeneracion  . ' <br>
                    
                </td>
                <td style="width: 30%; text-align: center;">                
                    <strong>ACTA DE RECEPCIÓN</strong><br>
                    <strong>' . $acta_tipo . '</strong> - ' . $acta_numero . '<br>
                    <strong>FECHA</strong><br>
                    ' . $fecha_creacion . '          
                </td>
              </tr>
           
        </table>
';
$pdf->writeHTML($tbl, true, false, false, false, '');

// Información del proveedor
$tblTer = <<<EOD
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
                padding: 5px;
            }
        </style>
        <table width="100%" class="first">
          <tr>
            <td style="background-color: #ECECEC; width: 20%;"><b>PROVEEDOR</b></td>
            <td colspan="3" style="width: 85%;">$ter_nombres</td>
          </tr>
          <tr>
            <td style="background-color: #ECECEC;"><b>RAZÓN COMERCIAL</b></td>
            <td colspan="3">$ter_razon</td>
          </tr>
          <tr>
            <td style="background-color: #ECECEC;"><b>NIT</b></td>
            <td style="width: 35%;">$ter_nit - $ter_nit_digito</td>
            <td style="background-color: #ECECEC; width: 15%;"><b>DIRECCIÓN</b></td>
            <td style="width: 35%;">$ter_dir</td>
          </tr>
          <tr>
            <td style="background-color: #ECECEC;"><b>TELÉFONO</b></td>
            <td>$ter_tel</td>
            <td style="background-color: #ECECEC;"><b>RESPONSABLE</b></td>
            <td>$usuario - $nombres_usuario</td>
          </tr>
        </table>
EOD;
$pdf->writeHTML($tblTer, true, false, false, false, '');

// Tabla de detalle de productos
$det = <<<EOD
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
              <td style="background-color: #ECECEC;font-weight: bold;" width="8%" align="center">CÓDIGO</td>
              <td style="background-color:#ECECEC;font-weight:bold;font-size:7pt;" width="26%">DESCRIPCIÓN</td>
              <td style="background-color:#ECECEC;font-weight:bold;font-size:7pt;" width="10%">MARCA</td>
              <td style="background-color:#ECECEC;font-weight:bold;text-align:center;font-size:7pt;" width="5%">CANT.</td>
              <td style="background-color:#ECECEC;font-weight:bold;text-align:center;font-size:7pt;" width="5%">UND</td>
              <td style="background-color:#ECECEC;font-weight:bold;font-size:7pt;" width="8%">INVIMA</td>
              <td style="background-color:#ECECEC;font-weight:bold;font-size:7pt;" width="9%">LOTE</td>
              <td style="background-color:#ECECEC;font-weight:bold;font-size:7pt;" width="9%">VENCTO</td>
              <td style="background-color:#ECECEC;font-weight:bold;font-size:7pt;" width="12%">NOTA</td>
              <td style="background-color:#ECECEC;font-weight:bold;text-align:center;font-size:7pt;" width="8%">ESTADO</td>
            </tr>
            </thead>
             <tbody>$det_rows</tbody>
        </table>
EOD;
$pdf->writeHTML($det, true, false, false, false, '');

$obs = '';
$pdf->writeHTML($obs, true, false, false, false, '');

// Sección de firmas
$firmas = <<<EOD
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
                padding: 5px;
            }
            .firma-box {
                text-align: center;
                height: 60px;
                border-top: 1px solid #000;
                padding-top: 3px;
            }
        </style>
        <table width="100%" class="first" style="margin-top: 15px;">
          <tr>
            <td style="width: 33%; text-align: center;"><b>RECIBIDO POR</b></td>
            <td style="width: 33%; text-align: center;"><b>REVISADO POR</b></td>
            <td style="width: 33%; text-align: center;"><b>APROBADO POR</b></td>
          </tr>
          <tr>
            <td style="width: 33%; text-align: center;"><div class="firma-box">
              <br><br>_____________________________<br>
              <small style="font-size: 7pt;">Firma y Cédula</small>
            </div></td>
            <td style="width: 33%; text-align: center;"><div class="firma-box">
              <br><br>_____________________________<br>
              <small style="font-size: 7pt;">Firma y Cédula</small>
            </div></td>
            <td style="width: 33%; text-align: center;"><div class="firma-box">
              <br><br>_____________________________<br>
              <small style="font-size: 7pt;">Firma y Cédula</small>
            </div></td>
          </tr>
          <tr>
            <td style="width: 33%; text-align: center; font-size: 8pt;"><b>NOMBRE:</b>_____________________</td>
            <td style="width: 33%; text-align: center; font-size: 8pt;"><b>NOMBRE:</b>_____________________</td>
            <td style="width: 33%; text-align: center; font-size: 8pt;"><b>NOMBRE:</b>_____________________</td>
          </tr>
          <tr>
            <td style="width: 33%; text-align: center; font-size: 8pt;"><b>FECHA:</b>_____________________</td>
            <td style="width: 33%; text-align: center; font-size: 8pt;"><b>FECHA:</b>_____________________</td>
            <td style="width: 33%; text-align: center; font-size: 8pt;"><b>FECHA:</b>_____________________</td>
          </tr>
        </table>
EOD;
$pdf->writeHTML($firmas, true, false, false, false, '');

// Pie de página con información de auditoría
$auditoria = <<<EOD
        <style>
           table.first {
                color: #666;
                font-family: helvetica;
                font-size: 7pt;
                border-collapse: collapse;
                border: 1px solid #DADADA;
            }
            table.first td {
                border: 1px solid #DADADA;
                padding: 3px;
            }
        </style>
        <table width="100%" class="first" style="margin-top: 10px;">
          <tr>
            <td style="background-color: #F5F5F5;"><b>INFORMACIÓN DE AUDITORÍA</b></td>
          </tr>
          <tr>
            <td style="background-color: #FAFAFA;">
              <b>Usuario Creación:</b> $usuario - $nombres_usuario | 
              <b>Fecha Creación:</b> $fecha_creacion | 
              <b>Impresión:</b> $fechaGeneracion
            </td>
          </tr>
        </table>
EOD;
$pdf->writeHTML($auditoria, true, false, false, false, '');

$pdf->Output('acta_recepcion_' . $acta_id . '.pdf');
