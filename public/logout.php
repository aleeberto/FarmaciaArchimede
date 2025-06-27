<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Service\AuthService;
use App\Core\Database;
use App\Core\Auth;

// 1. Inizializza DB e AuthenticationService
$db  = Database::getInstance(
    getenv('MARIADB_HOST') ?: 'mariadb',
    getenv('MARIADB_USER') ?: 'admin',
    getenv('MARIADB_PASSWORD') ?: 'admin',
    getenv('MARIADB_DATABASE') ?: 'farmacia_archimede'
);

Auth::requireLogin();
$auth = new AuthService($db);


// 2. Distruggi la sessione
$auth->logout();

// 3. Redirigi al login
header('Location: /login.php');
exit;
