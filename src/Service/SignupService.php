<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Core\PageBuilder;
use Exception;
use mysqli;
use Throwable;

class SignupService
{
    private mysqli $db;
    private AuthService $auth;

    public function __construct()
    {
        $database   = Database::getInstance(
            getenv('MARIADB_HOST') ?: 'mariadb',
            getenv('MARIADB_USER') ?: 'admin',
            getenv('MARIADB_PASSWORD') ?: 'admin',
            getenv('MARIADB_DATABASE') ?: 'pharmacy_archimede'
        );
        $this->db   = $database->connect();
        $this->auth = new AuthService($database);
    }

    public function handleRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->renderForm();
            return;
        }

        // POST → valida e registra
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name'] ?? '');
        $taxCode   = strtoupper(trim($_POST['tax_code'] ?? ''));
        $email     = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL) ?: '';
        $pwd       = $_POST['password'] ?? '';
        $pwd2      = $_POST['password_confirm'] ?? '';

        $old = [
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'tax_code'   => $taxCode,
            'email'      => $email,
        ];

        // Validazioni basilari
        $errorMessage = $this->validate($firstName, $lastName, $taxCode, $email, $pwd, $pwd2);
        if ($errorMessage !== '') {
            $this->renderForm($old, $errorMessage);
            return;
        }

        // Controllo email già esistente
        if ($this->emailExists($email)) {
            $this->renderForm($old, 'Esiste già un account con questa email.');
            return;
        }

        // (Opzionale) controllo codice fiscale univoco
        if ($taxCode !== '' && $this->taxCodeExists($taxCode)) {
            $this->renderForm($old, 'Esiste già un account con questo codice fiscale.');
            return;
        }

        // Inserimento utente
        try {
            $this->db->begin_transaction();

            $hash = password_hash($pwd, PASSWORD_DEFAULT);
            $q = 'INSERT INTO users (email, first_name, last_name, tax_code, password_hash, is_admin)
                  VALUES (?, ?, ?, ?, ?, 0)';
            $stmt = $this->db->prepare($q);
            if (!$stmt) {
                throw new Exception('Preparazione INSERT fallita.');
            }
            $stmt->bind_param('sssss', $email, $firstName, $lastName, $taxCode, $hash);
            $ok = $stmt->execute();
            $stmt->close();

            if (!$ok) {
                throw new Exception('Esecuzione INSERT fallita.');
            }

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollback();
            $this->renderForm($old, 'Errore durante la registrazione. Riprova più tardi.');
            return;
        }

        // Auto-login e redirect area personale (evita passaggi di messaggi via querystring)
        if ($this->auth->login($email, $pwd)) {
            header('Location: /area_personale.php');
            exit;
        }

        // Fallback: se per qualche motivo l’auto-login non riesce
        header('Location: /login.php?registered=1');
        exit;
    }

    private function renderForm(array $old = [], string $errorMessage = ''): void
    {
        // default per tutti i campi attesi
        $defaults = [
            'first_name' => '',
            'last_name'  => '',
            'tax_code'   => '',
            'email'      => '',
        ];
        $old = array_merge($defaults, $old);

        // escape per sicurezza XSS nei value=""
        $escape = static fn(string $v): string =>
        htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        foreach ($old as $k => $v) {
            $old[$k] = $escape((string)$v);
        }
        $errorMessage = $escape($errorMessage);

        PageBuilder::show('signup', [
            'error_message'    => $errorMessage,
            'old'              => $old,
            'meta_title'       => 'Registrati | Farmacia Archimede',
            'meta_description' => 'Crea il tuo account per accedere all’area personale.',
            'meta_keywords'    => 'registrazione, account, farmacia archimede',
        ]);
        exit;
    }


    private function validate(
        string $firstName,
        string $lastName,
        string $taxCode,
        string $email,
        string $pwd,
        string $pwd2,
    ): string {
        if ($firstName === '' || $lastName === '' || $email === '' || $pwd === '' || $pwd2 === '') {
            return 'Compila tutti i campi obbligatori.';
        }

        if ($pwd !== $pwd2) {
            return 'Le password non coincidono.';
        }
        if (strlen($pwd) < 8) {
            return 'La password deve avere almeno 8 caratteri.';
        }
        // Validazione basilare CF italiano (16 alfanumerici). Se opzionale, ignora quando vuoto.
        if ($taxCode !== '' && !preg_match('/^[A-Z0-9]{16}$/', $taxCode)) {
            return 'Codice fiscale non valido.';
        }
        return '';
    }

    private function emailExists(string $email): bool
    {
        $q = 'SELECT 1 FROM users WHERE email = ? LIMIT 1';
        $stmt = $this->db->prepare($q);
        if (!$stmt) return true;
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    private function taxCodeExists(string $taxCode): bool
    {
        $q = 'SELECT 1 FROM users WHERE tax_code = ? LIMIT 1';
        $stmt = $this->db->prepare($q);
        if (!$stmt) return false;
        $stmt->bind_param('s', $taxCode);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        return $exists;
    }
}