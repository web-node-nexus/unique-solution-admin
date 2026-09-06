-- Unique Solution demo data (MySQL / MariaDB)
-- Prerequisites: php artisan migrate:fresh --seed  (base catalog rows must exist)
-- Import: mysql -u USER -p DB_NAME < database/demo_data.mysql.sql

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;
START TRANSACTION;


-- Settings (upsert)
INSERT INTO settings (key, value, created_at, updated_at) VALUES
('shop_name', 'Unique Solution', NOW(), NOW()),
('shop_tagline', 'आपकी अपनी दुकान · Kurud', NOW(), NOW()),
('shop_address', 'Kargil Chowk, Megha Road, Kurud - 493663', NOW(), NOW()),
('contact_number', '+91 9876543210', NOW(), NOW()),
('whatsapp_number', '919876543210', NOW(), NOW()),
('contact_email', 'info@uniquesolution.com', NOW(), NOW()),
('tax_percentage', '18', NOW(), NOW()),
('default_shipping_charge', '49', NOW(), NOW()),
('currency_symbol', '₹', NOW(), NOW()),
('serviceable_pincodes', '493663,493661,492001,492002,490001', NOW(), NOW()),
('delivery_eta_days', '4', NOW(), NOW())
ON DUPLICATE KEY UPDATE value=VALUES(value), updated_at=VALUES(updated_at);

-- Categories: images + sale banners
UPDATE categories SET
  image = CONCAT('categories/demo-c', id, '.jpg'),
  status = 1,
  sale_active = CASE WHEN id IN (1,4,5) THEN 1 ELSE 0 END,
  sale_title = CASE
    WHEN id = 1 THEN 'AC Summer Sale'
    WHEN id = 4 THEN 'Phone Bonanza'
    WHEN id = 5 THEN 'TV Fiesta'
    ELSE sale_title END,
  sale_subtitle = CASE
    WHEN id IN (1,4,5) THEN 'Limited period offers · free installation check'
    ELSE sale_subtitle END,
  sale_banner = CASE WHEN id IN (1,4,5) THEN 'sales/demo-sale-1.jpg' ELSE sale_banner END,
  updated_at = NOW()
WHERE id BETWEEN 1 AND 13;

-- Brands: logos + warranty + category link
UPDATE brands SET
  logo = CONCAT('brands/demo-b', id, '.jpg'),
  status = 1,
  warranty = CASE
    WHEN id IN (1,2,3,4) THEN '1 Year Brand Warranty + 6 Months Screen Protect'
    WHEN id IN (5,6,7,10) THEN '2 Years Comprehensive Warranty'
    ELSE '1 Year Manufacturer Warranty'
  END,
  category_id = CASE
    WHEN id IN (1,4,8,9) THEN 4
    WHEN id IN (5) THEN 3
    WHEN id IN (2,6,7,10) THEN 1
    WHEN id IN (3) THEN 5
    ELSE category_id
  END,
  updated_at = NOW()
WHERE id BETWEEN 1 AND 14;

-- Products: featured, sale prices, clean test junk
UPDATE products SET
  status = 'active',
  is_featured = CASE WHEN id IN (1,2,3,5,6,8,11,14,16,18,23) THEN 1 ELSE 0 END,
  sale_price = CASE
    WHEN id = 1 THEN 31999
    WHEN id = 2 THEN 23999
    WHEN id = 3 THEN 74900
    WHEN id = 4 THEN 21999
    WHEN id = 5 THEN 54990
    WHEN id = 8 THEN 34990
    WHEN id = 11 THEN 23990
    WHEN id = 14 THEN 62990
    WHEN id = 15 THEN 28990
    WHEN id = 18 THEN 28990
    WHEN id = 23 THEN 21999
    WHEN id = 24 THEN 22999
    ELSE sale_price
  END,
  warranty_info = COALESCE(warranty_info, 'Brand warranty applicable as per manufacturer terms. Visit Unique Solution, Kurud for claim support.'),
  description = COALESCE(NULLIF(description,''), 'Genuine product from Unique Solution, Kurud. Demo / installation support available on select appliances.'),
  updated_at = NOW()
WHERE id BETWEEN 1 AND 24;

