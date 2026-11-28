<?php
require __DIR__ . '/../server/app.php';
require __DIR__ . '/../server/layout.php';
requireAccount();
require __DIR__ . '/db.php';

$error = '';
$products = [];
$form = ['productId' => '', 'quantity' => '', 'minStock' => ''];
$editing = isset($_GET['edit']);
$editId = integer(INPUT_GET, 'edit');
$record = false;
$submitted = postRequest();
if ($submitted) {
    requireCsrf();
}

try {
    if ($editing && $editId !== null) {
        if ($submitted) {
            $pdo->beginTransaction();
        }
        $sql = "SELECT ProductID, Quantity, MinStock FROM INVENTORY WHERE InventoryID = ?";
        $stmt = $pdo->prepare($submitted ? $sql . " FOR UPDATE" : $sql);
        $stmt->execute([$editId]);
        $record = $stmt->fetch();
    }

    if ($editing && !$record) {
        http_response_code(404);
        $error = "That inventory record no longer exists.";
    } elseif ($submitted) {
        $productId = $editing ? $record['ProductID'] : integer(INPUT_POST, 'productId');
        $quantity = integer(INPUT_POST, 'quantity', 0, 10000);
        $minStock = integer(INPUT_POST, 'minStock', 0, 10000);
        $form = [
            'productId' => $productId ?? '',
            'quantity' => (string)filter_input(INPUT_POST, 'quantity'),
            'minStock' => (string)filter_input(INPUT_POST, 'minStock'),
        ];

        if (!$productId || $quantity === null || $minStock === null) {
            http_response_code(422);
            $error = "Choose a product and enter whole numbers between 0 and 10,000 for Quantity and Minimum Stock.";
        } elseif ($editing) {
            $pdo->prepare("UPDATE INVENTORY SET Quantity = ?, MinStock = ?, LastUpdated = CURRENT_TIMESTAMP WHERE InventoryID = ?")
                ->execute([$quantity, $minStock, $editId]);
            $msg = 'updated';
        } else {
            $pdo->beginTransaction();
            // Serialize inventory creation for the same product across form submissions.
            $stmt = $pdo->prepare("SELECT ProductID FROM PRODUCT WHERE ProductID = ? FOR UPDATE");
            $stmt->execute([$productId]);
            if (!$stmt->fetch()) {
                $error = "That product no longer exists.";
            } else {
                $stmt = $pdo->prepare("SELECT InventoryID FROM INVENTORY WHERE ProductID = ? FOR UPDATE");
                $stmt->execute([$productId]);

                if ($stmt->fetch()) {
                    $pdo->prepare("UPDATE INVENTORY SET Quantity = ?, MinStock = ?, LastUpdated = CURRENT_TIMESTAMP WHERE ProductID = ?")
                        ->execute([$quantity, $minStock, $productId]);
                    $msg = 'updated';
                } else {
                    $pdo->prepare("INSERT INTO INVENTORY (ProductID, Quantity, MinStock, LastUpdated) VALUES (?, ?, ?, CURRENT_TIMESTAMP)")
                        ->execute([$productId, $quantity, $minStock]);
                    $msg = 'created';
                }
            }
        }

        if (isset($msg)) {
            $pdo->commit();
            redirect("inventory-results.php?msg=$msg");
        }
    } elseif ($editing) {
        $form = ['productId' => $record['ProductID'], 'quantity' => $record['Quantity'], 'minStock' => $record['MinStock']];
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    $error = $submitted ? "Could not save inventory." : "Could not load the inventory record.";
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

if (!$editing || $record) {
    try {
        $products = $pdo->query("SELECT ProductID, ProductName FROM PRODUCT ORDER BY ProductName")->fetchAll();
    } catch (PDOException $e) {
        error_log($e->getMessage());
        http_response_code(500);
        $error = "Could not load products.";
    }
}
?>
<?php pageStart('Inventory Update', 'Inventory Update', 'inventory'); ?>

<main>
  <aside class="sections">
    <a href="inventory.php" class="active">Update Inventory</a>
    <a href="inventory-results.php">Inventory Query Results</a>
  </aside>

  <section class="content">
    <h3><?= $editing ? 'Edit Inventory Record' : 'Update Inventory' ?></h3>

    <?php if ($error): ?>
      <p style="color: #b00020; font-weight: bold;"><?= htmlspecialchars($error) ?></p>
    <?php elseif (!$products): ?>
      <p>Add a product before updating inventory.</p>
    <?php endif; ?>

    <?php if ($products && (!$editing || $record)): ?>
    <form action="inventory.php<?= $editing ? '?edit=' . $editId : '' ?>" method="POST">
      <?= csrfField() ?>

      <label for="productId">Product:</label>
      <select id="productId" name="productId" <?= $editing ? 'disabled' : 'required' ?>>
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
        <a href="inventory-results.php" class="btn-secondary">Cancel</a>
      <?php else: ?>
        <button type="reset">Clear Form</button>
      <?php endif; ?>
    </form>
    <?php endif; ?>
  </section>
</main>

</body>
</html>
