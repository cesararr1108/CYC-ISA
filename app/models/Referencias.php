<?php

use App\Controllers\GenerarAcuses;

session_start();
require_once('bd.php');
require_once('funciones.php');
$conn = conectar();

function upImage($data, $id)
{
  // Manejo de archivos (fotos)
  $result = Query("SELECT imagen FROM t_referencias_img WHERE id_referencia = $id;");
  if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
      $imagenPath = "../../assets/img/referencias/" . $row['imagen'];
      if (file_exists($imagenPath)) {
        unlink($imagenPath);
      }
      Query("DELETE FROM t_referencias_img WHERE id_referencia = $id;");
    }
  }

  if (!empty($_FILES['file']['name'][0])) {
    $newFileName = '';
    $countfiles = count($_FILES['file']['name']);
    $ruta = "../../assets/img/referencias/";

    if (!file_exists($ruta)) {
      mkdir($ruta, 0777, true);
    }


    for ($index = 0; $index < $countfiles; $index++) {
      $fileTmpPath = $_FILES['file']['tmp_name'][$index];
      $fileName = $_FILES['file']['name'][$index];
      $fileSize = $_FILES['file']['size'][$index];
      $fileType = $_FILES['file']['type'][$index];

      $img = $ruta . $fileName;
      if (file_exists($img)) {
        break;
      }
      $allowedFileTypes = ['image/jpeg', 'image/png', 'image/gif']; // Tipos de archivo permitidos
      $maxFileSize = 10 * 1024 * 1024; // Tamaño máximo de archivo (10MB)

      if (!in_array($fileType, $allowedFileTypes)) {
        echo json_encode([
          'error' => true,
          'message' => 'Archivo no permitido: ' . $fileName
        ]);
        break;
      }

      if ($fileSize > $maxFileSize) {
        echo json_encode([
          'error' => true,
          'message' => 'El archivo ' . $fileName . ' es demasiado grande.'
        ]);
        break;
      }
      $newFileName = uniqid('img_', true) . '.' . pathinfo($fileName, PATHINFO_EXTENSION);
      $destino = $ruta . $newFileName;

      if (move_uploaded_file($fileTmpPath, $destino)) {
        Query("insert into t_referencias_img (id_referencia, imagen) values ($id, '$newFileName');");
      } else {
        echo json_encode([
          'error' => true,
          'message' => 'Error al subir el archivo ' . $fileName
        ]);
        break;
      }
    }
  }
  // Fin manejo de fotos
}

function obtenerEstadoReferenciaAuditoria($idReferencia)
{
  $idReferencia = (int)$idReferencia;
  if ($idReferencia <= 0) {
    return array();
  }

  $referencia = GenerarArray("SELECT * FROM t_referencias WHERE id = $idReferencia");
  $listas = GenerarArray("SELECT id, lista, tipo_lista, precio, precio_2, precio_3, precio_4 FROM t_referencias_listas WHERE id_referencia = $idReferencia ORDER BY id");
  $ean = GenerarArray("SELECT id, ean FROM t_referencias_ean WHERE id_referencia = $idReferencia ORDER BY id");
  $ubicaciones = GenerarArray("SELECT id_ubicacion FROM t_ubicaciones_referencias WHERE id_referencia = $idReferencia ORDER BY id_ubicacion");
  $imagenes = GenerarArray("SELECT imagen FROM t_referencias_img WHERE id_referencia = $idReferencia ORDER BY id");

  return array(
    'referencia' => count($referencia) > 0 ? $referencia[0] : array(),
    'listas_precios' => $listas ? $listas : array(),
    'codigos_ean' => $ean ? $ean : array(),
    'ubicaciones' => $ubicaciones ? $ubicaciones : array(),
    'imagenes' => $imagenes ? $imagenes : array()
  );
}

function esArrayAsociativoAuditoria($arr)
{
  if (!is_array($arr)) {
    return false;
  }

  return array_keys($arr) !== range(0, count($arr) - 1);
}

function normalizarValorAuditoria($valor)
{
  if (is_array($valor)) {
    if (esArrayAsociativoAuditoria($valor)) {
      ksort($valor);
    }

    foreach ($valor as $k => $v) {
      $valor[$k] = normalizarValorAuditoria($v);
    }
  }

  return $valor;
}

