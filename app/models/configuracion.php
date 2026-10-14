<?php
session_start();
require_once('bd.php');
require_once('funciones.php');
$conn = conectar();

$op = isset($_REQUEST['op']) ? $_REQUEST['op'] : '';

switch ($op) {
    case 'listar':
        listar($conn);
        break;

    case 'g_config':
        guardar($conn);
        break;

    case 'u_config':
        actualizar($conn);
        break;

    case 'd_config':
        eliminar($conn);
        break;
    case 'validar_usuario':
        usuarioAutorizado();
        break;

    default:
        echo json_encode([
            'error' => true,
            'message' => 'Operación no válida'
        ]);
        break;
}

$conn->close();

function usuarioAutorizado()
{
    if ($_SESSION["Ses_usuario"] == 'CYC') {
        echo json_encode([
            'autorizado' => true,
            'message' => 'Usuario válido'
        ]);
    } else {
        echo json_encode([
            'autorizado' => false,
            'message' => 'Usuario no autorizado'
        ]);
    }
}

// ============ FUNCIONES CRUD ============

function listar($conn)
{
    $filtro = isset($_REQUEST['filtro']) && $_REQUEST['filtro'] != '' ? $_REQUEST['filtro'] : '';

    $sql = "SELECT 
            c.id,
            c.id_modulo,
            CASE 
              WHEN c.id_modulo = 0 THEN 'Global'
              ELSE m.titulo
            END as nombre_modulo,
            c.descripcion,
            c.Clave,
            c.valor,
            c.validacion
          FROM t_config_modulos c
          LEFT JOIN t_modulos m ON m.id = c.id_modulo
          WHERE 1=1 ";

    if (!empty($filtro)) {
        $sql .= " AND (c.descripcion LIKE '%$filtro%' 
              OR c.Clave LIKE '%$filtro%' 
              OR (c.id_modulo = 0) 
              OR m.titulo LIKE '%$filtro%')";
    }

    $sql .= " ORDER BY c.id_modulo, c.descripcion ASC";

    $datos = GenerarArray($sql);

    echo json_encode([
        'error' => false,
        'message' => 'success',
        'data' => $datos
    ]);
}

