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
            'localhost', 'gbarison','SaSoo9chahNguuCh', 'gbarison'
        );
        $this->service = new ProductService($db);
        $this->perPage = $perPage;
    }

    public function handleRequest(): void
    {
        $page      = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
        $rawSearch = filter_input(INPUT_GET, 'search', FILTER_UNSAFE_RAW);
        $search    = $rawSearch !== null ? strip_tags($rawSearch) : '';
        $rawType   = filter_input(INPUT_GET, 'tipologia', FILTER_UNSAFE_RAW);
        $type      = $rawType !== null ? strip_tags($rawType) : 'tutte';
        $rawAvail  = filter_input(INPUT_GET, 'disponibilita', FILTER_UNSAFE_RAW);
        $avail     = $rawAvail !== null ? strip_tags($rawAvail) : 'tutti';
        $ajax      = filter_input(INPUT_GET, 'ajax', FILTER_VALIDATE_BOOLEAN);

        $filter   = new Filter($search, $type, $avail);
        $offset   = ($page - 1) * $this->perPage;
        $products = $this->service->getProducts($this->perPage, $offset, $filter);
        $total    = $this->service->countProducts($filter);
        $pages    = (int)ceil($total / $this->perPage);

        $imgDir = rtrim(dirname($_SERVER['SCRIPT_FILENAME'] ?? __FILE__), '/\\') . '/assets/img';
        $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
        $imgBaseUrl = ($scriptDir === '' || $scriptDir === '/') ? '/assets/img' : ($scriptDir . '/assets/img');

        $htmlItems = '';
        foreach ($products as $p) {
            $tpl = PageBuilder::getInstance()->loadTemplate('prodotti/item.html');

            $imgUrl = self::resolveImageUrl((string)($p->imagePath ?? ''), $imgDir, $imgBaseUrl);
            $alt    = htmlspecialchars($p->shortName ?? ($p->short_name ?? 'Immagine prodotto'), ENT_QUOTES, 'UTF-8');

            $tpl->insertAll([
                'url_farmaco' => "prodotto.php?id={$p->id}",
                'id'          => $p->id,
                'immagine'    => sprintf(
                    '<img src="%s" alt="%s" width="100" height="100" loading="lazy" decoding="async"/>',
                    htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8'),
                    $alt
                ),
                'nome'        => $p->shortName,
                'prezzo'      => number_format($p->price, 2, ',', '.') . '€',
            ]);
            $htmlItems .= $tpl->build();
        }

        $htmlPagination = $this->buildPagination($page, $pages, $total, $filter);

        $productTypes = $this->getProductTypes();
        $typeOptions  = '<option value="tutte"' . ($type === 'tutte' ? ' selected' : '') . '>Tutte</option>';
        foreach ($productTypes as $t) {
            $sel = ($type === $t) ? ' selected' : '';
            $typeOptions .= '<option value="' . htmlspecialchars($t, ENT_QUOTES, 'UTF-8') . '"' . $sel . '>' . htmlspecialchars($t, ENT_QUOTES, 'UTF-8') . '</option>';
        }

        $checkedTutti       = ($avail === 'tutti') ? 'checked' : '';
        $checkedDisponibile = ($avail === 'disponibile') ? 'checked' : '';
        $checkedEsaurito    = ($avail === 'esaurito') ? 'checked' : '';

        $info = $this->getInfo($total, $this->perPage, $page);

        if ($ajax) {
            header('Content-Type: application/json');
            echo json_encode([
                'items'      => $htmlItems,
                'pagination' => $htmlPagination,
                'info'       => $info,
            ]);
            exit;
        }

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
            'meta_title'         => 'Prodotti | Farmacia Archimede',
            'meta_description'   => 'Pagina di presentazione dei prodotti disponibili presso la Farmacia Archimede',
            'meta_keywords'      => 'prodotti, farmacia, archimede, disponibilità'
        ]);
    }

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

        $blocks = ['prev' => '', 'next' => ''];

        if ($pagination['currentPage'] > 1) {
            $tplPrev = PageBuilder::getInstance()->loadTemplate('prodotti/pagination-prev.html');
            $tplPrev->insertAll(['href' => htmlspecialchars($pagination['prevHref'], ENT_QUOTES, 'UTF-8')]);
            $blocks['prev'] = $tplPrev->build();
        }

        if ($pagination['currentPage'] < $pages) {
            $tplNext = PageBuilder::getInstance()->loadTemplate('prodotti/pagination-next.html');
            $tplNext->insertAll(['href' => htmlspecialchars($pagination['nextHref'], ENT_QUOTES, 'UTF-8')]);
            $blocks['next'] = $tplNext->build();
        }

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

    /** @return string[] */
    private function getProductTypes(): array
    {
        $db = Database::getInstance(
            'localhost', 'gbarison','SaSoo9chahNguuCh', 'gbarison'
        )->connect();

        $res = $db->query('SELECT name FROM product_types ORDER BY name');
        $types = [];
        while ($row = $res->fetch_assoc()) {
            $types[] = $row['name'];
        }
        return $types;
    }

    private function getInfo(int $total, int $perPage, int $currentPage): string
    {
        if ($total === 0) return "Nessun risultato trovato";
        $start = ($currentPage - 1) * $perPage + 1;
        $end   = min($start + $perPage - 1, $total);
        return "Risultati {$start}–{$end} di {$total}";
    }

    /**
     * Se manca l’estensione, costruisce automaticamente src provando .webp poi .jpg.
     * Se non trova nulla, usa il placeholder.
     */
    private static function resolveImageUrl(string $imagePath, string $imgDir, string $imgBaseUrl): string
    {
        $base = $imagePath !== '' ? basename($imagePath) : '';
        $stem = $base !== '' ? pathinfo($base, PATHINFO_FILENAME) : '';
        $ext  = $base !== '' ? strtolower((string)pathinfo($base, PATHINFO_EXTENSION)) : '';

        $candidates = [];

        if ($ext === 'webp' || $ext === 'jpg' || $ext === 'jpeg') {
            $ext = ($ext === 'jpeg') ? 'jpg' : $ext;
            if ($stem !== '') {
                $candidates[] = $stem . '.' . $ext;
            }
        } elseif ($stem !== '') {
            $candidates[] = $stem . '.webp';
            $candidates[] = $stem . '.jpg';
        }

        foreach ($candidates as $fn) {
            $fs = rtrim($imgDir, '/\\') . DIRECTORY_SEPARATOR . $fn;
            if (is_file($fs)) {
                return rtrim($imgBaseUrl, '/') . '/' . $fn;
            }
        }

        // Placeholder di fallback
        $phWebp = rtrim($imgDir, '/\\') . DIRECTORY_SEPARATOR . 'placeholder.webp';
        if (is_file($phWebp)) {
            return rtrim($imgBaseUrl, '/') . '/placeholder.webp';
        }
        return rtrim($imgBaseUrl, '/') . '/placeholder.jpg';
    }
}
