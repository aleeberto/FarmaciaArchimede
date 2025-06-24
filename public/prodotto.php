<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\AuthFacade;
use App\Core\PageBuilder;
use App\Config\Config;
use App\Core\Database;
use App\Service\ProductService;

// Se il dettaglio prodotto deve essere protetto:
// AuthFacade::requireLogin();

// Inizializza DB e servizio
$cfg = Config::get('database');
$db  = Database::getInstance(
    $cfg['host'], $cfg['username'], $cfg['password'], $cfg['dbname']
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
$imm = $service->getProductImage($prodotto->id);
$path = $imm ? $imm->path : '/assets/img/default.jpg';
$alt  = $imm ? $imm->alt  : 'Immagine non disponibile';

// Prepara i parametri per il template
$params = [
    'shortNome'     => $prodotto->shortNome,
    'nome'          => $prodotto->nome,
    'tipo'          => $prodotto->tipo,
    'descrizione'   => $prodotto->descrizione,
    'produttore'    => $prodotto->produttore,
    'codice'        => $prodotto->codice_aic,
    'disponibilita' => (string) $prodotto->disponibilita,
    'prezzo'        => number_format($prodotto->prezzo, 2, ',', '.') . '€',
    'immagine'      => "<img src='{$path}' alt='{$alt}' width='100' height='100'>",
];

// Mostra la pagina "prodotto.html"
PageBuilder::show($_SERVER['SCRIPT_NAME'], $params);
