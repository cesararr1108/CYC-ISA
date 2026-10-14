<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}
require_once '../../assets/tcpdf/tcpdf.php';
require_once( '../models/bd.php' );
require_once( '../models/funciones.php' );

// Configuración de la página tipo tirilla (80mm ancho)
$pdf = new TCPDF( 'P', 'mm', array( 80, 300 ), true, 'UTF-8', false );
$pdf->SetPrintHeader( false );
$pdf->SetPrintFooter( false );
$pdf->SetMargins( 2, 2, 2 ); // márgenes pequeños
$pdf->SetAutoPageBreak( true, 2 );
$pdf->AddPage();
$id_doc = $_GET[ 'id' ];
// --- Datos de empresa ---
$sqlEmpresa = " SELECT 
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
                  AND m.codigo = t.codigo_mpio
";

// Obtener datos
$empresa = GenerarArray($sqlEmpresa);

// Validar resultado
if (!empty($empresa)) {
    $d = $empresa[0]; // solo una fila de empresa
    $logo = !empty($d['logo']) ? $d['logo'] : 'default_logo.png';
    $dataEnc = [
        'nit'              => number_format($d['nit'], 0, '', '.'),
        'nit_digito'       => $d['nit_digito'],
        'razon_comercial'  => $d['razon_comercial'],
        'direccion'        => $d['direccion'],
        'nombre_mpio'      => $d['nombre_mpio'],
        'nombre_dpto'      => $d['nombre_dpto'],
        'telefono'         => trim($d['telefono']),
        'correo'           => $d['correo'],
        'logo'             => $logo
    ];
} else {
    $dataEnc = []; // fallback si no hay datos
}

//Datos de cliente

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
                        d.id = $id_doc" );
// Si $t solo trae un registro de cliente, mejor tomar directamente el primero:
$d = reset($t);

$dataCli = [
  'tipo_doc'        => $d['tipo'] ?? '',
  'numero_doc'      => $d['numero'] ?? '',
  'numero'          => number_format($d['nit'], 0, '', '.') . ' - ' . ($d['nit_digito'] ?? ''),
  'nombres'         => $d['nombres'] ?? '',
  'razon_comercial' => $d['razon_comercial'] ?? '',
  'direccion'       => $d['direccion'] ?? '',
  'nombre_dpto'     => $d['nombre_dpto'] ?? '',
  'nombre_mpio'     => $d['nombre_mpio'] ?? '',
  'telefono'        => $d['telefono'] ?? '',
  'fecha_hora'      => $d['fecha_hora'] ?? '',
  'usuario'         => $d['usuario'] ?? '',
  'nombres_usuario' => $d['nombres_usuario'] ?? '',
  'id_bodega'       => $d['id_bodega'] ?? '',
  'desc_bodega'     => $d['desc_bodega'] ?? '',
  'cufe'            => $d['cufe'] ?? '',
  'fecha_envio'     => $d['fechaEnvio'] ?? '',
  'fecha_validacion'=> $d['fechaValidacion'] ?? '',
  'vencimiento'     => $d['vencimiento'] ?? '',
  'qrcode'          => $d['QRCode'] ?? '',
  // Resolución DIAN
  'esElectronico'   => $d['documento_electronico'] ?? '',
  'consecutivo'     => ($d['rango_inicial'] ?? '') . ' a ' . ($d['rango_final'] ?? ''),
  'res_fecha'       => ($d['fecha_inicio'] ?? '') . ' a ' . ($d['fecha_fin'] ?? ''),
  'tipo_documento'  => $d['descripcion'] ?? '',
  'res_numero'      => $d['resolucion'] ?? '',
  'res_prefijo'     => $d['tipo'] ?? '',
  // Condicion de pago
  'condicion'       => $d['descripcion_condicion'] ?? '',
  'dias'            => $d['dias'] ?? '',
  // Leyendas opcionales
  'leyenda1'        => '',
  'leyenda2'        => ''
];

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


//Detalle de factura
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
                         r.descripcion" );

