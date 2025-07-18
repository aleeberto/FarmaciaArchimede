<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Core\Filter\Filter;
use App\Core\Model\ProductDTO;

class ProductService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    // ... metodi esistenti getProducts(), countProducts(), getProductByID(), mapRowToDTO() ...

    /**
     * Recupera i valori ENUM definiti per la colonna `format` nella tabella `products`.
     * @return string[] array di valori (es. ['compresse','capsule',…])
     */
    public function getFormatEnums(): array
    {
        $conn = $this->db->connect();
        // Metodo 1: information_schema
        $sql = "
            SELECT COLUMN_TYPE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'products'
              AND COLUMN_NAME = 'format'
        ";
        $stmt = $conn->prepare($sql);
        if (! $stmt) {
            throw new \RuntimeException('Errore preparazione ENUM: ' . $conn->error);
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (! $row) {
            return [];
        }

        // COLUMN_TYPE è tipo "enum('compresse','capsule',…)"
        $enumDef = $row['COLUMN_TYPE'];
        // estraggo con regex i valori tra apici
        preg_match_all("/'([^']+)'/", $enumDef, $matches);
        return $matches[1] ?? [];
    }

    /**
     * Genera le <option> per il select dei formati, prendendo i valori ENUM dal DB.
     * @param string|null $selectedFormat
     * @return string HTML delle <option>
     */
    public function renderFormatOptions(?string $selectedFormat): string
    {
        $options = '';
        foreach ($this->getFormatEnums() as $value) {
            // qui potresti anche mappare label più leggibili, se vuoi
            $label = ucfirst($value);
            $sel   = ($value === $selectedFormat) ? ' selected' : '';
            $options .= "<option value=\"{$value}\"{$sel}>"
                . htmlspecialchars($label, ENT_QUOTES)
                . "</option>\n";
        }
        return $options;
    }

    /**
     * Recupera tutti i tipi di prodotto (id + nome).
     * @return array<int,string>
     */
    public function getAllProductTypes(): array
    {
        $conn = $this->db->connect();
        $sql  = "SELECT product_type_id, name FROM product_types ORDER BY name";
        $stmt = $conn->prepare($sql);
        if (! $stmt) {
            throw new \RuntimeException('Errore preparazione: ' . $conn->error);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $types = [];
        while ($row = $result->fetch_assoc()) {
            $types[(int)$row['product_type_id']] = $row['name'];
        }
        return $types;
    }

    public function renderTypeOptions($selectedId): string
    {
        $options = '';
        foreach ($this->getAllProductTypes() as $id => $label) {
            $sel = ((string)$id === (string)$selectedId) ? ' selected' : '';
            $options .= "<option value=\"{$id}\"{$sel}>"
                . htmlspecialchars($label, ENT_QUOTES)
                . "</option>\n";
        }
        return $options;
    }
}