UPDATE products SET status = 'active', is_featured = 0, name = 'Demo Earphones Pack', slug = CONCAT('demo-earphones-pack-', id)
WHERE id = 22;

-- Product images (clear old missing refs for 1-22 if any, then insert)
DELETE FROM product_images WHERE product_id BETWEEN 1 AND 24 AND image_path LIKE 'products/demo-p%';
INSERT INTO product_images (product_id, image_path, is_primary, sort_order, created_at, updated_at)
SELECT id, CONCAT('products/demo-p', id, '.jpg'), 1, 0, NOW(), NOW()
FROM products WHERE id BETWEEN 1 AND 24
AND NOT EXISTS (
  SELECT 1 FROM product_images pi WHERE pi.product_id = products.id AND pi.is_primary = 1
);

-- Also ensure every product has at least one image
INSERT INTO product_images (product_id, image_path, is_primary, sort_order, created_at, updated_at)
SELECT p.id, CONCAT('products/demo-p', p.id, '.jpg'), 1, 0, NOW(), NOW()
FROM products p
WHERE p.id BETWEEN 1 AND 24
AND (SELECT COUNT(*) FROM product_images pi WHERE pi.product_id = p.id) = 0;

-- Secondary gallery images (angles) for every product
INSERT INTO product_images (product_id, image_path, is_primary, sort_order, created_at, updated_at)
SELECT id, CONCAT('products/demo-p', CASE WHEN id < 24 THEN id+1 ELSE 1 END, '.jpg'), 0, 1, NOW(), NOW()
FROM products WHERE id BETWEEN 1 AND 24;

INSERT INTO product_images (product_id, image_path, is_primary, sort_order, created_at, updated_at)
SELECT id, CONCAT('products/demo-p', CASE WHEN id < 23 THEN id+2 ELSE ((id + 1) % 24) + 1 END, '.jpg'), 0, 2, NOW(), NOW()
FROM products WHERE id BETWEEN 1 AND 24;

INSERT INTO product_images (product_id, image_path, is_primary, sort_order, created_at, updated_at)
SELECT id, CONCAT('products/demo-p', CASE WHEN id < 22 THEN id+3 ELSE ((id + 2) % 24) + 1 END, '.jpg'), 0, 3, NOW(), NOW()
FROM products WHERE id IN (1,2,3,4,8,11,14,15,16,22,24);

-- Attributes + values (idempotent-ish using fixed ids high range)
DELETE FROM variant_attribute_values WHERE attribute_value_id >= 900;
DELETE FROM category_attributes WHERE attribute_id >= 900;
DELETE FROM attribute_values WHERE id >= 900;
DELETE FROM attributes WHERE id >= 900;

INSERT INTO attributes (id, name, type, status, created_at, updated_at) VALUES
(900, 'Color', 'color-swatch', 1, NOW(), NOW()),
(901, 'Storage', 'dropdown', 1, NOW(), NOW()),
(902, 'RAM', 'dropdown', 1, NOW(), NOW()),
(903, 'Capacity', 'dropdown', 1, NOW(), NOW());

INSERT INTO attribute_values (id, attribute_id, value, extra_data, created_at, updated_at) VALUES
(900, 900, 'Black', '{"hex":"#111111"}', NOW(), NOW()),
(901, 900, 'Blue', '{"hex":"#1E5AA8"}', NOW(), NOW()),
(902, 900, 'Silver', '{"hex":"#C0C0C0"}', NOW(), NOW()),
(903, 901, '128GB', NULL, NOW(), NOW()),
(904, 901, '256GB', NULL, NOW(), NOW()),
(905, 902, '8GB', NULL, NOW(), NOW()),
(906, 902, '12GB', NULL, NOW(), NOW()),
(907, 903, '1 Ton', NULL, NOW(), NOW()),
(908, 903, '1.5 Ton', NULL, NOW(), NOW());

INSERT INTO category_attributes (category_id, attribute_id, created_at, updated_at) VALUES
(4, 900, NOW(), NOW()),
(4, 901, NOW(), NOW()),
(4, 902, NOW(), NOW()),
(10, 900, NOW(), NOW()),
(10, 901, NOW(), NOW()),
(1, 903, NOW(), NOW()),
(8, 903, NOW(), NOW());

