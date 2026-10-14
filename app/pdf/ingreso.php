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

$e = GenerarArray("
    select 
       /* upper(t.razon_comercial) as razon_comercial,
        upper(t.representante_legal) as representante_legal,
        t.nit,
        t.nit_digito,
        upper(t.correo) as correo,
        t.direccion,
        p.descripcion as nombre_pais,
        d.departamento as nombre_dpto,
        m.municipio as nombre_mpio,
        t.telefono,
        t.logo*/ 
        t.*
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
    'notas' => $d['notas'] ?? '',
    'valor_total' => $d['valor_total'] ?? 0
];

// Ruta del logo
$logoPath = '../../assets/img/logo/' . $dataEmp['logo'];
$pdf->Image(
    $logoPath, // ruta
    18, // X en mm desde el borde izquierdo
    10, // Y en mm desde el borde superior
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
$config = $_SESSION["Ses_confSys"]['confGeneral'];
$configManejaLote = false;
$configManejaFE = false;
$espacio = '';
$textoCufe = '';

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
                    Teléfono: ' . $dataEmp['telefono'] . '                    
                </td>
                <td style="width: 30%; text-align: center;">                
                    <strong>' . $dataCli['desc_doc'] . '</strong><br>
                    <strong>' . $dataCli['tipo_doc'] . '</strong> - ' . $dataCli['numero_doc'] . '<br>
                    <strong>ALMACEN</strong><br>
                    ' . $dataCli['bodega_id'] . ' - ' . $dataCli['bodega_desc'] . '
                    ' . $espacio . '                   
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
    $pdf->Ln(10);
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
                <td style="background-color: #ECECEC;"><b>TERCERO</b></td>
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
$total_letra = numeroALetras($dataCli['valor_total']);
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
<table width="100%" class="first" border="0">
  <thead>
    <tr>
      <th style="background-color: #ECECEC;font-weight: bold;">Valor</th>
      <th style="background-color: #ECECEC;font-weight: bold;">En letras</th>
    </tr>
  </thead>
  <tbody>
  <tr>
      <td>' . number_format($dataCli['valor_total'], 2, ',', '.') . '</td>
      <td>' . $total_letra . '</td>
    </tr>
  </tbody> 
</table>';
$pdf->writeHTML($htmlMedios, true, false, false, false, '');

// ===================== CRUCES =====================
$docCruces = GenerarArray("
               SELECT 
                 t.tipo_aplica ,
                 t.numero_aplica ,
                 t.valor ,
                 t.descuento ,
                 t.ret_FTE ,
                 t.ret_ICA ,
                 t.ret_IVA 
                FROM t_transacciones_cruce t 
                WHERE 
                t.id_transaccion  = $id_doc");
$htmlCruce = '
<style>
    table.first {
        width: 100%;
        border-collapse: collapse;
        font-family: helvetica;
        font-size: 8pt;
        color: #003300;
    }
    table.first th, table.first td {
        border: 1px solid #DADADA;
    }
    table.first th {
        background-color: #ECECEC;
        font-weight: bold;
        text-align: center;
    }
    .text-right { text-align: right; }
    .text-left { text-align: left; }
    .text-center { text-align: center; }
</style>

<table class="first" border="0">
  <thead>
    <tr>
      <th colspan="7" style="background-color: #ECECEC; font-weight: bold;">CRUCE DE DOCUMENTOS</th>
    </tr>
    <tr>
        <th style="background-color: #ECECEC; font-weight: bold;">Tipo</th>
        <th style="background-color: #ECECEC; font-weight: bold;">Numero</th>
        <th style="background-color: #ECECEC; font-weight: bold;">Valor</th>
        <th style="background-color: #ECECEC; font-weight: bold;">Descuento</th>
        <th style="background-color: #ECECEC; font-weight: bold;">ReteIVA</th>
        <th style="background-color: #ECECEC; font-weight: bold;">ReteICA</th>
        <th style="background-color: #ECECEC; font-weight: bold;">ReteFTE</th>
    </tr>
  </thead>
  <tbody>
';
// Recorremos resultados
if (count($docCruces) > 0) {
    foreach ($docCruces as $row) {
        $htmlCruce .= '
        <tr>
            <td class="text-center">' . htmlspecialchars($row['tipo_aplica']) . '</td>
            <td class="text-center">' . htmlspecialchars($row['numero_aplica']) . '</td>
            <td class="text-right">' . number_format($row['valor'], 2, ',', '.') . '</td>
            <td class="text-right">' . number_format($row['descuento'], 2, ',', '.') . '</td>
            <td class="text-right">' . number_format($row['ret_IVA'], 2, ',', '.') . '</td>
            <td class="text-right">' . number_format($row['ret_ICA'], 2, ',', '.') . '</td>
            <td class="text-right">' . number_format($row['ret_FTE'], 2, ',', '.') . '</td>
        </tr>';
    }
} else {
    $htmlCruce .= '
        <tr>
            <td colspan="7" class="text-center">No hay registros contables asociados a este documento.</td>
        </tr>';
}
// Agregar fila de totales
$htmlCruce .= '
  </tbody>
</table>
';

// Escribir en el PDF
$pdf->writeHTML($htmlCruce, true, false, false, false, '');
// ===================== CONTABLE =====================
$resContable = GenerarArray("
    SELECT 
        m.id_transaccion,
        m.fecha_hora,
        m.tipo,
        m.numero,
        m.nit,
        m.cuenta,
        UPPER(c.descripcion) AS cuenta_desc,
        m.debito,
        m.credito 
    FROM t_movimiento_contable m 
    INNER JOIN t_cuentas_contables c ON c.cuenta = m.cuenta 
    WHERE m.id_transaccion = $id_doc
    ORDER BY m.debito DESC, m.credito DESC");

// Construcción del HTML
$htmlContable = '
<style>
    table.first {
        width: 100%;
        border-collapse: collapse;
        font-family: helvetica;
        font-size: 8pt;
        color: #003300;
    }
    table.first th, table.first td {
        border: 1px solid #DADADA;
        padding: 4px;
    }
    table.first th {
        background-color: #ECECEC;
        font-weight: bold;
        text-align: center;
    }
    .text-right { text-align: right; }
    .text-left { text-align: left; }
    .text-center { text-align: center; }
</style>

<table class="first" border="0">
  <thead>
    <tr>
      <th colspan="5" style="background-color: #ECECEC; font-weight: bold;">IMPUTACIÓN CONTABLE</th>
    </tr>
    <tr>
        <th style="background-color: #ECECEC; font-weight: bold;" width="10%">NIT</th>
        <th style="background-color: #ECECEC; font-weight: bold;" width="10%">Cuenta</th>
        <th style="background-color: #ECECEC; font-weight: bold;" width="50%">Descripción</th>
        <th style="background-color: #ECECEC; font-weight: bold;" width="15%">Débito</th>
        <th style="background-color: #ECECEC; font-weight: bold;" width="15%">Crédito</th>
    </tr>
  </thead>
  <tbody>
';

// Variables de totales
$totalDebito = 0;
$totalCredito = 0;

// Recorremos resultados
if (count($resContable) > 0) {
    foreach ($resContable as $row) {
        $htmlContable .= '
        <tr>
            <td class="text-center" width="10%">' . htmlspecialchars($row['nit']) . '</td>
            <td class="text-center" width="10%">' . htmlspecialchars($row['cuenta']) . '</td>
            <td class="text-left" width="50%">' . htmlspecialchars($row['cuenta_desc']) . '</td>
            <td class="text-right" width="15%">' . number_format($row['debito'], 2, ',', '.') . '</td>
            <td class="text-right" width="15%">' . number_format($row['credito'], 2, ',', '.') . '</td>
        </tr>';

        $totalDebito += $row['debito'];
        $totalCredito += $row['credito'];
    }
} else {
    $htmlContable .= '
        <tr>
            <td colspan="5" class="text-center">No hay registros contables asociados a este documento.</td>
        </tr>';
}

// Agregar fila de totales
$htmlContable .= '
    <tr>
        <td colspan="3" class="text-right" style="font-weight:bold;">TOTALES</td>
        <td class="text-right" style="font-weight:bold;">' . number_format($totalDebito, 2, ',', '.') . '</td>
        <td class="text-right" style="font-weight:bold;">' . number_format($totalCredito, 2, ',', '.') . '</td>
    </tr>
  </tbody>
</table>
';

// Escribir en el PDF
$pdf->writeHTML($htmlContable, true, false, false, false, '');

$tblTot = '<style>
    table.first {
        width: 100%;
        color: #003300;
        font-family: Helvetica, Arial, sans-serif;
        font-size: 8pt;
        border-collapse: collapse;
        border: 1px solid #DADADA;
    }

    table.first th,
    table.first td {
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
            <td class="value">' . $dataCli['notas'] . '</td>
        </tr>
        <tr>
            <th class="label">Elaborado por:</th>
            <td class="value">
                ' . $dataCli['usuario_crea'] . ' - ' . $dataCli['usuario_nombre'] . '
            </td>
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
