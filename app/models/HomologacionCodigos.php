<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

session_start();

require_once('bd.php');
require_once('funciones.php');
require_once('../shared/servicios/services-factura-electronica.php');

$conn = conectar();
/**
 * =============================================================
 * PROCESAR FACTURA ELECTRÓNICA
 * =============================================================
 */
function procesarFacturaElectronica()
{
	header('Content-Type: application/json; charset=utf-8');

	try {

		if (
			!isset($_FILES['archivo_xml']) ||
			$_FILES['archivo_xml']['error'] !== UPLOAD_ERR_OK
		) {

			echo json_encode([
				'error' => true,
				'exito' => false,
				'mensaje' =>
				'No se recibió correctamente el archivo XML.'
			]);

			return;
		}


		$archivo =
			$_FILES['archivo_xml'];


		/*
         * Validar extensión
         */
		$extension =
			strtolower(
				pathinfo(
					$archivo['name'],
					PATHINFO_EXTENSION
				)
			);


		if ($extension !== 'xml') {

			echo json_encode([
				'error' => true,
				'exito' => false,
				'mensaje' =>
				'El archivo debe tener extensión XML.'
			]);

			return;
		}


		/*
         * Leer XML
         */
		$xml =
			file_get_contents(
				$archivo['tmp_name']
			);


		if (empty($xml)) {

			echo json_encode([
				'error' => true,
				'exito' => false,
				'mensaje' =>
				'El archivo XML está vacío.'
			]);

			return;
		}


		/*
         * =====================================================
         * LLAMAR PARSER
         * =====================================================
         */

		$resultado =
			extraerFacturaElectronica(
				$xml
			);


		if (
			!$resultado['exito']
		) {

			echo json_encode([
				'error' => true,
				'exito' => false,
				'mensaje' =>
				$resultado['mensaje'] ??
					'No fue posible interpretar la factura electrónica.'
			]);

			return;
		}


		/*
         * =====================================================
         * CONVERTIR A ESTRUCTURA DE ISA
         * =====================================================
         */

		$filas = [];


		foreach (
			$resultado['items']
			as $item
		) {

			/*
             * Convertir vencimiento
             *
             * El parser entrega:
             *
             * YYYY-MM-DD
             *
             * Pero tu proceso actual trabaja con:
             *
             * DD-MM-YYYY
             */

			$fechaVencimiento = '';


			if (
				!empty($item['vencimiento'])
			) {

				$partes =
					explode(
						'-',
						$item['vencimiento']
					);


				if (
					count($partes) === 3
				) {

					$fechaVencimiento =
						$partes[2] . '-' .
						$partes[1] . '-' .
						$partes[0];
				} else {

					$fechaVencimiento =
						$item['vencimiento'];
				}
			}


			/*
             * =================================================
             * ESTRUCTURA QUE YA UTILIZA ISA
             * =================================================
             */

			$filas[] = [
				'numero_factura' => $resultado['numero_factura'],
				'codigo_proveedor' => $item['codigo_proveedor'] ?? $item['codigo'] ?? '',
				'producto' => $item['descripcion'] ?? '',
				'nit_proveedor' => $resultado['nit_emisor'] ?? '',
				'cantidad' => $item['cantidad'] ?? 0,
				'porcentaje_descuento' => $item['descuento_porcentaje'] ?? 0,
				'costo' => $item['costo'] ?? 0,
				'fecha_vencimiento' => $fechaVencimiento,
				'lote' => $item['lote'] ?? '',
				/*
                 * Información adicional que podemos
                 * utilizar posteriormente.
                 */
				'ean' => $item['ean'] ?? '',
				'codigo_interno' => $item['codigo_interno'] ?? '',
				'subtotal' => $item['subtotal'] ?? 0,
				'iva_porcentaje' => $item['iva_porcentaje'] ?? 0,
				'iva_valor' => $item['iva_valor'] ?? 0,
				'descuento_valor' => $item['descuento_valor'] ?? 0,
				'total' => $item['total'] ?? 0,
				'tipo_documento' => $resultado['tipo_documento'] ?? '',
				'fecha_factura' => $resultado['fecha_factura'] ?? '',
			];
		}


		/*
         * =====================================================
         * RESPUESTA
         * =====================================================
         */

		echo json_encode([
			'error' => false,
			'exito' => true,
			'mensaje' => 'Factura electrónica procesada correctamente.',
			'documento' => [
				'tipo' => $resultado['tipo_documento'],
				'nit' => $resultado['nit_emisor'],
				'numero' => $resultado['numero_factura'],
				'fecha' => $resultado['fecha_factura']
			],
			'cantidad_items' => count($filas),
			'data' => $filas
		], JSON_UNESCAPED_UNICODE);
	} catch (Throwable $e) {

		echo json_encode([
			'error' => true,
			'exito' => false,
			'mensaje' => 'Error procesando factura electrónica: ' . $e->getMessage()
		], JSON_UNESCAPED_UNICODE);
	}
}
function responder(array $data)
{
	echo json_encode($data, JSON_UNESCAPED_UNICODE);
	exit;
}

