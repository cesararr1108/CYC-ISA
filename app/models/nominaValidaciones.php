<?php
/**
 * Modelo de Validaciones para Nómina Electrónica
 * Basado en especificaciones técnicas de la DIAN
 * Validaciones según: Anexo Tecnico Documento Soporte de Pago de Nómina Electrónica V1.0
 */

require_once(__DIR__ . '/nominaHelpers.php');

class ValidacionesNomina {
    
    // Constantes DIAN
    const TIPOS_IDENTIFICACION = array('CC', 'CE', 'PA', 'RC', 'DE', 'TI', 'PE', 'PPT', 'NIT');
    const SUBTIPOS_TRABAJADOR = array('00', '01'); // DIAN: 00 No aplica, 01 Dependiente pensionado por vejez activo
    const ESTADOS_VALIDOS = array('ACTIVO', 'INACTIVO');
    const JORNADAS_VALIDAS = array('COMPLETA', 'PARCIAL');
    const TIPOS_CONCEPTOS = array('DEVENGADO', 'DEDUCCION');
    
    // Valores mínimos y máximos
    const VALOR_MAXIMO = 999999999.99;
    const LONGITUD_DOCUMENTO_MINIMA = 5;
    const LONGITUD_DOCUMENTO_MAXIMA = 20;
    
    /**
     * Validar tipo de identificación
     * @param string $tipoDoc
     * @return array|bool
     */
    public static function validarTipoIdentificacion($tipoDoc) {
        $tipoDoc = strtoupper(trim($tipoDoc));
        
        if (empty($tipoDoc)) {
            return array('error' => true, 'mensaje' => 'Tipo de identificación requerido');
        }
        
        if (strlen($tipoDoc) > 5) {
            return array('error' => true, 'mensaje' => 'Tipo de identificación máximo 5 caracteres');
        }
        
        if (!in_array($tipoDoc, self::TIPOS_IDENTIFICACION)) {
            return array('error' => true, 'mensaje' => 'Tipo de identificación no válido. Permitidos: ' . implode(', ', self::TIPOS_IDENTIFICACION));
        }
        
        return array('error' => false, 'valor' => $tipoDoc);
    }
    
    /**
     * Validar número de documento
     * @param string $documento
     * @param string $tipoDoc
     * @return array
     */
    public static function validarDocumento($documento, $tipoDoc = 'CC') {
        $documento = trim($documento);
        
        if (empty($documento)) {
            return array('error' => true, 'mensaje' => 'Documento requerido');
        }
        
        if (strlen($documento) < self::LONGITUD_DOCUMENTO_MINIMA || strlen($documento) > self::LONGITUD_DOCUMENTO_MAXIMA) {
            return array('error' => true, 'mensaje' => "Documento debe tener entre " . self::LONGITUD_DOCUMENTO_MINIMA . " y " . self::LONGITUD_DOCUMENTO_MAXIMA . " caracteres");
        }
        
        // Validar formato según tipo
        $tipoDoc = strtoupper($tipoDoc);
        if ($tipoDoc === 'CC') {
            // Cédula: debe ser principalmente numérica
            if (!preg_match('/^\d{6,10}$/', $documento)) {
                return array('error' => true, 'mensaje' => 'CC debe contener solo dígitos (6-10 caracteres)');
            }
        }
        
        return array('error' => false, 'valor' => $documento);
    }
    
    /**
     * Validar nombres y apellidos
     * @param string $nombre
     * @param string $campo
     * @return array
     */
    public static function validarNombre($nombre, $campo = 'nombre') {
        $nombre = trim($nombre);
        
        if (empty($nombre)) {
            return array('error' => true, 'mensaje' => ucfirst($campo) . " requerido");
        }
        
        if (strlen($nombre) > 60) {
            return array('error' => true, 'mensaje' => ucfirst($campo) . " máximo 60 caracteres");
        }
        
        if (!preg_match('/^[a-záéíóúñüA-ZÁÉÍÓÚÑÜ\s\'-]+$/', $nombre)) {
            return array('error' => true, 'mensaje' => ucfirst($campo) . " contiene caracteres no válidos");
        }
        
        return array('error' => false, 'valor' => $nombre);
    }
    
