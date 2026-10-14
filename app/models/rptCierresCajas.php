<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once('bd.php');
require_once('funciones.php');

if (!sessionExists()) {
    echo json_encode([
        'error'   => true,
        'message' => 'La sesion se ha cerrado, por favor ingrese nuevamente'
    ]);
    exit;
}

$conn = conectar();
$op   = $_POST['op'] ?? '';

/**
 * Devuelve la fecha si tiene formato Y-m-d válido, o null.
 */
function ccFechaValida($valor)
{
    $d = DateTime::createFromFormat('Y-m-d', (string) $valor);
    return ($d && $d->format('Y-m-d') === $valor) ? $valor : null;
}

switch ($op) {

    case 's_cierres_cajas':
        try {
            $fechaInicio = ccFechaValida($_POST['fechaInicio'] ?? '') ?? date('Y-m-d');
            $fechaFin    = ccFechaValida($_POST['fechaFin'] ?? '') ?? date('Y-m-d');
            $bodega      = (int) ($_POST['bodegaSeleccionada'] ?? 0);

            if ($fechaFin < $fechaInicio) {
                echo json_encode([
                    'error'   => true,
                    'message' => 'La fecha final no puede ser anterior a la fecha inicial'
                ]);
                break;
            }

            // Solo admin (1) y super (4) ven todos los cierres; el resto solo los suyos
            $idRol     = (string) ($_SESSION['Ses_roles_id'] ?? '');
            $idUsuario = in_array($idRol, ['1', '4'], true) ? 0 : (int) $_SESSION['Ses_id'];

            $stmt = $conn->prepare('CALL sp_reporte_cierre_caja(?, ?, ?, ?)');
            if (!$stmt) {
                throw new Exception($conn->error);
            }

            $stmt->bind_param('ssii', $fechaInicio, $fechaFin, $idUsuario, $bodega);
            $stmt->execute();

            $result = $stmt->get_result();
            $data   = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
            if ($result) {
                $result->free();
            }

            // Un CALL devuelve resultados adicionales; se consumen para no dejar la conexión
            // en estado "Commands out of sync" si se ejecutan más consultas después.
            while ($stmt->more_results() && $stmt->next_result()) {
                $extra = $stmt->get_result();
                if ($extra) {
                    $extra->free();
                }
            }
            $stmt->close();

            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log('rptCierresCajas s_cierres_cajas: ' . $e->getMessage());
            echo json_encode([
                'error'   => true,
                'message' => 'No fue posible consultar los cierres de caja'
            ]);
        }
        break;

    /* ------------------------------------------------------------------
     * Detalle de un cierre: ingresos (sw 10), egresos (sw 9),
     * ventas (sw 5) y devoluciones (sw 4) con sus medios de pago.
     * Usa los mismos criterios que sp_reporte_cierre_caja para que
     * los totales coincidan con el reporte.
     * ------------------------------------------------------------------ */
    case 's_detalle_cierre':
        try {
            $idApertura = (int) ($_POST['idApertura'] ?? 0);
            if ($idApertura <= 0) {
                echo json_encode(['error' => true, 'message' => 'Cierre no válido']);
                break;
            }

            // 1. Datos del cierre
            $stmt = $conn->prepare(
                'SELECT id_apertura, id_usuario, id_bodega, fecha_apertura, fecha_cierre
                   FROM v_cierres_cajas
                  WHERE id_apertura = ?
                  LIMIT 1'
            );
            $stmt->bind_param('i', $idApertura);
            $stmt->execute();
            $cierre = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$cierre) {
                echo json_encode(['error' => true, 'message' => 'No se encontró el cierre']);
                break;
            }

            // Solo admin (1) y super (4) pueden ver cierres de otros usuarios
            $idRol = (string) ($_SESSION['Ses_roles_id'] ?? '');
            if (!in_array($idRol, ['1', '4'], true) && (int) $cierre['id_usuario'] !== (int) $_SESSION['Ses_id']) {
                echo json_encode(['error' => true, 'message' => 'No tiene permiso para ver este cierre']);
                break;
            }

            // Evita que GROUP_CONCAT corte la lista de medios de pago
            $conn->query('SET SESSION group_concat_max_len = 100000');

            $idUsuario = (int) $cierre['id_usuario'];
            $idBodega  = (int) $cierre['id_bodega'];
            $desde     = $cierre['fecha_apertura'];
            $hasta     = $cierre['fecha_cierre'] ?: date('Y-m-d H:i:s');

            // 2. Ingresos y egresos de caja (mismo criterio que el SP)
            $stmt = $conn->prepare(
                "SELECT t.id, t.sw, t.tipo, t.numero, t.fecha_hora, t.nit, t.id_tercero,
                        t.notas, t.documento, t.valor_total, t.id_concepto_caja, t.cuenta_caja,
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

            // 3. Ventas y devoluciones según los pagos del turno (mismo criterio que el SP)
            $stmt = $conn->prepare(
                "SELECT t.id, t.sw, t.tipo, t.numero, t.fecha_hora, t.nit, t.id_tercero,
                        t.notas, t.documento, t.valor_total, t.id_concepto_caja, t.cuenta_caja,
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

            // 4. Separar por tipo y convertir los medios a arreglo
            $resp = ['ingresos' => [], 'egresos' => [], 'ventas' => [], 'devoluciones' => []];
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
        } catch (Throwable $e) {
            error_log('rptCierresCajas s_detalle_cierre: ' . $e->getMessage());
            echo json_encode([
                'error'   => true,
                'message' => 'No fue posible consultar el detalle del cierre'
            ]);
        }
        break;

    default:
        echo json_encode([
            'error'   => true,
            'message' => 'Operación no válida'
        ]);
        break;
}