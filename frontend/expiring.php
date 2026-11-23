<?php
require __DIR__ . '/../server/app.php';
require __DIR__ . '/../server/layout.php';
requireAccount();
require __DIR__ . '/db.php';

$error = '';
$rows = [];
$expired = input(INPUT_GET, 'expired') === '1';
$days = isset($_GET['expiringDays']) ? integer(INPUT_GET, 'expiringDays') : 10;
if (!$expired && $days === null) {
    http_response_code(400);
    $error = 'Enter a whole number of days greater than zero.';
} else {
    try {
        $expiryFilter = $expired ? '< 0' : 'BETWEEN 0 AND ?';
        $stmt = $pdo->prepare("
            SELECT p.ProductID, p.ProductName, c.CategoryName, i.InventoryID, i.Quantity, p.ExpirationDate,
                   DATEDIFF(p.ExpirationDate, CURRENT_DATE) AS DaysLeft
            FROM INVENTORY i
            JOIN PRODUCT p ON p.ProductID = i.ProductID
            LEFT JOIN CATEGORY c ON c.CategoryID = p.CategoryID
            WHERE i.Quantity > 0 AND DATEDIFF(p.ExpirationDate, CURRENT_DATE) $expiryFilter
            ORDER BY p.ExpirationDate, p.ProductName, p.ProductID
        ");
        if (!$expired) {
            $stmt->bindValue(1, $days, PDO::PARAM_INT);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log($e->getMessage());
        http_response_code(500);
        $error = 'Could not load expiring inventory.';
    }
}
?>
<?php pageStart('Expiring Soon', 'Alert Dashboard', 'dashboard'); ?>

<main>
  <aside class="sections">
    <a href="index.php">Search / Filter Inventory</a>
    <a href="low-stock.php">Low Stock Alerts</a>
    <a href="expiring.php" class="active">Expiring Soon</a>
  </aside>

  <section class="content">
    <h3><?= $expired ? 'Expired Stock' : 'Expiring Soon' ?></h3>
    <?php if (!$expired): ?>

    <form action="expiring.php" method="get">

      <label for="expiringDays">Expiring Within (days):</label>
      <input type="number" id="expiringDays"
             name="expiringDays" min="1" step="1" required value="<?= $days ?? '' ?>">
      <br><br>

      <button type="submit">Show Products</button>
      <a href="expiring.php">Reset</a>
    </form>
    <?php endif; ?>
    <p class="note"><a href="<?= $expired ? 'expiring.php' : 'expiring.php?expired=1' ?>"><?= $expired ? 'View expiring stock' : 'View expired stock' ?></a></p>

    <?php if ($error): ?>
      <p role="alert"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if (!$error): ?>
    <table>
      <thead>
      <tr>
        <th>Product ID</th>
        <th>Product Name</th>
        <th>Category</th>
        <th>Quantity</th>
        <th>Expiration Date</th>
        <th>Days Left</th>
        <?php if ($expired): ?><th>Actions</th><?php endif; ?>
      </tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="<?= $expired ? 7 : 6 ?>"><?= $expired ? 'No expired products in stock.' : 'No in-stock products expire within that window.' ?></td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $row): ?>
        <tr>
          <td><?= (int)$row['ProductID'] ?></td>
          <td><?= htmlspecialchars($row['ProductName']) ?></td>
          <td><?= htmlspecialchars($row['CategoryName'] ?? '') ?></td>
          <td><?= (int)$row['Quantity'] ?></td>
          <td><?= htmlspecialchars($row['ExpirationDate']) ?></td>
          <td><?= (int)$row['DaysLeft'] ?></td>
          <?php if ($expired): ?>
            <td><a href="inventory.php?edit=<?= (int) $row['InventoryID'] ?>">Adjust Stock</a></td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </section>
</main>

</body>
</html>
