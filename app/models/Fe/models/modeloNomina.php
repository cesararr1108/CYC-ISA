<?php

/**
 * Clases de modelo para la transmisión de Nómina Electrónica DIAN via API-FE
 * compatibles con la estructura esperada por EnviarDocumentoControlNomina.php.
 */

class NominaElectronicaDoc implements JsonSerializable
{
    public $seguridad;
    public $informacion;
    public $empleador;
    public $trabajador;
    public $pago;
    public $periodo;
    public $devengados;
    public $deducciones;
    public $totales;
    public $predecesor;

    public $CUNE = '';
    public $CUNENov = '';
    public $QRCode = '';
    public $Redondeo = '0.00';

    public $InformacionGeneral;
    public $Periodo;
    public $NumeroSecuenciaXML = [];
    public $LugarGeneracionXML = [];
    public $ProveedorXML = [];
    public $Empleador;
    public $Trabajador;
    public $Pago;
    public $FechasPagos = [];
    public $Devengados;
    public $Deducciones;
    public $Totales;
    public $Predecesor;

    public function __construct()
    {
        $this->seguridad = new SeguridadNomina();
        $this->informacion = new InformacionNomina();
        $this->empleador = new EmpleadorNomina();
        $this->trabajador = new TrabajadorNomina();
        $this->pago = new PagoNomina();
        $this->periodo = new PeriodoNomina();
        $this->devengados = new DevengadosNomina();
        $this->deducciones = new DeduccionesNomina();
        $this->totales = new TotalesNomina();
        $this->predecesor = new PredecesorNomina();

        // Alias para consumo estilo array/clase anterior
        $this->InformacionGeneral = $this->informacion;
        $this->Periodo = $this->periodo;
        $this->Empleador = $this->empleador;
        $this->Trabajador = $this->trabajador;
        $this->Pago = $this->pago;
        $this->Devengados = $this->devengados;
        $this->Deducciones = $this->deducciones;
        $this->Totales = $this->totales;
        $this->Predecesor = $this->predecesor;
    }

