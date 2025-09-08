<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Core\Model\UserDTO;
use mysqli;
use Exception;

class AuthService
{
    /** Conserva sia il wrapper Database sia la connessione mysqli */
    private Database $database;
    private mysqli $mysqli;

    public function __construct(Database $database)
    {
        $this->database = $database;
        $this->mysqli   = $database->connect();
    }

    public function login(string $email, string $password): bool
    {
        $stmt = $this->mysqli->prepare(
            'SELECT user_id, email, first_name, last_name, tax_code, password_hash, is_admin
             FROM users WHERE email = ? LIMIT 1'
        );
        if (!$stmt) {
            throw new Exception('Errore nella preparazione della query di login.');
        }
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res?->fetch_assoc();
        $stmt->close();

        if (!$row || !password_verify($password, $row['password_hash'])) {
            return false;
        }

        $userDTO = new UserDTO(
            (int)$row['user_id'],
            (string)$row['email'],
            (string)$row['first_name'],
            (string)$row['last_name'],
            (string)$row['tax_code'],
            (bool)$row['is_admin']
        );

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['user'] = $userDTO;
        return true;
    }

    public function logout(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        unset($_SESSION['user']);
        session_destroy();
    }

    public function isLogged(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        return isset($_SESSION['user']) && $_SESSION['user'] instanceof UserDTO;
    }


    public function getUser(): ?UserDTO
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        return $_SESSION['user'] ?? null;
    }

    public function getUserDataArray(): ?array
    {
        $user = $this->getUser();
        if (!$user) return null;
        return [
            'user_id'    => $user->getId(),
            'email'      => $user->getEmail(),
            'first_name' => $user->getFirstName(),
            'last_name'  => $user->getLastName(),
            'tax_code'   => $user->getTaxCode(),
            'is_admin'   => $user->isAdmin(),
        ];
    }

    public function reloadUserFromDbAndSyncSession(): void
    {
        $user = $this->getUser();
        if (!$user instanceof UserDTO) {
            return; // non autenticato
        }
        $uid = $user->getId();

        // Usa la connessione già pronta
        $stmt = $this->mysqli->prepare(
            'SELECT user_id, email, first_name, last_name, tax_code, is_admin
             FROM users WHERE user_id = ? LIMIT 1'
        );
        if (!$stmt) {
            throw new Exception('Errore sistema (prep reload user).');
        }
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res?->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return;
        }

        // Mantieni lo stesso ordine del costruttore usato nel login
        $updated = new UserDTO(
            (int)$row['user_id'],
            (string)$row['email'],
            (string)$row['first_name'],
            (string)$row['last_name'],
            (string)$row['tax_code'],
            (bool)$row['is_admin']
        );

        if (session_status() !== PHP_SESSION_ACTIVE) {
            // (opzionale) verifica path sessioni se hai avuto problemi
            // if ($p = session_save_path()) { if (!is_dir($p)) { throw new \RuntimeException('Session path inesistente: '.$p); } }
            session_start();
        }

        $_SESSION['user'] = $updated;

        // Hardening opzionale
        if (PHP_SAPI !== 'cli') {
            session_regenerate_id(true);
            // session_write_close(); // chiudi se vuoi forzare la scrittura immediata
        }
    }
}
