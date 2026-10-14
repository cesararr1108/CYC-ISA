<?php
/**
 * ============================================
 * Modelo: Control de Temperatura
 * Descripción: CRUD para registro de temperatura y humedad
 * Tabla: t_control_temperatura
 * ============================================
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

session_start();
require_once('bd.php');
require_once('funciones.php');

$conn = conectar();

// Obtener operación desde la petición
$op = isset($_REQUEST['op']) ? trim($_REQUEST['op']) : '';

// Enrutamiento de operaciones CRUD
switch ($op) {
    case 'listar':
        listar($conn);
        break;
    
    case 'guardar':
        guardar($conn);
        break;
    
    case 'actualizar':
        actualizar($conn);
        break;
    
    case 'eliminar':
        eliminar($conn);
        break;
    
    case 'obtener_registro':
        obtener_registro($conn);
        break;
    
    case 'historial':
        historial($conn);
        break;
    
    default:
        http_response_code(400);
        echo json_encode([
            'error' => true,
            'message' => 'Operación no válida',
            'op' => $op
        ]);
        break;
}

$conn->close();


// ============================================
// FUNCIONES CRUD
// ============================================

/**
 * LISTAR - Obtiene registros filtrados por fecha
 */
function listar($conn)
{
    try {
        $fecha = isset($_REQUEST['fecha']) && !empty($_REQUEST['fecha']) 
                 ? $_REQUEST['fecha'] 
                 : date('Y-m-d');
        
        // Validar formato de fecha
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            throw new Exception('Formato de fecha inválido: ' . $fecha);
        }

        $sql = "SELECT 
                    id,
                    fecha,
                    hora_registro,
                    temperatura,
                    humedad,
                    estado_climatico,
                    DATE_FORMAT(fecha_creacion, '%d/%m/%Y %H:%i') as fecha_creacion,
                    DATE_FORMAT(fecha_actualizacion, '%d/%m/%Y %H:%i') as fecha_actualizacion
                FROM t_control_temperatura
                WHERE fecha = ?
                ORDER BY 
                    CASE 
                        WHEN hora_registro = '9AM' THEN 1
                        WHEN hora_registro = '1PM' THEN 2
                        WHEN hora_registro = '5PM' THEN 3
                    END ASC";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error al preparar consulta: " . $conn->error);
        }

        $stmt->bind_param('s', $fecha);
        if (!$stmt->execute()) {
            throw new Exception("Error al ejecutar consulta: " . $stmt->error);
        }
        $result = $stmt->get_result();

        $datos = [];
        while ($row = $result->fetch_assoc()) {
            $datos[] = $row;
        }

        $stmt->close();

        echo json_encode([
            'error' => false,
            'message' => 'Datos cargados correctamente (' . count($datos) . ' registros)',
            'data' => $datos
        ]);

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'error' => true,
            'message' => 'Error al listar: ' . $e->getMessage()
        ]);
    }
}

/**
 * GUARDAR - Inserta nuevo registro
 */