    public function agregarItem($fecha)
    {
        if (is_object($fecha) && isset($fecha->Fecha)) {
            $valor = (string)$fecha->Fecha;
        } elseif (is_array($fecha) && isset($fecha['Fecha'])) {
            $valor = (string)$fecha['Fecha'];
        } else {
            $valor = (string)$fecha;
        }

        if ($valor !== '') {
            $this->FechasPagos[] = $valor;
            $this->pago->fechasPagos[] = $valor;
        }
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    public function toArray()
    {
        $codigoTrabajador = $this->pick($this->trabajador, ['codigoTrabajador', 'CodigoTrabajador'], '');
        $fechasPagos = $this->normalizarFechasPago();

        $payload = [
            'InformacionGeneral' => [
                'Prefijo' =>  $this->pick($this->informacion, ['prefijo'], ''),
                'Consecutivo' => $this->pick($this->informacion, ['consecutivo'], ''),
                'PeriodoNomina' => $this->pick($this->informacion, ['periodoNomina'], ''),
                'TipoMoneda' =>     $this->pick($this->informacion, ['tipoMoneda'], 'COP'),
                'Notas' =>     $this->pick($this->informacion, ['notas'], ''),
            ],
            'Periodo' => [
                'FechaIngreso' => $this->pick($this->periodo, ['fechaIngreso', 'FechaIngreso'], ''),
                'FechaLiquidacionInicio' => $this->pick($this->periodo, ['fechaLiquidacionInicio', 'FechaLiquidacionInicio'], ''),
                'FechaLiquidacionFin' => $this->pick($this->periodo, ['fechaLiquidacionFinal', 'FechaLiquidacionFin'], ''),
                'TiempoLaborado' => $this->fmtNumero($this->pick($this->periodo, ['tiempoLaborado', 'TiempoLaborado'], '30')),
                'FechaRetiro' => $this->pick($this->periodo, ['fechaRetiro', 'FechaRetiro'], null),
            ],

            'Trabajador' => [
                'TipoTrabajador' => $this->pick($this->trabajador, ['tipoTrabajador', 'TipoTrabajador'], '01'),
                'SubTipoTrabajador' => $this->pick($this->trabajador, ['subTipoTrabajador', 'SubTipoTrabajador'], '00'),
                'AltoRiesgoPension' => $this->normalizarBooleano($this->pick($this->trabajador, ['altoRiesgoPension', 'AltoRiesgoPension'], 'false')),
                'TipoDocumento' => $this->pick($this->trabajador, ['tipoDocumento', 'TipoDocumento'], '13'),
                'NumeroDocumento' => $this->pick($this->trabajador, ['numeroDocumento', 'NumeroDocumento'], ''),
                'PrimerApellido' => $this->pick($this->trabajador, ['primerApellido', 'PrimerApellido'], ''),
                'SegundoApellido' => $this->pick($this->trabajador, ['segundoApellido', 'SegundoApellido'], ''),
                'PrimerNombre' => $this->pick($this->trabajador, ['primerNombre', 'PrimerNombre'], ''),
                'OtrosNombres' => $this->pick($this->trabajador, ['otrosNombres', 'OtrosNombres'], ''),
                'LugarTrabajoPais' => $this->pick($this->trabajador, ['lugarTrabajoPais', 'LugarTrabajoPais'], 'CO'),
                'LugarTrabajoDepartamentoEstado' => $this->pick($this->trabajador, ['lugarTrabajoDpto', 'LugarTrabajoDepartamentoEstado'], ''),
                'LugarTrabajoMunicipioCiudad' => $this->pick($this->trabajador, ['lugarTrabajoMpio', 'LugarTrabajoMunicipioCiudad'], ''),
                'LugarTrabajoDireccion' => $this->pick($this->trabajador, ['lugarTrabajoDireccion', 'LugarTrabajoDireccion'], ''),
                'SalarioIntegral' => $this->normalizarBooleano($this->pick($this->trabajador, ['salarioIntegral', 'SalarioIntegral'], 'false')),
                'TipoContrato' => $this->pick($this->trabajador, ['tipoContrato', 'TipoContrato'], '1'),
                'Sueldo' => $this->fmtValor($this->pick($this->trabajador, ['sueldo', 'Sueldo'], 0)),
                'CodigoTrabajador' => $codigoTrabajador,
            ],

            'Pago' => [
                'Forma' => $this->pick($this->pago, ['forma', 'Forma'], '1'),
                'Metodo' => $this->pick($this->pago, ['metodo', 'Metodo'], '10'),
                'Banco' => $this->pick($this->pago, ['banco', 'Banco'], ''),
                'TipoCuenta' => $this->pick($this->pago, ['tipoCuenta', 'TipoCuenta'], ''),
                'NumeroCuenta' => $this->pick($this->pago, ['numeroCuenta', 'NumeroCuenta'], ''),
            ],

            'FechasPagos' => $fechasPagos,
            'Devengados' => $this->buildDevengadosArray(),
            'Deducciones' => $this->buildDeduccionesArray(),
            'Redondeo' => $this->fmtValor($this->pick($this, ['Redondeo'], 0)),

            'Totales' => [
                'DevengadosTotal' => $this->fmtValor($this->pick($this->totales, ['devengadosTotal', 'DevengadosTotal'], 0)),
                'DeduccionesTotal' => $this->fmtValor($this->pick($this->totales, ['deduccionesTotal', 'DeduccionesTotal'], 0)),
                'ComprobanteTotal' => $this->fmtValor($this->pick($this->totales, ['comprobanteTotal', 'ComprobanteTotal'], 0)),
            ],
        ];

        if ($this->pick($this->predecesor, ['numero', 'Numero'], '') !== '' || $this->pick($this->predecesor, ['cune', 'CUNE'], '') !== '') {
            $payload['Predecesor'] = [
                'Tipo' => $this->pick($this->predecesor, ['tipo', 'Tipo'], ''),
                'Numero' => $this->pick($this->predecesor, ['numero', 'Numero'], ''),
                'CUNEPred' => $this->pick($this->predecesor, ['cune', 'CUNE'], ''),
                'FechaGen' => $this->pick($this->predecesor, ['fecha', 'Fecha'], null),
            ];
        }

        return $payload;
    }

    private function buildDevengadosArray()
    {
        $data = [
            'Basico' => [
                'DiasTrabajados' => $this->fmtNumero($this->devengados->diasTrabajados),
                'SueldoTrabajado' => $this->fmtValor($this->devengados->sueldoTrabajado),
            ],
        ];

        if ($this->devengados->auxilioTransporte > 0 || $this->devengados->viaticoManuAlojS > 0 || $this->devengados->viaticoManuAlojNS > 0) {
            $transporte = [
                'AuxilioTransporte' => $this->fmtValor($this->devengados->auxilioTransporte),
            ];

            if ($this->devengados->viaticoManuAlojS > 0) {
                $transporte['ViaticoManuAlojS'] = $this->fmtValor($this->devengados->viaticoManuAlojS);
            }

            if ($this->devengados->viaticoManuAlojNS > 0) {
                $transporte['ViaticoManuAlojNS'] = $this->fmtValor($this->devengados->viaticoManuAlojNS);
            }

            $data['Transporte'] = $transporte;
        }

        $heds = [];
        if ($this->devengados->horaExtraDiurnaCantidad > 0 || $this->devengados->horaExtraDiurnaPago > 0) {
            $heds[] = ['Cantidad' => $this->fmtNumero($this->devengados->horaExtraDiurnaCantidad), 'Pago' => $this->fmtValor($this->devengados->horaExtraDiurnaPago)];
        }
        if (!empty($heds)) {
            $data['HEDs'] = $heds;
        }

        if ($this->devengados->cantidadVacacionesComunes > 0 || $this->devengados->pagoVacacionesComunes > 0 || $this->devengados->cantidadVacacionesCompensadas > 0 || $this->devengados->pagoVacacionesCompensadas > 0) {
            $vacaciones = [];
            if ($this->devengados->cantidadVacacionesComunes > 0 || $this->devengados->pagoVacacionesComunes > 0) {
                $vacaciones['VacacionesComunes'] = [
                    'Cantidad' => $this->fmtNumero($this->devengados->cantidadVacacionesComunes),
                    'Pago' => $this->fmtValor($this->devengados->pagoVacacionesComunes),
                ];
            }
            if ($this->devengados->cantidadVacacionesCompensadas > 0 || $this->devengados->pagoVacacionesCompensadas > 0) {
                $vacaciones['VacacionesCompensadas'] = [
                    'Cantidad' => $this->fmtNumero($this->devengados->cantidadVacacionesCompensadas),
                    'Pago' => $this->fmtValor($this->devengados->pagoVacacionesCompensadas),
                ];
            }
            $data['Vacaciones'] = $vacaciones;
        }

        if ($this->devengados->cantidadPrimasDias > 0 || $this->devengados->pagoPrimas > 0 || $this->devengados->pagoPrimasNoSalarial > 0) {
            $data['Primas'] = [
                'Cantidad' => $this->fmtNumero($this->devengados->cantidadPrimasDias),
                'Pago' => $this->fmtValor($this->devengados->pagoPrimas),
                'PagoNS' => $this->fmtValor($this->devengados->pagoPrimasNoSalarial),
            ];
        }

        if ($this->devengados->pagoCesantias > 0 || $this->devengados->pagoInteresesCesantias > 0 || $this->devengados->porcentajeCesantias > 0) {
            $data['Cesantias'] = [
                'Pago' => $this->fmtValor($this->devengados->pagoCesantias),
                'PagoIntereses' => $this->fmtValor($this->devengados->pagoInteresesCesantias),
                'Porcentaje' => $this->fmtNumero($this->devengados->porcentajeCesantias),
            ];
        }

        if ($this->devengados->bonificacionSalarial > 0 || $this->devengados->bonificacionNoSalarial > 0) {
            $bonificacion = [];

            if ($this->devengados->bonificacionSalarial > 0) {
                $bonificacion['BonificacionS'] = $this->fmtValor($this->devengados->bonificacionSalarial);
            }

            if ($this->devengados->bonificacionNoSalarial > 0) {
                $bonificacion['BonificacionNS'] = $this->fmtValor($this->devengados->bonificacionNoSalarial);
            }

            $data['Bonificaciones'] = [
                'Bonificacion' => $bonificacion
            ];
        }

        return $data;
    }

    private function buildDeduccionesArray()
    {
        $data = [];

        // Salud obligatoria
        $data['Salud'] = [
            'Porcentaje' => $this->fmtNumero($this->deducciones->saludPorcentaje),
            'Deduccion'  => $this->fmtValor($this->deducciones->saludValor),
        ];
        // Pensión obligatoria
        $data['FondoPension'] = [
            'Porcentaje' => $this->fmtNumero($this->deducciones->pensionPorcentaje),
            'Deduccion'  => $this->fmtValor($this->deducciones->pensionValor),
        ];

        // Fondo de seguridad pensional (Solidaridad Pensional y de Subsistencia)
        if ($this->deducciones->fspValor > 0 || $this->deducciones->fspSubsistenciaValor > 0) {
            $data['FondoSP'] = [
                'Porcentaje'     => $this->fmtNumero($this->deducciones->fspPorcentaje),
                'DeduccionSP'    => $this->fmtValor($this->deducciones->fspValor),
                'PorcentajeSub'   => $this->fmtNumero($this->deducciones->fspSubsistenciaPorcentaje),
                'DeduccionSub'    => $this->fmtValor($this->deducciones->fspSubsistenciaValor),
            ];
        }


        // Sindicato
        if ($this->deducciones->sindicatosValor > 0) {
            $data['Sindicatos'] = [
                'Porcentaje' => $this->fmtNumero($this->deducciones->sindicatosPorcentaje),
                'Deduccion'  => $this->fmtValor($this->deducciones->sindicatosValor),
            ];
        }

        // Sanciones
        if (
            $this->deducciones->sancionesValorPub > 0 ||
            $this->deducciones->sancionesValorPriv > 0
        ) {
            $data['Sanciones'] = [
                'Porcentaje' => $this->fmtNumero($this->deducciones->sancionesPorcentaje),
            ];

            if ($this->deducciones->sancionesValorPub > 0) {
                $data['Sanciones']['SancionPublic'] =
                    $this->fmtValor($this->deducciones->sancionesValorPub);
            }

            if ($this->deducciones->sancionesValorPriv > 0) {
                $data['Sanciones']['SancionPriv'] =
                    $this->fmtValor($this->deducciones->sancionesValorPriv);
            }
        }

        // Libranzas
        if ($this->deducciones->libranzas > 0) {
            $data['Libranzas'] = [
                'Libranza' => $this->fmtValor($this->deducciones->libranzas),
            ];
        }

        // Pagos a terceros
        if ($this->deducciones->pagosTerceros > 0) {
            $data['PagosTerceros'] = [
                'PagoTercero' => $this->fmtValor($this->deducciones->pagosTerceros),
            ];
        }

        // Anticipos
        if ($this->deducciones->anticiposValor > 0) {
            $data['Anticipos'] = [
                'Anticipo' => $this->fmtValor($this->deducciones->anticiposValor),
            ];
        }

        // Otra nómina (quincena)
        if ($this->deducciones->nominaQuincena > 0) {
            $data['OtrasDeducciones'] = [
                [
                    'Descripcion' => 'Nomina quincena',
                    'Deduccion'   => $this->fmtValor($this->deducciones->nominaQuincena),
                ]
            ];
        }

        // Retención en la fuente
        if ($this->deducciones->retencionFuente > 0) {
            $data['RetencionFuente'] = $this->fmtValor($this->deducciones->retencionFuente);
        }

        // Deudas
        if ($this->deducciones->deuda > 0) {
            $data['Deudas'] = [
                'Deuda' => $this->fmtValor($this->deducciones->deuda),
            ];
        }

        // Embargos
        if ($this->deducciones->embargo > 0) {
            $data['Embargos'] = [
                'Embargo' => $this->fmtValor($this->deducciones->embargo),
            ];
        }

        // Planes complementarios
        if ($this->deducciones->planComplementarios > 0) {
            $data['PlanComplementarios'] = [
                'PlanComplementario' => $this->fmtValor($this->deducciones->planComplementarios),
            ];
        }

        // Educación
        if ($this->deducciones->educacion > 0) {
            $data['Educacion'] = [
                'Deduccion' => $this->fmtValor($this->deducciones->educacion),
            ];
        }

        // Reintegros
        if ($this->deducciones->reintegro > 0) {
            $data['Reintegros'] = [
                'Reintegro' => $this->fmtValor($this->deducciones->reintegro),
            ];
        }

        // Otras deducciones
        if ($this->deducciones->otras > 0) {
            $data['OtrasDeducciones'][] = [
                'Descripcion' => 'Otras deducciones',
                'Deduccion'   => $this->fmtValor($this->deducciones->otras),
            ];
        }

        return $data;
    }

    private function normalizarFechasPago()
    {
        $fechas = [];
        $origen = !empty($this->FechasPagos) ? $this->FechasPagos : $this->pago->fechasPagos;

        foreach ((array)$origen as $item) {
            if (is_object($item) && isset($item->Fecha) && $item->Fecha !== '') {
                $fechas[] = $item->Fecha;
            } elseif (is_array($item) && isset($item['Fecha']) && $item['Fecha'] !== '') {
                $fechas[] = $item['Fecha'];
            } elseif ($item !== null && $item !== '') {
                $fechas[] = (string)$item;
            }
        }

        $fechaPago = $this->pick($this->pago, ['fechaPago', 'FechaPago'], '');
        if (empty($fechas) && $fechaPago !== '') {
            $fechas[] = $fechaPago;
        }

        return $fechas;
    }

    private function pick($source, $keys, $default = '')
    {
        foreach ((array)$keys as $key) {
            if (is_array($source) && array_key_exists($key, $source) && $source[$key] !== null && $source[$key] !== '') {
                return $source[$key];
            }
            if (is_object($source) && isset($source->$key) && $source->$key !== null && $source->$key !== '') {
                return $source->$key;
            }
        }
        return $default;
    }

    private function fmtValor($valor)
    {
        return number_format((float)$valor, 2, '.', '');
    }

    private function fmtNumero($valor)
    {
        $numero = (float)$valor;
        if (abs($numero - round($numero)) < 0.00001) {
            return (string)((int)round($numero));
        }
        return number_format($numero, 2, '.', '');
    }

    private function normalizarBooleano($valor)
    {
        return in_array(strtolower(trim((string)$valor)), ['1', 'true', 's', 'si', 'yes'], true) ? 'true' : 'false';
    }
}

class SeguridadNomina
{
    public $tokenUser = '';
    public $tokenPassword = '';
}

class InformacionNomina
{
    public $ambiente = '2';
    public $prefijo = '';
    public $consecutivo = '';
    public $periodoNomina = '';
    public $tipoMoneda = 'COP';
    public $cune = '';
    public $cuneNov = '';
    public $notas = '';
}

class EmpleadorNomina
{
    public $nit = '';
    public $dv = '';
    public $razonSocial = '';
    public $otrosNombres = '';
    public $codigoDpto = '';
    public $codigoMpio = '';
    public $pais = 'CO';
    public $idioma = 'es';
    public $direccion = '';
    public $softwareId = '';
    public $softwarePin = '';
    public $softwareSC = '';
    public $qrCode = '';
}

class TrabajadorNomina
{
    public $tipoTrabajador = '01';
    public $subTipoTrabajador = '00';
    public $altoRiesgoPension = 'false';
    public $tipoDocumento = '13';
    public $numeroDocumento = '';
    public $primerApellido = '';
    public $segundoApellido = '';
    public $primerNombre = '';
    public $otrosNombres = '';
    public $lugarTrabajoPais = 'CO';
    public $lugarTrabajoDpto = '';
    public $lugarTrabajoMpio = '';
    public $lugarTrabajoDireccion = '';
    public $salarioIntegral = 'false';
    public $tipoContrato = '1';
    public $sueldo = 0.0;
    public $codigoTrabajador = '';
}

class PagoNomina
{
    public $forma = '1';
    public $metodo = '10';
    public $banco = '';
    public $tipoCuenta = '';
    public $numeroCuenta = '';
    public $fechaPago = '';
    public $fechasPagos = [];
}

class PeriodoNomina
{
    public $fechaIngreso = '';
    public $fechaRetiro = '';
    public $retirado = 'N';
    public $fechaLiquidacionInicio = '';
    public $fechaLiquidacionFinal = '';
    public $tiempoLaborado = '';
}

class DevengadosNomina
{
    public $diasTrabajados = 0;
    public $sueldoTrabajado = 0.0;

