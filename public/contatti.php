<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\PageBuilder;

PageBuilder::show('contatti', [
    'meta_title'       => 'Contatti | Farmacia Archimede',
    'meta_description' => 'Pagina dei contatti a cui è possibile rivolgersi per informazioni sulla Farmacia Archimede',
    'meta_keywords'    => 'contatti, farmacia, archimede, assistenza, informazioni'],
);
