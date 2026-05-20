<?php
include("../controller/traitement.php");
include("../configuration_base.php");

// Messages
$successUpdate = isset($_GET['modif']) && $_GET['modif'] == 'ok';
$successDelete = isset($_GET['delete']) && $_GET['delete'] == 'ok';
$errorDelete   = isset($_GET['delete']) && $_GET['delete'] == 'error';

// Recherche
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $Users = searchUsers($cnx, $_GET['search']);
} else {
    $Users = getAllUsers($cnx);
}

$total = count($Users);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineSafra – Users List</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="userlist.css">
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
<?php if ($successUpdate): ?>
<div class="toast toast-success" id="toast">
    <i class="fas fa-check-circle"></i>
    Utilisateur modifié avec succès
</div>
<?php endif; ?>

<?php if ($successDelete): ?>
<div class="toast toast-success" id="toast">
    <i class="fas fa-check-circle"></i>
    Utilisateur supprimé avec succès
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
            <h1>Users <span>List</span></h1>
            <p class="subtitle">Gérez les comptes utilisateurs de la plateforme.</p>
        </div>
        <div class="header-right">
            <div class="stat-card">
                <span class="stat-num"><?= $total ?></span>
                <span class="stat-label">Utilisateurs</span>
            </div>
        </div>
    </div>

    <!-- SEARCH -->
    <div class="toolbar">
        <form method="GET" style="flex:1;display:flex;gap:10px">
            <div class="search-wrap" style="flex:1">
                <i class="fas fa-search"></i>
                <input type="text" name="search"
                       placeholder="Rechercher un utilisateur..."
                       value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
            </div>
            <button type="submit" class="btn-search">
                <i class="fas fa-search"></i> Rechercher
            </button>
            <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
            <a href="userlist.php" class="btn-reset">
                <i class="fas fa-times"></i> Réinitialiser
            </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- TABLE -->
    <div class="table-wrap">
        <?php if ($total === 0): ?>
            <div class="empty-state">
                <i class="fas fa-user-slash"></i>
                <p>Aucun utilisateur trouvé.</p>
            </div>
        <?php else: ?>
        <table id="userTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Utilisateur</th>
                    <th>Rôle</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php $i = 0; ?>
            <?php foreach ($Users as $user): $i++; ?>
                <tr>
                    <td>
                        <span class="id-tag">#<?= htmlspecialchars($user->id) ?></span>
                    </td>
                    <td>
                        <div class="user-cell">
                            <div class="avatar">
                                <?= strtoupper(mb_substr($user->name, 0, 1)) ?>
                            </div>
                            <span class="user-name">
                                <?= htmlspecialchars($user->name) ?>
                            </span>
                        </div>
                    </td>
                    <td>
                     <?php if ($user->role === 'admin'): ?>
                    <span class="role-badge admin">Admin</span>
                    <?php else: ?>
                    <span class="role-badge user">User</span>
                    <?php endif; ?>
                    </td>
                    <td class="email-cell">
                        <?= htmlspecialchars($user->email) ?>
                    </td>
                    <td>
                        <div class="action-btns">
                            <!-- UPDATE BUTTON -->
                            <button class="btn-edit" onclick="openEditModal(
                                <?= $user->id ?>,
                                '<?= htmlspecialchars($user->name, ENT_QUOTES) ?>',
                                '<?= htmlspecialchars($user->email, ENT_QUOTES) ?>',
                                 '<?= htmlspecialchars($user->role, ENT_QUOTES) ?>')">
                                <i class="fas fa-trash"></i> modifier
                            <button class="btn-delete"
                                    onclick="openDeleteModal(
                                        <?= $user->id ?>,
                                        '<?= htmlspecialchars($user->name, ENT_QUOTES) ?>'
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
            <h3><i class="fas fa-edit"></i> Modifier l'utilisateur</h3>
            <button class="modal-close" onclick="closeEditModal()">✕</button>
        </div>
        <form method="POST" action="../controller/update.php">
            <input type="hidden" name="update" value="1">
            <input type="hidden" name="idu" id="editId">

            <div class="form-group">
                <label>Nom d'utilisateur</label>
                <input type="text" name="name" id="editName"
                       class="form-input" required
                       placeholder="Nom d'utilisateur">
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="editEmail"
                       class="form-input" required
                       placeholder="email@example.com">
            </div>
         <div class="form-group">
        <label>Rôle</label>
        <select name="role" id="editRole" class="form-input">
            <option value="user">User</option>
            <option value="admin">Admin</option>
         </select>
    </div>
            <div class="form-group">
                <label>Nouveau mot de passe</label>
                <input type="password" name="password"
                       class="form-input"
                       placeholder="Laisser vide pour ne pas changer">
                <small>Laisser vide pour conserver l'ancien mot de passe</small>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel"
                        onclick="closeEditModal()">
                    Annuler
                </button>
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Sauvegarder
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
            <strong id="deleteUserName"></strong>
            <p>Cette action est irréversible.</p>
        </div>
        <form method="POST" action="../controller/delete.php">
            <input type="hidden" name="idu" id="deleteId">
            <div class="modal-actions">
                <button type="button" class="btn-cancel"
                        onclick="closeDeleteModal()">
                    Annuler
                </button>
                <button type="submit" class="btn-danger-confirm">
                    <i class="fas fa-trash"></i> Supprimer
                </button>
            </div>
        </form>
    </div>
</div>

<footer>CineSafra Admin &mdash; User Management</footer>

<script>
// ===== SEARCH LIVE =====
document.querySelector('input[name="search"]')
    .addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#userTable tbody tr').forEach(row => {
        row.style.display =
            row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

// ===== EDIT MODAL =====
function openEditModal(id, name, email, role) {
    document.getElementById('editId').value    = id;
    document.getElementById('editName').value  = name;
    document.getElementById('editEmail').value = email;
    document.getElementById('editRole').value  = role;
    document.getElementById('editOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeEditModal() {
    document.getElementById('editOverlay').classList.remove('open');
    document.body.style.overflow = '';
}

// ===== DELETE MODAL =====
function openDeleteModal(id, name) {
    document.getElementById('deleteId').value          = id;
    document.getElementById('deleteUserName').textContent = '"' + name + '"';
    document.getElementById('deleteOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeDeleteModal() {
    document.getElementById('deleteOverlay').classList.remove('open');
    document.body.style.overflow = '';
}

// ===== ESCAPE KEY =====
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closeEditModal();
        closeDeleteModal();
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
    window.history.replaceState({}, document.title, url.pathname);
}
</script>

</body>
</html>