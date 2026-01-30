-- Execute each statement once. If a column already exists, skip that statement.

ALTER TABLE `registrations`
    ADD COLUMN `academic_major` VARCHAR(50) DEFAULT NULL AFTER `married_status`,
    ADD COLUMN `academic_level` VARCHAR(30) DEFAULT NULL AFTER `academic_major`;

ALTER TABLE `registrations`
    ADD COLUMN `payment_reference` VARCHAR(100) DEFAULT NULL AFTER `payment_order_guid`,
    ADD COLUMN `payment_status_text` VARCHAR(255) DEFAULT NULL AFTER `payment_reference`,
    ADD COLUMN `payment_status_id` INT DEFAULT NULL AFTER `payment_status_text`,
    ADD COLUMN `payment_checked_at` DATETIME DEFAULT NULL AFTER `payment_status_id`;

ALTER TABLE `registrations`
    ADD KEY `idx_payment_status` (`payment_status_id`);
