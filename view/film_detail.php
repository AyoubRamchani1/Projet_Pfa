<?php
// film_detail.php
include("../configuration_base.php");

// 1. Récupérer le film depuis TA base
// Dans film_detail.php
// 1. Récupérer le film depuis TA base avec TON id
$movie_id = intval($_GET['id']);
$stmt = $cnx->prepare("SELECT * FROM movies WHERE movie_id = ?");
$stmt->execute([$movie_id]);
$film = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. Chercher sur TMDB avec le TITRE (pas l'ID)
$apiKey = '8a72ef51b2d102fe9d08edcbba8a3ef3';
$title = urlencode($film['title']);
$tmdbSearch = file_get_contents(
    "https://api.themoviedb.org/3/search/movie?api_key=$apiKey&query=$title&language=fr-FR"
);
$tmdbData = json_decode($tmdbSearch, true);

// 3. Prendre le premier résultat TMDB
$tmdbFilm = $tmdbData['results'][0] ?? null;
$tmdbId = $tmdbFilm['id'] ?? null;  // ← ID TMDB récupéré via le titre

// 4. Maintenant utiliser l'ID TMDB pour affiche + trailer
if ($tmdbId) {
    $details = file_get_contents(
        "https://api.themoviedb.org/3/movie/$tmdbId?api_key=$apiKey&language=fr-FR"
    );
    $tmdbDetails = json_decode($details, true);

    $videos = file_get_contents(
        "https://api.themoviedb.org/3/movie/$tmdbId/videos?api_key=$apiKey"
    );
    $videosData = json_decode($videos, true);

    $trailer = null;
    foreach ($videosData['results'] as $video) {
        if ($video['type'] === 'Trailer' && $video['site'] === 'YouTube') {
            $trailer = $video['key'];
            break;
        }
    }
}

$poster = ($tmdbFilm && $tmdbFilm['poster_path'])
    ? 'https://image.tmdb.org/t/p/w500' . $tmdbFilm['poster_path']
    : 'default_poster.jpg';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($film['title']) ?></title>
    <link rel="stylesheet" href="style2.css">
    <style>
        .film-detail {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
            display: flex;
            gap: 40px;
        }
        .film-poster img {
            width: 300px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .film-info h1 {
            color: var(--primary);
            font-size: 28px;
            margin-bottom: 10px;
        }
        .film-info p {
            color: var(--text);
            margin: 8px 0;
            line-height: 1.6;
        }
        .trailer-container {
            margin-top: 30px;
        }
        .trailer-container iframe {
            width: 100%;
            height: 400px;
            border-radius: 12px;
            border: none;
        }
        .badge-tmdb {
            background: rgba(245,197,24,0.2);
            color: var(--primary);
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 13px;
            margin-right: 8px;
        }
        .back-btn {
            display: inline-block;
            margin: 20px;
            padding: 10px 20px;
            background: var(--primary);
            color: var(--dark);
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
<div class="app">

    <a href="javascript:history.back()" class="back-btn">← Retour</a>

    <div class="film-detail">

        <!-- AFFICHE -->
        <div class="film-poster">
            <img src="<?= $poster ?>" 
                 alt="<?= htmlspecialchars($film['title']) ?>"
                 onerror="this.src='default_poster.jpg'">
        </div>

        <!-- INFOS -->
        <div class="film-info">
            <h1><?= htmlspecialchars($film['title']) ?></h1>

            <!-- Données de TA base -->
            <p><strong>Réalisateur :</strong> <?= htmlspecialchars($film['directeur']) ?></p>
            <p><strong>Acteurs :</strong> <?= htmlspecialchars($film['acteurs']) ?></p>
            <p><strong>Genre :</strong> <?= htmlspecialchars($film['genres']) ?></p>
            <p><strong>Langue :</strong> <?= htmlspecialchars($film['original_language']) ?></p>
            <p><strong>Note :</strong> ⭐ <?= htmlspecialchars($film['vote_average']) ?>/10</p>

            <!-- Données enrichies TMDB -->
            <?php if ($tmdbDetails): ?>
                <p><strong>Date de sortie :</strong> <?= $tmdbDetails['release_date'] ?></p>
                <p><strong>Durée :</strong> <?= $tmdbDetails['runtime'] ?> min</p>
                <p><strong>Budget :</strong> $<?= number_format($tmdbDetails['budget']) ?></p>
                <div style="margin-top:10px;">
                    <?php foreach ($tmdbDetails['genres'] as $g): ?>
                        <span class="badge-tmdb"><?= $g['name'] ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <p style="margin-top:15px; color:var(--muted); line-height:1.7;">
                <?= htmlspecialchars($film['overview']) ?>
            </p>
        </div>
    </div>

    <!-- BANDE-ANNONCE -->
    <?php if ($trailer): ?>
    <div class="trailer-container" style="max-width:1000px; margin:0 auto 40px; padding:0 20px;">
        <h2 style="color:var(--primary); margin-bottom:15px;">🎬 Bande-annonce</h2>
        <iframe src="https://www.youtube.com/embed/<?= $trailer ?>"
                allowfullscreen>
        </iframe>
    </div>
    <?php endif; ?>

</div>
</body>
</html>