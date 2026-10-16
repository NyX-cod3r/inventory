DROP PROCEDURE IF EXISTS sp_record_stock_movement;

DELIMITER //

CREATE PROCEDURE sp_record_stock_movement(
    IN p_product_id INT,
    IN p_warehouse_id INT,
    IN p_movement_type VARCHAR(10),
    IN p_quantity INT,
    IN p_to_warehouse_id INT
)
BEGIN
    DECLARE v_source_quantity INT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    IF p_quantity IS NULL OR p_quantity <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Movement quantity must be greater than zero';
    END IF;

    IF p_movement_type NOT IN ('IN', 'OUT', 'TRANSFER') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid movement type';
    END IF;

    IF p_movement_type = 'TRANSFER' AND (p_to_warehouse_id IS NULL OR p_to_warehouse_id = p_warehouse_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'A transfer requires a different destination warehouse';
    END IF;

    START TRANSACTION;

    IF p_movement_type IN ('OUT', 'TRANSFER') THEN
        SELECT quantity INTO v_source_quantity
        FROM Inventory
        WHERE product_id = p_product_id AND warehouse_id = p_warehouse_id
        FOR UPDATE;

        IF v_source_quantity IS NULL OR v_source_quantity < p_quantity THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Insufficient stock for this movement';
        END IF;
    END IF;

    IF p_movement_type = 'IN' THEN
        INSERT INTO Inventory (warehouse_id, product_id, quantity)
        VALUES (p_warehouse_id, p_product_id, p_quantity)
        ON DUPLICATE KEY UPDATE quantity = quantity + p_quantity;
    ELSEIF p_movement_type = 'OUT' THEN
        UPDATE Inventory
        SET quantity = quantity - p_quantity
        WHERE product_id = p_product_id AND warehouse_id = p_warehouse_id;
    ELSE
        UPDATE Inventory
        SET quantity = quantity - p_quantity
        WHERE product_id = p_product_id AND warehouse_id = p_warehouse_id;

        INSERT INTO Inventory (warehouse_id, product_id, quantity)
        VALUES (p_to_warehouse_id, p_product_id, p_quantity)
        ON DUPLICATE KEY UPDATE quantity = quantity + p_quantity;
    END IF;

    INSERT INTO Stock_Movement (product_id, warehouse_id, movement_type, quantity)
    VALUES (p_product_id, p_warehouse_id, p_movement_type, p_quantity);

    COMMIT;
END//

DELIMITER ;
