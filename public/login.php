<?php

session_start();

require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config/config.php';

use App\Controller\AuthController;

try {
    $controller = new AuthController($config);
    $controller->login();
} catch (Throwable $e) {
    echo "Erreur interne.";
}