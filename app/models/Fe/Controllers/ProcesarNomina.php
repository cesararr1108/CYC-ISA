<?php

/**
 * ProcesarNomina.php
 * Controller principal para emisión de Nómina Electrónica DIAN via API-FE local.
 */
// Cargar clases necesarias
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once('../../bd.php');
require_once('../../funciones.php');
include_once('../../nominaCalculos.php');
// Cargar clases necesarias
require_once('../models/modeloNomina.php');
require_once('EnviarNomina.php');
require_once('../shared/funciones.php');

function datosFiscales()
{
    $datEmpresa = GenerarArray("select nit from t_empresa");
    $sqlDatosFiscales = "SELECT
                            r.Prefix ,
                            r.StartDate,
                            r.EndDate,
                            d.tokenPassword ,
                            d.tokenUser,
                            upper(d.razonSocial) AS razonSocial  ,
                            d.nit,
                            d.nitDv,
                            d.direccion ,
                            d.codigoCiudad ,
                            d.codigoDpto ,
                            d.codigoPais,
                            CASE WHEN d.ProfileExecutionIDNomina = 2 THEN 'EN PRUEBAS' ELSE 'EN PRODUCCION' END AS ProfileExecutionNomina           
                        FROM t_resoluciones r
                        INNER JOIN t_datos_fiscales d ON r.Id_datos_fiscales = d.id
                        WHERE
                            d.nit = '{$datEmpresa[0]['nit']}'
                            AND r.idTipoDoc = 6";
    $connfel = conectarFel();
    $dataToken = GenerarArray($sqlDatosFiscales, $connfel);

    return $dataToken ? $dataToken[0] : array();
}

$conn = conectar();
$idNomina = intval($_POST['id_nomina'] ?? 0);
if (!$idNomina) {
    responder(true, 'ID de nómina requerido');
}

// Obtener datos de la nómina
$sqlNomina = "SELECT 
                    n.*, 
                    c.id as id_contrato,
                    c.primer_nombre, 
                    c.segundo_nombre,
                    c.primer_apellido, 
                    c.segundo_apellido,
                    c.empleado_documento, 
                    c.tipo_documento, 
                    c.salario_basico, 
                    c.subtipo_trabajador,
                    c.alto_riesgo, 
                    c.id_perfil_contable,
                    -- nuevos campos
                    tc.codigo as tipo_contrato,
                    case when c.salario_integral = 0 then 'false' else 'true' end as salario_integral,
                    c.metodo_pago ,
                    c.fecha_inicio,
                    IFNULL(c.fecha_fin , '') AS fecha_fin,
                    DATEDIFF(IFNULL(c.fecha_fin , NOW()), c.fecha_inicio) AS dias_trabajados,
                    -- nuevos campos 
                    c.metodo_pago,
                    c.banco ,
                    c.tipo_cuenta ,
                    c.numero_cuenta,
                    t.tipo_dian AS tipo_documento,
                    n.observaciones AS notas 
                FROM t_nomina n
                INNER JOIN t_nomina_contratos c ON n.id_contrato = c.id
                INNER JOIN t_tipo_identificaciones t ON t.tipo_identificacion = c.tipo_documento 
                INNER JOIN t_nomina_tipos_contratos tc ON tc.id = c.id_tipo_contrato 
                WHERE 
                    n.id = $idNomina";

$resNomina = $conn->query($sqlNomina);
if (!$resNomina || !($nomina = $resNomina->fetch_assoc())) {
    responder(true, 'Nómina no encontrada');
}

// Obtener detalles de conceptos
$sqlDetalles = " SELECT concepto, tipo, cantidad, valor_unitario, total
                            FROM t_nomina_detalle
                            WHERE id_nomina = $idNomina
                            ORDER BY tipo DESC, concepto ASC";

$resDetalles = $conn->query($sqlDetalles);
$devengos = array();
$deducciones = array();

while ($detalle = $resDetalles->fetch_assoc()) {
    if ($detalle['tipo'] === 'DEVENGADO') {
        $devengos[$detalle['concepto']] = floatval($detalle['total']);
    } else {
        $deducciones[$detalle['concepto']] = floatval($detalle['total']);
    }
}
/*return responder(false, 'Nómina construida correctamente', array(
            'devengos' => $devengos,
            'deducciones' => $deducciones
        ));*/

// Obtener datos de empresa y credenciales DIAN desde datosFiscales()
$datosEmpresa = datosFiscales();
if (!$datosEmpresa || empty($datosEmpresa['nit'])) {
    responder(true, 'No se encontraron datos de empresa o credenciales DIAN');
}

