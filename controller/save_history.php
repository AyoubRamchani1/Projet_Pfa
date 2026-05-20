<?php
session_start();
require_once '../configuration_base.php';

if (!isset($_SESSION['email'])) {
    die("Utilisateur non connecté");
}

if (!isset($_POST['movie_id'])) {
    die("Film manquant");
}

// Récupérer l'id user depuis email
$stmt = $cnx->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$_SESSION['email']]);
$idUser = $stmt->fetchColumn();

if (!$idUser) die("Utilisateur introuvable");

$idMovie = intval($_POST['movie_id']);

// ── Historique ──
$stmt = $cnx->prepare("
    INSERT INTO historique (id_user, id_film, date_de_regard)
    VALUES (?, ?, NOW())
");
$stmt->execute([$idUser, $idMovie]);

// ── Récupérer infos du film ──
$stmt = $cnx->prepare("SELECT genres, directeur, acteurs FROM movies WHERE movie_id = ?");
$stmt->execute([$idMovie]);
$film = $stmt->fetch(PDO::FETCH_ASSOC);

if ($film) {
    $termes = [];

    foreach (explode(' ', trim($film['genres'] ?? '')) as $g) {
        if (trim($g)) $termes[] = ['terme' => trim($g), 'type' => 'genre', 'points' => 1.0];
    }

    if (trim($film['directeur'] ?? '')) {
        $termes[] = ['terme' => trim($film['directeur']), 'type' => 'directeur', 'points' => 1.5];
    }

    // Acteurs — séparer par espace ou virgule
$acteurs = preg_split('/[\s,]+/', trim($film['acteurs'] ?? ''));
foreach ($acteurs as $a) {
    if (strlen(trim($a)) > 2) {
        $termes[] = ['terme' => trim($a), 'type' => 'acteur', 'points' => 0.8];
    }
}
    // ── Mettre à jour profil ──
    $stmtP = $cnx->prepare("
        INSERT INTO user_profile (user_id, terme, type, score)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE score = score + VALUES(score)
    ");
    foreach ($termes as $t) {
        $stmtP->execute([$idUser, $t['terme'], $t['type'], $t['points']]);
    }
}

echo "OK";
?>