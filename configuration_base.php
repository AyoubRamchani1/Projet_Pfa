<?php

$host = '127.0.0.1'; // Changé 'localhost' par '127.0.0.1' pour stabiliser le mode CLI (PHPUnit)
$dbname = 'projet_pfa';
$username = 'root';
$password = '';

// On s'assure que $cnx est accessible globalement
global $cnx;

try {
    $cnx = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $cnx->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $cnx->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Si la connexion échoue, on affiche explicitement l'erreur dans la console PHPUnit
    fwrite(STDERR, "Erreur de connexion à la base de données : " . $e->getMessage() . PHP_EOL);
    exit(1);
}