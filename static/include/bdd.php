<?php
try {
    // Lecture des accès depuis les variables d'environnement
    $driverName = getenv('DB_DRIVER_NAME');
    $dbUsername = getenv('DB_USERNAME');
    $dbPassword = getenv('DB_PASSWORD');
    $host = getenv('DB_HOST');
    $dbName = getenv('DB_NAME');

    $pdo = new PDO($driverName . ':host=' . $host . ';dbname=' . $dbName, $dbUsername, $dbPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die('Erreur PDO, connexion impossible: ' . $e->getMessage());
}