//totales
$config = $_SESSION[ "Ses_confSys" ][ 'confGeneral' ];
$configManejaLote = false;
$configManejaFE = false;
$espacio = '';
$textoCufe = '';
foreach ( $config as $item ) {
  // Validar lote y fecha de vencimiento
  if ( isset( $item[ 'ManejaFechaLote' ] ) && $item[ 'ManejaFechaLote' ] === 'S' ) {
    $configManejaLote = true;
  }
  // Validar facturación electronica
  if ( isset( $item[ 'ManejaFe' ] ) && $item[ 'ManejaFe' ] === 'S' ) {
    $configManejaFE = true;
    $espacio = '&nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;<br> &nbsp;<br>&nbsp;<br>&nbsp;';
    $textoCufe = '<tr style=" text-align: left; font-size:8px">
                   <td colspan="3">Fecha validación :' . $dataCli['fecha_validacion'] . ' <br>Cufe:' . $dataCli['cufe'] . '</td>
                  </tr>';
  }
}
$items = 0;
$unds = 0;
$vlr_subtotal = 0;
$vlr_total_compra = 0;
$vlr_total_dto = 0;
$vlr_total_ibua = 0;
$vlr_total_icui = 0;
$vlr_total_impoconsumo = 0;
$vlr_total_iva = 0;

$detalle = [];

foreach ($q as $d) {
    $cantidad       = (float) $d['cantidad'];
    $costo_unit     = (float) $d['valor_unitario'];
    $dto_unit       = $costo_unit * ((float) $d['dto'] / 100);
    $impoconsumo    = (float) $d['impoconsumo'];
    $ibua_unit      = (float) $d['ibua'];
    $icui_unit      = ($costo_unit - $dto_unit) * ((float) $d['icui'] / 100);
    $iva_unit       = ($costo_unit - $dto_unit) * ((float) $d['iva'] / 100);

    // Costo neto del producto (unitario)
    $costo_neto     = $costo_unit - $dto_unit + $impoconsumo + $icui_unit + $ibua_unit + $iva_unit;
    // Costo total por cantidad
    $costo_total    = $costo_neto * $cantidad;

    // Acumuladores generales
    $vlr_subtotal          += $costo_unit * $cantidad;
    $vlr_total_compra      += $costo_total;
    $vlr_total_dto         += $dto_unit * $cantidad;
    $vlr_total_ibua        += $ibua_unit * $cantidad;
    $vlr_total_icui        += $icui_unit * $cantidad;
    $vlr_total_iva         += $iva_unit * $cantidad;
    $vlr_total_impoconsumo += $impoconsumo * $cantidad;

    $items++;
    $unds += $cantidad;

    // Descripción del producto
    $descripcion = trim($d['descripcion']);
    /*
    if ($configManejaLote) {
        $descripcion .= '<br><b>Lote:</b> ' . $d['lote'] . 
                        '<br><b>Vencimiento:</b> ' . $d['vencimiento'];
    }
    */

    // Detalle del producto en estructura clara
    $detalle[] = [
        'codigo'     => $d['codigo'],
        'producto'   => $descripcion,
        'cantidad'   => $cantidad,
        'precio'     => $costo_unit,
        'descuento'  => $dto_unit,
        'pcj_iva'    => $d['iva'] ,
        'pcj_dto'    => $d['dto'] ,
        'total'      => $costo_total
    ];
}


//Totales
$vlr_subtotal = number_format( ( $vlr_subtotal ), 0 );
$vlr_total_dto = number_format( ( $vlr_total_dto ), 0 );
$vlr_total_impoconsumo = number_format( ( $vlr_total_impoconsumo ), 0 );
$vlr_total_compra = number_format( $vlr_total_compra, 0 );
$vlr_total_iva = number_format( $vlr_total_iva, 0 );
$vlr_total_saludables = number_format( $vlr_total_ibua + $vlr_total_icui, 0 );