function valorNumerico($valor)
{
	$valor = trim((string) $valor);
	if ($valor === '') {
		return null;
	}

	$valor = str_replace(['$', ' '], '', $valor);

	if (strpos($valor, ',') !== false && strpos($valor, '.') === false) {
		$valor = str_replace(',', '.', $valor);
	} else {
		$valor = str_replace(',', '', $valor);
	}

	return is_numeric($valor) ? $valor + 0 : null;
}

function normalizarTextoCarga($valor)
{
	return trim((string) $valor);
}

function identificarNitsArchivo($rows)
{
	$nits = [];
	foreach ($rows as $row) {
		$nit = trim((string) ($row['nit_proveedor'] ?? ''));
		if ($nit !== '') {
			$nits[$nit] = true;
		}
	}

	return array_keys($nits);
}

function buscarTerceroHomologacion($nit)
{
	$sql = "SELECT
				t.id,
				IFNULL(t.estado, '') AS estado,
				IFNULL(tt.es_proveedor, 'N') AS es_proveedor,
				IFNULL(t.id_condicion_pago_prov, 0) AS id_condicion_pago_prov,
				IFNULL(p2.id, 0) AS condicion_proveedor_valida,
				IFNULL(d.id, 0) AS id_direccion,
				IFNULL(TRIM(d.direccion), '') AS direccion
			FROM t_terceros t
			LEFT JOIN t_terceros_direcciones d
				ON d.id = (
					SELECT td.id
					FROM t_terceros_direcciones td
					WHERE td.id_tercero = t.id
					ORDER BY td.principal DESC, td.id ASC
					LIMIT 1
				)
			LEFT JOIN t_tipos_terceros tt ON tt.id = t.id_tipo_tercero
			LEFT JOIN t_condiciones_pago p2
				ON p2.id = t.id_condicion_pago_prov
				AND p2.tipo_condicion = 'P'
			WHERE t.nit = ?
			LIMIT 1
	";


	$parametros = [
		[
			'tipo' => 's',
			'valor' => $nit
		]
	];
	$rs = GenerarArrayPrepare($sql, $parametros);
	return $rs[0] ?? null;
}

