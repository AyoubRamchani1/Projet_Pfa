<?php
session_start();
session_destroy();

header('Location: ../view/login.html?message=' . urlencode('Vous avez été déconnecté avec succès.'));
exit;
?>