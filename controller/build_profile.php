<?php
require_once '../configuration_base.php';

// Récupérer tout l'historique
$historique = $cnx->query("
    SELECT h.id_user, m.genres, m.directeur, m.acteurs
    FROM historique h
    JOIN movies m ON m.movie_id = h.id_film
")->fetchAll(PDO::FETCH_ASSOC);

$stmtP = $cnx->prepare("
    INSERT INTO user_profile (user_id, terme, type, score)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE score = score + VALUES(score)
");

foreach ($historique as $h) {
    // Genres
    foreach (explode(' ', trim($h['genres'] ?? '')) as $g) {
        if (strlen(trim($g)) > 1) {
            $stmtP->execute([$h['id_user'], trim($g), 'genre', 1.0]);
        }
    }

    // Réalisateur
    if (trim($h['directeur'] ?? '')) {
        $stmtP->execute([$h['id_user'], trim($h['directeur']), 'directeur', 1.5]);
    }

    // Acteurs séparés
    foreach (preg_split('/[\s,]+/', trim($h['acteurs'] ?? '')) as $a) {
        if (strlen(trim($a)) > 2) {
            $stmtP->execute([$h['id_user'], trim($a), 'acteur', 0.8]);
        }
    }
}

// Favoris — bonus supplémentaire
$favoris = $cnx->query("
    SELECT f.id_user, m.genres, m.directeur, m.acteurs
    FROM favoris f
    JOIN movies m ON m.movie_id = f.id_film
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($favoris as $f) {
    foreach (explode(' ', trim($f['genres'] ?? '')) as $g) {
        if (strlen(trim($g)) > 1) {
            $stmtP->execute([$f['id_user'], trim($g), 'genre', 1.5]); // bonus favori
        }
    }
    if (trim($f['directeur'] ?? '')) {
        $stmtP->execute([$f['id_user'], trim($f['directeur']), 'directeur', 2.0]);
    }
}

echo "✅ Profil construit depuis historique et favoris !";
?>