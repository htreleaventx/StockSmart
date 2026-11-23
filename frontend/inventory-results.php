<?php
require 'db.php';

$error = '';

// ---------- DELETE ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = filter_var($_POST['inventoryId'] ?? '', FILTER_VALIDATE_INT);
    if ($id) {
        try {
            $pdo->prepare("DELETE FROM INVENTORY WHERE InventoryID = ?")->execute([$id]);
            header("Location: inventory-results.php?msg=deleted");
            exit;
        } catch (PDOException $e) {
            $error = "Could not delete: " . $e->getMessage();
        }
    }
}

// ---------- Flash messages (fixed text only, never echoes user input) ----------
$messages = [
    'created' => 'Inventory record created.',
    'updated' => 'Inventory record updated.',
    'deleted' => 'Inventory record deleted.',
];
$message = $messages[$_GET['msg'] ?? ''] ?? '';

// ---------- READ (with optional filters) ----------
$search   = trim($_GET['searchName'] ?? '');
$category = filter_var($_GET['category'] ?? '', FILTER_VALIDATE_INT);
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
    $categories = $pdo->query("SELECT CategoryID, CategoryName FROM CATEGORY ORDER BY CategoryName")->fetchAll();
} catch (PDOException $e) {
    $error = "Query failed: " . $e->getMessage();
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>StockSmart - Inventory Query Results</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
  <div class="brand">
    <h1>StockSmart</h1>
    <h2>Inventory Update</h2>
    <h3>Never run out. Never run over.</h3>
  </div>
  <nav>
    <a href="index.html">Dashboard</a>
    <a href="products.html">Products</a>
    <a href="inventory.php" class="active">Inventory</a>
    <a href="sales.html">Sales</a>
    <a href="reports.html">Reports</a>
  </nav>
</header>

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
        <?php foreach ($categories as $cat): ?>
          <option value="<?= (int)$cat['CategoryID'] ?>" <?= ($category === (int)$cat['CategoryID']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($cat['CategoryName']) ?>
          </option>
        <?php endforeach; ?>
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
            <form action="inventory-results.php" method="POST" style="display:inline"
                  onsubmit="return confirm('Delete this inventory record?');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="inventoryId" value="<?= (int)$row['InventoryID'] ?>">
              <button type="submit">Delete</button>
            </form>
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