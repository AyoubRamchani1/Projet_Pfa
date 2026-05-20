<?php
include("../configuration_base.php");

if  (isset($_POST['idu'])) {

    $id = $_POST['idu'];
    $sql  = "DELETE FROM users WHERE id = ?";
    $stmt = $cnx->prepare($sql);
    $stmt->execute([$id]);

        header("Location: ../view/userlist.php?delete=ok");
        exit;
} else {
    header("Location: userlist.php");
    exit;
}
?>