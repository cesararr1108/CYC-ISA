<?php

function crearSession($data)
{
  $claves = array_keys($data);
  foreach ($claves as $c) {
    $_SESSION["Ses_" . $c] = $data[$c];
  }
}

function destruirSession()
{
  session_start(); // Inicia la sesión
  foreach ($_SESSION as $key => $value) {
    unset($_SESSION[$key]);
  }
  session_destroy(); // Destruye la sesión por completo
  echo "<script> window.location = 'login.php'; </script>";
}

function encriptar_password($clave)
{
  return password_hash($clave, PASSWORD_DEFAULT);
}

function sessionExists()
{
  return isset($_SESSION['Ses_id']);
}

function obtenerUsuarioAuditoria($usuarioId = null)
{
  if ($usuarioId !== null && (int)$usuarioId > 0) {
    return (int)$usuarioId;
  }

  if (session_status() === PHP_SESSION_NONE) {
    @session_start();
  }

  if (isset($_SESSION['Ses_id']) && (int)$_SESSION['Ses_id'] > 0) {
    return (int)$_SESSION['Ses_id'];
  }

  if (isset($_SESSION['Ses_id_usuario']) && (int)$_SESSION['Ses_id_usuario'] > 0) {
    return (int)$_SESSION['Ses_id_usuario'];
  }

  return 0;
}

function normalizarDetalleAuditoria($detalle)
{
  if ($detalle === null) {
    return null;
  }

  if (is_array($detalle) || is_object($detalle)) {
    return json_encode($detalle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  }

  return (string)$detalle;
}

function registrarAuditoria($accion, $tablaAfectada, $idAfectado = null, $detalle = null, $usuarioId = null, $conn = null)
{
  try {
    $usuarioId = obtenerUsuarioAuditoria($usuarioId);
    if ($usuarioId <= 0) {
      return false;
    }

    $cerrarConexion = false;
    if (!$conn) {
      $conn = conectar();
      $cerrarConexion = true;
    }

    $accion = substr(strtoupper(trim((string)$accion)), 0, 50);
    $tablaAfectada = substr(trim((string)$tablaAfectada), 0, 50);
    $detalleNormalizado = normalizarDetalleAuditoria($detalle);

    if ($idAfectado === null || $idAfectado === '') {
      $sql = "INSERT INTO t_auditoria (usuario_id, accion, tabla_afectada, id_afectado, detalle)
              VALUES (?, ?, ?, NULL, ?)";
      $stmt = $conn->prepare($sql);
      if (!$stmt) {
        if ($cerrarConexion) {
          $conn->close();
        }
        return false;
      }
      $stmt->bind_param("isss", $usuarioId, $accion, $tablaAfectada, $detalleNormalizado);
    } else {
      $idAfectado = (int)$idAfectado;
      $sql = "INSERT INTO t_auditoria (usuario_id, accion, tabla_afectada, id_afectado, detalle)
              VALUES (?, ?, ?, ?, ?)";
      $stmt = $conn->prepare($sql);
      if (!$stmt) {
        if ($cerrarConexion) {
          $conn->close();
        }
        return false;
      }
      $stmt->bind_param("issis", $usuarioId, $accion, $tablaAfectada, $idAfectado, $detalleNormalizado);
    }

    $ok = $stmt->execute();
    $stmt->close();

    if ($cerrarConexion) {
      $conn->close();
    }

    return $ok;
  } catch (Throwable $e) {
    return false;
  }
}

function registrarAuditoriaCambio($accion, $tablaAfectada, $idAfectado, $estadoAnterior, $estadoNuevo, $extra = array(), $usuarioId = null, $conn = null)
{
  $detalle = array(
    'estado_anterior' => $estadoAnterior,
    'estado_nuevo' => $estadoNuevo
  );

  if (is_array($extra) && count($extra) > 0) {
    $detalle['extra'] = $extra;
  }

  return registrarAuditoria($accion, $tablaAfectada, $idAfectado, $detalle, $usuarioId, $conn);
}

function filtrosBusquedaTerceros($texto, $alias)
{
  // Remove extra spaces in the input text
  $newcadena = implode(" ", explode(" ", $texto));

  // Tokenize the cleaned text
  $textoArray = explode(" ", $newcadena);

  // Generate SQL condition
  $new_str = "";
  foreach ($textoArray as $word) {
    if (!empty($new_str)) {
      $new_str .= " AND ";
    }

    $new_str .= "CONCAT($alias.nit, $alias.tratamiento, $alias.razon_comercial) LIKE '%" . $word . "%'";
  }

  return $new_str;
}

function filtrosBusquedaReferencias($texto, $alias)
{
  // Remove extra spaces in the input text
  $newcadena = implode(" ", explode(" ", $texto));

  // Tokenize the cleaned text
  $textoArray = explode(" ", $newcadena);

  // Generate SQL condition
  $new_str = "";
  foreach ($textoArray as $word) {
    if (!empty($new_str)) {
      $new_str .= " AND ";
    }

    $new_str .= "CONCAT($alias.codigo, ifnull($alias.descripcion,''), ifnull($alias.descripcion_larga,'')) LIKE '%" . $word . "%'";
  }

  return $new_str;
}

function numeroSiguiente($tipo_id, $tabla = 't_transacciones_tmp')
{

  $siguiente = GenerarArray("SELECT 
                            IFNULL(max(numero),0)+ 1 as siguiente
                          FROM $tabla
                          WHERE id_tipo_documento='$tipo_id'");

  if (count($siguiente) == 0) {
    return 0;
  }


  return $siguiente[0]["siguiente"];
}


function aumentarConsecutivo($id, $numero)
{
  $update = Query("update t_tipos_documentos set rango_incial = " . $numero . " where id=" . $id);
}

function actualizarTotalesEncabezado($id_transaccion)
{

  $conn = conectar();
  $sql = "CALL ActualizarTotalTransaccion(?)";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $id_transaccion);
  $stmt->execute();

  $result = GenerarArray("SELECT valor_total,descuento,iva FROM  t_transacciones_tmp where  id=$id_transaccion");
  return $result;
}

function actualizarTotalesEncabezadoPedido($id_pedido)
{

  $conn = conectar();
  $sql = "CALL actualizarTotalesEncabezadoPedido(?)";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $id_pedido);
  $stmt->execute();

  $result = GenerarArray("SELECT valor_total,descuento,iva FROM  t_pedidos where  id=$id_pedido");
  return $result;
}