function guardar($conn)
{
    try {
        // Validar campos requeridos (id_modulo puede ser 0 para global)
        $id_modulo = isset($_REQUEST['idModuloSelect']) ? intval($_REQUEST['idModuloSelect']) : null;
        $descripcion = isset($_REQUEST['descripcionConfig']) ? trim($_REQUEST['descripcionConfig']) : '';
        $clave = isset($_REQUEST['claveConfig']) ? trim($_REQUEST['claveConfig']) : '';
        $valor = isset($_REQUEST['valorConfig']) ? $_REQUEST['valorConfig'] : '';
        $validacion = isset($_REQUEST['validacionConfig']) && trim($_REQUEST['validacionConfig']) !== '' ? trim($_REQUEST['validacionConfig']) : null;

        // Validar que id_modulo no sea null (puede ser 0) y otros campos no estén vacíos
        // Nota: $valor puede ser "0" (válido aunque parezca vacío)
        if ($id_modulo === null || empty($descripcion) || empty($clave) || $valor === '') {
            echo json_encode([
                'error' => true,
                'message' => 'Todos los campos son requeridos',
                'id' => 0,
                'id_modulo' => $id_modulo,
                'descripcion' => $descripcion,
                'clave' => $clave,
                'valor' => $valor,
                'validacion' => $validacion

            ]);
            return;
        }

        // Validar que validacion sea JSON válido si se proporciona
        if ($validacion && !json_decode($validacion)) {
            echo json_encode([
                'error' => true,
                'message' => 'El JSON de validación no es válido',
                'id' => 0
            ]);
            return;
        }

        // Preparar datos
        $data = [
            'id_modulo' => $id_modulo,
            'descripcion' => $descripcion,
            'Clave' => $clave,
            'valor' => $valor,
            'validacion' => $validacion
        ];

        // Validar que la clave sea única
        $checkQuery = "SELECT id FROM t_config_modulos 
                   WHERE Clave = '" . $conn->real_escape_string($clave) . "' AND id_modulo = $id_modulo";
        $checkResult = GenerarArray($checkQuery);

        if (count($checkResult) > 0) {
            echo json_encode([
                'error' => true,
                'message' => 'La clave ya existe',
                'id' => 0
            ]);
            return;
        }

        // Insertar
        $id = insertReturnId('t_config_modulos', $data, true, $conn);

        if ($id > 0) {
            // Registrar auditoría
            $tipo_config = $id_modulo == 0 ? 'Global' : 'Módulo';
            registrarAuditoria(
                'CREAR',
                't_config_modulos',
                $id,
                [
                    'tipo' => $tipo_config,
                    'descripcion' => $descripcion,
                    'Clave' => $clave,
                    'valor' => $valor,
                    'validacion' => $validacion
                ],
                null,
                $conn
            );

            echo json_encode([
                'error' => false,
                'message' => 'Configuración guardada correctamente',
                'id' => $id
            ]);
        } else {
            echo json_encode([
                'error' => true,
                'message' => 'Error al guardar la configuración',
                'id' => 0
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'error' => true,
            'message' => $e->getMessage(),
            'id' => 0
        ]);
    }
}

function actualizar($conn)
{
    try {
        // Validar campos requeridos (id_modulo puede ser 0 para global)
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        $id_modulo = isset($_REQUEST['idModuloSelect']) ? intval($_REQUEST['idModuloSelect']) : null;
        $descripcion = isset($_REQUEST['descripcionConfig']) ? trim($_REQUEST['descripcionConfig']) : '';
        $clave = isset($_REQUEST['claveConfig']) ? trim($_REQUEST['claveConfig']) : '';
        $valor = isset($_REQUEST['valorConfig']) ? $_REQUEST['valorConfig'] : '';
        $validacion = isset($_REQUEST['validacionConfig']) && trim($_REQUEST['validacionConfig']) !== '' ? trim($_REQUEST['validacionConfig']) : null;

        // Validar que id sea válido, id_modulo no sea null (puede ser 0) y otros campos no estén vacíos
        // Nota: $valor puede ser "0" (válido aunque parezca vacío)
        if ($id <= 0 || $id_modulo === null || empty($descripcion) || empty($clave) || $valor === '') {
            echo json_encode([
                'error' => true,
                'message' => 'Todos los campos son requeridos'
            ]);
            return;
        }

        // Validar que validacion sea JSON válido si se proporciona
        if ($validacion && !json_decode($validacion)) {
            echo json_encode([
                'error' => true,
                'message' => 'El JSON de validación no es válido'
            ]);
            return;
        }

        // Preparar datos
        $data = [
            'id_modulo' => $id_modulo,
            'descripcion' => $descripcion,
            'Clave' => $clave,
            'valor' => $valor,
            'validacion' => $validacion
        ];

        // Validar que la clave sea única en otro registro
        $checkQuery = "SELECT id FROM t_config_modulos 
                   WHERE Clave = '" . $conn->real_escape_string($clave) . "' 
                   AND id_modulo = $id_modulo
                   AND id != $id";
        $checkResult = GenerarArray($checkQuery);

        if (count($checkResult) > 0) {
            echo json_encode([
                'error' => true,
                'message' => 'La clave ya existe en otro registro'
            ]);
            return;
        }

        // Obtener datos anteriores para auditoría
        $antes = GenerarArray("SELECT * FROM t_config_modulos WHERE id = $id");
        $estadoAnterior = $antes[0] ?? [];

        // Actualizar
        $update = update('t_config_modulos', array_merge(['id' => $id], $data), $id);

        if ($update) {
            // Registrar auditoría
            $tipo_config = $id_modulo == 0 ? 'Global' : 'Módulo';
            registrarAuditoriaCambio(
                'ACTUALIZAR',
                't_config_modulos',
                $id,
                $estadoAnterior,
                $data,
                ['tipo' => $tipo_config],
                null,
                $conn
            );

            echo json_encode([
                'error' => false,
                'message' => 'Configuración actualizada correctamente'
            ]);
        } else {
            echo json_encode([
                'error' => true,
                'message' => 'Error al actualizar la configuración'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'error' => true,
            'message' => $e->getMessage()
        ]);
    }
}

function eliminar($conn)
{
    try {
        if (empty($_REQUEST['id'])) {
            echo json_encode([
                'error' => true,
                'message' => 'ID requerido'
            ]);
            return;
        }

        $id = intval($_REQUEST['id']);

        // Obtener datos para auditoría
        $datos = GenerarArray("SELECT * FROM t_config_modulos WHERE id = $id");

        if (empty($datos)) {
            echo json_encode([
                'error' => true,
                'message' => 'Configuración no encontrada'
            ]);
            return;
        }

        // Eliminar
        $delete = Query("DELETE FROM t_config_modulos WHERE id = $id");

        if ($delete) {
            // Registrar auditoría
            registrarAuditoria(
                'ELIMINAR',
                't_config_modulos',
                $id,
                $datos[0],
                null,
                $conn
            );

            echo json_encode([
                'error' => false,
                'message' => 'Configuración eliminada correctamente'
            ]);
        } else {
            echo json_encode([
                'error' => true,
                'message' => 'Error al eliminar la configuración'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'error' => true,
            'message' => $e->getMessage()
        ]);
    }
}
