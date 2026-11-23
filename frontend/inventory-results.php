<?php
require __DIR__ . '/../server/app.php';
require __DIR__ . '/../server/layout.php';
requireAccount();
require __DIR__ . '/db.php';

$error = '';

// ---------- DELETE ----------
if (postRequest() && input(INPUT_POST, 'action') === 'delete') {
    if (!$isManager) {
        http_response_code(403);
        exit('Manager access is required.');
    }
    requireCsrf();
    $id = integer(INPUT_POST, 'inventoryId');
    if ($id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM INVENTORY WHERE InventoryID = ?");
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 1) {
                redirect('inventory-results.php?msg=deleted');
            }
            http_response_code(404);
            $error = "That inventory record no longer exists.";
        } catch (PDOException $e) {
            error_log($e->getMessage());
            http_response_code(500);
            $error = "Could not delete the inventory record.";
        }
    } else {
        http_response_code(400);
        $error = "Choose a valid inventory record to delete.";
    }
}

// ---------- Flash messages (fixed text only, never echoes user input) ----------
$messages = [
    'created' => 'Inventory record created.',
    'updated' => 'Inventory record updated.',
    'deleted' => 'Inventory record deleted.',
];
$message = $messages[(string)filter_input(INPUT_GET, 'msg')] ?? '';

// ---------- READ (with optional filters) ----------
$search   = trim((string)filter_input(INPUT_GET, 'searchName'));
$category = integer(INPUT_GET, 'category');
$lowOnly  = isset($_GET['lowOnly']);

$sql = "
    SELECT i.InventoryID, p.ProductName, c.CategoryName, i.Quantity, i.MinStock, i.LastUpdated
    FROM INVENTORY i
    JOIN PRODUCT  p ON i.ProductID  = p.ProductID
    JOIN CATEGORY c ON p.CategoryID = c.CategoryID
    WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND p.ProductName LIKE ?";
    $params[] = '%' . $search . '%';
}
if ($category) {
    $sql .= " AND c.CategoryID = ?";
    $params[] = $category;
}
if ($lowOnly) {
    $sql .= " AND i.Quantity <= i.MinStock";
}
$sql .= " ORDER BY i.LastUpdated DESC, p.ProductName";

$inventoryList = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $inventoryList = $stmt->fetchAll();
    $categories = categories($pdo);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    $error = "Could not load inventory results.";
    $categories = [];
}
?>
<?php pageStart('Inventory Query Results', 'Inventory Update', 'inventory'); ?>

<main>
  <aside class="sections">
    <a href="inventory.php">Update Inventory</a>
    <a href="inventory-results.php" class="active">Inventory Query Results</a>
  </aside>

  <section class="content">
    <h3>Inventory Query Results</h3>

    <?php if ($message): ?>
      <p style="color: green; font-weight: bold;"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
      <p style="color: #b00020; font-weight: bold;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form action="inventory-results.php" method="GET">
      <label for="searchName">Product Name:</label>
      <input type="text" id="searchName" name="searchName" value="<?= htmlspecialchars($search) ?>">

      <label for="category">Category:</label>
      <select id="category" name="category">
        <option value="">All Categories</option>
        <?php categoryOptions($categories, $category); ?>
      </select>

      <label><input type="checkbox" name="lowOnly" value="1" <?= $lowOnly ? 'checked' : '' ?>> Low stock only</label>

      <button type="submit">Filter</button>
      <a href="inventory-results.php"><button type="button">Clear Filters</button></a>
    </form>

    <p class="note">Displaying live MySQL query results from the database.</p>

    <table>
      <thead>
      <tr>
        <th>Inventory ID</th>
        <th>Product</th>
        <th>Category</th>
        <th>Quantity</th>
        <th>Min Stock</th>
        <th>Last Updated</th>
        <th>Actions</th>
      </tr>
      </thead>
      <tbody>
      <?php if (count($inventoryList) > 0): ?>
        <?php foreach ($inventoryList as $row): ?>
        <tr>
          <td><?= (int)$row['InventoryID'] ?></td>
          <td><?= htmlspecialchars($row['ProductName']) ?></td>
          <td><?= htmlspecialchars($row['CategoryName']) ?></td>
          <td <?= ($row['Quantity'] <= $row['MinStock']) ? 'style="color: red; font-weight: bold;"' : '' ?>>
            <?= (int)$row['Quantity'] ?>
          </td>
          <td><?= (int)$row['MinStock'] ?></td>
          <td><?= htmlspecialchars($row['LastUpdated']) ?></td>
          <td>
            <a href="inventory.php?edit=<?= (int)$row['InventoryID'] ?>">Edit</a>
            <?php if ($isManager): ?>
            <form action="inventory-results.php" method="POST" style="display:inline"
                  onsubmit="return confirm('Delete this inventory record?');">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="inventoryId" value="<?= (int)$row['InventoryID'] ?>">
              <button type="submit">Delete</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="7">No inventory records found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </section>
</main>

</body>
</html>
