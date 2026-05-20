<?php
include '..\configuration_base.php';

if (isset($_POST['name'], $_POST['email'], $_POST['password'], $_POST['confirm_password'])) {

    $name            = $_POST['name'];
    $email           = $_POST['email'];
    $password        = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // Vérifier si les mots de passe correspondent
    if ($password !== $confirmPassword) {
        header('Location: login.html?page=signup&error=' . urlencode('Les mots de passe ne correspondent pas.'));
        exit;
    }

    // Vérifier si l'email existe déjà
    $stmt = $cnx->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    if ($stmt->rowCount() > 0) {
        header('Location: login.html?page=signup&error=' . urlencode('Cet email est déjà utilisé.'));
        exit;
    }

    // Hasher et insérer
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $cnx->prepare("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)");
    $stmt->execute([':name' => $name, ':email' => $email, ':password' => $hashedPassword]);

    header('Location: login.html?message=' . urlencode('Inscription réussie ! Vous pouvez maintenant vous connecter.'));
    exit;
}
?>