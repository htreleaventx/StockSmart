# StockSmart

CS 4347 - Database Systems, Fall 2026, Group 14 Project:

Grocery Inventory and Profit Management System that allows a business to efficiently track and manage its grocery products. 

The system will allow users to add, update, and remove items while keeping track of information 
such as product name, quantity, category, expiration date, purchase cost, and selling price.


Group members (sorted alphabetically by last name) & roles for Phase 3:
1. Casey, Benjamin -- Part A
2. Matta, Rithwik -- Part C*
3. Shenoj, Shriya -- Part B + README
4. Treleaven, Hunter -- Part B

How to run:

1. Clone `https://github.com/htreleaventx/StockSmart.git` into local workspace (i.e. VSCode)

2. Type `python3 -m http.server 8000 --directory frontend` into terminal

3. Then visit `http://localhost:8000/`

(As proof for Phase 3 Part B - UI)

HTML files are in frontend/, and a style.css file is present as well.

*TaskC.pdf contains code for create.sql and load.sql

### Included HTML Files (`frontend/`)
- `current-sale.html`
- `expiring.html`
- `index.html`
- `inventory-results.html`
- `inventory.html`
- `low-stock.html`
- `product-results.html`
- `products.html`
- `report-summary.html`
- `reports.html`
- `sales.html`

### Styling & Assets (`frontend/`)
- `style.css`
- `pantry.jpg`
- `veggies.jpg`

<img width="3408" height="1838" alt="image" src="https://github.com/user-attachments/assets/44c7f1f9-181d-4ad6-93fe-ff4d246bf796" />

## Database Setup
On Mac:

Step 1: Check Homebrew

brew --version

Step 2: Install MySQL

brew install mysql
brew services start mysql

Step 3: Verify installation

mysql --version

Step 4: Log into MySQL

mysql -u root

Inside the MySQL prompt (mysql>), set your own password:

ALTER USER 'root'@'localhost' IDENTIFIED BY 'some_pwd';

Replace some_pwd with your own password.

Step 5: Exit MySQL

exit;

Step 6: Test login

mysql -u root -p

Enter the password you just created. If you successfully log in, exit again:

exit;

Step 7: Create the StockSmart database

From the StockSmart project root:

mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS stocksmart;"

Step 8: Import the database schema

Navigate into the database folder:

cd database

Import the SQL file:

mysql -u root -p stocksmart < create.sql

Step 9: Verify the tables

mysql -u root -p -e "USE stocksmart; SHOW TABLES;"

You should see:

CATEGORY
INVENTORY
PRODUCT
SALE
SALEITEM
