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
    'localhost', 'gbarison','SaSoo9chahNguuCh', 'gbarison'
);

$service = new ProductService($db);

// Get product ID from query string
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
if ($id <= 0) {
    header('Location: prodotti.php');
    exit;
}

// Fetch the product
$product = $service->getProductByID($id);
if (! $product) {
    header('Location: 404.php');
    exit;
}

$imagePath = $product->imagePath;
$src       = $imagePath ?: 'assets/img/default.jpg';
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
    'image'        => sprintf(
        '<img src="%s" alt="%s" loading="lazy" decoding="async"/>',
        htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($short_name ?? $product->shortName, ENT_QUOTES, 'UTF-8')),
    'meta_title'       => $product->shortName . ' | Prodotti',
    'meta_description' => 'Pagina del prodotto specifico disponibile presso la Farmacia Archimede',
    'meta_keywords'    => 'prodotti, farmacia, archimede, disponibilità'
];

PageBuilder::show('prodotto', $params);
