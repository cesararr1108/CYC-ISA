<?php

/**
 * EXTRAER INFORMACIÓN DE FACTURA ELECTRÓNICA DIAN
 *
 * Compatible con:
 * - Invoice
 * - CreditNote
 * - AttachedDocument
 *
 * Puede recibir:
 * - XML como string
 * - Ruta física del archivo XML
 *
 * Retorna:
 *
 * [
 *     'exito' => true,
 *     'tipo_documento' => 'FC',
 *     'nit_emisor' => '890300466',
 *     'numero_factura' => 'FV12345',
 *     'fecha_factura' => '2026-09-05',
 *     'fecha_vencimiento_factura' => '2026-10-05',
 *     'items' => [
 *         [
 *             'codigo' => '0716220',
 *             'codigo_proveedor' => '0716220',
 *             'codigo_interno' => '0716220',
 *             'ean' => '7702057716226',
 *             'descripcion' => 'PRODUCTO',
 *             'cantidad' => 72,
 *             'unidad' => '94',
 *             'descuento_porcentaje' => 0,
 *             'descuento_valor' => 0,
 *             'costo' => 21141.16,
 *             'subtotal' => 1522163.52,
 *             'iva_porcentaje' => 0,
 *             'iva_valor' => 0,
 *             'total' => 1522163.52,
 *             'lote' => null,
 *             'vencimiento' => null,
 *             'registro_sanitario' => null
 *         ]
 *     ]
 * ]
 *
 * @param string $xmlContenido XML o ruta del archivo
 * @return array
 */
