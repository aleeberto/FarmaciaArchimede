<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Core\Model\UserDTO;
use mysqli;
use Exception;

class AuthService
{
    private mysqli $db;

    public function __construct(Database $database)
    {
        // Ottiene la connessione mysqli dal singleton Database
        $this->db = $database->connect();
    }

    /**
     * Tenta il login con email e password.
     * Restituisce true se avvenuto con successo, false altrimenti.
     */
    public function login(string $email, string $password): bool
    {
        $query = 'SELECT user_id, email, first_name, last_name, tax_code, password_hash, is_admin
                  FROM users
                  WHERE email = ?';
        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            throw new Exception('Errore nella preparazione della query di login.');
        }
        $stmt->bind_param('s', $email);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return false;
        }

        // Verifica password usando password_verify
        if (!password_verify($password, $row['password_hash'])) {
            return false;
        }

        // Crea un UserDTO con i dati
        $userDTO = new UserDTO(
            (int)$row['user_id'],
            $row['email'],
            $row['first_name'],
            $row['last_name'],
            $row['tax_code'],
            (bool)$row['is_admin']
        );

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['user'] = $userDTO;

        return true;
    }

    /**
     * Effettua il logout, distruggendo la sessione.
     */
    public function logout(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        unset($_SESSION['user']);
        session_destroy();
    }

    /**
     * Verifica se esiste un utente loggato.
     */
    public function isLogged(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        return isset($_SESSION['user']) && $_SESSION['user'] instanceof UserDTO;
    }

    /**
     * Restituisce l'utente loggato (istanza di UserDTO) o null.
     */
    public function getUser(): ?UserDTO
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        return $_SESSION['user'] ?? null;
    }

    /**
     * Estrae i dati dell'utente loggato come array.
     */
    public function getUserDataArray(): ?array
    {
        $user = $this->getUser();
        if (!$user) {
            return null;
        }
        return [
            'user_id'    => $user->getId(),
            'email'      => $user->getEmail(),
            'first_name' => $user->getFirstName(),
            'last_name'  => $user->getLastName(),
            'tax_code'   => $user->getTaxCode(),
            'is_admin'   => $user->isAdmin(),
        ];
    }
}
