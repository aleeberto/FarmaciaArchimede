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
    private int $perPage;

    public function __construct(int $perPage = 6)
    {
        $db = Database::getInstance(
            'localhost', 'gabrison','SaSoo9chahNguuCh', 'gabrison'
        );
        $this->service = new ProductService($db);
        $this->perPage = $perPage;
    }

    public function handleRequest(): void
    {
        // 1) Parametri GET
        $page      = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
        $rawSearch = filter_input(INPUT_GET, 'search', FILTER_UNSAFE_RAW);
        $search    = $rawSearch !== null ? strip_tags($rawSearch) : '';
        $rawType   = filter_input(INPUT_GET, 'tipologia', FILTER_UNSAFE_RAW);
        $type      = $rawType !== null ? strip_tags($rawType) : 'tutte';
        $rawAvail  = filter_input(INPUT_GET, 'disponibilita', FILTER_UNSAFE_RAW);
        $avail     = $rawAvail !== null ? strip_tags($rawAvail) : 'tutti';
        $ajax      = filter_input(INPUT_GET, 'ajax', FILTER_VALIDATE_BOOLEAN);

        // 2) Filtro e recupero dati
        $filter   = new Filter($search, $type, $avail);
        $offset   = ($page - 1) * $this->perPage;
        $products = $this->service->getProducts($this->perPage, $offset, $filter);
        $total    = $this->service->countProducts($filter);
        $pages    = (int)ceil($total / $this->perPage);

        // 3) Costruzione HTML prodotti
        $htmlItems = '';
        foreach ($products as $p) {
            $tpl = PageBuilder::getInstance()->loadTemplate('prodotti/item.html');
            $tpl->insertAll([
                'url_farmaco' => "prodotto.php?id={$p->id}",
                'id' => $p->id,
                'immagine' => sprintf(
                    '<img src="%s" alt="%s" width="100" height="100" loading="lazy" decoding="async"/>',
                    htmlspecialchars($p->imagePath, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($p->short_name ?? $p->shortName, ENT_QUOTES, 'UTF-8')
                ),

                'nome'        => $p->shortName,
                'prezzo'      => number_format($p->price, 2, ',', '.') . '€',
            ]);
            $htmlItems .= $tpl->build();
        }

        // 4) Paginazione markup modulare
        $htmlPagination = $this->buildPagination($page, $pages, $total, $filter);

        // 5) Tipi prodotto dal DB (per la select)
        $productTypes = $this->getProductTypes();
        $typeOptions  = '<option value="tutte"' . ($type === 'tutte' ? ' selected' : '') . '>Tutte</option>';
        foreach ($productTypes as $t) {
            $sel = ($type === $t) ? ' selected' : '';
            $typeOptions .= '<option value="' . htmlspecialchars($t) . '"' . $sel . '>' . htmlspecialchars($t) . '</option>';
        }

        // 6) Radio "Disponibilità" checked dinamico
        $checkedTutti       = ($avail === 'tutti') ? 'checked' : '';
        $checkedDisponibile = ($avail === 'disponibile') ? 'checked' : '';
        $checkedEsaurito    = ($avail === 'esaurito') ? 'checked' : '';

        // 7) Frase dinamica per i risultati (variabile 'info')
        $info = $this->getInfo($total, $this->perPage, $page);

        // 8) Risposta AJAX
        if ($ajax) {
            header('Content-Type: application/json');
            echo json_encode([
                'items'      => $htmlItems,
                'pagination' => $htmlPagination,
                'info'       => $info,
            ]);
            exit;
        }

        // 9) Render finale
        PageBuilder::show('prodotti', [
            'items'              => $htmlItems,
            'searchQuery'        => $search,
            'typeFilter'         => $type,
            'availabilityFilter' => $avail,
            'pagination'         => $htmlPagination,
            'info'               => $info,
            'typeOptions'        => $typeOptions,
            'checkedTutti'       => $checkedTutti,
            'checkedDisponibile' => $checkedDisponibile,
            'checkedEsaurito'    => $checkedEsaurito,
            'meta_title'       => 'Prodotti | Farmacia Archimede',
            'meta_description' => 'Pagina di presentazione dei prodotti disponibili presso la Farmacia Archimede',
            'meta_keywords'    => 'prodotti, farmacia, archimede, disponibilità'
        ]);
    }

    /**
     * Costruisce la paginazione tramite template separati.
     */
    private function buildPagination(int $page, int $pages, int $total, Filter $filter): string
    {
        $paginator = new Paginator(
            $page,
            $pages,
            $this->perPage,
            $total,
            'prodotti.php',
            $filter->toQueryString()
        );
        $pagination = $paginator->getData();

        if ($pages <= 1) {
            return '';
        }

        $blocks = [
            'prev'   => '',
            'next'   => '',
        ];

        // Prev
        if ($pagination['currentPage'] > 1) {
            $tplPrev = PageBuilder::getInstance()->loadTemplate('prodotti/pagination-prev.html');
            $tplPrev->insertAll([
                'href' => htmlspecialchars($pagination['prevHref'])
            ]);
            $blocks['prev'] = $tplPrev->build();
        }

        // Next
        if ($pagination['currentPage'] < $pages) {
            $tplNext = PageBuilder::getInstance()->loadTemplate('prodotti/pagination-next.html');
            $tplNext->insertAll([
                'href' => htmlspecialchars($pagination['nextHref'])
            ]);
            $blocks['next'] = $tplNext->build();
        }

        // Template principale
        $tplPag = PageBuilder::getInstance()->loadTemplate('prodotti/pagination.html');
        $tplPag->insertAll([
            'start'       => $pagination['start'],
            'end'         => $pagination['end'],
            'total'       => $pagination['total'],
            'prev'        => $blocks['prev'],
            'currentPage' => $pagination['currentPage'],
            'next'        => $blocks['next'],
        ]);
        return $tplPag->build();
    }

    /**
     * Recupera tutti i tipi di prodotto dal DB
     * @return string[]
     */
    private function getProductTypes(): array
    {
        $db = Database::getInstance(
            'localhost', 'gabrison','SaSoo9chahNguuCh', 'gabrison'
        )->connect();

        $res = $db->query('SELECT name FROM product_types ORDER BY name');
        $types = [];
        while ($row = $res->fetch_assoc()) {
            $types[] = $row['name'];
        }
        return $types;
    }

    /**
     * Frase dinamica per la paginazione
     */
    private function getInfo(int $total, int $perPage, int $currentPage): string
    {
        if ($total === 0) {
            return "Nessun risultato trovato";
        }

        $start = ($currentPage - 1) * $perPage + 1;
        $end   = min($start + $perPage - 1, $total);

        if ($total === 1) {
            return "Risultati 1 di 1";
        }

        if ($total <= $perPage) {
            return "Risultati {$start}–{$end} di {$total}";
        }

        return "Risultati {$start}–{$end} di {$total}";
    }

}