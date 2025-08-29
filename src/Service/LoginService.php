<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Core\PageBuilder;

class LoginService
{
    private AuthService $auth;

    public function __construct()
    {
        $db = Database::getInstance(
            getenv('MARIADB_HOST')     ?: 'mariadb',
            getenv('MARIADB_USER')     ?: 'admin',
            getenv('MARIADB_PASSWORD') ?: 'admin',
            getenv('MARIADB_DATABASE') ?: 'farmacia_archimede'
        );
        $this->auth = new AuthService($db);
    }

    public function handleRequest(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // === Logout (PRG) ===
        if (isset($_GET['logout'])) {
            $this->auth->logout();
            header('Location: /login.php');
            exit;
        }

        // === POST → tenta login, poi SEMPRE redirect (PRG) ===
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL) ?: '';
            $pwd   = $_POST['password'] ?? '';

            if ($this->auth->login($email, $pwd)) {
                // Successo → vai all’area personale
                header('Location: /area_personale.php');
                exit;
            }

            // Fallimento → imposta flash + old values in session
            $_SESSION['flash_message'] = [
                'type'    => 'error',
                'message' => 'Credenziali errate.',
            ];
            $_SESSION['old'] = ['email' => $email];

            // GET pulita
            header('Location: /login.php');
            exit;
        }

        $old = $_SESSION['old'] ?? ['email' => ''];
        unset($_SESSION['old']); // pulizia dopo lettura

        PageBuilder::show('login', [
            'old'               => $old,
            'meta_title'        => 'Accedi | Farmacia Archimede',
            'meta_description'  => 'Pagina di Login all’area personale di Farmacia Archimede.',
            'meta_keywords'     => 'login, farmacia archimede, area personale, registrazione',
        ]);
        exit;
    }
}
