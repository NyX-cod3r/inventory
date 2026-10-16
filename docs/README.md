# Inventory and Warehouse Management System

This PHP and MySQL application tracks products, suppliers, warehouses, inventory quantities, and stock movements.

## Requirements

- PHP 8 with `pdo_mysql`
- MySQL Server 8.0.16 or newer on port 3306
- MySQL Workbench or MySQL command-line client

## Database setup

Create the database once:

```sql
CREATE DATABASE IF NOT EXISTS inventory_db CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
```

If MySQL rejects the trigger because binary logging is enabled, run this once as a MySQL administrator and then import `schema.sql` again:

```powershell
mysql -u root -p -e "SET GLOBAL log_bin_trust_function_creators = 1;"
```

In Windows Command Prompt, import the files in this order:

```cmd
mysql -u inv_user -p inventory_db < database\schema.sql
mysql -u inv_user -p inventory_db < database\views.sql
mysql -u inv_user -p inventory_db < database\procedures.sql
mysql -u inv_user -p inventory_db < database\seed.sql
```

In PowerShell, use these equivalent commands:

```powershell
Get-Content -Raw database/schema.sql | mysql -u inv_user -p inventory_db
Get-Content -Raw database/views.sql | mysql -u inv_user -p inventory_db
Get-Content -Raw database/procedures.sql | mysql -u inv_user -p inventory_db
Get-Content -Raw database/seed.sql | mysql -u inv_user -p inventory_db
```

The scripts can be re-run. Re-running `schema.sql` replaces the tables, and re-running `seed.sql` restores the sample master, inventory, and movement data.

## Application configuration

Copy `config/config.sample.php` to `config/config.php` if it does not already exist. Set the MySQL host, database, username, and password in `config/config.php`.

`config/config.php` is excluded from Git because it contains credentials.

The seed does not add website accounts. Create your initial administrator before attempting to log in.

Generate a password hash:

```powershell
php -r "echo password_hash('choose-a-strong-password', PASSWORD_DEFAULT), PHP_EOL;"
```

Insert the printed hash into MySQL:

```sql
INSERT INTO Users (username, password_hash, role)
VALUES ('your-admin-username', 'paste-the-generated-hash-here', 'admin');
```

## Run locally

From the project folder, start the PHP server:

```powershell
php -S localhost:8000
```

Open `http://localhost:8000/login.php` and sign in with the administrator you created.

## Main features

- Login, logout, sessions, password hashing, CSRF protection, and role checks
- Master-data CRUD for categories, suppliers, products, and warehouses
- Product and warehouse inventory filtering
- Atomic IN, OUT, and TRANSFER stock movements
- Low-stock, warehouse-value, and movement-history reports with CSV exports
- MySQL views, indexes, constraints, trigger, and stored procedure

## Project layout

```text
assets/       CSS and JavaScript
config/       Database configuration and PDO connection
database/     Schema, views, procedure, and seed scripts
docs/         Setup, tests, and viva queries
includes/     Authentication, CSRF, layout, and flash helpers
```
