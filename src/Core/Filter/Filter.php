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

    public function apply(array &$conditions, array &$params, string &$types): void
    {
        if ($this->search !== '') {
            $conditions[] = 'ShortNome LIKE ?';
            $params[] = "%{$this->search}%";
            $types .= 's';
        }
        if ($this->type !== 'tutte') {
            $conditions[] = 'Tipo = ?';
            $params[] = $this->type;
            $types .= 's';
        }
        if ($this->availability === 'disponibile') {
            $conditions[] = 'Disponibilita > 0';
        } elseif ($this->availability === 'esaurito') {
            $conditions[] = 'Disponibilita = 0';
        }
    }

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

