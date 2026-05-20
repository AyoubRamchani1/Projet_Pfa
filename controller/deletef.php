<?php
include("../configuration_base.php");

if (isset($_POST['movie_title']) && !empty($_POST['movie_title'])) {

    $movie_title = $_POST['movie_title'];

    $sql  = "DELETE FROM movies WHERE title = ?";
    $stmt = $cnx->prepare($sql);
    $res  = $stmt->execute([$movie_title]);

    if ($res) {
        header("Location: ../view/filmlist.php?delete=ok");
    } else {
        header("Location: ../view/filmlist.php?delete=error");
    }
    exit();
}

header("Location: ../view/filmlist.php?delete=error");
exit();
?>