    /**
     * Validar cargo
     * @param string $cargo
     * @return array
     */
    public static function validarCargo($cargo) {
        $cargo = trim($cargo);
        
        if (empty($cargo)) {
            return array('error' => true, 'mensaje' => 'Cargo requerido');
        }
        
        if (strlen($cargo) > 100) {
            return array('error' => true, 'mensaje' => 'Cargo máximo 100 caracteres');
        }
        
        return array('error' => false, 'valor' => $cargo);
    }
    
    /**
     * Validar salario
     * @param float $salario
     * @param float $smlv (Salario Mínimo Legal Vigente)
     * @return array
     */
    public static function validarSalario($salario, $smlv = 0) {
        $salario = floatval($salario);
        
        if ($salario <= 0) {
            return array('error' => true, 'mensaje' => 'Salario debe ser mayor a cero');
        }
        
        if ($smlv > 0 && $salario < $smlv) {
            return array('error' => true, 'mensaje' => 'Salario debe ser mayor o igual al SMLV (' . number_format($smlv, 2, ',', '.') . ')');
        }
        
        if ($salario > self::VALOR_MAXIMO) {
            return array('error' => true, 'mensaje' => 'Salario excede el valor máximo permitido');
        }
        
        return array('error' => false, 'valor' => round($salario, 2));
    }
    
    /**
     * Validar auxilios y beneficios
     * @param float $valor
     * @param string $concepto
     * @return array
     */
    public static function validarAuxilios($valor, $concepto = 'Auxilio') {
        $valor = floatval($valor);
        
        if ($valor < 0) {
            return array('error' => true, 'mensaje' => $concepto . ' no puede ser negativo');
        }
        
        if ($valor > self::VALOR_MAXIMO) {
            return array('error' => true, 'mensaje' => $concepto . ' excede el valor máximo permitido');
        }
        
        return array('error' => false, 'valor' => round($valor, 2));
    }
    
    /**
     * Validar subtipo de trabajador
     * @param string $subtipo
     * @return array
     */
    public static function validarSubtipoTrabajador($subtipo) {
        $subtipo = trim($subtipo);
        
        if (!in_array($subtipo, self::SUBTIPOS_TRABAJADOR)) {
            return array('error' => true, 'mensaje' => 'Subtipo de trabajador no válido. Permitidos: ' . implode(', ', self::SUBTIPOS_TRABAJADOR));
        }
        
        return array('error' => false, 'valor' => $subtipo);
    }
    
    /**
     * Validar jornada laboral
     * @param string $jornada
     * @return array
     */
    public static function validarJornada($jornada) {
        $jornada = strtoupper(trim($jornada));
        
        if (!in_array($jornada, self::JORNADAS_VALIDAS)) {
            return array('error' => true, 'mensaje' => 'Jornada no válida. Permitidas: ' . implode(', ', self::JORNADAS_VALIDAS));
        }
        
        return array('error' => false, 'valor' => $jornada);
    }
    
    /**
     * Validar alto riesgo
     * @param int $altoRiesgo
     * @return array
     */
    public static function validarAltoRiesgo($altoRiesgo) {
        $altoRiesgo = intval($altoRiesgo);
        
        if ($altoRiesgo !== 0 && $altoRiesgo !== 1) {
            return array('error' => true, 'mensaje' => 'Alto riesgo debe ser 0 (No) o 1 (Sí)');
        }
        
        return array('error' => false, 'valor' => $altoRiesgo);
    }
    
    /**
     * Validar fecha
     * @param string $fecha (formato YYYY-MM-DD)
     * @param string $campo
     * @return array
     */
    public static function validarFecha($fecha, $campo = 'fecha') {
        $fecha = trim($fecha);
        
        if (empty($fecha)) {
            return array('error' => true, 'mensaje' => ucfirst($campo) . ' requerida');
        }
        
        $partes = explode('-', $fecha);
        if (count($partes) !== 3 || !checkdate($partes[1], $partes[2], $partes[0])) {
            return array('error' => true, 'mensaje' => ucfirst($campo) . ' con formato inválido (YYYY-MM-DD)');
        }
        
        return array('error' => false, 'valor' => $fecha);
    }
    
    /**
     * Validar periodo (YYYYMM)
     * @param string $periodo
     * @return array
     */
    public static function validarPeriodo($periodo) {
        $periodo = trim($periodo);
        
        if (empty($periodo)) {
            return array('error' => true, 'mensaje' => 'Período requerido');
        }
        
        if (!preg_match('/^\d{4}(0[1-9]|1[0-2])$/', $periodo)) {
            return array('error' => true, 'mensaje' => 'Período con formato inválido (YYYYMM)');
        }
        
        return array('error' => false, 'valor' => $periodo);
    }
    
