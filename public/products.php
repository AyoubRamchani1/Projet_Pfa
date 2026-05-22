<?php

session_start();

require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config/config.php';

if (!isset($_SESSION['user'])) {
    header("Location: /login.php");
    exit;
}

use App\Controller\ProductController;

try {
    $controller = new ProductController($config);
    $controller->index();
} catch (Throwable $e) {
    echo "Erreur interne.";
}