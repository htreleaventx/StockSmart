<?php
require __DIR__ . '/../server/app.php';
require __DIR__ . '/../server/layout.php';
requireAccount();
require __DIR__ . '/db.php';
require __DIR__ . '/../server/checkout.php';

$cart = $_SESSION['cart'] ?? [];
$error = '';
if (postRequest()) {
    requireCsrf();
    $action = input(INPUT_POST, 'action');
    if ($action === 'cancel') {
        unset($_SESSION['cart']);
        redirect('current-sale.php');
    }
    if ($action === 'complete') {
        try {
            $saleId = completeSale($pdo, $cart);
            unset($_SESSION['cart']);
            $_SESSION['completedSale'] = $saleId;
            redirect('current-sale.php');
        } catch (DomainException $exception) {
            http_response_code(409);
            $error = $exception->getMessage();
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            http_response_code(500);
            $error = 'The sale could not be recorded. Try again.';
        }
    }
}

$message = '';
if (isset($_SESSION['completedSale'])) {
    $message = 'Sale #' . $_SESSION['completedSale'] . ' recorded.';
    unset($_SESSION['completedSale']);
}
$lines = [];
$revenue = $cost = 0;
if ($cart) {
    $placeholders = implode(',', array_fill(0, count($cart), '?'));
    $stmt = $pdo->prepare("SELECT ProductID, ProductName, SellPrice, PurchasePrice FROM PRODUCT
                           WHERE ProductID IN ($placeholders) ORDER BY ProductName, ProductID");
    $stmt->execute(array_keys($cart));
    foreach ($stmt->fetchAll() as $product) {
        $quantity = $cart[$product['ProductID']];
        try {
            [$lineRevenue, $lineCost] = saleAmounts($product, $quantity);
            if (max($revenue + $lineRevenue, $cost + $lineCost) > SALE_MAX_CENTS) {
                throw new DomainException('The sale exceeds the supported dollar amount.');
            }
            $revenue += $lineRevenue;
            $cost += $lineCost;
            $lines[] = [$product, $quantity, $lineRevenue, $lineCost];
        } catch (DomainException $exception) {
            $error = $error ?: $exception->getMessage();
        }
    }
    if (count($lines) !== count($cart) && !$error) {
        $error = 'A product in the current sale is no longer available.';
    }
}
?>
<?php pageStart('Current Sale', 'Sales Entry / Checkout', 'sales'); ?>

<main>
  <aside class="sections">
    <a href="sales.php">Add Item to Sale</a>
    <a href="current-sale.php" class="active">Current Sale</a>
  </aside>

  <section class="content">
    <h3>Current Sale</h3>

    <?php pageMessage($error, $message); ?>
        <?php if (!$cart): ?><p class="note">No items in the current sale.</p><?php endif; ?>

    <div class="table-wrap">
      <table>
        <thead>
        <tr>
          <th>Product</th>
          <th>Quantity Sold</th>
          <th>Unit Price</th>
          <th>Unit Cost</th>
          <th>Line Revenue</th>
          <th>Line Cost</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$lines): ?>
          <tr><td colspan="6">Your cart is empty. Add items from the "Add Item to Sale" tab.</td></tr>
        <?php endif; ?>
        <?php foreach ($lines as [$product, $quantity, $lineRevenue, $lineCost]): ?>
          <tr>
            <td><?= e($product['ProductName']) ?></td>
            <td><?= e($quantity) ?></td>
            <td><?= e($product['SellPrice']) ?></td>
            <td><?= e($product['PurchasePrice']) ?></td>
            <td><?= e(dollars($lineRevenue)) ?></td>
            <td><?= e(dollars($lineCost)) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <table class="totals">
      <tr>
        <th>Total Revenue</th>
        <td><?= e(dollars($revenue)) ?></td>
      </tr>
      <tr>
        <th>Total Cost</th>
        <td><?= e(dollars($cost)) ?></td>
      </tr>
      <tr>
        <th>Total Profit</th>
        <td><?= e(dollars($revenue - $cost)) ?></td>
      </tr>
    </table>

    <?php if ($cart): ?>
    <form method="post">
      <?= csrfField() ?>
      <button type="submit" name="action" value="complete">Complete Sale</button>
      <button type="submit" name="action" value="cancel" style="background:#fff;color:var(--ink);border:1.5px solid var(--ink)">Cancel Sale</button>
    </form>
    <?php endif; ?>
  </section>
</main>

</body>
</html>
