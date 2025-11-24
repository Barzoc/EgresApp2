<?php
include_once 'Conexion.php';

class Ingresar {
    var $objetos;

    public function __construct(){
        $db = new Conexion();
        $this->acceso = $db->pdo;
    }

    public function Validar($email, $contrasena) {
        $sql = "SELECT contraseña FROM usuario WHERE email = :email";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':email' => $email));
        $this->objetos = $query->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($this->objetos)) {
            $row = $this->objetos[0]; // Obtenemos la primera fila del resultado
            if (password_verify($contrasena, $row['contraseña'])) {
                echo 'ingresoExitoso';
                $_SESSION['email'] = $_POST['email'];
            } else {
                echo 'ingresoFallido';
            }
        } else {
            echo 'ingresoFallido'; // No se encontró el email
        }
    }
}
?>
