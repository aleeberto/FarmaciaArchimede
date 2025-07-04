<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Core\PageBuilder;
use App\Service\AuthService;

$db  = Database::getInstance(
    getenv('MARIADB_HOST') ?: 'mariadb',
    getenv('MARIADB_USER') ?: 'admin',
    getenv('MARIADB_PASSWORD') ?: 'admin',
    getenv('MARIADB_DATABASE') ?: 'farmacia_archimede'
);
$auth = new AuthService($db);

if (!$auth->isLogged()) {
    header('Location: /login.php?error=not_logged');
    exit;
}

$user = $auth->getUser();
PageBuilder::show('area_personale', [
    'user' => $user,
    'title' => 'Area personale'
]);