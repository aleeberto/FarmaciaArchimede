<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\PageBuilder;

PageBuilder::show($_SERVER['SCRIPT_NAME'], [
    'meta_title'       => 'Contatti | Farmacia Archimede',
    'meta_description' => 'Pagina dei contatti a cui è possibile rivolgersi per informazioni sulla Farmacia Archimede',
    'meta_keywords'    => 'contatti, farmacia, archimede, assistenza, informazioni'],
safe: true);
