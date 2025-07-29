<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Core\PageBuilder;
use App\Service\AuthService;
use App\Core\Auth;
use App\Service\AreaPersonaleService;

Auth::requireLogin();

// 1. Inizializza DB e AuthenticationService
$db  = Database::getInstance(
    getenv('MARIADB_HOST') ?: 'mariadb',
    getenv('MARIADB_USER') ?: 'admin',
    getenv('MARIADB_PASSWORD') ?: 'admin',
    getenv('MARIADB_DATABASE') ?: 'farmacia_archimede'
);
$auth = new AuthService($db);

// 3. Recupera dati aggiuntivi dell’area personale
$areaService = new AreaPersonaleService($auth, $db);
$data        = $areaService->getAreaPersonaleData();

// Mostra la pagina
PageBuilder::show('area_personale', $data);