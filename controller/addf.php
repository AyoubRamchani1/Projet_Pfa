<?php
include("../configuration_base.php");

if (isset($_POST['add']) && $_POST['add'] == '1') {

    $title             = $_POST['title'];
    $overview          = $_POST['overview'];
    $original_language = $_POST['original_language'];
    $popularity        = $_POST['popularity'];
    $vote_average      = $_POST['vote_average'];
    $genres            = $_POST['genres'];
    $directeur         = $_POST['directeur'];
    $acteurs           = $_POST['acteurs'];

    $sql = "INSERT INTO movies (title, overview, original_language, popularity, vote_average, genres, directeur, acteurs)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $cnx->prepare($sql);
    $res  = $stmt->execute([
        $title,
        $overview,
        $original_language,
        $popularity,
        $vote_average,
        $genres,
        $directeur,
        $acteurs
    ]);

    if ($res) {
        header("Location: ../view/filmlist.php?add=ok");
    } else {
        header("Location: ../view/filmlist.php?add=error");
    }
    exit();
}
?>