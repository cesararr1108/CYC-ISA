<?php
session_start();
require_once('bd.php');
require_once('funciones.php');
$conn = conectar();

/* =========================================================
 * Helpers del módulo
 * ======================================================= */

// Solo letras, números, guion y guion bajo (códigos de almacén de la sesión)
function ge_limpiarCodigo($valor)
{
  return preg_replace('/[^A-Za-z0-9_\-]/', '', (string)$valor);
}

function ge_fechaValida($fecha)
{
  $d = DateTime::createFromFormat('Y-m-d', (string)$fecha);
  return $d && $d->format('Y-m-d') === $fecha;
}

function ge_swPorMovimiento($tipoMovimiento)
{
  return strtoupper(trim((string)$tipoMovimiento)) === 'EGRESO' ? 9 : 10;
}

function ge_idBodegaSesion()
{
  // Se cachea en sesión por código de almacén (si el usuario cambia de almacén se vuelve a consultar)
  $bodega = ge_limpiarCodigo($_SESSION['Ses_bodega'] ?? '');
  if (isset($_SESSION['Ses_id_bodega_ge'][$bodega])) {
    return intval($_SESSION['Ses_id_bodega_ge'][$bodega]);
  }
  $res = GenerarArray("select id from t_almacenes where almacen = '$bodega' limit 1");
  $id = intval($res[0]['id'] ?? 0);
  $_SESSION['Ses_id_bodega_ge'] = [$bodega => $id];
  return $id;
}

function ge_infoUsuario()
{
  return [
    'id'          => $_SESSION['Ses_id'] ?? 0,
    'usuario'     => $_SESSION['Ses_usuario'] ?? '',
    'bodega'      => $_SESSION['Ses_bodega'] ?? '',
    'bodega_desc' => $_SESSION['Ses_bodega_des'] ?? '',
    'id_bodega'   => ge_idBodegaSesion()
  ];
}

