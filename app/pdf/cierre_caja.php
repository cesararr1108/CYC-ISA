<?php
session_start();

require_once '../../assets/tcpdf/tcpdf.php';
require_once('../models/bd.php');
require_once('../models/funciones.php');

$id_doc = $_GET['id'];

//---------------------------------------------
// CONFIGURACION PDF
//---------------------------------------------

$pdf = new TCPDF('P', 'mm', array(80, 300), true, 'UTF-8', false);
$pdf->SetPrintHeader(false);
$pdf->SetPrintFooter(false);
$pdf->SetMargins(2, 2, 2);
$pdf->SetAutoPageBreak(true, 2);
$pdf->AddPage();


//---------------------------------------------
// DATOS EMPRESA
//---------------------------------------------

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
INNER JOIN t_paises p ON p.pais = t.codigo_pais
INNER JOIN t_departamentos d 
ON d.codigo_dpto = t.codigo_dpto AND d.codigo_pais = t.codigo_pais
INNER JOIN t_municipios m 
ON m.codigo_pais = t.codigo_pais
AND m.codigo_dpto = t.codigo_dpto
AND m.codigo = t.codigo_mpio
";

$empresa = GenerarArray($sqlEmpresa);
$d = $empresa[0];

// Validar resultado
if (!empty($empresa)) {
    $d = $empresa[0]; // solo una fila de empresa
    $logo = !empty($d['logo']) ? $d['logo'] : 'default_logo.png';
    $dataEnc = [
        'nit' => number_format($d['nit'], 0, '', '.'),
        'nit_digito' => $d['nit_digito'],
        'razon_comercial' => $d['razon_comercial'],
        'direccion' => $d['direccion'],
        'nombre_mpio' => $d['nombre_mpio'],
        'nombre_dpto' => $d['nombre_dpto'],
        'telefono' => trim($d['telefono']),
        'correo' => $d['correo'],
        'logo' => $logo
    ];
} else {
    $dataEnc = []; // fallback si no hay datos
}


//---------------------------------------------
// DATOS CIERRE
//---------------------------------------------

