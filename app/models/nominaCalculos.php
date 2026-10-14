<?php

/**
 * Motor de cálculo de nómina (Colombia)
 *
 * Normativa aplicada:
 *  - CST arts. 127, 128, 134 (salario, pagos no salariales, mes comercial de 30 días)
 *  - Ley 100/1993 y Ley 797/2003: salud 12,5 % (4 % trabajador / 8,5 % empleador),
 *    pensión 16 % (4 % / 12 %), Fondo de Solidaridad Pensional escalonado 1 %–2 % desde 4 SMLMV
 *  - Ley 1393/2010 art. 30: pagos no salariales que excedan el 40 % de la remuneración suman al IBC
 *  - Decreto 1072/2015 y 1295/1994: ARL según clase de riesgo (I a V) sobre días laborados
 *  - Ley 1607/2012 y art. 114-1 ET: exoneración de salud empleador, SENA e ICBF por trabajadores
 *    que devenguen menos de 10 SMLMV (personas jurídicas y naturales con 2+ empleados)
 *  - Decreto 2090/2003: +10 puntos de pensión a cargo del empleador en actividades de alto riesgo
 *  - Salario integral (art. 132 CST): IBC 70 %, sin prima, cesantías ni intereses
 *  - Retención en la fuente procedimiento 1 (arts. 383, 387, 388 ET; Ley 2277/2022):
 *    renta exenta 25 % limitada a 790 UVT anuales y límite global del 40 % / 1.340 UVT anuales
 *  - Auxilio de transporte para salarios hasta 2 SMLMV, proporcional a días laborados
 *
 * Todos los valores legales se leen de t_nomina_parametros por año. Los parámetros monetarios
 * (SMLMV, auxilio, UVT y porcentajes base) son obligatorios: si faltan, no se liquida.
 */

require_once(__DIR__ . '/nominaHelpers.php');

class CalculosNomina
{
  /** Parámetros que deben existir en t_nomina_parametros para el año */
  const PARAMETROS_REQUERIDOS = array(
    'smlv'                     => 'Salario mínimo (smlv)',
    'aux_transporte'           => 'Auxilio de transporte (aux_transporte)',
    'uvt'                      => 'Valor UVT (uvt)',
    'mult_smlv_aux_transporte' => 'Múltiplo SMLV auxilio transporte (mult_smlv_aux_transporte)',
    'mult_smlv_fsp'            => 'Múltiplo SMLV Fondo Solidaridad (mult_smlv_fsp)',
    'pct_salud_empleado'       => 'Salud empleado (pct_salud_empleado)',
    'pct_pension_empleado'     => 'Pensión empleado (pct_pension_empleado)',
    'pct_fsp_solidaridad'      => 'FSP solidaridad (pct_fsp_solidaridad)',
    'pct_fsp_subsistencia'     => 'FSP subsistencia (pct_fsp_subsistencia)',
    'pct_salud_empleador'      => 'Salud empleador (pct_salud_empleador)',
    'pct_pension_empleador'    => 'Pensión empleador (pct_pension_empleador)',
    'pct_arl'                  => 'ARL clase I (pct_arl)',
    'pct_caja'                 => 'Caja de compensación (pct_caja)',
    'pct_sena'                 => 'SENA (pct_sena)',
    'pct_icbf'                 => 'ICBF (pct_icbf)',
    'pct_cesantias'            => 'Cesantías (pct_cesantias)',
    'pct_intereses_cesantias'  => 'Intereses de cesantías (pct_intereses_cesantias)',
    'pct_prima'                => 'Prima de servicios (pct_prima)',
    'pct_vacaciones'           => 'Vacaciones (pct_vacaciones)',
  );

  /**
   * Parámetros técnicos con valor legal por defecto (se pueden sobrescribir en la tabla).
   * No incluyen valores que cambian cada año (SMLMV, auxilio, UVT).
   */
  const PARAMETROS_DEFECTO = array(
    'exonerado_art114'              => 1,      // 1 = empresa exonerada (art. 114-1 ET); 0 = no exonerada
    'tope_exoneracion_smlv'         => 10,
    'tope_ibc_smlv'                 => 25,
    'pct_pension_alto_riesgo'       => 0.10,
    'pct_ibc_salario_integral'      => 0.70,
    'pct_limite_no_salarial'        => 0.40,
    'exencion_renta_pct'            => 0.25,
    'exencion_renta_max_uvt_anual'  => 790,
    'limite_deducciones_pct'        => 0.40,
    'limite_deducciones_uvt_anual'  => 1340,
    'redondeo_retencion'            => 1000,   // múltiplo al que se aproxima la retención
    'redondeo_aportes'              => 1,      // 1 = al peso; 100 = múltiplo de 100 (PILA)
    'pct_arl_clase_1'               => 0.00522,
    'pct_arl_clase_2'               => 0.01044,
    'pct_arl_clase_3'               => 0.02436,
    'pct_arl_clase_4'               => 0.04350,
    'pct_arl_clase_5'               => 0.06960,
    'horas_semanales'               => 42,     // Ley 2101/2021: 42 h desde el 15-jul-2026
    'max_horas_extra_semana'        => 12,
    'factor_hora_extra_diurna'      => 1.25,
    'factor_hora_extra_nocturna'    => 1.75,
    'factor_hora_extra_festiva'     => 2.00,
    'pct_recargo_nocturno'          => 0.35,
  );

