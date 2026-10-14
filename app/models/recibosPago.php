<?php
/**
 * models/recibosPago.php  (Recibos de caja / ingreso de dinero, sw = 10)
 *
 * Mismas mejoras que models/egresosPago.php:
 *  - Todas las consultas usan sentencias preparadas (antes había SQL injection en
 *    id_tercero, id, fhIni/fhFin, tipo y numero).
 *  - guardar-preliminar valida en servidor (cliente, tipo de documento, sw cobrables,
 *    saldos, duplicados en otros preliminares, retenciones y pagos) y recalcula ajuste /
 *    saldo a favor; ya no confía en los valores del navegador. Todo en una transacción.
 *  - seleccionar-documentos respeta el filtro "estado" (antes se ignoraba), usa rangos de
 *    fecha sargables y agrega el detalle en una subconsulta (ya no oculta los recibos sin detalle).
 *  - d_recibos_pago y contabiliza_recibos_pago solo operan sobre recibos de caja (sw = 10)
 *    pendientes; contabiliza_recibos_pago además cortaba mal las validaciones (emitía dos JSON).
 *  - Los errores internos se registran con error_log; al cliente ya no se devuelve el SQL
 *    ni $conn->error.
 *
 * Modalidades de recibo:
 *  - Recibo con pagos: documentos + uno o más pagos (neto de documentos > 0). La diferencia
 *    hasta ±TOLERANCIA_AJUSTE es ajuste al peso; si los pagos exceden, queda saldo a favor.
 *  - Cruce de documentos (sin pagos): al menos un documento que suma (5, 12) y uno que resta
 *    (4, 10, 11). Debe quedar balanceado (neto = 0, o ajuste al peso dentro de la tolerancia).
 *    Si no cuadra se rechaza indicando cuánto falta o cuánto excede; nunca se crea saldo a
 *    favor ni se castiga saldo sin que exista el par de documentos.
 *  - Anticipo: solo pagos (sin documentos); todo el valor queda como saldo a favor.
 *
 * Tipos de documento (t_sw) que maneja este módulo:
 *    5  ventas                         -> suma (el cliente debe)
 *    12 notas débito clientes          -> suma
 *    4  devoluciones en venta          -> resta
 *    11 notas crédito clientes         -> resta
 *    10 ingresos (saldos a favor)      -> resta
 *  Antes el listado no filtraba por sw (aparecían compras, pedidos, inventarios...) y el
 *  signo se decidía con los sw 2 y 23, que no corresponden a cartera de clientes en t_sw.
 */
session_start();
require_once( 'bd.php' );
require_once( 'funciones.php' );

header( 'Content-Type: application/json; charset=utf-8' );

const SW_RECIBO = 10;
const SW_SUMAN = [ 5, 12 ];
const SW_NEGATIVOS = [ 4, 10, 11 ];
const SW_COBRABLES = [ 4, 5, 10, 11, 12 ];
const TOLERANCIA_AJUSTE = 1000;      // ajuste al peso permitido
const EPS = 0.01;                    // tolerancia de centavos en comparaciones
// En recibos de caja el saldo es bruto: las retenciones las practica el cliente al pagar y se
// registran en el recibo (en egresos, en cambio, el saldo ya viene neto de retenciones).
const SQL_SALDO = "(IFNULL(d.valor_total,0) - IFNULL(d.valor_aplicado,0))";

class ValidacionException extends Exception {}

// ---------------------------------------------------------------------------
// Utilidades
// ---------------------------------------------------------------------------
function responder( array $payload ): void {
  echo json_encode( $payload, JSON_INVALID_UTF8_SUBSTITUTE );
  exit;
}

