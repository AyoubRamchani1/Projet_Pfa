<?php 
include("../configuration_base.php");
include("../model/film.php");

function AddMovie($cnx, $data) {
    $sql  = "INSERT INTO movies (movie_id, tags, title, overview, original_language, popularity, vote_average, genres, directeur, acteurs)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $cnx->prepare($sql);
    $res  = $stmt->execute([
        $data['movie_id'],
        $data['tags'],
        $data['title'],
        $data['overview'],
        $data['original_language'],
        $data['popularity'],
        $data['vote_average'],
        $data['genres'],
        $data['directeur'],
        $data['acteurs']
    ]);
    return $res;
}

function getAllMovies($cnx) {
    $stmt = $cnx->prepare("
        SELECT movie_id , title, overview, original_language, popularity, vote_average, genres, directeur, acteurs
        FROM movies 
        ORDER BY movie_id ASC
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll();
    $movies = [];
    foreach ($rows as $row) {
        $movie = new Movie(
            $row['movie_id'],
            $row['title'],
            $row['overview'],
            $row['original_language'],
            $row['popularity'],
            $row['vote_average'],
            $row['genres'],
            $row['directeur'],
            $row['acteurs']
        );
        $movies[] = $movie;
    }
    return $movies;
}

function searchMovies($cnx, $title) {
    $sql  = "
        SELECT movie_id,title, overview, original_language, popularity, vote_average, genres, directeur, acteurs
        FROM movies 
        WHERE title LIKE ?
    ";
    $stmt = $cnx->prepare($sql);
    $stmt->execute(['%' . $title . '%']);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $movies = [];
    foreach ($rows as $row) {
        $movie = new Movie(
            $row['movie_id'],
            $row['title'],
            $row['overview'],
            $row['original_language'],
            $row['popularity'],
            $row['vote_average'],
            $row['genres'],
            $row['directeur'],
            $row['acteurs']
        );
        $movies[] = $movie;
    }
    return $movies;
}
?>