<?php
session_start();

require_once('bd.php');
require_once('funciones.php');
$conn = conectar();

switch ($_POST["op"]) {
  case "login":
    try {
      $usuario = trim($_POST["usuario"] ?? '');
      $clave = trim($_POST["clave"] ?? '');

      if ($usuario === '' || $clave === '') {
        echo json_encode([
          'error' => true,
          'valido' => false,
          'mensaje' => 'Debe ingresar usuario y contraseña.'
        ]);
        return;
      }

      // Sentencia preparada: evita SQL Injection
      $stmt = $conn->prepare("  SELECT 
        u.id, 
        u.nombres,
        u.apellidos,
        u.email,
        u.usuario, 
        u.clave, 
        u.fecha_creacion,
        u.nit,
        u.roles_id,
        r.descripcion AS rol,
        u.bodegas,
        u.foto
      FROM t_usuarios u
      INNER JOIN t_roles r ON r.id = u.roles_id 
      WHERE u.usuario = ?
    ");
      $stmt->bind_param("s", $usuario);
      $stmt->execute();
      $result = $stmt->get_result();

      if ($result->num_rows === 0) {
        echo json_encode([
          'error' => false,
          'valido' => false,
          'mensaje' => 'Usuario o clave no válidos'
        ]);
        return;
      }

      $row = $result->fetch_assoc();

      // ✅ Verificar contraseña encriptada con password_verify()
      if (!password_verify($clave, $row["clave"])) {
        echo json_encode([
          'error' => false,
          'valido' => false,
          'mensaje' => 'Usuario o clave no válidos'
        ]);
        return;
      }

      // Cargar configuraciones
      $confGeneral = [];
      $confModulos = [];

      $sqlConfig = "SELECT * FROM t_config_modulos";
      $configuraciones = GenerarArray($sqlConfig);

      foreach ($configuraciones as $item) {
        if ($item["id_modulo"] == 0) {
          $confGeneral[] = [
            $item["Clave"] => $item["valor"],
            'descripcion' => $item["descripcion"]
          ];
        } else {
          $confModulos[] = [
            $item["Clave"] => $item["valor"],
            'descripcion' => $item["descripcion"],
            'id_modulo' => $item["id_modulo"]
          ];
        }
      }

      // Obtener logo empresa
      $logo = GenerarArray("SELECT logo FROM t_empresa", '');
      $logoEmpresa = 'default_logo.png';
      if (is_array($logo) && count($logo) > 0 && !empty($logo[0]["logo"])) {
        $logoEmpresa = trim($logo[0]["logo"]);
      }

      // Crear sesión
      $usuarioSesion = [
        'usuario' => $row["usuario"],
        'nombres' => mb_convert_encoding($row["nombres"], "UTF-8"),
        'apellidos' => mb_convert_encoding($row["apellidos"], "UTF-8"),
        'email' => mb_convert_encoding($row["email"], "UTF-8"),
        'nit' => $row["nit"],
        'id' => $row["id"],
        'roles_id' => $row["roles_id"],
        'rol' => $row["rol"],
        'foto' => $row["foto"],
        'logo' => $logoEmpresa,
        'empresa' => $_POST["empresa"],
        'confSys' => [
          'confGeneral' => $confGeneral,
          'confModulos' => $confModulos
        ]
      ];

      crearSession($usuarioSesion);

      //Validacion de suscripcion    
      $connCore = conectarCore();
      $db_empresa = $_POST["empresa"] ?? '';
      $validacion = validarSuscripcion($connCore, $db_empresa);

      if (!$validacion['valido']) {
        echo json_encode([
          'error' => false,
          'valido' => false,
          'mensaje' => $validacion['mensaje']
        ]);
        return;
      }

      // Crear carpeta de empresa si no existe
      $directorio = '/var/www/html/cyc/files/' . $_POST["empresa"];
      if (!is_dir($directorio)) {
        mkdir($directorio, 0777, true);
      }

      $token = bin2hex(random_bytes(16)); // token seguro
      $_SESSION["Ses_token"] = $token;

      $stmt = $conn->prepare("UPDATE t_usuarios SET session_token = ? WHERE id = ?");
      $stmt->bind_param("si", $token, $usuarioSesion['id']);
      $stmt->execute();
      $stmt->close();

      echo json_encode([
        'error' => false,
        'valido' => true,
        'mensaje' => 'Usuario válido'
      ]);
    } catch (Exception $e) {
      echo json_encode([
        'ok' => false,
        'error' => true,
        'mensaje' => $e->getMessage()
      ]);
    }
    break;

  case "s_usuario":
    $usuario = trim($_POST["usuario"]);
    $parametros  = [];

    $sql = "SELECT 
				u.id, 
				u.usuario, 
				u.fecha_creacion, 
				u.nombres, 
				u.apellidos, 
				u.email, 
				u.roles_id, 
				u.nit, 
				u.estado,
				u.telefono,
        u.bodegas,
        CONCAT('../../assets/img/usuarios/',u.foto) as imagen,
        ifnull(t.primer_nombre,'') AS primer_nombre,
        ifnull(t.primer_apellido,'') AS primer_apellido,
        ifnull(t.segundo_nombre,'') AS segundo_nombre,
        ifnull(t.segundo_apellido ,'') AS segundo_apellido
			 FROM t_usuarios u
       left join t_terceros t on t.nit=u.nit
			 WHERE 
			  usuario = ?";

    $parametros[] = array(
      'tipo'  => 's',
      'valor' => $usuario
    );
    $usuarios = GenerarArrayPrepare($sql, $parametros);

    // $usuario = GenerarArray( $sql );

    echo json_encode(
      array(
        'ok' => true,
        'mensaje' => '',
        'usuario' => $usuarios
      )
    );

    break;
  case "s_usuario_activo":
    $usuario = trim($_SESSION["Ses_id"]);
    $parametros = [];
    $sql = "SELECT 
				u.id, 
				u.usuario, 
				u.fecha_creacion, 
				u.nombres, 
				u.apellidos, 
				u.email, 
				u.roles_id, 
				u.nit, 
				u.estado,
				u.telefono,
        u.bodegas,
        CONCAT('../../assets/img/usuarios/',u.foto) as imagen,
        ifnull(t.primer_nombre,'') AS primer_nombre,
        ifnull(t.primer_apellido,'') AS primer_apellido,
        ifnull(t.segundo_nombre,'') AS segundo_nombre,
        ifnull(t.segundo_apellido ,'') AS segundo_apellido
			 FROM t_usuarios u
       left join t_terceros t on t.nit=u.nit
			 WHERE 
			  u.id = ? ";
    $parametros[] = array(
      'tipo'  => 's',
      'valor' => $usuario
    );
    $data = GenerarArrayPrepare($sql, $parametros);

    echo json_encode(
      array(
        'ok' => true,
        'mensaje' => '',
        'data' => $data
      )
    );

    break;
  case 'u_clave':
    try {
      $id = $_POST["id"];
      $clave = encriptar_password($_POST["clave1"]);
      $datos = array(
        'calve' => $clave
      );
      //$query = $conn->query( "UPDATE t_usuarios SET clave ='$clave' WHERE id =" . $id );
      $query = update('t_usuarios', $datos, $id);

      if (!$query) {
        echo json_encode(array(
          'ok' => false,
          'mensaje' => 'Se presento un error al intentar actualizar la clave, verifique e intente nuevamente.'
        ));
        return;
      }
      echo json_encode(array(
        'ok' => true,
        'mensaje' => 'Se actualizo la clave correctamente'
      ));
    } catch (Exception $e) {
      echo json_encode(array(
        'ok' => false,
        'mensaje' => $e->getMessage()
      ));
    }
    break;
  case "i_usuario":

    if ($_SESSION["Ses_empresa"] != 'CYC') {
      $empresa = $_SESSION["Ses_empresa"];
      $parametros = [];
      $connCore = conectarCore();
      $parametros[] = array(
        'tipo'  => 's',
        'valor' => $empresa
      );
      $parametros[] = array(
        'tipo'  => 'i',
        'valor' => 1
      );
      $plan = GenerarArrayPrepare("SELECT usuarios FROM t_empresas WHERE db_name = ?  AND activo = ? ", $parametros, $connCore);

      if (!$plan) {
        echo json_encode([
          'error' => true,
          'message' => 'Empresa no válida o inactiva',
          'id' => 0
        ]);
        return;
      }

      $limiteUsuarios = (int)$plan[0]["usuarios"];

      /* 3️⃣ Contar usuarios activas en la empresa */
      $res = GenerarArray(" SELECT COUNT(*) as total FROM t_usuarios where usuario <> 'CYC'");
      $usuariosActuales = (int)$res[0]["total"];


      /* 4️⃣ Validar límite */
      if ($usuariosActuales >= $limiteUsuarios) {
        echo json_encode([
          'error' => true,
          'message' => 'Has alcanzado el límite de ' . $limiteUsuarios . ' usuarios de tu plan actual, consulte el area comercial para ampliarlo',
          'id' => 0
        ]);
        return;
      }
    }
    /* 5️⃣ Crear usuario */

    $fields = $_POST;
    $fields["clave"] = encriptar_password($fields["clave"]);
    $id = insertReturnId('t_usuarios', $fields, true, null, $conn);

    if ($id > 0) {
      echo json_encode([
        'error' => false,
        'message' => 'Usuario creado correctamente',
        'data' => $fields
      ]);
    } else {
      echo json_encode([
        'error' => true,
        'message' => 'Error al crear Usuario'
      ]);
    }

    break;
  case 'd_usuario':
    try {
      $id = $_POST["id"];
      $query = delete('t_usuarios', $id);

      if (!$query) {
        echo json_encode(array(
          'ok' => false,
          'mensaje' => 'Se presento un error al intentar eliminar el usuario, verifique e intente nuevamente.'
        ));
        return;
      }
      echo json_encode(array(
        'ok' => true,
        'mensaje' => 'Se elimino el usuario correctamente'
      ));
    } catch (Exception $e) {
      echo json_encode(array(
        'ok' => false,
        'mensaje' => $e->getMessage()
      ));
    }
    break;
  case "s_usuario_all":
    $estado = isset($_POST['estado']) ? $_POST['estado'] : 'A';
    $filtro = isset($_POST['filtro']) ? $_POST['filtro'] : '';
    $rol = isset($_POST['rol']) ? $_POST['rol'] : '0';
    $parametros = [];
    $sql = "SELECT 
              u.id, 
              UPPER(u.usuario) as usuario, 
              u.clave, 
              u.fecha_creacion, 
              u.nombres, 
              u.apellidos, 
              UPPER(CONCAT(u.nombres,' ',u.apellidos)) as nombre_completo, 
              u.email, 
              u.roles_id,
              r.descripcion  as rol_descripcion,
              u.nit, 
              u.estado,
              u.telefono,
              u.bodegas,
              CONCAT('../../assets/img/usuarios/',u.foto) as imagen,
              ifnull(t.primer_nombre,'')     AS primer_nombre,
              ifnull(t.primer_apellido,'')   AS primer_apellido,
              ifnull(t.segundo_nombre,'')    AS segundo_nombre,
              ifnull(t.segundo_apellido ,'') AS segundo_apellido,
              CONCAT('../../assets/img/usuarios/',u.foto) as imagen_td
			 FROM t_usuarios u
			 inner join t_roles    r on r.id  = u.roles_id 
       left  join t_terceros t on t.nit = u.nit
			 WHERE u.usuario <> 'CYC' ";

    if ($filtro != '') {
      $sql .= " and  CONCAT(u.nombres, u.apellidos, u.usuario) LIKE ? ;";
      $parametros[] = array(
        'tipo' => 's',
        'valor' => "%$filtro%"
      );
    }
    if ($rol != '' && $rol != '0') {
      $sql .= " and  u.roles_id =  ? ";
      $parametros[] = array(
        'tipo' => 'i',
        'valor' => $rol
      );
    }
    if ($estado != '') {
      $sql .= " and u.estado=?";
      $parametros[] = array(
        'tipo' => 's',
        'valor' => $estado
      );
    }


    $usuarios = GenerarArrayPrepare($sql, $parametros);

    echo json_encode(
      array(
        'ok' => true,
        'data' => $usuarios
      )
    );

    break;
  case "u_usuario":

    try {
      //code...      
      $fields = $_POST;

      $table = 't_usuarios';
      unset($fields['clave']);
      unset($fields['usuario']);

      $update = update($table, $fields, $fields['id']);

      if ($update) {
        echo json_encode(array(
          'ok' => true,
          'id' => $fields["id"]
        ));
      } else {
        echo json_encode(array(
          'ok' => false,
          'error' => print_r(mysqli_error($conn), true)
        ));
      }
    } catch (\Exception $e) {
      //throw $th;
      print_r($e);
    }

    break;
  case "update-password":

    try {
      //code...
      $id = $_POST["id"];
      $clave = encriptar_password($_POST["clave"]);
      $datos = array(
        'clave' => $clave
      );
      //$update = Query( "update t_usuarios set clave='$clave'  where id=$id" );
      $update = update('t_usuarios', $datos, $id);

      if (!$update) {
        echo json_encode(array(
          'ok' => false,
          'message' => 'No se pudo actualizar la clave del usuario'
        ));
        return;
      }

      echo json_encode(array(
        'ok' => true,
        'message' => 'Clave actualizada correctamente!'
      ));
    } catch (\Throwable $th) {
      //throw $th;
      echo json_encode(array(
        'ok' => false,
        'message' => $th->getMessage()
      ));
    }
    break;
  case "bodegas-usuarios":
    if (empty($_SESSION["Ses_id"])) {
      return;
    }
    $id = $_SESSION["Ses_id"];
    $parametros = [];

    $sql = "select bodegas from t_usuarios where id=?";

    $parametros[] = array(
      'tipo' => 'i',
      'valor' => $id
    );

    $bodegas = GenerarArrayPrepare($sql, $parametros);

    foreach ($bodegas as $key) {
      # code...
      echo json_encode(json_decode($key['bodegas'], true));
    }

    break;
  case "cambiar_password":
    try {
      $id = empty($_SESSION["Ses_id"]) ? 0 : $_SESSION["Ses_id"];
      $claveActual = $_POST["password_actual"];
      $claveNueva = $_POST["password_nueva"];
      $parametros = [];
      // Verificar clave actual
      $sql = "SELECT clave FROM t_usuarios WHERE id = ? ";
      $parametros[] = array(
        'tipo' => 'i',
        'valor' => $id
      );

      $result = GenerarArrayPrepare($sql, $parametros);

      if (count($result) === 0) {
        echo json_encode([
          'ok' => false,
          'message' => 'Usuario no encontrado'
        ]);
        return;
      }

      $claveEnDB = $result[0]["clave"];

      if (!password_verify($claveActual, $claveEnDB)) {
        echo json_encode([
          'ok' => false,
          'message' => 'La clave actual es incorrecta'
        ]);
        return;
      }

      // Actualizar a la nueva clave
      $claveEncriptada = encriptar_password($claveNueva);
      //$update = Query( "UPDATE t_usuarios SET clave='$claveEncriptada' WHERE id=$id" );
      $datos = array(
        'clave' => $claveEncriptada
      );
      $update  = update('t_usuarios', $datos, $id);

      if (!$update) {
        echo json_encode([
          'ok' => false,
          'message' => 'No se pudo actualizar la contraseña'
        ]);
        return;
      }

      echo json_encode([
        'ok' => true,
        'message' => 'Contraseña actualizada correctamente'
      ]);
    } catch (\Throwable $th) {
      echo json_encode([
        'ok' => false,
        'message' => $th->getMessage()
      ]);
    }
}
