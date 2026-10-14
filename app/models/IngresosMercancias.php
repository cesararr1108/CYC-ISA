<?php
session_start();
require_once('bd.php');
require_once('funciones.php');
$conn = conectar();

switch ($_POST["op"]) {
  case "perfil_tributario_detalle":
    try {
      $id_perfil_tributario = isset($_POST["id_perfil_tributario"]) ? intval($_POST["id_perfil_tributario"]) : 0;
      $bodega = isset($_POST["bodega"]) ? intval($_POST["bodega"]) : 0;

      if ($id_perfil_tributario <= 0) {
        echo json_encode(array(
          'ok' => true,
          'data' => array()
        ));
        return;
      }

      $sql = " SELECT
                  t.id,
                  t.tipo,
                  t.descripcion,
                  d.bodega,
                  d.ret_fte,
                  d.ret_cree,
                  d.ret_ica,
                  d.ret_iva
                from t_perfil_tributario t
                inner join t_perfil_tributario_definiciones d on d.id_definicion = t.id
                where
                  t.id = $id_perfil_tributario
                  and (d.bodega = 0 or d.bodega = $bodega)
        ";

      $data = GenerarArray($sql);
      echo json_encode(array(
        'ok' => true,
        'data' => $data
      ));
    } catch (\Throwable $th) {
      echo json_encode(array(
        'ok' => false,
        'message' => $th->getMessage()
      ));
    }
    break;
  case "guardar_retenciones":
    try {
      $id_transaccion = isset($_POST["id_transaccion"]) ? intval($_POST["id_transaccion"]) : 0;
      $ret_fte = isset($_POST["ret_fte"]) ? floatval($_POST["ret_fte"]) : 0;
      $ret_ica = isset($_POST["ret_ica"]) ? floatval($_POST["ret_ica"]) : 0;
      $ret_iva = isset($_POST["ret_iva"]) ? floatval($_POST["ret_iva"]) : 0;
      $id_concepto_retencion = isset($_POST["id_concepto_retencion"]) ? intval($_POST["id_concepto_retencion"]) : 0;

      if ($id_transaccion <= 0) {
        echo json_encode(array(
          'ok' => false,
          'message' => 'ID de transacción inválido'
        ));
        return;
      }

      $sql = "UPDATE t_transacciones_tmp 
                SET retencion_fuente = $ret_fte,
                    retencion_ica = $ret_ica,
                    retencion_iva = $ret_iva,
                    id_concepto_retencion = $id_concepto_retencion
                WHERE id = $id_transaccion";

      $update = Query($sql);

      if ($update) {
        echo json_encode(array(
          'ok' => true,
          'message' => 'Retenciones guardadas correctamente'
        ));
      } else {
        echo json_encode(array(
          'ok' => false,
          'message' => 'Error al guardar retenciones'
        ));
      }
    } catch (\Throwable $th) {
      echo json_encode(array(
        'ok' => false,
        'message' => $th->getMessage()
      ));
    }
    break;
  case "update_encabezado_compra":
    $id_transaccion = $_POST["id_transaccion"];
    $sql = " select  
          CAST(bruto as decimal(19,0)) as bruto,
          CAST(descuento as decimal(19,0)) as descuento ,
          CAST(iva as decimal(19,0)) as iva ,
          CAST(ibua as decimal(19,0)) as ibua ,
          CAST(icui as decimal(19,0)) as icui ,
          CAST(impoconsumo as decimal(19,0)) as impoconsumo  
        from v_detalle_transaccion_tmp where id_transaccion=$id_transaccion";
    $datos = GenerarArray($sql);

    if (count($datos) == 0) {
      echo  json_encode(array(
        'bruto' => 0,
        'descuento' => 0,
        'iva' => 0,
        'ibua' => 0,
        'icui' => 0,
        'impoconsumo' => 0
      ));
      return;
    }

    $total = $datos[0]["bruto"] - $datos[0]["descuento"] + $datos[0]["iva"] + $datos[0]["ibua"] + $datos[0]["icui"] + $datos[0]["impoconsumo"];


    $update = Query("
        update t_transacciones_tmp set 
        valor_total= " . $total . ", 
        iva=" . $datos[0]["iva"] . ",
        descuento = " . $datos[0]["descuento"] . ",
        ibua =" . $datos[0]["ibua"] . ",
        icui=" . $datos[0]["icui"] . ",
        impoconsumo=" . $datos[0]["impoconsumo"] . "
        where id=$id_transaccion
      ");
    echo  json_encode($datos);

    break;
  case "actualizarNota":


    try {
      //code...c
      $id_transaccion = $_POST["id_transaccion"];
      $nota = $_POST["nota"];
      $update = Query("update t_transacciones_tmp  set notas='$nota' where id = $id_transaccion");

      if ($update) {
        echo  json_encode(array(
          'ok' => true,
          'message' => 'Se actualizo correctamente'
        ));
      } else {
        echo  json_encode(array(
          'ok' => false,
          'message' => 'Error al actualizar la nota'
        ));
      }
    } catch (\Throwable $th) {
      //throw $th;
      echo  json_encode(array(
        'ok' => false,
        'message' => $th->getMessage()
      ));
    }

    break;
  case "cargar-precios":
    try {
      $id_referencia = isset($_POST["id_referencia"]) ? intval($_POST["id_referencia"]) : 0;
      $id_transaccion = isset($_POST["id_transaccion"]) ? intval($_POST["id_transaccion"]) : 0;

      if ($id_referencia <= 0 || $id_transaccion <= 0) {
        echo json_encode([]);
        break;
      }

      $sql = "SELECT *
              FROM t_precios_recepcion
              WHERE id_transaccion = $id_transaccion
              AND id_referencia = $id_referencia";

      $result = $conn->query($sql);

      if (!$result) {
        echo json_encode([]);
        break;
      }

      $datos = [];
      while ($row = $result->fetch_assoc()) {
        $datos[] = $row;
      }
      $result->free();

      echo json_encode($datos);
    } catch (\Throwable $th) {
      echo json_encode([]);
    }
    break;
  case "update-ref":
    try {
      $id_transaccion = $_POST["id_transaccion"];
      $referencia = $_POST["referencia"];

      $update = Query("update t_transacciones_tmp set documento = '$referencia' where id = $id_transaccion");
      if ($update) {
        echo  json_encode(array(
          'ok' => true,
          'message' => 'Referencia actualizada correctamente'
        ));
      } else {
        echo  json_encode(array(
          'ok' => false,
          'message' => 'Error al actualizar la referencia'
        ));
      }
    } catch (\Throwable $th) {
      echo  json_encode(array(
        'ok' => false,
        'message' => $th->getMessage()
      ));
    }
    break;
  case "cargar-lotes":
    $id_referencia = isset($_POST["id_referencia"]) ? intval($_POST["id_referencia"]) : 0;
    $sql = "SELECT
              id,
              lote,
              vencimiento
            FROM
              t_referencias_lotes
            WHERE
              id_referencia = $id_referencia";
    $resp = generarArray($sql);
    echo json_encode($resp);
    break;
  case "insertar_carga_masiva_tmp":

    try {
      //code...
      //begin transaccion
      $conn->begin_transaction();
      $rows = json_decode($_POST["rows"], true);
      $encabezado = json_decode($_POST["encabezado"], true);
      // datos 

      //numero siguinte de transaccion
      $data = $encabezado;
      $data["id_usuario"] = $_SESSION["Ses_id"];
      $numero = numeroSiguiente($data["id_tipo_documento"]);
      $data["fecha"] = !empty($encabezado['fecha']) ? $encabezado['fecha'] : date("Y-m-d");
      $data["numero"] = $numero;
      //


      $id = insertReturnId('t_transacciones_tmp', $data, true);

      if ($id <= 0) {
        $conn->rollback();
        echo json_encode(array(
          'ok' => false,
          'message' => 'Error al insertar transacción'
        ));
        return;
      }

      if ($id > 0) {
        //recorro los items y traigo los datos necesarios 
        foreach ($rows as $row) {
          $row["id_transaccion"] = $id;

          $parametros =   [];
          //valido si existe el lote para el id_referencia
          $parametros[] = array(
            'valor' => $row['id_referencia'],
            'tipo' => 'i'
          );
          $parametros[] = array(
            'valor' => $row['lote'],
            'tipo' => 's'
          );

          $existeLote = GenerarArrayPrepare("SELECT id FROM t_referencias_lotes 
              where id_referencia = ?
              and lote = ? ", $parametros);

          if (count($existeLote) > 0) {
            $row['id_lote'] = $existeLote[0]['id'];
          } else {

            $id_lote = insertReturnId('t_referencias_lotes', [
              'id_referencia' => $row['id_referencia'],
              'lote' => $row['lote'],
              // LLEGA COMO DD-MM-YYYY, SE CONVIERTE A YYYY-MM-DD
              'vencimiento' => date('Y-m-d', strtotime(str_replace('-', '/', $row['fecha_vencimiento'])))
            ]);
            $row['id_lote'] = $id_lote;
          }
          $ref = GenerarArrayPrepare("SELECT 
                                           case  
                                           when  r.und_compra =1 then r.und_1
                                            when r.und_compra =2 then r.und_2
                                            when r.und_compra =3 then r.und_3
                                            when r.und_compra =4 then r.und_4 
                                          end as und,
                                          case  
                                           when  r.und_compra =1 then r.cant_1
                                            when r.und_compra =2 then r.cant_2
                                            when r.und_compra =3 then r.cant_3
                                            when r.und_compra =4 then r.cant_4 
                                          end as can_und,
                                          i.porcentaje_iva  ,
                                          r.impoconsumo,
                                          r.ibua
                                          FROM t_referencias r 
                                          inner join t_iva i on r.id_iva = i.id  where r.id = ? ", [
            ['valor' => $row['id_referencia'], 'tipo' => 'i']
          ]);

          $dataRef = [
            'id_transaccion' => $row['id_transaccion'],
            'id_referencia' => $row['id_referencia'],
            'cantidad' => $row['cantidad'],
            'valor_unitario' => $row['costo_unitario'],
            'costo_unitario' => $row['costo_unitario'],
            'id_lote' => $row['id_lote'],
            'pcj_iva' =>  $row['porcentaje_iva'], //$ref[0]['porcentaje_iva'],
            'pcj_descuento' => $row['pcj_descuento'],
            'und' => trim($row['und']),
            'can_und' => (int)$row['can_und'],
            // LLEGA COMO DD-MM-YYYY, SE CONVIERTE A YYYY-MM-DD
            'vmcto' => date('Y-m-d', strtotime(str_replace('-', '/', $row['fecha_vencimiento']))),
            'lote' => $row['lote'],
            'sw' => 1,
            'id_bodega' => $row['id_bodega'],
            'id_pedido' => 0,
            'impoconsumo' => $ref[0]['impoconsumo'],
            'ibua' => 0,
            'icui' => 0,
            'id_transaccion_ref' => 0,
            'fec' => date('Y-m-d'),
            'comision' => 0
          ];
          $id_detalle = insertReturnId('t_transacciones_detalle_tmp', $dataRef, true);

          if ($id_detalle <= 0) {
            $conn->rollback();
            echo json_encode(array(
              'ok' => false,
              'message' => 'Error al insertar detalle de transacción'
            ));
            return;
          }
        }
      }

      $conn->commit();
      // recalcular encabeazado de transaccion
      actualizarTotalesEncabezado($id);

      $datater = GenerarArrayPrepare(
        "SELECT 
        td.direccion, 
        t.nit, 
        concat(t.primer_apellido, ' ', t.segundo_apellido, ' ', t.primer_nombre, ' ', t.segundo_nombre) as nombres,
        t.razon_comercial 
        FROM t_terceros t 
        inner join t_terceros_direcciones td on t.id = td.id_tercero and td.principal = 1
        
        WHERE t.id = ?",
        [
          ['valor' => $data['id_tercero'], 'tipo' => 'i']
        ]
      );

      echo  json_encode(array(
        'ok' => true,
        'message' => 'Carga masiva insertada correctamente',
        'id_transaccion' => $id,
        'data' => [
          'id_transaccion' => $id,
          'id_tercero' => $data['id_tercero'],
          'id_tercero_dir' => $data['id_tercero_dir'],
          'direccion' => $datater[0]['direccion'],
          'nit' => $datater[0]['nit'],
          'nombres' => $datater[0]['nombres'],
          'razon_comercial' => $datater[0]['razon_comercial'],
          'id_bodega' => $data['id_bodega'],
          'id_tipo_documento' => $data['id_tipo_documento'],
          'id_condicion_pago' => $data['id_condicion'],
          'notas' => $data['notas'],
          'valor_total' => $data['valor_total'],
          'numero' => $data['numero'],
          'documento' => $data['documento'],
          'id_concepto_retencion' => $data['id_concepto_retencion'],
        ]
      ));
    } catch (\Throwable $th) {
      //throw $th;
      echo  json_encode(array(
        'ok' => false,
        'message' => $th->getMessage(),
        'id_transaccion' => 0
      ));
    }
    break;
}
