 <?php
    session_start();
    require_once('bd.php');
    require_once('funciones.php');
    $conn = conectar();

    switch ($_POST["op"]) {
        case "cargar-pedido":
            $sql = "SELECT
                        p.id,
                        td.tipo ,
                        p.numero ,
                        p.fecha_hora,
                        m.municipio AS ciudad,
                        IFNULL(s.estado,0) AS estado,
                        p.notas
                    FROM t_pedidos p
                    INNER JOIN t_tipos_documentos td ON td.id = p.id_tipo_documento 
                    INNER JOIN t_terceros_direcciones d 
                        ON d.id = p.id_tercero_dir
                    INNER JOIN t_municipios m 
                        ON m.codigo_dpto = d.codigo_dpto 
                        AND m.codigo = d.codigo_mpio
                    LEFT JOIN t_pedidos_separacion s
                        ON s.id_pedido = p.id
                    WHERE 
                        p.sw = 8
                        AND p.estado = 'P'
                        AND IFNULL(s.estado,0) = 0 and 
                        IFNULL(s.inicio,'') = ''
                    ORDER BY
                    p.fecha_hora ASC ";
            if ($_POST['list'] == 1) {
                $sql .= " LIMIT 1";
            }

            $pedidos = GenerarArray($sql);
            echo  json_encode($pedidos);
            break;
        case "cargar-sin-finalizar":
            $id_usuario = $_SESSION["Ses_id"];

            $sql = "SELECT
                        p.id,
                        td.tipo,
                        p.numero,
                        p.fecha_hora,
                        m.municipio AS ciudad,
                        IFNULL(s.estado,0) AS estado,
                        s.inicio,
                        p.notas
                    FROM t_pedidos p
                    INNER JOIN t_tipos_documentos td
                        ON td.id = p.id_tipo_documento
                    INNER JOIN t_terceros_direcciones d
                        ON d.id = p.id_tercero_dir
                    INNER JOIN t_municipios m
                        ON m.codigo_dpto = d.codigo_dpto
                        AND m.codigo = d.codigo_mpio
                    INNER JOIN t_pedidos_separacion s
                        ON s.id_pedido = p.id
                    WHERE
                        p.sw = 8
                        AND s.estado = 0
                        AND s.inicio IS NOT NULL
                        AND s.fin IS NULL
                        AND s.id_usuario = '$id_usuario'
                    ORDER BY s.inicio ASC";
            $pedidos = GenerarArray($sql);
            echo  json_encode($pedidos);
            break;
        case "iniciar-separacion":

            $id = intval($_POST['id']);
            $id_usuario = $_SESSION["Ses_id"];

            // Validar que exista el pedido
            $pedido = GenerarArray(" SELECT id, sw FROM t_pedidos WHERE id = $id LIMIT 1");

            if (count($pedido) == 0) {
                echo json_encode([
                    "ok" => false,
                    "mensaje" => "El pedido no existe."
                ]);
                exit;
            }

            // Validar separación
            $sep = GenerarArray("SELECT id, id_usuario, inicio, fin, estado
                                 FROM t_pedidos_separacion
                                 WHERE id_pedido = $id
                                 LIMIT 1");

            if (count($sep) > 0) {
                $sep = $sep[0];
                // Ya fue finalizado
                if ($sep["estado"] == 1 || !empty($sep["fin"])) {

                    echo json_encode([
                        "ok" => false,
                        "mensaje" => "Este pedido ya fue separado."
                    ]);
                    exit;
                }

                // Está siendo separado por otro usuario
                if ($sep["id_usuario"] != $id_usuario) {

                    echo json_encode([
                        "ok" => false,
                        "mensaje" => "Este pedido está siendo separado por otro usuario."
                    ]);
                    exit;
                }
            } else {

                // Crear registro de separación
                Query(" INSERT INTO t_pedidos_separacion(id_pedido,id_usuario,inicio,estado)
                        VALUES ( $id, $id_usuario, NOW(), 0 )");
            }

            // Consultar productos
            $sql = " SELECT
                        p.id AS id_pedido,
                        r.codigo,
                        r.descripcion,
                        UPPER(IFNULL(m.descripcion,'Sin asignar')) AS marca,
                        d.cantidad,
                        d.cantidad_desp,
                        (d.cantidad-d.cantidad_desp) AS pendiente,
                        p.notas
                    FROM t_pedidos p
                    INNER JOIN t_pedidos_detalle d
                        ON d.id_pedido=p.id
                        AND d.id_bodega=p.id_bodega
                    INNER JOIN t_referencias r
                        ON r.id=d.id_referencia
                    LEFT JOIN t_marcas m
                        ON m.id=r.id_marca
                    WHERE p.id=$id
                    ORDER BY r.descripcion";

            $productos = GenerarArray($sql);

            echo json_encode([
                "ok" => true,
                "productos" => $productos
            ]);

            break;
        case "finalizar-separacion":

            if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
                echo json_encode([
                    'ok' => false,
                    'mensaje' => 'Pedido inválido.'
                ]);
                exit;
            }

            $id = (int)$_POST['id'];
            $id_usuario = $_SESSION["Ses_id"];

            // Validar el proceso de separación
            $sep = GenerarArray(" SELECT id,  id_usuario, inicio, fin
                                  FROM t_pedidos_separacion
                                  WHERE id_pedido = $id LIMIT 1");
            $sep = reset($sep);

            if (!$sep) {
                echo json_encode([
                    'ok' => false,
                    'mensaje' => 'El pedido no tiene un proceso de separación iniciado.'
                ]);
                exit;
            }

            if ($sep['id_usuario'] != $id_usuario) {
                echo json_encode([
                    'ok' => false,
                    'mensaje' => 'Este pedido está siendo separado por otro usuario.'
                ]);
                exit;
            }

            if (!empty($sep['fin'])) {
                echo json_encode([
                    'ok' => false,
                    'mensaje' => 'El pedido ya fue finalizado.'
                ]);
                exit;
            }

            // Finalizar separación
            $sql = "  UPDATE t_pedidos_separacion SET
                      fin = NOW(),
                      estado = 1
                      WHERE id = {$sep['id']}";

            if (Query($sql)) {

                echo json_encode([
                    'ok' => true,
                    'mensaje' => 'Separación finalizada correctamente.'
                ]);
            } else {

                echo json_encode([
                    'ok' => false,
                    'mensaje' => 'No fue posible finalizar la separación.'
                ]);
            }

            break;
    }
