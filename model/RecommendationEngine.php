<?php

require_once __DIR__ . '/../controller/tmdb_cache.php';

class RecommendationEngine {
    private string $apiKey;
    private $posterFetcher;

    public function __construct(string $apiKey, callable $posterFetcher = null) {
        $this->apiKey = $apiKey;
        $this->posterFetcher = $posterFetcher;
    }

    public function generateRecommendations(array $profileRaw, array $movies, array $filmsExclus = []): array {
        if (empty($profileRaw)) {
            return [];
        }

        $profileVec = $this->buildProfileVector($profileRaw);
        $movies = array_filter($movies, function ($film) use ($filmsExclus) {
            return !in_array($film['movie_id'], $filmsExclus, true);
        });

        if (empty($movies)) {
            return [];
        }

        $idf = $this->calculateIdf($profileVec, $movies);
        $scores = [];

        foreach ($movies as $film) {
            $doc = $this->buildDocument($film);
            $filmVecData = $this->calculateFilmVector($profileVec, $idf, $doc);

            if ($filmVecData === null) {
                continue;
            }

            [$cosinusSimilarity, $normeFilm] = $filmVecData;
            $scoreFinal = (0.7 * $cosinusSimilarity) + (0.3 * ((float)$film['vote_average'] / 10));

            if ($scoreFinal > 0) {
                $scores[] = [
                    'movie_id' => $film['movie_id'],
                    'title' => $film['title'],
                    'genres' => $film['genres'] ?? '',
                    'vote_average' => $film['vote_average'],
                    'cosinus' => round($cosinusSimilarity, 4),
                    'score_final' => round($scoreFinal, 4),
                ];
            }
        }

        usort($scores, fn($a, $b) => $b['score_final'] <=> $a['score_final']);
        $top8 = array_slice($scores, 0, 8);

        foreach ($top8 as &$result) {
            $posterFetcher = $this->posterFetcher ?: function ($title) {
                $tmdb = getTmdbPoster($title, $this->apiKey);
                return $tmdb['poster'] ?? null;
            };

            $poster = $posterFetcher($result['title']);
            $result['poster'] = $poster;
        }
        unset($result);

        return $top8;
    }

    public function buildProfileVector(array $profileRaw): array {
        $norm = sqrt(array_sum(array_map(fn($score) => $score * $score, $profileRaw)));
        $profileVec = [];

        foreach ($profileRaw as $term => $score) {
            $profileVec[strtolower($term)] = $norm > 0 ? $score / $norm : 0;
        }

        return $profileVec;
    }

    public function calculateIdf(array $profileVec, array $movies): array {
        $N = count($movies);
        $df = [];

        foreach ($movies as $film) {
            $doc = strtolower($this->buildDocument($film));
            foreach ($profileVec as $term => $_) {
                if (strpos($doc, $term) !== false) {
                    $df[$term] = ($df[$term] ?? 0) + 1;
                }
            }
        }

        $idf = [];
        foreach ($profileVec as $term => $_) {
            $docFreq = $df[$term] ?? 0;
            $idf[$term] = log(($N + 1) / ($docFreq + 1)) + 1;
        }

        return $idf;
    }

    public function calculateFilmVector(array $profileVec, array $idf, string $doc): ?array {
        $mots = array_filter(preg_split('/[\s,]+/', strtolower($doc)));
        $totalMots = count($mots);
        if ($totalMots === 0) {
            return null;
        }

        $filmVec = [];
        $normeFilm = 0;

        foreach ($profileVec as $term => $_) {
            $occurrences = substr_count($doc, $term);
            $tf = $occurrences / $totalMots;
            $tfidf = $tf * ($idf[$term] ?? 1);
            $filmVec[$term] = $tfidf;
            $normeFilm += $tfidf * $tfidf;
        }

        $normeFilm = sqrt($normeFilm);
        if ($normeFilm <= 0.0) {
            return null;
        }

        $produitScalaire = 0;
        foreach ($profileVec as $term => $pScore) {
            $produitScalaire += $pScore * ($filmVec[$term] / $normeFilm);
        }

        return [$produitScalaire, $normeFilm];
    }

    private function buildDocument(array $film): string {
        return trim((string)($film['tags'] ?? '') . ' ' . ($film['genres'] ?? '') . ' ' . ($film['directeur'] ?? '') . ' ' . ($film['acteurs'] ?? ''));
    }
}
