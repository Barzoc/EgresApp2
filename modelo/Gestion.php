<?php
    include_once 'Conexion.php';

    class Gestion {
        var $objetos;

        public function __construct(){
            $db = new Conexion();
            $this->acceso = $db->pdo;
        }

        //-----------------------------------------------------------
        // Buscar los registros segun criterio de busqueda en consulta
        //-----------------------------------------------------------
        function BuscarTodos($consulta){
            if(!empty($consulta)){                
                $sql = "SELECT * FROM gestion WHERE nombre LIKE :consulta";      
                $query = $this->acceso->prepare($sql);
                $query->execute(array(':consulta'=>"%$consulta%"));
                $this->objetos = $query->fetchall();
            }
            else{
                $sql = "SELECT * FROM gestion WHERE nombre NOT LIKE '' ORDER BY idGestion";          
                $query = $this->acceso->prepare($sql);
                $query->execute();
                $this->objetos = $query->fetchall();
            }
            //Retorna la consulta
            return $this->objetos;    
        } 
        
        //-----------------------------------------------------------
        // Buscar los registros segun criterio de busqueda en consulta
        //-----------------------------------------------------------
        function Buscar($id){
                        
                $sql = "SELECT * FROM gestion WHERE idGestion = :id";      
                $query = $this->acceso->prepare($sql);
                $query->execute(array(':id'=>$id));
                $this->objetos = $query->fetchall();
       
            //Retorna la consulta
            return $this->objetos;    
        }   

        //---------------------------------------------------------
        // Crear
        //---------------------------------------------------------
        function Crear($id, $nombre){
            $sql = "SELECT idGestion FROM gestion WHERE idGestion = :id";
            $query = $this->acceso->prepare($sql);
            $query->execute(array(':id'=>$id));;
            $this->objetos = $query->fetchall();
            if(!empty($this->objetos))
                echo 'noadd';
            else{
                $sql = "INSERT INTO Gestion (idGestion, nombre)
                        VALUES (:id, :nombre)";
                $query = $this->acceso->prepare($sql);
                $query->execute(array(':id'=>$id, ':nombre'=>$nombre));
                echo 'add';
            }
        }


        //-----------------------------------------------------------
        // Editar
        //-----------------------------------------------------------
        function Editar($id, $nombre){
            $sql = "UPDATE gestion SET nombre =:nombre 
                    WHERE idGestion = :id";        
            $query = $this->acceso->prepare($sql);
            $query->execute(array(':id'=>$id,':nombre'=>$nombre));
            echo 'update';
         }

        //-----------------------------------------------------------
        // Eliminar
        //-----------------------------------------------------------
        function Eliminar($id){
            $sql = "DELETE FROM gestion WHERE idGestion = :id";        
            $query = $this->acceso->prepare($sql);
            $query->execute(array(':id'=>$id)); 
            if(!empty($query->execute(array(':id'=>$id))))
                echo 'eliminado';
            else 
                echo 'noeliminado';
        }

        //-----------------------------------------------------------
        // Función para cargar un ComboBox
        //-----------------------------------------------------------
        function Seleccionar(){
            $sql = "SELECT * FROM gestion ORDER BY nombre asc";          
            $query = $this->acceso->prepare($sql);
            $query->execute();
            $this->objetos = $query->fetchall();
        
            //Retorna la consulta
            return $this->objetos;    
        }

         
    }     
?>