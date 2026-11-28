<?php
require __DIR__ . '/../server/app.php';
require __DIR__ . '/../server/layout.php';
requireAccount();
require __DIR__ . '/db.php';

$error = '';
$rows = [];
try {
    $rows = $pdo->query("
        SELECT p.ProductID, p.ProductName, c.CategoryName, i.Quantity, i.MinStock
        FROM INVENTORY i
        JOIN PRODUCT p ON p.ProductID = i.ProductID
        LEFT JOIN CATEGORY c ON c.CategoryID = p.CategoryID
        WHERE i.Quantity <= i.MinStock
        ORDER BY i.Quantity, p.ProductName, p.ProductID
    ")->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    $error = 'Could not load low stock alerts.';
}
?>
<?php pageStart('Low Stock Alerts', 'Alert Dashboard', 'dashboard'); ?>

<main>
  <aside class="sections">
    <a href="index.php">Search / Filter Inventory</a>
    <a href="low-stock.php" class="active">Low Stock Alerts</a>
    <a href="expiring.php">Expiring Soon</a>
  </aside>

  <section class="content">
    <h3>Low Stock Alerts</h3>

    <?php if ($error): ?>
      <p role="alert"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if (!$error): ?>
        <?php if (!$error): ?>
    <p class="note"><strong><?= count($rows) ?></strong> <?= count($rows) === 1 ? 'item' : 'items' ?> low on stock</p>
    <div class="table-wrap">
      <table>
        <thead>
        <tr>
          <th>Product ID</th>
          <th>Product Name</th>
          <th>Category</th>
          <th>Quantity</th>
          <th>Min Stock</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="5">No low stock alerts.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td><?= (int)$row['ProductID'] ?></td>
            <td><?= htmlspecialchars($row['ProductName']) ?></td>
            <td><?= htmlspecialchars($row['CategoryName'] ?? '') ?></td>
            <td style="color: #b00020; font-weight: bold;"><?= (int)$row['Quantity'] ?></td>
            <td><?= (int)$row['MinStock'] ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </section>
</main>

</body>
</html>
