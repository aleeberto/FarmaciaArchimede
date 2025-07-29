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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $db = Database::getInstance(
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
     * @return PageBuilder
     */
    public static function getInstance(): PageBuilder
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Espone il servizio di autenticazione per l'HeaderBuilder.
     *
     * @return AuthService
     */
    public function getAuthService(): AuthService
    {
        return $this->auth;
    }

    /**
     * Determina il template da usare, unisce i parametri e stampa la pagina.
     *
     * @param string|null $templateName
     * @param array       $parameters
     * @return void
     */
    public static function show(
        ?string $templateName = null,
        array $parameters = []
    ): void {
        $self = self::getInstance();

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
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Carica e restituisce un Template a partire dal nome del file.
     *
     * @param string $name
     * @throws RuntimeException Se il file non è leggibile.
     * @return Template
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
     * Ottiene e rende il messaggio flash, se presente.
     *
     * @return string
     */
    public static function getFlashMessage(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['flash_message'])) {
            return '';
        }

        $type    = $_SESSION['flash_message']['type'];    // 'success' o 'error'
        $message = $_SESSION['flash_message']['message'];
        unset($_SESSION['flash_message']);

        $tpl = self::getInstance()->loadTemplate('common/alert.html');
        $tpl->insertAll([
            'type'    => $type,
            'message' => htmlspecialchars($message),
        ]);
        return $tpl->build();
    }

    /**
     * Costruisce il markup HTML completo unendo head, header, contenuto e footer.
     *
     * @param string $templateName
     * @param array  $parameters
     * @throws RuntimeException
     * @return string
     */
    public function build(string $templateName, array $parameters = []): string
    {
        $uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
        $uriPath = $uriPath === '/index.php' ? '/' : $uriPath;

        $meta = [
            'meta_title'       => $parameters['meta_title']       ?? '',
            'meta_description' => $parameters['meta_description'] ?? '',
            'meta_keywords'    => $parameters['meta_keywords']    ?? '',
        ];

        $main        = $this->loadTemplate("{$templateName}.html");
        $headHtml    = (new HeadBuilder($this))->build($meta);
        $headerHtml  = (new HeaderBuilder($this, $uriPath))->build();
        $contentHtml = $main->build();
        $footerHtml  = (new FooterBuilder($this))->build();

        $main->insert('head',    $headHtml);
        $main->insert('header',  $headerHtml);
        $main->insert('content', $contentHtml);
        $main->insert('footer',  $footerHtml);
        $main->insert('alert',   self::getFlashMessage());
        $main->insertAll($parameters);

        return $main->build();
    }
}