// Logo
$pdf->Image( '../../assets/img/logo/'.$dataEnc['logo'], // ruta
  28, // X en mm desde el borde izquierdo
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
$pdf->Ln( 35 );

$html = '
<style>
  .encabezado, .cliente {
    width: 100%;
    font-size: 9px;
    text-align: center;
  }
  .encabezado td, .cliente td {
    padding: 2px;
  }
  .cliente {
    margin-top: 4px;
    text-align: left;
  }
  .linea {
 
    margin: 4px 0;
  }
</style>

<!-- Encabezado Empresa -->
<table class="encabezado">
  <tr><td><strong>' . $dataEnc[ 'razon_comercial' ] . '</strong></td></tr>
  <tr><td>' . $dataEnc[ 'nit' ] . '-' . $dataEnc[ 'nit_digito' ] . '</td></tr>
  <tr><td>' . $dataEnc[ 'direccion' ] . '</td></tr>
  <tr><td>Tel: ' . $dataEnc[ 'telefono' ] . '</td></tr>
  <tr><td>' . $dataEnc[ 'nombre_mpio' ] . ' - ' . $dataEnc[ 'nombre_dpto' ] . '</td></tr>
  <tr><td>' . $dataEnc[ 'correo' ] . '</td></tr>
</table>

<div class="linea"></div>

<!-- Datos documento -->
<table class="encabezado">
  <tr><td><strong>' . $dataCli[ 'tipo_documento' ] . '</strong></td></tr>
  <tr><td>Nro Doc.: ' . $dataCli[ 'tipo_doc' ] . ' - ' . $dataCli[ 'numero_doc' ] . '</td></tr>
  <tr><td>Consecutivo: ' . $dataCli[ 'consecutivo' ] . '</td></tr>
  <tr><td>Fecha: ' . $dataCli[ 'res_fecha' ] . '</td></tr>
</table>

<div class="linea"></div>

<!-- Datos cliente -->
<table class="cliente">
  <tr><td>Documento: ' . $dataCli[ 'numero' ] . '</td></tr>
  <tr><td>Cliente: ' . ( $dataCli[ 'nombres' ] ? : $dataCli[ 'razon_comercial' ] ) . '</td></tr>
  <tr><td>Dirección: ' . $dataCli[ 'direccion' ] . ' ' . $dataCli[ 'nombre_dpto' ] . ' ' . $dataCli[ 'nombre_mpio' ] . '</td></tr>
  <tr><td>Fecha: ' . $dataCli[ 'fecha_hora' ] . '</td></tr>
</table>

<div class="linea"></div>
';

// Pintamos en el PDF
$pdf->writeHTML( $html, true, false, false, false, '' );



// Tabla detalle
// Estilos para la tabla POS
$html = '
<style>
  table {
    border-collapse: collapse;
    width: 100%;
    font-size: 8px;
  }
  th, td {
    padding: 2px;
    text-align: left;
  }
  th {
    font-weight: bold;
    text-align: center;
  }
  td.num {
    text-align: right;
  }
</style>

<table border="0">
  <tr>
    <th align="center" style="width:13%;"><b>COD</b></th>
    <th align="center" style="width:20%;"><b>VALOR</b></th>
    <th align="center" style="width:14%;"><b>IVA</b></th>
    <th align="center" style="width:14%;"><b>DCTO</b></th>
    <th align="center" style="width:14%;"><b>CANT</b></th>
    <th align="center" style="width:30%;"><b>TOTAL</b></th>
  </tr>';

foreach ( $detalle as $item ) {
  $html .= '
    <tr>
     <td colspan="6">' . $item[ 'producto' ] . '</td>
    </tr>
    <tr>
      <td>' . $item[ 'codigo' ] . '</td>
      <td class="num">' . number_format( $item[ 'precio' ], 0, ',', '.' ) . '</td>
      <td class="num">' . number_format( $item[ 'pcj_iva' ], 0, ',', '.' ) . '</td>
      <td class="num">' . number_format( $item[ 'pcj_dto' ], 0, ',', '.' ) . '</td>
      <td class="num">' . number_format( $item[ 'cantidad' ], 0, ',', '.' ) . '</td>
      <td align="justify" class="num">' . number_format( $item[ 'total' ], 0, ',', '.' ) . '</td>
    </tr>
    <tr>
     <td colspan="6">&nbsp;</td>
    </tr>';
}

$html .= '</table>';

// Pintar tabla en el PDF
$pdf->writeHTML( $html, true, false, false, false, '' );


$pdf->Ln( 1 );
$htmlTotales = '
<style>
  .totales {
    width: 100%;
    font-size: 8px;
    margin-top: 6px;
  }
  .totales td {
    padding: 2px 0;
  }
  .totales .label {
    text-align: left;
    width: 60%;
  }
  .totales .valor {
    text-align: right;
    width: 40%;
  }
  .totales .resaltado {
    font-weight: bold;
    border-top: 1px solid #000;
    padding-top: 2px;
  }
</style>

<table class="totales">
  <tr>
    <td class="label">Subtotal:</td>
    <td class="valor">$ ' . $vlr_subtotal . '</td>
  </tr>
  <tr>
    <td class="label">Descuento:</td>
    <td class="valor">- $ ' . $vlr_total_dto . '</td>
  </tr>
  <tr>
    <td class="label">IVA:</td>
    <td class="valor">$ ' . $vlr_total_iva . '</td>
  </tr>
  <tr>
    <td class="label">Impoconsumo:</td>
    <td class="valor">$ ' . $vlr_total_impoconsumo . '</td>
  </tr>
  <tr>
    <td class="label">Productos saludables:</td>
    <td class="valor">$ ' . $vlr_total_saludables . '</td>
  </tr>
  <tr>
    <td class="label resaltado">TOTAL A PAGAR:</td>
    <td class="valor resaltado">$ ' . $vlr_total_compra . '</td>
  </tr>
</table>
';

$pdf->writeHTML( $htmlTotales, true, false, false, false, '' );
$htmlCondicion = '
<style>
  .condicion td {
    padding: 2px;
  }
  .condicion {
    margin-top: 2px;
    text-align: left;
    width: 100%;
    font-size: 9px;
    text-align: justify;
  }
</style>
<table class="condicion">
  <tr><td><b>Forma de pago:</b> ' . $dataCli[ 'condicion' ] . ' <b>Número de Días:</b> '.  $dataCli[ 'dias' ] .'</td></tr>
</table>
';
$pdf->writeHTML( $htmlCondicion, true, false, false, false, '' );
$htmlMedios = '
<style>
  .encabezado, .cliente {
    width: 100%;
    font-size: 8px;
    text-align: center;
  }
  .encabezado td, .cliente td {
    padding: 2px;
  }
  .cliente {
    margin-top: 4px;
    text-align: left;
  }
  .linea {
    margin: 4px 0;
  }
  .tabla-pagos {
    width: 100%;
    font-size: 9px;
    margin-top: 4px;
    border-collapse: collapse;
    text-align: left;
  }
  .tabla-pagos th, .tabla-pagos td {
    padding: 2px;
  }
  .tabla-pagos th {
    border-bottom: 1px solid #000;
  }
  .tabla-pagos tfoot td {
    border-top: 1px solid #000;
    font-weight: bold;
  }
</style>
<table class="tabla-pagos">
  <thead>
    <tr>
      <th style="text-align:left;">Medio de pago</th>
      <th style="text-align:right;">Valor</th>
      <th style="text-align:right;">Cambio</th>
    </tr>
  </thead>
  <tbody>';
    
$totalPagado = 0;
foreach ($medios_pago as $mp) {
    $htmlMedios .= '
    <tr>
      <td>' . htmlspecialchars($mp['descripcion']) . '</td>
      <td style="text-align:right;">' . number_format($mp['valor'], 0, ',', '.') . '</td>
      <td style="text-align:right;">' . number_format($mp['cambio'], 0, ',', '.') . '</td>
    </tr>';
    $totalPagado += $mp['valor'];
}

$htmlMedios .=  '
  </tbody>
  <tfoot>
    <tr>
      <td>TOTAL</td>
      <td style="text-align:right;" colspan="2">' . number_format($totalPagado, 0, ',', '.') . '</td>
    </tr>
  </tfoot>
</table>';

$pdf->writeHTML( $htmlMedios, true, false, false, false, '' );
//Documento electronico
if ($dataCli['esElectronico'] == 'S') {
    // Ancho de tirilla (mm) según tu impresora POS
    $tirillaWidth = 80; // 80mm 

    // Tamaño del QR
    $qrSize = 30; 

    // Posición X para centrar
    $x = ($tirillaWidth - $qrSize) / 2; 
    $y = $pdf->GetY(); // posición actual en la tirilla

    $style = [
        'border' => false,
        'vpadding' => 'auto',
        'hpadding' => 'auto',
        'fgcolor' => [0, 0, 0],
        'bgcolor' => false,
        'module_width' => 1,
        'module_height' => 1
    ];

    // Imprimir QR centrado
    $pdf->write2DBarcode($dataCli['qrcode'], 'QRCODE,M', $x, $y, $qrSize, $qrSize, $style, 'N');
    
    // Dejar espacio debajo
    $pdf->Ln(2);
    $html = '
                <style>
                  .cufe td {
                    padding: 2px;
                  }
                  .cufe {
                    margin-top: 2px;
                    text-align: left;
                    width: 100%;
                    font-size: 9px;
                    text-align: justify;
                  }
                </style>
                <table class="cufe">
                  <tr><td><b>CUFE:</b> ' . $dataCli[ 'cufe' ] . '</td></tr>
                </table>
                ';
    $pdf->writeHTML( $html, true, false, false, false, '' );
}
$pdf->Ln(2);
$html = '<style>
          .desarrollador td {
            padding: 2px; text-align: center;
          }
          .desarrollador {
            margin-top: 2px;
            text-align: center;
            width: 100%;
            font-size: 9px;
            text-align: justify;
          }
        </style>
        <table class="desarrollador">
          <tr><td>Desarrollado por CYC Technology - Todos los derechos reservados - Medellín, Antioquia</td></tr>
        </table>
        ';
    $pdf->writeHTML( $html, true, false, false, false, '' );
$pdf->Output( 'factura_pos.pdf', 'I' );