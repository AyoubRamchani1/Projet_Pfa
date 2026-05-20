<?php
include("../configuration_base.php");

if (isset($_POST['idr'])) {

    $id   = $_POST['idr'];
    $sql  = "DELETE FROM reviews WHERE id = ?";
    $stmt = $cnx->prepare($sql);
    $stmt->execute([$id]);

    header("Location: ../view/reviewlist.php?delete=ok");
    exit;
} else {
    header("Location: ../view/reviewlist.php");
    exit;
}
?>