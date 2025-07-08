<?php
declare(strict_types=1);

namespace App\Core\Filter;

class Filter
{
    private string $search;
    private string $type;
    private string $availability;

    public function __construct(string $search = '', string $type = 'tutte', string $availability = 'tutti')
    {
        $this->search = trim($search);
        $this->type = $type;
        $this->availability = $availability;
    }

    /**
     * Applica i filtri, popolando condizioni e parametri per query SQL con JOIN.
     */
    public function apply(array &$conditions, array &$params, string &$types): void
    {
        // Cerca nel nome breve prodotto (case insensitive)
        if ($this->search !== '') {
            $conditions[] = 'p.short_name LIKE ?';
            $params[] = '%' . $this->search . '%';
            $types .= 's';
        }
        // Filtro per tipologia (nome tipo prodotto)
        if ($this->type !== 'tutte') {
            $conditions[] = 'pt.name = ?';
            $params[] = $this->type;
            $types .= 's';
        }
        // Disponibilità
        if ($this->availability === 'disponibile') {
            $conditions[] = 'p.availability > 0';
        } elseif ($this->availability === 'esaurito') {
            $conditions[] = 'p.availability = 0';
        }
    }

    /**
     * Query string per mantenere i filtri su URL
     */
    public function toQueryString(): string
    {
        $qs = [];
        if ($this->search !== '') {
            $qs['search'] = urlencode($this->search);
        }
        if ($this->type !== 'tutte') {
            $qs['tipologia'] = urlencode($this->type);
        }
        if ($this->availability !== 'tutti') {
            $qs['disponibilita'] = urlencode($this->availability);
        }
        return http_build_query($qs);
    }
}