    public $auxilioTransporte = 0.0;
    public $viaticoManuAlojS = 0.0;
    public $viaticoManuAlojNS = 0.0;

    public $horaExtraDiurnaCantidad = 0;
    public $horaExtraDiurnaPago = 0.0;
    public $horaExtraNocturnaCantidad = 0;
    public $horaExtraNocturnaPago = 0.0;
    public $horaRecargoNocturnaCantidad = 0;
    public $horaRecargoNocturnaPago = 0.0;
    public $horaExtraDiurnaFestivoCantidad = 0;
    public $horaExtraDiurnaFestivoPago = 0.0;
    public $horaRecargoDiurnaFestivoCantidad = 0;
    public $horaRecargoDiurnaFestivoPago = 0.0;
    public $horaExtraNocturnaFestivoCantidad = 0;
    public $horaExtraNocturnaFestivoPago = 0.0;
    public $horaRecargoNocturnaFestivoCantidad = 0;
    public $horaRecargoNocturnaFestivoPago = 0.0;

    public $cantidadVacacionesComunes = 0;
    public $pagoVacacionesComunes = 0.0;
    public $cantidadVacacionesCompensadas = 0;
    public $pagoVacacionesCompensadas = 0.0;

    public $cantidadPrimasDias = 0;
    public $pagoPrimas = 0.0;
    public $pagoPrimasNoSalarial = 0.0;

