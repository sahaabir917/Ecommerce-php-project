-- ============================================================
-- Inventory Management Triggers for E-commerce Database
-- ============================================================
-- This file contains triggers to automatically manage inventory
-- when orders are placed for products and deals
-- ============================================================

-- Drop existing triggers if they exist
DROP TRIGGER IF EXISTS `trg_deal_products_reserve_stock`;
DROP TRIGGER IF EXISTS `trg_order_details_product_deduct`;
DROP TRIGGER IF EXISTS `trg_order_details_deal_deduct`;

-- ============================================================
-- TRIGGER 1: Reserve stock when deal is created
-- ============================================================
-- When a deal_product is created, reserve the quantity from the actual product stock
-- This prevents overselling when products are added to deals

DELIMITER $$
CREATE TRIGGER `trg_deal_products_reserve_stock`
AFTER INSERT ON `deal_products`
FOR EACH ROW
BEGIN
    -- Deduct the deal quantity from the product's available stock
    UPDATE products
    SET available_qty = GREATEST(0, available_qty - NEW.available_quantity)
    WHERE id = NEW.product_id;
END$$
DELIMITER ;

-- ============================================================
-- TRIGGER 2: Decrease inventory when regular product is ordered
-- ============================================================
-- When a regular product (not part of a deal) is ordered,
-- decrease the product's available quantity

DELIMITER $$
CREATE TRIGGER `trg_order_details_product_deduct`
AFTER INSERT ON `order_details`
FOR EACH ROW
BEGIN
    -- Only process if this is a regular product order (not a deal)
    IF NEW.product_id IS NOT NULL AND NEW.deal_id IS NULL THEN
        UPDATE products
        SET available_qty = GREATEST(0, available_qty - NEW.quantity)
        WHERE id = NEW.product_id;
    END IF;
END$$
DELIMITER ;

-- ============================================================
-- TRIGGER 3: Decrease inventory when deal is ordered
-- ============================================================
-- When a deal is ordered, decrease the deal's available quantity
-- Note: Product stock was already reserved when deal was created,
-- so we only need to update the deal_products table

DELIMITER $$
CREATE TRIGGER `trg_order_details_deal_deduct`
AFTER INSERT ON `order_details`
FOR EACH ROW
BEGIN
    -- Only process if this is a deal order
    IF NEW.deal_id IS NOT NULL THEN
        -- Decrease available quantity for all products in this deal
        UPDATE deal_products
        SET available_quantity = GREATEST(0, available_quantity - NEW.quantity)
        WHERE deal_id = NEW.deal_id;

        -- NOTE: We do NOT update the products table here because:
        -- The stock was already reserved/deducted when the deal_product was created
        -- (via trg_deal_products_reserve_stock trigger)
    END IF;
END$$
DELIMITER ;

-- ============================================================
-- Verification Queries (Run these to test the triggers)
-- ============================================================

-- Check current triggers
-- SELECT TRIGGER_NAME, EVENT_MANIPULATION, EVENT_OBJECT_TABLE, ACTION_TIMING
-- FROM information_schema.TRIGGERS
-- WHERE TRIGGER_SCHEMA = 'ecommerce'
-- ORDER BY EVENT_OBJECT_TABLE, ACTION_TIMING, EVENT_MANIPULATION;

-- Test product stock before and after order
-- SELECT id, name, available_qty FROM products WHERE id = 125;

-- Test deal stock before and after order
-- SELECT dp.id, dp.deal_id, dp.product_id, dp.available_quantity, p.available_qty AS product_stock
-- FROM deal_products dp
-- JOIN products p ON dp.product_id = p.id
-- WHERE dp.deal_id = 1;

-- ============================================================
-- IMPORTANT NOTES:
-- ============================================================
-- 1. Stock Flow for Regular Products:
--    - When ordered → products.available_qty decreases
--
-- 2. Stock Flow for Deals:
--    - When deal created → products.available_qty decreases (reserved)
--    - When deal ordered → deal_products.available_quantity decreases
--    - Product stock stays same (already reserved)
--
-- 3. GREATEST(0, ...) function ensures quantity never goes negative
--
-- 4. If you need to restore stock (e.g., order cancellation),
--    you'll need separate UPDATE queries or additional triggers
-- ============================================================