-- Banners
DELETE FROM banners WHERE title LIKE 'US Demo%';
INSERT INTO banners (title, subtitle, image_path, link_type, link_value, sort_order, status, starts_at, ends_at, created_at, updated_at) VALUES
('US Demo · Phone Mega Days', 'Upto ₹8,000 off on selected smartphones', 'banners/demo-banner-1.jpg', 'category', '4', 1, 1, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 60 DAY), NOW(), NOW()),
('US Demo · Cool Summer ACs', 'Split & Window ACs with free demo', 'banners/demo-banner-2.jpg', 'category', '1', 2, 1, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 60 DAY), NOW(), NOW()),
('US Demo · Smart TV Week', '4K & QLED starting offers', 'banners/demo-banner-3.jpg', 'category', '5', 3, 1, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 60 DAY), NOW(), NOW()),
('US Demo · Visit Kurud Store', 'Kargil Chowk · expert guidance', 'banners/demo-banner-4.jpg', 'url', '/brands', 4, 1, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 60 DAY), NOW(), NOW());

-- Sales
DELETE FROM sales WHERE title LIKE 'US Demo%';
INSERT INTO sales (title, subtitle, description, image, starts_at, ends_at, link_type, link_value, status, sort_order, notify_users, created_at, updated_at) VALUES
('US Demo · Independence Offers', 'Electronics bank holiday specials', 'Flat deals across mobiles and appliances.', 'sales/demo-sale-1.jpg', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_ADD(NOW(), INTERVAL 45 DAY), 'category', '4', 1, 1, 0, NOW(), NOW()),
('US Demo · Appliance Fest', 'Fridge · AC · WM', 'Seasonal appliance offers at Unique Solution.', 'sales/demo-sale-2.jpg', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_ADD(NOW(), INTERVAL 45 DAY), 'category', '2', 1, 2, 0, NOW(), NOW()),
('US Demo · Coupon Carnival', 'Use code UNIQUE100', 'Extra savings at checkout.', 'sales/demo-sale-3.jpg', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_ADD(NOW(), INTERVAL 45 DAY), 'coupon', 'UNIQUE100', 1, 3, 0, NOW(), NOW());

-- Coupons
DELETE FROM coupons WHERE code IN ('UNIQUE100','WELCOME50','ACSAVE500','TV10');
INSERT INTO coupons (code, title, description, discount_type, discount_value, min_order_value, max_uses, used_count, start_date, expiry_date, image, status, created_at, updated_at) VALUES
('UNIQUE100', 'Flat ₹100 off', 'On orders above ₹2,999', 'fixed', 100, 2999, 500, 12, DATE(DATE_SUB(NOW(), INTERVAL 7 DAY)), DATE(DATE_ADD(NOW(), INTERVAL 90 DAY)), 'coupons/demo-coupon-1.png', 1, NOW(), NOW()),
('WELCOME50', 'Welcome 5% off', 'New customers special', 'percent', 5, 1999, 1000, 40, DATE(DATE_SUB(NOW(), INTERVAL 7 DAY)), DATE(DATE_ADD(NOW(), INTERVAL 90 DAY)), 'coupons/demo-coupon-2.png', 1, NOW(), NOW()),
('ACSAVE500', '₹500 AC offer', 'On AC category carts above ₹25,000', 'fixed', 500, 25000, 200, 5, DATE(DATE_SUB(NOW(), INTERVAL 7 DAY)), DATE(DATE_ADD(NOW(), INTERVAL 60 DAY)), 'coupons/demo-coupon-3.png', 1, NOW(), NOW()),
('TV10', '10% TV weekend', 'Max value coupon on TVs', 'percent', 10, 15000, 150, 8, DATE(DATE_SUB(NOW(), INTERVAL 3 DAY)), DATE(DATE_ADD(NOW(), INTERVAL 30 DAY)), 'coupons/demo-coupon-4.png', 1, NOW(), NOW());

