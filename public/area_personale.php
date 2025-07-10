<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Core\PageBuilder;
use App\Service\AuthService;

$db  = Database::getInstance(
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

// Preparazione dati in base al tipo di utente
$params = ['user' => $user];

if ($user['is_admin']) {
    // Sezione admin
    $params['user_section_display'] = 'none';
    $params['admin_section_display'] = 'block';
    
    // Recupera tutti i prodotti
    $conn = $db->connect();
    $products = $conn->query("
        SELECT p.product_id, p.short_name, p.name, p.manufacturer, p.price, p.availability, pt.name as type_name
        FROM products p
        JOIN product_types pt ON p.product_type_id = pt.product_type_id
        ORDER BY p.product_id
    ");
    
    $productsHtml = '<table class="admin-table"><thead><tr>
        <th>ID</th><th>Nome breve</th><th>Nome completo</th><th>Produttore</th>
        <th>Tipo</th><th>Prezzo</th><th>Disponibilità</th><th>Azioni</th>
    </tr></thead><tbody>';
    
    while ($product = $products->fetch_assoc()) {
        $productsHtml .= '<tr>
            <td>'.$product['product_id'].'</td>
            <td>'.$product['short_name'].'</td>
            <td>'.$product['name'].'</td>
            <td>'.$product['manufacturer'].'</td>
            <td>'.$product['type_name'].'</td>
            <td>'.number_format($product['price'], 2).' €</td>
            <td>'.$product['availability'].'</td>
            <td>
                <button class="edit-product" data-id="'.$product['product_id'].'">Modifica</button>
                <button class="delete-product" data-id="'.$product['product_id'].'">Elimina</button>
            </td>
        </tr>';
    }
    
    $productsHtml .= '</tbody></table>';
    $params['all_products'] = $productsHtml;
    
    // Recupera tutti gli ordini
    $orders = $conn->query("
        SELECT o.order_id, o.created_at, u.first_name, u.last_name, 
               COUNT(oi.product_id) as items_count, SUM(oi.quantity * p.price) as total_amount
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        JOIN order_items oi ON o.order_id = oi.order_id
        JOIN products p ON oi.product_id = p.product_id
        GROUP BY o.order_id
        ORDER BY o.created_at DESC
    ");
    
    $ordersHtml = '<table class="admin-table"><thead><tr>
        <th>ID</th><th>Data</th><th>Cliente</th><th>Prodotti</th><th>Totale</th>
    </tr></thead><tbody>';
    
    while ($order = $orders->fetch_assoc()) {
        $ordersHtml .= '<tr>
            <td>'.$order['order_id'].'</td>
            <td>'.$order['created_at'].'</td>
            <td>'.$order['first_name'].' '.$order['last_name'].'</td>
            <td>'.$order['items_count'].'</td>
            <td>'.number_format($order['total_amount'], 2).' €</td>
        </tr>';
    }
    
    $ordersHtml .= '</tbody></table>';
    $params['all_orders'] = $ordersHtml;
    
} else {
    // Sezione utente normale
    $params['user_section_display'] = 'block';
    $params['admin_section_display'] = 'none';
    
    // Recupera ordini dell'utente
    $conn = $db->connect();
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
    $result = $stmt->get_result();
    
    $ordersHtml = '<ul class="user-orders">';
    
    if ($result->num_rows === 0) {
        $ordersHtml .= '<li>Nessun ordine effettuato</li>';
    } else {
        while ($order = $result->fetch_assoc()) {
            $ordersHtml .= '<li>
                <strong>Ordine #'.$order['order_id'].'</strong> - 
                '.$order['created_at'].' - 
                '.$order['items_count'].' prodotti - 
                Totale: '.number_format($order['total_amount'], 2).' €
                <a href="#" class="order-details" data-id="'.$order['order_id'].'">Dettagli</a>
            </li>';
        }
    }
    
    $ordersHtml .= '</ul>';
    $params['user_orders'] = $ordersHtml;
}

PageBuilder::show('area_personale', $params);