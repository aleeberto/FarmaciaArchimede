<?php
declare(strict_types=1);

namespace App\Core;

use App\Service\AuthService;
use App\Core\Model\UserDTO;

/**
 * Facade per la gestione della sessione e dell'autenticazione.
 */
final class Auth
{
    private static ?AuthService $authService = null;

    /**
     * Inizializza il servizio di autenticazione con iniezione del Database.
     */
    private static function init(): void
    {
        self::ensureSessionStarted();

        if (self::$authService === null) {
            $dbInstance = Database::getInstance(
                getenv('MARIADB_HOST')     ?: 'mariadb',
                getenv('MARIADB_USER')     ?: 'admin',
                getenv('MARIADB_PASSWORD') ?: 'admin',
                getenv('MARIADB_DATABASE') ?: 'farmacia_archimede'
            );
            self::$authService = new AuthService($dbInstance);
        }
    }

    /**
     * Assicura che la sessione PHP sia avviata.
     */
    private static function ensureSessionStarted(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Restituisce l'istanza di AuthService.
     */
    public static function manager(): AuthService
    {
        self::init();
        return self::$authService;
    }

    /**
     * Impone il login: se non autenticato, reindirizza.
     */
    public static function requireLogin(): void
    {
        if (!self::manager()->isLogged()) {
            header('Location: /login.php');
            exit;
        }
    }

    /**
     * Se già autenticato, reindirizza all'area personale.
     */
    public static function redirectIfLogged(): void
    {
        if (self::manager()->isLogged()) {
            header('Location: /area_personale.php');
            exit;
        }
    }

    /**
     * Restituisce i dati dell'utente autenticato come array o null.
     */
    public static function user(): ?array
    {
        return self::manager()->getUserDataArray();
    }

    /**
     * Impone che l'utente sia admin, altrimenti 403 e stop.
     */
    public static function requireAdmin(): void
    {
        $user = self::manager()->getUserDataArray();
        if (!$user || empty($user['is_admin'])) {
            http_response_code(403);
            echo 'Accesso negato: permessi insufficienti.';
            exit;
        }
    }
}
