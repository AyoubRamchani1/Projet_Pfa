<?php
/**
 * ═══════════════════════════════════════════════════════════════
 * CONFIGURATION DE LA BASE DE DONNÉES
 * ═══════════════════════════════════════════════════════════════
 */

// Paramètres de connexion
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'projet_pfa');

// Créer la connexion
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Vérifier la connexion
if ($conn->connect_error) {
    die(json_encode([
        'success' => false,
        'message' => 'Erreur de connexion à la base de données: ' . $conn->connect_error
    ]));
}

// Définir le charset UTF-8
$conn->set_charset("utf8mb4");

?>