function buscarReferenciaHomologacion($codigo_tercero, $nit_tercero, $tipo_listado = 'PROVEEDOR')
{
	$sql = "SELECT
				r.id,
				r.codigo,
				r.descripcion,
				IFNULL(i.porcentaje_iva, 0) AS porcentaje_iva,
				r.und_compra,
				case when r.und_compra =1 
				then r.cant_1 
				when r.und_compra =2 
				then r.cant_2 
				when r.und_compra =3 
				then r.cant_3 
				when r.und_compra =4 
				then r.cant_4 
				else 0 end as conversion ,
				case when r.und_compra =1 then r.und_1 
				when r.und_compra =2 then r.und_2 
				when r.und_compra =3 then r.und_3 
				when r.und_compra =4 then r.und_4 
				else '' end as des_und_compra,
				IFNULL(m.descripcion, '') AS laboratorio
			FROM t_referencias r
			INNER JOIN t_homologacion_codigos h ON h.id_referencia = r.id
			LEFT JOIN t_iva i ON i.id = r.id_iva
			LEFT JOIN t_marcas m ON m.id = r.id_marca
			WHERE h.codigo_tercero = ? 
			AND h.nit_tercero = ?
			AND UPPER(IFNULL(h.tipo_listado, 'PROVEEDOR')) = ?
			LIMIT 1
	";

	$parametros = [
		[
			'tipo' => 's',
			'valor' => $codigo_tercero
		],
		[
			'tipo' => 's',
			'valor' => $nit_tercero
		],
		[
			'tipo' => 's',
			'valor' => $tipo_listado
		]
	];
	$rs = GenerarArrayPrepare($sql, $parametros);
	return $rs[0] ?? null;
}

function buscarHomologacionExistente($conexion, $nitProveedor, $codigoProveedor, $tipoListado = 'PROVEEDOR')
{
	$tipoListado = strtoupper(trim((string) $tipoListado));
	$sql = "
		SELECT id, id_referencia
		FROM t_homologacion_codigos
		WHERE nit_proveedor = ?
		  AND codigo_proveedor = ?
		  AND UPPER(IFNULL(tipo_listado, 'PROVEEDOR')) = ?
		LIMIT 1
	";

	$stmt = $conexion->prepare($sql);
	$stmt->bind_param('sss', $nitProveedor, $codigoProveedor, $tipoListado);
	$stmt->execute();
	$rs = $stmt->get_result();
	return $rs->fetch_assoc();
}

function evaluarTerceroHomologacion($nit)
{
	$errores = [];
	$nit = trim((string) $nit);
	if ($nit === '') {
		return ['errores' => ['Falta NIT proveedor'], 'tercero' => null];
	}

	$tercero = buscarTerceroHomologacion($nit);
	if (!$tercero) {
		$errores[] = 'No existe tercero para el NIT';
		return ['errores' => $errores, 'tercero' => null];
	}

	if (trim((string) ($tercero['estado'] ?? '')) !== 'A') {
		$errores[] = 'El tercero no se encuentra activo';
	}

	if ((string) ($tercero['es_proveedor'] ?? 'N') !== 'S') {
		$errores[] = 'El tercero no está marcado como proveedor';
	}

	if (intval($tercero['id_condicion_pago_prov'] ?? 0) <= 0 || intval($tercero['condicion_proveedor_valida'] ?? 0) <= 0) {
		$errores[] = 'El tercero no tiene condición de pago de proveedor válida';
	}

	if (intval($tercero['id_direccion'] ?? 0) <= 0 || trim((string) ($tercero['direccion'] ?? '')) === '') {
		$errores[] = 'El tercero no tiene dirección registrada';
	}

	return ['errores' => array_values(array_unique($errores)), 'tercero' => $tercero];
}

