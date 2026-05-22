<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../configuration_base.php';
require_once __DIR__ . '/../../model/SearchScorer.php';

class FilmSearchScorerTest extends TestCase

{    /**
     * Connexion PDO utilisée pour les tests
     */
    private ?PDO $cnx = null;

    /**
     * Initialisation avant chaque test
     */
    protected function setUp(): void
    {
        // Recharge la configuration si nécessaire
        if (!isset($GLOBALS['cnx'])) {
            require_once __DIR__ . '/../../configuration_base.php';
        }

        // Récupération de la connexion globale
        $this->cnx = $GLOBALS['cnx'] ?? null;

        // Vérification de sécurité
        if (!$this->cnx instanceof PDO) {
            $this->fail(
                "Connexion PDO non initialisée. Vérifiez configuration_base.php"
            );
        }
   }

    /**
     * Nettoyage après chaque test
     */
    protected function tearDown(): void
    {
        $this->cnx = null;
    }

    // ════════════════════════════════════════════════════════
    // TESTS DE FAILLES / ROBUSTESSE
    // ════════════════════════════════════════════════════════

    /**
     * Vérifie la recherche d'acteur en position impaire
     */
  // ════════════════════════════════════════════════════════
// TESTS SUPPLÉMENTAIRES POUR AUGMENTER LE COVERAGE
// ════════════════════════════════════════════════════════

// ─── KEYWORD ────────────────────────────────
    public function testKeywordScoreBasicMatch()
    {
        $score = SearchScorer::calculateKeywordScore(
            "action hero adventure",
            "hero",
            ""
        );

        $this->assertGreaterThan(0, $score);
    }

    public function testKeywordScorePerfectMatch()
    {
        $score = SearchScorer::calculateKeywordScore(
            "hero action",
            "hero action",
            ""
        );

        $this->assertEquals(100, $score);
    }

    public function testGenreMatch()
    {
        $score = SearchScorer::calculateGenreScore(
            "comedy drama",
            "comedy"
        );

        $this->assertEquals(100, $score);
    }

    public function testActorFullMatch()
    {
        $score = SearchScorer::calculateActorScore(
            "tom cruise brad pitt",
            "tom cruise"
        );

        $this->assertEquals(100, $score);
    }

    public function testActorPartialMatch()
    {
        $score = SearchScorer::calculateActorScore(
            "tom cruise",
            "tom"
        );

        $this->assertEquals(50, $score);
    }

    public function testDirectorMatch()
    {
        $score = SearchScorer::calculateDirectorScore(
            "steven spielberg",
            "spielberg"
        );

        $this->assertEquals(100, $score);
    }

    public function testLanguageMappingEnglish()
    {
        $score = SearchScorer::calculateLanguageScore(
            "en",
            "english"
        );

        $this->assertEquals(100, $score);
    }

    public function testLanguageMappingArabic()
    {
        $score = SearchScorer::calculateLanguageScore(
            "ar",
            "arabic"
        );

        $this->assertEquals(100, $score);
    }

    public function testGenreNoMatch()
    {
        $score = SearchScorer::calculateGenreScore(
            "horror",
            "comedy"
        );

        $this->assertEquals(0, $score);
    }

    public function testActorNotFound()
    {
        $score = SearchScorer::calculateActorScore(
            "tom cruise",
            "leonardo"
        );

        $this->assertEquals(0, $score);
    }


    // ─────────────────────────────────────────────
    // ❌ TESTS QUI FAIL (volontaires)
    // ─────────────────────────────────────────────

    // ❌ BUG : retourne 100 au lieu de 0 si input invalide
    public function testKeywordInvalidInputShouldReturnZero()
    {
        
        $score = SearchScorer::calculateKeywordScore([], [], []);

        $this->assertEquals(0, $score);
        // ACTUELLEMENT → retourne 100 → FAIL
    }

    // ❌ Mauvaise logique potentielle
    public function testActorEmptyShouldBeZero()
    {
        $score = SearchScorer::calculateActorScore(
            "tom cruise",
            ""
        );

        $this->assertEquals(0, $score);
    }

    // ❌ Si jamais bug sur gestion empty
    public function testGenreEmptyShouldReturnZero()
    {
        $score = SearchScorer::calculateGenreScore("", "");

        $this->assertEquals(0, $score);
    }

    // ❌ Validation manquante possible
    public function testDirectorEmptyFail()
    {
        $score = SearchScorer::calculateDirectorScore("", "nolan");

        $this->assertEquals(0, $score);
    }

    // ❌ Langue inconnue
    public function testLanguageUnknownShouldReturnZero()
    {
        $score = SearchScorer::calculateLanguageScore(
            "xx",
            "unknown"
        );

        $this->assertEquals(0, $score);
    }

    // ❌ CAS LIMITE : array vide
    public function testKeywordEmptyStrings()
    {
        $score = SearchScorer::calculateKeywordScore("", "", "");

        $this->assertEquals(0, $score);
    }

    // ❌ CAS LIMITE acteur mal formé
    public function testActorWeirdFormat()
    {
        $score = SearchScorer::calculateActorScore(
            "tomcruisebradpitt",
            "tom cruise"
        );

        $this->assertEquals(100, $score);
        // Peut FAIL selon parsing actuel
    }
}