  private static $paramsCache = array();
  private static $faltantesCache = array();

  public static function cargarParametros($anio = null)
  {
    $anio = intval($anio ?: date('Y'));
    if (isset(self::$paramsCache[$anio])) {
      return self::$paramsCache[$anio];
    }
    // Los obligatorios arrancan en 0 para que un año sin configurar no genere avisos de PHP;
    // parametrosFaltantes() sigue impidiendo liquidar o contabilizar con ellos.
    $params = array_fill_keys(array_keys(self::PARAMETROS_REQUERIDOS), 0) + self::PARAMETROS_DEFECTO;
    $definidos = array();
    foreach (nomina_select("SELECT clave, valor FROM t_nomina_parametros WHERE anio = $anio AND estado = 'ACTIVO'") as $row) {
      $params[$row['clave']] = floatval($row['valor']);
      $definidos[$row['clave']] = true;
    }
    // Compatibilidad: si la clase I no está definida explícitamente se usa pct_arl
    if (!isset($definidos['pct_arl_clase_1']) && isset($params['pct_arl'])) {
      $params['pct_arl_clase_1'] = $params['pct_arl'];
    }
    self::$faltantesCache[$anio] = array();
    foreach (self::PARAMETROS_REQUERIDOS as $clave => $nombre) {
      if (!isset($definidos[$clave])) {
        self::$faltantesCache[$anio][] = $nombre;
      }
    }
    self::$paramsCache[$anio] = $params;
    return $params;
  }

  /** Lista de parámetros obligatorios ausentes para el año */
  public static function parametrosFaltantes($anio)
  {
    $anio = intval($anio);
    self::cargarParametros($anio);
    return self::$faltantesCache[$anio];
  }

  public static function param($clave, $anio = null)
  {
    $p = self::cargarParametros($anio);
    return $p[$clave] ?? 0;
  }

  /** Redondeo hacia arriba a un múltiplo (aportes) */
  public static function redondearArriba($valor, $multiplo = 1)
  {
    $multiplo = max(1, floatval($multiplo));
    if ($valor <= 0) {
      return 0;
    }
    return ceil(round($valor, 4) / $multiplo) * $multiplo;
  }

  /** Redondeo al múltiplo más cercano (retención) */
  public static function redondearCercano($valor, $multiplo = 1)
  {
    $multiplo = max(1, floatval($multiplo));
    return round($valor / $multiplo) * $multiplo;
  }

  /** Valor de la hora ordinaria según jornada máxima legal (horas semanales × 5 = horas mensuales) */
  public static function calcularValorHora($salarioMensual, $anio = null, $horasMes = null)
  {
    $horasMes = $horasMes ?: self::param('horas_semanales', $anio) * 5;
    return $horasMes > 0 ? round($salarioMensual / $horasMes, 2) : 0;
  }

  /** Tasa total del Fondo de Solidaridad Pensional según IBC mensual en SMLMV */
  public static function tasaFsp($ibcMensual, $anio = null)
  {
    $p = self::cargarParametros($anio);
    $smlv = $p['smlv'];
    if ($smlv <= 0 || $ibcMensual < $smlv * $p['mult_smlv_fsp']) {
      return 0;
    }
    $base = $p['pct_fsp_solidaridad'] + $p['pct_fsp_subsistencia']; // 1 %
    $veces = $ibcMensual / $smlv;
    if ($veces < 16) return $base;
    if ($veces <= 17) return $base + 0.002;
    if ($veces <= 18) return $base + 0.004;
    if ($veces <= 19) return $base + 0.006;
    if ($veces <= 20) return $base + 0.008;
    return $base + 0.010;
  }

  /** Tarifa ARL por clase de riesgo (1 a 5) */
  public static function tasaArl($clase, $anio = null)
  {
    $clase = intval($clase);
    if ($clase < 1 || $clase > 5) {
      $clase = 1;
    }
    return self::param('pct_arl_clase_' . $clase, $anio);
  }

