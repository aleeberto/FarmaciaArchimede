<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\PageBuilder;

PageBuilder::show($_SERVER['SCRIPT_NAME'], [
    'meta_title'       => 'Chi Siamo | Farmacia Archimede',
    'meta_description' => 'Descrizione specifica per questa pagina',
    'meta_keywords'    => 'parola1, parola2, parola3'],
);
