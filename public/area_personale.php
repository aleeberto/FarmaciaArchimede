<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Core\PageBuilder;
use App\Core\View;
use App\Service\AuthService;

// Connessione al DB
$db = Database::getInstance(
    getenv('MARIADB_HOST') ?: 'mariadb',
    getenv('MARIADB_USER') ?: 'admin',
    getenv('MARIADB_PASSWORD') ?: 'admin',
    getenv('MARIADB_DATABASE') ?: 'farmacia_archimede'
);

$auth = new AuthService($db);

if (!$auth->isLogged()) {
    header('Location: /login.php?error=not_logged');
    exit;
}

$user = $auth->getUser();

$params = [
    'user' => $user,
    'user_section_class' => $user['is_admin'] ? '' : 'show',
    'admin_section_class' => $user['is_admin'] ? 'show' : ''
];

$conn = $db->connect();

if ($user['is_admin']) {
    // --- ADMIN: Prodotti ---
    $productsQuery = $conn->query("
        SELECT p.product_id, p.short_name, p.name, p.manufacturer, p.price, p.availability, pt.name as type_name
        FROM products p
        JOIN product_types pt ON p.product_type_id = pt.product_type_id
        ORDER BY p.product_id
    ");
    $products = $productsQuery->fetch_all(MYSQLI_ASSOC);

    $params['all_products'] = View::renderPartial('area_personale/products_table', ['products' => $products]);

    // --- ADMIN: Ordini ---
    $ordersQuery = $conn->query("
        SELECT o.order_id, o.created_at, u.first_name, u.last_name, 
               COUNT(oi.product_id) as items_count, SUM(oi.quantity * p.price) as total_amount
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        JOIN order_items oi ON o.order_id = oi.order_id
        JOIN products p ON oi.product_id = p.product_id
        GROUP BY o.order_id
        ORDER BY o.created_at DESC
    ");
    $orders = $ordersQuery->fetch_all(MYSQLI_ASSOC);

    $params['all_orders'] = View::renderPartial('area_personale/admin_orders_table', ['orders' => $orders]);

} else {
    // --- UTENTE NORMALE: Ordini personali ---
    $stmt = $conn->prepare("
        SELECT o.order_id, o.created_at, 
               COUNT(oi.product_id) as items_count, SUM(oi.quantity * p.price) as total_amount
        FROM orders o
        JOIN order_items oi ON o.order_id = oi.order_id
        JOIN products p ON oi.product_id = p.product_id
        WHERE o.user_id = ?
        GROUP BY o.order_id
        ORDER BY o.created_at DESC
    ");
    $stmt->bind_param('i', $user['user_id']);
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $params['user_orders'] = View::renderPartial('area_personale/user_orders_list', ['orders' => $orders]);
}

// Visualizza la pagina
PageBuilder::show('area_personale', $params);
