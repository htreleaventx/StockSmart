-- The CSV snapshot is dated November 23, 2026. Shift dates together for each demo.
-- Set @stocksmart_demo_date in this session before loading to choose another date.
SET @demo_shift = DATEDIFF(COALESCE(@stocksmart_demo_date, CURRENT_DATE), '2026-11-23');

START TRANSACTION;

LOAD DATA LOCAL INFILE 'category.csv'
INTO TABLE CATEGORY
CHARACTER SET utf8mb4
FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 LINES
(CategoryID, CategoryName, @Description)
SET Description = NULLIF(TRIM(TRAILING '\r' FROM @Description), '');

LOAD DATA LOCAL INFILE 'product.csv'
INTO TABLE PRODUCT
CHARACTER SET utf8mb4
FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 LINES
(ProductID, ProductName, PurchasePrice, SellPrice,
@ExpirationDate, CategoryID)
SET ExpirationDate = IF(LENGTH(@ExpirationDate) = 0, NULL,
    TIMESTAMPADD(DAY, @demo_shift, CAST(@ExpirationDate AS DATE)));

LOAD DATA LOCAL INFILE 'inventory.csv'
INTO TABLE INVENTORY
CHARACTER SET utf8mb4
FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 LINES
(InventoryID, ProductID, Quantity, MinStock, @LastUpdated)
SET LastUpdated = TIMESTAMPADD(DAY, @demo_shift, CAST(@LastUpdated AS DATETIME));

LOAD DATA LOCAL INFILE 'sale.csv'
INTO TABLE SALE
CHARACTER SET utf8mb4
FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 LINES
(SaleID, @SaleDate, TotalCost, TotalRevenue)
SET SaleDate = TIMESTAMPADD(DAY, @demo_shift, CAST(@SaleDate AS DATETIME));

LOAD DATA LOCAL INFILE 'saleitem.csv'
INTO TABLE SALEITEM
CHARACTER SET utf8mb4
FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 LINES
(SaleItemID, SaleID, ProductID, QuantitySold, UnitPrice, UnitCost);

COMMIT;
