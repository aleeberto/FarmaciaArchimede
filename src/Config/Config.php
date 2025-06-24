<?php
namespace App\Config;

class Config
{
    /** @var array<string,mixed> */
    private static array $data = [];

    /**
     * Carica tutti i file PHP nella cartella src/Config (escluse classi stesse)
     * e li mette in self::$data sotto la chiave basename(file).
     */
    private static function loadAll(): void
    {
        if (!empty(self::$data)) {
            return;
        }

        // Punto direttamente qui dentro, dove si trova Config.php
        $configDir = __DIR__;

        foreach (glob($configDir . '/*.php') as $file) {
            // Se è proprio questo file Config.php, lo salto
            if ($file === __FILE__) {
                continue;
            }

            $key = pathinfo($file, PATHINFO_FILENAME);
            self::$data[$key] = require $file;
        }
    }

    /**
     * Restituisce un valore di configurazione usando la chiave "file.chiave1.chiave2..."
     *
     * Esempi:
     *   Config::get('paths.templates');
     *   Config::get('database.host');
     */
    public static function get(string $fullKey, $default = null)
    {
        self::loadAll();

        $parts   = explode('.', $fullKey);
        $section = array_shift($parts);

        if (!isset(self::$data[$section]) || !is_array(self::$data[$section])) {
            return $default;
        }

        $value = self::$data[$section];
        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }

        return $value;
    }
}
