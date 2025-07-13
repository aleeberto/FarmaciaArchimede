<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Core\PageBuilder;
use App\Service\ProductService;
use App\Core\Auth;

// 1) Proteggi la rotta
Auth::requireLogin();
Auth::requireAdmin();

// 2) Connessione al DB e servizio
$db             = Database::getInstance(
    getenv('MARIADB_HOST')     ?: 'mariadb',
    getenv('MARIADB_USER')     ?: 'admin',
    getenv('MARIADB_PASSWORD') ?: 'admin',
    getenv('MARIADB_DATABASE') ?: 'farmacia_archimede'
);
$productService = new ProductService($db);

// 3) Controllo del parametro GET `id`
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// 4) Preparo dati di default (nuovo prodotto)
$data = [
    'product_id'            => '',
    'product_type_id'       => '',
    'short_name'            => '',
    'name'                  => '',
    'manufacturer'          => '',
    'aic_code'              => '',
    'format'                => '',
    'price'                 => '',
    'availability'          => '',
    'description'           => '',
    // eventuale anteprima immagine
    'image_url'             => '',
    'meta_title'            => 'Nuovo Prodotto',
    'meta_description'      => '',
    'meta_keywords'         => '',
];

// 5) Se ho un ID valido, carico il prodotto
if ($id !== false && $id !== null) {
    $product = $productService->getProductByID($id);
    if (! $product) {
        throw new RuntimeException("Prodotto con ID {$id} non trovato");
    }
    $data = [
        'product_id'       => $product->id,
        'product_type_id'  => $product->productType, // se hai solo nome, vedi nota in fondo
        'short_name'       => $product->shortName,
        'name'             => $product->name,
        'manufacturer'     => $product->manufacturer,
        'aic_code'         => $product->aicCode,
        'format'           => $product->format,
        'price'            => $product->price,
        'availability'     => $product->availability,
        'description'      => $product->description,
        'image_url'        => $product->imagePath,
        'meta_title'       => "Modifica | {$product->shortName}",
        'meta_description' => '',
        'meta_keywords'    => '',
    ];
}

PageBuilder::show($_SERVER['SCRIPT_NAME'], $data);
