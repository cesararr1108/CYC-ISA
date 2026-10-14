<?php
session_start();
require_once('bd.php');
require_once('funciones.php');

switch ($_POST["op"]) {
  case "uniqId":
    $id = uniqid(bin2hex(random_bytes(10)), true);
    echo json_encode(array(
      'error' => false,
      'message' => 'success',
      'data' => $id
    ));
    break;
  case "g_facturador_electronico":
    try {
      $data = $_POST;

      $impuestos = is_array($data['impuestos']) ? implode(';', $data['impuestos']) : $data['impuestos'];
      $responsabilidades = is_array($data['responsabilidades']) ? implode(';', $data['responsabilidades']) : $data['responsabilidades'];

      // Manejo del logo (si viene)
      $nombreLogoFinal = '';
      if (isset($_FILES['logoEmpresa']) && $_FILES['logoEmpresa']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['logoEmpresa']['name'], PATHINFO_EXTENSION);
        $nombreLogoFinal = $data['nit'] . '.' . strtolower($ext); // nombre del archivo = NIT.ext
        $rutaDestino = '../../../../assets/logos-empresas/' . $nombreLogoFinal;

        if (!move_uploaded_file($_FILES['logoEmpresa']['tmp_name'], $rutaDestino)) {
          echo json_encode([
            'ok' => false,
            'message' => 'Error al subir el logo de la empresa.'
          ]);
          return;
        }
      }

      $sql = "INSERT INTO t_datos_fiscales (
                nit, nitDv, perfilTributario, regimen, razonSocial, nombreComercial,
                ProfileExecutionID, certificadoNombre, certificadoClave, tokenUser,
                tokenPassword, codigoPais, codigoDpto, codigoCiudad, direccion,
                SoftwareID, claveTecnica, testSetId, pinSoftware,
                SoftwareIDNomina, testSetIdNomina, pinSoftwareNomina, ProfileExecutionIDNomina,
                telefono, email, impuestos, responsabilidades, logoEmpresa,
                imapProveedor, imapMail, imapClave, servicioAcuse,  usrReciboTipo, usrReciboDoc,
                usrReciboDv, usrReciboNombre, usrReciboApellido, usrAceptTipo, usrAceptboDoc,
                usrAceptDv, usrAceptNombre, usrAceptApellido, usrRechazaTipo, usrRechazaDoc,
                usrRechazaDv,usrRechazaNombre, usrRechazaApellido
            ) VALUES (
                '{$data['nit']}',
                '{$data['nitDv']}',
                '{$data['perfilTributario']}',
                '{$data['regimen']}',
                '{$data['razonSocial']}',
                '{$data['nombreComercial']}',
                '{$data['ProfileExecutionID']}',
                '{$data['certificadoNombre']}',
                '{$data['certificadoClave']}',
                '{$data['tokenUser']}',
                '{$data['tokenPassword']}',
                '{$data['codigoPais']}',
                '{$data['codigoDpto']}',
                '{$data['codigoCiudad']}',
                '{$data['direccion']}',
                '{$data['SoftwareID']}',
                '{$data['claveTecnica']}',
                '{$data['testSetId']}',
                '{$data['pinSoftware']}',
                '{$data['SoftwareIDNomina']}',
                '{$data['testSetIdNomina']}',
                '{$data['pinSoftwareNomina']}',
                '{$data['ProfileExecutionIDNomina']}',
                '{$data['telefono']}',
                '{$data['email']}',
                '{$impuestos}',
                '{$responsabilidades}',
                '{$nombreLogoFinal}',
                '{$data['imapProveedor']}',
                '{$data['imapMail']}',
                '{$data['imapClave']}',
                '{$data['servicioAcuse']}',
                '{$data['usrReciboTipo']}',
                '{$data['usrReciboDoc']}',
                '{$data['usrReciboDv']}',
                '{$data['usrReciboNombre']}',
                '{$data['usrReciboApellido']}',
                '{$data['usrAceptTipo']}',
                '{$data['usrAceptboDoc']}',
                '{$data['usrAceptDv']}',
                '{$data['usrAceptNombre']}',
                '{$data['usrAceptApellido']}',
                '{$data['usrRechazaTipo']}',
                '{$data['usrRechazaDoc']}',
                '{$data['usrRechazaDv']}',
                '{$data['usrRechazaNombre']}',
                '{$data['usrRechazaApellido']}'
            );";

      if (Query($sql, conectarFel())) {
        echo json_encode([
          'ok' => true,
          'message' => 'Datos fiscales creados correctamente.'
        ]);
      } else {
        echo json_encode([
          'ok' => false,
          'message' => 'Error al intentar crear los datos fiscales.'
        ]);
      }
    } catch (\Throwable $th) {
      echo json_encode([
        'ok' => false,
        'message' => 'Excepción capturada: ' . $th->getMessage()
      ]);
    }
    break;

  case "u_facturador_electronico":
    try {
      $data = $_POST;

      $impuestos = is_array($data['impuestos']) ? implode(';', $data['impuestos']) : $data['impuestos'];
      $responsabilidades = is_array($data['responsabilidades']) ? implode(';', $data['responsabilidades']) : $data['responsabilidades'];

      // Manejo de carga del logo
      $nombreLogoFinal = '';
      if (isset($_FILES['logoEmpresa']) && $_FILES['logoEmpresa']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['logoEmpresa']['name'], PATHINFO_EXTENSION);
        $nombreLogoFinal = $data['nit'] . '.' . strtolower($ext); // Usar el NIT como nombre de archivo
        $rutaDestino = '../../../../assets/logos-empresas/' . $nombreLogoFinal;

        // Mover archivo
        if (!move_uploaded_file($_FILES['logoEmpresa']['tmp_name'], $rutaDestino)) {
          echo json_encode([
            'ok' => false,
            'message' => 'Error al subir el logo de la empresa.'
          ]);
          return;
        }
      }

      $sql = "UPDATE t_datos_fiscales
             SET
                nit = '{$data['nit']}',
                nitDv = '{$data['nitDv']}',
                perfilTributario = '{$data['perfilTributario']}',
                regimen = '{$data['regimen']}',
                razonSocial = '{$data['razonSocial']}',
                nombreComercial = '{$data['nombreComercial']}',
                ProfileExecutionID = '{$data['ProfileExecutionID']}',
                certificadoNombre = '{$data['certificadoNombre']}',
                certificadoClave = '{$data['certificadoClave']}',
                tokenUser = '{$data['tokenUser']}',
                tokenPassword = '{$data['tokenPassword']}',
                codigoPais = '{$data['codigoPais']}',
                codigoDpto = '{$data['codigoDpto']}',
                codigoCiudad = '{$data['codigoCiudad']}',
                direccion = '{$data['direccion']}',
                SoftwareID = '{$data['SoftwareID']}',
                claveTecnica = '{$data['claveTecnica']}',
                testSetId = '{$data['testSetId']}',
                pinSoftware = '{$data['pinSoftware']}',
                SoftwareIDNomina = '{$data['SoftwareIDNomina']}',
                testSetIdNomina = '{$data['testSetIdNomina']}',
                pinSoftwareNomina = '{$data['pinSoftwareNomina']}',
                ProfileExecutionIDNomina = '{$data['ProfileExecutionIDNomina']}',
                telefono = '{$data['telefono']}',
                email = '{$data['email']}',
                impuestos = '{$impuestos}',
                responsabilidades = '{$responsabilidades}',
                logoEmpresa = " . ($nombreLogoFinal ? "'$nombreLogoFinal'" : "logoEmpresa") . ",
                imapProveedor = '{$data['imapProveedor']}',
                imapMail = '{$data['imapMail']}',
                imapClave = '{$data['imapClave']}',
                servicioAcuse = '{$data['servicioAcuse']}',
                usrReciboTipo = '{$data['usrReciboTipo']}',
                usrReciboDoc = '{$data['usrReciboDoc']}',
                usrReciboDv = '{$data['usrReciboDv']}',
                usrReciboNombre = '{$data['usrReciboNombre']}',
                usrReciboApellido = '{$data['usrReciboApellido']}',
                usrAceptTipo = '{$data['usrAceptTipo']}',
                usrAceptboDoc = '{$data['usrAceptboDoc']}',
                usrAceptDv = '{$data['usrAceptDv']}',
                usrAceptNombre = '{$data['usrAceptNombre']}',
                usrAceptApellido = '{$data['usrAceptApellido']}',
                usrRechazaTipo = '{$data['usrRechazaTipo']}', 
                usrRechazaDoc = '{$data['usrRechazaDoc']}',
                usrRechazaDv = '{$data['usrRechazaDv']}',
                usrRechazaNombre = '{$data['usrRechazaNombre']}',
                usrRechazaApellido = '{$data['usrRechazaApellido']}'
              WHERE 
                Id = '{$data['Id']}';";

      if (Query($sql, conectarFel())) {
        echo json_encode([
          'ok' => true,
          'message' => 'Datos fiscales actualizados correctamente.',
          'sql' => $sql
        ]);
      } else {
        echo json_encode([
          'ok' => false,
          'message' => 'Error al intentar actualizar datos fiscales.'
        ]);
      }
    } catch (\Throwable $th) {
      echo json_encode([
        'ok' => false,
        'message' => 'Excepción capturada: ' . $th->getMessage()
      ]);
    }
    break;

  case "s_facturador_electronico":
    $nit = $_POST['nit'];
    $sql = "SELECT
                Id,
                nit,
                nitDv,
                pinSoftware,
                pinSoftwareNomina,
                ProfileExecutionID,
                ProfileExecutionIDNomina,
                claveTecnica,
                certificadoClave,
                certificadoNombre,
                consecutivoEnvioFV,
                consecutivoEnvioNC,
                consecutivoEnvioND,
                consecutivoEnvioDA,
                SoftwareID,
                SoftwareIDNomina,
                testSetId,
                testSetIdNomina,
                tokenUser,
                tokenPassword,
                razonSocial,
                nombreComercial,
                codigoPais,
                codigoDpto,
                codigoCiudad,
                direccion,
                responsabilidades,
                impuestos,
                telefono,
                email,
                perfilTributario,
                regimen,
                logoEmpresa,
                imapProveedor,
                imapMail,
                imapClave,
                servicioAcuse,
                usrReciboTipo,
                usrReciboDoc,
                usrReciboDv,
                usrReciboNombre,
                usrReciboApellido,
                usrAceptTipo,
                usrAceptboDoc,
                usrAceptDv,
                usrAceptNombre,
                usrAceptApellido,
                usrRechazaTipo,
                usrRechazaDoc,
                usrRechazaDv,
                usrRechazaNombre,
                usrRechazaApellido
            FROM t_datos_fiscales
            WHERE 
               nit = '" . $nit . "' ";

    $datos = GenerarArray($sql, conectarFel());
    $result = [];

    foreach ($datos as $item) {

      $responsabilidadesArray = !empty($item['responsabilidades']) ? explode(';', $item['responsabilidades']) : [];
      $impuestosArray = !empty($item['impuestos']) ? explode(';', $item['impuestos']) : [];

      $resoluciones = GenerarArray("SELECT
                                        r.idTipoDoc,
                                        d.Descripcion AS descTipoDoc,
                                        r.id_datos_fiscales,
                                        r.Id,
                                        r.InvoiceAuthorization,
                                        r.StartDate,
                                        r.EndDate,
                                        r.Prefix,
                                        `r`.`From`,
                                        `r`.`To`,
                                        r.claveTecnica as claveTecnicaRes
                                      FROM t_resoluciones r
                                      INNER JOIN t_tipos_documentos d ON d.id = r.idTipoDoc
                                      WHERE 
                                        r.id_datos_fiscales = " . $item['Id'], conectarFel());

      $result[] = [
        'Id' => $item['Id'],
        'nit' => $item['nit'],
        'nitDv' => $item['nitDv'],
        'pinSoftware' => $item['pinSoftware'],
        'pinSoftwareNomina' => $item['pinSoftwareNomina'],
        'ProfileExecutionID' => $item['ProfileExecutionID'],
        'ProfileExecutionIDNomina' => $item['ProfileExecutionIDNomina'],
        'claveTecnica' => $item['claveTecnica'],
        'certificadoClave' => $item['certificadoClave'],
        'certificadoNombre' => $item['certificadoNombre'],
        'consecutivoEnvioFV' => $item['consecutivoEnvioFV'],
        'consecutivoEnvioNC' => $item['consecutivoEnvioNC'],
        'consecutivoEnvioND' => $item['consecutivoEnvioND'],
        'consecutivoEnvioDA' => $item['consecutivoEnvioDA'],
        'SoftwareID' => $item['SoftwareID'],
        'SoftwareIDNomina' => $item['SoftwareIDNomina'],
        'testSetId' => $item['testSetId'],
        'testSetIdNomina' => $item['testSetIdNomina'],
        'tokenUser' => $item['tokenUser'],
        'tokenPassword' => $item['tokenPassword'],
        'razonSocial' => $item['razonSocial'],
        'nombreComercial' => $item['nombreComercial'],
        'codigoPais' => $item['codigoPais'],
        'codigoDpto' => $item['codigoDpto'],
        'codigoCiudad' => $item['codigoCiudad'],
        'direccion' => $item['direccion'],
        'responsabilidades' => $responsabilidadesArray,
        'impuestos' => $impuestosArray,
        'resoluciones' => $resoluciones,
        'telefono' => $item['telefono'],
        'email' => $item['email'],
        'perfilTributario' => $item['perfilTributario'],
        'regimen' => $item['regimen'],
        'logoEmpresa' => $item['logoEmpresa'],
        'imapProveedor' => $item['imapProveedor'],
        'imapMail' => $item['imapMail'],
        'imapClave' => $item['imapClave'],
        'servicioAcuse' => $item['servicioAcuse'],
        'usrReciboTipo' => $item['usrReciboTipo'],
        'usrReciboDoc' => $item['usrReciboDoc'],
        'usrReciboDv' => $item['usrReciboDv'],
        'usrReciboNombre' => $item['usrReciboNombre'],
        'usrReciboApellido' => $item['usrReciboApellido'],
        'usrAceptTipo' => $item['usrAceptTipo'],
        'usrAceptboDoc' => $item['usrAceptboDoc'],
        'usrAceptDv' => $item['usrAceptDv'],
        'usrAceptNombre' => $item['usrAceptNombre'],
        'usrAceptApellido' => $item['usrAceptApellido'],
        'usrRechazaTipo' => $item['usrRechazaTipo'],
        'usrRechazaDoc' => $item['usrRechazaDoc'],
        'usrRechazaDv' => $item['usrRechazaDv'],
        'usrRechazaNombre' => $item['usrRechazaNombre'],
        'usrRechazaApellido' => $item['usrRechazaApellido']
      ];
    }
    echo json_encode($result);
    break;
  case 'g_resoluciones':
    try {
      $data = $_POST;
      $checkSql = "SELECT 
                    COUNT(*) AS count 
                   FROM t_resoluciones 
                   WHERE 
                    id_datos_fiscales = '{$data['id_datos_fiscales']}' AND Prefix = '{$data['Prefix']}' ";

      $result = GenerarArray($checkSql, conectarFel());
      if ($result && $result[0]['count'] > 0) {
        echo json_encode([
          'ok' => false,
          'message' => 'Ya existe una resolución con el mismo numero o con el mismo prefijo'
        ]);
      } else {
        $sql = "INSERT INTO t_resoluciones (
                           id_datos_fiscales, InvoiceAuthorization, Prefix, `From`, `To`, StartDate, EndDate, idTipoDoc, claveTecnica
                       ) VALUES (
                           '{$data['id_datos_fiscales']}', 
                           '{$data['InvoiceAuthorization']}', 
                           '{$data['Prefix']}', 
                           '{$data['From']}', 
                           '{$data['To']}', 
                           '{$data['StartDate']}', 
                           '{$data['EndDate']}',
                           '{$data['idTipoDoc']}',
                           '{$data['claveTecnicaRes']}'
                       )";

        if (Query($sql, conectarFel())) {
          echo json_encode([
            'ok' => true,
            'message' => 'Resolución creada correctamente.'
          ]);
        } else {
          echo json_encode([
            'ok' => false,
            'message' => 'Error al intentar crear la resolución.'
          ]);
        }
      }
    } catch (\Throwable $th) {
      print_r($th);
    }


    break;
  case 'u_resoluciones':
    $data = $_POST;
    $sql = "UPDATE t_resoluciones 
            SET
                id_datos_fiscales = '{$data['id_datos_fiscales']}', 
                InvoiceAuthorization = '{$data['InvoiceAuthorization']}', 
                Prefix = '{$data['Prefix']}', 
                `From` = '{$data['From']}', 
                `To` = '{$data['To']}', 
                StartDate = '{$data['StartDate']}', 
                EndDate = '{$data['EndDate']}',
                claveTecnica = '{$data['claveTecnicaRes']}'
            WHERE 
                Id = '{$data['Id']}'";
    if (Query($sql, conectarFel())) {
      echo json_encode([
        'ok' => true,
        'message' => 'Resolución actualizada correctamente.'
      ]);
    } else {
      echo json_encode([
        'ok' => false,
        'message' => 'Error al intentar actualizar la resolución.'
      ]);
    }
    break;
  case 's_resoluciones':
    $data = $_POST;
    $sql = GenerarArray("SELECT
                            r.idTipoDoc,
                            d.Descripcion AS descTipoDoc,
                            r.id_datos_fiscales,
                            r.Id,
                            r.InvoiceAuthorization,
                            r.StartDate,
                            r.EndDate,
                            r.Prefix,
                            `r`.`From`,
                            `r`.`To`,
                            r.claveTecnica as claveTecnicaRes
                          FROM t_resoluciones r
                          INNER JOIN t_tipos_documentos d ON d.id = r.idTipoDoc
                          WHERE 
                            r.id_datos_fiscales = " . $data['Id'], conectarFel());
    echo json_encode($sql);
    break;
  case 'd_resolucion':
    $data = $_POST;
    $sql = "DELETE FROM t_resoluciones 
            WHERE 
                Id = '{$data['id']}'";
    if (Query($sql, conectarFel())) {
      echo json_encode([
        'ok' => true,
        'message' => 'Resolución eliminada correctamente.'
      ]);
    } else {
      echo json_encode([
        'ok' => false,
        'message' => 'Error al intentar eliminada la resolución.'
      ]);
    }
    break;
  case 's_tipos_documentos':
    $data = $_POST;
    $sql = GenerarArray("SELECT
                            Id,
                            Descripcion
                          FROM t_tipos_documentos", conectarFel());
    echo json_encode($sql);
    break;
  case 's_clase_documento_fe':
    $tipo = isset($_POST['tipo']) ? intval($_POST['tipo']) : 0;

    $sql = GenerarArray("SELECT
                            s.sw,
                            s.descripcion as desc_sw,
                            t.tipo,
                            t.descripcion as desc_tipo
                          FROM t_tipos_documentos t
                          INNER JOIN t_sw s ON s.sw = t.sw
                          WHERE
                            t.documento_electronico = 'S' AND
                            s.sw = " . $tipo . "
                          ORDER BY
                            t.tipo", conectar());
    echo json_encode($sql);
    break;
  case 's_documentos_fe':
    $fhIni  = $_POST['fhIni'];
    $fhFin  = $_POST['fhFin'];
    $sw     = intval($_POST['sw']);
    $tipo   = trim($_POST['tipo'] ?? '');
    $estado = trim($_POST['estado'] ?? '0');

    $filtroTipo   = ($tipo !== '') ? "AND d.tipo = '" . addslashes($tipo) . "'" : "";
    $filtroEstado = '';
    if ($estado === 'S') {
      $filtroEstado = "AND IFNULL(r.cufe, '') != ''";
    } elseif ($estado === 'N') {
      $filtroEstado = "AND IFNULL(r.cufe, '') = ''";
    }

    $query = "SELECT
                d.id,
                d.numero,
                d.tipo,
                d.valor_total,
                t.nit,
                CASE
                    WHEN t.primer_nombre IS NULL THEN t.razon_comercial
                    ELSE CONCAT(
                        IFNULL(t.primer_nombre, ''), ' ',
                        IFNULL(t.segundo_nombre, ''), ' ',
                        IFNULL(t.primer_apellido, ''), ' ',
                        IFNULL(t.segundo_apellido, '')
                    )
                END AS cliente,
                CAST(d.fecha_hora AS date) AS fecha,
                IFNULL(t.email_fe, '') AS email,
                IFNULL(r.cufe, '') AS cufe,
                IFNULL(xx.documento_electronico, 'N') AS documento_electronico
              FROM t_transacciones d
              INNER JOIN t_terceros t ON t.id = d.id_tercero
              INNER JOIN t_tipos_documentos xx ON xx.id = d.id_tipo_documento
              LEFT JOIN t_respuestas_dian r ON r.tipo = d.tipo AND r.numero = d.numero
              WHERE
                IFNULL(d.anulado, 0) = 0
                AND IFNULL(xx.documento_electronico, 'N') = 'S'
                AND d.sw = $sw
                AND CAST(d.fecha_hora AS date) BETWEEN '$fhIni' AND '$fhFin'
                $filtroTipo
                $filtroEstado
              ORDER BY d.fecha_hora DESC";

    $data = GenerarArray($query, conectar());
    echo json_encode(['ok' => true, 'data' => $data]);
    break;
  case "s_responsabilidades":
    $sql = "select * from t_responsabilidades";
    $res = GenerarArray($sql, conectarFel());
    echo json_encode(array(
      'error' => false,
      'message' => 'success',
      'data' => $res
    ));
    break;
  case "s_impuestos":
    $sql = "select * from t_impuestos";
    $res = GenerarArray($sql, conectarFel());
    echo json_encode(array(
      'error' => false,
      'message' => 'success',
      'data' => $res
    ));
    break;
  case "s_perfil_tributario":
    $sql = "select * from t_perfil_tributario";
    $perfil_tributario = GenerarArray($sql, conectarFel());
    echo json_encode(array(
      'error' => false,
      'message' => 'success',
      'data' => $perfil_tributario
    ));
    break;
  case "s_regimenes":
    $sql = "select * from t_regimenes";
    $data = GenerarArray($sql, conectarFel());
    echo json_encode(array(
      'error' => false,
      'message' => 'success',
      'data' => $data
    ));
    break;
  case 's_tipo_usuario':
    $sql = "SELECT nit FROM t_empresa";
    $data = GenerarArray($sql);

    $nit = isset($data[0]['nit']) ? $data[0]['nit'] : null;

    echo json_encode([
      'error' => false,
      'message' => 'success',
      'nit' => $nit
    ]);
    break;
}
