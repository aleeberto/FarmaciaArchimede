<?php
declare(strict_types=1);

namespace App\Core\Filter;

class Pagination
{
    private int $currentPage;
    private int $lastPage;
    private int $perPage;
    private int $total;
    private string $baseUrl;
    private string $queryString;
    private int $start;
    private int $end;

    public function __construct(
        int $currentPage,
        int $lastPage,
        int $perPage,
        int $total,
        string $baseUrl,
        string $queryString
    ) {
        $this->currentPage = $currentPage;
        $this->lastPage    = $lastPage;
        $this->perPage     = $perPage;
        $this->total       = $total;
        $this->baseUrl     = $baseUrl;
        $this->queryString = $queryString;

        $this->start = ($currentPage - 1) * $perPage + 1;
        $this->end   = min($this->start + $perPage - 1, $total);
    }

    private function buildUrl(int $page): string
    {
        parse_str(ltrim($this->queryString, '?'), $params);
        $qs = http_build_query(array_merge(['page' => $page], $params));
        return "{$this->baseUrl}?{$qs}";
    }

    /**
     * Restituisce i dati per il template di paginazione.
     * Nessun HTML qui, solo dati pronti.
     */
    public function getData(): array
    {
        $hasPrev = $this->currentPage > 1;
        $hasNext = $this->currentPage < $this->lastPage;

        return [
            'start'       => $this->start,
            'end'         => $this->end,
            'total'       => $this->total,
            'currentPage' => $this->currentPage,
            'lastPage'    => $this->lastPage,
            'baseUrl'     => $this->baseUrl,
            'queryString' => $this->queryString ? '&' . ltrim($this->queryString, '?') : '',
            'prevHref'    => $hasPrev ? $this->buildUrl($this->currentPage - 1) : '#',
            'nextHref'    => $hasNext ? $this->buildUrl($this->currentPage + 1) : '#',
            'prevClass'   => $hasPrev ? '' : 'disabled',
            'nextClass'   => $hasNext ? '' : 'disabled',
        ];
    }
}