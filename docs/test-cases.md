# Test Cases

| ID | Test | Steps | Expected result |
| --- | --- | --- | --- |
| TC-01 | Database import | Import schema, views, procedures, then seed files. | Tables, both views, trigger, procedure, and sample data are created without errors. |
| TC-02 | Product constraint | Run `INSERT INTO Product (category_id, supplier_id, product_name, unit_price, reorder_level) VALUES (1, 1, 'Invalid Price', -1, 10);`. | MySQL rejects the row because `unit_price` cannot be negative. |
| TC-03 | Inventory constraint | Run `UPDATE Inventory SET quantity = -1 WHERE inventory_id = 1;`. | MySQL rejects the update through the CHECK constraint and trigger. |
| TC-04 | Duplicate inventory protection | Insert a second Inventory row with an existing warehouse and product pair. | MySQL rejects the insert because of the unique warehouse-product constraint. |
| TC-05 | Login validation | Submit an empty login form or incorrect credentials. | The form displays a validation or invalid-credentials message and does not create a session. |
| TC-06 | CSRF protection | Submit a POST form after removing or changing its CSRF token. | The application returns `Invalid form request.` and makes no data change. |
| TC-07 | Staff deletion restriction | Sign in as a staff user and submit a delete request for a category. | The application returns an admin-access error and the category remains unchanged. |
| TC-08 | Master-data create | Add a new supplier, category, and product through their forms. | Each record appears in its table after submission. |
| TC-09 | Foreign-key deletion restriction | Attempt to delete a supplier or category used by a product. | The application reports that the related record cannot be deleted. |
| TC-10 | IN movement | Record an IN movement of 5 units for a product and warehouse. | Inventory increases by 5 and one IN history row is recorded. |
| TC-11 | OUT movement | Record an OUT movement less than or equal to the available quantity. | Inventory decreases by that quantity and one OUT history row is recorded. |
| TC-12 | Insufficient OUT movement | Record an OUT movement larger than the available quantity. | The application rejects it, rolls back, and records no movement row. |
| TC-13 | Transfer movement | Transfer 2 units between different warehouses. | Source quantity decreases by 2, destination quantity increases by 2, and one TRANSFER row is recorded. |
| TC-14 | Inventory filtering | Search an existing product name and choose a warehouse filter. | Only matching inventory rows are displayed. |
| TC-15 | Report CSV export | Open Reports and select each Export CSV button. | A CSV file downloads with the same columns and rows as its report. |
| TC-16 | Date-filtered history | Select a From Date and To Date in Movements or Reports. | Only movements within the inclusive date range appear and export. |

## Seed verification

```sql
SELECT COUNT(*) AS products FROM Product;
SELECT COUNT(*) AS inventory_rows FROM Inventory;
SELECT COUNT(*) AS movement_rows FROM Stock_Movement;
SELECT COUNT(*) AS low_stock_rows FROM v_low_stock;
```

Expected seeded results are 22 products, 66 inventory rows, 12 movement rows, and 22 low-stock rows.
