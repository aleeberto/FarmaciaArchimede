<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Service\SignupService;

$svc = new SignupService();
$svc->handleRequest();
