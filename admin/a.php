
<?php
session_start();


// ── Déconnexion ───────────────────────────────────────────────
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ../view/login.php');
    exit();
}

require_once '..\configuration_base.php';

// ── Genres ────────────────────────────────────────────────────
$stmtGenres = $cnx->query("SELECT genres, COUNT(*) as total FROM movies WHERE genres != '' AND genres IS NOT NULL GROUP BY genres ORDER BY total DESC LIMIT 8");
$genresData = $stmtGenres->fetchAll(PDO::FETCH_ASSOC);
$genreLabels = []; $genreCounts = [];
foreach ($genresData as $row) {
    $genreLabels[] = $row['genres'];
    $genreCounts[] = $row['total'];
}

// ── Nouveaux utilisateurs ─────────────────────────────────────
$stmtUsers = $cnx->query("SELECT id, name, email, role FROM users ORDER BY id DESC LIMIT 5");
$newUsers = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

// ── Stats générales ───────────────────────────────────────────
$totalMovies  = $cnx->query("SELECT COUNT(*) FROM movies")->fetchColumn();
$totalUsers   = $cnx->query("SELECT COUNT(*) FROM users")->fetchColumn();
$topGenre     = !empty($genreLabels) ? $genreLabels[0] : 'N/A';
$totalReviews = $cnx->query("SELECT COUNT(*) FROM reviews")->fetchColumn();

// ── Alertes : films sans description ou sans genre ────────────
$noDesc  = $cnx->query("SELECT COUNT(*) FROM movies WHERE overview IS NULL OR TRIM(overview) = ''")->fetchColumn();
$noGenre = $cnx->query("SELECT COUNT(*) FROM movies WHERE genres IS NULL OR TRIM(genres) = ''")->fetchColumn();

