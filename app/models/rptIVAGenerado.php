<?php
session_start();
require_once('bd.php');

$conn = conectar();

if (!isset($_POST['op'])) {
	echo json_encode(['error' => true, 'mensaje' => 'Operacion no especificada']);
	exit;
}

$op = $_POST['op'];

function responseJson($error, $data = [], $mensaje = '')
{
	echo json_encode([
		'error' => $error,
		'data' => $data,
		'mensaje' => $mensaje
	], JSON_UNESCAPED_UNICODE);
	exit;
}

switch ($op) {
	case 'consultar':
		$fechaInicial = isset($_POST['fecha_inicial']) ? trim($_POST['fecha_inicial']) : '';
		$fechaFinal = isset($_POST['fecha_final']) ? trim($_POST['fecha_final']) : '';
		$tipoConsulta = isset($_POST['tipo_consulta']) ? trim($_POST['tipo_consulta']) : 'transacciones';

		if ($fechaInicial === '' || $fechaFinal === '') {
			responseJson(true, [], 'La fecha inicial y final son obligatorias');
		}

		if (!in_array($tipoConsulta, ['transacciones', 'contable'], true)) {
			responseJson(true, [], 'Tipo de consulta no valido');
		}

		if ($tipoConsulta === 'contable') {
			$sql = "SELECT
						mc.id_transaccion,
						mc.nit,
						mc.tipo,
						mc.numero,
						SUM(
							CASE
								WHEN ct.tipo = 'IVA_GENERADO'
								THEN IFNULL(mc.credito,0) - IFNULL(mc.debito,0)
								ELSE 0
							END
						) AS iva_generado,
						SUM(
							CASE
								WHEN ct.tipo = 'IVA_DESCONTABLE'
								THEN IFNULL(mc.debito,0) - IFNULL(mc.credito,0)
								ELSE 0
							END
						) AS iva_descontable,
						SUM(
							CASE
								WHEN ct.tipo = 'IVA_GENERADO'
								THEN IFNULL(mc.credito,0) - IFNULL(mc.debito,0)
								ELSE 0
							END
						) -
						SUM(
							CASE
								WHEN ct.tipo = 'IVA_DESCONTABLE'
								THEN IFNULL(mc.debito,0) - IFNULL(mc.credito,0)
								ELSE 0
							END
						) AS saldo_iva
					FROM t_movimiento_contable mc
					INNER JOIN t_cuentas_contables cc
						ON cc.cuenta = mc.cuenta
					INNER JOIN t_cuentas_tributarias ct
						ON ct.id_cuenta_contable = cc.id
					WHERE CAST(mc.fecha_hora AS DATE) BETWEEN ? AND ?
					GROUP BY
						mc.id_transaccion,
						mc.nit,
						mc.tipo,
						mc.numero
					HAVING
						(
							SUM(CASE WHEN ct.tipo = 'IVA_GENERADO'
									 THEN IFNULL(mc.credito,0) - IFNULL(mc.debito,0)
									 ELSE 0 END)
							-
							SUM(CASE WHEN ct.tipo = 'IVA_DESCONTABLE'
									 THEN IFNULL(mc.debito,0) - IFNULL(mc.credito,0)
									 ELSE 0 END)
						) <> 0
					ORDER BY
						mc.tipo";
		} else {
			$sql = "SELECT
						v.porcentaje_iva,
						SUM(
							CASE
								WHEN v.sw IN (5)
								THEN IFNULL(v.base_gravable,0)
								WHEN v.sw IN (4)
								THEN -IFNULL(v.base_gravable,0)
								ELSE 0
							END
						) AS base_ventas,
						SUM(
							CASE
								WHEN v.sw IN (5,12)
								THEN IFNULL(v.valor_iva,0)
								WHEN v.sw IN (4,11)
								THEN -IFNULL(v.valor_iva,0)
								ELSE 0
							END
						) AS iva_generado,
						SUM(
							CASE
								WHEN v.sw IN (1,14)
								THEN IFNULL(v.base_gravable,0)
								WHEN v.sw IN (7,13)
								THEN -IFNULL(v.base_gravable,0)
								ELSE 0
							END
						) AS base_compras,
						SUM(
							CASE
								WHEN v.sw IN (1,14)
								THEN IFNULL(v.valor_iva,0)
								WHEN v.sw IN (7,13)
								THEN -IFNULL(v.valor_iva,0)
								ELSE 0
							END
						) AS iva_descontable,
						v.nit,
						v.id_tercero,
						t.tratamiento,
						t.razon_comercial
					FROM v_impuestos_detalle v
					INNER JOIN t_terceros t ON t.id = v.id_tercero
					WHERE CAST(v.fecha_hora AS DATE) BETWEEN ? AND ?
					  AND v.porcentaje_iva > 0
					GROUP BY
						v.porcentaje_iva,
						v.nit,
						v.id_tercero,
						t.tratamiento,
						t.razon_comercial
					ORDER BY v.porcentaje_iva";
		}

		$stmt = $conn->prepare($sql);
		if (!$stmt) {
			responseJson(true, [], 'Error al preparar la consulta');
		}

		$stmt->bind_param('ss', $fechaInicial, $fechaFinal);
		if (!$stmt->execute()) {
			responseJson(true, [], 'Error al ejecutar la consulta');
		}

		$result = $stmt->get_result();
		$data = [];
		while ($row = $result->fetch_assoc()) {
			$data[] = $row;
		}

		responseJson(false, $data, 'Operacion exitosa');
		break;

	default:
		responseJson(true, [], 'Operacion no valida');
		break;
}
