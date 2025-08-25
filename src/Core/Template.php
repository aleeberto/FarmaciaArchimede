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
     * Inserisce un valore:
     * - se esiste il blocco {{ id }}...{{ /id }}, sostituisce **tutti** quei blocchi con $value
     * - altrimenti, se esiste il placeholder {{ id }}, sostituisce **tutte** le occorrenze
     * Se $value è array/oggetto, inserisce ricorsivamente "id.k" => v e
     * rimuove eventuali placeholder residui dell’id radice.
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
            // rimuove eventuali placeholder semplici rimasti per l'id radice
            $this->removeAllPlaceholders($id);
            return;
        }

        $value = (string)$value;
        $idRe  = preg_quote($id, '/');

        // 1) sostituisce **tutti** i blocchi {{ id }}...{{ /id }}
        $blockRe = '/\{\{\s*' . $idRe . '\s*\}\}(.*?)\{\{\s*\/\s*' . $idRe . '\s*\}\}/s';
        if (preg_match($blockRe, $this->state)) {
            $this->state = preg_replace($blockRe, $value, $this->state);
            return;
        }

        // 2) sostituisce **tutte** le occorrenze del placeholder semplice {{ id }}
        $phRe = '/\{\{\s*' . $idRe . '\s*\}\}/';
        if (preg_match($phRe, $this->state)) {
            $this->state = preg_replace($phRe, $value, $this->state);
        }
    }

    public function insertAll(array $parameters): void
    {
        foreach ($parameters as $id => $value) {
            $this->insert($id, $value);
        }
    }

    /**
     * Ritorna il contenuto interno del primo blocco {{ id }}...{{ /id }}, oppure null se assente.
     * (Comportamento invariato)
     */
    public function getBlockContent(string $id): ?string
    {
        $idRe = preg_quote($id, '/');
        if (preg_match('/\{\{\s*' . $idRe . '\s*\}\}(.*?)\{\{\s*\/\s*' . $idRe . '\s*\}\}/s', $this->state, $m)) {
            return $m[1];
        }
        return null;
    }

    /**
     * Materializza automaticamente:
     * 1) Tutti i blocchi rimasti: {{ id }}...{{ /id }} -> solo contenuto interno
     * 2) Rimuove placeholder semplici residui {{ qualcosa }}
     * 3) Rimuove eventuali chiusure orfane {{ /qualcosa }}
     */
    public function build(): string
    {
        $out = $this->state;

        // 1) srotola blocchi annidati dal più interno
        $blockAny = '/\{\{\s*([^\s\/][^}]*)\s*\}\}(.*?)\{\{\s*\/\s*\1\s*\}\}/s';
        while (preg_match($blockAny, $out)) {
            $out = preg_replace($blockAny, '$2', $out);
        }

        // 2) rimuove placeholder semplici rimasti
        $out = preg_replace('/\{\{\s*[^\/][^}]*\s*\}\}/', '', $out);

        // 3) rimuove eventuali chiusure orfane
        $out = preg_replace('/\{\{\s*\/[^}]*\}\}/', '', $out);

        return $out;
    }

    /** Rimuove **tutte** le occorrenze del placeholder semplice {{ id }} */
    private function removeAllPlaceholders(string $id): void
    {
        $idRe = preg_quote($id, '/');
        $this->state = preg_replace('/\{\{\s*' . $idRe . '\s*\}\}/', '', $this->state);
    }
}
