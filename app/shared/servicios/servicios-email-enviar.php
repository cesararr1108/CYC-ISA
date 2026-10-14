<?php

require_once('servicios-email.php');
header('Content-Type: application/json; charset=utf-8');
$correo = new Correo(true, 'CYC');
$destinatarios  = $_POST['destinatarios'] ?? '';
$asunto = $_POST['asunto'] ?? '';
$link = $_POST['url'] ?? '';
$nombres = $_POST['nombres'] ?? '';
if ($destinatarios === '') {
    echo json_encode([
        'ok'  => false,
        'message' => 'No se proporcionaron destinatarios'
    ]);
    exit;
}
if ($link === '') {
    echo json_encode([
        'ok'  => false,
        'message' => 'No se proporcionó un enlace de descarga'
    ]);;
    exit;
}
$html = '<!DOCTYPE html>
                    <html lang="es">
                    <head>
                        <meta charset="UTF-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                        <title>Documento - ISA Software</title>
                    </head>

                    <body style="margin:0; padding:0; background-color:#f5f3fa; font-family:Arial, Helvetica, sans-serif; color:#25234a;">

                        <!-- CONTENEDOR PRINCIPAL -->
                        <table width="100%" cellpadding="0" cellspacing="0" border="0" 
                            style="background-color:#f5f3fa; padding:35px 15px;">
                            <tr>
                                <td align="center">

                                    <!-- CARD -->
                                    <table width="600" cellpadding="0" cellspacing="0" border="0"
                                        style="max-width:600px; width:100%; background-color:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 5px 25px rgba(70,30,130,0.10);">

                                        <!-- HEADER -->
                                        <tr>
                                            <td align="center"
                                                style="padding:30px 30px 25px 30px; background-color:#ffffff;">

                                                <!-- LOGO -->
                                                <img src="https://thecyctechnology.com/assets/logos-empresas/logo-isa.png"
                                                    alt="ISA Software"
                                                    width="190"
                                                    style="display:block; width:190px; max-width:100%; height:auto; border:0;">

                                            </td>
                                        </tr>

                                        <!-- FRANJA -->
                                        <tr>
                                            <td style="height:6px; background-color:#5b21d8; font-size:0; line-height:0;">&nbsp;
                                                
                                            </td>
                                        </tr>

                                        <!-- CONTENIDO -->
                                        <tr>
                                            <td style="padding:38px 40px 30px 40px;">

                                                <!-- ICONO -->
                                                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                                    <tr>
                                                        <td align="center">

                                                            <div style="
                                                                width:70px;
                                                                height:70px;
                                                                background-color:#f0e8ff;
                                                                border-radius:50%;
                                                                text-align:center;
                                                                line-height:70px;
                                                                font-size:32px;
                                                                color:#5b21d8;
                                                            ">
                                                                📄
                                                            </div>

                                                        </td>
                                                    </tr>
                                                </table>

                                                <!-- TITULO -->
                                                <h1 style="
                                                    margin:25px 0 10px 0;
                                                    text-align:center;
                                                    font-size:27px;
                                                    line-height:34px;
                                                    color:#21145f;
                                                    font-weight:700;
                                                ">
                                                    Documento disponible
                                                </h1>

                                                <p style="
                                                    margin:0 0 25px 0;
                                                    text-align:center;
                                                    font-size:16px;
                                                    line-height:25px;
                                                    color:#68657a;
                                                ">
                                                    Hola <strong>'.$nombres.'</strong>,
                                                    hemos generado un nuevo documento desde
                                                    <strong>ISA Software</strong>.
                                                </p>

                                                <!-- DATOS DOCUMENTO -->
                                            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                                    style="
                                                    background-color:#f8f6ff;
                                                    border-radius:12px;
                                                    margin:20px 0 28px 0;
                                                    border:1px solid #e8e1fa;
                                                    ">

                                                    <tr> </tr>

                                                </table>

                                                <!-- MENSAJE -->
                                                <p style="
                                                    margin:0 0 25px 0;
                                                    text-align:center;
                                                    font-size:15px;
                                                    line-height:24px;
                                                    color:#68657a;
                                                ">
                                                    Puedes consultar y descargar tu documento haciendo clic
                                                    en el siguiente botón.
                                                </p>

                                                <!-- BOTON -->
                                                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                                    <tr>
                                                        <td align="center">

                                                            <a href="'.$link.'"
                                                            target="_blank"
                                                            style="
                                                            display:inline-block;
                                                            padding:16px 38px;
                                                            background-color:#5b21d8;
                                                            color:#ffffff;
                                                            text-decoration:none;
                                                            font-size:16px;
                                                            font-weight:bold;
                                                            border-radius:10px;
                                                            ">
                                                                ⬇ &nbsp; Descargar documento
                                                            </a>

                                                        </td>
                                                    </tr>
                                                </table>

                                                <!-- AVISO -->
                                                <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                                    style="margin-top:30px;">
                                                    <tr>
                                                        <td style="
                                                            padding:16px 18px;
                                                            background-color:#fff8e8;
                                                            border-left:4px solid #f4b400;
                                                            border-radius:6px;
                                                            font-size:13px;
                                                            line-height:20px;
                                                            color:#665c42;
                                                        ">

                                                            <strong>Importante:</strong><br>
                                                            Este enlace es personal y permite consultar
                                                            el documento asociado a esta comunicación y tendra una vigencia de 24 horas.

                                                        </td>
                                                    </tr>
                                                </table>

                                            </td>
                                        </tr>

                                        <!-- SEPARADOR -->
                                        <tr>
                                            <td style="
                                                padding:0 40px;
                                            ">
                                                <div style="
                                                    border-top:1px solid #eeeeee;
                                                    height:1px;
                                                    line-height:1px;
                                                    font-size:1px;
                                                ">
                                                    &nbsp;
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- FOOTER -->
                                        <tr>
                                            <td align="center"
                                                style="padding:28px 30px 32px 30px;">

                                                <p style="
                                                    margin:0 0 8px 0;
                                                    font-size:15px;
                                                    font-weight:bold;
                                                    color:#21145f;
                                                ">
                                                    ISA Software
                                                </p>

                                                <p style="
                                                    margin:0 0 15px 0;
                                                    font-size:13px;
                                                    color:#858193;
                                                    line-height:20px;
                                                ">
                                                    Tecnología para el control y crecimiento de tu negocio.
                                                </p>

                                                <p style="
                                                    margin:0 0 15px 0;
                                                    font-size:12px;
                                                    color:#858193;
                                                    line-height:19px;
                                                ">
                                                    Desarrollado por <strong>CYC Technology S.A.S</strong><br>
                                                    Todos los derechos reservados © '.date('Y').'<br>
                                                    Medellín, Antioquia<br>
                                                    Tels. 3242181420 - 3015941578<br>
                                                    Email. ventas@thecyctechnology.com<br>
                                                    www.thecyctechnology.com
                                                </p>

                                                <p style="
                                                    margin:0;
                                                    font-size:11px;
                                                    color:#aaa6b5;
                                                    line-height:18px;
                                                ">
                                                    Este correo fue generado automáticamente por ISA Software.
                                                    <br>
                                                    Por favor, no respondas directamente a este mensaje.
                                                </p>

                                            </td>
                                        </tr>


                                        <!-- FRANJA FINAL -->
                                        <tr>
                                            <td style="
                                                height:5px;
                                                background-color:#e92d9b;
                                                font-size:0;
                                                line-height:0;
                                            ">&nbsp;
                                                
                                            </td>
                                        </tr>

                                    </table>

                                </td>
                            </tr>
                        </table>

                    </body>
                    </html>';
$enviado = $correo->enviarCorreo($destinatarios, $asunto, $html, '');

if ($enviado) {
    echo json_encode([
        'ok'  => true,
        'message' => 'Correo enviado correctamente'
    ]);
} else {
    echo json_encode([
        'ok'  => false,
        'message' => 'No se pudo enviar el correo'
    ]);
}
