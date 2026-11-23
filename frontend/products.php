<?php
require __DIR__ . '/../server/app.php';
require __DIR__ . '/../server/layout.php';
requireAccount(true);
require __DIR__ . '/db.php';

$categories = categories($pdo);
$edit = integer(INPUT_GET, 'edit');
$error = '';
$product = ['ProductName' => '', 'CategoryID' => '', 'PurchasePrice' => '', 'SellPrice' => '', 'ExpirationDate' => ''];

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM PRODUCT WHERE ProductID = ?');
    $stmt->execute([$edit]);
    $product = $stmt->fetch();
    if (!$product) {
        http_response_code(404);
        exit('Product not found.');
    }
}

if (postRequest()) {
    requireCsrf();
    $expiration = input(INPUT_POST, 'expirationDate');
    $product = [
        'ProductName' => trim(input(INPUT_POST, 'productName')),
        'CategoryID' => integer(INPUT_POST, 'category'),
        'PurchasePrice' => input(INPUT_POST, 'purchasePrice'),
        'SellPrice' => input(INPUT_POST, 'sellPrice'),
        'ExpirationDate' => $expiration === '' ? null : $expiration,
    ];
    $pricePattern = '/\A(?:\d{1,8}(?:\.\d{1,2})?|\.\d{1,2})\z/';
    if ($product['ProductName'] === '' || mb_strlen($product['ProductName']) > 100
        || !in_array($product['CategoryID'], array_column($categories, 'CategoryID'), true)
        || !preg_match($pricePattern, $product['PurchasePrice']) || !preg_match($pricePattern, $product['SellPrice'])
        || ($product['ExpirationDate'] !== null && !validDate($product['ExpirationDate']))) {
        http_response_code(422);
        $error = 'Enter a product name, a category, valid prices, and a valid date if supplied.';
    } else {
        $fields = 'ProductName = ?, CategoryID = ?, PurchasePrice = ?, SellPrice = ?, ExpirationDate = ?';
        $params = array_values($product);
        if ($edit !== null) {
            $sql = 'UPDATE PRODUCT SET ' . $fields . ' WHERE ProductID = ?';
            $params[] = $edit;
        } else {
            $sql = 'INSERT INTO PRODUCT SET ' . $fields;
        }
        try {
            $pdo->prepare($sql)->execute($params);
            redirect('product-results.php?saved=1');
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            http_response_code(500);
            $error = 'Could not save the product. Try again.';
        }
    }
}
?>
<?php pageStart('Product Management', 'Product Management', 'products'); ?>

<main>
  <aside class="sections">
    <a href="products.php" class="active">Add Product</a>
    <a href="product-results.php">Product Query Results</a>
  </aside>

  <section class="content">
    <h3><?= $edit ? 'Edit Product' : 'Add Product' ?></h3>
    <?php pageMessage($error); ?>

    <form method="post">
      <?= csrfField() ?>

      <label for="productName">Product Name:</label>
      <input type="text" id="productName"
             name="productName" value="<?= e($product['ProductName']) ?>" required>
      <br><br>

      <label for="category">Category:</label>
      <select id="category" name="category" required>
        <option value="">Select Category</option>
        <?php categoryOptions($categories, $product['CategoryID']); ?>
      </select>
      <br><br>

      <label for="purchasePrice">Purchase Price:</label>
      <input type="number" id="purchasePrice"
             name="purchasePrice" value="<?= e($product['PurchasePrice']) ?>"
             min="0" step="0.01" required>
      <br><br>

      <label for="sellPrice">Selling Price:</label>
      <input type="number" id="sellPrice"
             name="sellPrice" value="<?= e($product['SellPrice']) ?>"
             min="0" step="0.01" required>
      <br><br>

      <label for="expirationDate">Expiration Date:</label>
      <input type="date" id="expirationDate"
             name="expirationDate" value="<?= e($product['ExpirationDate']) ?>">
      <br><br>

      <button type="submit"><?= $edit ? 'Save Changes' : 'Add Product' ?></button>
      <?php if ($edit): ?>
        <a href="product-results.php">Go Back</a>
      <?php else: ?>
        <button type="reset">Clear Form</button>
      <?php endif; ?>
    </form>
  </section>
</main>

</body>
</html>
