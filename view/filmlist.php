<?php
include("../controller/traitementf.php");
include("../configuration_base.php");
if (isset($_GET['filter'])) {
    if ($_GET['filter'] === 'no_genre') {
        $stmt = $cnx->query("SELECT * FROM movies WHERE genres IS NULL OR TRIM(genres) = ''");
    } elseif ($_GET['filter'] === 'no_desc') {
        $stmt = $cnx->query("SELECT * FROM movies WHERE overview IS NULL OR TRIM(overview) = ''");
    }
    $films = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$successUpdate = isset($_GET['modif']) && $_GET['modif'] == 'ok';
$successDelete = isset($_GET['delete']) && $_GET['delete'] == 'ok';
$errorDelete   = isset($_GET['delete']) && $_GET['delete'] == 'error';
$successAdd    = isset($_GET['add'])    && $_GET['add']    == 'ok';
$errorAdd      = isset($_GET['add'])    && $_GET['add']    == 'error';

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $Movies = searchMovies($cnx, $_GET['search']);
} else {
    $Movies = getAllMovies($cnx);
}

$total = count($Movies);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineSafra – Movies List</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="filmlist.css">
</head>
<body>

<div class="bg-grid"></div>
<div class="bg-glow"></div>

<!-- NAVBAR -->
<nav>
    <a class="logo" href="#">
        <div class="logo-icon"><i class="fas fa-play"></i></div>
        <span>CineSafra</span>
    </a>
    <button class="nav-btn btn-back" onclick="window.location.href='../admin/a.php'">
        <i class="fas fa-arrow-left"></i> Retour
    </button>
</nav>

<!-- TOASTS -->
<?php if ($successUpdate): ?>
<div class="toast toast-success" id="toast">
    <i class="fas fa-check-circle"></i> Film modifié avec succès
</div>
<?php endif; ?>

<?php if ($successDelete): ?>
<div class="toast toast-success" id="toast">
    <i class="fas fa-check-circle"></i> Film supprimé avec succès
</div>
<?php endif; ?>

<?php if ($errorDelete): ?>
<div class="toast toast-error" id="toast">
    <i class="fas fa-exclamation-circle"></i> Erreur lors de la suppression
</div>
<?php endif; ?>

<?php if ($successAdd): ?>
<div class="toast toast-success" id="toast">
    <i class="fas fa-check-circle"></i> Film ajouté avec succès
</div>
<?php endif; ?>

<?php if ($errorAdd): ?>
<div class="toast toast-error" id="toast">
    <i class="fas fa-exclamation-circle"></i> Erreur lors de l'ajout
</div>
<?php endif; ?>

<!-- MAIN -->
<main>

    <div class="page-header">
        <div class="header-left">
            <p class="label">ADMINISTRATION</p>
            <h1>Movies <span>List</span></h1>
            <p class="subtitle">Gérez les films de la plateforme.</p>
        </div>
        <div class="header-right">
            <div class="stat-card">
                <span class="stat-num"><?= $total ?></span>
                <span class="stat-label">Films</span>
            </div>
        </div>
    </div>

    <!-- SEARCH + ADD -->
    <div class="toolbar">
        <form method="GET" style="flex:1;display:flex;gap:10px">
            <div class="search-wrap" style="flex:1">
                <i class="fas fa-search"></i>
                <input type="text" name="search"
                       placeholder="Rechercher un film..."
                       value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
            </div>
            <button type="submit" class="btn-search">
                <i class="fas fa-search"></i> Rechercher
            </button>
        </form>
        <button class="btn-add" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Ajouter un film
        </button>
    </div>

    <!-- TABLE -->
    <div class="table-wrap">
        <?php if ($total === 0): ?>
            <div class="empty-state">
                <i class="fas fa-film"></i>
                <p>Aucun film trouvé.</p>
            </div>
        <?php else: ?>
        <table id="movieTable">
            <thead>
                <tr>
                    <th>Titre</th>
                    <th>Langue</th>
                    <th>Popularité</th>
                    <th>Note</th>
                    <th>Genres</th>
                    <th>Directeur</th>
                    <th>Acteurs</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($Movies as $movie): ?>
                <tr>
                    <td>
                        <div class="user-cell">
                            <div class="avatar"><i class="fas fa-film"></i></div>
                            <span class="user-name"><?= htmlspecialchars($movie->title) ?></span>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($movie->original_language) ?></td>
                    <td><?= htmlspecialchars($movie->popularity) ?></td>
                    <td><?= htmlspecialchars($movie->vote_average) ?></td>
                    <td><?= htmlspecialchars($movie->genres) ?></td>
                    <td><?= htmlspecialchars($movie->directeur) ?></td>
                    <td><?= htmlspecialchars($movie->acteurs) ?></td>
                    <td>
                        <div class="action-btns">
                            <!-- ✅ CORRECTION : movie_id ajouté en premier paramètre -->
                            <button class="btn-edit"
                                    onclick="openEditModal(
                                        <?= $movie->movie_id ?>,
                                        '<?= htmlspecialchars($movie->title, ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($movie->overview, ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($movie->original_language, ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($movie->popularity, ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($movie->vote_average, ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($movie->genres, ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($movie->directeur, ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($movie->acteurs, ENT_QUOTES) ?>'
                                    )">
                                <i class="fas fa-edit"></i> Modifier
                            </button>

                            <button class="btn-delete"
                                    onclick="openDeleteModal(
                                        '<?= htmlspecialchars($movie->title, ENT_QUOTES) ?>'
                                    )">
                                <i class="fas fa-trash"></i> Supprimer
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</main>

