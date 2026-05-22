<?php
/**
 * ═══════════════════════════════════════════════════════════════
 * ALGORITHME BFS POUR RECHERCHE DE FILMS AVEC INDEXATION
 * ═══════════════════════════════════════════════════════════════
 */

require_once 'db_config.php';
require_once __DIR__ . '/../model/SearchScorer.php';

class FilmSearchEngine {
    private $conn;
    private $indexedFilms = [];
    private $scoringWeights = [
        'keyword_match' => 0.3,
        'genre_match' => 0.25,
        'actor_match' => 0.2,
        'director_match' => 0.15,
        'language_match' => 0.1
    ];

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    /**
     * ─── BFS SEARCH ALGORITHM ───
     * Utilise BFS pour explorer les films selon les critères
     */
    public function searchFilmsBFS($criteria) {
        $queue = new SplQueue();
        $visited = [];
        $results = [];

        // Étape 1: Initialisation - Ajouter les films correspondant à UN critère à la queue
        $initialFilms = $this->getInitialFilms($criteria);

        foreach ($initialFilms as $film) {
            $queue->enqueue([
                'film' => $film,
                'depth' => 0,
                'matchedCriteria' => []
            ]);
            $visited[$film['movie_id']] = true;
        }

        // Étape 2: BFS - Explorer la queue et appliquer les critères
        while (!$queue->isEmpty()) {
            $current = $queue->dequeue();
            $film = $current['film'];
            $depth = $current['depth'];

            // Calculer le score d'indexation
            $indexScore = $this->calculateIndexScore($film, $criteria);

            // Si le score dépasse le seuil, ajouter aux résultats
            if ($indexScore > 0) {
                $results[] = [
                    'film' => $film,
                    'score' => $indexScore,
                    'depth' => $depth,
                    'matchedCriteria' => $this->getMatchedCriteria($film, $criteria)
                ];
            }

            // Ajouter les films connexes si profondeur < 2
            if ($depth < 1) {
                $relatedFilms = $this->getRelatedFilms($film, $criteria);
                foreach ($relatedFilms as $related) {
                    if (!isset($visited[$related['movie_id']])) {
                        $queue->enqueue([
                            'film' => $related,
                            'depth' => $depth + 1,
                            'matchedCriteria' => []
                        ]);
                        $visited[$related['movie_id']] = true;
                    }
                }
            }
        }

        return $results;
    }

