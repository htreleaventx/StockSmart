<?php

const SALE_MAX_CENTS = 9999999999;

function cents(string $amount): int
{
    return (int) str_replace('.', '', $amount);
}

function dollars(int $amount): string
{
    $absolute = abs($amount);
    return ($amount < 0 ? '-' : '') . intdiv($absolute, 100) . '.' . str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);
}

function saleAmounts(array $product, int $quantity): array
{
    $price = cents($product['SellPrice']);
    $cost = cents($product['PurchasePrice']);
    $largest = max($price, $cost);
    if ($largest > 0 && $quantity > intdiv(SALE_MAX_CENTS, $largest)) {
        throw new DomainException('The sale exceeds the supported dollar amount.');
    }
    return [$price * $quantity, $cost * $quantity];
}

function completeSale(PDO $pdo, array $cart): int
{
    if (!$cart) {
        throw new DomainException('Add an item before completing the sale.');
    }
    ksort($cart, SORT_NUMERIC);
    $productQuery = $pdo->prepare('
        SELECT ProductName, SellPrice, PurchasePrice,
               IF(ExpirationDate < CURRENT_DATE, 1, 0) AS Expired
        FROM PRODUCT WHERE ProductID = ? FOR UPDATE
    ');
    $stockQuery = $pdo->prepare('SELECT Quantity FROM INVENTORY WHERE ProductID = ? FOR UPDATE');
    $stockUpdate = $pdo->prepare('UPDATE INVENTORY SET Quantity = Quantity - ? WHERE ProductID = ?');
    $saleInsert = $pdo->prepare('INSERT INTO SALE (TotalRevenue, TotalCost) VALUES (?, ?)');
    $itemInsert = $pdo->prepare('
        INSERT INTO SALEITEM (SaleID, ProductID, QuantitySold, UnitPrice, UnitCost)
        VALUES (?, ?, ?, ?, ?)
    ');

    $pdo->beginTransaction();
    try {
        $products = [];
        $revenue = $cost = 0;
        foreach ($cart as $productId => $quantity) {
            $productQuery->execute([$productId]);
            $product = $productQuery->fetch();
            $stockQuery->execute([$productId]);
            $stock = $stockQuery->fetchColumn();
            if (!$product || $product['Expired'] || $stock === false || $quantity > $stock) {
                $name = $product['ProductName'] ?? 'An item';
                throw new DomainException($name . ' has expired or no longer has enough stock.');
            }

            [$lineRevenue, $lineCost] = saleAmounts($product, $quantity);
            $revenue += $lineRevenue;
            $cost += $lineCost;
            if (max($revenue, $cost) > SALE_MAX_CENTS) {
                throw new DomainException('The sale exceeds the supported dollar amount.');
            }
            $products[$productId] = $product;
        }

        $saleInsert->execute([dollars($revenue), dollars($cost)]);
        $saleId = (int) $pdo->lastInsertId();
        foreach ($products as $productId => $product) {
            $quantity = $cart[$productId];
            $itemInsert->execute([$saleId, $productId, $quantity, $product['SellPrice'], $product['PurchasePrice']]);
            $stockUpdate->execute([$quantity, $productId]);
        }
        $pdo->commit();
        return $saleId;
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
}
