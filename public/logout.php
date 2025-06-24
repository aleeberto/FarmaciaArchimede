<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Config\Config;
use App\Service\AuthService;
use App\Core\Database;
use App\Core\Auth;

// 1. Inizializza DB e AuthenticationService
$cfg = Config::get('database');
$db  = Database::getInstance(
    $cfg['host'],
    $cfg['username'],
    $cfg['password'],
    $cfg['dbname']
);

Auth::requireLogin();
$auth = new AuthService($db);


// 2. Distruggi la sessione
$auth->logout();

// 3. Redirigi al login
header('Location: /login.php');
exit;
