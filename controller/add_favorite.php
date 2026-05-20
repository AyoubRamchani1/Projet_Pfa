<?php
session_start();
require_once '../configuration_base.php';

if (!isset($_POST['movie_id'])) {
    exit("Film invalide");
}

$movie_id = (int) $_POST['movie_id'];

// ── Récupérer l'utilisateur depuis email OU name ──
$user = null;
if (isset($_SESSION['email'])) {
    $stmt = $cnx->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$_SESSION['email']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} elseif (isset($_SESSION['name'])) {
    $stmt = $cnx->prepare("SELECT id FROM users WHERE name = ?");
    $stmt->execute([$_SESSION['name']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    exit("Connectez-vous d'abord");
}

if (!$user) exit("Utilisateur introuvable");

$id_user = $user['id'];

// ── Vérifier si déjà ajouté ──
$stmt = $cnx->prepare("SELECT id FROM favoris WHERE id_film = ? AND id_user = ?");
$stmt->execute([$movie_id, $id_user]);
if ($stmt->fetch()) exit("Déjà dans vos favoris");

// ── Insertion ──
$stmt = $cnx->prepare("INSERT INTO favoris (id_film, id_user) VALUES (?, ?)");
$stmt->execute([$movie_id, $id_user]);

// ── Mettre à jour le profil ──
$stmtFilm = $cnx->prepare("SELECT genres, directeur, acteurs FROM movies WHERE movie_id = ?");
$stmtFilm->execute([$movie_id]);
$film = $stmtFilm->fetch(PDO::FETCH_ASSOC);

if ($film) {
    $termes = [];

    foreach (explode(' ', trim($film['genres'] ?? '')) as $g) {
        if (strlen(trim($g)) > 1) {
            $termes[] = ['terme' => trim($g), 'type' => 'genre', 'points' => 1.5];
        }
    }

    if (trim($film['directeur'] ?? '')) {
        $termes[] = ['terme' => trim($film['directeur']), 'type' => 'directeur', 'points' => 2.0];
    }

    foreach (preg_split('/[\s,]+/', trim($film['acteurs'] ?? '')) as $a) {
        if (strlen(trim($a)) > 2) {
            $termes[] = ['terme' => trim($a), 'type' => 'acteur', 'points' => 1.0];
        }
    }

    $stmtP = $cnx->prepare("
        INSERT INTO user_profile (user_id, terme, type, score)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE score = score + VALUES(score)
    ");
    foreach ($termes as $t) {
        $stmtP->execute([$id_user, $t['terme'], $t['type'], $t['points']]);
    }
}

echo "OK";
?>