-- Notifications / announcements
DELETE FROM app_notifications WHERE title LIKE 'US Demo%';
INSERT INTO app_notifications (type, title, body, image, link_type, link_value, audience, status, sent_by, sent_at, fcm_success_count, fcm_failure_count, created_at, updated_at) VALUES
('announcement', 'US Demo · Store open today', 'Unique Solution Kurud is open 10am–8pm. Visit Kargil Chowk for demos.', 'notifications/demo-n-1.png', 'none', NULL, 'all', 'sent', 1, DATE_SUB(NOW(), INTERVAL 1 DAY), 12, 0, NOW(), NOW()),
('sale', 'US Demo · Phone Mega Sale live', 'Tap to browse smartphones on offer.', 'notifications/demo-n-2.png', 'category', '4', 'all', 'sent', 1, DATE_SUB(NOW(), INTERVAL 2 HOUR), 20, 1, NOW(), NOW()),
('announcement', 'US Demo · Free delivery above ₹4,999', 'Selected pin codes around Kurud.', 'notifications/demo-n-3.png', 'url', '/deals', 'customers', 'sent', 1, DATE_SUB(NOW(), INTERVAL 5 HOUR), 8, 0, NOW(), NOW());

-- Customer addresses (for customers 5-9)
DELETE FROM customer_addresses WHERE user_id BETWEEN 5 AND 9;
INSERT INTO customer_addresses (user_id, label, address, city, state, pincode, is_default, created_at, updated_at) VALUES
(5, 'Home', 'Ward 12, near Megha Road', 'Kurud', 'Chhattisgarh', '493663', 1, NOW(), NOW()),
(5, 'Office', 'Main Market Road', 'Kurud', 'Chhattisgarh', '493663', 0, NOW(), NOW()),
(6, 'Home', 'House No 44, School Para', 'Kurud', 'Chhattisgarh', '493661', 1, NOW(), NOW()),
(7, 'Home', 'Plot 9, Civil Lines', 'Raipur', 'Chhattisgarh', '492001', 1, NOW(), NOW()),
(8, 'Home', 'Sector 2, Ring Road', 'Raipur', 'Chhattisgarh', '492002', 1, NOW(), NOW()),
(9, 'Home', 'Bhilai Nagar Sector 6', 'Bhilai', 'Chhattisgarh', '490001', 1, NOW(), NOW());

-- Wishlists
DELETE FROM wishlists WHERE user_id BETWEEN 5 AND 9;
INSERT INTO wishlists (user_id, product_id, created_at, updated_at) VALUES
(5, 1, NOW(), NOW()),
(5, 8, NOW(), NOW()),
(5, 14, NOW(), NOW()),
(6, 3, NOW(), NOW()),
(6, 11, NOW(), NOW()),
(7, 2, NOW(), NOW()),
(7, 16, NOW(), NOW()),
(8, 6, NOW(), NOW()),
(9, 18, NOW(), NOW());

-- Cart items
DELETE FROM cart_items WHERE user_id BETWEEN 5 AND 9;
INSERT INTO cart_items (user_id, product_id, product_variant_id, quantity, name, image_url, mrp, sale_price, attribute_label, created_at, updated_at) VALUES
(5, 1, 1, 1, 'Samsung Galaxy A54', NULL, 34999, 31999, 'Black / 128GB', NOW(), NOW()),
(5, 8, (SELECT id FROM product_variants WHERE product_id=8 LIMIT 1), 1, 'Voltas 1.5 Ton Split Inverter AC', NULL, 38990, 34990, '1.5 Ton', NOW(), NOW()),
(6, 3, 9, 1, 'Apple iPhone 15', NULL, 79900, 74900, 'Black / 128GB', NOW(), NOW());

