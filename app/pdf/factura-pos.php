<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
ob_start(); // Capturar cualquier salida no deseada
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
$sqlEmpresa = "SELECT 
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

$t = GenerarArray( " SELECT
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
                        d.descuento,
                        d.iva,
                        d.ibua,
                        d.icui,
                        ifnull(d.impoconsumo, 0) as impoconsumo,
                        d.retencion_fuente,
                        d.retencion_cree,
                        d.retencion_ica,
                        d.retencion_iva,
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
                        d.descripcion_condicion,
                        -- ALMACENES
                        a.descripcion AS alm_desc,
                        a.direccion AS alm_direccion,
                        a.telefono AS alm_tel,
                        sa.departamento  as alm_dpto,
                        ma.municipio  as alm_mpio
                    from t_transacciones d
                    inner join t_terceros             t on t.id = d.id_tercero 
                    inner join t_terceros_direcciones r on r.id = d.id_tercero_dir
                    inner join t_usuarios             u on u.id=d.id_usuario
                    inner join t_tipos_documentos     z on z.id=d.id_tipo_documento 
                    inner join t_paises               p on p.pais = r.codigo_pais 
                    inner join t_departamentos        s on s.codigo_dpto = r.codigo_dpto  and s.codigo_pais = r.codigo_pais 
                    inner join t_municipios           m on m.codigo_pais = r.codigo_pais and m.codigo_dpto  = r.codigo_dpto and m.codigo =r.codigo_mpio
                    inner join t_almacenes            a on a.id = d.id_bodega 
                    inner join t_departamentos        sa on sa.codigo_dpto = a.departamento and sa.codigo_pais = r.codigo_pais 
                    inner join t_municipios           ma on ma.codigo_pais = r.codigo_pais and ma.codigo_dpto  = a.departamento and ma.codigo = a.municipio                     
                    left  join t_respuestas_dian     rd on rd.tipo = d.tipo and rd.numero= d.numero 
                    where 
                        d.id = $id_doc" );
// Si $t solo trae un registro de cliente, mejor tomar directamente el primero:
$d = reset($t);

// Validar que $d no sea false y que sea un array
if ($d === false || !is_array($d)) {
  ob_clean();
  die('Error: Documento no encontrado');
}

$dataCli = [
  'id_transaccion'  => $d['id_transaccion'] ?? 0,
  'tipo_doc'        => $d['tipo'] ?? '',
  'numero_doc'      => $d['numero'] ?? '',
  'numero'          => number_format($d['nit'] ?? 0, 0, '', '.') . ' - ' . ($d['nit_digito'] ?? ''),
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
  // Totales y retenciones
  'valor_total'     => isset($d['valor_total']) && $d['valor_total'] !== null ? floatval($d['valor_total']) : 0,
  'descuento'       => isset($d['descuento']) && $d['descuento'] !== null ? floatval($d['descuento']) : 0,
  'iva'             => isset($d['iva']) && $d['iva'] !== null ? floatval($d['iva']) : 0,
  'ibua'            => isset($d['ibua']) && $d['ibua'] !== null ? floatval($d['ibua']) : 0,
  'icui'            => isset($d['icui']) && $d['icui'] !== null ? floatval($d['icui']) : 0,
  'impoconsumo'     => isset($d['impoconsumo']) && $d['impoconsumo'] !== null ? floatval($d['impoconsumo']) : 0,
  'ret_fuente'      => isset($d['retencion_fuente']) && $d['retencion_fuente'] !== null ? floatval($d['retencion_fuente']) : 0,
  'ret_cree'        => isset($d['retencion_cree']) && $d['retencion_cree'] !== null ? floatval($d['retencion_cree']) : 0,
  'ret_ica'         => isset($d['retencion_ica']) && $d['retencion_ica'] !== null ? floatval($d['retencion_ica']) : 0,
  'ret_iva'         => isset($d['retencion_iva']) && $d['retencion_iva'] !== null ? floatval($d['retencion_iva']) : 0,
  //Almacen
  'alm_desc'        => $d['alm_desc'] ?? '',
  'alm_direccion'   => $d['alm_direccion'] ?? '',
  'alm_telefono'    => $d['alm_tel'] ?? '',
  'alm_dpto'        => $d['alm_dpto'] ?? '',
  'alm_mpio'        => $d['alm_mpio'] ?? '',
  // Leyendas opcionales
  'leyenda1'        => '',
  'leyenda2'        => ''
];

// Medios de Pago
$medios_pago = [];
$m = GenerarArray(" SELECT 
                        ttp.valor,
                        ttp.cambio,
                        tmp.descripcion 
                    FROM t_transacciones_pago ttp 
                    INNER JOIN t_medios_pagos tmp ON tmp.id = ttp.id_medio_pago 
                    WHERE 
                        ttp.id_transaccion  = '" . $dataCli['id_transaccion'] . "' 
");

// Validar que $m sea un array antes de iterar
if (is_array($m) && !empty($m)) {
    foreach ($m as $mp) {
        $medios_pago[] = [
            'descripcion' => $mp['descripcion'],
            'valor'       => (float)$mp['valor'],
            'cambio'      => (float)$mp['cambio']
        ];
    }
}


//Detalle de factura
$q = GenerarArray( "SELECT 
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

// Validar que $q sea un array antes de iterar
if (is_array($q) && !empty($q)) {
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
}


// Totales desde encabezado (mismo ajuste de factura.php)
$hdr_valor_total = $dataCli['valor_total'];
if ($hdr_valor_total <= 0) {
  $hdr_valor_total = $vlr_total_compra; // fallback si encabezado no trae total valido
}
$hdr_descuento   = $dataCli['descuento'];
$hdr_iva         = $dataCli['iva'];
$hdr_ibua        = $dataCli['ibua'];
$hdr_icui        = $dataCli['icui'];
$hdr_impoconsumo = $dataCli['impoconsumo'];
$hdr_ret_fuente  = $dataCli['ret_fuente'];
$hdr_ret_ica     = $dataCli['ret_ica'];
$hdr_ret_iva     = $dataCli['ret_iva'];

$vlr_subtotal_num          = $hdr_valor_total + $hdr_descuento - $hdr_iva - $hdr_impoconsumo - $hdr_ibua - $hdr_icui;
$vlr_saludables_num        = $hdr_ibua + $hdr_icui;
$vlr_total_retenciones_num = $hdr_ret_fuente + $hdr_ret_ica + $hdr_ret_iva;
$vlr_neto_pagar_num        = $hdr_valor_total - $vlr_total_retenciones_num;

$vlr_subtotal          = number_format($vlr_subtotal_num, 0, ',', '.');
$vlr_total_dto         = number_format($hdr_descuento, 0, ',', '.');
$vlr_total_iva         = number_format($hdr_iva, 0, ',', '.');
$vlr_total_impoconsumo = number_format($hdr_impoconsumo, 0, ',', '.');
$vlr_total_saludables  = number_format($vlr_saludables_num, 0, ',', '.');
$vlr_total_compra      = number_format($hdr_valor_total, 0, ',', '.');
$vlr_ret_fuente        = number_format($hdr_ret_fuente, 0, ',', '.');
$vlr_ret_ica           = number_format($hdr_ret_ica, 0, ',', '.');
$vlr_ret_iva           = number_format($hdr_ret_iva, 0, ',', '.');
$vlr_total_retenciones = number_format($vlr_total_retenciones_num, 0, ',', '.');
$vlr_neto_pagar        = number_format($vlr_neto_pagar_num, 0, ',', '.');

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
// Parametro de configuración para mostrar punto de venta en tirilla POS
$sql = "SELECT valor
        FROM t_config_modulos
        WHERE Clave = 'verPDVTirillaPOS'";

$t = GenerarArray($sql);
$config = reset($t);

$verPDV = ($config && $config['valor'] == 'S'); 

$html = '
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
</style>

<!-- Encabezado Empresa -->
<table class="encabezado">
    <tr>
        <td><strong>' . strtoupper($dataEnc['razon_comercial']) . '</strong></td>
    </tr>
    <tr>
        <td>NIT: ' . $dataEnc['nit'] . '-' . $dataEnc['nit_digito'] . '</td>
    </tr>
    <tr><td>&nbsp;</td></tr>';
if($verPDV){
  $html.='    <tr>
                  <td><strong>PUNTO DE VENTA</strong></td>
              </tr>
              <tr>
                  <td>
                      ' . $dataCli['alm_desc'] . '<br>
                      ' . $dataCli['alm_direccion'] . '<br>
                      ' . $dataCli['alm_mpio'] . ' - ' . $dataCli['alm_dpto'] . '<br>
                      Tel: ' . $dataCli['alm_telefono'] . '
                  </td>
              </tr>
              <tr><td>&nbsp;</td></tr>';
}


 $html .= ' <tr>
        <td><strong>OFICINA PRINCIPAL</strong></td>
    </tr>
    <tr>
        <td>
            ' . $dataEnc['direccion'] . '<br>
            Tel: ' . $dataEnc['telefono'] . '            
        </td>
    </tr>
</table>

<div class="linea"></div>

<!-- Datos documento -->
<table class="encabezado">
  <tr><td><strong>' . $dataCli[ 'tipo_documento' ] . '</strong></td></tr>
  <tr><td>Nro Doc.: ' . $dataCli[ 'tipo_doc' ] . ' - ' . $dataCli[ 'numero_doc' ] . '</td></tr>
  <tr><td>Consecutivo: ' . $dataCli[ 'consecutivo' ] . '</td></tr>
  <tr><td>Fecha: ' . $dataCli[ 'res_fecha' ] . '</td></tr>
  <tr><td>Resolución: ' . $dataCli[ 'res_numero' ] . ' Prefijo ' . $dataCli[ 'res_prefijo' ] . '</td></tr>
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
$lineasRetenciones = '';
if ($hdr_ret_fuente > 0) {
  $lineasRetenciones .= '<tr><td class="label">Rte Fuente:</td><td class="valor">- $ ' . $vlr_ret_fuente . '</td></tr>';
}
if ($hdr_ret_ica > 0) {
  $lineasRetenciones .= '<tr><td class="label">Rte ICA:</td><td class="valor">- $ ' . $vlr_ret_ica . '</td></tr>';
}
if ($hdr_ret_iva > 0) {
  $lineasRetenciones .= '<tr><td class="label">Rte IVA:</td><td class="valor">- $ ' . $vlr_ret_iva . '</td></tr>';
}
if ($vlr_total_retenciones_num > 0) {
  $lineasRetenciones .= '<tr><td class="label">Total retenciones:</td><td class="valor">- $ ' . $vlr_total_retenciones . '</td></tr>';
}

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
    <td class="label">Total bruto:</td>
    <td class="valor">$ ' . $vlr_total_compra . '</td>
  </tr>
  ' . $lineasRetenciones . '
  <tr>
    <td class="label resaltado">NETO A PAGAR:</td>
    <td class="valor resaltado">$ ' . $vlr_neto_pagar . '</td>
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

    if (!empty($dataCli['dias']) && intval($dataCli['dias']) > 0) {
      $htmlNota = '<style>
                    .nota td {
                      padding: 2px;
                    }
                    .nota {
                      margin-top: 2px;
                      text-align: left;
                      width: 100%;
                      font-size: 8px;
                      text-align: justify;
                      line-height: 1.2;
                    }
                  </style>
                  <table class="nota">
                    <tr><td>Los productos fueron recibidos real y materialmente por el comprador, de conformidad según el Artículo 2 Ley 1231 de 2008. Nos reservamos el derecho de propiedad de la mercancía aquí relacionada hasta la cancelación total de esta factura. Los reclamos por errores en los despachos deberán hacerse al recibo de la mercancía. Según lo dispuesto por el artículo 518 del código de comercio. Pasada la fecha de vencimiento de la factura se cobrara intereses por mora a la máxima tasa permitida por la Ley. Después de 60 días de vencida la factura será enviada al departamento jurídico para el cobro coactivo.<br><br>Esta factura será cargada a la orden de <strong>' . strtoupper($dataEnc['razon_comercial']) . '</strong>. Solicitar recibo de caja Original. El cliente acepta que quien firma este documento tiene plena autorización para ello.<br><br><strong>NO SE ACEPTAN RECLAMOS DE LA FACTURA DESPUÉS DE 3 DÍAS</strong></td></tr>
                  </table>';
      $pdf->writeHTML( $htmlNota, true, false, false, false, '' );
    }
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
            font-size: 8px;
            text-align: justify;
          }
        </style>
        <table class="desarrollador">
          <tr><td>Desarrollado por CYC Technology S.A.S</td></tr>
          <tr><td>Todos los derechos reservados - Medellín, Antioquia</td></tr>
          <tr><td>Contáctenos: 3242181420 - 3108272605</td></tr>
        </table>
        ';
    $pdf->writeHTML( $html, true, false, false, false, '' );

// Limpiar el buffer de salida antes de enviar el PDF
ob_end_clean();
$pdf->Output( 'factura_pos.pdf', 'I' );