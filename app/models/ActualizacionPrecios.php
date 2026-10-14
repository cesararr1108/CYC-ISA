<?php
session_start();
require_once('bd.php');
require_once('funciones.php');
$conn = conectar();
switch ($_POST["op"]) {
    case "consultar_productos_lista":
        $codigo = $_POST['codigo'];
        $lista = $_POST['lista'];
        $parametros = [];

        $sql = "SELECT 
                    r.codigo,
                    r.descripcion,
                    IFNULL(l.precio,0) as precio_actual
                FROM t_referencias r
                LEFT JOIN t_referencias_listas l  ON l.id_referencia = r.id AND l.lista = ?
                WHERE r.codigo = ?
                LIMIT 1";


        $parametros[] = array(
            'tipo'=>'i',
            'valor'=>$lista
        );
        $parametros[] = array(
            'tipo'=>'s',
            'valor'=>$codigo
        );
        $data = GenerarArrayPrepare($sql, $parametros);

        echo json_encode([
            "error" => false,
            "data" => $data
        ]);
        break;
    case "consultar_productos_excel":

        $lista = $_POST['lista'];
        $tipo_lista = $_POST['tipo_lista'];
        $datos = json_decode($_POST['datos'], true);
        $parametros = [];

        if (!$datos) {
            echo json_encode([
                "error" => true,
                "message" => "Datos inválidos"
            ]);
            exit;
        }

        $respuesta = [];

        foreach ($datos as $row) {

            $codigo = trim($row['codigo']);
            $nuevo_precio = floatval($row['nuevo_precio']);

            $sql = "SELECT 
                    r.codigo,
                    r.descripcion,
                    IFNULL(l.precio,0) as precio_actual
                FROM t_referencias r
                LEFT JOIN t_referencias_listas l ON l.id_referencia = r.id AND l.lista = ?
                WHERE r.codigo = ?
                LIMIT 1";
       
            $parametros[] = array(
                'tipo'=>'i',
                'valor'=>$lista
            );
            $parametros[] = array(
                'tipo'=>'s',
                'valor'=>$codigo
            );
            $data = GenerarArrayPrepare($sql, $parametros);
            //$data = GenerarArray($sql);

            if (count($data)) {

                $respuesta[] = [
                    "codigo" => $data[0]['codigo'],
                    "descripcion" => $data[0]['descripcion'],
                    "precio_actual" => $data[0]['precio_actual'],
                    "tipo_lista" => $tipo_lista,
                    "nuevo_precio" => $nuevo_precio
                ];
            }
        }

        echo json_encode([
            "error" => false,
            "data" => $respuesta
        ]);
        break;
    case "guardar_actualizacion_masiva":
        $items = isset($_POST['items']) ? json_decode($_POST['items'], true) : [];
        $usuario = isset($_SESSION['Ses_id']) ? intval($_SESSION['Ses_id']) : 0;

        if (!$items || !is_array($items)) {
            echo json_encode([
                "error" => true,
                "message" => "Datos inválidos para actualizar precios"
            ]);
            exit;
        }

        $conn = conectar();
        $errores = [];
        $exito = 0;
        $sinCambios = 0;

        try {
            // 🚀 Iniciamos transacción
            $conn->begin_transaction();

            foreach ($items as $item) {

                $codigo = $item['codigo'] ?? '';
                $tipo_lista = $item['tipo_lista'] ?? '';
                $nuevo_precio = isset($item['nuevo_precio']) ? floatval($item['nuevo_precio']) : 0;
                $lista = isset($item['lista']) ? intval($item['lista']) : 0;

                if (!$codigo || !$tipo_lista || !$lista) {
                    $errores[] = "Producto con código vacío o tipo_lista vacío o lista vacía: " . json_encode($item);
                    continue;
                }

                // Buscar id de referencia
                $stmt = $conn->prepare("SELECT id FROM t_referencias WHERE codigo = ?");
                $stmt->bind_param("s", $codigo);
                $stmt->execute();
                $result = $stmt->get_result();
                $ref = $result->fetch_assoc();

                if (!$ref) {
                    $errores[] = "Código no encontrado: $codigo";
                    continue;
                }

                $id_referencia = $ref['id'];

                // Verificar si ya existe en lista
                $stmt = $conn->prepare("SELECT id, precio FROM t_referencias_listas WHERE id_referencia = ? AND lista = ?");
                $stmt->bind_param("ii", $id_referencia, $lista);
                $stmt->execute();
                $result = $stmt->get_result();
                $exist = $result->fetch_assoc();

                if ($exist) {
                    // Guardar precio anterior para auditoría
                    $precio_ant = floatval($exist['precio']);

                    // Si no hay cambio real, no actualizar ni auditar
                    if ($precio_ant == $nuevo_precio) {
                        $sinCambios++;
                        continue;
                    }

                    // Actualizar precio
                    $stmt_up = $conn->prepare(" UPDATE t_referencias_listas SET precio = ?, tipo_lista = ? WHERE id = ?");
                    $stmt_up->bind_param("dsi", $nuevo_precio, $tipo_lista, $exist['id']);
                    $stmt_up->execute();

                    // Registrar auditoría solo con campos cambiados
                    registrarAuditoriaCambio(
                        'UPDATE',
                        't_referencias_listas',
                        intval($exist['id']),
                        [
                            'precio' => $precio_ant
                        ],
                        [
                            'precio' => $nuevo_precio
                        ],
                        [
                            'codigo' => $codigo,
                            'id_referencia' => $id_referencia,
                            'lista' => $lista,
                            'tipo_lista' => $tipo_lista,
                            'modulo' => 'ActualizacionPrecios',
                            'proceso' => 'actualizacion_masiva'
                        ],
                        $usuario,
                        $conn
                    );
                } else {
                    // Insertar nuevo registro
                    $stmt_in = $conn->prepare("INSERT INTO t_referencias_listas (id_referencia, lista, tipo_lista, precio) VALUES (?, ?, ?, ?)");
                    $stmt_in->bind_param("iisd", $id_referencia, $lista, $tipo_lista, $nuevo_precio);
                    $stmt_in->execute();

                    $nuevo_id = $stmt_in->insert_id;

                    // Registrar auditoría de creación de precio en lista
                    registrarAuditoriaCambio(
                        'CREATE',
                        't_referencias_listas',
                        intval($nuevo_id),
                        [],
                        [
                            'id_referencia' => $id_referencia,
                            'lista' => $lista,
                            'tipo_lista' => $tipo_lista,
                            'precio' => $nuevo_precio
                        ],
                        [
                            'codigo' => $codigo,
                            'modulo' => 'ActualizacionPrecios',
                            'proceso' => 'actualizacion_masiva'
                        ],
                        $usuario,
                        $conn
                    );
                }

                $exito++;
            }

            // 🔒 Commit si todo va bien
            $conn->commit();

            echo json_encode([
                "error" => false,
                "message" => "$exito productos actualizados",
                "sin_cambios" => $sinCambios,
                "detalles_errores" => $errores
            ]);
        } catch (Exception $e) {
            // 🚨 Rollback en caso de error
            $conn->rollback();
            echo json_encode([
                "error" => true,
                "message" => "Error al guardar cambios: " . $e->getMessage()
            ]);
        }

        break;
}
