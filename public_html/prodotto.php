<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\PageBuilder;
use App\Core\Database;
use App\Core\Image;
use App\Service\ProductService;

$db = Database::getInstance('localhost', 'gbarison','SaSoo9chahNguuCh', 'gbarison');
$service = new ProductService($db);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
if ($id <= 0) { header('Location: prodotti.php'); exit; }

$product = $service->getProductByID($id);
if (!$product) { header('Location: 404.php'); exit; }

$imgDir = rtrim(__DIR__, '/\\') . '/assets/img';
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
$imgBaseUrl = ($scriptDir === '' || $scriptDir === '/') ? '/assets/img' : ($scriptDir . '/assets/img');

$stem = '';
if (!empty($product->imagePath)) {
    $stem = pathinfo(basename((string)$product->imagePath), PATHINFO_FILENAME);
}

$sources = Image::resolvePictureSources($stem, $imgDir, $imgBaseUrl);

$params = [
    'shortName'       => $product->shortName,
    'name'            => $product->name,
    'type'            => $product->productType ?? '',
    'description'     => $product->description,
    'manufacturer'    => $product->manufacturer,
    'code'            => $product->aicCode,
    'availability'    => $product->getAvailability(),
    'price'           => number_format($product->price, 2, ',', '.') . '€',
    // Variabili per <picture>
    'image_webp_url'  => $sources['webp'],
    'image_jpg_url'   => $sources['jpg'],
    'image_alt'       => $product->shortName ?: 'Immagine del prodotto',
    // META
    'meta_title'       => $product->shortName . ' | Prodotti',
    'meta_description' => 'Pagina del prodotto specifico disponibile presso la Farmacia Archimede',
    'meta_keywords'    => 'prodotti, farmacia, archimede, disponibilità'
];

PageBuilder::show('prodotto', $params);
