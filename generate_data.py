"""Generate all five StockSmart demo CSVs without reading any existing data."""

import argparse
import csv
from datetime import date, datetime, time, timedelta
from pathlib import Path

from faker import Faker


SNAPSHOT = date(2026, 11, 23)  # The reference date used by database/load.sql.
CATEGORIES = [
    ("Dairy", "Milk and dairy products", ("Whole Milk 1 qt", "Greek Yogurt 16 oz", "Cheddar Cheese 8 oz", "Salted Butter 1 lb", "Heavy Cream 1 pt"), (-3, 45)),
    ("Bakery", "Bread and baked goods", ("Sourdough Bread", "Croissants 4 ct", "Whole Wheat Bread", "Dinner Rolls 8 ct", "Blueberry Muffins 4 ct"), (-4, 12)),
    ("Beverages", "Drinks and refreshments", ("Orange Juice 52 oz", "Sparkling Water 8 pk", "Apple Juice 64 oz", "Cold Brew 16 oz", "Lemonade 32 oz"), (0, 365)),
    ("Produce", "Fresh fruits and vegetables", ("Gala Apples 3 lb", "Bananas 1 bunch", "Baby Spinach 5 oz", "Carrots 2 lb", "Roma Tomatoes 1 lb"), (-4, 14)),
    ("Household", "Household supplies", ("Dish Soap 16 oz", "Paper Towels 2 pk", "Trash Bags 30 ct", "Laundry Detergent 32 oz", "Kitchen Sponges 4 pk"), None),
    ("Meat & Seafood", "Chilled meat, poultry and seafood", ("Ground Beef 1 lb", "Chicken Breast 1 lb", "Salmon Fillet 8 oz", "Pork Chops 1 lb", "Turkey Cutlets 1 lb"), (-3, 10)),
    ("Frozen", "Frozen meals, vegetables and desserts", ("Frozen Peas 12 oz", "Cheese Pizza 14 oz", "Vanilla Ice Cream 1 qt", "Frozen Berries 16 oz", "Potato Fries 24 oz"), (20, 365)),
    ("Pantry", "Shelf-stable cooking ingredients and canned goods", ("Pinto Beans 15 oz", "Penne Pasta 16 oz", "Jasmine Rice 2 lb", "Tomato Sauce 24 oz", "Rolled Oats 18 oz"), (30, 500)),
    ("Snacks", "Crackers, chips, nuts and snack bars", ("Potato Chips 8 oz", "Wheat Crackers 9 oz", "Roasted Almonds 8 oz", "Oat Bars 6 ct", "Pretzels 10 oz"), (12, 180)),
    ("Deli & Prepared Foods", "Prepared meals and refrigerated deli items", ("Roast Beef Slices 7 oz", "Potato Salad 12 oz", "Hummus 8 oz", "Turkey Sandwich", "Pasta Salad 12 oz"), (-3, 12)),
]


def write_csv(directory, name, header, rows):
    with (directory / f"{name}.csv").open("w", newline="", encoding="utf-8") as file:
        writer = csv.writer(file, lineterminator="\n")
        writer.writerow(header.split(","))
        writer.writerows(rows)


def money(cents):
    return f"{cents // 100}.{cents % 100:02d}"


def generate(directory, seed):
    fake = Faker("en_US")
    fake.seed_instance(seed)
    brands = [f"{fake.unique.last_name()} Market" for _ in range(20)]
    categories, products, inventory, sales, items = [], [], [], [], []
    excluded = set(fake.random.sample(range(101, 1001), 12))

    for category_id, (name, description, varieties, shelf_life) in enumerate(CATEGORIES, 1):
        categories.append((category_id, name, description))
        for variety in varieties:
            for brand in brands:
                product_id = len(products) + 1
                cost = fake.random_int(25, 850)
                price = cost + fake.random_int(30, 420)
                expires = SNAPSHOT + timedelta(days=fake.random_int(*shelf_life)) if shelf_life else None
                products.append((product_id, f"{brand} {variety}", cost, price, expires, category_id))

                if product_id not in excluded:
                    minimum = fake.random_int(3, 15)
                    quantity = (fake.random_int(0, minimum) if product_id % 5 == 0
                                else fake.random_int(minimum + 1, 100))
                    if product_id % 25 == 0:
                        quantity = 0
                    updated = datetime.combine(
                        SNAPSHOT - timedelta(days=fake.random_int(0, 20)),
                        time(fake.random_int(8, 20), fake.random_int(0, 59)),
                    )
                    inventory.append((len(inventory) + 1, product_id, quantity, minimum, updated))

    line_counts = [3] * 105 + [4] * 236 + [5] * 264
    fake.random.shuffle(line_counts)
    for sale_id, line_count in enumerate(line_counts, 1):
        sold_at = datetime.combine(
            SNAPSHOT - timedelta(days=fake.random_int(1, 56)),
            time(fake.random_int(8, 20), fake.random_int(0, 59)),
        )
        available = [p for p in products if p[4] is None or p[4] >= sold_at.date()]
        total_cost = total_revenue = 0
        for product_id, _, cost, price, _, _ in fake.random.sample(available, line_count):
            quantity = fake.random_int(1, 5)
            total_cost += cost * quantity
            total_revenue += price * quantity
            items.append((len(items) + 1, sale_id, product_id, quantity, money(price), money(cost)))
        sales.append((sale_id, sold_at, money(total_cost), money(total_revenue)))

    directory.mkdir(parents=True, exist_ok=True)
    write_csv(directory, "category", "CategoryID,CategoryName,Description", categories)
    write_csv(directory, "product", "ProductID,ProductName,PurchasePrice,SellPrice,ExpirationDate,CategoryID",
              ((pid, name, money(cost), money(price), expires, cid)
               for pid, name, cost, price, expires, cid in products))
    write_csv(directory, "inventory", "InventoryID,ProductID,Quantity,MinStock,LastUpdated", inventory)
    write_csv(directory, "sale", "SaleID,SaleDate,TotalCost,TotalRevenue", sales)
    write_csv(directory, "saleitem", "SaleItemID,SaleID,ProductID,QuantitySold,UnitPrice,UnitCost", items)


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("directory", type=Path, help="Output directory for the five CSVs")
    parser.add_argument("--seed", type=int, default=4347)
    args = parser.parse_args()
    generate(args.directory, args.seed)