function extraerFacturaElectronica($xmlContenido)
{
    /*
     * -------------------------------------------------------------
     * 1. VALIDAR ENTRADA
     * -------------------------------------------------------------
     */

    if (empty($xmlContenido)) {
        return [
            'exito' => false,
            'mensaje' => 'No se recibió contenido XML.',
            'items' => []
        ];
    }

    /*
     * Si lo recibido es una ruta de archivo, leerla.
     */
    if (
        is_string($xmlContenido) &&
        file_exists($xmlContenido) &&
        is_file($xmlContenido)
    ) {
        $contenido = file_get_contents($xmlContenido);
    } else {
        $contenido = $xmlContenido;
    }

    if (empty($contenido)) {
        return [
            'exito' => false,
            'mensaje' => 'El XML está vacío.',
            'items' => []
        ];
    }

    /*
     * -------------------------------------------------------------
     * 2. CARGAR XML
     * -------------------------------------------------------------
     */

    libxml_use_internal_errors(true);

    $xml = simplexml_load_string($contenido);

    /*
     * Si no pudo cargar directamente, intentar limpiar BOM.
     */
    if ($xml === false) {

        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido);

        $xml = simplexml_load_string($contenido);
    }

    if ($xml === false) {

        $errores = libxml_get_errors();

        $mensaje = 'XML inválido.';

        if (!empty($errores)) {
            $mensaje .= ' ' . trim($errores[0]->message);
        }

        libxml_clear_errors();

        return [
            'exito' => false,
            'mensaje' => $mensaje,
            'items' => []
        ];
    }

    libxml_clear_errors();

    /*
     * -------------------------------------------------------------
     * 3. DETECTAR AttachedDocument
     * -------------------------------------------------------------
     *
     * Muchos proveedores envían:
     *
     * AttachedDocument
     *      └── Attachment
     *             └── ExternalReference
     *                    └── Description
     *                           └── CDATA
     *                                  └── Invoice / CreditNote
     */

    $documento = $xml;

    $rootName = $xml->getName();

    if ($rootName === 'AttachedDocument') {

        $documentoInterno = extraerDocumentoInternoAttachedDocument($xml);

        if ($documentoInterno !== false) {

            $documento = $documentoInterno;
        } else {

            /*
             * En algunos XML el AttachedDocument también contiene
             * información suficiente para extraer encabezado.
             * Pero si no encontramos el documento interno,
             * continuamos con el XML original.
             */
            $documento = $xml;
        }
    }

    /*
     * -------------------------------------------------------------
     * 4. DETERMINAR TIPO DE DOCUMENTO
     * -------------------------------------------------------------
     */

    $nombreDocumento = $documento->getName();

    $tipoDocumento = 'OTRO';

    if ($nombreDocumento === 'Invoice') {
        $tipoDocumento = 'FC';
    } elseif ($nombreDocumento === 'CreditNote') {
        $tipoDocumento = 'NC';
    } elseif ($nombreDocumento === 'DebitNote') {
        $tipoDocumento = 'ND';
    }

    /*
     * -------------------------------------------------------------
     * 5. REGISTRAR NAMESPACES
     * -------------------------------------------------------------
     */

    $namespaces = $documento->getDocNamespaces(true);

    /*
     * Registrar namespaces conocidos.
     */
    $cac = 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2';

    $cbc = 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2';

    /*
     * -------------------------------------------------------------
     * 6. FUNCIONES AUXILIARES
     * -------------------------------------------------------------
     */

    /*
     * Obtener valor de un nodo.
     */
    $getValue = function ($node) {

        if ($node === null) {
            return null;
        }

        $valor = trim((string) $node);

        return $valor !== '' ? $valor : null;
    };

    /*
     * Buscar primer nodo XPath.
     */
    $xpathFirst = function ($obj, $paths) use ($getValue) {

        foreach ($paths as $path) {

            $resultado = $obj->xpath($path);

            if ($resultado !== false && !empty($resultado)) {

                $valor = $getValue($resultado[0]);

                if ($valor !== null) {
                    return $valor;
                }
            }
        }

        return null;
    };

    /*
     * Convertir número.
     */
    $toFloat = function ($valor) {

        if ($valor === null || $valor === '') {
            return 0;
        }

        /*
         * El XML DIAN normalmente utiliza punto decimal.
         */
        $valor = trim((string) $valor);

        /*
         * Si viene con separadores de miles:
         */
        if (
            strpos($valor, ',') !== false &&
            strpos($valor, '.') !== false
        ) {

            /*
             * Determinar formato.
             */
            if (strrpos($valor, ',') > strrpos($valor, '.')) {
                $valor = str_replace('.', '', $valor);
                $valor = str_replace(',', '.', $valor);
            } else {
                $valor = str_replace(',', '', $valor);
            }
        } elseif (strpos($valor, ',') !== false) {

            /*
             * Si solamente tiene coma, asumir decimal.
             */
            $valor = str_replace(',', '.', $valor);
        }

        return (float) $valor;
    };

    /*
     * Normalizar fecha.
     */
    $normalizarFecha = function ($fecha) {

        if (empty($fecha)) {
            return null;
        }

        $fecha = trim((string) $fecha);

        /*
         * YYYY-MM-DD
         */
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return $fecha;
        }

        /*
         * YYYY-MM-DDTHH:MM:SS
         */
        if (preg_match('/^(\d{4}-\d{2}-\d{2})T/', $fecha, $m)) {
            return $m[1];
        }

        /*
         * DD/MM/YYYY
         */
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $fecha, $m)) {

            return $m[3] . '-' . $m[2] . '-' . $m[1];
        }

        /*
         * DD-MM-YYYY
         */
        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $fecha, $m)) {

            return $m[3] . '-' . $m[2] . '-' . $m[1];
        }

        return $fecha;
    };

    /*
     * -------------------------------------------------------------
     * 7. EXTRAER ENCABEZADO
     * -------------------------------------------------------------
     */

    /*
     * Número de factura/documento.
     */
    $numeroFactura = $xpathFirst(
        $documento,
        [
            '//cbc:ID',
            '//*[local-name()="ID"]'
        ]
    );

    /*
     * Fecha factura.
     */
    $fechaFactura = $xpathFirst(
        $documento,
        [
            '//cbc:IssueDate',
            '//*[local-name()="IssueDate"]'
        ]
    );

    $fechaFactura = $normalizarFecha($fechaFactura);

    /*
     * -------------------------------------------------------------
     * 8. NIT DEL EMISOR
     * -------------------------------------------------------------
     *
     * En UBL normalmente:
     *
     * AccountingSupplierParty
     *      Party
     *          PartyTaxScheme
     *              CompanyID
     *
     * -------------------------------------------------------------
     */

    $nitEmisor = $xpathFirst(
        $documento,
        [
            '//cac:AccountingSupplierParty//cbc:CompanyID',
            '//*[local-name()="AccountingSupplierParty"]//*[local-name()="CompanyID"]'
        ]
    );

    /*
     * Quitar espacios y caracteres extraños.
     */
    if ($nitEmisor !== null) {

        $nitEmisor = preg_replace('/[^0-9\-]/', '', $nitEmisor);

        /*
         * Si trae dígito de verificación como:
         * 890300466-5
         *
         * conservarlo porque puede ser útil.
         */
    }

    /*
     * -------------------------------------------------------------
     * 9. FECHA DE VENCIMIENTO DE LA FACTURA
     * -------------------------------------------------------------
     */

    $fechaVencimientoFactura = $xpathFirst(
        $documento,
        [
            '//cbc:DueDate',
            '//*[local-name()="DueDate"]'
        ]
    );

    $fechaVencimientoFactura =
        $normalizarFecha($fechaVencimientoFactura);

    /*
     * -------------------------------------------------------------
     * 10. ENCONTRAR LAS LÍNEAS
     * -------------------------------------------------------------
     */

    $lineas = [];

    if ($nombreDocumento === 'Invoice') {

        $lineas = $documento->xpath(
            '//cac:InvoiceLine'
        );
    } elseif ($nombreDocumento === 'CreditNote') {

        $lineas = $documento->xpath(
            '//cac:CreditNoteLine'
        );
    } elseif ($nombreDocumento === 'DebitNote') {

        $lineas = $documento->xpath(
            '//cac:DebitNoteLine'
        );
    }

    /*
     * Si no encontró utilizando namespaces,
     * buscar por local-name().
     */
    if (empty($lineas)) {

        if ($nombreDocumento === 'Invoice') {

            $lineas = $documento->xpath(
                '//*[local-name()="InvoiceLine"]'
            );
        } elseif ($nombreDocumento === 'CreditNote') {

            $lineas = $documento->xpath(
                '//*[local-name()="CreditNoteLine"]'
            );
        } elseif ($nombreDocumento === 'DebitNote') {

            $lineas = $documento->xpath(
                '//*[local-name()="DebitNoteLine"]'
            );
        }
    }

    /*
     * -------------------------------------------------------------
     * 11. PROCESAR PRODUCTOS
     * -------------------------------------------------------------
     */

    $items = [];

    foreach ($lineas as $linea) {

        /*
         * ---------------------------------------------------------
         * CANTIDAD
         * ---------------------------------------------------------
         */

        $cantidad = $xpathFirst(
            $linea,
            [
                './cbc:InvoicedQuantity',
                './cbc:CreditedQuantity',
                './cbc:DebitedQuantity',
                './*[local-name()="InvoicedQuantity"]',
                './*[local-name()="CreditedQuantity"]',
                './*[local-name()="DebitedQuantity"]'
            ]
        );

        $cantidad = $toFloat($cantidad);

        /*
         * Unidad.
         */
        $unidad = null;

        $cantidadNodo = $linea->xpath(
            './cbc:InvoicedQuantity |
             ./cbc:CreditedQuantity |
             ./*[local-name()="InvoicedQuantity"] |
             ./*[local-name()="CreditedQuantity"]'
        );

        if (!empty($cantidadNodo)) {

            $atributos = $cantidadNodo[0]->attributes();

            if (isset($atributos['unitCode'])) {
                $unidad = (string) $atributos['unitCode'];
            }
        }

        /*
         * ---------------------------------------------------------
         * DESCRIPCIÓN
         * ---------------------------------------------------------
         */

        $descripcion = $xpathFirst(
            $linea,
            [
                './cac:Item/cbc:Description',
                './*[local-name()="Item"]/*[local-name()="Description"]'
            ]
        );

        /*
         * ---------------------------------------------------------
         * CÓDIGO COMPRADOR
         * ---------------------------------------------------------
         */

        $codigoComprador = $xpathFirst(
            $linea,
            [
                './cac:Item/cac:BuyersItemIdentification/cbc:ID',
                './*[local-name()="Item"]/*[local-name()="BuyersItemIdentification"]/*[local-name()="ID"]'
            ]
        );

        /*
         * ---------------------------------------------------------
         * CÓDIGO VENDEDOR
         * ---------------------------------------------------------
         */

        $codigoVendedor = $xpathFirst(
            $linea,
            [
                './cac:Item/cac:SellersItemIdentification/cbc:ID',
                './*[local-name()="Item"]/*[local-name()="SellersItemIdentification"]/*[local-name()="ID"]'
            ]
        );

        /*
         * ---------------------------------------------------------
         * EAN / GTIN
         * ---------------------------------------------------------
         */

        $ean = $xpathFirst(
            $linea,
            [
                './cac:Item/cac:StandardItemIdentification/cbc:ID',
                './*[local-name()="Item"]/*[local-name()="StandardItemIdentification"]/*[local-name()="ID"]'
            ]
        );

        /*
         * ---------------------------------------------------------
         * CÓDIGO PRINCIPAL
         * ---------------------------------------------------------
         *
         * Para ISA priorizamos:
         *
         * 1. código comprador
         * 2. código vendedor
         * 3. EAN
         */

        $codigo = null;

        if (!empty($codigoComprador)) {

            $codigo = $codigoComprador;
        } elseif (!empty($codigoVendedor)) {

            $codigo = $codigoVendedor;
        } elseif (!empty($ean)) {

            $codigo = $ean;
        }

        /*
         * ---------------------------------------------------------
         * PRECIO / COSTO
         * ---------------------------------------------------------
         */

        $costo = $xpathFirst(
            $linea,
            [
                './cac:Price/cbc:PriceAmount',
                './*[local-name()="Price"]/*[local-name()="PriceAmount"]'
            ]
        );

        $costo = $toFloat($costo);

        /*
         * ---------------------------------------------------------
         * BASE QUANTITY
         * ---------------------------------------------------------
         *
         * Algunos proveedores indican que el precio corresponde
         * a una cantidad determinada.
         *
         * Ejemplo:
         *
         * PriceAmount = 21141.16
         * BaseQuantity = 72
         */

        $baseQuantity = $xpathFirst(
            $linea,
            [
                './cac:Price/cbc:BaseQuantity',
                './*[local-name()="Price"]/*[local-name()="BaseQuantity"]'
            ]
        );

        $baseQuantity = $toFloat($baseQuantity);

        /*
         * Si el precio está expresado para una base diferente,
         * normalizar el costo unitario.
         */
        /* if ($baseQuantity > 0 && $baseQuantity != 1) {

            $costoUnitario = $costo / $baseQuantity;
        } else {*/

        $costoUnitario = $costo;
        // }

        /*
         * ---------------------------------------------------------
         * VALOR DE LA LÍNEA
         * ---------------------------------------------------------
         */

        $subtotal = $xpathFirst(
            $linea,
            [
                './cbc:LineExtensionAmount',
                './*[local-name()="LineExtensionAmount"]'
            ]
        );

        $subtotal = $toFloat($subtotal);

        /*
         * ---------------------------------------------------------
         * IVA
         * ---------------------------------------------------------
         */

        $ivaValor = 0;
        $ivaPorcentaje = 0;

        $ivaNodo = $linea->xpath(
            './cac:TaxTotal/cac:TaxSubtotal |
             ./*[local-name()="TaxTotal"]/*[local-name()="TaxSubtotal"]'
        );

        if (!empty($ivaNodo)) {

            foreach ($ivaNodo as $tax) {

                $nombreImpuesto = $xpathFirst(
                    $tax,
                    [
                        './cac:TaxCategory/cac:TaxScheme/cbc:Name',
                        './*[local-name()="TaxCategory"]/*[local-name()="TaxScheme"]/*[local-name()="Name"]'
                    ]
                );

                $taxId = $xpathFirst(
                    $tax,
                    [
                        './cac:TaxCategory/cac:TaxScheme/cbc:ID',
                        './*[local-name()="TaxCategory"]/*[local-name()="TaxScheme"]/*[local-name()="ID"]'
                    ]
                );

                /*
                 * IVA = ID 01.
                 */
                if (
                    $taxId === '01' ||
                    strtoupper((string) $nombreImpuesto) === 'IVA'
                ) {

                    $ivaValor = $toFloat(
                        $xpathFirst(
                            $tax,
                            [
                                './cbc:TaxAmount',
                                './*[local-name()="TaxAmount"]'
                            ]
                        )
                    );

                    $ivaPorcentaje = $toFloat(
                        $xpathFirst(
                            $tax,
                            [
                                './cac:TaxCategory/cbc:Percent',
                                './*[local-name()="TaxCategory"]/*[local-name()="Percent"]'
                            ]
                        )
                    );

                    break;
                }
            }
        }

        /*
         * ---------------------------------------------------------
         * DESCUENTO
         * ---------------------------------------------------------
         *
         * UBL:
         *
         * AllowanceCharge
         *
         * ChargeIndicator = false
         *
         * Significa descuento.
         */

        $descuentoValor = 0;
        $descuentoPorcentaje = 0;

        $allowanceCharges = $linea->xpath(
            './cac:AllowanceCharge |
     ./*[local-name()="AllowanceCharge"]'
        );

        if (!empty($allowanceCharges)) {

            foreach ($allowanceCharges as $allowance) {

                $chargeIndicator = $xpathFirst(
                    $allowance,
                    [
                        './cbc:ChargeIndicator',
                        './*[local-name()="ChargeIndicator"]'
                    ]
                );

                /*
         * false = descuento
         */
                if (
                    strtolower(trim((string) $chargeIndicator)) === 'false' ||
                    (string) $chargeIndicator === '0'
                ) {

                    // Valor real del descuento
                    $valor = $xpathFirst(
                        $allowance,
                        [
                            './cbc:Amount',
                            './*[local-name()="Amount"]'
                        ]
                    );

                    $valorDescuento = $toFloat($valor);

                    $descuentoValor += $valorDescuento;

                    /*
             * La base real del descuento está en BaseAmount.
             * No utilizamos MultiplierFactorNumeric porque
             * puede venir con un valor que no corresponde
             * al porcentaje real del descuento.
             */
                    $baseAmount = $xpathFirst(
                        $allowance,
                        [
                            './cbc:BaseAmount',
                            './*[local-name()="BaseAmount"]'
                        ]
                    );

                    $baseDescuento = $toFloat($baseAmount);

                    if ($baseDescuento > 0 && $valorDescuento > 0) {

                        $descuentoPorcentaje +=
                            ($valorDescuento / $baseDescuento) * 100;
                    }
                }
            }
        }

        /*
            * Si no tenemos BaseAmount, pero tenemos descuento y subtotal,
            * intentar calcular el porcentaje usando el subtotal.
            */
        if (
            $descuentoPorcentaje == 0 &&
            $descuentoValor > 0 &&
            $subtotal > 0
        ) {

            $baseDescuento = $subtotal + $descuentoValor;

            if ($baseDescuento > 0) {

                $descuentoPorcentaje =
                    ($descuentoValor / $baseDescuento) * 100;
            }
        }

        /*
        * Redondear el porcentaje para evitar demasiados decimales.
        */
        $descuentoPorcentaje = round($descuentoPorcentaje, 2);


        /*
            * ---------------------------------------------------------
            * LOTE
            * ---------------------------------------------------------
            *
            * Estructura:
            *
            * <cac:AdditionalItemProperty>
            *     <cbc:ID>1</cbc:ID>
            *     <cbc:Name>Lote</cbc:Name>
            *     <cbc:NameCode>5173</cbc:NameCode>
            *     <cbc:Value>C260474</cbc:Value>
            * </cac:AdditionalItemProperty>
         */

        $lote = $xpathFirst(
            $linea,
            [
                './/cac:AdditionalItemProperty[cbc:NameCode="5173"]/cbc:Value',

                './/cac:AdditionalItemProperty['
                    . 'translate(normalize-space(cbc:Name), '
                    . '"ABCDEFGHIJKLMNOPQRSTUVWXYZ", '
                    . '"abcdefghijklmnopqrstuvwxyz")="lote"'
                    . ']/cbc:Value',

                './/*[local-name()="AdditionalItemProperty"]'
                    . '[*[local-name()="NameCode"]="5173"]'
                    . '/*[local-name()="Value"]',

                './/*[local-name()="AdditionalItemProperty"]'
                    . '[translate(normalize-space(*[local-name()="Name"]), '
                    . '"ABCDEFGHIJKLMNOPQRSTUVWXYZ", '
                    . '"abcdefghijklmnopqrstuvwxyz")="lote"]'
                    . '/*[local-name()="Value"]'
            ]
        );



        /*
            * ---------------------------------------------------------
            * VENCIMIENTO DEL PRODUCTO
            * ---------------------------------------------------------
            *
            * Estructura:
            *
            * <cac:AdditionalItemProperty>
            *     <cbc:ID>1</cbc:ID>
            *     <cbc:Name>Vencimiento</cbc:Name>
            *     <cbc:NameCode>5174</cbc:NameCode>
            *     <cbc:Value>2028-01-01</cbc:Value>
            * </cac:AdditionalItemProperty>
            */

        $vencimiento = $xpathFirst(
            $linea,
            [
                /*
                 * Por código DIAN.
                 */
                './/cac:AdditionalItemProperty[cbc:NameCode="5174"]/cbc:Value',

                /*
                 * Por nombre:
                 * - vencimiento
                 * - vto
                 * - fecha_vencimiento
                 * - vencimiento_lote
                 * - vto_lote
                 * etc.
                 */
                './/cac:AdditionalItemProperty['
                    . 'contains('
                    . 'translate(normalize-space(cbc:Name), '
                    . '"ABCDEFGHIJKLMNOPQRSTUVWXYZ", '
                    . '"abcdefghijklmnopqrstuvwxyz"), '
                    . '"vencimiento"'
                    . ')'
                    . ' or '
                    . 'contains('
                    . 'translate(normalize-space(cbc:Name), '
                    . '"ABCDEFGHIJKLMNOPQRSTUVWXYZ", '
                    . '"abcdefghijklmnopqrstuvwxyz"), '
                    . '"vto"'
                    . ')'
                    . ']/cbc:Value',

                /*
         * Fallback ignorando namespaces.
         */
                './/*[local-name()="AdditionalItemProperty"]['
                    . 'contains('
                    . 'translate(normalize-space(*[local-name()="Name"]), '
                    . '"ABCDEFGHIJKLMNOPQRSTUVWXYZ", '
                    . '"abcdefghijklmnopqrstuvwxyz"), '
                    . '"vencimiento"'
                    . ')'
                    . ' or '
                    . 'contains('
                    . 'translate(normalize-space(*[local-name()="Name"]), '
                    . '"ABCDEFGHIJKLMNOPQRSTUVWXYZ", '
                    . '"abcdefghijklmnopqrstuvwxyz"), '
                    . '"vto"'
                    . ')'
                    . ']/'
                    . '*[local-name()="Value"]'
            ]
        );


        $vencimiento = $normalizarFecha($vencimiento);


        /*
         * ---------------------------------------------------------
         * REGISTRO SANITARIO
         * ---------------------------------------------------------
         */

        $registroSanitario = $xpathFirst(
            $linea,
            [
                './/*[local-name()="RegistrationNumber"]',
                './/*[local-name()="RegistrationID"]',
                './/*[local-name()="RegSanitario"]',
                './/*[local-name()="RegistroSanitario"]'
            ]
        );

        /*
         * ---------------------------------------------------------
         * TOTAL
         * ---------------------------------------------------------
         */

        $total = $subtotal + $ivaValor;

        /*
         * ---------------------------------------------------------
         * AGREGAR PRODUCTO
         * ---------------------------------------------------------
         */

        $items[] = [
            'codigo' => $codigo,
            'codigo_proveedor' => $codigoVendedor,
            'codigo_interno' => $codigoComprador,
            'ean' => $ean,
            'descripcion' => $descripcion,
            'cantidad' => $cantidad,
            'unidad' => $unidad,
            'descuento_porcentaje' => round($descuentoPorcentaje, 4),
            'descuento_valor' => round($descuentoValor, 2),
            'costo' => round($costoUnitario, 4),
            'precio_xml' => round($costo, 4),
            'base_quantity' => $baseQuantity,
            'subtotal' => round($subtotal, 2),
            'iva_porcentaje' => round($ivaPorcentaje, 4),
            'iva_valor' => round($ivaValor, 2),
            'total' => round($total, 2),
            'lote' => $lote,
            'vencimiento' => $vencimiento,
            'registro_sanitario' => $registroSanitario
        ];
    }

    /*
     * -------------------------------------------------------------
     * 12. SI NO ENCONTRÓ PRODUCTOS
     * -------------------------------------------------------------
     */

    if (empty($items)) {

        return [
            'exito' => false,
            'mensaje' =>
            'No se encontraron líneas de productos en el XML.',
            'tipo_documento' => $tipoDocumento,
            'nit_emisor' => $nitEmisor,
            'numero_factura' => $numeroFactura,
            'fecha_factura' => $fechaFactura,
            'fecha_vencimiento_factura' =>
            $fechaVencimientoFactura,
            'items' => []
        ];
    }

    /*
     * -------------------------------------------------------------
     * 13. RETORNAR RESULTADO
     * -------------------------------------------------------------
     */

    return [

        'exito' => true,

        'mensaje' =>
        'Factura electrónica procesada correctamente.',

        'tipo_documento' =>
        $tipoDocumento,

        'nit_emisor' =>
        $nitEmisor,

        'numero_factura' =>
        $numeroFactura,

        'fecha_factura' =>
        $fechaFactura,

        'fecha_vencimiento_factura' =>
        $fechaVencimientoFactura,

        'cantidad_items' =>
        count($items),

        'items' =>
        $items
    ];
}