-- Reviews (approved) — clear demo ones first
DELETE FROM reviews WHERE comment LIKE 'US Demo%' OR (product_id BETWEEN 1 AND 20 AND user_id BETWEEN 5 AND 9);
INSERT INTO reviews (product_id, user_id, rating, comment, admin_reply, status, created_at, updated_at) VALUES
(1, 5, 5, 'US Demo · Smooth phone, genuine bill from Unique Solution Kurud.', 'Thanks Rahul! Visit again.', 'approved', DATE_SUB(NOW(), INTERVAL 10 DAY), NOW()),
(1, 6, 4, 'US Demo · Good camera, delivery was on time.', NULL, 'approved', DATE_SUB(NOW(), INTERVAL 8 DAY), NOW()),
(2, 7, 5, 'US Demo · Battery lasts full day. Staff explained EMI clearly.', 'Glad it helped!', 'approved', DATE_SUB(NOW(), INTERVAL 6 DAY), NOW()),
(3, 8, 5, 'US Demo · iPhone sealed pack, fair price.', NULL, 'approved', DATE_SUB(NOW(), INTERVAL 4 DAY), NOW()),
(8, 5, 4, 'US Demo · AC cooling excellent. Installation arranged next day.', 'Thank you for the feedback.', 'approved', DATE_SUB(NOW(), INTERVAL 3 DAY), NOW()),
(14, 6, 5, 'US Demo · Picture quality superb on Bravia.', NULL, 'approved', DATE_SUB(NOW(), INTERVAL 2 DAY), NOW()),
(11, 7, 4, 'US Demo · Fridge is quiet and spacious.', NULL, 'approved', DATE_SUB(NOW(), INTERVAL 1 DAY), NOW()),
(18, 9, 3, 'US Demo · Washer is fine, wish door was quieter.', 'Noted — we will share care tips.', 'approved', NOW(), NOW()),
(23, 5, 5, 'US Demo · Local support is why I buy here.', NULL, 'approved', NOW(), NOW()),
(5, 8, 4, 'US Demo · Laptop for college work is solid.', NULL, 'pending', NOW(), NOW());

-- Orders + items + history + payments + refund
DELETE FROM refunds WHERE reason LIKE 'US Demo%';
DELETE FROM payments WHERE order_id IN (SELECT id FROM orders WHERE order_number LIKE 'US-DEMO-%');
DELETE FROM order_status_history WHERE order_id IN (SELECT id FROM orders WHERE order_number LIKE 'US-DEMO-%');
DELETE FROM order_items WHERE order_id IN (SELECT id FROM orders WHERE order_number LIKE 'US-DEMO-%');
DELETE FROM orders WHERE order_number LIKE 'US-DEMO-%';

