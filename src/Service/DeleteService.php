<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use RuntimeException;

class DeleteService
{
    private \mysqli $db;

    public function __construct(Database $database)
    {
        $this->db = $database->connect();
    }

    public function deleteProduct(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM products WHERE product_id = ? LIMIT 1');
        if (!$stmt) {
            throw new RuntimeException("Errore preparazione query prodotti");
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if ($stmt->affected_rows < 1) {
            throw new RuntimeException("Prodotto non trovato o già eliminato.");
        }
        $stmt->close();
    }

    public function deleteOrder(int $id): void
    {

    }

    public function deleteUser(int $id, int $currentUserId): void {
    }
}
