<?php
session_start();
require_once('bd.php');
require_once('funciones.php');
$conn = conectar();

header('Content-Type: application/json; charset=utf-8');

if (!sessionExists()) {
  echo json_encode(array(
    'error' => true,
    'message' => 'La sesion se ha cerrado, por favor ingrese nuevamente'
  ));
  return;
}

/**
 * Lee un checkbox enviado desde JS (true / "true").
 */
function postBool($key)
{
  return isset($_POST[$key]) && ($_POST[$key] === true || $_POST[$key] === 'true');
}

/**
 * Construye el WHERE común para ambas consultas.
 * - Bodega y marca se fuerzan a entero (evita inyección).
 * - La búsqueda divide el texto en palabras: cada palabra debe aparecer
 *   en alguno de los campos (descripción, descripción larga, código,
 *   CUM, INVIMA o EAN). Así "acetam 500" encuentra "ACETAMINOFEN TAB 500MG".
 */
function construirFiltros()
{
  $where = " WHERE 1=1 ";

  // Bodega
  $bodega = isset($_POST['bodega']) ? (int) $_POST['bodega'] : 0;
  if ($bodega > 0) {
    $where .= " AND v.id_bodega = $bodega ";
  }

  // Marca
  $marca = isset($_POST['marca']) ? (int) $_POST['marca'] : 0;
  if ($marca > 0) {
    $where .= " AND r.id_marca = $marca ";
  }

  // Búsqueda por palabras
  $texto = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
  if ($texto !== '') {
    $palabras = preg_split('/\s+/', $texto, -1, PREG_SPLIT_NO_EMPTY);
    $palabras = array_slice($palabras, 0, 6); // límite razonable
    foreach ($palabras as $p) {
      $p = addslashes(str_replace(array('%', '_'), array('\%', '\_'), $p));
      $where .= " AND (
                    r.descripcion LIKE '%$p%'
                    OR r.descripcion_larga LIKE '%$p%'
                    OR r.codigo LIKE '%$p%'
                    OR r.codigo_cum LIKE '%$p%'
                    OR r.codigo_invima LIKE '%$p%'
                    OR e.ean LIKE '%$p%'
                  ) ";
    }
  }

  if (postBool('institucional')) $where .= " AND r.institucional = 'S' ";
  if (postBool('regulado'))      $where .= " AND r.regulado = 'S' ";
  if (postBool('controlado'))    $where .= " AND r.controlado = 'S' ";

  return $where;
}

/**
 * JOINs comunes.
 * El EAN se trae con un LEFT JOIN a una tabla derivada agrupada
 * (antes era una subconsulta correlacionada que se ejecutaba por cada fila).
 */
$joins = "
    FROM t_referencias r
    LEFT JOIN v_referencias_stock_lotes v ON v.id_referencia = r.id
    LEFT JOIN t_almacenes a ON a.id = v.id_bodega
    LEFT JOIN t_marcas m ON m.id = r.id_marca AND m.inactivo = 0
    LEFT JOIN (
        SELECT id_referencia, MIN(ean) AS ean
        FROM t_referencias_ean
        GROUP BY id_referencia
    ) e ON e.id_referencia = r.id
    LEFT JOIN v_costo_promedio c
           ON c.id_referencia = r.id
          AND c.ano = YEAR(CURDATE())
          AND c.mes = MONTH(CURDATE())
";

$op = isset($_POST['op']) ? $_POST['op'] : '';

switch ($op) {

  /* ============================================================
   * Detalle por lote y vencimiento
   * ============================================================ */
  case 's_inventario_lote':
    $sql = "SELECT
                v.id_bodega AS bodega_num,
                IFNULL(a.descripcion, '') AS bodega_desc,
                r.id_marca,
                IFNULL(m.descripcion, '') AS proveedor,
                r.codigo,
                r.descripcion,
                IFNULL(r.descripcion_larga, '') AS descripcion_larga,
                IFNULL(v.stock, 0) AS stock,
                IFNULL(v.lote, '') AS lote,
                IFNULL(v.vencimiento, '') AS vencimiento,
                IFNULL(e.ean, '') AS ean,
                IFNULL(c.promedio, 0) AS costo_unitario,
                IFNULL(c.promedio, 0) * IFNULL(v.stock, 0) AS costo_total,
                IFNULL(r.codigo_invima, '') AS codigo_invima,
                IFNULL(r.codigo_cum, '') AS codigo_cum
            $joins
            " . construirFiltros();

    if (postBool('stock')) {
      $sql .= " AND v.stock > 0 ";
    }

    $sql .= " ORDER BY r.descripcion, v.vencimiento ";

    echo json_encode(GenerarArray($sql));
    break;

  /* ============================================================
   * Stock total por referencia y bodega
   * ============================================================ */
  case 's_inventario_total':
    $sql = "SELECT
                v.id_bodega AS bodega_num,
                IFNULL(a.descripcion, '') AS bodega_desc,
                r.id_marca,
                IFNULL(m.descripcion, '') AS proveedor,
                r.codigo,
                r.descripcion,
                IFNULL(r.descripcion_larga, '') AS descripcion_larga,
                SUM(IFNULL(v.stock, 0)) AS stock,
                IFNULL(e.ean, '') AS ean,
                IFNULL(c.promedio, 0) AS costo_unitario,
                IFNULL(c.promedio, 0) * SUM(IFNULL(v.stock, 0)) AS costo_total,
                IFNULL(r.codigo_invima, '') AS codigo_invima,
                IFNULL(r.codigo_cum, '') AS codigo_cum
            $joins
            " . construirFiltros() . "
            GROUP BY
                r.id,
                v.id_bodega,
                a.descripcion,
                r.id_marca,
                m.descripcion,
                r.codigo,
                r.descripcion,
                r.descripcion_larga,
                e.ean,
                c.promedio,
                r.codigo_invima,
                r.codigo_cum ";

    // En el modo total, "con stock" se evalúa sobre la suma de lotes
    if (postBool('stock')) {
      $sql .= " HAVING SUM(IFNULL(v.stock, 0)) > 0 ";
    }

    $sql .= " ORDER BY r.descripcion ";

    echo json_encode(GenerarArray($sql));
    break;

  default:
    echo json_encode(array('error' => true, 'message' => 'Operación no válida'));
    break;
}