function guardar($conn)
{
    try {
        // Validar campos requeridos
        $fecha = isset($_REQUEST['fecha']) ? trim($_REQUEST['fecha']) : '';
        $hora_registro = isset($_REQUEST['hora_registro']) ? trim($_REQUEST['hora_registro']) : '';
        $temperatura = isset($_REQUEST['temperatura']) ? floatval($_REQUEST['temperatura']) : null;
        $humedad = isset($_REQUEST['humedad']) ? floatval($_REQUEST['humedad']) : null;
        $estado_climatico = isset($_REQUEST['estado_climatico']) ? trim($_REQUEST['estado_climatico']) : '';

        // Validaciones
        if (empty($fecha) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            throw new Exception('Fecha inválida');
        }

        if (empty($hora_registro) || !in_array($hora_registro, ['9AM', '1PM', '5PM'])) {
            throw new Exception('Hora de registro inválida. Debe ser: 9AM, 1PM ó 5PM');
        }

        if ($temperatura === null || $temperatura < -50 || $temperatura > 60) {
            throw new Exception('Temperatura inválida. Debe estar entre -50 y 60°C');
        }

        if ($humedad === null || $humedad < 0 || $humedad > 100) {
            throw new Exception('Humedad inválida. Debe estar entre 0 y 100%');
        }

        // Verificar si ya existe un registro para esa fecha y hora
        $sqlCheck = "SELECT id FROM t_control_temperatura WHERE fecha = ? AND hora_registro = ?";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->bind_param('ss', $fecha, $hora_registro);
        $stmtCheck->execute();
        $resultCheck = $stmtCheck->get_result();

        if ($resultCheck->num_rows > 0) {
            throw new Exception('Ya existe un registro para esta fecha y hora. Use actualizar para modificarlo.');
        }
        $stmtCheck->close();

        // Insertar registro
        $sql = "INSERT INTO t_control_temperatura 
                (fecha, hora_registro, temperatura, humedad, estado_climatico) 
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error al preparar: " . $conn->error);
        }

        $stmt->bind_param('ssdds', $fecha, $hora_registro, $temperatura, $humedad, $estado_climatico);
        $stmt->execute();
        $id_insertado = $conn->insert_id;
        $stmt->close();

        // Registrar auditoría
        registrarAuditoria(
            'INSERT',
            't_control_temperatura',
            $id_insertado,
            ['fecha' => $fecha, 'hora' => $hora_registro, 'temp' => $temperatura, 'humedad' => $humedad]
        );

        echo json_encode([
            'error' => false,
            'message' => 'Registro guardado correctamente',
            'id' => $id_insertado
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'error' => true,
            'message' => 'Error al guardar: ' . $e->getMessage()
        ]);
    }
}

/**
 * ACTUALIZAR - Modifica un registro existente
 */
function actualizar($conn)
{
    try {
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        
        if ($id <= 0) {
            throw new Exception('ID inválido');
        }

        $temperatura = isset($_REQUEST['temperatura']) ? floatval($_REQUEST['temperatura']) : null;
        $humedad = isset($_REQUEST['humedad']) ? floatval($_REQUEST['humedad']) : null;
        $estado_climatico = isset($_REQUEST['estado_climatico']) ? trim($_REQUEST['estado_climatico']) : '';

        // Validaciones
        if ($temperatura === null || $temperatura < -50 || $temperatura > 60) {
            throw new Exception('Temperatura inválida');
        }

        if ($humedad === null || $humedad < 0 || $humedad > 100) {
            throw new Exception('Humedad inválida');
        }

        // Obtener registro anterior para auditoría
        $sqlAnterior = "SELECT temperatura, humedad, estado_climatico FROM t_control_temperatura WHERE id = ?";
        $stmtAnterior = $conn->prepare($sqlAnterior);
        $stmtAnterior->bind_param('i', $id);
        $stmtAnterior->execute();
        $resultAnterior = $stmtAnterior->get_result();
        
        if ($resultAnterior->num_rows === 0) {
            throw new Exception('Registro no encontrado');
        }

        $registroAnterior = $resultAnterior->fetch_assoc();
        $stmtAnterior->close();

        // Actualizar
        $sql = "UPDATE t_control_temperatura 
                SET temperatura = ?, 
                    humedad = ?, 
                    estado_climatico = ?
                WHERE id = ?";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error al preparar: " . $conn->error);
        }

        $stmt->bind_param('ddsi', $temperatura, $humedad, $estado_climatico, $id);
        $stmt->execute();
        $stmt->close();

        // Registrar auditoría de cambios
        registrarAuditoriaCambio(
            'UPDATE',
            't_control_temperatura',
            $id,
            $registroAnterior,
            ['temperatura' => $temperatura, 'humedad' => $humedad, 'estado_climatico' => $estado_climatico]
        );

        echo json_encode([
            'error' => false,
            'message' => 'Registro actualizado correctamente'
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'error' => true,
            'message' => 'Error al actualizar: ' . $e->getMessage()
        ]);
    }
}

/**
 * ELIMINAR - Borra un registro
 */
