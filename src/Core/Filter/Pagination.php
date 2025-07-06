<?php
declare(strict_types=1);

namespace App\Core\Filter;

use App\Core\PageBuilder;

class Pagination
{
    private int    $currentPage;
    private int    $totalPages;
    private int    $itemsPerPage;
    private int    $totalItems;
    private string $baseUrl;
    private string $queryString;

    public function __construct(
        int    $currentPage,
        int    $totalPages,
        int    $itemsPerPage,
        int    $totalItems,
        string $baseUrl,
        string $queryString = ''
    ) {
        $this->currentPage  = $currentPage;
        $this->totalPages   = $totalPages;
        $this->itemsPerPage = $itemsPerPage;
        $this->totalItems   = $totalItems;
        $this->baseUrl      = $baseUrl;
        $this->queryString  = $queryString;
    }

    public function getStart(): int
    {
        return ($this->currentPage - 1) * $this->itemsPerPage + 1;
    }

    public function getEnd(): int
    {
        return min($this->currentPage * $this->itemsPerPage, $this->totalItems);
    }

    public function hasPrev(): bool
    {
        return $this->currentPage > 1;
    }

    public function hasNext(): bool
    {
        return $this->currentPage < $this->totalPages;
    }

    public function getPrevUrl(): string
    {
        $page = $this->currentPage - 1;
        $qs   = 'page='.$page.($this->queryString?'&'.$this->queryString:'');
        return $this->baseUrl.'?'.$qs;
    }

    public function getNextUrl(): string
    {
        $page = $this->currentPage + 1;
        $qs   = 'page='.$page.($this->queryString?'&'.$this->queryString:'');
        return $this->baseUrl.'?'.$qs;
    }

    /**
     * Carica il template pagination.html, popola i placeholder e restituisce l’HTML.
     */
    public function render(): string
    {
        // se tipo single-page, niente paginazione
        if ($this->totalPages <= 1) {
            return '';
        }

        $tpl = PageBuilder::getInstance()
            ->loadTemplate('pagination.html');

        // preparo i frammenti Prev/Next
        $prevHtml = $this->hasPrev()
            ? '<li><a href="'.$this->getPrevUrl().'">Prev</a></li>'
            : '';
        $nextHtml = $this->hasNext()
            ? '<li><a href="'.$this->getNextUrl().'">Next</a></li>'
            : '';

        $tpl->insertAll([
            'start' => $this->getStart(),
            'end'   => $this->getEnd(),
            'total' => $this->totalItems,
            'prev'  => $prevHtml,
            'next'  => $nextHtml,
        ]);

        return $tpl->build();
    }
}
