<?php
session_start();
require_once('bd.php');
require_once('funciones.php');
$conn = conectar();

switch ($_POST['op'] ?? '') {
	case 'listar-sw':
		$sql = "SELECT sw, descripcion FROM t_sw WHERE sw IN (17,18) ORDER BY sw";
		$data = GenerarArray($sql);

		if (!is_array($data) || count($data) === 0) {
			$data = [
				['sw' => 17, 'descripcion' => 'SUBE COSTO PROMEDIO'],
				['sw' => 18, 'descripcion' => 'BAJA COSTO PROMEDIO']
			];
		}

		echo json_encode([
			'error' => false,
			'message' => 'success',
			'data' => $data
		]);
		break;

	case 'crear-sw':
		$sql17 = "INSERT INTO t_sw(sw, descripcion)
							SELECT 17, 'SUBE COSTO PROMEDIO' FROM DUAL
							WHERE NOT EXISTS(SELECT 1 FROM t_sw WHERE sw = 17)";

		$sql18 = "INSERT INTO t_sw(sw, descripcion)
							SELECT 18, 'BAJA COSTO PROMEDIO' FROM DUAL
							WHERE NOT EXISTS(SELECT 1 FROM t_sw WHERE sw = 18)";

		$ok17 = $conn->query($sql17);
		$ok18 = $conn->query($sql18);

		echo json_encode([
			'error' => !($ok17 && $ok18),
			'message' => ($ok17 && $ok18) ? 'SW 17/18 verificados correctamente.' : $conn->error
		]);
		break;

	case 'buscar-referencia':
		$codigo = trim($_POST['codigo'] ?? '');
		$id_bodega = intval($_POST['id_bodega'] ?? 0);
		$mes = intval($_POST['mes'] ?? 0);
		$ano = intval($_POST['ano'] ?? 0);

		if ($codigo === '' || $id_bodega <= 0 || $mes <= 0 || $ano <= 0) {
			echo json_encode([
				'error' => true,
				'message' => 'Parametros incompletos.',
				'data' => []
			]);
			break;
		}

		$codigoSafe = mysqli_real_escape_string($conn, $codigo);

		$sql = "SELECT
							r.id,
							r.codigo,
							r.descripcion,
							IFNULL(v.stock, 0) AS stock,
							IFNULL(v.promedio, 0) AS promedio
						FROM t_referencias r
						LEFT JOIN v_costo_promedio v ON v.id_referencia = r.id
							AND v.mes = $mes
							AND v.ano = $ano
						WHERE r.codigo = '$codigoSafe'
						LIMIT 1";

		$data = GenerarArray($sql);

		echo json_encode([
			'error' => false,
			'message' => 'success',
			'data' => $data
		]);
		break;

	case 'listar-documentos':
		$sw = intval($_POST['sw'] ?? 0);
		$idUsuario = intval($_SESSION['Ses_id'] ?? 0);

		if (!in_array($sw, [17, 18], true)) {
			echo json_encode([
				'error' => true,
				'message' => 'SW invalido',
				'data' => []
			]);
			break;
		}

		$sql = "SELECT
							t.id AS id_transaccion,
							t.numero,
							t.fecha_hora,
							t.valor_total,
							t.notas,
							t.sw,
							td.tipo,
							td.descripcion AS tipo_documento,
							UPPER(u.usuario) AS usuario,
							UPPER(CONCAT(u.nombres, ' ', u.apellidos)) AS nombres_usuario
						FROM t_transacciones t
						INNER JOIN t_tipos_documentos td ON td.id = t.id_tipo_documento
						INNER JOIN t_usuarios u ON u.id = t.id_usuario
						WHERE t.sw = $sw
							AND t.id_usuario = $idUsuario
							AND t.anulado = 0
						ORDER BY t.id DESC";

		$data = GenerarArray($sql);
		echo json_encode([
			'error' => false,
			'message' => 'success',
			'data' => $data
		]);
		break;

	case 'ajustar-costo-promedio':
		$sw = intval($_POST['sw'] ?? 0);
		$id_bodega = intval($_POST['id_bodega'] ?? 0);
		$mes = intval($_POST['mes'] ?? 0);
		$ano = intval($_POST['ano'] ?? 0);
		$itemsRaw = $_POST['items'] ?? '[]';

		if (!in_array($sw, [17, 18], true) || !$id_bodega || !$mes || !$ano) {
			echo json_encode([
				'error' => true,
				'message' => 'Parametros invalidos para ajuste de costo.'
			]);
			break;
		}

		$items = json_decode($itemsRaw, true);
		if (!is_array($items) || count($items) === 0) {
			echo json_encode([
				'error' => true,
				'message' => 'No hay productos para ajustar.'
			]);
			break;
		}

		mysqli_begin_transaction($conn);

		try {
			$procesados = 0;

			foreach ($items as $item) {
				$id_referencia = intval($item['id_referencia'] ?? 0);
				$stock = floatval($item['stock'] ?? 0);
				$promedioActual = floatval($item['promedio_actual'] ?? 0);
				$nuevoPromedio = floatval($item['nuevo_promedio'] ?? 0);

				if ($id_referencia <= 0 || $stock <= 0) {
					continue;
				}

				$delta = $nuevoPromedio - $promedioActual;

				if ($sw === 17 && $delta <= 0) {
					continue;
				}
				if ($sw === 18 && $delta >= 0) {
					continue;
				}

				$ajusteCosto = $delta * $stock;

				if (abs($ajusteCosto) <= 0) {
					continue;
				}

				$sql = "INSERT INTO t_referencias_mov
									(id_referencia, id_bodega, mes, ano,
									 cant_ini, cant_ent, cant_sal,
									 costo_ini, costo_ent, costo_sal,
									 costo_tras, costo_comp, costo_ven)
								VALUES
									($id_referencia, $id_bodega, $mes, $ano,
									 0, 0, 0,
									 0, $ajusteCosto, 0,
									 0, 0, 0)
								ON DUPLICATE KEY UPDATE
									costo_ent = costo_ent + $ajusteCosto";

				if (!$conn->query($sql)) {
					throw new Exception($conn->error);
				}

				$procesados++;
			}

			mysqli_commit($conn);

			echo json_encode([
				'error' => false,
				'message' => 'Ajuste de costos aplicado correctamente.',
				'data' => ['procesados' => $procesados]
			]);
		} catch (Exception $e) {
			mysqli_rollback($conn);
			echo json_encode([
				'error' => true,
				'message' => 'Error al ajustar costos: ' . $e->getMessage()
			]);
		}
		break;

	case 'listar-mis-ajustes':
		$fechaInicial = $_POST['fecha_inicial'] ?? '';
		$fechaFinal = $_POST['fecha_final'] ?? '';
		$idUsuario = intval($_SESSION['Ses_id'] ?? 0);

		if (!$fechaInicial || !$fechaFinal || !$idUsuario) {
			echo json_encode([
				'error' => true,
				'message' => 'Parametros incompletos.',
				'data' => []
			]);
			break;
		}

		$sql = "SELECT
					t.id AS id_transaccion,
					t.numero,
					t.fecha_hora,
					t.sw,
					t.valor_total,
					ter.razon_comercial,
					r.codigo,
					r.descripcion,
					IFNULL(td.cantidad, 0) AS stock,
					IFNULL(v.promedio, 0) - (CASE WHEN t.sw = 17 THEN ABS(IFNULL(td.valor_unitario, 0)) ELSE -ABS(IFNULL(td.valor_unitario, 0)) END) AS promedio_anterior,
					IFNULL(v.promedio, 0) AS nuevo_promedio,
					(CASE WHEN t.sw = 17 THEN ABS(IFNULL(td.valor_unitario, 0)) ELSE -ABS(IFNULL(td.valor_unitario, 0)) END) AS delta,
					((CASE WHEN t.sw = 17 THEN ABS(IFNULL(td.valor_unitario, 0)) ELSE -ABS(IFNULL(td.valor_unitario, 0)) END) * IFNULL(td.cantidad, 0)) AS ajuste_total
				FROM t_transacciones t
				INNER JOIN t_terceros ter ON ter.id = t.id_tercero
				INNER JOIN t_transacciones_detalle td ON td.id_transaccion = t.id
				INNER JOIN t_referencias r ON r.id = td.id_referencia
				LEFT JOIN v_costo_promedio v ON v.id_referencia = td.id_referencia
					AND v.mes = MONTH(t.fecha_hora)
					AND v.ano = YEAR(t.fecha_hora)
				WHERE t.sw IN (17, 18)
					AND t.id_usuario = $idUsuario
					AND DATE(t.fecha_hora) >= '$fechaInicial'
					AND DATE(t.fecha_hora) <= '$fechaFinal'
					AND t.anulado = 0
				ORDER BY t.fecha_hora DESC, t.id DESC";

		$data = GenerarArray($sql);
		echo json_encode([
			'error' => false,
			'message' => 'success',
			'data' => $data
		]);
		break;

	default:
		echo json_encode([
			'error' => true,
			'message' => 'Operacion no valida.'
		]);
		break;
}
