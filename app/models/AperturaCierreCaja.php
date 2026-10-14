<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once('bd.php');
require_once('funciones.php');

if (!sessionExists()) {
    echo json_encode([
        'ok'      => false,
        'message' => 'La sesion se ha cerrado, por favor ingrese nuevamente'
    ]);
    exit;
}

$conn = conectar();
$op   = $_POST['op'] ?? '';

/** Convierte un valor del formulario a número (acepta 1234.5, "1.234,5", "$ 1.234"). */
function acNumero($valor)
{
    if ($valor === null || $valor === '') {
        return 0.0;
    }
    $valor = trim((string) $valor);
    // "150.000" o "1.250.000" son miles en formato es-CO, no decimales
    if (preg_match('/^-?\d{1,3}(\.\d{3})+$/', $valor)) {
        return (float) str_replace('.', '', $valor);
    }
    if (is_numeric($valor)) {
        return (float) $valor;
    }
    $s = preg_replace('/[^\d,.\-]/', '', (string) $valor);
    $s = str_replace('.', '', $s);
    $s = str_replace(',', '.', $s);
    return is_numeric($s) ? (float) $s : 0.0;
}

/** Devuelve la apertura indicada (o null). */
function acObtenerApertura($id)
{
    $datos = GenerarArrayPrepare(
        'SELECT id, id_usuario, id_bodega, fecha_apertura, fecha_cierre, base, efectivo, tarjetas, otros, observaciones
           FROM t_aperturas_cajas
          WHERE id = ?
          LIMIT 1',
        [['tipo' => 'i', 'valor' => (int) $id]]
    );
    return $datos[0] ?? null;
}

function acEsAdmin()
{
    return in_array((string) ($_SESSION['Ses_roles_id'] ?? ''), ['1', '4'], true);
}

/** Bodega seleccionada en la sesión: ['id' => int, 'codigo' => string, 'nombre' => string] o null. */
function acBodegaSesion()
{
    $codigo = $_SESSION['Ses_bodega'] ?? '';
    if ($codigo === '') {
        return null;
    }
    $datos = GenerarArrayPrepare(
        'SELECT id FROM t_almacenes WHERE almacen = ? LIMIT 1',
        [['tipo' => 's', 'valor' => $codigo]]
    );
    if (empty($datos)) {
        return null;
    }
    return [
        'id'     => (int) $datos[0]['id'],
        'codigo' => $codigo,
        'nombre' => $_SESSION['Ses_bodega_des'] ?? $codigo
    ];
}

/** Devuelve la fecha si tiene formato Y-m-d válido, o null. */
function acFecha($valor)
{
    $d = DateTime::createFromFormat('Y-m-d', (string) $valor);
    return ($d && $d->format('Y-m-d') === $valor) ? $valor : null;
}

