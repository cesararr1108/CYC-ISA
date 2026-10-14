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

		if ($fechaInicial === '' || $fechaFinal === '') {
			responseJson(true, [], 'La fecha inicial y final son obligatorias');
		}

		$sql = "SELECT
					tr.id,
					tr.fecha,
					tr.tipo,
					tr.numero,
					CASE tr.sw
						WHEN 1 THEN 'COMPRA'
						WHEN 4 THEN 'DEVOLUCION VENTA'
						WHEN 5 THEN 'VENTA'
						WHEN 7 THEN 'DEVOLUCION COMPRA'
						WHEN 9 THEN 'EGRESO'
						WHEN 10 THEN 'INGRESO'
						WHEN 11 THEN 'NC CLIENTE'
						WHEN 12 THEN 'ND CLIENTE'
						WHEN 13 THEN 'NC PROVEEDOR'
						WHEN 14 THEN 'ND PROVEEDOR'
						ELSE 'OTRO'
					END AS operacion,
					tr.nit,
					IFNULL(tr.valor_total, 0) AS valor_total,
					(
						IFNULL(tr.valor_total,0)
						- IFNULL(tr.iva,0)
						- IFNULL(tr.impoconsumo,0)
						- IFNULL(tr.ibua,0)
						- IFNULL(tr.icui,0)
					) AS base_documento,
					IFNULL(tr.iva, 0) AS iva_documento,
					IFNULL(tr.retencion_fuente, 0) AS retefuente_documento,
					IFNULL(tr.retencion_iva, 0) AS reteiva_documento,
					IFNULL(tr.retencion_ica, 0) AS reteica_documento,
					SUM(
						CASE
							WHEN ct.tipo IN ('IVA_GENERADO','IVA_DESCONTABLE')
							THEN ABS(IFNULL(mc.credito, 0) - IFNULL(mc.debito, 0))
							ELSE 0
						END
					) AS iva_contabilidad,
					SUM(
						CASE
							WHEN ct.tipo IN ('RETEFUENTE_COMPRA','RETEFUENTE_VENTA')
							THEN ABS(IFNULL(mc.credito, 0) - IFNULL(mc.debito, 0))
							ELSE 0
						END
					) AS retefuente_contabilidad,
					SUM(
						CASE
							WHEN ct.tipo IN ('RETEIVA_COMPRA','RETEIVA_VENTA')
							THEN ABS(IFNULL(mc.credito, 0) - IFNULL(mc.debito, 0))
							ELSE 0
						END
					) AS reteiva_contabilidad,
					SUM(
						CASE
							WHEN ct.tipo IN ('RETEICA_COMPRA','RETEICA_VENTA')
							THEN ABS(IFNULL(mc.credito, 0) - IFNULL(mc.debito, 0))
							ELSE 0
						END
					) AS reteica_contabilidad,
					(
						IFNULL(tr.iva, 0) -
						SUM(
							CASE
								WHEN ct.tipo IN ('IVA_GENERADO','IVA_DESCONTABLE')
								THEN ABS(IFNULL(mc.credito, 0) - IFNULL(mc.debito, 0))
								ELSE 0
							END
						)
					) AS diferencia_iva,
					(
						IFNULL(tr.retencion_fuente, 0) -
						SUM(
							CASE
								WHEN ct.tipo IN ('RETEFUENTE_COMPRA','RETEFUENTE_VENTA')
								THEN ABS(IFNULL(mc.credito, 0) - IFNULL(mc.debito, 0))
								ELSE 0
							END
						)
					) AS diferencia_retefuente,
					(
						IFNULL(tr.retencion_iva, 0) -
						SUM(
							CASE
								WHEN ct.tipo IN ('RETEIVA_COMPRA','RETEIVA_VENTA')
								THEN ABS(IFNULL(mc.credito, 0) - IFNULL(mc.debito, 0))
								ELSE 0
							END
						)
					) AS diferencia_reteiva,
					(
						IFNULL(tr.retencion_ica, 0) -
						SUM(
							CASE
								WHEN ct.tipo IN ('RETEICA_COMPRA','RETEICA_VENTA')
								THEN ABS(IFNULL(mc.credito, 0) - IFNULL(mc.debito, 0))
								ELSE 0
							END
						)
					) AS diferencia_reteica,
					CASE
						WHEN
							ABS(
								IFNULL(tr.iva, 0) -
								SUM(
									CASE
										WHEN ct.tipo IN ('IVA_GENERADO','IVA_DESCONTABLE')
										THEN ABS(IFNULL(mc.credito, 0) - IFNULL(mc.debito, 0))
										ELSE 0
									END
								)
							) <= 1
						AND ABS(
								IFNULL(tr.retencion_fuente, 0) -
								SUM(
									CASE
										WHEN ct.tipo IN ('RETEFUENTE_COMPRA','RETEFUENTE_VENTA')
										THEN ABS(IFNULL(mc.credito, 0) - IFNULL(mc.debito, 0))
										ELSE 0
									END
								)
							) <= 1
						AND ABS(
								IFNULL(tr.retencion_iva, 0) -
								SUM(
									CASE
										WHEN ct.tipo IN ('RETEIVA_COMPRA','RETEIVA_VENTA')
										THEN ABS(IFNULL(mc.credito, 0) - IFNULL(mc.debito, 0))
										ELSE 0
									END
								)
							) <= 1
						AND ABS(
								IFNULL(tr.retencion_ica, 0) -
								SUM(
									CASE
										WHEN ct.tipo IN ('RETEICA_COMPRA','RETEICA_VENTA')
										THEN ABS(IFNULL(mc.credito, 0) - IFNULL(mc.debito, 0))
										ELSE 0
									END
								)
							) <= 1
						THEN 'OK'
						ELSE 'REVISAR'
					END AS estado
				FROM t_transacciones tr
				LEFT JOIN t_movimiento_contable mc ON mc.id_transaccion = tr.id
				LEFT JOIN t_cuentas_contables cc ON cc.cuenta = mc.cuenta
				LEFT JOIN t_cuentas_tributarias ct ON ct.id_cuenta_contable = cc.id
				WHERE IFNULL(tr.anulado, 0) = 0 AND tr.sw IN (1, 4, 5, 7, 9, 10, 11, 12, 13, 14)
				  AND tr.fecha BETWEEN ? AND ?
				GROUP BY
					tr.id,
					tr.fecha,
					tr.tipo,
					tr.numero,
					tr.sw,
					tr.nit,
					tr.valor_total,
					tr.iva,
					tr.retencion_fuente,
					tr.retencion_iva,
					tr.retencion_ica,
					tr.impoconsumo,
					tr.ibua,
					tr.icui
				ORDER BY
					tr.fecha,
					tr.tipo,
					tr.numero";

		$stmt = $conn->prepare($sql);
		if (!$stmt) {
			responseJson(true, [], 'Error al preparar la consulta del reporte');
		}

		$stmt->bind_param('ss', $fechaInicial, $fechaFinal);
		if (!$stmt->execute()) {
			responseJson(true, [], 'Error al consultar la conciliacion tributaria vs contable');
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