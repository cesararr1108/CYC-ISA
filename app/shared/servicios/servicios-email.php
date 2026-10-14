<?php
require_once(__DIR__ . '/../../../assets/email/PHPMailer-master/src/PHPMailer.php');
require_once(__DIR__ . '/../../../assets/email/PHPMailer-master/src/SMTP.php');
require_once(__DIR__ . '/../../../assets/email/PHPMailer-master/src/Exception.php');


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Correo extends PHPMailer
{

    public function __construct($exceptions = true, $empresa = 'CYC')
    {
        parent::__construct($exceptions);

        // Configuración del servidor SMTP (ajusta con Gmail o Brevo)
        /* $this->isSMTP();
        $this->Host       = 'smtp-relay.brevo.com';  // o smtp.gmail.com si usas Gmail
        $this->SMTPAuth   = true;
        $this->Username   = '8f9d93002@smtp-brevo.com'; 
        $this->Password   = 'SgAnKIctmxQMT2FB';      
        $this->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->Port       = 587;

        // Remitente por defecto
        $this->setFrom('cribuma@gmail.com',$empresa);
        $this->isHTML(true); // siempre en HTML*/

        //Configuracion copifam

        $this->isSMTP();
        $this->Host       = 'smtp.hostinger.com';
        $this->SMTPAuth   = true;
        $this->Username   = 'christian.bula@thecyctechnology.com';
        $this->Password   = 'Cr1buma27$%';
        $this->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->Port       = 587;
        $this->CharSet    = 'UTF-8';
        $this->Encoding   = 'base64';

        $this->setFrom('isa@thecyctechnology.com', 'ISA Software');
        $this->isHTML(true);

        //Cr1buma27$%
    }

    /**
     * Enviar correo
     * @param array|string $destinatarios Lista de correos o un solo correo
     * @param string $asunto Asunto del correo
     * @param string $htmlBody Contenido en HTML
     * @param string|null $adjunto Ruta del archivo adjunto (opcional)
     */
    public function enviarCorreo($destinatarios, $asunto, $htmlBody, $adjunto = null)
    {
        try {
            // Limpiar destinatarios anteriores
            $this->clearAddresses();

            // Añadir destinatarios
            if (is_array($destinatarios)) {
                foreach ($destinatarios as $correo) {
                    $this->addAddress($correo);
                }
            } else {
                $this->addAddress($destinatarios);
            }

            // Asunto y cuerpo
            $this->Subject = $asunto;
            $this->Body    = $htmlBody;

            // Adjuntar si existe

            if ($adjunto && file_exists($adjunto)) {
                $this->addAttachment($adjunto);
            }

            $resultado =  $this->send();

            if ($adjunto && file_exists($adjunto)) {
                unlink($adjunto);
            }

            return $resultado;
        } catch (Exception $e) {
            throw new Exception("Error enviando correo: " . $this->ErrorInfo);
        }
    }
}
