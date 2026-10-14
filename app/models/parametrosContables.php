<?php
session_start();
require_once( 'bd.php' );
require_once( 'funciones.php' );
$conn = conectar();

function responseJson($error, $data = [], $mensaje = '')
{
  echo json_encode([
    'error' => $error,
    'data' => $data,
    'mensaje' => $mensaje
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

function tiposTributariosValidos()
{
  return [
    'IVA_GENERADO',
    'IVA_DESCONTABLE',
    'RETEFUENTE_COMPRA',
    'RETEFUENTE_VENTA',
    'RETEIVA_COMPRA',
    'RETEIVA_VENTA',
    'RETEICA_COMPRA',
    'RETEICA_VENTA'
  ];
}

switch ( $_POST[ "op" ] ) {
  case 'actualizar':
    $sql = "UPDATE `t_parametros_contables`
                SET 
                  `cu_ingresos`                 = '" . $_POST[ 'cu_ingresos' ] . "', 
                  `cu_dev_ventas`               = '" . $_POST[ 'cu_dev_ventas' ] . "', 
                  `cu_deviva_ventas`            = '" . $_POST[ 'cu_deviva_ventas' ] . "', 
                  `cu_inventario`               = '" . $_POST[ 'cu_inventario' ] . "', 
                  `cu_costo_inventario`         = '" . $_POST[ 'cu_costo_inventario' ] . "', 
                  `cu_aju_inventario_entradas`  = '" . $_POST[ 'cu_aju_inventario_entradas' ] . "', 
                  `cu_aju_inventario_salidas`   = '" . $_POST[ 'cu_aju_inventario_salidas' ] . "', 
                  `cu_cxc_clientes`             = '" . $_POST[ 'cu_cxc_clientes' ] . "', 
                  `cu_dev_clientes`             = '" . $_POST[ 'cu_dev_clientes' ] . "', 
                  `cu_cxp_proveedores`          = '" . $_POST[ 'cu_cxp_proveedores' ] . "', 
                  `cu_dev_proveedores`          = '" . $_POST[ 'cu_dev_proveedores' ] . "', 
                  `cu_deviva_compras`           = '" . $_POST[ 'cu_deviva_compras' ] . "', 
                  `cu_ganancia_acumulada`       = '" . $_POST[ 'cu_ganancia_acumulada' ] . "', 
                  `cu_utilidad_ejercicio`       = '" . $_POST[ 'cu_utilidad_ejercicio' ] . "', 
                  `cu_perdida_ejercicio`        = '" . $_POST[ 'cu_perdida_ejercicio' ] . "', 
                  `cu_ajuste_saldos_bancos`     = '" . $_POST[ 'cu_ajuste_saldos_bancos' ] . "', 
                  `cu_ajuste_saldos_inventario` = '" . $_POST[ 'cu_ajuste_saldos_inventario' ] . "', 
                  `cu_ganancia_difcambio`       = '" . $_POST[ 'cu_ganancia_difcambio' ] . "', 
                  `cu_perdida_difcambio`        = '" . $_POST[ 'cu_perdida_difcambio' ] . "', 
                  `cu_aprox_decimales`          = '" . $_POST[ 'cu_aprox_decimales' ] . "',
                  `cu_retefuente_clientes`      = '" . $_POST[ 'cu_retefuente_clientes' ] . "', 
                  `cu_reteiva_clientes`         = '" . $_POST[ 'cu_reteiva_clientes' ] . "', 
                  `cu_reteica_clientes`         = '" . $_POST[ 'cu_reteica_clientes' ] . "', 
                  `cu_retecree_clientes`        = '" . $_POST[ 'cu_retecree_clientes' ] . "', 
                  `cu_retefuente_proveedores`   = '" . $_POST[ 'cu_retefuente_proveedores' ] . "', 
                  `cu_reteiva_proveedores`      = '" . $_POST[ 'cu_reteiva_proveedores' ] . "', 
                  `cu_reteica_proveedores`      = '" . $_POST[ 'cu_reteica_proveedores' ] . "', 
                  `cu_retecree_proveedores`     = '" . $_POST[ 'cu_retecree_proveedores' ] . "',
                  `cu_descuento_clientes`       = '" . $_POST[ 'cu_descuento_clientes' ] . "', 
                  `cu_descuento_proveedores`    = '" . $_POST[ 'cu_descuento_proveedores' ] . "',
                  `cu_iva_ventas`               = '" . $_POST[ 'cu_iva_ventas' ] . "',
                  `cu_gastos_admin`             = '" . $_POST[ 'cu_gastos_admin' ] . "',
                  `cu_gastos_ventas`            = '" . $_POST[ 'cu_gastos_ventas' ] . "',
                  `cu_gastos_financieros`       = '" . $_POST[ 'cu_gastos_financieros' ] . "',
                  `cu_cxp_clientes`             = '" . $_POST[ 'cu_cxp_clientes' ] . "',
                  `cu_cxc_proveedores`          = '" . $_POST[ 'cu_cxc_proveedores' ] . "',
                  `cu_iva_compras`              = '" . $_POST[ 'cu_iva_compras' ] . "'";
    if ( Query( $sql ) ) {
      echo json_encode( array(
        'error' => false,
        'message' => 'Parametros contables, actualizados correctamente'
      ) );
    } else {
      echo json_encode( array(
        'error' => true,
        'message' => 'Ocurrio un error, no fue podible actualizar los parametros contables'
      ) );
    }

    break;
  case 'listar-parametros':
    $sql = "SELECT 
              `cu_ingresos`, 
              `cu_dev_ventas`, 
              `cu_deviva_ventas`, 
              `cu_inventario`, 
              `cu_costo_inventario`,
              `cu_aju_inventario_entradas`,
              `cu_aju_inventario_salidas`,
              `cu_cxc_clientes`, 
              `cu_dev_clientes`, 
              `cu_cxp_proveedores`, 
              `cu_dev_proveedores`, 
              `cu_deviva_compras`, 
              `cu_ganancia_acumulada`, 
              `cu_utilidad_ejercicio`, 
              `cu_perdida_ejercicio`, 
              `cu_ajuste_saldos_bancos`, 
              `cu_ajuste_saldos_inventario`, 
              `cu_ganancia_difcambio`, 
              `cu_perdida_difcambio`, 
              `cu_aprox_decimales`,
              `cu_retefuente_clientes` ,
              `cu_reteiva_clientes`,
              `cu_reteica_clientes` ,
              `cu_retecree_clientes`,
              `cu_retefuente_proveedores`,
              `cu_reteiva_proveedores`,
              `cu_reteica_proveedores` ,
              `cu_retecree_proveedores`,
              `cu_descuento_clientes`,
              `cu_descuento_proveedores`,
              `cu_iva_ventas`,
              `cu_gastos_admin`,
              `cu_gastos_ventas`,
              `cu_gastos_financieros`,
              `cu_cxp_clientes`,
              `cu_cxc_proveedores`,
              `cu_iva_compras`
            FROM `t_parametros_contables`;
            ";
    echo json_encode( GenerarArray( $sql ) );
    break;

  case 's_cuentas_tributarias':
    $sql = "SELECT
              ct.id,
              ct.tipo,
              ct.id_cuenta_contable,
              c.cuenta,
              UPPER(c.descripcion) AS descripcion
            FROM t_cuentas_tributarias ct
            INNER JOIN t_cuentas_contables c ON c.id = ct.id_cuenta_contable
            ORDER BY ct.tipo";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
      responseJson(true, [], 'Error al preparar la consulta');
    }

    if (!$stmt->execute()) {
      responseJson(true, [], 'Error al consultar las cuentas tributarias');
    }

    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
      $data[] = $row;
    }

    responseJson(false, $data, 'Operacion exitosa');
    break;

  case 'i_cuenta_tributaria':
    $tipo = isset($_POST['tipo']) ? trim($_POST['tipo']) : '';
    $idCuentaContable = isset($_POST['id_cuenta_contable']) ? intval($_POST['id_cuenta_contable']) : 0;

    if (!in_array($tipo, tiposTributariosValidos(), true)) {
      responseJson(true, [], 'Tipo tributario invalido');
    }

    if ($idCuentaContable <= 0) {
      responseJson(true, [], 'La cuenta contable es obligatoria');
    }

    $stmt = $conn->prepare('SELECT id FROM t_cuentas_contables WHERE id = ? AND activa = "S" LIMIT 1');
    if (!$stmt) {
      responseJson(true, [], 'Error al validar la cuenta contable');
    }
    $stmt->bind_param('i', $idCuentaContable);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
      responseJson(true, [], 'La cuenta contable seleccionada no existe o no esta activa');
    }

    $stmt = $conn->prepare('INSERT INTO t_cuentas_tributarias (id_cuenta_contable, tipo) VALUES (?, ?)');
    if (!$stmt) {
      responseJson(true, [], 'Error al preparar insercion');
    }
    $stmt->bind_param('is', $idCuentaContable, $tipo);
    if (!$stmt->execute()) {
      responseJson(true, [], 'Error al insertar la cuenta tributaria');
    }

    responseJson(false, ['id' => $conn->insert_id], 'Operacion exitosa');
    break;

  case 'u_cuenta_tributaria':
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $tipo = isset($_POST['tipo']) ? trim($_POST['tipo']) : '';
    $idCuentaContable = isset($_POST['id_cuenta_contable']) ? intval($_POST['id_cuenta_contable']) : 0;

    if ($id <= 0) {
      responseJson(true, [], 'El id del registro es obligatorio');
    }

    if (!in_array($tipo, tiposTributariosValidos(), true)) {
      responseJson(true, [], 'Tipo tributario invalido');
    }

    if ($idCuentaContable <= 0) {
      responseJson(true, [], 'La cuenta contable es obligatoria');
    }

    $stmt = $conn->prepare('SELECT id FROM t_cuentas_tributarias WHERE id = ? LIMIT 1');
    if (!$stmt) {
      responseJson(true, [], 'Error al validar el registro');
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
      responseJson(true, [], 'El registro a actualizar no existe');
    }

    $stmt = $conn->prepare('SELECT id FROM t_cuentas_contables WHERE id = ? AND activa = "S" LIMIT 1');
    if (!$stmt) {
      responseJson(true, [], 'Error al validar la cuenta contable');
    }
    $stmt->bind_param('i', $idCuentaContable);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
      responseJson(true, [], 'La cuenta contable seleccionada no existe o no esta activa');
    }

    $stmt = $conn->prepare('UPDATE t_cuentas_tributarias SET id_cuenta_contable = ?, tipo = ? WHERE id = ?');
    if (!$stmt) {
      responseJson(true, [], 'Error al preparar actualizacion');
    }
    $stmt->bind_param('isi', $idCuentaContable, $tipo, $id);

    if (!$stmt->execute()) {
      responseJson(true, [], 'Error al actualizar la cuenta tributaria');
    }

    responseJson(false, ['id' => $id], 'Operacion exitosa');
    break;

  case 'd_cuenta_tributaria':
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    if ($id <= 0) {
      responseJson(true, [], 'El id del registro es obligatorio');
    }

    // TODO: validar uso en otras tablas
    $stmt = $conn->prepare('DELETE FROM t_cuentas_tributarias WHERE id = ?');
    if (!$stmt) {
      responseJson(true, [], 'Error al preparar eliminacion');
    }
    $stmt->bind_param('i', $id);

    if (!$stmt->execute()) {
      if ($stmt->errno == 1451) {
        responseJson(true, [], 'No es posible eliminar este tipo porque esta en uso en otras tablas');
      }
      responseJson(true, [], 'Error al eliminar la cuenta tributaria');
    }

    responseJson(false, ['id' => $id], 'Operacion exitosa');
    break;

  case 'buscar_cuenta_contable':
    $filtro = isset($_POST['filtro']) ? trim($_POST['filtro']) : '';
    $like = '%' . $filtro . '%';

    $sql = "SELECT id, cuenta, UPPER(descripcion) AS descripcion
            FROM t_cuentas_contables
            WHERE activa = 'S' AND (cuenta LIKE ? OR descripcion LIKE ?)
            ORDER BY cuenta ASC
            LIMIT 20";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
      responseJson(true, [], 'Error al preparar la busqueda de cuentas');
    }

    $stmt->bind_param('ss', $like, $like);
    if (!$stmt->execute()) {
      responseJson(true, [], 'Error al buscar cuentas contables');
    }

    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
      $data[] = $row;
    }

    responseJson(false, $data, 'Operacion exitosa');
    break;
}
?>