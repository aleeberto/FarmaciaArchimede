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
     *
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
        $sql   = "SELECT * FROM Prodotto {$where} LIMIT ? OFFSET ?";

        $stmt = $conn->prepare($sql);
        if (! $stmt) {
            throw new \RuntimeException('Errore preparazione: ' . $conn->error);
        }

        // Bind dei parametri per filtro + limit/offset
        if ($types !== '') {
            $types    .= 'ii';
            $params[]  = $limit;
            $params[]  = $offset;
            $stmt->bind_param($types, ...$params);
        } else {
            $stmt->bind_param('ii', $limit, $offset);
        }

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
        $sql   = "SELECT COUNT(*) AS cnt FROM Prodotto {$where}";

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
        $stmt = $conn->prepare('SELECT * FROM Prodotto WHERE ID_prodotto = ?');
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
        $dto->id           = (int)$row['ID_prodotto'];
        $dto->shortName    = (string)$row['ShortNome'];
        $dto->name         = (string)$row['Nome'];
        $dto->manufacturer = (string)$row['Produttore'];
        $dto->aicCode      = (string)$row['Codice_AIC'];
        $dto->type         = (string)$row['Tipo'];
        $dto->price        = (float)$row['Prezzo'];
        $dto->availability = (int)$row['Disponibilita'];
        $dto->description  = (string)$row['Descrizione'];
        $dto->imagePath    = '/assets/img/' . $row['PathImmagine'];
        return $dto;
    }
}