<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Service\ProductPageService;
use App\Core\PageBuilder;

// Configurazione: prodotti per pagina
$itemsPerPage = 12;

$service = new ProductPageService($itemsPerPage);
$service->handleRequest();