<div class="user-orders-container">
    <ul class="user-orders">
        <?php if (empty($orders)): ?>
            <li class="no-orders">Nessun ordine effettuato</li>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <li>
                    <div class="order-header">
                        <span class="order-id">Ordine #<?= $order['order_id'] ?></span>
                        <span class="order-date"><?= htmlspecialchars($order['created_at']) ?></span>
                    </div>
                    <div class="order-details">
                        <span><?= $order['items_count'] ?> prodotti</span>
                        <span class="order-total"><?= number_format($order['total_amount'], 2, ',', '.') ?> €</span>
                        <button class="btn-details" data-id="<?= $order['order_id'] ?>">Dettagli ordine</button>
                    </div>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</div>
