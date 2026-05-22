<?php

class TmdbCache {
    private string $cacheDir;
    private string $apiKey;
    private int $timeout;
    private $fetchCallback;  // ✅ nom correct de la propriété

    public function __construct(string $cacheDir, string $apiKey, callable $fetchCallback = null, int $timeout = 10) {
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $cacheDir);
        $this->cacheDir      = rtrim($normalized, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->apiKey        = $apiKey;
        $this->timeout       = $timeout;
        $this->fetchCallback = $fetchCallback;  // ✅ stocké dans fetchCallback
    }

    public function fetchPoster(string $title): array
    {
        $cacheFile = $this->getCacheFile($title);

        // Lire le cache si existant
        if (file_exists($cacheFile)) {
            $content = @file_get_contents($cacheFile);
            $data    = json_decode($content, true);
            
            // ✅ Si JSON corrompu → supprimer et refetcher
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                @unlink($cacheFile);
                // continuer vers le fetcher ci-dessous
            } else {
                return $data;
            }
        }

        // ✅ FIX: utiliser $this->fetchCallback au lieu de $this->fetcher
        if ($this->fetchCallback !== null) {
            $response = ($this->fetchCallback)($title);
        } else {
            $response = $this->fetchTmdbData($title);
        }

        $results = $response['results'] ?? [];

        if (empty($results)) {
            $result = ['poster' => null, 'tmdb_id' => null];
        } else {
            $first  = $results[0];
            $result = [
                'poster'  => isset($first['poster_path'])
                    ? 'https://image.tmdb.org/t/p/w300' . $first['poster_path']
                    : null,
                'tmdb_id' => $first['id'] ?? null,
            ];
        }

        // Écriture cache silencieuse si le dossier est accessible
        @file_put_contents($cacheFile, json_encode($result));

        return $result;
    }

    public function getCacheFile(string $title): string {
        return $this->cacheDir . md5($title) . '.json';
    }

    public function buildSearchUrl(string $title): string {
        $titleEncoded = urlencode($title);
        return "https://api.themoviedb.org/3/search/movie?api_key={$this->apiKey}&query={$titleEncoded}&language=fr-FR";
    }

    private function fetchTmdbData(string $title): array {
        $url = $this->buildSearchUrl($title);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response ? json_decode($response, true) : [];
    }
}