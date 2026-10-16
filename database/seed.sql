SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE Stock_Movement;
TRUNCATE TABLE Inventory;
TRUNCATE TABLE Product;
TRUNCATE TABLE Warehouse;
TRUNCATE TABLE Supplier;
TRUNCATE TABLE Category;
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO Category (category_name) VALUES
('Electronics'),
('Office Supplies'),
('Furniture'),
('Safety Equipment'),
('Packaging');

INSERT INTO Supplier (supplier_name, phone) VALUES
('TechSource Distributors', '9876543210'),
('OfficeMart Supplies', '9876501234'),
('Comfort Furniture Co.', '9876512345'),
('SafeGuard Industrial', '9876523456'),
('PackRight Solutions', '9876534567');

INSERT INTO Warehouse (warehouse_name, location) VALUES
('Central Warehouse', 'Bengaluru, Karnataka'),
('North Warehouse', 'Delhi, NCR'),
('South Warehouse', 'Chennai, Tamil Nadu');

INSERT INTO Product (category_id, supplier_id, product_name, unit_price, reorder_level) VALUES
(1, 1, 'Wireless Keyboard', 1450.00, 12),
(1, 1, 'Optical Mouse', 650.00, 20),
(1, 1, '24-inch Monitor', 11200.00, 5),
(1, 1, 'USB-C Docking Station', 3900.00, 8),
(1, 1, 'Laser Printer', 18500.00, 3),
(2, 2, 'A4 Copier Paper Pack', 320.00, 30),
(2, 2, 'Blue Ball Pen Box', 240.00, 25),
(2, 2, 'Stapler', 180.00, 15),
(2, 2, 'Permanent Marker Set', 300.00, 10),
(3, 3, 'Ergonomic Office Chair', 7800.00, 6),
(3, 3, 'Work Desk', 9500.00, 4),
(3, 3, 'Metal Filing Cabinet', 6200.00, 5),
(3, 3, 'Visitor Chair', 2800.00, 8),
(4, 4, 'Safety Helmet', 850.00, 20),
(4, 4, 'Nitrile Gloves Box', 550.00, 25),
(4, 4, 'Fire Extinguisher', 3200.00, 4),
(4, 4, 'Reflective Safety Vest', 480.00, 15),
(5, 5, 'Cardboard Box Large', 75.00, 50),
(5, 5, 'Bubble Wrap Roll', 420.00, 12),
(5, 5, 'Packing Tape Roll', 60.00, 40),
(5, 5, 'Shipping Label Pack', 280.00, 20),
(5, 5, 'Stretch Film Roll', 350.00, 10);

INSERT INTO Inventory (warehouse_id, product_id, quantity) VALUES
(1, 1, 30), (2, 1, 8), (3, 1, 18),
(1, 2, 45), (2, 2, 25), (3, 2, 12),
(1, 3, 9), (2, 3, 3), (3, 3, 7),
(1, 4, 14), (2, 4, 6), (3, 4, 10),
(1, 5, 5), (2, 5, 2), (3, 5, 4),
(1, 6, 80), (2, 6, 28), (3, 6, 55),
(1, 7, 40), (2, 7, 20), (3, 7, 30),
(1, 8, 25), (2, 8, 10), (3, 8, 18),
(1, 9, 16), (2, 9, 8), (3, 9, 12),
(1, 10, 8), (2, 10, 4), (3, 10, 9),
(1, 11, 6), (2, 11, 3), (3, 11, 5),
(1, 12, 8), (2, 12, 4), (3, 12, 7),
(1, 13, 15), (2, 13, 6), (3, 13, 10),
(1, 14, 35), (2, 14, 18), (3, 14, 24),
(1, 15, 40), (2, 15, 20), (3, 15, 30),
(1, 16, 6), (2, 16, 3), (3, 16, 5),
(1, 17, 20), (2, 17, 12), (3, 17, 18),
(1, 18, 100), (2, 18, 40), (3, 18, 75),
(1, 19, 20), (2, 19, 10), (3, 19, 18),
(1, 20, 70), (2, 20, 35), (3, 20, 55),
(1, 21, 30), (2, 21, 15), (3, 21, 22),
(1, 22, 16), (2, 22, 8), (3, 22, 12);

INSERT INTO Stock_Movement (product_id, warehouse_id, movement_type, quantity, moved_at) VALUES
(1, 1, 'IN', 30, '2026-10-01 09:00:00'),
(2, 2, 'IN', 25, '2026-10-01 10:15:00'),
(3, 2, 'OUT', 2, '2026-10-02 11:30:00'),
(6, 1, 'IN', 80, '2026-10-03 09:45:00'),
(10, 3, 'TRANSFER', 3, '2026-10-04 14:00:00'),
(14, 2, 'IN', 18, '2026-10-05 12:20:00'),
(18, 1, 'IN', 100, '2026-10-06 10:10:00'),
(20, 2, 'OUT', 5, '2026-10-07 16:30:00'),
(5, 1, 'IN', 5, '2026-10-08 11:00:00'),
(16, 3, 'OUT', 1, '2026-10-09 15:10:00'),
(9, 1, 'TRANSFER', 4, '2026-10-10 13:25:00'),
(22, 2, 'IN', 8, '2026-10-11 09:50:00');