/**
 * -------------------------------------------------------------
 * EXTRAER DOCUMENTO INTERNO DE ATTACHEDDOCUMENT
 * -------------------------------------------------------------
 *
 * Algunos proveedores envían:
 *
 * <AttachedDocument>
 *
 *     <cac:Attachment>
 *         <cac:ExternalReference>
 *
 *             <cbc:Description>
 *                 <![CDATA[
 *
 *                     <Invoice>...</Invoice>
 *
 *                 ]]>
 *             </cbc:Description>
 *
 *         </cac:ExternalReference>
 *     </cac:Attachment>
 *
 * </AttachedDocument>
 *
 * Esta función busca ese XML interno.
 *
 * @param SimpleXMLElement $attachedDocument
 * @return SimpleXMLElement|false
 */
function extraerDocumentoInternoAttachedDocument($attachedDocument)
{
    /*
     * Buscar Description.
     */
    $descripciones = $attachedDocument->xpath(
        '//*[local-name()="ExternalReference"]/*[local-name()="Description"]'
    );

    if (empty($descripciones)) {
        return false;
    }

    foreach ($descripciones as $descripcion) {

        $contenido = trim((string) $descripcion);

        if (empty($contenido)) {
            continue;
        }

        /*
         * Buscar inicio de Invoice.
         */
        $posInvoice = strpos($contenido, '<Invoice');

        /*
         * Buscar inicio de CreditNote.
         */
        $posCreditNote = strpos($contenido, '<CreditNote');

        /*
         * Buscar DebitNote.
         */
        $posDebitNote = strpos($contenido, '<DebitNote');

        $posiciones = array_filter([
            $posInvoice,
            $posCreditNote,
            $posDebitNote
        ], function ($valor) {
            return $valor !== false;
        });

        if (empty($posiciones)) {
            continue;
        }

        $inicio = min($posiciones);

        /*
         * Eliminar cualquier contenido anterior.
         */
        $xmlInterno = substr($contenido, $inicio);

        /*
         * Intentar cargar directamente.
         */
        libxml_use_internal_errors(true);

        $documento = simplexml_load_string($xmlInterno);

        if ($documento !== false) {

            libxml_clear_errors();

            return $documento;
        }

        /*
         * ---------------------------------------------------------
         * Si falla, intentar extraer hasta el cierre correspondiente.
         * ---------------------------------------------------------
         */

        $root = null;

        if (strpos($xmlInterno, '<Invoice') === 0) {
            $root = 'Invoice';
        } elseif (strpos($xmlInterno, '<CreditNote') === 0) {
            $root = 'CreditNote';
        } elseif (strpos($xmlInterno, '<DebitNote') === 0) {
            $root = 'DebitNote';
        }

        if ($root !== null) {

            $cierre = '</' . $root . '>';

            $posCierre = strrpos($xmlInterno, $cierre);

            if ($posCierre !== false) {

                $xmlInterno = substr(
                    $xmlInterno,
                    0,
                    $posCierre + strlen($cierre)
                );

                $documento =
                    simplexml_load_string($xmlInterno);

                if ($documento !== false) {

                    libxml_clear_errors();

                    return $documento;
                }
            }
        }

        libxml_clear_errors();
    }

    return false;
}
