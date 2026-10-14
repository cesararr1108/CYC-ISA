<?php

class IntelligenceService
{
    private $conn;

    public function __construct()
    {
        $this->validarContexto();

        $this->conn = conectar();
    }

    /**
     * Verifica que exista una empresa activa.
     */
    private function validarContexto()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (
            empty($_SESSION['Ses_empresa']) ||
            !defined('DB') ||
            empty(DB)
        ) {
            throw new Exception(
                'No existe una empresa activa para ISA Intelligence.'
            );
        }
    }

    /**
     * Devuelve la conexión actual.
     */
    public function getConnection()
    {
        return $this->conn;
    }

    /**
     * Ejecuta una consulta SQL.
     */
    public function consultar($sql)
    {
        $resultado = $this->conn->query($sql);

        if (!$resultado) {
            throw new Exception(
                'Error ejecutando consulta Intelligence: ' .
                $this->conn->error
            );
        }

        $datos = [];

        while ($fila = $resultado->fetch_assoc()) {
            $datos[] = $fila;
        }

        $resultado->free();

        return $datos;
    }

    /**
     * Ejecuta una consulta preparada.
     */
    public function consultarPreparada(
        $sql,
        $parametros = [],
        $tipos = ''
    ) {
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                'Error preparando consulta Intelligence: ' .
                $this->conn->error
            );
        }

        if (!empty($parametros)) {

            if (empty($tipos)) {
                throw new Exception(
                    'Debe especificar los tipos de parámetros.'
                );
            }

            $stmt->bind_param(
                $tipos,
                ...$parametros
            );
        }

        if (!$stmt->execute()) {
            throw new Exception(
                'Error ejecutando consulta Intelligence: ' .
                $stmt->error
            );
        }

        $resultado = $stmt->get_result();

        $datos = [];

        if ($resultado) {

            while ($fila = $resultado->fetch_assoc()) {
                $datos[] = $fila;
            }

            $resultado->free();
        }

        $stmt->close();

        return $datos;
    }

    /**
     * Información del contexto actual.
     */
    public function contexto()
    {
        return [
            'empresa' => DB,
            'fecha'   => date('Y-m-d'),
            'hora'    => date('H:i:s')
        ];
    }

    /**
     * Cierra la conexión.
     */
    public function cerrar()
    {
        if ($this->conn) {
            $this->conn->close();
            $this->conn = null;
        }
    }
}