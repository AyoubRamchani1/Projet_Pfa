<?php
// Configuration de la base de données
$host = 'localhost';
$dbname = 'projet_pfa';
$username = 'root';
$password = '';

try {
    $cnx = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $cnx->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>