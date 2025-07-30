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
        if (isset($_GET['logout'])) {
            $this->auth->logout();
            header('Location: /login.php');
            exit;
        }

        // Se è GET, mostro la form
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $error    = $_GET['error'] ?? '';
            $oldEmail = $_GET['email'] ?? '';

            PageBuilder::show('login', [
                'error'            => $error,
                'old'              => ['email' => $oldEmail],
                'meta_title'       => 'Accedi | Farmacia Archimede',
                'meta_description' => 'Descrizione specifica per questa pagina',
                'meta_keywords'    => 'parola1, parola2, parola3',
            ]);
            exit;
        }

        // Se è POST, provo a loggare
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL) ?: '';
        $pwd   = $_POST['password'] ?? '';

        if ($this->auth->login($email, $pwd)) {
            // login ok → redirect all’area personale
            header('Location: /area_personale.php');
            exit;
        }

        // login KO → ricarico la form subito, passando error e old email
        PageBuilder::show('login', [
            'error'            => 'credentials',
            'old'              => ['email' => $email],
            'meta_title'       => 'Accedi | Farmacia Archimede',
            'meta_description' => 'Descrizione specifica per questa pagina',
            'meta_keywords'    => 'parola1, parola2, parola3',
        ]);
        exit;
    }

}