function ge_tiposDocumento($sw)
{
  $sw = intval($sw);
  $bodega = ge_limpiarCodigo($_SESSION['Ses_bodega'] ?? '');
  // Nota: antes había un espacio en ' $bodega' que impedía encontrar los tipos propios del almacén
  return GenerarArray("select id, tipo, descripcion
                       from t_tipos_documentos
                       where almacen in ('0', '$bodega') and sw = $sw
                       order by tipo");
}

function ge_conceptosCaja($idBodega, $sw)
{
  $idBodega = intval($idBodega);
  $tipoConcepto = ($sw == 9) ? 'E' : 'I';
  $filtroBodega = $idBodega > 0 ? "cc.id_bodega in (0, $idBodega)" : "cc.id_bodega = 0";

  return GenerarArray("select
                          cc.id,
                          cc.concepto,
                          upper(ifnull(cc.descripcion,'')) as descripcion,
                          cc.id_bodega,
                          upper(case when cc.id_bodega = 0 then 'TODOS LOS ALMACENES' else ifnull(a.descripcion,'') end) as bodega
                        from t_cajas_conceptos cc
                        left join t_almacenes a on a.id = cc.id_bodega
                        where cc.tipo = '$tipoConcepto' and $filtroBodega
                        order by cc.id_bodega desc, cc.concepto");
}

function ge_catalogos($sw)
{
  return [
    'tipos'     => ge_tiposDocumento($sw),
    'conceptos' => ge_conceptosCaja(ge_idBodegaSesion(), $sw)
  ];
}

function ge_responder($data)
{
  echo json_encode($data);
}

/**
 * Documentos por pagar del tercero: compras (sw 1) y notas débito proveedor (sw 14).
 * Saldo = valor_total - lo ya aplicado en t_transacciones_cruce (valor + descuento + retenciones).
 * Índice recomendado: t_transacciones_cruce (sw, tipo_aplica, numero_aplica)
 *
 * $ids: si se envía, limita la consulta a esos documentos (para validar antes de aplicar).
 */
function ge_documentosPendientes($idTercero, $ids = null)
{
  $idTercero = intval($idTercero);
  if ($idTercero <= 0) return [];

  $filtroIds = '';
  if (is_array($ids)) {
    $ids = array_filter(array_map('intval', $ids));
    if (empty($ids)) return [];
    $filtroIds = ' and d.id in (' . implode(',', $ids) . ')';
  }

  return GenerarArray("select
                          d.id,
                          d.sw,
                          z.tipo,
                          d.numero,
                          date(d.fecha_hora) as fecha,
                          d.valor_total,
                          ifnull(c.aplicado, 0) as aplicado,
                          round(d.valor_total - ifnull(c.aplicado, 0), 2) as saldo
                        from t_transacciones d
                        inner join t_tipos_documentos z on z.id = d.id_tipo_documento
                        left join (
                          select sw, tipo_aplica, numero_aplica,
                                 sum(ifnull(valor,0) + ifnull(descuento,0) + ifnull(ret_ICA,0)
                                     + ifnull(ret_IVA,0) + ifnull(ret_FTE,0)) as aplicado
                          from t_transacciones_cruce
                          where sw in (1, 14)
                          group by sw, tipo_aplica, numero_aplica
                        ) c on c.sw = d.sw and c.tipo_aplica = z.tipo and c.numero_aplica = d.numero
                        where d.id_tercero = $idTercero
                          and d.sw in (1, 14)
                          $filtroIds
                          and round(d.valor_total - ifnull(c.aplicado, 0), 2) > 0
                        order by d.fecha_hora, d.id");
}

/**
 * Valida que cada documento pertenezca al tercero, siga pendiente y que el valor no supere su saldo.
 * Devuelve [error(bool), mensaje, documentos indexados por id con su información].
 */
function ge_validarDocumentos($idTercero, $documentos)
{
  if (!is_array($documentos) || empty($documentos)) {
    return [true, 'No se enviaron documentos para aplicar', []];
  }

  $ids = array_map(function ($d) { return intval($d['id'] ?? 0); }, $documentos);
  $pendientes = [];
  foreach (ge_documentosPendientes($idTercero, $ids) as $row) {
    $pendientes[intval($row['id'])] = $row;
  }

  foreach ($documentos as $doc) {
    $id = intval($doc['id'] ?? 0);
    $valor = round(floatval($doc['valor'] ?? 0), 2);

    if ($valor <= 0) {
      return [true, 'Hay documentos con valor a pagar inválido', []];
    }
    if (!isset($pendientes[$id])) {
      return [true, 'Uno de los documentos ya no está pendiente o no pertenece al tercero', []];
    }
    $saldo = floatval($pendientes[$id]['saldo']);
    if ($valor - $saldo > 0.009) {
      $etiqueta = $pendientes[$id]['tipo'] . ' ' . $pendientes[$id]['numero'];
      return [true, "El valor a pagar de $etiqueta supera su saldo pendiente", []];
    }
  }
  return [false, '', $pendientes];
}

/* =========================================================
 * Operaciones
 * ======================================================= */
$op = $_POST['op'] ?? '';

switch ($op) {

  // Carga inicial en una sola petición: usuario + tipos + conceptos
  case 'init':
    try {
      $sw = ge_swPorMovimiento($_POST['tipoMovimiento'] ?? 'INGRESO');
      ge_responder([
        'usuario'   => ge_infoUsuario(),
        'catalogos' => ge_catalogos($sw)
      ]);
    } catch (\Throwable $th) {
      http_response_code(500);
      ge_responder(['error' => true, 'message' => $th->getMessage()]);
    }
    break;

  // Tipos de documento + conceptos de un movimiento (se cachean en el cliente)
  case 'catalogos-movimiento':
    try {
      ge_responder(ge_catalogos(ge_swPorMovimiento($_POST['tipoMovimiento'] ?? 'INGRESO')));
    } catch (\Throwable $th) {
      http_response_code(500);
      ge_responder(['error' => true, 'message' => $th->getMessage()]);
    }
    break;

  /* --- Se conservan por compatibilidad con otros módulos que los usen --- */
  case 'info':
    try {
      ge_responder(ge_infoUsuario());
    } catch (\Throwable $th) {
      ge_responder(['error' => true, 'message' => $th->getMessage()]);
    }
    break;

  case 'listar-tipo-documento':
    ge_responder(ge_tiposDocumento(ge_swPorMovimiento($_POST['tipoMovimiento'] ?? 'INGRESO')));
    break;

  case 'listar-conceptos-caja':
    $sw = ge_swPorMovimiento($_POST['tipoMovimiento'] ?? 'INGRESO');
    ge_responder(ge_conceptosCaja($_POST['id_bodega'] ?? 0, $sw));
    break;
  /* --------------------------------------------------------------------- */

  case 'listar-ingresos-egresos':
    $id = intval($_SESSION['Ses_id'] ?? 0);
    $fi = $_POST['fecha_inicial'] ?? date('Y-m-d');
    $ff = $_POST['fecha_final'] ?? date('Y-m-d');

    if (!ge_fechaValida($fi) || !ge_fechaValida($ff)) {
      ge_responder([]);
      break;
    }

    $swFiltro = intval($_POST['sw'] ?? 0);
    $condSw = in_array($swFiltro, [9, 10], true) ? "d.sw = $swFiltro" : "d.sw in (9, 10)";

    // Solo los campos que usa la vista (antes se unían 8 tablas sin filtro de fecha)
    // Índice recomendado: t_transacciones (id_usuario, sw, fecha_hora)
    $sql = "select
              d.id as id_transaccion,
              d.sw,
              d.fecha_hora,
              d.numero,
              d.valor_total,
              d.notas,
              z.tipo,
              t.nit,
              upper(coalesce(nullif(trim(t.razon_comercial), ''),
                     trim(concat_ws(' ', t.primer_nombre, t.segundo_nombre, t.primer_apellido, t.segundo_apellido)))) as tercero
            from t_transacciones d
            inner join t_terceros         t on t.id = d.id_tercero
            inner join t_tipos_documentos z on z.id = d.id_tipo_documento
            where d.id_usuario = $id
              and $condSw
              and d.fecha_hora >= '$fi 00:00:00'
              and d.fecha_hora <  date_add('$ff', interval 1 day)
            order by d.fecha_hora desc
            limit 1000";

    ge_responder(GenerarArray($sql));
    break;

  case 'add_transacciones_caja_tmp':
    try {
      $idTransaccion = intval($_POST['id_transaccion'] ?? 0);
      $sw = intval($_POST['sw'] ?? 0);
      $cajas = json_decode($_POST['cajas'] ?? '[]', true);

      if (!$idTransaccion || !is_array($cajas) || empty($cajas)) {
        ge_responder(['error' => true, 'message' => 'No se enviaron cajas o id_transaccion', 'data' => []]);
        break;
      }
      if (!in_array($sw, [9, 10], true)) {
        ge_responder(['error' => true, 'message' => 'Tipo de movimiento no válido', 'data' => []]);
        break;
      }
      if ($sw == 9 && count($cajas) > 1) {
        ge_responder(['error' => true, 'message' => 'Un egreso solo puede tener una caja', 'data' => []]);
        break;
      }

      // Validar todo antes de insertar (evita registros a medias)
      foreach ($cajas as $row) {
        $valor = floatval($row['valor'] ?? 0);
        if (intval($row['idCaja'] ?? 0) <= 0 || $valor <= 0) {
          ge_responder(['error' => true, 'message' => 'Hay cajas sin identificador o con valor inválido', 'data' => []]);
          break 2;
        }
      }

      $idsInsertados = [];
      foreach ($cajas as $row) {
        $valor = floatval($row['valor']);
        $dataInsert = [
          'id_transaccion' => $idTransaccion,
          'id_caja'        => intval($row['idCaja']),
          'valor'          => $valor
        ];

        $idCaja = insertReturnId('t_transacciones_cajas_tmp', $dataInsert, true);
        if ($idCaja > 0) {
          $idsInsertados[] = $idCaja;
          insertReturnId('t_transacciones_pago_tmp', [
            'numero'             => $_POST['numero'] ?? '',
            'tipo'               => ge_limpiarCodigo($_POST['tipo'] ?? ''),
            'id_medio_pago'      => 1,
            'valor'              => $sw == 9 ? $valor * -1 : $valor,
            'tipo_tarjeta'       => '0',
            'voucher'            => '',
            'cambio'             => 0,
            'id_transaccion'     => $idTransaccion,
            'id_transaccion_ref' => $idTransaccion,
            'id_usuario'         => $_SESSION['Ses_id']
          ], true);
        }
      }

      if (count($idsInsertados) === count($cajas)) {
        ge_responder(['error' => false, 'message' => 'Se insertaron correctamente las cajas', 'data' => ['ids' => $idsInsertados]]);
      } else {
        // Si alguna falló se limpian los temporales para no dejar el pago incompleto
        Query("DELETE FROM t_transacciones_cajas_tmp WHERE id_transaccion = $idTransaccion");
        Query("DELETE FROM t_transacciones_pago_tmp WHERE id_transaccion = $idTransaccion");
        ge_responder(['error' => true, 'message' => 'No se pudieron registrar todas las cajas', 'data' => []]);
      }
    } catch (\Throwable $th) {
      ge_responder(['error' => true, 'message' => $th->getMessage(), 'data' => []]);
    }
    break;

  case 'delete_transaccion_caja_tmp':
    try {
      $idTransaccion = intval($_POST['id_transaccion'] ?? 0);
      if ($idTransaccion <= 0) {
        ge_responder(['error' => true, 'message' => 'Es necesario el id de transacción']);
        break;
      }

      // Antes solo se borraban las cajas; los pagos temporales quedaban huérfanos
      $delCajas = Query("DELETE FROM t_transacciones_cajas_tmp WHERE id_transaccion = $idTransaccion");
      Query("DELETE FROM t_transacciones_pago_tmp WHERE id_transaccion = $idTransaccion");

      ge_responder([
        'error'   => !$delCajas,
        'message' => $delCajas
          ? "Registros de la transacción #$idTransaccion eliminados correctamente"
          : "No se encontraron registros para eliminar con id_transaccion = $idTransaccion"
      ]);
    } catch (\Throwable $th) {
      ge_responder(['error' => true, 'message' => 'Error al eliminar: ' . $th->getMessage()]);
    }
    break;

  /* ---------------- Pago de documentos pendientes (egresos) ---------------- */
  case 'documentos-pendientes':
    try {
      ge_responder(ge_documentosPendientes($_POST['id_tercero'] ?? 0));
    } catch (\Throwable $th) {
      ge_responder(['error' => true, 'message' => $th->getMessage()]);
    }
    break;

  case 'validar-documentos':
    try {
      $documentos = json_decode($_POST['documentos'] ?? '[]', true);
      [$error, $mensaje] = ge_validarDocumentos($_POST['id_tercero'] ?? 0, $documentos);
      ge_responder(['error' => $error, 'message' => $mensaje]);
    } catch (\Throwable $th) {
      ge_responder(['error' => true, 'message' => $th->getMessage()]);
    }
    break;

  case 'aplicar-documentos':
    try {
      $idEgreso = intval($_POST['id_transaccion'] ?? 0);
      $idUsuario = intval($_SESSION['Ses_id'] ?? 0);
      $documentos = json_decode($_POST['documentos'] ?? '[]', true);

      // El egreso debe existir, ser sw 9 y pertenecer al usuario; el tercero se toma del egreso
      $egreso = GenerarArray("select d.id, d.id_tercero, d.numero, d.valor_total, z.tipo
                              from t_transacciones d
                              inner join t_tipos_documentos z on z.id = d.id_tipo_documento
                              where d.id = $idEgreso and d.sw = 9 and d.id_usuario = $idUsuario
                              limit 1");
      if (empty($egreso)) {
        ge_responder(['error' => true, 'message' => 'No se encontró el egreso para aplicar los documentos']);
        break;
      }
      $egreso = $egreso[0];

      [$error, $mensaje] = ge_validarDocumentos($egreso['id_tercero'], $documentos);
      if ($error) {
        ge_responder(['error' => true, 'message' => $mensaje]);
        break;
      }

      $pendientes = ge_documentosPendientes($egreso['id_tercero'], array_column($documentos, 'id'));
      $porId = [];
      foreach ($pendientes as $row) $porId[intval($row['id'])] = $row;

      $totalAplicar = array_sum(array_map(function ($d) { return round(floatval($d['valor']), 2); }, $documentos));
      if ($totalAplicar - floatval($egreso['valor_total']) > 0.009) {
        ge_responder(['error' => true, 'message' => 'El total aplicado supera el valor del egreso']);
        break;
      }

      $idsCruce = [];
      foreach ($documentos as $doc) {
        $info = $porId[intval($doc['id'])];
        $idCruce = insertReturnId('t_transacciones_cruce', [
          'id_transaccion' => $idEgreso,
          'tipo'           => $egreso['tipo'],
          'numero'         => $egreso['numero'],
          'sw'             => intval($info['sw']),
          'tipo_aplica'    => $info['tipo'],
          'numero_aplica'  => $info['numero'],
          'valor'          => round(floatval($doc['valor']), 2),
          'descuento'      => 0,
          'ret_ICA'        => 0,
          'ret_IVA'        => 0,
          'ret_FTE'        => 0,
          'fecha'          => date('Y-m-d')
        ], true);

        if ($idCruce > 0) {
          $idsCruce[] = intval($idCruce);
        } else {
          // Si uno falla se revierten los cruces de este egreso para no dejar pagos a medias
          if ($idsCruce) Query('DELETE FROM t_transacciones_cruce WHERE id IN (' . implode(',', $idsCruce) . ')');
          ge_responder(['error' => true, 'message' => 'No se pudo aplicar el pago a ' . $info['tipo'] . ' ' . $info['numero']]);
          break 2;
        }
      }

      ge_responder(['error' => false, 'message' => 'Pago aplicado a ' . count($idsCruce) . ' documento(s)']);
    } catch (\Throwable $th) {
      ge_responder(['error' => true, 'message' => $th->getMessage()]);
    }
    break;

  default:
    http_response_code(400);
    ge_responder(['error' => true, 'message' => 'Operación no válida']);
    break;
}