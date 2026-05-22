<?php

session_start();

require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config/config.php';

// récupérer URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// charger routes
$router = require __DIR__ . '/../routes.php';

// exécuter route
$router($uri, $config);