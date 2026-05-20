<?php
header('Content-Type: application/json');
require_once '../configuration_base.php'; // Vérifie bien le chemin ici

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) { 
    echo json_encode([]); 
    exit; 
}

try {
    // On utilise les noms exacts de ta base (image 2)
    $stmt = $cnx->prepare("
        SELECT movie_id, title, genres, vote_average 
        FROM movies 
        WHERE title LIKE ? 
        ORDER BY popularity DESC 
        LIMIT 8
    ");
    $stmt->execute(["%$q%"]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($results);
} catch (Exception $e) {
    echo json_encode([]);
}