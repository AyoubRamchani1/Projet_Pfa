<?php
function getTmdbPoster($title, $apiKey) {
    $cacheDir  = __DIR__ . '/../cache/';
    $cacheFile = $cacheDir . md5($title) . '.json';

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 2592000) {
        return json_decode(file_get_contents($cacheFile), true);
    }

    $titleEncoded = urlencode($title);
    $url = "https://api.themoviedb.org/3/search/movie?api_key=$apiKey&query=$titleEncoded&language=fr-FR";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_ENCODING, '');  // décompresse gzip automatiquement
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $tmdbSearch = curl_exec($ch);
    curl_close($ch);

    $result = ['poster' => null, 'tmdb_id' => null];

    if ($tmdbSearch) {
        $tmdbData = json_decode($tmdbSearch, true);
        $tmdbFilm = $tmdbData['results'][0] ?? null;

        if ($tmdbFilm) {
            $result['poster']  = $tmdbFilm['poster_path']
                ? 'https://image.tmdb.org/t/p/w300' . $tmdbFilm['poster_path']
                : null;
            $result['tmdb_id'] = $tmdbFilm['id'] ?? null;
        }
    }

    if (!is_dir($cacheDir)) mkdir($cacheDir, 0777, true);
    file_put_contents($cacheFile, json_encode($result));

    return $result;
}
?>