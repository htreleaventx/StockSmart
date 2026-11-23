<?php
require __DIR__ . '/../server/app.php';
require __DIR__ . '/../server/layout.php';
requireAccount();
require __DIR__ . '/db.php';

$search = trim(input(INPUT_GET, 'searchName'));
$category = integer(INPUT_GET, 'category');
$categories = categories($pdo);
$query = '
    SELECT p.ProductID, p.ProductName, c.CategoryName, COALESCE(i.Quantity, 0) AS Quantity, p.ExpirationDate
    FROM PRODUCT p
    JOIN CATEGORY c ON c.CategoryID = p.CategoryID
    LEFT JOIN INVENTORY i ON i.ProductID = p.ProductID
    WHERE p.ProductName LIKE ?';
$params = ['%' . $search . '%'];
if ($category !== null) {
    $query .= ' AND p.CategoryID = ?';
    $params[] = $category;
}
$stmt = $pdo->prepare($query . ' ORDER BY p.ProductName, p.ProductID');
$stmt->execute($params);
$products = $stmt->fetchAll();
?>
<?php pageStart('Alert Dashboard', 'Alert Dashboard', 'dashboard'); ?>

<main>
  <aside class="sections">
    <a href="index.php" class="active">Search / Filter Inventory</a>
    <a href="low-stock.php">Low Stock Alerts</a>
    <a href="expiring.php">Expiring Soon</a>
    <?php if ($isManager): ?><a href="accounts.php">User Management</a><?php endif; ?>
    <form action="login.php" method="post">
      <?= csrfField() ?>
      <button type="submit" name="action" value="logout">Sign Out</button>
    </form>
  </aside>

  <section class="content">
    <h3>Search / Filter Inventory</h3>

    <form action="index.php" method="get">

      <label for="searchName">Product Name:</label>
      <input type="text" id="searchName" name="searchName" value="<?= e($search) ?>">
      <br><br>

      <label for="category">Category:</label>
      <select id="category" name="category">
        <option value="">All Categories</option>
        <?php categoryOptions($categories, $category); ?>
      </select>
      <br><br>

      <button type="submit">Search</button>
      <button type="reset" onclick="window.location.href='index.php'">Clear Filters</button>
    </form>

    <h3 class="spaced">Search Results</h3>

    <?php if (!$products): ?><p class="note">No products found.</p><?php endif; ?>

    <table>
      <thead>
      <tr>
        <th>Product ID</th>
        <th>Product Name</th>
        <th>Category</th>
        <th>Quantity</th>
        <th>Expiration Date</th>
      </tr>
      </thead>
      <tbody>
      <?php foreach ($products as $product): ?>
        <tr>
          <td><?= e($product['ProductID']) ?></td>
          <td><?= e($product['ProductName']) ?></td>
          <td><?= e($product['CategoryName']) ?></td>
          <td><?= e($product['Quantity']) ?></td>
          <td><?= e($product['ExpirationDate'] ?? '—') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </section>
</main>

</body>
</html>