    /**
     * ─── RÉCUPÉRER LES FILMS INITIAUX ───
     * Première couche BFS: films correspondant à au moins un critère
     */
    private function getInitialFilms($criteria) {
        $films = [];
        $query = "SELECT movie_id, title, genres, directeur, acteurs, original_language, tags, overview, vote_average FROM movies WHERE 1=1";

        // Filtrer par genre
        if (!empty($criteria['genres']) && $criteria['genres'] !== 'all') {
            $genre = $this->conn->real_escape_string($criteria['genres']);
            $query .= " AND LOWER(genres) LIKE '%$genre%'";
        }

        // Filtrer par langue
        if (!empty($criteria['original_language'])) {
            $lang = $this->conn->real_escape_string($criteria['original_language']);
            $query .= " AND LOWER(original_language) LIKE '%$lang%'";
        }

        // Filtrer par réalisateur
        if (!empty($criteria['directeur'])) {
            $director = $this->conn->real_escape_string($criteria['directeur']);
            $query .= " AND LOWER(directeur) LIKE '%$director%'";
        }

        // Filtrer par acteur
        if (!empty($criteria['acteurs'])) {
            $actor = $this->conn->real_escape_string($criteria['acteurs']);
            $query .= " AND LOWER(acteurs) LIKE '%$actor%'";
        }

        // Filtrer par mots-clés (multi-mots dans tags ET overview)
        if (!empty($criteria['tags'])) {
            $words = array_filter(array_map('trim', explode(' ', strtolower($criteria['tags']))));
            if (!empty($words)) {
                $wordConditions = [];
                foreach ($words as $word) {
                    $w = $this->conn->real_escape_string($word);
                    $wordConditions[] = "(LOWER(tags) LIKE '%$w%' OR LOWER(overview) LIKE '%$w%')";
                }
                $query .= " AND (" . implode(' AND ', $wordConditions) . ")";
            }
        }

        $query .= " LIMIT 100";

        $result = $this->conn->query($query);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $films[] = $row;
            }
        }

        return $films;
    }

    /**
     * ─── OBTENIR LES FILMS CONNEXES ───
     * Deuxième couche BFS: films avec des caractéristiques similaires
     */
    private function getRelatedFilms($film, $criteria) {
        $relatedFilms = [];
        $filmGenres = explode(' ', trim($film['genres']));
        $primaryGenre = $filmGenres[0] ?? '';

        if (empty($primaryGenre)) {
            return $relatedFilms;
        }

        $genre = $this->conn->real_escape_string($primaryGenre);
        $query = "SELECT movie_id, title, genres, directeur, acteurs, original_language, tags, overview, vote_average 
                  FROM movies 
                  WHERE movie_id != {$film['movie_id']} 
                  AND LOWER(genres) LIKE '%$genre%'
                  LIMIT 20";

        $result = $this->conn->query($query);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $relatedFilms[] = $row;
            }
        }

        return $relatedFilms;
    }

    /**
     * ─── CALCUL DE L'INDEX DE SCORE ───
     * Système d'indexation pondéré pour classer les résultats
     */
    private function calculateIndexScore($film, $criteria) {
        $score = 0;
        $maxScore = array_sum($this->scoringWeights);

        // 1. CORRESPONDANCE DES MOTS-CLÉS (30%)
        if (!empty($criteria['tags'])) {
            $keywordScore = $this->calculateKeywordScore($film['tags'], $criteria['tags'], $film['overview'] ?? '');
            $score += $keywordScore * $this->scoringWeights['keyword_match'];
        }

        // 2. CORRESPONDANCE DU GENRE (25%)
        if (!empty($criteria['genres']) && $criteria['genres'] !== 'all') {
            $genreScore = $this->calculateGenreScore($film['genres'], $criteria['genres']);
            $score += $genreScore * $this->scoringWeights['genre_match'];
        }

        // 3. CORRESPONDANCE DE L'ACTEUR (20%)
        if (!empty($criteria['acteurs'])) {
            $actorScore = $this->calculateActorScore($film['acteurs'], $criteria['acteurs']);
            $score += $actorScore * $this->scoringWeights['actor_match'];
        }

        // 4. CORRESPONDANCE DU RÉALISATEUR (15%)
        if (!empty($criteria['directeur'])) {
            $directorScore = $this->calculateDirectorScore($film['directeur'], $criteria['directeur']);
            $score += $directorScore * $this->scoringWeights['director_match'];
        }

        // 5. CORRESPONDANCE DE LA LANGUE (10%)
        if (!empty($criteria['original_language'])) {
            $languageScore = $this->calculateLanguageScore($film['original_language'], $criteria['original_language']);
            $score += $languageScore * $this->scoringWeights['language_match'];
        }

        // Bonus: Augmenter le score basé sur la note IMDB
        $imdbBonus = (floatval($film['vote_average']) / 10) * 0.2;
        $score += $imdbBonus;

        return min($score, 100);
    }

    /**
     * ─── SCORE DE CORRESPONDANCE DES MOTS-CLÉS ───
     */
    private function calculateKeywordScore($filmKeywords, $searchKeywords, $filmOverview = '') {
        // Combiner tags + overview pour la recherche
        $combinedText = strtolower($filmKeywords . ' ' . $filmOverview);
        $filmWordsArray = array_filter(array_map('trim', explode(' ', $combinedText)));
        $searchKeywordsArray = array_filter(array_map('trim', explode(' ', strtolower($searchKeywords))));

        if (empty($filmWordsArray) || empty($searchKeywordsArray)) {
            return 0;
        }

        $matches = 0;
        foreach ($searchKeywordsArray as $searchWord) {
            // Chercher le mot dans le texte combiné (correspondance partielle aussi)
            foreach ($filmWordsArray as $filmWord) {
                if (strpos($filmWord, $searchWord) !== false || strpos($searchWord, $filmWord) !== false) {
                    $matches++;
                    break;
                }
            }
        }

        $totalKeywords = count($searchKeywordsArray);

        return ($matches / $totalKeywords) * 100;
    }

    /**
     * ─── SCORE DE CORRESPONDANCE DU GENRE ───
     */
    private function calculateGenreScore($filmGenre, $searchGenre) {
        $filmGenres = array_map('trim', explode(' ', strtolower($filmGenre)));
        $searchGenre = strtolower(trim($searchGenre));

       $genreMap = [
     // Genres simples
        'Drama'            => 'drama',
        'Comedy'           => 'comedy',
        'Action'           => 'action',
        'Adventure'        => 'adventure',
        'Thriller'         => 'thriller',
        'Romance'          => 'romance',
        'Horror'           => 'horror',
        'Fantasy'          => 'fantasy',
        'Mystery'          => 'mystery',
        'Crime'            => 'crime',
        'Animation'        => 'animation',
        'Sci-Fi'           => 'sci-fi',
        'Family'           => 'family',
        'War'              => 'war',
        'History'          => 'history',
        'Music'            => 'music',
        'Documentary'      => 'documentary',

        // Genres combinés
        'Drama Romance'    => 'drama romance',
        'Comedy Romance'   => 'comedy romance',
        'Comedy Drama'     => 'comedy drama',
        'Horror Thriller'  => 'horror thriller',
        'Action Adventure' => 'action adventure',
        'Action Thriller'  => 'action thriller',
        'Crime Drama'      => 'crime drama',
        'Mystery Thriller' => 'mystery thriller',
        'Sci-Fi Adventure' => 'sci-fi adventure',
        'Fantasy Adventure'=> 'fantasy adventure',
        'Animation Family' => 'animation family',
        'War Drama'        => 'war drama',
        'Romantic Comedy'  => 'romantic comedy',
];
        $searchGenreExpanded = $genreMap[$searchGenre] ?? $searchGenre;

        foreach ($filmGenres as $genre) {
            if (strpos($genre, $searchGenreExpanded) !== false || strpos($searchGenreExpanded, $genre) !== false) {
                return 100;
            }
        }

        return 0;
    }

    /**
     * ─── SCORE DE CORRESPONDANCE DE L'ACTEUR ───
     */
    private function calculateActorScore($filmCast, $searchActor) {
        $filmCastArray = array_map('trim', explode(' ', strtolower($filmCast)));
        $searchActor = strtolower(trim($searchActor));

        $castNames = [];
        for ($i = 0; $i < count($filmCastArray); $i++) {
            if ($i % 2 == 0) {
                $castNames[] = implode(' ', array_slice($filmCastArray, $i, 2));
            }
        }

        foreach ($castNames as $actor) {
            if (strpos($actor, $searchActor) !== false || strpos($searchActor, $actor) !== false) {
                return 100;
            }
        }

        // Recherche partielle
        foreach ($filmCastArray as $name) {
            if (strpos($name, $searchActor) !== false) {
                return 50;
            }
        }

        return 0;
    }

    /**
     * ─── SCORE DE CORRESPONDANCE DU RÉALISATEUR ───
     */
    private function calculateDirectorScore($filmDirector, $searchDirector) {
        $filmDirector = strtolower(trim($filmDirector));
        $searchDirector = strtolower(trim($searchDirector));

        if (strpos($filmDirector, $searchDirector) !== false || strpos($searchDirector, $filmDirector) !== false) {
            return 100;
        }

        return 0;
    }

    /**
     * ─── SCORE DE CORRESPONDANCE DE LA LANGUE ───
     */
    private function calculateLanguageScore($filmLanguage, $searchLanguage) {
        $filmLang = strtolower(trim($filmLanguage));
        $searchLang = strtolower(trim($searchLanguage));

        // Convertir les noms de langue en codes
        $langMap = [
            'english' => 'en',
            'french' => 'fr',
            'spanish' => 'es',
            'chinese' => 'zh',
            'german' => 'de',
            'italian' => 'it',
            'japanese' => 'ja'
        ];

        $searchLangCode = $langMap[$searchLang] ?? substr($searchLang, 0, 2);
        $filmLangCode = $langMap[$filmLang] ?? $filmLang;

        if ($filmLangCode === $searchLangCode || $filmLang === $searchLang) {
            return 100;
        }

        return 0;
    }

    /**
     * ─── OBTENIR LES CRITÈRES CORRESPONDANTS ───
     */
    private function getMatchedCriteria($film, $criteria) {
    $matched = [];

    if (!empty($criteria['genres']) && $criteria['genres'] !== 'all') {
        if (stripos($film['genres'], $criteria['genres']) !== false) {
            $matched[] = 'Genre';
        }
    }

    if (!empty($criteria['directeur'])) {
        if (stripos($film['directeur'], $criteria['directeur']) !== false) {
            $matched[] = 'Réalisateur';
        }
    }

    if (!empty($criteria['acteurs'])) {
        if (stripos($film['acteurs'], $criteria['acteurs']) !== false) {
            $matched[] = 'Acteur';
        }
    }

    if (!empty($criteria['original_language'])) {
        if (stripos($film['original_language'], $criteria['original_language']) !== false) {
            $matched[] = 'Langue';
        }
    }

    if (!empty($criteria['tags'])) {
        $keywordScore = $this->calculateKeywordScore(
            $film['tags'],
            $criteria['tags'],
            $film['overview'] ?? ''
        );

        if ($keywordScore > 30) {
            $matched[] = 'Mots-clés';
        }
    }

    return $matched;
}
}

