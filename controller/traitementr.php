<?php 
include("../configuration_base.php");
include("../model/user.php");
include("../model/review.php");

/* ===============================
   REVIEWS
================================= */
function getAllReviews($cnx) {

    $stmt = $cnx->prepare("SELECT * FROM reviews ORDER BY id ASC");
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $reviews = [];

    foreach ($rows as $row) {
        $review             = new Review($row['movie_id'], $row['movie_title'], $row['user_name'], $row['note'], $row['comment']);
        $review->id         = $row['id'];
        $review->created_at = $row['created_at'];
        $review->updated_at = $row['updated_at'];
        $reviews[]          = $review;
    }

    return $reviews;
}

function searchReviews($cnx, $query) {

    $q    = '%' . $query . '%';
    $stmt = $cnx->prepare("SELECT * FROM reviews WHERE movie_title LIKE ? OR user_name LIKE ? OR comment LIKE ? ORDER BY id ASC");
    $stmt->execute([$q, $q, $q]);
    $rows = $stmt->fetchAll();

    $reviews = [];

    foreach ($rows as $row) {
        $review             = new Review($row['movie_id'], $row['movie_title'], $row['user_name'], $row['note'], $row['comment']);
        $review->id         = $row['id'];
        $review->created_at = $row['created_at'];
        $review->updated_at = $row['updated_at'];
        $reviews[]          = $review;
    }

    return $reviews;
}

?>