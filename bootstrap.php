<?php

require_once __DIR__ . '/vendor/autoload.php';

// Charge la connexion PDO dans le scope global AVANT que PHPUnit isole les tests.
// $cnx devient alors accessible via `global $cnx` dans n'importe quel TestCase.
require_once __DIR__ . '/configuration_base.php';