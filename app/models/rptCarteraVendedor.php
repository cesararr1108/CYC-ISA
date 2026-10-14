  <?php
    session_start();
    require_once('bd.php');
    require_once('funciones.php');
    $conn = conectar();
    switch ($_POST['op']) {
        case 'consultar':
            $tipo        = $_POST['tipo'];
            $anio        = $_POST["anio"];
            $id_vendedor = $_POST["id_vendedor"] ?? '';
            $id_zona     = $_POST["id_zona"] ?? '';

            // Consulta base
            $sql = "SELECT 
                        v.id,
                        v.sw,
                        v.tipo,
                        v.numero,
                        v.estado,
                        v.fecha,
                        v.vencimiento,
                        v.dias,
                        v.total_vencida,
                        v.total_sin_vencer,
                        v.razon_comercial,
                        v.nombres_tercero,
                        v.c_1_30,
                        v.c_31_60,
                        v.c_61_90,
                        v.c_91_120,
                        v.total_aplicado,
                        v.total,
                        v.id_vendedor,
                        IFNULL(v.nombres_vendedor,'SIN ASIGNAR') AS nombres_vendedor,
                        v.id_zona,
                        v.codigo_zona,
                        IFNULL(v.des_zona,'SIN ASIGNAR') AS des_zona
                    FROM v_cartera v
                    WHERE v.estado = '$tipo' 
                    AND YEAR(v.fecha) = '$anio'
                    AND v.sw IN(5,4,11,12)";

            // Filtra por vendedor solo si no está vacío y es diferente de 0
            if (!empty($id_vendedor) && $id_vendedor != '0') {
                $sql .= " AND v.id_vendedor = '$id_vendedor'";
            }

            // Filtra por zona solo si no está vacío y es diferente de 0
            if (!empty($id_zona) && $id_zona != '0') {
                $sql .= " AND v.id_zona = '$id_zona'";
            }

            // Ordenamiento
            $sql .= " ORDER BY v.fecha";

            $ventas = GenerarArray($sql);

            echo json_encode($ventas);
            break;
    }