  /**
   * Retención en la fuente – procedimiento 1 (art. 383 ET), base mensual.
   *
   * @param float $ingresosLaborales  pagos laborales del mes (salario, extras, auxilios, etc.)
   * @param float $ingresosNoGravados aportes obligatorios del trabajador (salud, pensión, FSP)
   * @return array ['base' => renta gravable, 'retencion' => valor]
   */
  public static function calcularRetencionFuente($ingresosLaborales, $ingresosNoGravados = 0, $anio = null)
  {
    $p = self::cargarParametros($anio);
    $uvt = $p['uvt'];
    if ($uvt <= 0 || $ingresosLaborales <= 0) {
      return array('base' => 0, 'retencion' => 0);
    }
    $subtotal = max(0, $ingresosLaborales - $ingresosNoGravados);

    // Renta exenta del 25 % (num. 10 art. 206 ET), tope 790 UVT anuales
    $exenta = min($subtotal * $p['exencion_renta_pct'], $uvt * $p['exencion_renta_max_uvt_anual'] / 12);
    // Límite global de deducciones y rentas exentas: 40 % y 1.340 UVT anuales (art. 336 ET)
    $exenta = min($exenta, $subtotal * $p['limite_deducciones_pct'], $uvt * $p['limite_deducciones_uvt_anual'] / 12);

    $base = max(0, $subtotal - $exenta);
    $baseUvt = $base / $uvt;

    if ($baseUvt <= 95) {
      $retUvt = 0;
    } elseif ($baseUvt <= 150) {
      $retUvt = ($baseUvt - 95) * 0.19;
    } elseif ($baseUvt <= 360) {
      $retUvt = ($baseUvt - 150) * 0.28 + 10;
    } elseif ($baseUvt <= 640) {
      $retUvt = ($baseUvt - 360) * 0.33 + 69;
    } elseif ($baseUvt <= 945) {
      $retUvt = ($baseUvt - 640) * 0.35 + 162;
    } elseif ($baseUvt <= 2300) {
      $retUvt = ($baseUvt - 945) * 0.37 + 268;
    } else {
      $retUvt = ($baseUvt - 2300) * 0.39 + 770;
    }

    return array(
      'base'      => round($base, 2),
      'retencion' => max(0, self::redondearCercano($retUvt * $uvt, $p['redondeo_retencion'])),
    );
  }

  /**
   * Bases de cotización y prestaciones a partir de las líneas devengadas.
   * Se usa tanto al liquidar como al contabilizar (respetando ajustes manuales del detalle).
   *
   * @param array $lineas       [['clase'=>..., 'total'=>..., 'incapacidad'=>bool, 'vacaciones'=>bool], ...]
   *                            clases: BASICO, TRANSPORTE, SALARIAL, AUSENCIA, PRESTACION, NO_SALARIAL
   * @param array $contrato     fila de t_nomina_contratos
   * @param int   $diasContrato días del período en que el contrato estuvo vigente
   */
  public static function calcularBases(array $lineas, array $contrato, $diasContrato, $anio = null)
  {
    $p = self::cargarParametros($anio);
    $smlv = $p['smlv'];
    $integral = intval($contrato['salario_integral'] ?? 0) === 1;

    $sum = array('BASICO' => 0, 'TRANSPORTE' => 0, 'SALARIAL' => 0, 'AUSENCIA' => 0, 'PRESTACION' => 0, 'NO_SALARIAL' => 0);
    $incapacidades = 0;
    $vacaciones = 0;
    foreach ($lineas as $l) {
      $clase = isset($sum[$l['clase']]) ? $l['clase'] : 'SALARIAL';
      $sum[$clase] += (float)$l['total'];
      if (!empty($l['incapacidad'])) {
        $incapacidades += (float)$l['total'];
      }
      if (!empty($l['vacaciones'])) {
        $vacaciones += (float)$l['total'];
      }
    }

    $salarial = $sum['BASICO'] + $sum['SALARIAL'] + $sum['AUSENCIA'];
    $remuneracion = $salarial + $sum['NO_SALARIAL'];

    // Ley 1393/2010 art. 30: exceso de pagos no salariales sobre el 40 % de la remuneración
    $excesoNoSalarial = max(0, $sum['NO_SALARIAL'] - $remuneracion * $p['pct_limite_no_salarial']);

    $baseCotizacion = ($integral ? $salarial * $p['pct_ibc_salario_integral'] : $salarial) + $excesoNoSalarial;

    $dias = max(0, intval($diasContrato));
    $piso = $dias > 0 ? $smlv / 30 * $dias : 0;
    $techo = $smlv * $p['tope_ibc_smlv'] / 30 * max(1, $dias);
    $ibc = self::redondearArriba(min(max($baseCotizacion, $piso), $techo), 1);

    // Parafiscales (caja, SENA, ICBF) no se liquidan sobre incapacidades
    $factorIbc = $baseCotizacion > 0 ? $ibc / $baseCotizacion : 1;
    $baseParafiscales = max(0, $ibc - $incapacidades * ($integral ? $p['pct_ibc_salario_integral'] : 1) * $factorIbc);
    // ARL solo sobre días efectivamente laborados (sin incapacidades, licencias ni vacaciones)
    $baseArl = max(0, $ibc - $sum['AUSENCIA'] * ($integral ? $p['pct_ibc_salario_integral'] : 1) * $factorIbc);
    if ($sum['BASICO'] > 0 && $baseArl < $smlv / 30) {
      $baseArl = 0;
    }

    $basePrestaciones = $integral ? 0 : $sum['BASICO'] + $sum['SALARIAL'] + $sum['AUSENCIA'] + $sum['TRANSPORTE'];
    // Vacaciones: salario ordinario, sin horas extras ni auxilio (art. 192 CST)
    $baseVacaciones = $sum['BASICO'] + $sum['AUSENCIA'] - $vacaciones;

    $ibcMensual = $dias > 0 ? $ibc / $dias * 30 : $ibc;

    return array(
      'dias_contrato'      => $dias,
      'salarial'           => round($salarial, 2),
      'no_salarial'        => round($sum['NO_SALARIAL'], 2),
      'exceso_no_salarial' => round($excesoNoSalarial, 2),
      'transporte'         => round($sum['TRANSPORTE'], 2),
      'ibc'                => round($ibc, 2),
      'ibc_mensual'        => round($ibcMensual, 2),
      'base_parafiscales'  => round($baseParafiscales, 2),
      'base_arl'           => round($baseArl, 2),
      'base_prestaciones'  => round(max(0, $basePrestaciones), 2),
      'base_vacaciones'    => round(max(0, $baseVacaciones), 2),
    );
  }

