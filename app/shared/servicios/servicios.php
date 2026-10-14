<?php
session_start();
require_once('../../models/bd.php');
require_once('../../models/funciones.php');
$conn = conectar();

switch ($_POST["op"]) {
  case "paises":
    $sql = "select * from t_paises ";
    $paises = GenerarArray($sql);
    echo json_encode(array(
      'error' => false,
      'message' => '',
      'paises' => $paises
    ));
    break;
  case "departamentos":
    $codigo_pais = $_POST["codigo_pais"];
    $sql = "select * from t_departamentos  where codigo_pais='" . $codigo_pais . "' ";
    $departamentos = GenerarArray($sql);
    echo json_encode(array(
      'error' => false,
      'message' => '',
      'departamentos' => $departamentos
    ));
    break;
  case "municipios":
    $codigo_dpto = $_POST["codigo_dpto"];
    $codigo_pais = $_POST["codigo_pais"];
    $sql = "select * from t_municipios where codigo_dpto ='$codigo_dpto' and codigo_pais='$codigo_pais'";
    $municipios = GenerarArray($sql);
    echo json_encode(array(
      'error' => false,
      'message' => '',
      'municipios' => $municipios
    ));
    break;
  case "bodegas":
    $sql = "select * from t_almacenes where ifnull(inactivo,0) = 0 order by descripcion";

    $almacenes = GenerarArray($sql);
    echo json_encode($almacenes);
    break;
  case "cargar_sw":
    $sws = GenerarArray("select * from t_sw order by sw ");
    echo json_encode(array(
      'error' => false,
      'message' => 'success',
      'data' => $sws
    ));
    break;
  //Transacciones TMP
  case "add_transacciones_tmp":
    try {
      $data = $_POST;
      $data["id_usuario"] = $_SESSION["Ses_id"];
      $numero = numeroSiguiente($data["id_tipo_documento"]);
      $data["fecha"] = !empty($_POST['fecha']) ? $_POST['fecha'] : date("Y-m-d");
      $data["numero"] = $numero;

      $id = insertReturnId('t_transacciones_tmp', $data, true);

      if ($id > 0) {

        // $update = Query( "update t_tipos_documentos set rango_inicial=" . $numero . " where id=" . $data[ "id_tipo_documento" ] );

        echo json_encode(array(
          'error' => false,
          'message' => 'Se inserto correactamente el registro',
          'data' => array(
            'numero' => $numero,
            'id' => $id
          )
        ));
      } else {
        echo json_encode(array(
          'error' => true,
          'message' => 'Error al insertar el registro',
          'data' => array(
            'numero' => 0,
            'id' => 0
          )
        ));
      }
    } catch (\Throwable $th) {
      echo json_encode(array(
        'error' => true,
        'message' => $th->getMessage(),
        'data' => array(
          'numero' => 0,
          'id' => 0
        )
      ));
    }

    break;
  case "add_transacciones_detalle_tmp":
    try {
      $data = $_POST;

      // Me toco comentar esto porque falla cuando es FIFO
      /*if(
         $data["sw"] == 4 // devolucion en venta S
      || $data["sw"] == 5 // ventas S
      || $data["sw"] == 6 // salidas S
      || $data["sw"] == 7 // dev compras S
      ){
          // validar que el id_lote no sea 0 si el tipo de referencia es P
          $referencia = GenerarArray( "SELECT 
                                          tipo,
                                          ifnull(maneja_lote,'N') as maneja_lote 
                                          FROM t_referencias WHERE id=" . $data[ "id_referencia" ] );
          if ( count( $referencia ) > 0 ) {
            if ( $referencia[ 0 ][ "tipo" ] == 'P' 
            && $referencia[ 0 ][ "maneja_lote" ] == 'S' 
            && ( !isset( $data[ "id_lote" ] ) || $data[ "id_lote" ] == 0 ) ) {
              echo json_encode( array(
                'error' => true,
                'message' => 'El lote es obligatorio para referencias de tipo producto',
                'data' => array(
                  'id' => 0
                )
              ) );
              return;
            }
          }
      }*/

      $id = insertReturnId('t_transacciones_detalle_tmp', $data, true);
      if ($id > 0) {

        //actualizo los valores de encabezado 
        $dataEnc = actualizarTotalesEncabezado($data["id_transaccion"]);

        echo json_encode(array(
          'error' => false,
          'message' => 'Se inserto correactamente el registro',
          'data' => array(
            'id' => $id,
            'totales' => $dataEnc
          )
        ));
      } else {
        echo json_encode(array(
          'error' => true,
          'message' => 'Error al insertar el registro',
          'data' => array(
            'id' => 0,
            'totales' => 0
          )
        ));
      }
    } catch (\Throwable $th) {
      echo json_encode(array(
        'error' => true,
        'message' => $th->getMessage(),
        'data' => array(
          'id' => 0
        )
      ));
    }
    break;
  case "add_transacciones_caja_tmp":
    try {
      $idTransaccion = $_POST['id_transaccion'] ?? 0;
      $sw = $_POST["sw"];
      $cajas = $_POST['cajas'] ?? [];

      if (!$idTransaccion || empty($cajas)) {
        echo json_encode([
          'error' => true,
          'message' => 'No se enviaron cajas o id_transaccion',
          'data' => []
        ]);
        break;
      }

      $idsInsertados = [];
      foreach ($cajas as $row) {
        $dataInsert = [
          'id_transaccion' => $idTransaccion,
          'id_caja' => $row['idCaja'],
          'valor' => $row['valor']
        ];

        $id = insertReturnId('t_transacciones_cajas_tmp', $dataInsert, true);
        if ($id > 0) {
          $idsInsertados[] = $id;
          //guardar en t_transacciones_pago 

          $data_pago = array(
            'numero' => $_POST["numero"],
            'tipo' => $_POST["tipo"],
            'id_medio_pago' => 1,
            'valor' => $sw == 9 ? $dataInsert["valor"] * -1 : $dataInsert["valor"],
            'tipo_tarjeta' => '0',
            'voucher' => '',
            'cambio' => 0,
            'id_transaccion' => $idTransaccion,
            'id_transaccion_ref' => $idTransaccion,
            'id_usuario' => $_SESSION["Ses_id"]
          );
          insertReturnId('t_transacciones_pago_tmp', $data_pago, true);
        }
      }

      if (count($idsInsertados) > 0) {
        echo json_encode([
          'error' => false,
          'message' => 'Se insertaron correctamente las cajas',
          'data' => [
            'ids' => $idsInsertados
          ]
        ]);
      } else {
        echo json_encode([
          'error' => true,
          'message' => 'No se pudo insertar ninguna caja',
          'data' => []
        ]);
      }
    } catch (\Throwable $th) {
      echo json_encode([
        'error' => true,
        'message' => $th->getMessage(),
        'data' => []
      ]);
    }
    break;
  case "delete_transaccion_caja_tmp":

    try {
      if (!isset($_POST["id_transaccion"])) {
        echo json_encode([
          'error' => true,
          'message' => 'Es necesario el id de transacción'
        ]);
        return;
      }

      $id_transaccion = intval($_POST["id_transaccion"]); // Sanitizar

      // Ejecutar eliminación
      $delete = Query("DELETE FROM t_transacciones_cajas_tmp WHERE id_transaccion = $id_transaccion");

      if ($delete) {
        echo json_encode([
          'error' => false,
          'message' => "Registros de la transacción #$id_transaccion eliminados correctamente"
        ]);
      } else {
        echo json_encode([
          'error' => true,
          'message' => "No se encontraron registros para eliminar con id_transaccion = $id_transaccion"
        ]);
      }
    } catch (\Throwable $th) {
      echo json_encode([
        'error' => true,
        'message' => 'Error al eliminar: ' . $th->getMessage()
      ]);
    }

    break;

  case "update_transacciones_detalle_tmp":
    $data = $_POST;
    $update = update('t_transacciones_detalle_tmp', $data, (int)$data["id"]);
    if (!$update) {
      echo json_encode([
        'error' => true,
        'menssage' => 'Error al actualizar el registro',
        'data' => [
          'id' => 0,
          'totales' => null
        ]
      ]);
      break;
    }
    $dataEnc = actualizarTotalesEncabezado($data["id_transaccion"]);
    echo json_encode([
      'error' => false,
      'menssage' => 'Se actualizó correctamente el registro',
      'data' => [
        'id' => $data["id"],
        'totales' => $dataEnc
      ]
    ]);

    break;
  case "consultar_lote":
    $id_referencia = $_POST["id_referencia"];
    $lote = $_POST["lote"];
    $id_lote = 0;
    $lotes = GenerarArray("
            select 
            *
            from t_referencias_lotes
            where id_referencia=$id_referencia and lote='$lote' ");
    echo json_encode($lotes);
    break;
  case "update_encabezado":
    $id_transaccion = $_POST["id_transaccion"];
    $sql = "CALL recalcular_valor_total_transaccion(?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_transaccion);
    $stmt->execute();

    $result = GenerarArray("SELECT 
    valor_total,
    ifnull(descuento,0) as descuento,
    ifnull(iva,0) as iva,
    ifnull(retencion_fuente,0) as retencion_fuente,
    ifnull(retencion_ica,0) as retencion_ica,
    ifnull(retencion_iva,0) as retencion_iva,
    ifnull(retencion_cree,0) as retencion_cree

    FROM  t_transacciones_tmp where  id=$id_transaccion");

    if (count($result) > 0) {
      echo json_encode(array(
        'error' => false,
        'valor_total' => $result[0]["valor_total"],
        'descuento' => $result[0]["descuento"],
        'iva' => $result[0]["iva"],
        'retencion_fuente' => $result[0]["retencion_fuente"],
        'retencion_ica' => $result[0]["retencion_ica"],
        'retencion_iva' => $result[0]["retencion_iva"],
        'retencion_cree' => $result[0]["retencion_cree"],
        'total_pagar' => $result[0]["valor_total"] - $result[0]["retencion_fuente"] - $result[0]["retencion_ica"] - $result[0]["retencion_iva"] - $result[0]["retencion_cree"]

      ));
    } else {
      echo json_encode(array(
        'error' => false,
        'valor_total' => 0,
        'descuento' => 0,
        'iva' => 0,
        'retencion_fuente' => 0,
        'retencion_ica' => 0,
        'retencion_iva' => 0,
        'retencion_cree' => 0,
        'total_pagar' => 0

      ));
    }
    $stmt->close();
    $conn->close();
    break;
  case "detalle_transacciones_tmp":
    $id = (int)$_POST["id"];
    /* 
    anterior esto lo modifica Christian 2026-02-12 para reunion con distribuidora
   
    $confGeneral = $_SESSION[ "Ses_confSys" ][ 'confGeneral' ][ 0 ]["ManejaFechaLote"];

     if ( $confGeneral == 'S' ) {
       $sql_stock = "
                 select 
                 v.stock 
                 from v_referencias_stock_lotes v
                 where v.id=t.id_lote and v.id_bodega =t.id_bodega and v.id_referencia= t.id_referencia
       ";
     } else {
       $sql_stock = "
                 select 
                 v.stock 
                 from v_stock_total v
                 where v.id_referencia = t.id_referencia 
                 and v.id_bodega=t.id_bodega  
                 and v.mes=MONTH(CURRENT_DATE) 
                 and v.ano=YEAR(CURRENT_DATE)
       ";
     }*/

    //Inicio Christian    
    $conf = $_SESSION["Ses_confSys"]['confGeneral'];
    $manejaFecha = $conf[0]['ManejaFechaLote'] === 'S';
    $manejaFIFO = $conf[4]['ManejaFIFO'] === 'S';

    $usaStockPorLote = ($manejaFecha && !$manejaFIFO);

    if ($usaStockPorLote) {

      $sql_stock = "
                SELECT 
                  stock
                FROM v_referencias_stock_lotes
                WHERE id_referencia = t.id_referencia
                AND id_bodega       = t.id_bodega
                AND id_lote         = t.id_lote
            ";
    } else {

      $sql_stock = " SELECT 
                      stock
                      FROM v_stock_total
                      WHERE mes = MONTH(CURRENT_DATE)
                      AND ano = YEAR(CURRENT_DATE)
                      AND id_referencia = t.id_referencia
                      AND id_bodega     = t.id_bodega
            ";
    }

    //Fin Christian    

    $sql = "SELECT 
                 r.id,
                 r.codigo,
                 r.descripcion,
                 r.descripcion_larga,
                 m.descripcion as laboratorio,
                 r.und_1,
                 r.und_2,
                 r.und_3,
                 r.und_4,
                 r.cant_1,
                 r.cant_2,
                 r.cant_3,
                 r.cant_4,
                 t.id as id_detalle,
                 t.id_transaccion,
                 t.cantidad,
                 t.valor_unitario,
                 t.costo_unitario,
                 t.id_lote,
                 t.pcj_iva as iva,
                 t.pcj_descuento,
                 t.und,
                 case when t.can_und=r.cant_1 then 1 
                      when t.can_und=r.cant_2 then 2
                      when t.can_und=r.cant_3 then 3
                      when t.can_und=r.cant_4 then 4
                end  as und_compra,
                 t.can_und,
                 t.lote,
                 t.vmcto as vencimiento,
                  ifnull((
                    $sql_stock
                ),0) as total_stock,
                ifnull(t.impoconsumo,0) as impoconsumo,
                ifnull(t.ibua,0) as ibua,
                ifnull(t.icui,0) as icui,
                l.tipo_lista,
                l.precio_lista,
                l.precio_lista_2,
                l.precio_lista_3,
                l.precio_lista_4,
                l.precion,
                l.precion2,
                l.precion3,
                l.precion4,
                l.precio,
                l.precio2,
                l.precio3,
                l.precio4,
                ifnull(l.promedio,0) as promedio,
                l.calculo_comision,
                l.comision1,
                l.comision2,
                l.comision3,
                l.comision4,
                l.comision,
                r.tipo,
                ifnull((
                  select 
                  sum(cantidad) as comprometida
                  from v_unidades_comprometidas
                  where id_referencia = t.id_referencia and id_bodega = t.id_bodega and id_lote = t.id_lote

                ),0) as comprometidas,
                ifnull(r.ultimo_costo_compra,0) AS ultimo_costo_compra
                from t_transacciones_detalle_tmp t
                inner join t_transacciones_tmp tt on tt.id=t.id_transaccion
                inner join t_referencias r on r.id=t.id_referencia 
                left  join t_marcas      m on m.id=r.id_marca
                left join v_referencias l on l.id=t.id_referencia and l.lista = tt.lista
               
                where t.id=$id ";

    $referencia = GenerarArray($sql, '');
    echo json_encode($referencia);
    break;
  case "busqueda_temporal":
    try {
      if (!isset($_POST["fecha_inicial"])) {
        echo json_encode(array(
          'error' => true,
          'message' => 'Debe enviar fecha inicial',
          'data' => []
        ));
        return;
      }
      if (!isset($_POST["fecha_final"])) {
        echo json_encode(array(
          'error' => true,
          'message' => 'Debe enviar fecha final',
          'data' => []
        ));
        return;
      }
      if (!isset($_POST["sw"])) {
        echo json_encode(array(
          'error' => true,
          'message' => 'No se envio el identificador del documento',
          'data' => []
        ));
        return;
      }

      // Valores saneados (antes se concatenaban directo: inyección SQL)
      $fecha_inicial = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST["fecha_inicial"]) ? $_POST["fecha_inicial"] : '';
      $fecha_final = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST["fecha_final"]) ? $_POST["fecha_final"] : '';
      $sw = (int)$_POST["sw"];
      $nit = isset($_POST["nit"]) ? preg_replace('/[^0-9A-Za-z\-]/', '', $_POST["nit"]) : '';
      $bodega = (isset($_POST["bodega"]) && $_POST["bodega"] !== '') ? (int)$_POST["bodega"] : '';
      $es_credito = (isset($_POST["es_credito"]) && $_POST["es_credito"] !== '') ? (int)$_POST["es_credito"] : '';

      $sql = "SELECT 
                    t.id as id_tercero,
                    t.nit,
                    CONCAT_WS(' ', t.primer_nombre, t.segundo_nombre, t.primer_apellido, t.segundo_apellido) as nombres,
                    t.razon_comercial,
                    t.email_fe,
                    t.cupo_credito,
                    d.id_bodega,
                    d.id_tipo_documento,
                    d.notas,
                    d.id as id_transaccion,
                    d.valor_total,
                    d.numero,
                    u.usuario,
                    d.fecha_hora,
                    z.tipo ,
                    d.iva,
                    d.descuento ,
                    d.id_tercero_dir,
                    c.id as id_condicion_pago,
                    c.descripcion as des_condicion,
                    d.lista as lista_precios,
                    d.zona_ventas,
                    d.zona_descripcion,
                    d.documento,
                    d.id_concepto_retencion,
                    d.retencion_fuente,
                    d.retencion_ica,
                    d.retencion_iva,
                    d.retencion_cree,
                    d.id_bodega_destino,
                    COALESCE(dx.direccion, (select direccion from t_terceros_direcciones where id_tercero = d.id_tercero limit 1)) as direccion,
                    d.es_domicilio
                  from t_transacciones_tmp d
                  inner join t_terceros             t   on t.id = d.id_tercero
                  inner join t_condiciones_pago     c   on c.id = d.id_condicion 
                  left  join t_terceros_direcciones dx  on dx.id_tercero =t.id and ifnull(dx.principal,0)=1
                  inner join t_usuarios             u   on u.id = d.id_usuario
                  inner join t_tipos_documentos     z   on z.id = d.id_tipo_documento 
                  where  d.sw = $sw
            ";

      if ($_SESSION["Ses_roles_id"] != 1 && $_SESSION["Ses_roles_id"] != 4) {
        //Rol super y rol administrativo pueden ver todas las transacciones
        $sql .= " AND  d.id_usuario=" . (int)$_SESSION["Ses_id"];
      }
      if ($fecha_inicial != '' && $fecha_final != '') {
        // Rango sin CAST sobre la columna: permite usar índice en fecha_hora
        $sql .= " AND d.fecha_hora >= '$fecha_inicial 00:00:00' AND d.fecha_hora < DATE_ADD('$fecha_final', INTERVAL 1 DAY)";
      }

      if ($nit != '') {
        $sql .= " AND t.nit = '$nit'";
      }

      if ($bodega != '') {
        $sql .= " AND d.id_bodega=" . $bodega;
      }

      if ($es_credito != '') {
        $sql .= " AND d.es_credito = " . $es_credito;
      }
      $sql .= " ORDER BY d.fecha_hora DESC";

      $datos = GenerarArray($sql, '');

      echo json_encode(array(
        'error' => false,
        'message' => 'Success',
        'data' => $datos
      ));
    } catch (\Throwable $th) {
      echo json_encode(array(
        'error' => true,
        'message' => $th->getMessage(),
        'data' => null
      ));
    }

    break;
  case "busqueda_temporal_det":
    $id_transaccion = (int)$_POST["id_transaccion"];
    // ligero=1: omite el cálculo de stock (2 vistas por fila). Por defecto se conserva el comportamiento anterior.
    $ligero = isset($_POST["ligero"]) && $_POST["ligero"] == '1';
    $sel_stock = $ligero
      ? "0 as stock_actual, 0 as stock_lote,"
      : "ifnull(s.stock,0) as stock_actual,
                  ifnull((
                            SELECT 
                               stock
                            FROM v_referencias_stock_lotes
                            WHERE id_referencia = t.id_referencia
                            AND id_bodega       = t.id_bodega
                            AND id_lote         = t.id_lote
                    ),0) as stock_lote,";
    $join_stock = $ligero
      ? ""
      : "left  join v_stock_total s on s.id_referencia=r.id 
                and s.id_bodega=t.id_bodega 
                and s.mes=MONTH(CURRENT_DATE) 
                and s.ano=YEAR(CURRENT_DATE)";
    $sql = "SELECT 
                  t.id as id_transaccion_det_tmp,
                  r.codigo,
                  r.descripcion,
                  IFNULL(m.descripcion, '') as laboratorio,
                  t.id,
                  t.id_transaccion,
                  t.id_referencia,
                  t.cantidad,
                  t.valor_unitario,
                  t.costo_unitario,
                  t.id_lote,
                  t.pcj_iva,
                  t.pcj_descuento,
                  t.und,
                  t.can_und,
                  t.vmcto,
                  t.lote,
                  t.sw,
                  t.id_bodega,
                  t.id_pedido,
                  ifnull(t.impoconsumo,0)  as impoconsumo,
                  ifnull(t.ibua,0) as ibua,
                  ifnull(t.icui,0) as icui,
                  $sel_stock
                  ifnull(r.ultimo_costo_compra,0) as costo_ultimo
                from t_transacciones_detalle_tmp t
                inner join t_referencias r on r.id=t.id_referencia
                left  join t_marcas m on m.id=r.id_marca
                $join_stock
                where 
                t.id_transaccion=$id_transaccion
                order by t.id
            ";
    $datos = GenerarArray($sql, '');

    echo json_encode($datos);
    break;
  case "delete_transaccion":

    if (!isset($_POST["id_transaccion"])) {
      echo json_encode(array(
        'error' => true,
        'message' => 'Es necesario el id de transaccion'
      ));
      return;
    }
    $id_transaccion = (int)$_POST["id_transaccion"];

    $delete_det = Query("delete from t_transacciones_detalle_tmp where id_transaccion=$id_transaccion");
    $delete_pag = Query("delete from t_transacciones_pago_tmp where id_transaccion=$id_transaccion"); // antes quedaban pagos huérfanos
    $delete_enc = Query("delete from t_transacciones_tmp where id=$id_transaccion");


    echo json_encode(array(
      'error' => false
    ));
    break;
  case "delete_item":

    if (!isset($_POST["id"])) {
      echo json_encode(array(
        'error' => true,
        'message' => 'Es necesario el id del item'
      ));
      return;
    }

    if (!isset($_POST["id_transaccion"])) {
      echo json_encode(array(
        'error' => true,
        'message' => 'Es necesario el id de transaccion'
      ));
      return;
    }

    $id = (int)$_POST["id"];
    $id_transaccion = (int)$_POST["id_transaccion"];
    // Se exige que el ítem pertenezca al documento
    $sql = "delete from t_transacciones_detalle_tmp where id=$id and id_transaccion=$id_transaccion";
    $del = Query($sql);

    if ($del) {
      $dataEnc = 0;
      // COUNT(*) en vez de traer todas las filas
      $conteo = GenerarArray("select count(*) as total from t_transacciones_detalle_tmp where id_transaccion = $id_transaccion");
      $total_reg = (int)($conteo[0]["total"] ?? 0);

      if ($total_reg > 0) {
        $dataEnc = actualizarTotalesEncabezado($id_transaccion);
      } else {
        Query("delete from t_transacciones_pago_tmp where id_transaccion=" . $id_transaccion);
        $del = Query("delete from t_transacciones_tmp where id=" . $id_transaccion);
        $dataEnc = 0;
      }

      echo json_encode(array(
        'error' => false,
        'message' => 'Se elimino correctamente el registro!',
        'total_reg' => $total_reg,
        'totales' => $dataEnc
      ));
    } else {
      echo json_encode(array(
        'error' => true,
        'message' => 'Error al eliminar el registro!',
        'total_reg' => null,
        'totales' => 0
      ));
    }
    break;
  case "volcar_documento":

    $lockVolcado = '';
    try {
      if (!isset($_POST["id_transaccion"]) || (int)$_POST["id_transaccion"] <= 0) {
        echo json_encode(array(
          "ok" => false,
          'message' => 'Es necesario enviar el id de la transaccion!'
        ));
        return;
      }
      $id_transaccion = (int)$_POST["id_transaccion"];

      // Bloqueo por documento: evita que un doble clic / doble petición genere dos facturas
      $lockVolcado = 'volcar_' . DB . '_' . $id_transaccion;
      $lock = $conn->query("SELECT GET_LOCK('" . $conn->real_escape_string($lockVolcado) . "', 10) AS ok")->fetch_assoc();
      if ((int)$lock['ok'] !== 1) {
        $lockVolcado = '';
        echo json_encode(array(
          "ok" => false,
          'message' => 'El documento ya se está procesando, por favor espere.'
        ));
        return;
      }
      $existe = $conn->query("SELECT COUNT(*) AS n FROM t_transacciones_tmp WHERE id = $id_transaccion")->fetch_assoc();
      if ((int)$existe['n'] === 0) {
        echo json_encode(array(
          "ok" => false,
          'message' => 'El documento ya fue procesado o no existe.'
        ));
        return;
      }


      if (isset($_POST["updateVendedor"]) && $_POST["updateVendedor"] == 'S') {
        if (isset($_POST["vendedor"]) && !empty($_POST["vendedor"])) {
          $update = Query(
            "UPDATE t_transacciones_tmp 
             SET vendedor = " . (int)$_POST["vendedor"] . " 
             WHERE id = " . (int)$_POST["id_transaccion"]
          );
        } else {
          echo json_encode(array(
            "ok" => false,
            'message' => 'Esta parametrizado el vendedor, debe seleccionarlo!'
          ));
          return;
        }
      }

      //valido los stock de los item antes de volcar el documento

      $sql_validacion = " SELECT
                              d.id_referencia,
                              r.codigo,
                              r.descripcion,
                              SUM(d.cantidad) AS cant_solicitada,
                              MAX(l.stock) AS stock_actual,
                              MAX(l.stock) - SUM(d.cantidad) AS diferencia
                          FROM t_transacciones_detalle_tmp d
                          INNER JOIN t_referencias r on r.id = d.id_referencia and r.tipo  = 'P' 
                          INNER JOIN v_referencias_stock_lotes l 
                              ON l.id_referencia = d.id_referencia
                              AND l.id_bodega = d.id_bodega
                              AND l.id_lote = d.id_lote
                          WHERE d.id_transaccion = ? AND d.sw IN (5,6)
                          GROUP BY d.id_referencia, d.id_bodega, d.id_lote, r.codigo, r.descripcion";
      //Se realiza esta validacion de stock solo para las salidas de inventario, es decir sw 5:ventas y 6:salidas de inventario

      $stmt_val = $conn->prepare($sql_validacion);
      $stmt_val->bind_param("i", $id_transaccion);
      $stmt_val->execute();
      $result_val = $stmt_val->get_result();

      $errores_stock = [];

      while ($row = $result_val->fetch_assoc()) {

        if ($row["diferencia"] < 0) {
          $errores_stock[] = [
            "codigo" => $row["codigo"],
            "producto" => $row["descripcion"],
            "stock_actual" => $row["stock_actual"],
            "cant_solicitada" => $row["cant_solicitada"]
          ];
        }
      }

      if (count($errores_stock) > 0) {

        echo json_encode([
          "ok" => false,
          "message" => "No hay stock suficiente para algunos productos",
          "productos" => $errores_stock
        ]);

        return;
      }
      $query = "CALL p_volcar_documento(?,@nuevoid)";

      $stmt = $conn->prepare($query);

      if (!$stmt) {
        echo json_encode(array('ok' => false, 'message' => $conn->error, 'id' => 0));
        return;
      }

      $stmt->bind_param("i", $id_transaccion);

      if (!$stmt->execute()) {   // antes se evaluaba !$stmt, que nunca fallaba después de execute
        echo json_encode(array('ok' => false, 'message' => $stmt->error, 'id' => 0));
        return;
      }
      $stmt->close();
      drenarResultados($conn);


      $result = $conn->query("SELECT @nuevoid AS nuevoid");

      if (!$result) {
        echo json_encode(array('ok' => false, 'message' => 'Error al insertar el registro tmp ' . $id_transaccion, 'id' => 0));
        return;
      }

      $nuevoid = $result->fetch_assoc();

      if ($nuevoid['nuevoid'] > 0) {
        // si es una compra debo actualizar la cantidad despachada de t_pedidos_Detalle
        $sw = GenerarArray("select sw from t_transacciones where id =" . (int)$nuevoid['nuevoid']);

        if ($sw[0]['sw'] == 1) { // compras
          $update_pedido = Query("UPDATE t_pedidos_detalle pd
                                              INNER JOIN (
                                                  SELECT 
                                                      id_pedido,
                                                      id_referencia,
                                                      SUM(cantidad) AS total_cantidad
                                                  FROM t_transacciones_detalle
                                                  WHERE id_transaccion = " . $nuevoid['nuevoid'] . "
                                                    AND id_pedido > 0
                                                  GROUP BY id_pedido, id_referencia
                                              ) td ON td.id_pedido = pd.id_pedido
                                                  AND td.id_referencia = pd.id_referencia
                                              SET pd.cantidad_desp = ifnull(pd.cantidad_desp, 0) + td.total_cantidad;
                                                        ");
        }

        echo json_encode(
          array(
            'ok' => true,
            'message' => 'Se inserto correctamente el registro',
            'id' => $nuevoid['nuevoid']
          )
        );
      } else {
        echo json_encode(
          array(
            'ok' => false,
            'message' => 'Error al insertar el registro '
          )
        );
      }
    } catch (\Throwable $th) {
      echo json_encode(array(
        'ok' => false,
        'message' => $th->getMessage()
      ));
    } finally {
      if ($lockVolcado !== '') {
        $conn->query("SELECT RELEASE_LOCK('" . $conn->real_escape_string($lockVolcado) . "')");
      }
    }
    break;
  case "generarContabilidad":
    $sw = (int)$_POST['sw'];
    $id = (int)$_POST['id'];
    $res = generarMovimientoContable($sw, $id);
    echo json_encode($res);
    break;
  case "asignar_lotes":

    try {
      if (!isset($_POST["id_transaccion"])) {
        echo json_encode(array(
          'error' => true,
          'message' => 'Es necesario enviar el id de la transaccion!'
        ));
        return;
      }
      $id_transaccion = $_POST["id_transaccion"];
      $error = [];

      $lotes = GenerarArray("
                select 
                    id,
                    id_referencia,
                    lote,
                    vmcto
                from t_transacciones_detalle_tmp 
                    where id_transaccion = $id_transaccion
            ", '');

      foreach ($lotes as $key) {
        # code...
        $existe = GenerarArray("
                select 
                * 
                from t_referencias_lotes 
                where id_referencia =" . $key["id_referencia"] . " 
                AND lote='" . trim($key["lote"]) . "'");

        if (count($existe) > 0) {
          $update = $conn->query("update t_transacciones_detalle_tmp set id_lote=" . $existe[0]["id"] . " where id=" . $key["id"]);
          if (!$update) {
            $error[] = array('message' => $conn->error);
          }
        } else {
          $stmt = $conn->prepare("INSERT INTO  t_referencias_lotes(id_referencia,lote,vencimiento) VALUES (?,?,?)");
          if (!$stmt) {
            echo json_encode(array(
              'ok' => false,
              'message' => print_r($conn->error),
              'id' => 0
            ));
            return;
          }
          $stmt->bind_param("sss", $key["id_referencia"], $key["lote"], $key["vmcto"]);
          if (!$stmt->execute()) {
            $error[] = array('message' => $conn->error);
            return;
          }
          $ultimo_id = $conn->insert_id;

          $update = $conn->query("update t_transacciones_detalle_tmp set id_lote=" . $ultimo_id . " where id=" . $key["id"]);
          if (!$update) {
            $error[] = array('message' => $conn->error);
          }
        }
      }

      echo json_encode(array(
        'ok' => count($error) > 0 ? false : true,
        'message' => $error
      ));
    } catch (\Throwable $th) {
      echo json_encode(array(
        'ok' => true,
        'message' => $th->getMessage()
      ));
    }
    break;
  //Pedidos encabezado 
  case "add_pedidos":
    try {
      $data = $_POST;
      $data["id_usuario"] = $_SESSION["Ses_id"];
      $data["numero"] = 0;


      if (!isset($data["id_tipo_documento"])) {
        echo json_encode(array(
          'ok' => false,
          'error' => true,
          'data' => array(
            'id' => 0
          ),
          'message' => 'Sin tipo de documento imposible avanzar'
        ));
        return;
      }
      $id_tipo = $data["id_tipo_documento"];
      $numero = numeroSiguiente($id_tipo, 't_pedidos');
      $data["numero"] = $numero;

      $id = insertReturnId('t_pedidos', $data, true);
      if ($id > 0) {
        //
        $update = Query("
          update t_tipos_documentos set numero=$numero where id =$id_tipo;
        ");

        echo json_encode(array(
          'error' => false,
          'message' => 'Se inserto correactamente el registro',
          'data' => array(
            'id' => $id,
            'numero' => $data["numero"]
          )
        ));
      } else {
        echo json_encode(array(
          'error' => true,
          'message' => 'Error al insertar el registro',
          'data' => array(
            'id' => 0,
            'numero' => 0
          )
        ));
      }
    } catch (\Throwable $th) {
      echo json_encode(array(
        'error' => true,
        'message' => $th->getMessage(),
        'data' => array(
          'id' => 0
        )
      ));
    }

    break;
  //Pedidos detalle
  case "add_pedido_detalle":
    try {
      $data = $_POST;
      $id = insertReturnId('t_pedidos_detalle', $data, true);
      if ($id > 0) {


        $dataEnc = actualizarTotalesEncabezadoPedido($data["id_pedido"]);

        echo json_encode(array(
          'error' => false,
          'message' => 'Se inserto correactamente el registro',
          'data' => array(
            'id' => $id,
            'totales' => $dataEnc
          )
        ));
      } else {
        echo json_encode(array(
          'error' => true,
          'message' => 'Error al insertar el registro',
          'data' => array(
            'id' => 0,
            'totale' => array()
          )
        ));
      }
    } catch (\Throwable $th) {
      echo json_encode(array(
        'error' => true,
        'message' => $th->getMessage(),
        'data' => array(
          'id' => 0
        )
      ));
    }
    break;
  case "update_encabezado_pedido":
    $id_pedido = $_POST["id_pedido"];
    $sql = "CALL recalcular_valor_total_pedido(?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_pedido);
    $stmt->execute();

    $result = GenerarArray("SELECT valor_total FROM t_pedidos where id = $id_pedido");

    if (count($result) > 0) {
      echo json_encode(array(
        'error' => false,
        'valor_total' => $result[0]["valor_total"]
      ));
    } else {
      echo json_encode(array(
        'error' => true,
        'valor_total' => $result[0]["valor_total"]
      ));
    }
    $stmt->close();
    $conn->close();
    break;

  case "delete_item_pedido":

    if (!isset($_POST["id"])) {
      echo json_encode(array(
        'error' => true,
        'message' => 'Es necesario el id del item'
      ));
      return;
    }

    if (!isset($_POST["id_pedido"])) {
      echo json_encode(array(
        'error' => true,
        'message' => 'Es necesario el id de pedido'
      ));
      return;
    }

    $id = $_POST["id"];
    $id_pedido = $_POST["id_pedido"];
    $sql = "delete from t_pedidos_detalle where id = $id";
    $del = Query($sql);
    $dataEnc = array();

    if ($del) {
      $total_items = GenerarArray("select * from t_pedidos_detalle where id_pedido = $id_pedido");
      if (count($total_items) == 0) {
        $del_ped = Query("delete from t_pedidos where id = $id_pedido");
      } else {
        $dataEnc = actualizarTotalesEncabezadoPedido($id_pedido);
      }
      echo json_encode(array(
        'error' => false,
        'message' => 'Se elimino correctamente el registro!',
        'total_reg' => count($total_items),
        'totales' => $dataEnc
      ));
    } else {
      echo json_encode(array(
        'error' => true,
        'message' => 'Error al eliminar el registro!',
        'total_reg' => 0,
        'totales' => $dataEnc
      ));
    }
    break;
  case "detalle_pedido":
    $id = $_POST["id"];
    $sql = "select 
                 r.id,
                 r.codigo,
                 r.descripcion,
                 r.descripcion_larga,
                 m.descripcion as laboratorio,
                 r.und_1,
                 r.und_2,
                 r.und_3,
                 r.und_4,
                 r.cant_1,
                 r.cant_2,
                 r.cant_3,
                 r.cant_4,
                 t.id as id_detalle,
                 t.id_pedido,
                 t.cantidad,
                 t.valor_unitario,
                 t.costo_unitario,
                 t.pcj_iva as iva,
                 t.pcj_descuento,
                 t.und,
                 IFNULL((
                    SELECT SUM(v.stock) 
                    FROM v_stock_total v
                    WHERE v.id_referencia = r.id and v.mes=MONTH(CURRENT_DATE()) and v.ano =YEAR(CURRENT_DATE())
                 ),0) AS total_stock,
                 case when t.can_und=r.cant_1 then 1 
                      when t.can_und=r.cant_2 then 2
                      when t.can_und=r.cant_3 then 3
                      when t.can_und=r.cant_4 then 4
                end  as und_compra,
                t.can_und,
                t.cantidad_desp,
                t.cantidad2,
                l.precio,
                l.precio2,
                l.precio3,
                l.precio4,
                l.precion,
                l.precion2,
                l.precion3,
                l.precion4,
                r.ultimo_costo_compra
            from t_pedidos_detalle t
            inner join t_pedidos p on p.id= t.id_pedido 
            inner join t_referencias r on r.id=t.id_referencia
            left  join t_marcas m on m.id=r.id_marca
            left  join v_referencias l  on l.id = t.id_referencia and l.lista = p.lista_precios 
            where 
                 t.id = $id ";

    $referencia = GenerarArray($sql, '');
    echo json_encode($referencia);
    break;
  case "update_pedido_detalle":
    $data = $_POST;
    $update = update('t_pedidos_detalle', $data, $data["id"]);
    if ($update) {

      $dataEnc = actualizarTotalesEncabezadoPedido($data["id_pedido"]);
      echo json_encode(array(
        'error' => false,
        'menssage' => 'Se actualizo correctamente el registro',
        'totales' => $dataEnc
      ));
    } else {
      echo json_encode(array(
        'error' => true,
        'menssage' => 'Error al actualizar el registro',
        'totales' => array()
      ));
    }
    break;
  case "busqueda_pedido":

    try {
      if (!isset($_POST["fecha_inicial"])) {
        echo json_encode(array(
          'error' => true,
          'message' => 'Debe enviar fecha inicial',
          'data' => null
        ));
        return;
      }
      if (!isset($_POST["fecha_final"])) {
        echo json_encode(array(
          'error' => true,
          'message' => 'Debe enviar fecha final',
          'data' => null
        ));
        return;
      }

      $fecha_inicial = $_POST["fecha_inicial"];
      $fecha_final = $_POST["fecha_final"];
      $nit = isset($_POST["nit"]) ? $_POST["nit"] : '';
      $sw = isset($_POST["sw"]) ? $_POST["sw"] : 1;
      $estado = isset($_POST["estado"]) ? $_POST["estado"] : '';
      $bodega = $_SESSION["Ses_id_bodega"];

      $col_edi = $conn->query("SHOW COLUMNS FROM t_pedidos LIKE 'estado_edi'");
      $sel_edi = ($col_edi && $col_edi->num_rows > 0) ? "ifnull(d.estado_edi,'') as estado_edi" : "'' as estado_edi";

      $sql = "
               CREATE TEMPORARY TABLE datos AS
                select
                t.id as id_tercero,
                t.nit,
                CONCAT(t.primer_nombre,' ',t.segundo_nombre,' ',t.primer_apellido,' ',t.segundo_apellido) as nombres,
                t.razon_comercial,
                d.id_bodega,
                CONCAT(a.almacen,' - ', a.descripcion) as bodega,
                d.notas,
                d.id as id_pedido,
                d.valor_total,
                ifnull(d.descuento,0) as descuento,
                ifnull(d.iva,0) as iva,
                UPPER(u.usuario) as usuario,
                d.fecha_hora,
                t.email_fe,
                d.estado,
                IFNULL(s.id,0) as id_transaccion_tmp,
                IFNULL(s.numero,0) as num_transaccion_tmp,
                d.id_condicion as condicion,
                d.numero,
                d.id_tercero_dir,
                t.edi,
                t.ean_edi,
                ifnull((
                  select 
                    sum(  ((valor_unitario*(1-(pcj_descuento/100)))*((pcj_iva/100)+1))*cantidad_desp  )
                  from t_pedidos_detalle 
                  where id_pedido = d.id
                ),0) as total_ingresado ,
                ifnull((
                  select 
                    count(*)
                  from t_pedidos_detalle 
                  where id_pedido = d.id
                ),0) as items ,
                ifnull((
                  select 
                    sum( case when cantidad=cantidad_desp then 1 else 0 end )
                  from t_pedidos_detalle 
                  where id_pedido = d.id
                ),0) as items_ingresados ,
                t.lista_precios  ,
                ifnull(s.documento,'') as documento  ,
                t.id_perfil_tributario  ,
                ifnull(s.id_concepto_retencion,0) as id_concepto_retencion,
                {$sel_edi}
              from t_pedidos d
              inner join t_terceros  t on t.id = d.id_tercero 
              inner join t_usuarios  u on u.id = d.id_usuario
              inner join t_almacenes a on a.id = d.id_bodega
              left  join t_transacciones_tmp s on s.id_bodega  = d.id_bodega 
             --  AND s.sw = d.sw 
              AND s.id_pedido = d.id
              AND s.id_pedido > 0";

      $sql .= " WHERE 
                d.sw = $sw 
                AND d.id_bodega = $bodega ";

      if ($estado != '') {
        $sql .= " AND d.estado='$estado' ";
      }

      if ($_SESSION["Ses_roles_id"] != 1 && $_SESSION["Ses_roles_id"] != 3) {
        $sql .= " AND  d.id_usuario = " . $_SESSION["Ses_id"];
      }
      if ($fecha_inicial != '' && $fecha_final != '') {
        $sql .= " AND  CAST(d.fecha_hora AS DATE) BETWEEN CAST('$fecha_inicial' AS DATE) AND CAST('$fecha_final' AS DATE)";
      }

      if ($nit != '' && $nit != '0') {
        $sql .= " AND t.nit=" . $nit;
      }


      $pedidos = $conn->query($sql);

      $result = $conn->query("SELECT 
                                *,
                                round((items_ingresados /items)*100,0) as porcentaje_ingresado
                               FROM datos WHERE items<>items_ingresados ");

      $datos = array();

      // Obtener los resultados de la consulta
      while ($row = $result->fetch_assoc()) {
        // Agregar el resultado al array, aplicando la función 'esFecha' si es necesario
        $datos[] = $row; //array_map('esFecha', $row);
      }


      echo json_encode(array(
        'error' => false,
        'message' => 'Success',
        'data' => $datos
      ));
    } catch (\Throwable $th) {
      echo json_encode(array(
        'error' => true,
        'message' => $th->getMessage(),
        'data' => null
      ));
    }

    break;
  case "delete_pedido":
    try {
      if (!isset($_POST["id_pedido"])) {
        echo json_encode([
          'error' => true,
          'message' => 'Es necesario el id del pedido.'
        ]);
        return;
      }
      $id_pedido = (int)$_POST["id_pedido"];
      $conn->begin_transaction();
      // Validar que exista el pedido y que sea temporal
      $stmt = $conn->prepare(" SELECT estado FROM t_pedidos  WHERE id = ? FOR UPDATE ");
      $stmt->bind_param("i", $id_pedido);
      $stmt->execute();
      $result = $stmt->get_result();
      if ($result->num_rows == 0) {
        $conn->rollback();
        echo json_encode([
          'error' => true,
          'message' => 'El pedido no existe.'
        ]);
        return;
      }
      $pedido = $result->fetch_assoc();
      if ($pedido["estado"] != "T") {
        $conn->rollback();
        echo json_encode([
          'error' => true,
          'message' => 'Solo es posible eliminar pedidos temporales.'
        ]);
        return;
      }
      // Eliminar detalle
      $stmt = $conn->prepare(" DELETE  FROM t_pedidos_detalle  WHERE id_pedido = ? ");
      $stmt->bind_param("i", $id_pedido);
      if (!$stmt->execute()) {
        throw new Exception($stmt->error);
      }
      // Eliminar encabezado
      $stmt = $conn->prepare(" DELETE FROM t_pedidos  WHERE id = ? ");
      $stmt->bind_param("i", $id_pedido);
      if (!$stmt->execute()) {
        throw new Exception($stmt->error);
      }
      $conn->commit();
      echo json_encode([
        'error' => false,
        'message' => 'Pedido eliminado correctamente.'
      ]);
    } catch (\Throwable $th) {
      $conn->rollback();
      echo json_encode([
        'error' => true,
        'message' => $th->getMessage()
      ]);
    }
    break;
  case "buscar_detalle_pedido":
    $id_pedido = $_POST["id_pedido"];
    $sql = "select 
              r.codigo,
              r.descripcion,
              r.descripcion_larga,
              r.maneja_lote,
              r.und_1 ,
              r.und_2 ,
              r.und_3 ,
              r.und_4 ,
              r.cant_1 ,
              r.cant_2 ,
              r.cant_3 ,
              r.cant_4 ,
              t.id,
              t.id_pedido ,
              t.id_referencia ,
              t.cantidad ,
              t.valor_unitario ,
              t.costo_unitario ,
              t.pcj_iva ,
              t.pcj_descuento ,
              t.und ,
              t.can_und ,
              t.sw,
              t.id_bodega ,
              IFNULL((
                SELECT SUM(v.stock) 
                FROM v_stock_total v
                WHERE v.id_referencia = t.id_referencia and v.mes=MONTH(CURRENT_DATE()) and v.ano =YEAR(CURRENT_DATE())
              ),0) AS total_stock,
              IFNULL(m.descripcion,'') as marca,
              IFNULL(( 
                SELECT SUM(td.cantidad/td.can_und) 
                FROM t_transacciones_detalle td 
                WHERE td.id_pedido = t.id_pedido and 
                td.id_referencia = t.id_referencia),0) AS ingresadas,
              IFNULL(( 
                SELECT SUM(td.cantidad/td.can_und) 
                FROM t_transacciones_detalle_tmp td 
                WHERE td.id_pedido = t.id_pedido and 
                td.id_referencia = t.id_referencia),0) AS temporales,
                ifnull(t.cantidad_desp,0) as cantidad_desp,
                t.cantidad2,
                ifnull((
                  select 
                  count(*)
                  from t_pedidos_lotes 
                  where id_detalle_pedido = t.id
                ),0) as n_lotes_pedidos ,
                ifnull(i.porcentaje_icui,0)	 as icui,
                ifnull(r.impoconsumo,0) as impoconsumo,
                ifnull(r.ibua,0) as ibua,
                IFNULL((
                    SELECT ifnull(v.promedio,0)
                    FROM v_costo_promedio v
                    WHERE v.id_referencia = t.id_referencia AND mes= MONTH(current_date) AND ano=YEAR(current_date)
                ),IFNULL((
                    SELECT costo_unitario
                    FROM t_transacciones_detalle
                    WHERE sw IN (5,6,7)
                    ORDER BY fecha_hora DESC
                    LIMIT 1
                ),r.costo_unitario)) AS promedio,

                IFNULL((
                    SELECT 
                       costo_unitario
                    FROM t_transacciones_detalle
                    WHERE sw = 1
                    ORDER BY fecha_hora DESC
                    LIMIT 1
                ),r.costo_unitario) AS ultimo_costo,
                r.ultimo_costo_compra
            from t_pedidos_detalle t
            inner join t_referencias r on r.id=t.id_referencia
            left join t_marcas m on m.id  = r.id_marca 
            left join t_icui i on i.id= r.id_icui
            where  
              t.id_pedido = $id_pedido";

    $datos = GenerarArray($sql, '');
    $arrayDatos = [];
    foreach ($datos as $fila) {
      $ean = GenerarArray("SELECT ean FROM t_referencias_ean WHERE id_referencia = " . (int)$fila['id_referencia']);
      $arrayEan = array_map(function ($filaEan) {
        return $filaEan['ean'];
      }, $ean);

      $pos = GenerarArray("SELECT 
      id_referencia,id,
      IFNULL(vmcto,'N/A') as vmcto,
      IFNULL(lote,'N/A') as lote, 
      (cantidad/can_und) as cantidad
      FROM t_transacciones_detalle_tmp
      WHERE id_referencia= " . (int)$fila['id_referencia'] . " and id_pedido = " . (int)$fila['id_pedido']);


      $arrayDatos[] = [
        'datos' => $fila,
        'ean' => $arrayEan,
        'posiciones' => $pos
      ];
    }
    echo json_encode($arrayDatos);
    break;
  case "buscar_detalle_transaccion_tmp":
    $id_pedido = $_POST["id_pedido"];
    $id_transaccion = $_POST["id_transaccion"];

    $sql = "select 
              r.codigo,
              r.descripcion,
              r.descripcion_larga,
              r.maneja_lote,
              r.und_1 ,
              r.und_2 ,
              r.und_3 ,
              r.und_4 ,
              r.cant_1 ,
              r.cant_2 ,
              r.cant_3 ,
              r.cant_4 ,
              ifnull(t.id,0) as id,
              tt.id_pedido ,
              tt.id_referencia ,
              tt.cantidad ,
              tt.valor_unitario ,
              tt.costo_unitario ,
              tt.pcj_iva ,
              tt.pcj_descuento ,
              tt.und ,
              tt.can_und ,
              tt.sw,
              tt.id_bodega ,
              IFNULL((
                SELECT SUM(v.stock) 
                FROM v_stock_total v
                WHERE v.id_referencia = tt.id_referencia and v.mes=MONTH(CURRENT_DATE()) and v.ano =YEAR(CURRENT_DATE())
              ),0) AS total_stock,
              IFNULL(m.descripcion,'') as marca,
              IFNULL(( 
                SELECT SUM(td.cantidad/td.can_und) 
                FROM t_transacciones_detalle td 
                WHERE td.id_pedido = tt.id_pedido and 
                td.id_referencia = tt.id_referencia),0) AS ingresadas,
              IFNULL(( 
                SELECT SUM(td.cantidad/td.can_und) 
                FROM t_transacciones_detalle_tmp td 
                WHERE td.id_pedido = tt.id_pedido and 
                td.id_referencia = tt.id_referencia),0) AS temporales,
                0 as cantidad_desp,
                0 as cantidad2,
                0 as n_lotes_pedidos ,
                ifnull(i.porcentaje_icui,0)	 as icui,
                ifnull(r.impoconsumo,0) as impoconsumo,
                ifnull(r.ibua,0) as ibua,
                IFNULL((
                    SELECT ifnull(v.promedio,0)
                    FROM v_costo_promedio v
                    WHERE v.id_referencia = tt.id_referencia AND mes= MONTH(current_date) AND ano=YEAR(current_date)
                ),IFNULL((
                    SELECT costo_unitario
                    FROM t_transacciones_detalle
                    WHERE sw IN (5,6,7) AND id_referencia = tt.id_referencia
                    ORDER BY fecha_hora DESC
                    LIMIT 1
                ),r.costo_unitario)) AS promedio,

                IFNULL((
                    SELECT 
                       costo_unitario
                    FROM t_transacciones_detalle
                    WHERE sw = 1 AND id_referencia = tt.id_referencia
                    ORDER BY fecha_hora DESC
                    LIMIT 1
                ),r.costo_unitario) AS ultimo_costo
                
            from  t_transacciones_detalle_tmp tt
            inner join t_referencias r on r.id=tt.id_referencia
            left  join  t_pedidos_detalle t on t.id_referencia = tt.id_referencia AND t.id_pedido =$id_pedido 
            left join t_marcas m on m.id  = r.id_marca 
            left join t_icui i on i.id= r.id_icui   
            WHERE tt.id_transaccion=$id_transaccion AND t.id is null ";

    $datos = GenerarArray($sql, '');

    $arrayDatos = [];
    foreach ($datos as $fila) {
      $ean = GenerarArray("SELECT ean FROM t_referencias_ean WHERE id_referencia = " . (int)$fila['id_referencia']);
      $arrayEan = array_map(function ($filaEan) {
        return $filaEan['ean'];
      }, $ean);

      $pos = GenerarArray("SELECT 
      id_referencia,id,
      IFNULL(vmcto,'N/A') as vmcto,
      IFNULL(lote,'N/A') as lote, 
      (cantidad/can_und) as cantidad
      FROM t_transacciones_detalle_tmp
      WHERE id_referencia= " . (int)$fila['id_referencia'] . " and id_pedido = " . (int)$fila['id_pedido']);


      $arrayDatos[] = [
        'datos' => $fila,
        'ean' => $arrayEan,
        'posiciones' => $pos
      ];
    }
    echo json_encode($arrayDatos);

    break;
  case "add_nota_pedido":
    $nota = $_POST['notas'];
    $id = $_POST['id'];
    $sql = "update t_pedidos set notas = '$nota' where id = $id";
    if (Query($sql)) {
      echo json_encode(array(
        'error' => false
      ));
    } else {
      echo json_encode(array(
        'error' => true
      ));
    }
    break;
  case "upd_estado_pedido":
    $id = $_POST['id'];
    $estado = $_POST['estado'];
    $sql = "update t_pedidos set estado = '$estado' where id = $id";
    $numero = GenerarArray("select numero from t_pedidos where id=$id");
    $nombreUnico = "Pedido de venta numero " . $numero[0]["numero"] . " a las " . date('d-m-Y H:m:i');
    if (Query($sql)) {
      echo json_encode(array(
        'error' => false,
        'nombre_pdf' => $nombreUnico
      ));
    } else {
      echo json_encode(array(
        'error' => true
      ));
    }
    break;
  case "cargar-lotes":

    $id_referencia = (int)$_POST["id_referencia"];
    $bodega = (int)$_POST["bodega"];
    $id_pedido = isset($_POST["id_pedido"]) ? (int)$_POST["id_pedido"] : 0;
    $condicion_pedido = '';
    if ($id_pedido > 0) {
      $condicion_pedido = " AND d.id_pedido <> $id_pedido ";
    }
    // Sin pedido no hay unidades escaneadas: se evita la subconsulta por cada lote
    $sel_comp_scan = $id_pedido > 0
      ? "IFNULL(( SELECT
                    SUM(yy.cantidad)
                  FROM t_pedidos_lotes yy
                  WHERE  yy.id_referencia = s.id_referencia
                  AND yy.id_lote = s.id_lote 
                  AND yy.id_pedido = $id_pedido
                ),0)"
      : "0";

    $sql = "SELECT 
                s.id AS id_lote,
                s.lote,
                s.vencimiento,
                s.stock  AS stock,
                s.id_referencia,
                SUM(IFNULL(d.cantidad, 0)) AS comprometidas,
                $sel_comp_scan as comp_scan
            FROM v_referencias_stock_lotes s
            LEFT JOIN v_unidades_comprometidas d ON d.id_referencia = s.id_referencia
                                                                      AND d.id_lote = s.id
                                                                      AND d.id_bodega = s.id_bodega 
                                                                          
            WHERE s.id_bodega = $bodega AND s.id_referencia = $id_referencia
            GROUP BY
                s.id,
                s.lote,
                s.vencimiento,
                s.stock,
                s.id_referencia
        
            HAVING (s.stock - SUM(IFNULL(d.cantidad, 0)) > 0)
            ORDER BY
            s.vencimiento;
            ";

    $lotes = GenerarArray($sql, '');

    echo json_encode($lotes);
    break;
  case "flujo_pedido":
    $sql = "SELECT 
                s.id as id_doc,
                s.tipo as tipo_doc,
                s.numero as numero_doc,
                s.fecha_hora,
                upper(u.usuario) as usuario 
            from t_pedidos p 
            left join t_transacciones_detalle t on t.id_pedido  = p.id
            left join t_transacciones s on s.id =t.id_transaccion 
            left join t_usuarios u on u.id=s.id_usuario
            where 
                p.id = " . $_POST['id'] . "
            group by
                s.id,
                s.numero,
                s.fecha_hora,
                u.usuario";
    $datos = GenerarArray($sql, '');

    echo json_encode(array(
      'error' => false,
      'message' => 'Success',
      'data' => $datos
    ));
    break;
  case "condiciones_pago":
    $tipo = $_POST["tipo"] == '' || !isset($_POST["tipo"]) ? '' : $_POST["tipo"];

    if ($tipo == '') {
      $sql = " select * from t_condiciones_pago   order by dias";
    } else {
      $sql = " select * from t_condiciones_pago  where tipo_condicion='$tipo' order by dias";
    }
    //$sql =" select * from t_condiciones_pago  where tipo_condicion='$tipo' order by dias";
    $condiciones = GenerarArray($sql, '');
    echo json_encode($condiciones);
    break;
  case "update_encabezado_transaccion":
    $data = $_POST;

    try {
      //code...

      if (!isset($data["id"])) {
        echo json_encode(array(
          'ok' => false,
          'message' => 'No se envio el id de la transaccion'
        ));
        return;
      }

      $update = update('t_transacciones_tmp', $data, (int)$data["id"]);

      if (!$update) {
        echo json_encode(array(
          'ok' => false,
          'message' => $update
        ));
        return;
      }

      echo json_encode(array(
        'ok' => true,
        'message' => 'Se actualizaron correctamente los datos!'
      ));
    } catch (\Throwable $th) {
      //throw $th;
      echo json_encode(array(
        'ok' => false,
        'message' => $th->getMessage()
      ));
      return;
    }

    break;
  case "validar-vendedor":
    $vendedor = preg_replace('/[^0-9A-Za-z\-]/', '', $_POST["vendedor"] ?? '');
    $datos = $vendedor === '' ? [] : GenerarArray("select id from t_terceros where nit = '$vendedor' limit 1");

    echo json_encode(array(
      'valido' => count($datos) > 0 ? true : false
    ));
    break;
  case "i_respuestas_dian":

    $id = $_POST["id"];
    $respDian = $_POST["respDian"];

    $documento = GenerarArray("select tipo,numero from t_transacciones where id=$id");


    $data = array(
      'tipo' => $documento[0]['tipo'],
      'numero' => $documento[0]['numero'],
      'cufe' => $respDian['UUID'],
      'XmlFileName' => $respDian['XmlFileName'],
      'fechaEnvio' => $respDian['fechaEnvio'],
      'fechaValidacion' => $respDian['fechaValidacion']
    );

    $id = insertReturnId('t_respuestas_dian', $data, true);

    if ($id > 0) {
      echo json_encode(array(
        'ok' => true,
        '' => 'Se inserto correctamente el registro',
        'id' => $id
      ));
    } else {
      echo json_encode(array(
        'ok' => false,
        '' => 'Error al insertar el registro',
        'id' => 0
      ));
    }
    break;
  case "datos-facturas":
    $id = $_POST["id"];

    $empresa = GenerarArray("select 
                        upper(t.razon_comercial) as razon_comercial,
                        upper(t.representante_legal) as representante_legal,
                        t.nit,
                        t.nit_digito,
                        upper(t.correo) as correo,
                        t.direccion ,
                        p.descripcion as nombre_pais,
                        d.departamento  as nombre_dpto,
                        m.municipio  as nombre_mpio,
	                    t.telefono,
                        t.logo,
                        rg.regimen
                    from t_empresa t
                    inner join t_paises p on p.pais = t.codigo_pais 
                    inner join t_departamentos d on d.codigo_dpto = t.codigo_dpto  and d.codigo_pais  = t.codigo_pais 
                    inner join t_municipios m on m.codigo_pais = t.codigo_pais and m.codigo_dpto  = t.codigo_dpto and m.codigo =t.codigo_mpio
                    inner join t_regimenes rg on rg.id=t.id_regimen");

    $tercero = GenerarArray(" select
                        t.id as id_tercero,
                        t.nit,
                        t.nit_digito,
                        CONCAT(t.primer_nombre,' ',t.segundo_nombre,' ',t.primer_apellido,' ',t.segundo_apellido) as nombres,
                        t.razon_comercial,
                        d.id_bodega,
                        d.notas,
                        d.id as id_transaccion,
                        d.valor_total,
                        d.numero,
                        d.tipo,
                        UPPER(u.usuario) as usuario,
                        UPPER(CONCAT(u.nombres,' ',u.apellidos)) as nombres_usuario, 
                        d.fecha_hora,
                        p.descripcion as nombre_pais,
                        s.departamento  as nombre_dpto,
                        m.municipio  as nombre_mpio,
                        upper(r.direccion) as direccion,
                        r.telefono,
                        a.descripcion as desc_bodega,
                        cp.descripcion as condicion,
                        ifnull(dd.cufe,'') as cufe,
                        ifnull(dd.QRCode,'') as QRCode,
                        ifnull(dd.fechaEnvio,'') as fecha_envio,
                        CONCAT(vv.primer_nombre,' ',vv.segundo_nombre,' ',vv.primer_apellido,' ',vv.segundo_apellido) as vendedor,
                        xx.leyenda1,
                        xx.leyenda2
                    from t_transacciones d
                    inner join t_terceros           t on t.id = d.id_tercero 
                    inner join t_terceros_direcciones r on r.id = d.id_tercero_dir
                    inner join t_tipos_documentos   xx on xx.id=d.id_tipo_documento
                    inner join t_usuarios           u on u.id=d.id_usuario
                    inner join t_paises             p on p.pais = r.codigo_pais 
                    inner join t_departamentos s on s.codigo_dpto = r.codigo_dpto  and s.codigo_pais  = r.codigo_pais 
                    inner join t_municipios           m on m.codigo_pais = r.codigo_pais 
                    and m.codigo_dpto  = r.codigo_dpto 
                    and m.codigo       = r.codigo_mpio
                    inner join t_almacenes           a on a.id  = d.id_bodega 
                    left  join t_terceros            vv on vv.nit = d.vendedor 
                    left  join t_condiciones_pago   cp on cp.id =d.id_condicion 
                    left join t_respuestas_dian     dd on dd.numero=d.numero and dd.tipo=d.tipo
                    where 
                        d.id = $id");

    $detalle = array();

    $detalle = GenerarArray("
      
        select 
           r.codigo,
           r.descripcion as producto,
           cast(sum(d.cantidad/d.can_und) as decimal(19,0)) as cantidad,
           round(d.valor_unitario*d.can_und,0) as precio,
           cast(d.pcj_iva as decimal(19,0)) as pcj_iva,
           cast(d.pcj_descuento as decimal(12,0)) as pcj_descuento,
           round( sum( (((d.valor_unitario*(1-(d.pcj_descuento/100)))*((d.pcj_iva/100)+1)))*d.cantidad) ,0) as total
        from t_transacciones_detalle d 
        inner join t_referencias r on r.id=d.id_referencia
        where d.id_transaccion=$id

        group by
        r.codigo,
        d.valor_unitario,
        d.can_und,
        d.pcj_iva,
        d.pcj_descuento,
        r.descripcion 

      ");

    $pago = GenerarArray("
        select 
          m.descripcion as medio ,
          cast(sum(p.valor) as decimal(19,0)) as valor
        from t_transacciones_pago p 
        inner join t_medios_pagos m on m.id= p.id_medio_pago
        where p.numero=" . $tercero[0]["numero"] . " and p.tipo='" . $tercero[0]["tipo"] . "'
        group by
        m.descripcion 
      ");

    echo json_encode(array(
      'encabezado' => array(
        'empresa' => $empresa,
        'tercero' => $tercero
      ),
      'detalle' => $detalle,
      'pagos' => $pago
    ));

    break;
  /*case "bodegas-usuarios":

    if (empty($_SESSION["Ses_id"])) {
      echo json_encode(array(
        'message' => 'No se detecto un usuario logueado'
      ));
      return;
    }
    $id = $_SESSION["Ses_id"];

     $sql = "select bodegas from t_usuarios where id=$id";

    $bodegas = GenerarArray($sql);

    if (count($bodegas) == 0) {
      echo json_encode([]);
      return;
    }

    foreach ($bodegas as $key) {
      $bodegasArray = json_decode($key['bodegas'], true);

      // Ordenar por id
      usort($bodegasArray, function ($a, $b) {
        return $a['id'] - $b['id'];
      });

      echo json_encode($bodegasArray);
    }
    break;*/
  case "bodegas-usuarios":

    if (empty($_SESSION["Ses_id"])) {
      echo json_encode(array(
        'message' => 'No se detecto un usuario logueado'
      ));
      return;
    }

    $id = (int) $_SESSION["Ses_id"];

    $sql = "SELECT 
                b.id,
                b.almacen AS bodega,
                b.descripcion
            FROM t_almacenes b
            WHERE EXISTS (
                SELECT 1
                FROM t_usuarios u
                WHERE u.id = $id
                  AND JSON_CONTAINS(
                      u.bodegas,
                      JSON_OBJECT('id', CAST(b.id AS CHAR))
                  )
            )
            ORDER BY b.id ASC";

    $bodegasArray = GenerarArray($sql);

    if (count($bodegasArray) == 0) {
      echo json_encode([]);
      return;
    }

    echo json_encode($bodegasArray);

    break;

  case "datos_empresa":
    $sql = "select * from t_empresa";
    echo json_encode(GenerarArray($sql));
    break;
  //valida el cierre del mes
  case "validar-cierre-mes":
    //valido si hay movimientos de inventarios 
    $sql = "
          select * from t_referencias_mov limit 1
      ";
    $mov = GenerarArray($sql);
    if (count($mov) > 0) {
      //valido la tabla de cierre
      $mes = date("m");
      $anio = date("Y");

      if ($mes == 1) {
        $mes = 12;
        $anio = $anio - 1;
      } else {
        $mes = $mes - 1;
      }

      $sqlc = "select * from t_cierre_mes where mes=$mes and anio=$anio limit 1 ";
      $result = GenerarArray($sqlc);

      if (count($result) > 0) {
        echo json_encode(array('ok' => true, 'message' => 'Cierre hecho para el mes actual'));
      } else {
        echo json_encode(array('ok' => false, 'message' => 'No se ha hecho el cierre', 'sql' => $sqlc));
      }
    } else {
      //devuelvo true
      echo json_encode(array('ok' => true, 'message' => 'Cierre hecho para el mes actual'));
    }
    break;
  case "valor-caja-turno":
    $id_usuario = $_SESSION["Ses_id"];
    $id_bodega  = $_SESSION["Ses_id_bodega"];

    /*$sql = "SELECT SUM(saldo) AS saldo
              FROM (
                  SELECT IFNULL(SUM(valor), 0) AS saldo
                  FROM t_transacciones_pago p
                  INNER JOIN t_transacciones t ON t.numero = p.numero AND t.tipo = p.tipo
                  WHERE p.fecha_hora >= (
                            SELECT fecha_apertura
                            FROM t_aperturas_cajas a
                            WHERE fecha_cierre IS NULL 
                              AND a.id_usuario = $id_usuario
                              AND a.id_bodega = " . $_SESSION[ "Ses_id_bodega" ] . "
                        )
                    AND p.id_usuario = $id_usuario
                    AND p.id_medio_pago = 1
                    AND t.id_bodega = " . $_SESSION[ "Ses_id_bodega" ] . "
                  UNION ALL
                  SELECT IFNULL(base, 0) AS saldo
                  FROM t_aperturas_cajas
                  WHERE fecha_cierre IS NULL 
                    AND id_usuario = $id_usuario
                    AND id_bodega = " . $_SESSION[ "Ses_id_bodega" ] . "
              ) AS t ";*/
    $sql = "SELECT SUM(saldo) AS saldo
                FROM (
                  -- Ingresos de efectivo por venta pos 
                    SELECT IFNULL(SUM(p.valor),0) AS saldo
                    FROM t_transacciones_pago p
                    INNER JOIN t_transacciones t 
                        ON t.numero = p.numero 
                        AND t.tipo = p.tipo
                        AND IFNULL(t.anulado,0)  = 0
                    INNER JOIN (
                        SELECT fecha_apertura, base
                        FROM t_aperturas_cajas
                        WHERE fecha_cierre IS NULL
                        AND id_usuario = $id_usuario
                        AND id_bodega = $id_bodega
                        LIMIT 1
                    ) a
                    WHERE p.fecha_hora >= a.fecha_apertura
                      AND p.id_usuario = $id_usuario
                      AND p.id_medio_pago = 1
                      AND t.id_bodega = $id_bodega
                    UNION ALL
                  -- Saldo base al momento de abrir la caja
                    SELECT IFNULL(a.base,0)
                    FROM (
                        SELECT base
                        FROM t_aperturas_cajas
                        WHERE fecha_cierre IS NULL
                        AND id_usuario = $id_usuario  
                        AND id_bodega = $id_bodega
                        LIMIT 1
                    ) a
                   /* UNION ALL
                    -- Ingresos y egresos de efectivo
                    SELECT IFNULL(SUM(
                        CASE 
                            WHEN t.sw = 10 THEN t.valor_total
                            ELSE -t.valor_total
                        END
                    ),0)
                    FROM t_transacciones t
                    INNER JOIN (
                        SELECT fecha_apertura
                        FROM t_aperturas_cajas
                        WHERE fecha_cierre IS NULL
                        AND id_usuario = $id_usuario
                        AND id_bodega = $id_bodega
                        LIMIT 1
                    ) a
                    WHERE t.sw IN (9,10)
                      AND t.fecha_hora >= a.fecha_apertura
                      AND t.id_usuario = $id_usuario
                      AND t.id_bodega = $id_bodega
                      AND IFNULL(t.anulado,0)  = 0*/ 
                ) x;";
    $pagos = GenerarArray($sql, '');
    echo json_encode($pagos);
    break;
  case 'contabilizar-documento':
    // Validar id_transaccion
    if (empty($_POST["id_transaccion"])) {
      echo json_encode([
        'success' => false,
        'message' => 'Es necesario enviar el ID de la transacción.'
      ]);
      return;
    }

    $id = (int)$_POST["id_transaccion"];

    // Consultar la transacción
    $q = GenerarArray("SELECT sw FROM t_transacciones WHERE id = $id");

    if (!$q || count($q) === 0) {
      echo json_encode([
        'success' => false,
        'message' => 'La transacción no existe o no fue encontrada.'
      ]);
      return;
    }

    $sw = (int)$q[0]['sw'];

    try {
      // Ejecutar el movimiento contable
      $res = generarMovimientoContable($sw, $id);
      echo json_encode($res);
    } catch (Exception $e) {
      echo json_encode([
        'success' => false,
        'message' => 'Error al contabilizar el documento: ' . $e->getMessage()
      ]);
    }

    break;
  case "FIFO":

    try {
      // ⛔ Iniciar transacción
      $conn->begin_transaction();

      $id_transaccion = (int)$_POST["id_transaccion"];

      $detalle_pedido = GenerarArray("
            SELECT t.*, ifnull(r.tipo,'P') as tipo_referencia 
            FROM t_transacciones_detalle_tmp t
            inner join t_referencias r on r.id = t.id_referencia 
            WHERE t.id_transaccion = $id_transaccion  
            AND IFNULL(t.cantidad, 0) > 0
        ");

      foreach ($detalle_pedido as $det) {

        $id_referencia = (int)$det["id_referencia"];
        $id_bodega = (int)$det["id_bodega"];
        $cantidad_restante = (float)$det["cantidad"];
        $tipo_referencia = $det['tipo_referencia'];

        // 🔹 Obtener lotes con stock disponible ordenados por vencimiento (FIFO)
        if ($tipo_referencia == 'P') {
          $stock_lote = GenerarArray("
                    SELECT 
                        v.id_lote,
                        v.lote,
                        v.vencimiento,
                        v.stock
                    FROM v_referencias_stock_lotes v
                    WHERE v.id_referencia = $id_referencia
                    AND v.id_bodega = $id_bodega
                    AND v.stock > 0
                    ORDER BY v.vencimiento ASC
                ");

          if (empty($stock_lote)) {
            throw new Exception("No hay stock disponible para la referencia $id_referencia");
          }
          // 🔹 Eliminar registro original UNA sola vez
          $conn->query("
                DELETE FROM t_transacciones_detalle_tmp 
                WHERE id_transaccion = $id_transaccion
                AND id_referencia = $id_referencia
            ");

          foreach ($stock_lote as $lote) {

            if ($cantidad_restante <= 0) {
              break;
            }

            $stock_disponible = (float)$lote['stock'];
            $cantidad_a_asignar = min($cantidad_restante, $stock_disponible);

            $detalle_transaccion = [
              'id_transaccion' => $id_transaccion,
              'id_referencia' => $id_referencia,
              'cantidad' => $cantidad_a_asignar,
              'valor_unitario' => $det["valor_unitario"],
              'costo_unitario' => $det["costo_unitario"],
              'id_lote' => $lote['id_lote'],
              'lote' => $lote['lote'],
              'vmcto' => $lote['vencimiento'],
              'pcj_iva' => $det["pcj_iva"],
              'pcj_descuento' => $det["pcj_descuento"],
              'und' => $det["und"],
              'can_und' => $det["can_und"],
              'sw' => 5,
              'id_bodega' => $id_bodega,
              'id_pedido' => 0,
              'impoconsumo' => $det["impoconsumo"],
              'ibua' => $det["ibua"],
              'icui' => $det["icui"]
            ];

            $id_detalle = insertReturnId(
              't_transacciones_detalle_tmp',
              $detalle_transaccion,
              true,
              $conn
            );

            if (!$id_detalle) {
              throw new Exception('Error al insertar detalle de transacción');
            }

            $cantidad_restante -= $cantidad_a_asignar;
          }

          // 🔴 Validar si no alcanzó el stock
          if ($cantidad_restante > 0) {
            throw new Exception("Stock insuficiente para la referencia $id_referencia");
          }
        }
      }

      //foreach ($detalle_pedido as $det) {
      // 4. Volcar documento
      $query = "CALL p_volcar_documento(?, @nuevoid)";
      $stmt = $conn->prepare($query);
      if (!$stmt) {
        throw new Exception("Error al preparar SP: " . $conn->error);
      }
      $stmt->bind_param("i", $id_transaccion);
      $stmt->execute();

      $result = $conn->query("SELECT @nuevoid AS nuevoid");
      if (!$result) {
        throw new Exception("Error al recuperar ID de volcamiento");
      }

      $nuevoid = $result->fetch_assoc();
      if (intval($nuevoid['nuevoid']) <= 0) {
        throw new Exception("Volcado fallido");
      }

      // ✅ Si todo va bien, hacemos commit
      $conn->commit();

      echo json_encode([
        'ok' => true,
        'message' => 'Se insertó correctamente el registro',
        'id' => $nuevoid['nuevoid']
      ]);
    } catch (Exception $e) {
      // ⛔ Si hay error, hacemos rollback
      $conn->rollback();

      echo json_encode([
        'ok' => false,
        'message' => 'Transacción fallida: ' . $e->getMessage()
      ]);
    }


    break;
  case "guardar-factura-pedido":
    $id_usuario = $_SESSION["Ses_id"];
    $id_pedido = intval($_POST["id_pedido"]);
    $id_tipo_transaccion = $_POST["id_tipo_transaccion"];


    try {
      // ⛔ Iniciar transacción
      $conn->begin_transaction();
      // 1. Obtener detalle del pedido
      $detalle_pedido = GenerarArray("
              SELECT 
               *
              FROM t_pedidos_detalle
              WHERE id_pedido = $id_pedido AND IFNULL(cantidad2, 0) > 0
          ");

      if (count($detalle_pedido) == 0) {
        throw new Exception('No se encontró detalle del pedido');
      }

      // 2. Traer encabezado del pedido
      $sql = "
              SELECT 
              *, 
              DATE_ADD(CURRENT_DATE(), INTERVAL c.dias DAY) AS vencimiento,
              (ifnull((
                select 
                  round(sum( ((valor_unitario*(1-(pcj_descuento/100)))*((pcj_iva/100)+1))*cantidad2  ),0)
                from t_pedidos_detalle l
                where l.id_pedido =p.id
              ),0)) as total_desp,
              ( ifnull((
                select 
                  round(sum( ((valor_unitario*(1-(pcj_descuento/100)))*((pcj_iva/100)))*cantidad2  ),0)
                from t_pedidos_detalle l
                where l.id_pedido =p.id
              ),0)) as iva_desp,
              ( ifnull((
                select 
                  round(sum( (valor_unitario*(pcj_descuento/100))*cantidad2 ),0)
                from t_pedidos_detalle l
                where l.id_pedido =p.id
              ),0)) as descuento_desp
              FROM t_pedidos p
              INNER JOIN t_condiciones_pago c ON c.id = p.id_condicion
              WHERE p.id = $id_pedido
          ";
      $t_pedidos = GenerarArray($sql);

      $numero = numeroSiguiente($id_tipo_transaccion, 't_transacciones_tmp');

      $encabezado = [
        'numero' => $numero,
        'id_tipo_documento' => $id_tipo_transaccion,
        'id_usuario' => $id_usuario,
        'id_tercero' => $t_pedidos[0]["id_tercero"],
        'id_bodega' => $t_pedidos[0]["id_bodega"],
        'notas' => $t_pedidos[0]["notas"],
        'valor_total' => $t_pedidos[0]["total_desp"],
        'sw' => 5,
        'id_tercero_dir' => $t_pedidos[0]["id_tercero_dir"],
        'id_pedido' => $id_pedido,
        'id_condicion' => $t_pedidos[0]["id_condicion"],
        'lista' => $t_pedidos[0]["lista_precios"],
        'iva' => $t_pedidos[0]["iva_desp"],
        'descuento' => $t_pedidos[0]["descuento_desp"],
        'zona_ventas' => $t_pedidos[0]["zona_ventas"],
        'zona_descripcion' => $t_pedidos[0]["zona_descripcion"],
        'vendedor' => $t_pedidos[0]["vendedor"],
        'fecha' => date("Y-m-d"),
        'documento' => 'PED-' . $t_pedidos[0]["numero"],
        'ibua' => 0,
        'icui' => 0,
        'impoconsumo' => 0,
        'valor_aplicado' => 0,
        'vencimiento' => $t_pedidos[0]["vencimiento"]
      ];

      $id_transaccion = insertReturnId('t_transacciones_tmp', $encabezado, true, $conn);

      if ($id_transaccion <= 0) {
        throw new Exception('Error al insertar encabezado de transacción');
      }

      // 3. Insertar detalle
      $conf = $_SESSION["Ses_confSys"]['confGeneral'];
      $maneja_fecha = $conf[0]['ManejaFechaLote'] == 'N';

      if ($maneja_fecha) {

        foreach ($detalle_pedido as $key) {
          $detalle_transaccion = [
            'id_transaccion' => $id_transaccion,
            'id_referencia' => $key["id_referencia"],
            'cantidad' => $key["cantidad2"],
            'valor_unitario' => $key["valor_unitario"],
            'costo_unitario' => $key["costo_unitario"],
            'id_lote' => 0,
            'pcj_iva' => $key["pcj_iva"],
            'pcj_descuento' => $key["pcj_descuento"],
            'und' => $key["und"],
            'can_und' => $key["can_und"],
            'sw' => 5,
            'id_bodega' => $key["id_bodega"],
            'id_pedido' => $id_pedido,
            'impoconsumo' => $key["impoconsumo"],
            'ibua' => $key["ibua"],
            'icui' => $key["icui"]
          ];

          $id_detalle = insertReturnId('t_transacciones_detalle_tmp', $detalle_transaccion, true, $conn);

          if (!$id_detalle) {
            throw new Exception('Error al insertar detalle de transacción');
          }
        }
      } else {

        // valido si maneja fiho 
        if ($_SESSION["Ses_confSys"]["confGeneral"][4]["ManejaFIFO"] == 'S') {
          //FIFO

          foreach ($detalle_pedido as $det) {
            $stock_lote = GenerarArray("
                          select 
                          v.id_referencia,
                          v.id_lote,
                          v.vencimiento,
                          v.lote,
                          v.stock
                          from v_referencias_stock_lotes v
                          where v.id_referencia=" . $det["id_referencia"] . "
                          and v.id_bodega = " . $det["id_bodega"] . "
                          ORDER  BY
                          id_referencia,
                          vencimiento
                      
                      ");

            $cantidad_agregada = 0;

            $cantidad_restante = $det["cantidad2"];

            if (count($stock_lote) > 0) {

              foreach ($stock_lote as $lote) {

                $stock_disponible = $lote['stock'];
                $lote_ = $lote['lote'];
                $vencimiento = $lote["vencimiento"];
                $id_lote = $lote['id_lote'];


                // Verificar cuántos lotes podemos tomar para cumplir con el pedido
                if ($cantidad_restante > 0) {
                  $cantidad_a_asignar = min($cantidad_restante, $stock_disponible);
                  $cantidad_restante -= $cantidad_a_asignar;

                  $detalle_transaccion = [
                    'id_transaccion' => $id_transaccion,
                    'id_referencia' => $det["id_referencia"],
                    'cantidad' => $cantidad_a_asignar,
                    'valor_unitario' => $det["valor_unitario"],
                    'costo_unitario' => $det["costo_unitario"],
                    'id_lote' => $id_lote,
                    'lote' => $lote_,
                    'vmcto' => $vencimiento,
                    'pcj_iva' => $det["pcj_iva"],
                    'pcj_descuento' => $det["pcj_descuento"],
                    'und' => $det["und"],
                    'can_und' => $det["can_und"],
                    'sw' => 5,
                    'id_bodega' => $det["id_bodega"],
                    'id_pedido' => $id_pedido,
                    'impoconsumo' => $det["impoconsumo"],
                    'ibua' => $det["ibua"],
                    'icui' => $det["icui"]
                  ];

                  $id_detalle = insertReturnId('t_transacciones_detalle_tmp', $detalle_transaccion, true, $conn);

                  if (!$id_detalle) {
                    throw new Exception('Error al insertar detalle de transacción');
                  }
                  // Si ya hemos asignado toda la cantidad, salimos del bucle
                  if ($cantidad_restante <= 0) {
                    break;
                  }
                }
              } // foreach lotes 

            } else {
            }
          } //foreach ($detalle_pedido as $det) {

        } else {
          // inserto basado en los lotes de t_pedidos_lotes 
          $pedidos_lotes = GenerarArray("
                  select 
                   l.*
                  from t_pedidos_lotes l
                  where l.id_pedido = $id_pedido
                  order by
                  l.id_detalle_pedido
                ");


          foreach ($detalle_pedido as $det) {
            # code...
            $id_detalle_pedido = $det["id"];

            // echo  $id_detalle_pedido."<br>";
            // Filtrar los lotes correspondientes a este producto
            $pedidos_lotes_filter = array_filter($pedidos_lotes, function ($lote) use ($id_detalle_pedido) {
              return $lote["id_detalle_pedido"] == $id_detalle_pedido;
            });


            foreach ($pedidos_lotes_filter as $pl) {
              $detalle_transaccion = array(
                'id_transaccion' => $id_transaccion,
                'id_referencia' => $det["id_referencia"],
                'cantidad' => $pl["cantidad"],
                'valor_unitario' => $det["valor_unitario"],
                'costo_unitario' => $det["costo_unitario"],
                'id_lote' => $pl["id_lote"],
                'lote' => $pl["lote"],
                'vmcto' => $pl["vencimiento"],
                'pcj_iva' => $det["pcj_iva"],
                'pcj_descuento' => $det["pcj_descuento"],
                'und' => $det["und"],
                'can_und' => $det["can_und"],
                'sw' => 5,
                'id_bodega' => $det["id_bodega"],
                'id_pedido' => $id_pedido,
                'impoconsumo' => $det["impoconsumo"],
                'ibua' => $det["ibua"],
                'icui' => $det["icui"]
              );

              $id_detalle = insertReturnId('t_transacciones_detalle_tmp', $detalle_transaccion, true, $conn);

              if (!$id_detalle) {
                throw new Exception('Error al insertar detalle de transacción');
              }
            } //foreach($pedidos_lotes as $pl){
            //elimino los t_pedidos_lotes 
            $delete_pedidos_lotes = $conn->query("delete from t_pedidos_lotes where id_detalle_pedido=$id_detalle_pedido");
          } //foreach foreach ($detalle_pedido as $det) {

        }
      }


      // 4. Volcar documento
      $query = "CALL p_volcar_documento(?, @nuevoid)";
      $stmt = $conn->prepare($query);
      if (!$stmt) {
        throw new Exception("Error al preparar SP: " . $conn->error);
      }
      $stmt->bind_param("i", $id_transaccion);
      $stmt->execute();

      $result = $conn->query("SELECT @nuevoid AS nuevoid");
      if (!$result) {
        throw new Exception("Error al recuperar ID de volcamiento");
      }

      $nuevoid = $result->fetch_assoc();
      if (intval($nuevoid['nuevoid']) <= 0) {
        throw new Exception("Volcado fallido");
      }

      // 5. Actualizar cantidades despachadas
      $update_ = Query("
              UPDATE t_pedidos_detalle pd
              JOIN (
                  SELECT 
                      id_pedido,
                      id_referencia,
                      SUM(cantidad) AS total_despachado
                  FROM t_transacciones_detalle
                  WHERE id_pedido = $id_pedido
                  GROUP BY id_pedido, id_referencia
              ) AS t ON pd.id_pedido = t.id_pedido AND pd.id_referencia = t.id_referencia
              SET pd.cantidad_desp = t.total_despachado
              WHERE pd.id_pedido = $id_pedido
          ");

      if (!$update_) {
        throw new Exception("Error al actualizar cantidad despachada");
      }

      // 6. Reiniciar cantidad2
      $update__ = Query("UPDATE t_pedidos_detalle SET cantidad2 = 0 WHERE id_pedido = $id_pedido");
      if (!$update__) {
        throw new Exception("Error al resetear cantidad2");
      }

      // ✅ Si todo va bien, hacemos commit
      $conn->commit();

      echo json_encode([
        'ok' => true,
        'message' => 'Se insertó correctamente el registro',
        'id' => $nuevoid['nuevoid']
      ]);
    } catch (Exception $e) {
      // ⛔ Si hay error, hacemos rollback
      $conn->rollback();

      echo json_encode([
        'ok' => false,
        'message' => 'Transacción fallida: ' . $e->getMessage()
      ]);
    }

    break;
  case "cambiar-preferencias":
    $usuario = $_SESSION["Ses_id"];
    $id_modulo = $_POST["id_modulo"];
    $clave = '';
    if (isset($_POST["CheckearBarras"])) {
      $clave = 'CheckearBarras';
      $valor = $_POST["CheckearBarras"];
    }
    if (isset($_POST["SoloProductoSock"])) {
      $clave = 'SoloProductoSock';
      $valor = $_POST["SoloProductoSock"];
    }
    if (isset($_POST["LimiteRegistros"])) {
      $clave = 'LimiteRegistros';
      $valor = $_POST["LimiteRegistros"];
    }

    $sql = GenerarArray("select * from t_preferencias where id_usuario=$usuario and id_modulo=$id_modulo and clave='$clave'");

    if (count($sql) > 0) {
      $update = Query("UPDATE t_preferencias SET valor='$valor'
      WHERE clave='$clave' 
      and id_usuario =$usuario 
      and id_modulo=$id_modulo");
      if ($update) {
        echo json_encode(array('ok' => true));
      } else {
        echo json_encode(array('ok' => false));
      }
    } else {
      $insert = Query("insert into t_preferencias(id_usuario,id_modulo,clave,valor) values($usuario,$id_modulo,'$clave','$valor'); ");
      if ($insert) {
        echo json_encode(array('ok' => true));
      } else {
        echo json_encode(array('ok' => false));
      }
    }


    break;
  case "tercero-empresa":
    $sql = "select 
         t.id,
         t.razon_comercial,
         (
            select 
              id
            from t_terceros_direcciones 
            where id_tercero =t.id 
            limit 1
         ) as id_direccion,
          t.nit
         from t_terceros t where nit = (select nit from t_empresa)";
    echo json_encode(GenerarArray($sql));
    break;
  case "ultimo_costo":
    $id = $_POST["id"];
    $sql = "
      select 
            ifnull(max(t.costo_unitario),r.costo_unitario) as costo
      from t_referencias r 
      left join t_transacciones_detalle t on t.id_referencia=r.id  and  t.sw=1 
      where  r.id=$id
    ";
    $costo = GenerarArray($sql, '');
    echo json_encode($costo);
    break;
  case "actualizacionPrecios":

    $id_referencia = isset($_POST["id_referencia"]) ? intval($_POST["id_referencia"]) : 0;
    $id_transaccion = isset($_POST["id_transaccion"]) ? intval($_POST["id_transaccion"]) : 0;
    $listas = [];

    if (isset($_POST["listas"])) {
      $listas = json_decode($_POST["listas"], true);
      if (!is_array($listas)) {
        $listas = [];
      }
    }

    if (count($listas) == 0 && isset($_POST["tipo_lista"])) {
      $listas[] = array(
        'lista' => isset($_POST["lista"]) ? intval($_POST["lista"]) : 0,
        'tipo_lista' => trim($_POST["tipo_lista"]),
        'precio' => isset($_POST["precio"]) ? floatval($_POST["precio"]) : 0,
        'precio_2' => isset($_POST["precio_2"]) ? floatval($_POST["precio_2"]) : 0,
        'precio_3' => isset($_POST["precio_3"]) ? floatval($_POST["precio_3"]) : 0,
        'precio_4' => isset($_POST["precio_4"]) ? floatval($_POST["precio_4"]) : 0
      );
    }

    if ($id_referencia <= 0 || $id_transaccion <= 0 || count($listas) == 0) {
      echo json_encode(array('ok' => false, 'message' => 'Datos incompletos para guardar precios en recepción'));
      return;
    }

    Query("DELETE FROM t_precios_recepcion WHERE id_transaccion=$id_transaccion AND id_referencia=$id_referencia");

    $procesados = 0;

    foreach ($listas as $item) {
      $lista = isset($item['lista']) ? intval($item['lista']) : 0;
      $tipo_lista = isset($item['tipo_lista']) ? trim($item['tipo_lista']) : '';

      if ($lista <= 0 || $tipo_lista == '') {
        continue;
      }

      $id_ = insertReturnId('t_precios_recepcion', array(
        'id_transaccion' => $id_transaccion,
        'id_referencia' => $id_referencia,
        'lista' => $lista,
        'tipo_lista' => $tipo_lista,
        'precio' => isset($item['precio']) ? floatval($item['precio']) : 0,
        'precio_2' => isset($item['precio_2']) ? floatval($item['precio_2']) : 0,
        'precio_3' => isset($item['precio_3']) ? floatval($item['precio_3']) : 0,
        'precio_4' => isset($item['precio_4']) ? floatval($item['precio_4']) : 0
      ), true);

      if ($id_ > 0) {
        $procesados++;
      }
    }

    echo json_encode(array(
      'ok' => $procesados > 0,
      'procesados' => $procesados,
      'message' => $procesados > 0 ? 'Precios temporales guardados correctamente' : 'No se encontraron listas válidas para guardar'
    ));
    break;
  case "volcar_documento_traslado":
    try {
      $id_transaccion   = $_POST["id_transaccion"];
      $traslado_directo = $_POST["traslado_directo"];
      $id_bodega        = $_SESSION["Ses_id_bodega"];
      $reg_id_bodega_destino =  GenerarArray("SELECT id_bodega_destino FROM t_transacciones_tmp WHERE id = $id_transaccion");

      if (
        $reg_id_bodega_destino[0]['id_bodega_destino'] == 0
        || $reg_id_bodega_destino[0]['id_bodega_destino'] == null
      ) {
        echo json_encode(array(
          'ok' => false,
          'message' => 'La transacción no tiene bodega de destino asignada'
        ));
        return;
      }

      $id_bodega_destino = $reg_id_bodega_destino[0]['id_bodega_destino'];

      // valido que la bodega de sdestino y salida no sean iguales
      $bog_salida = GenerarArray("SELECT id_bodega FROM t_transacciones_tmp WHERE id=$id_transaccion");
      $id_bodega_salida = $bog_salida[0]["id_bodega"];


      if ($id_bodega_salida == $id_bodega_destino) {
        echo json_encode(array(
          'ok' => false,
          'message' => 'La bodega de destino no puede ser igual a la bodega de salida'
        ));
        return;
      }


      $sql_validacion = " SELECT
                              d.sw,
                              d.id_referencia,
                              r.codigo,
                              r.descripcion,
                              d.cantidad AS cant_solicitada,
                              l.stock AS stock_actual,
                              l.stock - d.cantidad AS diferencia
                          FROM t_transacciones_detalle_tmp d
                          INNER JOIN t_referencias r on r.id = d.id_referencia and r.tipo  = 'P' 
                          INNER JOIN v_referencias_stock_lotes l 
                              ON l.id_referencia = d.id_referencia
                              AND l.id_bodega = d.id_bodega
                              AND l.id_lote = d.id_lote
                          WHERE d.id_transaccion = ? AND d.sw IN (6)";
      //Se realiza esta validacion de stock solo para las salidas de inventario, es decir sw 5:ventas y 6:salidas de inventario

      $stmt_val = $conn->prepare($sql_validacion);
      $stmt_val->bind_param("i", $id_transaccion);
      $stmt_val->execute();
      $result_val = $stmt_val->get_result();

      $errores_stock = [];

      while ($row = $result_val->fetch_assoc()) {

        if ($row["diferencia"] < 0) {
          $errores_stock[] = [
            "codigo" => $row["codigo"],
            "producto" => $row["descripcion"],
            "stock_actual" => $row["stock_actual"],
            "cant_solicitada" => $row["cant_solicitada"]
          ];
        }
      }

      if (count($errores_stock) > 0) {

        echo json_encode([
          "ok" => false,
          "message" => "No hay stock suficiente para algunos productos",
          "productos" => $errores_stock
        ]);
        return;
      }


      if ($traslado_directo == 'S') {
        $sql = "
          insert into t_transacciones_detalle_tmp(
            id_transaccion, 
            id_referencia, 
            cantidad, 
            valor_unitario, 
            costo_unitario, 
            id_lote, 
            pcj_iva, 
            pcj_descuento, 
            und, 
            can_und, 
            vmcto, 
            lote,
            sw,
            id_bodega,
            id_pedido,
            impoconsumo,
            ibua,
            icui,
            id_transaccion_ref
          )
          select 
            id_transaccion, 
            id_referencia, 
            cantidad, 
            valor_unitario, 
            costo_unitario, 
            id_lote, 
            pcj_iva, 
            pcj_descuento, 
            und, 
            can_und, 
            vmcto, 
            lote,
            2,
            $id_bodega_destino, 
            id_pedido,
            impoconsumo,
            ibua,
            icui,
            id_transaccion_ref
          from t_transacciones_detalle_tmp
          where id_transaccion=$id_transaccion
        ";

        $insert = $conn->query($sql);

        if (!$insert) {
          echo json_encode(array(
            'ok' => false,
            'message' => 'Error al volcar el documento'
          ));
          return;
        }
      }
      //llamo al volcado 

      $query = "CALL p_volcar_documento(?,@nuevoid)";

      $stmt = $conn->prepare($query);

      if (!$stmt) {
        Query("DELETE FROM t_transacciones_detalle_tmp WHERE id_transaccion=$id_transaccion AND sw=2 ");
        echo json_encode(array('ok' => false, 'message' => $conn->error, 'id' => 0));
        return;
      }

      $stmt->bind_param("i", $id_transaccion);
      $stmt->execute();

      if (!$stmt) {
        Query("DELETE FROM t_transacciones_detalle_tmp WHERE id_transaccion=$id_transaccion AND sw=2 ");
        echo json_encode(array('ok' => false, 'message' => $conn->error, 'id' => 0));
        return;
      }


      $result = $conn->query("SELECT @nuevoid AS nuevoid");

      if (!$result) {
        Query("DELETE FROM t_transacciones_detalle_tmp WHERE id_transaccion=$id_transaccion AND sw=2 ");
        echo json_encode(array('ok' => false, 'message' => 'Error al insertar el registro', 'id' => 0));
        return;
      }

      $nuevoid = $result->fetch_assoc();

      if ($nuevoid['nuevoid'] > 0) {
        // valido si debeo ganerar trasnito o no  
        if ($traslado_directo != 'S') {
          //inserta en el transito 
          $sql = "INSERT INTO 
                      t_traslados_transito(id_bodega_destino,id_transacciones_detalle) 
                    SELECT $id_bodega_destino, id 
                    FROM t_transacciones_detalle
                    WHERE id_transaccion=" . $nuevoid['nuevoid'] . "
              ";
          $insert = $conn->query($sql);

          if (!$insert) {
            echo json_encode(array(
              'ok' => false,
              'message' => 'Error al volcar el documento'
            ));
            return;
          }
        }


        // INSERTO LA ALerta 
        $fields = array(
          'transaccion ' => 'traslados',
          'id_bodega ' => $id_bodega,
          'id_bodega_destino' => $reg_id_bodega_destino[0]['id_bodega_destino'],
          'id_usuario' => $_SESSION["Ses_id"],
          'id_transaccion ' => $nuevoid['nuevoid']
        );
        $insertAlert = insertReturnId('t_alertas ', $fields);

        echo json_encode(
          array(
            'ok' => true,
            'message' => 'Se inserto correctamente el registro',
            'id' => $nuevoid['nuevoid']
          )
        );
      } else {
        Query("DELETE FROM t_transacciones_detalle_tmp WHERE id_transaccion=$id_transaccion AND sw=2 ");
        echo json_encode(
          array(
            'ok' => false,
            'message' => 'Error al insertar el registro ',
            'id' => 0
          )
        );
      }
    } catch (\Throwable $th) {
      $del = Query("DELETE FROM t_transacciones_detalle_tmp WHERE id_transaccion=$id_transaccion AND sw=2 ");
      echo json_encode(array(
        'ok' => false,
        'message' => $th->getMessage()
      ));
    }

    break;
  case "delete-item-lotes-pedidos":
    $id = $_POST["id"];
    $id_pedido_detalle = $_POST["id_detalle_pedido"];
    $id_referencia = $_POST["id_referencia"];

    if ($id > 0 && $id_pedido_detalle > 0) {
      $sql = GenerarArray("select cantidad from t_pedidos_lotes where id=$id");
      $delete = Query("delete from t_pedidos_lotes where id=$id");

      if ($delete) {
        $del_det = Query("UPDATE t_pedidos_detalle SET cantidad2 =cantidad2 - " . $sql[0]["cantidad"] . " 
          WHERE id=$id_pedido_detalle and id_referencia = $id_referencia");
      }
    }
    $sql = GenerarArray("select count(*) as n from t_pedidos_lotes where id_detalle_pedido = $id_pedido_detalle and id_referencia = $id_referencia");
    echo json_encode([
      'ok' => true,
      'message' => 'Se actualizo correctamente el registro',
      'n_lotes' => ($sql[0]["n"])
    ]);


    break;

  case "listas-precios":
    $sql = "select * from t_listas_precios";
    $listas = GenerarArray($sql);
    echo json_encode($listas);
    break;
  case 'update-listas':
    $id = $_POST['id'];
    $desc = strtoupper($_POST['Descripcion']);
    $update = Query("UPDATE t_listas_precios set Descripcion = '$desc' where id = $id;");
    if ($update) {
      echo json_encode([
        'ok' => true,
        'error' => false,
        'message' => 'Se actualizo correctamente el registro'
      ]);
    } else {
      echo json_encode([
        'ok' => false,
        'error' => true,
        'message' => 'Error no se pudo actualizar el registo'
      ]);
    }
    break;
  case 'insert-listas':
    $id = $_POST['id'];
    $desc = strtoupper($_POST['Descripcion']);
    $update = Query("INSERT INTO t_listas_precios (Descripcion) value('$desc') ");
    if ($update) {
      echo json_encode([
        'ok' => true,
        'error' => false,
        'message' => 'Se cre� correctamente el registro'
      ]);
    } else {
      echo json_encode([
        'ok' => false,
        'error' => true,
        'message' => 'Error no se pudo crear el registo'
      ]);
    }
    break;
  case 'delete-listas':

    $id = intval($_POST['id']);

    $existeReferencias = GenerarArray(" SELECT COUNT(*) as total 
                                          FROM t_referencias_listas 
                                          WHERE lista = $id ");

    if ($existeReferencias && $existeReferencias[0]['total'] > 0) {
      echo json_encode([
        'ok' => false,
        'error' => true,
        'message' => 'No se puede eliminar, la lista esta asociada a referencias.'
      ]);
      return;
    }

    $existeTerceros = GenerarArray(" SELECT COUNT(*) as total 
                                      FROM t_terceros 
                                      WHERE lista_precios = $id");

    if ($existeTerceros && $existeTerceros[0]['total'] > 0) {
      echo json_encode([
        'ok' => false,
        'error' => true,
        'message' => 'No se puede eliminar, la lista esta asociada a terceros.'
      ]);
      break;
    }

    $delete = Query("DELETE FROM t_listas_precios WHERE id = $id");

    if ($delete) {
      echo json_encode([
        'ok' => true,
        'error' => false,
        'message' => 'Se elimin� correctamente el registro'
      ]);
    } else {
      echo json_encode([
        'ok' => false,
        'error' => true,
        'message' => 'Error: no se pudo eliminar el registro'
      ]);
    }

    break;
  case "lista-almacen":
    $bodega = (int)$_SESSION["Ses_id_bodega"];
    $sql = "select lista_precios from t_almacenes where id = $bodega";
    $listas = GenerarArray($sql);
    echo json_encode($listas);
    break;
  case "get-rol":
    $rol = $_SESSION["Ses_roles_id"];
    echo json_encode(['rol' => $rol]);
    break;
  case "get-tipos-documentos-concep-ret":
    try {
      if (!isset($_POST["id_tipo_documento"])) {
        throw new Exception("El id del tipo de documento es requerido");
      }
      $id_tipo_documento = intval($_POST["id_tipo_documento"]);

      $sql = "SELECT *
              FROM t_tipos_documentos_concep_ret
              WHERE estado = 1
              AND id_tipo_documento = $id_tipo_documento";

      $data = GenerarArray($sql);

      echo json_encode([
        "ok" => true,
        "data" => $data
      ]);
    } catch (\Throwable $th) {
      echo json_encode([
        "ok" => false,
        "message" => $th->getMessage()
      ]);
    }
    break;
  case 's_control_credito':
    try {
      if (!isset($_POST["id_tercero"])) {
        throw new Exception("El id del tercero es requerido");
      }
      $id_tercero = intval($_POST["id_tercero"]);

      $sql = "SELECT * FROM v_control_credito WHERE id_tercero = $id_tercero";

      $data = GenerarArray($sql);

      echo json_encode([
        "ok" => true,
        "data" => $data
      ]);
    } catch (\Throwable $th) {
      echo json_encode([
        "ok" => false,
        "message" => $th->getMessage()
      ]);
    }
    break;
  case "exportar-factura":
    $id = (int)$_POST["id"];

    $SQL = "SELECT 
              CONCAT(t.tipo, '-', t.numero) as numero_factura,
              r.codigo,
              r.descripcion,
              te.nit,
              d.cantidad / d.can_und as cantidad,
              d.pcj_descuento,
              d.valor_unitario * d.can_und as valor_unitario,
              l.vencimiento,
              l.lote
            FROM t_transacciones t
            INNER JOIN t_transacciones_detalle d ON d.id_transaccion = t.id
            INNER JOIN t_referencias_lotes l ON l.id = d.id_lote
            INNER JOIN t_referencias r ON r.id = d.id_referencia
            INNER JOIN t_terceros te ON te.id = t.id_tercero
            WHERE t.id = ? ";
    $parametros = [];
    $parametros[] = array(
      'valor' => $id,
      'tipo' => 'i'
    );

    $datos = GenerarArrayPrepare($SQL, $parametros);
    echo json_encode([
      'ok' => true,
      'data' => $datos
    ]);
    break;
  // ------------------------------------------------------------
  // Guardar el token que manda push-notifications.js tras el login 
  // ------------------------------------------------------------
  case 'guardar_token_fcm':
    $id_usuario = $_SESSION['Ses_id'];
    $rol     = $_SESSION['Ses_roles_id'];
    $bodega  = $_SESSION['Ses_id_bodega'];
    $empresa    = $_SESSION["Ses_empresa"];
    $token      = $_POST['token'];
    $navegador  = $_POST['navegador'] ?? null;

    $conn = conectar();

    // INSERT ... ON DUPLICATE KEY UPDATE:
    // si el mismo token ya existía (mismo navegador reconectando),
    // actualiza el usuario dueño en vez de duplicar la fila.
    $stmt = $conn->prepare(
      "INSERT INTO t_fcm_tokens (id_usuario, token, navegador,empresa,rol,bodega)
             VALUES (?, ?, ? ,? ,? ,?)
             ON DUPLICATE KEY UPDATE 
             id_usuario = VALUES(id_usuario), 
             navegador = VALUES(navegador),
             empresa = VALUES(empresa),
             rol = VALUES(rol),
             bodega = VALUES(bodega)"
    );
    $stmt->bind_param('isssii', $id_usuario, $token, $navegador, $empresa, $rol, $bodega);
    $stmt->execute();
    $stmt->close();
    $_SESSION["toke-fcm"] = $token;

    echo json_encode(['ok' => true]);
    break;
}
