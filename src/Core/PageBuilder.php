<?php

declare(strict_types = 1);

namespace App\Core;

use App\Service\AuthService;
use App\View\FooterBuilder;
use App\View\HeadBuilder;
use App\View\HeaderBuilder;
use RuntimeException;
use Throwable;

class PageBuilder {
    private static ?PageBuilder $instance = null;

    private string $basePath;
    private ?AuthService $auth = null;         // può rimanere null in degraded mode
    private bool $degraded = false;            // true se Auth/DB non disponibili

    // === DEBUG FORZATO (metti a false in produzione) ===
    private const FORCE_DEBUG = true;

    private static function isDebug(): bool
    {
        $env = getenv('APP_ENV') ?: '';
        return self::FORCE_DEBUG
            || (getenv('APP_DEBUG') === '1')
            || in_array($env, ['dev','local','development'], true);
    }

    private static function setPlain500(): void
    {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
        }
    }

    private function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Best-effort: prova a istanziare DB/Auth, altrimenti rimani in degraded mode.
        try {
            $db = Database::getInstance(
                'localhost', 'gbarison','SaSoo9chahNguuCh', 'gbarison'
            );
            $this->auth = new AuthService($db);
        } catch (Throwable $e) {
            // Niente DB/Auth: continuiamo lo stesso
            $this->auth = null;
            $this->degraded = true;

            // In debug: mostra a schermo
            if (self::isDebug()) {
                self::setPlain500();
                echo "[PageBuilder::__construct][Auth/DB degraded] " . $e->getMessage() . "\n";
                echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
                // non usciamo: proviamo comunque a renderizzare
            }
        }

        $configuredPath = realpath(__DIR__ . '/../html');
        if ($configuredPath === false) {
            if (self::isDebug()) {
                self::setPlain500();
                echo "[PageBuilder] Directory template non trovata: " . (__DIR__ . '/../html') . "\n";
                exit;
            }
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

    public function getAuthService(): ?AuthService
    {
        return $this->auth;
    }

    /**
     * Mostra il template richiesto.
     *
     * @param string|null $templateName Nome template senza estensione (default: nome dello script chiamante).
     * @param array       $parameters   Parametri da iniettare nel template.
     */
    public static function show(?string $templateName = null, array $parameters = []): void
    {
        $self = self::getInstance();

        if ($templateName === null) {
            $templateName = pathinfo($_SERVER['SCRIPT_FILENAME'] ?? 'index.php', PATHINFO_FILENAME);
        } else {
            $templateName = trim($templateName, '/\\');
            $templateName = preg_replace('/\.(html|php)$/i', '', $templateName);
        }

        try {
            echo $self->build($templateName, $parameters);
        } catch (Throwable $e) {
            if (self::isDebug()) {
                self::setPlain500();
                echo "[PageBuilder::show] " . $e->getMessage() . "\n";
                echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
                echo $e->getTraceAsString() . "\n";
                exit;
            }

            // Produzione: pagina 500 “pulita”
            self::error(500, [
                'meta_description' => 'Si è verificato un errore interno. Torna alla home.',
                'meta_keywords'    => 'errore 500, problema server, Farmacia Archimede'
            ]);
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

        if (self::isDebug()) {
            self::setPlain500();
            echo "[PageBuilder::error] HTTP $code\n";
            if (!empty($parameters)) {
                echo "Dettagli:\n";
                foreach ($parameters as $k => $v) {
                    echo "- $k: " . (is_scalar($v) ? (string)$v : json_encode($v)) . "\n";
                }
            }
            exit;
        }

        http_response_code($code);
        $parameters["meta_title"] = "Errore $code | Farmacia Archimede";
        self::show((string)$code, ['error_code' => $code] + $parameters);

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
            if (self::isDebug()) {
                self::setPlain500();
                echo "[PageBuilder::loadTemplate] Impossibile leggere il template: {$file}.html\n";
                echo "Percorso: {$path}\n";
                exit;
            }
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

        // Se non passato esplicitamente, deduci is_admin dall'utente (se disponibile)
        if (!array_key_exists('is_admin', $parameters)) {
            $parameters['is_admin'] = false;
            try {
                $user = $this->auth?->getUser();
                if ($user && method_exists($user, 'isAdmin')) {
                    $parameters['is_admin'] = (bool)$user->isAdmin();
                }
            } catch (Throwable $e) {
                // ignora errori auth in fase di build ma MOSTRA in debug
                $parameters['is_admin'] = false;
                if (self::isDebug()) {
                    self::setPlain500();
                    echo "[PageBuilder::build][getUser] " . $e->getMessage() . "\n";
                    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
                    // non usciamo: continuiamo il rendering
                }
            }
        }

        $meta = [
            'meta_title'       => $parameters['meta_title'] ?? '',
            'meta_description' => $parameters['meta_description'] ?? '',
            'meta_keywords'    => $parameters['meta_keywords'] ?? '',
        ];

        $main       = $this->loadTemplate($templateName);
        $headHtml   = (new HeadBuilder($this))->build($meta);
        $headerHtml = (new HeaderBuilder($this, $uriPath))->build();
        $footerHtml = (new FooterBuilder($this))->build();

        // Inserisci i componenti standard
        $main->insert('head', $headHtml);
        $main->insert('header', $headerHtml);
        $main->insert('footer', $footerHtml);
        $main->insert('alert', self::getFlashMessage());
        $main->insertAll($parameters);

        // Materializza i blocchi condizionali PRIMA del build
        $adminBlock = $main->getBlockContent('admin_section');
        $userBlock  = $main->getBlockContent('user_section');

        if ($parameters['is_admin']) {
            if ($adminBlock !== null) {
                $main->insert('admin_section', $adminBlock);
            }
            $main->insert('user_section', '');
        } else {
            if ($userBlock !== null) {
                $main->insert('user_section', $userBlock);
            }
            $main->insert('admin_section', '');
        }

        return $main->build();
    }
}