function validarFilaHomologacion($fila)
{

	$nit_proveedor = $fila["nit_proveedor"] ?? '';
	$codigo_proveedor = $fila["codigo_proveedor"] ?? '';
	$referencia = [];
	$errores = [];

	if ($codigo_proveedor != '') {
		$referencia = buscarReferenciaHomologacion($codigo_proveedor, $nit_proveedor, 'PROVEEDOR');
		if (!$referencia) {
			$errores[] = 'Referencia de codigo tercero ' . $codigo_proveedor . ' no homologada para el proveedor';
		}
	}

	$porcentajeIva = $fila['iva_porcentaje'];//$referencia ? floatval($referencia['porcentaje_iva']) : 0;
	$sqlUnd = "SELECT 
					id, 
					und_compra, 
					cant_1, 
					cant_2, 
					cant_3, 
					cant_4, 
					und_1, 
					und_2, 
					und_3, 
					und_4 
				FROM t_referencias 
				WHERE id = ?";
	$parametrosUnd = [
		[
			'tipo' => 'i',
			'valor' => $referencia ? intval($referencia['id']) : 0
		]
	];
	$UnidadesCompra = GenerarArrayPrepare($sqlUnd, $parametrosUnd);

	$html = '<select class="form-select form-select-sm select-unidades-compra" style="width: 100px !important;" >';
	$unidadesCompra = $UnidadesCompra[0] ?? null;

	foreach (range(1, 4) as $i) {
		$cant = $unidadesCompra ? intval($unidadesCompra['cant_' . $i] ?? 0) : 0;
		$und = $unidadesCompra ? trim((string) ($unidadesCompra['und_' . $i] ?? '')) : '';


		if ($cant > 0 && $und !== '') {
			if ($i == $UnidadesCompra[0]['und_compra']) {
				$html .= '<option value="' . $cant . '" selected>' . $und . ' - ' . $cant . '</option>';
			} else {
				$html .= '<option value="' . $cant . '">' . $und . ' - ' . $cant . '</option>';
			}
		}
	}
	$html .= '</select>';

	return [
		'numero_factura' => $fila['numero_factura'] ?? '',
		'codigo' => $referencia ? $referencia['codigo'] : '',
		'descripcion' => $referencia ? $referencia['descripcion'] : '',
		'laboratorio' => $referencia ? $referencia['laboratorio'] : '',
		'id_referencia' => $referencia ? intval($referencia['id']) : 0,
		'referencia_match' => $referencia ? true : false,
		'porcentaje_iva' => $porcentajeIva,
		'porcentaje_descuento' => $fila['porcentaje_descuento'] ?? 0,
		'costo' => $fila['costo'] ?? 0,
		'costo_real' => $fila["costo"] / ($referencia ? $referencia['conversion'] ?? 1 : 1),
		'cantidad' => $fila['cantidad'] ?? 0,
		'cantidad_real' => $fila['cantidad']  * ($referencia ? $referencia['conversion'] ?? 0 : 0),
		'des_und_compra' => trim($referencia ? $referencia['des_und_compra'] : ''),
		'conversion' => $referencia ? $referencia['conversion'] : 0,
		'fecha_vencimiento' => $fila['fecha_vencimiento'] ?? '',
		'codigo_proveedor' => $codigo_proveedor,
		'nit_proveedor' => $nit_proveedor,
		'producto' => $fila['producto'] ?? '',
		'homologado' => $referencia ? true : false,
		'lote' => $fila['lote'] ?? '',
		'errores' => array_values(array_unique($errores)),
		'valida' => count($errores) === 0,
		'unidadesCompras' => $html

	];
}

function tablaHomologacion()
{
	return 't_homologacion_codigos';
}

function buscarReferencia()
{
	$sql = "
		SELECT
			r.id,
			r.codigo,
			r.descripcion,
			IFNULL(e.ean, '') AS ean
		FROM t_referencias r
		LEFT JOIN (
			SELECT id_referencia, MIN(ean) AS ean
			FROM t_referencias_ean
			GROUP BY id_referencia
		) e ON e.id_referencia = r.id
	";
	$parametros = [];

	$data = GenerarArrayPrepare($sql, $parametros);
	return is_array($data) ? $data : [];
}

