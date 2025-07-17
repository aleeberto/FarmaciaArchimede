<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Core\PageBuilder;
use App\Service\AuthService;
use App\Service\AreaPersonaleService;

// 1. Inizializza DB e AuthenticationService
$db  = Database::getInstance(
    getenv('MARIADB_HOST') ?: 'mariadb',
    getenv('MARIADB_USER') ?: 'admin',
    getenv('MARIADB_PASSWORD') ?: 'admin',
    getenv('MARIADB_DATABASE') ?: 'farmacia_archimede'
);

$auth = new AuthService($db);

// Verifica autenticazione
if (!$auth->isLogged()) {
    header('Location: /login.php?error=not_logged');
    exit;
}

// Carica dati con il service
$areaService = new AreaPersonaleService($auth, $db);
$data = $areaService->getAreaPersonaleData();

// Mostra la pagina
PageBuilder::show('area_personale', $data);