  /**
   * Aportes del empleador y provisiones de prestaciones sociales.
   * Devuelve una lista de ítems listos para contabilizar (gasto contra pasivo).
   */
  public static function calcularCargasEmpleador(array $bases, array $contrato, $anio = null)
  {
    $p = self::cargarParametros($anio);
    $smlv = $p['smlv'];
    $integral = intval($contrato['salario_integral'] ?? 0) === 1;
    $pensionado = trim((string)($contrato['subtipo_trabajador'] ?? '00')) === '01';
    $altoRiesgo = intval($contrato['alto_riesgo'] ?? 0) === 1;
    $red = $p['redondeo_aportes'];

    // Exoneración art. 114-1 ET: aplica a quien devenga menos de 10 SMLMV (nunca a salario integral)
    $salarioMensualRef = max((float)($contrato['salario_basico'] ?? 0), $bases['ibc_mensual']);
    $exonerado = intval($p['exonerado_art114']) === 1
      && !$integral
      && $salarioMensualRef < $smlv * $p['tope_exoneracion_smlv'];

    $ibc = $bases['ibc'];
    $items = array();
    $add = function ($clave, $nombre, $grupo, $base, $pct, $valor, $gasto, $pasivo, $entidad) use (&$items) {
      if ($valor <= 0) {
        return;
      }
      $items[] = array(
        'clave' => $clave, 'nombre' => $nombre, 'grupo' => $grupo,
        'base' => round($base, 2), 'porcentaje' => round($pct, 6), 'valor' => round($valor, 2),
        'cuenta_gasto' => $gasto, 'cuenta_pasivo' => $pasivo, 'entidad' => $entidad,
      );
    };

    // --- Aportes a seguridad social y parafiscales ---
    if (!$exonerado) {
      $add('salud_empleador', 'Salud empleador', 'APORTE', $ibc, $p['pct_salud_empleador'],
        self::redondearArriba($ibc * $p['pct_salud_empleador'], $red),
        'cuenta_gasto_salud_patronal', 'cuenta_pasivo_salud', 'SALUD');
    }
    if (!$pensionado) {
      $pctPension = $p['pct_pension_empleador'] + ($altoRiesgo ? $p['pct_pension_alto_riesgo'] : 0);
      $add('pension_empleador', 'Pensión empleador' . ($altoRiesgo ? ' (alto riesgo)' : ''), 'APORTE', $ibc, $pctPension,
        self::redondearArriba($ibc * $pctPension, $red),
        'cuenta_gasto_pension_patronal', 'cuenta_pasivo_pension', 'PENSION');
    }
    $pctArl = self::tasaArl($contrato['clase_riesgo_arl'] ?? 1, $anio);
    $add('arl', 'ARL clase ' . max(1, min(5, intval($contrato['clase_riesgo_arl'] ?? 1))), 'APORTE', $bases['base_arl'], $pctArl,
      self::redondearArriba($bases['base_arl'] * $pctArl, $red),
      'cuenta_gasto_arl', 'cuenta_pasivo_arl', 'ARL');
    $add('caja', 'Caja de compensación', 'APORTE', $bases['base_parafiscales'], $p['pct_caja'],
      self::redondearArriba($bases['base_parafiscales'] * $p['pct_caja'], $red),
      'cuenta_gasto_caja', 'cuenta_pasivo_caja', 'CAJA_COMPENSACION');
    if (!$exonerado) {
      $add('sena', 'SENA', 'APORTE', $bases['base_parafiscales'], $p['pct_sena'],
        self::redondearArriba($bases['base_parafiscales'] * $p['pct_sena'], $red),
        'cuenta_gasto_sena', 'cuenta_pasivo_sena', 'SENA');
      $add('icbf', 'ICBF', 'APORTE', $bases['base_parafiscales'], $p['pct_icbf'],
        self::redondearArriba($bases['base_parafiscales'] * $p['pct_icbf'], $red),
        'cuenta_gasto_icbf', 'cuenta_pasivo_icbf', 'ICBF');
    }

    // --- Provisiones de prestaciones sociales ---
    if (!$integral) {
      $ces = round($bases['base_prestaciones'] * $p['pct_cesantias'], 0);
      $add('prov_cesantias', 'Provisión cesantías', 'PROVISION', $bases['base_prestaciones'], $p['pct_cesantias'], $ces,
        'cuenta_gasto_cesantias', 'cuenta_pasivo_cesantias', null);
      // Intereses: 12 % anual sobre cesantías ⇒ 12 % de la provisión de cesantías del período
      $add('prov_int_cesantias', 'Provisión intereses de cesantías', 'PROVISION', $ces, $p['pct_intereses_cesantias'],
        round($ces * $p['pct_intereses_cesantias'], 0),
        'cuenta_gasto_int_cesantias', 'cuenta_pasivo_int_cesantias', null);
      $add('prov_prima', 'Provisión prima de servicios', 'PROVISION', $bases['base_prestaciones'], $p['pct_prima'],
        round($bases['base_prestaciones'] * $p['pct_prima'], 0),
        'cuenta_gasto_prima', 'cuenta_pasivo_prima', null);
    }
    $add('prov_vacaciones', 'Provisión vacaciones', 'PROVISION', $bases['base_vacaciones'], $p['pct_vacaciones'],
      round($bases['base_vacaciones'] * $p['pct_vacaciones'], 0),
      'cuenta_gasto_vacaciones', 'cuenta_pasivo_vacaciones', null);

    return array('exonerado' => $exonerado, 'items' => $items);
  }