<!-- ===== MODAL UPDATE ===== -->
<div class="modal-overlay" id="editOverlay" onclick="closeEditModal()">
    <div class="modal-box" onclick="event.stopPropagation()">
        <div class="modal-head">
            <h3><i class="fas fa-edit"></i> Modifier le film</h3>
            <button class="modal-close" onclick="closeEditModal()">✕</button>
        </div>
        <form method="POST" action="../controller/updatef.php">
            <input type="hidden" name="update" value="1">
            <!-- ✅ CORRECTION : champ caché movie_id -->
            <input type="hidden" name="movie_id" id="editMovieId">

            <div class="form-group">
                <label>Titre</label>
                <input type="text" name="title" id="editTitle"
                       class="form-input" required placeholder="Titre du film">
            </div>

            <div class="form-group">
                <label>Overview</label>
                <textarea name="overview" id="editOverview"
                          class="form-input" rows="3"
                          placeholder="Description du film"></textarea>
            </div>

            <div class="form-group">
                <label>Langue originale</label>
                <input type="text" name="original_language" id="editLanguage"
                       class="form-input" maxlength="5" placeholder="ex: en, fr...">
            </div>

            <div class="form-group">
                <label>Popularité</label>
                <input type="number" step="0.000001" name="popularity" id="editPopularity"
                       class="form-input" placeholder="ex: 126.39">
            </div>

            <div class="form-group">
                <label>Note (vote_average)</label>
                <input type="number" step="0.1" name="vote_average" id="editVoteAverage"
                       class="form-input" placeholder="ex: 7.5">
            </div>

            <div class="form-group">
                <label>Genres</label>
                <input type="text" name="genres" id="editGenres"
                       class="form-input" placeholder="ex: Action, Comedy">
            </div>

            <div class="form-group">
                <label>Directeur</label>
                <input type="text" name="directeur" id="editDirecteur"
                       class="form-input" placeholder="Nom du réalisateur">
            </div>

            <div class="form-group">
                <label>Acteurs</label>
                <input type="text" name="acteurs" id="editActeurs"
                       class="form-input" placeholder="ex: Tom Hanks, Meryl Streep">
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeEditModal()">Annuler</button>
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Sauvegarder
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===== MODAL ADD ===== -->
<div class="modal-overlay" id="addOverlay" onclick="closeAddModal()">
    <div class="modal-box" onclick="event.stopPropagation()">
        <div class="modal-head">
            <h3><i class="fas fa-plus-circle"></i> Ajouter un film</h3>
            <button class="modal-close" onclick="closeAddModal()">✕</button>
        </div>
        <form method="POST" action="../controller/addf.php">
            <input type="hidden" name="add" value="1">

            <div class="form-group">
                <label>Titre</label>
                <input type="text" name="title"
                       class="form-input" required placeholder="Titre du film">
            </div>

            <div class="form-group">
                <label>Overview</label>
                <textarea name="overview"
                          class="form-input" rows="3"
                          placeholder="Description du film"></textarea>
            </div>

            <div class="form-group">
                <label>Langue originale</label>
                <input type="text" name="original_language"
                       class="form-input" maxlength="5" placeholder="ex: en, fr...">
            </div>

            <div class="form-group">
                <label>Popularité</label>
                <input type="number" step="0.000001" name="popularity"
                       class="form-input" placeholder="ex: 126.39">
            </div>

            <div class="form-group">
                <label>Note (vote_average)</label>
                <input type="number" step="0.1" min="0" max="10" name="vote_average"
                       class="form-input" placeholder="ex: 7.5">
            </div>

            <div class="form-group">
                <label>Genres</label>
                <input type="text" name="genres"
                       class="form-input" placeholder="ex: Action, Comedy">
            </div>

            <div class="form-group">
                <label>Directeur</label>
                <input type="text" name="directeur"
                       class="form-input" placeholder="Nom du réalisateur">
            </div>

            <div class="form-group">
                <label>Acteurs</label>
                <input type="text" name="acteurs"
                       class="form-input" placeholder="ex: Tom Hanks, Meryl Streep">
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeAddModal()">Annuler</button>
                <button type="submit" class="btn-save">
                    <i class="fas fa-plus"></i> Ajouter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===== MODAL DELETE ===== -->
