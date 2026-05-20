<?php 
include("../configuration_base.php");
include("../model/user.php");

function getAllUsers($cnx) {

    $stmt = $cnx->prepare("SELECT * FROM users ORDER BY id ASC");
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $users = [];

    foreach ($rows as $row) {
        $user       = new User($row['name'], $row['email'],$row['role'],$row['password']);
        $user->id   = $row['id'];
        $users[]    = $user;
    }

    return $users;
}

function searchUsers($cnx, $name) {

    $sql  = "SELECT * FROM users WHERE name LIKE ?";
    $stmt = $cnx->prepare($sql);
    $stmt->execute(['%' . $name . '%']);
    $rows = $stmt->fetchAll();

    $users = [];

    foreach ($rows as $row) {
        $user       = new User($row['name'], $row['email'],$row['role'], $row['password']);
        $user->id   = $row['id'];
        $users[]    = $user;
    }

    return $users;
}
function ConnectUser($cnx, $data){

    $req = $cnx->prepare("SELECT * FROM users WHERE email = :email");
    $req->execute([
        ':email' => $data['email']
    ]);

    $user = $req->fetch();

    if($user && password_verify($data['password'], $user['password'])){
        return $user;
    }else{
        return false;
    }
}

?>