switch ($op) {

    /* ------------------------------------------------------------------
     * Estado de la caja del usuario en la bodega de la sesión
     * ------------------------------------------------------------------ */
    case 'info':
        try {
            $id         = (int) $_SESSION['Ses_id'];
            $usuario    = $_SESSION['Ses_usuario'];
            $bodega     = $_SESSION['Ses_bodega'];
            $bodega_des = $_SESSION['Ses_bodega_des'];

            $bodegaData = GenerarArrayPrepare(
                'SELECT id FROM t_almacenes WHERE almacen = ? LIMIT 1',
                [['tipo' => 's', 'valor' => $bodega]]
            );

            if (empty($bodegaData)) {
                echo json_encode(['ok' => false, 'message' => 'No se encontró la bodega de la sesión.']);
                break;
            }

            $id_bodega = (int) $bodegaData[0]['id'];

            $datos = GenerarArrayPrepare(
                'SELECT c.id AS id_apertura, c.fecha_apertura, c.base, c.efectivo, c.tarjetas, c.otros,
                        c.observaciones, c.id_bodega,
                        TIMESTAMPDIFF(MINUTE, c.fecha_apertura, NOW()) AS minutos_apertura,
                        TIMESTAMPDIFF(HOUR, c.fecha_apertura, NOW()) AS horas_apertura
                   FROM t_aperturas_cajas c
                  WHERE c.id_usuario = ?
                    AND c.id_bodega = ?
                    AND c.fecha_cierre IS NULL
                  ORDER BY c.fecha_apertura DESC
                  LIMIT 1',
                [['tipo' => 'i', 'valor' => $id], ['tipo' => 'i', 'valor' => $id_bodega]]
            );

            $ultimo = GenerarArrayPrepare(
                'SELECT id, fecha_cierre
                   FROM t_aperturas_cajas
                  WHERE id_usuario = ?
                    AND fecha_cierre IS NOT NULL
                  ORDER BY fecha_cierre DESC
                  LIMIT 1',
                [['tipo' => 'i', 'valor' => $id]]
            );

            $base = [
                'usuario'           => $usuario,
                'id_usuario'        => $id,
                'id_bodega'         => $id_bodega,
                'bodega'            => $bodega,
                'bodega_des'        => $bodega_des,
                'ultimo_cierre'     => $ultimo[0]['fecha_cierre'] ?? '',
                'id_ultimo_cierre'  => (int) ($ultimo[0]['id'] ?? 0),
                'hora_servidor'     => date('Y-m-d H:i:s')
            ];

            if (empty($datos)) {
                echo json_encode($base + [
                    'ok'               => true,
                    'abierta'          => false,
                    'id_apertura'      => 0,
                    'fecha_apertura'   => '',
                    'base'             => 0,
                    'efectivo'         => 0,
                    'tarjetas'         => 0,
                    'otros'            => 0,
                    'observaciones'    => '',
                    'minutos_apertura' => 0,
                    'horas_apertura' => 0
                ]);
                break;
            }

            $c = $datos[0];
            echo json_encode($base + [
                'ok'               => true,
                'abierta'          => true,
                'id_apertura'      => (int) $c['id_apertura'],
                'id_bodega'        => (int) $c['id_bodega'],
                'fecha_apertura'   => $c['fecha_apertura'],
                'base'             => $c['base'],
                'efectivo'         => $c['efectivo'],
                'tarjetas'         => $c['tarjetas'],
                'otros'            => $c['otros'],
                'observaciones'    => $c['observaciones'] ?? '',
                'minutos_apertura' => (int) $c['minutos_apertura'],
                'horas_apertura' => (int) $c['horas_apertura']
            ]);
        } catch (Throwable $th) {
            error_log('AperturaCierreCaja info: ' . $th->getMessage());
            echo json_encode(['ok' => false, 'message' => 'Ocurrió un error al consultar la información de la caja.']);
        }
        break;

    /* ------------------------------------------------------------------
     * Abrir caja
     * ------------------------------------------------------------------ */
    case 'abrir_caja':
        try {
            $id_usuario = (int) $_SESSION['Ses_id'];          // siempre el usuario de la sesión
            $id_bodega  = (int) ($_POST['id_bodega'] ?? 0);
            $base       = acNumero($_POST['base'] ?? 0);

            if ($id_bodega <= 0) {
                echo json_encode(['ok' => false, 'message' => 'No se identificó la bodega.']);
                break;
            }
            if ($base < 0) {
                echo json_encode(['ok' => false, 'message' => 'La base no puede ser negativa.']);
                break;
            }

            // No permitir dos cajas abiertas al mismo tiempo en la misma bodega
            $abierta = GenerarArrayPrepare(
                'SELECT id FROM t_aperturas_cajas
                  WHERE id_usuario = ? AND id_bodega = ? AND fecha_cierre IS NULL
                  LIMIT 1',
                [['tipo' => 'i', 'valor' => $id_usuario], ['tipo' => 'i', 'valor' => $id_bodega]]
            );
            if (!empty($abierta)) {
                echo json_encode(['ok' => false, 'message' => 'Ya tienes una caja abierta en esta bodega.']);
                break;
            }

            // Control de una apertura por día
            if (($_POST['ControlDiarioAperturaCaja'] ?? '0') === '1') {
                $hoy = GenerarArrayPrepare(
                    'SELECT COUNT(*) AS total FROM t_aperturas_cajas
                      WHERE id_usuario = ? AND DATE(fecha_apertura) = CURDATE()',
                    [['tipo' => 'i', 'valor' => $id_usuario]]
                );
                if ((int) ($hoy[0]['total'] ?? 0) > 0) {
                    echo json_encode(['ok' => false, 'message' => 'Ya abriste caja hoy; solo se permite una apertura por día.']);
                    break;
                }
            }

            $data = [
                'id_usuario'     => $id_usuario,
                'id_bodega'      => $id_bodega,
                'base'           => $base,
                'efectivo'       => 0,
                'tarjetas'       => 0,
                'otros'          => 0,
                'observaciones'  => '',
                'fecha_apertura' => date('Y-m-d H:i:s')
            ];

            $id_apertura = insertReturnId('t_aperturas_cajas', $data, false);

            echo json_encode($id_apertura > 0
                ? ['ok' => true, 'message' => 'La caja se abrió correctamente.', 'id_apertura' => (int) $id_apertura]
                : ['ok' => false, 'message' => 'No fue posible abrir la caja.']);
        } catch (Throwable $th) {
            error_log('AperturaCierreCaja abrir_caja: ' . $th->getMessage());
            echo json_encode(['ok' => false, 'message' => 'Ocurrió un error al abrir la caja.']);
        }
        break;

    /* ------------------------------------------------------------------
     * Cerrar caja
     * ------------------------------------------------------------------ */
    case 'cerrar_caja':
        try {
            $id_apertura = (int) ($_POST['id_apertura'] ?? 0);
            $apertura    = acObtenerApertura($id_apertura);

            if (!$apertura) {
                echo json_encode(['ok' => false, 'message' => 'No se encontró la apertura de caja.']);
                break;
            }
            if ((int) $apertura['id_usuario'] !== (int) $_SESSION['Ses_id']) {
                echo json_encode(['ok' => false, 'message' => 'Solo el usuario que abrió la caja puede cerrarla.']);
                break;
            }
            if (!empty($apertura['fecha_cierre'])) {
                echo json_encode(['ok' => false, 'message' => 'Esta caja ya fue cerrada.']);
                break;
            }

            $efectivo = acNumero($_POST['efectivo'] ?? 0);
            $tarjetas = acNumero($_POST['tarjetas'] ?? 0);
            $otros    = acNumero($_POST['otros'] ?? 0);

            if ($efectivo < 0 || $tarjetas < 0 || $otros < 0) {
                echo json_encode(['ok' => false, 'message' => 'Los valores no pueden ser negativos.']);
                break;
            }

            $data = [
                'efectivo'      => $efectivo,
                'tarjetas'      => $tarjetas,
                'otros'         => $otros,
                'observaciones' => mb_substr(trim((string) ($_POST['observaciones'] ?? '')), 0, 1000),
                'fecha_cierre'  => date('Y-m-d H:i:s')
            ];

            $update = update('t_aperturas_cajas', $data, $id_apertura);

            echo json_encode($update
                ? ['ok' => true, 'message' => 'La caja se cerró correctamente.', 'id_apertura' => $id_apertura]
                : ['ok' => false, 'message' => 'No fue posible cerrar la caja.']);
        } catch (Throwable $th) {
            error_log('AperturaCierreCaja cerrar_caja: ' . $th->getMessage());
            echo json_encode(['ok' => false, 'message' => 'Ocurrió un error al cerrar la caja.']);
        }
        break;

    /* ------------------------------------------------------------------
     * Consultar un cierre para editarlo
     * ------------------------------------------------------------------ */
    case 's_cierre':
        $apertura = acObtenerApertura($_POST['id_apertura'] ?? 0);
        if (!$apertura) {
            echo json_encode(['ok' => false, 'message' => 'No se encontró el cierre.']);
            break;
        }
        if (!acEsAdmin() && (int) $apertura['id_usuario'] !== (int) $_SESSION['Ses_id']) {
            echo json_encode(['ok' => false, 'message' => 'No tiene permiso para ver este cierre.']);
            break;
        }
        echo json_encode([
            'ok'             => true,
            'id_apertura'    => (int) $apertura['id'],
            'fecha_apertura' => $apertura['fecha_apertura'],
            'fecha_cierre'   => $apertura['fecha_cierre'],
            'base'           => $apertura['base'],
            'efectivo'       => $apertura['efectivo'],
            'tarjetas'       => $apertura['tarjetas'],
            'otros'          => $apertura['otros'],
            'observaciones'  => $apertura['observaciones'] ?? ''
        ]);
        break;

    /* ------------------------------------------------------------------
     * Editar un cierre (solo campos de valores y observaciones)
     * ------------------------------------------------------------------ */
    case 'u_cierre':
        try {
            $id_apertura = (int) ($_POST['id_apertura'] ?? 0);
            $apertura    = acObtenerApertura($id_apertura);

            if (!$apertura) {
                echo json_encode(['ok' => false, 'message' => 'No se encontró el cierre.']);
                break;
            }
            if (!acEsAdmin() && (int) $apertura['id_usuario'] !== (int) $_SESSION['Ses_id']) {
                echo json_encode(['ok' => false, 'message' => 'No tiene permiso para editar este cierre.']);
                break;
            }

            $observaciones = trim((string) ($_POST['observaciones'] ?? ''));
            if ($observaciones === '') {
                echo json_encode(['ok' => false, 'message' => 'Indica el motivo de la modificación en las observaciones.']);
                break;
            }

            $data = [
                'base'                => acNumero($_POST['base'] ?? 0),
                'efectivo'            => acNumero($_POST['efectivo'] ?? 0),
                'tarjetas'            => acNumero($_POST['tarjetas'] ?? 0),
                'otros'               => acNumero($_POST['otros'] ?? 0),
                'observaciones'       => mb_substr($observaciones, 0, 1000),
                'fecha_modifica'      => date('Y-m-d H:i:s'),
                'id_usuario_modifica' => (int) $_SESSION['Ses_id']
            ];

            $update = update('t_aperturas_cajas', $data, $id_apertura);
            echo json_encode([
                'ok'      => (bool) $update,
                'message' => $update ? 'Cierre actualizado correctamente.' : 'No fue posible actualizar el cierre.'
            ]);
        } catch (Throwable $th) {
            error_log('AperturaCierreCaja u_cierre: ' . $th->getMessage());
            echo json_encode(['ok' => false, 'message' => 'Ocurrió un error al actualizar el cierre.']);
        }
        break;

    /* ------------------------------------------------------------------
     * Historial de cierres de la bodega de la sesión
     * Admin (1) y super (4) ven todos los cajeros de la bodega; el resto solo los suyos.
     * ------------------------------------------------------------------ */
    case 'listar-cierres':
        try {
            $bodega = acBodegaSesion();
            if (!$bodega) {
                echo json_encode(['ok' => false, 'message' => 'No se encontró la bodega de la sesión.']);
                break;
            }

            $fechaInicio = acFecha($_POST['fecha_inicial'] ?? '') ?? date('Y-m-d');
            $fechaFin    = acFecha($_POST['fecha_final'] ?? '') ?? date('Y-m-d');
            if ($fechaFin < $fechaInicio) {
                echo json_encode(['ok' => false, 'message' => 'La fecha final no puede ser anterior a la inicial.']);
                break;
            }

            $idUsuario = acEsAdmin() ? 0 : (int) $_SESSION['Ses_id'];
            $idBodega  = $bodega['id'];

            $stmt = $conn->prepare('CALL sp_reporte_cierre_caja(?, ?, ?, ?)');
            if (!$stmt) {
                throw new Exception($conn->error);
            }
            $stmt->bind_param('ssii', $fechaInicio, $fechaFin, $idUsuario, $idBodega);
            $stmt->execute();

            $result = $stmt->get_result();
            $cierres = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
            if ($result) {
                $result->free();
            }
            // Consumir los resultados adicionales del CALL
            while ($stmt->more_results() && $stmt->next_result()) {
                $extra = $stmt->get_result();
                if ($extra) {
                    $extra->free();
                }
            }
            $stmt->close();

            echo json_encode([
                'ok'      => true,
                'bodega'  => ['id' => $bodega['id'], 'nombre' => $bodega['nombre']],
                'admin'   => acEsAdmin(),
                'cierres' => $cierres
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $th) {
            error_log('AperturaCierreCaja listar-cierres: ' . $th->getMessage());
            echo json_encode(['ok' => false, 'message' => 'No fue posible consultar el historial de cierres.']);
        }
        break;

    /* ------------------------------------------------------------------
     * Movimientos de un cierre: ingresos (sw 10), egresos (sw 9),
     * ventas (sw 5) y devoluciones (sw 4), con los mismos criterios
     * de sp_reporte_cierre_caja para que los totales coincidan.
     * ------------------------------------------------------------------ */
    case 'detalle-cierre':
        try {
            $apertura = acObtenerApertura($_POST['id_apertura'] ?? 0);
            $bodega   = acBodegaSesion();

            if (!$apertura || !$bodega) {
                echo json_encode(['ok' => false, 'message' => 'No se encontró el cierre.']);
                break;
            }
            if ((int) $apertura['id_bodega'] !== $bodega['id']) {
                echo json_encode(['ok' => false, 'message' => 'El cierre no pertenece a la bodega de la sesión.']);
                break;
            }
            if (!acEsAdmin() && (int) $apertura['id_usuario'] !== (int) $_SESSION['Ses_id']) {
                echo json_encode(['ok' => false, 'message' => 'No tiene permiso para ver este cierre.']);
                break;
            }

            $conn->query('SET SESSION group_concat_max_len = 100000');

            $idUsuario = (int) $apertura['id_usuario'];
            $idBodega  = (int) $apertura['id_bodega'];
            $desde     = $apertura['fecha_apertura'];
            $hasta     = $apertura['fecha_cierre'] ?: date('Y-m-d H:i:s');

            $columnas = 't.id, t.sw, t.tipo, t.numero, t.fecha_hora, t.nit, t.id_tercero,
                         t.notas, t.documento, t.valor_total, t.id_concepto_caja, t.cuenta_caja';

            // Ingresos y egresos de caja
            $stmt = $conn->prepare(
                "SELECT $columnas,
                        (SELECT GROUP_CONCAT(CONCAT(IFNULL(m.descripcion, 'Sin medio'), '|', p.valor) SEPARATOR ';;')
                           FROM t_transacciones_pago p
                           LEFT JOIN t_medios_pagos m ON m.id = p.id_medio_pago
                          WHERE p.id_transaccion = t.id) AS medios_detalle
                   FROM t_transacciones t
                  WHERE t.sw IN (9, 10)
                    AND IFNULL(t.anulado, 0) = 0
                    AND t.id_usuario = ?
                    AND t.id_bodega = ?
                    AND t.fecha_hora BETWEEN ? AND ?
                  ORDER BY t.fecha_hora"
            );
            $stmt->bind_param('iiss', $idUsuario, $idBodega, $desde, $hasta);
            $stmt->execute();
            $movCaja = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            // Ventas y devoluciones según los pagos del turno
            $stmt = $conn->prepare(
                "SELECT $columnas,
                        GROUP_CONCAT(CONCAT(IFNULL(m.descripcion, 'Sin medio'), '|', p.valor) SEPARATOR ';;') AS medios_detalle,
                        SUM(p.valor) AS valor_pagado
                   FROM t_transacciones_pago p
                  INNER JOIN t_transacciones t
                          ON t.id = p.id_transaccion
                         AND IFNULL(t.anulado, 0) = 0
                         AND t.sw IN (4, 5)
                   LEFT JOIN t_medios_pagos m ON m.id = p.id_medio_pago
                  WHERE p.id_usuario = ?
                    AND p.fecha_hora BETWEEN ? AND ?
                  GROUP BY t.id
                  ORDER BY t.fecha_hora"
            );
            $stmt->bind_param('iss', $idUsuario, $desde, $hasta);
            $stmt->execute();
            $movVentas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $resp  = ['ok' => true, 'ingresos' => [], 'egresos' => [], 'ventas' => [], 'devoluciones' => []];
            $grupo = ['10' => 'ingresos', '9' => 'egresos', '5' => 'ventas', '4' => 'devoluciones'];

            foreach (array_merge($movCaja, $movVentas) as $row) {
                $medios = [];
                if (!empty($row['medios_detalle'])) {
                    foreach (explode(';;', $row['medios_detalle']) as $item) {
                        [$desc, $valor] = array_pad(explode('|', $item, 2), 2, 0);
                        $medios[] = ['medio' => $desc, 'valor' => (float) $valor];
                    }
                }
                unset($row['medios_detalle']);
                $row['medios'] = $medios;

                $tipo = $grupo[(string) $row['sw']] ?? null;
                if ($tipo) {
                    $resp[$tipo][] = $row;
                }
            }

            echo json_encode($resp, JSON_UNESCAPED_UNICODE);
        } catch (Throwable $th) {
            error_log('AperturaCierreCaja detalle-cierre: ' . $th->getMessage());
            echo json_encode(['ok' => false, 'message' => 'No fue posible consultar el detalle del cierre.']);
        }
        break;

    default:
        echo json_encode(['ok' => false, 'message' => 'Operación no válida']);
        break;
}