function eliminar($conn)
{
    try {
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        
        if ($id <= 0) {
            throw new Exception('ID inválido');
        }

        // Obtener datos antes de eliminar para auditoría
        $sqlAnt = "SELECT fecha, hora_registro FROM t_control_temperatura WHERE id = ?";
        $stmtAnt = $conn->prepare($sqlAnt);
        $stmtAnt->bind_param('i', $id);
        $stmtAnt->execute();
        $resultAnt = $stmtAnt->get_result();

        if ($resultAnt->num_rows === 0) {
            throw new Exception('Registro no encontrado');
        }
        $stmtAnt->close();

        // Eliminar
        $sql = "DELETE FROM t_control_temperatura WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        // Registrar auditoría
        registrarAuditoria('DELETE', 't_control_temperatura', $id);

        echo json_encode([
            'error' => false,
            'message' => 'Registro eliminado correctamente'
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'error' => true,
            'message' => 'Error al eliminar: ' . $e->getMessage()
        ]);
    }
}

/**
 * OBTENER_REGISTRO - Obtiene un registro individual
 */
function obtener_registro($conn)
{
    try {
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        
        if ($id <= 0) {
            throw new Exception('ID inválido');
        }

        $sql = "SELECT 
                    id,
                    fecha,
                    hora_registro,
                    temperatura,
                    humedad,
                    estado_climatico
                FROM t_control_temperatura
                WHERE id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception('Registro no encontrado');
        }

        $registro = $result->fetch_assoc();
        $stmt->close();

        echo json_encode([
            'error' => false,
            'message' => 'Registro obtenido',
            'data' => $registro
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'error' => true,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

/**
 * HISTORIAL - Obtiene registros en rango de fechas
 */
function historial($conn)
{
    try {
        $fecha_desde = isset($_REQUEST['fecha_desde']) && !empty($_REQUEST['fecha_desde']) 
                      ? $_REQUEST['fecha_desde'] 
                      : date('Y-m-d', strtotime('-7 days'));
        
        $fecha_hasta = isset($_REQUEST['fecha_hasta']) && !empty($_REQUEST['fecha_hasta']) 
                      ? $_REQUEST['fecha_hasta'] 
                      : date('Y-m-d');
        
        $hora_registro = isset($_REQUEST['hora_registro']) && !empty($_REQUEST['hora_registro']) 
                        ? $_REQUEST['hora_registro'] 
                        : '';

        // Validar formato de fechas
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_desde)) {
            throw new Exception('Formato de fecha desde inválido');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_hasta)) {
            throw new Exception('Formato de fecha hasta inválido');
        }

        // Validar que desde <= hasta
        if (strtotime($fecha_desde) > strtotime($fecha_hasta)) {
            throw new Exception('La fecha desde no puede ser mayor que la fecha hasta');
        }

        $sql = "SELECT 
                    id,
                    fecha,
                    hora_registro,
                    temperatura,
                    humedad,
                    estado_climatico,
                    DATE_FORMAT(fecha_creacion, '%d/%m/%Y %H:%i') as fecha_creacion,
                    DATE_FORMAT(fecha_actualizacion, '%d/%m/%Y %H:%i') as fecha_actualizacion
                FROM t_control_temperatura
                WHERE fecha BETWEEN ? AND ?";

        $params = [$fecha_desde, $fecha_hasta];
        $types = 'ss';

        // Agregar filtro de hora si se proporciona
        if (!empty($hora_registro)) {
            $sql .= " AND hora_registro = ?";
            $params[] = $hora_registro;
            $types .= 's';
        }

        $sql .= " ORDER BY fecha DESC, 
                    CASE 
                        WHEN hora_registro = '9AM' THEN 1
                        WHEN hora_registro = '1PM' THEN 2
                        WHEN hora_registro = '5PM' THEN 3
                    END ASC";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error al preparar consulta: " . $conn->error);
        }

        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            throw new Exception("Error al ejecutar consulta: " . $stmt->error);
        }
        $result = $stmt->get_result();

        $datos = [];
        while ($row = $result->fetch_assoc()) {
            $datos[] = $row;
        }

        $stmt->close();

        echo json_encode([
            'error' => false,
            'message' => 'Se encontraron ' . count($datos) . ' registros',
            'data' => $datos
        ]);

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'error' => true,
            'message' => 'Error al obtener historial: ' . $e->getMessage()
        ]);
    }
}
?>