<div class="modal-overlay" id="deleteOverlay" onclick="closeDeleteModal()">
    <div class="modal-box modal-box-sm" onclick="event.stopPropagation()">
        <div class="modal-head modal-head-danger">
            <h3><i class="fas fa-exclamation-triangle"></i> Confirmation</h3>
            <button class="modal-close" onclick="closeDeleteModal()">✕</button>
        </div>
        <div class="modal-body-text">
            <p>Voulez-vous vraiment supprimer</p>
            <strong id="deleteMovieName"></strong>
            <p>Cette action est irréversible.</p>
        </div>
        <form method="POST" action="../controller/deletef.php">
            <input type="hidden" name="movie_title" id="deleteTitle">
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Annuler</button>
                <button type="submit" class="btn-danger-confirm">
                    <i class="fas fa-trash"></i> Supprimer
                </button>
            </div>
        </form>
    </div>
</div>

<footer>CineSafra Admin &mdash; Movie Management</footer>

<script>
// ===== SEARCH LIVE =====
document.querySelector('input[name="search"]')
    .addEventListener('input', function () {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#movieTable tbody tr').forEach(row => {
            row.style.display =
                row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });

// ===== EDIT MODAL ===== ✅ CORRECTION : movie_id ajouté en premier paramètre
function openEditModal(movie_id, title, overview, language, popularity, voteAverage, genres, directeur, acteurs) {
    document.getElementById('editMovieId').value     = movie_id;
    document.getElementById('editTitle').value       = title;
    document.getElementById('editOverview').value    = overview;
    document.getElementById('editLanguage').value    = language;
    document.getElementById('editPopularity').value  = popularity;
    document.getElementById('editVoteAverage').value = voteAverage;
    document.getElementById('editGenres').value      = genres;
    document.getElementById('editDirecteur').value   = directeur;
    document.getElementById('editActeurs').value     = acteurs;
    document.getElementById('editOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeEditModal() {
    document.getElementById('editOverlay').classList.remove('open');
    document.body.style.overflow = '';
}

// ===== DELETE MODAL =====
function openDeleteModal(title) {
    document.getElementById('deleteTitle').value = title;
    document.getElementById('deleteMovieName').textContent = '"' + title + '"';
    document.getElementById('deleteOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeDeleteModal() {
    document.getElementById('deleteOverlay').classList.remove('open');
    document.body.style.overflow = '';
}

// ===== ADD MODAL =====
function openAddModal() {
    document.getElementById('addOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeAddModal() {
    document.getElementById('addOverlay').classList.remove('open');
    document.body.style.overflow = '';
}

// ===== ESCAPE KEY =====
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closeEditModal();
        closeDeleteModal();
        closeAddModal();
    }
});

// ===== AUTO HIDE TOAST =====
const toast = document.getElementById('toast');
if (toast) {
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3500);
}

// ===== CLEAN URL =====
if (window.history.replaceState) {
    const url = new URL(window.location);
    url.searchParams.delete('modif');
    url.searchParams.delete('delete');
    url.searchParams.delete('add');
    window.history.replaceState({}, document.title, url.pathname);
}
</script>

</body>
</html>