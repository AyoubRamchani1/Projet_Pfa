<?php
session_start();
include '../../configuration_base.php';

if (!isset($_SESSION['email'])) {
    header("Location: ../view/login.html");
    exit();
}
include "../../controller/historique.php";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mon Historique – Cine Safra</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <link rel="stylesheet" href="historique.css">
  
</head>
<body>

<!-- TOPBAR -->
<nav class="topbar">
  <div class="topbar-logo">C</div>
    <button class="nav-btn btn-back" onclick="window.location.href='/Projet PFA/home page/parametres.php'">
        <i class="fas fa-arrow-left"></i> Retour
    </button>
  </button>
</nav>

<!-- PAGE HEADER -->
<div class="page-header">
  <div class="container" style="padding-top:0;padding-bottom:0">
    <p>Mon compte</p>
    <h1>Mon <span>Historique</span></h1>
    <small><?= count($history) ?> film<?= count($history) > 1 ? 's' : '' ?> regardé<?= count($history) > 1 ? 's' : '' ?></small>
  </div>
</div>

<!-- CONTENU -->
<div class="container">

  <?php if (empty($history)): ?>
    <div class="empty">
      <i class="fas fa-film"></i>
      <p>Vous n'avez encore regardé aucun film.</p>
    </div>

  <?php else: ?>
    <div class="grid">
      <?php foreach ($history as $h): ?>
        <a class="card" href="../description.php?id=<?= $h['id_film'] ?>">
          <div class="poster">🎬</div>
          <div class="card-body">
            <div class="film-title"><?= htmlspecialchars($h['title']) ?></div>
            <div class="film-date">
              <i class="fas fa-clock"></i>
              Vu le <?= date("d/m/Y à H:i", strtotime($h['date_de_regard'])) ?>
            </div>
            <span class="film-badge">Regardé</span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

</body>
</html>