function validarDependencias($tablas, $campo, $id)
{
  $sw = true;
  foreach ($tablas as $tab) {
    $existe = GenerarArray("SELECT * FROM $tab WHERE $campo=$id LIMIT 1");
    if (count($existe) > 0) {
      $sw = false;
    }
  }
  return $sw;
}

function fefo($id_referencia, $cantidad, $bodega)
{
  $result = array();

  $stock = GenerarArray("
          select 
           stock,
           r.codigo
          from v_stock_total v
          inner join t_referencias r on r.id=v.id_referencia
          where id_referencia = $id_referencia and bodega=$bodega ", '');

  if ($stock[0]["stock"] < $cantidad) {
    echo json_encode(array(
      'ok' => false,
      'message' => " El codigo " . $stock[0]["codigo"] . " no tien stock suficiente , por favor valide el inventario y vuelva a intentarlo",
      'items' => []
    ));
    return;
  }

  // Obtener los lotes disponibles para ese código
  $query_lotes = "
                  SELECT 
                      vsl.id_referencia, 
                      vsl.stock, 
                      vsl.lote, 
                      vsl.vencimiento , 
                      vsl.id_lote
                  FROM 
                    v_referencias_stock_lotes vsl
                  WHERE 
                          vsl.id_referencia = '$id_referencia' and  vsl.bodega=$bodega
                  ORDER BY 
                      vsl.vencimiento ASC
              ";
  $stmt_lotes = GenerarArray($query_lotes, '');

  $cantidad_restante = $cantidad;

  // Insertar conforme a la disponibilidad de los lotes 
  /**
   * devuelve un array con cantidad,id_lote,lote,vencimiento
   */
  foreach ($stmt_lotes as $lote) {

    $stock_disponible = $lote['stock'];
    $lote_codigo = $lote['lote'];
    $vencimiento = $lote['vencimiento'];
    $id_lote = $lote['id_lote'];

    // Verificar cuántos lotes podemos tomar para cumplir con el pedido
    if ($cantidad_restante > 0) {

      $cantidad_a_asignar = min($cantidad_restante, $stock_disponible);

      $cantidad_restante -= $cantidad_a_asignar;

      $result[] = array(
        'id_referencia' => $$lote['id_referencia'],
        'cantidad' => $cantidad_a_asignar,
        'lote' => $lote_codigo,
        'vencimiento' => $vencimiento,
        'id_lote' => $id_lote
      );

      if ($cantidad_restante <= 0) {
        break;
      }
    }
  }

  return array(
    'ok' => true,
    'message' => "Success",
    'items' => $result
  );
}

function generarMovimientoContable(int $sw, int $id_transaccion, ?mysqli $conn = null): array
{
  static $procedimientos = [
    1  => 'add_movimiento_compras',             // Compras
    2  => 'add_movimiento_ajuste_inv',          // Entradas de inventario
    4  => 'add_movimiento_devolucion',          // Devolución en ventas
    5  => 'add_movimiento_ventas',              // Ventas
    6  => 'add_movimiento_ajuste_inv',          // Salidas de inventario
    7  => 'add_movimiento_devolucion_compras',  // Devolución en compras
    9  => 'add_movimiento_egresos',             // Egresos
    10 => 'add_movimiento_ingresos',            // Ingresos (RC)
    11 => 'add_movimiento_nc_cliente',          // Nota crédito cliente
    12 => 'add_movimiento_nd_cliente',          // Nota débito cliente
    13 => 'add_movimiento_nc_proveedor',        // Nota crédito proveedor
    14 => 'add_movimiento_nd_proveedor',        // Nota débito proveedor
  ];

  if (!isset($procedimientos[$sw])) {
    return [
      'success' => false,
      'message' => "El tipo de documento (sw $sw) no tiene contabilización configurada."
    ];
  }
  if ($id_transaccion <= 0) {
    return ['success' => false, 'message' => 'Documento no válido.'];
  }

  $transaccionPropia = ($conn === null);
  if ($transaccionPropia) {
    $conn = conectar();
  }

  try {
    if ($transaccionPropia) {
      $conn->begin_transaction();
    }

    $stmt = $conn->prepare("CALL {$procedimientos[$sw]}(?)");
    if (!$stmt) {
      throw new RuntimeException($conn->error);
    }
    $stmt->bind_param('i', $id_transaccion);
    if (!$stmt->execute()) {
      $err = $stmt->error;
      $stmt->close();
      throw new RuntimeException($err);
    }
    // Un CALL puede devolver varios resultados: hay que consumirlos todos
    // antes de usar la conexión de nuevo.
    do {
      $res = $stmt->get_result();
      if ($res) {
        $res->free();
      }
    } while ($stmt->more_results() && $stmt->next_result());
    $stmt->close();

    // Verificación del asiento generado
    $chk = $conn->prepare(
      "SELECT COUNT(*) AS lineas,
              IFNULL(SUM(debito), 0)  AS debito,
              IFNULL(SUM(credito), 0) AS credito
         FROM t_movimiento_contable
        WHERE id_transaccion = ?"
    );
    $chk->bind_param('i', $id_transaccion);
    $chk->execute();
    $tot = $chk->get_result()->fetch_assoc();
    $chk->close();

    if ($transaccionPropia) {
      $conn->commit();
    }

    $lineas     = (int) $tot['lineas'];
    $diferencia = round((float) $tot['credito'] - (float) $tot['debito'], 2);

    if ($lineas === 0) {
      return [
        'success'    => false,
        'message'    => 'El documento no generó movimiento contable. Verifique que no esté anulado y que tenga valores.',
        'lineas'     => 0,
        'diferencia' => 0
      ];
    }
    if ($diferencia != 0.0) {
      return [
        'success'    => true,
        'message'    => 'Contabilización generada con una diferencia de $' .
          number_format(abs($diferencia), 2, ',', '.') .
          ' entre débitos y créditos. Revise los parámetros contables.',
        'lineas'     => $lineas,
        'diferencia' => $diferencia
      ];
    }
    return [
      'success'    => true,
      'message'    => 'Contabilización generada correctamente.',
      'lineas'     => $lineas,
      'diferencia' => 0
    ];
  } catch (Throwable $e) {
    if ($transaccionPropia) {
      $conn->rollback();
    }
    error_log("[generarMovimientoContable] sw=$sw id=$id_transaccion: " . $e->getMessage());
    if (!$transaccionPropia) {
      // Con conexión del llamador: se relanza para que él revierta su transacción
      throw $e;
    }
    return [
      'success' => false,
      'message' => 'Error al generar la contabilización: ' . $e->getMessage()
    ];
  }
}

function numeroALetras($numero, $moneda = 'PESOS', $centimos = 'CENTAVOS')
{
  $formatter = new NumberFormatter("es", NumberFormatter::SPELLOUT);

  // Separar parte entera y decimal
  $partes = explode('.', number_format($numero, 2, '.', ''));
  $entero = intval($partes[0]);
  $decimal = intval($partes[1]);

  // Convertir parte entera
  $texto = mb_strtoupper($formatter->format($entero), "UTF-8") . " " . $moneda;

  // Convertir decimales (si existen)
  if ($decimal > 0) {
    $texto .= " CON " . mb_strtoupper($formatter->format($decimal), "UTF-8") . " " . $centimos;
  }

  return $texto;
}

function validarSuscripcion($conn, $db_empresa)
{
  //Consulta segura con prepared statement
  $stmt = $conn->prepare("
        SELECT fecha_vencimiento, dias_gracia, estado_servicio
        FROM t_empresas
        WHERE activo = 1 AND db_name = ?
        LIMIT 1
    ");
  $stmt->bind_param("s", $db_empresa);
  $stmt->execute();
  $result = $stmt->get_result();

  if (!$result || $result->num_rows === 0) {
    return [
      'valido' => false,
      'mensaje' => 'Empresa no tiene un plan configurado. Contacte con soporte.'
    ];
  }

  $empresa = $result->fetch_assoc();

  if ($empresa['estado_servicio'] !== 'ACTIVO') {
    return [
      'valido' => false,
      'mensaje' => 'Servicio suspendido. Contacte con soporte.'
    ];
  }

  //Trabajar SOLO con fechas (sin horas)
  $hoy = new DateTime(date('Y-m-d'));
  $vencimiento = new DateTime($empresa['fecha_vencimiento']);

  // aplicar días de gracia
  $vencimiento->modify("+" . (int)$empresa['dias_gracia'] . " days");

  if ($hoy > $vencimiento) {

    //Suspender automáticamente
    $stmtUpdate = $conn->prepare("
            UPDATE t_empresas 
            SET estado_servicio = 'SUSPENDIDO' 
            WHERE db_name = ?
        ");
    $stmtUpdate->bind_param("s", $db_empresa);
    $stmtUpdate->execute();

    return [
      'valido' => false,
      'mensaje' => 'Su suscripción ha vencido el '
        . $empresa['fecha_vencimiento']
        . '. Contacte con soporte.'
    ];
  }
  // Se actualiza el ultimo ingreso al sistema
  $stmtUpdate = $conn->prepare("
        UPDATE t_empresas 
        SET ultimo_ingreso = NOW() 
        WHERE db_name = ?
    ");
  $stmtUpdate->bind_param("s", $db_empresa);
  $stmtUpdate->execute();

  return ['valido' => true];
}
