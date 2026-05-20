<?php
require_once 'C:/xampp/htdocs/Projet PFA/controller/tmdb_cache.php';

session_start();
require_once '../configuration_base.php';

// ── Utilisateur connecté ───────────────────────────────────────
$userName = $_SESSION['name'] ?? null;

// ── Créer table unifiée reviews ────────────────────────────────
$cnx->exec("
    CREATE TABLE IF NOT EXISTS reviews (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        movie_id    INT NOT NULL,
        movie_title VARCHAR(255) NOT NULL,
        user_name   VARCHAR(100) NOT NULL DEFAULT 'Anonyme',
        note        INT DEFAULT NULL,
        comment     TEXT DEFAULT NULL,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");

// ── Recherche ──────────────────────────────────────────────────
$searchResults = [];
$searchQuery   = trim($_GET['search'] ?? '');

if ($searchQuery !== '') {
    $stmt = $cnx->prepare("
        SELECT movie_id, title, genres, vote_average
        FROM movies
        WHERE title LIKE ?
        ORDER BY vote_average DESC
        LIMIT 8
    ");
    $stmt->execute(['%' . $searchQuery . '%']);
    $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ── Récupérer le film ──────────────────────────────────────────
$movieId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($movieId <= 0) {
    $movie   = $cnx->query("SELECT * FROM movies LIMIT 1")->fetch();
    $movieId = $movie['movie_id'] ?? 0;
} else {
    $stmt = $cnx->prepare("SELECT * FROM movies WHERE movie_id = ?");
    $stmt->execute([$movieId]);
    $movie = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$movie) die("Film introuvable.");

$movieTitle = $movie['title'] ?? '—';

// ── POST note ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['note']) && $userName) {
    $note = (int)$_POST['note'];
    if ($note >= 1 && $note <= 10) {
        $stmtCheck = $cnx->prepare("SELECT id FROM reviews WHERE movie_id = ? AND user_name = ?");
        $stmtCheck->execute([$movieId, $userName]);
        $existing = $stmtCheck->fetch();

        if ($existing) {
            $stmtU = $cnx->prepare("UPDATE reviews SET note = ?, movie_title = ? WHERE movie_id = ? AND user_name = ?");
            $stmtU->execute([$note, $movieTitle, $movieId, $userName]);
        } else {
            $stmtI = $cnx->prepare("INSERT INTO reviews (movie_id, movie_title, user_name, note) VALUES (?, ?, ?, ?)");
            $stmtI->execute([$movieId, $movieTitle, $userName, $note]);
        }
    }
    header("Location: description.php?id=$movieId#noter");
    exit;
}

// ── POST commentaire ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content']) && trim($_POST['content']) !== '' && $userName) {
    $content = trim($_POST['content']);

    $stmtCheck = $cnx->prepare("SELECT id FROM reviews WHERE movie_id = ? AND user_name = ?");
    $stmtCheck->execute([$movieId, $userName]);
    $existing = $stmtCheck->fetch();

    if ($existing) {
        $stmtU = $cnx->prepare("UPDATE reviews SET comment = ?, movie_title = ? WHERE movie_id = ? AND user_name = ?");
        $stmtU->execute([$content, $movieTitle, $movieId, $userName]);
    } else {
        $stmtI = $cnx->prepare("INSERT INTO reviews (movie_id, movie_title, user_name, comment) VALUES (?, ?, ?, ?)");
        $stmtI->execute([$movieId, $movieTitle, $userName, $content]);
    }
    header("Location: description.php?id=$movieId#commentaires");
    exit;
}

// ── Récupérer notes ────────────────────────────────────────────
$stmtNote = $cnx->prepare("SELECT AVG(note) as moyenne, COUNT(*) as total FROM reviews WHERE movie_id = ? AND note IS NOT NULL");
$stmtNote->execute([$movieId]);
$noteData   = $stmtNote->fetch(PDO::FETCH_ASSOC);
$moyenne    = $noteData['total'] > 0 ? round((float)$noteData['moyenne'], 1) : null;
$totalVotes = (int)$noteData['total'];

$myNote = null;
if ($userName) {
    $stmtMyNote = $cnx->prepare("SELECT note FROM reviews WHERE movie_id = ? AND user_name = ?");
    $stmtMyNote->execute([$movieId, $userName]);
    $myNote = $stmtMyNote->fetchColumn() ?: null;
}

// ── Commentaires ───────────────────────────────────────────────
$stmt = $cnx->prepare("SELECT user_name, comment FROM reviews WHERE movie_id = ? AND comment IS NOT NULL ORDER BY updated_at DESC");
$stmt->execute([$movieId]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Suggestions intelligentes (Graphe de similarité) ──────────
$genres_film  = array_filter(explode(' ', trim($movie['genres'] ?? '')));
$director_film = $movie['directeur'] ?? '';
$actors_film   = $movie['acteurs']   ?? '';

// Score pondéré : réalisateur (3pts) + genre (2pts) + acteur (1pt)
$stmt = $cnx->prepare("
    SELECT 
    m.movie_id, m.title, m.genres, m.vote_average, m.popularity, m.directeur,
        (
            CASE WHEN m.directeur = :dir AND m.directeur != '' THEN 3 ELSE 0 END +
            CASE WHEN m.genres LIKE :g1 THEN 2 ELSE 0 END +
            CASE WHEN m.genres LIKE :g2 THEN 1 ELSE 0 END +
            CASE WHEN m.acteurs LIKE :act THEN 1 ELSE 0 END
        ) AS similarity_score
    FROM movies m
    WHERE m.movie_id != :id
    HAVING similarity_score > 0
    ORDER BY similarity_score DESC, m.vote_average DESC
    LIMIT 8
");
$stmt->execute([
    ':id'  => $movieId,
    ':dir' => $director_film,
    ':g1'  => '%' . ($genres_film[0] ?? '') . '%',
    ':g2'  => '%' . ($genres_film[1] ?? '') . '%',
    ':act' => '%' . explode(' ', trim($actors_film))[0] . '%',
]);
$suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
// ── Variables ──────────────────────────────────────────────────
$title    = htmlspecialchars($movie['title']             ?? '—');
$overview = htmlspecialchars($movie['overview']          ?? '—');
$genres   = htmlspecialchars($movie['genres']            ?? '—');
$rating   = number_format((float)($movie['vote_average'] ?? 0), 1);
$lang     = strtoupper(htmlspecialchars($movie['original_language'] ?? '—'));
$director = htmlspecialchars($movie['directeur']         ?? '—');
$cast     = htmlspecialchars($movie['acteurs']           ?? '—');
$popularity = number_format((float)($movie['popularity']   ?? 0), 1);

// ── TMDB API ───────────────────────────────────────────────────
// ── TMDB API avec cache ────────────────────────────────────────
$apiKey      = '8a72ef51b2d102fe9d08edcbba8a3ef3';
$tmdbDetails = null;
$trailer     = null;
$poster      = null;
$tmdbId      = null;

require_once 'C:/xampp/htdocs/Projet PFA/controller/tmdb_cache.php';
$tmdb   = getTmdbPoster($movie['title'], $apiKey);
$poster = $tmdb['poster'];
$tmdbId = $tmdb['tmdb_id'];

// Détails + trailer (aussi mis en cache)
if ($tmdbId) {
    $cacheDir      = __DIR__ . '/../cache/';
    $cacheDetails  = $cacheDir . 'details_' . $tmdbId . '.json';
    $cacheTrailer  = $cacheDir . 'trailer_' . $tmdbId . '.json';

    // Détails
    if (file_exists($cacheDetails) && (time() - filemtime($cacheDetails)) < 2592000) {
        $tmdbDetails = json_decode(file_get_contents($cacheDetails), true);
    } else {
        $detailsJson = @file_get_contents("https://api.themoviedb.org/3/movie/$tmdbId?api_key=$apiKey&language=fr-FR");
        if ($detailsJson) {
            $tmdbDetails = json_decode($detailsJson, true);
            file_put_contents($cacheDetails, $detailsJson);
        }
    }

    // Trailer
    if (file_exists($cacheTrailer) && (time() - filemtime($cacheTrailer)) < 2592000) {
        $trailer = json_decode(file_get_contents($cacheTrailer), true)['key'];
    } else {
        $videosJson = @file_get_contents("https://api.themoviedb.org/3/movie/$tmdbId/videos?api_key=$apiKey");
        if ($videosJson) {
            $videos = json_decode($videosJson, true);
            foreach ($videos['results'] ?? [] as $video) {
                if ($video['type'] === 'Trailer' && $video['site'] === 'YouTube') {
                    $trailer = $video['key'];
                    file_put_contents($cacheTrailer, json_encode(['key' => $trailer]));
                    break;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Cine Safra | <?= $title ?></title>
  <link rel="stylesheet" href="description.css" />
  <style>

  </style>
</head>
<body>
<div class="app">
  <nav class="topbar">
    <span class="topbar-n">C</span>
    <button class="nav-btn" onclick="goToPage('../home page/home.php'); setActive(this)">
      <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="1.8" viewBox="0 0 24 24">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
        <polyline points="9 22 9 12 15 12 15 22"/>
      </svg>
      <span>Home</span>
    </button>
    <button class="nav-btn" onclick="goToPage('../home page/recherche.php'); setActive(this)">
      <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="1.8" viewBox="0 0 24 24">
        <circle cx="11" cy="11" r="8"/>
        <path d="m21 21-4.35-4.35"/>
      </svg>
      <span>Recherche Intelligente</span>
    </button>
    <button class="nav-btn" onclick="goToPage('../home page/parametres.php'); setActive(this)">
      <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="1.8" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="3"/>
        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
      </svg>
      <span>Paramètres</span>
    </button>

    <div class="search-wrapper">
      <form method="GET" action="description.php" onsubmit="return false;">
        <div class="search-box">
          <svg width="16" height="16" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="11" cy="11" r="8"/>
            <path d="m21 21-4.35-4.35"/>
          </svg>
          <input type="text" name="search" id="searchInput"
            placeholder="Rechercher un film..." autocomplete="off"
            value="<?= htmlspecialchars($searchQuery) ?>"
            oninput="liveSearch(this.value)" />
        </div>
      </form>

      <?php if ($searchQuery !== ''): ?>
        <div class="search-dropdown" id="searchDropdown">
          <?php if (empty($searchResults)): ?>
            <div class="search-empty">Aucun film trouvé pour "<?= htmlspecialchars($searchQuery) ?>"</div>
          <?php else: ?>
            <?php foreach ($searchResults as $r): ?>
              <a class="search-result-item" href="description.php?id=<?= $r['movie_id'] ?>">
                <div class="search-result-icon">🎬</div>
                <div class="search-result-info">
                  <strong><?= htmlspecialchars($r['title']) ?></strong>
                  <span><?= htmlspecialchars($r['genres']) ?></span>
                </div>
                <div class="search-result-rating">★ <?= number_format((float)$r['vote_average'], 1) ?></div>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </nav>

  <main class="main">

    <section class="movie-detail">

      <!-- ✅ AFFICHE TMDB avec fallback image locale -->
      <div class="poster-box">
        <?php if ($poster): ?>
          <img src="<?= $poster ?>"
               alt="<?= $title ?>"
               onerror="this.src='/Projet PFA/description page/image/<?= $movieId ?>.jpg'">
        <?php else: ?>
          <img src="/Projet PFA/description page/image/<?= $movieId ?>.jpg"
               alt="<?= $title ?>">
        <?php endif; ?>
      </div>

      <div class="movie-info">
        <div class="movie-header">
          <div>
            <p class="label">Description du film</p>
            <h1><?= $title ?></h1>
            <p class="movie-subtitle"><?= $genres ?></p>
          </div>
          <div class="movie-score"><?= $rating ?></div>
        </div>

        <p class="movie-description"><?= $overview ?></p>

        <div class="action-buttons">
          <?php if ($tmdbId): ?>
  <button class="btn btn-watch" onclick="watchAndOpen(<?= (int)$movieId ?>, <?= (int)$tmdbId ?>)">
    ▶ Regarder ce film
  </button>
<?php endif; ?>
          <button class="btn btn-comment" onclick="document.getElementById('commentContent').focus()">
            Ajouter un commentaire
          </button>
          <button class="btn btn-favorite" onclick="addFavorite(<?= $movieId ?>)">
            ❤️ Ajouter aux favoris
          </button>
        </div>

        <!-- ✅ DETAIL GRID enrichi avec TMDB -->
        <div class="detail-grid">
          <div><span>Réalisateur</span><strong><?= $director ?></strong></div>
          <div><span>Genres</span><strong><?= $genres ?></strong></div>
          <div><span>Langue</span><strong><?= $lang ?></strong></div>
          <div><span>Acteurs</span><strong><?= $cast ?></strong></div>
          <?php if ($tmdbDetails): ?>
            <div><span>Date de sortie</span><strong><?= $tmdbDetails['release_date'] ?? '—' ?></strong></div>
            <div><span>Durée</span><strong><?= ($tmdbDetails['runtime'] ?? 0) ?> min</strong></div>
            <div><span>Popularité</span><strong><?= $popularity ?></strong>M</div>
            <div><span>Budget</span><strong>$<?= number_format($tmdbDetails['budget'] ?? 0) ?></strong></div>
          <?php endif; ?>
        </div>

        <!-- ── SECTION NOTATION ── -->
        <div class="rating-section" id="noter">
          <h3>⭐ Noter ce film</h3>
          <div class="rating-avg">
            <?php if ($moyenne !== null): ?>
              <div class="big-score"><?= $moyenne ?></div>
              <div style="display:flex;flex-direction:column;gap:2px">
                <div class="score-info">
                  <strong>/ 10</strong>
                  <?= $totalVotes ?> vote<?= $totalVotes > 1 ? 's' : '' ?>
                </div>
              </div>
            <?php else: ?>
              <div class="score-info" style="color:var(--muted,#aaa)">Aucune note pour l'instant. Soyez le premier !</div>
            <?php endif; ?>
          </div>

          <?php if ($userName): ?>
            <form method="POST" action="description.php?id=<?= $movieId ?>#noter" style="margin-top:1.2rem">
              <div class="stars-form">
                <p><?= $myNote ? "Votre note actuelle : <strong style='color:#facc15'>$myNote/10</strong> — vous pouvez la modifier :" : "Donnez votre note :" ?></p>
                <div class="stars">
                  <?php for ($i = 10; $i >= 1; $i--): ?>
                    <input type="radio" name="note" id="star<?= $i ?>" value="<?= $i ?>"
                      <?= ($myNote == $i) ? 'checked' : '' ?>>
                    <label for="star<?= $i ?>" title="<?= $i ?>/10">★</label>
                  <?php endfor; ?>
                </div>
                <button type="submit" class="btn-rate">Valider ma note</button>
              </div>
              <?php if ($myNote): ?>
                <div class="my-note-badge">✓ Vous avez noté <?= $myNote ?>/10</div>
              <?php endif; ?>
            </form>
          <?php else: ?>
            <div class="login-alert">
              ⚠️ <span>Connectez-vous pour noter ce film. <a href="../view/login.html">Se connecter</a></span>
            </div>
          <?php endif; ?>
        </div>

      </div>
    </section>

    <!-- ✅ BANDE-ANNONCE YOUTUBE -->
    <?php if ($trailer): ?>
    <div class="trailer-section">
      <h2>🎬 Bande-annonce</h2>
      <iframe src="https://www.youtube.com/embed/<?= $trailer ?>"
              allowfullscreen>
      </iframe>
    </div>
    <?php endif; ?>

    <section class="extras">

      <div class="section-card" id="commentaires">
        <div class="section-title">
          <h2>Commentaires</h2>
          <span><?= count($comments) ?> commentaire<?= count($comments) > 1 ? 's' : '' ?></span>
        </div>
        <div class="comments-list">
          <?php if (empty($comments)): ?>
            <p style="color:var(--muted)">Aucun commentaire. Soyez le premier !</p>
          <?php else: ?>
            <?php foreach ($comments as $c): ?>
              <div class="comment-card">
                <strong><?= htmlspecialchars($c['user_name']) ?></strong>
                <p><?= htmlspecialchars($c['comment']) ?></p>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <?php if ($userName): ?>
          <form method="POST" action="description.php?id=<?= $movieId ?>" class="comment-form">
            <textarea id="commentContent" name="content"
              placeholder="Écrire un commentaire en tant que <?= htmlspecialchars($userName) ?>..."
              rows="4" required></textarea>
            <button class="btn btn-submit" type="submit">Envoyer</button>
          </form>
        <?php else: ?>
          <div class="login-alert" style="margin-top:1rem">
            ⚠️ <span>Connectez-vous pour laisser un commentaire. <a href="../view/login.html">Se connecter</a></span>
          </div>
        <?php endif; ?>
      </div>

      <div class="section-card suggestions-section">
  <div class="section-title">
    <div>
      <h2>🎯 Vous aimerez aussi</h2>
      <p>Basé sur le genre, réalisateur et acteurs</p>
    </div>
  </div>

  <div class="suggestions-grid">
    <?php if (empty($suggestions)): ?>
      <p style="color:var(--muted)">Aucune suggestion disponible.</p>
    <?php else: ?>
      <?php foreach ($suggestions as $s): ?>
        <?php
          // Affiche TMDB pour chaque suggestion
          $sTitleEncoded = urlencode($s['title']);
          $sTmdb = @file_get_contents("https://api.themoviedb.org/3/search/movie?api_key=$apiKey&query=$sTitleEncoded&language=fr-FR");
          $sPoster = null;
          if ($sTmdb) {
              $sTmdbData = json_decode($sTmdb, true);
              $sTmdbFilm = $sTmdbData['results'][0] ?? null;
              if ($sTmdbFilm && $sTmdbFilm['poster_path']) {
                  $sPoster = 'https://image.tmdb.org/t/p/w300' . $sTmdbFilm['poster_path'];
              }
          }
          // Raison de suggestion
          $raison = [];
          if (trim($s['genres']) && strpos($s['genres'], $genres_film[0] ?? '') !== false) $raison[] = '🎭 ' . ($genres_film[0] ?? '');
          if ($s['directeur'] === $director_film && $director_film) $raison[] = '🎬 ' . $director_film;
          $raisonText = implode(' · ', array_slice($raison, 0, 2));
        ?>
        <a href="description.php?id=<?= $s['movie_id'] ?>" class="sug-card">
          <div class="sug-poster">
            <?php if ($sPoster): ?>
              <img src="<?= $sPoster ?>" alt="<?= htmlspecialchars($s['title']) ?>">
            <?php else: ?>
              <div class="sug-poster-placeholder">🎬</div>
            <?php endif; ?>
            <div class="sug-rating">★ <?= number_format((float)$s['vote_average'], 1) ?></div>
          </div>
          <div class="sug-info">
            <strong><?= htmlspecialchars($s['title']) ?></strong>
            <span class="sug-genre"><?= htmlspecialchars($s['genres']) ?></span>
            <?php if ($raisonText): ?>
              <span class="sug-raison"><?= $raisonText ?></span>
            <?php endif; ?>
          </div>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

    </section>
  </main>

  <div class="toast" id="toast"></div>
</div>

<script>
let searchTimer = null;

function liveSearch(value) {
  const q = value.trim();
  const prev = new URLSearchParams(window.location.search).get('search') || '';
  clearTimeout(searchTimer);
  if (q.length < 2) return;
  if (q === prev) return;
  searchTimer = setTimeout(() => {
    const url = new URL(window.location.href);
    url.searchParams.set('search', q);
    window.location.href = url.toString();
  }, 500);
}

const searchInput = document.getElementById('searchInput');
if (searchInput && searchInput.value !== '') {
  searchInput.focus();
  searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);
}

document.addEventListener('click', function(e) {
  const wrapper = document.querySelector('.search-wrapper');
  const dropdown = document.getElementById('searchDropdown');
  if (dropdown && wrapper && !wrapper.contains(e.target)) {
    dropdown.style.display = 'none';
  }
});

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    window.location.href = 'description.php?id=<?= $movieId ?>';
  }
});

function showToast(message) {
  const toast = document.getElementById("toast");
  toast.innerText = message;
  toast.classList.add("show");
  setTimeout(() => { toast.classList.remove("show"); }, 2500);
}

function watchAndOpen(movieId, tmdbId) {
  fetch("../controller/save_history.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "movie_id=" + movieId
  })
  .then(response => response.text())
  .then(data => {
    console.log("Réponse:", data);
    showToast("Film ajouté à l'historique !");
    setTimeout(() => {
      window.open("https://vidsrc.to/embed/movie/" + tmdbId, "_blank");
    }, 500);
  })
  .catch(() => {
    window.open("https://vidsrc.to/embed/movie/" + tmdbId, "_blank");
  });
}

function addFavorite(movieId) {
  fetch("../controller/add_favorite.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "movie_id=" + movieId
  })
  .then(response => response.text())
  .then(data => {
    if (data.trim() === "OK") {
      showToast("Ajouté aux favoris ❤️");
    } else {
      showToast(data);
    }
  })
  .catch(() => showToast("Erreur !"));
}

function setActive(btn) {
  document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
}

function goToPage(page) { window.location.href = page; }
</script>
</body>
</html>