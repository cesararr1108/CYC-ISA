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
$sqlEmpresa = "
    SELECT 
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
$empresa = GenerarArray( $sqlEmpresa );

// Validar resultado
if ( !empty( $empresa ) ) {
  $d = $empresa[ 0 ]; // solo una fila de empresa
  $logo = !empty($d['logo']) ? $d['logo'] : 'default_logo.png';
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


// Si $t solo trae un registro de cliente, mejor tomar directamente el primero:
$d = reset( $t );

$dataCli = [
  'tipo_doc' => $d[ 'tipo' ] ?? '',
  'numero_doc' => $d[ 'numero' ] ?? '',
  'numero' => number_format( $d[ 'nit' ], 0, '', '.' ) . ' - ' . ( $d[ 'nit_digito' ] ?? '' ),
  'nombres' => $d[ 'nombres' ] ?? '',
  'razon_comercial' => $d[ 'razon_comercial' ] ?? '',
  'direccion' => $d[ 'direccion' ] ?? '',
  'nombre_dpto' => $d[ 'nombre_dpto' ] ?? '',
  'nombre_mpio' => $d[ 'nombre_mpio' ] ?? '',
  'telefono' => $d[ 'telefono' ] ?? '',
  'fecha_hora' => $d[ 'fecha_hora' ] ?? '',
  'usuario' => $d[ 'usuario' ] ?? '',
  'nombres_usuario' => $d[ 'nombres_usuario' ] ?? '',
  'id_bodega' => $d[ 'id_bodega' ] ?? '',
  'tipo_documento' => $d[ 'descripcion' ] ?? '',
  'desc_bodega' => $d[ 'desc_bodega' ] ?? '',
  'valor_total' => $d[ 'valor_total' ] ?? 0,
  'notas' => $d[ 'notas' ] ?? ''
];


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
$total = number_format( ( $dataCli[ 'valor_total' ] ), 0 );
$total_letra = numeroALetras($dataCli[ 'valor_total' ]);
$notas = $dataCli[ 'notas' ];
$tblDet = '
<style>
  .detalle {
    width: 100%;
    font-size: 8px;
    margin-top: 4px;
  }
  .detalle td {
    padding: 2px;
    vertical-align: top;
  }
  .detalle .label {
    font-weight: bold;
    width: 40%;
  }
  .detalle .valor {
    text-align: right;
    width: 60%;
  }
</style>
<table class="detalle">
  <tr>
    <td class="label">VALOR</td>
    <td class="valor">$'.$total.'</td>
  </tr>
  <tr>
    <td class="label">VALOR EN LETRAS</td>
    <td class="valor">'.$total_letra.'</td>
  </tr>
</table>

<div class="linea"></div>

<table class="detalle">
  <tr>
    <td class="label">NOTAS</td>
    <td>'.$notas.'</td>
  </tr>
</table>
';
$pdf->writeHTML( $tblDet, true, false, false, false, '' );

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
$pdf->Output( 'egreso_pos.pdf', 'I' );