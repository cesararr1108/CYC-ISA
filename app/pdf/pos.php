<?php
session_start();
require_once '../../assets/tcpdf/tcpdf.php';
require_once( '../models/bd.php' );
require_once( '../models/funciones.php' );

$id_doc = $_GET["id"];

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

  $emp_razon ='';
  $emp_nit = '';
  $emp_nit_digito = '';
  $emp_direccion ='';
  $emp_ciudad = '';
  $emp_dpto ='';
  $emp_mail ='';
  $emp_telefono = '';
  $emp_logo = '';


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
                        d.notas,
                        d.id as id_transaccion,
                        d.valor_total,
                        d.id as numero,
                        UPPER(u.usuario) as usuario,
                        UPPER(CONCAT(u.nombres,' ',u.apellidos)) as nombres_usuario, 
                        d.fecha_hora,
                        'PED' as tipo,
                        'PEDIDO DE VENTA' as descripcion,
                        p.descripcion as nombre_pais,
                        s.departamento  as nombre_dpto,
                        m.municipio  as nombre_mpio,
                        upper(r.direccion) as direccion,
                        r.telefono,
                        a.descripcion as desc_bodega,
                        cp.descripcion as condicion
                    from t_transacciones d
                    inner join t_terceros           t on t.id = d.id_tercero 
                    inner join t_terceros_direcciones r on r.id = d.id_tercero_dir
                    inner join t_usuarios           u on u.id=d.id_usuario
                    inner join t_paises             p on p.pais = r.codigo_pais 
                    inner join t_departamentos s on s.codigo_dpto = r.codigo_dpto  and s.codigo_pais  = r.codigo_pais 
                    inner join t_municipios m on m.codigo_pais = r.codigo_pais and m.codigo_dpto  = r.codigo_dpto and m.codigo =r.codigo_mpio
                    inner join t_almacenes a on a.id  = d.id_bodega 
                    left  join t_condiciones_pago   cp on cp.id=d.id_condicion 
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
  $condicion = $d['condicion'];
}

// Paso 1: Crear un PDF con página muy larga temporalmente para calcular altura real
$temp_pdf = new TCPDF('P', 'mm', array(80, 227), true, 'UTF-8', false); // alto grande
$logoPath = '../../assets/img/logo/'.$emp_logo;
$logoHtml = $temp_pdf->Image( $logoPath, '', '', 20 );

$html=<<<EOD
        <style>
          .encabezados_emp{
               font-size:8px;
            }
        </style>
        <table style="width:100%;" class="first" border="0" cellpading="0" cellspacing="0">
           <tr><td class="encabezados_emp"><strong>$emp_razon</strong></td></tr>
            <tr><td class="encabezados_emp">$emp_nit-$emp_nit_digito</td></tr>
            <tr><td class="encabezados_emp">$emp_dpto-$emp_ciudad</td></tr>
            <tr><td class="encabezados_emp">$emp_telefono</td></tr>
            <tr><td class="encabezados_emp"><small>$emp_mail</small></td></tr>
        </table>
EOD;
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
                     cast(t.pcj_iva as decimal(19,0)) as iva,
                     t.pcj_descuento as dto,
                     t.und,
                     case when t.can_und=r.cant_1 then 1 
                          when t.can_und=r.cant_2 then 2
                          when t.can_und=r.cant_3 then 3
                          when t.can_und=r.cant_4 then 4
                     end  as und_compra,
                     t.can_und
                    from t_transacciones_detalle t
                    inner join t_referencias r on r.id=t.id_referencia 
                    where 
                     t.id_transaccion = $id_doc
                    order by 
                     r.descripcion " );
$total = 0;
$valor_iva = 0;
$unds = 0;
$items = 0;


$html.='
<table border="1" width="100%">
    <tr>
      <td>Codigo</td>
      <td>Producto</td>
      <td>Precio</td>
      <td>Iva</td>
      <td>Cantidad</td>
      <td>Total</td>
    </tr>
';

foreach ( $q as $d ) {
  $cantidad = $d[ 'cantidad' ] / $d[ 'can_und' ];
  $precio = ( $d[ 'valor_unitario' ] * $d[ 'can_und' ] );    
  $precio_con_descuento = ($precio) * (1 - ($d[ 'dto' ] / 100));       
  $precio_neto = ( $precio_con_descuento * ( ( $d[ 'iva' ] / 100 ) + 1 ) );
  $precio_total = ( $precio_neto * $cantidad );
  $total += $precio_total;
  $valor_iva += $precio * ( $d[ 'iva' ] / 100 );
  $items++;
  $unds += $cantidad;
  $html .= '<tr>
            <td align="center">' . $d[ 'codigo' ] . '</td>
            <td >' . $d[ 'descripcion' ] . '</td>
            <td  align="right">' . number_format( $precio, 0 ) . '</td>
            
            <td align="center">' . $d[ 'iva' ] . '</td>   
            <td align="center">' . number_format( $cantidad ) . '</td>         
            <td align="right">' . number_format( $precio_total, 0 ) . '</td>
          </tr>';
}
$html.='</table>';

$temp_pdf->SetMargins(1, 1, 1);
$temp_pdf->SetAutoPageBreak(false, 0); // ⚠️ Desactivar salto automático
$temp_pdf->AddPage();
$temp_pdf->SetFont('helvetica', '', 9);
$temp_pdf->writeHTML($html, true, false, true, false, '');
$altura_usada = $temp_pdf->GetY() + 5; // altura real + margen



// Paso 2: Crear el PDF definitivo con altura exacta
$pdf = new TCPDF('P', 'mm', array(80, $altura_usada), true, 'UTF-8', false);
$pdf->SetMargins(2, 2, 2);
$pdf->SetAutoPageBreak(false, 0); // ⚠️ Muy importante
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 9);
$pdf->writeHTML($html, true, false, true, false, '');

// Mostrar en navegador
$pdf->Output('tirilla.pdf', 'I');
