<?php

declare(strict_types = 1);

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
    private static ?PageBuilder $instance = null;

    /** Se true, NON istanzia Auth/DB. Può essere forzata per singola chiamata con show(..., safe: true). */
    private static bool $safeMode = false;

    /** Indica se questa specifica istanza è stata costruita in safe mode. */
    private bool $isSafeInstance = false;

    private string $basePath;
    private ?AuthService $auth = null; // opzionale in safe mode

    private function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->isSafeInstance = self::$safeMode;

        // In safe mode NON istanziare Auth/DB
        if (!self::$safeMode) {
            $db = Database::getInstance(
                getenv('MARIADB_HOST') ?: 'mariadb',
                getenv('MARIADB_USER') ?: 'admin',
                getenv('MARIADB_PASSWORD') ?: 'admin',
                getenv('MARIADB_DATABASE') ?: 'farmacia_archimede'
            );
            $this->auth = new AuthService($db);
        }

        $configuredPath = realpath(__DIR__ . '/../html');
        if ($configuredPath === false) {
            throw new RuntimeException('Directory template non trovata');
        }
        $this->basePath = $configuredPath;
    }

    /**
     * Ritorna un'istanza coerente con lo stato di safe mode corrente.
     * Se lo stato desiderato differisce da quello dell'istanza esistente, ne crea una nuova.
     */
    public static function getInstance(): PageBuilder
    {
        if (
            self::$instance === null
            || (self::$instance !== null && self::$instance->isSafeInstance !== self::$safeMode)
        ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getAuthService(): ?AuthService
    {
        return $this->auth;
    }

    /**
     * Mostra il template richiesto.
     *
     * @param string|null $templateName  Nome template senza estensione (default: nome dello script chiamante).
     * @param array       $parameters    Parametri da iniettare nel template.
     * @param bool|null   $safe          Se true forza la safe mode SOLO per questa chiamata.
     *                                   Se false forza la modalità normale SOLO per questa chiamata.
     *                                   Se null, lascia invariato lo stato corrente.
     */
    public static function show(?string $templateName = null, array $parameters = [], ?bool $safe = null): void
    {
        $previousSafe = self::$safeMode;

        if ($safe !== null) {
            self::$safeMode = $safe;
        }

        try {
            $self = self::getInstance();

            if ($templateName === null) {
                $templateName = pathinfo($_SERVER['SCRIPT_FILENAME'] ?? 'index.php', PATHINFO_FILENAME);
            } else {
                $templateName = trim($templateName, '/\\');
                $templateName = preg_replace('/\.(html|php)$/i', '', $templateName);
            }

            echo $self->build($templateName, $parameters);
        } finally {
            if ($safe !== null) {
                self::$safeMode = $previousSafe;
                self::$instance = null;
            }
        }
    }

    /**
     * Renderizza una pagina di errore e termina l'esecuzione.
     */
    public static function error(int $code, array $parameters = []): void
    {
        $allowed = [400, 401, 403, 404, 418, 422, 429, 500, 502, 503, 504];
        if (!in_array($code, $allowed, true)) {
            $code = 500;
        }

        http_response_code($code);

        $previousSafe = self::$safeMode;
        self::$safeMode = true;

        self::show("{$code}", ['error_code' => $code] + $parameters, null);

        self::$safeMode = $previousSafe;
        self::$instance = null;

        exit;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function loadTemplate(string $name): Template
    {
        $file = preg_replace('/\.(html|php)$/i', '', $name);
        $path = $this->basePath . '/' . $file . '.html';
        if (!is_readable($path)) {
            throw new RuntimeException("Impossibile leggere il template: {$file}.html");
        }
        return new Template($file . '.html', file_get_contents($path));
    }

    public static function getFlashMessage(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['flash_message'])) {
            return '';
        }

        $type = $_SESSION['flash_message']['type'];
        $message = $_SESSION['flash_message']['message'];
        unset($_SESSION['flash_message']);

        $tpl = self::getInstance()->loadTemplate('common/alert');
        $tpl->insertAll([
            'type'    => $type,
            'message' => htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        ]);
        return $tpl->build();
    }

    public function build(string $templateName, array $parameters = []): string
    {
        $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $uriPath = $uriPath === '/index.php' ? '/' : $uriPath;

        $isAdmin = $parameters['is_admin'] ?? false;

        $meta = [
            'meta_title'       => $parameters['meta_title'] ?? '',
            'meta_description' => $parameters['meta_description'] ?? '',
            'meta_keywords'    => $parameters['meta_keywords'] ?? '',
        ];

        $main     = $this->loadTemplate($templateName);
        $headHtml = (new HeadBuilder($this))->build($meta);

        $headerHtml = (new HeaderBuilder($this, $uriPath, self::$safeMode))->build();

        $contentHtml = $main->build();
        $footerHtml  = (new FooterBuilder($this))->build();

        $main->insert('head', $headHtml);
        $main->insert('header', $headerHtml);
        $main->insert('content', $contentHtml);
        $main->insert('footer', $footerHtml);
        $main->insert('alert', self::getFlashMessage());
        $main->insertAll($parameters);

        $output = $main->build();

        if (!self::$safeMode) {
            if ($isAdmin) {
                $output = preg_replace('/{{\s*\/??admin_section\s*}}/', '', $output);
            } else {
                $output = preg_replace('/{{\s*admin_section\s*}}.*?{{\s*\/admin_section\s*}}/s', '', $output);
            }
        }

        return $output;
    }
}