  /**
   * Liquidación completa de un empleado para un período.
   *
   * @param array $contrato   fila de t_nomina_contratos
   * @param array $novedades  filas de novedades del período (con datos del concepto)
   * @param array $periodo    resultado de nomina_resolver_periodo()
   * @param array $acumulado  primera quincena del mes (para retención mensual en la 2ª quincena):
   *                          ['ingresos' => x, 'no_gravados' => y, 'retencion' => z]
   * @return array|null       null si el contrato no estuvo vigente en el período
   */
  public static function calcularLiquidacion(array $contrato, array $novedades, array $periodo, array $acumulado = array())
  {
    $anio = $periodo['anio'];
    $p = self::cargarParametros($anio);
    $smlv = $p['smlv'];

    $diasContrato = nomina_dias_contrato_en_periodo($contrato, $periodo);
    if ($diasContrato <= 0) {
      return null;
    }

    $integral = intval($contrato['salario_integral'] ?? 0) === 1;
    $pensionado = trim((string)($contrato['subtipo_trabajador'] ?? '00')) === '01';
    $salarioMensual = (float)($contrato['salario_basico'] ?? 0);
    $avisos = array();

    // Clasificar novedades y descontar días de ausencia
    $diasAusencia = 0;
    $devNov = array();
    $dedNov = array();
    foreach ($novedades as $n) {
      $tipo = strtoupper(trim((string)($n['tipo'] ?? '')));
      $valor = round((float)($n['total'] ?? 0), 2);
      $concepto = trim((string)($n['concepto'] ?? '')) ?: 'novedad';
      $meta = nomina_concepto($n['id_concepto'] ?? 0, $concepto) ?: $n;

      if ($tipo === 'DEVENGADO') {
        $clas = nomina_clasificar_devengo($meta);
        if ($clas['afecta_dias']) {
          $dias = intval($n['dias_incapacidad'] ?? 0) ?: intval(round((float)($n['cantidad'] ?? 0)));
          $diasAusencia += max(0, $dias);
        }
        $k = $concepto;
        if (!isset($devNov[$k])) {
          $devNov[$k] = array('concepto' => $k, 'id_concepto' => intval($n['id_concepto'] ?? 0) ?: null,
            'cantidad' => 0, 'valor_unitario' => (float)($n['valor_unitario'] ?? 0), 'total' => 0) + $clas;
        }
        $devNov[$k]['cantidad'] += (float)($n['cantidad'] ?? 0);
        $devNov[$k]['total'] += $valor;
      } elseif ($tipo === 'DEDUCCION') {
        $k = $concepto;
        if (!isset($dedNov[$k])) {
          $dedNov[$k] = array('concepto' => $k, 'id_concepto' => intval($n['id_concepto'] ?? 0) ?: null,
            'cantidad' => 0, 'valor_unitario' => (float)($n['valor_unitario'] ?? 0), 'total' => 0);
        }
        $dedNov[$k]['cantidad'] += (float)($n['cantidad'] ?? 0);
        $dedNov[$k]['total'] += $valor;
      }
    }

    if ($diasAusencia > $diasContrato) {
      $avisos[] = "Los días de ausencia ($diasAusencia) superan los días del período ($diasContrato); se limitan.";
      $diasAusencia = $diasContrato;
    }
    $diasTrabajados = $diasContrato - $diasAusencia;

    // --- DEVENGADOS ---
    $devengos = array();
    $valorDia = $salarioMensual / 30;
    $devengos[] = array('concepto' => 'salario_basico', 'id_concepto' => null, 'clase' => 'BASICO',
      'cantidad' => $diasTrabajados, 'valor_unitario' => round($valorDia, 2),
      'total' => round($valorDia * $diasTrabajados, 2));

    $auxContrato = $contrato['aux_transporte'] ?? null;
    $auxMensual = ($auxContrato === null || $auxContrato === '') ? $p['aux_transporte'] : (float)$auxContrato;
    $topeAux = $smlv * $p['mult_smlv_aux_transporte'];
    if ($integral) {
      // El salario integral no da derecho a auxilio de transporte
    } elseif ($salarioMensual > $topeAux) {
      $avisos[] = 'Auxilio de transporte no aplica: salario supera ' . intval($p['mult_smlv_aux_transporte']) . ' SMLMV';
    } elseif ($auxMensual > 0 && $diasTrabajados > 0) {
      $devengos[] = array('concepto' => 'aux_transporte', 'id_concepto' => null, 'clase' => 'TRANSPORTE',
        'cantidad' => $diasTrabajados, 'valor_unitario' => round($auxMensual / 30, 2),
        'total' => round($auxMensual / 30 * $diasTrabajados, 2));
    }

    foreach ($devNov as $d) {
      $d['total'] = round($d['total'], 2);
      if ($d['total'] > 0) {
        $devengos[] = $d;
      }
    }

    $totalDevengos = 0;
    foreach ($devengos as $d) {
      $totalDevengos += $d['total'];
    }

    // --- BASES ---
    $bases = self::calcularBases($devengos, $contrato, $diasContrato, $anio);
    $ibc = $bases['ibc'];
    $red = $p['redondeo_aportes'];

    // --- DEDUCCIONES DE LEY ---
    $deducciones = array();
    $salud = self::redondearArriba($ibc * $p['pct_salud_empleado'], $red);
    $deducciones[] = array('concepto' => 'aporte_salud', 'id_concepto' => null, 'cantidad' => $p['pct_salud_empleado'] * 100,
      'valor_unitario' => $ibc, 'total' => $salud);
    $noGravados = $salud;

    if ($pensionado) {
      $avisos[] = 'Trabajador pensionado (subtipo 01): no cotiza a pensión ni al Fondo de Solidaridad';
    } else {
      $pension = self::redondearArriba($ibc * $p['pct_pension_empleado'], $red);
      $deducciones[] = array('concepto' => 'aporte_pension', 'id_concepto' => null, 'cantidad' => $p['pct_pension_empleado'] * 100,
        'valor_unitario' => $ibc, 'total' => $pension);
      $noGravados += $pension;

      $tasaFsp = self::tasaFsp($bases['ibc_mensual'], $anio);
      if ($tasaFsp > 0) {
        $solidaridad = self::redondearArriba($ibc * $p['pct_fsp_solidaridad'], $red);
        $subsistencia = self::redondearArriba($ibc * ($tasaFsp - $p['pct_fsp_solidaridad']), $red);
        $deducciones[] = array('concepto' => 'aporte_fsp_solidaridad', 'id_concepto' => null,
          'cantidad' => $p['pct_fsp_solidaridad'] * 100, 'valor_unitario' => $ibc, 'total' => $solidaridad);
        $deducciones[] = array('concepto' => 'aporte_fsp_subsistencia', 'id_concepto' => null,
          'cantidad' => round(($tasaFsp - $p['pct_fsp_solidaridad']) * 100, 2), 'valor_unitario' => $ibc, 'total' => $subsistencia);
        $noGravados += $solidaridad + $subsistencia;
      }
    }

    foreach ($dedNov as $d) {
      $d['total'] = round($d['total'], 2);
      if ($d['total'] > 0) {
        $deducciones[] = $d;
      }
    }

    // --- RETENCIÓN EN LA FUENTE (mensual; en quincenal se calcula en la 2ª quincena con el acumulado) ---
    $retencion = 0;
    $baseRetencion = 0;
    if ($periodo['quincena'] !== 1) {
      $ingresosMes = $totalDevengos + (float)($acumulado['ingresos'] ?? 0);
      $noGravadosMes = $noGravados + (float)($acumulado['no_gravados'] ?? 0);
      $rf = self::calcularRetencionFuente($ingresosMes, $noGravadosMes, $anio);
      $baseRetencion = $rf['base'];
      $retencion = max(0, $rf['retencion'] - (float)($acumulado['retencion'] ?? 0));
      if ($retencion > 0) {
        $deducciones[] = array('concepto' => 'retencion_fuente', 'id_concepto' => null, 'cantidad' => 1,
          'valor_unitario' => $baseRetencion, 'total' => $retencion);
      }
    }

    $totalDeducciones = 0;
    foreach ($deducciones as $d) {
      $totalDeducciones += $d['total'];
    }

    $neto = round($totalDevengos - $totalDeducciones, 2);
    if ($neto < 0) {
      $avisos[] = 'El neto a pagar es negativo: revise las deducciones del período';
    }
    // Art. 149 CST: deducciones (distintas a las de ley) no pueden afectar el salario mínimo
    $deduccionesVoluntarias = 0;
    foreach ($dedNov as $d) {
      $deduccionesVoluntarias += $d['total'];
    }
    if ($deduccionesVoluntarias > 0 && ($totalDevengos - $totalDeducciones) < $smlv / 30 * $diasTrabajados * 0.5) {
      $avisos[] = 'Las deducciones dejan al trabajador por debajo del 50 % del salario mínimo del período (art. 149 y 156 CST)';
    }

    $cargas = self::calcularCargasEmpleador($bases, $contrato, $anio);

    return array(
      'devengos'    => $devengos,
      'deducciones' => $deducciones,
      'bases'       => $bases,
      'cargas'      => $cargas,
      'dias'        => array('contrato' => $diasContrato, 'ausencia' => $diasAusencia, 'trabajados' => $diasTrabajados),
      'totales'     => array(
        'devengos_total'    => round($totalDevengos, 2),
        'deducciones_total' => round($totalDeducciones, 2),
        'neto_pago'         => $neto,
        'retencion_fuente'  => $retencion,
        'base_retencion'    => $baseRetencion,
        'ingresos_no_gravados' => round($noGravados, 2),
      ),
      'avisos'      => $avisos,
      'parametros'  => array('anio' => $anio, 'smlv' => $smlv, 'uvt' => $p['uvt'], 'aux_transporte' => $p['aux_transporte']),
    );
  }

