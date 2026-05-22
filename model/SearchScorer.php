<?php

class SearchScorer {
    
    /**
     * Supprime les accents d'une chaîne de caractères (Normalisation)
     */
    private static function stripAccents($str) {
        return strtr(
            utf8_decode($str), 
            utf8_decode('àáâãäåçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 
            'aaaaaaceeeeiiiinooooouuuuyyaaaaaaceeeeiiiinooooouuuuy'
        );
    }

    public static function calculateKeywordScore($tags, $query, $overview): int
    {
        // Validation : si l'input n'est pas une string, retourner 0
        if (!is_string($tags) || !is_string($query) || !is_string($overview)) {
            return 0;
        }
        if (empty(trim($query))) return 0;

        // ✅ FIX: utiliser les bons noms de variables ($tags, $overview)
        $combinedText = strtolower(trim($tags . ' ' . $overview));
        $combinedText = self::stripAccents($combinedText);
        
        $searchCleaned = strtolower(trim($query));
        $searchCleaned = self::stripAccents($searchCleaned);

        $filmWordsArray   = array_filter(array_map('trim', explode(' ', $combinedText)));
        $searchKeywordsArray = array_filter(array_map('trim', explode(' ', $searchCleaned)));

        if (empty($filmWordsArray) || empty($searchKeywordsArray)) {
            return 0;
        }

        $matches = 0;
        foreach ($searchKeywordsArray as $searchWord) {
            foreach ($filmWordsArray as $filmWord) {
                if (strpos($filmWord, $searchWord) !== false || strpos($searchWord, $filmWord) !== false) {
                    $matches++;
                    break;
                }
            }
        }

        return (int)(($matches / count($searchKeywordsArray)) * 100);
    }

    public static function calculateGenreScore($filmGenre, $searchGenre) {
        if (!is_string($filmGenre) || !is_string($searchGenre)) {
            return 0;
        }
        
        $filmGenres  = array_filter(array_map('trim', explode(' ', strtolower($filmGenre))));
        $searchGenre = strtolower(trim($searchGenre));
        
        if (empty($filmGenres) || empty($searchGenre)) {
            return 0;
        }

        $genreMap = [
            'drama'           => 'drama',
            'comedy'          => 'comedy',
            'drama romance'   => 'drama romance',
            'comedy romance'  => 'comedy romance',
            'comedy drama'    => 'comedy drama',
            'horror thriller' => 'horror thriller',
            'horror'          => 'horror',
        ];

        $searchGenreExpanded = $genreMap[$searchGenre] ?? $searchGenre;

        foreach ($filmGenres as $genre) {
            if (!empty($genre) && (
                strpos($genre, $searchGenreExpanded) !== false ||
                strpos($searchGenreExpanded, $genre) !== false
            )) {
                return 100;
            }
        }

        return 0;
    }

    public static function calculateActorScore(string $acteurs, string $query): int
    {
        if (empty(trim($query)) || empty(trim($acteurs))) return 0;

        $queryLower = strtolower(trim($query));

        // ✅ FIX: essayer d'abord la virgule, sinon traiter toute la chaîne comme un seul nom
        $acteursList = array_map('trim', explode(',', strtolower($acteurs)));

        // Si pas de virgule → la chaîne entière est un seul acteur (ex: "tom cruise")
        // Match exact complet
        if (count($acteursList) === 1) {
            $singleActor = strtolower(trim($acteurs));

            // Match exact
            if ($singleActor === $queryLower) return 100;

            // Match partiel : query est contenu dans l'acteur ou l'inverse
            if (str_contains($singleActor, $queryLower) || str_contains($queryLower, $singleActor)) {
                return 50;
            }

            return 0;
        }

        // Plusieurs acteurs séparés par virgule
        // Match exact sur un acteur complet → 100
        foreach ($acteursList as $acteur) {
            if ($acteur === $queryLower) return 100;
        }

        // Match partiel → 50
        foreach ($acteursList as $acteur) {
            if (str_contains($acteur, $queryLower) || str_contains($queryLower, $acteur)) {
                return 50;
            }
        }

        return 0;
    }

    public static function calculateDirectorScore($filmDirector, $searchDirector) {
        if (!is_string($filmDirector) || !is_string($searchDirector)) {
            return 0;
        }
        
        $filmDirector   = strtolower(trim($filmDirector));
        $searchDirector = strtolower(trim($searchDirector));

        if ($filmDirector === '' || $searchDirector === '') {
            return 0;
        }

        if (strpos($filmDirector, $searchDirector) !== false || strpos($searchDirector, $filmDirector) !== false) {
            return 100;
        }

        return 0;
    }

    public static function calculateLanguageScore($filmLanguage, $searchLanguage) {
        if (!is_string($filmLanguage) || !is_string($searchLanguage)) {
            return 0;
        }
        
        $filmLang   = strtolower(trim($filmLanguage));
        $searchLang = strtolower(trim($searchLanguage));

        if ($filmLang === '' || $searchLang === '') {
            return 0;
        }

        $langMap = [
            'english' => 'en',
            'french'  => 'fr',
            'spanish' => 'es',
            'chinese' => 'zh',
            'german'  => 'de',
            'italian' => 'it',
            'japanese'=> 'ja',
            'arabic'  => 'ar',
        ];

        $searchLangCode = $langMap[$searchLang] ?? substr($searchLang, 0, 2);
        $filmLangCode   = $langMap[$filmLang]   ?? $filmLang;

        if ($filmLangCode === $searchLangCode || $filmLang === $searchLang) {
            return 100;
        }

        return 0;
    }
}