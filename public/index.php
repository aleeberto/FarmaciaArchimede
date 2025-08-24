<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\PageBuilder;

PageBuilder::show($_SERVER['SCRIPT_NAME'], [
    'meta_title'       => 'Home | Farmacia Archimede',
    'meta_description' => 'Pagina di benvenuto della Farmacia Archimede',
    'meta_keywords'    => 'home, farmacia, archimede, benvenuto, salute',
];
