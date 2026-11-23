<?php
require 'db.php';

$message = '';
$error   = '';

// Values used to pre-fill the form (blank, or an existing record when editing)
$form = ['productId' => '', 'quantity' => '', 'minStock' => ''];

// ---------- CREATE / UPDATE ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = filter_var($_POST['productId'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $quantity  = filter_var($_POST['quantity']  ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    $minStock  = filter_var($_POST['minStock']  ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

    if ($productId === false || $quantity === false || $minStock === false) {
        $error = "Please choose a product and enter whole numbers (0 or more) for both quantities.";
        $form = [
            'productId' => $_POST['productId'] ?? '',
            'quantity'  => $_POST['quantity']  ?? '',
            'minStock'  => $_POST['minStock']  ?? '',
        ];
    } else {
        try {
            $stmt = $pdo->prepare("SELECT InventoryID FROM INVENTORY WHERE ProductID = ?");
            $stmt->execute([$productId]);

            if ($stmt->fetch()) {
                $pdo->prepare("UPDATE INVENTORY SET Quantity = ?, MinStock = ? WHERE ProductID = ?")
                    ->execute([$quantity, $minStock, $productId]);
                $msg = 'updated';
            } else {
                $pdo->prepare("INSERT INTO INVENTORY (ProductID, Quantity, MinStock) VALUES (?, ?, ?)")
                    ->execute([$productId, $quantity, $minStock]);
                $msg = 'created';
            }
            // Post/Redirect/Get so a refresh doesn't resubmit the form
            header("Location: inventory-results.php?msg=$msg");
            exit;
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// ---------- Pre-fill when arriving from an "Edit" link ----------
$editing = false;
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT);
    if ($editId) {
        $stmt = $pdo->prepare("SELECT ProductID, Quantity, MinStock FROM INVENTORY WHERE InventoryID = ?");
        $stmt->execute([$editId]);
        if ($row = $stmt->fetch()) {
            $form = ['productId' => $row['ProductID'], 'quantity' => $row['Quantity'], 'minStock' => $row['MinStock']];
            $editing = true;
        } else {
            $error = "That inventory record no longer exists.";
        }
    }
}

// Products for the dropdown
$products = $pdo->query("SELECT ProductID, ProductName FROM PRODUCT ORDER BY ProductName")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>StockSmart - Inventory Update</title>
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
    <a href="inventory.php" class="active">Update Inventory</a>
    <a href="inventory-results.php">Inventory Query Results</a>
  </aside>

  <section class="content">
    <h3><?= $editing ? 'Edit Inventory Record' : 'Update Inventory' ?></h3>

    <?php if ($error): ?>
      <p style="color: #b00020; font-weight: bold;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form action="inventory.php" method="POST">

      <label for="productId">Product:</label>
      <select id="productId" name="productId" required>
        <option value="">Select Product</option>
        <?php foreach ($products as $prod): ?>
          <option value="<?= (int)$prod['ProductID'] ?>"
            <?= ((string)$form['productId'] === (string)$prod['ProductID']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($prod['ProductName']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <br><br>

      <label for="quantity">Quantity in Stock:</label>
      <input type="number" id="quantity" name="quantity" min="0" step="1" required
             value="<?= htmlspecialchars((string)$form['quantity']) ?>">
      <br><br>

      <label for="minStock">Minimum Stock Level:</label>
      <input type="number" id="minStock" name="minStock" min="0" step="1" required
             value="<?= htmlspecialchars((string)$form['minStock']) ?>">
      <br><br>

      <button type="submit"><?= $editing ? 'Save Changes' : 'Update Inventory' ?></button>
      <?php if ($editing): ?>
        <a href="inventory.php"><button type="button">Cancel</button></a>
      <?php else: ?>
        <button type="reset">Clear Form</button>
      <?php endif; ?>
    </form>
  </section>
</main>

</body>
</html>