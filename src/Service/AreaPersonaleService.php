<?php

namespace App\Service;

use App\Core\Database;
use App\Core\Template;
use App\Core\Model\UserDTO;
use App\Service\AuthService;
use RuntimeException;

class AreaPersonaleService
{
    private \mysqli $mysqli;
    private AuthService $auth;

    public function __construct(AuthService $auth, Database $db)
    {
        $this->auth   = $auth;
        $this->mysqli = $db->connect();
    }

    private function readTpl(string $relativePath): string
    {
        $file = __DIR__ . '/../html/' . ltrim($relativePath, '/');
        $html = @file_get_contents($file);
        if ($html === false) {
            throw new \RuntimeException("Template non trovato: {$relativePath}");
        }
        return $html;
    }

    /**
     * Renderizza un “row template” sostituendo un array associativo di valori.
     * (Escape a monte quando necessario).
     */
    private function renderRow(string $tpl, array $data): string
    {
        $t = new Template('row', $tpl);
        foreach ($data as $k => $v) {
            $t->insert($k, (string)$v);
        }
        return $t->build();
    }

    /**
     * =========================
     * Helpers sicurezza/ruoli
     * =========================
     */

    /** CSRF helper: assicura e ritorna il token in sessione. */
    private function ensureCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['csrf_token'];
    }

    /** Verifica su DB se l'utente è admin (fallback quando DTO non espone isAdmin()). */
    private function isUserAdminById(int $userId): bool
    {
        $stmt = $this->mysqli->prepare('SELECT is_admin FROM users WHERE user_id = ? LIMIT 1');
        if (!$stmt) {
            throw new \RuntimeException('Errore sistema (prep isUserAdminById).');
        }
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return (bool)($row['is_admin'] ?? false);
    }

    /**
     * Elimina un utente rispettando le policy:
     * - nessuno può eliminare se stesso;
     * - un non-admin può eliminare altri utenti; l’admin non può eliminare se stesso.
     */
    public function deleteUserById(int $targetUserId): void
    {
        $me = $this->auth->getUser();
        if (!$me instanceof UserDTO) {
            throw new \RuntimeException('Utente non autenticato.');
        }

        $myId = (int)$me->getId();
        if ($targetUserId === $myId) {
            throw new \RuntimeException('Non puoi eliminare il tuo stesso account.');
        }

        $stmt = $this->mysqli->prepare('DELETE FROM users WHERE user_id = ? LIMIT 1');
        if (!$stmt) {
            throw new \RuntimeException('Errore sistema (prep delete user).');
        }
        $stmt->bind_param('i', $targetUserId);
        if (!$stmt->execute()) {
            $code = $stmt->errno;
            $msg  = $stmt->error;
            $stmt->close();
            throw new \RuntimeException("Errore durante l'eliminazione dell'utente (SQL $code): $msg");
        }
        $stmt->close();
    }

    /**
     * =========================
     * Dati area personale
     * =========================
     */

    /** Dati base dell’utente */
    public function getDatiUtente(): array
    {
        $user = $this->auth->getUser();
        if (!$user instanceof UserDTO) {
            throw new \RuntimeException("Utente non autenticato.");
        }

        // Leggi sempre dal DB per evitare dati stantii in sessione
        $row = $this->getProfiloUtente($user->getId());
        if ($row === null) {
            throw new \RuntimeException("Profilo utente non trovato.");
        }

        return [
            'user' => [
                'first_name' => $row->first_name,
                'last_name'  => $row->last_name,
                'email'      => $row->email,
                'tax_code'   => $row->tax_code,
            ],
        ];
    }

    /** Elenco prodotti (UI: se vuoto mostra stato vuoto) */
    public function getProdottiAdmin(): array
    {
        return [
            'all_products' => $this->renderProductsComponent(),
        ];
    }

    /** Elenco utenti (UI: se vuoto mostra stato vuoto) */
    public function getUtentiAdmin(): array
    {
        return [
            'all_users' => $this->renderUsersComponent(),
        ];
    }

    /**
     * =========================
     * Component: Prodotti
     * =========================
     */
    private function renderProductsComponent(): string
    {
        // 1) Query
        $sql = "
            SELECT product_id, name, manufacturer, price, availability
            FROM products
            ORDER BY name ASC
        ";

        $result = $this->mysqli->query($sql);
        if (!$result) {
            throw new \RuntimeException("Errore query prodotti: " . $this->mysqli->error);
        }
        $products = $result->fetch_all(MYSQLI_ASSOC);

        // 2) CSRF per le delete forms
        $csrf = $this->ensureCsrfToken();

        // 3) Carica template
        $tableTpl = file_get_contents(__DIR__ . '/../html/area_personale/card/all_products.html');
        if ($tableTpl === false) {
            throw new \RuntimeException("Template non trovato: all_products.html");
        }
        $rowTpl = file_get_contents(__DIR__ . '/../html/area_personale/card/_product_row.html');
        if ($rowTpl === false) {
            throw new \RuntimeException("Template non trovato: _product_row.html");
        }

        // 4) Costruisci righe (gestione stato vuoto)
        $rowsHtml = '';
        if (empty($products)) {
            $colspan = 6;
            $rowsHtml = '<tr><td colspan="' . $colspan . '" class="empty" role="status">Non ci sono prodotti al momento.</td></tr>';
        } else {
            foreach ($products as $p) {
                $row = new Template('product_row', $rowTpl);
                $row->insert('id',           (string)(int)$p['product_id']);
                $row->insert('name',         htmlspecialchars($p['name']));
                $row->insert('manufacturer', htmlspecialchars($p['manufacturer']));
                $row->insert('price',        number_format((float)$p['price'], 2));
                $row->insert('availability', (string)(int)$p['availability']);
                $row->insert('csrf',         $csrf); // per la form di delete nel partial
                $rowsHtml .= $row->build();
            }
        }

        // 5) Inserisci nel wrapper
        $table = new Template('all_products', $tableTpl);
        $table->insert('products_rows', $rowsHtml);

        return $table->build();
    }

    /**
     * =========================
     * Component: Utenti
     * =========================
     */
    private function renderUsersComponent(): string
    {
        // Chi sono io? Sono admin?
        $me = $this->auth->getUser();
        if (!$me instanceof UserDTO) {
            throw new \RuntimeException('Utente non autenticato.');
        }
        $myId     = (int)$me->getId();
        $iAmAdmin = method_exists($me, 'isAdmin') ? (bool)$me->isAdmin() : $this->isUserAdminById($myId);

        // Dati
        $sql = "
        SELECT user_id, email, first_name, last_name, tax_code, is_admin
        FROM users
        ORDER BY last_name, first_name
    ";
        $result = $this->mysqli->query($sql);
        if (!$result) {
            throw new \RuntimeException('Errore nella query utenti: ' . $this->mysqli->error);
        }
        $users = $result->fetch_all(MYSQLI_ASSOC);

        // CSRF
        $csrf = $this->ensureCsrfToken();

        // Template: un solo row template (niente *_public.html)
        $listTplPath = __DIR__ . '/../html/area_personale/card/all_users.html';
        $rowTplPath  = __DIR__ . '/../html/area_personale/card/_user_row.html';

        $listTpl = file_get_contents($listTplPath);
        if ($listTpl === false) {
            throw new \RuntimeException('Template non trovato: all_users.html');
        }
        $rowTpl = file_get_contents($rowTplPath);
        if ($rowTpl === false) {
            throw new \RuntimeException('Template non trovato: _user_row.html');
        }

        // Costruzione righe
        $rowsHtml = '';
        foreach ($users as $u) {
            $userId  = (int)$u['user_id'];
            $isAdmin = (bool)$u['is_admin'];
            $isSelf  = ($userId === $myId);

            $row = new Template('user_row', $rowTpl);
            $row->insert('id',         (string)$userId);
            $row->insert('first_name', htmlspecialchars((string)$u['first_name']));
            $row->insert('last_name',  htmlspecialchars((string)$u['last_name']));
            $row->insert('csrf',       $csrf);

            if ($iAmAdmin) {
                // Vista admin: tutti i dati visibili
                $row->insert('email',    htmlspecialchars((string)$u['email']));
                $row->insert('tax_code', htmlspecialchars((string)$u['tax_code']));
                $row->insert('role',     $isAdmin ? 'Admin' : 'Utente');
            } else {
                // Vista non-admin: dati sensibili oscurati
                $row->insert('email',    '—');
                $row->insert('tax_code', '—');
                $row->insert('role',     '—');
            }
            if ($isSelf) {
                $row->insert(
                    'edit_control',
                    '<a class="btn-edit" href="?section=dati">Modifica</a>'
                );
            } else {
                $row->insert('edit_control', '');
            }

            $row->insert('delete_disabled', $isSelf ? 'disabled aria-disabled="true"' : '');

            $rowsHtml .= $row->build();
        }

        // Wrapper
        $list = new Template('all_users', $listTpl);
        $list->insert('users_rows', $rowsHtml);
        return $list->build();
    }

    /**
     * =========================
     * Profilo / Aggiornamenti
     * =========================
     */

    /**
     * Ritorna il profilo utente completo per la vista "I miei dati".
     * @return \stdClass|null  Proprietà: user_id, first_name, last_name, email, tax_code
     */
    public function getProfiloUtente(int $userId): ?\stdClass
    {
        $stmt = $this->mysqli->prepare("
            SELECT user_id, first_name, last_name, email, tax_code
            FROM users
            WHERE user_id = ?
            LIMIT 1
        ");
        if (!$stmt) {
            throw new \RuntimeException("Errore prepare getProfiloUtente: " . $this->mysqli->error);
        }

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        if (!$res) {
            throw new \RuntimeException("Errore execute getProfiloUtente: " . $stmt->error);
        }

        $row = $res->fetch_assoc();
        if (!$row) {
            return null;
        }

        $o = new \stdClass();
        $o->user_id    = (int)$row['user_id'];
        $o->first_name = (string)$row['first_name'];
        $o->last_name  = (string)$row['last_name'];
        $o->email      = (string)$row['email'];
        $o->tax_code   = (string)$row['tax_code'];
        return $o;
    }

    /**
     * Aggiorna i dati base del profilo utente.
     * $data atteso: first_name, last_name, email, tax_code (già validati a monte).
     */
    public function updateProfiloUtente(int $userId, array $data): void
    {
        $firstName = $data['first_name'] ?? '';
        $lastName  = $data['last_name']  ?? '';
        $email     = $data['email']      ?? '';
        $taxCode   = $data['tax_code']   ?? '';

        $stmt = $this->mysqli->prepare("
            UPDATE users
            SET first_name = ?, last_name = ?, email = ?, tax_code = ?
            WHERE user_id = ?
            LIMIT 1
        ");
        if (!$stmt) {
            throw new \RuntimeException("Errore prepare updateProfiloUtente: " . $this->mysqli->error);
        }

        $stmt->bind_param('ssssi', $firstName, $lastName, $email, $taxCode, $userId);
        if (!$stmt->execute()) {
            throw new \RuntimeException("Errore execute updateProfiloUtente: " . $stmt->error);
        }

        // Allinea eventuali dati in sessione se l'AuthService espone un metodo dedicato
        if (method_exists($this->auth, 'refreshSessionUserData')) {
            try {
                $this->auth->refreshSessionUserData($userId);
            } catch (\Throwable $e) {
                // Non è bloccante per il salvataggio
            }
        }
    }

    /**
     * Verifica se l'email è già usata da un altro utente (utile per la validazione server-side).
     * Ritorna true se esiste un altro utente con la stessa email.
     */
    public function emailEsistePerAltroUtente(string $email, int $excludeUserId): bool
    {
        $stmt = $this->mysqli->prepare("
            SELECT 1
            FROM users
            WHERE email = ?
              AND user_id <> ?
            LIMIT 1
        ");
        if (!$stmt) {
            throw new \RuntimeException("Errore prepare emailEsistePerAltroUtente: " . $this->mysqli->error);
        }

        $stmt->bind_param('si', $email, $excludeUserId);
        $stmt->execute();
        $res = $stmt->get_result();
        if (!$res) {
            throw new \RuntimeException("Errore execute emailEsistePerAltroUtente: " . $stmt->error);
        }

        return (bool)$res->fetch_row();
    }

    /**
     * Verifica la password di conferma per l’utente corrente.
     * Usa AuthService se disponibile, altrimenti password_verify su DB.
     */
    public function verifyPassword(string $password): void
    {
        if ($password === '') {
            throw new RuntimeException('Password di conferma mancante.');
        }

        // 1) Se AuthService espone checkPassword(), usalo
        if (method_exists($this->auth, 'checkPassword')) {
            if ($this->auth->checkPassword($password)) {
                return;
            }
            throw new RuntimeException('Password di conferma errata.');
        }

        // 2) Fallback diretto su DB con password_hash/password_verify
        $user = $this->auth->getUser();
        if (!$user instanceof UserDTO) {
            throw new RuntimeException('Utente non autenticato.');
        }

        $uid = $user->getId();
        $stmt = $this->mysqli->prepare('SELECT password_hash FROM users WHERE user_id = ? LIMIT 1');
        if (!$stmt) {
            throw new RuntimeException('Errore di sistema (prep verifica password).');
        }
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if (!$row) {
            throw new RuntimeException('Utente non trovato.');
        }

        $expected = $row['password_hash'];
        if (!password_verify($password, $expected)) {
            throw new RuntimeException('Password di conferma errata.');
        }
    }

    /**
     * Cambia la password (verifica prima la corrente).
     */
    public function changePassword(string $current, string $new): void
    {
        if ($current === '' || $new === '') {
            throw new \RuntimeException('Compila i campi per il cambio password.');
        }
        if (strlen($new) < 8) {
            throw new \RuntimeException('La nuova password deve avere almeno 8 caratteri.');
        }

        // Verifica current
        $this->verifyPassword($current);

        $user = $this->auth->getUser();
        if (!$user instanceof UserDTO) {
            throw new \RuntimeException('Utente non autenticato.');
        }

        $uid  = (int)$user->getId();
        $hash = password_hash($new, PASSWORD_DEFAULT);

        $stmt = $this->mysqli->prepare('UPDATE users SET password_hash = ? WHERE user_id = ? LIMIT 1');
        if (!$stmt) {
            throw new \RuntimeException('Errore di sistema (prep update password).');
        }
        $stmt->bind_param('si', $hash, $uid);
        $ok = $stmt->execute();
        $stmt->close();

        if (!$ok) {
            throw new \RuntimeException('Errore durante l’aggiornamento della password.');
        }
    }

    /**
     * Aggiorna first_name, last_name, email, tax_code.
     * - normalizza email (trim+lower) e CF (trim+upper)
     * - controlla unicità email
     */
    public function updateProfile(array $data): string
    {
        $user = $this->auth->getUser();
        if (!$user instanceof UserDTO) {
            throw new \RuntimeException('Utente non autenticato.');
        }

        // Normalizzazione + validazioni
        $first = trim((string)($data['first_name'] ?? ''));
        $last  = trim((string)($data['last_name']  ?? ''));
        $email = strtolower(trim((string)($data['email'] ?? '')));
        $tax   = strtoupper(trim((string)($data['tax_code'] ?? '')));
        if ($first === '' || $last === '') throw new \RuntimeException('Nome e cognome sono obbligatori.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new \RuntimeException('Email non valida.');
        if (!preg_match('/^[A-Z0-9]{16}$/', $tax)) throw new \RuntimeException('Codice fiscale non valido.');

        $uid = (int)$user->getId();

        // 1) Carica valori correnti
        $stmt = $this->mysqli->prepare('SELECT first_name,last_name,email,tax_code FROM users WHERE user_id = ? LIMIT 1');
        if (!$stmt) throw new \RuntimeException('Errore sistema (prep select current).');
        $stmt->bind_param('i', $uid);
        if (!$stmt->execute()) throw new \RuntimeException('Errore sistema (exec select current).');
        $res = $stmt->get_result();
        if (!$res) throw new \RuntimeException('Errore sistema (result select current).');
        $current = $res->fetch_assoc();
        $stmt->close();
        if (!$current) throw new \RuntimeException('Utente non trovato.');

        $noOp =
            (trim((string)$current['first_name']) === $first) &&
            (trim((string)$current['last_name'])  === $last)  &&
            (strtolower((string)$current['email']) === $email) &&
            (strtoupper((string)$current['tax_code']) === $tax);

        if ($noOp) {
            return 'noop';
        }

        // 2) Unicità email
        $stmt = $this->mysqli->prepare('SELECT 1 FROM users WHERE email = ? AND user_id <> ? LIMIT 1');
        if (!$stmt) throw new \RuntimeException('Errore sistema (prep check email).');
        $stmt->bind_param('si', $email, $uid);
        if (!$stmt->execute()) throw new \RuntimeException('Errore sistema (exec check email).');
        $exists = (bool)$stmt->get_result()->fetch_row();
        $stmt->close();
        if ($exists) throw new \RuntimeException('Email già in uso.');

        // 3) UPDATE
        $stmt = $this->mysqli->prepare(
            'UPDATE users SET first_name = ?, last_name = ?, email = ?, tax_code = ? WHERE user_id = ? LIMIT 1'
        );
        if (!$stmt) throw new \RuntimeException('Errore sistema (prep update).');
        $stmt->bind_param('ssssi', $first, $last, $email, $tax, $uid);

        try {
            if (!$stmt->execute()) {
                throw new \RuntimeException('Errore durante l’aggiornamento del profilo.');
            }
        } catch (\mysqli_sql_exception $e) {
            if ((int)$e->getCode() === 1062) {
                throw new \RuntimeException('Email già in uso.');
            }
            error_log('[updateProfile] SQL error '.$e->getCode().': '.$e->getMessage());
            throw new \RuntimeException('Errore durante l’aggiornamento del profilo.');
        } finally {
            $stmt->close();
        }

        // 4) Riallinea sessione (best-effort)
        try {
            $this->auth->reloadUserFromDbAndSyncSession();
        } catch (\Throwable $e) {
            // non bloccare l’utente
        }

        return 'updated';
    }
}