function listarHomologaciones($conexion)
{
	$nit = isset($_POST['nit_proveedor']) ? trim($_POST['nit_proveedor']) : '';
	$filtro = isset($_POST['filtro']) ? trim($_POST['filtro']) : '';

	$where = ['1=1'];
	$parametros = [];

	if ($nit !== '') {

		$where[] = "h.nit_proveedor = ?";

		$parametros[] = [
			'tipo' => 's',
			'valor' => $nit
		];
	}

	if ($filtro !== '') {

		$where[] = "(
            h.codigo_barras LIKE ?
            OR h.codigo_tercero LIKE ?
            OR h.nit_tercero LIKE ?
            OR r.codigo LIKE ?
            OR r.descripcion LIKE ?
        )";

		$valorFiltro = '%' . $filtro . '%';

		$parametros[] = [
			'tipo' => 's',
			'valor' => $valorFiltro
		];

		$parametros[] = [
			'tipo' => 's',
			'valor' => $valorFiltro
		];

		$parametros[] = [
			'tipo' => 's',
			'valor' => $valorFiltro
		];

		$parametros[] = [
			'tipo' => 's',
			'valor' => $valorFiltro
		];

		$parametros[] = [
			'tipo' => 's',
			'valor' => $valorFiltro
		];
	}

	$sql = "
        SELECT 
            h.id, 
            h.codigo_barras, 
            h.codigo_tercero, 
            h.nit_tercero, 
            IFNULL(h.stock, 0) AS stock, 
            IFNULL(h.precio, 0) AS precio, 
            UPPER(IFNULL(h.tipo_listado, 'PROVEEDOR')) AS tipo_listado, 
            h.id_referencia, 
            IFNULL(r.codigo, '') AS codigo_sistema, 
            IFNULL(r.descripcion, '') AS descripcion_sistema, 
            IFNULL(e.ean, '') AS ean_sistema, 
            h.fecha_creacion, 
            h.usuario_creacion 
        FROM " . tablaHomologacion() . " h 

        LEFT JOIN t_referencias r 
            ON r.id = h.id_referencia 

        LEFT JOIN (
            SELECT 
                id_referencia, 
                MIN(ean) AS ean 
            FROM t_referencias_ean 
            GROUP BY id_referencia
        ) e 
            ON e.id_referencia = r.id 

        WHERE " . implode(' AND ', $where) . "

        ORDER BY h.id DESC
    ";

	$data = GenerarArrayPrepare($sql, $parametros, $conexion);

	return is_array($data) ? $data : [];
}

function previsualizarHomologacion()
{
	$rows = isset($_POST['rows']) ? json_decode($_POST['rows'], true) : [];
	if (!is_array($rows) || count($rows) === 0) {
		responder([
			'error' => true,
			'message' => 'No se recibieron filas para previsualizar',
			'data' => []
		]);
	}

	$resultado = [];
	$validas = 0;
	$invalidas = 0;

	foreach ($rows as $i => $fila) {
		$res = validarFilaHomologacion($fila);
		$resultado[] = $res;
		if (!empty($res['valida'])) {
			$validas++;
		} else {
			$invalidas++;
		}
	}

	responder([
		'error' => false,
		'message' => 'Previsualización generada',
		'resumen' => [
			'total' => count($resultado),
			'validas' => $validas,
			'invalidas' => $invalidas,
			'nit' => $rows[0]['nit_tercero'] ?? '',
			'errores_tercero' => true,
			'errores_globales' => []
		],
		'data' => $resultado
	]);
}

