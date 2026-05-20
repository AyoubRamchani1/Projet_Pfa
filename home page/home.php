<?php
include("../configuration_base.php");
session_start();

if (isset($_GET['ajax_search'])) {
    header('Content-Type: application/json');
    $q = trim($_GET['ajax_search']);
    if (strlen($q) < 2) { echo json_encode([]); exit; }
    $stmt = $cnx->prepare("
        SELECT movie_id, title, genres, vote_average
        FROM movies
        WHERE title LIKE ?
        ORDER BY vote_average DESC
        LIMIT 8
    ");
    $stmt->execute(['%' . $q . '%']);
    echo json_encode($stmt->fetchAll());
    exit;
}
// ============================================================
//  SYSTÈME DE RECOMMANDATION TF-IDF + SIMILARITÉ COSINUS
//  À intégrer dans home.php en remplacement du bloc existant
//  "Recommandations personnalisées avec cache"
// ============================================================
 
// ── 1. RÉCUPÉRER L'UTILISATEUR (identique à ton code actuel) ──
$recommendations = [];
$apiKey = '8a72ef51b2d102fe9d08edcbba8a3ef3';
require_once '../controller/tmdb_cache.php';
 
$userId = null;
if (isset($_SESSION['email'])) {
    $stmtU = $cnx->prepare("SELECT id FROM users WHERE email = ?");
    $stmtU->execute([$_SESSION['email']]);
    $userId = $stmtU->fetchColumn();
} elseif (isset($_SESSION['name'])) {
    $stmtU = $cnx->prepare("SELECT id FROM users WHERE name = ?");
    $stmtU->execute([$_SESSION['name']]);
    $userId = $stmtU->fetchColumn();
}
 
if ($userId) {
 
    // ── 2. CHARGER LE PROFIL UTILISATEUR ──
    // On récupère tous les termes du profil avec leur score
    // Ex: Drama→16, Comedy→3.5, "Robert Zemeckis"→3
    $stmtP = $cnx->prepare("
        SELECT terme, score 
        FROM user_profile 
        WHERE user_id = ? 
        ORDER BY score DESC
    ");
    $stmtP->execute([$userId]);
    $profileRaw = $stmtP->fetchAll(PDO::FETCH_KEY_PAIR); // [terme => score]
 
    if (!empty($profileRaw)) {
 
        // ── 3. NORMALISER LE VECTEUR PROFIL ──
        // On divise chaque score par la norme du vecteur
        // ||profil|| = sqrt(sum(score²))
        // Pourquoi ? Pour que la similarité cosinus soit entre 0 et 1
        $normeProfile = sqrt(array_sum(array_map(fn($s) => $s * $s, $profileRaw)));
        $profileVec = [];
        foreach ($profileRaw as $terme => $score) {
            $profileVec[strtolower($terme)] = ($normeProfile > 0) ? $score / $normeProfile : 0;
        }
 
        // ── 4. CHARGER TOUS LES FILMS ──
        // On récupère les colonnes utiles pour construire le document TF-IDF
        // Récupérer les films déjà regardés par l'utilisateur
$stmtVus = $cnx->prepare("
    SELECT DISTINCT id_film FROM historique WHERE id_user = ?
");
$stmtVus->execute([$userId]);
$filmsVus = $stmtVus->fetchAll(PDO::FETCH_COLUMN); // [11, 238, 557, ...]

// Exclure aussi les favoris (optionnel mais logique)
$stmtFavs = $cnx->prepare("
    SELECT DISTINCT id_film FROM favoris WHERE id_user = ?
");
$stmtFavs->execute([$userId]);
$filmsFavoris = $stmtFavs->fetchAll(PDO::FETCH_COLUMN);

// Fusionner les deux listes
$filmsExclus = array_unique(array_merge($filmsVus, $filmsFavoris));

// Construire le filtre SQL dynamiquement
if (!empty($filmsExclus)) {
    $placeholders = implode(',', array_fill(0, count($filmsExclus), '?'));
    $stmtMovies = $cnx->prepare("
        SELECT movie_id, title, genres, vote_average, directeur, acteurs, tags
        FROM movies
        WHERE movie_id NOT IN ($placeholders)
        LIMIT 2000
    ");
    $stmtMovies->execute($filmsExclus);
} else {
    $stmtMovies = $cnx->query("
        SELECT movie_id, title, genres, vote_average, directeur, acteurs, tags
        FROM movies
        LIMIT 2000
    ");
}
$allMovies = $stmtMovies->fetchAll(PDO::FETCH_ASSOC);
        // ── 5. CALCULER IDF POUR CHAQUE TERME DU PROFIL ──
        // IDF(terme) = log(N / df(terme))
        // N = nombre total de films
        // df(terme) = nombre de films contenant ce terme
        // Pourquoi IDF ? Un terme rare est plus discriminant qu'un terme commun
        $N = count($allMovies);
        $df = []; // document frequency par terme
 
        foreach ($allMovies as $film) {
            // Construire le "document" du film : tags + genres + directeur + acteurs
            $doc = strtolower(
                $film['tags'] . ' ' .
                $film['genres'] . ' ' .
                $film['directeur'] . ' ' .
                $film['acteurs']
            );
 
            // Pour chaque terme du profil, vérifier s'il apparaît dans ce film
            foreach ($profileVec as $terme => $_) {
                if (strpos($doc, $terme) !== false) {
                    $df[$terme] = ($df[$terme] ?? 0) + 1;
                }
            }
        }
 
        // Calculer IDF pour chaque terme
        $idf = [];
        foreach ($profileVec as $terme => $_) {
            $docFreq = $df[$terme] ?? 0;
            // +1 pour éviter la division par zéro (lissage de Laplace)
            $idf[$terme] = log(($N + 1) / ($docFreq + 1)) + 1;
        }
 
        // ── 6. CALCULER SCORE COSINUS POUR CHAQUE FILM ──
        $scores = [];
 
        foreach ($allMovies as $film) {
            // Construire le document du film
            $doc = strtolower(
                $film['tags'] . ' ' .
                $film['genres'] . ' ' .
                $film['directeur'] . ' ' .
                $film['acteurs']
            );
 
            // Tokeniser le document en mots
            $mots = preg_split('/[\s,]+/', $doc);
            $totalMots = count($mots);
            if ($totalMots === 0) continue;
 
            // ── CALCUL TF pour chaque terme du profil ──
            // TF(terme, film) = nb_occurrences(terme) / total_mots
            $filmVec = [];
            $normeFilm = 0;
 
            foreach ($profileVec as $terme => $_) {
                // Compter combien de fois le terme apparaît dans le doc
                $occurrences = substr_count($doc, $terme);
                $tf = $occurrences / $totalMots;
 
                // TF-IDF = TF × IDF
                $tfidf = $tf * ($idf[$terme] ?? 1);
                $filmVec[$terme] = $tfidf;
                $normeFilm += $tfidf * $tfidf;
            }
 
            $normeFilm = sqrt($normeFilm);
            if ($normeFilm == 0) continue; // Film sans aucun terme du profil
 
            // ── PRODUIT SCALAIRE : Σ(profil[t] × film[t]) ──
            $produitScalaire = 0;
            foreach ($profileVec as $terme => $pScore) {
                $produitScalaire += $pScore * ($filmVec[$terme] / $normeFilm);
            }
 
            // cos(θ) = produit_scalaire / (||profil|| × ||film||)
            // Note: ||profil|| est déjà normalisé à 1 (voir étape 3)
            // Donc cos(θ) = produit_scalaire / ||film||
            // (la division par normeFilm est déjà faite dans la boucle ci-dessus)
            $cosinusSimilarity = $produitScalaire;
 
            // ── SCORE FINAL = 70% cosinus + 30% note générale ──
            // La note vote_average est sur 10, on la normalise sur 1
            $scoreFinal = (0.7 * $cosinusSimilarity) + (0.3 * ($film['vote_average'] / 10));
 
            if ($scoreFinal > 0) {
                $scores[] = [
                    'movie_id'     => $film['movie_id'],
                    'title'        => $film['title'],
                    'genres'       => $film['genres'],
                    'vote_average' => $film['vote_average'],
                    'cosinus'      => round($cosinusSimilarity, 4),
                    'score_final'  => round($scoreFinal, 4),
                ];
            }
        }
 
        // ── 7. TRIER PAR SCORE FINAL DÉCROISSANT ──
        usort($scores, fn($a, $b) => $b['score_final'] <=> $a['score_final']);
 
        // ── 8. PRENDRE LES 8 MEILLEURS + RÉCUPÉRER LES POSTERS TMDB ──
        $top8 = array_slice($scores, 0, 8);
 
        foreach ($top8 as &$r) {
            $tmdb = getTmdbPoster($r['title'], $apiKey);
            $r['poster'] = $tmdb['poster'];
        }
        unset($r);
 
        $recommendations = $top8;
    }
}
?>
 
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>cine safra</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app">
  <nav class="topbar">
      <span class="topbar-n">C</span>
      <button class="nav-btn active" onclick="goToPage('home.php'); setActive(this)">
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
      <button class="nav-btn" onclick="goToPage('parametres.php'); setActive(this)">
        <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="1.8" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="3"/>
          <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
        </svg>
        <span>Paramètres</span>
      </button>
  </nav>

    <div class="search-wrapper" id="searchWrapper">
      <div class="search-box">
        <svg width="16" height="16" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"/>
          <path d="m21 21-4.35-4.35"/>
        </svg>
        <input
          type="text"
          id="searchInput"
          placeholder="Rechercher rapide..."
          autocomplete="off"
          oninput="liveSearch(this.value)"
        />
      </div>
      <div class="search-dropdown" id="searchDropdown"></div>
    </div>

  <div class="main">
    <div class="hero">
      <div class="hero-bg"></div>
      <div class="hero-overlay-left"></div>
      <div class="hero-overlay-bottom"></div>
      <div class="hero-content">
        <div class="netflix-badge">
          <span class="n-logo">Cine safra</span>
          <span>SERIES</span>
        </div>
        <div class="hero-title">
          game of <span class="heist-red">thrones</span>
        </div>
        <div class="streams">1m+ <span>Streams</span></div>
        <div class="hero-btns">
          <button class="btn-play">Play</button>
          <button class="btn-trailer">Watch Trailer</button>
        </div>
      </div>
    </div>

    <div class="content-area">
      <div class="row-title">New this week</div>
      <div class="scroll-wrapper">
        <div class="scroll-row" id="row1">
          <!-- ✅ onclick → film_detail.php -->
          <div class="card c9"><div class="card-label" onclick="goToDescription(11)">Star Wars</div></div>
          <div class="card c10"><div class="card-label" onclick="goToDescription(13)">Forrest Gump</div><div class="netflix-tag">Cine safra</div></div>
          <div class="card c11"><div class="card-label" onclick="goToDescription(0)">from</div><div class="netflix-tag">Cine safra</div></div>
          <div class="card c12"><div class="card-label" onclick="goToDescription(0)">The Perfection</div><div class="netflix-tag">Cine safra</div></div>
          <div class="card c13"><div class="card-label" onclick="goToDescription(0)">Extraction</div><div class="netflix-tag">Cine safra</div></div>
          <div class="card c14"><div class="card-label" onclick="goToDescription(0)">his and hers</div><div class="netflix-tag">Cine safra</div></div>
        </div>
        <button class="next-arrow" onclick="scroll('row1')">›</button>
      </div>
<?php if (!empty($recommendations)): ?>
<div class="row-title">🎯 Recommandés pour vous</div>
<div class="scroll-wrapper">
    <div class="scroll-row" id="rowReco">
        <?php foreach ($recommendations as $r): ?>
            <div class="card reco-card" onclick="goToDescription(<?= $r['movie_id'] ?>)">
                <?php if (!empty($r['poster'])): ?>
                    <img src="<?= htmlspecialchars($r['poster']) ?>"
                         alt="<?= htmlspecialchars($r['title']) ?>"
                         style="width:100%;height:100%;object-fit:cover;border-radius:12px;">
                <?php endif; ?>
                <div class="card-label"><?= htmlspecialchars($r['title']) ?></div>
                <div class="netflix-tag">⭐ <?= number_format((float)$r['vote_average'], 1) ?></div>
                <!-- Score cosinus visible pour debug (à retirer en production) -->
                <!-- <div style="font-size:10px;color:#aaa">cos: <?= $r['cosinus'] ?></div> -->
            </div>
        <?php endforeach; ?>
    </div>
    <button class="next-arrow" onclick="scroll('rowReco')">›</button>
</div>
<?php endif; ?>
      <div class="row-title">Trending Now</div>
      <div class="scroll-wrapper">
        <div class="scroll-row" id="row2">
          <div class="card c1"><div class="card-label" onclick="goToDescription(0)">prison break</div><div class="netflix-tag">Cine safra</div></div>
          <div class="card c2"><div class="card-label" onclick="goToDescription(0)">la casa de papel</div><div class="netflix-tag">Cine safra</div></div>
          <div class="card c3"><div class="card-label" onclick="goToDescription(0)">Breaking Bad</div><div class="netflix-tag">Cine safra</div></div>
          <div class="card c4"><div class="card-label" onclick="goToDescription(0)">Dark</div><div class="netflix-tag">Cine safra</div></div>
          <div class="card c5"><div class="card-label" onclick="goToDescription(0)">Squid Game</div><div class="netflix-tag">Cine safra</div></div>
          <div class="card c6"><div class="card-label" onclick="goToDescription(0)">outer banks</div><div class="netflix-tag">Cine safra</div></div>
        </div>
        <button class="next-arrow" onclick="scroll('row2')">›</button>
      </div>
    </div>
  </div>
</div>

<div id="message" class="message"></div>

<script>
  function scroll(rowId) {
    const row = document.getElementById(rowId);
    row.scrollBy({ left: 500, behavior: 'smooth' });
  }

  function setActive(btn) {
    document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
  }

  function goToPage(page) { window.location.href = page; }

  // ✅ Redirige vers film_detail.php au lieu de description.php
  // ✅ À remplacer par
function goToDescription(id) {
    if (id === 0) return;
    window.location.href = "../description page/description.php?id=" + id;
}

  // ── Recherche AJAX ──
  const dropdown = document.getElementById('searchDropdown');

  function liveSearch(value) {
    const q = value.trimEnd();
    if (q.trim().length < 2) {
      dropdown.innerHTML = '';
      dropdown.classList.remove('open');
      return;
    }

    fetch('home.php?ajax_search=' + encodeURIComponent(q.trim()))
      .then(r => r.json())
      .then(results => {
        if (results.length === 0) {
          dropdown.innerHTML = `<div class="search-empty">Aucun film trouvé pour "${q.trim()}"</div>`;
        } else {
          dropdown.innerHTML = results.map(r => `
           <a class="search-result-item" href="../description page/description.php?id=${r.movie_id}">
              <div class="search-result-icon">🎬</div>
              <div class="search-result-info">
                <strong>${r.title}</strong>
                <span>${r.genres}</span>
              </div>
              <div class="search-result-rating">★ ${parseFloat(r.vote_average).toFixed(1)}</div>
            </a>
          `).join('');
        }
        dropdown.classList.add('open');
      });
  }

  // Fermer dropdown en cliquant ailleurs
  document.addEventListener('click', function(e) {
    if (!document.getElementById('searchWrapper').contains(e.target)) {
      dropdown.classList.remove('open');
    }
  });

  // Card hover
  document.querySelectorAll('.card').forEach(card => {
    card.addEventListener('mouseenter', () => { card.style.zIndex = '10'; });
    card.addEventListener('mouseleave', () => { card.style.zIndex = ''; });
  });

  // Message depuis URL
  function showMessage(text) {
    const messageBox = document.getElementById('message');
    messageBox.textContent = text;
    messageBox.className = "message show";
    setTimeout(() => { messageBox.classList.remove("show"); }, 3000);
  }
  const urlParams = new URLSearchParams(window.location.search);
  const user = urlParams.get('user');
  if (user) showMessage(decodeURIComponent(user));
</script>
</body>
</html>