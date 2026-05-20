<?php
session_start();
if (!isset($_SESSION['email'])) {
    header('Location: ../view/login.html');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Paramètres</title>
  <link rel="stylesheet" href="parametre.css">
  <script src="parametre.js" defer></script>
</head>
<body>

<!-- Topbar -->
<nav class="topbar">
      <span class="topbar-n">C</span>
      <button class="nav-btn" onclick="goToPage('home.php'); setActive(this)">
        <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="1.8" viewBox="0 0 24 24">
          <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
          <polyline points="9 22 9 12 15 12 15 22"/>
        </svg>
        <span>Home</span>
      </button>
      <button class="nav-btn" onclick="goToPage('recherche.php'); setActive(this)">
    <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="1.8" viewBox="0 0 24 24">
        <circle cx="11" cy="11" r="8"/>
        <path d="m21 21-4.35-4.35"/>
    </svg>
    <span>Recherche Intelligente</span>
</button>
       <button class="nav-btn active" onclick="goToPage('parametres.php'); setActive(this)">
        <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="1.8" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="3"/>
          <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
        </svg>
        <span>Paramètres</span>
      </button>
  </nav>

<!-- Contenu -->
<div class="main">
  <h1>Paramètres</h1>

  <!-- Profil -->
  <div class="section">
    <div class="section-title">Profil</div>
    <div class="avatar-row">
    <div class="avatar"><?= strtoupper(substr($_SESSION['name'],0,1)); ?></div>      
  <div class="avatar-info">
        <div class="avatar-name"><?= htmlspecialchars($_SESSION['name']); ?></div>
        <div class="avatar-email"><?= htmlspecialchars($_SESSION['email']); ?></div>
      </div>
    </div>
  </div>

  <!-- Préférences -->
  <div class="section">
    <div class="section-title">Préférences</div>

    <div class="setting-row">
      <div class="setting-info">
        <div class="setting-label">Notifications</div>
        <div class="setting-desc">Recevoir des alertes nouveautés</div>
      </div>
      <label class="toggle">
        <input type="checkbox" checked onchange="toast('Notifications ' + (this.checked ? 'activées' : 'désactivées'))">
        <span class="slider"></span>
      </label>
    </div>

    <div class="setting-row">
      <div class="setting-info">
        <div class="setting-label">Lecture automatique</div>
        <div class="setting-desc">Lancer la bande-annonce auto</div>
      </div>
      <label class="toggle">
        <input type="checkbox" onchange="toast('Lecture auto ' + (this.checked ? 'activée' : 'désactivée'))">
        <span class="slider"></span>
      </label>
    </div>

    <div class="setting-row">
      <div class="setting-info">
        <div class="setting-label">Langue</div>
        <div class="setting-desc">Langue de l'interface</div>
      </div>
      <select class="setting-select" onchange="toast('Langue changée')">
        <option>Français</option>
        <option>English</option>
        <option>العربية</option>
      </select>
    </div>

    <div class="setting-row">
      <div class="setting-info">
        <div class="setting-label">Qualité vidéo</div>
        <div class="setting-desc">Résolution par défaut</div>
      </div>
      <select class="setting-select" onchange="toast('Qualité mise à jour')">
        <option>Auto</option>
        <option>1080p</option>
        <option>720p</option>
        <option>480p</option>
      </select>
    </div>
  </div>


<!-- Modifier nom + mot de passe -->
<div class="section">
  <div class="section-title">Modifier le profil</div>

  <form method="POST" class="form-body" action="../controller/parametre.php">

    <!-- NOM -->
    <div class="form-group">
      <label>Nom</label>
      <input type="text" name="name"
             value="<?= htmlspecialchars($_SESSION['name'] ?? '') ?>" required>
    </div>

    <hr style="border:1px solid #333; margin:15px 0;">

    <!-- MOT DE PASSE ACTUEL -->
    <div class="form-group">
      <label>Mot de passe actuel</label>
      <input type="password" name="current_password" required>
    </div>

    <!-- NOUVEAU MOT DE PASSE -->
    <div class="form-row">
      <div class="form-group">
        <label>Nouveau mot de passe</label>
        <input type="password" name="new_password" required>
      </div>

      <div class="form-group">
        <label>Confirmer</label>
        <input type="password" name="confirm_password" required>
      </div>
    </div>

    <button type="submit" name="update_all" class="btn btn-primary">
      Enregistrer
    </button>

  </form>
</div>


  <!-- Historique -->
<div class="section">
  <div class="section-title">Historique</div>

  <div class="setting-row">
    <div class="setting-info">
      <div class="setting-label">Historique de visionnage</div>
      <div class="setting-desc">Consulter vos films et séries récemment regardés</div>
    </div>

    <button class="btn-history" onclick="window.location.href='/Projet PFA/view/historique/historique.php'">
      Voir
    </button>
  </div>
</div>

<!-- Favoris -->
<div class="section">
  <div class="section-title">Liste des favoris</div>

  <div class="setting-row">
    <div class="setting-info">
      <div class="setting-label">Mes contenus favoris</div>
      <div class="setting-desc">Accéder à vos films et séries enregistrés</div>
    </div>

    <button class="btn-favoris" onclick="window.location.href='../view/favoris/favoris.php'">
      Ouvrir
    </button>
  </div>
</div>

  <!-- Compte -->
  <div class="section">
    <div class="section-title">Compte</div>
    <div class="setting-row">
      <div class="setting-info">
        <div class="setting-label">Mode sombre</div>
        <div class="setting-desc">Interface sombre activée</div>
      </div>
      <label class="toggle">
        <input type="checkbox" checked onchange="toast('Thème modifié')">
        <span class="slider"></span>
      </label>
    </div>
    <button class="btn-danger" onclick="window.location.href='../view/logout.php'">Se déconnecter</button>
  </div>

</div>



<!-- Toast -->
<div class="toast" id="toastMsg"></div>



</body>
</html>