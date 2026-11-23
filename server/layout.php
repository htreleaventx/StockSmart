<?php
function pageStart(string $title, string $heading, string $section = ''): void
{
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>StockSmart - <?= e($title) ?></title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
  <div class="brand">
    <h1>StockSmart</h1>
    <h2><?= e($heading) ?></h2>
    <h3>Never run out. Never run over.</h3>
  </div>
  <nav>
    <?php if ($section !== ''): ?>
      <a href="index.php"<?= $section === 'dashboard' ? ' class="active"' : '' ?>>Dashboard</a>
      <a href="products.php"<?= $section === 'products' ? ' class="active"' : '' ?>>Products</a>
      <a href="inventory.php"<?= $section === 'inventory' ? ' class="active"' : '' ?>>Inventory</a>
      <a href="sales.php"<?= $section === 'sales' ? ' class="active"' : '' ?>>Sales</a>
      <a href="reports.php"<?= $section === 'reports' ? ' class="active"' : '' ?>>Reports</a>
    <?php endif; ?>
  </nav>
</header>
<?php
}

function pageMessage(string $error, string $message = ''): void
{
    if ($error !== '') {
        echo '<p style="color: #b00020; font-weight: bold;" role="alert">' . e($error) . '</p>';
    }
    if ($message !== '') {
        echo '<p class="note" role="status">' . e($message) . '</p>';
    }
}
