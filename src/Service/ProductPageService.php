<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Core\Filter\Filter;
use App\Core\Filter\Pagination as Paginator;
use App\Core\PageBuilder;

class ProductPageService
{
    private ProductService $service;
    private int            $perPage;

    public function __construct(int $perPage = 10)
    {
        $db              = Database::getInstance(
            getenv('MARIADB_HOST')     ?: 'mariadb',
            getenv('MARIADB_USER')     ?: 'admin',
            getenv('MARIADB_PASSWORD') ?: 'admin',
            getenv('MARIADB_DATABASE') ?: 'farmacia_archimede'
        );
        $this->service   = new ProductService($db);
        $this->perPage   = $perPage;
    }

    public function handleRequest(): void {
        // 1) Parametri GET
        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
        $rawSearch = filter_input(INPUT_GET, 'search', FILTER_UNSAFE_RAW);
        $search    = $rawSearch !== null ? strip_tags($rawSearch) : '';
        $rawType   = filter_input(INPUT_GET, 'tipologia', FILTER_UNSAFE_RAW);
        $type      = $rawType !== null ? strip_tags($rawType) : 'tutte';
        $rawAvail  = filter_input(INPUT_GET, 'disponibilita', FILTER_UNSAFE_RAW);
        $avail     = $rawAvail !== null ? strip_tags($rawAvail) : 'tutti';
        $ajax = filter_input(INPUT_GET, 'ajax', FILTER_VALIDATE_BOOLEAN);

        // 2) Filtro e recupero dati
        $filter = new Filter($search, $type, $avail);
        $offset = ($page - 1) * $this->perPage;
        $products = $this->service->getProducts($this->perPage, $offset, $filter);
        $total    = $this->service->countProducts($filter);
        $pages    = (int)ceil($total / $this->perPage);

        // 3) Costruzione HTML prodotti (invariato)
        $htmlItems = '';
        foreach ($products as $p) {
            $tpl = PageBuilder::getInstance()->loadTemplate('item.html');
            $tpl->insertAll([
                'url_farmaco' => "prodotto.php?id={$p->id}",
                'immagine'    => "<img src=\"{$p->imagePath}\" alt=\"{$p->description}\" width=\"100\" height=\"100\">",
                'nome'        => $p->shortName,
                'prezzo'      => number_format($p->price, 2, ',', '.') . '€',
            ]);
            $htmlItems .= $tpl->build();
        }

        // 4) Paginazione: ottieni dati e markup
        $paginator      = new Paginator(
            $page,
            $pages,
            $this->perPage,
            $total,
            'prodotti.php',
            $filter->toQueryString()
        );
        $pagination = $paginator->getData();
        // carico il partial di paginazione
        $tplPag         = PageBuilder::getInstance()
            ->loadTemplate('pagination.html');
        $tplPag->insertAll([
            'pagination' => $pagination
        ]);
        $htmlPagination = $tplPag->build();

        // 5) Risposta AJAX
        if ($ajax) {
            header('Content-Type: application/json');
            echo json_encode([
                'items'      => $htmlItems,
                'pagination' => $htmlPagination,
            ]);
            exit;
        }

        // 6) Render finale
        PageBuilder::show('prodotti', [
            'items'              => $htmlItems,
            'searchQuery'        => $search,
            'typeFilter'         => $type,
            'availabilityFilter' => $avail,
            'pagination'         => $htmlPagination,
        ]);
    }
}
