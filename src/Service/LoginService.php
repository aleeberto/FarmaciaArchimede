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
            getenv('MARIADB_HOST') ?: 'mariadb',
            getenv('MARIADB_USER') ?: 'admin',
            getenv('MARIADB_PASSWORD') ?: 'admin',
            getenv('MARIADB_DATABASE') ?: 'pharmacy_archimede'
        );
        $this->auth = new AuthService($db);
    }

    public function handleRequest(): void
    {
        // Logout
        if (isset($_GET['logout'])) {
            $this->auth->logout();
            header('Location: /login.php');
            exit;
        }

        // GET → mostra form senza errori
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $oldEmail = $_GET['email'] ?? '';

            PageBuilder::show('login', [
                'error'             => '',
                'old'               => ['email' => $oldEmail],
                'meta_title'        => 'Accedi | Farmacia Archimede',
                'meta_description'  => 'Pagina di Login all area personale della Farmacia Archimede',
                'meta_keywords'     => 'login, farmacia, archimede, area personale, registrazione',
            ]);
            exit;
        }

        // POST → tenta login
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL) ?: '';
        $pwd   = $_POST['password'] ?? '';

        if ($this->auth->login($email, $pwd)) {
            header('Location: /area_personale.php');
            exit;
        }



        // Login KO → mostra form con blocco errore lasciato visibile
        PageBuilder::show('login', [
            'old'               => ['email' => $email],
            'meta_title'        => 'Accedi | Farmacia Archimede',
            'meta_description'  => 'Descrizione specifica per questa pagina',
            'meta_keywords'     => 'parola1, parola2, parola3',
        ]);

        $_SESSION['flash_message'] = [
            'type'    => 'error',
            'message' => 'Credenziali errate.',
        ];
        exit;
    }
}
