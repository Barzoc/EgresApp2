<?php
include_once 'Conexion.php';

class Observacion {
    var $objetos;

    public function __construct(){
        $db = new Conexion();
        $this->acceso = $db->pdo;
    }

    //-----------------------------------------------------------
    // Buscar los registros segun criterio de busqueda en consulta
    //-----------------------------------------------------------
    function BuscarTodos(){
        $sql = "SELECT o.id, o.observacion, o.identificacion, e.nombreCompleto
                FROM observacion o
                JOIN egresado e ON o.identificacion = e.identificacion
                ORDER BY o.id";
        $query = $this->acceso->prepare($sql);
        $query->execute();
        $this->objetos = $query->fetchall();
        return $this->objetos;    
    }

    //-----------------------------------------------------------
    // Buscar un registro por ID
    //-----------------------------------------------------------
    function Buscar($id){
        $sql = "SELECT * FROM observacion WHERE id = :id";          
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':id'=>$id));
        $this->objetos = $query->fetchall();
        return $this->objetos;    
    }

    //-------------------------------------------
    // Crear
    //-------------------------------------------
    function Crear($id, $observacion, $identificacion){
        $sql = "SELECT id FROM observacion WHERE id = :id";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':id'=>$id));
        $this->objetos = $query->fetchall();
        if(!empty($this->objetos)){
            echo 'noadd';
        }
        else{
            $sql = "INSERT INTO observacion (id, observacion, identificacion)
                    VALUES (:id, :observacion, :identificacion)";
            $query = $this->acceso->prepare($sql);
            $query->execute(array(':id'=>$id, ':observacion'=>$observacion, ':identificacion'=>$identificacion));
            echo 'add';
        }
    }

    //-----------------------------------------------------------
    // Editar
    //-----------------------------------------------------------
    function Editar($id, $observacion, $identificacion){
        $sql = "UPDATE observacion SET observacion = :observacion, identificacion = :identificacion 
                WHERE id = :id";        
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':id'=>$id, ':observacion'=>$observacion, ':identificacion'=>$identificacion));
        echo 'update';
    }

    //-----------------------------------------------------------
    // Eliminar
    //-----------------------------------------------------------
    function Eliminar($id){
        $sql = "DELETE FROM observacion WHERE id = :id";        
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':id'=>$id)); 
        echo 'eliminado';
    }
}
?>
