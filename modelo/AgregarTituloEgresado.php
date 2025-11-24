<?php
include_once 'Conexion.php';
class AgregarTituloEgresado {
    var $objetos;

    public function __construct() {
        $db = new Conexion();
        $this->acceso = $db->pdo;
    }

    function BuscarTitulo($egresado, $titulo) {
        $sql = "SELECT * FROM tituloegresado WHERE identificacion = :egresado AND id = :titulo";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':egresado' => $egresado, ':titulo' => $titulo));
        $this->objetos = $query->fetchall();
        return $this->objetos;
    }

    function CrearTituloEgresado($egresado, $titulo, $fechaGrado) {
        $sql = "INSERT INTO tituloegresado (identificacion, id, fechaGrado) VALUES (:egresado, :titulo, :fechaGrado)";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':egresado' => $egresado, ':titulo' => $titulo, ':fechaGrado' => $fechaGrado));
    }

    function seleccionarEgresados() {
        $sql = "SELECT identificacion, nombreCompleto FROM egresado";
        $query = $this->acceso->prepare($sql);
        $query->execute();
        $this->objetos = $query->fetchall();
        echo json_encode($this->objetos);
    }

    function seleccionarTitulos() {
        $sql = "SELECT id, nombre FROM titulo";
        $query = $this->acceso->prepare($sql);
        $query->execute();
        $this->objetos = $query->fetchall();
        echo json_encode($this->objetos);
    }

    function obtenerTituloEgresado($identificacion) {
        $sql = "SELECT identificacion, id, DATE_FORMAT(fechaGrado, '%d/%m/%Y') as fechaGrado FROM tituloegresado WHERE identificacion = :identificacion";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':identificacion' => $identificacion));
        $this->objetos = $query->fetch();
        echo json_encode($this->objetos);
    }
    
    
}
?>