/**
 * ═══════════════════════════════════════════════════════════════
 * GESTION DES REQUÊTES AJAX
 * ═══════════════════════════════════════════════════════════════
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    $criteria = [
    'genres'            => $_POST['genres'] ?? '',
    'directeur'         => $_POST['directeur'] ?? '',
    'acteurs'           => $_POST['acteurs'] ?? '',
    'original_language' => $_POST['original_language'] ?? '',
    'tags'              => $_POST['tags'] ?? ''
];

    // Valider et nettoyer les critères
    $criteria = array_map('trim', $criteria);
    $criteria = array_filter($criteria);

    if (empty($criteria)) {
        echo json_encode([
            'success' => false,
            'message' => 'Veuillez saisir au moins un critère de recherche'
        ]);
        exit;
    }

    try {
        $searchEngine = new FilmSearchEngine($conn);
        $results = $searchEngine->searchFilmsBFS($criteria);

        // Trier les résultats par score (ordre décroissant)
        usort($results, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // Limiter à 50 résultats
        $results = array_slice($results, 0, 50);

        echo json_encode([
            'success' => true,
            'count' => count($results),
            'results' => $results,
            'criteria' => $criteria
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de la recherche: ' . $e->getMessage()
        ]);
    }
} else {
    // Afficher la page HTML de recherche
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>CineMatch | Recommandation de films</title>
        <link rel="stylesheet" href="style2.css">
        
        <style>

            .results-container {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 20px;
                margin-top: 40px;
            }

            .film-card {
                background: var(--card);
                border-radius: 12px;
                padding: 20px;
                transition: transform 0.3s, box-shadow 0.3s;
                border-left: 4px solid var(--primary);
            }

            .film-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 15px 40px rgba(245, 197, 24, 0.2);
            }

            .film-title {
                font-size: 18px;
                font-weight: 700;
                color: var(--primary);
                margin: 0 0 10px 0;
            }

            .film-director {
                color: var(--muted);
                font-size: 14px;
                margin: 5px 0;
            }

            .film-score {
                display: inline-block;
                background: var(--primary);
                color: var(--dark);
                padding: 5px 12px;
                border-radius: 20px;
                font-weight: 600;
                font-size: 12px;
                margin-top: 10px;
            }

            .film-badges {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                margin-top: 12px;
            }

            .badge {
                background: rgba(245, 197, 24, 0.2);
                color: var(--primary);
                padding: 4px 10px;
                border-radius: 12px;
                font-size: 12px;
            }

            .search-status {
                margin-top: 20px;
                padding: 15px;
                border-radius: 8px;
                background: rgba(245, 197, 24, 0.1);
                border-left: 4px solid var(--primary);
                color: var(--text);
            }

            .loading {
                display: none;
                text-align: center;
                padding: 30px;
                color: var(--muted);
            }

            .spinner {
                border: 4px solid rgba(245, 197, 24, 0.2);
                border-top: 4px solid var(--primary);
                border-radius: 50%;
                width: 40px;
                height: 40px;
                animation: spin 1s linear infinite;
                margin: 0 auto 15px;
            }

            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }

            .no-results {
                text-align: center;
                padding: 40px;
                color: var(--muted);
            }
        </style>
    </head>
    <body>
<div class="app">
    <nav class="topbar">
      <span class="topbar-n">C</span>
      <button class="nav-btn" onclick="goToPage('home.php'); setActive(this)">
        <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="1.8" viewBox="0 0 24 24">
          <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
          <polyline points="9 22 9 12 15 12 15 22"/>
        </svg>
        <span>Home</span>
      </button>
      <button class="nav-btn active" onclick="setActive(this)">
    <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="1.8" viewBox="0 0 24 24">
        <circle cx="11" cy="11" r="8"/>
        <path d="m21 21-4.35-4.35"/>
    </svg>
    <span>Recherche Intelligente</span>
</button>
       <button class="nav-btn" onclick="goToPage('parametres.php'); setActive(this)">
        <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="1.8" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="3"/>
          <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
        </svg>
        <span>Paramètres</span>
      </button>
  </nav>

            <section id="discover">
                <div class="container">
                    <div class="section-heading reveal">
                        <div>
                            <h2>Recherche Intelligente</h2>
                            <p>Trouvez vos films préférés avec CineMatch</p>
                        </div>
                    </div>

                    <div class="search-panel reveal delay-1">
                        <div class="search-row">
                            <select id="genreSelect" aria-label="Filtrer par genre">
   <option value="">Tous les genres</option>

<!-- Genres simples -->
<option value="Drama">Drama</option>
<option value="Comedy">Comedy</option>
<option value="Action">Action</option>
<option value="Adventure">Adventure</option>
<option value="Thriller">Thriller</option>
<option value="Romance">Romance</option>
<option value="Horror">Horror</option>
<option value="Fantasy">Fantasy</option>
<option value="Mystery">Mystery</option>
<option value="Crime">Crime</option>
<option value="Animation">Animation</option>
<option value="Sci-Fi">Sci-Fi</option>
<option value="Family">Family</option>
<option value="War">War</option>
<option value="History">History</option>
<option value="Music">Music</option>
<option value="Documentary">Documentary</option>

<!-- Genres combinés -->
<option value="Drama Romance">Drama Romance</option>
<option value="Comedy Romance">Comedy Romance</option>
<option value="Comedy Drama">Comedy Drama</option>
<option value="Horror Thriller">Horror Thriller</option>
<option value="Action Adventure">Action Adventure</option>
<option value="Action Thriller">Action Thriller</option>
<option value="Crime Drama">Crime Drama</option>
<option value="Mystery Thriller">Mystery Thriller</option>
<option value="Sci-Fi Adventure">Sci-Fi Adventure</option>
<option value="Fantasy Adventure">Fantasy Adventure</option>
<option value="Animation Family">Animation Family</option>
<option value="War Drama">War Drama</option>
<option value="Romantic Comedy">Romantic Comedy</option>
</select>
                            <input id="langInput" type="text" placeholder="Langue (ex: en, fr, es)...">
                            <input id="actorInput" type="text" placeholder="Nom de l'acteur...">
                             <input id="realisateur" type="text" placeholder="Donner le nom de réalisateur préféré">
                            <input id="searchInput" type="text" placeholder="Mots-clés...">
                            <button class="primary-btn" onclick="searchFilms()">Lancer</button>
                        </div>
                    </div>

                    <div id="loading" class="loading">
                        <div class="spinner"></div>
                        <p>Recherche en cours...</p>
                    </div>

                    <div id="status" class="search-status" style="display: none;"></div>

                    <div id="results" class="results-container"></div>

                    <div id="noResults" class="no-results" style="display: none;">
                        <p>Aucun film ne correspond à vos critères. Essayez d'autres mots-clés.</p>
                    </div>
                </div>
            </section>
        </div>

        <script>
            function searchFilms() {
                const criteria = {
                    genres: document.getElementById('genreSelect').value,
                    directeur: document.getElementById('realisateur').value,
                    acteurs: document.getElementById('actorInput').value,
                    original_language: document.getElementById('langInput').value,
                    tags: document.getElementById('searchInput').value,
                    action: 'search'
                };

                // Vérifier si au moins un critère est rempli
                const hasFilter = Object.values(criteria).some((v, i) => i < 5 && v.trim() !== '');
                if (!hasFilter) {
                    alert('Veuillez saisir au moins un critère de recherche');
                    return;
                }

                // Afficher le chargement
                document.getElementById('loading').style.display = 'block';
                document.getElementById('results').innerHTML = '';
                document.getElementById('noResults').style.display = 'none';
                document.getElementById('status').style.display = 'none';

                // Requête AJAX
                const formData = new FormData();
                for (const [key, value] of Object.entries(criteria)) {
                    formData.append(key, value);
                }

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('loading').style.display = 'none';

                    if (data.success) {
                        displayResults(data.results);
                        displayStatus(data.count, criteria);
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                })
                .catch(error => {
                    document.getElementById('loading').style.display = 'none';
                    alert('Erreur de communication: ' + error.message);
                });
            }

            function displayResults(results) {
    const container = document.getElementById('results');
    const noResults = document.getElementById('noResults');

    if (results.length === 0) {
        noResults.style.display = 'block';
        return;
    }

    container.innerHTML = '';

    results.forEach(item => {
        const film = item.film;
        const score = item.score.toFixed(1);
        const matched = item.matchedCriteria.join(', ');

        const card = document.createElement('div');
        card.className = 'film-card';

        card.innerHTML = `
            <h3 class="film-title">${film.title}</h3>

            <p class="film-director">
                <strong>Réalisateur:</strong>
                ${film.directeur || 'Inconnu'}
            </p>

            <p class="film-director">
                <strong>Genre:</strong>
                ${film.genres || 'Inconnu'}
            </p>

            <p class="film-director">
                <strong>Langue:</strong>
                ${film.original_language || 'Inconnu'}
            </p>

            <div style="margin-top:10px;">
                <span class="film-score">
                    Score: ${score}%
                </span>

                <span class="film-score"
                      style="background: rgba(100,100,100,0.3); color: var(--text);">
                    ⭐ ${film.vote_average || '0'}
                </span>
            </div>

            <div class="film-badges">
                ${
                    matched
                    ? matched.split(', ').map(m =>
                        `<span class="badge">${m}</span>`
                      ).join('')
                    : ''
                }
            </div>

            <p style="font-size:13px; color:var(--muted); margin-top:12px; line-height:1.5;">
                ${(film.overview || '').substring(0,120)}...
            </p>
            <a href="../description page/description.php?id=${film.movie_id}" 
       style="display:inline-block;margin-top:14px;padding:8px 16px;
              background:var(--primary);color:var(--dark);border-radius:8px;
              font-weight:600;text-decoration:none;font-size:13px;">
       Voir le film →
    </a>
        `;

        container.appendChild(card);
    });
}

            function displayStatus(count, criteria) {
                const status = document.getElementById('status');
                const filters = [];

                if (criteria.genres) filters.push(`<strong>Genre:</strong> ${criteria.genres}`);
                if (criteria.directeur) filters.push(`<strong>Réalisateur:</strong> ${criteria.directeur}`);
                if (criteria.acteurs) filters.push(`<strong>Acteur:</strong> ${criteria.acteurs}`);
                if (criteria.original_language) filters.push(`<strong>Langue:</strong> ${criteria.original_language}`);
                if (criteria.tags) filters.push(`<strong>Mots-clés:</strong> ${criteria.tags}`);

                status.innerHTML = `
                    <strong>✓ Recherche complétée</strong><br>
                    ${count} film(s) trouvé(s)<br>
                    <small>${filters.join(' | ')}</small>
                `;
                status.style.display = 'block';
            }

            function goToPage(page) {
                window.location.href = page;
            }

            function setActive(btn) {
                document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            }
        </script>
    </body>
    </html>
    <?php
}
?>