  /**
   * Reconstruye las líneas devengadas (con su clase) desde t_nomina_detalle.
   * Permite que la contabilización respete los ajustes manuales hechos en el detalle.
   */
  public static function lineasDesdeDetalle(array $detalles)
  {
    $lineas = array();
    foreach ($detalles as $d) {
      if (strtoupper($d['tipo']) !== 'DEVENGADO') {
        continue;
      }
      $concepto = trim((string)$d['concepto']);
      if ($concepto === 'salario_basico') {
        $l = array('clase' => 'BASICO', 'afecta_dias' => false, 'incapacidad' => false, 'vacaciones' => false, 'hora_extra' => false, 'cuenta' => '');
      } elseif ($concepto === 'aux_transporte') {
        $l = array('clase' => 'TRANSPORTE', 'afecta_dias' => false, 'incapacidad' => false, 'vacaciones' => false, 'hora_extra' => false, 'cuenta' => '');
      } else {
        $meta = nomina_concepto($d['id_concepto'] ?? 0, $concepto) ?: array('clave' => $concepto);
        $l = nomina_clasificar_devengo($meta);
      }
      $l['concepto'] = $concepto;
      $l['total'] = (float)$d['total'];
      $lineas[] = $l;
    }
    return $lineas;
  }
}

/* ===========================================================================
 * Endpoints (solo cuando este archivo es invocado directamente)
 * ========================================================================= */
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') !== realpath(__FILE__)) {
  return;
}

