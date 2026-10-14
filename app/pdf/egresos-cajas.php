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
$halfLetter = [220.7, 139.9];        // ancho, alto
$pdf = new MYPDF('P', 'mm', $halfLetter);
/* CON TCPDF 6 ya no es necesario llamar a ->Open() */
$pdf->SetMargins(10, 10, 10);        // opcional, ajusta márgenes
$pdf->SetAutoPageBreak(true, 10);
$pdf->AddPage();
// set auto page breaks
$pdf->SetAutoPageBreak( TRUE, PDF_MARGIN_BOTTOM );

$e = GenerarArray( "select 
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
foreach ( $e as $d ) {
  $emp_razon = $d[ 'razon_comercial' ];
  $emp_nit = number_format( $d[ 'nit' ], 0, '', '.' );
  $emp_nit_digito = $d[ 'nit_digito' ];
  $emp_direccion = $d[ 'direccion' ];
  $emp_ciudad = $d[ 'nombre_mpio' ];
  $emp_dpto = $d[ 'nombre_dpto' ];
  $emp_mail = $d[ 'correo' ];
  $emp_telefono = $d[ 'telefono' ];
  $emp_logo = $d[ 'logo' ];
}

$t = GenerarArray( " select
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
                        a.descripcion as desc_bodega
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
                        d.id = $id_doc" );

foreach ( $t as $d ) {
  $ter_nombres = $d[ 'nombres' ];
  $ter_razon = $d[ 'razon_comercial' ];
  $ter_nit = number_format( $d[ 'nit' ], 0, '', '.' );
  $ter_nit_digito = $d[ 'nit_digito' ];
  $ter_direccion = $d[ 'direccion' ];
  $ter_ciudad = $d[ 'nombre_mpio' ];
  $ter_dpto = $d[ 'nombre_dpto' ];
  $ter_telefono = $d[ 'telefono' ];
  $tipo_doc = $d[ 'tipo' ];
  $tipo_desc_doc = $d[ 'descripcion' ];
  $numero_doc = $d[ 'numero' ];
  $fecha_hora = $d[ 'fecha_hora' ];
  $usuario_crea = $d[ 'usuario' ];
  $nombres_usuario = $d[ 'nombres_usuario' ];
  $id_bodega = $d[ 'id_bodega' ];
  $desc_bodega = $d[ 'desc_bodega' ];
}
// Ruta del logo
$logoPath = '../../assets/img/logo/'.$emp_logo;
$logoHtml = $pdf->Image( $logoPath, '', '', 20 );
$fechaGeneracion = date( 'Y-m-d H:i:s' );
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
                    $logoHtml
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
                </td>
            </tr>
        </table>
EOD;
$pdf->writeHTML( $tbl, true, false, false, false, '' );

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
$pdf->writeHTML( $tblTer, true, false, false, false, '' );

$tblDet = <<<EOD
    <table class="first">
        <tr>
            <td>NOTAS</td>
            <td></td>
        </tr>
        <tr>
            <td>VALOR</td>
            <td>
                <div ></div>
            </td>
        </tr>
        <tr>
            <td>VALOR EN LETRAS</td>
            <td></td>
        </tr>
        <tr>
            <td></td>
            <td></td>
        </tr>
    </table>
EOD;
$pdf->writeHTML( $tblDet, true, false, false, false, '' );
$pdf->Output( 'Recibo de cajas n° '.$id_doc.'.pdf' );