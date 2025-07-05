<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\AuthFacade;
use App\Core\PageBuilder;
use App\Core\Database;
use App\Service\ProductService;

// Se il dettaglio prodotto deve essere protetto:
// AuthFacade::requireLogin();

// Inizializza DB e servizio
$db  = Database::getInstance(
    getenv('MARIADB_HOST') ?: 'mariadb',
    getenv('MARIADB_USER') ?: 'admin',
    getenv('MARIADB_PASSWORD') ?: 'admin',
    getenv('MARIADB_DATABASE') ?: 'farmacia_archimede'
);
$service = new ProductService($db);

// Ottieni ID prodotto da query string
$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: /prodotti.php');
    exit;
}

// Recupera dati prodotto e immagine
$prodotto = $service->getProductByID($id);
if (! $prodotto) {
    header('Location: /prodotti.php');
    exit;
}

$imm = $prodotto->pathImmagine;
$path = $imm ?: '/assets/img/default.jpg';
$alt  = $imm ? 'Immagine del prodotto' : 'Immagine non disponibile';

// Prepara i parametri per il template
$params = [
    'shortNome'     => $prodotto->shortNome,
    'nome'          => $prodotto->nome,
    'tipo'          => $prodotto->tipo,
    'descrizione'   => $prodotto->descrizione,
    'produttore'    => $prodotto->produttore,
    'codice'        => $prodotto->codice_aic,
    'disponibilita' => $prodotto->getDisponibilita(),
    'prezzo'        => number_format($prodotto->prezzo, 2, ',', '.') . '€',
    'immagine'      => "<img src='{$path}'>",
];

// Mostra la pagina "prodotto.html"
PageBuilder::show($_SERVER['SCRIPT_NAME'], $params);
