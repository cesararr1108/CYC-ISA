<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}
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
  $logo = !empty($d['logo']) ? $d['logo'] : 'default_logo.png';
  $emp_razon = $d[ 'razon_comercial' ];
  $emp_nit = number_format( $d[ 'nit' ], 0, '', '.' );
  $emp_nit_digito = $d[ 'nit_digito' ];
  $emp_direccion = $d[ 'direccion' ];
  $emp_ciudad = $d[ 'nombre_mpio' ];
  $emp_dpto = $d[ 'nombre_dpto' ];
  $emp_mail = $d[ 'correo' ];
  $emp_telefono = $d[ 'telefono' ];
  $emp_logo = $logo;
}

$t = GenerarArray( " select
                        t.id as id_tercero,
                        t.nit,
                        t.nit_digito,
                        CONCAT(t.primer_nombre,' ',t.segundo_nombre,' ',t.primer_apellido,' ',t.segundo_apellido) as nombres,
                        t.razon_comercial,
                        d.id_bodega,
                        d.notas,
                        d.id as id_transaccion,
                        d.valor_total,
                        d.id as numero,
                        UPPER(u.usuario) as usuario,
                        UPPER(CONCAT(u.nombres,' ',u.apellidos)) as nombres_usuario, 
                        d.fecha_hora,
                        'OC' as tipo,
                        'ORDEN DE COMPRA' as descripcion,
                        p.descripcion as nombre_pais,
                        s.departamento  as nombre_dpto,
                        m.municipio  as nombre_mpio,
                        upper(r.direccion) as direccion,
                        r.telefono,
                        a.descripcion as desc_bodega
                    from t_pedidos d
                    inner join t_terceros           t on t.id = d.id_tercero 
                    inner join t_terceros_direcciones r on r.id = d.id_tercero_dir
                    inner join t_usuarios           u on u.id=d.id_usuario
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
  $ter_notas = $d[ 'notas' ];
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
$det = '';
$q = GenerarArray( "select 
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
                     t.id,
                     t.cantidad,
                     t.valor_unitario,
                     t.costo_unitario,
                     t.pcj_iva as iva,
                     t.pcj_descuento as dto,
                     t.und,
                     case when t.can_und=r.cant_1 then 1 
                          when t.can_und=r.cant_2 then 2
                          when t.can_und=r.cant_3 then 3
                          when t.can_und=r.cant_4 then 4
                     end  as und_compra,
                     t.can_und
                    from t_pedidos_detalle t
                    inner join t_referencias r on r.id=t.id_referencia 
                    where 
                     t.id_pedido = $id_doc
                    order by 
                     r.descripcion " );
$total = 0;
$valor_iva = 0;
$unds = 0;
$items = 0;
foreach ( $q as $d ) {
  $cantidad = $d[ 'cantidad' ] / $d[ 'can_und' ];
  $costo = ( $d[ 'costo_unitario' ] * $d[ 'can_und' ] );    
  $costo_con_descuento = ($costo) * (1 - ($d[ 'dto' ] / 100));       
  $costo_neto = ( $costo_con_descuento * ( ( $d[ 'iva' ] / 100 ) + 1 ) );
  $costo_total = ( $costo_neto * $cantidad );
  $total += $costo_total;
  $valor_iva += $costo * ( $d[ 'iva' ] / 100 );
  $items++;
  $unds += $cantidad;
  $det .= '<tr>
            <td width="8%" align="center">' . $d[ 'codigo' ] . '</td>
            <td width="35%">' . $d[ 'descripcion' ] . '</td>
            <td width="6%" align="center">' . $d[ 'und' ] . '</td>
            <td width="6%" align="center">' . number_format( $cantidad ) . '</td>
            <td width="11%" align="right">' . number_format( $costo, 2 ) . '</td>
            <td width="6%" align="center">' . $d[ 'iva' ] . '</td>
            <td width="6%" align="center">' . $d[ 'dto' ] . '</td>
            <td width="11%" align="right">' . number_format( $costo_neto, 2 ) . '</td>                 
            <td width="11%" align="right">' . number_format( $costo_total, 2 ) . '</td>
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
$pdf->writeHTML( $tblDet, true, false, false, false, '' );

//Totales
$subtotal = number_format( ( $total - $valor_iva ), 2 );
$total = number_format( $total, 2 );
$valor_iva = number_format( $valor_iva, 2 );

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
                  <th colspan="6" rowspan="2">$ter_notas</th>
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
$pdf->writeHTML( $tblTot, true, false, false, false, '' );
$pdf->Output( 'rpt.pdf' );
?>