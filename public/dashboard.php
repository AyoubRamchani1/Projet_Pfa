<?php

session_start();

require __DIR__ . '/../vendor/autoload.php';

if (!isset($_SESSION['user'])) {
    header("Location: /login.php");
    exit;
}

require __DIR__ . '/../views/dashboard.view.php';