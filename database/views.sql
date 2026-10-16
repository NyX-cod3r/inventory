DROP VIEW IF EXISTS v_low_stock;
DROP VIEW IF EXISTS v_inventory_summary;

CREATE VIEW v_low_stock AS
SELECT
    i.inventory_id,
    p.product_id,
    p.product_name,
    c.category_name,
    w.warehouse_id,
    w.warehouse_name,
    i.quantity,
    p.reorder_level
FROM Inventory AS i
JOIN Product AS p ON p.product_id = i.product_id
JOIN Category AS c ON c.category_id = p.category_id
JOIN Warehouse AS w ON w.warehouse_id = i.warehouse_id
WHERE i.quantity <= p.reorder_level;

CREATE VIEW v_inventory_summary AS
SELECT
    i.inventory_id,
    p.product_id,
    p.product_name,
    w.warehouse_id,
    w.warehouse_name,
    i.quantity,
    p.unit_price,
    (i.quantity * p.unit_price) AS inventory_value
FROM Inventory AS i
JOIN Product AS p ON p.product_id = i.product_id
JOIN Warehouse AS w ON w.warehouse_id = i.warehouse_id;
