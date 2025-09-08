<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Core\PageBuilder;
use App\View\HeaderBuilder;
use Exception;
use mysqli;
use Throwable;

class SignupService extends HeaderBuilder
{
    private mysqli $db;
    private AuthService $auth;

    public function __construct()
    {
        $database   = Database::getInstance(
            'localhost', 'gbarison','SaSoo9chahNguuCh', 'gbarison'
        );
        $this->db   = $database->connect();
        $this->auth = new AuthService($database);
    }

    public function handleRequest(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
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

        // Validazioni basilari → array di errori per campo
        $errors = $this->validate($firstName, $lastName, $taxCode, $email, $pwd, $pwd2);

        // Controllo email già esistente
        if ($email !== '' && $this->emailExists($email)) {
            $errors['email'] = 'Esiste già un account con questa email.';
        }

        // Controllo codice fiscale univoco
        if ($taxCode !== '' && $this->taxCodeExists($taxCode)) {
            $errors['tax_code'] = 'Esiste già un account con questo codice fiscale.';
        }

        if (!empty($errors)) {
            if (!isset($errors['_global'])) {
                $errors['_global'] = 'Correggi i campi evidenziati.';
            }
            $this->renderForm($old, $errors);
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
            $this->renderForm($old, ['_global' => 'Errore durante la registrazione. Riprova più tardi.']);
            return;
        }

        // Auto-login e redirect area personale
        if ($this->auth->login($email, $pwd)) {
            header('Location: /area_personale.php');
            exit;
        }

        // Fallback: auto-login non riuscito
        header('Location: /login.php?registered=1');
        exit;
    }

    private function renderForm(array $old = [], array $errors = []): void
    {
        // default per tutti i campi attesi
        $defaults = [
            'first_name' => '',
            'last_name'  => '',
            'tax_code'   => '',
            'email'      => '',
        ];
        $old = array_merge($defaults, $old);

        $escape = static fn(string $v): string =>
        htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        foreach ($old as $k => $v) {
            $old[$k] = $escape((string)$v);
        }
        foreach ($errors as $k => $v) {
            $errors[$k] = $escape((string)$v);
        }

        // Banner globale opzionale in {{ alert }}
        $alert = '';
        if (!empty($errors['_global'])) {
            $alert = '<div class="alert error" role="alert" aria-live="assertive">'
                . $errors['_global']
                . '</div>';
        }

        PageBuilder::show('signup', [
            'alert'            => $alert,
            'old'              => $old,
            'errors'           => $errors,
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
    ): array {
        $errors = [];

        // Obbligatorietà
        if ($firstName === '') $errors['first_name'] = 'Il nome è obbligatorio.';
        if ($lastName === '')  $errors['last_name']  = 'Il cognome è obbligatorio.';
        if ($email === '')     $errors['email']      = 'L’email è obbligatoria.';
        if ($taxCode === '')   $errors['tax_code']   = 'Il codice fiscale è obbligatorio.';
        if ($pwd === '')       $errors['password']   = 'La password è obbligatoria.';
        if ($pwd2 === '')      $errors['password_confirm'] = 'Conferma la password.';

        // Se ci sono già errori di required, fermo qui con un globale
        if (!empty($errors)) {
            $errors['_global'] = 'Correggi i campi evidenziati.';
            return $errors;
        }

        // Password
        if ($pwd !== $pwd2) {
            $errors['password_confirm'] = 'Le password non coincidono.';
        } elseif (strlen($pwd) < 8) {
            $errors['password'] = 'La password deve avere almeno 8 caratteri.';
        }

        // CF italiano basilare
        if (!preg_match('/^[A-Z0-9]{16}$/', $taxCode)) {
            $errors['tax_code'] = 'Codice fiscale non valido.';
        }

        if (!empty($errors)) {
            $errors['_global'] = 'Correggi i campi evidenziati.';
        }

        return $errors;
    }

    private function emailExists(string $email): bool
    {
        $q = 'SELECT 1 FROM users WHERE email = ? LIMIT 1';
        $stmt = $this->db->prepare($q);
        if (!$stmt) return true; // conservativo
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
        if (!$stmt) return false; // se fallisce la prepare, non blocco la registrazione
        $stmt->bind_param('s', $taxCode);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        return $exists;
    }
}
