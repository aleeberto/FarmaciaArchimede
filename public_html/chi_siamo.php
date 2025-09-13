<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\PageBuilder;

PageBuilder::show('chi_siamo', [
    'meta_title'       => 'Chi Siamo | Farmacia Archimede',
    'meta_description' => 'Pagina di presentazione della Farmacia Archimede e del suo team',
    'meta_keywords'    => 'chi siamo, farmacia, archimede, team, presentazione'],
);
