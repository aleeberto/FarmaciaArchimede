<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Core\Filter\Filter;
use App\Core\Model\ProductDTO;
use RuntimeException;

class ProductService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Recupera un blocco di prodotti con filtri, LIMIT e OFFSET.
     * @return ProductDTO[]
     */
    public function getProducts(int $limit, int $offset, Filter $filter): array
    {
        $conn   = $this->db->connect();
        $conds  = [];
        $params = [];
        $types  = '';

        $filter->apply($conds, $params, $types);

        $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
        $sql   = "SELECT 
                    p.product_id, p.short_name, p.name, p.manufacturer, p.aic_code,
                    p.product_type_id, pt.name AS product_type, p.format, p.price, p.availability,
                    p.description, p.image_path
                  FROM products p
                  JOIN product_types pt ON p.product_type_id = pt.product_type_id
                  {$where}
                  LIMIT ? OFFSET ?";

        $types    .= 'ii';
        $params[]  = $limit;
        $params[]  = $offset;

        $stmt = $conn->prepare($sql);
        if (! $stmt) {
            throw new RuntimeException('Errore preparazione: ' . $conn->error);
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $this->mapRowToDTO($row);
        }
        return $products;
    }

    /**
     * Conta tutti i prodotti che soddisfano il filtro (senza LIMIT).
     */
    public function countProducts(Filter $filter): int
    {
        $conn   = $this->db->connect();
        $conds  = [];
        $params = [];
        $types  = '';

        $filter->apply($conds, $params, $types);

        $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
        $sql   = "SELECT COUNT(*) AS cnt
                  FROM products p
                  JOIN product_types pt ON p.product_type_id = pt.product_type_id
                  {$where}";

        $stmt = $conn->prepare($sql);
        if (! $stmt) {
            throw new RuntimeException('Errore preparazione: ' . $conn->error);
        }
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return (int)$row['cnt'];
    }

    /**
     * Recupera un singolo prodotto per ID.
     */
    public function getProductByID(int $id): ?ProductDTO
    {
        $conn = $this->db->connect();
        $sql  = "SELECT 
                    p.product_id, p.short_name, p.name, p.manufacturer, p.aic_code,
                    p.product_type_id, pt.name AS product_type, p.format, p.price, p.availability,
                    p.description, p.image_path
                 FROM products p
                 JOIN product_types pt ON p.product_type_id = pt.product_type_id
                 WHERE p.product_id = ?";
        $stmt = $conn->prepare($sql);
        if (! $stmt) {
            throw new RuntimeException('Errore preparazione: ' . $conn->error);
        }

        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        return $row ? $this->mapRowToDTO($row) : null;
    }

    /**
     * Mappa una riga di risultato in DTO.
     */
    private function mapRowToDTO(array $row): ProductDTO
    {
        $dto = new ProductDTO();
        $dto->id             = (int)$row['product_id'];
        $dto->shortName      = (string)$row['short_name'];
        $dto->name           = (string)$row['name'];
        $dto->manufacturer   = (string)$row['manufacturer'];
        $dto->aicCode        = (string)$row['aic_code'];
        $dto->productTypeId  = (int)$row['product_type_id'];
        $dto->productType    = (string)$row['product_type'];
        $dto->format         = (string)$row['format'];
        $dto->price          = (float)$row['price'];
        $dto->availability   = (int)$row['availability'];
        $dto->description    = (string)$row['description'];
        $dto->imagePath      = 'assets/img/' . $row['image_path'];
        return $dto;
    }

    /**
     * Recupera dinamicamente tutti i tipi di prodotto.
     * @return array<int,string>  [product_type_id => nome]
     */
    public function getAllProductTypes(): array
    {
        $conn = $this->db->connect();
        $sql  = "SELECT product_type_id, name FROM product_types ORDER BY name";
        $stmt = $conn->prepare($sql);
        if (! $stmt) {
            throw new RuntimeException('Errore preparazione: ' . $conn->error);
        }
        $stmt->execute();
        $res = $stmt->get_result();

        $types = [];
        while ($row = $res->fetch_assoc()) {
            $types[(int)$row['product_type_id']] = $row['name'];
        }
        return $types;
    }

    /**
     * Genera l’HTML <option> per i tipi prodotto.
     */
    public function renderTypeOptions($selectedId): string
    {
        $html = '';
        foreach ($this->getAllProductTypes() as $id => $label) {
            $sel = ((string)$id === (string)$selectedId) ? ' selected' : '';
            $html .= "<option value=\"{$id}\"{$sel}>"
                . htmlspecialchars($label, ENT_QUOTES)
                . "</option>\n";
        }
        return $html;
    }

    /**
     * Legge i valori dell'ENUM 'format' dalla colonna di DB.
     * @return string[] Lista di valori (es. ['compresse', ...])
     */
    private function getFormatValues(): array
    {
        $conn = $this->db->connect();
        $sql  = "SHOW COLUMNS FROM products WHERE Field = 'format'";
        $stmt = $conn->prepare($sql);
        if (! $stmt) {
            throw new RuntimeException('Errore preparazione: ' . $conn->error);
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (! $row) {
            throw new RuntimeException("Colonna 'format' non trovata");
        }

        preg_match_all("/'([^']+)'/", $row['Type'], $matches);
        return $matches[1] ?? [];
    }

    /**
     * Genera l’HTML <option> per i formati, basandosi sui valori ENUM.
     */
    public function renderFormatOptions($selectedFormat): string
    {
        $html = '';
        foreach ($this->getFormatValues() as $value) {
            $label = ucfirst($value);
            $sel   = ($value === $selectedFormat) ? ' selected' : '';
            $html .= "<option value=\"{$value}\"{$sel}>"
                . htmlspecialchars($label, ENT_QUOTES)
                . "</option>\n";
        }
        return $html;
    }

    /**
     * Inserisce un nuovo prodotto nel database.
     * Restituisce l'ID appena creato.
     *
     * @param array $data
     *  - product_type_id
     *  - short_name
     *  - name
     *  - manufacturer
     *  - aic_code
     *  - format
     *  - price
     *  - availability
     *  - description
     *  - image_path (opzionale)
     * @return int
     */
    public function insertProduct(array $data): int
    {
        $conn = $this->db->connect();
        $sql = "INSERT INTO products
        (product_type_id, short_name, name, manufacturer, aic_code, format, price, availability, description, image_path)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if (! $stmt) {
            throw new RuntimeException('Errore preparazione: ' . $conn->error);
        }

        $imagePath = $data['image_path'] ?? null;

        $stmt->bind_param(
            'issssssiss',
            $data['product_type_id'],
            $data['short_name'],
            $data['name'],
            $data['manufacturer'],
            $data['aic_code'],
            $data['format'],
            $data['price'],
            $data['availability'],
            $data['description'],
            $imagePath
        );

        if (! $stmt->execute()) {
            throw new RuntimeException('Errore esecuzione: ' . $stmt->error);
        }

        return $conn->insert_id;
    }

    /**
     * Aggiorna un prodotto esistente.
     * @param int $id
     * @param array $data
     * @return bool true se almeno una riga modificata
     */
    public function updateProduct(int $id, array $data): bool
    {
        $conn = $this->db->connect();
        $sql = "UPDATE products SET
                    product_type_id = ?,
                    short_name      = ?,
                    name            = ?,
                    manufacturer    = ?,
                    aic_code        = ?,
                    format          = ?,
                    price           = ?,
                    availability    = ?,
                    description     = ?,
                    image_path      = ?
                WHERE product_id = ?";
        $stmt = $conn->prepare($sql);
        if (! $stmt) {
            throw new \RuntimeException('Errore preparazione: ' . $conn->error);
        }
        $imagePath = $data['image_path'] ?? null;
        $stmt->bind_param(
            'issssssissi',
            $data['product_type_id'],
            $data['short_name'],
            $data['name'],
            $data['manufacturer'],
            $data['aic_code'],
            $data['format'],
            $data['price'],
            $data['availability'],
            $data['description'],
            $imagePath,
            $id
        );
        if (! $stmt->execute()) {
            throw new \RuntimeException('Errore esecuzione: ' . $stmt->error);
        }
        return $stmt->affected_rows > 0;
    }

    public function existsAicCode(string $aicCode, ?int $excludeId = null): bool
    {
        $conn = $this->db->connect();

        $sql    = 'SELECT COUNT(*) AS cnt FROM products WHERE aic_code = ?';
        $types  = 's';
        $params = [$aicCode];

        if ($excludeId !== null) {
            $sql   .= ' AND product_id <> ?';
            $types .= 'i';
            $params[] = $excludeId;
        }

        $stmt = $conn->prepare($sql);
        if (! $stmt) {
            throw new \RuntimeException('Errore prepare in existsAicCode: ' . $conn->error);
        }

        $stmt->bind_param($types, ...$params);

        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();
        if (! $row) {
            throw new \RuntimeException('Errore recupero risultato in existsAicCode');
        }

        return ((int)$row['cnt']) > 0;
    }
}