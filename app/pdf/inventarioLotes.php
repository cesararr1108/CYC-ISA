<?php
session_start();
require_once '../../assets/tcpdf/tcpdf.php';
require_once('../models/bd.php');
require_once('../models/funciones.php');

// Configuración de la página tipo tirilla (80mm ancho)
$pdf = new TCPDF('P', 'mm', array(80, 300), true, 'UTF-8', false);
$pdf->SetPrintHeader(false);
$pdf->SetPrintFooter(false);
$pdf->SetMargins(2, 2, 2); // márgenes pequeños
$pdf->SetAutoPageBreak(true, 2);
$pdf->AddPage();

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

// Logo
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
$pdf->Ln(35);

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
  <tr><td><strong>' . $dataEnc['razon_comercial'] . '</strong></td></tr>
  <tr><td>' . $dataEnc['nit'] . '-' . $dataEnc['nit_digito'] . '</td></tr>
  <tr><td>' . $dataEnc['direccion'] . '</td></tr>
  <tr><td>Tel: ' . $dataEnc['telefono'] . '</td></tr>
  <tr><td>' . $dataEnc['nombre_mpio'] . ' - ' . $dataEnc['nombre_dpto'] . '</td></tr>
  <tr><td>' . $dataEnc['correo'] . '</td></tr>
</table>
<div class="linea"></div>
';

// Pintamos en el PDF
$pdf->writeHTML($html, true, false, false, false, '');

$opt_lote = isset($_GET['optLote']) ? $_GET['optLote'] : '';


// =====================================================
// CONSULTA BASE SEGÚN MODO DE LOTE
// =====================================================

if ($opt_lote === 'detalle') {

    // =================================================
    // DETALLE POR LOTE
    // =================================================

    $sql = "SELECT 
                v.id_bodega AS bodega_num,
                a.descripcion AS bodega_desc,
                r.id_marca,
                IFNULL(m.descripcion, '') AS proveedor,
                r.descripcion,
                r.codigo,
                IFNULL(r.descripcion_larga, '') AS descripcion_larga,
                IFNULL(v.stock, 0) AS stock,
                IFNULL(v.lote, '') AS lote,
                IFNULL(v.vencimiento, '') AS vencimiento,
                IFNULL((
                    SELECT e.ean
                    FROM t_referencias_ean e
                    WHERE e.id_referencia = r.id
                    LIMIT 1
                ), '') AS ean,
                IFNULL(c.promedio, 0) AS costo_unitario,
                IFNULL(c.promedio, 0) * IFNULL(v.stock, 0) AS costo_total,
                r.codigo_invima

            FROM t_referencias r

            LEFT JOIN v_referencias_stock_lotes v 
                ON v.id_referencia = r.id 

            LEFT JOIN t_almacenes a 
                ON a.id = v.id_bodega

            LEFT JOIN t_marcas m 
                ON m.id = r.id_marca 
                AND m.inactivo = 0

            LEFT JOIN v_costo_promedio c 
                ON c.id_referencia = r.id
                AND c.ano = YEAR(NOW())
                AND c.mes = MONTH(NOW())

            WHERE 1 = 1 ";
} else {

    // =================================================
    // RESUMEN SIN LOTE
    // Una fila por referencia + bodega
    // =================================================

    $sql = "SELECT 
                v.id_bodega AS bodega_num,
                a.descripcion AS bodega_desc,
                r.id_marca,
                IFNULL(m.descripcion, '') AS proveedor,
                r.descripcion,
                r.codigo,
                IFNULL(r.descripcion_larga, '') AS descripcion_larga,

                -- SUMA DE TODOS LOS LOTES
                IFNULL(SUM(v.stock), 0) AS stock,

                -- No se muestran lotes
                '' AS lote,
                '' AS vencimiento,

                IFNULL((
                    SELECT e.ean
                    FROM t_referencias_ean e
                    WHERE e.id_referencia = r.id
                    LIMIT 1
                ), '') AS ean,

                IFNULL(c.promedio, 0) AS costo_unitario,

                -- Costo sobre el stock total
                IFNULL(c.promedio, 0) * IFNULL(SUM(v.stock), 0) AS costo_total,

                r.codigo_invima

            FROM t_referencias r

            LEFT JOIN v_referencias_stock_lotes v 
                ON v.id_referencia = r.id 

            LEFT JOIN t_almacenes a 
                ON a.id = v.id_bodega

            LEFT JOIN t_marcas m 
                ON m.id = r.id_marca 
                AND m.inactivo = 0

            LEFT JOIN v_costo_promedio c 
                ON c.id_referencia = r.id
                AND c.ano = YEAR(NOW())
                AND c.mes = MONTH(NOW())

            WHERE 1 = 1 ";
}


// =====================================================
// FILTRO POR BODEGA
// =====================================================

if (isset($_GET['bodega']) && $_GET['bodega'] !== '') {

    $bodega = (int) $_GET['bodega'];

    if ($bodega > 0) {
        $sql .= " AND v.id_bodega = $bodega ";
    }
}


