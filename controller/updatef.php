<?php
include("../configuration_base.php");

if (isset($_POST['update']) && $_POST['update'] == '1') {

    $movie_id          = $_POST['movie_id'];
    $title             = $_POST['title'];
    $overview          = $_POST['overview'];
    $original_language = $_POST['original_language'];
    $popularity        = $_POST['popularity'];
    $vote_average      = $_POST['vote_average'];
    $genres            = $_POST['genres'];
    $directeur         = $_POST['directeur'];
    $acteurs           = $_POST['acteurs'];

    $sql = "UPDATE movies 
            SET title             = ?, 
                overview          = ?, 
                original_language = ?, 
                popularity        = ?,
                vote_average      = ?,
                genres            = ?,
                directeur         = ?,
                acteurs           = ?
            WHERE movie_id = ?";

    $stmt = $cnx->prepare($sql);
    $res  = $stmt->execute([
        $title,
        $overview,
        $original_language,
        $popularity,
        $vote_average,
        $genres,
        $directeur,
        $acteurs,
        $movie_id       // toujours en dernier pour le WHERE
    ]);

    if ($res) {
        header("Location: ../view/filmlist.php?modif=ok");
    } else {
        header("Location: ../view/filmlist.php?modif=error");
    }
    exit();
}
?>