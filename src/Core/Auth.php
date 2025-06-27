<?php
declare(strict_types=1);

namespace App\Core;

use App\Service\AuthService;

/**
 * Facade per la gestione della sessione e dei redirect di autenticazione.
 */
class Auth
{
    /**
     * @var AuthService|null Istanza singleton del servizio di autenticazione.
     */
    private static ?AuthService $auth = null;

    /**
     * Inizializza la sessione e crea l'istanza di AuthService se non già presente.
     *
     * @return void
     */
    public static function init(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (self::$auth === null) {
            $db = Database::getInstance(
                getenv('MARIADB_HOST') ?: 'mariadb',
                getenv('MARIADB_USER') ?: 'admin',
                getenv('MARIADB_PASSWORD') ?: 'admin',
                getenv('MARIADB_DATABASE') ?: 'farmacia_archimede'
            );
            self::$auth = new AuthService($db);
        }
    }

    /**
     * Restituisce l'istanza di AuthService, inizializzandola se necessario.
     *
     * @return AuthService L'istanza del servizio di autenticazione.
     */
    public static function manager(): AuthService
    {
        self::init();
        return self::$auth;
    }

    /**
     * Verifica che l'utente sia autenticato; in caso contrario esegue un redirect alla pagina di login.
     *
     * @return void
     */
    public static function requireLogin(): void
    {
        if (! self::manager()->isLogged()) {
            header('Location: /login.php');
            exit;
        }
    }

    /**
     * Se l'utente è già autenticato, lo reindirizza all'area personale.
     *
     * @return void
     */
    public static function redirectIfLogged(): void
    {
        if (self::manager()->isLogged()) {
            header('Location: /area_personale.php');
            exit;
        }
    }

    /**
     * Ottiene i dati dell'utente autenticato.
     *
     * @return array<string,mixed>|null Array associativo con le informazioni dell'utente, oppure null se non autenticato.
     */
    public static function user(): ?array
    {
        return self::manager()->getUser();
    }
}