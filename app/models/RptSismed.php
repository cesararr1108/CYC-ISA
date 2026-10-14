<?php
session_start();
require_once('bd.php');
require_once('funciones.php');
$conn = conectar();

/* ==========================================================================
   SISMED - Circular 021 de 2026 (Anexo Técnico No. 2 - ROL 2)
   Parámetros del reporte: ajústelos según la operación de la empresa.
   ========================================================================== */

// sw de las ventas facturadas (ver tabla de sw). 4 = devoluciones en venta, 5 = ventas.
const SISMED_SW_VENTAS = 5;

// Campo 2: primeros 10 dígitos del código REPS si la empresa es IPS; si no, '0'.
const SISMED_CODIGO_HABILITACION = '0';

// Campo 4: Rol 02 (actor que no elabora ni importa). Tipo N, sin relleno.
const SISMED_ROL = '2';

// Campo 10: debe ser el MISMO código de unidad que viaja en el XML de la FEV
// (en la mayoría de facturadores colombianos es '94' = unidad). Máx. 2 caracteres.
const SISMED_UNIDAD_MEDIDA = 'UN';

// Campo 17: VN venta | CM compra | RC recobro
const SISMED_TIPO_OPERACION = 'VN';

// Campo 18: 01 primaria inst. | 02 primaria com. | 03 secundaria inst. | 04 secundaria com. | 05 final inst.
const SISMED_TIPO_TRANSACCION = '04';


function obtenerTrimestres($anio)
{
    return [
        1 => ['fecha_inicial' => "$anio-01-01", 'fecha_final' => "$anio-03-31"],
        2 => ['fecha_inicial' => "$anio-04-01", 'fecha_final' => "$anio-06-30"],
        3 => ['fecha_inicial' => "$anio-07-01", 'fecha_final' => "$anio-09-30"],
        4 => ['fecha_inicial' => "$anio-10-01", 'fecha_final' => "$anio-12-31"],
    ];
}

function obtenerTrimestreAnterior()
{
    $fecha = new DateTime();
    $anio  = (int) $fecha->format('Y');
    $trimestreAnterior = (int) ceil(((int) $fecha->format('n')) / 3) - 1;

    if ($trimestreAnterior === 0) {
        $trimestreAnterior = 4;
        $anio--;
    }

    $periodo = obtenerTrimestres($anio)[$trimestreAnterior];
    $periodo['anio'] = $anio;
    $periodo['trimestre'] = $trimestreAnterior;
    return $periodo;
}

function fechaValida($fecha)
{
    $d = DateTime::createFromFormat('Y-m-d', (string) $fecha);
    return $d && $d->format('Y-m-d') === $fecha;
}


$op = $_POST['op'] ?? '';

