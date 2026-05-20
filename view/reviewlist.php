<?php
include("../configuration_base.php");

// Messages
$successDelete = isset($_GET['delete']) && $_GET['delete'] == 'ok';
$errorDelete   = isset($_GET['delete']) && $_GET['delete'] == 'error';

// Recherche
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $q    = '%' . $_GET['search'] . '%';
    $stmt = $cnx->prepare("SELECT * FROM reviews WHERE movie_title LIKE ? OR user_name LIKE ? OR comment LIKE ? ORDER BY id ASC");
    $stmt->execute([$q, $q, $q]);
} else {
    $stmt = $cnx->prepare("SELECT * FROM reviews ORDER BY id ASC");
    $stmt->execute();
}

$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total   = count($reviews);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineSafra – Reviews List</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="review.css">
</head>
<body>

<div class="bg-grid"></div>
<div class="bg-glow"></div>

<!-- NAVBAR -->
<nav>
    <a class="logo" href="#">
        <div class="logo-icon"><i class="fas fa-play"></i></div>
        <span>Cine Safra</span>
    </a>
    <button class="nav-btn btn-back" onclick="window.location.href='../admin/a.php'">
        <i class="fas fa-arrow-left"></i> Retour
    </button>
</nav>

<!-- TOASTS -->
<?php if ($successDelete): ?>
<div class="toast toast-success" id="toast">
    <i class="fas fa-check-circle"></i>
    Avis supprimé avec succès
</div>
<?php endif; ?>

<?php if ($errorDelete): ?>
<div class="toast toast-error" id="toast">
    <i class="fas fa-exclamation-circle"></i>
    Erreur lors de la suppression
</div>
<?php endif; ?>

<!-- MAIN -->
<main>

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div class="header-left">
            <p class="label">ADMINISTRATION</p>
            <h1>Reviews <span>List</span></h1>
            <p class="subtitle">Gérez les avis et notes des utilisateurs.</p>
        </div>
        <div class="header-right">
            <div class="stat-card">
                <span class="stat-num"><?= $total ?></span>
                <span class="stat-label">Avis</span>
            </div>
        </div>
    </div>

    <!-- SEARCH -->
    <div class="toolbar">
        <form method="GET" style="flex:1;display:flex;gap:10px">
            <div class="search-wrap" style="flex:1">
                <i class="fas fa-search"></i>
                <input type="text" name="search"
                       placeholder="Rechercher par film, utilisateur ou commentaire..."
                       value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
            </div>
            <button type="submit" class="btn-search">
                <i class="fas fa-search"></i> Rechercher
            </button>
            <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
            <a href="reviewlist.php" class="btn-reset">
                <i class="fas fa-times"></i> Réinitialiser
            </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- TABLE -->
    <div class="table-wrap">
        <?php if ($total === 0): ?>
            <div class="empty-state">
                <i class="fas fa-comment-slash"></i>
                <p>Aucun avis trouvé.</p>
            </div>
        <?php else: ?>
        <table id="reviewTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Film</th>
                    <th>Utilisateur</th>
                    <th>Note</th>
                    <th>Commentaire</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($reviews as $review): ?>
                <tr>
                    <td>
                        <span class="id-tag">#<?= htmlspecialchars($review['id']) ?></span>
                    </td>
                    <td>
                        <div class="movie-cell">
                            <div class="movie-icon">
                                <i class="fas fa-film"></i>
                            </div>
                            <div>
                                <span class="movie-title"><?= htmlspecialchars($review['movie_title']) ?></span>
                                <span class="movie-id">ID <?= htmlspecialchars($review['movie_id']) ?></span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="user-cell">
                            <div class="avatar">
                                <?= strtoupper(mb_substr($review['user_name'], 0, 1)) ?>
                            </div>
                            <span class="user-name"><?= htmlspecialchars($review['user_name']) ?></span>
                        </div>
                    </td>
                    <td>
                        <?php if ($review['note'] !== null): ?>
                            <div class="note-badge note-<?= $review['note'] >= 7 ? 'high' : ($review['note'] >= 4 ? 'mid' : 'low') ?>">
                                <i class="fas fa-star"></i>
                                <?= htmlspecialchars($review['note']) ?>/10
                            </div>
                        <?php else: ?>
                            <span class="no-data">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="comment-cell">
                        <?php if (!empty($review['comment'])): ?>
                            <span class="comment-text" title="<?= htmlspecialchars($review['comment']) ?>">
                                <?= htmlspecialchars(mb_strimwidth($review['comment'], 0, 40, '…')) ?>
                            </span>
                        <?php else: ?>
                            <span class="no-data">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="date-cell">
                        <?= date('d/m/Y', strtotime($review['created_at'])) ?>
                        <span class="time"><?= date('H:i', strtotime($review['created_at'])) ?></span>
                    </td>
                    <td>
                        <div class="action-btns">
                            <button class="btn-delete"
                                    onclick="openDeleteModal(
                                        <?= $review['id'] ?>,
                                        '<?= htmlspecialchars($review['movie_title'], ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($review['user_name'], ENT_QUOTES) ?>'
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

<!-- ===== MODAL DELETE ===== -->
<div class="modal-overlay" id="deleteOverlay" onclick="closeDeleteModal()">
    <div class="modal-box modal-box-sm" onclick="event.stopPropagation()">
        <div class="modal-head modal-head-danger">
            <h3><i class="fas fa-exclamation-triangle"></i> Confirmation</h3>
            <button class="modal-close" onclick="closeDeleteModal()">✕</button>
        </div>
        <div class="modal-body-text">
            <p>Voulez-vous vraiment supprimer l'avis de</p>
            <strong id="deleteUserName"></strong>
            <p>sur <em id="deleteMovieName"></em> ?</p>
            <p style="margin-top:8px">Cette action est irréversible.</p>
        </div>
        <form method="POST" action="../controller/deleter.php">
            <input type="hidden" name="idr" id="deleteId">
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeDeleteModal()">
                    Annuler
                </button>
                <button type="submit" class="btn-danger-confirm">
                    <i class="fas fa-trash"></i> Supprimer
                </button>
            </div>
        </form>
    </div>
</div>

<footer>CineSafra Admin &mdash; Reviews Management</footer>

<script>
// ===== DELETE MODAL =====
function openDeleteModal(id, movie, user) {
    document.getElementById('deleteId').value             = id;
    document.getElementById('deleteUserName').textContent = '"' + user + '"';
    document.getElementById('deleteMovieName').textContent = movie;
    document.getElementById('deleteOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeDeleteModal() {
    document.getElementById('deleteOverlay').classList.remove('open');
    document.body.style.overflow = '';
}

// ===== ESCAPE KEY =====
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeDeleteModal();
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
    url.searchParams.delete('delete');
    window.history.replaceState({}, document.title, url.pathname);
}
</script>

</body>
</html>