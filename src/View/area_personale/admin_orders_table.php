<div class="table-responsive">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Data</th>
                <th>Cliente</th>
                <th>Prodotti</th>
                <th>Totale</th>
                <th>Azioni</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td><?= $order['order_id'] ?></td>
                <td><?= htmlspecialchars($order['created_at']) ?></td>
                <td><?= htmlspecialchars($order['first_name']) ?> <?= htmlspecialchars($order['last_name']) ?></td>
                <td><?= $order['items_count'] ?></td>
                <td><?= number_format($order['total_amount'], 2, ',', '.') ?> €</td>
                <td class="actions">
                    <button class="btn-details" data-id="<?= $order['order_id'] ?>">Dettagli</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
