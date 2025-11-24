<?php
require __DIR__ . '/modelo/Conexion.php';

try {
    $db = new Conexion();
    $pdo = $db->pdo;
    $sql = "SELECT identificacion, carnet, REPLACE(REPLACE(UPPER(carnet),'.',''),'-','') AS clean FROM egresado";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        echo sprintf("id=%s, carnet='%s', clean='%s'\n", $r['identificacion'], $r['carnet'], $r['clean']);
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
