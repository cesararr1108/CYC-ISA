<?php
session_start();
try {
  //code...
  require_once('../../bd.php');
  require_once('../models/modelo.php');
  require_once('Enviar.php');
  require_once('../shared/funciones.php');
  $connfel = conectarFel();

  if (!isset($_POST["id"])) {
    echo json_encode(array(
      'ok' => false,
      'message' => 'El id de documento es necesario'
    ));
    return;
  }

  if (!isset($_POST["sw"])) {
    echo json_encode(array(
      'ok' => false,
      'message' => 'El sw del documento es necesario'
    ));
    return;
  }



  $id_transaccion = $_POST["id"];
  $sw_documento = intval($_POST["sw"]);

  $tipoED = '';
  $tipoE = '';

  switch ($sw_documento) {
    case 5: // Ventas
      $tipoED = 'fv';
      $tipoE = 'fv';
      break;
    case 4: //Devoluciones
      $tipoED = 'nc';
      $tipoE = 'nc';
      break;
    case 11: // NC cliente
      $tipoED = 'nc';
      $tipoE = 'nc';
      break;
    case 12: // ND cliente
      $tipoED = 'nd';
      $tipoE = 'nd';
      break;
    case 14: // ND proveedor / Documento soporte
      // Para SW 14, si el tipo de documento esta marcado como documento soporte se envia como 'ds'
      $sqlSoporte = "SELECT IFNULL(td.documento_soporte,'N') AS documento_soporte
                     FROM t_transacciones t
                     INNER JOIN t_tipos_documentos td ON td.id = t.id_tipo_documento
                     WHERE t.id = $id_transaccion
                     LIMIT 1";
      $rowSoporte = GenerarArray($sqlSoporte, '');
      $esSoporte = isset($rowSoporte[0]["documento_soporte"]) && $rowSoporte[0]["documento_soporte"] === 'S';
      $tipoED = $esSoporte ? 'ds' : 'nd';
      $tipoE = $tipoED;
      break;
    default:
      echo json_encode(array(
        'ok' => false,
        'message' => 'SW no soportado para envio electronico: ' . $sw_documento
      ));
      return;
  }

  $responsabilidades = array();

  $sql = "SELECT 
            p.sw,
            t.nit,
            t.nit_digito as digito,
            t.email_fe,
            case when p.sw = 14 and x.documento_soporte = 'S' then '31' else  i.tipo_dian end as tipo_persona,
            p.tipo,
            p.numero,
            p.notas,
            p.retencion_fuente,
            p.valor_total,
            (p.valor_total - p.iva) as base_imponible_retencion,
            (p.retencion_fuente / (p.valor_total- p.iva)) * 100 as pcj_retencion,
            cast(p.fecha_hora as date) as fecha,
            CONCAT(ifnull(t.primer_nombre,''),' ',ifnull(t.segundo_nombre,''),' ',ifnull(t.primer_apellido,''),' ',ifnull(t.segundo_apellido,'')) as nombres,
            ifnull(t.razon_comercial,'') as razon_comercial,
            td.direccion,
            m.codigo   as codigo_ciudad,
            m.municipio     as nombre_ciudad,
            d.codigo_dpto   as codigo_dpto,
            d.departamento  as nombre_dpto,
            td.telefono,
            case when cp.dias>0 then 2 else 1 end as id_pago,
            case when cp.dias>0 then 10 else 30 end as codigo_pago,
            cast(p.fecha_hora as date) as fecha_pago,
            cp.dias as diasValidez,
            GROUP_CONCAT(
                CASE 
                    WHEN t.autorretenedor = 'S' THEN 'O-15' 
                    WHEN t.gran_contribuyente ='S' THEN 'O-13'
                    WHEN t.agente_retenedor ='S' THEN 'O-23'
                    WHEN r.regimen ='SIMPLIFICADO' THEN 'O-47'
                    WHEN t.nit = 222222222222 THEN 'R-99-PN'
                    ELSE 'R-99-PN' 
                END SEPARATOR '; '
                ) AS responsabilidades
        from t_transacciones p
        inner join t_terceros              t on t.id            = p.id_tercero
        inner join t_regimenes             r on r.id            = t.id_regimen
        inner join t_terceros_direcciones td on td.id           = p.id_tercero_dir
        inner join t_condiciones_pago     cp on cp.id           = p.id_condicion
        inner join t_tipos_documentos      x on x.id            = p.id_tipo_documento
        inner join t_tipo_identificaciones i on i.id            = t.id_tipo_identificacion 
        left  join t_departamentos         d on d.codigo_dpto   = td.codigo_dpto
        left  join t_municipios            m on m.codigo_dpto   = d.codigo_dpto and m.codigo=td.codigo_mpio
        where 
             p.id = $id_transaccion
        group by 
            t.email_fe,
            td.direccion,
            t.nit,
            t.nit_digito,
            p.tipo,
            p.numero,
            p.notas,
            p.valor_total,
            p.fecha_hora,
            t.primer_nombre,
            t.segundo_nombre,
            t.primer_apellido,
            t.segundo_apellido ,
            t.razon_comercial,
            m.codigo   ,
            m.municipio     ,
            d.codigo_dpto   ,
            d.departamento  ,
            td.telefono,
            cp.dias,
            t.id_tipo_identificacion,
            p.sw

    ";
  $datos_encabezado = GenerarArray($sql, '');
  $datEmpresa = GenerarArray("select nit from t_empresa");
  // Crear instancia de Factura
  $Factura = new Factura();
  // Seguridad
  $sqlToken = "select * from t_datos_fiscales where nit =" . $datEmpresa[0]["nit"];
  $tokes = GenerarArray($sqlToken, $connfel);
  $Factura->seguridad->tokenUser = $tokes[0]["tokenUser"];
  $Factura->seguridad->tokenPassword = $tokes[0]["tokenPassword"];

  foreach ($datos_encabezado as $d) {
    // Documento
    $Factura->documento->tipo = $d['tipo'];
    $Factura->documento->numero = $d['numero'];
    $Factura->documento->tipoE = $tipoE;
    $Factura->documento->fecha = $d["fecha"];
    // Dirección
    $Factura->cliente->direccion->codigo_ciudad = $d["codigo_ciudad"];
    $Factura->cliente->direccion->nombre_ciudad = $d["nombre_ciudad"];
    $Factura->cliente->direccion->codigo_dpto = $d["codigo_dpto"];
    $Factura->cliente->direccion->direccion = $d["direccion"];
    $Factura->cliente->direccion->codigo_postal = '02500';
    //codigo_postal
    // País
    $Factura->cliente->direccion->pais->codigo_pais = 169;
    $Factura->cliente->direccion->pais->nombre_pais = 'CO';

    // Cliente
    $Factura->cliente->nombres = $d["nombres"];
    $Factura->cliente->razon = $d["razon_comercial"];
    $Factura->cliente->nit = $d["nit"];
    $Factura->cliente->digito = $d["digito"];
    $Factura->cliente->tipo_persona = $d["tipo_persona"];
    $Factura->cliente->responsabilidades = $d["responsabilidades"];

    // Contacto
    $Factura->cliente->contacto->telefono = $d['telefono']; // Se asigna directamente sobre la instancia ya creada
    $Factura->cliente->contacto->email = $d['email_fe'];

    // Forma de pago
    $Factura->formaPago->id = $d["id_pago"];
    $Factura->formaPago->codigo = $d["codigo_pago"];
    $Factura->formaPago->fecha = $d["fecha_pago"];
    $Factura->formaPago->diasValidez = $d["diasValidez"];
  }


  if ($tipoED === 'ds') {
    $d = isset($datos_encabezado[0]) ? $datos_encabezado[0] : [];

    // Detalle del documento de soporte
    $item = new Item();
    $item->codigo = 'DS01';
    $descripcionNota = (string)($d["notas"] ?? '');
    if ($descripcionNota !== '' && !preg_match('//u', $descripcionNota)) {
      $convertida = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $descripcionNota);
      if ($convertida !== false) {
        $descripcionNota = $convertida;
      }
    }
    $item->descripcion = $descripcionNota;
    $item->muestraGratis = false;
    $item->cantidad = '1.00';
    $item->pcjImpuesto1 = '0.00';
    $item->pcjImpuesto3 = '0.00';
    $item->pcjImpuesto4 = '0.00';
    $item->valorUnitario = $d["valor_total"] ?? 0;
    $item->pcjDescuento = '0.00';
    $item->lote = '';
    $item->vmcto = '';

    $Factura->agregarItem($item);

    $baseImponible = $d["valor_total"] ?? 0;
    $valorImpuesto = '0.00';

    $esquemaImpuesto = new EsquemaImpuesto('01', 'IVA');
    $categoriaImpuesto = new CategoriaImpuesto('0.00', $esquemaImpuesto);
    $subtotalImpuesto = new SubtotalImpuesto(
      $baseImponible,
      $valorImpuesto,
      $categoriaImpuesto
    );

    $item->impuestoTotal = $subtotalImpuesto;

    //Retencion en la fuente
    $valorRetencion = $d["retencion_fuente"] ?? 0;
    $pcjRetencion = $d["pcj_retencion"] ?? 0;

    $esquema = new EsquemaImpuesto('06', 'ReteRenta');
    $categoria = new CategoriaImpuesto($pcjRetencion, $esquema);
    $retencion = new SubtotalImpuesto(
      $baseImponible,
      $valorRetencion,
      $categoria
    );

    $Factura->retencionesTotales[] = $retencion;
  } else {
    $sqlDet = "SELECT  
                  r.codigo,
                  r.descripcion,
                  CASE WHEN t.valor_unitario*t.can_und>0 THEN false ELSE true END AS muestraGratis,
                  t.cantidad/t.can_und AS cantidad,
                  t.pcj_iva AS pcjImpuesto1,
                  0 AS pcjImpuesto3,
                  0 AS pcjImpuesto4,
                  t.valor_unitario*t.can_und  AS valorUnitario,
                  t.pcj_descuento AS pcjDescuento,
                  t.lote ,
                  t.vmcto 
                FROM t_transacciones_detalle t
                INNER JOIN t_transacciones    p ON p.id=t.id_transaccion
                INNER JOIN t_tipos_documentos x ON x.id=p.id_tipo_documento 
                INNER JOIN t_referencias      r ON r.id=t.id_referencia
                WHERE 
                  t.id_transaccion = $id_transaccion 
    ";

    $detalle = GenerarArray($sqlDet, '');

    foreach ($detalle as $dato) {
      $item = new Item();
      $item->codigo = $dato["codigo"];
      $item->descripcion = $dato["descripcion"];
      $item->muestraGratis = $dato["muestraGratis"];
      $item->cantidad = (float)$dato["cantidad"];
      $item->pcjImpuesto1 = (float)$dato["pcjImpuesto1"];
      $item->pcjImpuesto3 = (float)$dato["pcjImpuesto3"];
      $item->pcjImpuesto4 = (float)$dato["pcjImpuesto4"];
      $item->valorUnitario = (float)$dato["valorUnitario"];
      $item->pcjDescuento = (float)$dato["pcjDescuento"];
      $item->lote = $dato["lote"];
      $item->vmcto = $dato["vmcto"];

      $Factura->agregarItem($item);

      // impuestos
      if ($dato["pcjImpuesto1"] > 0) {
        $baseImponible = (float)$dato["cantidad"] * (float)$dato["valorUnitario"];
        $valorImpuesto = ($baseImponible * (float)$dato["pcjImpuesto1"]) / 100;

        $esquemaImpuesto = new EsquemaImpuesto('01', 'IVA');
        $categoriaImpuesto = new CategoriaImpuesto($dato['pcjImpuesto1'], $esquemaImpuesto);
        $subtotalImpuesto = new SubtotalImpuesto(
          $baseImponible,
          $valorImpuesto,
          $categoriaImpuesto
        );

        $item->impuestoTotal = $subtotalImpuesto;
      }
    }


    $de = isset($datos_encabezado[0]) ? $datos_encabezado[0] : [];

    //Retencion en la fuente
    $valorRetencion = $de["retencion_fuente"] ?? 0;
    $pcjRetencion = $de["pcj_retencion"] ?? 0;
    $baseImponibleRet = $de["base_imponible_retencion"] ?? 0;
    $esquema = new EsquemaImpuesto('06', 'ReteRenta');
    $categoria = new CategoriaImpuesto($pcjRetencion, $esquema);
    $retencion = new SubtotalImpuesto(
      $baseImponibleRet,
      $valorRetencion,
      $categoria
    );
    $Factura->retencionesTotales[] = $retencion;
    //Retencion en la fuente
  }
/*
  echo json_encode(array(
    'ok' => true,
    'message' => 'Factura procesada correctamente',
    'data' => $Factura
  ));
  return;*/

  $respuesta = enviar($Factura->seguridad->tokenUser, $Factura->seguridad->tokenPassword, $Factura, $tipoED);

  if (isset($respuesta['data'])) {
    procesarRespuesta($respuesta, $datos_encabezado[0]["tipo"], $datos_encabezado[0]["numero"]);
  }

  echo json_encode($respuesta);

} catch (\Throwable $th) {
  //throw $th;
  echo json_encode(array(
    'ok' => false,
    'message' => print_r($th)
  ));
}