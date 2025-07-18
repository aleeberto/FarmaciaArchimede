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
$isEdit = ($id !== false && $id !== null);

// 4) Dati di default (nuovo prodotto)
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
    'image_url'             => '',
    'meta_description'      => '',
    'meta_keywords'         => '',
    // placeholder per view
    'meta_title'            => '',
    'page_mode'             => '',
    'submit_label'          => '',
];

// 5) Se sto modificando, carico i dati esistenti
if ($isEdit) {
    $product = $productService->getProductByID($id);
    if (! $product) {
        throw new RuntimeException("Prodotto con ID {$id} non trovato");
    }

    $data = [
        'product_id'       => $product->id,
        'product_type_id'  => $product->productType,
        'short_name'       => $product->shortName,
        'name'             => $product->name,
        'manufacturer'     => $product->manufacturer,
        'aic_code'         => $product->aicCode,
        'format'           => $product->format,
        'price'            => $product->price,
        'availability'     => $product->availability,
        'description'      => $product->description,
        'image_url'        => $product->imagePath,
        'meta_description' => '',
        'meta_keywords'    => '',
        // sovrascrivo solo i placeholder
        'meta_title'       => "Modifica | {$product->shortName}",
        'page_mode'        => "Modifica",
        'submit_label'     => "Modifica",
    ];
} else {
    // Nuovo prodotto
    $data['meta_title']   = "Inserisci Nuovo Prodotto";
    $data['page_mode']    = "Inserisci";
    $data['submit_label'] = "Inserisci";
}

// 6) Eventuali select/options (ad es. tipi e formati)
$data['product_type_options'] = $productService->renderTypeOptions($data['product_type_id']);
$data['format_options']       = $productService->renderFormatOptions($data['format']);

// 7) Mostro la pagina
PageBuilder::show($_SERVER['SCRIPT_NAME'], $data);