function responderError( string $op, string $msg ): void {
  switch ( $op ) {
    case 'contabiliza_recibos_pago':
      responder( [ 'success' => false, 'message' => $msg ] );
    case 'guardar-preliminar':
      responder( [ 'error' => true, 'message' => $msg, 'Id' => 0 ] );
    case 'seleccionar-documentos':
    case 'seleccionar-documentos-detalle':
    case 'valida_documento':
      responder( [ 'ok' => false, 'error' => true, 'mensaje' => $msg, 'message' => $msg, 'data' => [] ] );
    default:
      responder( [ 'error' => true, 'message' => $msg ] );
  }
}

/** SELECT preparado -> arreglo asociativo. */
function consultar( mysqli $conn, string $sql, string $types = '', array $params = [] ): array {
  $stmt = $conn->prepare( $sql );
  if ( !$stmt ) {
    throw new RuntimeException( 'Error al preparar consulta: ' . $conn->error );
  }
  try {
    if ( $types !== '' ) {
      $stmt->bind_param( $types, ...$params );
    }
    if ( !$stmt->execute() ) {
      throw new RuntimeException( 'Error al ejecutar consulta: ' . $stmt->error );
    }
    $res = $stmt->get_result();
    return $res ? $res->fetch_all( MYSQLI_ASSOC ) : [];
  } finally {
    $stmt->close();
  }
}

/** INSERT/UPDATE/DELETE preparado -> filas afectadas. */
function ejecutar( mysqli $conn, string $sql, string $types = '', array $params = [] ): int {
  $stmt = $conn->prepare( $sql );
  if ( !$stmt ) {
    throw new RuntimeException( 'Error al preparar sentencia: ' . $conn->error );
  }
  try {
    if ( $types !== '' ) {
      $stmt->bind_param( $types, ...$params );
    }
    if ( !$stmt->execute() ) {
      throw new RuntimeException( 'Error al ejecutar sentencia: ' . $stmt->error );
    }
    return $stmt->affected_rows;
  } finally {
    $stmt->close();
  }
}

function entero( $v ): int {
  return is_numeric( $v ) ? ( int )$v : 0;
}

function decimal( $v ): float {
  return is_numeric( $v ) ? round( ( float )$v, 2 ) : 0.0;
}

function fechaValida( string $f ): bool {
  $d = DateTime::createFromFormat( 'Y-m-d', $f );
  return $d && $d->format( 'Y-m-d' ) === $f;
}

// ---------------------------------------------------------------------------
// Sesión
// ---------------------------------------------------------------------------
$conn = conectar();
if ( !sessionExists() ) {
  responder( [
    'error' => true,
    'message' => 'La sesion se ha cerrado, por favor ingrese nuevamente'
  ] );
}

$op = $_POST[ 'op' ] ?? '';
$enTransaccion = false;
$idCreado = 0;

