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
            'localhost', 'gbarison','SaSoo9chahNguuCh', 'gbarison'
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
            header('Location: login.php');
            exit;
        }

        // === POST → tenta login con USERNAME, poi PRG ===
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim((string)($_POST['username'] ?? ''));
            $pwd      = (string)($_POST['password'] ?? '');

            if ($username === '' || !preg_match('/^[a-zA-Z0-9_.-]{3,30}$/', $username)) {
                $_SESSION['flash_message'] = [
                    'type'    => 'error',
                    'message' => 'Credenziali errate.',
                ];
                $_SESSION['old'] = ['username' => $username];
                header('Location: login.php');
                exit;
            }

            if ($this->auth->login($username, $pwd)) {
                header('Location: area_personale.php');
                exit;
            }

            // Fallimento → flash + old
            $_SESSION['flash_message'] = [
                'type'    => 'error',
                'message' => 'Credenziali errate.',
            ];
            $_SESSION['old'] = ['username' => $username];

            header('Location: login.php');
            exit;
        }

        $old = $_SESSION['old'] ?? ['username' => ''];
        unset($_SESSION['old']); // pulizia

        PageBuilder::show('login', [
            'old'               => $old,
            'meta_title'        => 'Accedi | Farmacia Archimede',
            'meta_description'  => 'Pagina di Login all’area personale di Farmacia Archimede.',
            'meta_keywords'     => 'login, farmacia archimede, area personale, registrazione',
        ]);
        exit;
    }
}
