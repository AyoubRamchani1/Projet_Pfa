<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../model/TmdbCache.php';

class TmdbCacheTest extends TestCase
{
    private string $tmpDir;
    private TmdbCache $cache;
    private int $fetchCount = 0;

    protected function setUp(): void
    {
        $this->tmpDir    = __DIR__ . '/tmp_cache';
        $this->fetchCount = 0;

        if (!is_dir($this->tmpDir)) {
            mkdir($this->tmpDir, 0777, true);
        }

        $fetcher = function ($title) {
            $this->fetchCount++;
            if ($title === 'Known Movie') {
                return ['results' => [['poster_path' => '/poster.jpg', 'id' => 42]]];
            }
            return ['results' => []];
        };

        $this->cache = new TmdbCache($this->tmpDir, 'dummy', $fetcher);
    }

    protected function tearDown(): void
    {
        // Nettoyage des fichiers de cache créés pendant le test
        foreach (glob($this->tmpDir . '/*.json') ?: [] as $file) {
            @unlink($file);
        }
    }

    // ─── Film connu ───────────────────────────────────────────────
    public function testKnownMovieReturnsPoster(): void
    {
        $result = $this->cache->fetchPoster('Known Movie');
        $this->assertEquals(
            'https://image.tmdb.org/t/p/w300/poster.jpg',
            $result['poster'],
            'Film connu renvoie un poster'
        );
    }

    public function testKnownMovieReturnsTmdbId(): void
    {
        $result = $this->cache->fetchPoster('Known Movie');
        $this->assertEquals(42, $result['tmdb_id'], 'Film connu renvoie un ID TMDB');
    }

    // ─── Film inconnu ─────────────────────────────────────────────
    public function testUnknownMovieReturnsNullPoster(): void
    {
        $result = $this->cache->fetchPoster('Unknown Movie 12345');
        $this->assertNull($result['poster'], 'Film inconnu ne génère pas d\'erreur et retourne null poster');
    }

    public function testUnknownMovieReturnsNullTmdbId(): void
    {
        $result = $this->cache->fetchPoster('Unknown Movie 12345');
        $this->assertNull($result['tmdb_id'], 'Film inconnu ne génère pas d\'erreur et retourne null tmdb_id');
    }

    // ─── Cache ────────────────────────────────────────────────────
    public function testCacheReturnsSameResult(): void
    {
        $result1 = $this->cache->fetchPoster('Known Movie');
        $result2 = $this->cache->fetchPoster('Known Movie');

        $this->assertEquals($result1, $result2, 'Le deuxième appel renvoie le même résultat que le premier');
    }

    public function testCacheFileIsCreated(): void
    {
        $this->cache->fetchPoster('Known Movie');
        $cacheFile = $this->cache->getCacheFile('Known Movie');

        $this->assertNotEmpty(file_get_contents($cacheFile), 'Le cache local est bien créé');
    }

    public function testCacheFileExists(): void
    {
        $this->cache->fetchPoster('Known Movie');
        $cacheFile = $this->cache->getCacheFile('Known Movie');

        $this->assertFileExists($cacheFile, 'Le fichier de cache TMDB est présent');
    }
    // ─── EXTENSIONS DE COVERAGE POUR TMDBCACHE (NEGATIVE TESTING) ──────

public function testCacheHandlesCorruptedJsonFile(): void
{
    // 1. On simule un fichier de cache existant mais corrompu (non lisible ou JSON invalide)
    $cacheFile = $this->cache->getCacheFile('Corrupted Movie');
    file_put_contents($cacheFile, "{ invalid json ... @@@");

    // 2. On appelle la méthode : elle doit ignorer le fichier cassé et appeler le fetcher
    $result = $this->cache->fetchPoster('Corrupted Movie');
    
    // Vérifie que la récupération s'est faite correctement malgré la corruption
    $this->assertNull($result['poster']);
}

public function testCacheHandlesWriteErrorGracefully(): void
{
    // 1. On crée un sous-dossier de cache en lecture seule (chmod 0444) pour empêcher l'écriture
    $unwritableDir = $this->tmpDir . '/readonly_layer';
    if (!is_dir($unwritableDir)) {
        mkdir($unwritableDir, 0444, true);
    }
    
    // Évite les alertes si le dossier ne peut être créé en mode strict
    @chmod($unwritableDir, 0444); 

    $restrictedCache = new TmdbCache($unwritableDir, 'dummy', function($title) {
        return ['results' => [['poster_path' => '/test.jpg', 'id' => 99]]];
    });

    // 2. L'appel ne doit pas générer d'erreur fatale PHP, le système doit contourner le problème
    $result = $restrictedCache->fetchPoster('Any Movie');
    $this->assertNotNull($result);
    
    // Nettoyage spécifique
    @chmod($unwritableDir, 0777);
    @rmdir($unwritableDir);
}
}