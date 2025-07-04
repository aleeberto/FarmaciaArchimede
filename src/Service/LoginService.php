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
            getenv('MARIADB_DATABASE') ?: 'farmacia_archimede'
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

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $error = $_GET['error'] ?? '';
            PageBuilder::show('login', [
                'error' => $error,
                'title' => 'Accedi',
            ]);
            exit;
        }

        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL) ?: '';
        $pwd   = $_POST['password'] ?? '';

        if ($this->auth->login($email, $pwd)) {
            header('Location: /area_personale.php');
        } else {
            header('Location: /login.php?error=credentials');
        }
        exit;
    }
}