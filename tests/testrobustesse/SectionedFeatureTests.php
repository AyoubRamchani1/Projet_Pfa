<?php

require_once __DIR__ . '/../../configuration_base.php';

$results = [];
$passed = 0;
$failed = 0;

// ─────────────────────────────
// MOTEUR DE TEST
// ─────────────────────────────
function test(string $name, callable $fn): void {
    global $results, $passed, $failed;

    try {
        $fn();
        $results[] = ['status' => 'PASS', 'name' => $name];
        $passed++;
    } catch (Throwable $e) {
        $results[] = ['status' => 'FAIL', 'name' => $name, 'msg' => $e->getMessage()];
        $failed++;
    }
}

function expect_true(bool $c, string $m = ''): void {
    if (!$c) throw new RuntimeException($m ?: 'Condition attendue TRUE');
}

function expect_false(bool $c, string $m = ''): void {
    if ($c) throw new RuntimeException($m ?: 'Condition attendue FALSE');
}

function expect_equals($a, $b, string $m = ''): void {
    if ($a !== $b) {
        throw new RuntimeException($m ?: "Attendu [$b], obtenu [$a]");
    }
}

// ─────────────────────────────
// CONNEXION DB (utilise config)
global $cnx;
$pdo = $cnx;

// ═════════════════════════════
// RECOMMANDATION
// ═════════════════════════════

test('RECO profil vide', function () {
    $profile = [];
    $recommendations = [];

    if (!empty($profile)) {
        $recommendations = ['erreur'];
    }

    expect_true(empty($recommendations));
});

test('RECO userId null', function () {
    $userId = null;
    $recommendations = [];

    if ($userId !== null) {
        $recommendations = ['erreur'];
    }

    expect_true(empty($recommendations));
});

test('RECO film vide ignore', function () {
    $film = ['tags' => '', 'genres' => '', 'directeur' => '', 'acteurs' => ''];

    $doc = strtolower(implode(' ', $film));
    $norme = str_word_count($doc) > 0 ? 1 : 0;

    expect_equals($norme, 0);
});

// ═════════════════════════════
// SEARCH
// ═════════════════════════════

test('SEARCH criteres vides', function () {
    $criteria = array_map('trim', ['genres' => '', 'directeur' => '', 'acteurs' => '', 'tags' => '']);
    expect_true(empty(array_filter($criteria)));
});

test('SEARCH espaces = vide', function () {
    $criteria = array_map('trim', ['tags' => '   ', 'genres' => '', 'acteurs' => ' ']);
    expect_true(empty(array_filter($criteria)));
});

test('SEARCH keyword no match', function () {
    $filmWords = explode(' ', 'romantic comedy love');
    $searchWords = explode(' ', 'horror zombie');

    $matches = 0;

    foreach ($searchWords as $sw) {
        foreach ($filmWords as $fw) {
            if (strpos($fw, $sw) !== false || strpos($sw, $fw) !== false) {
                $matches++;
                break;
            }
        }
    }

    $score = count($searchWords) > 0 ? ($matches / count($searchWords)) * 100 : 0;

    expect_equals($score, 0.0);
});

// ═════════════════════════════
// DESCRIPTION
// ═════════════════════════════

test('DESC id invalide', function () use ($pdo) {
    $stmt = $pdo->prepare("SELECT movie_id FROM movies WHERE movie_id = ?");
    $stmt->execute([999999]);

    expect_false((bool)$stmt->fetch());
});

test('DESC vote null', function () {
    $formatted = number_format((float)(null ?? 0), 1);
    expect_equals($formatted, '0.0');
});

test('DESC overview null', function () {
    $safe = substr('', 0, 120);
    expect_equals($safe, '');
});

test('DESC poster fallback', function () {
    $poster = '';
    $display = !empty($poster) ? $poster : '/images/no-poster.jpg';

    expect_equals($display, '/images/no-poster.jpg');
});

// ═════════════════════════════
// TMDB
// ═════════════════════════════

test('TMDB titre vide', function () {
    $title = '';
    expect_false(!empty(trim($title)));
});

test('TMDB results vide', function () {
    $response = ['results' => []];
    $poster = null;

    expect_true($poster === null);
});

test('TMDB http error', function () {
    $result = ['poster' => null, 'error' => 'HTTP 401'];

    expect_true($result['poster'] === null);
    expect_true(isset($result['error']));
});

test('TMDB response vide', function () {
    $poster = null;
    expect_true($poster === null);
});

// ═════════════════════════════
// AJAX
// ═════════════════════════════

test('AJAX moins 2 chars', function () {
    $q = 'a';
    $result = strlen($q) < 2 ? [] : ['data'];

    expect_true(empty($result));
});

test('AJAX vide', function () {
    $q = '';
    $result = strlen(trim($q)) < 2 ? [] : ['data'];

    expect_true(empty($result));
});

test('AJAX trim', function () {
    $input = '  Inception  ';
    $trimmed = rtrim($input);

    expect_equals(substr($trimmed, -1), 'n');
});

// ═════════════════════════════
// ❌ TESTS FAIL (ROBUSTESSE)
// ═════════════════════════════

// ❌ 1. BUG score dépasse 100
test('FAIL SCORE overflow', function () {
    $score = 200;

    // ne doit jamais être >100
    expect_true($score <= 100);
});

// ❌ 2. BUG acteur mal formé
test('FAIL ACTOR format invalide', function () {
    $actor = "tomcruisebradpitt";

    // devrait être parsé en noms séparés
    $isValid = strpos($actor, " ") !== false;

    expect_true($isValid);
});

// ❌ 3. BUG DB doublon favoris
test('FAIL DB duplicate favori', function () use ($pdo) {

    $movieId = 1;
    $userId = 1;

    // insertion doublon
    $pdo->exec("INSERT INTO favoris (id_film, id_user) VALUES ($movieId, $userId)");
    $pdo->exec("INSERT INTO favoris (id_film, id_user) VALUES ($movieId, $userId)");

    $count = $pdo->query("
        SELECT COUNT(*) FROM favoris 
        WHERE id_film = $movieId AND id_user = $userId
    ")->fetchColumn();

});
echo "RESULTATS TESTS\n\n";

foreach ($results as $r) {
    echo ($r['status'] === 'PASS' ? "✅" : "❌") . " " . $r['name'];
    if (!empty($r['msg'])) {
        echo " → " . $r['msg'];
    }
    echo "\n";
}

echo "\n✔ PASS: $passed | ❌ FAIL: $failed\n";