    /**
     * Validar horas o cantidad
     * @param float $cantidad
     * @param string $campo
     * @return array
     */
    public static function validarCantidad($cantidad, $campo = 'Cantidad') {
        $cantidad = floatval($cantidad);
        
        if ($cantidad < 0) {
            return array('error' => true, 'mensaje' => $campo . ' no puede ser negativa');
        }
        
        if ($cantidad > 999.99) {
            return array('error' => true, 'mensaje' => $campo . ' excede el máximo permitido');
        }
        
        return array('error' => false, 'valor' => round($cantidad, 2));
    }
}

// API Endpoints (solo cuando se invoca directamente)
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') !== realpath(__FILE__)) {
    return;
}
$op = $_POST['op'] ?? '';

switch ($op) {
    case 'validar_documento':
        $documento = $_POST['documento'] ?? '';
        $tipoDoc = $_POST['tipo_documento'] ?? 'CC';
        
        $valTipo = ValidacionesNomina::validarTipoIdentificacion($tipoDoc);
        if ($valTipo['error']) {
            responder(true, $valTipo['mensaje']);
            exit;
        }
        
        $valDoc = ValidacionesNomina::validarDocumento($documento, $tipoDoc);
        responder($valDoc['error'], $valDoc['error'] ? $valDoc['mensaje'] : 'Documento válido', $valDoc);
        break;
    
    case 'validar_salario':
        $salario = $_POST['salario'] ?? 0;
        $smlv = $_POST['smlv'] ?? 0;
        
        $valSalario = ValidacionesNomina::validarSalario($salario, floatval($smlv));
        responder($valSalario['error'], $valSalario['error'] ? $valSalario['mensaje'] : 'Salario válido', $valSalario);
        break;
    
    case 'validar_contrato':
        // Validar todos los campos del contrato
        $campos = $_POST;
        $errores = array();
        
        // Validaciones requeridas
        $valTipo = ValidacionesNomina::validarTipoIdentificacion($campos['tipo_documento'] ?? '');
        if ($valTipo['error']) $errores[] = $valTipo['mensaje'];
        
        $valDoc = ValidacionesNomina::validarDocumento($campos['empleado_documento'] ?? '', $campos['tipo_documento'] ?? 'CC');
        if ($valDoc['error']) $errores[] = $valDoc['mensaje'];
        
        $valNombre = ValidacionesNomina::validarNombre($campos['primer_nombre'] ?? '', 'primer nombre');
        if ($valNombre['error']) $errores[] = $valNombre['mensaje'];
        
        $valApellido = ValidacionesNomina::validarNombre($campos['primer_apellido'] ?? '', 'primer apellido');
        if ($valApellido['error']) $errores[] = $valApellido['mensaje'];
        
        $valCargo = ValidacionesNomina::validarCargo($campos['cargo'] ?? '');
        if ($valCargo['error']) $errores[] = $valCargo['mensaje'];
        
        $valSalario = ValidacionesNomina::validarSalario($campos['salario_basico'] ?? 0);
        if ($valSalario['error']) $errores[] = $valSalario['mensaje'];
        
        $valJornada = ValidacionesNomina::validarJornada($campos['jornada'] ?? 'COMPLETA');
        if ($valJornada['error']) $errores[] = $valJornada['mensaje'];
        
        $valSubtipo = ValidacionesNomina::validarSubtipoTrabajador($campos['subtipo_trabajador'] ?? '00');
        if ($valSubtipo['error']) $errores[] = $valSubtipo['mensaje'];
        
        $valAltoRiesgo = ValidacionesNomina::validarAltoRiesgo($campos['alto_riesgo'] ?? 0);
        if ($valAltoRiesgo['error']) $errores[] = $valAltoRiesgo['mensaje'];
        
        $valInicio = ValidacionesNomina::validarFecha($campos['fecha_inicio'] ?? '', 'fecha de inicio');
        if ($valInicio['error']) $errores[] = $valInicio['mensaje'];
        
        if (count($errores) > 0) {
            responder(true, 'Errores de validación', $errores);
        } else {
            responder(false, 'Contrato válido', null);
        }
        break;
}


