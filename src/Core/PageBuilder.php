<?php

namespace App\Core;
use App\Service\AuthService;
use App\View\FooterBuilder;
use App\View\HeadBuilder;
use App\View\HeaderBuilder;
use RuntimeException;

/**
 * Costruisce e rende le pagine HTML utilizzando template, header, footer e dati utente.
 */
class PageBuilder
{
    /**
     * Istanza singleton di PageBuilder.
     *
     * @var PageBuilder|null
     */
    private static ?PageBuilder $instance = null;

    /**
     * Percorso assoluto alla directory dei template.
     *
     * @var string
     */
    private string $basePath;

    /**
     * Servizio di autenticazione per recuperare i dati utente.
     *
     * @var AuthService
     */
    private AuthService $auth;

    /**
     * Costruttore privato: avvia la sessione, inizializza AuthService e definisce il percorso ai file HTML.
     *
     * @throws RuntimeException Se la cartella html non esiste.
     */
    private function __construct()
    {
        // Avvia la sessione se non presente
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $db    = Database::getInstance(
            getenv('MARIADB_HOST') ?: 'mariadb',
            getenv('MARIADB_USER') ?: 'admin',
            getenv('MARIADB_PASSWORD') ?: 'admin',
            getenv('MARIADB_DATABASE') ?: 'farmacia_archimede'
        );
        $this->auth = new AuthService($db);

        $configuredPath = realpath(__DIR__ . '/../html');
        if ($configuredPath === false) {
            throw new RuntimeException('Directory template non trovata');
        }
        $this->basePath = $configuredPath;
    }

    /**
     * Restituisce l'istanza singleton di PageBuilder, creandola se necessario.
     *
     * @return PageBuilder Istanza singleton di PageBuilder.
     */
    public static function getInstance(): PageBuilder
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Determina il template da usare, unisce i parametri e stampa la pagina.
     *
     * @param string|null $templateName Nome del template (senza estensione), dedotto dal file PHP se null.
     * @param array       $parameters   Array associativo di parametri da passare al template.
     * @return void
     */
    public static function show(
        ?string $templateName = null,
        array   $parameters   = []
    ): void {
        $self = self::getInstance();

        // Deduce il nome del template dal file chiamante se non fornito
        if ($templateName === null) {
            $templateName = pathinfo($_SERVER['SCRIPT_FILENAME'], PATHINFO_FILENAME);
        } else {
            $templateName = pathinfo(ltrim($templateName, '/\\'), PATHINFO_FILENAME);
        }


        echo $self->build($templateName, $parameters);
    }

    /**
     * Restituisce il percorso assoluto alla directory dei template.
     *
     * @return string Percorso base dei template.
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Carica e restituisce un Template a partire dal nome del file.
     *
     * @param string $name Nome del file template (con estensione).
     * @throws RuntimeException Se il file non è leggibile.
     * @return Template      Istanza del template caricato.
     */
    public function loadTemplate(string $name): Template
    {
        $path = "{$this->basePath}/{$name}";
        if (!is_readable($path)) {
            throw new RuntimeException("Impossibile leggere il template: {$name}");
        }
        return new Template($name, file_get_contents($path));
    }

    /**
     * Costruisce il markup HTML completo unendo head, header, contenuto e footer.
     *
     * @param string $templateName Nome del template (senza estensione).
     * @param array  $parameters   Parametri da sostituire all'interno del template.
     * @throws RuntimeException In caso di errori nel caricamento o nella navigazione.
     * @return string             Markup HTML finale.
     */
    public function build(
        string $templateName,
        array  $parameters   = []
    ): string {
        $uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
        $uriPath = $uriPath === '/index.php' ? '/' : $uriPath;

        $main        = $this->loadTemplate("{$templateName}.html");
        $headHtml    = (new HeadBuilder($this))->build();
        $headerHtml  = (new HeaderBuilder($this, $uriPath))->build();
        $contentHtml = $main->build();
        $footerHtml  = (new FooterBuilder($this))->build();

        $main->insert('head',    $headHtml);
        $main->insert('header',  $headerHtml);
        $main->insert('content', $contentHtml);
        $main->insert('footer',  $footerHtml);

        $main->insertAll($parameters);

        return $main->build();
    }
}
