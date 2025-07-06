<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Service\ProductPageService;

// Configurazione: prodotti per pagina
$itemsPerPage = 6;

$service = new ProductPageService($itemsPerPage);
$service->handleRequest();