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

### Run locally

1. Clone `https://github.com/htreleaventx/StockSmart.git`.
2. From the repository root, create a fresh MySQL demo database and import the files in `database/`. Use your local MySQL username in place of `root` if needed. MySQL must have `local_infile` enabled on the server for the import.

   ```sh
   mysql -u root -p -e 'CREATE DATABASE stocksmart CHARACTER SET utf8mb4'
   mysql -u root -p stocksmart < database/create.sql
   (cd database && mysql --local-infile=1 --show-warnings -u root -p stocksmart < load.sql)
   ```

   The CSVs contain 1,000 products, with low-stock, out-of-stock, and soon-to-expire examples. `load.sql` shifts expiration dates, sale dates, and inventory timestamps by the same number of days so the demo is current on the day you import it. Run the loader once against a fresh schema.

3. Use PHP 8.1+ with `pdo_mysql` and `mbstring`. Set the database credentials in the shell that launches PHP. `STOCKSMART_DB_HOST`, `STOCKSMART_DB_PORT`, `STOCKSMART_DB_NAME`, and `STOCKSMART_DB_USER` default to `127.0.0.1`, `3306`, `stocksmart`, and `root`. Set `STOCKSMART_DB_PASSWORD` if your MySQL account uses a password.
4. **Create your StockSmart login.** A fresh checkout starts with no app accounts. From the project root, run the block below in Bash or Zsh. At the prompt, enter a password of at least eight characters and press Enter. Your typing is hidden. The last argument to `create-manager.php` sets your app username, which is `manager` in this example.

   ```bash
   printf 'Manager password: '
   IFS= read -rs STOCKSMART_MANAGER_PASSWORD
   printf '\n'
   printf '%s\n' "$STOCKSMART_MANAGER_PASSWORD" | php server/create-manager.php manager
   unset STOCKSMART_MANAGER_PASSWORD
   ```

5. Run `php -S localhost:8000 -t frontend` and open `http://localhost:8000/`. Sign in with username `manager` and the password you just entered. Use **User Management** on the dashboard to create sign-ins for employees and other managers.

Your MySQL credentials from steps 2 and 3 are used by PHP to connect to the database. The StockSmart sign-in uses the account from step 4.

**Forgot your manager password?** Repeat step 4 with your existing manager username and enter a new password. Account password hashes are stored in `var/accounts.json` outside the web root. If you use `STOCKSMART_ACCOUNTS_FILE` to choose another location outside `frontend/`, set the same absolute path for the PHP server and the manager setup command.

Managers can manage products, delete inventory records, access reports, and manage users. Employees can search for products, use inventory and alerts, and record sales. The existing schema supports one stock count per product. Set the quantity to zero when removing the remaining expired stock. Checkout retains each item's price and cost at the time of sale, and reports calculate profit from recorded revenue and cost.

### Generate demo data

`generate_data.py` uses Faker to write all five CSVs in the format consumed by `database/load.sql`. It creates 10 categories, 1,000 products, 988 stock records, 605 sales, and 2,579 sale items. Generate into a separate directory to keep the supplied CSVs available.

```sh
python3 -m venv /tmp/stocksmart-faker
/tmp/stocksmart-faker/bin/python -m pip install Faker==40.63.0
/tmp/stocksmart-faker/bin/python generate_data.py /tmp/stocksmart-demo
```

Run the loader from the directory containing the CSVs, with the path to `database/load.sql` supplied as input. The optional `--seed` flag creates a different dataset that uses the same snapshot date.

### Styling & Assets (`frontend/`)
- `style.css`
- `pantry.jpg`
- `veggies.jpg`

<img width="3408" height="1838" alt="image" src="https://github.com/user-attachments/assets/44c7f1f9-181d-4ad6-93fe-ff4d246bf796" />
