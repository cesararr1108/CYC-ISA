<?php
session_start();
require_once('bd.php');
require_once('funciones.php');
$conn = conectar();

switch ($_POST["op"]) {
  case "listar-sw-notas":
    $sql = "select sw, descripcion from t_sw where sw in(11,12,13,14)";
    $data = GenerarArray($sql);
    echo json_encode(array(
      'error' => false,
      'message' => 'success',
      'data' => $data
    ));
    break;
  case 'listar-notas':
    $id = $_SESSION["Ses_id"];
    $sql = "SELECT
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
                    a.descripcion as desc_bodega,
                    d.sw,
                    ifnull(z.documento_electronico, 'N') as documento_electronico,
                    ifnull(rd.cufe, '') as cufe
                from t_transacciones d
                inner join t_terceros           t on t.id = d.id_tercero 
                inner join t_terceros_direcciones r on r.id = d.id_tercero_dir
                inner join t_usuarios           u on u.id=d.id_usuario
                inner join t_tipos_documentos   z on z.id=d.id_tipo_documento 
                  left join t_respuestas_dian     rd on rd.tipo = d.tipo and rd.numero = d.numero
                inner join t_paises             p on p.pais = r.codigo_pais 
                inner join t_departamentos s on s.codigo_dpto = r.codigo_dpto  and s.codigo_pais  = r.codigo_pais 
                inner join t_municipios m on m.codigo_pais = r.codigo_pais and m.codigo_dpto  = r.codigo_dpto and m.codigo =r.codigo_mpio
                inner join t_almacenes a on a.id  = d.id_bodega 
                where
                 d.sw in(11,12,13,14) AND
                 IFNULL(d.anulado,0) = 0 AND 
                 d.id_usuario  = $id  ";
    echo json_encode(GenerarArray($sql));
    break;
  case 'buscar-documentos':
    // Parámetros de búsqueda
    $id_tercero = !empty($_POST['id_tercero']) ? $_POST['id_tercero'] : '';
    $fecha_ini = !empty($_POST['fecha_ini']) ? $_POST['fecha_ini'] : '';
    $fecha_fin = !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : '';
    $tipo_doc = !empty($_POST['tipo_doc']) ? $_POST['tipo_doc'] : '';
    $numero = !empty($_POST['numero']) ? $_POST['numero'] : '';


    // Construir condiciones WHERE
    $where = "WHERE d.sw = 1 AND d.valor_aplicado <> d.valor_total AND d.id_tercero = $id_tercero";

    if (!empty($fecha_ini)) {
      $where .= " AND DATE(d.fecha_hora) >= '$fecha_ini'";
    }
    if (!empty($fecha_fin)) {
      $where .= " AND DATE(d.fecha_hora) <= '$fecha_fin'";
    }
    if (!empty($tipo_doc)) {
      $where .= " AND (z.tipo LIKE '%$tipo_doc%' OR z.descripcion LIKE '%$tipo_doc%')";
    }
    if (!empty($numero)) {
      $where .= " AND d.numero LIKE '%$numero%'";
    }


    $sql = "SELECT
              d.id as id_transaccion,
              d.numero,
              d.tipo,
              d.nit,
              d.documento,
              d.id_tercero,
              d.fecha_hora,
              d.valor_total,
              d.valor_aplicado,
              (d.valor_total - d.valor_aplicado) as valor_pendiente,
              t.nit as tercero_nit,
              t.razon_comercial,
              CONCAT(t.primer_nombre,' ',t.segundo_nombre,' ',t.primer_apellido,' ',t.segundo_apellido) as nombre_tercero,
              z.descripcion as tipo_documento
            FROM t_transacciones d
            INNER JOIN t_terceros t ON t.id = d.id_tercero
            INNER JOIN t_tipos_documentos z ON z.id = d.id_tipo_documento
            $where
            ORDER BY d.fecha_hora DESC";

    $data = GenerarArray($sql);
    echo json_encode(array(
      'error' => false,
      'message' => 'success',
      'data' => $data
    ));
    break;
  case 'obtener-detalles':
    // Obtener detalles de una transacción
    $id_transaccion = !empty($_POST['id_transaccion']) ? $_POST['id_transaccion'] : '';

    if (empty($id_transaccion)) {
      echo json_encode(array(
        'error' => true,
        'message' => 'ID transacción requerido',
        'data' => []
      ));
      break;
    }

    $sql = "SELECT
              td.id as id_detalle,
              td.id_transaccion,
              td.id_referencia,
              td.cantidad,
              td.valor_unitario,
              td.costo_unitario,
              td.pcj_iva,
              td.pcj_descuento,
              td.und,
              ref.codigo,
              ref.descripcion,
              ref.costo_unitario as costo_ref,
              m.descripcion as marca,
              (td.cantidad * td.valor_unitario) as costo_total,
              (td.cantidad * td.valor_unitario * td.pcj_iva / 100) as iva_total
            FROM t_transacciones_detalle td
            INNER JOIN t_referencias ref ON ref.id = td.id_referencia
            LEFT JOIN t_marcas m ON m.id = ref.id_marca
            WHERE td.id_transaccion = $id_transaccion
            ORDER BY td.id ASC";

    $data = GenerarArray($sql);
    echo json_encode(array(
      'error' => false,
      'message' => 'success',
      'data' => $data
    ));
    break;
  case 'ajustar-costo-promedio':
    $id_referencia = intval($_POST['id_referencia'] ?? 0);
    $id_bodega     = intval($_POST['id_bodega']     ?? 0);
    $mes           = intval($_POST['mes']           ?? 0);
    $ano           = intval($_POST['ano']           ?? 0);
    $ajuste_costo  = floatval($_POST['ajuste_costo'] ?? 0);
    $id_doc_link   = intval($_POST['id_doc_link']   ?? 0);
    $nuevo_costo   = floatval($_POST['nuevo_costo'] ?? 0);

    if (!$id_referencia || !$id_bodega || !$mes || !$ano || !$id_doc_link) {
      echo json_encode(['error' => true, 'message' => 'Parámetros inválidos para ajuste de costo']);
      break;
    }

    if ($ajuste_costo == 0) {
      echo json_encode(['error' => false, 'message' => 'Sin cambios, el ajuste es cero.']);
      break;
    }

    mysqli_begin_transaction($conn);
    try {
      // 1. Actualizar costo_unitario en el detalle del documento origen
      $sql = "UPDATE t_transacciones_detalle
              SET costo_unitario = $nuevo_costo
              WHERE id_referencia = $id_referencia
                AND id_transaccion = $id_doc_link";
      if (!$conn->query($sql)) {
        throw new Exception($conn->error);
      }

      // 2. Upsert en t_referencias_mov: acumula el ajuste en costo_ent sin tocar cantidades
      $sql = "INSERT INTO t_referencias_mov
                (id_referencia, id_bodega, mes, ano,
                 cant_ini, cant_ent, cant_sal,
                 costo_ini, costo_ent, costo_sal,
                 costo_tras, costo_comp, costo_ven)
              VALUES
                ($id_referencia, $id_bodega, $mes, $ano,
                 0, 0, 0,
                 0, $ajuste_costo, 0,
                 0, 0, 0)
              ON DUPLICATE KEY UPDATE
                costo_ent = costo_ent + $ajuste_costo";
      if (!$conn->query($sql)) {
        throw new Exception($conn->error);
      }

      mysqli_commit($conn);
      echo json_encode(['error' => false, 'message' => 'Costo promedio ajustado correctamente']);
    } catch (Exception $e) {
      mysqli_rollback($conn);
      echo json_encode(['error' => true, 'message' => 'Error al ajustar costo: ' . $e->getMessage()]);
    }
    break;
}
