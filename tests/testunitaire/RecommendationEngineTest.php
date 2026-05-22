<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../model/RecommendationEngine.php';

class RecommendationEngineTest extends TestCase
{
    private RecommendationEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new RecommendationEngine('dummy', function ($title) {
            return 'https://image.tmdb.org/t/p/w300/mock_' . urlencode($title) . '.jpg';
        });
    }

    // ─── Aucun historique ─────────────────────────────────────────
    public function testNoRecommendationsWithoutHistory(): void
    {
        $recommendations = $this->engine->generateRecommendations([], [], []);
        $this->assertCount(0, $recommendations, 'Aucun utilisateur sans historique -> pas de recommandations');
    }

    // ─── Recommandations pertinentes ─────────────────────────────
    private function getSampleMovies(): array
    {
        return [
            ['movie_id' => 10, 'title' => 'Drama Movie',   'genres' => 'Drama',        'vote_average' => 8.0, 'directeur' => 'Jane Doe',   'acteurs' => 'Tom Hanks',  'tags' => 'drama emotion'],
            ['movie_id' => 11, 'title' => 'Action Film',   'genres' => 'Action',       'vote_average' => 7.5, 'directeur' => 'John Smith',  'acteurs' => 'Mark Doe',   'tags' => 'fight adventure'],
            ['movie_id' => 12, 'title' => 'Hanks Comedy',  'genres' => 'Comedy Drama', 'vote_average' => 7.2, 'directeur' => 'Jane Doe',   'acteurs' => 'Tom Hanks',  'tags' => 'comedy drama'],
        ];
    }

    public function testRecommendationsWithHistory(): void
    {
        $profile = ['Drama' => 4.5, 'Tom Hanks' => 3.0];
        $recs    = $this->engine->generateRecommendations($profile, $this->getSampleMovies(), [11]);

        $this->assertNotEmpty($recs, 'Utilisateur avec historique -> recommandations pertinentes');
    }

    public function testAlreadyWatchedFilmNotRecommended(): void
    {
        $profile = ['Drama' => 4.5, 'Tom Hanks' => 3.0];
        $recs    = $this->engine->generateRecommendations($profile, $this->getSampleMovies(), [11]);

        $this->assertNotEquals(11, $recs[0]['movie_id'], 'Les films déjà vus n\'apparaissent pas');
    }

    public function testPosterProvidedByFetcher(): void
    {
        $profile = ['Drama' => 4.5, 'Tom Hanks' => 3.0];
        $recs    = $this->engine->generateRecommendations($profile, $this->getSampleMovies(), [11]);

        $this->assertEquals(
            'https://image.tmdb.org/t/p/w300/mock_Drama+Movie.jpg',
            $recs[0]['poster'],
            'Poster fourni via le fetcher de test'
        );
    }

    // ─── Profil acteur + genre ────────────────────────────────────
    public function testActorAndGenreProfileRecommendation(): void
    {
        $recs = $this->engine->generateRecommendations(
            ['Comedy' => 3.0, 'Tom Hanks' => 2.0],
            $this->getSampleMovies(),
            [10, 11]
        );

        $this->assertNotEmpty($recs, 'Recommandation depuis un profil acteur + genre fonctionne');
    }

    public function testBestMatchRankedFirst(): void
    {
        $recs = $this->engine->generateRecommendations(
            ['Comedy' => 3.0, 'Tom Hanks' => 2.0],
            $this->getSampleMovies(),
            [10, 11]
        );

        $this->assertSame(12, $recs[0]['movie_id'], 'Le film correspondant au profil est classé premier');
    }
}