<?php
session_start();
require_once('bd.php');
require_once('funciones.php');
$conn = conectar();


switch ($_POST["op"]) {

  case "buscar-factura":
    $numero = $_POST["numero"];
    $tipo = $_POST["tipo"];
    $id_tercero = $_POST["id_tercero"];
    $fecha_inicial = $_POST["fecha_inicial"];
    $fecha_final = $_POST["fecha_final"];
    $bodega = $_POST["bodega"];

    $sql = "SELECT 
                d.id, 
                d.numero,
                d.tipo,
                t.nit,
                CONCAT(t.primer_apellido,' ',t.segundo_apellido,' ',t.primer_nombre,' ',t.segundo_nombre) as tratamiento,
                t.razon_comercial,
                ifnull(CONCAT(v.primer_apellido,' ',v.segundo_apellido,' ',v.primer_nombre,' ',v.segundo_nombre),'') as vendedor,
                d.fecha_hora,
                d.valor_total,
                d.id_bodega,
                z.codigo_zona,
                z.descripcion as des_zona_ventas,
                d.id_tercero_dir as id_direccion,
                (select 
                        direccion
                    from t_terceros_direcciones 
                    where id = d.id_tercero_dir
                limit 1 ) as direccion,
                t.id as id_tercero,
                (
                    SELECT 
                     count(*)
                    FROM t_transacciones_pago 
                    WHERE tipo = d.tipo AND numero = d.numero AND id_medio_pago =1 
                ) AS pago_efectivo,
                (
                    SELECT 
                     count(*)
                    FROM t_transacciones_pago 
                    WHERE tipo = d.tipo AND numero = d.numero AND id_medio_pago <>1                
                ) AS otro_medio,
                 d.vencimiento,
                 pp.descripcion as condicion,
                 case when d.vencimiento> d.fecha then 'CREDITO' else 'CONTADO' end  as tipo_factura,
                 d.vendedor as cod_vendedor
            from t_transacciones d 
            inner join t_terceros           t on t.id           = d.id_tercero
            left  join t_terceros           v on v.nit          = d.vendedor
            left  join t_condiciones_pago   pp on pp.id         = d.id_condicion
            left  join t_zonas_ventas       z on z.id           = t.id_zona_ventas
            where ifnull(d.anulado,'0')<>'1' 
            and d.sw=5 
            and d.valor_total = d.valor_aplicado

        ";

    if ($numero != '') {
      $sql .= " and d.numero = '$numero' ";
    }
    if ($tipo != '') {
      $sql .= " and d.tipo='$tipo'  ";
    }
    if ($id_tercero != '0') {
      $sql .= " and d.id_tercero=$id_tercero";
    }

    if ($fecha_inicial != '' && $fecha_final != '') {
      $sql .= " and CAST(d.fecha_hora AS date) BETWEEN CAST('$fecha_inicial' as date) and CAST('$fecha_final' as date)";
    }

    $sql .= "            
            order by
            d.fecha_hora 
            limit 50";


    $facturas = GenerarArray($sql);

    if (count($facturas) > 0 && $facturas[0]['id_bodega'] != $bodega) {
      echo  json_encode(array('ok' => false, 'message' => 'La factura de venta seleccionada no pertenece a la bodega seleccionada'));
      return;
    }

    echo json_encode(array('ok' => true, 'data' => $facturas));

    break;
  case "detalle-fact":
    $id_transaccion = $_POST["id_transaccion"];
    $sql = " SELECT 
                    d.id_transaccion,
                    d.id_referencia,
                    r.codigo,
                    r.descripcion,
                    ifnull(m.descripcion,'Sin asignar') as lab,
                    cast(d.cantidad/d.can_und as decimal(19,0)) as cantidad,
                    d.pcj_iva ,
                    d.pcj_descuento,
                    round((((d.valor_unitario*(1-(d.pcj_descuento/100))))*((d.pcj_iva/100)+1)) * d.can_und,0) as precio ,
                    cast(ifnull((
                        SELECT
                          sum(cantidad/can_und)
                        FROM t_transacciones_detalle 
                        WHERE 
                          id_transaccion_ref = d.id_transaccion and  
                          id_referencia = d.id_referencia  and 
                          sw = 4
                          and id_lote = d.id_lote
                    ),0) as decimal(19,0)) as cantidad_devuelta,
                    d.valor_unitario,
                    d.can_und,
                     d.costo_unitario ,
                     d.id_bodega ,
                     d.id_lote,
                     d.lote,
                     d.vmcto,
                     d.und,
                     ifnull(d.impoconsumo,0) as impoconsumo,
                     ifnull(d.ibua,0) as ibua,
                     ifnull(d.icui,0) as icui,
                     d.id_transaccion_ref ,
                    z.codigo_zona,
                    z.descripcion as des_zona_ventas,
                    tt.id_tercero_dir as id_direccion,
                     (select 
                        direccion
                    from t_terceros_direcciones 
                    where id=tt.id_tercero_dir
                    limit 1 ) as direccion,
                    tt.vendedor,
                    tt.vencimiento,
                    pp.descripcion as condicion,
                    ifnull(d.comision,0) as comision
                from t_transacciones_detalle d
                inner join t_transacciones tt on tt.id  =d.id_transaccion
                inner join t_terceros       t on t.id   =tt.id_tercero
                inner join t_condiciones_pago pp on pp.id=tt.id_condicion
                left  join t_zonas_ventas   z on z.codigo_zona=tt.zona_ventas

                inner join t_referencias r on r.id=d.id_referencia
                left  join t_marcas m on m.id=r.id_marca
                WHERE d.id_transaccion = $id_transaccion 
                order by
                r.descripcion ";

    $detalle = GenerarArray($sql);

    echo json_encode($detalle);
    break;
  case "guardar-devolucion":
    try {
      //Validar sesión
      if (!isset($_SESSION["Ses_id"])) {
        echo json_encode(array(
          'ok' => false,
          'message' => 'Sesión no válida',
          'id_transaccion' => 0
        ));
        return;
      }
      //code... 
      if (!isset($_POST["detalle"])) {
        echo json_encode(array(
          'ok' => false,
          'message' => 'No se envio el detalle la devolucion',
          'id_transaccion' => 0
        ));
        return;
      }
      $numero = 0;
      $detalle = json_decode($_POST["detalle"], true);

      if (isset($_POST["id_tipo_documento"])) {
        $numero = numeroSiguiente($_POST["id_tipo_documento"]);
      }

      if ($numero == 0) {
        echo json_encode(array(
          'ok' => false,
          'message' => 'El consecutivo no se pudo generar',
          'id_transaccion' => 0
        ));
        return;
      }

      $DataEncanezado = [
        'numero' => $numero,
        'id_tipo_documento' => $_POST["id_tipo_documento"],
        'id_tercero' => $_POST["id_tercero"],
        'id_bodega' => $_POST["id_bodega"],
        'notas' => $_POST["notas"],
        'valor_total' => $_POST["valor_total"],
        'iva' => $_POST["iva"],
        'descuento' => $_POST["descuento"],
        'sw' => $_POST["sw"],
        'id_tercero_dir' => $_POST["id_tercero_dir"],
        'id_condicion' => $_POST["id_condicion"],
        'zona_ventas' => $_POST["zona_ventas"],
        'zona_descripcion' => $_POST["zona_descripcion"],
        'id_usuario' => $_SESSION["Ses_id"],
        'fecha' => date("Y-m-d"),
        'vendedor' => $_POST["vendedor"],
        'documento' => $_POST["documento"],
        'vencimiento' => date("Y-m-d"),
        'valor_aplicado' => 0,
        'es_credito' => $_POST["es_credito"]
      ];

      $IdEnc = insertReturnId('t_transacciones_tmp', $DataEncanezado, true);

      if ($IdEnc > 0) {

        foreach ($detalle as $key) {

          $key["id_transaccion"] = $IdEnc;

          if (isset($_SESSION["Ses_confSys"]["confGeneral"][0]["ManejaFechaLote"]) && trim($_SESSION["Ses_confSys"]["confGeneral"][0]["ManejaFechaLote"]) == 'N') {
            unset($key["id_lote"]);
            unset($key["vmcto"]);
            unset($key["lote"]);
          }
          insertReturnId('t_transacciones_detalle_tmp', $key, true);
        }

        $detalle_insertar = GenerarArray("SELECT 
                     round(sum( ((valor_unitario*(1-(pcj_descuento/100)))*((pcj_iva/100)+1))*cantidad ) ,0) as total
                    FROM t_transacciones_detalle_tmp 
                    WHERE id_transaccion = $IdEnc ");

        $valor_total_dev = (isset($detalle_insertar[0]['total'])) ? $detalle_insertar[0]['total'] : 0;
        //devuelve todo en efectivo 
        if (isset($_POST["devolver_pago_completo"]) && $_POST["devolver_pago_completo"] == 0) {
          $data_pago = array(
            'numero' => $numero,
            'tipo' => $_POST["tipo"],
            'id_medio_pago' => 1,
            'valor' => $valor_total_dev * -1,
            'tipo_tarjeta' => '0',
            'voucher' => '',
            'cambio' => 0,
            'id_transaccion' => $IdEnc,
            'id_transaccion_ref' => $IdEnc,
            'id_usuario' => $_SESSION["Ses_id"]
          );
          insertReturnId('t_transacciones_pago_tmp', $data_pago, true);
        } else if (isset($_POST["devolver_pago_completo"]) && $_POST["devolver_pago_completo"] == 1) {
          //validar que existan las variables requeridas
          if (!isset($_POST["tipo_factura"]) || !isset($_POST["numero_factura"]) || !isset($_POST["id_transaccion_ref"])) {
            echo json_encode(array(
              'ok' => false,
              'message' => 'Faltan datos requeridos: tipo_factura, numero_factura o id_transaccion_ref',
              'id_transaccion' => 0
            ));
            return;
          }
          //busco el pago de la factura de t_transacciones_pago
          $sql_pago_factura = "INSERT INTO t_transacciones_pago_tmp (
                    numero,
                    tipo,
                    id_medio_pago,
                    valor,
                    tipo_tarjeta,
                    voucher,
                    cambio,
                    id_transaccion,
                    id_transaccion_ref,
                    id_usuario)
                    SELECT 
                      $numero,
                      '" . $_POST["tipo"] . "', 
                      id_medio_pago,
                      valor*-1,
                      tipo_tarjeta, 
                      voucher,
                      cambio,
                      $IdEnc,
                      '" . $_POST["id_transaccion_ref"] . "',       
                      " . $_SESSION["Ses_id"] . "
                    FROM t_transacciones_pago 
                    WHERE tipo = '" . $_POST["tipo_factura"] . "' 
                    AND numero = '" . $_POST["numero_factura"] . "' ";

          $pagos_factura = Query($sql_pago_factura);

          if (!$pagos_factura) {
            echo json_encode(array(
              'ok' => false,
              'message' => 'Error al insertar los pagos de la factura',
              'id_transaccion' => 0
            ));
            return;
          }
        }

        //hago el volcado del documento
        /***VOLCADO  */
        $query = "CALL p_volcar_documento(?,@nuevoid)";

        $stmt = $conn->prepare($query);

        if (!$stmt) {
          echo json_encode(array('ok' => false, 'message' => $conn->error, 'id' => 0));
          return;
        }

        $stmt->bind_param("i", $IdEnc);
        $stmt->execute();

        if (!$stmt) {
          echo json_encode(array('ok' => false, 'message' => $conn->error, 'id' => 0));
          return;
        }

        $result = $conn->query("SELECT @nuevoid AS nuevoid");

        if (!$result) {
          echo json_encode(array('ok' => false, 'message' => $conn->error));
          return;
        }

        $nuevoid = $result->fetch_assoc();

        if ($nuevoid['nuevoid'] > 0) {

          echo json_encode(array(
            'ok' => true,
            'message' => 'Se inserto correctamente el registro',
            'id_transaccion' => $nuevoid['nuevoid']
          ));
        } else {
          echo json_encode(array('ok' => false, 'message' => 'Error al insertar el registro ' . $nuevoid['nuevoid']));
        }
      } else {
        echo json_encode(array(
          'ok' => false,
          'message' => 'Error al insertar el encabezado '
        ));
      }
    } catch (\Throwable $th) {
      //throw $th;
      echo json_encode(array(
        'ok' => false,
        'message' => $th->getMessage(),
        'id_transaccion' => 0
      ));
    }

    break;

  case "listar-devoluciones":
    $fecha_inicial      = $_POST["fecha_inicial"];
    $fecha_final        = $_POST["fecha_final"];
    $id_tipo_documento  = $_POST["id_tipo_documento"];
    $idUser             = $_SESSION["Ses_id"];
    $id_rol             = $_SESSION["Ses_roles_id"];

    $sql = " SELECT 
                d.id,
                d.numero,
                d.tipo,
                d.valor_total,
                t.nit,
                case when t.primer_nombre is null then t.razon_comercial else CONCAT(t.primer_nombre,t.segundo_nombre,t.primer_apellido,t.segundo_apellido) end  as cliente,
                cast(d.fecha_hora as date) as fecha,
                ifnull(email_fe,'') as email,
                ifnull(r.cufe,'') as cufe,
                ifnull(xx.documento_electronico,'N') as documento_electronico
            from t_transacciones d
            inner join t_terceros t on t.id=d.id_tercero 
            inner join t_tipos_documentos xx on xx.id=d.id_tipo_documento
            left join t_respuestas_dian r on r.tipo=d.tipo and r.numero=d.numero
            where 
            case when d.anulado is null 
            then ifnull(d.anulado,'N')='N' 
            else d.anulado='' end
            and d.sw=4
            and d.id_tipo_documento =$id_tipo_documento

    ";
    if ($id_rol != 1) {
      $sql .= "
            and d.id_usuario = $idUser
        ";
    }

    if ($fecha_final != ''  && $fecha_inicial != '') {
      $sql .= "
            and cast(d.fecha_hora as date)   between '$fecha_inicial' and '$fecha_final'
        ";
    }

    $devoluciones = GenerarArray($sql, '');

    echo  json_encode($devoluciones);

    break;
}
