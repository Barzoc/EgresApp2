<?php
require __DIR__ . '/modelo/Conexion.php';

$clean = '167694152';
try {
    $db = new Conexion();
    $pdo = $db->pdo;
    $sql = "SELECT nombreCompleto FROM egresado WHERE REPLACE(REPLACE(UPPER(carnet),'.',''),'-','') = :clean";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':clean' => $clean]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) var_dump($row);
    else echo "Not found\n";
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
