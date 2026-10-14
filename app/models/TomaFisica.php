<?php
session_start();
require_once('bd.php');
require_once('funciones.php');
$conn = conectar();

if (!sessionExists()) {
    echo json_encode(array(
        'error' => true,
        'message' => 'La sesion se ha cerrado, por favor ingrese nuevamente'
    ));
    return;
}

switch ($_POST["op"]) {
    case "busqueda-referencia":
        $filtro = trim($_POST["filtro"]);
        $id_bodega = $_POST["bodega"];
        $id_usuario = $_SESSION["Ses_id"];
        $conteo = $_POST["conteo"];
        switch ($conteo) {
            case "1":
                $condicion = ' toma_1>0 ';
                break;
            case "2":
                $condicion = ' toma_2>0 ';
                break;
            case "3":
                $condicion = ' toma_3>0 ';
                break;
        }

        $sql = " SELECT 
                r.codigo,
                r.id,
                r.descripcion,
                IFNULL(ur.ubicaciones, '') AS ubicaciones,
                IFNULL(m.descripcion,'NA') AS marca,
                IFNULL(c1.descripcion,'NA') AS categoria_1,
                IFNULL(c2.descripcion,'NA') AS categoria_2,
                IFNULL(c3.descripcion,'NA') AS categoria_3,
                IFNULL(c4.descripcion,'NA') AS categoria_4,
                CONCAT(r.und_1,'(',r.cant_1,')') AS unidad,
                ifnull((
                    select 
                     sum(case when $conteo = 1 then ifnull(toma_1,0) 
                          when $conteo = 2 then ifnull(toma_2,0)
                          when $conteo = 3 then ifnull(toma_3,0) 
                     end)  
                    from t_toma_fisica xx
                    where xx.id_referencia=r.id AND xx.id_bodega=$id_bodega AND xx.id_usuario=$id_usuario AND $condicion
                ),0) as conteo,
                ifnull((
                    select 
                        id
                    from t_toma_fisica 
                    where id_referencia=r.id AND id_bodega=$id_bodega 
                    LIMIT 1
                ),0) as marcado
            FROM t_referencias r
            LEFT JOIN t_marcas m ON m.id = r.id_marca 
            LEFT JOIN t_referencias_categoria1 c1 ON c1.id = r.id_categoria_1
            LEFT JOIN t_referencias_categoria2 c2 ON c2.id = r.id_categoria_2
            LEFT JOIN t_referencias_categoria3 c3 ON c3.id = r.id_categoria_3
            LEFT JOIN t_referencias_categoria4 c4 ON c4.id = r.id_categoria_4
            -- 🔹 Subconsulta para concatenar las ubicaciones sin duplicar
            LEFT JOIN (
                SELECT 
                    id_referencia, 
                    GROUP_CONCAT(DISTINCT tu.ubicacion  SEPARATOR ', ') AS ubicaciones
                FROM t_ubicaciones_referencias xx 
                INNER JOIN t_ubicaciones tu on tu.id=xx.id_ubicacion 
                GROUP BY id_referencia
            ) ur ON ur.id_referencia = r.id

            WHERE r.tipo = 'P'
            ";
        if (is_numeric($filtro)) {
            $id_ref = GenerarArray("select id from t_referencias_ean where ean ='$filtro' limit 1");
            if (count($id_ref) > 0) {
                $sql .= " and r.id=" . $id_ref[0]["id"];
            } else {
                $sql .= " and r.codigo='$filtro' ";
            }
            $sql .= " GROUP BY
                    r.codigo,
                    r.id,
                    r.descripcion,
                    m.descripcion ,
                    c1.descripcion ,
                    c2.descripcion ,
                    c3.descripcion ,
                    c4.descripcion ,
                    r.und_1,
                    r.cant_1
                    limit 100
            ";

            $referencias = GenerarArray($sql);
            echo json_encode($referencias);
            return;
        } else {
            //
            $sql .= " and " . filtrosBusquedaReferencias($filtro, 'r');
            $sql .= " 
                GROUP BY
                r.codigo,
                r.id,
                r.descripcion,
                m.descripcion ,
                c1.descripcion ,
                c2.descripcion ,
                c3.descripcion ,
                c4.descripcion 
                limit 100
            ";

            $referencias = GenerarArray($sql);
            echo json_encode($referencias);
            return;
        }
        break;
    case "validar-usuario":
        $id_usuario = $_SESSION["Ses_id"];
        $id_bodega = $_SESSION["Ses_id_bodega"];

        $sql = "
            select 
            *
            from t_usuarios_toma_fisica 
            where id_usuario =$id_usuario and id_bodega=$id_bodega
        ";

        $usuario = GenerarArray($sql);
        echo json_encode($usuario);
        break;
    case "cargar-lotes":
        $id_referencia = $_POST["id_referencia"];
        $sql = "
            select 
            * 
            from t_referencias_lotes 
            where id_referencia=$id_referencia
        ";
        $lotes = GenerarArray($sql);
        echo json_encode($lotes);
        break;
    case "marcar-ferencias":

        $id_bodega = $_SESSION["Ses_id_bodega"];
        $id_usuario = $_SESSION["Ses_id"];
        $tipo = $_POST["tipoMarca"];

        $categoria1 = isset($_POST["categoria1"])
            ? json_decode($_POST["categoria1"], true)
            : [];

        $categoria2 = isset($_POST["categoria2"])
            ? json_decode($_POST["categoria2"], true)
            : [];

        $categoria3 = isset($_POST["categoria3"])
            ? json_decode($_POST["categoria3"], true)
            : [];

        $categoria4 = isset($_POST["categoria4"])
            ? json_decode($_POST["categoria4"], true)
            : [];

        $marcas = isset($_POST["marcas"])
            ? json_decode($_POST["marcas"], true)
            : [];

        $ubicaciones = isset($_POST["ubicaciones"])
            ? json_decode($_POST["ubicaciones"], true)
            : [];

        if (count($categoria1) > 0) {
            $categoria1 = explode(',', $categoria1);
        }
        if (count($categoria2) > 0) {
            $categoria2 = explode(',', $categoria2);
        }

        if (count($categoria3) > 0) {
            $categoria3 = explode(',', $categoria3);
        }

        if (count($categoria4) > 0) {
            $categoria4 = explode(',', $categoria4);
        }

        $ids = [];

        switch ($tipo) {
            case "Ubicacion":
                $ids = GenerarArray("SELECT id_referencia as id FROM t_ubicaciones_referencias WHERE id_ubicacion IN(" . implode(",", $ubicaciones) . ")");
                break;
            case "Marca":
                $ids = GenerarArray("SELECT id FROM t_referencias WHERE id_marca IN(" . implode(",", $marcas) . ")");
                break;
            case "Categorias":
                $sql_ = '';

                if (count($categoria1) > 0) {
                    $sql_ .= "SELECT id FROM t_referencias WHERE id_categoria_1 in(" . implode($categoria1) . ") ";
                }
                if (count($categoria2) > 0) {
                    $sql_ .= "UNION SELECT id FROM t_referencias WHERE id_categoria_2 in(" . implode($categoria2) . ") ";
                }
                if (count($categoria3) > 0) {
                    $sql_ .= "UNION  SELECT id FROM t_referencias WHERE id_categoria_3 in(" . implode($categoria3) . ") ";
                }
                if (count($categoria4) > 0) {
                    $sql_ .= "UNION  SELECT id FROM t_referencias WHERE id_categoria_4 in(" . implode($categoria4) . ") ";
                }

                $ids = GenerarArray($sql_);
                break;
        }

        $id_array = array_column($ids, 'id');

        // Contar referencias que ya existen para evitar duplicados
        $referencias_existentes = 0;
        $referencias_insertadas = 0;

        if (count($id_array) > 0) {
            // Contar cuántas referencias ya existen
            $sql_count = "SELECT COUNT(*) as existe FROM t_toma_fisica 
                         WHERE id_bodega = $id_bodega 
                         AND id_referencia IN(" . implode(",", $id_array) . ")";
            $result_count = GenerarArray($sql_count);
            $referencias_existentes = $result_count[0]['existe'];
        }

        // Ejecutar DELETE primero
        $sql_delete = "DELETE FROM t_toma_fisica WHERE id_bodega = $id_bodega";
        $delete = $conn->query($sql_delete);

        // Construir y ejecutar INSERT
        $sql = "  INSERT INTO t_toma_fisica(
                        id_referencia,
                        id_bodega,	
                        id_lote	,
                        id_usuario,
                        conteo
                    )
                    SELECT 
                        r.id,
                        $id_bodega,
                        s.id_lote,
                        $id_usuario ,
                        1
                        FROM t_referencias r
                        left join v_referencias_stock_lotes s on s.id_referencia = r.id and s.id_bodega = $id_bodega
                        left join t_marcas m on m.id = r.id_marca 
                        left join t_referencias_categoria1  c1 on c1.id=r.id_categoria_1
                        left join t_referencias_categoria2  c2 on c2.id=r.id_categoria_2
                        left join t_referencias_categoria3  c3 on c3.id=r.id_categoria_3
                        left join t_referencias_categoria4  c4 on c4.id=r.id_categoria_4
                        left join t_ubicaciones_referencias ur on ur.id_referencia = r.id
                        left join t_ubicaciones             u  on u.id=ur.id_ubicacion 
                        left join t_toma_fisica             tf on tf.id_referencia = r.id and tf.id_bodega =$id_bodega 
                        and tf.id_usuario=$id_usuario
                        left join t_usuarios_toma_fisica     ut on ut.id_usuario =  $id_usuario and ut.id_bodega=$id_bodega
                        WHERE tipo='P'
                        AND tf.id IS NULL
        ";
        if (count($id_array) > 0) {
            $sql .= " and r.id in(" . implode(",", $id_array) . ") ";
        }
        
        $insert = $conn->query($sql);

        if (!$insert) {
            echo json_encode([
                'ok' => false,
                'message' => 'Error al insertar referencias',
                'referencias_insertadas' => 0,
                'referencias_existentes' => $referencias_existentes,
                'referencias_totales' => count($id_array)
            ]);
        } else {
            // Obtener el número de filas afectadas
            $referencias_insertadas = $conn->affected_rows;

            echo json_encode([
                'ok' => true,
                'message' => 'Referencias marcadas correctamente',
                'referencias_insertadas' => $referencias_insertadas,
                'referencias_existentes' => $referencias_existentes,
                'referencias_totales' => count($id_array)
            ]);
        }

        // $referencias = GenerarArray($sql);
        //echo  json_encode($referencias);
        break;

    case "desmarcar-referencias":
        $id_bodega = $_POST["bodega"];
        $sql_delete = "DELETE FROM t_toma_fisica WHERE id_bodega = $id_bodega";
        $delete = $conn->query($sql_delete);
        if ($delete) {
            echo json_encode([
                'ok' => true,
                'message' => 'Referencias desmarcadas correctamente'
            ]);
        } else {
            echo json_encode([
                'ok' => false,
                'message' => 'Error al desmarcar referencias'
            ]);
        }
        break;
    case "validar-conteo-proceso":
        $id_bodega = $_SESSION["Ses_id_bodega"];

        // Validar si hay toma_1, toma_2 o toma_3 con datos en esa bodega
        $sql = "SELECT COUNT(*) as hay_conteo FROM t_toma_fisica 
                WHERE id_bodega = $id_bodega 
                AND ((toma_1 > 0) OR(toma_2 > 0) OR (toma_3 > 0))
                LIMIT 1";

        $resultado = GenerarArray($sql);

        if ($resultado[0]['hay_conteo'] > 0) {
            echo json_encode([
                'ok' => false,
                'en_proceso' => true,
                'message' => 'Ya existe un conteo en proceso en esta bodega. Por favor, verifique antes de iniciar un nuevo marcado.'
            ]);
        } else {
            echo json_encode([
                'ok' => true,
                'en_proceso' => false,
                'message' => 'No hay conteos en proceso'
            ]);
        }
        break;
    case "guardar-linea":
        // $item = $_POST["item"]; 

        $item = json_decode($_POST["item"], true); // true = array asociativo
        $conteo = $item["conteo"];
        $cantidad = $item["cantidad"];
        $item["id_usuario"] = $_SESSION["Ses_id"];

        switch ($conteo) {
            case 1:
                $campo = "toma_1";
                break;
            case 2:
                $campo = "toma_2";
                break;
            case 3:
                $campo = "toma_3";
                break;
        }

        if (!isset($item) || !is_array($item)) {
            throw new Exception("Item no válido");
        }
        //valido si existe el lote 
        $lotes = GenerarArray("SELECT * FROM t_toma_fisica 
        WHERE id_referencia = " . $item["id_referencia"] . " 
        AND id_bodega=" . $item["id_bodega"] . " ", '');

        if (count($lotes) > 0) {

            // valido si existe ese id de lote 
            $IdLote = GenerarArray("SELECT 
                                      * 
                                    FROM t_toma_fisica 
                                    WHERE id_referencia = " . $item["id_referencia"] . " 
                                    AND id_bodega=" . $item["id_bodega"] . " 
                                    AND id_lote=" . $item["id_lote"] . " ", '');
            if (count($IdLote) > 0) {
                $update = Query("UPDATE t_toma_fisica 
                SET $campo=ifnull($campo,0)+" . $item["cantidad"] . "

                 WHERE id_referencia = " . $item["id_referencia"] . " 
                 AND id_bodega=" . $item["id_bodega"] . " 
                 AND id_lote=" . $item["id_lote"] . " ");
            } else {
                //valido si existe con lote null, actualizo , sino inserto 
                $lotenull = GenerarArray("SELECT 
                                           *
                                         FROM t_toma_fisica 
                                         WHERE id_referencia = " . $item["id_referencia"] . " 
                                         AND id_bodega=" . $item["id_bodega"] . " 
                                         AND id_lote is null");

                if (count($lotenull) > 0) {
                    $update = Query("UPDATE t_toma_fisica 
                    SET $campo=ifnull($campo,0)+" . $item["cantidad"] . ",
                        id_lote = " . $item["id_lote"] . " 
                     WHERE id_referencia = " . $item["id_referencia"] . " 
                     AND id_bodega=" . $item["id_bodega"] . " AND id_lote is null");
                } else {
                    unset($item["cantidad"]);
                    $item[$campo] = $cantidad;
                    $insert = insertReturnId('t_toma_fisica', $item, false);

                    if ($insert) {
                        echo json_encode([
                            'ok' => true,
                            'message' => 'Se inserto el registro',
                        ]);
                    } else {
                        echo json_encode([
                            'ok' => false,
                            'message' => 'Error al insertar',
                        ]);
                    }
                    return;
                }
            }


            if ($update) {
                echo json_encode([
                    'ok' => true,
                    'message' => 'Se actualizo el registro',
                ]);
            } else {
                echo json_encode([
                    'ok' => false,
                    'message' => 'Error al actualizar',
                ]);
            }
        } else {
            unset($item["cantidad"]);
            $item[$campo] = $cantidad;
            //print_r($_);
            //validido si existe 
            $id = insertReturnId('t_toma_fisica', $item, false);
        }
        /*
                $update = Query("UPDATE t_toma_fisica SET $campo=" . $item["cantidad"] . " 
                         WHERE id_referencia = " . $item["id_referencia"] . " 
                         AND id_bodega=" . $item["id_bodega"]);
                if ($update) {
                    echo json_encode(array(
                        'ok' => true,
                        'message' => 'Se actualizo el registro',
                    ));
                } else {
                    echo json_encode(array(
                        'ok' => false,
                        'message' => 'Error al actualizar',
                    ));
                }*/
        break;
    case "crear-lote":
        $id_referencia = $_POST["id_referencia"];
        $lote = $_POST["lote"];
        $vencimiento = $_POST["vencimiento"];

        $data = array(
            'id_referencia' => $id_referencia,
            'lote' => $lote,
            'vencimiento' => $vencimiento
        );
        $id = insertReturnId('t_referencias_lotes', $data, false);
        echo json_encode(array(
            'ok' => $id > 0 ? true : false,
            'id' => $id
        ));
        break;
    case "validar-lote":
        $id_referencia = $_POST["id_referencia"];
        $lote = $_POST["lote"];
        $sql = "
         select 
            *
         from t_referencias_lotes l 
         where l.lote='$lote' and l.id_referencia=$id_referencia";
        $lotes = GenerarArray($sql);
        echo json_encode($lotes);
        break;
    case "informe-seguimiento":
        $id_bodega = $_POST["id_bodega"];
        $id_usuario = $_POST["id_usuario"];


        if ($id_usuario != '') {
            $condicion_usuario = " AND f.id_usuario=$id_usuario";
        } else {
            $condicion_usuario = "";
        }

        $sql1 = " CREATE TEMPORARY TABLE tmp_tomas AS
                    SELECT 
                        r.codigo,
                        r.descripcion,
                        m.descripcion as marca,
                        sum(f.toma_1) as toma_1,
                        sum(f.toma_2) as toma_2,
                        sum(f.toma_3) as toma_3,
                        sum(IFNULL(s.stock,0)) AS stock                    
                    FROM t_toma_fisica f 
                    INNER JOIN t_referencias r ON r.id=f.id_referencia
                    LEFT JOIN t_marcas m ON m.id=r.id_marca
                    LEFT JOIN v_referencias_stock_lotes s ON s.id_referencia=f.id_referencia 
                    AND s.id_bodega=f.id_bodega
                    AND  s.id_lote = f.id_lote
                    WHERE f.id_bodega =$id_bodega $condicion_usuario    
                    GROUP BY
                        r.codigo,
                        r.descripcion,
                        m.descripcion;

        ";

        $sql2 = " SELECT
                        *,
                        CASE
                            WHEN IFNULL(toma_1, 0) <> 0
                            OR IFNULL(toma_2, 0) <> 0
                            OR IFNULL(toma_3, 0) <> 0
                            THEN 1
                            ELSE 0
                        END AS contado,
                        CASE
                            -- Si existe toma_3 (>0) pero no coincide → Auditoría
                            WHEN IFNULL(toma_3, 0) > 0
                                AND IFNULL(toma_3, 0) <> stock
                            THEN 'auditoria'
                            -- Si existe toma_2 (>0) pero no coincide y toma_3 aún no existe → Pedir toma 3
                            WHEN IFNULL(toma_2, 0) > 0
                                AND IFNULL(toma_2, 0) <> stock
                                AND IFNULL(toma_3, 0) = 0
                            THEN 'pasar_a_toma_3'
                            -- Si solo existe toma_1 (>0) pero no coincide y toma_2 aún no existe
                            WHEN IFNULL(toma_1, 0) > 0
                                AND IFNULL(toma_1, 0) <> stock
                                AND IFNULL(toma_2, 0) = 0
                            THEN 'Auditoria'
                            -- Si todas las tomas existentes coinciden → OK
                            WHEN IFNULL(toma_3, 0) > 0
                                AND IFNULL(toma_3, 0) = stock
                            THEN 'ok'
                            WHEN IFNULL(toma_2, 0) > 0
                                AND IFNULL(toma_2, 0) = stock
                            THEN 'ok'
                            WHEN IFNULL(toma_1, 0) > 0
                                AND IFNULL(toma_1, 0) = stock
                            THEN 'ok'
                            ELSE 'sin_datos'
                        END AS observacion
                    FROM tmp_tomas;";
        $preinforme = $conn->query($sql1);

        $informe = $conn->query($sql2);

        // Crear un array para almacenar los resultados
        $matriz = array();

        // Obtener los resultados de la consulta
        while ($row = $informe->fetch_assoc()) {
            // Agregar el resultado al array, aplicando la función 'esFecha' si es necesario
            $matriz[] = $row; //array_map('esFecha', $row);
        }

        //$
        $mov = GenerarArray("SELECT 
        count(*) as n
        FROM  
        t_referencias_mov v 
        WHERE v.mes=MONTH(CURRENT_DATE) AND v.ano=YEAR(CURRENT_DATE) 
        AND id_bodega=$id_bodega ");

        echo json_encode(array(
            'data' => $matriz,
            'n_ref_mov' => $mov[0]["n"]
        ));
        break;
    case "tercero":
        $sql = "select 
         t.id,
         t.razon_comercial,
         (
            select 
              id
            from t_terceros_direcciones 
            where id_tercero =t.id 
            limit 1
         ) as id_direccion ,

         (
            select 
              direccion
            from t_terceros_direcciones 
            where id_tercero =t.id 
            limit 1
         ) as direccion
         from t_terceros t where nit = (select nit from t_empresa)";
        echo json_encode(GenerarArray($sql));
        break;
    case "eliminar-conteos":
        $bodega = $_POST["bodega"];
        $conteo = $_POST["conteo"];
        //valido si existen conteo 
        $conteos_ = GenerarArray("SELECT * FROM t_toma_fisica WHERE id_bodega=$bodega AND (toma_1>0 OR toma_2>0 OR toma_3>0)");

        if (count($conteos_) == 0) {
            echo json_encode(array(
                'ok' => false,
                'message' => 'No existen conteos para eliminar'
            ));
            return;
        }

        if ($conteo == '0') { //elimina todos los conteos de la bodega
            $delete = $conn->query("UPDATE t_toma_fisica SET toma_1=0,toma_2=0,toma_3=0
             WHERE id_bodega=$bodega");
        } else {
            switch ($conteo) {
                case "1":
                    $campo = "toma_1";
                    break;
                case "2":
                    $campo = "toma_2";
                    break;
                case "3":
                    $campo = "toma_3";
                    break;
            }
            $delete = $conn->query("UPDATE t_toma_fisica SET $campo=0 WHERE id_bodega=$bodega ");
        }

        if ($delete) {
            echo json_encode(array(
                'ok' => true,
                'message' => 'se elimno correctamente los registros'
            ));
        } else {
            echo json_encode(array(
                'ok' => false,
                'message' => 'Error al eliminar los registros'
            ));
        }
        break;
    case "pre-ajuste":
        $id_bodega = $_POST["id_bodega"];

        $sql1 = " CREATE TEMPORARY TABLE tmp_tomas AS
                    select 
                    r.id as id_referencia,
                    r.codigo,
                    r.descripcion,
                    m.descripcion as marca,
                    sum(f.toma_1) as toma_1,
                    sum(f.toma_2) as toma_2,
                    sum(f.toma_3) as toma_3,
                    IFNULL(s.stock,0) AS stock,
                    IFNULL(
                      CASE  WHEN sum(f.toma_3)>0 then sum(f.toma_3)  
                            WHEN sum(f.toma_2)>0 then sum(f.toma_2)  
                            WHEN sum(f.toma_1)>0 then sum(f.toma_1)  
                            ELSE 0
                      END, 0
                    ) AS cantidad_final,
                    IFNULL(s.lote, ll.lote) as lote,
                    IFNULL(s.vencimiento, ll.vencimiento) as vencimiento,
                    ll.lote as lote_toma,
                    ll.vencimiento as vmcto_toma,
                    f.id_lote,
                    i.porcentaje_iva as pcj_iva
                    from t_toma_fisica f 
                    inner join t_referencias        r  on r.id =f.id_referencia
                    inner join t_iva                i  on i.id =r.id_iva
                    left join t_referencias_lotes   ll on ll.id=f.id_lote
                    left join t_marcas              m  on m.id =r.id_marca
                    left join v_referencias_stock_lotes s on s.id_referencia=f.id_referencia and s.id_bodega=f.id_bodega and s.id=f.id_lote
                    WHERE f.id_bodega = $id_bodega and
                    (ifnull(toma_1,0)>0 or ifnull(toma_2,0)>0 or ifnull(toma_3,0)>0) 
                    or (s.stock>0)
                    group BY
                    ll.lote,
                    r.id ,
                    r.codigo,
                    r.descripcion,
                    m.descripcion ,
                    s.stock,
                    s.lote,
                    s.vencimiento,
                    f.id_lote,
                    ll.vencimiento; ";
        $preinforme = $conn->query($sql1);
        $sql2 = "SELECT DISTINCT
            t.*,
           CASE WHEN t.stock>0  THEN
                   CASE WHEN  t.cantidad_final- t.stock > 0  THEN 'Entrada' ELSE 'Salida' 
            END ELSE
                CASE WHEN t.cantidad_final>0 THEN 'Entrada' ELSE 'oK' END 
            END 
            as proceso,
            t.cantidad_final-t.stock as cantidad_ajustar ,
            ROUND(CASE WHEN cp.promedio IS NULL OR cp.promedio = 0 
                THEN ifnull(td.ultimo_costo,r.costo_unitario)
            ELSE cp.promedio END,2) as costo,
            IFNULL(ROUND((t.cantidad_final-t.stock) *(
                CASE WHEN cp.promedio IS NULL OR cp.promedio = 0 
                    THEN ifnull(td.ultimo_costo,r.costo_unitario)
                ELSE cp.promedio END
             ),0),0) AS total_costo
            FROM tmp_tomas t
          INNER JOIN t_referencias r on r.id= t.id_referencia
          LEFT JOIN v_costo_promedio cp 
            ON cp.id_referencia = t.id_referencia
            AND cp.mes=MONTH(CURRENT_DATE) AND cp.ano=YEAR(CURRENT_DATE)
        -- unir con último costo de transacción
            LEFT JOIN (
                SELECT 
                    tdi.id_referencia,
                    tdi.costo_unitario AS ultimo_costo
                FROM t_transacciones_detalle tdi
                WHERE tdi.sw IN (1, 6, 4, 7)
                ORDER BY tdi.fec DESC, id DESC
            ) td ON td.id_referencia = t.id_referencia ";

        $informe = $conn->query($sql2);
        $matriz = array();

        // Obtener los resultados de la consulta
        while ($row = $informe->fetch_assoc()) {
            // Agregar el resultado al array, aplicando la función 'esFecha' si es necesario
            $matriz[] = $row; //array_map('esFecha', $row);
        }
        echo json_encode($matriz);

        break;
    case "reiniciar-toma":
        $toma = $_POST["toma"];
        $bodega = $_POST["bodega"];

        switch ($toma) {
            case '1':
                $campo = 'toma_1';
                break;
            case '2':
                $campo = 'toma_2';
                break;
            case '3':
                $campo = 'toma_3';
                break;
        }

        $update = $conn->query("UPDATE t_toma_fisica SET $campo=0  WHERE id_bodega=$bodega");
        if ($update) {
            echo json_encode(array(
                'ok' => true,
                'message' => 'se elimno correctamente el conteo'
            ));
        } else {
            echo json_encode(array(
                'ok' => false,
                'message' => 'error al eliminar el registro'
            ));
        }
        break;
    case "realizar-ajuste":

        try {

            $conn->begin_transaction();

            $id_bodega = $_POST["id_bodega"];
            $id_tercero = $_POST["id_tercero"];
            $id_tercero_dir = $_POST["id_tercero_dir"];
            $id_tipo_documento_salida = $_POST["id_tipo_documento_salida"];
            $id_tipo_documento_entrada = $_POST["id_tipo_documento_entrada"];
            $id_condicion = $_POST["id_condicion"];
            $notas = $_POST["notas"];
            $id_usuario = $_SESSION["Ses_id"];

            $valor_total = 0;
            $lista = 0;
            $sw_salida = 6;
            $sw_entrada = 2;
            $iva = 0;
            $descuento = 0;

            $salidas = isset($_POST["salidas"])
                ? json_decode($_POST["salidas"], true)
                : [];

            $entradas = isset($_POST["entradas"])
                ? json_decode($_POST["entradas"], true)
                : [];
            //validar que existan salidas o entradas
            if (count($salidas) == 0 && count($entradas) == 0) {
                throw new Exception("No hay salidas ni entradas para procesar");
            }

            // ===============================
            // VALIDAR STOCK
            // ===============================
            if (is_array($salidas) && count($salidas) > 0) {

                foreach ($salidas as $s) {

                    if ($_SESSION["Ses_confSys"]['confGeneral'][0]['ManejaFechaLote'] == 'S') {

                        $stock = GenerarArray(" SELECT stock
                                                FROM v_referencias_stock_lotes
                                                WHERE id_referencia = {$s["id_referencia"]}
                                                AND id_lote = {$s["id_lote"]}
                                                AND id_bodega = $id_bodega
                ");
                    } else {

                        $stock = GenerarArray(" SELECT stock
                                                FROM v_stock_total
                                                WHERE id_referencia = {$s["id_referencia"]}
                                                AND id_bodega = $id_bodega
                                                AND ano = YEAR(CURDATE())
                                                AND mes = MONTH(CURDATE())
                ");
                    }

                    if (!isset($stock[0]["stock"]) || $stock[0]["stock"] < $s["cantidad"]) {
                        throw new Exception("El código {$s["codigo"]} no tiene stock suficiente!");
                    }
                }
            }

            $numero_entrada = numeroSiguiente($id_tipo_documento_entrada);
            $numero_salida = numeroSiguiente($id_tipo_documento_salida);

            // ===============================
            // INSERTAR ENCABEZADO TMP
            // ===============================
            $enc_entrada = [
                'numero' => $numero_entrada,
                'id_bodega' => $id_bodega,
                'id_tercero' => $id_tercero,
                'id_tipo_documento' => $id_tipo_documento_entrada,
                'id_condicion' => $id_condicion,
                'notas' => $notas,
                'id_usuario' => $id_usuario,
                'valor_total' => $valor_total,
                'lista' => $lista,
                'sw' => $sw_entrada,
                'iva' => $iva,
                'descuento' => $descuento,
                'id_tercero_dir' => $id_tercero_dir,
                'es_credito' => 0
            ];

            $id_enc_entrada = insertReturnId('t_transacciones_tmp', $enc_entrada, false, $conn);

            if (!is_numeric($id_enc_entrada) || $id_enc_entrada == 0) {
                throw new Exception("No se pudo insertar el encabezado");
            }

            // ===============================
            // INSERTAR DETALLES ENTRADAS
            // ===============================
            foreach ($entradas as $e) {

                $data_ent = [
                    'id_transaccion' => $id_enc_entrada,
                    'id_referencia' => $e["id_referencia"],
                    'cantidad' => $e["cantidad"],
                    'valor_unitario' => $e["costo"],
                    'costo_unitario' => $e["costo"],
                    'id_lote' => $e["id_lote"],
                    'pcj_iva' => $e["pcj_iva"],
                    'pcj_descuento' => 0,
                    'und' => 'UND',
                    'can_und' => 1,
                    'vmcto' => $e["vmcto"],
                    'lote' => $e["lote"],
                    'sw' => 2,
                    'id_bodega' => $id_bodega,
                    'id_pedido' => 0,
                    'impoconsumo' => 0,
                    'ibua' => 0,
                    'icui' => 0,
                    'id_transaccion_ref' => 0,
                    'fec' => date("Y-m-d")
                ];

                if (!is_numeric(insertReturnId('t_transacciones_detalle_tmp', $data_ent, false, $conn))) {
                    throw new Exception("Error al insertar detalle de ENTRADA");
                }
            }


            // ===============================
            // LLAMAR PROCEDIMIENTO
            // ===============================
            $stmt_entrada = $conn->prepare("CALL p_volcar_documento(?, @nuevoid)");

            if (!$stmt_entrada) {
                throw new Exception($conn->error);
            }

            $stmt_entrada->bind_param("i", $id_enc_entrada);

            if (!$stmt_entrada->execute()) {
                throw new Exception($stmt_entrada->error);
            }

            $stmt_entrada->close();

            // Limpiar result sets
            while ($conn->more_results()) {
                $conn->next_result();
            }

            $result_entrada = $conn->query("SELECT @nuevoid AS nuevoid");

            if (!$result_entrada) {
                throw new Exception("No se pudo obtener el id generado");
            }

            $row_entrada = $result_entrada->fetch_assoc();
            $nuevoid_entrada = $row_entrada["nuevoid"];



            $enc_salida = [
                'numero' => $numero_salida,
                'id_bodega' => $id_bodega,
                'id_tercero' => $id_tercero,
                'id_tipo_documento' => $id_tipo_documento_salida,
                'id_condicion' => $id_condicion,
                'notas' => $notas,
                'id_usuario' => $id_usuario,
                'valor_total' => $valor_total,
                'lista' => $lista,
                'sw' => $sw_salida,
                'iva' => $iva,
                'descuento' => $descuento,
                'id_tercero_dir' => $id_tercero_dir,
                'es_credito' => 0
            ];

            $id_enc_salida = insertReturnId('t_transacciones_tmp', $enc_salida, false, $conn);

            if (!is_numeric($id_enc_salida) || $id_enc_salida == 0) {
                throw new Exception("No se pudo insertar el encabezado");
            }
            // ===============================
            // INSERTAR DETALLES SALIDAS
            // ===============================
            foreach ($salidas as $s) {

                $data_sal = [
                    'id_transaccion' => $id_enc_salida,
                    'id_referencia' => $s["id_referencia"],
                    'cantidad' => $s["cantidad"],
                    'valor_unitario' => $s["costo"],
                    'costo_unitario' => $s["costo"],
                    'id_lote' => $s["id_lote"],
                    'pcj_iva' => $s["pcj_iva"],
                    'pcj_descuento' => 0,
                    'und' => 'UND',
                    'can_und' => 1,
                    'vmcto' => $s["vmcto"],
                    'lote' => $s["lote"],
                    'sw' => 6,
                    'id_bodega' => $id_bodega,
                    'id_pedido' => 0,
                    'impoconsumo' => 0,
                    'ibua' => 0,
                    'icui' => 0,
                    'id_transaccion_ref' => 0,
                    'fec' => date("Y-m-d")
                ];

                if (!is_numeric(insertReturnId('t_transacciones_detalle_tmp', $data_sal, false, $conn))) {
                    throw new Exception("Error al insertar detalle de SALIDA");
                }
            }


            // ===============================
            // LLAMAR PROCEDIMIENTO
            // ===============================
            $stmt_salida = $conn->prepare("CALL p_volcar_documento(?, @nuevoid)");

            if (!$stmt_salida) {
                throw new Exception($conn->error);
            }

            $stmt_salida->bind_param("i", $id_enc_salida);

            if (!$stmt_salida->execute()) {
                throw new Exception($stmt_salida->error);
            }

            $stmt_salida->close();

            // Limpiar result sets
            while ($conn->more_results()) {
                $conn->next_result();
            }

            $result_salida = $conn->query("SELECT @nuevoid AS nuevoid");

            if (!$result_salida) {
                throw new Exception("No se pudo obtener el id generado");
            }

            $row_salida = $result_salida->fetch_assoc();
            $nuevoid_salida = $row_salida["nuevoid"];

            $conn->commit();
            // ⚠ NO HACEMOS COMMIT AQUÍ

            if ($nuevoid_entrada > 0 && $nuevoid_salida > 0) {
                // elimino la toma fisica 
                $del_toma = $conn->query("DELETE FROM t_toma_fisica where id_bodega =$id_bodega  ");

                echo json_encode([
                    'ok' => true,
                    'message' => 'Se insertó correctamente el registro',
                    'id_entrada' => $nuevoid_entrada,
                    'id_salida' => $nuevoid_salida
                ]);
            } else {
                throw new Exception("El procedimiento no generó id válido");
            }
        } catch (Exception $e) {

            $conn->rollback();

            echo json_encode([
                'ok' => false,
                'message' => $e->getMessage()
            ]);
        }

        break;
}
