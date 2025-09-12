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

    public function deleteUser(int $id, int $currentUserId): void
    {
        if ($id === $currentUserId) {
            throw new RuntimeException("Non puoi eliminare il tuo stesso account.");
        }

        $stmt = $this->db->prepare('SELECT is_admin FROM users WHERE user_id = ?');
        if (!$stmt) {
            throw new RuntimeException("Errore preparazione query ruolo utente corrente.");
        }
        $stmt->bind_param('i', $currentUserId);
        $stmt->execute();
        $stmt->bind_result($currentIsAdmin);
        if (!$stmt->fetch()) {
            $stmt->close();
            throw new RuntimeException("Utente corrente non trovato.");
        }
        $stmt->close();

        if ((int)$currentIsAdmin !== 1) {
            throw new RuntimeException("Solo un amministratore può eliminare utenti.");
        }

        $stmt = $this->db->prepare('SELECT is_admin FROM users WHERE user_id = ?');
        if (!$stmt) {
            throw new RuntimeException("Errore preparazione query utente da eliminare.");
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->bind_result($targetIsAdmin);
        $found = $stmt->fetch();
        $stmt->close();

        if (!$found) {
            throw new RuntimeException("Utente non trovato.");
        }

        if ((int)$targetIsAdmin === 1) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE is_admin = 1 AND user_id <> ?');
            if (!$stmt) {
                throw new RuntimeException("Errore preparazione conteggio amministratori.");
            }
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->bind_result($otherAdmins);
            $stmt->fetch();
            $stmt->close();

            if ((int)$otherAdmins === 0) {
                throw new RuntimeException("Impossibile eliminare l'ultimo amministratore.");
            }
        }

        $this->db->begin_transaction();
        try {

            $stmt = $this->db->prepare('DELETE FROM users WHERE user_id = ? LIMIT 1');
            if (!$stmt) {
                throw new RuntimeException("Errore preparazione DELETE utente.");
            }
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();

            if ($affected < 1) {
                throw new RuntimeException("Utente non trovato o già eliminato.");
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

}
