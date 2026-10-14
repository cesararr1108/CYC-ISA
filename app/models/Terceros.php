<?php
session_start();
require_once('bd.php');
require_once('funciones.php');
$conn = conectar();

function obtenerEstadoTerceroAuditoria($idTercero)
{
  $idTercero = (int)$idTercero;
  if ($idTercero <= 0) {
    return array();
  }

  $tercero = GenerarArray("SELECT * FROM t_terceros WHERE id = $idTercero");
  $direcciones = GenerarArray("SELECT id, codigo_pais, codigo_dpto, codigo_mpio, direccion, telefono, celular, email, principal
                               FROM t_terceros_direcciones
                               WHERE id_tercero = $idTercero
                               ORDER BY principal DESC, id ASC");
  $contactos = GenerarArray("SELECT id, nombres, apellidos, telefono, celular, email
                             FROM t_terceros_contactos
                             WHERE id_tercero = $idTercero
                             ORDER BY id ASC");

  return array(
    'tercero' => ($tercero && count($tercero) > 0) ? $tercero[0] : array(),
    'direcciones' => $direcciones ? $direcciones : array(),
    'contactos' => $contactos ? $contactos : array()
  );
}

function esArrayAsociativoAuditoriaTercero($arr)
{
  if (!is_array($arr)) {
    return false;
  }
  return array_keys($arr) !== range(0, count($arr) - 1);
}

function normalizarValorAuditoriaTercero($valor)
{
  if (is_array($valor)) {
    if (esArrayAsociativoAuditoriaTercero($valor)) {
      ksort($valor);
    }
    foreach ($valor as $k => $v) {
      $valor[$k] = normalizarValorAuditoriaTercero($v);
    }
  }
  return $valor;
}

function sonIgualesAuditoriaTercero($a, $b)
{
  $na = normalizarValorAuditoriaTercero($a);
  $nb = normalizarValorAuditoriaTercero($b);
  return json_encode($na, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) === json_encode($nb, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function construirCambiosAuditoriaTercero($anterior, $nuevo)
{
  if (!is_array($anterior) || !is_array($nuevo)) {
    $changed = !sonIgualesAuditoriaTercero($anterior, $nuevo);
    return array(
      'changed' => $changed,
      'anterior' => $changed ? $anterior : array(),
      'nuevo' => $changed ? $nuevo : array()
    );
  }

  if (esArrayAsociativoAuditoriaTercero($anterior) || esArrayAsociativoAuditoriaTercero($nuevo)) {
    $keys = array_unique(array_merge(array_keys($anterior), array_keys($nuevo)));
    $diffAnterior = array();
    $diffNuevo = array();
    $changed = false;

    foreach ($keys as $k) {
      $va = array_key_exists($k, $anterior) ? $anterior[$k] : null;
      $vn = array_key_exists($k, $nuevo) ? $nuevo[$k] : null;
      $sub = construirCambiosAuditoriaTercero($va, $vn);

      if ($sub['changed']) {
        $changed = true;
        $diffAnterior[$k] = $sub['anterior'];
        $diffNuevo[$k] = $sub['nuevo'];
      }
    }

    return array(
      'changed' => $changed,
      'anterior' => $diffAnterior,
      'nuevo' => $diffNuevo
    );
  }

  $changed = !sonIgualesAuditoriaTercero($anterior, $nuevo);
  return array(
    'changed' => $changed,
    'anterior' => $changed ? $anterior : array(),
    'nuevo' => $changed ? $nuevo : array()
  );
}

function auditarTercero($accion, $idTercero, $estadoAnterior, $estadoNuevo, $extra = array(), $conn = null)
{
  $diff = construirCambiosAuditoriaTercero($estadoAnterior, $estadoNuevo);
  if (!$diff['changed']) {
    return;
  }

  $detalle = array(
    'id_tercero' => (int)$idTercero,
    'estado_anterior' => $diff['anterior'],
    'estado_nuevo' => $diff['nuevo']
  );

  if (is_array($extra) && count($extra) > 0) {
    $detalle['extra'] = $extra;
  }

  registrarAuditoria($accion, 't_terceros', (int)$idTercero, $detalle, null, $conn);
}

if (!sessionExists()) {
  echo json_encode(array(
    'error' => true,
    'message' => 'La sesion se ha cerrado, por favor ingrese nuevamente'
  ));
  return;
}

// Este modelo solo LEE la sesión: se libera el bloqueo para que las peticiones
// simultáneas del mismo usuario (autocompletar, validaciones) no esperen en fila.
session_write_close();

switch ($_POST["op"]) {
  case 'g_tercero':
    $data = $_POST;
    $arrayDirecciones = json_decode($data['direcciones'], true);
    $arrayContactos = json_decode($data['contactos'], true);
    unset($data['direcciones'], $data['contactos']);
    $estadoAnterior = array();

    $data["usuario_creacion"] = $_SESSION["Ses_id"];
    $id = insertReturnId('t_terceros', $data, true);

    if ($id > 0) {

      /* Inserción de direcciones */
      if (!empty($arrayDirecciones) && is_array($arrayDirecciones)) {
        foreach ($arrayDirecciones as $item) {
          $idItem = $item['id'];
          $eliminar = $item['eliminar'];

          $dataDir = [
            'codigo_pais' => $item['pais'],
            'codigo_dpto' => $item['dpto'],
            'codigo_mpio' => $item['mpio'],
            'direccion' => $item['dir'],
            'telefono' => $item['tel'],
            'celular' => $item['cel'],
            'email' => $item['mail'],
            'id_tercero' => $id,
            'principal' => $item['principal']
          ];

          if ($eliminar == '') {
            if ($idItem == 0) { // Insertar
              insertReturnId('t_terceros_direcciones', $dataDir, false);
            } else { // Actualizar
              update('t_terceros_direcciones', $dataDir, $idItem);
            }
          } else { // Eliminar
            if (!empty($idItem)) {
              continue;
            }
          }
        }
      }
      /* Final inserción de direcciones */

      /* Inserción de contactos */
      if (!empty($arrayContactos) && is_array($arrayContactos)) {
        foreach ($arrayContactos as $item) {
          $idItem = $item['id'];
          $eliminar = $item['eliminar'];

          $dataCon = [
            'nombres' => $item['nombres'],
            'apellidos' => $item['apellidos'],
            'telefono' => $item['tel'],
            'celular' => $item['cel'],
            'email' => $item['mail'],
            'id_tercero' => $id
          ];

          if ($eliminar == '') {
            if ($idItem == 0) { // Insertar
              insertReturnId('t_terceros_contactos', $dataCon, false);
            } else { // Actualizar
              update('t_terceros_contactos', $dataCon, $idItem);
            }
          } else { // Eliminar
            Query('DELETE FROM t_terceros_contactos WHERE id = ' . intval($idItem));
          }
        }
      }
      /* Final inserción de contactos */

      $estadoNuevo = obtenerEstadoTerceroAuditoria($id);
      auditarTercero(
        'CREAR',
        $id,
        $estadoAnterior,
        $estadoNuevo,
        array(
          'modulo' => 'Terceros',
          'evento' => 'Creacion de tercero'
        ),
        $conn
      );

      echo json_encode([
        'error' => false,
        'message' => 'Tercero creado correctamente.',
        'id' => $id
      ]);
    } else {
      echo json_encode([
        'error' => true,
        'message' => 'Error al crear el tercero',
        'id' => 0
      ]);
    }
    break;

  case 'u_tercero':
    $data = $_POST;

    $arrayDirecciones = json_decode($data['direcciones'], true);
    $arrayContactos = json_decode($data['contactos'], true);
    unset($data['direcciones']);
    unset($data['contactos']);


    $estadoAnterior = obtenerEstadoTerceroAuditoria($data["id"]);
    $data["usuario_creacion"] = $_SESSION["Ses_id"];
    $update = update('t_terceros', $data, $data["id"]);

    if ($update) {
      $id = $data["id"];
      /* Inserción de direcciones */
      if (!empty($arrayDirecciones) && is_array($arrayDirecciones)) {
        foreach ($arrayDirecciones as $item) {
          $idItem = $item['id'];
          $eliminar = $item['eliminar'];

          $dataDir = [
            'codigo_pais' => $item['pais'],
            'codigo_dpto' => $item['dpto'],
            'codigo_mpio' => $item['mpio'],
            'direccion' => $item['dir'],
            'telefono' => $item['tel'],
            'celular' => $item['cel'],
            'email' => $item['mail'],
            'id_tercero' => $id,
            'principal' => $item['principal']
          ];

          if ($eliminar == '') {
            if ($idItem == 0) { // Insertar
              insertReturnId('t_terceros_direcciones', $dataDir, false);
            } else { // Actualizar
              update('t_terceros_direcciones', $dataDir, $idItem);
            }
          } else { // Eliminar
            if (!empty($idItem)) {
              continue;
            }
          }
        }
      }
      /* Final inserción de direcciones */

      /*Insercion de contactos*/
      foreach ($arrayContactos as $item) {
        $dataCon = [];
        $idItem = $item['id'];
        $eliminar = $item['eliminar'];
        $dataCon['nombres'] = $item['nombres'];
        $dataCon['apellidos'] = $item['apellidos'];
        $dataCon['telefono'] = $item['tel'];
        $dataCon['celular'] = $item['cel'];
        $dataCon['email'] = $item['mail'];
        $dataCon['id_tercero'] = $id;
        if ($eliminar == '') {
          if ($idItem == 0) { //Insertar
            insertReturnId('t_terceros_contactos', $dataCon, false);
          } else { //Actualizar
            update('t_terceros_contactos', $dataCon, $idItem);
          }
        } else { //eliminar
          $sql = 'delete from t_terceros_contactos where id = ' . $idItem;
          Query($sql);
        }
      }
      /*Final insercion de contactos*/

      $estadoNuevo = obtenerEstadoTerceroAuditoria($id);
      auditarTercero(
        'UPDATE',
        $id,
        $estadoAnterior,
        $estadoNuevo,
        array(
          'modulo' => 'Terceros',
          'evento' => 'Actualizacion de tercero'
        ),
        $conn
      );


      echo json_encode(array(
        'error' => false,
        'message' => 'Tercero actualizado correctamente.'
      ));
    } else {
      echo json_encode(array(
        'error' => true,
        'message' => 'Error al actualizar el tercero'
      ));
    }
    break;
  case 's_tercero':
    $filtro = isset($_POST['filtro']) ? trim($_POST['filtro']) : '';
    $tipo_tercero = isset($_POST['tipo_tercero']) ? $_POST['tipo_tercero'] : 0;
    $id_tercero = isset($_POST["id_tercero"]) ? (int)$_POST["id_tercero"] : 0;

    /*
     * ligero=1 (lo envía el POS): omite el saldo de CxC y la cartera vencida.
     * Esas dos subconsultas recorren las vistas v_cxc y v_cartera por cada fila
     * y eran la causa principal de la demora en el autocompletar. El POS no las usa
     * (el cupo y la mora se validan aparte con s_control_credito).
     * Sin el parámetro, la respuesta es igual a la anterior para no afectar otros módulos.
     */
    $ligero = isset($_POST['ligero']) && $_POST['ligero'] == '1';

    $sel_cartera = $ligero
      ? "0 AS cxc, 0 AS cartera_vencida"
      : "(IFNULL((
                SELECT 
                  SUM(saldo)
                FROM v_cxc 
                WHERE nit  = t.nit
              ),0)) AS cxc,
              (IFNULL((
                SELECT 
                SUM(total_vencida) 
                FROM v_cartera 
                WHERE nit = t.nit
              ),0)) AS cartera_vencida";

    $sql = "SELECT 
              CONCAT(t.nit, ' - ', IFNULL(t.tratamiento,''),' - ',IFNULL(t.razon_comercial,'')) AS value, 
              t.*,
              d.direccion,
              d.id AS id_direccion,
              CONCAT(z.codigo_zona,'-',z.descripcion) AS zona_ventas,
              z.codigo_zona,
              z.descripcion AS zona_descripcion,
              IFNULL(p.id,0) AS id_condicion ,
              p.descripcion  AS des_condicion,
              IFNULL(p2.id,0) AS id_condicion_prov,
              p2.descripcion AS des_condicion_prov,
              p.dias AS dias_c,
              p2.dias AS dias_p,
              CONCAT(d.telefono,' ',d.celular) AS telefono,
              $sel_cartera
              ,IFNULL(t.id_perfil_tributario,0) AS id_perfil_tributario
              ,IFNULL(tt.es_proveedor,'N') AS es_proveedor
              ,IFNULL(tt.es_cliente,'N') AS es_cliente
              ,IFNULL(tt.es_empleado,'N') AS es_empleado
            FROM t_terceros t 
            LEFT JOIN t_terceros_direcciones d  ON d.id = (
                                                    SELECT td.id
                                                    FROM t_terceros_direcciones td
                                                    WHERE td.id_tercero = t.id
                                                    ORDER BY td.principal DESC, td.id ASC
                                                    LIMIT 1
                                                )
            LEFT JOIN t_condiciones_pago      p  ON p.id=t.id_condicion and p.tipo_condicion='C'
            LEFT JOIN t_condiciones_pago      p2 ON p2.id=t.id_condicion_pago_prov and p2.tipo_condicion='P'
            LEFT JOIN t_zonas_ventas          z  ON z.id=t.id_zona_ventas
            LEFT JOIN t_tipos_terceros        tt ON tt.id = t.id_tipo_tercero
            WHERE 1 = 1 ";

    if ($id_tercero > 0) {
      $sql .= " and t.id=$id_tercero";
    } else {
      if ($filtro != '') {
        $sql .= " and " . filtrosBusquedaTerceros($filtro, 't');
      }
      if ($tipo_tercero != '' && $tipo_tercero != '0') {
        $sql .= " and t.id_tipo_tercero = '" . $conn->real_escape_string($tipo_tercero) . "'";
      }
    }

    $sql .= " LIMIT 10";
    $datos = GenerarArray($sql);
    $respuesta = array(
      'error' => false,
      'data' => $datos ? $datos : array()
    );
    // El SQL solo se devuelve fuera del modo ligero (antes viajaba siempre al navegador)
    if (!$ligero) {
      $respuesta['sql'] = $sql;
    }
    echo json_encode($respuesta);
    break;

  case 'd_tercero':
    $id = $_POST['id'];
    $estadoAnterior = obtenerEstadoTerceroAuditoria($id);
    $sql_check = GenerarArray("SELECT * FROM t_transacciones WHERE id_tercero = $id", '');
    if (count($sql_check) > 0) {
      echo json_encode(array(
        'success' => false,
        'message' => 'El tercero tiene movimientos, no es posible su eliminación.'
      ));
    } else {
      $response = [];
      try {
        $conn->begin_transaction();
        $sql1 = "DELETE FROM t_terceros WHERE id = ?";
        $stmt1 = $conn->prepare($sql1);
        if (!$stmt1) {
          throw new Exception("Error al preparar la consulta 1: " . $conn->error);
        }
        $stmt1->bind_param("i", $id);
        if (!$stmt1->execute()) {
          throw new Exception("Error al ejecutar la consulta 1: " . $stmt1->error);
        }
        $stmt1->close();
        $sql2 = "DELETE FROM t_terceros_contactos WHERE id_tercero = ?";
        $stmt2 = $conn->prepare($sql2);
        if (!$stmt2) {
          throw new Exception("Error al preparar la consulta 2: " . $conn->error);
        }
        $stmt2->bind_param("i", $id);
        if (!$stmt2->execute()) {
          throw new Exception("Error al ejecutar la consulta 2: " . $stmt2->error);
        }
        $stmt2->close();
        $sql3 = "DELETE FROM t_terceros_direcciones WHERE id_tercero = ?";
        $stmt3 = $conn->prepare($sql3);
        if (!$stmt3) {
          throw new Exception("Error al preparar la consulta 3: " . $conn->error);
        }
        $stmt3->bind_param("i", $id);
        if (!$stmt3->execute()) {
          throw new Exception("Error al ejecutar la consulta 3: " . $stmt3->error);
        }
        $stmt3->close();
        $conn->commit();

        auditarTercero(
          'DELETE',
          $id,
          $estadoAnterior,
          array(),
          array(
            'modulo' => 'Terceros',
            'evento' => 'Eliminacion de tercero'
          )
        );

        $response = [
          'success' => true,
          'message' => "Eliminación completada correctamente."
        ];
      } catch (Exception $e) {
        $conn->rollback();
        $response = [
          'success' => false,
          'message' => "Error: " . $e->getMessage()
        ];
      } finally {
        $conn->close();
      }

      echo json_encode($response);
    }

    break;
  case 's_terceros_direcciones':
    $id_tercero = $_POST['id_tercero'];
    $sql = "select 
              t.id,
              t.codigo_pais ,
              p.descripcion as nombre_pais,
              t.codigo_dpto ,
              d.departamento  as nombre_dpto,
              t.codigo_mpio ,
              m.municipio  as nombre_mpio,
              t.direccion,
              t.telefono,
              t.celular ,
              t.email ,
              t.principal
            from t_terceros_direcciones t
            inner join t_paises p on p.pais = t.codigo_pais 
            inner join t_departamentos d on d.codigo_dpto = t.codigo_dpto  and d.codigo_pais  = t.codigo_pais 
            inner join t_municipios m on m.codigo_pais = t.codigo_pais and m.codigo_dpto  = t.codigo_dpto and m.codigo =t.codigo_mpio
            where 
              t.id_tercero  = $id_tercero";
    $datos = GenerarArray($sql);
    echo json_encode(array(
      'error' => false,
      'data' => $datos
    ));
    break;
  case 's_terceros_contactos':
    $id_tercero = $_POST['id_tercero'];
    $sql = "select  c.* from t_terceros_contactos c where  c.id_tercero  = $id_tercero";
    $datos = GenerarArray($sql);
    echo json_encode(array(
      'error' => false,
      'data' => $datos
    ));
    break;
  case 's_auditoria_tercero':
    $idTercero = isset($_POST['id_tercero']) ? intval($_POST['id_tercero']) : 0;

    if ($idTercero <= 0) {
      echo json_encode(array(
        'error' => true,
        'message' => 'Tercero no valido',
        'data' => array()
      ));
      break;
    }

    $sql = "SELECT
              a.id,
              a.fecha,
              a.accion,
              a.tabla_afectada,
              a.id_afectado,
              a.detalle,
              IFNULL(TRIM(CONCAT(u.nombres, ' ', u.apellidos)), u.usuario) AS usuario
            FROM t_auditoria a
            LEFT JOIN t_usuarios u ON u.id = a.usuario_id
            WHERE
              (a.tabla_afectada = 't_terceros' AND a.id_afectado = $idTercero)
              OR (JSON_VALID(a.detalle) AND JSON_UNQUOTE(JSON_EXTRACT(a.detalle, '$.id_tercero')) = '$idTercero')
              OR (JSON_VALID(a.detalle) AND JSON_UNQUOTE(JSON_EXTRACT(a.detalle, '$.estado_anterior.tercero.id')) = '$idTercero')
              OR (JSON_VALID(a.detalle) AND JSON_UNQUOTE(JSON_EXTRACT(a.detalle, '$.estado_nuevo.tercero.id')) = '$idTercero')
            ORDER BY a.fecha DESC, a.id DESC";

    $auditoria = GenerarArray($sql);
    echo json_encode(array(
      'error' => false,
      'data' => $auditoria
    ));
    break;
  case 'listar_terceros':
    $filtro = $_POST['filtro'];
    $sql = "SELECT
              t.*,
              tt.descripcion as tipo_tercero
            FROM
              t_terceros t
            LEFT JOIN t_tipos_terceros tt ON
              tt.id = t.id_tipo_tercero";
    if ($filtro != '') {
      $sql .= " where CONCAT(nit, tratamiento,razon_comercial) like '%$filtro%'";
    }
    $datos = GenerarArray($sql);
    echo json_encode(array(
      'error' => false,
      'data' => $datos
    ));
    break;

  case 'listar_vendedores':
    $sql = "SELECT t.id, t.razon_comercial, t.tratamiento, t.nit
            FROM t_terceros t
            INNER JOIN t_tipos_terceros tt ON t.id_tipo_tercero = tt.id
            WHERE tt.es_vendedor = 'S' AND t.estado = 'A'
            ORDER BY t.razon_comercial ASC";
    $datos = GenerarArray($sql);
    echo json_encode(array(
      'error' => false,
      'data' => $datos
    ));
    break;

  case 'cargar_importados':
    $data = json_decode($_POST['datos'], true);
    $resultados = [
      'nuevos' => [],
      'existentes' => [],
      'errores' => []
    ];
    foreach ($data as $key => $row) {
      //Datos generales.
      $nit = $row['NIT'];
      $id = 0;
      $sql_check = GenerarArray("SELECT * FROM t_terceros WHERE nit = '$nit'", '');
      if (count($sql_check) > 0) {
        $resultados['existentes'][] = $nit;
      } else {
        $datosGen = [];
        $datosGen['primer_nombre'] = mb_strtoupper(mb_substr(trim($row['primerNombre']), 0, 30, 'UTF-8'), 'UTF-8');
        $datosGen['segundo_nombre'] = mb_strtoupper(mb_substr(trim($row['segundoNombre']), 0, 30, 'UTF-8'), 'UTF-8');
        $datosGen['primer_apellido'] = mb_strtoupper(mb_substr(trim($row['primerApellido']), 0, 30, 'UTF-8'), 'UTF-8');
        $datosGen['segundo_apellido'] = mb_strtoupper(mb_substr(trim($row['segundoApellido']), 0, 30, 'UTF-8'), 'UTF-8');
        $datosGen['tratamiento'] = strtoupper($datosGen['primer_nombre'] . " " . $datosGen['segundo_nombre'] . " " . $datosGen['primer_apellido'] . " " . $datosGen['segundo_apellido']);
        $datosGen['razon_comercial'] = strtoupper($row['razonComercial']);
        $datosGen['id_tipo_tercero'] = $row['tipoTercero'];
        $datosGen['cupo_credito'] = $row['cupo'];
        $datosGen['id_tipo_identificacion'] = isset($row['tipoIdentificacion']) ? (int)filter_var($row['tipoIdentificacion'], FILTER_SANITIZE_NUMBER_INT) : 1;
        $datosGen['nit'] = substr(preg_replace('/\D/', '', $row['NIT']), 0, 18);
        $datosGen['nit_digito'] = isset($row['digitoVerificacion']) ? (int)filter_var($row['digitoVerificacion'], FILTER_SANITIZE_NUMBER_INT) : 0;
        $datosGen['id_perfil_tributario'] = $row['perfilTributario'];
        $datosGen['id_regimen'] = $row['regimen'];
        $datosGen['email_fe'] = strtoupper($row['emailFe']);
        $datosGen['id_condicion'] = 1;
        $datosGen['id_condicion_pago_prov'] = 3;
        $datosGen['lista_precios'] = 1;
        $id = insertReturnId('t_terceros', $datosGen, true);
        if ($id > 0) {
          //Datos de direccion
          $datosDir = [];
          $datosDir['id_tercero'] = $id;
          $datosDir['codigo_pais'] = $row['pais'];
          $datosDir['codigo_dpto'] = $row['departamento'];
          $datosDir['codigo_mpio'] = $row['ciudad'];
          $datosDir['direccion'] = strtoupper($row['direccion']);
          $datosDir['telefono'] =  mb_strtoupper(mb_substr(trim($row['telefono']), 0, 20, 'UTF-8'), 'UTF-8');
          $datosDir['celular'] = mb_strtoupper(mb_substr(trim($row['celular']), 0, 20, 'UTF-8'), 'UTF-8');
          $datosDir['email'] = strtoupper($row['email']);
          $datosDir['principal'] = 1;
          insertReturnId('t_terceros_direcciones', $datosDir, false);
          $resultados['nuevos'][] = $nit;
        } else {
          $resultados['errores'][] = $nit;
        }
      }
    } //for

    echo json_encode(array(
      'error' => false,
      'message' => 'Registros insertados/actualizados.',
      'datos' => $resultados
    ));
    break;
  case "cargar_ter_nit":
    $nit = isset($_POST["nit"]) ? $_POST['nit'] : '';

    if ($nit == '') {
      echo json_encode(array(
        'ok' => false,
        'message' => 'No se envio el nit',
        'data' => []
      ));
      return;
    }

    $sql = "select 
              t.primer_nombre,
              t.primer_apellido,
              t.segundo_nombre,
              t.segundo_apellido,
              t.id ,
              t.nit,
              CASE WHEN id_tipo_identificacion = 1 then razon_comercial else 
                    CONCAT(primer_nombre,' ',segundo_nombre,' ',primer_apellido,' ',segundo_apellido)
              END AS tercero,
              d.id as id_tercero_dir
            from 
            t_terceros  t
            inner join t_terceros_direcciones d on d.id_tercero = t.id
            where nit = $nit limit 1 ";
    $tercero = GenerarArray($sql);

    if (count($tercero) == 0) {
      echo json_encode(array(
        'ok' => false,
        'message' => 'No se encontro el nit',
        'data' => []
      ));
      return;
    }
    echo json_encode(array(
      'ok' => true,
      'message' => 'Success',
      'data' => $tercero
    ));

    break;
  case "cartera_nit":
    $nit = isset($_POST["nit"]) ? $_POST["nit"] : 0;

    if (!isset($_POST["nit"])) {
      echo json_encode(array(
        'ok' => false,
        'message' => 'No se envio el nit'
      ));
      return;
    }
    $sql = "
        select 
          nit,
          CONCACT(primer_nombre,' ',segundo_nombre,' ',primer_apellido,' ',segundo_apellido) as nombres,
          razon_comercial ,
          sum(total_vencida) as total_vencida,
          sum(total_sin_vencida) as total_sin_vencida,
          sum(c_30_60) as c_30_60,
          sum(c_61_90) as c_61_90,
          sum(c_91_120) as c_91_120,
          sum(c_120) as c_120
        from v_cartera 
        WHERE nit=$nit
        group by
        nit,
        primer_nombre,
        segundo_nombre,
        primer_apellido,
        segundo_apellido,
        razon_comercial
      ";

    $cartera = GenerarArray($sql, '');

    echo json_encode($cartera);

    break;
  case "s_tercero_basico":
    $nit = isset($_POST["nit"]) ? $_POST["nit"] : 0;

    if (!isset($_POST["nit"])) {
      echo json_encode(array(
        'ok' => false,
        'message' => 'No se envio el nit'
      ));
      return;
    }
    $sql = "SELECT 
              t.id as id_tercero,
              t.id_tipo_identificacion,
              t.nit,
              t.nit_digito,
              t.primer_apellido,
              t.primer_nombre,
              t.segundo_apellido,
              t.segundo_nombre,
              t.tratamiento,
              t.razon_comercial,
              t.email_fe,
              d.id as id_direccion,
              d.codigo_pais ,
              d.codigo_dpto ,
              d.codigo_mpio ,
              d.direccion ,
              d.email ,
              d.telefono ,
              d.celular 
            FROM t_terceros t 
            left join t_terceros_direcciones d on d.id_tercero  = t.id and d.principal  = 1
            where 
              t.nit  = '$nit'";
    $tercero = GenerarArray($sql, '');
    echo json_encode($tercero);
    break;
}