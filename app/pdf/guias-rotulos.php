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

$e = GenerarArray(" SELECT   
                        t.razon_comercial,
                        t.representante_legal,
                        t.nit,
                        t.nit_digito,
                        t.correo,
                        t.telefono,
                        t.logo,
                        t.direccion,
                        p.descripcion  AS nombre_pais,
                        d.departamento AS nombre_dpto,
                        m.municipio    AS nombre_mpio                    
                     FROM t_empresa t
                     INNER JOIN t_paises p 
                        ON p.pais = t.codigo_pais 
                     INNER JOIN t_departamentos d 
                        ON d.codigo_dpto = t.codigo_dpto  
                        AND d.codigo_pais = t.codigo_pais 
                     INNER JOIN t_municipios m 
                        ON m.codigo_pais = t.codigo_pais 
                        AND m.codigo_dpto = t.codigo_dpto 
                        AND m.codigo = t.codigo_mpio
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
/*
 0 : Guia carta 
 1 : Rotulo carta
 2 : Rotulo sticker
*/
$tipo = $_GET['tipo'];
$tipoFact = $_GET['tipoFact'];
$numFact = $_GET['numFact'];
$cajas = $_GET['cajas'];
$notas = $_GET['notas'];
$html  = '';
$logoPath = realpath('../../../cyc/assets/img/logo/' . $dataEmp['logo']);
$delicado = realpath('../../../cyc/assets/img/delicado.jpg');
$fragil = realpath('../../../cyc/assets/img/fragil.png');

$datos = GenerarArray("SELECT
                        t.nit,
                        CONCAT(t.tipo, t.numero) AS factura,
                        tt.tratamiento,
                        tt.razon_comercial,
                        d.direccion,
                        m.municipio AS ciudad,
                        p.departamento,
                        t.fecha,
	                    TIME(t.fecha_hora) AS hora,
	                    d.telefono 
                    FROM t_transacciones AS t
                    INNER JOIN t_terceros AS tt
                        ON tt.id = t.id_tercero
                    INNER JOIN t_terceros_direcciones AS d
                        ON d.id = t.id_tercero_dir
                    INNER JOIN t_departamentos AS p
                        ON p.codigo_dpto = d.codigo_dpto
                    INNER JOIN t_municipios AS m
                        ON m.codigo_dpto = d.codigo_dpto
                    AND m.codigo = d.codigo_mpio
                    WHERE t.tipo = '" . $tipoFact . "'
                    AND t.numero = " . $numFact . ";");
$data  = reset($datos);
$dataFact = [
    'nit' => $data['nit'] ?? '',
    'factura' => $data['factura'] ?? '',
    'tratamiento' => $data['tratamiento'] ?? '',
    'razon_comercial' => $data['razon_comercial'] ?? '',
    'direccion' => $data['direccion'] ?? '',
    'ciudad' => $data['ciudad'] ?? '',
    'departamento' => $data['departamento'] ?? '',
    'fecha' => $data['fecha'] ?? '',
    'hora' => $data['hora'] ?? '',
    'telefono' => $data['telefono'] ?? ''
];
switch ($tipo) {
    case 0:
        $html = '<!doctype html>
                    <html>
                    <head>
                    <meta charset="utf-8">
                    <title>Documento sin título</title>
                    </head>
                    <body>
                    <style>
                    body {
                        font-family: helvetica;
                        color:#003300;
                    }


                    /* =========================
                    ENCABEZADO
                    ========================= */

                    .header{
                        width:100%;
                        border-collapse:collapse;
                        border:1px solid #DADADA;
                        color:#003300;
                        font-size:8pt;
                    }

                    .header td{
                        border:1px solid #DADADA;
                        padding:5px;
                        vertical-align:middle;
                    }

                    .logo-cell{
                        width:22%;
                        text-align:center;
                    }

                    .empresa{
                        width:78%;
                        text-align:center;
                        color:#003300;
                        font-size:9pt;
                    }

                    .titulo{
                        font-size:11pt;
                        font-weight:bold;
                    }


                    /* =========================
                    TABLAS INFORMACION
                    ========================= */

                    .datos{
                        width:100%;
                        color:#003300;
                        font-family:helvetica;
                        font-size:8pt;
                        border-collapse:collapse;
                        border:1px solid #DADADA;
                    }

                    .datos td{
                        border:1px solid #DADADA;
                        padding:5px;
                    }

                    .etiqueta{
                        width:22%;
                        font-weight:bold;
                        background-color:#F7F7F7;
                        color:#003300;
                    }

                    .valor{
                        width:78%;
                        color:#333333;
                    }

                    .caja{
                        text-align:center;
                        font-size:10pt;
                        font-weight:bold;
                        color:#003300;
                    }


                    .tituloEntrega{
                        text-align:center;
                        font-weight:bold;
                        font-size:8pt;
                        background-color:#F7F7F7;
                    }


                    /* =========================
                    TRANSPORTE
                    ========================= */

                    .transporte{
                        width:100%;
                        color:#003300;
                        font-family:helvetica;
                        font-size:7.5pt;
                        border-collapse:collapse;
                        border:1px solid #DADADA;
                    }

                    .transporte td{
                        border:1px solid #DADADA;
                        padding:4px;
                    }


                    .etiquetaTrans{
                        font-weight:bold;
                        background-color:#F7F7F7;
                        color:#003300;
                        font-size:7.5pt;
                    }

                    .valorTrans{
                        color:#333333;
                    }


                    .campo{
                        height:14px;
                        border:1px solid #DADADA;
                    }


                    .firma{
                        height:55px;
                        text-align:center;
                        vertical-align:middle;
                    }


                    .firmaCaja{
                        width:45%;
                        height:35px;
                        border:1px solid #DADADA;
                        margin:auto;
                    }


                    .nota{
                        font-size:7pt;
                        color:#333333;
                        text-align:justify;
                    }


                    </style>

            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="100%" valign="top">
                        <table class="header">
                            <tr>
                                <td class="logo-cell"><img src="' . $logoPath . '" width="60"></td>
                                <td class="empresa"><span class="titulo"> GUIA DE TRANSPORTE </span> <br>
                                <strong>' . $dataEmp['razon'] . '</strong> <br>
                                Nit: ' . $dataEmp['nit'] . ' - ' . $dataEmp['digito'] . ' <br>
                                Dirección: ' . $dataEmp['direccion'] . ' ' . $dataEmp['dpto'] . ' - ' . $dataEmp['ciudad'] . ' <br>
                                Email: ' . $dataEmp['correo'] . ' <br>
                                Teléfono: ' . $dataEmp['telefono'] . ' </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <br>
            <br>

            <table width="100%" border="0" cellpadding="0" cellspacing="0">
            <tr>
                <td width="50%" valign="top"><!-- INFORMACIÓN DEL ENVÍO -->
                
                <table class="datos">
                    <tr>
                    <td class="tituloEntrega" colspan="2"> INFORMACIÓN DEL ENVÍO </td>
                    </tr>
                    <tr>
                        <td class="etiqueta"> FACTURA </td>
                        <td class="valor"> ' . $dataFact['factura'] . ' </td>
                    </tr>
                    <tr>
                        <td class="etiqueta"> FECHA </td>
                        <td class="valor"> ' . $dataFact['fecha'] . ' </td>
                    </tr>
                    <tr>
                        <td class="etiqueta"> HORA </td>
                        <td class="valor"> ' . $dataFact['hora'] . ' </td>
                    </tr>
                    <tr>
                        <td class="etiqueta"> CAJAS </td>
                        <td class="valor"> ' . $cajas . ' </td>
                    </tr>
                </table></td>
                <td width="50%" valign="top" style="padding:0;" ><!-- INFORMACIÓN DESTINATARIO -->
                
                <table class="datos" style="padding:0;">
                    <tr>
                        <td class="tituloEntrega" colspan="2"> INFORMACIÓN DEL DESTINATARIO </td>
                    </tr>
                    <tr>
                        <td class="etiqueta"> CLIENTE </td>
                        <td class="valor"> ' . $dataFact['tratamiento'] . '</td>
                    </tr>
                    <tr>
                        <td class="etiqueta"> NEGOCIO </td>
                        <td class="valor"> ' . $dataFact['razon_comercial'] . ' </td>
                    </tr>
                    <tr>
                        <td class="etiqueta"> DIRECCIÓN </td>
                        <td class="valor"> ' . $dataFact['direccion'] . ' - ' . $dataFact['ciudad'] . ' - ' . $dataFact['departamento'] . '</td>
                    </tr>
                    <tr>
                        <td class="etiqueta"> TELÉFONO </td>
                        <td class="valor"> ' . $dataFact['telefono'] . ' </td>
                    </tr>
                </table></td>
            </tr>
            </table>
            <br>
            <br>
            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="100%" valign="top">
                        <table class="transporte">
                            <tr>
                                <td class="tituloEntrega" colspan="6"> NOTAS Y OBSERVACIONES </td>
                            </tr>
                            <tr>
                                <td class="valorTrans" colspan="6"> ' . $notas . ' </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>  
            <br>
            <br>
            <table width="100%" border="0" cellpadding="0" cellspacing="0">
            <tr>
                <td width="100%" valign="top">
                    <table class="transporte">
                        <tr>
                            <td class="tituloEntrega" colspan="6"> INFORMACIÓN DE ENTREGA Y TRANSPORTE </td>
                        </tr>
                        <tr>
                            <td class="etiquetaTrans"> TRANSPORTADOR </td>
                            <td class="valorTrans"></td>
                            <td class="etiquetaTrans"> C.C. </td>
                            <td class="valorTrans"></td>
                            <td class="etiquetaTrans"> TELÉFONO </td>
                            <td class="valorTrans"></td>
                        </tr>
                        <tr>
                            <td class="etiquetaTrans"> VEHÍCULO </td>
                            <td class="valorTrans"></td>
                            <td class="etiquetaTrans"> COLOR </td>
                            <td class="valorTrans"></td>
                            <td class="etiquetaTrans"> PLACAS </td>
                            <td class="valorTrans"></td>
                        </tr>
                        <tr>
                            <td class="etiquetaTrans"> N° CAJAS </td>
                            <td class="valorTrans">  </td>
                            <td class="etiquetaTrans"> N° PAQUETES </td>
                            <td class="valorTrans"></td>
                            <td class="etiquetaTrans"> VALOR FLETE </td>
                            <td class="valorTrans"></td>
                        </tr>
                        <tr>
                            <td colspan="6" style="height:55px; vertical-align:bottom; text-align:center;"><br><br>
                                ____________________________________________

                                <br>

                                <b>Firma, C.C. y Sello</b>

                            </td>
                        </tr>

                        <tr>
                            <td class="nota" colspan="6" align="center">
                                 Señor cliente Al recibir la mercancía, revísela en presencia del
                                transportador.<br>Si observa roturas, magulladuras o cualquier daño,
                                registre las observaciones en la guía antes de firmar.
                            </td>
                        </tr>

                    </table>
                </td>
            </tr>
            </table>
            </body>
            </html>';
        $pdf = new MYPDF('P', 'mm', 'LETTER');
        $pdf->Open();
        $pdf->AddPage();
        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('rpt.pdf');

        break;
    case 1:
        $pdf = new MYPDF('P', 'mm', 'LETTER');
        $pdf->Open();

        $altoRotulo = 140; // Ajusta este valor hasta que quepan dos

        for ($cont = 1; $cont <= $cajas; $cont++) {

            // Cada dos rótulos crea una página nueva
            if (($cont - 1) % 2 == 0) {
                $pdf->AddPage('P', 'LETTER');
                $y = 8;
            } else {
                $y = $altoRotulo + 8;
            }

            // Aquí construyes tu $html usando $cont
            $html = '<style>
                      .datos{
                        width:100%;
                        color:#003300;
                        font-family:helvetica;
                        font-size:8pt;
                        border-collapse:collapse;
                        border:1px solid #DADADA;
                    }
                 </style>
                 <table border="0" cellpadding="2" cellspacing="0" nobr="true" width="560" >
                    <tr> 
                        
                        <!-- COLUMNA IZQUIERDA -->
                        <td width="450" valign="top"><!-- LOGO + ENCABEZADO -->
                        
                        <table border="0" width="100%" cellpadding="1" class="datos">
                            <tr>
                            <td width="20%" align="center"><img src="' . $logoPath . '" width="60"></td>
                            <td width="80%" align="center"><b style="font-size:11;"> ' . $dataEmp['razon'] . ' </b> <br>
                            <span style="font-size:8;"> Nit: ' . $dataEmp['nit'] . '-' . $dataEmp['digito'] . ' <br>
                            Dirección: ' . $dataEmp['direccion'] . ' ' . $dataEmp['dpto'] . ' - ' . $dataEmp['ciudad'] . ' <br>
                             Email: ' . $dataEmp['correo'] . ' <br>
                                Teléfono: ' . $dataEmp['telefono'] . '    
                                Tel: ' . $dataEmp['telefono'] . ' </span> <br>
                                <br>
                                <b style="font-size:9;"> PRODUCTOS FARMACEUTICOS TRATAR CON CUIDADO </b></td>
                            </tr>
                        </table>
                        <br>
                        
                        <!-- DATOS -->
                        
                       <table border="0" width="100%" cellpadding="1" class="datos">
                            <tr>
                            <td width="20%" style="font-size:8;font-weight:bold;"> FACTURA </td>
                            <td width="80%" style="font-size:8;"> ' . $dataFact['factura'] . ' </td>
                            </tr>
                            <tr>
                                <td style="font-size:8;font-weight:bold;"> FECHA </td>
                                <td style="font-size:8;"> ' . $dataFact['fecha'] . ' </td>
                            </tr>
                            <tr>
                            <td style="font-size:8;font-weight:bold;"> DESTINATARIO </td>
                            <td style="font-size:8;"> ' . $dataFact['tratamiento'] . ' </td>
                            </tr>
                            <tr>
                            <td style="font-size:8;font-weight:bold;"> NEGOCIO </td>
                            <td style="font-size:8;"> ' . $dataFact['razon_comercial'] . ' </td>
                            </tr>
                            <tr>
                            <td style="font-size:8;font-weight:bold;"> DIRECCION </td>
                            <td style="font-size:8;"> ' . $dataFact['direccion'] . ' </td>
                            </tr>
                            <tr>
                            <td style="font-size:8;font-weight:bold;"> DEPARTAMENTO </td>
                            <td style="font-size:8;"> ' . $dataFact['departamento'] . ' </td>
                            </tr>
                            <tr>
                            <td style="font-size:8;font-weight:bold;"> CIUDAD </td>
                            <td style="font-size:8;"> ' . $dataFact['ciudad'] . ' </td>
                            </tr>
                            <tr>
                            <td style="font-size:8;font-weight:bold;"> N° CAJAS </td>
                            <td style="font-size:8;"> ' . $cajas . ' </td>
                            </tr>
                            <tr>
                            <td style="font-size:8;font-weight:bold;"> CAJA </td>
                            <td style="font-size:8;"> ' . $cont . ' DE ' . $cajas . ' </td>
                            </tr>
                        </table>
                        <br>
                        
                        <!-- AVISO -->
                        
                        <table border="0" cellpadding="2" width="100%" class="datos">
                            <tr>
                            <td align="center"><b style="font-size:10;"> FLETES PAGADOS POR LA COMPAÑIA </b></td>
                            </tr>
                            <tr>
                            <td align="center"><span style="font-size:7;"> Señor cliente al recibir la mercancía por favor revísela
                                en presencia del transportador, si observa roturas,
                                magulladuras o cualquier tipo de daño escriba en la
                                guía las observaciones. </span></td>
                            </tr>
                        </table></td>
                        
                        <!-- COLUMNA DERECHA ICONOS -->
                        
                        <td width="110" valign="middle" align="center" >
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" class="datos">
                                <tr>
                                    <td align="center"><br><br><img src="' . $delicado . '" width="82"> </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    </table>
                    </body>
                    </html>';

            $pdf->writeHTMLCell(
                200,    // ancho
                0,      // alto automático
                5,      // X
                $y,     // Y
                $html,
                0,
                0,
                false,
                true,
                'L',
                true
            );
        }
        $pdf->Output('rpt.pdf');



        break;
    case 2:
        $pdf = new MYPDF('L', 'mm', array(150, 100), true, 'UTF-8', false);

        $pdf->SetMargins(3, 3, 3);
        $pdf->SetAutoPageBreak(false);

        for ($cont = 1; $cont <= $cajas; $cont++) {

            $pdf->AddPage();

            $html = '
            <style>
                .datos{
                    width:100%;
                    color:#003300;
                    font-family:helvetica;
                    font-size:8pt;
                    border-collapse:collapse;
                    border:1px solid #DADADA;
                }
                .label{
                    font-weight:bold;
                    background-color:#F7F7F7;
                    color:#003300;
                }
            </style>

            <table width="100%" cellpadding="2" cellspacing="0">
            <tr>
            <td width="78%" valign="top">
            <table class="datos">
                <tr>
                    <td width="20%" align="center">
                        <img src="' . $logoPath . '" width="45">
                    </td>
                    <td width="80%" align="center">
                        <span class="titulo">' . $dataEmp['razon'] . '</span>
                        <br>
                        Nit: ' . $dataEmp['nit'] . '-' . $dataEmp['digito'] . '
                        <br>
                        ' . $dataEmp['direccion'] . ' ' . $dataEmp['ciudad'] . '
                        <br>
                        Tel: ' . $dataEmp['telefono'] . '
                        <br><br>
                        <b>PRODUCTOS FARMACÉUTICOS</b>
                        <br>
                        TRATAR CON CUIDADO
                    </td>
                </tr>
            </table>
            <br>
            <table class="datos">
                <tr>
                    <td width="20%" class="label">FACTURA</td>
                    <td width="80%" class="valor">' . $dataFact['factura'] . '</td>
                </tr>
                <tr>
                    <td class="label">FECHA</td>
                    <td>' . $dataFact['fecha'] . '</td>
                </tr>
                <tr>
                    <td class="label">CLIENTE</td>
                    <td>' . $dataFact['tratamiento'] . '</td>
                </tr>
                <tr>
                    <td class="label">NEGOCIO</td>
                    <td>' . $dataFact['razon_comercial'] . '</td>
                </tr>
                <tr>
                    <td class="label">DIRECCIÓN</td>
                    <td>' . $dataFact['direccion'] . '</td>
                </tr>
                <tr>
                    <td class="label">CIUDAD</td>
                    <td>' . $dataFact['departamento'] . ' - ' . $dataFact['ciudad'] . '</td>
                </tr>
                <tr>
                    <td class="label">NOTAS</td>
                    <td>' . $notas . '</td>
                </tr>
                <tr>
                    <td class="label">TOTAL CAJAS</td>
                    <td>' . $cajas . '</td>
                </tr>
                <tr>
                    <td class="label">CAJA</td>
                    <td style="font-weight:bold;">' . $cont . ' DE ' . $cajas . '</td>
                </tr>
            </table>
            <br>
            <table class="datos">
                <tr>
                    <td align="center">
                    <b>FLETES PAGADOS POR LA COMPAÑÍA</b>
                    </td>
                </tr>
                <tr>
                    <td align="center" style="font-size:7pt">
                    Señor cliente al recibir la mercancía revísela en presencia del transportador.
                    Si observa daños regístrelos en la guía.
                    </td>
                </tr>
            </table>
            </td>
                <td width="22%" align="center" valign="middle">
                <img src="' . $delicado . '" width="85">
            </td>
            </tr>
            </table>';

            $pdf->writeHTML($html, true, false, true, false, '');
        }

        $pdf->Output('Rotulos_.pdf', 'I');
        break;
}
