<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\PageBuilder;

PageBuilder::show($_SERVER['SCRIPT_NAME'], [
'title' => 'Contatti'
]);
