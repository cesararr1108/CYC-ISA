<?php
session_start();
require_once '../../assets/tcpdf/tcpdf.php';
require_once( '../models/bd.php' );
require_once( '../models/funciones.php' );

class MYPDF extends TCPDF {
  public function Header() {}


  public function Footer() {
    // Posiciona a 15 mm desde el final
    $this->SetY( -20 );
    // Fuente pequeña
    $this->SetFont( 'helvetica', 'I', 8 );
    // Información de la empresa
    $desarrolladorInfo = "Desarrollado por CYC Technology - Todos los derechos reservados - Medellín, Antioquia";
    // Línea 1: desarrollado por...
    $this->Cell( 0, 5, $desarrolladorInfo, 0, 1, 'C', 0, '', 0, false, 'T', 'M' );
    // Línea 2: número de página
    $this->Cell( 0, 5, 'Página ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C' );
  }
}

$id_doc = $_GET[ 'id' ];

$pdf = new MYPDF( 'P', 'mm', 'LETTER' );
$pdf->Open();
$pdf->AddPage();
// set auto page breaks
$pdf->SetAutoPageBreak( TRUE, PDF_MARGIN_BOTTOM );


// Obtener datos
$empresa = GenerarArray( "select 
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
                    inner join t_municipios m on m.codigo_pais = t.codigo_pais and m.codigo_dpto  = t.codigo_dpto and m.codigo =t.codigo_mpio" );

// Validar resultado
if ( !empty( $empresa ) ) {
  $d = $empresa[ 0 ]; // solo una fila de empresa
  $logo = !empty( $d[ 'logo' ] ) ? $d[ 'logo' ] : 'default_logo.png';
  $dataEnc = [
    'nit' => number_format( $d[ 'nit' ], 0, '', '.' ),
    'nit_digito' => $d[ 'nit_digito' ],
    'razon_comercial' => $d[ 'razon_comercial' ],
    'direccion' => $d[ 'direccion' ],
    'nombre_mpio' => $d[ 'nombre_mpio' ],
    'nombre_dpto' => $d[ 'nombre_dpto' ],
    'telefono' => trim( $d[ 'telefono' ] ),
    'correo' => $d[ 'correo' ],
    'logo' => $logo
  ];
} else {
  $dataEnc = []; // fallback si no hay datos
}
$transportador = GenerarArray( " select 
                                    d.id as id_planilla,
                                    u.id as id_usuario,
                                    u.usuario,
                                    t.nombres ,
                                    t.razon_social,
                                    t.placas ,
                                    t.nit,
                                    t.celular ,
                                    t.vehiculo ,
	                                d.fecha_hora 
                                from t_registro_despacho d
                                inner join t_usuarios u on u.id  = d.usuario_id 
                                inner join t_transportadores t on t.id = d.transportador_id 
                                where 
                                  d.id = $id_doc" );

if ( !empty( $transportador ) ) {
  $d = $transportador[ 0 ];
  $dataTrans = [
    'id_planilla' => $d[ 'id_planilla' ],
    'id_usuario' => $d[ 'id_usuario' ],
    'usuario' => $d[ 'usuario' ],
    'nombres' => $d[ 'nombres' ],
    'nit' => $d['nit'],
    'razon_social' => $d[ 'razon_social' ],
    'placas' => $d[ 'placas' ],
    'celular' => $d[ 'celular' ],
    'vehiculo' => $d[ 'vehiculo' ],
    'fecha_hora' => $d[ 'fecha_hora' ]
  ];
} else {
  $dataTrans = []; // fallback si no hay datos
}
// Ruta del logo
$logoPath = '../../assets/img/logo/' . $dataEnc[ 'logo' ];
$logoHtml = $pdf->Image( $logoPath, '', '', 20 );
$fechaGeneracion = date( 'Y-m-d H:i:s' );
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
                   ' . $logoHtml . '
                </td>
                <td style="width: 50%; text-align: center;">
                    <strong>' . $dataEnc[ 'razon_comercial' ] . '</strong><br>
                    Nit: ' . $dataEnc[ 'nit' ] . ' - ' . $dataEnc[ 'nit_digito' ] . '<br>
                    Dirección: ' . $dataEnc[ 'direccion' ] . '<br>
                    Ciudad: ' . $dataEnc[ 'nombre_mpio' ] . ' - ' . $dataEnc[ 'nombre_dpto' ] . ' <br>
                    Email: ' . $dataEnc[ 'correo' ] . ' <br>
                    Teléfono: ' . $dataEnc[ 'telefono' ] . '
                    
                </td>
                <td style="width: 30%; text-align: center;">
                &nbsp;<br>
                    <strong>PLANILLA DE DESPACHO</strong><br>
                    <strong>'.$dataTrans['id_planilla'].'</strong><br>
                    <strong>USUARIO</strong><br>
                     '.$dataTrans['usuario'].'
                </td>
            </tr>
        </table>';
$pdf->writeHTML( $tbl, true, false, false, false, '' );
$fechaGeneracion = date( 'Y-m-d H:i:s' );
$tblTrans = '<style>
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
                <td colspan="4" align="center" style="background-color: #ECECEC;"><b>DATOS DEL TRANSPORTADOR / MENSAJERO</b></td>
              </tr>
              <tr>
                <td style="background-color: #ECECEC;"><b>NOMBRES</b></td>
                <td colspan="3" >'.$dataTrans['nombres'].'</td>
              </tr>
              <tr>
                <td style="background-color: #ECECEC;"><b>RAZÓN COMERCIAL</b></td>
                <td colspan="3" >'.$dataTrans['razon_social'].'</td>
              </tr>
              <tr>
                <td style="background-color: #ECECEC;"><b>NIT</b></td>
                <td>'.$dataTrans['nit'].'</td>
                <td style="background-color: #ECECEC;"><b>TELÉFONO</b></td>
                <td>'.$dataTrans['celular'].'</td>
              </tr>
              <tr>
                <td style="background-color: #ECECEC;"><b>VEHICULO</b></td>
                <td>'.$dataTrans['vehiculo'].'</td>
                <td style="background-color: #ECECEC;"><b>PLACA</b></td>
                <td>'.$dataTrans['placas'].'</td>
              </tr>
              <tr>
                <td style="background-color: #ECECEC;"><b>FECHA CREACIÓN</b></td>
                <td>'.$dataTrans['fecha_hora'].'</td>
                <td style="background-color: #ECECEC;"><b>FECHA IMPRESIÓN</b></td>
                <td>'.$fechaGeneracion.'</td>
              </tr>
            </table>';
$pdf->writeHTML( $tblTrans, true, false, false, false, '' );

// --Detalle de planilla
$detalles = GenerarArray("
    SELECT 
        d.tipo_fac,
        d.numero_fac,
        d.estado,
        d.cajas,
        d.flete,
        d.nota,
        d.guia,
        s.tratamiento,
        s.razon_comercial,
        r.direccion,
        m.departamento,
        c.municipio ,
	    r.telefono 
    FROM t_registro_despacho_det d
    INNER JOIN t_transacciones t 
        ON t.tipo = d.tipo_fac AND t.numero = d.numero_fac 
    INNER JOIN t_terceros_direcciones r 
        ON r.id = t.id_tercero_dir 
    INNER JOIN t_terceros s 
        ON s.id = t.id_tercero 
    INNER JOIN t_departamentos m 
        ON m.codigo_dpto = r.codigo_dpto 
    INNER JOIN t_municipios c 
        ON c.codigo_dpto = r.codigo_dpto AND c.codigo = r.codigo_mpio 
    WHERE d.id_reg = $id_doc
");
if (!empty($detalles)) {
    foreach ($detalles as $row) {
        $cliente   = trim($row['tratamiento'].' - '.$row['razon_comercial']);
        $ciudad    = $row['municipio'].' - '.$row['departamento'];
        $estado    = ($row['estado'] == 'D') ? 'Despachado' : (($row['estado'] == 'E') ? 'Entregado' : $row['estado']);
        $valor     = number_format($row['flete'], 0, ',', '.'); 
        $telefono  = isset($row['telefono']) ? $row['telefono'] : '';
        $direccion = isset($row['direccion']) ? $row['direccion'] : '';
        $documento = $row['tipo_fac'].' - '.$row['numero_fac'];
        $guia = isset($row['guia']) ? $row['guia'] : '';
        $nota = isset($row['nota']) ? $row['nota'] : '';
        $cajas = isset($row['cajas']) ? $row['cajas'] : '';
        $bloque = '
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
                <td colspan="4" align="justify" style="background-color: #ECECEC;"><b> '.$cliente.'</b></td>
             </tr>
             <tr>
               <td align="justify" style="background-color: #ECECEC;"><b>DOCUMENTO</b></td>
               <td colspan="3">'.$documento.'</td>
             </tr>
             <tr>
               <td align="justify" style="background-color: #ECECEC;"><b>DIRECCIÓN</b></td>
               <td colspan="3">'.$direccion.' '. $ciudad.'</td>
             </tr>
             <tr>
               <td align="justify" style="background-color: #ECECEC;"><b>TELÉFONO</b></td>
               <td colspan="3">'.$telefono.'</td>
             </tr>
             <tr>
               <td align="justify" style="background-color: #ECECEC;"><b>CAJAS</b></td>
               <td colspan="3">'.$cajas.'</td>
             </tr>
             <tr>
               <td align="justify" style="background-color: #ECECEC;"><b>FLETE</b></td>
               <td colspan="3">'.$valor.'</td>
             </tr>
             <tr>
               <td align="justify" style="background-color: #ECECEC;"><b>GUIA</b></td>
               <td colspan="3">'.$guia.'</td>
             </tr>
             <tr>
               <td align="justify" style="background-color: #ECECEC;"><b>NOTAS</b></td>
               <td colspan="3">'.$nota.'</td>
             </tr>
             <tr>
              <td colspan="4"style="height:20px; vertical-align:bottom; text-align:center; border-top:1px solid #000;">
                  <br><br>_______________________________<br>
                  <b>Firma / sello recibido</b>
              </td>
            </tr>

        </table>
        ';
        
        $pdf->writeHTML($bloque, true, false, false, false, '');
    }
} else {
    $pdf->writeHTML('<br><b>No se encontraron facturas asociadas a esta planilla.</b>', true, false, false, false, '');
}


$pdf->Output( 'rpt.pdf' );
?>