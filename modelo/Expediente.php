<?php
include_once 'Conexion.php';

class Expediente {
    var $objetos;
    private $acceso;

    public function __construct() {
        $db = new Conexion();
        $this->acceso = $db->pdo;
    }

    /**
     * Registra una nueva emisión de certificado
     */
    public function registrarEmision($identificacion, $archivo_pdf = null) {
        try {
            $sql = "CALL sp_registrar_emision_certificado(?, ?)";
            $query = $this->acceso->prepare($sql);
            $query->execute([$identificacion, $archivo_pdf]);
            return true;
        } catch (Exception $e) {
            error_log("Error al registrar emisión: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene datos del expediente y certificados por identificación
     */
    public function obtenerDatosCertificado($identificacion) {
        $sql = "SELECT * FROM v_datos_certificados WHERE identificacion = ?";
        $query = $this->acceso->prepare($sql);
        $query->execute([$identificacion]);
        $this->objetos = $query->fetchAll(PDO::FETCH_OBJ);
        return $this->objetos;
    }

    /**
     * Obtiene datos del expediente usando el RUT/carnet
     */
    public function obtenerPorRut($rut) {
        // Limpiar RUT para búsqueda
        $rut_limpio = preg_replace('/[^0-9kK]/', '', $rut);
        
        $sql = "SELECT * FROM v_datos_certificados WHERE REPLACE(REPLACE(rut, '.', ''), '-', '') = ?";
        $query = $this->acceso->prepare($sql);
        $query->execute([$rut_limpio]);
        $this->objetos = $query->fetchAll(PDO::FETCH_OBJ);
        return $this->objetos;
    }

    /**
     * Lista todos los expedientes con datos relevantes
     */
    public function listarTodos() {
        $sql = "SELECT * FROM v_datos_certificados ORDER BY fecha_ultima_emision DESC";
        $query = $this->acceso->prepare($sql);
        $query->execute();
        $this->objetos = $query->fetchAll(PDO::FETCH_OBJ);
        return $this->objetos;
    }
}