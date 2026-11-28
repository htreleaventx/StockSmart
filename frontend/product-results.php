<?php
require __DIR__ . '/../server/app.php';
require __DIR__ . '/../server/layout.php';
requireAccount(true);
require __DIR__ . '/db.php';

$error = '';
if (postRequest()) {
    requireCsrf();
    $stmt = $pdo->prepare('DELETE FROM PRODUCT WHERE ProductID = ?');
    try {
        $stmt->execute([integer(INPUT_POST, 'productId')]);
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            $error = 'Product not found.';
        } else {
            redirect('product-results.php?deleted=1');
        }
    } catch (PDOException $exception) {
        if ((int) ($exception->errorInfo[1] ?? 0) !== 1451) {
            throw $exception;
        }
        http_response_code(409);
        $error = 'This product appears in a recorded sale and must be kept for sales history.';
    }
}
$products = $pdo->query('
    SELECT p.*, c.CategoryName, p.SellPrice - p.PurchasePrice AS UnitProfit
    FROM PRODUCT p JOIN CATEGORY c ON c.CategoryID = p.CategoryID
    ORDER BY p.ProductName, p.ProductID
')->fetchAll();
$message = input(INPUT_GET, 'saved') === '1' ? 'Product saved.' : '';
if (input(INPUT_GET, 'deleted') === '1') {
    $message = 'Product deleted.';
}
?>
<?php pageStart('Product Query Results', 'Product Management', 'products'); ?>

<main>
  <aside class="sections">
    <a href="products.php">Add Product</a>
    <a href="product-results.php" class="active">Product Query Results</a>
  </aside>

    <section class="content">
    <h3>Product Query Results</h3>

    <?php pageMessage($error, $message); ?>
    <p class="note"><strong><?= count($products) ?></strong> <?= count($products) === 1 ? 'product' : 'products' ?> found</p>

    <div class="table-wrap">
      <table>
        <thead>
        <tr>
          <th>Product ID</th>
          <th>Product Name</th>
          <th>Category</th>
          <th>Purchase Price</th>
          <th>Selling Price</th>
          <th>Unit Profit</th>
          <th>Expiration Date</th>
          <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($products as $product): ?>
          <tr>
            <td><?= e($product['ProductID']) ?></td>
            <td><?= e($product['ProductName']) ?></td>
            <td><?= e($product['CategoryName']) ?></td>
            <td><?= e($product['PurchasePrice']) ?></td>
            <td><?= e($product['SellPrice']) ?></td>
            <td><?= e($product['UnitProfit']) ?></td>
            <td><?= e($product['ExpirationDate'] ?? '—') ?></td>
            <td style="white-space: nowrap;">
              <a href="products.php?edit=<?= (int) $product['ProductID'] ?>">Edit</a>
              <form method="post" style="display:inline" onsubmit="return confirm('Delete this product?');">
                <?= csrfField() ?>
                <input type="hidden" name="productId" value="<?= (int) $product['ProductID'] ?>">
                <button type="submit">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>

</body>
</html>
