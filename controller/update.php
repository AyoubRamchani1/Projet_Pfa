<?php
include("../configuration_base.php");
include("../model/user.php");

if(isset($_POST['idu'])){

    $user = new user(
        $_POST['name'],
        $_POST['email'],
        $_POST['role'],
        $_POST['password']
    );

    $user->id = $_POST['idu'];

    // récupérer ancien password
    $reqOld = $cnx->prepare("SELECT password FROM users WHERE id = :id");
    $reqOld->execute([
        ':id' => $user->id
    ]);
    $oldData = $reqOld->fetch();

    // comparer
    if($user->password != $oldData['password']){
        $password = password_hash($user->password, PASSWORD_DEFAULT);
    } else {
        $password = $oldData['password'];
    }

    $requete = $cnx->prepare("UPDATE users 
                SET name = :name, 
                    email = :email, 
                    role = :role, 
                    password = :password 
                WHERE id = :id");

    $resultat = $requete->execute([
        ':name'     => $user->name,
        ':email'    => $user->email,
        ':role'    => $user->role,
        ':password' => $password,
        ':id'       => $user->id
    ]);

    if ($resultat){
        header('location:../view/userlist.php?modif=ok');
        exit;
    } else {
        echo "Erreur lors de la mise à jour";
    }
}
?>