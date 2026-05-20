<?php
session_start();
include '../configuration_base.php';

if (!isset($_SESSION['email'])) {
    header("Location: ../view/login.html");
    exit();
}

/* USER */
$email = $_SESSION['email'];

$stmt = $cnx->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    session_unset();
    header("Location: /Projet PFA/view/login.html");
    exit();
}

if (isset($_POST['update_all'])) {

    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $current = $_POST['current_password'];
    $new     = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    /* check password confirm */
    if ($new !== $confirm) {
        header("Location: ../parametres.php?error=password_confirm");
        exit();
    }

    /* verify current password */
    if (password_verify($current, $user['password'])) {

        $newHash = password_hash($new, PASSWORD_DEFAULT);

        $update = $cnx->prepare("
            UPDATE users 
            SET password = ?, name = ? 
            WHERE id = ?
        ");

        $update->execute([$newHash, $name, $user['id']]);

        session_destroy();
        session_unset();

        header("Location: ../view/login.html?msg=password_changed");
        exit();

    } else {
        header("Location: ../parametres.php?error=wrong_password");
        exit();
    }
}
?>