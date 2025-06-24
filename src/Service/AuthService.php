<?php

namespace App\Service;

use App\Core\Database;
use Exception;

/**
 * Classe AuthService: gestisce autenticazione, sessioni e logout.
 */
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
     *
     * @param string $email
     * @param string $password
     * @return bool
     * @throws Exception
     */
    public function login(string $email, string $password): bool
    {
        $conn = $this->db->connect();
        $stmt = $conn->prepare(
            'SELECT Email, Psw, Nome, Cognome, CF FROM Utente WHERE Email = ?'
        );
        if (!$stmt) {
            throw new Exception('Errore nella preparazione della query di login.');
        }
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {

            if (password_verify($password, $user['Psw'])) {
                $_SESSION[self::SESSION_USER] = [
                    'email'   => $user['Email'],
                    'nome'    => $user['Nome'],
                    'cognome' => $user['Cognome'],
                    'cf'      => $user['CF'],
                ];
                return true;
            }
        }
        return false;
    }

    /**
     * Verifica se l'utente è autenticato.
     *
     * @return bool
     */
    public function isLogged(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION[self::SESSION_USER]);
    }

    /**
     * Restituisce i dati dell'utente autenticato, o null.
     *
     * @return array<string,string>|null
     */
    public function getUser(): ?array
    {
        return $this->isLogged() ? $_SESSION[self::SESSION_USER] : null;
    }

    /**
     * Distrugge la sessione e fa logout.
     */
    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION[self::SESSION_USER]);
        session_destroy();
    }
}
