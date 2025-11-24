<?php
require_once __DIR__ . '/Conexion.php';

class ConfiguracionCertificado
{
    private $pdo;

    public function __construct()
    {
        $conexion = new Conexion();
        $this->pdo = $conexion->pdo;
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS configuracion_certificado (
            clave VARCHAR(100) PRIMARY KEY,
            valor TEXT NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        $this->pdo->exec($sql);
    }

    public function obtener(string $clave, $default = null)
    {
        $stmt = $this->pdo->prepare('SELECT valor FROM configuracion_certificado WHERE clave = ? LIMIT 1');
        $stmt->execute([$clave]);
        $valor = $stmt->fetchColumn();
        return $valor !== false ? $valor : $default;
    }

    public function guardar(string $clave, string $valor): bool
    {
        $stmt = $this->pdo->prepare('INSERT INTO configuracion_certificado (clave, valor) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE valor = VALUES(valor)');
        return $stmt->execute([$clave, $valor]);
    }

    public function obtenerTodo(): array
    {
        $stmt = $this->pdo->query('SELECT clave, valor FROM configuracion_certificado');
        $datos = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $datos[$row['clave']] = $row['valor'];
        }
        return $datos;
    }
}
