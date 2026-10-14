<?php

class AIService
{
    private $apiKey;
    private $modelo;
    private $url;

    public function __construct()
    {
        /*
         * ==========================================================
         * CONFIGURACIÓN
         * ==========================================================
         *
         * Por seguridad, la API Key debe estar almacenada
         * fuera del código fuente.
         */

        $this->apiKey = getenv('OPENAI_API_KEY');

        /*
         * Modelo por defecto.
         * Lo podemos cambiar posteriormente desde configuración.
         */
        $this->modelo = 'gpt-5.6';

        $this->url = 'https://api.openai.com/v1/responses';
    }


    /**
     * Envía un contexto a la IA y obtiene una respuesta.
     *
     * @param string $instruccion
     * @param array $contexto
     * @return array
     */
    public function analizar($instruccion, $contexto = [])
    {
        if (empty($this->apiKey)) {
            throw new Exception(
                'No está configurada la variable OPENAI_API_KEY.'
            );
        }

        /*
         * ==========================================================
         * PREPARAR CONTEXTO
         * ==========================================================
         */

        $contextoTexto = json_encode(
            $contexto,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES |
            JSON_PRETTY_PRINT
        );

        /*
         * ==========================================================
         * PROMPT
         * ==========================================================
         */

        $prompt = $instruccion . "\n\n";

        $prompt .= "DATOS DEL SISTEMA ISA:\n";

        $prompt .= $contextoTexto;

        /*
         * ==========================================================
         * REQUEST
         * ==========================================================
         */

        $payload = [
            'model' => $this->modelo,

            'input' => [
                [
                    'role' => 'system',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' =>
                                'Eres ISA Intelligence, un asistente '
                                . 'especializado en analizar información '
                                . 'empresarial del sistema ISA Software. '
                                . 'Debes basarte únicamente en los datos '
                                . 'proporcionados por ISA y generar '
                                . 'conclusiones claras, útiles y accionables.'
                        ]
                    ]
                ],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => $prompt
                        ]
                    ]
                ]
            ]
        ];

        /*
         * ==========================================================
         * CURL
         * ==========================================================
         */

        $ch = curl_init($this->url);

        curl_setopt_array($ch, [

            CURLOPT_RETURNTRANSFER => true,

            CURLOPT_POST => true,

            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ],

            CURLOPT_POSTFIELDS => json_encode($payload),

            CURLOPT_TIMEOUT => 60
        ]);

        $respuesta = curl_exec($ch);

        $httpCode = curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );

        $error = curl_error($ch);

        curl_close($ch);


        /*
         * ==========================================================
         * ERROR CURL
         * ==========================================================
         */

        if ($respuesta === false) {

            throw new Exception(
                'Error conectando con la IA: ' . $error
            );
        }


        /*
         * ==========================================================
         * DECODIFICAR RESPUESTA
         * ==========================================================
         */

        $resultado = json_decode(
            $respuesta,
            true
        );


        /*
         * ==========================================================
         * ERROR API
         * ==========================================================
         */

        if ($httpCode >= 400) {

            $mensaje = $resultado['error']['message']
                ?? 'Error desconocido de la API de IA.';

            throw new Exception(
                'Error IA (' . $httpCode . '): ' . $mensaje
            );
        }


        /*
         * ==========================================================
         * EXTRAER TEXTO
         * ==========================================================
         */

        $texto = $this->extraerTexto(
            $resultado
        );


        return [
            'ok' => true,
            'modelo' => $this->modelo,
            'respuesta' => $texto,
            'respuesta_api' => $resultado
        ];
    }


    /**
     * Extrae el texto de la respuesta de Responses API.
     */
    private function extraerTexto($resultado)
    {
        /*
         * La API puede devolver diferentes estructuras.
         *
         * Primero intentamos utilizar output_text
         * si está disponible.
         */

        if (
            isset($resultado['output_text']) &&
            is_string($resultado['output_text'])
        ) {
            return $resultado['output_text'];
        }


        /*
         * ==========================================================
         * RECORRER OUTPUT
         * ==========================================================
         */

        $texto = '';

        if (!empty($resultado['output'])) {

            foreach ($resultado['output'] as $output) {

                if (
                    !isset($output['content']) ||
                    !is_array($output['content'])
                ) {
                    continue;
                }

                foreach ($output['content'] as $content) {

                    if (
                        isset($content['text']) &&
                        is_string($content['text'])
                    ) {
                        $texto .= $content['text'];
                    }
                }
            }
        }


        return trim($texto);
    }


    /**
     * Permite cambiar el modelo.
     */
    public function establecerModelo($modelo)
    {
        if (!empty($modelo)) {
            $this->modelo = $modelo;
        }
    }


    /**
     * Devuelve el modelo actualmente configurado.
     */
    public function obtenerModelo()
    {
        return $this->modelo;
    }
}