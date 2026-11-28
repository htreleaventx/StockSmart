<?php
require __DIR__ . '/../server/app.php';
require __DIR__ . '/../server/layout.php';
requireAccount();
require __DIR__ . '/db.php';
require __DIR__ . '/../server/checkout.php';

$error = '';
$products = $pdo->query('
    SELECT p.ProductID, p.ProductName, p.SellPrice, p.PurchasePrice, i.Quantity
    FROM PRODUCT p JOIN INVENTORY i ON i.ProductID = p.ProductID
    WHERE i.Quantity > 0 AND (p.ExpirationDate IS NULL OR p.ExpirationDate >= CURRENT_DATE)
    ORDER BY p.ProductName, p.ProductID
')->fetchAll();
$available = array_column($products, null, 'ProductID');
if (postRequest()) {
    requireCsrf();
    $productId = integer(INPUT_POST, 'productId');
    $quantity = integer(INPUT_POST, 'quantitySold');
    $total = ($quantity ?? 0) + ($_SESSION['cart'][$productId] ?? 0);
        if (!isset($available[$productId])) {
        http_response_code(422);
        $error = 'Select an available product from the dropdown list.';
    } elseif ($quantity === null) {
        $maxAllowed = max(1, (int)$available[$productId]['Quantity'] - ($_SESSION['cart'][$productId] ?? 0));
        http_response_code(422);
        $error = "Enter a whole number quantity between 1 and $maxAllowed.";
    } elseif ($total > $available[$productId]['Quantity']) {
        $remaining = (int)$available[$productId]['Quantity'] - ($_SESSION['cart'][$productId] ?? 0);
        http_response_code(422);
        $error = $remaining > 0
            ? "Enter a quantity between 1 and $remaining (only $remaining left in stock)."
            : 'All available stock for this product is already in your current sale.';
    } else {
        try {
            saleAmounts($available[$productId], $total);
            $_SESSION['cart'][$productId] = $total;
            redirect('current-sale.php');
        } catch (DomainException $exception) {
            http_response_code(422);
            $error = $exception->getMessage();
        }
    }
}
?>
<?php pageStart('Sales Entry', 'Sales Entry / Checkout', 'sales'); ?>

<main>
  <aside class="sections">
    <a href="sales.php" class="active">Add Item to Sale</a>
    <a href="current-sale.php">Current Sale</a>
  </aside>

  <section class="content">
    <h3>Add Item to Sale</h3>

    <?php pageMessage($error); ?>
    <form method="post">
      <?= csrfField() ?>

      <label for="productId">Product:</label>
      <select id="productId" name="productId" required>
        <option value="">Select Product</option>
          <?php foreach ($products as $product): ?>
          <?php $inStock = max(0, (int)$product['Quantity'] - ($_SESSION['cart'][$product['ProductID']] ?? 0)); ?>
          <option value="<?= (int) $product['ProductID'] ?>"
                  data-price="<?= e($product['SellPrice']) ?>"
                  data-cost="<?= e($product['PurchasePrice']) ?>"
                  data-stock="<?= $inStock ?>"
                  <?= ((string)($productId ?? '') === (string)$product['ProductID']) ? 'selected' : '' ?>>
            <?= e($product['ProductName']) ?> (<?= $inStock ?> in stock)
          </option>
        <?php endforeach; ?>
      </select>
      <br><br>

      <label for="availStock">Available Stock:</label>
      <input type="text" id="availStock" readonly placeholder="Select a product">
      <br><br>

      <label for="quantitySold">Quantity Sold:</label>
      <input type="number" id="quantitySold"
             name="quantitySold"
             min="1" step="1" required>
      <br><br>

      <label for="unitPrice">Unit Price:</label>
      <input type="number" id="unitPrice"
             name="unitPrice"
             min="0" step="0.01" readonly>
      <br><br>

      <label for="unitCost">Unit Cost:</label>
      <input type="number" id="unitCost"
             name="unitCost"
             min="0" step="0.01" readonly>
      <br><br>

      <p class="note">
        Unit price and unit cost are copied from the Product table
        when the sale is recorded.
      </p>

      <button type="submit">Add Item</button>
      <button type="reset">Clear Form</button>
    </form>
  </section>
</main>

<script>
    const product = document.getElementById('productId');
  const qty = document.getElementById('quantitySold');
  const price = document.getElementById('unitPrice');
  const cost = document.getElementById('unitCost');

  function showPrices() {
    const option = product.selectedOptions[0];
    price.value = option?.dataset.price ?? '';
    cost.value = option?.dataset.cost ?? '';
    if (option?.dataset.stock) {
      qty.max = option.dataset.stock;
    } else {
      qty.removeAttribute('max');
    }
    document.getElementById('availStock').value = option?.dataset.stock !== undefined ? option.dataset.stock : '';
  }
  
  product.addEventListener('change', showPrices);
  product.form.addEventListener('reset', () => setTimeout(showPrices, 0));
  showPrices();
</script>
</body>
</html>
