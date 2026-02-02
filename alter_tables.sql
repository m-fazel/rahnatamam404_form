-- Execute each statement once. If a column already exists, skip that statement.

ALTER TABLE `registrations`
    ADD COLUMN `academic_major` VARCHAR(50) DEFAULT NULL AFTER `married_status`,
    ADD COLUMN `academic_level` VARCHAR(30) DEFAULT NULL AFTER `academic_major`;

ALTER TABLE `group_members`
    ADD COLUMN `academic_major` VARCHAR(50) DEFAULT NULL AFTER `mobile`,
    ADD COLUMN `academic_level` VARCHAR(30) DEFAULT NULL AFTER `academic_major`;

ALTER TABLE `registrations`
    ADD COLUMN `payment_reference` VARCHAR(100) DEFAULT NULL AFTER `payment_order_guid`,
    ADD COLUMN `payment_status_text` VARCHAR(255) DEFAULT NULL AFTER `payment_reference`,
    ADD COLUMN `payment_status_id` INT DEFAULT NULL AFTER `payment_status_text`,
    ADD COLUMN `payment_checked_at` DATETIME DEFAULT NULL AFTER `payment_status_id`;

ALTER TABLE `registrations`
    ADD KEY `idx_payment_status` (`payment_status_id`);

ALTER TABLE `registrations`
    ADD COLUMN `payment_type` VARCHAR(20) DEFAULT NULL AFTER `academic_level`,
    ADD COLUMN `total_amount` BIGINT DEFAULT NULL AFTER `payment_type`,
    ADD COLUMN `discount_code_id` INT UNSIGNED DEFAULT NULL AFTER `total_amount`,
    ADD COLUMN `discount_code` VARCHAR(50) DEFAULT NULL AFTER `discount_code_id`,
    ADD COLUMN `discount_amount` BIGINT DEFAULT NULL AFTER `discount_code`;

ALTER TABLE `registrations`
    ADD KEY `idx_discount_code_id` (`discount_code_id`);

CREATE TABLE `discount_codes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(50) NOT NULL,
    `title` VARCHAR(150) DEFAULT NULL,
    `discount_type` VARCHAR(20) NOT NULL DEFAULT 'amount',
    `discount_value` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_discount_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
