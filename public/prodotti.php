<?php
// public/prodotti.php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\AuthFacade;
use App\Core\PageBuilder;
use App\Core\Database;
use App\Service\ProductService;

// Se la pagina Prodotti deve essere protetta:
// AuthFacade::requireLogin();

// Inizializza DB e servizio
$db  = Database::getInstance(
    getenv('MARIADB_HOST') ?: 'mariadb',
    getenv('MARIADB_USER') ?: 'admin',
    getenv('MARIADB_PASSWORD') ?: 'admin',
    getenv('MARIADB_DATABASE') ?: 'farmacia_archimede'
);
$service = new ProductService($db);

// Recupera tutti i prodotti
$prodotti = $service->getAllProducts();

// Costruisci HTML degli item
$items = '';
foreach ($prodotti as $prodotto) {
    // ogni item.html usa <component>nome</component>, <component>prezzo</component>, ecc.
    $itemTpl = PageBuilder::getInstance()
        ->loadTemplate('item.html');

    $imm = $prodotto->pathImmagine;
    $path = $imm ?: '/assets/img/default.jpg';
    $alt  = $imm ? 'Immagine del prodotto' : 'Immagine non disponibile';

    $itemTpl->insertAll([
        'nome'      => $prodotto->shortNome,
        'prezzo'    => number_format($prodotto->prezzo, 2, ',', '.') . '€',
        'immagine'  => "<img src='{$path}' alt='{$alt}' width='100' height='100'>",
        'url_farmaco' => "prodotto.php?id={$prodotto->id}",
    ]);
    $items .= $itemTpl->build();
}

PageBuilder::show($_SERVER['SCRIPT_NAME'], [
    'items' => $items,
]);