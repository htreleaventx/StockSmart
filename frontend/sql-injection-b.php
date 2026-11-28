<?php

if (getenv('STOCKSMART_SQL_DEMO') !== '1') {
    http_response_code(403);
    exit('SQL injection demo is disabled.');
}

require __DIR__ . '/db.php';

$productName = $_GET['ProductName'] ?? '';
$categoryName = $_GET['CategoryName'] ?? '';
$sellPrice = $_GET['SellPrice'] ?? '';

$sql = "SELECT p.ProductID, p.ProductName, c.CategoryName, p.SellPrice
        FROM PRODUCT p
        JOIN CATEGORY c
        ON p.CategoryID = c.CategoryID
        WHERE p.ProductName = :productName
        AND c.CategoryName = :categoryName
        AND p.SellPrice = :sellPrice";

// Display the prepared SQL query
echo "<h3>Prepared SQL Query</h3>";
echo "<pre>" . htmlspecialchars($sql) . "</pre>";

$result = $pdo->prepare($sql);

$result->bindValue(':productName', $productName);
$result->bindValue(':categoryName', $categoryName);
$result->bindValue(':sellPrice', $sellPrice);

$result->execute();

echo "<h2>Product Search Results</h2>";

$found = false;

while ($row = $result->fetch()) {

    $found = true;

    echo "Product ID: " .
        htmlspecialchars($row['ProductID']) . "<br>";

    echo "Product Name: " .
        htmlspecialchars($row['ProductName']) . "<br>";

    echo "Category: " .
        htmlspecialchars($row['CategoryName']) . "<br>";

    echo "Selling Price: " .
        htmlspecialchars($row['SellPrice']) . "<br>";

    echo "<hr>";
}

if (!$found) {
    echo "<p>No matching products found.</p>";
}

echo '<a href="sql-injection.html">Return To Search</a>';

?>