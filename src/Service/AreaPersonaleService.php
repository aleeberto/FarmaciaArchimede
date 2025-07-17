<?php

namespace App\Service;

use App\Core\Database;
use RuntimeException;

class AreaPersonaleService
{
    private \mysqli $mysqli;
    private AuthService $auth;

    public function __construct(AuthService $auth, Database $db)
    {
        $this->auth = $auth;
        $this->mysqli = $db->connect();
    }

    /**
     * Recupera tutti i dati e HTML necessari per l'area personale.
     *
     * @return array Dati da passare al template.
     */
    public function getAreaPersonaleData(): array
    {
        $user = $this->auth->getUser();
        if (!$user) {
            throw new RuntimeException("Utente non autenticato.");
        }

        if ($user['is_admin']) {
            return $this->getAdminData();
        } else {
            return $this->getUserData($user['user_id']);
        }
    }

    /**
     * Dati e HTML per admin
     */
    private function getAdminData(): array
    {
        return [
            'user' => $this->auth->getUser(),
            'user_section_display' => 'none',
            'admin_section_display' => 'block',
            'all_orders' => $this->getAllOrdersHtml(),
            'all_products' => $this->getAllProductsHtml(),
            'all_users' => $this->getAllUsersHtml(),
        ];
    }

    /**
     * Dati e HTML per utente normale
     */
    private function getUserData(int $userId): array
    {
        $user = $this->auth->getUser();

        return [
            'user' => $user,
            'user_section_display' => 'block',
            'admin_section_display' => 'none',
            'user_orders' => $this->getUserOrdersHtml($userId),
        ];
    }

    /**
     * Recupera tutti gli ordini con dettagli e costruisce HTML per admin
     */
    private function getAllOrdersHtml(): string
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

        $html = '';
        foreach ($orders as $order) {
            $html .= "<article class='order'>";
            $html .= "<h3>Ordine #{$order['order_id']} - {$order['created_at']}</h3>";
            $html .= "<p>Utente: {$order['first_name']} {$order['last_name']} ({$order['email']})</p>";
            $html .= "<ul>";
            $items = $this->getOrderItems($order['order_id']);
            foreach ($items as $item) {
                $html .= "<li>{$item['product_name']} x {$item['quantity']}</li>";
            }
            $html .= "</ul>";
            $html .= "</article>";
        }
        return $html;
    }

    /**
     * Recupera gli articoli di un ordine
     */
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
     * Recupera tutti i prodotti e costruisce HTML per admin
     */
    private function getAllProductsHtml(): string
    {
        $sql = "
            SELECT product_id, short_name, name, manufacturer, price, availability
            FROM products
            ORDER BY name ASC
        ";

        $result = $this->mysqli->query($sql);
        if (!$result) {
            throw new \RuntimeException("Errore nella query prodotti: " . $this->mysqli->error);
        }

        $products = $result->fetch_all(MYSQLI_ASSOC);

        $html = "<table border='1' cellpadding='5'>";
        $html .= "<thead><tr><th>ID</th><th>Nome</th><th>Produttore</th><th>Prezzo (€)</th><th>Disponibilità</th></tr></thead><tbody>";

        foreach ($products as $p) {
            $html .= "<tr>";
            $html .= "<td>{$p['product_id']}</td>";
            $html .= "<td>" . htmlspecialchars($p['name']) . "</td>";
            $html .= "<td>" . htmlspecialchars($p['manufacturer']) . "</td>";
            $html .= "<td>" . number_format($p['price'], 2) . "</td>";
            $html .= "<td>{$p['availability']}</td>";
            $html .= "</tr>";
        }
        $html .= "</tbody></table>";

        return $html;
    }

    /**
     * Recupera tutti gli utenti e costruisce HTML per admin
     */
    private function getAllUsersHtml(): string
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

        $html = "<table border='1' cellpadding='5'>";
        $html .= "<thead><tr><th>ID</th><th>Email</th><th>Nome</th><th>Cognome</th><th>Codice Fiscale</th><th>Ruolo</th></tr></thead><tbody>";

        foreach ($users as $u) {
            $role = $u['is_admin'] ? 'Admin' : 'Utente';
            $html .= "<tr>";
            $html .= "<td>{$u['user_id']}</td>";
            $html .= "<td>" . htmlspecialchars($u['email']) . "</td>";
            $html .= "<td>" . htmlspecialchars($u['first_name']) . "</td>";
            $html .= "<td>" . htmlspecialchars($u['last_name']) . "</td>";
            $html .= "<td>" . htmlspecialchars($u['tax_code']) . "</td>";
            $html .= "<td>{$role}</td>";
            $html .= "</tr>";
        }
        $html .= "</tbody></table>";

        return $html;
    }

    /**
     * Recupera ordini di un singolo utente e costruisce HTML
     */
    private function getUserOrdersHtml(int $userId): string
    {
        $stmt = $this->mysqli->prepare("
            SELECT order_id, created_at
            FROM orders
            WHERE user_id = ?
            ORDER BY created_at DESC
        ");
        if (!$stmt) {
            throw new \RuntimeException("Errore prepare getUserOrdersHtml: " . $this->mysqli->error);
        }

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result) {
            throw new \RuntimeException("Errore execute getUserOrdersHtml: " . $stmt->error);
        }
        $orders = $result->fetch_all(MYSQLI_ASSOC);

        if (empty($orders)) {
            return "<p>Non hai ancora effettuato ordini.</p>";
        }

        $html = '';
        foreach ($orders as $order) {
            $html .= "<article class='order'>";
            $html .= "<h3>Ordine #{$order['order_id']} - {$order['created_at']}</h3><ul>";
            $items = $this->getOrderItems($order['order_id']);
            foreach ($items as $item) {
                $html .= "<li>" . htmlspecialchars($item['product_name']) . " x {$item['quantity']}</li>";
            }
            $html .= "</ul></article>";
        }
        return $html;
    }
}