switch ($op) {

    case 'consultar':

        $fecha_inicial = $_POST['fecha_inicial'] ?? '';
        $fecha_final   = $_POST['fecha_final'] ?? '';

        if (!fechaValida($fecha_inicial) || !fechaValida($fecha_final) || $fecha_inicial > $fecha_final) {
            echo json_encode(['error' => 'Rango de fechas inválido']);
            break;
        }

        // Códigos DIVIPOLA de 5 dígitos: 2 del departamento + 3 del municipio.
        // Si el municipio ya viene con 5 dígitos se usa tal cual.
        $str = "SELECT
                '2' AS Tipo_de_registro,
                ROW_NUMBER() OVER (ORDER BY d.fec, t.id, d.id) AS Consecutivo_de_registro,
                '" . SISMED_CODIGO_HABILITACION . "' AS Codigo_de_habilitacion,
                MONTH(d.fec) AS Mes_emision,
                '" . SISMED_ROL . "' AS Rol_del_actor,
                CASE
                    WHEN CHAR_LENGTH(IFNULL(a.municipio,'')) >= 5 THEN a.municipio
                    WHEN IFNULL(a.municipio,'') = '' THEN ''
                    ELSE CONCAT(LPAD(a.departamento,2,'0'), LPAD(a.municipio,3,'0'))
                END AS CiudadBodega,
                IFNULL(rx.cufe,'') AS CufeFev,
                CONCAT(t.tipo, t.numero) AS NumeroFactura,
                DATE_FORMAT(d.fec,'%Y-%m-%d') AS FechaEmision,
                r.codigo AS CodigoProducto,
                '" . SISMED_UNIDAD_MEDIDA . "' AS UnidadMedida,

                -- Cantidad y precio tal como salen en la FEV (por presentación).
                -- Si su FEV factura en unidades sueltas, use d.cantidad y d.valor_unitario.
                ROUND(d.cantidad / IF(IFNULL(d.can_und,0) = 0, 1, d.can_und), 4) AS Cantidad,
                ROUND(d.valor_unitario * IF(IFNULL(d.can_und,0) = 0, 1, d.can_und), 2) AS PrecioUnitario,

                '0' AS DocumentoSoporte,

                -- Valor bruto (Art. 3): precio unitario x cantidad, sin descuento ni IVA
                ROUND(d.valor_unitario * d.cantidad, 2) AS Total,

                REGEXP_REPLACE(SUBSTRING_INDEX(COALESCE(NULLIF(t.nit,''), ter.nit, ''),'-',1),'[^0-9]','') AS NitEntidadFacturacion,
                CASE
                    WHEN CHAR_LENGTH(IFNULL(tdir.codigo_mpio,'')) >= 5 THEN tdir.codigo_mpio
                    WHEN IFNULL(tdir.codigo_mpio,'') = '' THEN ''
                    ELSE CONCAT(LPAD(tdir.codigo_dpto,2,'0'), LPAD(tdir.codigo_mpio,3,'0'))
                END AS CodigoMunicipioEntidad,
                '" . SISMED_TIPO_OPERACION . "' AS TipoOperacion,
                '" . SISMED_TIPO_TRANSACCION . "' AS TipoTransaccion,

                -- Sin IUM asignado: niveles en 0 y se diligencian expediente y presentación
                '0' AS IUM_1,
                '0' AS IUM_2,
                '0' AS IUM_3,
                IF(LOCATE('-', r.codigo_cum) > 0,
                   REGEXP_REPLACE(SUBSTRING_INDEX(r.codigo_cum,'-',1),'[^0-9]',''), '') AS NumeroExpediente,
                IF(LOCATE('-', r.codigo_cum) > 0,
                   CAST(REGEXP_REPLACE(SUBSTRING_INDEX(r.codigo_cum,'-',-1),'[^0-9]','') AS UNSIGNED), '') AS NumeroPresentacion,

                -- Unidades mínimas de dispensación (tableta, cápsula, ampolla...) incluidas bonificaciones
                ROUND(d.cantidad, 4) AS TotalUnidadesMinimas

            FROM t_transacciones_detalle d
            INNER JOIN t_transacciones t        ON t.id = d.id_transaccion
            INNER JOIN t_referencias r          ON r.id = d.id_referencia
            LEFT  JOIN t_terceros ter           ON ter.id = t.id_tercero
            LEFT  JOIN t_terceros_direcciones tdir ON tdir.id = t.id_tercero_dir
            LEFT  JOIN t_almacenes a            ON a.id = t.id_bodega
            LEFT  JOIN (
                    SELECT tipo, numero, MAX(cufe) AS cufe
                    FROM t_respuestas_dian
                    WHERE IFNULL(cufe,'') <> ''
                    GROUP BY tipo, numero
                  ) rx ON rx.tipo = t.tipo AND rx.numero = t.numero
            WHERE d.sw = " . (int) SISMED_SW_VENTAS . "
              AND IFNULL(r.codigo_cum,'') <> ''
              AND d.fec >= ?
              AND d.fec < DATE_ADD(?, INTERVAL 1 DAY)
            ORDER BY d.fec, t.id, d.id";

        $parametros = [
            ['tipo' => 's', 'valor' => $fecha_inicial],
            ['tipo' => 's', 'valor' => $fecha_final],
        ];

        $result = GenerarArrayPrepare($str, $parametros);
        echo json_encode($result);
        break;

    case 'trimestres':
        echo json_encode(obtenerTrimestreAnterior());
        break;

    default:
        echo json_encode(['error' => 'Operación no válida']);
        break;
}