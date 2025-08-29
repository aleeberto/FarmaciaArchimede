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
        $this->auth    = $auth;
        $this->mysqli  = $db->connect();
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
     * Usa htmlspecialchars per sicurezza dove serve (già pronto per HTML).
     */
    private function renderRow(string $tpl, array $data): string
    {
        $t = new Template('row', $tpl);
        foreach ($data as $k => $v) {
            // di default escape per testo; se passi HTML “sicuro”, escapa a monte e qui usa così com’è
            $t->insert($k, (string)$v);
        }
        return $t->build();
    }


    /**
     * Dati base dell’utente
     */
    public function getDatiUtente(): array
    {
        $user = $this->auth->getUser();
        if (!$user instanceof UserDTO) {
            throw new \RuntimeException("Utente non autenticato.");
        }

        // 🔁 Leggi sempre dal DB per evitare dati stantii in sessione
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

    /**
     * Elenco ordini dell’utente
     */
    public function getOrdiniUtente(): array
    {
        $user = $this->auth->getUser();
        return [
            'user_orders' => $this->renderUserOrdersComponent($user->getId()),
        ];
    }

    /**
     * Elenco prodotti (solo admin)
     */
    public function getProdottiAdmin(): array
    {
        return [
            'all_products' => $this->renderProductsComponent(),
        ];
    }

    /**
     * Elenco ordini (solo admin)
     */
    public function getOrdiniAdmin(): array
    {
        return [
            'all_orders' => $this->renderOrdersComponent(),
        ];
    }

    /**
     * Elenco utenti (solo admin)
     */
    public function getUtentiAdmin(): array
    {
        return [
            'all_users' => $this->renderUsersComponent(),
        ];
    }

    private function renderOrdersComponent(): string
    {
        $sql = "
            SELECT o.order_id, o.created_at, u.first_name, u.last_name, u.email
            FROM orders o
            JOIN users u ON o.user_id = u.user_id
            ORDER BY o.created_at DESC
        ";

        $result = $this->mysqli->query($sql);
        if (!$result) {
            throw new \RuntimeException("Errore nella query ordini: " . $this->mysqli->error);
        }

        $orders = $result->fetch_all(MYSQLI_ASSOC);
        $rows = '';

        foreach ($orders as $order) {
            $orderId = $order['order_id'];
            $date = $order['created_at'];
            $name = htmlspecialchars($order['first_name'] . ' ' . $order['last_name']);
            $email = htmlspecialchars($order['email']);

            $rows .= "<tr>";
            $rows .= "<td>{$orderId}</td>";
            $rows .= "<td>{$date}</td>";
            $rows .= "<td>{$name}</td>";
            $rows .= "<td>{$email}</td>";
            $rows .= "<td>
                <a class='btn-edit' href='/modifica.php?id=" . (int)$orderId . "'>Modifica</a>
                <button class='btn-delete' data-id='{$orderId}'>Elimina</button>
                </td>";

            $rows .= "</tr>";
        }

        $templateHtml = file_get_contents(__DIR__ . '/../html/area_personale/tabelle/all_orders.html');
        $template = new Template('all_orders', $templateHtml);
        $template->insert('orders_rows', $rows);
        return $template->build();
    }

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
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
        }
        $csrf = $_SESSION['csrf_token'];

        // 3) Carica template
        $tableTpl = file_get_contents(__DIR__ . '/../html/area_personale/tabelle/all_products.html');
        if ($tableTpl === false) {
            throw new \RuntimeException("Template non trovato: all_products.html");
        }
        $rowTpl = file_get_contents(__DIR__ . '/../html/area_personale/tabelle/_product_row.html');
        if ($rowTpl === false) {
            throw new \RuntimeException("Template non trovato: _product_row.html");
        }

        // 4) Costruisci righe
        $rowsHtml = '';
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

        // 5) Inserisci nel wrapper
        $table = new Template('all_products', $tableTpl);
        $table->insert('products_rows', $rowsHtml);

        return $table->build();
    }


    private function renderUsersComponent(): string
    {
        $sql = "
            SELECT user_id, email, first_name, last_name, tax_code, is_admin
            FROM users
            ORDER BY last_name, first_name
        ";

        $result = $this->mysqli->query($sql);
        if (!$result) {
            throw new \RuntimeException("Errore nella query utenti: " . $this->mysqli->error);
        }

        $users = $result->fetch_all(MYSQLI_ASSOC);
        $rows = '';

        foreach ($users as $u) {
            $userId = $u['user_id'];
            $role = $u['is_admin'] ? 'Admin' : 'Utente';

            $rows .= "<tr>";
            $rows .= "<td>{$userId}</td>";
            $rows .= "<td>" . htmlspecialchars($u['email']) . "</td>";
            $rows .= "<td>" . htmlspecialchars($u['first_name']) . "</td>";
            $rows .= "<td>" . htmlspecialchars($u['last_name']) . "</td>";
            $rows .= "<td>" . htmlspecialchars($u['tax_code']) . "</td>";
            $rows .= "<td>{$role}</td>";
            $rows .= "<td>
                <a class='btn-edit' href='?area_personale.php?section=dati.php?id=" . (int)$userId . "'>Modifica</a>
                <button class='btn-delete' data-id='{$userId}'>Elimina</button>
                </td>";

            $rows .= "</tr>";
        }

        $templateHtml = file_get_contents(__DIR__ . '/../html/area_personale/tabelle/all_users.html');
        $template = new Template('all_users', $templateHtml);
        $template->insert('users_rows', $rows);
        return $template->build();
    }

    private function renderUserOrdersComponent(int $userId): string
    {
        $stmt = $this->mysqli->prepare("
            SELECT order_id, created_at
            FROM orders
            WHERE user_id = ?
            ORDER BY created_at DESC
        ");
        if (!$stmt) {
            throw new \RuntimeException("Errore prepare getUserOrders: " . $this->mysqli->error);
        }

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result) {
            throw new \RuntimeException("Errore execute getUserOrders: " . $stmt->error);
        }

        $orders = $result->fetch_all(MYSQLI_ASSOC);
        $html = '';

        if (empty($orders)) {
            $html = "<p class='order-info'>Non hai ancora effettuato ordini.</p>";
        } else {
            foreach ($orders as $order) {
                $html .= "<article class='order'>";
                $html .= "<h3>Ordine #{$order['order_id']} - {$order['created_at']}</h3><ul>";

                $items = $this->getOrderItems($order['order_id']);
                foreach ($items as $item) {
                    $html .= "<li>" . htmlspecialchars($item['product_name']) . " x {$item['quantity']}</li>";
                }

                $html .= "</ul></article>";
            }
        }

        $templateHtml = file_get_contents(__DIR__ . '/../html/area_personale/tabelle/user_orders.html');
        $template = new Template('user_orders', $templateHtml);
        $template->insert('user_orders_list', $html);
        return $template->build();
    }

    private function getOrderItems(int $orderId): array
    {
        $stmt = $this->mysqli->prepare("
            SELECT p.name AS product_name, oi.quantity
            FROM order_items oi
            JOIN products p ON oi.product_id = p.product_id
            WHERE oi.order_id = ?
        ");
        if (!$stmt) {
            throw new \RuntimeException("Errore prepare getOrderItems: " . $this->mysqli->error);
        }

        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result) {
            throw new \RuntimeException("Errore execute getOrderItems: " . $stmt->error);
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    }

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
                // Non è bloccante per il salvataggio: logga se hai un logger
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
     * Usa AuthService se disponibile, altrimenti confronta SHA-256 esadecimale su DB.
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

        // >>> qui usi password_verify <<<
        if (!password_verify($password, $expected)) {
            throw new RuntimeException('Password di conferma errata.');
        }
    }


    /**
     * Cambia la password (verifica prima la corrente).
     * Salva SHA-256 esadecimale in users.password_hash.
     */
    public function changePassword(string $current, string $new): void
    {
        if ($current === '' || $new === '') {
            throw new \RuntimeException('Compila i campi per il cambio password.');
        }
        if (strlen($new) < 8) {
            throw new \RuntimeException('La nuova password deve avere almeno 8 caratteri.');
        }

        // Verifica current (usa verifyPassword che già fa password_verify)
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
        // NIENTE mysqli_report() qui: lascia la policy globale com’è
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

        // 1) Carica valori correnti (no eccezioni: controlli espliciti)
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

        // 3) UPDATE con gestione errori puntuale
        $stmt = $this->mysqli->prepare(
            'UPDATE users SET first_name = ?, last_name = ?, email = ?, tax_code = ? WHERE user_id = ? LIMIT 1'
        );
        if (!$stmt) throw new \RuntimeException('Errore sistema (prep update).');
        $stmt->bind_param('ssssi', $first, $last, $email, $tax, $uid);

        try {
            if (!$stmt->execute()) {
                // in teoria, con check espliciti, qui non arrivi
                throw new \RuntimeException('Errore durante l’aggiornamento del profilo.');
            }
        } catch (\mysqli_sql_exception $e) {
            // Mappa errori noti e logga gli altri
            if ((int)$e->getCode() === 1062) {
                throw new \RuntimeException('Email già in uso.');
            }
            // TODO: usa il tuo logger invece di error_log
            error_log('[updateProfile] SQL error '.$e->getCode().': '.$e->getMessage());
            throw new \RuntimeException('Errore durante l’aggiornamento del profilo.');
        } finally {
            $stmt->close();
        }

        // 4) Riallinea sessione (non deve far fallire il salvataggio)

        try {
            $this->auth->reloadUserFromDbAndSyncSession();
        } catch (\Throwable $e) {
                var_dump('[updateProfile] sync session failed: '.$e->getMessage());
                // non bloccare l’utente
        }


        return 'updated';
    }



}