INSERT INTO orders (order_number, user_id, subtotal, discount, tax, shipping_charge, total_amount, payment_status, order_status, shipping_address, billing_address, notes, created_at, updated_at) VALUES
('US-DEMO-1001', 5, 31999, 100, 5741.82, 49, 37689.82, 'paid', 'delivered', '(Home) Ward 12, near Megha Road, Kurud, Chhattisgarh, 493663', '(Home) Ward 12, near Megha Road, Kurud, Chhattisgarh, 493663', 'Coupon: UNIQUE100', DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY)),
('US-DEMO-1002', 6, 74900, 0, 13482, 49, 88431, 'paid', 'shipped', '(Home) House No 44, School Para, Kurud, Chhattisgarh, 493661', '(Home) House No 44, School Para, Kurud, Chhattisgarh, 493661', NULL, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
('US-DEMO-1003', 7, 34990, 500, 6208.2, 49, 40747.2, 'pending', 'confirmed', '(Home) Plot 9, Civil Lines, Raipur, Chhattisgarh, 492001', '(Home) Plot 9, Civil Lines, Raipur, Chhattisgarh, 492001', 'COD request', DATE_SUB(NOW(), INTERVAL 1 DAY), NOW()),
('US-DEMO-1004', 5, 23990, 0, 4318.2, 49, 28357.2, 'paid', 'processing', '(Home) Ward 12, near Megha Road, Kurud, Chhattisgarh, 493663', '(Home) Ward 12, near Megha Road, Kurud, Chhattisgarh, 493663', NULL, DATE_SUB(NOW(), INTERVAL 6 HOUR), NOW());

INSERT INTO order_items (order_id, product_variant_id, product_name_snapshot, variant_details_snapshot, quantity, price, subtotal, created_at, updated_at)
SELECT o.id, 1, 'Samsung Galaxy A54', '{"attributes":[{"name":"Color","value":"Black"},{"name":"Storage","value":"128GB"}]}', 1, 31999, 31999, NOW(), NOW()
FROM orders o WHERE o.order_number='US-DEMO-1001';

INSERT INTO order_items (order_id, product_variant_id, product_name_snapshot, variant_details_snapshot, quantity, price, subtotal, created_at, updated_at)
SELECT o.id, 9, 'Apple iPhone 15', '{"attributes":[{"name":"Color","value":"Black"},{"name":"Storage","value":"128GB"}]}', 1, 74900, 74900, NOW(), NOW()
FROM orders o WHERE o.order_number='US-DEMO-1002';

INSERT INTO order_items (order_id, product_variant_id, product_name_snapshot, variant_details_snapshot, quantity, price, subtotal, created_at, updated_at)
SELECT o.id, (SELECT id FROM product_variants WHERE product_id=8 LIMIT 1), 'Voltas 1.5 Ton Split Inverter AC', '{"attributes":[{"name":"Capacity","value":"1.5 Ton"}]}', 1, 34990, 34990, NOW(), NOW()
FROM orders o WHERE o.order_number='US-DEMO-1003';

INSERT INTO order_items (order_id, product_variant_id, product_name_snapshot, variant_details_snapshot, quantity, price, subtotal, created_at, updated_at)
SELECT o.id, (SELECT id FROM product_variants WHERE product_id=11 LIMIT 1), 'Samsung 253L Double Door Refrigerator', '{}', 1, 23990, 23990, NOW(), NOW()
FROM orders o WHERE o.order_number='US-DEMO-1004';

INSERT INTO order_status_history (order_id, status, remarks, changed_by, created_at, updated_at)
SELECT id, 'pending', 'Order placed', 5, DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY) FROM orders WHERE order_number='US-DEMO-1001'
UNION ALL SELECT id, 'confirmed', 'Payment received', 1, DATE_SUB(NOW(), INTERVAL 11 DAY), DATE_SUB(NOW(), INTERVAL 11 DAY) FROM orders WHERE order_number='US-DEMO-1001'
UNION ALL SELECT id, 'shipped', 'Out for delivery · Kurud', 1, DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_SUB(NOW(), INTERVAL 7 DAY) FROM orders WHERE order_number='US-DEMO-1001'
UNION ALL SELECT id, 'delivered', 'Delivered to customer', 1, DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY) FROM orders WHERE order_number='US-DEMO-1001'
UNION ALL SELECT id, 'pending', 'Order placed', 6, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY) FROM orders WHERE order_number='US-DEMO-1002'
UNION ALL SELECT id, 'confirmed', 'Payment received', 1, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY) FROM orders WHERE order_number='US-DEMO-1002'
UNION ALL SELECT id, 'shipped', 'Courier: BlueDart · AWB DEMO123', 1, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY) FROM orders WHERE order_number='US-DEMO-1002'
UNION ALL SELECT id, 'pending', 'Order placed', 7, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY) FROM orders WHERE order_number='US-DEMO-1003'
UNION ALL SELECT id, 'confirmed', 'COD confirmed by shop', 1, DATE_SUB(NOW(), INTERVAL 20 HOUR), DATE_SUB(NOW(), INTERVAL 20 HOUR) FROM orders WHERE order_number='US-DEMO-1003'
UNION ALL SELECT id, 'pending', 'Order placed', 5, DATE_SUB(NOW(), INTERVAL 6 HOUR), DATE_SUB(NOW(), INTERVAL 6 HOUR) FROM orders WHERE order_number='US-DEMO-1004'
UNION ALL SELECT id, 'processing', 'Packing at Kurud store', 1, DATE_SUB(NOW(), INTERVAL 2 HOUR), DATE_SUB(NOW(), INTERVAL 2 HOUR) FROM orders WHERE order_number='US-DEMO-1004';

INSERT INTO payments (order_id, payment_method, transaction_id, amount, status, gateway_response, paid_at, created_at, updated_at)
SELECT id, 'razorpay', 'pay_demo_1001', total_amount, 'paid', '{"source":"demo"}', DATE_SUB(NOW(), INTERVAL 12 DAY), NOW(), NOW() FROM orders WHERE order_number='US-DEMO-1001'
UNION ALL SELECT id, 'razorpay', 'pay_demo_1002', total_amount, 'paid', '{"source":"demo"}', DATE_SUB(NOW(), INTERVAL 3 DAY), NOW(), NOW() FROM orders WHERE order_number='US-DEMO-1002'
UNION ALL SELECT id, 'cod', NULL, total_amount, 'pending', '{"source":"demo"}', NULL, NOW(), NOW() FROM orders WHERE order_number='US-DEMO-1003'
UNION ALL SELECT id, 'upi', 'pay_demo_1004', total_amount, 'paid', '{"source":"demo"}', DATE_SUB(NOW(), INTERVAL 6 HOUR), NOW(), NOW() FROM orders WHERE order_number='US-DEMO-1004';

