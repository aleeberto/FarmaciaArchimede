<div class="add-product-bar">
    <span>Aggiungi prodotto</span>
    <button class="btn-add-product">Aggiungi</button>
</div>

<div class="table-responsive">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome breve</th>
                <th>Nome completo</th>
                <th>Produttore</th>
                <th>Tipo</th>
                <th>Prezzo</th>
                <th>Disponibilità</th>
                <th>Azioni</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($products as $product): ?>
            <tr>
                <td><?= $product['product_id'] ?></td>
                <td><?= htmlspecialchars($product['short_name']) ?></td>
                <td><?= htmlspecialchars($product['name']) ?></td>
                <td><?= htmlspecialchars($product['manufacturer']) ?></td>
                <td><?= htmlspecialchars($product['type_name']) ?></td>
                <td><?= number_format($product['price'], 2, ',', '.') ?> €</td>
                <td><?= $product['availability'] ?></td>
                <td class="actions">
                    <button class="btn-edit" data-id="<?= $product['product_id'] ?>">Modifica</button>
                    <button class="btn-delete" data-id="<?= $product['product_id'] ?>">Elimina</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
