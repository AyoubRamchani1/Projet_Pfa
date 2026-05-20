<?php
include '..\configuration_base.php';
session_start();



if (isset($_POST['email'], $_POST['password'])) {

    $stmt = $cnx->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute([':email' => $_POST['email']]);
    $user = $stmt->fetch();

    if ($user && password_verify($_POST['password'], $user['password'])) {

        $_SESSION['name']  = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        if($user['role'] === 'admin') {
            if (isset($_SESSION['email'])) {
            header('Location: ../admin/a.php');
            exit;
            }
            
            

        } else {
            if (isset($_SESSION['email'])) {
            header('Location: ../home page/home.php');
            exit;
            }
        }

        
    } else {
        header('Location: login.html?page=signin&error=' . urlencode('Identifiants invalides.'));
        exit;
    }

} else {
    header('Location: login.html');
    exit;
}
?>
