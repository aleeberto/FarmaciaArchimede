<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\PageBuilder;
use App\Core\Database;
use App\Service\ProductService;

// If this page should be protected:
// AuthFacade::requireLogin();

// Initialize DB and service
$db  = Database::getInstance(
    getenv('MARIADB_HOST')     ?: 'mariadb',
    getenv('MARIADB_USER')     ?: 'admin',
    getenv('MARIADB_PASSWORD') ?: 'admin',
    getenv('MARIADB_DATABASE') ?: 'farmacia_archimede'
);
$service = new ProductService($db);

// Get product ID from query string
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
if ($id <= 0) {
    header('Location: /prodotti.php');
    exit;
}

// Fetch the product
$product = $service->getProductByID($id);
if (! $product) {
    header('Location: /prodotti.php');
    exit;
}

$imagePath = $product->imagePath;
$src       = $imagePath ?: '/assets/img/default.jpg';
$alt       = $imagePath ? 'Immagine del prodotto' : 'Immagine non disponibile';

$params = [
    'shortName'    => $product->shortName,
    'name'         => $product->name,
    'type'         => $product->productType ?? '',
    'description'  => $product->description,
    'manufacturer' => $product->manufacturer,
    'code'         => $product->aicCode,
    'availability' => $product->getAvailability(),
    'price'        => number_format($product->price, 2, ',', '.') . '€',
    'image'        => "<img src=\"{$src}\" alt=\"{$alt}\">",
    'meta_title'       => $product->shortName . ' | Prodotti',
    'meta_description' => 'Pagina del prodotto specifico disponibile presso la Farmacia Archimede',
    'meta_keywords'    => 'prodotti, farmacia, archimede, disponibilità'
];

PageBuilder::show($_SERVER['SCRIPT_NAME'], $params);
