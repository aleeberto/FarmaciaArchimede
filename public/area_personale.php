<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Config\Config;
use App\Core\Database;
use App\Core\PageBuilder;
use App\Service\AuthService;

$cfg = Config::get('database');
$db  = Database::getInstance(
    $cfg['host'], $cfg['username'], $cfg['password'], $cfg['dbname']
);
$auth = new AuthService($db);

if (!$auth->isLogged()) {
    header('Location: /login.php?error=not_logged');
    exit;
}

$user = $auth->getUser();
PageBuilder::show('area_personale', ['user' => $user]);