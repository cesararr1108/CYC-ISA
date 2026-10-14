<?php
session_start();
require_once('bd.php');
require_once('funciones.php');
$conn = conectar();
switch ($_POST["op"] ?? '') {

    case "validar-factura":

        if (
            !isset($_POST['tipo']) ||
            !isset($_POST['numero']) ||
            empty($_POST['tipo']) ||
            empty($_POST['numero'])
        ) {

            echo json_encode([
                "respuesta" => false,
                "id" => 0,
                "mensaje" => "Datos incompletos"
            ]);

            break;
        }

        $tipo = $_POST['tipo'];
        $numero = $_POST['numero'];


        $sql = "SELECT
                    t.id
                FROM
                    t_transacciones t
                WHERE
                    t.tipo = '$tipo'
                    AND t.numero = $numero
                    AND IFNULL(t.anulado,0) = 0";


        $facturas = GenerarArray($sql, '');


        if (!empty($facturas)) {

            echo json_encode([
                "respuesta" => true,
                "id" => $facturas[0]['id'],
                "mensaje" => "Factura encontrada"
            ]);
        } else {

            echo json_encode([
                "respuesta" => false,
                "id" => 0,
                "mensaje" => "Factura no encontrada"
            ]);
        }

        break;


    default:

        echo json_encode([
            "respuesta" => false,
            "id" => 0,
            "mensaje" => "Operación no válida"
        ]);

        break;
}