$empresa = array(
    'razon_comercial' => $datosEmpresa['razonSocial'] ?? 'Empresa',
    'nit' => $datosEmpresa['nit'] ?? '',
    'dv' => $datosEmpresa['nitDv'] ?? 0,
    'codigo_dpto' => $datosEmpresa['codigoDpto'] ?? '05',
    'codigo_mpio' => $datosEmpresa['codigoCiudad'] ?? '05001',
    'direccion' => $datosEmpresa['direccion'] ?? 'Calle 1',
);

$tokenUser = $datosEmpresa['tokenUser'] ?? '';
$tokenPassword = $datosEmpresa['tokenPassword'] ?? '';

if (!$tokenUser || !$tokenPassword) {
    responder(true, 'No se encontraron credenciales DIAN configuradas');
}

try {


    // Construir objeto NominaElectronicaDoc
    $doc = new NominaElectronicaDoc();
    $calc = new CalculosNomina();

    // Información general
    $doc->informacion->prefijo = $nomina['prefijo'] ?? 'NE';
    $doc->informacion->consecutivo = $nomina['consecutivo'];
    $doc->informacion->periodoNomina = 5; // Mes 1 Semanal - 2 Decenal - 3 Catorcenal - 4 Quincenal - 5 Mensual - 6 - Otro
    $doc->informacion->tipoMoneda = 'COP';
    $doc->informacion->notas = $nomina['notas'] ?? '';

    // Período
    $doc->periodo->fechaIngreso = $nomina['periodo_fecha_inicio'];
    $doc->periodo->fechaLiquidacionInicio = $nomina['periodo_fecha_inicio'];
    $doc->periodo->fechaLiquidacionFinal = $nomina['periodo_fecha_fin'];
    $doc->periodo->tiempoLaborado = $nomina['dias_trabajados'] ?? 0; // Días trabajados (aproximado)
    $doc->periodo->fechaRetiro = $nomina['fecha_fin'] ?? ''; // Solo si el trabajador se retiró en este período

    // Empleador
    $doc->empleador->razonSocial = $empresa['razon_comercial'] ?? 'Empresa';
    $doc->empleador->nit = $empresa['nit'] ?? '';
    $doc->empleador->dv = $empresa['dv'] ?? 0;

    // Trabajador
    $doc->trabajador->tipoTrabajador = '01'; // Empleado
    $doc->trabajador->subTipoTrabajador = $nomina['subtipo_trabajador'] ?? '00';
    $doc->trabajador->altoRiesgoPension = ($nomina['trabajador_alto_riesgo'] ?? 0) ? 'true' : 'false';
    $doc->trabajador->tipoDocumento = $nomina['tipo_documento'] ?? '13';
    $doc->trabajador->numeroDocumento = $nomina['empleado_documento'];
    $doc->trabajador->primerApellido = $nomina['primer_apellido'] ?? '';
    $doc->trabajador->segundoApellido = $nomina['segundo_apellido'] ?? '';
    $doc->trabajador->primerNombre = $nomina['primer_nombre'] ?? '';
    $doc->trabajador->otrosNombres = $nomina['segundo_nombre'] ?? '';
    $doc->trabajador->lugarTrabajoPais = 'CO';
    $doc->trabajador->lugarTrabajoDpto = $empresa['codigo_dpto'] ?? '05'; // Departamento de la empresa
    $doc->trabajador->lugarTrabajoMpio = ($empresa['codigo_dpto'] ?? '05') . ($empresa['codigo_mpio'] ?? '05001'); // Municipio de la empresa
    $doc->trabajador->lugarTrabajoDireccion = $empresa['direccion'] ?? 'Calle 1'; // Dirección de la empresa
    $doc->trabajador->salarioIntegral = $nomina['salario_integral'] ?? 'false';
    $doc->trabajador->tipoContrato = $nomina['tipo_contrato'] ?? '1'; // Indefinido
    $doc->trabajador->sueldo = floatval($nomina['trabajador_salario_basico'] ?? 0);
    $doc->trabajador->codigoTrabajador = 'EMP' . $nomina['id_contrato'];

    // Pago - usar datos del contrato
    $doc->pago->forma = '1'; // Contado
    $doc->pago->metodo = $nomina['metodo_pago'] ?? '47'; // Transferencia por defecto
    $doc->pago->banco = $nomina['banco'] ?? 'BANCOLOMBIA'; // Banco del contrato
    $doc->pago->tipoCuenta = $nomina['tipo_cuenta'] ?? 'Ahorros'; // Tipo de cuenta
    $doc->pago->numeroCuenta = $nomina['numero_cuenta'] ?? ''; // Número de cuenta
    $doc->pago->fechaPago = $nomina['fecha_pago'] ?? $nomina['periodo_fecha_fin'];
    $doc->agregarItem($nomina['fecha_pago'] ?? $nomina['periodo_fecha_fin']);

    // Devengados - mapear conceptos
    $doc->devengados->diasTrabajados = 30;
    $doc->devengados->sueldoTrabajado = $devengos['salario_basico'] ?? 0;
    $doc->devengados->auxilioTransporte = $devengos['aux_transporte'] ?? 0;
    // Primas
    $primas = $devengos['con_prima_servicios'] ?? 0;
    $doc->devengados->pagoPrimas = $primas;
    $doc->devengados->cantidadPrimasDias = $primas > 0 ? 180 : 0;
    // Cesantias
    $cesantias = $devengos['con_cesantias'] ?? 0;
    $doc->devengados->pagoCesantias = $cesantias;
    $doc->devengados->porcentajeCesantias = $cesantias > 0 ? 12 : 0;

    $doc->devengados->pagoInteresesCesantias = $devengos['con_int_cesantias'] ?? 0;
    $doc->devengados->cantidadVacacionesComunes = 0;
    $doc->devengados->pagoVacacionesComunes = $devengos['con_vacaciones'] ?? 0;
    $doc->devengados->bonificacionNoSalarial = $devengos['con_bonificacion_no_salarial'] ?? 0;
    $doc->devengados->bonificacionSalarial = $devengos['con_bonificacion_salarial'] ?? 0;

    // Deducciones - mapear conceptos a propiedades de DeduccionesNomina

    //Salud
    $doc->deducciones->saludPorcentaje = floatval($deducciones['aporte_salud'] ?? 0) > 0 ? floatval($calc->param('pct_salud_empleado') * 100) : 4.00; // Porcentaje empleado
    $doc->deducciones->saludValor = $deducciones['aporte_salud'] ?? 0;
    //Pension
    $doc->deducciones->pensionPorcentaje = floatval($deducciones['aporte_pension'] ?? 0) > 0  ? floatval($calc->param('pct_pension_empleado') * 100) : 4.00; // Porcentaje empleado
    $doc->deducciones->pensionValor = floatval($deducciones['aporte_pension'] ?? 0);
    // Retención en la fuente
    $doc->deducciones->retencionFuente = floatval($deducciones['retencion_fuente'] ?? 0);
    // Embargos
    $doc->deducciones->embargo = floatval($deducciones['con_embargo'] ?? 0);
    // Sindicato
    $doc->deducciones->sindicatosValor = floatval($deducciones['con_cuota_sindicato'] ?? 0);
    // Sanciones disciplinarias
    $doc->deducciones->sancionesValorPriv = floatval($deducciones['con_desc_sancion'] ?? 0);
    // Préstamos
    $doc->deducciones->deuda = floatval($deducciones['con_desc_prestamo'] ?? 0);
    // Descuento por faltas
    $doc->deducciones->otras += floatval($deducciones['con_desc_falta'] ?? 0);
    // Retención patrimonio
    $doc->deducciones->otras += floatval($deducciones['con_retepatrimonio'] ?? 0);
    // Fondo de Solidaridad Pensional
    $doc->deducciones->fspPorcentaje = floatval($calc->param('pct_fsp_solidaridad') * 100); // o el porcentaje real calculado
    $doc->deducciones->fspValor = floatval($deducciones['aporte_fsp_solidaridad'] ?? 0);
    // Fondo de Subsistencia
    $doc->deducciones->fspSubsistenciaPorcentaje = floatval($calc->param('pct_fsp_subsistencia') * 100); // o el porcentaje real
    $doc->deducciones->fspSubsistenciaValor = floatval($deducciones['aporte_fsp_subsistencia'] ?? 0);


    // Totales
    $doc->Redondeo = 0;
    $doc->totales->devengadosTotal = floatval($nomina['devengados_total']);
    $doc->totales->deduccionesTotal = floatval($nomina['deducciones_total']);
    $doc->totales->comprobanteTotal = floatval($nomina['comprobante_total']);

    // Seguridad - usar credenciales obtenidas de t_datos_fiscales (igual que Procesar.php)
    $doc->seguridad->tokenUser = $tokenUser;
    $doc->seguridad->tokenPassword = $tokenPassword;
    $tipoED = 'nie'; // Nómina Electrónica Indivídual
    /*return responder(false, 'Nómina construida correctamente', array(
        'doc' => $doc,
    ));*/
    // Enviar a DIAN

    $respuesta = enviarNomina(
        $tokenUser,
        $tokenPassword,
        $doc,
        $tipoED
    );
    if (isset($respuesta['data'])) {
        procesarRespuesta($respuesta, $nomina['prefijo'] ?? 'NE', $nomina['consecutivo'] ?? '');
    }
    echo json_encode($respuesta);
} catch (Exception $e) {
    responder(true, 'Error al construir nómina: ' . $e->getMessage());
}