try {

  switch ( $op ) {

    // -----------------------------------------------------------------------
    case 'listar-tipo-documento':
      $bodega = ( string )( $_SESSION[ 'Ses_bodega' ] ?? '' );
      $tipos = consultar( $conn,
        "SELECT sw, id, tipo, descripcion
           FROM t_tipos_documentos
          WHERE almacen IN ('0', ?)
            AND sw = ?
            AND IFNULL(inactivo,0) = 0
          ORDER BY tipo",
        'si', [ $bodega, SW_RECIBO ] );
      responder( $tipos );

    // -----------------------------------------------------------------------
    case 'listar-documentos':
      $idTercero = entero( $_POST[ 'id_tercero' ] ?? 0 );
      if ( $idTercero <= 0 ) {
        throw new ValidacionException( 'Cliente no válido' );
      }
      $saldoSql = SQL_SALDO;
      $sql = "SELECT
                d.id AS id_transaccion,
                d.sw,
                IFNULL(d.valor_total,0) AS valor_total,
                IFNULL(d.valor_aplicado, 0) AS valor_aplicado,
                $saldoSql AS saldo,
                t.nit AS nit,
                t.razon_comercial AS razon_comercial,
                t.cupo_credito AS cupo_credito,
                d.numero AS numero,
                d.tipo AS tipo,
                t.id AS id_tercero,
                UPPER(t.tratamiento) AS tratamiento,
                d.iva,
                d.retencion_fuente,
                d.retencion_ica,
                d.retencion_iva,
                d.retencion_cree,
                t.id_condicion,
                c.descripcion AS desc_condicion,
                c.dias_gracia,
                c.dias AS dias_vcmto,
                CAST(d.fecha_hora AS DATE) AS fecha,
                d.vencimiento,
                DATEDIFF(CURDATE(), d.vencimiento) AS dias_transcurridos,
                DATEDIFF(CURDATE(), d.fecha_hora) AS dias,
                CASE
                  WHEN c.id IS NOT NULL
                   AND DATEDIFF(CURDATE(), CAST(d.fecha_hora AS DATE))
                       BETWEEN 0 AND (IFNULL(c.dias,0) + IFNULL(c.dias_gracia,0))
                  THEN c.dto
                  ELSE 0
                END AS dcto_calculado,
                IFNULL(d.documento, '') AS documento
              FROM t_transacciones d
              INNER JOIN t_terceros t ON t.id = d.id_tercero
              LEFT  JOIN t_condiciones_pago c ON c.id = d.id_condicion
              WHERE d.id_tercero = ?
                AND d.sw IN (" . implode( ',', SW_COBRABLES ) . ")
                AND IFNULL(d.anulado, 0) = 0
              HAVING saldo > 0
              ORDER BY d.vencimiento, d.fecha_hora, d.numero";
      responder( consultar( $conn, $sql, 'i', [ $idTercero ] ) );

    // -----------------------------------------------------------------------
    case 'guardar-preliminar':
      $pagos = json_decode( $_POST[ 'ArrayPagos' ] ?? '[]', true );
      $docs = json_decode( $_POST[ 'ArrayDocumentos' ] ?? '[]', true );
      if ( !is_array( $pagos ) || !is_array( $docs ) ) {
        throw new ValidacionException( 'Los pagos o documentos enviados no son válidos' );
      }
      if ( !$pagos && !$docs ) {
        throw new ValidacionException( 'Debe agregar documentos para cruzar o al menos un pago' );
      }

      $idTercero = entero( $_POST[ 'id_tercero' ] ?? 0 );
      $idTipo = entero( $_POST[ 'id_tipo' ] ?? 0 );

      $tercero = consultar( $conn, 'SELECT id, nit FROM t_terceros WHERE id = ? LIMIT 1', 'i', [ $idTercero ] );
      if ( !$tercero ) {
        throw new ValidacionException( 'El cliente seleccionado no existe' );
      }

      $tipoDoc = consultar( $conn,
        'SELECT sw FROM t_tipos_documentos WHERE id = ? AND IFNULL(inactivo,0) = 0 LIMIT 1',
        'i', [ $idTipo ] );
      if ( !$tipoDoc || entero( $tipoDoc[ 0 ][ 'sw' ] ) !== SW_RECIBO ) {
        throw new ValidacionException( 'El tipo de documento no es válido para recibos de caja' );
      }

      $conn->begin_transaction();
      $enTransaccion = true;

      // ---- Validar documentos (con bloqueo de fila para evitar doble aplicación) ----
      $saldoSql = SQL_SALDO;
      $vistos = [];
      $totalDocs = 0.0;
      $haySuman = false;
      $hayRestan = false;
      $docsOk = [];

      foreach ( $docs as $doc ) {
        $tipo = trim( ( string )( $doc[ 'tipo' ] ?? '' ) );
        $numero = trim( ( string )( $doc[ 'numero' ] ?? '' ) );
        $sw = entero( $doc[ 'sw' ] ?? 0 );
        $valor = decimal( $doc[ 'valor' ] ?? 0 );
        $dcto = decimal( $doc[ 'dcto' ] ?? 0 );
        $retIva = decimal( $doc[ 'retIva' ] ?? 0 );
        $retIca = decimal( $doc[ 'retIca' ] ?? 0 );
        $retFte = decimal( $doc[ 'retFuente' ] ?? 0 );
        $ref = "$tipo-$numero";

        if ( $tipo === '' || $numero === '' || $sw <= 0 ) {
          throw new ValidacionException( 'Hay un documento con datos incompletos' );
        }
        if ( !in_array( $sw, SW_COBRABLES, true ) ) {
          throw new ValidacionException( "El tipo de documento sw=$sw no se puede aplicar en recibos de caja ($ref)" );
        }
        if ( isset( $vistos[ "$sw|$tipo|$numero" ] ) ) {
          throw new ValidacionException( "El documento $ref está repetido" );
        }
        $vistos[ "$sw|$tipo|$numero" ] = true;

        if ( $valor <= 0 || $dcto < 0 || $retIva < 0 || $retIca < 0 || $retFte < 0 ) {
          throw new ValidacionException( "Valores inválidos en el documento $ref" );
        }

        $fila = consultar( $conn,
          "SELECT $saldoSql AS saldo
             FROM t_transacciones d
            WHERE d.id_tercero = ? AND d.sw = ? AND d.tipo = ? AND d.numero = ?
              AND IFNULL(d.anulado,0) = 0
            LIMIT 1 FOR UPDATE",
          'iiss', [ $idTercero, $sw, $tipo, $numero ] );
        if ( !$fila ) {
          throw new ValidacionException( "El documento $ref no existe o no pertenece al cliente" );
        }
        $saldo = ( float )$fila[ 0 ][ 'saldo' ];

        if ( ( $valor - $dcto ) > $saldo + EPS ) {
          throw new ValidacionException( "El abono del documento $ref supera su saldo actual (" . number_format( $saldo, 2 ) . ')' );
        }
        $netoDoc = $valor - $dcto - $retIva - $retIca - $retFte;
        if ( $netoDoc < -EPS ) {
          throw new ValidacionException( "Descuento y retenciones superan el abono del documento $ref" );
        }

        $pend = consultar( $conn,
          "SELECT p.id_rc
             FROM t_recibos_pago_det d
            INNER JOIN t_recibos_pago p ON p.id_rc = d.id_rc
            WHERE p.estado = 'P' AND d.tipo_doc = 'D'
              AND d.tipo = ? AND d.numero = ? AND d.sw = ?
            LIMIT 1",
          'ssi', [ $tipo, $numero, $sw ] );
        if ( $pend ) {
          throw new ValidacionException( "El documento $ref ya está en el preliminar #" . $pend[ 0 ][ 'id_rc' ] );
        }

        if ( in_array( $sw, SW_NEGATIVOS, true ) ) {
          $hayRestan = true;
          $totalDocs -= $netoDoc;
        } else {
          $haySuman = true;
          $totalDocs += $netoDoc;
        }
        $docsOk[] = [ 'tipo' => $tipo, 'numero' => ( int )$numero, 'sw' => $sw,
          'valor' => $valor, 'dcto' => $dcto, 'retIva' => $retIva, 'retIca' => $retIca, 'retFte' => $retFte ];
      }

      // ---- Validar pagos ----
      $totalPagos = 0.0;
      $pagosOk = [];
      foreach ( $pagos as $pago ) {
        $modelo = trim( ( string )( $pago[ 'modelo' ] ?? '' ) );
        $cuenta = trim( ( string )( $pago[ 'cuenta' ] ?? '' ) );
        $valor = decimal( $pago[ 'valor' ] ?? 0 );
        if ( $modelo === '' || $cuenta === '' || $valor <= 0 ) {
          throw new ValidacionException( 'Hay un pago con caja/banco o valor inválido' );
        }
        $totalPagos += $valor;
        $pagosOk[] = [ 'modelo' => $modelo, 'desc' => trim( ( string )( $pago[ 'desc' ] ?? '' ) ),
          'valor' => $valor, 'cuenta' => $cuenta ];
      }

      // ---- Cruce / ajuste / saldo a favor calculados en servidor ----
      $ajuste = 0.0;
      $saldoFavor = 0.0;
      $dif = round( $totalPagos - $totalDocs, 2 ); // pagos - documentos netos

      if ( $docsOk && !$pagosOk ) {
        // Cruce de documentos sin movimiento de dinero
        if ( !$haySuman || !$hayRestan ) {
          throw new ValidacionException( 'Un cruce sin pagos requiere al menos un documento que suma (venta o nota débito) y uno que resta (devolución, nota crédito o ingreso)' );
        }
        if ( abs( $dif ) > TOLERANCIA_AJUSTE ) {
          $difTxt = number_format( abs( $dif ), 2 );
          if ( $totalDocs > 0 ) {
            throw new ValidacionException( "El cruce no está balanceado: faltan $difTxt por cubrir. Agregue un pago o reduzca el abono de los documentos que suman" );
          }
          throw new ValidacionException( "El cruce no está balanceado: los documentos que restan exceden en $difTxt. Reduzca el abono de esos documentos (el saldo restante queda disponible para otro cruce)" );
        }
        $ajuste = $dif;
      } elseif ( $docsOk ) {
        // Recibo con documentos y pagos
        if ( $totalDocs <= 0 ) {
          throw new ValidacionException( 'Con pagos registrados el valor neto de los documentos debe ser mayor a cero. Para cruzar documentos elimine los pagos' );
        }
        if ( $dif < -TOLERANCIA_AJUSTE ) {
          throw new ValidacionException( 'Los pagos no cubren los documentos: la diferencia es demasiado grande para compensar' );
        }
        if ( abs( $dif ) <= TOLERANCIA_AJUSTE ) {
          $ajuste = $dif;
        } else {
          $saldoFavor = $dif;
        }
      } else {
        // Solo pagos: todo el valor queda como saldo a favor (anticipo)
        if ( $totalPagos <= TOLERANCIA_AJUSTE ) {
          throw new ValidacionException( 'Sin documentos, el pago debe ser mayor a ' . number_format( TOLERANCIA_AJUSTE, 2 ) . ' para generar saldo a favor' );
        }
        $saldoFavor = round( $totalPagos, 2 );
      }

      // ---- Encabezado ----
      $fields = [
        'usuario_id' => intval( $_SESSION[ 'Ses_id' ] ),
        'nit' => $tercero[ 0 ][ 'nit' ],
        'adjunto' => 0,
        'referencia_doc' => mb_substr( trim( ( string )( $_POST[ 'referencia_doc' ] ?? '' ) ), 0, 100 ),
        'referencia_ref' => mb_substr( trim( ( string )( $_POST[ 'referencia_ref' ] ?? '' ) ), 0, 100 ),
        'notas' => mb_substr( trim( ( string )( $_POST[ 'notas' ] ?? '' ) ), 0, 200 ),
        'ajuste' => $ajuste,
        'saldo' => $saldoFavor,
        'sw' => SW_RECIBO,
        'id_tipo' => $idTipo,
        'id_tercero' => $idTercero,
        'id_bodega' => intval( $_SESSION[ 'Ses_id_bodega' ] ?? 0 ),
        'id_tercero_dir' => entero( $_POST[ 'id_tercero_dir' ] ?? 0 ),
        'id_condicion' => entero( $_POST[ 'id_condicion' ] ?? 0 )
      ];

      $idCreado = ( int )insertReturnId( 't_recibos_pago', $fields, true );
      if ( $idCreado <= 0 ) {
        throw new RuntimeException( 'No se pudo insertar el encabezado del recibo de caja' );
      }

      // ---- Detalle: pagos ----
      foreach ( $pagosOk as $p ) {
        insertReturnId( 't_recibos_pago_det', [
          'id_rc' => $idCreado,
          'modelo' => $p[ 'modelo' ],
          'descripcion' => $p[ 'desc' ],
          'valor' => $p[ 'valor' ],
          'cuenta' => $p[ 'cuenta' ],
          'tipo_doc' => 'P'
        ], false );
      }

      // ---- Detalle: documentos ----
      foreach ( $docsOk as $d ) {
        insertReturnId( 't_recibos_pago_det', [
          'id_rc' => $idCreado,
          'tipo' => $d[ 'tipo' ],
          'numero' => $d[ 'numero' ],
          'sw' => $d[ 'sw' ],
          'valor' => $d[ 'valor' ],
          'descuento' => $d[ 'dcto' ],
          'tipo_doc' => 'D',
          'ret_IVA' => $d[ 'retIva' ],
          'ret_ICA' => $d[ 'retIca' ],
          'ret_FTE' => $d[ 'retFte' ]
        ], false );
      }

      $conn->commit();
      $enTransaccion = false;

      responder( [
        'error' => false,
        'message' => 'Documento preliminar creado con éxito',
        'Id' => $idCreado
      ] );

    // -----------------------------------------------------------------------
    case 'seleccionar-documentos':
      $fechaIni = ( string )( $_POST[ 'fhIni' ] ?? '' );
      $fechaFin = ( string )( $_POST[ 'fhFin' ] ?? '' );
      if ( !fechaValida( $fechaIni ) || !fechaValida( $fechaFin ) ) {
        throw new ValidacionException( 'Rango de fechas no válido' );
      }
      if ( $fechaIni > $fechaFin ) {
        throw new ValidacionException( 'La fecha inicial no puede ser mayor a la final' );
      }

      $estado = $_POST[ 'estado' ] ?? 'T';
      $filtroEstado = '';
      if ( $estado === 'P' ) {
        $filtroEstado = " AND r.estado = 'P'";
      } elseif ( $estado === 'G' ) {
        $filtroEstado = " AND IFNULL(r.estado,'') <> 'P'";
      }

      $sql = "SELECT
                s.tipo,
                CAST(r.fecha_hora AS DATE) AS fecha,
                CAST(r.fecha_hora AS TIME) AS hora,
                r.id_rc,
                IFNULL(r.sw,0) AS sw,
                IFNULL(r.numero,'') AS numero,
                u.id,
                UPPER(u.usuario) AS usuario,
                r.nit,
                t.tratamiento AS nombres,
                t.razon_comercial,
                r.estado,
                r.referencia_doc,
                r.referencia_ref,
                r.adjunto,
                r.formato_adjunto,
                r.autoriza_id,
                IFNULL(a.usuario,'') AS usuario_autoriza,
                r.notas,
                IFNULL(tot.valor,0) AS valor,
                IFNULL(tot.descuento,0) AS descuento,
                IFNULL(tot.n_pagos,0) AS n_pagos,
                IFNULL(tot.n_docs,0) AS n_docs,
                r.id_transaccion
              FROM t_recibos_pago r
              INNER JOIN t_tipos_documentos s ON s.id = r.id_tipo
              INNER JOIN t_terceros t ON t.nit = r.nit
              INNER JOIN t_usuarios u ON u.id = r.usuario_id
              LEFT  JOIN t_usuarios a ON a.id = r.autoriza_id
              LEFT  JOIN (
                    SELECT d.id_rc,
                           SUM(CASE WHEN d.tipo_doc = 'P' THEN d.valor ELSE 0 END)     AS valor,
                           SUM(CASE WHEN d.tipo_doc = 'D' THEN d.descuento ELSE 0 END) AS descuento,
                           SUM(CASE WHEN d.tipo_doc = 'P' THEN 1 ELSE 0 END)           AS n_pagos,
                           SUM(CASE WHEN d.tipo_doc = 'D' THEN 1 ELSE 0 END)           AS n_docs
                      FROM t_recibos_pago_det d
                     INNER JOIN t_recibos_pago x ON x.id_rc = d.id_rc
                     WHERE x.fecha_hora >= ? AND x.fecha_hora < DATE_ADD(?, INTERVAL 1 DAY)
                       AND IFNULL(x.sw,0) = ?
                     GROUP BY d.id_rc
                   ) tot ON tot.id_rc = r.id_rc
              WHERE r.fecha_hora >= ? AND r.fecha_hora < DATE_ADD(?, INTERVAL 1 DAY)
                AND IFNULL(r.sw,0) = ?
                $filtroEstado
              ORDER BY r.fecha_hora DESC, r.id_rc DESC";
      $data = consultar( $conn, $sql, 'ssissi',
        [ $fechaIni, $fechaFin, SW_RECIBO, $fechaIni, $fechaFin, SW_RECIBO ] );
      responder( [ 'ok' => true, 'mensaje' => '', 'data' => $data ] );

    // -----------------------------------------------------------------------
    case 'seleccionar-documentos-detalle':
      $id = entero( $_POST[ 'id' ] ?? 0 );
      if ( $id <= 0 ) {
        throw new ValidacionException( 'Id de recibo no válido' );
      }
      $data = consultar( $conn,
        "SELECT d.id, d.id_rc, d.valor, d.descuento, d.modelo, d.descripcion, d.cuenta,
                d.tipo, d.numero, d.sw, d.tipo_doc, d.ret_FTE, d.ret_ICA, d.ret_IVA
           FROM t_recibos_pago_det d
          INNER JOIN t_recibos_pago r ON r.id_rc = d.id_rc AND IFNULL(r.sw,0) = ?
          WHERE d.id_rc = ?
          ORDER BY d.id",
        'ii', [ SW_RECIBO, $id ] );
      responder( [ 'ok' => true, 'mensaje' => '', 'data' => $data ] );

    // -----------------------------------------------------------------------
    case 'd_recibos_pago':
      $id = entero( $_POST[ 'id' ] ?? 0 );
      if ( $id <= 0 ) {
        throw new ValidacionException( 'Es necesario un id de transacción válido' );
      }

      $conn->begin_transaction();
      $enTransaccion = true;

      $rc = consultar( $conn,
        'SELECT estado, id_transaccion, IFNULL(sw,0) AS sw FROM t_recibos_pago WHERE id_rc = ? FOR UPDATE',
        'i', [ $id ] );
      if ( !$rc || entero( $rc[ 0 ][ 'sw' ] ) !== SW_RECIBO ) {
        throw new ValidacionException( "El preliminar #$id no existe" );
      }
      if ( ( $rc[ 0 ][ 'estado' ] ?? '' ) !== 'P' || !empty( $rc[ 0 ][ 'id_transaccion' ] ) ) {
        throw new ValidacionException( 'Solo se pueden eliminar preliminares pendientes (sin contabilizar)' );
      }

      ejecutar( $conn, 'DELETE FROM t_recibos_pago_det WHERE id_rc = ?', 'i', [ $id ] );
      ejecutar( $conn, 'DELETE FROM t_recibos_pago WHERE id_rc = ?', 'i', [ $id ] );

      $conn->commit();
      $enTransaccion = false;

      responder( [ 'error' => false, 'message' => "Transacción #{$id} eliminada correctamente" ] );

    // -----------------------------------------------------------------------
    case 'valida_documento':
      $tipo = trim( ( string )( $_POST[ 'tipo' ] ?? '' ) );
      $numero = trim( ( string )( $_POST[ 'numero' ] ?? '' ) );
      if ( $tipo === '' || $numero === '' ) {
        throw new ValidacionException( 'Tipo y número del documento son obligatorios' );
      }
      $data = consultar( $conn,
        "SELECT d.id_rc
           FROM t_recibos_pago_det d
          INNER JOIN t_recibos_pago p ON p.id_rc = d.id_rc
          WHERE p.estado = 'P' AND d.numero = ? AND d.tipo = ?
          LIMIT 1",
        'ss', [ $numero, $tipo ] );
      responder( [ 'ok' => true, 'data' => $data ] );

    // -----------------------------------------------------------------------
    case 'contabiliza_recibos_pago':
      $reciboId = entero( $_POST[ 'recibo_id' ] ?? 0 );
      $autorizaId = entero( $_SESSION[ 'Ses_id' ] ?? 0 );
      if ( $reciboId <= 0 ) {
        throw new ValidacionException( 'recibo_id es obligatorio para cruces de pagos' );
      }
      if ( $autorizaId <= 0 ) {
        throw new ValidacionException( 'No se pudo identificar el usuario que autoriza' );
      }

      $rc = consultar( $conn,
        'SELECT estado, id_transaccion, IFNULL(sw,0) AS sw FROM t_recibos_pago WHERE id_rc = ? LIMIT 1',
        'i', [ $reciboId ] );
      if ( !$rc || entero( $rc[ 0 ][ 'sw' ] ) !== SW_RECIBO ) {
        throw new ValidacionException( "El preliminar #$reciboId no existe" );
      }
      if ( ( $rc[ 0 ][ 'estado' ] ?? '' ) !== 'P' || !empty( $rc[ 0 ][ 'id_transaccion' ] ) ) {
        throw new ValidacionException( "El preliminar #$reciboId ya fue contabilizado" );
      }

      $stmt = $conn->prepare( 'CALL p_cruces_pagos(?, ?, @transaccion_id)' );
      if ( !$stmt ) {
        throw new RuntimeException( 'Error al preparar SP: ' . $conn->error );
      }
      try {
        $stmt->bind_param( 'ii', $reciboId, $autorizaId );
        if ( !$stmt->execute() ) {
          throw new RuntimeException( 'Error al ejecutar SP: ' . $stmt->error );
        }
        // Limpiar TODOS los resultsets del SP (obligatorio en mysqli)
        do {
          if ( $result = $stmt->get_result() ) {
            $result->free();
          }
        } while ( $stmt->more_results() && $stmt->next_result() );
      } finally {
        $stmt->close();
      }

      $transaccionId = null;
      $res = $conn->query( 'SELECT @transaccion_id AS transaccion_id' );
      if ( $res ) {
        $row = $res->fetch_assoc();
        $transaccionId = $row[ 'transaccion_id' ] ?? null;
        $res->free();
      }
      if ( empty( $transaccionId ) ) {
        throw new RuntimeException( 'p_cruces_pagos no devolvió el id de la transacción' );
      }

      responder( [
        'success' => true,
        'message' => 'Recibo de caja creado correctamente',
        'data' => [ 'transaccion_id' => $transaccionId ]
      ] );

    // -----------------------------------------------------------------------
    default:
      responderError( $op, 'Operación no válida' );
  }

} catch ( ValidacionException $e ) {

  if ( $enTransaccion ) {
    $conn->rollback();
  }
  responderError( $op, $e->getMessage() );

} catch ( Throwable $e ) {

  if ( $enTransaccion ) {
    $conn->rollback();
  }
  // Compensación por si insertReturnId usa otra conexión o las tablas no son transaccionales
  if ( $idCreado > 0 ) {
    try {
      ejecutar( $conn, 'DELETE FROM t_recibos_pago_det WHERE id_rc = ?', 'i', [ $idCreado ] );
      ejecutar( $conn, 'DELETE FROM t_recibos_pago WHERE id_rc = ?', 'i', [ $idCreado ] );
    } catch ( Throwable $ignorar ) {
    }
  }
  error_log( "[recibosPago:$op] " . $e->getMessage() );
  responderError( $op, 'Ocurrió un error inesperado, intente nuevamente o contacte a soporte' );
}