function guardarHomologaciones($conexion)
{

	$items = isset($_POST['items']) ? json_decode($_POST['items'], true) : [];
	$tipoListado = isset($_POST['tipo_listado']) ? strtoupper(trim($_POST['tipo_listado'])) : 'PROVEEDOR';
	$usuario = isset($_SESSION['Ses_id']) ? (int) $_SESSION['Ses_id'] : 0;

	if (!is_array($items) || count($items) === 0) {
		responder([
			'error' => true,
			'message' => 'No se recibieron registros para homologar.'
		]);
	}


	try {
		$conexion->begin_transaction();

		$insertados = 0;
		$actualizados = 0;

		foreach ($items as $item) {
			$stmt = $conexion->prepare("SELECT id FROM " . tablaHomologacion() . " 
				WHERE nit_tercero = ? 
				  AND tipo_listado = ? 
				  AND (
					  codigo_tercero = ?
					  OR codigo_barras = ?
					  OR id_referencia = ?
				  )
				ORDER BY 
				  CASE
					WHEN codigo_tercero = ? THEN 1
					WHEN codigo_barras = ? THEN 2
					WHEN id_referencia = ? THEN 3
					ELSE 4
				  END
				LIMIT 1");
			$stmt->bind_param(
				'ssssissi',
				$item['nit_tercero'],
				$tipoListado,
				$item['codigo_tercero'],
				$item['codigo_barras'],
				$item['id_referencia'],
				$item['codigo_tercero'],
				$item['codigo_barras'],
				$item['id_referencia']
			);
			$stmt->execute();
			$resultado = $stmt->get_result();
			$existente = $resultado->fetch_assoc();

			if ($existente) {
				$idExistente = (int) $existente['id'];
				$stmtUp = $conexion->prepare("UPDATE " . tablaHomologacion() . " SET codigo_barras = ?, codigo_tercero = ?, nit_tercero = ?, id_referencia = ?, stock = ?, precio = ?, tipo_listado = ?, usuario_actualizacion = ?, fecha_actualizacion = NOW() WHERE id = ?");
				$stmtUp->bind_param('sssiddsii', $item['codigo_barras'], $item['codigo_tercero'], $item['nit_tercero'], $item['id_referencia'], $item['stock'], $item['precio'], $tipoListado, $usuario, $idExistente);
				$stmtUp->execute();
				$actualizados++;
			} else {
				$stmtIn = $conexion->prepare("INSERT INTO " . tablaHomologacion() . " (codigo_barras, codigo_tercero, nit_tercero, id_referencia, stock, precio, tipo_listado, usuario_creacion, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
				$stmtIn->bind_param('sssiddsi', $item['codigo_barras'], $item['codigo_tercero'], $item['nit_tercero'], $item['id_referencia'], $item['stock'], $item['precio'], $tipoListado, $usuario);
				$stmtIn->execute();
				$insertados++;
			}
		}

		$conexion->commit();

		responder([
			'error' => false,
			'message' => 'Homologaciones guardadas correctamente.',
			'insertados' => $insertados,
			'actualizados' => $actualizados,
			'omitidos' => 0,
			'detalle_omitidos' => [],
			'errores_globales' => [],
			'errores_tercero' => []
		]);
	} catch (Throwable $e) {
		$conexion->rollback();
		responder([
			'error' => true,
			'message' => 'Error al guardar la homologación: ' . $e->getMessage()
		]);
	}
}

function eliminarHomologacion($conexion)
{
	$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

	if ($id <= 0) {
		responder([
			'error' => true,
			'message' => 'Id inválido.'
		]);
	}

	$stmt = $conexion->prepare('DELETE FROM ' . tablaHomologacion() . ' WHERE id = ?');
	$stmt->bind_param('i', $id);
	$ok = $stmt->execute();

	responder([
		'error' => !$ok,
		'message' => $ok ? 'Homologación eliminada correctamente.' : 'No se pudo eliminar la homologación.'
	]);
}

$op = isset($_POST['op']) ? trim($_POST['op']) : (isset($_GET['op']) ? trim($_GET['op']) : '');

if ($op === '') {
	responder([
		'error' => true,
		'message' => 'Operación no especificada.'
	]);
}

switch ($op) {
	case 'buscar_referencia':

		responder([
			'error' => false,
			'data' => buscarReferencia()
		]);
		break;

	case 'previsualizar_homologacion':
		previsualizarHomologacion();
		break;

	case 'listar_homologacion':
		responder([
			'error' => false,
			'data' => listarHomologaciones($conn)
		]);
		break;

	case 'guardar_homologaciones':
		guardarHomologaciones($conn);
		break;
	case 'guardar_homologacion':
		$nit = $_POST['nit_tercero'] ?? '';
		$codigo_tercero = $_POST['codigo_tercero'] ?? '';
		$id_referencia = $_POST['id_referencia'] ?? '';
		$tipo_listado = $_POST['tipo_listado'] ?? 'PROVEEDOR';
		$stock = 0;
		$codigo_barras = '';
		$precio = 0;
		$usuario = $_SESSION['Ses_id'] ?? 0;

		$fields = [
			'codigo_barras' => $codigo_barras,
			'codigo_tercero' => $codigo_tercero,
			'nit_tercero' => $nit,
			'id_referencia' => $id_referencia,
			'stock' => $stock,
			'precio' => $precio,
			'tipo_listado' => $tipo_listado,
			'usuario_creacion' => $usuario
		];

		try {

			$conn->begin_transaction();

			/*
			 * ---------------------------------------------------------
			 * VALIDAR SI YA EXISTE LA HOMOLOGACIÓN
			 * ---------------------------------------------------------
			 */

			$sql = "SELECT id
					FROM t_homologacion_codigos
					WHERE codigo_tercero = ?
					AND nit_tercero = ?
					AND tipo_listado = ?
					LIMIT 1
			";

			$stmt = $conn->prepare($sql);

			if (!$stmt) {
				throw new Exception(
					'Error preparando la consulta: ' . $conn->error
				);
			}

			$stmt->bind_param(
				'sss',
				$codigo_tercero,
				$nit,
				$tipo_listado
			);

			if (!$stmt->execute()) {
				throw new Exception(
					'Error ejecutando la consulta: ' . $stmt->error
				);
			}

			$result = $stmt->get_result();
			$existente = $result->fetch_assoc();

			$stmt->close();


			/*
			 * ---------------------------------------------------------
			 * SI EXISTE -> ACTUALIZAR
			 * ---------------------------------------------------------
			 */

			if ($existente) {

				$id_homologacion = (int) $existente['id'];

				$updateFields = [
					'codigo_barras' => $codigo_barras,
					'id_referencia' => $id_referencia,
					'stock' => $stock,
					'precio' => $precio,
					'usuario_actualizacion' => $usuario
				];

				/*
				 *  Función update() recibe:
				 *
				 * update($tabla, $datos, $id)
				 */

				$actualizado = update(
					't_homologacion_codigos',
					$updateFields,
					$id_homologacion
				);

				if (!$actualizado) {
					throw new Exception(
						'Error al actualizar la homologación.'
					);
				}

				$conn->commit();

				echo json_encode([
					'error' => false,
					'message' => 'La homologación ya existía y fue actualizada correctamente.',
					'id' => $id_homologacion,
					'accion' => 'actualizado'
				], JSON_UNESCAPED_UNICODE);
			} else {

				/*
				 * ---------------------------------------------------------
				 * NO EXISTE -> INSERTAR
				 * ---------------------------------------------------------
				 */

				$insert = insertReturnId(
					't_homologacion_codigos',
					$fields,
					false,
					null,
					true
				);

				if ($insert <= 0) {
					throw new Exception(
						'Error al insertar la homologación.'
					);
				}

				$conn->commit();

				echo json_encode([
					'error' => false,
					'message' => 'Homologación guardada correctamente.',
					'id' => $insert,
					'accion' => 'insertado'
				], JSON_UNESCAPED_UNICODE);
			}
		} catch (Throwable $e) {
			$conn->rollback();
			responder([
				'error' => true,
				'message' => 'Error al guardar la homologación: ' . $e->getMessage()
			]);
		}

		break;
	case 'eliminar_homologacion':
		eliminarHomologacion($conn);
		break;

	case 'procesar_factura_electronica':

		procesarFacturaElectronica();

		break;
	case 'validar_tercero':
		$nit = isset($_POST['nit']) ? trim($_POST['nit']) : '';
		if ($nit === '') {
			responder([
				'error' => true,
				'message' => 'Falta NIT para validar.'
			]);
		}
		$terceroEval = evaluarTerceroHomologacion($nit);
		responder([
			'error' => false,
			'errores' => $terceroEval['errores'],
			'tercero' => $terceroEval['tercero']
		]);
		break;
	default:
		responder([
			'error' => true,
			'message' => 'Operación no válida: ' . $op
		]);
		break;
}
