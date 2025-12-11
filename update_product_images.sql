-- Update product images for Electronics category
-- Operating System subcategory -> softwares.jpg
-- All other Electronics subcategories -> accessories.png

-- Update Operating System products to use software image
UPDATE products
SET product_image_url = 'assets/softwares.jpg'
WHERE category_id = 1
  AND subcategory_id = (
    SELECT id FROM subcategories
    WHERE category_id = 1
      AND name = 'Operating System'
  );

-- Update all other Electronics products to use accessories image
UPDATE products
SET product_image_url = 'assets/accessories.png'
WHERE category_id = 1
  AND subcategory_id != (
    SELECT id FROM subcategories
    WHERE category_id = 1
      AND name = 'Operating System'
  );

-- Verify the updates
SELECT
    p.id,
    p.name,
    s.name AS subcategory,
    p.product_image_url
FROM products p
JOIN subcategories s ON p.subcategory_id = s.id
WHERE p.category_id = 1
ORDER BY s.name, p.id;
