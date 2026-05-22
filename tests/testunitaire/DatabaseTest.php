<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../configuration_base.php';

class DatabaseTest extends TestCase
{
    private ?PDO $cnx = null; // Ajout du ? pour le rendre nullable et éviter le crash initial
    private int $firstId;
    private int $secondId;

    protected function setUp(): void
    {
        // On force la ré-inclusion si la variable n'est pas encore détectée globalement
        if (!isset($GLOBALS['cnx'])) {
            require_once __DIR__ . '/../../configuration_base.php';
        }
        
        $this->cnx = $GLOBALS['cnx'] ?? null;

        if (!$this->cnx instanceof PDO) {
            $this->fail("La connexion PDO n'a pas pu être récupérée depuis la variable globale.");
        }

        $this->cnx->beginTransaction();

        // --- Insertion du premier film (Film A) ---
        $stmt = $this->cnx->prepare("INSERT INTO movies (title, overview, original_language, popularity, vote_average, genres, directeur, acteurs, tags) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            'Test Movie A',
            'Test overview',
            'en',
            1.0,
            6.5,
            'Test',
            'Test Director',
            'Actor One Actor Two',
            'test'
        ]);
        $this->firstId = (int)$this->cnx->lastInsertId();

        // --- Insertion du deuxième film (Film B) ---
        $stmt->execute([
            'Test Movie B',
            'Second overview',
            'en',
            1.1,
            7.0,
            'Test',
            'Another Director',
            'Actor Three Actor Four',
            'test'
        ]);
        $this->secondId = (int)$this->cnx->lastInsertId();
    }

    protected function tearDown(): void
    {
        if ($this->cnx->inTransaction()) {
            $this->cnx->rollBack();
        }
    }

    // ─── [NOMINAL] Auto-incrément des IDs ────────────────────────
    public function testMovieIdIsPositive(): void
    {
        $this->assertGreaterThan(0, $this->firstId, 'Le premier movie_id est défini');
    }

    public function testMovieIdAutoIncrements(): void
    {
        $this->assertGreaterThan($this->firstId, $this->secondId, 'movie_id s\'incrémente automatiquement');
    }

    // ─── [NOMINAL] Ajout aux favoris ─────────────────────────────
    public function testAddFavori(): void
    {
        $userId = 1;
        $stmt = $this->cnx->prepare("INSERT INTO favoris (id_film, id_user) VALUES (?, ?)");
        $stmt->execute([$this->firstId, $userId]);

        $stmtCheck = $this->cnx->prepare("SELECT COUNT(*) FROM favoris WHERE id_film = ? AND id_user = ?");
        $stmtCheck->execute([$this->firstId, $userId]);

        $this->assertEquals(1, (int)$stmtCheck->fetchColumn(), 'Ajout favori apparaît dans la liste');
    }

    // ─── [FAILLE 7] Doublon dans les favoris ─────────────────────
    public function testNoDuplicateFavori(): void
    {
        $userId = 1;
        $stmt = $this->cnx->prepare("INSERT INTO favoris (id_film, id_user) VALUES (?, ?)");
        $stmt->execute([$this->firstId, $userId]);

        try {
            $stmt->execute([$this->firstId, $userId]);
        } catch (\Exception $e) {
            // Contrainte UNIQUE ou PRIMARY KEY bloque l'insertion
        }

        $stmtCheck = $this->cnx->prepare("SELECT COUNT(*) FROM favoris WHERE id_film = ? AND id_user = ?");
        $stmtCheck->execute([$this->firstId, $userId]);

        // [FAIL ATTENDU] Sans contrainte UNIQUE(id_film, id_user), retourne 2
        $this->assertEquals(
            1,
            (int)$stmtCheck->fetchColumn(),
            'Un utilisateur ne peut pas ajouter le même film deux fois en favori'
        );
    }

    // ─── [NOMINAL] Moyenne des reviews ───────────────────────────
    public function testReviewAverage(): void
    {
        $stmt = $this->cnx->prepare("INSERT INTO reviews (movie_id, movie_title, user_name, note) VALUES (?, ?, ?, ?)");
        $stmt->execute([$this->firstId, 'Test Movie A', 'TestUser', 8]);
        $stmt->execute([$this->firstId, 'Test Movie A', 'OtherUser', 6]);

        $stmtAvg = $this->cnx->prepare("SELECT AVG(note) as moyenne FROM reviews WHERE movie_id = ?");
        $stmtAvg->execute([$this->firstId]);
        $average = (float)$stmtAvg->fetchColumn();

        $this->assertTrue(abs($average - 7.0) < 0.001, 'La moyenne se met à jour après ajout de notes');
    }

    // ─── [FAILLE 1] Cascade manquante lors de la suppression ─────
    public function testDeleteMovieCascadesReviews(): void
    {
        $stmt = $this->cnx->prepare("INSERT INTO reviews (movie_id, movie_title, user_name, note) VALUES (?, ?, ?, ?)");
        $stmt->execute([$this->secondId, 'Test Movie B', 'CascadeUser', 10]);

        // On supprime le film
        $stmtDelete = $this->cnx->prepare("DELETE FROM movies WHERE movie_id = ?");
        $stmtDelete->execute([$this->secondId]);

        // FORCE LE FAIL : Commentez ou supprimez la ligne ci-dessous.
        // Sans cette ligne (et sans contrainte ON DELETE CASCADE en BDD), la review restera à 1.
        // $stmtDeleteReviews = $this->cnx->prepare("DELETE FROM reviews WHERE movie_id = ?");
        // $stmtDeleteReviews->execute([$this->secondId]);

        $stmtCheck = $this->cnx->prepare("SELECT COUNT(*) FROM reviews WHERE movie_id = ?");
        $stmtCheck->execute([$this->secondId]);

        // Le test va échouer ici car il trouvera 1 review au lieu de 0
        $this->assertEquals(
            0,
            (int)$stmtCheck->fetchColumn(),
            'Les avis d\'un film doivent être supprimés en cascade quand le film disparaît'
        );
    }

    // ─── [FAILLE 5] Absence de valeur par défaut ─────────────────
    public function testInsertMovieWithMissingOptionalFields(): void
    {
        $insertionMissingFields = true;
        try {
            $stmt = $this->cnx->prepare("INSERT INTO movies (title, overview, original_language, popularity, vote_average, directeur) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                'Minimal Movie',
                'Missing optional fields test',
                'fr',
                0.8,
                6.0,
                'Minimal Director'
            ]);
        } catch (\Exception $e) {
            $insertionMissingFields = false;
        }

        // [FAIL ATTENDU] Si NOT NULL sans DEFAULT '', la base rejette l'insertion
        $this->assertTrue(
            $insertionMissingFields,
            'La base doit accepter l\'omission des champs secondaires et leur attribuer une valeur par défaut'
        );
    }
}