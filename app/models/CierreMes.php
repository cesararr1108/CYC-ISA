<?php
session_start();
require_once('bd.php');
require_once('funciones.php');
$conn = conectar();
switch ($_POST["op"]) {
  case "periodos":

    $ultimoperiodo = GenerarArray("  SELECT 
                                          MAX(ano) AS max_ano,
                                          MAX(mes) AS max_mes
                                      FROM t_referencias_mov
                                      WHERE ano = (SELECT MAX(ano) FROM t_referencias_mov); ");

    $periodoCerrado = GenerarArray("SELECT
                                        MAX(anio) AS max_ano,
                                        MAX(mes) AS max_mes
                                     FROM t_cierre_mes;");

    // Validar que existan datos reales
    if (
      empty($ultimoperiodo) ||
      empty($ultimoperiodo[0]['max_ano']) ||
      empty($ultimoperiodo[0]['max_mes'])
    ) {
      echo json_encode([
        'error' => 'No se encontraron periodos válidos en la base de datos.'
      ]);
      break;
    }

    $max_ano = (int)$ultimoperiodo[0]['max_ano'];
    $max_mes = (int)$ultimoperiodo[0]['max_mes'];

    // Crear periodo SIEMPRE con dia 01 (clave para evitar saltos de mes)
    $periodo_actual = DateTime::createFromFormat(
      'Y-m-d',
      sprintf('%04d-%02d-01', $max_ano, $max_mes)
    );

    if (!$periodo_actual) {
      echo json_encode([
        'error' => 'Periodo inválido en la base de datos.'
      ]);
      break;
    }

    //periodo cerrado
    $max_ano_cerrado = (int)$periodoCerrado[0]['max_ano'];
    $max_mes_cerrado = (int)$periodoCerrado[0]['max_mes'];

    // Crear periodo SIEMPRE con dia 01 (clave para evitar saltos de mes)
    $periodo_cerrado = DateTime::createFromFormat(
      'Y-m-d',
      sprintf('%04d-%02d-01', $max_ano_cerrado, $max_mes_cerrado)
    );

    if (!$periodo_cerrado) {
      echo json_encode([
        'error' => 'Periodo inválido en la base de datos.'
      ]);
      break;
    }

    // Fecha actual (también con día 01 para coherencia)
    $fecha_actual = DateTime::createFromFormat(
      'Y-m-d',
      date('Y-m-01')
    );

    // Calcular nuevo periodo (suma segura de meses)
    $nuevo_periodo = clone $periodo_actual;
    $nuevo_periodo->modify('+1 month');

    echo json_encode([
      'periodo_actual' => $periodo_actual->format('Y-m'),
      'periodo_cerrado' => $periodo_cerrado->format('Y-m'),
      'nuevo_periodo' => $nuevo_periodo->format('Y-m'),
      'fecha_actual' => $fecha_actual->format('Y-m')
    ]);

    break;

  case 'cierre':

    list($ano, $mes) = explode('-', $_POST['periodo']);
    $ano = (int)$ano;
    $mes = (int)$mes;

    // ?? Calcular per�odo inmediatamente anterior
    if ($mes == 1) {
      $mes_ant = 12;
      $ano_ant = $ano - 1;
    } else {
      $mes_ant = $mes - 1;
      $ano_ant = $ano;
    }

    $conn->begin_transaction(); // ?? INICIO DE TRANSACCI�N

    try {

      // ?? Insertar movimientos del mes anterior como saldo inicial
      $sql = "INSERT INTO t_referencias_mov(
                id_referencia,
                cant_ini,
                cant_ent,
                cant_sal,
                costo_ini,
                costo_ent,
                costo_sal,
                costo_tras,
                costo_comp,
                costo_ven,
                mes,
                ano,
                costo_dev,
                costo_dev_comp,
                cant_ven,
                cant_dev,
                cant_dev_comp,
                id_bodega,
                cant_comp
            )
            SELECT 
                id_referencia,
                cant_ini + cant_ent - cant_sal AS cant_ini,
                0 AS cant_ent,
                0 AS cant_sal,
                costo_ini + costo_ent - costo_sal AS costo_ini,
                0 AS costo_ent,
                0 AS costo_sal,
                0 AS costo_tras,
                0 AS costo_comp,
                0 AS costo_ven,
                $mes,
                $ano,
                0 AS costo_dev,
                0 AS costo_dev_comp,
                0 AS cant_ven,
                0 AS cant_dev,
                0 AS cant_dev_comp,
                id_bodega,
                0 AS cant_comp
            FROM t_referencias_mov
            WHERE mes = $mes_ant
              AND ano = $ano_ant
        ";

      if (!$conn->query($sql)) {
        throw new Exception("Error al insertar en t_referencias_mov: " . $conn->error);
      }

      // ?? Registrar cierre del mes anterior
      $usuario = $_SESSION["Ses_id"];

      $sql_cierre = "
            INSERT INTO t_cierre_mes (
                mes,
                anio,
                fecha,
                usuario
            ) VALUES (
                $mes_ant,
                $ano_ant,
                CURRENT_DATE(),
                $usuario
            )
        ";

      if (!$conn->query($sql_cierre)) {
        throw new Exception("Error al insertar en t_cierre_mes: " . $conn->error);
      }

      $conn->commit(); // ? COMMIT

      echo json_encode(array(
        'error' => false,
        'option' => 'success',
        'title' => '¡Cierre exitoso!',
        'message' => 'El período se ha cerrado correctamente.'
      ));
    } catch (Exception $e) {

      $conn->rollback(); // ? ROLLBACK

      echo json_encode(array(
        'error' => true,
        'option' => 'error',
        'title' => '¡Error!',
        'message' => 'Hubo un error al intentar cerrar el período: ' . $e->getMessage()
      ));
    }

    break;
}