$op = $_POST['op'] ?? '';

switch ($op) {
  case 'calcular_valor_hora':
    $salario = floatval($_POST['salario'] ?? 0);
    $idContrato = intval($_POST['id_contrato'] ?? 0);
    if ($idContrato > 0) {
      $c = nomina_select("SELECT salario_basico FROM t_nomina_contratos WHERE id = $idContrato LIMIT 1");
      $salario = floatval($c[0]['salario_basico'] ?? 0);
    }
    $anio = intval($_POST['anio'] ?? date('Y'));
    responder(false, 'Cálculo de valor hora', array(
      'valor' => CalculosNomina::calcularValorHora($salario, $anio),
      'horas_mes' => CalculosNomina::param('horas_semanales', $anio) * 5,
      'salario' => $salario
    ));
    break;

  case 'calcular_horas_extras':
    $valorHora = floatval($_POST['valor_hora'] ?? 0);
    $horas = floatval($_POST['horas'] ?? 0);
    $factores = array(
      'DIURNA' => CalculosNomina::param('factor_hora_extra_diurna'),
      'NOCTURNA' => CalculosNomina::param('factor_hora_extra_nocturna'),
      'FESTIVA' => CalculosNomina::param('factor_hora_extra_festiva'),
    );
    $tipo = strtoupper($_POST['tipo'] ?? 'DIURNA');
    $valor = round($valorHora * $horas * ($factores[$tipo] ?? 0), 2);
    responder(false, 'Cálculo de horas extras', array('valor' => $valor, 'tipo' => $tipo));
    break;

  case 'calcular_liquidacion':
    // Vista previa de la liquidación de un contrato (no guarda)
    $idContrato = intval($_POST['id_contrato'] ?? 0);
    $periodo = nomina_resolver_periodo($_POST['periodo'] ?? '');
    if ($idContrato <= 0 || !$periodo) {
      responder(true, 'Debe indicar un contrato y un período válidos');
    }
    $faltantes = CalculosNomina::parametrosFaltantes($periodo['anio']);
    if ($faltantes) {
      responder(true, 'Faltan parámetros para ' . $periodo['anio'] . ': ' . implode(', ', $faltantes));
    }
    $contratos = nomina_select("SELECT * FROM t_nomina_contratos WHERE id = $idContrato LIMIT 1");
    if (!$contratos) {
      responder(true, 'No se encontró el contrato');
    }
    $nov = nomina_select("SELECT n.*, n.valor_final AS total, co.subtipo
                            FROM t_nomina_novedades n
                            LEFT JOIN t_nomina_conceptos co ON co.id = n.id_concepto
                           WHERE n.id_contrato = $idContrato AND n.periodo = '{$periodo['periodo']}'
                             AND IFNULL(n.estado,'ACTIVO') = 'ACTIVO'");
    $liq = CalculosNomina::calcularLiquidacion($contratos[0], $nov, $periodo);
    responder(false, $liq ? 'Liquidación calculada' : 'El contrato no estuvo vigente en el período', $liq);
    break;

  case 'listar_parametros':
    $anio = intval($_POST['anio'] ?? date('Y'));
    $rows = nomina_select("SELECT id, anio, clave, nombre, valor, tipo, descripcion, estado
                             FROM t_nomina_parametros WHERE anio = $anio ORDER BY tipo, nombre");
    $anios = nomina_select("SELECT DISTINCT anio FROM t_nomina_parametros ORDER BY anio DESC");
    responder(false, 'Parámetros cargados', array(
      'parametros' => $rows,
      'anios' => $anios,
      'faltantes' => CalculosNomina::parametrosFaltantes($anio)
    ));
    break;

  case 'guardar_parametro':
    $id = intval($_POST['id'] ?? 0);
    $anio = intval($_POST['anio'] ?? date('Y'));
    $clave = trim($_POST['clave'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $valor = floatval(str_replace(',', '.', $_POST['valor'] ?? 0));
    $tipo = trim($_POST['tipo'] ?? 'VALOR_PESOS');
    $estado = trim($_POST['estado'] ?? 'ACTIVO');

    if ($clave === '' || $nombre === '') {
      responder(true, 'Clave y nombre son obligatorios');
    }
    if (!preg_match('/^[a-z0-9_]+$/i', $clave)) {
      responder(true, 'La clave solo admite letras, números y guion bajo');
    }
    if (!in_array($tipo, array('PORCENTAJE', 'VALOR_PESOS', 'FACTOR', 'MULTIPLO_SMLV', 'UVT'), true)) {
      responder(true, 'Tipo de parámetro no válido');
    }
    if (!in_array($estado, array('ACTIVO', 'INACTIVO'), true)) {
      responder(true, 'Estado no válido');
    }
    if ($tipo === 'PORCENTAJE' && ($valor < 0 || $valor > 1)) {
      responder(true, 'Los porcentajes se registran como fracción decimal (ej. 0.04 para 4 %)');
    }

    $claveEsc = nomina_esc($clave);
    $nombreEsc = nomina_esc($nombre);
    $descEsc = nomina_esc(trim($_POST['descripcion'] ?? ''));

    if ($id > 0) {
      $ok = Query("UPDATE t_nomina_parametros
                      SET nombre = '$nombreEsc', valor = $valor, tipo = '$tipo',
                          descripcion = '$descEsc', estado = '$estado'
                    WHERE id = $id");
      responder(!$ok, $ok ? 'Parámetro actualizado correctamente' : 'No se pudo actualizar el parámetro');
    }
    $existe = nomina_select("SELECT id FROM t_nomina_parametros WHERE anio = $anio AND clave = '$claveEsc' LIMIT 1");
    if ($existe) {
      responder(true, "Ya existe el parámetro '$clave' para el año $anio");
    }
    $ok = Query("INSERT INTO t_nomina_parametros (anio, clave, nombre, valor, tipo, descripcion, estado)
                 VALUES ($anio, '$claveEsc', '$nombreEsc', $valor, '$tipo', '$descEsc', '$estado')");
    responder(!$ok, $ok ? 'Parámetro creado correctamente' : 'No se pudo crear el parámetro');
    break;

  case 'copiar_parametros_anio':
    $anioOrigen = intval($_POST['anio_origen'] ?? 0);
    $anioDestino = intval($_POST['anio_destino'] ?? 0);
    if ($anioOrigen <= 0 || $anioDestino <= 0 || $anioOrigen === $anioDestino) {
      responder(true, 'Indique años de origen y destino válidos y distintos');
    }
    Query("INSERT INTO t_nomina_parametros (anio, clave, nombre, valor, tipo, descripcion, estado)
           SELECT $anioDestino, o.clave, o.nombre, o.valor, o.tipo, o.descripcion, 'ACTIVO'
             FROM t_nomina_parametros o
            WHERE o.anio = $anioOrigen AND o.estado = 'ACTIVO'
              AND NOT EXISTS (SELECT 1 FROM t_nomina_parametros d WHERE d.anio = $anioDestino AND d.clave = o.clave)");
    responder(false, "Parámetros del año $anioOrigen copiados al año $anioDestino. Recuerde actualizar SMLMV, auxilio de transporte y UVT.");
    break;

  case 'eliminar_parametro':
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
      responder(true, 'ID de parámetro inválido');
    }
    Query("UPDATE t_nomina_parametros SET estado = 'INACTIVO' WHERE id = $id");
    responder(false, 'Parámetro desactivado correctamente');
    break;
}