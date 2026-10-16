SELECT p.product_id, p.product_name, c.category_name, s.supplier_name, p.unit_price
FROM Product AS p
JOIN Category AS c ON c.category_id = p.category_id
JOIN Supplier AS s ON s.supplier_id = p.supplier_id
ORDER BY p.product_name;

SELECT i.inventory_id, p.product_name, w.warehouse_name, i.quantity, p.reorder_level
FROM Inventory AS i
JOIN Product AS p ON p.product_id = i.product_id
JOIN Warehouse AS w ON w.warehouse_id = i.warehouse_id
ORDER BY w.warehouse_name, p.product_name;

SELECT w.warehouse_name, SUM(i.quantity) AS total_units,
       ROUND(SUM(i.quantity * p.unit_price), 2) AS total_inventory_value
FROM Warehouse AS w
JOIN Inventory AS i ON i.warehouse_id = w.warehouse_id
JOIN Product AS p ON p.product_id = i.product_id
GROUP BY w.warehouse_id, w.warehouse_name
ORDER BY total_inventory_value DESC;

SELECT product_name, category_name, warehouse_name, quantity, reorder_level
FROM v_low_stock
ORDER BY quantity, product_name;

SELECT p.product_id, p.product_name, p.reorder_level,
       COALESCE((SELECT SUM(i.quantity) FROM Inventory AS i WHERE i.product_id = p.product_id), 0) AS total_stock
FROM Product AS p
WHERE COALESCE((SELECT SUM(i.quantity) FROM Inventory AS i WHERE i.product_id = p.product_id), 0) <= p.reorder_level
ORDER BY total_stock, p.product_name;

SELECT s.supplier_name, COUNT(p.product_id) AS product_count,
       ROUND(AVG(p.unit_price), 2) AS average_product_price
FROM Supplier AS s
LEFT JOIN Product AS p ON p.supplier_id = s.supplier_id
GROUP BY s.supplier_id, s.supplier_name
ORDER BY product_count DESC, s.supplier_name;

SELECT product_name, unit_price
FROM Product
WHERE unit_price > (SELECT AVG(unit_price) FROM Product)
ORDER BY unit_price DESC;

SELECT DATE_FORMAT(moved_at, '%Y-%m') AS movement_month, movement_type,
       COUNT(*) AS movement_count, SUM(quantity) AS units_moved
FROM Stock_Movement
GROUP BY DATE_FORMAT(moved_at, '%Y-%m'), movement_type
ORDER BY movement_month, movement_type;

SELECT w.warehouse_name, COUNT(DISTINCT i.product_id) AS distinct_products
FROM Warehouse AS w
LEFT JOIN Inventory AS i ON i.warehouse_id = w.warehouse_id
GROUP BY w.warehouse_id, w.warehouse_name
HAVING COUNT(DISTINCT i.product_id) >= 20
ORDER BY distinct_products DESC;

WITH product_value AS (
    SELECT p.product_id, p.product_name, SUM(i.quantity * p.unit_price) AS stock_value
    FROM Product AS p
    JOIN Inventory AS i ON i.product_id = p.product_id
    GROUP BY p.product_id, p.product_name
)
SELECT product_name, ROUND(stock_value, 2) AS stock_value
FROM product_value
ORDER BY stock_value DESC
LIMIT 5;
