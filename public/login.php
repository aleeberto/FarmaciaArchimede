<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Service\LoginService;
use App\Core\Auth;


Auth::redirectIfLogged();

$service = new LoginService();
$service->handleRequest();