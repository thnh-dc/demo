ALTER TABLE orders
ADD COLUMN payment_method VARCHAR(50) NULL,
ADD COLUMN payment_status ENUM('unpaid', 'paid') DEFAULT 'unpaid',
ADD COLUMN payment_code VARCHAR(100) NULL,
ADD COLUMN paid_at DATETIME NULL;