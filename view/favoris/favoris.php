<?php
session_start();
include '../../configuration_base.php';

if (!isset($_SESSION['email'])) {
    header("Location: ../login.html");
    exit();
}

/* USER */
$email = $_SESSION['email'];

$stmt = $cnx->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

/* FAVORIS */
$sql = "
SELECT f.*, m.title
FROM favoris f
JOIN movies m ON m.movie_id = f.id_film
WHERE f.id_user = ?
ORDER BY f.created_at DESC
";
$stmt = $cnx->prepare($sql);
$stmt->execute([$user['id']]);
$favoris = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Mes Favoris</title>
  <link rel="stylesheet" href="favoris.css">
  <script src="favoris.js" defer></script>
</head>
<body>
<div class="bg-grid"></div>
<div class="bg-orb-1"></div>
<div class="bg-orb-2"></div>
<div class="bg-orb-3"></div>
<div class="stars" id="stars"></div>
<div class="container">
<nav class="topbar">
  <div class="topbar-logo"></div>
  <button class="nav-btn btn-back" onclick="window.location.href='/Projet PFA/home page/parametres.php'">
        <i class="fas fa-arrow-left"></i> Retour
  </button>
</nav>
  <h1>⭐ Mes Favoris</h1>

  <?php if (empty($favoris)) : ?>
      <div class="empty">Aucun film en favori</div>
  <?php else: ?>

      <div class="grid">

          <?php foreach ($favoris as $f): ?>
              <div class="card">
                  <div class="poster">⭐</div>

                  <div class="info"><div class="film-title"><?= htmlspecialchars($f['title']) ?></div>
                     >
                      <div class="date">
                          Ajouté le : <?= date("d/m/Y H:i", strtotime($f['created_at'])) ?>
                      </div>
                  </div>
              </div>
          <?php endforeach; ?>

      </div>

  <?php endif; ?>

</div>

</body>
</html>