function sonIgualesAuditoria($a, $b)
{
  $na = normalizarValorAuditoria($a);
  $nb = normalizarValorAuditoria($b);
  return json_encode($na, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) === json_encode($nb, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function construirCambiosAuditoria($anterior, $nuevo)
{
  // Caso base: valores escalares u objetos/arrays no comparables por estructura
  if (!is_array($anterior) || !is_array($nuevo)) {
    $changed = !sonIgualesAuditoria($anterior, $nuevo);
    return array(
      'changed' => $changed,
      'anterior' => $changed ? $anterior : array(),
      'nuevo' => $changed ? $nuevo : array()
    );
  }

  // Si alguno es asociativo, comparar por llave y propagar diff recursivo
  if (esArrayAsociativoAuditoria($anterior) || esArrayAsociativoAuditoria($nuevo)) {
    $keys = array_unique(array_merge(array_keys($anterior), array_keys($nuevo)));
    $diffAnterior = array();
    $diffNuevo = array();
    $changed = false;

    foreach ($keys as $k) {
      $va = array_key_exists($k, $anterior) ? $anterior[$k] : null;
      $vn = array_key_exists($k, $nuevo) ? $nuevo[$k] : null;

      $sub = construirCambiosAuditoria($va, $vn);
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

  // Si ambos son listas (arrays indexados), se comparan completos
  $changed = !sonIgualesAuditoria($anterior, $nuevo);
  return array(
    'changed' => $changed,
    'anterior' => $changed ? $anterior : array(),
    'nuevo' => $changed ? $nuevo : array()
  );
}

function auditarReferencia($accion, $idReferencia, $estadoAnterior, $estadoNuevo, $extra = array(), $conn = null)
{
  $diff = construirCambiosAuditoria($estadoAnterior, $estadoNuevo);

  // Si no hay cambios reales, no insertar auditoria
  if (!$diff['changed']) {
    return;
  }

  $detalle = array(
    'id_referencia' => (int)$idReferencia,
    'estado_anterior' => $diff['anterior'],
    'estado_nuevo' => $diff['nuevo']
  );

  if (is_array($extra) && count($extra) > 0) {
    $detalle['extra'] = $extra;
  }

  registrarAuditoria($accion, 't_referencias', (int)$idReferencia, $detalle, null, $conn);
}


if (!sessionExists()) {
  echo json_encode(array(
    'error' => true,
    'message' => 'La sesion se ha cerrado, por favor ingrese nuevamente'
  ));
  return;
}

switch ($_POST["op"]) {
  case 's_iva_perfil':
    $id_perfil = $_POST['id_perfil'];
    $q = GenerarArray("select 
                            p.id_iva as id,
                            i.porcentaje_iva
                        from t_perfiles_contables p
                        inner join t_iva i on i.id  = p.id_iva 
                        where 
                            p.id = $id_perfil
                        order 
                            by p.id");
    echo json_encode($q);
    break;
  case 's_iva':
    $q = GenerarArray("select id,porcentaje_iva from t_iva");
    echo json_encode($q);
    break;
  case 's_icui':
    $q = GenerarArray("select id,porcentaje_icui from t_icui");
    echo json_encode($q);
    break;
  case 'g_referencia':
    $data = $_POST;

    //validar existencia de codigo dado que no se puede repetir
    $codigo = $data["codigo"];
    $existeCodigo = GenerarArray("select id from t_referencias where codigo = '$codigo'");
    if (count($existeCodigo) > 0) {
      echo json_encode(array(
        'error' => true,
        'message' => 'El código ya existe, por favor ingrese uno diferente'
      ));
      return;
    }

    $ubicaciones = [];
    if (isset($_POST["ubicaciones"])) {
      $ubicaciones = json_decode($_POST["ubicaciones"], true);
      unset($data["ubicaciones"]);
    }
    $arrayListas = json_decode($data['listas'], true);
    $arrayEan = json_decode($data['ean'], true);
    unset($data['listas']);
    unset($data['ean']);
    //valido si hay comisiones 
    if (isset($data["comision"]) && $data["comision"] == 'S') {

      if (isset($data["comisiones"])) {
        $comisiones = json_decode($data["comisiones"], true);
        if (is_array($comisiones) && count($comisiones) > 0) {
          foreach ($comisiones as $c) {
            //lo agrego como propiedad en arrayListas , en la misma lista 
            foreach ($arrayListas as $k => $al) {
              if ($al['lista'] == 1) {
                $arrayListas[$k]['comision'] = $c['comision'];
                $arrayListas[$k]['calculo_comision'] = $c['calculo_comision'];
              }
            }
          }
        }
      }
    }
    $data["usuario_creacion"] = $_SESSION["Ses_id"];
    $estadoAnterior = array();
    $id = insertReturnId('t_referencias', $data, true);
    if ($id > 0) {

      /*Insercion de listas de precios*/
      if (is_array($arrayListas)) {
        if (count($arrayListas) > 0) {
          foreach ($arrayListas as $item) {
            $dataListas = [];
            $idItem = $item['id'];
            $eliminar = $item['eliminar'];

            if ($eliminar == '') {
              if ($idItem == 0) { // Insertar
                $dataListas['lista'] = $item['lista'];
                $dataListas['tipo_lista'] = $item['tipo'];
                $dataListas['precio'] = $item['precio'];
                $dataListas['precio_2'] = $item['precio_2'];
                $dataListas['precio_3'] = $item['precio_3'];
                $dataListas['precio_4'] = $item['precio_4'];
                $dataListas['id_referencia'] = $id;

                insertReturnId('t_referencias_listas', $dataListas, false);
              } else { // Actualizar
                $dataListas['tipo_lista'] = $item['tipo'];
                $dataListas['precio'] = $item['precio'];
                $dataListas['precio_2'] = $item['precio_2'];
                $dataListas['precio_3'] = $item['precio_3'];
                $dataListas['precio_4'] = $item['precio_4'];

                update('t_referencias_listas', $dataListas, $idItem);
              }
            } else { // Eliminar
              $sql = 'DELETE FROM t_referencias_listas WHERE id = ' . $idItem;
              Query($sql);
            }
          }
        }
      }
      /*final de insercion de listas de precios*/
      /*Insersion de codigos ean*/
      if (is_array($arrayEan)) {
        if (count($arrayEan) > 0) {
          foreach ($arrayEan as $item) {
            $dataEan = [];
            $idItem = $item['id'];
            $eliminar = $item['eliminar'];
            if ($eliminar == '') {
              if ($idItem == 0) { //Insertar
                $dataEan['ean'] = $item['ean'];
                $dataEan['id_referencia'] = $id;
                insertReturnId('t_referencias_ean', $dataEan, false);
              } else { //Actualizar
                $dataEan['ean'] = $item['ean'];
                update('t_referencias_ean', $dataEan, $idItem);
              }
            } else { //eliminar
              $sql = 'delete from t_referencias_ean where id = ' . $idItem;
              Query($sql);
            }
          }
        }
      }
      /*final de insercion de codigos ean*/
      upImage($data, $id);

      /** inserta la ubicacion */
      if (is_array($ubicaciones)) {
        foreach ($ubicaciones as $u) {

          $data_ubi = [
            'id_referencia' => $id,
            'id_ubicacion' => (int)$u
          ];

          insertReturnId(
            't_ubicaciones_referencias',
            $data_ubi,
            false
          );
        }
      }

      $estadoNuevo = obtenerEstadoReferenciaAuditoria($id);
      auditarReferencia(
        'CREAR',
        $id,
        $estadoAnterior,
        $estadoNuevo,
        array(
          'modulo' => 'Referencias',
          'evento' => 'Creacion de referencia'
        ),
        $conn
      );


      echo json_encode(array(
        'error' => false,
        'message' => 'Referencia creada correctamente.',
        'listas' => $arrayListas,
        'id' => $id
      ));
    } else {
      echo json_encode(array(
        'error' => true,
        'message' => 'Error al crear el registro',
        'id' => 0
      ));
    }
    break;
  case 'u_referencia':
    $data = $_POST;

    $ubicaciones = [];

    if (isset($_POST["ubicaciones"])) {
      $ubicaciones = json_decode($_POST["ubicaciones"], true);
      unset($data["ubicaciones"]);
    }


    $arrayListas = json_decode($data['listas'], true);
    $arrayEan = json_decode($data['ean'], true);
    unset($data['listas']);
    unset($data['ean']);

    //valido si hay comisiones 
    if (isset($data["comision"]) && $data["comision"] == 'S') {

      if (isset($data["comisiones"])) {
        $comisiones = json_decode($data["comisiones"], true);
        unset($data["comisiones"]);
        if (is_array($comisiones) && count($comisiones) > 0) {
          foreach ($comisiones as $c) {
            //lo agrego como propiedad en arrayListas , en la misma lista 
            foreach ($arrayListas as $k => $al) {
              if ($al['lista'] == $c['lista']) {
                $arrayListas[$k]['comision1'] = $c['comision1'];
                $arrayListas[$k]['comision2'] = $c['comision2'];
                $arrayListas[$k]['comision3'] = $c['comision3'];
                $arrayListas[$k]['comision4'] = $c['comision4'];
                $arrayListas[$k]['calculo_comision'] = $c['calculo_comision'];
              }
            }
          }
        }
      }
    }
    $data["usuario_creacion"] = $_SESSION["Ses_id"];
    $estadoAnterior = obtenerEstadoReferenciaAuditoria($data["id"]);
    $update = update('t_referencias', $data, $data["id"]);

    if ($update) {



      $id = $data["id"];
      /*Insercion de listas de precios*/
      foreach ($arrayListas as $item) {
        $dataListas = [];
        $idItem = $item['id'];
        $eliminar = $item['eliminar'];

        if ($eliminar == '') {
          if ($idItem == 0) { // Insertar
            $dataListas['lista'] = $item['lista'];
            $dataListas['tipo_lista'] = $item['tipo'];
            $dataListas['precio'] = $item['precio'];
            $dataListas['precio_2'] = $item['precio_2'];
            $dataListas['precio_3'] = $item['precio_3'];
            $dataListas['precio_4'] = $item['precio_4'];
            $dataListas['id_referencia'] = $id;
            $dataListas['comision1'] = 0;
            $dataListas['comision2'] = 0;
            $dataListas['comision3'] = 0;
            $dataListas['comision4'] = 0;

            if (isset($item['comision1'])) {
              $dataListas['comision1'] = $item['comision1'];
            }

            if (isset($item['comision2'])) {
              $dataListas['comision2'] = $item['comision2'];
            }
            if (isset($item['comision3'])) {
              $dataListas['comision3'] = $item['comision3'];
            }
            if (isset($item['comision4'])) {
              $dataListas['comision4'] = $item['comision4'];
            }
            if (isset($item['calculo_comision'])) {
              $dataListas['calculo_comision'] = $item['calculo_comision'];
            }

            insertReturnId('t_referencias_listas', $dataListas, false);
          } else { // Actualizar
            $dataListas['tipo_lista'] = $item['tipo'];
            $dataListas['precio'] = $item['precio'];
            $dataListas['precio_2'] = $item['precio_2'];
            $dataListas['precio_3'] = $item['precio_3'];
            $dataListas['precio_4'] = $item['precio_4'];
            $dataListas['comision1'] = 0;
            $dataListas['comision2'] = 0;
            $dataListas['comision3'] = 0;
            $dataListas['comision4'] = 0;

            if (isset($item['comision1'])) {
              $dataListas['comision1'] = $item['comision1'];
            }
            if (isset($item['comision2'])) {
              $dataListas['comision2'] = $item['comision2'];
            }
            if (isset($item['comision3'])) {
              $dataListas['comision3'] = $item['comision3'];
            }
            if (isset($item['comision4'])) {
              $dataListas['comision4'] = $item['comision4'];
            }
            if (isset($item['calculo_comision'])) {
              $dataListas['calculo_comision'] = $item['calculo_comision'];
            }

            update('t_referencias_listas', $dataListas, $idItem);
          }
        } else { // Eliminar
          $sql = 'DELETE FROM t_referencias_listas WHERE id = ' . $idItem;
          Query($sql);
        }
      }
      /*final de insercion de listas de precios*/
      /*Insersion de codigos ean*/
      if (is_array($arrayEan)) {
        if (count($arrayEan) > 0) {
          foreach ($arrayEan as $item) {
            $dataEan = [];
            $idItem = $item['id'];
            $eliminar = $item['eliminar'];
            if ($eliminar == '') {
              if ($idItem == 0) { //Insertar
                $dataEan['ean'] = $item['ean'];
                $dataEan['id_referencia'] = $id;
                insertReturnId('t_referencias_ean', $dataEan, false);
              } else { //Actualizar
                $dataEan['ean'] = $item['ean'];
                update('t_referencias_ean', $dataEan, $idItem);
              }
            } else { //eliminar
              $sql = 'delete from t_referencias_ean where id = ' . $idItem;
              Query($sql);
            }
          }
        }
      }
      /*final de insercion de codigos ean*/

      upImage($data, $id);
      /** inserta la ubicacion */
      $del = Query(
        "DELETE FROM t_ubicaciones_referencias WHERE id_referencia = " . (int)$id
      );

      if (is_array($ubicaciones)) {
        foreach ($ubicaciones as $u) {

          $data_ubi = [
            'id_referencia' => $id,
            'id_ubicacion' => (int)$u
          ];

          insertReturnId(
            't_ubicaciones_referencias',
            $data_ubi,
            false
          );
        }
      }




      $estadoNuevo = obtenerEstadoReferenciaAuditoria($id);
      auditarReferencia(
        'UPDATE',
        $id,
        $estadoAnterior,
        $estadoNuevo,
        array(
          'modulo' => 'Referencias',
          'evento' => 'Actualizacion de referencia'
        ),
        $conn
      );


      echo json_encode(array(
        'error' => false,
        'message' => 'Referencia actualizada correctamente.',
        'data' => $data
      ));
    } else {
      echo json_encode(array(
        'error' => true,
        'message' => 'No se pudo actualizar la referencia'
      ));
    }
    break;
  case 's_referencia':
    $codigo = $_POST['codigo'];
    $bodega = isset($_POST["bodega"]) ? $_POST["bodega"]  : (!empty($_SESSION["Ses_id_bodega"]) ? $_SESSION["Ses_id_bodega"] : 0);
    $sql = "SELECT 
            r.id ,
            r.codigo ,
            r.descripcion,
            r.descripcion_larga,
            r.tipo,
            r.id_iva ,
            ifnull(r.impoconsumo,0) as impoconsumo,
            ifnull(r.ibua,0) as ibua,
            r.id_icui,
            r.controlado,
            r.institucional,
            r.regulado,
            ifnull(r.costo_unitario,0) as costo_unitario,
            ifnull(r.precio_unitario,0) as precio_unitario,
            r.codigo_cum,
            r.id_categoria_1,
            r.id_categoria_2,
            r.id_categoria_3,
            r.id_categoria_4,
            ifnull(r.ultimo_costo,r.costo_unitario) as ultimo_costo,
            r.maneja_lote,
            ifnull(r.ultimo_costo_compra,0) as ultimo_costo_compra,
            ifnull(r.ultimo_costo_venta,0) as ultimo_costo_venta,
            ifnull(r.dias_reposicion,0) as dias_reposicion,
            r.usuario_creacion,
            r.fecha_creacion,
            r.id_marca ,
            r.stock_minimo,
            r.stock_maximo,
            r.codigo_invima,
            r.perfil_contable,
            r.und_1,
            r.cant_1,
            r.und_2,
            r.cant_2,
            r.und_3,
            r.cant_3,
            r.und_4,
            r.cant_4,
            ifnull(r.conversion,1) as conversion,
            r.und_compra,
            r.costo_ultimo,
            r.no_venta,
            r.activo,
            ifnull(v.promedio,0) as costo_promedio,
            ifnull(r.comision,'N') as comision,
            r.ium,
            r.numero_expediente,
            -- Stock
            ifnull(s.stock,0) as stock_actual
    from t_referencias r
    left join v_costo_promedio v on v.id_referencia=r.id and v.mes=MONTH(CURRENT_DATE()) and v.ano=YEAR(CURRENT_DATE())
    LEFT JOIN v_stock_total s 
               ON s.id_referencia = r.id
              AND s.id_bodega = $bodega 
              AND s.mes = MONTH(CURRENT_DATE())
              AND s.ano = YEAR(CURRENT_DATE())
    where r.codigo = '$codigo'";
    $datos = GenerarArray($sql);
    echo json_encode(array(
      'error' => false,
      'data' => $datos
    ));
    break;
  case 's_referencia_listas':
    $id = $_POST['id_referencia'];
    $sql = "select * from t_referencias_listas where id_referencia = $id";
    $datos = GenerarArray($sql);
    echo json_encode(array(
      'error' => false,
      'data' => $datos
    ));
    break;
  case 's_referencia_ean':
    $id = $_POST['id_referencia'];
    $sql = "select * from t_referencias_ean where id_referencia = $id";
    $datos = GenerarArray($sql);
    echo json_encode(array(
      'error' => false,
      'data' => $datos
    ));
    break;
  case 's_auditoria_referencia':
    $idReferencia = isset($_POST['id_referencia']) ? intval($_POST['id_referencia']) : 0;

    if ($idReferencia <= 0) {
      echo json_encode(array(
        'error' => true,
        'message' => 'Referencia no valida',
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
              (a.id_afectado = $idReferencia)
              OR (JSON_VALID(a.detalle) AND JSON_UNQUOTE(JSON_EXTRACT(a.detalle, '$.id_referencia')) = '$idReferencia')
              OR (JSON_VALID(a.detalle) AND JSON_UNQUOTE(JSON_EXTRACT(a.detalle, '$.estado_anterior.referencia.id')) = '$idReferencia')
              OR (JSON_VALID(a.detalle) AND JSON_UNQUOTE(JSON_EXTRACT(a.detalle, '$.estado_nuevo.referencia.id')) = '$idReferencia')
            ORDER BY a.fecha DESC, a.id DESC";

    $auditoria = GenerarArray($sql);

    echo json_encode(array(
      'error' => false,
      'data' => $auditoria
    ));
    break;
  case 'g_categoria':
    $id = $_POST['id'];
    $codigo = strtoupper($_POST['codigo']);
    $descripcion = strtoupper($_POST['descripcion']);
    $table_name = "t_referencias_categoria" . $id;
    $sql = "insert into $table_name(codigo,descripcion)values('$codigo','$descripcion');";

    if (Query($sql)) {
      echo json_encode(array(
        'error' => false,
        'message' => 'Categoria creada correctamente.'
      ));
    } else {
      echo json_encode(array(
        'error' => true,
        'message' => 'Error al crear la categoria.'
      ));
    }
    break;
  case 's_categoria':
    $id = $_POST['id'];
    $sql = "select * from t_referencias_categoria" . $id;
    $datos = GenerarArray($sql);
    echo json_encode(array(
      'error' => false,
      'data' => $datos
    ));
    break;
  case 'd_categoria':
    $id_categoria = isset($_POST['id_categoria']) ? intval($_POST['id_categoria']) : 0;
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $result = Query("select count(*) as count from t_referencias where id_categoria_" . $id_categoria . " = $id");

    if ($result && $result->num_rows > 0) {
      $row = $result->fetch_assoc();
      $count = $row['count'];
    } else {
      $count = 0;
    }

    if ($count > 0) {
      echo json_encode(array(
        'error' => true,
        'message' => 'No se puede eliminar la categoría porque tiene referencias asociadas.'
      ));
    } else {
      $deleteResult = Query("delete from t_referencias_categoria" . $id_categoria . " where id = $id");

      if ($deleteResult) {
        echo json_encode(array(
          'error' => false,
          'message' => 'Categoría eliminada correctamente.'
        ));
      } else {
        echo json_encode(array(
          'error' => true,
          'message' => 'Error al eliminar la categoría.'
        ));
      }
    }

    break;
  case 'listar_referencias':

    $filtro = isset($_POST['filtro']) ? $_POST['filtro'] : $_POST['descripcion'];
    $laboratorio = isset($_POST["laboratorio"]) ? $_POST["laboratorio"] : '';
    $criterio1 = isset($_POST["criterio1"]) ? trim($_POST["criterio1"]) : '';
    $criterio2 = isset($_POST["criterio2"]) ? trim($_POST["criterio2"]) : '';
    $criterio3 = isset($_POST["criterio3"]) ? trim($_POST["criterio3"]) : '';
    $criterio4 = isset($_POST["criterio4"]) ? trim($_POST["criterio4"]) : '';
    $controlado = isset($_POST["controlado"]) ? trim($_POST["controlado"]) : '';
    $institucional = isset($_POST["institucional"]) ? trim($_POST["institucional"]) : '';
    $regulado = isset($_POST["regulado"]) ? trim($_POST["regulado"]) : '';
    $stock = isset($_POST["con_stock"]) ? trim($_POST["con_stock"]) : '';
    $bodega = isset($_POST["bodega"]) ? $_POST["bodega"]  : (!empty($_SESSION["Ses_id_bodega"]) ? $_SESSION["Ses_id_bodega"] : 0);
    $lista = isset($_POST["lista"]) ? $_POST["lista"] : 1;
    $estado = isset($_POST["estado"]) ? intval($_POST["estado"]) : 1;
    $LimiteRegistros = isset($_POST["LimiteRegistros"]) ? intval($_POST["LimiteRegistros"]) : 20;

    $ean = GenerarArray("select  id_referencia from t_referencias_ean where ean ='$filtro' limit 1", '');

    $sql = "SELECT
            CONCAT(t.codigo, ' - ', IFNULL(t.descripcion,''), ' - ', IFNULL(m.descripcion,'')) AS value, 
            t.id,
            t.codigo,
            t.descripcion,
            IFNULL(t.descripcion_larga, '') AS descripcion_larga,
            t.tipo,
            t.id_iva,
            i.porcentaje_iva,
            IFNULL(t.impoconsumo, 0) AS impoconsumo,
            IFNULL(t.ibua, 0) AS ibua,
            IFNULL(t.id_icui, 0) AS id_icui,
            IFNULL(t.controlado, 'N') AS controlado,
            IFNULL(t.institucional, 'N') AS institucional,
            IFNULL(t.regulado, 'N') AS regulado,
            IFNULL(t.costo_unitario, 0) AS costo_unitario,
            IFNULL(t.precio_unitario, 0) AS precio_unitario,
            IFNULL(t.codigo_cum, '') AS codigo_cum,
            IFNULL(t.id_categoria_1, 0) AS id_categoria_1,
            IFNULL(t.id_categoria_2, 0) AS id_categoria_2,
            IFNULL(t.id_categoria_3, 0) AS id_categoria_3,
            IFNULL(t.id_categoria_4, 0) AS id_categoria_4,
            IFNULL(t.ultimo_costo, 0) AS ultimo_costo,
            IFNULL(t.maneja_lote, 0) AS maneja_lote,
            IFNULL(t.ultimo_costo_compra, 0) AS ultimo_costo_compra,
            IFNULL(t.ultimo_costo_venta, 0) AS ultimo_costo_venta,
            IFNULL(t.dias_reposicion, 0) AS dias_reposicion,
            t.usuario_creacion,
            t.fecha_creacion,
            IFNULL(t.id_marca, 0) AS id_marca,
            IFNULL(t.stock_minimo, 0) AS stock_minimo,
            IFNULL(t.stock_maximo, 0) AS stock_maximo,
            IFNULL(t.codigo_invima, '') AS codigo_invima,
            IFNULL(t.perfil_contable, 0) AS perfil_contable,
            IFNULL(t.und_1, '') AS und_1,
            IFNULL(t.cant_1, 0) AS cant_1,
            IFNULL(t.und_2, '') AS und_2,
            IFNULL(t.cant_2, 0) AS cant_2,
            IFNULL(t.und_3, '') AS und_3,
            IFNULL(t.cant_3, 0) AS cant_3,
            IFNULL(t.und_4, '') AS und_4,
            IFNULL(t.cant_4, 0) AS cant_4,
            IFNULL(t.conversion, 0) AS conversion,
            IFNULL(t.und_compra, '') AS und_compra,
            IFNULL(i.porcentaje_iva, 0) AS iva,
            IFNULL(m.descripcion, '') AS laboratorio,
            
            -- Precios condicionados a la lista solicitada
            IFNULL(ROUND((CASE WHEN l.tipo_lista = 'PF' THEN l.precio / ((i.porcentaje_iva / 100) + 1) WHEN l.tipo_lista = 'CP' THEN cp.promedio * ((l.precio / 100) + 1) WHEN l.tipo_lista = 'CU' THEN t.ultimo_costo_compra * ((l.precio / 100) + 1) END), 2), 0) AS precio,
            IFNULL(ROUND((CASE WHEN l.tipo_lista = 'PF' THEN l.precio WHEN l.tipo_lista = 'CP' THEN (cp.promedio * ((l.precio / 100) + 1)) * ((i.porcentaje_iva / 100) + 1) WHEN l.tipo_lista = 'CU' THEN (t.ultimo_costo_compra * ((l.precio / 100) + 1)) * ((i.porcentaje_iva / 100) + 1) END), 2), 0) AS precion,
            
            IFNULL(ROUND((CASE WHEN l.tipo_lista = 'PF' THEN l.precio_2 / ((i.porcentaje_iva / 100) + 1) WHEN l.tipo_lista = 'CP' THEN cp.promedio * ((l.precio_2 / 100) + 1) WHEN l.tipo_lista = 'CU' THEN t.ultimo_costo_compra * ((l.precio_2 / 100) + 1) END), 2), 0) AS precio2,
            IFNULL(ROUND((CASE WHEN l.tipo_lista = 'PF' THEN l.precio_2 WHEN l.tipo_lista = 'CP' THEN (cp.promedio * ((l.precio_2 / 100) + 1)) * ((i.porcentaje_iva / 100) + 1) WHEN l.tipo_lista = 'CU' THEN (t.ultimo_costo_compra * ((l.precio_2 / 100) + 1)) * ((i.porcentaje_iva / 100) + 1) END), 2), 0) AS precion2,

            IFNULL(ROUND((CASE WHEN l.tipo_lista = 'PF' THEN l.precio_3 / ((i.porcentaje_iva / 100) + 1) WHEN l.tipo_lista = 'CP' THEN cp.promedio * ((l.precio_3 / 100) + 1) WHEN l.tipo_lista = 'CU' THEN t.ultimo_costo_compra * ((l.precio_3 / 100) + 1) END), 2), 0) AS precio3,
            IFNULL(ROUND((CASE WHEN l.tipo_lista = 'PF' THEN l.precio_3 WHEN l.tipo_lista = 'CP' THEN (cp.promedio * ((l.precio_3 / 100) + 1)) * ((i.porcentaje_iva / 100) + 1) WHEN l.tipo_lista = 'CU' THEN (t.ultimo_costo_compra * ((l.precio_3 / 100) + 1)) * ((i.porcentaje_iva / 100) + 1) END), 2), 0) AS precion3,

            IFNULL(ROUND((CASE WHEN l.tipo_lista = 'PF' THEN l.precio_4 / ((i.porcentaje_iva / 100) + 1) WHEN l.tipo_lista = 'CP' THEN cp.promedio * ((l.precio_4 / 100) + 1) WHEN l.tipo_lista = 'CU' THEN t.ultimo_costo_compra * ((l.precio_4 / 100) + 1) END), 2), 0) AS precio4,
            IFNULL(ROUND((CASE WHEN l.tipo_lista = 'PF' THEN l.precio_4 WHEN l.tipo_lista = 'CP' THEN (cp.promedio * ((l.precio_4 / 100) + 1)) * ((i.porcentaje_iva / 100) + 1) WHEN l.tipo_lista = 'CU' THEN (t.ultimo_costo_compra * ((l.precio_4 / 100) + 1)) * ((i.porcentaje_iva / 100) + 1) END), 2), 0) AS precion4,

            IFNULL(dsc.descuento_total, 0) AS dcto,
            IFNULL(s.stock, 0) AS total_stock,
            IFNULL(cc.cantidad, 0) AS comprometidas,
            IFNULL(s.stock, 0) - IFNULL(cc.cantidad, 0) AS disponible,
            IFNULL(cp.promedio, 0) AS promedio,
            IFNULL(cr1.descripcion, '') AS criterio1,
            IFNULL(cr2.descripcion, '') AS criterio2,
            IFNULL(cr3.descripcion, '') AS criterio3,
            IFNULL(cr4.descripcion, '') AS criterio4,
            $bodega AS id_bodega,
            IFNULL(l.lista, 0) AS lista,
            ti.porcentaje_icui,
            
            CASE 
                WHEN t.und_compra = 1 THEN t.cant_1 
                WHEN t.und_compra = 2 THEN t.cant_2
                WHEN t.und_compra = 3 THEN t.cant_3
                WHEN t.und_compra = 4 THEN t.cant_4
            END AS can_und,
            CASE 
                WHEN t.und_compra = 1 THEN t.und_1 
                WHEN t.und_compra = 2 THEN t.und_2
                WHEN t.und_compra = 3 THEN t.und_3
                WHEN t.und_compra = 4 THEN t.und_4
            END AS des_und_comp,
            
            l.precio AS precio_lista,
            l.precio_2 AS precio_lista_2,
            l.precio_3 AS precio_lista_3,
            l.precio_4 AS precio_lista_4,
            
            IFNULL(sem.semaforo_rojo, 0) AS semaforo_rojo,
            IFNULL(sem.semaforo_naranja, 0) AS semaforo_naranja,
            IFNULL(t.activo, 1) AS activo,
            
            IFNULL(l.calculo_comision, '') AS calculo_comision,
            IFNULL(l.comision1, 0) AS comision1,
            IFNULL(l.comision2, 0) AS comision2,
            IFNULL(l.comision3, 0) AS comision3,
            IFNULL(l.comision4, 0) AS comision4,
            IFNULL(t.comision, 0) AS comision
            
        FROM t_referencias t
        LEFT JOIN t_iva i ON i.id = t.id_iva
        LEFT JOIN t_marcas m ON m.id = t.id_marca
        LEFT JOIN t_icui ti ON ti.id = t.id_icui
        -- AQUÍ ESTÁ EL CAMBIO CLAVE: Unimos directo filtrando la lista de precios que el usuario seleccionó
        LEFT JOIN t_referencias_listas l ON l.id_referencia = t.id AND l.lista = $lista
        
        -- Costo promedio optimizado para el mes y año actual
        LEFT JOIN (
            SELECT 
                id_referencia,
                SUM((IFNULL(cant_ini, 0) + IFNULL(cant_ent, 0)) - IFNULL(cant_sal, 0)) AS stock,
                CASE 
                    WHEN SUM((IFNULL(cant_ini, 0) + IFNULL(cant_ent, 0)) - IFNULL(cant_sal, 0)) > 0 
                    THEN SUM((IFNULL(costo_ini, 0) + IFNULL(costo_ent, 0)) - IFNULL(costo_sal, 0)) / NULLIF(SUM((IFNULL(cant_ini, 0) + IFNULL(cant_ent, 0)) - IFNULL(cant_sal, 0)), 0)
                    ELSE 0 
                END AS promedio
            FROM t_referencias_mov
            WHERE mes = MONTH(CURDATE()) AND ano = YEAR(CURDATE())
            GROUP BY id_referencia
        ) cp ON cp.id_referencia = t.id

        LEFT JOIN t_referencias_categoria1 cr1 ON cr1.id = t.id_categoria_1
        LEFT JOIN t_referencias_categoria1 cr2 ON cr2.id = t.id_categoria_2
        LEFT JOIN t_referencias_categoria1 cr3 ON cr3.id = t.id_categoria_3
        LEFT JOIN t_referencias_categoria1 cr4 ON cr4.id = t.id_categoria_4
        
        -- Stock total filtrado directamente por la bodega del usuario
        LEFT JOIN (
            SELECT 
                m.id_referencia,
                (SUM((m.cant_ini + m.cant_ent) - m.cant_sal) - IFNULL(res.cantidad, 0)) AS stock
            FROM t_referencias_mov m
            LEFT JOIN (
                SELECT id_referencia, id_bodega, SUM(cantidad) AS cantidad
                FROM t_reservas_inventario
                WHERE estado = 1 AND id_bodega = $bodega
                GROUP BY id_referencia, id_bodega
            ) res ON res.id_referencia = m.id_referencia AND res.id_bodega = m.id_bodega
            WHERE m.id_bodega = $bodega 
              AND m.mes = MONTH(CURRENT_DATE()) 
              AND m.ano = YEAR(CURRENT_DATE())
            GROUP BY m.id_referencia
        ) s ON s.id_referencia = t.id

        -- Unidades comprometidas
        LEFT JOIN (
            SELECT id_referencia, id_bodega, SUM(cantidad) AS cantidad
            FROM v_unidades_comprometidas
            WHERE id_bodega = $bodega
            GROUP BY id_referencia, id_bodega
        ) cc ON cc.id_referencia = t.id

        -- Semáforos de lotes unificados en una sola pasada
        LEFT JOIN (
            SELECT 
                id_referencia, 
                MIN(CASE WHEN meses_para_vencer <= 3 THEN meses_para_vencer END) AS semaforo_rojo,
                MIN(CASE WHEN meses_para_vencer > 3 AND meses_para_vencer <= 6 THEN meses_para_vencer END) AS semaforo_naranja
            FROM v_referencias_stock_lotes
            WHERE id_bodega = $bodega 
              AND meses_para_vencer <= 6 
              AND stock > 0
            GROUP BY id_referencia
        ) sem ON sem.id_referencia = t.id

        -- Descuentos / Promociones
        LEFT JOIN (
            SELECT ref.id AS id_referencia, SUM(p.porcentaje_descuento) AS descuento_total
            FROM t_referencias ref
            JOIN t_promociones_referencias pr ON pr.id_referencia = ref.id
            JOIN t_promociones p ON p.id = pr.id_promocion
            WHERE p.estado = 1 
              AND p.fecha_inicio <= CURDATE() 
              AND p.fecha_final >= CURDATE()
              AND (p.id_bodega = 0 OR p.id_bodega = $bodega)
              AND (p.id_lista_precios = 0 OR p.id_lista_precios = $lista)
            GROUP BY ref.id
        ) dsc ON dsc.id_referencia = t.id

        WHERE 
          IFNULL(t.activo, 0) = $estado
          AND (IFNULL(l.lista, 0) = $lista OR IFNULL(l.lista, 0) = 0)";

            // Filtros dinámicos condicionales (igual que tu código original)
    if (count($ean) > 0 && $filtro != '') {
        $sql .= " AND t.id = " . intval($ean[0]["id_referencia"]);
    } else if ($filtro != '') {
        $sql .= " AND (
                    " . filtrosBusquedaReferencias($filtro, 't') . "
                    OR t.id IN (
                        SELECT id_referencia
                        FROM t_referencias_ean
                        WHERE ean LIKE '%$filtro%'
                    )
                ) ";
    }

    if ($stock == 'S') {
        $sql .= " AND (CASE WHEN t.tipo='P' THEN s.stock > 0 ELSE 1=1 END)";
    }

    if ($laboratorio != '') { $sql .= " AND m.descripcion LIKE '%$laboratorio%'"; }
    if ($controlado != '') { $sql .= " AND t.controlado = '" . $controlado . "'"; }
    if ($institucional != '') { $sql .= " AND t.institucional = '" . $institucional . "'"; }
    if ($regulado != '') { $sql .= " AND t.regulado = '" . $regulado . "'"; }
    if ($criterio1 != '') { $sql .= " AND t.criterio1 LIKE '%$criterio1%'"; }
    if ($criterio2 != '') { $sql .= " AND t.criterio2 LIKE '%$criterio2%'"; }
    if ($criterio3 != '') { $sql .= " AND t.criterio3 LIKE '%$criterio3%'"; }
    if ($criterio4 != '') { $sql .= " AND t.criterio4 LIKE '%$criterio4%'"; }

    if($LimiteRegistros>0){
        $sql.="  LIMIT ".$LimiteRegistros;
    }
  

    $datos = GenerarArray($sql);
    echo json_encode(array('error' => false, 'data' => $datos));
    break;
  case "s_lotes":
    $id = $_POST["id"];
    $bodega = $_POST["bodega"];

    $sql = " SELECT 
                r.id,
                r.`codigo`,
                r.`descripcion`,
                r.`descripcion_larga`,
                ifnull(t.`razon_comercial`,'') as laboratorio,
                i.`porcentaje_iva` as iva,
                r.`costo_unitario`,
                r.`und_compra`,
                r.`und_1`,
                r.`und_2`,
                r.`und_3`,
                r.`und_4`,
                r.`cant_1`,
                r.`cant_2`,
                r.`cant_3`,
                r.`cant_4`,
                r.`maneja_lote`,
                r.`tipo`,
                ifnull(s.id,0) as id_lote,
                ifnull(s.`stock`,0) as total_stock,
                
                ifnull(s.`lote`,'') as lote,
                ifnull(s.`vencimiento`,'') as vencimiento
              from t_referencias r 
              left join t_iva         i on i.id=r.`id_iva`
              left join `t_terceros`  t on t.id=r.`id_proveedor`
              left join `v_referencias_stock_lotes` s on s.`id_referencia`=r.id
              and s.`id_bodega`=$bodega
              where r.`id`=$id  ";
    $datos = GenerarArray($sql);
    echo json_encode($datos);
    break;

  case 's_referencia_img':
    $idReferencia = $_POST['id_referencia'];
    $sql = "select imagen from t_referencias_img where id_referencia = $idReferencia";
    $datos = GenerarArray($sql);

    // Preparamos el array final para las imágenes y tamaños
    $result = [];
    foreach ($datos as $item) {
      $imagePath = '../../assets/img/referencias/' . basename($item['imagen']);
      if (file_exists($imagePath)) {
        $size = filesize($imagePath); // Obtiene el tamaño en bytes
      } else {
        $size = 0; // Si no existe la imagen, asignamos tamaño 0
      }
      // Añadimos la URL de la imagen junto con el tamaño
      $result[] = [
        'imagen' => $item['imagen'],
        'size' => $size,
        'path' => $imagePath
      ];
    }

    echo json_encode(array(
      'error' => false,
      'data' => $result
    ));
    break;
  case 'd_referencia':
    $id = $_POST['id'];
    // se debe validar en esta parte si la referencia no ha tenido movimiento para que se pueda eliminar
    $validacion = GenerarArray("SELECT COUNT(*) AS total FROM t_transacciones_detalle WHERE id_referencia = $id", '');
    if ($validacion[0]['total'] > 0) {
      echo json_encode(array(
        'error' => true,
        'message' => 'No se puede eliminar la referencia porque tiene movimientos asociados.'
      ));
      break;
    }
    $estadoAnterior = obtenerEstadoReferenciaAuditoria($id);
    $deleteReferencias = Query("DELETE FROM t_referencias WHERE id = $id");
    $deleteEan = Query("DELETE FROM t_referencias_ean WHERE id_referencia = $id");
    $deleteListas = Query("DELETE FROM t_referencias_listas WHERE id_referencia = $id");

    // Verificar el resultado de las eliminaciones
    if ($deleteReferencias && $deleteEan && $deleteListas) {
      auditarReferencia(
        'DELETE',
        $id,
        $estadoAnterior,
        array(),
        array(
          'modulo' => 'Referencias',
          'evento' => 'Eliminacion de referencia'
        ),
        $conn
      );

      echo json_encode(array(
        'error' => false,
        'message' => 'Referencia eliminada correctamente.'
      ));
    } else {
      echo json_encode(array(
        'error' => true,
        'message' => 'Error al eliminar la referencia.'
      ));
    }

    break;
  case "ultimo_codigo":
    $codigo = GenerarArray("
        select 
        codigo 
        from t_referencias 
        order by 
        fecha_creacion desc
        limit 1
      ", '');
    echo json_encode(array(
      'codigo' => $codigo[0]["codigo"]
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
      $codigo = $row['codigo'];
      $desc_corta = $row['descripcionCorta'];
      $desc_larga = $row['descripcionLarga'];
      $costo = $row['costoUnitario'];
      $maneja_lote = $row['manejaLoteVmcto'];
      $tipo_lista = $row['tipoLista'];
      $precio = $row['precioRentabilidad'];
      $tipo_ref = $row['tipo'];
      $iva = isset($row['iva']) ? trim($row['iva']) : '';
      $ean = isset($row['codigoBarraEAN']) ? trim($row['codigoBarraEAN']) : '';

      $und_2 = isset($row['und2']) ? trim($row['und2']) : '';
      $cant_2 = isset($row['can2']) ? (int)filter_var($row['can2'], FILTER_SANITIZE_NUMBER_INT) : 0;
      $und_3 = isset($row['und3']) ? trim($row['und3']) : '';
      $cant_3 = isset($row['can3']) ? (int)filter_var($row['can3'], FILTER_SANITIZE_NUMBER_INT) : 0;
      $und_4 = isset($row['und4']) ? trim($row['und4']) : '';
      $cant_4 = isset($row['can4']) ? (int)filter_var($row['can4'], FILTER_SANITIZE_NUMBER_INT) : 0;


      //consultar id del IVA
      if ($iva != '') {
        $sql_iva = GenerarArray(" SELECT 
                                        i.id AS id_iva, 
                                        p.id AS id_perfil 
                                    FROM t_iva i
                                    INNER JOIN t_perfiles_contables p ON p.id_iva = i.id
                                    WHERE 
                                     i.porcentaje_iva = $iva", '');
        if (count($sql_iva) > 0) {
          $iva = $sql_iva[0]['id_iva'];
          $perfil = $sql_iva[0]['id_perfil'];
        }
      }
      $sql_check = GenerarArray("SELECT * FROM t_referencias WHERE codigo = '$codigo'", '');
      if (count($sql_check) > 0) {
        $resultados['existentes'][] = $codigo;
      } else {
        //Datos generales.
        $datosGen = [];
        $datosGen["codigo"] = $codigo;
        $datosGen["descripcion"] = strtoupper($desc_corta);
        $datosGen["descripcion_larga"] = strtoupper($desc_larga);
        $datosGen["costo_unitario"] = $costo;
        $datosGen["precio_unitario"] = $costo;
        $datosGen["tipo"] = $tipo_ref;
        $datosGen["und_1"] = 'UND';
        $datosGen["cant_1"] = 1;
        //Nuevos campos
        $datosGen["und_2"] = $und_2;
        $datosGen["cant_2"] = $cant_2;
        $datosGen["und_3"] = $und_3;
        $datosGen["cant_3"] = $cant_3;
        $datosGen["und_4"] = $und_4;
        $datosGen["cant_4"] = $cant_4;
        //Nuevos campos
        $datosGen["und_compra"] = 1;
        $datosGen["id_iva"] = $iva;
        $datosGen["perfil_contable"] = $perfil;
        $datosGen["maneja_lote"] = strtoupper($maneja_lote);
        $datosGen["usuario_creacion"] = $_SESSION["Ses_id"];
        $id = insertReturnId('t_referencias', $datosGen, true);
        if ($id > 0) {
          //Datos de listas.
          $datosListas = [];
          $datosListas['lista'] = 1;
          $datosListas['tipo_lista'] = $tipo_lista;
          $datosListas['precio'] = $precio;
          $datosListas['id_referencia'] = $id;
          insertReturnId('t_referencias_listas', $datosListas, false);
          if ($ean != '') {
            //Datos de ean.
            $dataEan = [];
            $dataEan['ean'] = $ean;
            $dataEan['id_referencia'] = $id;
            insertReturnId('t_referencias_ean', $dataEan, false);
          }
          $resultados['nuevos'][] = $codigo;
        } else {
          $resultados['errores'][] = $codigo;
        }
      }
    }

    echo json_encode(array(
      'error' => false,
      'message' => 'Registros insertados/actualizados.',
      'datos' => $resultados
    ));
    break;
  case "listar-ubicaciones":
    $id_referencia = isset($_POST["id_referencia"]) ? $_POST["id_referencia"] : 0;
    $sql = "
        select 
          u.ubicacion,
          u.id,
          ifnull(r.id_ubicacion,0) as asignada
        from 
        t_ubicaciones u
        left join t_ubicaciones_referencias r on r.id_ubicacion = u.id and r.id_referencia = $id_referencia
        order by 
        u.ubicacion
      ";
    $ubicaciones = GenerarArray($sql);
    echo  json_encode($ubicaciones);
    break;
  case "ubicaciones_referencias":
    $id_referencia = $_POST["id_referencia"];
    $sql = "select id_ubicacion from t_ubicaciones_referencias where id_referencia = $id_referencia";
    echo  json_encode(GenerarArray($sql));
    break;
  case "update_comision":
    $id = $_POST["id"];

    $id_ref = GenerarArray("select id_referencia from t_referencias_listas where id = $id", '');
    if (count($id_ref) > 0) {
      $id_referencia = $id_ref[0]["id_referencia"];
      $updateref = Query("update t_referencias set comision = 'N' where id = $id_referencia");
    }
    $updateLista = Query("UPDATE 
    t_referencias_listas SET comision1 = 0, 
                            comision2=0, 
                            comision3=0, 
                            comision4=0, 
                            calculo_comision='' 
                            where id = $id");
    //valido que no tenga comisiones en cualquier lista 
    $comisiones = GenerarArray("select * from t_referencias_listas 
      where id_referencia = $id_referencia 
      and (comision1>0 or comision2>0 or comision3>0 or comision4>0)", '');

    // si no tiene marco como comision N en la referencia
    if (count($comisiones) == 0) {
      $updateref = Query("update t_referencias set comision = 'N' where id = $id_referencia");
    }
    echo  json_encode(array(
      'ok' => true,
      'message' => 'Comisión actualizada correctamente.',
      'n_listas_comisiones' => count($comisiones)
    ));

    break;
}
