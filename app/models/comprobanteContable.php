<?php
session_start();
require_once('bd.php');
require_once('funciones.php');
$conn = conectar();

switch ($_POST["op"]) {
    case "guardar_comprobante":

        $conn->begin_transaction();

        try {

            $tipo_comprobante = isset($_POST["id_tipo_documento"]) ? intval($_POST["id_tipo_documento"]) : 0;
            $fecha = $_POST["fecha"] ?? '';
            $observacion = $_POST["observacion"] ?? '';


            if ($tipo_comprobante <= 0) {
                throw new Exception('Tipo de comprobante inválido');
            }

            if (!isset($_SESSION["Ses_id"])) {
                throw new Exception('Sesión no válida');
            }

            if (!isset($_POST["movimientos"])) {
                throw new Exception('No se envió el detalle de movimientos');
            }

            $movimientos = json_decode($_POST["movimientos"], true);

            if (!is_array($movimientos) || count($movimientos) == 0) {
                throw new Exception('No existen movimientos para guardar');
            }
            if ($_POST["id_transaccion"] > 0) {
                //editar
                $datosTra = generarArray("SELECT 
                    t.numero ,
                    td.tipo 
                FROM t_transacciones t 
                inner join t_tipos_documentos td on td.id = t.id_tipo_documento  
                WHERE t.id = " . $_POST["id_transaccion"]);

                $numero = $datosTra[0]["numero"];
                $numero_doc = $numero;
                $tipo_doc = $datosTra[0]["tipo"];

            } else {
                $numero = numeroSiguiente($tipo_comprobante);
            }
            

            if ($numero <= 0) {
                throw new Exception('El consecutivo no se pudo generar');
            }

            $datosEmpresa = generarArray("
            SELECT
                t.id,
                t.nit,
                t.razon_comercial,
                td.id_direccion,
                t.id_condicion,
                t.id_zona_ventas,
                z.codigo_zona,
                z.descripcion AS desc_zona
            FROM t_terceros t
            INNER JOIN t_empresa e
                ON e.nit = t.nit
            LEFT JOIN t_zonas_ventas z
                ON z.id = t.id_zona_ventas
            LEFT JOIN (
                SELECT
                    id_tercero,
                    MIN(id) AS id_direccion
                FROM t_terceros_direcciones
                GROUP BY id_tercero
            ) td
                ON td.id_tercero = t.id
        ");

            if (count($datosEmpresa) == 0) {
                throw new Exception(
                    'No se encontraron los datos de la empresa. Debe configurar la empresa para generar el comprobante'
                );
            }
        

            if($_POST["id_transaccion"]>0){
                  $dataEncabezado = [
                     'notas'             => $observacion,
                     'vencimiento'       => $fecha,
                     'fecha'             => $fecha

                  ];
                $idEnc = $_POST["id_transaccion"];
                $update =update( 't_transacciones', $dataEncabezado, $idEnc ) ;
                $id_transaccion = $idEnc;

            }else{
                $dataEncabezado = [
                                    'numero'            => $numero,
                                    'id_tipo_documento' => $tipo_comprobante,
                                    'id_tercero'        => $datosEmpresa[0]['id'],
                                    'id_bodega'         => $_SESSION["Ses_id_bodega"],
                                    'notas'             => $observacion,
                                    'valor_total'       => 0,
                                    'iva'               => 0,
                                    'descuento'         => 0,
                                    'sw'                => 0,
                                    'id_tercero_dir'    => $datosEmpresa[0]['id_direccion'],
                                    'id_condicion'      => $datosEmpresa[0]['id_condicion'],
                                    'zona_ventas'       => $datosEmpresa[0]['codigo_zona'],
                                    'zona_descripcion'  => $datosEmpresa[0]['desc_zona'],
                                    'id_usuario'        => $_SESSION["Ses_id"],
                                    'fecha'             => $fecha,
                                    'vendedor'          => 0,
                                    'documento'         => '',
                                    'vencimiento'       => $fecha,
                                    'valor_aplicado'    => 0,
                                    'es_credito'        => 0
                                ];

                    $idEnc = insertReturnId(
                        't_transacciones_tmp',
                        $dataEncabezado,
                        true
                    );

                    if (!$idEnc) {
                        throw new Exception('Error al insertar encabezado temporal');
                    }

                    $stmt = $conn->prepare(
                        "CALL p_volcar_comprobante(?, @id, @tipo, @numero)"
                    );

                    if (!$stmt) {
                        throw new Exception($conn->error);
                    }

                    $stmt->bind_param("i", $idEnc);

                    if (!$stmt->execute()) {
                        throw new Exception($stmt->error);
                    }

                    $stmt->close();

                    while ($conn->more_results()) {
                        $conn->next_result();
                    }

                    $result = $conn->query(
                        "SELECT @id AS id, @tipo AS tipo, @numero AS numero"
                    );

                    if (!$result) {
                        throw new Exception($conn->error);
                    }

                    $nuevoid = $result->fetch_assoc();

                    $id_transaccion = intval(
                        $nuevoid['id'] ?? 0
                    );

                    if ($id_transaccion <= 0) {
                        throw new Exception(
                            'Error al generar el documento definitivo'
                        );
                    }
                     $tipo_doc   = $nuevoid['tipo'];
                     $numero_doc = $nuevoid['numero'];

            }


            

            foreach ($movimientos as $mov) {

                if($mov["eliminar"]== false &&  $mov["id"]==0){
                    $dataDetalle = [
                        'id_transaccion' => $id_transaccion,
                        'tipo'           => $tipo_doc,
                        'numero'         => $numero_doc,
                        'cuenta'         => $mov['cuenta'],
                        'nit'            => $mov['nit'],
                        'debito'         => $mov['debito'],
                        'credito'        => $mov['credito'],
                        'ceco'           => $mov['centroCosto'] == '' ? 0 : $mov['centroCosto'],
                        'detalle'        => $mov['detalle']
                    ];

                    $idMov = insertReturnId(
                        't_movimiento_contable',
                        $dataDetalle,
                        true
                    );

                    if (!$idMov) {
                        throw new Exception(
                            "Error al insertar movimiento contable de la cuenta {$mov['cuenta']}"
                        );
                    }
                }elseif($mov["eliminar"]== true &&  $mov["id"]>0    ){
                    $delete  =  Query("DELETE FROM t_movimiento_contable WHERE id = " . $mov["id"]);
                }

            }

            $conn->commit();

            echo json_encode([
                'ok' => true,
                'message' => 'Se insertó correctamente el registro',
                'id_transaccion' => $id_transaccion
            ]);
        } catch (Throwable $th) {

            $conn->rollback();

            echo json_encode([
                'ok' => false,
                'message' => $th->getMessage(),
                'id_transaccion' => 0
            ]);
        }
        break;
        case "listar-movimientos":

            $fecha_incial = $_POST["fecha_inicial"];
            $fecha_final = $_POST["fecha_final"];
        

            $sql = "SELECT 
                    t.id,
                    t.numero,
                    tp.tipo,
                    t.id_tipo_documento,
                    ifnull( (
                    select sum(c.debito)  FROM t_movimiento_contable c 
                        WHERE c.id_transaccion = t.id
                    ) ,0) as debito,
                    ifnull( (
                    select sum(c.credito)  FROM t_movimiento_contable c 
                        WHERE c.id_transaccion = t.id
                    ) ,0) as credito,
                    cast(t.fecha_hora as date) as fecha,
                    t.usuario,
                    t.valor_total,
                    t.iva,
                    t.notas as observacion,
                    ter.nit,
                    ter.razon_comercial,
                    CONCAT( ter.primer_apellido,' ',ter.segundo_apellido,' ',ter.primer_nombre,' ',ter.segundo_apellido) as tratamiento

                    FROM t_transacciones t 
                    INNER JOIN t_tipos_documentos tp on tp.id = t.id_tipo_documento
                    INNER JOIN t_terceros ter on ter.id = t.id_tercero
                    where t.sw=0
                ";
            if($fecha_incial && $fecha_final){
                $sql .= " AND cast(t.fecha_hora as date) BETWEEN '$fecha_incial' AND '$fecha_final'";
            }
            $comprobantes = generarArray($sql);
            $detalleComprobante = [];
            $data = [];

            foreach($comprobantes as $c){

            $sqlMovimientos = "SELECT  
                                m.id,
                                m.cuenta,
                                ifnull(c.descripcion,'') as nombreCuenta ,
                                CASE WHEN ter.nit is null THEN '' ELSE
                                   ifnull(CASE WHEN r.regimen ='SIMPLE' THEN CONCAT( ter.primer_apellido,' ',ter.segundo_apellido,' ',ter.primer_nombre,' ',ter.segundo_apellido) ELSE ter.razon_comercial END ,'') 
                                END as tercero,
                                ifnull(cc.centro,'') as centroCosto,
                                ifnull(cc.descripcion,'') as nombreCentroCosto ,
                                m.detalle,
                                m.debito,
                                m.credito,
                                ifnull(ter.id,0) as id_tercero ,
                                m.nit
                                from t_movimiento_contable m
                                INNER JOIN t_cuentas_contables c on c.cuenta = m.cuenta
                                LEFT  JOIN t_terceros ter on ter.nit = m.nit
                                LEFT  JOIN t_regimenes r on r.id = ter.id_regimen
                                LEFT  JOIN t_centros_costos cc on cc.centro = m.ceco
                                where m.id_transaccion = " . $c["id"];
                $detalleComprobante = generarArray($sqlMovimientos);

                $data[] = [
                    "id" => $c["id"],
                    "tipo" => $c["tipo"],
                    "numero" => $c["numero"],
                    "debito" => $c["debito"],
                    "credito" => $c["credito"],
                    "fecha" => $c["fecha"],
                    "usuario" => $c["usuario"],
                    "valor_total" => $c["valor_total"],
                    "iva" => $c["iva"],
                    "observacion" => $c["observacion"],
                    "nit" => $c["nit"],
                    "razon_comercial" => $c["razon_comercial"],
                    "tratamiento" => $c["tratamiento"],
                    "id_tipo_documento"=>$c["id_tipo_documento"],
                    "movimientos" => $detalleComprobante
                ];
            }//foreach

            echo json_encode($data);

        break;
}
