<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Core\Filter\Filter;
use App\Core\Product\ProductDTO;

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
                    pt.name AS product_type, p.format, p.price, p.availability,
                    p.description, p.image_path
                  FROM products p
                  JOIN product_types pt ON p.product_type_id = pt.product_type_id
                  {$where}
                  LIMIT ? OFFSET ?";

        // Bind parametri filtro + limit/offset
        $types .= 'ii';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $conn->prepare($sql);
        if (! $stmt) {
            throw new \RuntimeException('Errore preparazione: ' . $conn->error);
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
            throw new \RuntimeException('Errore preparazione: ' . $conn->error);
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
        $sql = "SELECT 
                    p.product_id, p.short_name, p.name, p.manufacturer, p.aic_code,
                    pt.name AS product_type, p.format, p.price, p.availability,
                    p.description, p.image_path
                FROM products p
                JOIN product_types pt ON p.product_type_id = pt.product_type_id
                WHERE p.product_id = ?";
        $stmt = $conn->prepare($sql);
        if (! $stmt) {
            throw new \RuntimeException('Errore preparazione: ' . $conn->error);
        }

        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            return $this->mapRowToDTO($row);
        }
        return null;
    }

    /**
     * Mappa una riga di risultato in DTO.
     */
    private function mapRowToDTO(array $row): ProductDTO
    {
        $dto = new ProductDTO();
        $dto->id           = (int)$row['product_id'];
        $dto->shortName    = (string)$row['short_name'];
        $dto->name         = (string)$row['name'];
        $dto->manufacturer = (string)$row['manufacturer'];
        $dto->aicCode      = (string)$row['aic_code'];
        $dto->productType  = (string)$row['product_type'];
        $dto->format       = (string)$row['format'];
        $dto->price        = (float)$row['price'];
        $dto->availability = (int)$row['availability'];
        $dto->description  = (string)$row['description'];
        $dto->imagePath    = '/assets/img/' . $row['image_path'];
        return $dto;
    }
}
