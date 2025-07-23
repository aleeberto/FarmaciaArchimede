<?php

namespace App\Core;

class Template
{
    private const PATT_BEGIN = '<component>';
    private const PATT_END   = '</component>';

    private string $name;
    private string $state;

    public function __construct(string $name, string $state)
    {
        $this->name  = $name;
        $this->state = $state;
    }

    public function insert(string $id, string|array $value): void
    {
        // Se è oggetto, prima trasformalo in array
        if (is_object($value)) {
            $value = get_object_vars($value);
        }

        if (is_array($value)) {
            // Espandi i figli
            foreach ($value as $k => $v) {
                $this->insert("$id.$k", (string)$v);
            }
            // Rimuovi il placeholder genitore
            $this->state = str_replace(
                self::PATT_BEGIN . $id . self::PATT_END,
                '',
                $this->state
            );
            return;
        }

        // Sostituisci i <component>id</component>
        $this->state = str_replace(
            self::PATT_BEGIN . $id . self::PATT_END,
            $value,
            $this->state
        );

        // Sostituisci anche i {{id}}
        // Uso regex per cogliere eventuali spazi: {{  id  }}
        $this->state = preg_replace(
            '/\{\{\s*' . preg_quote($id, '/') . '\s*\}\}/',
            $value,
            $this->state
        );
    }

    public function insertAll(array $parameters): void
    {
        foreach ($parameters as $id => $value) {
            $this->insert($id, $value);
        }
    }

    public function build(): string
    {
        return $this->state;
    }
}
