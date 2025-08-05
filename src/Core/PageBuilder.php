<?php

declare(strict_types=1);

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
    private string $basePath;
    private AuthService $auth;

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

    public static function getInstance(): PageBuilder
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getAuthService(): AuthService
    {
        return $this->auth;
    }

    public static function show(?string $templateName = null, array $parameters = []): void
    {
        $self = self::getInstance();

        if ($templateName === null) {
            $templateName = pathinfo($_SERVER['SCRIPT_FILENAME'], PATHINFO_FILENAME);
        } else {
            $templateName = trim($templateName, '/\\');
            $templateName = preg_replace('/\.(html|php)$/i', '', $templateName);
        }

        echo $self->build($templateName, $parameters);
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

        $type    = $_SESSION['flash_message']['type'];
        $message = $_SESSION['flash_message']['message'];
        unset($_SESSION['flash_message']);

        $tpl = self::getInstance()->loadTemplate('common/alert');
        $tpl->insertAll([
            'type'    => $type,
            'message' => htmlspecialchars($message),
        ]);
        return $tpl->build();
    }

    public function build(string $templateName, array $parameters = []): string
    {
        $uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
        $uriPath = $uriPath === '/index.php' ? '/' : $uriPath;

        // Aggiunge variabile per blocco admin
        $isAdmin = $parameters['is_admin'] ?? false;

        $meta = [
            'meta_title'       => $parameters['meta_title']       ?? '',
            'meta_description' => $parameters['meta_description'] ?? '',
            'meta_keywords'    => $parameters['meta_keywords']    ?? '',
        ];

        // Carica template principale
        $main        = $this->loadTemplate($templateName);
        $headHtml    = (new HeadBuilder($this))->build($meta);
        $headerHtml  = (new HeaderBuilder($this, $uriPath))->build();
        $contentHtml = $main->build();
        $footerHtml  = (new FooterBuilder($this))->build();

        // Inserimenti base
        $main->insert('head',    $headHtml);
        $main->insert('header',  $headerHtml);
        $main->insert('content', $contentHtml);
        $main->insert('footer',  $footerHtml);
        $main->insert('alert',   self::getFlashMessage());
        $main->insertAll($parameters);

        // Build preliminare e gestione blocco admin
        $output = $main->build();
        if ($isAdmin) {
            // rimuove solo i tag di apertura/chiusura
            $output = preg_replace('/{{\s*\/??admin_section\s*}}/', '', $output);
        } else {
            // rimuove intero blocco compresi i contenuti
            $output = preg_replace('/{{\s*admin_section\s*}}.*?{{\s*\/admin_section\s*}}/s', '', $output);
        }

        return $output;
    }
}