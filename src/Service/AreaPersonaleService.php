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
        $this->auth = $auth;
        $this->mysqli = $db->connect();
    }

    public function getAreaPersonaleData(): array
    {
        $userDTO = $this->auth->getUser();
        if (!$userDTO instanceof UserDTO) {
            throw new RuntimeException("Utente non autenticato.");
        }

        if ($userDTO->isAdmin()) {
            return $this->getAdminData($userDTO);
        }

        return $this->getUserData($userDTO);
    }

    private function getAdminData(UserDTO $userDTO): array
    {
        return [
            'user' => [
                'user_id'    => $userDTO->getId(),
                'email'      => $userDTO->getEmail(),
                'first_name' => $userDTO->getFirstName(),
                'last_name'  => $userDTO->getLastName(),
                'tax_code'   => $userDTO->getTaxCode(),
                'is_admin'   => true,
            ],
            'user_section_display'  => 'none',
            'admin_section_display' => 'block',
            'all_orders'   => $this->renderOrdersComponent(),
            'all_products' => $this->renderProductsComponent(),
            'all_users'    => $this->renderUsersComponent(),
        ];
    }

    private function getUserData(UserDTO $userDTO): array
    {
        return [
            'user' => [
                'user_id'    => $userDTO->getId(),
                'email'      => $userDTO->getEmail(),
                'first_name' => $userDTO->getFirstName(),
                'last_name'  => $userDTO->getLastName(),
                'tax_code'   => $userDTO->getTaxCode(),
                'is_admin'   => false,
            ],
            'user_section_display'  => 'block',
            'admin_section_display' => 'none',
            'user_orders' => $this->renderUserOrdersComponent($userDTO->getId()),
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
                        <button class='btn-edit' data-id='{$orderId}'>Modifica</button>
                        <button class='btn-delete' data-id='{$orderId}'>Elimina</button>
                      </td>";
            $rows .= "</tr>";
        }

        $templateHtml = file_get_contents(__DIR__ . '/../html/areapersonale/all_orders.html');
        $template = new Template('all_orders', $templateHtml);
        $template->insert('orders_rows', $rows);
        return $template->build();
    }

    private function renderProductsComponent(): string
    {
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
        $rows = '';

        foreach ($products as $p) {
            $id = $p['product_id'];
            $rows .= "<tr>";
            $rows .= "<td>{$id}</td>";
            $rows .= "<td>" . htmlspecialchars($p['name']) . "</td>";
            $rows .= "<td>" . htmlspecialchars($p['manufacturer']) . "</td>";
            $rows .= "<td>" . number_format($p['price'], 2) . "</td>";
            $rows .= "<td>{$p['availability']}</td>";
            $rows .= "<td>
                        <button class='btn-edit' data-id='{$id}'>Modifica</button>
                        <button class='btn-delete' data-id='{$id}'>Elimina</button>
                      </td>";
            $rows .= "</tr>";
        }

        $templateHtml = file_get_contents(__DIR__ . '/../html/areapersonale/all_products.html');
        $template = new Template('all_products', $templateHtml);
        $template->insert('products_rows', $rows);
        return $template->build();
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
                        <button class='btn-edit' data-id='{$userId}'>Modifica</button>
                        <button class='btn-delete' data-id='{$userId}'>Elimina</button>
                      </td>";
            $rows .= "</tr>";
        }

        $templateHtml = file_get_contents(__DIR__ . '/../html/areapersonale/all_users.html');
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
            $html = "<p>Non hai ancora effettuato ordini.</p>";
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

        $templateHtml = file_get_contents(__DIR__ . '/../html/areapersonale/user_orders.html');
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
}
