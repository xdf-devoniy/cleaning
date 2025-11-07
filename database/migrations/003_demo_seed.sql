BEGIN TRANSACTION;
INSERT INTO services (name, description, base_price, duration_min) VALUES
('General Cleaning', 'Standard residential cleaning', 1500000, 180),
('Deep Cleaning', 'Detailed cleaning package', 2500000, 300),
('Office Cleaning', 'Scheduled office package', 2000000, 240);

INSERT INTO clients (name, status, phone, email, preferred_days, preferred_time)
VALUES ('Tashkent Residence', 'active', '+998901234567', 'client1@example.com', 'Mon,Wed,Fri', '09:00'),
       ('Samarkand LLC', 'active', '+998907654321', 'client2@example.com', 'Tue,Thu', '14:00');

INSERT INTO addresses (client_id, label, address_line, latitude, longitude)
VALUES (1, 'Main Apartment', 'Yunusabad 5, Tashkent', 41.3385, 69.3347),
       (2, 'Head Office', 'Registan st 12, Samarkand', 39.6542, 66.9750);

INSERT INTO orders (client_id, address_id, status, scheduled_at, duration, subtotal, total)
VALUES (1, 1, 'scheduled', date('now'), 180, 1500000, 1500000),
       (2, 2, 'scheduled', date('now', '+1 day'), 240, 2000000, 2000000);

INSERT INTO order_items (order_id, service_id, qty, unit_price, line_total)
VALUES (1, 1, 1, 1500000, 1500000),
       (2, 3, 1, 2000000, 2000000);

COMMIT;
