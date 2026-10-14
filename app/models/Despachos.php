<?php
session_start();
require_once('bd.php');
require_once('funciones.php');
$conn = conectar();
switch ($_POST["op"]) {
  case 's_transportadores':
    $tipo = $_POST['tipo'];
    $sql = "SELECT
                t.id,
                t.nit,
                t.id_tipo_identificacion,
                t.nombres,
                t.razon_social,
                t.celular,
                d.departamento,
                c.municipio,
                t.vehiculo,
                t.placas,
                t.bloqueo_planilla,
                t.tipo_transportador,
                CASE t.tipo_transportador
                    WHEN 'M' THEN 'MENSAJERO'
                    WHEN 'T' THEN 'TRANSPORTADOR'
                    ELSE ''
                END AS tipo_desc,
                t.fecha_creacion,
                t.codigo_dpto,
                t.codigo_mpio
            FROM t_transportadores t
            INNER JOIN t_departamentos d
                ON d.codigo_dpto = t.codigo_dpto
            INNER JOIN t_municipios c
                ON c.codigo_dpto = t.codigo_dpto
                AND c.codigo = t.codigo_mpio
            WHERE t.tipo_transportador = '$tipo'";
    $data = GenerarArray($sql);
    echo json_encode(array(
      'Ok' => true,
      'data' => $data
    ));
    break;
  case 's_tipos_facturas':
    $sql = "SELECT 
                id,
                tipo,
                descripcion 
            FROM t_tipos_documentos t 
            WHERE 
                sw = 5";
    $data = GenerarArray($sql);
    echo json_encode(array(
      'Ok' => true,
      'data' => $data
    ));
    break;
  case 'validar_factura':
    $numero = $_POST['factura'];
    $tipo = $_POST['tipo'];
    $sql = "SELECT 
                  d.id,
                  d.sw,
                  d.tipo,
                  d.numero,
                  IFNULL(r.id, 0) AS id_despacho,
                  IFNULL(r.estado, '') AS estado
              FROM t_transacciones d
              LEFT JOIN t_registro_despacho_det r 
                  ON r.id = (
                      SELECT MAX(r2.id)
                      FROM t_registro_despacho_det r2
                      WHERE r2.tipo_fac = d.tipo
                        AND r2.numero_fac = d.numero
                  )
              WHERE d.numero = $numero
                AND d.tipo = '$tipo'
                AND d.sw = 5";
    $data = GenerarArray($sql);
    echo json_encode(array(
      'Ok' => true,
      'data' => $data
    ));
    break;
  case 's_factura':
    $id = $_POST['id'];
    $sql = "SELECT 
                t.id,
                t.numero,
                t.tipo,
                t.nit,
                s.tratamiento AS nombres,
                s.razon_comercial,
                d.departamento AS dpto,
                c.municipio AS ciudad
            FROM t_transacciones t
            INNER JOIN t_terceros s 
                ON s.id = t.id_tercero
            LEFT JOIN t_terceros_direcciones r 
                ON r.id_tercero = t.id_tercero
                AND r.principal = 1
            LEFT JOIN t_departamentos d 
                ON d.codigo_dpto = r.codigo_dpto
            LEFT JOIN t_municipios c 
                ON c.codigo_dpto = r.codigo_dpto
                AND c.codigo = r.codigo_mpio
            WHERE t.id = $id";
    $data = GenerarArray($sql);
    echo json_encode(array(
      'Ok' => true,
      'data' => $data
    ));
    break;
  case 'g_despacho':
    $fields["usuario_id"] = $_SESSION["Ses_id"];
    $fields["transportador_id"] = $_POST["transportador"];
    $datos = json_decode($_POST['datos'], true);
    $id = insertReturnId('t_registro_despacho', $fields);

    if ($id > 0) {
      //Ahora insertamos el detalle de despacho
      $ok = true;
      $sql = '';
      for ($i = 0; $i <= count($datos) - 1; $i++) {
        $ok = Query(" INSERT INTO t_registro_despacho_det (
                          id_reg,
                          tipo_fac,
                          numero_fac,
                          estado,
                          cajas,
                          flete,
                          guia,
                          nota,
                          inicio
                      ) VALUES (
                          " . $id . ",
                          '" . $datos[$i]['tipo'] . "',
                          '" . $datos[$i]['numero'] . "',
                          'D',
                          '" . $datos[$i]['cajas'] . "',
                          " . $datos[$i]['flete'] . ",
                          '" . $datos[$i]['guia'] . "',
                          '" . $datos[$i]['nota'] . "',
                          NOW()
                      )
                  ");
        if (!$ok) break;
      }

      if ($ok) {
        echo json_encode(array(
          'ok' => true,
          'mensaje' => 'Registro insertado correctamente.',
          'id' => $id
        ));
      } else {
        echo json_encode(array(
          'ok' => false,
          'mensaje' => 'Error al insertar el registro',
          'id' => $id
        ));
      }
    } else {
      echo json_encode(array(
        'ok' => false,
        'mensaje' => 'Error al insertar el registro',
        'id' => $id
      ));
    }
    break;
  case 's_despacho':
    $fhIni = $_POST['fhIni'];
    $fhFin = $_POST['fhFin'];
    $sql = "SELECT 
                  d.id,
                  d.fecha_hora,
                  u.usuario,
                  t.nombres,
                  t.razon_social,
                  SUM(r.cajas) AS cajas,
                  SUM(r.flete) AS flete,
                  COUNT(r.numero_fac) AS nfacturas,
                  SUM(CASE WHEN r.estado = 'D' THEN 1 ELSE 0 END) AS despachadas,
                  SUM(CASE WHEN r.estado = 'E' THEN 1 ELSE 0 END) AS entregadas
              FROM t_registro_despacho d
              INNER JOIN t_transportadores t 
                  ON t.id = d.transportador_id
              INNER JOIN t_usuarios u 
                  ON u.id = d.usuario_id
              INNER JOIN t_registro_despacho_det r 
                  ON r.id_reg = d.id
              WHERE d.fecha_hora >= '$fhIni 00:00:00'
                AND d.fecha_hora < DATE_ADD('$fhFin', INTERVAL 1 DAY)
              GROUP BY 
                  d.id,
                  d.fecha_hora,
                  u.usuario,
                  t.nombres,
                  t.razon_social
              ORDER BY d.fecha_hora DESC;";
    $data = GenerarArray($sql);
    echo json_encode(array(
      'Ok' => true,
      'data' => $data
    ));

    break;
  case 's_despacho_det':
    $id = $_POST['id'];
    $sql = "SELECT 
              d.id,
              d.tipo_fac,
              d.numero_fac,
              d.cajas,
              d.flete,
              d.guia,
              d.estado
            FROM t_registro_despacho_det d 
            WHERE 
              d.id_reg = $id";
    $data = GenerarArray($sql);
    echo json_encode(array(
      'Ok' => true,
      'data' => $data,
      'SQL' => $sql
    ));

    break;
  case 'd_despacho_det':
    $id = $_POST['id'];
    $sql = "DELETE from t_registro_despacho_det where id = $id";
    if (Query($sql)) {
      echo json_encode(array(
        'ok' => true,
        'mensaje' => 'Registro eliminado correctamente.',
        'id' => $id
      ));
    } else {
      echo json_encode(array(
        'ok' => false,
        'mensaje' => 'Error al eliminado el registro',
        'id' => $id
      ));
    }
    break;
  case 'u_despacho_det':
    $id = $_POST['id'];
    $sql = "UPDATE t_registro_despacho_det set estado = 'E', fin = NOW() where id = $id";
    if (Query($sql)) {
      echo json_encode(array(
        'ok' => true,
        'mensaje' => 'Registro actualizado correctamente.',
        'id' => $id
      ));
    } else {
      echo json_encode(array(
        'ok' => false,
        'mensaje' => 'Error al actualizar el registro',
        'id' => $id
      ));
    }
    break;
}
