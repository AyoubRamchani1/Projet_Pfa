<?php

/* USER */
$email = $_SESSION['email'];

$stmt = $cnx->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

/* HISTORIQUE avec nom du film */
$sql = "
SELECT h.*, h.date_de_regard, m.title
FROM historique h
JOIN movies m ON m.movie_id = h.id_film
WHERE h.id_user = ?
ORDER BY h.date_de_regard DESC
";

$stmt = $cnx->prepare($sql);
$stmt->execute([$user['id']]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>