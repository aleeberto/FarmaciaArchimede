<?php

namespace App\Core;

class Template
{
    private string $name;
    private string $state;

    public function __construct(string $name, string $state)
    {
        $this->name  = $name;
        $this->state = $state;
    }

    /**
     * Inserisce un valore o un blocco nel template.
     * Supporta:
     * - placeholder semplici: {{ id }}
     * - blocchi con contenuto predefinito:
     *   {{ id }}...{{ /id }}
     */
    public function insert(string $id, string|array|object $value): void
    {
        if (is_object($value)) {
            $value = get_object_vars($value);
        }

        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $this->insert("{$id}.{$k}", (string)$v);
            }
            // Rimuove eventuali placeholder semplici residui
            $this->state = preg_replace(
                '/\{\{\s*' . preg_quote($id, '/') . '\s*\}\}/',
                '',
                $this->state
            );
            return;
        }

        // Pattern blocco: {{ id }} contenuto... {{ /id }}
        $blockPattern = '/\{\{\s*' . preg_quote($id, '/') . '\s*\}\}.*?\{\{\s*\/' . preg_quote($id, '/') . '\s*\}\}/s';
        if (preg_match($blockPattern, $this->state)) {
            // Sostituisce l'intero blocco
            $this->state = preg_replace(
                $blockPattern,
                $value,
                $this->state
            );
            return;
        }

        // Sostituisce placeholder {{ id }}
        $this->state = preg_replace(
            '/\{\{\s*' . preg_quote($id, '/') . '\s*\}\}/',
            $value,
            $this->state
        );
    }

    /**
     * Inserisce più parametri usando insert()
     */
    public function insertAll(array $parameters): void
    {
        foreach ($parameters as $id => $value) {
            $this->insert($id, $value);
        }
    }

    /**
     * Restituisce il contenuto HTML (o testo) presente all'interno di un blocco nel template.
     * Se il blocco non è trovato, restituisce null.
     */
    public function getBlockContent(string $id): ?string
    {
        $pattern = '/\{\{\s*' . preg_quote($id, '/') . '\s*\}\}(.*?)\{\{\s*\/' . preg_quote($id, '/') . '\s*\}\}/s';
        if (preg_match($pattern, $this->state, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }

    /**
     * Restituisce il contenuto processato
     */
    public function build(): string
    {
        return $this->state;
    }
}