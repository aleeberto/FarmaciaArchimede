<?php

namespace App\Core;

use Exception;
use mysqli;
use mysqli_sql_exception;

/**
 * Classe Database: singleton per la connessione MySQL tramite mysqli.
 */
class Database
{
    /**
     * Messaggio d'errore in caso di fallimento della connessione.
     */
    private const ERR_CONNECTION_FAILED = "Connessione al database fallita.";

    /**
     * Host (URL) del server MySQL.
     *
     * @var string
     */
    private string $url;

    /**
     * Nome utente per la connessione.
     *
     * @var string
     */
    private string $user;

    /**
     * Password per la connessione.
     *
     * @var string
     */
    private string $password;

    /**
     * Nome del database a cui connettersi.
     *
     * @var string
     */
    private string $database;

    /**
     * Istanza di mysqli rappresentante la connessione aperta.
     *
     * @var mysqli|null
     */
    private ?mysqli $connection = null;

    /**
     * Istanza singleton di Database.
     *
     * @var Database|null
     */
    private static ?Database $instance = null;

    /**
     * Costruttore privato.
     *
     * @param string $url      Host (URL) del server MySQL.
     * @param string $user     Nome utente per l'accesso al database.
     * @param string $password Password per l'accesso al database.
     * @param string $database Nome del database da utilizzare.
     */
    private function __construct(string $url, string $user, string $password, string $database)
    {
        $this->url      = $url;
        $this->user     = $user;
        $this->password = $password;
        $this->database = $database;
    }

    /**
     * Restituisce l'istanza singleton di Database, creandola se necessario.
     *
     * @param string $url      Host (URL) del server MySQL.
     * @param string $user     Nome utente per l'accesso al database.
     * @param string $password Password per l'accesso al database.
     * @param string $database Nome del database da utilizzare.
     * @return Database        Istanza singleton di Database.
     */
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
     *
     * @throws Exception      Solleva eccezione in caso di errore di connessione.
     * @return mysqli         Oggetto mysqli collegato al database.
     */
    public function connect(): mysqli
    {
        if ($this->connection === null) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

            try {
                $this->connection = @new mysqli(
                    $this->url,
                    $this->user,
                    $this->password,
                    $this->database
                );
                $this->connection->set_charset("utf8mb4");

            } catch (mysqli_sql_exception $e) {
                throw new Exception(self::ERR_CONNECTION_FAILED, 0, $e);
            }
        }

        return $this->connection;
    }
}
