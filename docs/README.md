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

The seed does not add website accounts. On your first visit, `index.php` redirects to a one-time setup page that creates your first administrator with `password_hash()`.

## Run locally

Do not start the server with a bare `php -S localhost:8000`. Windows resolves
`localhost` to both `::1` (IPv6) and `127.0.0.1` (IPv4), and a browser tries `::1`
first. That command binds only one of the two addresses, leaving the other free for
some unrelated process — so `http://localhost:8000/` can serve a different directory
than `http://127.0.0.1:8000/`.

Use the bundled launcher instead. It binds this folder to both addresses and reclaims
the port from any stale PHP server first:

```powershell
powershell -ExecutionPolicy Bypass -File .\start-server.ps1
```

Open `http://localhost:8000/`. Both that URL and `http://127.0.0.1:8000/` serve the
same app. On the first visit, create the administrator account. Later visits open the
login page.

To stop the servers, or to use a different port:

```powershell
powershell -ExecutionPolicy Bypass -File .\start-server.ps1 -Stop
powershell -ExecutionPolicy Bypass -File .\start-server.ps1 -Port 8080
```

Server logs are written to `.server-logs/`.

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