$t = GenerarArray(" SELECT
                        c.id as id_apertura,
                        c.fecha_apertura,
                        c.fecha_cierre,
                        c.base,
                        c.efectivo,
                        c.tarjetas,
                        c.otros,
                        c.observaciones,
                        c.id_bodega,
                        a.descripcion as desc_bodega,
                        u.usuario,
                        c.id_usuario,
                    CONCAT(ifnull(u.nombres,''),' ',ifnull(u.apellidos,'')) as nombres
                    FROM t_aperturas_cajas c
                    INNER JOIN t_almacenes a ON a.id=c.id_bodega
                    INNER JOIN t_usuarios u ON u.id=c.id_usuario
                    WHERE c.id = $id_doc
");

$d = $t[0];

$dataDoc = [
    'id_apertura' => $d['id_apertura'],
    'fecha_apertura' => $d['fecha_apertura'],
    'fecha_cierre' => $d['fecha_cierre'],
    'base' => $d['base'],
    'efectivo' => $d['efectivo'],
    'tarjetas' => $d['tarjetas'],
    'otros' => $d['otros'],
    'observaciones' => $d['observaciones'],
    'id_bodega' => $d['id_bodega'],
    'desc_bodega' => $d['desc_bodega'],
    'usuario' => $d['usuario'],
    'nombres' => $d['nombres'],
    'id_usuario' => $d['id_usuario']
];


//---------------------------------------------
// VARIABLES PARA CONSULTAS
//---------------------------------------------

 $fecha_inicial = date('Y-m-d H:i:s', strtotime($dataDoc['fecha_apertura']));
$fecha_final   = date('Y-m-d H:i:s', strtotime($dataDoc['fecha_cierre']));
$bodega        = $dataDoc['id_bodega'];
$usuario       = $d['id_usuario'];


//---------------------------------------------
// PAGOS DEL SISTEMA
//---------------------------------------------

$sql_pagos = "SELECT  
                    m.descripcion as medio,
                    ifnull(sum(t.valor),0) as valor
              FROM t_medios_pagos m
              LEFT JOIN t_transacciones_pago t   ON t.id_medio_pago=m.id
              LEFT JOIN  t_transacciones x ON x.numero=t.numero AND x.tipo=t.tipo AND IFNULL(x.anulado, 0) = 0
              WHERE  
                x.fecha_hora  BETWEEN '$fecha_inicial' and '$fecha_final' AND 
                x.id_bodega = $bodega AND
                x.id_usuario = $usuario AND 
                x.sw = 5
              GROUP BY 
                m.descripcion";
 

$pagos = GenerarArray($sql_pagos);
$total_pagos = 0;
foreach ($pagos as $p) {
    $total_pagos += $p['valor'];
}


//---------------------------------------------
// OTROS INGRESOS
//---------------------------------------------

$sql_otros = "SELECT 
                IFNULL(sum(valor_total),0) AS total
              FROM t_transacciones
              WHERE 
                id_usuario = $usuario AND
                sw = 10 AND 
                IFNULL(anulado, 0) = 0 AND
                fecha_hora  BETWEEN '$fecha_inicial' AND '$fecha_final' AND 
                id_bodega = $bodega";

$otros = GenerarArray($sql_otros);
$otros_ingresos = $otros[0]['total'];


//---------------------------------------------
// EGRESOS
//---------------------------------------------

$sql_e = "SELECT 
            IFNULL(sum(valor_total),0) as total
          FROM t_transacciones
          WHERE 
           id_usuario = $usuario AND
           sw = 9 AND 
           IFNULL(anulado, 0) = 0 AND
           fecha_hora  BETWEEN '$fecha_inicial' and '$fecha_final' AND  
           id_bodega = $bodega
";

$egresos = GenerarArray($sql_e);
$total_egresos = $egresos[0]['total'];


//---------------------------------------------
// TOTALES
//---------------------------------------------

$total_sistema = $total_pagos + $otros_ingresos - $total_egresos;

$base = $dataDoc['base'];
$efectivo = $dataDoc['efectivo'];
$tarjetas = $dataDoc['tarjetas'];
$otros = $dataDoc['otros'];

$total_cajero = /*$base +*/ $efectivo + $tarjetas + $otros;

$diferencia = $total_cajero - $total_sistema;


//---------------------------------------------
// LOGO
//---------------------------------------------

//$pdf->Image('../../assets/img/logo/'.$dataEnc['logo'],28,10,25);
$pdf->Image(
    '../../assets/img/logo/' . $dataEnc['logo'], // ruta
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

$pdf->Ln(40);


//---------------------------------------------
// ENCABEZADO
//---------------------------------------------

$html = '

<style>

.enc{
text-align:center;
font-size:9px;
}

.det{
font-size:8px;
width:100%;
}

.det td{
padding:2px;
}

.valor{
text-align:right;
}

.dat{
text-align:center;
}

.titulo{
font-weight:bold;
text-align:center;
}

</style>

<table class="enc">

<tr><td><b>' . $dataEnc['razon_comercial'] . '</b></td></tr>
<tr><td>' . $dataEnc['nit'] . '-' . $dataEnc['nit_digito'] . '</td></tr>
<tr><td>' . $dataEnc['direccion'] . '</td></tr>
<tr><td>' . $dataEnc['nombre_mpio'] . '-' . $dataEnc['nombre_dpto'] . '</td></tr>
<tr><td>Tel ' . $dataEnc['telefono'] . '</td></tr>

</table>

<br>

<table class="enc">
<tr><td><b>CIERRE DE CAJA</b></td></tr>
<tr><td>Doc: ' . $dataDoc['id_apertura'] . '</td></tr>
</table>

<br>

<table class="det">
<tr>
 <td class="titulo">CAJERO</td>
</tr>
<tr>
 <td class="dat">' . $dataDoc['nombres'] . '</td>
</tr>
<tr>
 <td class="titulo">BODEGA</td>
</tr>
<tr>
 <td class="dat">' . $dataDoc['desc_bodega'] . '</td>
</tr>
<tr>
 <td class="titulo">APERTURA</td>
</tr>
<tr>
 <td class="dat">' . date('Y-m-d H:i', strtotime($dataDoc['fecha_apertura'])) .  '</td>
</tr>
<tr>
 <td class="titulo">CIERRE</td>
</tr>
<tr>
 <td class="dat">' . date('Y-m-d H:i', strtotime($dataDoc['fecha_cierre'])) . '</td>
</tr>
</table>

<br>

<table class="det">

<tr><td colspan="2" class="titulo">DATOS DEL SISTEMA</td></tr>

<tr><td colspan="2" class="titulo">Medios de pago</td></tr>
';

foreach ($pagos as $p) {
    $html .= '<tr>
        <td>' . htmlspecialchars($p['medio']) . '</td>
        <td class="valor">$' . number_format($p['valor'], 0, ",", ".") . '</td>
    </tr>';
}

$html .= '
<tr>
<td><b>Total pagos</b></td>
<td class="valor"><b>$' . number_format($total_pagos, 0, ",", ".") . '</b></td>
</tr>

<tr>
<td>Otros ingresos</td>
<td class="valor">$' . number_format($otros_ingresos, 0, ",", ".") . '</td>
</tr>

<tr>
<td>Egresos</td>
<td class="valor">$' . number_format($total_egresos, 0, ",", ".") . '</td>
</tr>

<tr>
<td><b>Total Sistema</b></td>
<td class="valor"><b>$' . number_format($total_sistema, 0, ",", ".") . '</b></td>
</tr>

<tr><td colspan="2" class="titulo">DATOS CAJERO</td></tr>

<tr>
<td>Base</td>
<td class="valor">$' . number_format($base, 0, ",", ".") . '</td>
</tr>

<tr>
<td>Efectivo</td>
<td class="valor">$' . number_format($efectivo, 0, ",", ".") . '</td>
</tr>

<tr>
<td>Tarjetas</td>
<td class="valor">$' . number_format($tarjetas, 0, ",", ".") . '</td>
</tr>

<tr>
<td>Otros</td>
<td class="valor">$' . number_format($otros, 0, ",", ".") . '</td>
</tr>

<tr>
<td><b>Total Cajero</b></td>
<td class="valor"><b>$' . number_format($total_cajero, 0, ",", ".") . '</b></td>
</tr>

<tr>
<td><b>Diferencia</b></td>
<td class="valor"><b>$' . number_format($diferencia, 0, ",", ".") . '</b></td>
</tr>

</table>

<br>

<table class="det">
<tr><td>Observaciones</td></tr>
<tr><td>' . $dataDoc['observaciones'] . '</td></tr>
</table>

<br>

<table class="enc">
<tr><td>Desarrollado por CYC Technology</td></tr>
<tr><td>Medellín - Antioquia</td></tr>
</table>

';

$pdf->writeHTML($html, true, false, false, false, '');


//---------------------------------------------
// SALIDA PDF
//---------------------------------------------

$pdf->Output('cierre_caja.pdf', 'I');