// ── Listes films manquants ────────────────────────────────────
$filmsNoGenre = $cnx->query("SELECT movie_id, title FROM movies WHERE genres IS NULL OR TRIM(genres) = '' ORDER BY title LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
$filmsNoDesc  = $cnx->query("SELECT movie_id, title FROM movies WHERE overview IS NULL OR TRIM(overview) = '' ORDER BY title LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

// ── Top 5 films les plus commentés ───────────────────────────
$stmtTop = $cnx->query("
    SELECT movie_title, movie_id, COUNT(*) as nb_comments
    FROM reviews
    WHERE comment IS NOT NULL AND TRIM(comment) != ''
    GROUP BY movie_id, movie_title
    ORDER BY nb_comments DESC
    LIMIT 5
");
$topMovies = $stmtTop->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineSafra Admin - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="a.css">
</head>
<body>

<div class="admin-wrapper">
    <aside class="sidebar">
        <div class="logo"><i class="fas fa-play-circle"></i> CINE<span>SAFRA</span></div>
        <div class="menu-item active" onclick="switchTab('overview', this)">
            <i class="fas fa-th-large"></i> Vue d'ensemble
        </div>
        <div class="menu-item" onclick="window.location.href='../view/filmlist.php'">
            <i class="fas fa-film"></i> Movies list
        </div>
        <div class="menu-item" onclick="window.location.href='../view/userlist.php'">
            <i class="fas fa-users"></i> Users
        </div>
        <div class="menu-item" onclick="window.location.href='../view/reviewlist.php'">
            <i class="fas fa-star"></i> Avis 
        </div>
        <div class="menu-item" onclick="window.location.href='../home page/home.php'">
            <i class="fas fa-house"></i> Accueil
        </div>
        <div class="menu-item logout-btn" onclick="window.location.href='?logout=1'">
            <i class="fas fa-right-from-bracket"></i> Déconnexion
        </div>
    </aside>

    <main class="main-content">
        <header>
            <div class="header-title">
                <p>ADMINISTRATION</p>
                <h1 id="current-title">VUE D'ENSEMBLE</h1>
            </div>
        </header>

        <section id="overview" class="tab-section active">

            <!-- ── Stats Cards ── -->
            <div class="stats-grid">
                <div class="stat-card">
                    <p style="color:var(--accent-yellow)">Films au total</p>
                    <h3><?= number_format($totalMovies) ?></h3>
                    <small>Dans la base de données</small>
                </div>
                <div class="stat-card">
                    <p style="color:var(--accent-red)">Utilisateurs</p>
                    <h3><?= number_format($totalUsers) ?></h3>
                    <small>Inscrits sur la plateforme</small>
                </div>
                <div class="stat-card">
                    <p style="color:#22c55e">Genre le plus populaire</p>
                    <h3 style="font-size:1.4rem"><?= htmlspecialchars($topGenre) ?></h3>
                    <small>Le plus représenté</small>
                </div>
                <div class="stat-card">
                    <p style="color:#06b6d4">Avis & Commentaires</p>
                    <h3><?= number_format($totalReviews) ?></h3>
                    <small>Total dans reviews</small>
                </div>
            </div>

            <!-- ── Ligne : Alertes + Top 5 films commentés ── -->
            <div class="two-col-row">

                <!-- ALERTES DONNÉES MANQUANTES -->
                <div class="alert-box">
                    <h4>⚠️ Données manquantes</h4>

                    <?php if ($noDesc == 0 && $noGenre == 0): ?>
                        <div class="alert-empty">
                            <i class="fas fa-check-circle" style="font-size:1.5rem;display:block;margin-bottom:6px"></i>
                            Tout est complet, aucun problème détecté !
                        </div>
                    <?php else: ?>

                        <?php if ($noDesc > 0): ?>
                        <div class="alert-item">
                            <i class="fas fa-file-slash alert-icon"></i>
                            <div class="alert-text">
                                <strong>Sans description</strong>
                                Films sans overview dans la BDD
                            </div>
                            <span class="alert-count"><?= $noDesc ?></span>
                        </div>
                        <div class="missing-list">
                            <?php foreach ($filmsNoDesc as $f): ?>
                                <a class="missing-film-link" href="../view/description.php?id=<?= $f['movie_id'] ?>">
                                    <i class="fas fa-circle"></i>
                                    <?= htmlspecialchars($f['title']) ?>
                                </a>
                            <?php endforeach; ?>
                            <?php if ($noDesc > 10): ?>
                                <span class="missing-more">+ <?= $noDesc - 10 ?> autres...</span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($noGenre > 0): ?>
                        <div class="alert-item">
                            <i class="fas fa-tags alert-icon"></i>
                            <div class="alert-text">
                                <strong>Sans genre</strong>
                                Films sans genres définis
                            </div>
                            <span class="alert-count"><?= $noGenre ?></span>
                        </div>
                        <div class="missing-list">
                            <?php foreach ($filmsNoGenre as $f): ?>
                                <a class="missing-film-link" href="../view/description.php?id=<?= $f['movie_id'] ?>">
                                    <i class="fas fa-circle"></i>
                                    <?= htmlspecialchars($f['title']) ?>
                                </a>
                            <?php endforeach; ?>
                            <?php if ($noGenre > 10): ?>
                                <span class="missing-more">+ <?= $noGenre - 10 ?> autres...</span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                    <?php endif; ?>
                </div>

                <!-- TOP 5 FILMS LES PLUS COMMENTÉS -->
                <div class="top-movies-box">
                    <h4>🏆 Top 5 films commentés</h4>

                    <?php if (empty($topMovies)): ?>
                        <p style="color:var(--text-dim);font-size:0.9rem">Aucun commentaire pour l'instant.</p>
                    <?php else: ?>
                        <?php
                        $rankClasses = ['gold', 'silver', 'bronze', '', ''];
                        foreach ($topMovies as $i => $m):
                            $rc = $rankClasses[$i] ?? '';
                        ?>
                        <div class="top-movie-item">
                            <div class="top-rank <?= $rc ?>"><?= $i + 1 ?></div>
                            <div class="top-movie-info">
                                <div class="top-movie-title"><?= htmlspecialchars($m['movie_title']) ?></div>
                            </div>
                            <span class="top-movie-count">
                                <i class="fas fa-comment" style="font-size:0.7rem"></i>
                                <?= $m['nb_comments'] ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>

            <!-- ── Ligne bas : Pie Chart genres + Nouveaux utilisateurs ── -->
            <div class="two-col-row">

                <!-- PIE CHART GENRES -->
                <div class="chart-box">
                    <h4>📊 Genres les plus populaires</h4>
                    <div class="pie-wrapper">
                        <canvas id="genreChart"></canvas>
                        <div class="genre-legend" id="genreLegend"></div>
                    </div>
                </div>

                <!-- NOUVEAUX UTILISATEURS -->
                <div class="users-box">
                    <h4>👤 Nouveaux utilisateurs</h4>
                    <?php foreach ($newUsers as $user):
                        $initiale = strtoupper(substr($user['name'], 0, 1));
                    ?>
                    <div class="user-item">
                        <div class="user-avatar"><?= $initiale ?></div>
                        <div class="user-info">
                            <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
                            <div class="user-email"><?= htmlspecialchars($user['email']) ?></div>
                        </div>
                        <span class="user-role <?= $user['role'] === 'admin' ? 'admin' : '' ?>">
                            <?= htmlspecialchars($user['role'] ?? 'user') ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>

            </div>

        </section>

        <section id="movies" class="tab-section">
            <div class="stat-card" style="width:100%;text-align:center;">
                <p>Interface de gestion de liste</p>
            </div>
        </section>
    </main>
</div>

<script>
    function switchTab(tabId, el) {
        document.querySelectorAll('.tab-section').forEach(s => s.classList.remove('active'));
        document.querySelectorAll('.menu-item').forEach(m => m.classList.remove('active'));
        document.getElementById(tabId).classList.add('active');
        el.classList.add('active');
        const titleMap = { 'overview': "VUE D'ENSEMBLE", 'movies': 'MOVIES LIST' };
        document.getElementById('current-title').innerText = titleMap[tabId] || '';
    }

    // ── PIE CHART GENRES ──────────────────────────────────────
    const genreLabels = <?= json_encode($genreLabels) ?>;
    const genreCounts = <?= json_encode($genreCounts) ?>;
    const palette = ['#facc15','#ef4444','#3b82f6','#22c55e','#a855f7','#f97316','#06b6d4','#ec4899'];

    const ctxPie = document.getElementById('genreChart').getContext('2d');
    new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: genreLabels,
            datasets: [{
                data: genreCounts,
                backgroundColor: palette,
                borderColor: '#111111',
                borderWidth: 3,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            cutout: '60%',
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: (ctx) => ` ${ctx.label}: ${ctx.raw} films` } }
            }
        }
    });

    const legendEl = document.getElementById('genreLegend');
    genreLabels.forEach((label, i) => {
        legendEl.innerHTML += `
            <div class="legend-item">
                <div class="legend-dot" style="background:${palette[i]}"></div>
                <span class="legend-label">${label}</span>
                <span class="legend-count">${genreCounts[i]}</span>
            </div>`;
    });
</script>

</body>
</html>