INSERT INTO refunds (order_id, order_item_id, requested_by, reason, refund_amount, status, admin_remarks, processed_by, processed_at, created_at, updated_at)
SELECT o.id, oi.id, 5, 'US Demo · Wrong colour variant delivered', 500, 'pending', NULL, NULL, NULL, NOW(), NOW()
FROM orders o
JOIN order_items oi ON oi.order_id = o.id
WHERE o.order_number='US-DEMO-1001'
LIMIT 1;

-- Stock alerts
DELETE FROM stock_alerts WHERE email LIKE '%@demo.local';
INSERT INTO stock_alerts (user_id, product_id, product_variant_id, email, phone, notified_at, created_at, updated_at) VALUES
(5, 7, (SELECT id FROM product_variants WHERE product_id=7 LIMIT 1), 'rahul@demo.local', '9876543210', NULL, NOW(), NOW()),
(6, 9, (SELECT id FROM product_variants WHERE product_id=9 LIMIT 1), 'priya@demo.local', '9876501234', NULL, NOW(), NOW());

-- Inventory logs
INSERT INTO inventory_logs (variant_id, type, quantity, reason, created_by, created_at, updated_at)
SELECT id, 'in', 10, 'US Demo stock top-up', 1, NOW(), NOW() FROM product_variants WHERE product_id IN (1,2,3) LIMIT 6;

-- Activity logs
INSERT INTO activity_logs (user_id, action, module, description, ip_address, created_at, updated_at) VALUES
(1, 'updated', 'settings', 'US Demo settings refreshed', '127.0.0.1', NOW(), NOW()),
(1, 'created', 'products', 'US Demo catalog images attached', '127.0.0.1', NOW(), NOW());

-- Analytics + crash sample
INSERT INTO analytics_events (user_id, event, screen, properties, session_id, platform, app_version, device_id, occurred_at, created_at, updated_at) VALUES
(5, 'product_view', '/products/1', '{"product_id":1}', 'demo-session-1', 'android', '1.0.0', 'demo-device-1', NOW(), NOW(), NOW()),
(5, 'add_to_cart', '/products/1', '{"product_id":1}', 'demo-session-1', 'android', '1.0.0', 'demo-device-1', NOW(), NOW(), NOW()),
(6, 'screen_view', '/home', '{}', 'demo-session-2', 'android', '1.0.0', 'demo-device-2', NOW(), NOW(), NOW());

INSERT INTO crash_reports (user_id, level, message, stack, screen, context, platform, app_version, device_id, is_fatal, created_at, updated_at) VALUES
(NULL, 'error', 'US Demo sample crash', 'demo stack', '/home', '{"source":"demo"}', 'android', '1.0.0', 'demo-device-1', 0, NOW(), NOW());

COMMIT;
SET FOREIGN_KEY_CHECKS=1;

-- Quick counts
SELECT 'categories' AS t, COUNT(*) AS c FROM categories
UNION ALL SELECT 'brands', COUNT(*) FROM brands
UNION ALL SELECT 'products', COUNT(*) FROM products
UNION ALL SELECT 'product_images', COUNT(*) FROM product_images
UNION ALL SELECT 'banners', COUNT(*) FROM banners
UNION ALL SELECT 'sales', COUNT(*) FROM sales
UNION ALL SELECT 'coupons', COUNT(*) FROM coupons
UNION ALL SELECT 'notifications', COUNT(*) FROM app_notifications
UNION ALL SELECT 'reviews', COUNT(*) FROM reviews
UNION ALL SELECT 'orders', COUNT(*) FROM orders
UNION ALL SELECT 'addresses', COUNT(*) FROM customer_addresses
UNION ALL SELECT 'wishlists', COUNT(*) FROM wishlists
UNION ALL SELECT 'cart_items', COUNT(*) FROM cart_items;
