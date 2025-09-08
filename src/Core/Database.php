<?php

declare(strict_types=1);

namespace App\Core;

use mysqli;
use mysqli_sql_exception;

/**
 * Classe Database: singleton per la connessione MySQL tramite mysqli (UTF-8).
 */
class Database
{
    private const ERR_CONNECTION_FAILED = 'Connessione al database fallita.';

    private string $url;
    private string $user;
    private string $password;
    private string $database;
    private ?mysqli $connection = null;
    private static ?Database $instance = null;

    private function __construct(string $url, string $user, string $password, string $database)
    {
        $this->url      = $url;
        $this->user     = $user;
        $this->password = $password;
        $this->database = $database;
    }

    public static function getInstance(
        string $url,
        string $user,
        string $password,
        string $database
    ): Database {
        if (self::$instance === null) {
            self::$instance = new self($url, $user, $password, $database);
        }
        return self::$instance;
    }

    /**
     * Apre la connessione al database se non ancora aperta, e la restituisce.
     * Forza utf8mb4 su client/connessione/risultati.
     */
    public function connect(): mysqli
    {
        if ($this->connection === null) {
            // niente warning a schermo
            ini_set('display_errors', '0');

            // alza eccezioni mysqli (niente @)
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

            try {
                $this->connection = new mysqli(
                    $this->url,
                    $this->user,
                    $this->password,
                    $this->database
                );

                // Imposta UTF-8 completo
                if (!$this->connection->set_charset('utf8mb4')) {
                    throw new mysqli_sql_exception(
                        'Impossibile impostare il charset utf8mb4: ' . $this->connection->error
                    );
                }

                $this->connection->query("SET collation_connection = 'utf8mb4_unicode_ci'");

            } catch (mysqli_sql_exception $e) {
                // Log tecnico per il server
                error_log('[DB] ' . $e->getMessage());

                // Pagina di errore per l’utente
                PageBuilder::error(500, [
                    'meta_description' => 'Si è verificato un errore interno. Il team di Farmacia Archimede è al lavoro. Torna alla home.',
                    'meta_keywords'    => 'errore 500, problema server, Farmacia Archimede, errore interno, sito farmacia'
                ]);
                exit;
            }
        }

        return $this->connection;
    }
}