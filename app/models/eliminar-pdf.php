<?php

header('Content-Type: application/json');

try {

    require_once('funciones.php');
    require_once('bd.php');

    /*
     * Conexión a la BD principal
     * Esta conexión debe tener permisos sobre las BD de las empresas.
     */
    $connPrincipal = conectarCore();

    /*
     * Obtener empresas activas
     */
    $sqlEmpresas = "
        SELECT db_name
        FROM t_empresas
        WHERE activo = 1
          AND estado_servicio = 'ACTIVO'
    ";

    $resultadoEmpresas = $connPrincipal->query($sqlEmpresas);

    if (!$resultadoEmpresas) {
        throw new Exception(
            'Error consultando empresas: ' . $connPrincipal->error
        );
    }

    /*
     * Estadísticas
     */
    $empresasProcesadas = 0;
    $empresasConError = 0;

    $encontrados = 0;
    $archivosEliminados = 0;
    $archivosNoEncontrados = 0;
    $registrosEliminados = 0;

    $errores = [];

    /*
     * Recorrer empresas
     */
    while ($empresa = $resultadoEmpresas->fetch_assoc()) {

        $dbName = $empresa['db_name'];

        $empresasProcesadas++;

        try {

            /*
             * Validar el nombre de la BD.
             *
             * Como $dbName se utiliza directamente en SQL,
             * no debe venir con caracteres no permitidos.
             */
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
                throw new Exception(
                    'Nombre de base de datos inválido: ' . $dbName
                );
            }

            /*
             * Buscar documentos expirados
             *
             * IMPORTANTE:
             * Se utiliza la BD de la empresa mediante:
             *
             * $dbName.t_documentos_temporales
             */
            $tablaDocumentos = "`" . $dbName . "`.`t_documentos_temporales`";

            $sqlDocumentos = "
                SELECT
                    id,
                    token,
                    archivo,
                    fecha_expiracion
                FROM {$tablaDocumentos}
                WHERE fecha_expiracion <= NOW()
            ";

            $resultadoDocumentos = $connPrincipal->query($sqlDocumentos);

            if (!$resultadoDocumentos) {
                throw new Exception(
                    'Error consultando documentos: '
                    . $connPrincipal->error
                );
            }

            /*
             * Recorrer documentos
             */
            while ($documento = $resultadoDocumentos->fetch_assoc()) {

                $encontrados++;

                $id = intval($documento['id']);

                /*
                 * basename evita salir del directorio
                 */
                $archivo = basename($documento['archivo']);

                $rutaArchivo =
                    __DIR__
                    . '/../../../documentos_privados/'
                    . $dbName
                    . '/'
                    . $archivo;

                /*
                 * Eliminar archivo físico
                 */
                if (file_exists($rutaArchivo)) {

                    if (unlink($rutaArchivo)) {

                        $archivosEliminados++;

                    } else {

                        $errores[] = [
                            'db' => $dbName,
                            'id' => $id,
                            'archivo' => $archivo,
                            'error' => 'No se pudo eliminar el archivo'
                        ];

                        /*
                         * Si no pudo eliminarse el archivo,
                         * conservamos el registro.
                         */
                        continue;
                    }

                } else {

                    $archivosNoEncontrados++;
                }

                /*
                 * Eliminar registro directamente en la BD
                 * correspondiente a la empresa.
                 */
                $sqlDelete = "
                    DELETE FROM {$tablaDocumentos}
                    WHERE id = ?
                ";

                $stmt = $connPrincipal->prepare($sqlDelete);

                if (!$stmt) {

                    $errores[] = [
                        'db' => $dbName,
                        'id' => $id,
                        'error' => 'Error preparando DELETE: '
                            . $connPrincipal->error
                    ];

                    continue;
                }

                $stmt->bind_param("i", $id);

                if ($stmt->execute()) {

                    $registrosEliminados++;

                } else {

                    $errores[] = [
                        'db' => $dbName,
                        'id' => $id,
                        'error' => 'Error eliminando registro: '
                            . $stmt->error
                    ];
                }

                $stmt->close();
            }

            $resultadoDocumentos->free();

        } catch (Throwable $e) {

            $empresasConError++;

            $errores[] = [
                'db' => $dbName,
                'error' => $e->getMessage()
            ];

            /*
             * Continuar con la siguiente empresa.
             */
            continue;
        }
    }

    $resultadoEmpresas->free();

    /*
     * Cerrar conexión principal
     */
    $connPrincipal->close();

    /*
     * Respuesta
     */
    echo json_encode([
        'ok' => true,
        'mensaje' => 'Limpieza ejecutada correctamente',

        'empresas_procesadas' => $empresasProcesadas,
        'empresas_con_error' => $empresasConError,

        'documentos_encontrados' => $encontrados,
        'archivos_eliminados' => $archivosEliminados,
        'archivos_no_encontrados' => $archivosNoEncontrados,
        'registros_eliminados' => $registrosEliminados,

        'errores' => $errores
    ]);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => $e->getMessage()
    ]);
}