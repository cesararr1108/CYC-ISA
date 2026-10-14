<?php

/**
 * ProcesarNomina.php
 * Controller principal para emisión de Nómina Electrónica DIAN via API-FE local.
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

try {
    require_once('../../bd.php');
    require_once('../models/modeloNomina.php');
    require_once('EnviarNomina.php');

    /*
        $tokenUser = trim((string)($_POST['tokenUser'] ?? $_SESSION['tokenUser'] ?? ''));
        $tokenPassword = trim((string)($_POST['tokenPassword'] ?? $_SESSION['tokenPassword'] ?? ''));
        $tipoED = strtolower(trim((string)($_POST['tipoED'] ?? 'nie')));
    */

    /*
    -- Datos de prueba CYC
    $tokenUser = 'd1a3ef429be3f5508948692f4a955db5b7.27550973';
    $tokenPassword = 'bdf7894615e4ed118b2a692f4a958c1067.56090658';
    */
    $tokenUser = '3d9267e319a78790c7a969dd009d6b24c6.12837597';
    $tokenPassword = '07020ac2f4a085ff9d5569dd009da25647.66555963';
    $tipoED = 'niar';

    if ($tokenUser === '' || $tokenPassword === '') {
        throw new Exception('tokenUser y tokenPassword son obligatorios para emitir la nómina.');
    }

    $permitidos = ['nie', 'niae', 'niar'];
    if (!in_array($tipoED, $permitidos, true)) {
        throw new Exception('El tipoED es obligatorio y debe ser uno de los valores permitidos: nie, niae o niar.');
    }

    $doc = crearNominaEjemploSegunCaso($tipoED);
    $doc->seguridad->tokenUser = $tokenUser;
    $doc->seguridad->tokenPassword = $tokenPassword;

    $respuesta = enviarNomina($tokenUser, $tokenPassword, $doc, $tipoED);

    echo json_encode([
        'ok' => $respuesta['ok'] ?? false,
        'message' => $respuesta['message'] ?? '',
        'tipoED' => $tipoED,
       // 'payload' => $doc->toArray(),
        'respuestaApi' => $respuesta['data'] ?? null,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'message' => 'Error al procesar la nómina: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function crearNominaEjemploSegunCaso(string $tipoED): NominaElectronicaDoc
{
    $doc = new NominaElectronicaDoc();

    $doc->informacion->prefijo = 'NOMA';
    $doc->informacion->consecutivo = '21';
    $doc->informacion->periodoNomina = '4';

    // Periodo
    $doc->periodo->fechaIngreso = '2025-10-01';
    $doc->periodo->fechaLiquidacionInicio = '2026-03-01';
    $doc->periodo->fechaLiquidacionFinal = '2026-03-30';
    $doc->periodo->tiempoLaborado = '180';
    $doc->periodo->fechaRetiro = '';

    // Trabajador
    $doc->trabajador->tipoTrabajador = '01';
    $doc->trabajador->subTipoTrabajador = '00';
    $doc->trabajador->altoRiesgoPension = 'false';
    $doc->trabajador->tipoDocumento = '13';
    $doc->trabajador->numeroDocumento = '1069477458';
    $doc->trabajador->primerApellido = 'BULA';
    $doc->trabajador->segundoApellido = 'MARTINEZ';
    $doc->trabajador->primerNombre = 'CHRISTIAN';
    $doc->trabajador->otrosNombres = 'DAVID';
    $doc->trabajador->lugarTrabajoPais = 'CO';
    $doc->trabajador->lugarTrabajoDpto = '05';
    $doc->trabajador->lugarTrabajoMpio = '05001';
    $doc->trabajador->lugarTrabajoDireccion = 'Calle 10 # 20-30';
    $doc->trabajador->salarioIntegral = 'false';
    $doc->trabajador->tipoContrato = '1';
    $doc->trabajador->sueldo = 3436908;
    $doc->trabajador->codigoTrabajador = 'EMP001';

    // Pago
    $doc->pago->forma = '1';
    $doc->pago->metodo = '47';
    $doc->pago->banco = 'BANCOLOMBIA';
    $doc->pago->tipoCuenta = 'Ahorros';
    $doc->pago->numeroCuenta = '1234567890';
    $doc->pago->fechaPago = '2026-03-15';
    $doc->agregarItem('2026-03-15');
    $doc->agregarItem('2026-03-30');

    // Devengados
    $doc->devengados->diasTrabajados = 30;
    $doc->devengados->sueldoTrabajado = 3436908;
    $doc->devengados->cantidadPrimasDias = 0;
    $doc->devengados->pagoPrimas = 286294;
    $doc->devengados->pagoPrimasNoSalarial = 0;
    $doc->devengados->pagoCesantias = 286294;
    $doc->devengados->pagoInteresesCesantias = 34355;
    $doc->devengados->porcentajeCesantias = 0;
    $doc->devengados->cantidadVacacionesComunes = 0;
    $doc->devengados->pagoVacacionesComunes = 163597;
    $doc->devengados->bonificacionNoSalarial = 0;//2424735;
    $doc->devengados->bonificacionSalarial = 0;

    // Deducciones
    $doc->deducciones->saludPorcentaje = 4.00;
    $doc->deducciones->saludValor = 137476;
    $doc->deducciones->pensionPorcentaje = 4.00;
    $doc->deducciones->pensionValor = 137476;
    $doc->deducciones->anticiposValor = 472707;

    // Totales
    $doc->Redondeo = 0;
    $doc->totales->devengadosTotal = 4207448;//6632183;
    $doc->totales->deduccionesTotal = 747659;
    $doc->totales->comprobanteTotal = 5884524;

    // Ajustes
    if ($tipoED === 'niae' || $tipoED === 'niar') {
        /*
         * Aqui van los datos del documento predecesor en caso de ser un ajuste (NIAE o NIAR). 
         * Para el ejemplo se usan datos ficticios, pero en un caso real deberían provenir del documento original que se está ajustando.
        */

        $doc->predecesor->tipo = 'NOM';
        $doc->predecesor->numero = '16';
        $doc->predecesor->cune = 'c605ff01d301d369bfd80911f14ba43f0dbcd2f6a2da53de5f11ee4ce8611b5b3061e626b395cac7c9dca5320874469b';
        $doc->predecesor->fecha = '2026-06-19';
    }

    return $doc;
}

