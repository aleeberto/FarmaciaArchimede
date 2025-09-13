<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\PageBuilder;

PageBuilder::error(404, [
    'meta_description' => 'La pagina richiesta non è stata trovata. Torna alla home di Farmacia Archimede per continuare la navigazione.',
    'meta_keywords'    => 'errore 404, pagina non trovata, Farmacia Archimede, sito farmacia'
]);