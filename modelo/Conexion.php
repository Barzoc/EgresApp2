
<?php
//Clase de conexion a una base de datos con PDO

class Conexion extends PDO{
    private $motor='mysql';
    private $host='localhost';
    private $dbname = 'gestion_egresados';
    private $port = '3306';
    private $user = 'root';
    private $pass = '';
    private $charset = 'utf8';
    private $atributos = [
                            PDO::ATTR_CASE=>PDO::CASE_LOWER,
                            PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_ORACLE_NULLS=>PDO::NULL_EMPTY_STRING, 
                            PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_OBJ
    ];

    public $pdo = null;

    public function __construct(){

        $dns ="$this->motor:host=$this->host;port=$this->port;dbname=$this->dbname;charset=$this->charset";
        try {
            $this->pdo = new PDO($dns, $this->user, $this->pass, $this->atributos);
        } catch (Exception $e) {
            echo "Tiene el siguiente error", $e->getMessage();
        }            
    }
}
?>