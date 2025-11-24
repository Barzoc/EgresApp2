<?php
include_once '../modelo/Expediente.php';

class ExpedienteController {
    private $expediente;

    public function __construct() {
        $this->expediente = new Expediente();
    }

    /**
     * Registra la emisión de un certificado
     */
    public function registrarEmision($identificacion, $archivo_pdf = null) {
        return $this->expediente->registrarEmision($identificacion, $archivo_pdf);
    }

    /**
     * Obtiene datos del expediente por identificación
     */
    public function obtenerDatos($identificacion) {
        return $this->expediente->obtenerDatosCertificado($identificacion);
    }
}

// Manejo de peticiones AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new ExpedienteController();
    $response = ['success' => false, 'message' => 'Operación no especificada'];
    
    if (isset($_POST['funcion'])) {
        switch ($_POST['funcion']) {
            case 'registrar_emision':
                if (isset($_POST['identificacion'])) {
                    $archivo_pdf = isset($_POST['archivo_pdf']) ? $_POST['archivo_pdf'] : null;
                    if ($controller->registrarEmision($_POST['identificacion'], $archivo_pdf)) {
                        $response = ['success' => true, 'message' => 'Emisión registrada'];
                    } else {
                        $response = ['success' => false, 'message' => 'Error al registrar emisión'];
                    }
                }
                break;
                
            case 'obtener_datos':
                if (isset($_POST['identificacion'])) {
                    $datos = $controller->obtenerDatos($_POST['identificacion']);
                    if ($datos) {
                        $response = ['success' => true, 'data' => $datos[0]];
                    } else {
                        $response = ['success' => false, 'message' => 'No se encontraron datos'];
                    }
                }
                break;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}