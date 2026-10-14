<?php
session_start();
require_once('bd.php');
require_once('funciones.php');
require_once('../shared/servicios/servicios-email.php');
$conn = conectar();

switch ($_POST["op"]) {
  case 'listar_pedidos':
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
      $logistico = isset($_POST["logistico"]) ? $_POST["logistico"] : '';

      $sql = "SELECT * FROM v_pedidos v WHERE v.id_bodega = $bodega ";

      if ($estado != '') {
        $sql .= " AND  v.estado = '$estado' ";
      }
      if ($logistico !== 'S') {
        if ($_SESSION["Ses_roles_id"] != 1 && $_SESSION["Ses_roles_id"] != 3) {
          $sql .= " AND  v.id_usuario = " . $_SESSION["Ses_id"];
        }
      }

      if ($fecha_inicial != '' && $fecha_final != '') {
        $sql .= " AND  CAST(v.fecha_hora AS DATE) BETWEEN CAST('$fecha_inicial' AS DATE) AND CAST('$fecha_final' AS DATE)";
      }

      if ($nit != '' && $nit != '0') {
        $sql .= " AND v.nit=" . $nit;
      }


      $pedidos = $conn->query($sql);

      $datos = array();

      // Obtener los resultados de la consulta
      while ($row = $pedidos->fetch_assoc()) {
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
        'message' => print_r($th),
        'data' => null
      ));
    }

    break;
  case 'listarDirecciones':
    $id_tercero = $_POST["id_tercero"];
    $sql = "SELECT 
              d.id,
              d.principal,
              CONCAT( d.`direccion`,' ',ifnull(x.departamento,''),'-',ifnull(m.`municipio`,'')  ) as direccion
            from `t_terceros_direcciones` d 
            left join `t_departamentos` x on x.codigo_dpto=d.`codigo_dpto`
            left join `t_municipios` m on m.`codigo_dpto`=x.codigo_dpto and  m.`codigo`= d.`codigo_mpio` 
            where d.id_tercero=$id_tercero
            order by 
            d.`principal` desc  
            ";
    $direcciones = GenerarArray($sql);
    echo json_encode($direcciones);
    break;
  case "volcar_pedido":

    $numero_pedido = $_POST["numero"];
    $sw = $_POST["sw"];

    // Obtener el pedido 
    $pedido = GenerarArray("SELECT * FROM t_pedidos 
            WHERE numero = $numero_pedido AND sw = $sw
        ", '');

    if (count($pedido) == 0) {
      echo json_encode(array(
        'ok' => false,
        'message' => 'No se encontró el pedido'
      ));
      return;
    }

    // Obtener número siguiente
    $numero = numeroSiguiente($pedido[0]["id_tipo_documento"]);

    // Iniciar transacción
    Query("BEGIN TRANSACTION");

    // Insertar en t_transacciones_tmp y recuperar el ID insertado
    $query_insert = "INSERT INTO t_transacciones_tmp (
                id_tercero,
                id_bodega,
                numero,
                id_tipo_documento,
                notas,
                id_usuario,
                fecha_hora,
                valor_total,
                sw,
                id_tercero_dir,
                id_pedido,
                id_condicion,
                lista,
                iva,
                descuento,
                zona_ventas,
                zona_descripcion,
                vendedor
            )
            OUTPUT INSERTED.id  -- Para SQL Server
            SELECT 
                id_tercero,
                id_bodega,
                $numero,
                id_tipo_documento,
                notas,
                id_usuario,
                GETDATE(), -- Fecha del sistema
                valor_total,
                sw,
                id_tercero_dir,
                " . $pedido[0]["id"] . ",
                id_condicion,
                lista_precios,
                iva,
                descuento,
                zona_ventas,
                zona_descripcion,
                vendedor
            FROM t_pedidos 
            WHERE numero = $numero_pedido AND sw = $sw
        ";

    // Ejecutar la consulta y obtener el ID insertado
    $insert_enc = Query($query_insert);
    $id_transaccion = mysqli_insert_id($conn);

    if (!$insert_enc) {
      Query("ROLLBACK");
      echo json_encode(array('ok' => false, 'message' => 'Error al insertar transacción.'));
      return;
    }


    $detalle = GenerarArray("
            select 
                p.id_referencia,
                p.cantidad,
                p.valor_unitario,
                p.pcj_iva,
                p.pcj_descuento,
                p.und,
                can_und,
                p.sw,
                p.id_bodega,
                p.vendedor,
                p.promedio as costo_unitario
            from t_pedidos_detalle d
            left join v_costo_promedio p on p.id_referencia= d.id_referencia
            where d.numero = $numero and d.sw=$sw
        ");

    if (count($detalle) == 0) {
      echo json_encode(array('ok' => false, 'message' => 'No se encontraron items en el pedido.'));
      return;
    }

    foreach ($detalle as $det) {
      $costo_unitario = $det["costo_unitario"];
      $valor_unitario = $det["valor_unitario"];
      $pcj_iva  = $det["pcj_iva"];
      $pcj_descuento = $det["pcj_descuento"];
      $und = $det["und"];
      $can_und = $det["can_und"];
      $sw = $det["sw"];
      $id_bodega = $det["id_bodega"];
      $vendedor = $pedido[0]["vendedor"];
      $id_pedido = $pedido[0]["id"];
      $id_referencia = $det["id_referencia"];
      $cantidad = $det["cantidad"];
      $bodega = $det["id_bodega"];

      // Procesar los ítems con FEFO
      $items = fefo($id_referencia, $cantidad, $bodega);

      if (!$items["ok"]) {
        Query("ROLLBACK");
        echo json_encode(array('ok' => false, 'message' => 'Error al procesar ítems.'));
        return;
      }

      if (count($items["items"]) == 0) {
        Query("ROLLBACK");
        echo json_encode(array('ok' => false, 'message' => 'No se encontraron lotes disponibles.'));
        return;
      }

      foreach ($items as $item) {
        $insert_detalle = Query("
            insert into t_transacciones_detalle_tmp(
                id_transaccion
                id_referencia
                cantidad
                valor_unitario
                costo_unitario
                id_lote
                pcj_iva
                pcj_descuento
                und
                can_und
                vmcto
                lote
                sw
                id_bodega
                id_pedido
            )
            values(
                $id_transaccion,
                " . $item["id_referencia"] . ",
                " . $item["cantidad"] . ",
                $valor_unitario,
                $costo_unitario,
                " . $item["id_lote"] . ",
                $pcj_iva,
                $pcj_descuento,
                '$und',
                $can_und,
                cast('" . $item["vencimiento"] . "' as date)
            )
            ");
      }
    }
    // Confirmar la transacción
    Query("COMMIT");

    echo json_encode(array('ok' => true, 'message' => 'Pedido volcado correctamente', 'id_transaccion' => $id_transaccion));
    break;
  case "email":
    header('Content-Type: application/json; charset=utf-8');
    $correo = new Correo(true, 'CYC');
    $id = $_POST["id"];
    //capturo correo del usuario 
    $destinatarios = GenerarArray("
            select
              email  
            from t_usuarios u 
            where usuario='" . $_SESSION["Ses_usuario"] . "'
        ");

    $pedido = GenerarArray("
            select 
                p.numero,
                p.valor_total,
                p.iva,
                p.descuento,
                p.notas,
                p.fecha_hora,
                u.usuario ,
                t.nit,
                t.email_fe,
                IFNULL(t.razon_comercial,'') as razon_comercial ,
                IFNULL(CONCAT(t.primer_nombre,' ',t.segundo_nombre,' '+t.primer_apellido,' ',t.segundo_nombre),'') as cliente,
                IFNULL(x.descripcion,'') as condicion,
                b.almacen,
                IFNULL(b.descripcion,'') as des_almacen 
            from t_pedidos  p 
            inner join t_terceros  t on t.id=p.id_tercero 
            inner join t_almacenes b on b.id=p.id_bodega
            inner join t_usuarios  u on u.id=p.id_usuario
            left join t_condiciones_pago  x on x.id=p.id_condicion

            where p.id=$id
        ");

    // $pedido es el array que devuelves con tu consulta
    $pedido = $pedido[0]; // suponiendo que solo es un pedido


    $html = '<!DOCTYPE html>
          <html>
          <head>
            <meta charset="UTF-8">
            <title>Detalle del Pedido</title>
            <style>
          /* Contenedor general */
          .table-container {
              max-width: 100%;
              overflow-x: auto;
              margin-top: 20px;
              border-radius: 8px;
              box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
              background-color: #ffffff;
          }

          /* Tabla principal */
          .pedido-table {
              width: 100%;
              border-collapse: collapse;
              font-size: 14px;
              color: #333;
          }

          /* Cabecera */
          .pedido-table thead {
              background-color: #f4f6f8;
              border-bottom: 2px solid #e0e0e0;
          }

          .pedido-table thead th {
              text-align: left;
              padding: 12px 16px;
              font-weight: 600;
              color: #555;
          }

          /* Filas */
          .pedido-table tbody tr {
              border-bottom: 1px solid #eee;
              transition: background-color 0.2s ease;
              font-size:0.7rem;
          }

          .pedido-table tbody tr:hover {
              background-color: #f9f9f9;
          }

          /* Celdas */
          .pedido-table td {
              padding: 10px 16px;
              vertical-align: middle;
          }

          /* Celdas numéricas alineadas a la derecha */
          .pedido-table td.cantidad,
          .pedido-table td.precio,
          .pedido-table td.total {
              text-align: right;
              font-weight: 500;
          }

          /* Alternancia de filas (opcional) */
          .pedido-table tbody tr:nth-child(odd) {
              background-color: #fcfcfc;
          }

          /* Estilo para la última fila de totales, si aplica */
          .pedido-table tfoot td {
              font-weight: bold;
              background-color: #f0f0f0;
              border-top: 2px solid #ccc;
          }

            </style>
          </head>
          <body style="margin:0; padding:0; font-family: Arial, sans-serif; background-color:#f5f7fa; color:#333;">
            <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px; margin:20px auto; background:#ffffff; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.05); overflow:hidden;">
              <!-- Encabezado -->
              <tr>
                <td style="background:#9230d8; padding:20px; text-align:center; color:#fff; font-size:20px; font-weight:bold;">
                  Confirmaci' . mb_convert_encoding('ó', "ISO-8859-1", "UTF-8") . 'n de Pedido
                </td>
              </tr>

              <!-- Info Cliente -->
              <tr>
                <td style="padding:20px;">
                  <h2 style="margin:0; font-size:18px; color:#9230d8;">Hola, ' . mb_convert_encoding($pedido["cliente"], "ISO-8859-1", "UTF-8") . '</h2>
                  <p style="margin:5px 0 15px; font-size:14px;">Gracias por tu compra. ' . mb_convert_encoding('Aquí', "ISO-8859-1", "UTF-8") . ' tienes el resumen de tu pedido:</p>

                  <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse; font-size:14px;">
                    <tr>
                      <td style="border-bottom:1px solid #eee;"><strong>N' . mb_convert_encoding('ú', "ISO-8859-1", "UTF-8") . 'mero de pedido:</strong></td>
                      <td style="border-bottom:1px solid #eee;">' . $pedido["numero"] . '</td>
                    </tr>
                    <tr>
                      <td style="border-bottom:1px solid #eee;"><strong>Condici' . mb_convert_encoding('ó', "ISO-8859-1", "UTF-8") . 'n de pago:</strong></td>
                      <td style="border-bottom:1px solid #eee;">' . $pedido["condicion"] . '</td>
                    </tr>
                    <tr>
                      <td style="border-bottom:1px solid #eee;"><strong>Almacen:</strong></td>
                      <td style="border-bottom:1px solid #eee;">' . mb_convert_encoding($pedido["almacen"] . "-" . $pedido["des_almacen"], "ISO-8859-1", "UTF-8") . '</td>
                    </tr>
                    <tr>
                      <td style="border-bottom:1px solid #eee;"><strong>Fecha:</strong></td>
                      <td style="border-bottom:1px solid #eee;">' . $pedido["fecha_hora"] . '</td>
                    </tr>
                    <tr>
                      <td style="border-bottom:1px solid #eee;"><strong>Cliente:</strong></td>
                      <td style="border-bottom:1px solid #eee;">' . mb_convert_encoding($pedido["razon_comercial"], "ISO-8859-1", "UTF-8") . '</td>
                    </tr>
                    <tr>
                      <td style="border-bottom:1px solid #eee;"><strong>NIT:</strong></td>
                      <td style="border-bottom:1px solid #eee;">' . $pedido["nit"] . '</td>
                    </tr>
                  </table>
                </td>
              </tr>

              <!-- Totales -->
              <tr>
                <td style="padding:20px;">
                  <h3 style="margin:0 0 10px; font-size:16px; color:#9230d8;">Resumen de pago</h3>
                  <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse; font-size:14px;">
                    <tr>
                      <td style="border-bottom:1px solid #eee;"><strong>Subtotal:</strong></td>
                      <td style="border-bottom:1px solid #eee; text-align:right;">$' . number_format($pedido["valor_total"] - $pedido["iva"] + $pedido["descuento"], 0, ",", ".") . '</td>
                    </tr>
                    <tr>
                      <td style="border-bottom:1px solid #eee;"><strong>IVA:</strong></td>
                      <td style="border-bottom:1px solid #eee; text-align:right;">$' . number_format($pedido["iva"], 0, ",", ".") . '</td>
                    </tr>
                    <tr>
                      <td style="border-bottom:1px solid #eee;"><strong>Descuento:</strong></td>
                      <td style="border-bottom:1px solid #eee; text-align:right;">-$' . number_format($pedido["descuento"], 0, ",", ".") . '</td>
                    </tr>
                    <tr>
                      <td style="font-weight:bold; font-size:15px;">Total a pagar:</td>
                      <td style="font-weight:bold; font-size:15px; text-align:right;">$' . number_format($pedido["valor_total"], 0, ",", ".") . '</td>
                    </tr>
                  </table>
                </td>
              </tr>

              <!-- Notas -->
              <tr>
                <td style="padding:20px;">
                  <p style="margin:0; font-size:13px; background:#f9f9f9; padding:10px; border-radius:5px;">
                    <strong>Notas:</strong> ' . mb_convert_encoding($pedido["notas"], "ISO-8859-1", "UTF-8") . '
                  </p>
                </td>
              </tr>
              <!-- Footer -->
              <tr>
                <td style="background:#f5f5f5; text-align:center; padding:15px; font-size:12px; color:#888;">' . mb_convert_encoding('©', "ISO-8859-1", "UTF-8") . ' ' . date("Y") . ' CYC TECHNOLOGY - Este es un correo autom' . mb_convert_encoding('á', "ISO-8859-1", "UTF-8") . 'tico, por favor no responder.
                </td>
              </tr>
            </table>
          </body>
          </html>';


    // Datos recibidos del JS (POST o JSON)
    //$destinatarios = [$destinatarios[0]["email"],$pedido["email_fe"]] ?? [];
    $destinatarios  = 'cesararr1108@gmail.com';
    $asunto = 'Nuevo pedido de venta numero ' . $pedido["numero"];
    $basePath = '/var/www/html/dev/files/' . $_SESSION["Ses_empresa"] . '/';

    if (!file_exists($basePath)) {
      mkdir($basePath, 0775, true);
      chmod($basePath, 0775);
    }

    $adjunto       = isset($_POST["NombreArchivo"]) ? '/var/www/html/dev/files/' . $_SESSION["Ses_empresa"] . "/" . $_POST["NombreArchivo"] . '.pdf' : null;

    $enviado = $correo->enviarCorreo($destinatarios, $asunto, $html, $adjunto);

    if ($enviado) {
      echo json_encode([
        'ok'  => true,
        'message' => 'Correo enviado correctamente'
      ]);
    } else {
      echo json_encode([
        'ok'  => false,
        'message' => 'No se pudo enviar el correo'
      ]);
    }
    break;

  case "marcar_ocupado":
    $numero = $_POST["numero"];
    $tipo   = $_POST["tipo"];
    $id_usuario = $_SESSION["Ses_id"];
    $sql    = "SELECT * FROM t_documentos_ocupados WHERE numero =$numero AND tipo ='$tipo'";
    $reg    = GenerarArray($sql, '');

    if (count($reg) == 0) {
      $insert = Query("INSERT INTO t_documentos_ocupados(
              numero ,
              tipo ,
              id_usuario 
            ) VALUES(
                $numero,
                '$tipo',
                $id_usuario
            )");
      if (!$insert) {
        echo json_encode([
          'ok' => false,
          'message' => 'No se puedo insertar el estado de ocupado'
        ]);
        return;
      }
    }

    echo  json_encode([
      'ok' => true,
      'message' => 'Se guardaron correctamente los registros'
    ]);

    break;
  case "eliminar_ocupado":
    $tipo = $_POST["tipo"];
    $numero = $_POST["numero"];

    $del = Query("DELETE FROM t_documentos_ocupados WHERE numero = $numero AND tipo='$tipo'");

    if (!$del) {
      echo  json_encode([
        'ok' => false,
        'message' => 'Error al eliminar el pedido ocupado'
      ]);
      return;
    }

    echo  json_encode([
      'ok' => true
    ]);
    break;
  case "pedido_ocupado":
    $tipo = $_POST["tipo"];
    $numero = $_POST["numero"];
    $id_usuario = $_SESSION["Ses_id"];

    $sql    = "SELECT 
                  o.*,
                  CONCAT( u.nombres, u.apellidos) as usuario  ,
                  o.fecha
                FROM t_documentos_ocupados o
                INNER JOIN t_usuarios u on u.id = o.id_usuario 
                WHERE numero =$numero AND tipo ='$tipo' AND o.id_usuario <> $id_usuario";
    $reg    = GenerarArray($sql, '');

    echo json_encode([
      'ocupado' => count($reg) > 0 ? true : false,
      'usuario' => $reg[0]["usuario"],
      'fecha'  => $reg[0]["fecha"]
    ]);
    break;

  case 'solicita_autorizacion':

    $idPedido   = (int)$_POST["id_pedido"];
    $idUsuario  = (int)$_SESSION["Ses_id"];
    $comentario = trim($_POST["comentario"]);

    // Verificar si ya existe una autorización pendiente
    // P = Pendiente
    // A = Aprobada
    // R = Rechazada
    $existe = GenerarArray("SELECT COUNT(*) as total
        FROM t_pedidos_autorizaciones
        WHERE id_pedido = $idPedido");

    if ($existe[0]['total'] > 0) {
      echo json_encode([
        'ok' => false,
        'mensaje' => 'El pedido ya tiene una solicitud de autorización pendiente.',
        'datos' => $existe
      ]);
      break;
    }

    $q = Query("INSERT INTO t_pedidos_autorizaciones(
                    id_pedido,
                    id_usuario_solicita,
                    fecha_solicitud,
                    estado,
                    observaciones
                )VALUES(
                    $idPedido,
                    $idUsuario,
                    NOW(),
                    'P',
                    '$comentario'
                )");

    if ($q) {
      echo json_encode([
        'ok' => true,
        'mensaje' => 'La solicitud de autorización fue enviada correctamente.'
      ]);
    } else {
      echo json_encode([
        'ok' => false,
        'mensaje' => 'No fue posible registrar la solicitud.'
      ]);
    }

    break;

  case "validar_autorizacion_pedido":

    $id_pedido = (int)$_POST["id_pedido"];

    $sql = "SELECT 
                pa.estado AS autorizacion
            FROM t_pedidos_autorizaciones pa
            WHERE pa.id_pedido = $id_pedido
            ORDER BY pa.id DESC
            LIMIT 1";

    $data = GenerarArray($sql);

    if (!$data || count($data) == 0) {

      echo json_encode([
        'ok' => false,
        'mensaje' => 'No existe solicitud de autorización para este pedido.',
        'data' => null
      ]);

      break;
    }


    echo json_encode([
      'ok' => true,
      'data' => $data[0]
    ]);

    break;
}
