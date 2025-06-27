<?php

namespace App\Core;

/**
 * Class Template
 *
 * Gestisce l’inserimento dinamico di componenti all’interno di un template HTML.
 */
class Template
{
    private const PATT_BEGIN = '<component>';
    private const PATT_END   = '</component>';

    private string $name;
    private string $state;

    /**
     * Template constructor.
     *
     * @param string $name   Nome del template
     * @param string $state  Contenuto HTML del template
     */
    public function __construct(string $name, string $state)
    {
        $this->name  = $name;
        $this->state = $state;
    }

    /**
     * Sostituisce un singolo componente identificato da ID.
     *
     * @param string $id     Identificatore del componente
     * @param string $value  HTML da inserire al posto del componente
     */
    public function insert(string $id, string|array $value): void
    {
        if (is_array($value)) {
            // per ogni chiave $k e valore $v dell'array, chiamo di nuovo insert
            foreach ($value as $k => $v) {
                $this->insert("$id.$k", (string)$v);
            }
            return;
        }

        $pattern     = self::PATT_BEGIN . $id . self::PATT_END;
        $this->state = str_replace($pattern, $value, $this->state);
    }

    /**
     * Inserisce più componenti contemporaneamente.
     *
     * @param array<string,string> $parameters  Mappa id => valore
     */
    public function insertAll(array $parameters): void
    {
        foreach ($parameters as $id => $value) {
            $this->insert($id, $value);
        }
    }

    /**
     * Restituisce il template finale (senza placeholder non sostituiti).
     *
     * @return string  HTML risultante
     */
    public function build(): string
    {
        // qui si potrebbero controllare placeholder rimasti
        return $this->state;
    }
}