// =====================================================
// FILTRO POR DESCRIPCIÓN
// =====================================================

if (!empty($_GET['descripcion'])) {

    $desc = addslashes(trim($_GET['descripcion']));

    $sql .= " 
        AND (
            r.descripcion LIKE '%$desc%'
            OR r.descripcion_larga LIKE '%$desc%'
            OR r.codigo LIKE '%$desc%'
        )
    ";
}


// =====================================================
// FILTRO POR MARCA
// =====================================================

if (isset($_GET['marca']) && $_GET['marca'] !== '') {

    $marca = (int) $_GET['marca'];

    if ($marca > 0) {
        $sql .= " AND r.id_marca = $marca ";
    }
}


// =====================================================
// FILTRO POR STOCK
// =====================================================

if (
    isset($_GET['stock']) &&
    strtoupper($_GET['stock']) === 'S'
) {
    $sql .= " AND v.stock > 0 ";
}


// =====================================================
// FILTRO INSTITUCIONAL
// =====================================================

if (
    isset($_GET['institucional']) &&
    strtoupper($_GET['institucional']) === 'S'
) {
    $sql .= " AND r.institucional = 'S' ";
}


// =====================================================
// FILTRO REGULADO
// =====================================================

if (
    isset($_GET['regulado']) &&
    strtoupper($_GET['regulado']) === 'S'
) {
    $sql .= " AND r.regulado = 'S' ";
}


// =====================================================
// FILTRO CONTROLADO
// =====================================================

if (
    isset($_GET['controlado']) &&
    strtoupper($_GET['controlado']) === 'S'
) {
    $sql .= " AND r.controlado = 'S' ";
}


// =====================================================
// AGRUPAMIENTO
// =====================================================

if ($opt_lote !== 'detalle') {

    $sql .= "
        GROUP BY
            v.id_bodega,
            a.descripcion,
            r.id,
            r.id_marca,
            m.descripcion,
            r.descripcion,
            r.codigo,
            r.descripcion_larga,
            c.promedio,
            r.codigo_invima
    ";
}


// =====================================================
// ORDENAMIENTO
// =====================================================

$sql .= " 
    ORDER BY 
        stock DESC,
        r.descripcion ASC
";


// =====================================================
// CONSULTA
// =====================================================


$q = GenerarArray($sql);

$pdf->SetFont('helvetica', '', 8);
if ($opt_lote === 'detalle') {
    $html = '<div style="font-size:10px; text-align:center; margin-top:6px;">'
        . '<strong>INVENTARIO POR LOTES</strong>'
        . '</div>';
} else {
    $html = '<div style="font-size:10px; text-align:center; margin-top:6px;">'
        . '<strong>INVENTARIO CONSOLIDADO</strong>'
        . '</div>';
}

if (!empty($q)) {

    $html .= '<div style="font-size:8px; margin-top:4px;">';

    foreach ($q as $row) {

        $html .= '<table width="100%" cellpadding="2" cellspacing="0" style="font-size:8px;">';

        $html .= '<tr>
                    <td>
                        <strong>Código:</strong> ' .
            htmlspecialchars(trim($row['codigo'])) .
            '</td>
                  </tr>';

        $html .= '<tr>
                    <td>
                        <strong>Descripción:</strong> ' .
            htmlspecialchars(trim($row['descripcion'])) .
            '</td>
                  </tr>';

        $html .= '<tr>
                    <td>
                        <strong>Laboratorio:</strong> ' .
            htmlspecialchars(trim($row['proveedor'])) .
            '</td>
                  </tr>';

        $html .= '<tr>
                    <td>
                        <strong>Cantidad:</strong> ' .
            number_format($row['stock'], 0, ',', '.') .
            '</td>
                  </tr>';

        // ==========================================
        // MOSTRAR LOTE Y VENCIMIENTO SOLO EN DETALLE
        // ==========================================

        if ($opt_lote === 'detalle') {

            $html .= '<tr>
                        <td>
                            <strong>Lote:</strong> ' .
                htmlspecialchars(trim($row['lote'])) .
                '</td>
                      </tr>';

            $html .= '<tr>
                        <td>
                            <strong>Vencimiento:</strong> ' .
                htmlspecialchars(trim($row['vencimiento'])) .
                '</td>
                      </tr>';
        }

        $html .= '<tr>
                    <td style="border-bottom:1px dashed #000; padding-top:4px;">
                        &nbsp;
                    </td>
                  </tr>';

        $html .= '</table>';
    }

    $html .= '</div>';
} else {

    $html .= '
        <div style="
            font-size:9px;
            margin-top:8px;
            text-align:center;
        ">
            <strong>
                No se encontraron registros para los filtros aplicados.
            </strong>
        </div>';
}


$pdf->writeHTML($html, true, false, false, false, '');
$pdf->Output('inventario_lotes.pdf', 'I');
