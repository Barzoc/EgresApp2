<?php
include_once 'Conexion.php';

class Certificado {
    private $pdo;

    public function __construct() {
        $db = new Conexion();
        $this->pdo = $db->pdo;
    }

    /**
     * Inserta un registro en la tabla certificados usando consultas preparadas.
     */
    public function insertar(array $datos) {
        $sql = "INSERT INTO certificados (rut, nombre_completo, anio_egreso, titulo, num_certificado, fecha_entrega)
                VALUES (:rut, :nombre_completo, :anio_egreso, :titulo, :num_certificado, :fecha_entrega)";

        $stmt = $this->pdo->prepare($sql);

        $stmt->bindValue(':rut', $datos['rut']);
        $stmt->bindValue(':nombre_completo', $datos['nombre_completo']);

        if ($datos['anio_egreso'] !== null) {
            $stmt->bindValue(':anio_egreso', (int)$datos['anio_egreso'], PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':anio_egreso', null, PDO::PARAM_NULL);
        }

        $stmt->bindValue(':titulo', $datos['titulo']);
        $stmt->bindValue(':num_certificado', $datos['num_certificado']);

        if ($datos['fecha_entrega'] !== null) {
            $stmt->bindValue(':fecha_entrega', $datos['fecha_entrega']);
        } else {
            $stmt->bindValue(':fecha_entrega', null, PDO::PARAM_NULL);
        }

        return $stmt->execute();
    }
}
