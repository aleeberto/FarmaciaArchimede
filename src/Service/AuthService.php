<?php

namespace App\Service;

use App\Core\Database;
use Exception;

class AuthService
{
    private const SESSION_USER = 'user';
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Tenta il login con email e password.
     */
    public function login(string $email, string $password): bool
    {
        $conn = $this->db->connect();
        $stmt = $conn->prepare(
            'SELECT user_id, email, password_hash, first_name, last_name, tax_code, is_admin FROM users WHERE email = ?'
        );
        if (!$stmt) {
            throw new Exception('Errore nella preparazione della query di login.');
        }
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION[self::SESSION_USER] = [
                    'user_id'    => $user['user_id'],
                    'email'      => $user['email'],
                    'first_name' => $user['first_name'],
                    'last_name'  => $user['last_name'],
                    'tax_code'   => $user['tax_code'],
                    'is_admin'   => (bool)$user['is_admin'],
                ];
                return true;
            }
        }
        return false;
    }

    public function isLogged(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION[self::SESSION_USER]);
    }

    public function getUser(): ?array
    {
        return $this->isLogged() ? $_SESSION[self::SESSION_USER] : null;
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION[self::SESSION_USER]);
        session_destroy();
    }
}