<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Core\PageBuilder;
use App\Service\AuthService;
use App\Core\Auth;

Auth::requireLogin();

$db  = Database::getInstance(
    getenv('MARIADB_HOST') ?: 'mariadb',
    getenv('MARIADB_USER') ?: 'admin',
    getenv('MARIADB_PASSWORD') ?: 'admin',
    getenv('MARIADB_DATABASE') ?: 'farmacia_archimede'
);
$auth = new AuthService($db);

$user = Auth::user();

PageBuilder::show('area_personale', [
    'user' => $user,
    'meta_title'       => 'Area Personale | Farmacia Archimede',
    'meta_description' => 'Descrizione specifica per questa pagina',
    'meta_keywords'    => 'parola1, parola2, parola3'
]);