    public $pagoCesantias = 0.0;
    public $pagoInteresesCesantias = 0.0;
    public $porcentajeCesantias = 0.0;

    public $cantidadDiasIncapacidad = 0;
    public $pagoIncapacidad = 0.0;
    public $tipoIncapacidad = '1';

    public $cantidadDiasLicenciaRemunerada = 0;
    public $pagoLicenciaRemunerada = 0.0;
    public $cantidadDiasLicenciaMaternidad = 0;
    public $pagoLicenciaMaternidad = 0.0;
    public $cantidadDiasNoRemunerada = 0;

    public $bonificacionSalarial = 0.0;
    public $bonificacionNoSalarial = 0.0;
    public $auxiliosSalarial = 0.0;
    public $auxiliosNoSalarial = 0.0;
    public $cantidadDiasHuelgaLegal = 0;
    public $otrosConceptosSalarial = 0.0;
    public $otrosConceptosNoSalarial = 0.0;
    public $otrosConceptosDescripcion = '';
    public $compensacionOrdinaria = 0.0;
    public $compensacionExtraordinaria = 0.0;
    public $bonoEpcsv = 0.0;
    public $bonoEpcns = 0.0;
    public $comisionS = 0.0;
    public $comisionNS = 0.0;
    public $pagoTercero = 0.0;
    public $anticipos = 0.0;
    public $dotacion = 0.0;
    public $apoyo_sostenimiento = 0.0;
    public $teletrabajo = 0.0;
    public $bonificacionRetiro = 0.0;
    public $indemnizacion = 0.0;
    public $reintegro = 0.0;
}

class DeduccionesNomina
{
    public $saludPorcentaje = 4.0;
    public $saludValor = 0.0;
    public $pensionPorcentaje = 4.0;
    public $pensionValor = 0.0;
    public $fspPorcentaje = 0.0;
    public $fspValor = 0.0;
    public $fspSubsistenciaPorcentaje = 0.0;
    public $fspSubsistenciaValor = 0.0;
    public $sindicatosPorcentaje = 0.0;
    public $sindicatosValor = 0.0;
    public $sancionesPorcentaje = 0.0;
    public $sancionesValorPub = 0.0;
    public $sancionesValorPriv = 0.0;
    public $libranzas = 0.0;
    public $pagosTerceros = 0.0;
    public $anticiposValor = 0.0;
    public $nominaQuincena = 0.0;
    public $retencionFuente = 0.0;
    public $deuda = 0.0;
    public $embargo = 0.0;
    public $planComplementarios = 0.0;
    public $educacion = 0.0;
    public $reintegro = 0.0;
    public $otras = 0.0;
}

class TotalesNomina
{
    public $devengadosTotal = 0.0;
    public $deduccionesTotal = 0.0;
    public $comprobanteTotal = 0.0;
}

class PredecesorNomina
{
    public $tipo = '';
    public $numero = '';
    public $cune = '';
    public $fecha = null;
}

// Alias de compatibilidad con nombres simples previos
class Nomina extends NominaElectronicaDoc {}
class Trabajador extends TrabajadorNomina {}
class Pago extends PagoNomina {}
class FechasPagos
{
    public $Fecha = '';
}
