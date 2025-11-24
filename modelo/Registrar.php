<?php
    include_once 'Conexion.php';

    class Registrar {
        var $objetos;

        public function __construct(){
            $db = new Conexion();
            $this->acceso = $db->pdo;
        }
        public function Crear($nombre, $email, $contrasena) {
            $sql = "SELECT email FROM usuario WHERE email = :email";
            $query = $this->acceso->prepare($sql);
            $query->execute(array(':email' => $email));
            $this->objetos = $query->fetchall();
            
            if (!empty($this->objetos)) {
                echo 'yaRegistrado';
            } else {
                $hashed_password = password_hash($contrasena, PASSWORD_DEFAULT);
                $sql = "INSERT INTO usuario (nombre, email, contraseña) VALUES (:nombre, :email, :contrasena)";
                $query = $this->acceso->prepare($sql);
                $query->execute(array(':nombre' => $nombre, ':email' => $email, ':contrasena' => $hashed_password));
                echo 'Usuario creado con éxito';
            }
        }
    }    

?>


